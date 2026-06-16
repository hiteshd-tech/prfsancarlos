<?php
/**
 * Email preview endpoint.
 *
 * @package   Charitable/Classes/Charitable_Email_Preview_Endpoint
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.55
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Preview_Endpoint' ) ) :

	/**
	 * Charitable_Email_Preview_Endpoint
	 *
	 * @since  1.5.0
	 */
	class Charitable_Email_Preview_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'email_preview';

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
		 * Return the endpoint URL.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $args Mixed arguments.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			if ( ! array_key_exists( 'email_id', $args ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'Missing email_id in args for email preview endpoint URL.', 'charitable' ),
					'1.5.0'
				);

				return '';
			}

			return esc_url_raw(
				add_query_arg(
					array(
						'charitable_action' => 'preview_email',
						'email_id'          => $args['email_id'],
					),
					home_url()
				)
			);
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $args Mixed arguments.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			return array_key_exists( 'charitable_action', $_GET ) && 'preview_email' === $_GET['charitable_action']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Whether the endpoint uses a custom template.
		 *
		 * @since  1.8.1.6
		 *
		 * @return boolean
		 */
		public function uses_custom_template() {
			return true;
		}

		/**
		 * Return the template to display for this endpoint.
		 *
		 * @since  1.5.0
		 * @since  1.6.55 Now returns an array.
		 *
		 * @param  string $template The default template.
		 * @return array
		 */
		public function get_template( $template ) {

			do_action( 'charitable_email_preview' );

			return array( 'emails/preview.php' );
		}
	}

endif;
