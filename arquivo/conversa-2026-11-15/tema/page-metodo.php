<?php
/**
 * Página de vendas do Método ONE News (portalonenews.com.br/metodo).
 * Página própria, sem o cabeçalho e o rodapé do portal.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
header( 'Content-Type: text/html; charset=utf-8' );
readfile( get_template_directory() . '/landing/metodo.html' );
exit;
