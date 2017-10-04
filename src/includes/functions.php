<?php

/**
 * The module's general functions.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

/**
 * Load the module's text domain.
 *
 * @since 1.0.0
 * @deprecated 1.2.2 No longer needed.
 */
function wordpoints_importer_load_textdomain() {

	_deprecated_function( __FUNCTION__, '1.2.2' );

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

	/**
	 * The CubePoints importer.
	 *
	 * @since 1.0.0
	 */
	require_once dirname( __FILE__ ) . '/importers/cubepoints.php';

	$args = array(
		'class' => 'WordPoints_CubePoints_Importer',
		'name'  => __( 'CubePoints', 'wordpoints-importer' ),
	);

	WordPoints_Importers::register( 'cubepoints', $args );
}
add_action( 'wordpoints_register_importers', 'wordpoints_importer_register_importers' );

// EOF
