<?php

/**
 * The extension's general functions.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

/**
 * Load the module's text domain.
 *
 * @since 1.0.0
 * @deprecated 1.3.0 No longer needed.
 */
function wordpoints_importer_load_textdomain() {

	_deprecated_function( __FUNCTION__, '1.3.0' );

	wordpoints_load_module_textdomain(
		'wordpoints-importer'
		, wordpoints_module_basename( dirname( dirname( __FILE__ ) ) ) . '/languages'
	);
}

/**
 * Register the included importers.
 *
 * @since 1.0.0
 */
function wordpoints_importer_register_importers() {

	$args = array(
		'class' => 'WordPoints_CubePoints_Importer',
		'name'  => __( 'CubePoints', 'wordpoints-importer' ),
	);

	WordPoints_Importers::register( 'cubepoints', $args );
}
add_action( 'wordpoints_register_importers', 'wordpoints_importer_register_importers' );

// EOF
