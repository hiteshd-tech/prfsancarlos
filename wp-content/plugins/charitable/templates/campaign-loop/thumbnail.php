<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the campaign thumbnail.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.8.2
 * @version 1.8.3.7 - added force_featured_thumbnail parameter/filter.
 * @version 1.8.8.6
 */

$charitable_campaign                 = $view_args['campaign'];
$charitable_force_featured_thumbnail = apply_filters( 'charitable_campaign_loop_featured_thumbnail', empty( $view_args['force_featured_thumbnail'] ) ? false : true );

// determine if this is a legacy campaign, or a new campaign built with the visual editor.
if ( ! function_exists( 'charitable_is_campaign_legacy' ) ) :
	if ( has_post_thumbnail( $charitable_campaign->ID ) ) :
		echo get_the_post_thumbnail( $charitable_campaign->ID, apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
	endif;
elseif ( charitable_is_campaign_legacy( $charitable_campaign ) || $charitable_force_featured_thumbnail ) :
		$charitable_thumbnail_id = get_post_thumbnail_id( $charitable_campaign->ID );
	if ( $charitable_thumbnail_id ) :
		echo wp_get_attachment_image( $charitable_thumbnail_id, apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
		endif;
	return;
elseif ( function_exists( 'charitable_find_photo_in_campaign_settings' ) ) :
		$charitable_campaign_settings = get_post_meta( $charitable_campaign->ID, 'campaign_settings_v2', true );
		$charitable_media_info        = charitable_find_photo_in_campaign_settings( $charitable_campaign_settings );
	if ( ! empty( $charitable_media_info['media_id'] ) ) {
		$charitable_image_attributes = wp_get_attachment_image_src( $charitable_media_info['media_id'], apply_filters( 'charitable_campaign_loop_thumbnail_size', 'medium' ) );
		if ( ! empty( $charitable_image_attributes ) ) : ?>
				<img src="<?php echo esc_url( $charitable_image_attributes[0] ); ?>" width="<?php echo esc_html( $charitable_image_attributes[1] ); ?>" height="<?php echo esc_html( $charitable_image_attributes[2] ); ?>" />
				<?php
			endif;
	}
endif;
