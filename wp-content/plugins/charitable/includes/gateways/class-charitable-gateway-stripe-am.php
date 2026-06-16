<?php
/**
 * Stripe Payment Gateway class.
 *
 * @package   Charitable/Classes/Charitable_Gateway_Stripe_AM
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.55
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway_Stripe_AM' ) ) :

	/**
	 * Stripe Gateway.
	 *
	 * @since 1.0.0
	 */
	class Charitable_Gateway_Stripe_AM extends Charitable_Gateway {

		/** The gateway ID. */
		const ID = 'stripe';

		/** The Stripe API version we are using. */
		const STRIPE_API_VERSION = '2019-03-14';

		/**
		 * Gateway badge.
		 *
		 * @var string
		 */
		protected $badge = '';

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			/**
			 * Filter the gateway name
			 *
			 * @since 1.0.0
			 *
			 * @param string $name Gateway name.
			 */
			$this->name = apply_filters( 'charitable_gateway_stripe_name', __( 'Stripe', 'charitable' ) );

			$this->defaults = [
				'label' => __( 'Stripe', 'charitable' ),
			];

			$this->badge = __( 'Recommended', 'charitable' );

			$this->recommended = true;

			$this->supports = [
				'1.3.0',
				'credit-card',
				'recurring',
				'refunds',
			];

			/* Needed for backwards compatibility with Charitable < 1.3 */
			$this->credit_card_form = true;

			add_filter( 'charitable_option_enable_stripe_checkout', array( $this, 'maybe_return_stripe_connect_setting' ), 10, 4 );
			add_filter( 'charitable_option_enable_checkout_billing_address_collection', array( $this, 'maybe_return_stripe_connect_setting' ), 10, 4 );
			add_filter( 'charitable_option_statement_descriptor', array( $this, 'maybe_return_stripe_connect_setting' ), 10, 4 );
			add_filter( 'charitable_option_statement_descriptor_custom', array( $this, 'maybe_return_stripe_connect_setting' ), 10, 4 );

			add_action( 'charitable_stripe_ipn_event', array( $this, 'maybe_process_webhook_with_stripe_connect' ), 10, 2 );

			add_filter( 'charitable_minimum_donation_amount', array( $this, 'set_minimum_donation_amount' ), 10 );
		}

		/**
		 * Register the Stripe payment gateway class.
		 *
		 * @param  string[] $gateways The list of registered gateways.
		 * @return string[]
		 * @since  1.7.0
		 */
		public static function register_gateway( $gateways ) {
			$gateways['stripe'] = 'Charitable_Gateway_Stripe';
			return $gateways;
		}

		/**
		 * Register gateway settings.
		 *
		 * @param  array $settings The existing settings to display for the Stripe settings page.
		 * @return array
		 * @since  1.7.0
		 */
		public function gateway_settings( $settings ) {

			$settings = $settings + array(
				'cc_fields_format' => [
					'title'    => __( 'Credit Card Fields Format <span class="badge beta">Beta</span>', 'charitable' ),
					'type'     => 'select',
					'priority' => 4,
					'class'    => 'general-settings',
					'default'  => 'below_amount_selection',
					'options'  => array(
						''         => __( 'Single field', 'charitable' ),
						'multiple' => __( 'Multiple fields', 'charitable' ),
					),
					'help'     => __( 'Single field (default) provides the card, expiration date, zip code, and CVC fields in one field. Multiple fields (beta) are seperated.', 'charitable' ),
				],
			);

			if ( ( charitable_is_debug() ) || charitable_is_admin_debug() ) {

				if ( charitable()->is_stripe_connect_addon() ) {

					$settings = $settings + array(
						'enable_stripe_checkout' => [
							'type'     => 'checkbox',
							'title'    => __( 'Use Stripe Checkout', 'charitable' ),
							'priority' => 4,
							'help'     => __( 'When you enable Stripe Checkout, donors enter their credit card details into a secure hosted payment page managed by Stripe. <a href="https://stripe.com/docs/checkout" target="_blank">https://stripe.com/docs/checkout</a>', 'charitable' ),
						],
						'section_statement_descriptor' => [
							'type'     => 'heading',
							'title'    => __( 'Statement Descriptor', 'charitable' ),
							'priority' => 25,
						],
						'statement_descriptor'         => [
							'type'     => 'select',
							'title'    => __( 'Format', 'charitable' ),
							'priority' => 26,
							'help'     => __( 'Customize the statement descriptor that will appear on donors\' bank statements.', 'charitable' ),
							'default'  => 'auto',
							'options'  => [
								'auto'   => __( 'Use campaign title', 'charitable' ),
								'custom' => __( 'Set a custom descriptor', 'charitable' ),
							],
						],
						'statement_descriptor_custom'  => [
							'type'     => 'text',
							'title'    => __( 'Custom Statement Descriptor', 'charitable' ),
							'priority' => 26,
							'help'     => __( 'A custom statement descriptor up to 22 characters long.', 'charitable' ),
							'attrs'    => [
								'data-trigger-key'   => '#charitable_settings_gateways_stripe_statement_descriptor',
								'data-trigger-value' => 'custom',
								'maxlength'          => 22,
							],
						],
					);

				}

				return array_merge(
					$settings,
					array(
						'section_live_mode' => [
							'title'    => __( 'Live Settings', 'charitable' ),
							'type'     => 'heading',
							'priority' => 5,
						],
						'live_secret_key'   => [
							'type'     => 'text',
							'title'    => __( 'Live Secret Key', 'charitable' ),
							'priority' => 6,
							'class'    => 'wide',
						],
						'live_public_key'   => [
							'type'     => 'text',
							'title'    => __( 'Live Publishable Key', 'charitable' ),
							'priority' => 7,
							'class'    => 'wide',
						],
						'section_test_mode' => [
							'title'    => __( 'Test Settings', 'charitable' ),
							'type'     => 'heading',
							'priority' => 10,
						],
						'test_secret_key'   => [
							'type'     => 'text',
							'title'    => __( 'Test Secret Key', 'charitable' ),
							'priority' => 11,
							'class'    => 'wide',
						],
						'test_public_key'   => [
							'type'     => 'text',
							'title'    => __( 'Test Publishable Key', 'charitable' ),
							'priority' => 12,
							'class'    => 'wide',
						],
						'api_description'   => array(
							'type'     => 'content',
							'title'    => __( 'Connection Status', 'charitable' ),
							'content'  => $this->get_connection_status_content(),
							'priority' => 21,
						),
					)
				);

			} else {

				$stripe_settings = array(
					'api_description' => array(
						'type'     => 'content',
						'title'    => __( 'Connection Status', 'charitable' ),
						'content'  => $this->get_connection_status_content(),
						'priority' => 5,
					),
				);

				if ( charitable_get_option( 'test_mode' ) || ( isset( $force_test_mode ) && $force_test_mode ) ) {
					$mode = 'test';
				} else {
					$mode = 'live';
				}

				if ( $this->check_keys_exist( $mode ) && false === charitable_using_stripe_connect() && charitable()->is_stripe_connect_addon() ) {
					// if there ARE keys and the Stripe addon is active... BUT WE ARE NOT USING 1.70 Stripe Connect... so this is most likely previous stripe addon keys.
					$stripe_settings = $stripe_settings + array(
						'stripe_modify_keys' => [
							'title'    => '',
							'type'     => 'content',
							'class'    => 'manually-modify',
							'content'  => '<a href="#" data-charitable-args="mode=' . $mode . '" data-charitable-action="show-stripe-keys" class="manually-modify-keys" id="charitable-modify-keys">Manually Modify Keys</a>',
							'priority' => 15,
						],
					);
				}

				if ( charitable()->is_stripe_connect_addon() ) {

					$stripe_settings = $stripe_settings + array(
						'enable_stripe_checkout'       => [
							'type'     => 'checkbox',
							'title'    => __( 'Use Stripe Checkout', 'charitable' ),
							'priority' => 16,
							'help'     => __( 'When you enable Stripe Checkout, donors enter their credit card details into a secure hosted payment page managed by Stripe. <a href="https://stripe.com/docs/checkout" target="_blank">https://stripe.com/docs/checkout</a>', 'charitable' ),
						],
						'enable_checkout_billing_address_collection' => [
							'type'     => 'checkbox',
							'title'    => __( 'Require Billing Address in Checkout', 'charitable' ),
							'priority' => 17,
							'help'     => __( 'If enabled, donors will always be asked to enter their billing address in Checkout.', 'charitable' ),
							'attrs'    => [
								'data-trigger-key'   => '#charitable_settings_gateways_stripe_enable_stripe_checkout',
								'data-trigger-value' => 'checked',
							],
						],
						'section_statement_descriptor' => [
							'type'     => 'heading',
							'title'    => __( 'Statement Descriptor', 'charitable' ),
							'priority' => 20,
						],
						'statement_descriptor'         => [
							'type'     => 'select',
							'title'    => __( 'Format', 'charitable' ),
							'priority' => 21,
							'help'     => __( 'Customize the statement descriptor that will appear on donors\' bank statements.', 'charitable' ),
							'default'  => 'auto',
							'options'  => [
								'auto'   => __( 'Use campaign title', 'charitable' ),
								'custom' => __( 'Set a custom descriptor', 'charitable' ),
							],
						],
						'statement_descriptor_custom'  => [
							'type'     => 'text',
							'title'    => __( 'Custom Statement Descriptor', 'charitable' ),
							'priority' => 22,
							'help'     => __( 'A custom statement descriptor up to 22 characters long.', 'charitable' ),
							'attrs'    => [
								'data-trigger-key'   => '#charitable_settings_gateways_stripe_statement_descriptor',
								'data-trigger-value' => 'custom',
								'maxlength'          => 22,
							],
						],
					);
				}

				if ( charitable_using_stripe_connect() ) {

					unset( $stripe_settings['section_live_mode'] );
					unset( $stripe_settings['live_secret_key'] );
					unset( $stripe_settings['live_public_key'] );
					unset( $stripe_settings['section_test_mode'] );
					unset( $stripe_settings['test_secret_key'] );
					unset( $stripe_settings['test_public_key'] );

				}

				if ( ( charitable_is_debug() ) || charitable_is_admin_debug() ) {
					$stripe_settings = $stripe_settings + array(
						'section_stripe_checkout' => [
							'title'    => __( 'Stripe Checkout INTERNAL', 'charitable' ),
							'type'     => 'heading',
							'priority' => 4,
						],
						'section_live_mode'       => [
							'title'    => __( 'Live Settings', 'charitable' ),
							'type'     => 'heading',
							'priority' => 5,
						],
						'live_secret_key'         => [
							'type'     => 'text',
							'title'    => __( 'Live Secret Key', 'charitable' ),
							'priority' => 6,
							'class'    => 'wide',
						],
						'live_public_key'         => [
							'type'     => 'text',
							'title'    => __( 'Live Publishable Key', 'charitable' ),
							'priority' => 7,
							'class'    => 'wide',
						],
						'section_test_mode'       => [
							'title'    => __( 'Test Settings', 'charitable' ),
							'type'     => 'heading',
							'priority' => 10,
						],
						'test_secret_key'         => [
							'type'     => 'text',
							'title'    => __( 'Test Secret Key', 'charitable' ),
							'priority' => 11,
							'class'    => 'wide',
						],
						'test_public_key'         => [
							'type'     => 'text',
							'title'    => __( 'Test Publishable Key', 'charitable' ),
							'priority' => 12,
							'class'    => 'wide',
						],
					);
				}

				return array_merge( $settings, $stripe_settings );

			}
		}

		/**
		 * Get the Stripe logo.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_logo() {
			return '<svg width="49" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.4718 10.3338c0-3.41791-1.6696-6.11484-4.8607-6.11484-3.2045 0-5.1434 2.69693-5.1434 6.08814 0 4.0187 2.289 6.048 5.5743 6.048 1.6023 0 2.8141-.3604 3.7296-.8678v-2.6702c-.9155.4539-1.9658.7343-3.2987.7343-1.3061 0-2.464-.4539-2.6121-2.0294h6.5841c0-.1735.0269-.8678.0269-1.1882Zm-6.6514-1.26838c0-1.50868.929-2.13618 1.7773-2.13618.8213 0 1.6965.6275 1.6965 2.13618h-3.4738Zm-8.5499-4.84646c-1.3195 0-2.1678.61415-2.639 1.04139l-.1751-.82777h-2.9621V20l3.3661-.7076.0134-3.7784c.4847.3471 1.1984.8411 2.3832.8411 2.4102 0 4.6048-1.9225 4.6048-6.1548-.0134-3.87186-2.235-5.98134-4.5913-5.98134Zm-.8079 9.19894c-.7944 0-1.2656-.2804-1.5888-.6275l-.0134-4.95328c.35-.38719.8348-.65421 1.6022-.65421 1.2253 0 2.0735 1.36182 2.0735 3.11079 0 1.7891-.8347 3.1242-2.0735 3.1242Zm-9.6001-9.98666 3.3796-.72096V0l-3.3796.70761v2.72363Zm0 1.01469h3.3796V16.1282h-3.3796V4.44593Zm-3.6219.98798-.2154-.98798h-2.9083V16.1282h3.3661V8.21095c.7944-1.02804 2.1408-.84112 2.5582-.69426V4.44593c-.4309-.16022-2.0062-.45394-2.8006.98798Zm-6.7322-3.88518-3.2853.69426-.01346 10.69421c0 1.976 1.49456 3.4313 3.48726 3.4313 1.1041 0 1.912-.2003 2.3563-.4406v-2.7103c-.4309.1736-2.5583.7877-2.5583-1.1882V7.28972h2.5583V4.44593h-2.5583l.0135-2.8972ZM3.40649 7.83712c0-.5207.43086-.72096 1.14447-.72096 1.0233 0 2.31588.30707 3.33917.85447V4.83311c-1.11755-.44059-2.22162-.61415-3.33917-.61415C1.81769 4.21896 0 5.63418 0 7.99733c0 3.68487 5.11647 3.09747 5.11647 4.68627 0 .6141-.53858.8144-1.29258.8144-1.11755 0-2.54477-.4539-3.675782-1.0681v3.1776c1.252192.534 2.517842.761 3.675782.761 2.80059 0 4.72599-1.3752 4.72599-3.765-.01346-3.97867-5.14339-3.27106-5.14339-4.76638Z" fill="#635BFF"/></svg>';
		}

		/**
		 * Determine if Stripe is connected.
		 *
		 * @since  1.8.1.12
		 *
		 * @return int
		 */
		public function maybe_stripe_connected() {
			return $this->check_keys_exist( 'live' ) || $this->check_keys_exist( 'test' );
		}

		/**
		 * Get connection status content.
		 *
		 * @since 1.8.1.12
		 *
		 * @param bool $force_test_mode Whether to force test mode.
		 *
		 * @return bool
		 */
		public function get_connection_status_content( $force_test_mode = false ) {

			if ( charitable_get_option( 'test_mode' ) || $force_test_mode ) {
				$mode = 'test';
			} else {
				$mode = 'live';
			}

			$html = '';

			if ( ! $this->check_keys_exist( $mode ) || ! charitable_using_stripe_connect() ) {
				$html .= $this->get_stripe_connect_button();
			} else {

				if ( ! charitable_is_pro() ) {
					$html .= '<div class="charitable-inline-notice info">
							<p>
							<strong>' . esc_html__( 'Pay as you go pricing:', 'charitable' ) . '</strong> ' .
							sprintf(
								/* translators: %1$s: opening link tag, %2$s: closing link tag */
								esc_html__( '3%% per transaction + Stripe fees. %1$sUpgrade to Pro%2$s for no added fees and priority support.', 'charitable' ),
								'<a target="_blank" href="' . esc_url( charitable_pro_upgrade_url( 'gateway-stripe' ) ) . '">',
								'</a>'
							) . '</p>
						</div>';
				}

				$html .= '<p id="wpcharitable-stripe-auth-error-account-actions" style="display: block;">' . sprintf(
					/* translators: %1$s Stripe payment mode. %2$s Opening anchor tag for reconnecting to Stripe, do not translate. %3$s Opening anchor tag for disconnecting Stripe, do not translate. %4$s Closing anchor tag, do not translate. */
					__( '%1$sDisconnect this account%2$s.', 'charitable' ),
					'<strong>Connected in ' . $mode . ' mode</strong>. <a href="' . esc_url( $this->get_stripe_disconnect_url() ) . '" class="wpcharitable-disconnect-link">',
					'</a>'
				) . '</p>';

				$html .= '<p id="wpcharitable-stripe-activated-account-actions" style="display: none;">' . sprintf(
					/* translators: %1$s Stripe payment mode. %2$s Opening anchor tag for reconnecting to Stripe, do not translate. %3$s Opening anchor tag for disconnecting Stripe, do not translate. %4$s Closing anchor tag, do not translate. */
					__( 'Your Stripe account is connected in %1$s mode. %2$sDisconnect this account%3$s.', 'charitable' ),
					'<strong>' . $mode . '</strong>',
					'<a href="' . esc_url( $this->get_stripe_disconnect_url() ) . '" class="wpcharitable-disconnect-link">',
					'</a>'
				) . '</p>';

				$html .= '<p id="wpcharitable-stripe-unactivated-account-actions" style="display: none;"><a href="' . esc_url( $this->get_stripe_disconnect_url() ) . '">' .
				__( 'Disconnect temporary account', 'charitable' ) .
				'</a></p>';
			}

			return $html;
		}

		/**
		 * Generate the Stripe Connect button.
		 *
		 * @since 1.8.2
		 *
		 * @param string $redirect_url The URL to redirect to after connecting.
		 * @return string
		 */
		public function get_stripe_connect_button( $redirect_url = '' ) {

			$url = $this->get_stripe_connect_url( $redirect_url );

			ob_start();
			?>

			<a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr__( 'Connect with Stripe', 'charitable' ); ?>" class="wpcharitable-stripe-connect">
				<span>
				<?php
				/* translators: Text before Stripe logo for "Connect with Stripe" button. */
				esc_html_e( 'Connect with', 'charitable' );
				?>
				</span>

				<svg width="49" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.4718 10.3338c0-3.41791-1.6696-6.11484-4.8607-6.11484-3.2045 0-5.1434 2.69693-5.1434 6.08814 0 4.0187 2.289 6.048 5.5743 6.048 1.6023 0 2.8141-.3604 3.7296-.8678v-2.6702c-.9155.4539-1.9658.7343-3.2987.7343-1.3061 0-2.464-.4539-2.6121-2.0294h6.5841c0-.1735.0269-.8678.0269-1.1882Zm-6.6514-1.26838c0-1.50868.929-2.13618 1.7773-2.13618.8213 0 1.6965.6275 1.6965 2.13618h-3.4738Zm-8.5499-4.84646c-1.3195 0-2.1678.61415-2.639 1.04139l-.1751-.82777h-2.9621V20l3.3661-.7076.0134-3.7784c.4847.3471 1.1984.8411 2.3832.8411 2.4102 0 4.6048-1.9225 4.6048-6.1548-.0134-3.87186-2.235-5.98134-4.5913-5.98134Zm-.8079 9.19894c-.7944 0-1.2656-.2804-1.5888-.6275l-.0134-4.95328c.35-.38719.8348-.65421 1.6022-.65421 1.2253 0 2.0735 1.36182 2.0735 3.11079 0 1.7891-.8347 3.1242-2.0735 3.1242Zm-9.6001-9.98666 3.3796-.72096V0l-3.3796.70761v2.72363Zm0 1.01469h3.3796V16.1282h-3.3796V4.44593Zm-3.6219.98798-.2154-.98798h-2.9083V16.1282h3.3661V8.21095c.7944-1.02804 2.1408-.84112 2.5582-.69426V4.44593c-.4309-.16022-2.0062-.45394-2.8006.98798Zm-6.7322-3.88518-3.2853.69426-.01346 10.69421c0 1.976 1.49456 3.4313 3.48726 3.4313 1.1041 0 1.912-.2003 2.3563-.4406v-2.7103c-.4309.1736-2.5583.7877-2.5583-1.1882V7.28972h2.5583V4.44593h-2.5583l.0135-2.8972ZM3.40649 7.83712c0-.5207.43086-.72096 1.14447-.72096 1.0233 0 2.31588.30707 3.33917.85447V4.83311c-1.11755-.44059-2.22162-.61415-3.33917-.61415C1.81769 4.21896 0 5.63418 0 7.99733c0 3.68487 5.11647 3.09747 5.11647 4.68627 0 .6141-.53858.8144-1.29258.8144-1.11755 0-2.54477-.4539-3.675782-1.0681v3.1776c1.252192.534 2.517842.761 3.675782.761 2.80059 0 4.72599-1.3752 4.72599-3.765-.01346-3.97867-5.14339-3.27106-5.14339-4.76638Z" fill="#fff"/></svg>
			</a>

			<style>
			.wpcharitable-stripe-connect {
				color: #fff;
				font-size: 15px;
				font-weight: bold;
				text-decoration: none;
				line-height: 1;
				background-color: #635bff;
				border-radius: 3px;
				padding: 10px 20px;
				display: inline-flex;
				align-items: center;
			}

			.wpcharitable-stripe-connect:focus,
			.wpcharitable-stripe-connect:hover {
				color: #fff;
				background-color: #0a2540;
			}

			.wpcharitable-stripe-connect:focus {
				outline: 0;
				box-shadow: inset 0 0 0 1px #fff, 0 0 0 1.5px #0a2540;
			}

			.wpcharitable-stripe-connect svg {
				margin-left: 5px;
			}
			</style>

			<?php
			return ob_get_clean();
		}

		/**
		 * Get the URL to connect to Stripe.
		 *
		 * @since 1.8.0
		 *
		 * @param string $redirect_url The URL to redirect to after connecting.
		 * @return string
		 */
		public function get_stripe_connect_url( $redirect_url = '' ) {
			if ( empty( $redirect_url ) ) {

				$redirect_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_stripe',
					),
					admin_url( 'admin.php' )
				);

			}

			return add_query_arg(
				array(
					'live_mode'         => (int) ! charitable_get_option( 'test_mode' ), // (int) ! $this->is_test_mode(),
					'state'             => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 100, wp_rand(), STR_PAD_BOTH ),
					'customer_site_url' => urlencode( $redirect_url ),
				),
				'https://wpcharitable.com/stripe-connect/?wpcharitable_gateway_connect_init=stripe_connect' // todo: filter market site url.
			);
		}

		/**
		 * Get the URL to disconnect from Stripe.
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		public function get_stripe_disconnect_url() {
			return add_query_arg(
				array(
					'wpcharitable-stripe-disconnect' => true,
					'_wpnonce'                       => wp_create_nonce(
						'wpcharitable-stripe-connect-disconnect'
					),
					'tab'                            => 'gateways',
					'page'                           => 'charitable-settings',
					'group'                          => 'gateways_stripe',
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Returns the current gateway's ID.
		 *
		 * @return string
		 * @since  1.0.3
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Return the keys to use.
		 *
		 * This will return the test keys if test mode is enabled. Otherwise, returns
		 * the production keys.
		 *
		 * @since  1.7.0
		 *
		 * @param  boolean $force_test_mode Forces the test API keys to be used.
		 * @return string[]
		 */
		public function get_keys( $force_test_mode = false ) {
			$keys = [];

			if ( charitable_get_option( 'test_mode' ) || $force_test_mode ) {
				$keys['secret_key'] = trim( $this->get_value( 'test_secret_key' ) );
				$keys['public_key'] = trim( $this->get_value( 'test_public_key' ) );
			} else {
				$keys['secret_key'] = trim( $this->get_value( 'live_secret_key' ) );
				$keys['public_key'] = trim( $this->get_value( 'live_public_key' ) );
			}

			return $keys;
		}

		/**
		 * Check if Stripe keys exist.
		 *
		 * @since  1.7.0
		 * @param  string $mode The mode to check for keys.
		 * @return boolean
		 */
		public function check_keys_exist( $mode ) {

			$secret_key      = trim( $this->get_value( $mode . '_secret_key' ) );
			$publishable_key = trim( $this->get_value( $mode . '_public_key' ) );

			if ( ! empty( $secret_key ) && ! empty( $publishable_key ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Return the submitted value for a gateway field.
		 *
		 * @since  1.7.0
		 *
		 * @param  string  $key The key of the value we want to get.
		 * @param  mixed[] $values An values in which to search.
		 * @return string|false
		 */
		public function get_gateway_value( $key, $values ) {
			if ( isset( $values['gateways']['stripe'][ $key ] ) ) {
				return $values['gateways']['stripe'][ $key ];
			}

			return false;
		}

		/**
		 * Return the submitted value for a gateway field.
		 *
		 * @since  1.7.0
		 *
		 * @param  string                        $key The key of the value we want to get.
		 * @param  Charitable_Donation_Processor $processor The Donation Processor helper object.
		 * @return string|false
		 */
		public function get_gateway_value_from_processor( $key, Charitable_Donation_Processor $processor ) {
			return $this->get_gateway_value( $key, $processor->get_donation_data() );
		}

		/**
		 * Add the connected account & charge owner setting to the donation form's hidden fields.
		 *
		 * @since  1.4.0
		 *
		 * @param  string[]                 $fields The hidden fields as key=>value pairs.
		 * @param  Charitable_Donation_Form $form   The donation form instance.
		 * @return string[]
		 */
		public static function add_hidden_stripe_account_field( $fields, $form ) {
			if ( ! class_exists( 'Charitable_Stripe_Connect' ) ) {
				return $fields;
			}

			$connected_account = charitable_stripe_get_connected_account_for_campaign( $form->get_campaign()->ID );

			if ( $connected_account ) {
				$fields['connected_account']    = $connected_account;
				$fields['connect_charge_owner'] = charitable_get_option( [ 'gateways_stripe', 'charge_owner' ], 'direct' );
			}

			return $fields;
		}

		/**
		 * Returns an array of credit card fields.
		 *
		 * If the gateway requires different fields, this can simply be redefined
		 * in the child class.
		 *
		 * @since  1.7.0
		 * @version 1.8.9.1
		 *
		 * @return array[]
		 */
		public function get_credit_card_fields() {
			/* If Stripe Checkout is enabled, remove the credit card fields. */
			if ( $this->get_value( 'enable_stripe_checkout' ) ) {
				return [];
			}

			$fields = parent::get_credit_card_fields();

			$cc_fields_format = empty( $this->get_value( 'cc_fields_format' ) ) ? 'standard' : $this->get_value( 'cc_fields_format' );

			if ( 'multiple' === $cc_fields_format ) {
				/* Remove all fields except for the cc_name field. */
				unset(
					$fields['cc_number'],
					$fields['cc_cvc'],
					$fields['cc_expiration']
				);

				/* Check for an existing payment intent. */
				$intent = Charitable_Stripe_Payment_Intent::init_from_session();
				$intent = false;

				ob_start();

				/**
				 * Render a template with our custom CSS. NOTE: We pass a non-empty array
				 * as the second parameter to ensure that the template is rendered immediately.
				 *
				 * @see charitable_template
				 */
				charitable_template(
					'stripe-elements.css.php',
					[ 1 ],
					'Charitable_Stripe_Template'
				);
				?>

				<div class="charitable_stripe_multiple_field">
					<label for="charitable_stripe_card_expiration_field"><?php esc_html_e( 'Expiration Date', 'charitable' ); ?></label>
					<div id="charitable_stripe_card_expiration_field">
						<!-- A Stripe Element will be inserted here -->
					</div>
				</div>
				<div class="charitable_stripe_multiple_field">
					<label for="charitable_stripe_card_number_field"><?php esc_html_e( 'Credit/Debit Number', 'charitable' ); ?></label>
					<div id="charitable_stripe_card_number_field">
						<!-- A Stripe Element will be inserted here -->
					</div>
				</div>
				<div class="charitable_stripe_multiple_field">
					<label for="charitable_stripe_card_cvc_field"><?php esc_html_e( 'CVC', 'charitable' ); ?></label>
					<div id="charitable_stripe_card_cvc_field">
						<!-- A Stripe Element will be inserted here -->
					</div>
				</div>


				<div id="charitable_stripe_card_errors" role="alert"></div>
				<input type="hidden" name="stripe_payment_method" />

				<?php

				$fields['cc_element'] = [
					'type'     => 'content',
					'content'  => ob_get_clean(),
					'priority' => 2,
				];

			} else {
				/* Remove all fields except for the cc_name field. */
				unset(
					$fields['cc_number'],
					$fields['cc_cvc'],
					$fields['cc_expiration']
				);

				/* Check for an existing payment intent. */
				$intent = Charitable_Stripe_Payment_Intent::init_from_session();
				$intent = false;

				ob_start();

				/**
				 * Render a template with our custom CSS. NOTE: We pass a non-empty array
				 * as the second parameter to ensure that the template is rendered immediately.
				 *
				 * @see charitable_template
				 */
				charitable_template(
					'stripe-elements.css.php',
					[ 1 ],
					'Charitable_Stripe_Template'
				);
				?>
				<label for="charitable_stripe_card_field"><?php esc_html_e( 'Credit/Debit Card', 'charitable' ); ?></label>
				<div id="charitable_stripe_card_field" data-secret="<?php echo esc_attr( $intent ? $intent->get( 'client_secret' ) : '' ); ?>" data-intent="<?php echo esc_attr( $intent ? $intent->get( 'id' ) : '' ); ?>"></div>
				<div id="charitable_stripe_card_errors" role="alert"></div>
				<input type="hidden" name="stripe_payment_method" />
				<?php

				$fields['cc_element'] = [
					'type'     => 'content',
					'content'  => ob_get_clean(),
					'priority' => 2,
				];

				$fields['cc_name']['attrs'] = [
					'data-input' => 'cc_name',
				];
			}

			return $fields;
		}

		/**
		 * Set up an output buffer before the donation form fields are displayed.
		 *
		 * @since  1.3.0
		 * @version 1.8.9.1
		 *
		 * @param  Charitable_Form $form Form object.
		 * @return void
		 */
		public static function setup_donation_form_field_output_buffer( Charitable_Form $form ) {
			if ( 'Charitable_Donation_Form' != get_class( $form ) ) {
				return;
			}

			ob_start();

			echo '<noscript><div class="charitable-notice charitable-form-errors">
				<ul class="charitable-notice-errors errors">
					<li>' . esc_html__( 'For security reasons, credit card donations require Javascript. Please enable Javascript in your browser before continuing.', 'charitable' ) . '</li>
				</ul><!-- charitable-notice- -->
			</div></noscript>';
		}

		/**
		 * Get the donation form fields from the buffer and remove the name attribute
		 * from cc fields.
		 *
		 * @since  1.3.0
		 * @version 1.8.9.1
		 *
		 * @param  Charitable_Form $form Form object.
		 * @return void
		 */
		public static function remove_name_attribute_from_cc_fields( Charitable_Form $form ) {
			if ( 'Charitable_Donation_Form' != get_class( $form ) ) {
				return;
			}

			$fields = ob_get_clean();
			$fields = str_replace( 'name="cc_name"', '', $fields );

			echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Validate the submitted credit card details.
		 *
		 * @since  1.7.0
		 * @since  1.4.0 Deprecated.
		 * @since  1.4.7 Restored. No longer deprecated.
		 *
		 * @param  boolean $valid Whether the donation is valid.
		 * @param  string  $gateway The chosen gateway.
		 * @param  mixed[] $values The filtered values from the donation form submission.
		 * @return boolean
		 */
		public static function validate_donation( $valid, $gateway, $values ) {
			if ( 'stripe' !== $gateway ) {
				return $valid;
			}

			$gateway = new Charitable_Gateway_Stripe_AM();
			$keys    = $gateway->get_keys();

			/* Make sure that the keys are set. */
			if ( empty( $keys['secret_key'] ) || empty( $keys['public_key'] ) ) {
				charitable_get_notices()->add_error( __( 'Missing keys for Stripe payment gateway. Unable to proceed with payment.', 'charitable' ) );
				return false;
			}

			/* If we're using Payment Intents, make sure we have a payment method. */
			if ( ! $gateway->get_value( 'enable_stripe_checkout' ) ) {
				// phpcs:disable WordPress.Security.NonceVerification.Missing
				$stripe_payment_method = isset( $_POST['stripe_payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_payment_method'] ) ) : '';
				if ( empty( $stripe_payment_method ) ) {
					// phpcs:enable WordPress.Security.NonceVerification.Missing
					charitable_get_notices()->add_error(
						__( '<strong>Missing payment details.</strong> <a href="#charitable-gateway-fields">Click here to double-check that all required payment fields are completed.</a>', 'charitable' )
					);
					return false;
				}
				// phpcs:enable WordPress.Security.NonceVerification.Missing
			}

			return $valid;
		}

		/**
		 * Checks whether the donation being processed is recurring.
		 *
		 * @since  1.3.0
		 *
		 * @param  Charitable_Donation_Processor $processor The Donation Processor helper.
		 * @return boolean
		 */
		public static function is_recurring_donation( Charitable_Donation_Processor $processor ) {
			return $processor->get_donation_data_value( 'donation_plan', false );
		}

		/**
		 * Process the donation with the gateway, seamlessly over the Stripe API.
		 *
		 * @since  1.7.0
		 *
		 * @param  mixed                         $return The result of the gateway processing.
		 * @param  int                           $donation_id The donation ID.
		 * @param  Charitable_Donation_Processor $processor The Donation Processor helper.
		 * @return boolean
		 */
		public static function process_donation( $return, $donation_id, Charitable_Donation_Processor $processor ) {
			if ( charitable_get_option( [ 'gateways_stripe', 'enable_stripe_checkout' ] ) && charitable()->is_stripe_connect_addon() ) {
				/**
				 * Filter the processor used for handling Checkout donations.
				 *
				 * @since 1.4.0
				 *
				 * @param string                        $class     The name of the Stripe gateway processor class.
				 * @param Charitable_Donation_Processor $processor The Donation Processor helper.
				 */
				$processor_class = apply_filters( 'charitable_stripe_gateway_processor_checkout', 'Charitable_Stripe_Gateway_Processor_Checkout', $processor );
			} else {
				/**
				 * Filter the processor used for handling PaymentIntent donations.
				 *
				 * @since 1.4.0
				 *
				 * @param string                        $class     The name of the Stripe gateway processor class.
				 * @param Charitable_Donation_Processor $processor The Donation Processor helper.
				 */
				$processor_class = apply_filters( 'charitable_stripe_gateway_processor_payment_intents', 'Charitable_Stripe_Gateway_Processor_Payment_Intents', $processor );
			}

			$gateway_processor = new $processor_class( $donation_id, $processor );

			/* Ensure we have a valid processor. */
			if ( ! $gateway_processor instanceof Charitable_Stripe_Gateway_Processor ) {
				$gateway_processor = new Charitable_Stripe_Gateway_Processor_Payment_Intents( $donation_id, $processor );
			}

			return $gateway_processor->run();
		}

		/**
		 * Sets up the API.
		 *
		 * This sets the API key, specifies an API version to use, and also
		 * sets the App info.
		 *
		 * @since  1.3.0
		 *
		 * @param  string|null $api_key The API key. If null, will use the secret key.
		 * @return boolean
		 */
		public function setup_api( $api_key = null ) {
			if ( is_null( $api_key ) ) {
				$keys = $this->get_keys();

				if ( ! array_key_exists( 'secret_key', $keys ) ) {
					if ( charitable_is_debug( 'stripe' ) ) {
						error_log( '[Charitable Stripe] setup_api FAILED: No secret_key in keys array.' ); // phpcs:ignore
					}
					return false;
				}

				$api_key = $keys['secret_key'];
			}

			if ( charitable_is_debug( 'stripe' ) ) {
				$key_preview = ! empty( $api_key ) ? substr( $api_key, 0, 7 ) . '...' . substr( $api_key, -4 ) : 'EMPTY';
				$test_mode   = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
				error_log( sprintf( '[Charitable Stripe] setup_api called. Mode: %s, Key: %s', $test_mode, $key_preview ) ); // phpcs:ignore
			}

			/**
			 * On certain versions of CentOS, curl requests to Stripe fail consistently.
			 * The following filter provides a way to work around this with the following:
			 *
				add_filter( 'charitable_stripe_http_curl_client', function( \Stripe\HttpClient\CurlClient $curl ) {
					$curl->setEnablePersistentConnections( false );
					return $curl;
				} );
			 *
			 * @see https://github.com/stripe/stripe-php/issues/918
			 *
			 * @since 1.4.11
			 *
			 * @param \Stripe\HttpClient\CurlClient $curl The CurlClient instance.
			 */
			$curl = apply_filters( 'charitable_stripe_http_curl_client', new \Stripe\HttpClient\CurlClient() );

			/* Allow the Curl client object to be changed. */
			\Stripe\ApiRequestor::setHttpClient( $curl );

			\Stripe\Stripe::setApiKey( $api_key );
			\Stripe\Stripe::setApiVersion( self::STRIPE_API_VERSION );
			\Stripe\Stripe::setAppInfo(
				'WordPress CharitablePlugin',
				charitable_stripe()->get_version(),
				'https://www.wpcharitable.com',
				'pp_partner_Ee8qdpxEQjiEne'
			);

			return true;
		}

		/**
		 * Check whether a particular donation can be refunded automatically in Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @param  Charitable_Donation $donation The donation object.
		 * @return boolean
		 */
		public function is_donation_refundable( Charitable_Donation $donation ) {
			$secret_key = $donation->get_test_mode( false ) ? 'test_secret_key' : 'live_secret_key';

			if ( ! $this->get_value( $secret_key ) ) {
				return false;
			}

			// todo: determine why this function might not be available when this is called on the donation detail page.
			if ( function_exists( 'charitable_stripe_get_charge_id_for_donation' ) ) {
				return false !== charitable_stripe_get_charge_id_for_donation( $donation->ID );
			} else {
				return false;
			}
		}

		/**
		 * Process a refund initiated in the WordPress dashboard.
		 *
		 * @since  1.3.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean
		 */
		public static function refund_donation_from_dashboard( $donation_id ) {
			$donation = charitable_get_donation( $donation_id );

			if ( ! $donation ) {
				return false;
			}

			$charge = charitable_stripe_get_charge_id_for_donation( $donation_id );

			if ( ! $charge ) {
				return false;
			}

			$gateway = new Charitable_Gateway_Stripe_AM();
			$key     = $donation->get_test_mode( false ) ? 'test_secret_key' : 'live_secret_key';
			$api_key = $gateway->get_value( $key );
			$account = charitable_stripe_get_account_id_for_donation( $donation->ID );
			$options = $account ? [ 'stripe_account' => $account ] : null;

			if ( ! $api_key ) {
				return false;
			}

			$gateway->setup_api( $api_key );

			$data = 0 === strpos( $charge, 'pi_' ) ? [ 'payment_intent' => $charge ] : [ 'charge' => $charge ];

			try {
				$refund = \Stripe\Refund::create( $data, $options );

				update_post_meta( $donation_id, '_stripe_refunded', true );
				update_post_meta( $donation_id, '_stripe_refund_id', $refund->id );

				$donation->log()->add(
					sprintf(
						/* translators: %s: transaction reference. */
						__( 'Stripe refund transaction ID: %s', 'charitable' ),
						$refund->id
					)
				);

				return true;
			} catch ( \Stripe\Error\InvalidRequest $e ) {
				$donation->log()->add(
					sprintf(
						/* translators: %s: error message. */
						__( 'Stripe refund failed: %s', 'charitable' ),
						$e->getStripeCode()
					)
				);
				return false;
			}
		}

		/**
		 * Check whether a recurring donation can be cancelled automatically in Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @param  boolean                       $can_cancel Whether the subscription can be cancelled.
		 * @param  Charitable_Recurring_Donation $donation The donation object.
		 * @return boolean
		 */
		public static function is_subscription_cancellable( $can_cancel, Charitable_Recurring_Donation $donation ) {
			if ( ! $can_cancel ) {
				return $can_cancel;
			}

			$secret_key = $donation->get_test_mode( false ) ? 'test_secret_key' : 'live_secret_key';

			if ( ! charitable_get_option( [ 'gateways_stripe', $secret_key ] ) ) {
				return false;
			}

			return ! empty( $donation->get_gateway_subscription_id() );
		}

		/**
		 * Cancel a subscription.
		 *
		 * This can be triggered via the WordPress dashboard when editing a recurring
		 * donation, or via the user's own account area.
		 *
		 * @since  1.3.0
		 *
		 * @param  boolean                       $cancelled Whether the subscription was cancelled successfully in the gateway.
		 * @param  Charitable_Recurring_Donation $donation  The recurring donation object.
		 * @return boolean
		 */
		public static function cancel_subscription( $cancelled, Charitable_Recurring_Donation $donation ) {
			// Enhanced logging for cancellation debugging
			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( sprintf(
					'CHARITABLE_STRIPE_CANCEL_DEBUG: Starting cancellation for donation #%d',
					$donation->get_donation_id()
				) );
			}

			$subscription_id = $donation->get_gateway_subscription_id();

			if ( ! $subscription_id ) {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'CHARITABLE_STRIPE_CANCEL_DEBUG: No subscription ID found - cancellation aborted' );
				}
				$donation->log()->add( __( 'Cancellation failed: No gateway subscription ID found.', 'charitable' ) );
				return false;
			}

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( sprintf(
					'CHARITABLE_STRIPE_CANCEL_DEBUG: Attempting to cancel subscription %s',
					$subscription_id
				) );
			}

			$gateway = new Charitable_Gateway_Stripe_AM();
			$key     = $donation->get_test_mode( false ) ? 'test_secret_key' : 'live_secret_key';
			$api_key = $gateway->get_value( $key );
			$account = charitable_stripe_get_account_id_for_donation( $donation->ID );
			$options = $account ? [ 'stripe_account' => $account ] : null;

			if ( ! $api_key ) {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( sprintf(
						'CHARITABLE_STRIPE_CANCEL_DEBUG: No API key found for mode %s - cancellation aborted',
						$donation->get_test_mode( false ) ? 'test' : 'live'
					) );
				}
				$donation->log()->add( __( 'Cancellation failed: No Stripe API key configured.', 'charitable' ) );
				return false;
			}

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( sprintf(
					'CHARITABLE_STRIPE_CANCEL_DEBUG: Using %s mode, account: %s',
					$donation->get_test_mode( false ) ? 'test' : 'live',
					$account ? $account : 'none'
				) );
			}

			$gateway->setup_api( $api_key );

			try {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( sprintf(
						'CHARITABLE_STRIPE_CANCEL_DEBUG: Retrieving subscription %s from Stripe',
						$subscription_id
					) );
				}

				$subscription = \Stripe\Subscription::retrieve( $subscription_id, $options );

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( sprintf(
						'CHARITABLE_STRIPE_CANCEL_DEBUG: Retrieved subscription status: %s',
						$subscription->status
					) );
				}

				// Log current subscription state before cancellation
				$donation->log()->add( sprintf(
					__( 'Retrieved subscription from Stripe - Current status: %s', 'charitable' ),
					$subscription->status
				) );

				$subscription->cancel( null, $options );

				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( 'CHARITABLE_STRIPE_CANCEL_DEBUG: Stripe cancellation API call completed successfully' );
				}

				$donation->log()->add( __( 'Subscription cancelled in Stripe.', 'charitable' ) );

				$cancelled = true;

			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error.', 'charitable' );

				$donation->log()->add(
					sprintf(
						/* translators: %s: error message */
						__( 'Stripe cancellation failed: %s', 'charitable' ),
						$message
					)
				);

				$cancelled = false;

			} finally {
				if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
					error_log( sprintf(
						'CHARITABLE_STRIPE_CANCEL_DEBUG: Cancellation result: %s',
						$cancelled ? 'SUCCESS' : 'FAILED'
					) );
				}
				return $cancelled;
			}
		}

		/**
		 * Cancel a subscription in Stripe after the recurring donation is marked as completed.
		 *
		 * @since  1.4.9
		 *
		 * @param  Charitable_Recurring_Donation $recurring_donation  The recurring donation object.
		 * @return boolean
		 */
		public function process_completed_subscription( $recurring_donation ) {
			return self::cancel_subscription( true, $recurring_donation );
		}

		/**
		 * Get the donation amount in the smallest common currency unit.
		 *
		 * @since  1.2.3
		 *
		 * @param  float       $amount   The donation amount in dollars.
		 * @param  string|null $currency The currency of the donation. If null, the site currency will be used.
		 * @return int
		 */
		public static function get_amount( $amount, $currency = null ) {
			return Charitable_Stripe_Gateway_Processor::get_sanitized_donation_amount( $amount, $currency );
		}

		/**
		 * Returns whether the currency is a zero decimal currency.
		 *
		 * @since  1.2.3
		 *
		 * @param  string $currency The currency for the charge. If left blank, will check for the site currency.
		 * @return boolean
		 */
		public static function is_zero_decimal_currency( $currency = null ) {
			return Charitable_Stripe_Gateway_Processor::is_zero_decimal_currency( $currency );
		}

		/**
		 * Return all zero-decimal currencies supported by Stripe.
		 *
		 * @since  1.2.3
		 *
		 * @return array
		 */
		public static function get_zero_decimal_currencies() {
			return Charitable_Stripe_Gateway_Processor::get_zero_decimal_currencies();
		}

		/**
		 * Attemps a Stripe charge and returns the status of the charge.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.1.0
		 * @since  1.3.0 Deprecated.
		 *
		 * @param  array                         $charge_args The arguments for the charge API request.
		 * @param  Charitable_Donor              $donor       The current user.
		 * @param  Charitable_Donation_Processor $processor   The Donation Processor helper object.
		 */
		public function make_charge( $charge_args, $donor, $processor ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );
		}

		/**
		 * Set the $charges property to empty.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.1.0
		 * @since  1.3.0 Deprecated.
		 *
		 * @return void
		 */
		public function clear_charges() {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );
		}

		/**
		 * Return the results of all charges.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.1.0
		 * @since  1.3.0 Deprecated.
		 */
		public function get_charges() {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );
		}

		/**
		 * Saves the results of a charge.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.1.0
		 * @since  1.3.0 Deprecated.
		 *
		 * @param  array  $result The result of the charge.
		 * @param  string $status The status of the charge.
		 *
		 * @return void
		 */
		public function save_charge_results( $result, $status ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );
		}

		/**
		 * Returns a card ID for the customer.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.7.0
		 * @since  1.3.0 Deprecated.
		 *
		 * @param  string $customer Stripe's customer ID.
		 * @param  string $card     The customer's card details or token.
		 * @return string|false Card ID or false if Stripe returns an error.
		 */
		public function get_customer_card( $customer, $card ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );

			try {

				$cu      = \Stripe\Customer::retrieve( $customer );
				$card    = $cu->sources->create( [ 'source' => $card ] );
				$card_id = $card->id;

			} catch ( Exception $e ) {

				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );
				charitable_get_notices()->add_error( $message );
				$card_id = false;

			}

			return $card_id;
		}

		/**
		 * Return the Stripe Customer ID for the current customer.
		 *
		 * If the donor has donated previously through Stripe, this will return
		 * their ID from the database. If not, this will first set them up as a
		 * customer in Stripe, store their customer ID and then return it.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.7.0
		 * @since  1.3.0 Deprecated.
		 *
		 * @param  Charitable_User|Charitable_Donor $donor The donor/user object for the logged in user.
		 * @param  Charitable_Donation_Processor    $processor The Donation Procesor helper.
		 * @return string|false
		 */
		public function get_stripe_customer( $donor, Charitable_Donation_Processor $processor ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );

			$key = charitable_get_option( 'test_mode' ) ? self::STRIPE_CUSTOMER_ID_KEY_TEST : self::STRIPE_CUSTOMER_ID_KEY;

			/**
			 * Retrieve current customer ID and verify that the customer still exists
			 * in Stripe.
			 */
			$stripe_customer_id = $donor->$key;

			if ( $stripe_customer_id ) {

				try {
					/* Retrieve the customer object from Stripe. */
					$cu = \Stripe\Customer::retrieve( $stripe_customer_id );

				} catch ( Stripe\Error\InvalidRequest $e ) {
					$cu = null;
				}

				if ( is_null( $cu ) || ( isset( $cu->deleted ) && $cu->deleted ) ) {
					$stripe_customer_id = false;
				}
			}

			/* No Stripe Customer ID found, so we're going to create one. */
			if ( ! $stripe_customer_id ) {

				$stripe_customer_id = $this->create_stripe_customer( $donor, $processor );

				/* Store the customer ID for logged in users. */
				if ( $stripe_customer_id && $donor->ID ) {
					update_user_meta( $donor->ID, $key, $stripe_customer_id );
				}
			}

			return $stripe_customer_id;
		}

		/**
		 * Create a Stripe Customer object through the API.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0 or 2.0.0.
		 *
		 * @since  1.2.2
		 * @since  1.3.0 Deprecated.
		 *
		 * @param  Charitable_Donor              $donor     The Donor object.
		 * @param  Charitable_Donation_Processor $processor The Donation Procesor helper.
		 * @return string|false
		 */
		public function create_stripe_customer( $donor, Charitable_Donation_Processor $processor ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );

			$stripe_customer_args = apply_filters(
				'charitable_stripe_customer_args',
				array(
					'description' => sprintf( '%s %s', __( 'Donor for', 'charitable' ), $donor->get_email() ),
					'email'       => $donor->get_email(),
					'metadata'    => array(
						'donor_id' => $processor->get_donor_id(),
						'user_id'  => $donor->ID,
					),
				),
				$donor,
				$processor
			);

			try {
				$customer = \Stripe\Customer::create( $stripe_customer_args );

				return $customer->id;

			} catch ( Exception $e ) {
				$body    = $e->getJsonBody();
				$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Something went wrong.', 'charitable' );
				charitable_get_notices()->add_error( $message );

				return false;
			}
		}

		/**
		 * Returns the payment source.
		 *
		 * This may return a string, identifying the ID of a payment source such as
		 * a credit card. It may also be an associative array containing the user's
		 * credit card details.
		 *
		 * @deprecated 1.5.0 To be removed in 1.5.0.
		 *
		 * @since  1.7.0
		 * @since  1.3.0 Deprecated.
		 *
		 * @param  Charitable_User|Charitable_Donor $donor The donor/user object for the logged in user.
		 * @param  Charitable_Donation_Processor    $processor The Donation Procesor helper.
		 * @return string|array
		 */
		public function get_source( $donor, Charitable_Donation_Processor $processor ) {
			charitable_stripe_get_deprecated()->deprecated_function( __METHOD__, '1.3.0' );

			$values = $processor->get_donation_data();

			/**
			 * If the donation is made by a logged in user who selected
			 * a source (card), return that.
			 */
			if ( $this->get_gateway_value( 'source', $values ) ) {
				return $this->get_gateway_value( 'source', $values );
			}

			/**
			 * If we have a token available, return that.
			 */
			if ( $this->get_gateway_value( 'token', $values ) ) {
				return $this->get_gateway_value( 'token', $values );
			}

			charitable_get_notices()->add_error( __( 'Missing credit card details. Unable to proceed with payment.', 'charitable' ) );

			return false;
		}

		/**
		 * Load Stripe JS, as well as our handling scripts.
		 *
		 * @deprecated 1.6.0
		 *
		 * @since  1.1.0
		 * @since  1.4.0 Deprecated. Method moved to Charitable_Stripe class.
		 *
		 * @return boolean
		 */
		public static function enqueue_scripts() {
			charitable_stripe_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_stripe()->enqueue_scripts()'
			);

			return charitable_stripe()->enqueue_scripts();
		}

		/**
		 * Load Stripe JS or Stripe Checkout, as well as our handling scripts.
		 *
		 * @deprecated 1.6.0
		 *
		 * @since  1.1.2
		 * @since  1.4.0 Deprecated. Method moved to Charitable_Stripe class.
		 *
		 * @param  Charitable_Donation_Form $form The current form object.
		 * @return boolean
		 */
		public static function maybe_setup_scripts_in_donation_form( $form ) {
			charitable_stripe_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_stripe()->maybe_setup_scripts_in_donation_form( $form )'
			);

			return charitable_stripe()->maybe_setup_scripts_in_donation_form( $form );
		}

		/**
		 * Enqueue the Stripe JS/Checkout scripts after a campaign loop if modal donations are in use.
		 *
		 * @deprecated 1.6.0
		 *
		 * @since  1.1.2
		 * @since  1.4.0 Deprecated. Method moved to Charitable_Stripe class.
		 *
		 * @return boolean
		 */
		public static function maybe_setup_scripts_in_campaign_loop() {
			charitable_stripe_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_stripe()->maybe_setup_scripts_in_campaign_loop()'
			);

			return charitable_stripe()->maybe_setup_scripts_in_campaign_loop();
		}


		/**
		 * Checks if credit card details are required.
		 *
		 * Credit card details are required UNLESS the donation is being
		 * made by a logged in user who has donated before and is using
		 * one of their stored cards (stored on Stripe's server, not ours).
		 *
		 * @deprecated 1.6.0
		 *
		 * @since  1.7.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @param  array $values The filtered values from the donation form submission.
		 * @return boolean
		 */
		public function require_credit_card_details( $values ) {
			charitable_stripe_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0'
			);

			return false;
		}

		/**
		 * Determine if the Stripe addon should be shown on the frontend if no keys are found
		 *
		 * @param  array[] $active_gateways The list of registered gateways.
		 * @return string[]
		 * @since  1.7.0
		 */
		public function maybe_active_public_gateway( $active_gateways ) {

			if ( ! isset( $active_gateways['stripe'] ) ) {
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
				charitable_get_notices()->add_error( __( 'Missing keys for Stripe payment gateway. Unable to proceed with payment.', 'charitable' ) );
				unset( $active_gateways['stripe'] );
			}

			return $active_gateways;
		}

		/**
		 * Register the Stripe payment gateway class.
		 *
		 * @param  string $setting Setting, which might have been stored in the database by an addon.
		 * @param  string $original_key Oringial key.
		 * @param  string $default Default setting.
		 * @return string
		 * @since  1.7.0
		 */
		public function maybe_return_stripe_connect_setting( $setting, $original_key, $default ) {

			if ( ! charitable()->is_stripe_connect_addon() ) {
				// if the stripe connect addon isn't activated, then this setting shouldn't be retreavable (although it stays in the dataabase).
				$setting = null;
			}

			return $setting;
		}


		/**
		 * Stripe shows errors for amounts less tha $1, so set the min. amount to 1. This filter catches the ajax request.
		 *
		 * @param  integer $minimum_amount The minimum donation amount.
		 *
		 * @return integer
		 * @since  1.7.0.2
		 */
		public function set_minimum_donation_amount( $minimum_amount ) {

			if ( isset( $_POST['gateway'] ) && false !== strpos( $_POST['gateway'], 'stripe' ) ) { // phpcs:ignore
					// Stripe gateway detected - make sure the minimum aount is at least 1 (logic revised in 1.7.0.9).
				if ( intval( $minimum_amount ) < 1 ) {
					$minimum_amount = 1;
				}
			}

			return $minimum_amount;
		}

		/**
		 * Use existing pre-1.7.0 hook to process webhooks that are for Stripe Connect AM accounts.
		 *
		 * @param  string $event_type The type of event.
		 * @param  object $event The event object.
		 *
		 * @since  1.7.0
		 */
		public function maybe_process_webhook_with_stripe_connect( $event_type, $event ) {

			// a reminder on this check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			if ( ! charitable_using_stripe_connect() ) {
				return;
			}

			if ( ( charitable_is_debug() ) ) {
				error_log( 'maybe_process_webhook_with_stripe_connect' ); // phpcs:ignore
				error_log( print_r( $event_type, true ) ); // phpcs:ignore
				error_log( print_r( $event, true ) ); // phpcs:ignore
			}

			$processor = new Charitable_Stripe_Webhook_Processor( $event );

			$default_processors = apply_filters(
				'charitable_stripe_default_event_processors',
				[
					'charge.refunded'               => [ $processor, 'process_refund' ],
					'invoice.created'               => [ $processor, 'process_invoice_created' ],
					'invoice.payment_failed'        => [ $processor, 'process_invoice_payment_failed' ],
					'invoice.payment_succeeded'     => [ $processor, 'process_invoice_payment_succeeded' ],
					'customer.subscription.updated' => [ $processor, 'process_customer_subscription_updated' ],
					'customer.subscription.deleted' => [ $processor, 'process_customer_subscription_deleted' ],
					'payment_intent.payment_failed' => [ $processor, 'process_payment_intent_payment_failed' ],
					'payment_intent.succeeded'      => [ $processor, 'process_payment_intent_succeeded' ],
					'checkout.session.completed'    => [ $processor, 'process_checkout_session_completed' ],
				]
			);

			if ( array_key_exists( $event_type, $default_processors ) ) {
				$processor = $default_processors[ $event_type ];
				if ( is_callable( $processor ) ) {
					$message = $processor( $event );
				} else {
					$message = 'Invalid processor';
				}
				/* Kill processing with a message returned by the event processor. */
				die( $message ); // phpcs:ignore
			}
		}
	}

endif;
