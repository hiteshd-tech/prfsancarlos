<?php
/**
 * Email shortcode class.
 *
 * @package   Charitable/Shortcodes/Email
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 * @version   1.8.9.5 Added recursion prevention for email shortcodes.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Shortcode' ) ) :

	/**
	 * Charitable_Email_Shortcode class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Shortcode {

		/**
		 * Static object instance.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Email_Shortcode
		 */
		private static $instance;

		/**
		 * The `Charitable_Email_Fields` instance.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Email_Fields
		 */
		private $fields;

		/**
		 * Processing stack to track active shortcodes and prevent recursion.
		 *
		 * @since 1.8.9.5
		 *
		 * @var   array
		 */
		private static $processing_stack = array();

		/**
		 * Processing depth counter for recursion detection.
		 *
		 * @since 1.8.9.5
		 *
		 * @var   int
		 */
		private static $processing_depth = 0;

		/**
		 * Set up class instance.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Email_Fields $fields The Charitable_Email_Fields instance.
		 */
		private function __construct( Charitable_Email_Fields $fields ) {
			$this->fields = $fields;
		}

		/**
		 * Set up the class instance.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Email $email The email object.
		 * @return void
		 */
		public static function init( Charitable_Email $email ) {
			$fields         = new Charitable_Email_Fields( $email, false );
			self::$instance = new self( $fields );
		}

		/**
		 * Set up the class instance, with preview set to true.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Email $email The email object.
		 * @return void
		 */
		public static function init_preview( Charitable_Email $email ) {
			$fields         = new Charitable_Email_Fields( $email, true );
			self::$instance = new self( $fields );
		}

		/**
		 * Flush the class instance.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public static function flush() {
			self::$instance = null;
		}

		/**
		 * The callback method for the campaigns shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $atts User-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts ) {
			if ( ! isset( self::$instance ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( '[charitable_email] cannot be called until a class instance has been created.', 'charitable' ),
					'1.5.0'
				);
				return;
			}

			$defaults = array(
				'show'    => '',
				'preview' => self::$instance->fields->is_preview(),
			);

			$args = apply_filters( 'charitable_email_shortcode_args', wp_parse_args( $atts, $defaults ), $atts, $defaults );

			if ( ! isset( $args['show'] ) ) {
				return '';
			}

			// Recursion prevention - Added in 1.8.9.5
			$recursion_result = self::check_recursion( $args['show'], $args );
			if ( false !== $recursion_result ) {
				return $recursion_result;
			}

			// Add to processing stack
			self::push_processing_stack( $args['show'] );

			// Process the shortcode
			$result = self::$instance->get( $args['show'], $args );

			// Remove from processing stack
			self::pop_processing_stack( $args['show'] );

			return $result;
		}

		/**
		 * Return the value for a particular variable.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field The field.
		 * @param  array  $args  Mixed arguments.
		 * @return string
		 */
		public function get( $field, $args ) {
			$value = apply_filters( 'charitable_email_shortcode_get_value', $this->fields->get_field_value( $field, $args ) );
			return $value;
		}

		/**
		 * Check for recursion and return fallback content if detected.
		 *
		 * @since 1.8.9.5
		 *
		 * @param  string $show The field to show.
		 * @param  array  $args Mixed arguments.
		 * @return string|false False if no recursion, fallback content if recursion detected.
		 */
		private static function check_recursion( $show, $args ) {
			// Check depth limit
			$max_depth = apply_filters( 'charitable_email_recursion_depth_limit', 3 );
			if ( self::$processing_depth >= $max_depth ) {
				self::log_recursion_attempt( 'depth', $show, self::$processing_depth, $max_depth );
				return self::get_context_specific_fallback( $show, 'depth_limit' );
			}

			// Check for direct recursion (same field already being processed)
			$stack_key = $show;
			if ( in_array( $stack_key, self::$processing_stack, true ) ) {
				self::log_recursion_attempt( 'circular', $show, count( self::$processing_stack ), $max_depth );
				return self::get_context_specific_fallback( $show, 'circular_reference' );
			}

			return false; // No recursion detected
		}

		/**
		 * Add a field to the processing stack.
		 *
		 * @since 1.8.9.5
		 *
		 * @param string $show The field being processed.
		 */
		private static function push_processing_stack( $show ) {
			self::$processing_stack[] = $show;
			self::$processing_depth++;
		}

		/**
		 * Remove a field from the processing stack.
		 *
		 * @since 1.8.9.5
		 *
		 * @param string $show The field being processed.
		 */
		private static function pop_processing_stack( $show ) {
			$index = array_search( $show, self::$processing_stack, true );
			if ( false !== $index ) {
				unset( self::$processing_stack[ $index ] );
				self::$processing_stack = array_values( self::$processing_stack ); // Re-index array
			}
			self::$processing_depth = max( 0, self::$processing_depth - 1 );
		}

		/**
		 * Get context-specific fallback content when recursion is detected.
		 *
		 * @since 1.8.9.5
		 *
		 * @param  string $show      The field that caused recursion.
		 * @param  string $reason    The reason for fallback (depth_limit, circular_reference).
		 * @return string           Fallback content.
		 */
		private static function get_context_specific_fallback( $show, $reason ) {
			$fallbacks = array(
				'offline_instructions' => __( 'Payment instructions will be provided separately.', 'charitable' ),
				'donation_summary'     => __( 'Donation summary unavailable.', 'charitable' ),
				'donor'                => __( 'Donor information unavailable.', 'charitable' ),
				'donor_email'          => __( 'Email unavailable.', 'charitable' ),
				'site_name'            => get_bloginfo( 'name' ),
				'campaign_title'       => __( 'Campaign information unavailable.', 'charitable' ),
			);

			// Get specific fallback or generic one
			$fallback = isset( $fallbacks[ $show ] ) ? $fallbacks[ $show ] : __( 'Content temporarily unavailable.', 'charitable' );

			/**
			 * Filter the fallback content for recursive email shortcodes.
			 *
			 * @since 1.8.9.5
			 *
			 * @param string $fallback The default fallback content.
			 * @param string $show     The field that caused recursion.
			 * @param string $reason   The reason for fallback.
			 */
			return apply_filters( 'charitable_email_shortcode_recursion_fallback', $fallback, $show, $reason );
		}

		/**
		 * Log recursion attempts for debugging.
		 *
		 * @since 1.8.9.5
		 *
		 * @param string $type      Type of recursion (circular, depth).
		 * @param string $show      The field that caused recursion.
		 * @param int    $current   Current depth/stack size.
		 * @param int    $limit     The configured limit.
		 */
		private static function log_recursion_attempt( $type, $show, $current, $limit ) {
			// Only log if both CHARITABLE_DEBUG and CHARITABLE_DEBUG_EMAILS are enabled
			if ( ! charitable_is_debug() || ! defined( 'CHARITABLE_DEBUG_EMAILS' ) || ! CHARITABLE_DEBUG_EMAILS ) {
				return;
			}

			$message = sprintf(
				'[CHARITABLE_EMAIL_DEBUG] Recursion prevented: %s recursion detected for shortcode [charitable_email show="%s"]. Current: %d, Limit: %d, Stack: [%s]',
				$type,
				$show,
				$current,
				$limit,
				implode( ', ', self::$processing_stack )
			);

			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

endif;
