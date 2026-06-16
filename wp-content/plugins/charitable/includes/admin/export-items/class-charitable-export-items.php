<?php
/**
 * This class is responsible for adding the Charitable admin pages.
 *
 * @package   Charitable/Classes/Charitable_Export_Items
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Export_Items' ) ) :

	/**
	 * Charitable_Export_Items
	 *
	 * @since 1.7.0.7
	 */
	final class Charitable_Export_Items {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Export_Items|null
		 */
		private static $instance = null;


		/**
		 * Create class object.
		 *
		 * @since  1.7.0.7
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.7.0.7
		 *
		 * @return Charitable_Export_Items
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * This forces the charitable menu to be open for category and tag pages.
		 *
		 * @since  1.7.0.7
		 *
		 * @return string
		 */
		public function admin_accept_export_campaign_request() {

			global $wpdb;

			if ( ! is_admin() || empty( $_POST ) ) {
				return;
			}

			// Array ( [charitable_nonce] => 56a1cf267c [_wp_http_referer] => /wp-admin/admin.php?page=charitable-settings&tab=tools [charitable_settings] => Array ( [export] => Array ( [export_campaign] => 5 ) ) ) good

			if ( ! isset( $_POST['charitable_nonce'] ) || ! wp_verify_nonce( $_POST['charitable_nonce'], 'export_campaign' ) ) { //	 phpcs:ignore
				return;
			}

			if ( ! isset( $_POST['charitable_settings']['export'] ) ) {
				return;
			}

			$export_args = $_POST['charitable_settings']['export']; // phpcs:ignore

			if ( ! isset( $export_args['export_campaign'] ) || intval( $export_args['export_campaign'] ) === 0 ) {
			if ( charitable_is_debug() ) {
				error_log( 'admin_accept_export_campaign_request: ' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $export_args, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}
				return;
			}

			// Ignore the user aborting the action.
			ignore_user_abort( true );

			// Grab the proper data.
			$campaign_id   = ( isset( $export_args['export_campaign'] ) ) ? absint( $export_args['export_campaign'] ) : false;
			$campaign_post = get_post( $campaign_id );

			// Metadata rows.
			$meta_sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_campaign_%' AND post_id = " . intval( $campaign_id );
			$meta_raw = $wpdb->get_results( $wpdb->prepare( $meta_sql ), OBJECT_K ); // phpcs:ignore

			// author / campaign_creator.
			$author_data = array();
			$author_meta = ( $campaign_post->post_author !== 0 ) ? get_userdata( $campaign_post->post_author ) : array();
			if ( $author_meta ) {
				$author_data = $author_meta->data;
				unset( $author_data->user_pass );
			}

			// categories and tags.
			$categories = get_the_terms( $campaign_id, 'campaign_category' );
			$tags       = get_the_terms( $campaign_id, 'campaign_tag' );

			// feature image.
			$thumbnail = get_the_post_thumbnail_url( $campaign_id, 'full' );

			// Wrapper.
			$data = array(
				'post'              => $campaign_post,
				'meta'              => $meta_raw,
				'campaign_creator'  => $author_data,
				'campaign_category' => $categories,
				'campaign_tag'      => $tags,
				'thumbnail'         => $thumbnail,
			);

			// Allow Addons to add to the Campaign export.
			$data = apply_filters( 'charitable_export_campaign_data', $data, $campaign_id );

			// Set the proper headers.
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=charitable-campaign-' . $campaign_id . '-' . gmdate( 'm-d-Y' ) . '.json' );
			header( 'Expires: 0' );

			// Make the settings downloadable to a JSON file and die.
			die( wp_json_encode( $data ) );
		}

		/**
		 * This forces the charitable menu to be open for category and tag pages.
		 *
		 * @since  1.7.0.7
		 *
		 * @return string
		 */
		public function admin_accept_export_donations_request() {

			global $wpdb;

			if ( ! is_admin() || empty( $_POST ) ) {
				return;
			}

			if ( ! isset( $_POST['charitable_nonce'] ) || ! wp_verify_nonce( $_POST['charitable_nonce'], 'export_donations_from_campaign' ) ) { // phpcs:ignore
				return;
			}

			if ( ! isset( $_POST['charitable_settings']['export'] ) ) {
				return;
			}

			$export_args = $_POST['charitable_settings']['export']; // phpcs:ignore

			if ( ! isset( $export_args['export_donations'] ) || intval( $export_args['export_donations'] ) === 0 ) {
				if ( charitable_is_debug() ) {
					error_log( 'admin_accept_export_donations_request: ' ); // phpcs:ignore
					error_log( print_r( $export_args, true ) ); // phpcs:ignore
				}
				return;
			}

			// Ignore the user aborting the action.
			ignore_user_abort( true );

			// Grab the proper data.
			$campaign_id   = ( isset( $export_args['export_donations'] ) ) ? absint( $export_args['export_donations'] ) : false;
			$campaign_post = get_post( $campaign_id );

			// Metadata rows.
			$meta_sql                = 'SELECT * FROM ' . $wpdb->prefix . 'charitable_campaign_donations WHERE campaign_id = ' . $campaign_id;
			$meta_campaign_donations = $wpdb->get_results( $meta_sql, OBJECT_K ); // phpcs:ignore

			if ( empty( $meta_campaign_donations ) ) {
				return;
			}

			$campaigns = array();
			$donations = array();
			foreach ( $meta_campaign_donations as $index => $campaign_donation ) {
				if ( ! array_key_exists( $campaign_donation->campaign_id, $campaigns ) ) {
					$campaigns[ $campaign_donation->campaign_id ]                   = array();
					$campaigns[ $campaign_donation->campaign_id ]['donation_posts'] = array();
				}
				$donation_post = get_post( $campaign_donation->donation_id );
				if ( $donation_post ) :
					$campaigns[ $campaign_donation->campaign_id ]['donation_posts'][ $campaign_donation->donation_id ]['post'] = $donation_post;
					$meta_sql = "SELECT * FROM $wpdb->postmeta WHERE post_id = " . $campaign_donation->donation_id;
					$meta_raw = $wpdb->get_results( $meta_sql, ARRAY_A ); // phpcs:ignore

					// if 'meta_key' of '_donation_log' exists, then we need to dejson the value and remove any html, then rejson it.
					foreach ( $meta_raw as $meta_key => $meta_value ) {
						if ( ! empty( $meta_value['meta_key'] ) && $meta_value['meta_key'] === '_donation_log' ) {

							if ( empty( $meta_value['meta_value'] ) ) {
								continue;
							}
							$donation_log = maybe_unserialize( $meta_value['meta_value'] );
							if ( ! empty( $donation_log ) ) {
								// this is an array.
								foreach ( $donation_log as $index => $log ) {
									$donation_log[ $index ] = array_map( 'strip_tags', $log );
								}
								$donation_log                        = serialize( $donation_log );
								$meta_raw[ $meta_key ]['meta_value'] = $donation_log; // phpcs:ignore
							}

						}
					}

					$campaigns[ $campaign_donation->campaign_id ]['donation_posts'][ $campaign_donation->donation_id ]['meta'] = $meta_raw;

					$author_data = false;
					$author_meta = ( $donation_post->post_author !== 0 ) ? get_userdata( $donation_post->post_author ) : false;

					if ( $author_meta ) {
						$author_data = $author_meta->data;
						unset( $author_data->user_pass );
					}

					$campaigns[ $campaign_donation->campaign_id ]['donation_posts'][ $campaign_donation->donation_id ]['author'] = $author_data;
				endif;
				$campaigns[ $campaign_donation->campaign_id ]['campaign']                      = get_post( $campaign_donation->campaign_id );
				$campaigns[ $campaign_donation->campaign_id ]['charitable_campaign_donations'] = $meta_campaign_donations;
			}

			// Wrapper.
			$data = array( 'campaigns' => $campaigns );

			// Allow Addons to add to the Campaign export.
			$data = apply_filters( 'charitable_export_campaign_donations_data', $data, $campaign_id );

			// Set the proper headers.
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=charitable-donations-' . $campaign_id . '-' . gmdate( 'm-d-Y' ) . '.json' );
			header( 'Expires: 0' );

			// Make the settings downloadable to a JSON file and die.
			die( wp_json_encode( $data ) );
		}
	}

endif;
