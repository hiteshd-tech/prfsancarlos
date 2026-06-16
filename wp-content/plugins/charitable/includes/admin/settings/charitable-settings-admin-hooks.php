<?php
/**
 * Charitable Settings Hooks.
 *
 * Action/filter hooks used for Charitable Settings API.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.2.0
 * @version   1.8.4   - added user tracking option.
 * @version   1.8.6.2 - added sanitization functions for privacy policy, terms and conditions, and contact consent label fields.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable settings.
 *
 * @see Charitable_Settings::register_settings()
 */
add_action( 'admin_init', array( Charitable_Settings::get_instance(), 'register_settings' ) );

/**
 * Maybe add "Licenses" settings tab.
 *
 * @see Charitable_Settings::maybe_add_extensions_tab()
 */
add_action( 'charitable_settings_tabs', array( Charitable_Licenses_Settings::get_instance(), 'maybe_add_licenses_tab' ), 1 );

/**
 * Maybe add "Extensions" settings tab.
 *
 * @see Charitable_Settings::maybe_add_extensions_tab()
 */
add_action( 'charitable_settings_tabs', array( Charitable_Settings::get_instance(), 'maybe_add_extensions_tab' ), 2 );

/**
 * Add a hidden "extensions" field.
 *
 * @see Charitable_Settings::add_hidden_extensions_setting_field()
 */
add_filter( 'charitable_settings_tab_fields', array( Charitable_Settings::get_instance(), 'add_hidden_extensions_setting_field' ) );

/**
 * Save the license when saving settings.
 *
 * @see Charitable_Licenses_Settings::save_license()
 */
add_filter( 'charitable_save_settings', array( Charitable_Licenses_Settings::get_instance(), 'save_license' ), 10, 2 );

/**
 * Add dynamic settings groups.
 *
 * @see Charitable_Gateway_Settings::add_gateway_settings_dynamic_groups()
 * @see Charitable_Email_Settings::add_email_settings_dynamic_groups()
 * @see Charitable_Email_Settings::add_licenses_group()
 */
add_filter( 'charitable_dynamic_groups', array( Charitable_Gateway_Settings::get_instance(), 'add_gateway_settings_dynamic_groups' ) );
add_filter( 'charitable_dynamic_groups', array( Charitable_Email_Settings::get_instance(), 'add_individual_email_fields' ) );
add_filter( 'charitable_dynamic_groups', array( Charitable_Licenses_Settings::get_instance(), 'add_licenses_group' ) );

/**
 * Add settings to the General tab.
 *
 * @see Charitable_General_Settings::add_general_fields()
 */
add_filter( 'charitable_settings_tab_fields_general', array( Charitable_General_Settings::get_instance(), 'add_general_fields' ), 5 );

/**
 * Add settings to the Payment Gateways tab.
 *
 * @see Charitable_Gateway_Settings::add_gateway_fields()
 */
add_filter( 'charitable_settings_tab_fields_gateways', array( Charitable_Gateway_Settings::get_instance(), 'add_gateway_fields' ), 5 );

/**
 * Add settings to the Email tab.
 *
 * @see Charitable_Email_Settings::add_email_fields()
 */
add_filter( 'charitable_settings_tab_fields_emails', array( Charitable_Email_Settings::get_instance(), 'add_email_fields' ), 5 );

/**
 * Add settings for the Licenses tab.
 *
 * @see Charitable_Licenses_Settings::add_licenses_fields()
 */
add_filter( 'charitable_settings_tab_fields_licenses', array( Charitable_Licenses_Settings::get_instance(), 'add_licenses_fields' ), 5 );

/**
 * Add extra button for the Licenses tab.
 *
 * @see Charitable_Licenses_Settings::add_license_recheck_button()
 */
add_filter( 'charitable_settings_button_licenses', array( Charitable_Licenses_Settings::get_instance(), 'add_license_recheck_button' ) );
add_filter( 'charitable_settings_button_advanced', array( Charitable_Licenses_Settings::get_instance(), 'add_license_recheck_button' ) );

/**
 * Add settings to the Privacy tab.
 *
 * @see Charitable_Privacy_Settings::add_privacy_fields()
 */
add_filter( 'charitable_settings_tab_fields_privacy', array( Charitable_Privacy_Settings::get_instance(), 'add_privacy_fields' ), 5 );

/**
 * Add the Pro settings CTA.
 *
 * @see Charitable_Settings::show_settings_cta()
 */
add_action( 'charitable_pro_settings_cta', array( Charitable_Settings::get_instance(), 'show_settings_cta' ), 10, 1 );


/**
 * Add security settings group.
 *
 * This ensures the 'security' group is recognized by the settings API.
 * Settings will be added via the charitable_settings_tab_fields_security filter.
 *
 * @since 1.8.0
 */
add_filter( 'charitable_security_admin_settings_groups', function( $groups ) {
	if ( ! is_array( $groups ) ) {
		$groups = array();
	}
	if ( ! in_array( 'security', $groups, true ) ) {
		$groups[] = 'security';
	}
	return $groups;
} );

/**
 * Add settings to the Advanced tab.
 *
 * @see Charitable_Advanced_Settings::add_advanced_fields()
 */
add_filter( 'charitable_settings_tab_fields_advanced', array( Charitable_Advanced_Settings::get_instance(), 'add_advanced_fields' ), 5 );

/**
 * Preform any tasks when the advanced tab is saved.
 *
 * @see Charitable_Advanced_Settings::save_license()
 */
add_filter( 'charitable_save_settings', array( Charitable_Advanced_Settings::get_instance(), 'clear_expired_options' ), 10, 2 );
add_filter( 'charitable_save_settings', array( Charitable_Advanced_Settings::get_instance(), 'clear_activity_database' ), 10, 2 );
add_filter( 'charitable_save_settings', array( Charitable_Advanced_Settings::get_instance(), 'minification_settings' ), 10, 2 );
add_filter( 'charitable_save_settings', array( Charitable_Checklist::get_instance(), 'confirm_general_settings' ), 10, 2 );
add_filter( 'charitable_save_settings', array( Charitable_Checklist::get_instance(), 'confirm_email_settings' ), 10, 2 );
add_filter( 'charitable_save_settings', array( Charitable_Advanced_Settings::get_instance(), 'update_user_tracking_option' ), 10, 2 );
add_filter( 'admin_init', array( Charitable_Checklist::get_instance(), 'confirm_email_changes' ), 10 );

/**
 * Add extra settings for the individual gateways & emails tabs.
 *
 * @see Charitable_Gateway_Settings::add_individual_gateway_fields()
 * @see Charitable_Email_Settings::add_individual_email_fields()
 */
add_filter( 'charitable_settings_tab_fields', array( Charitable_Gateway_Settings::get_instance(), 'add_individual_gateway_fields' ), 5 );
add_filter( 'charitable_settings_tab_fields', array( Charitable_Email_Settings::get_instance(), 'add_individual_email_fields' ), 5 );

/**
 * Add Addons Directory
 *
 * @see Charitable_Advanced_Settings::add_advanced_fields()
 */
add_action( 'admin_init', array( Charitable_Addons_Directory::get_instance(), 'init' ), 5 );

/**
 * Checklist.
 *
 * @see Charitable_Checklist::maybe_redirect_to_checklist_after_event()
 */
add_action( 'charitable_save_settings', array( Charitable_Checklist::get_instance(), 'maybe_redirect_to_checklist_after_event' ), 99, 2 );
add_filter( 'admin_body_class', array( Charitable_Checklist::get_instance(), 'add_body_class' ), 10 );
add_filter( 'charitable_submenu_pages', array( Charitable_Checklist::get_instance(), 'add_checklist_to_menu' ), 2 );
add_action( 'admin_init', array( Charitable_Checklist::get_instance(), 'maybe_complete_checklist' ), 10 );

/**
 * Maybe activate the Pro plugin after onboarding.
 *
 * @see Charitable_Checklist::maybe_activate_pro_after_onboarding()
 */
add_action( 'admin_init', array( Charitable_Checklist::get_instance(), 'maybe_activate_pro_after_onboarding' ), 10 );

/**
 * Hook the sanitization function for the privacy_policy field.
 *
 * @see Charitable_Privacy_Settings::sanitize_privacy_policy_field()
 */
add_filter( 'charitable_sanitize_value_privacy_privacy_policy', array( Charitable_Privacy_Settings::get_instance(), 'sanitize_privacy_policy_field' ), 10, 3 );

/**
 * Hook the sanitization function for the terms_conditions field.
 *
 * @see Charitable_Privacy_Settings::sanitize_terms_conditions_field()
 */
add_filter( 'charitable_sanitize_value_privacy_terms_conditions', array( Charitable_Privacy_Settings::get_instance(), 'sanitize_terms_conditions_field' ), 10, 3 );

/**
 * Hook the sanitization function for the contact_consent_label field.
 *
 * @see Charitable_Privacy_Settings::sanitize_contact_consent_label_field()
 */
add_filter( 'charitable_sanitize_value_privacy_contact_consent_label', array( Charitable_Privacy_Settings::get_instance(), 'sanitize_contact_consent_label_field' ), 10, 3 );
