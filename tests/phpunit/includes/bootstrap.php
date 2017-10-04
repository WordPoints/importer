<?php

/**
 * PHPUnit tests bootstrap for the extension.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.0.0
 */

/**
 * The extension's admin-side code.
 *
 * @since 1.2.1
 */
require_once WORDPOINTS_IMPORTER_TESTS_DIR . '/../../src/admin/admin.php';

/**
 * Feedback object used in the tests.
 *
 * @since 1.0.0
 */
require_once WORDPOINTS_IMPORTER_TESTS_DIR . '/includes/feedback.php';

/**
 * Mocks used in the tests.
 *
 * @since 1.0.0
 */
require_once WORDPOINTS_IMPORTER_TESTS_DIR . '/includes/mocks.php';

/**
 * Testcase for testing hooks.
 *
 * @since 1.2.0
 */
require_once WORDPOINTS_IMPORTER_TESTS_DIR . '/includes/testcases/hook.php';

// EOF
