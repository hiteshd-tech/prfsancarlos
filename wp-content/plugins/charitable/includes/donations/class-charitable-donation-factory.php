<?php
/**
 * Donation Factory Class
 *
 * The Charitable donation factory creating the right donation objects.
 *
 * @package   Charitable/Classes/Charitable_Donation_Factory
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Factory' ) ) :

	/**
	 * Donation Factory
	 *
	 * @since   1.4.0
	 */
	class Charitable_Donation_Factory {

		/**
		 * Get donation.
		 *
		 * @since  1.4.0
		 *
		 * @param  boolean|int|Charitable_Donation $donation A donation object, ID or false.
		 * @return Charitable_Donation|boolean
		 */
		public function get_donation( $donation = false ) {
			global $post;

			if ( false === $donation ) {
				$donation = $post;
			} elseif ( is_numeric( $donation ) ) {
				$donation = get_post( $donation );
			} elseif ( $donation instanceof Charitable_Donation ) {
				$donation = get_post( $donation->id );
			}

			if ( ! $donation || ! is_object( $donation ) ) {
				return false;
			}

			/**
			 * Filter the list of valid donation types.
			 *
			 * @since 1.4.7
			 *
			 * @param string[] $post_types List of post types that qualify as donations.
			 */
			$valid_post_types = apply_filters(
				'charitable_valid_donation_types',
				array(
					Charitable::DONATION_POST_TYPE,
				)
			);

			if ( ! in_array( $donation->post_type, $valid_post_types ) ) { // phpcs:ignore
				return false;
			}

			$classname = $this->get_donation_class( $donation );

			if ( ! class_exists( $classname ) ) {
				$classname = 'Charitable_Donation';
			}

			return new $classname( $donation );
		}

		/**
		 * Create a class name e.g. Charitable_Donation_Type_Class instead of charitable_donation_type-class.
		 *
		 * @since   1.4.0
		 *
		 * @param  string $donation_type The type of donation (e.g. 'charitable-donation').
		 * @return string|false The generated class name or false if invalid.
		 */
		private function get_classname_from_donation_type( $donation_type ) {
			return 'Charitable_' . implode( '_', array_map( 'ucfirst', explode( '-', $donation_type ) ) );
		}

		/**
		 * Get the donation class name.
		 *
		 * @since   1.4.0
		 *
		 * @param   WP_Post $the_donation The donation post object.
		 * @return  string The donation class name.
		 */
		private function get_donation_class( $the_donation ) {
			$donation_id   = absint( $the_donation->ID );
			$donation_type = $the_donation->post_type;

			$classname = $this->get_classname_from_donation_type( $donation_type );

			// Filter classname so that the class can be overridden if extended.
			return apply_filters( 'charitable_donation_class', $classname, $donation_type, $donation_id );
		}
	}

endif;
