<?php
/**
 * Campaign endpoint.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Endpoint
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.55
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Endpoint' ) ) :

	/**
	 * Charitable_Campaign_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Campaign_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'campaign';

		/**
		 * Endpoint class constructor.
		 *
		 * @since 1.6.36
		 */
		public function __construct() {
			$this->comments_disabled = false;
		}

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
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed args.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			$campaign_id = array_key_exists( 'campaign_id', $args ) ? $args['campaign_id'] : get_the_ID();

			return get_permalink( $campaign_id );
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed arguments.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			if ( is_null( $wp_query->get_queried_object() ) ) {
				return false;
			}

			if ( ! $wp_query->is_singular( Charitable::CAMPAIGN_POST_TYPE ) ) {
				return false;
			}

			return ! array_key_exists( 'donate', $wp_query->query_vars );
		}

		/**
		 * Prepare the template.
		 *
		 * @since  1.6.55
		 *
		 * @return void
		 */
		public function setup_template() {
			$donation_id = get_query_var( 'donation_id', false );

			/* If a donation ID is included, make sure it belongs to the current user. */
			if ( $donation_id && ! charitable_user_can_access_donation( $donation_id ) ) {
				wp_safe_redirect( charitable_get_permalink( 'campaign_donation' ) );
				exit();
			}
		}

		/**
		 * Get the content to display for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $content Default content.
		 * @return string
		 */
		public function get_content( $content ) {
			if ( ! charitable_is_main_loop() ) {
				return $content;
			}

			/**
			 * If this is the donation form, and it's showing on a separate page, return the content.
			 */
			if ( charitable_is_page( 'campaign_donation_page' ) ) {
				if ( 'separate_page' == charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
					return $content;
				}

				if ( false !== get_query_var( 'donate', false ) ) {
					return $content;
				}
			}

			/**
			 * If you do not want to use the default campaign template, use this filter and return false.
			 *
			 * @uses charitable_use_campaign_template
			 */
			if ( ! apply_filters( 'charitable_use_campaign_template', true ) ) {
				return $content;
			}

			/**
			 * Remove ourselves as a filter to prevent eternal recursion if apply_filters( 'the_content' )
			 * is called by one of the templates.
			 */
			remove_filter( 'the_content', array( charitable()->endpoints(), 'get_content' ) );

			/**
			 * IF there is no campaign template, that means this is likely a legacy campaign (1.x) and fall back to the content-campaign template.
			 */
			$campaign_template = charitable_get_current_campaign_template();

			ob_start();

			if ( ! $campaign_template ) {

				charitable_template(
					'content-campaign.php',
					array(
						'content'  => $content,
						'campaign' => charitable_get_current_campaign(),
					)
				);

			} else {

				charitable_template(
					'campaign/builder/content.php',
					array(
						'content'       => $content,
						'template'      => charitable_get_current_campaign_template(),
						'campaign'      => charitable_get_current_campaign(),
						'campaign_data' => charitable_get_current_campaign_data(),

					)
				);

			}

			$this->prefix_add_footer_styles( charitable_get_current_campaign_template_id(), charitable_get_current_campaign_id() );

			$content = ob_get_clean();

			add_filter( 'the_content', array( charitable()->endpoints(), 'get_content' ) );

			return $content;
		}

		/**
		 * Return the body class to add for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_body_class() {
			return 'campaign-donation-page';
		}

		/**
		 * Load template specific CSS in the footer, primarily for showing off template in a preview.
		 *
		 * @since   1.8.0
		 * @since   1.8.1 Added $template_id blank check.
		 *
		 * @param  string $template_id Template slug.
		 * @param  object $campaign_id ID of campaign.
		 */
		public function prefix_add_footer_styles( $template_id = 'basic', $campaign_id = false ) {

			if ( '' === $template_id ) {
				return;
			}

			$min           = charitable_get_min_suffix();
			$version       = charitable_is_break_cache() ? charitable()->get_version() . '.' . time() : charitable()->get_version();
			$assets_dir    = charitable()->get_path( 'assets', false );
			$campaign_data = ( false !== $campaign_id ) ? get_post_meta( intval( $campaign_id ), 'campaign_settings_v2', true ) : false;

			if ( false === $campaign_data || '' === $campaign_data ) {
				return;
			}

			// Pass along the theme color overides to the theme. Start by defining the base/default theme values as stored in settings.
			$query_args = array(
				'p' => ! empty( $campaign_data['color_base_primary'] ) ? charitable_sanitize_hex( $campaign_data['color_base_primary'], false ) : false,
				's' => ! empty( $campaign_data['color_base_secondary'] ) ? charitable_sanitize_hex( $campaign_data['color_base_secondary'], false ) : false,
				't' => ! empty( $campaign_data['color_base_tertiary'] ) ? charitable_sanitize_hex( $campaign_data['color_base_tertiary'], false ) : false,
				'b' => ! empty( $campaign_data['color_base_button'] ) ? charitable_sanitize_hex( $campaign_data['color_base_button'], false ) : false,
			);

			// Now check for overrides in the settings.
			$layout_theme_settings = ! empty( $campaign_data['layout']['advanced'] ) ? $campaign_data['layout']['advanced'] : array();

			$query_args['p'] = ! empty( $layout_theme_settings['theme_color_primary'] ) ? charitable_sanitize_hex( $layout_theme_settings['theme_color_primary'], false ) : $query_args['p'];
			$query_args['s'] = ! empty( $layout_theme_settings['theme_color_secondary'] ) ? charitable_sanitize_hex( $layout_theme_settings['theme_color_secondary'], false ) : $query_args['s'];
			$query_args['t'] = ! empty( $layout_theme_settings['theme_color_tertiary'] ) ? charitable_sanitize_hex( $layout_theme_settings['theme_color_tertiary'], false ) : $query_args['t'];
			$query_args['b'] = ! empty( $layout_theme_settings['theme_color_button'] ) ? charitable_sanitize_hex( $layout_theme_settings['theme_color_button'], false ) : $query_args['b'];

			$template_frontend_css_url = add_query_arg(
				apply_filters(
					'charitable_builder_template_frontend_styles_' . $template_id,
					$query_args,
					$template_id,
					$campaign_data
				),
				rest_url( 'charitable/v1/campaign-css/' . $template_id )
			);

			wp_enqueue_style(
				'charitable-campaign-theme-base',
				apply_filters(
					'charitable_builder_template_frontend_base_styles_' . $template_id,
					$assets_dir . 'css/campaign-builder/themes/frontend/base' . $min . '.css',
					$template_id,
					$campaign_data
				),
				array(),
				$version
			);

			wp_enqueue_style(
				'charitable-campaign-theme-' . $template_id,
				$template_frontend_css_url,
				array( 'charitable-campaign-theme-base' ),
				$version
			);
		}
	}

endif;
