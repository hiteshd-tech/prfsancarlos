<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-button.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.3.0
 * @version 1.7.0.9
 * @version 1.8.8.6
 */

$charitable_campaign          = $view_args['campaign'];
$charitable_button_style      = array_key_exists( 'button_colour', $view_args ) ? 'style="background-color:' . $view_args['button_colour'] . ';"' : '';
$charitable_button_text       = array_key_exists( 'button_text', $view_args ) ? $view_args['button_text'] : __( 'Donate', 'charitable' );
$charitable_show_amount_field = array_key_exists( 'show_amount_field', $view_args ) && $view_args['show_amount_field'];

$charitable_button_text = esc_html( get_post_meta( $charitable_campaign->ID, '_campaign_donate_button_text', true ) );
$charitable_button_text = false === $charitable_button_text || '' === trim( $charitable_button_text ) ? __( 'Donate', 'charitable' ) : $charitable_button_text;

?>
<form class="campaign-donation" method="post">
	<?php wp_nonce_field( 'charitable-donate', 'charitable-donate-now' ); ?>
	<input type="hidden" name="charitable_action" value="start_donation" />
	<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $charitable_campaign->ID ); ?>" />
	<button type="submit" name="charitable_submit" class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $charitable_button_text ) ); ?></button>
</form>
