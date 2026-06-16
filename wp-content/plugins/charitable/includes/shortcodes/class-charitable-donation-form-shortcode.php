<?php
/**
 * Donation Form shortcode class.
 *
 * @package   Charitable/Shortcodes/Donation Form
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.8.3.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Form_Shortcode' ) ) :

	/**
	 * Charitable_Donation_Form_Shortcode class.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Donation_Form_Shortcode {

		/**
		 * The callback method for the donation form shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $atts User-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts ) {
			$defaults = array(
				'campaign_id' => 0,
				'type'        => 'full',
			);

			/* Parse incoming $atts into an array and merge it with $defaults. */
			$args = shortcode_atts( $defaults, $atts, 'charitable_donation_form' );

			ob_start();

			charitable_template_donation_form( $args['campaign_id'], $args );

			return ob_get_clean();
		}
	}

endif;
