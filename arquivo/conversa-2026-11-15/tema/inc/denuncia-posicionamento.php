<?php
/**
 * Pedido de posicionamento (Israel, 17/11/2026): antes de publicar a denúncia, o Portal manda
 * para o órgão responsável, por e-mail e/ou WhatsApp, o resumo e a foto (ou o print do vídeo),
 * com o prazo de publicação e um link exclusivo para responder. A resposta aparece no painel
 * e entra na matéria quando o editor marca "Incluir na matéria".
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- lista de órgãos (Denúncias → Órgãos responsáveis) ---------- */
function po_posic_orgaos() {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) get_option( 'po_orgaos', '' ) ) as $l ) {
		$p = array_map( 'trim', explode( '|', $l ) );
		if ( '' === $p[0] ) continue;
		$out[] = array( 'nome' => $p[0], 'email' => isset( $p[1] ) ? sanitize_email( $p[1] ) : '', 'whats' => isset( $p[2] ) ? preg_replace( '/\D/', '', $p[2] ) : '' );
	}
	return $out;
}
add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=po_denuncia', 'Órgãos responsáveis', 'Órgãos responsáveis', 'edit_posts', 'po-orgaos', 'po_posic_pagina_orgaos' );
} );
function po_posic_pagina_orgaos() {
	if ( isset( $_POST['po_orgaos_nonce'] ) && wp_verify_nonce( $_POST['po_orgaos_nonce'], 'po_orgaos' ) ) {
		update_option( 'po_orgaos', sanitize_textarea_field( wp_unslash( $_POST['po_orgaos'] ) ) );
		echo '<div class="updated"><p>Lista salva.</p></div>';
	}
	?>
	<div class="wrap">
		<h1>Órgãos responsáveis</h1>
		<p>Um órgão por linha, assim: <code>Nome | e-mail da imprensa | WhatsApp com DDD</code>. Pode deixar o e-mail ou o WhatsApp em branco.</p>
		<p>Exemplo: <code>Prefeitura de Santa Luzia | imprensa@santaluzia.mg.gov.br | 31 99999-0000</code></p>
		<form method="post">
			<?php wp_nonce_field( 'po_orgaos', 'po_orgaos_nonce' ); ?>
			<textarea name="po_orgaos" rows="16" style="width:100%;font-family:monospace"><?php echo esc_textarea( get_option( 'po_orgaos', '' ) ); ?></textarea>
			<?php submit_button( 'Salvar lista' ); ?>
		</form>
	</div>
	<?php
}

/* ---------- dados do pedido ---------- */
function po_posic_token( $id ) {
	$t = get_post_meta( $id, 'dc_posic_token', true );
	if ( ! $t ) { $t = wp_generate_password( 24, false ); update_post_meta( $id, 'dc_posic_token', $t ); }
	return $t;
}
function po_posic_link( $id ) { return add_query_arg( 'po_posic', po_posic_token( $id ), home_url( '/' ) ); }
function po_posic_orgao( $id ) {
	return array(
		'nome'  => (string) get_post_meta( $id, 'dc_posic_orgao', true ),
		'email' => (string) get_post_meta( $id, 'dc_posic_email', true ),
		'whats' => (string) get_post_meta( $id, 'dc_posic_whats', true ),
	);
}
/* foto da denúncia, ou o print do vídeo; devolve [url, arquivo] */
function po_posic_imagem( $id ) {
	$md  = po_denuncia_midias( $id );
	$aid = $md['foto'] ? $md['foto'] : (int) get_post_meta( $id, 'dc_capa_video', true );
	if ( ! $aid ) return array( '', '' );
	return array( (string) wp_get_attachment_url( $aid ), (string) get_attached_file( $aid ) );
}
function po_posic_resumo( $id ) {
	$t = trim( (string) get_post_meta( $id, 'dc_titulo_pub', true ) );
	$rel = trim( wp_strip_all_tags( get_post_field( 'post_content', $id ) ) );
	if ( mb_strlen( $rel ) > 600 ) $rel = rtrim( mb_substr( $rel, 0, 600 ) ) . '...';
	return array( $t, $rel );
}
function po_posic_quando( $id ) {
	$p = (int) get_post_meta( $id, 'dc_posic_prazo', true );
	return $p ? wp_date( 'd/m \à\s H\hi', $p ) : '';
}
function po_posic_marcar_envio( $id, $canal ) {
	$h = (int) get_post_meta( $id, 'dc_posic_horas', true ); if ( ! $h ) $h = 24;
	if ( ! get_post_meta( $id, 'dc_posic_prazo', true ) ) update_post_meta( $id, 'dc_posic_prazo', time() + $h * HOUR_IN_SECONDS );
	$log = (array) get_post_meta( $id, 'dc_posic_log', true );
	$o = po_posic_orgao( $id );
	$log[] = array( 't' => time(), 'canal' => $canal, 'para' => 'email' === $canal ? $o['email'] : $o['whats'], 'orgao' => $o['nome'] );
	update_post_meta( $id, 'dc_posic_log', array_values( array_filter( $log ) ) );
}

/* ---------- texto do WhatsApp ---------- */
function po_posic_texto_whats( $id ) {
	list( $tit, $rel ) = po_posic_resumo( $id );
	list( $img ) = po_posic_imagem( $id );
	$o = po_posic_orgao( $id );
	$prot = get_post_meta( $id, 'dc_protocolo', true );
	$local = get_post_meta( $id, 'dc_local', true );
	$s  = "Olá! Aqui é da redação do *Portal ONE News*.\n\n";
	$s .= 'Recebemos de um leitor o relato abaixo' . ( $local ? " ({$local})" : '' ) . ', protocolo ' . $prot . ".\n\n";
	if ( $tit ) $s .= '*' . $tit . "*\n";
	$s .= '"' . $rel . "\"\n\n";
	if ( $img ) $s .= "Foto enviada pelo leitor: {$img}\n\n";
	$s .= 'A matéria está prevista para ir ao ar em ' . po_posic_quando( $id ) . '. Caso ' . ( $o['nome'] ? $o['nome'] : 'o órgão' ) . " queira se manifestar, é só responder por aqui ou pelo link:\n" . po_posic_link( $id ) . "\n\nObrigado!";
	return $s;
}

/* ---------- caixa no painel da denúncia ---------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'dc_posic', 'Pedir posicionamento do órgão responsável', 'po_posic_metabox', 'po_denuncia', 'normal', 'default' );
} );
function po_posic_metabox( $post ) {
	wp_nonce_field( 'dc_posic', 'dc_posic_nonce' );
	$o = po_posic_orgao( $post->ID ); $lista = po_posic_orgaos();
	$h = (int) get_post_meta( $post->ID, 'dc_posic_horas', true ); if ( ! $h ) $h = 24;
	$prazo = (int) get_post_meta( $post->ID, 'dc_posic_prazo', true );
	$log = array_filter( (array) get_post_meta( $post->ID, 'dc_posic_log', true ) );
	$resp = get_post_meta( $post->ID, 'dc_posic_resposta', true );
	if ( isset( $_GET['po_posic_ok'] ) ) echo '<div class="notice notice-success inline"><p>' . esc_html( wp_unslash( $_GET['po_posic_ok'] ) ) . '</p></div>';
	?>
	<p style="margin-top:0">Manda para o órgão o resumo e a foto (ou o print do vídeo), com o prazo e um link para ele responder. O nome e o telefone do leitor <b>não</b> vão.</p>
	<p><b>Órgão</b> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=po_denuncia&page=po-orgaos' ) ); ?>" style="font-size:12px">(editar a lista)</a><br>
	<select id="dc_posic_lista" style="width:100%"><option value="">— escolher da lista —</option>
	<?php foreach ( $lista as $i => $x ) : ?><option value="<?php echo (int) $i; ?>" data-n="<?php echo esc_attr( $x['nome'] ); ?>" data-e="<?php echo esc_attr( $x['email'] ); ?>" data-w="<?php echo esc_attr( $x['whats'] ); ?>"><?php echo esc_html( $x['nome'] ); ?></option><?php endforeach; ?>
	</select></p>
	<p><input type="text" name="dc_posic_orgao" id="dc_posic_orgao" value="<?php echo esc_attr( $o['nome'] ); ?>" placeholder="Nome do órgão (ex.: Prefeitura de Santa Luzia)" style="width:100%"></p>
	<p><input type="email" name="dc_posic_email" id="dc_posic_email" value="<?php echo esc_attr( $o['email'] ); ?>" placeholder="E-mail da imprensa" style="width:49%">
	<input type="text" name="dc_posic_whats" id="dc_posic_whats" value="<?php echo esc_attr( $o['whats'] ); ?>" placeholder="WhatsApp com DDD" style="width:49%"></p>
	<p><b>Prazo para responder</b><br><select name="dc_posic_horas">
		<?php foreach ( array( 2 => '2 horas (urgente)', 24 => '24 horas', 48 => '48 horas' ) as $k => $v ) : ?><option value="<?php echo (int) $k; ?>" <?php selected( $h, $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?>
	</select> <span style="color:#646970">conta a partir do primeiro envio</span></p>
	<p style="color:#b32d2e">Mudou o órgão ou o prazo? Clique em <b>Atualizar</b> (lá em cima) antes de enviar.</p>
	<p>
		<?php if ( $o['email'] ) : ?><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=po_posic_email&post=' . $post->ID ), 'po_posic_' . $post->ID ) ); ?>">Enviar e-mail</a><?php endif; ?>
		<?php if ( $o['whats'] ) : ?><a class="button" style="background:#25d366;border-color:#1da851;color:#fff" target="_blank" rel="noopener" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=po_posic_whats&post=' . $post->ID ), 'po_posic_' . $post->ID ) ); ?>">Abrir no WhatsApp</a><?php endif; ?>
		<?php if ( ! $o['email'] && ! $o['whats'] ) echo '<i>Escolha o órgão, preencha o e-mail e/ou o WhatsApp e clique em Atualizar.</i>'; ?>
	</p>
	<p style="color:#646970;font-size:12px">O WhatsApp abre no seu celular ou computador com a mensagem pronta (resumo, link da foto e prazo): é só conferir e enviar. Para mandar a foto como imagem, baixe-a e anexe na conversa.</p>
	<?php if ( $log ) : ?>
		<hr><p style="margin-bottom:4px"><b>Histórico</b></p><ul style="margin-top:0">
		<?php foreach ( $log as $l ) echo '<li>' . esc_html( wp_date( 'd/m H\hi', $l['t'] ) . ' · ' . ( 'email' === $l['canal'] ? 'e-mail para ' : 'WhatsApp para ' ) . $l['para'] . ( $l['orgao'] ? ' (' . $l['orgao'] . ')' : '' ) ) . '</li>'; ?>
		</ul>
		<p><b>Prazo:</b> <?php echo esc_html( po_posic_quando( $post->ID ) ); echo $prazo && time() > $prazo ? ' <span style="color:#b32d2e">(vencido)</span>' : ' <span style="color:#646970">(se publicar antes, a matéria sai sem a resposta)</span>'; ?></p>
	<?php endif; ?>
	<?php if ( $resp ) : ?>
		<hr><p style="margin-bottom:4px"><b>Resposta recebida</b> em <?php echo esc_html( wp_date( 'd/m H\hi', (int) get_post_meta( $post->ID, 'dc_posic_resp_em', true ) ) ); ?> · <?php echo esc_html( trim( get_post_meta( $post->ID, 'dc_posic_resp_nome', true ) . ', ' . get_post_meta( $post->ID, 'dc_posic_resp_cargo', true ), ', ' ) ); ?></p>
		<blockquote style="background:#f6f7f7;padding:8px 12px;margin:0"><?php echo nl2br( esc_html( $resp ) ); ?></blockquote>
		<p><label><input type="checkbox" name="dc_posic_usar" value="1" <?php checked( get_post_meta( $post->ID, 'dc_posic_usar', true ) ); ?>> <b>Incluir a resposta na matéria</b> (e na legenda do Instagram)</label></p>
		<p><textarea name="dc_posic_resposta_ed" rows="4" style="width:100%" placeholder="Pode editar o texto que vai na matéria"><?php echo esc_textarea( get_post_meta( $post->ID, 'dc_posic_resposta_ed', true ) ?: $resp ); ?></textarea></p>
	<?php endif; ?>
	<script>
	(function(){var s=document.getElementById('dc_posic_lista');if(!s)return;s.addEventListener('change',function(){var o=s.options[s.selectedIndex];if(!o.value)return;
	document.getElementById('dc_posic_orgao').value=o.dataset.n;document.getElementById('dc_posic_email').value=o.dataset.e;document.getElementById('dc_posic_whats').value=o.dataset.w;});})();
	</script>
	<?php
}
/* salva antes do salvamento da denúncia (que atualiza a matéria) */
add_action( 'save_post_po_denuncia', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) return;
	if ( ! isset( $_POST['dc_posic_nonce'] ) || ! wp_verify_nonce( $_POST['dc_posic_nonce'], 'dc_posic' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	update_post_meta( $post_id, 'dc_posic_orgao', sanitize_text_field( wp_unslash( $_POST['dc_posic_orgao'] ?? '' ) ) );
	update_post_meta( $post_id, 'dc_posic_email', sanitize_email( wp_unslash( $_POST['dc_posic_email'] ?? '' ) ) );
	$w = preg_replace( '/\D/', '', (string) ( $_POST['dc_posic_whats'] ?? '' ) );
	update_post_meta( $post_id, 'dc_posic_whats', $w );
	$h = absint( $_POST['dc_posic_horas'] ?? 24 ); if ( ! in_array( $h, array( 2, 24, 48 ), true ) ) $h = 24;
	update_post_meta( $post_id, 'dc_posic_horas', $h );
	if ( get_post_meta( $post_id, 'dc_posic_resposta', true ) ) {
		$antes = get_post_meta( $post_id, 'dc_posic_usar', true ) . '|' . get_post_meta( $post_id, 'dc_posic_resposta_ed', true );
		update_post_meta( $post_id, 'dc_posic_usar', empty( $_POST['dc_posic_usar'] ) ? '' : '1' );
		if ( isset( $_POST['dc_posic_resposta_ed'] ) ) update_post_meta( $post_id, 'dc_posic_resposta_ed', sanitize_textarea_field( wp_unslash( $_POST['dc_posic_resposta_ed'] ) ) );
		$depois = get_post_meta( $post_id, 'dc_posic_usar', true ) . '|' . get_post_meta( $post_id, 'dc_posic_resposta_ed', true );
		if ( $antes !== $depois ) $GLOBALS['po_den_relato_mudou'] = true; // a matéria é refeita no salvamento da denúncia
	}
}, 5 );

/* ---------- envio ---------- */
function po_posic_volta( $id, $msg ) {
	wp_safe_redirect( add_query_arg( 'po_posic_ok', rawurlencode( $msg ), get_edit_post_link( $id, 'url' ) ) . '#dc_posic' ); exit;
}
add_action( 'admin_post_po_posic_email', function () {
	$id = absint( $_GET['post'] ?? 0 );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'po_posic_' . $id ) ) wp_die( 'Sem permissão.' );
	$o = po_posic_orgao( $id );
	if ( ! is_email( $o['email'] ) ) po_posic_volta( $id, 'E-mail do órgão inválido.' );
	if ( ! get_post_meta( $id, 'dc_posic_prazo', true ) ) { $h = (int) get_post_meta( $id, 'dc_posic_horas', true ) ?: 24; update_post_meta( $id, 'dc_posic_prazo', time() + $h * HOUR_IN_SECONDS ); }
	list( $tit, $rel ) = po_posic_resumo( $id );
	list( $img, $arq ) = po_posic_imagem( $id );
	$prot = get_post_meta( $id, 'dc_protocolo', true ); $local = get_post_meta( $id, 'dc_local', true );
	$quando = po_posic_quando( $id ); $link = po_posic_link( $id );
	$corpo  = '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#1d2327;max-width:620px">';
	$corpo .= '<p>Prezados' . ( $o['nome'] ? ' (' . esc_html( $o['nome'] ) . ')' : '' ) . ',</p>';
	$corpo .= '<p>A redação do <b>Portal ONE News</b> recebeu de um leitor o relato abaixo' . ( $local ? ', sobre <b>' . esc_html( $local ) . '</b>' : '' ) . ' (protocolo ' . esc_html( $prot ) . ').</p>';
	if ( $tit ) $corpo .= '<p style="font-size:17px"><b>' . esc_html( $tit ) . '</b></p>';
	$corpo .= '<blockquote style="border-left:4px solid #1f3b8f;margin:0;padding:6px 14px;background:#f4f6fb">' . nl2br( esc_html( $rel ) ) . '</blockquote>';
	if ( $img ) $corpo .= '<p><img src="' . esc_url( $img ) . '" alt="Imagem enviada pelo leitor" style="max-width:100%;border-radius:8px"></p>';
	$corpo .= '<p>A matéria está prevista para ser publicada em <b>' . esc_html( $quando ) . '</b>. Caso queiram se manifestar, por favor respondam até lá pelo link abaixo (ou respondendo a este e-mail):</p>';
	$corpo .= '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#1f3b8f;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold">Enviar o posicionamento</a></p>';
	$corpo .= '<p>Sem resposta até o prazo, a matéria informará que o órgão foi procurado e não se manifestou. O espaço segue aberto depois da publicação.</p>';
	$corpo .= '<p>Atenciosamente,<br>Redação Portal ONE News<br><a href="' . esc_url( home_url( '/' ) ) . '">portalonenews.com.br</a></p></div>';
	$resp_para = get_option( 'po_denuncia_email', get_option( 'admin_email' ) );
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: Portal ONE News <' . get_option( 'admin_email' ) . '>', 'Reply-To: Redação Portal ONE News <' . $resp_para . '>' );
	$ok = wp_mail( $o['email'], 'Pedido de posicionamento · Portal ONE News · Protocolo ' . $prot, $corpo, $headers, ( $arq && file_exists( $arq ) ) ? array( $arq ) : array() );
	if ( ! $ok ) po_posic_volta( $id, 'O site não conseguiu enviar o e-mail. Use o WhatsApp ou tente de novo.' );
	po_posic_marcar_envio( $id, 'email' );
	po_posic_volta( $id, 'E-mail enviado para ' . $o['email'] . '. Prazo: ' . $quando . '.' );
} );
add_action( 'admin_post_po_posic_whats', function () {
	$id = absint( $_GET['post'] ?? 0 );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'po_posic_' . $id ) ) wp_die( 'Sem permissão.' );
	$o = po_posic_orgao( $id ); $n = $o['whats'];
	if ( strlen( $n ) < 10 ) wp_die( 'WhatsApp do órgão inválido. Coloque com DDD e clique em Atualizar.' );
	if ( 0 !== strpos( $n, '55' ) || strlen( $n ) <= 11 ) $n = '55' . $n;
	if ( ! get_post_meta( $id, 'dc_posic_prazo', true ) ) { $h = (int) get_post_meta( $id, 'dc_posic_horas', true ) ?: 24; update_post_meta( $id, 'dc_posic_prazo', time() + $h * HOUR_IN_SECONDS ); }
	po_posic_marcar_envio( $id, 'whats' );
	wp_redirect( 'https://wa.me/' . $n . '?text=' . rawurlencode( po_posic_texto_whats( $id ) ) ); exit;
} );

/* ---------- página pública de resposta (link exclusivo) ---------- */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['po_posic'] ) ) return;
	$tok = preg_replace( '/[^A-Za-z0-9]/', '', (string) $_GET['po_posic'] );
	$q = get_posts( array( 'post_type' => 'po_denuncia', 'post_status' => 'any', 'meta_key' => 'dc_posic_token', 'meta_value' => $tok, 'fields' => 'ids', 'posts_per_page' => 1 ) );
	status_header( $q ? 200 : 404 ); nocache_headers();
	$id = $q ? $q[0] : 0; $msg = '';
	if ( $id && 'POST' === $_SERVER['REQUEST_METHOD'] && empty( $_POST['site'] ) ) {
		$txt = sanitize_textarea_field( wp_unslash( $_POST['resposta'] ?? '' ) );
		if ( mb_strlen( $txt ) >= 10 ) {
			update_post_meta( $id, 'dc_posic_resposta', mb_substr( $txt, 0, 5000 ) );
			update_post_meta( $id, 'dc_posic_resp_nome', sanitize_text_field( wp_unslash( $_POST['nome'] ?? '' ) ) );
			update_post_meta( $id, 'dc_posic_resp_cargo', sanitize_text_field( wp_unslash( $_POST['cargo'] ?? '' ) ) );
			update_post_meta( $id, 'dc_posic_resp_em', time() );
			delete_post_meta( $id, 'dc_posic_resposta_ed' );
			$msg = 'Posicionamento recebido. Obrigado! A redação do Portal ONE News vai analisar e incluir na matéria.';
		} else {
			$msg = 'Escreva o posicionamento (pelo menos uma frase) e envie de novo.';
		}
	}
	list( $tit, $rel ) = $id ? po_posic_resumo( $id ) : array( '', '' );
	list( $img ) = $id ? po_posic_imagem( $id ) : array( '' );
	?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
	<title>Posicionamento · Portal ONE News</title>
	<style>body{margin:0;background:#f2f4f8;font-family:Arial,sans-serif;color:#1d2327}.c{max-width:640px;margin:0 auto;padding:24px 16px}.k{background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.06)}h1{font-size:22px;margin:0 0 6px;color:#1f3b8f}blockquote{border-left:4px solid #1f3b8f;margin:12px 0;padding:6px 14px;background:#f4f6fb}img{max-width:100%;border-radius:10px}label{display:block;font-weight:bold;margin:14px 0 4px}input,textarea{width:100%;box-sizing:border-box;padding:11px;border:1px solid #c3c4c7;border-radius:8px;font:inherit}button{margin-top:16px;background:#1f3b8f;color:#fff;border:0;border-radius:10px;padding:14px 22px;font:bold 16px Arial;cursor:pointer;width:100%}.m{background:#e7f6ec;border-radius:8px;padding:12px;margin-bottom:12px}.h{position:absolute;left:-9999px}</style>
	</head><body><div class="c"><div class="k">
	<h1>Portal ONE News · Pedido de posicionamento</h1>
	<?php if ( ! $id ) : ?><p>Este link não é válido ou expirou.</p><?php else : ?>
		<?php if ( $msg ) echo '<div class="m">' . esc_html( $msg ) . '</div>'; ?>
		<p>Protocolo <b><?php echo esc_html( get_post_meta( $id, 'dc_protocolo', true ) ); ?></b> · publicação prevista para <b><?php echo esc_html( po_posic_quando( $id ) ); ?></b></p>
		<?php if ( $tit ) echo '<p><b>' . esc_html( $tit ) . '</b></p>'; ?>
		<blockquote><?php echo nl2br( esc_html( $rel ) ); ?></blockquote>
		<?php if ( $img ) echo '<p><img src="' . esc_url( $img ) . '" alt="Imagem enviada pelo leitor"></p>'; ?>
		<form method="post">
			<label for="nome">Seu nome</label><input id="nome" name="nome" required>
			<label for="cargo">Cargo / órgão</label><input id="cargo" name="cargo" placeholder="Ex.: Assessoria de Comunicação da Prefeitura">
			<label for="resposta">Posicionamento</label><textarea id="resposta" name="resposta" rows="8" required></textarea>
			<input class="h" name="site" tabindex="-1" autocomplete="off">
			<button type="submit">Enviar posicionamento</button>
		</form>
	<?php endif; ?>
	</div></div></body></html><?php
	exit;
} );

/* ---------- texto que vai na matéria e na legenda ---------- */
function po_posic_texto( $id ) {
	$o = po_posic_orgao( $id );
	$log = array_filter( (array) get_post_meta( $id, 'dc_posic_log', true ) );
	if ( ! $o['nome'] || ! $log ) return '';
	$resp = get_post_meta( $id, 'dc_posic_usar', true ) ? ( get_post_meta( $id, 'dc_posic_resposta_ed', true ) ?: get_post_meta( $id, 'dc_posic_resposta', true ) ) : '';
	if ( $resp ) return 'O Portal ONE News procurou ' . $o['nome'] . ', que informou: "' . trim( $resp ) . '"';
	$prazo = (int) get_post_meta( $id, 'dc_posic_prazo', true );
	if ( $prazo && time() >= $prazo && ! get_post_meta( $id, 'dc_posic_resposta', true ) ) return 'O Portal ONE News procurou ' . $o['nome'] . ', que não se manifestou até a publicação desta matéria. O espaço segue aberto.';
	return '';
}
