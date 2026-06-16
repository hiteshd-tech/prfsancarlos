<?php
/**
 * Charitable Privacy Compliance Hooks.
 *
 * Action/filter hooks used for Charitable integration with Privacy Compliance.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable scripts.
 *
 * @see Charitable_Intergrations_Privacy_Compliance::enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_Privacy_Compliance::get_instance(), 'enqueue_scripts' ) );
