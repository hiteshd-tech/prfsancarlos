<?php
/**
 * Charitable Admin Report Hooks.
 *
 * Action/filter hooks used for setting up reports and activity monitoring in the admin.
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
 * Get overview data via ajax, probably as a result of a changing UI element.
 *
 * @see Charitable_Reports_Ajax::get_overview_data()
 */
add_action( 'wp_ajax_charitable_report_overview_data', array( Charitable_Reports_Ajax::get_instance(), 'get_overview_data' ) );

/**
 * Get activity data via ajax, probably as a result of a changing UI element.
 *
 * @see Charitable_Reports_Ajax::get_activity_data()
 */
add_action( 'wp_ajax_charitable_report_activity_data', array( Charitable_Reports_Ajax::get_instance(), 'get_activity_data' ) );

/**
 * Get donor data via ajax, probably as a result of a changing UI element.
 *
 * @see Charitable_Reports_Ajax::get_donor_data()
 */
add_action( 'wp_ajax_charitable_report_donor_data', array( Charitable_Reports_Ajax::get_instance(), 'get_donor_data' ) );

/**
 * Get donor data via ajax, probably as a result of a changing UI element.
 *
 * @see Charitable_Reports_Ajax::get_donor_data()
 */
add_action( 'wp_ajax_charitable_report_donor_data_pagination', array( Charitable_Reports_Ajax::get_instance(), 'get_donor_data' ) );

/**
 * Get dashboard data via ajax, probably as a result of a changing UI element.
 *
 * @see Charitable_Reports_Ajax::get_dashboard_data()
 */
add_action( 'wp_ajax_charitable_report_dashboard_data', array( Charitable_Reports_Ajax::get_instance(), 'get_dashboard_data' ) );

/**
 * Get UI elements for the advanced report page.
 *
 * @see Charitable_Reports_Ajax::get_advanced_report_ui()
 */
add_action( 'wp_ajax_charitable_report_advanced_ui', array( Charitable_Reports_Ajax::get_instance(), 'get_advanced_report_ui' ) );

/**
 * Download a CSV or print info of the donations breakdown.
 *
 * @see Charitable_Reports_Download::download_donations_breakdown()
 */
add_action( 'admin_init', array( Charitable_Reports_Download::get_instance(), 'download_donations_breakdown' ) );

/**
 * Download a CSV or print info of the donations breakdown.
 *
 * @see Charitable_Reports_Download::download_donations_breakdown()
 */
add_action( 'admin_init', array( Charitable_Reports_Download::get_instance(), 'download_activities' ) );

/**
 * Download a CSV or print info of the donors.
 *
 * @see Charitable_Reports_Download::download_donors()
 */
add_action( 'admin_init', array( Charitable_Reports_Download::get_instance(), 'download_donors' ) );

/**
 * Download a CSV or print info of the advanced report.
 *
 * @see Charitable_Reports_Download::download_advanced()
 */
add_action( 'admin_init', array( Charitable_Reports_Download::get_instance(), 'download_advanced' ) );

/**
 * Request a PDF to be generated.
 *
 * @see Charitable_Reports_Download::generate_pdf()
 */
add_action( 'admin_init', array( Charitable_Reports_Download::get_instance(), 'generate_pdf' ) );
