<?php
/**
 * Stripe Connect helper functions.
 *
 * @package   Charitable Stripe/Functions/Connect
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, David Bisset
 * @license   http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since     1.4.0
 * @version   1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the connected Stripe account id for a campaign.
 *
 * @since  1.4.0
 *
 * @param  int $campaign_id The campaign id.
 * @return string|false Returns account id if one is connected and Charitable
 *                      Stripe Connect is active. Otherwise, returns false.
 */
function charitable_stripe_get_connected_account_for_campaign( $campaign_id ) {
	if ( ! class_exists( 'Charitable_Stripe_Connect' ) ) {
		return false;
	}

	$connected_user_id = get_post_meta( $campaign_id, '_campaign_stripe_connect_user_id', true );

	if ( ! $connected_user_id ) {
		return false;
	}

	$stripe_user       = new Charitable_Stripe_Connect_User( $connected_user_id );
	$connected_account = $stripe_user->get( 'stripe_user_id' );

	/* We don't have an access token for the user, so we're just going to send the funds to the platform. */
	if ( ! $connected_account || ! $stripe_user->is_token_valid_for_current_mode() ) {
		return false;
	}

	return $connected_account;
}