<?php
/**
 * Class responsible for adding logs & meta about Stripe donations.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Donation_Log
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Donation_Log' ) ) :

	/**
	 * Charitable_Stripe_Donation_Log
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Donation_Log {

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
		 * Log the donation's payment intent.
		 *
		 * @since  1.4.0
		 *
		 * @param  string  $payment_intent The payment intent.
		 * @param  boolean $add_to_log     Whether to add the payment intent to the donation log.
		 * @return boolean Whether the payment intent was logged. If it has already been logged previously,
		 *                 this will return false. Otherwise it will be logged now.
		 */
		public function log_payment_intent( $payment_intent, $add_to_log = true ) {
			/* The payment intent has already been logged. */
			if ( get_post_meta( $this->donation->ID, '_stripe_payment_intent', true ) == $payment_intent ) {
				return false;
			}

			update_post_meta( $this->donation->ID, '_stripe_payment_intent', $payment_intent );

			if ( $add_to_log ) {
				$this->donation->update_donation_log(
					sprintf(
						/* translators: %s: link to Stripe payment intent details */
						__( 'Stripe payment intent: %s', 'charitable' ),
						'<a href="' . $this->get_resource_link( 'payment', $payment_intent ) . '" target="_blank"><code>' . $payment_intent . '</code></a>'
					)
				);
			}

			return true;
		}

		/**
		 * Log the donation's underlying charge object.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $charge The charge id.
		 * @return boolean Whether the charge was logged.
		 */
		public function log_charge( $charge ) {
			/* The charge has already been logged. */
			if ( $charge === $this->donation->get_gateway_transaction_id() ) {
				return false;
			}

			$this->donation->set_gateway_transaction_id( $charge );

			return true;
		}

		/**
		 * Log a Checkout Session id.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $session_id The session id.
		 * @return boolean Whether the session id was logged.
		 */
		public function log_session_id( $session_id ) {
			/* The session id has already been logged. */
			if ( $session_id == get_post_meta( $this->donation->ID, '_stripe_session_id', true ) ) {
				return false;
			}

			update_post_meta( $this->donation->ID, '_stripe_session_id', $session_id );

			return true;
		}

		/**
		 * Log the account that the payment was made on.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $account The Stripe account id.
		 * @return boolean Whether the account was logged.
		 */
		public function log_connected_account( $account ) {
			/* The payment intent has already been logged. */
			if ( get_post_meta( $this->donation->ID, '_stripe_account_id', true ) == $account ) {
				return false;
			}

			update_post_meta( $this->donation->ID, '_stripe_account_id', $account );

			$this->donation->update_donation_log(
				sprintf(
					/* translators: %s: link to connected account page in Stripe */
					__( 'Payment made directly on connected account: %s', 'charitable' ),
					'<a href="' . $this->get_resource_link( 'connect/account', $account ) . '" target="_blank"><code>' . $account . '</code></a>'
				)
			);

			return true;
		}

		/**
		 * Log references to details about Connect transactions made directly on connected accounts.
		 *
		 * We log the transfer record and the application fee record.
		 *
		 * @since  1.4.0
		 *
		 * @param  Stripe\PaymentIntent $payment_intent The payment intent object returned from Stripe.
		 * @return boolean Whether any details were logged.
		 */
		public function log_connect_details( Stripe\PaymentIntent $payment_intent ) {
			$logged = false;

			/* The transfer record and application fee are included in the underlying charge objects. */
			$charge = $payment_intent->charges->data[0];

			if ( ! empty( $charge->application_fee ) ) {
				$logged = $this->log_application_fee( $charge->application_fee, $charge->application_fee_amount );
			}

			if ( ! empty( $charge->transfer ) ) {
				$logged = $logged || $this->log_transfer_to_connected_account( $charge->transfer );
			}

			return $logged;
		}

		/**
		 * Log references to details about Connect transactions made directly on connected accounts.
		 *
		 * We log the transfer record and the application fee record.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $payment_intent_id The payment intent object returned from Stripe.
		 * @param  array  $options           The options array used in the API request.
		 * @return boolean Whether any details were logged.
		 */
		public function log_connect_details_with_payment_intent_id( $payment_intent_id, $options ) {
			try {
				return $this->log_connect_details( \Stripe\PaymentIntent::retrieve( $payment_intent_id, $options ) );
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Log the application fee.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $application_fee        The application fee.
		 * @param  int    $application_fee_amount The application fee amount.
		 * @return boolean Whether the application fee was logged.
		 */
		public function log_application_fee( $application_fee, $application_fee_amount ) {
			/* The application fee has already been logged. */
			if ( get_post_meta( $this->donation->ID, '_stripe_application_fee', true ) == $application_fee ) {
				return false;
			}

			$application_fee_amount = Charitable_Stripe_Gateway_Processor::is_zero_decimal_currency() ? $application_fee_amount : $application_fee_amount / 100;

			update_post_meta( $this->donation->ID, '_stripe_application_fee', $application_fee );
			update_post_meta( $this->donation->ID, '_stripe_application_fee_amount', $application_fee_amount );

			$this->donation->update_donation_log(
				sprintf(
					/* translators: %s: link to connected account page in Stripe */
					__( '%1$s application fee collected: %2$s', 'charitable' ),
					charitable_format_money( (string) $application_fee_amount ),
					'<a href="' . $this->get_resource_link( 'connect/application_fee', $application_fee ) . '" target="_blank"><code>' . $application_fee . '</code></a>'
				)
			);

			return true;
		}

		/**
		 * Log the transfer to the connected account.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $transfer The transfer.
		 * @return boolean Whether the transfer was logged.
		 */
		public function log_transfer_to_connected_account( $transfer ) {
			/* The transfer has already been logged. */
			if ( get_post_meta( $this->donation->ID, '_stripe_transfer_to_connected_account' ) == $transfer ) {
				return false;
			}

			update_post_meta( $this->donation->ID, '_stripe_transfer_to_connected_account', $transfer );

			$this->donation->update_donation_log(
				sprintf(
					/* translators: %s: link to connected account page in Stripe */
					__( 'Transfer to connected account: %s', 'charitable' ),
					'<a href="' . $this->get_resource_link( 'connect/transfer', $transfer ) . '" target="_blank"><code>' . $transfer . '</code></a>'
				)
			);

			return true;
		}

		/**
		 * Return the link for a particular resource.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $type      The type of resource.
		 * @param  string $object_id The id of the object.
		 * @return string
		 */
		public function get_resource_link( $type, $object_id ) {
			$slug   = $type . 's/'; // Pluralize the resource.
			$prefix = $this->donation->get_test_mode() ? 'test/' : '';

			return 'https://dashboard.stripe.com/' . $prefix . $slug . $object_id;
		}
	}

endif;
