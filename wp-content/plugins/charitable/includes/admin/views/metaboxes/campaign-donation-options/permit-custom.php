<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the checkbox to permit custom donation amounts inside the donation options metabox for the Campaign post type.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.8.8.6
 */

global $post;

$title                  = isset( $view_args['label'] ) ? $view_args['label'] : ''; // phpcs:ignore
$charitable_tooltip     = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> ' . $view_args['tooltip'] . '</span>' : '';
$charitable_description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . $view_args['description'] . '</span>' : '';
$charitable_is_allowed  = get_post_meta( $post->ID, '_campaign_allow_custom_donations', true );

if ( ! strlen( $charitable_is_allowed ) ) {
	$charitable_is_allowed = true;
}
?>
<div id="charitable-campaign-allow-custom-donations-metabox-wrap" class="charitable-metabox-wrap charitable-checkbox-wrap">
	<input type="checkbox" id="campaign_allow_custom_donations" name="_campaign_allow_custom_donations" <?php checked( $charitable_is_allowed ); ?> />
	<label for="campaign_allow_custom_donations"><?php echo $title; // phpcs:ignore ?></label>
</div>
