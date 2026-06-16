<?php
/**
 * Logout shortcode class.
 *
 * @package   Charitable/Shortcodes/Logout
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.45
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Logout_Shortcode' ) ) :

	/**
	 * Charitable_Logout_Shortcode class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Logout_Shortcode {

		/**
		 * The callback method for the logout shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $atts User-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts = array() ) {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$defaults = array(
				'redirect' => charitable_get_current_url(),
				'text'     => __( 'Logout', 'charitable' ),
			);

			$args = shortcode_atts( $defaults, $atts, 'charitable_logout' );

			ob_start();

			charitable_template( 'shortcodes/logout.php', $args );

			/**
			 * Filter the output of the login shortcode.
			 *
			 * @since 1.5.0
			 *
			 * @param string $content Shortcode output.
			 */
			return apply_filters( 'charitable_logout_shortcode', ob_get_clean() );
		}
	}

endif;
