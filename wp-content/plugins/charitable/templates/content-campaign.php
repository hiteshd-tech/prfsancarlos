<?php
/**
 * Displays the campaign content.
 *
 * Override this template by copying it to yourtheme/charitable/content-campaign.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = $view_args['campaign'];
$charitable_content  = $view_args['content'];

/**
 * Add something before the campaign content.
 *
 * @since 1.0.0
 *
 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
 */
do_action( 'charitable_campaign_content_before', $charitable_campaign );

echo $charitable_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Add something after the campaign content.
 *
 * @since 1.0.0
 *
 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
 */
do_action( 'charitable_campaign_content_after', $charitable_campaign );
