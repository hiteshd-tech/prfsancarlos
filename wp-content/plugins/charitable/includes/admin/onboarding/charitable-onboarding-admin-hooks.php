<?php
/**
 * Charitable Onboarding Hooks.
 *
 * Action/filter hooks used for Charitable Onboarding.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 * @version   1.8.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable onboarding scripts.
 *
 * @see Charitable_Onboarding::enqueue_scripts()
 */
// add_action( 'admin_enqueue_scripts', array( Charitable_Onboarding::get_instance(), 'enqueue_scripts' ) );

/**
 * Register the custom scripts and styles.
 *
 * @see     Charitable_Setup::enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Setup::get_instance(), 'enqueue_scripts' ) );

/**
 * Process setup steps via ajax.
 *
 * @see    Charitable_Setup::maybe_process_step()
 * @see    Charitable_Setup::ajax_activate_feature()
 * @see    Charitable_Setup::ajax_process_campaign()
 * @see    Charitable_Setup::ajax_process_complete()
 * @see    Charitable_Setup::ajax_process_tracking()
 */
add_action( 'wp_ajax_charitable_setup_process_meta', array( Charitable_Setup::get_instance(), 'maybe_process_step' ) );

add_action( 'wp_ajax_charitable_activate_feature', array( Charitable_Setup::get_instance(), 'ajax_activate_feature' ) );

add_action( 'wp_ajax_charitable_setup_process_campaign', array( Charitable_Setup::get_instance(), 'ajax_process_campaign' ) );

add_action( 'wp_ajax_charitable_setup_process_complete', array( Charitable_Setup::get_instance(), 'ajax_process_complete' ) );

add_action( 'wp_ajax_charitable_setup_process_tracking', array( Charitable_Setup::get_instance(), 'ajax_process_tracking' ) );


/**
 * Register Charitable checklist scripts.
 *
 * @see Charitable_Checklist::enqueue_scripts()
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Checklist::get_instance(), 'enqueue_styles_and_scripts' ) );

/**
 * Add the checklist HTML to the footer.
 *
 * @see Charitable_Checklist::maybe_add_checklist_html()
 */
add_action( 'admin_footer', array( Charitable_Checklist::get_instance(), 'maybe_add_checklist_widget_html' ) );

/**
 * Show a notice related to the checklist.
 *
 * @see Charitable_Checklist::get_dashboard_notices()
 */
add_filter( 'charitable_admin_dashboard_init_end', array( Charitable_Checklist::get_instance(), 'get_dashboard_notices' ), 10 );

/**
 * Redirect from the notifications page if necessary.
 *
 * @see Charitable_Checklist::maybe_redirect_from_notifications_page()
 */
add_action( 'init', array( Charitable_Checklist::get_instance(), 'maybe_redirect_from_checklist_page' ) );

/**
 * Register the admin page.
 *
 * @see Charitable_Setup::register_page()
 */
add_action( 'admin_menu', array( Charitable_Setup::get_instance(), 'register_page' ) );

/**
 * Hide the admin page from the menu.
 *
 * @see Charitable_Setup::remove_page_from_menu()
 */
add_action( 'admin_head', array( Charitable_Setup::get_instance(), 'remove_page_from_menu' ) );
