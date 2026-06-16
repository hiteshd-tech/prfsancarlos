<?php
/**
 * Help class management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0, 1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Help' ) ) :

	/**
	 * Design management panel.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Help extends Charitable_Builder_Panel {

		/**
		 * Form data and help.
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
			$this->name    = esc_html__( 'Help', 'charitable' );
			$this->slug    = 'help';
			$this->icon    = 'panel_help.svg';
			$this->order   = 100;
			$this->sidebar = true;
			$this->button  = true;
			$this->url     = 'https://wpcharitable.com/documentation/';

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
		}


		/**
		 * Output the Field panel sidebar.
		 *
		 * @since 1.8.0
		 */
		public function panel_sidebar() {
		}



		/**
		 * Output the Field panel primary content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {
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

new Charitable_Builder_Panel_Help();
