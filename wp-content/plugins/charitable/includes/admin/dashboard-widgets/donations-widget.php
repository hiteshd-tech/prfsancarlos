<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the donations widget on the dashboard.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Dashboard Widgets
 * @since   1.2.0
 * @version 1.8.8.6
 */

$charitable_statuses = charitable_get_valid_donation_statuses();

$charitable_donation_args = array(
	'post_type'      => Charitable::DONATION_POST_TYPE,
	'posts_per_page' => 5,
	'post_status'    => array_keys( $charitable_statuses ),
	'fields'         => 'ids',
);
$charitable_donation_args = apply_filters( 'charitable_donations_widget_summary_donation_args', $charitable_donation_args );
$charitable_donations     = get_posts( $charitable_donation_args );

$charitable_table = charitable_get_table( 'campaign_donations' );

$charitable_today      = $charitable_table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_today', wp_date( 'Y-m-d%' ) ) );
$charitable_this_month = $charitable_table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_this_month', wp_date( 'Y-m%' ) ) );
$charitable_last_month = $charitable_table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_last_month', wp_date( 'Y-m%', strtotime( '-1 month' ) ) ) );
$charitable_this_year  = $charitable_table->get_donations_summary_by_period( apply_filters( 'charitable_donations_widget_summary_this_year', wp_date( 'Y-%' ) ) );

?>
<div class="charitable-donation-statistics">
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $charitable_today->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">today</span>', '%d donations <span class="time-period">today</span>', $charitable_today->count, 'charitable' ) ),
					intval( $charitable_today->count )
				);
				?>
		</p>
	</div>
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $charitable_this_month->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">this month</span>', '%d donations <span class="time-period">this month</span>', $charitable_this_month->count, 'charitable' ) ),
					intval( $charitable_this_month->count )
				);
				?>
		</p>
	</div>
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $charitable_last_month->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">last month</span>', '%d donations <span class="time-period">last month</span>', $charitable_last_month->count, 'charitable' ) ),
					intval( $charitable_last_month->count )
				);
				?>
		</p>
	</div>
	<div class="cell">
		<h3 class="amount">
			<?php echo esc_html( charitable_format_money( $charitable_this_year->amount ) ); ?>
		</h3>
		<p class="summary">
			<?php
				printf(
					/* translators: %d: number of donations */
					esc_html( _n( '%d donation <span class="time-period">this year</span>', '%d donations <span class="time-period">this year</span>', $charitable_this_year->count, 'charitable' ) ),
					intval( $charitable_this_year->count )
				);
				?>
		</p>
	</div>
</div>
<?php if ( count( $charitable_donations ) ) : ?>
	<div class="recent-donations">
		<table>
			<caption><h3><?php esc_html_e( 'Recent Donations', 'charitable' ); ?></h3></caption>
			<?php
			foreach ( $charitable_donations as $charitable_donation_id ) :
				$charitable_donation = charitable_get_donation( $charitable_donation_id );
				?>
			<tr>
				<td class="donation-date"><?php echo esc_html( $charitable_donation->get_date() ); ?></td>
				<td class="donation-id">#<?php echo esc_html( $charitable_donation->get_number() ); ?></td>
				<td class="donation-status"><?php echo esc_html( $charitable_donation->get_status_label() ); ?></td>
				<td class="donation-total"><?php echo esc_html( charitable_format_money( $charitable_donation->get_total(), false, true, $charitable_donation->get_currency() ) ); ?></td>
			</tr>
				<?php
			endforeach;
			?>
		</table>
	</div>
<?php endif ?>
