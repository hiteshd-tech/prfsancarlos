<?php
/**
 * Charitable General Settings UI.
 *
 * @package   Charitable/Classes/Charitable_General_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.38
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_General_Settings' ) ) :

	/**
	 * Charitable_General_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_General_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_General_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_General_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the general tab settings fields.
		 *
		 * @since   1.0.0
		 *
		 * @param   array[] $fields
		 * @return  array
		 */
		public function add_general_fields( $fields = array() ) {
			if ( ! charitable_is_settings_view( 'general' ) ) {
				return array();
			}

			$currency_helper = charitable_get_currency_helper();

			$general_fields = array(
				'section'                                => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 10000,
					'value'    => 'general',
				),
				'general_settings'   => array(
					'title'    => __( 'General Settings', 'charitable' ),
					'type'     => 'heading',
					'priority' => 1,
				),
				'section_license'                        => array(
					'title'    => __( 'License', 'charitable' ),
					'type'     => 'heading',
					'priority' => 2,
					'class'    => 'section-heading general-settings',
				),
				'license_key'                            => array(
					'title'    => __( 'License Key', 'charitable' ),
					'type'     => 'content',
					'class'    => 'general-settings',
					'content'  => Charitable_Licenses_Settings::get_instance()->generate_license_check_html(),
					'priority' => 3,
					'help'     => __( 'Your license key provides access to updates and addons.', 'charitable' ),
				),
				'section_locale'                         => array(
					'title'    => __( 'Currency & Location', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'section-heading general-setting currrency-location',
					'priority' => 4,
				),
				'country'                                => array(
					'title'    => __( 'Base Country', 'charitable' ),
					'type'     => 'select',
					'priority' => 8,
					'default'  => 'US',
					'class'    => 'general-setting',
					'options'  => charitable_get_location_helper()->get_countries(),
				),
				'currency'                               => array(
					'title'    => __( 'Currency', 'charitable' ),
					'type'     => 'select',
					'priority' => 10,
					'default'  => 'USD',
					'class'    => 'general-settings',
					'options'  => charitable_get_currency_helper()->get_all_currencies(),
				),
				'currency_format'                        => array(
					'title'    => __( 'Currency Format', 'charitable' ),
					'type'     => 'select',
					'priority' => 12,
					'default'  => 'left',
					'class'    => 'general-settings',
					'options'  => array(
						'left'             => $currency_helper->get_monetary_amount( '23', false, false, 'left' ),
						'right'            => $currency_helper->get_monetary_amount( '23', false, false, 'right' ),
						'left-with-space'  => $currency_helper->get_monetary_amount( '23', false, false, 'left-with-space' ),
						'right-with-space' => $currency_helper->get_monetary_amount( '23', false, false, 'right-with-space' ),
					),
				),
				'decimal_separator'                      => array(
					'title'    => __( 'Decimal Separator', 'charitable' ),
					'type'     => 'select',
					'priority' => 14,
					'default'  => '.',
					'class'    => 'general-settings',
					'options'  => array(
						'.' => 'Period (12.50)',
						',' => 'Comma (12,50)',
					),
				),
				'thousands_separator'                    => array(
					'title'    => __( 'Thousands Separator', 'charitable' ),
					'type'     => 'select',
					'priority' => 16,
					'default'  => ',',
					'class'    => 'general-settings',
					'options'  => array(
						','    => __( 'Comma (10,000)', 'charitable' ),
						'.'    => __( 'Period (10.000)', 'charitable' ),
						' '    => __( 'Space (10 000)', 'charitable' ),
						'none' => __( 'None', 'charitable' ),
					),
				),
				'decimal_count'                          => array(
					'title'    => __( 'Number of Decimals', 'charitable' ),
					'type'     => 'number',
					'priority' => 18,
					'class'    => 'general-settings short',
					'default'  => 2,
				),
				'section_donation_form'                  => array(
					'title'    => __( 'Donation Form', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'section-heading general-settings',
					'priority' => 20,
				),
				'donation_form_display'                  => array(
					'title'    => __( 'Display Options', 'charitable' ),
					'type'     => 'select',
					'priority' => 22,
					'default'  => 'modal',
					'class'    => 'general-settings',
					'options'  => array(
						'separate_page' => __( 'Show on a Separate Page', 'charitable' ),
						'same_page'     => __( 'Show on the Same Page', 'charitable' ),
						'modal'         => __( 'Reveal in a Modal', 'charitable' ),
					),
					'help'     => __( 'Choose how you want a campaign\'s donation form to show.', 'charitable' ),
				),
				'donation_form_template'                 => array(
					'title'    => __( 'Form Template <span class="badge beta">Beta</span>', 'charitable' ),
					'type'     => 'select',
					'priority' => 23,
					'default'  => '',
					'class'    => 'general-settings',
					'options'  => array(
						''        => __( 'Standard', 'charitable' ),
						'minimal' => __( 'Minimal', 'charitable' ),
					),
					'help'     => __( 'The minimal template has a more compact and simplified layout. It is most effective with "only show required fields" set to "Yes".', 'charitable' ),
				),
				'donation_form_minimal_fields'           => array(
					'title'    => __( 'Only Show Required Fields', 'charitable' ),
					'type'     => 'radio',
					'priority' => 24,
					'class'    => 'general-settings',
					'default'  => '0',
					'options'  => array(
						'1' => __( 'Yes', 'charitable' ),
						'0' => __( 'No', 'charitable' ),
					),
					'help'     => __( 'Choose if you wish fields not required on the donation form to be hidden.', 'charitable' ),
				),
				'donation_form_show_login_message'       => array(
					'title'    => __( 'Show Login Form <span class="badge beta">Beta</span>', 'charitable' ),
					'type'     => 'radio',
					'priority' => 25,
					'class'    => 'general-settings',
					'default'  => '1',
					'options'  => array(
						'1' => __( 'Yes', 'charitable' ),
						'0' => __( 'No', 'charitable' ),
					),
					'help'     => __( 'Choose if you wish to display a register reminder or login form at the top of the donation form.', 'charitable' ),
				),
				'donation_form_minimal_amount_notice_display' => array(
					'title'    => __( 'Minimum Donation Location', 'charitable' ),
					'type'     => 'select',
					'priority' => 26,
					'class'    => 'general-settings',
					'default'  => 'below_amount_selection',
					'options'  => array(
						'below_amount_selection' => __( 'Below Donation Choices / Amounts', 'charitable' ),
						'below_donation_title'   => __( 'Below Donation Title', 'charitable' ),
						'above_donation_title'   => __( 'Above Donation Title', 'charitable' ),
					),
					'help'     => __( 'Choose where you wish the minimum donation message to appear on the donation form, if a minimum is set for the campaign.', 'charitable' ),
				),
				'donation_form_notices_display'          => array(
					'title'    => __( 'Notice Display Location', 'charitable' ),
					'type'     => 'select',
					'priority' => 27,
					'default'  => 'top',
					'class'    => 'general-settings',
					'options'  => array(
						'top'    => __( 'Top', 'charitable' ),
						'bottom' => __( 'Bottom', 'charitable' ),
					),
					'help'     => __( 'Choose where notice and validation errors appear on the donation form.', 'charitable' ),
				),
				'donation_form_default_highlight_colour' => array(
					'title'    => __( 'Default Highlight Color', 'charitable' ),
					'type'     => 'color-picker',
					'priority' => 28,
					'default'  => false,
					'class'    => 'general-settings charitable-color-field',
					'help'     => __( 'Define a highlight color for form notices, errors, and other UI. Default is ', 'charitable' ) . apply_filters( 'charitable_default_highlight_colour', '#f89d35' ) . '. Templates built in Campaign Builder or CSS in your theme or plugins might override this.',
				),
				'section_pages'                          => array(
					'title'    => __( 'Pages', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'section-heading general-settings',
					'priority' => 30,
				),
				'login_page'                             => array(
					'title'    => __( 'Login Page', 'charitable' ),
					'type'     => 'select',
					'class'    => 'general-settings',
					'priority' => 31,
					'default'  => 'wp',
					'options'  => array(
						'wp'    => __( 'Use WordPress Login', 'charitable' ),
						'pages' => array(
							'options' => charitable_get_admin_settings()->get_pages(),
							'label'   => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'     => __( 'Allow users to login via the normal WordPress login page or via a static page. The static page should contain the <code>[charitable_login]</code> shortcode.', 'charitable' ),
				),
				'registration_page'                      => array(
					'title'    => __( 'Registration Page', 'charitable' ),
					'type'     => 'select',
					'priority' => 32,
					'default'  => 'wp',
					'class'    => 'general-settings',
					'options'  => array(
						'wp'    => __( 'Use WordPress Registration Page', 'charitable' ),
						'pages' => array(
							'options' => charitable_get_admin_settings()->get_pages(),
							'label'   => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'     => __( 'Allow users to register via the default WordPress login or via a static page. The static page should contain the <code>[charitable_registration]</code> shortcode.', 'charitable' ),
				),
				'profile_page'                           => array(
					'title'    => __( 'Profile Page', 'charitable' ),
					'type'     => 'select',
					'priority' => 33,
					'class'    => 'general-settings',
					'options'  => charitable_get_admin_settings()->get_pages(),
					'help'     => __( 'The static page should contain the <code>[charitable_profile]</code> shortcode.', 'charitable' ),
				),
				'donation_receipt_page'                  => array(
					'title'    => __( 'Donation Receipt Page', 'charitable' ),
					'type'     => 'select',
					'priority' => 34,
					'default'  => 'auto',
					'class'    => 'general-settings',
					'options'  => array(
						'auto'  => __( 'Automatic', 'charitable' ),
						'pages' => array(
							'options' => charitable_get_admin_settings()->get_pages(),
							'label'   => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'     => __( 'Choose the page that users will be redirected to after donating. Leave it set to automatic to use the built-in Charitable receipt. If you choose a static page, it should contain the <code>[donation_receipt]</code> shortcode.', 'charitable' ),
				),
				'privacy_policy_page'                             => array(
					'title'    => __( 'Privacy Policy Page', 'charitable' ),
					'type'     => 'select',
					'class'    => 'general-settings',
					'priority' => 40,
					'default'  => '0',
					'options'  => array(
						'0'  => __( 'No Privacy Page', 'charitable' ),
						'pages' => array(
							'options' => charitable_get_admin_settings()->get_pages(),
							'label'   => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'     => __( 'Choose the page that contains your site\'s privacy policy.', 'charitable' ),
				),
				'terms_conditions_page'                  => array(
					'title'    => __( 'Terms and Conditions Page', 'charitable' ),
					'type'     => 'select',
					'priority' => 45,
					'default'  => '0',
					'class'    => 'general-settings',
					'options'  => array(
						'0'  => __( 'No Terms Page', 'charitable' ),
						'pages' => array(
							'options' => charitable_get_admin_settings()->get_pages(),
							'label'   => __( 'Choose a Static Page', 'charitable' ),
						),
					),
					'help'     => __( 'Choose the page that contains your site\'s terms and conditions.', 'charitable' ),
				),
			);

			/* If we're using a zero-decimal currency, get rid of the decimal separator and decimal number fields */
			if ( $currency_helper->is_zero_decimal_currency() ) {
				unset(
					$general_fields['decimal_separator'],
					$general_fields['decimal_count']
				);
			}

			$fields = array_merge( $fields, $general_fields );

			return $fields;
		}
	}

endif;
