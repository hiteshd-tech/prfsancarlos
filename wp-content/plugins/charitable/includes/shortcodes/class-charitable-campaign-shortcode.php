<?php
/**
 * Campaign v2 shortcode model.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Shortcode
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version.  1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Shortcode' ) ) :

	/**
	 * Campaign Model
	 */
	class Charitable_Campaign_Shortcode {

		/**
		 * Campaign data.
		 *
		 * @var array $campaign_data The campaign data.
		 */
		private $campaign_data;

		/**
		 * Display the shortcode output. This is the callback method for the campaigns shortcode.
		 *
		 * @since   1.8.0
		 *
		 * @param  array $atts The user-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts ) {

			$default = array(
				'id' => '',
			);

			$args = shortcode_atts( $default, $atts, 'campaign' );

			// Get the campaign data from the ID that should be passed along in the shortcode.
			$args['campaign']      = self::get_campaign( $args );
			$args['campaign_data'] = self::get_campaign_data( $args );

			// Get the template_id, which should be a part of the campaign data, fall back to the default if you have to.
			$args['template_id'] = ! empty( $args['campaign_data']['template_id'] ) ? esc_attr( $args['campaign_data']['template_id'] ) : charitable_campaign_builder_default_template();

			$args['template'] = self::get_template( $args['template_id'] );

			self::prefix_add_footer_styles( $args['template_id'], $args['campaign_data'] );

			/**
			 * Replace the default template with your own.
			 *
			 * If you replace the template with your own, it needs to be an instance of Charitable_Template.
			 *
			 * @since 1.0.0
			 *
			 * @param false|Charitable_Template The template. If false (the default), we will use our own template.
			 * @param array $args               All the parsed arguments.
			 */
			$template = apply_filters( 'charitable_campaign_shortcode_template', false, $args );

			/* Fall back to default Charitable_Template if no template returned or if template was not object of 'Charitable_Template' class. */
			if ( ! is_object( $template ) || ! is_a( $template, 'Charitable_Template' ) ) {
				$template = new Charitable_Template( 'campaign/builder/content.php', false );
			}

			if ( ! $template->template_file_exists() ) {
				return false;
			}

			/**
			 * Modify the view arguments that are passed to the campaigns shortcode template.
			 *
			 * @since 1.0.0
			 *
			 * @param array $view_args The arguments to pass.
			 * @param array $args      All the parsed arguments.
			 */
			$view_args = apply_filters( 'charitable_campaign_shortcode_view_args', $args, $template );

			$template->set_view_args( $view_args );

			ob_start();

			$template->render();

			$html = ob_get_clean();

			// If the $args['elementor'] is true, then replace the campaign-loop with the elementor campaign-loop.
			if ( isset( $args['elementor'] ) && $args['elementor'] ) {
				$html = str_replace( 'campaign-loop', 'elementor-campaign-loop', $html );
			}

			/**
			 * Customize the output of the shortcode.
			 *
			 * @since  1.0.0
			 *
			 * @param  string $content The content to be displayed.
			 * @param  array  $args    All the parsed arguments.
			 * @return string
			 */
			return apply_filters( 'charitable_campaign_shortcode', $html, $args );
		}

		/**
		 * Return campaigns to display in the campaigns shortcode.
		 *
		 * @since   1.0.0
		 *
		 * @param  array $args The query arguments to be used to retrieve campaigns.
		 * @return WP_Query
		 */
		public static function get_campaign( $args ) {

			$campaign = charitable_get_campaign( $args['id'] );

			return $campaign;
		}

		/**
		 * Get campaign data.
		 *
		 * @since   1.0.0
		 *
		 * @param  array $args The query arguments to be used to retrieve campaigns.
		 * @return WP_Query
		 */
		public static function get_campaign_data( $args ) {

			$settings = get_post_meta( $args['id'], 'campaign_settings_v2', true );

			if ( ! empty( $settings ) ) {
				return $settings;
			}

			return $args;
		}

		/**
		 * Return the template metadata to help display in the campaigns shortcode.
		 *
		 * @since   1.0.0
		 *
		 * @param  string $template_id The template ID.
		 * @return array|false
		 */
		public static function get_template_data( $template_id ) {

			$settings = get_post_meta( $template_id, 'campaign_settings_v2', true );

			if ( ! empty( $settings ) ) {
				return $settings;
			}

			return false;
		}

		/**
		 * Load template specific CSS in the footer.
		 *
		 * @since   1.8.0
		 * @version 1.8.1 Added $template_id blank check.
		 * @version 1.8.2 removed $min from frotend styles.
		 *
		 * @param string $template_id Template slug.
		 * @param array  $campaign_data Saved campaign data.
		 */
		public static function prefix_add_footer_styles( $template_id = 'basic', $campaign_data = false ) {

			if ( '' === $template_id ) {
				return;
			}

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			// Pass along the theme color overides to the theme. Start by defining the base/default theme values as stored in settings.
			$query_args = array(
				'p' => ! empty( $campaign_data['color_base_primary'] ) ? charitable_sanitize_hex( $campaign_data['color_base_primary'], false ) : false,
				's' => ! empty( $campaign_data['color_base_secondary'] ) ? charitable_sanitize_hex( $campaign_data['color_base_secondary'], false ) : false,
				't' => ! empty( $campaign_data['color_base_tertiary'] ) ? charitable_sanitize_hex( $campaign_data['color_base_tertiary'], false ) : false,
				'b' => ! empty( $campaign_data['color_base_button'] ) ? charitable_sanitize_hex( $campaign_data['color_base_button'], false ) : false,
			);

			// Now check for overrides in the settings.
			$layout_theme_settings = isset( $campaign_data['layout']['advanced'] ) ? $campaign_data['layout']['advanced'] : array();

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
				false // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
			);
		}

		/**
		 * Load template classes.
		 *
		 * @since   1.8.0
		 *
		 * @param  string $template_id The template ID.
		 * @return array|false
		 */
		public static function get_template( $template_id = 'basic' ) {

			/**
			 * Allows developers to disable loading of some builder panels.
			 *
			 * @since 1.8.0
			 *
			 * @param array $panels Panels slugs array.
			 */
			$classes = apply_filters(
				'charitable_builder_template_classes_',
				array(
					'templates',
				)
			);

			foreach ( $classes as $class ) {
				$class = sanitize_file_name( $class );
				$file  = require_once charitable()->get_path( 'includes' ) . "admin/campaign-builder/templates/class-{$class}.php";

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}

			$builder_template = new Charitable_Campaign_Builder_Templates();
			$templates_data   = $builder_template->get_templates_data( $template_id );
			$template_data    = isset( $templates_data['templates'][ $template_id ] ) ? $templates_data['templates'][ $template_id ] : false;

			return $template_data;
		}
	}

endif;
