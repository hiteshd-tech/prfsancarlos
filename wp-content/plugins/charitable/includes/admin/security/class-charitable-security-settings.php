<?php
/**
 * Charitable Security Settings.
 *
 * @package   Charitable/Classes/Charitable_Security_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9
 * @version   1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Security_Settings' ) ) :

	/**
	 * Charitable_Security_Settings
	 *
	 * @since 1.8.0
	 */
	class Charitable_Security_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Security_Settings|null
		 */
		private static $instance = null;

		/**
		 * Loaded modules.
		 *
		 * @var array
		 */
		private $loaded_modules = array();

		/**
		 * Create object instance.
		 *
		 * @since 1.8.9
		 */
		private function __construct() {
			// If spam blocker is active, don't load core security features.
			if ( defined( 'CHARITABLE_SPAMBLOCKER_FEATURE_PLUGIN' ) ) {
				return;
			}

			$this->load_modules();
			$this->setup_notices();
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.0
		 *
		 * @return Charitable_Security_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Load all security modules.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		private function load_modules() {
			$modules_dir = charitable()->get_path( 'includes', true ) . 'admin/security/modules/';

			if ( ! is_dir( $modules_dir ) ) {
				return;
			}

			// Load CAPTCHA modules.
			$captcha_modules = array(
				'captcha/class-charitable-captcha.php',
				'captcha/class-charitable-captcha-google-recaptcha-v2.php',
				'captcha/class-charitable-captcha-google-recaptcha-v3.php',
				'captcha/class-charitable-captcha-hcaptcha.php',
				'captcha/class-charitable-captcha-cloudflare-turnstile.php',
			);

			foreach ( $captcha_modules as $module_file ) {
				$file_path = $modules_dir . $module_file;
				if ( file_exists( $file_path ) ) {
					require_once $file_path;
				}
			}

			// Instantiate modules that have get_settings method.
			$module_classes = array(
				'Charitable_Captcha',
				'Charitable_Captcha_Google_ReCAPTCHA_V2',
				'Charitable_Captcha_Google_ReCAPTCHA_V3',
				'Charitable_Captcha_HCaptcha',
				'Charitable_Captcha_Cloudflare_Turnstile',
			);

			foreach ( $module_classes as $class_name ) {
				if ( class_exists( $class_name ) && method_exists( $class_name, 'get_settings' ) ) {
					$instance = new $class_name();
					$this->loaded_modules[] = $instance;
					if ( charitable_is_debug() && charitable_is_debug( 'security' ) && method_exists( $instance, 'is_active' ) ) {
						error_log( '[Charitable Security] Loaded module: ' . $class_name . ' | Active: ' . ( $instance->is_active() ? 'YES' : 'NO' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
				}
			}
		}

		/**
		 * Add security settings group.
		 *
		 * @since  1.8.0
		 *
		 * @param  array $groups The setting groups.
		 * @return array
		 */
		public function add_security_settings_group( $groups ) {
			// If spam blocker is active, don't add core security group.
			if ( defined( 'CHARITABLE_SPAMBLOCKER_FEATURE_PLUGIN' ) ) {
				return $groups;
			}

			if ( ! is_array( $groups ) ) {
				$groups = array();
			}
			if ( ! in_array( 'security', $groups, true ) ) {
				$groups[] = 'security';
			}
			return $groups;
		}

		/**
		 * Add settings from all modules.
		 *
		 * @since  1.8.0
		 *
		 * @param  array $settings Existing settings.
		 * @return array Modified settings
		 */
		public function add_settings( $settings ) {
			// If spam blocker is active, don't add core security settings.
			if ( defined( 'CHARITABLE_SPAMBLOCKER_FEATURE_PLUGIN' ) ) {
				return $settings;
			}

			if ( empty( $this->loaded_modules ) ) {
				return $settings;
			}

			foreach ( $this->loaded_modules as $module ) {
				if ( method_exists( $module, 'get_settings' ) && is_callable( array( $module, 'get_settings' ) ) ) {
					$module_settings = $module->get_settings();
					if ( is_array( $module_settings ) ) {
						$settings = array_merge(
							$settings,
							$module_settings
						);
					}
				}
			}

			return $settings;
		}

		/**
		 * Set up admin notices for security recommendations.
		 *
		 * @since  1.8.9
		 *
		 * @return void
		 */
		private function setup_notices() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'render_security_recommendation_notice' ) );
			add_action( 'charitable_dismiss_notice', array( $this, 'dismiss_security_recommendation_notice' ), 10, 1 );
			add_filter( 'charitable_save_settings', array( $this, 'maybe_reset_security_notice_dismissal' ), 10, 2 );
		}

		/**
		 * Render security recommendation notice.
		 *
		 * Shows a notice when CAPTCHA is disabled and there's at least one campaign.
		 *
		 * @since  1.8.9
		 *
		 * @return void
		 */
		public function render_security_recommendation_notice() {
			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			// If spam blocker is active, don't show notice.
			if ( defined( 'CHARITABLE_SPAMBLOCKER_FEATURE_PLUGIN' ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( is_null( $screen ) ) {
				return;
			}

			// Only show on specific Charitable pages.
			$allowed_screens = array(
				'campaign',
				'edit-campaign',
				'donation',
				'edit-donation',
				'charitable_page_charitable-settings',
				'charitable_page_charitable-tools',
			);

			if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
				return;
			}

			// Check if at least one campaign exists.
			$count_campaigns = wp_count_posts( 'campaign' );
			$total_campaigns = isset( $count_campaigns->publish ) ? intval( $count_campaigns->publish ) : 0;
			if ( $total_campaigns < 1 ) {
				return;
			}

			// Check if CAPTCHA is disabled.
			$captcha_provider = charitable_get_option( 'captcha_provider', 'disabled' );

			$captcha_disabled = 'disabled' === $captcha_provider;

			if ( ! $captcha_disabled ) {
				return;
			}

			// Check if notice was dismissed.
			$slug = 'security-recommendation';
			$dismissed = get_transient( 'charitable_' . $slug . '_banner' );
			if ( $dismissed ) {
				return;
			}

			// Build the notice message.
			$security_url = admin_url( 'admin.php?page=charitable-settings&tab=security' );
			$message = sprintf(
				/* translators: %s: link to security settings */
				__( 'Charitable recommends enabling CAPTCHA to protect your donation forms against bots and spam. <a href="%s">Configure security settings</a>.', 'charitable' ),
				esc_url( $security_url )
			);

			$key = 'security-recommendation';
			charitable_get_admin_notices()->render_notice( $message, 'warning', true, $key, false );
		}

		/**
		 * Dismiss security recommendation notice.
		 *
		 * @since  1.8.9
		 *
		 * @param  array $postdata POST data from dismissal request.
		 * @return void
		 */
		public function dismiss_security_recommendation_notice( $postdata ) {
			if ( empty( $postdata['notice'] ) || 'security-recommendation' !== $postdata['notice'] ) {
				return;
			}

			$slug = 'security-recommendation';
			set_transient( 'charitable_' . $slug . '_banner', 1, 0 );
		}

		/**
		 * Reset security notice dismissal if settings are saved with CAPTCHA disabled.
		 *
		 * @since  1.8.9
		 *
		 * @param  array $values     Current settings values.
		 * @param  array $new_values New settings values being saved.
		 * @return array
		 */
		public function maybe_reset_security_notice_dismissal( $values, $new_values ) {
			// Only process if security settings are being saved.
			if ( ! isset( $new_values['captcha_provider'] ) ) {
				return $values;
			}

			// Get the final values (after merge with old values).
			$captcha_provider = isset( $new_values['captcha_provider'] ) ? $new_values['captcha_provider'] : charitable_get_option( 'captcha_provider', 'disabled' );

			// If CAPTCHA is disabled, clear the dismissal so notice appears again.
			if ( 'disabled' === $captcha_provider ) {
				delete_transient( 'charitable_security-recommendation_banner' );
			}

			return $values;
		}
	}

endif;

