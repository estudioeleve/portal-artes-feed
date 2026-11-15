<?php get_header(); ?>
<div class="w"><?php while ( have_posts() ) : the_post(); ?><article class="materia"><h1><?php the_title(); ?></h1><div class="texto"><?php the_content(); ?></div></article><?php endwhile; ?></div>
<?php get_footer(); ?>
