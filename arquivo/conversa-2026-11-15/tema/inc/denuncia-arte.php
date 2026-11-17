<?php
/**
 * Gera as artes das denúncias (feed 1080x1350, Reels 1080x1920 e capa do site 1200x675)
 * na moldura do Portal ONE News / Portal Luziense, usando só o GD do PHP (sem serviço de fora).
 *
 * $d = array(
 *   'formato'   => 'feed' | 'reels' | 'site',
 *   'portal'    => 'luz' | 'one',
 *   'foto'      => caminho de uma imagem no servidor, ou '' (gera a moldura com fundo transparente, pra pôr sobre vídeo),
 *   'cat'       => 'Denúncia · Santa Luzia',
 *   'titulo'    => 'Resumo curto da denúncia',
 *   'anonima'   => true|false,
 *   'nome'      => 'Fulano de Tal',
 *   'protocolo' => 'OC-2026-0001',
 * )
 */

if ( ! function_exists( 'po_arte_denuncia' ) ) :

function po_arte_fonte( $peso ) {
	return __DIR__ . '/../assets/denuncia/opensans-' . $peso . '.ttf';
}

/* px -> pontos do GD (o GD trabalha a 96 dpi) */
function po_arte_pt( $px ) { return $px * 0.75; }

/* espaçamento entre letras (negativo = mais juntas), em fração do tamanho da letra */
function po_arte_track( $px, $peso ) { return 700 === $peso || 800 === $peso ? -0.045 * $px : -0.015 * $px; }
function po_arte_largura( $txt, $px, $peso ) {
	$b = imagettfbbox( po_arte_pt( $px ), 0, po_arte_fonte( $peso ), $txt );
	$n = function_exists( 'mb_strlen' ) ? mb_strlen( $txt, 'UTF-8' ) : strlen( $txt );
	return abs( $b[2] - $b[0] ) + po_arte_track( $px, $peso ) * max( 0, $n - 1 );
}

/* quebra em linhas e equilibra (sem uma palavra sozinha embaixo) */
function po_arte_quebra( $txt, $px, $peso, $larg ) {
	$pal = preg_split( '/\s+/u', trim( $txt ) );
	$monta = function ( $w ) use ( $pal, $px, $peso ) {
		$linhas = array(); $at = '';
		foreach ( $pal as $p ) {
			$t = '' === $at ? $p : $at . ' ' . $p;
			if ( '' !== $at && po_arte_largura( $t, $px, $peso ) > $w ) { $linhas[] = $at; $at = $p; } else { $at = $t; }
		}
		if ( '' !== $at ) $linhas[] = $at;
		return $linhas;
	};
	$base = $monta( $larg );
	$n = count( $base );
	if ( $n < 2 ) return $base;
	$melhor = $base;
	for ( $w = $larg - 10; $w > $larg * 0.5; $w -= 10 ) {
		$l = $monta( $w );
		if ( count( $l ) > $n ) break;
		$melhor = $l;
	}
	return $melhor;
}

function po_arte_cor( $im, $r, $g, $b, $a = 1.0 ) {
	return imagecolorallocatealpha( $im, $r, $g, $b, (int) round( 127 - 127 * max( 0, min( 1, $a ) ) ) );
}

function po_arte_ret_redondo( $im, $x, $y, $w, $h, $r, $cor ) {
	imagefilledrectangle( $im, $x + $r, $y, $x + $w - $r, $y + $h, $cor );
	imagefilledrectangle( $im, $x, $y + $r, $x + $w, $y + $h - $r, $cor );
	foreach ( array( array( $x + $r, $y + $r ), array( $x + $w - $r, $y + $r ), array( $x + $r, $y + $h - $r ), array( $x + $w - $r, $y + $h - $r ) ) as $c ) {
		imagefilledellipse( $im, $c[0], $c[1], $r * 2, $r * 2, $cor );
	}
}

/* foto cobrindo a área toda (corte centralizado) */
function po_arte_foto( $im, $arq, $W, $H, $x0 = 0, $y0 = 0 ) {
	$src = @imagecreatefromstring( file_get_contents( $arq ) );
	if ( ! $src ) return false;
	if ( function_exists( 'exif_read_data' ) && preg_match( '/\.jpe?g$/i', $arq ) ) {
		$ex = @exif_read_data( $arq );
		$o = isset( $ex['Orientation'] ) ? (int) $ex['Orientation'] : 1;
		if ( 3 === $o ) $src = imagerotate( $src, 180, 0 );
		if ( 6 === $o ) $src = imagerotate( $src, -90, 0 );
		if ( 8 === $o ) $src = imagerotate( $src, 90, 0 );
	}
	$sw = imagesx( $src ); $sh = imagesy( $src );
	$esc = max( $W / $sw, $H / $sh );
	$cw = $W / $esc; $ch = $H / $esc;
	imagecopyresampled( $im, $src, $x0, $y0, (int) ( ( $sw - $cw ) / 2 ), (int) ( ( $sh - $ch ) / 2 ), $W, $H, (int) $cw, (int) $ch );
	imagedestroy( $src );
	return true;
}

/* degradê: lista de paradas [posição 0-1, opacidade] */
function po_arte_degrade_v( $im, $W, $H, $rgb, $paradas ) {
	for ( $y = 0; $y < $H; $y++ ) {
		$p = $y / $H; $a = 0;
		for ( $i = 0; $i < count( $paradas ) - 1; $i++ ) {
			list( $p0, $a0 ) = $paradas[ $i ]; list( $p1, $a1 ) = $paradas[ $i + 1 ];
			if ( $p >= $p0 && $p <= $p1 ) { $a = $a0 + ( $a1 - $a0 ) * ( ( $p - $p0 ) / max( 0.0001, $p1 - $p0 ) ); break; }
			if ( $p > $p1 ) $a = $a1;
		}
		if ( $a > 0.001 ) imageline( $im, 0, $y, $W - 1, $y, po_arte_cor( $im, $rgb[0], $rgb[1], $rgb[2], $a ) );
	}
}
function po_arte_degrade_h( $im, $W, $H, $rgb, $paradas ) {
	for ( $x = 0; $x < $W; $x++ ) {
		$p = $x / $W; $a = 0;
		for ( $i = 0; $i < count( $paradas ) - 1; $i++ ) {
			list( $p0, $a0 ) = $paradas[ $i ]; list( $p1, $a1 ) = $paradas[ $i + 1 ];
			if ( $p >= $p0 && $p <= $p1 ) { $a = $a0 + ( $a1 - $a0 ) * ( ( $p - $p0 ) / max( 0.0001, $p1 - $p0 ) ); break; }
		}
		if ( $a > 0.001 ) imageline( $im, $x, 0, $x, $H - 1, po_arte_cor( $im, $rgb[0], $rgb[1], $rgb[2], $a ) );
	}
}

function po_arte_texto( $im, $txt, $px, $peso, $x, $topo, $cor ) {
	$fn = po_arte_fonte( $peso ); $pt = po_arte_pt( $px ); $tr = po_arte_track( $px, $peso );
	$ch = preg_split( '//u', $txt, -1, PREG_SPLIT_NO_EMPTY );
	$pre = '';
	foreach ( $ch as $i => $c ) {
		$dx = 0;
		if ( '' !== $pre ) { $b = imagettfbbox( $pt, 0, $fn, $pre . 'H' ); $bh = imagettfbbox( $pt, 0, $fn, 'H' ); $dx = ( $b[2] - $b[0] ) - ( $bh[2] - $bh[0] ); }
		if ( ' ' !== $c ) imagettftext( $im, $pt, 0, (int) round( $x + $dx + $tr * $i ), (int) ( $topo + $px * 0.80 ), $cor, $fn, $c );
		$pre .= $c;
	}
}

function po_arte_denuncia( $d, $saida ) {
	$f = $d['formato'];
	$cfg = array(
		'feed'  => array( 'W' => 1080, 'H' => 1350, 'tab' => array( 'tabc', 76, 59, 412 ), 'x' => 108, 'larg' => 868, 'base' => 1135, 'cat' => 40, 'tit' => 60, 'selo' => 27, 'prot' => 28, 'gap' => 22, 'url' => array( 21, 'dir', 90, 100 ) ),
		'reels' => array( 'W' => 1080, 'H' => 1920, 'tab' => array( 'tabr', 76, 58, 704 ), 'x' => 108, 'larg' => 864, 'base' => 1620, 'cat' => 41, 'tit' => 64, 'selo' => 28, 'prot' => 29, 'gap' => 24, 'url' => array( 21, 'dir', 74, 220 ) ),
		'site'  => array( 'W' => 1200, 'H' => 675, 'tab' => array( 'tabc', 56, 48, 338 ), 'x' => 150, 'larg' => 700, 'base' => 579, 'cat' => 30, 'tit' => 44, 'selo' => 21, 'prot' => 22, 'gap' => 16, 'url' => array( 18, 'esq', 150, 40 ) ),
	);
	$c = $cfg[ $f ];
	$W = $c['W']; $H = $c['H'];
	$im = imagecreatetruecolor( $W, $H );
	imagesavealpha( $im, true );
	imagealphablending( $im, false );
	imagefill( $im, 0, 0, po_arte_cor( $im, 0, 0, 0, 0 ) );
	imagealphablending( $im, true );

	$tem_foto = ! empty( $d['foto'] ) && file_exists( $d['foto'] ) && po_arte_foto( $im, $d['foto'], $W, $H );
	if ( ! $tem_foto && 'site' === $f ) imagefilledrectangle( $im, 0, 0, $W, $H, po_arte_cor( $im, 12, 30, 74 ) ); // capa sem foto: fundo azul do Portal

	// escuro da moldura
	if ( 'reels' === $f ) po_arte_degrade_v( $im, $W, $H, array( 0, 0, 0 ), array( array( 0, 0 ), array( 0.40, 0 ), array( 0.55, 0.35 ), array( 0.72, 0.75 ), array( 1, 0.92 ) ) );
	elseif ( 'feed' === $f ) po_arte_degrade_v( $im, $W, $H, array( 0, 0, 0 ), array( array( 0, 0 ), array( 0.34, 0 ), array( 0.52, 0.55 ), array( 0.70, 0.9 ), array( 1, 0.96 ) ) );
	else { po_arte_degrade_h( $im, $W, $H, array( 0, 0, 0 ), array( array( 0, 0.95 ), array( 0.40, 0.85 ), array( 0.75, 0.25 ), array( 1, 0 ) ) ); po_arte_degrade_v( $im, $W, $H, array( 0, 0, 0 ), array( array( 0, 0 ), array( 0.5, 0 ), array( 1, 0.6 ) ) ); }

	// aba do portal
	list( $tn, $tx, $tw, $th ) = $c['tab'];
	$tab = @imagecreatefrompng( __DIR__ . '/../assets/denuncia/' . $tn . '_' . ( 'one' === $d['portal'] ? 'one' : 'luz' ) . '.png' );
	if ( $tab ) { imagecopyresampled( $im, $tab, $tx, 0, 0, 0, $tw, $th, imagesx( $tab ), imagesy( $tab ) ); imagedestroy( $tab ); }

	$branco = po_arte_cor( $im, 255, 255, 255 );
	$sombra = po_arte_cor( $im, 0, 0, 0, 0.35 );
	$reels = 'reels' === $f;

	// título: até 3 linhas, diminui a letra se precisar
	$tp = $c['tit'];
	do { $linhas = po_arte_quebra( $d['titulo'], $tp, 700, $c['larg'] ); if ( count( $linhas ) <= 3 ) break; $tp -= 4; } while ( $tp > $c['tit'] * 0.6 );
	$lh = $tp * 1.11;

	// mede o bloco de baixo pra cima
	$selo_txt = ! empty( $d['selo'] ) ? $d['selo'] : ( ! empty( $d['anonima'] ) ? 'Denúncia anônima' : 'Denúncia feita por ' . $d['nome'] );
	$pad_y = round( $c['selo'] * 0.34 ); $pad_x = round( $c['selo'] * 0.6 );
	$selo_h = $c['selo'] * 1.36 + 2 * $pad_y;
	$y_info = $c['base'] - $selo_h;
	$y_tit = $y_info - $c['gap'] - 6 - $lh * count( $linhas );
	$y_cat = $y_tit - $c['gap'] - $c['cat'];

	// editoria
	$cat = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $d['cat'], 'UTF-8' ) : strtoupper( $d['cat'] );
	if ( $reels ) po_arte_texto( $im, $cat, $c['cat'], 700, $c['x'] + 2, $y_cat + 3, $sombra );
	po_arte_texto( $im, $cat, $c['cat'], 700, $c['x'], $y_cat, $branco );

	// título
	foreach ( $linhas as $i => $l ) {
		$yy = $y_tit + $i * $lh + ( $lh - $tp ) / 2;
		if ( $reels ) po_arte_texto( $im, $l, $tp, 700, $c['x'] + 2, $yy + 3, $sombra );
		po_arte_texto( $im, $l, $tp, 700, $c['x'], $yy, $branco );
	}

	// selo branco + protocolo
	$sw = po_arte_largura( $selo_txt, $c['selo'], 800 ) + 2 * $pad_x;
	po_arte_ret_redondo( $im, $c['x'], (int) $y_info, (int) $sw, (int) $selo_h, 8, $branco );
	po_arte_texto( $im, $selo_txt, $c['selo'], 800, $c['x'] + $pad_x, $y_info + $pad_y + $c['selo'] * 0.12, po_arte_cor( $im, 12, 30, 74 ) );
	$px = $c['x'] + $sw + round( $c['selo'] * 0.66 );
	$py = $y_info + ( $selo_h - $c['prot'] * 1.1 ) / 2;
	$p1 = 'Protocolo ';
	po_arte_texto( $im, $p1, $c['prot'], 600, $px, $py, po_arte_cor( $im, 255, 255, 255, 0.92 ) );
	po_arte_texto( $im, $d['protocolo'], $c['prot'], 800, $px + po_arte_largura( $p1, $c['prot'], 600 ) + 6, $py, $branco );

	// rodapé do site
	list( $us, $lado, $ux, $ub ) = $c['url'];
	$u1 = 'acesse o nosso site '; $u2 = 'www.portalonenews.com.br';
	$w1 = po_arte_largura( $u1, $us, 600 ); $w2 = po_arte_largura( $u2, $us, 800 );
	$x0 = 'dir' === $lado ? $W - $ux - $w1 - $w2 - 4 : $ux;
	$yt = $H - $ub - $us * 1.1;
	po_arte_texto( $im, $u1, $us, 600, $x0, $yt, po_arte_cor( $im, 255, 255, 255, 0.85 ) );
	po_arte_texto( $im, $u2, $us, 800, $x0 + $w1 + 4, $yt, $branco );

	$ok = imagepng( $im, $saida, 6 );
	imagedestroy( $im );
	return $ok;
}

endif;
