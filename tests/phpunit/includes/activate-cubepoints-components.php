<?php

/**
 * Activate CubePoints components during remote install for the PHPUnit tests.
 *
 * @package WordPoints_Importer\Tests
 * @since 1.2.1
 */

foreach ( $data as $module_slug ) {
	cp_module_activation_set( $module_slug, 'active' );
}

// EOF
