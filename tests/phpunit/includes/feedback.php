<?php

/**
 * Feedback class used in the tests.
 *
 * @package WordPoints_Import\Tests
 * @since 1.0.0
 */

/**
 * Feedback class used in the tests.
 *
 * @since 1.0.0
 */
class WordPoints_Importer_Tests_Feedback extends WordPoints_Importer_Feedback {

	/**
	 * The messages that have been reported.
	 *
	 * @since 1.0.0
	 *
	 * @var string[][]
	 */
	public $messages = array();

	/**
	 * @since 1.0.0
	 */
	protected function _send( $message, $type = 'info' ) {

		$this->messages[ $type ][] = $message;
	}
}

// EOF
