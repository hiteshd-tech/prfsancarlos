<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop/donate-modal.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.2.3
 * @version 1.6.29
 * @version 1.8.8.6
 */

$charitable_campaign = $view_args['campaign'];

?>
<div class="campaign-donation">
	<a data-trigger-modal="charitable-donation-form-modal-loop"
		data-campaign-id="<?php echo esc_html( $charitable_campaign->ID ); ?>"
		class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
		href="<?php echo esc_url( charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $charitable_campaign->ID ) ) ); ?>"
		<?php // translators: aria-label for the donate button on the campaign loop. ?>
		aria-label="<?php echo esc_attr( sprintf( _x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ), get_the_title( $charitable_campaign->ID ) ) ); ?>">
		<?php esc_html_e( 'Donate', 'charitable' ); ?>
	</a>
</div>
