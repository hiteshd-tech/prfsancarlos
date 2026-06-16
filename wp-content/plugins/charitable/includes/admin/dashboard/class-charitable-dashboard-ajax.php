<?php
/**
 * Charitable Dashboard AJAX Handler.
 *
 * Handles AJAX requests for the dashboard functionality.
 *
 * @package   Charitable/Admin/Dashboard
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

if ( ! class_exists( 'Charitable_Dashboard_Ajax' ) ) :

	/**
	 * Charitable Dashboard AJAX Handler.
	 *
	 * @since 1.8.1
	 */
	class Charitable_Dashboard_Ajax {

		/**
		 * Single instance of this class.
		 *
		 * @since 1.8.1
		 *
		 * @var   Charitable_Dashboard_Ajax
		 */
		private static $instance = null;

		/**
		 * Create and return the single instance of this class.
		 *
		 * @since 1.8.1
		 *
		 * @return Charitable_Dashboard_Ajax
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * @since 1.8.1
		 */
		private function __construct() {
			// Constructor intentionally empty - hooks are registered in the hooks file
		}

		/**
		 * Get dashboard data via AJAX.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function get_dashboard_data() {

			// Security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-dashboard' ) ) { // phpcs:ignore
				wp_send_json_error( 'Invalid nonce.' );
				exit;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Insufficient permissions.' );
				exit;
			}

			$time_period = isset( $_POST['time_period'] ) ? sanitize_text_field( wp_unslash( $_POST['time_period'] ) ) : 'last-7-days'; // phpcs:ignore

			// Validate time period.
			$valid_periods = array( 'last-7-days', 'last-14-days', 'last-30-days' );
			if ( ! in_array( $time_period, $valid_periods, true ) ) {
				wp_send_json_error( 'Invalid time period.' );
				exit;
			}

			try {
				// Get dashboard instance.
				$charitable_dashboard = Charitable_Dashboard::get_instance();

				// If requested via URL or POST, clear stats cache so AJAX reflects latest logic
				$clear_cache = ( isset( $_GET['charitable_clear_stats_cache'] ) && '1' === $_GET['charitable_clear_stats_cache'] ) ||
							   ( isset( $_POST['charitable_clear_stats_cache'] ) && '1' === $_POST['charitable_clear_stats_cache'] );

				if ( $clear_cache ) { // phpcs:ignore
					$charitable_dashboard->clear_dashboard_stats_cache();
					// Add a flag to indicate cache was cleared
					$response_data['cache_cleared'] = true;
				}

				// Get stats data.
				$stats = $charitable_dashboard->get_dashboard_stats( $time_period );

				// Get chart data using legacy dashboard methods.
				$legacy_dashboard = Charitable_Dashboard_Legacy::get_instance();

				// Set up the legacy dashboard with the correct time period.
				$date_range = $charitable_dashboard->get_date_range_for_period( $time_period );
				$legacy_dashboard->generate_dashboard_report_html( array(
					'start_date' => $date_range['start_date'],
					'end_date'   => $date_range['end_date'],
					'days'       => $date_range['days'],
				) );

				// Get chart axis data.
				$date_axis = $legacy_dashboard->get_date_axis();

				// For 30-day periods, format dates as M/d instead of M d
				if ( 'last-30-days' === $time_period ) {
					$date_axis = array_map( function( $date ) {
						// Convert "Aug 27" to "8/27"
						$timestamp = strtotime( $date );
						return date( 'n/j', $timestamp ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					}, $date_axis );
				}

				$chart_data = array(
					'donation_axis' => $legacy_dashboard->get_donation_axis(),
					'date_axis'     => $date_axis,
				);

				// Prepare response data.
				$response_data = array(
					'stats' => $stats,
					'chart' => $chart_data,
				);

				wp_send_json_success( $response_data );

			} catch ( Exception $e ) {
				wp_send_json_error( 'Failed to load dashboard data: ' . $e->getMessage() );
			}
		}
	}

endif;
