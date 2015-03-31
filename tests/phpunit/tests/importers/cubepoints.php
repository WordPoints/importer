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
class WordPoints_CubePoints_Importer_Test extends WordPoints_Points_UnitTestCase {

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

		$this->importer = new WordPoints_CubePoints_Importer( 'Test CubePoints' );
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

		$this->assertEquals(
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

		$this->assertEquals(
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
	 */
	public function test_import_points_settings() {

		update_option( 'cp_comment_points',     10 );
		update_option( 'cp_post_points',        20 );
		update_option( 'cp_reg_points',         50 );

		$this->do_points_import( 'settings' );

		$this->assertHookImported( 'comment', 10 );
		$this->assertHookImported( 'post', 20 );
		$this->assertHookImported( 'registration', 50 );
	}

	/**
	 * Test importing the settings from the post author points module to points hooks.
	 *
	 * @since 1.1.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_points_settings
	 */
	public function test_import_post_author_points() {

		cp_module_activation_set( 'post_author_points', 'active' );

		update_option( 'cp_post_author_points', 15 );

		$this->do_points_import( 'settings' );

		$this->assertHookImported( 'comment_received', 15 );
	}

	/**
	 * Test importing the settings from the post author points module to points hooks.
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
			'periodic'
			, array( 'points' => 30, 'period' => DAY_IN_SECONDS )
		);
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
			$this->assertEquals( $points, wordpoints_get_points( $user_id, 'points' ) );
		}
	}

	/**
	 * Test importing the user's points.
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

		$query = new WordPoints_Points_Logs_Query( array( 'orderby' => 'id' ) );
		$logs = $query->get();

		$this->assertCount( 4, $logs );

		$log = $logs[2];

		$this->assertEquals( $user_id, $log->user_id );
		$this->assertEquals( 10, $log->points );
		$this->assertEquals( 'Testing things.', $log->text );
		$this->assertEquals( 'cubepoints', $log->log_type );
		$this->assertEquals( 'points', $log->points_type );
		$this->assertEquals( 'misc', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertEquals( 'Testing things.', wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );

		$log = $logs[0];

		$this->assertEquals( $user_id_2, $log->user_id );
		$this->assertEquals( 25, $log->points );
		$this->assertStringMatchesFormat( 'Post on "<a href="%s">Post title 1</a>"', $log->text );
		$this->assertEquals( 'cubepoints', $log->log_type );
		$this->assertEquals( 'points', $log->points_type );
		$this->assertEquals( 'post', wordpoints_get_points_log_meta( $log->id, 'cubepoints_type', true ) );
		$this->assertEquals( $post_id, wordpoints_get_points_log_meta( $log->id, 'cubepoints_data', true ) );
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
	 *
	 * @param string    $type     The type of hook.
	 * @param int|array $instance The instance settings, or just the points value.
	 */
	protected function assertHookImported( $type, $instance ) {

		$hook = WordPoints_Points_Hooks::get_handler_by_id_base(
			"wordpoints_{$type}_points_hook"
		);

		if ( is_int( $instance ) ) {
			$this->assertEquals( $instance, $hook->get_points() );
		} else {
			$instances = $hook->get_instances();
			$this->assertEquals( $instance, end( $instances ) );
		}
	}
}

// EOF
