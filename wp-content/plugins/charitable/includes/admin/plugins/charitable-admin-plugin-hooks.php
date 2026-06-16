<?php
/**
 * Charitable Admin Third Party Plugin Related Hooks.
 *
 * Action/filter hooks used for setting up activity monitoring in the admin.
 *
 * @package   Charitable/Functions/Activity
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
 * Install plugin.
 *
 * @see Charitable_Admin_Plugins_Third_Party::install_plugin()
 */
add_action( 'wp_ajax_charitable_install_plugin', array( Charitable_Admin_Plugins_Third_Party::get_instance(), 'install_plugin' ), 11 );

/**
 * Activate plugin.
 *
 * @see Charitable_Admin_Plugins_Third_Party::activate_plugin()
 */
add_action( 'wp_ajax_charitable_activate_plugin', array( Charitable_Admin_Plugins_Third_Party::get_instance(), 'activate_plugin' ), 11 );

/**
 * Get Plugin.
 *
 * @see Charitable_Admin_Plugins_Third_Party::get_plugins()
 */
add_action( 'wp_ajax_charitable_get_plugins', array( Charitable_Admin_Plugins_Third_Party::get_instance(), 'get_plugins' ), 11 );

/**
 * Install Charitable addon.
 *
 * @see Charitable_Dashboard::ajax_install_charitable_addon()
 */
add_action( 'wp_ajax_charitable_install_charitable_addon', array( Charitable_Dashboard::get_instance(), 'ajax_install_charitable_addon' ), 11 );

/**
 * Activate Charitable addon.
 *
 * @see Charitable_Dashboard::ajax_activate_charitable_addon()
 */
add_action( 'wp_ajax_charitable_activate_charitable_addon', array( Charitable_Dashboard::get_instance(), 'ajax_activate_charitable_addon' ), 11 );
