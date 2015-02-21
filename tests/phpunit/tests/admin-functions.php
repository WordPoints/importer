<?php

/**
 * Testcase for miscellaneous functions from the admin code.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

/**
 * Test miscellaneous functions from the admin code.
 *
 * @since 1.0.0
 */
class WordPoints_Importer_Admin_Test extends WordPoints_Points_UnitTestCase {

	/**
	 * Test that a points type must be supplied.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_importer_validate_points_type_setting
	 */
	public function test_validate_points_type_setting_not_set() {

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_points_type_setting(
			true
			, array()
			, $feedback
		);

		$this->assertFalse( $valid );
		$this->assertCount( 1, $feedback->messages['warning'] );
	}

	/**
	 * Test that the points type supplied must be valid.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_importer_validate_points_type_setting
	 */
	public function test_validate_points_type_setting_invalid() {

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_points_type_setting(
			true
			, array( 'points_type' => 'invalid' )
			, $feedback
		);

		$this->assertFalse( $valid );
		$this->assertCount( 1, $feedback->messages['warning'] );
	}

	/**
	 * Test that it returns true when supplied a valid points type.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_importer_validate_points_type_setting
	 */
	public function test_validate_points_type_setting_valid() {

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_points_type_setting(
			true
			, array( 'points_type' => 'points' )
			, $feedback
		);

		$this->assertTrue( $valid );
		$this->assertEmpty( $feedback->messages );
	}

	/**
	 * Test that it returns false when supplied a valid points type if $valid is false.
	 *
	 * @since 1.0.0
	 *
	 * @covers ::wordpoints_importer_validate_points_type_setting
	 */
	public function test_validate_points_type_setting_valid_false() {

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_points_type_setting(
			false
			, array( 'points_type' => 'points' )
			, $feedback
		);

		$this->assertFalse( $valid );
		$this->assertEmpty( $feedback->messages );
	}
}

// EOF
