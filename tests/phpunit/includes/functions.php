<?php

/**
 * Utility functions used in PHPUnit testing.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

if ( ! defined( 'WORDPOINTS_MODULE_TESTS_LOADER' ) ) {
	/**
	 * The function that loads the module for the tests.
	 *
	 * @since 1.0.0
	 */
	define( 'WORDPOINTS_MODULE_TESTS_LOADER', 'wordpoints_importer_tests_manually_load_module' );
}

/**
 * The module's tests directory.
 *
 * @since 1.0.0
 *
 * @type string
 */
define( 'WORDPOINTS_IMPORTER_TESTS_DIR', dirname( dirname( __FILE__ ) ) );

/**
 * Manually load the module.
 *
 * @since 1.0.0
 */
function wordpoints_importer_tests_manually_load_module() {

	require( WORDPOINTS_IMPORTER_TESTS_DIR . '/../../src/importer.php' );
	require( WORDPOINTS_IMPORTER_TESTS_DIR . '/../../src/admin/admin.php' );

	wordpoints_importer_tests_manually_load_cubepoints();
}

/**
 * Manually load the CubePoints plugin.
 *
 * @since 1.0.0
 */
function wordpoints_importer_tests_manually_load_cubepoints() {

	require( WP_PLUGIN_DIR . '/cubepoints/cubepoints.php' );

	cp_activate();
}

// EOF
