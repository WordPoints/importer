<?php

/**
 * Feedback class used to give the user feedback during import.
 *
 * @package WordPoints_Import
 * @since 1.0.0
 */

/**
 * Class to use to provide feedback to a user while a process is running.
 *
 * Examples of processes where this might be useful are upgrades and imports.
 *
 * @since 1.0.0
 */
class WordPoints_Importer_Feedback {

	/**
	 * Send an info message.
	 *
	 * Use this to give the user any info that wouldn't be handles by the other
	 * methods.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The info message.
	 */
	public function info( $message ) {
		$this->_send( $message, 'info' );
	}

	/**
	 * Send a success message.
	 *
	 * Use this when you want to let the user know that something was a success.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The success message.
	 */
	public function success( $message ) {
		$this->_send( $message, 'success' );
	}

	/**
	 * Send an error message.
	 *
	 * Use this when you want to let the user know that there was an error.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The error message.
	 */
	public function error( $message ) {
		$this->_send( $message, 'error' );
	}

	/**
	 * Send a warning message.
	 *
	 * Use this to warn the user about something. A warning doesn't necessarily imply
	 * an error, but that something unexpected has occured that the user might need
	 * to know.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The warning message.
	 */
	public function warning( $message ) {
		$this->_send( $message, 'warning' );
	}

	//
	// Protected Methods.
	//

	/**
	 * Send a message to the user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The feedback message.
	 * @param string $type    The type of message: 'info' (defuault), 'sucess',
	 *                        'error', 'warning'.
	 */
	protected function _send( $message, $type = 'info' ) { // @codingStandardsIgnoreLine

		?>

		<p class="wordpoints-feedback wordpoints-feedback-<?php echo esc_attr( $type ); ?>">
			<?php echo wp_kses( $message, 'wordpoints_importer_feedback' ); ?>
		</p>

		<?php
	}
}

// EOF
