<?php
/**
 * Stripe donation functions.
 *
 * @package   Charitable Stripe/Functions/Donations
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, David Bisset
 * @license   http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since     1.3.0
 * @version   1.4.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the charge id for a particular donation.
 *
 * As of version 1.3, the charge for a donation is stored as the
 * gateway transaction key, but prior to this version only renewal
 * donations were stored with the gateway transaction key.
 *
 * However, the charge id has been listed in the donation logs since
 * 1.2.2, so if available we can grab the charge id from there. In
 * this case, we also record the charge as the gateway transaction
 * for future reference.
 *
 * @since  1.3.0
 *
 * @param  int $donation_id The donation ID.
 * @return false|string False if no charge id can be found. Charge id otherwise.
 */
function charitable_stripe_get_charge_id_for_donation( $donation_id ) {
	$donation  = charitable_get_donation( $donation_id );
	$charge_id = $donation->get_gateway_transaction_id();

	if ( $charge_id ) {
		return $charge_id;
	}

	/* Return false by default. */
	$charge_id = false;

	/* Pattern to match in the donation logs. */
	$pattern = '/dashboard\.stripe\.com\/(?:test\/)?payments\/([a-z,A-Z,0-9,_]*)/';

	foreach ( $donation->get_donation_log() as $log ) {
		if ( false !== $charge_id ) {
			continue;
		}

		if ( ! preg_match( $pattern, $log['message'], $matches ) ) {
			continue;
		}

		$charge_id = $matches[1];
	}

	/**
	 * Filter the charge id.
	 *
	 * @since 1.3.0
	 *
	 * @param string|false $charge_id    The charge id, or false if none was stored.
	 * @param int          $donation_id The donation id.
	 */
	$charge_id = apply_filters( 'charitable_stripe_donation_charge_id', $charge_id, $donation_id );

	/* If the charge id is not false, save it. */
	if ( $charge_id ) {
		$donation->set_gateway_transaction_id( $charge_id );
	}

	return $charge_id;
}

/**
 * Return the account id for a particular donation.
 *
 * By default, this will check for the _stripe_account_id meta value. If
 * this is not set, it will return false.
 *
 * However, this uses a filter which allows Stripe Connect to return the
 * account_id linked to a particular donation if that was not set.
 *
 * @since  1.3.0
 *
 * @param  int $donation_id The donation ID.
 * @return false|string False if no account id can be found. Account id otherwise.
 */
function charitable_stripe_get_account_id_for_donation( $donation_id ) {
	$account_id = get_post_meta( $donation_id, '_stripe_account_id', true );

	if ( empty( $account_id ) ) {
		$account_id = false;
	}

	/**
	 * Filter the account id.
	 *
	 * @since 1.3.0
	 *
	 * @param string|false $account_id  The account id, or false if none was stored.
	 * @param int          $donation_id The donation id.
	 */
	$account_id = apply_filters( 'charitable_stripe_donation_account_id', $account_id, $donation_id );

	/* If the account id is set, save it. */
	if ( $account_id ) {
		update_post_meta( $donation_id, '_stripe_account_id', $account_id );
	}

	return $account_id;
}

/**
 * Get the metadata to pass to Stripe for a particular donation.
 *
 * @since  1.4.0
 *
 * @param  Charitable_Donation $donation The donation object.
 * @return array
 */
function charitable_stripe_get_donation_metadata( Charitable_Donation $donation ) {
	/**
	 * Filter the donation fields that are included in the donation metadata.
	 *
	 * @since 1.4.0
	 *
	 * @param array $fields The field keys.
	 */
	$fields = apply_filters(
		'charitable_stripe_metadata_fields',
		array_merge(
			[
				'donation_id',
				'donor',
			],
			array_keys( charitable()->donation_fields()->get_donation_form_fields() )
		)
	);

	return array_reduce(
		$fields,
		function( $carry, $field ) use ( $donation ) {
			$carry[ $field ] = substr( (string) $donation->get( $field ), 0, 500 );
			return $carry;
		},
		[]
	);
}
