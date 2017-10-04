<?php

/**
 * Testcase for the CubePoints importer class.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

/**
 * Tests for the CubePoints importer.
 *
 * @since 1.0.0
 *
 * @group importers
 * @group cubepoints
 */
class WordPoints_CubePoints_Importer_Test
	extends WordPoints_PHPUnit_TestCase_Points {

	/**
	 * The importer used in the tests.
	 *
	 * @since 1.0.0
	 *
	 * @var WordPoints_CubePoints_Importer
	 */
	protected $importer;

	/**
	 * @since 1.0.0
	 */
	public function setUp() {

		parent::setUp();

		// These are usually inactive by default. We activate them in the tests
		// bootstrap so that they will be fully loaded, but deactivate them here to
		// restore default behavior.
		cp_module_activation_set( 'post_author_points', false );
		cp_module_activation_set( 'dailypoints', false );

		$this->importer = new WordPoints_CubePoints_Importer( 'Test CubePoints' );
	}

	/**
	 * @since 1.1.0
	 */
	public function tearDown() {

		WordPoints_Rank_Groups::deregister_group( 'points_type-points' );
		WordPoints_Rank_Types::deregister_type( 'points-points' );

		parent::tearDown();
	}

	/**
	 * Test that it returns true when CubePoints is installed.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::is_cubepoints_installed
	 */
	public function test_is_cubepoints_installed() {

		$this->assertTrue( $this->importer->is_cubepoints_installed() );
	}

	/**
	 * Test that it returns false when CubePoints is not installed.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::is_cubepoints_installed
	 */
	public function test_is_cubepoints_not_installed() {

		delete_option( 'cp_db_version' );

		$this->assertFalse( $this->importer->is_cubepoints_installed() );
	}

	/**
	 * Test that it returns true when CubePoints is installed.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::is_available
	 */
	public function test_is_cubepoints_is_available() {

		$this->assertTrue( $this->importer->is_available() );
	}

	/**
	 * Test that it returns a WP_Error when CubePoints is not installed.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::is_available
	 */
	public function test_is_cubepoints_not_available() {

		delete_option( 'cp_db_version' );

		$this->assertWPError( $this->importer->is_available() );
	}

	/**
	 * Test that it returns true when CubePoints is active.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::is_cubepoints_active
	 */
	public function test_is_cubepoints_active() {

		$this->assertSame(
			function_exists( 'cp_ready' )
			, $this->importer->is_cubepoints_active()
		);
	}

	/**
	 * Test importing the excluded users.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_excluded_users
	 */
	public function test_import_excluded_users() {

		$user_ids = $this->factory->user->create_many( 3 );
		$user_logins = array();

		foreach ( $user_ids as $user_id ) {
			$user_logins[] = get_userdata( $user_id )->user_login;
		}

		update_option( 'cp_topfilter', $user_logins );

		$this->do_points_import( 'excluded_users' );

		$this->assertSame(
			$user_ids
			, wordpoints_get_excluded_users( 'tests' )
		);
	}

	/**
	 * Test importing the excluded users gives a warning if there are none.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_excluded_users
	 */
	public function test_import_excluded_users_none() {

		delete_option( 'cp_topfilter' );

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$this->importer->do_import(
			array(
				'points' => array(
					'excluded_users' => '1',
					'_data' => array( 'points_type' => 'points' ),
				),
			)
			, $feedback
		);

		$this->assertCount( 1, $feedback->messages['warning'] );
	}

	/**
	 * Test importing the points settings to points hooks.
	 *
	 * @since 1.1.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 * @covers WordPoints_CubePoints_Importer::import_daily_points_hook
	 * @covers WordPoints_CubePoints_Importer::format_settings_for_post_type
	 */
	public function test_import_points_settings() {

		update_option( 'cp_comment_points',     10 );
		update_option( 'cp_post_points',        20 );
		update_option( 'cp_reg_points',         50 );

		$this->do_points_import( 'settings' );

		$this->assertHookImported(
			array(
				'event' => 'comment_leave\post',
				'target' => array( 'comment\post', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 10,
				'points_type' => 'points',
				'log_text' => 'Comment on a Post.',
				'description' => 'Commenting on a Post.',
				'legacy_log_type' => 'cubepoints-comment',
				'legacy_meta_key' => 'comment',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);

		$this->assertHookImported(
			array(
				'event' => 'comment_leave\page',
				'target' => array( 'comment\page', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 10,
				'points_type' => 'points',
				'log_text' => 'Comment on a Page.',
				'description' => 'Commenting on a Page.',
				'legacy_log_type' => 'cubepoints-comment',
				'legacy_meta_key' => 'comment',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);

		$this->assertHookImported(
			array(
				'event' => 'comment_leave\attachment',
				'target' => array( 'comment\attachment', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 10,
				'points_type' => 'points',
				'log_text' => 'Comment on a Media.',
				'description' => 'Commenting on a Media.',
				'legacy_log_type' => 'cubepoints-comment',
				'legacy_meta_key' => 'comment',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);

		$this->assertHookImported(
			array(
				'event' => 'post_publish\post',
				'target' => array( 'post\post', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 20,
				'points_type' => 'points',
				'log_text' => 'Published a Post.',
				'description' => 'Publishing a Post.',
				'blocker' => array( 'toggle_off' => true ),
				'legacy_log_type' => 'cubepoints-post',
				'legacy_meta_key' => 'post',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
				'points_legacy_repeat_blocker' => array( 'toggle_on' => true ),
			)
		);

		$reaction_store = wordpoints_hooks()->get_reaction_store( 'points' );

		$this->assertSame(
			array()
			, $reaction_store->get_reactions_to_event( 'post_publish\page' )
		);

		$this->assertSame(
			array()
			, $reaction_store->get_reactions_to_event( 'post_publish\attachment' )
		);

		$this->assertSame(
			array()
			, $reaction_store->get_reactions_to_event( 'media_upload' )
		);

		$this->assertHookImported(
			array(
				'event' => 'user_register',
				'target' => array( 'user' ),
				'reactor' => 'points_legacy',
				'points' => 50,
				'points_type' => 'points',
				'log_text' => 'Registration.',
				'description' => 'Registration.',
				'legacy_log_type' => 'cubepoints-register',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);
	}

	/**
	 * Test that it imports legacy points hooks on install.
	 *
	 * @since 1.0.0
	 *
	 * @coversNothing
	 */
	public function test_imported_post_points_hook_does_not_refire() {

		update_option( 'cp_post_points', 20 );

		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create(
			array(
				'post_author' => $user_id,
				'post_type'   => 'post',
			)
		);

		$this->assertSame( '120', cp_getPoints( $user_id ) );

		$this->factory->post->update_object(
			$post_id
			, array( 'post_status' => 'draft' )
		);

		$this->assertSame( '120', cp_getPoints( $user_id ) );

		$this->do_points_import( 'settings' );
		$this->do_points_import( 'user_points' );
		$this->do_points_import( 'logs' );

		$this->assertSame(
			120
			, wordpoints_get_points( $user_id, 'points' )
		);

		$this->factory->post->update_object(
			$post_id
			, array( 'post_status' => 'publish' )
		);

		$this->assertSame(
			120
			, wordpoints_get_points( $user_id, 'points' )
		);
	}

	/**
	 * Test importing the settings from the post author points module to points hooks.
	 *
	 * @since 1.1.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 * @covers WordPoints_CubePoints_Importer::format_settings_for_post_type
	 */
	public function test_import_post_author_points() {

		cp_module_activation_set( 'post_author_points', 'active' );

		update_option( 'cp_post_author_points', 15 );

		$this->do_points_import( 'settings' );

		$this->assertHookImported(
			array(
				'event' => 'comment_leave\post',
				'target' => array( 'comment\post', 'post\post', 'post\post', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 15,
				'points_type' => 'points',
				'log_text' => 'Received a comment on a Post.',
				'description' => 'Receiving a comment on a Post.',
				'legacy_log_type' => 'cubepoints-post_author',
				'legacy_meta_key' => 'comment',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);

		$this->assertHookImported(
			array(
				'event' => 'comment_leave\page',
				'target' => array( 'comment\page', 'post\page', 'post\page', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 15,
				'points_type' => 'points',
				'log_text' => 'Received a comment on a Page.',
				'description' => 'Receiving a comment on a Page.',
				'legacy_log_type' => 'cubepoints-post_author',
				'legacy_meta_key' => 'comment',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);

		$this->assertHookImported(
			array(
				'event' => 'comment_leave\attachment',
				'target' => array( 'comment\attachment', 'post\attachment', 'post\attachment', 'author', 'user' ),
				'reactor' => 'points_legacy',
				'points' => 15,
				'points_type' => 'points',
				'log_text' => 'Received a comment on a Media.',
				'description' => 'Receiving a comment on a Media.',
				'legacy_log_type' => 'cubepoints-post_author',
				'legacy_meta_key' => 'comment',
				'points_legacy_reversals' => array( 'toggle_off' => 'toggle_on' ),
			)
		);
	}

	/**
	 * Test importing the settings from the daily points module to points hooks.
	 *
	 * @since 1.1.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 * @covers WordPoints_CubePoints_Importer::import_daily_points_hook
	 */
	public function test_import_periodic_points() {

		cp_module_activation_set( 'dailypoints', 'active' );

		update_option( 'cp_module_dailypoints_points', 30 );
		update_option( 'cp_module_dailypoints_time', DAY_IN_SECONDS );

		$this->do_points_import( 'settings' );

		$this->assertHookImported(
			array(
				'event' => 'user_visit',
				'target' => array( 'current:user' ),
				'reactor' => 'points_legacy',
				'points' => 30,
				'points_type' => 'points',
				'log_text' => 'Visiting the site.',
				'description' => 'Visiting the site.',
				'points_legacy_periods' => array(
					'fire' => array(
						array(
							'length' => DAY_IN_SECONDS,
							'args' => array( array( 'current:user' ) ),
							'relative' => true,
						),
					),
				),
				'points_legacy_reversals' => array(),
				'legacy_log_type' => 'cubepoints-dailypoints',
			)
		);
	}

	/**
	 * Test that the imported user visit hook respects CubePoints's started periods.
	 *
	 * @since 1.2.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 * @covers WordPoints_CubePoints_Importer::import_daily_points_hook
	 */
	public function test_import_periodic_points_respect_old_periods() {

		if ( version_compare( $GLOBALS['wp_version'], '4.5', '>=' ) ) {
			$this->setExpectedDeprecated( 'get_currentuserinfo' );
		}

		cp_module_activation_set( 'dailypoints', 'active' );

		update_option( 'cp_module_dailypoints_points', 30 );
		update_option( 'cp_module_dailypoints_time', DAY_IN_SECONDS );

		$user_id = $this->factory->user->create();

		wp_set_current_user( $user_id );

		$this->assertSame( '100', cp_getPoints( $user_id ) );

		cp_module_dailypoints_checkTimer();

		$this->assertSame( '130', cp_getPoints( $user_id ) );

		// Running again shouldn't hit again.
		cp_module_dailypoints_checkTimer();

		$this->assertSame( '130', cp_getPoints( $user_id ) );

		$this->do_points_import( 'settings' );
		$this->do_points_import( 'user_points' );
		$this->do_points_import( 'logs' );

		$this->assertSame( 130, wordpoints_get_points( $user_id, 'points' ) );

		wordpoints_hooks()->get_sub_app( 'router' )->{'wp,10'}();

		$this->assertSame( 130, wordpoints_get_points( $user_id, 'points' ) );

		// Fast-forward and try again.
		global $wpdb;

		$id = $wpdb->get_var(
			"
				SELECT `id`
				FROM `{$wpdb->wordpoints_points_logs}`
				ORDER BY `id` DESC
				LIMIT 1
			"
		);

		// Don't go all the way yet.
		$updated = $wpdb->update(
			$wpdb->wordpoints_points_logs
			, array( 'date' => date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - DAY_IN_SECONDS + HOUR_IN_SECONDS ) )
			, array( 'id' => $id )
			, array( '%s' )
			, array( '%d' )
		);

		$this->assertSame( 1, $updated );

		// The periods cache will still hold the old date.
		$this->flush_cache();

		wordpoints_hooks()->get_sub_app( 'router' )->{'wp,10'}();

		// Points should have been awarded again yet.
		$this->assertSame( 130, wordpoints_get_points( $user_id, 'points' ) );

		// This time go all the way.
		$updated = $wpdb->update(
			$wpdb->wordpoints_points_logs
			, array( 'date' => date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - DAY_IN_SECONDS - 1 ) )
			, array( 'id' => $id )
			, array( '%s' )
			, array( '%d' )
		);

		$this->assertSame( 1, $updated );

		// The periods cache will still hold the old date.
		$this->flush_cache();

		wordpoints_hooks()->get_sub_app( 'router' )->{'wp,10'}();

		// Points should have been awarded again.
		$this->assertSame( 160, wordpoints_get_points( $user_id, 'points' ) );
	}

	/**
	 * Test the imported periods when the site has a positive GMT offset.
	 *
	 * @since 1.2.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 * @covers WordPoints_CubePoints_Importer::import_daily_points_hook
	 */
	public function test_import_periods_positive_gmt_offset() {

		update_option( 'gmt_offset', 5 );

		$this->test_import_periodic_points_respect_old_periods();
	}

	/**
	 * Test the imported periods when the site has a negative GMT offset.
	 *
	 * @since 1.2.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 * @covers WordPoints_CubePoints_Importer::import_daily_points_hook
	 */
	public function test_import_periods_negative_gmt_offset() {

		update_option( 'gmt_offset', -5 );

		$this->test_import_periodic_points_respect_old_periods();
	}

	/**
	 * Test importing the user's points.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_user_points
	 * @covers WordPoints_CubePoints_Importer::get_next_user_points_batch
	 */
	public function test_import_user_points() {

		$user_points = array();

		foreach ( array( 20, 10, 45 ) as $points ) {

			$user_id = $this->factory->user->create();
			cp_updatePoints( $user_id, $points );
			$user_points[ $user_id ] = $points;
		}

		$this->do_points_import( 'user_points' );

		foreach ( $user_points as $user_id => $points ) {
			$this->assertSame( $points, wordpoints_get_points( $user_id, 'points' ) );
		}
	}

	/**
	 * Test importing the points logs.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_logs
	 * @covers WordPoints_CubePoints_Importer::import_points_log
	 * @covers WordPoints_CubePoints_Importer::get_next_points_logs_batch
	 * @covers WordPoints_CubePoints_Importer::render_points_log_text
	 */
	public function test_import_points_logs() {

		remove_action( 'publish_post', 'cp_newPost' );

		$user_id = $this->factory->user->create();
		cp_points( 'misc', $user_id, 10, 'Testing things.' );

		$user_id_2 = $this->factory->user->create();
		$post_id = $this->factory->post->create();
		cp_points( 'post', $user_id_2, 25, $post_id );

		$this->do_points_import( 'logs' );

		$query = new WordPoints_Points_Logs_Query( array( 'order_by' => 'id' ) );
		$logs = $query->get();

		$this->assertCount( 4, $logs );

		$log = $logs[2];

		$this->assertSame( (string) $user_id, $log->user_id );
		$this->assertSame( '10', $log->points );
		$this->assertSame( 'Testing things.', $log->text );
		$this->assertSame( 'cubepoints-misc', $log->log_type );
		$this->assertSame( 'points', $log->points_type );
		$this->assertSame( 'misc', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertSame( 'Testing things.', wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );

		$log = $logs[0];

		$this->assertSame( (string) $user_id_2, $log->user_id );
		$this->assertSame( '25', $log->points );
		$this->assertStringMatchesFormat( 'Post on "<a href="%s">Post title %s</a>"', $log->text );
		$this->assertSame( 'cubepoints-post', $log->log_type );
		$this->assertSame( 'points', $log->points_type );
		$this->assertSame( 'post', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertSame( (string) $post_id, wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );
		$this->assertSame( (string) $post_id, wordpoints_get_points_log_meta( $log->id, 'post', true ) );
	}

	/**
	 * Test importing points logs that have been reversed.
	 *
	 * @since 1.2.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_logs
	 * @covers WordPoints_CubePoints_Importer::import_points_log
	 * @covers WordPoints_CubePoints_Importer::get_next_points_logs_batch
	 * @covers WordPoints_CubePoints_Importer::render_points_log_text
	 */
	public function test_import_points_logs_reversals() {

		remove_action( 'publish_post', 'cp_newPost' );
		remove_action( 'cp_comment_add', 'cp_module_post_author_points_comment_add' );
		remove_action( 'cp_comment_remove', 'cp_module_post_author_points_comment_remove' );

		update_option( 'cp_comment_points', 10 );
		update_option( 'cp_del_comment_points', 10 );

		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create();

		$comment_id = $this->factory->comment->create(
			array( 'user_id' => $user_id, 'comment_post_ID' => $post_id, 'comment_approved' => 0 )
		);

		wp_update_comment(
			array( 'comment_ID' => $comment_id, 'comment_approved' => 1 )
		);

		$user_id_2 = $this->factory->user->create();
		$comment_id_2 = $this->factory->comment->create(
			array( 'user_id' => $user_id_2, 'comment_post_ID' => $post_id, 'comment_approved' => 0 )
		);

		wp_update_comment(
			array( 'comment_ID' => $comment_id_2, 'comment_approved' => 1 )
		);

		// Now reverse the two transactions.
		wp_update_comment(
			array( 'comment_ID' => $comment_id, 'comment_approved' => 0 )
		);

		wp_update_comment(
			array( 'comment_ID' => $comment_id_2, 'comment_approved' => 0 )
		);

		$this->do_points_import( 'logs' );

		$query = new WordPoints_Points_Logs_Query(
			array( 'order_by' => 'id', 'order' => 'ASC' )
		);

		$logs = $query->get();

		$this->assertCount( 6, $logs );

		// The first log will be for when the first user was created, so we skip it.
		$log = $logs[1];

		$this->assertSame( (string) $user_id, $log->user_id );
		$this->assertSame( '10', $log->points );
		$this->assertSame( 'cubepoints-comment', $log->log_type );
		$this->assertSame( 'points', $log->points_type );
		$this->assertSame( 'comment', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertSame( (string) $comment_id, wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );
		$this->assertSame( (string) $comment_id, wordpoints_get_points_log_meta( $log->id, 'comment', true ) );
		$this->assertSame( $logs[4]->id, wordpoints_get_points_log_meta( $log->id, 'auto_reversed', true ) );

		// The third log is for when the second user was created, so we skip it, too.
		$log = $logs[3];

		$this->assertSame( (string) $user_id_2, $log->user_id );
		$this->assertSame( '10', $log->points );
		$this->assertSame( 'cubepoints-comment', $log->log_type );
		$this->assertSame( 'points', $log->points_type );
		$this->assertSame( 'comment', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertSame( (string) $comment_id_2, wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );
		$this->assertSame( (string) $comment_id_2, wordpoints_get_points_log_meta( $log->id, 'comment', true ) );
		$this->assertSame( $logs[5]->id, wordpoints_get_points_log_meta( $log->id, 'auto_reversed', true ) );

		$log = $logs[4];

		$this->assertSame( (string) $user_id, $log->user_id );
		$this->assertSame( '-10', $log->points );
		$this->assertSame( 'cubepoints-comment_remove', $log->log_type );
		$this->assertSame( 'points', $log->points_type );
		$this->assertSame( 'comment_remove', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertSame( (string) $comment_id, wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );
		$this->assertSame( (string) $comment_id, wordpoints_get_points_log_meta( $log->id, 'comment', true ) );
		$this->assertSame( $logs[1]->id, wordpoints_get_points_log_meta( $log->id, 'original_log_id', true ) );

		$log = $logs[5];

		$this->assertSame( (string) $user_id_2, $log->user_id );
		$this->assertSame( '-10', $log->points );
		$this->assertSame( 'cubepoints-comment_remove', $log->log_type );
		$this->assertSame( 'points', $log->points_type );
		$this->assertSame( 'comment_remove', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertSame( (string) $comment_id_2, wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );
		$this->assertSame( (string) $comment_id_2, wordpoints_get_points_log_meta( $log->id, 'comment', true ) );
		$this->assertSame( $logs[3]->id, wordpoints_get_points_log_meta( $log->id, 'original_log_id', true ) );
	}

	/**
	 * Test that ranks are imported.
	 *
	 * @since 1.1.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_ranks
	 */
	public function test_ranks_import() {

		update_option(
			'cp_module_ranks_data'
			, array( 0 => 'Newbie', 1000 => 'Biggie', 5000 => 'Oldie' )
		);

		wordpoints_register_points_ranks();

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$this->importer->do_import(
			array(
				'ranks' => array(
					'ranks' => '1',
					'_data' => array( 'rank_group' => 'points_type-points' ),
				),
			)
			, $feedback
		);

		$this->assertCount( 4, $feedback->messages['info'] );
		$this->assertCount( 1, $feedback->messages['success'] );

		$group = WordPoints_Rank_Groups::get_group( 'points_type-points' );

		$base_rank = wordpoints_get_rank( $group->get_rank( 0 ) );
		$this->assertSame( 'base', $base_rank->type );
		$this->assertSame( 'Newbie', $base_rank->name );

		$second_rank = wordpoints_get_rank( $group->get_rank( 1 ) );
		$this->assertSame( '1000', $second_rank->points );
		$this->assertSame( 'Biggie', $second_rank->name );

		$third_rank = wordpoints_get_rank( $group->get_rank( 2 ) );
		$this->assertSame( '5000', $third_rank->points );
		$this->assertSame( 'Oldie', $third_rank->name );
	}

	/**
	 * Test that there is an error if there are no ranks import.
	 *
	 * @since 1.1.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_ranks
	 */
	public function test_error_if_no_ranks_to_import() {

		wordpoints_register_points_ranks();

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$this->importer->do_import(
			array(
				'ranks' => array(
					'ranks' => '1',
					'_data' => array( 'rank_group' => 'points_type-points' ),
				),
			)
			, $feedback
		);

		$this->assertCount( 4, $feedback->messages['info'] );
		$this->assertCount( 1, $feedback->messages['error'] );

	}

	//
	// Helpers.
	//

	/**
	 * Do the import for the points settings.
	 *
	 * @since 1.1.0
	 *
	 * @param string $type The type of points import.
	 */
	protected function do_points_import( $type ) {

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$this->importer->do_import(
			array(
				'points' => array(
					$type => '1',
					'_data' => array( 'points_type' => 'points' ),
				),
			)
			, $feedback
		);

		$this->assertCount( 4, $feedback->messages['info'] );
		$this->assertCount( 1, $feedback->messages['success'] );
	}

	/**
	 * Assert that a hook was imported.
	 *
	 * Actually just checks that the hook exists.
	 *
	 * @since 1.1.0
	 * @since 1.2.0 Now just accepts a single parameter, $settings.
	 *
	 * @param array $settings The expected reaction settings.
	 */
	protected function assertHookImported( $settings ) {

		$reaction_store = wordpoints_hooks()->get_reaction_store( 'points' );

		$reactions = $reaction_store->get_reactions_to_event( $settings['event'] );

		$this->assertNotEmpty( $reactions );

		foreach ( $reactions as $reaction ) {
			if ( $settings === $reaction->get_all_meta() ) {
				$this->assertSame( $settings, $reaction->get_all_meta() );
				return;
			}
		}

		$this->assertSameSetsWithIndex( $settings, $reaction->get_all_meta() );
	}
}

// EOF
