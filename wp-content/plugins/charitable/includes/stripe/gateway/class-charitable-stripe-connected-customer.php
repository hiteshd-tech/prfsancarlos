<?php
/**
 * Create and retrieve Stripe Customers for campaigns with connected accounts.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Connected_Customer
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

if ( ! class_exists( 'Charitable_Stripe_Connected_Customer' ) ) :

	/**
	 * Charitable_Stripe_Connected_Customer
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Connected_Customer {

		/** The key to use to store a customer ID. */
		const STRIPE_CONNECT_CUSTOMER_ID_KEY = 'stripe_connect_customer_id';

		/** The key to use to store a customer ID. */
		const STRIPE_CONNECT_CUSTOMER_ID_KEY_TEST = 'stripe_connect_customer_id_test';

		/**
		 * Platform customer object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Stripe_Customer
		 */
		private $platform_customer;

		/**
		 * Connected account customer object.
		 *
		 * @since 1.4.0
		 *
		 * @var   \Stripe\Customer
		 */
		private $connected_customer;

		/**
		 * Options.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $options;

		/**
		 * Gateway helper object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		private $gateway;

		/**
		 * Donor object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Donor
		 */
		private $donor;

		/**
		 * Payment method.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		private $payment_method;

		/**
		 * Customer arguments to be updated.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $update_args = [];

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param Charitable_Stripe_Customer $platform_customer The platform customer object.
		 * @param array                      $options           Mixed set of options to pass to API request.
		 * @param Charitable_Donor           $donor             The donor object.
		 * @param string|null                $payment_method    Optional payment method to attach to donor.
		 */
		public function __construct( Charitable_Stripe_Customer $platform_customer, $options, Charitable_Donor $donor, $payment_method = null ) {
			$this->platform_customer = $platform_customer;
			$this->options           = $options;
			$this->donor             = $donor;
			$this->gateway           = new Charitable_Gateway_Stripe_AM();
			$this->payment_method    = $payment_method;
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
			$customer = $this->get_connected_customer();

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

				$this->connected_customer = \Stripe\Customer::update( $customer_id, $this->update_args, $this->options );

				wp_cache_set( $customer_id, $this->connected_customer, 'stripe_customer' );
			} catch ( Exception $e ) {
				$this->connected_customer = null;
			}

			return $this->connected_customer;
		}

		/**
		 * Return the connected account.
		 *
		 * @since  1.4.0
		 *
		 * @return string|false
		 */
		public function get_connected_account() {
			return ! is_array( $this->options ) || ! array_key_exists( 'stripe_account', $this->options ) ? false : $this->options['stripe_account'];
		}

		/**
		 * Return the connected customer object.
		 *
		 * @since  1.4.0
		 *
		 * @return \Stripe\Customer|null
		 */
		public function get_connected_customer() {
			if ( isset( $this->connected_customer ) ) {
				return $this->connected_customer;
			}

			$this->connected_customer = null;
			$connected_customer_id    = $this->get_saved_connected_customer();

			if ( ! is_null( $connected_customer_id ) ) {
				try {
					$this->gateway->setup_api();

					/* Retrieve the customer object from Stripe. */
					$this->connected_customer = \Stripe\Customer::retrieve( $connected_customer_id, $this->options );

					if ( isset( $this->connected_customer->deleted ) && $this->connected_customer->deleted ) {
						$this->connected_customer = null;
					}
				} catch ( \Stripe\Exception\ApiErrorException $e ) {
					if ( charitable_is_debug( 'stripe' ) ) {
						error_log( sprintf(
							'[Charitable Stripe] Connected customer retrieval failed (ApiErrorException) for ID: %s. Error: %s (HTTP %s)',
							$connected_customer_id,
							$e->getMessage(),
							$e->getHttpStatus()
						) );
					}
					$this->connected_customer = null;
				} catch ( Stripe\Error\InvalidRequest $e ) {
					if ( charitable_is_debug( 'stripe' ) ) {
						error_log( sprintf(
							'[Charitable Stripe] Connected customer retrieval failed (InvalidRequest) for ID: %s. Error: %s',
							$connected_customer_id,
							$e->getMessage()
						) );
					}
					$this->connected_customer = null;
				} catch ( Exception $e ) {
					if ( charitable_is_debug( 'stripe' ) ) {
						error_log( sprintf(
							'[Charitable Stripe] Connected customer retrieval failed (Exception) for ID: %s. Error: %s',
							$connected_customer_id,
							$e->getMessage()
						) );
					}
					$this->connected_customer = null;
				}
			}

			/* We don't have a connected customer, so create one now. */
			if ( is_null( $this->connected_customer ) ) {
				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf( '[Charitable Stripe] No connected customer found (saved ID: %s). Creating new one.', $connected_customer_id ? $connected_customer_id : 'none' ) );
				}
				$this->connected_customer = $this->create_connected_customer();
			}

			return $this->connected_customer;
		}

		/**
		 * For a customer id on the platform account, check if we have already
		 * added it to the connected account.
		 *
		 * @since  1.4.0
		 *
		 * @return string|null
		 */
		public function get_saved_connected_customer() {
			$key     = charitable_get_option( 'test_mode' ) ? self::STRIPE_CONNECT_CUSTOMER_ID_KEY_TEST : self::STRIPE_CONNECT_CUSTOMER_ID_KEY;
			$meta    = $this->donor->__get( $key );
			$account = $this->get_connected_account();

			if ( ! is_array( $meta ) || ! array_key_exists( $account, $meta ) ) {
				return null;
			}

			return $meta[ $account ];
		}

		/**
		 * Save the connected account id of a customer for a given customer
		 * that already exists on the platform account.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $connected_id The customer id on the connected account.
		 * @return void
		 */
		public function save_connected_customer( $connected_id ) {
			$key     = charitable_get_option( 'test_mode' ) ? self::STRIPE_CONNECT_CUSTOMER_ID_KEY_TEST : self::STRIPE_CONNECT_CUSTOMER_ID_KEY;
			$meta    = $this->donor->__get( $key );
			$account = $this->get_connected_account();

			if ( ! is_array( $meta ) ) {
				$meta = [];
			}

			$meta[ $account ] = $connected_id;

			update_user_meta( $this->donor->ID, $key, $meta );
		}

		/**
		 * Return a payment method for a shared customer.
		 *
		 * @since  1.4.0
		 *
		 * @return string|false
		 */
		public function get_payment_method() {
			if ( is_null( $this->payment_method ) ) {
				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( '[Charitable Stripe] Connected customer get_payment_method: no payment method provided (null). Skipping.' );
				}
				return false;
			}

			try {
				$payment_method = \Stripe\PaymentMethod::create(
					[
						'customer'       => $this->platform_customer->get( 'id' ),
						'payment_method' => $this->payment_method,
					],
					$this->options
				);

			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );

				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf(
						'[Charitable Stripe] Connected customer get_payment_method FAILED. Platform customer: %s, Payment method: %s. Error: %s',
						$this->platform_customer->get( 'id' ),
						$this->payment_method,
						$message
					) );
				}

				charitable_get_notices()->add_error( $message );

				return false;
			}//end try

			return $payment_method->id;
		}

		/**
		 * Create the customer on the connected account.
		 *
		 * @since  1.4.0
		 *
		 * @return \Stripe\Customer|null
		 */
		public function create_connected_customer() {
			$args = [
				'email'            => $this->platform_customer->get( 'email' ),
				'description'      => $this->platform_customer->get( 'description' ),
			];

			$payment_method = $this->get_payment_method();

			if ( $payment_method ) {
				$args['payment_method']   = $payment_method;
				$args['invoice_settings'] = [
					'default_payment_method' => $payment_method,
				];
			}

			try {
				/* Add the shared customer to the connected account, using the payment method above. */
				$customer = \Stripe\Customer::create( $args, $this->options );

				$this->save_connected_customer( $customer->id );

				return $customer;

			} catch ( Exception $e ) {
				if ( charitable_is_debug( 'stripe' ) ) {
					error_log( sprintf(
						'[Charitable Stripe] create_connected_customer FAILED. Error code: %s, Message: %s',
						$e->getCode(),
						$e->getMessage()
					) );
				}
				return null;
			}
		}
	}

endif;
