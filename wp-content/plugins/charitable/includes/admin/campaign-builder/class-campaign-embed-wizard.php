<?php
/**
 * Class that sets up the WordPress Campaign Builder Wizard.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Embed_Wizard' ) ) :

	/**
	 * Embed Form in a Page wizard.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Campaign_Embed_Wizard {

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

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Charitable_Campaign_Builder ) ) {

				self::$instance = new Charitable_Campaign_Embed_Wizard();

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

			// Campaign Embed Wizard should load only in the Campaign Builder and on the Edit/Add Page screen.
			if (
				! $this->campaign_is_campaign_builder_admin_page( 'builder' ) &&
				! wp_doing_ajax() &&
				! $this->is_campaign_embed_page()
			) {

				return;
			}

			$this->hooks();
		}

		/**
		 * Determine if we are on the campaign builder admin page
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 */
		public function campaign_is_campaign_builder_admin_page() {

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['campaign_id'] ) ) {
				return true;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'charitable-campaign-builder' ) {
				return false;
			} else {
				return true;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Register hooks.
		 *
		 * @since 1.8.0
		 */
		public function hooks() {

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ] );
			add_action( 'admin_footer', [ $this, 'output' ] );
			add_filter( 'default_title', [ $this, 'embed_page_title' ], 10, 2 );
			add_filter( 'default_content', [ $this, 'embed_page_content' ], 10, 2 );
			add_action( 'wp_ajax_campaign_admin_campaign_embed_wizard_embed_page_url', [ $this, 'get_embed_page_url_ajax' ] );
			add_action( 'wp_ajax_campaign_admin_campaign_embed_wizard_clear_meta', [ $this, 'ajax_delete_meta' ] );
			add_action( 'wp_ajax_campaign_admin_campaign_embed_wizard_search_pages_choicesjs', [ $this, 'get_search_result_pages_ajax' ] );
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {

			// $min = campaign_get_min_suffix();
			$min = false;

			wp_enqueue_style(
				'campaign-admin-campaign-embed-wizard',
				charitable()->get_path( 'directory', false ) . 'assets/css/campaign-builder/campaign-embed-wizard.css',
				null,
				'4.7.0'
			);

			wp_enqueue_script(
				'campaign-admin-campaign-embed-wizard',
				charitable()->get_path( 'directory', false ) . 'assets/js/campaign-builder/campaign-embed-wizard.js',
				[ 'jquery', 'underscore' ],
				'4.2.6'
			);

			wp_localize_script(
				'campaign-admin-campaign-embed-wizard',
				'campaign_admin_campaign_embed_wizard',
				[
					'nonce'        => wp_create_nonce( 'campaign_admin_campaign_embed_wizard_nonce' ),
					'is_edit_page' => (int) $this->is_campaign_embed_page( 'edit' ),
				]
			);
		}

		/**
		 * Output HTML.
		 *
		 * @since   1.8.0
		 * @version 1.8.1
		 *
		 * @return void
		 */
		public function output() {

			// We don't need to output tooltip if it's not an embed flow.
			if ( $this->is_campaign_embed_page() && ! $this->get_meta() ) {
				return;
			}

			$template  = $this->is_campaign_embed_page() ? charitable()->get_path( 'directory', true ) . 'includes/admin/campaign-builder/campaign-embed-wizard/tooltip.php' : charitable()->get_path( 'directory', true ) . 'includes/admin/campaign-builder/campaign-embed-wizard/popup.php';
			$view_args = [];

			if ( ! $this->is_campaign_embed_page() ) {
				$args['user_can_edit_pages'] = current_user_can( 'edit_pages' );
				$args['dropdown_pages']      = $this->get_select_dropdown_pages_html();
			}

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
		public function is_campaign_embed_page( $type = '' ) {

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

			update_user_meta( get_current_user_id(), 'campaign_admin_campaign_embed_wizard', $data );
		}

		/**
		 * Get user's embed meta data.
		 *
		 * @since 1.8.0
		 *
		 * @return array User's embed meta data.
		 */
		public function get_meta() {

			return get_user_meta( get_current_user_id(), 'campaign_admin_campaign_embed_wizard', true );
		}

		/**
		 * Delete user's embed meta data.
		 *
		 * @since 1.8.0
		 */
		public function delete_meta() {

			delete_user_meta( get_current_user_id(), 'campaign_admin_campaign_embed_wizard' );
		}

		/**
		 * Delete meta via ajax.
		 *
		 * @since 1.8.1
		 */
		public function ajax_delete_meta() {

			check_admin_referer( 'campaign_admin_campaign_embed_wizard_nonce' );

			$this->delete_meta();

			wp_send_json_success();
		}

		/**
		 * Get embed page URL via AJAX.
		 *
		 * @since 1.8.0
		 */
		public function get_embed_page_url_ajax() {

			check_admin_referer( 'campaign_admin_campaign_embed_wizard_nonce' );

			$page_id = ! empty( $_POST['pageId'] ) ? absint( $_POST['pageId'] ) : 0;

			if ( ! empty( $page_id ) ) {
				$url  = get_edit_post_link( $page_id, '' );
				$meta = [
					'embed_page' => $page_id,
				];
			} else {
				$url  = add_query_arg( 'post_type', 'page', admin_url( 'post-new.php' ) );
				$meta = [
					'embed_page'       => 0,
					'embed_page_title' => ! empty( $_POST['pageTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['pageTitle'] ) ) : '',
				];
			}

			$meta['form_id'] = ! empty( $_POST['formId'] ) ? absint( $_POST['formId'] ) : 0;

			$this->set_meta( $meta );

			wp_send_json_success( $url );
		}

		/**
		 * Set default title for the new page.
		 *
		 * @since 1.8.0
		 *
		 * @param string   $post_title Default post title.
		 * @param \WP_Post $post       Post object.
		 *
		 * @return string New default post title.
		 */
		public function embed_page_title( $post_title, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			$meta = $this->get_meta();

			$this->delete_meta();

			return empty( $meta['embed_page_title'] ) ? $post_title : $meta['embed_page_title'];
		}

		/**
		 * Embed the campaign to the new page.
		 *
		 * @since 1.8.0
		 *
		 * @param string   $post_content Default post content.
		 * @param \WP_Post $post         Post object.
		 *
		 * @return string Embedding string (shortcode or GB component code).
		 */
		public function embed_page_content( $post_content, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			$meta = $this->get_meta();

			$campaign_id = ! empty( $meta['form_id'] ) ? $meta['form_id'] : 0;
			$page_id     = ! empty( $meta['embed_page'] ) ? $meta['embed_page'] : 0;

			if ( ! empty( $page_id ) || empty( $campaign_id ) ) {
				return $post_content;
			}

			if ( charitable_is_gutenberg_active() ) {
				$pattern = '<!-- wp:create-block/campaignblock {"campaignID":"%d"} /-->';
			} else {
				$pattern = '[campaign id="%d"]';
			}

			return sprintf( $pattern, absint( $campaign_id ) );
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
					'id'        => 'campaign-embed-wizard-choicesjs-select-pages',
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

		/**
		 * Get search result pages for ChoicesJS via AJAX.
		 *
		 * @since 1.8.0
		 */
		public function get_search_result_pages_ajax() {

			// Run a security check.
			if ( ! check_ajax_referer( 'campaign_admin_campaign_embed_wizard_nonce', false, false ) ) {
				wp_send_json_error(
					[
						'msg' => esc_html__( 'Your session expired. Please reload the builder.', 'charitable' ),
					]
				);
			}

			if ( ! array_key_exists( 'search', $_GET ) ) {
				wp_send_json_error(
					[
						'msg' => esc_html__( 'Incorrect usage of this operation.', 'charitable' ),
					]
				);
			}

			$result_pages = campaign_search_pages_for_dropdown(
				sanitize_text_field( wp_unslash( $_GET['search'] ) ),
				[
					'count'       => self::MAX_SEARCH_RESULTS_DROPDOWN_PAGES_COUNT,
					'post_status' => self::POST_STATUSES_OF_DROPDOWN_PAGES,
				]
			);

			if ( empty( $result_pages ) ) {
				wp_send_json_error( [] );
			}

			wp_send_json_success( $result_pages );
		}

		/**
		 * Excludes pages from dropdown which user can't edit.
		 *
		 * @since 1.8.0
		 * @deprecated 1.8.0
		 *
		 * @param WP_Post[] $pages Array of page objects.
		 *
		 * @return WP_Post[]|false Array of filtered pages or false.
		 */
		public function remove_inaccessible_pages( $pages ) {

			_deprecated_function( __METHOD__, '1.8.0 of the Campaign plugin' );

			if ( ! $pages ) {
				return $pages;
			}

			foreach ( $pages as $key => $page ) {
				if ( ! current_user_can( 'edit_page', $page->ID ) ) {
					unset( $pages[ $key ] );
				}
			}

			return $pages;
		}
	}


endif;
