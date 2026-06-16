<?php
/**
 * Sets up the /campaign-css/ API route for serving campaign theme CSS.
 *
 * This endpoint serves campaign template CSS with dynamic color values,
 * avoiding direct PHP file access which can fail on some server configurations.
 *
 * @package   Charitable/Classes/Charitable_API_Route_Campaign_CSS
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9.2
 * @version   1.8.9.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_API_Route_Campaign_CSS' ) ) :

	/**
	 * Charitable_API_Route_Campaign_CSS
	 *
	 * @since 1.8.9.2
	 */
	class Charitable_API_Route_Campaign_CSS extends Charitable_API_Route {

		/**
		 * Route base.
		 *
		 * @since 1.8.9.2
		 *
		 * @var string
		 */
		protected $base;

		/**
		 * Set up class instance.
		 *
		 * @since 1.8.9.2
		 *
		 * @return void
		 */
		public function __construct() {
			parent::__construct();

			$this->base = 'campaign-css';
		}

		/**
		 * Register the routes for this controller.
		 *
		 * @since 1.8.9.2
		 */
		public function register_routes() {
			$args = array(
				'template' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_file_name',
					'description'       => __( 'The campaign template slug (e.g., youth-sports, basic).', 'charitable' ),
				),
				'p'        => array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_hex_color' ),
					'description'       => __( 'Primary color (hex without #).', 'charitable' ),
				),
				's'        => array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_hex_color' ),
					'description'       => __( 'Secondary color (hex without #).', 'charitable' ),
				),
				't'        => array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_hex_color' ),
					'description'       => __( 'Tertiary color (hex without #).', 'charitable' ),
				),
				'b'        => array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_hex_color' ),
					'description'       => __( 'Button color (hex without #).', 'charitable' ),
				),
				'mw'       => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 800,
					'description'       => __( 'Mobile breakpoint width in pixels.', 'charitable' ),
				),
			);

			// Frontend CSS route
			register_rest_route(
				$this->namespace,
				'/' . $this->base . '/(?P<template>[a-zA-Z0-9-_]+)',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_campaign_css' ),
					'permission_callback' => '__return_true', // Public endpoint - CSS needs to be accessible.
					'args'                => $args,
				)
			);

			// Admin CSS route (added in 1.8.9.2)
			register_rest_route(
				$this->namespace,
				'/' . $this->base . '-admin/(?P<template>[a-zA-Z0-9-_]+)',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_admin_css' ),
					'permission_callback' => '__return_true', // Public endpoint - CSS needs to be accessible.
					'args'                => $args,
				)
			);
		}

		/**
		 * Sanitize a hex color value (without the # prefix).
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $color The color value to sanitize.
		 * @return string The sanitized color value.
		 */
		public function sanitize_hex_color( $color ) {
			return preg_replace( '/[^A-Fa-f0-9]/', '', $color );
		}

		/**
		 * Get the campaign CSS for a specific template.
		 *
		 * @since 1.8.9.2
		 *
		 * @param WP_REST_Request $request The API request object.
		 * @return void Outputs CSS directly and exits.
		 */
		public function get_campaign_css( $request ) {
			$template = $request->get_param( 'template' );

			// Build the path to the template CSS file.
			$template_file = charitable()->get_path( 'includes' ) . 'css-templates/frontend/' . $template . '.php';

			// Validate template exists.
			if ( ! file_exists( $template_file ) ) {
				status_header( 404 );
				header( 'Content-Type: text/css; charset=UTF-8' );
				echo '/* Template not found: ' . esc_html( $template ) . ' */';
				exit;
			}

			// Get color parameters with defaults.
			$primary      = $request->get_param( 'p' );
			$secondary    = $request->get_param( 's' );
			$tertiary     = $request->get_param( 't' );
			$button       = $request->get_param( 'b' );
			$mobile_width = $request->get_param( 'mw' );

			// Set up variables that the template file expects.
			// These are accessed via $_GET in the original PHP files,
			// so we'll set them up the same way for compatibility.
			$_GET['p']  = $primary;
			$_GET['s']  = $secondary;
			$_GET['t']  = $tertiary;
			$_GET['b']  = $button;
			$_GET['mw'] = $mobile_width;

			// Generate CSS using output buffering.
			ob_start();

			// Save current working directory.
			$original_cwd = getcwd();

			// Change to the template directory so relative paths in template files work.
			chdir( dirname( $template_file ) );

			// Include the template file - it will output CSS.
			include $template_file;

			// Restore original working directory.
			chdir( $original_cwd );

			$css = ob_get_clean();

			// Generate ETag for cache validation.
			$etag = md5( $template . $primary . $secondary . $tertiary . $button . $mobile_width );

			// Set headers for CSS response.
			header( 'Content-Type: text/css; charset=UTF-8' );
			header( 'Cache-Control: public, max-age=31536000' ); // 1 year.
			header( 'ETag: "' . $etag . '"' );

			// Output CSS and exit to prevent REST API from processing further.
			echo $css;
			exit;
		}

		/**
		 * Get the admin CSS for a specific template.
		 *
		 * @since 1.8.9.2
		 *
		 * @param WP_REST_Request $request The API request object.
		 * @return void Outputs CSS directly and exits.
		 */
		public function get_admin_css( $request ) {
			$template = $request->get_param( 'template' );

			// Build the path to the admin template CSS file.
			$template_file = charitable()->get_path( 'includes' ) . 'css-templates/admin/' . $template . '.php';

			// Validate template exists.
			if ( ! file_exists( $template_file ) ) {
				status_header( 404 );
				header( 'Content-Type: text/css; charset=UTF-8' );
				echo '/* Admin template not found: ' . esc_html( $template ) . ' */';
				exit;
			}

			// Get color parameters with defaults.
			$primary      = $request->get_param( 'p' );
			$secondary    = $request->get_param( 's' );
			$tertiary     = $request->get_param( 't' );
			$button       = $request->get_param( 'b' );
			$mobile_width = $request->get_param( 'mw' );

			// Set up variables that the template file expects.
			// These are accessed via $_GET in the original PHP files,
			// so we'll set them up the same way for compatibility.
			$_GET['p']  = $primary;
			$_GET['s']  = $secondary;
			$_GET['t']  = $tertiary;
			$_GET['b']  = $button;
			$_GET['mw'] = $mobile_width;

			// Generate CSS using output buffering.
			ob_start();

			// Save current working directory.
			$original_cwd = getcwd();

			// Change to the template directory so relative paths in template files work.
			chdir( dirname( $template_file ) );

			// Include the template file - it will output CSS.
			include $template_file;

			// Restore original working directory.
			chdir( $original_cwd );

			$css = ob_get_clean();

			// Generate ETag for cache validation.
			$etag = md5( 'admin-' . $template . $primary . $secondary . $tertiary . $button . $mobile_width );

			// Set headers for CSS response.
			header( 'Content-Type: text/css; charset=UTF-8' );
			header( 'Cache-Control: public, max-age=31536000' ); // 1 year.
			header( 'ETag: "' . $etag . '"' );

			// Output CSS and exit to prevent REST API from processing further.
			echo $css;
			exit;
		}
	}

endif;