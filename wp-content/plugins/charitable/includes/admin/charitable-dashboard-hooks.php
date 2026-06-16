<?php
/**
 * Charitable Dashboard Hooks.
 *
 * Action/filter hooks used for dashboard functionality in the admin.
 *
 * @package   Charitable/Functions/Dashboard
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get dashboard data via ajax, probably as a result of a changing UI element.
 *
 * @see Charitable_Dashboard_Ajax::get_dashboard_data()
 */
add_action( 'wp_ajax_charitable_dashboard_data', array( Charitable_Dashboard_Ajax::get_instance(), 'get_dashboard_data' ) );
