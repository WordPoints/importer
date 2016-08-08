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

	// We activate these now so that they will be fully loaded. Otherwise only part
	// of their functions will be loaded, as the rest are defined in a conditional.
	// Because of some of the functions are defined outside of the conditional, there
	// is no way for us to load the functions later without a fatal error from
	// "already defined function".
	cp_module_activation_set( 'dailypoints', 'active' );
	cp_module_activation_set( 'post_author_points', 'active' );
}

// EOF
