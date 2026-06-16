<?php
/**
 * A class to resolve compatibility issues with WPML.
 *
 * @package   Charitable/Classes/Charitable_WPML_Compat
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.44
 * @version   1.6.44
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_WPML_Compat' ) ) :

	/**
	 * Charitable_WPML_Compat
	 *
	 * @since 1.6.44
	 */
	class Charitable_WPML_Compat {

		/**
		 * The global sitepress object.
		 *
		 * @since 1.6.44
		 *
		 * @var   SitePress
		 */
		private $sitepress;

		/**
		 * Create class object.
		 *
		 * @since 1.6.44
		 */
		public function __construct() {
			global $sitepress;

			$this->sitepress = $sitepress;

			/* Filter the amount donated to include any translations of the campaign. */
			add_filter( 'charitable_campaign_donated_amount', array( $this, 'get_donated_amount' ), 5, 3 );

			/* Filter the donor count to include any translations of the campaign. */
			add_filter( 'charitable_campaign_donor_count', array( $this, 'get_donor_count' ), 5, 2 );

			/* Handle WPML translation of user dashboard menu */
			add_action( 'wp_update_nav_menu', array( $this, 'flush_menu_object_cache' ) );
			add_action( 'wp_update_nav_menu_item', array( $this, 'flush_menu_object_cache' ) );
			add_filter( 'transient_charitable_user_dashboard_objects', array( $this, 'get_user_dashboard_objects' ) );
			add_filter( 'pre_set_transient_charitable_user_dashboard_objects', array( $this, 'set_user_dashboard_objects' ) );

			/* Profile Page */
			add_filter( 'charitable_permalink_profile_page', array( $this, 'get_profile_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_profile_page', array( $this, 'is_profile_page' ), 10, 2 );

			/* Login Page */
			add_filter( 'charitable_permalink_login_page', array( $this, 'get_login_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_login_page', array( $this, 'is_login_page' ), 10, 2 );

			/* Registration Page */
			add_filter( 'charitable_permalink_registration_page', array( $this, 'get_registration_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_registration_page', array( $this, 'is_registration_page' ), 10, 2 );

			/* Donation Receipt Page */
			add_filter( 'charitable_permalink_donation_receipt_page', array( $this, 'get_donation_receipt_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_donation_receipt_page', array( $this, 'is_donation_receipt_page' ), 10, 2 );

			/* Terms & Conditions and Privacy Policy */
			add_filter( 'charitable_option_terms_conditions_page', array( $this, 'get_wpml_page_id' ) );
			add_filter( 'charitable_option_privacy_policy_page', array( $this, 'get_wpml_page_id' ) );

			/* Add language code to AJAX request. */
			add_filter( 'charitable_javascript_vars', array( $this, 'add_language_code_to_ajaxurl' ) );

			/**
			 * Do something with this class instance.
			 *
			 * @since 1.6.44
			 *
			 * @param Charitable_WPML_Compat $helper WPML compatibility class instance.
			 */
			do_action( 'charitable_wpml_compat', $this );
		}

		/**
		 * Return a list of campaign IDs that are translations of the
		 * given campaign.
		 *
		 * @since  1.6.44
		 *
		 * @param  Charitable_Campaign $campaign
		 * @return array
		 */
		public function get_campaign_translations( Charitable_Campaign $campaign ) {
			/* Get the WPML translation ID. */
			$trid = $this->sitepress->get_element_trid( $campaign->ID, 'post_campaign' );

			return array_values(
				wp_list_pluck(
					$this->sitepress->get_element_translations( $trid, 'post_campaign' ),
					'element_id'
				)
			 );
		}

		/**
		 * Filter the amount donated to a campaign to include any translations of the same campaign.
		 *
		 * @since  1.6.44
		 *
		 * @param  string              $amount   The default amount donated.
		 * @param  Charitable_Campaign $campaign The campaign to get the donated amount for.
		 * @param  boolean             $sanitize Whether to sanitize the amount. False by default.
		 * @return string
		 */
		public function get_donated_amount( $amount, Charitable_Campaign $campaign, $sanitize = false ) {
			$campaigns = $this->get_campaign_translations( $campaign );

			if ( empty( $campaigns ) ) {
				return $amount;
			}

			$amount = charitable_get_table( 'campaign_donations' )->get_campaign_donated_amount( $campaigns );

			if ( $sanitize ) {
				$amount = charitable_sanitize_amount( $amount );
			}

			return $amount;
		}

		/**
		 * Return the number of donors, including any donations to campaign locale variations.
		 *
		 * @since  1.6.44
		 *
		 * @param  int                 $count    The default number of donors.
		 * @param  Charitable_Campaign $campaign The campaign object.
		 * @return int
		 */
		public function get_donor_count( $count, Charitable_Campaign $campaign ) {
			$campaigns = $this->get_campaign_translations( $campaign );

			if ( empty( $campaigns ) ) {
				return $count;
			}

			return charitable_get_table( 'campaign_donations' )->count_campaign_donors( $campaigns );
		}

		/**
		 * Returns the user dashboard menus, including any WPML
		 * translation versions.
		 *
		 * @since  1.6.44
		 *
		 * @return array
		 */
		public function get_user_dashboard_menus() {
			/* Get all nav menu locations. */
			$locations = get_nav_menu_locations();

			if ( ! array_key_exists( 'charitable-dashboard', $locations ) ) {
				return $dashboard_menus;
			}

			/* Get the WPML translation ID for the menu. */
			$trid = $this->sitepress->get_element_trid( $locations['charitable-dashboard'], 'tax_nav_menu' );

			foreach ( $this->sitepress->get_element_translations( $trid, 'tax_nav_menu' ) as $menu_translation ) {
				$dashboard_menus[] = wp_get_nav_menu_object( $menu_translation->element_id );
			}

			return $dashboard_menus;
		}

		/**
		 * Flush the object cache when a menu assigned to the user dashboard
		 * location (in any language) is edited.
		 *
		 * @since  1.6.44
		 *
		 * @param  int $menu_id The menu id.
		 * @return void
		 */
		public function flush_menu_object_cache( $menu_id ) {
			$dashboard_menus = $this->get_user_dashboard_menus();

			if ( empty( $dashboard_menus ) ) {
				delete_transient( 'charitable_user_dashboard_objects' );
			}

			if ( in_array( $menu_id, wp_list_pluck( $dashboard_menus, 'term_id' ) ) ) {
				delete_transient( 'charitable_user_dashboard_objects' );
			}
		}

		/**
		 * Get the user dashboard objects for the current language.
		 *
		 * @since  1.6.44
		 *
		 * @return array
		 */
		public function get_user_dashboard_objects( $objects ) {
			$language = $this->sitepress->get_current_language();

			if ( false === $objects ) {
				return false;
			}

			if ( ! array_key_exists( $language, $objects ) ) {
				return false;
			}

			return $objects[ $language ];
		}

		/**
		 * When saving the user dashboard objects, include any objects in WPML
		 * locations of the user dashboard menu.
		 *
		 * @since  1.6.44
		 *
		 * @return array
		 */
		public function set_user_dashboard_objects( $menu_items ) {
			$language = $this->sitepress->get_current_language();

			/* Temporarily switch off our filter. */
			remove_filter( 'transient_charitable_user_dashboard_objects', array( $this, 'get_user_dashboard_objects' ) );

			$all_menu_items = get_transient( 'charitable_user_dashboard_objects' );

			if ( ! is_array( $all_menu_items ) ) {
				$all_menu_items = array();
			}

			if ( ! array_key_exists( $language, $all_menu_items ) ) {
				$all_menu_items[ $language ] = $menu_items;
			}

			/* Temporarily switch off our filter. */
			add_filter( 'transient_charitable_user_dashboard_objects', array( $this, 'get_user_dashboard_objects' ) );

			return $all_menu_items;
		}

		/**
		 * Get the current language version of the specified endpoint,
		 * or return the default.
		 *
		 * @since  1.6.44
		 *
		 * @param  string $endpoint_id The endpoint ID.
		 * @param  string $default     The endpoint's URL.
		 * @param  array  $args        Mixed set of arguments.
		 * @return string
		 */
		public function get_wpml_page_url( $page_option, $default, $args ) {
			if ( empty( $default ) ) {
				return $default;
			}

			/* Prevent WPML override. */
			if ( array_key_exists( 'wpml_override', $args ) && ! $args['wpml_override'] ) {
				return $default;
			}

			$page_id = charitable_get_option( $page_option, false );

			if ( in_array( $page_id, array( 'wp', 'auto' ) ) ) {
				return $default;
			}

			return get_permalink( wpml_object_id_filter( $page_id, 'page' ) );
		}

		/**
		 * Checks whether this is the specified page.
		 *
		 * @since  1.6.44
		 *
		 * @param  string  $page_option The option key used to record the page ID.
		 * @param  boolean $is_page     Whether we are currently on the page.
		 * @param  array   $args        Mixed arguments.
		 * @return string
		 */
		public function is_wpml_page( $page_option, $is_page, $args ) {
			/* We've already determined it's the current page. */
			if ( $is_page ) {
				return $is_page;
			}

			/* Prevent WPML override. */
			if ( array_key_exists( 'wpml_override', $args ) && ! $args['wpml_override'] ) {
				return $is_page;
			}

			global $post, $wp_query;

			if ( is_null( $post ) || ! $wp_query->is_main_query() ) {
				return $is_page;
			}

			$page_id = charitable_get_option( $page_option, false );

			if ( in_array( $page_id, array( 'wp', 'auto' ) ) ) {
				return $is_page;
			}

			$wpml_page = wpml_object_id_filter( $page_id );

			return $wpml_page && $wpml_page === $post->ID;
		}

		/**
		 * Get the profile page URL.
		 *
		 * @since  1.6.44
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_profile_page_url( $default, $args ) {
			return $this->get_wpml_page_url( 'profile_page', $default, $args );
		}

		/**
		 * Check whether we are currently on the profile page.
		 *
		 * @since  1.6.44
		 *
		 * @param  boolean $is_page Whether we are currently on the profile page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_profile_page( $is_page, $args ) {
			return $this->is_wpml_page( 'profile_page', $is_page, $args );
		}

		/**
		 * Get the login page URL.
		 *
		 * @since  1.6.44
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_login_page_url( $default, $args ) {
			return $this->get_wpml_page_url( 'login_page', $default, $args );
		}

		/**
		 * Check whether we are currently on the login page.
		 *
		 * @since  1.6.44
		 *
		 * @param  boolean $is_page Whether we are currently on the login page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_login_page( $is_page, $args ) {
			return $this->is_wpml_page( 'login_page', $is_page, $args );
		}

		/**
		 * Get the registration page URL.
		 *
		 * @since  1.6.44
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_registration_page_url( $default, $args ) {
			return $this->get_wpml_page_url( 'registration_page', $default, $args );
		}

		/**
		 * Check whether we are currently on the registration page.
		 *
		 * @since  1.6.44
		 *
		 * @param  boolean $is_page Whether we are currently on the registration page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_registration_page( $is_page, $args ) {
			return $this->is_wpml_page( 'registration_page', $is_page, $args );
		}

		/**
		 * Get the donation receipt page URL.
		 *
		 * @since  1.6.44
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_donation_receipt_page_url( $default, $args ) {
			$wpml_url = $this->get_wpml_page_url( 'donation_receipt_page', $default, $args );

			if ( $wpml_url !== $default ) {
				$donation_id = isset( $args['donation_id'] ) ? $args['donation_id'] : get_the_ID();
				$wpml_url     = esc_url_raw( add_query_arg( array( 'donation_id' => $donation_id ), $wpml_url ) );
			}

			return $wpml_url;
		}

		/**
		 * Check whether we are currently on the donation receipt page.
		 *
		 * @since  1.6.44
		 *
		 * @param  boolean $is_page Whether we are currently on the donation_receipt page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_donation_receipt_page( $is_page, $args ) {
			return $this->is_wpml_page( 'donation_receipt_page', $is_page, $args );
		}

		/**
		 * Get the page ID of a specific page, returning the WPML
		 * translation if applicable.
		 *
		 * @since  1.6.44
		 *
		 * @param  int|string $page_id The set page id.
		 * @return int|string
		 */
		public function get_wpml_page_id( $page_id ) {
			if ( empty( $page_id ) || 0 == $page_id ) {
				return $page_id;
			}

			return wpml_object_id_filter( $page_id, 'page' );
		}

		/**
		 * Add the current language code to the ajax url.
		 *
		 * @since  1.6.44
		 *
		 * @param  array $vars Javascript vars.
		 * @return array
		 */
		public function add_language_code_to_ajaxurl( $vars ) {
			$vars['ajaxurl'] = add_query_arg( 'lang', $this->sitepress->get_current_language(), $vars['ajaxurl'] );
			return $vars;
		}
	}

endif;
