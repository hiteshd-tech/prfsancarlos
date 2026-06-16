<?php
/**
 * Charitable Settings Hooks.
 *
 * Action/filter hooks used for Charitable Settings API.
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
 * Register Charitable tools.
 *
 * @see Charitable_Tools::register_settings()
 */
add_action( 'admin_init', array( Charitable_Tools::get_instance(), 'register_settings' ) );

/**
 * Add settings to the Tools tab.
 *
 * @since 1.8.1.6 and 1.8.2
 *
 * @see Charitable_Tools_Settings::add_import_fields()
 */
add_filter( 'charitable_tools_tab_fields_export', array( Charitable_Tools_Settings::get_instance(), 'add_tools_export_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_snippets', array( Charitable_Tools_Settings::get_instance(), 'add_tools_snippets_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_customize', array( Charitable_Tools_Settings::get_instance(), 'add_tools_customize_fields' ), 5 );

/**
 * Import sub-tab fields.
 *
 * @since 1.8.10
 */
add_filter( 'charitable_tools_tab_fields_sub_import__charitable', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_charitable_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_sub_import__givewp', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_givewp_fields' ), 5 );
add_filter( 'charitable_tools_tab_fields_sub_import__givebutter', array( Charitable_Tools_Settings::get_instance(), 'add_tools_import_givebutter_fields' ), 5 );

/**
 * Add settings to the Misc tab.
 *
 * @since 1.8.9
 *
 * @see Charitable_Tools_Misc::add_misc_fields()
 */
add_filter( 'charitable_tools_tab_fields_misc', array( Charitable_Tools_Misc::get_instance(), 'add_misc_fields' ), 5 );

/**
 * Handle bulk removal of donations.
 *
 * @since 1.8.9
 *
 * @see Charitable_Tools_Misc::bulk_remove_donations()
 */
add_filter( 'charitable_save_tools', array( Charitable_Tools_Misc::get_instance(), 'bulk_remove_donations' ), 10, 3 );

/**
 * Look for export/import attempts.
 *
 * @see Charitable_Export_Settings::add_export_fields()
 */
add_action( 'admin_init', array( Charitable_Export_Items::get_instance(), 'admin_accept_export_campaign_request' ) );
add_action( 'admin_init', array( Charitable_Export_Items::get_instance(), 'admin_accept_export_donations_request' ) );
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_campaign_request' ) );
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_donations_request' ) );

/**
 * Import sub-tab default redirect.
 *
 * @since 1.8.10
 */
add_action( 'admin_init', array( Charitable_Tools::get_instance(), 'tools_sub_tab_default' ), 5 );

/**
 * GiveWP and GiveButter CSV import handlers.
 *
 * @since 1.8.10
 */
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_donations_givewp_request' ) );
add_action( 'admin_init', array( Charitable_Import_Items::get_instance(), 'admin_accept_import_donations_givebutter_request' ) );

/**
 * GiveWP migration tool AJAX handlers.
 *
 * @since 1.8.10
 */
add_action( 'wp_ajax_charitable_givewp_import_start', array( Charitable_GiveWP_Importer::get_instance(), 'ajax_import_start' ) );
add_action( 'wp_ajax_charitable_givewp_import_batch', array( Charitable_GiveWP_Importer::get_instance(), 'ajax_import_batch' ) );


/**
 * Add the tools tab settings fields.
 *
 * @since   1.8.1.6
 *
 * @return  array<string,array>
 */
add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_WPCode::get_instance(), 'enqueue_scripts' ) );
add_action( 'admin_enqueue_scripts', array( Charitable_Tools_System_Info::get_instance(), 'enqueue_scripts' ) );

/**
 * Register AJAX action for email diagnostics.
 *
 * @since 1.8.9.2
 */
add_action( 'wp_ajax_charitable_email_diagnostics', array( Charitable_Tools_System_Info::get_instance(), 'ajax_email_diagnostics' ) );

/**
 * Register AJAX action for test email.
 *
 * @since 1.8.9.2
 */
add_action( 'wp_ajax_charitable_send_test_email', array( Charitable_Tools_System_Info::get_instance(), 'ajax_send_test_email' ) );

/**
 * Clear error logs via AJAX.
 *
 * @since 1.8.9.2
 *
 * @see Charitable_Tools_System_Info::ajax_clear_error_logs()
 */
add_action( 'wp_ajax_charitable_clear_error_logs', array( Charitable_Tools_System_Info::get_instance(), 'ajax_clear_error_logs' ) );

/**
 * Export error logs via AJAX.
 *
 * @since 1.8.9.2
 *
 * @see Charitable_Tools_System_Info::ajax_export_error_logs()
 */
add_action( 'wp_ajax_charitable_export_error_logs', array( Charitable_Tools_System_Info::get_instance(), 'ajax_export_error_logs' ) );

/**
 * Register AJAX action for debug log scanner.
 *
 * @since 1.8.9.2
 *
 * @see Charitable_Tools_System_Info::ajax_debug_log_scan()
 */
add_action( 'wp_ajax_charitable_debug_log_scan', array( Charitable_Tools_System_Info::get_instance(), 'ajax_debug_log_scan' ) );

add_action( 'admin_enqueue_scripts', array( Charitable_Tools_Misc::get_instance(), 'enqueue_scripts' ) );

/**
 * Enqueue GiveWP migration tool scripts.
 *
 * @since 1.8.10
 */
add_action( 'admin_enqueue_scripts', array( Charitable_GiveWP_Importer::get_instance(), 'enqueue_scripts' ) );
