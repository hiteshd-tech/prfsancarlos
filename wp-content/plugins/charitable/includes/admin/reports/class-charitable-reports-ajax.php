<?php
/**
 * Charitable Reports Ajax.
 *
 * @package   Charitable/Classes/Charitable_Reports_Ajax
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

if ( ! class_exists( 'Charitable_Reports_Ajax' ) ) :

	/**
	 * Charitable_Reports_Ajax
	 *
	 * @final
	 * @since 1.8.1
	 */
	class Charitable_Reports_Ajax {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Reports_Ajax|null
		 */
		private static $instance = null;

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
		 * Generate and return data that overview report needs.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_overview_data() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-reporting' ) ) { // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$start_date      = ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : false; // phpcs:ignore
			$end_date        = ! empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : false; // phpcs:ignore
			$campaign_id     = ! empty( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : false; // phpcs:ignore
			$category_id     = ! empty( $_POST['campaign_category_id'] ) ? intval( $_POST['campaign_category_id'] ) : false; // phpcs:ignore
			$filter          = ! empty( $_POST['filter'] ) ? sanitize_text_field( $_POST['filter'] ) : false; // phpcs:ignore
			$report_type     = ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'overview'; // phpcs:ignore
			$status          = ! empty( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : false; // phpcs:ignore

			$start_date = str_replace( '/', '-', $start_date );
			$end_date   = str_replace( '/', '-', $end_date );

			$charitable_reports = Charitable_Reports::get_instance();

			$args = array(
				'start_date'  => $start_date,
				'end_date'    => $end_date,
				'status'      => $status,
				'campaign_id' => $campaign_id,
				'category_id' => $category_id,
			);

			$charitable_reports->init_with_array( $report_type, $args );

			$donations = $charitable_reports->get_donations();

			$charitable_reports->init_axis_with_donations( $donations, $start_date, $end_date );

			$donations              = $charitable_reports->get_donations();
			$total_count_donations  = count( $donations );
			$total_amount_donations = $charitable_reports->get_donations_total();
			$donation_breakdown     = $charitable_reports->get_donations_by_day();
			$donation_average       = intval( $total_amount_donations ) > 0 && intval( $total_count_donations ) > 0 ? charitable_format_money( $total_amount_donations / $total_count_donations, 2, true ) : 0;
			$donation_total         = charitable_format_money( $total_amount_donations, 2, true );
			$total_count_donors     = $charitable_reports->get_donors_count();

			$donation_breakdown = $charitable_reports->get_donations_by_day();

			$refunds_data         = $charitable_reports->get_refunds();
			$total_amount_refunds = charitable_format_money( $refunds_data['total_amount'], 2, true );
			$total_count_refunds  = $refunds_data['total_count'];

			$payment_breakdown = $charitable_reports->get_donations_by_payment();

			$top_donors = $charitable_reports->get_top_donors_overview();

			// campaigns.
			$top_campaigns_args = false;
			$top_campaigns      = Charitable_Campaigns::ordered_by_amount( $top_campaigns_args );

			// main activity.
			$report_activity = $charitable_reports->get_activity();

			$report_html = array();

			$report_html['html']['donation_breakdown']                   = $charitable_reports->generate_donations_breakdown_rows( $donation_breakdown, $refunds_data );
			$report_html['headline_chart_options']['donation_axis']      = (array) $charitable_reports->donation_axis;
			$report_html['headline_chart_options']['date_axis']          = (array) $charitable_reports->date_axis;
			$report_html['donation_amount']                              = $donation_total;
			$report_html['donation_count']                               = $total_count_donations;
			$report_html['donation_average']                             = $donation_average;
			$report_html['donors_count']                                 = $total_count_donors;
			$report_html['refund_total_amount']                          = $total_amount_refunds;
			$report_html['refund_count']                                 = $total_count_refunds;
			$report_html['payment_methods_chart']['payment_percentages'] = (array) array_reverse( $charitable_reports->payment_percentages );
			$report_html['payment_methods_chart']['payment_labels']      = (array) array_reverse( $charitable_reports->payment_labels );
			$report_html['html']['payment_methods_list']                 = $charitable_reports->generate_payment_methods_list( $payment_breakdown );
			$report_html['html']['activities']                           = $charitable_reports->generate_activity_list( $report_activity );
			$report_html['html']['top_donors_list']                      = $charitable_reports->generate_top_donors( $top_donors );
			$report_html['html']['top_campaigns_list']                   = $charitable_reports->generate_top_campaigns( $top_campaigns );

			// Cache via transients the 'charitable_report_overview_args' for 1 hour.
			$charitable_report_overview_args = array(
				'start_date'  => $start_date,
				'end_date'    => $end_date,
				'filter'      => $filter,
				'campaign_id' => $campaign_id,
			);

			$was_cached = set_transient( 'charitable-report-overview-args', $charitable_report_overview_args, 60 * 60 );

			$report_html['was_cached'] = $was_cached;

			wp_send_json_success( $report_html );

			exit;
		}

		/**
		 * Generate and return data that activity report needs.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_activity_data() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-reporting' ) ) { // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$report_html = array();

			$types         = array( 'campaign', 'donation' );
			$action        = ! empty( $_POST['activity_type'] ) ? esc_attr( $_POST['activity_type'] ) : ''; // phpcs:ignore
			$campaign_id   = ! empty( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : false; // phpcs:ignore
			$category_id   = ! empty( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : false; // phpcs:ignore
			$activity_type = ! empty( $_POST['activity_type'] ) ? sanitize_text_field( $_POST['activity_type'] ) : ''; // phpcs:ignore
			$start_date    = ! empty( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : false; // phpcs:ignore
			$end_date      = ! empty( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : false; // phpcs:ignore
			$limit         = ! empty( $_POST['limit'] ) ? intval( $_POST['limit'] ) : false; // phpcs:ignore

			$start_date = str_replace( '/', '-', $start_date );
			$end_date   = str_replace( '/', '-', $end_date );

			$admin_activities = new Charitable_Admin_Activities();
			$activities_args  = $admin_activities->get_activity(
				array(
					'activity_filter_types' => array( $action ),
					'campaign_id'           => $campaign_id,
					'category_id'           => $category_id,
					'activity_type'         => $activity_type,
					'start_date'            => $start_date,
					'end_date'              => $end_date,
					'limit'                 => $limit,
				)
			);
			$report_activity  = $admin_activities->get_activity_report_data( $activities_args );

			$charitable_reports = Charitable_Reports::get_instance();

			$report_html['html']['activities'] = $charitable_reports->generate_activity_list( $report_activity );

			// Cache via transients the 'charitable_report_activity_args' for 1 hour.
			$charitable_report_activity_args = array(
				'start_date'    => $start_date,
				'end_date'      => $end_date,
				'activity_type' => $activity_type,
				'campaign_id'   => $campaign_id,
			);

			$was_cached = set_transient( 'charitable-report-activity-args', $charitable_report_activity_args, 60 * 60 );

			$report_html['was_cached'] = $was_cached;

			wp_send_json_success( $report_html );

			exit;
		}

		/**
		 * Generate and return data that donor report needs.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_donor_data() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-reporting' ) ) { // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$report_html     = array();
			$donor_breakdown = array();

			$donor_report_type = isset( $_POST['report_type'] ) ? esc_attr( $_POST['report_type'] ) : 'donors-top'; // phpcs:ignore
			$limit             = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : false;

			$args           = array();
			$args['limit']  = ! $limit ? charitable_reports_get_pagination_per_page( $donor_report_type ) : $limit; // phpcs:ignore
			$args['offset'] = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0; // phpcs:ignore
			$args['ppage']  = isset( $_POST['ppage'] ) ? intval( $_POST['ppage'] ) : 1; // phpcs:ignore

			$charitable_reports = Charitable_Reports::get_instance();
			$charitable_reports->init_with_array( $donor_report_type, $args );

			switch ( $donor_report_type ) {
				case 'donors-top':
					$donor_breakdown = $charitable_reports->get_top_donors();
					break;
				case 'donors-recurring':
					$donor_breakdown = $charitable_reports->get_recurring_donors();
					break;
				case 'donors-first-time':
					$donor_breakdown = $charitable_reports->get_first_time_donors();
					break;

				default:
					// code...
					break;
			}

			$report_html['html']['donors']     = $charitable_reports->generate_donor_breakdown_table_html( $donor_report_type, $donor_breakdown, $args['ppage'], $limit );
			$report_html['html']['title_card'] = $charitable_reports->generate_title_card_html( $donor_report_type );

			// Cache via transients the 'charitable_report_donor_args' for 1 hour.
			$charitable_report_donor_args = array(
				'donor_report_type' => $donor_report_type,
				'limit'             => $limit,
			);

			$was_cached = set_transient( 'charitable-report-donor-args', $charitable_report_donor_args, 60 * 60 );

			$report_html['was_cached'] = $was_cached;

			wp_send_json_success( $report_html );
		}

		/**
		 * Generate and return data that advanced screen (UI) needs.
		 * This allows addons and third parties to hook in and change the UI in the top of the advanced report page.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_advanced_report_ui() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-reporting' ) ) {  // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$args                         = array();
			$args['start_date']           = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : false; // phpcs:ignore
			$args['end_date']             = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : false; // phpcs:ignore
			$args['selected_report_type'] = isset( $_POST['report_type'] ) ? esc_attr( $_POST['report_type'] ) : false; // phpcs:ignore
			$args['datepickers']          = false;

			$ui_html = apply_filters( 'charitable_report_advanced_ui', array(), $args );

			wp_send_json_success( $ui_html );

			exit;
		}

		/**
		 * Generate and return data that dashboard needs.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_dashboard_data() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-reporting' ) ) { // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$report_html = array();

			$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : false; // phpcs:ignore
			$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : false; // phpcs:ignore
			$days       = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : false;

			$charitable_dashboard                                   = Charitable_Dashboard_Legacy::get_instance();
			$report_html['html']                                    = $charitable_dashboard->generate_dashboard_report_html( array( 'start_date' => $start_date, 'end_date' => $end_date, 'days' => $days ) ); // phpcs:ignore
			$report_html['headline_chart_options']['donation_axis'] = (array) $charitable_dashboard->get_donation_axis();
			$report_html['headline_chart_options']['date_axis']     = (array) $charitable_dashboard->get_date_axis();

			// we go by days with dashbaord so adjust the start_date and end_date accordingly.
			// Start date should be today's date and end date should be today's date plus the number of days.
			// If days is 0, then we use the start_date and end_date.
			if ( 0 === $days ) {
				$start_date = $charitable_dashboard->get_start_date();
				$end_date   = $charitable_dashboard->get_end_date();
			} else {
				$start_date = gmdate( 'Y-m-d', strtotime( 'today' ) );
				$end_date   = gmdate( 'Y-m-d', strtotime( 'today' ) + ( $days * 24 * 60 * 60 ) );
			}
			$report_html['start_date'] = $start_date;
			$report_html['end_date']   = $end_date;
			$report_html['days']       = $days;

			// Cache via transients the 'charitable_report_donor_args' for 1 hour.
			$charitable_dashboard_data_args = array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'days'       => $days,
				'timestamp'  => gmdate( 'Y/m/d h:i A', current_time( 'timestamp', 0 ) ),
			);

			$args_was_cached = set_transient( 'wpch_dashboard_data_args', $charitable_dashboard_data_args, DAY_IN_SECONDS );
			// $html_was_cached = set_transient( 'wpch_dashboard_data_html', $report_html, 60 * 60 );

			$report_html['args_was_cached'] = $args_was_cached;

			wp_send_json_success( $report_html );
		}

		/**
		 * Generate and return data that the default advanced report "lybunt" needs.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_lybunt_report() {

			// type check.
			if ( ! isset( $_POST['report_type'] ) || 'lybunt' !== $_POST['report_type'] ) {
				return;
			}

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-reporting' ) ) { // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$report_args = charitable_reports_get_advanced_report_args();

			$charitable_reports = Charitable_Reports::get_instance();

			if ( charitable_is_pro() ) :

				$charitable_reports->init_with_array( 'lybunt' );

				$advanced_report_data = $charitable_reports->get_advanced_data_by_report_type( $report_args );

			else :

				$advanced_report_data = $charitable_reports->get_data_sample( 'advanced' );

			endif;

			$start_date_compare_from = $report_args['start_date_compare_from'];
			$end_date_compare_from   = $report_args['end_date_compare_from'];
			$start_date_compare_to   = $report_args['start_date_compare_to'];
			$end_date_compare_to     = $report_args['end_date_compare_to'];

			$report_html         = array();
			$report_html['html'] = $charitable_reports->generate_advanced_breakdown_report_html( 'lybunt', $advanced_report_data, $report_args );

			wp_send_json_success( $report_html );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Reports_Ajax
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
