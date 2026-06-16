<?php
/**
 * Class that sets up the WordPress Campaign Builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Builder' ) ) :

	/**
	 * Sets up the WordPress customizer.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Campaign_Builder {

		/**
		 * Abort. Bail on proceeding to process the page.
		 *
		 * @since 1.8.0
		 *
		 * @var bool
		 */
		public $abort = false;

		/**
		 * The human-readable error message.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $abort_message;

		/**
		 * One is the loneliest number that you'll ever do.
		 *
		 * @since 1.8.0
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Current view (panel).
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $view;

		/**
		 * Current campaign ID (post ID).
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $campaign_id;

		/**
		 * Available panels.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $panels;

		/**
		 * CSS Classes.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $classes;

		/**
		 * Current form.
		 *
		 * @since 1.0.0
		 *
		 * @var WP_Post
		 */
		public $campaign;

		/**
		 * Form data and settings.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * Current template information.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public $template;

		/**
		 * Main Instance.
		 *
		 * @since 1.8.0
		 *
		 * @return Charitable_Builder
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Charitable_Campaign_Builder ) ) {

				self::$instance = new Charitable_Campaign_Builder();

				add_action( 'admin_init', array( self::$instance, 'init' ), 10 );
				add_action( 'admin_init', array( self::$instance, 'deregister_common_wp_admin_styles' ), PHP_INT_MAX );

			}

			return self::$instance;
		}

		/**
		 * Create object instance.
		 *
		 * @since 1.8.0
		 */
		private function __construct() {
		}

		/**
		 * Get campaign settings from an ID in the querystring.
		 *
		 * @since 1.8.0
		 * @version 1.8.2 add maybe_unserialize
		 *
		 * @param int|bool $campaign_id The campaign ID.
		 */
		public function get_campaign_settings( $campaign_id = false ) {

			if ( false === $campaign_id && ! empty( $_GET['campaign_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$campaign_id = (int) $_GET['campaign_id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			if ( 0 !== $campaign_id ) {

				$this->campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

				if ( empty( $this->campaign_data ) || false === $this->campaign_data ) {
					$this->campaign_data = $this->get_default_settings();
				}

				if ( is_string( $this->campaign_data ) ) {
					$this->campaign_data = maybe_unserialize( $this->campaign_data );
				}

				$this->campaign_data['id']          = $campaign_id;
				$this->campaign_data['campaign_id'] = $campaign_id; // this should be fixed at some point.

			} else {

				$this->campaign_data = $this->get_default_settings();

			}
		}

		/**
		 * Establish default settings for a new campaign.
		 *
		 * @since 1.8.0
		 */
		public function get_default_settings() {

			if ( ! is_array( $this->campaign_data ) ) {
				$this->campaign_data = array();
			}

			$this->campaign_data['id']          = 0;
			$this->campaign_data['campaign_id'] = 0;

			$campaign_name = isset( $_POST['campaign_name'] ) && '' !== trim( $_POST['campaign_name'] ) ? esc_html( $_POST['campaign_name'] ) : ''; // phpcs:ignore

			$this->campaign_data['tabs']['campaign']['title'] = esc_html__( 'Story.', 'charitable' );
			$this->campaign_data['tabs']['campaign']['desc']  = esc_html__( 'Write Your Campaign\'s Story Here', 'charitable' );

			$this->campaign_data['title'] = $campaign_name;
			$this->campaign_data['desc']  = false; // 'test description';

			$this->campaign_data['layout']['advanced']['tab_style'] = 'small';
			$this->campaign_data['layout']['advanced']['tab_size']  = 'medium';

			return $this->campaign_data;
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			if ( isset( $_POST['action'] ) && str_starts_with( $_POST['action'], 'charitable_' ) ) { // phpcs:ignore
				$this->load_template_classes();
				return;
			}

			// This isn't a charitable campaign builder page.
			if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'charitable-campaign-builder' ) { // phpcs:ignore
				return;
			}

			$this->view        = isset( $_GET['view'] ) ? esc_attr( $_GET['view'] ) : false; // phpcs:ignore
			$this->campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : false; // phpcs:ignore

			// if no view was past, determine if the new campaign cofmr has been used (check settings) and if not redirect to template screen.
			if ( $this->campaign_id && ! $this->view ) {
				$campaign_data = get_post_meta( $this->campaign_id, 'campaign_settings_v2' );
				if ( ! empty( $campaign_data ) && isset( $campaign_data['template'] ) && isset( $campaign_data['template']['id'] ) && 0 !== intval( $campaign_data['template']['id'] ) ) { // todo: better check?
					$this->view = 'design';
				} else {
					$this->view = 'template';
				}
			} elseif ( ! $this->view ) {
				$this->view = 'template';
			}

			// Load builder panels.
			$this->load_panels();

			// Load template related functions.
			$this->load_template_classes();

			// Load misc classes.
			$this->load_misc();

			add_action( 'admin_head', array( $this, 'admin_head' ) );
			add_filter( 'admin_footer_text', '__return_false' );
			add_action( 'admin_menu', array( $this, 'oz_admin_dashboard_footer_right' ) );
			add_action( 'update_footer', '__return_false' );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ), PHP_INT_MAX );

			// Disable displaying errors on this page because it breaks the CSS/Javascript.
			if ( true === apply_filters( 'charitable_campaign_builder_diable_display_errors', true ) ) :
				ini_set( 'display_errors', 0 ); // phpcs:ignore
			endif;
		}

		/**
		 * Hide the WP version in the footer
		 *
		 * @return void
		 */
		public function oz_admin_dashboard_footer_right() {
			remove_filter( 'update_footer', 'core_update_footer' );
		}

		/**
		 * Enqueue assets for the builder.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {

			$this->get_campaign_settings();

			// Remove conflicting scripts.
			wp_deregister_script( 'serialize-object' );
			wp_deregister_script( 'wpclef-ajax-settings' );

			$min           = '';
			$style_version = charitable_get_style_version();
			$version       = charitable_is_break_cache() ? charitable()->get_version() . '.' . time() : charitable()->get_version();

			/*
			* Builder CSS.
			*/
			$builder_styles = array(
				'overlay',
				'basic',
				'preview',
				'ui-general',
				'fields',
				'modals',
				'alerts',
				'onboarding',
				'tour',
			);

			foreach ( $builder_styles as $style ) {
				wp_enqueue_style(
					$style === 'basic' ? 'campaign-builder' : 'charitable-builder-' . $style,
					charitable()->get_path( 'directory', false ) . "assets/css/campaign-builder/builder-{$style}{$min}.css",
					array(),
					$style_version
				);
			}

			/*
			* Template CSS.
			*/

			$template_id = isset( $this->campaign_data['template_id'] ) ? esc_attr( $this->campaign_data['template_id'] ) : charitable_campaign_builder_default_template();

			$url_data = array(
				'p'   => isset( $this->campaign_data['layout']['advanced']['theme_color_primary'] ) ? preg_replace( '/[^A-Za-z0-9 ]/', '', $this->campaign_data['layout']['advanced']['theme_color_primary'] ) : '',
				's'   => isset( $this->campaign_data['layout']['advanced']['theme_color_secondary'] ) ? preg_replace( '/[^A-Za-z0-9 ]/', '', $this->campaign_data['layout']['advanced']['theme_color_secondary'] ) : '',
				't'   => isset( $this->campaign_data['layout']['advanced']['theme_color_tertiary'] ) ? preg_replace( '/[^A-Za-z0-9 ]/', '', $this->campaign_data['layout']['advanced']['theme_color_tertiary'] ) : '',
				'b'   => isset( $this->campaign_data['layout']['advanced']['theme_color_button'] ) ? preg_replace( '/[^A-Za-z0-9 ]/', '', $this->campaign_data['layout']['advanced']['theme_color_button'] ) : '',
				'ver' => charitable()->get_version(),
			);

			$url_data_params = http_build_query( $url_data );

			// Use REST API for admin CSS templates (added in 1.8.9.2)
			wp_enqueue_style(
				'charitable-builder-template-preview-theme',
				add_query_arg( $url_data, rest_url( 'charitable/v1/campaign-css-admin/' . $template_id ) ),
				array(),
				$style_version
			);
			wp_enqueue_style(
				'charitable-builder-template-preview-theme-colors-primary',
				add_query_arg( $url_data, rest_url( 'charitable/v1/campaign-css-admin/' . $template_id . '-colors' ) ),
				array(),
				$style_version
			);
			wp_enqueue_style(
				'charitable-builder-template-preview-theme-colors-secondary',
				add_query_arg( $url_data, rest_url( 'charitable/v1/campaign-css-admin/' . $template_id . '-colors' ) ),
				array(),
				$style_version
			);
			wp_enqueue_style(
				'charitable-builder-template-preview-theme-colors-tertiary',
				add_query_arg( $url_data, rest_url( 'charitable/v1/campaign-css-admin/' . $template_id . '-colors' ) ),
				array(),
				$style_version
			);
			wp_enqueue_style(
				'charitable-builder-template-preview-theme-colors-button',
				add_query_arg( $url_data, rest_url( 'charitable/v1/campaign-css-admin/' . $template_id . '-colors' ) ),
				array(),
				$style_version
			);

			/*
			* Third-party CSS.
			*/
			wp_enqueue_style(
				'charitable-font-awesome',
				charitable()->get_path( 'directory', false ) . 'assets/lib/font-awesome/font-awesome.min.css',
				null,
				'4.7.0'
			);

			wp_enqueue_style(
				'tooltipster',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.tooltipster/jquery.tooltipster.min.css',
				null,
				'4.2.6'
			);

			wp_enqueue_style(
				'jquery-confirm',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
				null,
				'3.3.4'
			);

			wp_enqueue_style(
				'minicolors',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.minicolors/jquery.minicolors.min.css',
				null,
				'2.2.6'
			);

			wp_enqueue_style(
				'choices',
				charitable()->get_path( 'directory', false ) . 'assets/css/campaign-builder/choices.css',
				null,
				'2.2.6'
			);

			wp_enqueue_style(
				'quill-css',
				charitable()->get_path( 'directory', false ) . 'assets/lib/quill/quill.snow.css',
				null,
				'2.2.6'
			);

			wp_enqueue_style(
				'select2-css',
				charitable()->get_path( 'directory', false ) . "assets/css/libraries/select2{$min}.css",
				array(),
				'4.0.12'
			);

			wp_enqueue_style(
				'quill-mention-css',
				charitable()->get_path( 'directory', false ) . 'assets/lib/quill/quill-mention/quill.mention.css',
				null,
				'2.2.6'
			);

			wp_enqueue_style(
				'coloris-css',
				charitable()->get_path( 'directory', false ) . 'assets/css/libraries/coloris.min.css',
				null,
				'2.2.6'
			);

			/**
			 * Enqueue jQuery UI smoothness theme for datepicker styling.
			 *
			 * Note: This file must be bundled locally for WordPress.org compliance.
			 * Download from: https://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css
			 * Save to: assets/css/libraries/jquery-ui-smoothness.css
			 *
			 * @since 1.0.0
			 * @version 1.8.9.1
			 */
			wp_enqueue_style(
				'themeslug-jquery-css',
				charitable()->get_path( 'directory', false ) . 'assets/css/libraries/jquery-ui-smoothness.css',
				null,
				'1.11.2'
			);

			// Remove TinyMCE editor styles from third-party themes and plugins.
			remove_editor_styles();

			do_action( 'charitable_campaign_builder_backend_styles', $min );

			/*
			* JavaScript.
			*/
			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'wp-util' );

			wp_enqueue_script(
				'tooltipster',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.tooltipster/jquery.tooltipster.min.js',
				array( 'jquery' ),
				'4.2.6'
			);

			wp_enqueue_script(
				'jquery-confirm',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
				array( 'jquery' ),
				'3.3.4'
			);

			wp_enqueue_script(
				'charitable-hotkeys',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/hotkeys.min.js',
				array( 'jquery' ),
				'3.3.4'
			);

			wp_enqueue_script(
				'charitable-utils',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/utils{$min}.js",
				array( 'jquery', 'dom-purify' ),
				charitable()->get_version()
			);

			wp_enqueue_script(
				'choicesjs',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/choices.min.js',
				array(),
				'9.0.1'
			);

			wp_enqueue_script(
				'charitable-builder-choicesjs',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/choicesjs{$min}.js",
				array( 'jquery', 'choicesjs' ),
				charitable()->get_version()
			);

			wp_enqueue_script(
				'dom-purify',
				charitable()->get_path( 'directory', false ) . 'assets/lib/purify.min.js',
				array(),
				'2.4.3'
			);

			wp_enqueue_script(
				'minicolors',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.minicolors/jquery.minicolors.min.js',
				array( 'jquery' ),
				'2.2.6'
			);

			wp_enqueue_script(
				'quill',
				charitable()->get_path( 'directory', false ) . 'assets/lib/quill/quill.js',
				array( 'jquery' ),
				'2.2.6'
			);

			wp_register_script(
				'select2',
				charitable()->get_path( 'directory', false ) . "assets/js/libraries/select2{$min}.js",
				array( 'jquery' ),
				'4.0.12',
				true
			);

			wp_enqueue_script(
				'quill-mention',
				charitable()->get_path( 'directory', false ) . 'assets/lib/quill/quill-mention/quill.mention-min.js',
				array( 'jquery', 'quill' ),
				'2.2.6'
			);

			wp_enqueue_script(
				'coloris',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/coloris.min.js',
				array( 'jquery' ),
				'1.0.0'
			);

			// if 'charitable-admin-utils' is already registered, deregister it and re-register it with this version.
			if ( wp_script_is( 'charitable-admin-utils', 'registered' ) ) {
				wp_deregister_script( 'charitable-admin-utils' );
			}
			wp_enqueue_script(
				'charitable-admin-utils',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/admin-utils{$min}.js",
				array( 'jquery', 'dom-purify' ),
				charitable()->get_version()
			);

			wp_enqueue_script(
				'charitable-builder',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/admin-builder{$min}.js",
				array(
					'jquery',
					'charitable-utils',
					'jquery-ui-sortable',
					'jquery-ui-draggable',
					'tooltipster',
					'jquery-confirm',
					'quill',
					'select2',
					'choicesjs',
					'coloris',
					'charitable-builder-choicesjs',
					'wp-util',
				),
				$version
			);

			wp_enqueue_script(
				'charitable-campaign-preview-field-js',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/fields/base{$min}.js",
				array(
					'charitable-builder',
				),
				charitable()->get_version()
			);

			wp_localize_script(
				'charitable-builder',
				'charitable_builder',
				$this->get_localized_strings()
			);

			wp_localize_script(
				'charitable-builder',
				'charitable_addons',
				$this->get_localized_addons()
			);

			wp_localize_script(
				'charitable-builder',
				'charitable_campaign_builder_field_conditionals',
				$this->get_conditionals()
			);

			/* tour */

			wp_enqueue_script(
				'charitable-shepherd',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/shepherd.js',
				array( 'jquery', 'charitable-float-ui-core', 'charitable-float-ui-dom' ),
				charitable()->get_version()
			);

			wp_enqueue_script(
				'charitable-admin-builder-tour',
				charitable()->get_path( 'directory', false ) . 'assets/js/campaign-builder/admin-tour.js',
				array( 'jquery', 'charitable-admin-utils', 'charitable-builder', 'charitable-shepherd', 'charitable-float-ui-core', 'charitable-float-ui-dom' ),
				charitable()->get_version()
			);

			wp_localize_script(
				'charitable-admin-builder-tour',
				'charitable_admin_builder_onboarding',
				[
					'nonce'   => wp_create_nonce( 'charitable_onboarding_ajax_nonce' ),
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'option'  => array(
						'tour' => array(
							'status' => $this->get_tour_option( 'status' ),
						),
					),
				]
			);

			wp_enqueue_style(
				'charitable-shepherd',
				charitable()->get_path( 'directory', false ) . "assets/css/libraries/shepherd{$min}.css",
				array(),
				$style_version
			);

			wp_enqueue_script(
				'charitable-float-ui-core',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/floating-ui-core.min.js',
				array( 'jquery' ),
				charitable()->get_version()
			);

			wp_enqueue_script(
				'charitable-float-ui-dom',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/floating-ui-dom.min.js',
				array( 'charitable-float-ui-core' ),
				charitable()->get_version()
			);

			/* color picker for admin settings */
			wp_enqueue_script( 'wp-color-picker', false, false, false, true ); // todo: check if this is needed.
			wp_enqueue_style( 'wp-color-picker' );

			do_action( 'charitable_builder_backend_scripts', $min );
		}

		/**
		 * Get localized strings.
		 *
		 * @since   1.8.0
		 * @version 1.8.1.12 Added error_no_title.
		 * @version 1.8.5.2 Added currency_decimal_separator and currency_thousands_separator.
		 *
		 * @return array
		 */
		private function get_localized_strings() {

			$campaign_name = isset( $_POST['campaign_name'] ) && '' !== trim( $_POST['campaign_name'] ) ? esc_html( $_POST['campaign_name'] ) : ''; // phpcs:ignore

			$upgrade_utm_medium = false; // todo: update this.

			$builder_template = new Charitable_Campaign_Builder_Templates();

			$currency_helper = charitable_get_currency_helper();

			$strings = array(
				'version'                           => '1.8.5.2',
				'currency_symbol'                   => $currency_helper->get_currency_symbol(),
				'currency_decimal_separator'        => $currency_helper->get_decimal_separator(),
				'currency_thousands_separator'      => $currency_helper->get_thousands_separator(),
				'and'                               => esc_html__( 'And', 'charitable' ),
				'assets_url'                        => admin_url( 'admin-ajax.php' ),
				'ajax_url'                          => admin_url( 'admin-ajax.php' ),
				'bulk_add_button'                   => esc_html__( 'Add New Choices', 'charitable' ),
				'bulk_add_show'                     => esc_html__( 'Bulk Add', 'charitable' ),
				'are_you_sure_to_close'             => esc_html__( 'Are you sure you want to leave? You have unsaved changes', 'charitable' ),
				'bulk_add_hide'                     => esc_html__( 'Hide Bulk Add', 'charitable' ),
				'bulk_add_heading'                  => esc_html__( 'Add Choices (one per line)', 'charitable' ),
				'bulk_add_presets_show'             => esc_html__( 'Show presets', 'charitable' ),
				'bulk_add_presets_hide'             => esc_html__( 'Hide presets', 'charitable' ),
				'date_select_day'                   => 'DD',
				'date_select_month'                 => 'MM',
				'cancel'                            => esc_html__( 'Cancel', 'charitable' ),
				'ok'                                => esc_html__( 'OK', 'charitable' ),
				'save_refresh'                      => esc_html__( 'Save And Reload Campaign', 'charitable' ),
				'close'                             => esc_html__( 'Close', 'charitable' ),
				'conditionals_change'               => esc_html__( 'Due to form changes, conditional logic rules will be removed or updated:', 'charitable' ),
				'conditionals_disable'              => esc_html__( 'Are you sure you want to disable conditional logic? This will remove the rules for this field or setting.', 'charitable' ),
				'no_preview_must_save'              => esc_html__( 'Please Save!', 'charitable' ),
				'no_preview_must_save_msg'          => esc_html__( 'You need to save this campaign before previewing.', 'charitable' ),
				'field_locked'                      => esc_html__( 'Field Locked', 'charitable' ),
				'field_locked_msg'                  => esc_html__( 'This field cannot be deleted or duplicated.', 'charitable' ),
				'field'                             => esc_html__( 'Field', 'charitable' ),
				'field_locked_no_delete_msg'        => esc_html__( 'This field cannot be deleted.', 'charitable' ),
				'field_locked_no_duplicate_msg'     => esc_html__( 'This field cannot be duplicated.', 'charitable' ),
				'fields_available'                  => esc_html__( 'Available Fields', 'charitable' ),
				'fields_unavailable'                => esc_html__( 'No fields available', 'charitable' ),
				'heads_up'                          => esc_html__( 'Heads up!', 'charitable' ),
				'photo_image_placeholder'           => apply_filters(
					'charitable_campaign_builder_photo_image_placeholder_url',
					charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/photo-default-image.png'
				),
				'nonce'                             => wp_create_nonce( 'charitable-builder' ),
				'admin_nonce'                       => wp_create_nonce( 'charitable-admin' ),
				'no_email_fields'                   => esc_html__( 'No email fields', 'charitable' ),
				'notification_delete'               => esc_html__( 'Are you sure you want to delete this notification?', 'charitable' ),
				'notification_prompt'               => esc_html__( 'Enter a notification name', 'charitable' ),
				'notification_ph'                   => esc_html__( 'Eg: User Confirmation', 'charitable' ),
				'notification_error'                => esc_html__( 'You must provide a notification name', 'charitable' ),
				'notification_def_name'             => esc_html__( 'Default Notification', 'charitable' ),
				'confirmation_delete'               => esc_html__( 'Are you sure you want to delete this confirmation?', 'charitable' ),
				'confirmation_prompt'               => esc_html__( 'Enter a confirmation name', 'charitable' ),
				'confirmation_ph'                   => esc_html__( 'Eg: Alternative Confirmation', 'charitable' ),
				'confirmation_error'                => esc_html__( 'You must provide a confirmation name', 'charitable' ),
				'confirmation_def_name'             => esc_html__( 'Default Confirmation', 'charitable' ),
				'save'                              => esc_html__( 'Save', 'charitable' ),
				'saving'                            => esc_html__( 'Saving', 'charitable' ),
				'saved'                             => esc_html__( 'Saved!', 'charitable' ),
				'save_exit'                         => esc_html__( 'Save and Exit', 'charitable' ),
				'save_embed'                        => esc_html__( 'Save and Embed', 'charitable' ),
				'saved_state'                       => '',
				'layout_selector_show'              => esc_html__( 'Show Layouts', 'charitable' ),
				'layout_selector_hide'              => esc_html__( 'Hide Layouts', 'charitable' ),
				'layout_selector_layout'            => esc_html__( 'Select your layout', 'charitable' ),
				'layout_selector_column'            => esc_html__( 'Select your column', 'charitable' ),
				'loading'                           => esc_html__( 'Loading', 'charitable' ),
				'template_select'                   => esc_html__( 'Use Template', 'charitable' ),
				'template_confirm'                  => esc_html__( 'Changing templates on an existing form will DELETE existing form fields. Are you sure you want apply the new template?', 'charitable' ),
				'embed'                             => esc_html__( 'Embed', 'charitable' ),
				'exit'                              => esc_html__( 'Exit', 'charitable' ),
				'exit_url'                          => charitable_current_user_can( 'edit_campaigns' ) ? admin_url( 'edit.php?post_type=campaign' ) : admin_url(),
				'exit_confirm'                      => esc_html__( 'Your campaign contains unsaved changes. Would you like to save your changes first?', 'charitable' ),
				'delete_confirm'                    => esc_html__( 'Are you sure you want to delete this field?', 'charitable' ),
				'delete_tab_confirm'                => esc_html__( 'Are you sure you want to delete this tab?', 'charitable' ),
				'delete_choice_confirm'             => esc_html__( 'Are you sure you want to delete this choice?', 'charitable' ),
				'duplicate_confirm'                 => esc_html__( 'Are you sure you want to duplicate this field?', 'charitable' ),
				'duplicate_copy'                    => esc_html__( '(copy)', 'charitable' ),
				'error_title'                       => esc_html__( 'Please enter a campaign name.', 'charitable' ),
				'error_no_title'                    => esc_html__( 'You need a name for your campaign before you save.', 'charitable' ),
				'error_choice'                      => esc_html__( 'This item must contain at least one choice.', 'charitable' ),
				'off'                               => esc_html__( 'Off', 'charitable' ),
				'on'                                => esc_html__( 'On', 'charitable' ),
				'or'                                => esc_html__( 'or', 'charitable' ),
				'other'                             => esc_html__( 'Other', 'charitable' ),
				'operator_is'                       => esc_html__( 'is', 'charitable' ),
				'operator_is_not'                   => esc_html__( 'is not', 'charitable' ),
				'operator_empty'                    => esc_html__( 'empty', 'charitable' ),
				'operator_not_empty'                => esc_html__( 'not empty', 'charitable' ),
				'operator_contains'                 => esc_html__( 'contains', 'charitable' ),
				'operator_not_contains'             => esc_html__( 'does not contain', 'charitable' ),
				'operator_starts'                   => esc_html__( 'starts with', 'charitable' ),
				'operator_ends'                     => esc_html__( 'ends with', 'charitable' ),
				'operator_greater_than'             => esc_html__( 'greater than', 'charitable' ),
				'operator_less_than'                => esc_html__( 'less than', 'charitable' ),
				'previous'                          => esc_html__( 'Previous', 'charitable' ),
				'error_save_form'                   => esc_html__( 'Something went wrong while saving the form. Please reload the page and try again.', 'charitable' ),
				'error_contact_support'             => esc_html__( 'Please contact the plugin support team if this behavior persists.', 'charitable' ),
				'error_already_started_campaign'    => esc_html__( 'Are you sure you want to reset your template? All your work will be lost.', 'charitable' ),
				'something_went_wrong'              => esc_html__( 'Something went wrong', 'charitable' ),
				'field_cannot_be_reordered'         => esc_html__( 'This field cannot be moved.', 'charitable' ),
				'donation_form_donation_button'     => esc_html__( 'You shouldn\'t have a donation form AND a donation button in the same campaign page. Remove the donation button if you wish to add a donation form.', 'charitable' ),
				'remove_donation_button'            => esc_html__( 'Remove Donation Button', 'charitable' ),
				'donation_button_donation_form'     => esc_html__( 'You shouldn\'t have a donation form AND a donation button in the same campaign page. Remove the donation form if you wish to add a donation button.', 'charitable' ),
				'remove_donation_form'              => esc_html__( 'Remove Donation Form', 'charitable' ),
				'only_one_donation_form'            => esc_html__( 'You shouldn\'t have more than one donation form on your campaign page. Move or delete the current donation form.', 'charitable' ),
				'campaign_delete_confirm'           => esc_html__( 'Are you sure you want to delete your campaign? You cannot undo this.', 'charitable' ),
				'feedback_form_fields_required'     => esc_html__( 'Some fields of this form are required. Please update the form and try submiting again. Thanks!', 'charitable' ),
				'empty_label'                       => esc_html__( 'Empty Label', 'charitable' ),
				'no_pages_found'                    => esc_html__( 'No results found', 'charitable' ),
				'empty_tab'                         => sprintf(
				/* translators: %s: configure tab settings link */
					esc_html__( 'This tab is empty. Drag a block from the left into this area or%1$s%2$s%3$s', 'charitable' ),
					'<br/><strong><a href="#" class="charitable-configure-tab-settings">',
					esc_html__( 'configure tab settings', 'charitable' ),
					'</a></strong>'
				),
				'no_tabs'                           => sprintf(
				/* translators: %s: configure tab settings link */
					esc_html__( 'There are no tabs yet for this template. You can %1$s%2$s%3$s to add a tab.', 'charitable' ),
					'<br/><strong><a href="#" class="charitable-configure-tab-settings">',
					esc_html__( 'configure tab settings', 'charitable' ),
					'</a></strong>'
				),
				'new_tab'                           => esc_html__( 'New Tab', 'charitable' ),
				'default_campaign_title'            => esc_html__( 'My New Campaign', 'charitable' ),
				'field_disabled_due_to_modal'       => esc_html__( 'We\'re sorry, the %name% is not available because you have the display settings for donation form set to \'modal\' in Charitable general settings.', 'charitable' ),
				'field_disabled_due_to_same_page'   => esc_html__( 'We\'re sorry, the %name% is not available because you have the display settings for donation form set to \'same page\' in Charitable general settings.', 'charitable' ),
				'settings_page_url'                 => admin_url( 'admin.php?page=charitable-settings' ),
				'go_to_settings'                    => esc_html__( 'Go to Settings', 'charitable' ),
				'update_campaign'                   => esc_html__( 'Update Campaign', 'charitable' ),
				'create_campaign'                   => esc_html__( 'Create Campaign', 'charitable' ),
				'upgrade'                           => array(
					'pro'       => array(
						/* translators: %s - plan name. */
						'title'        => esc_html( sprintf( __( 'is a %1$s feature.', 'charitable' ), '%plan%' ) ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'We\'re sorry, the %1$s is not available on your plan. Please upgrade to the %2$s plan to unlock all these awesome features.', 'charitable' ), '%name%', '%plan%' ) ) . '</p>',
						'doc'          => sprintf(
							'<div class="lite-promo"><span>Bonus:</span> Charitable Lite users save <strong class="percent">$300 or more</strong> off the regular price, automatically applied at checkout!</div><div class="already-purchased-div"><a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a></div>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-vs-pro/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Already purchased?', 'charitable' )
						),
						'type'         => 'pro-upgrade',
						// translators: %1$s - plan name, %2$s - addon name.
						'button'       => esc_html( sprintf( __( 'Upgrade to %1$s And Unlock %2$s', 'charitable' ), '%plan%', '%name%' ) ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
					'plus'      => array(
						/* translators: %s - plan name. */
						'title'        => esc_html( sprintf( __( 'is a %1$s feature.', 'charitable' ), '%plan%' ) ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'We\'re sorry, the %1$s is not available on your plan. Please upgrade to the %2$s plan to unlock all these awesome features.', 'charitable' ), '%name%', '%plan%' ) ) . '</p>',
						'doc'          => sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-vs-pro/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Already purchased?', 'charitable' )
						),
						'type'         => 'pro-upgrade',
						// translators: %1$s - plan name, %2$s - addon name.
						'button'       => esc_html( sprintf( __( 'Upgrade to %1$s And Unlock %2$s', 'charitable' ), '%plan%', '%name%' ) ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
					'basic'     => array(
						/* translators: %s - plan name. */
						'title'        => esc_html( sprintf( __( 'is a %1$s feature.', 'charitable' ), '%plan%' ) ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'We\'re sorry, the %1$s is not available on your plan. Please upgrade to the %2$s plan to unlock all these awesome features.', 'charitable' ), '%name%', '%plan%' ) ) . '</p>',
						'doc'          => sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-vs-pro/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Already purchased?', 'charitable' )
						),
						'type'         => 'pro-upgrade',
						// translators: %1$s - plan name, %2$s - addon name.
						'button'       => esc_html( sprintf( __( 'Upgrade to %1$s And Unlock %2$s', 'charitable' ), '%plan%', '%name%' ) ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
					'pro-panel' => array(
						'title'        => esc_html__( 'is a PRO feature', 'charitable' ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'We\'re sorry, %s features are not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.', 'charitable' ), '%name%' ) ) . '</p>',
						'doc'          => sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-vs-pro/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Already purchased?', 'charitable' )
						),
						'type'         => 'pro-upgrade',
						// translators: %1$s - plan name, %2$s - addon name.
						'button'       => esc_html( sprintf( __( 'Upgrade to %1$s And Unlock %2$s', 'charitable' ), '%plan%', '%name%' ) ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
				),
				'install'                           => array(
					'pro'   => array(
						'title'        => esc_html__( 'is not installed or activated', 'charitable' ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'Good news! You have the %1$s plan. Please install and activate the %2$s to add this feature to your campaign page.', 'charitable' ), '%name%', '%addon%' ) ) . '</p>',
						'doc'          => sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/account/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Access Your Account', 'charitable' )
						),
						'type'         => 'charitable-install',
						'button'       => esc_html__( 'Install and Activate', 'charitable' ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
					'plus'  => array(
						'title'        => esc_html__( 'is not installed or activated', 'charitable' ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'Good news! You have the %1$s plan. Please install and activate the %2$s to add this feature to your campaign page.', 'charitable' ), '%name%', '%addon%' ) ) . '</p>',
						'doc'          => sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/account/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Access Your Account', 'charitable' )
						),
						'type'         => 'charitable-install',
						'button'       => esc_html__( 'Install and Activate', 'charitable' ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
					'basic' => array(
						'title'        => esc_html__( 'is not installed or activated', 'charitable' ),
						/* translators: %s - addon name. */
						'message'      => '<p>' . esc_html( sprintf( __( 'Good news! You have the %1$s plan. Please install and activate the %2$s to add this feature to your campaign page.', 'charitable' ), '%name%', '%addon%' ) ) . '</p>',
						'doc'          => sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
							esc_url( charitable_utm_link( 'https://wpcharitable.com/account/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
							esc_html__( 'Access Your Account', 'charitable' )
						),
						'type'         => 'charitable-install',
						'button'       => esc_html__( 'Install and Activate', 'charitable' ),
						'url'          => charitable_admin_upgrade_link( 'Action+Link+Campaign+Builder+Modal' ),
						'url_template' => false,
						'modal'        => charitable_get_upgrade_modal_text( 'pro' ),
					),
				),
				'activate'                          => array(
					'title'   => esc_html__( 'is not activated', 'charitable' ),
					/* translators: %s - addon name. */
					'message' => '<p>' . esc_html( sprintf( __( 'Good news! You have the %1$s installed, you just need to activate it.', 'charitable' ), '%addon%' ) ) . '</p>',
					'doc'     => sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="already-purchased">%2$s</a>',
						esc_url( charitable_utm_link( 'https://wpcharitable.com/account/', 'Action+Link+Campaign+Builder+Modal', 'AP - %name%' ) ),
						esc_html__( 'Access Your Account', 'charitable' )
					),
					'type'    => 'charitable-activate',
					'button'  => esc_html__( 'Activate', 'charitable' ),
				),
				'activated_title'                   => esc_html__( 'is activated', 'charitable' ),
				'installing'                        => esc_html__( 'installing...', 'charitable' ),
				'activating'                        => esc_html__( 'activating...', 'charitable' ),
				'standby'                           => esc_html__( 'standby...', 'charitable' ),
				'installed_activated_reboot'        => esc_html__( 'Save your campaign and refresh the page to use the addon with your campaign.', 'charitable' ),
				'installed_activated_title'         => esc_html__( 'has been installed and activated', 'charitable' ),
				'installed_activated_failed_title'  => esc_html__( 'failed to install and activate', 'charitable' ),
				'installed_activated_failed_reboot' => esc_html__( 'Something went wrong. Save your campaign and attempt to install and activate the plugin on the WordPress plugin page.', 'charitable' ),
				'activated_reboot'                  => esc_html__( 'Save your campaign and refresh the page to use the addon with your campaign.', 'charitable' ),
				'activated_failed_title'            => esc_html__( 'failed to activate', 'charitable' ),
				'activated_failed_reboot'           => esc_html__( 'Something went wrong. Save your campaign and attempt to activate the plugin on the WordPress plugin page.', 'charitable' ),
				'activated_refresh_title'           => esc_html__( 'is activated', 'charitable' ),
				'activated_refresh'                 => esc_html__( 'Save your campaign and refresh the page to use the addon with your campaign.', 'charitable' ),
				'upgrade_bonus_modal'               => wpautop(
					wp_kses(
						__( '<strong>Bonus:</strong> Charitable Lite users get <span>50% off</span> regular price, automatically applied at checkout.', 'charitable' ),
						array(
							'strong' => array(),
							'span'   => array(),
						)
					)
				),
				'charitable_addons_page'            => esc_url( admin_url( 'admin.php?page=charitable-addons' ) ),
				'charitable_license_label'          => esc_html( Charitable_Licenses_Settings::get_instance()->get_license_label_from_plan_id() ),
				'charitable_form_name'              => $campaign_name,
				'charitable_assets_dir'             => apply_filters(
					'charitable_campaign_builder_charitable_assets_dir',
					charitable()->get_path( 'directory', false ) . 'assets/'
				),
				'charitable_rest_url'               => rest_url( 'charitable/v1/' ),
			);

			if ( $this->is_tour_active() ) {
				$strings['onboarding_tour'] = array(
					'next'                    => esc_html__( 'Next', 'charitable' ),
					'start_tour'              => esc_html__( 'Start Tour', 'charitable' ),
					'watch_video'             => esc_html__( 'Watch Video', 'charitable' ),
					'choose_a_template'       => esc_html__( 'Choose a Template', 'charitable' ),
					'lets_get_started'        => esc_html__( 'Get Started', 'charitable' ),
					'step_0_text'             => '<h2>' . esc_html__( 'Welcome to the Campaign Builder!', 'charitable' ) . '</h2><p>' . esc_html__( 'This is where you build, manage, and add features to your campaigns. The following steps will walk you through essential areas.', 'charitable' ) . '</p><div id="charitable-tour-video"></div>',
					'step_1_title'            => esc_html__( 'Name Your Campaign', 'charitable' ),
					'step_1_text'             => sprintf(
						'<p>%1$s <strong>%2$s</strong> %3$s.</p>',
						esc_html__( 'Give your campaign a name so you can easily identify it. Once you have entered a name, click', 'charitable' ),
						esc_html__( 'Next', 'charitable' ),
						esc_html__( 'to continue', 'charitable' )
					),
					'step_2_title'            => esc_html__( 'Select A Template', 'charitable' ),
					'step_2_text'             => sprintf(
						'<p>%1$s <strong>%2$s</strong> %3$s.</p><p class="charitable-tour-tip"><strong>%4$s</strong> %5$s <strong>%6$s</strong> %7$s.</p>',
						esc_html__( 'Build your campaign from scratch or use one of our pre-made templates. Hover over a thumbnail and select', 'charitable' ),
						esc_html__( 'Create Campaign', 'charitable' ),
						esc_html__( 'to get started', 'charitable' ),
						esc_html__( 'For example:', 'charitable' ),
						esc_html__( 'The', 'charitable' ),
						esc_html__( 'Animal Sanctuary', 'charitable' ),
						esc_html__( 'template is perfect for animal rescue organizations', 'charitable' )
					),
					'step_3_title'            => esc_html__( 'Campaign Fields', 'charitable' ),
					'step_3_text'             => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'Clicking on this tab shows you the available fields for your campaign. You can drag additional fields to add to your page.', 'charitable' )
					),
					'step_4_title'            => esc_html__( 'Recommended Fields', 'charitable' ),
					'step_4_text'             => sprintf(
						'<p>%1$s <span class="charitable-tour-check"></span> %2$s.</p>',
						esc_html__( 'These fields are usually found on all campaign pages. A', 'charitable' ),
						esc_html__( 'means that the field already is on your campaign page', 'charitable' )
					),
					'step_5_title'            => esc_html__( 'Standard Fields', 'charitable' ),
					'step_5_text'             => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'These are common fields you can use when you need them.', 'charitable' )
					),
					'step_6_title'            => esc_html__( 'Pro Fields', 'charitable' ),
					'step_6_text'             => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'Fields that offer advanced features offered by addons or third-party integrations.', 'charitable' )
					),
					'step_7_title'            => esc_html__( 'Save', 'charitable' ),
					'step_7_text'             => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'Save your campaign progress at any time.', 'charitable' )
					),
					'step_8_title'            => esc_html__( 'Publish Your Campaign', 'charitable' ),
					'step_8_text'             => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'When you\'re ready, launch your campaign and start raising funds.', 'charitable' )
					),
					'step_9_title'            => esc_html__( 'Preview', 'charitable' ),
					'step_9_text'             => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'See how your campaign will look while in draft or before making updates.', 'charitable' )
					),
					'step_10_title'           => esc_html__( 'View', 'charitable' ),
					'step_10_text'            => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'You can also check out your campaign once it\'s live.', 'charitable' )
					),
					'step_11_title'           => esc_html__( 'Embed', 'charitable' ),
					'step_11_text'            => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'Add a campaign to a new or existing page with our embed wizard, or use the shortcode provided.', 'charitable' )
					),
					'step_12_title'           => esc_html__( 'Settings', 'charitable' ),
					'step_12_text'            => sprintf(
						'<p>%1$s</p><p>%2$s</p>',
						esc_html__( 'Customize campaign details, preferences, and enable new functionality.', 'charitable' ),
						esc_html__( 'Start with general settings where you can add donation goals, end dates, suggested amounts, and more.', 'charitable' )
					),
					'step_13_title'           => esc_html__( 'We hope you enjoyed the tour!', 'charitable' ),
					'step_13_text'            => sprintf(
						'<p>%1$s</p>',
						// translators: %s - getting started guide link.
						sprintf( __( 'Remember that you can view our <a href="%1$s" target="_blank">getting started guide</a>, read our <a href="%2$s" target="_blank">documentation</a>, or <a href="%3$s" target="_blank">reach out to us</a> for support if you have any questions.', 'charitable' ), 'https://wpcharitable.com/getting-started', 'https://wpcharitable.com/documentation', 'https://wpcharitable.com/support' )
					),
					/* onboarding alts */
					'step_1_title_onboarding' => esc_html__( 'Your Campaign Name', 'charitable' ),
					'step_1_text_onboarding'  => sprintf(
						'<p>%1$s</p>',
						esc_html__( 'This is where you can change or update your campaign name', 'charitable' )
					),
				);
			}

			$strings = apply_filters( 'charitable_builder_strings', $strings, $this->campaign );

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['campaign_id'] ) ) {
				$campaign_id            = (int) $_GET['campaign_id'];
				$strings['preview_url'] = false;
				$strings['entries_url'] = esc_url( admin_url( 'admin.php?page=charitable-entries&view=list&campaign_id=' . $campaign_id ) );
			}
			// phpcs:enable

			return $strings;
		}

		/**
		 * Get localized addons.
		 *
		 * @since 1.8.0
		 *
		 * @return array
		 */
		private function get_localized_addons() {

			$strings = apply_filters(
				'charitable_campaign_builder_localized_addon_strings',
				array()
			);

			return $strings;
		}

		/**
		 * Get conditional rules for showing/hiding settings fields in the campaign builder.
		 *
		 * @since 1.8.0
		 *
		 * @return array
		 */
		private function get_conditionals() {

			$strings = apply_filters(
				'charitable_campaign_builder_localized_conditionals',
				array()
			);

			return $strings;
		}

		/**
		 * Clear common wp-admin styles, keep only allowed.
		 *
		 * @since 1.8.0
		 */
		public function deregister_common_wp_admin_styles() {

			if ( ! campaign_is_campaign_builder_admin_page() ) {
				return;
			}

			/**
			 * Filters the allowed common wp-admin styles.
			 *
			 * @since 1.8.0
			 *
			 * @param array $allowed_styles Styles allowed in the Campaign builder.
			 */
			$allowed_styles = (array) apply_filters(
				'charitable_admin_builder_allowed_common_wp_admin_styles',
				array(
					'wp-editor',
					'wp-editor-font',
					'editor-buttons',
					'dashicons',
					'media-views',
					'imgareaselect',
					'wp-mediaelement',
					'mediaelement',
					'buttons',
					'admin-bar',
				)
			);

			wp_styles()->registered = array_intersect_key( wp_styles()->registered, array_flip( $allowed_styles ) );
		}

		/**
		 * Define TinyMCE buttons to use with our fancy editor instances.
		 *
		 * @since 1.8.0
		 *
		 * @param array $buttons List of default buttons.
		 *
		 * @return array
		 */
		public function tinymce_buttons( $buttons ) { // phpcs:ignore

			return array( 'colorpicker', 'lists', 'wordpress', 'wpeditimage', 'wplink' );
		}

		/**
		 * Load panels.
		 *
		 * @since 1.0.0
		 */
		public function load_panels() {

			// Base class and functions.
			require_once charitable()->get_path( 'includes' ) . 'admin/campaign-builder/panels/class-base.php';

			/**
			 * Campaign Builder panels slugs array filter.
			 *
			 * Allows developers to disable loading of some builder panels.
			 *
			 * @since 1.8.0
			 *
			 * @param array $panels Panels slugs array.
			 */
			$this->panels = apply_filters(
				'charitable_builder_panels_',
				array(
					'template',
					'design',
					'settings',
					'marketing',
					'payment',
					'help',
				)
			);

			foreach ( $this->panels as $panel ) {
				$panel = sanitize_file_name( $panel );
				$file  = require_once charitable()->get_path( 'includes' ) . "admin/campaign-builder/panels/class-{$panel}.php";

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}


		/**
		 * Load template classes.
		 *
		 * @since 1.8.0
		 */
		public function load_template_classes() {

			/**
			 * Campaign builder panels slugs array filter.
			 *
			 * Allows developers to disable loading of some builder panels.
			 *
			 * @since 1.8.0
			 *
			 * @param array $panels Panels slugs array.
			 */
			$this->classes = apply_filters(
				'charitable_builder_template_classes_',
				array(
					'templates',
				)
			);

			foreach ( $this->classes as $class ) {
				$class = sanitize_file_name( $class );
				$file  = require_once charitable()->get_path( 'includes' ) . "admin/campaign-builder/templates/class-{$class}.php";

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}

		/**
		 * Load misc classes.
		 *
		 * @since 1.8.0
		 */
		public function load_misc() {

			/**
			 * Campaign builder panels slugs array filter.
			 *
			 * Allows developers to disable loading of some builder panels.
			 *
			 * @since 1.8.0
			 *
			 * @param array $panels Panels slugs array.
			 */
			$this->classes = apply_filters(
				'charitable_builder_class_',
				array(
					'campaign-embed-wizard',
					'campaign-congrats-wizard',
				)
			);

			foreach ( $this->classes as $class ) {
				$class = sanitize_file_name( $class );
				$file  = require_once charitable()->get_path( 'includes' ) . "admin/campaign-builder/class-{$class}.php";

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}

		/**
		 * Load the appropriate files to build the page.
		 *
		 * @since 1.8.0
		 * @version 1.8.8.6
		 */
		public function output() {

			if ( $this->abort ) {
				return;
			}

			/**
			 * Allow developers to disable Campaign builder output.
			 *
			 * @since 1.8.0
			 *
			 * @param bool $is_enabled Is builder output enabled? Defaults to `true`.
			 */
			if ( ! (bool) apply_filters( 'charitable_builder_output', true ) ) {
				return;
			}

			$this->get_campaign_settings();

			if ( ! $this->campaign_data && intval( $_GET['campaign_id'] ) > 0 ) { // phpcs:ignore
				$this->campaign_data = $this->get_campaign_settings( intval( $_GET['campaign_id'] ) ); // phpcs:ignore
			}

			$builder_template      = new Charitable_Campaign_Builder_Templates();
			$load_checklist_assets = Charitable_Checklist::get_instance()->maybe_load_checklist_assets();
			$is_checklist_skipped  = Charitable_Checklist::get_instance()->is_checklist_skipped();
			$display_tour_divs     = $this->display_tour_divs();

			( $load_checklist_assets && ! $is_checklist_skipped ) ? true : false;

			$campaign_id       = $this->campaign_data ? absint( $this->campaign_data['id'] ) : 0;
			$template_id       = $this->campaign_data && isset( $this->campaign_data['template_id'] ) ? esc_attr( $this->campaign_data['template_id'] ) : '';
			$template_label    = ! empty( $template_id ) ? $builder_template->get_template_data_meta_field( 'label', $template_id ) : '';
			$field_id          = ! empty( $this->campaign_data['field_id'] ) ? intval( $this->campaign_data['field_id'] ) : 0;
			$post_status       = $campaign_id ? get_post_status( $campaign_id ) : '';
			$post_status_label = ucwords( $post_status );

			$campaign_url = ( 0 !== $campaign_id ) ? get_permalink( $campaign_id ) : '';

			$color_base_primary   = $this->campaign_data && isset( $this->campaign_data['color_base_primary'] ) ? esc_attr( $this->campaign_data['color_base_primary'] ) : '';
			$color_base_secondary = $this->campaign_data && isset( $this->campaign_data['color_base_secondary'] ) ? esc_attr( $this->campaign_data['color_base_secondary'] ) : '';
			$color_base_tertiary  = $this->campaign_data && isset( $this->campaign_data['color_base_tertiary'] ) ? esc_attr( $this->campaign_data['color_base_tertiary'] ) : '';
			$color_base_button    = $this->campaign_data && isset( $this->campaign_data['color_base_button'] ) ? esc_attr( $this->campaign_data['color_base_button'] ) : '';

			$revision        = 0; // for future use.
			$allowed_caps    = array( 'edit_posts', 'edit_other_posts', 'edit_private_posts', 'edit_published_posts', 'edit_pages', 'edit_other_pages', 'edit_published_pages', 'edit_private_pages' );
			$can_embed       = array_filter( $allowed_caps, 'current_user_can' );
			$preview_classes = array( 'charitable-btn', 'charitable-btn-toolbar', 'charitable-btn-light-grey' );
			$builder_classes = array( 'charitable-admin-page' );

			// for future use.
			if ( ! $can_embed ) {
				$preview_classes[] = 'charitable-alone';
			}

			$revision_id = null; // for future use.

			/**
			 * Allow to modify builder container classes.
			 *
			 * @since 1.8.0
			 *
			 * @param array $classes   List of classes.
			 * @param array $campaign_data Form data and settings.
			 */
			$builder_classes = (array) apply_filters( 'charitable_builder_output_classes', $builder_classes, $this->campaign_data );

			/**
			 * Allow developers to add content before the top toolbar in the Campaign builder.
			 *
			 * @since 1.8.0
			 *
			 * @param string $content Content before toolbar. Defaults to empty string.
			 */
			$before_toolbar = apply_filters( 'charitable_builder_output_before_toolbar', '' );
		?>

		<?php
		$builder_classes_output = charitable_sanitize_classes( $builder_classes, true );
		?>
		<div id="charitable-builder" class="<?php echo esc_attr( $builder_classes_output ); ?>">

				<?php

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( empty( $_GET['force_desktop_view'] ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->mobile_notice();
				}
				?>

				<div id="charitable-builder-overlay">
					<div class="charitable-builder-overlay-content">
						<i class="spinner"></i>
						<i class="avatar"></i>
					</div>
				</div>

				<?php

				// determine what value to use for "form_saved". the value should represent the last time a change was made and NOT saved, so new campaigns are blank (but not saved)
				// any changes would add a value to this field although it doesn't have to be exact to every click.

				$form_saved = false;

				?>

				<form
						name="charitable-builder" id="charitable-builder-form" method="post"
						data-id="<?php echo esc_attr( $campaign_id ); ?>"
						data-template-id="<?php echo esc_attr( $template_id ); ?>"
						data-template-label="<?php echo esc_html( $template_label ); ?>"
						data-revision="<?php echo esc_attr( $revision_id ); ?>"
				>

					<input type="hidden" name="id" value="<?php echo intval( $campaign_id ); ?>" />
					<input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>" />
					<input type="hidden" name="template_label" value="<?php echo esc_html( $template_label ); ?>" />
					<input type="hidden" name="color_base_primary" value="<?php echo esc_html( $color_base_primary ); ?>" />
					<input type="hidden" name="color_base_secondary" value="<?php echo esc_html( $color_base_secondary ); ?>" />
					<input type="hidden" name="color_base_tertiary" value="<?php echo esc_html( $color_base_tertiary ); ?>" />
					<input type="hidden" name="color_base_button" value="<?php echo esc_html( $color_base_button ); ?>" />
					<input type="hidden" name="form_saved" id="charitable-form-saved" value="<?php echo esc_html( $form_saved ); ?>" />
					<input type="hidden" name="post_status" id="charitable-form-post-status" value="<?php echo esc_html( $post_status ); ?>" />
					<input type="hidden" name="post_status_label" id="charitable-form-post-status-label" value="<?php echo esc_html( $post_status_label ); ?>" />
					<input type="hidden" value="<?php echo absint( $field_id ); ?>" name="field_id" id="charitable-field-id" />

					<?php echo $before_toolbar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<!-- Toolbar -->
					<div class="charitable-toolbar">

						<div class="charitable-left">
							<div class="mascot">

							</div>
						</div>

						<div class="charitable-center">

							<span class="charitable-edit-campaign-title-label"><?php echo esc_html__( 'Now editing', 'charitable' ); ?></span>

							<span class="charitable-edit-campaign-title-area">
								<input id="charitable_settings_title" name="title" placeholder="<?php echo esc_html__( 'Enter Your Campaign Name Here...', 'charitable' ); ?>" class="charitable-center-form-name charitable-form-name" value="<?php echo esc_html( isset( $this->campaign_data['title'] ) ? $this->campaign_data['title'] : '' ); ?>" />
								<!--<a href="#" class="charitable-title-edit" title="<?php echo esc_html__( 'Edit Campaign Title', 'charitable' ); ?>"><img class="charitable-edit-campaign-title-icon" width="18" height="18" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/edit.png' ); ?>" /></a>-->

							</span>

						</div>

						<div class="charitable-right">

							<button id="charitable-embed" class="charitable-btn charitable-btn-toolbar
							<?php
							if ( 'publish' !== $post_status ) {
								echo 'charitable-disabled'; }
							?>
							" title="<?php echo esc_html__( 'Embed Campaign Ctrl+B', 'charitable' ); ?>">
								<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/topbar_embed.svg' ); ?>" />
								<span class="text"><?php echo esc_html__( 'Embed', 'charitable' ); ?></span>
							</button>

							<a href="<?php echo esc_url( $campaign_url ); ?>" id="charitable-view-btn" class="charitable-btn charitable-btn-toolbar
												<?php
												if ( 'publish' !== $post_status ) {
													echo 'charitable-disabled'; }
												?>
							" title="<?php esc_attr_e( 'View Live', 'charitable' ); ?>" target="_blank" rel="noopener noreferrer">
								<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/view_live.svg' ); ?>" />
								<span class="text"><?php echo esc_html__( 'View Live', 'charitable' ); ?></span>
							</a>

							<a href="<?php echo esc_url( charitable_get_campaign_preview_url( $campaign_id, true ) ); ?>" class="charitable-btn charitable-btn-toolbar charitable-disabled" id="charitable-preview-btn" target="_blank" rel="noopener noreferrer">
								<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/topbar_visibility.svg' ); ?>" />
								<span class="text"><?php echo esc_html__( 'Preview', 'charitable' ); ?></span>
							</a>

							<div id="charitable-status" class="charitable-status-container">

							<?php if ( $display_tour_divs ) : ?>
								<div id="charitable-tour-block-4" class="charitable-tour-block"></div>
							<?php endif; ?>

								<button id="charitable-status-button" class="charitable-btn charitable-btn-toolbar charitable-btn-light-grey" data-status="draft">
									<span class="text"><?php echo esc_html__( 'Publish', 'charitable' ); ?></span>
									<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/expand_more.svg' ); ?>" />
								</button>
								<ul id="charitable-status-dropdown" class="charitable-hidden">
									<li><a data-status="publish" data-status-label="Publish" class="publish" href="#"><?php echo esc_html__( 'Publish', 'charitable' ); ?></a></li>
									<li><a data-status="pending" data-status-label="Pending" class="pending" href="#"><?php echo esc_html__( 'Pending Review', 'charitable' ); ?></a></li>
									<li><a data-status="draft" data-status-label="Draft" class="draft" href="#"><?php echo esc_html__( 'Draft', 'charitable' ); ?></a></li>
								</ul>
							</div>


							<button id="charitable-save" class="charitable-btn charitable-btn-toolbar charitable-btn-green" title="Save Campaign Ctrl+S">
									<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/topbar_check.svg' ); ?>" />
									<i class="charitable-loading-spinner charitable-loading-white charitable-loading-inline charitable-hidden"></i><span class="text"><?php echo esc_html__( 'Save', 'charitable' ); ?></span>
							</button>

							<button id="charitable-exit" title="<?php esc_attr_e( 'Exit Ctrl+Q', 'charitable' ); ?>">
								<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/x.png' ); ?>" />
							</button>

						</div>

					</div>

					<!-- Panel toggle buttons. -->
					<div class="charitable-panels-toggle" id="charitable-panels-toggle">

						<?php
						/**
						 * Outputs the buttons to toggle between Campaign builder panels.
						 *
						 * @since 1.8.0
						 *
						 * @param WP_Post $campaign The campaign object.
						 * @param string  $view Current view (panel) name.
						 */
						do_action( 'charitable_builder_panel_buttons', $this->campaign, $this->view );
						?>

					</div>
					<div class="charitable-panels">

						<?php
						/**
						 * Outputs the contents of Campaign builder panels.
						 *
						 * @since 1.8.0
						 *
						 * @param WP_Post $campaign The campaign object.
						 * @param string  $view Current view (panel) name.
						 */
						do_action( 'charitable_builder_panels', $this->campaign, $this->view );
						?>

					</div>

					<!-- Models -->

					<?php

						$create_update_css = isset( $_GET['campaign_id'] ) && 0 !== intval( $_GET['campaign_id'] ) ? 'update-campaign' : 'create-campaign'; // phpcs:ignore

					?>

					<div id="charitable-builder-modal-template-preview" class="charitable-builder-modal charitable-builder-modal-template-preview">

						<div id="charitable-templates-preview-form" class="charitable-form charitable-templates-preview-form">
							<div class="charitable-templates-close-icon">×</div>

							<h4 class="charitable-templates-preview-headline"></h4>
							<div class="charitable-templates-preview-description">

							</div>

							<div class="charitable-form-row haritable-templates-form-row charitable-feedback-form-preview-image">
								<img title="" src="" alt="" />
							</div>

							<div class="charitable-form-row charitable-feedback-form-row charitable-feedback-form-row-button">
								<a class="button-link button-preview-<?php echo esc_attr( $create_update_css ); ?>" data-template-id=""><?php echo esc_html__( 'Use This Template', 'charitable' ); ?></a>
							</div>

						</div>

					</div>


				</form>

			</div>

			<?php
		}


		/**
		 * Add Settings menu item under the Campaign menu tab.
		 *
		 * @since 1.8.0
		 * @since 1.8.1.5 Update capability.
		 *
		 * @return void
		 */
		public function add_page() {

			add_submenu_page(
				'null',
				esc_html__( 'Campaign Builder', 'charitable' ), // Page Title.
				esc_html__( 'Campaign Builder', 'charitable' ), // Title that would otherwise appear in the menu.
				'publish_pages', // Capability level.
				'charitable-campaign-builder',   // Still accessible via admin.php?page=menu_handle.
				array( $this, 'output' ) // To render the page.
			);
		}

		/**
		 * Admin head area inside the Campaign builder.
		 *
		 * @since 1.8.0
		 */
		public function admin_head() {

			// Force hide admin side menu.
			echo '<style>#adminmenumain { display: none !important }</style>';

			do_action( 'charitable_builder_admin_head', $this->view );
		}


		/**
		 * Entry debug metabox. Hidden by default obviously.
		 *
		 * @since 1.8.0
		 *
		 * @param object $entry     Submitted entry values.
		 * @param array  $form_data Form data and settings.
		 */
		public function details_debug( $entry = false, $form_data = false ) {

			if ( ! charitable_builder_debug() ) {
				return;
			}

			if ( false === $entry ) {
				return;
			}

			/** This filter is documented in /includes/functions.php */
			$allow_display = apply_filters( 'charitable_debug_data_allow_display', true ); // phpcs:ignore charitable.PHP.ValidateHooks.InvalidHookName

			if ( ! $allow_display ) {
				return;
			}

			?>

			<!-- Entry Debug metabox -->
			<div id="charitable-entry-debug" class="postbox">

				<div class="postbox-header">
					<h2 class="hndle">
						<span><?php esc_html_e( 'Debug Information', 'charitable' ); ?></span>
					</h2>
				</div>

				<div class="inside">

					<?php charitable_builder_debug_data( $entry ); ?>
					<?php charitable_builder_debug_data( $form_data ); ?>

				</div>

			</div>
			<?php
		}

		/**
		 * Campaign Builder mobile / small screen notice template.
		 *
		 * @since 1.8.0
		 */
		public function mobile_notice() {

			ob_start();

			?>

			<div id='charitable-builder-mobile-notice' class='charitable-fullscreen-notice'>

				<h3><?php esc_html_e( 'Our campaign builder is optimized for desktop computers.', 'charitable' ); ?></h3>
				<p><?php esc_html_e( 'We recommend that you edit your campaign on a bigger screen. If you\'d like to proceed, please understand that some functionality might not behave as expected.', 'charitable' ); ?></p>

				<div class="charitable-fullscreen-notice-buttons">
					<button type="button" class="charitable-fullscreen-notice-button charitable-fullscreen-notice-button-primary">
						<?php esc_html_e( 'Back to All Campaigns', 'charitable' ); ?>
					</button>
					<button type="button" class="charitable-fullscreen-notice-button charitable-fullscreen-notice-button-secondary">
						<?php esc_html_e( 'Continue', 'charitable' ); ?>
					</button>

					<button type="button" class="close"><span class="screen-reader-text"><?php esc_html_e( 'Close', 'charitable' ); ?></span></button>
				</div>

			</div>

			<?php

			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Adds HTML (va templates) related to onboarding to the campaign builder page, made visible if onboarding is enabled.
		 *
		 * @since 1.8.1.12
		 */
		public function render_onboarding_html() {

			if ( ! is_admin() || ! isset( $_GET['page'] ) || 'charitable-campaign-builder' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			include charitable()->get_path( 'includes' ) . 'admin/templates/campaign-builder-onboarding.php';
		}

		/* TOUR */

		/**
		 * Save tour option via AJAX.
		 *
		 * @since 1.8.1.12
		 *
		 * @return array
		 */
		public function save_tour_option_ajax() {

			if ( ! is_admin() ) {
				return;
			}

			check_ajax_referer( 'charitable_onboarding_ajax_nonce', 'nonce' );

			if ( empty( $_POST['data'] ) || empty( $_POST['data']['optionData'] ) || empty( $_POST['data']['type'] ) ) { // phpcs:ignore
				wp_send_json_error();
			}

			$schema      = $this->get_onboarding_option_schema();
			$schema_tour = $schema['tour'];
			$query       = [];

			foreach ( $schema_tour as $key => $value ) {
				if ( isset( $_POST['data']['optionData'][ $key ] ) ) {
					$query[ $key ] = sanitize_text_field( wp_unslash( $_POST['data']['optionData'][ $key ] ) );
				}
			}

			if ( empty( $query ) ) {
				wp_send_json_error();
			}

			if ( ! empty( $query['status'] ) && $query['status'] === 'started' ) {
				$query['started_date_gmt'] = current_time( 'mysql', true );
			}

			if ( ! empty( $query['status'] ) && in_array( $query['status'], [ 'completed', 'canceled', 'skipped' ], true ) ) {
				$query['finished_date_gmt'] = current_time( 'mysql', true );
			}

			if ( ! empty( $query['status'] ) && $query['status'] === 'skipped' ) {
				$query['started_date_gmt']  = current_time( 'mysql', true );
				$query['finished_date_gmt'] = $query['started_date_gmt'];
			}

			$this->set_tour_option( $query );

			wp_send_json_success();
		}

		/**
		 * Set tour parameter(s) to tour option.
		 *
		 * @since 1.8.1.12
		 *
		 * @param array $query Query using 'charitable_builder_tour' schema keys.
		 */
		public function set_tour_option( $query ) {

			if ( empty( $query ) || ! is_array( $query ) ) {
				return;
			}

			$schema      = $this->get_onboarding_option_schema();
			$schema_tour = $schema['tour'];
			$replace     = array_intersect_key( $query, $schema_tour );

			if ( ! $replace ) {
				return;
			}

			// Validate and sanitize the data.
			foreach ( $replace as $key => $value ) {
				if ( in_array( $key, [ 'step', 'user_id' ], true ) ) {
					$replace[ $key ] = absint( $value );

					continue;
				}
				$replace[ $key ] = sanitize_text_field( $value );
			}

			$option      = $this->get_onboarding_options();
			$option_tour = empty( $option['tour'] ) || ! $option['tour'] || ! is_array( $option['tour'] ) ? $schema_tour : $option['tour'];
			$option_tour = array_merge( $option_tour, $replace );

			$option['tour'] = $option_tour;

			update_option( 'charitable_builder_onboarding', $option );
		}

		/**
		 * Get tour parameter(s).
		 *
		 * @since 1.8.1.12
		 * @version 1.8.1.15 - updated global defines.
		 *
		 * @param string $option Option key.
		 * @return mixed
		 */
		public function get_tour_option( $option = false ) {

			$schema  = $this->get_onboarding_option_schema();
			$options = get_option( 'charitable_builder_onboarding', array() );

			if ( 'status' === $option && defined( 'CHARITABLE_CAMPAIGN_BUILDER_FORCE_TOUR' ) && CHARITABLE_CAMPAIGN_BUILDER_FORCE_TOUR ) {
				return 'init';
			}

			// Step in and override here... if 'status' is the option being requested and there is already at least one campaign created then return 'skipped'.
			if ( 'status' === $option && ( ! empty( $tours ) || ! isset( $options['tour']['status'] ) || 'init' === $options['tour']['status'] ) ) {

				$count_campaigns = wp_count_posts( 'campaign' );
				$total_campaigns = isset( $count_campaigns->publish ) ? $count_campaigns->publish : 0;

				if ( $total_campaigns > 0 ) {
					return 'skipped';
				}

				if ( ( defined( 'CHARITABLE_CAMPAIGN_BUILDER_FORCE_NO_TOUR' ) && CHARITABLE_CAMPAIGN_BUILDER_FORCE_NO_TOUR ) ) { // phpcs:ignore
					return 'skipped';
				}
			}

			if ( ! $options || ! is_array( $options ) || ! is_array( $options['tour'] ) ) {
				return $schema['tour'][ $option ] ?? '';
			}

			return $options['tour'][ $option ] ?? $schema['tour'][ $option ] ?? '';
		}

		/**
		 * Check if the tour is active.
		 *
		 * @since 1.8.1.15
		 *
		 * @return bool
		 */
		public function is_tour_active() {

			$status = $this->get_tour_option( 'status' );

			return 'init' === $status || 'started' === $status;
		}


		/**
		 * Display the tour divs HTML.
		 *
		 * @since 1.8.1.1.16
		 *
		 * @return bool
		 */
		public function display_tour_divs() {

			$is_tour_active = $this->is_tour_active();
			$tour_status    = $this->get_tour_option( 'status' );
			if ( $is_tour_active ) {
				return true;
			}

			return false;
		}


		/**
		 * Get option schema for all the onboarding options.
		 *
		 * @since 1.8.1.15
		 *
		 * @return array
		 */
		public function get_onboarding_option_schema() {

			return [
				'tour'      => [
					'status'            => 'init',
					'step'              => 0,
					'user_id'           => get_current_user_id(),
					'started_date_gmt'  => '',
					'finished_date_gmt' => '',
					'window_closed'     => '',
				],
				'checklist' => [
					'status' => 'init',
				],
			];
		}

		/**
		 * Get onboarding options.
		 *
		 * @since 1.8.1.15
		 *
		 * @return array
		 */
		public function get_onboarding_options() {

			$option = get_option( 'charitable_builder_onboarding' );
			if ( empty( $option ) ) {
				$option = $this->get_onboarding_option_schema();
				// Update the option.
				update_option( 'charitable_builder_onboarding', $option );
			}
			return $option;
		}

		/* ONBOARDING */

		/**
		 * Save onboarding option via AJAX.
		 *
		 * @since 1.8.1.12
		 *
		 * @return array
		 */
		public function save_onboarding_option_ajax() {

			if ( ! is_admin() ) {
				return;
			}

			check_admin_referer( 'charitable_onboarding_ajax_nonce' );

			if ( empty( $_POST['option_data'] ) ) {
				wp_send_json_error();
			}

			$schema = $this->get_onboarding_option_schema();
			$query  = [];

			foreach ( $schema as $key => $value ) {
				if ( isset( $_POST['option_data'][ $key ] ) ) {
					$query[ $key ] = sanitize_text_field( wp_unslash( $_POST['option_data'][ $key ] ) );
				}
			}

			if ( empty( $query ) ) {
				wp_send_json_error();
			}

			if ( ! empty( $query['status'] ) && $query['status'] === 'started' ) {
				$query['started_date_gmt'] = current_time( 'mysql', true );
			}

			if ( ! empty( $query['status'] ) && in_array( $query['status'], [ 'completed', 'canceled', 'skipped' ], true ) ) {
				$query['finished_date_gmt'] = current_time( 'mysql', true );
			}

			if ( ! empty( $query['status'] ) && $query['status'] === 'skipped' ) {
				$query['started_date_gmt']  = current_time( 'mysql', true );
				$query['finished_date_gmt'] = $query['started_date_gmt'];
			}

			$this->set_onboarding_option( $query );

			wp_send_json_success();
		}

		/**
		 * Get Onboarding parameter(s).
		 *
		 * @since 1.8.1.12
		 * @version 1.8.1.15 - updated global defines.
		 *
		 * @param string $option Option key.
		 * @return mixed
		 */
		public function get_onboarding_option( $option = false ) {

			$schema      = $this->get_onboarding_option_schema();
			$onboardings = get_option( 'charitable_builder_onboarding' );

			if ( 'status' === $option && defined( 'CHARITABLE_CAMPAIGN_BUILDER_FORCE_ONBOARDING' ) && CHARITABLE_CAMPAIGN_BUILDER_FORCE_ONBOARDING ) {
				return 'init';
			}

			// Step in and override here... if 'status' is the option being requested and there is already at least one campaign created then return 'skipped'.
			if ( 'status' === $option && ( ! empty( $onboardings ) || ! isset( $onboardings['status'] ) || 'init' === $onboardings['status'] ) ) {

				$count_campaigns = wp_count_posts( 'campaign' );
				$total_campaigns = isset( $count_campaigns->publish ) ? $count_campaigns->publish : 0;

				if ( $total_campaigns > 0 ) {
					return 'skipped';
				}

				if ( ( defined( 'CHARITABLE_CAMPAIGN_BUILDER_NO_ONBOARDING' ) && CHARITABLE_CAMPAIGN_BUILDER_NO_ONBOARDING ) ) {
					return 'skipped';
				}
			}

			if ( ! $onboardings || ! is_array( $onboardings ) ) {
				return $schema[ $option ] ?? '';
			}

			return $onboardings[ $option ] ?? $schema[ $option ] ?? '';
		}

		/**
		 * Set Onboarding parameter(s) to Onboarding option.
		 *
		 * @since 1.8.1.12
		 *
		 * @param array $query Query using 'charitable_builder_onboarding' schema keys.
		 */
		public function set_onboarding_option( $query ) {

			if ( empty( $query ) || ! is_array( $query ) ) {
				return;
			}

			$schema  = $this->get_onboarding_option_schema();
			$replace = array_intersect_key( $query, $schema );

			if ( ! $replace ) {
				return;
			}

			// Validate and sanitize the data.
			foreach ( $replace as $key => $value ) {
				if ( in_array( $key, [ 'step', 'user_id', 'form_id', 'embed_page', 'seconds_spent', 'seconds_left' ], true ) ) {
					$replace[ $key ] = absint( $value );

					continue;
				}
				if ( in_array( $key, [ 'feedback_sent', 'feedback_contact_me' ], true ) ) {
					$replace[ $key ] = wp_validate_boolean( $value );

					continue;
				}
				$replace[ $key ] = sanitize_text_field( $value );
			}

			$option = get_option( 'charitable_builder_onboarding' );
			$option = ! $option || ! is_array( $option ) ? $schema : $option;

			update_option( 'charitable_builder_onboarding', array_merge( $option, $replace ) );
		}
	}

endif;
