<?php

/**
 * Testcase for the imported post publish hooks.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.2.0
 */

/**
 * Tests that the imported post publish hooks behave correctly.
 *
 * @since 1.2.0
 *
 * @group importers
 * @group cubepoints
 *
 * @coversNothing
 */
class WordPoints_CubePoints_Importer_Post_Publish_Hook_Test
	extends WordPoints_Importer_Hook_UnitTestCase {

	/**
	 * @since 1.2.0
	 */
	protected $cubepoints_option = 'cp_post_points';

	/**
	 * @since 1.2.0
	 *
	 * @dataProvider data_provider_types
	 */
	public function test( $type ) {

		$this->before( $type );

		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create(
			array( 'post_author' => $user_id, 'post_status' => 'publish' )
		);

		$this->assertEquals( 10, $this->get_user_points( $user_id ) );

		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );

		$this->assertEquals( 10, $this->get_user_points( $user_id ) );

		wp_delete_post( $post_id, true );

		$this->assertEquals( 10, $this->get_user_points( $user_id ) );
	}
}

// EOF
