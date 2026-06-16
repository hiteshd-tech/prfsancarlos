<?php
/**
 * The class that is responsible for registering the Upgrades page.
 *
 * @package   Charitable/Classes/Charitable_Upgrade_Page
 * @version   1.3.0
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Upgrade_Page' ) ) :

	/**
	 * Charitable_Upgrade_Page
	 *
	 * @since 1.3.0
	 */
	class Charitable_Upgrade_Page {

		/**
		 * The one and only class instance.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Upgrade_Page
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.3.0
		 */
		private function __construct() {
		}

		/**
		 * Create and return the class object.
		 *
		 * @since 1.3.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register the page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function register_page() {
			add_dashboard_page(
				__( 'Upgrade Charitable', 'charitable' ),
				__( 'Upgrade Charitable', 'charitable' ),
				'manage_charitable_settings',
				'charitable-upgrades',
				array( $this, 'render_page' )
			);
		}

		/**
		 * Remove the page from the dashboard menu.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function remove_page_from_menu() {
			remove_submenu_page( 'index.php', 'charitable-upgrades' );
		}

		/**
		 * Render the page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function render_page() {
			charitable_admin_view( 'upgrades-page/page', array( 'page' => $this ) );
		}

		/**
		 * Return the current upgrade action.
		 *
		 * @since  1.3.0
		 * @version 1.8.9.1
		 *
		 * @return false|string False if no action was specified.
		 */
		public function get_action() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! isset( $_GET['charitable-upgrade'] ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			return sanitize_text_field( wp_unslash( $_GET['charitable-upgrade'] ) );
		}

		/**
		 * Return the current upgrade step.
		 *
		 * @since  1.3.0
		 * @version 1.8.9.1
		 *
		 * @return int
		 */
		public function get_step() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! isset( $_GET['step'] ) ) {
				return 1;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			return absint( wp_unslash( $_GET['step'] ) );
		}

		/**
		 * Return the total number of records to be updated.
		 *
		 * @since  1.3.0
		 * @version 1.8.9.1
		 *
		 * @return false|int
		 */
		public function get_total() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! isset( $_GET['total'] ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			return absint( wp_unslash( $_GET['total'] ) );
		}

		/**
		 * Return the... number?
		 *
		 * @since  1.3.0
		 * @version 1.8.9.1
		 *
		 * @return int
		 */
		public function get_number() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! isset( $_GET['number'] ) ) {
				return 100;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			return absint( wp_unslash( $_GET['number'] ) );
		}

		/**
		 * Return the nonce
		 *
		 * @since  1.8.1
		 * @version 1.8.9.1
		 *
		 * @return false|string
		 */
		public function get_nonce() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! isset( $_GET['nonce'] ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			return sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
		}


		/**
		 * Return the total number of steps.
		 *
		 * @since  1.3.0
		 *
		 * @param  int $total  The total number of records to update.
		 * @param  int $number The number to update in a single step.
		 * @return int
		 */
		public function get_steps( $total, $number ) {
			return round( ( $total / $number ), 0 );
		}
	}

endif;
