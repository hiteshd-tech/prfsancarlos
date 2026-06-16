<?php
/**
 * A helper class for logging deprecated arguments, functions and methods.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Deprecated
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Deprecated' ) ) :

	/**
	 * Charitable_Deprecated
	 *
	 * @since 1.4.0
	 */
	class Charitable_Stripe_Deprecated extends Charitable_Deprecated {

		/**
		 * One true class object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Stripe_Deprecated
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.4.0
		 */
		private function __construct() {
			$this->context = 'Charitable Stripe';
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.4.0
		 *
		 * @return Charitable_Stripe_Deprecated
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
