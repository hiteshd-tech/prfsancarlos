<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.55
 * @version   1.8.8.6
 */

global $post;

$charitable_donation    = charitable_get_donation( $post->ID );
$charitable_donor       = $charitable_donation->get_donor();
$charitable_date        = 'manual' == $charitable_donation->get_gateway() && '00:00:00' == mysql2date( 'H:i:s', $charitable_donation->post_date_gmt ) ? $charitable_donation->get_date() : $charitable_donation->get_date() . ' - ' . $charitable_donation->get_time();
$charitable_data_erased = $charitable_donation->get_data_erasure_date();

/**
 * Filter the total donation amount.
 *
 * @since 1.5.0
 * @since 1.6.8 Removed first parameter, $total (formatted). We also now expect to receive a float from this filter.
 *
 * @param float               $amount   The donation amount.
 * @param Charitable_Donation $donation The Donation object.
 */
$charitable_total = apply_filters( 'charitable_donation_details_table_total', $charitable_donation->get_total(), $charitable_donation );

?>
<div id="charitable-donation-overview-metabox" class="charitable-metabox">
	<?php if ( $charitable_data_erased ) : ?>
		<div class="donation-erasure-notice-wrapper notice-warning notice-alt">
			<div class="donation-erasure-notice">
				<?php
					/* translators: %s: erasure date */
					printf( esc_html__( 'Personal data for this donation was erased on %s.', 'charitable' ), esc_html( $charitable_data_erased ) );
				?>
			</div>
		</div>
	<?php endif ?>
	<div class="donation-banner-wrapper">
		<div class="donation-banner">
			<h3 class="donation-number"><?php printf( '%s #%d', esc_html__( 'Donation', 'charitable' ), esc_html( $charitable_donation->get_number() ) ); ?></h3>
			<span class="donation-date"><?php echo esc_html( $charitable_date ); ?></span>
			<a href="<?php echo esc_url( add_query_arg( 'show_form', true ) ); ?>" class="donation-edit-link"><?php esc_html_e( 'Edit Donation', 'charitable' ); ?></a>
		</div>
	</div>
	<div id="donor" class="charitable-media-block">
		<div class="donor-avatar charitable-media-image">
			<?php echo $charitable_donor->get_avatar( 80 ); // phpcs:ignore ?>
		</div>
		<div class="donor-facts charitable-media-body">
			<h3 class="donor-name"><?php echo esc_html( $charitable_donor->get_name() ); ?></h3>
			<p class="donor-email"><?php echo esc_html( $charitable_donor->get_email() ); ?></p>
			<?php
			/**
			 * Display additional details about the donor.
			 *
			 * @since 1.0.0
			 *
			 * @param Charitable_Donor    $donor    The donor object.
			 * @param Charitable_Donation $donation The donation object.
			 */
			do_action( 'charitable_donation_details_donor_facts', $charitable_donor, $charitable_donation );
			?>
		</div>
	</div>
	<div id="donation-summary">
		<span class="donation-status">
			<?php
				printf(
					'%s: <mark class="status %s">%s</mark>',
					esc_html__( 'Status', 'charitable' ),
					$charitable_donation->get_status(), // phpcs:ignore
					$charitable_donation->get_status_label() // phpcs:ignore
				);
				?>
		</span>
		<?php if ( $charitable_donation->contact_consent_explicitly_set() || ! is_null( $charitable_donor->contact_consent ) ) : ?>
			<span class="contact-consent">
				<?php
					$charitable_consent = $charitable_donation->get_contact_consent();

					printf(
						'%s: <span class="consent %s">%s</span>',
						esc_html__( 'Contact Consent', 'charitable' ),
						strtolower( str_replace( ' ', '-', $charitable_consent ) ), // phpcs:ignore
						$charitable_consent // phpcs:ignore
					);
				?>
			</span>
		<?php endif ?>
	</div>
	<?php
	/**
	 * Add additional output before the table of donations.
	 *
	 * @since 1.5.0
	 *
	 * @param Charitable_Donor    $donor    The donor object.
	 * @param Charitable_Donation $donation The donation object.
	 */
	do_action( 'charitable_donation_details_before_campaign_donations', $charitable_donor, $charitable_donation );
	?>
	<table id="overview">
		<thead>
			<tr>
				<th class="col-campaign-name"><?php esc_html_e( 'Campaign', 'charitable' ); ?></th>
				<th class="col-campaign-donation-amount"><?php esc_html_e( 'Total', 'charitable' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $charitable_donation->get_campaign_donations() as $charitable_campaign_donation ) : ?>
			<tr>
				<td class="campaign-name">
				<?php
					/**
					 * Filter the campaign name.
					 *
					 * @since 1.5.0
					 *
				 * @param string              $campaign_name     The name of the campaign.
				 * @param object              $campaign_donation The campaign donation object.
				 * @param Charitable_Donation $donation          The Donation object.
				 */
				echo apply_filters( 'charitable_donation_details_table_campaign_donation_campaign', $charitable_campaign_donation->campaign_name, $charitable_campaign_donation, $charitable_donation )  // phpcs:ignore
			?>
			</td>
			<td class="campaign-donation-amount">
			<?php
				/**
				 * Filter the campaign donation amount.
				 *
				 * @since 1.5.0
				 *
				 * @param string              $amount            The default amount to display.
				 * @param object              $campaign_donation The campaign donation object.
				 * @param Charitable_Donation $donation          The Donation object.
				 */
				echo apply_filters( 'charitable_donation_details_table_campaign_donation_amount', charitable_format_money( $charitable_campaign_donation->amount, false, false, $charitable_donation->get_currency() ), $charitable_campaign_donation, $charitable_donation ) // phpcs:ignore
			?>
			</td>
		</tr>
		<?php endforeach ?>
		</tbody>
		<tfoot>
			<?php
			/**
			 * Return true if you want to display a Subtotal row before the Total row.
			 *
			 * @since 1.6.8
			 *
			 * @param boolean $display Whether to show the Subtotal row. By default, this is false.
			 */
			if ( apply_filters( 'charitable_donation_details_table_show_subtotal', false ) ) :
				?>
					<tr>
						<th><?php esc_html_e( 'Subtotal', 'charitable' ); ?></th>
						<td><?php echo esc_html( charitable_format_money( $charitable_donation->get_subtotal(), false, true, $charitable_donation->get_currency() ) ); ?></td>
					</tr>
				<?php
			endif;

			/**
			 * Add a row before the total. Any output should be wrapped in <tr></tr> with two cells.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation The Donation object.
			 * @param float               $total    The total donation amount.
			 */
			do_action( 'charitable_donation_details_table_before_total', $charitable_donation, $charitable_total );
			?>
			<tr>
				<th><?php esc_html_e( 'Total', 'charitable' ); ?></th>
				<td><?php echo esc_html( charitable_format_money( $charitable_total, false, true, $charitable_donation->get_currency() ) ); ?></td>
			</tr>
			<?php
			/**
			 * Return true if you want to display a Currency row before the Payment Method row.
			 *
			 * @since 1.6.55
			 *
			 * @param boolean $display Whether to show the Currency row. By default, this is false.
			 */
			if ( apply_filters( 'charitable_donation_details_table_show_currency', false ) ) :
				?>
				<tr>
					<th><?php esc_html_e( 'Currency', 'charitable' ); ?></th>
					<td><?php echo esc_html( $charitable_donation->get_currency() ); ?></td>
				</tr>
				<?php
			endif;
			/**
			 * Add a row before the payment method. Any output should be wrapped in <tr></tr> with two cells.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation The Donation object.
			 */
			do_action( 'charitable_donation_details_table_before_payment_method', $charitable_donation );
			?>
			<tr>
				<th><?php esc_html_e( 'Payment Method', 'charitable' ); ?></th>
				<td><?php echo esc_html( $charitable_donation->get_gateway_label() ); ?></td>
			</tr>
			<?php
			/**
			 * Add a row before the status. Any output should be wrapped in <tr></tr> with two cells.
			 *
			 * @deprecated 1.9.0
			 *
			 * @since 1.5.0
			 * @since 1.6.0 Deprecated.
			 *
			 * @param Charitable_Donation $donation The Donation object.
			 */
			do_action( 'charitable_donation_details_table_before_status', $charitable_donation );

			/**
			 * Add a row after the status. Any output should be wrapped in <tr></tr> with two cells.
			 *
			 * @deprecated 1.9.0
			 *
			 * @since 1.5.0
			 * @since 1.6.0 Deprecated.
			 *
			 * @param Charitable_Donation $donation The Donation object.
			 */
			do_action( 'charitable_donation_details_table_after_status', $charitable_donation );
			?>
		</tfoot>
	</table>
</div>
