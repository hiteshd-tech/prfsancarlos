<?php
/**
 * Integration modules provide compatibility with other plugins, or extend the
 * core features of Divi Areas Pro.
 *
 * Integrates with: SG Optimizer
 * Scope: Fix compatibility with code minification
 *
 * @free include file
 * @package PopupsForDivi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Instructs Caching plugins to NOT combine our loader script. Combined scripts are
 * moved to end of the document, which counteracts the entire purpose of the
 * loader...
 *
 * @since 1.4.5
 *
 * @param array $exclude_list Default exclude list.
 *
 * @return array Extended exclude list.
 */
function pfd_integration_sg_optimizer_exclude_inline_content( $exclude_list ) {
	$exclude_list[] = 'window.DiviPopupData=window.DiviAreaConfig=';

	return $exclude_list;
}

add_filter(
	'sgo_javascript_combine_excluded_inline_content',
	'pfd_integration_sg_optimizer_exclude_inline_content'
);
