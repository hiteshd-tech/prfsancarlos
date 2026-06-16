<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Helpers
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.1.0
 * @version   1.3.0
 */

use Square\Types\SubscriptionCadence;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Helpers' ) ) :

	/**
	 * Charitable_Square_Helpers
	 *
	 * @since 1.1.0
	 */
	class Charitable_Square_Helpers {


		/**
		 * Determine whether the addon is activated and appropriate license is set.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_pro(): bool {

			return self::is_addon_active() && self::is_allowed_license_type();
		}

		/**
		 * Determine whether the addon is activated.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_addon_active(): bool {
			return is_plugin_active( 'charitable-square/charitable-square.php' );
		}

		/**
		 * Determine whether a license is ok.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_license_ok(): bool {

			return self::is_license_active() && self::is_allowed_license_type();
		}

		/**
		 * Determine whether a license type is allowed.
		 * For future use, for 'pro', 'elite', 'basic', 'plus' license types.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_allowed_license_type(): bool {
			return true;
		}

		/**
		 * Determine whether a license key is active.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_license_active(): bool {
			return charitable_is_pro();
		}

		/**
		 * Determine whether Square is in sandbox mode.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_sandbox_mode(): bool {

			return self::get_mode() === 'test';
		}

		/**
		 * Determine whether Square is in production mode.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_production_mode(): bool {

			return self::get_mode() === 'live';
		}

		/**
		 * Retrieve Square mode from Charitable settings.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public static function get_mode(): string {
			return charitable_get_option( 'test_mode' ) ? 'test' : 'live';
		}

		/**
		 * Retrieve Square Business Location ID from Charitable settings.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		public static function get_location_id( string $mode = '' ): string {

			$mode = self::validate_mode( $mode );

			$option = get_option( 'charitable_square_location_id_' . $mode );

			if ( $option ) {
				return $option;
			}

			return '';
		}

		/**
		 * Retrieve Square available modes.
		 *
		 * @since 1.8.7
		 *
		 * @return array
		 */
		public static function get_available_modes(): array {

			return array( 'test', 'live' );
		}

		/**
		 * Validate Square mode to ensure it's either 'production' or 'sandbox'.
		 * If given mode is invalid, fetches current Square mode.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode to validate.
		 *
		 * @return string
		 */
		public static function validate_mode( string $mode ): string {

			return in_array( $mode, self::get_available_modes(), true ) ? $mode : self::get_mode();
		}

		/**
		 * The `array_key_first` polyfill.
		 *
		 * @since 1.8.7
		 *
		 * @param array $arr Input array.
		 *
		 * @return mixed|null
		 */
		public static function array_key_first( array $arr ) {

			if ( function_exists( 'array_key_first' ) ) {
				return array_key_first( $arr );
			}

			foreach ( $arr as $key => $unused ) {
				return $key;
			}

			return null;
		}

		/**
		 * Determine if webhook ID and secret are set in Charitable settings.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_webhook_configured(): bool {

			$mode = self::get_mode();

			$settings = charitable_get_option( 'gateways_square' );

			return ! empty( $settings[ $mode ]['webhooks-id'] );
		}

		/**
		 * Determine if Square is configured and valid.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_square_configured(): bool {

			$connection = Charitable_Square_Connection::get();

			// Check if connection is configured and valid.
			return ! ( ! $connection || ! $connection->is_configured() || ! $connection->is_valid() );
		}

		/**
		 * Get webhook URL for REST API.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public static function get_webhook_url_for_rest(): string {
			return esc_url_raw( add_query_arg( array( 'charitable-listener' => Charitable_Gateway_Square::ID ), trailingslashit( home_url() ) ) );
		}

		/**
		 * Reset Square webhooks settings.
		 *
		 * @since 1.8.7
		 *
		 * @param bool $reset_enable Optional. Whether to reset the webhook enabled status. Default is false.
		 *
		 * @return bool
		 */
		public static function reset_webhook_configuration( bool $reset_enable = false ): bool {

			$settings = (array) get_option( 'charitable_settings', array() );

			$mode = self::get_mode();

			if ( $reset_enable ) {
				$settings['gateways_square']['square_enable_webhooks'] = 0; // Switch off webhooks.
			}

			$settings['gateways_square'][ $mode ]['webhooks-id']     = '';
			$settings['gateways_square'][ $mode ]['webhooks-secret'] = '';

			return update_option( 'charitable_settings', $settings );
		}

		/**
		 * Determine the billing cadences of a Subscription.
		 *
		 * @since 1.8.7
		 *
		 * @return array
		 */
		public static function get_subscription_cadences(): array {

			/**
			 * Filter the available billing cadences of a Subscription.
			 *
			 * @since 1.8.7
			 *
			 * @param array $cadences Subscription billing cadences.
			 */
			return (array) apply_filters(
				'charitable_integrations_square_helpers_get_subscription_cadences',
				array(
					'daily'      => array(
						'slug'  => 'daily',
						'name'  => esc_html__( 'Daily', 'charitable' ),
						'value' => SubscriptionCadence::Daily->value,
					),
					'weekly'     => array(
						'slug'  => 'weekly',
						'name'  => esc_html__( 'Weekly', 'charitable' ),
						'value' => SubscriptionCadence::Weekly->value,
					),
					'monthly'    => array(
						'slug'  => 'monthly',
						'name'  => esc_html__( 'Monthly', 'charitable' ),
						'value' => SubscriptionCadence::Monthly->value,
					),
					'quarterly'  => array(
						'slug'  => 'quarterly',
						'name'  => esc_html__( 'Quarterly', 'charitable' ),
						'value' => SubscriptionCadence::Quarterly->value,
					),
					'semiyearly' => array(
						'slug'  => 'semiyearly',
						'name'  => esc_html__( 'Semi-Yearly', 'charitable' ),
						'value' => SubscriptionCadence::EverySixMonths->value,
					),
					'yearly'     => array(
						'slug'  => 'yearly',
						'name'  => esc_html__( 'Yearly', 'charitable' ),
						'value' => SubscriptionCadence::Annual->value,
					),
				)
			);
		}

		/**
		 * Get Square webhook endpoint URL.
		 *
		 * If the constant CHARITABLE_SQUARE_WHURL is defined, it will be used as the webhook URL.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public static function get_webhook_url(): string {

			if ( defined( 'CHARITABLE_SQUARE_WHURL' ) ) {
				return CHARITABLE_SQUARE_WHURL;
			}

			if ( self::is_rest_api_set() ) {
				return self::get_webhook_url_for_rest();
			}

			return false;

			// self::get_webhook_url_for_curl();
		}

		/**
		 * Determine if the REST API is set in Charitable settings.
		 * Designed in case we want to experiment with different webhook communication methods.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_rest_api_set(): bool {
			return true;
		}

		/**
		 * Determine if webhooks are enabled in Charitable settings.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_webhook_enabled(): bool {

			$settings = charitable_get_option( 'gateways_square' );

			if ( ! is_array( $settings ) ) {
				return false;
			}

			$square_enable_webhooks = $settings['square_enable_webhooks'] ?? null;

			return ! empty( $square_enable_webhooks );
		}

		/**
		 * Determine whether the application fee is supported.
		 *
		 * @since 1.8.7
		 *
		 * @param string $currency Currency.
		 *
		 * @return bool
		 */
		public static function is_application_fee_supported( string $currency = '' ): bool {

			$currency = ! $currency ? charitable_get_currency() : $currency;

			return strtoupper( $currency ) === 'USD';
		}
	}

endif;
