<?php
/**
 * Webhook listener endpoint.
 *
 * @package   Charitable Authorize.Net/Classes/Charitable_Webhook_Listener_Endpoint
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.14
 * @version   1.6.14
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Webhook_Listener_Endpoint' ) ) :

	/**
	 * Charitable_Webhook_Listener_Endpoint
	 *
	 * @since 1.6.14
	 */
	class Charitable_Webhook_Listener_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID */
		const ID = 'webhook_listener';

		/**
		 * Whether to force HTTPS on the endpoint.
		 *
		 * @since 1.6.14
		 *
		 * @var   boolean
		 */
		private $force_https;

		/**
		 * Object instantiation.
		 *
		 * @since 1.6.14
		 */
		public function __construct() {
			$this->cacheable = false;

			/**
			 * Whether to force HTTPS on the webhook listener endpoint.
			 *
			 * @since 1.6.14
			 *
			 * @param boolean $force_https Whether HTTPS is forced for the webhook listener endpoint.
			 */
			$this->force_https = apply_filters( 'charitable_webhook_listener_endpoint_force_https', false );

			add_action( 'parse_query', array( $this, 'process_incoming_webhook' ) );
		}

		/**
		 * Return the endpoint ID.
		 *
		 * @since  1.6.14
		 *
		 * @return string
		 */
		public static function get_endpoint_id() {
			return self::ID;
		}

		/**
		 * Add rewrite rules for the endpoint.
		 *
		 * @since  1.6.14
		 */
		public function setup_rewrite_rules() {
			add_rewrite_endpoint( 'charitable-listener', EP_ROOT );
			add_rewrite_rule( '^charitable-listener/([^/]+)/$', 'index.php?charitable-listener=$matches[1]', 'bottom' );
		}

		/**
		 * Return the endpoint URL.
		 *
		 * @since  1.6.14
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed args.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			global $wp_rewrite;

			$home_url = $this->force_https ? home_url( '', 'https' ) : home_url();

			if ( $wp_rewrite->using_permalinks() ) {
				$url = sprintf( '%s/charitable-listener/%s', untrailingslashit( $home_url ), $args['gateway'] );
			} else {
				$url = add_query_arg( array( 'charitable-listener' => $args['gateway'] ), $home_url );
				$url = esc_url_raw( $url );
			}

			return $url;
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.6.14
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed args.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			return is_main_query()
				&& array_key_exists( 'charitable-listener', $wp_query->query_vars );
		}

		/**
		 * Process an incoming webhook if we are on the listener page.
		 *
		 * @since  1.6.14
		 *
		 * @param  WP_Query $wp_query The query object.
		 * @return boolean
		 */
		public function process_incoming_webhook( WP_Query $wp_query ) {

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'process_incoming_webhook' );
				// phpcs:enable
			}

			if ( ! $wp_query->is_main_query() ) {
				if ( charitable_is_debug( 'webhook' ) ) {
					// phpcs:disable
					error_log( 'process_incoming_webhook is_main_query = false' );
					// phpcs:enable
				}
				return false;
			}

			/**
			 * Prevent this from being called again for this request.
			 */
			remove_action( 'parse_query', array( $this, 'process_incoming_webhook' ) );

			$gateway = get_query_var( 'charitable-listener', false );

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'process_incoming_webhook - starting to process webhook for gateway ' . $gateway );
				// phpcs:enable
			}

			if ( ! $gateway ) {
				if ( charitable_is_debug( 'webhook' ) ) {
					// phpcs:disable
					error_log( 'process_incoming_webhook no gateway found' );
					// Show the request URL.
					error_log( 'process_incoming_webhook - request URL is ' . $_SERVER['REQUEST_URI'] );
					// phpcs:enable
				}
			}

			if ( $gateway ) {

				if ( charitable_is_debug( 'webhook' ) ) {
					// phpcs:disable
					error_log( 'process_incoming_webhook for gateway ***' . $gateway . '***' );
					// Show the request URL.
					error_log( 'process_incoming_webhook - request URL is ' . $_SERVER['REQUEST_URI'] );
					// phpcs:enable
				}

				// Debug: Log the hook that will be fired.
				if ( charitable_is_debug( 'webhook' ) ) {
					// phpcs:disable
					error_log( 'process_incoming_webhook - will fire hook: charitable_process_ipn_' . $gateway );
					// phpcs:enable
				}

				/**
				 * Handle a gateway's IPN.
				 *
				 * @since 1.0.0
				 */
				do_action( 'charitable_process_ipn_' . $gateway );

				return true;
			}

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'process_incoming_webhook testing for gateway end' );
				// phpcs:enable
			}

			return false;
		}
	}

endif;
