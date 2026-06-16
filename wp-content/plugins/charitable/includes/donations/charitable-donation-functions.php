<?php
/**
 * Charitable Donation Functions.
 *
 * @package   Charitable/Functions/Donation
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.60
 * @version   1.8.9.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the given donation.
 *
 * This will first attempt to retrieve it from the object cache to prevent duplicate objects.
 *
 * @since  1.0.0
 *
 * @param  int     $donation_id The donation ID.
 * @param  boolean $force       Whether to force a non-cached donation object to be retrieved.
 * @return Charitable_Donation|false
 */
function charitable_get_donation( $donation_id, $force = false ) {
	$donation = wp_cache_get( $donation_id, 'charitable_donation', $force );

	if ( ! $donation ) {
		$donation = charitable()->registry()->get( 'donation_factory' )->get_donation( $donation_id );
		wp_cache_set( $donation_id, $donation, 'charitable_donation' );
	}

	return $donation;
}

/**
 * Update the donation cache.
 *
 * @since  1.6.34
 *
 * @param  Charitable_Donation|Charitable_Recurring_Donation $donation The donation object.
 * @return mixed
 */
function charitable_update_cached_donation( Charitable_Abstract_Donation $donation ) {
	wp_cache_set( $donation->ID, $donation, 'charitable_donation' );
}

/**
 * Given a donation ID and a key, return the submitted value.
 *
 * @since  1.5.0
 *
 * @param  Charitable_Abstract_Donation $donation The donation ID.
 * @param  string                       $key      The meta key.
 * @return mixed
 */
function charitable_get_donor_meta_value( Charitable_Abstract_Donation $donation, $key ) {
	return $donation->get_donor()->get_donor_meta( $key );
}

/**
 * Given a donation ID and a key, return the submitted value.
 *
 * @since  1.5.0
 *
 * @param  Charitable_Abstract_Donation $donation The donation ID.
 * @param  string                       $key      The meta key.
 * @return mixed
 */
function charitable_get_donation_meta_value( Charitable_Abstract_Donation $donation, $key ) {
	return get_post_meta( $donation->ID, $key, true );
}

/**
 * Sanitize a value for a donation field, based on the type of field.
 *
 * @since  1.6.7
 *
 * @param  mixed  $value The submitted value.
 * @param  string $key   The meta key.
 * @return mixed
 */
function charitable_get_sanitized_donation_field_value( $value, $key ) {
	$field = charitable()->donation_fields()->get_field( $key );
	$form  = ( false === $field->admin_form ) ? $field->donation_form : $field->admin_form;

	if ( ! is_array( $form ) ) {
		return $value;
	}

	if ( ! array_key_exists( 'options', $form ) ) {
		return $value;
	}

	if ( is_array( $value ) ) {
		$values = array();

		foreach ( $value as $val ) {
			$values[] = array_key_exists( $val, $form['options'] ) ? $form['options'][ $val ] : $val;
		}

		$value = implode( ', ', $values );
	} else {
		$value = array_key_exists( $value, $form['options'] ) ? $form['options'][ $value ] : $value;
	}

	return $value;
}

/**
 * Return the date formatted for a form field value.
 *
 * @since  1.5.3
 *
 * @param  Charitable_Abstract_Donation $donation The donation instance.
 * @return string
 */
function charitable_get_donation_date_for_form_value( Charitable_Abstract_Donation $donation ) {
	return $donation->get_date( 'F j, Y' );
}

/**
 * Get the donation date as a timestamp.
 *
 * @since  1.6.52
 *
 * @param  Charitable_Abstract_Donation $donation The donation instance.
 * @return int
 */
function charitable_get_donation_date_as_timestamp( Charitable_Abstract_Donation $donation ) {
	return strtotime( $donation->post_date );
}

/**
 * Returns the donation for the current request.
 *
 * @since  1.0.0
 *
 * @return Charitable_Donation|false
 */
function charitable_get_current_donation() {
	return charitable_get_helper( 'request' )->get_current_donation();
}

/**
 * Create a donation.
 *
 * @since  1.4.0
 *
 * @param  array $args Values for the donation.
 * @return int
 */
function charitable_create_donation( array $args ) {
	$donation_id = Charitable_Donation_Processor::get_instance()->save_donation( $args );

	Charitable_Donation_Processor::destroy();

	return $donation_id;
}

/**
 * Find and return a donation based on the given donation key.
 *
 * @since  1.4.0
 *
 * @param  string $donation_key The donation key to query by.
 * @return int|null
 */
function charitable_get_donation_by_key( $donation_key ) {
	global $wpdb;

	$sql = "SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = 'donation_key'
			AND meta_value = %s";

	return $wpdb->get_var( $wpdb->prepare( $sql, $donation_key ) ); // phpcs:ignore
}

/**
 * Find and return a donation using a gateway transaction ID.
 *
 * @since  1.4.7
 *
 * @param  string $transaction_id The transaction id to query by.
 * @return int|null
 */
function charitable_get_donation_by_transaction_id( $transaction_id ) {
	global $wpdb;

	$sql = "SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = '_gateway_transaction_id'
			AND meta_value = %s";

	return $wpdb->get_var( $wpdb->prepare( $sql, $transaction_id ) ); // phpcs:ignore
}

/**
 * Return the IPN url for this gateway.
 *
 * IPNs in Charitable are structured in this way: charitable-listener=gateway
 *
 * @since  1.4.0
 *
 * @param  string $gateway The gateway to get the ipn URL for.
 * @return string
 */
function charitable_get_ipn_url( $gateway ) {
	return charitable_get_permalink( 'webhook_listener', array( 'gateway' => $gateway ) );
}

/**
 * Checks for calls to our IPN.
 *
 * This method is called on the init hook.
 *
 * IPNs in Charitable are structured in this way: charitable-listener=gateway
 *
 * @deprecated 1.9.0
 *
 * @since  1.4.0
 * @since  1.6.14 Deprecated. This is now handled by the webhook listener endpoint.
 *
 * @return boolean True if this is a call to our IPN. False otherwise.
 */
function charitable_ipn_listener() {
	charitable_get_deprecated()->deprecated_function(
		__FUNCTION__,
		'1.6.14',
		"charitable()->endpoints()->get_endpoint( 'webhook_listener' )->process_incoming_webhook()"
	);

	return charitable()->endpoints()->get_endpoint( 'webhook_listener' )->process_incoming_webhook();
}

/**
 * Checks if this is happening right after a donation.
 *
 * This method is called on the init hook.
 *
 * @since  1.4.0
 *
 * @return boolean Whether this is after a donation.
 */
function charitable_is_after_donation() {
	if ( is_admin() ) {
		return false;
	}

	$processor = get_transient( 'charitable_donation_' . charitable_get_session()->get_session_id() );

	if ( ! $processor ) {
		return false;
	}

	/**
	 * Do something on a user's first page load after a donation has been made.
	 *
	 * @since 1.3.6
	 *
	 * @param Charitable_Donation_Processor $processor The instance of `Charitable_Donation_Processor`.
	 */
	do_action( 'charitable_after_donation', $processor );

	foreach ( $processor->get_campaign_donations_data() as $campaign_donation ) {
		charitable_get_session()->remove_donation( $campaign_donation['campaign_id'] );
	}

	delete_transient( 'charitable_donation_' . charitable_get_session()->get_session_id() );

	return true;
}

/**
 * Returns whether the donation status is valid.
 *
 * @since  1.4.0
 *
 * @param  string $status The status to check.
 * @return boolean
 */
function charitable_is_valid_donation_status( $status ) {
	return array_key_exists( $status, charitable_get_valid_donation_statuses() );
}

/**
 * Returns the donation statuses that signify a donation was complete.
 *
 * By default, this is just 'charitable-completed'. However, 'charitable-preapproval'
 * is also counted.
 *
 * @since  1.4.0
 *
 * @return string[]
 */
function charitable_get_approval_statuses() {
	/**
	 * Filter the list of donation statuses that we consider "approved".
	 *
	 * All statuses must already be listed as valid donation statuses.
	 *
	 * @see   charitable_get_valid_donation_statuses
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $statuses List of statuses.
	 */
	$statuses = apply_filters( 'charitable_approval_donation_statuses', array( 'charitable-completed' ) );

	return array_filter( $statuses, 'charitable_is_valid_donation_status' );
}

/**
 * Returns whether the passed status is an confirmed status.
 *
 * @since  1.4.0
 *
 * @param  string|false $status The status to check.
 * @return boolean
 */
function charitable_is_approved_status( $status ) {
	return false !== $status && in_array( $status, charitable_get_approval_statuses() ); // phpcs:ignore
}

/**
 * Return array of valid donations statuses.
 *
 * @since  1.4.0
 *
 * @return array
 */
function charitable_get_valid_donation_statuses() {
	/**
	 * Filter the list of possible donation statuses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $statuses The list of status as a key=>value array.
	 */
	return apply_filters(
		'charitable_donation_statuses',
		array(
			'charitable-completed' => __( 'Paid', 'charitable' ),
			'charitable-pending'   => __( 'Pending', 'charitable' ),
			'charitable-failed'    => __( 'Failed', 'charitable' ),
			'charitable-cancelled' => __( 'Cancelled', 'charitable' ),
			'charitable-refunded'  => __( 'Refunded', 'charitable' ),
		)
	);
}

/**
 * Cancel a donation.
 *
 * @since  1.4.0
 *
 * @global WP_Query $wp_query
 * @return boolean True if the donation was cancelled. False otherwise.
 */
function charitable_cancel_donation() {
	global $wp_query;

	if ( ! charitable_is_page( 'donation_cancel_page' ) ) {
		return false;
	}

	if ( ! isset( $wp_query->query_vars['donation_id'] ) ) {
		return false;
	}

	$donation = charitable_get_donation( $wp_query->query_vars['donation_id'] );

	if ( ! $donation ) {
		return false;
	}

	/* Donations can only be cancelled if they are currently pending. */
	if ( 'charitable-pending' != $donation->get_status() ) { // phpcs:ignore
		return false;
	}

	if ( ! $donation->is_from_current_user() ) {
		return false;
	}

	$donation->update_status( 'charitable-cancelled' );

	return true;
}

/**
 * Load the donation form script.
 *
 * @since  1.4.0
 *
 * @return void
 */
function charitable_load_donation_form_script() {
	wp_enqueue_script( 'charitable-donation-form' );
}

/**
 * Add a message to a donation's log.
 *
 * @since  1.0.0
 *
 * @param  int    $donation_id The donation ID.
 * @param  string $message     The message to add to the log.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function charitable_update_donation_log( $donation_id, $message ) {
	return charitable_get_donation( $donation_id )->log()->add( $message );
}

/**
 * Get a donation's log.
 *
 * @since  1.0.0
 *
 * @param  int $donation_id The donation ID.
 * @return array
 */
function charitable_get_donation_log( $donation_id ) {
	return charitable_get_donation( $donation_id )->log()->get_meta_log();
}

/**
 * Get the gateway used for the donation.
 *
 * @since  1.0.0
 *
 * @param  int $donation_id The donation ID.
 * @return string
 */
function charitable_get_donation_gateway( $donation_id ) {
	return get_post_meta( $donation_id, 'donation_gateway', true );
}

/**
 * Sanitize meta values before they are persisted to the database.
 *
 * @since  1.0.0
 *
 * @param  mixed  $value The value to sanitize.
 * @param  string $key   The key of the meta field to be sanitized.
 * @return mixed
 */
function charitable_sanitize_donation_meta( $value, $key ) {
	if ( 'donation_gateway' == $key ) { // phpcs:ignore
		if ( empty( $value ) || ! $value ) {
			$value = 'manual';
		}
	}

	/**
	 * Deprecated hook for filtering donation meta.
	 *
	 * @since 1.0.0
	 * @since 1.5.0 Deprecated.
	 *
	 * @param mixed $value The value of the donation meta field.
	 */
	$value = apply_filters( 'charitable_sanitize_donation_meta-' . $key, $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

	/**
	 * Filter donation meta.
	 *
	 * This hook takes the form of charitable_sanitize_donation_meta_{key}.
	 * For example, to filter the `donation_gateway` meta, the hook would be:
	 *
	 * charitable_sanitize_donation_meta_doantion_gateway
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $value The value of the donation meta field.
	 */
	return apply_filters( 'charitable_sanitize_donation_meta_' . $key, $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
}

/**
 * Flush the donations cache for every campaign receiving a donation.
 *
 * @since  1.0.0
 *
 * @param  int $donation_id The donation ID.
 * @return void
 */
function charitable_flush_campaigns_donation_cache( $donation_id ) {
	$campaign_donations = charitable_get_table( 'campaign_donations' )->get_donation_records( $donation_id );

	foreach ( $campaign_donations as $campaign_donation ) {
		Charitable_Campaign::flush_donations_cache( $campaign_donation->campaign_id );
	}

	wp_cache_delete( $donation_id, 'charitable_donation' );

	/**
	 * Do something when the donation cache is flushed.
	 *
	 * @since 1.4.18
	 *
	 * @param int $donation_id The donation ID.
	 */
	do_action( 'charitable_flush_donation_cache', $donation_id );
}

/**
 * Return the minimum donation amount.
 *
 * @since  1.5.0
 * @since  1.6.60 Added the $campaign_id argument.
 * @since  1.7.0.3 Added the minimum check.
 *
 * @param  int $campaign_id Optional campaign ID.
 * @return float
 */
function charitable_get_minimum_donation_amount( $campaign_id = 0 ) {

	$minimum_amount = 0;

	if ( 0 !== $campaign_id ) {

		$minimum_amount_to_check = get_post_meta( $campaign_id, '_campaign_minimum_donation_amount', true );

		if ( charitable_is_debug() ) {
			error_log( print_r( $minimum_amount_to_check, true ) ); // phpcs:ignore
		}

		if ( false !== $minimum_amount_to_check && '' !== trim( $minimum_amount_to_check ) ) {
			$minimum_amount = charitable_sanitize_amount( $minimum_amount_to_check, true ); // converts a format like $1.05 to 1.05, per the dev note below.
			if ( charitable_is_debug() ) {
				error_log( print_r( $minimum_amount, true ) ); // phpcs:ignore
			}
		}
	}

	/**
	 * Filter the minimum donation amount.
	 *
	 * Note, the minimum donation amount should be expressed in a format
	 * where a period (.) is used as the decimal separator. i.e. 2.50 is
	 * two dollars and fifty cents.
	 *
	 * @since 1.5.0
	 * @since 1.6.60 Added the $campaign_id argument.
	 *
	 * @param float $minimum     The minimum amount.
	 * @param int   $campaign_id Campaign ID. This is 0 by default, meaning the global minimum should be returned.
	 */
	return apply_filters( 'charitable_minimum_donation_amount', $minimum_amount, $campaign_id );
}

/**
 * Return the maximum donation amount.
 *
 * @since  1.6.60
 *
 * @param  int $campaign_id Optional campaign ID.
 * @return float
 */
function charitable_get_maximum_donation_amount( $campaign_id = 0 ) {
	/**
	 * Filter the maximum donation amount.
	 *
	 * Note, the maximum donation amount should be expressed in a format
	 * where a period (.) is used as the decimal separator and no commas
	 * should be used for thousands. i.e. 9999.90 is nine thousand, nine hundred
	 * ninety-nine dollars and ninety cents.
	 *
	 * @since 1.6.60
	 *
	 * @param float|false $maximum     The maximum amount, or false if there is no maximum.
	 * @param int         $campaign_id Campaign ID. This is 0 by default, meaning the global maximum should be returned.
	 */
	return apply_filters( 'charitable_maximum_donation_amount', false, $campaign_id );
}

/**
 * Check whether the current user has access to a particular donation.
 *
 * @since  1.5.14
 *
 * @param  int $donation_id The donation ID.
 * @return boolean
 */
function charitable_user_can_access_donation( $donation_id ) {
	$donation = charitable_get_donation( $donation_id );

	return $donation && $donation->is_from_current_user();
}

/**
 * Log a donation form error.
 *
 * @since  1.8.9.2
 *
 * @param  string $error_type The type of error (validation_failure, ajax_failure, security_failure, email_failure).
 * @param  string $error_details Specific error details or code.
 * @param  array  $context Additional context data for the error.
 *
 * @return int|false The activity ID on success, false on failure.
 */
function charitable_log_form_error( $error_type, $error_details, $context = array() ) {
	// Check if logging is enabled
	if ( ! apply_filters( 'charitable_form_error_logging_enabled', true ) ) {
		return false;
	}

	// Basic spam protection - rate limiting per IP
	$ip_address = charitable_get_user_ip_address();
	if ( ! charitable_check_basic_rate_limit( $ip_address ) ) {
		return false; // Rate limit exceeded
	}

	// Basic burst protection - total errors per minute
	if ( ! charitable_check_basic_burst_limit() ) {
		return false; // Burst limit exceeded
	}

	// Get the activity logger instance (defensive: avoid fatal if class not loaded, e.g. cron/frontend edge cases).
	if ( ! class_exists( 'Charitable_Admin_Activities' ) ) {
		return false;
	}
	$activity_logger = Charitable_Admin_Activities::get_instance();
	if ( ! is_object( $activity_logger ) || ! method_exists( $activity_logger, 'log_form_error' ) ) {
		return false;
	}

	// Prepare context with current request data
	$default_context = array(
		'url'        => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '',
		'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
		'ip_address' => $ip_address,
	);

	$context = array_merge( $default_context, $context );

	return $activity_logger->log_form_error( $error_type, $error_details, $context );
}

/**
 * Get user IP address safely.
 *
 * @since  1.8.9.2
 *
 * @return string User IP address.
 */
function charitable_get_user_ip_address() {
	$ip_address = '';

	// Check for IP from shared internet
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}
	// Check for IP passed from proxy (may be comma-separated list: client, proxy1, proxy2)
	elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip_address = trim( strtok( $_SERVER['HTTP_X_FORWARDED_FOR'], ',' ) );
	}
	// Check for IP from remote address
	elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}

	// Validate and sanitize IP (prevents using invalid/comma-separated value)
	$ip_address = filter_var( $ip_address, FILTER_VALIDATE_IP );

	return $ip_address ? $ip_address : '';
}

/**
 * Get recent donation form errors.
 *
 * @since  1.8.9.2
 *
 * @param  int $limit The number of errors to retrieve.
 *
 * @return array Array of error records.
 */
function charitable_get_recent_form_errors( $limit = 50 ) {
	if ( ! class_exists( 'Charitable_Admin_Activities' ) ) {
		return array();
	}
	$activity_logger = Charitable_Admin_Activities::get_instance();
	if ( ! is_object( $activity_logger ) || ! method_exists( $activity_logger, 'get_recent_form_errors' ) ) {
		return array();
	}
	return $activity_logger->get_recent_form_errors( $limit );
}

/**
 * Get donation form error statistics.
 *
 * @since  1.8.9.2
 *
 * @param  int $days Number of days to look back.
 *
 * @return array Statistics array.
 */
function charitable_get_form_error_stats( $days = 7 ) {
	if ( ! class_exists( 'Charitable_Admin_Activities' ) ) {
		return array();
	}
	$activity_logger = Charitable_Admin_Activities::get_instance();
	if ( ! is_object( $activity_logger ) || ! method_exists( $activity_logger, 'get_form_error_stats' ) ) {
		return array();
	}
	return $activity_logger->get_form_error_stats( $days );
}

/**
 * Clean up old donation form error logs.
 *
 * @since  1.8.9.2
 *
 * @param  int $days Number of days to retain.
 *
 * @return int Number of records deleted.
 */
function charitable_cleanup_old_form_errors( $days = 7 ) {
	// Allow customization of retention period
	$days = apply_filters( 'charitable_form_error_retention_days', $days );

	if ( ! class_exists( 'Charitable_Admin_Activities' ) ) {
		return 0;
	}
	$activity_logger = Charitable_Admin_Activities::get_instance();
	if ( ! is_object( $activity_logger ) || ! method_exists( $activity_logger, 'cleanup_old_form_errors' ) ) {
		return 0;
	}
	return $activity_logger->cleanup_old_form_errors( $days );
}

/**
 * Schedule daily cleanup of old donation form error logs.
 *
 * @since  1.8.9.2
 *
 * @return void
 */
function charitable_schedule_error_log_cleanup() {
	if ( ! wp_next_scheduled( 'charitable_daily_error_log_cleanup' ) ) {
		// Schedule daily cleanup at 3 AM local time
		wp_schedule_event( strtotime( '03:00:00' ), 'daily', 'charitable_daily_error_log_cleanup' );
	}
}

/**
 * Register the deactivation hook for error log cleanup.
 *
 * @since  1.8.9.2
 *
 * @return void
 */
function charitable_register_error_log_deactivation_hook() {
	$charitable = charitable();
	if ( ! $charitable || ! is_callable( array( $charitable, 'get_path' ) ) ) {
		return;
	}
	register_deactivation_hook( $charitable->get_path(), 'charitable_cleanup_error_log_cron_on_deactivation' );
}

/**
 * Clean up scheduled cron jobs on plugin deactivation.
 *
 * @since  1.8.9.2
 *
 * @return void
 */
function charitable_cleanup_error_log_cron_on_deactivation() {
	// Clear all scheduled events for this hook (avoids orphaned events if scheduled multiple times).
	wp_clear_scheduled_hook( 'charitable_daily_error_log_cleanup' );
}

/**
 * Basic rate limiting check per IP address.
 *
 * @since  1.8.9.2
 *
 * @param  string $ip_address IP address to check.
 *
 * @return bool True if within rate limit, false if exceeded.
 */
function charitable_check_basic_rate_limit( $ip_address ) {
	$rate_limit = apply_filters( 'charitable_error_rate_limit_per_ip', 10 ); // 10 errors per minute per IP
	$time_window = apply_filters( 'charitable_error_rate_limit_window', 60 ); // 60 seconds

	if ( empty( $ip_address ) ) {
		return true; // Can't rate limit without IP
	}

	$transient_key = 'charitable_error_rate_' . md5( $ip_address );
	$current_count = get_transient( $transient_key );

	if ( false === $current_count ) {
		// First error from this IP in this time window
		set_transient( $transient_key, 1, $time_window );
		return true;
	}

	if ( $current_count >= $rate_limit ) {
		// Rate limit exceeded
		return false;
	}

	// Increment counter
	set_transient( $transient_key, $current_count + 1, $time_window );
	return true;
}

/**
 * Basic burst protection check.
 *
 * @since  1.8.9.2
 *
 * @return bool True if within burst limit, false if exceeded.
 */
function charitable_check_basic_burst_limit() {
	$burst_limit = apply_filters( 'charitable_error_burst_limit', 50 ); // 50 errors per minute system-wide
	$time_window = apply_filters( 'charitable_error_burst_window', 60 ); // 60 seconds

	$transient_key = 'charitable_error_burst_count';
	$current_count = get_transient( $transient_key );

	if ( false === $current_count ) {
		// First error in this time window
		set_transient( $transient_key, 1, $time_window );
		return true;
	}

	if ( $current_count >= $burst_limit ) {
		// Burst limit exceeded
		return false;
	}

	// Increment counter
	set_transient( $transient_key, $current_count + 1, $time_window );
	return true;
}

/**
 * Get current spam protection status.
 *
 * @since  1.8.9.2
 *
 * @return array Status information.
 */
function charitable_get_spam_protection_status() {
	$ip_address = charitable_get_user_ip_address();
	$ip_transient_key = 'charitable_error_rate_' . md5( $ip_address );
	$burst_transient_key = 'charitable_error_burst_count';

	$ip_count = get_transient( $ip_transient_key );
	$burst_count = get_transient( $burst_transient_key );

	$rate_limit = apply_filters( 'charitable_error_rate_limit_per_ip', 10 );
	$burst_limit = apply_filters( 'charitable_error_burst_limit', 50 );

	return array(
		'ip_address' => $ip_address,
		'ip_errors_this_minute' => $ip_count ?: 0,
		'ip_rate_limit' => $rate_limit,
		'ip_rate_limit_exceeded' => ( $ip_count >= $rate_limit ),
		'total_errors_this_minute' => $burst_count ?: 0,
		'burst_limit' => $burst_limit,
		'burst_limit_exceeded' => ( $burst_count >= $burst_limit ),
		'logging_enabled' => apply_filters( 'charitable_form_error_logging_enabled', true ),
	);
}
