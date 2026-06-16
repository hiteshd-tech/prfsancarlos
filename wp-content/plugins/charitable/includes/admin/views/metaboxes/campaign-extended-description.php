<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the extended description field for the Campaign post type.
 *
 * @author    WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.0.0
 * @version   1.0.0
 * @version   1.8.8.6
 */

global $post;

$charitable_textarea_name      = 'content';
$charitable_textarea_rows      = apply_filters( 'charitable_extended_description_rows', 90 );
$charitable_textarea_tab_index = isset( $view_args['tab_index'] ) ? $view_args['tab_index'] : 0;

wp_editor( $post->post_content, 'charitable-extended-description', array(
	'textarea_name' => 'post_content',
	'textarea_rows' => $charitable_textarea_rows,
	'tabindex'      => $charitable_textarea_tab_index,
) );
