<?php
/**
 * Pedido de posicionamento (Israel, 17/11/2026): antes de publicar a denúncia, o Portal manda
 * para quem responde pela instituição (diretor, assessoria, secretário...), por e-mail e/ou
 * WhatsApp, o resumo e a foto (ou o print do vídeo), com o prazo de publicação e um link exclusivo
 * para responder. A resposta aparece no painel e entra na matéria quando o editor marca "Incluir".
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- lista (Denúncias → Órgãos responsáveis): uma pessoa por linha ---------- */
function po_posic_orgaos() {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) get_option( 'po_orgaos', '' ) ) as $l ) {
		$p = array_map( 'trim', explode( '|', $l ) );
		if ( '' === $p[0] ) continue;
		$c = array( 'nome' => $p[1] ?? '', 'email' => sanitize_email( $p[2] ?? '' ), 'whats' => preg_replace( '/\D/', '', $p[3] ?? '' ) );
		if ( ! isset( $out[ $p[0] ] ) ) $out[ $p[0] ] = array();
		if ( $c['nome'] || $c['email'] || $c['whats'] ) $out[ $p[0] ][] = $c;
	}
	return $out; // [ 'Instituição' => [ [nome, email, whats], ... ] ]
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
		<p><b>Uma pessoa por linha</b>, assim: <code>Instituição | Nome e cargo | e-mail | WhatsApp com DDD</code>. Pode deixar o e-mail ou o WhatsApp em branco. A mesma instituição pode ter várias linhas (diretor, assessoria, secretário...).</p>
		<p>Exemplo:<br>
		<code>Hospital Municipal (HMPC) | Dr. Fulano de Tal, diretor | diretoria@hospital.mg.gov.br | 31 99999-0000</code><br>
		<code>Hospital Municipal (HMPC) | Assessoria de imprensa | imprensa@hospital.mg.gov.br |</code><br>
		<code>Prefeitura de Santa Luzia | Assessoria de Comunicação | imprensa@santaluzia.mg.gov.br | 31 98888-0000</code></p>
		<form method="post">
			<?php wp_nonce_field( 'po_orgaos', 'po_orgaos_nonce' ); ?>
			<textarea name="po_orgaos" rows="18" style="width:100%;font-family:monospace"><?php echo esc_textarea( get_option( 'po_orgaos', '' ) ); ?></textarea>
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
function po_posic_orgao( $id ) { return array( 'nome' => (string) get_post_meta( $id, 'dc_posic_orgao', true ) ); }
/* destinatários escolhidos nesta denúncia: [nome, email, whats, marcado] */
function po_posic_dest( $id ) {
	$d = get_post_meta( $id, 'dc_posic_dest', true );
	if ( ! is_array( $d ) ) $d = array();
	if ( ! $d && ( get_post_meta( $id, 'dc_posic_email', true ) || get_post_meta( $id, 'dc_posic_whats', true ) ) ) // versão anterior
		$d[] = array( 'nome' => '', 'email' => (string) get_post_meta( $id, 'dc_posic_email', true ), 'whats' => (string) get_post_meta( $id, 'dc_posic_whats', true ), 'on' => 1 );
	return $d;
}
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
function po_posic_iniciar_prazo( $id ) {
	if ( get_post_meta( $id, 'dc_posic_prazo', true ) ) return;
	$h = (int) get_post_meta( $id, 'dc_posic_horas', true ); if ( ! $h ) $h = 24;
	update_post_meta( $id, 'dc_posic_prazo', time() + $h * HOUR_IN_SECONDS );
}
function po_posic_marcar_envio( $id, $canal, $c ) {
	$log = array_filter( (array) get_post_meta( $id, 'dc_posic_log', true ) );
	$log[] = array( 't' => time(), 'canal' => $canal, 'para' => 'email' === $canal ? $c['email'] : $c['whats'], 'quem' => $c['nome'], 'orgao' => po_posic_orgao( $id )['nome'] );
	update_post_meta( $id, 'dc_posic_log', array_values( $log ) );
}
function po_posic_saudacao( $nome ) { return $nome ? preg_replace( '/,.*$/', '', $nome ) : ''; }

/* ---------- texto do WhatsApp ---------- */
function po_posic_texto_whats( $id, $c ) {
	list( $tit, $rel ) = po_posic_resumo( $id );
	list( $img ) = po_posic_imagem( $id );
	$org = po_posic_orgao( $id )['nome'];
	$prot = get_post_meta( $id, 'dc_protocolo', true );
	$local = get_post_meta( $id, 'dc_local', true );
	$s  = ( po_posic_saudacao( $c['nome'] ) ? 'Olá, ' . po_posic_saudacao( $c['nome'] ) . '! ' : 'Olá! ' ) . "Aqui é da redação do *Portal ONE News*.\n\n";
	$s .= 'Recebemos de um leitor o relato abaixo' . ( $org ? ', envolvendo ' . $org : '' ) . ( $local ? " ({$local})" : '' ) . ', protocolo ' . $prot . ".\n\n";
	if ( $tit ) $s .= '*' . $tit . "*\n";
	$s .= '"' . $rel . "\"\n\n";
	if ( $img ) $s .= "Imagem enviada pelo leitor: {$img}\n\n";
	$s .= 'A matéria está prevista para ir ao ar em ' . po_posic_quando( $id ) . ". Caso queira se manifestar, é só responder por aqui ou pelo link:\n" . po_posic_link( $id ) . "\n\nObrigado!";
	return $s;
}

/* ---------- caixa no painel da denúncia ---------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'dc_posic', 'Pedir posicionamento do órgão responsável', 'po_posic_metabox', 'po_denuncia', 'normal', 'default' );
} );
function po_posic_metabox( $post ) {
	wp_nonce_field( 'dc_posic', 'dc_posic_nonce' );
	$org = po_posic_orgao( $post->ID )['nome']; $lista = po_posic_orgaos(); $dest = po_posic_dest( $post->ID );
	while ( count( $dest ) < 4 ) $dest[] = array( 'nome' => '', 'email' => '', 'whats' => '', 'on' => 0 );
	$h = (int) get_post_meta( $post->ID, 'dc_posic_horas', true ); if ( ! $h ) $h = 24;
	$prazo = (int) get_post_meta( $post->ID, 'dc_posic_prazo', true );
	$log = array_filter( (array) get_post_meta( $post->ID, 'dc_posic_log', true ) );
	$resp = get_post_meta( $post->ID, 'dc_posic_resposta', true );
	if ( isset( $_GET['po_posic_ok'] ) ) echo '<div class="notice notice-success inline"><p>' . esc_html( wp_unslash( $_GET['po_posic_ok'] ) ) . '</p></div>';
	?>
	<style>#dc_posic table{width:100%;border-collapse:collapse}#dc_posic td{padding:3px 2px;vertical-align:middle}#dc_posic td input[type=text],#dc_posic td input[type=email]{width:100%}#dc_posic .bt{white-space:nowrap}</style>
	<p style="margin-top:0">Manda o resumo e a foto (ou o print do vídeo), com o prazo e um link para a pessoa responder. O nome e o telefone do leitor <b>não</b> vão.</p>
	<p><b>Instituição</b> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=po_denuncia&page=po-orgaos' ) ); ?>" style="font-size:12px">(editar a lista)</a><br>
	<select id="dc_posic_lista" style="width:100%"><option value="">— escolher da lista (preenche as pessoas abaixo) —</option>
	<?php foreach ( $lista as $inst => $cs ) : ?><option value="<?php echo esc_attr( $inst ); ?>" data-c="<?php echo esc_attr( wp_json_encode( $cs ) ); ?>"><?php echo esc_html( $inst ); ?></option><?php endforeach; ?>
	</select></p>
	<p><input type="text" name="dc_posic_orgao" id="dc_posic_orgao" value="<?php echo esc_attr( $org ); ?>" placeholder="Nome da instituição (ex.: Hospital Municipal, Prefeitura de Santa Luzia)" style="width:100%"></p>
	<p style="margin-bottom:4px"><b>Para quem enviar</b> <span style="color:#646970">(marque quem recebe; pode digitar outra pessoa)</span></p>
	<table><tr style="color:#646970;font-size:12px"><td style="width:26px"></td><td>Nome e cargo</td><td>E-mail</td><td style="width:21%">WhatsApp com DDD</td><td></td></tr>
	<?php foreach ( $dest as $i => $c ) : ?>
		<tr>
			<td><input type="checkbox" name="dc_posic_dest[<?php echo (int) $i; ?>][on]" value="1" <?php checked( ! empty( $c['on'] ) ); ?>></td>
			<td><input type="text" name="dc_posic_dest[<?php echo (int) $i; ?>][nome]" value="<?php echo esc_attr( $c['nome'] ); ?>" placeholder="Ex.: Dr. Fulano, diretor"></td>
			<td><input type="email" name="dc_posic_dest[<?php echo (int) $i; ?>][email]" value="<?php echo esc_attr( $c['email'] ); ?>" placeholder="e-mail"></td>
			<td><input type="text" name="dc_posic_dest[<?php echo (int) $i; ?>][whats]" value="<?php echo esc_attr( $c['whats'] ); ?>" placeholder="31 9...."></td>
			<td class="bt">
				<button type="submit" class="button" name="dc_posic_acao" value="email:<?php echo (int) $i; ?>" title="Salva e manda o e-mail para esta pessoa">E-mail</button>
				<button type="submit" class="button" style="background:#25d366;border-color:#1da851;color:#fff" name="dc_posic_acao" value="whats:<?php echo (int) $i; ?>" formtarget="_blank" title="Salva e abre o WhatsApp desta pessoa com a mensagem pronta">WhatsApp</button>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
	<p><b>Prazo para responder</b><br><select name="dc_posic_horas">
		<?php foreach ( array( 2 => '2 horas (urgente)', 24 => '24 horas', 48 => '48 horas' ) as $k => $v ) : ?><option value="<?php echo (int) $k; ?>" <?php selected( $h, $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?>
	</select> <span style="color:#646970">conta a partir do primeiro envio</span></p>
	<p><button type="submit" class="button button-primary" name="dc_posic_acao" value="email:todos">Enviar e-mail para todos os marcados</button></p>
	<p style="color:#646970;font-size:12px">Os botões já salvam a denúncia antes de enviar. O WhatsApp abre numa aba nova, no seu WhatsApp, com a mensagem pronta (saudação com o nome, resumo, link da imagem, prazo e link para responder): é só conferir e apertar enviar. O e-mail sai pelo site, com a imagem anexada.</p>
	<?php if ( $log ) : ?>
		<hr><p style="margin-bottom:4px"><b>Histórico</b></p><ul style="margin-top:0">
		<?php foreach ( $log as $l ) echo '<li>' . esc_html( wp_date( 'd/m H\hi', $l['t'] ) . ' · ' . ( 'email' === $l['canal'] ? 'e-mail' : 'WhatsApp' ) . ' para ' . ( ! empty( $l['quem'] ) ? $l['quem'] . ' (' . $l['para'] . ')' : $l['para'] ) . ( ! empty( $l['orgao'] ) ? ' · ' . $l['orgao'] : '' ) ) . '</li>'; ?>
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
	document.getElementById('dc_posic_orgao').value=o.value;var cs=JSON.parse(o.dataset.c||'[]');
	for(var i=0;i<4;i++){var c=cs[i]||{nome:'',email:'',whats:''};
	document.querySelector('[name="dc_posic_dest['+i+'][nome]"]').value=c.nome;document.querySelector('[name="dc_posic_dest['+i+'][email]"]').value=c.email;
	document.querySelector('[name="dc_posic_dest['+i+'][whats]"]').value=c.whats;document.querySelector('[name="dc_posic_dest['+i+'][on]"]').checked=!!cs[i];}});})();
	</script>
	<?php
}
/* salva antes do salvamento da denúncia (que atualiza a matéria) */
add_action( 'save_post_po_denuncia', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) return;
	if ( ! isset( $_POST['dc_posic_nonce'] ) || ! wp_verify_nonce( $_POST['dc_posic_nonce'], 'dc_posic' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	update_post_meta( $post_id, 'dc_posic_orgao', sanitize_text_field( wp_unslash( $_POST['dc_posic_orgao'] ?? '' ) ) );
	$dest = array();
	foreach ( (array) ( $_POST['dc_posic_dest'] ?? array() ) as $c ) {
		$c = array( 'nome' => sanitize_text_field( wp_unslash( $c['nome'] ?? '' ) ), 'email' => sanitize_email( wp_unslash( $c['email'] ?? '' ) ), 'whats' => preg_replace( '/\D/', '', (string) ( $c['whats'] ?? '' ) ), 'on' => empty( $c['on'] ) ? 0 : 1 );
		if ( $c['nome'] || $c['email'] || $c['whats'] ) $dest[] = $c;
	}
	update_post_meta( $post_id, 'dc_posic_dest', $dest );
	$h = absint( $_POST['dc_posic_horas'] ?? 24 ); if ( ! in_array( $h, array( 2, 24, 48 ), true ) ) $h = 24;
	update_post_meta( $post_id, 'dc_posic_horas', $h );
	if ( ! empty( $_POST['dc_posic_acao'] ) ) {
		// o envio acontece depois de salvar: guarda qual botão foi clicado (com o número da linha já sem as vazias)
		$acao = sanitize_text_field( wp_unslash( $_POST['dc_posic_acao'] ) );
		list( $tipo, $alvo ) = array_pad( explode( ':', $acao ), 2, '' );
		if ( 'todos' !== $alvo ) { // acha a linha certa depois de remover as vazias
			$c = $_POST['dc_posic_dest'][ (int) $alvo ] ?? array();
			$alvo = '';
			foreach ( $dest as $k => $x ) if ( $x['email'] === sanitize_email( wp_unslash( $c['email'] ?? '' ) ) && $x['whats'] === preg_replace( '/\D/', '', (string) ( $c['whats'] ?? '' ) ) && $x['nome'] === sanitize_text_field( wp_unslash( $c['nome'] ?? '' ) ) ) { $alvo = (string) $k; break; }
		}
		if ( '' !== $alvo ) $GLOBALS['po_posic_depois'] = array( $tipo, $alvo );
	}
	if ( get_post_meta( $post_id, 'dc_posic_resposta', true ) ) {
		$antes = get_post_meta( $post_id, 'dc_posic_usar', true ) . '|' . get_post_meta( $post_id, 'dc_posic_resposta_ed', true );
		update_post_meta( $post_id, 'dc_posic_usar', empty( $_POST['dc_posic_usar'] ) ? '' : '1' );
		if ( isset( $_POST['dc_posic_resposta_ed'] ) ) update_post_meta( $post_id, 'dc_posic_resposta_ed', sanitize_textarea_field( wp_unslash( $_POST['dc_posic_resposta_ed'] ) ) );
		$depois = get_post_meta( $post_id, 'dc_posic_usar', true ) . '|' . get_post_meta( $post_id, 'dc_posic_resposta_ed', true );
		if ( $antes !== $depois ) $GLOBALS['po_den_relato_mudou'] = true; // a matéria é refeita no salvamento da denúncia
	}
}, 5 );
/* depois de salvar, em vez de voltar para a tela, vai para o envio */
add_filter( 'redirect_post_location', function ( $loc, $post_id ) {
	if ( empty( $GLOBALS['po_posic_depois'] ) ) return $loc;
	list( $tipo, $alvo ) = $GLOBALS['po_posic_depois'];
	return wp_nonce_url( admin_url( 'admin-post.php?action=po_posic_' . ( 'whats' === $tipo ? 'whats' : 'email' ) . '&post=' . $post_id . '&alvo=' . rawurlencode( $alvo ) ), 'po_posic_' . $post_id );
}, 10, 2 );

/* ---------- envio ---------- */
function po_posic_volta( $id, $msg ) {
	wp_safe_redirect( add_query_arg( 'po_posic_ok', rawurlencode( $msg ), get_edit_post_link( $id, 'url' ) ) . '#dc_posic' ); exit;
}
function po_posic_alvos( $id ) {
	$dest = po_posic_dest( $id ); $alvo = (string) ( $_GET['alvo'] ?? '' );
	if ( 'todos' === $alvo ) return array_values( array_filter( $dest, function ( $c ) { return ! empty( $c['on'] ); } ) );
	return isset( $dest[ (int) $alvo ] ) && '' !== $alvo ? array( $dest[ (int) $alvo ] ) : array();
}
function po_posic_email_html( $id, $c ) {
	list( $tit, $rel ) = po_posic_resumo( $id );
	list( $img ) = po_posic_imagem( $id );
	$org = po_posic_orgao( $id )['nome'];
	$prot = get_post_meta( $id, 'dc_protocolo', true ); $local = get_post_meta( $id, 'dc_local', true );
	$s  = '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#1d2327;max-width:620px">';
	$s .= '<p>' . ( po_posic_saudacao( $c['nome'] ) ? 'Prezado(a) ' . esc_html( po_posic_saudacao( $c['nome'] ) ) : 'Prezados' ) . ',</p>';
	$s .= '<p>A redação do <b>Portal ONE News</b> recebeu de um leitor o relato abaixo' . ( $org ? ', envolvendo <b>' . esc_html( $org ) . '</b>' : '' ) . ( $local ? ' (' . esc_html( $local ) . ')' : '' ) . ', protocolo ' . esc_html( $prot ) . '.</p>';
	if ( $tit ) $s .= '<p style="font-size:17px"><b>' . esc_html( $tit ) . '</b></p>';
	$s .= '<blockquote style="border-left:4px solid #1f3b8f;margin:0;padding:6px 14px;background:#f4f6fb">' . nl2br( esc_html( $rel ) ) . '</blockquote>';
	if ( $img ) $s .= '<p><img src="' . esc_url( $img ) . '" alt="Imagem enviada pelo leitor" style="max-width:100%;border-radius:8px"></p>';
	$s .= '<p>A matéria está prevista para ser publicada em <b>' . esc_html( po_posic_quando( $id ) ) . '</b>. Caso queira se manifestar, por favor responda até lá pelo link abaixo (ou respondendo a este e-mail):</p>';
	$s .= '<p><a href="' . esc_url( po_posic_link( $id ) ) . '" style="display:inline-block;background:#1f3b8f;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;font-weight:bold">Enviar o posicionamento</a></p>';
	$s .= '<p>Sem resposta até o prazo, a matéria informará que a instituição foi procurada e não se manifestou. O espaço segue aberto depois da publicação.</p>';
	$s .= '<p>Atenciosamente,<br>Redação Portal ONE News<br><a href="' . esc_url( home_url( '/' ) ) . '">portalonenews.com.br</a></p></div>';
	return $s;
}
add_action( 'admin_post_po_posic_email', function () {
	$id = absint( $_GET['post'] ?? 0 );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'po_posic_' . $id ) ) wp_die( 'Sem permissão.' );
	$alvos = array_values( array_filter( po_posic_alvos( $id ), function ( $c ) { return is_email( $c['email'] ); } ) );
	if ( ! $alvos ) po_posic_volta( $id, 'Ninguém com e-mail válido para enviar. Confira o e-mail da pessoa (e se ela está marcada).' );
	po_posic_iniciar_prazo( $id );
	list( , $arq ) = po_posic_imagem( $id );
	$prot = get_post_meta( $id, 'dc_protocolo', true );
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: Portal ONE News <' . get_option( 'admin_email' ) . '>', 'Reply-To: Redação Portal ONE News <' . get_option( 'po_denuncia_email', get_option( 'admin_email' ) ) . '>' );
	$ok = array(); $erro = array();
	foreach ( $alvos as $c ) {
		if ( wp_mail( $c['email'], 'Pedido de posicionamento · Portal ONE News · Protocolo ' . $prot, po_posic_email_html( $id, $c ), $headers, ( $arq && file_exists( $arq ) ) ? array( $arq ) : array() ) ) { po_posic_marcar_envio( $id, 'email', $c ); $ok[] = $c['nome'] ?: $c['email']; }
		else $erro[] = $c['nome'] ?: $c['email'];
	}
	po_posic_volta( $id, ( $ok ? 'E-mail enviado para: ' . implode( ', ', $ok ) . '. Prazo: ' . po_posic_quando( $id ) . '.' : '' ) . ( $erro ? ' O site não conseguiu enviar para: ' . implode( ', ', $erro ) . '.' : '' ) );
} );
add_action( 'admin_post_po_posic_whats', function () {
	$id = absint( $_GET['post'] ?? 0 );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'po_posic_' . $id ) ) wp_die( 'Sem permissão.' );
	$alvos = po_posic_alvos( $id ); $c = $alvos ? $alvos[0] : null;
	if ( ! $c || strlen( $c['whats'] ) < 10 ) wp_die( 'WhatsApp inválido. Coloque o número com DDD (ex.: 31 99999-0000) e clique de novo.' );
	$n = $c['whats']; if ( ! ( 0 === strpos( $n, '55' ) && strlen( $n ) >= 12 ) ) $n = '55' . $n;
	po_posic_iniciar_prazo( $id );
	po_posic_marcar_envio( $id, 'whats', $c );
	wp_redirect( 'https://wa.me/' . $n . '?text=' . rawurlencode( po_posic_texto_whats( $id, $c ) ) ); exit;
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
