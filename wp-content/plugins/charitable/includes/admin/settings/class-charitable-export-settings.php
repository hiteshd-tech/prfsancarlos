<?php
/**
 * Charitable Export Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Export_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0.7
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Export_Settings' ) ) :

	/**
	 * Charitable_Export_Settings
	 *
	 * @since 1.6.0
	 */
	final class Charitable_Export_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.6.0
		 *
		 * @var    Charitable_Export_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.6.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.6.0
		 *
		 * @return  Charitable_Export_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.6.0
		 *
		 * @return string[]
		 */
		protected function get_user_donation_field_options() {
			$fields = charitable()->donation_fields()->get_data_type_fields( 'user' );

			return array_combine(
				array_keys( $fields ),
				wp_list_pluck( $fields, 'label' )
			);
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.7.0.7
		 *
		 * @return string[]
		 */
        protected function get_campaigns_to_export( $include_donations = false ) {

            $campaigns = array();

			$args = array(
                'posts_per_page' => -1,
                'orderby'        => 'post_title',
                'order'          => 'ASC',
                'fields'         => array('post_title', 'ID')
			);

			$results = Charitable_Campaigns::query( $args );

            if ( empty( $results->posts ) ) {
                return array();
            }

            foreach ( $results->posts as $index => $post ) :
                $campaigns[ $post->ID ] = $post->post_title;
                if ( $include_donations ) {
                    $donations      = charitable_get_table( 'campaign_donations' )->get_donations_on_campaign( $post->ID );
                    $donation_count = count( $donations );
                    $plural         = ( ( $donation_count > 1 ) || 0 === $donation_count ) ? 's' : '';
                    $campaigns[ $post->ID ] = $post->post_title . ' (' . count( $donations ) . ' donation' . $plural . ') ';
                }
            endforeach;

            return( $campaigns );

        }
	}

endif;
