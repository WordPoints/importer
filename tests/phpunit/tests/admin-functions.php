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
class WordPoints_Importer_Admin_Test extends WordPoints_PHPUnit_TestCase_Points {

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
		$this->assertSame( array(), $feedback->messages );
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
		$this->assertSame( array(), $feedback->messages );
	}

	/**
	 * Test that a rank group must be supplied.
	 *
	 * @since 1.1.0
	 *
	 * @covers ::wordpoints_importer_validate_rank_group_setting
	 */
	public function test_validate_rank_group_setting_not_set() {

		wordpoints_register_points_ranks();

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_rank_group_setting(
			true
			, array()
			, $feedback
		);

		$this->assertFalse( $valid );
		$this->assertCount( 1, $feedback->messages['warning'] );
	}

	/**
	 * Test that the rank group supplied must be valid.
	 *
	 * @since 1.1.0
	 *
	 * @covers ::wordpoints_importer_validate_rank_group_setting
	 */
	public function test_validate_rank_group_setting_invalid() {

		wordpoints_register_points_ranks();

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_rank_group_setting(
			true
			, array( 'rank_group' => 'invalid' )
			, $feedback
		);

		$this->assertFalse( $valid );
		$this->assertCount( 1, $feedback->messages['warning'] );
	}

	/**
	 * Test that it returns true when supplied a valid rank group.
	 *
	 * @since 1.1.0
	 *
	 * @covers ::wordpoints_importer_validate_rank_group_setting
	 */
	public function test_validate_rank_group_setting_valid() {

		wordpoints_register_points_ranks();

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_rank_group_setting(
			true
			, array( 'rank_group' => 'points_type-points' )
			, $feedback
		);

		$this->assertTrue( $valid );
		$this->assertSame( array(), $feedback->messages );
	}

	/**
	 * Test that it returns false when supplied a valid rank group if $valid is false.
	 *
	 * @since 1.1.0
	 *
	 * @covers ::wordpoints_importer_validate_rank_group_setting
	 */
	public function test_validate_rank_group_setting_valid_false() {

		wordpoints_register_points_ranks();

		$feedback = new WordPoints_Importer_Tests_Feedback;

		$valid = wordpoints_importer_validate_rank_group_setting(
			false
			, array( 'rank_group' => 'points_type-points' )
			, $feedback
		);

		$this->assertFalse( $valid );
		$this->assertSame( array(), $feedback->messages );
	}
}

// EOF
