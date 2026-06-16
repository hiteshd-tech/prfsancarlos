<?php
/**
 * Responsible for creating and updating PaymentIntent objects.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Payment_Intent
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Payment_Intent' ) ) :

	/**
	 * Charitable_Stripe_Payment_Intent
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Payment_Intent {

		/**
		 * The payment intent id.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		private $intent_id;

		/**
		 * The payment intent object.
		 *
		 * @since 1.4.0
		 *
		 * @var   \Stripe\PaymentIntent
		 */
		private $intent;

		/**
		 * The gateway helper object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		private $gateway;

		/**
		 * Options.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $options;

		/**
		 * The destination account for Stripe Connect.
		 *
		 * @since 1.4.0
		 *
		 * @var   string|null
		 */
		private $destination;

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param string      $intent_id   The payment intent id.
		 * @param array|null  $options     Additional options to pass when making Stripe API requests.
		 * @param string|null $destination The destination account. Used with Stripe Connect when transactions
		 *                                 are made on platform account.
		 */
		public function __construct( $intent_id = '', $options = null, $destination = null ) {
			$this->intent_id   = $intent_id;
			$this->options     = $options;
			$this->destination = empty( $destination ) ? null : $destination;
		}

		/**
		 * Get the gateway helper class.
		 *
		 * @since  1.4.0
		 *
		 * @return Charitable_Gateway_Stripe_AM
		 */
		public function get_gateway() {
			if ( ! isset( $this->gateway ) ) {
				$this->gateway = new Charitable_Gateway_Stripe_AM();
			}

			return $this->gateway;
		}

		/**
		 * Return a particular property of the PaymentIntent.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $property The property to get.
		 * @return mixed
		 */
		public function get( $property ) {
			$intent = $this->get_intent();

			return is_null( $intent ) ? null : $intent->$property;
		}

		/**
		 * Get the PaymentIntent object.
		 *
		 * @since  1.4.0
		 *
		 * @return \Stripe\PaymentIntent|null
		 */
		public function get_intent() {
			if ( empty( $this->intent_id ) ) {
				return null;
			}

			if ( ! isset( $this->intent ) ) {
				$this->get_gateway()->setup_api();

				$this->intent = \Stripe\PaymentIntent::retrieve( $this->intent_id, $this->options );
			}

			return $this->intent;
		}

		/**
		 * Create a payment intent.
		 *
		 * @since  1.4.0
		 *
		 * @param  int    $amount       The new payment intent amount.
		 * @param  string $future_usage The future usage. Either on_session or off_session.
		 * @return \Stripe\PaymentIntent
		 */
		public function create( $amount, $future_usage = 'on_session' ) {
			$this->get_gateway()->setup_api();

			$data = [
				'amount'             => Charitable_Stripe_Gateway_Processor::get_sanitized_donation_amount( $amount ),
				'currency'           => charitable_get_currency(),
				'setup_future_usage' => $future_usage,
			];

			if ( ! is_null( $this->destination ) ) {
				$data['transfer_data'] = [ 'destination' => $this->destination ];

				/**
				 * If desired, return true to make the connected account the settlement merchant.
				 *
				 * @since 1.4.0
				 *
				 * @param boolean $enabled Whether to make the connected account the settlement merchant.
				 */
				if ( apply_filters( 'charitable_stripe_connect_enable_on_behalf_of', false ) ) {
					$data['on_behalf_of']  = $this->destination;
				}
			}

			$this->intent    = \Stripe\PaymentIntent::create( $data, $this->options );
			$this->intent_id = $this->intent->id;

			charitable_get_session()->set( 'stripe-payment-intent', $this->intent_id );

			return $this->intent;
		}

		/**
		 * Update a payment intent.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $args The payment intent arguments.
		 * @return \Stripe\PaymentIntent
		 */
		public function update( $args ) {
			$this->get_gateway()->setup_api();

			$this->intent = \Stripe\PaymentIntent::update(
				$this->intent_id,
				$args,
				$this->options
			);

			return $this->intent;
		}

		/**
		 * Update a payment intent.
		 *
		 * @since  1.4.9
		 *
		 * @param  array $args The payment intent arguments.
		 * @return \Stripe\PaymentIntent
		 */
		public function cancel( $args = [] ) {
			return $this->get_intent()->cancel( $args );
		}

		/**
		 * Return PaymentIntent details based on the user session, or false
		 * if none exists.
		 *
		 * @since  1.4.0
		 *
		 * @param  array|null $options Options to pass to Stripe API requests.
		 * @return false|Charitable_Stripe_Payment_Intent
		 */
		public static function init_from_session( $options = null ) {
			$intent = charitable_get_session()->get( 'stripe-payment-intent' );

			if ( ! $intent ) {
				return false;
			}

			return new Charitable_Stripe_Payment_Intent( $intent, $options );
		}

		/**
		 * Clear the PaymentIntent from the user session.
		 *
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public function clear_session() {
			charitable_get_session()->remove( 'stripe-payment-intent' );
		}

		/**
		 * Handles an AJAX call to update a particular payment intent.
		 *
		 * @deprecated 1.6.0
		 *
		 * @since  1.4.0
		 * @since  1.4.7 Deprecated.
		 *
		 * @return void Output is sent as a JSON response.
		 */
		public static function ajax_update() {
			if ( ! array_key_exists( 'amount', $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				wp_send_json_error( __( 'Missing amount in request.', 'charitable' ) );
			}

			if ( 0 == $_POST['amount'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$options = $_POST['options']; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( is_array( $options ) && array_key_exists( 'stripeAccount', $options ) ) {
				$options['stripe_account'] = $options['stripeAccount'];
				unset( $options['stripeAccount'] );
			}

			if ( empty( $_POST['intent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$intent = new Charitable_Stripe_Payment_Intent( '', $options, $_POST['destination'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$intent->create( $_POST['amount'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				$intent = new Charitable_Stripe_Payment_Intent( $_POST['intent'], $options ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$intent->update(
					[
						'amount' => Charitable_Stripe_Gateway_Processor::get_sanitized_donation_amount( $_POST['amount'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					]
				);
			}

			wp_send_json_success(
				[
					'intent' => $intent->get( 'id' ),
					'secret' => $intent->get( 'client_secret' ),
				]
			);
		}

	}

endif;
