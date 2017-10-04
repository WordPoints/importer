<?php

/**
 * Base class for the importers.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

/**
 * Represents an importer.
 *
 * @since 1.0.0
 */
abstract class WordPoints_Importer {

	/**
	 * The name of the importer.
	 *
	 * @since 1.0.0
	 *
	 * @type string $name
	 */
	protected $name;

	/**
	 * The components supported by this importer.
	 *
	 * The keys are the component slugs, the values arrays of options for importing
	 * to that component.
	 *
	 * @since 1.0.0
	 *
	 * @type array[] $components
	 */
	protected $components = array();

	/**
	 * The feedback provider object.
	 *
	 * This is only set by self::do_import().
	 *
	 * @since 1.0.0
	 *
	 * @type WordPoints_Importer_Feedback $feedback
	 */
	protected $feedback;

	/**
	 * Check if this importer is available.
	 *
	 * @since 1.0.0
	 *
	 * @return true|WP_Error A WP_Error if the importer is not available.
	 */
	abstract public function is_available();

	/**
	 * Construct the importer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the importer.
	 */
	public function __construct( $name ) {

		$this->name = $name;
	}

	/**
	 * Check if this importer supports a specific component.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component The slug of a component.
	 *
	 * @return bool True if the component is supported, otherwise false.
	 */
	public function supports_component( $component ) {

		return isset( $this->components[ $component ] );
	}

	/**
	 * Get the import options for a component.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component The slug of a component.
	 *
	 * @return array[] The options for this component.
	 */
	public function get_options_for_component( $component ) {

		if ( ! $this->supports_component( $component ) ) {
			return array();
		}

		return $this->components[ $component ];
	}

	/**
	 * Run the import.
	 *
	 * @since 1.0.0
	 *
	 * @param array                        $args     The settings for the import.
	 * @param WordPoints_Importer_Feedback $feedback The feedback object.
	 */
	public function do_import( array $args, $feedback = null ) {

		if ( ! ( $feedback instanceof WordPoints_Importer_Feedback ) ) {
			$feedback = new WordPoints_Importer_Feedback();
		}

		$this->feedback = $feedback;

		// translators: Plugin name.
		$this->feedback->info( sprintf( __( 'Importing from %s&hellip;', 'wordpoints-importer' ), $this->name ) );

		$this->no_interruptions();

		foreach ( $args as $component => $options ) {
			$this->do_import_for_component( $component, $options );
		}

		$this->feedback->info( __( 'Import complete.', 'wordpoints-importer' ) );
	}

	/**
	 * Prevent any interruptions from occurring during the import.
	 *
	 * @since 1.2.1
	 */
	protected function no_interruptions() {

		ignore_user_abort( true );

		if (
			// Back-compat with WordPoints 2.1.
			function_exists( 'wordpoints_is_function_disabled' )
			&& ! wordpoints_is_function_disabled( 'set_time_limit' )
		) {
			set_time_limit( 0 );
		}
	}

	/**
	 * Validate the import settings for a component.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component The slug of the component.
	 * @param array  $settings  The settings supplied for this component.
	 *
	 * @return bool Whether the settings are valid.
	 */
	protected function validate_import_settings( $component, $settings ) {

		/**
		 * Filter whether the settings are valid before importing.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $valid    Whether the settings are valid.
		 * @param array $settings The settings for this component.
		 * @param WordPoints_Importer_Feedback $feedback The feedback object.
		 */
		return apply_filters( "wordpoints_import_settings_valid-{$component}", true, $settings, $this->feedback );
	}

	/**
	 * Run the import for a component.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component The component to run the import for.
	 * @param array  $options   The selected options of what to import.
	 */
	protected function do_import_for_component( $component, $options ) {

		$component_data = WordPoints_Components::instance()->get_component(
			$component
		);

		if ( false === $component_data ) {
			// translators: Component name.
			$this->feedback->warning( sprintf( __( 'Skipping %s component—not installed.', 'wordpoints-importer' ), esc_html( $component ) ) );
			return;
		}

		if ( true !== $this->supports_component( $component ) ) {
			// translators: Component name.
			$this->feedback->warning( sprintf( __( 'Skipping the %s component—not supported.', 'wordpoints-importer' ), $component_data['name'] ) );
			return;
		}

		$settings = array();

		if ( isset( $options['_data'] ) ) {
			$settings = $options['_data'];
			unset( $options['_data'] );
		}

		if ( empty( $options ) || ! $this->validate_import_settings( $component, $settings ) ) {
			return;
		}

		// translators: Component name.
		$this->feedback->info( sprintf( __( 'Importing data to the %s component&hellip;', 'wordpoints-importer' ), $component_data['name'] ) );

		foreach ( $options as $option => $unused ) {
			$this->do_import_for_option( $option, $component, $settings );
		}
	}

	/**
	 * Run the import for an option.
	 *
	 * The import is split up into different options which the user can select (these
	 * are displayed to the user as checkboxes in the form). This handles the import
	 * for each of the individual things the user has selected to import. These are
	 * all optional, so each is just termed an import "option" here.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option    An import option that has been selected.
	 * @param string $component The component this option is for.
	 * @param array  $settings  Other settings for this component.
	 */
	protected function do_import_for_option( $option, $component, $settings ) {

		if ( ! isset( $this->components[ $component ][ $option ] ) ) {
			// translators: Option name.
			$this->feedback->warning( sprintf( __( 'Skipping unrecognized import option &#8220;%s&#8221;&hellip;', 'wordpoints-importer' ), $option ) );
			return;
		}

		$option_data = $this->components[ $component ][ $option ];

		// Check if we can actually run this option.
		if ( isset( $option_data['can_import'] ) ) {

			$cant_import = call_user_func( $option_data['can_import'], $settings );

			if ( is_wp_error( $cant_import ) ) {
				// translators: 1. Option name; 2. Reason the import was skipped.
				$this->feedback->warning( sprintf( __( 'Skipping importing %1$s. Reason: %2$s', 'wordpoints-importer' ), $option_data['label'], $cant_import->get_error_message() ) );
				return;
			}
		}

		// OK, we can run the import method for this option.
		call_user_func( $option_data['function'], $settings );
	}
}

// EOF
