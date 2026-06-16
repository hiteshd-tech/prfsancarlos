<?php
/**
 * Class responsible for adding logs & meta about Square donations.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Donation_Log
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Donation_Log' ) ) :

	/**
	 * Charitable_Square_Donation_Log
	 *
	 * @since 1.4.0
	 */
	class Charitable_Square_Donation_Log {

		/**
		 * The donation object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Donation
		 */
		protected $donation;

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param Charitable_Donation $donation The donation object.
		 */
		public function __construct( Charitable_Donation $donation ) {
			$this->donation = $donation;
		}

		/**
		 * Log the payment update details.
		 *
		 * @since 1.8.7
		 *
		 * @param array   $payment_data The payment data.
		 * @param array   $charge The charge data.
		 * @param boolean $add_to_log Whether to add the payment update details to the donation log.
		 * @return boolean Whether the payment update details were logged.
		 */
		public function log_payment_update_details( $payment_data = array(), $charge = array(), $add_to_log = true ) {
			$logged = false;

			if ( empty( $payment_data ) || empty( $charge ) ) {
				return false;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'charitable_square_donation_log - log_payment_update_details - the payment data is ' . print_r( $payment_data, true ) );
				// phpcs:enable
			}

			// $payment_id = ! empty( $payment_data['payment_id'] ) ? $payment_data['payment_id'] : 'Not Available';
			$payment_id = ! empty( $payment_data['payment_id'] ) ? $payment_data['payment_id'] : 'Not Available';

			if ( $payment_id ) {
				$this->donation->set_gateway_payment_id( $payment_id );
			}

			if ( $add_to_log ) {
				$this->donation->update_donation_log(
					sprintf(
						/* translators: %s: link to Square payment intent details */
						__( 'Square Webhook: Payment status updated to %1$s and card details status is %2$s. Payment ID: %3$s', 'charitable' ),
						ucfirst( $payment_data['payment_status'] ),
						ucfirst( $payment_data['card_details_status'] ),
						$payment_data['payment_id']
					)
				);

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'charitable_square_donation_log - log_payment_update_details - the donation id is ' . $this->donation->ID );
					error_log( 'charitable_square_donation_log - log_payment_update_details - the charge is ' . print_r( $charge, true ) );
					// phpcs:enable
				}
			}

			$logged = true;

			return $logged;
		}

		/**
		 * Log a custom message.
		 *
		 * @since 1.8.7
		 *
		 * @param string $message The message to log.
		 */
		public function log_custom_message( $message ) {
			$this->donation->update_donation_log( $message );
		}

		/**
		 * Return the link for a particular resource.
		 *
		 * @since  1.8.7
		 *
		 * @param  string $type      The type of resource.
		 * @param  string $object_id The id of the object.
		 * @return string
		 */
		public function get_resource_link( $type, $object_id ) {

			$base_url = $this->donation->get_test_mode() ? 'https://squareupsandbox.com/dashboard/' : 'https://squareup.com/t/cmtp_performance/pr_developers/d_partnerships/p_Charitable/?route=dashboard/';

			$slug = $type . 's/'; // Pluralize the resource.

			return $base_url . $slug . $object_id;
		}
	}

endif;
