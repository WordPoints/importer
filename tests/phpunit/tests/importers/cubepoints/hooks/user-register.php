<?php

/**
 * Testcase for the imported user register points hooks.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.2.0
 */

/**
 * Tests that the imported user register hooks behave correctly.
 *
 * @since 1.2.0
 *
 * @group importers
 * @group cubepoints
 *
 * @coversNothing
 */
class WordPoints_CubePoints_Importer_User_Register_Hook_Test
	extends WordPoints_Importer_Hook_UnitTestCase {

	/**
	 * @since 1.2.0
	 */
	protected $cubepoints_option = 'cp_reg_points';

	/**
	 * @since 1.2.0
	 *
	 * @dataProvider data_provider_types
	 */
	public function test( $type ) {

		$this->before( $type );

		$user_id = $this->factory->user->create();

		$this->assertEquals( 10, $this->get_user_points( $user_id ) );

		self::delete_user( $user_id );

		$this->assertEquals( 0, $this->get_user_points( $user_id ) );
	}
}

// EOF
