<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-link.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.6.29
 * @version 1.8.8.6
 */

if ( ! array_key_exists( 'campaign', $view_args ) || ! is_a( $view_args['campaign'], 'Charitable_Campaign' ) ) :
	return;
endif;

$charitable_campaign = $view_args['campaign'];

if ( ! $charitable_campaign->can_receive_donations() ) :
	return;
endif;

$charitable_label = sprintf(
	/* translators: %s: campaign title */
	esc_attr_x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ),
	get_the_title( $charitable_campaign->ID )
);

?>
<div class="campaign-donation">
	<a href="#charitable-donation-form"
		class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
		aria-label="<?php echo esc_attr( $charitable_label ); ?>"
	>
	<?php esc_html_e( 'Donate', 'charitable' ); ?>
	</a>
</div>
