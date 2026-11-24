
/* === Denúncia: até 3 fotos e 3 vídeos escolhidos no painel (Israel, 24/11/2026) === */
// lista de ids escolhidos (dc_fotos_pub / dc_videos_pub); sem escolha, usa os que o leitor mandou
function po_denuncia_lista_pub( $id, $k ) {
	$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $id, 'dc_' . $k . 's_pub', true ) ) ) ) );
	if ( ! metadata_exists( 'post', $id, 'dc_' . $k . 's_pub' ) ) {
		$um = (int) get_post_meta( $id, 'dc_' . $k . '_pub', true );
		if ( $um ) $ids[] = $um;
		foreach ( get_attached_media( '', $id ) as $a ) {
			if ( 0 !== strpos( $a->post_mime_type, 'foto' === $k ? 'image' : 'video' ) || in_array( (int) $a->ID, $ids, true ) ) continue;
			if ( count( $ids ) >= 3 ) break;
			$ids[] = (int) $a->ID;
		}
	}
	return array_slice( array_values( array_filter( $ids, function ( $i ) { return (bool) wp_get_attachment_url( $i ); } ) ), 0, 3 );
}
add_action( 'save_post_po_denuncia', function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['dc_midia_nonce'] ) || ! wp_verify_nonce( $_POST['dc_midia_nonce'], 'dc_midia_pub' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	foreach ( array( 'foto', 'video' ) as $k ) {
		if ( ! isset( $_POST[ 'dc_' . $k . 's_pub' ] ) ) continue;
		$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) $_POST[ 'dc_' . $k . 's_pub' ] ) ) ) ) ), 0, 3 );
		update_post_meta( $post_id, 'dc_' . $k . 's_pub', implode( ',', $ids ) );
	}
}, 5 );
/* === fim fotos e vídeos === */
