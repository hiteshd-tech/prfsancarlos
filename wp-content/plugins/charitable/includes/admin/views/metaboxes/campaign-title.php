<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the campaign title field for the Campaign post type.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Metaboxes
 * @version 1.8.8.6
 */

global $post;

$title   = isset( $view_args['title'] ) ? $view_args['title'] : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$charitable_tooltip = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> ' . $view_args['tooltip'] . '</span>' : '';

/**
 * Filter the title field placeholder text.
 *
 * @since 3.1.0
 *
 * @param string  $text Placeholder text. Default 'Enter title here'.
 * @param WP_Post $post Post object.
 */
$charitable_title_placeholder = apply_filters( 'enter_title_here', __( 'Enter campaign title here', 'charitable' ), $post ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'enter_title_here' is a WordPress core filter hook.
?>
<div id="charitable-campaign-title-metabox-wrap" class="charitable-metabox-wrap">
	<label class="screen-reader-text" for="title"><?php echo wp_kses_post( $charitable_title_placeholder ); ?></label>
	<input type="text" name="post_title" size="30"  value="<?php echo esc_attr( htmlspecialchars( $post->post_title ) ); ?>" id="title" spellcheck="true" autocomplete="off" placeholder="<?php echo esc_attr( $charitable_title_placeholder ); ?>" tabindex="1" />
</div>
