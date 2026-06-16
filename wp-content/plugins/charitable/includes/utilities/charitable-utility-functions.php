<?php
/**
 * Charitable Utility Functions.
 *
 * Utility functions.
 *
 * @package   Charitable/Functions/Utility
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.55
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders an array by a particular key.
 *
 * @since  1.5.0
 *
 * @param  strign $key The key to sort by.
 * @param  array  $a   First element.
 * @param  array  $b   Element to compare against.
 * @return int
 */
function charitable_element_key_sort( $key, $a, $b ) {
	foreach ( array( $a, $b ) as $item ) {
		if ( ! array_key_exists( $key, $item ) ) {
			if ( charitable_is_debug() ) {
				error_log( sprintf( '%s missing from element: ' . wp_json_encode( $item ), $key ) ); // phpcs:ignore
			}
		}
	}

	if ( $a[ $key ] == $b[ $key ] ) {
		return 0;
	}

	return $a[ $key ] < $b[ $key ] ? -1 : 1;
}

/**
 * Orders an array by the priority key.
 *
 * @since  1.0.0
 *
 * @param  array $a First element.
 * @param  array $b Element to compare against.
 * @return int
 */
function charitable_priority_sort( $a, $b ) {
	return charitable_element_key_sort( 'priority', $a, $b );
}

/**
 * Orders an array by the time key.
 *
 * @since  1.5.0
 *
 * @param  array $a First element.
 * @param  array $b Element to compare against.
 * @return int
 */
function charitable_timestamp_sort( $a, $b ) {
	return charitable_element_key_sort( 'time', $a, $b );
}

/**
 * Checks whether function is disabled.
 *
 * Full credit to Pippin Williamson and the EDD team.
 *
 * @since  1.0.0
 *
 * @param  string $function Name of the function.
 * @return boolean Whether or not function is disabled.
 */
function charitable_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}

/**
 * Verify a nonce. This also just ensures that the nonce is set.
 *
 * @since  1.0.0
 * @version 1.8.9.1
 *
 * @param  string $nonce        The nonce name.
 * @param  string $action       The nonce action.
 * @param  array  $request_args Request arguments. If not set, will populate with $_GET.
 * @return boolean
 */
function charitable_verify_nonce( $nonce, $action, $request_args = array() ) {
	if ( empty( $request_args ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_args = $_GET;
	}

	return isset( $request_args[ $nonce ] ) && wp_verify_nonce( $request_args[ $nonce ], $action );
}

/**
 * Retrieve the timezone id.
 *
 * Credit: Pippin Williamson & the rest of the EDD team.
 *
 * @since  1.0.0
 *
 * @return string
 */
function charitable_get_timezone_id() {
	$timezone = get_option( 'timezone_string' );

	/* If site timezone string exists, return it */
	if ( $timezone ) {
		return $timezone;
	}

	$utc_offset = 3600 * get_option( 'gmt_offset', 0 );

	/* Get UTC offset, if it isn't set return UTC */
	if ( ! $utc_offset ) {
		return 'UTC';
	}

	/* Attempt to guess the timezone string from the UTC offset */
	$timezone = timezone_name_from_abbr( '', $utc_offset );

	/* Last try, guess timezone string manually */
	if ( false === $timezone ) {

		$is_dst = date( 'I' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
					return $city['timezone_id'];
				}
			}
		}
	}

	/* If we still haven't figured out the timezone, fall back to UTC */
	return 'UTC';
}

/**
 * Given an array and a separate array of keys, returns a new array that only contains the
 * elements in the original array with the specified keys.
 *
 * @since  1.5.0
 *
 * @param  array $original_array The original array we need to pull a subset from.
 * @param  array $subset_keys    The keys to use for our subset.
 * @return array
 */
function charitable_array_subset( array $original_array, $subset_keys ) {
	return array_intersect_key( $original_array, array_flip( $subset_keys ) );
}

/**
 * Ensure a number is a positive integer.
 *
 * @since  1.0.0
 *
 * @param  mixed $i Number received.
 * @return int|false
 */
function charitable_validate_absint( $i ) {
	return filter_var( $i, FILTER_VALIDATE_INT, array( 'min_range' => 1 ) );
}

/**
 * Ensure a string is a valid email..
 *
 * @since  1.7.0.9
 *
 * @param  mixed $i Number received.
 * @return int|false
 */
function charitable_validate_email( $i ) {
	return filter_var( $i, FILTER_VALIDATE_EMAIL );
}

/**
 * Sanitize any checkbox value.
 *
 * @since  1.5.0
 *
 * @param  mixed $value Value set for checkbox, or false.
 * @return boolean
 */
function charitable_sanitize_checkbox( $value = false ) {
	return intval( true == $value || 'on' == $value );
}

/**
 * Format an array of strings as a sentence part.
 *
 * If there is one item in the string, this will just return that item.
 * If there are two items, it will return a string like this: "x and y".
 * If there are three or more items, it will return a string like this: "x, y and z".
 *
 * @since  1.3.0
 *
 * @param  string[] $list The list.
 * @return string
 */
function charitable_list_to_sentence_part( $list ) {
	$list = array_values( $list );

	if ( 1 == count( $list ) ) {
		return $list[0];
	}

	if ( 2 == count( $list ) ) {
		return sprintf(
			/* translators: %1$s: first list item; %2$s: second list item. */
			_x( '%1$s and %2$s', 'x and y', 'charitable' ),
			$list[0],
			$list[1]
		);
	}

	$last = array_pop( $list );

	return sprintf(
		/* translators: %1$s: all list items except last, comma-separated; %2$s: second list item. */
		_x( '%1$s and %2$s', 'x and y', 'charitable' ),
		implode( ', ', $list ),
		$last
	);
}

/**
 * Sanitizes a date passed in the format of January 29, 2009.
 *
 * We use WP_Locale to parse the month that the user has set.
 *
 * @global WP_Locale $wp_locale
 *
 * @since   1.4.10
 * @version 1.8.4.2 Fix bug where english dates weren't being found in $wp_locale.
 * @version 1.8.9.1
 *
 * @param  string $date          The date to be sanitized.
 * @param  string $return_format The date format to return. Default is U (timestamp).
 * @return string|false
 */
function charitable_sanitize_date( $date, $return_format = 'U' ) {
	global $wp_locale;

	if ( empty( $date ) || ! $date ) {
		return false;
	}

	// If the month is in english, we need to ensure it's translated if the site is in another language.
	$english_to_local_month = array(
		'january'   => $wp_locale->get_month( 1 ),
		'february'  => $wp_locale->get_month( 2 ),
		'march'     => $wp_locale->get_month( 3 ),
		'april'     => $wp_locale->get_month( 4 ),
		'may'       => $wp_locale->get_month( 5 ),
		'june'      => $wp_locale->get_month( 6 ),
		'july'      => $wp_locale->get_month( 7 ),
		'august'    => $wp_locale->get_month( 8 ),
		'september' => $wp_locale->get_month( 9 ),
		'october'   => $wp_locale->get_month( 10 ),
		'november'  => $wp_locale->get_month( 11 ),
		'december'  => $wp_locale->get_month( 12 ),
	);

	// a fix for a PHP warning to cover this particulr case.
	$date_format = 'Y-m-d H:i:s';
	$date_time   = DateTime::createFromFormat( $date_format, $date );

	if ( 'Y-m-d 23:59:59' === $return_format && $date_time && $date_time->format( $date_format ) === $date ) {
		return $date;
	}

	list( $month, $day, $year ) = explode( ' ', $date );

	// Normalize the month to lower case to match key.
	$month = strtolower( trim( $month ) );

	// Translate the month using the mapping.
	if ( array_key_exists( $month, $english_to_local_month ) ) {
		$month = $english_to_local_month[ $month ];
	}

	$day   = trim( $day, ',' );
	$month = 1 + array_search( $month, array_values( $wp_locale->month ), true );
	$time  = mktime( 0, 0, 0, $month, (int) $day, (int) $year );

	if ( 'U' === $return_format ) {
		return $time;
	}

	return date( $return_format, $time ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
}

/**
 * Sanitizes a date passed in the format of yyyy/mm/dd.
 *
 * @since  1.7.0.8
 * @version 1.8.9.1
 *
 * @param  string $date          The date to be sanitized.
 * @param  string $return_format The date format to return. Default is U (timestamp).
 * @return string|false
 */
function charitable_sanitize_date_alt_format( $date, $return_format = 'U' ) {
	global $wp_locale;

	if ( empty( $date ) || ! $date ) {
		return false;
	}

	$date_array = explode( '/', $date );

	if ( count( $date_array ) !== 3 ) {
		return false;
	}

	$day   = intval( $date_array[2] );
	$month = intval( $date_array[1] );
	$year  = intval( $date_array[0] );

	$day   = trim( $day, ',' );
	$month = trim( $month, ',' );
	$time  = mktime( 0, 0, 0, $month, (int) $day, (int) $year );

	if ( 'U' === $return_format ) {
		return $time;
	}

	return date( $return_format, $time ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
}

/**
 * Sanitizes a date passed in the format of mm/dd/yyyy for form filters.
 *
 * @since  1.7.0.11
 * @version 1.8.9.1
 *
 * @param  string $date          The date to be sanitized.
 * @return string|false
 */
function charitable_sanitize_date_filter_format( $date = false ) {

	if ( empty( $date ) || ! $date ) {
		return false;
	}

	// Convert the date to a timestamp.
	$timestamp = strtotime( $date );

	// If it's not empty and not equal to -1, then convert it to the mm/dd/yyyy format.
	$return_value = ( empty( $timestamp ) && -1 !== $timestamp ) ? '' : date( 'Y/m/d', $timestamp ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

	return $return_value;
}

/**
 * Sanitizes a date passed in the format of mm/dd/yyyy for form filters.
 *
 * @since  1.7.0.11
 * @version 1.8.9.1
 *
 * @param  string $date          The date to be sanitized.
 * @return string|false
 */
function charitable_sanitize_date_export_format( $date = false ) {

	if ( empty( $date ) || ! $date ) {
		return false;
	}

	// Convert the date to a timestamp.
	$timestamp = strtotime( $date );

	// If it's not empty and not equal to -1, then convert it to the mm/dd/yyyy format.
	$return_value = ( empty( $timestamp ) && -1 !== $timestamp ) ? '' : date( 'Y/m/d', $timestamp ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

	return $return_value;
}

/**
 * Return a string containing the correct number & type of placeholders.
 *
 * @since  1.5.0
 *
 * @param  int    $count       The number of placeholders to add.
 * @param  string $placeholder Type of placeholder to insert.
 * @return string
 */
function charitable_get_query_placeholders( $count = 1, $placeholder = '%s' ) {
	$placeholders = array_fill( 0, $count, $placeholder );
	return implode( ', ', $placeholders );
}

/**
 * Return a list of pages in id=>title format for use in a select dropdown.
 *
 * @see    get_pages
 *
 * @since  1.6.0
 *
 * @param  array $args Optional arguments to be passed to get_pages.
 * @return array
 */
function charitable_get_pages_options( $args = array() ) {
	$pages = get_pages( $args );

	if ( ! $pages ) {
		return array();
	}

	return array_combine( wp_list_pluck( $pages, 'ID' ), wp_list_pluck( $pages, 'post_title' ) );
}

/**
 * Checks whether this is localhost.
 *
 * This is not fullproof. It uses a safelist of IP addresses.
 *
 * @since  1.6.14
 * @version 1.8.9.1
 *
 * @return boolean
 */
function charitable_is_localhost() {

	if ( false !== ( defined( 'CHARITABLE_FORCE_NO_LOCALHOST_WITH_STRIPE_CONNECT' ) && CHARITABLE_FORCE_NO_LOCALHOST_WITH_STRIPE_CONNECT ) ) {
		return false;
	}

	/**
	 * Filter list of localhost IP addresses.
	 *
	 * @since 1.6.14
	 *
	 * @param array $ip_addresses The list of IP addresses.
	 */
	$safelist = apply_filters(
		'charitable_localhost_ips',
		array(
			'127.0.0.1',
			'::1',
		)
	);

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	return isset( $_SERVER['REMOTE_ADDR'] ) && in_array( $_SERVER['REMOTE_ADDR'], $safelist );
}

/**
 * Check whether we are currently using a block theme.
 *
 * @since 1.6.55
 *
 * @return boolean
 */
function charitable_is_block_theme() {
	return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
}

/**
 * Santitize to ensure we are working with a hex color (especially when working with campaign templates).
 *
 * @since  1.8.0
 *
 * @param  string  $color The color to sanitize.
 * @param  boolean $hash  Whether to include the hash in the return value.
 *
 * @return boolean
 */
function charitable_sanitize_hex( $color = '#FFFFFF', $hash = true ) {

	// Remove any spaces and special characters before and after the string.
	$color = trim( $color );

	// Remove any trailing '#' symbols from the color value.
	$color = str_replace( '#', '', $color );

	// If the string is 6 characters long then use it in pairs.
	if ( 3 === strlen( $color ) ) {
		$color = substr( $color, 0, 1 ) . substr( $color, 0, 1 ) . substr( $color, 1, 1 ) . substr( $color, 1, 1 ) . substr( $color, 2, 1 ) . substr( $color, 2, 1 );
	}

	$substr = array();
	for ( $i = 0; $i <= 5; $i++ ) {
		$default      = ( 0 == $i ) ? 'F' : ( $substr[ $i - 1 ] );
		$substr[ $i ] = substr( $color, $i, 1 );
		$substr[ $i ] = ( false === $substr[ $i ] || ! ctype_xdigit( $substr[ $i ] ) ) ? $default : $substr[ $i ];
	}
	$hex = implode( '', $substr );

	return ( ! $hash ) ? $hex : '#' . $hex;
}

/**
 * Searches the database for transients stored there that match a specific prefix.
 * Full credit to Kellen Mace and Brad Parbs for this concept.
 *
 * @since 1.8.1
 *
 * @param  string $prefix Prefix to search for.
 * @param  string $postfix_separator Separator to use for the postfix.
 *
 * @return array|bool     Nested array response for wpdb->get_results or false on failure.
 */
function charitable_search_database_for_transients_by_prefix( $prefix = '', $postfix_separator = '_' ) {

	global $wpdb;

	// Add our prefix after concating our prefix with the _transient prefix.
	$prefix = $wpdb->esc_like( '_transient_' . $prefix . $postfix_separator );

	// Build up our SQL query.
	$sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";

	// Execute our query.
	$transients = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A ); // phpcs:ignore

	// If if looks good, pass it back.
	if ( $transients && ! is_wp_error( $transients ) ) {
		return $transients;
	}

	// Otherise return false.
	return false;
}

/**
 * Expects a passed in multidimensional array of transient keys.
 *
 * Array(
 *     array( 'option_name' => '_transient_blah_blah' ),
 *     array( 'option_name' => 'transient_another_one' ),
 * )
 *
 * Can also pass in an array of transient names.
 *
 * @since 1.8.1
 *
 * @param  array|string $transients  Nested array of transients, keyed by option_name,
 *                                   or array of names of transients.
 * @return array|bool                Count of total vs deleted or false on failure.
 */
function charitable_delete_transients_from_keys( $transients = false ) {

	if ( ! isset( $transients ) || false === $transients ) {
		return false;
	}

	// If we get a string key passed in, might as well use it correctly.
	if ( is_string( $transients ) ) {
		$transients = array( array( 'option_name' => $transients ) );
	}

	// If its not an array, we can't do anything.
	if ( ! is_array( $transients ) ) {
		return false;
	}

	$results = array();

	// Loop through our transients.
	foreach ( $transients as $transient ) {

		if ( is_array( $transient ) ) {

			// If we have an array, grab the first element.
			$transient = current( $transient );
		}

		// Remove that sucker.
		$results[ $transient ] = delete_transient( str_replace( '_transient_', '', $transient ) );
	}

	// Return an array of total number, and number deleted.
	return array(
		'total'   => count( $results ),
		'deleted' => array_sum( $results ),
	);
}

/**
 * Check if AIOSEO Pro version is installed or not.
 *
 * @since 1.8.1
 *
 * @return bool
 */
function charitable_is_installed_aioseo_pro() {
	$installed_plugins = get_plugins();

	if ( array_key_exists( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php', $installed_plugins ) ) {
		return true;
	}

	return false;
}

/**
 * Check if MonsterInsights Pro version is installed or not.
 *
 * @since 1.8.1
 *
 * @return bool
 */
function charitable_is_installed_mi_pro() {
	$installed_plugins = get_plugins();

	if ( array_key_exists( 'google-analytics-premium/googleanalytics-premium.php', $installed_plugins ) ) {
		return true;
	}

	return false;
}

/**
 * Get the secret key for the crypto functions.
 *
 * @since 1.8.7
 * @version 1.8.9.1
 *
 * @return string
 */
function charitable_crypto_get_secret_key() {

	$secret_key = get_option( 'charitable_crypto_secret_key' );

	if ( charitable_is_debug() ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'charitable_crypto_get_secret_key: secret_key: ' . $secret_key );
	}

	// If we already have the secret, send it back.
	if ( false !== $secret_key ) {
		if ( charitable_is_debug() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'charitable_crypto_get_secret_key: secret_key already exists: ' . $secret_key );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			error_log( 'charitable_crypto_get_secret_key: secret_key decoded: ' . base64_decode( $secret_key ) );
		}
		return base64_decode( $secret_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	// We don't have a secret, so let's generate one.
	$secret_key = sodium_crypto_secretbox_keygen();
	add_option( 'charitable_crypto_secret_key', base64_encode( $secret_key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

	if ( charitable_is_debug() ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'charitable_crypto_get_secret_key: secret_key returned: ' . $secret_key );
	}

	return $secret_key;
}

/**
 * Encrypt a message.
 *
 * @since 1.6.1.2
 * @version 1.8.9.1
 *
 * @param string $message Message to encrypt.
 * @param string $key     Encryption key.
 *
 * @return string
 */
function charitable_crypto_encrypt( $message, $key = '' ) {

	if ( charitable_is_debug() ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'charitable_crypto_encrypt: message: ' . $message );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'charitable_crypto_encrypt: key: ' . $key );
	}

	// Create a nonce for this operation. It will be stored and recovered in the message itself.
	$nonce = random_bytes(
		SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
	);

	if ( empty( $key ) ) {
		$key = charitable_crypto_get_secret_key();
	}

	if ( charitable_is_debug() ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'charitable_crypto_encrypt: key: ' . $key );
	}

	// Encrypt message and combine with nonce.
	$cipher = base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$nonce .
		sodium_crypto_secretbox(
			$message,
			$nonce,
			$key
		)
	);

	try {
		sodium_memzero( $message );
		sodium_memzero( $key );
		if ( charitable_is_debug() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'charitable_crypto_encrypt: cipher: ' . $cipher );
		}
	} catch ( \Exception $e ) {
		if ( charitable_is_debug() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'charitable_crypto_encrypt: error: ' . $e->getMessage() );
		}
		return $cipher;
	}

	return $cipher;
}

/**
 * Decrypt a message.
 *
 * @since 1.6.1.2
 * @version 1.8.9.1
 *
 * @param string $encrypted Encrypted message.
 * @param string $key       Encryption key.
 *
 * @return string
 */
function charitable_crypto_decrypt( $encrypted, $key = '' ) {

	// Unpack base64 message.
	$decoded = base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

	if ( false === $decoded ) {
		return false;
	}

	if ( mb_strlen( $decoded, '8bit' ) < ( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) ) {
		return false;
	}

	// Pull nonce and ciphertext out of unpacked message.
	$nonce      = mb_substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
	$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

	if ( empty( $key ) ) {
		$key = charitable_crypto_get_secret_key();
	}

	// Decrypt it.
	$message = sodium_crypto_secretbox_open(
		$ciphertext,
		$nonce,
		$key
	);

	// Check for decrpytion failures.
	if ( false === $message ) {
		return false;
	}

	try {
		sodium_memzero( $ciphertext );
		sodium_memzero( $key );
		if ( charitable_is_debug() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'charitable_crypto_decrypt: message: ' . $message );
		}
	} catch ( \Exception $e ) {
		if ( charitable_is_debug() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'charitable_crypto_decrypt: error: ' . $e->getMessage() );
		}
		return $message;
	}

	return $message;
}

if ( ! function_exists( 'charitable_array_key_first' ) ) {
	/**
	 * The `array_key_first` polyfill.
	 *
	 * @since 1.8.7
	 *
	 * @param array $arr Input array.
	 *
	 * @return mixed|null
	 */
	function charitable_array_key_first( array $arr ) {

		if ( function_exists( 'array_key_first' ) ) {
			return array_key_first( $arr );
		}

		foreach ( $arr as $key => $unused ) {
			return $key;
		}

		return null;
	}
}
