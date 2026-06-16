<?php
/**
 * Class that sets up the Campaign builder preview abilities.
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

if ( ! class_exists( 'Campaign_Builder_Preview' ) ) :

	/**
	 * Campaign preview.
	 *
	 * @since 1.8.0
	 * @version 1.8.8.6
	 */
	class Campaign_Builder_Preview { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Class name is used by other code. Changing it would break existing functionality.

		/**
		 * One is the loneliest number that you'll ever do.
		 *
		 * @since 1.8.0
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Campaign data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * Constructor.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			if ( ! $this->is_preview_page() ) {
				return;
			}

			$this->hooks();
		}

		/**
		 * Main Instance.
		 *
		 * @since 1.8.0
		 *
		 * @return Charitable_Builder
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Campaign_Builder_Preview ) ) {

				self::$instance = new Campaign_Builder_Preview();

			}

			return self::$instance;
		}

		/**
		 * Init
		 *
		 * @since 1.8.0
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * Check if current page request meets requirements for campaign preview page.
		 *
		 * @since 1.8.0
		 *
		 * @return bool
		 */
		public function is_preview_page() {

			// Only proceed for the campaign preview page.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET['charitable_campaign_preview'] ) ) {
				return false;
			}

			// Check for logged-in user with correct capabilities.
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$campaign_id = isset( $_GET['p'] ) ? absint( $_GET['p'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$campaign_id = ( 0 === $campaign_id && ! empty( $_GET['charitable_campaign_preview'] ) ) ? absint( $_GET['charitable_campaign_preview'] ) : $campaign_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 0 === $campaign_id && ! empty( $_GET['charitable_campaign_preview'] ) && 1 === intval( $_GET['charitable_campaign_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				global $post;
				if ( ! empty( $post->post_id ) ) {
					$campaign_id = absint( $post->post_id );
				}
			}

			// check if this custom post type actually exists.
			global $wpdb;
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ID=%s AND post_status != 'trash'", array( $campaign_id ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				return false;
			}

			// check permissions.
			if ( ! charitable_current_user_can( 'edit_post', $campaign_id ) ) { // this was 'view_campaign_single'.
				return false;
			}

			// Fetch campaign details.
			$this->campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

			// Check valid campaign was found.
			if ( empty( $this->campaign_data ) || ( empty( $this->campaign_data['id'] ) && empty( absint( $_GET['charitable_campaign_preview'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return false;
			}

			return true;
		}

		/**
		 * Hooks.
		 *
		 * @since 1.8.0
		 */
		public function hooks() {

			add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
			add_filter( 'the_title', [ $this, 'the_title' ], 100, 1 );
			add_filter( 'the_content', [ $this, 'the_content' ], 999 );
			add_filter( 'get_the_excerpt', [ $this, 'the_content' ], 999 );
			add_filter( 'home_template_hierarchy', [ $this, 'force_page_template_hierarchy' ], 999 );
			add_filter( 'frontpage_template_hierarchy', [ $this, 'force_page_template_hierarchy' ], 999 );
			add_filter( 'post_thumbnail_html', '__return_empty_string' );
			add_filter( 'charitable_campaign_can_receive_donations', [ $this, 'enable_campaign_form' ], 10, 2 );
		}

		/**
		 * The campaign donation form should be enabled for a campaign preview page, so on a preview page it's "ok" for a campaign to recieve a donation.
		 *
		 * @param boolean $can        Whether the campaign form accepts donations.
		 * @param mixed   $the_object Charitable_Campaign.
		 *
		 * @return boolean Whether the campaign form can be enabled for the given object.
		 */
		public function enable_campaign_form( $can, $the_object ) { // phpcs:ignore
			return true;
		}

		/**
		 * Modify query, limit to one post.
		 *
		 * @since 1.8.0
		 *
		 * @param \WP_Query $query The WP_Query instance.
		 */
		public function pre_get_posts( $query ) {

			if ( is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$query->set( 'page_id', '' );
			$query->set( 'post_type', 'campaign' );
			$query->set( 'post__in', empty( $this->campaign_data['id'] ) ? [] : [ (int) $this->campaign_data['id'] ] );
			$query->set( 'posts_per_page', 1 );
		}

		/**
		 * Customize campaign preview page title.
		 *
		 * @since 1.5.1
		 *
		 * @param string $title Page title.
		 *
		 * @return string
		 */
		public function the_title( $title ) {

			if ( in_the_loop() ) {
				$title = sprintf( /* translators: %s - campaign title. */
					esc_html__( '%s Preview', 'charitable' ),
					! empty( $this->campaign_data['settings']['campaign_title'] ) ? sanitize_text_field( $this->campaign_data['settings']['campaign_title'] ) : esc_html__( 'Campaign', 'charitable' )
				);
			}

			return $title;
		}

		/**
		 * Customize campaign preview page content.
		 *
		 * @since 1.8.0
		 *
		 * @return string
		 */
		public function the_content() {

			if ( ! isset( $this->campaign_data['id'] ) ) {
				return '';
			}

			if ( ! charitable_current_user_can( 'edit_posts', $this->campaign_data['id'] ) ) {
				return '';
			}

			$links = [];

			if ( charitable_current_user_can( 'edit_posts', $this->campaign_data['id'] ) ) {
				$links[] = [
					'url'  => esc_url(
						add_query_arg(
							[
								'page'        => 'charitable-campaign-builder',
								'view'        => 'design',
								'campaign_id' => absint( $this->campaign_data['id'] ),
							],
							admin_url( 'admin.php' )
						)
					),
					'text' => esc_html__( 'Edit Campaign', 'charitable' ),
				];
			}

			if ( charitable_current_user_can( 'read', $this->campaign_data['id'] ) ) {
				$links[] = [
					'url'  => esc_url(
						add_query_arg(
							[
								'post_type'   => 'donation',
								'campaign_id' => absint( $this->campaign_data['id'] ),
							],
							admin_url( 'edit.php' )
						)
					),
					'text' => esc_html__( 'View Donations', 'charitable' ),
				];
			}

			$links[] = [
				'url'  => esc_url(
					charitable_utm_link( 'https://www.wpcharitable.com/documentation/', 'Campaign Preview', 'Documentation' )
				),
				'text' => esc_html__( 'Documentation', 'charitable' ),
			];

			if ( ! empty( $_GET['new_window'] ) ) { // phpcs:ignore
				$links[] = [
					'url'  => 'javascript:window.close();',
					'text' => esc_html__( 'Close this window', 'charitable' ),
				];
			}

			$content = '<div class="charitable-preview-messages"><p>';

			if ( 'publish' === get_post_status( $this->campaign_data['id'] ) ) :

				if ( ! empty( $_GET['charitable_campaign_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					$content .= '<p>';

					$content .= sprintf(
						wp_kses(
							/* translators: %s - Charitable doc link. */
							__( 'You are viewing a preview of a <a href="%s" target="_blank">published campaign</a>. If this does not match your campaign page in the campaign builder, save your changes and then refresh this page. Your current theme can also effect the look of this page.', 'charitable' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url( get_permalink( $this->campaign_data['id'] ) )
					);

					$content .= '</p>';

					$content .= '<p>' . esc_html__( 'Ssome functionality and elements (like donation buttons, forms, social sharing, etc.) are disabled.', 'charitable' ) . '</p>';

				} else {

					$content .= '<p>';

					$content .= sprintf(
						wp_kses(
							/* translators: %s - Charitable doc link. */
							__( 'This is a preview of the latest saved revision of your campaign page. If this does not match your campaign page in the campaign builder, save your changes and then refresh this page. Your current theme can also effect the look of this page.', 'charitable' ),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						),
						esc_url( get_permalink( $this->campaign_data['id'] ) )
					);

					$content .= '</p>';

					$content .= '<p>' . esc_html__( 'Ssome functionality and elements (like donation buttons, forms, social sharing, etc.) are disabled.', 'charitable' ) . '</p>';

				}

				array_unshift(
					$links,
					[
						'url'  => esc_url(
							get_permalink( $this->campaign_data['id'] )
						),
						'text' => esc_html__( 'View Live Campaign', 'charitable' ),
					]
				);

			elseif ( 'draft' === get_post_status( $this->campaign_data['id'] ) ) :

				$content .= '<p>';

				$content .= sprintf(
					wp_kses(
						/* translators: %s - Charitable doc link. */
						__( 'This is a preview of the latest saved revision of your campaign page. If this does not match your campaign page in the campaign builder, save your changes and then refresh this page. Your current theme can also effect the look of this page.', 'charitable' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					),
					esc_url( get_permalink( $this->campaign_data['id'] ) )
				);

				$content .= '</p>';

				$content .= '<p>' . esc_html__( 'Some functionality and elements (like donation buttons, forms, social sharing, etc.) are disabled.', 'charitable' ) . '</p>';

			endif;

			if ( ! empty( $links ) ) {
				$content .= '<span class="charitable-preview-notice-links">';

				foreach ( $links as $key => $link ) {
					$content .= '<a href="' . $link['url'] . '">' . $link['text'] . '</a>';
					$l        = array_keys( $links );

					if ( end( $l ) !== $key ) {
						$content .= ' <span style="display:inline-block;margin:0 6px;opacity: 0.5">|</span> ';
					}
				}

				$content .= '</span>';
			}
			$content .= '</p>';

			$content .= '</div>';

			// if there is no campaign ID in the campaign_data, this MIGHT be an initial preview on a new campaign which means it should be in the URL.
			$campaign_id = 0 === intval( $this->campaign_data['id'] ) && ! empty( $_GET['charitable_campaign_preview'] ) ? absint( $_GET['charitable_campaign_preview'] ) : absint( $this->campaign_data['id'] ); // phpcs:ignore

			$content .= do_shortcode( '[campaign version="2" id=' . $campaign_id . ']' );

			return $content;
		}

		/**
		 * Force page template types.
		 *
		 * @since 1.8.0
		 *
		 * @param array $templates A list of template candidates, in descending order of priority.
		 *
		 * @return array
		 */
		public function force_page_template_hierarchy( $templates ) { // phpcs:ignore

			return [ 'page.php', 'single.php', 'index.php' ];
		}

		/**
		 * Adjust value of the {page_title} smart tag.
		 *
		 * @since 1.7.7
		 *
		 * @param string $content          Content.
		 * @param array  $campaign_data    Campaign data.
		 * @param array  $fields           List of fields.
		 * @param string $entry_id         Entry ID.
		 * @param object $smart_tag_object The smart tag object or the Generic object for those cases when class unregistered.
		 *
		 * @return string
		 */
		public function smart_tags_process_page_title_value( $content, $campaign_data, $fields, $entry_id, $smart_tag_object ) { // phpcs:ignore

			return sprintf( /* translators: %s - campaign title. */
				esc_html__( '%s Preview', 'charitable' ),
				! empty( $campaign_data['settings']['campaign_title'] ) ? sanitize_text_field( $campaign_data['settings']['campaign_title'] ) : esc_html__( 'Campaign', 'charitable' )
			);
		}

		/**
		 * Force page template types.
		 *
		 * @since 1.5.1
		 * @deprecated 1.8.0
		 *
		 * @return string
		 */
		public function template_include() {

			_deprecated_function( __METHOD__, '1.8.0 of the Charitable plugin' );

			return locate_template( [ 'page.php', 'single.php', 'index.php' ] );
		}
	}

endif;

new Campaign_Builder_Preview();
