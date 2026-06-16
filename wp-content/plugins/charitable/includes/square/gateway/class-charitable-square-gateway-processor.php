<?php
/**
 * Base Charitable_Square_Gateway_Processor_Interface instance.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Gateway_Processor
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Gateway_Processor' ) ) :

	/**
	 * Charitable_Square_Gateway_Processor
	 *
	 * @since 1.8.0
	 */
	class Charitable_Square_Gateway_Processor {

		/**
		 * The donation object.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Donation
		 */
		protected $donation;

		/**
		 * Donation log instance for this donation.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Donation_Log
		 */
		protected $donation_log;

		/**
		 * The donor object.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Donor
		 */
		protected $donor;

		/**
		 * The donation processor object.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Donation_Processor
		 */
		protected $processor;

		/**
		 * The Square gateway model.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Gateway_Square_AM
		 */
		protected $gateway;

		/**
		 * Submitted donation values.
		 *
		 * @since 1.8.0
		 *
		 * @var   array
		 */
		protected $donation_data;

		/**
		 * Options passed to Square with certain API requests.
		 *
		 * @since 1.8.0
		 *
		 * @var   array
		 */
		protected $options = array();

		/**
		 * Connect mode.
		 *
		 * This will remain unset if Square Connect is not active. If
		 * it is active, this will either be `direct` or `charge_owner`.
		 *
		 * @since 1.8.0
		 *
		 * @var   string
		 */
		protected $connect_mode;

		/**
		 * Application fee.
		 *
		 * This will remain unset if Square Connect is not active.
		 *
		 * @since 1.8.0
		 *
		 * @var   string
		 */
		protected $application_fee;

		/**
		 * Destination account.
		 *
		 * This will remain unset if Square Connect is not active or
		 * if Connect mode is set to `direct`.
		 *
		 * @since 1.8.0
		 *
		 * @var   string
		 */
		protected $destination;

		/**
		 * Customer object, always on the platform.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Square_Customer
		 */
		protected $customer;

		/**
		 * Connected account customer object.
		 *
		 * @since 1.8.0
		 *
		 * @var   Charitable_Square_Connected_Customer
		 */
		protected $connected_customer;

		/**
		 * Set up class instance.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {}

		/**
		 * Get the donation ID for a given gateway transaction ID.
		 *
		 * @since  1.8.7
		 *
		 * @param  string $transaction_id The gateway transaction ID.
		 * @return int|false The donation ID if found, false otherwise.
		 */
		public static function get_donation_with_gateway_transaction_id( $transaction_id ) {

			// Make sure the transaction ID is a string and trimmed.
			$transaction_id = trim( $transaction_id );

			// Direct SQL query since we know the data exists.
			global $wpdb;
			$donation_id = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND pm.meta_key = %s
				AND pm.meta_value = %s
				LIMIT 1",
					'donation',
					'_gateway_transaction_id',
					$transaction_id
				)
			);

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Direct SQL Query Result: ' . ( $donation_id ? $donation_id : 'false' ) );
				// phpcs:enable
			}

			return $donation_id ? (int) $donation_id : false;
		}

		/**
		 * Get the donation ID for a given gateway customer ID.
		 *
		 * @since  1.8.7
		 *
		 * @param  string $customer_id The gateway customer ID.
		 * @return int|false The donation ID if found, false otherwise.
		 */
		public static function get_donation_with_gateway_customer_id( $customer_id ) {
			global $wpdb;

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_donation_with_gateway_customer_id - starting' );
				error_log( 'get_donation_with_gateway_customer_id - customer_id: ' . $customer_id );
				// phpcs:enable
			}

			// the customer id is stored in donormeta table, either square_customer_id_test or square_customer_id_live depending on the test mode.
			$key = charitable_get_option( 'test_mode' ) ? 'square_customer_id_test' : 'square_customer_id_live';

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_donation_with_gateway_customer_id - key: ' . $key );
				// phpcs:enable
			}

			// get the donor id from the donormeta table that matches the customer id.
			$sql = $wpdb->prepare(
				"SELECT d.donor_id
				FROM {$wpdb->prefix}charitable_donormeta dm
				INNER JOIN {$wpdb->prefix}charitable_donors d ON dm.donor_id = d.donor_id
				WHERE dm.meta_key = %s
				AND dm.meta_value = %s
				LIMIT 1",
				$key,
				$customer_id
			);
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_donation_with_gateway_customer_id - donor sql: ' . $sql );
				// phpcs:enable
			}
			$donor_id = $wpdb->get_var( $sql ); // phpcs:ignore

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_donation_with_gateway_customer_id - donor_id: ' . $donor_id );
				// phpcs:enable
			}

			// now via the campaign_donations table find the latest donation for this donor id.
			$sql = $wpdb->prepare(
				"SELECT cd.donation_id
				FROM {$wpdb->prefix}charitable_campaign_donations cd
				WHERE cd.donor_id = %s
				ORDER BY cd.donation_id DESC
				LIMIT 1",
				$donor_id
			);
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_donation_with_gateway_customer_id - donation sql: ' . $sql );
				// phpcs:enable
			}
			$donation_id = $wpdb->get_var( $sql ); // phpcs:ignore

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_donation_with_gateway_customer_id - donation_id: ' . $donation_id );
				// phpcs:enable
			}

			return $donation_id ? (int) $donation_id : false;
		}

		/**
		 * Returns whether the currency is a zero decimal currency.
		 *
		 * @since  1.8.0
		 *
		 * @param  string $currency The currency for the charge. If left blank, will check for the site currency.
		 * @return boolean
		 */
		public static function is_zero_decimal_currency( $currency = null ) {
			if ( is_null( $currency ) ) {
				$currency = charitable_get_currency();
			}

			return in_array( strtoupper( $currency ), self::get_zero_decimal_currencies() ); // phpcs:ignore
		}

		/**
		 * Return all zero-decimal currencies supported by Square.
		 *
		 * @since  1.8.0
		 *
		 * @return array
		 */
		public static function get_zero_decimal_currencies() {
			return array(
				'BIF',
				'CLP',
				'DJF',
				'GNF',
				'JPY',
				'KMF',
				'KRW',
				'MGA',
				'PYG',
				'RWF',
				'VND',
				'VUV',
				'XAF',
				'XOF',
				'XPF',
			);
		}
	}

endif;
