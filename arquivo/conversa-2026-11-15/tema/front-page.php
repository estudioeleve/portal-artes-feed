<?php
/* Página inicial do Portal ONE News — versão 2.4 (topo CNN + organização Globo, destaque em carrossel). */
get_header();
$usados = array();
$enviado = isset( $_GET['enviado'] ) ? sanitize_key( $_GET['enviado'] ) : '';

/* carrossel do destaque: notícias fixadas no topo primeiro, completando com as mais recentes (até 5) */
$fixas = get_option( 'sticky_posts' );
$colunistas = wp_list_pluck( po_lista_colunistas( 200 ), 'ID' ); // colunas ficam fora dos destaques (têm a área própria)
$slides = $fixas ? po_posts( array( 'post__in' => $fixas, 'posts_per_page' => 5 ), $usados ) : array();
if ( count( $slides ) < 5 ) $slides = array_merge( $slides, po_posts( array( 'posts_per_page' => 5 - count( $slides ), 'author__not_in' => $colunistas ), $usados ) );
?>
<div class="portais"><div class="w"><nav aria-label="Portais e áreas">
  <a class="c-azul" href="<?php echo esc_url( home_url( '/' ) ); ?>">one news</a>
  <a class="c-azul" href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>">portal luziense</a>
  <a class="c-comercio" href="<?php echo esc_url( po_portal_url( 'comercio' ) ); ?>">portal comércio</a>
  <a class="c-preto" href="<?php echo esc_url( po_portal_url( 'politica' ) ); ?>">portal política</a>
  <a class="c-verde" href="<?php echo esc_url( po_link_cat( 'esporte' ) ); ?>">esporte</a>
  <a class="c-azul" href="<?php echo esc_url( po_link_cat( 'grande-bh' ) ); ?>">grande bh</a>
  <a class="c-azul" href="#colunistas">colunistas</a>
  <a class="c-vermelho" href="<?php echo esc_url( get_theme_mod( 'po_youtube' ) ? get_theme_mod( 'po_youtube' ) : get_post_type_archive_link( 'po_video' ) ); ?>">youtube</a>
</nav></div></div>

<main>
  <div class="w" style="padding-top:16px"><div id="placar-home"></div></div>
  <div class="w"><?php po_banner( 'topo', 'lb', '970 × 90' ); ?></div>

  <?php if ( $slides ) :
    $lado = po_posts( array( 'posts_per_page' => 4, 'author__not_in' => $colunistas ), $usados );
    $nl = count( $lado );
    /* Portal Luziense: fica embaixo do carrossel, no espaço ao lado das notícias do destaque */
    $sl = po_posts( array( 'category_name' => 'santa-luzia', 'posts_per_page' => 3 ), $usados );
    /* sem notícias de Santa Luzia suficientes: o espaço embaixo do carrossel mostra as últimas notícias, nunca fica vazio */
    $sl_vazio = array();
    if ( ! $sl ) $sl_vazio = po_posts( array( 'posts_per_page' => 3, 'author__not_in' => $colunistas ), $usados ); ?>
  <section class="w hero <?php echo $nl >= 4 ? '' : ( $nl >= 1 ? 'hero-2' : 'hero-1' ); ?>" aria-label="Destaques">
    <div class="h-princ">
      <div class="carrossel" aria-roledescription="carrossel" aria-label="Principais notícias">
        <?php foreach ( $slides as $i => $sid ) : $ed = po_editoria( $sid ); $cor = po_cor( $sid );
          $res = array_slice( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $sid, 'po_resumos', true ) ) ) ), 0, 2 ); ?>
        <article class="manchete slide<?php echo 0 === $i ? ' ativo' : ''; ?>" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' de ' . count( $slides ) ); ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
          <a href="<?php echo esc_url( get_permalink( $sid ) ); ?>"<?php echo 0 === $i ? '' : ' tabindex="-1"'; ?>>
            <div class="img">
              <?php echo get_the_post_thumbnail( $sid, 'large', array( 'alt' => '', 'loading' => 0 === $i ? 'eager' : 'lazy' ) ); ?>
              <div class="sobre">
                <?php if ( $ed ) echo '<span class="ed">' . esc_html( $ed->name ) . '</span>'; ?>
                <?php echo 0 === $i ? '<h1>' : '<h2 class="h1">'; echo esc_html( get_the_title( $sid ) ); echo 0 === $i ? '</h1>' : '</h2>'; ?>
              </div>
            </div>
            <?php if ( $res ) : ?><div class="faixa f-<?php echo esc_attr( $cor ); ?>"><?php foreach ( $res as $r ) echo '<span>' . esc_html( $r ) . '</span>'; ?></div><?php endif; ?>
          </a>
        </article>
        <?php endforeach; ?>
        <?php if ( count( $slides ) > 1 ) : ?>
        <button class="car-bt ant" type="button" aria-label="Notícia anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
        <button class="car-bt prox" type="button" aria-label="Próxima notícia"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
        <div class="car-pontos"><?php foreach ( $slides as $i => $sid ) echo '<button type="button" aria-label="Ir para a notícia ' . ( $i + 1 ) . '"' . ( 0 === $i ? ' aria-current="true"' : '' ) . '></button>'; ?></div>
        <?php endif; ?>
      </div>

      <?php if ( $sl ) : ?>
      <section class="bloco bloco-sl" id="santa-luzia" aria-labelledby="sl-h">
        <div class="sec-t" style="border-top-color:var(--luz)"><a href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>" style="display:flex;align-items:center;gap:12px"><?php echo po_selo( 'luziense' ); ?><h2 id="sl-h" class="c-azul">Santa Luzia</h2></a><a class="ver c-azul" href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>">Ver tudo ›</a></div>
        <div class="g3c"><?php foreach ( $sl as $id ) po_card( $id, 'card', 'azul' ); ?></div>
      </section>
      <?php elseif ( $sl_vazio ) : ?>
      <section class="bloco bloco-sl" aria-labelledby="ua-h">
        <div class="sec-t"><h2 id="ua-h" class="c-azul">Últimas notícias</h2></div>
        <div class="g3c"><?php foreach ( $sl_vazio as $id ) po_card( $id ); ?></div>
      </section>
      <?php endif; ?>
    </div>
    <?php if ( $nl >= 4 ) : ?>
    <div class="hcol"><?php foreach ( array_slice( $lado, 0, 2 ) as $id ) po_card_g( $id ); ?></div>
    <div class="hcol"><?php foreach ( array_slice( $lado, 2, 2 ) as $id ) po_card_g( $id ); ?></div>
    <?php elseif ( $nl ) : ?>
    <div class="hcol"><?php foreach ( array_slice( $lado, 0, 2 ) as $id ) po_card_g( $id ); ?></div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php /* ---- corpo em duas colunas: notícias à esquerda; à direita os dois quadrados, mais lidas e os vídeos ---- */ ?>
  <div class="w corpo">
    <div class="principal">
      <?php
      $blocos = 0;
      /* Portal Comércio e Portal Política */
      $com = po_posts( array( 'category_name' => 'comercio', 'posts_per_page' => 3 ), $usados );
      $pol = po_posts( array( 'category_name' => 'politica', 'posts_per_page' => 3 ), $usados );
      if ( count( $com ) < 2 ) { $usados = array_diff( $usados, $com ); $com = array(); }
      if ( count( $pol ) < 2 ) { $usados = array_diff( $usados, $pol ); $pol = array(); }
      if ( $com || $pol ) : $blocos++; ?>
      <section class="bloco <?php echo ( $com && $pol ) ? 'g2' : ''; ?>" aria-label="Portal Comércio e Portal Política">
        <?php if ( $com ) : ?><div style="min-width:0">
          <div class="sec-t" style="border-top-color:var(--com)"><a href="<?php echo esc_url( po_portal_url( 'comercio' ) ); ?>"><?php echo po_selo( 'comercio' ); ?></a><a class="ver c-comercio" href="<?php echo esc_url( po_portal_url( 'comercio' ) ); ?>">Ver tudo ›</a></div>
          <?php foreach ( $com as $id ) po_card( $id, 'linha', 'comercio' ); ?>
        </div><?php endif; ?>
        <?php if ( $pol ) : ?><div style="min-width:0">
          <div class="sec-t" style="border-top-color:var(--pol)"><a href="<?php echo esc_url( po_portal_url( 'politica' ) ); ?>"><?php echo po_selo( 'politica' ); ?></a><a class="ver c-preto" href="<?php echo esc_url( po_portal_url( 'politica' ) ); ?>">Ver tudo ›</a></div>
          <?php foreach ( $pol as $id ) po_card( $id, 'linha', 'preto' ); ?>
        </div><?php endif; ?>
      </section>
      <?php endif;

      /* BH, Região Metropolitana e Minas */
      $gb = po_posts( array( 'category_name' => 'grande-bh,belo-horizonte,regiao-metropolitana,minas-gerais', 'posts_per_page' => 3 ), $usados );
      if ( count( $gb ) >= 3 ) : $blocos++; ?>
      <section class="bloco" id="grande-bh" aria-labelledby="bh-h">
        <div class="sec-t"><h2 id="bh-h" class="c-azul">BH, Região Metropolitana e Minas</h2><a class="ver c-azul" href="<?php echo esc_url( po_link_cat( 'grande-bh' ) ); ?>">Ver tudo ›</a></div>
        <div class="g3c"><?php foreach ( $gb as $id ) po_card( $id ); ?></div>
      </section>
      <?php else : $usados = array_diff( $usados, $gb ); endif;

      /* Colunistas: ao lado dos vídeos, antes das últimas notícias */
      $cols = po_lista_colunistas( 6 );
      if ( $cols ) : $blocos++; ?>
      <section class="bloco" id="colunistas" aria-labelledby="col-h">
        <div class="sec-t"><h2 id="col-h" class="c-azul">Colunistas</h2><a class="ver c-azul" href="#anuncie">Quero ser colunista ›</a></div>
        <div class="g-col">
          <?php foreach ( $cols as $u ) :
            $ultc = get_posts( array( 'author' => $u->ID, 'posts_per_page' => 1, 'ignore_sticky_posts' => true ) );
            $quem = get_user_meta( $u->ID, 'po_quem', true );
            $quem = $quem ? $quem : get_user_meta( $u->ID, 'po_coluna', true );
            $resumo = '';
            if ( $ultc ) {
              $resumo = get_post_meta( $ultc[0]->ID, 'po_linha_fina', true );
              if ( ! $resumo ) $resumo = wp_trim_words( wp_strip_all_tags( $ultc[0]->post_content ), 22, '…' );
            } ?>
          <a class="colunista" href="<?php echo esc_url( get_author_posts_url( $u->ID ) ); ?>">
            <?php echo po_avatar( $u ); ?>
            <span class="tx"><span class="nome"><?php echo esc_html( $u->display_name ); ?></span><span class="quem"><?php echo esc_html( $quem ); ?></span><?php if ( $ultc ) : ?><span class="ult-tit"><?php echo esc_html( get_the_title( $ultc[0] ) ); ?></span><?php if ( $resumo ) echo '<span class="ult">' . esc_html( $resumo ) . '</span>'; ?><?php endif; ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif;

      /* Últimas notícias: completa a coluna com o que sobrou, para nunca ficar espaço vazio ao lado da coluna da direita */
      $falta = $blocos >= 3 ? 7 : ( $blocos ? 8 : 9 );
      $ult = po_posts( array( 'posts_per_page' => $falta, 'author__not_in' => $colunistas ), $usados );
      if ( $ult ) : ?>
      <section class="bloco" aria-labelledby="ult-h">
        <div class="sec-t"><h2 id="ult-h" class="c-azul">Últimas notícias</h2></div>
        <?php foreach ( $ult as $id ) po_card( $id, 'linha' ); ?>
      </section>
      <?php endif;
      /* poucas notícias no site: as Mais lidas vêm para esta coluna, para ela acompanhar a altura da coluna ao lado */
      $curto = ( 0 === $blocos );
      if ( $curto ) : ?>
      <section class="bloco lidas-ad" style="display:block" aria-labelledby="ml-h"><div class="sec-t"><h2 id="ml-h" class="c-azul">Mais lidas</h2></div><?php po_mais_lidas( 6 ); ?></section>
      <?php endif; ?>
    </div>

    <aside class="lateral" aria-label="Publicidade, mais lidas e vídeos">
      <?php po_banner( 'lateral', 'ret', '300 × 250' ); ?>
      <?php if ( ! $curto ) : ?><div class="lat-bloco"><h2 class="c-azul">Mais lidas</h2><?php po_mais_lidas( 5, 'uma' ); ?></div><?php endif; ?>
      <?php po_banner( 'lateral2', 'ret', '300 × 250' ); ?>
      <?php $vids = get_posts( array( 'post_type' => 'po_video', 'posts_per_page' => 3 ) ); if ( $vids ) : ?>
      <div class="lat-bloco lat-vid" id="videos">
        <h2 class="c-vermelho" style="border-top-color:#B4232A">Vídeos</h2>
        <?php foreach ( $vids as $v ) po_video_card( $v ); ?>
        <a class="ver-v" href="<?php echo esc_url( get_theme_mod( 'po_youtube' ) ? get_theme_mod( 'po_youtube' ) : get_post_type_archive_link( 'po_video' ) ); ?>">Ver todos os vídeos ›</a>
        <?php $pv = po_anuncios( 'videos' ); if ( $pv ) echo '<p class="pat">Vídeos oferecidos por <b>' . esc_html( get_the_title( $pv[0] ) ) . '</b></p>'; ?>
      </div>
      <?php endif; ?>
    </aside>
  </div>

  <div class="w so-pc"><?php po_banner( 'meio', 'lb', '970 × 90' ); ?></div>

  <?php /* editorias em colunas: só as que têm notícias */
  $eds = array();
  foreach ( array( array( 'brasil', 'Brasil', 'azul' ), array( 'mundo', 'Mundo', 'azul' ), array( 'esporte', 'Esporte', 'verde' ), array( 'educacao', 'Educação', 'azul' ) ) as $e ) {
    $ids = po_posts( array( 'category_name' => $e[0], 'posts_per_page' => 4 ), $usados );
    if ( count( $ids ) >= 2 ) { $e[] = $ids; $eds[] = $e; }
  }
  if ( count( $eds ) >= 2 ) : ?>
  <section class="w bloco" style="padding-top:40px" aria-label="Editorias">
    <div class="cols4" style="--n:<?php echo (int) count( $eds ); ?>">
      <?php foreach ( $eds as $e ) : ?>
      <div class="coluna-ed">
        <a class="tit-ed c-<?php echo esc_attr( $e[2] ); ?>" href="<?php echo esc_url( po_link_cat( $e[0] ) ); ?>"><?php echo esc_html( $e[1] ); ?></a>
        <ul><?php foreach ( $e[3] as $id ) echo '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></li>'; ?></ul>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="w bloco" aria-label="Canal exclusivo">
    <div class="canal" id="canal">
      <div><span class="selo2">CANAL EXCLUSIVO · GRÁTIS</span><h2>Receba as notícias de Santa Luzia antes de todo mundo</h2><p>Entre no canal de notícias exclusivas do Portal ONE News no WhatsApp.</p></div>
      <a class="bt bt-canal" href="<?php echo esc_url( po_canal_link() ); ?>" target="_blank" rel="noopener"><?php echo po_icone_zap(); ?>Entrar no canal</a>
    </div>
  </section>

  <?php $vit = po_anuncios( 'vitrine', 4 ); if ( count( $vit ) >= 2 ) : ?>
  <section class="w bloco" aria-labelledby="vit-h">
    <div class="sec-t"><h2 id="vit-h">Vitrine de anunciantes</h2><a class="ver c-azul" href="#anuncie">Anuncie seu produto ›</a></div>
    <div class="vitrine" style="--n:<?php echo (int) count( $vit ); ?>">
      <?php foreach ( $vit as $a ) : $link = get_post_meta( $a->ID, 'po_link', true ); ?>
        <a class="prod" href="<?php echo esc_url( $link ? $link : '#anuncie' ); ?>" rel="sponsored noopener" target="_blank"><div class="im"><?php echo get_the_post_thumbnail( $a, 'po-card', array( 'alt' => '', 'loading' => 'lazy' ) ); ?></div><div class="tx"><span class="pat">Patrocinado</span><b><?php echo esc_html( get_the_title( $a ) ); ?></b><?php $pr = get_post_meta( $a->ID, 'po_preco', true ); if ( $pr ) echo '<span class="preco">' . esc_html( $pr ) . '</span>'; ?></div></a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="w bloco" aria-labelledby="an-h">
    <div class="anuncie" id="anuncie">
      <div>
        <h2 id="an-h">Anuncie no Portal ONE News</h2>
        <p>Mais de <?php echo esc_html( get_theme_mod( 'po_seguidores', '137 mil' ) ); ?> seguidores no Instagram e o site com as notícias da Grande BH. Escolha como sua marca aparece.</p>
        <div class="formatos">
          <div><b>Banner no site</b><span>No topo das páginas e ao lado das notícias.</span></div>
          <div><b>Patrocínio de coluna ou portal</b><span>"Oferecido por" na coluna ou no portal que você escolher.</span></div>
          <div><b>Vitrine de produto</b><span>Seu produto com foto e preço no meio das notícias.</span></div>
          <div><b>Matéria patrocinada</b><span>Uma reportagem sobre a sua empresa, no site e no Instagram.</span></div>
        </div>
      </div>
      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="po_form"><input type="hidden" name="po_tipo" value="anuncie"><?php wp_nonce_field( 'po_form', 'po_form_nonce' ); ?>
        <input type="text" name="site" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <label for="f-nome">Seu nome</label><input id="f-nome" name="po_nome" required autocomplete="name">
        <label for="f-emp">Empresa</label><input id="f-emp" name="po_empresa" required>
        <label for="f-zap">WhatsApp</label><input id="f-zap" name="po_whats" required inputmode="tel" autocomplete="tel">
        <label for="f-fmt">Formato</label><select id="f-fmt" name="po_formato"><option>Banner no site</option><option>Patrocínio de coluna ou portal</option><option>Vitrine de produto</option><option>Matéria patrocinada</option><option>Quero ser colunista</option></select>
        <button class="bt" type="submit">Quero anunciar</button>
        <?php if ( 'anuncie' === $enviado ) echo '<div class="ok">Recebemos o seu pedido. A equipe comercial vai chamar você no WhatsApp.</div>'; ?>
      </form>
    </div>
  </section>
</main>
<?php get_footer(); ?>
