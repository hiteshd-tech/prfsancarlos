<?php
/**
 * Endpoint abstract model.
 *
 * @package   Charitable/Classes/Charitable_Endpoint
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.55
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Endpoint' ) ) :

	/**
	 * Charitable_Endpoint
	 *
	 * @since 1.5.0
	 */
	abstract class Charitable_Endpoint implements Charitable_Endpoint_Interface {

		/** The endpoint's unique identifier. */
		const ID = '';

		/** The endpoint's priority in terms of when it should be loaded. */
		const PRIORITY = 10;

		/**
		 * Whether the endpoint can be cached.
		 *
		 * @since 1.5.4
		 *
		 * @var   boolean
		 */
		protected $cacheable = true;

		/**
		 * Whether comments are disabled on this endpoint.
		 *
		 * @since 1.6.36
		 *
		 * @var   boolean
		 */
		protected $comments_disabled = true;

		/**
		 * Add rewrite rules for the endpoint.
		 *
		 * Unless the child class defines this, this won't do anything for an endpoint.
		 *
		 * @since 1.5.0
		 */
		public function setup_rewrite_rules() {
			/* Do nothing by default. */
		}

		/**
		 * Add new query vars for the endpoint.
		 *
		 * Unless the child class defines this, this won't do anything for an endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $vars The query vars.
		 * @return array
		 */
		public function add_query_vars( array $vars ) {
			/* Return vars unchanged by default. */
			return $vars;
		}

		/**
		 * If the user should be redirected somewhere, return a URL. Otherwise,
		 * simply return false.
		 *
		 * @since  1.6.26
		 *
		 * @return false|string
		 */
		public function get_redirect() {
			return false;
		}

		/**
		 * Whether the endpoint uses a custom template.
		 *
		 * @since  1.6.55
		 *
		 * @return boolean
		 */
		public function uses_custom_template() {
			return ! charitable_is_block_theme();
		}

		/**
		 * Prepare the template for the endpoint.
		 *
		 * @since  1.6.55
		 *
		 * @return void
		 */
		public function setup_template() {
			return;
		}

		/**
		 * Set up the endpoint template.
		 *
		 * By default, we will return the default template that WordPress already selected.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $template The default template.
		 * @return string|array
		 */
		public function get_template( $template ) {
			return $template;
		}

		/**
		 * Get the content to display for the endpoint.
		 *
		 * By default, we will return the default content that is passed by WordPress.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $content The default content.
		 * @return string
		 */
		public function get_content( $content ) {
			return $content;
		}

		/**
		 * Return the body class to add for the endpoint.
		 *
		 * By default, this will be the endpoint ID with underscores replaced by hyphens.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_body_class() {
			return str_replace( '_', '-', $this->get_endpoint_id() );
		}

		/**
		 * Whether an endpoint can be cached.
		 *
		 * @since  1.5.4
		 *
		 * @return boolean
		 */
		public function is_cacheable() {
			return $this->cacheable;
		}

		/**
		 * Whether comments are disabled for this endpoint.
		 *
		 * @since  1.6.36
		 *
		 * @return boolean
		 */
		public function comments_disabled() {
			return $this->comments_disabled;
		}

		/**
		 * Return a nav menu object, or null if the endpoint should not be
		 * added to navigation menus.
		 *
		 * @since  1.6.29
		 *
		 * @return object|null
		 */
		public function nav_menu_object() {
			return null;
		}

		/**
		 * Return a nav menu object.
		 *
		 * @since  1.6.29
		 *
		 * @param  string $title Menu item title.
		 * @return object
		 */
		public function get_nav_menu_object( $title ) {
			$menu_object                   = new stdClass();
			$menu_object->classes          = array( 'charitable-menu' );
			$menu_object->type             = 'custom';
			$menu_object->object_id        = $this->get_endpoint_id();
			$menu_object->title            = $title;
			$menu_object->object           = 'custom';
			$menu_object->url              = esc_url_raw( $this->get_page_url() );
			$menu_object->attr_title       = $title;
			$menu_object->db_id            = '';
			$menu_object->menu_item_parent = 0;
			$menu_object->target           = '';
			$menu_object->xfn              = '';

			return $menu_object;
		}

		/**
		 * Given a base URL and a slug to append, return an
		 * updated endpoint URL.
		 *
		 * @since  1.6.44
		 *
		 * @param  string $base_url The base URL.
		 * @param  string $slug     The slug to append.
		 * @return string
		 */
		public function sanitize_endpoint_url( $base_url, $slug ) {
			$url_parts = parse_url( $base_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

			if ( ! array_key_exists( 'query', $url_parts ) ) {
				return trailingslashit( $base_url ) . trailingslashit( $slug );
			}

			/* Strip out the query string. */
			$base_url = str_replace( '?' . $url_parts['query'], '', $base_url );

			/* Append our slug, then the query string. */
			return trailingslashit( $base_url ) . trailingslashit( $slug ) . '?' . $url_parts['query'];
		}
	}

endif;
