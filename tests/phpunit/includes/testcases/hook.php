<?php

/**
 * Testcase for imported points hooks.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.2.0
 */

/**
 * Bootstrap for testing that imported points hooks maintain the correct behavior.
 *
 * Enables you to write a single generic test that can be tested against both
 * CubePoints and the imported hook. This ensures that they follow the same behavior.
 *
 * @since 1.2.0
 *
 * @group importers
 * @group cubepoints
 *
 * @coversNothing
 */
abstract class WordPoints_Importer_Hook_UnitTestCase
	extends WordPoints_Points_UnitTestCase {

	/**
	 * The option where CubePoints stores the number of points to award for this.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected $cubepoints_option;

	/**
	 * The type of hook being tested.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Test that the hook behaves properly.
	 *
	 * @since 1.2.0
	 *
	 * @param string $type The type of test.
	 *
	 * @dataProvider data_provider_types
	 */
	abstract public function test( $type );

	/**
	 * Data provider for types of tests.
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public function data_provider_types() {
		return array(
			'cubepoints' => array( 'cubepoints' ),
			'wordpoints' => array( 'wordpoints' ),
		);
	}

	/**
	 * Set up before a test.
	 *
	 * @since 1.2.0
	 *
	 * @param string $type The type of test being run.
	 */
	protected function before( $type ) {

		update_option( 'cp_reg_points', 0 );

		$this->type = $type;

		update_option( $this->cubepoints_option, 10 );

		if ( 'wordpoints' === $this->type ) {
			$this->do_points_import( 'settings' );
		}
	}

	/**
	 * Get the number of points a user has.
	 *
	 * @since 1.2.0
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return int The number of points the user has.
	 */
	protected function get_user_points( $user_id ) {
		if ( 'cubepoints' === $this->type ) {
			return cp_getPoints( $user_id );
		} else {
			return wordpoints_get_points( $user_id, 'points' );
		}
	}

	/**
	 * Do the import for the points settings.
	 *
	 * @since 1.2.0
	 *
	 * @param string $type The type of points import.
	 */
	protected function do_points_import( $type ) {

		$importer = new WordPoints_CubePoints_Importer( 'Test CubePoints' );
		$feedback = new WordPoints_Importer_Tests_Feedback();

		$importer->do_import(
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
}

// EOF
