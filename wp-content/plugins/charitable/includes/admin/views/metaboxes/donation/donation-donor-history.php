<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation donor history meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.7.0.8
 * @version   1.8.1.5 - added donation ID check in foreach.
 * @version   1.8.8.6
 */

global $post;

$charitable_donor_id           = charitable_get_donation( $post->ID )->get_donor_id();
$charitable_date_format        = get_option( 'date_format' );
$charitable_time_format        = get_option( 'time_format' );
$charitable_distinct_donations = false;
$charitable_show_campaign_name = apply_filters( 'charitable_donations_donor_history_show_campaign', true );

$charitable_meta = charitable_get_donation( $post->ID )->get_donation_meta();

$charitable_donations            = charitable_get_table( 'campaign_donations' )->get_donations_by_donor( $charitable_donor_id, $charitable_distinct_donations );
$charitable_total_amount_donated = charitable_get_table( 'campaign_donations' )->get_total_donated_by_donor( $charitable_donor_id );
$charitable_number_of_donations  = charitable_get_table( 'campaign_donations' )->count_donations_by_donor( $charitable_donor_id, $charitable_distinct_donations );

?>
<div id="charitable-donation-donor-history-metabox" class="charitable-metabox">

	<?php do_action( 'charitable_before_donor_history_meta_info', $charitable_donor_id, $post ); ?>

	<?php if ( $charitable_number_of_donations && ! empty( $charitable_donations ) && intval( $charitable_donor_id ) > 0 && isset( $charitable_meta['donor']['value'] ) && '' !== trim( $charitable_meta['donor']['value'] ) ) : ?>

		<p>
			<?php esc_html_e( 'This user has donated ', 'charitable' ); ?>
			<strong><?php echo intval( $charitable_number_of_donations ); ?></strong>
			<?php if ( count( $charitable_donations ) === 1 ) : ?>
				<?php esc_html_e( 'time', 'charitable' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'times', 'charitable' ); ?>
			<?php endif; ?>
			<?php esc_html_e( ' for a total of', 'charitable' ); ?>
			<strong><?php echo esc_html( charitable_format_money( $charitable_total_amount_donated, false, false, charitable_get_currency() ) ); ?></strong>.
			<a href="#" class="donor-list-view-donations"><?php esc_html_e( 'Show Donations', 'charitable' ); ?></a>
		</p>

		<table class="widefat charitable-donor-history-table" style="display:none;">

		<?php $charitable_donations = array_reverse( $charitable_donations ); ?>

		<?php
		foreach ( $charitable_donations as $charitable_donation ) {

			if ( empty( $charitable_donation->donation_id ) || intval( $charitable_donation->donation_id ) === 0 || ! charitable_get_donation( intval( $charitable_donation->donation_id ) ) ) {
				continue;
			}

			$charitable_donation_date       = charitable_get_donation( intval( $charitable_donation->donation_id ) )->get_date();
			$charitable_donation_time       = charitable_get_donation( intval( $charitable_donation->donation_id ) )->get_time();
			$charitable_donation_status     = charitable_get_donation( intval( $charitable_donation->donation_id ) )->get_status();
			$charitable_amount              = charitable_get_donation( intval( $charitable_donation->donation_id ) )->get_amount_formatted();
			$charitable_admin_donation_link = get_edit_post_link( $charitable_donation->donation_id );
			?>

			<tr>
				<td>
					<?php
					if ( $charitable_show_campaign_name && 0 !== intval( $charitable_donation->campaign_id ) ) :
							$charitable_campaign = charitable_get_campaign( intval( $charitable_donation->campaign_id ) );

						?>
						<p><strong><a href="<?php echo esc_url( get_edit_post_link( $charitable_donation->campaign_id ) ); ?>"><?php echo esc_html( get_the_title( $charitable_donation->campaign_id ) ); ?></a></strong></p>
					<?php endif; ?>
					<p><a href="<?php echo esc_url( $charitable_admin_donation_link ); ?>"><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d', strtotime( $charitable_donation_date ) ), $charitable_date_format ) ); ?></a></p>
					<p><a href="<?php echo esc_url( $charitable_admin_donation_link ); ?>"><?php echo esc_html( get_date_from_gmt( gmdate( 'H:i:s', strtotime( $charitable_donation_time ) ), $charitable_time_format ) ); ?></a></p>
				</td>
				<td><p><?php echo esc_html( $charitable_amount ); ?></p>
					<p>
					<?php
					$charitable_display = sprintf(
						'<mark class="status %s">%s</mark>',
						esc_attr( charitable_get_donation( $charitable_donation->donation_id )->get_status() ),
						strtolower( charitable_get_donation( $charitable_donation->donation_id )->get_status_label() )
					);
						echo $charitable_display; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
						</p>
					<?php do_action( 'charitable_donations_donor_history_after_status', $charitable_donation, $post ); ?>
				</td>
			</tr>

		<?php } ?>

		<?php do_action( 'charitable_after_donor_history_meta_info', $charitable_donor_id, $post ); ?>

		</table>

	<?php else : ?>

		<?php esc_html_e( 'No history for this donor.', 'charitable' ); ?>

	<?php endif; ?>

</div>
