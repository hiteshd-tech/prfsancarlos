<?php
/**
 * Displays the donation summary.
 *
 * Override this template by copying it to yourtheme/charitable/donation-receipt/summary.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Receipt
 * @since   1.0.0
 * @version 1.4.7
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* @var Charitable_Donation */
$charitable_donation = $view_args['donation'];
$charitable_amount   = $charitable_donation->get_total();

?>
<dl class="donation-summary">
	<dt class="donation-id"><?php esc_html_e( 'Donation Number:', 'charitable' ); ?></dt>
	<dd class="donation-summary-value"><?php echo esc_html( $charitable_donation->get_number() ); ?></dd>
	<dt class="donation-date"><?php esc_html_e( 'Date:', 'charitable' ); ?></dt>
	<dd class="donation-summary-value"><?php echo esc_html( $charitable_donation->get_date() ); ?></dd>
	<dt class="donation-total"> <?php esc_html_e( 'Total:', 'charitable' ); ?></dt>
	<dd class="donation-summary-value">
	<?php
		/**
		 * Filter the total donation amount.
		 *
		 * @since  1.5.0
		 *
		 * @param  string              $amount   The default amount to display.
		 * @param  float               $total    The total, unformatted.
		 * @param  Charitable_Donation $donation The Donation object.
		 * @param  string              $context  The context in which this is being shown.
		 * @return string
		 */
		echo apply_filters( 'charitable_donation_receipt_donation_amount', charitable_format_money( $charitable_amount ), $charitable_amount, $charitable_donation, 'summary' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	</dd>
	<dt class="donation-method"><?php esc_html_e( 'Payment Method:', 'charitable' ); ?></dt>
	<dd class="donation-summary-value"><?php echo esc_html( $charitable_donation->get_gateway_label() ); ?></dd>
</dl>
