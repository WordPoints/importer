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
	require dirname( __FILE__ ) . '/screens/import.php';
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
	require dirname( __FILE__ ) . '/screens/importing.php';
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
		wp_die( esc_html__( 'Sorry, you are not allowed to import to WordPoints.', 'wordpoints-importer' ) );
	}

	check_admin_referer( 'wordpoints_import' );

	if ( ! isset( $_GET['importer'] ) ) {
		wp_die( esc_html__( 'No importer selected.', 'wordpoints-importer' ) );
	}

	$importer = WordPoints_Importers::get_importer(
		sanitize_key( $_GET['importer'] )
	);

	if ( ! ( $importer instanceof WordPoints_Importer ) ) {
		wp_die( esc_html__( 'Importer not installed.', 'wordpoints-importer' ) );
	}

	wp_enqueue_style( 'wordpoints-importer-feedback' );

	$args = array();

	if ( isset( $_GET['wordpoints_import'] ) && is_array( $_GET['wordpoints_import'] ) ) {
		$args = wp_unslash( $_GET['wordpoints_import'] ); // WPCS: sanitization OK.
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
 *
 * @param bool                         $valid    Whether the settings are valid.
 * @param array                        $settings The settings.
 * @param WordPoints_Importer_Feedback $feedback The feedback object.
 *
 * @return bool Whether the settings are valid.
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

/**
 * Display a rank group dropdown above the ranks component settings.
 *
 * @since 1.1.0
 */
function wordpoints_importer_admin_screen_rank_group_select() {

	$rank_groups = WordPoints_Rank_Groups::get();

	// See https://github.com/WordPoints/wordpoints/issues/310.
	$options = array();
	foreach ( $rank_groups as $rank_group ) {
		$options[ $rank_group->slug ] = $rank_group->name;
	}

	$dropdown = new WordPoints_Dropdown_Builder(
		$options
		, array( 'name' => 'wordpoints_import[ranks][_data][rank_group]' )
	);

	?>

	<p>
		<label for="wordpoints_import[ranks][_data][rank_group]">
			<?php esc_html_e( 'Import to rank group:', 'wordpoints-importer' ); ?>
			<?php $dropdown->display(); ?>
		</label>
	</p>

<?php
}
add_action(
	'wordpoints_importer_before_component_options-ranks'
	, 'wordpoints_importer_admin_screen_rank_group_select'
);

/**
 * Validate the rank group import setting for the ranks component.
 *
 * @since 1.1.0
 *
 * @param bool                         $valid    Whether the settings are valid.
 * @param array                        $settings The settings.
 * @param WordPoints_Importer_Feedback $feedback The feedback object.
 *
 * @return bool Whether the settings are valid.
 */
function wordpoints_importer_validate_rank_group_setting( $valid, $settings, $feedback ) {

	if ( $valid ) {

		if ( ! isset( $settings['rank_group'] ) ) {
			$feedback->warning( __( 'Skipping Ranks component—no rank group specified.', 'wordpoints-importer' ) );
			$valid = false;
		} elseif ( ! WordPoints_Rank_Groups::is_group_registered( $settings['rank_group'] ) ) {
			$feedback->warning( __( 'Skipping Ranks component—invalid rank group selected.', 'wordpoints-importer' ) );
			$valid = false;
		}
	}

	return $valid;
}
add_action(
	'wordpoints_import_settings_valid-ranks'
	, 'wordpoints_importer_validate_rank_group_setting'
	, 10
	, 3
);

// EOF
