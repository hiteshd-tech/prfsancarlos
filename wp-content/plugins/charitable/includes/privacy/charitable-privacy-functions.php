<?php
/**
 * Charitable Privacy Functions.
 *
 * @package   Charitable/Functions/Privacy
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.2
 * @version   1.6.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether the terms and conditions field is active.
 *
 * This returns true when the "terms_conditions" and the "terms_conditions_page"
 * settings are not empty.
 *
 * @since  1.6.2
 *
 * @return boolean
 */
function charitable_is_terms_and_conditions_activated() {
	return 0 !== charitable_get_option( 'terms_conditions_page', 0 )
		&& 0 !== charitable_get_option( 'terms_and_conditions_enabled', 0 )
		&& '' !== charitable_get_option( 'terms_conditions', __( 'I have read and agree to the website [terms].', 'charitable' ) );
}

/**
 * Check whether the privacy policy is active.
 *
 * This returns true when the "privacy_policy" and the "privacy_policy_page"
 * settings are not empty.
 *
 * @since  1.6.2
 * @version 1.8.4.7
 *
 * @return boolean
 */
function charitable_is_privacy_policy_activated() {
	// if no 'privacy_policy_enabled' option has been saved, then go with legacy logic.
	if ( 'not set' === charitable_get_option( 'privacy_policy_page', 'not set' ) ) {
		return 0 !== intval( charitable_get_option( 'privacy_policy_page', 0 ) )
			&& '' !== charitable_get_option( 'privacy_policy', __( 'Your personal data will be used to process your donation, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'charitable' ) );
	} else {
		return 0 !== intval( charitable_get_option( 'privacy_policy_page', 0 ) )
			&& '' !== charitable_get_option( 'privacy_policy', __( 'Your personal data will be used to process your donation, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'charitable' ) )
			&& charitable_get_option( 'privacy_policy_enabled', 0 );
	}
}

/**
 * Check whether the contact consent is active.
 *
 * This returns true when the "contact_consent" and the "contact_consent_label"
 * fields are not empty, and when the upgrade_donor_tables upgrade routine has
 * been run.
 *
 * @since  1.6.2
 *
 * @return boolean
 */
function charitable_is_contact_consent_activated() {
	return 0 != charitable_get_option( 'contact_consent', 0 ) // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
		&& '' != charitable_get_option( 'contact_consent_label', __( 'Yes, I am happy for you to contact me via email or phone.', 'charitable' ) ) // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
		&& Charitable_Upgrade::get_instance()->upgrade_has_been_completed( 'upgrade_donor_tables' );
}

/**
 * Returns the full text of the Terms and Conditions.
 *
 * @since 1.6.2
 * @since 1.8.0
 * @since 1.8.1.10 Added the `charitable_get_terms_and_conditions_content_after` filter and shortcode check.
 *
 * @return string
 */
function charitable_get_terms_and_conditions() {
	$endpoints = charitable()->endpoints();

	remove_filter( 'the_content', array( $endpoints, 'get_content' ) );

	$custom_content = apply_filters( 'charitable_get_terms_and_conditions_content', false );

	if ( $custom_content === false ) {
		$terms_page_content = get_post_field( 'post_content', charitable_get_option( 'terms_conditions_page', 0 ), 'display' );

		// if this content contains the shortcode 'charitable_donation_form', stripe this shortcode from the content/text.
		$custom_content = ( has_shortcode( $terms_page_content, 'charitable_donation_form' ) ) ? '' : $terms_page_content;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- the_content is a WordPress core filter hook.
		$custom_content = apply_filters( 'the_content', $custom_content );
	}

	$content = apply_filters( 'charitable_get_terms_and_conditions_content_after', $custom_content );

	add_filter( 'the_content', array( $endpoints, 'get_content' ) );

	return $content;
}

/**
 * Returns the checkbox label for the Terms and Conditions field.
 *
 * @since  1.6.2
 * @since  1.8.6.2 - added additional sanitization
 *
 * @return string
 */
function charitable_get_terms_and_conditions_field_label() {
	$url = get_the_permalink( charitable_get_option( 'terms_conditions_page', 0 ) );

	if ( ! $url ) {
		return '';
	}

	$text = charitable_get_option( 'terms_conditions', __( 'I have read and agree to the website [terms].', 'charitable' ) );
	// Sanitize and remove XSS.
	$text = wp_kses_post( $text );

	$replace = sprintf(
		'<a href="%s" target="_blank" class="charitable-terms-link">%s</a>',
		$url,
		__( 'terms and conditions', 'charitable' )
	);

	return str_replace( '[terms]', $replace, $text );
}

/**
 * Returns the Privacy Policy text.
 *
 * @since  1.6.2
 * @since  1.8.6.2 - added additional sanitization
 *
 * @return string
 */
function charitable_get_privacy_policy_field_text() {
	$url = get_the_permalink( charitable_get_option( 'privacy_policy_page', 0 ) );

	if ( ! $url ) {
		return '';
	}

	$text = charitable_get_option( 'privacy_policy', __( 'Your personal data will be used to process your donation, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'charitable' ) );
	// Sanitize and remove XSS.
	$text = wp_kses_post( $text );

	$replace = sprintf(
		'<a href="%s" target="_blank" class="charitable-privacy-policy-link">%s</a>',
		$url,
		__( 'privacy policy', 'charitable' )
	);

	return str_replace( '[privacy_policy]', $replace, $text );
}
