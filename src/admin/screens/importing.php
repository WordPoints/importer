<?php

/**
 * The importing administration screen.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

if ( true !== current_user_can( 'manage_options' ) ) {
	wp_die();
}

check_admin_referer( 'wordpoints_import' );

?>

<h2><?php esc_html_e( 'WordPoints Importer', 'wordpoints-importer' ); ?></h2>

<div class="wrap">
	<p><?php esc_html_e( 'Starting import (this could take a few moments)&hellip;', 'wordpoints-importer' ); ?></p>

	<iframe src="<?php echo esc_attr( esc_url( self_admin_url( 'update.php?action=wordpoints_import&' . http_build_query( $_POST ) ) ) ); ?>" style="width: 100%; height:100%; min-height:850px;"></iframe>
</div>
