<?php

/**
 * Importer class for importing from CubePoints.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

/**
 * CubePoints importer.
 *
 * @since 1.0.0
 */
class WordPoints_CubePoints_Importer extends WordPoints_Importer {

	/**
	 * @since 1.0.0
	 */
	function __construct( $name ) {

		parent::__construct( $name );

		$this->components = array(
			'points' => array(
				'excluded_users' => array(
					'label' => __( 'Excluded users', 'wordpoints-importer' ),
					'function' => array( $this, 'import_excluded_users' ),
				),
				'user_points' => array(
					'label' => __( 'User points', 'wordpoints-importer' ),
					'function' => array( $this, 'import_user_points' ),
				),
				'logs' => array(
					'label' => __( 'Points logs', 'wordpoints-importer' ),
					'function' => array( $this, 'import_points_logs' ),
					'can_import' => array( $this, 'can_import_points_logs' ),
				),
			),
		);
	}

	/**
	 * @since 1.0.0
	 */
	public function is_available() {

		return $this->is_cubepoints_installed();
	}

	/**
	 * Check if CubePoints is installed.
	 *
	 * @since 1.0.0
	 */
	public function is_cubepoints_installed() {

		return (bool) get_option( 'cp_db_version' );
	}

	/**
	 * Check if CubePoints is active.
	 *
	 * @since 1.0.0
	 */
	public function is_cubepoints_active() {

		return function_exists( 'cp_ready' );
	}

	/**
	 * Import the excluded users.
	 *
	 * @since 1.0.0
	 */
	protected function import_excluded_users() {

		$this->feedback->info( __( 'Importing excluded users&hellip;', 'wordpoints-importer' ) );

		$excluded_users = get_option( 'cp_topfilter' );

		if ( ! is_array( $excluded_users ) ) {
			$this->feedback->warning( __( 'No excluded users found.', 'wordpoints-importer' ) );
			return;
		}

		$user_ids = array();

		foreach ( $excluded_users as $user_login ) {

			$user = get_user_by( 'login', $user_login );

			if ( $user instanceof WP_User ) {
				$user_ids[] = $user->ID;
			}
		}

		$excluded_user_ids = wordpoints_get_array_option( 'wordpoints_excluded_users', 'network' );
		$excluded_user_ids = array_unique(
			array_merge( $excluded_user_ids, $user_ids )
		);

		wordpoints_update_network_option( 'wordpoints_excluded_users', $excluded_user_ids );

		$this->feedback->success(
			sprintf(
				__( 'Imported %s excluded users.', 'wordpoints-importer' )
				, count( $user_ids )
			)
		);
	}

	/**
	 * Import the user points.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The settings for the points component import.
	 */
	protected function import_user_points( $settings ) {

		$this->feedback->info( __( 'Importing users&apos; points&hellip;', 'wordpoints-importer' ) );

		// We don't log the import transactions.
		add_filter( 'wordpoints_points_log', '__return_false' );

		$start = 0;

		// We do the import in batches.
		while ( $rows = $this->get_next_user_points_batch( $start ) ) {

			$start += count( $rows );

			foreach ( $rows as $row ) {

				wordpoints_alter_points(
					$row->user_id
					, $row->points
					, $settings['points_type']
					, 'cubepoints_import' // This is only for the hooks, it isn't being logged.
				);
			}

			unset( $rows );
		}

		remove_filter( 'wordpoints_points_log', '__return_false' );

		$this->feedback->success( sprintf( __( 'Imported points for %s users&hellip;', 'wordpoints-importer' ), $start ) );
	}

	/**
	 * Get a batch of user points to import.
	 *
	 * @since 1.0.0
	 *
	 * @param int $start The offset number to begin counting at.
	 *
	 * @return object[]
	 */
	protected function get_next_user_points_batch( $start ) {

		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"
					SELECT `user_id`, `meta_value` as points
					FROM {$wpdb->usermeta}
					WHERE `meta_key` = 'cpoints'
					ORDER BY `umeta_id`
					LIMIT %d,500
				"
				, $start
			)
		);

		if ( ! is_array( $rows ) ) {
			return false;
		}

		return $rows;
	}

	/**
	 * Check if the points logs can be imported.
	 *
	 * @since 1.0.0
	 *
	 * @return true|WP_Error True on success or a WP_Error on failure.
	 */
	public function can_import_points_logs() {

		if ( ! $this->is_cubepoints_active() ) {

			return new WP_Error(
				'wordpoints_import_points_logs_cubepoints_inactive'
				, __( 'CubePoints must be active.', 'wordpoints-importer' )
			);
		}

		return true;
	}

	/**
	 * Import the points logs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The import settings for the points component.
	 */
	protected function import_points_logs( $settings ) {

		$this->feedback->info( __( 'Importing points logs&hellip;', 'wordpoints-importer' ) );

		add_filter( 'wordpoints_points_log-cubepoints', array( $this, 'render_points_log_text' ), 10, 6 );

		$start = 0;

		while ( $logs = $this->get_next_points_logs_batch( $start ) ) {

			$start += count( $logs );

			foreach ( $logs as $log ) {

				$this->import_points_log(
					$log->uid
					, $log->points
					, $settings['points_type']
					, 'cubepoints'
					, array(
						'cubepoints_type' => $log->type,
						'cubepoints_data' => $log->data,
					)
					, date( 'Y-m-d H:i:s', $log->timestamp )
				);
			}

			unset( $logs );
		}

		remove_filter( 'wordpoints_points_log-cubepoints', array( $this, 'render_points_log_text' ) );

		$this->feedback->success( sprintf( __( 'Imported %s points log entries.', 'wordpoints-importer' ), $start ) );
	}

	/**
	 * Import a points log.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id     The ID of the user the log is for.
	 * @param int    $points      The number of points awarded.
	 * @param string $points_type The points type.
	 * @param string $log_type    The log type.
	 * @param array  $meta        The metadata for this log.
	 * @param int    $date        The date the transaction took place.
	 */
	protected function import_points_log( $user_id, $points, $points_type, $log_type, $meta, $date ) {

		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->wordpoints_points_logs,
			array(
				'user_id'     => $user_id,
				'points'      => $points,
				'points_type' => $points_type,
				'log_type'    => $log_type,
				'text'        => wordpoints_render_points_log_text( $user_id, $points, $points_type, $log_type, $meta ),
				'date'        => $date,
				'site_id'     => $wpdb->siteid,
				'blog_id'     => $wpdb->blogid,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
		);

		if ( false !== $result ) {

			$log_id = (int) $wpdb->insert_id;

			foreach ( $meta as $meta_key => $meta_value ) {

				wordpoints_add_points_log_meta( $log_id, $meta_key, $meta_value );
			}

			do_action( 'wordpoints_points_log', $user_id, $points, $points_type, $log_type, $meta, $log_id );
		}
	}

	/**
	 * Generate the log text when importing a points log.
	 *
	 * @since 1.0.0
	 *
	 * @WordPress\filter wordpoints_points_log-cubepoints Added by self::import_points_logs().
	 */
	public function render_points_log_text( $text, $user_id, $points, $points_type, $log_type, $meta ) {

		ob_start();
		do_action( 'cp_logs_description', $meta['cubepoints_type'], $user_id, $points, $meta['cubepoints_data'] );
		return ob_get_clean();
	}

	/**
	 * Get the next 500 points logs from CubePoints.
	 *
	 * @since 1.0.0
	 *
	 * @param int $start The offset number to begin counting the 500 at.
	 *
	 * @return object[] The rows from the database.
	 */
	protected function get_next_points_logs_batch( $start ) {

		global $wpdb;

		$logs = $wpdb->get_results(
			$wpdb->prepare(
				'
					SELECT *
					FROM `' . CP_DB . '`
					ORDER BY `id`
					LIMIT %d,500
				'
				, $start
			)
		);

		if ( ! is_array( $logs ) ) {
			$this->feedback->error( __( 'Unable to retrieve the logs from CubePoints&hellip;', 'wordpoints-importer' ) );
			return false;
		}

		return $logs;
	}
}

// EOF
