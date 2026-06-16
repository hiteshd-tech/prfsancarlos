<?php
/**
 * Charitable Reports UI.
 *
 * @package   Charitable/Classes/Charitable_Reports
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

if ( ! class_exists( 'Charitable_Reports' ) ) :

	/**
	 * Charitable_Reports
	 *
	 * @final
	 * @since 1.8.1
	 */
	class Charitable_Reports {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Reports|null
		 */
		private static $instance = null;

		/**
		 * The date filter values.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $date_filter_values = array();

		/**
		 * The report type.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $report_type = false;

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
		 * The key filter for the report.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $filter = false;

		/**
		 * The (post) status filter for the report, usually reflecting post_status.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $status = false;

		/**
		 * The campaign ID filter for the report.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $campaign_id = false;

		/**
		 * The Charitable custom tax category ID filter for the report.
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $category_id = false;

		/**
		 * The donation data axis for the highlight report.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donation_axis = array();

		/**
		 * The date axis for the highlight report.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $date_axis = array();

		/**
		 * The donation data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donations = array();

		/**
		 * The donor data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donors = array();

		/**
		 * The donor total for the query.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $donors_total = 0;

		/**
		 * The payment percentages data for the payment report/graph.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $payment_percentages = array( '100' );

		/**
		 * The payment percentages labels for the payment report/graph.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $payment_labels = array( 'No donations found' );

		/**
		 * The payment gateway keys for the payment report/graph (used for color mapping).
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $payment_keys = array();

		/**
		 * The activities data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $activities = array();

		/**
		 * The dot ... threshold for the pagination.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $pagination_dot_threshold = 5;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.1
		 */
		public function __construct() {
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $status The status.
		 * @param  int    $campaign_id The campaign ID.
		 * @param  int    $category_id The category ID.
		 *
		 * @return void
		 */
		public function init( $report_type = 'overview', $start_date = false, $end_date = false, $status = false, $campaign_id = false, $category_id = false ) {

			do_action( 'charitable_admin_reports_init_start', $this );

			// Get the date filter values.
			$this->date_filter_values = apply_filters(
				'charitable_dashboard_date_filter_values',
				array(
					'7'  => __( 'Last 7 Days', 'charitable' ),
					'14' => __( 'Last 14 Days', 'charitable' ),
					'30' => __( 'Last 30 Days', 'charitable' ),
					'0'  => __( 'Custom Date Range', 'charitable' ),
				)
			);

			$this->report_type = $report_type;
			$this->start_date  = $start_date;
			$this->end_date    = $end_date;
			$this->status      = $status;
			$this->campaign_id = $campaign_id;
			$this->category_id = $category_id;

			// Customize how we grab data from the database based on the report type.
			if ( $this->is_report_type_donor( $report_type ) ) {
				$this->get_donors_data( $report_type, $start_date, $end_date, $campaign_id, $category_id );
			} else {
				$this->get_data( $report_type, $start_date, $end_date, $status, $campaign_id, $category_id );
			}

			$this->maybe_load_scripts();
			$this->maybe_add_reports_cta();

			do_action( 'charitable_admin_reports_init_end', $this );
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $args The report args.
		 *
		 * @return void
		 */
		public function init_with_array( $report_type = 'overview', $args = array() ) {

			do_action( 'charitable_admin_reports_init_start', $this );

			// Get the date filter values.
			$this->date_filter_values = apply_filters(
				'charitable_dashboard_date_filter_values',
				array(
					'7'  => __( 'Last 7 Days', 'charitable' ),
					'14' => __( 'Last 14 Days', 'charitable' ),
					'30' => __( 'Last 30 Days', 'charitable' ),
					'0'  => __( 'Custom Date Range', 'charitable' ),
				)
			);

			$defaults = array(
				'report_type' => 'overview',
				'start_date'  => false,
				'end_date'    => false,
				'status'      => false,
				'campaign_id' => false,
				'category_id' => false,
				'limit'       => false,
				'offset'      => false,
				'ppage'       => false,
			);

			$args = wp_parse_args( $args, $defaults );

			$this->report_type = $args['report_type'];
			$this->start_date  = $args['start_date'];
			$this->end_date    = $args['end_date'];
			$this->status      = $args['status'];
			$this->campaign_id = $args['campaign_id'];
			$this->category_id = $args['category_id'];

			// Customize how we grab data from the database based on the report type.
			if ( $this->is_report_type_donor( $report_type ) ) {
				// override the report type in $args with what was passed in.
				$args['report_type'] = $report_type;

				$this->get_donors_data_array( $report_type, $args );
			} else {
				$this->get_data( $report_type, $args );
			}

			$this->maybe_load_scripts();
			$this->maybe_add_reports_cta();

			do_action( 'charitable_admin_reports_init_end', $this );
		}

		/**
		 * Generate HTML for the Top Campaigns list.
		 *
		 * @since  1.8.1
		 * @version 1.8.3 Make status translatable.
		 *
		 * @param  object $top_campaigns The top campaigns data.
		 * @param  bool   $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_top_campaigns( $top_campaigns = false, $include_icons = true ) {

			if ( empty( $top_campaigns ) || empty( $top_campaigns->posts ) ) {
				return;
			}

			ob_start();

			foreach ( $top_campaigns->posts as $campaign ) :

				$_campaign       = $campaign->ID ? charitable_get_campaign( $campaign->ID ) : false;
				$status          = $_campaign ? $_campaign->get_status() : false;
				switch ( $status ) {
					case 'active':
						$status_string = esc_html__( 'Active', 'charitable' );
						break;
					case 'draft':
						$status_string = esc_html__( 'Draft', 'charitable' );
						break;
					case 'completed':
						$status_string = esc_html__( 'Completed', 'charitable' );
						break;
					case 'failed':
						$status_string = esc_html__( 'Failed', 'charitable' );
						break;
					default:
						$status_string = ucwords( $status );
						break;
				}
				$donated_amount  = $_campaign->get_donated_amount();
				$donor_count     = $_campaign->get_donor_count();
				$donation_goal   = $_campaign->get_goal();
				$time_ended      = $_campaign->get_time_since_ended();
				$end_date        = $_campaign->get_end_date();
				$percent_donated = $_campaign->get_percent_donated_raw();
				if ( $percent_donated > 100 ) {
					$percent_donated = 100;
				}

				?>
				<li class="campaign-completed">
					<div class="main">
						<div class="info">
							<p class="campaign-title"><a href="<?php echo esc_url( charitable_get_admin_campaign_edit_url( $campaign->ID ) ); ?>" target="_blank"><?php echo esc_html( $campaign->post_title ); ?></a></p>
							<div class="info-summary">
								<p class="total-donations"><?php echo esc_html__( 'Total Donations', 'charitable' ); ?>: <strong><?php echo charitable_format_money( $donated_amount ); // phpcs:ignore ?></strong></p>
								<p class="cr"><?php echo esc_html__( 'Total Donors', 'charitable' ); ?>: <strong><?php echo intval( $donor_count ); ?></strong></p>
								<?php if ( $donation_goal ) : ?>
								<p class="cr"><?php echo esc_html__( 'Goal', 'charitable' ); ?>: <strong><?php echo charitable_format_money( $donation_goal ); // phpcs:ignore ?></strong></p>
								<?php endif; ?>
								<?php if ( $end_date ) : ?>
								<p class="cr"><?php echo esc_html__( 'End Date', 'charitable' ); ?>: <strong><?php echo $end_date; // phpcs:ignore ?></strong></p>
								<?php endif; ?>
							</div>
						</div>
						<div class="status">
							<p><span class="badge <?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_string ); ?></span></p>
						</div>
					</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
					<div class="progress-bar">
					<?php
					if ( $donated_amount > 0 && $donation_goal && $percent_donated > 0 ) :
						?>
						<div class="progress" style="width: <?php echo intval( $percent_donated ); ?>%"></div>
						<?php
						endif;
					?>
					</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
				</li>
				<?php endforeach; ?>
			<?php

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the Top Donor List
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donors The donor data.
		 * @param  bool  $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_top_donors( $donors = array(), $include_icons = true ) {

			ob_start();

			if ( empty( $donors ) ) {
				?>

				<div class="no-items the-list">
					<p><strong><?php echo esc_html__( 'There are no donors within the date range.', 'charitable' ); ?></strong></p>
				</div>

			<?php } else { ?>

				<div class="the-list">
					<ul id="charitable-top-donors-list">

				<?php

				$unique_donors = array();

				foreach ( $donors as $top_donor ) :

					if ( in_array( $top_donor->donor_id, $unique_donors, true ) ) {
						continue;
					}

					// if this is a donation related to a recurring donation, then this parent will have a type of 'recurring'.
					$donation_parent = ! empty( $top_donor->donation_parent_id ) ? charitable_get_donation( $top_donor->donation_parent_id )->get( 'donation_type' ) : false;

					$donor_name   = ! empty( $top_donor->donor_name ) ? esc_html( $top_donor->donor_name ) : 'Unknown';
					$donor_avatar = ! empty( $top_donor->donor_avatar_url ) ? esc_url( $top_donor->donor_avatar_url ) : false;
					if ( false === $donor_avatar && function_exists( 'get_avatar_url' ) && ! empty( $top_donor->donor_email ) ) {
						$donor_avatar = get_avatar_url(
							$top_donor->donor_email,
							array(
								'size' => 50,
							)
						);
					}
					$badge_css   = strtolower( $donation_parent ) === 'recurring' ? 'recurring' : 'one-time';
					$badge_label = strtolower( $donation_parent ) === 'recurring' ? esc_html__( 'Recurring', 'charitable' ) : esc_html__( 'One-Time', 'charitable' );

					$display_amount = ! empty( $top_donor->total_donation_amount ) ? charitable_format_money( $top_donor->total_donation_amount, 2, true ) : charitable_format_money( esc_html( $top_donor->amount ), 2, true );
					$display_count  = ! empty( $top_donor->total_donation_count ) ? intval( $top_donor->total_donation_count ) : 1;

					// based on display count, override the badge css/label.
					if ( $display_count > 1 ) {
						$badge_css   = 'multiple';
						$badge_label = esc_html__( 'Multiple', 'charitable' );
					}

					// ensure we don't display the same donor twice.
					$unique_donors[] = $top_donor->donor_id;

					?>
				<li>
					<?php if ( $include_icons ) : ?>
					<div class="avatar">
						<img src="<?php echo esc_url( $donor_avatar ); ?>" alt="<?php echo esc_html( $donor_name ); ?>">
					</div>
					<?php endif; ?>
					<div class="info">
						<p class="name">
						<?php echo esc_html( $donor_name ); ?>
						</p>
					<?php if ( ! empty( $top_donor->donor_email ) ) : ?>
						<p class="email"><?php echo esc_html( $top_donor->donor_email ); ?></p>
						<?php endif; ?>
					</div>
					<div class="donor-donation-info">
						<p><span class="amount"><?php echo $display_amount; // phpcs:ignore ?></span></p>
						<p><span class="badge <?php echo esc_attr( $badge_css ); ?>"><?php echo esc_html( $badge_label ); ?></span></p>
					</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
				</li>
				<?php endforeach; ?>
					</ul>
				</div>

				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the Recent Donation List (appears on dashboard).
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donations The donation data.
		 * @param  bool  $limit Whether to limit the number of donations.
		 * @param  bool  $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_recent_donations_list( $donations = array(), $limit = false, $include_icons = true ) { // phpcs:ignore

			if ( empty( $donations ) ) {
				return;
			}

			$limit_counter = 0;

			ob_start();

			$statuses = charitable_get_valid_donation_statuses();

			// reverse the donations array.
			$donations = array_reverse( $donations );

			foreach ( $donations as $donation ) :

				if ( $limit && $limit_counter >= $limit ) {
					break;
				}

				$donation_id     = intval( $donation->donation_id );
				$donor_name      = esc_html( $donation->first_name ) . ' ' . esc_html( $donation->last_name );
				$payment_method  = esc_html( $donation->payment_gateway );
				$donation_status = esc_html( $donation->donation_status );

				$donation_status_label = array_key_exists( $donation_status, $statuses ) ? $statuses[ $donation_status ] : $donation_status;

				?>
			<li>
				<div class="main">
					<div class="info">
						<p class="donor-id-name">#<?php echo esc_html( $donation_id ); ?> <?php echo esc_html__( 'by', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?></p>
						<div class="info-summary">
							<p class="amount-donated"><?php echo esc_html__( 'Amount Donated', 'charitable' ); ?>: <strong><?php echo charitable_format_money( $donation->amount ); // phpcs:ignore ?></strong></p>
							<p class="amount-donated"><?php echo esc_html__( 'Method', 'charitable' ); ?>: <strong><?php echo esc_html( ucwords( $payment_method ) ); ?></strong></p>
						</div>
					</div>
					<div class="status">
						<p><span class="badge <?php echo esc_attr( strtolower( $donation_status ) ); ?>"><?php echo esc_html( $donation_status_label ); ?></span></p>
					</div>
				</div>
			</li>
				<?php

				++$limit_counter;

			endforeach;

			return ob_get_clean();
		}

		/**
		 * Return the HTML of the activity filter select dropdown.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $selected_values The selected values to possibly pre-select the <select>.
		 *
		 * @return string
		 */
		public function get_activity_report_filter_dropdown( $selected_values = array() ) {

			if ( ! is_array( $selected_values ) ) {
				$selected_values = array( $selected_values );
			}

			$options_values = apply_filters(
				'charitable_dashboard_activity_report_type_filter_values',
				array(
					'donations-made'      => __( 'Donations Made (All)', 'charitable' ),
					'donations-made-paid' => __( 'Donations Made (Paid)', 'charitable' ),
					'refunds'             => __( 'Refunds', 'charitable' ),
					'campaigns-created'   => __( 'Campaigns Created', 'charitable' ),
					'user-comments'       => __( 'User Comments', 'charitable' ),
				)
			);

			ob_start();

			?>

			<select name="action" id="report-activity-type-filter">
				<option value=""><?php echo esc_html__( 'All Activity', 'charitable' ); ?> </option>
				<?php foreach ( $options_values as $value => $label ) : ?>
					<?php $selected = ! empty( $selected_values ) && in_array( $value, $selected_values, true ) ? 'selected="selected"' : ''; ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php echo esc_html( $selected ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the Activity List
		 *
		 * @since  1.8.1
		 *
		 * @param  array  $activities The activity data.
		 * @param  bool   $include_icons Whether to include icons.
		 * @param  string $show_date_value The date value to show when showing "time ago". Default is 'date_to_sort'.
		 *
		 * @return string
		 */
		public function generate_activity_list( $activities = array(), $include_icons = true, $show_date_value = 'date_to_sort' ) {

			ob_start();

			if ( empty( $activities ) ) {
				?>


					<p><strong><?php echo esc_html__( 'There are no activity within the date range.', 'charitable' ); ?></strong></p>


			<?php } else { ?>

					<ul id="charitable-activity-list" class="charitable-activity-list">

					<?php

					foreach ( $activities as $activity ) :

						$time_ago = ! empty( $activity->$show_date_value ) ? human_time_diff( strtotime( $activity->$show_date_value ), time() ) : false;
						$item_id  = $activity->type === 'campaign' ? $activity->campaign_id : $activity->item_id;

						?>
						<li>
							<?php if ( $include_icons ) : ?>
							<div class="icon">
								<img src="<?php echo esc_url( $this->get_activity_icon_url( $activity->icon ) ); ?>" alt="<?php echo esc_html( $activity->status_label ); ?>">
							</div>
							<?php endif; ?>
							<div class="info">
								<p>
									<?php

									/* Determine Label */

									// If the user isn't using pro and is on a page that isn't the report overview, give them a generic label.
									if ( ! charitable_is_pro() && ( ! isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && 'overview' !== $_GET['tab'] ) ) ) { // phpcs:ignore
										$action_label = esc_html__( 'Charitable Donor', 'charitable' );
									} else {
										// If this is a campaign, show the campaign name vs just any number ID.
										$action_label = isset( $activity->type ) && $activity->type === 'campaign' ? str_replace( '%id', '"' . esc_html( $activity->campaign_title ) . '"', $activity->action_label ) : str_replace( '%id', $activity->item_id_prefix . $item_id, $activity->action_label );
									}

									echo $action_label; // phpcs:ignore

									?>
									<span class="badge <?php echo esc_attr( $activity->status ); ?>"><?php echo esc_html( $activity->status_label ); ?></span>
								</p>
								<?php

								/* Determine Secondary Info */

								// If this is a campaign, get the created_by value and show a name of who created it.
								if ( isset( $activity->type ) && $activity->type === 'campaign' && ! empty( $activity->created_by ) ) {
									$created_by = get_userdata( $activity->created_by );
									$created_by = $created_by ? $created_by->display_name : '';
									// get the WordPress user admin url to edit this user.
									$admin_user_url = get_edit_user_link( $activity->created_by );

									$secondary_info = '<p class="charitable-reports-activity-secondary-info amount">' . esc_html__( 'Created By:', 'charitable' ) . ' <a href="' . esc_url( $admin_user_url ) . '">' . esc_html( $created_by ) . '</a></p>';
								} else {
									$secondary_info = $this->get_activity_secondary_info( $activity );
								}

								echo $secondary_info; // phpcs:ignore

								// phpcs:ignore ?>
							</div>
							<?php if ( $time_ago ) : ?>
							<div class="time-ago">
								<?php echo esc_html( $time_ago ); ?> <?php echo esc_html__( 'ago', 'charitable' ); ?>
							</div>
							<?php endif; ?>
							<?php
							if ( ! $include_icons ) :
								?>
								<br class="clear" /><?php endif; ?>
						</li>
					<?php endforeach; ?>
					</ul>

				<?php
			}

			return ob_get_clean();
		}

		/**
		 * Generate HTML for the "secondary" info for an activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  object $activity The activity data.
		 *
		 * @return string
		 */
		public function get_activity_secondary_info( $activity ) {

			// Each "activity" needs at least an activity type.
			if ( empty( $activity ) || empty( $activity->type ) ) {
				return;
			}

			switch ( $activity->type ) {
				case 'donation':
					return $this->get_donation_activity_secondary_info( $activity );
				case 'campaign':
					return $this->get_campaign_activity_secondary_info( $activity );
				default:
					return;
			}
		}

		/**
		 * Generate HTML for the "secondary" info for a donation activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  object $activity The activity data.
		 *
		 * @return string
		 */
		public function get_donation_activity_secondary_info( $activity ) {

			if ( empty( $activity ) || empty( $activity->type ) ) {
				return;
			}

			$secondary_info = '';

			$primary_action = isset( $activity->primary_action ) ? $activity->primary_action : false;

			switch ( $primary_action ) {
				case 'charitable-completed':
				case 'charitable-pending':
				case 'charitable-refunded':
				case 'charitable-failed':
					$admin_campaign_url = ! empty( $activity->campaign_id ) ? charitable_get_admin_campaign_edit_url( $activity->campaign_id ) : '#';
					$admin_donation_url = ! empty( $activity->item_id ) ? charitable_get_admin_donation_edit_url( $activity->item_id ) : false;
					$campaign_title     = ! empty( $activity->campaign_title ) ? ' to <a target="_blank" href="' . $admin_campaign_url . '"><span class="campaign-title">' . $activity->campaign_title . '</span></a> ' : '';
					$secondary_info     = $admin_donation_url ? '<p class="charitable-reports-activity-secondary-info amount"><a href="' . $admin_donation_url . '" target="_blank">' . charitable_format_money( $activity->amount, 2, true ) . '</a>' . $campaign_title . '</p>' : '<p class="charitable-reports-activity-secondary-info amount">' . charitable_format_money( $activity->amount, 2, true ) . $campaign_title . '</p>';
					break;
				default:
					$admin_campaign_url = ! empty( $activity->campaign_id ) ? charitable_get_admin_campaign_edit_url( $activity->campaign_id ) : '#';
					$admin_donation_url = ! empty( $activity->item_id ) ? charitable_get_admin_donation_edit_url( $activity->item_id ) : false;
					$campaign_title     = ! empty( $activity->campaign_title ) ? ' to <a target="_blank" href="' . $admin_campaign_url . '"><span class="campaign-title">' . $activity->campaign_title . '</span></a> ' : '';
					$secondary_info     = $admin_donation_url ? '<p class="charitable-reports-activity-secondary-info amount"><a href="' . $admin_donation_url . '" target="_blank">' . charitable_format_money( $activity->amount, 2, true ) . '</a>' . $campaign_title . '</p>' : '<p class="charitable-reports-activity-secondary-info amount">' . charitable_format_money( $activity->amount, 2, true ) . $campaign_title . '</p>';
					break;
			}

			return $secondary_info;
		}

		/**
		 * Generate HTML for the "secondary" info for a campaign activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  object $activity The activity data.
		 *
		 * @return string
		 */
		public function get_campaign_activity_secondary_info( $activity ) {

			$secondary_info = '';

			switch ( $activity->primary_action ) {
				case 'update':
					$secondary_info = '<p class="campaign-title">' . $activity->campaign_title . '</p>';
					break;
				default:
					$secondary_info = '<p class="campaign-title">' . $activity->campaign_title . '</p>';
					break;
			}

			return $secondary_info;
		}

		/**
		 * Generates HTML for the payment methods list.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $payment_breakdown The payment breakdown data.
		 * @param  bool  $include_icons Whether to include icons.
		 *
		 * @return string
		 */
		public function generate_payment_methods_list( $payment_breakdown = array(), $include_icons = true ) {

			ob_start();

			if ( empty( $payment_breakdown ) ) {
				?>

			<li class="none">
				<div class="icon">
					<span></span>
				</div>
				<div class="info">
					<span><?php echo esc_html__( 'No payments.', 'charitable' ); ?></span>
				</div>
				<div class="total">
					<span>-</span>
				</div>
			</li>

				<?php
			} else {

				foreach ( $payment_breakdown['donations'] as $payment_method => $data ) :

					$percentage = intval( $data['donors'] ) / $payment_breakdown['donations_total'] * 100;
					// round to nearest .1 number.
					$percentage = round( $percentage, 1 );

					$title = ! empty( $data['title'] ) ? 'title="' . esc_html( $data['title'] ) . '"' : false;

					?>
			<li class="<?php echo esc_attr( $payment_method ); ?>">
					<?php if ( $include_icons ) : ?>
				<div class="icon">
					<span <?php echo $title; // phpcs:ignore ?>></span>
				</div>
				<?php endif; ?>
				<div class="info">
					<span <?php echo $title; // phpcs:ignore ?>><?php echo esc_html( $data['label'] ); ?></span>
				</div>
				<div class="total">
					<span><?php echo esc_html( $percentage ); ?>%</span>
				</div>
					<?php
					if ( ! $include_icons ) :
						?>
						<br class="clear" /><?php endif; ?>
			</li>
					<?php
			endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Generates HTML rows for donation breakdown table.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donation_data The donation data.
		 * @param  array $refunds_data The refunds data.
		 *
		 * @return string
		 */
		public function generate_donations_breakdown_rows( $donation_data = array(), $refunds_data = array() ) {

			ob_start();

			if ( empty( $donation_data ) ) {
				?>

				<td colspan="5"><strong><?php echo esc_html__( 'There are no donations within the date range.', 'charitable' ); ?></strong></td>

				<?php
			} else {

				foreach ( $donation_data as $day => $data ) :

					$net          = 0;
					$refund_count = 0;
					$css_class    = false;
					$prefix       = false;
					$row_id       = strtotime( $day );

					$net           = $data['amount'] - $refunds_data['refunds_by_day'][ $day ]['amount'];
					$prefix        = intval( $net ) < 0 ? '-' : '+';
					$css_class     = intval( $net ) < 0 ? 'negative' : 'positive';
					$css_class     = intval( $net ) === 0 ? 'zero' : $css_class;
					$refund_count  = intval( $refunds_data['refunds_by_day'][ $day ]['donors'] );
					$refund_amount = $refunds_data['refunds_by_day'][ $day ]['amount'];

					?>

					<tr id="type-donations-donor-<?php echo intval( $row_id ); ?>" class="row-type-donation-breakdown">
						<td class="donated column-date" data-colname="Date"><?php echo esc_html( $data['label'] ); ?></td>
						<td class="donated column-donations" data-colname="Donations"><?php echo charitable_format_money( esc_html( $data['amount'] ) ); // phpcs:ignore ?></td>
						<td class="donated column-donors" data-colname="Number of Donors"><?php echo esc_html( $data['donors'] ); ?></td>
						<td class="donated column-refunds" data-colname="Refunds"><?php echo charitable_format_money( ( $refund_amount ) ); // phpcs:ignore ?></td>
						<td class="donated column-net <?php echo $css_class; ?>" data-colname="Net"><?php echo $prefix . charitable_format_money( esc_html( $net ) ); // phpcs:ignore ?></td>
					</tr>

					<?php
				endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Generates meta info (title, column names) for the report table.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 *
		 * @return array
		 */
		public function get_donor_report_table_meta( $report_type = 'donors-top' ) {

			$table_meta = array();

			switch ( $report_type ) {
				case 'donors-top':
					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'           => '',
							'name-email'       => esc_html__( 'Name', 'charitable' ),
							'total-donations'  => esc_html__( 'Total Donations', 'charitable' ),
							'number-donations' => esc_html__( 'Number of Donations', 'charitable' ),
							'average'          => esc_html__( 'Average', 'charitable' ),
							'number-campaigns' => esc_html__( 'Number of Campaigns', 'charitable' ),
							'last-donation'    => esc_html__( 'Last Donation', 'charitable' ),
							'actions'          => esc_html__( 'Actions', 'charitable' ),
						),
					);
					break;

				case 'donors-recurring':
					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'           => '',
							'name-email'       => esc_html__( 'Name', 'charitable' ),
							'total-donations'  => esc_html__( 'Total Donations', 'charitable' ),
							'number-donations' => esc_html__( 'Number of Donations', 'charitable' ),
							'average'          => esc_html__( 'Average', 'charitable' ),
							'number-campaigns' => esc_html__( 'Number of Campaigns', 'charitable' ),
							'last-donation'    => esc_html__( 'Last Donation', 'charitable' ),
							'actions'          => esc_html__( 'Actions', 'charitable' ),
						),
					);
					break;

				case 'donors-first-time':
					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'          => '',
							'name-email'      => esc_html__( 'Name', 'charitable' ),
							'total-donations' => esc_html__( 'Donation', 'charitable' ),
							'last-donation'   => esc_html__( 'Campaign', 'charitable' ),
							'date'            => esc_html__( 'Date', 'charitable' ),
							'actions'         => esc_html__( 'Actions', 'charitable' ),
						),
					);
					break;

				default:
					// code...
					break;
			}

			return $table_meta;
		}

		/**
		 * Generates HTML for the donor breakdown table on the overview page.
		 *
		 * @since  1.8.1
		 * @since  1.8.3 Added $limit_per_page parameter and generate_pagination_html().
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $donor_breakdown The donor breakdown data.
		 * @param  int    $current_page The current page.
		 * @param  int    $limit_per_page The limit per page.
		 *
		 * @return string
		 */
		public function generate_donor_breakdown_table_html( $report_type = 'donors-top', $donor_breakdown = array(), $current_page = 1, $limit_per_page = 10 ) {

			$table_meta = $this->get_donor_report_table_meta( $report_type );

			ob_start();

			?>

				<div class="alignleft actions">

					<h2><?php echo esc_html( $table_meta['title'] ); ?></h2>

				</div>
				<div class="alignright">

					<?php if ( ! charitable_is_pro() ) : ?>

						<button disabled="disabled" value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>

					<?php else : ?>

					<form action="" method="post" class="charitable-report-donor-form" id="charitable-donor-download-form">
						<input name="charitable_report_action" type="hidden" value="charitable_report_download_donors">
						<input name="report_type" type="hidden" value="<?php echo esc_attr( $report_type ); ?>">
						<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
						<button value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>
					</form>

					<?php endif; ?>

				</div>

				<br class="clear">

				<div class="charitable-report-table-container">

					<?php do_action( 'charitable_report_before_donor_table' ); ?>

					<?php
					if ( ! charitable_is_pro() ) :
						?>
						<div class="charitable-restricted"><div class="restricted-access-overlay"></div><?php endif; ?>

					<table class="wp-list-table widefat fixed striped table-view-list donor-breakdown charitable-report-ui">
						<thead>
							<tr>
								<?php
								foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
									if ( $column_slug === 'avatar' ) {
										echo '<th style="width: 32px;" class="charitable-avatar"></th>';
									} else {
										echo '<th scope="col" id="' . esc_attr( $column_slug ) . '" class="manage-column column-' . esc_attr( $column_slug ) . '"><span>' . esc_html( $column_title ) . '</span></th>';
									}
								endforeach;
								?>
							</tr>
						</thead>
						<tbody id="donations-breakdown-list">
							<?php echo $this->generate_donor_breakdown_rows( $donor_breakdown, $report_type ); // phpcs:ignore ?>
						</tbody>
						<tfoot>
							<tr>
								<?php
								foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
									if ( $column_slug === 'avatar' ) {
										echo '<th style="width: 32px;" class="charitable-avatar"></th>';
									} else {
										echo '<th scope="col" id="' . esc_html( $column_slug ) . '" class="manage-column column-' . esc_attr( $column_slug ) . '"><span>' . esc_html( $column_title ) . '</span></th>';
									}
								endforeach;
								?>
							</tr>
						</tfoot>
					</table>

					<?php
					if ( ! charitable_is_pro() ) :
						?>
						</div><?php endif; ?>

					<?php do_action( 'charitable_report_after_donor_table' ); ?>

					<br class="clear">

					<?php echo $this->generate_pagination_html( $limit_per_page, $current_page, $report_type ); // phpcs:ignore ?>

					<?php do_action( 'charitable_report_after_donor_table_pagination' ); ?>

				</div>

			<?php

			return ob_get_clean();
		}

		/**
		 * Generates HTML for the donation breakdown table on the overview page.
		 *
		 * @since  1.8.1
		 *
		 * @param array  $donor_data The donor data.
		 * @param string $report_type The report type.
		 *
		 * @return string
		 */
		public function generate_donor_breakdown_rows( $donor_data = array(), $report_type = 'donors-top' ) {

			$suffix = ( charitable_is_debug() || charitable_is_script_debug() ) ? '#charitabledebug' : false;

			ob_start();

			if ( empty( $donor_data ) ) {
				?>

				<td colspan="5"><strong><?php echo esc_html__( 'There are no donors found.', 'charitable' ); ?></strong></td>

				<?php

			} else {

				foreach ( $donor_data as $index => $data ) :

					$donor_id               = isset( $data->donor_id ) ? intval( $data->donor_id ) : 0;
					$donor_email            = isset( $data->email ) ? esc_html( $data->email ) : '';
					$donor_avatar           = ( is_email( $donor_email ) ) ? get_avatar_url( $donor_email ) : charitable()->get_path( 'assets', false ) . '/images/misc/placeholder-avatar-small.jpg';
					$donor_name             = esc_html( $data->first_name ) . ' ' . esc_html( $data->last_name );
					$donor_number_donations = isset( $data->total_count_donations ) ? intval( $data->total_count_donations ) : 0;
					$donor_number_campaigns = isset( $data->total_count_campaigns ) ? intval( $data->total_count_campaigns ) : 0;
					$donor_average          = $donor_number_donations > 0 ? $data->total_amount / $donor_number_donations : 0;
					$donation_id            = isset( $data->donation_id ) ? intval( $data->donation_id ) : 0;
					$is_sample_data         = isset( $data->sample ) ? true : false;

					// date format: M j, Y plus time of day in am/pm.
					$last_donation_date          = $data->last_donation_date ? gmdate( 'M j, Y g:i a', strtotime( $data->last_donation_date ) ) : 'N/A';
					$last_donation_campaign_name = $data->last_campaign_title ? esc_html( $data->last_campaign_title ) : 'N/A';
					$last_donation_campaign_id   = $data->last_campaign_id ? intval( $data->last_campaign_id ) : 0;

					$action_link  = '#';
					$action_label = false;

					// Determine the (view) link.
					if ( 'donors-first-time' === $report_type && $donation_id > 0 ) {

						$action_link  = ( $donor_id !== 0 ) ? admin_url( 'post.php?post=' . $donation_id . '&action=edit' ) : '#';
						$action_label = esc_html__( 'View Donation', 'charitable' );

					} elseif ( $last_donation_campaign_id > 0 ) {

						// Does this post exist?
						if ( false === get_post_status( $last_donation_campaign_id ) ) {

							$action_link = '#';

						} else {

							// Determine if this is a "legacy" campaign.
							$is_legacy_campaign = charitable_is_campaign_legacy( $last_donation_campaign_id );

							$action_link  = $is_legacy_campaign ? admin_url( 'post.php?post=' . $last_donation_campaign_id . '&action=edit' ) : admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . $last_donation_campaign_id ) . $suffix;
							$action_label = 'donors-top' === $report_type ? esc_html__( 'View Last Donated Campaign', 'charitable' ) : esc_html__( 'View Campaign', 'charitable' );

						}
					}

					if ( 'donors-first-time' === $report_type ) {
						if ( $last_donation_campaign_name && $last_donation_campaign_id ) {
							$last_donation_html = '<a href="' . $action_link . '" target="_blank">' . $last_donation_campaign_name . '</a>';
						} elseif ( $is_sample_data ) {
							$last_donation_html = $last_donation_campaign_name;
						}
					} elseif ( $last_donation_date && $last_donation_campaign_name && $last_donation_campaign_id ) {
						$last_donation_html = charitable_format_money( esc_html( $data->amount ), 2, true ) . '<br/><a href="' . $action_link . '" target="_blank">' . $last_donation_campaign_name . '</a><br/>' . $last_donation_date;
					} elseif ( $is_sample_data ) {
						$last_donation_html = charitable_format_money( esc_html( $data->amount ), 2, true ) . '<br/>' . $last_donation_campaign_name . '<br/>' . $last_donation_date;
					}

					if ( $report_type === 'donors-first-time' ) {
						?>

					<tr id="type-<?php echo esc_attr( $report_type ); ?>-donor-<?php echo intval( $donor_id ); ?>" class="type-<?php echo esc_attr( $report_type ); ?>">
						<td class="charitable-avatar" style="width: 32px;"><img src="<?php echo esc_url( $donor_avatar ); ?>" class="avatar avatar-32 photo charitable-avatar-donor-<?php echo intval( $donor_id ); ?>" width="32" height="32" alt="<?php echo esc_html__( 'Profile picture of', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?>"></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Name / Email', 'charitable' ); ?>"><?php echo esc_html( $donor_name ); ?><br/><?php echo esc_html( $donor_email ); ?></td>
						<td class="donated column-donation-amount" data-colname="<?php echo esc_html__( 'Donation Amount', 'charitable' ); ?>"><?php echo charitable_format_money( esc_html( $data->total_amount ), 2, true ); // phpcs:ignore ?></td>
						<td class="donated column-campaign" data-colname="<?php echo esc_html__( 'Campaign', 'charitable' ); ?>"><?php echo $last_donation_html; // phpcs:ignore ?></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Date', 'charitable' ); ?>"><?php echo esc_html( $last_donation_date ); ?></td>
						<?php
						$display = '<td class="donated column-actions " data-colname="Actions">';
						if ( $action_label !== false ) {
							$display .= '<a class="charitable-campaign-action-button" title="' . $action_label . '" href="' . esc_url( $action_link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/eye.svg" width="14" height="14" alt="' . $action_label . '" /></a>';
						}
							$display .= '</td>';

						echo $display; // phpcs:ignore
						?>
					</tr>

					<?php } else { ?>

						<tr id="type-<?php echo esc_attr( $report_type ); ?>-donor-<?php echo intval( $donor_id ); ?>" class="type-<?php echo esc_attr( $report_type ); ?>">
						<td class="charitable-avatar" style="width: 32px;"><img src="<?php echo esc_url( $donor_avatar ); ?>" class="avatar avatar-32 photo charitable-avatar-donor-<?php echo intval( $donor_id ); ?>" width="32" height="32" alt="<?php echo esc_html__( 'Profile picture of', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?>"></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Name / Email', 'charitable' ); ?>"><?php echo esc_html( $donor_name ); ?><br/><?php echo esc_html( $donor_email ); ?></td>
						<td class="donated column-total-donations" data-colname="<?php echo esc_html__( 'Total Donations', 'charitable' ); ?>"><?php echo charitable_format_money( esc_html( $data->total_amount ), 2, true ); // phpcs:ignore ?></td>
						<td class="donated column-number-donations" data-colname="Number of Donations"><?php echo esc_html( $donor_number_donations ); ?></td>
						<td class="donated column-average" data-colname="Average"><?php echo charitable_format_money( $donor_average, 2, true ); // phpcs:ignore ?></td>
						<td class="donated column-number-campaigns" data-colname="Number of Campaigns"><?php echo esc_html( $donor_number_campaigns ); ?></td>
						<td class="donated column-last-donation" data-colname="Last Donation"><?php echo $last_donation_html; // phpcs:ignore ?></td>
							<?php
							$display = '<td class="donated column-actions" data-colname="Actions">';
							if ( $action_label !== false ) {
								$display .= '<a class="charitable-campaign-action-button" title="' . $action_label . '" href="' . esc_url( $action_link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/eye.svg" width="14" height="14" alt="' . $action_label . '" /</a>';
							}
							$display .= '</td>';

						echo $display; // phpcs:ignore
							?>
					</tr>

							<?php
					}

				endforeach;

			}

			return ob_get_clean();
		}

		/**
		 * Enqueue assets and write JS vars for the report.
		 *
		 * @since 1.8.1
		 *
		 * @return void
		 */
		public function report_vars() {

			$localized_strings = $this->get_localized_strings();

			?>
			<script id="charitable-report-data-js">
				var charitable_reporting = <?php echo wp_json_encode( $localized_strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
			</script>

			<?php
		}

		/**
		 * Get the donations stored in the class.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		public function get_donations() {
			return $this->donations;
		}

		/**
		 * Get the donations stored in the class, and return sorted/organized by payment method.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		public function get_donations_by_payment() {
			if ( empty( $this->donations ) ) {
				return;
			}

			// setup array so that it holds all the days in the date range.
			$donations_by_payment = apply_filters(
				'charitable_reports_default_payment_types',
				array(
					'stripe'  => array(
						'label'  => esc_html__( 'Stripe', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations made through the Stripe gateway.', 'charitable' ),
					),
					'paypal'  => array(
						'label'  => esc_html__( 'PayPal', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations made through the PayPal gateway.', 'charitable' ),
					),
					'manual'  => array(
						'label'  => esc_html__( 'Manual', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations manually entered into the WordPress admin.', 'charitable' ),
					),
					'offline' => array(
						'label'  => esc_html__( 'Offline', 'charitable' ),
						'amount' => 0,
						'donors' => 0,
						'title'  => esc_html__( 'Donations made outside of the website.', 'charitable' ),
					),
				)
			);

			// Go through the array and group the amounts and number of donors by day.
			foreach ( $this->donations as $key => $donation ) {

				$payment = ( ! empty( $donation->payment_gateway ) ? strtolower( esc_attr( $donation->payment_gateway ) ) : $payment = 'unknown' );

				// Get the proper label from the gateway object if available.
				$label = '';
				$gateways = charitable_get_helper( 'gateways' );
				if ( $gateways && $gateways->is_valid_gateway( $payment ) ) {
					$gateway_object = $gateways->get_gateway_object( $payment );
					if ( $gateway_object ) {
						$label = $gateway_object->get_label();
					}
				}

				// Fallback to manual label generation if gateway object is not available.
				if ( empty( $label ) ) {
					// cap the P in Paypal manually.
					$label = ( 'paypal' === strtolower( $donation->payment_gateway ) ) ? 'PayPal' : ucwords( str_replace( '_', ' ', $donation->payment_gateway ) );
				}

				if ( isset( $donations_by_payment[ $payment ] ) ) {
					// Update the label if it wasn't set in the default array.
					if ( ! empty( $label ) ) {
						$donations_by_payment[ $payment ]['label'] = esc_html( $label );
					}
					$donations_by_payment[ $payment ]['amount'] += $donation->total_amount;
					$donations_by_payment[ $payment ]['donors'] += 1;
				} else {
					$donations_by_payment[ $payment ]['label']  = esc_html( $label );
					$donations_by_payment[ $payment ]['amount'] = $donation->total_amount;
					$donations_by_payment[ $payment ]['donors'] = 1;
					$donations_by_payment[ $payment ]['title']  = $donation->title;
				}
			}

			$this->payment_percentages = array();
			$this->payment_labels      = array();
			$this->payment_keys        = array();

			foreach ( $donations_by_payment as $key => $donation ) {

				$percentage = intval( $donation['donors'] ) / count( $this->donations ) * 100;
				// round to nearest .1 number.
				$percentage = round( $percentage, 1 );

				$this->payment_percentages[] = $percentage;
				$this->payment_labels[]      = $donation['label']; // . '% (' . charitable_format_money( $donation['amount'] ) . ')';
				$this->payment_keys[]        = $key; // Store the payment gateway key for color mapping.

			}

			if ( empty( $this->payment_labels ) && empty( $this->payment_percentages ) ) {
				// no donations were likely found so let's add the "not found" data.
				$this->payment_percentages[] = 100;
				$this->payment_labels[]      = __( 'No donations found.', 'charitable' );
				$this->payment_keys[]        = 'none';
			} else {
				$this->payment_percentages[] = 0;
				$this->payment_labels[]      = '';
				$this->payment_keys[]        = '';
			}

			$donations_by_payment = apply_filters( 'charitable_reports_donations_by_payment_data', $donations_by_payment );

			return array(
				'donations'       => $donations_by_payment,
				'donations_total' => count( $this->donations ),
			);
		}


		/**
		 * Get unique donor count from donations.
		 *
		 * @since 1.8.1
		 *
		 * @return int
		 */
		public function get_donors_count() {
			if ( empty( $this->donations ) ) {
				return 0;
			}

			// Go through multi-dimenssional array and get a count of all unique values of donor_id.
			$donor_ids      = array();
			$donation_total = 0;

			foreach ( $this->donations as $donation ) {
				// Add the donor ID to the array.
				$donor_ids[] = $donation->donor_id;
				// Add the donation amount to the total.
				$donation_total += $donation->total_amount;
			}

			return ( count( array_unique( $donor_ids ) ) );
		}

		/**
		 * Get donation amount total from donations.
		 *
		 * @since 1.8.1
		 *
		 * @return float
		 */
		public function get_donations_total() {
			if ( empty( $this->donations ) ) {
				return 0;
			}

			// Go through multi-dimenssional array and get a count of all unique values of donor_id.
			$donation_total = 0;

			foreach ( $this->donations as $donation ) {
				// Add the donation amount to the total.
				$donation_total += $donation->total_amount;
			}

			return ( (float) $donation_total );
		}

		/**
		 * Get a breakdown of donations per day based on the start and end dates.
		 *
		 * @since 1.8.1
		 *
		 * @param  bool $format_money Whether to format the money (say for downloadable reports ).
		 *
		 * @return array
		 */
		public function get_donations_by_day( $format_money = false ) {
			if ( empty( $this->donations ) ) {
				return;
			}

			$campaign_donations_table = charitable_get_table( 'campaign_donations' );

			// setup array so that it holds all the days in the date range.
			$donations_by_day  = array();
			$days              = $this->get_days_between_dates( $this->start_date, $this->end_date );
			$refunded_statuses = array( 'charitable-refunded' );

			foreach ( $days as $day_unformatted => $day_label ) {

				$donation_today_completed = $campaign_donations_table->get_donations_summary_by_period( gmdate( 'Y-m-d', strtotime( $day_unformatted ) ), array( 'charitable-completed', 'charitable-refunded' ) );
				$donation_today_refunded  = $campaign_donations_table->get_donations_summary_by_period( gmdate( 'Y-m-d', strtotime( $day_unformatted ) ), $refunded_statuses );

				$donation_day                                 = gmdate( 'Y-m-d', strtotime( $day_unformatted ) );
				$donations_by_day[ $donation_day ]['label']   = gmdate( 'F d, Y', strtotime( $day_unformatted ) );
				$donations_by_day[ $donation_day ]['amount']  = $donation_today_completed->amount;
				$donations_by_day[ $donation_day ]['donors']  = 0;
				$donations_by_day[ $donation_day ]['refunds'] = $donation_today_refunded->amount;
				$donations_by_day[ $donation_day ]['net']     = 0;
			}

			// Go through the array and group the amounts and number of donors by day.
			foreach ( $this->donations as $donation ) {

				$donation_day = gmdate( 'Y-m-d', strtotime( $donation->post_date ) );

				if ( ! empty( $donation->email ) ) {
					if ( isset( $unique_donors[ $donation_day ][ $donation->email ] ) ) {
						$unique_donors[ $donation_day ][ $donation->email ] = $unique_donors[ $donation_day ][ $donation->email ] + 1;
					} else {
						$unique_donors[ $donation_day ][ $donation->email ] = 1;
					}
				}

				if ( isset( $donations_by_day[ $donation_day ] ) ) {
					$donations_by_day[ $donation_day ]['label'] = gmdate( 'F d, Y', strtotime( $donation->post_date ) );
					// $donations_by_day[ $donation_day ]['refunds'] += in_array( $donation->donation_status, $refunded_statuses, true ) ? $donation->total_amount : 0;
				} else {
					$donations_by_day[ $donation_day ]['label'] = gmdate( 'F d, Y', strtotime( $donation->post_date ) );
					// $donations_by_day[ $donation_day ]['refunds'] = in_array( $donation->donation_status, $refunded_statuses, true ) ? $donation->total_amount : 0;
				}
			}

			// Go through the array and (1) add the net values now that the totals are calculated (2) format amount and refunds (3) add unique donors.
			foreach ( $donations_by_day as $day => $data ) {

				$donations_by_day[ $day ]['net']    = $this->get_net( $data['amount'], $data['refunds'] );
				$donations_by_day[ $day ]['donors'] = ! empty( $unique_donors[ $day ] ) ? count( $unique_donors[ $day ] ) : 0;

				if ( $format_money ) :
					$donations_by_day[ $day ]['amount']  = $this->charitable_reports_format_money( $data['amount'] );
					$donations_by_day[ $day ]['refunds'] = $this->charitable_reports_format_money( $data['refunds'] );
					$donations_by_day[ $day ]['net']     = $this->charitable_reports_format_money( $donations_by_day[ $day ]['net'] );
				endif;
			}

			// reverse array.
			$donations_by_day = array_reverse( $donations_by_day );

			return $donations_by_day;
		}

		/**
		 * Get the total amount of refunds from the stored activities.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @param  int $limit The limit of activities to get.
		 *
		 * @return float
		 */
		public function get_activity_refund_total( $campaign_id = false, $limit = false ) {

			if ( $this->is_activities_empty() ) {
				$this->activities = $this->get_activity( $campaign_id, $limit );
			}

			if ( $this->is_activities_empty() ) {
				return 0;
			}

			$refunded_total = 0;

			foreach ( $this->activities as $activity ) {
				if ( 'refunded' === $activity->status ) {
					$refunded_total += $activity->amount;
				}
			}

			return $refunded_total;
		}

		/**
		 * Get the total count of refunds from the stored activities.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @param  int $limit The limit of activities to get.
		 *
		 * @return int
		 */
		public function get_activity_refund_count( $campaign_id = false, $limit = false ) {

			if ( $this->is_activities_empty() ) {
				$this->activities = $this->get_activity( $campaign_id, $limit );
			}

			if ( $this->is_activities_empty() ) {
				return 0;
			}

			$refunded_count = 0;

			foreach ( $this->activities as $activity ) {
				if ( 'refunded' === $activity->status ) {
					++$refunded_count;
				}
			}

			return $refunded_count;
		}

		/**
		 * Get activity for the report.
		 *
		 * @since 1.8.1
		 *
		 * @param array $args The array of args.
		 *
		 * @return array
		 */
		public function get_activity( $args = array() ) {

			$defaults = array(
				'activity_filter_types' => array(),
				'activity_action_types' => array(),
				'start_date'            => false,
				'end_date'              => false,
				'status'                => false,
				'campaign_id'           => false,
				'category_id'           => false,
				'limit'                 => false,
				'offset'                => false,
				'ppage'                 => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$activity_filter_types = $args['activity_filter_types'] ?? array();
			$activity_action_types = $args['activity_action_types'] ?? array();
			$start_date            = $args['start_date'] ?? false;
			$end_date              = $args['end_date'] ?? false;
			$status                = $args['status'] ?? false;
			$campaign_id           = $args['campaign_id'] ?? false;
			$category_id           = $args['category_id'] ?? false;
			$limit                 = $args['limit'] ?? false;
			$offset                = $args['offset'] ?? false;
			$ppage                 = $args['ppage'] ?? false;

			if ( ! class_exists( 'Charitable_Admin_Activities' ) || ! class_exists( 'Charitable_Donation_Activities_DB' ) || ! class_exists( 'Charitable_Campaign_Activities_DB' ) ) {
				return;
			}

			$charitable_donation_activities_db = Charitable_Donation_Activities_DB::get_instance();

			// check to see if db tables exist.
			if ( ! $charitable_donation_activities_db->table_exists() ) {
				return;
			}

			$charitable_campaign_activities_db = Charitable_Campaign_Activities_DB::get_instance();

			// check to see if db tables exist.
			if ( ! $charitable_campaign_activities_db->table_exists() ) {
				return;
			}

			// If not passed, grab from the local vars in the class.
			$campaign_id = $campaign_id ? $campaign_id : $this->campaign_id;
			$category_id = absint( $category_id ) > 0 ? $category_id : $this->category_id;

			$admin_activities = new Charitable_Admin_Activities();

			// Get the "raw" activities data.
			$activities = $admin_activities->get_activity(
				array(
					'activity_filter_types' => $activity_filter_types,
					'activity_action_types' => $activity_action_types,
					'campaign_id'           => $campaign_id,
					'category_id'           => $category_id,
					'start_date'            => $this->start_date,
					'end_date'              => $this->end_date,
					'limit'                 => $limit,
					'offset'                => $offset,
				)
			);

			// Get the more "formatted" (with additional data) reports data.
			$report_activities = $admin_activities->get_activity_report_data( $activities );

			return $report_activities;
		}

		/**
		 * Generate Pagination HTML
		 *
		 * @since 1.8.1
		 *
		 * @param  int   $total_pages The total number of pages.
		 * @param  int   $current_page The current page.
		 * @param  int   $total_records Total number of records.
		 * @param  int   $offset The offset.
		 * @param  int   $limit The limit.
		 * @param  array $arr_params The params for the pagination links, which might depend on report type.
		 * @param  int   $dot_threshold The dot threshold.
		 *
		 * @return void
		 */
		public function show_pagination( $total_pages = 1, $current_page = 1, $total_records = false, $offset = false, $limit = 10, $arr_params = array(), $dot_threshold = false ) {

			// Only display if pro.
			if ( ! charitable_is_pro() ) {
				return;
			}

			if ( false === $dot_threshold ) {
				$dot_threshold = $this->pagination_dot_threshold;
			}

			$total_pages = intval( $total_pages );

			echo '<nav class="charitable-reports-pagination-nav">';

			if ( $total_records ) :
				// translators: %d: total number of records.
				echo '<div class="page-item total-count"><span class="page-total">' . sprintf( esc_html__( 'Total: %d', 'charitable' ), intval( $total_records ) ) . '</span></div>';
			endif;

			// If there are no pages, don't display anything further.
			if ( ! $total_pages || $total_pages <= 1 ) {
				echo '</nav>';
				return;
			}

			echo '<ul>';

			if ( intval( $total_pages ) > intval( $dot_threshold ) ) {
				if ( intval( $current_page ) === 1 ) {
					echo '<li class="page-item disabled"><span class="page-link">‹</span></li>';
					echo '<li class="page-item active" aria-current="page"><span class="page-link">1</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) - 1, $arr_params ) ) . '">‹</a></li>';
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( 1 ) ) . '">1</a></li>';
				}

				if ( $total_pages - $current_page > 3 ) {
					if ( intval( $current_page ) > 4 ) {
						echo '<li class="page-item"><span class="page-link">...</span></li>';
						echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) - 1, $arr_params ) ) . '">' . ( intval( $current_page ) - 1 ) . '</a></li>';
						echo '<li class="page-item active" aria-current="page"><span class="page-link">' . ( intval( $current_page ) ) . '</span></li>';
						echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) + 1, $arr_params ) ) . '">' . ( intval( $current_page ) + 1 ) . '</a></li>';
					} else {
						for ( $page_no = 2; $page_no <= 5; $page_no++ ) {
							if ( intval( $current_page ) === $page_no ) {
								echo '<li class="page-item active" aria-current="page"><span class="page-link">' . ( intval( $page_no ) ) . '</span></li>';
							} else {
								echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( $page_no, $arr_params ) ) . '">' . intval( $page_no ) . '</a></li>';
							}
						}
					}
				}

				if ( $total_pages - $current_page < 4 ) {
					echo '<li class="page-item"><span class="page-link">...</span></li>';
					for ( $page_no = $total_pages - 4; $page_no <= $total_pages - 1; $page_no++ ) {
						if ( intval( $current_page ) === $page_no ) {
							echo '<li class="page-item active" aria-current="page"><span class="page-link">' . intval( $page_no ) . '</span></li>';
						} else {
							echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( $page_no, $arr_params ) ) . '">' . intval( $page_no ) . '</a></li>';
						}
					}
				} else {
					echo '<li class="page-item"><span class="page-link">...</span></li>';
				}

				if ( intval( $current_page ) === intval( $total_pages ) ) {
					echo '<li class="page-item active" aria-current="page"><span class="page-link">' . ( intval( $total_pages ) ) . '</span></li>';
					echo '<li class="page-item disabled"><span class="page-link">›</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( $total_pages, $arr_params ) ) . '">' . ( esc_html( $total_pages ) ) . '</a></li>';
					echo '<li class="page-item"><a class="page-link" href="' . esc_url( $this->get_pagination_url( intval( $current_page ) + 1, $arr_params ) ) . '">›</a></li>';
				}
			} else {
				if ( intval( $current_page ) === 1 ) {
					echo '<li class="page-item disabled"><span class="page-link">‹</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" ' . $this->get_pagination_data_attributes( $total_pages, $current_page, $total_records, $offset, intval( $current_page - 1 ), $limit, $arr_params ) . ' href="' . esc_url( $this->get_pagination_url( intval( $current_page ) - 1, $arr_params ) ) . '">‹</a></li>'; // phpcs:ignore
				}
				for ( $page_no = 1; $page_no <= $total_pages; $page_no++ ) {
					if ( intval( $current_page ) === $page_no ) {
						echo '<li class="page-item active" aria-current="page"><span class="page-link">' . intval( $page_no ) . '</span></li>';
					} else {
						echo '<li class="page-item"><a class="page-link" ' . $this->get_pagination_data_attributes( $total_pages, $current_page, $total_records, $offset, $page_no, $limit, $arr_params ) . ' href="' . esc_url( $this->get_pagination_url( $page_no, $arr_params ) ) . '">' . intval( $page_no ) . '</a></li>'; // phpcs:ignore
					}
				}
				if ( intval( $current_page ) === intval( $total_pages ) ) {
					echo '<li class="page-item disabled"><span class="page-link">›</span></li>';
				} else {
					echo '<li class="page-item"><a class="page-link" ' . $this->get_pagination_data_attributes( $total_pages, $current_page, $total_records, $offset, intval( $current_page + 1 ), $limit, $arr_params ) . ' href="' . esc_url( $this->get_pagination_url( intval( $current_page ) + 1, $arr_params ) ) . '">›</a></li>'; // phpcs:ignore
				}
			}

			echo '</ul></nav>';
		}

		/**
		 * Get link for the charitable pagination
		 *
		 * @since 1.8.1.3
		 *
		 * @param  string $total_pages Current page number.
		 * @param  string $current_page Current page.
		 * @param  string $total_records Total number of records.
		 * @param  string $offset The offset.
		 * @param  string $ppage The ppage.
		 * @param  string $limit The limit.
		 * @param  array  $arr_params Params to build the attributes.
		 *
		 * @return string
		 */
		public function get_pagination_data_attributes( $total_pages = '', $current_page = '', $total_records = 0, $offset = 0, $ppage = 1, $limit = 10, $arr_params = array() ) {

			$data_attributes = array(
				'total-pages'   => $total_pages,
				'current-page'  => $current_page,
				'total-records' => $total_records,
				// 'offset'        => $offset,
				'ppage'         => $ppage, // a page in the results.
				'limit'         => $limit, // the limit of items per page.
			);

			if ( ! empty( $arr_params ) ) {
				foreach ( $arr_params as $key => $value ) {
					$data_attributes[ sanitize_title( $key ) ] = $value;
				}
			}

			// create a string of data attributes for an HTML element based on $data_attributes.
			$data_attributes_string = '';
			foreach ( $data_attributes as $key => $value ) {
				$data_attributes_string .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}

			return $data_attributes_string;
		}

		/**
		 * Get link for the charitable pagination
		 *
		 * @since 1.8.1
		 *
		 * @param  string $page_number Current page number.
		 * @param  array  $arr_params Params to build the URL.
		 *
		 * @return array
		 */
		public function get_pagination_url( $page_number, $arr_params = array() ) {

			$arr_params['ppage'] = $page_number;

			return add_query_arg( $arr_params, admin_url( 'admin.php' ) );
		}

		/**
		 * Generate the HTML for the pagination.
		 *
		 * @since 1.8.1
		 *
		 * @param  int    $limit The limit of items per page.
		 * @param  int    $current_page The (current) page number.
		 * @param  string $report_type The report type.
		 *
		 * @return float
		 */
		public function generate_pagination_html( $limit = 10, $current_page = 1, $report_type = 'donors-top' ) {

			$donors = (array) $this->donors;

			$limit = charitable_reports_is_ajax() && isset( $_POST['limit'] ) && is_numeric( $_POST['limit'] ) ? intval( $_POST['limit'] ) : $limit; // phpcs:ignore
			$limit = false !== $limit && is_numeric( $limit ) ? $limit : charitable_reports_get_pagination_per_page();

			$current_page = charitable_reports_is_ajax() && isset( $_POST['ppage'] ) && is_numeric( $_POST['ppage'] ) ? intval( $_POST['ppage'] ) : $current_page; // phpcs:ignore
			$current_page = is_numeric( $current_page ) ? $current_page : 1;

			$pagination = array(
				'length'      => isset( $_GET['limit'] ) ? (int) $_GET['limit'] : $limit, // phpcs:ignore
				'total'       => 0 !== $this->donors_total ? $this->donors_total : count( $donors ),
				'currentPage' => isset( $_GET['ppage'] ) ? (int) $_GET['ppage'] : $current_page, // phpcs:ignore
			);

			$pagination['total_pages'] = ceil( $pagination['total'] / $pagination['length'] );
			$pagination['offset']      = ( $pagination['currentPage'] * $pagination['length'] ) - $pagination['length'];

			// The params for the pagination links, which might depend on report type.
			$arr_params = array(
				'page'        => 'charitable-reports',
				'tab'         => 'donors',
				'report-type' => isset( $_GET['report_type'] ) ? sanitize_text_field( wp_unslash( $_GET['report_type'] ) ) : $report_type, // phpcs:ignore
			);

			ob_start();

			$this->show_pagination( $pagination['total_pages'], $pagination['currentPage'], $pagination['total'], $pagination['offset'], $pagination['length'], $arr_params );

			return ob_get_clean();
		}

		/**
		 * Get the activity icon url from a status string.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $icon The icon name.
		 *
		 * @return string
		 */
		public function get_activity_icon_url( $icon = false ) {

			if ( false === $icon || '' === $icon ) {
				return false;
			}

			return charitable()->get_path( 'directory', false ) . 'assets/images/icons/activities/' . $icon . '.png';
		}

		/**
		 * Get the top donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 * @param  int $offset The offset of donors to get.
		 *
		 * @return string
		 */
		public function get_top_donors( $limit = false, $offset = false ) { // phpcs:ignore
			if ( empty( $this->donors ) ) {
				return;
			}

			return $this->donors;
		}

		/**
		 * Get the top donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 *
		 * @return string
		 */
		public function get_recurring_donors( $limit = false ) { // phpcs:ignore
			if ( empty( $this->donors ) ) {
				return;
			}

			return $this->donors;
		}

		/**
		 * Get the first time donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 *
		 * @return string
		 */
		public function get_first_time_donors( $limit = false ) { // phpcs:ignore
			if ( empty( $this->donors ) ) {
				return;
			}

			return $this->donors;
		}

		/**
		 * Get the donors for the dedicated donor report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $args The array of args.
		 *
		 * @return array|bool
		 */
		public function get_donor_report_by_type( $report_type = false, $args = array() ) {

			$defaults = array(
				'limit'  => false,
				'offset' => false,
				'ppage'  => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$limit  = $args['limit'] ?? false;
			$offset = $args['offset'] ?? false;
			$ppage  = $args['ppage'] ?? false;

			if ( ! $offset && $ppage && $limit ) {
				$offset = ( $ppage - 1 ) * $limit;
			}

			switch ( $report_type ) {
				case 'donors-top':
					return ( $this->get_top_donors( $limit, $offset ) );
					break; // phpcs:ignore
				case 'donors-recurring':
					return ( $this->get_recurring_donors( $limit, $offset ) );
					break; // phpcs:ignore
				case 'donors-first-time':
					return ( $this->get_first_time_donors( $limit, $offset ) );
					break; // phpcs:ignore

				default:
					return false;
					break; // phpcs:ignore
			}
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
		public function get_donor_report_type_dropdown( $selected_value = false ) {

			$options_values = apply_filters(
				'charitable_dashboard_donor_report_type_filter_values',
				array(
					'donors-top'        => __( 'Top Donors', 'charitable' ),
					'donors-first-time' => __( 'First Time Donors', 'charitable' ),
					'donors-recurring'  => __( 'Recurring Donors', 'charitable' ),
				)
			);

			ob_start();

			?>

			<select name="action" id="report-donor-type-filter">
				<?php foreach ( $options_values as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Get the top donors for the overview report.
		 *
		 * @since 1.8.1
		 *
		 * @param  int $limit The limit of donors to get.
		 *
		 * @return string
		 */
		public function get_top_donors_overview( $limit = false ) { // phpcs:ignore
			if ( empty( $this->donations ) ) {
				return;
			}

			$donations_by_donors = (array) $this->donations;
			$unique_donors       = array();

			// sort multi-dimensional array by 'amount'.
			usort(
				$donations_by_donors,
				function ( $a, $b ) {
					return $b->total_amount - $a->total_amount;
				}
			);

			foreach ( $donations_by_donors as $index => $donation ) {

				if ( empty( $donation->donor_id ) || 0 === $donation->donor_id ) {
					continue;
				}

				$donor = charitable_get_table( 'donors' )->get_personal_data_by_donor_id( $donation->donor_id );

				if ( empty( $donor ) ) {
					continue;
				}

				$donor = $donor[0];

				// combine first and last name from $donor into $donor_name, if either exist.
				$donor_name  = ( ! empty( $donor->first_name ) || ! empty( $donor->last_name ) ? $donor->first_name . ' ' . $donor->last_name : 'Unknown' );
				$donor_email = ( ! empty( $donor->email ) ) ? $donor->email : '';

				$donations_by_donors[ $index ]->donor_name  = trim( $donor_name );
				$donations_by_donors[ $index ]->donor_email = $donor_email;

				$donations_by_donors[ $index ]->donor_avatar_url   = isset( $donation->user_id ) && $donation->user_id !== 0 ? get_avatar_url( $donation->user_id, 32 ) : 0;
				$donations_by_donors[ $index ]->donation_type      = charitable_get_donation( $donation->donation_id )->get( 'donation_type' );
				$donations_by_donors[ $index ]->donation_parent_id = $donation->donation_parent_id;

				if ( ! isset( $unique_donors[ $donation->donor_id ] ) ) {
					$unique_donors[ $donation->donor_id ]['amount'] = $donation->total_amount;
					$unique_donors[ $donation->donor_id ]['count']  = 1;
				} else {
					$unique_donors[ $donation->donor_id ]['amount'] += $donation->total_amount;
					$unique_donors[ $donation->donor_id ]['count']   = $unique_donors[ $donation->donor_id ]['count'] + 1;
				}
			}

			// go back and add the total donation amount to the $donations_by_donors array.
			foreach ( $donations_by_donors as $index => $donation ) {

				if ( ! empty( $unique_donors[ $donation->donor_id ] ) ) {
					$donations_by_donors[ $index ]->total_donation_amount = $unique_donors[ $donation->donor_id ]['amount'];
					$donations_by_donors[ $index ]->total_donation_count  = $unique_donors[ $donation->donor_id ]['count'];
				}
			}

			// sort multi-dimensional array by 'total_donation_amount'.
			// @since 1.8.8.6 - Fixed undefined property warning by checking if property exists before accessing.
			usort(
				$donations_by_donors,
				function ( $a, $b ) {
					// @since 1.8.8.6 - Check if total_donation_amount exists, default to 0 if not set.
					$a_amount = isset( $a->total_donation_amount ) ? $a->total_donation_amount : 0;
					$b_amount = isset( $b->total_donation_amount ) ? $b->total_donation_amount : 0;
					return $b_amount - $a_amount;
				}
			);

			return $donations_by_donors;
		}

		/**
		 * Get the name of the cache value (or key) for the report we use for the database.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $post_status The post status.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 *
		 * @return string
		 */
		public function get_cache_key( $report_type = 'overview', $start_date = false, $end_date = false, $post_status = false, $campaign_id = false, $category_id = false ) {

			$cache_key = sanitize_title( $start_date . $end_date . str_replace( 'charitable', '', $post_status ) . $campaign_id . $category_id );

			// remove all dashes from the cache key.
			$cache_key = 'wpch-report-' . $report_type . '-' . str_replace( '-', '', $cache_key );

			return $cache_key;
		}

		/**
		 * Get the cached report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $post_status The post status.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 *
		 * @return array|bool
		 */
		public function get_cached_report( $report_type = 'overview', $start_date = false, $end_date = false, $post_status = false, $campaign_id = false, $category_id = false ) {

			if ( ! $this->maybe_cache_report( true ) ) {
				return false;
			}

			$cache_key = $this->get_cache_key( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id );

			$data = get_transient( $cache_key );

			if ( false === $data ) {
				return false;
			}

			return $data;
		}

		/**
		 * Set the cached report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $post_status The post status.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 * @param  array  $data The data to cache.
		 *
		 * @return bool
		 */
		public function set_cached_report( $report_type = 'overview', $start_date = false, $end_date = false, $post_status = false, $campaign_id = false, $category_id = false, $data = array() ) {

			if ( ! $this->maybe_cache_report( true ) ) {
				return false;
			}

			$cache_key = $this->get_cache_key( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id );

			$was_set = set_transient( $cache_key, $data, MINUTE_IN_SECONDS );

			return $was_set;
		}

		/**
		 * Check if the report should be cached.
		 *
		 * @since 1.8.1
		 *
		 * @param  bool $force_cache Whether to force the cache.
		 *
		 * @return bool
		 */
		public function maybe_cache_report( $force_cache = false ) {

			// Check the globals to see if caching is enabled specifically for reports.
			if ( $force_cache ) {
				return true;
			}
			if ( charitable_is_debug() ) {
				return false;
			}
			if ( defined( 'CHARITABLE_REPORTS_NO_CACHE' ) && CHARITABLE_REPORTS_NO_CACHE ) { // phpcs:ignore
				return false;
			}

			return true;
		}

		/**
		 * This accepts a report type and date and attemps to create a report that has multiple data points
		 *
		 * @param  string $report_type The type of report to create.
		 * @param  array  $args The array of args.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_data( $report_type = 'overview', $args = array() ) {

			global $wpdb;

			$defaults = array(
				'report_type' => 'overview',
				'start_date'  => false,
				'end_date'    => false,
				'post_status' => 'charitable-completed',
				'campaign_id' => false,
				'category_id' => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$report_type = $args['report_type'] ?? 'overview';
			$start_date  = $args['start_date'] ?? false;
			$end_date    = $args['end_date'] ?? false;
			$post_status = $args['post_status'] ?? 'charitable-completed';
			$campaign_id = $args['campaign_id'] ?? false;
			$category_id = $args['category_id'] ?? false;

			// Check the post_status and set it to the default if it's not a valid status.
			if ( ! in_array( $post_status, array( 'charitable-completed', 'charitable-pending', 'charitable-failed', 'charitable-cancelled', 'charitable-refunded' ), true ) ) {
				$post_status = 'charitable-completed';
			}

			$this->donations = ! charitable_is_pro() && $report_type !== 'overview' ? $this->get_data_sample( $report_type ) : false;

			// Determine if this particular report has been cached recently.
			$this->donations = false; // $this->get_cached_report( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id );

			if ( false === $this->donations ) {

				// No cache? Let's run the data.

				$where_sql   = array();
				$where_sql[] = 'WHERE 1=1';
				$where_sql[] = ( intval( $campaign_id ) > 0 ) ? 'cd.campaign_id = ' . intval( $campaign_id ) : false;
				$where_sql[] = 'p.post_type = "%s"';
				$where_sql[] = 'p.post_status = "' . $post_status . '"';
				$where_sql[] = ( $start_date ) ? "p.post_date >= '" . $start_date . " 00:00:00'" : false;
				$where_sql[] = ( $end_date ) ? "p.post_date <= '" . $end_date . " 23:59:59'" : false;
				$where_sql[] = ( intval( $category_id ) > 0 ) ? 'tr.term_taxonomy_id = ' . intval( $category_id ) : false;
				$where_sql[] = 'pm1.meta_key = "donation_gateway"';
				// remove all empty values from the array.
				$where_sql  = array_filter( $where_sql );
				$where_args = implode( ' AND ', $where_sql );

				$left_join   = array();
				$left_join[] = $wpdb->prefix . 'charitable_campaign_donations cd ON p.ID = cd.donation_id';
				$left_join[] = $wpdb->prefix . 'charitable_donors cdonors ON cd.donor_id = cdonors.donor_id';
				$left_join[] = $wpdb->prefix . 'postmeta pm1 ON p.ID = pm1.post_id';
				$left_join[] = ( intval( $category_id ) > 0 ) ? $wpdb->prefix . 'term_relationships tr ON tr.object_id = campaign_id' : false;
				// remove all empty values from the array.
				$left_join      = array_filter( $left_join );
				$left_join_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join );

				$sql = "SELECT SUM(cd.amount) AS total_amount,
				cd.donation_id AS donation_id,
				cd.campaign_id AS campaign_id,
				cd.donor_id AS donor_id,
				cd.amount AS amount,
				pm1.meta_value AS payment_gateway,
				p.post_date AS post_date,
				p.post_date_gmt AS post_date_gmt,
				p.post_status AS donation_status,
				p.post_parent AS donation_parent_id,
				cdonors.first_name AS first_name,
				cdonors.last_name AS last_name,
				cdonors.email AS email
				FROM $wpdb->posts p
				" . $left_join_args . '
				' . $where_args . '
				GROUP BY p.ID';

				// get all donations (posts with a post type of donation) between a start date and an end date.
				$this->donations = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						$sql, // phpcs:ignore
						'donation'
					)
				);

				// Save this to the cache.
				// $this->set_cached_report( $report_type, $start_date, $end_date, $post_status, $campaign_id, $category_id, $this->donations );.

			}

			$this->init_axis_with_donations( $this->donations, $start_date, $end_date );

			return $this->donations;
		}

		/**
		 * This accepts a report type and date and attemps to create a report specific to a donor type report.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The type of report to create.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $category_id The category ID.
		 *
		 * @return array
		 */
		public function get_donors_data( $report_type = 'donors-top', $start_date = false, $end_date = false, $campaign_id = false, $category_id = false ) { // phpcs:ignore

			global $wpdb;

			// Determine if this particular report gets sample data, or if not if it's has been cached recently.
			$this->donors = ! charitable_is_pro() ? $this->get_data_sample( $report_type ) : false; // $this->get_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id );

			if ( false === $this->donors ) {

				// No cache? Let's run the data.

				$having_sql = '';
				$order_by   = '';

				switch ( $report_type ) {
					case 'donors-recurring':
						$having_sql = 'HAVING total_count_donations > 1';
						$order_by   = 'ORDER BY %s DESC';
						$limit      = 'LIMIT ' . apply_filters( 'charitable_dashboard_donor_report_recurring_sql_limit', 100 );

						$sql = "SELECT SUM( ccd.amount ) AS total_amount,
								AVG( ccd.amount ) AS average,
								COUNT( ccd.donation_id ) AS total_count_donations,
								COUNT( DISTINCT( ccd.campaign_id ) ) AS total_count_campaigns,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								{$limit}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								$sql, // phpcs:ignore
								'total_amount'
							)
						);

						break;

					case 'donors-first-time':
						$having_sql = 'HAVING total_count_donations = 1';
						$order_by   = 'ORDER BY %s DESC'; // not currently being used.
						$limit      = 'LIMIT ' . apply_filters( 'charitable_dashboard_donor_report_first_time_sql_limit', 100 );

						$sql = "SELECT ccd.amount AS total_amount,
								COUNT( ccd.donation_id ) AS total_count_donations,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								GROUP BY cd.donor_id

								{$having_sql}

								ORDER BY last_donation_date DESC

								{$limit}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								$sql // phpcs:ignore
							)
						);

						break;

					default:
						$order_by = 'ORDER BY %s DESC';
						$limit    = 'LIMIT ' . apply_filters( 'charitable_dashboard_donor_report_default_sql_limit', 100, $report_type );

						$sql = "SELECT SUM( ccd.amount ) AS total_amount,
								AVG( ccd.amount ) AS average,
								COUNT( ccd.donation_id ) AS total_count_donations,
								COUNT( DISTINCT( ccd.campaign_id ) ) AS total_count_campaigns,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								{$limit}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								$sql, // phpcs:ignore
								'total_amount'
							)
						);

						break;
				}

				// Save this to the cache.
				// $this->set_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id, $this->donors );.

				return $this->donors;

			}
		}

		/**
		 * This accepts a report type and date and attemps to create a report that has multiple data points
		 *
		 * @since  1.8.1
		 * @since  1.8.3 Updated w/ limit and offset sql, etc.
		 *
		 * @param  string $report_type The type of report to create.
		 * @param  array  $args The array of args.
		 *
		 * @return array
		 */
		public function get_donors_data_array( $report_type = 'donors-top', $args = array() ) {

			global $wpdb;

			$defaults = array(
				'report_type' => 'donors-top',
				'start_date'  => false,
				'end_date'    => false,
				'campaign_id' => false,
				'category_id' => false,
				'limit'       => false,
				'offset'      => false,
			);

			$args = wp_parse_args( $args, $defaults );

			// Extract individual variables from args array.
			$report_type = $args['report_type'] ?? 'donors-top';
			$start_date  = $args['start_date'] ?? false;
			$end_date    = $args['end_date'] ?? false;
			$campaign_id = $args['campaign_id'] ?? false;
			$category_id = $args['category_id'] ?? false;
			$limit       = $args['limit'] ?? false;
			$offset      = $args['offset'] ?? false;
			$ppage       = $args['ppage'] ?? false;

			// Determine if this particular report gets sample data, or if not if it's has been cached recently.
			$this->donors = ! charitable_is_pro() ? $this->get_data_sample( $report_type ) : false; // $this->get_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id );

			if ( false === $this->donors ) {

				// No cache? Let's run the data.

				$having_sql = '';
				$order_by   = '';
				$where      = '';
				// generate mysql LIMIT and OFFSET.
				if ( ! $offset && $ppage && $limit ) {
					$offset = ( $ppage - 1 ) * $limit;
				}
				$limit_sql  = ( $limit ) ? 'LIMIT ' . $limit : '';
				$offset_sql = ( $offset ) ? 'OFFSET ' . $offset : '';

				switch ( $report_type ) {
					case 'donors-recurring':
						$having_sql = 'HAVING total_count_donations > 1';
						$order_by   = 'ORDER BY total_amount DESC';

						$query = "SELECT SUM( ccd2.amount ) AS total_amount,
									AVG( ccd2.amount ) AS average,
									COUNT( ccd2.donation_id ) AS total_count_donations,
									COUNT( DISTINCT( ccd2.campaign_id ) ) AS total_count_campaigns,
									last_donation.post_date AS last_donation_date,
									last_campaign.post_title AS last_campaign_title,
									last_campaign.ID AS last_campaign_id,
									cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount, ccd.campaign_donation_id AS CAMPAIGN_DONATION_ID

									FROM {$wpdb->prefix}charitable_donors cd

									JOIN ( 	SELECT {$wpdb->prefix}charitable_campaign_donations.donor_id, {$wpdb->prefix}charitable_campaign_donations.donation_id, {$wpdb->prefix}charitable_campaign_donations.campaign_id, {$wpdb->prefix}charitable_campaign_donations.amount, {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
											FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN ( SELECT MAX(campaign_donation_id) AS max_campaign_donation_id, donor_id FROM {$wpdb->prefix}charitable_campaign_donations JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed') GROUP BY donor_id ) last_campaign_donation_id ON last_campaign_donation_id.max_campaign_donation_id = {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
										) ccd ON ccd.donor_id = cd.donor_id

									JOIN ( 	SELECT donor_id, donation_id, campaign_id, amount, campaign_donation_id FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID
											WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed')

										) ccd2 ON ccd2.donor_id = cd.donor_id

									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_donation ON ccd.donation_id = last_donation.ID
									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_campaign ON ccd.campaign_id = last_campaign.ID

									{$where}

									GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$query . ' ' . $limit_sql . ' ' . $offset_sql // phpcs:ignore
						);

						break;

					case 'donors-first-time':
						$having_sql = 'HAVING total_count_donations = 1';
						$order_by   = 'ORDER BY last_donation_date ASC';

						$query = "SELECT ccd.amount AS total_amount,
								COUNT( ccd.donation_id ) AS total_count_donations,
								last_donation.post_date AS last_donation_date,
								last_campaign.post_title AS last_campaign_title,
								last_campaign.ID AS last_campaign_id,
								cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount

								FROM {$wpdb->prefix}charitable_donors cd

								JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON cd.donor_id = ccd.donor_id
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_donation ON ccd.donation_id = last_donation.ID
								INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ORDER BY post_date DESC ) last_campaign ON ccd.campaign_id = last_campaign.ID

								{$where}

								GROUP BY cd.donor_id

								{$having_sql}

								{$order_by}

								";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$query . ' ' . $limit_sql . ' ' . $offset_sql // phpcs:ignore
						);

						break;

					default:
						$order_by = 'ORDER BY total_amount DESC';

						$query = "SELECT SUM( ccd2.amount ) AS total_amount,
									AVG( ccd2.amount ) AS average,
									COUNT( ccd2.donation_id ) AS total_count_donations,
									COUNT( DISTINCT( ccd2.campaign_id ) ) AS total_count_campaigns,
									last_donation.post_date AS last_donation_date,
									last_campaign.post_title AS last_campaign_title,
									last_campaign.ID AS last_campaign_id,
									cd.donor_id, cd.user_id, cd.email, cd.first_name, cd.last_name, cd.date_joined, ccd.donation_id, ccd.campaign_id, ccd.amount, ccd.campaign_donation_id AS CAMPAIGN_DONATION_ID

									FROM {$wpdb->prefix}charitable_donors cd

									JOIN ( 	SELECT {$wpdb->prefix}charitable_campaign_donations.donor_id, {$wpdb->prefix}charitable_campaign_donations.donation_id, {$wpdb->prefix}charitable_campaign_donations.campaign_id, {$wpdb->prefix}charitable_campaign_donations.amount, {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
											FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN ( SELECT MAX(campaign_donation_id) AS max_campaign_donation_id, donor_id FROM {$wpdb->prefix}charitable_campaign_donations JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed') GROUP BY donor_id ) last_campaign_donation_id ON last_campaign_donation_id.max_campaign_donation_id = {$wpdb->prefix}charitable_campaign_donations.campaign_donation_id
										) ccd ON ccd.donor_id = cd.donor_id

									JOIN ( 	SELECT donor_id, donation_id, campaign_id, amount, campaign_donation_id FROM {$wpdb->prefix}charitable_campaign_donations
											JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}charitable_campaign_donations.donation_ID
											WHERE {$wpdb->prefix}posts.post_status IN ('charitable-completed')

										) ccd2 ON ccd2.donor_id = cd.donor_id

									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_donation ON ccd.donation_id = last_donation.ID
									INNER JOIN ( SELECT ID, post_title, post_date FROM {$wpdb->prefix}posts ) last_campaign ON ccd.campaign_id = last_campaign.ID

									{$where}

									GROUP BY cd.donor_id

									{$having_sql}

									{$order_by}";

						$this->donors = $wpdb->get_results( // phpcs:ignore
							$query . ' ' . $limit_sql . ' ' . $offset_sql // phpcs:ignore
						);

						break;
				}

				$total_query        = 'SELECT COUNT(1) FROM (' . $query . ') AS combined_table';
				$this->donors_total = $wpdb->get_var( $total_query ); // phpcs:ignore

				// Save this to the cache.
				// $this->set_cached_report( $report_type, $start_date, $end_date, false, $campaign_id, $category_id, $this->donors );.

				return $this->donors;

			}
		}

		/**
		 * This accepts donations and attempts to create data for the headline chart with donation data.
		 *
		 * @param  array  $donations An array of donations.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function init_axis_with_donations( $donations = array(), $start_date = false, $end_date = false ) {

			if ( false === $donations ) {
				return;
			}

			$date_data     = array();
			$donation_data = array();

			foreach ( $donations as $donation ) {

				$donation_day = gmdate( 'M d', strtotime( $donation->post_date ) );

				if ( ! in_array( $donation_day, $date_data, true ) ) {
					$date_data[] = $donation_day;
				}

				if ( isset( $donation_data[ $donation_day ]['amount'] ) ) {
					$donation_data[ $donation_day ]['amount'] += $donation->total_amount;
				} else {
					$donation_data[ $donation_day ]['amount'] = $donation->total_amount;
				}

			}

			$this->set_date_axis( $date_data, $start_date, $end_date );
			$this->set_donation_axis( $donation_data, $start_date, $end_date );
		}

		/**
		 * This generates the donation axis for the headline chart.
		 *
		 * @param  array  $donation_data An array of donations.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function set_donation_axis( $donation_data, $start_date, $end_date ) {

			$this->donation_axis = array();

			$days = $this->get_days_between_dates( $start_date, $end_date );

			if ( ! $days || empty( $days ) ) {
				return;
			}

			foreach ( $days as $day ) {
				$this->donation_axis[ $day ] = ! empty( $donation_data[ $day ]['amount'] ) ? $donation_data[ $day ]['amount'] : 0;
			}

			// Remove the keys from the array but keep the order of the values.
			$this->donation_axis = array_values( $this->donation_axis );

			// if the days is only one day, add a day before and after that date so the chart has at least 3 days for display.
			if ( count( $days ) === 1 ) {
				$this->donation_axis = array_merge( array( 0 ), $this->donation_axis, array( 0 ) );
			}

			return $this->donation_axis;
		}

		/**
		 * This generates the date axis for the headline chart.
		 *
		 * @param  array  $date_data An array of dates.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function set_date_axis( $date_data, $start_date, $end_date ) {

			$days = $this->get_days_between_dates( $start_date, $end_date, false );

			// if the days is only one day, then add a day before and after that date so the chart has at least 3 days for display.
			if ( count( $days ) === 1 ) {
				$days = array_merge( array( gmdate( 'M d', strtotime( $start_date . ' -1 day' ) ) ), $days, array( gmdate( 'M d', strtotime( $end_date . ' +1 day' ) ) ) );
			}

			$this->date_axis = $days;

			return $days;
		}

		/**
		 * Util function that gets dates between two given dates.
		 *
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $array_key_format The format of the array key.
		 *
		 * @since  1.8.1
		 *
		 * @return mixed
		 */
		public function get_days_between_dates( $start_date, $end_date, $array_key_format = 'Y-m-d' ) { // phpcs:ignore
			$days = array();

			$current_date = new DateTime( $start_date );
			$end_date     = new DateTime( $end_date );

			while ( $current_date <= $end_date ) {
				if ( $array_key_format ) :
					$days[ $current_date->format( $array_key_format ) ] = $current_date->format( 'M d' );
				else :
					$days[] = $current_date->format( 'M d' );
				endif;

				$current_date->modify( '+1 day' );
			}

			return $days;
		}

		/**
		 * This accepts a report type and date and attemps to create a refund "report".
		 *
		 * @since  1.8.1
		 * @version 1.8.8.6
		 *
		 * @param  string $campaign_id The campaign ID.
		 * @param  string $limit The limit of refunds to get.
		 *
		 * @return array
		 */
		public function get_refunds( $campaign_id = false, $limit = false ) { // phpcs:ignore

			global $wpdb;

			$where_sql   = array();
			$prepare_args = array();

			$where_sql[] = 'p.post_status = %s';
			$prepare_args[] = 'charitable-refunded';

			// add to $where_sql depending on the values of $start_date and $end_date.
			if ( $this->start_date ) {
				$where_sql[] = 'p.post_date >= %s';
				$prepare_args[] = $this->start_date . ' 00:00:00';
			}
			if ( $this->end_date ) {
				$where_sql[] = 'p.post_date <= %s';
				$prepare_args[] = $this->end_date . ' 23:59:59';
			}

			$where_sql[] = 'pm1.meta_key = %s';
			$prepare_args[] = 'donation_gateway';

			$where_clause = implode( ' AND ', $where_sql );

			// Build prepare args with post_type first, then where clause args.
			$all_prepare_args = array_merge( array( 'donation' ), $prepare_args );

			// get all donations (posts with a post type of donation) between a start date and an end date.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// $where_clause is built with placeholders (%s) that are properly processed by $wpdb->prepare().
			// The interpolation is safe because all values in $where_clause are placeholders, not raw values.
			$refunded_donations = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT SUM(cd.amount) AS total_amount,
						COUNT(p.ID) AS total_number_of_refunds,
						cd.donation_id AS donation_id,
						cd.campaign_id AS campaign_id,
						cd.donor_id AS donor_id,
						cd.amount AS amount,
						pm1.meta_value AS payment_gateway,
						p.post_date AS post_date,
						p.post_date_gmt AS post_date_gmt
					FROM {$wpdb->posts} p
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations cd ON p.ID = cd.donation_id
					LEFT JOIN {$wpdb->prefix}postmeta pm1 ON p.ID = pm1.post_id
					WHERE p.post_type = %s AND {$where_clause}
					GROUP BY p.ID",
						...$all_prepare_args
					)
				);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

			$refunds_by_day                  = array();
			$refunded_donations_total_amount = 0;
			$refunded_donations_total_count  = 0;
			$processed_donation_ids         = array(); // Track unique donation IDs to avoid double-counting

			$days = $this->get_days_between_dates( $this->start_date, $this->end_date );

			// First, calculate totals from all refunded donations (regardless of day matching)
			// This ensures count and amount are consistent
			if ( ! empty( $refunded_donations ) ) {
				foreach ( $refunded_donations as $refunded_donation ) {
					// Only count each unique donation ID once
					$donation_id = isset( $refunded_donation->donation_id ) ? intval( $refunded_donation->donation_id ) : 0;
					if ( $donation_id > 0 && ! in_array( $donation_id, $processed_donation_ids, true ) ) {
						$refunded_donations_total_amount += floatval( $refunded_donation->amount );
						$refunded_donations_total_count++;
						$processed_donation_ids[] = $donation_id;
					}
				}
			}

			// Then, organize by day for the breakdown
			foreach ( $days as $day ) {
				$donation_day             = gmdate( 'Y-m-d', strtotime( $day ) );
				$refunded_donation_amount = 0;
				$refunded_donation_count  = 0;
				$processed_day_donation_ids = array(); // Track unique donation IDs per day
				if ( ! empty( $refunded_donations ) ) :
					foreach ( $refunded_donations as $refunded_donation ) {
						// if the refunded donation post date is the same day as the donation_day, add it to the refunded total.
						$refund_date = gmdate( 'Y-m-d', strtotime( $refunded_donation->post_date_gmt ) );
						if ( $refund_date === $donation_day ) {
							$donation_id = isset( $refunded_donation->donation_id ) ? intval( $refunded_donation->donation_id ) : 0;
							// Only count each unique donation ID once per day
							if ( $donation_id > 0 && ! in_array( $donation_id, $processed_day_donation_ids, true ) ) {
								$refunded_donation_amount += floatval( $refunded_donation->amount );
								++$refunded_donation_count;
								$processed_day_donation_ids[] = $donation_id;
							}
						}
					}
				endif;
				$refunds_by_day[ $donation_day ]['label']  = gmdate( 'F d, Y', strtotime( $day ) );
				$refunds_by_day[ $donation_day ]['amount'] = $refunded_donation_amount;
				$refunds_by_day[ $donation_day ]['donors'] = $refunded_donation_count;
			}

			return array(
				'donations'      => $refunded_donations,
				'total_amount'   => $refunded_donations_total_amount,
				'total_count'    => $refunded_donations_total_count,
				'refunds_by_day' => $refunds_by_day,
			);
		}


		/**
		 * This accepts a report type and date and attemps to create a report that has multiple data points.
		 *
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 *
		 * @since  1.8.1
		 */
		public function get_donation_breakdown_data( $start_date = false, $end_date = false ) { // phpcs:ignore

			global $wpdb;

			// Get all posts with a custom post type of 'donation' that were created on and including between $start_data and $end_date.
			$donations = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT SUM(cd.amount) AS total_amount, p.ID FROM $wpdb->posts p
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations cd ON p.ID = cd.donation_id
					WHERE p.post_type = 'donation' AND p.post_status = 'charitable-completed'
					GROUP BY p.ID"
				) // phpcs:ignore
			);

			// Get all posts with a custom post type of 'donation' that were created on and including between $start_data and $end_date - group them by day.
			$donations_by_day = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT SUM(cd.amount) AS total_amount, COUNT(p.ID) AS total_donors, DATE(p.post_date) AS post_date FROM $wpdb->posts p
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations cd ON p.ID = cd.donation_id
					WHERE p.post_type = 'donation' AND p.post_status = 'charitable-completed'
					GROUP BY DATE(p.post_date)"
				) // phpcs:ignore
			);
		}


		/**
		 * This gathers info from the database with a focus on donors.
		 *
		 * @param  string $slug Slug.
		 * @param  string $start_date The start date.
		 * @param  string $end_date The end date.
		 * @param  string $return_as The return type. Object or array. Default is object.
		 *
		 * @since  1.8.1
		 */
		public function get_donors_from_db( $slug = 'compare_from', $start_date = false, $end_date = false, $return_as = 'object' ) {

			// what we need:
			//
			// Total Donation count between start_date and end_date.
			// Total Donation amount between start_date and end_date.

			global $wpdb;

			$return_as = ( 'array' === $return_as ) ? 'ARRAY_A' : 'OBJECT_K';

			return ( $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT cd.donor_id AS donor_id, COUNT(ccd.donation_id) as %s, SUM(ccd.amount) AS %s,
					cd.user_id AS user_id,
					cd.email AS donor_email,
					cd.first_name AS donor_first_name,
					cd.last_name AS donor_last_name
					FROM {$wpdb->prefix}charitable_donors cd
					LEFT JOIN {$wpdb->prefix}charitable_campaign_donations ccd ON ccd.donor_id = cd.donor_id
					LEFT JOIN {$wpdb->prefix}posts p ON p.ID = ccd.donation_id
					WHERE p.post_date >= %s AND p.post_date <= %s AND p.post_status = 'charitable-completed'
					GROUP BY cd.donor_id",
					'total_donation_count_' . $slug,
					'total_donation_amount_' . $slug,
					$start_date,
					$end_date
				),
				$return_as
			) );
		}

		/**
		 * Generates HTML for almost the entire activities area.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  array  $advanced_report_data The report data.
		 * @param  array  $report_args The report args.
		 * @param  bool   $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function generate_advanced_breakdown_report_html( $report_type = 'lybunt', $advanced_report_data = array(), $report_args = array(), $show_actions = false ) {

			$table_meta = (array) $this->get_advanced_report_table_meta( $report_type, $report_args, $show_actions );

			// Allow certain HTML tags in the column titles.
			$title_whitelist_tags = array(

				'br' => array(),

			);

			ob_start();

			?>

			<div class="charitable-headline-reports">


				<?php
				if ( ! charitable_is_pro() ) :
					?>
					<div class="restricted-access-overlay"></div><?php endif; ?>

				<?php echo $this->generate_advanced_cards( $advanced_report_data, $report_type, $report_args ); // phpcs:ignore ?>

			</div>

			<div class="tablenav charitable-section no-bottom">

				<div class="alignleft actions">

					<h2><?php echo esc_html( $table_meta['title'] ); ?></h2>

				</div>

				<div class="alignright">

					<?php if ( ! charitable_is_pro() ) : ?>

						<button disabled="disabled" value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/download.svg'; // phpcs:ignore ?>" alt=""></button>

					<?php else : ?>

					<form action="" method="post" class="charitable-advanced-download-form" id="charitable-advanced-download-form">
						<input name="charitable_report_action" type="hidden" value="charitable_report_download_advanced">
						<input name="start_date_compare_from" type="hidden" value="<?php echo esc_html( $report_args['start_date_compare_from'] ); ?>">
						<input name="end_date_compare_from" type="hidden" value="<?php echo esc_html( $report_args['end_date_compare_from'] ); ?>">
						<input name="start_date_compare_to" type="hidden" value="<?php echo esc_html( $report_args['start_date_compare_to'] ); ?>">
						<input name="end_date_compare_to" type="hidden" value="<?php echo esc_html( $report_args['end_date_compare_to'] ); ?>">
						<input name="campaign_id" type="hidden" value="-1">
						<input name="report_type" type="hidden" value="<?php echo esc_html( $report_type ); ?>">
						<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
						<button value="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" type="submit" class="button with-icon charitable-report-download-button" title="<?php echo esc_html__( 'Download CSV', 'charitable' ); ?>" data-nonce=""><label><?php echo esc_html__( 'Download CSV', 'charitable' ); ?></label><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/download.svg' ); ?>" alt=""></button>
					</form>

					<?php endif; ?>

				</div>

			</div>

			<br class="clear">

			<div class="charitable-report-table-container">

				<?php do_action( 'charitable_report_before_advanced_table' ); ?>

			<?php
			if ( ! charitable_is_pro() ) :
				?>
				<div class="charitable-restricted"><div class="restricted-access-overlay"></div><?php endif; ?>

			<table class="wp-list-table widefat fixed striped table-view-list donations-breakdown charitable-report-ui">
				<thead>
					<tr>
						<?php
						if ( ! empty( $table_meta['columns'] ) ) :
							foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
								if ( $column_slug === 'avatar' ) {
									echo '<th style="width: 32px;" class="charitable-avatar"></th>';
								} else {
									echo '<th scope="col" id="' . esc_attr( $column_slug ) . '" class="manage-column column-' . esc_attr( $column_slug ) . '"><span>' . wp_kses( $column_title, $title_whitelist_tags ) . '</span></th>';
								}
							endforeach;
						endif;
						?>
					</tr>
				</thead>
				<tbody id="donations-breakdown-list">
					<?php echo $this->generate_advanced_breakdown_rows( $advanced_report_data, $report_type, count( $table_meta['columns'], $show_actions ) ); // phpcs:ignore ?>
				</tbody>
				<tfoot>
					<tr>
						<?php
						foreach ( $table_meta['columns'] as $column_slug => $column_title ) :
							if ( $column_slug === 'avatar' ) {
								echo '<th style="width: 32px;" class="charitable-avatar"></th>';
							} else {
								echo '<th scope="col" id="' . esc_html( $column_slug ) . '" class="manage-column column-' . esc_html( $column_slug ) . '"><span>' . wp_kses( $column_title, $title_whitelist_tags ) . '</span></th>';
							}
						endforeach;
						?>
					</tr>
				</tfoot>
			</table>

			<?php
			if ( ! charitable_is_pro() ) :
				?>
				</div><?php endif; ?>

			<?php

			do_action( 'charitable_report_after_advanced_table' );

			?>
			</div>
			<?php

			return ob_get_clean();
		}

		/**
		 * Determines what function to generate table rows for advanced based on the report type.
		 *
		 * @since  1.8.1
		 *
		 * @param  array  $advanced_report_data The report data.
		 * @param  string $report_type The report type.
		 * @param  int    $number_of_columns The number of columns.
		 * @param  bool   $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function generate_advanced_breakdown_rows( $advanced_report_data = array(), $report_type = 'lybunt', $number_of_columns = false, $show_actions = false ) {

			$rows = false;

			switch ( $report_type ) {
				case 'lybunt':
					$rows = $this->generate_lybunt_rows( $advanced_report_data, $number_of_columns, $show_actions );
					break;

				default:
					// code...
					break;
			}

			return $rows;
		}

		/**
		 * Generates HTML for the "report cards" at the top of the advanced page.
		 *
		 * @since  1.8.1
		 *
		 * @param  array  $advanced_report_data The report data.
		 * @param  string $report_type The report type.
		 * @param  array  $report_args The report args.
		 *
		 * @return string
		 */
		public function generate_advanced_cards( $advanced_report_data = array(), $report_type = 'lybunt', $report_args = array() ) {

			ob_start();

			if ( 'lybunt' === $report_type ) :

				if ( charitable_is_pro() ) :

					if ( ! is_array( $advanced_report_data ) ) {
						$advanced_report_data = array();
					}

					// count all elements in a multi-dimenstional array that has a particular value greator than 0.
					$total_count_donors_from = number_format(
						count(
							array_filter(
								array_column( $advanced_report_data, 'total_donation_count_compare_from' ),
								function ( $value ) {
									return $value > 0;
								}
							)
						)
					);
					$total_count_donors_to   = number_format(
						count(
							array_filter(
								array_column( $advanced_report_data, 'total_donation_count_compare_to' ),
								function ( $value ) {
									return $value > 0;
								}
							)
						)
					);

					$total_amount_donations_from = array_sum( array_column( $advanced_report_data, 'total_donation_amount_compare_from' ) );
					$total_amount_donations_to   = array_sum( array_column( $advanced_report_data, 'total_donation_amount_compare_to' ) );

					// if value does not have decimals, then add two decimal places.
					$total_amount_donations_from = ( false === strpos( $total_amount_donations_from, '.' ) ) ? $total_amount_donations_from . '.00' : $total_amount_donations_from;
					$total_amount_donations_to   = ( false === strpos( $total_amount_donations_to, '.' ) ) ? $total_amount_donations_to . '.00' : $total_amount_donations_to;

					$from_date_range = '( ' . $report_args['start_date_compare_from'] . ' - ' . $report_args['end_date_compare_from'] . ' )';
					$to_date_range   = '( ' . $report_args['start_date_compare_to'] . ' - ' . $report_args['end_date_compare_to'] . ' )';

				else :

					$total_count_donors_from     = number_format( 143 );
					$total_count_donors_to       = number_format( 1332 );
					$total_amount_donations_from = '$500.00';
					$total_amount_donations_to   = '$25,040.00';
					$from_date_range             = '2022/01/01 - 2022/12/31';
					$to_date_range               = '2023/01/01 - 2023/12/31';

				endif;


				?>

				<div class="charitable-cards">
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo ( intval( $total_count_donors_from ) ); ?></span></strong>
						<p><?php echo esc_html__( 'Donors', 'charitable' ); ?></p>
						<p><?php echo esc_html( $from_date_range ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo ( intval( $total_count_donors_to ) ); ?></span></strong>
						<p><?php echo esc_html__( 'Donors', 'charitable' ); ?></p>
						<p><?php echo esc_html( $to_date_range ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo charitable_format_money( $total_amount_donations_from, 2, true ); // phpcs:ignore ?></span></strong>
						<p><?php echo esc_html__( 'Donations', 'charitable' ); ?></p>
						<p><?php echo esc_html( $from_date_range ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card">
						<strong><span id="charitable-top-donor-count"><?php echo charitable_format_money( $total_amount_donations_to, 2, true ); // phpcs:ignore ?></span></strong>
						<p><?php echo esc_html__( 'Donations', 'charitable' ); ?></p>
						<p><?php echo esc_html( $to_date_range ); ?></p>
					</div>
				</div>

				<?php

			endif;

			return ob_get_clean();
		}

		/**
		 * Generates HTML for the "report table" at the middle of the advanced page.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $report_type The report type.
		 * @param  array $report_args The report args.
		 * @param  array $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function get_advanced_report_table_meta( $report_type = 'lybunt', $report_args = array(), $show_actions = false ) {

			$table_meta = array();

			switch ( $report_type ) {
				case 'lybunt':
					$number_donations_1_date_range = ! empty( $report_args['start_date_compare_from'] ) && ! empty( $report_args['end_date_compare_from'] ) ? esc_html( $report_args['start_date_compare_from'] ) . ' - ' . esc_html( $report_args['end_date_compare_from'] ) : '';
					$number_donations_2_date_range = ! empty( $report_args['start_date_compare_to'] ) && ! empty( $report_args['end_date_compare_to'] ) ? esc_html( $report_args['start_date_compare_to'] ) . ' - ' . esc_html( $report_args['end_date_compare_to'] ) : '';

					$table_meta = array(
						'title'   => esc_html__( 'Donors', 'charitable' ),
						'columns' => array(
							'avatar'             => '',
							'name-email'         => esc_html__( 'Name', 'charitable' ),
							'total-donations'    => esc_html__( 'Last Donation', 'charitable' ),
							'number-donations-1' => esc_html__( 'Total Donations', 'charitable' ) . '<br/>' . $number_donations_1_date_range,
							'number-donations-2' => esc_html__( 'Total Donations', 'charitable' ) . '<br/>' . $number_donations_2_date_range,
							'last-campaign'      => esc_html__( 'Last Campaign', 'charitable' ),
							'life-total'         => esc_html__( 'Life Total', 'charitable' ),
						),
					);

					if ( $show_actions ) {
						$table_meta['columns']['actions'] = esc_html__( 'Actions', 'charitable' );
					}

					break;

				default:
					// code...
					break;
			}

			return $table_meta;
		}

		/**
		 * This gets report args and an iniital report.
		 *
		 * @param  string $report_args The report args.
		 *
		 * @since  1.8.1
		 *
		 * @return array
		 */
		public function get_lybunt_data( $report_args = array() ) {

			global $wpdb;

			$start_date_compare_from = esc_html( $report_args['start_date_compare_from'] );
			$end_date_compare_from   = esc_html( $report_args['end_date_compare_from'] );
			$start_date_compare_to   = esc_html( $report_args['start_date_compare_to'] );
			$end_date_compare_to     = esc_html( $report_args['end_date_compare_to'] );
			$limit                   = ! empty( $report_args['limit'] ) ? intval( $report_args['limit'] ) : apply_filters( 'charitable_report_advanced_lybunt_limit', 100 );
			$order_by                = ! empty( $report_args['order_by'] ) ? esc_html( $report_args['order_by'] ) : 'total_life_amount';
			$order                   = ! empty( $report_args['order'] ) ? esc_html( $report_args['order'] ) : 'DESC';

			$_donors = array();

			// Determine if this particular report has been cached recently.
			// For now though we are not caching.
			$donors = $this->maybe_cache_report( false ) ? false : $this->get_cached_report( 'lybunt', $start_date_compare_from, $end_date_compare_to, false, false, false );

			if ( false === $donors ) {

				// Get the foundation of the report.
				$donors_compare_from = (array) $this->get_donors_from_db( 'compare_from', $start_date_compare_from, $end_date_compare_from, 'array' );
				$donors_compare_to   = (array) $this->get_donors_from_db( 'compare_to', $start_date_compare_to, $end_date_compare_to, 'array' );

				// Merge the two arrays - from and to.
				if ( ! empty( $donors_compare_from ) ) :

					foreach ( $donors_compare_from as $key => $donor ) :
						$_donors[ $donor['donor_id'] ] = $donor;
					endforeach;

				endif;

				if ( ! empty( $donors_compare_to ) ) :

					foreach ( $donors_compare_to as $key => $donor ) :
						// if donor_id exists in $_donors, merge the two arrays.
						if ( is_array( $_donors ) && array_key_exists( $donor['donor_id'], $_donors ) ) {
							$_donors[ $donor['donor_id'] ] = array_merge( $_donors[ $donor['donor_id'] ], $donor );
						} else {
							$_donors[ $donor['donor_id'] ] = $donor;
						}
					endforeach;

				endif;

				if ( empty( $_donors ) ) {
					return false;
				}

				// Convert array of arrays to array of objects, keeping the array keys.
				$_donors = array_map(
					function ( $donor ) {
						return (object) $donor;
					},
					$_donors
				);

				$donors = array();

				foreach ( $_donors as $key => $donor ) {
					if ( ! empty( $donor->donor_id ) && 0 !== intval( $donor->donor_id ) ) :
						$donors[ $donor->donor_id ] = $donor;
					endif;
				}

				foreach ( $donors as $donor_id => $donor_info ) {

					$donor                                     = new Charitable_Donor( $donor_id );
					$last_donation                             = $donor->get_last_donation();
					$last_donation_amount                      = $last_donation->get_total_donation_amount();
					$last_donation_campaign_links              = $last_donation->get_campaigns_links();
					$donors[ $donor_id ]->last_donation_amount = $last_donation_amount;
					$donors[ $donor_id ]->last_donation_campaign_links = $last_donation_campaign_links;
					$donors[ $donor_id ]->total_life_amount            = $donor->get_amount();

					if ( empty( $donors[ $donor_id ]->total_donation_count_compare_from ) ) {
						$donors[ $donor_id ]->total_donation_count_compare_from = 0;
					}
					if ( empty( $donors[ $donor_id ]->total_donation_amount_compare_from ) ) {
						$donors[ $donor_id ]->total_donation_amount_compare_from = 0;
					}
					if ( empty( $donors[ $donor_id ]->total_donation_count_compare_to ) ) {
						$donors[ $donor_id ]->total_donation_count_compare_to = 0;
					}
					if ( empty( $donors[ $donor_id ]->total_donation_amount_compare_to ) ) {
						$donors[ $donor_id ]->total_donation_amount_compare_to = 0;
					}
				}

				// Sort the data.
				if ( is_array( $donors ) && count( $donors ) > 0 ) :
					$donors = $this->sort_lybunt_data( $donors, $order_by, $order );
				endif;

				// Reduce the array to the limit.
				if ( $limit && is_array( $donors ) && count( $donors ) > $limit ) :
					$donors = array_slice( $donors, 0, $limit, true );
				endif;

				// Save this to the cache.
				$this->set_cached_report( 'lybunt', $start_date_compare_from, $end_date_compare_to, false, false, false, $donors );

			}

			return $donors;
		}

		/**
		 * Sorts the lybunt data.
		 *
		 * @since  1.8.1.3
		 *
		 * @param  array  $donors The donors data.
		 * @param  string $order_by The order by. Default is total_life_amount.
		 * @param  string $order The order (DESC OR ASC). Default is DESC.
		 *
		 * @return array
		 */
		public function sort_lybunt_data( $donors = array(), $order_by = 'total_life_amount', $order = 'DESC' ) {

			if ( ! empty( $donors ) ) :

				usort(
					$donors,
					function ( $a, $b ) use ( $order_by, $order ) {
						if ( $order === 'DESC' && ! empty( $a->$order_by ) && ! empty( $b->$order_by ) ) {
							return $b->$order_by <=> $a->$order_by;
						} elseif ( $order === 'ASC' && ! empty( $a->$order_by ) && ! empty( $b->$order_by ) ) {
							return $a->$order_by <=> $b->$order_by;
						} else {
							return;
						}
					}
				);

			endif;

			return $donors;
		}

		/**
		 * Generates HTML rows for doantion breakdown table.
		 *
		 * @since  1.8.1
		 *
		 * @param  array $donors_data The donors data.
		 * @param  int   $number_of_columns The number of columns.
		 * @param  bool  $show_actions Whether to show actions.
		 *
		 * @return string
		 */
		public function generate_lybunt_rows( $donors_data = array(), $number_of_columns = false, $show_actions = false ) {

			ob_start();

			if ( empty( $donors_data ) ) {
				?>

				<td colspan="<?php echo intval( $number_of_columns ); ?>"><strong><?php echo esc_html__( 'There are no donations within the date range.', 'charitable' ); ?></strong></td>

				<?php

			} else {

				foreach ( $donors_data as $donor_id => $data ) :

					$donor_name                         = $data->donor_first_name . ' ' . $data->donor_last_name;
					$donor_email                        = $data->donor_email;
					$donor_avatar                       = ( is_email( $donor_email ) ) ? get_avatar_url( $donor_email ) : charitable()->get_path( 'assets', false ) . '/images/misc/placeholder-avatar-small.jpg';
					$total_donation_count_compare_to    = $data->total_donation_count_compare_to;
					$total_donation_amount_compare_to   = number_format( $data->total_donation_amount_compare_to, 2 );
					$total_donation_count_compare_from  = $data->total_donation_count_compare_from;
					$total_donation_amount_compare_from = number_format( $data->total_donation_amount_compare_from, 2 );
					$last_donation_amount               = $data->last_donation_amount;
					$total_life_amount                  = number_format( $data->total_life_amount, 2 );
					$campaign_links                     = implode( ',', $data->last_donation_campaign_links );
					$link                               = false;
					$actions_display                    = '';

					?>

					<tr id="type-lybunt-donor-<?php echo intval( $donor_id ); ?>" class="row-type-lybunt">
						<td class="charitable-avatar" style="width: 32px;"><img src="<?php echo esc_url( $donor_avatar ); ?>" class="avatar avatar-<?php echo esc_html( $donor_id ); ?> photo" width="32" height="32" alt="<?php esc_html__( 'Profile picture of', 'charitable' ); ?> <?php echo esc_html( $donor_name ); ?>"></td>
						<td class="donated column-date" data-colname="<?php echo esc_html__( 'Date.', 'charitable' ); ?>"><?php echo esc_html( $donor_name ); ?><br/><?php echo esc_html( $donor_email ); ?></td>
						<td class="donated column-donations" data-colname="Donations"><?php echo charitable_format_money( $last_donation_amount ); // phpcs:ignore ?></td>
						<td class="donated column-donors" data-colname="Number of Donors"><?php echo esc_html( $total_donation_count_compare_from ); ?> <?php echo esc_html__( 'Donations', 'charitable' ); ?><br/><small><?php echo charitable_format_money( $total_donation_amount_compare_from, 2, true ); // phpcs:ignore ?></small></td>
						<td class="donated column-donors" data-colname="Number of Donors"><?php echo esc_html( $total_donation_count_compare_to ); ?> <?php echo esc_html__( 'Donations', 'charitable' ); ?><br/><small><?php echo charitable_format_money( $total_donation_amount_compare_to, 2, true ); // phpcs:ignore ?></small></td>
						<td class="donated column-refunds" data-colname="Refunds"><?php echo $campaign_links; // phpcs:ignore ?></td>
						<td class="donated column-net " data-colname="Net"><?php echo charitable_format_money( $total_life_amount, 2, true ); // phpcs:ignore ?></td>
						<?php

						if ( $show_actions ) :
							if ( $link && charitable_is_pro() ) :

								$actions_display = '<td class="donated column-actions " data-colname="Actions"><a class="charitable-campaign-action-button" title="' . esc_html__( 'View User', 'charitable' ) . '" href="' . esc_url( $link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/eye.svg" width="14" height="14" alt="' . esc_html__( 'View User', 'charitable' ) . '" /></td>';

							else :

								$actions_display = '<td class="donated column-actions " data-colname="Actions"><a class="charitable-campaign-action-button" title="' . esc_html__( 'View User', 'charitable' ) . '" href="#" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/eye.svg" width="14" height="14" alt="' . esc_html__( 'View User', 'charitable' ) . '" /></td>';

							endif;
						endif;

						echo $actions_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

					</tr>

					<?php
				endforeach;

			}

			return ob_get_clean();
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
		public function get_advanced_report_type_dropdown( $selected_value = false ) {

			$options_values = apply_filters(
				'charitable_reports_advanced_report_type_filter_values',
				array(
					'lybunt' => __( 'LYBUNT / SYBUNT', 'charitable' ),
				)
			);

			ob_start();

			?>

			<select autocomplete="off" name="action" id="report-advanced-type-filter">
				<?php foreach ( $options_values as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php

			return ob_get_clean();
		}

		/**
		 * Get the donors for the dedicated advanced report.
		 *
		 * @since 1.8.1
		 *
		 * @param  string $report_args The report args.
		 *
		 * @return array|bool
		 */
		public function get_advanced_data_by_report_type( $report_args = array() ) {

			if ( empty( $report_args['report_type'] ) ) {
				return false;
			}

			switch ( sanitize_title( $report_args['report_type'] ) ) {
				case 'lybunt':
					return ( $this->get_lybunt_data( $report_args ) );
					break; // phpcs:ignore

				default:
					return false;
					break; // phpcs:ignore
			}
		}

		/**
		 * Return net value
		 *
		 * @since 1.8.1
		 *
		 * @param  float $donation_amount The donation amount.
		 * @param  float $refund_amount The refund amount.
		 *
		 * @return float
		 */
		public function get_net( $donation_amount = 0, $refund_amount = 0 ) {

			// If the donation amount is less than the refund amount, return 0.
			if ( $donation_amount < $refund_amount ) {
				return 0;
			}

			return $donation_amount - $refund_amount;
		}

		/**
		 * Amount formatting for reports - includes currency symbol.
		 *
		 * @since  1.8.1
		 *
		 * @param  int $amount The amount.
		 *
		 * @return string
		 */
		public function charitable_reports_format_money( $amount = 0 ) {

			return html_entity_decode( charitable_format_money( $amount ) );
		}

		/**
		 * Returns if activities are empty.
		 *
		 * @since 1.8.1
		 *
		 * @return boolean
		 */
		public function is_activities_empty() {

			if ( $this->activities === false || empty( $this->activities ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get localized strings.
		 *
		 * @since 1.8.1
		 *
		 * @return array
		 */
		private function get_localized_strings() {

			$charitable_reports_advanced_report_strings = apply_filters(
				'charitable_reports_advanced_report_strings',
				array(
					'lybunt' => array(
						'title'       => __( 'LYBUNT / SYBUNT', 'charitable' ),
						'description' => wp_kses_post(
							__(
								'<p>LYBUNT stands for <strong>Last Year But Unfortunately Not This Year</strong>, meaning the donor supported your organization during the last fiscal year but did not return to donate again this year. SYBUNT stands for <strong>Some Year But Unfortunately Not This Year</strong>. </p>

							<p><strong>Why This Is Important:</strong> LYBUNT and SYBUNT reports are important to nonprofits because they allow you to look for low-hanging fruit. Nonprofit organizations have an average retention rate of 43.6%. In order to meet or exceed this retention rate, it is important to nurture the donors you have.</p>',
								'charitable'
							),
							array(
								'p'      => array(),
								'strong' => array(),
							)
						),
					),
				)
			);

			$currency_helper = charitable_get_currency_helper();

			// Decode HTML entities in currency symbol to prevent double-encoding in JSON.
			// The currency symbol comes as HTML entities (e.g., &#36; for $), so we decode it
			// before passing to JavaScript to avoid double-encoding issues.
			// This ensures the currency symbol is a plain character that won't be double-encoded.
			$currency_symbol = $currency_helper->get_currency_symbol();
			// Decode HTML entities to get the plain character (e.g., &#36; becomes $).
			$currency_symbol = html_entity_decode( $currency_symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$decimal_count   = $currency_helper->get_decimals();

			$strings = array(
				'version'                       => Charitable::VERSION,
				'nonce'                         => wp_create_nonce( 'charitable-reporting' ),
				'admin_nonce'                   => wp_create_nonce( 'charitable-admin' ),
				'currency_symbol'               => $currency_symbol,
				'decimal_count'                 => (int) $decimal_count,
				'ajax_url'                      => admin_url( 'admin-ajax.php' ),
				'date_select_day'               => 'DD',
				'date_select_month'             => 'MM',
				'headline_chart_options'        => array(
					'donation_axis' => (array) $this->donation_axis,
					'date_axis'     => (array) $this->date_axis,
				),
				'payment_methods_chart_options' => array(
					'payment_percentages' => (array) array_reverse( $this->payment_percentages ),
					'payment_labels'      => (array) array_reverse( $this->payment_labels ),
					'payment_keys'        => (array) array_reverse( $this->payment_keys ),
				),
				'default_start_date'            => current_time( gmdate( 'Y-m-d', strtotime( '-1 month' ) ) ),
				'default_end_date'              => current_time( gmdate( 'Y-m-d' ) ),
				'advanced_reports'              => $charitable_reports_advanced_report_strings,
			);

			$strings = apply_filters( 'charitable_reporting_strings', $strings );

			return $strings;
		}

		/**
		 * Return the array of tabs used on the settings page.
		 *
		 * @since  1.8.1
		 *
		 * @return string[]
		 */
		public function get_sections() {
			/**
			 * Filter the settings tabs.
			 *
			 * @since 1.8.1
			 *
			 * @param string[] $tabs List of tabs in key=>label format.
			 */
			return apply_filters(
				'charitable_reports_tabs',
				array(
					'overview'  => __( 'Overview', 'charitable' ),
					'advanced'  => __( 'Advanced', 'charitable' ),
					'activity'  => __( 'Activity', 'charitable' ),
					'donors'    => __( 'Donors', 'charitable' ),
					'analytics' => __( 'Analytics', 'charitable' ),
				)
			);
		}

		/**
		 * Return the array of terms for custom campaign categories.
		 *
		 * @since  1.8.1
		 *
		 * @return string[]
		 */
		public function get_campaign_categories() {

			return apply_filters(
				'charitable_reports_filter_campaign_categories',
				get_terms(
					'campaign_category'
				)
			);
		}

		/**
		 * Provides the content (title/description) to display inside the title card for a particular report.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 *
		 * @return string[]
		 */
		public function generate_title_card_html( $report_type = false ) {

			if ( false === $report_type ) {
				return;
			}

			switch ( sanitize_title( $report_type ) ) {
				case 'donors-top':
					$title = __( 'Top Donors', 'charitable' );
					// use wp_kses_post to allow html in the string.
					$description = wp_kses_post(
						__(
							'<p>This report shows which donors have made the largest gifts and donations.</p>
					<p><strong>Why This Is Important:</strong> It will show you which donors you can turn to when you need to make an ask for major needs or projects. Given that they are donating a significant amount of money at one time, you don’t want to lump them in with campaigns asking people for smaller, one-off donations. This can help you identify donors who are increasing their giving year over year and who may be good candidates for a major gift ask.</p>
					<p>You can also use this report to thank top donors for their support.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				case 'donors-recurring':
					$title = __( 'Recurring Donors', 'charitable' );
					// use wp_kses_post to allow html in the string.
					$description = wp_kses_post(
						__(
							'<p>This report shows which donors have made repeated donations the largest gifts and donations.</p>
					<p><strong>Why This Is Important:</strong> It will show you which donors you can turn to when you need to make an ask for major needs or projects. Sometimes you do not want to target all donors - especially those that have given once or in small amounts - in repeated campaigns.</p>
					<p>You can also use this report to thank recurring donors for their support.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				case 'donors-first-time':
					$title = __( 'First Time Donors', 'charitable' );
					// use wp_kses_post to allow html in the string.
					$description = wp_kses_post(
						__(
							'<p>This report shows which donors have made one donation - their first.</p>
					<p><strong>Why This Is Important:</strong> Seeing which donors are new to your campaigns can help you determine where your traffic is coming from or how donors are finding you.</p>
					<p>You can also use this report to thank first time donors for their support.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				case 'lybunt':
					$title       = __( 'LYBUNT / SYBUNT', 'charitable' );
					$description = wp_kses_post(
						__(
							'<p>LYBUNT stands for <strong>Last Year But Unfortunately Not This Year</strong>, meaning the donor supported your organization during the last fiscal year but did not return to donate again this year. SYBUNT stands for <strong>Some Year But Unfortunately Not This Year</strong>. </p>

							<p><strong>Why This Is Important:</strong> LYBUNT and SYBUNT reports are important to nonprofits because they allow you to look for low-hanging fruit. Nonprofit organizations have an average retention rate of 43.6%. In order to meet or exceed this retention rate, it is important to nurture the donors you have.</p>',
							'charitable'
						),
						array(
							'p'      => array(),
							'strong' => array(),
						)
					);
					break;

				default:
					// code...
					break;
			}

			ob_start();

			?>
			<h2><?php echo esc_html( $title ); ?></h2>
			<div class="charitable-report-description"><?php echo $description; // phpcs:ignore ?></div>
			<?php

			return ob_get_clean();
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

			$options_values = $this->date_filter_values;

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
		 * Return sample data for the example tables.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 * @param  string $data_type The data type. Object or array.
		 * @param  int    $number_of_records The number of records to return. Default 10.
		 *
		 * @return array
		 */
		public function get_data_sample( $report_type = false, $data_type = 'object', $number_of_records = 10 ) {

			if ( false === $report_type ) {
				return;
			}

			if ( $data_type === 'object' ) {
				$data = new stdClass();
			} else {
				$data = array();
			}

			if ( $this->is_report_type_donor( $report_type ) ) {

				for ( $x = 0; $x < $number_of_records; $x++ ) {

					if ( $data_type === 'object' ) {

						$random_price = wp_rand( 50, 10000 ) / 10;

						$data->{$x} = new stdClass();

						$data->{$x}->total_amount = $random_price;
						$data->{$x}->average      = $random_price;

						$data->{$x}->total_count_donations = 1;
						$data->{$x}->total_count_campaigns = 1;
						$data->{$x}->last_donation_date    = '2023-11-28 16:59:33';
						$data->{$x}->last_campaign_title   = 'My New Campaign 1';
						$data->{$x}->last_campaign_id      = 0;
						$data->{$x}->donor_id              = 0;
						$data->{$x}->user_id               = 0;
						$data->{$x}->email                 = 'example_donor_' . $x . '@wpcharitable.com';
						$data->{$x}->first_name            = 'Roger';
						$data->{$x}->last_name             = 'Wilco';
						$data->{$x}->date_joined           = '2023-11-28 16:59:33';
						$data->{$x}->donation_id           = 0;
						$data->{$x}->campaign_id           = 0;
						$data->{$x}->amount                = $random_price;
						$data->{$x}->sample                = true;

					} else {

						$random_price_1 = mt_rand( 50, 1000 ) / 10; // phpcs:ignore

						$data[] = array(

							'total_amount'          => $random_price_1,
							'average'               => $random_price_1,
							'total_count_donations' => 1,
							'total_count_campaigns' => 1,
							'last_donation_date'    => '2023-11-28 16:59:33',
							'last_campaign_title'   => 'My New Campaign 1',
							'last_campaign_id'      => 0,
							'donor_id'              => 0,
							'user_id'               => 0,
							'email'                 => 'example_donor_' . $x . '@wpcharitable.com',
							'first_name'            => 'Roger',
							'last_name'             => 'Wilco',
							'date_joined'           => '2024-01-28 16:59:33',
							'donation_id'           => 0,
							'campaign_id'           => 0,
							'amount'                => $random_price_1,
							'sample'                => true,
						);
					}
				}

			} elseif ( $report_type === 'advanced' ) {

				$random_price_1 = wp_rand( 50, 10000 ) / 10;
				$random_price_2 = wp_rand( 50, 10000 ) / 10;
				$random_price_3 = wp_rand( 50, 10000 ) / 10;
				$random_price_4 = wp_rand( 50, 10000 ) / 10;

				$data = array(
					1  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'dbisset@donoremail.com',
						'donor_first_name'                 => 'David',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					2  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'sarah@donoremail.com',
						'donor_first_name'                 => 'Sarah',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					3  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'jim@donoremail.com',
						'donor_first_name'                 => 'Jim',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					4  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'george@donoremail.com',
						'donor_first_name'                 => 'George',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					5  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'zoe123@donoremail.com',
						'donor_first_name'                 => 'Zoe',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					6  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'avas@donoremail.com',
						'donor_first_name'                 => 'Ava',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					7  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'zoe123@donoremail.com',
						'donor_first_name'                 => 'Zoe',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					8  => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'avas@donoremail.com',
						'donor_first_name'                 => 'Ava',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					9  => (object) array(
						'donor_id'                         => 1,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_1,
						'user_id'                          => 0,
						'donor_email'                      => 'dbisset@donoremail.com',
						'donor_first_name'                 => 'David',
						'donor_last_name'                  => 'Bisset',
						'last_donation_amount'             => $random_price_2,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => 678,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
					10 => (object) array(
						'donor_id'                         => 2,
						'total_donation_count_compare_to'  => 3,
						'total_donation_amount_compare_to' => $random_price_3,
						'user_id'                          => 0,
						'donor_email'                      => 'sarah@donoremail.com',
						'donor_first_name'                 => 'Sarah',
						'donor_last_name'                  => 'Smith',
						'last_donation_amount'             => $random_price_4,
						'last_donation_campaign_links'     => array(
							34 => 'Help the Children Campaign',
						),
						'total_life_amount'                => $random_price_3,
						'total_donation_count_compare_from' => 0,
						'total_donation_amount_compare_from' => 0,
						'sample'                           => true,
					),
				);

			}

			$data = apply_filters(
				'charitable_report_donation_sample_data',
				$data,
				$report_type,
				$data_type,
				$number_of_records
			);

			return $data;
		}

		/**
		 * Determine if the report type is a particular type of donor report.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The report type.
		 *
		 * @return string
		 */
		public function is_report_type_donor( $report_type = false ) {
			return ( strpos( $report_type, 'donors-' ) === 0 );
		}

		/**
		 * Checks and sees if the string is a valid date, and if so return it in Y-m-d format.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $date_string The date string.
		 *
		 * @return string
		 */
		public function get_valid_date_string( $date_string = false ) {

			// Determine if $date_string is a valid date.
			$parsed_date = gmdate( 'Y/m/d', strtotime( $date_string ) );

			$date = DateTime::createFromFormat( 'Y/m/d', $parsed_date );

			if ( $date && $date->format( 'Y/m/d' ) === $parsed_date ) {
				return $date->format( 'Y-m-d' );
			} else {
				return false;
			}
		}

		/**
		 * Display upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since   1.8.1
		 * @version 1.8.8
		 *
		 * @param string $view Current view inside the plugin settings page.
		 *
		 * @return void
		 */
		public function reports_cta( $view = false, $css_class = 'reports-lite-cta', $show_close_button = false ) { // phpcs:ignore

			if ( charitable_is_pro() ) {
				// no need to display this cta since they have a valid license.
				return;
			}

			if ( get_option( 'charitable_lite_reports_upgrade', false ) || apply_filters( 'charitable_lite_reports_upgrade', false ) ) {
				return;
			}
			?>
			<?php charitable_render_global_upgrade_cta( $css_class ); ?>
			<?php
		}

		/**
		 * Dismiss upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since 1.8.1
		 */
		public function reports_cta_dismiss() {

			if ( ! charitable_current_user_can() ) {
				wp_send_json_error();
			}

			update_option( 'charitable_lite_reports_upgrade', time() );

			wp_send_json_success();
		}

		/**
		 * See if it's the right time to load scripts.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function maybe_load_scripts() {

			$screen = get_current_screen();

			// Add Javascript vars at the footer of the WordPress admin, at the dashboard and the overview tab of reporting.
			if ( ! is_null( $screen ) && ( $screen->id === 'charitable_page_charitable-reports' && ( empty( $_GET['tab'] ) || ( ! empty( $_GET['tab'] ) && charitable_reports_allow_tab_load_scripts( strtolower( $_GET['tab'] ) ) ) ) ) ) { // phpcs:ignore

				// Specific styles for the "overview" tab.
				add_action( 'admin_footer', [ $this, 'report_vars' ], 100 );
			}
		}

		/**
		 * See if it's the right time to load cta.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function maybe_add_reports_cta() {
			add_action( 'charitable_report_before_donor_table', [ $this, 'reports_cta' ] );
			add_action( 'charitable_report_before_activity_table', [ $this, 'reports_cta' ] );
			add_action( 'charitable_report_before_advanced_table', [ $this, 'reports_cta' ] );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Reports
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
