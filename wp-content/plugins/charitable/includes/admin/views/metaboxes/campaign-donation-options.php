<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation options metabox for the Campaign post type.
 *
 * @author 	WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Metaboxes
 * @version 1.8.8.6
 */

$charitable_title       = isset( $view_args['title'] ) ? $view_args['title'] : '';
$charitable_tooltip     = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> '. $view_args['tooltip'] . '</span>' : '';
$charitable_description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . $view_args['description'] . '</span>' : '';
?>
<div class="charitable-metabox">
	<?php
	do_action( 'charitable_campaign_donation_options_metabox' );
	?>
</div>
