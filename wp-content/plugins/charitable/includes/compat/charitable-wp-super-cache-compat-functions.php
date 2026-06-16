<?php
/**
 * Functions to improve compatibility with WP Super Cache.
 *
 * @package   Charitable/Functions/Compatibility
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.18
 * @version   1.6.46
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clear the campaign page cache after a donation is received.
 *
 * @since  1.4.18
 *
 * @param  int $campaign_id The campaign ID.
 * @return void
 */
function charitable_compat_wp_super_cache_clear_campaign_cache( $campaign_id ) {
	/* Set super cache to enabled. */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $GLOBALS['super_cache_enabled'] is a third-party global variable used by WP Super Cache plugin for compatibility.
	$GLOBALS['super_cache_enabled'] = 1;

	if ( ! function_exists( 'wp_cache_post_change' ) ) {
		return;
	}

	wp_cache_post_change( $campaign_id );
}

add_action( 'charitable_flush_campaign_cache', 'charitable_compat_wp_super_cache_clear_campaign_cache' );

/**
 * Prevent caching on certain Charitable pages.
 *
 * @deprecated 1.9.0
 *
 * @since  1.5.4
 * @since  1.6.14 Deprecated.
 *
 * @return void
 */
function charitable_compat_wp_super_cache_disable_cache() {
	charitable_get_deprecated()->deprecated_function(
		__FUNCTION__,
		'1.6.14',
		'charitable()->endpoints()->disable_endpoint_cache()'
	);

	charitable()->endpoints()->disable_endpoint_cache();
}
