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
 * @since   1.0.0
 * @version 1.6.53
 * @version 1.8.8.6
 */

global $post;

$charitable_i18n       = charitable()->registry()->get( 'i18n' );
$charitable_php_format = $charitable_i18n->get_datepicker_format( 'F d, Y' );
$charitable_js_format  = $charitable_i18n->get_js_datepicker_format( 'MM d, yy' );

$charitable_end_date           = get_post_meta( $post->ID, '_campaign_end_date', true );
$charitable_end_time           = strtotime( $charitable_end_date );
$charitable_end_date_formatted = ! $charitable_end_date ? '' : date_i18n( $charitable_php_format, $charitable_end_time );
$title              = array_key_exists( 'title', $view_args ) ? $view_args['title'] : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$charitable_description        = array_key_exists( 'description', $view_args ) ? $view_args['description'] : '';

?>
<div id="charitable-campaign-end-date-metabox-wrap" class="charitable-metabox-wrap">
	<label for="campaign_end_date"><?php echo wp_kses_post( $title ); ?></label>
	<input type="text" id="campaign_end_date" name="_campaign_end_date" placeholder="&#8734;" class="charitable-datepicker" autocomplete="off" data-date="<?php echo esc_attr( $charitable_end_date_formatted ); ?>" data-format="<?php echo esc_attr( $charitable_js_format ); ?>" />
	<?php if ( $charitable_end_date ) : ?>
		<span class="charitable-end-time"><?php echo esc_html( date_i18n( '@ G:i A', $charitable_end_time ) ); ?></span>
		<input type="hidden" id="campaign_end_time" name="_campaign_end_time" value="<?php echo esc_attr( date_i18n( 'H:i:s', $charitable_end_time ) ); ?>" />
	<?php else : ?>
		<span class="charitable-end-time" style="display: none;">=</span>
		<input type="hidden" id="campaign_end_time" name="_campaign_end_time" value="0" />
	<?php endif ?>
	<span class="charitable-helper"><?php echo $charitable_description; // phpcs:ignore ?></span>
</div>
