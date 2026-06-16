<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the campaign description field for the Campaign post type.
 *
 * @author 	WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Metaboxes
 * @version 1.8.8.6
 */

global $post;

$title 					            = isset( $view_args['title'] ) 		? $view_args['title'] 	: ''; // phpcs:ignore
$charitable_tooltip 				= isset( $view_args['tooltip'] )	? '<span class="tooltip"> '. $view_args['tooltip'] . '</span>'	: '';
$charitable_campaign_description	= get_post_meta( $post->ID, '_campaign_description', true ); // esc_textarea was removed

$charitable_textarea_name      = 'content';
$charitable_textarea_rows      = apply_filters( 'charitable_campaign_description_rows', 15 );
$charitable_textarea_tab_index = isset( $view_args['tab_index'] ) ? $view_args['tab_index'] : 0;

wp_editor( $charitable_campaign_description, '_charitable_campaign_description', array(
	'textarea_name' => '_campaign_description',
	'textarea_rows' => $charitable_textarea_rows,
	'tabindex'      => $charitable_textarea_tab_index,
) );


?>