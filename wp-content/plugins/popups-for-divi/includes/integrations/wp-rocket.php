<?php
/**
 * Integration modules provide compatibility with other plugins, or extend
 * the core features of Divi Areas Pro.
 *
 * Integrates with: WP Rocket
 * Scope: Fix compatibility with code minification and "Remove Unused CSS"
 *
 * @free    include file
 * @package PopupsForDivi
 */

defined( 'ABSPATH' ) || exit;


/**
 * Instructs Caching plugins to NOT combine our loader script. 
 * Combined scripts are moved to end of the document,
 * which counteracts the entire purpose of the loader...
 *
 * @see   pfd_assets_inject_loader()
 *
 * @since 1.4.5
 *
 * @param array $exclude_list Default exclude list.
 *
 * @return array Extended exclude list.
 */
function pfd_integration_wp_rocket_exclude_inline_content( $exclude_list ) {
	// Never delay/move the Divi Area loader.
	$exclude_list[] = 'window.DiviPopupData=window.DiviAreaConfig=';

	return $exclude_list;
}

// Do not combine the popup loader script with other scripts,
// to preserve the script position.
add_filter(
	'rocket_excluded_inline_js_content',
	'pfd_integration_wp_rocket_exclude_inline_content'
);

// Do not delay the popup loader script.
add_filter(
	'rocket_delay_js_exclusions',
	'pfd_integration_wp_rocket_exclude_inline_content'
);

/**
 * CSS safelist to exclude from WP Rocket's 'Unused CSS' option.
 * The array may contain CSS filenames, IDs or classes.
 *
 * @since 3.2.0
 *
 * @param array[] $css_safelist list of CSS filenames, IDs or classes.
 *
 * @return array[]with-close
 */
function pfd_rocket_css_safelist( $css_safelist ) {
	
	$pfd_css_safelist = array(
		'.area-outer-wrap',
		'.area-outer-wrap > [data-da-area]',
		'[data-da-area]'
	);

	return array_merge( $css_safelist, $pfd_css_safelist );
}
add_filter( 'rocket_rucss_safelist', 'pfd_rocket_css_safelist', 10, 1 );

add_filter( 'rocket_rucss_inline_atts_exclusions', 'pfd_rocket_css_safelist', 10, 1 );


/**
 * Filters the styles attributes to be skipped (blocked) by RemoveUnusedCSS.
 *
 * @since 3.2.0
 *
 * @param array $skipped_attr Array of safelist values.
 *
 * @return array[]
 */
function pfd_rocket_rucss_skip_styles_with_attr( $skipped_attr ) {
	
	$pfd_skipped_attr = array(
		'id="css-divi-area-inline-css"',
		"id='css-divi-area-inline-css'"
	);
	
	return array_merge( $skipped_attr, $pfd_skipped_attr );
}

add_filter( 'rocket_rucss_skip_styles_with_attr', 'pfd_rocket_rucss_skip_styles_with_attr' );


/**
 * Excludes scripts from WP Rocket
 *
 * @since 3.2.0
 *
 * @param Array $excluded_js An array of JS handles enqueued in WordPress.
 * @return Array the updated array of handles
 */
function pfd_rocket_exclude_js( $excluded_js ) {
	
	$excluded_js[] = str_replace( home_url(), '', plugins_url( '/popups-for-divi/scripts/ie-compat.min.js' ) );
	$excluded_js[] = str_replace( home_url(), '', plugins_url( '/popups-for-divi/scripts/front.min.js' ) );

	return $excluded_js;
}
add_filter( 'rocket_exclude_js', 'pfd_rocket_exclude_js', 10, 1 );


/**
 * Remove "css-divi-area-inline-css" inline style for WP Rocket's SaaS
 *
 */
// phpcs:disable WordPress.Security.NonceVerification
$nowprocket = isset( $_GET['nowprocket'] ) && '1' === $_GET['nowprocket'];
$no_optimize = isset( $_GET['no_optimize'] ) && '1' === $_GET['no_optimize'];

if ( $nowprocket || $no_optimize ) {
	
	add_action( 'wp_print_styles', function()
	{
		wp_styles()->add_data( 'css-divi-area', 'after', '' );    

	} );
}

