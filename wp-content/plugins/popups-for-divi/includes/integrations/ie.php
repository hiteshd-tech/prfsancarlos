<?php
/**
 * Integration modules provide compatibility with other plugins, or extend the
 * core features of Divi Areas Pro.
 *
 * Integrates with: Internet Explorer 11
 * Scope: Polyfills required by Divi Areas Pro JS libraries.
 *
 * @free include file
 * @package PopupsForDivi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues polyfills for compatibility with IE11. Required by the front.js
 * bundle, but not in the Visual Builder.
 *
 * @since 2.0.1
 * @return void
 */
function pfd_integration_ie_polyfill() {
	// Not needed anywhere in the admin dashboard.
	if ( is_admin() || pfd_is_visual_builder() ) {
		return;
	}

	if ( dm_get_const( 'SCRIPT_DEBUG' )  ) {
		$cache_version = time();
	} else {
		$cache_version = DIVI_POPUP_VERSION;
	}
	
	if ( ! is_preview() ) {

	wp_enqueue_script(
		'dap-ie',
		pfd_url( 'scripts/ie-compat.min.js' ),
		[],
		$cache_version
	);
	
	}
}

add_action( 'wp_enqueue_scripts', 'pfd_integration_ie_polyfill' );
