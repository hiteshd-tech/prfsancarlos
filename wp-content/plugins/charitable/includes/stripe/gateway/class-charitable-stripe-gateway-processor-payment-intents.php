<?php
/**
 * Charitable_Stripe_Gateway_Processor_Payment_Intents class.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stirpe_Gateway_Processor
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

if ( ! class_exists( 'Charitable_Stripe_Gateway_Processor_Payment_Intents' ) ) :

	/**
	 * Charitable_Stripe_Gateway_Processor_Payment_Intents
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Gateway_Processor_Payment_Intents extends Charitable_Stripe_Gateway_Processor implements Charitable_Stripe_Gateway_Processor_Interface {

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

			/* Collect our payment method & create/get customer. */
			$payment_method = array_key_exists( 'stripe_payment_method', $_POST ) ? $_POST['stripe_payment_method'] : null; // phpcs:ignore
			$customer_id    = $this->get_stripe_customer( $payment_method );

			if ( ! $customer_id ) {
				if ( ! empty( $this->customer_error ) ) {
					$this->donation_log->add( $this->customer_error );
				} else {
					$this->donation_log->add( __( 'Unable to retrieve customer.', 'charitable' ) );
				}
				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf( '[Charitable Stripe] Donation FAILED: %s. Donation ID: %s', ! empty( $this->customer_error ) ? $this->customer_error : 'Unable to retrieve customer (no details available)', $this->donation->ID ) );
				}
				return false;
			}

			$donation_log = new Charitable_Stripe_Donation_Log( $this->donation );

			/* For donations on connected accounts, log the account id. */
			if ( $this->using_connected_account() ) {
				$donation_log->log_connected_account( $this->options['stripe_account'] );
			}

			/* If it's a recurring donation, handle it separately to keep this method clean. */
			if ( $this->is_recurring_donation() ) {
				return $this->handle_recurring_donation( $customer_id );
			}

			$intent = Charitable_Stripe_Payment_Intent::init_from_session( $this->options );

			if ( ! $intent ) {
				$intent = new Charitable_Stripe_Payment_Intent( '', $this->options, $this->destination );
				$intent->create(
					$this->donation->get_total_donation_amount( true ),
					$this->is_recurring_donation() ? 'off_session' : 'on_session'
				);
			}

			$donation_log->log_payment_intent( $intent->get( 'id' ), ! $this->using_connected_account() );

			$args = array_merge(
				$this->get_payment_intent_data(),
				[
					'amount'         => self::get_sanitized_donation_amount( $this->donation->get_total_donation_amount( true ) ),
					'customer'       => $customer_id,
					'payment_method' => $this->get_payment_method(),
				]
			);

			/**
			 * Filter the PaymentIntent arguments.
			 *
			 * @since 1.4.0
			 *
			 * @param array                         $args      The PaymentIntent args.
			 * @param Charitable_Donation           $donation  The donation object.
			 * @param Charitable_Donation_Processor $processor The processor object.
			 * @param Charitable_Gateway_Stripe_AM     $gateway   The Stripe gateway class helper.
			 */
			$args = apply_filters(
				'charitable_stripe_payment_intent_args',
				$args,
				$this->donation,
				$this->processor,
				$this->gateway
			);

			$intent->update( $args );
			$intent->clear_session();

			/* Send back the intent secret. */
			return [
				'requires_action' => true,
				'secret'          => $intent->get( 'client_secret' ),
			];
		}

		/**
		 * Handle a recurring donation.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $customer_id Stripe customer id.
		 * @return boolean|array
		 */
		public function handle_recurring_donation( $customer_id ) {
			$response = $this->add_customer_subscription( $customer_id );

			if ( is_wp_error( $response ) ) {
				charitable_get_notices()->add_error( $response->get_error_messages() );
				return false;
			}

			if ( 'incomplete' == $response->status && $this->intent_requires_action( $response->latest_invoice->payment_intent ) ) {
				return $this->send_intent_action_response( $response->latest_invoice->payment_intent );
			}

			return true;
		}

		/**
		 * Add a customer subscription.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $customer_id The customer id.
		 * @return \Stripe\Subscription|WP_Error
		 */
		public function add_customer_subscription( $customer_id ) {
			$recurring = charitable_get_donation( $this->donation->get_donation_plan_id() );

			/* Check for a valid recurring donation. */
			if ( ! $recurring ) {
				return new WP_Error(
					'missing_recurring_donation',
					__( 'Could not create customer subscription. No associated recurring donation.', 'charitable' )
				);
			}

			/* Log the connected account id. */
			$recurring_log = new Charitable_Stripe_Recurring_Donation_Log( $recurring );

			if ( $this->using_connected_account() ) {
				$recurring_log->log_connected_account( $this->options['stripe_account'] );
			}

			$campaign_id = current( $this->donation->get_campaign_donations() )->campaign_id;
			$plans       = new Charitable_Stripe_Plan( $campaign_id, [ 'recurring' => $recurring ], $this->options );
			$plan_id     = $plans->get_plan( true );

			if ( ! $plan_id ) {
				$plan_id = $plans->create_plan();

				if ( ! $plan_id ) {
					$this->donation_log->add( __( 'Unable to add plan.', 'charitable' ) );

					return new WP_Error(
						'error_creating_plan',
						__( 'Could not create recurring donation plan.', 'charitable' )
					);
				}
			}

			/* Prepare the subscription args. */
			$subscription_args = [
				'customer' => $customer_id,
				'plan'     => $plan_id,
				'expand'   => [ 'latest_invoice.payment_intent' ],
				'metadata' => $this->get_charge_metadata(),
			];

			if ( method_exists( $recurring, 'get_donation_length' ) ) {
				$length = (int) $recurring->get_donation_length();

				if ( $length ) {
					$cancel_at = charitable_recurring_calculate_future_date(
						$length,
						$recurring->get_donation_period(),
						date( 'Y-m-d 00:00:00' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						'U'
					);

					$subscription_args['cancel_at'] = $cancel_at - HOUR_IN_SECONDS;
				}
			}

			if ( $this->using_connected_account() ) {
				$application_fee_percent = charitable_get_option( [ 'gateways_stripe', 'application_fee' ], false );

				if ( $application_fee_percent ) {
					$subscription_args['application_fee_percent'] = $application_fee_percent;
				}
			}

			$subscription_args['metadata']['recurring_donation_id'] = $this->processor->get_donation_data_value( 'donation_plan', false );

			/**
			 * Filter the Subscription args.
			 *
			 * @since 1.4.0
			 *
			 * @param array                               $subscription_args The subscription args.
			 * @param Charitable_Recurring_Donation       $recurring         The recurring donation object.
			 * @param Charitable_Stripe_Gateway_Processor $processor         This gateway processor instance.
			 */
			$subscription_args = apply_filters( 'charitable_stripe_subscriptions_args', $subscription_args, $recurring, $this );

			try {
				/* Create the subscription. */
				$subscription = \Stripe\Subscription::create( $subscription_args, $this->options );

				/* Save the subscription ID. */
				if ( $this->using_connected_account() ) {
					$recurring->set_gateway_subscription_id( $subscription->id );
				} else {
					$recurring_log->log_subscription( $subscription->id );
				}

				$status = self::get_subscription_status( $subscription );

			} catch ( \Stripe\Error\Card $e ) {
				$message = __( 'There was an error processing your payment, please ensure you have entered your card number correctly.', 'charitable' );
				$status  = 'charitable-failed';
			} catch ( \Stripe\Error\ApiConnection $e ) {
				$message = __( 'There was an error processing your payment (our payment gateways\'s API is down), please try again.', 'charitable' );
				$status  = 'charitable-failed';
			} catch ( \Stripe\Error\InvalidRequest $e ) {
				$message = __( 'The payment gateway API request was invalid, please try again.', 'charitable' );
				$status  = 'charitable-failed';
			} catch ( \Stripe\Error\API $e ) {
				$message = __( 'The payment gateway API request was invalid, please try again.', 'charitable' );
				$status  = 'charitable-failed';
			} catch ( \Stripe\Error\Authentication $e ) {
				$message = __( 'The API keys entered in settings are incorrect', 'charitable' );
				$status  = 'charitable-failed';
			} catch ( Exception $e ) {
				$message = __( 'Something went wrong.', 'charitable' );
				$status  = 'charitable-failed';
			}//end try

			if ( 'charitable-failed' == $status ) {
				$recurring->set_to_failed( __( 'Initial subscription payment failed', 'charitable' ) );

				return new WP_Error(
					'error_creating_subscription',
					$message
				);
			}

			$recurring->update_status( $status );

			return $subscription;
		}

		/**
		 * Return the customer's payment method.
		 *
		 * @since  1.4.0
		 *
		 * @return string|null
		 */
		public function get_payment_method() {
			$customer         = isset( $this->connected_customer ) ? $this->connected_customer : $this->customer;
			$invoice_settings = $customer->get( 'invoice_settings' );

			return $invoice_settings['default_payment_method'];
		}

		/**
		 * Check whether a payment intent requires further action.
		 *
		 * @since  1.4.0
		 *
		 * @param  \Stripe\PaymentIntent $intent The Payment Intent.
		 * @return boolean
		 */
		public function intent_requires_action( \Stripe\PaymentIntent $intent ) {
			return 'requires_action' == $intent->status;
		}

		/**
		 * Send a response describing the next action the intent requires.
		 *
		 * @since  1.4.0
		 *
		 * @param  \Stripe\PaymentIntent $intent The Payment Intent.
		 * @return array
		 */
		public function send_intent_action_response( \Stripe\PaymentIntent $intent ) {
			switch ( $intent->next_action->type ) {
				case 'use_stripe_sdk':
					return [
						'requires_action' => true,
						'secret'          => $intent->client_secret,
					];

				case 'redirect_to_url':
					return [
						'redirect_to' => $intent->redirect_to_url->url,
					];
			}
		}
	}

endif;
