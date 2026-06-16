<?php
/**
 * Displays a dashboard notice when GiveWP is detected.
 *
 * @package   Charitable/Classes/Charitable_GiveWP_Notice
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_GiveWP_Notice' ) ) :

	/**
	 * Charitable_GiveWP_Notice
	 *
	 * @since 1.8.10
	 */
	class Charitable_GiveWP_Notice {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_GiveWP_Notice|null
		 */
		private static $instance = null;

		/**
		 * Create class object.
		 *
		 * @since 1.8.10
		 */
		public function __construct() {
		}

		/**
		 * Check if GiveWP is installed (active or inactive).
		 *
		 * @since 1.8.10
		 *
		 * @return bool
		 */
		private function is_givewp_installed() {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = get_plugins();

			foreach ( $plugins as $plugin_file => $plugin_data ) {
				if ( strpos( $plugin_file, 'give.php' ) !== false || strpos( $plugin_file, 'give/' ) === 0 ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get dashboard notices for GiveWP import suggestion.
		 *
		 * @since 1.8.10
		 *
		 * @return array
		 */
		public function get_dashboard_notices() {
			$dashboard_notices = get_option( 'charitable_dashboard_notifications', array() );

			// If the notice has already been dismissed, bail.
			if ( isset( $dashboard_notices['givewp_import'] ) && isset( $dashboard_notices['givewp_import']['dismissed'] ) ) {
				return $dashboard_notices;
			}

			// If GiveWP is not installed, remove notice if it exists and bail.
			if ( ! $this->is_givewp_installed() ) {
				if ( isset( $dashboard_notices['givewp_import'] ) ) {
					unset( $dashboard_notices['givewp_import'] );
					update_option( 'charitable_dashboard_notifications', $dashboard_notices );
				}
				return $dashboard_notices;
			}

			// If the notice already exists and has the current format, don't add it again.
			if ( isset( $dashboard_notices['givewp_import'] ) && ! empty( $dashboard_notices['givewp_import']['button_url'] ) ) {
				return $dashboard_notices;
			}

			$dashboard_notices['givewp_import'] = array(
				'type'       => 'notice',
				'priority'   => 5,
				'dismiss'    => true,
				'title'      => esc_html__( 'Notice', 'charitable' ),
				'custom_css' => 'charitable-notification-type-notice',
				'message'     => '<p>' . __( 'We noticed you have GiveWP installed. You can import your donation data from GiveWP to Charitable.', 'charitable' ) . '</p>',
				'button_url'  => admin_url( 'admin.php?page=charitable-tools&tab=import' ),
				'button_text' => __( 'Import Now', 'charitable' ),
			);

			update_option( 'charitable_dashboard_notifications', $dashboard_notices );

			return $dashboard_notices;
		}

		/**
		 * Create and return the class object.
		 *
		 * @since 1.8.10
		 *
		 * @return Charitable_GiveWP_Notice
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
