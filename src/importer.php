<?php

/**
 * Module Name: Importer
 * Author:      J.D. Grimes
 * Author URI:  http://codesymphony.co/
 * Version:     1.0.0
 * License:     GPLv2+
 * Description: Import your data from CubePoints to WordPoints.
 * Text Domain: wordpoints-importer
 * Domain Path: /languages
 *
 * ---------------------------------------------------------------------------------|
 * Copyright 2014  J.D. Grimes  (email : jdg@codesymphony.co)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or later, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * ---------------------------------------------------------------------------------|
 *
 * @package WordPoints_Importer
 * @version 1.0.0
 * @author  J.D. Grimes <jdg@codesymphony.co>
 * @license GPLv2+
 */

/**
 * The module's base class.
 *
 * @since 1.0.0
 */

/**
 * The base feedback class.
 *
 * @since 1.0.0
 */
require_once( dirname( __FILE__ ) . '/includes/class-feedback.php' );

/**
 * The base importer class.
 *
 * @since 1.0.0
 */
require_once( dirname( __FILE__ ) . '/includes/class-importer.php' );

/**
 * The module's general functions.
 *
 * @since 1.0.0
 */
require_once( dirname( __FILE__ ) . '/includes/functions.php' );

if ( is_admin() ) {

	/**
	 * The module's admin-side code.
	 *
	 * @since 1.0.0
	 */
	require_once( dirname( __FILE__ ) . '/admin/admin.php' );
}

// EOF
