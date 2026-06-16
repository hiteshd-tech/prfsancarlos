<?php
/**
 * Helper functions (or utility functions) aid in keeping the code clean. They
 * do not add business logic but simplify other tasks, such as sanitation of
 * input values.
 *
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Returns an absolute URL to the requested resource inside the plugin folder.
 * When no resource is specified, the plugin folders root URL is returned (with
 * trailing slash). The result is always passed through esc_url().
 *
 * @since 3.0.0
 *
 * @param string $resource Optional. Path to a resource, relative to the plugin
 *                         root.
 *
 * @return string Escaped, absolute URL to the path inside the plugin folder.
 */
function pfd_url( $resource = '' ) {
	return esc_url( DIVI_POPUP_URL . $resource );
}

/**
 * Returns an absolute path to the requested resource inside the plugin folder.
 * When no resource is specified, the plugin folders root path is returned (with
 * trailing slash)..
 *
 * @since 3.0.0
 *
 * @param string $resource Optional. Path to a resource, relative to the plugin
 *                         root.
 *
 * @return string Escaped, absolute URL to the path inside the plugin folder.
 */
function pfd_path( $resource = '' ) {
	return DIVI_POPUP_PATH . $resource;
}

/**
 * Returns the state of the "debug_mode" flag.
 *
 * @since 2.0.0
 *
 * @return bool True, when the JS should be unminified. The production setting
 *              should return false.
 */
function pfd_flag_debug_mode() {
	$debug_mode = (bool) dm_get_const( 'WP_DEBUG' );

	/**
	 * Determine, if the JS snippet should be minified.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $debug_mode True means, the snippet will stay un-minified.
	 */
	return apply_filters( 'divi_areas_enable_debug_mode', $debug_mode );
}
