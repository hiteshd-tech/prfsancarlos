<?php
/**
 * Charitable Dashboard UI.
 *
 * @package   Charitable/Classes/Charitable_Dashboard
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Dashboard_Legacy' ) ) :

	/**
	 * Charitable_Dashboard_Legacy
	 *
	 * @final
	 * @since 1.8.1
	 */
	final class Charitable_Dashboard_Legacy {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Dashboard_Legacy|null
		 */
		private static $instance = null;

		/**
		 * The dashboard data that holds start date, end date, and filter.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $dashboard_data = array();

		/**
		 * The date filter values.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $date_range_filter_values = array();

		/**
		 * The start date.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $start_date = false;

		/**
		 * The end date.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $end_date = false;

		/**
		 * The number of days.
		 *
		 * @since 1.8.1
		 *
		 * @var   int
		 */
		public $days = false;

		/**
		 * The donation axis of the highlight chart.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donation_axis = array();

		/**
		 * The date axis of the highlight chart.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $date_axis = array();

		/**
		 * The default number of days to show in the filter.
		 *
		 * @since 1.8.1
		 *
		 * @var   int
		 */
		private $filter_days_default = 7;

		/**
		 * Stores the last date/time of cache, false if there is no active or valid cache.
		 *
		 * @since 1.8.1
		 *
		 * @var   int
		 */
		public $is_data_cached = false;

		/**
		 * Create object instance.
		 *
		 * @param  array $args The arguments.
		 *
		 * @since 1.8.1
		 */
		public function __construct( $args = array() ) {

			do_action( 'charitable_admin_reports_start', $this );

			add_action( 'charitable_after_admin_dashboard', array( $this, 'dashboard_cta' ) );

			add_action( 'charitable_admin_dashboard_notifications', array( $this, 'display_notifications' ) );

			$this->init( $args );
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1
		 *
		 * @param  array $args The arguments.
		 *
		 * @return void
		 */
		private function init( $args = array() ) {

			$defaults = apply_filters(
				'charitable_dashboard_data_arg_defaults',
				array(
					'start_date' => gmdate( 'Y/m/d', strtotime( '-7 days' ) ),
					'end_date'   => gmdate( 'Y/m/d' ),
					'days'       => 7,
				)
			);

			$cached_dashboard_data_args = $this->get_cached_dashboard_data_args();

			if ( ! empty( $cached_dashboard_data_args ) && ! empty( $cached_dashboard_data_args['start_date'] ) && ! empty( $cached_dashboard_data_args['end_date'] ) && ! empty( $cached_dashboard_data_args['days'] ) ) {

				$dashboard_data_args['start_date'] = esc_html( $cached_dashboard_data_args['start_date'] );
				$dashboard_data_args['end_date']   = esc_html( $cached_dashboard_data_args['end_date'] );
				$dashboard_data_args['days']       = intval( $cached_dashboard_data_args['days'] );

				$this->is_data_cached = $cached_dashboard_data_args['timestamp'];

			} else {

				$this->is_data_cached = false;
				$dashboard_data_args  = wp_parse_args( $args, $defaults );
			}

			do_action( 'charitable_admin_dashboard_init_start', $this );

			// Get the date filter values.
			$this->date_range_filter_values = apply_filters(
				'charitable_dashboard_date_range_filter_values',
				array(
					'7'  => __( 'Last 7 Days', 'charitable' ),
					'14' => __( 'Last 14 Days', 'charitable' ),
					'30' => __( 'Last 30 Days', 'charitable' ),
				)
			);

			$this->start_date = $dashboard_data_args['start_date'];
			$this->end_date   = $dashboard_data_args['end_date'];
			$this->days       = $dashboard_data_args['days'];

			$this->dashboard_data = (array) $dashboard_data_args;

			$this->maybe_load_scripts();

			// Allow Charitable and third-party plugins to hook into the dashboard.
			do_action( 'charitable_admin_dashboard_init_end', $this );
		}

		/**
		 * Get the cached dashboard notifications.
		 *
		 * @since  1.8.2
		 *
		 * @return array
		 */
		public function display_notifications() {

			// Grab the array of notifications, which includes the title, some meta, and the message.
			$notifications = get_option( 'charitable_dashboard_notifications', array() );

			if ( empty( $notifications ) ) {
				return false;
			}

			// remove any notifications that don't have a 'type' key.
			$notifications = array_filter(
				$notifications,
				function ( $notification ) {
					return isset( $notification['type'] );
				}
			);

			// remove any notifications that have a 'dismissed' key.
			$notifications = array_filter(
				$notifications,
				function ( $notification ) {
					return ! isset( $notification['dismissed'] );
				}
			);

			if ( empty( $notifications ) ) {
				return false;
			}

			// sort notifications by type = 'error' first, then 'warning', followed by 'notice'.
			uasort(
				$notifications,
				function ( $a, $b ) {
					$types = array( 'error', 'warning', 'notice' );
					$pos_a = array_search( $a['type'], $types ); // phpcs:ignore
					$pos_b = array_search( $b['type'], $types ); // phpcs:ignore

					return $pos_a - $pos_b;
				}
			);

			include charitable()->get_path( 'includes' ) . 'admin/templates/dashboard-notifications.php';
		}


		/**
		 * Remove a dashboard notification.
		 *
		 * @since 1.8.2
		 *
		 * @param  string $notification_id The notification ID.
		 *
		 * @return boolean
		 */
		public function charitable_remove_dashboard_notification( $notification_id = false ) {

			// check nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'charitable_dashboard_notification_nonce' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce.', 'charitable' ) ) );
			}

			if ( false === $notification_id ) {
				return false;
			}

			$notification_id = sanitize_text_field( wp_unslash( $notification_id ) );

			$notifications = (array) get_option( 'charitable_dashboard_notifications', array() );

			if ( empty( $notifications ) ) {
				return false;
			}

			if ( ! empty( $notifications[ $notification_id ] ) ) {
				unset( $notifications[ $notification_id ] );
				update_option( 'charitable_dashboard_notifications', $notifications );
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get the donation axis information for the highlight chart.
		 *
		 * @since  1.8.1.6
		 *
		 * @return array
		 */
		public function enable_tooltips() {

			$donation_axis = $this->get_donation_axis();

			if ( empty( $donation_axis ) ) {
				return false;
			}

			// remove any '0' and ',' from the string.
			$donation_axis = array_map(
				function ( $value ) {
					return str_replace( array( '0', ',' ), '', $value );
				},
				$donation_axis
			);

			// if the trimmed length is 0, return false.
			$enable_tooltips = array_filter(
				$donation_axis,
				function ( $value ) {
					return strlen( trim( $value ) ) > 0;
				}
			);

			return $enable_tooltips;
		}

		/**
		 * Get the donation axis information for the highlight chart.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_donation_axis() {

			$donation_axis = $this->maybe_cache_dashboard() ? get_transient( 'wpch_dashboard_report_html_donation_axis' ) : false;
			$date_axis     = $this->maybe_cache_dashboard() ? get_transient( 'wpch_dashboard_report_html_date_axis' ) : false;

			if ( $donation_axis && $date_axis ) {
				return $donation_axis;
			}

			if ( empty( $this->donation_axis ) ) {
				return array( 0, 0, 0, 0, 0, 0 );
			}

			return (array) $this->donation_axis;
		}

		/**
		 * Get the date axis information for the highlight chart.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_date_axis() {

			$donation_axis = $this->maybe_cache_dashboard() ? get_transient( 'wpch_dashboard_report_html_donation_axis' ) : false;
			$date_axis     = $this->maybe_cache_dashboard() ? get_transient( 'wpch_dashboard_report_html_date_axis' ) : false;

			if ( $donation_axis && $date_axis ) {
				return $date_axis;
			}

			if ( empty( $this->date_axis ) ) {
				return $this->get_days_between_dates( gmdate( 'Y/m/d', strtotime( '-7 days' ) ), gmdate( 'Y/m/d' ) );
			}

			return (array) $this->date_axis;
		}

		/**
		 * Generate the dashboard HTML.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $args The arguments.
		 *
		 * @return array
		 */
		public function generate_dashboard_report_html( $args = array() ) {

			if ( ! is_array( $args ) ) {
				$args = array();
			}

			$defaults = array(
				'start_date'                => false,
				'end_date'                  => false,
				'days'                      => false,
				'show_support_box'          => true,
				'include_icons'             => true,
				'action'                    => false,
				'show_recommended_addons'   => true,
				'show_recommended_snippets' => true,
				'show_notifications'        => true,
				'use_cache'                 => 'no', // 'yes' or 'no' or 'maybe'.
			);

			$args = apply_filters( 'charitable_dashboard_report_html_args', wp_parse_args( $args, $defaults ) );

			// Extract individual variables from args array.
			$start_date                = $args['start_date'] ?? false;
			$end_date                  = $args['end_date'] ?? false;
			$days                      = $args['days'] ?? false;
			$show_support_box          = $args['show_support_box'] ?? true;
			$include_icons             = $args['include_icons'] ?? true;
			$action                    = $args['action'] ?? false;
			$show_recommended_addons   = $args['show_recommended_addons'] ?? true;
			$show_recommended_snippets = $args['show_recommended_snippets'] ?? true;
			$show_notifications        = $args['show_notifications'] ?? true;
			$use_cache                 = $args['use_cache'] ?? 'no';

			if ( false === $days ) {
				$args = $this->get_cached_dashboard_data_args();
				if ( empty( $args ) || false === $args ) {
					$start_date = gmdate( 'Y/m/d', strtotime( '-' . $days . ' days' ) );
					$end_date   = gmdate( 'Y/m/d' );
					$days       = 7;
				} else {
					$start_date = $args['start_date'];
					$end_date   = $args['end_date'];
					$days       = $args['days'];
				}
			}

			$html                = $this->maybe_cache_dashboard( $use_cache ) ? get_transient( 'wpch_dashboard_report_html_' . intval( $days ) ) : false;
			$this->donation_axis = $this->maybe_cache_dashboard( $use_cache ) ? get_transient( 'wpch_dashboard_report_html_donation_axis_' . intval( $days ) ) : false;
			$this->date_axis     = $this->maybe_cache_dashboard( $use_cache ) ? get_transient( 'wpch_dashboard_report_html_date_axis_' . intval( $days ) ) : false;

			$show_snippets_on_dashboard = apply_filters( 'charitable_show_snippets_on_dashboard', true );

			// if WPCode isn't activated, then don't show the WPCode snippets on the dashboard.
			if ( $show_snippets_on_dashboard ) {
				// is WPCode WordPress plugin activated?
				$is_activated = Charitable_Admin_Plugins_Third_Party::get_instance()->is_plugin_activated( 'wpcode' );

				// if WPCode is not activated, then don't show the WPCode snippets on the dashboard.
				if ( ! $is_activated ) {
					$show_recommended_snippets = false;
				}
			} else {
				$show_recommended_snippets = false;
			}

			if ( false === $html || false === $this->donation_axis || false === $this->date_axis || ! $this->maybe_cache_dashboard( $use_cache ) ) {

				if ( $days ) {
					$start_date = gmdate( 'Y/m/d', strtotime( '-' . $days . ' days' ) );
					$end_date   = gmdate( 'Y/m/d' );
				} else {
					$start_date = ( false === $start_date ) ? $this->start_date : $start_date;
					$end_date   = ( false === $end_date ) ? $this->end_date : $end_date;
				}

				$charitable_reports = Charitable_Reports::get_instance();

				$args = array(
					'start_date' => $start_date,
					'end_date'   => $end_date,
					'days'       => $days,
				);

				$charitable_reports->init_with_array( 'dashboard', $args );

				// donations.
				$donations              = $charitable_reports->get_donations();
				$total_count_donations  = count( $donations );
				$total_amount_donations = $charitable_reports->get_donations_total();
				$donation_average       = ( $total_amount_donations > 0 && $total_amount_donations > 0 ) ? ( charitable_format_money( $total_amount_donations / $total_count_donations, 2, true ) ) : charitable_format_money( 0 );
				$donation_total         = charitable_format_money( $total_amount_donations );

				// donors.
				$total_count_donors = $charitable_reports->get_donors_count();

				// refunds.
				$refunds_data = $charitable_reports->get_refunds();

				$total_amount_refunds = $refunds_data['total_amount'];
				$total_count_refunds  = $refunds_data['total_count'];

				// campaigns.
				$args          = array( 'posts_per_page' => apply_filters( 'charitable_dashboard_top_campaigns_amount', 5 ) );
				$top_campaigns = Charitable_Campaigns::ordered_by_amount( $args );

				$this->donation_axis = ! empty( $charitable_reports->donation_axis ) ? (array) $charitable_reports->donation_axis : false;
				$this->date_axis     = ! empty( $charitable_reports->date_axis ) ? (array) $charitable_reports->date_axis : false;

				$html = array();

				ob_start();

				?>

				<div class="charitable-cards">
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-donation-total-amount"><?php echo esc_html( $donation_total ); ?></span></strong>
						<p><span id="charitable-top-donation-total-count"><?php echo count( $donations ); ?></span> <?php echo esc_html__( 'Total Donations', 'charitable' ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-donation-average"><?php echo esc_html( $donation_average ); ?></span></strong>
						<p><?php echo esc_html__( 'Average Donation', 'charitable' ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-donor-count"><?php echo intval( $total_count_donors ); ?></span></strong>
						<p><?php echo esc_html__( 'Donors', 'charitable' ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-refund-total-amount"><?php echo charitable_format_money( $total_amount_refunds ); // phpcs:ignore ?></span></strong>
						<p><span id="charitable-top-refund-count"><?php echo intval( $total_count_refunds ); ?></span> <?php echo esc_html__( 'Refunds', 'charitable' ); ?></p>
					</div>
				</div>
				<?php

					$html['charitable_cards'] = ob_get_clean();

					$no_items_text = ( $days > 0 ) ? esc_html__( 'There are no donations in the last ', 'charitable' ) . $days . ' ' . esc_html__( 'days.', 'charitable' ) : esc_html__( 'There are no donations for the selected date range.', 'charitable' );

					ob_start();
				?>
					<div class="charitable-container charitable-report-card charitable-recent-donations-report">
						<div class="header">
							<h4><?php echo esc_html__( 'Recent Donations', 'charitable' ); ?></h4>
							<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
						</div>
						<div class="charitable-toggle-container charitable-report-ui">
							<?php

							$list = $charitable_reports->generate_recent_donations_list( $donations, 10, $include_icons );

							if ( ! empty( $list ) ) :
								?>
								<div class="the-list">
									<ul id="charitable-recent-donations-list">
										<?php echo $list; // phpcs:ignore ?>
									</ul>
								</div>
							<?php else : ?>
								<div class="no-items">
									<p><strong><?php echo esc_html( $no_items_text ); ?></strong></p>
									<p class="link"><a href="<?php echo admin_url( 'post-new.php?post_type=donation' ); // phpcs:ignore ?>"><?php echo esc_html__( 'Add Donation', 'charitable' ); ?>
																		<?php
																		if ( $include_icons ) :
																			?>
										<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /><?php endif; ?></a></p>
								</div>
							<?php endif; ?>
						</div>
						<div class="more">
							<?php if ( false === $action ) : ?>
							<a href="<?php echo admin_url( 'edit.php?post_type=donation' ); // phpcs:ignore ?>"><?php echo esc_html__( 'View All Donations', 'charitable' ); ?>
												<?php
												if ( $include_icons ) :
													?>
								<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /><?php endif; ?></a>
							<?php endif; ?>
						</div>
					</div>

					<div class="charitable-container charitable-report-card charitable-top-campaigns-report">
						<div class="header">
							<h4><?php echo esc_html__( 'Top Campaigns', 'charitable' ); ?></h4>
							<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
						</div>
						<div class="charitable-toggle-container charitable-report-ui">
						<?php if ( ! empty( $top_campaigns->posts ) ) : ?>
							<div class="the-list">
								<ul id="charitable-top-campaigns-list">
									<?php echo $charitable_reports->generate_top_campaigns( $top_campaigns ); // phpcs:ignore ?>
								</ul>
							</div>
						<?php else : ?>
							<div class="no-items">
								<p><strong><?php echo esc_html__( 'There are no campaigns at the moment.', 'charitable' ); ?></strong></p>
								<?php if ( false === $action ) : ?>
									<?php if ( charitable_disable_legacy_campaigns() ) : ?>
									<p class="link"><a href="<?php echo admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ); // phpcs:ignore ?>"><?php echo esc_html__( 'Add New Campaign', 'charitable' ); ?>
										<?php else : ?>
										<p class="link"><a href="<?php echo admin_url( 'post-new.php?post_type=campaign' ); // phpcs:ignore ?>"><?php echo esc_html__( 'Add New Campaign', 'charitable' ); ?>
									<?php endif; ?>


																	<?php
																	if ( $include_icons ) :
																		?>
									<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /><?php endif; ?></a></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						</div>
						<div class="more">
							<?php if ( ! empty( $top_campaigns->posts ) && false === $action ) : ?>
								<?php if ( charitable_disable_legacy_campaigns() ) : ?>
									<a href="<?php echo admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ); // phpcs:ignore ?>"><?php echo esc_html__( 'Add New Campaign', 'charitable' ); ?> <img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a>
								<?php else : ?>
									<a href="<?php echo admin_url( 'post-new.php?post_type=campaign' ); // phpcs:ignore ?>"><?php echo esc_html__( 'Add New Campaign', 'charitable' ); ?> <img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>

					<?php
					if ( $show_recommended_snippets ) :

						$recommended_snippets = $this->get_dashboard_recommended_snippets( 2 );

						if ( ! empty( $recommended_snippets ) ) :

							?>
							<div class="charitable-container charitable-report-card charitable-recommended-snippets">
								<div class="header">
								<h4><?php echo esc_html__( 'Recommended Snippets', 'charitable' ); ?></h4>
									<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
								</div>
								<div class="charitable-toggle-container charitable-report-ui">
									<div id="charitable-wpcode-snippets-list">
										<div class="charitable-wpcode-snippets-list">

											<?php
											foreach ( $recommended_snippets as $snippet ) :
												$button_text       = $snippet['installed'] ? __( 'Edit Snippet', 'charitable' ) : __( 'Install Snippet', 'charitable' );
												$button_type_class = $snippet['installed'] ? 'button-primary' : 'button-secondary';
												$button_action     = $snippet['installed'] ? 'edit' : 'install';
												$library_id        = ! empty( $snippet['library_id'] ) ? ( $snippet['library_id'] ) : false;
												?>
												<div class="charitable-wpcode-snippet">
													<div class="charitable-wpcode-snippet-header">
														<h3 class="charitable-wpcode-snippet-title"><?php echo esc_html( $snippet['title'] ); ?></h3>
														<div class="charitable-wpcode-snippet-note"><?php echo esc_html( $snippet['note'] ); ?></div>
													</div>
													<div class="charitable-wpcode-snippet-footer">
														<a
															href="<?php echo esc_url( $snippet['install'] ); ?>"
															class="button charitable-wpcode-snippet-button <?php echo sanitize_html_class( $button_type_class ); ?>"
															data-action="<?php echo esc_attr( $button_action ); ?>"><?php echo esc_html( $button_text ); ?> </a>
															<?php if ( $library_id ) : ?>
																<a class="charitable-wpcode-snippet-external-link" title="<?php esc_html_e( 'View this snippet on WPCode.com', 'charitable' ); ?>" href="https://library.wpcode.com/profile/wpcharitable/?code_type=all&order=popular&view=all&search=<?php echo urlencode( $snippet['title'] ); ?>" target="_blank"><span class="dashicons dashicons-external"></span></a>
															<?php endif; ?>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
										<div class="no-items">
									<p class="link"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=snippets' ) ); ?>"><?php esc_html_e( 'View All Snippets', 'charitable' ); ?> <img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/icons/east.svg'; ?>" /> </a></p>
								</div>
									</div>
								</div>
							</div>
							<?php

							endif; // snippets links not empty.

					endif;

					if ( $show_support_box ) :

						$dashboard_support_links = $this->get_dashboard_support_links();

						if ( ! empty( $dashboard_support_links ) ) :
							?>
							<div class="charitable-container charitable-report-card charitable-support">
								<div class="header">
								<h4><?php echo esc_html__( 'Support', 'charitable' ); ?></h4>
									<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
								</div>
								<div class="charitable-toggle-container charitable-report-ui">
									<div class="the-list">
										<ul>
											<?php foreach ( $dashboard_support_links as $dashboard_support_link ) : ?>
												<?php
												if ( ! empty( $dashboard_support_link['lite_pro'] )
												&& ( ( $dashboard_support_link['lite_pro'] === 'lite' && charitable_is_pro() ) || ( $dashboard_support_link['lite_pro'] === 'pro' && ! charitable_is_pro() ) ) ) {
													continue;
												}
												?>
											<li>
												<?php if ( ! empty( $dashboard_support_link['icon'] ) ) : ?>
												<div class="icon">
													<img src="<?php echo esc_url( $dashboard_support_link['icon'] ); ?>" alt="">
												</div>
												<?php endif; ?>
												<div class="info">
													<a target="_blank" href="<?php echo esc_url( $dashboard_support_link['url'] ); ?>"><?php echo esc_html( $dashboard_support_link['title'] ); ?></a>
												</div>
											</li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
							</div>
							<?php

						endif; // support links not empty.

					endif; // show support box.

					if ( $show_notifications ) :

						Charitable_Notifications::get_instance()->output( 'dashboard' );

					endif; // show support box.

					if ( $show_recommended_addons ) :

						$recommended_addons = $this->get_dashboard_recommended_addons();

						if ( ! empty( $recommended_addons ) ) :

							?>
							<div class="charitable-container charitable-report-card charitable-recommended-addons">
								<div class="header">
								<h4><?php echo esc_html__( 'Recommended Addons', 'charitable' ); ?></h4>
									<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
								</div>
								<div class="charitable-toggle-container charitable-report-ui">
									<div class="the-list">
										<ul>
										<?php
										foreach ( $recommended_addons as $addon_slug => $addon_info ) :

											$extension_url = charitable_ga_url(
												'https://wpcharitable.com/extensions/charitable-' . $addon_slug,
												urlencode( 'Recommended Extensions' ), // phpcs:ignore
												$addon_slug
											);

											?>
											<li class="<?php echo esc_attr( $addon_slug ); ?>">
												<?php if ( ! empty( $addon_info['recommended'] ) ) : ?>
													<span class="popular"><?php echo esc_html__( 'Popular', 'charitable' ); ?></span>
												<?php endif; ?>
												<a href="<?php echo esc_url( $extension_url ); ?>" target="_blank" rel="noopener">
													<?php /* translators: %s: extension title */ ?>
													<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/extensions/<?php echo esc_attr( $addon_slug ); ?>.png" alt="<?php echo esc_attr( sprintf( _x( '%s banner', 'extension banner', 'charitable' ), $addon_slug ) ); ?>" />
													<h4><?php echo esc_html( $addon_info['title'] ); ?></h4>
													<?php echo esc_html( $addon_info['description'] ); ?>
												</a>
											</li><?php endforeach; ?>
										</ul>
									</div>
								</div>
							</div>
							<?php

						endif; // support links not empty.

					endif; // show recommended addons box.

					$html['charitable_reports'] = ob_get_clean();

					if ( $this->maybe_cache_dashboard( $use_cache ) ) :

						set_transient( 'wpch_dashboard_report_html_' . intval( $days ), $html, MINUTE_IN_SECONDS );
						set_transient( 'wpch_dashboard_report_html_donation_axis_' . intval( $days ), $this->donation_axis, MINUTE_IN_SECONDS );
						set_transient( 'wpch_dashboard_report_html_date_axis_' . intval( $days ), $this->date_axis, MINUTE_IN_SECONDS );

					endif;

			} // End if().

			return $html;
		}

		/**
		 * Get the cached dashboard data args.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_cached_dashboard_data_args() {

			if ( ! $this->maybe_cache_dashboard() ) {
				return false;
			}

			$dashboard_data_args = get_transient( 'wpch_dashboard_data_args' );

			return $dashboard_data_args;
		}

		/**
		 * Set the cached dashboard data args.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $dashboard_data_args The dashboard data args.
		 *
		 * @return bool False if not set, otherwise the date/time of cache.
		 */
		public function set_cached_dashboard_data_args( $dashboard_data_args = array() ) {

			if ( empty( $dashboard_data_args ) || ! $this->maybe_cache_dashboard() ) {
				return false;
			}

			// Set the timestamp.
			$dashboard_data_args['timestamp'] = gmdate( 'Y/m/d h:i A', current_time( 'timestamp', 0 ) );

			$transient_set = set_transient( 'wpch_dashboard_data_args', $dashboard_data_args, DAY_IN_SECONDS );

			return ( $transient_set ) ? $dashboard_data_args['timestamp'] : false; // phpcs:ignore
		}

		/**
		 * A check to see if the dashboard data is cached, and if so the timestamp of when it was cached.
		 *
		 * @since  1.8.1
		 *
		 * @return string
		 */
		public function is_dashboard_data_cached() {

			if ( ! $this->maybe_cache_dashboard() ) {
				return false;
			}

			$dashboard_data_args = get_transient( 'wpch_dashboard_data_args' );

			if ( $dashboard_data_args && ! empty( $dashboard_data_args['timestamp'] ) ) {
				// return format of the date/time to something like March 31, 1970 12:00 AM.
				return gmdate( 'F j, Y h:i A', strtotime( $dashboard_data_args['timestamp'] ) );
			}

			return false;
		}

		/**
		 * Get the start date.
		 *
		 * @since  1.8.1
		 *
		 * @return string
		 */
		public function get_start_date() {

			$start_date = ! empty( $this->dashboard_data['start_date'] ) ? $this->dashboard_data['start_date'] : false;

			return $start_date;
		}

		/**
		 * Get the end date.
		 *
		 * @since  1.8.1
		 *
		 * @return string
		 */
		public function get_end_date() {

			$end_date = ! empty( $this->dashboard_data['end_date'] ) ? $this->dashboard_data['end_date'] : false;

			return $end_date;
		}

		/**
		 * Get the filter value.
		 *
		 * @since  1.8.1
		 *
		 * @return string
		 */
		public function get_filter_value() {

			$filter = ! empty( $this->dashboard_data['filter'] ) ? $this->dashboard_data['filter'] : false;

			return $filter;
		}

		/**
		 * Check if the report should be cached.
		 *
		 * @since 1.8.1
		 *
		 * @param  bool $force_cache Force cache. Default is 'maybe'. Accepts 'yes', 'no', 'maybe'. :-).
		 *
		 * @return bool
		 */
		public function maybe_cache_dashboard( $force_cache = 'maybe' ) {

			if ( $force_cache === 'yes' ) {
				return true;
			}
			if ( $force_cache === 'no' ) {
				return false;
			}
			if ( charitable_is_debug() ) {
				return false;
			}
			if ( defined( 'CHARITABLE_REPORTS_NO_CACHE' ) && CHARITABLE_REPORTS_NO_CACHE ) {
				return false;
			}

			return true;
		}

		/**
		 * Is the data cached?
		 *
		 * @since  1.8.1
		 *
		 * @return boolean
		 */
		public function is_data_cached() {

			return $this->is_data_cached;
		}

		/**
		 * Return the HTML of the filter select dropdown.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $selected_value The selected value.
		 *
		 * @return string
		 */
		public function get_filter_dropdown( $selected_value = false ) {

			$options_values = $this->date_range_filter_values;

			$selected_value = false === $selected_value && $this->days ? $this->days : $selected_value;
			$selected_value = false === $selected_value && $this->filter_days_default ? $this->filter_days_default : $selected_value;

			ob_start();

			?>

			<select name="action" id="report-date-range-filter">
				<?php foreach ( $options_values as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Return the default number of days for the dashboard filter.
		 *
		 * @since  1.8.1
		 *
		 * @return string
		 */
		public function get_days() {

			$selected_value = $this->days ? $this->days : false;
			$selected_value = false === $selected_value && $this->filter_days_default ? $this->filter_days_default : $selected_value;

			return $selected_value;
		}

		/**
		 * Util function that gets dates between two given dates.
		 *
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $format The date format.
		 *
		 * @since  1.8.1
		 *
		 * @return mixed
		 */
		public function get_days_between_dates( $start_date, $end_date, $format = 'M d' ) { // phpcs:ignore
			$days = array();

			$current_date = new DateTime( $start_date );
			$end_date     = new DateTime( $end_date );

			while ( $current_date <= $end_date ) {
				$days[] = $current_date->format( 'M d' ); // 'Y-m-d
				$current_date->modify( '+1 day' );
			}

			return $days;
		}


		/**
		 * Load scripts and styles.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function maybe_load_scripts() {

			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();

			// Add Javascript vars at the footer of the WordPress admin, at the dashboard and the overview tab of reporting.
			if ( ! is_null( $screen ) && $screen->id === 'charitable_page_charitable-dashboard' ) {
				// Specific styles for the "dashboard".
				add_action( 'admin_footer', array( $this, 'report_vars' ), 100 );
			}
		}

		/**
		 * Enqueue assets and write JS vars for the report.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function report_vars() {

			?>
			<script id="charitable-report-data-js">
				var charitable_reporting = <?php echo wp_json_encode( $this->get_localized_strings() ); ?>;
			</script>

			<?php
		}

		/**
		 * Get localized strings.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		private function get_localized_strings() {

			$currency_helper = charitable_get_currency_helper();

			$strings = array(
				'version'                       => Charitable::VERSION,
				'nonce'                         => wp_create_nonce( 'charitable-reporting' ),
				'admin_nonce'                   => wp_create_nonce( 'charitable-admin' ),
				'currency_symbol'               => $currency_helper->get_currency_symbol(),
				'ajax_url'                      => admin_url( 'admin-ajax.php' ),
				'date_select_day'               => 'DD',
				'date_select_month'             => 'MM',
				'headline_chart_options'        => array(
					'donation_axis'   => (array) $this->get_donation_axis(),
					'date_axis'       => (array) $this->get_date_axis(),
					'enable_tooltips' => (bool) $this->enable_tooltips(),
				),
				'payment_methods_chart_options' => array(
					'payment_percentages' => array(),
					'payment_labels'      => array(),
				),
			);

			$strings = apply_filters( 'charitable_admin_dashboard_strings', $strings );

			return $strings;
		}

		/**
		 * Get recommended addons, which can be based on the locale or currency.
		 *
		 * @since  1.8.1
		 * @since  1.8.1.5 Added recommended and $limit.
		 *
		 * @param  string $currency The currency code.
		 * @param  string $locale The locale.
		 * @param  int    $limit The number of recommended addons to return.
		 *
		 * @return array
		 */
		public function get_dashboard_recommended_addons( $currency = false, $locale = false, $limit = 6 ) {

			$currency   = ( false === $currency && function_exists( 'charitable_get_default_currency' ) ? charitable_get_default_currency() : $currency );
			$currencies = charitable_get_currency_helper()->get_all_currencies();
			$locale     = $locale ? $locale : get_locale();

			$all_extensions = array(
				'payfast'                        => array(
					'title'       => esc_html__( 'Refunds', 'charitable' ),
					'description' => esc_html__( 'Accept donations in South African Rand', 'charitable' ),
				),
				'payu-money'                     => array(
					'title'       => esc_html__( 'PayUmoney', 'charitable' ),
					'description' => esc_html__( 'Accept donations in Indian Rupees with PayUmoney', 'charitable' ),
				),
				'easy-digital-downloads-connect' => array(
					'title'       => esc_html__( 'Easy Digital Downloads', 'charitable' ),
					'description' => esc_html__( 'Collect donations with Easy Digital Downloads', 'charitable' ),
				),
				'recurring-donations'            => array(
					'title'       => esc_html__( 'Recurring Donations', 'charitable' ),
					'description' => esc_html__( 'Accept recurring donations', 'charitable' ),
				),
				'fee-relief'                     => array(
					'title'       => esc_html__( 'Fee Relief', 'charitable' ),
					'description' => esc_html__( 'Let donors cover the gateway fees', 'charitable' ),
				),
				'authorize-net'                  => array(
					'title'       => esc_html__( 'Authorize.NET', 'charitable' ),
					'description' => esc_html__( 'Collect donations with Authorize.Net', 'charitable' ),
				),
				'ambassadors'                    => array(
					'title'       => esc_html__( 'Ambassadors', 'charitable' ),
					'description' => esc_html__( 'Peer to peer fundraising or crowdfunding', 'charitable' ),
				),
				'windcave'                       => array(
					'title'       => esc_html__( 'Windcave', 'charitable' ),
					'description' => sprintf(
						/* translators: %s: currency code */
						__( 'Collect donations in %s', 'charitable' ),
						$currencies[ $currency ]
					),
				),
				'anonymous-donations'            => array(
					'title'       => esc_html__( 'Anonymous Donations', 'charitable' ),
					'description' => esc_html__( 'Let donors give anonymously', 'charitable' ),
				),
				'user-avatar'                    => array(
					'title'       => esc_html__( 'User Avatar', 'charitable' ),
					'description' => esc_html__( 'Let your donors upload their own profile photo', 'charitable' ),
				),
				'video'                          => array(
					'title'       => esc_html__( 'Video', 'charitable' ),
					'description' => esc_html__( 'Boost campaigns by adding videos', 'charitable' ),
				),
				'annual-receipts'                => array(
					'title'       => esc_html__( 'Annual Receipts', 'charitable' ),
					'description' => esc_html__( 'Provide downloadable annual receipts', 'charitable' ),
				),
				'pdf-receipts'                   => array(
					'title'       => esc_html__( 'PDF Receipts', 'charitable' ),
					'description' => esc_html__( 'Make life easy for your donors by providing them with a PDF receipt for their donation', 'charitable' ),
				),
			);

			if ( 'en_ZA' === $locale || 'ZAR' === $currency ) {
				$extensions = array_intersect_key(
					$all_extensions,
					array(
						'payfast'             => '',
						'recurring-donations' => '',
						'ambassadors'         => '',
						'fee-relief'          => '',
					)
				);
			} elseif ( 'hi_IN' === $locale || 'INR' === $currency ) {
				$extensions = array_intersect_key(
					$all_extensions,
					array(
						'payu-money'  => '',
						'ambassadors' => '',
						'fee-relief'  => '',
						'windcave'    => '',
					)
				);
			} elseif ( in_array( $locale, array( 'en_NZ', 'ms_MY', 'ja', 'zh_HK' ) ) || in_array( $currency, array( 'NZD', 'MYR', 'JPY', 'HKD' ), true ) ) {
				$extensions = array_intersect_key(
					$all_extensions,
					array(
						'recurring-donations' => '',
						'windcave'            => '',
						'fee-relief'          => '',
					)
				);
			} elseif ( in_array( $locale, array( 'th' ) ) || in_array( $currency, array( 'BND', 'FJD', 'KWD', 'PGK', 'SBD', 'THB', 'TOP', 'VUV', 'WST' ), true ) ) {
				$extensions = array_intersect_key(
					$all_extensions,
					array(
						'windcave'            => '',
						'fee-relief'          => '',
						'ambassadors'         => '',
						'anonymous-donations' => '',
					)
				);
			} elseif ( class_exists( 'EDD' ) ) {
				$extensions = array_intersect_key(
					$all_extensions,
					array(
						'ambassadors'                    => '',
						'easy-digital-downloads-connect' => '',
						'anonymous-donations'            => '',
						'user-avatar'                    => '',
					)
				);
			} else {
				$extensions = array_intersect_key(
					$all_extensions,
					array(
						'recurring-donations' => '',
						'authorize-net'       => '',
						'fee-relief'          => '',
						'ambassadors'         => '',
						'video'               => '',
						'annual-receipts'     => '',
					)
				);
			}

			$recommended_addons = $this->get_recommended_dashboard_addons();

			// Recommended addons.
			if ( ! empty( $recommended_addons ) ) {

				foreach ( $extensions as $key => $value ) {
					// go through extensions and remove any that are already recommended.
					if ( array_key_exists( $key, $recommended_addons ) ) {
						unset( $extensions[ $key ] );
					}
					// add recommended addons to the front of the extensions array.
					$extensions = array_merge( $recommended_addons, $extensions );

				}
			}

			// Chop off array to the limit.
			$extensions = array_slice( $extensions, 0, $limit );

			return $extensions;
		}

		/**
		 * Get the recommended dashboard addons.
		 *
		 * @since   1.8.1.5
		 * @version 1.8.3
		 * @version 1.8.8.2
		 *
		 * @return array
		 */
		public function get_recommended_dashboard_addons() {

			$recommended_addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $recommended_addons ) {
				$recommended_addons = charitable_get_addons_data_from_server();
			}

			$recommended_to_return = array();

			if ( $recommended_addons ) {

				foreach ( (array) $recommended_addons as $i => $addon ) {

					if ( ! isset( $addon['slug'] ) || $addon['slug'] === '' || strtolower( $addon['slug'] ) === 'auto draft' ) {
						continue;
					}

					if ( ! is_array( $addon ) ) {
						$addon = array();
					}

					if ( isset( $addon['featured'] ) && ! empty( $addon['sections'] ) && in_array( 'recommended', $addon['featured'], true ) ) {

						// Check if sections is already an array or needs to be unserialized (added in 1.8.8.2)
						$sections    = is_array( $addon['sections'] ) ? $addon['sections'] : unserialize( $addon['sections'] );
						$description = isset( $sections['description'] ) ? $sections['description'] : '';

						// subsutite test for translations, trying in 1.8.3.
						$addon['name'] = ! empty( $addon['name'] ) && strtolower( $addon['name'] ) === 'charitable recurring donations' ? __( 'Charitable Recurring Donations', 'charitable' ) : $addon['name'];
						$addon['name'] = ! empty( $addon['name'] ) && strtolower( $addon['name'] ) === 'charitable pdf receipts' ? __( 'Charitable PDF Receipts', 'charitable' ) : $addon['name'];
						$description   = ! empty( $description ) && strpos( $description, 'with recurring donations' ) !== false ? __( 'Grow your organization\'s revenue with recurring donations.', 'charitable' ) : $description;
						$description   = ! empty( $description ) && strpos( $description, 'PDF receipt' ) !== false ? __( 'Make life easy for your donors by providing them with a PDF receipt for their donation.', 'charitable' ) : $description;

						$recommended_to_return[ str_replace( 'charitable-', '', $addon['slug'] ) ] = array(
							'title'       => $addon['name'],
							'description' => wp_strip_all_tags( $description ),
							'recommended' => true,
						);

					}
				}
			}

			return $recommended_to_return;
		}

		/**
		 * Get recommended snippets.
		 *
		 * @since  1.8.1.6
		 *
		 * @param  int  $limit The number of recommended addons to return.
		 * @param  bool $show_installed Show installed snippets.
		 *
		 * @return array
		 */
		public function get_dashboard_recommended_snippets( $limit = 3, $show_installed = true ) { // phpcs:ignore

			$snippets = charitable_get_intergration_wpcode()->load_charitable_snippets( $limit, $show_installed );

			return $snippets;
		}

		/**
		 * Get the dashboard support links, which are displayed in a "card" on the dashboard.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_dashboard_support_links() {

			$dashboard_support_links = apply_filters(
				'charitable_dashboard_support_links',
				array(
					array(
						'title'    => __( 'Getting started? Read the Beginners Guide', 'charitable' ),
						'url'      => 'https://wpcharitable.com/start-here/?referrer=chariable-dashboard',
						'icon'     => charitable()->get_path( 'assets', false ) . 'images/icons/stories.svg',
						'lite_pro' => false,
					),
					array(
						'title'    => __( 'View Our Documentation', 'charitable' ),
						'url'      => 'https://wpcharitable.com/documentation?referrer=chariable-dashboard',
						'icon'     => charitable()->get_path( 'assets', false ) . 'images/icons/menu_book.svg',
						'lite_pro' => false,
					),
					array(
						'title'    => __( 'Contact Our Expert Support Team For Help', 'charitable' ),
						'url'      => 'https://wpcharitable.com/support?referrer=chariable-dashboard',
						'icon'     => charitable()->get_path( 'assets', false ) . 'images/icons/sms.svg',
						'lite_pro' => false,
					),
					array(
						'title'    => __( 'Upgrade From Lite to Unlock More Powerful Features', 'charitable' ),
						'url'      => 'https://wpcharitable.com/lite-vs-pro?referrer=chariable-dashboard',
						'icon'     => charitable()->get_path( 'assets', false ) . 'images/icons/bullet_check.svg',
						'lite_pro' => 'lite',
					),
					array(
						'title'    => __( 'Upgrade Your Plan to Unlock More Powerful Features', 'charitable' ),
						'url'      => 'https://wpcharitable.com/pricing?referrer=chariable-dashboard',
						'icon'     => charitable()->get_path( 'assets', false ) . 'images/icons/bullet_check.svg',
						'lite_pro' => 'pro',
					),
				)
			);

			return $dashboard_support_links;
		}

		/**
		 * Get the notices to display on the dashboard. Get any stored notices/notifications from the database.
		 *
		 * @since  1.8.3
		 *
		 * @return array
		 */
		public function get_notifications() {

			$notifications_html = Charitable_Notifications::get_instance()->output( 'dashboard' );

			return $notifications_html;
		}

		/**
		 * Get the notices to display on the dashboard. Get any stored notices/notifications from the database.
		 *
		 * @since  1.8.1.6
		 * @version 1.8.2
		 *
		 * @return array
		 */
		public function get_notices() {

			return (array) apply_filters( 'charitable_dashboard_notices', get_option( 'charitable_dashboard_notifications', array() ) );
		}

		/**
		 * Get the dashboard guide tool notices.
		 *
		 * @since 1.8.1.6
		 * @since 1.8.1.10
		 *
		 * @return bool
		 */
		public function maybe_show_dashboard_growth_tool_chart_notice() {

			$charitable_growth_tool_notices = get_option( 'charitable_growth_tool_notices', false );

			$ret = true;

			if ( ! $charitable_growth_tool_notices ) {
				$ret = true;
			}

			if ( ! empty( $charitable_growth_tool_notices['dismiss']['dashboard'] ) ) {
				$ret = false;
			}

			// Don't show this immediately for new users.
			$slug = 'dashboard-growth-tool-chart';

			// determine when to display this message. for now, there should be some sensible boundaries before showing the notification: a minimum of 14 days of use, created one campaign.
			$activated_datetime = ( false !== get_option( 'wpcharitable_activated_datetime' ) ) ? get_option( 'wpcharitable_activated_datetime' ) : false;
			$days               = 0;
			if ( $activated_datetime ) {
				$diff = current_time( 'timestamp' ) - $activated_datetime;
				$days = abs( round( $diff / 86400 ) );
			}

			$count_campaigns = wp_count_posts( 'campaign' );
			$total_campaigns = isset( $count_campaigns->publish ) ? $count_campaigns->publish : 0;

			if ( $days >= apply_filters( 'charitable_days_since_activated', 14 ) && $total_campaigns >= 1 ) {
				// check transient.
				$help_pointers = get_transient( 'charitable_' . $slug . '_banner' );

				// render five star rating banner/notice.
				if ( ! $help_pointers ) {
					return apply_filters( 'charitable_show_dashboard_growth_tool_chart_notice', $ret );
				}
			}

			return false;
		}

		/* DEPRECATED FUNCTIONS */

		/**
		 * Display upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since   1.8.1
		 * @version 1.8.8
		 *
		 * @param string $view Current view inside the plugin settings page.
		 * @param string $css_class CSS class for the cta.
		 * @param bool   $show_close_button Whether to show the close button.
		 */
		public function dashboard_cta( $view = false, $css_class = 'reports-lite-cta', $show_close_button = false ) { // phpcs:ignore

			if ( charitable_is_pro() ) {
				// no need to display this cta since they have a valid license.
				return;
			}

			if ( get_option( 'charitable_lite_reports_upgrade', false ) || apply_filters( 'charitable_lite_reports_upgrade', false ) ) {
				return;
			}
			?>
			<?php if ( $show_close_button ) : ?>
				<div class="charitable-cta-dismiss-container">
					<button type="button" class="button-link charitable-banner-dismiss dismiss">x</button>
				</div>
			<?php endif; ?>
			<?php charitable_render_global_upgrade_cta( $css_class ); ?>
			<script type="text/javascript">
				jQuery( function ( $ ) {
					$( document ).on( 'click', '.reports-lite-cta .dismiss', function ( event ) {
						event.preventDefault();
						$.post( ajaxurl, {
							action: 'charitable_lite_reports_upgrade',
							charitable_action: 'remove_lite_cta'
						} );
						$( '.reports-lite-cta' ).remove();
					} );
				} );
			</script>
			<?php
		}

		/**
		 * Dismiss upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since 1.7.0.4
		 */
		public function reports_cta_dismiss() {

			if ( ! charitable_current_user_can() ) {
				wp_send_json_error();
			}

			update_option( 'charitable_lite_reports_upgrade', time() );

			wp_send_json_success();
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Dashboard_Legacy
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
