<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Util functions.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 * @phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found
 */

/**
 * Get sanitized post title or "no title" placeholder.
 *
 * The placeholder is prepended with post ID.
 *
 * @since 1.8.0
 *
 * @param WP_Post|object $post Post object.
 *
 * @return string Post title.
 */
function charitable_get_post_title( $post ) {

	/* translators: %d - a post ID. */
	return charitable_is_empty_string( trim( $post->post_title ) ) ? sprintf( __( '#%d (no title)', 'charitable' ), absint( $post->ID ) ) : $post->post_title;
}

/**
 * Check if a string is empty.
 *
 * @since 1.8.0
 *
 * @param string $the_string String to test.
 *
 * @return bool
 */
function charitable_is_empty_string( $the_string ) {

	return is_string( $the_string ) && $the_string === '';
}

/**
 * Changes array of items into string of items, separated by comma and sql-escaped.
 *
 * @see https://coderwall.com/p/zepnaw
 *
 * @since 1.8.0
 *
 * @param mixed|array $items  Item(s) to be joined into string.
 * @param string      $format Can be %s or %d.
 *
 * @return string Items separated by comma and sql-escaped.
 */
function charitable_wpdb_prepare_in( $items, $format = '%s' ) {

	global $wpdb;

	$items    = (array) $items;
	$how_many = count( $items );

	if ( $how_many === 0 ) {
		return '';
	}

	$placeholders    = array_fill( 0, $how_many, $format );
	$prepared_format = implode( ',', $placeholders );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->prepare( $prepared_format, $items );
}

/**
 * Settings select field callback.
 *
 * @since 1.8.0
 *
 * @param array $args Arguments.
 *
 * @return string
 */
function charitable_settings_select_callback( $args ) {

	$default     = isset( $args['default'] ) ? esc_html( $args['default'] ) : '';
	$value       = isset( $args['id'] ) ? esc_html( $args['id'] ) : $default; // charitable_setting( $args['id'], $default );.
	$id          = sanitize_key( $args['id'] );
	$select_name = $id;
	$class       = ! empty( $args['choicesjs'] ) ? 'choicesjs-select' : '';
	$choices     = ! empty( $args['choicesjs'] ) ? true : false;
	$data        = isset( $args['data'] ) ? (array) $args['data'] : [];
	$attr        = isset( $args['attr'] ) ? (array) $args['attr'] : [];

	if ( $choices && ! empty( $args['search'] ) ) {
		$data['search'] = 'true';
	}

	if ( ! empty( $args['placeholder'] ) ) {
		$data['placeholder'] = $args['placeholder'];
	}

	if ( $choices && ! empty( $args['multiple'] ) ) {
		$attr[]      = 'multiple';
		$select_name = $id . '[]';
	}

	foreach ( $data as $name => $val ) {
		$data[ $name ] = 'data-' . sanitize_html_class( $name ) . '="' . esc_attr( $val ) . '"';
	}

	$data = implode( ' ', $data );
	$attr = implode( ' ', array_map( 'sanitize_html_class', $attr ) );

	$output  = $choices ? '<span class="choicesjs-select-wrap">' : '';
	$output .= '<select id="campaign-setting-' . $id . '" name="' . $select_name . '" class="' . $class . '"' . $data . $attr . '>';

	foreach ( $args['options'] as $option => $name ) {
		if ( empty( $args['selected'] ) ) {
			$selected = selected( $value, $option, false );
		} else {
			$selected = is_array( $args['selected'] ) && in_array( $option, $args['selected'], true ) ? 'selected' : '';
		}
		$output .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$output .= '</select>';
	$output .= $choices ? '</span>' : '';

	if ( ! empty( $args['desc'] ) ) {
		$output .= '<p class="desc">' . wp_kses_post( $args['desc'] ) . '</p>';
	}

	return $output;
}

/**
 * Check if Gutenberg is active.
 *
 * @since 1.8.0
 *
 * @return bool True if Gutenberg is active.
 */
function charitable_is_gutenberg_active() {

	$gutenberg    = false;
	$block_editor = false;

	if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) {
		// Gutenberg is installed and activated.
		$gutenberg = true;
	}

	if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) {
		// Block editor.
		$block_editor = true;
	}

	if ( ! $gutenberg && ! $block_editor ) {
		return false;
	}

	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	if ( is_plugin_active( 'disable-gutenberg/disable-gutenberg.php' ) ) {
		return ! disable_gutenberg();
	}

	if ( is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
		return get_option( 'classic-editor-replace' ) === 'block';
	}

	return true;
}

/**
 * Is the string a unix time stamp.
 *
 * @since 1.8.0
 *
 * @param string $timestamp A string that could be a date.
 *
 * @return bool True if it's likely a unix time stamp.
 */
function charitable_is_valid_timestamp( $timestamp = '' ) {

	return ( (string) (int) $timestamp === $timestamp )
		&& ( $timestamp <= PHP_INT_MAX )
		&& ( $timestamp >= ~PHP_INT_MAX );
}

/**
 * Is the string a unix time stamp.
 *
 * @since 1.8.0
 *
 * @param string $date  The string to check.
 * @param string $format Any date format.
 *
 * @return bool True if it's the date with matching format.
 */
function charitable_validate_date( $date = '', $format = 'Y-m-d' ) {

	$d = DateTime::createFromFormat( $format, $date );
	return $d && $d->format( $format ) === $date;
}
