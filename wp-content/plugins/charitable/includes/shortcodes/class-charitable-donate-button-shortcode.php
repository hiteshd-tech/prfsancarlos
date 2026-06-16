<?php
/**
 * Donate Button shortcode class.
 *
 * @package   Charitable/Shortcodes/Donate_Button
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donate_Button_Shortcode' ) ) :

	/**
	 * Charitable_Donate_Button_Shortcode class.
	 *
	 * @since 1.8.2
	 */
	class Charitable_Donate_Button_Shortcode {

		/**
		 * The callback method for the doante button shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since  1.8.2
		 * @version 1.8.10.2
		 *
		 * @param  array $atts User-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts = array() ) {
			$defaults = array(
				'url'      => charitable_get_current_url(),
				'label'    => __( 'Donate', 'charitable' ),
				'type'     => 'button', // can also be 'link'.
				'css'      => 'charitable-button',
				'new_tab'  => false,
				'campaign' => 0,
			);

			if ( isset( $atts['campaign'] ) ) {
				$campaign_id = (int) $atts['campaign'];
				if ( $campaign_id > 0 ) {
					$atts['url'] = charitable_get_permalink( 'campaign_donation', array( 'campaign_id' => $campaign_id ) );
				}
			}

			$args = shortcode_atts( $defaults, $atts, 'charitable_donate_button' );

			ob_start();

			charitable_template( 'shortcodes/donate-button.php', $args );

			/**
			 * Filter the output of the login shortcode.
			 *
			 * @since 1.8.2
			 *
			 * @param string $content Shortcode output.
			 */
			return apply_filters( 'charitable_donate_button_shortcode', ob_get_clean() );
		}
	}

endif;
