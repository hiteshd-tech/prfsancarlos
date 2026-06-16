<?php
/**
 * A class to resolve compatibility issues with Weglot.
 *
 * @package   Charitable/Classes/Charitable_Weglot_Compat
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.45
 * @version   1.6.47
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Weglot_Compat' ) ) :

	/**
	 * Charitable_Weglot_Compat
	 *
	 * @since 1.6.45
	 */
	class Charitable_Weglot_Compat {

		/**
		 * Create class object.
		 *
		 * @since 1.6.45
		 */
		public function __construct() {
			/* Donation locale & email */
			add_filter( 'charitable_donation_locale', 'weglot_get_current_language' );
			add_action( 'charitable_before_send_email', array( $this, 'maybe_set_email_locale' ) );

			/* Profile Page */
			add_filter( 'charitable_permalink_profile_page', array( $this, 'get_weglot_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_profile_page', array( $this, 'is_profile_page' ), 10, 2 );

			/* Donation Page */
			add_filter( 'charitable_permalink_campaign_donation_page', array( $this, 'get_weglot_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_campaign_donation_page', array( $this, 'is_campaign_donation_page' ), 10, 2 );

			/* Login Page */
			add_filter( 'charitable_permalink_login_page', array( $this, 'get_weglot_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_login_page', array( $this, 'is_login_page' ), 10, 2 );

			/* Registration Page */
			add_filter( 'charitable_permalink_registration_page', array( $this, 'get_weglot_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_registration_page', array( $this, 'is_registration_page' ), 10, 2 );

			/* Donation Receipt Page */
			add_filter( 'charitable_permalink_donation_receipt_page', array( $this, 'get_weglot_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_donation_receipt_page', array( $this, 'is_donation_receipt_page' ), 10, 2 );

			/**
			 * Do something with this class instance.
			 *
			 * @since 1.6.45
			 *
			 * @param Charitable_Weglot_Compat $helper Weglot compatibility class instance.
			 */
			do_action( 'charitable_weglot_compat', $this );
		}

		/**
		 * Maybe set a locale for the email.
		 *
		 * @since  1.6.45
		 *
		 * @param  Charitable_Email $email The email object.
		 * @return void
		 */
		public function maybe_set_email_locale( Charitable_Email $email ) {
			$donation = $email->get( 'donation' );
			$campaign = $email->get( 'campaign' );

			if ( is_a( $donation, 'Charitable_Donation' ) ) {
				$this->email_locale = $donation->get( 'locale' );
			} elseif ( is_a( $campaign, 'Charitable_Campaign' ) ) {
				$this->email_locale = $campaign->get( 'locale' );
			}

			if ( ! empty( $this->email_locale ) ) {
				add_filter( 'weglot_translate_current_language', array( $this, 'set_email_locale' ) );
				add_action( 'charitable_after_send_email', array( $this, 'turn_off_email_locale_filter' ) );
			}
		}

		/**
		 * Set the locale to use for an email.
		 *
		 * @since  1.6.45
		 *
		 * @return string
		 */
		public function set_email_locale() {
			return $this->email_locale;
		}

		/**
		 * Turn off the email locale filter.
		 *
		 * @since  1.6.45
		 *
		 * @return void
		 */
		public function turn_off_email_locale_filter() {
			remove_filter( 'weglot_translate_current_language', array( $this, 'set_email_locale' ) );
		}

		/**
		 * Get the current language version of the specified endpoint,
		 * or return the default.
		 *
		 * @since  1.6.45
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_weglot_page_url( $default, $args ) {
			if ( empty( $default ) ) {
				return $default;
			}

			/* Prevent Weglot override. */
			if ( array_key_exists( 'weglot_override', $args ) && ! $args['weglot_override'] ) {
				return $default;
			}

			/**
			 * Backwards compatibility for Weglot versions prior to 3.3.0.
			 *
			 * @see https://github.com/Charitable/Charitable/issues/861
			 */
			if ( version_compare( WEGLOT_VERSION, '3.3.0', '<' ) ) {
				$language = weglot_get_current_language();
			} else {
				$language = weglot_get_service( 'Language_Service_Weglot' )->get_language_from_internal( weglot_get_original_language() );
			}

			return weglot_create_url_object( $default )->getForLanguage( $language );
		}

		/**
		 * Checks whether this is the specified page.
		 *
		 * @since  1.6.45
		 *
		 * @param  string  $endpoint The endpoint.
		 * @param  boolean $is_page  Whether we are currently on the page.
		 * @param  array   $args     Mixed arguments.
		 * @return string
		 */
		public function is_weglot_page( $endpoint, $is_page, $args ) {
			/* We've already determined it's the current page. */
			if ( $is_page ) {
				return $is_page;
			}

			/* Prevent Weglot override. */
			if ( array_key_exists( 'weglot_override', $args ) && ! $args['weglot_override'] ) {
				return $is_page;
			}

			/**
			 * Backwards compatibility for Weglot versions prior to 3.3.0.
			 *
			 * @see https://github.com/Charitable/Charitable/issues/861
			 */
			if ( version_compare( WEGLOT_VERSION, '3.3.0', '<' ) ) {
				$language = weglot_get_original_language();
			} else {
				$language = weglot_get_service( 'Language_Service_Weglot' )->get_language_from_internal( weglot_get_original_language() );
			}

			return charitable_get_permalink( $endpoint, $args ) === weglot_create_url_object( weglot_get_current_full_url() )->getForLanguage( $language );
		}

		/**
		 * Check whether we are currently on the campaign donation page.
		 *
		 * @since  1.6.45
		 *
		 * @param  boolean $is_page Whether we are currently on the campaign donation page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_campaign_donation_page( $is_page, $args ) {
			return $this->is_weglot_page( 'campaign_donation_page', $is_page, $args );
		}

		/**
		 * Check whether we are currently on the profile page.
		 *
		 * @since  1.6.45
		 *
		 * @param  boolean $is_page Whether we are currently on the profile page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_profile_page( $is_page, $args ) {
			return $this->is_weglot_page( 'profile_page', $is_page, $args );
		}

		/**
		 * Check whether we are currently on the login page.
		 *
		 * @since  1.6.45
		 *
		 * @param  boolean $is_page Whether we are currently on the login page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_login_page( $is_page, $args ) {
			return $this->is_weglot_page( 'login_page', $is_page, $args );
		}

		/**
		 * Check whether we are currently on the registration page.
		 *
		 * @since  1.6.45
		 *
		 * @param  boolean $is_page Whether we are currently on the registration page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_registration_page( $is_page, $args ) {
			return $this->is_weglot_page( 'registration_page', $is_page, $args );
		}

		/**
		 * Check whether we are currently on the donation receipt page.
		 *
		 * @since  1.6.45
		 *
		 * @param  boolean $is_page Whether we are currently on the donation_receipt page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_donation_receipt_page( $is_page, $args ) {
			return $this->is_weglot_page( 'donation_receipt_page', $is_page, $args );
		}

	}

endif;
