<?php
/**
 * Charitable Tools - Debug Log Scanner.
 *
 * @package   Charitable/Classes/Charitable_Tools_Debug_Log_Scanner
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools_Debug_Log_Scanner' ) ) :

	/**
	 * Charitable_Tools_Debug_Log_Scanner
	 *
	 * @final
	 * @since 1.8.9.2
	 */
	class Charitable_Tools_Debug_Log_Scanner {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.8.9.2
		 *
		 * @var    Charitable_Tools_Debug_Log_Scanner|null
		 */
		private static $instance = null;

		/**
		 * Cache key for scanner results.
		 *
		 * @since 1.8.9.2
		 * @var string
		 */
		private $cache_key = 'charitable_debug_log_scanner_cache';

		/**
		 * Cache duration in seconds (5 minutes).
		 *
		 * @since 1.8.9.2
		 * @var int
		 */
		private $cache_duration = 300;

		/**
		 * Maximum file size to process (50MB).
		 *
		 * @since 1.8.9.2
		 * @var int
		 */
		const MAX_FILE_SIZE_MB = 50;

		/**
		 * Maximum lines to scan for safety.
		 *
		 * @since 1.8.9.2
		 * @var int
		 */
		const MAX_LINES_TO_SCAN = 10000;

		/**
		 * Read chunk size (1MB).
		 *
		 * @since 1.8.9.2
		 * @var int
		 */
		const READ_CHUNK_SIZE = 1048576;

		/**
		 * Set up the class.
		 *
		 * @since 1.8.9.2
		 */
		private function __construct() {
			// Private constructor for singleton
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  Charitable_Tools_Debug_Log_Scanner
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get debug log scanner summary for system info display.
		 *
		 * @since 1.8.9.2
		 *
		 * @return string
		 */
		public function get_debug_log_scanner_summary() {
			try {
				$log_file = $this->get_debug_log_path();
				$scan_info = $this->get_basic_scan_info();

				$data = "\n-- Debug Log Scanner\n\n";
				$data .= sprintf( "Debug Logging:            %s\n", $scan_info['debug_enabled'] ? 'Enabled' : 'Disabled' );
				$data .= sprintf( "Log File Location:        %s\n", $scan_info['log_file_status'] );
				$data .= sprintf( "Log File Size:            %s\n", $scan_info['file_size'] );
				$data .= sprintf( "Recent Fatal Errors:      %d (last 24h)\n", $scan_info['recent_fatals'] );
				$data .= sprintf( "Charitable Errors:        %d (last 24h)\n", $scan_info['charitable_errors'] );
				$data .= sprintf( "Last Scan:                %s\n", $scan_info['last_scan'] );

				return $data;

			} catch ( Exception $e ) {
				return "\n-- Debug Log Scanner\n\nError: Unable to run debug log scanner - " . $e->getMessage() . "\n";
			}
		}

		/**
		 * Get detailed debug log scan for AJAX response.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		public function get_detailed_debug_log_scan() {
			// Check for cached results first
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				return $cached;
			}

			$scan_results = array();
			$start_time = microtime( true );

			try {
				// Pre-flight checks
				$scan_results['pre_flight'] = $this->run_pre_flight_checks();
				if ( ! $scan_results['pre_flight']['passed'] ) {
					return $scan_results;
				}

				// Get scan timeframe (filterable)
				$hours = apply_filters( 'charitable_debug_log_scan_hours', 24 );

				// Scan for errors
				$scan_results['errors'] = $this->scan_recent_errors( $hours );
				$scan_results['statistics'] = $this->generate_error_statistics( $scan_results['errors'] );
				$scan_results['recommendations'] = $this->generate_recommendations( $scan_results );

				// Add timing and metadata
				$scan_results['execution_time'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );
				$scan_results['timestamp'] = current_time( 'Y-m-d H:i:s' );
				$scan_results['scan_timeframe'] = $hours;

				// Cache the results
				set_transient( $this->cache_key, $scan_results, $this->cache_duration );

			} catch ( Exception $e ) {
				// Enhanced error information for debugging
				$error_context = array();

				// Add debug logging status
				$error_context['wp_debug'] = defined( 'WP_DEBUG' ) && WP_DEBUG;
				$error_context['wp_debug_log'] = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;

				// Add log file information
				$log_file = $this->get_debug_log_path();
				$error_context['log_file_path'] = $log_file;
				$error_context['log_file_exists'] = $log_file ? file_exists( $log_file ) : false;

				// Add memory information
				$error_context['memory_available'] = $this->check_memory_availability();
				$error_context['php_memory_limit'] = ini_get( 'memory_limit' );

				$scan_results['error'] = array(
					'message' => 'Debug log scan failed: ' . $e->getMessage(),
					'code' => $e->getCode(),
					'file' => $e->getFile(),
					'line' => $e->getLine(),
					'context' => $error_context
				);
			}

			return $scan_results;
		}

		/**
		 * Run pre-flight safety checks.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function run_pre_flight_checks() {
			$checks = array(
				'passed' => true,
				'messages' => array()
			);

			// Check memory availability
			if ( ! $this->check_memory_availability() ) {
				$checks['passed'] = false;
				$checks['messages'][] = 'Insufficient memory available for log scanning.';
			}

			// Check debug log file
			$log_file = $this->get_debug_log_path();
			if ( ! $log_file ) {
				$checks['passed'] = false;
				$checks['messages'][] = 'WordPress debug logging not enabled or log file not found.';
				return $checks;
			}

			// Check file safety (if file exists)
			if ( file_exists( $log_file ) && ! $this->is_file_safe_to_read( $log_file ) ) {
				$checks['passed'] = false;
				$checks['messages'][] = 'Debug log file is too large for safe processing (>' . self::MAX_FILE_SIZE_MB . 'MB).';
			}

			return $checks;
		}

		/**
		 * Get debug log file path from WordPress configuration.
		 *
		 * @since 1.8.9.2
		 *
		 * @return string|false
		 */
		private function get_debug_log_path() {
			$custom_path = null;
			$default_path = WP_CONTENT_DIR . '/debug.log';

			// Check if WP_DEBUG_LOG specifies a custom path
			if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
				$custom_path = WP_DEBUG_LOG;
			}

			// If debug logging is properly enabled, return the configured path
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				return $custom_path ? $custom_path : $default_path;
			}

			// Even if debug logging is disabled, check if log files exist
			// This allows scanning historical data
			if ( $custom_path && file_exists( $custom_path ) ) {
				return $custom_path;
			}

			if ( file_exists( $default_path ) ) {
				return $default_path;
			}

			return false;
		}
	/**
	 * Get debug log scanner UI state information.
	 *
	 * @since 1.8.9.3
	 *
	 * @return array UI state information
	 */
	public function get_ui_state() {
		$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_log_enabled = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
		$log_path = $this->get_debug_log_path();
		$log_file_exists = $log_path && file_exists( $log_path );

		$state = array(
			'debug_enabled'     => $debug_enabled,
			'debug_log_enabled' => $debug_log_enabled,
			'log_path'          => $log_path,
			'log_file_exists'   => $log_file_exists,
			'show_button'       => $log_file_exists, // Show button if file exists, regardless of settings
			'button_enabled'    => $log_file_exists, // Enable button if file exists
		);

		// Determine UI message based on state
		if ( $debug_enabled && $debug_log_enabled && $log_file_exists ) {
			$state['status'] = 'ready';
			$state['message'] = '';
		} elseif ( $debug_enabled && $debug_log_enabled && ! $log_file_exists ) {
			$state['status'] = 'no_errors_yet';
			$state['message'] = __( 'Debug logging is enabled, but no error log file has been created yet. This means no errors have been logged recently.', 'charitable' );
		} elseif ( ! $debug_enabled || ! $debug_log_enabled ) {
			if ( $log_file_exists ) {
				$state['status'] = 'disabled_but_file_exists';
				$state['message'] = __( 'Debug logging is disabled, but scanning existing log file.', 'charitable' );
			} else {
				$state['status'] = 'disabled_no_file';
				$state['message'] = __( 'Debug logging is not enabled.', 'charitable' );
				$state['show_button'] = false;
				$state['button_enabled'] = false;
			}
		}

		return $state;
	}
		/**
		 * Check if file is safe to read.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $file_path Path to the log file.
		 * @return bool
		 */
		private function is_file_safe_to_read( $file_path ) {
			if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
				return false;
			}

			$file_size_mb = filesize( $file_path ) / 1024 / 1024;
			return $file_size_mb <= self::MAX_FILE_SIZE_MB;
		}

		/**
		 * Check memory availability for processing.
		 *
		 * @since 1.8.9.2
		 *
		 * @return bool
		 */
		private function check_memory_availability() {
			$memory_limit = ini_get( 'memory_limit' );
			$memory_usage = memory_get_usage( true );

			// Convert memory_limit to bytes
			$limit_bytes = $this->convert_to_bytes( $memory_limit );
			$available = $limit_bytes - $memory_usage;

			// Need at least 10MB available to proceed safely
			return $available >= ( 10 * 1024 * 1024 );
		}

		/**
		 * Convert memory limit string to bytes.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $memory_limit Memory limit string (e.g., '256M').
		 * @return int
		 */
		private function convert_to_bytes( $memory_limit ) {
			$unit = strtoupper( substr( $memory_limit, -1 ) );
			$value = (int) $memory_limit;

			switch ( $unit ) {
				case 'G':
					return $value * 1024 * 1024 * 1024;
				case 'M':
					return $value * 1024 * 1024;
				case 'K':
					return $value * 1024;
				default:
					return $value;
			}
		}

		/**
		 * Scan for recent errors in the debug log.
		 *
		 * @since 1.8.9.2
		 *
		 * @param int $hours Hours to look back.
		 * @return array
		 */
		private function scan_recent_errors( $hours ) {
			$log_file = $this->get_debug_log_path();
			$target_timestamp = time() - ( $hours * 3600 );
			$errors = array();

			// If log file doesn't exist yet, return empty array (this is normal)
			if ( ! file_exists( $log_file ) ) {
				return $errors;
			}

			try {
				$handle = fopen( $log_file, 'r' );
				if ( ! $handle ) {
					return $errors;
				}

				// Get file size and start from end
				fseek( $handle, 0, SEEK_END );
				$file_size = ftell( $handle );

				$lines_read = 0;
				$current_line = '';
				$pos = $file_size - 1;

				// Read backwards from end of file
				while ( $pos >= 0 && $lines_read < self::MAX_LINES_TO_SCAN ) {
					fseek( $handle, $pos );
					$char = fgetc( $handle );

					if ( $char === "\n" ) {
						$lines_read++;

						if ( ! empty( $current_line ) ) {
							$parsed_entry = $this->parse_log_entry( strrev( $current_line ) );

							if ( $parsed_entry ) {
								if ( $parsed_entry['timestamp'] >= $target_timestamp ) {
									$classification = $this->classify_log_entry( $parsed_entry );
									if ( $classification ) {
										$parsed_entry = array_merge( $parsed_entry, $classification );
										$errors[] = $parsed_entry;
									}
								} elseif ( $parsed_entry['timestamp'] < $target_timestamp ) {
									// Reached our time limit, stop reading
									break;
								}
							}
						}
						$current_line = '';
					} else {
						$current_line .= $char;
					}

					$pos--;
				}

				fclose( $handle );

			} catch ( Exception $e ) {
				error_log( 'Charitable debug log scanner error: ' . $e->getMessage() );
			}

			// Sort chronologically (newest first)
			usort( $errors, function( $a, $b ) {
				return $b['timestamp'] - $a['timestamp'];
			} );

			return $errors;
		}

		/**
		 * Parse a single log entry.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $line Log line to parse.
		 * @return array|null
		 */
		private function parse_log_entry( $line ) {
			// Match standard WordPress debug log format
			$pattern = '/\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC)\] PHP (.+?):(.*)/';

			if ( preg_match( $pattern, $line, $matches ) ) {
				$timestamp = strtotime( $matches[1] );
				$level = trim( $matches[2] );
				$message = trim( $matches[3] );

				// Extract file and line information if present
				$file = '';
				$line_number = '';
				if ( preg_match( '/in (.+?) on line (\d+)/', $message, $file_matches ) ) {
					$file = $file_matches[1];
					$line_number = $file_matches[2];
				}

				return array(
					'timestamp' => $timestamp,
					'datetime' => date( 'Y-m-d H:i:s', $timestamp ),
					'level' => $level,
					'message' => $message,
					'file' => $file,
					'line' => $line_number,
					'raw_line' => $line
				);
			}

			return null;
		}

		/**
		 * Classify a log entry for relevance and priority.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $entry Parsed log entry.
		 * @return array|null
		 */
		private function classify_log_entry( $entry ) {
			$message = $entry['message'];
			$file = $entry['file'] ?? '';
			$level = $entry['level'] ?? '';

			// Only process fatal errors and critical warnings
			if ( ! preg_match( '/fatal error|parse error|warning/i', $level ) ) {
				return null;
			}

			// Charitable-specific patterns (highest priority)
			if ( $this->contains_charitable_patterns( $message, $file ) ) {
				return array(
					'priority' => 100,
					'category' => 'charitable_error',
					'description' => 'Direct Charitable plugin error'
				);
			}

			// Payment gateway errors
			if ( $this->contains_gateway_patterns( $message, $file ) ) {
				return array(
					'priority' => 80,
					'category' => 'gateway_error',
					'description' => 'Payment gateway related error'
				);
			}

			// Donation context errors (during donation flow)
			if ( $this->contains_donation_context( $message ) ) {
				return array(
					'priority' => 60,
					'category' => 'donation_context',
					'description' => 'Error occurred during donation process'
				);
			}

			// Memory/resource errors
			if ( preg_match( '/memory|maximum execution time|allowed memory size/i', $message ) ) {
				return array(
					'priority' => 40,
					'category' => 'resource_error',
					'description' => 'Server resource limitation error'
				);
			}

			// Generic PHP fatal errors
			if ( preg_match( '/fatal error|parse error/i', $level ) ) {
				return array(
					'priority' => 20,
					'category' => 'php_fatal',
					'description' => 'PHP fatal error'
				);
			}

			return null;
		}

		/**
		 * Check if entry contains Charitable-specific patterns.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $message Error message.
		 * @param string $file File path.
		 * @return bool
		 */
		private function contains_charitable_patterns( $message, $file ) {
			$patterns = array(
				'/charitable/i',
				'/donation/i',
				'/campaign/i',
				'/wp-content\/plugins\/charitable/i'
			);

			$text_to_search = $message . ' ' . $file;

			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $text_to_search ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if entry contains payment gateway patterns.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $message Error message.
		 * @param string $file File path.
		 * @return bool
		 */
		private function contains_gateway_patterns( $message, $file ) {
			$patterns = array(
				'/stripe/i',
				'/paypal/i',
				'/square/i',
				'/authorize\.net/i',
				'/braintree/i',
				'/payfast/i',
				'/mollie/i',
				'/payment.*gateway/i'
			);

			$text_to_search = $message . ' ' . $file;

			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $text_to_search ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if entry contains donation context patterns.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $message Error message.
		 * @return bool
		 */
		private function contains_donation_context( $message ) {
			$patterns = array(
				'/donation.*process/i',
				'/checkout/i',
				'/payment.*form/i',
				'/donation.*form/i',
				'/wp-json.*charitable/i',
				'/charitable.*ajax/i'
			);

			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $message ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Generate error statistics.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $errors Array of errors.
		 * @return array
		 */
		private function generate_error_statistics( $errors ) {
			$stats = array(
				'total_errors' => count( $errors ),
				'by_category' => array(),
				'by_priority' => array(),
				'by_hour' => array(),
				'most_common' => array()
			);

			foreach ( $errors as $error ) {
				// Count by category
				$category = $error['category'] ?? 'unknown';
				$stats['by_category'][ $category ] = ( $stats['by_category'][ $category ] ?? 0 ) + 1;

				// Count by priority
				$priority = $error['priority'] ?? 0;
				$priority_group = $this->get_priority_group( $priority );
				$stats['by_priority'][ $priority_group ] = ( $stats['by_priority'][ $priority_group ] ?? 0 ) + 1;

				// Count by hour
				$hour = date( 'H:00', $error['timestamp'] );
				$stats['by_hour'][ $hour ] = ( $stats['by_hour'][ $hour ] ?? 0 ) + 1;

				// Track most common error messages (simplified)
				$simplified_message = $this->simplify_error_message( $error['message'] );
				$stats['most_common'][ $simplified_message ] = ( $stats['most_common'][ $simplified_message ] ?? 0 ) + 1;
			}

			// Sort most common errors
			arsort( $stats['most_common'] );
			$stats['most_common'] = array_slice( $stats['most_common'], 0, 5 );

			return $stats;
		}

		/**
		 * Get priority group name.
		 *
		 * @since 1.8.9.2
		 *
		 * @param int $priority Priority score.
		 * @return string
		 */
		private function get_priority_group( $priority ) {
			if ( $priority >= 80 ) {
				return 'Critical';
			} elseif ( $priority >= 60 ) {
				return 'High';
			} elseif ( $priority >= 40 ) {
				return 'Medium';
			} else {
				return 'Low';
			}
		}

		/**
		 * Simplify error message for grouping.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $message Original error message.
		 * @return string
		 */
		private function simplify_error_message( $message ) {
			// Remove file paths and line numbers
			$simplified = preg_replace( '/in \/[^\s]+ on line \d+/', '', $message );

			// Remove specific values that might vary
			$simplified = preg_replace( '/\b\d+\b/', '[NUMBER]', $simplified );

			// Trim and limit length
			$simplified = trim( $simplified );
			if ( strlen( $simplified ) > 100 ) {
				$simplified = substr( $simplified, 0, 100 ) . '...';
			}

			return $simplified;
		}

		/**
		 * Generate recommendations based on scan results.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $scan_results Full scan results.
		 * @return array
		 */
		private function generate_recommendations( $scan_results ) {
			$recommendations = array();

			if ( empty( $scan_results['errors'] ) ) {
				$recommendations[] = '✅ No recent fatal errors found - your site appears to be running smoothly!';
				return $recommendations;
			}

			$stats = $scan_results['statistics'];

			// Charitable-specific errors
			if ( isset( $stats['by_category']['charitable_error'] ) && $stats['by_category']['charitable_error'] > 0 ) {
				$recommendations[] = '🔴 Charitable-specific errors detected. Please share these logs with support.';
			}

			// Gateway errors
			if ( isset( $stats['by_category']['gateway_error'] ) && $stats['by_category']['gateway_error'] > 0 ) {
				$recommendations[] = '💳 Payment gateway errors found. Check your payment processor settings.';
			}

			// Memory errors
			if ( isset( $stats['by_category']['resource_error'] ) && $stats['by_category']['resource_error'] > 0 ) {
				$recommendations[] = '⚠️ Memory/resource errors detected. Consider increasing PHP memory limit.';
			}

			// High error count
			if ( $stats['total_errors'] > 10 ) {
				$recommendations[] = '📈 High error frequency detected. Consider reviewing recent changes or plugin updates.';
			}

			// General advice
			$recommendations[] = '💡 When sharing logs in forums, use the "Forum-Ready" format below to protect sensitive information.';

			return $recommendations;
		}

		/**
		 * Get basic scan info for system info summary.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function get_basic_scan_info() {
			$log_file = $this->get_debug_log_path();

			$info = array(
				'debug_enabled' => defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
				'log_file_status' => $log_file ? 'Found' : 'Not found',
				'file_size' => 'N/A',
				'recent_fatals' => 0,
				'charitable_errors' => 0,
				'last_scan' => 'Never'
			);

			if ( $log_file && file_exists( $log_file ) ) {
				$file_size_mb = round( filesize( $log_file ) / 1024 / 1024, 2 );
				$info['file_size'] = $file_size_mb . ' MB';

				// Quick scan for recent errors (lightweight version)
				try {
					$errors = $this->scan_recent_errors( 24 );
					$info['recent_fatals'] = count( $errors );

					$charitable_count = 0;
					foreach ( $errors as $error ) {
						if ( $error['category'] === 'charitable_error' ) {
							$charitable_count++;
						}
					}
					$info['charitable_errors'] = $charitable_count;

				} catch ( Exception $e ) {
					// Silent fail for basic info
				}
			}

			// Check for cached results to show last scan time
			$cached = get_transient( $this->cache_key );
			if ( $cached && isset( $cached['timestamp'] ) ) {
				$info['last_scan'] = $cached['timestamp'];
			}

			return $info;
		}

		/**
		 * Sanitize error entries for public sharing.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $errors Array of error entries.
		 * @return array
		 */
		public function sanitize_errors_for_sharing( $errors ) {
			$sanitized = array();

			foreach ( $errors as $error ) {
				$sanitized[] = array(
					'datetime' => $error['datetime'],
					'level' => $error['level'],
					'category' => $error['category'],
					'priority' => $error['priority'],
					'description' => $error['description'],
					'message' => $this->sanitize_error_message( $error['message'] ),
					'file' => $this->sanitize_file_path( $error['file'] ?? '' ),
					'line' => $error['line'] ?? ''
				);
			}

			return $sanitized;
		}

		/**
		 * Sanitize error message for public sharing.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $message Original error message.
		 * @return string
		 */
		private function sanitize_error_message( $message ) {
			$patterns = array(
				// Email addresses
				'/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL_REDACTED]',

				// IP addresses
				'/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/' => '[IP_REDACTED]',

				// Database credentials
				'/DB_[A-Z]+[\'"][^\'"]*/' => 'DB_[REDACTED]',

				// Common donor field patterns
				'/first_name[\'"\s]*[=:][\'"\s]*[^\'",\s]+/i' => 'first_name=[REDACTED]',
				'/last_name[\'"\s]*[=:][\'"\s]*[^\'",\s]+/i' => 'last_name=[REDACTED]',
				'/donor_email[\'"\s]*[=:][\'"\s]*[^\'",\s]+/i' => 'donor_email=[REDACTED]',

				// Credit card patterns
				'/\b4[0-9]{12}(?:[0-9]{3})?\b/' => '[CARD_REDACTED]',
				'/\b5[1-5][0-9]{14}\b/' => '[CARD_REDACTED]',

				// Transaction IDs
				'/(?:txn_|transaction_id|stripe_|paypal_)[a-zA-Z0-9_-]+/i' => '[TXN_REDACTED]',

				// User IDs in contexts that might be sensitive
				'/user_id[\'"\s]*[=:][\'"\s]*\d+/i' => 'user_id=[REDACTED]'
			);

			$sanitized = $message;
			foreach ( $patterns as $pattern => $replacement ) {
				$sanitized = preg_replace( $pattern, $replacement, $sanitized );
			}

			return $sanitized;
		}

		/**
		 * Sanitize file path for public sharing.
		 *
		 * @since 1.8.9.2
		 *
		 * @param string $file_path Original file path.
		 * @return string
		 */
		private function sanitize_file_path( $file_path ) {
			if ( empty( $file_path ) ) {
				return '';
			}

			// Replace absolute paths with relative paths from WordPress root
			$wp_root = ABSPATH;
			$sanitized = str_replace( $wp_root, '/', $file_path );

			// Replace common sensitive path components
			$sanitized = preg_replace( '/\/home\/[^\/]+\//', '/home/[USER]/', $sanitized );
			$sanitized = preg_replace( '/\/var\/www\/[^\/]+\//', '/var/www/[SITE]/', $sanitized );
			$sanitized = preg_replace( '/\/Applications\/[^\/]+\//', '/Applications/[APP]/', $sanitized );

			return $sanitized;
		}
	}

endif;