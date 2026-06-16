<?php
/**
 * Class that sets up the gateways.
 *
 * @package   Charitable/Classes/Charitable_Gateways
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.57
 * @version   1.8.7 - Added Square.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateways' ) ) :

	/**
	 * Charitable_Gateways
	 *
	 * @since 1.0.0
	 */
	class Charitable_Gateways {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Gateways|null
		 */
		private static $instance = null;

		/**
		 * All available payment gateways.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		private $gateways;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		protected function __construct() {
			add_action( 'init', array( $this, 'check_settings' ), 10 );
			add_action( 'init', array( $this, 'register_gateways' ), 1 );
			add_action( 'init', array( $this, 'connect_gateways' ), 99 ); // Stripe.
			add_action( 'init', array( $this, 'disconnect_gateways' ), 99 ); // Stripe.

			add_action( 'charitable_make_default_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_action( 'charitable_enable_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_action( 'charitable_disable_gateway', array( $this, 'handle_gateway_settings_request' ) );
			add_filter( 'charitable_settings_fields_gateways_gateway', array( $this, 'register_gateway_settings' ), 10, 2 );

			add_action( 'charitable_disable_gateway', array( $this, 'handle_gateway_settings_request' ) );

			/**
			 * Square Connect
			 */
			add_action( 'init', array( $this, 'disconnect_gateways_square' ), 99 ); // Square.
			add_action( 'init', array( $this, 'refresh_square_token' ), 99 ); // Square.

			add_action( 'init', array( $this, 'get_gateway_error_message' ), 99 );

			do_action( 'charitable_gateway_start', $this );

			add_action( 'wp_ajax_charitable-show-stripe-keys', array( $this, 'show_stripe_manual_keys_ajax' ), 10 );
			add_action( 'wp_ajax_charitable_switch_square_gateways', array( $this, 'handle_square_gateway_switch' ) );
			add_action( 'wp_ajax_charitable_switch_square_core_to_legacy', array( $this, 'handle_square_core_to_legacy_switch' ) );

			// Dequeue Square Legacy scripts if Square Legacy is not active.
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_square_legacy_scripts' ), 999 );
		}


		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Gateways
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register gateways.
		 *
		 * To register a new gateway, you need to hook into the `charitable_payment_gateways`
		 * hook and give Charitable the name of your gateway class.
		 *
		 * @since  1.2.0
		 *
		 * @return void
		 */
		public function register_gateways() {
			/**
			 * Filter the list of payment gateways.
			 *
			 * @since   1.2.0
			 * @version 1.8.7
			 *
			 * @param array $gateways The list of gateways in gateway ID => gateway class format.
			 */

			$gateways = array(
				'stripe'  => 'Charitable_Gateway_Stripe_AM',
				'paypal'  => 'Charitable_Gateway_Paypal',
				'offline' => 'Charitable_Gateway_Offline',
			);

			// Always add Square Core to the available gateways.
			$gateways['square_core'] = 'Charitable_Gateway_Square';

			$this->gateways = apply_filters(
				'charitable_payment_gateways',
				$gateways
			);

			// If Legacy mode is active and Square Legacy gateway is active, deactivate Square Core.
			if ( charitable_square_legacy_mode() && $this->is_active_gateway( 'square' ) ) {
				$this->disable_gateway( 'square_core' );

				if ( charitable_is_debug( 'square' ) ) {
					error_log( '[Charitable Square Core] Deactivated Square Core because Square Legacy is active' ); // phpcs:ignore
				}
			}
		}

		/**
		 * Receives a request to enable or disable a payment gateway and validates it before passing it off.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function handle_gateway_settings_request() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_REQUEST['_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_nonce'] ), 'gateway' ) ) {
				wp_die( __( 'Cheatin\' eh?!', 'charitable' ) ); // phpcs:ignore
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Gateway ID, sanitized below
			$gateway = isset( $_REQUEST['gateway_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['gateway_id'] ) ) : false;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			/* Gateway must be set */
			if ( false === $gateway ) {
				wp_die( __( 'Missing gateway.', 'charitable' ) ); // phpcs:ignore
			}

			/* Validate gateway. */
			if ( ! isset( $this->gateways[ $gateway ] ) ) {
				wp_die( __( 'Invalid gateway.', 'charitable' ) ); // phpcs:ignore
			}

			switch ( $_REQUEST['charitable_action'] ) { // phpcs:ignore
				case 'disable_gateway':
					$this->disable_gateway( $gateway );
					break;
				case 'enable_gateway':
					$this->enable_gateway( $gateway );
					$this->redirect_gateway_settings( $gateway );
					break;
				case 'make_default_gateway':
					$this->set_default_gateway( $gateway );
					break;
				default:
					/**
					 * Do something when a gateway settings request takes place.
					 *
					 * @since 1.0.0
					 *
					 * @param string $action     The action taking place.
					 * @param string $gateway_id The gateway ID.
					 */
					do_action( 'charitable_gateway_settings_request', $_REQUEST['charitable_action'], $gateway ); // phpcs:ignore
			}
		}

		/**
		 * Returns all available payment gateways.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_available_gateways() {
			if ( isset( $this->gateways['stripe'] ) ) {
				$this->gateways['stripe'] = charitable()->is_stripe_connect_addon() ? 'Charitable_Gateway_Stripe_AM' : $this->gateways['stripe'];
			}
			return $this->gateways;
		}

		/**
		 * Returns the current active gateways.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_active_gateways() {
			$active_gateways = charitable_get_option( 'active_gateways', array() );

			// Force cleanup of 'square' gateway if charitable-square plugin is not active (runs once per 24 hours).
			$this->maybe_force_cleanup_square_gateway( $active_gateways );

			foreach ( $active_gateways as $gateway_id => $gateway_class ) {
				if ( ! class_exists( $gateway_class ) ) {
					unset( $active_gateways[ $gateway_id ] );
				}
			}

			// Remove 'square' gateway if charitable-square plugin is not active.
			if ( isset( $active_gateways['square'] ) && ! $this->is_square_addon_active() ) {
				unset( $active_gateways['square'] );
			}

			uksort( $active_gateways, array( $this, 'sort_by_default' ) );

			// force the strip active gateway normally for stripe addon to point to the stripe built into core.
			if ( isset( $active_gateways['stripe'] ) ) {
				$active_gateways['stripe'] = 'Charitable_Gateway_Stripe_AM';
			}

			/**
			 * Filter the list of active gateways.
			 *
			 * @since 1.0.0
			 *
			 * @param array $gateways Active gateways.
			 */
			return apply_filters( 'charitable_active_gateways', $active_gateways );
		}

		/**
		 * Returns an array of the active gateways, in ID => name format.
		 *
		 * This is useful for select/radio input fields.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_gateway_choices() {
			$gateways = array();

			foreach ( $this->get_active_gateways() as $id => $class ) {
				$gateway         = new $class();
				$gateways[ $id ] = $gateway->get_label();
			}

			return $gateways;
		}

		/**
		 * Returns a text description of the active gateways.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		public function get_active_gateways_names() {
			$gateways = array();

			foreach ( $this->get_active_gateways() as $id => $class ) {
				$gateway    = new $class();
				$gateways[] = $gateway->get_name();
			}

			return $gateways;
		}

		/**
		 * Return the gateway class name for a given gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return string|false
		 */
		public function get_gateway( $gateway ) {
			return isset( $this->gateways[ $gateway ] ) ? $this->gateways[ $gateway ] : false;
		}

		/**
		 * Return the gateway object for a given gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return Charitable_Gateway|null
		 */
		public function get_gateway_object( $gateway ) {
			$class  = $this->get_gateway( $gateway );
			$object = $class ? new $class() : null;

			/**
			 * Filter the gateway object.
			 *
			 * @since 1.6.30
			 *
			 * @param Charitable_Gateway|null $object  The gateway object.
			 */
			return apply_filters( 'charitable_gateway_object_' . $gateway, $object, $gateway );
		}

		/**
		 * Returns whether the passed gateway is active.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway_id Gateway ID.
		 * @return boolean
		 */
		public function is_active_gateway( $gateway_id ) {
			return array_key_exists( $gateway_id, $this->get_active_gateways() );
		}

		/**
		 * Checks whether the submitted gateway is valid.
		 *
		 * @since  1.4.3
		 *
		 * @param  string $gateway Gateway ID.
		 * @return boolean
		 */
		public function is_valid_gateway( $gateway ) {
			/**
			 * Validate a particular gatewya.
			 *
			 * @since 1.4.3
			 *
			 * @param boolean $valid   Whether a gateway is valid.
			 * @param string  $gateway The gateway ID.
			 */

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'is_valid_gateway' );
				error_log( print_r( $gateway, true ) );
				error_log( print_r( $this->gateways, true ) );
				// phpcs:enable
			}

			return apply_filters( 'charitable_is_valid_gateway', array_key_exists( $gateway, $this->gateways ), $gateway );
		}

		/**
		 * Returns the default gateway.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_default_gateway() {
			return charitable_get_option( 'default_gateway', '' );
		}

		/**
		 * Provide default gateway settings fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  array              $settings Gateway settings.
		 * @param  Charitable_Gateway $gateway  The gateway's helper object.
		 * @return array
		 */
		public function register_gateway_settings( $settings, Charitable_Gateway $gateway ) {
			add_filter( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), array( $gateway, 'default_gateway_settings' ), 5 );
			add_filter( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), array( $gateway, 'gateway_settings' ), 15 );

			/**
			 * Filter the settings to show for a particular gateway.
			 *
			 * @since 1.0.0
			 *
			 * @param array $settings Gateway settings.
			 */
			return apply_filters( 'charitable_settings_fields_gateways_gateway_' . $gateway->get_gateway_id(), $settings );
		}

		/**
		 * Returns true if test mode is enabled.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function in_test_mode() {
			$enabled = charitable_get_option( 'test_mode', false );

			/**
			 * Return whether Charitable is in test mode.
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $enabled Whether test mode is on.
			 */
			return apply_filters( 'charitable_in_test_mode', $enabled );
		}

		/**
		 * Checks whether all of the active gateways support a feature.
		 *
		 * If ANY gateway doesn't support the feature, this returns false.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $feature Feature to search for.
		 * @return boolean
		 */
		public function all_gateways_support( $feature ) {
			foreach ( $this->get_active_gateways() as $gateway_id => $gateway_class ) {

				$gateway_object = new $gateway_class();

				if ( false === $gateway_object->supports( $feature ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks whether any of the active gateways support a feature.
		 *
		 * If any gateway supports the feature, this returns true. Otherwise false.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $feature Feature to check for.
		 * @return boolean
		 */
		public function any_gateway_supports( $feature ) {
			foreach ( $this->get_active_gateways() as $gateway_id => $gateway_class ) {

				$gateway_object = new $gateway_class();

				if ( true === $gateway_object->supports( $feature ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks whether all of the active gateways support AJAX.
		 *
		 * If ANY gateway doesn't support AJAX, this returns false.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function gateways_support_ajax() {
			return $this->all_gateways_support( '1.3.0' );
		}

		/**
		 * Return an array of recommended gateways for the current site.
		 *
		 * Note that this will only return gateways that are not already
		 * available on the site. i.e. If you have Stripe installed, it
		 * will not suggest that.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_recommended_gateways() {
			$available = $this->get_available_gateways();
			$gateways  = array(
				'payfast'       => __( 'Payfast', 'charitable' ),
				'paystack'      => __( 'Paystack', 'charitable' ),
				'stripe'        => __( 'Stripe', 'charitable' ),
				'authorize_net' => __( 'Authorize.Net', 'charitable' ),
				'windcave'      => __( 'Windcave', 'charitable' ),
				'braintree'     => __( 'Braintree', 'charitable' ),
				'mollie'        => __( 'Mollie', 'charitable' ),
				'gocardless'    => __( 'GoCardless', 'charitable' ),
				'payrexx'       => __( 'Payrexx', 'charitable' ),
			);

			/* If the user has already enabled one of these, leave them alone. :) */
			foreach ( $gateways as $gateway_id => $gateway ) {
				if ( array_key_exists( $gateway_id, $available ) ) {
					return array();
				}
			}

			$currency = charitable_get_default_currency();
			$locale   = get_locale();

			if ( 'en_ZA' == $locale || 'ZAR' == $currency ) {
				return charitable_array_subset( $gateways, array( 'payfast', 'paystack' ) );
			}

			if ( in_array( $currency, array( 'NGN', 'GHS' ) ) ) {
				return charitable_array_subset( $gateways, array( 'paystack' ) );
			}

			if ( in_array( $locale, array( 'en_NZ', 'en_AU', 'en_GB' ) ) || in_array( $currency, array( 'NZD', 'AUD', 'GBP' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'gocardless' ) );
			}

			if ( in_array( $locale, array( 'ms_MY', 'ja', 'zh_HK' ) ) || in_array( $currency, array( 'MYR', 'JPY', 'HKD' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'windcave' ) );
			}

			if ( in_array( $locale, array( 'th' ) ) || in_array( $currency, array( 'BND', 'FJD', 'KWD', 'PGK', 'SBD', 'THB', 'TOP', 'VUV', 'WST' ) ) ) {
				return charitable_array_subset( $gateways, array( 'windcave' ) );
			}

			if ( in_array( $currency, array( 'EUR' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'mollie' ) );
			}

			if ( in_array( $currency, array( 'CHF', 'DKK' ) ) ) {
				return charitable_array_subset( $gateways, array( 'stripe', 'payrexx' ) );
			}

			return charitable_array_subset( $gateways, array( 'stripe', 'braintree' ) );
		}

		/**
		 * Sets the default gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function set_default_gateway( $gateway ) {
			$settings = get_option( 'charitable_settings' );

			$settings['default_gateway'] = $gateway;

			update_option( 'charitable_settings', $settings );

			charitable_get_admin_notices()->add_success( __( 'Default Gateway Updated', 'charitable' ) );

			do_action( 'charitable_set_gateway_gateway', $gateway );
		}

		/**
		 * Enable a payment gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @param  bool   $show_update_message Whether to show the update message.
		 * @return void
		 */
		protected function enable_gateway( $gateway, $show_update_message = true ) {
			$settings = get_option( 'charitable_settings' );

			if ( false === $settings || ! is_array( $settings ) ) {
				echo 'settings is false';
				$settings = array();
			}

			$active_gateways             = isset( $settings['active_gateways'] ) ? $settings['active_gateways'] : array();
			$active_gateways[ $gateway ] = $this->gateways[ $gateway ];

			if ( ! isset( $settings['active_gateways'] ) ) {
				$settings['active_gateways'] = array();
			}

			$settings['active_gateways'] = $active_gateways;

			/* If this is the only gateway, make it the default gateway */
			if ( 1 == count( $settings['active_gateways'] ) ) {
				$settings['default_gateway'] = $gateway;
			}

			update_option( 'charitable_settings', $settings );

			if ( $show_update_message ) {
				Charitable_Settings::get_instance()->add_update_message( __( 'Gateway enabled', 'charitable' ), 'success' );
			}

			/**
			 * Do something when a payment gateway is enabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gateway The payment gateway.
			 */
			do_action( 'charitable_gateway_enable', $gateway );
		}

		/**
		 * Disable a payment gateway.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		protected function disable_gateway( $gateway ) {
			$settings = get_option( 'charitable_settings' );

			if ( ! isset( $settings['active_gateways'][ $gateway ] ) ) {
				return;
			}

			unset( $settings['active_gateways'][ $gateway ] );

			/* Set a new default gateway */
			if ( $gateway == $this->get_default_gateway() ) {

				$settings['default_gateway'] = count( $settings['active_gateways'] ) ? key( $settings['active_gateways'] ) : '';

			}

			update_option( 'charitable_settings', $settings );

			Charitable_Settings::get_instance()->add_update_message( __( 'Gateway disabled', 'charitable' ), 'success' );

			/**
			 * Do something when a payment gateway is disabled.
			 *
			 * @since 1.0.0
			 *
			 * @param string $gateway The payment gateway.
			 */
			do_action( 'charitable_gateway_disable', $gateway );
		}

		/**
		 * Connects to Stripe by saving account information passed back to the plugin.
		 *
		 * @since   4.2.2
		 * @version 1.8.9
		 *
		 * @return void
		 */
		public function connect_gateways() {
			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Do not need to handle this request, bail.
			if (
				! isset( $_GET['wpcharitable_gateway_connect_completion'] ) || // phpcs:ignore
				'stripe_connect' !== $_GET['wpcharitable_gateway_connect_completion'] || // phpcs:ignore
				! isset( $_GET['state'] ) // phpcs:ignore
			) {
				return;
			}

			// Unable to redirect, bail.
			if ( headers_sent() ) {
				return;
			}

			if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Server variables for URL construction
				$current_url = ( is_ssl() ? 'https' : 'http' ) . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server variables are unslashed and then sanitized via esc_url_raw()
				$current_url = esc_url_raw( $current_url ); // phpcs:ignore
			} else {
				$current_url = '';
			}

			$customer_site_url = remove_query_arg(
				array(
					'state',
					'wpcharitable_gateway_connect_completion',
				),
				$current_url
			);

			$wpcharitable_credentials_url = add_query_arg(
				array(
					'live_mode'         => (int) ! charitable_get_option( 'test_mode' ),
					'state'             => sanitize_text_field( $_GET['state'] ), // phpcs:ignore
					'customer_site_url' => urlencode( $customer_site_url ),
				),
				'https://wpcharitable.com/stripe-connect/?wpcharitable_gateway_connect_credentials=stripe_connect'
			);

			// You might need to add add_filter('https_ssl_verify', '__return_false'); here if testing locally.
			$response = wp_remote_get( esc_url_raw( $wpcharitable_credentials_url ) );

			if (
				is_wp_error( $response ) ||
				200 !== wp_remote_retrieve_response_code( $response )
			) {

				// Log error details for debugging.
				if ( is_wp_error( $response ) ) {
					error_log( 'Charitable Stripe Connect Error: ' . $response->get_error_message() . ' | URL: ' . $wpcharitable_credentials_url ); // phpcs:ignore
				} else {
					$response_code = wp_remote_retrieve_response_code( $response );
					$response_body = wp_remote_retrieve_body( $response );
					error_log( 'Charitable Stripe Connect Error: HTTP ' . $response_code . ' | URL: ' . $wpcharitable_credentials_url . ' | Response: ' . substr( $response_body, 0, 500 ) ); // phpcs:ignore
				}

				$stripe_account_settings_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_stripe',
					),
					admin_url( 'admin.php' )
				);

				$message = wpautop(
					sprintf(
						/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
						__(
							'There was an error getting your Stripe credentials. Please %1$stry again%2$s. If you continue to have this problem, please contact support.',
							'charitable'
						),
						'<a href="' . esc_url( $stripe_account_settings_url ) . '">',
						'</a>'
					)
				);

				wp_die( $message ); // phpcs:ignore
			}

			$body = wp_remote_retrieve_body( $response );

			/** @var string $body */
			$body = json_decode( $body, true );

			/** @var array<array<string>> $body */
			$account_data = $body['data'];

			$this->save_account_information( $account_data );

			// update the option to track that we are using stripe connect.
			update_option( 'charitable_using_stripe_connect', time() );

			/**
			 * Allow further processing after connecting a Stripe account.
			 *
			 * @since 3.6.0
			 *
			 * @param array $data Stripe response data.
			 */
			do_action( 'wpcharitable_stripe_account_connected', $account_data ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a legacy hook used by other Charitable code. Changing it would break existing functionality.

			wp_safe_redirect( esc_url_raw( $customer_site_url ) );
			exit;
		}

		/**
		 * Refreshes the Square token.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public function refresh_square_token() {
			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) || charitable_square_legacy_mode() ) {
				return;
			}

			// Do not need to handle this request, bail.
			if (
				! isset( $_GET['wpcharitable_gateway_refresh_token_completion'] ) || // phpcs:ignore
				'square_connect' !== $_GET['wpcharitable_gateway_refresh_token_completion'] || // phpcs:ignore
				! isset( $_GET['state'] ) // phpcs:ignore
			) {
				return;
			}

			// Unable to redirect, bail.
			if ( headers_sent() ) {
				return;
			}

			if ( charitable_is_debug( 'square' ) ) {
				error_log( 'checking for use: refresh_square_token' ); // phpcs:ignore
			}

			if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
				$current_url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore
			} else {
				$current_url = '';
			}

			$customer_site_url = remove_query_arg(
				array(
					'state',
					'wpcharitable_gateway_refresh_token_completion',
					'message',
				),
				$current_url
			);

			$wpcharitable_credentials_url = add_query_arg(
				array(
					'live_mode'         => (int) ! charitable_get_option( 'test_mode' ),
					'state'             => sanitize_text_field( $_GET['state'] ), // phpcs:ignore
					'code'              => 'request_credentials',
					'customer_site_url' => urlencode( $customer_site_url ),
				),
				'https://connect.wpcharitable.com/square-connect/'
			);

			$response = wp_remote_get( esc_url_raw( $wpcharitable_credentials_url ) );

			if (
				is_wp_error( $response ) ||
				200 !== wp_remote_retrieve_response_code( $response )
			) {

				$square_account_settings_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_square_core',
					),
					admin_url( 'admin.php' )
				);

				$message = wpautop(
					sprintf(
						/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
						__(
							'There was an error getting your Square credentials. Please %1$stry again%2$s. If you continue to have this problem, please contact support.',
							'charitable'
						),
						'<a href="' . esc_url( $square_account_settings_url ) . '">',
						'</a>'
					)
				);

				wp_die( $message ); // phpcs:ignore
			}

			$body = wp_remote_retrieve_body( $response );

			$body = json_decode( $body, true );

			$account_data = $body['data'];

			$this->save_account_information_square( $account_data );

			// Don't show the success message if we are on the welcome / getting started page (otherwise it's fine).
			if ( ! isset( $_GET['page'] ) || 'charitable-getting-started' !== $_GET['page'] ) { // phpcs:ignore
				// translators: %s is the mode of the connection.
				charitable_get_admin_notices()->add_success( sprintf( __( 'You have reconnected and updated your access token for Square in %s mode.', 'charitable' ), charitable_get_option( 'test_mode' ) ? 'test' : 'live' ) );
			}

			// update the option to track that we are using square connect.
			// Get the value and update it.
			$charitable_using_square_connect = get_option( 'charitable_using_square_connect', array() );
			if ( ! is_array( $charitable_using_square_connect ) ) {
				$charitable_using_square_connect = array();
			}
			$charitable_using_square_connect['test'] = time();
			update_option( 'charitable_using_square_connect', $charitable_using_square_connect );

			/**
			 * Allow further processing after reconnecting a Square account.
			 *
			 * @since 3.6.0
			 *
			 * @param array $data Square response data.
			 */
			do_action( 'wpcharitable_square_token_refreshed', $account_data ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a legacy hook used by other Charitable code. Changing it would break existing functionality.

			wp_safe_redirect( esc_url_raw( $customer_site_url ) );
			exit;
		}

		/**
		 * Schedule the Square token renewal cron job.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to schedule the cron for (test/live).
		 * @return void
		 */
		private function schedule_square_token_renewal_cron( $mode ) {
			// Clear any existing cron jobs for this mode.
			wp_clear_scheduled_hook( 'charitable_square_token_renewal', array( $mode ) );

			// Schedule the cron job to run every 14 days.
			wp_schedule_event( time() + ( 14 * DAY_IN_SECONDS ), '14days', 'charitable_square_token_renewal', array( $mode ) );
		}

		/**
		 * Renew the Square token via cron job.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to renew the token for (test/live).
		 * @return void
		 */
		public function renew_square_token_cron( $mode ) {
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'renew_square_token_cron for test mode: ' . $mode );
				// phpcs:enable
			}

			// Get the URL to renew the token.
			$url = Charitable_Gateway_Square::get_instance()->get_square_renew_token_url( '', true );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'url: ' . $url );
				// phpcs:enable
			}

			// Clear both failed and success options.
			delete_option( 'wpcharitable_square_failed_token_renewal_' . $mode );
			delete_option( 'wpcharitable_square_token_refreshed_' . $mode );

			if ( empty( $url ) ) {
				update_option( 'wpcharitable_square_failed_token_renewal_' . $mode, time() );
				return;
			}

			// Add SSL verification argsget_square_renew_token_url.
			$args = array(
				'timeout'   => 30,
				'sslverify' => false, // Temporarily disable SSL verification.
				'headers'   => array(
					'Content-Type' => 'application/json',
				),
			);

			// Make the request to the proxy server.
			$response = wp_remote_get( esc_url_raw( $url ), $args );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'response: ' . print_r( $response, true ) );
				// phpcs:enable
			}

			if ( is_wp_error( $response ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'The cron job url has failed with a wp_error: ' . $response->get_error_message() );
					// phpcs:enable
				}
				update_option( 'wpcharitable_square_failed_token_renewal_' . $mode, time() );
				return;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'The cron job url has failed with response code: ' . $response_code );
					// phpcs:enable
				}
				update_option( 'wpcharitable_square_failed_token_renewal_' . $mode, time() );
				return;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'data: ' . print_r( $data, true ) );
				error_log( 'body: ' . print_r( $body, true ) );
				// phpcs:enable
			}

			if ( empty( $data ) || ! isset( $data['data'] ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'The cron job url has failed - no data returned.' );
					// phpcs:enable
				}
				update_option( 'wpcharitable_square_failed_token_renewal_' . $mode, time() );
				return;
			}

			// Update the stored tokens.
			$settings = get_option( 'charitable_settings' );
			$settings['gateways_square'][ $mode ]['access_token']       = isset( $data['data']['access_token'] ) ? sanitize_text_field( $data['data']['access_token'] ) : false;
			$settings['gateways_square'][ $mode ]['refresh_token']      = isset( $data['data']['refresh_token'] ) ? sanitize_text_field( $data['data']['refresh_token'] ) : false;
			$settings['gateways_square'][ $mode ]['token_refresh_date'] = time();

			update_option( 'charitable_settings', $settings );

			// Clear any failure notices.
			delete_option( 'wpcharitable_square_failed_token_renewal_' . $mode );

			// Add a success notice.
			update_option( 'wpcharitable_square_token_refreshed_' . $mode, time() );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'wpcharitable_square_token_refreshed_' . $mode . ' updated to: ' . time() );
				// phpcs:enable
			}
		}

		/**
		 * Saves the account information sent back from Square, alongside other, to identify the connected account.
		 *
		 * @since 1.8.7
		 *
		 * @param array<string> $data Square oAuth account data.
		 * @param string        $gateway_id The gateway ID.
		 * @return void
		 */
		private function save_account_information_square( $data, $gateway_id = 'square' ) {
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'save_account_information_square' );
				error_log( print_r( $data, true ) );
				// phpcs:enable
			}

			$mode = $this->in_test_mode()
				? 'test'
				: 'live';

			$settings = get_option( 'charitable_settings' );

			$settings[ 'gateways_' . $gateway_id ][ $mode ]['access_token']       = isset( $data['access_token'] ) ? sanitize_text_field( $data['access_token'] ) : false;
			$settings[ 'gateways_' . $gateway_id ][ $mode ]['refresh_token']      = isset( $data['refresh_token'] ) ? sanitize_text_field( $data['refresh_token'] ) : false;
			$settings[ 'gateways_' . $gateway_id ][ $mode ]['merchant_id']        = isset( $data['merchant_id'] ) ? sanitize_text_field( $data['merchant_id'] ) : false;
			$settings[ 'gateways_' . $gateway_id ][ $mode ]['location_id']        = isset( $data['location_id'] ) ? sanitize_text_field( $data['location_id'] ) : false;
			$settings[ 'gateways_' . $gateway_id ][ $mode ]['token_refresh_date'] = time();

			update_option( 'charitable_settings', $settings );

			// We save the AES key in it's own WordPress options field, marked as 'charitable_square_aes_key_test' or 'charitable_square_aes_key_live'.
			$aes_key = isset( $data['aes_key'] ) ? sanitize_text_field( $data['aes_key'] ) : false;

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'aes_key: ' . $aes_key );
				// phpcs:enable
			}

			update_option( 'charitable_square_aes_key_' . $mode, $aes_key );

			// Schedule the token renewal cron job.
			$this->schedule_square_token_renewal_cron( $mode );

			do_action( 'charitable_save_account_information_gateway_' . $gateway_id, $settings, $data, $gateway_id );
		}

		/**
		 * Saves the account information sent back from Stripe, alongside other, to identify the connected account.
		 *
		 * @since 4.4.2
		 * @version 1.8.1.12
		 *
		 * @param array<string> $data Stripe oAuth account data.
		 * @param string        $gateway_id The gateway ID.
		 * @return void
		 */
		private function save_account_information( $data, $gateway_id = 'stripe' ) {

			$prefix = $this->in_test_mode()
				? 'test'
				: 'live';

			$settings = get_option( 'charitable_settings' );

			$settings[ 'gateways_' . $gateway_id ]['live_secret_key'] = '';
			$settings[ 'gateways_' . $gateway_id ]['live_public_key'] = '';
			$settings[ 'gateways_' . $gateway_id ]['test_secret_key'] = '';
			$settings[ 'gateways_' . $gateway_id ]['test_public_key'] = '';

			$settings[ 'gateways_' . $gateway_id ][ $prefix . '_secret_key' ] = isset( $data['secret_key'] ) ? sanitize_text_field( $data['secret_key'] ) : false;
			$settings[ 'gateways_' . $gateway_id ][ $prefix . '_public_key' ] = isset( $data['publishable_key'] ) ? sanitize_text_field( $data['publishable_key'] ) : false;

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'save_account_information' );
				error_log( print_r( $settings, true ) );
				// phpcs:enable
			}

			update_option( 'charitable_settings', $settings );

			// Don't show the success message if we are on the welcome / getting started page (otherwise it's fine).
			if ( ! isset( $_GET['page'] ) || 'charitable-getting-started' !== $_GET['page'] ) { // phpcs:ignore
				charitable_get_admin_notices()->add_success( __( 'Stripe Information Updated', 'charitable' ) );
			}

			do_action( 'charitable_save_account_information_gateway_' . $gateway_id, $settings, $data, $gateway_id );
		}

		/**
		 * Disconnects from Stripe by removing associated account information.
		 *
		 * This does not deauthorize the application within the Stripe account.
		 *
		 * @since 4.2.2
		 *
		 * @return void
		 */
		public function disconnect_gateways() {

			// Do not need to handle this request, bail.
			if (
				! ( isset( $_GET['page'] ) && 'charitable-settings' === $_GET['page'] ) ||
				! isset( $_GET['wpcharitable-stripe-disconnect'] )
			) {
				return;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! isset( $_GET['_wpnonce'] ) ) {
				return;
			}

			// Invalid nonce, bail.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'wpcharitable-stripe-connect-disconnect' ) ) {
				return;
			}

			$settings = get_option( 'charitable_settings' );

			unset( $settings['gateways_stripe']['live_secret_key'] );
			unset( $settings['gateways_stripe']['live_public_key'] );
			unset( $settings['gateways_stripe']['test_secret_key'] );
			unset( $settings['gateways_stripe']['test_public_key'] );

			update_option( 'charitable_settings', $settings );

			// update the option to track that we are using stripe connect.
			delete_option( 'charitable_using_stripe_connect' );

			charitable_get_admin_notices()->add_success( __( 'Stripe Connected Removed', 'charitable' ) );

			do_action( 'charitable_remove_connection_gateway_stripe', $settings );

			$redirect_url = add_query_arg(
				array(
					'tab'   => 'gateways',
					'page'  => 'charitable-settings',
					'group' => 'gateways_stripe',
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Disconnects from Square by removing associated account information.
		 * Also remove webhooks from the Square account.
		 *
		 * This does not deauthorize the application within the Square account.
		 * LEGIT BEING CALLED.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public function disconnect_gateways_square() {

			// Do not need to handle this request, bail.
			if (
				! ( isset( $_GET['page'] ) && 'charitable-settings' === $_GET['page'] ) ||
				! isset( $_GET['wpcharitable-square-disconnect'] ) ||
				charitable_square_legacy_mode()
			) {
				return;
			}

			// We need to know if this is a sandbox or live disconnect.
			if ( ! isset( $_GET['mode'] ) ) {
				return;
			}

			$mode = sanitize_text_field( wp_unslash( $_GET['mode'] ) );
			$mode = 'sandbox' === $mode ? 'test' : 'live';

			// Current user cannot handle this request or nonce is invalid.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'wpcharitable-square-connect-disconnect' ) ) {
				if ( charitable_is_debug( 'webhook' ) ) {
					error_log( 'disconnect_gateways_square - user cannot manage options or nonce is invalid' ); // phpcs:ignore
				}
				return;
			}

			$settings = get_option( 'charitable_settings' );

			// Delete the Square connection error notice.
			delete_option( 'charitable_square_connection_error_notice' );

			// Attempt to disconnect / remove webhooks from the Square account.
			$this->remove_webhooks_from_square_account( $mode, $settings );

			$settings['gateways_square'][ $mode ] = array();

			update_option( 'charitable_settings', $settings );

			// remove the option to track that we are using square connect (test or live).
			$charitable_using_square_connect = get_option( 'charitable_using_square_connect', array() );
			if ( isset( $charitable_using_square_connect[ $mode ] ) ) {
				unset( $charitable_using_square_connect[ $mode ] );
				update_option( 'charitable_using_square_connect', $charitable_using_square_connect );
			}

			// translators: %s is the mode of the connection.
			charitable_get_admin_notices()->add_success( sprintf( __( 'Your connection to Square in %s mode has been removed.', 'charitable' ), $mode ) );

			do_action( 'charitable_remove_connection_gateway_square', $settings );

			$redirect_url = add_query_arg(
				array(
					'tab'   => 'gateways',
					'page'  => 'charitable-settings',
					'group' => 'gateways_square_core',
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Redirects to the gateway settings page once a gateway has been activated.
		 *
		 * @since  1.7.0
		 *
		 * @param  string $gateway Gateway ID.
		 * @return void
		 */
		public function redirect_gateway_settings( $gateway ) {

			$settings_url = add_query_arg(
				array(
					'group' => 'gateways_' . $gateway,
				),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
			);

			wp_safe_redirect( $settings_url );
			exit;
		}

		/**
		 * Sort the active gateways, placing the default gateway first.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $a Gateway to compare.
		 * @param  string $b Gateway to compare against.
		 * @return int
		 */
		protected function sort_by_default( $a, $b ) {
			$default = $this->get_default_gateway();

			if ( $a == $default ) { // phpcs:ignore
				return -1;
			}

			if ( $b == $default ) { // phpcs:ignore
				return 1;
			}

			return 0;
		}

		/**
		 * Remove webhooks from the Square account.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to remove the webhooks from.
		 * @param array  $settings The settings array.
		 * @param bool   $force_remove Whether to force remove the webhooks.
		 * @return void
		 */
		public function remove_webhooks_from_square_account( $mode, $settings = array(), $force_remove = true ) {

			if ( charitable_is_debug( 'square' ) ) {
				error_log( 'remove_webhooks_from_square_account' ); // phpcs:ignore
			}

			// Check if settings are empty and if so, get the settings from the database.
			if ( empty( $settings ) ) {
				$settings = get_option( 'charitable_settings' );
			}

			$access_token = $settings['gateways_square'][ $mode ]['access_token'];

			// Test to see if we have the access token.
			if ( empty( $access_token ) ) {
				// if force_remove is true, we can remove the webhooks from the Square account in Charitable.
				if ( $force_remove ) {
					if ( charitable_is_debug( 'square' ) ) {
						error_log( 'remove_webhooks_from_square_account:force_remove' ); // phpcs:ignore
					}
					$settings['gateways_square'][ $mode ]['webhooks'] = array();
					update_option( 'charitable_settings', $settings );
					return;

				}
				return;
			} elseif ( isset( $settings['gateways_square'][ $mode ]['webhook_id'] ) ) {
				$webhook_manager = new Charitable_Square_WebhooksManager( $mode, $access_token );
				$result          = $webhook_manager->remove_webhook( $access_token, $settings['gateways_square'][ $mode ]['webhook_id'] );

			}

			if ( charitable_is_debug( 'square' ) ) {
				error_log( 'remove_webhooks_from_square_account:deactivate_webhook' ); // phpcs:ignore
			}

			return $result;
		}

		/**
		 * Ensure there is a settings option when landing on settings pages, even if init blank. Mostly applicable to new installs.
		 *
		 * @since  1.4.0
		 * @since  1.8.1.11
		 *
		 * @return void
		 */
		public function check_settings() {

			if ( isset( $_GET['page'] ) && 'charitable-settings' !== $_GET['page'] ) { // phpcs:ignore
				return;
			}

			$settings = get_option( 'charitable_settings' );

			// attach this to CHARITABLE_DEBUG constant.
			if ( charitable_is_debug() ) {
				// phpcs:disable
				// error_log( 'checking_settings' );
				// error_log( print_r( $settings, true ) );
				// phpcs:enable
			}

			// If there is no settings, or the settings are empty, set the test mode to true and stripe is default gateway.
			if ( false === $settings || ( is_array( $settings ) && empty( $settings ) ) ) {
				$this->enable_gateway( 'stripe', false );
				$settings              = (array) get_option( 'charitable_settings' );
				$settings['test_mode'] = true;
				update_option( 'charitable_settings', $settings );
			}
		}

		/**
		 * Check the setup stored value of the payment methods.
		 *
		 * @since  1.8.4
		 *
		 * @return bool
		 */
		public function check_setup_payment_methods() {

			$payment_methods = get_option( 'charitable_setup_payment_methods', array() );

			if ( ! is_array( $payment_methods ) ) {
				return false;
			}

			$payment_methods = array_map( 'sanitize_text_field', $payment_methods );

			if ( empty( $payment_methods ) ) {
				return false;
			}

			$gateways_enbled = 0;

			if ( in_array( 'stripe', $payment_methods, true ) ) {
				// check if this gateway is already enabled.
				if ( ! $this->is_active_gateway( 'stripe' ) ) {
					$this->enable_gateway( 'stripe', false );
					++$gateways_enbled;
				}
			}

			if ( in_array( 'paypal', $payment_methods, true ) ) {
				// check if this gateway is already enabled.
				if ( ! $this->is_active_gateway( 'paypal' ) ) {
					$this->enable_gateway( 'paypal', false );
					++$gateways_enbled;
				}
			}

			if ( in_array( 'offline', $payment_methods, true ) ) {
				// check if this gateway is already enabled.
				if ( ! $this->is_active_gateway( 'offline' ) ) {
					$this->enable_gateway( 'offline', false );
				}
			}

			if ( $gateways_enbled > 0 && class_exists( 'Charitable_Checklist' ) ) {
				$checklist_class = Charitable_Checklist::get_instance();
				$checklist_class->mark_step_completed( 'connect-payment' );
			}

			return true;
		}

		/**
		 * Display the test and live fields that represent the manual keys for Stripe that users might be already using prior to 1.7.0.
		 *
		 * @since  1.7.0
		 */
		public function show_stripe_manual_keys_ajax() {

			if ( ! isset( $_POST ) || 'charitable-show-stripe-keys' !== $_POST['action'] ) { // phpcs:ignore
				return;
			}

			// Invalid nonce, bail.
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'charitable-show-stripe-keys' ) ) { // phpcs:ignore
				return;
			}

			// Current user cannot handle this request.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings = get_option( 'charitable_settings' );

			if ( false === $settings ) {
				return;
			}

			$gateway_id = 'stripe';

			$live_secret_key = $settings[ 'gateways_' . $gateway_id ]['live_secret_key'];
			$live_public_key = $settings[ 'gateways_' . $gateway_id ]['live_public_key'];
			$test_secret_key = $settings[ 'gateways_' . $gateway_id ]['test_secret_key'];
			$test_public_key = $settings[ 'gateways_' . $gateway_id ]['test_public_key'];

			$html = '<tr><th scope="row">Live Settings</th><td><hr  />
			</td></tr><tr class="wide"><th scope="row">Live Secret Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_live_secret_key"
				name="charitable_settings[gateways_stripe][live_secret_key]"
				value="' . trim( $live_secret_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr><tr class="wide"><th scope="row">Live Publishable Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_live_public_key"
				name="charitable_settings[gateways_stripe][live_public_key]"
				value="' . trim( $live_public_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr><tr><th scope="row">Test Settings</th><td><hr  />
			</td></tr><tr class="wide"><th scope="row">Test Secret Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_test_secret_key"
				name="charitable_settings[gateways_stripe][test_secret_key]"
				value="' . trim( $test_secret_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr><tr class="wide"><th scope="row">Test Publishable Key</th><td><input type="text"
				id="charitable_settings_gateways_stripe_test_public_key"
				name="charitable_settings[gateways_stripe][test_public_key]"
				value="' . trim( $test_public_key ) . '"
				class="charitable-settings-field wide"
				 />
			</td></tr>';

			wp_send_json_success( $html );
		}

		/**
		 * Returns the error message for the square connect gateway.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public function get_gateway_error_message() {

			if ( ! isset( $_GET['square_connect_error'] ) && ! isset( $_GET['square_connect_error_description'] ) ) { // phpcs:ignore
				return;
			}

			$square_connect_error             = sanitize_text_field( wp_unslash( $_GET['square_connect_error'] ) ); // phpcs:ignore
			$square_connect_error_description = sanitize_text_field( wp_unslash( $_GET['square_connect_error_description'] ) ); // phpcs:ignore
			$support_link                     = 'https://wpcharitable.com/support';

			switch ( $square_connect_error ) {
				case 'access_denied':
					switch ( $square_connect_error_description ) {
						case 'user_denied':
							$message = __( 'You have denied access to Square Connect.', 'charitable' );
							break;
						case 'missing_state_or_code':
							$message = sprintf(
								/* translators: %s: Support link URL */
								__( 'There has been an issue with your request. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
								$support_link
							);
							break;
						case 'missing_init_request':
							$message = sprintf(
								/* translators: %s: Support link URL */
								__( 'There has been an issue confirming your request. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
								$support_link
							);
							break;
						default:
							$message = sprintf(
								/* translators: %s: Support link URL */
								__( 'Your connect to Square has been canceled for an unknown reason. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
								$support_link
							);
							break;
					}
					break;
				case 'connect_error':
					switch ( $square_connect_error_description ) {
						case 'error_refreshing_token':
							$refresh_by_date = Charitable_Gateway_Square::get_instance()->get_personal_access_token_should_refresh( 'test' );
							$message         = sprintf(
								/* translators: %s: Refresh by date */
								__( 'There has been an issue refreshing your Square token. Token should be refreshed by: %1$s. Please try again or <a href="%2$s">contact support</a>.', 'charitable' ),
								$refresh_by_date,
								$support_link
							);
							break;
						case 'missing_response_fields':
						case 'credentials_not_saved':
							$message = sprintf(
								/* translators: %s: Support link URL */
								__( 'There has been an issue confirming your Square token. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
								$support_link
							);
							break;
						case 'response_error':
						case 'merchant_id_not_found':
						case 'save_credentials_error':
						case 'missing_state_or_code':
						case 'invalid_request':
						case 'error_getting_credentials':
						case 'missing_state_or_site_url':
						case 'error_getting_credentials':
						case 'missing_information_for_gateway_connect':
						case 'error_saving_init_request':
							$message = sprintf(
								/* translators: %s: Support link URL */
								__( 'There has been an issue communicating with Square. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
								$support_link
							);
							break;
						default:
							$message = sprintf(
								/* translators: %s: Support link URL */
								__( 'There has been an issue with your request. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
								$support_link
							);
							break;
					}
					break;
				default:
					$message = sprintf(
						/* translators: %s: Support link URL */
						__( 'There has been an issue with your request. Please try again or <a href="%s">contact support</a>.', 'charitable' ),
						$support_link
					);
			}

			charitable_get_admin_notices()->add_notice( $message, 'error', false, true );
		}

		/**
		 * Get the personal access refresh token.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to get the personal access refresh token for.
		 * @return string
		 */
		private function get_personal_access_refresh_token( $mode = 'test' ) {
			$square_settings = charitable_get_option( 'gateways_square', array() );

			if ( ! empty( $square_settings[ $mode ]['refresh_token'] ) ) {
				return $square_settings[ $mode ]['refresh_token'];
			}

			return '';
		}

		/**
		 * Add custom cron schedules.
		 *
		 * @since 1.8.7
		 *
		 * @param array $schedules Existing cron schedules.
		 * @return array
		 */
		public function add_cron_schedules( $schedules ) {
			$schedules['14days'] = array(
				'interval' => 14 * DAY_IN_SECONDS,
				'display'  => __( 'Every 14 days', 'charitable' ),
			);
			return $schedules;
		}

		/**
		 * Handle Square gateway switch.
		 *
		 * @since  1.8.7
		 * @version 1.8.9.1
		 *
		 * @return void
		 */
		public function handle_square_gateway_switch() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// Verify nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable_admin_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'charitable' ) ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// Check user permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
			}

			// Check if Square Legacy is active and Square Core is not active.
			if ( ! $this->is_active_gateway( 'square' ) || $this->is_active_gateway( 'square_core' ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gateway state for switching.', 'charitable' ) ) );
			}

			// Disable Square Legacy.
			$this->disable_gateway( 'square' );

			// Enable Square Core.
			$this->enable_gateway( 'square_core', false );

			// Get the redirect URL for Square Core settings.
			$redirect_url = add_query_arg(
				array(
					'page'  => 'charitable-settings',
					'tab'   => 'gateways',
					'group' => 'gateways_square_core',
				),
				admin_url( 'admin.php' )
			);

			wp_send_json_success( array( 'redirect_url' => $redirect_url ) );
		}

		/**
		 * Handle Square core to legacy switch.
		 *
		 * @since  1.8.7
		 * @version 1.8.9.1
		 *
		 * @return void
		 */
		public function handle_square_core_to_legacy_switch() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// Verify nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable_admin_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'charitable' ) ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// Check user permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
			}

			// Check if Square Core is active and Square Legacy is not active.
			if ( ! $this->is_active_gateway( 'square_core' ) || $this->is_active_gateway( 'square' ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid gateway state for switching.', 'charitable' ) ) );
			}

			if ( charitable_is_debug() ) {
				error_log( 'Charitable: Starting Square Core to Legacy gateway switch process.' ); // phpcs:ignore
			}

			// Disconnect Square Core connections for both live and test modes.
			$this->disconnect_square_core_connections();

			// Disable Square Core.
			$this->disable_gateway( 'square_core' );

			// Enable Square Legacy.
			$this->enable_gateway( 'square', false );

			if ( charitable_is_debug() ) {
				error_log( 'Charitable: Successfully switched from Square Core to Square Legacy gateway.' ); // phpcs:ignore
			}

			// Get the redirect URL for Square Legacy settings.
			$redirect_url = add_query_arg(
				array(
					'page'  => 'charitable-settings',
					'tab'   => 'gateways',
					'group' => 'gateways_square',
				),
				admin_url( 'admin.php' )
			);

			wp_send_json_success( array( 'redirect_url' => $redirect_url ) );
		}

		/**
		 * Disconnect Square Core connections for both live and test modes.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		private function disconnect_square_core_connections() {

			if ( charitable_is_debug() ) {
				error_log( 'Charitable: Starting Square Core connection disconnection process.' ); // phpcs:ignore
			}

			// Check if Charitable_Square_Connection class exists.
			if ( ! class_exists( 'Charitable_Square_Connection' ) ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Charitable_Square_Connection class not found. Skipping disconnect process.' ); // phpcs:ignore
				}
				return;
			}

			// Check and disconnect live mode connection.
			$live_connection = Charitable_Square_Connection::get( 'live' );
			if ( $live_connection && $live_connection->is_configured() ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Found active Square Core live connection. Attempting to disconnect.' ); // phpcs:ignore
				}
				$this->disconnect_square_core_mode( $live_connection, 'live' );
			} elseif ( charitable_is_debug() ) {
					error_log( 'Charitable: No active Square Core live connection found.' ); // phpcs:ignore

			}

			// Check and disconnect test mode connection.
			$test_connection = Charitable_Square_Connection::get( 'test' );
			if ( $test_connection && $test_connection->is_configured() ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Found active Square Core test connection. Attempting to disconnect.' ); // phpcs:ignore
				}
				$this->disconnect_square_core_mode( $test_connection, 'test' );
			} elseif ( charitable_is_debug() ) {
					error_log( 'Charitable: No active Square Core test connection found.' ); // phpcs:ignore

			}

			if ( charitable_is_debug() ) {
				error_log( 'Charitable: Completed Square Core connection disconnection process.' ); // phpcs:ignore
			}
		}

		/**
		 * Disconnect Square Core connection for a specific mode.
		 *
		 * @since 1.8.7
		 *
		 * @param Charitable_Square_Connection $connection The connection object.
		 * @param string                       $mode       The mode (live/test).
		 * @return void
		 */
		private function disconnect_square_core_mode( $connection, $mode ) {

			if ( charitable_is_debug() ) {
				error_log( 'Charitable: Disconnecting Square Core connection for mode: ' . $mode ); // phpcs:ignore
			}

			// Check if Charitable_Square_Connect class exists.
			if ( ! class_exists( 'Charitable_Square_Connect' ) ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Charitable_Square_Connect class not found. Cannot disconnect mode: ' . $mode ); // phpcs:ignore
				}
				return;
			}

			try {
				// Get the Square Connect instance.
				$square_connect = Charitable_Square_Connect::get_instance();

				// Use reflection to access the private handle_disconnect method.
				$reflection = new ReflectionClass( $square_connect );
				$method     = $reflection->getMethod( 'handle_disconnect' );
				$method->setAccessible( true );

				// Set the live_mode parameter for the disconnect process.
				$_GET['live_mode'] = ( $mode === 'live' ) ? 1 : 0;

				// Call the disconnect method without redirect.
				$method->invoke( $square_connect, false );

				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Successfully disconnected Square Core connection for mode: ' . $mode ); // phpcs:ignore
				}
			} catch ( Exception $e ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Error disconnecting Square Core connection for mode ' . $mode . ': ' . $e->getMessage() ); // phpcs:ignore
				}
			}
		}

		/**
		 * Dequeue Square Legacy scripts if Square Legacy is not active.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public function maybe_dequeue_square_legacy_scripts() {
			// Only run on frontend.
			if ( is_admin() ) {
				return;
			}

			// Debug: Log the status checks.
			if ( charitable_is_debug( 'square' ) ) {
				error_log( '[Charitable Square] Script dequeue check - Legacy mode: ' . ( charitable_square_legacy_mode() ? 'true' : 'false' ) ); // phpcs:ignore
				error_log( '[Charitable Square] Script dequeue check - Square gateway active: ' . ( $this->is_active_gateway( 'square' ) ? 'true' : 'false' ) ); // phpcs:ignore
			}

			// Check if Square Legacy is active.
			if ( ! charitable_square_legacy_mode() || ! $this->is_active_gateway( 'square' ) ) {
				// Dequeue Square Legacy scripts and styles.
				wp_dequeue_script( 'charitable-square' );
				wp_dequeue_script( 'square' );
				wp_dequeue_style( 'charitable-square' );

				// Deregister scripts to prevent them from being loaded.
				wp_deregister_script( 'charitable-square' );
				wp_deregister_script( 'square' );
				wp_deregister_style( 'charitable-square' );

				if ( charitable_is_debug( 'square' ) ) {
					error_log( '[Charitable Square] Dequeued Square Legacy scripts - Square Legacy is not active' ); // phpcs:ignore
				}
			} elseif ( charitable_is_debug( 'square' ) ) {
					error_log( '[Charitable Square] Square Legacy scripts will remain loaded - Square Legacy is active' ); // phpcs:ignore

			}
		}

		/**
		 * Check if charitable-square plugin is active.
		 *
		 * @since 1.8.7
		 *
		 * @return boolean
		 */
		private function is_square_addon_active() {
			return is_plugin_active( 'charitable-square/charitable-square.php' );
		}

		/**
		 * Force cleanup of 'square' gateway if charitable-square plugin is not active.
		 * Runs once per 24 hours (adjustable via filter) to avoid constant database checks.
		 *
		 * @since 1.8.7
		 *
		 * @param array $active_gateways The current active gateways array.
		 * @return void
		 */
		private function maybe_force_cleanup_square_gateway( $active_gateways ) {
			// Check if we need to run cleanup (once per 24 hours).
			$transient_name = 'charitable_square_cleanup_last_run';
			$check_interval = apply_filters( 'charitable_square_cleanup_check_interval', 24 * HOUR_IN_SECONDS );

			if ( get_transient( $transient_name ) ) {
				return;
			}

			// Check if 'square' gateway exists and charitable-square plugin is not active.
			if ( isset( $active_gateways['square'] ) && ! $this->is_square_addon_active() ) {
				try {
					// Get current settings.
					$settings = get_option( 'charitable_settings', array() );

					if ( ! empty( $settings['active_gateways']['square'] ) ) {
						// Remove 'square' gateway from active gateways.
						unset( $settings['active_gateways']['square'] );

						// Update default gateway if it was set to 'square'.
						if ( isset( $settings['default_gateway'] ) && 'square' === $settings['default_gateway'] ) {
							$settings['default_gateway'] = '';
						}

						// Remove the option that warns users about incorrect square settings.
						delete_option( 'charitable_square_mode_connection_warning' );

						// Save updated settings.
						$updated = update_option( 'charitable_settings', $settings );

						if ( $updated ) {
							if ( charitable_is_debug( 'square' ) ) {
								error_log( '[Charitable Square] Successfully removed legacy square gateway from active gateways' ); // phpcs:ignore
							}
						} else {
							if ( charitable_is_debug( 'square' ) ) {
								error_log( '[Charitable Square] Failed to update settings when removing legacy square gateway' ); // phpcs:ignore
							}
						}
					}
				} catch ( Exception $e ) {
					if ( charitable_is_debug( 'square' ) ) {
						error_log( '[Charitable Square] Error during square gateway cleanup: ' . $e->getMessage() ); // phpcs:ignore
					}
				}
			}

			// Set transient to prevent running again for the specified interval.
			set_transient( $transient_name, time(), $check_interval );
		}
	}

endif;
