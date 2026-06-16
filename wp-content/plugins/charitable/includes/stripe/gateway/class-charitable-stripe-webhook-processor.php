<?php
/**
 * Class responsible for processing webhooks.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Webhook_Processor
 * @author    Eric Daams
 * @copyright Copyright (c) 2021-2022, WPCharitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.3.0
 * @version   1.4.13
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Webhook_Processor' ) ) :

	/**
	 * Charitable_Stripe_Webhook_Processor
	 *
	 * @since 1.3.0
	 */
	class Charitable_Stripe_Webhook_Processor {

		/**
		 * Event object.
		 *
		 * @since 1.3.0
		 *
		 * @var   \Stripe\Event
		 */
		protected $event;

		/**
		 * Gateway helper.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		protected $gateway;

		/**
		 * Stripe Event object.
		 *
		 * @deprecated
		 *
		 * @since 1.3.0
		 * @since 1.4.0 Deprecated.
		 *
		 * @var   \Stripe\Event
		 */
		protected $stripe_event;

		/**
		 * Create class object.
		 *
		 * @since 1.3.0
		 *
		 * @param \Stripe\Event $event Incoming event object.
		 *
		 */
		public function __construct( \Stripe\Event $event ) {
			$this->event   = $event;
			$this->gateway = new Charitable_Gateway_Stripe_AM();
		}

		/**
		 * Process an incoming Stripe IPN.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public static function process() {

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'Charitable_Stripe_Webhook_Processor PROCESS FUNCTION ' );
			}
			// phpcs:enable

			/* Retrieve and validate the request's body with signature verification. */
			$event = self::get_validated_incoming_event();

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( print_r( $event, true ) );
			}
			// phpcs:enable

			if ( ! $event ) {
				status_header( 403 );
				die( __( 'Invalid or unverified Stripe event.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			// get_validated_incoming_event() now returns a verified \Stripe\Event object.
			if ( ! ( $event instanceof \Stripe\Event ) ) {
				status_header( 400 );
				die( __( 'Unable to construct Stripe object with payload.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$processor = new Charitable_Stripe_Webhook_Processor( $event );
			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'Charitable_Stripe_Webhook_Processor PROCESS FUNCTION RUN' );
			}
			// phpcs:enable
			$processor->run();
		}

		/**
		 * Run the processor.
		 *
		 * @since  1.3.0
		 * @since  1.8.9.5 Fixed fatal error when ErrorException is thrown by using type-safe exception handling.
		 *
		 * @return void
		 */
		public function run() {
			$this->set_stripe_api_key();

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( sprintf( '[STRIPE_WEBHOOK] run() started - Event: %s | Type: %s', $this->event->id, $this->event->type ) );
			}
			// phpcs:enable

			try {
				status_header( 200 );

				/* This is Stripe's test webhook, so just die with a success message. */
				if ( 'evt_00000000000000' == $this->event->id ) {
					die( __( 'Test webhook successfully received.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				$this->run_event_processors();

				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] run() completed successfully - Event: %s | Type: %s', $this->event->id, $this->event->type ) );
				}
				// phpcs:enable

				die( __( 'Webhook processed.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			} catch ( Exception $e ) {
				// Handle Stripe API exceptions that have getJsonBody method
				if ( method_exists( $e, 'getJsonBody' ) ) {
					$body = $e->getJsonBody();
					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log( sprintf( '[STRIPE_WEBHOOK] Stripe API Error - Event: %s | Message: %s', $this->event->id, $body['error']['message'] ) );
					}
					// phpcs:enable
				} else {
					// Handle generic PHP exceptions (ErrorException, etc.)
					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log( sprintf( '[STRIPE_WEBHOOK] Exception caught - Event: %s | Type: %s | Exception: %s | File: %s:%d', $this->event->id, $this->event->type, get_class( $e ) . ': ' . $e->getMessage(), $e->getFile(), $e->getLine() ) );
					}
					// phpcs:enable
				}

				status_header( 500 );
				die( __( 'Error while processing webhook.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}//end try
		}

		/**
		 * Set Stripe API key.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean True if the API key is set. False otherwise.
		 */
		public function set_stripe_api_key() {
			$keys = $this->gateway->get_keys( false === $this->event->livemode );

			if ( empty( $keys['secret_key'] ) ) {
				return false;
			}

			return $this->gateway->setup_api( $keys['secret_key'] );
		}

		/**
		 * Get the account ID for the site.
		 *
		 * @since  1.3.0
		 *
		 * @return string|null Account ID if successfull. Null if the account couldn't be retrieved from Stripe.
		 */
		public function get_site_account_id() {
			$account_id = $this->gateway->get_value( 'account_id' );

			if ( empty( $account_id ) ) {
				try {
					$this->set_stripe_api_key();

					$account    = \Stripe\Account::retrieve();
					$account_id = $account->id;

					/* Store the account id in the gateway settings. */
					$options                                  = get_option( 'charitable_settings' );
					$options['gateways_stripe']['account_id'] = $account_id;

					update_option( 'charitable_settings', $options );

				} catch ( Exception $e ) {
					$account_id = null;
				}
			}

			return $account_id;
		}

		/**
		 * Check whether the current event is from a Connect webhook and is signed for the platform account.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function is_connect_webhook_for_site_account_id() {
			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'is_connect_webhook_for_site_account_id' );
				error_log( print_r( $this->event->account, true ) );
				error_log( print_r( $this->get_site_account_id(), true ) );
				error_log( print_r( isset( $this->event->account ) && $this->get_site_account_id() == $this->event->account, true ) );
			}
			// phpcs:enable
			return isset( $this->event->account ) && $this->get_site_account_id() == $this->event->account; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		}

		/**
		 * Checks whether the current event is from a Connect webhook and
		 * is for a transaction taking place directly on a connected account.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function is_connect_webhook_for_connected_account() {
			return isset( $this->event->account ) && $this->get_site_account_id() != $this->event->account; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		}

		/**
		 * Get the options array to pass when retrieving the event from Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @return array
		 */
		public function get_options() {
			if ( isset( $this->event->account ) ) {
				return [
					'stripe_account' => $this->event->account,
				];
			}

			return [];
		}

		/**
		 * Sets up any default event processors.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function run_event_processors() {
			/**
			 * Default event processors.
			 *
			 * @since 1.3.0
			 *
			 * @param array $processors Array of Stripe event types and associated callback functions.
			 */
			$default_processors = apply_filters(
				'charitable_stripe_default_event_processors',
				[
					'charge.refunded'               => [ $this, 'process_refund' ],
					'invoice.created'               => [ $this, 'process_invoice_created' ],
					'invoice.payment_failed'        => [ $this, 'process_invoice_payment_failed' ],
					'invoice.payment_succeeded'     => [ $this, 'process_invoice_payment_succeeded' ],
					'customer.subscription.updated' => [ $this, 'process_customer_subscription_updated' ],
					'customer.subscription.deleted' => [ $this, 'process_customer_subscription_deleted' ],
					'payment_intent.payment_failed' => [ $this, 'process_payment_intent_payment_failed' ],
					'payment_intent.succeeded'      => [ $this, 'process_payment_intent_succeeded' ],
					'checkout.session.completed'    => [ $this, 'process_checkout_session_completed' ],
				]
			);

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'run_event_processors' );
				error_log( 'default_processors' );
				error_log( print_r( $default_processors, true ) );
				error_log( '$this->event->type' );
				error_log( print_r( $this->event->type, true ) );
			}
			// phpcs:enable

			/* Check if this event can be handled by one of our built-in event processors. */
			if ( array_key_exists( $this->event->type, $default_processors ) ) {

				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( 'array_key_exists' );
				}
				// phpcs:enable

				/**
				 * Double-check that this isn't a Connect webhook for the site account.
				 *
				 * We want to skip processing for those because there will be a duplicate
				 * standard webhook coming in as well, for the same event.
				 *
				 * If you still want to do something with the Connect webhook, you can use
				 * the `charitable_stripe_ipn_event` hook below.
				 */
				if ( ! $this->is_connect_webhook_for_site_account_id() ) {

					// phpcs:disable
					if ( charitable_is_debug() ) {
						error_log( '! $this->is_connect_webhook_for_site_account_id()' );
					}
					// phpcs:enable

					$processor = $default_processors[ $this->event->type ];
				if ( is_callable( $processor ) ) {
					$message = $processor( $this->event );
				} else {
					$message = 'Invalid processor';
				}

					/* Kill processing with a message returned by the event processor. */
					die( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

			/**
			 * Fire an action hook to process the event.
			 *
			 * Note that this will only fire for webhooks that have not already been processed by one
			 * of the default webhook handlers above.
			 *
			 * @since 1.0.0
			 *
			 * @param string        $event_type Type of event.
			 * @param \Stripe\Event $event      Stripe event object.
			 */
			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'before charitable_stripe_ipn_event' );
			}
			// phpcs:enable
			do_action( 'charitable_stripe_ipn_event', $this->event->type, $this->event );
			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'after charitable_stripe_ipn_event' );
			}
			// phpcs:enable
		}

		/**
		 * Process a refund initiated via the Stripe dashboard.
		 *
		 * @see    https://stripe.com/docs/api#events
		 *
		 * @since  1.3.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message
		 */
		public function process_refund( $event ) {
			$charge = $event->data->object;

			/**
			 * If we're missing a donation ID, stop processing.
			 * This probably isn't a Charitable payment.
			 */
			if ( ! isset( $charge->metadata->donation_id ) ) {
				return __( 'Donation Webhook: Missing donation ID', 'charitable' );
			}

			$donation_id   = $charge->metadata->donation_id;
			$refund        = $charge->refunds->data[0];
			$refund_amount = $refund->amount;

			if ( ! Charitable_Stripe_Gateway_Processor::is_zero_decimal_currency( $refund->currency ) ) {
				$refund_amount = $refund_amount / 100;
			}

			if ( Charitable::DONATION_POST_TYPE !== get_post_type( $donation_id ) ) {
				return __( 'Donation Webhook: Refund donation ID not valid', 'charitable' );
			}

			$donation = new Charitable_Donation( $donation_id );

			/**
			 * Ensure that the gateway transaction ID matches the charge ID, to avoid refunding a
			 * donation originally made on a different site.
			 *
			 * @see https://bitbucket.org/wpcharitable/charitable-stripe/issues/54/webhooks-distinguish-between-webhooks-for
			 */
			if ( $donation->get_gateway_transaction_id() != $charge->id ) {
				return __( 'Donation Webhook: Charge ID does not match donation reference on this site', 'charitable' );
			}

			$donation->process_refund( $refund_amount, __( 'Donation refunded from the Stripe dashboard.', 'charitable' ) );

			return __( 'Donation Webhook: Refund processed', 'charitable' );
		}

		/**
		 * Process the payment_intent.payment_failed webhook.
		 *
		 * @since  1.4.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message.
		 */
		public function process_payment_intent_payment_failed( $event ) {
			$payment_intent = $event->data->object;

			/* Process a failed payment intent for a subscription payment. */
			if ( ! is_null( $payment_intent->invoice ) ) {
				return $this->process_payment_intent_payment_failed_for_subscription( $event );
			}

			if ( ! isset( $payment_intent->metadata->donation_id ) ) {
				return __( 'Donation Webhook: Missing donation ID', 'charitable' );
			}

			$donation_id = $payment_intent->metadata->donation_id;

			if ( Charitable::DONATION_POST_TYPE !== get_post_type( $donation_id ) ) {
				return __( 'Donation Webhook: Donation ID not valid', 'charitable' );
			}

			$donation = new Charitable_Donation( $donation_id );

			/**
			 * Ensure that the payment intent matches the one we have on record for this
			 * donation, to make sure this is the correct donation.
			 *
			 * @see https://github.com/Charitable/Charitable-Stripe/issues/54/
			 */
			if ( get_post_meta( $donation_id, '_stripe_payment_intent', true ) != $payment_intent->id ) {
				return __( 'Donation Webhook: Payment Intent does not match donation reference on this site', 'charitable' );
			}

			/* Log the payment error along with the error code. */
			$donation->log()->add(
				sprintf(
					'%1$s Error code: <a href="%2$s" target="_blank">%3$s</a>',
					$payment_intent->last_payment_error->message,
					$payment_intent->last_payment_error->doc_url,
					$payment_intent->last_payment_error->code
				)
			);

			/* Record the number of payment failures. */
			$this->update_payment_failure_count( $donation, $payment_intent->id );

			/* Mark the donation as Failed. */
			$donation->update_status( 'charitable-failed' );

			return __( 'Donation Webhook: Donation marked as Failed', 'charitable' );
		}

		/**
		 * Process a payment intent payment failure for a subscription payment.
		 *
		 * @since  1.4.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string
		 */
		public function process_payment_intent_payment_failed_for_subscription( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			$payment_intent = $event->data->object;

			try {
				$gateway = new Charitable_Gateway_Stripe_AM;
				$gateway->setup_api();

				/* Get the invoice, so we can get the subscription id from that. */
				$invoice = \Stripe\Invoice::retrieve( $payment_intent->invoice, $this->get_options() );
			} catch ( Exception $e ) {
				return __( 'Donation Webhook: Unable to retrieve invoice for failed payment intent.', 'charitable' );
			}

			$subscription = charitable_recurring_get_subscription_by_gateway_id( $invoice->subscription, 'stripe' );

			if ( ! $subscription || ! is_a( $subscription, 'Charitable_Recurring_Donation' ) ) {
				return __( 'Donation Webhook: No matching subscription found for invoice with failed payment intent.', 'charitable' );
			}

			$subscription_log = new Charitable_Stripe_Recurring_Donation_Log( $subscription );
			$first_donation   = $subscription->get_first_donation_id();

			/* Make sure this is not for the first donation. */
			if ( 'charitable-pending' != get_post_status( $first_donation ) ) {
				$subscription_log->log_failed_renewal_invoice( $invoice->id, $invoice->payment_intent );

				/* Mark the subscription as cancelled. */
				if ( 'canceled' == $payment_intent->status ) {
					$subscription->update_status( 'charitable-cancelled' );
					return __( 'Donation Webhook: Recurring donation for payment intent marked as cancelled.', 'charitable' );
				} else {
					$subscription->update_status( 'charitable-cancel' );
					return __( 'Donation Webhook: Recurring donation for payment intent marked as pending cancellation.', 'charitable' );
				}
			}

			/* This was the first donation, so mark the recurring donation as failed. */
			$subscription->set_to_failed(
				$subscription_log->get_failed_invoice_log_message( $invoice->id, $invoice->payment_intent )
			);

			/* Log the payment error along with the error code. */
			$donation = new Charitable_Donation( $first_donation );
			$donation->log()->add(
				sprintf(
					'%1$s Error code: <a href="%2$s" target="_blank">%3$s</a>',
					$payment_intent->last_payment_error->message,
					$payment_intent->last_payment_error->doc_url,
					$payment_intent->last_payment_error->code
				)
			);

			/* Record the number of payment failures. */
			$this->update_payment_failure_count( $donation, $invoice->payment_intent );

			/* Mark the donation as Failed. */
			$donation->update_status( 'charitable-failed' );

			return __( 'Donation Webhook: Recurring donation and initial payment for payment intent marked as failed', 'charitable' );
		}

		/**
		 * Process the payment_intent.succeeded webhook.
		 *
		 * @since  1.4.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message.
		 */
		public function process_payment_intent_succeeded( $event ) {

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'process_payment_intent_succeeded' );
			}
			// phpcs:enable

			$payment_intent = $event->data->object;

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( print_r( $payment_intent, true ) );
			}
			// phpcs:enable

			if ( ! isset( $payment_intent->metadata->donation_id ) ) {
				return __( 'Donation Webhook: Missing donation ID', 'charitable' );
			}

			$donation_id = $payment_intent->metadata->donation_id;

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( print_r( $donation_id, true ) );
			}
			// phpcs:enable

			if ( Charitable::DONATION_POST_TYPE !== get_post_type( $donation_id ) ) {
				return __( 'Donation Webhook: Donation ID not valid', 'charitable' );
			}

			/**
			 * Ensure that the payment intent matches the one we have on record for this
			 * donation, to make sure this is the correct donation.
			 *
			 * @see https://bitbucket.org/wpcharitable/charitable-stripe/issues/54/webhooks-distinguish-between-webhooks-for
			 */
			if ( get_post_meta( $donation_id, '_stripe_payment_intent', true ) != $payment_intent->id ) {
				return __( 'Donation Webhook: Payment Intent does not match donation reference on this site', 'charitable' );
			}

			$donation = new Charitable_Donation( $donation_id );

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( print_r( $donation, true ) );
			}
			// phpcs:enable

			/* Update the donation log. */
			$log = new Charitable_Stripe_Donation_Log( $donation );

			if ( $this->is_connect_webhook_for_connected_account() ) {
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( 'is_connect_webhook_for_connected_account' );
				}
				// phpcs:enable
				$log->log_connected_account( $this->event->account );
				$log->log_connect_details( $payment_intent );
			} else {
				// phpcs:disable
				if ( charitable_is_debug() ) {
					error_log( 'NOT is_connect_webhook_for_connected_account' );
					error_log( print_r( $payment_intent->charges->data[0]->id, true ) );
				}
				// phpcs:enable
				$log->log_charge( $payment_intent->charges->data[0]->id );

				/**
				 * If this was a payment on the platform but with funds going to a
				 * connected account, log the relevant details.
				 */
				if ( ! is_null( $payment_intent->application_fee_amount ) ) {
					$log->log_connect_details( $payment_intent );
				}
			}

			// phpcs:disable
			if ( charitable_is_debug() ) {
				error_log( 'made it to charitable-completed' );
			}
			// phpcs:enable
			/* Finally, update the donation status. */
			$donation->update_status( 'charitable-completed' );

			return __( 'Donation Webhook: Donation marked as Paid', 'charitable' );
		}

		/**
		 * Process the checkout.session.completed webhook.
		 *
		 * When a session is completed, a `payment_intent.succeeded` or
		 * `payment_intent.payment_failed` event is also fired, so we
		 * update the status of the donation when that is received.
		 *
		 * However, since the payment intent is not logged when the
		 * donation is initially processed for Checkout, we record
		 * the payment intent in the Donation log here.
		 *
		 * @since  1.4.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message.
		 */
		public function process_checkout_session_completed( $event ) {
			$session     = $event->data->object;
			$donation_id = $session->client_reference_id;

			/**
			 * Ensure that the session id matches the session id we recorded for this donation.
			 *
			 * @see https://bitbucket.org/wpcharitable/charitable-stripe/issues/54/webhooks-distinguish-between-webhooks-for
			 */
			if ( $session->id != get_post_meta( $donation_id, '_stripe_session_id', true ) ) {
				return __( 'Donation Webhook: Session id does not match donation reference on this site', 'charitable' );
			}

			/* Ensure the post type is correct. */
			if ( Charitable::DONATION_POST_TYPE !== get_post_type( $donation_id ) ) {
				return __( 'Donation Webhook: Donation ID not valid', 'charitable' );
			}

			/* Process subscriptions separately. */
			if ( 'subscription' === $session->mode ) {
				return $this->process_checkout_session_completed_for_subscription( $event );
			}

			/* If this is not a subscription, we need a payment intent. */
			if ( is_null( $session->payment_intent ) ) {
				return __( 'Donation Webhook: Missing payment intent', 'charitable' );
			}

			/* Mark the donation as complete. */
			$donation = new Charitable_Donation( $donation_id );
			$donation->update_status( 'charitable-completed' );

			/* Log the Payment Intent to the session. */
			$log = new Charitable_Stripe_Donation_Log( $donation );

			if ( $this->is_connect_webhook_for_connected_account() ) {
				$log->log_connected_account( $this->event->account );
				$log->log_connect_details_with_payment_intent_id( $session->payment_intent, $this->get_options() );
			} else {
				$log->log_payment_intent( $session->payment_intent );
			}

			return __( 'Session Webhook: Donation updated with Payment Intent data', 'charitable' );
		}

		/**
		 * Process a checkout.session.completed event for a subscription.
		 *
		 * @since  1.4.3
		 *
		 * @param  object $event The Stripe event object.
		 * @return string
		 */
		public function process_checkout_session_completed_for_subscription( $event ) {
			$session     = $event->data->object;
			$donation_id = $session->client_reference_id;

			/* Make sure we have a subscription. */
			if ( is_null( $session->subscription ) ) {
				return __( 'Session Webhook: Missing subscription', 'charitable' );
			}

			/* Mark the donation as complete. */
			$donation     = charitable_get_donation( $donation_id );
			$subscription = $donation->get_donation_plan();

			/* Make sure a valid subscription exists. */
			if ( ! $subscription ) {
				return __( 'Session Webhook: Invalid subscription', 'charitable' );
			}

			/* Log the subscription id. */
			$log = new Charitable_Stripe_Recurring_Donation_Log( $subscription );
			$log->log_subscription( $session->subscription );

			/* If the subscription should end after a certain amount of time, set that. */
			if ( method_exists( $subscription, 'get_donation_length' ) ) {
				$length = (int) $subscription->get_donation_length();

			if ( $length ) {
				$cancel_at = charitable_recurring_calculate_future_date(
					$length,
					$subscription->get_donation_period(),
					date( 'Y-m-d 00:00:00' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'U'
				);

					/* Set the cancel_at in the subscription. */
					try {
						$stripe_sub            = \Stripe\Subscription::retrieve( $session->subscription, $this->get_options() );
						$stripe_sub->cancel_at = $cancel_at - HOUR_IN_SECONDS;
						$stripe_sub->save();
					} catch ( Exception $e ) {
						$subscription->update_donation_log( __( 'Unable to set cancel time for subscription.', 'charitable' ) );
					}
				}
			}

			return __( 'Session Webhook: Donation and subscription updated with session data', 'charitable' );
		}

		/**
		 * Process the invoice.created webhook.
		 *
		 * @since  1.3.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message
		 */
		public function process_invoice_created( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			$invoice      = $event->data->object;
			$subscription = $this->get_subscription_for_webhook_object( $invoice );

			if ( ! $subscription || ! is_a( $subscription, 'Charitable_Recurring_Donation' ) ) {
				return __( 'Subscription Webhook: Missing subscription', 'charitable' );
			}

			/* Record the invoice in the subscription. */
			if ( ! $this->is_connect_webhook_for_connected_account() ) {
				$log = new Charitable_Stripe_Recurring_Donation_Log( $subscription );
				$log->log_new_invoice( $invoice->id );
			}

			return __( 'Subscription Webhook: Invoice created', 'charitable' );
		}

		/**
		 * Process the invoice.payment_failed webhook.
		 *
		 * @since  1.3.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message
		 */
		public function process_invoice_payment_failed( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			$invoice = $event->data->object;

			if ( ! in_array( $invoice->status, [ 'void', 'uncollectible' ] ) ) {
				// translators: %s is the invoice status.
				return sprintf( __( 'Subscription Webhook: Not processing invoice with a status of %s.', 'charitable' ), $invoice->status );
			}

			$subscription = $this->get_subscription_for_webhook_object( $invoice );

			if ( empty( $subscription ) || ! is_a( $subscription, 'Charitable_Recurring_Donation' ) ) {
				return __( 'Subscription Webhook: Missing subscription', 'charitable' );
			}

			$subscription_log = new Charitable_Stripe_Recurring_Donation_Log( $subscription );
			$subscription->set_to_failed(
				$subscription_log->get_failed_invoice_log_message( $invoice->id, $invoice->payment_intent )
			);

			return __( 'Subscription Webhook: Invoice payment failed', 'charitable' );
		}

		/**
		 * Process the invoice.payment_succeeded webhook.
		 *
		 * @since  1.3.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message
		 */
		public function process_invoice_payment_succeeded( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			$invoice      = $event->data->object;
			$subscription = $this->get_subscription_for_webhook_object( $invoice );

			if ( charitable_is_debug() ) {
				error_log( sprintf( '[STRIPE_WEBHOOK] invoice.payment_succeeded - Invoice: %s | Charge: %s | PaymentIntent: %s | Stripe Sub: %s', $invoice->id, $invoice->charge ?? 'null', $invoice->payment_intent ?? 'null', $invoice->subscription ?? 'null' ) ); // phpcs:ignore
			}

			if ( empty( $subscription ) || ! is_a( $subscription, 'Charitable_Recurring_Donation' ) ) {
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] No matching Charitable subscription found for Stripe sub: %s', $invoice->subscription ?? 'null' ) ); // phpcs:ignore
				}
				return __( 'Subscription Webhook: Missing subscription', 'charitable' );
			}

			$subscription_status = $subscription->get_status();

			if ( charitable_is_debug() ) {
				error_log( sprintf( '[STRIPE_WEBHOOK] Matched Charitable subscription #%d | Status: %s | Gateway Sub ID: %s', $subscription->get_donation_id(), $subscription_status, $subscription->get_gateway_subscription_id() ) ); // phpcs:ignore
			}

			/* Do not process payments for cancelled subscriptions. */
			if ( 'charitable-cancelled' === $subscription_status ) {
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] BLOCKED: Cancelled subscription #%d received invoice.payment_succeeded (Stripe: %s) - skipping to prevent reactivation', $subscription->get_donation_id(), $invoice->subscription ) ); // phpcs:ignore
				}
				return __( 'Subscription Webhook: Subscription is cancelled, skipping payment processing', 'charitable' );
			}

			/* The first donation is pending, which means this is the payment for that webhook. */
			$first_donation        = $subscription->get_first_donation_id();
			$first_donation_status = get_post_status( $first_donation );

			if ( 'charitable-pending' == $first_donation_status ) { // phpcs:ignore
				$donation_id = $first_donation;
				$donation    = charitable_get_donation( $donation_id );

				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] Processing as FIRST payment - Donation #%d (status: %s)', $donation_id, $first_donation_status ) ); // phpcs:ignore
				}
			} else {
				/* Check whether we've already added this renewal using the charge ID. */
				$existing_donation = charitable_get_donation_by_transaction_id( $invoice->charge );

				if ( $existing_donation ) {
					if ( charitable_is_debug() ) {
						error_log( sprintf( '[STRIPE_WEBHOOK] DUPLICATE BLOCKED: Charge %s already exists as donation #%d - idempotency check passed', $invoice->charge, $existing_donation ) ); // phpcs:ignore
					}
					return __( 'Subscription Webhook: Renewal has already been added', 'charitable' );
				}

				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] Creating RENEWAL donation for subscription #%d (Charge: %s not found in existing donations)', $subscription->get_donation_id(), $invoice->charge ) ); // phpcs:ignore
				}

				/* Transient lock to prevent duplicate renewals from near-simultaneous webhooks. */
				$lock_key = 'charitable_renewal_' . $invoice->charge;
				if ( get_transient( $lock_key ) ) {
					return __( 'Subscription Webhook: Renewal is already being processed', 'charitable' );
				}
				set_transient( $lock_key, true, 300 );

				$donation_id = $subscription->create_renewal_donation( array( 'status' => 'charitable-completed' ) );
				$donation    = charitable_get_donation( $donation_id );

				if ( charitable_is_debug() ) {
					if ( is_wp_error( $donation_id ) ) {
						error_log( sprintf( '[STRIPE_WEBHOOK] ERROR: Renewal creation failed - %s', $donation_id->get_error_message() ) ); // phpcs:ignore
					} else {
						error_log( sprintf( '[STRIPE_WEBHOOK] Renewal donation #%d created successfully', $donation_id ) ); // phpcs:ignore
					}
				}
			}

			/* Update the log. */
			$log = new Charitable_Stripe_Donation_Log( $donation );

			if ( $this->is_connect_webhook_for_connected_account() ) {
				$log->log_connected_account( $this->event->account );
				$log->log_connect_details_with_payment_intent_id( $invoice->payment_intent, $this->get_options() );
			} else {
				$log->log_payment_intent( $invoice->payment_intent );
				$log->log_charge( $invoice->charge );
			}

			/* Mark the first payment as complete. */
			if ( $first_donation === $donation_id ) {
				$donation->update_status( 'charitable-completed' );
			}

			/* Mark subscription as active or completed. */
			$subscription->renew();

			/* Store the donation_id in the charge's metadata to support refunds. */
			try {
				$charge           = \Stripe\Charge::retrieve( $invoice->charge, $this->get_options() );
				$charge->metadata = charitable_stripe_get_donation_metadata( $donation );
				$charge->save();
			} catch ( Exception $e ) {
				$donation->update_donation_log( __( 'Unable to save donation ID to Stripe charge metadata.', 'charitable' ) );
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] Warning: Could not save metadata to Stripe charge %s - %s', $invoice->charge, $e->getMessage() ) ); // phpcs:ignore
				}
			}

			if ( charitable_is_debug() ) {
				error_log( sprintf( '[STRIPE_WEBHOOK] SUCCESS: invoice.payment_succeeded fully processed - Donation #%d | Subscription #%d | Charge: %s', $donation_id, $subscription->get_donation_id(), $invoice->charge ) ); // phpcs:ignore
			}

			return __( 'Subscription Webhook: Payment complete', 'charitable' );
		}

		/**
		 * Process the customer.subscription.updated webhook.
		 *
		 * @since  1.3.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message
		 */
		public function process_customer_subscription_updated( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			$object       = $event->data->object;
			$subscription = $this->get_subscription_for_webhook_object( $object );

			if ( empty( $subscription ) ) {
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] customer.subscription.updated - No matching Charitable subscription for Stripe sub: %s', $object->id ?? 'null' ) ); // phpcs:ignore
				}
				return __( 'Subscription Webhook: Missing subscription', 'charitable' );
			}

			$stripe_status  = $this->get_subscription_status( $object->status );
			$current_status = $subscription->get_status();

			if ( charitable_is_debug() ) {
				error_log( sprintf( '[STRIPE_WEBHOOK] customer.subscription.updated - Subscription #%d | Stripe status: %s → Charitable status: %s | Current Charitable status: %s', $subscription->get_donation_id(), $object->status, $stripe_status, $current_status ) ); // phpcs:ignore
			}

			/* Do not allow Stripe to reactivate a subscription that was cancelled in Charitable. */
			if ( 'charitable-cancelled' === $current_status && 'charitable-cancelled' !== $stripe_status ) {
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] BLOCKED: Stripe tried to change cancelled subscription #%d to %s (Stripe status: %s) - preserving cancelled status', $subscription->get_donation_id(), $stripe_status, $object->status ) ); // phpcs:ignore
				}
				return __( 'Subscription Webhook: Subscription is cancelled in Charitable, ignoring Stripe status update', 'charitable' );
			}

			if ( $stripe_status != $current_status && 'charitable-completed' != $current_status ) { // phpcs:ignore
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] Updating subscription #%d status: %s → %s', $subscription->get_donation_id(), $current_status, $stripe_status ) ); // phpcs:ignore
				}
				$subscription->update_status( $stripe_status );
			}

			return __( 'Subscription Webhook: Recurring donation updated', 'charitable' );
		}

		/**
		 * Process the customer.subscription.deleted webhook.
		 *
		 * @since   1.3.0
		 *
		 * @param  object $event The Stripe event object.
		 * @return string Response message
		 */
		public function process_customer_subscription_deleted( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			$object       = $event->data->object;
			$subscription = $this->get_subscription_for_webhook_object( $object );

			if ( empty( $subscription ) ) {
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] customer.subscription.deleted - No matching Charitable subscription for Stripe sub: %s', $object->id ?? 'null' ) ); // phpcs:ignore
				}
				return __( 'Subscription Webhook: Missing subscription', 'charitable' );
			}

			$current_status = $subscription->get_status();

			if ( charitable_is_debug() ) {
				error_log( sprintf( '[STRIPE_WEBHOOK] customer.subscription.deleted - Subscription #%d | Current status: %s', $subscription->get_donation_id(), $current_status ) ); // phpcs:ignore
			}

			if ( 'charitable-completed' != $current_status ) { // phpcs:ignore
				$subscription->update_status( 'charitable-cancelled' );
				if ( charitable_is_debug() ) {
					error_log( sprintf( '[STRIPE_WEBHOOK] Subscription #%d cancelled (was: %s)', $subscription->get_donation_id(), $current_status ) ); // phpcs:ignore
				}
			}

			return __( 'Subscription Webhook: Recurring donation cancelled', 'charitable' );
		}

		/**
		 * Return a recurring donation object for a particular invoice, or false if
		 * none is found.
		 *
		 * @since  1.4.0
		 *
		 * @param  object $object The invoice or subscription object received from Stripe.
		 * @return Charitable_Recurring_Donation|false
		 */
		private function get_subscription_for_webhook_object( $object ) {
			$subscription_id = 'subscription' == $object->object ? $object->id : $object->subscription;

			return charitable_recurring_get_subscription_by_gateway_id( $subscription_id, 'stripe' );
		}

		/**
		 * Given a Stripe subscription status, return the corresponding Charitable status.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $status Stripe subscription status.
		 * @return string
		 */
		public function get_subscription_status( $status ) {
			switch( $status ) {
				case 'incomplete':
				case 'trialing':
					return 'charitable-pending';

				case 'active':
					return 'charitable-active';

				case 'past_due':
					return 'charitable-cancel';

				case 'canceled':
				case 'unpaid':
				case 'incomplete_expired':
					return 'charitable-cancelled';
			}
		}

		/**
		 * When payment failures for a particular payment intent, update the failure count.
		 *
		 * After three failures, cancel the payment intent.
		 *
		 * @since  1.4.9
		 *
		 * @param  Charitable_Abstract_Donation $donation       The donation to be updated.
		 * @param  string                       $payment_intent The payment intent id.
		 * @return void
		 */
		public function update_payment_failure_count( Charitable_Abstract_Donation $donation, $payment_intent ) {
			$failure_count  = (int) get_post_meta( $donation->ID, '_stripe_payment_intent_failure_count', true );
			$failure_count += 1;

			/* Update the failure count. */
			update_post_meta( $donation->ID, '_stripe_payment_intent_failure_count', $failure_count );

			/**
			 * Filter the threshold number of failures after which a payment intent
			 * should be cancelled.
			 *
			 * @since 1.4.9
			 *
			 * @param int $threshold The threshold number.
			 */
			$threshold = apply_filters( 'charitable_stripe_payment_failure_cancellation_threshold', 3 );

			/* The threshold has been reached, so cancel the payment intent. */
			if ( $threshold <= $failure_count ) {
				$intent = new Charitable_Stripe_Payment_Intent( $payment_intent );
				$intent->cancel();

				/* Add a log message. */
				$donation->log()->add(
					sprintf(
						/* translators: %d: threshold */
						__( 'The payment intent has been cancelled after %d failed payment attempts.', 'charitable' ),
						$threshold
					)
				);
			}
		}

		/**
		 * Check whether Recurring Donations is active.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		private function is_recurring_installed() {
			return class_exists( 'Charitable_Recurring' );
		}

		/**
		 * For an IPN request, get the validated incoming event object.
		 *
		 * Verifies the Stripe webhook signature to prevent forged events.
		 *
		 * @since  1.3.0
		 * @since  1.4.0 Returns an array instead of an object.
		 * @since  1.8.9.8 Verifies Stripe-Signature header using webhook signing secret.
		 *
		 * @return false|\Stripe\Event If valid, returns a verified Stripe Event object. Otherwise false.
		 */
		private static function get_validated_incoming_event() {
			$body = @file_get_contents( 'php://input' );

			if ( empty( $body ) ) {
				return false;
			}

			$sig_header     = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';
			$signing_secret = self::get_webhook_signing_secret();

			// If we have both a signature header and a signing secret, verify cryptographically.
			if ( ! empty( $sig_header ) && ! empty( $signing_secret ) ) {
				try {
					$event = \Stripe\Webhook::constructEvent( $body, $sig_header, $signing_secret );
					return $event;
				} catch ( \Stripe\Error\SignatureVerification $e ) {
					if ( charitable_is_debug() ) {
						error_log( 'Charitable Stripe webhook signature verification failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
					self::record_verification_failure();
					return false;
				} catch ( \UnexpectedValueException $e ) {
					if ( charitable_is_debug() ) {
						error_log( 'Charitable Stripe webhook invalid payload: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
					self::record_verification_failure();
					return false;
				} catch ( \Exception $e ) {
					// Catches \Stripe\Exception\SignatureVerificationException from newer Stripe SDK versions
					// in addition to the legacy \Stripe\Error\SignatureVerification namespace above.
					if ( charitable_is_debug() ) {
						error_log( 'Charitable Stripe webhook signature verification failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
					self::record_verification_failure();
					return false;
				}
			}

			// Fallback: if no signing secret is stored yet (e.g., legacy installs before upgrade),
			// verify the event by re-fetching it from the Stripe API using the event ID.
			$event_data = json_decode( $body, true );

			if ( ! is_array( $event_data ) || ! array_key_exists( 'id', $event_data ) ) {
				self::record_verification_failure();
				return false;
			}

			// Do not accept events with obviously forged IDs.
			if ( 0 !== strpos( $event_data['id'], 'evt_' ) ) {
				self::record_verification_failure();
				return false;
			}

			try {
				$gateway = new Charitable_Gateway_Stripe_AM();
				$gateway->setup_api();

				// Re-retrieve the event from Stripe API to confirm authenticity.
				$event = \Stripe\Event::retrieve( $event_data['id'] );
				return $event;
			} catch ( \Exception $e ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable Stripe webhook event verification via API failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				self::record_verification_failure();
				return false;
			}
		}

		/**
		 * Retrieve the webhook signing secret for the current mode.
		 *
		 * Checks all possible signing secret option keys (live/test, connect/direct)
		 * and returns the first non-empty one found.
		 *
		 * @since  1.8.9.8
		 *
		 * @return string|false The signing secret, or false if not found.
		 */
		private static function get_webhook_signing_secret() {
			// Allow override via constant (useful for Stripe CLI testing).
			if ( defined( 'CHARITABLE_WEBHOOK_SIGNING_SECRET' ) && CHARITABLE_WEBHOOK_SIGNING_SECRET ) {
				return CHARITABLE_WEBHOOK_SIGNING_SECRET;
			}

			$test_mode = charitable_get_option( 'test_mode', false );

			// Check the signing secret keys in priority order based on current mode.
			if ( $test_mode ) {
				$keys = array( 'test_connect_webhook_signing_secret', 'test_webhook_signing_secret' );
			} else {
				$keys = array( 'live_connect_webhook_signing_secret', 'live_webhook_signing_secret' );
			}

			foreach ( $keys as $key ) {
				$secret = charitable_get_option( array( 'gateways_stripe', $key ) );
				if ( ! empty( $secret ) ) {
					return $secret;
				}
			}

			return false;
		}

		/**
		 * Record a webhook verification failure for admin alerting.
		 *
		 * Increments a transient counter that expires after 24 hours.
		 * When the count exceeds the threshold, an admin notice is displayed.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		private static function record_verification_failure() {
			$transient_key = 'charitable_stripe_webhook_verification_failures';
			$count         = (int) get_transient( $transient_key );
			set_transient( $transient_key, $count + 1, DAY_IN_SECONDS );
		}
	}

endif;
