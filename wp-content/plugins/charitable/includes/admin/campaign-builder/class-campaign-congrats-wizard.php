<?php
/**
 * Class that sets up the WordPress Campaign Builder Wizard.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Congrats_Wizard' ) ) :

	/**
	 * Embed Form in a Page wizard.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Campaign_Congrats_Wizard {

		/**
		 * One is the loneliest number that you'll ever do.
		 *
		 * @since 1.8.0
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Max search results count of 'Select Page' dropdown.
		 *
		 * @since 1.8.0
		 *
		 * @var int
		 */
		const MAX_SEARCH_RESULTS_DROPDOWN_PAGES_COUNT = 20;

		/**
		 * Post statuses of pages in 'Select Page' dropdown.
		 *
		 * @since 1.8.0
		 *
		 * @var string[]
		 */
		const POST_STATUSES_OF_DROPDOWN_PAGES = [ 'publish', 'pending' ];

		/**
		 * Main Instance.
		 *
		 * @since 1.8.0
		 *
		 * @return Charitable_Builder
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Charitable_Campaign_Congrats_Wizard ) ) {

				self::$instance = new Charitable_Campaign_Congrats_Wizard();

				add_action( 'admin_init', [ self::$instance, 'init' ], 10 );

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
		 * Initialize class.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'charitable-campaign-builder' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			// Campaign Embed Wizard should load only in the Campaign Builder and on the Edit/Add Page screen.
			if (
				! $this->campaign_is_campaign_builder_admin_page( 'builder' ) &&
				! wp_doing_ajax() &&
				! $this->is_campaign_congrats_page()
			) {

				return;
			}

			$this->hooks();
		}

		/**
		 * Determine if we are on the campaign builder admin page
		 *
		 * @since 1.8.0
		 */
		public function campaign_is_campaign_builder_admin_page() {

			if ( isset( $_POST['campaign_id'] ) ) { // phpcs:ignore
				return true;
			}

			if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'charitable-campaign-builder' ) { // phpcs:ignore
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Register hooks.
		 *
		 * @since 1.8.0
		 */
		public function hooks() {

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );
			add_action( 'admin_footer', [ $this, 'output' ] );
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {

			$min           = charitable_get_min_suffix();
			$style_version = charitable_get_style_version();

			wp_enqueue_style(
				'campaign-admin-campaign-congrats-wizard',
				charitable()->get_path( 'directory', false ) . "assets/css/campaign-builder/campaign-congrats-wizard{$min}.css",
				null,
				$style_version
			);

			wp_enqueue_script(
				'campaign-admin-congrats-wizard',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/campaign-congrats-wizard{$min}.js",
				[ 'jquery', 'underscore' ],
				$style_version
			);

			wp_enqueue_script(
				'campaign-admin-congrats-wizard-confetti',
				charitable()->get_path( 'directory', false ) . 'assets/js/libraries/confetti.min.js',
				[ 'jquery', 'campaign-admin-congrats-wizard' ],
				'1.8.0'
			);

			wp_localize_script(
				'campaign-admin-campaign-congrats-wizard',
				'campaign_admin_campaign_congrats_wizard',
				[
					'nonce'        => wp_create_nonce( 'campaign_admin_campaign_congrats_wizard_nonce' ),
					'is_edit_page' => (int) $this->is_campaign_congrats_page( 'edit' ),
				]
			);
		}

		/**
		 * Output HTML.
		 *
		 * @since 1.8.0
		 */
		public function output() {

			$template  = charitable()->get_path( 'directory', true ) . 'includes/admin/campaign-builder/campaign-congrats-wizard/popup.php';
			$view_args = [];

			$args['user_can_edit_pages'] = current_user_can( 'edit_pages' );
			$args['dropdown_pages']      = $this->get_select_dropdown_pages_html();

			include $template;
		}

		/**
		 * Check if the current page is a campaign embed page.
		 *
		 * @since 1.8.0
		 *
		 * @param string $type Type of the embed page to check. Can be '', 'add' or 'edit'. By default is empty string.
		 *
		 * @return boolean
		 */
		public function is_campaign_congrats_page( $type = '' ) {

			global $pagenow;

			$type = $type === 'add' || $type === 'edit' ? $type : '';

			if (
				$pagenow !== 'post.php' &&
				$pagenow !== 'post-new.php'
			) {
				return false;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$post_id   = empty( $_GET['post'] ) ? 0 : (int) $_GET['post'];
			$post_type = empty( $_GET['post_type'] ) ? '' : sanitize_key( $_GET['post_type'] );
			$action    = empty( $_GET['action'] ) ? 'add' : sanitize_key( $_GET['action'] );
			// phpcs:enable

			if ( $pagenow === 'post-new.php' &&
				( empty( $post_type ) || $post_type !== 'page' )
			) {
				return false;
			}

			if (
				$pagenow === 'post.php' &&
				( empty( $post_id ) || get_post_type( $post_id ) !== 'page' )
			) {
				return false;
			}

			$meta       = $this->get_meta();
			$embed_page = ! empty( $meta['embed_page'] ) ? (int) $meta['embed_page'] : 0;

			if ( 'add' === $action && 0 === $embed_page && $type !== 'edit' ) {
				return true;
			}

			if ( ! empty( $post_id ) && $embed_page === $post_id && $type !== 'add' ) {
				return true;
			}

			return false;
		}

		/**
		 * Set user's embed meta data.
		 *
		 * @since 1.8.0
		 *
		 * @param array $data Data array to set.
		 */
		public function set_meta( $data ) {

			update_user_meta( get_current_user_id(), 'campaign_admin_campaign_congrats_wizard', $data );
		}

		/**
		 * Get user's embed meta data.
		 *
		 * @since 1.8.0
		 *
		 * @return array User's embed meta data.
		 */
		public function get_meta() {

			return get_user_meta( get_current_user_id(), 'campaign_admin_campaign_congrats_wizard', true );
		}

		/**
		 * Delete user's embed meta data.
		 *
		 * @since 1.8.0
		 */
		public function delete_meta() {

			delete_user_meta( get_current_user_id(), 'campaign_admin_campaign_congrats_wizard' );
		}

		/**
		 * Get embed page URL via AJAX.
		 *
		 * @since 1.8.0
		 */
		public function get_congrats_page_url_ajax() {
		}

		/**
		 * Generate select with pages which are available to edit for current user.
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		private function get_select_dropdown_pages_html() {

			$dropdown_pages = charitable_campaign_search_posts(
				'',
				[
					'count'       => self::MAX_SEARCH_RESULTS_DROPDOWN_PAGES_COUNT,
					'post_status' => self::POST_STATUSES_OF_DROPDOWN_PAGES,
				]
			);

			if ( empty( $dropdown_pages ) ) {
				return '';
			}

			$total_pages    = 0;
			$wp_count_pages = (array) wp_count_posts( 'page' );

			foreach ( $wp_count_pages as $page_status => $pages_count ) {
				if ( in_array( $page_status, self::POST_STATUSES_OF_DROPDOWN_PAGES, true ) ) {
					$total_pages += $pages_count;
				}
			}

			$return_this = charitable_settings_select_callback(
				[
					'id'        => 'campaign-congrats-wizard-choicesjs-select-pages',
					'type'      => 'select',
					'choicesjs' => true,
					'options'   => wp_list_pluck( $dropdown_pages, 'post_title', 'ID' ),
					'data'      => [
						'use_ajax' => $total_pages > self::MAX_SEARCH_RESULTS_DROPDOWN_PAGES_COUNT,
					],
				]
			);

			return $return_this;
		}
	}


endif;
