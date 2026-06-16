<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper logging and debug functions within the campaign builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

use Charitable\Logger\Log;

/**
 * Check whether plugin works in a debug mode.
 *
 * @since 1.8.0
 *
 * @return bool
 */
function charitable_builder_debug() {

	$debug = false;

	if ( ( ( defined( 'CHARITABLE_BUILDER_DEBUG' ) && true === CHARITABLE_BUILDER_DEBUG ) || ( defined( 'CHARITABLE_DEBUG' ) && true === CHARITABLE_DEBUG ) ) && is_super_admin() ) {
		$debug = true;
	}

	return apply_filters( 'charitable_builder_debug', $debug );
}

/**
 * Helper function to display debug data.
 *
 * @since 1.8.0
 *
 * @param mixed $data What to dump, can be any type.
 * @param bool  $echo_output Whether to print or return. Default is to print.
 *
 * @return string|void
 */
function charitable_builder_debug_data( $data, $echo_output = true ) {

	if ( ! charitable_builder_debug() ) {
		return;
	}

	if ( is_array( $data ) || is_object( $data ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$data = print_r( $data, true );
	}

	$output = sprintf(
		'<style>
			.charitable-debug {
				line-height: 0;
			}
			.charitable-debug textarea {
				background: #f6f7f7 !important;
				margin: 20px 0 0 0;
				width: 100%%;
				height: 3500px;
				font-size: 12px;
				font-family: Consolas, Menlo, Monaco, monospace;
				direction: ltr;
				unicode-bidi: embed;
				line-height: 1.4;
				padding: 10px;
				border-radius: 0;
				border-color: #c3c4c7;
			}
			.postbox .charitable-debug {
				padding-top: 12px;
			}
			.postbox .charitable-debug:first-of-type {
				padding-top: 6px;
			}
			.postbox .charitable-debug textarea {
				margin-top: 0 !important;
			}
		</style>
		<div class="charitable-debug">
			<textarea readonly>=================== CHARITABLE DEBUG ===================%s</textarea>
		</div>',
		"\n\n" . $data
	);

	/**
	 * Allow developers to determine whether the debug data should be displayed.
	 * Works only in debug mode (`CHARITABLE_DEBUG` constant is `true`).
	 *
	 * @since 1.8.0
	 *
	 * @param bool $allow_display True by default.
	 */
	$allow_display = apply_filters( 'charitable_debug_data_allow_display', true );

	if ( $echo_output && $allow_display ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Helper function that returns debug info via ajax/json.
 *
 * @since 1.8.0
 *
 * @return string|void
 */
function charitable_update_debug_window_ajax() {

	// data to post is likely be submitted form data from an ajax request.
	$data = false;

	if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) { // @codingStandardsIgnoreLine
		$data = json_decode( html_entity_decode( stripslashes( $_POST['data'] ) ) ); // @codingStandardsIgnoreLine
	} elseif ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) { // @codingStandardsIgnoreLine
		$data = print_r( $_POST['data'], true ); // @codingStandardsIgnoreLine
	}

	$output = sprintf(
		'<textarea readonly>=================== ' . esc_html__( 'CHARITABLE', 'charitable' ) . ' DEBUG ===================%s</textarea>',
		"\n\n" . $data
	);

	wp_send_json_success( $output );
	exit;
}
add_action( 'wp_ajax_charitable_update_debug_window_ajax', 'charitable_update_debug_window_ajax' );
add_action( 'wp_ajax_nopriv_charitable_update_debug_window_ajax', 'charitable_update_debug_window_ajax' );

/**
 * Log helper.
 *
 * @since 1.8.0
 *
 * @param string $title   Title of a log message.
 * @param mixed  $message Content of a log message.
 * @param array  $args    Expected keys: form_id, meta, parent.
 */
function charitable_builder_log( $title = '', $message = '', $args = [] ) {

	// Skip if logs disabled in Tools -> Logs.
	if ( ! charitable_setting( 'logs-enable', false ) ) {
		return;
	}

	// Require log title.
	if ( empty( $title ) ) {
		return;
	}

	/**
	 * Compare error levels to determine if we should log.
	 * Current supported levels:
	 * - Conditional Logic (conditional_logic)
	 * - Entries (entry)
	 * - Errors (error)
	 * - Payments (payment)
	 * - Providers (provider)
	 * - Security (security)
	 * - Spam (spam)
	 * - Log (log)
	 */
	$types = ! empty( $args['type'] ) ? (array) $args['type'] : [ 'error' ];

	// Skip invalid logs types.
	$log_types = Log::get_log_types();

	foreach ( $types as $key => $type ) {
		if ( ! isset( $log_types[ $type ] ) ) {
			unset( $types[ $key ] );
		}
	}

	if ( empty( $types ) ) {
		return;
	}

	// Make arrays and objects look nice.
	if ( is_array( $message ) || is_object( $message ) ) {
		$message = '<pre>' . print_r( $message, true ) . '</pre>'; // phpcs:ignore
	}

	// Filter logs types from Tools -> Logs page.
	$logs_types = charitable_setting( 'logs-types', false );

	if ( $logs_types && empty( array_intersect( $logs_types, $types ) ) ) {
		return;
	}

	// Filter user roles from Tools -> Logs page.
	$current_user       = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
	$current_user_id    = $current_user ? $current_user->ID : 0;
	$current_user_roles = $current_user ? $current_user->roles : [];
	$logs_user_roles    = charitable_setting( 'logs-user-roles', false );

	if ( $logs_user_roles && empty( array_intersect( $logs_user_roles, $current_user_roles ) ) ) {
		return;
	}

	// Filter logs users from Tools -> Logs page.
	$logs_users = charitable_setting( 'logs-users', false );

	if ( $logs_users && ! in_array( $current_user_id, $logs_users, true ) ) {
		return;
	}

	$log = charitable()->get( 'log' );

	if ( ! method_exists( $log, 'add' ) ) {
		return;
	}
	// Create log entry.
	$log->add(
		$title,
		$message,
		$types,
		isset( $args['form_id'] ) ? absint( $args['form_id'] ) : 0,
		isset( $args['parent'] ) ? absint( $args['parent'] ) : 0,
		$current_user_id
	);
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 1.8.0
 *
 * @param int $limit Time limit.
 */
function charitable_set_time_limit( $limit = 0 ) {

	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}
