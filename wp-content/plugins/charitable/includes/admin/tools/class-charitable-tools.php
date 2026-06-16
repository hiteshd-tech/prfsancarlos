<?php
/**
 * Charitable Tools UI.
 *
 * @package   Charitable/Classes/Charitable_Tools
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 * @version   1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools' ) ) :

	/**
	 * Charitable_Tools
	 *
	 * @final
	 * @since 1.8.1.6
	 */
	class Charitable_Tools {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Tools|null
		 */
		private static $instance = null;


		/**
		 * The dynamic groups.
		 *
		 * @var array
		 */
		public $dynamic_groups;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.1.6
		 * @version 1.8.9
		 */
		public function __construct() {

			// Set the default tab.
			// Only redirect if we're on the tools page (not options.php) and no tab is set.
			// Don't redirect when processing form submissions (options.php).
			$is_options_page = isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'options.php' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( charitable_is_tools_view() && ! isset( $_GET['tab'] ) && ! $is_options_page ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$link = admin_url( 'edit-tags.php?taxonomy=campaign_category&post_type=campaign' );
				wp_safe_redirect( $link );
				exit();
			}

			add_action( 'campaign_category_pre_add_form', array( $this, 'add_tools_nav_to_taxononmy_pages' ), 10, 1 );
			add_action( 'campaign_tag_pre_add_form', array( $this, 'add_tools_nav_to_taxononmy_pages' ), 10, 1 );
			add_action( 'campaign_category_pre_edit_form', array( $this, 'add_tools_nav_to_taxononmy_pages' ), 10, 1 );
			add_action( 'campaign_tag_pre_edit_form', array( $this, 'add_tools_nav_to_taxononmy_pages' ), 10, 1 );
			add_action( 'admin_init', array( $this, 'taxonomy_redirects' ), 10, 1 );
		}

		/**
		 * Redirects to the correct taxonomy page.
		 *
		 * @since 1.8.2
		 * @version 1.8.9
		 *
		 * @return void
		 */
		public function taxonomy_redirects() {
			if ( isset( $_GET['page'] ) && 'charitable-tools' == $_GET['page'] && isset( $_GET['tab'] ) && 'categories' == $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$link = admin_url( 'edit-tags.php?taxonomy=campaign_category&post_type=campaign' );
				wp_safe_redirect( $link );
				exit();
			}

			if ( isset( $_GET['page'] ) && 'charitable-tools' == $_GET['page'] && isset( $_GET['tab'] ) && 'tags' == $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$link = admin_url( 'edit-tags.php?taxonomy=campaign_tag&post_type=campaign' );
				wp_safe_redirect( $link );
				exit();
			}
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1.6
		 *
		 * @return Charitable_Tools
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1.6
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * Return the sub-sections for the Import tab.
		 *
		 * @since 1.8.10
		 *
		 * @return array
		 */
		public function get_sub_sections_import() {
			/**
			 * Filter the import sub-tabs.
			 *
			 * @since 1.8.10
			 *
			 * @param array $sub_tabs List of sub-tabs in key=>label format.
			 */
			return apply_filters(
				'charitable_tools_import_sub_tabs',
				array(
					'import__charitable'  => __( 'Charitable', 'charitable' ),
					'import__givewp'      => __( 'GiveWP', 'charitable' ),
					'import__givebutter'  => __( 'GiveButter', 'charitable' ),
				)
			);
		}

		/**
		 * Redirect to default sub-tab when on the import tab without a sub_tab parameter.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function tools_sub_tab_default() {
			// Don't redirect during form submissions.
			if ( ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			if ( ! charitable_is_tools_view( 'import' ) ) {
				return;
			}

			if ( isset( $_GET['sub_tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$link = admin_url( 'admin.php?page=charitable-tools&tab=import&sub_tab=charitable' );
			wp_safe_redirect( $link );
			exit();
		}

		/**
		 * Return the array of tabs used on the settings page.
		 *
		 * @since  1.8.1.6
		 *
		 * @return string[]
		 */
		public function get_sections() {
			/**
			 * Filter the settings tabs.
			 *
			 * @since 1.8.1.6
			 *
			 * @param string[] $tabs List of tabs in key=>label format.
			 */
			return apply_filters(
				'charitable_tools_tabs',
				array(
					'categories'  => __( 'Categories', 'charitable' ),
					'tags'        => __( 'Tags', 'charitable' ),
					'customize'   => __( 'Customize', 'charitable' ),
					'export'      => __( 'Export', 'charitable' ),
					'import'      => __( 'Import', 'charitable' ),
					'system-info' => __( 'System Info', 'charitable' ),
					'snippets'    => __( 'Code Snippets', 'charitable' ),
					'misc'        => __( 'Misc', 'charitable' ),
				)
			);
		}

		/**
		 * Register setting.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function register_settings() {

			if ( ! charitable_is_tools_view() ) {
				return;
			}

			register_setting( 'charitable_tools', 'charitable_tools', array( $this, 'sanitize_settings' ) );

			$fields = $this->get_fields();

			if ( empty( $fields ) ) {
				return;
			}

			$sections = array_merge( $this->get_sections(), $this->get_sub_sections_import(), $this->get_dynamic_groups() );

			/* Register each section */
			foreach ( $sections as $section_key => $section ) {
				$section_id = 'charitable_tools_' . $section_key;

				add_settings_section(
					$section_id,
					__return_null(),
					'__return_false',
					$section_id
				);

				if ( ! isset( $fields[ $section_key ] ) || empty( $fields[ $section_key ] ) ) {
					continue;
				}

				/* Sort by priority */
				$section_fields = $fields[ $section_key ];
				uasort( $section_fields, 'charitable_priority_sort' );

				/* Add the individual fields within the section */
				foreach ( $section_fields as $key => $field ) {
					$this->register_field( $field, array( $section_key, $key ) );
				}
			}
		}

		/**
		 * Return list of dynamic groups.
		 *
		 * @since  1.8.1.6
		 *
		 * @return string[]
		 */
		private function get_dynamic_groups() {
			if ( ! isset( $this->dynamic_groups ) ) {
				/**
				 * Filter the list of dynamic groups.
				 *
				 * @since  1.8.1.6
				 *
				 * @param array $groups The dynamic groups.
				 */
				$this->dynamic_groups = apply_filters( 'charitable_dynamic_tools_groups', array() );
			}

			return $this->dynamic_groups;
		}

		/**
		 * Returns whether the given key indicates the start of a new section of the settings.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $composite_key The unique key for this group.
		 * @return boolean
		 */
		private function is_dynamic_group( $composite_key ) {
			return array_key_exists( $composite_key, $this->get_dynamic_groups() );
		}

		/**
		 * Return the label for the given field.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $field The field definition.
		 * @param  string $key   The field key.
		 * @return string
		 */
		private function get_field_label( $field, $key ) { // phpcs:ignore
			$label = '';

			if ( isset( $field['label_for'] ) ) {
				$label = $field['label_for'];
			}

			if ( isset( $field['title'] ) ) {
				$label = $field['title'];
			}

			return $label;
		}

		/**
		 * Return a space separated string of classes for the given field.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		private function get_field_classes( $field ) {
			$classes = array( 'charitable-settings-field' );

			if ( isset( $field['class'] ) ) {
				$classes[] = $field['class'];
			}

			/**
			 * Filter the list of classes to apply to settings fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $classes The list of classes.
			 * @param array $field   The field definition.
			 */
			$classes = apply_filters( 'charitable_settings_field_classes', $classes, $field );

			return implode( ' ', $classes );
		}

		/**
		 * Return an array with all the fields & sections to be displayed.
		 *
		 * @uses   charitable_settings_fields
		 * @see    Charitable_Settings::register_setting()
		 * @since  1.0.0
		 *
		 * @return array
		 */
		private function get_fields() {
			/**
			 * Use the charitable_settings_tab_fields to include the fields for new tabs.
			 * DO NOT use it to add individual fields. That should be done with the
			 * filters within each of the methods.
			 */
			$fields = array();

			$all_sections = array_merge( $this->get_sections(), $this->get_sub_sections_import() );

			foreach ( $all_sections as $section_key => $section ) {
				/**
				 * Filter the array of fields to display in a particular tab.
				 *
				 * For sub-tabs, uses the filter name charitable_tools_tab_fields_sub_{key}.
				 *
				 * @since 1.0.0
				 *
				 * @param array $fields Array of fields.
				 */
				$filter_name = ( false !== strpos( $section_key, '__' ) )
					? 'charitable_tools_tab_fields_sub_' . $section_key
					: 'charitable_tools_tab_fields_' . $section_key;

				$fields[ $section_key ] = apply_filters( $filter_name, array() );
			}

			/**
			 * Filter the array of settings fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $fields Array of fields.
			 */
			return apply_filters( 'charitable_tools_tab_fields', $fields );
		}

		/**
		 * Recursively add settings fields, given an array.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $field The setting field.
		 * @param  array $keys  Array containing the section key and field key.
		 * @return void
		 */
		private function register_field( $field, $keys ) {
			$section_id = 'charitable_tools_' . $keys[0];

			if ( isset( $field['render'] ) && ! $field['render'] ) {
				return;
			}

			/* Drop the first key, which is the section identifier */
			$field['name'] = implode( '][', $keys );

			if ( ! $this->is_dynamic_group( $keys[0] ) ) {
				array_shift( $keys );
			}

			$field['key']     = $keys;
			$field['classes'] = $this->get_field_classes( $field );
			$callback         = isset( $field['callback'] ) ? $field['callback'] : array( $this, 'render_field' );
			$label            = $this->get_field_label( $field, end( $keys ) );

			add_settings_field(
				sprintf( 'charitable_tools_%s', implode( '_', $keys ) ),
				$label,
				$callback,
				$section_id,
				$section_id,
				$field
			);
		}

		/**
		 * Sanitize settings before saving.
		 *
		 * @since  1.8.9
		 *
		 * @param  array $values Submitted values.
		 * @return array
		 */
		public function sanitize_settings( $values ) {
			$old_values = get_option( 'charitable_tools', array() );
			$new_values = array();

			if ( ! is_array( $old_values ) ) {
				$old_values = array();
			}

			if ( ! is_array( $values ) ) {
				$values = array();
			}

			// Merge submitted values into the master array.
			foreach ( $values as $section => $submitted ) {
				if ( ! is_array( $submitted ) ) {
					continue;
				}
				foreach ( $submitted as $key => $value ) {
					if ( $this->is_dynamic_group( $section ) ) {
						$new_values[ $section ][ $key ] = $value;
					} else {
						$new_values[ $key ] = $value;
					}
				}
			}

			$values = wp_parse_args( $new_values, $old_values );

			if ( charitable_is_debug() ) {
				error_log( 'sanitize_settings tools' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'values:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( 'old_values:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $old_values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( 'new_values:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $new_values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			/**
			 * Filter sanitized tools settings.
			 *
			 * @since 1.8.9
			 *
			 * @param array $values     All values, merged.
			 * @param array $new_values Newly submitted values.
			 * @param array $old_values Old settings.
			 */
			$values = apply_filters( 'charitable_save_tools', $values, $new_values, $old_values );

			return $values;
		}

		/**
		 * Checkbox settings should always be either 1 or 0.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed $value Submitted value for field.
		 * @param  array $field Field definition.
		 * @return int
		 */
		public function sanitize_checkbox_value( $value, $field ) {
			if ( isset( $field['type'] ) && 'checkbox' == $field['type'] ) {
				$value = intval( $value && 'on' == $value );
			}

			return $value;
		}

		/**
		 * Render field. This is the default callback used for all fields, unless an alternative callback has been specified.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Field definition.
		 * @return void
		 */
		public function render_field( $args ) {
			$field_type = isset( $args['type'] ) ? $args['type'] : 'text';

			charitable_admin_view( 'settings/' . $field_type, $args );
		}

		/**
		 * Returns an array of all pages in the id=>title format.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_pages() {
			if ( ! isset( $this->pages ) ) {
				$this->pages = charitable_get_pages_options();
			}

			return $this->pages;
		}

		/**
		 * Add tools nav to taxonomy pages.
		 *
		 * @since 1.8.2
		 *
		 * @param string $taxonomy The taxonomy.
		 *
		 * @return void
		 */
		public function add_tools_nav_to_taxononmy_pages( $taxonomy = false ) { // phpcs:ignore

			ob_start();

			$categories_css = isset( $_GET['taxonomy'] ) && 'campaign_category' == $_GET['taxonomy'] ? 'nav-tab-active' : ''; // phpcs:ignore
			$tags_css       = isset( $_GET['taxonomy'] ) && 'campaign_tag' == $_GET['taxonomy'] ? 'nav-tab-active' : ''; // phpcs:ignore

			?>

			<div id="charitable-tools-nav">
				<h1><?php echo esc_html__( 'Charitable Tools', 'charitable' ); ?></h1>
				<h2 class="nav-tab-wrapper">
						<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=campaign_category&post_type=campaign' ) ); ?>" class="nav-tab <?php echo esc_attr( $categories_css ); ?>"><?php echo esc_html__( 'Categories', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=campaign_tag&post_type=campaign' ) ); ?>" class="nav-tab <?php echo esc_attr( $tags_css ); ?>"><?php echo esc_html__( 'Tags', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=customize' ) ); ?>" class="nav-tab "><?php echo esc_html__( 'Customize', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=export' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'Export', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=import' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'Import', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=system-info' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'System Info', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=snippets' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'Code Snippets', 'charitable' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=misc' ) ); ?>" class="nav-tab"><?php echo esc_html__( 'Misc', 'charitable' ); ?></a>
				</h2>
			</div>

			<?php

			$content = ob_get_clean();

			echo $content; // phpcs:ignore
		}
	}

endif;
