<?php
/**
 * Class responsible for secure Square initialization.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Initialization
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Initialization' ) ) :

	/**
	 * Charitable_Square_Initialization
	 *
	 * @since 1.8.7
	 */
	class Charitable_Square_Initialization {

		/**
		 * Single instance of this class.
		 *
		 * @since 1.8.7
		 *
		 * @var   Charitable_Square_Initialization
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.8.7
		 */
		private function __construct() {
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.8.7
		 *
		 * @return Charitable_Square_Initialization
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Initialize Square securely.
		 * Deprecated even though called?
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		public function initialize_square() {
			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'charitable_square_initialize' ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'charitable' ) ) );
			}

			// Get current mode.
			$test_mode = charitable_get_option( 'test_mode' );
			$mode      = $test_mode ? 'test' : 'live';

			// Get Square gateway instance.
			$gateway = Charitable_Gateway_Square::get_instance();

			// Get initialization data.
			$data = array(
				'location'             => $gateway->get_square_location_id( $mode ),
				'enable_google_pay'    => false,
				'enable_apple_pay'     => false,
				'wallet_not_supported' => __( 'Recurring payments can\'t be processed using Google Pay or Apple Pay. Please enter your card details.', 'charitable' ),
			);

			// Log initialization attempt.
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( sprintf(
					'[Charitable Square] Initializing Square in %s mode',
					$mode
				) );
				// phpcs:enable
			}

			wp_send_json_success( $data );
		}
	}

endif;
