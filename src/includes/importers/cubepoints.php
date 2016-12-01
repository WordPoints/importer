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
	 * Reversible log types indexed by the reversing log type.
	 *
	 * This information is used to set the `auto_reversed` and `original_log_id`
	 * points log metadata for the imported logs.
	 *
	 * @since 1.2.0
	 *
	 * @see WordPoints_CubePoints_Importer::import_points_log()
	 *
	 * @var array
	 */
	protected $reversible_log_types = array(
		'comment_remove'      => 'comment',
		'post_comment_remove' => 'post_comment',
	);

	/**
	 * Primary entity slugs for each imported log type.
	 *
	 * These are points log meta keys under which to save the entity ID in the
	 * CubePoints `data` log field.
	 *
	 * @since 1.2.0
	 *
	 * @see WordPoints_CubePoints_Importer::import_points_log()
	 *
	 * @var array
	 */
	protected $log_type_entities = array(
		'comment'             => 'comment',
		'comment_remove'      => 'comment',
		'post'                => 'post',
		'post_comment'        => 'comment',
		'post_comment_remove' => 'comment',
		'register'            => 'user',
	);

	/**
	 * IDs of reversible logs, indexed by CubePoints log type and object ID.
	 *
	 * @since 1.2.0
	 *
	 * @see WordPoints_CubePoints_Importer::import_points_log()
	 *
	 * @var int[][]
	 */
	protected $reversible_log_ids;

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
					'label' => __( 'Points Reactions', 'wordpoints-importer' ),
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

		$this->feedback->info( __( 'Importing points reactions&hellip;', 'wordpoints-importer' ) );

		$options = array(
			'cp_comment_points'     => array(
				'event' => 'comment_leave\post',
				'target' => array( 'comment\post', 'author', 'user' ),
				/* translators: The post type name */
				'log_text' => __( 'Comment on a %s.', 'wordpoints-importer' ),
				/* translators: The post type name */
				'description' => __( 'Commenting on a %s.', 'wordpoints-importer' ),
				'legacy_log_type' => 'cubepoints-comment',
				'legacy_meta_key' => 'comment',
			),
			'cp_post_points'        => array(
				'event' => 'post_publish\post',
				'target' => array( 'post\post', 'author', 'user' ),
				/* translators: The post type name */
				'log_text' => __( 'Published a Post.', 'wordpoints-importer' ),
				/* translators: The post type name */
				'description' => __( 'Publishing a Post.', 'wordpoints-importer' ),
				'legacy_log_type' => 'cubepoints-post',
				'legacy_meta_key' => 'post',
				// CubePoints doesn't remove points when a post is deleted.
				'blocker' => array( 'toggle_off' => true ),
				'points_legacy_repeat_blocker' => array( 'toggle_on' => true ),
			),
			'cp_reg_points'         => array(
				'event' => 'user_register',
				'log_text' => __( 'Registration.', 'wordpoints-importer' ),
				'description' => __( 'Registration.', 'wordpoints-importer' ),
				'legacy_log_type' => 'cubepoints-register',
			),
			'cp_post_author_points' => array(
				'event' => 'comment_leave\post',
				'target' => array( 'comment\post', 'post\post', 'post\post', 'author', 'user' ),
				/* translators: The post type name */
				'log_text' => __( 'Received a comment on a %s.', 'wordpoints-importer' ),
				/* translators: The post type name */
				'description' => __( 'Receiving a comment on a %s.', 'wordpoints-importer' ),
				'legacy_log_type' => 'cubepoints-post_author',
				'legacy_meta_key' => 'comment',
			),
		);

		// Don't import this module setting if the module isn't active.
		if ( function_exists( 'cp_module_activated' ) && ! cp_module_activated( 'post_author_points' ) ) {
			unset( $options['cp_post_author_points'] );
		}

		$imported = 0;

		foreach ( $options as $option => $hook_settings ) {

			$points = get_option( $option );

			if ( wordpoints_posint( $points ) ) {

				$hook_settings['points'] = $points;
				$hook_settings['points_type'] = $settings['points_type'];

				if (
					// The CubePoints post points were only awarded for Posts.
					'post_publish\post' !== $hook_settings['event']
					&& strpos( $hook_settings['event'], '\post' )
				) {

					$post_type_slugs = get_post_types( array( 'public' => true ) );

					foreach ( $post_type_slugs as $post_type_slug ) {

						$added = $this->add_points_hook(
							$this->format_settings_for_post_type(
								$post_type_slug
								, $hook_settings
							)
						);

						if ( $added ) {
							$imported++;
						}
					}

				} else {
					if ( $this->add_points_hook( $hook_settings ) ) {
						$imported++;
					}
				}
			}

		} // End foreach ( $options ).

		if ( $this->import_daily_points_hook( $settings ) ) {
			$imported++;
		}

		$this->feedback->success(
			sprintf( __( 'Imported %s points reactions.', 'wordpoints-importer' ), $imported )
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
			array(
				'event' => 'user_visit',
				'target' => array( 'current:user' ),
				'reactor' => 'points_legacy',
				'points' => $points,
				'points_type' => $settings['points_type'],
				'points_legacy_periods' => array(
					'fire' => array(
						array(
							'length' => $period,
							'args' => array( array( 'current:user' ) ),
							'relative' => true,
						),
					),
				),
				'log_text' => __( 'Visiting the site.', 'wordpoints-importer' ),
				'description' => __( 'Visiting the site.', 'wordpoints-importer' ),
				'points_legacy_reversals' => array(),
				'legacy_log_type' => 'cubepoints-dailypoints',
			)
		);
	}

	/**
	 * Programmatically create a new instance of a points hook.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Now just accepts a single argument, $settings.
	 *
	 * @param array $settings The settings for this hook.
	 *
	 * @return bool True if added successfully, or false on failure.
	 */
	private function add_points_hook( $settings = array() ) {

		$reaction_store = wordpoints_hooks()->get_reaction_store( 'points' );

		$settings = array_merge(
			array(
				'target' => array( 'user' ),
				'reactor' => 'points_legacy',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
			, $settings
		);

		$reaction = $reaction_store->create_reaction( $settings );

		if ( ! $reaction instanceof WordPoints_Hook_ReactionI ) {
			return false;
		}

		return true;
	}

	/**
	 * Format the settings for a reaction for a particular post type.
	 *
	 * @since 1.2.0
	 *
	 * @param string $post_type The slug of the post type to format the settings for.
	 * @param array  $settings  The reaction settings.
	 *
	 * @return array The settings modified for this particular post type.
	 */
	protected function format_settings_for_post_type( $post_type, $settings ) {

		$settings['event'] = str_replace(
			'\post'
			, '\\' . $post_type
			, $settings['event']
		);

		$settings['target'] = str_replace(
			'\post'
			, '\\' . $post_type
			, $settings['target']
		);

		$labels = get_post_type_labels( get_post_type_object( $post_type ) );

		$settings['log_text'] = sprintf( $settings['log_text'], $labels->singular_name );
		$settings['description'] = sprintf( $settings['description'], $labels->singular_name );

		return $settings;
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

		$start = 0;

		while ( $logs = $this->get_next_points_logs_batch( $start ) ) {

			$start += count( $logs );

			foreach ( $logs as $log ) {

				$this->import_points_log(
					$log->uid
					, $log->points
					, $settings['points_type']
					, "cubepoints-{$log->type}"
					, array(
						'cubepoints_type' => $log->type,
						'cubepoints_data' => $log->data,
					)
					, date( 'Y-m-d H:i:s', $log->timestamp )
				);
			}

			unset( $logs );
		}

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
				'text'        => $this->render_points_log_text( '', $user_id, $points, $points_type, $log_type, $meta ),
				'date'        => $date,
				'site_id'     => $wpdb->siteid,
				'blog_id'     => $wpdb->blogid,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
		);

		if ( false !== $result ) {

			$log_id = (int) $wpdb->insert_id;

			// Set auto_reversed and original_log_id metadata for reversed logs.
			foreach ( $this->reversible_log_types as $reverse_type => $type ) {

				if ( $meta['cubepoints_type'] === $type ) {

					// Save this log ID for later, in case this log was reversed.
					// cubepoints_data will contain the entity ID.
					$this->reversible_log_ids[ $type ][ $meta['cubepoints_data'] ] = $log_id;

				} elseif (
					$meta['cubepoints_type'] === $reverse_type
					&& isset( $this->reversible_log_ids[ $type ][ $meta['cubepoints_data'] ] )
				) {

					// This log was reverses another one. Set the original log ID.
					$meta['original_log_id'] = $this->reversible_log_ids[ $type ][ $meta['cubepoints_data'] ];

					// And mark the original as auto_reversed.
					wordpoints_add_points_log_meta( $meta['original_log_id'], 'auto_reversed', $log_id );

					// No need to keep this info anymore.
					unset( $this->reversible_log_ids[ $type ][ $meta['cubepoints_data'] ] );
				}
			}

			// Set the entity IDs to their own meta keys, for the sake of reversals.
			if ( isset( $this->log_type_entities[ $meta['cubepoints_type'] ] ) ) {
				$meta[ $this->log_type_entities[ $meta['cubepoints_type'] ] ] = $meta['cubepoints_data'];
			}

			foreach ( $meta as $meta_key => $meta_value ) {

				wordpoints_add_points_log_meta( $log_id, $meta_key, $meta_value );
			}

			do_action( 'wordpoints_points_log', $user_id, $points, $points_type, $log_type, $meta, $log_id );

		} // End if ( inserted successfully ).
	}

	/**
	 * Generate the log text when importing a points log.
	 *
	 * @since 1.0.0
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

		$logs = $wpdb->get_results( // WPCS: unprepared SQL OK.
			$wpdb->prepare( // WPCS: unprepared SQL OK.
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
