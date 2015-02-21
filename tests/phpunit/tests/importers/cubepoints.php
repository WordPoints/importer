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
	 * Test that it returns false when CubePoints is not installed.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::is_available
	 */
	public function test_is_cubepoints_not_available() {

		delete_option( 'cp_db_version' );

		$this->assertFalse( $this->importer->is_available() );
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

		$this->assertEquals(
			$user_ids
			, wordpoints_get_excluded_users( 'tests' )
		);

		$this->assertCount( 4, $feedback->messages['info'] );
		$this->assertCount( 1, $feedback->messages['success'] );
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
	 * Test importing the user's points.
	 *
	 * @since 1.0.0
	 *
	 * @covers WordPoints_CubePoints_Importer::import_user_points
	 * @covers WordPoints_CubePoints_Importer::get_next_user_points_batch
	 */
	public function test_import_user_points() {

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$user_points = array();

		foreach ( array( 20, 10, 45 ) as $points ) {

			$user_id = $this->factory->user->create();
			cp_updatePoints( $user_id, $points );
			$user_points[ $user_id ] = $points;
		}

		$this->importer->do_import(
			array(
				'points' => array(
					'user_points' => '1',
					'_data' => array( 'points_type' => 'points' ),
				),
			)
			, $feedback
		);

		foreach ( $user_points as $user_id => $points ) {
			$this->assertEquals( $points, wordpoints_get_points( $user_id, 'points' ) );
		}

		$this->assertCount( 4, $feedback->messages['info'] );
		$this->assertCount( 1, $feedback->messages['success'] );
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

		$feedback = new WordPoints_Importer_Tests_Feedback();

		$user_id = $this->factory->user->create();
		cp_points( 'misc', $user_id, 10, 'Testing things.' );

		$user_id_2 = $this->factory->user->create();
		$post_id = $this->factory->post->create();
		cp_points( 'post', $user_id_2, 25, $post_id );

		$this->importer->do_import(
			array(
				'points' => array(
					'logs' => '1',
					'_data' => array( 'points_type' => 'points' ),
				),
			)
			, $feedback
		);

		$this->assertCount( 4, $feedback->messages['info'] );
		$this->assertCount( 1, $feedback->messages['success'] );

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
}

// EOF
