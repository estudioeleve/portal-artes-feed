<?php get_header(); ?>
<div class="w">
<?php po_banner( 'topo', 'lb', '970 × 90' ); ?>
<?php while ( have_posts() ) : the_post();
  $ed = po_editoria(); $cor = po_cor( get_the_ID() );
  $lf = get_post_meta( get_the_ID(), 'po_linha_fina', true );
  $cr = get_post_meta( get_the_ID(), 'po_credito_foto', true );
  $fo = get_post_meta( get_the_ID(), 'po_fonte', true );
  $autor = get_userdata( get_the_author_meta( 'ID' ) );
  $col = $autor ? get_user_meta( $autor->ID, 'po_coluna', true ) : ''; ?>
<div class="pag-mat">
  <article class="materia">
    <?php if ( $ed ) echo '<a class="ed c-' . esc_attr( $cor ) . '" href="' . esc_url( get_category_link( $ed ) ) . '">' . esc_html( $ed->name ) . '</a>'; ?>
    <h1><?php the_title(); ?></h1>
    <?php if ( $lf ) echo '<p class="linha-fina">' . esc_html( $lf ) . '</p>'; ?>
    <?php if ( $col ) : $quem = get_user_meta( $autor->ID, 'po_quem', true ); ?>
      <a class="autor-box" href="<?php echo esc_url( get_author_posts_url( $autor->ID ) ); ?>"><?php echo po_avatar( $autor ); ?><span><b><?php echo esc_html( $autor->display_name ); ?></b><br><span class="hora"><?php echo esc_html( $quem ? $quem : $col ); ?></span></span></a>
    <?php endif; ?>
    <div class="meta"><span class="por">Por <?php echo esc_html( $col ? $autor->display_name : 'Redação Portal ONE News' ); ?></span><span><?php echo esc_html( po_hora() ); ?></span><?php if ( $fo ) echo '<span>Fonte: ' . esc_html( $fo ) . '</span>'; ?></div>
    <?php if ( has_post_thumbnail() ) : ?><figure><?php the_post_thumbnail( 'large', array( 'class' => 'capa' ) ); ?><?php if ( $cr ) echo '<figcaption>Foto: ' . esc_html( $cr ) . '</figcaption>'; ?></figure><?php endif; ?>
    <div class="texto"><?php the_content(); ?></div>
    <?php $u = rawurlencode( get_permalink() ); $t = rawurlencode( get_the_title() ); ?>
    <div class="compart" data-url="<?php echo esc_url( get_permalink() ); ?>" data-titulo="<?php echo esc_attr( get_the_title() ); ?>">
      <p class="compart-tit">Compartilhe esta matéria</p>
      <div class="compart-btns">
        <a class="zap" href="https://wa.me/?text=<?php echo $t . '%20' . $u; ?>" target="_blank" rel="noopener" aria-label="Compartilhar no WhatsApp"><?php echo po_icone( 'whatsapp' ); ?>WhatsApp</a>
        <a class="fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $u; ?>" target="_blank" rel="noopener" aria-label="Compartilhar no Facebook"><?php echo po_icone( 'facebook' ); ?>Facebook</a>
        <a class="x" href="https://twitter.com/intent/tweet?text=<?php echo $t; ?>&url=<?php echo $u; ?>" target="_blank" rel="noopener" aria-label="Compartilhar no X (Twitter)"><?php echo po_icone( 'x' ); ?>X</a>
        <button type="button" class="ig" data-compart-copiar aria-label="Copiar link para postar no Instagram"><?php echo po_icone( 'instagram' ); ?>Instagram</button>
        <button type="button" class="mais" data-compart-nativo aria-label="Mais opções de compartilhamento"><?php echo po_icone( 'compartilhar' ); ?>Mais opções</button>
      </div>
      <p class="compart-dica" hidden data-compart-dica>Link copiado — é só colar nos stories, no TikTok ou onde você quiser postar.</p>
    </div>
    <div class="compart-canal"><a href="<?php echo esc_url( po_canal_link() ); ?>" target="_blank" rel="noopener">Entrar no canal exclusivo</a></div>
  </article>
  <aside class="lado-mat">
    <?php if ( $col ) { $pc = po_patrocinador( 'coluna', $autor->ID ); if ( $pc ) echo '<div class="pat-col"><small>Coluna oferecida por</small>' . str_replace( 'Oferecido por ', '', $pc ) . '</div>'; } ?>
    <?php po_banner( 'lateral', 'ret', '300 × 250' ); ?>
    <div><h2>Mais lidas</h2><?php po_mais_lidas( 5, 'uma' ); ?></div>
    <?php po_banner( 'lateral2', 'ret', '300 × 250' ); ?>
  </aside>
</div>
<?php endwhile; ?>
<section class="bloco">
  <div class="sec-t"><h2 class="c-azul">Leia também</h2></div>
  <div class="g4"><?php $usados = array( get_the_ID() ); $c = po_editoria(); foreach ( po_posts( array( 'cat' => $c ? $c->term_id : 0, 'posts_per_page' => 4 ), $usados ) as $id ) po_card( $id ); ?></div>
</section>
</div>
<?php get_footer(); ?>
