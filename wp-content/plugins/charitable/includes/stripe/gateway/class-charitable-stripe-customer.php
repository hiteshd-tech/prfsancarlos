<?php
/**
 * Create and retrieve Stripe Customers for campaigns.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Customer
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Customer' ) ) :

	/**
	 * Charitable_Stripe_Customer
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Customer {

		/** The key to use to store a customer ID. */
		const STRIPE_CUSTOMER_ID_KEY = 'stripe_customer_id';

		/** The key to use to store a customer ID. */
		const STRIPE_CUSTOMER_ID_KEY_TEST = 'stripe_customer_id_test';

		/**
		 * The customer id.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		private $customer_id;

		/**
		 * Customer object.
		 *
		 * @since 1.4.0
		 *
		 * @var   \Stripe\Customer
		 */
		private $customer;

		/**
		 * Gateway helper object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		private $gateway;

		/**
		 * Customer arguments to be updated.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $update_args = [];

		/**
		 * Last error message from a Stripe API call.
		 *
		 * @since 1.8.10
		 *
		 * @var   string
		 */
		private $last_error = '';

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param int|null $customer_id The customer id.
		 */
		public function __construct( $customer_id = null ) {
			$this->customer_id = $customer_id;
			$this->gateway     = new Charitable_Gateway_Stripe_AM();
		}

		/**
		 * Return the last error message from a Stripe API call.
		 *
		 * @since  1.8.9.7
		 *
		 * @return string
		 */
		public function get_last_error() {
			return $this->last_error;
		}

		/**
		 * Return a particular property of the Customer.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $property The property to get.
		 * @return mixed
		 */
		public function get( $property ) {
			$customer = $this->get_customer();

			return is_null( $customer ) ? null : $customer->$property;
		}

		/**
		 * Arguments to be updated.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $property The property to be updated.
		 * @param  mixed  $value    The value that the property should be set to.
		 * @return void
		 */
		public function set( $property, $value ) {
			$this->update_args[ $property ] = $value;
		}

		/**
		 * Get the Customer.
		 *
		 * @since  1.4.0
		 *
		 * @return \Stripe\Customer|null
		 */
		public function get_customer() {
			if ( ! isset( $this->customer ) ) {
				if ( is_null( $this->customer_id ) ) {
					return null;
				}

				$this->customer = wp_cache_get( $this->customer_id, 'stripe_customer' );

				if ( false === $this->customer ) {
					try {
						$this->gateway->setup_api();

						/* Retrieve the customer object from Stripe. */
						$this->customer = \Stripe\Customer::retrieve( $this->customer_id );

						if ( isset( $this->customer->deleted ) && $this->customer->deleted ) {
							$this->customer = null;
						}
					} catch ( \Stripe\Exception\ApiErrorException $e ) {
						if ( charitable_is_debug( 'stripe' ) ) {
							error_log( sprintf(
								'[Charitable Stripe] Customer retrieval error (ApiErrorException) for ID: %s. Error: %s (HTTP %s, code: %s)',
								$this->customer_id,
								$e->getMessage(),
								$e->getHttpStatus(),
								$e->getStripeCode()
							) );
						}
						$this->customer = null;
					} catch ( Stripe\Error\InvalidRequest $e ) {
						if ( charitable_is_debug( 'stripe' ) ) {
							error_log( sprintf(
								'[Charitable Stripe] Customer retrieval error (InvalidRequest) for ID: %s. Exception: %s (code: %s)',
								$this->customer_id,
								$e->getMessage(),
								$e->getCode()
							) );
						}
						$this->customer = null;
					} catch ( Exception $e ) {
						if ( charitable_is_debug( 'stripe' ) ) {
							error_log( sprintf(
								'[Charitable Stripe] Customer retrieval error (Exception) for ID: %s. Exception: %s (code: %s)',
								$this->customer_id,
								$e->getMessage(),
								$e->getCode()
							) );
						}
						$this->customer = null;
					}

					wp_cache_set( $this->customer_id, $this->customer, 'stripe_customer' );
				}
			}

			return $this->customer;
		}

		/**
		 * Update the customer.
		 *
		 * @since  1.4.0
		 *
		 * @return \Stripe\Customer|null Customer object. If error, returns null.
		 */
		public function update() {
			$customer_id = $this->get( 'id' );

			try {
				$this->gateway->setup_api();

				$this->customer = \Stripe\Customer::update( $customer_id, $this->update_args );

				wp_cache_set( $customer_id, $this->customer, 'stripe_customer' );
			} catch ( Exception $e ) {
				$this->customer = null;
			}

			return $this->customer;
		}

		/**
		 * Save a Stripe Customer id to a donor's meta.
		 *
		 * @since  1.4.0
		 *
		 * @param  int $user_id The user's ID.
		 * @return void
		 */
		public function save_customer_id( $user_id ) {
			$key = charitable_get_option( 'test_mode' ) ? self::STRIPE_CUSTOMER_ID_KEY_TEST : self::STRIPE_CUSTOMER_ID_KEY;

			update_user_meta( $user_id, $key, $this->get( 'id' ) );
		}

		/**
		 * Get a particular donor's Stripe Customer object if one exists.
		 *
		 * @since  1.4.0
		 *
		 * @param  Charitable_Donor $donor Donor object.
		 * @return Charitable_Stripe_Customer|null
		 */
		public static function init_with_donor( Charitable_Donor $donor ) {
			$user_id = $donor->get_user()->ID;

			if ( ! $user_id ) {
				return null;
			}

			$key         = charitable_get_option( 'test_mode' ) ? self::STRIPE_CUSTOMER_ID_KEY_TEST : self::STRIPE_CUSTOMER_ID_KEY;
			$customer_id = get_user_meta( $user_id, $key, true );

			if ( ! $customer_id ) {
				return null;
			}

			return new Charitable_Stripe_Customer( $customer_id );
		}

		/**
		 * Create a Stripe Customer object for a donor.
		 *
		 * @since  1.4.0
		 *
		 * @param  Charitable_Donor $donor          Donor object.
		 * @return Charitable_Stripe_Customer|null
		 */
		public static function create_for_donor( Charitable_Donor $donor ) {
			$user_id = $donor->get_user()->ID;

			$args = [
				'name'        => $donor->get_name(),
				'description' => $donor->get_name(),
				'email'       => $donor->get_email(),
				'metadata'    => [
					'donor_id' => $donor->donor_id,
					'user_id'  => $user_id,
				],
			];

			$address = array_filter(
				[
					'line1'       => $donor->get_donor_meta( 'address' ),
					'line2'       => $donor->get_donor_meta( 'address_2' ),
					'city'        => $donor->get_donor_meta( 'city' ),
					'country'     => $donor->get_donor_meta( 'country' ),
					'postal_code' => $donor->get_donor_meta( 'postcode' ),
					'state'       => $donor->get_donor_meta( 'state' ),
				]
			);

			if ( array_key_exists( 'line1', $address ) ) {
				$args['address'] = $address;
			}

			$phone = $donor->get_donor_meta( 'phone' );

			if ( ! empty( $phone ) ) {
				$args['phone'] = $phone;
			}

			/**
			 * Filter the Stripe customer arguments.
			 *
			 * @since 1.2.2
			 * @since 1.4.0 The Donation Processor object is no longer passed.
			 *
			 * @param array                         $args      The customer arguments.
			 * @param Charitable_Donor              $donor     The Donor object.
			 * @param Charitable_Donation_Processor $processor The Donation Procesor helper.
			 */
			$args = apply_filters( 'charitable_stripe_customer_args', $args, $donor );

			try {
				$gateway  = new Charitable_Gateway_Stripe_AM();
				$gateway->setup_api();

				$stripe_customer = \Stripe\Customer::create( $args );

				wp_cache_set( $stripe_customer->id, $stripe_customer, 'stripe_customer' );

			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );

				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf(
						'[Charitable Stripe] create_for_donor FAILED for donor email: %s, user ID: %s. Stripe error: %s (code: %s)',
						$donor->get_email(),
						$user_id ? $user_id : '0 (guest)',
						$message,
						$e->getCode()
					) );
				}

				charitable_get_notices()->add_error( $message );

				return null;
			}

			$customer = new Charitable_Stripe_Customer( $stripe_customer->id );

			if ( $user_id ) {
				$customer->save_customer_id( $user_id );
			}

			if ( charitable_is_debug( 'stripe' ) ) {
				error_log( sprintf( '[Charitable Stripe] Created new customer: %s for donor email: %s', $stripe_customer->id, $donor->get_email() ) );
			}

			return $customer;
		}

		/**
		 * Add payment method to the customer.
		 *
		 * @since  1.4.0
		 *
		 * @param  string  $payment_method_id The payment method id.
		 * @param  boolean $set_as_default    Whether to make this the customer's default payment method.
		 * @return \Stripe\PaymentMethod|null
		 */
		public function add_payment_method( $payment_method_id, $set_as_default = true ) {
			try {
				$payment_method = \Stripe\PaymentMethod::retrieve( $payment_method_id );

				/* The payment method is not attached to the customer yet, so do that now. */
				if ( $this->get( 'id' ) != $payment_method->customer ) {
					$payment_method = $payment_method->attach(
						[
							'customer' => $this->get( 'id' ),
						]
					);
				}

				if ( $set_as_default ) {
					$invoice_settings = $this->get( 'invoice_settings' );

					$this->set(
						'invoice_settings',
						[
							'default_payment_method' => $payment_method_id,
							'custom_fields'          => $invoice_settings->custom_fields,
							'footer'                 => $invoice_settings->footer,
						]
					);

					$this->update();
				}

				return $payment_method;

			} catch ( \Stripe\Exception\ApiErrorException $e ) {
				$error        = $e->getError();
				$message      = $error->message;
				$decline_code = isset( $error->decline_code ) ? $error->decline_code : '';
				$error_code   = isset( $error->code ) ? $error->code : '';

				// Build a detailed error for the donation log.
				$detail_parts = array( $message );
				if ( $error_code ) {
					$detail_parts[] = sprintf( 'Code: %s', $error_code );
				}
				if ( $decline_code ) {
					$detail_parts[] = sprintf( 'Decline code: %s', $decline_code );
				}
				$detail_parts[] = sprintf( 'Payment method: %s', $payment_method_id );
				$detail_parts[] = 'Note: Card declines occur during verification when attaching the payment method to the customer. This happens before any charge is created, so the attempt will not appear in the Stripe Dashboard Payments section (check Developers > Logs in Stripe instead).';

				$this->last_error = implode( ' | ', $detail_parts );

				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf(
						'[Charitable Stripe] add_payment_method FAILED (ApiErrorException). Payment method: %s, Customer: %s. Error: %s, Code: %s, Decline code: %s',
						$payment_method_id,
						$this->get( 'id' ),
						$message,
						$error_code,
						$decline_code
					) );
				}

				charitable_get_notices()->add_error( $message );

				return null;
			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );

				$this->last_error = $message;

				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf(
						'[Charitable Stripe] add_payment_method FAILED (Exception). Payment method: %s, Customer: %s. Error: %s',
						$payment_method_id,
						$this->get( 'id' ),
						$message
					) );
				}

				charitable_get_notices()->add_error( $message );

				return null;
			}
		}
	}

endif;
