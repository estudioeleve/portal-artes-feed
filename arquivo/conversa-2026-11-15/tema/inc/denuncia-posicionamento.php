<?php
/**
 * Pedido de posicionamento (Israel, 17/11/2026): antes de publicar a denúncia, o Portal manda para
 * quem responde (diretor, secretário, prefeito, assessoria...), por e-mail e/ou WhatsApp, o resumo e a
 * foto (ou o print do vídeo), com o prazo e um link exclusivo para responder.
 * Os contatos ficam numa AGENDA (salva uma vez só); na denúncia é só buscar o nome e clicar.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- agenda de contatos ---------- */
function po_agenda() {
	$a = get_option( 'po_agenda', null );
	if ( ! is_array( $a ) ) { // primeira vez: aproveita a lista antiga em texto, se houver
		$a = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) get_option( 'po_orgaos', '' ) ) as $l ) {
			$p = array_map( 'trim', explode( '|', $l ) );
			if ( '' === $p[0] ) continue;
			$c = po_agenda_limpar( array( 'inst' => $p[0], 'nome' => count( $p ) > 3 ? $p[1] : '', 'email' => count( $p ) > 3 ? $p[2] : ( $p[1] ?? '' ), 'whats' => count( $p ) > 3 ? $p[3] : ( $p[2] ?? '' ) ) );
			$a[ $c['id'] ] = $c;
		}
		update_option( 'po_agenda', $a, false );
	}
	uasort( $a, function ( $x, $y ) { return strcasecmp( $x['nome'] . $x['inst'], $y['nome'] . $y['inst'] ); } );
	return $a;
}
function po_agenda_limpar( $c ) {
	return array(
		'id'    => ! empty( $c['id'] ) ? preg_replace( '/[^a-z0-9]/', '', (string) $c['id'] ) : strtolower( wp_generate_password( 10, false ) ),
		'nome'  => sanitize_text_field( $c['nome'] ?? '' ),
		'cargo' => sanitize_text_field( $c['cargo'] ?? '' ),
		'inst'  => sanitize_text_field( $c['inst'] ?? '' ),
		'email' => sanitize_email( $c['email'] ?? '' ),
		'whats' => preg_replace( '/\D/', '', (string) ( $c['whats'] ?? '' ) ),
	);
}
function po_agenda_salvar_contato( $c ) {
	$a = po_agenda(); $c = po_agenda_limpar( $c );
	if ( ! $c['nome'] && ! $c['inst'] ) return null;
	$a[ $c['id'] ] = $c; update_option( 'po_agenda', $a, false );
	return $c;
}
function po_agenda_rotulo( $c ) { return trim( $c['nome'] . ( $c['cargo'] ? ', ' . $c['cargo'] : '' ) . ( $c['inst'] ? ' · ' . $c['inst'] : '' ), ' ,·' ); }

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=po_denuncia', 'Agenda de contatos', 'Agenda de contatos', 'edit_posts', 'po-orgaos', 'po_agenda_pagina' );
} );
function po_agenda_pagina() {
	if ( isset( $_POST['po_agenda_nonce'] ) && wp_verify_nonce( $_POST['po_agenda_nonce'], 'po_agenda' ) ) {
		po_agenda_salvar_contato( wp_unslash( $_POST['c'] ?? array() ) );
		echo '<div class="updated"><p>Contato salvo.</p></div>';
	}
	if ( isset( $_GET['excluir'], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'po_agenda_del' ) ) {
		$a = po_agenda(); unset( $a[ preg_replace( '/[^a-z0-9]/', '', $_GET['excluir'] ) ] ); update_option( 'po_agenda', $a, false );
		echo '<div class="updated"><p>Contato excluído.</p></div>';
	}
	$a = po_agenda(); $url = admin_url( 'edit.php?post_type=po_denuncia&page=po-orgaos' );
	?>
	<div class="wrap">
		<h1>Agenda de contatos <span style="font-size:14px;color:#646970">(<?php echo count( $a ); ?>)</span></h1>
		<p>Salve aqui uma vez só: diretor, secretário, prefeito, assessoria... Na denúncia, é só digitar o nome e escolher. Também dá para cadastrar direto na denúncia.</p>
		<form method="post" id="po-ag-form" style="background:#fff;border:1px solid #dcdcde;padding:12px 16px;border-radius:6px;max-width:900px">
			<?php wp_nonce_field( 'po_agenda', 'po_agenda_nonce' ); ?>
			<input type="hidden" name="c[id]" id="ag-id">
			<h2 id="ag-tit" style="margin-top:4px">Novo contato</h2>
			<p><input name="c[nome]" id="ag-nome" placeholder="Nome (ex.: Antônio)" style="width:32%"> <input name="c[cargo]" id="ag-cargo" placeholder="Cargo (ex.: diretor)" style="width:30%"> <input name="c[inst]" id="ag-inst" placeholder="Instituição (ex.: Hospital Madalena Parrillo Calixto)" style="width:36%"></p>
			<p><input name="c[email]" id="ag-email" type="email" placeholder="E-mail" style="width:49%"> <input name="c[whats]" id="ag-whats" placeholder="WhatsApp com DDD" style="width:49%"></p>
			<?php submit_button( 'Salvar contato', 'primary', 'submit', false ); ?>
		</form>
		<p style="margin-top:16px"><input type="search" id="ag-busca" placeholder="Buscar na agenda..." style="width:320px"></p>
		<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Nome</th><th>Cargo</th><th>Instituição</th><th>E-mail</th><th>WhatsApp</th><th></th></tr></thead><tbody>
		<?php foreach ( $a as $c ) : ?>
			<tr data-c="<?php echo esc_attr( wp_json_encode( $c ) ); ?>"><td><b><?php echo esc_html( $c['nome'] ); ?></b></td><td><?php echo esc_html( $c['cargo'] ); ?></td><td><?php echo esc_html( $c['inst'] ); ?></td><td><?php echo esc_html( $c['email'] ); ?></td><td><?php echo esc_html( $c['whats'] ); ?></td>
			<td><a href="#" class="ag-ed">Editar</a> · <a href="<?php echo esc_url( add_query_arg( array( 'excluir' => $c['id'], '_wpnonce' => wp_create_nonce( 'po_agenda_del' ) ), $url ) ); ?>" onclick="return confirm('Excluir este contato?')" style="color:#b32d2e">Excluir</a></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<script>
	document.querySelectorAll('.ag-ed').forEach(function(l){l.addEventListener('click',function(e){e.preventDefault();var c=JSON.parse(l.closest('tr').dataset.c);
	['id','nome','cargo','inst','email','whats'].forEach(function(k){document.getElementById('ag-'+k).value=c[k]||'';});document.getElementById('ag-tit').textContent='Editar contato';window.scrollTo(0,0);});});
	document.getElementById('ag-busca').addEventListener('input',function(){var q=this.value.toLowerCase();document.querySelectorAll('tbody tr').forEach(function(r){r.style.display=r.textContent.toLowerCase().indexOf(q)>-1?'':'none';});});
	</script>
	<?php
}
/* cadastrar contato direto da denúncia */
add_action( 'wp_ajax_po_agenda_add', function () {
	check_ajax_referer( 'po_agenda', 'n' );
	if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error();
	$c = po_agenda_salvar_contato( wp_unslash( $_POST['c'] ?? array() ) );
	$c ? wp_send_json_success( $c + array( 'rotulo' => po_agenda_rotulo( $c ) ) ) : wp_send_json_error();
} );

/* ---------- dados do pedido ---------- */
function po_posic_token( $id ) {
	$t = get_post_meta( $id, 'dc_posic_token', true );
	if ( ! $t ) { $t = wp_generate_password( 24, false ); update_post_meta( $id, 'dc_posic_token', $t ); }
	return $t;
}
function po_posic_link( $id ) { return add_query_arg( 'po_posic', po_posic_token( $id ), home_url( '/' ) ); }
function po_posic_orgao( $id ) { return array( 'nome' => (string) get_post_meta( $id, 'dc_posic_orgao', true ) ); }
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
function po_posic_iniciar( $id, $c, $horas ) {
	if ( in_array( (int) $horas, array( 2, 24, 48 ), true ) ) update_post_meta( $id, 'dc_posic_horas', (int) $horas );
	if ( ! get_post_meta( $id, 'dc_posic_prazo', true ) ) {
		$h = (int) get_post_meta( $id, 'dc_posic_horas', true ); if ( ! $h ) $h = 24;
		update_post_meta( $id, 'dc_posic_prazo', time() + $h * HOUR_IN_SECONDS );
	}
	if ( ! get_post_meta( $id, 'dc_posic_orgao', true ) ) update_post_meta( $id, 'dc_posic_orgao', $c['inst'] ? $c['inst'] : trim( $c['nome'] . ( $c['cargo'] ? ', ' . $c['cargo'] : '' ) ) );
	$ids = array_filter( (array) get_post_meta( $id, 'dc_posic_ids', true ) );
	if ( ! in_array( $c['id'], $ids, true ) ) { $ids[] = $c['id']; update_post_meta( $id, 'dc_posic_ids', array_values( $ids ) ); }
}
function po_posic_marcar_envio( $id, $canal, $c ) {
	$log = array_filter( (array) get_post_meta( $id, 'dc_posic_log', true ) );
	$log[] = array( 't' => time(), 'canal' => $canal, 'para' => 'email' === $canal ? $c['email'] : $c['whats'], 'quem' => po_agenda_rotulo( $c ), 'orgao' => '' );
	update_post_meta( $id, 'dc_posic_log', array_values( $log ) );
}
function po_posic_saudacao( $c ) { return $c['nome'] ? preg_replace( '/,.*$/', '', $c['nome'] ) : ''; }
function po_posic_url_envio( $post_id, $canal, $ids ) {
	return add_query_arg( array( 'action' => 'po_posic_' . $canal, 'post' => $post_id, 'ids' => implode( ',', (array) $ids ), '_wpnonce' => wp_create_nonce( 'po_posic_' . $post_id ) ), admin_url( 'admin-post.php' ) );
}

/* ---------- texto do WhatsApp ---------- */
function po_posic_texto_whats( $id, $c ) {
	list( $tit, $rel ) = po_posic_resumo( $id );
	list( $img ) = po_posic_imagem( $id );
	$prot = get_post_meta( $id, 'dc_protocolo', true );
	$local = get_post_meta( $id, 'dc_local', true );
	$s  = ( po_posic_saudacao( $c ) ? 'Olá, ' . po_posic_saudacao( $c ) . '! ' : 'Olá! ' ) . "Aqui é da redação do *Portal ONE News*.\n\n";
	$s .= 'Recebemos de um leitor o relato abaixo' . ( $c['inst'] ? ', envolvendo ' . $c['inst'] : '' ) . ( $local ? " ({$local})" : '' ) . ', protocolo ' . $prot . ".\n\n";
	if ( $tit ) $s .= '*' . $tit . "*\n";
	$s .= '"' . $rel . "\"\n\n";
	if ( $img ) $s .= "Imagem enviada pelo leitor: {$img}\n\n";
	$s .= 'A matéria está prevista para ir ao ar em ' . po_posic_quando( $id ) . ". Caso queira se manifestar, é só responder por aqui ou pelo link:\n" . po_posic_link( $id ) . "\n\nObrigado!";
	return $s;
}

/* ---------- caixa no painel da denúncia ---------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'dc_posic', 'Pedir posicionamento', 'po_posic_metabox', 'po_denuncia', 'normal', 'default' );
} );
function po_posic_metabox( $post ) {
	wp_nonce_field( 'dc_posic', 'dc_posic_nonce' );
	$ag = po_agenda();
	$h = (int) get_post_meta( $post->ID, 'dc_posic_horas', true ); if ( ! $h ) $h = 24;
	$prazo = (int) get_post_meta( $post->ID, 'dc_posic_prazo', true );
	$log = array_filter( (array) get_post_meta( $post->ID, 'dc_posic_log', true ) );
	$resp = get_post_meta( $post->ID, 'dc_posic_resposta', true );
	$ids = array_values( array_filter( (array) get_post_meta( $post->ID, 'dc_posic_ids', true ), function ( $i ) use ( $ag ) { return isset( $ag[ $i ] ); } ) );
	$lista = array();
	foreach ( $ag as $c ) $lista[] = $c + array( 'rotulo' => po_agenda_rotulo( $c ) );
	$base = array( 'email' => po_posic_url_envio( $post->ID, 'email', array( 'IDS' ) ), 'whats' => po_posic_url_envio( $post->ID, 'whats', array( 'IDS' ) ) );
	if ( isset( $_GET['po_posic_ok'] ) ) echo '<div class="notice notice-success inline"><p>' . esc_html( wp_unslash( $_GET['po_posic_ok'] ) ) . '</p></div>';
	?>
	<style>
	#dc_posic .pp-busca{position:relative}#dc_posic .pp-sug{position:absolute;left:0;right:0;top:100%;background:#fff;border:1px solid #c3c4c7;border-radius:6px;max-height:260px;overflow:auto;z-index:20;box-shadow:0 6px 18px rgba(0,0,0,.12)}
	#dc_posic .pp-sug div{padding:8px 10px;cursor:pointer;border-bottom:1px solid #f0f0f1}#dc_posic .pp-sug div:hover{background:#f0f6fc}
	#dc_posic .pp-dest{border:1px solid #dcdcde;border-radius:6px;padding:8px 10px;margin:6px 0;display:flex;gap:8px;align-items:center;flex-wrap:wrap}#dc_posic .pp-dest b{flex:1;min-width:200px}
	#dc_posic .pp-novo{background:#f6f7f7;border-radius:6px;padding:10px;margin-top:8px}#dc_posic .pp-novo input{margin:2px 0}
	</style>
	<p style="margin-top:0">Manda o resumo e a foto (ou o print do vídeo), com o prazo e um link para a pessoa responder. O nome e o telefone do leitor <b>não</b> vão.</p>
	<p style="margin-bottom:4px"><b>Prazo para responder:</b> <select id="pp-horas" name="dc_posic_horas">
		<?php foreach ( array( 2 => '2 horas (urgente)', 24 => '24 horas', 48 => '48 horas' ) as $k => $v ) : ?><option value="<?php echo (int) $k; ?>" <?php selected( $h, $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?>
	</select> <span style="color:#646970">conta a partir do primeiro envio<?php echo $prazo ? ' · já definido: ' . esc_html( po_posic_quando( $post->ID ) ) : ''; ?></span></p>
	<p style="margin:12px 0 4px"><b>Para quem enviar</b> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=po_denuncia&page=po-orgaos' ) ); ?>" style="font-size:12px" target="_blank">(abrir a agenda)</a></p>
	<div class="pp-busca"><input type="search" id="pp-q" placeholder="Digite o nome, o cargo ou a instituição (ex.: Antônio, diretor, hospital)" style="width:100%;padding:6px 10px" autocomplete="off"><div class="pp-sug" id="pp-sug" hidden></div></div>
	<div id="pp-lista"></div>
	<div class="pp-novo" id="pp-novo" hidden>
		<b>Novo contato</b> (fica salvo na agenda)<br>
		<input id="pn-nome" placeholder="Nome" style="width:32%"> <input id="pn-cargo" placeholder="Cargo" style="width:30%"> <input id="pn-inst" placeholder="Instituição" style="width:36%"><br>
		<input id="pn-email" type="email" placeholder="E-mail" style="width:49%"> <input id="pn-whats" placeholder="WhatsApp com DDD" style="width:49%"><br>
		<button type="button" class="button button-primary" id="pn-ok">Salvar na agenda e adicionar</button> <button type="button" class="button" id="pn-cancela">Cancelar</button>
	</div>
	<p><button type="button" class="button" id="pp-abrenovo">+ Novo contato</button> <a class="button button-primary" id="pp-todos" href="#" hidden>Enviar e-mail para todos da lista</a></p>
	<p style="color:#646970;font-size:12px">O WhatsApp abre numa aba nova, no seu WhatsApp, com a mensagem pronta (saudação com o nome, resumo, link da imagem, prazo e link para responder): é só conferir e apertar enviar. O e-mail sai pelo site, com a imagem anexada.</p>
	<?php if ( $log ) : ?>
		<hr><p style="margin-bottom:4px"><b>Histórico</b></p><ul style="margin-top:0">
		<?php foreach ( $log as $l ) echo '<li>' . esc_html( wp_date( 'd/m H\hi', $l['t'] ) . ' · ' . ( 'email' === $l['canal'] ? 'e-mail' : 'WhatsApp' ) . ' para ' . ( ! empty( $l['quem'] ) ? $l['quem'] . ' (' . $l['para'] . ')' : $l['para'] ) ) . '</li>'; ?>
		</ul>
		<p><b>Prazo:</b> <?php echo esc_html( po_posic_quando( $post->ID ) ); echo $prazo && time() > $prazo ? ' <span style="color:#b32d2e">(vencido)</span>' : ' <span style="color:#646970">(se publicar antes, a matéria sai sem a resposta)</span>'; ?></p>
	<?php endif; ?>
	<?php if ( $resp ) : ?>
		<hr><p style="margin-bottom:4px"><b>Resposta recebida</b> em <?php echo esc_html( wp_date( 'd/m H\hi', (int) get_post_meta( $post->ID, 'dc_posic_resp_em', true ) ) ); ?> · <?php echo esc_html( trim( get_post_meta( $post->ID, 'dc_posic_resp_nome', true ) . ', ' . get_post_meta( $post->ID, 'dc_posic_resp_cargo', true ), ', ' ) ); ?></p>
		<blockquote style="background:#f6f7f7;padding:8px 12px;margin:0"><?php echo nl2br( esc_html( $resp ) ); ?></blockquote>
		<p><label>Instituição que vai na matéria: <input type="text" name="dc_posic_orgao" value="<?php echo esc_attr( get_post_meta( $post->ID, 'dc_posic_orgao', true ) ); ?>" style="width:60%"></label></p>
		<p><label><input type="checkbox" name="dc_posic_usar" value="1" <?php checked( get_post_meta( $post->ID, 'dc_posic_usar', true ) ); ?>> <b>Incluir a resposta na matéria</b> (e na legenda do Instagram)</label></p>
		<p><textarea name="dc_posic_resposta_ed" rows="4" style="width:100%" placeholder="Pode editar o texto que vai na matéria"><?php echo esc_textarea( get_post_meta( $post->ID, 'dc_posic_resposta_ed', true ) ?: $resp ); ?></textarea></p>
	<?php endif; ?>
	<script>
	(function(){
	var AG=<?php echo wp_json_encode( $lista ); ?>, SEL=<?php echo wp_json_encode( $ids ); ?>, URL=<?php echo wp_json_encode( $base ); ?>, NONCE=<?php echo wp_json_encode( wp_create_nonce( 'po_agenda' ) ); ?>;
	var q=document.getElementById('pp-q'), sug=document.getElementById('pp-sug'), lista=document.getElementById('pp-lista'), horas=document.getElementById('pp-horas');
	function norm(s){return (s||'').normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase();}
	function achar(id){for(var i=0;i<AG.length;i++)if(AG[i].id===id)return AG[i];return null;}
	function url(tipo,ids){return URL[tipo].replace('IDS',encodeURIComponent(ids.join(',')))+'&horas='+horas.value;}
	function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
	function desenhar(){
		lista.innerHTML='';
		SEL.forEach(function(id){var c=achar(id);if(!c)return;var d=document.createElement('div');d.className='pp-dest';
			d.innerHTML='<input type="hidden" name="dc_posic_ids[]" value="'+c.id+'"><b>'+esc(c.rotulo)+'</b>'+
			(c.email?'<a class="button" href="'+url('email',[c.id])+'">E-mail</a>':'<span style="color:#a7aaad">sem e-mail</span>')+
			(c.whats?' <a class="button" target="_blank" rel="noopener" style="background:#25d366;border-color:#1da851;color:#fff" href="'+url('whats',[c.id])+'">WhatsApp</a>':' <span style="color:#a7aaad">sem WhatsApp</span>')+
			' <a href="#" data-x="'+c.id+'" style="color:#b32d2e;text-decoration:none" title="Tirar da lista">✕</a>';
			lista.appendChild(d);});
		var em=SEL.filter(function(id){var c=achar(id);return c&&c.email;});var t=document.getElementById('pp-todos');
		t.hidden=em.length<2;t.href=url('email',em);
	}
	lista.addEventListener('click',function(e){var x=e.target.getAttribute('data-x');if(x){e.preventDefault();SEL=SEL.filter(function(i){return i!==x;});desenhar();}});
	horas.addEventListener('change',desenhar);
	q.addEventListener('input',function(){var t=norm(q.value).trim();sug.innerHTML='';
		if(!t){sug.hidden=true;return;}
		var r=AG.filter(function(c){return norm(c.rotulo).indexOf(t)>-1;}).slice(0,12);
		r.forEach(function(c){var d=document.createElement('div');d.innerHTML='<b>'+esc(c.nome||c.inst)+'</b> <span style="color:#646970">'+esc((c.cargo?c.cargo:'')+(c.inst&&c.nome?' · '+c.inst:''))+'</span>';
			d.addEventListener('mousedown',function(e){e.preventDefault();if(SEL.indexOf(c.id)<0)SEL.push(c.id);q.value='';sug.hidden=true;desenhar();});sug.appendChild(d);});
		var n=document.createElement('div');n.innerHTML='<b>+ Cadastrar "'+esc(q.value)+'" na agenda</b>';n.addEventListener('mousedown',function(e){e.preventDefault();abrirNovo(q.value);});sug.appendChild(n);
		sug.hidden=false;});
	q.addEventListener('blur',function(){setTimeout(function(){sug.hidden=true;},150);});
	q.addEventListener('keydown',function(e){if(e.key==='Enter')e.preventDefault();});
	function abrirNovo(nome){document.getElementById('pp-novo').hidden=false;document.getElementById('pn-nome').value=nome||'';sug.hidden=true;q.value='';document.getElementById('pn-cargo').focus();}
	document.getElementById('pp-abrenovo').addEventListener('click',function(){abrirNovo('');document.getElementById('pn-nome').focus();});
	document.getElementById('pn-cancela').addEventListener('click',function(){document.getElementById('pp-novo').hidden=true;});
	document.getElementById('pn-ok').addEventListener('click',function(){
		var f=new FormData();f.append('action','po_agenda_add');f.append('n',NONCE);
		['nome','cargo','inst','email','whats'].forEach(function(k){f.append('c['+k+']',document.getElementById('pn-'+k).value);});
		fetch(ajaxurl,{method:'POST',credentials:'same-origin',body:f}).then(function(r){return r.json();}).then(function(j){
			if(!j.success){alert('Preencha pelo menos o nome ou a instituição.');return;}
			AG.push(j.data);SEL.push(j.data.id);document.getElementById('pp-novo').hidden=true;
			['nome','cargo','inst','email','whats'].forEach(function(k){document.getElementById('pn-'+k).value='';});desenhar();}).catch(function(){alert('Não consegui salvar. Tente de novo.');});
	});
	desenhar();
	})();
	</script>
	<?php
}
/* salva antes do salvamento da denúncia (que atualiza a matéria) */
add_action( 'save_post_po_denuncia', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) return;
	if ( ! isset( $_POST['dc_posic_nonce'] ) || ! wp_verify_nonce( $_POST['dc_posic_nonce'], 'dc_posic' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	$ids = array_values( array_unique( array_map( function ( $i ) { return preg_replace( '/[^a-z0-9]/', '', (string) $i ); }, (array) ( $_POST['dc_posic_ids'] ?? array() ) ) ) );
	update_post_meta( $post_id, 'dc_posic_ids', $ids );
	$h = absint( $_POST['dc_posic_horas'] ?? 24 ); if ( ! in_array( $h, array( 2, 24, 48 ), true ) ) $h = 24;
	update_post_meta( $post_id, 'dc_posic_horas', $h );
	if ( isset( $_POST['dc_posic_orgao'] ) ) update_post_meta( $post_id, 'dc_posic_orgao', sanitize_text_field( wp_unslash( $_POST['dc_posic_orgao'] ) ) );
	if ( get_post_meta( $post_id, 'dc_posic_resposta', true ) ) {
		$antes = get_post_meta( $post_id, 'dc_posic_usar', true ) . '|' . get_post_meta( $post_id, 'dc_posic_resposta_ed', true ) . '|' . get_post_meta( $post_id, 'dc_posic_orgao', true );
		update_post_meta( $post_id, 'dc_posic_usar', empty( $_POST['dc_posic_usar'] ) ? '' : '1' );
		if ( isset( $_POST['dc_posic_resposta_ed'] ) ) update_post_meta( $post_id, 'dc_posic_resposta_ed', sanitize_textarea_field( wp_unslash( $_POST['dc_posic_resposta_ed'] ) ) );
		$depois = get_post_meta( $post_id, 'dc_posic_usar', true ) . '|' . get_post_meta( $post_id, 'dc_posic_resposta_ed', true ) . '|' . get_post_meta( $post_id, 'dc_posic_orgao', true );
		if ( $antes !== $depois ) $GLOBALS['po_den_relato_mudou'] = true; // a matéria é refeita no salvamento da denúncia
	}
}, 5 );

/* ---------- envio ---------- */
function po_posic_volta( $id, $msg ) {
	wp_safe_redirect( add_query_arg( 'po_posic_ok', rawurlencode( $msg ), get_edit_post_link( $id, 'url' ) ) . '#dc_posic' ); exit;
}
function po_posic_pedido() {
	$id = absint( $_GET['post'] ?? 0 );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'po_posic_' . $id ) ) wp_die( 'Link vencido. Volte na denúncia, atualize a página e clique de novo.' );
	$ag = po_agenda(); $out = array();
	foreach ( explode( ',', (string) ( $_GET['ids'] ?? '' ) ) as $i ) { $i = preg_replace( '/[^a-z0-9]/', '', $i ); if ( isset( $ag[ $i ] ) ) $out[] = $ag[ $i ]; }
	return array( $id, $out, absint( $_GET['horas'] ?? 0 ) );
}
function po_posic_email_html( $id, $c ) {
	list( $tit, $rel ) = po_posic_resumo( $id );
	list( $img ) = po_posic_imagem( $id );
	$prot = get_post_meta( $id, 'dc_protocolo', true ); $local = get_post_meta( $id, 'dc_local', true );
	$s  = '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#1d2327;max-width:620px">';
	$s .= '<p>' . ( po_posic_saudacao( $c ) ? 'Prezado(a) ' . esc_html( po_posic_saudacao( $c ) ) : 'Prezados' ) . ',</p>';
	$s .= '<p>A redação do <b>Portal ONE News</b> recebeu de um leitor o relato abaixo' . ( $c['inst'] ? ', envolvendo <b>' . esc_html( $c['inst'] ) . '</b>' : '' ) . ( $local ? ' (' . esc_html( $local ) . ')' : '' ) . ', protocolo ' . esc_html( $prot ) . '.</p>';
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
	list( $id, $alvos, $horas ) = po_posic_pedido();
	$alvos = array_values( array_filter( $alvos, function ( $c ) { return is_email( $c['email'] ); } ) );
	if ( ! $alvos ) po_posic_volta( $id, 'Esse contato não tem e-mail válido na agenda.' );
	po_posic_iniciar( $id, $alvos[0], $horas );
	list( , $arq ) = po_posic_imagem( $id );
	$prot = get_post_meta( $id, 'dc_protocolo', true );
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: Portal ONE News <' . get_option( 'admin_email' ) . '>', 'Reply-To: Redação Portal ONE News <' . get_option( 'po_denuncia_email', get_option( 'admin_email' ) ) . '>' );
	$ok = array(); $erro = array();
	foreach ( $alvos as $c ) {
		po_posic_iniciar( $id, $c, $horas );
		if ( wp_mail( $c['email'], 'Pedido de posicionamento · Portal ONE News · Protocolo ' . $prot, po_posic_email_html( $id, $c ), $headers, ( $arq && file_exists( $arq ) ) ? array( $arq ) : array() ) ) { po_posic_marcar_envio( $id, 'email', $c ); $ok[] = po_agenda_rotulo( $c ); }
		else $erro[] = po_agenda_rotulo( $c );
	}
	po_posic_volta( $id, ( $ok ? 'E-mail enviado para: ' . implode( '; ', $ok ) . '. Prazo: ' . po_posic_quando( $id ) . '.' : '' ) . ( $erro ? ' O site não conseguiu enviar para: ' . implode( '; ', $erro ) . '.' : '' ) );
} );
add_action( 'admin_post_po_posic_whats', function () {
	list( $id, $alvos, $horas ) = po_posic_pedido();
	$c = $alvos ? $alvos[0] : null;
	if ( ! $c || strlen( $c['whats'] ) < 10 ) wp_die( 'Esse contato não tem WhatsApp válido na agenda (precisa do DDD).' );
	$n = $c['whats']; if ( ! ( 0 === strpos( $n, '55' ) && strlen( $n ) >= 12 ) ) $n = '55' . $n;
	po_posic_iniciar( $id, $c, $horas );
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
