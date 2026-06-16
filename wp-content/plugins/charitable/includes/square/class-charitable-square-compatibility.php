<?php
/**
 * Square Gateway Compatibility Wrapper.
 *
 * This class serves as a compatibility layer between Charitable and Square SDK,
 * checking PHP version requirements before loading Square components.
 *
 * @package   Charitable/Classes
 * @author    David Bisset
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 * @version   1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Compatibility' ) ) :

	/**
	 * Charitable_Square_Compatibility
	 *
	 * @since 1.8.7
	 */
	class Charitable_Square_Compatibility {

		/**
		 * Minimum PHP version required for Square SDK.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		const MIN_PHP_VERSION = '8.1.0';

		/**
		 * Single instance of this class.
		 *
		 * @since 1.8.7
		 *
		 * @var Charitable_Square_Compatibility
		 */
		private static $instance = null;

		/**
		 * Whether Square functionality can be loaded.
		 *
		 * @since 1.8.7
		 *
		 * @var bool
		 */
		private static $is_compatible = null;

		/**
		 * Check if the current PHP version is compatible with Square SDK.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public static function is_compatible() {
			if ( null === self::$is_compatible ) {
				// First check if our constant is defined (set in platform_check.php).
				if ( defined( 'CHARITABLE_SQUARE_AVAILABLE' ) ) {
					self::$is_compatible = CHARITABLE_SQUARE_AVAILABLE;
				} else {
					// Fallback to direct version comparison.
					self::$is_compatible = version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
				}
			}

			return apply_filters( 'charitable_square_is_compatible', self::$is_compatible );
		}

		/**
		 * Initialize Square functionality if PHP version is compatible.
		 *
		 * @since 1.8.7
		 *
		 * @return bool True if Square was initialized, false otherwise.
		 */
		public static function init() {
			if ( ! self::is_compatible() ) {
				// Make sure we're loading the admin notice at the right time.
				if ( is_admin() ) {
					// Register the admin notice - make sure this runs after admin is initialized.
					add_action( 'init', array( __CLASS__, 'display_version_notice' ) );
				}

				// Remove Square from available gateways.
				add_filter( 'charitable_payment_gateways', array( __CLASS__, 'remove_square_gateway' ), 20 );

				return false;
			}

			// If WordPress is not fully loaded yet, schedule initialization for later.
			if ( ! did_action( 'plugins_loaded' ) ) {
				add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 10 );
				return true;
			}

			// Load Square components.
			self::load_square_components();
			return true;
		}

		/**
		 * Display Square compatibility version notice.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public static function display_version_notice_sitewide() {
			// We already handle this in the init hook with display_version_notice().
			// This function exists for compatibility but should not duplicate the notice.
			return; // phpcs:ignore
		}

		/**
		 * Load Square components.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		private static function load_square_components() {

			// Check if Charitable is fully loaded.
			if ( ! function_exists( 'charitable' ) || ! charitable() ) {
				// Schedule to try again after the 'plugins_loaded' hook.
				add_action( 'plugins_loaded', array( __CLASS__, 'load_square_components' ), 20 );
				return;
			}

			$includes_path = charitable()->get_path( 'includes' );

			// Check if required Square classes are available.
			if ( ! class_exists( '\Square\SquareClient' ) ) {
				// Square SDK not loaded, cannot proceed with Square functionality.
				add_action( 'admin_notices', array( __CLASS__, 'display_sdk_missing_notice' ) );
				return;
			}

			require_once $includes_path . 'square/charitable-square-core-functions.php';
			require_once $includes_path . 'square/helpers/class-charitable-donation-data-mapper.php';
			require_once $includes_path . 'square/payment/class-charitable-payment-response.php';
			require_once $includes_path . 'square/gateway/class-charitable-square-webhook-processor.php';
			require_once $includes_path . 'square/donations/class-charitable-square-donation-log.php';
			require_once $includes_path . 'square/gateway/class-charitable-square-gateway-processor.php';
			require_once $includes_path . 'square/gateway/class-charitable-square-subscription-plan.php';
			require_once $includes_path . 'square/donations/class-charitable-square-recurring-donation-log.php';

			// Additional Square files from class map.
			require_once $includes_path . 'square/admin/class-charitable-square-connect.php';
			require_once $includes_path . 'square/admin/class-charitable-square-connection.php';
			require_once $includes_path . 'gateways/class-charitable-gateway-square.php';
			require_once $includes_path . 'square/class-charitable-square-api.php';
			require_once $includes_path . 'square/gateway/class-charitable-square-initialization.php';
			require_once $includes_path . 'square/admin/class-charitable-square-helpers.php';
			require_once $includes_path . 'square/admin/class-charitable-square-webhookshealthcheck.php';
			require_once $includes_path . 'square/admin/class-charitable-square-webhooksmanager.php';

			// Load admin files if we're in the admin area.
			if ( is_admin() ) {
				require_once $includes_path . 'square/admin/class-charitable-square-admin.php';
				require_once $includes_path . 'square/admin/charitable-admin-square-gateway-hooks.php';
			}

			require_once $includes_path . 'square/gateway/charitable-square-gateway-hooks.php'; // 1.8.7
		}

		/**
		 * Display admin notice about PHP version incompatibility.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public static function display_version_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Generate an admin notice so the admin knows that the Square gateway is disabled.
			$message = sprintf(
				/* translators: %1$s: Minimum PHP version required, %2$s: Current PHP version, %3$s: Alternative gateway URL, %4$s: Documentation URL */
				__( 'Charitable: The Square payment gateway requires PHP %1$s or higher. Your site is running PHP %2$s. Please upgrade PHP to use Square or use an <a href="%3$s">alternative payment gateway</a>. See <a href="%4$s">our documentation</a>.', 'charitable' ),
				esc_html( self::MIN_PHP_VERSION ),
				esc_html( PHP_VERSION ),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' ),
				'https://www.wpcharitable.com/documentation/php-version-compatibility-square/'
			);
			charitable_get_admin_notices()->add_notice( $message, 'error', 'square-php-version-notice', true );
		}

		/**
		 * Remove Square payment gateway if PHP version is incompatible.
		 *
		 * @since 1.8.7
		 *
		 * @param array $gateways Registered payment gateways.
		 * @return array Modified gateways list.
		 */
		public static function remove_square_gateway( $gateways ) {
			if ( isset( $gateways['square'] ) ) {
				unset( $gateways['square'] );
			}

			return $gateways;
		}

		/**
		 * Display admin notice about missing Square SDK.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public static function display_sdk_missing_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Only show on Charitable pages.
			$screen = get_current_screen();
			if ( ! $screen || strpos( $screen->id, 'charitable' ) === false ) {
				return;
			}

			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Square Gateway Error:', 'charitable' ); ?></strong>
					<?php esc_html_e( 'The Square SDK could not be loaded. The Square payment gateway has been disabled.', 'charitable' ); ?>
				</p>
			</div>
			<?php
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.7
		 *
		 * @return Charitable_Square_Compatibility
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;