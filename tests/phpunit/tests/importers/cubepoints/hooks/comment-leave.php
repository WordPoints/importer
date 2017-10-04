<?php

/**
 * Testcase for the imported comment leave points hooks.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.2.0
 */

/**
 * Tests that the imported comment leave hooks behave correctly.
 *
 * @since 1.2.0
 *
 * @group importers
 * @group cubepoints
 *
 * @coversNothing
 */
class WordPoints_CubePoints_Importer_Comment_Leave_Hook_Test
	extends WordPoints_Importer_Hook_UnitTestCase {

	/**
	 * @since 1.2.0
	 */
	protected $cubepoints_option = 'cp_comment_points';

	/**
	 * @since 1.2.0
	 *
	 * @dataProvider data_provider_types
	 */
	public function test( $type ) {

		$this->before( $type );

		update_option( 'cp_del_comment_points', 10 );

		$user_id = $this->factory->user->create();
		$post_id = $this->factory->post->create(
			array( 'post_author' => $this->factory->user->create() )
		);

		$comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID' => $post_id,
				'comment_approved' => 0,
				'user_id' => $user_id,
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
