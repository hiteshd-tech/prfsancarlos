<?php
/**
 * Tracking functions for reporting plugin usage (optin) and anonymous tracking (optin) to the Charitable site for users
 *
 * @access public
 * @package     Charitable
 * @subpackage  Admin
 * @copyright   Copyright (c) 2024, David Bisset
 * @since       1.8.4
 * @version     1.8.4.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tracking' ) ) {
	/**
	 * Usage tracking
	 */
	class Charitable_Tracking {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Tracking|null
		 */
		private static $instance = null;

		/**
		 * Class init function.
		 */
		public function __construct() {
		}

		/**
		 * Starts the process of sending in the optin usage checking.
		 *
		 * @since 1.8.4.2
		 *
		 * @param boolean $override            Override usage_optin_allowed.
		 * @param boolean $ignore_last_checkin Ignore last checkin flag.
		 *
		 * @return void
		 */
		public function send_checkins( $override = false, $ignore_last_checkin = false ) {
			$this->send_optin_usage_checkin( $override, $ignore_last_checkin );
			$this->send_tracking_checkin( $override, $ignore_last_checkin );
		}

		/**
		 * Manually sends tracking data if optin is enabled. Testing purposes only.
		 *
		 * @since 1.8.4
		 *
		 * @return void
		 */
		public function test_checkin() {
			if ( is_admin() && current_user_can( 'manage_options' ) && defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:ignore
				// detect the query string in the admin url.
				$send_checkin = isset( $_GET['charitable_send_checkin'] ) ? sanitize_text_field( wp_unslash( $_GET['charitable_send_checkin'] ) ) : false; // phpcs:ignore
				if ( charitable_is_debug() ) {
					error_log( 'charitable test checkin triggered' ); // phpcs:ignore
				}
				if ( 'usage' === $send_checkin ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable test checkin was run for usage' ); // phpcs:ignore
					}
					$this->send_optin_usage_checkin( true, true );
				} elseif ( 'tracking' === $send_checkin ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable test checkin was run for tracking' ); // phpcs:ignore
					}
					$this->send_tracking_checkin( true, true );
				} elseif ( 'both' === $send_checkin ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable test checkin was run for both' ); // phpcs:ignore
					}
					$this->send_optin_usage_checkin( true, true );
					$this->send_tracking_checkin( true, true );
				}
			}
		}

		/**
		 * Fetch tracking data.
		 *
		 * @since 1.8.4
		 * @version 1.8.4.5
		 *
		 * @return array $data Tracked data.
		 */
		private function get_optin_data() {

			global $wpdb;

			$data = array();

			// get charitable settings.
			$charitable_settings = get_option( 'charitable_settings' );

			// Retrieve current theme info.
			$theme_data = wp_get_theme();

			$count_b = 1;
			if ( is_multisite() ) {
				if ( function_exists( 'get_blog_count' ) ) {
					$count_b = get_blog_count();
				} else {
					$count_b = '0';
				}
			}

			$charitable_object = charitable();

			// get license info.
			$licenses        = ! empty( $charitable_settings['licenses'] ) ? $charitable_settings['licenses'] : array();
			$country         = ! empty( $charitable_settings['country'] ) ? $charitable_settings['country'] : '';
			$currency        = ! empty( $charitable_settings['currency'] ) ? $charitable_settings['currency'] : '';
			$default_gateway = ! empty( $charitable_settings['default_gateway'] ) ? $charitable_settings['default_gateway'] : '';

			$data['php_version']        = phpversion();
			$data['wpchar_version']     = $charitable_object !== null ? charitable()->get_version() : '';
			$data['wp_version']         = get_bloginfo( 'version' );
			$data['servertype']         = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
			$data['upgraded_from']      = get_option( 'charitable_upgraded_from', '' );
			$data['activated']          = get_option( 'charitable_activated', '' );
			$data['activated_datetime'] = get_option( 'wpcharitable_activated_datetime', '' );
			$data['first_campaign']     = get_option( 'charitable_first_campaign', '' );
			$data['first_donation']     = get_option( 'charitable_first_donation', '' );
			$data['multisite']          = is_multisite();
			$data['url']                = home_url();
			$data['themename']          = $theme_data->Name; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data['themeversion']       = $theme_data->Version; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$data['email']              = get_bloginfo( 'admin_email' );
			$data['licenses']           = $licenses;
			$data['country']            = $country;
			$data['currency']           = $currency;
			$data['default_gateway']    = $default_gateway;
			$data['pro']                = function_exists( 'charitable_pro' ) ? charitable_is_pro() : false;
			$data['sites']              = $count_b;
			$data['usagetracking']      = get_option( 'charitable_usage_tracking_config', false );
			$data['usercount']          = function_exists( 'count_users' ) ? count_users()['total_users'] : '0';
			$data['timezoneoffset']     = gmdate( 'P' );
			$data['wc_active']          = $this->check_if_wc_active();
			$data['usages']             = array(
				'blocks'   => $this->block_count_summation(),
				'wp_pages' => $this->get_wp_pages(),
				'wp_posts' => $this->get_wp_posts(),
			);

			// Add recommendation tracking data
			$data['recommended_plugins_viewed'] = get_option( 'charitable_recommended_plugins_viewed', array() );
			$data['recommended_plugins_clicked'] = get_option( 'charitable_recommended_plugins_clicked', array() );
			$data['recommended_plugins_installed'] = get_option( 'charitable_recommended_plugins_installed', array() );
			$data['recommended_plugins_activated'] = get_option( 'charitable_recommended_plugins_activated', array() );
			$data['dashboard_enhance_section_views'] = get_option( 'charitable_dashboard_enhance_views', 0 );

			// Retrieve current plugin information.
			if ( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$plugins        = array_keys( get_plugins() );
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $plugin, $active_plugins, true ) ) {
					// Remove active plugins from list so we can show active and inactive separately.
					unset( $plugins[ $key ] );
				}
			}

			$data['active_plugins']   = $active_plugins;
			$data['inactive_plugins'] = $plugins;
			$data['locale']           = get_locale();

			return $data;
		}


		/**
		 * Fetch tracking data.
		 *
		 * @since 1.8.4
		 *
		 * @return array $data Tracked data.
		 */
		private function get_tracking_data() {

			global $wpdb;

			$data = array();

			$data['id'] = $this->get_site_aid();

			// get the total number of campaigns.
			$data['campaign_counts'] = (array) wp_count_posts( 'campaign' );

			// campaign data.
			$data['campaign_data'] = (array) $this->get_charitable_data();

			// donation data.
			$data['donation_data']           = $this->get_donation_data();
			$data['recurring_donation_data'] = $this->get_recurring_donation_data();

			return $data;
		}

		/**
		 * Get Charitable data.
		 *
		 * @return array $data Charitable data.
		 */
		public function get_charitable_data() {

			global $wpdb;

			$sql = "SELECT
                        SUM(subquery.total_amount) AS grand_total_amount,
                        AVG(subquery.total_amount) AS grand_average_amount,
                        SUM(subquery.total_count_donations) AS grand_total_count_donations
                    FROM (
                        SELECT
                            SUM(ccd.amount) AS total_amount,
                            COUNT(ccd.donation_id) AS total_count_donations,
                            cd.donor_id
                        FROM {$wpdb->prefix}charitable_donors cd
                        JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
                        GROUP BY cd.donor_id
                    ) AS subquery;
            ";

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

			$data = [];

			if ( ! empty( $results ) ) {
				// Ensure each amount is converted to a string before sanitization.
				$data['total'] = 0 !== $results[0]['grand_total_amount']
					? charitable_sanitize_amount( (string) ( $results[0]['grand_total_amount'] ), true )
					: 0;

				$data['average']     = 0 !== $results[0]['grand_average_amount']
					? charitable_sanitize_amount( (string) ( $results[0]['grand_average_amount'] ), true )
					: 0;
				$data['donor_count'] = 0 !== $results[0]['grand_total_count_donations'] ? intval( $results[0]['grand_total_count_donations'] ) : 0;
			}

			if ( ! empty( $data ) ) {
				return $data;
			}
		}

		/**
		 * Get donation related data.
		 *
		 * @since 1.8.4
		 *
		 * @return array $data Donation data.
		 */
		public function get_donation_data() {

			global $wpdb;

			$defaults = array(
				'start_date'  => false,
				'end_date'    => false,
				'post_status' => 'charitable-completed',
				'campaign_id' => false,
				'category_id' => false,
			);

			// Extract individual variables from defaults array.
			$start_date  = $defaults['start_date'];
			$end_date    = $defaults['end_date'];
			$post_status = $defaults['post_status'];
			$campaign_id = $defaults['campaign_id'];
			$category_id = $defaults['category_id'];

			$where_sql   = array();
			$where_sql[] = 'WHERE 1=1';
			$where_sql[] = 'p.post_type = "%s"';
			$where_sql[] = 'p.post_status = "' . $post_status . '"';
			$where_sql[] = 'pm1.meta_key = "donation_gateway"';

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			$left_join   = array();
			$left_join[] = $wpdb->prefix . 'charitable_campaign_donations cd ON p.ID = cd.donation_id';
			$left_join[] = $wpdb->prefix . 'charitable_donors cdonors ON cd.donor_id = cdonors.donor_id';
			$left_join[] = $wpdb->prefix . 'postmeta pm1 ON p.ID = pm1.post_id';

			// remove all empty values from the array.
			$left_join      = array_filter( $left_join );
			$left_join_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join );

			$sql = "SELECT SUM(cd.amount) AS total_amount,
			cd.donation_id AS donation_id,
			cd.campaign_id AS campaign_id,
			cd.donor_id AS donor_id,
			cd.amount AS amount,
			pm1.meta_value AS payment_gateway,
			p.post_date AS post_date,
			p.post_date_gmt AS post_date_gmt,
			p.post_status AS donation_status,
			p.post_type AS donation_type,
			p.post_parent AS donation_parent_id
			FROM $wpdb->posts p
			" . $left_join_args . '
			' . $where_args . '
			GROUP BY p.ID';

			$donations = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					$sql, // phpcs:ignore
					'donation'
				)
			);

			// get total all time donations.
			$total_donations = 0;
			foreach ( $donations as $donation ) {
				$total_donations += $donation->total_amount;
			}

			// get total donations in the past 7 days.
			$donations_7_days = 0;
			foreach ( $donations as $donation ) {
				if ( strtotime( $donation->post_date ) > strtotime( '-7 days' ) ) {
					$donations_7_days += $donation->total_amount;
				}
			}

			// get total donations from the last 30 days.
			$donations_30_days = 0;
			foreach ( $donations as $donation ) {
				if ( strtotime( $donation->post_date ) > strtotime( '-30 days' ) ) {
					$donations_30_days += $donation->total_amount;
				}
			}

			// get donations by payment gateway.
			$donations_payment_gateway = array();
			foreach ( $donations as $donation ) {
				if ( ! isset( $donations[ $donation->payment_gateway ] ) ) {
					$donations_payment_gateway[ $donation->payment_gateway ] = 0;
				}
				$donations_payment_gateway[ $donation->payment_gateway ] += $donation->total_amount;
			}

			return array(
				'total_donations'           => $total_donations,
				'donations_7_days'          => $donations_7_days,
				'donations_30_days'         => $donations_30_days,
				'donations_payment_gateway' => $donations_payment_gateway,
			);
		}

		/**
		 * Get recurring donation related data.
		 *
		 * @since 1.8.4
		 *
		 * @return array $data Recurring donation data.
		 */
		public function get_recurring_donation_data() {

			global $wpdb;

			// if the recurring donations plugin is not active, return an empty array.
			if ( ! class_exists( 'Charitable_Recurring' ) ) {
				return array();
			}

			$where_sql   = array();
			$where_sql[] = 'WHERE 1=1';
			$where_sql[] = 'p.post_type = "%s"';
			$where_sql[] = 'p.post_status = "charitable-active"';
			$where_sql[] = 'pm1.meta_key = "donation_gateway"';
			$where_sql[] = 'pm2.meta_key = "donation_period"';
			$where_sql[] = 'pm3.meta_key = "_first_donation"';
			$where_sql[] = 'pm4.meta_key = "_expiration_date"';

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			$left_join      = array();
			$left_join[]    = $wpdb->prefix . 'postmeta pm1 ON p.ID = pm1.post_id';
			$left_join[]    = $wpdb->prefix . 'postmeta pm2 ON p.ID = pm2.post_id';
			$left_join[]    = $wpdb->prefix . 'postmeta pm3 ON p.ID = pm3.post_id';
			$left_join[]    = $wpdb->prefix . 'postmeta pm4 ON p.ID = pm4.post_id';
			$left_join      = array_filter( $left_join );
			$left_join_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join );

			$sql = "SELECT SUM(pm4.meta_value) AS total_recurring_amount,
			pm1.meta_value AS payment_gateway,
			pm2.meta_value AS donation_period,
			pm3.meta_value AS first_donation,
			pm4.meta_value AS expiration_date,
			p.post_date AS post_date,
			p.post_date_gmt AS post_date_gmt,
			p.post_status AS donation_status,
			p.post_parent AS donation_parent_id
			FROM $wpdb->posts p
			" . $left_join_args . '
			' . $where_args . '
			GROUP BY p.ID';

			$recurring_donations = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					$sql, // phpcs:ignore
					'recurring_donation'
				)
			);

			$total_recurring_amount = $recurring_donations ? array_sum( wp_list_pluck( $recurring_donations, 'first_donation' ) ) : 0;

			// get recurring donations by payment gateway.
			$recurring_donations_payment_gateway = array();
			foreach ( $recurring_donations as $donation ) {
				if ( ! isset( $recurring_donations_payment_gateway[ $donation->payment_gateway ] ) ) {
					$recurring_donations_payment_gateway[ $donation->payment_gateway ] = 0;
				}
				$recurring_donations_payment_gateway[ $donation->payment_gateway ] += $donation->first_donation;
			}

			// get recurring donations by period.
			$recurring_donations_period = array();
			foreach ( $recurring_donations as $donation ) {
				if ( ! isset( $recurring_donations_period[ $donation->donation_period ] ) ) {
					$recurring_donations_period[ $donation->donation_period ] = 0;
				}
				$recurring_donations_period[ $donation->donation_period ] += $donation->first_donation;
			}

			// get total recurring donations in the past 7 days.
			$recurring_donations_7_days = 0;
			foreach ( $recurring_donations as $donation ) {
				if ( strtotime( $donation->post_date ) > strtotime( '-7 days' ) ) {
					$recurring_donations_7_days += $donation->first_donation;
				}
			}

			// get total recurring donations from the last 30 days.
			$recurring_donations_30_days = 0;
			foreach ( $recurring_donations as $donation ) {
				if ( strtotime( $donation->post_date ) > strtotime( '-30 days' ) ) {
					$recurring_donations_30_days += $donation->first_donation;
				}
			}

			return array(
				'total_recurring_amount'              => $total_recurring_amount,
				'recurring_donations_payment_gateway' => $recurring_donations_payment_gateway,
				'recurring_donations_period'          => $recurring_donations_period,
				'recurring_donations_7_days'          => $recurring_donations_7_days,
				'recurring_donations_30_days'         => $recurring_donations_30_days,
			);
		}

		/**
		 * Send optin usage data.
		 *
		 * @since 1.8.4
		 *
		 * @param boolean $override            Override usage_optin_allowed.
		 * @param boolean $ignore_last_checkin Ignore last checkin flag.
		 * @return boolean
		 */
		public function send_optin_usage_checkin( $override = false, $ignore_last_checkin = false ) {

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				error_log('send_optin_usage_checkin');
				// phpcs:enable
			}

			if ( ! $this->usage_optin_allowed() && ! $override ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					error_log('charitable usage tracking not allowed');
					// phpcs:enable
				}
				return false;
			}

			// Send a maximum of once per week.
			$last_send = get_option( 'charitable_usage_tracking_last_checkin' );
			if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					error_log('charitable usage tracking not allowed because of last checkin');
					// phpcs:enable
				}
				return false;
			}

			$charitable_object  = charitable();
			$charitable_version = $charitable_object !== null ? charitable()->get_version() : '';

			$request = wp_remote_post(
				'https://usage.wpcharitable.com/capture',
				array(
					'method'      => 'POST',
					'timeout'     => 15,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'sslverify'   => false,
					'body'        => $this->get_optin_data(),
					'user-agent'  => 'CH/' . $charitable_version . '; ' . get_bloginfo( 'url' ),
				)
			);

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				error_log( 'send_optin_usage_checkin' );
				error_log( print_r( $request, true  ) );
				error_log( print_r( $this->get_optin_data(), true ) );
				// phpcs:enable
			}

			if ( ! is_wp_error( $request ) ) {
				// If we have completed successfully, recheck in 1 week.
				update_option( 'charitable_usage_tracking_last_checkin', time() );
				return true;
			} else {
				// If we have failed, recheck in 24 hours.
				update_option( 'charitable_usage_tracking_last_checkin', time() + 86400 );
				return false;
			}
		}

		/**
		 * Send tracking data.
		 *
		 * @since 1.8.4
		 *
		 * @param boolean $override            Override usage_optin_allowed.
		 * @param boolean $ignore_last_checkin Ignore last checkin flag.
		 * @return boolean
		 */
		public function send_tracking_checkin( $override = false, $ignore_last_checkin = false ) {

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				error_log('send_tracking_checkin');
				// phpcs:enable
			}

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				error_log( 'send_checkin' );
				error_log( $override );
				error_log( $ignore_last_checkin );
				error_log( 'tracking allowed' );
				error_log( $this->usage_optin_allowed() );
				// phpcs:enable
			}

			if ( ! $this->tracking_allowed() && ! $override ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					error_log( 'charitable tracking not allowed' );
					// phpcs:enable
				}
				return false;
			}

			// Send a maximum of once per week.
			$last_send = get_option( 'charitable_tracking_last_checkin' );
			if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
				if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
					// phpcs:disable
					error_log('charitable tracking not allowed because of last checkin');
					// phpcs:enable
				}
				return false;
			}

			$charitable_object  = charitable();
			$charitable_version = $charitable_object !== null ? charitable()->get_version() : '';

			$request = wp_remote_post(
				'https://tracking.wpcharitable.com/capture',
				array(
					'method'      => 'POST',
					'timeout'     => 15,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'sslverify'   => false,
					'body'        => $this->get_tracking_data(),
					'user-agent'  => 'GenericTrackingClient/1.0',
				)
			);

			if ( defined( 'CHARITABLE_DEBUG_USAGE' ) && CHARITABLE_DEBUG_USAGE ) { // phpcs:disable
				// phpcs:disable
				error_log( print_r( $request, true ) );
				error_log( print_r( $last_send, true ) );
				error_log( print_r( $charitable_version, true ) );
				error_log( print_r( $this->get_tracking_data(), true ) );
			}

			if ( ! is_wp_error( $request ) ) {
				// If we have completed successfully, recheck in 1 week.
				update_option( 'charitable_usage_tracking_last_checkin', time() );
				return true;
			} else {
				// If we have failed, recheck in 24 hours.
				update_option( 'charitable_usage_tracking_last_checkin', time() + 86400 );
				return false;
			}
		}

		/**
		 * Check if optin usage tracking is allowed.
		 *
		 * @since 1.8.4
		 *
		 * @return boolean
		 */
		private function usage_optin_allowed() {

			if ( defined( 'CHARITABLE_DISABLE_OPTIN_USAGE' ) && CHARITABLE_DISABLE_OPTIN_USAGE ) {
				return true;
			}

			return (bool) apply_filters( 'charitable_usage_tracking', charitable_get_usage_tracking_setting() );
		}

		/**
		 * Check if tracking is allowed.
		 *
		 * @since 1.8.4
		 *
		 * @return boolean
		 */
		private function tracking_allowed() {

			if ( defined( 'CHARITABLE_DISABLE_TRACKING' ) && CHARITABLE_DISABLE_TRACKING ) {
				return true;
			}

			if ( ! $this->environment_allows_tracking() ) {
				return false;
			}

			return (bool) apply_filters( 'charitable_usage_tracking', charitable_get_usage_tracking_setting() );
		}

		/**
		 * Whether the environment allows sending telemetry data.
		 *
		 * @since 1.8.4
		 *
		 * @return bool
		 */
		private function environment_allows_tracking() {

			if ( function_exists( 'wp_get_environment_type' ) && 'staging' === wp_get_environment_type() ) {
				return false;
			}

			return true;
		}

		/**
		 * Schedule send tracking data event.
		 *
		 * @since 1.8.4
		 *
		 * @return void
		 */
		public function schedule_send() {
			if ( ! wp_next_scheduled( 'charitable_usage_tracking_cron' ) ) {
				$tracking             = array();
				$tracking['day']      = wp_rand( 0, 6 );
				$tracking['hour']     = wp_rand( 0, 23 );
				$tracking['minute']   = wp_rand( 0, 59 );
				$tracking['second']   = wp_rand( 0, 59 );
				$tracking['offset']   = ( $tracking['day'] * DAY_IN_SECONDS ) +
									( $tracking['hour'] * HOUR_IN_SECONDS ) +
									( $tracking['minute'] * MINUTE_IN_SECONDS ) +
									$tracking['second'];
				$tracking['initsend'] = strtotime( "next sunday" ) + $tracking['offset'];

				wp_schedule_event( $tracking['initsend'], 'weekly', 'charitable_usage_tracking_cron' );
				update_option( 'charitable_usage_tracking_config', $tracking );
			}
		}

		/**
		 * Add schedules.
		 *
		 * @since 1.8.4
		 *
		 * @param array $schedules Available/current schedules.
		 * @return array $schedules Schedules array.
		 */
		public function add_schedules( $schedules = array() ) {
			// Adds once weekly to the existing schedules.
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once Weekly', 'charitable' ),
			);
			return $schedules;
		}

		/**
		 * Get WP Posts count.
		 *
		 * @since 1.8.4
		 *
		 * @return array $wp_post_count WP Posts count.
		 */
		public function get_wp_posts() {
			global $wpdb;

			$wp_post_count = 0;

			$results = $wpdb->get_var( // phpcs:ignore
				"SELECT COUNT(`ID`) `hits`
				FROM {$wpdb->posts}
				WHERE `post_type` = 'post' AND `post_status` = 'publish';"
			);
			if ( ! empty( $results ) ) {
				$wp_post_count = $results;
			}

			return $wp_post_count;
		}

		/**
		 * Get WP Pages count.
		 *
		 * @since 1.8.4
		 *
		 * @return array $wp_pages_count WP Pages count.
		 */
		public function get_wp_pages() {
			global $wpdb;

			$wp_pages_count = 0;

			$results = $wpdb->get_var( // phpcs:ignore
				"SELECT COUNT(`ID`) `hits`
				FROM {$wpdb->posts}
				WHERE `post_type` = 'page' AND `post_status` = 'publish';"
			);
			if ( ! empty( $results ) ) {
				$wp_pages_count = $results;
			}

			return $wp_pages_count;
		}

		/**
		 * Insert time for first campaign.
		 *
		 * @since 1.8.4.5
		 *
		 * @param string $insert_or_update Insert or update.
		 * @param int    $campaign_id       Campaign ID.
		 * @param array  $data              Data.
		 * @param object $object            Object.
		 *
		 * @return void
		 */
		public function insert_time_to_first_campaign( $insert_or_update, $campaign_id, $data, $object ) {
			global $wpdb;

			// Only do this for inserts.
			if ( 'insert' !== $insert_or_update ) {
				return;
			}

			$first_campaign = get_option( 'charitable_first_campaign' );

			if ( ! $first_campaign ) {
				$first_campaign = time();
				update_option( 'charitable_first_campaign', $first_campaign );
			}
		}

		/**
		 * Insert time for first donation.
		 *
		 * @since 1.8.4.5
		 *
		 * @param int    $donation_id       Donation ID.
		 * @param object $object            Charitable_Donation_Processor Object.
		 *
		 * @return void
		 */
		public function insert_time_to_first_donation( $donation_id, $object ) {
			global $wpdb;

			$first_donation = get_option( 'charitable_first_donation' );

			if ( ! $first_donation ) {
				$first_donation = time();
				update_option( 'charitable_first_donation', $first_donation );
			}
		}

		/**
		 * Get block count summation.
		 *
		 * @since 1.8.4
		 *
		 * @return array $blocks_usage_sum Block usage summation.
		 */
		public function block_count_summation() {
			// Get all _wpchar_block_usage data.
			global $wpdb;

			$tablename = $wpdb->prefix . 'postmeta';
			$sql       = "SELECT meta_value FROM $tablename";
			$sql      .= ' WHERE meta_key = %s';
			$safe_sql  = $wpdb->prepare( $sql, '_wpchar_block_usage' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results   = $wpdb->get_results( $safe_sql ); // phpcs:ignore

			$blocks_usage_sum = array();

			// Sum all block usage data.
			if ( $results ) {
				foreach ( $results as $result ) {
					if ( $result->meta_value ) {
						$page_usage_data = maybe_unserialize( $result->meta_value );
						if ( is_array( $page_usage_data ) ) {
							foreach ( $page_usage_data as $type => $value ) {
								if ( array_key_exists( $type, $blocks_usage_sum ) ) {
									// If set.
									$blocks_usage_sum[ $type ] = array(
										'name'  => $blocks_usage_sum[ $type ]['name'],
										'count' => $blocks_usage_sum[ $type ]['count'] + $value['count'], // Sum count.
									);
								}

								if ( ! array_key_exists( $type, $blocks_usage_sum ) ) {
									// If block type is not set.
									$blocks_usage_sum[ $type ] = $value;
								}
							}
						}
					}
				}
			}

			return $blocks_usage_sum;
		}

		/**
		 * Check if WooCommerce is active or not.
		 *
		 * @since 1.8.4
		 *
		 * @return boolean true|false Return if WC active.
		 */
		public function check_if_wc_active() {
			// Check if WooCommerce is active.
			return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
		}

		/**
		 * Get the AID for the site.
		 *
		 * @since 1.8.4
		 *
		 * @return string The AID for the site.
		 */
		private function get_site_aid() {
			// Check if the AID already exists in the database.
			$aid = get_option( 'charitable_site_tracking_aid' );

			if ( $aid === false ) {
				// If it doesn't exist, generate a new one.
				$site_url    = get_site_url();  // Get the site URL.
				$random_salt = wp_generate_password( 20, false, false ); // Generate a random salt.
				// Create a hash based on the site URL and salt.
				$aid = hash( 'sha256', $site_url . $random_salt );

				// Save the generated AID in the database.
				update_option( 'charitable_site_tracking_aid', $aid );
			}

			return $aid;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.4
		 *
		 * @return Charitable_Tracking
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}


}
