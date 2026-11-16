<?php
/**
 * Tema Portal ONE News — Studio Eleve.
 * Tudo em português. Áreas: destaque, Santa Luzia, canal exclusivo, colunistas,
 * Grande BH e Minas, vídeos, vitrine de anunciantes, editorias, mais lidas, anuncie.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- configuração básica ---------- */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_image_size( 'po-card', 800, 500, true );
	add_image_size( 'po-mini', 240, 240, true );
	register_nav_menus( array( 'principal' => 'Menu de editorias (topo)', 'rodape' => 'Menu do rodapé' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'po-fontes', 'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'po-tema', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'po-tema', get_template_directory_uri() . '/assets/tema.js', array(), wp_get_theme()->get( 'Version' ), true );

	if ( is_category( array( 'esporte', 'atletico', 'cruzeiro', 'america' ) ) ) {
		wp_enqueue_script( 'po-placar-vivo', get_template_directory_uri() . '/assets/placar-vivo.js', array(), wp_get_theme()->get( 'Version' ), true );
		wp_localize_script( 'po-placar-vivo', 'poPlacar', array( 'ajax' => admin_url( 'admin-ajax.php' ) ) );
	}

	if ( is_front_page() ) {
		wp_enqueue_script( 'po-placar-home', get_template_directory_uri() . '/assets/placar-home.js', array(), wp_get_theme()->get( 'Version' ), true );
		$cats = array();
		foreach ( array( 'atletico', 'cruzeiro', 'america' ) as $slug ) {
			$c = get_category_by_slug( $slug );
			if ( $c ) $cats[ $slug ] = get_category_link( $c );
		}
		wp_localize_script( 'po-placar-home', 'poPlacar', array(
			'ajax'       => admin_url( 'admin-ajax.php' ),
			'categorias' => $cats,
		) );
	}
} );

/* ---------- conteúdos extras ---------- */
add_action( 'init', function () {
	register_post_type( 'po_video', array(
		'label' => 'Vídeos', 'public' => true, 'menu_icon' => 'dashicons-video-alt3', 'has_archive' => 'videos',
		'rewrite' => array( 'slug' => 'video' ), 'supports' => array( 'title', 'thumbnail', 'editor' ), 'show_in_rest' => true,
		'labels' => array( 'name' => 'Vídeos', 'singular_name' => 'Vídeo', 'add_new_item' => 'Adicionar vídeo' ),
	) );
	register_post_type( 'po_anuncio', array(
		'label' => 'Anúncios', 'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-megaphone',
		'supports' => array( 'title', 'thumbnail' ), 'show_in_rest' => true,
		'labels' => array( 'name' => 'Anúncios', 'singular_name' => 'Anúncio', 'add_new_item' => 'Adicionar anúncio' ),
	) );
	register_post_type( 'po_inscrito', array(
		'label' => 'Cadastros', 'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-groups',
		'supports' => array( 'title' ), 'capabilities' => array( 'create_posts' => 'do_not_allow' ), 'map_meta_cap' => true,
		'labels' => array( 'name' => 'Cadastros (canal e anúncios)', 'singular_name' => 'Cadastro' ),
	) );
	foreach ( array( 'po_resumos', 'po_linha_fina', 'po_credito_foto', 'po_fonte' ) as $k ) {
		register_post_meta( 'post', $k, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
	}
	register_post_meta( 'po_video', 'po_url', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
	foreach ( array( 'po_posicao', 'po_link', 'po_preco', 'po_texto', 'po_colunista' ) as $k ) {
		register_post_meta( 'po_anuncio', $k, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
	}
} );

/* ---------- caixas de campos no painel ---------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'po_post', 'Portal ONE News: destaque', 'po_box_post', 'post', 'normal', 'high' );
	add_meta_box( 'po_video', 'Link do vídeo', 'po_box_video', 'po_video', 'normal', 'high' );
	add_meta_box( 'po_anuncio', 'Dados do anúncio', 'po_box_anuncio', 'po_anuncio', 'normal', 'high' );
	add_meta_box( 'po_inscrito', 'Dados do cadastro', 'po_box_inscrito', 'po_inscrito', 'normal', 'high' );
} );
function po_campo( $id, $rot, $val, $tipo = 'text', $ajuda = '' ) {
	echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $rot ) . '</strong></label><br>';
	if ( 'textarea' === $tipo ) echo '<textarea style="width:100%" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">' . esc_textarea( $val ) . '</textarea>';
	else echo '<input style="width:100%" type="' . esc_attr( $tipo ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '">';
	if ( $ajuda ) echo '<br><small>' . esc_html( $ajuda ) . '</small>';
	echo '</p>';
}
function po_box_post( $p ) {
	wp_nonce_field( 'po_salvar', 'po_nonce' );
	po_campo( 'po_linha_fina', 'Linha fina (resumo abaixo do título)', get_post_meta( $p->ID, 'po_linha_fina', true ) );
	po_campo( 'po_resumos', 'Resumos do destaque (um por linha)', get_post_meta( $p->ID, 'po_resumos', true ), 'textarea', 'Aparecem como tópicos embaixo da manchete quando a notícia for o destaque principal.' );
	po_campo( 'po_credito_foto', 'Crédito da foto', get_post_meta( $p->ID, 'po_credito_foto', true ) );
	po_campo( 'po_fonte', 'Fonte', get_post_meta( $p->ID, 'po_fonte', true ) );
	echo '<p><small>Para virar o destaque principal da página inicial, marque "Fixar no topo do blog" no painel do lado.</small></p>';
}
function po_box_video( $p ) {
	wp_nonce_field( 'po_salvar', 'po_nonce' );
	po_campo( 'po_url', 'Link do YouTube ou do Instagram', get_post_meta( $p->ID, 'po_url', true ), 'url', 'Cole o link do vídeo. Ele toca dentro do site.' );
}
function po_box_anuncio( $p ) {
	wp_nonce_field( 'po_salvar', 'po_nonce' );
	$pos = get_post_meta( $p->ID, 'po_posicao', true );
	echo '<p><label for="po_posicao"><strong>Onde aparece</strong></label><br><select id="po_posicao" name="po_posicao">';
	foreach ( array( 'topo' => 'Banner grande 1 – no alto, logo na entrada (970 × 90)', 'meio' => 'Banner grande 2 – meio da página inicial (970 × 90)', 'lateral' => 'Banner quadrado 1 (300 × 250)', 'lateral2' => 'Banner quadrado 2 (300 × 250)', 'vitrine' => 'Vitrine de produto', 'videos' => 'Patrocínio da seção de vídeos', 'coluna' => 'Patrocinador de uma coluna (escolha o colunista abaixo)', 'portal-luziense' => 'Patrocinador do Portal Luziense', 'portal-comercio' => 'Patrocinador do Portal Comércio', 'portal-politica' => 'Patrocinador do Portal Política' ) as $v => $r ) {
		echo '<option value="' . esc_attr( $v ) . '"' . selected( $pos, $v, false ) . '>' . esc_html( $r ) . '</option>';
	}
	echo '</select></p>';
	$col = get_post_meta( $p->ID, 'po_colunista', true );
	echo '<p><label for="po_colunista"><strong>Colunista patrocinado (só para "Patrocinador de uma coluna")</strong></label><br><select id="po_colunista" name="po_colunista"><option value="">—</option>';
	foreach ( po_lista_colunistas( 100 ) as $u ) echo '<option value="' . esc_attr( $u->ID ) . '"' . selected( $col, (string) $u->ID, false ) . '>' . esc_html( $u->display_name . ' · ' . get_user_meta( $u->ID, 'po_coluna', true ) ) . '</option>';
	echo '</select></p>';
	po_campo( 'po_link', 'Link (site ou WhatsApp do anunciante)', get_post_meta( $p->ID, 'po_link', true ), 'url', 'WhatsApp: https://wa.me/5531999999999' );
	po_campo( 'po_inicio', 'Começa a aparecer em', get_post_meta( $p->ID, 'po_inicio', true ), 'date', 'Deixe vazio para começar já.' );
	po_campo( 'po_fim', 'Sai do ar depois de', get_post_meta( $p->ID, 'po_fim', true ), 'date', 'Deixe vazio para ficar no ar até você tirar.' );
	po_campo( 'po_preco', 'Preço (só para vitrine)', get_post_meta( $p->ID, 'po_preco', true ) );
	po_campo( 'po_texto', 'Texto curto (só para vitrine)', get_post_meta( $p->ID, 'po_texto', true ) );
	echo '<p><small>Use a "Imagem destacada" (no lado direito) para a arte do banner, no tamanho certo: 970 × 90 para os banners grandes e 300 × 250 para os quadrados. Se houver mais de um anúncio no mesmo espaço, eles se revezam a cada visita.</small></p>';
}
function po_box_inscrito( $p ) {
	foreach ( array( 'po_tipo' => 'Tipo', 'po_nome' => 'Nome', 'po_whats' => 'WhatsApp', 'po_bairro' => 'Cidade ou bairro', 'po_empresa' => 'Empresa', 'po_formato' => 'Formato de anúncio' ) as $k => $r ) {
		$v = get_post_meta( $p->ID, $k, true );
		if ( $v ) echo '<p><strong>' . esc_html( $r ) . ':</strong> ' . esc_html( $v ) . '</p>';
	}
}
add_action( 'save_post', function ( $id ) {
	if ( ! isset( $_POST['po_nonce'] ) || ! wp_verify_nonce( $_POST['po_nonce'], 'po_salvar' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $id ) ) return;
	foreach ( array( 'po_linha_fina', 'po_credito_foto', 'po_fonte', 'po_preco', 'po_texto', 'po_posicao', 'po_colunista', 'po_inicio', 'po_fim' ) as $k ) {
		if ( isset( $_POST[ $k ] ) ) update_post_meta( $id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
	}
	if ( isset( $_POST['po_resumos'] ) ) update_post_meta( $id, 'po_resumos', sanitize_textarea_field( wp_unslash( $_POST['po_resumos'] ) ) );
	foreach ( array( 'po_url', 'po_link' ) as $k ) {
		if ( isset( $_POST[ $k ] ) ) update_post_meta( $id, $k, esc_url_raw( wp_unslash( $_POST[ $k ] ) ) );
	}
} );

/* ---------- colunistas: campos no perfil do usuário ---------- */
function po_campos_usuario( $u ) { ?>
	<h2>Colunista do Portal</h2>
	<table class="form-table">
		<tr><th><label for="po_coluna">Nome da coluna</label></th><td><input type="text" id="po_coluna" name="po_coluna" class="regular-text" value="<?php echo esc_attr( get_user_meta( $u->ID, 'po_coluna', true ) ); ?>"><p class="description">Preencha para a pessoa aparecer na seção Colunistas.</p></td></tr>
		<tr><th><label for="po_quem">Quem é</label></th><td><input type="text" id="po_quem" name="po_quem" class="regular-text" value="<?php echo esc_attr( get_user_meta( $u->ID, 'po_quem', true ) ); ?>"><p class="description">Uma linha curta. Ex.: "Advogado e professor de direito". O texto mais longo vai em "Informações biográficas", acima.</p></td></tr>
		<tr><th><label for="po_foto">Link da foto do colunista</label></th><td><input type="url" id="po_foto" name="po_foto" class="regular-text" value="<?php echo esc_attr( get_user_meta( $u->ID, 'po_foto', true ) ); ?>"><p class="description">Envie a foto na Biblioteca de Mídia e cole o link aqui.</p></td></tr>
	</table>
<?php }
add_action( 'show_user_profile', 'po_campos_usuario' );
add_action( 'edit_user_profile', 'po_campos_usuario' );
function po_salvar_usuario( $id ) {
	if ( ! current_user_can( 'edit_user', $id ) ) return;
	if ( isset( $_POST['po_coluna'] ) ) update_user_meta( $id, 'po_coluna', sanitize_text_field( wp_unslash( $_POST['po_coluna'] ) ) );
	if ( isset( $_POST['po_quem'] ) ) update_user_meta( $id, 'po_quem', sanitize_text_field( wp_unslash( $_POST['po_quem'] ) ) );
	if ( isset( $_POST['po_foto'] ) ) update_user_meta( $id, 'po_foto', esc_url_raw( wp_unslash( $_POST['po_foto'] ) ) );
}
add_action( 'personal_options_update', 'po_salvar_usuario' );
add_action( 'edit_user_profile_update', 'po_salvar_usuario' );

/* ---------- links do WhatsApp (canal e contato) ---------- */
define( 'PO_CANAL_PADRAO', 'https://whatsapp.com/channel/0029VbAl1JQLSmbejLg0uN0I' );
define( 'PO_ZAP_PADRAO', '5531992371702' );
function po_canal_link() { $l = get_theme_mod( 'po_canal_link' ); return $l ? $l : PO_CANAL_PADRAO; }
function po_zap_numero() { $n = preg_replace( '/\D/', '', (string) get_theme_mod( 'po_whats_noticia' ) ); return $n ? $n : PO_ZAP_PADRAO; }
function po_zap_contato() { return 'https://wa.me/' . po_zap_numero() . '?text=' . rawurlencode( 'Olá, Portal ONE News! Quero enviar uma denúncia / sugestão / reclamação: ' ); }
function po_icone_zap() { return '<svg class="zap-i" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.700 6.700 0 0 1-3.300-2.900c-.2-.4.2-.4.700-1.300.1-.2 0-.3 0-.5l-.8-1.800c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.200c0 1.300.9 2.600 1.100 2.800.1.200 1.900 2.900 4.600 4 1.700.7 2.400.8 3.200.7.500-.1 1.500-.6 1.700-1.200.2-.6.2-1.100.2-1.200-.1-.2-.3-.2-.5-.3z"/></svg>'; }

/* ---------- link da página de denúncia (Correspondente Cidadão) ---------- */
function po_denuncia_link() {
	$cache = get_transient( 'po_denuncia_url' );
	if ( $cache ) return $cache;
	$pags = get_posts( array(
		'post_type'      => 'page',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-denuncia.php',
	) );
	$url = $pags ? get_permalink( $pags[0] ) : home_url( '/denuncia/' );
	set_transient( 'po_denuncia_url', $url, HOUR_IN_SECONDS );
	return $url;
}

/* ---------- personalizar: links do WhatsApp ---------- */
add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'po_contato', array( 'title' => 'Portal ONE News: WhatsApp e textos', 'priority' => 30 ) );
	$campos = array(
		'po_canal_link' => array( 'Link do canal exclusivo no WhatsApp', PO_CANAL_PADRAO ),
		'po_whats_comercial' => array( 'WhatsApp comercial (só números, com DDD)', '' ),
		'po_whats_noticia' => array( 'WhatsApp para o leitor mandar denúncia, sugestão ou reclamação (só números, com DDD)', PO_ZAP_PADRAO ),
		'po_seguidores' => array( 'Número de seguidores no Instagram (texto)', '137 mil' ),
		'po_aviso' => array( 'Faixa de aviso no topo (deixe vazio para esconder)', '' ),
			'po_logo_comercio' => array( 'Trocar a logo do Portal Comércio (link de PNG branca; vazio = logo do tema)', '' ),
			'po_logo_politica' => array( 'Trocar a logo do Portal Política (link de PNG branca; vazio = logo do tema)', '' ),
			'po_youtube' => array( 'Link do canal no YouTube', '' ),
			'po_instagram' => array( 'Link do Instagram', 'https://www.instagram.com/portalonenews/' ),
	);
	foreach ( $campos as $id => $d ) {
		$c->add_setting( $id, array( 'default' => $d[1], 'sanitize_callback' => 'sanitize_text_field' ) );
		$c->add_control( $id, array( 'label' => $d[0], 'section' => 'po_contato', 'type' => 'text' ) );
	}
} );

/* ---------- formulários: canal exclusivo e anuncie ---------- */
function po_receber_form() {
	if ( ! isset( $_POST['po_form_nonce'] ) || ! wp_verify_nonce( $_POST['po_form_nonce'], 'po_form' ) ) wp_die( 'Formulário expirado. Volte e tente de novo.' );
	if ( ! empty( $_POST['site'] ) ) wp_safe_redirect( home_url( '/' ) ) && exit; // armadilha de robô
	$tipo = ( isset( $_POST['po_tipo'] ) && 'anuncie' === $_POST['po_tipo'] ) ? 'anuncie' : 'canal';
	$d = array();
	foreach ( array( 'po_nome', 'po_whats', 'po_bairro', 'po_empresa', 'po_formato' ) as $k ) {
		$d[ $k ] = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : '';
	}
	$id = wp_insert_post( array( 'post_type' => 'po_inscrito', 'post_status' => 'publish', 'post_title' => ( 'canal' === $tipo ? 'Canal: ' : 'Anúncio: ' ) . $d['po_nome'] . ' ' . $d['po_whats'] ) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'po_tipo', 'canal' === $tipo ? 'Canal exclusivo' : 'Quer anunciar' );
		foreach ( $d as $k => $v ) update_post_meta( $id, $k, $v );
	}
	if ( 'canal' === $tipo && get_theme_mod( 'po_canal_link' ) ) wp_redirect( get_theme_mod( 'po_canal_link' ) );
	else wp_safe_redirect( add_query_arg( 'enviado', $tipo, home_url( '/' ) ) . '#' . ( 'canal' === $tipo ? 'canal' : 'anuncie' ) );
	exit;
}
add_action( 'admin_post_nopriv_po_form', 'po_receber_form' );
add_action( 'admin_post_po_form', 'po_receber_form' );

/* ---------- SEO: meta description, Open Graph, Twitter Card, dados estruturados ---------- */

/** Remove a exposição da versão do WordPress no <head> (segurança, não afeta ranqueamento). */
remove_action( 'wp_head', 'wp_generator' );

/** Descrição de 150-160 caracteres para a página atual: linha fina da matéria, descrição do portal/categoria, ou a descrição padrão do site. */
function po_meta_descricao() {
	$texto = '';
	if ( is_singular( 'post' ) || is_singular( 'po_video' ) ) {
		$texto = get_post_meta( get_the_ID(), 'po_linha_fina', true );
		if ( ! $texto ) $texto = get_the_excerpt();
	} elseif ( is_category() ) {
		$t = get_queried_object();
		$portal = get_query_var( 'po_portal' );
		$todos  = po_portais();
		if ( $portal && isset( $todos[ $portal ] ) ) {
			$texto = $todos[ $portal ]['desc'] . ' Um portal do ecossistema Portal ONE News.';
		} else {
			$texto = 'Acompanhe as últimas notícias de ' . $t->name . ' no Portal ONE News, o maior ecossistema de notícias da Região Metropolitana de Belo Horizonte.';
		}
	} elseif ( is_author() ) {
		$u = get_queried_object();
		$texto = $u->description ? $u->description : 'Coluna de ' . $u->display_name . ' no Portal ONE News.';
	} elseif ( is_search() ) {
		$texto = 'Resultados da busca por "' . get_search_query() . '" no Portal ONE News.';
	}
	if ( ! $texto ) $texto = get_bloginfo( 'description' );
	if ( ! $texto ) $texto = 'Portal ONE News — o maior ecossistema de notícias da Região Metropolitana de Belo Horizonte: Santa Luzia, BH, Grande BH, Minas, política, esporte e comércio.';
	$texto = trim( wp_strip_all_tags( $texto ) );
	if ( mb_strlen( $texto ) > 160 ) $texto = mb_substr( $texto, 0, 157 ) . '...';
	return $texto;
}

/** Imagem representativa da página atual (para Open Graph/Twitter), com fallback para a logo do portal. */
function po_meta_imagem() {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $src ) return $src[0];
	}
	return get_template_directory_uri() . '/assets/logo.png';
}

/** URL canônica da página atual (home, categoria ou matéria). */
function po_meta_canonical() {
	if ( is_front_page() ) return home_url( '/' );
	if ( is_singular() ) return get_permalink();
	if ( is_category() ) return get_category_link( get_queried_object() );
	if ( is_author() ) return get_author_posts_url( get_queried_object_id() );
	global $wp;
	return home_url( add_query_arg( array(), $wp->request ) );
}

add_action( 'wp_head', function () {
	$titulo = wp_get_document_title();
	$desc   = po_meta_descricao();
	$img    = po_meta_imagem();
	$url    = po_meta_canonical();

	echo "\n" . '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";

	echo '<meta property="og:site_name" content="Portal ONE News">' . "\n";
	echo '<meta property="og:locale" content="pt_BR">' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $titulo ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";

	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $titulo ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";

	if ( is_singular( 'post' ) ) {
		$id = get_the_ID();
		$autor = get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) );
		$dados = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'NewsArticle',
			'headline'         => get_the_title( $id ),
			'description'      => $desc,
			'image'            => array( $img ),
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'mainEntityOfPage' => $url,
			'author'           => array( '@type' => 'Person', 'name' => $autor ? $autor : 'Portal ONE News' ),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => 'Portal ONE News',
				'logo'  => array( '@type' => 'ImageObject', 'url' => get_template_directory_uri() . '/assets/logo.png' ),
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $dados, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	if ( is_front_page() ) {
		$dados = array(
			'@context' => 'https://schema.org',
			'@type'    => 'NewsMediaOrganization',
			'name'     => 'Portal ONE News',
			'url'      => home_url( '/' ),
			'logo'     => get_template_directory_uri() . '/assets/logo.png',
			'description' => $desc,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $dados, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}, 1 );

/* ---------- mais lidas: contador simples ---------- */
add_action( 'wp_head', function () {
	if ( is_single() && ! is_user_logged_in() ) {
		$id = get_the_ID();
		update_post_meta( $id, 'po_views', (int) get_post_meta( $id, 'po_views', true ) + 1 );
	}
} );

/* ---------- utilidades usadas nos modelos ---------- */
function po_editoria( $id = null ) {
	$cats = get_the_category( $id );
	if ( ! $cats ) return null;
	foreach ( $cats as $c ) if ( 'santa-luzia' === $c->slug ) return $c;
	foreach ( $cats as $c ) if ( 'uncategorized' !== $c->slug && (int) get_option( 'default_category' ) !== $c->term_id ) return $c;
	return null;
}
function po_hora( $id = null ) {
	$t = get_post_time( 'U', true, $id );
	if ( wp_date( 'Ymd' ) === wp_date( 'Ymd', $t ) ) return 'Hoje, ' . wp_date( 'H:i', $t );
	if ( wp_date( 'Ymd', time() - DAY_IN_SECONDS ) === wp_date( 'Ymd', $t ) ) return 'Ontem, ' . wp_date( 'H:i', $t );
	return wp_date( 'd/m/Y', $t );
}
function po_data_hoje() {
	$dias = array( 'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado' );
	$meses = array( 1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro' );
	return $dias[ (int) wp_date( 'w' ) ] . ', ' . wp_date( 'j' ) . ' de ' . $meses[ (int) wp_date( 'n' ) ] . ' de ' . wp_date( 'Y' );
}
/** Cor da editoria (títulos coloridos no modelo Globo). */
function po_cor( $id ) {
	$c = po_editoria( $id );
	if ( ! $c ) return 'azul';
	$slugs = array( $c->slug );
	if ( $c->parent ) { $pai = get_category( $c->parent ); if ( $pai && ! is_wp_error( $pai ) ) $slugs[] = $pai->slug; }
	$mapa = array( 'esporte' => 'verde', 'entretenimento' => 'laranja', 'politica' => 'preto', 'comercio' => 'comercio', 'luto' => 'preto' );
	foreach ( $slugs as $sl ) if ( isset( $mapa[ $sl ] ) ) return $mapa[ $sl ];
	return 'azul';
}
/** Ícone (SVG simples, sem depender de nenhum serviço externo) para os botões de compartilhar. */
function po_icone( $nome ) {
	$svgs = array(
		'whatsapp'     => '<path d="M17.47 14.38c-.3-.15-1.73-.85-2-.95-.27-.1-.47-.15-.67.15s-.77.95-.94 1.15-.35.22-.64.07a8.2 8.2 0 0 1-2.42-1.49 9.1 9.1 0 0 1-1.68-2.08c-.18-.3 0-.46.13-.6.13-.14.3-.35.44-.52a2 2 0 0 0 .3-.5.55.55 0 0 0 0-.52c-.08-.15-.68-1.62-.93-2.22s-.49-.5-.67-.5h-.57a1.1 1.1 0 0 0-.79.37A3.33 3.33 0 0 0 6 8.65a5.8 5.8 0 0 0 1.22 3.07 13.27 13.27 0 0 0 5.1 4.5c.71.3 1.27.49 1.7.62.72.23 1.37.2 1.89.12.58-.09 1.73-.71 1.97-1.39s.25-1.27.17-1.39-.27-.2-.57-.35z"/><path d="M12 2a10 10 0 0 0-8.6 15.08L2 22l5.07-1.33A10 10 0 1 0 12 2zm0 18.1a8.1 8.1 0 0 1-4.12-1.13l-.3-.17-3.07.8.82-3-.19-.31A8.12 8.12 0 1 1 12 20.1z"/>',
		'facebook'     => '<path d="M14.5 8.5h2V5.6c-.35-.05-1.54-.15-2.92-.15-2.9 0-4.88 1.78-4.88 5.03v2.6H6v3.3h2.7V22h3.4v-7.62h2.6l.4-3.3h-3V11c0-.95.26-1.5 1.4-1.5z"/>',
		'x'            => '<path d="M17.5 3h2.9l-6.4 7.3L21.5 21h-5.9l-4.6-6-5.3 6H2.7l6.8-7.8L2 3h6.1l4.2 5.6L17.5 3zm-1 16.2h1.6L7.6 4.7H5.9l10.6 14.5z"/>',
		'instagram'    => '<path d="M12 8.6a3.4 3.4 0 1 0 0 6.8 3.4 3.4 0 0 0 0-6.8zm0 5.6a2.2 2.2 0 1 1 0-4.4 2.2 2.2 0 0 1 0 4.4z"/><path d="M15.6 3H8.4A5.4 5.4 0 0 0 3 8.4v7.2A5.4 5.4 0 0 0 8.4 21h7.2a5.4 5.4 0 0 0 5.4-5.4V8.4A5.4 5.4 0 0 0 15.6 3zm4.1 12.6a4.1 4.1 0 0 1-4.1 4.1H8.4a4.1 4.1 0 0 1-4.1-4.1V8.4a4.1 4.1 0 0 1 4.1-4.1h7.2a4.1 4.1 0 0 1 4.1 4.1z"/><circle cx="16.3" cy="7.7" r="1"/>',
		'compartilhar' => '<circle cx="18" cy="5" r="2.2"/><circle cx="6" cy="12" r="2.2"/><circle cx="18" cy="19" r="2.2"/><path d="M8 10.9 16 6.6M8 13.1l8 4.3" stroke="currentColor" stroke-width="1.6" fill="none"/>',
	);
	if ( ! isset( $svgs[ $nome ] ) ) return '';
	return '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">' . $svgs[ $nome ] . '</svg>';
}
/** Foto da notícia, ou um espaço cinza quando não houver. */
function po_img( $id, $tam = 'po-card' ) {
	return has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, $tam, array( 'class' => 'foto', 'alt' => '', 'loading' => 'lazy' ) ) : '<div class="sem-foto"></div>';
}
/** Card do destaque (colunas ao lado da manchete): foto, título colorido e um tópico. */
function po_card_g( $id ) {
	$res = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $id, 'po_resumos', true ) ) ) );
	echo '<article><a class="card-g" href="' . esc_url( get_permalink( $id ) ) . '">' . po_img( $id ) . '<h2 class="c-' . esc_attr( po_cor( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</h2>';
	if ( $res ) echo '<span class="sub">' . esc_html( reset( $res ) ) . '</span>';
	echo '</a></article>';
}
/** Card de notícia. $estilo: 'card' (foto em cima) ou 'linha' (foto ao lado). */
function po_card( $id, $estilo = 'card', $cor = '' ) {
	$ed = po_editoria( $id );
	$cor = $cor ? $cor : po_cor( $id );
	if ( 'linha' === $estilo ) {
		echo '<a class="linha-n" href="' . esc_url( get_permalink( $id ) ) . '">' . ( has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, 'po-card', array( 'class' => 'foto', 'alt' => '', 'loading' => 'lazy' ) ) : '' ) . '<h3 class="c-' . esc_attr( $cor ) . '">' . esc_html( get_the_title( $id ) ) . '</h3></a>';
		return;
	}
	echo '<article><a class="card-p" href="' . esc_url( get_permalink( $id ) ) . '">' . po_img( $id );
	if ( $ed ) echo '<span class="ed c-' . esc_attr( $cor ) . '">' . esc_html( $ed->name ) . '</span>';
	echo '<h3>' . esc_html( get_the_title( $id ) ) . '</h3><span class="hora">' . esc_html( po_hora( $id ) ) . '</span></a></article>';
}
/** Busca posts sem repetir os já mostrados na página. */
function po_posts( $args, &$usados ) {
	$args = wp_parse_args( $args, array( 'posts_per_page' => 4, 'ignore_sticky_posts' => true, 'post_status' => 'publish', 'no_found_rows' => true ) );
	$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? $args['post__not_in'] : array(), $usados );
	$ids = get_posts( array_merge( $args, array( 'fields' => 'ids' ) ) );
	$usados = array_merge( $usados, $ids );
	return $ids;
}
function po_anuncios( $posicao, $qtd = 1, $colunista = 0 ) {
	$mq = array( array( 'key' => 'po_posicao', 'value' => $posicao ) );
	if ( $colunista ) $mq[] = array( 'key' => 'po_colunista', 'value' => (string) $colunista );
	$hoje = wp_date( 'Y-m-d' );
	$lista = array();
	foreach ( get_posts( array( 'post_type' => 'po_anuncio', 'posts_per_page' => 60, 'meta_query' => $mq ) ) as $a ) {
		$ini = get_post_meta( $a->ID, 'po_inicio', true ); $fim = get_post_meta( $a->ID, 'po_fim', true );
		if ( $ini && $ini > $hoje ) continue;
		if ( $fim && $fim < $hoje ) continue;
		$lista[] = $a;
	}
	shuffle( $lista ); // vários anúncios no mesmo espaço se revezam
	return array_slice( $lista, 0, $qtd );
}

/** Banner: mostra o anúncio cadastrado ou um espaço discreto "Anuncie aqui". */
function po_banner( $posicao, $classe, $rotulo, $chamada = '' ) {
	$a = po_anuncios( $posicao );
	echo '<div class="pub ' . esc_attr( $classe ) . '"><small>Publicidade</small>';
	if ( $a && has_post_thumbnail( $a[0] ) ) {
		$link = get_post_meta( $a[0]->ID, 'po_link', true );
		echo '<a href="' . esc_url( $link ? $link : home_url( '/#anuncie' ) ) . '" rel="sponsored noopener" target="_blank">' . get_the_post_thumbnail( $a[0], 'large', array( 'alt' => esc_attr( get_the_title( $a[0] ) ) ) ) . '</a></div>';
		return;
	}
	/* sem anunciante: anúncio da própria casa, no visual do portal (nunca um quadro vazio) */
	$logo = '<img src="' . esc_url( get_template_directory_uri() . '/assets/logo.png' ) . '" alt="Portal ONE News">';
	$url  = esc_url( home_url( '/#anuncie' ) );
	if ( 'lb' === $classe ) {
		echo '<a class="casa casa-lb" href="' . $url . '">' . $logo . '<span class="t"><b>Sua marca no portal de notícias da Grande BH</b><span>Banners, patrocínio de colunas e portais e matérias patrocinadas</span></span><span class="bt-c">Anuncie aqui</span></a></div>';
	} else {
		echo '<a class="casa casa-ret" href="' . $url . '">' . $logo . '<b>Sua marca aqui, ao lado das notícias mais lidas da região</b><span class="bt-c">Fale com o comercial</span></a></div>';
	}
}
/** "Oferecido por": patrocinador de coluna ou de portal. */
function po_patrocinador( $posicao, $colunista = 0 ) {
	$a = po_anuncios( $posicao, 1, $colunista );
	if ( ! $a ) return '';
	$link = get_post_meta( $a[0]->ID, 'po_link', true );
	$img = has_post_thumbnail( $a[0] ) ? get_the_post_thumbnail( $a[0], 'medium', array( 'alt' => esc_attr( get_the_title( $a[0] ) ) ) ) : '<b>' . esc_html( get_the_title( $a[0] ) ) . '</b>';
	return '<a class="of" href="' . esc_url( $link ? $link : '#' ) . '" rel="sponsored noopener" target="_blank">Oferecido por ' . $img . '</a>';
}
/** Colunistas: usuários com "Nome da coluna" preenchido. */
function po_lista_colunistas( $qtd = 4 ) {
	return get_users( array( 'meta_key' => 'po_coluna', 'meta_compare' => '!=', 'meta_value' => '', 'number' => $qtd, 'orderby' => 'ID', 'order' => 'ASC' ) );
}
function po_avatar( $u, $classe = 'av' ) {
	$foto = get_user_meta( $u->ID, 'po_foto', true );
	if ( $foto ) return '<img class="' . esc_attr( $classe ) . '" src="' . esc_url( $foto ) . '" alt="" loading="lazy">';
	$ini = mb_strtoupper( mb_substr( $u->first_name ? $u->first_name : $u->display_name, 0, 1 ) . mb_substr( $u->last_name, 0, 1 ) );
	return '<span class="' . esc_attr( $classe ) . '">' . esc_html( $ini ) . '</span>';
}
/** Link de uma editoria pelo slug. */
function po_link_cat( $slug ) {
	$c = get_category_by_slug( $slug );
	return $c ? get_category_link( $c ) : home_url( '/' );
}
/** Os portais do ecossistema: cada um tem link próprio (/portal-luziense/ etc.). */
function po_portais() {
	return array(
		'luziense' => array( 'nome' => 'Portal Luziense', 'cat' => 'santa-luzia', 'cor' => 'luz', 'logo' => get_template_directory_uri() . '/assets/luziense.png', 'desc' => 'As notícias de Santa Luzia.' ),
		'comercio' => array( 'nome' => 'Portal Comércio', 'cat' => 'comercio', 'cor' => 'com', 'logo' => get_theme_mod( 'po_logo_comercio' ) ? get_theme_mod( 'po_logo_comercio' ) : get_template_directory_uri() . '/assets/comercio.png', 'desc' => 'Comércio, empresas e oportunidades da região.' ),
		'politica' => array( 'nome' => 'Portal Política', 'cat' => 'politica', 'cor' => 'pol', 'logo' => get_theme_mod( 'po_logo_politica' ) ? get_theme_mod( 'po_logo_politica' ) : get_template_directory_uri() . '/assets/politica.png', 'desc' => 'Política de Santa Luzia, da Grande BH e de Minas.' ),
	);
}
function po_portal_url( $chave ) { return home_url( '/portal-' . $chave . '/' ); }
/** Selo do portal: logo quando houver, senão o nome. */
function po_selo( $chave ) {
	$todos = po_portais();
	$p = $todos[ $chave ];
	return '<span class="selo f-' . esc_attr( $p['cor'] ) . '">' . ( $p['logo'] ? '<img src="' . esc_url( $p['logo'] ) . '" alt="' . esc_attr( $p['nome'] ) . '">' : esc_html( mb_strtolower( $p['nome'] ) ) ) . '</span>';
}
add_action( 'init', function () {
	foreach ( po_portais() as $k => $p ) {
		add_rewrite_rule( '^portal-' . $k . '/page/([0-9]+)/?$', 'index.php?category_name=' . $p['cat'] . '&po_portal=' . $k . '&paged=$matches[1]', 'top' );
		add_rewrite_rule( '^portal-' . $k . '/?$', 'index.php?category_name=' . $p['cat'] . '&po_portal=' . $k, 'top' );
	}
} );
add_filter( 'query_vars', function ( $v ) { $v[] = 'po_portal'; return $v; } );
/* garante os links dos portais: se o WordPress ainda não conhece /portal-luziense/ etc., atualiza as regras sozinho (uma vez) */
add_action( 'init', function () {
	$regras = get_option( 'rewrite_rules' );
	if ( ! is_array( $regras ) || ! isset( $regras['^portal-luziense/?$'] ) || ! isset( $regras['^portal-comercio/?$'] ) || ! isset( $regras['^portal-politica/?$'] ) ) {
		foreach ( array( 'santa-luzia' => 'Santa Luzia', 'comercio' => 'Comércio', 'politica' => 'Política' ) as $sl => $nm ) {
			if ( ! get_category_by_slug( $sl ) ) wp_insert_term( $nm, 'category', array( 'slug' => $sl ) );
		}
		flush_rewrite_rules( false );
	}
}, 99 );
/* página de portal nunca dá "não encontrada", mesmo antes de ter notícias */
add_action( 'template_redirect', function () {
	if ( get_query_var( 'po_portal' ) && is_404() ) {
		global $wp_query;
		$wp_query->is_404 = false; $wp_query->is_archive = true; $wp_query->is_category = true;
		status_header( 200 );
	}
}, 1 );
/** Busca: só notícias. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) $q->set( 'post_type', 'post' );
} );
function po_embed_video( $url ) {
	$html = wp_oembed_get( $url );
	return $html ? $html : '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Assistir ao vídeo</a>';
}

/** Menu do topo: links simples; item com a classe "sl" fica dourado (Santa Luzia). */
class PO_Menu_Walker extends Walker_Nav_Menu {
	public function start_el( &$out, $item, $depth = 0, $args = null, $id = 0 ) {
		$sl = in_array( 'sl', (array) $item->classes, true ) || false !== stripos( $item->title, 'Santa Luzia' );
		$out .= '<a' . ( $sl ? ' class="sl"' : '' ) . ' href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
	}
	public function end_el( &$out, $item, $depth = 0, $args = null ) {}
}

/* ---------- configuração automática (roda uma vez, logo depois de ativar o tema) ---------- */
add_action( 'admin_init', function () {
	if ( get_option( 'po_setup' ) === '2.0' || ! current_user_can( 'manage_options' ) ) return;
	$cats = array(
		'santa-luzia' => array( 'Santa Luzia', '' ),
		'grande-bh' => array( 'Grande BH', '' ),
		'belo-horizonte' => array( 'Belo Horizonte', 'grande-bh' ),
		'regiao-metropolitana' => array( 'Região Metropolitana', 'grande-bh' ),
		'contagem' => array( 'Contagem', 'grande-bh' ),
		'betim' => array( 'Betim', 'grande-bh' ),
		'vespasiano' => array( 'Vespasiano', 'grande-bh' ),
		'lagoa-santa' => array( 'Lagoa Santa', 'grande-bh' ),
		'ribeirao-das-neves' => array( 'Ribeirão das Neves', 'grande-bh' ),
		'sabara' => array( 'Sabará', 'grande-bh' ),
		'minas-gerais' => array( 'Minas Gerais', '' ),
		'politica' => array( 'Política', '' ),
		'comercio' => array( 'Comércio', '' ),
		'brasil' => array( 'Brasil', '' ),
		'mundo' => array( 'Mundo', '' ),
		'esporte' => array( 'Esporte', '' ),
		'atletico' => array( 'Atlético', 'esporte' ),
		'cruzeiro' => array( 'Cruzeiro', 'esporte' ),
		'america' => array( 'América', 'esporte' ),
		'entretenimento' => array( 'Entretenimento', '' ),
		'saude' => array( 'Saúde', '' ),
		'educacao' => array( 'Educação', '' ),
		'luto' => array( 'Luto', '' ),
	);
	foreach ( $cats as $slug => $c ) {
		if ( get_category_by_slug( $slug ) ) continue;
		$pai = $c[1] ? get_category_by_slug( $c[1] ) : null;
		wp_insert_term( $c[0], 'category', array( 'slug' => $slug, 'parent' => $pai ? $pai->term_id : 0 ) );
	}
	if ( false !== strpos( get_option( 'blogname' ), 'hostingersite' ) || '' === get_option( 'blogname' ) ) update_option( 'blogname', 'Portal ONE News' );
	update_option( 'blogdescription', 'O maior ecossistema de notícias da Região Metropolitana de Belo Horizonte' );
	update_option( 'timezone_string', 'America/Sao_Paulo' );
	update_option( 'date_format', 'd/m/Y' );
	update_option( 'time_format', 'H:i' );
	update_option( 'show_on_front', 'posts' );
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%category%/%postname%/' );
	flush_rewrite_rules();
	update_option( 'po_setup', '2.0' );
	set_transient( 'po_setup_ok', 1, 300 );
} );
add_action( 'admin_notices', function () {
	if ( get_transient( 'po_setup_ok' ) ) {
		delete_transient( 'po_setup_ok' );
		echo '<div class="notice notice-success"><p><strong>Portal ONE News:</strong> tema 2.0 configurado: editorias, links dos portais (/portal-luziense/, /portal-comercio/, /portal-politica/), fuso de Brasília e links das notícias.</p></div>';
	}
} );

/* ---------- versão 2.4: mais cidades, colunistas cadastrados e correção da chuva de Pedro Leopoldo (roda uma vez) ---------- */
add_action( 'init', function () {
	if ( get_option( 'po_setup_243' ) === '1' ) return;
	$pai = get_category_by_slug( 'grande-bh' );
	foreach ( array( 'pedro-leopoldo' => 'Pedro Leopoldo', 'matozinhos' => 'Matozinhos', 'jaboticatubas' => 'Jaboticatubas', 'venda-nova' => 'Venda Nova', 'nova-lima' => 'Nova Lima', 'ibirite' => 'Ibirité', 'esmeraldas' => 'Esmeraldas', 'sao-jose-da-lapa' => 'São José da Lapa' ) as $sl => $nm ) {
		if ( ! get_category_by_slug( $sl ) ) wp_insert_term( $nm, 'category', array( 'slug' => $sl, 'parent' => $pai ? $pai->term_id : 0 ) );
	}
	/* a notícia da chuva em Pedro Leopoldo tinha ido para "Sem categoria" */
	$pl = get_category_by_slug( 'pedro-leopoldo' );
	if ( $pl ) foreach ( get_posts( array( 'meta_key' => 'po_origem', 'meta_value' => '190931_47', 'posts_per_page' => 1, 'post_status' => 'any' ) ) as $p ) wp_set_post_categories( $p->ID, array( $pl->term_id ) );
	/* colunistas */
	$cols = array(
		array( 'israel-fernandes', 'Israel Fernandes', 'Israel', 'Fernandes', 'Comunicação e marketing', 'Jornalista e especialista em comunicação e marketing' ),
		array( 'jota-bertoldo', 'Jota Bertoldo', 'Jota', 'Bertoldo', 'Engenharia, Tecnologia e Mercado Aeronáutico', 'Engenharia, Tecnologia e Mercado Aeronáutico' ),
		array( 'paulo-alexandre', 'Paulo Alexandre', 'Paulo', 'Alexandre', 'Cientista político', 'Cientista político' ),
	);
	foreach ( $cols as $c ) {
		$u = get_user_by( 'login', $c[0] );
		if ( ! $u ) {
			$uid = wp_insert_user( array( 'user_login' => $c[0], 'user_pass' => wp_generate_password( 32 ), 'display_name' => $c[1], 'first_name' => $c[2], 'last_name' => $c[3], 'nickname' => $c[1], 'role' => 'author', 'user_email' => $c[0] . '@colunistas.portalonenews.invalid' ) );
			if ( is_wp_error( $uid ) ) continue;
		} else $uid = $u->ID;
		update_user_meta( $uid, 'po_coluna', $c[4] );
		update_user_meta( $uid, 'po_quem', $c[5] );
		update_user_meta( $uid, 'po_foto', get_template_directory_uri() . '/assets/colunistas/' . $c[0] . '.jpg' );
	}
	flush_rewrite_rules( false );
	update_option( 'po_setup_243', '1' );
	set_transient( 'po_setup_24_ok', 1, 300 );
} );
add_action( 'admin_notices', function () {
	if ( get_transient( 'po_setup_24_ok' ) ) {
		delete_transient( 'po_setup_24_ok' );
		echo '<div class="notice notice-success"><p><strong>Portal ONE News 2.4:</strong> colunistas cadastrados (Israel Fernandes, Jota Bertoldo e Paulo Alexandre), novas cidades criadas e canal do WhatsApp configurado. Para cadastrar outro colunista: Usuários &gt; Adicionar novo (função Autor) e preencha "Nome da coluna" no perfil.</p></div>';
	}
} );

/* ---------- robô de notícias: o site busca as notícias novas a cada 15 minutos ---------- */
define( 'PO_FEED', 'https://api.github.com/repos/estudioeleve/portal-artes-feed/contents/dados/site.json' );
define( 'PO_FEED_COLUNAS', 'https://api.github.com/repos/estudioeleve/portal-artes-feed/contents/dados/colunas.json' );
add_filter( 'cron_schedules', function ( $s ) {
	$s['po_15min'] = array( 'interval' => 900, 'display' => 'A cada 15 minutos' );
	return $s;
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'po_importar' ) ) wp_schedule_event( time() + 60, 'po_15min', 'po_importar' );
} );
add_action( 'po_importar', 'po_importar_noticias' );
function po_importar_noticias() {
	if ( get_option( 'po_robo_pausado' ) ) return;
	$novos = po_importar_feed( PO_FEED, false );
	if ( false === $novos ) { update_option( 'po_robo_ultimo', current_time( 'mysql' ) . ' · erro ao buscar o arquivo' ); return; }
	$cols = po_importar_feed( PO_FEED_COLUNAS, true );
	update_option( 'po_robo_ultimo', current_time( 'mysql' ) . ' · ' . $novos . ' notícia(s) nova(s)' . ( $cols ? ' · ' . $cols . ' coluna(s) nova(s)' : '' ) );
}
/** Acha o usuário colunista pelo slug (israel-fernandes) ou pelo nome ("J. Bertoldo" = "Jota Bertoldo"). */
function po_achar_colunista( $slug, $nome ) {
	if ( $slug && ( $u = get_user_by( 'slug', $slug ) ) ) return $u;
	$nome = trim( (string) $nome );
	if ( '' === $nome ) return null; /* sem slug válido e sem nome: não arrisca casar com string vazia de outra conta */
	$n = sanitize_title( $nome );
	$sob = sanitize_title( preg_replace( '/^.*\s/u', '', $nome ) );
	foreach ( po_lista_colunistas( 200 ) as $u ) {
		if ( sanitize_title( $u->display_name ) === $n || sanitize_title( trim( $u->first_name . ' ' . $u->last_name ) ) === $n ) return $u;
		if ( $sob && sanitize_title( preg_replace( '/^.*\s/u', '', trim( $u->display_name ) ) ) === $sob ) return $u;
	}
	return null;
}
/** Importa um arquivo do robô. Colunas: publicadas no nome do colunista; sem colunista achado, a coluna espera (não sai como Redação). */
function po_importar_feed( $url, $coluna ) {
	$r = wp_remote_get( $url, array( 'timeout' => 30, 'headers' => array( 'Accept' => 'application/vnd.github.raw', 'User-Agent' => 'PortalONENews-WordPress' ) ) );
	if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) return $coluna ? 0 : false;
	$lista = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( ! is_array( $lista ) ) return 0;
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$robo = get_user_by( 'login', 'robo-portal' );
	if ( ! $robo && ! $coluna ) {
		$uid = wp_insert_user( array( 'user_login' => 'robo-portal', 'user_pass' => wp_generate_password( 32 ), 'display_name' => 'Redação Portal ONE News', 'role' => 'author', 'user_email' => 'robo@portalonenews.invalid' ) );
		$robo = is_wp_error( $uid ) ? null : get_user_by( 'id', $uid );
	}
	$novos = 0; $sem = array();
	foreach ( array_slice( $lista, -200 ) as $n ) {
		if ( empty( $n['id'] ) || empty( $n['titulo'] ) ) continue;
		$ja = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'meta_key' => 'po_origem', 'meta_value' => $n['id'], 'fields' => 'ids', 'posts_per_page' => 1 ) );
		if ( $ja ) continue;
		$autor = $robo ? $robo->ID : 1;
		if ( $coluna ) {
			$u = po_achar_colunista( isset( $n['colunista_slug'] ) ? $n['colunista_slug'] : '', isset( $n['colunista'] ) ? $n['colunista'] : '' );
			if ( ! $u ) { $sem[] = isset( $n['colunista'] ) ? $n['colunista'] : '?'; continue; }
			$autor = $u->ID;
		}
		$cat = ! empty( $n['categoria'] ) ? get_category_by_slug( sanitize_title( $n['categoria'] ) ) : null;
		$id = wp_insert_post( array(
			'post_title' => wp_strip_all_tags( $n['titulo'] ),
			'post_content' => wp_kses_post( $n['texto'] ),
			'post_excerpt' => sanitize_text_field( isset( $n['linha_fina'] ) ? $n['linha_fina'] : '' ),
			'post_status' => 'publish',
			'post_author' => $autor,
			'post_date' => ! empty( $n['data'] ) ? wp_date( 'Y-m-d H:i:s', strtotime( $n['data'] ) ) : current_time( 'mysql' ),
			'post_category' => $cat ? array( $cat->term_id ) : array(),
		) );
		if ( ! $id || is_wp_error( $id ) ) continue;
		update_post_meta( $id, 'po_origem', sanitize_text_field( $n['id'] ) );
		update_post_meta( $id, 'po_linha_fina', sanitize_text_field( isset( $n['linha_fina'] ) ? $n['linha_fina'] : '' ) );
		update_post_meta( $id, 'po_credito_foto', sanitize_text_field( isset( $n['credito_foto'] ) ? $n['credito_foto'] : '' ) );
		update_post_meta( $id, 'po_fonte', sanitize_text_field( isset( $n['fonte'] ) ? $n['fonte'] : '' ) );
		if ( ! empty( $n['resumos'] ) ) update_post_meta( $id, 'po_resumos', sanitize_textarea_field( $n['resumos'] ) );
		if ( ! empty( $n['foto'] ) && 0 === strpos( $n['foto'], 'https://raw.githubusercontent.com/estudioeleve/' ) ) {
			$mid = media_sideload_image( $n['foto'], $id, wp_strip_all_tags( ! empty( $n['alt'] ) ? $n['alt'] : $n['titulo'] ), 'id' );
			if ( ! is_wp_error( $mid ) ) set_post_thumbnail( $id, $mid );
		}
		$novos++;
	}
	if ( $coluna ) update_option( 'po_colunas_sem_autor', array_values( array_unique( $sem ) ) );
	return $novos;
}
/* painel: ver o robô e buscar agora */
add_action( 'admin_menu', function () {
	add_menu_page( 'Robô de notícias', 'Robô de notícias', 'manage_options', 'po-robo', 'po_pagina_robo', 'dashicons-rss', 3 );
} );
function po_pagina_robo() {
	if ( isset( $_POST['po_acao'] ) && check_admin_referer( 'po_robo' ) ) {
		if ( 'buscar' === $_POST['po_acao'] ) po_importar_noticias();
		if ( 'pausar' === $_POST['po_acao'] ) update_option( 'po_robo_pausado', 1 );
		if ( 'ligar' === $_POST['po_acao'] ) delete_option( 'po_robo_pausado' );
	}
	$pausado = get_option( 'po_robo_pausado' );
	echo '<div class="wrap"><h1>Robô de notícias</h1>';
	echo '<p>O site busca sozinho, a cada 15 minutos, as notícias que o robô publica no Instagram do Portal, com texto completo e foto — e também as colunas, no nome de cada colunista.</p>';
	echo '<p><strong>Situação:</strong> ' . ( $pausado ? 'pausado' : 'ligado' ) . '<br><strong>Última busca:</strong> ' . esc_html( get_option( 'po_robo_ultimo', 'ainda não buscou' ) ) . '</p>';
	$sem = get_option( 'po_colunas_sem_autor' );
	if ( $sem ) echo '<p style="color:#b32d2e"><strong>Colunas esperando:</strong> não achei o usuário de ' . esc_html( implode( ', ', $sem ) ) . '. Confira o nome em Usuários (nome de exibição igual ao do colunista).</p>';
	echo '<form method="post">';
	wp_nonce_field( 'po_robo' );
	echo '<button class="button button-primary" name="po_acao" value="buscar">Buscar notícias agora</button> ';
	echo $pausado ? '<button class="button" name="po_acao" value="ligar">Ligar o robô</button>' : '<button class="button" name="po_acao" value="pausar">Pausar o robô</button>';
	echo '</form></div>';
}

/** Card de vídeo: capa com botão play; ao tocar, vira o player (assets/tema.js). */
function po_yt_id( $url ) {
	if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', (string) $url, $m ) ) return $m[1];
	return '';
}
function po_video_card( $v ) {
	$url = get_post_meta( $v->ID, 'po_url', true );
	$rede = false !== strpos( (string) $url, 'instagram' ) ? 'Instagram · Reels' : 'YouTube';
	if ( has_post_thumbnail( $v ) ) $capa = get_the_post_thumbnail( $v, 'po-card', array( 'alt' => '', 'loading' => 'lazy' ) );
	elseif ( $yt = po_yt_id( $url ) ) $capa = '<img src="' . esc_url( 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' ) . '" alt="" loading="lazy">';
	else $capa = '';
	echo '<a class="vid" href="' . esc_url( get_permalink( $v ) ) . '" target="_blank" rel="noopener"><span class="cap">' . $capa . '<span class="play"></span></span><b>' . esc_html( get_the_title( $v ) ) . '</b><span class="rede">' . esc_html( $rede ) . '</span></a>';
}
/** Mais lidas dos últimos 3 dias (completa com as mais recentes). */
function po_mais_lidas( $qtd = 6, $classe = '' ) {
	$lidas = get_posts( array( 'posts_per_page' => $qtd, 'meta_key' => 'po_views', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'date_query' => array( array( 'after' => '3 days ago' ) ), 'ignore_sticky_posts' => true ) );
	if ( count( $lidas ) < $qtd ) $lidas = array_merge( $lidas, get_posts( array( 'posts_per_page' => $qtd - count( $lidas ), 'post__not_in' => wp_list_pluck( $lidas, 'ID' ), 'ignore_sticky_posts' => true ) ) );
	echo '<ol class="lidas ' . esc_attr( $classe ) . '">';
	foreach ( $lidas as $p ) echo '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></li>';
	echo '</ol>';
}

/* Google Analytics 4 (Portal ONE News) */
add_action( 'wp_head', function () {
	?>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-M1LGXZ7C3V"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-M1LGXZ7C3V');
</script>
	<?php
}, 1 );

/* painel: colunas da lista de anúncios (onde aparece e até quando) */
add_filter( 'manage_po_anuncio_posts_columns', function ( $c ) {
	return array( 'cb' => $c['cb'], 'title' => 'Anunciante', 'po_onde' => 'Onde aparece', 'po_periodo' => 'Período', 'date' => 'Criado em' );
} );
add_action( 'manage_po_anuncio_posts_custom_column', function ( $col, $id ) {
	$nomes = array( 'topo' => 'Banner grande 1 (970 × 90)', 'meio' => 'Banner grande 2 (970 × 90)', 'lateral' => 'Quadrado 1 (300 × 250)', 'lateral2' => 'Quadrado 2 (300 × 250)', 'vitrine' => 'Vitrine', 'videos' => 'Patrocínio dos vídeos', 'coluna' => 'Patrocínio de coluna', 'portal-luziense' => 'Patrocínio Portal Luziense', 'portal-comercio' => 'Patrocínio Portal Comércio', 'portal-politica' => 'Patrocínio Portal Política' );
	if ( 'po_onde' === $col ) { $p = get_post_meta( $id, 'po_posicao', true ); echo esc_html( isset( $nomes[ $p ] ) ? $nomes[ $p ] : '—' ); }
	if ( 'po_periodo' === $col ) {
		$ini = get_post_meta( $id, 'po_inicio', true ); $fim = get_post_meta( $id, 'po_fim', true );
		echo esc_html( ( $ini ? wp_date( 'd/m/Y', strtotime( $ini ) ) : 'já' ) . ' até ' . ( $fim ? wp_date( 'd/m/Y', strtotime( $fim ) ) : 'sem data' ) );
		if ( $fim && $fim < wp_date( 'Y-m-d' ) ) echo ' <strong style="color:#b32d2e">(vencido)</strong>';
	}
}, 10, 2 );

/* ===================================================================
 * Correspondente Cidadão — recebe denúncias/pautas da página pública
 * (page-denuncia.php). Fica salvo no painel e avisa por e-mail.
 * =================================================================== */

/* tipo de registro, só aparece pra quem está logado no painel */
add_action( 'init', function () {
	register_post_type( 'po_denuncia', array(
		'label' => 'Denúncias', 'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-warning',
		'capability_type' => 'post', 'map_meta_cap' => true, 'capabilities' => array( 'create_posts' => 'do_not_allow' ), // denúncia só chega pelo site
		'menu_position' => 4, 'supports' => array( 'title' ),
		'labels' => array( 'name' => 'Denúncias', 'singular_name' => 'Denúncia', 'all_items' => 'Denúncias recebidas' ),
	) );
} );

/* tela de cada denúncia: tudo visível numa caixa só — texto, local, quem mandou e os anexos
 * (foto, vídeo, áudio) já tocando ali, sem precisar abrir a biblioteca de mídia */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'dc_detalhes', 'Detalhes da denúncia', 'po_denuncia_metabox_detalhes', 'po_denuncia', 'normal', 'high' );
	add_meta_box( 'dc_midia_pub', 'Publicar esta denúncia', 'po_denuncia_metabox_midia', 'po_denuncia', 'side', 'high' );
} );

/* caixa lateral: o editor escolhe (ou envia) a foto/vídeo que vai na arte da denúncia */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$s = get_current_screen();
	if ( $s && 'po_denuncia' === $s->post_type ) wp_enqueue_media();
} );
function po_denuncia_metabox_midia( $post ) {
	wp_nonce_field( 'dc_midia_pub', 'dc_midia_nonce' );
	$fid = (int) get_post_meta( $post->ID, 'dc_foto_pub', true );
	$vid = (int) get_post_meta( $post->ID, 'dc_video_pub', true );
	foreach ( array( array( 'foto', 'Foto', $fid, 'image', 'vai pro site e, se não tiver vídeo, pro feed' ), array( 'video', 'Vídeo', $vid, 'video', 'vai pro Instagram como Reels' ) ) as $sl ) :
		list( $k, $rot, $aid, $tp, $dica ) = $sl; $u = $aid ? wp_get_attachment_url( $aid ) : ''; ?>
		<p style="margin:0 0 4px"><b><?php echo esc_html( $rot ); ?></b> <span style="color:#646970;font-size:12px">(<?php echo esc_html( $dica ); ?>)</span></p>
		<div class="dc-slot" data-k="<?php echo esc_attr( $k ); ?>" data-tp="<?php echo esc_attr( $tp ); ?>" style="margin-bottom:14px">
			<div class="dc-prev" style="margin-bottom:8px"><?php
				if ( $u && 'video' === $tp ) echo '<video src="' . esc_url( $u ) . '" controls style="width:100%;border-radius:6px"></video>';
				elseif ( $u ) echo '<img src="' . esc_url( $u ) . '" style="width:100%;border-radius:6px;display:block">';
				else echo '<p style="margin:0;color:#646970">Nenhum arquivo.</p>';
			?></div>
			<input type="hidden" name="dc_<?php echo esc_attr( $k ); ?>_pub" value="<?php echo esc_attr( $aid ); ?>">
			<button type="button" class="button button-primary dc-escolher"><?php echo $aid ? 'Trocar' : 'Adicionar ' . esc_html( strtolower( $rot ) ); ?></button>
			<button type="button" class="button dc-tirar" <?php echo $aid ? '' : 'style="display:none"'; ?>>Remover</button>
		</div>
	<?php endforeach; ?>
	<p style="color:#646970;font-size:12px;margin:10px 0 0">Depois de escolher, clique em <b>Atualizar</b> pra salvar.</p>
	<?php
	$tit  = get_post_meta( $post->ID, 'dc_titulo_pub', true );
	$cat  = get_post_meta( $post->ID, 'dc_cat_pub', true );
	$cat  = $cat ? $cat : 'Denúncia · Santa Luzia';
	$prt  = get_post_meta( $post->ID, 'dc_portal_pub', true );
	$prt  = $prt ? $prt : 'luz';
	$apr  = get_post_meta( $post->ID, 'dc_aprovada', true );
	$mat  = (int) get_post_meta( $post->ID, 'dc_post_id', true );
	$art  = get_post_meta( $post->ID, 'dc_artes', true );
	$ins  = get_post_meta( $post->ID, 'dc_insta', true );
	$ins_st = get_post_meta( $post->ID, 'dc_insta_status', true );
	?>
	<hr style="margin:16px 0">
	<p style="margin:0 0 4px"><b>Título da arte</b> <span style="color:#646970">(opcional — se deixar vazio, o robô escreve)</span></p>
	<textarea name="dc_titulo_pub" rows="3" style="width:100%" maxlength="120" placeholder="Ex.: Morador cai em buraco e tem moto danificada no Imperial"><?php echo esc_textarea( $tit ); ?></textarea>
	<p style="margin:10px 0 4px"><b>Editoria</b></p>
	<input type="text" name="dc_cat_pub" value="<?php echo esc_attr( $cat ); ?>" style="width:100%">
	<p style="margin:10px 0 4px"><b>Moldura</b></p>
	<select name="dc_portal_pub" style="width:100%">
		<option value="luz" <?php selected( $prt, 'luz' ); ?>>Portal Luziense (Santa Luzia)</option>
		<option value="one" <?php selected( $prt, 'one' ); ?>>Portal ONE News (outras cidades)</option>
	</select>
	<?php $lib = get_post_meta( $post->ID, 'dc_liberada', true ); if ( ! $apr && $lib ) : ?>
		<div style="margin:14px 0 8px;padding:12px;border-radius:8px;background:#f0f6fc;border:1px solid #9ec2e6;font-size:14px"><b style="color:#135e96">✅ Aprovada por você</b> — o robô está criando o título e vai publicar no site e no Instagram. Não precisa fazer mais nada.</div>
	<?php elseif ( $apr ) : ?>
		<div style="margin:14px 0 8px;padding:12px;border-radius:8px;background:#edfaef;border:1px solid #68de7c;font-size:14px"><b style="color:#1e7e34">✅ Denúncia concluída</b> — publicada no site<?php echo 'postado' === $ins_st ? ' e postada no Instagram' : '. Instagram: aguardando o robô'; ?>.</div>
		<button type="submit" name="dc_aprovar" value="1" class="button" style="width:100%" onclick="return confirm('Refazer as artes e atualizar a matéria? (não posta de novo no Instagram)');">Refazer as artes e atualizar a matéria</button>
	<?php else : ?>
		<button type="submit" name="dc_aprovar" value="1" class="button button-primary button-large" style="width:100%;margin-top:14px;height:auto;padding:10px;font-size:15px">Aprovar e publicar agora</button>
		<p style="color:#646970;font-size:12px;margin:6px 0 0">Precisa só da foto ou do vídeo. O robô escreve o título, o site gera as artes e publica a matéria, e o robô posta no Instagram.</p>
	<?php endif; ?>
	<?php if ( $apr ) : ?>
		<div style="margin-top:12px;font-size:13px;line-height:1.7">
			<b style="color:#1e7e34">✓ Aprovada</b> em <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $apr ) ) ); ?><br>
			<?php if ( $mat ) : ?>Matéria: <a href="<?php echo esc_url( get_permalink( $mat ) ); ?>" target="_blank">ver no site</a><br><?php endif; ?>
			<?php if ( is_array( $art ) ) : foreach ( array( 'feed' => 'Arte feed', 'reels' => 'Arte Reels', 'site' => 'Capa site' ) as $k => $rot ) : if ( ! empty( $art[ $k ] ) ) : ?><a href="<?php echo esc_url( $art[ $k ] ); ?>" target="_blank"><?php echo esc_html( $rot ); ?></a> · <?php endif; endforeach; endif; ?><br>
			Instagram: <?php echo 'postado' === $ins_st ? '<b style="color:#1e7e34">postado</b>' : '<b style="color:#996800">aguardando o robô</b>'; ?>
		</div>
	<?php endif; ?>
	<script>
	(function () {
		document.querySelectorAll('.dc-slot').forEach(function (sl) {
			var tp = sl.dataset.tp, campo = sl.querySelector('input'), prev = sl.querySelector('.dc-prev'), bt = sl.querySelector('.dc-escolher'), tirar = sl.querySelector('.dc-tirar'), frame;
			bt.addEventListener('click', function () {
				if (!frame) {
					frame = wp.media({ title: tp === 'video' ? 'Vídeo da denúncia' : 'Foto da denúncia', button: { text: 'Usar este arquivo' }, multiple: false, library: { type: tp } });
					frame.on('select', function () {
						var a = frame.state().get('selection').first().toJSON();
						campo.value = a.id;
						prev.innerHTML = tp === 'video' ? '<video src="' + a.url + '" controls style="width:100%;border-radius:6px"></video>' : '<img src="' + a.url + '" style="width:100%;border-radius:6px;display:block">';
						bt.textContent = 'Trocar'; tirar.style.display = '';
						window.dcSujo = true;
					});
				}
				frame.open();
			});
			tirar.addEventListener('click', function () { campo.value = ''; prev.innerHTML = '<p style="margin:0;color:#646970">Nenhum arquivo.</p>'; tirar.style.display = 'none'; });
		});
		/* avisa se sair da página sem salvar a foto/vídeo escolhidos */
		var f = document.getElementById('post');
		if (f) f.addEventListener('submit', function () { window.dcSujo = false; });
		window.addEventListener('beforeunload', function (e) { if (window.dcSujo) { e.preventDefault(); e.returnValue = ''; } });
	})();
	</script>
	<?php
}
add_action( 'save_post_po_denuncia', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['dc_midia_nonce'] ) || ! wp_verify_nonce( $_POST['dc_midia_nonce'], 'dc_midia_pub' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	foreach ( array( 'foto', 'video' ) as $k ) {
		$id = isset( $_POST[ 'dc_' . $k . '_pub' ] ) ? absint( $_POST[ 'dc_' . $k . '_pub' ] ) : 0;
		if ( $id ) update_post_meta( $post_id, 'dc_' . $k . '_pub', $id );
		else delete_post_meta( $post_id, 'dc_' . $k . '_pub' );
	}
	if ( isset( $_POST['dc_titulo_pub'] ) ) update_post_meta( $post_id, 'dc_titulo_pub', sanitize_text_field( wp_unslash( $_POST['dc_titulo_pub'] ) ) );
	if ( isset( $_POST['dc_cat_pub'] ) ) update_post_meta( $post_id, 'dc_cat_pub', sanitize_text_field( wp_unslash( $_POST['dc_cat_pub'] ) ) );
	if ( isset( $_POST['dc_portal_pub'] ) ) update_post_meta( $post_id, 'dc_portal_pub', 'one' === $_POST['dc_portal_pub'] ? 'one' : 'luz' );
	// campos editáveis da denúncia (local, quem mandou, nome, instagram); o relato é gravado no filtro wp_insert_post_data
	if ( isset( $_POST['dc_ed_local'] ) ) {
		$antes = get_post_meta( $post_id, 'dc_local', true ) . '|' . get_post_meta( $post_id, 'dc_identidade', true ) . '|' . get_post_meta( $post_id, 'dc_nome', true );
		update_post_meta( $post_id, 'dc_local', sanitize_text_field( wp_unslash( $_POST['dc_ed_local'] ) ) );
		update_post_meta( $post_id, 'dc_identidade', ( isset( $_POST['dc_ed_identidade'] ) && 'credito' === $_POST['dc_ed_identidade'] ) ? 'credito' : 'anonimo' );
		if ( isset( $_POST['dc_ed_nome'] ) ) update_post_meta( $post_id, 'dc_nome', sanitize_text_field( wp_unslash( $_POST['dc_ed_nome'] ) ) );
		if ( isset( $_POST['dc_ed_insta'] ) ) update_post_meta( $post_id, 'dc_insta', sanitize_text_field( wp_unslash( $_POST['dc_ed_insta'] ) ) );
		$depois = get_post_meta( $post_id, 'dc_local', true ) . '|' . get_post_meta( $post_id, 'dc_identidade', true ) . '|' . get_post_meta( $post_id, 'dc_nome', true );
		// já publicada e o Israel mudou algo (e não clicou em "refazer"): atualiza a matéria do site na hora
		$mat = (int) get_post_meta( $post_id, 'dc_post_id', true );
		if ( $mat && get_post( $mat ) && empty( $_POST['dc_aprovar'] ) && ( $antes !== $depois || ! empty( $GLOBALS['po_den_relato_mudou'] ) ) ) {
			wp_update_post( array( 'ID' => $mat, 'post_content' => po_denuncia_html_site( $post_id ) ) );
			po_limpar_cache_post( $mat );
			$anon = 'credito' !== get_post_meta( $post_id, 'dc_identidade', true ) || ! get_post_meta( $post_id, 'dc_nome', true );
			update_post_meta( $mat, 'po_linha_fina', 'Denúncia · Protocolo ' . get_post_meta( $post_id, 'dc_protocolo', true ) . ' · ' . ( $anon ? 'Denúncia anônima' : 'Enviada por ' . get_post_meta( $post_id, 'dc_nome', true ) ) );
		}
	}
	if ( ! empty( $_POST['dc_aprovar'] ) ) {
		$md = po_denuncia_midias( $post_id );
		if ( ! $md['foto'] && ! $md['video'] ) {
			set_transient( 'po_den_aviso_' . get_current_user_id(), 'adicione uma foto ou um vídeo antes de aprovar.', 60 );
		} elseif ( '' === trim( (string) get_post_meta( $post_id, 'dc_titulo_pub', true ) ) && ! get_post_meta( $post_id, 'dc_aprovada', true ) ) {
			update_post_meta( $post_id, 'dc_liberada', current_time( 'mysql' ) ); // o robô cria o título e publica
			set_transient( 'po_den_aviso_' . get_current_user_id(), 'robo', 60 );
		} else {
			$r = po_denuncia_publicar( $post_id );
			if ( is_wp_error( $r ) ) set_transient( 'po_den_aviso_' . get_current_user_id(), $r->get_error_message(), 60 );
			else set_transient( 'po_den_aviso_' . get_current_user_id(), 'ok', 60 );
		}
	}
}, 20 );

/* relato editado pelo Israel no painel: grava no texto da denúncia (o original do leitor fica guardado) */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'po_denuncia' !== $data['post_type'] || empty( $postarr['ID'] ) || ! isset( $_POST['dc_ed_relato'] ) ) return $data;
	if ( ! isset( $_POST['dc_midia_nonce'] ) || ! wp_verify_nonce( $_POST['dc_midia_nonce'], 'dc_midia_pub' ) ) return $data;
	if ( ! current_user_can( 'edit_post', $postarr['ID'] ) ) return $data;
	$novo  = trim( sanitize_textarea_field( wp_unslash( $_POST['dc_ed_relato'] ) ) );
	$atual = trim( (string) get_post_field( 'post_content', $postarr['ID'] ) );
	if ( $novo === $atual ) return $data;
	if ( '' !== $atual && ! get_post_meta( $postarr['ID'], 'dc_relato_original', true ) ) update_post_meta( $postarr['ID'], 'dc_relato_original', $atual );
	$data['post_content'] = wp_slash( $novo );
	$GLOBALS['po_den_relato_mudou'] = true;
	return $data;
}, 10, 2 );

add_action( 'admin_notices', function () {
	$k = 'po_den_aviso_' . get_current_user_id();
	$m = get_transient( $k );
	if ( ! $m ) return;
	delete_transient( $k );
	if ( 'robo' === $m ) { echo '<div class="notice notice-success is-dismissible"><p><b>Denúncia aprovada.</b> O robô vai escrever o título, gerar as artes, publicar a matéria no site e postar no Instagram. Não precisa fazer mais nada.</p></div>'; return; }
	if ( 'ok' === $m ) echo '<div class="notice notice-success is-dismissible"><p><b>Denúncia aprovada.</b> As artes foram geradas, a matéria foi publicada no site e o robô já pode postar no Instagram.</p></div>';
	else echo '<div class="notice notice-error is-dismissible"><p><b>Não deu pra aprovar:</b> ' . esc_html( $m ) . '</p></div>';
} );

require_once get_template_directory() . '/inc/denuncia-arte.php';
// as rotas do robô nunca podem ficar em cache (senão ele vê lista velha e posta repetido)
add_filter( 'rest_post_dispatch', function ( $res, $server, $req ) {
	if ( 0 === strpos( $req->get_route(), '/portalone/v1/' ) ) {
		do_action( 'litespeed_control_set_nocache', 'robo portalone' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );
		$res->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$res->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
	}
	return $res;
}, 10, 3 );


/* foto e vídeo da denúncia: os escolhidos pelo editor ou, se faltar, os que a pessoa mandou */
function po_denuncia_midias( $id ) {
	$foto = (int) get_post_meta( $id, 'dc_foto_pub', true );
	$video = (int) get_post_meta( $id, 'dc_video_pub', true );
	foreach ( get_attached_media( '', $id ) as $a ) {
		if ( ! $foto && 0 === strpos( $a->post_mime_type, 'image' ) ) $foto = $a->ID;
		if ( ! $video && 0 === strpos( $a->post_mime_type, 'video' ) ) $video = $a->ID;
	}
	return array( 'foto' => $foto, 'video' => $video );
}


// texto da matéria da denúncia no site: protocolo, quem enviou, relato na íntegra (sem moldura)
function po_denuncia_html_site( $id ) {
	$prot  = get_post_meta( $id, 'dc_protocolo', true );
	$anon  = 'credito' !== get_post_meta( $id, 'dc_identidade', true ) || ! get_post_meta( $id, 'dc_nome', true );
	$quem  = $anon ? 'Denúncia anônima' : 'Enviada por ' . get_post_meta( $id, 'dc_nome', true );
	$html  = '<p><strong>Denúncia · Protocolo ' . esc_html( $prot ) . '</strong><br>' . esc_html( $quem ) . '</p>';
	$local = get_post_meta( $id, 'dc_local', true );
	if ( $local ) $html .= '<p><strong>Onde:</strong> ' . esc_html( $local ) . '</p>';
	$html .= '<p>Relato enviado ao Portal pelo canal <a href="' . esc_url( po_denuncia_link() ) . '">Faça sua denúncia</a>:</p>';
	$html .= '<blockquote><p>' . nl2br( esc_html( get_post_field( 'post_content', $id ) ) ) . '</p></blockquote>';
	$html .= '<p>O espaço está aberto para os citados se manifestarem.</p>';
	$html .= '<p>Viu algo errado no seu bairro? <a href="' . esc_url( po_denuncia_link() ) . '">Faça sua denúncia aqui</a>.</p>';
	return $html;
}
// uma vez só: arruma as matérias de denúncia já publicadas (capa = foto, texto novo)
add_action( 'init', function () {
	if ( get_option( 'po_den_site_v2' ) ) return;
	update_option( 'po_den_site_v2', 1 );
	foreach ( get_posts( array( 'post_type' => 'po_denuncia', 'post_status' => 'any', 'posts_per_page' => 100, 'meta_key' => 'dc_post_id' ) ) as $d ) {
		$mat = (int) get_post_meta( $d->ID, 'dc_post_id', true );
		if ( ! $mat || ! get_post( $mat ) ) continue;
		$md = po_denuncia_midias( $d->ID );
		if ( $md['foto'] ) set_post_thumbnail( $mat, $md['foto'] );
		wp_update_post( array( 'ID' => $mat, 'post_content' => po_denuncia_html_site( $d->ID ) ) );
		$anon = 'credito' !== get_post_meta( $d->ID, 'dc_identidade', true ) || ! get_post_meta( $d->ID, 'dc_nome', true );
		update_post_meta( $mat, 'po_linha_fina', 'Denúncia · Protocolo ' . get_post_meta( $d->ID, 'dc_protocolo', true ) . ' · ' . ( $anon ? 'Denúncia anônima' : 'Enviada por ' . get_post_meta( $d->ID, 'dc_nome', true ) ) );
	}
}, 99 );

function po_denuncia_publicar( $id ) {
	$titulo = trim( (string) get_post_meta( $id, 'dc_titulo_pub', true ) );
	if ( '' === $titulo ) return new WP_Error( 'sem_titulo', 'escreva o título da arte.' );
	$md = po_denuncia_midias( $id );
	if ( ! $md['foto'] && ! $md['video'] ) return new WP_Error( 'sem_midia', 'adicione uma foto ou um vídeo na caixa "Publicar esta denúncia".' );
	if ( ! function_exists( 'imagettftext' ) ) return new WP_Error( 'sem_gd', 'o servidor não tem o GD com fontes ativado.' );

	$video   = (bool) $md['video'];
	$arq     = $md['foto'] ? get_attached_file( $md['foto'] ) : '';
	$prot    = get_post_meta( $id, 'dc_protocolo', true );
	$anon    = 'credito' !== get_post_meta( $id, 'dc_identidade', true );
	$nome    = get_post_meta( $id, 'dc_nome', true );
	if ( ! $anon && ! $nome ) $anon = true;
	$portal  = get_post_meta( $id, 'dc_portal_pub', true ) === 'one' ? 'one' : 'luz';
	$cat     = get_post_meta( $id, 'dc_cat_pub', true );
	$cat     = $cat ? $cat : 'Denúncia · Santa Luzia';
	$base    = array( 'portal' => $portal, 'foto' => $arq, 'cat' => $cat, 'titulo' => $titulo, 'anonima' => $anon, 'nome' => $nome, 'protocolo' => $prot );

	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'denuncias';
	wp_mkdir_p( $dir );
	$slug = sanitize_file_name( strtolower( $prot ? $prot : 'denuncia-' . $id ) ) . '-' . time();
	$artes = array();
	foreach ( array( 'feed', 'reels', 'site' ) as $f ) {
		$d = $base; $d['formato'] = $f;
		if ( 'reels' === $f && $video ) $d['foto'] = ''; // Reels com vídeo: moldura transparente pra ir por cima do vídeo
		$saida = $dir . '/' . $slug . '-' . $f . '.png';
		if ( ! po_arte_denuncia( $d, $saida ) ) return new WP_Error( 'arte', 'não consegui gerar a arte (' . $f . ').' );
		// o Instagram só aceita JPG: o feed (e o Reels com foto) saem em JPG; o Reels com vídeo fica PNG transparente
		if ( 'feed' === $f || ( 'reels' === $f && ! $video ) ) {
			$png = @imagecreatefrompng( $saida );
			if ( $png ) {
				$jpg = preg_replace( '/\.png$/', '.jpg', $saida );
				$fundo = imagecreatetruecolor( imagesx( $png ), imagesy( $png ) );
				imagefill( $fundo, 0, 0, imagecolorallocate( $fundo, 12, 30, 74 ) );
				imagecopy( $fundo, $png, 0, 0, 0, 0, imagesx( $png ), imagesy( $png ) );
				if ( imagejpeg( $fundo, $jpg, 92 ) ) { @unlink( $saida ); $saida = $jpg; }
				imagedestroy( $fundo ); imagedestroy( $png );
			}
		}
		$artes[ $f ] = trailingslashit( $up['baseurl'] ) . 'denuncias/' . basename( $saida );
		$artes[ $f . '_arq' ] = $saida;
	}

	// site: a capa é a própria foto, sem moldura e sem degradê (Israel, 15/11/2026)
	// NUNCA a arte com moldura no site (Israel, 15/11/2026 21h16). Só vídeo: a capa é um quadro do vídeo, cortado na horizontal.
	$capa_id = 0;
	if ( $md['foto'] ) {
		$capa_id = $md['foto'];
	} elseif ( $md['video'] ) {
		$capa_id = (int) get_post_meta( $id, 'dc_capa_video', true );
		if ( ! $capa_id || ! get_post( $capa_id ) ) $capa_id = po_denuncia_quadro_servidor( $id, $md['video'] );
	}
	update_post_meta( $id, 'dc_capa_pendente', $capa_id ? 0 : 1 ); // sem quadro ainda: o robô tira o print do vídeo e manda

	$quem = $anon ? 'Denúncia anônima' : 'Enviada por ' . $nome;
	$html = po_denuncia_html_site( $id );

	$cats = array();
	$c_den = get_category_by_slug( 'denuncias' );
	if ( $c_den ) { $cats[] = $c_den->term_id; }
	else { $t = wp_insert_term( 'Denúncias', 'category', array( 'slug' => 'denuncias' ) ); if ( ! is_wp_error( $t ) ) $cats[] = $t['term_id']; }
	if ( 'luz' === $portal && ( $c_sl = get_category_by_slug( 'santa-luzia' ) ) ) $cats[] = $c_sl->term_id;

	$mat = (int) get_post_meta( $id, 'dc_post_id', true );
	$dados = array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $titulo, 'post_content' => $html, 'post_category' => $cats );
	if ( $mat && get_post( $mat ) ) { $dados['ID'] = $mat; wp_update_post( $dados ); }
	else { $mat = wp_insert_post( $dados ); }
	if ( ! $mat || is_wp_error( $mat ) ) return new WP_Error( 'materia', 'não consegui publicar a matéria no site.' );
	if ( $capa_id ) set_post_thumbnail( $mat, $capa_id );
	else delete_post_thumbnail( $mat ); // nada de arte com moldura enquanto o print do vídeo não chega
	update_post_meta( $mat, 'po_denuncia_id', $id );
	update_post_meta( $mat, 'po_linha_fina', 'Denúncia · Protocolo ' . $prot . ' · ' . $quem );

	update_post_meta( $id, 'dc_post_id', $mat );
	update_post_meta( $id, 'dc_artes', array( 'feed' => $artes['feed'], 'reels' => $artes['reels'], 'site' => $artes['site'] ) );
	update_post_meta( $id, 'dc_aprovada', current_time( 'mysql' ) );
	if ( 'postado' !== get_post_meta( $id, 'dc_insta_status', true ) ) update_post_meta( $id, 'dc_insta_status', 'pendente' );
	return $mat;
}

/* ---------- lista para o robô postar no Instagram ----------
 * GET  /wp-json/portalone/v1/denuncias?chave=XXX          -> denúncias aprovadas que ainda não foram pro Instagram
 * POST /wp-json/portalone/v1/denuncias/123/postada?chave=XXX -> marca como postada */
/* ---------- capa do site quando a denúncia só tem vídeo: print do vídeo cortado na horizontal (16:9), sem moldura ---------- */
// recorta qualquer imagem no meio para 1280x720 e guarda como capa da denúncia (e da matéria, se já existir)
function po_denuncia_salvar_capa( $id, $origem ) {
	$info = @getimagesize( $origem );
	if ( ! $info ) return 0;
	$src = 'image/png' === $info['mime'] ? @imagecreatefrompng( $origem ) : ( 'image/webp' === $info['mime'] && function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $origem ) : @imagecreatefromjpeg( $origem ) );
	if ( ! $src ) return 0;
	$w = imagesx( $src ); $h = imagesy( $src );
	if ( $w / $h > 16 / 9 ) { $cw = (int) round( $h * 16 / 9 ); $ch = $h; } else { $cw = $w; $ch = (int) round( $w * 9 / 16 ); }
	$x = (int) ( ( $w - $cw ) / 2 ); $y = (int) ( ( $h - $ch ) / 2 );
	$dst = imagecreatetruecolor( 1280, 720 );
	imagecopyresampled( $dst, $src, 0, 0, $x, $y, 1280, 720, $cw, $ch );
	$up = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'denuncias';
	wp_mkdir_p( $dir );
	$prot = get_post_meta( $id, 'dc_protocolo', true );
	$arq = $dir . '/' . sanitize_file_name( strtolower( $prot ? $prot : 'denuncia-' . $id ) ) . '-capa-' . time() . '.jpg';
	$ok = imagejpeg( $dst, $arq, 88 );
	imagedestroy( $dst ); imagedestroy( $src );
	if ( ! $ok ) return 0;
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$cid = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg', 'post_title' => 'Denúncia ' . $prot, 'post_status' => 'inherit' ), $arq );
	if ( ! $cid || is_wp_error( $cid ) ) return 0;
	wp_update_attachment_metadata( $cid, wp_generate_attachment_metadata( $cid, $arq ) );
	update_post_meta( $id, 'dc_capa_video', $cid );
	update_post_meta( $id, 'dc_capa_pendente', 0 );
	$mat = (int) get_post_meta( $id, 'dc_post_id', true );
	if ( $mat && get_post( $mat ) ) set_post_thumbnail( $mat, $cid );
	return $cid;
}
// tenta tirar o print direto no servidor (só funciona se a hospedagem tiver ffmpeg); se não der, o robô faz
function po_denuncia_quadro_servidor( $id, $video_id ) {
	$video = get_attached_file( $video_id );
	if ( ! $video || ! file_exists( $video ) || ! function_exists( 'exec' ) ) return 0;
	$off = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
	if ( in_array( 'exec', $off, true ) ) return 0;
	$tmp = trailingslashit( get_temp_dir() ) . 'den-' . $id . '-' . time() . '.jpg';
	foreach ( array( '1', '0' ) as $seg ) {
		@exec( 'ffmpeg -y -loglevel error -ss ' . $seg . ' -i ' . escapeshellarg( $video ) . ' -frames:v 1 -q:v 2 ' . escapeshellarg( $tmp ) . ' 2>&1', $saida, $cod );
		if ( 0 === $cod && file_exists( $tmp ) && filesize( $tmp ) > 0 ) break;
	}
	if ( ! file_exists( $tmp ) ) return 0;
	$cid = po_denuncia_salvar_capa( $id, $tmp );
	@unlink( $tmp );
	return $cid;
}
add_action( 'init', function () { if ( get_option( 'po_den_cache_v1' ) ) return; update_option( 'po_den_cache_v1', 1 ); do_action( 'litespeed_purge_all' ); }, 101 );
// uma vez só: tira a arte com moldura da capa das matérias de denúncia já publicadas que só tinham vídeo
add_action( 'init', function () {
	if ( get_option( 'po_den_site_v3' ) ) return;
	update_option( 'po_den_site_v3', 1 );
	foreach ( get_posts( array( 'post_type' => 'po_denuncia', 'post_status' => 'any', 'posts_per_page' => 200, 'meta_key' => 'dc_post_id' ) ) as $d ) {
		$mat = (int) get_post_meta( $d->ID, 'dc_post_id', true );
		if ( ! $mat || ! get_post( $mat ) ) continue;
		$md = po_denuncia_midias( $d->ID );
		if ( $md['foto'] ) { set_post_thumbnail( $mat, $md['foto'] ); continue; }
		if ( ! $md['video'] ) continue;
		$cid = (int) get_post_meta( $d->ID, 'dc_capa_video', true );
		if ( $cid && get_post( $cid ) ) { set_post_thumbnail( $mat, $cid ); continue; }
		delete_post_thumbnail( $mat );
		update_post_meta( $d->ID, 'dc_capa_pendente', 1 );
		po_denuncia_quadro_servidor( $d->ID, $md['video'] );
	}
}, 100 );

/* capa trocada (ou matéria de denúncia atualizada): limpa o cache do LiteSpeed na hora, senão o leitor continua vendo a imagem antiga */
function po_limpar_cache_post( $post_id ) {
	do_action( 'litespeed_purge_post', $post_id );
	do_action( 'litespeed_purge_url', get_permalink( $post_id ) );
	do_action( 'litespeed_purge_all' ); // a capa também aparece na home e nas listas
}
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $gancho ) {
	add_action( $gancho, function ( $mid, $post_id, $chave ) {
		if ( '_thumbnail_id' === $chave && get_post_meta( $post_id, 'po_denuncia_id', true ) ) po_limpar_cache_post( $post_id );
	}, 10, 3 );
}

function po_denuncia_chave() {
	$k = get_option( 'po_denuncia_chave' );
	if ( ! $k ) { $k = wp_generate_password( 24, false ); update_option( 'po_denuncia_chave', $k ); }
	return $k;
}
add_action( 'rest_api_init', function () {
	$ok = function ( $req ) { return hash_equals( po_denuncia_chave(), (string) $req->get_param( 'chave' ) ); };
	register_rest_route( 'portalone/v1', '/denuncias', array( 'methods' => 'GET', 'permission_callback' => $ok, 'callback' => function ( $req ) {
		$todas = (bool) $req->get_param( 'todas' );
		$q = get_posts( array( 'post_type' => 'po_denuncia', 'post_status' => 'any', 'posts_per_page' => 50, 'meta_key' => 'dc_aprovada', 'orderby' => 'meta_value', 'order' => 'ASC' ) );
		$out = array();
		foreach ( $q as $p ) {
			$st = get_post_meta( $p->ID, 'dc_insta_status', true );
			if ( ! $todas && 'postado' === $st ) continue;
			// trava: quem pegou a denúncia tem 30 min pra postar; outro robô não recebe a mesma (evita post repetido)
			if ( ! $todas ) {
				$res = (int) get_post_meta( $p->ID, 'dc_insta_reserva', true );
				if ( $res && $res > time() - 1800 ) continue;
				update_post_meta( $p->ID, 'dc_insta_reserva', time() );
			}
			$md = po_denuncia_midias( $p->ID );
			$anon = 'credito' !== get_post_meta( $p->ID, 'dc_identidade', true ) || ! get_post_meta( $p->ID, 'dc_nome', true );
			$art = (array) get_post_meta( $p->ID, 'dc_artes', true );
			$mat = (int) get_post_meta( $p->ID, 'dc_post_id', true );
			$out[] = array(
				'id' => $p->ID, 'protocolo' => get_post_meta( $p->ID, 'dc_protocolo', true ),
				'titulo' => get_post_meta( $p->ID, 'dc_titulo_pub', true ), 'editoria' => get_post_meta( $p->ID, 'dc_cat_pub', true ),
				'moldura' => get_post_meta( $p->ID, 'dc_portal_pub', true ) === 'one' ? 'Portal ONE News' : 'Portal Luziense',
				'anonima' => $anon, 'nome' => $anon ? '' : get_post_meta( $p->ID, 'dc_nome', true ),
				'local' => get_post_meta( $p->ID, 'dc_local', true ), 'relato' => $p->post_content,
				'instagram_formato' => $md['video'] ? 'reels' : 'feed',
				'midia_tipo' => $md['video'] ? 'video' : 'foto',
				'midia_url' => $md['video'] ? wp_get_attachment_url( $md['video'] ) : ( $md['foto'] ? wp_get_attachment_url( $md['foto'] ) : '' ),
				'foto_url' => $md['foto'] ? wp_get_attachment_url( $md['foto'] ) : '',
				'video_url' => $md['video'] ? wp_get_attachment_url( $md['video'] ) : '',
				'arte_feed' => isset( $art['feed'] ) ? $art['feed'] : '', 'arte_reels' => isset( $art['reels'] ) ? $art['reels'] : '', 'capa_site' => isset( $art['site'] ) ? $art['site'] : '',
				'materia_url' => $mat ? get_permalink( $mat ) : '', 'instagram' => $st ? $st : 'pendente',
			);
		}
		return $out;
	} ) );
	register_rest_route( 'portalone/v1', '/denuncias/para-titular', array( 'methods' => 'GET', 'permission_callback' => $ok, 'callback' => function () {
		$q = get_posts( array( 'post_type' => 'po_denuncia', 'post_status' => 'any', 'posts_per_page' => 30, 'meta_key' => 'dc_liberada', 'orderby' => 'meta_value', 'order' => 'ASC' ) );
		$out = array();
		foreach ( $q as $p ) {
			if ( get_post_meta( $p->ID, 'dc_aprovada', true ) ) continue;
			$res = (int) get_post_meta( $p->ID, 'dc_titulo_reserva', true );
			if ( $res && $res > time() - 600 ) continue; // outro robô já está escrevendo o título
			update_post_meta( $p->ID, 'dc_titulo_reserva', time() );
			$md = po_denuncia_midias( $p->ID );
			$anon = 'credito' !== get_post_meta( $p->ID, 'dc_identidade', true ) || ! get_post_meta( $p->ID, 'dc_nome', true );
			$out[] = array(
				'id' => $p->ID, 'protocolo' => get_post_meta( $p->ID, 'dc_protocolo', true ), 'local' => get_post_meta( $p->ID, 'dc_local', true ),
				'relato' => $p->post_content, 'anonima' => $anon, 'nome' => $anon ? '' : get_post_meta( $p->ID, 'dc_nome', true ),
				'foto_url' => $md['foto'] ? wp_get_attachment_url( $md['foto'] ) : '', 'video_url' => $md['video'] ? wp_get_attachment_url( $md['video'] ) : '',
			);
		}
		return $out;
	} ) );
	register_rest_route( 'portalone/v1', '/denuncias/(?P<id>\d+)/titulo', array( 'methods' => 'POST', 'permission_callback' => $ok, 'callback' => function ( $req ) {
		$id = (int) $req['id'];
		if ( 'po_denuncia' !== get_post_type( $id ) || ! get_post_meta( $id, 'dc_liberada', true ) ) return new WP_Error( 'nao', 'denúncia não aprovada pelo Israel', array( 'status' => 403 ) );
		if ( get_post_meta( $id, 'dc_aprovada', true ) ) return array( 'ok' => true, 'ja_publicada' => true );
		$t = sanitize_text_field( (string) $req->get_param( 'titulo' ) );
		if ( '' === $t ) return new WP_Error( 'titulo', 'mande o titulo', array( 'status' => 400 ) );
		update_post_meta( $id, 'dc_titulo_pub', function_exists( 'mb_substr' ) ? mb_substr( $t, 0, 120 ) : substr( $t, 0, 120 ) );
		$ed = sanitize_text_field( (string) $req->get_param( 'editoria' ) );
		if ( $ed ) update_post_meta( $id, 'dc_cat_pub', $ed );
		$mo = $req->get_param( 'moldura' );
		if ( $mo ) update_post_meta( $id, 'dc_portal_pub', 'one' === $mo ? 'one' : 'luz' );
		try { $r = po_denuncia_publicar( $id ); } catch ( Throwable $e ) { return new WP_Error( 'falhou', 'erro no site: ' . $e->getMessage() . ' (' . basename( $e->getFile() ) . ':' . $e->getLine() . ')', array( 'status' => 500 ) ); }
		if ( is_wp_error( $r ) ) return new WP_Error( 'falhou', $r->get_error_message(), array( 'status' => 400 ) );
		return array( 'ok' => true, 'materia_url' => get_permalink( $r ) );
	} ) );
	register_rest_route( 'portalone/v1', '/denuncias/(?P<id>\d+)/postada', array( 'methods' => 'POST', 'permission_callback' => $ok, 'callback' => function ( $req ) {
		$id = (int) $req['id'];
		if ( 'po_denuncia' !== get_post_type( $id ) ) return new WP_Error( 'nao', 'denúncia não encontrada', array( 'status' => 404 ) );
		update_post_meta( $id, 'dc_insta_status', 'postado' );
		update_post_meta( $id, 'dc_insta_em', current_time( 'mysql' ) );
		return array( 'ok' => true );
	} ) );
	// denúncias publicadas que só têm vídeo e ainda estão sem capa no site: o robô tira o print e manda
	register_rest_route( 'portalone/v1', '/denuncias/sem-capa', array( 'methods' => 'GET', 'permission_callback' => $ok, 'callback' => function () {
		$out = array();
		foreach ( get_posts( array( 'post_type' => 'po_denuncia', 'post_status' => 'any', 'posts_per_page' => 30, 'meta_key' => 'dc_capa_pendente', 'meta_value' => '1' ) ) as $p ) {
			$md = po_denuncia_midias( $p->ID );
			if ( ! $md['video'] || $md['foto'] ) { update_post_meta( $p->ID, 'dc_capa_pendente', 0 ); continue; }
			$mat = (int) get_post_meta( $p->ID, 'dc_post_id', true );
			$out[] = array( 'id' => $p->ID, 'protocolo' => get_post_meta( $p->ID, 'dc_protocolo', true ), 'video_url' => wp_get_attachment_url( $md['video'] ), 'materia_url' => $mat ? get_permalink( $mat ) : '' );
		}
		return $out;
	} ) );
	// recebe o print do vídeo (corpo da requisição = a imagem JPG); o site corta em 16:9 e põe de capa na matéria
	register_rest_route( 'portalone/v1', '/denuncias/(?P<id>\d+)/capa', array( 'methods' => 'POST', 'permission_callback' => $ok, 'callback' => function ( $req ) {
		$id = (int) $req['id'];
		if ( 'po_denuncia' !== get_post_type( $id ) ) return new WP_Error( 'nao', 'denúncia não encontrada', array( 'status' => 404 ) );
		$corpo = $req->get_body();
		$arqs = $req->get_file_params();
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = wp_tempnam( 'den-capa' );
		if ( ! empty( $arqs['imagem']['tmp_name'] ) ) copy( $arqs['imagem']['tmp_name'], $tmp );
		elseif ( $corpo ) file_put_contents( $tmp, $corpo );
		else return new WP_Error( 'vazio', 'mande a imagem no corpo da requisição', array( 'status' => 400 ) );
		$cid = po_denuncia_salvar_capa( $id, $tmp );
		@unlink( $tmp );
		if ( ! $cid ) return new WP_Error( 'imagem', 'não consegui ler a imagem (mande JPG ou PNG)', array( 'status' => 400 ) );
		return array( 'ok' => true, 'capa' => wp_get_attachment_url( $cid ) );
	} ) );
} );
function po_denuncia_metabox_detalhes( $post ) {
	$protocolo  = get_post_meta( $post->ID, 'dc_protocolo', true );
	$local      = get_post_meta( $post->ID, 'dc_local', true );
	$identidade = get_post_meta( $post->ID, 'dc_identidade', true );
	$nome       = get_post_meta( $post->ID, 'dc_nome', true );
	$insta      = get_post_meta( $post->ID, 'dc_insta', true );
	$relato     = $post->post_content;
	$anexos     = $post->ID ? get_attached_media( '', $post->ID ) : array();
	?>
	<style>
		.dc-box{font-size:14px;line-height:1.6;color:#1d2327}
		.dc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:18px}
		.dc-campo b{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:3px;font-weight:600}
		.dc-relato{background:#f6f7f9;border:1px solid #e2e4e9;border-radius:8px;padding:14px 16px;white-space:pre-wrap;margin:6px 0 20px;max-width:640px}
		.dc-midia h3{font-size:14px;margin:0 0 10px}
		.dc-anexos{display:flex;flex-wrap:wrap;gap:14px}
		.dc-anexo{border:1px solid #e2e4e9;border-radius:8px;padding:10px;background:#fff}
		.dc-anexo img{max-width:220px;max-height:220px;display:block;border-radius:5px}
		.dc-anexo video{max-width:300px;display:block;border-radius:5px}
		.dc-anexo audio{width:300px}
	</style>
	<div class="dc-box">
		<?php
		// guarda o texto original do leitor na primeira vez que o Israel editar (nunca se perde)
		$original = get_post_meta( $post->ID, 'dc_relato_original', true );
		?>
		<div class="dc-grid">
			<div class="dc-campo"><b>Protocolo</b><?php echo esc_html( $protocolo ?: '—' ); ?></div>
			<div class="dc-campo"><b>Recebido em</b><?php echo $post->ID ? esc_html( get_the_date( 'd/m/Y \à\s H:i', $post ) ) : '—'; ?></div>
		</div>

		<div class="dc-edit">
			<label class="dc-campo"><b>Onde (bairro / cidade)</b>
				<input type="text" name="dc_ed_local" value="<?php echo esc_attr( $local ); ?>" placeholder="Ex.: Bairro Imperial, Santa Luzia">
			</label>
			<label class="dc-campo"><b>Quem mandou</b>
				<select name="dc_ed_identidade" id="dc_ed_identidade">
					<option value="anonimo" <?php selected( 'credito' !== $identidade ); ?>>Denúncia anônima</option>
					<option value="credito" <?php selected( 'credito', $identidade ); ?>>Com o nome da pessoa</option>
				</select>
			</label>
			<label class="dc-campo dc-so-nome"><b>Nome</b>
				<input type="text" name="dc_ed_nome" value="<?php echo esc_attr( $nome ); ?>" placeholder="Nome que aparece na arte e na matéria">
			</label>
			<label class="dc-campo dc-so-nome"><b>Instagram da pessoa</b>
				<input type="text" name="dc_ed_insta" value="<?php echo esc_attr( $insta ); ?>" placeholder="@usuario (opcional)">
			</label>
		</div>

		<label class="dc-campo" style="display:block"><b>Relato (texto da denúncia)</b></label>
		<?php if ( ! trim( $relato ) ) : ?>
			<p style="margin:0 0 6px;color:#996800"><b>O leitor não mandou texto.</b> Escreva aqui o relato — ele vai para a matéria, para a legenda do Instagram e o robô usa para criar o título.</p>
		<?php endif; ?>
		<textarea name="dc_ed_relato" rows="8" class="dc-relato-ed" placeholder="Escreva aqui o que aconteceu, onde e quando."><?php echo esc_textarea( $relato ); ?></textarea>
		<?php if ( $original && $original !== $relato ) : ?>
			<details style="margin:-12px 0 20px;max-width:640px"><summary style="cursor:pointer;color:#646970">Ver o texto original que o leitor mandou</summary>
				<div class="dc-relato" style="margin-top:8px"><?php echo esc_html( $original ); ?></div>
			</details>
		<?php endif; ?>
		<p style="margin:-10px 0 20px;color:#646970;font-size:12px">Depois de mudar, clique em <b>Atualizar</b> (botão azul no topo/lateral). Se a denúncia já foi publicada, a matéria do site é atualizada na hora. O que já foi postado no Instagram não muda.</p>
		<style>
			.dc-edit{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:18px;max-width:900px}
			.dc-edit input,.dc-edit select{width:100%;font-size:16px}
			.dc-relato-ed{width:100%;max-width:640px;font-size:16px;line-height:1.6;border-radius:8px;padding:12px 14px;margin:0 0 20px}
		</style>
		<script>
		(function () {
			var s = document.getElementById('dc_ed_identidade');
			function v() { document.querySelectorAll('.dc-so-nome').forEach(function (e) { e.style.display = s.value === 'credito' ? '' : 'none'; }); }
			s.addEventListener('change', v); v();
			document.querySelectorAll('[name^="dc_ed_"]').forEach(function (e) { e.addEventListener('input', function () { window.dcSujo = true; }); });
		})();
		</script>

		<div class="dc-midia">
			<h3>Anexos<?php echo $anexos ? ' (' . count( $anexos ) . ')' : ''; ?></h3>
			<?php if ( ! $anexos ) : ?>
				<p style="color:#6b7280">Nenhum arquivo enviado com essa denúncia.</p>
			<?php else : ?>
				<div class="dc-anexos">
					<?php foreach ( $anexos as $a ) :
						$url  = wp_get_attachment_url( $a->ID );
						$mime = get_post_mime_type( $a->ID );
						if ( ! $url ) continue;
						?>
						<div class="dc-anexo">
							<?php if ( 0 === strpos( $mime, 'image/' ) ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $url ); ?>" alt=""></a>
							<?php elseif ( 0 === strpos( $mime, 'video/' ) ) : ?>
								<video controls><source src="<?php echo esc_url( $url ); ?>" type="<?php echo esc_attr( $mime ); ?>"></video>
							<?php elseif ( 0 === strpos( $mime, 'audio/' ) ) : ?>
								<audio controls><source src="<?php echo esc_url( $url ); ?>" type="<?php echo esc_attr( $mime ); ?>"></audio>
							<?php else : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( basename( $url ) ); ?></a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/* aceita os formatos que normalmente chegam de celular (foto HEIC do iPhone, áudio/vídeo gravado no navegador) */
add_filter( 'upload_mimes', function ( $mimes ) {
	$mimes['webm'] = 'video/webm';
	$mimes['heic'] = 'image/heic';
	$mimes['heif'] = 'image/heif';
	$mimes['mov']  = 'video/quicktime';
	return $mimes;
} );

/* colunas da lista, pra bater o olho e já entender do que se trata */
add_filter( 'manage_po_denuncia_posts_columns', function ( $c ) {
	return array( 'cb' => $c['cb'], 'title' => 'Protocolo', 'dc_situacao' => 'Situação', 'dc_resumo' => 'Relato', 'dc_local' => 'Onde', 'dc_quem' => 'Quem mandou', 'dc_midia' => 'Anexos', 'date' => 'Recebido em' );
} );
add_action( 'manage_po_denuncia_posts_custom_column', function ( $col, $id ) {
	if ( 'dc_resumo' === $col ) {
		$t = get_post_field( 'post_content', $id );
		echo esc_html( mb_strlen( $t ) > 110 ? mb_substr( $t, 0, 110 ) . '…' : $t );
	}
	if ( 'dc_situacao' === $col ) {
		if ( get_post_meta( $id, 'dc_aprovada', true ) ) echo '<b style="color:#1e7e34">✅ Concluída</b><br><small>' . ( 'postado' === get_post_meta( $id, 'dc_insta_status', true ) ? 'Instagram: postado' : 'Instagram: aguardando o robô' ) . '</small>';
		elseif ( get_post_meta( $id, 'dc_liberada', true ) ) echo '<b style="color:#135e96">✅ Aprovada</b><br><small>robô publicando</small>';
		else { $m = po_denuncia_midias( $id ); echo ( $m['foto'] || $m['video'] ) ? '<b style="color:#996800">⏳ Aguardando sua aprovação</b>' : '<b style="color:#b32d2e">Falta foto ou vídeo</b>'; }
	}
	if ( 'dc_local' === $col ) echo esc_html( get_post_meta( $id, 'dc_local', true ) ?: '—' );
	if ( 'dc_quem' === $col ) {
		$identidade = get_post_meta( $id, 'dc_identidade', true );
		if ( 'credito' === $identidade ) {
			$nome = get_post_meta( $id, 'dc_nome', true ); $insta = get_post_meta( $id, 'dc_insta', true );
			echo '<strong>' . esc_html( $nome ?: 'Sem nome' ) . '</strong>' . ( $insta ? ' · ' . esc_html( $insta ) : '' );
		} else { echo '<em>Anônimo</em>'; }
	}
	if ( 'dc_midia' === $col ) {
		$anexos = get_attached_media( '', $id );
		echo $anexos ? count( $anexos ) . ' arquivo(s)' : '—';
	}
}, 10, 2 );

/* e-mail de aviso: uma aba de configuração simples, dentro do próprio menu de Denúncias */
add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=po_denuncia', 'Configurações', 'Configurações', 'manage_options', 'po-denuncia-config', 'po_denuncia_pagina_config' );
} );
function po_denuncia_pagina_config() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( isset( $_POST['po_denuncia_email_nonce'] ) && wp_verify_nonce( $_POST['po_denuncia_email_nonce'], 'po_denuncia_email' ) ) {
		update_option( 'po_denuncia_email', sanitize_email( $_POST['po_denuncia_email'] ) );
		echo '<div class="updated"><p>E-mail salvo.</p></div>';
	}
	$email = get_option( 'po_denuncia_email', get_option( 'admin_email' ) );
	?>
	<div class="wrap">
		<h1>Configurações — Denúncias</h1>
		<p>Toda vez que alguém mandar uma denúncia ou sugestão de pauta pela página do site, um aviso é enviado para este e-mail.</p>
		<form method="post">
			<?php wp_nonce_field( 'po_denuncia_email', 'po_denuncia_email_nonce' ); ?>
			<table class="form-table"><tr>
				<th><label for="po_denuncia_email">E-mail de aviso</label></th>
				<td><input type="email" id="po_denuncia_email" name="po_denuncia_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" required></td>
			</tr></table>
			<?php submit_button( 'Salvar' ); ?>
		</form>
		<h2 style="margin-top:30px">Ligação com o robô (Instagram)</h2>
		<p>O robô lê as denúncias aprovadas neste endereço. Não compartilhe a chave com ninguém além do robô.</p>
		<p><b>Endereço:</b><br><code style="user-select:all"><?php echo esc_html( rest_url( 'portalone/v1/denuncias' ) . '?chave=' . po_denuncia_chave() ); ?></code></p>
	</div>
	<?php
}

/* salva o arquivo enviado (foto, vídeo ou áudio gravado) como anexo da denúncia */
function po_denuncia_salvar_anexo( $tmp_name, $nome_arquivo, $post_id ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$conteudo = @file_get_contents( $tmp_name );
	if ( false === $conteudo ) return 0;
	$bits = wp_upload_bits( sanitize_file_name( $nome_arquivo ), null, $conteudo );
	if ( ! empty( $bits['error'] ) ) return 0;
	$tipo = wp_check_filetype( $bits['file'] );
	$anexo_id = wp_insert_attachment( array(
		'post_mime_type' => $tipo['type'] ?: 'application/octet-stream',
		'post_title'     => sanitize_file_name( $nome_arquivo ),
		'post_status'    => 'inherit',
	), $bits['file'], $post_id );
	if ( ! is_wp_error( $anexo_id ) && $anexo_id ) {
		wp_update_attachment_metadata( $anexo_id, wp_generate_attachment_metadata( $anexo_id, $bits['file'] ) );
	}
	return $anexo_id;
}

/* recebe o envio da página pública (visitante não precisa estar logado) */
add_action( 'wp_ajax_nopriv_po_denuncia_submit', 'po_denuncia_receber' );
add_action( 'wp_ajax_po_denuncia_submit', 'po_denuncia_receber' );
function po_denuncia_receber() {
	check_ajax_referer( 'po_denuncia', 'nonce' );

	/* campo-isca: só bot preenche isso, gente de verdade nunca vê esse campo */
	if ( ! empty( $_POST['site_web'] ) ) {
		wp_send_json_success( array( 'protocolo' => 'OC-' . wp_date( 'Y' ) . '-0000' ) );
	}

	$relato = isset( $_POST['relato'] ) ? sanitize_textarea_field( wp_unslash( $_POST['relato'] ) ) : '';
	$local  = isset( $_POST['local'] ) ? sanitize_text_field( wp_unslash( $_POST['local'] ) ) : '';
	$identidade = ( isset( $_POST['identidade'] ) && 'credito' === $_POST['identidade'] ) ? 'credito' : 'anonimo';
	$nome  = sanitize_text_field( wp_unslash( $_POST['nome'] ?? '' ) );
	$insta = sanitize_text_field( wp_unslash( $_POST['insta'] ?? '' ) );

	if ( '' === $relato && empty( $_FILES['midia']['name'][0] ) ) {
		wp_send_json_error( array( 'mensagem' => 'Escreva (ou fale) o relato, ou envie uma foto/vídeo antes de enviar.' ) );
	}

	$autores = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	$autor_id = $autores ? $autores[0] : 1;

	$titulo = 'Denúncia' . ( $local ? ' · ' . $local : '' ) . ' · ' . wp_date( 'd/m/Y H:i' );
	$post_id = wp_insert_post( array(
		'post_type'    => 'po_denuncia',
		'post_status'  => 'publish',
		'post_title'   => $titulo,
		'post_content' => $relato,
		'post_author'  => $autor_id,
	), true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'mensagem' => 'Não deu pra salvar agora, tenta de novo em alguns minutos.' ) );
	}

	update_post_meta( $post_id, 'dc_local', $local );
	update_post_meta( $post_id, 'dc_identidade', $identidade );
	if ( 'credito' === $identidade ) {
		update_post_meta( $post_id, 'dc_nome', $nome );
		update_post_meta( $post_id, 'dc_insta', $insta );
	}

	$total_anexos = 0;
	if ( ! empty( $_FILES['midia']['name'][0] ) ) {
		foreach ( $_FILES['midia']['name'] as $i => $nome_arquivo ) {
			if ( UPLOAD_ERR_OK !== $_FILES['midia']['error'][ $i ] ) continue;
			$anexo_id = po_denuncia_salvar_anexo( $_FILES['midia']['tmp_name'][ $i ], $nome_arquivo, $post_id );
			if ( $anexo_id && 0 === $total_anexos && wp_attachment_is_image( $anexo_id ) ) set_post_thumbnail( $post_id, $anexo_id );
			if ( $anexo_id ) $total_anexos++;
		}
	}
	$protocolo = 'OC-' . wp_date( 'Y' ) . '-' . str_pad( $post_id, 4, '0', STR_PAD_LEFT );
	update_post_meta( $post_id, 'dc_protocolo', $protocolo );

	$para = get_option( 'po_denuncia_email', get_option( 'admin_email' ) );
	if ( $para ) {
		$quem = 'credito' === $identidade ? ( $nome ?: 'Se identificou, sem nome' ) . ( $insta ? " ({$insta})" : '' ) : 'Anônimo';
		$corpo  = "Chegou uma denúncia/pauta pelo site.\n\n";
		$corpo .= "Protocolo: {$protocolo}\n";
		$corpo .= "Onde: " . ( $local ?: '—' ) . "\n";
		$corpo .= "Quem mandou: {$quem}\n";
		$corpo .= "Anexos: {$total_anexos}\n\n";
		$corpo .= "Relato:\n" . ( $relato ?: '(sem texto — ver anexos)' ) . "\n\n";
		$corpo .= "Ver no painel: " . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . "\n";
		wp_mail( $para, '[Portal ONE News] Nova denúncia recebida · ' . $protocolo, $corpo );
	}

	wp_send_json_success( array( 'protocolo' => $protocolo ) );
}

/* Painel Instagram — atalho no admin pro painel de métricas do Instagram (Portal ONE News) */
add_action( 'admin_menu', 'po_instagram_painel_menu' );
function po_instagram_painel_menu() {
	add_menu_page(
		'Instagram',
		'Instagram',
		'edit_posts',
		'po-instagram-painel',
		'po_instagram_painel_pagina',
		'dashicons-chart-area',
		5
	);
}

function po_instagram_painel_pagina() {
	$link = 'https://claude.ai/artifact/SEyiRS3tf7PtL4GBJisKzv';
	?>
	<div class="wrap" style="max-width:720px">
		<h1>Painel do Instagram</h1>
		<p>Alcance, seguidores, idade e cidade do público, tudo puxado direto da conta do Instagram do Portal ONE News. Abre numa aba separada e atualiza toda vez que você abre.</p>
		<p>
			<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" class="button button-primary button-hero">
				Abrir painel do Instagram
			</a>
		</p>
		<p style="color:#666">Dica: pode deixar essa aba favoritada no navegador do celular ou do computador pra abrir rapidinho todo dia.</p>
	</div>
	<?php
}

/* ---------- Placar em tempo real (Atlético-MG, Cruzeiro, América-MG) ----------
   Busca os jogos de hoje numa fonte pública e gratuita (ESPN) e serve pronto
   pro site, já filtrado pelos 3 times. Guarda em cache 20s pra não bater na
   fonte toda hora, mesmo com muita gente vendo ao mesmo tempo. Custo: R$ 0. */

function po_placar_times() {
	return array(
		'7632' => array( 'nome' => 'Atlético-MG', 'escudo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/7632.png', 'categoria' => 'atletico' ),
		'2022' => array( 'nome' => 'Cruzeiro', 'escudo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/2022.png', 'categoria' => 'cruzeiro' ),
		'6154' => array( 'nome' => 'América-MG', 'escudo' => 'https://a.espncdn.com/i/teamlogos/soccer/500/6154.png', 'categoria' => 'america' ),
	);
}

function po_placar_competicoes() {
	return array(
		'bra.1'               => 'Brasileirão Série A',
		'bra.2'                => 'Brasileirão Série B',
		'bra.copa_do_brazil'   => 'Copa do Brasil',
	);
}

function po_placar_busca_remota( $url ) {
	$r = wp_remote_get( $url, array(
		'timeout'    => 8,
		'user-agent' => 'Mozilla/5.0 (compatible; PortalONENews/1.0; +https://www.portalonenews.com.br)',
	) );
	if ( is_wp_error( $r ) || wp_remote_retrieve_response_code( $r ) !== 200 ) return null;
	$d = json_decode( wp_remote_retrieve_body( $r ), true );
	return is_array( $d ) ? $d : null;
}

add_action( 'wp_ajax_po_placar_dados', 'po_placar_dados' );
add_action( 'wp_ajax_nopriv_po_placar_dados', 'po_placar_dados' );
function po_placar_dados() {
	$cache = get_transient( 'po_placar_hoje' );
	if ( $cache !== false ) {
		wp_send_json_success( $cache );
	}

	$hoje  = wp_date( 'Ymd' );
	$times = po_placar_times();
	$jogos  = array(); // jogos do Atlético, Cruzeiro e América — card completo, com lances
	$outros = array(); // demais jogos do dia nas mesmas competições — só placar, pra preencher o espaço

	$situacoes_pt = array(
		'Scheduled'         => 'Agendado',
		'In Progress'       => 'Em andamento',
		'Halftime'          => 'Intervalo',
		'Full Time'         => 'Encerrado',
		'Postponed'         => 'Adiado',
		'Canceled'          => 'Cancelado',
		'Suspended'         => 'Suspenso',
		'Abandoned'         => 'Abandonado',
		'Extra Time'        => 'Prorrogação',
		'Penalty Shootout'  => 'Pênaltis',
	);

	foreach ( po_placar_competicoes() as $slug => $nome ) {
		$d = po_placar_busca_remota( 'https://site.api.espn.com/apis/site/v2/sports/soccer/' . $slug . '/scoreboard?dates=' . $hoje );
		if ( ! $d || empty( $d['events'] ) ) continue;

		foreach ( $d['events'] as $ev ) {
			$comp = isset( $ev['competitions'][0] ) ? $ev['competitions'][0] : null;
			if ( ! $comp ) continue;
			$time_acompanhado = null;
			foreach ( $comp['competitors'] as $c ) {
				$tid = (string) $c['team']['id'];
				if ( isset( $times[ $tid ] ) ) { $time_acompanhado = $times[ $tid ]; break; }
			}

			$casa = null; $fora = null;
			foreach ( $comp['competitors'] as $c ) {
				if ( $c['homeAway'] === 'home' ) $casa = $c; else $fora = $c;
			}
			$tipo = $comp['status']['type'];
			$situacao_pt = isset( $situacoes_pt[ $tipo['description'] ] ) ? $situacoes_pt[ $tipo['description'] ] : $tipo['description'];

			if ( $time_acompanhado ) {
				$jogos[] = array(
					'id'          => $comp['id'],
					'competicao'  => $nome,
					'competicao_slug' => $slug,
					'data'        => $ev['date'],
					'casa'        => $casa['team']['shortDisplayName'],
					'fora'        => $fora['team']['shortDisplayName'],
					'placar_casa' => $casa['score'],
					'placar_fora' => $fora['score'],
					'estado'      => $tipo['state'],
					'situacao'    => $situacao_pt,
					'relogio'     => isset( $comp['status']['displayClock'] ) ? $comp['status']['displayClock'] : '',
					'time_nome'      => $time_acompanhado['nome'],
					'time_escudo'    => $time_acompanhado['escudo'],
					'time_categoria' => $time_acompanhado['categoria'],
				);
			} else {
				$outros[] = array(
					'competicao'  => $nome,
					'data'        => $ev['date'],
					'casa'        => $casa['team']['shortDisplayName'],
					'fora'        => $fora['team']['shortDisplayName'],
					'placar_casa' => $casa['score'],
					'placar_fora' => $fora['score'],
					'estado'      => $tipo['state'],
					'situacao'    => $situacao_pt,
					'relogio'     => isset( $comp['status']['displayClock'] ) ? $comp['status']['displayClock'] : '',
				);
			}
		}
	}

	/* ao vivo primeiro, depois agendado, depois encerrado; limita pra não ficar enorme */
	$peso = array( 'in' => 0, 'pre' => 1, 'post' => 2 );
	usort( $outros, function ( $a, $b ) use ( $peso ) {
		return ( $peso[ $a['estado'] ] ?? 3 ) <=> ( $peso[ $b['estado'] ] ?? 3 );
	} );
	$outros = array_slice( $outros, 0, 10 );

	$resultado = array( 'times' => $jogos, 'outros' => $outros );
	set_transient( 'po_placar_hoje', $resultado, 20 );
	wp_send_json_success( $resultado );
}

add_action( 'wp_ajax_po_placar_lances', 'po_placar_lances' );
add_action( 'wp_ajax_nopriv_po_placar_lances', 'po_placar_lances' );
function po_placar_lances() {
	$comp = isset( $_GET['comp'] ) ? sanitize_text_field( wp_unslash( $_GET['comp'] ) ) : '';
	$id   = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
	$competicoes = po_placar_competicoes();
	if ( ! isset( $competicoes[ $comp ] ) || ! preg_match( '/^\d+$/', $id ) ) {
		wp_send_json_success( array() );
	}

	$chave = 'po_placar_lances_' . $comp . '_' . $id;
	$cache = get_transient( $chave );
	if ( $cache !== false ) {
		wp_send_json_success( $cache );
	}

	$d = po_placar_busca_remota( 'https://site.api.espn.com/apis/site/v2/sports/soccer/' . $comp . '/summary?event=' . $id );
	$lances = array();
	if ( $d && ! empty( $d['keyEvents'] ) ) {
		$tipos_pt = array(
			'goal'              => 'Gol',
			'own-goal'          => 'Gol contra',
			'penalty'           => 'Gol de pênalti',
			'missed-penalty'    => 'Pênalti perdido',
			'yellow-card'       => 'Cartão amarelo',
			'red-card'          => 'Cartão vermelho',
			'yellow-red-card'   => 'Segundo amarelo (vermelho)',
			'substitution-in'   => 'Substituição',
			'substitution'      => 'Substituição',
		);
		$eventos = array_slice( $d['keyEvents'], -8 );
		$eventos = array_reverse( $eventos );
		foreach ( $eventos as $ev ) {
			$chave_tipo = isset( $ev['type']['type'] ) ? $ev['type']['type'] : '';
			$tipo_pt    = isset( $tipos_pt[ $chave_tipo ] ) ? $tipos_pt[ $chave_tipo ] : ( isset( $ev['type']['text'] ) ? $ev['type']['text'] : '' );
			$jogador    = ! empty( $ev['participants'][0]['athlete']['displayName'] ) ? $ev['participants'][0]['athlete']['displayName'] : '';
			$time       = isset( $ev['team']['displayName'] ) ? $ev['team']['displayName'] : '';
			$texto      = trim( $jogador . ( $time ? ' (' . $time . ')' : '' ) . ' — ' . $tipo_pt, ' —' );
			$lances[]   = array(
				'minuto' => isset( $ev['clock']['displayValue'] ) ? $ev['clock']['displayValue'] : '',
				'texto'  => $texto,
			);
		}
	}

	set_transient( $chave, $lances, 15 );
	wp_send_json_success( $lances );
}
