<?php
/**
 * The class responsible for monitoring Square webhooks health.
 *
 * @package   Charitable Square/Classes/Charitable_Square_WebhooksHealthCheck
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.1.0
 * @version   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_WebhooksHealthCheck' ) ) :

	/**
	 * Webhooks Health Check class.
	 *
	 * @since 1.8.7
	 */
	class Charitable_Square_WebhooksHealthCheck {

		/**
		 * Endpoint status option name.
		 *
		 * @since 1.8.7
		 */
		public const ENDPOINT_OPTION = 'charitable_square_webhooks_endpoint_status';

		/**
		 * Webhook health check cron hook.
		 *
		 * @since 1.8.7
		 */
		private const CRON_HOOK = 'charitable_square_webhooks_health_check';

		/**
		 * Status OK key.
		 *
		 * @since 1.8.7
		 */
		public const STATUS_OK = 'ok';

		/**
		 * Status error key.
		 *
		 * @since 1.8.7
		 */
		private const STATUS_ERROR = 'error';

		/**
		 * Admin notice ID.
		 *
		 * @since 1.8.7
		 */
		private const NOTICE_ID = 'charitable_square_webhooks_site_health';

		/**
		 * Initialization.
		 *
		 * @since 1.8.7
		 */
		public function init() {
			$this->hooks();
			return $this;
		}

		/**
		 * Register hooks.
		 *
		 * @since 1.8.7
		 */
		private function hooks() {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			add_action( self::CRON_HOOK, array( $this, 'process_webhooks_status_check' ) );
			add_action( 'init', array( $this, 'maybe_schedule_check' ) );

			// Hook into the charitable_uninstall action for cleanup.
			add_action( 'charitable_uninstall', array( $this, 'uninstall' ) );
		}

		/**
		 * Schedule webhook health check using WP Cron.
		 *
		 * @since 1.8.7
		 */
		public function maybe_schedule_check() {
			// Skip if webhook health check should be disabled.
			if ( apply_filters( 'charitable_square_webhooks_health_check_disable', false ) ) {
				return;
			}

			// Skip if not configured or already scheduled.
			if ( ! Charitable_Square_Helpers::is_webhook_enabled() || wp_next_scheduled( self::CRON_HOOK ) ) {
				return;
			}

			// Schedule health check to run hourly.
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}

		/**
		 * Clean up scheduled events and options on uninstall.
		 *
		 * @since 1.8.7
		 */
		public function uninstall() {
			// Clear cron event.
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::CRON_HOOK );
			}

			// Clean up options.
			delete_option( self::ENDPOINT_OPTION );
		}

		/**
		 * Process webhook health check status.
		 *
		 * @since 1.8.7
		 */
		public function process_webhooks_status_check() {
			// Skip if webhooks are not enabled.
			if ( ! Charitable_Square_Helpers::is_webhook_enabled() ) {
				return;
			}

			$last_payment = $this->get_last_square_payment();

			// If no Square payments found, remove status option to avoid false positives.
			if ( empty( $last_payment ) ) {
				delete_option( self::ENDPOINT_OPTION );
				return;
			}

			// If payment is processed but webhook didn't update status within 15 minutes,
			// we likely have a webhook configuration issue.
			if (
				isset( $last_payment['status'] ) &&
				$last_payment['status'] === 'processed' &&
				time() > ( strtotime( $last_payment['date_created_gmt'] ) + ( 15 * MINUTE_IN_SECONDS ) )
			) {
				Charitable_Square_Helpers::reset_webhook_configuration();
				self::save_status( self::STATUS_ERROR );
				return;
			}

			// Everything looks good.
			self::save_status( self::STATUS_OK );
		}

		/**
		 * Get the most recent Square payment.
		 *
		 * @since 1.8.7
		 *
		 * @return array Payment data or empty array if none found.
		 */
		private function get_last_square_payment() {
			global $wpdb;

			// Find the last 'donation' custom post type with a meta_key of 'gateway' set to 'square'.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnnecessaryPrepare
			$payments = $wpdb->get_results(
				"SELECT * FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'donation'
				AND p.post_status = 'charitable-completed'
				AND pm.meta_key = 'donation_gateway'
				AND pm.meta_value = 'square'
				ORDER BY ID DESC
				LIMIT 1",
				ARRAY_A
			);

			return $payments ? $payments[0] : array();
		}

		/**
		 * Display admin notice about webhook issues.
		 *
		 * @since 1.8.7
		 */
		public function admin_notice() {
			// Only show notice if Square is configured.
			if ( ! Charitable_Square_Helpers::is_square_configured() ) {
				return;
			}

			// Only show if webhooks are enabled but not properly configured.
			if (
				! Charitable_Square_Helpers::is_webhook_enabled() ||
				Charitable_Square_Helpers::is_webhook_configured()
			) {
				return;
			}

			// Only show if we've detected a webhook error.
			if ( get_option( self::ENDPOINT_OPTION, self::STATUS_OK ) === self::STATUS_OK ) {
				return;
			}

			// Only show if there are Square payments.
			if ( empty( $this->get_last_square_payment() ) ) {
				return;
			}

			// Display the admin notice.
			$notice = sprintf(
				wp_kses(
					/* translators: %1$s: URL to the webhooks documentation */
					__( 'Looks like you have a problem with your webhooks configuration. Please check and confirm that you\'ve configured the WPCharitable webhooks in your Square account. This notice will disappear automatically when a new Square request comes in. See our <a href="%1$s" rel="nofollow noopener" target="_blank">documentation</a> for more information.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				esc_url( charitable_utm_link( 'https://wpcharitable.com/docs/setting-up-square-webhooks/', 'Admin', 'Square Webhooks not active' ) )
			);

			// Use WordPress admin notices.
			?>
			<div class="notice notice-error is-dismissible" data-notice-id="<?php echo esc_attr( self::NOTICE_ID ); ?>">
				<p><?php echo wp_kses_post( $notice ); ?></p>
			</div>
			<?php
		}

		/**
		 * Save webhooks status.
		 *
		 * @since 1.8.7
		 *
		 * @param string $value Status value.
		 */
		public static function save_status( string $value ) {
			if ( ! in_array( $value, array( self::STATUS_OK, self::STATUS_ERROR ), true ) ) {
				return;
			}

			update_option( self::ENDPOINT_OPTION, $value );
		}
	}

endif;