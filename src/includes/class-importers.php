<?php

/**
 * Importers class.
 *
 * @package WordPoints_Importer
 * @since 1.3.0
 */

/**
 * Container class for the available importers.
 *
 * @since 1.0.0
 */
final class WordPoints_Importers {

	//
	// Private Vars.
	//

	/**
	 * The registered importers.
	 *
	 * @since 1.0.0
	 *
	 * @type array $importers
	 */
	private static $importers = array();

	/**
	 * Whether the class has been initialized yet.
	 *
	 * @since 1.0.0
	 *
	 * @type bool $initialized
	 */
	private static $initialized = false;

	//
	// Private Functions.
	//

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	private static function init() {

		// We do this first so we avoid infinite loops if this class is called by a
		// function hooked to the below action.
		self::$initialized = true;

		/**
		 * Register importers.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wordpoints_register_importers' );
	}

	//
	// Public Functions.
	//

	/**
	 * Get all of the registered importers.
	 *
	 * @since 1.0.0
	 *
	 * @return array All of the registered importers.
	 */
	public static function get() {

		if ( ! self::$initialized ) {
			self::init();
		}

		return self::$importers;
	}

	/**
	 * Register an importer.
	 *
	 * If the importer is already registered, it will be overwritten.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The unique identifier for this importer.
	 * @param array  $args {
	 *        Other importer arguments.
	 *
	 *        @type string $class The Importer class.
	 *        @type string $name  The name of this importer.
	 * }
	 */
	public static function register( $slug, array $args ) {
		self::$importers[ $slug ] = $args;
	}

	/**
	 * Deregister an importer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The slug of the importer to deregister.
	 */
	public static function deregister( $slug ) {
		unset( self::$importers[ $slug ] );
	}

	/**
	 * Check if an importer is registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The slug of the importer.
	 *
	 * @return bool True if the importer is registered, otherwise false.
	 */
	public static function is_registered( $slug ) {

		if ( ! self::$initialized ) {
			self::init();
		}

		return isset( self::$importers[ $slug ] );
	}

	/**
	 * Get an instance of an importer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The slug of the importer to get an instance of.
	 *
	 * @return WordPoints_Importer|false The importer, or false if it isn't registered.
	 */
	public static function get_importer( $slug ) {

		if ( ! self::is_registered( $slug ) ) {
			return false;
		}

		$importer = self::$importers[ $slug ];

		return new $importer['class']( $importer['name'] );
	}
}

// EOF
