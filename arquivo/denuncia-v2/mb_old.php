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
	$cat  = $cat ? $cat : po_den_cat_tipo( '', po_den_tipo( $post->ID ) );
	$prt  = get_post_meta( $post->ID, 'dc_portal_pub', true );
	$prt  = $prt ? $prt : 'luz';
	$apr  = get_post_meta( $post->ID, 'dc_aprovada', true );
	$mat  = (int) get_post_meta( $post->ID, 'dc_post_id', true );
	$art  = get_post_meta( $post->ID, 'dc_artes', true );
	$ins  = get_post_meta( $post->ID, 'dc_insta', true );
	$ins_st = get_post_meta( $post->ID, 'dc_insta_status', true );
	?>
	<hr style="margin:16px 0">
	<?php $tp_at = po_den_tipo( $post->ID ); ?>
	<p style="margin:0 0 4px"><b>Tipo do envio</b></p>
	<select name="dc_tipo" style="width:100%">
		<?php foreach ( po_den_tipos() as $k => $x ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $tp_at, $k ); ?>><?php echo esc_html( $x['nome'] ); ?></option><?php endforeach; ?>
	</select>
	<p style="color:#646970;font-size:12px;margin:4px 0 12px">Muda a etiqueta da arte, a editoria, o texto do site e a legenda do Instagram. Se já estiver publicada, clique em <b>Atualizar</b> e depois em <b>Refazer as artes</b> para trocar a arte.</p>
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
		<div style="margin:14px 0 8px;padding:12px;border-radius:8px;background:#edfaef;border:1px solid #68de7c;font-size:14px"><b style="color:#1e7e34">✅ <?php echo esc_html( po_den_rot( $post->ID )['nome'] ); ?> concluída</b> — publicada no site<?php echo 'postado' === $ins_st ? ' e postada no Instagram' : '. Instagram: aguardando o robô'; ?>.</div>
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
