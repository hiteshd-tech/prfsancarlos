<?php
/**
 * Base Charitable_Stripe_Gateway_Processor_Interface instance.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Gateway_Processor
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.3.0
 * @version   1.4.13
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Gateway_Processor' ) ) :

	/**
	 * Charitable_Stripe_Gateway_Processor
	 *
	 * @since 1.3.0
	 */
	abstract class Charitable_Stripe_Gateway_Processor implements Charitable_Stripe_Gateway_Processor_Interface {

		/** The key to use to store a customer ID. */
		const STRIPE_CUSTOMER_ID_KEY = 'stripe_customer_id';

		/** The key to use to store a customer ID. */
		const STRIPE_CUSTOMER_ID_KEY_TEST = 'stripe_customer_id_test';

		/** The key to use to store a customer ID. */
		const STRIPE_CONNECT_CUSTOMER_ID_KEY = 'stripe_connect_customer_id';

		/** The key to use to store a customer ID. */
		const STRIPE_CONNECT_CUSTOMER_ID_KEY_TEST = 'stripe_connect_customer_id_test';

		/**
		 * The donation object.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Donation
		 */
		protected $donation;

		/**
		 * Donation log instance for this donation.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Donation_Log
		 */
		protected $donation_log;

		/**
		 * The donor object.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Donor
		 */
		protected $donor;

		/**
		 * The donation processor object.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Donation_Processor
		 */
		protected $processor;

		/**
		 * The Stripe gateway model.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		protected $gateway;

		/**
		 * Submitted donation values.
		 *
		 * @since 1.3.0
		 *
		 * @var   array
		 */
		protected $donation_data;

		/**
		 * Options passed to Stripe with certain API requests.
		 *
		 * @since 1.3.0
		 *
		 * @var   array
		 */
		protected $options = [];

		/**
		 * Connect mode.
		 *
		 * This will remain unset if Stripe Connect is not active. If
		 * it is active, this will either be `direct` or `charge_owner`.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $connect_mode;

		/**
		 * Application fee.
		 *
		 * This will remain unset if Stripe Connect is not active.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $application_fee;

		/**
		 * Destination account.
		 *
		 * This will remain unset if Stripe Connect is not active or
		 * if Connect mode is set to `direct`.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		protected $destination;

		/**
		 * Customer object, always on the platform.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Stripe_Customer
		 */
		protected $customer;

		/**
		 * Last error encountered during customer retrieval/creation.
		 *
		 * @since 1.8.10
		 *
		 * @var   string
		 */
		protected $customer_error = '';

		/**
		 * Connected account customer object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Stripe_Connected_Customer
		 */
		protected $connected_customer;

		/**
		 * Set up class instance.
		 *
		 * @since 1.3.0
		 *
		 * @param int                           $donation_id The donation ID.
		 * @param Charitable_Donation_Processor $processor   The donation processor object.
		 */
		public function __construct( $donation_id, Charitable_Donation_Processor $processor ) {
			$this->donation      = new Charitable_Donation( $donation_id );
			$this->donation_log  = $this->donation->log();
			$this->donor         = $this->donation->get_donor();
			$this->gateway       = new Charitable_Gateway_Stripe_AM();
			$this->processor     = $processor;
			$this->donation_data = $this->processor->get_donation_data();

			$this->setup_stripe_connect();
		}

		/**
		 * Set Stripe API key.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean True if the API key is set. False otherwise.
		 */
		public function set_stripe_api_key() {
			return $this->gateway->setup_api();
		}

		/**
		 * Return the submitted value for a gateway field.
		 *
		 * @since  1.3.0
		 *
		 * @param  string  $key    The key of the value we want to get.
		 * @param  mixed[] $values An values in which to search.
		 * @return string|false
		 */
		public static function get_gateway_value( $key, $values ) {
			if ( isset( $values['gateways']['stripe'][ $key ] ) ) {
				return $values['gateways']['stripe'][ $key ];
			}

			return false;
		}

		/**
		 * Return the submitted value for a gateway field.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $key The key of the value we want to get.
		 * @return string|false
		 */
		public function get_gateway_value_from_processor( $key ) {
			return self::get_gateway_value( $key, $this->donation_data );
		}

		/**
		 * Return the statement_descriptor value.
		 *
		 * @since  1.3.0
		 *
		 * @param  string|null $campaign Optional Campaign object. If not passed, will get the
		 *                               list of campaigns donated to from the Donation object.
		 * @return string
		 * @version 1.8.9.1
		 */
		public function get_statement_descriptor( $campaign = null ) {
			$format     = charitable_get_option( [ 'gateways_stripe', 'statement_descriptor' ], 'auto' );
			$descriptor = substr( charitable_get_option( [ 'gateways_stripe', 'statement_descriptor_custom' ], '' ), 0, 22 );

			if ( 'auto' == $format || empty( $descriptor ) ) {
				if ( is_null( $campaign ) ) {
					$campaign = $this->donation->get_campaigns_donated_to();
				}

				/**
				 * Filter the automatically formatted statement_descriptor.
				 *
				 * @since 1.3.0
				 *
				 * @param string                        $descriptor The default descriptor.
				 * @param Charitable_Donation           $donation   The donation object.
				 * @param Charitable_Donation_Processor $processor  The processor object.
				 */
				$descriptor = apply_filters( 'charitable_stripe_statement_descriptor', $campaign, $this->donation, $this->processor );
			}

			/* Strip invalid characters. */
			$descriptor = $this->strip_invalid_characters( $descriptor );

			/* The descriptor must be at least 5 characters long. */
			if ( strlen( $descriptor ) < 5 ) {
				$url        = wp_parse_url( home_url() );
				$descriptor = $descriptor . ' ' . $url['host'];
			}

			/* Finally, ensure that the descriptor is no more than 22 characters long. */
			return substr( $descriptor, 0, 22 );
		}

		/**
		 * Strip invalid characters from the descriptor.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $descriptor The descriptor.
		 * @return string
		 */
		public function strip_invalid_characters( $descriptor ) {
			return str_replace(
				[
					'<',
					'>',
					'\\',
					"'",
					'"',
					'*',
				],
				'',
				$descriptor
			);
		}

		/**
		 * Return the description value of the charge.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		public function get_charge_description() {
			return html_entity_decode( $this->donation->get_campaigns_donated_to(), ENT_COMPAT, 'UTF-8' );
		}

		/**
		 * Return the charge metadata.
		 *
		 * @since  1.3.0
		 *
		 * @return array
		 */
		public function get_charge_metadata() {
			/**
			 * Filter the charge metadata.
			 *
			 * @since 1.3.0
			 *
			 * @param array                         $metadata  The set of metadata.
			 * @param Charitable_Donation           $donation  The donation object.
			 * @param Charitable_Donation_Processor $processor The processor object.
			 */
			return apply_filters(
				'charitable_stripe_charge_metadata',
				charitable_stripe_get_donation_metadata( $this->donation ),
				$this->donation,
				$this->processor
			);
		}

		/**
		 * Get the donation amount in the smallest common currency unit.
		 *
		 * @since  1.3.0
		 *
		 * @param  float       $amount   The donation amount in dollars.
		 * @param  string|null $currency The currency of the donation. If null, the site currency will be used.
		 * @return int|false Returns integer if valid amount was passed, otherwise false.
		 */
		public static function get_sanitized_donation_amount( $amount, $currency = null ) {
			if ( is_wp_error( $amount ) ) {
				return false;
			}

			/* Unless it's a zero decimal currency, multiply the currency x 100 to get the amount in cents. */
			if ( self::is_zero_decimal_currency( $currency ) ) {
				$amount = (float) $amount * 1;
			} else {
				$amount = (float) $amount * 100;
			}

			return absint( round( $amount ) );
		}

		/**
		 * Returns whether the currency is a zero decimal currency.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $currency The currency for the charge. If left blank, will check for the site currency.
		 * @return boolean
		 */
		public static function is_zero_decimal_currency( $currency = null ) {
			if ( is_null( $currency ) ) {
				$currency = charitable_get_currency();
			}

			return in_array( strtoupper( $currency ), self::get_zero_decimal_currencies() );
		}

		/**
		 * Return all zero-decimal currencies supported by Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @return array
		 */
		public static function get_zero_decimal_currencies() {
			return [
				'BIF',
				'CLP',
				'DJF',
				'GNF',
				'JPY',
				'KMF',
				'KRW',
				'MGA',
				'PYG',
				'RWF',
				'VND',
				'VUV',
				'XAF',
				'XOF',
				'XPF',
			];
		}

		/**
		 * Returns the payment source.
		 *
		 * This may return a string, identifying the ID of a payment source such as
		 * a credit card. It may also be an associative array containing the user's
		 * credit card details.
		 *
		 * @see    https://stripe.com/docs/api#create_charge
		 *
		 * @since  1.3.0
		 *
		 * @param  string $customer_id Stripe customer id.
		 * @return false|string|array False if we don't have the data we need, a string if a source or token was
		 *                            available in the request, or an array if card data was passed in the request.
		 */
		public function get_payment_source( $customer_id ) {
			$source = $this->get_gateway_value_from_processor( 'source' );

			if ( $source ) {
				return $source;
			}

			$source = $this->get_gateway_value_from_processor( 'token' );

			if ( ! $source ) {
				return false;
			}

			/* Store the payment source for the Customer, and obtain a Card object from Stripe */
			$customer        = new Charitable_Stripe_Customer( $customer_id );
			$stripe_customer = $customer->get_customer();

			$card = $stripe_customer->sources->create( [ 'source' => $source ] );

			return $card->id;
		}

		/**
		 * Return the Stripe Customer ID for the current customer.
		 *
		 * If the donor has donated previously through Stripe, this will return
		 * their ID from the database. If not, this will first set them up as a
		 * customer in Stripe, store their customer ID and then return it.
		 *
		 * @see    https://stripe.com/docs/api#create_customer
		 *
		 * @since  1.3.0
		 *
		 * @param  string|null $payment_method Optional payment method to attach to donor.
		 * @return string|false Customer id if it exists, or false otherwise.
		 */
		public function get_stripe_customer( $payment_method = null ) {
			$debug = charitable_is_debug( 'stripe' );

			$this->customer = Charitable_Stripe_Customer::init_with_donor( $this->donor );

			if ( $debug ) {
				if ( is_null( $this->customer ) ) {
					$user_id  = $this->donor->get_user()->ID;
					$donor_id = $this->donor->donor_id;
					error_log( sprintf( '[Charitable Stripe] init_with_donor returned null. Donor ID: %s, User ID: %s. Will attempt to create new customer.', $donor_id, $user_id ? $user_id : '0 (guest)' ) );
				} else {
					error_log( sprintf( '[Charitable Stripe] init_with_donor found existing customer: %s', $this->customer->get( 'id' ) ) );
				}
			}

			/* Check if customer object exists or has been deleted in Stripe. If needed, create one. */
			if ( is_null( $this->customer ) || is_null( $this->customer->get( 'id' ) ) ) {
				$this->customer = Charitable_Stripe_Customer::create_for_donor( $this->donor );
			}

			if ( is_null( $this->customer ) ) {
				$this->customer_error = __( 'Unable to create Stripe customer. The Stripe API may be unreachable or returned an error.', 'charitable' );
				if ( $debug ) {
					error_log( '[Charitable Stripe] get_stripe_customer FAILED: create_for_donor returned null. Customer could not be created.' );
				}
				return false;
			}

			$customer_id = $this->customer->get( 'id' );

			if ( $debug ) {
				error_log( sprintf( '[Charitable Stripe] Customer ID resolved: %s', $customer_id ) );
			}

			if ( ! is_null( $payment_method ) ) {
				if ( $debug ) {
					error_log( sprintf( '[Charitable Stripe] Attaching payment method: %s to customer: %s', $payment_method, $customer_id ) );
				}

				$payment_method = $this->customer->add_payment_method( $payment_method, true );

				if ( is_null( $payment_method ) ) {
					$this->customer_error = $this->customer->get_last_error();
					if ( $debug ) {
						error_log( '[Charitable Stripe] get_stripe_customer FAILED: add_payment_method returned null.' );
					}
					return false;
				}
			}

			/**
			 * When charges are made directly against different Stripe accounts, the
			 * customer needs to be added to the connected Stripe account.
			 *
			 * @see https://stripe.com/docs/connect/shared-customers
			 */
			if ( ! empty( $this->options ) && ( 'direct' === $this->connect_mode || $this->is_recurring_donation() ) ) {
				if ( $debug ) {
					$account = isset( $this->options['stripe_account'] ) ? $this->options['stripe_account'] : 'unknown';
					error_log( sprintf( '[Charitable Stripe] Creating connected customer on account: %s (connect_mode: %s)', $account, $this->connect_mode ) );
				}

				$this->connected_customer = new Charitable_Stripe_Connected_Customer( $this->customer, $this->options, $this->donor, $payment_method );
				$customer_id              = $this->connected_customer->get( 'id' );

				if ( is_null( $customer_id ) ) {
					$this->customer_error = __( 'Unable to create customer on connected Stripe account.', 'charitable' );
					if ( $debug ) {
						error_log( '[Charitable Stripe] get_stripe_customer FAILED: connected customer ID is null.' );
					}
				}
			}

			return is_null( $customer_id ) ? false : $customer_id;
		}

		/**
		 * Return a token for a shared customer.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $customer Stripe customer id.
		 * @return string|false
		 */
		public function get_stripe_shared_customer_token( $customer ) {
			try {
				$token = \Stripe\Token::create( [ 'customer' => $customer ], $this->options );
			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );

				charitable_get_notices()->add_error( $message );

				return false;
			}//end try

			return $token->id;
		}

		/**
		 * Return a Stripe customer id for a customer already existing
		 * on the platform account.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $customer_id The customer id on the platform account.
		 * @return string|false
		 */
		public function get_connected_stripe_customer( $customer_id ) {
			/* First, check if we already have a customer id for the customer on this account. */
			$connected_id = $this->get_saved_connected_stripe_customer();

			if ( $connected_id ) {
				return $connected_id;
			}

			/* Get a token for the customer. */
			$token = $this->get_stripe_shared_customer_token( $customer_id );

			if ( ! $token ) {
				return false;
			}

			try {
				/* Retrieve the Customer object from the platform */
				$original = new Charitable_Stripe_Customer( $customer_id );

				/* Add the shared customer to the connected account, using the token above. */
				$customer = \Stripe\Customer::create(
					[
						'email'       => $original->get( 'email' ),
						'description' => $original->get( 'description' ),
						'source'      => $token,
					],
					$this->options
				);

				$this->save_connected_stripe_customer( $customer->id );

				return $customer->id;

			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * For a customer id on the platform account, check if we have already
		 * added it to the connected account.
		 *
		 * @since  1.3.0
		 *
		 * @return string|false
		 */
		public function get_saved_connected_stripe_customer() {
			$key     = charitable_get_option( 'test_mode' ) ? self::STRIPE_CONNECT_CUSTOMER_ID_KEY_TEST : self::STRIPE_CONNECT_CUSTOMER_ID_KEY;
			$meta    = $this->donor->__get( $key );
			$account = $this->options['stripe_account'];

			if ( ! is_array( $meta ) || ! array_key_exists( $account, $meta ) ) {
				return false;
			}

			return $meta[ $account ];
		}

		/**
		 * Save the connected account id of a customer for a given customer
		 * that already exists on the platform account.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $connected_id The customer id on the connected account.
		 * @return string|false
		 */
		public function save_connected_stripe_customer( $connected_id ) {
			$key     = charitable_get_option( 'test_mode' ) ? self::STRIPE_CONNECT_CUSTOMER_ID_KEY_TEST : self::STRIPE_CONNECT_CUSTOMER_ID_KEY;
			$meta    = $this->donor->__get( $key );
			$account = $this->options['stripe_account'];

			if ( ! is_array( $meta ) ) {
				$meta = [];
			}

			$meta[ $account ] = $connected_id;

			update_user_meta( $this->donor->ID, $key, $meta );
		}

		/**
		 * Return payment intent data.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_payment_intent_data() {

			$data = [
				'description'          => $this->get_charge_description(),
				'statement_descriptor' => $this->get_statement_descriptor(),
				'metadata'             => $this->get_charge_metadata(),
				'setup_future_usage'   => $this->is_recurring_donation() ? 'off_session' : 'on_session',
			];

			if ( isset( $this->application_fee ) && $this->application_fee > 0 ) {
				$data['application_fee_amount'] = $this->application_fee;
			}

			if ( ! is_null( $this->destination ) && is_a( $this, 'Charitable_Stripe_Gateway_Processor_Checkout' ) ) {
				$data['transfer_data'] = [ 'destination' => $this->destination ];

				/**
				 * If desired, return true to make the connected account the settlement merchant.
				 *
				 * @since 1.4.0
				 *
				 * @param boolean $enabled Whether to make the connected account the settlement merchant.
				 */
				if ( apply_filters( 'charitable_stripe_connect_enable_on_behalf_of', false ) ) {
					$data['on_behalf_of'] = $this->destination;
				}
			}

			// Filter added in 1.8.1.8.
			$data = apply_filters( 'charitable_stripe_payment_intent_data', $data, $this );

			return $data;
		}

		/**
		 * Create a recurring donation plan in Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @param  mixed $return      The default return value.
		 * @param  int   $campaign_id The campaign ID.
		 * @param  array $plan_args   The plan parameters/arguments.
		 * @param  array $args        Other arguments related to the donation.
		 * @return string|false
		 */
		public static function create_plan( $return, $campaign_id, $plan_args, $args ) {
			$options               = array_key_exists( 'options', $args ) ? $args['options'] : null;
			$currency              = charitable_get_currency();
			$zero_decimal_currency = self::is_zero_decimal_currency( $currency );
			$period                = self::get_plan_period( $plan_args );
			$interval              = self::get_plan_interval( $plan_args );
			$amount                = self::sanitize_plan_amount( $plan_args['amount'], $currency, $zero_decimal_currency );
			$amount_description    = strval( $zero_decimal_currency ? $amount : $amount / 100 );
			$plan_id               = $period . '-' . $interval . '-' . $amount . $currency . '-' . $campaign_id;
			$plan_name             = sprintf(
				/* translators: %1$s: campaign title; %2$s: amount; %3$s: currency; %4$s: period */
				_x( '%1$s - %2$s %3$s every %4$s', 'campaign title — amount every period', 'charitable' ),
				get_the_title( $campaign_id ),
				charitable_sanitize_amount( $amount_description ),
				$currency,
				charitable_recurring_get_donation_periods_i18n( $interval, $period )
			);

			$product_id = self::get_stripe_campaign_product_id( $campaign_id, $options );

			if ( ! $product_id ) {
				$product_id = self::create_stripe_campaign_product( $campaign_id, $options );

				if ( ! $product_id ) {
					return false;
				}
			}

			try {
				$plan = \Stripe\Plan::create(
					[
						'id'             => $plan_id,
						'interval'       => $period,
						'interval_count' => $interval,
						'currency'       => $currency,
						'amount'         => $amount,
						'product'        => $product_id,
					],
					$options
				);

				return $plan_id;

			} catch ( Exception $e ) {

				$response = json_decode( $e->getHttpBody() );

				/* The plan already exists. */
				if ( 'resource_already_exists' == $response->error->code ) {
					return $plan_id;
				}

				/* Log the error message and return false. */
				charitable_get_notices()->add_error( 'STRIPE - Error creating plan: ' . $e->getMessage() );

				return false;

			}//end try
		}

		/**
		 * Get a Stripe plan.
		 *
		 * If the plan already exists in the database, this will ensure
		 * that it also exists within Stripe. If it doesn't, it will create
		 * it.
		 *
		 * If the plan doesn't exist in the database, this will add it
		 * and also create it in Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @param  int   $campaign_id The campaign ID.
		 * @param  array $args        Additional arguments used to define the plan.
		 *
		 * @return string The plan ID.
		 */
		public function get_stripe_plan( $campaign_id, $args ) {
			$plans     = get_post_meta( $campaign_id, 'stripe_donation_plans', true );
			$plan_args = charitable_recurring_get_plan_args( $args );
			$plan_key  = charitable_recurring_get_plan_key( $plan_args );
			$mode      = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$plan_id   = false;

			if ( ! is_array( $plans ) ) {
				$plans = [];
			}

			/**
			 * Check whether the plan has been created before, and if it has,
			 * make sure it still exists in Stripe.
			 */
			if ( isset( $plans[ $mode ][ $plan_key ] ) ) {
				$options = array_key_exists( 'options', $args ) ? $args['options'] : null;
				$plan_id = $this->stripe_plan_exists( $plans[ $mode ][ $plan_key ], $options );
			}

			if ( ! $plan_id ) {
				/**
				 * Create the Stripe plan.
				 *
				 * This filter replicates the filter defined in `charitable_recurring_campaign_create_gateway_plan_id`.
				 * As of Charitable Stripe 1.3, that function is not utilized by the Stripe extension since its approach
				 * is not quite ideal. Hence, we apply the filter here.
				 *
				 * @since 1.3.0
				 *
				 * @param boolean|string $return      The plan ID. Set to false by default.
				 * @param int            $campaign_id The campaign ID.
				 * @param array          $plan_args   The plan arguments.
				 * @param array          $args        Additional arguments.
				 */
				$plan_id = apply_filters( 'charitable_recurring_create_gateway_plan_' . Charitable_Gateway_Stripe_AM::get_gateway_id(), false, $campaign_id, $plan_args, $args );

				if ( $plan_id ) {
					$plans[ $mode ][ $plan_key ] = $plan_id;

					update_post_meta( $campaign_id, 'stripe_donation_plans', $plans );
				}
			}

			return $plan_id;
		}

		/**
		 * Verify that the plan exists.
		 *
		 * @since  1.3.0
		 *
		 * @param  string     $plan_id The plan ID.
		 * @param  array|null $options Options to pass to Stripe.
		 * @return false|string The plan ID if it exists. False otherwise.
		 */
		public function stripe_plan_exists( $plan_id, $options = null ) {
			try {
				$plan = \Stripe\Plan::retrieve( $plan_id, $options );

				return $plan_id;

			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Get the Stripe product ID for a campaign, creating the product if necessary.
		 *
		 * @since  1.3.0
		 *
		 * @param  int        $campaign_id The campaign ID.
		 * @param  array|null $options     Options to pass to Stripe.
		 * @return string|false
		 */
		public static function get_stripe_campaign_product_id( $campaign_id, $options = null ) {
			$product_id = get_post_meta( $campaign_id, 'stripe_product_id', true );

			if ( ! $product_id ) {
				return false;
			}

			/* The product may have been deleted within Stripe, so make sure we can retrieve it. */
			try {
				$product = \Stripe\Product::retrieve( $product_id, $options );

				return $product_id;

			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Create a product in Stripe for the campaign.
		 *
		 * @since  1.3.0
		 *
		 * @param  int        $campaign_id The campaign ID.
		 * @param  array|null $options     Options to pass to Stripe.
		 * @return string|false
		 * @version 1.8.9.1
		 */
		public static function create_stripe_campaign_product( $campaign_id, $options = null ) {
			/* No product could be retrieved, so we need to create one. */
			$statement_descriptor = $this->get_statement_descriptor( get_the_title( $campaign_id ) );

			try {
				$product = \Stripe\Product::create(
					[
						'name'                 => get_the_title( $campaign_id ),
						'type'                 => 'service',
						'statement_descriptor' => $statement_descriptor,
						'metadata'             => [
							'campaign_id' => $campaign_id,
						],
					],
					$options
				);

				update_post_meta( $campaign_id, 'stripe_product_id', $product->id );

				return $product->id;

			} catch ( Exception $e ) {
				/* Log the error message and return false. */
				if ( charitable_is_debug() ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'STRIPE - Error creating product: ' . $e->getMessage() );
				}

				return false;
			}
		}

		/**
		 * Given a Stripe subscription, return the Charitable subscription status.
		 *
		 * @since  1.3.0
		 *
		 * @param  object $subscription The subscription object from Stripe.
		 * @return string
		 */
		public static function get_subscription_status( $subscription ) {
			switch ( $subscription->status ) {
				case 'active':
					$status = 'charitable-active';
					break;

				case 'past_due':
				case 'canceled':
				case 'unpaid':
					$status = 'charitable-failed';
					break;

				default:
					$status = 'charitable-pending';
			}

			/**
			 * Filter the status for a recurring subscription based on a Stripe status.
			 *
			 * @since 1.2.0
			 *
			 * @param string $status        The Charitable status.
			 * @param string $stripe_status Stripe's status for the subscription.
			 */
			return apply_filters( 'charitable_stripe_recurring_subscription_status', $status, $subscription->status );
		}

		/**
		 * Return the Stripe period given a set of plan args.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $args The plan args.
		 * @return string
		 */
		public static function get_plan_period( $args ) {
			switch ( $args['period'] ) {
				case 'month':
				case 'quarter':
				case 'semiannual':
					$period = 'month';
					break;

				default:
					$period = $args['period'];
			}

			return $period;
		}

		/**
		 * Return the Stripe billing interval, given a set of plan args.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $args The plan args.
		 * @return int
		 */
		public static function get_plan_interval( $args ) {
			switch ( $args['period'] ) {
				case 'quarter':
					$interval = 3;
					break;

				case 'semiannual':
					$interval = 6;
					break;

				default:
					$interval = $args['interval'];
			}

			return $interval;
		}

		/**
		 * Sanitize the plan amount.
		 *
		 * @since  1.3.0
		 *
		 * @param  string  $amount                The plan amount.
		 * @param  string  $currency              The site currency.
		 * @param  boolean $zero_decimal_currency Whether the site is using a zero decimal currency.
		 * @return string
		 */
		public static function sanitize_plan_amount( $amount, $currency, $zero_decimal_currency ) {
			if ( version_compare( charitable_recurring()->get_version(), '1.0.5', '<' ) ) {
				return $zero_decimal_currency ? $amount / 100 : $amount;
			} else {
				return self::get_sanitized_donation_amount( $amount, $currency );
			}
		}

		/**
		 * Checks whether the donation being processed is recurring.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function is_recurring_donation() {
			return false !== $this->processor->get_donation_data_value( 'donation_plan', false );
		}

		/**
		 * Set the $charges property to empty.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function clear_charges() {
			$this->charges = [];
		}

		/**
		 * Return the results of all charges.
		 *
		 * @since  1.3.0
		 *
		 * @return array
		 */
		public function get_charges() {
			return $this->charges;
		}

		/**
		 * Saves the results of a charge.
		 *
		 * @since  1.3.0
		 *
		 * @param  mixed  $result The result of a Stripe charge.
		 * @param  string $status The status of the charge.
		 * @return void
		 */
		public function save_charge_results( $result, $status ) {
			$this->charges[] = [
				'result' => $result,
				'status' => $status,
			];
		}

		/**
		 * When a charge fails and raises an exception, save the result and
		 * add a notice for the error.
		 *
		 * @since  1.3.0
		 *
		 * @param  Exception $e       Exception thrown.
		 * @param  string    $message Fallback message to be logged if one isn't set in the exception body.
		 * @return void
		 */
		public function save_charge_error( Exception $e, $message ) {
			$body = $e->getJsonBody();

			if ( isset( $body['error']['message'] ) ) {
				$message = $body['error']['message'];
			}

			charitable_get_notices()->add_error( $message );

			$this->save_charge_results( $body, 'error' );
		}

		/**
		 * Log a failed Stripe charge.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $charge_result The charge result.
		 * @return void
		 */
		public function log_error( $charge_result ) {
			$this->donation_log->add(
				sprintf(
					/* translators: %s: type of error */
					__( 'Stripe error: %s', 'charitable' ),
					'<code>' . $charge_result['result']['error']['type'] . '</code>'
				)
			);
		}

		/**
		 * Log a successful Stripe charge.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $charge_result The charge result.
		 * @return void
		 */
		public function log_success( $charge_result ) {
			/* Charge includes an application fee. */
			if ( ! is_null( $charge_result->application_fee ) ) {
				$this->log_application_fee( $charge_result );
			}

			/* Charge is on our account (not directly on a connected account). */
			if ( is_null( $charge_result->application ) || ! is_null( $charge_result->destination ) ) {
				$url = sprintf(
					'https://dashboard.stripe.com/%spayments/%s',
					$charge_result->livemode ? '' : 'test/',
					$charge_result->id
				);

				$this->donation_log->add(
					sprintf(
						/* translators: %s: link to Stripe charge details */
						__( 'Stripe charge: %s', 'charitable' ),
						'<a href="' . $url . '" target="_blank"><code>' . $charge_result->id . '</code></a>'
					)
				);
			}
		}

		/**
		 * Log an application fee for a charge.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $charge_result The charge result.
		 * @return void
		 */
		public function log_application_fee( $charge_result ) {
			$url = sprintf(
				'https://dashboard.stripe.com/%sapplications/fees/%s',
				$charge_result->livemode ? '' : 'test/',
				$charge_result->application_fee
			);

			$this->donation_log->add(
				sprintf(
					/* translators: %s: link to Stripe application fee details */
					__( 'Stripe application fee: %s', 'charitable' ),
					'<a href="' . $url . '" target="_blank"><code>' . $charge_result->application_fee . '</code></a>'
				)
			);
		}

		/**
		 * Set up connected account data for this donation.
		 *
		 * @since  1.4.0
		 * @version 1.8.9.1
		 *
		 * @return void
		 */
		public function setup_stripe_connect() {
			$debug = charitable_is_debug( 'stripe' );

			$campaign_donations = $this->donation->get_campaign_donations();

			if ( charitable_is_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( 'setup_stripe_connect in embedded stripe' );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( print_r( $campaign_donations, true ) );
			}

			if ( $debug ) {
				$campaign_id = current( $campaign_donations )->campaign_id;
				error_log( sprintf( '[Charitable Stripe] setup_stripe_connect called. Campaign ID: %s, Using Stripe Connect: %s', $campaign_id, charitable_using_stripe_connect() ? 'yes' : 'no' ) ); // phpcs:ignore
			}

			$this->application_fee = $this->get_application_fee_amount(
				current( $campaign_donations )->amount,
				0
			);

			if ( ! class_exists( 'Charitable_Stripe_Connect' ) ) {
				if ( $debug ) {
					error_log( '[Charitable Stripe] Legacy Charitable_Stripe_Connect class not found. Using built-in Stripe Connect handling.' ); // phpcs:ignore
				}
			}

			$campaign_donations = $this->donation->get_campaign_donations();

			/* We cannot support multiple campaign donations in a single donation with Stripe Connect. */
			if ( 1 < count( $campaign_donations ) ) {
				wp_die( esc_html( __( 'Error: Unable to process multiple campaign donations in a single donation with Stripe Connect.', 'charitable' ) ) );
			}

			$connected_account = charitable_stripe_get_connected_account_for_campaign( current( $campaign_donations )->campaign_id );

			if ( ! $connected_account ) {
				if ( $debug ) {
					error_log( sprintf( '[Charitable Stripe] No legacy connected account for campaign %s. connect_mode: %s, options: %s', current( $campaign_donations )->campaign_id, $this->connect_mode ? $this->connect_mode : 'not set', ! empty( $this->options ) ? wp_json_encode( $this->options ) : 'empty' ) ); // phpcs:ignore
				}
				return;
			}

			$this->connect_mode = charitable_get_option( [ 'gateways_stripe', 'charge_owner' ] );

			/* Recurring donations on connected accounts are processed directly on the connected account. */
			if ( 'direct' == $this->connect_mode || $this->is_recurring_donation() ) {
				$this->options['stripe_account'] = $connected_account;
			} else {
				$this->destination = $connected_account;
			}

			if ( $debug ) {
				error_log( sprintf( '[Charitable Stripe] setup_stripe_connect completed. connect_mode: %s, connected_account: %s, destination: %s', $this->connect_mode, $connected_account, $this->destination ? $this->destination : 'none' ) ); // phpcs:ignore
			}

			$this->application_fee = $this->get_application_fee_amount(
				current( $campaign_donations )->amount,
				charitable_get_option( [ 'gateways_stripe', 'application_fee' ] )
			);
		}

		/**
		 * Returns whether the current payment will be processed directly on a connected account.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function using_connected_account() {
			return is_array( $this->options ) && array_key_exists( 'stripe_account', $this->options );
		}

		/**
		 * Get the application fee percentage to apply.
		 *
		 * @since  1.4.3
		 *
		 * @return false|int
		 */
		public function get_application_fee_percentage() {
			if ( ! $this->connect_mode ) {
				return false;
			}

			return charitable_get_option( [ 'gateways_stripe', 'application_fee' ], false );
		}

		/**
		 * Return the application fee to be charged for a particular amount.
		 *
		 * @since   1.4.2
		 * @version 1.8.0
		 * @version 1.8.8.3
		 * @version 1.8.9.1
		 *
		 * @param  mixed $amount          The donation amount.
		 * @param  mixed $application_fee The application fee.
		 * @return int
		 */
		public function get_application_fee_amount( $amount, $application_fee ) {

			$fee    = 0;
			$amount = str_replace( ',', '.', $amount );

			if ( charitable_is_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'get_application_fee_amount' );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( print_r( $amount, true ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( print_r( $application_fee, true ) );
			}

			if ( round( (float) $amount ) === 0 ) {
				return 0;
			}

			if ( charitable_is_pro() || ! charitable_using_stripe_connect() ) {
				$fee = round(
					$amount * 0,
					0
				);

			} else {
				// Check if this is a zero-decimal currency (JPY, KRW, VND, etc.)
				if ( self::is_zero_decimal_currency() ) {
					// For zero-decimal currencies, use 0.03 (3% as decimal)
					$fee = round(
						(float) $amount * 0.03,
						0
					);
				} else {
					// For regular currencies (USD, EUR, GBP, etc.), use 3 (cents per dollar)
					$application_fee = 3;
					$fee = round(
						(float) $amount * $application_fee,
						0
					);
				}

			}

			if ( charitable_is_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'get_application_fee_amount fee' );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( print_r( $fee, true ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( print_r( $amount, true ) );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( print_r( $application_fee, true ) );
			}

			return $fee;
		}

		/**
		 * Create a Stripe Customer object through the API.
		 *
		 * @deprecated To be removed in 1.6.0 or 2.1.0.
		 *
		 * @since  1.3.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @return string|false
		 */
		public function create_stripe_customer() {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.4.0' );

			$customer = Charitable_Stripe_Customer::create_for_donor( $this->donor );

			if ( is_null( $customer ) || is_null( $customer->get( 'id' ) ) ) {
				return false;
			}

			return $customer->get( 'id' );
		}

		/**
		 * Return the saved Stripe customer id from the user meta table.
		 *
		 * @deprecated To be removed in 1.6.0 or 2.1.0.
		 *
		 * @since  1.3.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @return string|false String if one is set, otherwise false.
		 */
		public function get_saved_stripe_customer_id() {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.4.0' );

			$customer = Charitable_Stripe_Customer::init_with_donor( $this->donor );

			if ( is_null( $customer ) || is_null( $customer->get( 'id' ) ) ) {
				return false;
			}

			return $customer->get( 'id' );
		}

		/**
		 * Save the Stripe customer id for logged in users.
		 *
		 * @deprecated To be removed in 1.6.0 or 2.1.0.
		 *
		 * @since  1.3.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @param  string $stripe_customer_id The Stripe customer id.
		 * @return void
		 */
		public function save_stripe_customer_id( $stripe_customer_id ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.4.0' );

			$key = charitable_get_option( 'test_mode' ) ? self::STRIPE_CUSTOMER_ID_KEY_TEST : self::STRIPE_CUSTOMER_ID_KEY;

			update_user_meta( $this->donor->ID, $key, $stripe_customer_id );
		}

		/**
		 * Return the Stripe Customer object for a particular Stripe customer id.
		 *
		 * @deprecated To be removed in 1.6.0 or 2.1.0.
		 *
		 * @since  1.3.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @param  string $stripe_customer_id The Stripe customer id.
		 * @return object|null
		 */
		public function get_stripe_customer_object( $stripe_customer_id ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.4.0' );

			$customer = new Charitable_Stripe_Customer( $stripe_customer_id );

			return $customer->get_customer();
		}

		/**
		 * Returns a card ID for the customer.
		 *
		 * @deprecated To be removed in 1.6.0 or 2.1.0.
		 *
		 * @since  1.3.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @param  string $customer  Stripe's customer ID.
		 * @param  string $card_args The customer's card details or token.
		 * @return string|false Card ID or false if Stripe returns an error.
		 */
		public function get_stripe_customer_card_id( $customer, $card_args ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.4.0' );

			try {
				$customer = $this->get_stripe_customer_object( $customer );
				$card     = $customer->sources->create( [ 'source' => $card_args ] );
			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );

				charitable_get_notices()->add_error( $message );

				return false;
			}

			return $card->id;
		}
	}

endif;
