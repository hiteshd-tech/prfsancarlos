<?php
/**
 * Settings class management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Settings' ) ) :

	/**
	 * Design management panel.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Settings extends Charitable_Builder_Panel {

		/**
		 * Form data and settings.
		 *
		 * @since 1.4.4.1
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * Panels for the submenu campaign builder.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $submenu_panels;

		/**
		 * All systems go.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define panel information.
			$this->name    = esc_html__( 'Settings', 'charitable' );
			$this->slug    = 'settings';
			$this->icon    = 'panel_settings.svg';
			$this->order   = 30;
			$this->sidebar = true;

			// This should never be called unless we are on the campaign builder page.
			if ( campaign_is_campaign_builder_admin_page() ) {
				$this->load_submenu_panels();
			}
		}

		/**
		 * Enqueue assets for the Design panel.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {
		}

		/**
		 * Load panels.
		 *
		 * @since 1.8.0
		 */
		public function load_submenu_panels() {

			$this->submenu_panels = apply_filters(
				'charitable_builder_panels_settings_',
				array(
					'general',
					'donation-options',
					'addons',
				)
			);

			foreach ( $this->submenu_panels as $panel ) {
				$panel = sanitize_file_name( $panel );
				$file  = require_once charitable()->get_path( 'includes' ) . "admin/campaign-builder/panels/settings/class-settings-{$panel}.php";

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}


		/**
		 * Output the Field panel sidebar.
		 *
		 * @since 1.8.0
		 */
		public function panel_sidebar() {

			// This should never be called unless we are on the campaign builder page.
			if ( ! campaign_is_campaign_builder_admin_page() ) {
				return;
			}

			do_action( 'charitable_campaign_builder_settings_sidebar', $this->campaign_data );
		}

		/**
		 * Process settings for campaigns, mostly via the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field Field.
		 * @param string $section Settings section.
		 * @param string $top_level Level.
		 * @param string $meta_key Alt legacy location to try to pull settings.
		 *
		 * @return string
		 */
		public function campaign_data_settings( $field = 'title', $section = 'general', $top_level = 'settings', $meta_key = false ) {

			if ( false === $top_level ) {

				$value = isset( $this->campaign_data[ $field ] ) ? $this->campaign_data[ $field ] : false;

			} elseif ( $section === 'campaign-summary' ) {

				$value_array = isset( $this->campaign_data[ $top_level ][ $section ] ) ? $this->campaign_data[ $top_level ][ $section ] : false;

				return $value_array;

			} elseif ( $field === 'categories' ) {

				$value_array = isset( $this->campaign_data[ $top_level ][ $section ] ) ? $this->campaign_data[ $top_level ][ $section ] : false;
				$result      = [];

				if ( ! empty( $value_array ) ) {

					foreach ( $value_array as $key => $value ) {

						$exp_key = explode( '-', $key );

						if ( $exp_key[0] === 'categories' ) {
							$result[] = $value;
						}

					}

				}

				return $result;

			} else {

				$value = isset( $this->campaign_data[ $top_level ][ $section ][ $field ] ) ? $this->campaign_data[ $top_level ][ $section ][ $field ] : false;

			}

			// If there is no value, attempt to see if this could be a postmeta from an addon.
			if ( false === $value && false !== $meta_key && isset( $this->campaign_data['id'] ) ) {

				$value = get_post_meta( intval( $this->campaign_data['id'] ), esc_attr( $meta_key ), true );

			}

			// todo: clean up, santitize.

			return apply_filters( 'charitable_builder_data_setting', $value, $field, $section, $top_level, $this->campaign_data );
		}

		/**
		 * Output the Field panel primary content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {

			// This should never be called unless we are on the campaign builder page.
			if ( ! campaign_is_campaign_builder_admin_page() ) {
				return;
			}

			do_action( 'charitable_campaign_builder_settings_panels', $this->campaign_data );
		}


		/**
		 * Output the Field panel primary content.
		 *
		 * @since 1.8.0
		 */
		public function preview() {

			ob_start();

			?>

		<div class="charitable-campaign-preview container">

		</div>

			<?php

			$preview = ob_get_clean();

			echo $preview; // phpcs:ignore
		}



		/**
		 * Builder field buttons.
		 *
		 * @since 1.8.0
		 */
		public function fields() {
		}

		/**
		 * Sort Add Field buttons by order provided.
		 *
		 * @since 1.8.0
		 *
		 * @param array $a First item.
		 * @param array $b Second item.
		 *
		 * @return array
		 */
		public function field_order( $a, $b ) {

			return $a['order'] - $b['order'];
		}
	}

endif;

new Charitable_Builder_Panel_Settings();
