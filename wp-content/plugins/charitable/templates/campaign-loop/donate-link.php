<?php
/**
 * Displays the donate button to be displayed within campaign loops.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop/donate-link.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.8.1.12
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = $view_args['campaign'];

if ( ! $charitable_campaign->can_receive_donations() ) :
	return;
endif;

$charitable_button_label = apply_filters( 'charitable_campaign_loop_donate_button_label', esc_html__( 'Donate', 'charitable' ), $charitable_campaign );

?>
<div class="<?php echo esc_attr( apply_filters( 'charitable_campaign_loop_donate_link_div_css', 'campaign-donation', $charitable_campaign ) ); ?>">
	<a class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
		href="<?php echo charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $charitable_campaign->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
		aria-label="
		<?php
		/* translators: %s: Campaign title */
		echo esc_attr( sprintf( _x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ), get_the_title( $charitable_campaign->ID ) ) );
		?>
		">
		<?php echo esc_html( $charitable_button_label ); ?>
	</a>
</div>
