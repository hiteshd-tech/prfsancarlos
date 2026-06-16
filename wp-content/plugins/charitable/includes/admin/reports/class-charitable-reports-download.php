<?php
/**
 * Charitable Reports Download class.
 *
 * @package   Charitable/Classes/Charitable_Reports_Download
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Reports_Download' ) ) :

	/**
	 * Charitable_Reports_Download
	 *
	 * @final
	 * @since 1.8.1
	 */
	class Charitable_Reports_Download {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Reports_Download|null
		 */
		private static $instance = null;

		/**
		 * Keeps track of column data..
		 *
		 * @var  array
		 */
		public $columns = array();

		/**
		 * Create object instance.
		 *
		 * @since 1.8.1
		 */
		public function __construct() {
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1
		 */
		public function init() {
		}

		/**
		 * Master function to generate the PDF.
		 *
		 * @since 1.8.1
		 */
		public function generate_pdf() {

			// basic checks.
			if ( ! isset( $_POST['charitable_export_report_nonce'] ) || ! isset( $_POST['charitable_report_action'] ) || empty( $_POST['charitable_report_action'] ) ) {
				return;
			}

			// check nonce.
			if ( ! wp_verify_nonce( $_POST['charitable_export_report_nonce'], 'charitable_export_report' ) ) { // phpcs:ignore
				return;
			}

			// check for export action.
			if ( strpos( sanitize_title( $_POST['charitable_report_action'] ), 'charitable_report_', 0 ) === false ) { // phpcs:ignore
				return;
			}

			$charitable_report_action = wp_unslash( sanitize_text_field( $_POST['charitable_report_action'] ) ); // phpcs:ignore

			$action = strpos( $charitable_report_action, 'download_pdf', 0 ) ? 'download' : 'print';

			// get the last word after the last underline in the string.
			$report_type = substr( $charitable_report_action, strrpos( $charitable_report_action, '_' ) + 1 );

			switch ( $report_type ) {
				case 'dashboard':
					$this->generate_dashboard_content( $action );
					break;

				case 'overview':
					$this->generate_overview_content( $action );
					break;

				default:
					break;
			}

			exit;
		}

		/**
		 * Generate a PDF report for the dashboard.
		 *
		 * @param  string $action The action to take. Download or print.
		 *
		 * @since 1.8.1
		 */
		public function generate_dashboard_content( $action = '' ) {

			$include_icons           = $action === 'download' ? false : true;
			$show_support_box        = false;
			$show_recommended_addons = false;

			$start_date = empty( $_POST['start_date'] ) ? false : esc_html( $_POST['start_date'] ); // phpcs:ignore
			$end_date   = empty( $_POST['end_date'] ) ? false : esc_html( $_POST['end_date'] ); // phpcs:ignore
			$days       = empty( $_POST['days'] ) ? false : intval( $_POST['days'] ); // phpcs:ignore

			$init_vars = array(
				'start_date'              => $start_date,
				'end_date'                => $end_date,
				'days'                    => $days,
				'show_support_box'        => $show_support_box,
				'include_icons'           => $include_icons,
				'action'                  => $action,
				'show_recommended_addons' => $show_recommended_addons,
				'show_notifications'      => false,
				'use_cache'               => 'no',
			);

			$charitable_dashboard = new Charitable_Dashboard_Legacy( $init_vars );
			$html                 = $charitable_dashboard->generate_dashboard_report_html( $init_vars ); // phpcs:ignore

			$assets_dir      = charitable()->get_path( 'assets', true );
			$currency_helper = charitable_get_currency_helper();

			ob_start();

			$page_args = array(
				'charitable_admin_2_0_css' => $assets_dir . 'css/admin/charitable-admin-report-' . $action . '.css', // phpcs:ignore
				'action'                   => $action,
				'charitable_cards'         => $html['charitable_cards'], // phpcs:ignore
				'charitable_reports'       => $html['charitable_reports'], // phpcs:ignore
				'headline_chart_options'   => array(
					'donation_axis' => (array) $charitable_dashboard->donation_axis,
					'date_axis'     => (array) $charitable_dashboard->date_axis,
				),
				'currency_symbol'          => $currency_helper->get_currency_symbol(),
				'start_date'               => $start_date,
				'end_date'                 => $end_date,
				'charitable_chart_js'	   => charitable()->get_path( 'assets', false ) . 'js/libraries/apexcharts.min.js', // phpcs:ignore
			);

			charitable_template(
				'print/dashboard.php',
				$page_args
			);

			if ( 'print' === $action ) {

				$html = ob_get_clean();
				echo $html; // phpcs:ignore
				exit;

			} else {

				die( 'Non-print generation not yet implemented.' );

			}

			exit;
		}

		/**
		 * Generate a PDF report for the overview.
		 *
		 * @param  string $action The action to take. Download or print.
		 *
		 * @since 1.8.1
		 */
		public function generate_overview_content( $action = '' ) {

			$start_date = gmdate( 'Y/m/d', strtotime( '-7 days' ) );
			$end_date   = gmdate( 'Y/m/d' );
			$status     = false;

			$start_date         = empty( $_POST['start_date'] ) ? $start_date : esc_html( $_POST['start_date'] ); // phpcs:ignore
			$end_date           = empty( $_POST['end_date'] ) ? $end_date : esc_html( $_POST['end_date'] ); // phpcs:ignore
			$campaign_id        = empty( $_POST['campaign_id'] ) ? -1 : intval( $_POST['campaign_id'] ); // phpcs:ignore
			$category_id 		= empty( $_POST['category_id'] ) ? 0 : intval( $_POST['category_id'] ); // phpcs:ignore
			$category_term_name = $category_id > 0 ? get_term_by( 'id', $category_id, 'campaign_category' )->name : '';

			$charitable_reports = Charitable_Reports::get_instance();

			$args = array(
				'start_date'  => $start_date,
				'end_date'    => $end_date,
				'status'      => $status,
				'campaign_id' => $campaign_id,
				'category_id' => $category_id,
			);

			$charitable_reports->init_with_array( 'overview', $args );

			// main activity.
			$report_activity = $charitable_reports->get_activity();

			// donations.
			$donations              = $charitable_reports->get_donations();
			$total_count_donations  = count( $donations );
			$total_amount_donations = $charitable_reports->get_donations_total();
			$donation_breakdown     = $charitable_reports->get_donations_by_day();
			$payment_breakdown      = $charitable_reports->get_donations_by_payment();
			$donation_average       = ( $total_amount_donations > 0 && $total_amount_donations > 0 ) ? charitable_format_money( $total_amount_donations / $total_count_donations ) : 0;
			$donation_total         = charitable_format_money( $total_amount_donations );

			// donors.
			$top_donors         = $charitable_reports->get_top_donors_overview();
			$total_count_donors = $charitable_reports->get_donors_count();

			// refunds.
			$refunds_data         = $charitable_reports->get_refunds();
			$total_amount_refunds = $refunds_data['total_amount'];
			$total_count_refunds  = $refunds_data['total_count'];

			$args          = false;
			$top_campaigns = Charitable_Campaigns::ordered_by_amount( $args );

			$assets_dir      = charitable()->get_path( 'assets', true );
			$currency_helper = charitable_get_currency_helper();

			$include_icons = $action === 'download' ? false : true;
			$campaign      = $campaign_id > 0 ? charitable_get_campaign( $campaign_id ) : false;

			ob_start();

			charitable_template(
				'print/overview.php',
				array(
					'charitable_admin_2_0_css'      => $assets_dir . 'css/admin/charitable-admin-report-' . $action . '.css',
					'action'                        => $action,
					'donations'                     => $donations,
					'total_count_donations'         => $total_count_donations,
					'total_amount_donations'        => $total_amount_donations,
					'donation_breakdown'            => $donation_breakdown,
					'payment_breakdown'             => $payment_breakdown,
					'donation_average'              => $donation_average,
					'donation_total'                => $donation_total,
					'total_count_donors'            => $total_count_donors,
					'total_amount_refunds'          => $total_amount_refunds,
					'total_count_refunds'           => $total_count_refunds,
					'donation_breakdown_html'       => $charitable_reports->generate_donations_breakdown_rows( $donation_breakdown, $refunds_data ),
					'activity_list'                 => $charitable_reports->generate_activity_list( $report_activity, $include_icons ),
					'top_donors'                    => $charitable_reports->generate_top_donors( $top_donors, $include_icons ),
					'top_campaigns'                 => $charitable_reports->generate_top_campaigns( $top_campaigns, $include_icons ),
					'payment_methods_list'          => $charitable_reports->generate_payment_methods_list( $payment_breakdown, $include_icons ),
					'headline_chart_options'        => array(
						'donation_axis' => (array) $charitable_reports->donation_axis,
						'date_axis'     => (array) $charitable_reports->date_axis,
					),
					'payment_methods_chart_options' => array(
						'payment_percentages' => (array) array_reverse( $charitable_reports->payment_percentages ),
						'payment_labels'      => (array) array_reverse( $charitable_reports->payment_labels ),
					),
					'currency_symbol'               => $currency_helper->get_currency_symbol(),
					'start_date'                    => $start_date,
					'end_date'                      => $end_date,
					'campaign_id'                   => $campaign_id,
					'campaign'                      => $campaign,
					'category_id'                   => $category_id,
					'category_term_name'            => $category_term_name,
					'charitable_chart_js'	        => charitable()->get_path( 'assets', false ) . 'js/libraries/apexcharts.min.js', // phpcs:ignore
				)
			);

			if ( 'print' === $action ) {

				$html = ob_get_clean();
				echo $html; // phpcs:ignore
				exit;

			} else {

				die( 'Non-print generation not yet implemented.' );

			}

			exit;
		}

		/**
		 * Print the CSV document headers.
		 *
		 * @param   string $report_type The type of report being exported.
		 * @since   1.8.1
		 *
		 * @return  void
		 */
		protected function print_headers( $report_type = '' ) {
			ignore_user_abort( true );

			if ( ! charitable_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
				set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			}

			if ( '' !== $report_type ) {
				$report_type = '-' . $report_type;
			}

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=charitable-report-download' . $report_type . '-' . gmdate( 'm-d-Y' ) . '.csv' );
			header( 'Expires: 0' );
		}

		/**
		 * Export the CSV file.
		 *
		 * @since   1.8.1
		 *
		 * @param   array  $data The data to export.
		 * @param   string $report_type The type of report being exported.
		 *
		 * @return  void
		 */
		protected function export( $data = array(), $report_type = '' ) {

			// if $data is an object, convert to array.
			if ( is_object( $data ) ) {
				$data = get_object_vars( $data );
			}

			if ( empty( $data ) ) {
				die( 'There is no data to export or download.' );
			}

			$data = array_map( array( $this, 'map_data' ), $data );
			$data = array_values( $data ); // reset keys.

			$this->print_headers( $report_type );

			/* Create a file pointer connected to the output stream */
			$output = fopen( 'php://output', 'w' );

			/* Print first row headers. */
			fputcsv( $output, array_values( $this->columns ) );

			/* Print the data */
			if ( is_array( $data ) && ! empty( $data ) ) {
				foreach ( $data as $row ) {
					fputcsv( $output, $row );
				}
			}

			fclose( $output ); // phpcs:ignore

			exit();
		}

		/**
		 * Map the data to the columns.
		 *
		 * @since   1.8.1
		 * @param   array $data The data to map.
		 *
		 * @return  array
		 */
		public function map_data( $data ) {
			$row = array();

			foreach ( $this->columns as $column_slug => $column_label ) {
				$row[ $column_slug ] = isset( $data[ $column_slug ] ) ? $data[ $column_slug ] : '';
			}

			return $row;
		}

		/**
		 * Generate the HTML for a breakdown of donations, required for some test output.
		 *
		 * @since   1.8.1
		 *
		 * @return  void
		 */
		public function download_donations_breakdown() {

			// check for nonce in $_POST and charitable_report_action variable.
			if ( ! isset( $_POST['charitable_export_report_nonce'] ) || ! isset( $_POST['charitable_report_action'] ) ) {
				return;
			}

			// check nonce.
			if ( ! wp_verify_nonce( $_POST['charitable_export_report_nonce'], 'charitable_export_report' ) ) { // phpcs:ignore
				return;
			}

			// check for admin.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// check for export action.
			if ( 'charitable_report_download_donation_breakdown' !== $_POST['charitable_report_action'] ) {
				return;
			}

			$args = array(
				'start_date'  => isset( $_POST['start_date'] ) ? esc_html( $_POST['start_date'] ) : false, // phpcs:ignore
				'end_date'    => isset( $_POST['end_date'] ) ? esc_html( $_POST['end_date'] ) : false, // phpcs:ignore
				'campaign_id' => isset( $_POST['campaign_id'] ) ? esc_html( $_POST['campaign_id'] ) : false, // phpcs:ignore
				'category_id' => isset( $_POST['category_id'] ) ? esc_html( $_POST['category_id'] ) : false, // phpcs:ignore
				'status'      => isset( $_POST['status'] ) ? esc_html( $_POST['status'] ) : false, // phpcs:ignore
			);

			$defaults = array(
				'start_date'  => false,
				'end_date'    => false,
				'campaign_id' => false,
				'category_id' => false,
				'status'      => false,
			);

			$args = wp_parse_args( $args, $defaults );

			$this->columns = array(
				'label'   => __( 'Date', 'charitable' ),
				'amount'  => __( 'Donations', 'charitable' ),
				'donors'  => __( 'No. of Donors', 'charitable' ),
				'refunds' => __( 'Refunds', 'charitable' ),
				'net'     => __( 'Net', 'charitable' ),
			);

			$charitable_reports = Charitable_Reports::get_instance();

			$charitable_reports->init_with_array( 'overview', $args );

			$donation_breakdown = $charitable_reports->get_donations_by_day( true );

			$this->export( $donation_breakdown );

			exit;
		}

		/**
		 * Generate the HTML for a breakdown of activities.
		 *
		 * @since   1.8.1
		 *
		 * @return  void
		 */
		public function download_activities() {

			// check for nonce in $_POST and charitable_report_action variable.
			if ( ! isset( $_POST['charitable_export_report_nonce'] ) || ! isset( $_POST['charitable_report_action'] ) ) {
				return;
			}

			// check nonce.
			if ( ! wp_verify_nonce( $_POST['charitable_export_report_nonce'], 'charitable_export_report' ) ) { // phpcs:ignore
				return;
			}

			// check for admin.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// check for export action.
			if ( 'charitable_report_download_activity' !== $_POST['charitable_report_action'] ) {
				return;
			}

			$args = array(
				'start_date'    => isset( $_POST['start_date'] ) ? esc_html( $_POST['start_date'] ) : false, // phpcs:ignore
				'end_date'      => isset( $_POST['end_date'] ) ? esc_html( $_POST['end_date'] ) : false, // phpcs:ignore
				'campaign_id'   => isset( $_POST['campaign_id'] ) ? esc_html( $_POST['campaign_id'] ) : false, // phpcs:ignore
				'activity_type' => isset( $_POST['activity_type'] ) ? esc_html( $_POST['activity_type'] ) : false, // phpcs:ignore
			);

			$defaults = array(
				'start_date'    => false,
				'end_date'      => false,
				'campaign_id'   => false,
				'activity_type' => false,
			);

			$args = wp_parse_args( $args, $defaults );

			$this->columns = array(
				'action_label'    => __( 'Action', 'charitable' ), // "Donation Made"
				'amount'          => __( 'Amount', 'charitable' ), // "$55.00"
				'campaign_title'  => __( 'Campaign', 'charitable' ), // "XYZ Campaign"
				'donor_id'        => __( 'Donor ID', 'charitable' ), // "123"
				'donor_name'      => __( 'Donor Name', 'charitable' ), // "John Doe"
				'created_by'      => __( 'Created By User ID', 'charitable' ), // 1
				'created_by_name' => __( 'Created By User Name', 'charitable' ), // "John Doe"
				'date_recorded'   => __( 'Date', 'charitable' ), // "March 1, 2023"
			);

			$charitable_reports = Charitable_Reports::get_instance();
			$charitable_reports->init_with_array( 'activity', $args );
			// $charitable_reports->init( 'activity', $args['start_date'], $args['end_date'], false, $args['campaign_id'] );
			$report_activity = $charitable_reports->get_activity( $args['activity_type'] );

			// convert arrays of objects to arrays of arrays.
			if ( is_array( $report_activity ) ) {
				foreach ( $report_activity as $key => $value ) {
					$report_activity[ $key ] = get_object_vars( $value );

					// format action labels.
					$report_activity[ $key ]['action_label'] = ( ! empty( $report_activity[ $key ]['type'] ) && 'donation' === $report_activity[ $key ]['type'] && ! empty( $report_activity[ $key ]['item_id_prefix'] ) && ! empty( $report_activity[ $key ]['item_id'] ) ) ? str_replace( '%id', $report_activity[ $key ]['item_id_prefix'] . $report_activity[ $key ]['item_id'], $report_activity[ $key ]['action_label'] ) : $report_activity[ $key ]['action_label'];
					$report_activity[ $key ]['action_label'] = ( ! empty( $report_activity[ $key ]['type'] ) && 'campaign' === $report_activity[ $key ]['type'] && ! empty( $report_activity[ $key ]['campaign_id'] ) ) ? str_replace( '%id', $report_activity[ $key ]['campaign_id'], $report_activity[ $key ]['action_label'] ) : $report_activity[ $key ]['action_label'];

					// convert to date format example: March 31, 2023 1:30 pm.
					if ( ! empty( $report_activity[ $key ]['date_recorded'] ) ) :
						$date_format                              = empty( $date_format ) ? get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) : $date_format;
						$report_activity[ $key ]['date_recorded'] = gmdate( $date_format, strtotime( $report_activity[ $key ]['date_recorded'] ) );
					endif;

					// format money amounts.
					if ( ! empty( $report_activity[ $key ]['amount'] ) ) :
						$report_activity[ $key ]['amount'] = html_entity_decode( trim( charitable_format_money( $report_activity[ $key ]['amount'] ) ) );
					endif;

				}

			}

			$this->export( $report_activity, 'activity' );

			exit;
		}

		/**
		 * Generate the HTML for a breakdown of donors, required for some reports.
		 *
		 * @since   1.8.1
		 *
		 * @return  void
		 */
		public function download_donors() {

			// check for nonce in $_POST and charitable_report_action variable.
			if ( ! isset( $_POST['charitable_export_report_nonce'] ) || ! isset( $_POST['charitable_report_action'] ) ) {
				return;
			}

			// check nonce.
			if ( ! wp_verify_nonce( $_POST['charitable_export_report_nonce'], 'charitable_export_report' ) ) { // phpcs:ignore
				return;
			}

			// check for admin.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// check for export action.
			if ( 'charitable_report_download_donors' !== $_POST['charitable_report_action'] ) {
				return;
			}

			$args = array(
				'report_type' => isset( $_POST['report_type'] ) ? esc_html( $_POST['report_type'] ) : false, // phpcs:ignore
			);

			$defaults = array(
				'report_type' => 'donors-top',
			);

			$args = wp_parse_args( $args, $defaults );

			$this->columns = array(
				'first_name'            => __( 'First Name', 'charitable' ),
				'last_name'             => __( 'Last Name', 'charitable' ),
				'email'                 => __( 'Email', 'charitable' ),
				'donor_id'              => __( 'Donor ID', 'charitable' ),
				'user_id'               => __( 'User ID', 'charitable' ),
				'date_joined'           => __( 'Date Joined', 'charitable' ),
				'total_amount'          => __( 'Total Donations', 'charitable' ),
				'average'               => __( 'Average Donations', 'charitable' ),
				'total_count_donations' => __( 'No. of Donations', 'charitable' ),
				'total_count_campaigns' => __( 'Number of Campaigns', 'charitable' ),
				'amount'                => __( 'Last Donation Amount', 'charitable' ),
				'last_campaign_title'   => __( 'Last Donation Campaign', 'charitable' ),
				'last_donation_date'    => __( 'Last Donation Date', 'charitable' ),
			);

			$charitable_reports = Charitable_Reports::get_instance();
			$charitable_reports->init_with_array( $args['report_type'] );

			$donor_data = $charitable_reports->get_donor_report_by_type( $args['report_type'] );

			// convert arrays of objects to arrays of arrays.
			if ( is_array( $donor_data ) ) {
				foreach ( $donor_data as $key => $value ) {
					$donor_data[ $key ] = get_object_vars( $value );
				}
			}

			$donor_data = $this->format_donors_download_data( $donor_data );

			$this->export( $donor_data, $args['report_type'] );

			exit;
		}

		/**
		 * Format the donor data for download.
		 *
		 * @since   1.8.1
		 *
		 * @param   array $donor_data The donor data to format.
		 *
		 * @return  array
		 */
		public function format_donors_download_data( $donor_data = false ) {

			if ( ! empty( $donor_data ) ) {
				$donor_data = charitable_reports_format_money( $donor_data, array( 'total_amount', 'average', 'amount' ), false, false, '', true, true );
				$donor_data = charitable_reports_format_date( $donor_data, array( 'date_joined', 'last_donation_date' ), false );
			}

			return $donor_data;
		}

		/**
		 * Generate the HTML for a breakdown of an advanced report.
		 *
		 * @since   1.8.1
		 *
		 * @return  void
		 */
		public function download_advanced() {

			// check for nonce in $_POST and charitable_report_action variable.
			if ( ! isset( $_POST['charitable_export_report_nonce'] ) || ! isset( $_POST['charitable_report_action'] ) ) {
				return;
			}

			// check nonce.
			if ( ! wp_verify_nonce( $_POST['charitable_export_report_nonce'], 'charitable_export_report' ) ) { // phpcs:ignore
				return;
			}

			// check for admin.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// check for export action.
			if ( 'charitable_report_download_advanced' !== $_POST['charitable_report_action'] ) {
				return;
			}

			$args = array(
				'report_type' => isset( $_POST['report_type'] ) ? sanitize_title( $_POST['report_type'] ) : false, // phpcs:ignore
			);

			$defaults = array(
				'report_type' => 'lybunt',
			);

			$args = wp_parse_args( $args, $defaults );

			$report_args = charitable_reports_get_advanced_report_args();

			$this->columns = array(
				'donor_first_name'                   => __( 'First Name', 'charitable' ),
				'donor_last_name'                    => __( 'Last Name', 'charitable' ),
				'donor_email'                        => __( 'Email', 'charitable' ),
				'donor_id'                           => __( 'Donor ID', 'charitable' ),
				'user_id'                            => __( 'User ID', 'charitable' ),
				'start_date_compare_from'            => __( 'Start Date Compare From', 'charitable' ),
				'end_date_compare_from'              => __( 'End Date Compare From', 'charitable' ),
				'total_donation_count_compare_from'  => __( 'Donation Count Compare From', 'charitable' ),
				'total_donation_amount_compare_from' => __( 'Donation Total Compare From', 'charitable' ),
				'start_date_compare_to'              => __( 'Start Date Compare To', 'charitable' ),
				'end_date_compare_to'                => __( 'End Date Compare To', 'charitable' ),
				'total_donation_count_compare_to'    => __( 'Donation Count Compare To', 'charitable' ),
				'total_donation_amount_compare_to'   => __( 'Donation Total Compare To', 'charitable' ),
				'last_donation_amount'               => __( 'Last Donation Amount', 'charitable' ),
				'last_donation_campaigns'            => __( 'Last Donated Campaign(s)', 'charitable' ),
				'total_life_amount'                  => __( 'Lifetime Total', 'charitable' ),
			);

			$charitable_reports = Charitable_Reports::get_instance();
			$charitable_reports->init_with_array( $args['report_type'] );
			$advanced_report_data = $charitable_reports->get_advanced_data_by_report_type( $report_args );

			// convert arrays of objects to arrays of arrays.
			if ( is_array( $advanced_report_data ) ) {
				foreach ( $advanced_report_data as $key => $value ) {
					$advanced_report_data[ $key ] = get_object_vars( $value );
				}
			}

			foreach ( $advanced_report_data as $key => $value ) {
				// merge $report_args with every element of $advanced_report_data.
				$advanced_report_data[ $key ]                            = array_merge( $report_args, $value );
				$advanced_report_data[ $key ]['last_donation_campaigns'] = wp_strip_all_tags( implode( ', ', $value['last_donation_campaign_links'] ) );
			}

			$advanced_report_data = $this->format_advanced_download_data( $advanced_report_data );

			$this->export( $advanced_report_data, $args['report_type'] );

			exit;
		}

		/**
		 * Format the donor data for download.
		 *
		 * @since   1.8.1
		 *
		 * @param   array $advanced_data The donor data to format.
		 *
		 * @return  array
		 */
		public function format_advanced_download_data( $advanced_data = false ) {

			if ( ! empty( $advanced_data ) ) {
				$advanced_data = charitable_reports_format_money( $advanced_data, array( 'total_donation_amount_compare_from', 'total_donation_amount_compare_to', 'last_donation_amount', 'total_life_amount' ), false, false, '', true, true );
				$advanced_data = charitable_reports_format_date( $advanced_data, array( 'start_date_compare_from', 'end_date_compare_from', 'start_date_compare_to', 'end_date_compare_to' ), 'F j, Y' );
			}

			return $advanced_data;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Reports_Download
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
endif;
