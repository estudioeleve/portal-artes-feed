<?php
/* Listas: portal (Luziense, Comércio, Política), colunista, editoria, vídeos e busca. */
get_header();
$portal = get_query_var( 'po_portal' );
$todos  = po_portais();
$portal = isset( $todos[ $portal ] ) ? $portal : '';

if ( $portal ) :
  $p = $todos[ $portal ]; ?>
  <div class="cab-portal f-<?php echo esc_attr( $p['cor'] ); ?>"><div class="w">
    <?php echo $p['logo'] ? '<img src="' . esc_url( $p['logo'] ) . '" alt="">' : ''; ?>
    <h1 class="nm<?php echo $p['logo'] ? ' so-leitor' : ''; ?>" style="margin:0"><?php echo esc_html( $p['nome'] ); ?></h1>
    <?php echo po_patrocinador( 'portal-' . $portal ); ?>
    <p><?php echo esc_html( $p['desc'] ); ?> Um portal do ecossistema Portal ONE News.</p>
  </div></div>

<?php elseif ( is_author() ) :
  $u = get_queried_object();
  $quem = get_user_meta( $u->ID, 'po_quem', true ); ?>
  <div class="cab-blog"><div class="w">
    <?php echo po_avatar( $u ); ?>
    <div class="tx">
      <span class="tag"><?php echo esc_html( get_user_meta( $u->ID, 'po_coluna', true ) ? get_user_meta( $u->ID, 'po_coluna', true ) : 'Coluna' ); ?></span>
      <h1><?php echo esc_html( $u->display_name ); ?></h1>
      <?php if ( $quem ) echo '<span class="quem">' . esc_html( $quem ) . '</span>'; ?>
      <?php if ( $u->description ) echo '<p>' . esc_html( $u->description ) . '</p>'; ?>
    </div>
  </div></div>
  <?php
  $ultc = get_posts( array( 'author' => $u->ID, 'posts_per_page' => 1, 'ignore_sticky_posts' => true ) );
  if ( $ultc ) :
    $resumo = get_post_meta( $ultc[0]->ID, 'po_linha_fina', true );
    if ( ! $resumo ) $resumo = wp_trim_words( wp_strip_all_tags( $ultc[0]->post_content ), 34, '…' ); ?>
    <div class="w">
      <a class="ult-col" href="<?php echo esc_url( get_permalink( $ultc[0] ) ); ?>">
        <small>Última coluna</small>
        <b><?php echo esc_html( get_the_title( $ultc[0] ) ); ?></b>
        <?php if ( $resumo ) echo '<span>' . esc_html( $resumo ) . '</span>'; ?>
      </a>
    </div>
  <?php endif; ?>

<?php else :
  if ( is_post_type_archive( 'po_video' ) ) { $tit = 'Vídeos'; $cor = 'azul'; }
  elseif ( is_search() ) { $tit = 'Busca: ' . get_search_query(); $cor = 'azul'; }
  elseif ( is_category() ) {
    $tit = single_term_title( '', false );
    $t = get_queried_object();
    $mapa = array( 'esporte' => 'verde', 'atletico' => 'verde', 'cruzeiro' => 'verde', 'america' => 'verde', 'entretenimento' => 'laranja', 'politica' => 'preto', 'comercio' => 'comercio', 'luto' => 'preto' );
    $cor = isset( $mapa[ $t->slug ] ) ? $mapa[ $t->slug ] : 'azul';
  } else { $tit = get_the_archive_title(); $cor = 'azul'; } ?>
  <div class="w cab-ed"><h1 class="c-<?php echo esc_attr( $cor ); ?>"><?php echo esc_html( $tit ); ?></h1></div>
  <?php
  if ( is_category() && isset( $t ) && in_array( $t->slug, array( 'esporte', 'atletico', 'cruzeiro', 'america' ), true ) ) :
    $abas = array( 'esporte' => 'Todo Esporte', 'atletico' => 'Atlético', 'cruzeiro' => 'Cruzeiro', 'america' => 'América' );
    ?>
    <div class="w abas-esporte">
      <?php foreach ( $abas as $slug => $nome ) :
        $cat_aba = get_category_by_slug( $slug );
        if ( ! $cat_aba ) continue; ?>
        <a href="<?php echo esc_url( get_category_link( $cat_aba ) ); ?>" class="<?php echo $t->slug === $slug ? 'ativa' : ''; ?>"><?php echo esc_html( $nome ); ?></a>
      <?php endforeach; ?>
    </div>
    <div class="w pv-grade">
      <div class="pv-col-principal">
        <div class="pv-escudos">
          <?php foreach ( array(
            array( 'atletico', 'Atlético-MG', 'https://a.espncdn.com/i/teamlogos/soccer/500/7632.png' ),
            array( 'cruzeiro', 'Cruzeiro', 'https://a.espncdn.com/i/teamlogos/soccer/500/2022.png' ),
            array( 'america', 'América-MG', 'https://a.espncdn.com/i/teamlogos/soccer/500/6154.png' ),
          ) as $e ) :
            $cat_e = get_category_by_slug( $e[0] );
            if ( ! $cat_e ) continue; ?>
            <a class="pv-escudo" href="<?php echo esc_url( get_category_link( $cat_e ) ); ?>"><img src="<?php echo esc_url( $e[2] ); ?>" alt="" loading="lazy"><?php echo esc_html( $e[1] ); ?></a>
          <?php endforeach; ?>
        </div>
        <div id="placar-vivo"><p class="pv-vazio">Carregando placar de hoje…</p></div>
      </div>
      <div class="pv-col-outros">
        <h2>Outros jogos de hoje</h2>
        <div id="placar-outros"><p class="pv-vazio">Carregando…</p></div>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="w">
<?php po_banner( 'topo', 'lb', '970 × 90' ); ?>
<?php if ( is_post_type_archive( 'po_video' ) ) : ?>
  <div class="grade-vid"><?php while ( have_posts() ) { the_post(); po_video_card( get_post() ); } ?></div>
  <div class="paginacao" style="margin-bottom:40px"><?php echo paginate_links( array( 'prev_text' => '‹ Anteriores', 'next_text' => 'Próximas ›' ) ); ?></div>
<?php else : ?>
  <div class="pag-lista">
    <div>
      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $ed = po_editoria(); ?>
        <a class="item-l" href="<?php the_permalink(); ?>">
          <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'po-card', array( 'class' => 'foto', 'alt' => '', 'loading' => 'lazy' ) ); ?>
          <div>
            <?php if ( $ed ) echo '<span class="ed c-' . esc_attr( po_cor( get_the_ID() ) ) . '">' . esc_html( $ed->name ) . '</span>'; ?>
            <h2><?php the_title(); ?></h2>
            <?php $lf = get_post_meta( get_the_ID(), 'po_linha_fina', true ); if ( $lf ) echo '<p>' . esc_html( $lf ) . '</p>'; ?>
            <span class="hora"><?php echo esc_html( po_hora() ); ?></span>
          </div>
        </a>
      <?php endwhile; else : echo '<p style="padding-block:24px;color:var(--sub)">' . ( $portal ? 'As notícias do ' . esc_html( $todos[ $portal ]['nome'] ) . ' aparecem aqui assim que forem publicadas.' : 'Ainda não há notícias aqui.' ) . '</p>'; endif; ?>
      <div class="paginacao"><?php echo paginate_links( array( 'prev_text' => '‹ Anteriores', 'next_text' => 'Próximas ›' ) ); ?></div>
    </div>
    <aside class="lado-mat">
      <?php if ( is_author() ) { $pc = po_patrocinador( 'coluna', get_queried_object_id() ); if ( $pc ) echo '<div class="pat-col"><small>Coluna oferecida por</small>' . str_replace( 'Oferecido por ', '', $pc ) . '</div>'; } ?>
      <?php po_banner( 'lateral', 'ret', '300 × 250' ); ?>
      <div><h2>Mais lidas</h2><?php po_mais_lidas( 5, 'uma' ); ?></div>
    <?php po_banner( 'lateral2', 'ret', '300 × 250' ); ?>
    </aside>
  </div>
<?php endif; ?>
</div>
<?php get_footer(); ?>
