<?php
/**
 * Stripe core functions.
 *
 * @package   Charitable Stripe/Functions/Core
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, David Bisset
 * @license   http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since     1.3.0
 * @version   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the instance of `Charitable_Stripe`.
 *
 * @since  1.3.0
 *
 * @return Charitable_Stripe
 */
function charitable_stripe() {
	return Charitable::get_instance();
}

/**
 * This returns the Charitable_Stripe_Deprecated object.
 *
 * @since  1.4.0
 *
 * @return Charitable_Stripe_Deprecated
 */
function charitable_stripe_deprecated() {
	return Charitable_Stripe_Deprecated::get_instance();
}

/**
 * Check whether webhooks should be set up.
 *
 * @since  1.3.0
 *
 * @return boolean
 */
function charitable_stripe_should_setup_webhooks() {
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
		'charitable_stripe_setup_webhooks',
		! charitable_is_localhost()
	);
}
