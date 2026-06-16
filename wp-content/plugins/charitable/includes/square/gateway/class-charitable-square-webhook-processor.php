<?php
/**
 * Class responsible for processing webhooks.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Webhook_Processor
 * @author    WP Charitable
 * @copyright Copyright (c) 2021-2022, WP Charitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Webhook_Processor' ) ) :

	/**
	 * Charitable_Square_Webhook_Processor
	 *
	 * @since 1.8.7
	 */
	class Charitable_Square_Webhook_Processor {

		/**
		 * Create instance of the class.
		 *
		 * @since 1.8.7
		 *
		 * @var   Charitable_Square_Webhook_Processor
		 */
		private static $instance;

		/**
		 * Event object.
		 *
		 * @since 1.8.7
		 *
		 * @var   \Square\Event
		 */
		protected $event;

		/**
		 * Gateway helper.
		 *
		 * @since 1.8.7
		 *
		 * @var   Charitable_Gateway_Square
		 */
		protected $gateway;

		/**
		 * Square Event object.
		 *
		 * @deprecated
		 *
		 * @since 1.8.7
		 *
		 * @var   \Square\Event
		 */
		protected $square_event;

		/**
		 * Create class object.
		 *
		 * @since 1.8.7
		 *
		 * @param \Square\Event $event The Square event object.
		 */
		public function __construct( $event ) {
			$this->event   = $event;
			$this->gateway = new Charitable_Gateway_Square();
		}

		/**
		 * Process an incoming Square IPN.
		 *
		 * @since  1.8.7
		 *
		 * @return void
		 */
		public static function process() {

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Charitable_Square_Webhook_Processor PROCESS START ' );
				// phpcs:enable
			}

			// Debug: Log that the Square Core webhook processor is being called.
			if ( charitable_is_debug( 'square' ) ) {
				error_log( '[Square Core] Webhook processor called' ); // phpcs:ignore
			}

			/* Retrieve and validate the request's body. */
			$event = self::get_validated_incoming_event();

			// Debug: Log the event type if it's a refund.
			if ( charitable_is_debug( 'square' ) && $event && isset( $event['type'] ) ) {
				// phpcs:disable
				error_log( '[Square Core] Webhook event type: ' . $event['type'] );
				// phpcs:enable
			}

			if ( ! $event ) {
				status_header( 500 );
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Charitable_Square_Webhook_Processor PROCESS FUNCTION INVALID SQUARE EVENT - 500 return' );
					// phpcs:enable
				}
				die( __( 'Invalid Square event.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			try {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Charitable_Square_Webhook_Processor PROCESS FUNCTION TRY' );
					error_log( 'the event is ' . print_r( $event, true ) );
					// phpcs:enable
				}

				$event = self::construct_from_event( $event );

			} catch ( \UnexpectedValueException $e ) {

				status_header( 400 );
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Charitable_Square_Webhook_Processor PROCESS FUNCTION TRY CATCH' );
					error_log( 'the event is ' . print_r( $event, true ) );
					// phpcs:enable
				}
				die( __( 'Unable to construct Square object with payload.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			}

			$processor = new Charitable_Square_Webhook_Processor( $event );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Charitable_Square_Webhook_Processor PROCESS FUNCTION RUN' );
				// phpcs:enable
			}

			$processor->run();
		}

		/**
		 * This parses the returning event from Square and sets the event property.
		 * Under review if it's needed.
		 *
		 * @since 1.8.7
		 *
		 * @param string $event Decoded JSON object from Square.
		 */
		public static function construct_from_event( $event ) {
			return $event;
		}

		/**
		 * Run the processor.
		 *
		 * @since  1.8.7
		 *
		 * @return void
		 */
		public function run() {
			$this->set_square_api_key();

			try {
				status_header( 200 );
				$this->run_event_processors();

				die( __( 'Webhook processed.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} catch ( Exception $e ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( $e->getMessage() );
					// phpcs:enable
				}
				status_header( 500 );

				die( __( 'Error while retrieving event.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} //end try
		}

		/**
		 * Set Square API key, which is actually the access token.
		 *
		 * @since  1.8.7
		 *
		 * @return boolean True if the API key is set. False otherwise.
		 */
		public function set_square_api_key() {
			$keys = $this->gateway->get_keys( charitable_get_option( 'test_mode' ) );

			if ( empty( $keys['access_token'] ) ) {
				return false;
			}

			return $this->gateway->setup_api( $keys['access_token'] );
		}

		/**
		 * Get the account ID for the site.
		 *
		 * @since  1.8.7
		 *
		 * @return string|null Account ID if successfull. Null if the account couldn't be retrieved from Square.
		 */
		public function get_site_account_id() {
			$account_id = $this->gateway->get_account_id( 'account_id' );

			if ( empty( $account_id ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'get_site_account_id - no account id found' );
					// phpcs:enable
				}
			}

			return $account_id;
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
			return isset( $this->event->account ) && $this->get_site_account_id() != $this->event->account;
		}

		/**
		 * Get the options array to pass when retrieving the event from Square.
		 *
		 * @since  1.8.7
		 *
		 * @return array
		 */
		public function get_options() {
			if ( isset( $this->event->account ) ) {
				return array(
					'square_account' => $this->event->account,
				);
			}

			return array();
		}

		/**
		 * Sets up any default event processors.
		 *
		 * @since  1.8.7
		 *
		 * @return void
		 */
		public function run_event_processors() {
			/**
			 * Default event processors.
			 *
			 * @since 1.8.7
			 *
			 * @param array $processors Array of Square event types and associated callback functions.
			 */
			$default_processors = apply_filters(
				'charitable_square_default_event_processors',
				array(
					'refund.updated'       => array( $this, 'process_refund' ),
					'payment.created'      => array( $this, 'process_payment_created' ),
					'payment.updated'      => array( $this, 'process_payment_updated' ),
					'subscription.created' => array( $this, 'process_subscription_created' ),
					'subscription.updated' => array( $this, 'process_subscription_updated' ),
					'invoice.payment_made' => array( $this, 'process_invoice_payment_made' ),
				)
			);

			// Critical items.
			$event_type       = $this->event['type'];          // example: payment.updated.
			$event_id         = $this->event['event_id'];      // example: 6a8f5f28-54a1-4eb0-a98a-3111513fd4fc.
			$merchant_id      = $this->event['merchant_id'];   // example: 1234567890.
			$event_created_at = $this->event['created_at'];    // example: 2020-02-06T21:27:34.308Z.
			$event_data_type  = $this->event['data']['type'];  // example: payment.
			$event_data_id    = $this->event['data']['id'];    // example: hYy9pRFVxpDsO1FB05SunFWUe9JZY.

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'run_event_processors' );
				error_log( print_r( $default_processors, true ) );
				error_log( 'event type' );
				error_log( print_r( $this->event['type'], true ) );
				// phpcs:enable
			}

			/* Check if this event can be handled by one of our built-in event processors. */
			if ( array_key_exists( $this->event['type'], $default_processors ) ) {

				if ( charitable_is_debug( 'square' ) ) {
					error_log( 'array_key_exists' ); // phpcs:ignore
				}

				/**
				 * Double-check that this isn't a legacy Square webhook.
				 *
				 * @since 1.8.7
				 */
				if ( ! charitable_square_legacy_mode() ) {

					error_log( 'double checked this is not a legacy webhook' ); // phpcs:ignore

					$message = call_user_func( $default_processors[ $this->event['type'] ], $this->event );

					/* Kill processing with a message returned by the event processor. */
					die( $message ); // phpcs:ignore
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
			 * @param \Square\Event $event      Square event object.
			 */
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'before charitable_square_ipn_event' );
				// phpcs:enable
			}

			do_action( 'charitable_square_ipn_event', $this->event['type'], $this->event );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'after charitable_square_ipn_event' );
				// phpcs:enable
			}
		}

		/**
		 * Process the payment_created webhook.
		 * This isn't used in the current implementation.
		 *
		 * @see    https://developer.squareup.com/reference/square/payments-api/webhooks/payment.created
		 * @since  1.8.7
		 *
		 * @param  object $event The Square event object.
		 */
		public function process_payment_created( $event ) {
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_payment_created - starting' );
				error_log( print_r( $event, true ) );
				// phpcs:enable
			}
		}

		/**
		 * Process the invoice_payment_made webhook.
		 *
		 * @see    https://developer.squareup.com/reference/square/invoices-api/webhooks/invoice.payment_made
		 * @since  1.8.7
		 *
		 * @param  object $event The Square event object.
		 * @return string Response message
		 */
		public function process_invoice_payment_made( $event ) {
			global $wpdb;

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_invoice_payment_made - starting' );
				error_log( print_r( $event, true ) );
				// phpcs:enable
			}
			$object = $event['data']['object']['invoice'];

			$order_id        = $object['order_id'];
			$subscription_id = $object['subscription_id'];
			$status          = $object['status']; // 'PAID'
			$invoice_number  = $object['invoice_number'];
			$invoice_id      = $object['id'];
			$email           = $object['primary_recipient']['email_address'];
			$first_name      = $object['primary_recipient']['given_name'];
			$last_name       = $object['primary_recipient']['family_name'];

			// We need to find the recurring donation with the subscription_id.
			$recurring_donation_id = $wpdb->get_col( // phpcs:ignore
				$wpdb->prepare(
					"SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_gateway_subscription_id'
				AND meta_value = %s
				LIMIT 1",
					$subscription_id
				)
			);
			// If any exist, get the first one. Otherwise, return an error.
			if ( count( $recurring_donation_id ) > 0 ) {
				$recurring_donation_id = $recurring_donation_id[0];
			} else {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_invoice_payment_made - no recurring donation found for subscription_id: ' . $subscription_id );
					error_log( print_r( $recurring_donation_id, true ) );
					// phpcs:enable
				}
				return __( 'Invoice Webhook: No recurring donation found for subscription ID', 'charitable' );
			}

			// Get the recurring donation object.
			$recurring_donation = charitable_get_donation( $recurring_donation_id );

			// If the recurring donation is not found, return an error.
			if ( ! $recurring_donation ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_invoice_payment_made - no recurring donation found for subscription_id: ' . $subscription_id );
					// phpcs:enable
				}
				return __( 'Invoice Webhook: No recurring donation found for subscription ID', 'charitable' );
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'recurring donation found' );
				error_log( print_r( $recurring_donation, true ) );
				// phpcs:enable
			}

			// If the recurring donation is not active, return an error.
			if ( 'charitable-active' !== $recurring_donation->get_status() ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_invoice_payment_made - recurring donation is not active' );
					// phpcs:enable
				}
				return __( 'Invoice Webhook: Recurring donation is not active', 'charitable' );
			}

			// Get the information so we can create a new one-time donation that has a post parent of the recurring donation.
			// Updated in 1.8.8.4 to use the new method.
			$amount    = $this->get_donation_amount_with_fees( $recurring_donation );
			$donations = $recurring_donation->get_campaign_donations();

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_invoice_payment_made - donations' );
				error_log( print_r( $donations, true ) );
				// phpcs:enable
			}

			$amount        = $donations[0]->amount;
			$campaign_id   = $donations[0]->campaign_id;
			$campaign_name = $donations[0]->campaign_name;
			$donor_id      = $recurring_donation->get_donor_id();

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_invoice_payment_made - donor_id' );
				error_log( print_r( $donor_id, true ) );
				// phpcs:enable
			}

			// We need to see if this is the first time a donation has been made for this subscription.
			// Because that means in Charitable, there is an initial (pending) donation that needs to be completed.
			$first_donation = $wpdb->get_col( // phpcs:ignore
				$wpdb->prepare(
					"SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_square_subscription_donation_first_donation'
				AND meta_value = %d
				LIMIT 1",
					$recurring_donation_id
				)
			);
			if ( count( $first_donation ) > 0 ) {
				// First donation exists! Update!

				$first_donation_id = $first_donation[0];
				$first_donation    = charitable_get_donation( $first_donation_id );

				// This donation needs to be completed.
				$first_donation->update_status( 'charitable-completed' );

				if ( $order_id ) {
					$first_donation->set_gateway_transaction_id( $order_id );
					$message = sprintf( '%s: %s', __( 'Square Order / Transaction ID processor1', 'charitable' ), $order_id );
					$first_donation->log()->add( $message );
				}

				if ( $invoice_id && $invoice_number ) {
					$message = sprintf( '%s: %s', __( 'Square Invoice ID and Number', 'charitable' ), $invoice_id . ' - ' . $invoice_number );
					$first_donation->log()->add( $message );
				}

				// We need to delete the meta key so that we don't do this again.
				delete_post_meta( $first_donation_id, '_square_subscription_donation_first_donation' );

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'donation upgraded to completed' );
					error_log( print_r( $first_donation, true ) );
					// phpcs:enable
				}
			} else {
				// No first donation exists! Create!

				// Translate Square status to Charitable status.
				switch ( $status ) {
					case 'PAID':
						$status = 'charitable-completed';
						break;
					case 'FAILED':
						$status = 'charitable-failed';
						break;
					default:
						$status = 'charitable-pending';
						break;
				}

				// Create the new donation.
				$new_donation_id = charitable_create_donation(
					array(
						'status'    => $status,
						'gateway'   => 'square',
						'note'      => '',
						'campaigns' => array(
							array(
								'campaign_id' => $campaign_id,
								'amount'      => $amount,
							),
						),
						'user'      => array(
							'first_name' => $first_name,
							'last_name'  => $last_name,
							'email'      => $email,
						),
					)
				);

				$new_donation = new Charitable_Donation( $new_donation_id );

				// Update post parent of the new donation to the recurring donation.
				// Use WP functions to update post parent.
				wp_update_post(
					array(
						'ID'          => $new_donation_id,
						'post_parent' => $recurring_donation_id,
					)
				);

				// Set the gateway subscription id of the new donation to the subscription_id.
				$new_donation->set_gateway_subscription_id( $subscription_id );

				if ( $order_id ) {
					$new_donation->set_gateway_transaction_id( $order_id );
					$message = sprintf( '%s: %s', __( 'Square Order / Transaction ID processor2', 'charitable' ), $order_id );
					$new_donation->log()->add( $message );
				}

				if ( $invoice_id && $invoice_number ) {
					$message = sprintf( '%s: %s', __( 'Square Invoice ID and Number', 'charitable' ), $invoice_id . ' - ' . $invoice_number );
					$new_donation->log()->add( $message );
				}

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'new donation created' );
					error_log( print_r( $new_donation, true ) );
					// phpcs:enable
				}
			}

			return __( 'Donation Webhook: Invoice payment received', 'charitable' );
		}

		/**
		 * Process the payment_updated webhook.
		 *
		 * @see    https://developer.squareup.com/reference/square/refunds-api/webhooks/payment.updated
		 * @since  1.8.7
		 *
		 * @param  object $event The Square event object.
		 * @return string Response message
		 */
		public function process_payment_updated( $event ) {
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_payment_updated - starting' );
				// phpcs:enable
			}

			$charge = (array) $event['data']['object']['payment'];

			if ( ! isset( $charge['order_id'] ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square Webhook - No order ID found... here is the charge' );
					error_log( print_r( $charge, true ) );
					error_log( 'Square Webhook - No order ID found... here is the event' );
					error_log( print_r( $event, true ) );
					// phpcs:enable
				}
				return __( 'Donation Webhook: Missing order ID', 'charitable' );
			}

			$order_id    = $charge['order_id'];
			$customer_id = isset( $charge['customer_id'] ) ? $charge['customer_id'] : '';
			if ( empty( $customer_id ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square Webhook - No customer ID found... here is the charge' );
					error_log( print_r( $charge, true ) );
					// phpcs:enable
				}
			}
			$donation_id = Charitable_Square_Gateway_Processor::get_donation_with_gateway_transaction_id( $order_id );

			if ( ! $donation_id ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square Webhook - No donation found for order ID: ' . $order_id );
					error_log( 'Square Webhook - Going to try to find the donation with the customer ID: ' . $customer_id );
					// phpcs:enable
				}
				// If this is a recurring donation, we have to find the last donation made by the customer (using the customer_id).
				// This is likely the donation that created the subscription. Once we have that, we can add the order_id (also known as the transaction_id) to this single donation for future reference.
				$donation_id = Charitable_Square_Gateway_Processor::get_donation_with_gateway_customer_id( $customer_id );
				if ( ! $donation_id ) {
					if ( charitable_is_debug( 'square' ) ) {
						// phpcs:disable
						error_log( 'Square Webhook - No donation found for order ID: ' . $order_id );
						// phpcs:enable
					}
					return __( 'Donation Webhook: No donation found for order ID', 'charitable' );
				} elseif ( charitable_is_debug( 'square' ) ) {
						// phpcs:disable
						error_log( 'Square Webhook - Found donation for customer ID: ' . $customer_id );
						// phpcs:enable

				}
			}

			$donation = new Charitable_Donation( $donation_id );

			// If there isn't a gateway transaction id (order_id), we need to add it to the donation.
			if ( ! $donation->get_gateway_transaction_id() ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square Webhook - Adding gateway transaction ID to donation (LIKELY A RECURRING DONATION): ' . $order_id );
					// phpcs:enable
				}
				$donation->set_gateway_transaction_id( $order_id );
			}

			if ( ! $donation ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square Webhook - No donation found for donation ID: ' . $donation_id );
					// phpcs:enable
				}
				return __( 'Donation Webhook: No donation found for donation ID', 'charitable' );
			}

			$payment_data = array(
				'payment_status'      => ! empty( $charge['status'] ) ? $charge['status'] : 'unknown',
				'payment_id'          => ! empty( $charge['id'] ) ? $charge['id'] : '',
				'card_details_status' => ! empty( $charge['card_details']['status'] ) ? $charge['card_details']['status'] : '',
				'receipt_url'         => ! empty( $charge['receipt_url'] ) ? $charge['receipt_url'] : '',
			);

			if ( $charge['status'] === 'unknown' || $charge['receipt_url'] === '' ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square Webhook - Unknown payment status or receipt URL' );
					error_log( print_r( $charge, true ) );
					error_log( 'Square Webhook - Unknown payment status or receipt URL' );
					error_log( print_r( $event, true ) );
					// phpcs:enable
				}
				return __( 'Donation Webhook: Unknown payment status or receipt URL', 'charitable' );
			}

			/* Update the donation log. */
			$log = new Charitable_Square_Donation_Log( $donation );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'made it to where we update the status of the donation to ' . $payment_data['payment_status'] );
				error_log( 'the donation has a status of ' . $donation->get_status() . ' and the payment status is ' . strtolower( $payment_data['payment_status'] ) );
				// phpcs:enable
			}

			$current_donation_status = $donation->get_status();

			// If this is a refunded donation, we don't need to do anything.
			if ( ! empty( $charge['refunded_money'] ) && ! empty( $charge['refunded_money']['amount'] ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_payment_updated - Refunded donation' );
					error_log( print_r( $charge, true ) );
					// phpcs:enable
				}
				return __( 'Donation Webhook: Payment ignored (Refunded donation)', 'charitable' );
			}

			// Test the overall payment transaction status.
			switch ( strtolower( $payment_data['payment_status'] ) ) {
				// Payment completed successfully.
				case 'completed':
					if ( charitable_is_debug( 'square' ) ) {
						// phpcs:disable
						error_log( 'process_payment_updated - card details status ' . strtolower( $payment_data['card_details_status'] ) );
						// phpcs:enable
					}
					switch ( strtolower( $payment_data['card_details_status'] ) ) {
						case 'authorized':
							if ( charitable_is_debug( 'square' ) ) {
								// phpcs:disable
								error_log( 'process_payment_updated - authorized' );
								// phpcs:enable
							}
							// Funds are held but not captured.
							if ( 'charitable-pending' !== $current_donation_status ) {
								$donation->update_status( 'charitable-pending' );
								$log->log_payment_update_details( $payment_data, $charge );
								if ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation has changed to a status of ' . $donation->get_status() );
									// phpcs:enable
								}
							} elseif ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation already has a status of charitable-pending' );
									// phpcs:enable

							}
							break;
						case 'captured':
							if ( charitable_is_debug( 'square' ) ) {
								// phpcs:disable
								error_log( 'process_payment_updated - captured' );
								// phpcs:enable
							}
							// The payment has fully completed its lifecycle
							// The funds have been successfully captured from the card. ALL GOOD!
							if ( 'charitable-completed' !== $current_donation_status ) {
								$donation->update_status( 'charitable-completed' );
								$log->log_payment_update_details( $payment_data, $charge );
								if ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation has changed to a status of ' . $donation->get_status() );
									// phpcs:enable
								}
							} elseif ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation already has a status of charitable-completed' );
									// phpcs:enable

							}
							break;
						case 'failed':
							if ( charitable_is_debug( 'square' ) ) {
								// phpcs:disable
								error_log( 'process_payment_updated - failed' );
								// phpcs:enable
							}
							// Card processing failed.
							if ( 'charitable-failed' !== $current_donation_status ) {
								$donation->update_status( 'charitable-failed' );
								$log->log_payment_update_details( $payment_data, $charge );
								if ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation has changed to a status of ' . $donation->get_status() );
									// phpcs:enable
								}
							} elseif ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation already has a status of charitable-failed' );
									// phpcs:enable

							}
							break;
						case 'voided':
							if ( charitable_is_debug( 'square' ) ) {
								// phpcs:disable
								error_log( 'process_payment_updated - voided' );
								// phpcs:enable
							}
							// The payment was voided.
							if ( 'charitable-failed' !== $current_donation_status ) {
								$donation->update_status( 'charitable-failed' );
								$log->log_payment_update_details( $payment_data, $charge );
								if ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation has changed to a status of ' . $donation->get_status() );
									// phpcs:enable
								}
							} elseif ( charitable_is_debug( 'square' ) ) {
									// phpcs:disable
									error_log( 'the donation already has a status of charitable-failed' );
									// phpcs:enable

							}
							break;
						default:
							// The card details status is unknown.
							$log->log_payment_update_details( $payment_data, $charge );
							if ( charitable_is_debug( 'square' ) ) {
								// phpcs:disable
								error_log( 'process_payment_updated - could not do anything with this payment status: ' . $payment_data['payment_status'] );
								// phpcs:enable
							}
							break;
					}
					break;
				// Payment was canceled.
				case 'canceled':
				case 'failed':
					if ( 'charitable-failed' !== $current_donation_status ) {
						$donation->update_status( 'charitable-failed' );
						$log->log_payment_update_details( $payment_data, $charge );
						if ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'the donation has changed to a status of ' . $donation->get_status() );
							// phpcs:enable
						}
					} elseif ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'the donation already has a status of charitable-failed' );
							// phpcs:enable

					}
					break;
				// Payment approved but not yet completed.
				case 'approved':
					if ( 'charitable-pending' !== $current_donation_status ) {
						$donation->update_status( 'charitable-pending' );
						$log->log_payment_update_details( $payment_data, $charge );
						if ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'the donation has changed to a status of ' . $donation->get_status() );
							// phpcs:enable
						}
					} elseif ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'the donation already has a status of charitable-pending' );
							// phpcs:enable

					}
					break;
				// Payment is pending.
				case 'pending':
					if ( 'charitable-pending' !== $current_donation_status ) {
						$donation->update_status( 'charitable-pending' );
						$log->log_payment_update_details( $payment_data, $charge );
						if ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'the donation has changed to a status of ' . $donation->get_status() );
							// phpcs:enable
						}
					} elseif ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'the donation already has a status of charitable-pending' );
							// phpcs:enable

					}
					break;
				default:
					if ( charitable_is_debug( 'square' ) ) {
						// phpcs:disable
						error_log( 'process_payment_updated - could not do anything with this payment status: ' . $payment_data['payment_status'] );
						// phpcs:enable
					}
					break;
			}

			return __( 'Donation Webhook: Payment updated', 'charitable' );
		}

		/**
		 * Process a refund initiated via the Square dashboard.
		 *
		 * @see    https://developer.squareup.com/docs/refunds-api/webhooks
		 * Example webhook payload for refund.created event:
		 * Example webhook payload for refund.created event:
		 * {
		 *   "merchant_id": "6SSW7HV8K2ST5",
		 *   "type": "refund.updated",
		 *   "event_id": "bc316346-6691-4243-88ed-6d651a0d0c47",
		 *   "created_at": "2020-02-06T22:14:16.421Z",
		 *   "data": {
		 *     "type": "refund",
		 *     "id": "KkAkhdMsgzn59SM8A89WgKwekxLZY_ptNBVqHYxt5gAdfcobBe4u1AZsXhoz06KTtuq9Ls24P",
		 *     "object": {
		 *       "refund": {
		 *         "id": "KkAkhdMsgzn59SM8A89WgKwekxLZY_ptNBVqHYxt5gAdfcobBe4u1AZsXhoz06KTtuq9Ls24P",
		 *         "created_at": "2020-02-06T21:27:41.836Z",
		 *         "updated_at": "2020-02-06T22:14:16.381Z",
		 *         "amount_money": {
		 *           "amount": 1000,
		 *           "currency": "USD"
		 *         },
		 *         "status": "COMPLETED",
		 *         "processing_fee": [
		 *           {
		 *             "effective_at": "2020-02-06T23:27:31.000Z",
		 *             "type": "INITIAL",
		 *             "amount_money": {
		 *               "amount": -59,
		 *               "currency": "USD"
		 *             }
		 *           }
		 *         ],
		 *         "location_id": "NAQ1FHV6ZJ8YV",
		 *         "order_id": "haOyDuHiqtAXMk0d8pDKXpL7Jg4F",
		 *         "payment_id": "KkAkhdMsgzn59SM8A89WgKwekxLZY",
		 *         "version": 10
		 *       }
		 *     }
		 *   }
		 * }
		 *
		 * @since  1.8.7
		 *
		 * @param  object $event The Square event object.
		 * @return string Response message
		 */
		public function process_refund( $event ) {
			global $wpdb;
			$charge = (array) $event['data']['object'];

			/**
			 * If we're missing a donation ID, stop processing.
			 * This probably isn't a Charitable payment.
			 */
			if ( ! isset( $charge['refund']['id'] ) ) {
				return __( 'Donation Webhook: Missing transaction ID', 'charitable' );
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square Webhook - Processing refund' );
				// phpcs:enable
			}

			$transaction_id = $charge['refund']['id'];
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square Webhook - Transaction ID: ' . $transaction_id );
				// phpcs:enable
			}

			$refund_amount = $charge['refund']['amount_money']['amount'];
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square Webhook - Refund Amount: ' . $refund_amount );
				// phpcs:enable
			}

			$refund_status = $charge['refund']['status']; // should be COMPLETED.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square Webhook - Refund Status: ' . $refund_status );
				// phpcs:enable
			}

			$order_id = $charge['refund']['order_id'];
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square Webhook - Order ID: ' . $order_id );
				// phpcs:enable
			}

			$payment_id = $charge['refund']['payment_id'];
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square Webhook - Payment ID: ' . $payment_id );
				// phpcs:enable
			}

			// We need to search post meta to find a _gateway_payment_id that matches the payment_id.
			$sql = $wpdb->prepare(
				"SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = '_gateway_payment_id'
				AND meta_value = %s",
				$payment_id
			);

			$donation_id = $wpdb->get_col( $sql ); // phpcs:ignore

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '[Square Core] Webhook - Donation ID: ' . print_r( $donation_id, true ) );
				// phpcs:enable
			}

			if ( empty( $donation_id ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( '[Square Core] Webhook - No donation found for payment ID: ' . $payment_id );
					// phpcs:enable
				}
				return __( 'Donation Webhook: Refund processed', 'charitable' );
			}

			$donation = charitable_get_donation( $donation_id[0] );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '[Square Core] Webhook - Donation found: ' . print_r( $donation, true ) );
				// phpcs:enable
			}

			$donation->process_refund( $refund_amount, __( 'Donation refunded from the Square dashboard.', 'charitable' ) );

			return __( 'Donation Webhook: Refund processed', 'charitable' );
		}

		/**
		 * Process the subscription.created webhook.
		 *
		 * @since  1.8.7
		 *
		 * @param  object $event The Square event object.
		 * @return string Response message
		 */
		public function process_subscription_created( $event ) {
			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_subscription_created - starting' );
				error_log( print_r( $event, true ) );
				// phpcs:enable
			}

			$object       = $event['data']['object'];
			$subscription = $this->get_subscription_for_webhook_object( $object['subscription'] );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_subscription_created - subscription' );
				error_log( print_r( $subscription, true ) );
				error_log( 'process_subscription_created - object' );
				error_log( print_r( $object, true ) );
				// phpcs:enable
			}

			if ( empty( $subscription ) ) {
				return __( 'Subscription Webhook: Missing subscription', 'charitable' );
			}

			$square_status  = $this->get_subscription_status( $object['subscription']['status'] );
			$current_status = $subscription->get_status();

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_subscription_created - square_status' );
				error_log( print_r( $square_status, true ) );
				error_log( 'process_subscription_created - current_status' );
				error_log( print_r( $current_status, true ) );
				// phpcs:enable
			}

			if ( $square_status !== $current_status && 'charitable-completed' !== $current_status ) {
				$subscription->update_status( $square_status );
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_subscription_created - updated the status of the subscription to ' . $square_status );
					// phpcs:enable
				}
			}

			return __( 'Subscription Webhook: Recurring donation updated', 'charitable' );
		}

		/**
		 * Process the subscription.updated webhook.
		 *
		 * @since  1.8.7
		 *
		 * @param  object $event The Square event object.
		 * @return string Response message
		 */
		public function process_subscription_updated( $event ) {
			global $wpdb;

			if ( ! $this->is_recurring_installed() ) {
				return __( 'Subscription Webhook: Unable to process without Charitable Recurring extension.', 'charitable' );
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_subscription_updated - incoming event' );
				error_log( print_r( $event, true ) );
				// phpcs:enable
			}

			$object = $event['data']['object'];

			// Early return if subscription data is missing.
			if ( empty( $object['subscription'] ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_subscription_updated - Missing subscription data in webhook' );
					// phpcs:enable
				}
				return __( 'Subscription Webhook: Missing subscription data', 'charitable' );
			}

			$subscription = $this->get_subscription_for_webhook_object( $object['subscription'] );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_subscription_updated - subscription' );
				error_log( print_r( $subscription, true ) );
				// phpcs:enable
			}

			// Early return if no subscription found.
			if ( ! $subscription ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_subscription_updated - No subscription found for ID: ' . $object['subscription']['id'] );
					// phpcs:enable
				}
				return __( 'Subscription Webhook: No subscription found', 'charitable' );
			}

			$square_status   = $this->get_subscription_status( $object['subscription']['status'] );
			$current_status  = $subscription->get_status();
			$subscription_id = $subscription->get_donation_id();

			$cancel_confirmed = false;

			// The subscription could be ACTIVE but still "cancelled". Because the subscription is not immediately cancelled, we need to check the canceled_date.
			if ( isset( $object['subscription']['canceled_date'] ) && 'active' === strtolower( $object['subscription']['status'] ) ) {
				// Check and see if there is metadata for the subscription for a "canncelled to be confirmed".
				$cancelled_to_be_confirmed = get_post_meta( $subscription_id, '_square_cancelled_to_be_confirmed', true );
				if ( 'true' === strtolower( $cancelled_to_be_confirmed ) ) {
					$log = new Charitable_Square_Recurring_Donation_Log( $subscription );
					$log->log_custom_message( 'Subscription cancelled via Square via admin.' );
					$square_status    = 'charitable-cancelled';
					$cancel_confirmed = true;
				}
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_subscription_updated - square_status' );
				error_log( print_r( $square_status, true ) );
				error_log( 'process_subscription_updated - current_status' );
				error_log( print_r( $current_status, true ) );
				error_log( 'process_subscription_updated - subscription_id' );
				error_log( print_r( $subscription_id, true ) );
				// phpcs:enable
			}

			if ( $square_status !== $current_status ) {

				$subscription->update_status( $square_status );
				if ( 'charitable-cancelled' === $square_status && class_exists( 'Charitable_Square_Recurring_Donation_Log' ) && ! $cancel_confirmed ) {
					$log = new Charitable_Square_Recurring_Donation_Log( $subscription );
					$log->log_custom_message( 'Subscription cancelled via Square.' );
				}

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_subscription_updated - updated the status of the subscription to ' . $square_status );
					// phpcs:enable
				}

				if ( 'charitable-cancelled' === $square_status ) {

					// If the status is 'suspended', we need to fail the last pending donation.
					// Find the lastest child post of the parent post (which has an ID of $subscription_id).
					$sql         = $wpdb->prepare(
						"SELECT ID
						FROM $wpdb->posts
						WHERE post_parent = %s
						ORDER BY post_date DESC
						LIMIT 1",
						$subscription_id
					);
					$donation_id = $wpdb->get_col( $sql ); // phpcs:ignore

					if ( ! empty( $donation_id ) ) {

						if ( charitable_is_debug( 'square' ) ) {
							// phpcs:disable
							error_log( 'process_subscription_updated - sql was: ' . $sql );
							error_log( 'process_subscription_updated - donation_id' );
							error_log( print_r( $donation_id[0], true ) );
							// phpcs:enable
						}

						if ( ! empty( $donation_id[0] ) && is_numeric( $donation_id[0] ) && $donation_id[0] > 0 ) {
							$donation = charitable_get_donation( intval( $donation_id[0] ) );
							$donation->update_status( 'charitable-failed' );

							$log = new Charitable_Square_Donation_Log( $donation );
							$log->log_custom_message( 'Subscription cancelled. Failed the last pending donation.' );
						}
					}
				}
			}

			return __( 'Subscription Webhook: Recurring donation updated', 'charitable' );
		}

		/**
		 * Return a recurring donation object for a particular invoice, or false if
		 * none is found.
		 *
		 * @since  1.8.7
		 *
		 * @param  object $the_object The invoice or subscription object received from Square.
		 * @return Charitable_Recurring_Donation|false
		 */
		private function get_subscription_for_webhook_object( $the_object ) {
			$subscription_id = ! empty( $the_object['id'] ) ? $the_object['id'] : '';

			// This charitable_recurring_get_subscription_by_gateway_id() function exits in the Charitable Recurring extension.
			if ( function_exists( 'charitable_recurring_get_subscription_by_gateway_id' ) ) {
				return charitable_recurring_get_subscription_by_gateway_id( $subscription_id, 'square' );
			}

			return false;
		}

		/**
		 * Given a Square subscription status, return the corresponding Charitable status.
		 *
		 * @since  1.8.7
		 *
		 * @param  string $status Square subscription status.
		 * @return string
		 */
		public function get_subscription_status( $status ) {
			switch ( strtolower( $status ) ) {
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
				case 'deactivated':
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
			$failure_count  = (int) get_post_meta( $donation->ID, '_square_payment_intent_failure_count', true );
			$failure_count += 1; // phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found

			/* Update the failure count. */
			update_post_meta( $donation->ID, '_square_payment_intent_failure_count', $failure_count );

			/**
			 * Filter the threshold number of failures after which a payment intent
			 * should be cancelled.
			 *
			 * @since 1.4.9
			 *
			 * @param int $threshold The threshold number.
			 */
			$threshold = apply_filters( 'charitable_square_payment_failure_cancellation_threshold', 3 );

			/* The threshold has been reached, so cancel the payment intent. */
			if ( $threshold <= $failure_count ) {
				$intent = new Charitable_Square_Payment_Intent( $payment_intent );
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
		 * @since  1.8.7
		 *
		 * @return boolean
		 */
		private function is_recurring_installed() {
			return class_exists( 'Charitable_Recurring' );
		}

		/**
		 * For an IPN request, get the validated incoming event object.
		 *
		 * @since  1.8.7
		 * @since  1.4.0 Returns an array instead of an object.
		 * @since  1.8.7 Debug logging.
		 *
		 * @return false|array If valid, returns an object. Otherwise false.
		 */
		private static function get_validated_incoming_event() {
			$body = @file_get_contents( 'php://input' ); // phpcs:ignore

			// Debug logging.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Raw webhook body: ' . $body );
				error_log( 'Request method: ' . $_SERVER['REQUEST_METHOD'] );
				error_log( 'Content type: ' . ( isset( $_SERVER['CONTENT_TYPE'] ) ? $_SERVER['CONTENT_TYPE'] : 'not set' ) );
				// phpcs:enable
			}

			$event = json_decode( $body, true );

			// Debug logging for decoded event.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Decoded event: ' . print_r( $event, true ) );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					error_log( 'JSON decode error: ' . json_last_error_msg() );
				}
				// phpcs:enable
			}

			if ( ! is_array( $event ) || ! array_key_exists( 'merchant_id', $event ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Invalid event format or missing merchant_id' );
					// phpcs:enable
				}
				return false;
			}

			return $event;
		}

		/**
		 * Create instance of the class.
		 *
		 * @since 1.8.7
		 *
		 * @return Charitable_Square_Webhook_Processor
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self( true );
			}
			return self::$instance;
		}

		/**
		 * Get the donation amount including fees if fee relief is enabled.
		 *
		 * @since  1.8.8.4
		 *
		 * @param  Charitable_Donation $donation The donation object.
		 * @return float
		 */
		private function get_donation_amount_with_fees( $donation ) {
			// Check if fee relief is enabled and donor opted to cover fees
			if ( $donation->get( 'cover_fees' ) ) {
				// Use the total donation amount including fees
				return \Charitable_Currency::get_instance()->cast_to_decimal_format( $donation->get( 'total_donation_with_fees' ) );
			}

			// Return the standard donation amount
			return $donation->get_total_donation_amount( true );
		}
	}
endif;
