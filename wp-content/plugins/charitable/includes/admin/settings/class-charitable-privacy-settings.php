<?php
/**
 * Charitable Privacy Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Privacy_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.8.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Privacy_Settings' ) ) :

	/**
	 * Charitable_Privacy_Settings
	 *
	 * @since 1.6.0
	 */
	final class Charitable_Privacy_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.6.0
		 *
		 * @var    Charitable_Privacy_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.6.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.6.0
		 *
		 * @return  Charitable_Privacy_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the privacy tab settings fields.
		 *
		 * @since   1.6.0
		 *
		 * @return  array<string,array>
		 */
		public function add_privacy_fields() {
			if ( ! charitable_is_settings_view( 'privacy' ) ) {
				return array();
			}

			$data_fields = $this->get_user_donation_field_options();

			return array(
				'section'                       => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 10000,
					'value'    => 'privacy',
				),
				'section_privacy'               => array(
					'title'    => __( 'Privacy', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'section-heading',
					'priority' => 10,
				),
				'section_privacy_description'   => array(
					'type'     => 'content',
					'priority' => 20,
					'content'  => '<div class="charitable-settings-notice">'
								. '<p>' . __( 'Charitable stores personal data such as donors\' names, email addresses, addresses and phone numbers in your database. Donors may request to have their personal data erased, but you may be legally required to retain some personal data for donations made within a certain time. Below you can control how long personal data is retained for at a minimum, as well as which data fields must be retained.', 'charitable' ) . '</p>'
								. '<p><a href="https://wpcharitable.com/documentation/charitable-user-privacy/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Settings+Privacy+Tab+Page&utm_content=Read+More+About+Privacy">' . __( 'Read more about Charitable & user privacy', 'charitable' ) . '</a></p>'
								. '</div>',
				),
				'minimum_data_retention_period' => array(
					'label_for' => __( 'Minimum Data Retention Period', 'charitable' ),
					'type'      => 'select',
					'help'      => sprintf(
						/* translators: %1$s: HTML strong tag. %2$s: HTML closing strong tag. %1$s: HTML break tag. */
						__( 'Prevent personal data from being erased for donations made within a certain amount of time.%3$sChoose %1$sNone%2$s to allow the personal data of any donation to be erased.%3$sChoose %1$sForever%2$s to prevent any personal data from being erased from donations, regardless of how long ago they were made.', 'charitable' ),
						'<strong>',
						'</strong>',
						'<br />'
					),
					'priority'  => 25,
					'default'   => 2,
					'options'   => array(
						0         => __( 'None', 'charitable' ),
						1         => __( 'One year', 'charitable' ),
						2         => __( 'Two years', 'charitable' ),
						3         => __( 'Three years', 'charitable' ),
						4         => __( 'Four years', 'charitable' ),
						5         => __( 'Five years', 'charitable' ),
						6         => __( 'Six years', 'charitable' ),
						7         => __( 'Seven years', 'charitable' ),
						8         => __( 'Eight years', 'charitable' ),
						9         => __( 'Nine years', 'charitable' ),
						10        => __( 'Ten years', 'charitable' ),
						'endless' => __( 'Forever', 'charitable' ),
					),
				),
				'data_retention_fields'         => array(
					'label_for' => __( 'Retained Data', 'charitable' ),
					'type'      => 'multi-checkbox',
					'priority'  => 30,
					'default'   => array_keys( $data_fields ),
					'options'   => $data_fields,
					'help'      => __( 'The checked fields will not be erased fields when personal data is erased for a donation made within the Minimum Data Retention Period.', 'charitable' ),
					'attrs'     => array(
						'data-trigger-key'   => '#charitable_settings_minimum_data_retention_period',
						'data-trigger-value' => '!0',
					),
				),
				'contact_consent'               => array(
					'title'    => __( 'Contact Consent Field', 'charitable' ),
					'type'     => 'checkbox',
					'priority' => 32,
					'default'  => '0',
					'help'     => __( 'Display a checkbox asking people for their consent to being contacted when they donate, register an account, or manage their profile.', 'charitable' ),
				),
				'contact_consent_required'      => array(
					'label_for' => __( 'Make Consent Field Required For Donation Forms', 'charitable' ),
					'type'      => 'checkbox',
					'help'      => __( 'Requires the user acknowledge consent before submitting a donation, which might be required for some countries or situations.', 'charitable' ),
					'priority'  => 34,
					'default'   => false,
				),
				'contact_consent_label'         => array(
					'title'    => __( 'Contact Consent Label', 'charitable' ),
					'type'     => 'textarea',
					'priority' => 36,
					'default'  => __( 'Yes, I am happy for you to contact me via email or phone.', 'charitable' ),
					'help'     => __( 'A short statement describing how you would like to contact people.', 'charitable' ),
				),
				'privacy_policy_enabled'        => array(
					'title'    => __( 'Privacy Policy Field', 'charitable' ),
					'type'     => 'checkbox',
					'priority' => 40,
					'default'  => charitable_get_option( 'privacy_policy_page', 0 ) ? true : false,
					'help'     => __( 'Display a field showing the privacy policy (must have a policy page set and terms added below).', 'charitable' ),
				),
				'privacy_policy'                => array(
					'title'    => __( 'Privacy Policy', 'charitable' ),
					'type'     => 'textarea',
					'priority' => 45,
					'default'  => __( 'Your personal data will be used to process your donation, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'charitable' ),
					'help'     => sprintf(
						/* translators: %1$s: HTML code tag. %2$s: HTML closing code tag. %3$s: HTML anchor tag. %4$s: HTML closing anchor tag. */
						__( 'Privacy policy information to be seen when individuals donate, register an account, or manage their profile. Use <code>[privacy_policy]</code> to link to the page assigned in <a href="%s">general settings</a>.', 'charitable' ),
						esc_url( admin_url( 'admin.php?page=charitable-settings&tab=general' ) )
					),
				),
				'terms_and_conditions_enabled'  => array(
					'title'    => __( 'Terms And Conditions Field', 'charitable' ),
					'type'     => 'checkbox',
					'priority' => 50,
					'default'  => charitable_get_option( 'terms_conditions_page', 0 ) ? true : false,
					'help'     => __( 'Display a field showing the terms and conditions (must have a terms page set and terms added below).', 'charitable' ),
				),
				'terms_conditions'              => array(
					'title'    => __( 'Terms And Conditions', 'charitable' ),
					'type'     => 'textarea',
					'priority' => 55,
					'default'  => __( 'I have read and agree to the website [terms].', 'charitable' ),
					'help'     => sprintf(
						/* translators: %1$s: HTML code tag. %2$s: HTML closing code tag. %3$s: HTML anchor tag. %4$s: HTML closing anchor tag. */
						__( 'Terms and conditions statement to display on donation forms. Use <code>[terms]</code> to link to the page assigned in <a href="%s">general settings</a>.', 'charitable' ),
						esc_url( admin_url( 'admin.php?page=charitable-settings&tab=general' ) )
					),
				),
			);
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.6.0
		 *
		 * @return string[]
		 */
		protected function get_user_donation_field_options() {
			$fields = charitable()->donation_fields()->get_data_type_fields( 'user' );

			return array_combine(
				array_keys( $fields ),
				wp_list_pluck( $fields, 'label' )
			);
		}

		/**
		 * Sanitize the privacy policy field to prevent XSS attacks.
		 *
		 * @since  1.8.6.2
		 *
		 * @param  mixed $value     The submitted value.
		 * @param  array $field     The field configuration.
		 * @param  array $submitted All submitted data.
		 * @return string
		 */
		public function sanitize_privacy_policy_field( $value, $field, $submitted ) { // phpcs:ignore
			return wp_kses_post( $value );
		}

		/**
		 * Sanitize the terms and conditions field to prevent XSS attacks.
		 *
		 * @since  1.8.6.2
		 *
		 * @param  mixed $value     The submitted value.
		 * @param  array $field     The field configuration.
		 * @param  array $submitted All submitted data.
		 * @return string
		 */
		public function sanitize_terms_conditions_field( $value, $field, $submitted ) { // phpcs:ignore
			return wp_kses_post( $value );
		}

		/**
		 * Sanitize the contact consent label field to prevent XSS attacks.
		 *
		 * @since  1.8.6.2
		 *
		 * @param  mixed $value     The submitted value.
		 * @param  array $field     The field configuration.
		 * @param  array $submitted All submitted data.
		 * @return string
		 */
		public function sanitize_contact_consent_label_field( $value, $field, $submitted ) { // phpcs:ignore
			return wp_kses_post( $value );
		}
	}

endif;
