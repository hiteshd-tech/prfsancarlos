<?php
/**
 * Charitable Intergration For SMTP.
 *
 * @package   Charitable/Classes/Class_Intergrations_SMTP
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.8
 * @version   1.8.1.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Intergrations_SMTP' ) ) :

	/**
	 * Charitable_Tools
	 *
	 * @final
	 * @since 1.8.1.8
	 */
	class Charitable_Intergrations_SMTP {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.8.1.8
		 *
		 * @var    Charitable_Tools_Settings|null
		 */
		private static $instance = null;

		/**
		 * Load the class.
		 *
		 * @since 1.8.1.8
		 */
		public function load() {

			$this->hooks();
		}

		/**
		 * Hooks.
		 *
		 * @since 1.8.1.8
		 */
		private function hooks() {
		}

		/**
		 * Enqueue assets.
		 *
		 * @since   1.8.1.8
		 */
		public function enqueue_scripts() {

			if ( ! class_exists( 'Charitable' ) ) {
				return;
			}

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( ( ! empty( $_GET['page'] ) && 'charitable-smtp' === $_GET['page'] ) ) { // phpcs:ignore

				wp_enqueue_style(
					'charitable-smtp',
					$assets_dir . 'css/integrations/smtp.css',
					array(),
					$version
				);

				wp_register_script(
					'charitable-admin-plugins',
					$assets_dir . 'js/plugins/charitable-admin-plugins' . $min . '.js',
					array( 'jquery' ),
					$version,
					true
				);

			}
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.8.1.8
		 *
		 * @return  Charitable_Tools_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
