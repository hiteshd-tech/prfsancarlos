<?php
/**
 * Class responsible for adding logs & meta about Square recurring donations.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Recurring_Donation_Log
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Recurring_Donation_Log' ) ) :

	/**
	 * Charitable_Square_Recurring_Donation_Log
	 *
	 * @since 1.4.0
	 */
	class Charitable_Square_Recurring_Donation_Log extends Charitable_Square_Donation_Log {

		/**
		 * The donation object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Recurring_Donation
		 */
		protected $donation;

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param Charitable_Recurring_Donation $donation The recurring donation object.
		 */
		public function __construct( Charitable_Recurring_Donation $donation ) {
			$this->donation = $donation;
		}

		/**
		 * Log the recurring donation's subscription id.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $subscription The subscription.
		 * @return boolean Whether the subscription id was logged. If it has already been logged previously,
		 *                 this will return false. Otherwise it will be logged now.
		 */
		public function log_subscription( $subscription ) {
			/* The subscription has already been logged. */
			if ( $subscription == $this->donation->get_gateway_subscription_id() ) {
				return false;
			}

			$this->donation->set_gateway_subscription_id( $subscription );
			$this->donation->update_donation_log(
				sprintf(
				/* translators: %s: link to subscription object in Square dashboard */
					__( 'Square subscription ID: %s', 'charitable' ),
					'<a href="' . $this->get_resource_link( 'subscription', $subscription ) . '" target="_blank"><code>' . $subscription . '</code></a>'
				)
			);

			return true;
		}

		/**
		 * Log a new invoice for the subscription.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $invoice_id The invoice id.
		 * @return true
		 */
		public function log_new_invoice( $invoice_id ) {
			$this->donation->update_donation_log(
				sprintf(
					/* translators: %s: link to invoice object in Square dashboard */
					__( 'New invoice created for the subscription: %s', 'charitable' ),
					'<a href="' . $this->get_resource_link( 'invoice', $invoice_id ) . '" target="_blank"><code>' . $invoice_id . '</code></a>'
				)
			);

			return true;
		}

		/**
		 * Log a failed invoice payment.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $invoice_id The invoice id.
		 * @param  string $payment_intent The payment intent id.
		 * @return true
		 */
		public function log_failed_renewal_invoice( $invoice_id, $payment_intent ) {
			$this->donation->update_donation_log( $this->get_failed_invoice_log_message( $invoice_id, $payment_intent ) );

			return true;
		}

		/**
		 * Get the message to log when an invoice payment fails.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $invoice_id The invoice id.
		 * @param  string $payment_intent The payment intent id.
		 * @return string
		 */
		public function get_failed_invoice_log_message( $invoice_id, $payment_intent ) {
			return sprintf(
				/* translators: %1$s: invoice id; %2$s: payment intent with link */
				__( 'Payment for invoice %1$s failed. Square payment intent: %2$s', 'charitable' ),
				$invoice_id,
				'<a href="' . $this->get_resource_link( 'payment', $payment_intent ) . '" target="_blank"><code>' . $payment_intent . '</code></a>'
			);
		}
	}

endif;
