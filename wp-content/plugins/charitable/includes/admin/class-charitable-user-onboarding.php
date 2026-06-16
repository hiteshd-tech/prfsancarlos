<?php
/**
 * Main class for setting up the server side onboarding.
 *
 * @package   Charitable/Classes/Charitable_User_Onboarding
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.4
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_User_Onboarding' ) ) :

	/**
	 * Charitable_User_Onboarding
	 *
	 * @since 1.0.0
	 */
	class Charitable_User_Onboarding {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_User_Onboarding|null
		 */
		private static $instance = null;

		/**
		 * Stores whether the current request is in the user onboarding.
		 *
		 * @since 1.6.42
		 *
		 * @var   boolean
		 */
		private $on_user_onboarding_page;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_User_Onboarding
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Create class instance.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			add_filter( 'body_class', array( $this, 'add_body_class' ) );
			add_action( 'template_include', array( $this, 'load_user_onboarding_template' ) );

			do_action( 'charitable_user_onboarding_start', $this );
		}

		/**
		 * Loads the user onboarding template.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $template The template to use for the user onboarding.
		 * @return string
		 */
		public function load_user_onboarding_template( $template ) {
			/**
			 * The user onboarding template is not loaded by default; this has to be enabled.
			 */
			if ( false === apply_filters( 'charitable_force_user_onboarding_template', false ) ) {
				return $template;
			}

			/**
			 * The current object isn't in the nav, so return the template.
			 */
			if ( ! get_query_var( 'page' ) || 'wpchar_lite' !== get_query_var( 'page' ) ) {
				return $template;
			}

			do_action( 'charitable_is_user_onboarding' );

			$new_template = apply_filters( 'charitable_user_onboarding_template', 'user-onboarding.php' );
			$template     = charitable_get_template_path( $new_template, $template );

			return $template;
		}

		/**
		 * Add the user-onboarding class to the body if we're looking at it.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $classes Body classes.
		 * @return array
		 */
		public function add_body_class( $classes ) {
			// check the query var first.
			if ( isset( $_GET['wpchar_lite'] ) && 'welcome' === $_GET['wpchar_lite'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$classes .= ' wpchar-user-onboarding';
			}

			return $classes;
		}
	}

endif;
