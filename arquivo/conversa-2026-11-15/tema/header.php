<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0A1733">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if ( get_theme_mod( 'po_aviso' ) ) : ?><div class="aviso"><?php echo esc_html( get_theme_mod( 'po_aviso' ) ); ?></div><?php endif; ?>
<header class="cab">
  <div class="w">
    <button class="ico" type="button" id="abre-menu" aria-label="Abrir menu" aria-controls="gaveta" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    <a class="marca" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Portal ONE News, início"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/logo.png' ); ?>" alt="Portal ONE News" width="82" height="38"></a>
    <nav class="menu" aria-label="Editorias">
      <?php
      if ( has_nav_menu( 'principal' ) ) {
        wp_nav_menu( array( 'theme_location' => 'principal', 'container' => false, 'items_wrap' => '%3$s', 'depth' => 1, 'walker' => new PO_Menu_Walker() ) );
      } else {
        $itens = array(
          array( po_portal_url( 'luziense' ), 'Santa Luzia', '' ),
          array( po_link_cat( 'belo-horizonte' ), 'BH', '' ),
          array( po_link_cat( 'regiao-metropolitana' ), 'Região Metropolitana', '' ),
          array( po_link_cat( 'minas-gerais' ), 'Minas', '' ),
          array( po_portal_url( 'politica' ), 'Política', '' ),
          array( po_link_cat( 'brasil' ), 'Brasil', 'x' ),
          array( po_link_cat( 'mundo' ), 'Mundo', 'x' ),
          array( po_link_cat( 'esporte' ), 'Esporte', '' ),
          array( po_link_cat( 'educacao' ), 'Educação', 'x' ),
          array( get_post_type_archive_link( 'po_video' ), 'Vídeos', '' ),
        );
        foreach ( $itens as $i ) echo '<a' . ( $i[2] ? ' class="' . esc_attr( $i[2] ) . '"' : '' ) . ' href="' . esc_url( $i[0] ) . '">' . esc_html( $i[1] ) . '</a>';
      }
      ?>
    </nav>
    <a class="bt-denuncia" href="<?php echo esc_url( po_denuncia_link() ); ?>">Faça sua denúncia</a>
    <button class="ico busca-bt" type="button" id="abre-busca" aria-label="Buscar" aria-controls="busca" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></button>
  </div>
  <div class="busca" id="busca" hidden>
    <div class="w"><form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>"><label class="screen-reader-text" for="q" style="position:absolute;left:-9999px">Buscar notícias</label><input id="q" type="search" name="s" placeholder="Buscar notícias" value="<?php echo esc_attr( get_search_query() ); ?>"><button type="submit">Buscar</button></form></div>
  </div>
</header>

<div class="fundo-gaveta" id="fundo-gaveta" hidden></div>
<aside class="gaveta" id="gaveta" aria-label="Menu" hidden>
  <div class="topo-g"><span>Menu</span><button class="ico" type="button" id="fecha-menu" aria-label="Fechar menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
  <nav>
    <a class="bt-denuncia-g" href="<?php echo esc_url( po_denuncia_link() ); ?>">Faça sua denúncia</a>
    <b>EDITORIAS</b>
    <a class="c-azul" href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>">Santa Luzia</a>
    <a href="<?php echo esc_url( po_link_cat( 'belo-horizonte' ) ); ?>">Belo Horizonte</a>
    <a href="<?php echo esc_url( po_link_cat( 'regiao-metropolitana' ) ); ?>">Região Metropolitana</a>
    <a href="<?php echo esc_url( po_link_cat( 'minas-gerais' ) ); ?>">Minas Gerais</a>
    <a href="<?php echo esc_url( po_link_cat( 'politica' ) ); ?>">Política</a>
    <a href="<?php echo esc_url( po_link_cat( 'brasil' ) ); ?>">Brasil</a>
    <a href="<?php echo esc_url( po_link_cat( 'mundo' ) ); ?>">Mundo</a>
    <a class="c-verde" href="<?php echo esc_url( po_link_cat( 'esporte' ) ); ?>">Esporte</a>
    <a href="<?php echo esc_url( po_link_cat( 'educacao' ) ); ?>">Educação</a>
    <a href="<?php echo esc_url( po_link_cat( 'saude' ) ); ?>">Saúde</a>
    <a href="<?php echo esc_url( po_link_cat( 'entretenimento' ) ); ?>">Entretenimento</a>
    <a href="<?php echo esc_url( get_post_type_archive_link( 'po_video' ) ); ?>">Vídeos</a>
    <b>PORTAIS</b>
    <a class="c-azul" href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>">Portal Luziense</a>
    <a class="c-comercio" href="<?php echo esc_url( po_portal_url( 'comercio' ) ); ?>">Portal Comércio</a>
    <a class="c-preto" href="<?php echo esc_url( po_portal_url( 'politica' ) ); ?>">Portal Política</a>
    <a class="c-azul" href="<?php echo esc_url( home_url( '/#colunistas' ) ); ?>">Colunistas</a>
    <b>PORTAL ONE NEWS</b>
    <a href="<?php echo esc_url( po_canal_link() ); ?>" target="_blank" rel="noopener">Canal exclusivo no WhatsApp</a>
    <a href="<?php echo esc_url( home_url( '/#anuncie' ) ); ?>">Anuncie</a>
  </nav>
</aside>

<div class="agora"><div class="w"><span class="rot">AGORA</span><div class="trilho"><span><?php
  foreach ( get_posts( array( 'posts_per_page' => 10, 'ignore_sticky_posts' => true ) ) as $p ) {
    echo '<b class="pt">•</b><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>';
  }
?></span></div></div></div>
