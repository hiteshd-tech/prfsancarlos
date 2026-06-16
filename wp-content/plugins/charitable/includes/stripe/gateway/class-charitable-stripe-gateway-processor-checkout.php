<?php
/**
 * Charitable_Stripe_Gateway_Processor_Checkout class.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stirpe_Gateway_Processor
 * @author    Eric Daams
 * @copyright Copyright (c) 2021, WPCharitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.13
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Gateway_Processor_Checkout' ) ) :

	/**
	 * Charitable_Stripe_Gateway_Processor_Checkout
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Gateway_Processor_Checkout extends Charitable_Stripe_Gateway_Processor implements Charitable_Stripe_Gateway_Processor_Interface {

		/**
		 * Run the processor.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function run() {
			if ( ! $this->set_stripe_api_key() ) {
				$this->donation_log->add( __( 'Missing secret API key.', 'charitable' ) );
				return false;
			}

			$args = [
				'payment_method_types'       => apply_filters( 'charitable_stripe_payment_method_types', array( 'card' ), $this->donation ),
				'success_url'                => charitable_get_permalink( 'donation_receipt_page', [ 'donation_id' => $this->donation->ID ] ),
				'cancel_url'                 => charitable_get_permalink( 'donation_cancel_page', [ 'donation_id' => $this->donation->ID ] ),
				'client_reference_id'        => $this->donation->ID,
				'billing_address_collection' => charitable_get_option( [ 'gateways_stripe', 'enable_checkout_billing_address_collection' ], false ) ? 'required' : 'auto',
			];

			$customer_id = $this->get_stripe_customer();

			if ( $this->is_recurring_donation() ) {
				$args['subscription_data'] = $this->get_subscription_data();
				$args['customer_email']    = $this->donor->get_email();
				$args['mode']              = 'subscription';
			} else {
				if ( $customer_id ) {
					$args['customer'] = $customer_id;
				}

				$args['payment_intent_data'] = $this->get_payment_intent_data();
				$args['line_items']          = $this->get_line_items();
				$args['submit_type']         = 'donate';
			}

			/**
			 * Filter the Session arguments.
			 *
			 * @since 1.4.0
			 *
			 * @param array                         $args      The Session args.
			 * @param Charitable_Donation           $donation  The donation object.
			 * @param Charitable_Donation_Processor $processor The processor object.
			 * @param Charitable_Gateway_Stripe     $gateway   The Stripe gateway class helper.
			 */
			$args = apply_filters(
				'charitable_stripe_session_args',
				$args,
				$this->donation,
				$this->processor,
				$this->gateway
			);

			/* Set up the session. */
			try {
				$session = \Stripe\Checkout\Session::create( $args, $this->options );
			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );

				charitable_get_notices()->add_error( $message );

				return false;
			}

			/* Record the session id. */
			$log = new Charitable_Stripe_Donation_Log( $this->donation );
			$log->log_session_id( $session->id );

			/* Record the payment intent id. */
			$log->log_payment_intent( $session->payment_intent );

			/* Log the connected account id. */
			if ( $this->using_connected_account() ) {
				$log->log_connected_account( $this->options['stripe_account'] );

				if ( $this->is_recurring_donation() ) {
					$recurring_log = new Charitable_Stripe_Recurring_Donation_Log( $this->donation->get_donation_plan() );
					$recurring_log->log_connected_account( $this->options['stripe_account'] );
				}
			}

			return [ 'session_id' => $session->id ];
		}

		/**
		 * Return the line items for a one-time donation.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_line_items() {
			return array_values(
				array_map(
					function ( $campaign_donation ) {
						$item = $campaign_donation->campaign_name;

						if ( empty( $item ) ) {
							// translators: %d is the campaign ID.
							$item = sprintf( __( 'Donation to campaign %d', 'charitable' ), $campaign_donation->campaign_id );
						}

						return [
							'amount'   => self::get_sanitized_donation_amount( $campaign_donation->amount ),
							'currency' => charitable_get_currency(),
							'name'     => $item,
							'quantity' => 1,
						];
					},
					$this->donation->get_campaign_donations()
				)
			);
		}

		/**
		 * Return the subscription data for a recurring donation.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_subscription_data() {
			$data = [
				'metadata' => $this->get_charge_metadata(),
				'items'    => array_values(
					array_map(
						function ( $campaign_donation ) {
							$plan    = new Charitable_Stripe_Plan( $campaign_donation->campaign_id, [ 'processor' => $this->processor ], $this->options );
							$plan_id = $plan->get_plan( true );

							if ( ! $plan_id ) {
								$plan_id = $plan->create_plan();

								if ( ! $plan_id ) {
									$this->donation_log->add( __( 'Unable to add plan.', 'charitable' ) );
									return [];
								}
							}

							return [
								'plan' => $plan_id,
							];
						},
						$this->donation->get_campaign_donations()
					)
				),
			];

			$application_fee = $this->get_application_fee_percentage();

			if ( $application_fee ) {
				$data['application_fee_percent'] = $application_fee;
			}

			$data['metadata']['recurring_donation_id'] = $this->processor->get_donation_data_value( 'donation_plan', false );

			return $data;
		}
	}

endif;
