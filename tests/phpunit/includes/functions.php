<?php

/**
 * Utility functions used in PHPUnit testing.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

/**
 * The extension's tests directory.
 *
 * @since 1.0.0
 *
 * @type string
 */
define( 'WORDPOINTS_IMPORTER_TESTS_DIR', dirname( dirname( __FILE__ ) ) );

// Back-compat with WordPoints 2.1.
if ( class_exists( 'WordPoints_PHPUnit_Bootstrap_Loader' ) ) {

	$loader = WordPoints_PHPUnit_Bootstrap_Loader::instance();
	$loader->add_plugin( 'cubepoints/cubepoints.php' );
	$loader->add_php_file(
		WORDPOINTS_IMPORTER_TESTS_DIR . '/includes/activate-cubepoints-components.php'
		, 'after'
		, array( 'dailypoints', 'post_author_points' )
	);

} elseif ( ! defined( 'WORDPOINTS_MODULE_TESTS_LOADER' ) ) {

	/**
	 * The function that loads the module for the tests.
	 *
	 * @since 1.0.0
	 */
	define( 'WORDPOINTS_MODULE_TESTS_LOADER', 'wordpoints_importer_tests_manually_load_module' );
}

/**
 * Manually load the module.
 *
 * @since 1.0.0
 * @deprecated 1.2.1
 */
function wordpoints_importer_tests_manually_load_module() {

	require WORDPOINTS_IMPORTER_TESTS_DIR . '/../../src/importer.php';
	require WORDPOINTS_IMPORTER_TESTS_DIR . '/../../src/admin/admin.php';

	wordpoints_importer_tests_manually_load_cubepoints();
}

/**
 * Manually load the CubePoints plugin.
 *
 * @since 1.0.0
 * @deprecated 1.2.1
 */
function wordpoints_importer_tests_manually_load_cubepoints() {

	require WP_PLUGIN_DIR . '/cubepoints/cubepoints.php';

	cp_activate();

	// We activate these now so that they will be fully loaded. Otherwise only part
	// of their functions will be loaded, as the rest are defined in a conditional.
	// Because of some of the functions are defined outside of the conditional, there
	// is no way for us to load the functions later without a fatal error from
	// "already defined function".
	cp_module_activation_set( 'dailypoints', 'active' );
	cp_module_activation_set( 'post_author_points', 'active' );

	// We have to do this manually here after WordPress 4.7.
	// https://core.trac.wordpress.org/ticket/38011#comment:3
	global $wp_version;

	if ( version_compare( $wp_version, '4.6', '>' ) ) {
		cp_modules_include();
	}
}

// EOF
