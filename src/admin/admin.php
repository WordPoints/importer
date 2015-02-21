<?php

/**
 * Code for the administration panels.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

/**
 * Register the module's administration panel and menu item.
 *
 * @since 1.0.0
 */
function wordpoints_importer_admin_menu() {

	add_submenu_page(
		wordpoints_get_main_admin_menu()
		,__( 'WordPoints — Import', 'wordpoints-importer' )
		,__( 'Import', 'wordpoints-importer' )
		,'manage_options'
		,'wordpoints_import'
		,'wordpoints_import_admin_screen'
	);

	add_submenu_page(
		'_wordpoints_import' // Fake.
		,__( 'WordPoints — Importing', 'wordpoints-importer' )
		,__( 'Importing', 'wordpoints-importer' )
		,'manage_options'
		,'wordpoints_importing'
		,'wordpoints_importing_admin_screen'
	);
}
add_action( 'admin_menu', 'wordpoints_importer_admin_menu' );
add_action( 'network_admin_menu', 'wordpoints_importer_admin_menu' );

/**
 * Display the importer administration screen.
 *
 * @since 1.0.0
 */
function wordpoints_import_admin_screen() {

	/**
	 * The importer admin screen.
	 *
	 * @since 1.0.0
	 */
	require( dirname( __FILE__ ) . '/screens/import.php' );
}

/**
 * Display the importing administration screen.
 *
 * @since 1.0.0
 */
function wordpoints_importing_admin_screen() {

	/**
	 * The importer admin screen.
	 *
	 * @since 1.0.0
	 */
	require( dirname( __FILE__ ) . '/screens/importing.php' );
}

/**
 * Register the scripts used on the admin screens.
 *
 * @since 1.0.0
 */
function wordpoints_importer_register_admin_scripts() {

	$assets_url = wordpoints_modules_url(
		'admin/assets'
		, dirname( dirname( __FILE__ ) ) . '/importer.php'
	);

	wp_register_style(
		'wordpoints-importer-feedback'
		, $assets_url . '/css/feedback.css'
	);
}
add_action( 'init', 'wordpoints_importer_register_admin_scripts' );

/**
 * Handle an import request.
 *
 * @since 1.0.0
 */
function wordpoints_importer_do_import() {

	if ( ! defined( 'IFRAME_REQUEST' ) ) {
		define( 'IFRAME_REQUEST', true );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to import to WordPoints.', 'wordpoints-importer' ) );
	}

	check_admin_referer( 'wordpoints_import' );

	if ( ! isset( $_GET['importer'] ) ) {
		wp_die( esc_html__( 'No importer selected.', 'wordpoints-importer' ) );
	}

	$importer = WordPoints_Importers::get_importer( $_GET['importer'] );

	if ( ! ( $importer instanceof WordPoints_Importer ) ) {
		wp_die( esc_html__( 'Importer not installed.', 'wordpoints-importer' ) );
	}

	wp_enqueue_style( 'wordpoints-importer-feedback' );

	$args = array();

	if ( isset( $_GET['wordpoints_import'] ) && is_array( $_GET['wordpoints_import'] ) ) {
		$args = $_GET['wordpoints_import'];
	}

	iframe_header();

	$importer->do_import( $args );

	iframe_footer();
}
add_action( 'update-custom_wordpoints_import', 'wordpoints_importer_do_import' );

/**
 * Display a points type dropdown above the points component settings.
 *
 * @since 1.0.0
 */
function wordpoints_importer_admin_screen_points_type_select() {

	$args = array( 'name' => 'wordpoints_import[points][_data][points_type]' );

	?>

	<p>
		<label for="wordpoints_import[points][_data][points_type]">
			<?php esc_html_e( 'Import to points type:', 'wordpoints-importer' ); ?>
			<?php wordpoints_points_types_dropdown( $args ); ?>
		</label>
	</p>

	<?php
}
add_action(
	'wordpoints_importer_before_component_options-points'
	, 'wordpoints_importer_admin_screen_points_type_select'
);

/**
 * Validate the points type import setting for the points component.
 *
 * @since 1.0.0
 */
function wordpoints_importer_validate_points_type_setting( $valid, $settings, $feedback ) {

	if ( $valid ) {

		if ( ! isset( $settings['points_type'] ) ) {
			$feedback->warning( __( 'Skipping Points component—no points type specified.', 'wordpoints-importer' ) );
			$valid = false;
		} elseif ( ! wordpoints_is_points_type( $settings['points_type'] ) ) {
			$feedback->warning( __( 'Skipping Points component—invalid points type selected.', 'wordpoints-importer' ) );
			$valid = false;
		}
	}

	return $valid;
}
add_action(
	'wordpoints_import_settings_valid-points'
	, 'wordpoints_importer_validate_points_type_setting'
	, 10
	, 3
);

// EOF
