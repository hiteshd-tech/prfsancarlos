<?php
/**
 * Displays the donation receipt.
 *
 * Override this template by copying it to yourtheme/charitable/content-donation-receipt.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Receipt
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_content  = $view_args['content'];
$charitable_donation = $view_args['donation'];

/**
 * Add something before the donation receipt and the page content.
 *
 * @since   1.5.0
 *
 * @param   Charitable_Donation $donation The Donation object.
 */
do_action( 'charitable_donation_receipt_before', $charitable_donation );

echo $charitable_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Display the donation receipt content.
 *
 * @since   1.5.0
 *
 * @param   Charitable_Donation $donation The Donation object.
 */
do_action( 'charitable_donation_receipt', $charitable_donation );

/**
 * Add something after the donation receipt.
 *
 * @since   1.5.0
 *
 * @param   Charitable_Donation $donation The Donation object.
 */
do_action( 'charitable_donation_receipt_after', $charitable_donation );
