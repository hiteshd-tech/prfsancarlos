<?php
/**
 * General functions made for the campaign builder interacts with legacy campaigns.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 * @version   1.8.1.14
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'charitable_campaign_save', 'charitable_legacy_campaign_save', 10, 1 );

/**
 * Do something with the posted data.
 *
 * @since 1.8.1.12
 * @version 1.8.9.1
 *
 * @param WP_Post $post An instance of `WP_Post`.
 */
function charitable_legacy_campaign_save( $post ) {

	// check and see if $post is an instance of 'WP_Post'.
	if ( empty( $post ) || ! $post instanceof WP_Post ) {
		return;
	}

	if ( defined( 'CHARITABLE_DISABLE_LEGACY_SYNC_TO_BUILDER' ) && CHARITABLE_DISABLE_LEGACY_SYNC_TO_BUILDER ) { // phpcs:ignore
		return;
	}

	$campaign_id = ! empty( $post->ID ) ? $post->ID : 0;

	if ( $campaign_id <= 0 ) {
		return;
	}

	// Fetch campaign details.
	$campaign_settings_v2 = (array) get_post_meta( $campaign_id, 'campaign_settings_v2', true );

	// Clean house.
	$campaign_settings_v2 = array_filter( $campaign_settings_v2 );

	// If the campaign settings are empty, then this was never created in the new builder.
	if ( empty( $campaign_settings_v2 ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// --- Campaign Goal.
	$_campaign_goal = ! empty( $_POST['_campaign_goal'] ) ? sanitize_text_field( wp_unslash( $_POST['_campaign_goal'] ) ) : false;

	// Sanitize some misc fields.
	$campaign_settings_v2['settings']['general']['goal'] = ! empty( $_campaign_goal ) ? Charitable_Currency::get_instance()->sanitize_monetary_amount( (string) $_campaign_goal ) : false;
		$campaign_settings_v2['settings']['general']['goal'] = '0' === $campaign_settings_v2['settings']['general']['goal'] ? '' : $campaign_settings_v2['settings']['general']['goal'];

	// if the money values are negative, make it positive.
	if ( ! empty( $campaign_settings_v2['settings']['general']['goal'] ) && '-' === substr( $campaign_settings_v2['settings']['general']['goal'], 0, 1 ) ) {
		$campaign_settings_v2['settings']['general']['goal'] = substr( $campaign_settings_v2['settings']['general']['goal'], 1 );
	}
	if ( ! empty( $campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'] ) && '-' === substr( $campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'], 0, 1 ) ) {
		$campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'] = substr( $campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'], 1 );
	}

	// --- End Date.
	$end_date_raw = ! empty( $_POST['_campaign_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_campaign_end_date'] ) ) : '';
	$end_time_raw = isset( $_POST['_campaign_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['_campaign_end_time'] ) ) : '';
	$_end_date = ! empty( $end_date_raw ) ? $end_date_raw . ' ' . $end_time_raw : '';

		$campaign_settings_v2['settings']['general']['end_date'] = Charitable_Campaign::sanitize_campaign_end_date( $_end_date );

	// --- Title.
	$campaign_settings_v2['title'] = ! empty( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : false;

	// --- Description.
	$campaign_settings_v2['settings']['general']['description'] = ! empty( $_POST['post_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['post_content'] ) ) : false;
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	update_post_meta( $campaign_id, 'campaign_settings_v2', $campaign_settings_v2 );
}
