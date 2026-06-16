<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper functions for campaign templates (visual builder).
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.4.2
 * @version   1.8.4.2
 */

if ( ! function_exists( 'charitable_hex_to_rgb' ) ) {
	/**
	 * Convert a hex color to an RGB array.
	 *
	 * @since  1.5.0
	 *
	 * @param  string $hex The hex color.
	 * @return array
	 */
	function charitable_hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( strlen( $hex ) == 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$int = hexdec( $hex );

		return [
			'r' => ( $int >> 16 ) & 255,
			'g' => ( $int >> 8 ) & 255,
			'b' => $int & 255,
		];
	}
}

if ( ! function_exists( 'charitable_get_brightness' ) ) {
	/**
	 * Get the brightness of an RGB color.
	 *
	 * @since  1.5.0
	 *
	 * @param  array $rgb The RGB color.
	 * @return float
	 */
	function charitable_get_brightness( $rgb ) {
		return ( $rgb['r'] * 299 + $rgb['g'] * 587 + $rgb['b'] * 114 ) / 1000;
	}
}

if ( ! function_exists( 'charitable_get_constracting_text_color' ) ) {

	/**
	 * Get the contrasting text color for a given background color.
	 *
	 * @since  1.5.0
	 *
	 * @param  string $hex_color The background color.
	 * @return string
	 */
	function charitable_get_constracting_text_color( $hex_color ) {
		$rgb        = charitable_hex_to_rgb( $hex_color );
		$brightness = charitable_get_brightness( $rgb );

		// If brightness is below 175, use white text, otherwise use black.
		return $brightness < 175 ? '#FFFFFF' : '#000000';
	}
}

if ( ! function_exists( 'charitable_get_campaign_builder_asset_url' ) ) {
	/**
	 * Get the correct URL for campaign builder assets.
	 * Handles both Charitable core and Charitable Pro plugin directories.
	 *
	 * @since   1.8.9.2
	 * @version 1.8.9.4
	 *
	 * @param  string $asset_path Relative path from plugin assets directory.
	 * @param  bool   $add_version Whether to add version parameter for cache busting.
	 * @return string Full URL to the asset.
	 */
	function charitable_get_campaign_builder_asset_url( $asset_path, $add_version = null ) {
		// Use filter to control version parameter default.
		if ( is_null( $add_version ) ) {
			/**
			 * Filter whether to add version parameters to campaign builder asset URLs.
			 *
			 * @since   1.8.9.2
			 * @version 1.8.9.4
			 *
			 * @param bool $add_version Whether to add version parameter. Default true.
			 */
			$add_version = apply_filters( 'charitable_campaign_builder_asset_versioning', true );
		}

		$asset_path = ltrim( $asset_path, '/' );
		$url        = '';
		$version    = '';

		// Check Charitable Pro first (if it exists and has the file).
		if ( defined( 'CHARITABLE_PRO_VERSION' ) && defined( 'CHARITABLE_PRO_FILE' ) ) {
			$pro_path = plugin_dir_path( CHARITABLE_PRO_FILE ) . 'assets/' . $asset_path;
			if ( file_exists( $pro_path ) ) {
				$url     = plugin_dir_url( CHARITABLE_PRO_FILE ) . 'assets/' . $asset_path;
				$version = CHARITABLE_PRO_VERSION;
			}
		}

		// Fallback to core Charitable.
		if ( empty( $url ) ) {
			$charitable_path = charitable()->get_path( 'assets' ) . $asset_path;
			if ( file_exists( $charitable_path ) ) {
				$url     = charitable()->get_path( 'assets', false ) . $asset_path;
				$version = charitable()->get_version();
			}
		}

		// Add version parameter for cache busting if enabled.
		if ( $add_version && ! empty( $version ) ) {
			$url = add_query_arg( 'ver', $version, $url );
		}

		/**
		 * Filter the campaign builder asset URL.
		 *
		 * @since   1.8.9.2
		 * @version 1.8.9.4
		 *
		 * @param string $url        The asset URL.
		 * @param string $asset_path The original asset path.
		 * @param bool   $add_version Whether versioning was requested.
		 */
		return apply_filters( 'charitable_campaign_builder_asset_url', $url, $asset_path, $add_version );
	}
}
