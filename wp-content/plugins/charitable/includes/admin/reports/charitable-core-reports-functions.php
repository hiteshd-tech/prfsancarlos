<?php
/**
 * Charitable Core Reporting Functions
 *
 * General core functions available only within the admin area.
 *
 * @package     Charitable/Functions/Admin
 * @version     1.8.1
 * @author      David Bisset
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects what tabs in reporting can load the scripts.
 *
 * @since   1.8.1
 *
 * @param   string $tab_slug Tab slug.
 *
 * @return  boolean
 */
function charitable_reports_allow_tab_load_scripts( $tab_slug = false ) {

	if ( ! $tab_slug ) {
		return false;
	}

	$allowed_tabs = array( 'overview', 'advanced', 'activity', 'donors' );

	return in_array( strtolower( $tab_slug ), $allowed_tabs, true );
}

/**
 * Get the advanced report args, perhaps checking a transient if the report has been run before.
 * This is useful if the user selected a report, then navigated away from the page, then came back.
 * Cache is limited to 60 * 60 * 24 seconds. See charitable_reports_get_advanced_report_arg_defaults() for more info.
 *
 * @since 1.8.1
 * @since 1.8.1.3 Added order_by and order.
 *
 * @param array $postdata Post data.
 *
 * @return array
 */
function charitable_reports_get_advanced_report_args( $postdata = array() ) {

	if ( ! empty( $postdata ) ) :

		return apply_filters(
			'charitable_report_advanced_arg_postdata',
			array(
				'start_date'              => ! empty( $postdata['start_date'] ) ? esc_html( $postdata['start_date'] ) : gmdate( 'Y/m/d', current_time( 'timestamp', 0 ) ),
				'end_date'                => ! empty( $postdata['end_date'] ) ? esc_html( $postdata['end_date'] ) : gmdate( 'Y/m/d', strtotime( '-7 days', current_time( 'timestamp', 0 ) ) ),
				'start_date_compare_from' => ! empty( $postdata['compare_from_start_date'] ) ? esc_html( $postdata['compare_from_start_date'] ) : false,
				'end_date_compare_from'   => ! empty( $postdata['compare_from_end_date'] ) ? esc_html( $postdata['compare_from_end_date'] ) : false,
				'start_date_compare_to'   => ! empty( $postdata['compare_to_start_date'] ) ? esc_html( $postdata['compare_to_start_date'] ) : false,
				'end_date_compare_to'     => ! empty( $postdata['compare_to_end_date'] ) ? esc_html( $postdata['compare_to_end_date'] ) : false,
				'filter'                  => ! empty( $postdata['filter'] ) ? esc_html( $postdata['filter'] ) : false,
				'campaign_id'             => ! empty( $postdata['campaign_id'] ) ? esc_html( $postdata['campaign_id'] ) : false,
				'report_type'             => ! empty( $postdata['report_type'] ) ? esc_html( $postdata['report_type'] ) : 'lybunt',
				'order_by'                => ! empty( $postdata['order_by'] ) ? esc_html( $postdata['order_by'] ) : 'total_life_amount',
				'order'                   => ! empty( $postdata['order'] ) ? esc_html( $postdata['order'] ) : 'DESC',
			)
		);
	endif;

	$charitable_report_advanced_args = charitable_reports_maybe_cache( false ) ? get_transient( 'charitable-report-advanced-args' ) : false;

	if ( ! charitable_is_pro() || false === $charitable_report_advanced_args ) {

		$charitable_report_advanced_args = charitable_reports_get_advanced_report_arg_defaults();

		set_transient( 'charitable-report-advanced-args', $charitable_report_advanced_args, 60 * 60 * 24 );
	}

	return $charitable_report_advanced_args;
}

/**
 * Get the advanced report default args.
 *
 * @since 1.8.1
 * @since 1.8.1.3 Added order_by and order.
 *
 * @return array
 */
function charitable_reports_get_advanced_report_arg_defaults() {

	// get today's date in Y/m/d format from WordPress server using current_time().
	$today = gmdate( 'Y/m/d', current_time( 'timestamp', 0 ) );
	// subtract one week from today's date using current_time().
	$one_week_ago = gmdate( 'Y/m/d', strtotime( '-7 days', current_time( 'timestamp', 0 ) ) );
	// get date Y/m/d from Jan 1st of last year.
	$first_day_last_year = gmdate( 'Y/m/d', strtotime( 'first day of January last year', current_time( 'timestamp', 0 ) ) );
	$last_day_last_year  = gmdate( 'Y/m/d', strtotime( 'last day of December last year', current_time( 'timestamp', 0 ) ) );
	$first_day_this_year = gmdate( 'Y/m/d', strtotime( 'first day of January this year', current_time( 'timestamp', 0 ) ) );
	$last_day_this_year  = gmdate( 'Y/m/d', strtotime( 'last day of December this year', current_time( 'timestamp', 0 ) ) );

	return apply_filters(
		'charitable_report_advanced_arg_defaults',
		array(
			'start_date'              => $today,
			'end_date'                => $one_week_ago,
			'start_date_compare_from' => $first_day_last_year,
			'end_date_compare_from'   => $last_day_last_year,
			'start_date_compare_to'   => $first_day_this_year,
			'end_date_compare_to'     => $last_day_this_year,
			'filter'                  => false,
			'campaign_id'             => false,
			'report_type'             => 'lybunt',
			'order_by'                => 'total_life_amount',
			'order'                   => 'DESC',
		)
	);
}

/**
 * Get the donor report args, mainly checking a transient if the report has been run before.
 * This is useful if the user selected a report, then navigated away from the page, then came back.
 * Cache is limited to 1 hour. See Charitable_Reports_Ajax::get_donor_data() for more info.
 *
 * @since 1.8.1
 *
 * @return array
 */
function charitable_reports_get_donor_reports_arg() {

	if ( ! charitable_reports_maybe_cache( false ) ) {
		return false;
	}

	$charitable_report_donor_args = get_transient( 'charitable-report-donor-args' );

	return $charitable_report_donor_args;
}

/**
 * Get the donor report type.
 *
 * @since 1.8.1
 *
 * @param string $report_default Default report type, if none is set is 'donors-top'.
 *
 * @return string
 */
function charitable_reports_get_donor_report_type( $report_default = 'donors-top' ) {

	$charitable_report_donor_args = charitable_reports_get_donor_reports_arg();

	// If the transient is set it probably looks like: Array ( [donor_report_type] => donors-recurring [limit] => ) .

	if ( false !== $charitable_report_donor_args && ! empty( $charitable_report_donor_args['donor_report_type'] ) ) {
		return $charitable_report_donor_args['donor_report_type'];
	}

	return $report_default;
}

/**
 * Check if the report or args should be cached.
 *
 * @since 1.8.1
 *
 * @param bool $force_cache Force cache. Default is false.
 *
 * @return bool
 */
function charitable_reports_maybe_cache( $force_cache = false ) {

	// Check the globals to see if caching is enabled specifically for reports.
	if ( charitable_is_debug() ) {
		return false;
	}
	if ( defined( 'CHARITABLE_REPORTS_NO_CACHE' ) && CHARITABLE_REPORTS_NO_CACHE ) {
		return false;
	}
	if ( ! $force_cache ) {
		return false;
	}

	return true;
}

/**
 * Get the report data.
 *
 * @since 1.8.1
 *
 * @param string $report_type Report type.
 *
 * @return array
 */
function charitable_reports_get_advanced_data_report( $report_type = 'lybunt' ) {

	// type check.
	if ( ! isset( $_POST['report_type'] ) || 'lybunt' !== $_POST['report_type'] ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// security check.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-reporting' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
		exit;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array data, sanitized in function
	$charitable_report_advanced_args = charitable_reports_get_advanced_report_args( $_POST );

	$report_type = isset( $_POST['report_type'] ) ? esc_html( wp_unslash( $_POST['report_type'] ) ) : $charitable_report_advanced_args['report_type']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by esc_html
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$charitable_reports = Charitable_Reports::get_instance();

	$args = array();

	$charitable_reports->init_with_array( $report_type, $args );

	$advanced_report_data = $charitable_reports->get_advanced_data_by_report_type( $charitable_report_advanced_args );

	$data = array();

	$data['html'] = $charitable_reports->generate_advanced_breakdown_report_html( $report_type, $advanced_report_data, $charitable_report_advanced_args );

	wp_send_json_success( $data );

	exit;
}
add_action( 'wp_ajax_charitable_report_advanced_data', 'charitable_reports_get_advanced_data_report' );

/**
 * Get the default pagination per page, perhaps based on report type.
 *
 * @since 1.8.1
 *
 * @param string $report_type Report type.
 *
 * @return int
 */
function charitable_reports_get_pagination_per_page( $report_type = 'donors-top ' ) {

	return apply_filters( 'charitable_report_pagination_per_page', 20, $report_type );
}

/**
 * A function that accepts an array of results and an array of fields and formats the fields using charitable_format_money.
 * This is useful for formatting money fields after calculating averages or getting raw data from the database.
 *
 * @since 1.8.1
 *
 * @param  array     $results       The results to be formatted.
 * @param  array     $fields        The fields to be formatted.
 * @param  int|false $decimal_count Optional. If not set, default decimal count will be used.
 * @param  boolean   $db_format     Optional. Whether the amount is in db format (i.e. using decimals for cents, regardless of site settings).
 * @param  string    $currency      Optional. If passed, will use the given currency's formatting, not the default currency.
 * @param  boolean   $add_currency_symbol Optional. If true, will add the currency symbol to the formatted field.
 * @param  boolean   $convert_currency_symbol Optional. If true, will convert the currency symbol to proper HTML.
 *
 * @return array
 */
function charitable_reports_format_money( $results = array(), $fields = array(), $decimal_count = false, $db_format = false, $currency = '', $add_currency_symbol = false, $convert_currency_symbol = false ) {

	if ( ! is_array( $results ) || empty( $results ) ) {
		return array();
	}

	if ( ! is_array( $fields ) || empty( $fields ) ) {
		return $results;
	}

	// if $convert_currency_symbol is true, we need to decode the currency symbol.
	$currency_symbol = $convert_currency_symbol ? html_entity_decode( charitable_get_currency_helper()->get_currency_symbol() ) : charitable_get_currency_helper()->get_currency_symbol();

	foreach ( $results as $key => $result ) {

		foreach ( $fields as $field ) {

			if ( ! isset( $result[ $field ] ) ) {
				continue;
			}

			$formatted_field           = str_replace( charitable_get_currency_helper()->get_currency_symbol(), '', trim( charitable_format_money( $result[ $field ], $decimal_count, $db_format, $currency ) ) );
			$results[ $key ][ $field ] = 1 === intval( $add_currency_symbol ) ? $currency_symbol . $formatted_field : $formatted_field;
		}
	}

	return $results;
}

/**
 * A function that accepts an array of results and an array of fields and formats the fields using date().
 *
 * @since 1.8.1
 *
 * @param  array  $results       The results to be formatted.
 * @param  array  $fields        The fields to be formatted.
 * @param  string $date_format   Optional. If not set, default date format will be used.
 *
 * @return array
 */
function charitable_reports_format_date( $results = array(), $fields = array(), $date_format = false ) {

	if ( ! is_array( $results ) || empty( $results ) ) {
		return array();
	}

	if ( ! is_array( $fields ) || empty( $fields ) ) {
		return $results;
	}

	foreach ( $results as $key => $result ) {

		foreach ( $fields as $field ) {

			if ( ! isset( $result[ $field ] ) ) {
				continue;
			}

			// convert to date format example: March 31, 2023 1:30 pm.
			$date_format               = empty( $date_format ) ? get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) : $date_format;
			$formatted_field           = gmdate( $date_format, strtotime( $result[ $field ] ) );
			$results[ $key ][ $field ] = $formatted_field;

		}
	}

	return $results;
}

/**
 * Check if the request is an AJAX request.
 * The actions in $ajax_actions what might be possible in an action request to determine if the request for get_donor_data is an AJAX request.
 *
 * @since 1.8.1.3
 *
 * @return boolan
 */
function charitable_reports_is_ajax() {

	$ajax_actions = array(
		'charitable_get_donor_data',
		'charitable_report_donor_data_pagination',
	);

	$is_ajax = isset( $_POST['action'] ) && in_array( $_POST['action'], $ajax_actions, true ) ? true : false; // phpcs:ignore

	return $is_ajax;
}
