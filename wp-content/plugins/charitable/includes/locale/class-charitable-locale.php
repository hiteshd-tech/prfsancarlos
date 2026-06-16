<?php
/**
 * Add support for switching between locales.
 *
 * @package   Charitable/Classes/Charitable_Locale
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.43
 * @version   1.6.57
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Locale' ) ) :

	/**
	 * Charitable_Locale
	 *
	 * @since 1.6.43
	 */
	class Charitable_Locale {

		/**
		 * The current locale.
		 *
		 * @since 1.6.43
		 *
		 * @var   string
		 */
		private $locale;

		/**
		 * The locale to use for the email being sent.
		 *
		 * @since 1.6.43
		 *
		 * @var   string
		 */
		private $email_locale;

		/**
		 * Create class object.
		 *
		 * @since 1.6.43
		 */
		public function __construct() {
			/**
			 * Boolean flag to decide whether to enable the locale functions.
			 *
			 * @since 1.6.43
			 *
			 * @param boolean $locale_enabled Whether to enable locale functions.
			 */
			if ( ! apply_filters( 'charitable_enable_locale_functions', false ) ) {
				return;
			}

			$this->locale = get_locale();

			/**
			 * Add locale as donation and campaign fields.
			 */
			add_filter( 'charitable_default_donation_fields', array( $this, 'add_locale_donation_field' ) );
			add_filter( 'charitable_default_campaign_fields', array( $this, 'add_locale_campaign_field' ) );

			/**
			 * Store current locale when a donation is made.
			 */
			add_filter( 'charitable_donation_meta', array( $this, 'save_donation_locale' ) );

			/**
			 * Before sending an email, start filtering the locale based
			 * on the stored locale for the campaign or donation.
			 */
			add_action( 'charitable_before_send_email', array( $this, 'maybe_set_email_locale' ) );
		}

		/**
		 * Add locale donation field.
		 *
		 * @since  1.6.43
		 *
		 * @param  array $fields The default registered fields.
		 * @return array
		 */
		public function add_locale_donation_field( $fields ) {
			$fields['locale'] = array(
				'label'          => __( 'Donor Locale', 'charitable' ),
				'data_type'      => 'meta',
				'donation_form'  => false,
				'admin_form'     => false,
				'show_in_meta'   => true,
				'show_in_export' => true,
				'email_tag'      => false,
			);

			return $fields;
		}

		/**
		 * Add locale campaign field.
		 *
		 * @since  1.6.43
		 *
		 * @param  array $fields The default registered fields.
		 * @return array
		 */
		public function add_locale_campaign_field( $fields ) {
			$fields['locale'] = array(
				'label'          => __( 'Campaign Creator Locale', 'charitable' ),
				'data_type'      => 'meta',
				'campaign_form'  => false,
				'admin_form'     => false,
				'show_in_export' => true,
				'email_tag'      => false,
			);

			return $fields;
		}

		/**
		 * Save the locale that a donor was using when they donated.
		 *
		 * @since  1.6.43
		 *
		 * @param  array $meta_fields The meta fields to be saved.
		 * @return array
		 */
		public function save_donation_locale( $meta_fields ) {
			/**
			 * Filter the locale. By default this will use the result of get_locale().
			 *
			 * @see get_locale
			 *
			 * @since 1.6.45
			 *
			 * @param string $locale The locale.
			 */
			$meta_fields['locale'] = apply_filters( 'charitable_donation_locale', get_locale() );

			return $meta_fields;
		}

		/**
		 * Maybe set a locale for the email.
		 *
		 * @since  1.6.43
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
				switch_to_locale( $this->email_locale );

				add_filter( 'locale', array( $this, 'set_email_locale' ) );
				add_action( 'charitable_after_send_email', array( $this, 'turn_off_email_locale_filter' ) );
			}
		}

		/**
		 * Set the locale to use for an email.
		 *
		 * @since  1.6.43
		 *
		 * @return string
		 */
		public function set_email_locale() {
			return $this->email_locale;
		}

		/**
		 * Turn off the email locale filter.
		 *
		 * @since  1.6.43
		 *
		 * @return void
		 */
		public function turn_off_email_locale_filter() {
			remove_filter( 'locale', array( $this, 'set_email_locale' ) );
			restore_previous_locale();
		}
	}

endif;
