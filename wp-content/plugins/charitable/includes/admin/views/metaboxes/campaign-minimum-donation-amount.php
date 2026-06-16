<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the end date field for the Campaign post type.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin Views/Metaboxes
 * @since   1.7.0.3
 * @version 1.6.53
 * @version 1.8.8.6
 */

global $post;

$title = isset( $view_args['title'] ) ? $view_args['title'] : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

$charitable_tooltip     = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> ' . $view_args['tooltip'] . '</span>' : '';
$charitable_description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . wp_kses_post( $view_args['description'] ) . '</span>' : '';
$charitable_goal        = get_post_meta( $post->ID, '_campaign_minimum_donation_amount', true );
$charitable_goal        = ! $charitable_goal ? '' : charitable_format_money( $charitable_goal );
?>
<div id="charitable-campaign-min-donation-metabox-wrap" class="charitable-metabox-wrap">
	<h4><?php echo wp_kses_post( $title ); ?></h4>
	<label class="screen-reader-text" for="campaign_minimum_donation_amount"><?php echo wp_kses_post( $title ); ?></label>
	<input type="text" id="campaign_minimum_donation_amount" name="_campaign_minimum_donation_amount"  placeholder="&#8734;" value="<?php echo esc_attr( $charitable_goal ); ?>" />
	<?php echo $charitable_description; // phpcs:ignore ?>
</div>
