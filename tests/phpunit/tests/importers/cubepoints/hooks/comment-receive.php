<?php

/**
 * Testcase for the imported comment receive points hooks.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.2.0
 */

/**
 * Tests that the imported comment receive hooks behave correctly.
 *
 * @since 1.2.0
 *
 * @group importers
 * @group cubepoints
 *
 * @coversNothing
 */
class WordPoints_CubePoints_Importer_Comment_Receive_Hook_Test
	extends WordPoints_Importer_Hook_UnitTestCase {

	/**
	 * @since 1.2.0
	 */
	protected $cubepoints_option = 'cp_post_author_points';

	/**
	 * @since 1.2.0
	 *
	 * @dataProvider data_provider_types
	 */
	public function test( $type ) {

		cp_module_activation_set( 'post_author_points', 'active' );

		update_option( 'cp_post_points', 0 );

		$this->before( $type );

		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create(
			array( 'post_author' => $user_id )
		);

		$comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID' => $post_id,
				'comment_approved' => 0,
				'user_id' => $this->factory->user->create(),
			)
		);

		$this->assertSame( 0, $this->get_user_points( $user_id ) );

		wp_update_comment(
			array( 'comment_ID' => $comment_id, 'comment_approved' => 1 )
		);

		$this->assertSame( 10, $this->get_user_points( $user_id ) );

		wp_update_comment(
			array( 'comment_ID' => $comment_id, 'comment_approved' => 0 )
		);

		$this->assertSame( 0, $this->get_user_points( $user_id ) );
	}
}

// EOF
