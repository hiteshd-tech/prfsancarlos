<?php
/**
 * Displays the donate button to be displayed within campaign loops.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop/more-link.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.2.3
 * @version 1.6.29
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = $view_args['campaign'];

?>
<p>
	<a class="<?php echo esc_attr( charitable_get_button_class( 'read-more' ) ); ?>"
		href="<?php echo esc_url( get_permalink( $charitable_campaign->ID ) ); ?>"
		<?php /* translators: %s: campaign title */ ?>
		aria-label="<?php echo esc_attr( sprintf( _x( 'Continue reading about %s', 'Continue reading about campaign', 'charitable' ), get_the_title( $charitable_campaign->ID ) ) ); ?>"
	>
		<?php esc_html_e( 'Read More', 'charitable' ); ?>
	</a>
</p>
