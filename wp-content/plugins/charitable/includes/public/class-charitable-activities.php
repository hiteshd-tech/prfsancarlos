<?php
/**
 * This class is responsible for setting up and general maintenance of activity logs.
 *
 * @package   Charitable/Classes/Charitable_Activities
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Activities' ) ) :

	/**
	 * Charitable_Activities
	 *
	 * @since 1.8.1
	 */
	final class Charitable_Activities {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Activities|null
		 */
		private static $instance = null;


		/**
		 * Create class object.
		 *
		 * @since  1.8.1
		 */
		private function __construct() {


		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Activities
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}



	}

endif;
