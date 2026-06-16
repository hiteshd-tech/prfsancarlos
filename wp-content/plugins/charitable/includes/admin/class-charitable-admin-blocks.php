<?php
/**
 * Class that sets up editer blocks for Charitable.
 *
 * @package   Charitable/Classes/Charitable_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Blocks' ) ) :

	/**
	 * Charitable_Admin_Blocks
	 *
	 * @final
	 * @since 1.8.0
	 */
	final class Charitable_Admin_Blocks {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Admin|null
		 */
		private static $instance = null;

		/**
		 * Set up the class.
		 *
		 * @since  1.8.0
		 */
		public function __construct() {

			if ( $this->allow_load() ) {

				add_action( 'init', array( $this, 'register_blocks' ) );

				do_action( 'charitable_admin_blocks_loaded' );

			}
		}

		/**
		 * Indicate if current integration is allowed to load.
		 *
		 * @since 1.8.0
		 *
		 * @return bool
		 */
		public function allow_load() {

			return function_exists( 'register_block_type' );
		}

		/**
		 * Registers the block using the metadata loaded from the `block.json` file.
		 * Behind the scenes, it registers also all assets so they can be enqueued
		 * through the block editor in the corresponding context.
		 *
		 * @see https://developer.wordpress.org/reference/functions/register_block_type/
		 *
		 * @since   1.8.0
		 * @version 1.8.3 - Added additional blocks.
		 */
		public function register_blocks() {
			if ( ! $this->is_block_registered( 'create-block/campaignblock' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/campaign/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_campaign_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/campaigns-block' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/campaigns/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_campaigns_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/donations-block' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/donations/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_donations_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/donors-block' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/donors/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_donors_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/donation-button' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/donation-button/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_donation_button_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/campaign-progress-bar' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/campaign-progress-bar/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_campaign_progress_bar_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/campaign-stats' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/campaign-stats/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_campaign_stats_widget' ),
					)
				);
			}
			if ( ! $this->is_block_registered( 'charitable/my-donations' ) ) {
				register_block_type(
					charitable()->get_path( 'directory', true ) . 'assets/js/blocks/my-donations/build/block.json',
					array(
						'render_callback' => array( $this, 'charitable_block_render_my_donations_widget' ),
					)
				);
			}
		}

		/**
		 * Checks if a block is registered.
		 *
		 * @since  1.8.0
		 *
		 * @param string $block_name The block name.
		 * @return bool
		 */
		private function is_block_registered( $block_name ) {

			if ( class_exists( 'WP_Block_Type_Registry' ) ) {
				return WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
			}

			return false;
		}

		/**
		 * Renders the campaign widget block.
		 *
		 * @since  1.8.0
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_campaign_widget( $attributes ) {
			$campaign_id = $attributes['campaignID'];

			ob_start();

			echo do_shortcode( '[campaign id="' . intval( $campaign_id ) . '"]' );

			return ob_get_clean();
		}

		/**
		 * Renders the campaigns widget block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_campaigns_widget( $attributes ) {

			$number             = ! empty( $attributes['numberCampaigns'] ) ? $attributes['numberCampaigns'] : 10;
			$columns            = ! empty( $attributes['columns'] ) ? $attributes['columns'] : 2;
			$order              = ! empty( $attributes['order'] ) ? $attributes['order'] : 'DESC';
			$orderby            = ! empty( $attributes['orderby'] ) ? $attributes['orderby'] : 'post_date';
			$creator            = ! empty( $attributes['creatorId'] ) ? $attributes['creatorId'] : '';
			$exclude            = ! empty( $attributes['excludeCampaignIds'] ) ? $attributes['excludeCampaignIds'] : '';
			$show               = ! empty( $attributes['showChampaignIds'] ) ? $attributes['showChampaignIds'] : '';
			$responsive         = ! empty( $attributes['responsiveLayout'] ) ? 1 : '';
			$masonry            = ! empty( $attributes['masonryLayout'] ) ? 1 : '';
			$description_limit  = ! empty( $attributes['descriptionLimit'] ) ? $attributes['descriptionLimit'] : '';
			$show_donate_button = ! empty( $attributes['showDonateButton'] ) ? $attributes['showDonateButton'] : '';
			$include_inactive   = ! empty( $attributes['includeInactive'] ) ? 1 : '';

			// create a shortcode with the attributes, but only include the attributes that are not empty with ''.
			$shortcode_attribute_string  = '';
			$shortcode_attribute_string .= ! empty( $number ) ? ' number="' . intval( $number ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $columns ) ? ' columns="' . intval( $columns ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $order ) ? ' order="' . esc_attr( $order ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $orderby ) ? ' orderby="' . esc_attr( $orderby ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $creator ) ? ' creator="' . esc_html( $creator ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $exclude ) ? ' exclude="' . esc_html( $exclude ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $show ) ? ' id="' . esc_html( $show ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $responsive ) ? ' responsive="' . intval( $responsive ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $masonry ) ? ' masonry="' . intval( $masonry ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $description_limit ) ? ' description_limit="' . intval( $description_limit ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $show_donate_button ) ? ' button="' . esc_attr( $show_donate_button ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $include_inactive ) ? ' include_inactive="' . esc_attr( $include_inactive ) . '"' : '';

			ob_start();

			echo do_shortcode( '[campaigns ' . $shortcode_attribute_string . ']' );

			return ob_get_clean();
		}

		/**
		 * Renders the donations widget block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_donations_widget( $attributes ) {

			$args                = array();
			$args['title']       = ! empty( $attributes['title'] ) ? $attributes['title'] : '';
			$args['campaign_id'] = ! empty( $attributes['campaignID'] ) ? $attributes['campaignID'] : false;

			ob_start();

			charitable_template( 'widgets/donate.php', array_merge( $args ) );

			return ob_get_clean();
		}

		/**
		 * Renders the donations widget block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_donors_widget( $attributes ) {

			$args                      = array();
			$args['number']            = ! empty( $attributes['donorsNumber'] ) ? $attributes['donorsNumber'] : 10;
			$args['orderby']           = ! empty( $attributes['orderby'] ) ? $attributes['orderby'] : 'date';
			$args['order']             = ! empty( $attributes['order'] ) ? $attributes['order'] : 'DESC';
			$args['campaign']          = ! empty( $attributes['campaignID'] ) ? $attributes['campaignID'] : '';
			$args['distinct']          = ! empty( $attributes['distinctDonors'] ) ? $attributes['distinctDonors'] : '';
			$args['orientation']       = ! empty( $attributes['orientation'] ) ? $attributes['orientation'] : 'horizontal';
			$args['show_name']         = ! empty( $attributes['showName'] ) ? $attributes['showName'] : '0';
			$args['show_location']     = ! empty( $attributes['showLocation'] ) ? $attributes['showLocation'] : '0';
			$args['show_amount']       = ! empty( $attributes['showAmount'] ) ? $attributes['showAmount'] : '0';
			$args['show_avatar']       = ! empty( $attributes['showAvatar'] ) ? $attributes['showAvatar'] : '0';
			$args['hide_if_no_donors'] = ! empty( $attributes['hideIfNoDonors'] ) ? $attributes['hideIfNoDonors'] : '';

			// create a shortcode with the attributes, but only include the attributes that are not empty with ''.
			$shortcode_attribute_string  = '';
			$shortcode_attribute_string .= ! empty( $args['number'] ) ? ' number="' . intval( $args['number'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['orderby'] ) ? ' orderby="' . esc_attr( $args['orderby'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['order'] ) ? ' order="' . esc_attr( $args['order'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['campaign'] ) ? ' campaign="' . esc_html( $args['campaign'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['distinct'] ) ? ' distinct="' . esc_html( $args['distinct'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['orientation'] ) ? ' orientation="' . esc_attr( $args['orientation'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['show_name'] || '0' === $args['show_name'] ) ? ' show_name="' . esc_attr( $args['show_name'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['show_location'] || '0' === $args['show_location'] ) ? ' show_location="' . esc_attr( $args['show_location'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['show_amount'] || '0' === $args['show_amount'] ) ? ' show_amount="' . esc_attr( $args['show_amount'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['show_avatar'] || '0' === $args['show_avatar'] ) ? ' show_avatar="' . esc_attr( $args['show_avatar'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['hide_if_no_donors'] ) ? ' hide_if_no_donors="' . esc_attr( $args['hide_if_no_donors'] ) . '"' : '';

			ob_start();

			echo do_shortcode( '[charitable_donors ' . $shortcode_attribute_string . ']' );

			return ob_get_clean();
		}

		/**
		 * Renders the donations widget block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_campaign_progress_bar_widget( $attributes ) {

			$args                = array();
			$args['campaign_id'] = ! empty( $attributes['campaignID'] ) ? $attributes['campaignID'] : '';

			if ( empty( $args['campaign_id'] ) || ! function_exists( 'charitable_template_campaign_progress_bar' ) ) {
				return;
			}

			ob_start();

			echo charitable_template_campaign_progress_bar( charitable_get_campaign( (int) $args['campaign_id'] ) ); // phpcs:ignore

			return ob_get_clean();
		}

		/**
		 * Renders the donations widget block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_campaign_stats_widget( $attributes ) {

			$args                 = array();
			$args['campaign_ids'] = ! empty( $attributes['campaignIDs'] ) ? $attributes['campaignIDs'] : '';
			$args['display']      = ! empty( $attributes['display'] ) ? $attributes['display'] : 'total';
			$args['goal']         = ! empty( $attributes['goal'] ) ? $attributes['goal'] : '';

			// create a shortcode with the attributes, but only include the attributes that are not empty with ''.
			$shortcode_attribute_string  = '';
			$shortcode_attribute_string .= ! empty( $args['campaign_ids'] ) ? ' campaigns="' . esc_html( $args['campaign_ids'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['display'] ) ? ' display="' . esc_html( $args['display'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['goal'] ) ? ' goal="' . esc_attr( $args['goal'] ) . '"' : '';

			ob_start();

			echo do_shortcode( '[charitable_stat ' . $shortcode_attribute_string . ']' );

			return ob_get_clean();
		}

		/**
		 * Renders the progress bar block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_donation_button_widget( $attributes ) {

			$args               = array();
			$args['campaign']   = ! empty( $attributes['campaignID'] ) ? $attributes['campaignID'] : '';
			$args['itemLabel']  = ! empty( $attributes['itemLabel'] ) ? $attributes['itemLabel'] : '';
			$args['customCSS']  = ! empty( $attributes['customCSS'] ) ? $attributes['customCSS'] : '';
			$args['newTab']     = ! empty( $attributes['newTab'] ) ? $attributes['newTab'] : '0';
			$args['buttonType'] = ! empty( $attributes['buttonType'] ) ? $attributes['buttonType'] : 'button';

			// the button must have a campaign ID, otherwise bail.
			if ( empty( $args['campaign'] ) ) {
				return;
			}

			// create a shortcode with the attributes, but only include the attributes that are not empty with ''.
			$shortcode_attribute_string  = '';
			$shortcode_attribute_string .= ! empty( $args['campaign'] ) ? ' campaign="' . esc_html( $args['campaign'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['itemLabel'] ) ? ' label="' . esc_html( $args['itemLabel'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['customCSS'] ) ? ' css="' . esc_html( $args['itemcustomCSSLabel'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['buttonType'] ) ? ' type="' . esc_html( $args['buttonType'] ) . '"' : '';
			$shortcode_attribute_string .= ! empty( $args['newTab'] ) ? ' new_tab="' . esc_html( $args['newTab'] ) . '"' : '';

			ob_start();

			echo do_shortcode( '[charitable_donate_button ' . $shortcode_attribute_string . ']' );

			return ob_get_clean();
		}

		/**
		 * Renders the donations widget block.
		 *
		 * @since  1.8.3
		 *
		 * @param array $attributes The attributes for the block.
		 */
		public function charitable_block_render_my_donations_widget( $attributes ) {

			$args                      = array();
			$args['include_recurring'] = ! empty( $attributes['includeRecurring'] ) ? $attributes['includeRecurring'] : '';

			// create a shortcode with the attributes, but only include the attributes that are not empty with ''.
			$shortcode_attribute_string  = '';
			$shortcode_attribute_string .= empty( $args['include_recurring'] ) ? ' hide_recurring="1"' : '';

			ob_start();

			echo do_shortcode( '[charitable_my_donations ' . $shortcode_attribute_string . ']' );

			return ob_get_clean();
		}

		/**
		 * Loads admin-only scripts and stylesheets.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {

			$min     = charitable_get_min_suffix();
			$version = charitable()->get_version();

			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			// add data only for block editor pages, where the campaign block (among others) would be used.
			if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {

				wp_localize_script(
					'create-block-campaignblock-editor-script',
					'charitable_block_data',
					$this->get_localized_strings_block_data()
				);
			}

			do_action( 'after_charitable_admin_block_enqueue_scripts', $min, $version, $assets_dir ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook name may be used by other code. Changing it would break existing functionality.
		}

		/**
		 * Retrieves localized strings for the Charitable Blocks editor.
		 *
		 * @since 1.8.0
		 * @version 1.8.3
		 *
		 * @param array $args Optional. Arguments to customize the localized strings.
		 * @return array An array of localized strings.
		 */
		private function get_localized_strings_block_data( $args = array() ) {

			$defaults = array(
				'post_type'      => array( Charitable::CAMPAIGN_POST_TYPE ),
				'posts_per_page' => -1,
				'post_status'    => array( 'publish' ),
			);

			$args = wp_parse_args( $args, $defaults );

			$query = new WP_Query( $args );

			$campaigns_for_dropdown = array();

			if ( ! empty( $query->posts ) ) :
				foreach ( $query->posts as $post_id => $campaign_post ) :
					$campaigns_for_dropdown[] = array(
						'label' => $campaign_post->post_title,
						'value' => $campaign_post->ID,
					);
				endforeach;
				// sory multidimensional array alphabetically by label.
				usort(
					$campaigns_for_dropdown,
					function ( $a, $b ) {
						return strcmp( $a['label'], $b['label'] );
					}
				);
			endif;

			// add blank element.
			array_unshift(
				$campaigns_for_dropdown,
				array(
					'label' => '',
					'value' => '',
				)
			);

			$strings = array(
				'campaigns'                              => $campaigns_for_dropdown,
				'charitable_addons_page'                 => esc_url( admin_url( 'admin.php?page=charitable-addons' ) ),
				'version'                                => '1.8.3',
				'logo'                                   => esc_url( home_url( 'wp-content/plugins/charitable/assets/images/charitable-header-logo.png' ) ),
				'charitable_assets_dir'                  => apply_filters(
					'charitable_campaign_builder_charitable_assets_dir',
					charitable()->get_path( 'directory', false ) . 'assets/'
				),
				'panel_notice_head'                      => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_notice_text'                      => esc_html__( 'Check out our docs for information about this block.', 'charitable' ),
				'panel_notice_link'                      => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/', 'charitable' ) ),
				'panel_notice_link_text'                 => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_campaigns_notice_head'            => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_campaigns_notice_text'            => esc_html__( 'You can read more about the campaigns shortcode and block on our site.', 'charitable' ),
				'panel_campaigns_notice_link'            => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/the-campaigns-shortcode/', 'charitable' ) ),
				'panel_campaigns_notice_link_text'       => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_donations_notice_head'            => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_donations_notice_text'            => esc_html__( 'You can read more about the donations shortcode and block on our site.', 'charitable' ),
				'panel_donations_notice_link'            => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/donations-shortcode/', 'charitable' ) ),
				'panel_donations_notice_link_text'       => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_donation_button_notice_head'      => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_donation_button_notice_text'      => esc_html__( 'You can read more about the shortcode and block for the donation button on our site.', 'charitable' ),
				'panel_donation_button_notice_link'      => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/donation-button-shortcode/', 'charitable' ) ),
				'panel_donation_button_notice_link_text' => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_progress_bar_notice_head'         => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_progress_bar_notice_text'         => esc_html__( 'You can read more about the shortcode and block for the progress bar on our site.', 'charitable' ),
				'panel_progress_bar_notice_link'         => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/progress-bar-shortcode/', 'charitable' ) ),
				'panel_progress_bar_notice_link_text'    => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_stats_notice_head'                => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_stats_notice_text'                => esc_html__( 'You can read more about the stats shortcode and the block on our site.', 'charitable' ),
				'panel_stats_notice_link'                => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/stats-shortcode/', 'charitable' ) ),
				'panel_stats_notice_link_text'           => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_my_donations_notice_head'         => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_my_donations_notice_text'         => esc_html__( 'You can read more about this block along with other related blocks and shortcodes on our site.', 'charitable' ),
				'panel_my_donations_notice_link'         => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/account-shortcodes/', 'charitable' ) ),
				'panel_my_donations_notice_link_text'    => esc_html__( 'Check out our docs.', 'charitable' ),

				'panel_my_donors_notice_head'            => esc_html__( 'Need Some Help?', 'charitable' ),
				'panel_my_donors_notice_text'            => esc_html__( 'You can read more about the donors block and donors shortcode on our site.', 'charitable' ),
				'panel_my_donors_notice_link'            => esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/donors-shortcode/', 'charitable' ) ),
				'panel_my_donors_notice_link_text'       => esc_html__( 'Check out our docs.', 'charitable' ),
			);

			return $strings;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.0
		 *
		 * @return Charitable_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	new Charitable_Admin_Blocks();

endif;
