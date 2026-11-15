<?php get_header(); ?>
<div class="w">
<?php po_banner( 'topo', 'lb', '970 × 90' ); ?>
<?php while ( have_posts() ) : the_post();
  $url = get_post_meta( get_the_ID(), 'po_url', true );
  $rede = false !== strpos( (string) $url, 'instagram' ) ? 'Instagram · Reels' : 'YouTube'; ?>
<div class="pag-mat">
  <article class="materia">
    <a class="ed c-azul" href="<?php echo esc_url( get_post_type_archive_link( 'po_video' ) ); ?>">Vídeos</a>
    <h1><?php the_title(); ?></h1>
    <div class="meta"><span class="por">Portal ONE News</span><span><?php echo esc_html( po_hora() ); ?></span><span>Fonte: <?php echo esc_html( $rede ); ?></span></div>
    <div class="vid-grande"><?php echo po_embed_video( $url ); ?></div>
    <?php if ( get_the_content() ) : ?><div class="texto"><?php the_content(); ?></div><?php endif; ?>
    <?php $u = rawurlencode( get_permalink() ); $t = rawurlencode( get_the_title() ); ?>
    <div class="compart"><a class="zap" href="https://wa.me/?text=<?php echo $t . '%20' . $u; ?>" target="_blank" rel="noopener">Compartilhar no WhatsApp</a><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $u; ?>" target="_blank" rel="noopener">Facebook</a><a href="<?php echo esc_url( po_canal_link() ); ?>" target="_blank" rel="noopener">Entrar no canal exclusivo</a></div>
  </article>
  <aside class="lado-mat">
    <?php po_banner( 'lateral', 'ret', '300 × 250' ); ?>
    <div><h2>Mais lidas</h2><?php po_mais_lidas( 5, 'uma' ); ?></div>
    <?php po_banner( 'lateral2', 'ret', '300 × 250' ); ?>
  </aside>
</div>
<?php endwhile; ?>
<section class="bloco">
  <div class="sec-t"><h2 class="c-azul">Mais vídeos</h2></div>
  <div class="g4"><?php foreach ( get_posts( array( 'post_type' => 'po_video', 'posts_per_page' => 4, 'post__not_in' => array( get_the_ID() ) ) ) as $v ) po_video_card( $v ); ?></div>
</section>
</div>
<?php get_footer(); ?>
