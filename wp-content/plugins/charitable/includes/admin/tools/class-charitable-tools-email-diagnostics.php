<?php
/**
 * Charitable Tools - Email Diagnostics.
 *
 * @package   Charitable/Classes/Charitable_Tools_Email_Diagnostics
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools_Email_Diagnostics' ) ) :

	/**
	 * Charitable_Tools_Email_Diagnostics
	 *
	 * @final
	 * @since 1.8.9.2
	 */
	class Charitable_Tools_Email_Diagnostics {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.8.9.2
		 *
		 * @var    Charitable_Tools_Email_Diagnostics|null
		 */
		private static $instance = null;

		/**
		 * Cache key for diagnostic results.
		 *
		 * @since 1.8.9.2
		 * @var string
		 */
		private $cache_key = 'charitable_email_diagnostics_cache';

		/**
		 * Cache duration in seconds (5 minutes).
		 *
		 * @since 1.8.9.2
		 * @var int
		 */
		private $cache_duration = 300;

		/**
		 * Priority emails for core functionality testing.
		 *
		 * @since 1.8.9.2
		 * @var array
		 */
		private $priority_emails = array(
			'donation_receipt',
			'offline_donation_receipt',
			'new_donation'
		);

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
		 * @return  Charitable_Tools_Email_Diagnostics
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get email diagnostics summary for system info display.
		 *
		 * @since 1.8.9.2
		 *
		 * @return string
		 */
		public function get_email_diagnostics_summary() {
			$diagnostics = $this->get_cached_basic_diagnostics();

			$data = "\n-- Email System Diagnostics\n\n";
			$data .= sprintf( "Email Health Score:       %s (%s)\n", $diagnostics['health_score'], $diagnostics['health_grade'] );
			$data .= sprintf( "Emails Registered:        %d emails\n", $diagnostics['emails_registered_count'] );
			$data .= sprintf( "Email Access Status:      %s\n", $diagnostics['email_access_status'] );
			$data .= sprintf( "Preview URLs:             %s functional\n", $diagnostics['preview_status'] );
			$data .= sprintf( "Hook Timing:              %s\n", $diagnostics['hook_timing'] );
			$data .= sprintf( "Average Access Time:      %s\n", $diagnostics['average_access_time'] );
			$data .= sprintf( "Caching Detected:         %s\n", $diagnostics['caching_detected'] );
			$data .= sprintf( "Recent Email Errors:      %d\n", $diagnostics['recent_errors'] );

			return $data;
		}

		/**
		 * Get detailed email diagnostics for AJAX response.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		public function get_detailed_email_diagnostics() {
			// Check for cached results first
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				return $cached;
			}

			$diagnostics = array();
			$start_time = microtime( true );

			try {
				// Run all diagnostic tests
				$diagnostics['email_registration'] = $this->test_email_registration();
				$diagnostics['email_access'] = $this->test_email_access();
				$diagnostics['preview_functionality'] = $this->test_preview_functionality();
				$diagnostics['hook_timing'] = $this->test_hook_timing();
				$diagnostics['environment_config'] = $this->test_environment_config();
				$diagnostics['performance'] = $this->test_performance();

				// Calculate overall health score
				$diagnostics['health_score'] = $this->calculate_health_score( $diagnostics );

				// Add timing information
				$diagnostics['execution_time'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );
				$diagnostics['timestamp'] = current_time( 'Y-m-d H:i:s' );

				// Cache the results
				set_transient( $this->cache_key, $diagnostics, $this->cache_duration );

			} catch ( Exception $e ) {
				$diagnostics['error'] = array(
					'message' => 'Diagnostic test failed: ' . $e->getMessage(),
					'code' => $e->getCode(),
					'file' => $e->getFile(),
					'line' => $e->getLine()
				);
			}

			return $diagnostics;
		}

		/**
		 * Get cached basic diagnostics for system info.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function get_cached_basic_diagnostics() {
			$cached = get_transient( $this->cache_key . '_basic' );
			if ( false !== $cached ) {
				return $cached;
			}

			try {
				$emails_registered = $this->test_email_registration();
				$email_access = $this->test_email_access();
				$preview_test = $this->test_preview_functionality();
				$hook_timing = $this->test_hook_timing();
				$environment = $this->test_environment_config();

				$basic = array(
					'health_score' => $this->calculate_basic_health_score( $emails_registered, $email_access, $preview_test ),
					'health_grade' => $this->get_health_grade( $this->calculate_basic_health_score( $emails_registered, $email_access, $preview_test ) ),
					'emails_registered_count' => count( $emails_registered['registered_emails'] ?? array() ),
					'email_access_status' => $email_access['status'] ?? 'Unknown',
					'preview_status' => sprintf( '%d/%d', $preview_test['functional_count'] ?? 0, count( $this->priority_emails ) ),
					'hook_timing' => $hook_timing['status'] ?? 'Unknown',
					'average_access_time' => sprintf( '%.1fms', $email_access['average_time'] ?? 0 ),
					'caching_detected' => implode( ', ', $environment['caching_plugins'] ?? array() ) ?: 'None',
					'recent_errors' => $this->count_recent_email_errors()
				);

				set_transient( $this->cache_key . '_basic', $basic, $this->cache_duration );
				return $basic;

			} catch ( Exception $e ) {
				return array(
					'health_score' => '0/100',
					'health_grade' => 'Grade: F',
					'emails_registered_count' => 0,
					'email_access_status' => 'Error',
					'preview_status' => '0/' . count( $this->priority_emails ),
					'hook_timing' => 'Error',
					'average_access_time' => '0.0ms',
					'caching_detected' => 'Unknown',
					'recent_errors' => 0
				);
			}
		}

		/**
		 * Test email registration functionality.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function test_email_registration() {
			$result = array(
				'test_name' => 'Email Registration',
				'status' => 'fail',
				'score' => 0,
				'max_score' => 20,
				'details' => array(),
				'registered_emails' => array(),
				'timing' => array()
			);

			try {
				$start_time = microtime( true );

				// Test helper accessibility
				$emails_helper = null;
				try {
					$emails_helper = charitable_get_helper( 'emails' );
					$result['details']['helper_accessible'] = true;
				} catch ( Exception $e ) {
					$result['details']['helper_accessible'] = false;
					$result['details']['helper_error'] = $e->getMessage();
				}

				if ( ! $emails_helper ) {
					$result['details']['error'] = 'charitable_get_helper(\'emails\') returned null or false';
					return $result;
				}

				// Test email registration via reflection
				try {
					$reflection = new ReflectionClass( $emails_helper );
					$method = $reflection->getMethod( 'ensure_emails_registered' );
					$registration_success = $method->invoke( $emails_helper );
					$result['details']['registration_method_exists'] = true;
					$result['details']['registration_successful'] = $registration_success;
				} catch ( ReflectionException $e ) {
					$result['details']['registration_method_exists'] = false;
					$result['details']['reflection_error'] = $e->getMessage();
				}

				// Test getting available emails
				$available_emails = $emails_helper->get_available_emails();
				if ( is_array( $available_emails ) && ! empty( $available_emails ) ) {
					$result['registered_emails'] = $available_emails;
					$result['details']['emails_count'] = count( $available_emails );
					$result['details']['has_priority_emails'] = $this->check_priority_emails_registered( $available_emails );

					// Check if core emails are present
					$core_emails_present = 0;
					foreach ( $this->priority_emails as $email ) {
						if ( isset( $available_emails[ $email ] ) ) {
							$core_emails_present++;
						}
					}
					$result['details']['core_emails_present'] = $core_emails_present;
					$result['details']['core_emails_total'] = count( $this->priority_emails );

					// Success if we have emails and core ones are present
					if ( $core_emails_present >= 2 ) {
						$result['status'] = 'success';
						$result['score'] = 20;
					} else {
						$result['status'] = 'partial';
						$result['score'] = 10;
					}
				} else {
					$result['details']['emails_empty'] = true;
					$result['details']['available_emails_result'] = $available_emails;
				}

				$end_time = microtime( true );
				$result['timing'] = array(
					'duration_ms' => round( ( $end_time - $start_time ) * 1000, 2 ),
					'before_init' => ! did_action( 'init' ),
					'during_init' => doing_action( 'init' ),
					'after_init' => did_action( 'init' ) && ! doing_action( 'init' )
				);

			} catch ( Exception $e ) {
				$result['details']['exception'] = $e->getMessage();
				$result['details']['exception_trace'] = $e->getTraceAsString();
			}

			return $result;
		}

		/**
		 * Test email access functionality.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function test_email_access() {
			$result = array(
				'test_name' => 'Email Access',
				'status' => 'fail',
				'score' => 0,
				'max_score' => 20,
				'details' => array(),
				'email_tests' => array(),
				'average_time' => 0
			);

			try {
				$emails_helper = charitable_get_helper( 'emails' );
				if ( ! $emails_helper ) {
					$result['details']['error'] = 'Email helper not available';
					return $result;
				}

				$total_time = 0;
				$successful_accesses = 0;
				$failed_accesses = 0;

				foreach ( $this->priority_emails as $email_id ) {
					$email_test = array(
						'email_id' => $email_id,
						'accessible' => false,
						'class_exists' => false,
						'instantiable' => false,
						'timing_ms' => 0
					);

					$start_time = microtime( true );

					try {
						// Test getting email class
						$email_class = $emails_helper->get_email( $email_id );
						$email_test['class_name'] = $email_class;
						$email_test['class_retrieved'] = ! empty( $email_class );

						if ( $email_class && class_exists( $email_class ) ) {
							$email_test['class_exists'] = true;

							// Test instantiation
							try {
								$email_instance = new $email_class();
								$email_test['instantiable'] = true;
								$email_test['accessible'] = true;

								// Test basic methods
								if ( method_exists( $email_instance, 'get_email_id' ) ) {
									$email_test['get_id_works'] = $email_instance->get_email_id() === $email_id;
								}
								if ( method_exists( $email_instance, 'get_name' ) ) {
									$email_test['has_name'] = ! empty( $email_instance->get_name() );
								}

								// Phase 1 Integration: Test recipient extraction with real donation data
								$email_test['recipient_extraction'] = $this->test_recipient_extraction_for_email( $email_instance, $email_id );

								$successful_accesses++;
							} catch ( Exception $e ) {
								$email_test['instantiation_error'] = $e->getMessage();
								$failed_accesses++;
							}
						} else {
							$email_test['class_missing'] = true;
							$failed_accesses++;
						}

					} catch ( Exception $e ) {
						$email_test['access_error'] = $e->getMessage();
						$failed_accesses++;
					}

					$end_time = microtime( true );
					$email_test['timing_ms'] = round( ( $end_time - $start_time ) * 1000, 2 );
					$total_time += $email_test['timing_ms'];

					$result['email_tests'][ $email_id ] = $email_test;
				}

				// Calculate results
				$result['details']['successful_accesses'] = $successful_accesses;
				$result['details']['failed_accesses'] = $failed_accesses;
				$result['details']['total_emails_tested'] = count( $this->priority_emails );
				$result['average_time'] = round( $total_time / count( $this->priority_emails ), 1 );

				// Phase 1 Integration: Count recipient extraction failures
				$recipient_failures = 0;
				foreach ( $result['email_tests'] as $email_test ) {
					if ( isset( $email_test['recipient_extraction']['tested'] ) &&
						 $email_test['recipient_extraction']['tested'] &&
						 ! $email_test['recipient_extraction']['success'] ) {
						$recipient_failures++;
					}
				}
				$result['details']['recipient_extraction_failures'] = $recipient_failures;

				// Determine status and score
				if ( $successful_accesses === count( $this->priority_emails ) ) {
					$result['status'] = 'success';
					$result['score'] = 20;

					// Reduce score for recipient extraction failures
					if ( $recipient_failures > 0 ) {
						$result['score'] -= ( $recipient_failures * 3 ); // -3 points per failure
						$result['status'] = 'partial';
						$result['details']['recipient_warning'] = "Recipient extraction failing for {$recipient_failures} email(s)";
					}
				} elseif ( $successful_accesses > 0 ) {
					$result['status'] = 'partial';
					$result['score'] = round( ( $successful_accesses / count( $this->priority_emails ) ) * 20 );

					// Additional penalty for recipient failures
					if ( $recipient_failures > 0 ) {
						$result['score'] -= ( $recipient_failures * 2 );
					}
				}

				// Performance check
				if ( $result['average_time'] > 100 ) {
					$result['details']['performance_warning'] = 'Average access time exceeds 100ms';
					$result['score'] = max( 0, $result['score'] - 5 ); // Penalty for slow access
				}

			} catch ( Exception $e ) {
				$result['details']['exception'] = $e->getMessage();
			}

			return $result;
		}

		/**
		 * Test email preview functionality.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function test_preview_functionality() {
			$result = array(
				'test_name' => 'Preview Functionality',
				'status' => 'fail',
				'score' => 0,
				'max_score' => 15,
				'details' => array(),
				'preview_tests' => array(),
				'functional_count' => 0
			);

			try {
				$functional_previews = 0;

				foreach ( $this->priority_emails as $email_id ) {
					$preview_test = array(
						'email_id' => $email_id,
						'url_generated' => false,
						'endpoint_accessible' => false,
						'preview_works' => false
					);

					try {
						// Generate preview URL
						$preview_url = add_query_arg(
							array(
								'charitable_action' => 'preview_email',
								'email_id' => $email_id,
								'_nonce' => wp_create_nonce( 'email_preview' )
							),
							site_url()
						);

						$preview_test['url_generated'] = true;
						$preview_test['preview_url'] = $preview_url;

						// Test endpoint accessibility (HEAD request only - no actual email sending)
						$response = wp_remote_head( $preview_url, array(
							'timeout' => 10,
							'user-agent' => 'Charitable Email Diagnostics'
						) );

						if ( ! is_wp_error( $response ) ) {
							$status_code = wp_remote_retrieve_response_code( $response );
							$preview_test['status_code'] = $status_code;
							$preview_test['endpoint_accessible'] = true;

							// Check for successful response or redirect (both are acceptable for previews)
							if ( in_array( $status_code, array( 200, 301, 302 ) ) ) {
								$preview_test['preview_works'] = true;
								$functional_previews++;
							}
						} else {
							$preview_test['endpoint_error'] = $response->get_error_message();
						}

					} catch ( Exception $e ) {
						$preview_test['exception'] = $e->getMessage();
					}

					$result['preview_tests'][ $email_id ] = $preview_test;
				}

				$result['functional_count'] = $functional_previews;
				$result['details']['total_tested'] = count( $this->priority_emails );

				// Determine status and score
				if ( $functional_previews === count( $this->priority_emails ) ) {
					$result['status'] = 'success';
					$result['score'] = 15;
				} elseif ( $functional_previews > 0 ) {
					$result['status'] = 'partial';
					$result['score'] = round( ( $functional_previews / count( $this->priority_emails ) ) * 15 );
				}

			} catch ( Exception $e ) {
				$result['details']['exception'] = $e->getMessage();
			}

			return $result;
		}

		/**
		 * Test hook timing and initialization order.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function test_hook_timing() {
			$result = array(
				'test_name' => 'Hook Timing',
				'status' => 'success',
				'score' => 15,
				'max_score' => 15,
				'details' => array()
			);

			try {
				// Check WordPress hook status
				$result['details']['init_completed'] = did_action( 'init' );
				$result['details']['admin_init_completed'] = did_action( 'admin_init' );
				$result['details']['currently_doing_init'] = doing_action( 'init' );
				$result['details']['currently_doing_admin_init'] = doing_action( 'admin_init' );

				// Check current action context
				$current_action = current_action();
				$result['details']['current_action'] = $current_action ?: 'none';

				// Check plugin load order
				$active_plugins = get_option( 'active_plugins', array() );
				$charitable_position = false;
				foreach ( $active_plugins as $index => $plugin ) {
					if ( strpos( $plugin, 'charitable' ) !== false ) {
						$charitable_position = $index;
						break;
					}
				}
				$result['details']['charitable_plugin_position'] = $charitable_position;
				$result['details']['total_active_plugins'] = count( $active_plugins );

				// Check for timing conflicts
				$timing_issues = array();

				if ( ! did_action( 'init' ) && ! doing_action( 'init' ) ) {
					$timing_issues[] = 'Emails accessed before init hook';
					$result['score'] -= 5;
				}

				if ( doing_action( 'plugins_loaded' ) ) {
					$timing_issues[] = 'Accessing during plugins_loaded (may cause timing issues)';
					$result['score'] -= 3;
				}

				// Check for AJAX context
				if ( wp_doing_ajax() ) {
					$result['details']['ajax_context'] = true;
					// AJAX is actually fine for email diagnostics
				}

				// Check for cron context
				if ( wp_doing_cron() ) {
					$result['details']['cron_context'] = true;
					$timing_issues[] = 'Running in cron context (may have limited functionality)';
					$result['score'] -= 2;
				}

				$result['details']['timing_issues'] = $timing_issues;

				// Overall status
				if ( empty( $timing_issues ) ) {
					$result['status'] = 'success';
				} elseif ( $result['score'] > 10 ) {
					$result['status'] = 'warning';
				} else {
					$result['status'] = 'fail';
				}

			} catch ( Exception $e ) {
				$result['details']['exception'] = $e->getMessage();
				$result['status'] = 'fail';
				$result['score'] = 0;
			}

			return $result;
		}

		/**
		 * Test environment configuration.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function test_environment_config() {
			$result = array(
				'test_name' => 'Environment Configuration',
				'status' => 'success',
				'score' => 15,
				'max_score' => 15,
				'details' => array(),
				'caching_plugins' => array(),
				'environment_info' => array()
			);

			try {
				// Detect caching plugins
				$known_caching_plugins = array(
					'wp-rocket/wp-rocket.php' => 'WP Rocket',
					'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
					'wp-super-cache/wp-cache.php' => 'WP Super Cache',
					'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
					'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
					'hummingbird-performance/wp-hummingbird.php' => 'Hummingbird',
					'autoptimize/autoptimize.php' => 'Autoptimize'
				);

				$active_plugins = get_option( 'active_plugins', array() );
				foreach ( $known_caching_plugins as $plugin_file => $plugin_name ) {
					if ( in_array( $plugin_file, $active_plugins ) ) {
						$result['caching_plugins'][] = $plugin_name;
					}
				}

				// Check object cache
				$result['details']['object_cache_enabled'] = wp_using_ext_object_cache();

				// Check OPcache
				$result['details']['opcache_enabled'] = function_exists( 'opcache_get_status' ) && opcache_get_status();

				// Environment information
				$result['environment_info'] = array(
					'php_version' => PHP_VERSION,
					'wordpress_version' => get_bloginfo( 'version' ),
					'charitable_version' => charitable()->get_version(),
					'memory_limit' => ini_get( 'memory_limit' ),
					'max_execution_time' => ini_get( 'max_execution_time' ),
					'is_multisite' => is_multisite(),
					'debug_enabled' => defined( 'WP_DEBUG' ) && WP_DEBUG,
					'charitable_debug' => defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG
				);

				// Check email environment
				$result['details']['smtp_configured'] = $this->is_smtp_configured();
				$result['details']['mail_function_available'] = function_exists( 'mail' );
				$result['details']['wp_mail_available'] = function_exists( 'wp_mail' );

				// Score adjustments
				if ( ! empty( $result['caching_plugins'] ) ) {
					$result['details']['caching_warning'] = 'Caching plugins detected - may affect email timing';
					$result['score'] -= 2;
				}

				if ( ! $result['details']['wp_mail_available'] ) {
					$result['details']['mail_error'] = 'wp_mail function not available';
					$result['score'] -= 5;
					$result['status'] = 'warning';
				}

			} catch ( Exception $e ) {
				$result['details']['exception'] = $e->getMessage();
				$result['status'] = 'fail';
				$result['score'] = 0;
			}

			return $result;
		}

		/**
		 * Test performance metrics.
		 *
		 * @since 1.8.9.2
		 *
		 * @return array
		 */
		private function test_performance() {
			$result = array(
				'test_name' => 'Performance',
				'status' => 'success',
				'score' => 15,
				'max_score' => 15,
				'details' => array(),
				'metrics' => array()
			);

			try {
				$start_time = microtime( true );
				$start_memory = memory_get_usage( true );

				// Test email helper performance
				$helper_start = microtime( true );
				$emails_helper = charitable_get_helper( 'emails' );
				$helper_time = ( microtime( true ) - $helper_start ) * 1000;

				// Test email registration performance
				$reg_start = microtime( true );
				if ( $emails_helper ) {
					$available_emails = $emails_helper->get_available_emails();
					$reg_time = ( microtime( true ) - $reg_start ) * 1000;
				} else {
					$reg_time = 0;
					$result['details']['helper_unavailable'] = true;
				}

				// Calculate metrics
				$total_time = ( microtime( true ) - $start_time ) * 1000;
				$memory_used = memory_get_usage( true ) - $start_memory;

				$result['metrics'] = array(
					'helper_access_time_ms' => round( $helper_time, 2 ),
					'registration_time_ms' => round( $reg_time, 2 ),
					'total_time_ms' => round( $total_time, 2 ),
					'memory_used_kb' => round( $memory_used / 1024, 2 ),
					'peak_memory_mb' => round( memory_get_peak_usage( true ) / 1024 / 1024, 2 )
				);

				// Performance scoring
				if ( $helper_time > 50 ) {
					$result['details']['slow_helper'] = 'Email helper access > 50ms';
					$result['score'] -= 3;
				}

				if ( $reg_time > 100 ) {
					$result['details']['slow_registration'] = 'Email registration > 100ms';
					$result['score'] -= 5;
				}

				if ( $memory_used > 1024 * 1024 ) { // 1MB
					$result['details']['high_memory'] = 'High memory usage during email operations';
					$result['score'] -= 2;
				}

				// Overall status
				if ( $result['score'] >= 13 ) {
					$result['status'] = 'excellent';
				} elseif ( $result['score'] >= 10 ) {
					$result['status'] = 'good';
				} elseif ( $result['score'] >= 7 ) {
					$result['status'] = 'fair';
				} else {
					$result['status'] = 'poor';
				}

			} catch ( Exception $e ) {
				$result['details']['exception'] = $e->getMessage();
				$result['status'] = 'fail';
				$result['score'] = 0;
			}

			return $result;
		}

		/**
		 * Calculate overall health score from diagnostic results.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $diagnostics Full diagnostic results.
		 * @return array Health score details.
		 */
		private function calculate_health_score( $diagnostics ) {
			$total_score = 0;
			$max_score = 100;
			$category_scores = array();

			// Extract scores from each category
			$categories = array(
				'email_registration',
				'email_access',
				'preview_functionality',
				'hook_timing',
				'environment_config',
				'performance'
			);

			foreach ( $categories as $category ) {
				if ( isset( $diagnostics[ $category ]['score'] ) ) {
					$score = (int) $diagnostics[ $category ]['score'];
					$max = (int) $diagnostics[ $category ]['max_score'];
					$total_score += $score;
					$category_scores[ $category ] = array(
						'score' => $score,
						'max_score' => $max,
						'percentage' => $max > 0 ? round( ( $score / $max ) * 100 ) : 0
					);
				}
			}

			$percentage = round( ( $total_score / $max_score ) * 100 );
			$grade = $this->get_health_grade( $percentage );

			return array(
				'total_score' => $total_score,
				'max_score' => $max_score,
				'percentage' => $percentage,
				'grade' => $grade,
				'category_scores' => $category_scores,
				'interpretation' => $this->get_score_interpretation( $percentage )
			);
		}

		/**
		 * Calculate basic health score for system info display.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $email_registration Email registration test results.
		 * @param array $email_access Email access test results.
		 * @param array $preview_test Preview functionality test results.
		 * @return int Health score percentage.
		 */
		private function calculate_basic_health_score( $email_registration, $email_access, $preview_test ) {
			$total_score = 0;
			$max_score = 55; // Registration (20) + Access (20) + Preview (15)

			$total_score += $email_registration['score'] ?? 0;
			$total_score += $email_access['score'] ?? 0;
			$total_score += $preview_test['score'] ?? 0;

			return round( ( $total_score / $max_score ) * 100 );
		}

		/**
		 * Get health grade based on percentage.
		 *
		 * @since 1.8.9.2
		 *
		 * @param int $percentage Health score percentage.
		 * @return string Grade letter with descriptive text.
		 */
		private function get_health_grade( $percentage ) {
			if ( $percentage >= 90 ) {
				return 'Grade: A';
			} elseif ( $percentage >= 80 ) {
				return 'Grade: B';
			} elseif ( $percentage >= 70 ) {
				return 'Grade: C';
			} elseif ( $percentage >= 60 ) {
				return 'Grade: D';
			} else {
				return 'Grade: F';
			}
		}

		/**
		 * Get score interpretation and recommendations.
		 *
		 * @since 1.8.9.2
		 *
		 * @param int $percentage Health score percentage.
		 * @return string Interpretation text.
		 */
		private function get_score_interpretation( $percentage ) {
			if ( $percentage >= 90 ) {
				return 'Excellent: Email system is functioning optimally with no issues detected.';
			} elseif ( $percentage >= 80 ) {
				return 'Good: Email system is working well with minor issues that do not affect core functionality.';
			} elseif ( $percentage >= 70 ) {
				return 'Fair: Email system has some issues that may affect performance or reliability.';
			} elseif ( $percentage >= 60 ) {
				return 'Poor: Email system has significant issues that need attention.';
			} else {
				return 'Critical: Email system has major problems that require immediate investigation.';
			}
		}

		/**
		 * Check if priority emails are registered.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $available_emails Available emails array.
		 * @return bool True if priority emails are registered.
		 */
		private function check_priority_emails_registered( $available_emails ) {
			foreach ( $this->priority_emails as $email ) {
				if ( ! isset( $available_emails[ $email ] ) ) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Check if SMTP is configured.
		 *
		 * @since 1.8.9.2
		 *
		 * @return bool True if SMTP appears to be configured.
		 */
		private function is_smtp_configured() {
			// Check for common SMTP plugins
			$smtp_plugins = array(
				'wp-mail-smtp/wp_mail_smtp.php',
				'easy-wp-smtp/easy-wp-smtp.php',
				'post-smtp/postman-smtp.php',
				'wp-smtp/wp-smtp.php'
			);

			$active_plugins = get_option( 'active_plugins', array() );
			foreach ( $smtp_plugins as $plugin ) {
				if ( in_array( $plugin, $active_plugins ) ) {
					return true;
				}
			}

			// Check for manual SMTP configuration
			if ( defined( 'WPMS_ON' ) && WPMS_ON ) {
				return true;
			}

			return false;
		}

		/**
		 * Count recent email errors from logs.
		 *
		 * @since 1.8.9.2
		 * @since 1.8.9.5 Enhanced to connect with Phase 1 error logging improvements.
		 *
		 * @return int Number of recent email errors.
		 */
		private function count_recent_email_errors() {
			$error_count = 0;

			try {
				// Connect to Phase 1 enhanced error logging via activities table (Pro only)
				$registered_tables = (array) apply_filters( 'charitable_db_tables', array() );
				if ( function_exists( 'charitable_get_table' ) && array_key_exists( 'charitable_activities', $registered_tables ) ) {
					$activities_table = charitable_get_table( 'charitable_activities' );
					if ( $activities_table ) {
						global $wpdb;
						$table_name = $activities_table->get_table_name();
						$seven_days_ago = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

						// Count email failures from the last 7 days
						$error_count = (int) $wpdb->get_var( $wpdb->prepare(
							"SELECT COUNT(*) FROM {$table_name}
							 WHERE type = 'form_error'
							 AND error_type = 'email_failure'
							 AND timestamp >= %s",
							$seven_days_ago
						) );
					}
				}
			} catch ( Exception $e ) {
				// Fail silently for diagnostics - don't break the diagnostic system
				error_log( 'Charitable Email Diagnostics: Error counting recent email errors - ' . $e->getMessage() );
			}

			return $error_count;
		}

		/**
		 * Test recipient extraction for a specific email with real donation data.
		 *
		 * @since 1.8.9.5 Phase 1 Integration
		 * @since 1.8.10 Handle array recipients to prevent fatal TypeError in is_email().
		 *
		 * @param object $email_instance Email instance to test.
		 * @param string $email_id Email ID being tested.
		 * @return array Test results.
		 */
		private function test_recipient_extraction_for_email( $email_instance, $email_id ) {
			$extraction_test = array(
				'tested' => false,
				'success' => false,
				'recipient_valid' => false,
				'recipient_value' => '',
				'donation_context' => 'none',
				'error' => null
			);

			try {
				// Skip recipient extraction test for emails that don't need donations
				$non_donation_emails = array( 'password_reset', 'email_verification' );
				if ( in_array( $email_id, $non_donation_emails ) ) {
					$extraction_test['tested'] = true;
					$extraction_test['success'] = true;
					$extraction_test['recipient_value'] = 'N/A - No donation required';
					return $extraction_test;
				}

				// Find a recent completed donation for testing
				$recent_donations = get_posts( array(
					'post_type' => 'donation',
					'post_status' => 'charitable-completed',
					'posts_per_page' => 1,
					'orderby' => 'date',
					'order' => 'DESC'
				) );

				if ( empty( $recent_donations ) ) {
					$extraction_test['donation_context'] = 'no_donations';
					$extraction_test['error'] = 'No completed donations available for testing';
					return $extraction_test;
				}

				$donation = charitable_get_donation( $recent_donations[0]->ID );
				if ( ! $donation ) {
					$extraction_test['donation_context'] = 'invalid_donation';
					$extraction_test['error'] = 'Could not load donation object';
					return $extraction_test;
				}

				$extraction_test['donation_context'] = 'donation_' . $donation->get_donation_id();
				$extraction_test['tested'] = true;

				// Create new email instance with donation context
				$email_class = get_class( $email_instance );
				$test_email = new $email_class( array( 'donation' => $donation ) );

				// Test recipient extraction
				if ( method_exists( $test_email, 'get_recipient' ) ) {
					$recipient = $test_email->get_recipient();
					$extraction_test['recipient_value'] = $recipient;
					if ( is_array( $recipient ) ) {
						$extraction_test['recipient_valid'] = ! empty( $recipient ) && ( array_filter( $recipient, 'is_email' ) === $recipient );
						$extraction_test['success'] = ! empty( $recipient );
					} else {
						$extraction_test['recipient_valid'] = is_email( $recipient );
						$extraction_test['success'] = ! empty( $recipient ) && is_email( $recipient );
					}
				} else {
					$extraction_test['error'] = 'get_recipient method not available';
				}

			} catch ( Exception $e ) {
				$extraction_test['error'] = $e->getMessage();
				$extraction_test['exception_file'] = basename( $e->getFile() );
				$extraction_test['exception_line'] = $e->getLine();
			}

			return $extraction_test;
		}

		/**
		 * Clear diagnostic cache.
		 *
		 * @since 1.8.9.2
		 *
		 * @return bool True if cache was cleared.
		 */
		public function clear_cache() {
			$result1 = delete_transient( $this->cache_key );
			$result2 = delete_transient( $this->cache_key . '_basic' );
			return $result1 || $result2;
		}
	}

endif;