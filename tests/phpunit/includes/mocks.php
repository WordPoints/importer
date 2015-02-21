<?php

/**
 * Mocks for abstract classes used in the unit tests.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

/**
 * A mock for the WordPoints_Importer class.
 *
 * @since 1.0.0
 */
class WordPoints_Importer_Mock extends WordPoints_Importer {

	/**
	 * @since 1.0.0
	 */
	public $components;

	/**
	 * The "imports" performed.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $imports;

	/**
	 * The "imports" for that were checked for possible performance.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $can_imports;

	/**
	 * Whether this importer is available.
	 *
	 * @since 1.0.0
	 *
	 * @var true|WP_Error
	 */
	public $is_available = true;

	/**
	 * @since 1.0.0
	 */
	public function is_available() {
		return $this->is_available;
	}

	/**
	 * Mock an import method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The settings for this "component".
	 */
	public function do_an_import( $settings ) {

		$this->imports[] = $settings;
	}

	/**
	 * Mock a can_import method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The settings for this "component".
	 *
	 * @return true
	 */
	public function can_import( $settings ) {

		$this->can_imports[] = $settings;

		return true;
	}

	/**
	 * Return a WP_Error because we can't do the import for an option.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The settings for this "component".
	 *
	 * @return WP_Error An error.
	 */
	public function cant_import( $settings ) {

		$this->can_imports[] = $settings;

		return new WP_Error();
	}
}

// EOF
