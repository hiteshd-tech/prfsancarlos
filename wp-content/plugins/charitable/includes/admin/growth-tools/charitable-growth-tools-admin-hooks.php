<?php
/**
 * Charitable Growth Tools Hooks.
 *
 * Action/filter hooks used for Charitable Growth Tools.
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
 * Register Charitable growth tool scripts.
 *
 * @see Charitable_Guide_Tools::enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Guide_Tools::get_instance(), 'enqueue_scripts' ) );

/**
 * Get the content for the dashboard notice via AJAX.
 *
 * @see Charitable_Guide_Tools::ajax_get_dashboard_notice_html()
 */
add_action( 'wp_ajax_ajax_get_dashboard_notice_html', array( Charitable_Guide_Tools::get_instance(), 'ajax_get_dashboard_notice_html' ) );

