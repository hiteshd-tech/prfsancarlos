<?php
/**
 * Displays the campaign status tag.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/status-tag.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = $view_args['campaign'];
$charitable_tag      = $charitable_campaign->get_status_tag(); // phpcs:ignore

if ( empty( $charitable_tag ) ) {
	return;
}

?>
<div class="campaign-status-tag campaign-status-tag-<?php echo esc_attr( strtolower( str_replace( ' ', '-', $charitable_campaign->get_status_key() ) ) ); ?>">
	<?php echo esc_html( $charitable_tag ); ?>
</div>
