<?php
/**
 * Donate endpoint.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Widget_Endpoint
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.55
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Charitable_Campaign_Widget_Endpoint' ) ) :

	/**
	 * Charitable_Campaign_Widget_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Campaign_Widget_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'campaign_widget';

		/**
		 * Return the endpoint ID.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public static function get_endpoint_id() {
			return self::ID;
		}

		/**
		 * Add rewrite rules for the endpoint.
		 *
		 * @since 1.5.0
		 */
		public function setup_rewrite_rules() {
			add_rewrite_endpoint( 'widget', EP_PERMALINK );
		}

		/**
		 * Return the endpoint URL.
		 *
		 * @since  1.5.0
		 * @version 1.8.9.1
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed args.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			global $wp_rewrite;

			$campaign_id = array_key_exists( 'campaign_id', $args ) ? $args['campaign_id'] : get_the_ID();

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( $wp_rewrite->using_permalinks() && ! isset( $_GET['preview'] ) ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
				$url = trailingslashit( get_permalink( $campaign_id ) ) . 'widget/';
			} else {
				$url = esc_url_raw( add_query_arg( array( 'widget' => 1 ), get_permalink( $campaign_id ) ) );
			}

			return $url;
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed args.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			return $wp_query->is_main_query()
				&& array_key_exists( 'widget', $wp_query->query_vars )
				&& $wp_query->is_singular( Charitable::CAMPAIGN_POST_TYPE );
		}

		/**
		 * Prepare the template for the endpoint.
		 *
		 * @since  1.6.55
		 *
		 * @return void
		 */
		public function setup_template() {
			do_action( 'charitable_is_widget' );

			add_filter( 'show_admin_bar', '__return_false' );
			add_action( 'wp_head', 'charitable_hide_admin_bar' );
		}

		/**
		 * Return the template to display for this endpoint.
		 *
		 * @since  1.5.0
		 * @since  1.6.55 Returns array instead of string.
		 *
		 * @param  string $template The default template.
		 * @return array
		 */
		public function get_template( $template ) {
			return apply_filters( 'charitable_widget_page_template', array( 'campaign-widget.php' ) );
		}
	}

endif;
