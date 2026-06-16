<?php
/**
 * Display a widget with a link to donate to a campaign.
 *
 * Override this template by copying it to yourtheme/charitable/widgets/donate.php
 *
 * @package Charitable/Templates/Widgets
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.5.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! charitable_is_campaign_page() && 'current' == $view_args['campaign_id'] ) : // phpcs:ignore
	return;
endif;

$charitable_widget_title = apply_filters( 'widget_title', $view_args['title'] );
$charitable_campaign_id  = 'current' == $view_args['campaign_id'] ? get_the_ID() : $view_args['campaign_id']; // phpcs:ignore
$charitable_campaign     = charitable_get_campaign( $charitable_campaign_id );

if ( ! $charitable_campaign || ! $charitable_campaign->can_receive_donations() ) :
	return;
endif;

$charitable_suggested_donations = $charitable_campaign->get_suggested_donations();

if ( empty( $charitable_suggested_donations ) && ! $charitable_campaign->get( 'allow_custom_donations' ) ) :
	return;
endif;

echo $view_args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! empty( $charitable_widget_title ) ) :
	echo $view_args['before_title'] . esc_html( $charitable_widget_title ) . $view_args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
endif;

$charitable_form = new Charitable_Donation_Amount_Form( $charitable_campaign );
$charitable_form->render();

echo $view_args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
