<?php
/**
 * Square core functions.
 *
 * @package   Charitable Square/Functions/Core
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, David Bisset
 * @license   http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether webhooks should be set up.
 *
 * @since  1.3.0
 *
 * @return boolean
 */
function charitable_square_should_setup_webhooks() {
	/**
	 * Filter whether webhooks should be set up for this site.
	 *
	 * By default, this will return true unless this is localhost.
	 *
	 * @since 1.3.0
	 *
	 * @param boolean $should Whether the webhooks should be set up.
	 */
	return apply_filters(
		'charitable_square_setup_webhooks',
		! charitable_is_localhost()
	);
}

// replaces charitable_get_option( 'gateways_square','square_legacy_settings' ) ) {

/**
 * Check if the Square legacy settings are set to true.
 *
 * @since 1.8.7
 *
 * @return boolean
 */
function charitable_square_legacy_settings_check() {
	$settings = charitable_get_option( 'gateways_square' );

	if ( isset( $settings['square_legacy_settings'] ) && $settings['square_legacy_settings'] ) {
		return true;
	}
	return false;
}

/**
 * Retrieve the Square location ID.
 *
 * @since 1.8.7
 *
 * @param string $mode The mode to get the location ID for.
 *
 * @return string
 */
function charitable_square_get_location_id( $mode = 'test' ) {

	// If nothing was passed in, get the current mode.
	if ( empty( $mode ) ) {
		$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
	}

	$location_id = get_option( 'charitable_square_location_id_' . $mode );

	return $location_id;
}

/**
 * Retrieve the Square settings page URL.
 *
 * @since 1.8.7
 *
 * @return string
 */
function charitable_get_square_settings_page_url() {
	return add_query_arg(
		array(
			'page'  => 'charitable-settings',
			'tab'   => 'gateways',
			'group' => 'gateways_square_core',
		),
		admin_url( 'admin.php' )
	);
}

/**
 * Check if Square integration is present. A "dumb" function but easy way for Square to check if new integration is present.
 *
 * @since 1.8.7
 *
 * @return boolean
 */
function charitable_square_connect_integration_check() {
	return true;
}
