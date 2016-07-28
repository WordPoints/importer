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
	public function __construct( $name ) {

		parent::__construct( $name );

		$this->components = array(
			'points' => array(
				'excluded_users' => array(
					'label' => __( 'Excluded users', 'wordpoints-importer' ),
					'function' => array( $this, 'import_excluded_users' ),
				),
				'settings'    => array(
					'label' => __( 'Points Hooks', 'wordpoints-importer' ),
					'description' => __( 'If checked, the settings for the number of points to award for posts, comments, etc. are imported.', 'wordpoints-importer' ),
					'function' => array( $this, 'import_points_settings' ),
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
			'ranks' => array(
				'ranks' => array(
					'label' => __( 'Rank settings', 'wordpoints-importer' ),
					'description' => __( 'If checked, the list of ranks is imported, and users will have the correct ranks assigned to them.', 'wordpoints-importer' ),
					'function' => array( $this, 'import_ranks' ),
				),
			),
		);
	}

	/**
	 * @since 1.0.0
	 */
	public function is_available() {

		if ( ! $this->is_cubepoints_installed() ) {
			return new WP_Error(
				'cubepoints_not_installed'
				, __( 'CubePoints is not installed', 'wordpoints-importer' )
			);
		}

		return true;
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

		$excluded_user_ids = wordpoints_get_maybe_network_array_option(
			'wordpoints_excluded_users'
		);

		$excluded_user_ids = array_unique(
			array_merge( $excluded_user_ids, $user_ids )
		);

		wordpoints_update_maybe_network_option(
			'wordpoints_excluded_users'
			, $excluded_user_ids
		);

		$this->feedback->success(
			sprintf(
				__( 'Imported %s excluded users.', 'wordpoints-importer' )
				, count( $user_ids )
			)
		);
	}

	/**
	 * Import the points settings to the points hooks.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings The settings for the points component import.
	 */
	protected function import_points_settings( $settings ) {

		$this->feedback->info( __( 'Importing points hooks&hellip;', 'wordpoints-importer' ) );

		$options = array(
			'cp_comment_points'     => 'comment',
			'cp_post_points'        => 'post',
			'cp_reg_points'         => 'registration',
			'cp_post_author_points' => 'comment_received',
		);

		// Don't import this module setting if the module isn't active.
		if ( function_exists( 'cp_module_activated' ) && ! cp_module_activated( 'post_author_points' ) ) {
			unset( $options['cp_post_author_points'] );
		}

		$imported = 0;

		foreach ( $options as $option => $type ) {

			$points = get_option( $option );

			if ( wordpoints_posint( $points ) ) {

				$added = $this->add_points_hook(
					"wordpoints_{$type}_points_hook"
					, $settings['points_type']
					, array( 'points' => $points )
				);

				if ( $added ) {
					$imported++;
				}
			}
		}

		if ( $this->import_daily_points_hook( $settings ) ) {
			$imported++;
		}

		$this->feedback->success(
			sprintf( __( 'Imported %s points hooks.', 'wordpoints-importer' ), $imported )
		);
	}

	/**
	 * Import the settings for the Daily Points module to a points hook.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings The settings for the points component import.
	 *
	 * @return bool True if the settings were imported, false otherwise.
	 */
	protected function import_daily_points_hook( $settings ) {

		// Don't import this module setting if the module isn't active.
		if ( function_exists( 'cp_module_activated' ) && ! cp_module_activated( 'dailypoints' ) ) {
			return false;
		}

		$points = get_option( 'cp_module_dailypoints_points' );
		$period = get_option( 'cp_module_dailypoints_time' );

		if ( ! wordpoints_int( $points ) || ! wordpoints_posint( $period ) ) {
			return false;
		}

		return $this->add_points_hook(
			'wordpoints_periodic_points_hook'
			, $settings['points_type']
			, array( 'points' => $points, 'period' => $period )
		);
	}

	/**
	 * Programmatically create a new instance of a points hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_type   The type of hook to create.
	 * @param string $points_type The slug of the points type the hook is for.
	 * @param array  $instance    The arguments for the instance.
	 *
	 * @return bool True if added successfully, or false on failure.
	 */
	private function add_points_hook( $hook_type, $points_type, $instance = array() ) {

		$hook = WordPoints_Points_Hooks::get_handler_by_id_base( $hook_type );

		if ( ! $hook instanceof WordPoints_Points_Hook ) {
			return false;
		}

		$number = $hook->next_hook_id_number();

		$points_types_hooks = WordPoints_Points_Hooks::get_points_types_hooks();
		$points_types_hooks[ $points_type ] = $hook->get_id( $number );
		WordPoints_Points_Hooks::save_points_types_hooks( $points_types_hooks );

		$hook->update_callback( $instance, $number );

		return true;
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
	 * @return object[]|false The rows, or false.
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
		); // WPCS: cache OK.

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
	 * @return object[]|false The rows from the database.
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
		); // WPCS: cache OK.

		if ( ! is_array( $logs ) ) {
			$this->feedback->error( __( 'Unable to retrieve the logs from CubePoints&hellip;', 'wordpoints-importer' ) );
			return false;
		}

		return $logs;
	}

	/**
	 * Import ranks.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings The import settings for the ranks component.
	 */
	public function import_ranks( $settings ) {

		$this->feedback->info( __( 'Importing ranks&hellip;', 'wordpoints-importer' ) );

		$ranks_data = get_option( 'cp_module_ranks_data' );

		if ( empty( $ranks_data ) || ! is_array( $ranks_data ) ) {
			$this->feedback->error( __( 'No ranks found.', 'wordpoints-importer' ) );
			return;
		}

		$i = 0;

		// The base rank already exists, so we just update it.
		if ( isset( $ranks_data[0] ) ) {

			wordpoints_update_rank(
				WordPoints_Rank_Groups::get_group( $settings['rank_group'] )->get_rank( 0 )
				, $ranks_data[0]
				, 'base'
				, $settings['rank_group']
				, 0
			);

			$i++;

			unset( $ranks_data[0] );
		}

		$points_type = substr( $settings['rank_group'], strlen( 'points_type-' ) );
		$rank_type = 'points-' . $points_type;

		ksort( $ranks_data );

		foreach ( $ranks_data as $points => $rank_name ) {

			wordpoints_add_rank(
				$rank_name
				, $rank_type
				, $settings['rank_group']
				, $i
				, array( 'points' => $points, 'points_type' => $points_type )
			);

			$i++;
		}

		$this->feedback->success( sprintf( __( 'Imported %s ranks.', 'wordpoints-importer' ), $i ) );
	}
}

// EOF
