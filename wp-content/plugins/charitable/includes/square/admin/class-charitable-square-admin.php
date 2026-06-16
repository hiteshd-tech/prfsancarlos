<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Admin' ) ) :

	/**
	 * Charitable_Square_Admin
	 *
	 * @since 1.1.0
	 */
	class Charitable_Square_Admin {

		/**
		 * Square Apps URL.
		 *
		 * @since 1.8.7
		 */
		public const SQUARE_APPS_URL = 'https://developer.squareup.com/apps';

		/**
		 * Single instance of this class.
		 *
		 * @since 1.1.0
		 *
		 * @var   Charitable_Square_Admin
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.1.0
		 */
		public function __construct() {

			/**
			 * When saving Square settings, check for webhook if secret key has changed (when you aren't using Square Connect AM)
			 */
			add_filter( 'charitable_save_settings', array( $this, 'save_square_settings' ), 10, 3 );



			/**
			 * When connecting Square Connect, check for webhook if secret key has changed.
			 */
			// add_action( 'wpcharitable_square_account_connected', array( $this, 'update_webhook_upon_connection' ), 10, 1 );

			add_filter( 'charitable_admin_strings', array( $this, 'javascript_strings' ) );
		}

		/**
		 * Enqueue scripts and styles.
		 *
		 * @since 1.8.7
		 */
		public function enqueue_scripts_styles() {

			if ( ! charitable_is_admin_screen( 'settings' ) ) {
				return;
			}

			$assets_dir   = charitable()->get_path( 'directory', false ) . 'assets/';
			$min          = charitable_get_min_suffix();
			$version      = charitable()->get_version();
			$dependencies = array();

			wp_register_script(
				'charitable-admin-square-settings',
				$assets_dir . 'js/integrations/square/square-settings.js',
				$dependencies,
				$version,
				false
			);

			wp_enqueue_script( 'charitable-admin-square-settings' );

			wp_enqueue_style(
				'charitable-admin-square',
				$assets_dir . 'css/admin/charitable-admin-square' . $min . '.css',
				array(),
				$version
			);
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.1.0
		 *
		 * @return Charitable_Square_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Square_Admin();
			}

			return self::$instance;
		}


		/**
		 * When Square settings are saved, maybe run background processes to set hidden settings.
		 *
		 * @since  1.8.7
		 *
		 * @param  array $values     The submitted values.
		 * @param  array $new_values The new settings.
		 * @param  array $old_values The previous settings.
		 * @return array
		 */
		public function save_square_settings( $values, $new_values, $old_values ) {

			if ( charitable_is_debug( 'webhook' ) ) {
				error_log( 'save_square_settings'); // phpcs:ignore
				error_log( print_r( $values, true ) ); // phpcs:ignore
				error_log( print_r( $new_values, true ) ); // phpcs:ignore
				error_log( print_r( $old_values, true ) ); // phpcs:ignore
			}

			/* Bail early if this is not the Square settings page. */
			if ( ! array_key_exists( 'gateways_square', $values ) ) {
				return $values;
			}

			/* Bail early if Square is not an active gateway */
			if ( isset( $values['active_gateways'] ) && ! array_key_exists( 'gateways_square', $values['active_gateways'] ) ) {
				return $values;
			}

			return $values;
		}

		/**
		 * Localize needed strings.
		 *
		 * @since 1.8.7
		 *
		 * @param array $strings JS strings.
		 *
		 * @return array
		 */
		public function javascript_strings( $strings ): array {

			$strings = (array) $strings;

			$strings['square'] = array(
				'mode_update'                => wp_kses(
					__(
						'<p>Switching sandbox/production modes requires Square account reconnection.</p><p>Press the <em>"Connect with Square"</em> button after saving the settings to reconnect.</p>',
						'charitable'
					),
					array(
						'p'  => array(),
						'em' => array(),
					)
				),
				'refresh_error'              => esc_html__( 'Something went wrong while performing a refresh tokens request.', 'charitable' ),
				'webhook_create_title'       => esc_html__( 'Personal Access Token', 'charitable' ),
				'webhook_create_description' => sprintf(
					wp_kses( /* translators: %s - the Square developer dashboard URL. */
						__( '<p>To receive events, create a webhook route by providing your Personal Access Token, which you can find after registering an app on the <a href="%1$s" target="_blank">Square Developer Dashboard</a>. You can also set it up manually in the Advanced section.</p><p>See <a href="%2$s" target="_blank">our documentation</a> for details.</p>', 'charitable' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
							'p' => array(),
						)
					),
					esc_url( self::SQUARE_APPS_URL ),
					esc_url( charitable_utm_link( 'https://wpcharitable.com/docs/setting-up-square-webhooks/', 'Settings - Payments', 'Square Webhooks Documentation Modal' ) )
				),

				'webhook_token_placeholder'  => esc_html__( 'Personal Access Token', 'charitable' ),
				'token_is_required'          => esc_html__( 'Personal Access Token is required to proceed.', 'charitable' ),
				'webhook_urls'               => array(
					'rest' => '', // Helpers::get_webhook_url_for_rest(),
					'curl' => '', // Helpers::get_webhook_url_for_curl(),
				),
			);

			return $strings;
		}
	}

endif;
