<footer class="rod">
  <div class="w">
    <div class="gr">
      <div>
        <a class="marca" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/logo.png' ); ?>" alt="Portal ONE News" width="86" height="40"></a>
        <p style="margin:0;max-width:300px">O maior ecossistema de notícias da Região Metropolitana de Belo Horizonte.</p>
      </div>
      <div>
        <h4>EDITORIAS</h4>
        <a href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>">Santa Luzia</a>
        <a href="<?php echo esc_url( po_link_cat( 'belo-horizonte' ) ); ?>">Belo Horizonte</a>
        <a href="<?php echo esc_url( po_link_cat( 'regiao-metropolitana' ) ); ?>">Região Metropolitana</a>
        <a href="<?php echo esc_url( po_link_cat( 'minas-gerais' ) ); ?>">Minas Gerais</a>
        <a href="<?php echo esc_url( po_link_cat( 'politica' ) ); ?>">Política</a>
      </div>
      <div>
        <h4>&nbsp;</h4>
        <a href="<?php echo esc_url( po_link_cat( 'brasil' ) ); ?>">Brasil</a>
        <a href="<?php echo esc_url( po_link_cat( 'mundo' ) ); ?>">Mundo</a>
        <a href="<?php echo esc_url( po_link_cat( 'esporte' ) ); ?>">Esporte</a>
        <a href="<?php echo esc_url( po_link_cat( 'educacao' ) ); ?>">Educação</a>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'po_video' ) ); ?>">Vídeos</a>
      </div>
      <div>
        <h4>PORTAIS</h4>
        <a href="<?php echo esc_url( po_portal_url( 'luziense' ) ); ?>">Portal Luziense</a>
        <a href="<?php echo esc_url( po_portal_url( 'comercio' ) ); ?>" style="color:#F59A3C">Portal Comércio</a>
        <a href="<?php echo esc_url( po_portal_url( 'politica' ) ); ?>">Portal Política</a>
        <a href="<?php echo esc_url( home_url( '/#colunistas' ) ); ?>">Colunistas</a>
        <a href="<?php echo esc_url( home_url( '/#anuncie' ) ); ?>" style="color:#F5B800">Anuncie</a>
      </div>
    </div>
    <div class="copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Portal ONE News · Eleve Comunicação e Marketing</div>
  </div>
</footer>
<a class="zap-flutua" href="<?php echo esc_url( po_zap_contato() ); ?>" target="_blank" rel="noopener" aria-label="Fale com a gente pelo WhatsApp: denúncia, sugestão ou reclamação"><?php echo po_icone_zap(); ?><span>Fale com a gente</span></a>
<?php wp_footer(); ?>
</body>
</html>
