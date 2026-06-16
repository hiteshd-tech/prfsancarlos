<?php
/**
 * Square Payment Gateway class.
 *
 * @package   Charitable/Classes/Charitable_Gateway_Square
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 * @version   1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway_Square' ) ) :

	/**
	 * Square Gateway.
	 *
	 * @since 1.8.7
	 */
	class Charitable_Gateway_Square extends Charitable_Gateway {

		/** The gateway ID. */
		const ID = 'square_core';

		/** The Square API version we are using. */
		const SQUARE_API_VERSION = '2024-04-25';

		/**
		 * Square Apps URL.
		 *
		 * @since 1.8.7
		 */
		public const SQUARE_APPS_URL = 'https://developer.squareup.com/apps';

		/**
		 * Gateway badge.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		protected $badge = '';

		/**
		 * An array of options for the Square location when in test mode.
		 *
		 * @since 1.8.7
		 *
		 * @var   array
		 */
		private $test_location_options;

		/**
		 * An array of options for the Square location when in live mode.
		 *
		 * @since 1.8.7
		 *
		 * @var   array
		 */
		private $live_location_options;

		/**
		 * Permissions for the Square API.
		 *
		 * @since 1.8.7
		 *
		 * @var   string
		 */
		private $permissions;

		/**
		 * Whether the Square account is connected or disconnected.
		 *
		 * @since 1.8.7
		 *
		 * @var bool
		 */
		private $is_connected;

		/**
		 * Whether the payment has been processed.
		 *
		 * @since 1.8.7
		 *
		 * @var bool
		 */
		protected $is_payment_processed = false;

		/**
		 * Main class that communicates with the Square API.
		 *
		 * @since 1.8.7
		 *
		 * @var Api
		 */
		protected $api;

		/**
		 * Processing errors.
		 *
		 * @since 1.8.7
		 *
		 * @var array
		 */
		protected $errors;

		/**
		 * Square Connect.
		 *
		 * @since 1.8.7
		 *
		 * @var Connect
		 */
		protected $connect;

		/**
		 * Connection.
		 *
		 * @since 1.8.7
		 *
		 * @var Charitable_Square_Connection
		 */
		protected $connection;

		/**
		 * Instance of the class.
		 *
		 * @since 1.8.7
		 *
		 * @var   Charitable_Gateway_Square
		 */
		private static $instance;

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @since 1.8.7
		 */
		public function __construct() {
			/**
			 * Filter the gateway name
			 *
			 * @since 1.8.7
			 *
			 * @param string $name Gateway name.
			 */
			$this->name = apply_filters( 'charitable_gateway_square_name', __( 'Square', 'charitable' ) );

			$this->defaults = array(
				'label' => __( 'Square', 'charitable' ),
			);

			$this->badge = __( 'Recommended', 'charitable' );

			$this->recommended = true;

			$this->supports = array(
				'1.3.0',
				'credit-card',
				'recurring',
				'refunds',
			);

			$this->connect = ( new Charitable_Square_Connect() )->init();

			$this->connection   = Charitable_Square_Connection::get( charitable_get_option( 'test_mode' ) ? 'test' : 'live' );
			$this->is_connected = $this->is_square_connected( charitable_get_option( 'test_mode' ) ? 'test' : 'live' );

			// Add action for handling redirects.
			add_action( 'admin_init', array( $this, 'handle_redirect' ) );

			// Add action for checking token status.
			add_action( 'admin_init', array( $this, 'check_token_status' ), 10 );
		}

		/**
		 * Check the locations.
		 *
		 * @since 1.8.7
		 */
		public function check_locations() {

			// Only check this if page=charitable-settings&tab=gateways&group=gateways_square_core.
			if ( ! isset( $_GET['page'] ) || 'charitable-settings' !== $_GET['page'] || ! isset( $_GET['tab'] ) || 'gateways' !== $_GET['tab'] || ! isset( $_GET['group'] ) || 'gateways_square_core' !== $_GET['group'] ) { //phpcs:ignore
				return;
			}

			// Check if the Square Connect class exists.
			if ( ! class_exists( 'Charitable_Square_Connect' ) ) {
				return;
			}
			// Check if the Square API class exists.
			$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';

			$locations = $this->get_location_options( $mode );
		}

		/**
		 * Handle redirects for Square gateway settings.
		 *
		 * @since 1.8.7
		 */
		public function handle_redirect() {
			if ( isset( $_GET['redirect'] ) && isset( $_GET['page'] ) && 'charitable-settings' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$mode    = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
				$connect = new Charitable_Square_Connect();
				$connect->prepare_locations( $mode );

				wp_safe_redirect(
					add_query_arg(
						array(
							'page'  => 'charitable-settings',
							'tab'   => 'gateways',
							'group' => 'gateways_square_core',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}
		}

		/**
		 * Register the Square payment gateway class.
		 *
		 * @since 1.8.7
		 *
		 * @param  string[] $gateways The list of registered gateways.
		 * @return string[]
		 */
		public static function register_gateway( $gateways ) {
			// Check if PHP version is compatible with Square SDK.
			if ( class_exists( 'Charitable_Square_Compatibility' ) && ! Charitable_Square_Compatibility::is_compatible() ) {
				// Don't register Square gateway if PHP version is incompatible.
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( '[Charitable Square Core] Gateway not registered - PHP version incompatible' );
					// phpcs:enable
				}
				return $gateways;
			}

			// Only register if not already registered (prevents duplicate registration).
			if ( ! isset( $gateways['square_core'] ) ) {
				$gateways['square_core'] = 'Charitable_Gateway_Square';

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( '[Charitable Square Core] Gateway registered successfully' );
					// phpcs:enable
				}
			}

			return $gateways;
		}

		/**
		 * Get the API object.
		 *
		 * @since  1.8.7
		 *
		 * @param  boolean|null $test_mode Whether to explicitly get the test or live key.
		 * @param  string|null  $live_access_token The live access token.
		 * @param  string|null  $test_access_token The test access token.
		 * @return \Charitable_Square_API
		 */
		public static function api( $test_mode = null, $live_access_token = null, $test_access_token = null ) {
			if ( is_null( $test_mode ) ) {
				return null;
			}
			return new \Charitable_Square_API( $test_mode, $live_access_token, $test_access_token );
		}

		/**
		 * This processes a request from the user to manually renew the Square token.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public function square_renew_token() {
			$mode = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : 'test'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'sandbox' === $mode ) {
				$mode = 'test';
			}

			if ( empty( $_GET['action'] ) || 'square_renew_token' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			// Get current live and/or access tokens.
			$live_access_token = charitable_square_get_access_token( 'live' );
			$test_access_token = charitable_square_get_access_token( 'test' );

			// Get the refresh token.
			$refresh_token = $this->get_personal_access_refresh_token( $mode );

			if ( empty( $refresh_token ) ) {
				wp_die( esc_html__( 'No refresh token found.', 'charitable' ) );
				return;
			}

			// Renew the token using the API.
			$response = $this->api( $mode, $live_access_token, $test_access_token )->renew_token( $refresh_token );

			if ( false === $response ) {
				wp_die( esc_html__( 'Failed to renew token. Please try again.', 'charitable' ) );
				return;
			}

			// Update the stored tokens.
			$settings = get_option( 'charitable_settings' );
			$settings['gateways_square'][ $mode ]['access_token'] = $response['access_token'];

			// Update refresh token if a new one was provided.
			if ( isset( $response['refresh_token'] ) ) {
				$settings[ $mode ]['refresh_token'] = $response['refresh_token'];
			}

			update_option( 'charitable_settings', $settings );

			// Redirect back to settings page with success message.
			wp_safe_redirect( $this->get_settings_page_url() );

			exit;
		}

		/**
		 * Register gateway settings.
		 *
		 * @since  1.8.7
		 *
		 * @param  array[] $settings Default array of settings for the gateway.
		 * @return array[]
		 */
		public function gateway_settings( $settings ) {

			if ( $this->connect === null ) {
				$this->connect = new Charitable_Square_Connect();
			}

			$connected_status_css = $this->is_connected ? 'square-connected' : 'square-disconnected';

			$settings = array_merge(
				$settings,
				array(
					'square_connection_status'  => array(
						'type'     => 'content',
						'title'    => __( 'Connection Status', 'charitable' ),
						'content'  => $this->get_connection_status_content( $this->connection, charitable_get_option( 'test_mode' ) ? 'test' : 'live' ),
						'priority' => 10,
						'class'    => 'square-connection-status square-non-legacy',
					),

					'square_business_locations' => array(
						'type'     => 'content',
						'title'    => __( 'Business Locations', 'charitable' ),
						'content'  => $this->get_business_locations_content(),
						'class'    => 'square-business-locations square-non-legacy ' . $connected_status_css,
						'priority' => 50,
					),

					'square_webhooks'           => array(
						'type'     => 'content',
						'title'    => __( 'Enable Webhooks', 'charitable' ),
						'content'  => $this->get_webhooks_content(),
						'priority' => 60,
						'class'    => 'square-webhooks-content square-non-legacy',
					),
				)
			);

			$settings = apply_filters( 'charitable_square_gateway_settings', $settings );

			return $settings;
		}

		/**
		 * Show the link to the documentation about the Webhooks ID.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode (e.g. 'live' or 'sandbox').
		 *
		 * @return string
		 */
		private function get_webhooks_id_desc( string $mode ): string {

			$modes = array(
				'live'    => __( 'Live Mode Endpoint Subscription ID', 'charitable' ),
				'sandbox' => __( 'Test Mode Endpoint Subscription ID', 'charitable' ),
			);

			return sprintf(
				wp_kses( /* translators: %1$s - Live Mode Endpoint ID or Test Mode Endpoint ID. %2$s - Square Dashboard Webhooks Settings URL. */
					__( 'Retrieve your %1$s from your <a href="%2$s" target="_blank" rel="noopener noreferrer">Square webhook settings</a>. Select the endpoint, then click Copy button.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				$modes[ $mode ],
				esc_url( self::SQUARE_APPS_URL )
			);
		}

		/**
		 * Show the link to the documentation about the Webhook Signature Key.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode (e.g. 'live' or 'sandbox').
		 *
		 * @return string
		 */
		private function get_webhooks_secret_desc( string $mode ): string {

			$modes = array(
				'live'    => __( 'Live Mode Signature Key', 'charitable' ),
				'sandbox' => __( 'Test Mode Signature Key', 'charitable' ),
			);

			return sprintf(
				wp_kses( /* translators: %1$s - Live Mode Signing Secret or Test Mode Signing Secret. %2$s - Square Dashboard Webhooks Settings URL. */
					__( 'Retrieve your %1$s from your <a href="%2$s" target="_blank" rel="noopener noreferrer">Square webhook settings</a>. Select the endpoint, then click Reveal.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				$modes[ $mode ],
				esc_url( self::SQUARE_APPS_URL )
			);
		}

		/**
		 * Determine if Square is connected.
		 *
		 * @since  1.8.7
		 *
		 * @return int
		 */
		public function maybe_square_connected() {
			return $this->check_keys_exist( 'live' ) || $this->check_keys_exist( 'test' );
		}

		/**
		 * Determine if Square is connected.
		 *
		 * @since  1.8.7
		 *
		 * @param string $mode The mode to check for keys. Should be 'test' or 'live'. If it's sandbox, rename it to 'test'.
		 * @return int
		 */
		private function is_square_connected( string $mode = 'test' ) {

			if ( ! $this->connection ) {
				return false;
			}

			// If the connection is invalid, return false.
			if ( ! $this->connection->is_valid() ) {
				return false;
			}

			return true;
		}

		/**
		 * Retrieve a Connection Status setting content.
		 *
		 * @since 1.8.7
		 *
		 * @param Charitable_Square_Connection $connection Connection data.
		 * @param string                       $mode       Square mode.
		 *
		 * @return string
		 */
		private function get_connection_status_content( $connection = null, $mode = 'test' ): string {

			if ( ! $this->is_square_connected() ) {
				return $this->get_disconnected_status_content( $mode );
			}

			$content = $this->get_disabled_status_content( $connection );

			if ( ! empty( $content ) ) {
				return $content;
			}

			return $this->get_enabled_status_content( $connection );
		}

		/**
		 * Retrieve a Disconnected Status setting content.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_disconnected_status_content( string $mode ): string {

			return $this->get_fees_content() .
				$this->get_connect_button( $mode, false ) .
				'<p class="desc">' .
				sprintf(
					wp_kses( /* translators: %s - Charitable.com Square documentation article URL. */
						__( 'Securely connect to Square with just a few clicks to begin accepting payments! <a href="%s" target="_blank" rel="noopener noreferrer" class="charitable-learn-more">Learn More</a>', 'charitable' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
								'class'  => array(),
							),
						)
					),
					esc_url( charitable_utm_link( 'https://wpcharitable.com/docs/how-to-install-and-use-the-square-addon-with-charitable/#connect-square', 'Settings - Payments', 'Square Learn More' ) )
				) .
				'</p>';
		}

		/**
		 * Retrieve setting content when a connection is disabled.
		 *
		 * @since 1.8.7
		 *
		 * @param Charitable_Square_Connection $connection Connection data.
		 *
		 * @return string
		 */
		private function get_disabled_status_content( Charitable_Square_Connection $connection ): string {

			if ( ! $connection->is_configured() ) {
				return $this->get_missing_status_content( $connection->get_mode() );
			}

			if ( ! $connection->is_valid() ) {
				return $this->get_invalid_status_content( $connection->get_mode() );
			}

			return '';
		}

		/**
		 * Retrieve a connection is missing status content.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_missing_status_content( string $mode ): string {

			return $this->get_fees_content() . '<div class="charitable-square-connected">' . $this->get_error_icon() . esc_html__( 'Your connection is missing required data. You must reconnect your Square account.', 'charitable' ) . $this->get_disconnect_button( $mode ) . '</div>';
		}

		/**
		 * Retrieve a connection invalid status content.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_invalid_status_content( string $mode ): string {

			return $this->get_fees_content() . '<div class="charitable-square-connected">' . $this->get_error_icon() . $this->get_connected_status_content( $mode ) .
			'<p>' . esc_html__( 'It appears your connection may be invalid. You must refresh tokens or reconnect your account.', 'charitable' ) . '</p>' .
			'<p>' . $this->get_refresh_button( $mode ) . $this->get_disconnect_button( $mode, false ) . '</p></div>';
		}

		/**
		 * Retrieve a connection expired status content.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_expired_status_content( string $mode ): string {

			return $this->get_fees_content() . '<div class="charitable-square-connected">' . $this->get_error_icon() . $this->get_connected_status_content( $mode ) .
			'<p>' . esc_html__( 'Your connection is expired. You must refresh tokens or reconnect your account.', 'charitable' ) . '</p>' .
			'<p>' . $this->get_refresh_button( $mode ) . $this->get_disconnect_button( $mode, false ) . '</p></div>';
		}

		/**
		 * Retrieve a currency mismatch status content.
		 * Might be a false flag.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_currency_mismatch_status_content( string $mode ): string {

			return $this->get_fees_content() . '<div class="charitable-square-connected">' . $this->get_error_icon() . $this->get_connected_status_content( $mode ) .
			'<span class="charitable-square-notice-error"><p>' . esc_html__( 'Charitable currency and Business Location currency are not matched.', 'charitable' ) . '</p></span></div>' .
			$this->get_disconnect_button( $mode );
		}

		/**
		 * Retrieve a currency missing location currency status content.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_currency_missing_location_currency_content( string $mode ): string {

			return $this->get_fees_content() . '<div class="charitable-square-connected">' . $this->get_error_icon() . $this->get_connected_status_content( $mode ) .
			'<span class="charitable-square-notice-error"><p>' . esc_html__( 'Location missing or not valid with the same currency as your Charitable setting. Create or update your location in Square Dashboard.', 'charitable' ) . '</p></span></div>' .
			$this->get_disconnect_button( $mode );
		}

		/**
		 * Retrieve setting content when a connection is enabled.
		 *
		 * @since 1.8.7
		 *
		 * @param Charitable_Square_Connection $connection Connection data.
		 *
		 * @return string
		 */
		private function get_enabled_status_content( Charitable_Square_Connection $connection ): string {

			$fees_html = $this->get_fees_content();

			$location_currency = get_option( 'charitable_square_location_currency_' . $connection->get_mode() );
			$currency_matched  = $location_currency === strtoupper( charitable_get_currency() );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_enabled_status_content called' );
				error_log( 'location_currency: ' . $location_currency );
				error_log( 'charitable_currency: ' . strtoupper( charitable_get_currency() ) );
				error_log( 'currency_matched: ' . ( $currency_matched ? 'true' : 'false' ) );
				// phpcs:enable
			}

			if ( ! $location_currency ) {
				// If the location currency is not set, we assume it's not matched.
				return $this->get_currency_missing_location_currency_content( $connection->get_mode() );
			}

			if ( $connection->is_expired() ) {
				return $this->get_expired_status_content( $connection->get_mode() );
			}

			if ( ! $currency_matched ) {
				return $this->get_currency_mismatch_status_content( $connection->get_mode() );
			}

			return $fees_html . '<div class="charitable-square-connected"><span class="charitable-success-icon"></span>' . $this->get_connected_status_content( $connection->get_mode() ) . $this->get_disconnect_button( $connection->get_mode() ) . '</div>';
		}

		/**
		 * Retrieve the fees content.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_fees_content() {

			if ( ! charitable_is_pro() ) {
				return '<div class="charitable-inline-notice info">
						<p>
						<strong>' . esc_html__( 'Pay as you go pricing:', 'charitable' ) . '</strong> ' .
						sprintf(
							/* translators: %1$s: opening link tag, %2$s: closing link tag */
							esc_html__( '3%% per transaction + Square fees. %1$sUpgrade to Pro%2$s for no added fees and priority support.', 'charitable' ),
							'<a target="_blank" href="' . esc_url( charitable_pro_upgrade_url( 'gateway-square' ) ) . '">',
							'</a>'
						) . '</p>
					</div>';
			}

			return '';
		}

		/**
		 * Retrieve a Connected Status setting content.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_connected_status_content( string $mode ): string {
			$connection = Charitable_Square_Connection::get( $mode );
			$content    = '';

			if ( $connection && $connection->is_configured() ) {
				$api           = new Charitable_Square_API( $connection );
				$merchant_name = $api->get_merchant_name();

				$content .= sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %s: Merchant name */
						esc_html__( 'Connected to Square as %s.', 'charitable' ),
						'<strong>' . esc_html( $merchant_name ?: $connection->get_merchant_id() ) . '</strong>' //phpcs:ignore
					)
				);

				// Check if token is within 14 days of expiration.
				$token_refresh_date = $this->get_personal_access_token_refresh_date( $mode, true );
				if ( $token_refresh_date ) {
					$days_until_expiration = ceil( ( $token_refresh_date - time() ) / DAY_IN_SECONDS );
					if ( $days_until_expiration <= 14 ) {
						$content .= sprintf(
							'<p class="charitable-square-expiration-notice">%s</p><p>%s</p>',
							sprintf(
								/* translators: %d: Number of days until expiration */
								esc_html__( 'Your connection will expire in %d days.', 'charitable' ),
								abs( $days_until_expiration )
							),
							$this->get_refresh_button( $mode )
						);
					}
				}
			}

			return $content;
		}

		/**
		 * Retrieve the connected account data.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return array
		 */
		public function get_connected_account( $mode ) {
			$account_data = charitable_get_option( array( 'gateways_square', $mode ) );
			return $account_data;
		}

		/**
		 * Retrieve the Connect button.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 * @param bool   $wrap Optional. Wrap a button HTML element or not.
		 *
		 * @return string
		 */
		private function get_connect_button( string $mode, bool $wrap = true ): string {

			$button = sprintf(
				$this->get_square_connect_button_html( $mode ),
				esc_url( $this->connect->get_connect_url( $mode ) ),
				esc_attr__( 'Connect Square account', 'charitable' ),
				esc_html__( 'Connect with Square', 'charitable' )
			);

			return $wrap ? '<p>' . $button . '</p>' : $button;
		}

		/**
		 * Generate the Square Connect button.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The api mode (test|live).
		 *
		 * @return string
		 */
		public function get_square_connect_button_html( $mode = 'test' ) {

			$html = '';

			if ( 'live' === $mode ) {

				$html .= '<a href="%s" id="charitable-square-connect-btn" class="charitable-square-btn charitable-square-live-btn" title="' . esc_attr__( 'Connect your site with Square account for easy onboarding.', 'charitable' ) . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z"></path><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z"></path></svg><span>' . esc_html__( 'Connect to Square Account', 'charitable' ) . '</span></a>';

			} elseif ( 'test' === $mode ) {

				$html .= '<a href="%s" id="charitable-square-connect-sandbox-btn" class="charitable-square-btn charitable-square-sandbox-btn" title="' . esc_attr__( 'Connect your site with Square account for easy onboarding.', 'charitable' ) . '"><svg version="1.1" id="Layer_1" xmlns:x="&ns_extend;" xmlns:i="&ns_ai;" xmlns:graph="&ns_graphs;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 994.6 995.7" style="enable-background:new 0 0 994.6 995 "xml:space="preserve"><style type="text/css">.st0{fill:#1A1A1A;}</style><switch><foreignObject requiredExtensions="&ns_ai;" x="0" y="0" width="1" height="1"><i:aipgfRef xlink:href="#adobe_illustrator_pgf"></i:aipgfRef></foreignObject><g i:extraneous="self"><path class="st0" d="M828.4,0H166.2C74.4,0,0,74.4,0,166.2v662.2c0,91.8,74.4,166.2,166.2,166.2h662.2c91.8,0,166.2-74.4,166.2-166.2V166.2C994.6,74.4,920.2,0,828.4,0z M813.8,761.3c0,29-23.5,52.5-52.5,52.5h-528c-29,0-52.5-23.5-52.5-52.5v-528c0-29,23.5-52.5,52.5-52.5h528c29,0,52.5,23.5,52.5,52.5V761.3z M391.8,632.3c-16.7,0-30.1-13.5-30.1-30.2V391.3c0-16.7,13.4-30.3,30.1-30.3h211.1c16.6,0,30.1,13.5,30.1,30.3V602c0,16.7-13.5,30.2-30.1,30.2H391.8z"/></g></switch></svg><span>' . esc_html__( 'Connect to Square Account (Sandbox)', 'charitable' ) . '</span></a>';

			}

			return $html;
		}

		/**
		 * Retrieve Business Location options.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_business_locations_content() {

			$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';

			// We only show content if the connection is valid.
			$connection = Charitable_Square_Connection::get( $mode );
			if ( ! $connection ) {
				return '';
			}

			return $this->get_location_options_html( $mode );
		}


		/**
		 * Retrieve Business Location options.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return array
		 */
		private function get_location_options_html( string $mode ) {

			$locations = $this->get_location_options( $mode );

			$options = array();

			ob_start();

			if ( empty( $locations ) ) {
				?>
				<div class="charitable-help">
					<?php esc_html_e( 'No locations were found', 'charitable' ); ?>
				</div>
				<?php
				return ob_get_clean();
			}

			foreach ( $locations as $location_id => $location_name ) {
				$options[ $location_id ] = $location_name;
			}

			ob_start();

			$location_id = get_option( 'charitable_square_location_id_' . $mode );

			$attr = array(
				'id'    => 'charitable_settings_gateways_square_' . $mode . '_location',
				'css'   => '',
				'class' => '',
			);

			?>
			<select
				name="charitable_settings[gateways_square][<?php echo esc_attr( $mode ); ?>][location_id]"
				id="<?php echo esc_attr( $attr['id'] ); ?>"
				style="<?php echo esc_attr( $attr['css'] ); ?>"
				class="<?php echo esc_attr( $attr['class'] ); ?>"
			>
				<?php
				if ( ! empty( $options ) ) {
					foreach ( $options as $key => $value ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"
							<?php selected( $key, $location_id ); ?>">
							<?php echo esc_html( $value ); ?>
						</option>
						<?php
					}
				}
				?>
			</select>

			<?php
			/*
			<a href="<?php echo esc_url_raw( $refresh_url ); ?>" class="button">
				<?php esc_html_e( 'Refresh', 'charitable' ); ?>
			</a>
			*/
			?>
			<div class="charitable-help">
				<?php esc_html_e( 'Only active locations that support credit card processing in Square can be chosen.', 'charitable' ); ?>
			</div>

			<?php
			return ob_get_clean();
		}

		/**
		 * Retrieve Business Location options.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return array
		 */
		private function get_location_options( string $mode ): array {

			$locations = $this->connect->get_connected_locations( $mode );

			return ! empty( $locations ) ? array_column( $locations, 'name', 'id' ) : array( '' => esc_html__( 'No locations were found', 'charitable' ) );
		}

		/**
		 * Retrieve the webhooks content.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		private function get_webhooks_content() {

			// Check if Square is connected.
			if ( ! $this->is_square_connected( charitable_get_option( 'test_mode' ) ? 'test' : 'live' ) ) {
				return sprintf(
					'<div class="charitable-webhook-not-connected">
						<p>%s</p>
					</div>',
					esc_html__( 'Connect your Square account to enable webhook functionality.', 'charitable' )
				);
			}

			// Always use query parameter format (for when permalinks are not active).
			$webhook_url = add_query_arg( array( 'charitable-listener' => self::ID ), home_url() );

			$message = sprintf(
				'<div class="charitable-webhook-manual-setup">
					<div class="charitable-help">
						<p><strong>%s</strong></p>
						<p>%s</p>
					</div>
					<div class="charitable-webhook-url">
						<label for="charitable-webhook-url">%s:</label>
						<div class="charitable-webhook-url-input-wrapper">
							<input type="text" id="charitable-webhook-url" value="%s" readonly />
							<button type="button" class="charitable-copy-icon" data-clipboard-target="#charitable-webhook-url" title="%s">
								<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="copy" class="svg-inline--fa fa-copy fa-xs" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
									<path fill="currentColor" d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l140.1 0L400 115.9 400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-204.1c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-32-48 0 0 32c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l32 0 0-48-32 0z"></path>
								</svg>
							</button>
						</div>
					</div>
					<div class="charitable-help">
						<strong>%s</strong><br>
						%s
						<ul style="margin: 8px 0 0 20px;">
							<li><code>invoice.payment_made</code></li>
							<li><code>oauth.authorization.revoked</code></li>
							<li><code>payment.created</code></li>
							<li><code>payment.updated</code></li>
							<li><code>refund.created</code></li>
						</ul>
						<p style="margin-top: 20px;">%s</p>
					</div>
				</div>',
				esc_html__( 'Manual Webhook Setup Required', 'charitable' ),
				esc_html__( 'To enable webhook functionality, you need to manually add a webhook to your Square dashboard using the URL below.', 'charitable' ),
				esc_html__( 'Webhook URL to add in Square Dashboard', 'charitable' ),
				esc_url( $webhook_url ),
				esc_attr__( 'Copy URL', 'charitable' ),
				esc_html__( 'Required webhook subscriptions:', 'charitable' ),
				esc_html__( 'When creating the webhook in Square, subscribe to these events:', 'charitable' ),
				sprintf(
					wp_kses(
						/* translators: %s - charitable.com URL for Square webhooks documentation. */
						__( 'Please see <a href="%s" target="_blank" rel="noopener noreferrer">our documentation on Square webhooks</a> for detailed instructions on how to add this webhook to your Square dashboard.', 'charitable' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/setting-up-square-webhooks/', 'Settings - Payments', 'Square Webhooks Documentation' ) )
				),
			);

			return $message;
		}

		/**
		 * Get the webhooks endpoint content.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		private function get_webhooks_endpoint_content() {

			ob_start();

			// Always use query parameter format (for when permalinks are not active).
			$webhook_url = add_query_arg( array( 'charitable-listener' => self::ID ), home_url() );

			echo '<input type="text" disabled style="width: 100%;" value="' . esc_url( $webhook_url ) . '" />';

			$help = sprintf(
				wp_kses( /* translators: %s - charitable.com URL for Square webhooks documentation. */
					__( 'Ensure an endpoint with the above URL is present in the <a href="%s" target="_blank" rel="noopener noreferrer">Square webhook settings</a>.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				esc_url( 'https://developer.squareup.com/apps', 'Settings - Payments', 'Square Webhooks Documentation' )
			);

			echo '<div class="charitable-help">' . wp_kses_post( $help ) . '</div>'; //phpcs:ignore

			return ob_get_clean();
		}

		/**
		 * Get the "Connect Webhooks" button.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		private function get_webhooks_connect_button() {

			// Assume webhooks are configured manually in Square dashboard.
			$is_connected = true;

			if ( $is_connected ) {
				return sprintf(
					'<div><span class="%s"></span>%s</div>',
					esc_attr( 'charitable-success-icon' ),
					esc_html__( 'Webhooks are configured manually in Square dashboard.', 'charitable' )
				);
			}

			$button = sprintf(
				'<button class="charitable-btn charitable-btn-md charitable-btn-connect-webhooks" type="button" id="charitable-setting-square-webhooks-connect" title="%1$s">%2$s</button>',
				esc_attr__( 'Press here to see the further instructions.', 'charitable' ),
				esc_html__( 'Connect Webhooks', 'charitable' )
			);

			$description = sprintf(
				'<p class="desc">%s</p>',
				wp_kses(
				/* translators: %s - charitable.com URL for Square webhooks documentation. */
					__( 'To start using webhooks, please register a webhook route inside our application. You can do this by pressing the button above. Please see <a href="%1$s" target="_blank">our documentation on Square webhooks</a> for full details.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				)
			);

			$description = sprintf( $description, esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/setting-up-square-webhooks/', 'Settings - Payments', 'Square Webhooks Documentation' ) ) );

			return $button . $description;
		}

		/**
		 * Get the Square logo (SVG).
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_logo() {
			return '<svg width="88" height="22" viewBox="0 0 88 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.6761 1.54849e-07H18.3239C19.299 -0.000282679 20.2344 0.386894 20.924 1.07633C21.6136 1.76576 22.0011 2.70095 22.0011 3.6761V18.3228C22.0011 20.3537 20.3547 22 18.3239 22H3.6761C1.64567 21.9994 0 20.3533 0 18.3228V3.6761C0 1.64584 1.64584 1.54849e-07 3.6761 1.54849e-07ZM16.8434 18.0005C17.4842 18.0005 18.0037 17.481 18.0037 16.8402L18.0005 5.16083C18.0005 4.52004 17.481 4.00058 16.8402 4.00058H5.1619C4.854 4.00058 4.55872 4.12297 4.3411 4.34079C4.12348 4.55861 4.00137 4.854 4.00165 5.1619V16.8402C4.00165 17.481 4.52111 18.0005 5.1619 18.0005H16.8434Z" fill="#3E4348"/><path d="M8.66615 13.9828C8.30039 13.9799 8.00543 13.6826 8.00544 13.3168V8.65442C8.00459 8.47722 8.07438 8.30698 8.19939 8.18138C8.32439 8.05578 8.49429 7.98517 8.67149 7.98517H13.3403C13.5174 7.98545 13.6871 8.05615 13.8121 8.18169C13.937 8.30724 14.0069 8.47731 14.0063 8.65442V13.3157C14.0069 13.4928 13.937 13.6629 13.8121 13.7884C13.6871 13.914 13.5174 13.9847 13.3403 13.985L8.66615 13.9828Z" fill="#3E4348"/><path d="M33.1959 10.0196C32.5149 9.83388 31.8702 9.65883 31.3696 9.43575C30.4431 9.0216 30.0119 8.44734 30.0119 7.62972C30.0119 6.08414 31.5062 5.3882 32.9942 5.3882C34.4084 5.3882 35.6434 5.97313 36.4728 7.03412L36.5293 7.1067L37.7248 6.17167L37.6672 6.09908C36.5646 4.69653 34.9315 3.92801 33.0667 3.92801C31.8254 3.92801 30.6875 4.26317 29.8646 4.87265C28.9381 5.55044 28.4492 6.53351 28.4492 7.70657C28.4492 10.4338 31.0173 11.0987 33.0817 11.6335C35.1706 12.1843 36.4504 12.6027 36.4504 14.1952C36.4504 15.7632 35.1823 16.7762 33.2204 16.7762C32.2502 16.7762 30.4538 16.519 29.3245 14.7941L29.2722 14.7129L28.0148 15.6234L28.0639 15.6971C29.1313 17.3131 30.9736 18.2407 33.1244 18.2407C36.0458 18.2407 38.0098 16.5915 38.0098 14.1387C38.0098 11.3314 35.3392 10.6045 33.1959 10.0196Z" fill="#3E4348"/><path fill-rule="evenodd" clip-rule="evenodd" d="M47.5395 9.45282V7.97662H48.9452V21.9979H47.5395V16.52C46.7368 17.6205 45.5328 18.2225 44.1174 18.2225C41.4447 18.2225 39.5767 16.0824 39.5767 12.9923C39.5767 9.90219 41.4489 7.745 44.1174 7.745C45.5232 7.745 46.7272 8.35021 47.5395 9.45282ZM41.0583 12.9752C41.0583 15.8358 42.6967 16.8552 44.2305 16.8552L44.2337 16.8562C46.2415 16.8562 47.5395 15.3192 47.5395 12.9752C47.5395 10.6312 46.2394 9.11552 44.2305 9.11552C41.8919 9.11552 41.0583 11.1094 41.0583 12.9752Z" fill="#3E4348"/><path d="M58.239 7.97662V13.511C58.239 15.4484 56.9122 16.8552 55.0848 16.8552C53.567 16.8552 52.8284 15.9543 52.8284 14.1024V7.97662H51.4226V14.3895C51.4226 16.7911 52.728 18.2246 54.914 18.2246C56.276 18.2246 57.4459 17.6472 58.24 16.5915V17.9962H59.6458V7.97662H58.239Z" fill="#3E4348"/><path fill-rule="evenodd" clip-rule="evenodd" d="M62.293 9.02907C63.3294 8.21465 64.7362 7.7482 66.1505 7.7482C68.3846 7.7482 69.7177 8.85935 69.7135 10.723V17.9984H68.3067V16.8872C67.5968 17.7763 66.57 18.2268 65.2486 18.2268C63.0956 18.2268 61.7571 17.0494 61.7571 15.1559C61.7571 12.6934 64.0776 12.307 65.0661 12.1426C65.2272 12.116 65.3937 12.0904 65.5601 12.0647L65.5603 12.0647L65.5663 12.0638C66.9168 11.8559 68.3109 11.6414 68.3109 10.4957C68.3109 9.19878 66.6276 9.09845 66.1121 9.09845C65.2016 9.09845 63.9154 9.3685 63.0412 10.1263L62.9612 10.1957L62.2268 9.08137L62.293 9.02907ZM63.2248 15.0769C63.2248 16.6823 64.7362 16.8562 65.3863 16.8562H65.3873C66.8006 16.8562 68.3131 16.1027 68.3099 13.985V12.5354C67.6242 12.9685 66.6483 13.1377 65.7778 13.2886L65.7631 13.2912L65.3265 13.3691C63.9325 13.6274 63.2248 13.9604 63.2248 15.0769Z" fill="#3E4348"/><path d="M77.8577 8.16554C77.5236 7.92752 76.9974 7.78555 76.4487 7.78555C75.3213 7.80037 74.2664 8.34386 73.5998 9.25322V7.97235H72.1941V17.9909H73.5998V12.6326C73.5998 10.2566 74.9352 9.19237 76.2576 9.19237C76.6447 9.1872 77.0279 9.26852 77.3795 9.4304L77.4745 9.48057L77.9196 8.20611L77.8577 8.16554Z" fill="#3E4348"/><path fill-rule="evenodd" clip-rule="evenodd" d="M78.2697 13.0136C78.2697 9.91394 80.2027 7.7482 82.9662 7.7482C85.6282 7.7482 87.4887 9.67057 87.4834 12.4276C87.4826 12.6673 87.4694 12.9067 87.4439 13.1449L87.4353 13.2271H79.7501C79.7853 15.4334 81.1132 16.8562 83.1508 16.8562C84.3186 16.8562 85.3304 16.3813 85.9997 15.5177L86.0605 15.4388L87.0788 16.346L87.0223 16.4143C86.3455 17.2394 85.1116 18.2236 83.0729 18.2236C80.2016 18.2236 78.2697 16.1304 78.2697 13.0136ZM82.9277 9.09738C81.2103 9.09738 79.9924 10.2277 79.8024 11.9879H85.9976C85.8759 10.5725 85.0113 9.09738 82.9277 9.09738Z" fill="#3E4348"/></svg>';
		}

		/**
		 * Generate the Square Connect button.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The api mode (test|live).
		 * @param string $redirect_url The URL to redirect to after connecting.
		 *
		 * @return string
		 */
		public function get_square_connect_button_x( $mode = 'sandbox', $redirect_url = '' ) {

			$url  = $this->get_square_connect_url( $mode, $redirect_url );
			$html = '';

			if ( 'live' === $mode ) {

				$html .= '<a href="' . esc_url( $url ) . '" id="charitable-square-connect-btn" class="charitable-square-btn charitable-square-live-btn" title="' . esc_attr__( 'Connect your site with Square account for easy onboarding.', 'charitable' ) . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z"></path><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z"></path></svg><span>' . esc_html__( 'Connect to Square Account', 'charitable' ) . '</span></a>';

			} elseif ( 'sandbox' === $mode ) {

				$html .= '<a href="' . esc_url( $url ) . '" id="charitable-square-connect-sandbox-btn" class="charitable-square-btn charitable-square-sandbox-btn" title="' . esc_attr__( 'Connect your site with Square account for easy onboarding.', 'charitable' ) . '"><svg version="1.1" id="Layer_1" xmlns:x="&ns_extend;" xmlns:i="&ns_ai;" xmlns:graph="&ns_graphs;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 994.6 995.7" style="enable-background:new 0 0 994.6 995 "xml:space="preserve"><style type="text/css">.st0{fill:#1A1A1A;}</style><switch><foreignObject requiredExtensions="&ns_ai;" x="0" y="0" width="1" height="1"><i:aipgfRef xlink:href="#adobe_illustrator_pgf"></i:aipgfRef></foreignObject><g i:extraneous="self"><path class="st0" d="M828.4,0H166.2C74.4,0,0,74.4,0,166.2v662.2c0,91.8,74.4,166.2,166.2,166.2h662.2c91.8,0,166.2-74.4,166.2-166.2V166.2C994.6,74.4,920.2,0,828.4,0z M813.8,761.3c0,29-23.5,52.5-52.5,52.5h-528c-29,0-52.5-23.5-52.5-52.5v-528c0-29,23.5-52.5,52.5-52.5h528c29,0,52.5,23.5,52.5,52.5V761.3z M391.8,632.3c-16.7,0-30.1-13.5-30.1-30.2V391.3c0-16.7,13.4-30.3,30.1-30.3h211.1c16.6,0,30.1,13.5,30.1,30.3V602c0,16.7-13.5,30.2-30.1,30.2H391.8z"/></g></switch></svg><span>' . esc_html__( 'Connect to Square Account (Sandbox)', 'charitable' ) . '</span></a>';

			}

			return $html;
		}

		/**
		 * Get the Square disconnect button.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The api mode (test|live).
		 * @param string $redirect_url The URL to redirect to after disconnecting.
		 * @return string
		 */
		public function get_square_disconnect_button( $mode = 'sandbox', $redirect_url = '' ) {
			ob_start();

			$html = '';

			if ( ! charitable_is_pro() && 'live' === $mode ) {
				$html .= '<div class="charitable-inline-notice info">
            <p>
            <strong>' . esc_html__( 'Pay as you go pricing:', 'charitable' ) . '</strong> ' .
				sprintf(
				/* translators: % 1$s: opening link tag, % 2$s: closing link tag */
					esc_html__( '3%% per transaction + Square fees. %1$sUpgrade to Pro%2$s for no added fees and priority support.', 'charitable' ),
					'<a target="_blank" href="' . esc_url( charitable_pro_upgrade_url( 'gateway-square' ) ) . '">',
					'</a>'
				) . '</p>
            </div>';
			}

			$html .= '<div id="charitable-square-sandbox-actions-disconnect" class="charitable-square-settings-actions-message charitable-square-settings-actions-message-disconnect charitable-square-settings-actions-message-testmode-' . charitable_get_option( 'test_mode' ) . '" style="display: block;">';

			if ( charitable_get_option( 'test_mode' ) ) {
				$html .= '<p class="charitable-square-settings-actions-title-testmode-sandbox"><strong>' . __( 'Charitable is in test mode and currently using the Sandbox environment.', 'charitable' ) . '</strong>';
			} else {
				$html .= '<p class="charitable-square-settings-actions-title-testmode-live"><strong>' . __( 'You have setup your Square account in Sandbox mode. Charitable is in live mode so this is not being used', 'charitable' ) . '</strong>';
			}

			$html .= '<p>' . __( 'The Sandbox environment is where all operations are simulated and no actual charges will occur. Perfect for testing your setup.', 'charitable' ) . '</p>';
			$html .= '<p><a href="' . esc_url( $this->get_square_disconnect_url( $mode, $redirect_url ) ) . '">' . __( 'Disconnect from Square Sandbox.', 'charitable' ) . '</a></p>';

			$html .= '<input type="hidden" id="charitable-square-test-access-token" name="charitable_gateways_square_test_access_token" value="' . $this->get_square_access_token( 'test' ) . '">';
			$html .= '<input type="hidden" id="charitable-square-test-refresh-token" name="charitable_gateways_square_test_refresh_token" value="' . $this->get_square_refresh_token( 'test' ) . '">';
			$html .= '<input type="hidden" id="charitable-square-test-merchant-id" name="charitable_gateways_square_test_merchant_id" value="' . $this->get_square_merchant_id( 'test' ) . '">';
			$html .= '<input type="hidden" id="charitable-square-test-merchant-id" name="charitable_gateways_square_test_token_refresh_date" value="' . $this->get_personal_access_token_refresh_date( 'test', true ) . '">';

			// check if the locations are set.
			$square_locations_check = get_option( '_charitable_square_locations_test' );

			if ( ! empty( $square_locations_check ) ) {
				$html .= '<input type="hidden" id="charitable-square-test-locations" name="charitable_gateways_square_test_locations" value="' . maybe_serialize( $square_locations_check['locations'] ) . '">';
				$html .= '<input type="hidden" id="charitable-square-test-locations-timestamp" name="charitable_gateways_square_test_locations_timestamp" value="' . $square_locations_check['locations_timestamp'] . '">';
			}

			return $html;
		}

		/**
		 * Get the Square access token.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The api mode, test or live.
		 * @return string
		 */
		public function get_square_access_token( $mode = 'test' ) {

			$access_token = charitable_square_get_refresh_token( $mode );

			// Debug log the settings.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log(
					sprintf(
						'[Charitable Square] Retrieving access token for mode: %s',
						$mode
					)
				);
				$square_settings = charitable_get_option( 'gateways_square', array() );
				error_log(
					sprintf(
						'[Charitable Square] Settings: %s',
						wp_json_encode( $square_settings )
					)
				);
				// phpcs:enable
			}

			if ( ! empty( $access_token ) ) {
				// Debug log token presence.
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log(
						sprintf(
							'[Charitable Square] Found access token: %s',
							substr( $access_token, 0, 10 ) . '...' // Only log first 10 chars for security.
						)
					);
					// phpcs:enable
				}

				return $access_token;
			}

			// Debug log missing token.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log(
					sprintf(
						'[Charitable Square] No access token found for mode: %s',
						$mode
					)
				);
				// phpcs:enable
			}

			return '';
		}

		/**
		 * Get the Square refresh token.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The api mode, test or live.
		 * @return string
		 */
		public function get_square_refresh_token( $mode = 'test' ) {
			return charitable_square_get_refresh_token( $mode );
		}

		/**
		 * Get the Square merchant ID.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The api mode, test or live.
		 * @return string
		 */
		public function get_square_merchant_id( $mode = 'test' ) {
			$square_settings = charitable_get_option( 'gateways_square', array() );

			if ( ! empty( $square_settings[ $mode ]['merchant_id'] ) ) {
				return $square_settings[ $mode ]['merchant_id'];
			}

			return '';
		}

		/**
		 * Get the URL to connect to Square.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to connect to.
		 * @param string $redirect_url The URL to redirect to after connecting.
		 * @return string
		 */
		public function get_square_connect_url( $mode = '', $redirect_url = '' ) {

			if ( ! $mode ) {
				$mode = 'test' === charitable_get_option( 'test_mode' ) || 1 === charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			}

			if ( empty( $redirect_url ) ) {

				$redirect_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_square_core',
					),
					admin_url( 'admin.php' )
				);

			}

			// Get the scopes for the needed permissions.
			$scopes = Charitable_Square_Connect::get_instance()->get_scopes();

			return add_query_arg(
				array(
					'live_mode'         => $mode === 'live' ? 1 : 0,
					'state'             => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 100, wp_rand(), STR_PAD_BOTH ),
					'customer_site_url' => rawurlencode( $redirect_url ),
					'admin_email'       => get_bloginfo( 'admin_email' ),
					'permissions'       => implode( '+', $scopes ),
				),
				'https://connect.wpcharitable.com/square-connect/?wpcharitable_gateway_connect_init=square_connect'
			);
		}

		/**
		 * Get the URL to renew the Square token.
		 *
		 * @since 1.8.7
		 *
		 * @param string  $redirect_url The URL to redirect to after connecting.
		 * @param boolean $cron_job_request Whether the request is a cron job request.
		 * @return string
		 */
		public function get_square_renew_token_url( $redirect_url = '', $cron_job_request = false ) {
			if ( empty( $redirect_url ) ) {

				$redirect_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_square_core',
					),
					admin_url( 'admin.php' )
				);

			}

			$mode = 'live' === charitable_get_option( 'test_mode' ) ? 'live' : 'test';

			return add_query_arg(
				array(
					'live_mode'         => (int) ! charitable_get_option( 'test_mode' ),
					'refresh_token'     => charitable_square_get_refresh_token( $mode ),
					'key'               => get_option( 'charitable_square_aes_key_' . $mode, '' ),
					'customer_site_url' => urlencode( $redirect_url ), // phpcs:ignore
					'admin_email'       => get_bloginfo( 'admin_email' ),
					'cron_job_request'  => $cron_job_request,
				),
				'https://connect.wpcharitable.com/square-connect/?wpcharitable_gateway_connect_refresh_token=square_connect' // todo: filter market site url.
			);
		}

		/**
		 * Get the URL to disconnect from Square.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to disconnect from.
		 * @param string $redirect_url The URL to redirect to after disconnecting.
		 * @return string
		 */
		public function get_square_disconnect_url( $mode = 'sandbox', $redirect_url = '' ) {
			return add_query_arg(
				array(
					'wpcharitable-square-disconnect' => true,
					'_wpnonce'                       => wp_create_nonce(
						'wpcharitable-square-connect-disconnect'
					),
					'tab'                            => 'gateways',
					'page'                           => 'charitable-settings',
					'group'                          => 'gateways_square_core',
					'mode'                           => $mode,
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Returns the current gateway's ID.
		 *
		 * @return string
		 * @since  1.8.7
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Save more complex Square gateway settings.
		 *
		 * @since 1.8.7
		 *
		 * @param array $values The values to save.
		 * @param array $new_values The new values.
		 * @param array $old_values The old values.
		 * @return array
		 */
		public function save_settings( $values, $new_values, $old_values ) {

			$square_settings_root_old_settings = ! empty( $old_values['gateways_square'] ) ? $old_values['gateways_square'] : array();
			$square_settings_root_new_settings = ! empty( $new_values['gateways_square'] ) ? $new_values['gateways_square'] : array();

			// merge the new settings with the old settings, with the new settings taking precedence.
			$values['gateways_square'] = array_merge( $square_settings_root_old_settings, $square_settings_root_new_settings );

			// Handle location ID for test mode.
			if ( isset( $_POST['charitable_settings']['gateways_square']['test']['location_id'] ) ) { //phpcs:ignore
				// For the location ID we should save the location ID to the option for test mode.
				update_option( 'charitable_square_location_id_test', sanitize_text_field( $_POST['charitable_settings']['gateways_square']['test']['location_id'] ) ); //phpcs:ignore
				$values['gateways_square']['test']['location_id'] = sanitize_text_field( $_POST['charitable_settings']['gateways_square']['test']['location_id'] ); //phpcs:ignore
			}

			// Handle location ID for live mode.
			if ( isset( $_POST['charitable_settings']['gateways_square']['live']['location_id'] ) ) { //phpcs:ignore
				// For the location ID we should save the location ID to the option for live mode.
				update_option( 'charitable_square_location_id_live', sanitize_text_field( $_POST['charitable_settings']['gateways_square']['live']['location_id'] ) ); //phpcs:ignore
				$values['gateways_square']['live']['location_id'] = sanitize_text_field( $_POST['charitable_settings']['gateways_square']['live']['location_id'] ); //phpcs:ignore
			}

			// We need to possibly update the sandbox and live webhook ids and secrets.
			// If the values exist in $_POST that means the webhook_settings=1 was added to the settings URL.
			if ( isset( $_POST['charitable_settings']['gateways_square']['square_webhooks_id_sandbox'] ) ) { //phpcs:ignore
				if ( ! empty( $_POST['charitable_settings']['gateways_square']['square_webhooks_id_sandbox'] ) ) { //phpcs:ignore
					$values['gateways_square']['test']['webhooks-id'] = sanitize_text_field( $_POST['charitable_settings']['gateways_square']['square_webhooks_id_sandbox'] ); //phpcs:ignore
				} else {
					$values['gateways_square']['test']['webhooks-id'] = '';
				}
			}

			if ( isset( $_POST['charitable_settings']['gateways_square']['square_webhooks_secret_sandbox'] ) ) { //phpcs:ignore
				if ( ! empty( $_POST['charitable_settings']['gateways_square']['square_webhooks_secret_sandbox'] ) ) { //phpcs:ignore
					$values['gateways_square']['test']['webhooks-secret'] = sanitize_text_field( $_POST['charitable_settings']['gateways_square']['square_webhooks_secret_sandbox'] ); //phpcs:ignore
				} else {
					$values['gateways_square']['test']['webhooks-secret'] = '';
				}
			}

			if ( isset( $_POST['charitable_settings']['gateways_square']['square_webhooks_id_live'] ) ) { //phpcs:ignore
				if ( ! empty( $_POST['charitable_settings']['gateways_square']['square_webhooks_id_live'] ) ) { //phpcs:ignore
					$values['gateways_square']['live']['webhooks-id'] = sanitize_text_field( $_POST['charitable_settings']['gateways_square']['square_webhooks_id_live'] ); //phpcs:ignore
				} else {
					$values['gateways_square']['live']['webhooks-id'] = '';
				}
			}

			if ( isset( $_POST['charitable_settings']['gateways_square']['square_webhooks_secret_live'] ) ) { //phpcs:ignore
				if ( ! empty( $_POST['charitable_settings']['gateways_square']['square_webhooks_secret_live'] ) ) { //phpcs:ignore
					$values['gateways_square']['live']['webhooks-secret'] = sanitize_text_field( $_POST['charitable_settings']['gateways_square']['square_webhooks_secret_live'] ); //phpcs:ignore
				} else {
					$values['gateways_square']['live']['webhooks-secret'] = '';
				}
			}

			// Check if we should clear the mode connection warning.
			$this->maybe_clear_mode_connection_warning( $values );

			return $values;
		}


		/**
		 * Get the personal access token.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to get the personal access token for.
		 * @return string
		 */
		public function get_personal_access_token( $mode = 'test' ) {

			// Personal access tokens are no longer used - return empty string.
			return '';
		}


		/**
		 * Get the personal access refresh token.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode The mode to get the personal access refresh token for.
		 * @return string
		 */
		public function get_personal_access_refresh_token( $mode = 'test' ) {

			// Personal access tokens are no longer used - return empty string.
			return '';
		}

		/**
		 * Get the personal access token refresh date.
		 *
		 * @since 1.8.7
		 *
		 * @param string  $mode The mode to get the personal access token refresh date for.
		 * @param boolean $raw Whether to return the raw date.
		 * @return string
		 */
		public function get_personal_access_token_refresh_date( $mode = 'test', $raw = false ) {
			// Personal access tokens are no longer used - return null.
			return null;
		}

		/**
		 * Get the personal access token should refresh date.
		 *
		 * @since 1.8.7
		 *
		 * @param string  $mode The mode to get the personal access token should refresh date for.
		 * @param boolean $raw Whether to return the raw date.
		 * @return string
		 */
		public function get_personal_access_token_should_refresh( $mode = 'test', $raw = false ) {
			// Personal access tokens are no longer used - return false.
			return false;
		}

		/**
		 * Return the keys to use.
		 *
		 * This will return the test keys if test mode is enabled. Otherwise, returns
		 * the production keys.
		 *
		 * @since  1.8.7
		 *
		 * @param string $force_mode Forces the "mode" to be used (forcing test for example if the setting is live and vice versa).
		 * @return string[]
		 */
		public function get_keys( $force_mode = '' ) {
			$keys = array();

			$settings = charitable_get_option( 'gateways_square', array() );

			if ( charitable_get_option( 'test_mode' ) || 'test' === $force_mode ) {
				$keys['access_token'] = trim( $settings['test']['access_token'] );
			} else {
				$keys['access_token'] = trim( $settings['live']['access_token'] );
			}

			return $keys;
		}

		/**
		 * Get the account ID.
		 *
		 * @since 1.8.7
		 *
		 * @param string $force_mode The mode to get the account ID for.
		 * @return string
		 */
		public function get_account_id( $force_mode = '' ) {
			$settings = charitable_get_option( 'gateways_square', array() );

			if ( '' === $force_mode ) {
				$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			} else {
				$mode = $force_mode;
			}

			return ! empty( $settings[ $mode . '_account_id' ] ) ? $settings[ $mode . '_account_id' ] : '';
		}

		/**
		 * Check if Square access token exists.
		 *
		 * @since  1.8.7
		 *
		 * @param  string $mode The mode to check for keys. Should be 'test' or 'live'. If it's sandbox, rename it to 'test'.
		 * @return boolean
		 */
		public function check_keys_exist( $mode = '' ) {

			if ( '' === $mode ) {
				return false;
			}

			if ( 'sandbox' === $mode ) {
				$mode = 'test';
			}

			$settings = get_option( 'charitable_settings' );

			$access_token = $settings['gateways_square'][ $mode . '_access_token' ];

			if ( ! empty( $access_token ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Return the submitted value for a gateway field.
		 *
		 * @since  1.8.7
		 *
		 * @param  string  $key The key of the value we want to get.
		 * @param  mixed[] $values An values in which to search.
		 * @return string|false
		 */
		public function get_gateway_value( $key, $values ) {
			if ( isset( $values['gateways']['square_core'][ $key ] ) ) {
				return $values['gateways']['square_core'][ $key ];
			}

			return false;
		}

		/**
		 * Return the submitted value for a gateway field.
		 *
		 * @since  1.8.7
		 *
		 * @param  string                        $key The key of the value we want to get.
		 * @param  Charitable_Donation_Processor $processor The Donation Processor helper object.
		 * @return string|false
		 */
		public function get_gateway_value_from_processor( $key, Charitable_Donation_Processor $processor ) {
			return $this->get_gateway_value( $key, $processor->get_donation_data() );
		}

		/**
		 * Returns an array of credit card fields.
		 *
		 * If the gateway requires different fields, this can simply be redefined
		 * in the child class.
		 *
		 * @since  1.8.7.
		 *
		 * @return array[]
		 */
		public function get_credit_card_fields() {
			$fields     = parent::get_credit_card_fields();
			$no_wallets = false;

			/* Remove all fields. Square will take over */
			unset(
				$fields['cc_name'],
				$fields['cc_number'],
				$fields['cc_cvc'],
				$fields['cc_expiration']
			);

			ob_start();

			?>

			<fieldset id="charitable-square-card-payment-fields" class="<?php echo $no_wallets ? 'no-wallets' : ''; ?>">
				<div id="square-card-container"></div>
				<input type="hidden" name="square_token" />
				<input type="hidden" name="square_verification_token" />
			</fieldset>

			<div id="square-payment-status-container"></div>

			<?php

			$fields['cc_element'] = array(
				'type'     => 'content',
				'content'  => ob_get_clean(),
				'priority' => 2,
			);

			return $fields;
		}

		/**
		 * Process a donation's payment using the Payment Processor API.
		 *
		 * @since  1.8.7
		 * @since  1.8.8.4
		 *
		 * @param  array                          $response The response from the Payment Processor API (Example: "1").
		 * @param  int                            $donation_id The ID of the donation (Example: "677").
		 * @param  \Charitable_Donation_Processor $processor The Donation Processor object.
		 * @return boolean|null|array
		 */
		public function process_donation( $response, $donation_id, \Charitable_Donation_Processor $processor ) {

			// CRITICAL DEBUG: Check if this method is being called at all.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '=== SQUARE CORE PROCESS_DONATION METHOD CALLED ===' );
				error_log( 'Donation ID: ' . $donation_id );
				error_log( 'Response: ' . print_r( $response, true ) );
				error_log( 'Processor: ' . get_class( $processor ) );
				error_log( 'POST data: ' . print_r( $_POST, true ) );
				error_log( 'Gateway from processor: ' . $processor->get_donation_data_value( 'gateway' ) );
				// phpcs:enable
			}

			$donation   = new \Charitable_Donation( $donation_id );
			$mode       = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$connection = Charitable_Square_Connection::get( $mode );
			$this->api  = new Charitable_Square_API( $connection );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Mode: ' . $mode );
				error_log( 'Connection object: ' . ( $connection ? get_class( $connection ) : 'NULL' ) );
				error_log( 'Connection configured: ' . ( $connection ? ( $connection->is_configured() ? 'YES' : 'NO' ) : 'N/A' ) );
				error_log( 'Connection valid: ' . ( $connection ? ( $connection->is_valid() ? 'YES' : 'NO' ) : 'N/A' ) );
				// phpcs:enable
			}

			$square_token              = isset( $_POST['square_token'] ) ? sanitize_text_field( $_POST['square_token'] ) : ''; // phpcs:ignore
			$square_verification_token = isset( $_POST['square_verification_token'] ) ? sanitize_text_field( $_POST['square_verification_token'] ) : ''; // phpcs:ignore
			$campaign_name             = get_the_title( (int) $_POST['campaign_id'] ); // phpcs:ignore

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Square token: ' . ( $square_token ? 'PRESENT' : 'MISSING' ) );
				error_log( 'Square verification token: ' . ( $square_verification_token ? 'PRESENT' : 'MISSING' ) );
				error_log( 'Campaign name: ' . $campaign_name );
				// phpcs:enable
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_donation - $_POST is ' . print_r( $_POST, true ) );
				// phpcs:enable
			}

		$donation_amount = $this->get_donation_amount_with_fees( $donation );
		// Conver to cents.
		$donation_amount = $donation_amount * 100;
			$args_name       = 'Donation ID: ' . $donation_id . ' | ' . ( 'custom' === ! empty( $_POST['donation_amount'] ) ? 'Custom Amount' : 'Suggested Amount' ); // phpcs:ignore

			// Set tokens provided by Web Payments SDK.
			$this->api->set_payment_tokens( $square_token ); // $entry

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_donation mode is ' . $mode );
				// phpcs:enable
			}

			$location_id = $this->api->get_location_id( $mode );
			$note        = get_bloginfo( 'name' ) . ': ' . $campaign_name;

			$args_payment_single = array(
				'amount'      => $donation_amount,
				'currency'    => charitable_get_currency(),
				'location_id' => $location_id,
				'note'        => $note,
				'order_items' => array(
					array(
						'name'       => 'Donations',
						'quantity'   => 1,
						'variations' => array(
							array(
								'name'           => 'Donation',
								'quantity'       => 1,
								'variation_name' => $args_name,
								'amount'         => $donation_amount,
							),
						),
					),
				),
			);

			$subscription_post_id = $donation->get( 'post_parent' );

			if ( $subscription_post_id ) {

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_donation - subscription_post_id is ' . $subscription_post_id );
					// phpcs:enable
				}

				$subscription = charitable_get_donation( $subscription_post_id );

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					// error_log( 'donation is ' . print_r( $donation, true ) );
					error_log( 'subscription is ' . print_r( $subscription, true ) );
					error_log( 'POST is ' . print_r( $_POST, true ) );
					// phpcs:enable
				}

				$plan = $_POST; //phpcs:ignore

				$args_subscription = $this->get_payment_args_subscription( $plan, $campaign_name, $donation_amount );

				$args_subscription_single = array(
					'amount'       => $donation_amount,
					'currency'     => charitable_get_currency(),
					'location_id'  => $location_id,
					'subscription' => $args_subscription,
				);

				$result = $this->api->process_subscription_transaction( $args_subscription_single );

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_donation - result process subscription transaction' );
					error_log( print_r( $result, true ) );
					// phpcs:enable
				}

				$subscription_id   = $result->getId();
				$plan_variation_id = $result->getPlanVariationId();
				$customer_id       = $result->getCustomerId();
				$start_date        = $result->getStartDate();
				$status            = $result->getStatus();

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_donation - subscription_id is ' . print_r( $subscription_id, true ) );
					error_log( 'process_donation - plan_variation_id is ' . print_r( $plan_variation_id, true ) );
					error_log( 'process_donation - customer_id is ' . print_r( $customer_id, true ) );
					error_log( 'process_donation - start_date is ' . print_r( $start_date, true ) );
					error_log( 'process_donation - status is ' . print_r( $status, true ) );
					// phpcs:enable
				}

				$subscription->set_gateway_subscription_id( $subscription_id );
				$message = sprintf( '%s: %s', __( 'Square Subscription ID', 'charitable' ), $subscription_id );
				$subscription->log()->add( $message );

				if ( 'ACTIVE' === $status ) {
					$subscription->update_status( 'charitable-active' );
				} else {
					$subscription->update_status( 'charitable-pending' );
				}

				if ( charitable_is_debug( 'webhook' ) ) {
					// Create a single array of meta data with the remaining items.
					$meta_data = array(
						'subscription_id'   => $subscription_id,
						'plan_variation_id' => $plan_variation_id,
						'customer_id'       => $customer_id,
						'start_date'        => $start_date,
						'status'            => $status,
					);
					update_post_meta( $subscription_post_id, '_square_subscription_meta_data', $meta_data );
				}

				// We need to update the donation so that we can find it again when the invoice is paid.
				update_post_meta( $donation_id, '_square_subscription_donation_first_donation', $subscription_post_id );

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_donation - donation_id is ' . $donation_id );
					error_log( 'process_donation - subscription_post_id is ' . $subscription_post_id );
					// phpcs:enable
				}
			} else {

				$result = $this->api->process_single_transaction( $args_payment_single );

				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'process_donation - result process single transaction' );
					error_log( print_r( $result, true ) );
					// phpcs:enable
				}

				// Set payment processing flag.
				$this->is_payment_processed = true;
				$order                      = null;
				$order_id                   = '';

				$errors = $this->process_api_errors( 'single' );

				if ( ! empty( $errors ) ) {
					// Process errors and handle categories.
					$error_messages = array();
					foreach ( $errors as $error ) {
						if ( is_array( $error ) && isset( $error['category'] ) ) {
							// Handle specific error categories.
							switch ( $error['category'] ) {
								case 'AUTHENTICATION_ERROR':
									$error_messages[] = __( 'Authentication error with Square. Please check your Square Gateway settings.', 'charitable' );
									break;
								default:
									$error_messages[] = $error['message'];
									break;
							}
						} else {
							$error_messages[] = $error;
						}
					}

					// If an array is returned, we need to implode it.
					$error_message = implode( ', ', $error_messages );
					$message       = sprintf( '%s: %s', __( 'Errors', 'charitable' ), $error_message );
					$donation->log()->add( $message );

					// Add a general admin notice to alert the user that there was an error.
					$admin_message = sprintf(
						'%s' . "\n" . 'Details: %s',
						__( 'Errors have occurred while processing Square donations. You may need to check the connection to your Square account.', 'charitable' ),
						$error_message
					);

					if ( charitable_is_debug( 'square' ) ) {
						error_log( 'Adding Square admin notice: ' . $admin_message ); //phpcs:ignore
					}

					// Get current notices.
					$current_notices = charitable_get_admin_notices()->get_notices();
					$notice_exists   = false;

					// Check if this exact error message already exists.
					if ( ! empty( $current_notices['error'] ) ) {
						foreach ( $current_notices['error'] as $notice ) {
							if ( $notice['message'] === $admin_message ) {
								$notice_exists = true;
								break;
							}
						}
					}

					// Only add the notice if it doesn't already exist.
					if ( ! $notice_exists ) {
						charitable_get_admin_notices()->add_notice( $admin_message, 'error', false, true );
					}

					if ( charitable_is_debug( 'square' ) ) {
						error_log( 'Current admin notices: ' . print_r( charitable_get_admin_notices()->get_notices(), true ) ); //phpcs:ignore
					}

					$donation->update_status( 'charitable-failed' );

					wp_send_json_error( $admin_message );
					exit;
				}

				if ( isset( $result['order'] ) ) {
					$order    = $result['order'];
					$order_id = $order->getId();
				}

				if ( $order_id ) {
					// This Square Order / Transaction ID is used by the webhook to update the donation status.
					$donation->set_gateway_transaction_id( $order_id );
					$message = sprintf( '%s: %s', __( 'Square Order / Transaction ID', 'charitable' ), $order_id );
					$donation->log()->add( $message );
				}
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'process_donation - end' );
				// phpcs:enable
			}

			$donation->update_status( 'charitable-pending' );

			return true;
		}

		/**
		 * Retrieve subscription payment args.
		 *
		 * @since 1.8.7
		 *
		 * @param array  $plan Plan settings.
		 * @param string $campaign_name Campaign name.
		 * @param int    $donation_amount Donation amount.
		 *
		 * @return array
		 */
		private function get_payment_args_subscription( array $plan, string $campaign_name, int $donation_amount ): array {

			$args_sub = array();
			$currency = charitable_get_currency();

			$recurring_donation_plan = ! empty( $plan['recurring_donation_plan'] ) ? $plan['recurring_donation_plan'] : '';
			$recurring_donation_plan = empty( $recurring_donation_plan ) && ! empty( $plan['recurring_donation'] ) ? sanitize_text_field( $plan['recurring_donation'] ) : $recurring_donation_plan;

			$recurring_donation_plan = apply_filters( 'charitable_square_recurring_donation_plan', $recurring_donation_plan, $plan );

			switch ( $recurring_donation_plan ) {
				case 'day':
				case 'daily':
					$phase_cadence_key = 'daily';
					break;
				case 'week':
				case 'weekly':
					$phase_cadence_key = 'weekly';
					break;
				case 'month':
				case 'monthly':
					$phase_cadence_key = 'monthly';
					break;
				case 'quarter':
				case 'quarterly':
					$phase_cadence_key = 'quarterly';
					break;
				case 'semiyear':
				case 'semiyearly':
					$phase_cadence_key = 'semiyearly';
					break;
				case 'year':
				case 'yearly':
				case 'annually':
				case 'annual':
					$phase_cadence_key = 'yearly';
					break;
			}
			$plan['name'] = get_bloginfo( 'name' );

			$args_sub['customer']['email']      = sanitize_email( $_POST['email'] ); //phpcs:ignore
			$args_sub['customer']['first_name'] = sanitize_text_field( $_POST['first_name'] ); //phpcs:ignore
			$args_sub['customer']['last_name']  = sanitize_text_field( $_POST['last_name'] ); //phpcs:ignore

			// Customer address.
			// There's address_2, city, state, postcode, country.
			$args_sub['customer']['address']   = ! empty( $_POST['address'] ) ? sanitize_text_field( $_POST['address'] ) : ''; //phpcs:ignore
			$args_sub['customer']['address_2'] = ! empty( $_POST['address_2'] ) ? sanitize_text_field( $_POST['address_2'] ) : ''; //phpcs:ignore
			$args_sub['customer']['city']      = ! empty( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : ''; //phpcs:ignore
			$args_sub['customer']['state']     = ! empty( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : ''; //phpcs:ignore
			$args_sub['customer']['postcode']  = ! empty( $_POST['postcode'] ) ? sanitize_text_field( $_POST['postcode'] ) : ''; //phpcs:ignore
			$args_sub['customer']['country']   = ! empty( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : ''; //phpcs:ignore
			$args_sub['customer']['phone']     = ! empty( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : ''; //phpcs:ignore

			$cadences_list = Charitable_Square_Helpers::get_subscription_cadences();

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'cadences_list is ' . print_r( $cadences_list, true ) );
				// phpcs:enable
			}

			$phase_cadence = $cadences_list[ $phase_cadence_key ] ?? $cadences_list['yearly'];

			// Subscription cadence.
			$args_sub['phase_cadence'] = $phase_cadence;

			$plan_name  = $campaign_name;
			$plan_name .= empty( $plan['name'] ) ? '' : ': ' . $plan['name'];

			$args_sub['plan_name']           = sprintf( '%s (%s)', $plan_name, $phase_cadence['name'] );
			$args_sub['plan_variation_name'] = sprintf( '%s (%s %s %s)', $plan['name'], $donation_amount, $currency, $phase_cadence['name'] );

			// Card holder.
			$args_sub['card_name'] = sanitize_text_field( $_POST['first_name'] ) . ' ' . sanitize_text_field( $_POST['last_name'] ); // phpcs:ignore

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'get_payment_args_subscription - args_sub is ' . print_r( $args_sub, true ) );
				// phpcs:enable
			}

			/**
			 * Filter subscription payment arguments.
			 *
			 * @since 1.8.7
			 *
			 * @param array   $args    The subscription payment arguments.
			 * @param Process $process The Process instance.
			 */
			return (array) apply_filters( 'square_integrations_square_process_get_payment_args_subscription', $args_sub, $this ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a third-party hook from the Square Integrations plugin. Changing it would break existing functionality.
		}

		/**
		 * Collect errors from API and turn it into form errors.
		 *
		 * @since 1.8.7
		 *
		 * @param string $type Payment type (e.g. 'single').
		 */
		private function process_api_errors( string $type ) {

			$errors = $this->api->get_errors();

			if ( empty( $errors ) || ! is_array( $errors ) ) {
				return;
			}

			$this->log_errors( $errors );

			if ( $type === 'subscription' ) {
				$title = esc_html__( 'Square subscription payment stopped', 'charitable' );
			} else {
				$title = esc_html__( 'Square payment stopped', 'charitable' );
			}

			$_errors = $this->api->get_response_errors();

			if ( ! empty( $_errors ) ) {
				$this->process_api_errors_codes( $_errors );
				$errors[] = $_errors;
			}

			// Process errors and extract categories.
			$processed_errors = array();
			foreach ( $errors as $error ) {
				if ( is_array( $error ) && isset( $error['category'] ) ) {
					// Handle error with category.
					$processed_errors[] = array(
						'message'  => $error['message'],
						'category' => $error['category'],
					);
				} else {
					// Handle regular error string.
					$processed_errors[] = $error;
				}
			}

			if ( ! empty( $processed_errors ) && charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Charitable_Gateway_Square. Errors reported in API. ' . print_r( $processed_errors, true ) );
				// phpcs:enable
			}

			return $processed_errors;
		}

		/**
		 * Display form errors.
		 *
		 * @since 1.8.7
		 *
		 * @param array $errors Errors to display.
		 */
		private function log_errors( array $errors = array() ) {

			if ( ! $errors ) {
				$errors = $this->errors;
			}

			if ( ! $errors || ! is_array( $errors ) ) {
				return;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Charitable_Gateway_Square. Errors replorted in API. ' . print_r( $errors, true ) );
				// phpcs:enable
			}
		}

		/**
		 * Check specific error codes.
		 *
		 * @since 1.8.7
		 *
		 * @param array $errors The last API call errors.
		 */
		private function process_api_errors_codes( array $errors ) {

			$codes = $this->get_oauth_error_codes();

			foreach ( $errors as $error ) {

				if (
					empty( $error['code'] ) ||
					! in_array( $error['code'], $codes, true )
				) {
					continue;
				}

				// If the error indicates that access token is bad, set a connection as invalid.
				$this->connection
				->set_status( Charitable_Square_Connection::STATUS_INVALID )
				->save();
			}
		}

		/**
		 * Retrieve OAuth-related errors.
		 * used to be return [ ErrorCode::ACCESS_TOKEN_EXPIRED, ErrorCode::ACCESS_TOKEN_REVOKED, ErrorCode::UNAUTHORIZED ];
		 *
		 * @since 1.8.7
		 *
		 * @link https://developer.squareup.com/docs/oauth-api/best-practices#ensure-api-calls-made-with-oauth-tokens-handle-token-based-errors-appropriately
		 *
		 * @return array
		 */
		private function get_oauth_error_codes(): array {
			return array( 'ACCESS_TOKEN_EXPIRED', 'ACCESS_TOKEN_REVOKED', 'UNAUTHORIZED' );
		}

		/**
		 * Validate the submitted credit card details.
		 *
		 * @since  1.8.7
		 * @since  1.4.0 Deprecated.
		 * @since  1.4.7 Restored. No longer deprecated.
		 *
		 * @param  boolean $valid Whether the donation is valid.
		 * @param  string  $gateway The chosen gateway.
		 * @param  mixed[] $values The filtered values from the donation form submission.
		 * @return boolean
		 */
		public static function validate_donation( $valid, $gateway, $values ) {
			if ( 'square_core' !== $gateway ) {
				return $valid;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'validate_donation : Charitable_Gateway_Square is making an attempt to validate the donation' );
				error_log( '$valid: ' . $valid );
				error_log( '$gateway: ' . $gateway );
				error_log( '$values: ' . print_r( $values, true ) );
				// phpcs:enable
			}

			$gateway   = new Charitable_Gateway_Square();
			$test_mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';

			if ( empty( charitable_square_get_access_token( $test_mode ) ) ) {
				charitable_get_notices()->add_error( __( 'Missing access token for Square payment gateway. Unable to proceed with payment.', 'charitable' ) );
				return false;
			}
			return $valid;
		}

		/**
		 * Check whether a particular donation can be refunded automatically in Square.
		 *
		 * @since  1.8.7
		 *
		 * @param  Charitable_Donation $donation The donation object.
		 * @return boolean
		 */
		public function is_donation_refundable( Charitable_Donation $donation ) {

			$mode       = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$connection = Charitable_Square_Connection::get( $mode );

			if ( ! $connection || ! $connection->is_configured() ) {
				$this->errors[] = esc_html__( 'Square account connection is missing.', 'charitable' );
				return false;
			}

			if ( ! $connection->is_valid() ) {
				$this->errors[] = esc_html__( 'Square account connection is invalid.', 'charitable' );
				return false;
			}

			if ( ! $connection->is_currency_matched() ) {
				$this->errors[] = esc_html__( 'The currency associated with the payment is not valid for the provided business location.', 'charitable' );
				return false;
			}

			// We need to get the payment id from the donation.
			$payment_id = $donation->get_gateway_payment_id();
			if ( ! $payment_id ) {
				return false;
			}

			return true;
		}

		/**
		 * Process a refund initiated in the WordPress dashboard.
		 *
		 * @since  1.8.7
		 * @since  1.8.8.4
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean
		 */
		public static function refund_donation_from_dashboard( $donation_id ) {
			// Debug: Log that the Square Core refund method is being called.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '[Square Core] Refund method called for donation ID: ' . $donation_id );
				// phpcs:enable
			}

			$donation = charitable_get_donation( $donation_id );

			if ( ! $donation ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( '[Square Core] Refund failed - donation not found for ID: ' . $donation_id );
					// phpcs:enable
				}
				return false;
			}

			try {

				$mode       = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
				$connection = Charitable_Square_Connection::get( $mode );
				$api        = new Charitable_Square_API( $connection );

				$payment_id = $donation->get_gateway_payment_id();

				if ( ! $payment_id ) {
					return false;
				}

				$currency = $donation->get_currency();
				$amount   = self::get_donation_amount_with_fees_static( $donation );
				// Convert amount to cents.
				$amount_cents = $amount * 100;

				$args = array(
					'reason'   => 'Refunded from the WordPress dashboard',
					'amount'   => $amount_cents,
					'currency' => $currency,
				);

				$refund = $api->refund_payment( $payment_id, $args );

				if ( ! $refund ) {
					// Log the failed refund.
					$donation->log()->add(
						sprintf(
							/* translators: %s: error message. */
							__( 'Square refund failed: %s', 'charitable' ),
							$api->get_errors()
						)
					);
					return false;
				}
				// Log the refund.
				$donation->log()->add(
					sprintf(
						/* translators: %s: transaction reference. */
						__( 'Square refund successful. Refund transaction ID: %s', 'charitable' ),
						$refund->getId()
					)
				);

			} catch ( Exception $e ) {
				$donation->log()->add(
					sprintf(
						/* translators: %s: error message. */
						__( 'Square refund failed: %s', 'charitable' ),
						$e->getMessage()
					)
				);
				return false;
			}
		}

		/**
		 * Get the donation amount including fees if fee relief is enabled.
		 *
		 * @since  1.8.8.4
		 *
		 * @param  Charitable_Donation $donation The donation object.
		 * @return float
		 */
		private static function get_donation_amount_with_fees_static( $donation ) {
			if ( ! $donation ) {
				return 0.0;
			}

			// Check if fee relief is enabled and donor opted to cover fees.
			if ( $donation->get( 'cover_fees' ) ) {
				return \Charitable_Currency::get_instance()->cast_to_decimal_format( $donation->get( 'total_donation_with_fees' ) );
			}

			// Return the standard donation amount.
			return (float) $donation->get_total_donation_amount( true );
		}

		/**
		 * Cancel a subscription.
		 *
		 * To cancel a subscription, you need to:
		 * Create a CancelSubscriptionsRequest object
		 * Pass it to the subscriptions->cancel() method
		 * Handle the response to determine success or failure
		 *
		 * @since  1.8.7
		 *
		 * @param  boolean                       $cancelled Whether the subscription was cancelled successfully in the gateway.
		 * @param  Charitable_Recurring_Donation $donation  The recurring donation object.
		 * @return boolean
		 */
		public static function cancel_subscription( $cancelled, $donation ) {

			$subscription_id = $donation->get_gateway_subscription_id();

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'cancel_subscription - subscription_id: ' . $subscription_id );
				// phpcs:enable
			}

			if ( ! $subscription_id ) {
				return false;
			}

			$mode       = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$connection = Charitable_Square_Connection::get( $mode );
			$api        = new Charitable_Square_API( $connection );

			$result = $api->process_subscription_cancellation( $subscription_id );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'cancel_subscription - result: ' . $result );
				// phpcs:enable
			}

			if ( $result ) {
				$donation->log()->add( __( 'Subscription initialised for cancellation in Square.', 'charitable' ) );
				update_post_meta( $donation->ID, '_square_cancelled_to_be_confirmed', 'true' );
				return true;
			} else {
				$errors  = $api->get_errors();
				$message = ! empty( $errors ) ? implode( ', ', $errors ) : __( 'Unknown error.', 'charitable' );

				$donation->log()->add(
					sprintf(
						/* translators: %s: error message. */
						__( 'Square cancellation failed: %s', 'charitable' ),
						$message
					)
				);

				return false;
			}
		}

		/**
		 * Sets up the API.
		 *
		 * This sets the API key, specifies an API version to use, and also
		 * sets the App info.
		 *
		 * This function is deprecated.
		 *
		 * @since  1.8.7
		 *
		 * @param  string|null $api_key The API key. Might be the access token. (Maybe later: If null, will use the secret key).
		 * @return boolean
		 */
		public function setup_api( $api_key = null ) {
			return true;
		}


		/**
		 * Determine if the Square addon should be shown on the frontend if no keys are found
		 *
		 * @param  array[] $active_gateways The list of registered gateways.
		 * @return string[]
		 * @since   1.8.7
		 * @version 1.8.7 - not currently used.
		 */
		public function maybe_active_public_gateway( $active_gateways ) {

			if ( ! isset( $active_gateways['square_core'] ) ) {
				return $active_gateways;
			}

			if ( is_admin() ) {
				// if this is the admin backend don't mess with this list.
				return $active_gateways;
			}

			global $post;

			if ( false === $post || ! isset( $post->ID ) ) {
				return $active_gateways;
			}

			// ok, this is being loaded as an active gateway - but do we have any keys?
			$keys = $this->get_keys();

			/* Make sure that the keys are set, otherwise there will likely be JS error in the template that will effect other JS generated by the plugin on the page. */
			if ( empty( $keys['secret_key'] ) || empty( $keys['public_key'] ) ) {
				charitable_get_notices()->add_error( __( 'Missing keys for Square payment gateway. Unable to proceed with payment.', 'charitable' ) );
				unset( $active_gateways['square_core'] );
			}

			return $active_gateways;
		}

		/**
		 * Enqueue Square Web Payments SDK scripts.
		 * Only load on donation forms.
		 *
		 * @since  1.8.7
		 *
		 * @return void
		 */
		public function enqueue_square_scripts() {
			// Check if Square Core is active before loading scripts.
			if ( ! self::is_square_core_active() ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( '[Charitable Square Core] Scripts not loaded - Square Core is not active' );
					// phpcs:enable
				}
				return;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '[Charitable Square Core] Loading Square Core scripts - gateway is active' );
				// phpcs:enable
			}

			if ( ! is_admin() ) {

				$connection = Charitable_Square_Connection::get();

				if ( ! $connection || ! $connection->is_usable() ) {
					return;
				}

				// Get current mode and debug information.
				$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';

				// Ensure the JavaScript reference in your HTML is pointing to the correct domain.

				$script_url = $mode === 'test' ? 'https://sandbox.web.squarecdn.com/v1/square.js' : 'https://web.squarecdn.com/v1/square.js';

				/* Register Square JS script. */
				wp_register_script(
					'square-payments-sdk',
					$script_url,
					array(),
					'1.0.0',
					true
				);

				$dependencies = array( 'charitable-script', 'square-payments-sdk', 'jquery' );

				wp_register_script(
					'charitable',
					charitable()->get_path( 'assets', false ) . 'js/square/charitable-square.js',
					$dependencies,
					'1.0.0',
					true
				);

				/**
				 * This filter allows to set a card configuration and styles.
				 *
				 * @since   1.8.7.
				 * @version 1.8.7 removed 'billing_details' => $this->get_mapped_contact_fields( $forms ),
				 *
				 * @param array $card_config Configuration and style options.
				 */
				$card_config = (array) apply_filters( 'charitable_square_frontend_enqueues_card_config', array() );

				wp_localize_script(
					'charitable',
					'CHARITABLE_SQUARE_VARS',
					array(
						'client_id'         => $connection->get_client_id(),
						'location_id'       => Charitable_Square_Helpers::get_location_id(),
						'card_config'       => $card_config,
						'enable_apple_pay'  => false,
						'enable_google_pay' => false,
						'i18n'              => array(
							'missing_sdk_script'   => esc_html__( 'Square.js failed to load properly.', 'charitable' ),
							'general_error'        => esc_html__( 'An unexpected Square SDK error has occurred.', 'charitable' ),
							'missing_creds'        => esc_html__( 'Client ID and/or Location ID is incorrect.', 'charitable' ),
							'card_init_error'      => esc_html__( 'Initializing Card failed.', 'charitable' ),
							'token_process_fail'   => esc_html__( 'Tokenization of the payment card failed.', 'charitable' ),
							'token_status_error'   => esc_html__( 'Tokenization failed with status:', 'charitable' ),
							'buyer_verify_error'   => esc_html__( 'The verification was not successful. An issue occurred while verifying the buyer.', 'charitable' ),
							'empty_details'        => esc_html__( 'Please fill out payment details to continue.', 'charitable' ),
							'wallet_not_supported' => esc_html__( 'Recurring payments can\'t be processed using Google Pay or Apple Pay. Please enter your card details.', 'charitable' ),
						),
					)
				);

				wp_enqueue_script( 'charitable' );

				// Debug information.
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Test / Square Mode: ' . $mode );
					error_log( 'Application ID: ' . $this->get_square_application_id( $mode ) );
					// phpcs:enable
				}
			}
		}

		/**
		 * Get the Square application ID.
		 *
		 * @since  1.8.7
		 *
		 * @param string $mode The mode to get the application ID for (test or live).
		 * @return string
		 */
		private function get_square_application_id( $mode = 'test' ) {
			if ( 'sandbox' === $mode ) {
				$mode = 'test';
			}

			$option_value = charitable_get_option( array( 'gateways_square', $mode, 'application_id' ) );

			return $option_value;
		}

		/**
		 * Get the Square location ID.
		 * Might be deprecated.
		 *
		 * @since  1.8.7
		 *
		 * @param string $mode The mode to get the location ID for (test or live).
		 * @return string
		 */
		private function get_square_location_id( $mode = 'test' ) {
			if ( empty( $mode ) ) {
				$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			}
			if ( 'sandbox' === $mode ) {
				$mode = 'test';
			}

			$option_value = charitable_get_option( array( 'gateways_square', $mode, 'location' ) );
			return $option_value;
		}

		/**
		 * Retrieve the Disconnect button.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 * @param bool   $wrap Optional. Wrap a button HTML element or not.
		 *
		 * @return string
		 */
		private function get_disconnect_button( string $mode, bool $wrap = true ): string {

			$button = sprintf(
				'<a class="charitable-btn charitable-btn-md charitable-btn-light-grey" href="%1$s" title="%2$s">%3$s</a>',
				esc_url( $this->connect->get_disconnect_url( $mode ) ),
				esc_attr__( 'Disconnect Square account', 'charitable' ),
				esc_html__( 'Disconnect', 'charitable' )
			);

			return $wrap ? '<p class="charitable-square-disconnect-btn">' . $button . '</p>' : $button;
		}

		/**
		 * Retrieve the Refresh tokens button.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		private function get_refresh_button( string $mode ): string {

			return sprintf(
				'<button class="charitable-btn charitable-btn-md charitable-btn-light-grey charitable-square-refresh-btn" type="button" data-mode="%1$s" data-url="%2$s" title="%3$s">%4$s</button>',
				esc_attr( $mode ),
				esc_url( $this->get_settings_page_url() ),
				esc_attr__( 'Refresh connection tokens', 'charitable' ),
				esc_html__( 'Refresh tokens', 'charitable' )
			);
		}

		/**
		 * Retrieve the Error icon emoji.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		private function get_error_icon(): string {

			return '<span class="charitable-error-icon"></span>';
		}

		/**
		 * Get the settings page URL.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_settings_page_url() {

			return add_query_arg(
				array(
					'page'  => 'charitable-settings',
					'tab'   => 'gateways',
					'group' => 'gateways_square_core',
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Create instance of the class.
		 *
		 * @since 1.8.7
		 *
		 * @return Charitable_Gateway_Square
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Check the Square token status and display admin notice if needed.
		 *
		 * @since 1.8.7
		 */
		public function check_token_status() {
			global $wpdb;

			// Only run on Square settings page.
			if ( ! is_admin() ) {
				return;
			}

			// If the error notice is already set, do not run this again.
			$option = get_option( 'charitable_square_connection_error_notice' );
			if ( ! empty( $option ) ) {
				// If the error notice is already set, do not run this again.
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'Square token status check already performed. Skipping.' );
					// phpcs:enable
				}
				return;
			}

			$mode       = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$connection = Charitable_Square_Connection::get( $mode );
			$api        = new Charitable_Square_API( $connection );

			if ( isset( $_GET['test_delete'] ) ) { //phpcs:ignore
				$status['is_valid'] = '';

				$this->generate_admin_notice_for_square_connection_error( __( 'Your Square connection has expired or been revoked. Please reconnect to continue accepting donations.', 'charitable' ), 'square_connection_error' );
			}

			// Only check if we have a configured connection.
			if ( ! $connection || ! $connection->is_configured() ) {
				return;
			}

			$status = $api->check_token_status();

			if ( charitable_is_debug( 'square' ) ) {
				error_log( 'Square token status check: ' . print_r( $status, true ) ); //phpcs:ignore
			}

			if ( ! $status['is_valid'] ) {

				if ( charitable_is_debug( 'square' ) ) {
					error_log( 'Square token status check failed: ' . $status['message'] ); //phpcs:ignore
				}

				if ( $connection ) {
					// Delete the transient to ensure we can recreate the error message.
					$connections = (array) get_option( 'charitable_square_connections', array() );
					unset( $connections[ $mode ] );

					empty( $connections ) ? delete_option( 'charitable_square_connections' ) : update_option( 'charitable_square_connections', $connections );

					$settings = get_option( 'charitable_settings' );

					$settings['gateways_square'][ $mode ] = array();

					$data = array(
						'option_value' => maybe_serialize( $settings ),
					);

					$where = array(
						'option_name' => 'charitable_settings',
					);

					$ret = $wpdb->update( //phpcs:ignore
						$wpdb->options,
						$data,
						$where,
						array( '%s' ),
						array( '%s' )
					);

				}

				// Are in live mode (production)?
				if ( ! charitable_get_option( 'test_mode' ) ) {
					Charitable_Square_Connect::get_instance()->unschedule_refresh();
				}

				// Webhooks are now manually configured in Square dashboard - no need to reset.
				// Charitable_Square_Helpers::reset_webhook_configuration( true );.

				// Not needed because this as stored in the "connection" that was wiped above.
				$api->set_location_id( '', $mode );
				$api->set_location_currency( '', $mode );
				$api->detete_transients( $mode );

				// Use a regex to extract the value between the quotes after "detail".
				preg_match( '/"detail":"([^"]+)"/', $status['message'], $matches );
				$message = isset( $matches[1] ) ? $matches[1] : '';
				// Get the Status code.
				preg_match( '/Status code: (\d+)/', $status['message'], $matches );
				$status_code = isset( $matches[1] ) ? $matches[1] : '';

				$this->generate_admin_notice_for_square_connection_error( $message, $status_code, 'square_connection_error' );

				// redirect to the settings page.
				if ( ! wp_doing_ajax() ) {
					wp_safe_redirect( $this->get_settings_page_url() );
						exit;
				}
			}
		}

		/**
		 * Generate admin notice for Square connection error.
		 *
		 * @since 1.8.7
		 *
		 * @param string $message    The error message.
		 * @param string $status_code The status code.
		 * @param string $notice_key The notice key.
		 */
		private function generate_admin_notice_for_square_connection_error( $message = '', $status_code = '', $notice_key = '' ) {
			// Store the notice in the database.
			$admin_notice = array(
				'message'     => $message,
				'status_code' => $status_code,
				'time_added'  => time(),
			);

			update_option( 'charitable_square_connection_error_notice', $admin_notice );
		}

		/**
		 * Check if this is the Square settings page.
		 *
		 * @since 1.8.7
		 *
		 * @return boolean
		 */
		private function is_square_settings_page() {
			return charitable_is_admin_screen( 'settings' ) && isset( $_GET['group'] ) && 'gateways_square_core' === $_GET['group']; //phpcs:ignore
		}

		/**
		 * Check if we should clear the mode connection warning.
		 *
		 * @since 1.8.5
		 *
		 * @param array $values The values being saved.
		 * @return void
		 */
		private function maybe_clear_mode_connection_warning( $values ) {
			$warning = get_option( 'charitable_square_mode_connection_warning' );

			if ( ! $warning ) {
				return;
			}

			$mode = $warning['mode'];

			// Check if there's now a valid connection for the mode that had the warning.
			if ( ! empty( $values['gateways_square'][ $mode ]['access_token'] ) ) {
				$connection = Charitable_Square_Connection::get( $mode );

				if ( $connection && $connection->is_configured() && $connection->is_valid() ) {
					// Clear the warning since we now have a valid connection.
					delete_option( 'charitable_square_mode_connection_warning' );
				}
			}
		}

		/**
		 * Check if Square Core gateway is active.
		 *
		 * @since 1.8.7
		 *
		 * @return boolean
		 */
		public static function is_square_core_active() {
			$active_gateways = charitable_get_helper( 'gateways' )->get_active_gateways();
			$is_active       = isset( $active_gateways['square_core'] );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '[Charitable Square Core] Checking if Square Core is active: ' . ( $is_active ? 'true' : 'false' ) );
				// phpcs:enable
			}

			return $is_active;
		}

		/**
		 * Check if Square Legacy gateway is active.
		 *
		 * @since 1.8.7
		 *
		 * @return boolean
		 */
		public static function is_square_legacy_active() {
			$active_gateways = charitable_get_helper( 'gateways' )->get_active_gateways();
			$is_active       = isset( $active_gateways['square'] );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( '[Charitable Square Core] Checking if Square Legacy is active: ' . ( $is_active ? 'true' : 'false' ) );
				// phpcs:enable
			}

			return $is_active;
		}

		/**
		 * Get the donation amount including fees if fee relief is enabled.
		 *
		 * @since  1.8.8.3
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
