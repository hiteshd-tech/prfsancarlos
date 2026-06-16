<?php
/**
 * Model for creating and retrieving the plans related to a particular campaign.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Plan
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Plan' ) ) :

	/**
	 * Charitable_Stripe_Plan
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Plan {

		/**
		 * Campaign ID.
		 *
		 * @since 1.4.0
		 *
		 * @var   int
		 */
		private $campaign_id;

		/**
		 * Options.
		 *
		 * @since 1.4.0
		 *
		 * @var   array|null
		 */
		private $options;

		/**
		 * Mode.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		private $mode;

		/**
		 * The campaign's stored plans.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $plans;

		/**
		 * Internal arguments used for defining a particular plan.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $args;

		/**
		 * Plan args.
		 *
		 * @since 1.4.0
		 *
		 * @var   array
		 */
		private $plan_args;

		/**
		 * Plan key.
		 *
		 * @since 1.4.0
		 *
		 * @var   string
		 */
		private $plan_key;

		/**
		 * Create class object.
		 *
		 * @since 1.4.0
		 *
		 * @param int        $campaign_id The campaign id.
		 * @param array      $args        Mixed set of args.
		 * @param array|null $options     Additional options to pass to Stripe in API request.
		 */
		public function __construct( $campaign_id, $args, $options = null ) {
			$this->campaign_id = $campaign_id;
			$this->mode        = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$this->args        = $args;
			$this->options     = $options;
		}

		/**
		 * Return a class property if set.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $prop The class property to return.
		 * @return mixed Returns null if the class property is not set.
		 */
		public function __get( $prop ) {
			return isset( $this->$prop ) ? $this->$prop : null;
		}

		/**
		 * Return the plans for the campaign.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_plans() {
			if ( isset( $this->plans ) ) {
				return $this->plans;
			}

			$all_plans = get_post_meta( $this->campaign_id, 'stripe_donation_plans', true );

			if ( ! is_array( $all_plans ) || ! array_key_exists( $this->mode, $all_plans ) ) {
				$this->plans = [];
				return $this->plans;
			}

			$this->plans = $all_plans[ $this->mode ];

			return $this->plans;
		}

		/**
		 * Return the plan args.
		 *
		 * @since  1.4.0
		 *
		 * @return array
		 */
		public function get_plan_args() {
			if ( array_key_exists( 'recurring', $this->args ) ) {
				$this->plan_args = charitable_recurring_get_plan_args(
					[
						'period'   => $this->args['recurring']->get_donation_period(),
						'amount'   => charitable_sanitize_amount( (string) $this->args['recurring']->get_recurring_donation_amount( false ) ),
						'interval' => $this->args['recurring']->get_donation_interval(),
					]
				);
			} else {
				$this->plan_args = charitable_recurring_get_plan_args( $this->args );
			}

			return $this->plan_args;
		}

		/**
		 * Return the key for a plan.
		 *
		 * @since  1.4.0
		 *
		 * @return string
		 */
		public function get_plan_key() {
			if ( ! isset( $this->plan_key ) ) {
				$this->plan_key = charitable_recurring_get_plan_key( $this->get_plan_args() );
			}

			return $this->plan_key;
		}

		/**
		 * Return the plan id, or false if none exists.
		 *
		 * @since  1.4.0
		 *
		 * @param  boolean $check_api Whether to check the API for the plan.
		 * @return string|false Plan ID if set. False otherwise.
		 */
		public function get_plan( $check_api = false ) {
			$plan_key = $this->get_plan_key();
			$plans    = $this->get_plans();

			if ( ! array_key_exists( $plan_key, $plans ) ) {
				return false;
			}

			if ( ! $check_api ) {
				return $plans[ $plan_key ];
			}

			return $this->plan_exists( $plans[ $plan_key ] );
		}

		/**
		 * Checks whether a plan still exists in Stripe.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $plan_id The plan ID.
		 * @return string|false The plan ID if it still exists. False otherwise.
		 */
		public function plan_exists( $plan_id ) {
			try {
				$plan = \Stripe\Plan::retrieve( $plan_id, $this->options );

				return $plan_id;

			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Create a plan.
		 *
		 * @since  1.4.0
		 *
		 * @return string The Plan ID.
		 */
		public function create_plan() {
			$currency              = charitable_get_currency();
			$zero_decimal_currency = Charitable_Stripe_Gateway_Processor::is_zero_decimal_currency( $currency );
			$plan_args             = $this->get_plan_args();
			$period                = $this->get_plan_period( $plan_args );
			$interval              = $this->get_plan_interval( $plan_args );
			$amount                = $this->sanitize_plan_amount( $plan_args['amount'], $currency, $zero_decimal_currency );
			$amount_description    = strval( $zero_decimal_currency ? $amount : $amount / 100 );
			$plan_id               = $period . '-' . $interval . '-' . $amount . $currency . '-' . $this->campaign_id;
			$plan_name             = sprintf(
				/* translators: %1$s: campaign title; %2$s: amount; %3$s: currency; %4$s: period */
				_x( '%1$s - %2$s %3$s every %4$s', 'campaign title — amount every period', 'charitable' ),
				get_the_title( $this->campaign_id ),
				charitable_sanitize_amount( $amount_description ),
				$currency,
				charitable_recurring_get_donation_periods_i18n( $interval, $period )
			);

			$product    = new Charitable_Stripe_Product( $this->campaign_id, $this->options );
			$product_id = $product->get( 'id' );

			if ( ! $product_id ) {
				$product_id = $product->create_product();

				if ( ! $product_id ) {
					return false;
				}
			}

			/**
			 * Filter the arguments passed to Stripe to make a new plan.
			 *
			 * @since 1.4.3
			 *
			 * @param array                  $plan_args The plan args to be sent to Stripe.
			 * @param Charitable_Stripe_Plan $plan      This plan object.
			 */
			$plan_args = apply_filters(
				'charitable_stripe_plan_args',
				[
					'id'             => $plan_id,
					'interval'       => $period,
					'interval_count' => $interval,
					'currency'       => $currency,
					'amount'         => $amount,
					'product'        => $product_id,
				],
				$this
			);

			try {
				$plan = \Stripe\Plan::create( $plan_args, $this->options );
			} catch ( Exception $e ) {
				$response = json_decode( $e->getHttpBody() );

				if ( function_exists( 'charitable_is_debug' ) ? charitable_is_debug() : ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
					error_log( var_export( $response->error, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
				}

				/* The plan already exists. */
				if ( 'resource_already_exists' != $response->error->code ) {
					/* Log the error message and return false. */
					charitable_get_notices()->add_error( 'STRIPE - Error creating plan: ' . $e->getMessage() );

					$plan_id = false;
				}
			}//end try

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
			$plan_id = apply_filters(
				'charitable_recurring_create_gateway_plan_' . Charitable_Gateway_Stripe_AM::get_gateway_id(),
				$plan_id,
				$this->campaign_id,
				$this->get_plan_args(),
				$this->args
			);

			if ( $plan_id ) {
				$this->save_plan( $plan_id );
			}

			return $plan_id;
		}

		/**
		 * Save plan to campaign meta.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $plan_id Save a new plan ID.
		 * @return mixed
		 */
		public function save_plan( $plan_id ) {
			$mode_plans                          = $this->get_plans();
			$mode_plans[ $this->get_plan_key() ] = $plan_id;

			$all_plans = get_post_meta( $this->campaign_id, 'stripe_donation_plans', true );

			if ( ! is_array( $all_plans ) ) {
				$all_plans = [];
			}

			$all_plans[ $this->mode ] = $mode_plans;

			return update_post_meta( $this->campaign_id, 'stripe_donation_plans', $all_plans );
		}

		/**
		 * Return the Stripe period given a set of plan args.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $args The plan args.
		 * @return string
		 */
		public function get_plan_period( $args ) {
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
		 * @since  1.4.0
		 *
		 * @param  array $args The plan args.
		 * @return int
		 */
		public function get_plan_interval( $args ) {
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
		 * @since  1.4.0
		 *
		 * @param  string  $amount                The plan amount.
		 * @param  string  $currency              The site currency.
		 * @param  boolean $zero_decimal_currency Whether the site is using a zero decimal currency.
		 * @return string
		 */
		public function sanitize_plan_amount( $amount, $currency, $zero_decimal_currency ) {
			if ( version_compare( charitable_recurring()->get_version(), '1.0.5', '<' ) ) {
				return $zero_decimal_currency ? $amount / 100 : $amount;
			} else {
				return Charitable_Stripe_Gateway_Processor::get_sanitized_donation_amount( $amount, $currency );
			}
		}
	}

endif;
