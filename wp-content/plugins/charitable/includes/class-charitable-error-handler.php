<?php
/**
 * The error handler to suppress text domain loading notices.
 *
 * @package     Charitable/Classes/Charitable_Error_Handler
 * @version     1.8.7
 * @author      WP Charitable LLC
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Error_Handler' ) ) :

	/**
	 * Charitable_Error_Handler
	 *
	 * @since 1.8.6.2
	 */
	class Charitable_Error_Handler {

		/**
		 * Previous error handler.
		 *
		 * @since 1.8.6.2
		 *
		 * @var callable|null
		 */
		private $previous_error_handler;

		/**
		 * Whether the error handler is handling an error.
		 *
		 * @since 1.8.6.2
		 *
		 * @var bool
		 */
		private $handling = false;

		/**
		 * Class constructor.
		 *
		 * @since 1.8.6.2
		 */
		public function __construct() {
			// Empty constructor.
		}

		/**
		 * Init class.
		 *
		 * @since 1.8.6.2
		 *
		 * @return void
		 */
		public function init() {
			if ( defined( 'CHARITABLE_DISABLE_ERROR_HANDLER' ) && CHARITABLE_DISABLE_ERROR_HANDLER ) {
				return;
			}

			$this->hooks();
		}

		/**
		 * Add hooks.
		 *
		 * @since 1.8.6.2
		 *
		 * @return void
		 */
		protected function hooks() {
			// Only hook into WordPress 6.7+ textdomain notices.
			if ( version_compare( $GLOBALS['wp_version'], '6.7', '>=' ) ) {
				add_action( 'doing_it_wrong_run', array( $this, 'action_doing_it_wrong_run' ), 0, 3 );
				add_action( 'doing_it_wrong_run', array( $this, 'action_doing_it_wrong_run' ), 20, 3 );
				add_filter( 'doing_it_wrong_trigger_error', array( $this, 'filter_doing_it_wrong_trigger_error' ), 10, 4 );
			}
		}

		/**
		 * Action for _doing_it_wrong() calls.
		 *
		 * @since 1.8.6.2
		 *
		 * @param string $function_name The function that was called.
		 * @param string $message       A message explaining what has been done incorrectly.
		 * @param string $version       The version of WordPress where the message was added.
		 *
		 * @return void
		 */
		public function action_doing_it_wrong_run( $function_name, $message, $version ) {
			global $wp_filter;

			$function_name = (string) $function_name;
			$message       = (string) $message;

			if ( ! class_exists( 'QM_Collectors' ) || ! $this->is_just_in_time_for_charitable_pro_domain( $function_name, $message ) ) {
				return;
			}

			$qm_collector_doing_it_wrong = QM_Collectors::get( 'doing_it_wrong' );
			$current_priority            = $wp_filter['doing_it_wrong_run']->current_priority();

			if ( $qm_collector_doing_it_wrong === null || $current_priority === false ) {
				return;
			}

			switch ( $current_priority ) {
				case 0:
					remove_action( 'doing_it_wrong_run', array( $qm_collector_doing_it_wrong, 'action_doing_it_wrong_run' ) );
					break;
				case 20:
					add_action( 'doing_it_wrong_run', array( $qm_collector_doing_it_wrong, 'action_doing_it_wrong_run' ), 10, 3 );
					break;
			}
		}

		/**
		 * Filter for _doing_it_wrong() calls.
		 *
		 * @since 1.8.6.2
		 *
		 * @param bool|mixed $trigger       Whether to trigger the error for _doing_it_wrong() calls. Default true.
		 * @param string     $function_name The function that was called.
		 * @param string     $message       A message explaining what has been done incorrectly.
		 * @param string     $version       The version of WordPress where the message was added.
		 *
		 * @return bool
		 */
		public function filter_doing_it_wrong_trigger_error( $trigger, $function_name, $message, $version ): bool {
			$trigger       = (bool) $trigger;
			$function_name = (string) $function_name;
			$message       = (string) $message;

			return $this->is_just_in_time_for_charitable_pro_domain( $function_name, $message ) ? false : $trigger;
		}

		/**
		 * Whether it is the just_in_time_error for Charitable-related domains.
		 *
		 * @since 1.8.6.2
		 *
		 * @param string $function_name Function name.
		 * @param string $message       Message.
		 *
		 * @return bool
		 */
		protected function is_just_in_time_for_charitable_pro_domain( string $function_name, string $message ): bool {
			if ( $function_name !== '_load_textdomain_just_in_time' ) {
				return false;
			}

			$domains = array(
				// Core domains.
				'charitable-pro',
				'charitable',
				// Addon domains.
				'charitable-recurring',
				'charitable-ambassadors',
				'charitable-newsletter',
				'charitable-stripe',
				'charitable-paypal',
				'charitable-payfast',
				'charitable-square',
				'charitable-shoutouts',
				'charitable-pdf-receipts',
				'charitable-windcave',
				'charitable-easy-digital-downloads-connect',
				'charitable-geolocation',
			);

			foreach ( $domains as $domain ) {
				if ( strpos( $message, '<code>' . $domain ) !== false ) {
					return true;
				}
			}

			return false;
		}
	}

endif;
