<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the campaign goal block in the settings metabox for the Campaign post type.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Metaboxes
 * @version 1.8.8.6
 */

global $post;

$title       = isset( $view_args['title'] ) ? $view_args['title'] : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$charitable_tooltip     = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> ' . $view_args['tooltip'] . '</span>' : '';
$charitable_description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . wp_kses_post( $view_args['description'] ) . '</span>' : '';
$charitable_goal        = get_post_meta( $post->ID, '_campaign_goal', true );
$charitable_goal        = ! $charitable_goal ? '' : charitable_format_money( $charitable_goal );
?>
<div id="charitable-campaign-goal-metabox-wrap" class="charitable-metabox-wrap">
	<label class="screen-reader-text" for="campaign_goal"><?php echo wp_kses_post( $title ); ?></label>
	<input type="text" id="campaign_goal" name="_campaign_goal"  placeholder="&#8734;" value="<?php echo esc_attr( $charitable_goal ); ?>" />
	<?php echo $charitable_description; // phpcs:ignore ?>
</div>
