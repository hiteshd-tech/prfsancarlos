<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 * @package block-developer-examples
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current year.
$charitable_current_year = gmdate( 'Y' );

// Determine which content to display.
if ( isset( $attributes['fallbackCurrentYear'] ) && $attributes['fallbackCurrentYear'] === $charitable_current_year ) {

	// The current year is the same as the fallback, so use the block content saved in the database (by the save.js function).
	$charitable_block_content = $content;
} else {

	// The current year is different from the fallback, so render the updated block content.
	if ( ! empty( $attributes['startingYear'] ) && ! empty( $attributes['showStartingYear'] ) ) {
		$charitable_display_date = $attributes['startingYear'] . '–' . $charitable_current_year;
	} else {
		$charitable_display_date = $charitable_current_year;
	}

	$charitable_block_content = '<p ' . get_block_wrapper_attributes() . '>© ' . esc_html( $charitable_display_date ) . '</p>';
}

echo wp_kses_post( $charitable_block_content );
