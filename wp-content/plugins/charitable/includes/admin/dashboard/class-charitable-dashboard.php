<?php
/**
 * Charitable Dashboard class.
 *
 * @package   Charitable/Classes/Charitable_Dashboard
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.9.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Dashboard' ) ) :

	/**
	 * Charitable_Dashboard
	 *
	 * @final
	 * @since 1.8.8
	 */
	final class Charitable_Dashboard {

	/**
	 * ========================================
	 * TEMPORARY TESTING MODE - EASY REVERT
	 * ========================================
	 *
	 * To show empty dashboard state for testing:
	 * - Set SHOW_EMPTY_DASHBOARD = true
	 *
	 * To revert to normal behavior:
	 * - Set SHOW_EMPTY_DASHBOARD = false
	 * - OR delete this entire constant block
	 *
	 * This affects: stats, campaigns, donations, donors, comments
	 * ========================================
	 */
	const SHOW_EMPTY_DASHBOARD = false;

	/**
	 * The single instance of this class.
	 *
	 * @var  Charitable_Dashboard|null
	 */
	private static $instance = null;

	/**
	 * Source of blog posts content.
	 *
	 * @since 1.8.8
	 *
	 * @var string
	 */
	const BLOG_POSTS_SOURCE_URL = 'https://plugin.wpcharitable.com/wp-content/charitable-blog-posts.json';

	/**
	 * Cached version information from the API.
	 *
	 * @since 1.8.8
	 *
	 * @var array|null
	 */
	private $version_info = null;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.8
		 */
		public function __construct() {
			// Constructor - AJAX handlers are registered in charitable-admin-hooks.php

			// Hook into Charitable's cache clearing mechanism
			add_action( 'charitable_after_clear_expired_options', array( $this, 'clear_dashboard_stats_cache' ) );
		}


		/**
		 * Render the dashboard new page.
		 *
		 * @since   1.8.8
		 * @version 1.8.8.1
		 */
		public function render_dashboard_new() {
			?>
			<div id="charitable-dashboard-v2" class="wrap">
				<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

				<?php do_action( 'charitable_maybe_show_notification' ); ?>

				<?php
				/**
				 * Do or render something right before the dashboard new area.
				 *
				 * @since 1.8.8
				 */
				do_action( 'charitable_before_admin_dashboard_v2' );
				?>

				<?php $this->render_dashboard_header(); ?>

				<?php $this->render_dashboard_notifications(); ?>

				<div class="charitable-dashboard-v2-content">
					<div class="charitable-dashboard-v2-layout">
						<!-- Left Column -->
						<div class="charitable-dashboard-v2-left-column">
							<?php $this->render_stats_section(); ?>
							<?php $this->render_tabs_section(); ?>
							<?php $this->render_upgrade_section(); ?>
							<?php
							// Show Quick Access in left column for Pro users (when upgrade section is hidden)
							if ( charitable_is_pro() ) {
								$this->render_quick_access_section();
							}
							?>
						</div>

						<!-- Right Column -->
						<div class="charitable-dashboard-v2-right-column">
							<?php $this->render_enhance_campaign_section(); ?>
							<?php $this->render_latest_updates_section(); ?>
							<?php
							// Show Quick Access in right column for non-Pro users
							if ( ! charitable_is_pro() ) {
								$this->render_quick_access_section();
							}
							?>
						</div>
					</div>
				</div>

				<?php
				/**
				 * Do or render something right after the dashboard new area.
				 *
				 * @since 1.8.8
				 */
				do_action( 'charitable_after_admin_dashboard_v2' );
				?>

				<?php $this->render_dashboard_scripts(); ?>
			</div>
			<?php
		}


		/**
		 * Render the dashboard header section.
		 *
		 * @since 1.8.8
		 */
		public function render_dashboard_header() {
			?>
			<div class="charitable-dashboard-v2-header">
				<div class="charitable-dashboard-v2-header-content">
					<div class="charitable-dashboard-v2-header-left">
						<h1><?php echo esc_html__( 'Overview', 'charitable' ); ?></h1>
					</div>
					<div class="charitable-dashboard-v2-header-right">
						<?php if ( charitable_disable_legacy_campaigns() ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="button button-primary charitable-button">
								<?php echo esc_html__( '+ Add New Campaign', 'charitable' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" class="button button-primary charitable-button">
								<?php echo esc_html__( '+ Add New Campaign', 'charitable' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render dashboard notifications.
		 *
		 * @since 1.8.8
		 */
		public function render_dashboard_notifications() {
			// Get dashboard notifications
			$notifications = get_option( 'charitable_dashboard_notifications', array() );

			if ( empty( $notifications ) || ! is_array( $notifications ) ) {
				return;
			}

			// Remove any notifications that don't have a 'type' key
			$notifications = array_filter(
				$notifications,
				function ( $notification ) {
					return isset( $notification['type'] );
				}
			);

			// Remove any notifications that have a 'dismissed' key
			$notifications = array_filter(
				$notifications,
				function ( $notification ) {
					return ! isset( $notification['dismissed'] );
				}
			);

			if ( empty( $notifications ) ) {
				return;
			}

			// Sort notifications by type = 'error' first, then 'warning', followed by 'notice'.
			// Within the same type, sort by priority (lower number = shown first, default 10).
			uasort(
				$notifications,
				function ( $a, $b ) {
					$types = array( 'error', 'warning', 'notice' );
					$pos_a = array_search( $a['type'], $types );
					$pos_b = array_search( $b['type'], $types );

					if ( $pos_a !== $pos_b ) {
						return $pos_a - $pos_b;
					}

					$priority_a = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
					$priority_b = isset( $b['priority'] ) ? (int) $b['priority'] : 10;

					return $priority_a - $priority_b;
				}
			);

			$notifications_count = 1;
			$notifications_total = count( $notifications );

			?>
			<div class="charitable-container charitable-dashboard-notifications charitable-dashboard-v2-notifications">
				<div class="charitable-dashboard-notification-bar">
					<?php if ( (int) $notifications_total > 1 ) : ?>
						<div class="charitable-dashboard-notification-navigation">
							<a class="prev">
								<span class="screen-reader-text"><?php esc_attr_e( 'Previous message', 'charitable' ); ?></span>
								<span aria-hidden="true">&lsaquo;</span>
							</a>
							<span class="charitable-dashboard-notification-counter">
								<?php
								printf(
									/* translators: 1: current notification number, 2: total notifications */
									esc_html__( '%1$d of %2$d', 'charitable' ),
									1,
									(int) $notifications_total
								);
								?>
							</span>
							<a class="next">
								<span class="screen-reader-text"><?php esc_attr_e( 'Next message', 'charitable' ); ?></span>
								<span aria-hidden="true">&rsaquo;</span>
							</a>
						</div>
					<?php else : ?>
						<div class="charitable-dashboard-notification-navigation"></div>
					<?php endif; ?>

					<a href="#" class="charitable-remove-dashboard-notification"></a>
				</div>

				<?php foreach ( $notifications as $notification_slug => $notification ) : ?>
					<?php
					$css_class     = ! empty( $notification['custom_css'] ) ? $notification['custom_css'] : '';
					$css_class    .= $notifications_count === 1 ? '' : ' charitable-hidden';
					$message_title = ! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : esc_html__( 'Important', 'charitable' );
					$message       = ! empty( $notification['message'] ) ? $notification['message'] : '';
					$message       = wp_kses(
						$message,
						array(
							'a'      => array(
								'href'  => array(),
								'title' => array(),
							),
							'strong' => array(),
							'p'      => array(),
							'ol'     => array(),
							'ul'     => array(),
							'li'     => array(),
							'br'     => array(),
							'h1'     => array(),
							'h2'     => array(),
							'h3'     => array(),
							'h4'     => array(),
							'h5'     => array(),
						)
					);
					$notification_type = ! empty( $notification['type'] ) ? $notification['type'] : 'notice';
					$type_icons        = array(
						'notice'  => 'dashicons-info',
						'warning' => 'dashicons-warning',
						'error'   => 'dashicons-dismiss',
					);
					$icon_class        = isset( $type_icons[ $notification_type ] ) ? $type_icons[ $notification_type ] : 'dashicons-info';
					$button_url        = ! empty( $notification['button_url'] ) ? esc_url( $notification['button_url'] ) : '';
					$button_text       = ! empty( $notification['button_text'] ) ? sanitize_text_field( $notification['button_text'] ) : '';
					?>

					<div class="charitable-dashboard-notification <?php echo esc_attr( $css_class ); ?>" data-notification-number="<?php echo (int) $notifications_count; ?>" data-notification-id="<?php echo esc_attr( $notification_slug ); ?>" data-notification-type="<?php echo esc_attr( $notification_type ); ?>">
						<div class="charitable-dashboard-notification-message">
							<h4 class="charitable-dashboard-notification-headline">
								<span class="dashicons <?php echo esc_attr( $icon_class ); ?> charitable-notification-type-icon"></span>
								<?php echo esc_html( $message_title ); ?>
							</h4>
							<?php echo $message; // phpcs:ignore ?>
							<?php if ( $button_url && $button_text ) : ?>
								<a href="<?php echo esc_url( $button_url ); ?>" class="charitable-notification-cta-button"><?php echo esc_html( $button_text ); ?></a>
							<?php endif; ?>
						</div>
					</div>

					<?php ++$notifications_count; ?>
				<?php endforeach; ?>
			</div>
			<?php
		}


		/**
		 * Render the stats section with time period filter and chart.
		 *
		 * @since 1.8.8
		 */
		public function render_stats_section() {
			?>
			<section class="charitable-dashboard-v2-section charitable-dashboard-v2-stats-row-section">
				<div class="charitable-dashboard-v2-header-bar">
					<div class="charitable-dashboard-v2-header-bar-left">
						<select name="time-period" id="time-period-filter">
							<option value="last-7-days" selected><?php esc_html_e( 'Last 7 Days', 'charitable' ); ?></option>
							<option value="last-14-days"><?php esc_html_e( 'Last 14 Days', 'charitable' ); ?></option>
							<option value="last-30-days"><?php esc_html_e( 'Last 30 Days', 'charitable' ); ?></option>
							<?php /* <option value="this-month">This Month</option>
							<option value="last-3-months">Last 3 Months</option>
							<option value="last-6-months">Last 6 Months</option>
							<option value="last-year">Last Year</option>
							<option value="custom">Custom Range</option> */ ?>
						</select>
					</div>
					<div class="charitable-dashboard-v2-header-bar-right">
						<?php /* <button class="charitable-dashboard-v2-download-button">
							<?php esc_html_e( 'Download Summary', 'charitable' ); ?>
							<svg class="charitable-dashboard-v2-download-icon" width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7.85908 10.5L3.45604 6.125L4.68889 4.85625L6.97847 7.13125V0H8.73969V7.13125L11.0293 4.85625L12.2621 6.125L7.85908 10.5ZM2.57543 14C2.09109 14 1.67647 13.8286 1.33157 13.4859C0.986662 13.1432 0.814209 12.7312 0.814209 12.25V9.625H2.57543V12.25H13.1427V9.625H14.904V12.25C14.904 12.7312 14.7315 13.1432 14.3866 13.4859C14.0417 13.8286 13.6271 14 13.1427 14H2.57543Z" fill="#0E2121" fill-opacity="0.6"/>
							</svg>
						</button> */ ?>

						<?php
						// Get current date range for print functionality
						// phpcs:disable WordPress.Security.NonceVerification.Recommended
						$time_period = isset( $_GET['time-period'] ) ? sanitize_text_field( wp_unslash( $_GET['time-period'] ) ) : 'last-7-days';
						// phpcs:enable WordPress.Security.NonceVerification.Recommended
						$date_range = $this->get_date_range_for_period( $time_period );
						?>
						<form action="" method="post" target="_blank" class="charitable-report-print" id="charitable-dashboard-v2-print">
							<input name="charitable_report_action" type="hidden" value="charitable_report_print_dashboard" />
							<input name="start_date" type="hidden" value="<?php echo esc_attr( $date_range['start_date'] ); ?>" />
							<input name="end_date" type="hidden" value="<?php echo esc_attr( $date_range['end_date'] ); ?>" />
							<input name="days" type="hidden" value="<?php echo esc_attr( $time_period ); ?>" />
							<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
							<button type="submit" class="charitable-dashboard-v2-print-button" title="<?php echo esc_html__( 'Print Summary', 'charitable' ); ?>">
								<svg class="charitable-dashboard-v2-print-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M6 9V2H18V9M6 9H4C2.89543 9 2 9.89543 2 11V17C2 18.1046 2.89543 19 4 19H6V22H18V19H20C21.1046 19 22 18.1046 22 17V11C22 9.89543 21.1046 9 20 9H18M6 9V13H18V9M6 15H18V17H6V15Z" stroke="#0E2121" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
								</svg>
							</button>
						</form>
					</div>
				</div>
				<?php $this->render_stats_row(); ?>
				<div class="charitable-dashboard-v2-header-bar-chart">
					<div id="charitable-dashboard-v2-headline-graph" class="charitable-headline-graph"></div>
				</div>
			</section>
			<?php
		}

		/**
		 * Render the stats row with donation statistics.
		 *
		 * @since 1.8.8
		 */
	private function render_stats_row() {
		// Get the selected time period from request, default to 'last-7-days'
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$time_period = isset( $_GET['time-period'] ) ? sanitize_text_field( wp_unslash( $_GET['time-period'] ) ) : 'last-7-days';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$stats = $this->get_dashboard_stats( $time_period );
			?>
			<div class="charitable-dashboard-v2-stats-row">
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php esc_html_e( 'Total Donations', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount">
						<?php echo esc_html( $stats['total_donations'] ); ?>
						<?php if ( '0%' !== $stats['donations_change'] ) : ?>
							<span class="charitable-dashboard-v2-stat-change">
								<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
									<mask id="mask0_1904_1526" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="13" height="13">
										<rect y="0.25415" width="12.9062" height="12.3612" fill="#D9D9D9"/>
									</mask>
									<g mask="url(#mask0_1904_1526)">
										<path d="M1.82849 9.52507L1.07562 8.804L5.05505 4.96689L7.20609 7.02708L10.0024 4.37458H8.60426V3.34448H11.8308V6.43477H10.7553V5.09565L7.20609 8.49497L5.05505 6.43477L1.82849 9.52507Z" fill="#31944D"/>
									</g>
								</svg>
								<?php echo esc_html( $stats['donations_change'] ); ?>%
							</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php esc_html_e( 'Avg. Donations', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount">
						<?php echo esc_html( $stats['avg_donations'] ); ?>
						<?php if ( '0%' !== $stats['avg_change'] ) : ?>
							<span class="charitable-dashboard-v2-stat-change">
								<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
									<mask id="mask0_1904_1526" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="13" height="13">
										<rect y="0.25415" width="12.9062" height="12.3612" fill="#D9D9D9"/>
									</mask>
									<g mask="url(#mask0_1904_1526)">
										<path d="M1.82849 9.52507L1.07562 8.804L5.05505 4.96689L7.20609 7.02708L10.0024 4.37458H8.60426V3.34448H11.8308V6.43477H10.7553V5.09565L7.20609 8.49497L5.05505 6.43477L1.82849 9.52507Z" fill="#31944D"/>
									</g>
								</svg>
								<?php echo esc_html( $stats['avg_change'] ); ?>%
							</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php esc_html_e( 'Total Donors', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount"><?php echo esc_html( $stats['total_donors'] ); ?></div>
				</div>
				<div class="charitable-dashboard-v2-stat-item">
					<div class="charitable-dashboard-v2-stat-title"><?php echo esc_html( $stats['total_refunds_count'] ); ?> <?php esc_html_e( 'Refunds', 'charitable' ); ?></div>
					<div class="charitable-dashboard-v2-stat-amount"><?php echo esc_html( $stats['total_refunds'] ); ?></div>
				</div>
			</div>
			<?php
		}

		/**
		 * Get dashboard statistics data.
		 *
		 * @since 1.8.8
		 * @param string $time_period The selected time period.
		 * @return array
		 */
	public function get_dashboard_stats( $time_period = 'last-7-days' ) {
		// Clear stats cache if requested via URL parameter
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['charitable_clear_stats_cache'] ) && '1' === $_GET['charitable_clear_stats_cache'] ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$this->clear_dashboard_stats_cache();
		}

		// TEMPORARY TESTING: Return empty stats if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			// Decode HTML entities in currency-formatted strings to prevent double-encoding in JSON.
			$empty_total = html_entity_decode( charitable_format_money( 0 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$empty_avg = html_entity_decode( charitable_format_money( 0 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$empty_refunds = html_entity_decode( charitable_format_money( 0 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return array(
				'total_donations' => $empty_total,
				'donations_change' => '0%',
				'avg_donations' => $empty_avg,
				'avg_change' => '0%',
				'total_donors' => 0,
				'total_refunds' => $empty_refunds,
				'total_refunds_count' => 0
			);
		}

		// Calculate dates based on time period
		$date_range = $this->get_date_range_for_period( $time_period );
		$start_date = $date_range['start_date'];
		$end_date = $date_range['end_date'];
		$days = $date_range['days'];

		// Initialize Charitable Reports
		$charitable_reports = Charitable_Reports::get_instance();
		$args = array(
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'days'       => $days,
		);
		$charitable_reports->init_with_array( 'dashboard', $args );

		// Get donations data
		$donations = $charitable_reports->get_donations();
		$total_count_donations = count( $donations );
		$total_amount_donations = $charitable_reports->get_donations_total();
		$donation_average = ( $total_amount_donations > 0 && $total_count_donations > 0 ) ? ( charitable_format_money( $total_amount_donations / $total_count_donations, 2, true ) ) : charitable_format_money( 0 );
		$donation_total = charitable_format_money( $total_amount_donations );

		// Get donors count
		$total_count_donors = $charitable_reports->get_donors_count();

		// Get refunds data
		$refunds_data = $charitable_reports->get_refunds();
		$total_amount_refunds = charitable_format_money( $refunds_data['total_amount'] );
		$total_count_refunds = $refunds_data['total_count'];

		// Decode HTML entities in currency-formatted strings to prevent double-encoding in JSON.
		// charitable_format_money() returns HTML entities (e.g., &#36; for $), so we decode them
		// before sending via AJAX to avoid double-encoding issues.
		$donation_total = html_entity_decode( $donation_total, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$donation_average = html_entity_decode( $donation_average, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$total_amount_refunds = html_entity_decode( $total_amount_refunds, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Calculate percentage changes
		$changes = $this->calculate_percentage_changes( $time_period, $total_amount_donations, $donation_average );
		$donations_change = $changes['donations_change'];
		$avg_change = $changes['avg_change'];

		// Defensive guard: if current average is zero, hide avg change
		if ( 0.0 === (float) $this->parse_money_amount( $donation_average ) ) {
			$avg_change = '';
		}

		// Allow filtering to disable percentage displays completely
		$show_percentage_changes = apply_filters( 'charitable_dashboard_show_percentage_changes', true );
		if ( ! $show_percentage_changes ) {
			$donations_change = '';
			$avg_change = '';
		}

		return array(
			'total_donations' => $donation_total,
			'donations_change' => $donations_change,
			'avg_donations' => $donation_average,
			'avg_change' => $avg_change,
			'total_donors' => $total_count_donors,
			'total_refunds' => $total_amount_refunds,
			'total_refunds_count' => $total_count_refunds
		);
	}

		/**
		 * Get date range for a given time period.
		 *
		 * @since 1.8.8
		 * @param string $time_period The time period.
		 * @return array
		 */
		public function get_date_range_for_period( $time_period ) {
			$end_date = gmdate( 'Y/m/d' );

			switch ( $time_period ) {
				case 'last-7-days':
					$start_date = gmdate( 'Y/m/d', strtotime( '-7 days' ) );
					$days = 7;
					break;
				case 'last-14-days':
					$start_date = gmdate( 'Y/m/d', strtotime( '-14 days' ) );
					$days = 14;
					break;
				case 'last-30-days':
					$start_date = gmdate( 'Y/m/d', strtotime( '-30 days' ) );
					$days = 30;
					break;
				case 'this-month':
					$start_date = gmdate( 'Y/m/d', strtotime( 'first day of this month' ) );
					$days = gmdate( 'j' ); // Current day of month
					break;
				case 'last-3-months':
					$start_date = gmdate( 'Y/m/d', strtotime( '-3 months' ) );
					$days = 90;
					break;
				case 'last-6-months':
					$start_date = gmdate( 'Y/m/d', strtotime( '-6 months' ) );
					$days = 180;
					break;
				case 'last-year':
					$start_date = gmdate( 'Y/m/d', strtotime( '-1 year' ) );
					$days = 365;
					break;
				default:
					$start_date = gmdate( 'Y/m/d', strtotime( '-7 days' ) );
					$days = 7;
					break;
			}

			return array(
				'start_date' => $start_date,
				'end_date' => $end_date,
				'days' => $days
			);
		}

	/**
	 * Calculate percentage changes for donations and average.
	 *
	 * @since 1.8.8
	 * @param string $time_period Current time period.
	 * @param float $current_total Current total donations.
	 * @param string $current_avg Current average donation.
	 * @return array
	 */
	private function calculate_percentage_changes( $time_period, $current_total, $current_avg ) {

		$debug_no_cache = ( defined( 'CHARITABLE_DEBUG' ) && true === constant( 'CHARITABLE_DEBUG' ) );

		// Get cache duration from filter (default 4 hours)
		$cache_duration = apply_filters( 'charitable_dashboard_stats_cache_duration', 4 * HOUR_IN_SECONDS );

		// Create cache key
		$cache_key = 'charitable_dashboard_stats_changes_' . $time_period . '_' . gmdate( 'Y-m-d' );

		// Try to get from cache first (unless debugging)
		if ( ! $debug_no_cache ) {
			$cached_changes = get_transient( $cache_key );
			if ( false !== $cached_changes ) {
				return $cached_changes;
			}
		}

		// Calculate previous period date range
		$previous_period_range = $this->get_previous_period_date_range( $time_period );
		if ( false === $previous_period_range ) {
			// If we can't calculate previous period, return no change
			$changes = array(
				'donations_change' => '',
				'avg_change' => ''
			);
			if ( ! $debug_no_cache ) {
				set_transient( $cache_key, $changes, $cache_duration );
			}
			return $changes;
		}

		// Get previous period data
		$previous_data = $this->get_previous_period_data( $previous_period_range );
		if ( false === $previous_data ) {
			// If we can't get previous data, return no change
			$changes = array(
				'donations_change' => '',
				'avg_change' => ''
			);
			if ( ! $debug_no_cache ) {
				set_transient( $cache_key, $changes, $cache_duration );
			}
			return $changes;
		}

		// Calculate percentage changes
		$donations_change = $this->calculate_percentage_change( $previous_data['total'], $current_total );
		$avg_change = $this->calculate_percentage_change( $previous_data['avg'], $this->parse_money_amount( $current_avg ) );

		$changes = array(
			'donations_change' => $donations_change,
			'avg_change' => $avg_change
		);

		// Cache the results unless debugging
		if ( ! $debug_no_cache ) {
			set_transient( $cache_key, $changes, $cache_duration );
		}

		return $changes;
	}

	/**
	 * Get previous period date range for comparison.
	 *
	 * @since 1.8.8
	 * @param string $time_period Current time period.
	 * @return array|false
	 */
	private function get_previous_period_date_range( $time_period ) {
		$current_range = $this->get_date_range_for_period( $time_period );
		$current_start = $current_range['start_date'];
		$current_days = $current_range['days'];

		// Calculate previous period dates
		$previous_end = gmdate( 'Y/m/d', strtotime( $current_start . ' -1 day' ) );
		$previous_start = gmdate( 'Y/m/d', strtotime( $previous_end . ' -' . ( $current_days - 1 ) . ' days' ) );

		return array(
			'start_date' => $previous_start,
			'end_date' => $previous_end,
			'days' => $current_days
		);
	}

	/**
	 * Get previous period donation data.
	 *
	 * @since 1.8.8
	 * @param array $date_range Previous period date range.
	 * @return array|false
	 */
	private function get_previous_period_data( $date_range ) {
		try {
			// Initialize Charitable Reports for previous period
			$charitable_reports = Charitable_Reports::get_instance();
			$args = array(
				'start_date' => $date_range['start_date'],
				'end_date'   => $date_range['end_date'],
				'days'       => $date_range['days'],
			);
			$charitable_reports->init_with_array( 'dashboard', $args );

			// Get donations data
			$donations = $charitable_reports->get_donations();
			$total_count_donations = count( $donations );
			$total_amount_donations = $charitable_reports->get_donations_total();

			// Calculate average
			$donation_average = ( $total_amount_donations > 0 && $total_count_donations > 0 ) ? ( $total_amount_donations / $total_count_donations ) : 0;

			return array(
				'total' => $total_amount_donations,
				'avg' => $donation_average
			);
		} catch ( Exception $e ) {
			// Log error and return false
			if ( charitable_is_debug() ) {
				error_log( 'Charitable Dashboard: Error getting previous period data - ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return false;
		}
	}

	/**
	 * Calculate percentage change between two values.
	 *
	 * @since 1.8.8
	 * @param float $previous Previous value.
	 * @param float $current Current value.
	 * @return string
	 */
	private function calculate_percentage_change( $previous, $current ) {
		// Handle edge cases: if either value is zero or negative, do not show a percentage change
		if ( $previous <= 0 || $current <= 0 ) {
			return '';
		}

		$percentage = ( ( $current - $previous ) / $previous ) * 100;

		// Round to 1 decimal place
		$percentage = round( $percentage, 1 );

		// Handle very large percentages
		if ( $percentage > 99.9 ) {
			return '+99.9%';
		}
		if ( $percentage < -99.9 ) {
			return '-99.9%';
		}

		// Format with + or - sign
		$sign = $percentage > 0 ? '+' : '';
		return $sign . $percentage . '%';
	}

	/**
	 * Parse money amount from formatted string.
	 *
	 * @since 1.8.8
	 * @param string $money_formatted Formatted money string.
	 * @return float
	 */
	private function parse_money_amount( $money_formatted ) {
		// Decode HTML entities first (e.g., &#36; becomes $)
		$money_formatted = html_entity_decode( $money_formatted, ENT_QUOTES, 'UTF-8' );

		// Remove currency symbols and formatting
		$amount = preg_replace( '/[^\d.,]/', '', $money_formatted );

		// Handle different decimal separators
		if ( strpos( $amount, ',' ) !== false && strpos( $amount, '.' ) !== false ) {
			// Both comma and dot present - assume comma is thousands separator
			$amount = str_replace( ',', '', $amount );
		} elseif ( strpos( $amount, ',' ) !== false ) {
			// Only comma - could be decimal separator
			$amount = str_replace( ',', '.', $amount );
		}

		return (float) $amount;
	}

	/**
	 * Clear dashboard stats cache.
	 *
	 * @since   1.8.8
	 * @version 1.8.8.4 - added blog posts cache clearing
	 */
	public function clear_dashboard_stats_cache() {
		// Delete all dashboard stats cache transients
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_charitable_dashboard_stats_changes_' ) . '%'
			)
		);

		// Also delete the transient timeout entries
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_charitable_dashboard_stats_changes_' ) . '%'
			)
		);

		// Clear blog posts cache
		delete_transient( 'charitable_blog_posts_cache' );
	}

	/**
	 * Get the default tab from URL parameter.
	 *
	 * @since 1.8.8
	 * @return string
	 */
	private function get_default_tab() {
		$valid_tabs = array( 'top-campaigns', 'latest-donations', 'top-donors', 'comments' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$default_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'top-campaigns';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Validate the tab parameter
		if ( ! in_array( $default_tab, $valid_tabs, true ) ) {
			$default_tab = 'top-campaigns';
		}

		return $default_tab;
	}

	/**
	 * Check if a tab should be active.
	 *
	 * @since 1.8.8
	 * @param string $tab_name The tab name to check.
	 * @return bool
	 */
	private function is_tab_active( $tab_name ) {
		return $this->get_default_tab() === $tab_name;
	}

	/**
	 * Render the tabs section with campaigns, donations, donors, and comments.
	 *
	 * @since 1.8.8
	 */
	public function render_tabs_section() {
		?>
		<section class="charitable-dashboard-v2-section charitable-dashboard-top-campaigns">
			<header class="charitable-dashboard-v2-section-header">
				<nav class="charitable-dashboard-v2-tab-nav">
					<a href="#" class="charitable-dashboard-v2-tab-nav-item<?php echo $this->is_tab_active( 'top-campaigns' ) ? ' charitable-dashboard-v2-tab-nav-active' : ''; ?>" data-tab="top-campaigns">
						Top Campaigns
					</a>
					<a href="#" class="charitable-dashboard-v2-tab-nav-item<?php echo $this->is_tab_active( 'latest-donations' ) ? ' charitable-dashboard-v2-tab-nav-active' : ''; ?>" data-tab="latest-donations">
						Latest Donations
					</a>
					<a href="#" class="charitable-dashboard-v2-tab-nav-item<?php echo $this->is_tab_active( 'top-donors' ) ? ' charitable-dashboard-v2-tab-nav-active' : ''; ?>" data-tab="top-donors">
						Top Donors
					</a>
					<a href="#" class="charitable-dashboard-v2-tab-nav-item<?php echo $this->is_tab_active( 'comments' ) ? ' charitable-dashboard-v2-tab-nav-active' : ''; ?>" data-tab="comments">
						Comments
					</a>
				</nav>
			</header>
			<div class="charitable-dashboard-v2-section-content">
				<?php $this->render_top_campaigns_tab(); ?>
				<?php $this->render_latest_donations_tab(); ?>
				<?php $this->render_top_donors_tab(); ?>
				<?php $this->render_comments_tab(); ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render the top campaigns tab content.
	 *
	 * @since 1.8.8
	 */
	private function render_top_campaigns_tab() {
		$campaigns = $this->get_top_campaigns();
		?>
		<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'top-campaigns' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="top-campaigns">
			<?php if ( ! empty( $campaigns ) ) : ?>
				<div class="charitable-dashboard-v2-campaigns-table">
					<table class="charitable-dashboard-v2-table">
						<thead>
							<tr>
								<th>Campaign Title</th>
								<th>Goal</th>
								<th>Raised</th>
								<th>Status</th>
								<th>Progress</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $campaigns as $campaign ) : ?>
								<tr>
									<td class="campaign-title">
										<a href="<?php echo esc_url( $campaign['url'] ); ?>">
											<?php echo esc_html( $campaign['title'] ); ?>
											<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M1.09091 11.0001C0.790908 11.0001 0.53409 10.8932 0.320454 10.6796C0.106818 10.466 0 10.2091 0 9.90915V2.27279C0 1.97279 0.106818 1.71598 0.320454 1.50234C0.53409 1.2887 0.790908 1.18188 1.09091 1.18188H4.36363V2.27279H1.09091V9.90915H8.72726V6.63642H9.81817V9.90915C9.81817 10.2091 9.71135 10.466 9.49772 10.6796C9.28408 10.8932 9.02726 11.0001 8.72726 11.0001H1.09091Z" fill="#191D2D" fill-opacity="0.6"/>
												<path d="M4 6.16363L4.76364 6.92726L9.83636 1.85454V3.81818H10.9273V0H7.10909V1.09091H9.07272L4 6.16363Z" fill="#191D2D" fill-opacity="0.6"/>
											</svg>
										</a>
									</td>
									<td><?php echo esc_html( $campaign['goal'] ); ?></td>
									<td><?php echo esc_html( $campaign['reach'] ); ?></td>
									<td><span class="status-badge status-<?php echo esc_attr( $campaign['status'] ); ?>"><?php echo esc_html( $campaign['status_label'] ); ?></span></td>
									<td>
										<div class="progress-circle" data-progress="<?php echo esc_attr( $campaign['progress'] ); ?>">
											<svg width="40" height="40" viewBox="0 0 40 40">
												<circle cx="20" cy="20" r="18" fill="none" stroke="rgba(222, 234, 255, 1)" stroke-width="3"/>
												<circle cx="20" cy="20" r="18" fill="none" stroke="rgba(76, 123, 207, 1)" stroke-width="3"/>
											</svg>
											<span class="progress-text"><?php echo esc_html( $campaign['progress'] ); ?>%</span>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="charitable-dashboard-v2-empty-state">
					<div class="charitable-dashboard-v2-empty-state-content">
						<h3><?php esc_html_e( 'No Campaigns Yet!', 'charitable' ); ?></h3>
						<p><?php esc_html_e( 'Create your first campaign to start receiving donations.', 'charitable' ); ?></p>
						<?php if ( charitable_disable_legacy_campaigns() ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Create Campaign', 'charitable' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Create Campaign', 'charitable' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get top campaigns data.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_top_campaigns() {
		// TEMPORARY TESTING: Return empty campaigns if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Get top campaigns using the same method as legacy dashboard
		$args = array(
			'posts_per_page' => apply_filters( 'charitable_dashboard_top_campaigns_amount', 5 )
		);
		$top_campaigns = Charitable_Campaigns::ordered_by_amount( $args );

			$campaigns = array();

			if ( ! empty( $top_campaigns->posts ) ) {
				foreach ( $top_campaigns->posts as $campaign ) {
					$_campaign = charitable_get_campaign( $campaign->ID );

					if ( ! $_campaign ) {
						continue;
					}

					// Get campaign data (same as legacy)
					$donated_amount = $_campaign->get_donated_amount();
					$donation_goal = $_campaign->get_goal();
					$status = $_campaign->get_status();
					$percent_donated = $_campaign->get_percent_donated_raw();

					// Filter out draft campaigns
					if ( 'draft' === $status ) {
						continue;
					}

					// Cap percentage at 100%
					if ( $percent_donated > 100 ) {
						$percent_donated = 100;
					}

					// Map status to display format (same as legacy)
					$status_labels = array(
						'active' => __( 'Active', 'charitable' ),
						'completed' => __( 'Finished', 'charitable' ),
						'failed' => __( 'Failed', 'charitable' )
					);
					$status_label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucwords( $status );

					// Map status for CSS class
					$status_class = $status;
					if ( 'completed' === $status ) {
						$status_class = 'finished';
					}

					// Build campaign URL
					$url = charitable_disable_legacy_campaigns()
						? admin_url( 'admin.php?page=charitable-campaign-builder&view=template&campaign_id=' . $campaign->ID )
						: admin_url( 'post.php?post=' . $campaign->ID . '&action=edit' );

					// Handle goal display for finished campaigns
					$goal_display = '-';
					if ( $donation_goal ) {
						$goal_display = charitable_format_money( $donation_goal );
					} elseif ( 'completed' === $status ) {
						// For finished campaigns without goals, show the total raised
						$goal_display = charitable_format_money( $donated_amount );
					}

					$campaigns[] = array(
						'title' => $campaign->post_title,
						'url' => $url,
						'goal' => $goal_display,
						'reach' => charitable_format_money( $donated_amount ),
						'status' => $status_class,
						'status_label' => $status_label,
						'progress' => round( $percent_donated )
					);
				}
			}

			// If no campaigns, return empty array
			return $campaigns;
		}

		/**
		 * Render the latest donations tab content.
		 *
		 * @since 1.8.8
		 */
		private function render_latest_donations_tab() {
			$donations = $this->get_latest_donations();
			?>
			<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'latest-donations' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="latest-donations">
				<?php if ( ! empty( $donations ) ) : ?>
					<div class="charitable-dashboard-v2-table-container">
						<table class="charitable-dashboard-v2-table charitable-dashboard-v2-table-donations">
							<thead>
								<tr>
									<th>Donation Details</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $donations as $donation ) : ?>
									<tr>
										<td>
											<div class="donation-details">
												<div class="donation-header">
													<span class="donation-name">
														<a href="<?php echo esc_url( $donation['url'] ); ?>">
															<span class="donation-id">#<?php echo esc_html( $donation['id'] ); ?></span>
															<span class="donor-name"><?php echo esc_html( $donation['donor_name'] ); ?></span>
															<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path d="M1.09091 11.0001C0.790908 11.0001 0.53409 10.8932 0.320454 10.6796C0.106818 10.466 0 10.2091 0 9.90915V2.27279C0 1.97279 0.106818 1.71598 0.320454 1.50234C0.53409 1.2887 0.790908 1.18188 1.09091 1.18188H4.36363V2.27279H1.09091V9.90915H8.72726V6.63642H9.81817V9.90915C9.81817 10.2091 9.71135 10.466 9.49772 10.6796C9.28408 10.8932 9.02726 11.0001 8.72726 11.0001H1.09091Z" fill="#191D2D" fill-opacity="0.6"/>
																<path d="M4 6.16363L4.76364 6.92726L9.83636 1.85454V3.81818H10.9273V0H7.10909V1.09091H9.07272L4 6.16363Z" fill="#191D2D" fill-opacity="0.6"/>
															</svg>
														</a>
													</span>
													<span class="donation-timestamp"><?php echo esc_html( $donation['timestamp'] ); ?></span>
													<span class="donation-amount">
														<strong>Amount Donated:</strong> <?php echo esc_html( $donation['amount'] ); ?>
													</span>
													<span class="donation-method <?php echo esc_attr( $donation['method_class'] ); ?>">
														<strong>Method:</strong> <?php echo esc_html( $donation['method'] ); ?>
													</span>
												</div>
											</div>
										</td>
										<td><span class="status-badge status-<?php echo esc_attr( $donation['status'] ); ?>"><?php echo esc_html( $donation['status_label'] ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="charitable-dashboard-v2-empty-state">
						<div class="charitable-dashboard-v2-empty-state-content">
							<h3><?php esc_html_e( 'No Donations Yet!', 'charitable' ); ?></h3>
							<p><?php esc_html_e( 'Start receiving donations to see them here.', 'charitable' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=donation' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Add Donation', 'charitable' ); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Get latest donations data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_latest_donations() {
		// TEMPORARY TESTING: Return empty donations if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Check for test parameter to simulate empty state
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['charitable_test_no_donations'] ) && '1' === $_GET['charitable_test_no_donations'] ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return array();
		}

			// Get latest donations using Charitable_Reports
			$charitable_reports = Charitable_Reports::get_instance();
			$args = array(
				'start_date' => gmdate( 'Y/m/d', strtotime( '-30 days' ) ),
				'end_date'   => gmdate( 'Y/m/d' ),
				'days'       => 30,
			);
			$charitable_reports->init_with_array( 'dashboard', $args );
			$donations = $charitable_reports->get_donations();

			$latest_donations = array();
			$limit = apply_filters( 'charitable_dashboard_latest_donations_amount', 3 );

			if ( ! empty( $donations ) ) {
				// Sort donations by date (newest first)
				usort( $donations, function( $a, $b ) {
					return strtotime( $b->post_date ) - strtotime( $a->post_date );
				});

				// Get valid donation statuses for mapping
				$statuses = charitable_get_valid_donation_statuses();

				$count = 0;
				foreach ( $donations as $donation ) {
					if ( $count >= $limit ) {
						break;
					}

					$donation_id = intval( $donation->donation_id );
					$donor_name = trim( esc_html( $donation->first_name ) . ' ' . esc_html( $donation->last_name ) );
					$payment_method = strtolower( $donation->payment_gateway );
					$donation_status = $donation->donation_status;
					$amount = charitable_format_money( $donation->amount );
					$timestamp = human_time_diff( strtotime( $donation->post_date ), current_time( 'timestamp' ) ) . ' ago';

					// Map status to display format
					$status_label = array_key_exists( $donation_status, $statuses ) ? $statuses[ $donation_status ] : ucwords( $donation_status );

					// Map status for CSS class
					$status_class = $donation_status;
					if ( 'charitable-completed' === $donation_status ) {
						$status_class = 'paid';
					} elseif ( strpos( $donation_status, 'charitable-' ) === 0 ) {
						$status_class = substr( $donation_status, 11 ); // Remove 'charitable-' prefix
					}

					// Determine payment method CSS class
					$method_class = '';
					if ( 'stripe' === $payment_method ) {
						$method_class = 'donation-method-stripe';
					} elseif ( 'paypal' === $payment_method ) {
						$method_class = 'donation-method-paypal';
					}

					// Build donation URL
					$url = admin_url( 'post.php?post=' . $donation_id . '&action=edit' );

					$latest_donations[] = array(
						'id' => $donation_id,
						'url' => $url,
						'donor_name' => $donor_name,
						'timestamp' => $timestamp,
						'amount' => $amount,
						'method' => ucwords( $payment_method ),
						'method_class' => $method_class,
						'status' => $status_class,
						'status_label' => $status_label
					);

					$count++;
				}
			}

			return $latest_donations;
		}

	/**
	 * Render the top donors tab content.
	 *
	 * @since 1.8.8
	 */
	private function render_top_donors_tab() {
		$donors = $this->get_top_donors();
		?>
		<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'top-donors' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="top-donors">
			<?php if ( ! empty( $donors ) ) : ?>
				<div class="charitable-dashboard-v2-table-container">
					<table class="charitable-dashboard-v2-table charitable-dashboard-v2-table-donors">
						<thead>
							<tr>
								<th style="width: 60px;"></th>
								<th style="width: 200px;">Name</th>
								<th style="width: 150px;">Total Donations</th>
								<th style="width: 120px;">Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $donors as $donor ) : ?>
								<tr>
									<td>
										<div class="donor-avatar">
											<img src="<?php echo esc_url( $donor['avatar'] ); ?>" alt="<?php echo esc_attr( $donor['name'] ); ?>" width="40" height="40">
										</div>
									</td>
									<td>
										<div class="donor-details">
											<div class="donor-name">
												<a href="<?php echo esc_url( $donor['url'] ); ?>" class="donor-name-link"><?php echo esc_html( $donor['name'] ); ?></a>
											</div>
											<div class="donor-email">
												<?php if ( ! empty( $donor['email'] ) ) : ?>
													<a href="mailto:<?php echo esc_attr( $donor['email'] ); ?>" class="donor-email-link" title="<?php echo esc_attr( $donor['email'] ); ?>"><?php echo esc_html( $donor['email_display'] ); ?></a>
												<?php else : ?>
													<span class="no-email"><?php esc_html_e( 'No email', 'charitable' ); ?></span>
												<?php endif; ?>
											</div>
										</div>
									</td>
									<td>
										<div class="donor-amount"><?php echo esc_html( $donor['total'] ); ?></div>
									</td>
									<td>
										<span class="status-badge status-<?php echo esc_attr( $donor['type'] ); ?>"><?php echo esc_html( $donor['type_label'] ); ?></span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<?php
					// Determine if there is at least one non-trashed campaign.
					$has_non_trashed_campaign = false;
					foreach ( array( 'campaign', 'charitable_campaign' ) as $campaign_cpt ) {
						$counts = wp_count_posts( $campaign_cpt );
						if ( $counts && is_object( $counts ) ) {
							foreach ( $counts as $status => $num_posts ) {
								if ( 'trash' === $status ) {
									continue;
								}
								if ( (int) $num_posts > 0 ) {
									$has_non_trashed_campaign = true;
									break 2;
								}
							}
						}
					}
				?>
				<div class="charitable-dashboard-v2-empty-state">
					<div class="charitable-dashboard-v2-empty-state-content">
						<h3><?php esc_html_e( 'No Donors Yet!', 'charitable' ); ?></h3>
						<p><?php esc_html_e( 'Start receiving donations to see your top donors here.', 'charitable' ); ?></p>
						<?php if ( $has_non_trashed_campaign ) : ?>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=donation' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Add Donation', 'charitable' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=charitable_campaign' ) ); ?>" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
								<?php esc_html_e( 'Create Campaign', 'charitable' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get top donors data.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_top_donors() {
		// TEMPORARY TESTING: Return empty donors if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Get configurable limit (default 10)
		$limit = apply_filters( 'charitable_dashboard_top_donors_limit', 10 );

		// Initialize Charitable_Reports for all-time data (no date filtering)
		$charitable_reports = Charitable_Reports::get_instance();
		$args = array(
			'start_date' => false,
			'end_date'   => false,
			'days'       => false,
		);
		$charitable_reports->init_with_array( 'dashboard', $args );

		// Get donations data first
		$charitable_reports->get_donations();

		// Get top donors data (get more than needed to account for filtering)
		$top_donors = $charitable_reports->get_top_donors_overview( $limit * 2 );

		if ( empty( $top_donors ) || ! is_array( $top_donors ) ) {
			return array();
		}

		$donors = array();
		$unique_donors = array();
		$count = 0;

		foreach ( $top_donors as $donor_data ) {
			// Skip if we've already processed this donor
			if ( in_array( $donor_data->donor_id, $unique_donors, true ) ) {
				continue;
			}

			// Stop if we've reached the limit
			if ( $count >= $limit ) {
				break;
			}

			$unique_donors[] = $donor_data->donor_id;
			$count++;

			// Get donor name
			$donor_name = ! empty( $donor_data->donor_name ) ? $donor_data->donor_name : __( 'Unknown', 'charitable' );

			// Get donor email and truncate if needed
			$donor_email = ! empty( $donor_data->donor_email ) ? $donor_data->donor_email : '';
			$donor_email_display = $donor_email;
			if ( strlen( $donor_email ) > 30 ) {
				$donor_email_display = substr( $donor_email, 0, 30 ) . '...';
			}

			// Get avatar (WordPress default)
			$donor_avatar = ! empty( $donor_data->donor_avatar_url ) ? $donor_data->donor_avatar_url : false;
			if ( false === $donor_avatar && function_exists( 'get_avatar_url' ) && ! empty( $donor_data->donor_email ) ) {
				$donor_avatar = get_avatar_url(
					$donor_data->donor_email,
					array(
						'size' => 40,
					)
				);
			}

			// Determine donor type and badge
			$donation_parent = false;
			if ( ! empty( $donor_data->donation_parent_id ) ) {
				$donation = charitable_get_donation( $donor_data->donation_parent_id );
				$donation_parent = ( $donation && is_object( $donation ) ) ? $donation->get( 'donation_type' ) : false;
			}
			$display_count = ! empty( $donor_data->total_donation_count ) ? intval( $donor_data->total_donation_count ) : 1;

			$badge_css = strtolower( $donation_parent ) === 'recurring' ? 'recurring' : 'one-time';
			$badge_label = strtolower( $donation_parent ) === 'recurring' ? esc_html__( 'Recurring', 'charitable' ) : esc_html__( 'One-Time', 'charitable' );

			// Override for multiple donations
			if ( $display_count > 1 ) {
				$badge_css = 'multiple';
				$badge_label = esc_html__( 'Multiple', 'charitable' );
			}

			// Format total amount
			$display_amount = ! empty( $donor_data->total_donation_amount ) ? charitable_format_money( $donor_data->total_donation_amount, 2, true ) : charitable_format_money( $donor_data->amount, 2, true );

			$donors[] = array(
				'name' => $donor_name,
				'email' => $donor_email,
				'email_display' => $donor_email_display,
				'avatar' => $donor_avatar,
				'total' => $display_amount,
				'type' => $badge_css,
				'type_label' => $badge_label,
				'url' => admin_url( 'admin.php?page=charitable-donors' )
			);
		}

		return $donors;
	}

		/**
		 * Render the comments tab content.
		 *
		 * @since 1.8.8
		 */
		private function render_comments_tab() {
			?>
			<div class="charitable-dashboard-v2-tab-content<?php echo $this->is_tab_active( 'comments' ) ? ' charitable-dashboard-v2-tab-content-active' : ''; ?>" data-tab="comments">
				<?php $this->render_comments_content(); ?>
			</div>
			<?php
		}

		/**
		 * Render comments content based on addon status.
		 *
		 * @since 1.8.8
		 */
		private function render_comments_content() {
			$addon_status = $this->get_donor_comments_addon_status();

			switch ( $addon_status ) {
				case 'active':
					$this->render_comments_table();
					break;
				case 'installed_inactive':
					$this->render_addon_inactive_message();
					break;
				case 'not_installed_pro':
					$this->render_addon_not_installed_pro_message();
					break;
				case 'not_installed_lite':
				default:
					$this->render_addon_not_installed_lite_message();
					break;
			}
		}

		/**
		 * Get donor comments addon status.
		 *
		 * @since 1.8.8
		 * @return string Status: 'active', 'installed_inactive', 'not_installed_pro', 'not_installed_lite'
		 */
		private function get_donor_comments_addon_status() {
			// Check if addon is active
			if ( class_exists( 'Charitable_Donor_Comments' ) ) {
				return 'active';
			}

			// Check if addon is installed but not active
			$plugin_file = 'charitable-donor-comments/charitable-donor-comments.php';
			if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
				return charitable_is_pro() ? 'installed_inactive' : 'not_installed_lite';
			}

			// Addon not installed
			return charitable_is_pro() ? 'not_installed_pro' : 'not_installed_lite';
		}

		/**
		 * Render comments table with actual data.
		 *
		 * @since 1.8.8
		 */
		private function render_comments_table() {
			$comments = $this->get_comments();
			?>
			<div class="charitable-dashboard-v2-table-container">
				<table class="charitable-dashboard-v2-table charitable-dashboard-v2-table-comments">
					<thead>
						<tr>
							<th>Comment Details</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $comments ) ) : ?>
							<?php foreach ( $comments as $comment ) : ?>
								<tr data-comment-id="<?php echo esc_attr( $comment['id'] ); ?>">
									<td>
										<div class="comment-details">
											<div class="comment-header">
												<span class="comment-name"><?php echo esc_html( $comment['name'] ); ?></span>
												<span class="comment-text">"<?php echo esc_html( $comment['text'] ); ?>"</span>
											</div>
										</div>
									</td>
									<td>
										<?php if ( 'approved' === $comment['status'] ) : ?>
											<span class="comment-status-approved">Approved</span>
										<?php else : ?>
											<div class="comment-actions">
												<a href="#" class="comment-action approve" data-comment-id="<?php echo esc_attr( $comment['id'] ); ?>">
													<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="#10B981"/>
													</svg>
													<span class="action-text">Approve</span>
													<span class="action-loading" style="display: none;">
														<svg class="charitable-spinner" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
																<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
																<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
															</circle>
														</svg>
														Processing...
													</span>
												</a>
												<span class="comment-action-separator">|</span>
												<a href="#" class="comment-action delete" data-comment-id="<?php echo esc_attr( $comment['id'] ); ?>">
													<img src="/wp-content/plugins/charitable/assets/images/icons/trash.svg" width="12" height="12" alt="Delete">
													<span class="action-text"><?php esc_html_e( 'Delete', 'charitable' ); ?></span>
													<span class="action-loading" style="display: none;">
														<svg class="charitable-spinner" width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
															<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
																<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
																<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
															</circle>
														</svg>
														Processing...
													</span>
												</a>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="2" class="charitable-dashboard-v2-no-comments">
									<p><strong><?php echo esc_html__( 'No donor comments yet.', 'charitable' ); ?></strong></p>
									<p><?php echo esc_html__( 'Donor comments will appear here once donors start leaving messages with their donations.', 'charitable' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Render message for addon not installed (Lite users).
		 *
		 * @since 1.8.8
		 */
		private function render_addon_not_installed_lite_message() {
			?>
			<div class="charitable-dashboard-v2-empty-state">
				<div class="charitable-dashboard-v2-empty-state-content">
					<h3><?php esc_html_e( 'Donor Comments Not Available', 'charitable' ); ?></h3>
					<p><?php echo wp_kses( __( 'The Donor Comments addon is not installed. Upgrade to <strong>Charitable Pro</strong> to access this feature.', 'charitable' ), array( 'strong' => array() ) ); ?></p>
					<a href="https://wpcharitable.com/pricing" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
						<?php esc_html_e( 'Upgrade to Pro', 'charitable' ); ?>
					</a>
				</div>
			</div>
			<?php
		}

		/**
		 * Render message for addon not installed (Pro users).
		 *
		 * @since 1.8.8
		 */
		private function render_addon_not_installed_pro_message() {
			?>
			<div class="charitable-dashboard-v2-empty-state">
				<div class="charitable-dashboard-v2-empty-state-content">
					<h3><?php esc_html_e( 'Donor Comments Addon Not Installed', 'charitable' ); ?></h3>
					<p><?php esc_html_e( 'Download and activate the Donor Comments addon to view donor comments.', 'charitable' ); ?></p>
					<a href="https://wpcharitable.com/addons" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary">
						<?php esc_html_e( 'Download Addon', 'charitable' ); ?>
					</a>
				</div>
			</div>
			<?php
		}

		/**
		 * Render message for addon installed but not activated.
		 *
		 * @since 1.8.8
		 */
		private function render_addon_inactive_message() {
			?>
			<div class="charitable-dashboard-v2-empty-state">
				<div class="charitable-dashboard-v2-empty-state-content">
					<h3><?php esc_html_e( 'Donor Comments Addon Not Activated', 'charitable' ); ?></h3>
					<p><?php esc_html_e( 'The Donor Comments addon is installed but not activated. Click the button below to activate it.', 'charitable' ); ?></p>
					<button id="charitable-activate-donor-comments" class="charitable-dashboard-v2-button charitable-dashboard-v2-button-primary" data-plugin="charitable-donor-comments/charitable-donor-comments.php">
						<span class="button-text"><?php esc_html_e( 'Activate Addon', 'charitable' ); ?></span>
						<span class="button-loading" style="display: none;">
							<svg class="charitable-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
									<animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
									<animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
								</circle>
							</svg>
							<?php esc_html_e( 'Activating...', 'charitable' ); ?>
						</span>
					</button>
				</div>
			</div>
			<?php
		}

		/**
		 * Get comments data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_comments() {
		// TEMPORARY TESTING: Return empty comments if testing mode is enabled
		if ( self::SHOW_EMPTY_DASHBOARD ) {
			return array();
		}

		// Check if donor comments addon is active
		if ( ! class_exists( 'Charitable_Donor_Comments' ) ) {
			return array();
		}

			// Get comments limit (configurable)
			$limit = apply_filters( 'charitable_dashboard_comments_limit', 10 );

			// Get charitable comments
			$comments = get_comments( array(
				'type'     => 'charitable_comment',
				'status'   => 'all', // Include pending and approved
				'number'   => $limit,
				'orderby'  => 'comment_date',
				'order'    => 'DESC',
				'post_type' => Charitable::CAMPAIGN_POST_TYPE,
			) );

			$formatted_comments = array();

			if ( ! empty( $comments ) ) {
				foreach ( $comments as $comment ) {
					// Get comment author (handle anonymous)
					$author = get_comment_author( $comment );
					if ( empty( $author ) ) {
						$author = __( 'Anonymous', 'charitable' );
					}

					// Get comment text and sanitize it
					$text = get_comment_text( $comment );
					if ( empty( $text ) ) {
						continue; // Skip comments without text
					}

					// Strip HTML tags except for bold and italic, then convert to proper formatting
					$allowed_tags = array(
						'strong' => array(),
						'b' => array(),
						'em' => array(),
						'i' => array(),
					);
					$text = wp_kses( $text, $allowed_tags );

					// Convert HTML tags to proper bold/italic formatting
					$text = str_replace( array( '<strong>', '</strong>', '<b>', '</b>' ), array( '<strong>', '</strong>', '<strong>', '</strong>' ), $text );
					$text = str_replace( array( '<em>', '</em>', '<i>', '</i>' ), array( '<em>', '</em>', '<em>', '</em>' ), $text );

					// Determine status
					$status = $comment->comment_approved ? 'approved' : 'pending';

					$formatted_comments[] = array(
						'id'     => $comment->comment_ID,
						'name'   => $author,
						'text'   => $text,
						'status' => $status,
						'date'   => $comment->comment_date,
					);
				}
			}

			return $formatted_comments;
		}

		/**
		 * AJAX handler for activating addons.
		 *
		 * @since 1.8.8
		 */
		public function ajax_activate_addon() {
			// Verify nonce
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
				wp_die( esc_html( __( 'Security check failed', 'charitable' ) ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to activate plugins.', 'charitable' ) ) );
			}

			$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $plugin ) ) {
				wp_send_json_error( array( 'message' => __( 'Plugin not specified.', 'charitable' ) ) );
			}

			// Check if plugin exists
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
				wp_send_json_error( array( 'message' => __( 'Plugin file not found.', 'charitable' ) ) );
			}

			// Activate the plugin
			$result = activate_plugin( $plugin );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'Addon activated successfully!', 'charitable' ) ) );
		}


		/**
		 * AJAX handler for approving comments.
		 *
		 * @since 1.8.8
		 */
		public function ajax_approve_comment() {
			// Verify nonce
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
				wp_die( esc_html( __( 'Security check failed', 'charitable' ) ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'moderate_comments' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to moderate comments.', 'charitable' ) ) );
			}

			$comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $comment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Comment ID not specified.', 'charitable' ) ) );
			}

			// Check if comment exists
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				wp_send_json_error( array( 'message' => __( 'Comment not found.', 'charitable' ) ) );
			}

			// Check if it's a charitable comment
			if ( 'charitable_comment' !== $comment->comment_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid comment type.', 'charitable' ) ) );
			}

			// Approve the comment
			$result = wp_set_comment_status( $comment_id, 'approve' );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'Comment approved successfully!', 'charitable' ) ) );
		}

		/**
		 * AJAX handler for deleting comments.
		 *
		 * @since 1.8.8
		 */
		public function ajax_delete_comment() {
			// Verify nonce
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
				wp_die( esc_html( __( 'Security check failed', 'charitable' ) ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'moderate_comments' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to moderate comments.', 'charitable' ) ) );
			}

			$comment_id = isset( $_POST['comment_id'] ) ? intval( wp_unslash( $_POST['comment_id'] ) ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $comment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Comment ID not specified.', 'charitable' ) ) );
			}

			// Check if comment exists
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				wp_send_json_error( array( 'message' => __( 'Comment not found.', 'charitable' ) ) );
			}

			// Check if it's a charitable comment
			if ( 'charitable_comment' !== $comment->comment_type ) {
				wp_send_json_error( array( 'message' => __( 'Invalid comment type.', 'charitable' ) ) );
			}

			// Delete the comment (force delete)
			$result = wp_delete_comment( $comment_id, true );

			if ( ! $result ) {
				wp_send_json_error( array( 'message' => __( 'Failed to delete comment.', 'charitable' ) ) );
			}

			wp_send_json_success( array( 'message' => __( 'Comment deleted successfully!', 'charitable' ) ) );
		}

		/**
		 * Render the upgrade section.
		 *
		 * @since 1.8.8
		 */
		public function render_upgrade_section() {
			// Don't show upgrade section if user already has Pro license
			if ( charitable_is_pro() ) {
				return;
			}
			?>
			<section class="charitable-dashboard-v2-section">
				<h4 class="charitable-dashboard-v2-upgrade-title"><?php esc_html_e( 'Upgrade to Pro to Unlock Powerful Donation Features', 'charitable' ); ?></h4>
				<div class="charitable-dashboard-v2-upgrade-features">
					<div class="charitable-dashboard-v2-upgrade-column">
						<?php $this->render_upgrade_features_left(); ?>
					</div>
					<div class="charitable-dashboard-v2-upgrade-column">
						<?php $this->render_upgrade_features_right(); ?>
					</div>
				</div>
				<div class="charitable-dashboard-v2-upgrade-actions">
					<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/pricing', 'Dashboard Upgrade Section', 'Upgrade To Pro Button' ) ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-upgrade-button"><?php esc_html_e( 'Upgrade To Pro', 'charitable' ); ?></a>
					<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/pricing', 'Dashboard Upgrade Section', 'Learn More About Features Link' ) ); ?>" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-learn-more-link"><?php esc_html_e( 'Learn more about all features →', 'charitable' ); ?></a>
				</div>
			</section>
			<?php
		}

		/**
		 * Render left column upgrade features.
		 *
		 * @since 1.8.8
		 */
		private function render_upgrade_features_left() {
			$features = array(
				__( 'Recurring Donations', 'charitable' ),
				__( 'Peer-to-Peer Fundraising', 'charitable' ),
				__( 'Donor Database', 'charitable' ),
				__( 'Donor Dashboard', 'charitable' ),
				__( 'PDF & Annual Receipts', 'charitable' )
			);

			foreach ( $features as $feature ) :
				?>
				<div class="charitable-dashboard-v2-upgrade-feature">
					<svg class="charitable-dashboard-v2-upgrade-checkmark" width="18" height="18" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M9.0688 1.3573C13.798 1.3573 17.6374 5.19676 17.6374 9.92591C17.6374 14.6551 13.798 18.4945 9.0688 18.4945C4.33966 18.4945 0.500198 14.6551 0.500198 9.92591C0.500198 5.19676 4.33966 1.3573 9.0688 1.3573ZM13.5778 7.82078C13.5778 7.67488 13.5197 7.52981 13.4146 7.42562L12.6234 6.63444C12.5192 6.52938 12.3733 6.47118 12.2283 6.47118C12.0824 6.47118 11.9373 6.52938 11.8322 6.63444L8.01537 10.4548L6.30536 8.7396C6.20026 8.6345 6.05523 8.57631 5.90933 8.57631C5.7643 8.57631 5.6184 8.6345 5.51421 8.7396L4.72303 9.53075C4.61793 9.63498 4.55978 9.78088 4.55978 9.92591C4.55978 10.0709 4.61793 10.2168 4.72303 10.3211L7.62021 13.2174C7.72444 13.3225 7.87034 13.3806 8.01537 13.3806C8.16127 13.3806 8.3063 13.3225 8.4114 13.2174L13.4146 8.2159C13.5197 8.11171 13.5778 7.96581 13.5778 7.82078Z" fill="#59A56D"/>
					</svg>
					<span class="charitable-dashboard-v2-upgrade-feature-text"><?php echo esc_html( $feature ); ?></span>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Render right column upgrade features.
		 *
		 * @since 1.8.8
		 */
		private function render_upgrade_features_right() {
			$features = array(
				__( 'Anonymous Giving', 'charitable' ),
				__( 'Fee Coverage Option', 'charitable' ),
				__( 'Email Marketing Integration', 'charitable' ),
				__( 'Built-In Analytics & Reports', 'charitable' ),
				__( 'Flexible Payment Methods', 'charitable' )
			);

			foreach ( $features as $feature ) :
				?>
				<div class="charitable-dashboard-v2-upgrade-feature">
					<svg class="charitable-dashboard-v2-upgrade-checkmark" width="18" height="18" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M9.0688 1.3573C13.798 1.3573 17.6374 5.19676 17.6374 9.92591C17.6374 14.6551 13.798 18.4945 9.0688 18.4945C4.33966 18.4945 0.500198 14.6551 0.500198 9.92591C0.500198 5.19676 4.33966 1.3573 9.0688 1.3573ZM13.5778 7.82078C13.5778 7.67488 13.5197 7.52981 13.4146 7.42562L12.6234 6.63444C12.5192 6.52938 12.3733 6.47118 12.2283 6.47118C12.0824 6.47118 11.9373 6.52938 11.8322 6.63444L8.01537 10.4548L6.30536 8.7396C6.20026 8.6345 6.05523 8.57631 5.90933 8.57631C5.7643 8.57631 5.6184 8.6345 5.51421 8.7396L4.72303 9.53075C4.61793 9.63498 4.55978 9.78088 4.55978 9.92591C4.55978 10.0709 4.61793 10.2168 4.72303 10.3211L7.62021 13.2174C7.72444 13.3225 7.87034 13.3806 8.01537 13.3806C8.16127 13.3806 8.3063 13.3225 8.4114 13.2174L13.4146 8.2159C13.5197 8.11171 13.5778 7.96581 13.5778 7.82078Z" fill="#59A56D"/>
					</svg>
					<span class="charitable-dashboard-v2-upgrade-feature-text"><?php echo esc_html( $feature ); ?></span>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Render the enhance campaign section.
		 *
		 * @since 1.8.8
		 */
		public function render_enhance_campaign_section() {
			// Hide section if no items to show
			if ( ! $this->should_show_enhance_section() ) {
				return;
			}

			?>

			<style>
			.charitable-dashboard-v2-setup-button {
				background-color: #28a745 !important;
				color: white !important;
				border-color: #28a745 !important;
			}
			.charitable-dashboard-v2-setup-button:hover {
				background-color: #218838 !important;
				border-color: #1e7e34 !important;
			}
			.charitable-dashboard-v2-activate-button:disabled {
				background-color: #6c757d !important;
				color: #fff !important;
				border-color: #6c757d !important;
				opacity: 0.65 !important;
				cursor: not-allowed !important;
			}
			.charitable-dashboard-v2-upgrade-button {
				background-color: #E38632 !important;
				color: white !important;
				border-color: #E38632 !important;
			}
			.charitable-dashboard-v2-upgrade-button:hover {
				background-color: #E38632 !important;
				border-color: #E38632 !important;
			}
			.charitable-dashboard-v2-installed-button {
				background-color: #6c757d !important;
				color: #fff !important;
				border-color: #6c757d !important;
				cursor: not-allowed !important;
			}

			/* Hide all text in stats row initially */
			.charitable-dashboard-v2-stats-row .charitable-dashboard-v2-stat-title,
			.charitable-dashboard-v2-stats-row .charitable-dashboard-v2-stat-amount {
				visibility: hidden;
			}

			/* Show text when loading is complete */
			.charitable-dashboard-v2-stats-row.charitable-dashboard-stats-loaded .charitable-dashboard-v2-stat-title,
			.charitable-dashboard-v2-stats-row.charitable-dashboard-stats-loaded .charitable-dashboard-v2-stat-amount {
				visibility: visible;
			}
			</style>

			<section id="charitable-dashboard-v2-enhance-campaign" class="charitable-dashboard-v2-section">
				<header class="charitable-dashboard-v2-section-header">
					<h3><?php esc_html_e( 'Enhance Your Campaign', 'charitable' ); ?></h3>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<div class="charitable-dashboard-v2-enhance-grid">
						<?php $this->render_enhance_grid_items(); ?>
					</div>
					<footer class="charitable-dashboard-v2-enhance-campaign-footer">
						<div class="charitable-dashboard-v2-enhance-campaign-footer-content">
							<div class="charitable-dashboard-v2-enhance-campaign-footer-left">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-addons' ) ); ?>" class="charitable-dashboard-v2-addons-link">
									<span class="charitable-dashboard-v2-addons-text"><?php esc_html_e( 'View All Addons', 'charitable' ); ?></span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}

		/**
		 * Render enhance grid items.
		 *
		 * @since 1.8.8
		 */
		private function render_enhance_grid_items() {
			$items = $this->get_enhance_items();

			foreach ( $items as $item ) :
			$button_attributes = '';
			if ( isset( $item['button_action'] ) ) {
				$button_attributes = 'data-action="' . esc_attr( $item['button_action'] ) . '"';
				$button_attributes .= ' data-slug="' . esc_attr( $item['slug'] ) . '"';
				$button_attributes .= ' data-type="' . esc_attr( $item['type'] ) . '"';

				// Add basename for third-party plugins and charitable addons
				if ( isset( $item['basename'] ) ) {
					$button_attributes .= ' data-basename="' . esc_attr( $item['basename'] ) . '"';
				}

				// Add setup URL for third-party plugins (always available for setup state)
				if ( $item['type'] === 'third_party' && isset( $item['setup_url'] ) ) {
					$button_attributes .= ' data-setup-url="' . esc_url( $item['setup_url'] ) . '"';
				}

				// Add upgrade URL for charitable addons
				if ( $item['type'] === 'charitable_addon' && isset( $item['upgrade_url'] ) ) {
					$button_attributes .= ' data-upgrade-url="' . esc_url( $item['upgrade_url'] ) . '"';
				}

				// Add disabled state
				if ( isset( $item['button_disabled'] ) && $item['button_disabled'] ) {
					$button_attributes .= ' disabled';
				}
			}
				?>
				<div class="charitable-dashboard-v2-enhance-grid-item">
					<div class="charitable-dashboard-v2-enhance-grid-content">
						<div class="charitable-dashboard-v2-enhance-grid-header">
							<div class="charitable-dashboard-v2-enhance-grid-icon">
								<?php
								// Allow SVG icons through as trusted raw output.
								if ( isset( $item['icon'] ) && is_string( $item['icon'] ) ) {
									echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is trusted as curated/provided internally.
								}
								?>
							</div>
							<h4 class="charitable-dashboard-v2-enhance-grid-title"><?php echo esc_html( $item['title'] ); ?></h4>
						</div>
						<p class="charitable-dashboard-v2-enhance-grid-description"><?php echo esc_html( $item['description'] ); ?></p>
						<button class="charitable-dashboard-v2-enhance-grid-button <?php echo esc_attr( $item['button_class'] ); ?>" <?php echo wp_kses_post( $button_attributes ); ?>><?php echo esc_html( $item['button_text'] ); ?></button>
					</div>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Get enhance items data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
		private function get_enhance_items() {
		// TODO: Future caching implementation
		// Consider caching plugin states to avoid repeated get_plugins() calls
		// Cache key: 'charitable_dashboard_plugin_states'
		// Cache duration: 1 hour or until plugin state changes
		// Invalidate cache when: plugins installed/activated/deactivated

		$items = array();
		$third_party_plugins = $this->get_curated_third_party_plugins();
		$charitable_addons = $this->get_curated_charitable_addons();

		// Get third-party plugin data
		$all_plugins = $this->get_third_party_plugin_data();

		// Get first 3 available third-party plugins
		$available_third_party = 0;
		foreach ( $third_party_plugins as $slug ) {
			if ( $available_third_party >= 3 ) {
				break;
			}

			if ( isset( $all_plugins[ $slug ] ) ) {
				$plugin_data = $all_plugins[ $slug ];
				if ( ! $plugin_data['active'] ) { // Show if not active (installed or not)
					$items[] = $this->format_plugin_item( $plugin_data, 'third_party' );
					$available_third_party++;
				}
			}
		}

		// Fill remaining slots with Charitable addons
		$remaining_slots = 4 - count( $items );
		$available_charitable = 0;

		foreach ( $charitable_addons as $slug ) {
			if ( $available_charitable >= $remaining_slots ) {
				break;
			}

			$addon_data = $this->get_charitable_addon_data( $slug );

			if ( $addon_data && ! ( $addon_data['installed'] && $addon_data['active'] ) ) {
				$items[] = $this->format_addon_item( $addon_data );
				$available_charitable++;
			}
		}

		return $items;
	}

	/**
	 * AJAX handler for installing Charitable addons.
	 *
	 * @since 1.8.8
	 */
	public function ajax_install_charitable_addon() {
		// Security check
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Permission check
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
			return;
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( empty( $slug ) ) {
			wp_send_json_error( 'Addon slug is required.' );
			return;
		}

		// Get addon data
		$addon_data = $this->get_charitable_addon_data( $slug );
		if ( ! $addon_data ) {
			wp_send_json_error( 'Addon not found.' );
			return;
		}

		// Check if already installed
		if ( $addon_data['installed'] ) {
			wp_send_json_error( 'Addon is already installed.' );
			return;
		}

		// Check if download URL is available
		if ( ! isset( $addon_data['download_url'] ) || empty( $addon_data['download_url'] ) ) {
			wp_send_json_error( 'Download URL not available for this addon.' );
			return;
		}

		// Download and install the addon
		$result = $this->install_charitable_addon_from_url( $addon_data['download_url'], $slug );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
			return;
		}

		wp_send_json_success( array(
			'message' => __( 'Addon installed successfully.', 'charitable' ),
			'button_text' => __( 'Activate', 'charitable' ),
			'button_class' => 'charitable-dashboard-v2-activate-button',
			'button_action' => 'activate_addon'
		) );
	}

	/**
	 * AJAX handler for activating Charitable addons.
	 *
	 * @since 1.8.8
	 */
	public function ajax_activate_charitable_addon() {
		// Security check
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-admin' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
			return;
		}

		// Permission check
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
			return;
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( empty( $slug ) ) {
			wp_send_json_error( 'Addon slug is required.' );
			return;
		}

		// Get addon data
		$addon_data = $this->get_charitable_addon_data( $slug );
		if ( ! $addon_data ) {
			wp_send_json_error( 'Addon not found.' );
			return;
		}

		// Check if installed
		if ( ! $addon_data['installed'] ) {
			wp_send_json_error( 'Addon is not installed.' );
			return;
		}

		// Check if already active
		if ( $addon_data['active'] ) {
			wp_send_json_error( 'Addon is already active.' );
			return;
		}

		// Activate the plugin
		$result = activate_plugin( $addon_data['basename'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( 'Failed to activate addon: ' . $result->get_error_message() );
			return;
		}

		wp_send_json_success( array(
			'message' => __( 'Addon activated successfully.', 'charitable' ),
			'button_text' => __( 'Installed', 'charitable' ),
			'button_class' => 'charitable-dashboard-v2-installed-button',
			'button_action' => 'installed',
			'button_disabled' => true
		) );
	}

	/**
	 * Get curated third-party plugins list.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_curated_third_party_plugins() {
			return array(
			'monsterinsights',      // Analytics
			'wpforms-lite',         // Forms
			'aioseo',              // SEO
			'duplicator',          // Backup
			'pushengage',          // Notifications
			'uncanny-automator',   // Automation
			'envira-gallery',      // Gallery
			// Add more as you review
		);
	}

	/**
	 * Get custom SVG icons for plugins.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_custom_plugin_icons() {
		return array(
			'pushengage' => '<svg width="106" height="83" viewBox="0 0 106 83" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M102 40.4611C101.856 54.0439 97.8267 64.8812 89.768 74.1291C88.3289 75.863 86.6021 77.4525 84.8752 78.8975C83.2922 80.3425 80.9897 80.3425 79.6946 78.8975C78.3994 77.308 78.6872 75.1405 80.4141 73.6956C84.0117 70.6611 86.8899 67.1932 89.1924 63.1472C92.6461 57.2228 94.6608 50.8649 94.9486 43.929C95.5242 31.9357 91.7827 21.6763 83.8678 12.862C82.7166 11.706 81.4214 10.55 80.2702 9.39403C78.6872 7.94905 78.3994 5.63709 79.6946 4.19211C80.9897 2.60263 83.1483 2.60263 84.8752 4.19211C94.0852 12.1395 99.5536 22.2543 101.568 34.2477C101.856 36.7041 101.856 39.1606 102 40.4611Z" fill="#3B43FF"/><path d="M4 41.617C4.28781 26.5893 9.90015 14.018 21.2687 4.04761C22.8517 2.60263 25.1542 2.74713 26.3054 4.19211C27.6006 5.63709 27.3128 7.94905 25.7298 9.24953C17.9589 16.0409 13.0661 24.4218 11.4831 34.6811C9.32452 48.1194 12.9222 59.9683 21.9883 70.0831C23.1395 71.3836 24.5786 72.6841 25.8737 73.8401C27.4567 75.285 27.6006 77.4525 26.3054 78.8975C25.0103 80.3425 22.8517 80.3425 21.2687 79.042C11.1953 70.3721 5.43906 59.2458 4.28781 45.952C4.28781 45.2295 4.14391 44.507 4.14391 43.7845C4 43.062 4 42.3395 4 41.617Z" fill="#3B43FF"/><path d="M17.9589 41.4725C18.2467 30.7797 22.1322 21.8208 30.3348 14.7404C32.0617 13.2955 33.9325 13.44 35.3715 15.0294C36.6667 16.4744 36.5228 18.3529 34.7959 19.9424C30.9104 23.5548 27.7445 27.7453 26.1615 32.9472C23.1395 43.4955 25.2981 52.8879 32.7812 60.9798C33.5007 61.8467 34.3642 62.5692 35.0837 63.4362C36.3789 64.7367 36.5228 66.7597 35.3715 68.0602C34.2203 69.5051 31.9178 69.7941 30.6226 68.4936C28.464 66.4707 26.4493 64.4477 24.5786 62.1357C20.6931 56.9338 18.5345 51.0094 18.1028 44.3625C18.1028 43.929 17.9589 43.4955 17.9589 42.9175C17.9589 42.484 17.9589 42.0505 17.9589 41.4725Z" fill="#3B43FF"/><path d="M88.0411 41.617C87.7533 52.0209 83.8678 60.8353 76.0969 67.9157C74.9457 69.0716 73.5066 69.6496 71.9236 69.0716C69.6211 68.0602 69.0455 65.4592 70.7724 63.4362C72.7871 61.1243 75.0896 58.9568 76.8164 56.3558C83.7239 45.663 82.141 31.0687 73.0749 21.9653C72.3554 21.2429 71.6358 20.5204 70.9163 19.7979C69.6211 18.4974 69.4772 16.4744 70.6285 15.1739C71.9236 13.729 73.9383 13.44 75.5213 14.596C78.3994 16.9079 80.8458 19.6534 82.7166 22.8323C85.7386 27.7453 87.6094 33.0917 87.8972 38.8716C87.8972 39.7386 87.8972 40.6056 88.0411 41.617Z" fill="#3B43FF"/><path d="M73.7944 40.7501C73.7944 47.975 71.348 53.0324 66.743 57.3673C65.1601 58.9568 62.8576 58.9568 61.5624 57.3673C60.2673 55.9223 60.2673 53.7549 61.9941 52.3099C64.8722 49.5644 66.743 46.241 66.8869 42.195C67.0308 38.2936 65.8796 34.9701 63.2893 32.0802C62.8576 31.5022 62.2819 31.0687 61.8502 30.6352C60.4112 29.1902 60.2673 27.1673 61.5624 25.7223C62.8576 24.2773 65.0162 24.1328 66.5991 25.5778C69.0455 27.7453 70.9163 30.3462 72.2115 33.3807C73.3627 35.9816 73.9383 38.7271 73.7944 40.7501Z" fill="#3B43FF"/><path d="M41.2717 24.5663C43.2863 24.5663 44.2937 25.2888 45.0132 26.7338C45.7327 28.1787 45.301 29.6237 44.1498 30.6352C42.279 32.5137 40.696 34.3922 39.8326 36.9931C38.1057 42.484 39.257 47.2525 42.9985 51.4429C43.2863 51.8764 43.7181 52.1654 44.1498 52.5989C45.5888 54.1884 45.7327 56.2113 44.2937 57.6563C42.9985 58.9568 40.8399 59.1013 39.4009 57.8008C35.8032 54.6219 33.3568 50.8649 32.6373 46.0965C31.1982 38.2936 33.3568 31.5022 39.1131 26.1558C39.8326 25.1443 40.8399 24.8553 41.2717 24.5663Z" fill="#3B43FF"/><path d="M52.7841 48.4084C48.8987 48.4084 45.8767 45.374 45.8767 41.4725C45.8767 37.5711 49.0426 34.5367 52.928 34.5367C56.8135 34.5367 59.8355 37.5711 59.8355 41.4725C59.8355 45.5185 56.8135 48.4084 52.7841 48.4084Z" fill="#3B43FF"/></svg>',
			'all-in-one-seo-pack' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M10 20C15.524 20 20 15.524 20 10C20 4.47599 15.524 0 10 0C4.47599 0 0 4.47599 0 10C0 15.524 4.47599 20 10 20ZM8.40919 3.6618C8.27557 3.4572 8.02088 3.35699 7.79123 3.4405C7.52818 3.53236 7.27349 3.64092 7.02296 3.76618C6.80585 3.87474 6.69311 4.12526 6.74322 4.36743L6.91441 5.23591C6.96033 5.4572 6.86848 5.68685 6.69311 5.82881C6.41754 6.05846 6.16701 6.31315 5.94155 6.59708C5.79958 6.77244 5.57829 6.86848 5.36117 6.82255L4.50939 6.65136C4.2714 6.60125 4.02505 6.71399 3.92067 6.93946C3.86221 7.06472 3.80376 7.19415 3.74948 7.32777C3.6952 7.46138 3.64927 7.59081 3.60334 7.72443C3.52401 7.95825 3.62004 8.21712 3.82463 8.35491L4.54697 8.84342C4.73486 8.96869 4.82672 9.19415 4.80167 9.4238C4.76409 9.78706 4.76827 10.1545 4.80585 10.5094C4.8309 10.7349 4.73904 10.9603 4.55115 11.0898L3.82881 11.5825C3.62839 11.7203 3.53236 11.9749 3.61169 12.2129C3.70355 12.4802 3.80793 12.7432 3.93319 12.9979C4.04175 13.2192 4.28393 13.3319 4.52192 13.2818L5.37369 13.1065C5.59081 13.0605 5.81628 13.1524 5.95407 13.3319C6.17537 13.6117 6.43006 13.8706 6.70981 14.1002C6.88518 14.2422 6.97704 14.4718 6.93111 14.6931L6.76409 15.5616C6.71816 15.8038 6.82672 16.0543 7.04802 16.1628C7.17328 16.2255 7.29854 16.2839 7.42797 16.3382C7.55741 16.3925 7.68685 16.4426 7.81628 16.4885C8.19624 16.6221 8.73069 16.1461 9.12317 15.7912C9.31942 15.6159 9.43633 15.3695 9.4405 15.1065V15.1023V13.6493C9.4405 13.6326 9.4405 13.62 9.4405 13.6033C8.27557 13.3194 7.41127 12.2505 7.41127 10.977V9.43633C7.41127 9.31942 7.50313 9.22338 7.62004 9.22338H8.34238V7.70355C8.34238 7.49478 8.50939 7.32359 8.71399 7.32359C8.91858 7.32359 9.0856 7.49478 9.0856 7.70355V9.21921H11.0355V7.70355C11.0355 7.49478 11.2025 7.32359 11.4071 7.32359C11.6117 7.32359 11.7787 7.49478 11.7787 7.70355V9.21921H12.501C12.6138 9.21921 12.7098 9.31524 12.7098 9.43215V10.9729C12.7098 12.2881 11.7871 13.382 10.5679 13.6242C10.5679 13.6326 10.5679 13.6367 10.5679 13.6451V15.0898C10.5679 15.357 10.6931 15.6075 10.8935 15.7829C11.2944 16.1336 11.8372 16.6054 12.2171 16.4718C12.4802 16.38 12.7349 16.2714 12.9854 16.1461C13.2025 16.0376 13.3152 15.7871 13.2651 15.5449L13.0939 14.6764C13.048 14.4551 13.1399 14.2255 13.3152 14.0835C13.5866 13.8539 13.8413 13.5992 14.0668 13.3152C14.2088 13.1399 14.4301 13.0438 14.6472 13.0898L15.499 13.261C15.737 13.3111 15.9833 13.1983 16.0877 12.9729C16.1461 12.8476 16.2046 12.7182 16.2589 12.5846C16.3132 12.4509 16.3591 12.3215 16.405 12.1879C16.4843 11.9541 16.3883 11.6952 16.1837 11.5574L15.4614 11.0689C15.2735 10.9436 15.1816 10.7182 15.2067 10.4885C15.2443 10.1253 15.2401 9.75783 15.2025 9.40292C15.1775 9.17745 15.2693 8.95198 15.4572 8.82255L16.1795 8.32985C16.38 8.19207 16.476 7.93737 16.3967 7.69937C16.3048 7.43215 16.2004 7.1691 16.0752 6.91441C15.9666 6.69311 15.7244 6.58038 15.4864 6.63048L14.6347 6.80585C14.4175 6.85177 14.1921 6.75992 14.0543 6.58038C13.833 6.30063 13.5783 6.04175 13.2985 5.81211C13.1232 5.67015 13.0313 5.4405 13.0772 5.21921L13.2443 4.35073C13.2902 4.10856 13.1816 3.85804 12.9603 3.74948C12.8351 3.68685 12.7098 3.62839 12.5804 3.57411C12.4509 3.51983 12.3215 3.46973 12.1921 3.4238C11.9624 3.34447 11.7119 3.4405 11.5741 3.64927L11.0939 4.38831C10.9687 4.5762 10.7474 4.67223 10.5261 4.65136C10.167 4.61378 9.81211 4.61795 9.46138 4.65553C9.24008 4.68058 9.01879 4.58455 8.89353 4.39666L8.40919 3.6618Z" fill="#005AE0"/>
			</svg>',
			'duplicator' => '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M46.2041 20.0816H44.8571C44.7347 20.0816 44.6122 20.0408 44.449 20.0408C43.9184 19.9184 43.4286 19.7143 42.9388 19.4694C42.8163 19.3878 42.6939 19.3469 42.6122 19.2653C42.5714 19.2245 42.4898 19.1837 42.449 19.1429C42.3673 19.102 42.3265 19.0612 42.2449 18.9796C42.0408 18.8163 41.8775 18.6531 41.6735 18.449C41.6735 18.449 41.6735 18.449 41.6327 18.4082C41.5102 18.2857 41.4286 18.1633 41.3469 18.0408C41.2245 17.8776 41.1429 17.7143 41.0204 17.551C40.898 17.3061 40.7755 17.0612 40.6531 16.7755C40.6122 16.6122 40.5306 16.449 40.4898 16.2857C40.449 16.1633 40.449 16.0408 40.4082 15.9592C40.449 15.8367 40.4082 15.6735 40.4082 15.5102C40.4082 15.4694 40.4082 15.4286 40.4082 15.3878C40.4082 14.898 40.449 14.449 40.5714 14C40.7755 13.3061 41.102 12.6531 41.4694 12.0408C41.551 11.9592 41.6327 11.8776 41.6735 11.7551L42.4082 10.9796C43.0204 10.3673 43.0612 9.34694 42.4898 8.69388C41.7551 7.79592 40.9388 6.93878 40.0408 6.16327L15.3061 30.5714C14.898 30.9388 14.2449 30.6939 14.2449 30.1224C14.2857 25.8367 14.3673 18.5306 14.4082 14.8571C14.4082 14.2449 15.2245 14.0408 15.551 14.5714L18.1224 19.0612C18.3265 19.4286 18.8163 19.4694 19.102 19.1837L30.5714 7.71429C30.5714 7.71429 30.5306 7.71428 30.5306 7.67347C30.3673 7.59184 30.2449 7.5102 30.0816 7.42857C30 7.34694 29.8776 7.30612 29.7959 7.22449C29.6735 7.14286 29.551 7.02041 29.4286 6.93878C29.3878 6.89796 29.3878 6.89796 29.3469 6.85714C28.9796 6.4898 28.7347 6.12245 28.4898 5.71429C28.1224 5.02041 27.8776 4.28571 27.7551 3.55102C27.7143 3.42857 27.7143 3.26531 27.6735 3.14286C27.6735 3.06122 27.6735 2.97959 27.6735 2.89796V1.83673C27.6735 0.897959 26.9796 0.163265 26.0816 0.0816327C25.3878 0.0408163 24.6939 0 24 0C23.3469 0 22.6531 0.0408163 22 0.0816327C21.102 0.163265 20.4082 0.897959 20.4082 1.79592C20.4082 2.32653 20.3673 2.93878 20.2449 3.55102C20.1224 4.28571 19.8776 5.02041 19.5102 5.71429C19.3061 6.12245 19.0204 6.4898 18.6531 6.81633C18.6122 6.85714 18.6122 6.85714 18.5714 6.89796C18.449 7.02041 18.3265 7.10204 18.2041 7.18367C18.1224 7.26531 18 7.30612 17.9184 7.38775C17.7551 7.46939 17.6327 7.55102 17.4694 7.63265C17.2245 7.7551 16.9388 7.87755 16.6939 7.91837C16.4898 8 16.3265 8.04082 16.1224 8.04082C15.9592 8.08163 15.8367 8.08163 15.6735 8.08163H15.6327C15.3878 8.08163 15.102 8.08163 14.8571 8.08163C14.7755 8.08163 14.6939 8.08163 14.6122 8.04082C14.5306 8.04082 14.449 8 14.4082 8C14.2857 7.95918 14.1633 7.95918 14.0408 7.91837C13.5102 7.7551 13.0204 7.55102 12.5714 7.30612C12.3265 7.18367 12.1224 7.02041 11.9184 6.85714C11.8367 6.77551 11.7551 6.73469 11.6735 6.65306L10.8571 5.79592C10.2449 5.14286 9.18367 5.06122 8.4898 5.63265C7.38775 6.57143 6.36735 7.59184 5.46939 8.69388C4.89796 9.38775 4.97959 10.3673 5.55102 10.9796L6.28571 11.7551C6.36735 11.8367 6.44898 11.9184 6.4898 12.0408C6.85714 12.6122 7.22449 13.3061 7.38775 14C7.5102 14.449 7.55102 14.898 7.55102 15.3878C7.55102 15.4286 7.55102 15.4694 7.55102 15.5102C7.55102 15.6735 7.5102 15.7959 7.5102 15.9592C7.5102 16.0816 7.46939 16.2041 7.42857 16.2857C7.38775 16.449 7.34694 16.6122 7.26531 16.7755C7.18367 17.0612 7.06122 17.3061 6.89796 17.551C6.81633 17.7143 6.69388 17.8776 6.57143 18.0408C6.4898 18.1633 6.36735 18.2857 6.28571 18.4082C6.28571 18.4082 6.28571 18.4082 6.2449 18.449C6.08163 18.6531 5.87755 18.8163 5.67347 18.9796C5.59184 19.0204 5.55102 19.102 5.46939 19.1429C5.42857 19.1837 5.34694 19.2245 5.30612 19.2653C5.18367 19.3469 5.06122 19.3878 4.97959 19.4694C4.4898 19.7143 4 19.9184 3.46939 20.0408C3.34694 20.0408 3.22449 20.0816 3.06122 20.0816H1.71429C0.857143 20.0816 0.0816327 20.7347 0 21.6327C0.0408163 22.4082 0 23.2245 0 24C0 24.6531 0.0408163 25.3061 0.0816327 25.9592C0.163265 26.8571 0.857143 27.551 1.7551 27.551L2.97959 27.5918C3.10204 27.5918 3.22449 27.5918 3.34694 27.6327C3.83673 27.7551 4.28571 27.9184 4.73469 28.1224C5.14286 28.3265 5.5102 28.6122 5.87755 28.9388C5.91837 28.9796 5.91837 28.9796 5.95918 29.0204C6.08163 29.1429 6.16327 29.2653 6.2449 29.3469C6.32653 29.4286 6.40816 29.5102 6.44898 29.6327C6.53061 29.7959 6.65306 29.9184 6.73469 30.0816C6.85714 30.3265 6.97959 30.5714 7.06122 30.8571C7.14286 31.0612 7.18367 31.2245 7.22449 31.4286C7.26531 31.5918 7.26531 31.7143 7.30612 31.8775V31.9184C7.34694 32.1633 7.34694 32.449 7.30612 32.6939C7.30612 32.7755 7.30612 32.8571 7.30612 32.9388C7.30612 33.0204 7.30612 33.102 7.26531 33.1429C7.22449 33.2653 7.22449 33.3878 7.18367 33.5102C7.06122 34.0408 6.85714 34.5306 6.61225 34.9796C6.53061 35.1429 6.44898 35.3061 6.32653 35.4286C6.2449 35.5102 6.20408 35.6327 6.08163 35.7143L5.30612 36.5714C4.69388 37.1837 4.61225 38.1633 5.14286 38.8571C6 39.9592 6.97959 40.9796 8 41.8775L33.1429 17.1837C33.551 16.8163 34.2041 17.0612 34.2041 17.6327C34.1633 21.9184 34.0816 29.2245 34.0408 32.898C34.0408 33.5102 33.2245 33.7143 32.898 33.1837L30.3265 28.6939C30.1224 28.3265 29.6327 28.2857 29.3469 28.5714L17.3878 40.5306C17.3878 40.5306 17.4286 40.5306 17.4286 40.5714C17.551 40.6531 17.6735 40.7347 17.7959 40.8571C17.7959 40.8571 17.7959 40.8571 17.8367 40.898C18.0408 41.0612 18.2041 41.2245 18.3673 41.4286C18.4082 41.5102 18.4898 41.551 18.5306 41.6327C18.5714 41.6735 18.6122 41.7551 18.6531 41.7959C18.7347 41.9184 18.7755 42 18.8571 42.1224C19.1429 42.5714 19.3469 43.0612 19.4694 43.5918C19.7143 44.4898 19.7959 45.3878 19.7959 46.1633C19.7959 47.0612 20.4898 47.7959 21.3469 47.8776C22.2041 47.9592 23.102 48.0408 24 48.0408C24.898 48.0408 25.7551 48 26.6531 47.8776C27.5102 47.7959 28.2041 47.0612 28.2041 46.1633C28.2041 45.3878 28.2857 44.449 28.5306 43.5918C28.6531 43.102 28.8571 42.6122 29.1429 42.1224C29.2245 42 29.2653 41.9184 29.3469 41.7959C29.3878 41.7551 29.4286 41.6735 29.4694 41.6327C29.5102 41.551 29.5918 41.5102 29.6327 41.4286C29.7959 41.2245 29.9592 41.0612 30.1633 40.898C30.1633 40.898 30.1633 40.898 30.2041 40.8571C30.3265 40.7755 30.449 40.6531 30.5714 40.5714C30.7347 40.449 30.898 40.3673 31.102 40.2857C31.3469 40.1633 31.5918 40.0408 31.8775 39.9592C32.0408 39.9184 32.2041 39.8775 32.3673 39.8367C32.4898 39.7959 32.6122 39.7959 32.7347 39.7959C32.898 39.7959 33.0204 39.7551 33.1837 39.7551C33.2245 39.7551 33.2653 39.7551 33.3061 39.7551C33.7959 39.7551 34.2449 39.8367 34.6939 39.9592C35.3061 40.1633 35.8775 40.449 36.4082 40.8163C36.4898 40.898 36.6122 40.9796 36.6939 41.0612L37.551 41.9592C38.2041 42.6122 39.2245 42.6531 39.9184 42.0816C41.0204 41.102 42 40.0816 42.898 38.9388C43.4286 38.2449 43.3469 37.2653 42.7347 36.6531L41.9592 35.8776C41.8776 35.7959 41.7959 35.7143 41.7143 35.5918C41.6326 35.4286 41.5102 35.3061 41.4286 35.1429C41.1837 34.6939 40.9796 34.2041 40.8571 33.6735C40.8163 33.551 40.7755 33.4286 40.7755 33.3061C40.7755 33.2245 40.7347 33.1429 40.7347 33.102C40.7347 33.0204 40.7347 32.9388 40.7347 32.8571C40.7347 32.6122 40.7347 32.3673 40.7347 32.0816V32.0408C40.7347 31.8776 40.7755 31.7551 40.8163 31.5918C40.8571 31.3878 40.898 31.2245 40.9796 31.0204C41.0612 30.7755 41.1837 30.4898 41.3061 30.2449C41.3878 30.0816 41.4694 29.9592 41.5918 29.7959C41.6735 29.7143 41.7143 29.5918 41.7959 29.5102C41.8775 29.3878 42 29.2653 42.0816 29.1837C42.1224 29.1429 42.1224 29.1429 42.1633 29.102C42.4898 28.7755 42.898 28.4898 43.3061 28.2857C43.7551 28.0408 44.2041 27.8776 44.6939 27.7959C44.8163 27.7551 44.9388 27.7551 45.0612 27.7551L46.2857 27.7143C47.1837 27.6735 47.8775 26.9796 47.9592 26.1224C48 25.4694 48.0408 24.8163 48.0408 24.1633C48.0408 23.3469 48 22.5714 47.9184 21.7959C47.7959 20.7755 47.0612 20.1224 46.2041 20.0816Z" fill="#FE4715"/>
			</svg>',
			// Add more custom SVG icons as needed
		);
	}

	/**
	 * Get curated Charitable addons list.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_curated_charitable_addons() {
		return array(
			'charitable-recurring',
			'charitable-donortrust',
			'charitable-gift-aid',
			// Add more as you review
		);
	}

	/**
	 * Get custom title overrides for Charitable addons.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_charitable_addon_title_overrides() {
		return array(
			'charitable-recurring' => __( 'Recurring Donations', 'charitable' ),
			'charitable-donortrust' => __( 'DonorTrust', 'charitable' ),
			'charitable-gift-aid' => __( 'Gift Aid', 'charitable' ),
			'charitable-ambassadors' => __( 'Ambassadors', 'charitable' ),
			'charitable-anonymous-donations' => __( 'Anonymous Donations', 'charitable' ),
			'charitable-fee-relief' => __( 'Fee Relief', 'charitable' ),
			// Add more overrides as needed
		);
	}

	/**
	 * Get third-party plugin data.
	 *
	 * @since 1.8.8
	 * @return array
	 */
	private function get_third_party_plugin_data() {
		$plugins_class = Charitable_Admin_Plugins_Third_Party::get_instance();
		return $plugins_class->get_plugins( false );
	}

	/**
	 * Get Charitable addon data.
	 *
	 * @since 1.8.8
	 * @param string $slug Addon slug.
	 * @return array|false
	 */
	private function get_charitable_addon_data( $slug ) {
		// Debug: Output current plan and license info
		$addons_directory = Charitable_Addons_Directory::get_instance();
		$current_plan     = $addons_directory->get_current_plan_slug();
		$licenses         = charitable_get_licenses();
		$is_pro           = charitable_is_pro();

		// First check if the addon is installed as a WordPress plugin
		$plugin_file = $slug . '/' . $slug . '.php';
		$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
		$is_active = is_plugin_active( $plugin_file );

		// If not installed, try to get addon data from directory
		if ( ! $is_installed ) {
			$addons_directory = Charitable_Addons_Directory::get_instance();
			$addon = $addons_directory->get_addon( $slug );

			if ( ! $addon ) {
				// Check if user has access to this addon by checking if it's in the recommended addons
				$all_addons = $addons_directory->get_addons();
				$has_access = false;

				// Check if addon is in recommended, licensed, or unlicensed categories
				foreach ( $all_addons as $category => $addons ) {
					foreach ( $addons as $addon_data ) {
						if ( isset( $addon_data['slug'] ) && $addon_data['slug'] === $slug ) {
							$has_access = true;
							$addon = $addon_data;
							break 2;
						}
					}
				}

				if ( ! $has_access ) {
					return false;
				}

				// Process the raw addon data to add status, action, and plugin_allow fields
				// This mimics what get_addon() does but for raw data from get_addons()
				$addon = $this->process_raw_addon_data( $addon, $slug );
			}
		} else {
			// Create addon data for installed plugin
			$addon = array(
				'name' => ucwords( str_replace( '-', ' ', $slug ) ),
				'status' => $is_active ? 'active' : 'installed',
				'action' => $is_active ? 'deactivate' : 'activate',
				'path' => $plugin_file,
				'plugin_allow' => true,
				'upgrade_url' => '',
				// Installed plugin may not have directory metadata; default license to empty
				'license' => array(),
			);
		}

		// Get title with overrides
		$title_overrides = $this->get_charitable_addon_title_overrides();
		if ( isset( $title_overrides[ $slug ] ) ) {
			$title = $title_overrides[ $slug ];
		} else {
			$title = isset( $addon['name'] ) ? $addon['name'] : ucwords( str_replace( '-', ' ', $slug ) );
			$title = preg_replace( '/^Charitable\s+/i', '', $title );
		}

		// Get local icon
		$icon = $this->get_charitable_addon_icon( $slug );

		$final_data = array(
			'title' => $title,
			'description' => $this->get_charitable_addon_description( $slug ),
			'icon' => $icon,
			'installed' => $addon['status'] === 'installed' || $addon['status'] === 'active',
			'active' => $addon['status'] === 'active',
			'download_url' => isset( $addon['download_link'] ) ? $addon['download_link'] : '',
			'slug' => $slug,
			'type' => 'charitable_addon',
			'basename' => $addon['path'],
			'status' => $addon['status'],
			'action' => $addon['action'],
			'plugin_allow' => $addon['plugin_allow'],
			'upgrade_url' => $addon['upgrade_url'] ?? '',
			'license' => isset( $addon['license'] ) ? (array) $addon['license'] : array(),
		);

		return $final_data;
	}

	/**
	 * Get Charitable addon description.
	 *
	 * @since 1.8.8.4
	 * @param string $slug Addon slug.
	 * @return string Description.
	 */
	public function get_charitable_addon_description( $slug ) {

		$descriptions = array(
			// Charitable official addons
			'charitable-recurring' => __( 'Let donors give automatically on a daily, weekly, monthly, or yearly schedule.', 'charitable' ),
			'charitable-donortrust' => __( 'Build Trust and Boost Donations with Real-Time Social Proof.', 'charitable' ),
			'charitable-gift-aid' => __( 'Allow UK donors to add Gift Aid and boost eligible donations by 25%.', 'charitable' ),
			'charitable-anonymous-donations' => __( 'Give supporters the option to hide their name from public donations.', 'charitable' ),
			'charitable-fee-relief' => __( 'Let donors optionally cover processing fees.', 'charitable' ),

			// Add more addon descriptions here as they are surfaced in the dashboard
			'google-analytics-for-wordpress' => __( 'Get detailed insights into your donation traffic and campaign performance.', 'charitable' ),
			'wpforms-lite' => __( 'Create custom donation forms and surveys to engage with supporters.', 'charitable' ),
			'all-in-one-seo-pack' => __( 'Optimize your campaigns for search engines to reach more potential donors.', 'charitable' ),
			'duplicator' => __( 'Safely backup and migrate your donation data and campaign content.', 'charitable' ),
			'pushengage' => __( 'Send targeted push notifications to re-engage donors and drive contributions.', 'charitable' ),
			'uncanny-automator' => __( 'Automate your donation workflows and connect with other fundraising tools.', 'charitable' ),
			'envira-gallery' => __( 'Create beautiful photo galleries to showcase your charitable impact.', 'charitable' ),
		);

		if ( isset( $descriptions[ $slug ] ) ) {
			return $descriptions[ $slug ];
		}

		// Sensible default if an addon is missing a curated description
		return __( 'Enhance your campaign with powerful fundraising features.', 'charitable' );
	}

	/**
	 * Get Charitable addon icon from local assets.
	 *
	 * @since 1.8.8
	 * @param string $slug Addon slug.
	 * @return string Icon URL or generic icon.
	 */
	private function get_charitable_addon_icon( $slug ) {
		// Map addon slugs to their base filenames (without extension)
		$icon_mapping = array(
			'charitable-donortrust' => 'donortrust',
			'charitable-gift-aid' => 'gift-aid',
			'charitable-recurring' => 'recurring-donations',
			'charitable-anonymous-donations' => 'anonymous-donations',
			'charitable-fee-relief' => 'fee-relief',
		);

		// Check if we have a local icon for this addon
		if ( isset( $icon_mapping[ $slug ] ) ) {
			$base_filename = $icon_mapping[ $slug ];
			$base_path = charitable()->get_path( 'assets' ) . 'images/plugins/charitable/' . $base_filename;

			// First try SVG
			$svg_path = $base_path . '.svg';
			if ( file_exists( $svg_path ) ) {
				$svg_content = file_get_contents( $svg_path );
				return $svg_content ? $svg_content : $this->get_generic_plugin_icon();
			}

			// Fallback to PNG
			$png_path = $base_path . '.png';
			if ( file_exists( $png_path ) ) {
				$png_url = charitable()->get_path( 'directory', false ) . 'assets/images/plugins/charitable/' . $base_filename . '.png';
				return '<img src="' . esc_url( $png_url ) . '" alt="' . esc_attr( $base_filename ) . '" width="20" height="20" />';
			}
		}

		// Fall back to generic puzzle piece icon
		return $this->get_generic_plugin_icon();
	}

	/**
	 * Get generic plugin icon SVG.
	 *
	 * @since 1.8.8
	 * @return string Generic icon SVG.
	 */
	private function get_generic_plugin_icon() {
		return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 2L12 6L8 10L4 6L8 2Z" fill="#6B7280"/>
			<path d="M8 10L12 14L8 18L4 14L8 10Z" fill="#6B7280"/>
			<path d="M12 6L16 10L12 14L8 10L12 6Z" fill="#6B7280"/>
		</svg>';
	}

	/**
	 * Format plugin item for display.
	 *
	 * @since 1.8.8
	 * @param array  $plugin_data Plugin data.
	 * @param string $type Plugin type.
	 * @return array
	 */
	private function format_plugin_item( $plugin_data, $type ) {
		$button_state = $this->get_button_state( $plugin_data, $type );


		$item = array(
			'title' => $plugin_data['title'],
			'description' => $this->get_charitable_addon_description( $plugin_data['slug'] ),
			'button_text' => $button_state['text'],
			'button_class' => $button_state['class'],
			'button_action' => $button_state['action'],
			'icon' => $this->get_plugin_icon( $plugin_data ),
			'slug' => $plugin_data['slug'],
			'basename' => $plugin_data['basename'],
			'type' => $type
		);

		// Always add setup URL if available (for when button changes to setup state)
		if ( ! empty( $plugin_data['setup'] ) ) {
			$item['setup_url'] = $plugin_data['setup'];
		}

		return $item;
	}

	/**
	 * Format addon item for display.
	 *
	 * @since 1.8.8
	 * @param array $addon_data Addon data.
	 * @return array
	 */
	private function format_addon_item( $addon_data ) {
		$button_state = $this->get_button_state( $addon_data, 'charitable_addon' );

		$item = array(
			'title' => $addon_data['title'],
			'description' => isset( $addon_data['description'] ) ? $addon_data['description'] : $this->get_charitable_addon_description( $addon_data['slug'] ),
			'button_text' => $button_state['text'],
			'button_class' => $button_state['class'],
			'button_action' => $button_state['action'],
			'icon' => $addon_data['icon'],
			'slug' => $addon_data['slug'],
			'type' => 'charitable_addon'
		);

		// Add upgrade URL for upgrade buttons
		if ( $button_state['action'] === 'upgrade' && isset( $button_state['upgrade_url'] ) ) {
			$item['upgrade_url'] = $button_state['upgrade_url'];
		}

		// Add disabled state for installed buttons
		if ( isset( $button_state['disabled'] ) && $button_state['disabled'] ) {
			$item['button_disabled'] = true;
		}

		return $item;
	}

	/**
	 * Install a Charitable addon from download URL.
	 *
	 * @param string $download_url The download URL for the addon.
	 * @param string $slug         The addon slug.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function install_charitable_addon_from_url( $download_url, $slug ) {
		// Include WordPress filesystem API
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'unzip_file' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Download the addon zip file
		$temp_file = download_url( $download_url );

		if ( is_wp_error( $temp_file ) ) {
			return new WP_Error( 'download_failed', 'Failed to download addon: ' . $temp_file->get_error_message() );
		}

		// Get the WordPress filesystem
		$wp_filesystem = $this->get_wp_filesystem();
		if ( ! $wp_filesystem ) {
			unlink( $temp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			return new WP_Error( 'filesystem_error', 'Unable to access WordPress filesystem.' );
		}

		// Create the plugin directory
		$plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
		if ( ! $wp_filesystem->is_dir( $plugin_dir ) ) {
			$wp_filesystem->mkdir( $plugin_dir );
		}

		// Unzip the file
		$result = unzip_file( $temp_file, WP_PLUGIN_DIR );

		// Clean up temp file
		unlink( $temp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'unzip_failed', 'Failed to extract addon: ' . $result->get_error_message() );
		}

		return true;
	}

	/**
	 * Get WordPress filesystem instance.
	 *
	 * @return WP_Filesystem_Base|false
	 */
	private function get_wp_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Process raw addon data to add status, action, and plugin_allow fields.
	 * This mimics what get_addon() does but for raw data from get_addons().
	 *
	 * @version 1.8.8.4
	 *
	 * @param array  $raw_addon The raw addon data from get_addons().
	 * @param string $slug      The addon slug.
	 * @return array The processed addon data.
	 */
	private function process_raw_addon_data( $raw_addon, $slug ) {
		// Check if plugin is installed
		$plugin_file = $slug . '/' . $slug . '.php';
		$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
		$is_active = $is_installed ? is_plugin_active( $plugin_file ) : false;
		$is_pro = charitable_is_pro();
		$current_plan = Charitable_Addons_Directory::get_current_plan_slug();
		$required_plans = isset( $raw_addon['license'] ) ? (array) $raw_addon['license'] : array();
		$plan_allows = empty( $required_plans ) ? false : in_array( $current_plan, $required_plans, true );

		// Determine status and action based on installation state and license
		if ( $is_active ) {
			$status = 'active';
			$action = 'deactivate';
			$plugin_allow = true;
		} elseif ( $is_installed ) {
			$status = 'installed';
			$action = 'activate';
			$plugin_allow = true;
		} else {
			$status = 'uninstalled';
			// Check if user has a valid license and required plan before allowing install
			if ( $is_pro && $plan_allows ) {
				$action = 'install';
				$plugin_allow = true;
			} else {
				$action = 'upgrade';
				$plugin_allow = false;
				// Provide upgrade URL so the button can link to pricing
				$addons_directory = Charitable_Addons_Directory::get_instance();
				if ( method_exists( $addons_directory, 'charitable_get_upgrade_link' ) ) {
					$raw_addon['upgrade_url'] = $addons_directory->charitable_get_upgrade_link();
				}
			}
		}

		// Add the processed fields to the raw addon data
		$raw_addon['status'] = $status;
		$raw_addon['action'] = $action;
		$raw_addon['plugin_allow'] = $plugin_allow;
		$raw_addon['path'] = $plugin_file;

		return $raw_addon;
	}

	/**
	 * Get button state for plugin/addon.
	 *
	 * @since 1.8.8
	 * @param array  $item_data Item data.
	 * @param string $type Item type.
	 * @return array
	 */
	private function get_button_state( $item_data, $type = 'third_party' ) {
		if ( $type === 'third_party' ) {
			if ( ! $item_data['installed'] ) {
					return array(
						'text' => __( 'Install & Activate', 'charitable' ),
						'action' => 'install',
					'class' => '' // Same as hardcoded
				);
			} elseif ( ! $item_data['active'] ) {
				return array(
					'text' => __( 'Activate', 'charitable' ),
					'action' => 'activate',
					'class' => 'charitable-dashboard-v2-activate-button' // Same as hardcoded
				);
			} else {
				return array(
					'text' => __( 'Setup', 'charitable' ),
					'action' => 'setup',
					'class' => 'charitable-dashboard-v2-setup-button' // New class for setup
				);
			}
		} else { // Charitable addon
			// Use the action from addons directory
			switch ( $item_data['action'] ) {
				case 'upgrade':
				case 'license':
					return array(
						'text' => __( 'Upgrade', 'charitable' ),
						'action' => 'upgrade',
						'class' => 'charitable-dashboard-v2-upgrade-button',
						'upgrade_url' => $item_data['upgrade_url']
					);
				case 'install':
					return array(
						'text' => 'Install & Activate',
						'action' => 'install_addon',
						'class' => ''
					);
				case 'activate':
					return array(
						'text' => __( 'Activate', 'charitable' ),
						'action' => 'activate_addon',
						'class' => 'charitable-dashboard-v2-activate-button'
					);
				case 'deactivate':
					return array(
						'text' => __( 'Installed', 'charitable' ),
						'action' => 'installed',
						'class' => 'charitable-dashboard-v2-installed-button',
						'disabled' => true
					);
				default:
					return array(
						'text' => __( 'Upgrade', 'charitable' ),
						'action' => 'upgrade',
						'class' => 'charitable-dashboard-v2-upgrade-button',
						'upgrade_url' => $item_data['upgrade_url']
					);
			}
		}
	}

	/**
	 * Get plugin icon.
	 *
	 * @since 1.8.8
	 * @param array $plugin_data Plugin data.
	 * @return string
	 */
	private function get_plugin_icon( $plugin_data ) {
		// Step 1: Try PNG icon from plugin data (check if file exists)
		if ( ! empty( $plugin_data['icon'] ) ) {
			$icon_url = $plugin_data['icon'];
			if ( filter_var( $icon_url, FILTER_VALIDATE_URL ) ) {
				// Convert URL to server path for file existence check
				$icon_path = $this->url_to_server_path( $icon_url );
				if ( $icon_path && file_exists( $icon_path ) ) {
					return '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $plugin_data['title'] ) . '" width="20" height="20" />';
				}
			}
		}

		// Step 2: Try custom SVG icon
		$custom_icons = $this->get_custom_plugin_icons();
		if ( isset( $custom_icons[ $plugin_data['slug'] ] ) ) {
			return $custom_icons[ $plugin_data['slug'] ];
		}

		// Step 3: Try the 'icon' from plugin data even if file doesn't exist
		// (might be a different format or external URL)
		if ( ! empty( $plugin_data['icon'] ) ) {
			$icon_url = $plugin_data['icon'];
			if ( filter_var( $icon_url, FILTER_VALIDATE_URL ) ) {
				// Check if the URL actually exists
				$response = wp_remote_head( $icon_url, array( 'timeout' => 5 ) );
				if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
					return '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $plugin_data['title'] ) . '" width="20" height="20" />';
				}
			}
		}

		// Step 4: Default puzzle piece icon
		return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 2a2 2 0 00-2 2v2H4a2 2 0 00-2 2v2a2 2 0 002 2h2v2a2 2 0 002 2h2a2 2 0 002-2v-2h2a2 2 0 002-2V8a2 2 0 00-2-2h-2V4a2 2 0 00-2-2H8z" fill="#9CA3AF"/>
			<path d="M10 6a1 1 0 100 2 1 1 0 000-2zM6 10a1 1 0 100 2 1 1 0 000-2zM14 10a1 1 0 100 2 1 1 0 000-2zM10 14a1 1 0 100 2 1 1 0 000-2z" fill="#6B7280"/>
		</svg>';
	}

	/**
	 * Convert URL to server file path.
	 *
	 * @since 1.8.8
	 * @param string $url The URL to convert.
	 * @return string|false The server path or false if conversion fails.
	 */
	private function url_to_server_path( $url ) {
		// Get the site URL and uploads directory
		$site_url = get_site_url();
		$upload_dir = wp_upload_dir();

		// Check if it's a local URL
		if ( strpos( $url, $site_url ) === 0 ) {
			// Remove site URL from the beginning
			$relative_path = str_replace( $site_url, '', $url );
			// Get the absolute path
			$absolute_path = ABSPATH . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		// Check if it's a plugin URL
		$plugin_url = plugins_url();
		if ( strpos( $url, $plugin_url ) === 0 ) {
			// Remove plugin URL from the beginning
			$relative_path = str_replace( $plugin_url, '', $url );
			// Get the absolute path
			$absolute_path = WP_PLUGIN_DIR . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		// Check if it's a theme URL
		$theme_url = get_template_directory_uri();
		if ( strpos( $url, $theme_url ) === 0 ) {
			// Remove theme URL from the beginning
			$relative_path = str_replace( $theme_url, '', $url );
			// Get the absolute path
			$absolute_path = get_template_directory() . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		// Check if it's a content URL
		$content_url = content_url();
		if ( strpos( $url, $content_url ) === 0 ) {
			// Remove content URL from the beginning
			$relative_path = str_replace( $content_url, '', $url );
			// Get the absolute path
			$absolute_path = WP_CONTENT_DIR . ltrim( $relative_path, '/' );
			return $absolute_path;
		}

		return false;
	}

	/**
	 * Track recommendation interaction.
	 *
	 * @since 1.8.8
	 * @param string $action Action performed.
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_type Plugin type.
	 * @param string $context Context of interaction.
	 * @return void
	 */
	private function track_recommendation_click( $action, $plugin_slug, $plugin_type, $context = 'dashboard_enhance' ) {
		if ( ! charitable_get_usage_tracking_setting() ) {
			return;
		}

		$tracked_data = get_option( 'charitable_recommended_plugins_' . $action, array() );
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$tracked_data[] = array(
			'plugin' => $plugin_slug,
			'type' => $plugin_type,
			'context' => $context,
			'timestamp' => time(),
			'user_agent' => $user_agent,
			'referrer' => wp_get_referer() ?: 'direct'
		);

		// Keep only last 100 entries
		if ( count( $tracked_data ) > 100 ) {
			$tracked_data = array_slice( $tracked_data, -100 );
		}

		update_option( 'charitable_recommended_plugins_' . $action, $tracked_data );
	}

	/**
	 * Check if enhance section should be shown.
	 *
	 * @since 1.8.8
	 * @return bool
	 */
	private function should_show_enhance_section() {
		$items = $this->get_enhance_items();
		return count( $items ) > 0; // Hide if no items to show
	}

		/**
		 * Render the latest updates section.
		 *
		 * @since 1.8.8
		 */
		public function render_latest_updates_section() {
			// Check if there are blog posts to determine footer link text
			$posts = $this->get_blog_posts();
			$has_posts = ! empty( $posts ) && ! $this->has_blog_posts_error( $posts );
			$footer_link_text = $has_posts ? __( 'Read More', 'charitable' ) : __( 'Read Blog', 'charitable' );

			// Check if user is more than 3 updates behind
			$updates_behind = $this->get_updates_behind();
			$show_version_update = $updates_behind > 3;
			$section_class = 'charitable-dashboard-v2-section';
			if ( ! $show_version_update ) {
				$section_class .= ' no-update-alert';
			}
			?>
			<section id="charitable-dashboard-v2-latest-updates" class="<?php echo esc_attr( $section_class ); ?>">
				<header class="charitable-dashboard-v2-section-header">
					<h3>Latest Updates</h3>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<?php if ( $show_version_update ) : ?>
					<div class="charitable-dashboard-v2-latest-updates-background">
						<div class="charitable-dashboard-v2-latest-updates-background-content">
							<div class="charitable-dashboard-v2-latest-updates-flex">
								<div class="charitable-dashboard-v2-latest-updates-icon">
									<svg class="charitable-dashboard-v2-latest-updates-icon-placeholder" width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
										<circle cx="13" cy="13" r="13" fill="#eaa251"/>
										<path d="M13 6.5L19.5 17.5H6.5L13 6.5Z" fill="white"/>
										<path d="M13 10.5V14.5" stroke="#eaa251" stroke-width="2" stroke-linecap="round"/>
										<circle cx="13" cy="17" r="1" fill="#eaa251"/>
									</svg>
								</div>
								<div class="charitable-dashboard-v2-latest-updates-content">
									<h4 class="charitable-dashboard-v2-latest-updates-headline">You are currently <?php echo esc_html( $updates_behind ); ?> updates behind. Please upgrade Charitable to take advantage of:</h4>
									<div class="charitable-dashboard-v2-latest-updates-features">
										<?php $this->render_update_features(); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<?php $this->render_blog_posts(); ?>
					<footer class="charitable-dashboard-v2-latest-updates-footer">
						<div class="charitable-dashboard-v2-latest-updates-footer-content">
							<div class="charitable-dashboard-v2-latest-updates-footer-left">
								<a href="https://wpcharitable.com/blog" target="_blank" rel="noopener noreferrer" class="charitable-dashboard-v2-blog-link">
									<span class="charitable-dashboard-v2-blog-text"><?php echo esc_html( $footer_link_text ); ?></span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}

		/**
		 * Render update features.
		 *
		 * @since 1.8.8
		 */
		private function render_update_features() {
			$features = array(
				__( 'Enhancement Payments', 'charitable' ),
				__( 'Reply-To Donors', 'charitable' ),
				__( 'Square Gateway', 'charitable' ),
				__( 'DonorTrust', 'charitable' )
			);

			foreach ( $features as $feature ) :
				?>
				<span class="charitable-dashboard-v2-latest-updates-feature">
					<svg class="charitable-dashboard-v2-latest-updates-checkmark" width="9" height="7" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M9.03612 1.89488C9.03612 1.74898 8.97797 1.60391 8.87287 1.49973L8.08169 0.708539C7.9775 0.603482 7.8316 0.545288 7.68657 0.545288C7.54067 0.545288 7.39564 0.603482 7.29054 0.708539L3.47366 4.52891L1.76365 2.81371C1.65855 2.70861 1.51352 2.65041 1.36762 2.65041C1.22259 2.65041 1.07669 2.70861 0.972505 2.81371L0.181318 3.60485C0.0762192 3.70908 0.0180664 3.85498 0.0180664 4.00001C0.0180664 4.14504 0.0762192 4.29094 0.181318 4.39517L3.0785 7.29148C3.18273 7.39658 3.32863 7.45473 3.47366 7.45473C3.61956 7.45473 3.76459 7.39658 3.86969 7.29148L8.87287 2.29C8.97797 2.18581 9.03612 2.03991 9.03612 1.89488Z" fill="#59A56D"/>
					</svg>
					<?php echo esc_html( $feature ); ?>
				</span>
				<?php
			endforeach;
		}

		/**
		 * Render blog posts.
		 *
		 * @since 1.8.8
		 */
	private function render_blog_posts() {
		$posts = $this->get_blog_posts();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_simulating = isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) );
		$clear_cache = isset( $_GET['charitable_clear_cache'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_clear_cache'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended


			?>
			<div class="charitable-dashboard-v2-blog-posts" id="charitable-blog-posts-container">

				<?php if ( $is_simulating || $clear_cache || ! $posts ) : ?>
				<div class="charitable-dashboard-v2-blog-post-row error-display">
					<h5 class="charitable-dashboard-v2-blog-post-title">Unable to load blog posts. Please check back later.</h5>
				</div>
				<?php else : ?>
				<?php foreach ( $posts as $post ) : ?>
					<div class="charitable-dashboard-v2-blog-post-row<?php echo ! empty( $post['featured'] ) ? ' featured' : ''; ?><?php echo ! empty( $post['error'] ) ? ' error' : ''; ?>">
						<div class="charitable-dashboard-v2-blog-post-left">
							<?php if ( ! empty( $post['timestamp'] ) ) : ?>
								<div class="charitable-dashboard-v2-blog-post-timestamp"><?php echo esc_html( $post['timestamp'] ); ?></div>
							<?php endif; ?>
							<h5 class="charitable-dashboard-v2-blog-post-title">
								<?php if ( ! empty( $post['error'] ) ) : ?>
									<span class="charitable-dashboard-v2-blog-post-error"><?php echo esc_html( $post['title'] ); ?></span>
								<?php else : ?>
									<a href="<?php echo esc_url( $post['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $post['title'] ); ?></a>
								<?php endif; ?>
							</h5>
						</div>
						<div class="charitable-dashboard-v2-blog-post-right">
							<?php if ( ! empty( $post['error'] ) ) : ?>
								<div class="charitable-dashboard-v2-blog-post-error-icon">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#f56565"/>
									</svg>
								</div>
							<?php else : ?>
								<?php if ( ! empty( $post['image'] ) && is_array( $post['image'] ) ) : ?>
									<?php if ( ! empty( $post['image']['img'] ) ) : ?>
										<!-- Actual image -->
										<img src="<?php echo esc_url( $post['image']['img'] ); ?>" alt="<?php echo esc_attr( $post['title'] ); ?>" class="charitable-dashboard-v2-blog-post-image" width="106" height="56" style="object-fit: cover; border-radius: 4px;" />
									<?php elseif ( ! empty( $post['image']['svg'] ) ) : ?>
										<!-- Custom SVG placeholder -->
										<?php echo wp_kses_post( $post['image']['svg'] ); ?>
									<?php else : ?>
										<!-- Default SVG placeholder -->
										<svg class="charitable-dashboard-v2-blog-post-image-placeholder" width="106" height="56" viewBox="0 0 106 56" fill="none" xmlns="http://www.w3.org/2000/svg">
											<rect width="106" height="56" rx="4" fill="#E5E7EB"/>
											<path d="M20 40L35 25L50 35L65 20L80 30L95 15" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											<circle cx="35" cy="25" r="3" fill="#9CA3AF"/>
											<circle cx="50" cy="35" r="3" fill="#9CA3AF"/>
											<circle cx="65" cy="20" r="3" fill="#9CA3AF"/>
											<circle cx="80" cy="30" r="3" fill="#9CA3AF"/>
											<circle cx="95" cy="15" r="3" fill="#9CA3AF"/>
										</svg>
									<?php endif; ?>
								<?php else : ?>
									<!-- Fallback default SVG placeholder -->
									<svg class="charitable-dashboard-v2-blog-post-image-placeholder" width="106" height="56" viewBox="0 0 106 56" fill="none" xmlns="http://www.w3.org/2000/svg">
										<rect width="106" height="56" rx="4" fill="#E5E7EB"/>
										<path d="M20 40L35 25L50 35L65 20L80 30L95 15" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										<circle cx="35" cy="25" r="3" fill="#9CA3AF"/>
										<circle cx="50" cy="35" r="3" fill="#9CA3AF"/>
										<circle cx="65" cy="20" r="3" fill="#9CA3AF"/>
										<circle cx="80" cy="30" r="3" fill="#9CA3AF"/>
										<circle cx="95" cy="15" r="3" fill="#9CA3AF"/>
									</svg>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php


		}

		/**
		 * Fetch blog posts from feed.
		 *
		 * @since 1.8.8
		 *
		 * @return array|false Returns array on success, false on failure
		 */
	private function fetch_blog_posts_feed() {

		// Allow simulating API failure for testing
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) ) ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return false;
		}

			$args = [
				'timeout'    => 10,
				'sslverify'  => false,
				'user-agent' => charitable_get_default_user_agent(),
			];

			$response = wp_remote_get(
				self::BLOG_POSTS_SOURCE_URL,
				$args
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				return false;
			}

			$decoded = json_decode( $body, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}


			return $this->verify_blog_posts( $decoded );
		}

		/**
		 * Calculate relative time from a date string.
		 *
		 * @since 1.8.8.4
		 *
		 * @param string $date_string Date string in format 'Y-m-d H:i:s'.
		 *
		 * @return string Relative time string (e.g., '2 days ago', '1 week ago').
		 */
		private function calculate_relative_time( $date_string ) {
			if ( empty( $date_string ) ) {
				return '';
			}

			$post_date = strtotime( $date_string );
			$current_time = current_time( 'timestamp' );
			$time_diff = $current_time - $post_date;

			// If the date is in the future, return empty string
			if ( $time_diff < 0 ) {
				return '';
			}

			// Calculate relative time
			$minutes = floor( $time_diff / 60 );
			$hours = floor( $time_diff / 3600 );
			$days = floor( $time_diff / 86400 );
			$weeks = floor( $time_diff / 604800 );
			$months = floor( $time_diff / 2592000 );
			$years = floor( $time_diff / 31536000 );

			if ( $years > 0 ) {
				/* translators: %s: number of years */
				return $years === 1 ? _x( '1 year ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s year ago', '%s years ago', $years, 'relative time', 'charitable' ), number_format_i18n( $years ) );
			} elseif ( $months > 0 ) {
				/* translators: %s: number of months */
				return $months === 1 ? _x( '1 month ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s month ago', '%s months ago', $months, 'relative time', 'charitable' ), number_format_i18n( $months ) );
			} elseif ( $weeks > 0 ) {
				/* translators: %s: number of weeks */
				return $weeks === 1 ? _x( '1 week ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s week ago', '%s weeks ago', $weeks, 'relative time', 'charitable' ), number_format_i18n( $weeks ) );
			} elseif ( $days > 0 ) {
				/* translators: %s: number of days */
				return $days === 1 ? _x( '1 day ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s day ago', '%s days ago', $days, 'relative time', 'charitable' ), number_format_i18n( $days ) );
			} elseif ( $hours > 0 ) {
				/* translators: %s: number of hours */
				return $hours === 1 ? _x( '1 hour ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s hour ago', '%s hours ago', $hours, 'relative time', 'charitable' ), number_format_i18n( $hours ) );
			} elseif ( $minutes > 0 ) {
				/* translators: %s: number of minutes */
				return $minutes === 1 ? _x( '1 minute ago', 'relative time', 'charitable' ) : sprintf( _nx( '%s minute ago', '%s minutes ago', $minutes, 'relative time', 'charitable' ), number_format_i18n( $minutes ) );
			} else {
				return _x( 'Just now', 'relative time', 'charitable' );
			}
		}

		/**
		 * Verify blog posts data before it is saved.
		 *
		 * @since 1.8.8
		 *
		 * @param array $blog_posts Array of blog posts items to verify.
		 *
		 * @return array
		 */
		private function verify_blog_posts( $blog_posts ) {

			$data = [];

			if ( ! is_array( $blog_posts ) || empty( $blog_posts ) ) {
				return $data;
			}

			// Check if we have the expected structure
			if ( ! isset( $blog_posts['blog_posts'] ) || ! is_array( $blog_posts['blog_posts'] ) ) {
				return $data;
			}

			// Store version information if available
			if ( isset( $blog_posts['meta'] ) && is_array( $blog_posts['meta'] ) ) {
				$this->version_info = array(
					'current_charitable_version_lite' => ! empty( $blog_posts['meta']['current_charitable_version_lite'] ) ? sanitize_text_field( $blog_posts['meta']['current_charitable_version_lite'] ) : '',
					'current_charitable_version_pro' => ! empty( $blog_posts['meta']['current_charitable_version_pro'] ) ? sanitize_text_field( $blog_posts['meta']['current_charitable_version_pro'] ) : '',
				);
			}

			foreach ( $blog_posts['blog_posts'] as $post ) {

				// Verify required fields
				if ( empty( $post['title'] ) || empty( $post['url'] ) ) {
					continue;
				}

				// Handle image data
				$image_data = array(
					'placeholder' => true,
					'svg' => ''
				);

				if ( ! empty( $post['image'] ) && is_array( $post['image'] ) ) {
					// Handle placeholder as both boolean and string
					$placeholder_value = $post['image']['placeholder'] ?? true;
					if ( is_string( $placeholder_value ) ) {
						// Handle string values: 'false', '0', 'no' should be false
						$placeholder_value = in_array( strtolower( $placeholder_value ), ['false', '0', 'no', ''], true ) ? false : true;
					}
					$image_data['placeholder'] = (bool) $placeholder_value;

					if ( ! empty( $post['image']['img'] ) ) {
						$image_data['img'] = esc_url_raw( $post['image']['img'] );
					}

					if ( ! empty( $post['image']['svg'] ) ) {
						$image_data['svg'] = $post['image']['svg']; // SVG is already sanitized HTML
					}
				}

				// Calculate timestamp from date field if available, otherwise use provided timestamp
				$calculated_timestamp = '';
				if ( ! empty( $post['date'] ) ) {
					$calculated_timestamp = $this->calculate_relative_time( $post['date'] );
				} elseif ( ! empty( $post['timestamp'] ) ) {
					$calculated_timestamp = sanitize_text_field( $post['timestamp'] );
				}

				$data[] = array(
					'title'     => sanitize_text_field( $post['title'] ),
					'url'       => esc_url_raw( $post['url'] ),
					'timestamp' => $calculated_timestamp,
					'featured'  => ! empty( $post['featured'] ) ? (bool) $post['featured'] : false,
					'image'     => $image_data,
				);

			}

			return $data;
		}

		/**
		 * Get blog posts from transient cache.
		 *
		 * @since 1.8.8
		 *
		 * @return array
		 */
		private function get_blog_posts_from_cache() {

			$cached_data = get_transient( 'charitable_blog_posts_cache' );

			if ( false === $cached_data ) {
				return [];
			}

			return (array) $cached_data;
		}

		/**
		 * Cache blog posts data using transients.
		 *
		 * @since 1.8.8
		 *
		 * @param array $blog_posts Blog posts data to cache.
		 */
		private function cache_blog_posts( $blog_posts ) {

			$data = [
				'update'       => time(),
				'blog_posts'   => $blog_posts,
				'version_info' => $this->version_info,
			];

			// Cache for 24 hours
			set_transient( 'charitable_blog_posts_cache', $data, DAY_IN_SECONDS );
		}

		/**
		 * Get blog posts data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_blog_posts() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_simulating = isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) );
		$clear_cache = isset( $_GET['charitable_clear_cache'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_clear_cache'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

			// If simulating API failure, always return error
			if ( $is_simulating ) {
				return $this->get_blog_posts_error();
			}

			// Clear cache if requested
			if ( $clear_cache ) {
				$this->clear_blog_posts_cache();
			}

			// Try to get from cache first
			$cached_data = $this->get_blog_posts_from_cache();
			if ( ! empty( $cached_data ) && ! $clear_cache ) {
				// Restore version info from cache
				if ( isset( $cached_data['version_info'] ) ) {
					$this->version_info = $cached_data['version_info'];
				}
				return $cached_data['blog_posts'] ?? [];
			}

			// Fetch fresh data from API
			$blog_posts = $this->fetch_blog_posts_feed();

			// If API fetch was successful, cache and return the data
			if ( false !== $blog_posts && ! empty( $blog_posts ) ) {
				$this->cache_blog_posts( $blog_posts );
				return $blog_posts;
			} else {
				// API failed, show error
				return $this->get_blog_posts_error();
			}
		}

		/**
		 * Clear blog posts cache (for testing purposes).
		 *
		 * @since 1.8.8
		 */
		private function clear_blog_posts_cache() {
			delete_transient( 'charitable_blog_posts_cache' );
		}

		/**
		 * Get blog posts error data when API fails and no cache is available.
		 *
		 * @since 1.8.8
		 * @return array
		 */
	private function get_blog_posts_error() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_simulating = isset( $_GET['charitable_simulate_api_failure'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['charitable_simulate_api_failure'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$error_title = $is_simulating ? __( 'Unable to load blog posts (Simulated API Failure)', 'charitable' ) : __( 'Unable to load blog posts', 'charitable' );

			return array(
				array(
					'title' => $error_title,
					'url' => '#',
					'timestamp' => '',
					'featured' => false,
					'error' => true
				)
			);
		}

		/**
		 * Check if blog posts contain an error.
		 *
		 * @since 1.8.8
		 * @param array $posts Blog posts array.
		 * @return bool True if posts contain an error.
		 */
		private function has_blog_posts_error( $posts ) {
			if ( empty( $posts ) ) {
				return true;
			}

			foreach ( $posts as $post ) {
				if ( ! empty( $post['error'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Calculate how many updates behind the user is.
		 *
		 * @since 1.8.8
		 * @return int Number of updates behind, or 0 if up to date or unable to determine.
		 */
		private function get_updates_behind() {
			// Get current installed version
			$current_version = charitable()->get_version();

			// Get latest version from API
			$latest_version = $this->get_latest_version();

			if ( empty( $latest_version ) ) {
				return 0;
			}

			return $this->compare_versions( $current_version, $latest_version );
		}

		/**
		 * Get the latest version from the API data.
		 *
		 * @since 1.8.8
		 * @return string Latest version or empty string if not available.
		 */
		private function get_latest_version() {
			if ( null === $this->version_info ) {
				// Try to get version info from cached data
				$cached_data = get_transient( 'charitable_blog_posts_cache' );
				if ( false !== $cached_data && isset( $cached_data['version_info'] ) ) {
					$this->version_info = $cached_data['version_info'];
				}
			}

			if ( null === $this->version_info ) {
				return '';
			}

			// For now, we're working with Lite version
			return ! empty( $this->version_info['current_charitable_version_lite'] ) ? $this->version_info['current_charitable_version_lite'] : '';
		}

		/**
		 * Compare two version strings and return how many updates behind.
		 *
		 * @since 1.8.8
		 * @param string $current Current version (e.g., "1.8.4").
		 * @param string $latest Latest version (e.g., "1.8.8").
		 * @return int Number of updates behind.
		 */
		private function compare_versions( $current, $latest ) {
			// Normalize versions by removing any non-numeric characters except dots
			$current = preg_replace( '/[^0-9.]/', '', $current );
			$latest = preg_replace( '/[^0-9.]/', '', $latest );

			// Split into parts
			$current_parts = array_map( 'intval', explode( '.', $current ) );
			$latest_parts = array_map( 'intval', explode( '.', $latest ) );

			// Ensure both arrays have the same length by padding with zeros
			$max_length = max( count( $current_parts ), count( $latest_parts ) );
			$current_parts = array_pad( $current_parts, $max_length, 0 );
			$latest_parts = array_pad( $latest_parts, $max_length, 0 );

			// Compare each part
			for ( $i = 0; $i < $max_length; $i++ ) {
				if ( $latest_parts[ $i ] > $current_parts[ $i ] ) {
					// Calculate the difference
					$updates_behind = 0;
					for ( $j = $i; $j < $max_length; $j++ ) {
						$updates_behind += $latest_parts[ $j ] - $current_parts[ $j ];
					}
					return $updates_behind;
				} elseif ( $latest_parts[ $i ] < $current_parts[ $i ] ) {
					// Current version is newer than latest (shouldn't happen)
					return 0;
				}
			}

			// Versions are equal
			return 0;
		}

		/**
		 * Test version comparison logic (for debugging).
		 *
		 * @since 1.8.8
		 * @return void
		 */
		public function test_version_comparison() {
			// Test cases
			$test_cases = array(
				array( '1.8.4', '1.8.8', 4 ),
				array( '1.8.4.2', '1.8.8', 4 ),
				array( '1.8.7.5', '1.8.8', 1 ),
				array( '1.8.8', '1.8.8', 0 ),
				array( '1.8.9', '1.8.8', 0 ),
				array( '1.7.5', '1.8.2', 5 ),
			);

			foreach ( $test_cases as $test ) {
				$result = $this->compare_versions( $test[0], $test[1] );
				$status = ( $result === $test[2] ) ? 'PASS' : 'FAIL';
			}
		}

		/**
		 * Render the quick access section.
		 *
		 * @since 1.8.8
		 * @version 1.8.8.6
		 */
		public function render_quick_access_section() {
			?>
			<section id="charitable-dashboard-v2-quick-access" class="charitable-dashboard-v2-section charitable-dashboard-v2-collapsible">
				<header class="charitable-dashboard-v2-section-header">
					<h3>Quick Access</h3>
					<a href="#" class="charitable-dashboard-v2-toggle">
						<i class="fa fa-angle-down charitable-dashboard-v2-angle-down"></i>
					</a>
				</header>
				<div class="charitable-dashboard-v2-section-content">
					<div class="charitable-dashboard-v2-quick-access-content">
						<div class="charitable-dashboard-v2-quick-access-items">
							<?php $this->render_quick_access_items(); ?>
						</div>
					</div>
					<footer class="charitable-dashboard-v2-quick-access-footer">
						<div class="charitable-dashboard-v2-quick-access-footer-content">
							<div class="charitable-dashboard-v2-quick-access-footer-left">
								<a href="https://wordpress.org/support/plugin/charitable/reviews/#new-post" class="charitable-dashboard-v2-rate-link" target="_blank" rel="noopener noreferrer">
									<span class="charitable-dashboard-v2-rate-text">Rate us 5 stars</span>
									<svg class="charitable-dashboard-v2-arrow-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
								</a>
							</div>
							<div class="charitable-dashboard-v2-quick-access-footer-right">
								<div class="charitable-dashboard-v2-stars">
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
									<span class="charitable-dashboard-v2-star">★</span>
								</div>
							</div>
						</div>
					</footer>
				</div>
			</section>
			<?php
		}

		/**
		 * Render quick access items.
		 *
		 * @since 1.8.8
		 */
		private function render_quick_access_items() {
			$items = $this->get_quick_access_items();

			foreach ( $items as $item ) :
				?>
				<div class="charitable-dashboard-v2-quick-access-item">
					<div class="charitable-dashboard-v2-quick-access-icon">
						<?php
						// Only allow specific SVG-related tags and attributes for safety.
						echo wp_kses(
							$item['icon'],
							array(
								'svg'   => array(
									'xmlns'       => true,
									'width'       => true,
									'height'      => true,
									'viewbox'     => true,
									'fill'        => true,
									'class'       => true,
									'aria-hidden' => true,
									'role'        => true,
								),
								'path'  => array(
									'd'             => true,
									'fill'          => true,
									'stroke'        => true,
									'stroke-width'  => true,
									'stroke-linecap' => true,
									'stroke-linejoin'=> true,
								),
								'rect'  => array(
									'x'      => true,
									'y'      => true,
									'width'  => true,
									'height' => true,
									'fill'   => true,
								),
								'mask'  => array(
									'id'            => true,
									'style'         => true,
									'maskunits'     => true,
									'x'             => true,
									'y'             => true,
									'width'         => true,
									'height'        => true,
								),
								'g'     => array(
									'mask' => true,
								),
							)
						);
						?>
					</div>
					<div class="charitable-dashboard-v2-quick-access-title">
						<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank"><?php echo esc_html( $item['title'] ); ?></a>
					</div>
				</div>
				<?php
			endforeach;
		}

		/**
		 * Get quick access items data.
		 *
		 * @since 1.8.8
		 * @return array
		 */
		private function get_quick_access_items() {
			return array(
				array(
					'title' => __( 'Getting Started', 'charitable' ),
					'url' => 'https://wpcharitable.com/start-here/?referrer=chariable-dashboard',
					'icon' => '<svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M11 20C10.2 19.3667 9.33333 18.875 8.4 18.525C7.46667 18.175 6.5 18 5.5 18C4.8 18 4.11267 18.0917 3.438 18.275C2.76267 18.4583 2.11667 18.7167 1.5 19.05C1.15 19.2333 0.812667 19.225 0.488 19.025C0.162667 18.825 0 18.5333 0 18.15V6.1C0 5.91667 0.046 5.74167 0.138 5.575C0.229333 5.40833 0.366667 5.28333 0.55 5.2C1.31667 4.8 2.11667 4.5 2.95 4.3C3.78333 4.1 4.63333 4 5.5 4C6.46667 4 7.41267 4.125 8.338 4.375C9.26267 4.625 10.15 5 11 5.5V17.6C11.85 17.0667 12.7417 16.6667 13.675 16.4C14.6083 16.1333 15.55 16 16.5 16C17.1 16 17.6877 16.05 18.263 16.15C18.8377 16.25 19.4167 16.4 20 16.6V4.6C20.25 4.68333 20.496 4.77067 20.738 4.862C20.9793 4.954 21.2167 5.06667 21.45 5.2C21.6333 5.28333 21.771 5.40833 21.863 5.575C21.9543 5.74167 22 5.91667 22 6.1V18.15C22 18.5333 21.8377 18.825 21.513 19.025C21.1877 19.225 20.85 19.2333 20.5 19.05C19.8833 18.7167 19.2373 18.4583 18.562 18.275C17.8873 18.0917 17.2 18 16.5 18C15.5 18 14.5333 18.175 13.6 18.525C12.6667 18.875 11.8 19.3667 11 20ZM13 15V5.5L18 0.5V10.5L13 15ZM9 16.625V6.725C8.45 6.49167 7.87933 6.31267 7.288 6.188C6.696 6.06267 6.1 6 5.5 6C4.88333 6 4.28333 6.05833 3.7 6.175C3.11667 6.29167 2.55 6.46667 2 6.7V16.625C2.58333 16.4083 3.16267 16.25 3.738 16.15C4.31267 16.05 4.9 16 5.5 16C6.1 16 6.68733 16.05 7.262 16.15C7.83733 16.25 8.41667 16.4083 9 16.625Z" fill="#5AA152"/>
					</svg>'
				),
				array(
					'title' => __( 'Support Ticket', 'charitable' ),
					'url' => 'https://wpcharitable.com/support?referrer=chariable-dashboard',
					'icon' => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M6 9C6.28333 9 6.521 8.904 6.713 8.712C6.90433 8.52067 7 8.28333 7 8C7 7.71667 6.90433 7.479 6.713 7.287C6.521 7.09567 6.28333 7 6 7C5.71667 7 5.479 7.09567 5.287 7.287C5.09567 7.479 5 7.71667 5 8C5 8.28333 5.09567 8.52067 5.287 8.712C5.479 8.904 5.71667 9 6 9ZM10 9C10.2833 9 10.521 8.904 10.713 8.712C10.9043 8.52067 11 8.28333 11 8C11 7.71667 10.9043 7.479 10.713 7.287C10.521 7.09567 10.2833 7 10 7C9.71667 7 9.47933 7.09567 9.288 7.287C9.096 7.479 9 7.71667 9 8C9 8.28333 9.096 8.52067 9.288 8.712C9.47933 8.904 9.71667 9 10 9ZM14 9C14.2833 9 14.5207 8.904 14.712 8.712C14.904 8.52067 15 8.28333 15 8C15 7.71667 14.904 7.479 14.712 7.287C14.5207 7.09567 14.2833 7 14 7C13.7167 7 13.4793 7.09567 13.288 7.287C13.096 7.479 13 7.71667 13 8C13 8.28333 13.096 8.52067 13.288 8.712C13.4793 8.904 13.7167 9 14 9ZM0 20V2C0 1.45 0.196 0.979 0.588 0.587C0.979333 0.195667 1.45 0 2 0H18C18.55 0 19.021 0.195667 19.413 0.587C19.8043 0.979 20 1.45 20 2V14C20 14.55 19.8043 15.021 19.413 15.413C19.021 15.8043 18.55 16 18 16H4L0 20ZM2 15.175L3.175 14H18V2H2V15.175Z" fill="#5AA152"/>
					</svg>'
				),
				array(
					'title' => __( 'Documentation', 'charitable' ),
					'url' => 'https://wpcharitable.com/documentation?referrer=chariable-dashboard',
					'icon' => '<svg width="22" height="16" viewBox="0 0 22 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M13 5.9V4.2C13.55 3.96667 14.1127 3.79167 14.688 3.675C15.2627 3.55833 15.8667 3.5 16.5 3.5C16.9333 3.5 17.3583 3.53333 17.775 3.6C18.1917 3.66667 18.6 3.75 19 3.85V5.45C18.6 5.3 18.196 5.18767 17.788 5.113C17.3793 5.03767 16.95 5 16.5 5C15.8667 5 15.2583 5.07933 14.675 5.238C14.0917 5.396 13.5333 5.61667 13 5.9ZM13 11.4V9.7C13.55 9.46667 14.1127 9.29167 14.688 9.175C15.2627 9.05833 15.8667 9 16.5 9C16.9333 9 17.3583 9.03333 17.775 9.1C18.1917 9.16667 18.6 9.25 19 9.35V10.95C18.6 10.8 18.196 10.6877 17.788 10.613C17.3793 10.5377 16.95 10.5 16.5 10.5C15.8667 10.5 15.2583 10.575 14.675 10.725C14.0917 10.875 13.5333 11.1 13 11.4ZM13 8.65V6.95C13.55 6.71667 14.1127 6.54167 14.688 6.425C15.2627 6.30833 15.8667 6.25 16.5 6.25C16.9333 6.25 17.3583 6.28333 17.775 6.35C18.1917 6.41667 18.6 6.5 19 6.6V8.2C18.6 8.05 18.196 7.93767 17.788 7.863C17.3793 7.78767 16.95 7.75 16.5 7.75C15.8667 7.75 15.2583 7.82933 14.675 7.988C14.0917 8.146 13.5333 8.36667 13 8.65ZM5.5 12C6.28333 12 7.046 12.0873 7.788 12.262C8.52933 12.4373 9.26667 12.7 10 13.05V3.2C9.31667 2.8 8.59167 2.5 7.825 2.3C7.05833 2.1 6.28333 2 5.5 2C4.9 2 4.30433 2.05833 3.713 2.175C3.121 2.29167 2.55 2.46667 2 2.7V12.6C2.58333 12.4 3.16267 12.25 3.738 12.15C4.31267 12.05 4.9 12 5.5 12ZM12 13.05C12.7333 12.7 13.471 12.4373 14.213 12.262C14.9543 12.0873 15.7167 12 16.5 12C17.1 12 17.6877 12.05 18.263 12.15C18.8377 12.25 19.4167 12.4 20 12.6V2.7C19.45 2.46667 18.8793 2.29167 18.288 2.175C17.696 2.05833 17.1 2 16.5 2C15.7167 2 14.9417 2.1 14.175 2.3C13.4083 2.5 12.6833 2.8 12 3.2V13.05ZM11 16C10.2 15.3667 9.33333 14.875 8.4 14.525C7.46667 14.175 6.5 14 5.5 14C4.8 14 4.11267 14.0917 3.438 14.275C2.76267 14.4583 2.11667 14.7167 1.5 15.05C1.15 15.2333 0.812667 15.225 0.488 15.025C0.162667 14.825 0 14.5333 0 14.15V2.1C0 1.91667 0.046 1.74167 0.138 1.575C0.229333 1.40833 0.366667 1.28333 0.55 1.2C1.31667 0.8 2.11667 0.5 2.95 0.3C3.78333 0.1 4.63333 0 5.5 0C6.46667 0 7.41267 0.125 8.338 0.375C9.26267 0.625 10.15 1 11 1.5C11.85 1 12.7377 0.625 13.663 0.375C14.5877 0.125 15.5333 0 16.5 0C17.3667 0 18.2167 0.1 19.05 0.3C19.8833 0.5 20.6833 0.8 21.45 1.2C21.6333 1.28333 21.771 1.40833 21.863 1.575C21.9543 1.74167 22 1.91667 22 2.1V14.15C22 14.5333 21.8377 14.825 21.513 15.025C21.1877 15.225 20.85 15.2333 20.5 15.05C19.8833 14.7167 19.2373 14.4583 18.562 14.275C17.8873 14.0917 17.2 14 16.5 14C15.5 14 14.5333 14.175 13.6 14.525C12.6667 14.875 11.8 15.3667 11 16Z" fill="#5AA152"/>
					</svg>'
				),
				array(
					'title' => __( 'Upgrade To Pro', 'charitable' ),
					'url' => 'https://wpcharitable.com/lite-vs-pro?referrer=chariable-dashboard',
					'icon' => '<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M5.675 11.7L6.55 8.85L4.25 7H7.1L8 4.2L8.9 7H11.75L9.425 8.85L10.3 11.7L8 9.925L5.675 11.7ZM2 21V13.275C1.36667 12.575 0.875 11.775 0.525 10.875C0.175 9.975 0 9.01667 0 8C0 5.76667 0.775 3.875 2.325 2.325C3.875 0.775 5.76667 0 8 0C10.2333 0 12.125 0.775 13.675 2.325C15.225 3.875 16 5.76667 16 8C16 9.01667 15.825 9.975 15.475 10.875C15.125 11.775 14.6333 12.575 14 13.275V21L8 19L2 21ZM8 14C9.66667 14 11.0833 13.4167 12.25 12.25C13.4167 11.0833 14 9.66667 14 8C14 6.33333 13.4167 4.91667 12.25 3.75C11.0833 2.58333 9.66667 2 8 2C6.33333 2 4.91667 2.58333 3.75 2.325C2.58333 3.875 2 5.76667 2 8C2 9.66667 2.58333 11.0833 3.75 12.25C4.91667 13.4167 6.33333 14 8 14ZM4 18.025L8 17L12 18.025V14.925C11.4167 15.2583 10.7875 15.5208 10.1125 15.7125C9.4375 15.9042 8.73333 16 8 16C7.26667 16 6.5625 15.9042 5.8875 15.7125C5.2125 15.5208 4.58333 15.2583 4 14.925V18.025Z" fill="#5AA152"/>
					</svg>'
				)
			);
		}

		/**
		 * Render dashboard scripts.
		 *
		 * @since 1.8.8
		 */
		public function render_dashboard_scripts() {
			$charitable_dashboard_legacy = Charitable_Dashboard_Legacy::get_instance();
			$charitable_dashboard_legacy->generate_dashboard_report_html();
			$donation_axis = $charitable_dashboard_legacy->get_donation_axis();
			$date_axis = $charitable_dashboard_legacy->get_date_axis();

			// Decode HTML entities in the donation axis data and extract numeric values
			if ( is_array( $donation_axis ) ) {
				$donation_axis = array_map( function( $value ) {
					$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					if ( preg_match( '/(\d+\.?\d*)/', $decoded, $matches ) ) {
						return floatval( $matches[1] );
					}
					return 0;
				}, $donation_axis );
			}
			?>
			<?php
			// Decode HTML entities in currency symbol before passing to JavaScript.
			// The currency symbol comes as HTML entities (e.g., &#36; for $), so we decode it
			// before using esc_js() to avoid double-encoding issues.
			$currency_helper = charitable_get_currency_helper();
			$currency_symbol = html_entity_decode( $currency_helper->get_currency_symbol(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$decimal_count = $currency_helper->get_decimals();
			?>
			<script id="charitable-dashboard-report-data-js">
				var charitable_dashboard_reporting = {
					version: '<?php echo esc_js( Charitable::VERSION ); ?>',
					user_id: '<?php echo esc_js( get_current_user_id() ); ?>',
					nonce: '<?php echo esc_js( wp_create_nonce( 'charitable-reporting' ) ); ?>',
					dashboard_nonce: '<?php echo esc_js( wp_create_nonce( 'charitable-dashboard' ) ); ?>',
					admin_nonce: '<?php echo esc_js( wp_create_nonce( 'charitable-admin' ) ); ?>',
					currency_symbol: '<?php echo esc_js( $currency_symbol ); ?>',
					decimal_count: <?php echo (int) $decimal_count; ?>,
					ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
					date_select_day: 'DD',
					date_select_month: 'MM',
					strings: {
						activation_failed: '<?php echo esc_js( __( 'Activation failed. Please try again.', 'charitable' ) ); ?>',
						error_occurred: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'charitable' ) ); ?>',
						approved: '<?php echo esc_js( __( 'Approved', 'charitable' ) ); ?>',
						no_comments_title: '<?php echo esc_js( __( 'No donor comments yet.', 'charitable' ) ); ?>',
						no_comments_message: '<?php echo esc_js( __( 'Donor comments will appear here once donors start leaving messages with their donations.', 'charitable' ) ); ?>'
					},
					headline_chart_options: {
						donation_axis: <?php echo wp_json_encode( (array) $donation_axis ); ?>,
						date_axis: <?php echo wp_json_encode( (array) $date_axis ); ?>,
						enable_tooltips: <?php echo $charitable_dashboard_legacy->enable_tooltips() ? 'true' : 'false'; ?>
					},
					payment_methods_chart_options: {
						payment_percentages: [],
						payment_labels: []
					}
				};
			</script>

			<script id="charitable-dashboard-tabs-js">
				document.addEventListener('DOMContentLoaded', function() {
					// Tab switching functionality
					const tabNavItems = document.querySelectorAll('.charitable-dashboard-v2-tab-nav-item');
					const tabContents = document.querySelectorAll('.charitable-dashboard-v2-tab-content');

					// Function to switch tabs
					function switchTab(targetTab) {
						// Remove active classes from all tabs and contents
						tabNavItems.forEach(item => item.classList.remove('charitable-dashboard-v2-tab-nav-active'));
						tabContents.forEach(content => content.classList.remove('charitable-dashboard-v2-tab-content-active'));

						// Add active class to selected tab and content
						const activeTab = document.querySelector(`[data-tab="${targetTab}"]`);
						const activeContent = document.querySelector(`.charitable-dashboard-v2-tab-content[data-tab="${targetTab}"]`);

						if (activeTab) {
							activeTab.classList.add('charitable-dashboard-v2-tab-nav-active');
						}
						if (activeContent) {
							activeContent.classList.add('charitable-dashboard-v2-tab-content-active');
						}

						// Update URL without page reload
						const url = new URL(window.location);
						url.searchParams.set('tab', targetTab);
						window.history.pushState({}, '', url);
					}

					// Add click event listeners to tab navigation
					tabNavItems.forEach(item => {
						item.addEventListener('click', function(e) {
							e.preventDefault();
							const targetTab = this.getAttribute('data-tab');
							switchTab(targetTab);
						});
					});

					// Handle browser back/forward buttons
					window.addEventListener('popstate', function() {
						const urlParams = new URLSearchParams(window.location.search);
						const tab = urlParams.get('tab') || 'top-campaigns';
						switchTab(tab);
					});

					// Addon activation functionality
					const activateButton = document.getElementById('charitable-activate-donor-comments');
					if (activateButton) {
						activateButton.addEventListener('click', function(e) {
							e.preventDefault();

							const plugin = this.getAttribute('data-plugin');
							const buttonText = this.querySelector('.button-text');
							const buttonLoading = this.querySelector('.button-loading');

							// Show loading state
							buttonText.style.display = 'none';
							buttonLoading.style.display = 'inline-flex';
							this.disabled = true;

							// Make AJAX request
							const formData = new FormData();
							formData.append('action', 'charitable_activate_addon');
							formData.append('plugin', plugin);
							formData.append('nonce', charitable_dashboard_v2_reporting.admin_nonce);

							fetch(charitable_dashboard_v2_reporting.ajax_url, {
								method: 'POST',
								body: formData
							})
							.then(response => response.json())
							.then(data => {
								if (data.success) {
									// Show success message and reload page
									alert(data.data.message);
									window.location.reload();
								} else {
									// Show error message
									alert(data.data.message || charitable_dashboard_v2_reporting.strings.activation_failed);

									// Reset button state
									buttonText.style.display = 'inline';
									buttonLoading.style.display = 'none';
									this.disabled = false;
								}
							})
							.catch(error => {
								console.error('Error:', error);
								alert(charitable_dashboard_v2_reporting.strings.error_occurred);

								// Reset button state
								buttonText.style.display = 'inline';
								buttonLoading.style.display = 'none';
								this.disabled = false;
							});
						});
					}

					// Comment actions functionality
					let isProcessing = false;

					// Rollback function - defined outside to be accessible
					function rollbackUI(originalState, actionElement) {
						// Restore original state for the specific action
						const actionText = actionElement.querySelector('.action-text');
						const actionLoading = actionElement.querySelector('.action-loading');

						actionText.textContent = originalState.actionText;
						actionText.style.display = 'inline';
						actionLoading.style.display = originalState.actionLoading;

						// Restore the icon
						const icon = actionElement.querySelector('svg, img');
						if (icon) {
							icon.style.display = originalState.icon;
						}

						// Re-enable all comment actions
						const allCommentActions = document.querySelectorAll('.comment-action');
						allCommentActions.forEach(btn => {
							btn.style.pointerEvents = 'auto';
							btn.style.opacity = '1';
						});

						isProcessing = false;
					}

					// Re-enable all comment actions function
					function reEnableAllActions() {
						const allCommentActions = document.querySelectorAll('.comment-action');
						allCommentActions.forEach(btn => {
							btn.style.pointerEvents = 'auto';
							btn.style.opacity = '1';
						});
						isProcessing = false;
					}

					const commentActions = document.querySelectorAll('.comment-action');
					commentActions.forEach(action => {
						action.addEventListener('click', function(e) {
							e.preventDefault();

							// Prevent multiple simultaneous requests
							if (isProcessing) {
								return;
							}

							const commentId = this.getAttribute('data-comment-id');
							const actionType = this.classList.contains('approve') ? 'approve' : 'delete';
							const actionText = this.querySelector('.action-text');
							const actionLoading = this.querySelector('.action-loading');
							const row = this.closest('tr');

							// Store original state for rollback
							const originalState = {
								actionText: actionText.textContent,
								actionLoading: actionLoading.style.display,
								icon: this.querySelector('svg, img').style.display,
								pointerEvents: this.style.pointerEvents
							};

							// Disable ALL comment actions in the entire comments area
							isProcessing = true;
							const allCommentActions = document.querySelectorAll('.comment-action');
							allCommentActions.forEach(btn => {
								btn.style.pointerEvents = 'none';
								btn.style.opacity = '0.5';
							});

							// Show loading state for this specific action
							actionText.style.display = 'none';
							actionLoading.style.display = 'inline-flex';

							// Hide the icon during processing
							const icon = this.querySelector('svg, img');
							if (icon) {
								icon.style.display = 'none';
							}

							// Make AJAX request
							const formData = new FormData();
							formData.append('action', 'charitable_' + actionType + '_comment');
							formData.append('comment_id', commentId);
							formData.append('nonce', charitable_dashboard_v2_reporting.admin_nonce);

							fetch(charitable_dashboard_v2_reporting.ajax_url, {
								method: 'POST',
								body: formData
							})
							.then(response => {
								if (!response.ok) {
									throw new Error('HTTP ' + response.status + ': ' + response.statusText);
								}
								return response.json();
							})
							.then(data => {
								if (data.success) {
									// Success - update UI with slight delay
									setTimeout(() => {
										if (actionType === 'approve') {
											// Replace with approved status
											const statusCell = row.querySelector('td:last-child');
											statusCell.innerHTML = '<span class="comment-status-approved">' + charitable_dashboard_v2_reporting.strings.approved + '</span>';
											// Re-enable all other actions after successful approve
											reEnableAllActions();
										} else if (actionType === 'delete') {
											// Fade out and remove row
											row.style.transition = 'opacity 0.3s ease';
											row.style.opacity = '0';
											setTimeout(() => {
												row.remove();
												// Check if table is now empty
												checkEmptyState();
												// Re-enable all other actions after successful delete
												reEnableAllActions();
											}, 300);
										}
									}, 200); // Slight delay as requested
								} else {
									// Error - rollback UI
									console.error('Charitable: AJAX error:', data.data.message);
									rollbackUI(originalState, this);
								}
							})
							.catch(error => {
								console.error('Charitable: Network error:', error);
								rollbackUI(originalState, this);
							});
						});
					});

					// Check if comments table is empty and show empty state
					function checkEmptyState() {
						const tbody = document.querySelector('.charitable-dashboard-v2-table-comments tbody');
						const visibleRows = tbody.querySelectorAll('tr[data-comment-id]');

						if (visibleRows.length === 0) {
							// Show empty state
							tbody.innerHTML = `
								<tr>
									<td colspan="2" class="charitable-dashboard-v2-no-comments">
										<p><strong>` + charitable_dashboard_v2_reporting.strings.no_comments_title + `</strong></p>
										<p>` + charitable_dashboard_v2_reporting.strings.no_comments_message + `</p>
									</td>
								</tr>
							`;
						}
					}
				});
			</script>
			<?php
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Dashboard
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
