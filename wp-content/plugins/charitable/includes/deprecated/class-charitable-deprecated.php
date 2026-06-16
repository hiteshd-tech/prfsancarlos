<?php
/**
 * A helper class for logging deprecated arguments, functions and methods.
 *
 * @package   Charitable/Classes/Charitable_Deprecated
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.4.0
 * @version   1.5.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Deprecated' ) ) :

	/**
	 * Charitable_Deprecated
	 *
	 * @since 1.4.0
	 */
	class Charitable_Deprecated {

		/**
		 * One true class object.
		 *
		 * @since 1.4.0
		 *
		 * @var   Charitable_Deprecated
		 */
		private static $instance = null;

		/**
		 * Whether logging is enabled.
		 *
		 * @since 1.4.0
		 *
		 * @var   $logging
		 */
		private static $logging;

		/**
		 * Plugin that this is being called for.
		 *
		 * @since 1.5.9
		 *
		 * @var   string
		 */
		protected $context;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.4.0
		 */
		private function __construct() {
			$this->context = 'Charitable';
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.4.0
		 *
		 * @return Charitable_Deprecated
		 */
		public static function get_instance() {
			return self::$instance ??= new self();
		}

		/**
		 * Log a deprecated argument.
		 *
		 * @since  1.4.0
		 *
		 * @param  string      $function      The deprecated function.
		 * @param  string      $version       The version when this argument became deprecated.
		 * @param  string|null $extra_message An extra message to include for the notice.
		 * @return boolean Whether the notice was logged.
		 */
		public function deprecated_argument( $function, $version, $extra_message = null ) {
			if ( ! $this->is_logging_enabled() ) {
				return false;
			}

			if ( ! is_null( $extra_message ) ) {
				// translators: 1: function name, 2: version number, 3: extra message, 4: plugin name.
				$message = sprintf( __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s of %4$s! %3$s', 'charitable' ), $function, $version, $extra_message, $this->context );
			} else {
				// translators: 1: function name, 2: version number, 3: plugin name.
				$message = sprintf( __( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s of %3$s with no alternatives available.', 'charitable' ), $function, $version, $this->context );
			}

			trigger_error( $message ); // phpcs:ignore

			return true;
		}

		/**
		 * Log a deprecated function.
		 *
		 * @since  1.4.0
		 *
		 * @param  string      $function    The function that has been deprecated.
		 * @param  string      $version     The version of Charitable where the function was deprecated.
		 * @param  string|null $replacement Optional. The function to use instead.
		 * @return boolean Whether the notice was logged.
		 */
		public function deprecated_function( $function, $version, $replacement = null ) {
			if ( ! $this->is_logging_enabled() ) {
				return false;
			}

			if ( ! is_null( $replacement ) ) {
				// translators: 1: function name, 2: version number, 3: replacement function name, 4: plugin name.
				$message = sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s of %4$s! Use %3$s instead.', 'charitable' ), $function, $version, $replacement, $this->context );
			} else {
				// translators: 1: function name, 2: version number, 3: plugin name.
				$message = sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s of %3$s with no alternatives available.', 'charitable' ), $function, $version, $this->context );
			}

			trigger_error( $message ); // phpcs:ignore

			return true;
		}

		/**
		 * Log a general "doing it wrong" notice.
		 *
		 * @since  1.4.0
		 *
		 * @param  string $function The function with the problem.
		 * @param  string $message  An error message.
		 * @param  string $version  The version in which this error message was addd.
		 * @return boolean Whether the notice was logged.
		 */
		public function doing_it_wrong( $function, $message, $version ) {
			if ( ! $this->is_logging_enabled() ) {
				return false;
			}

			// translators: 1: function name, 2: error message, 3: version number.
			$version = is_null( $version ) ? '' : sprintf( __( '(This message was added in %1$s version %2$s.)', 'charitable' ), $this->context, $version );

			// translators: 1: function name, 2: error message, 3: version number.
			$message = sprintf( __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s', 'charitable' ), $function, $message, $version );

			trigger_error( $message ); // phpcs:ignore

			return true;
		}

		/**
		 * Returns whether logging is enabled.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		private function is_logging_enabled() {
			if ( ! isset( self::$logging ) ) {
				self::$logging = WP_DEBUG && apply_filters( 'charitable_log_deprecated_notices', true );
			}

			return self::$logging;
		}
	}

endif;
