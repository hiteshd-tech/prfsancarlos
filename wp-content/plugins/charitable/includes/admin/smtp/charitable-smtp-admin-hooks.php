<?php
/**
 * Charitable SMTP Hooks.
 *
 * Action/filter hooks used for Charitable integration with SMTP.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable scripts.
 *
 * @see Charitable_Intergrations_SMTP::register_settings()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_SMTP::get_instance(), 'enqueue_scripts' ) );
