<?php
/**
 * Charitable Square Gateway Hooks for admin.
 *
 * Action/filter hooks used for handling payments through the Square gateway.
 *
 * @package     Charitable Square/Hooks/Gateway/Admin
 * @author      David Bisset
 * @copyright   Copyright (c) 2018, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue scripts and styles.
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Square_Admin::get_instance(), 'enqueue_scripts_styles' ) );

/**
 * Display admin notice about PHP version incompatibility.
 * This is handled by the init hook in Charitable_Square_Compatibility already.
 */
// add_action( 'admin_notices', array( Charitable_Square_Compatibility::get_instance(), 'display_version_notice_sitewide' ) );

// add_filter( 'charitable_admin_strings', array( Charitable_Square_Admin::get_instance(), 'javascript_strings' ) );

