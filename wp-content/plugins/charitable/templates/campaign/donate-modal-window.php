<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-modal-window.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.6.57
 * @version 1.8.3.7
 * @version 1.8.8.6
 */

$charitable_campaign = $view_args['campaign'];

if ( ! $charitable_campaign->can_receive_donations() ) :
	return;
endif;

$charitable_modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal charitable-modal-donation' );

wp_enqueue_script( 'lean-modal' );
wp_enqueue_style( 'lean-modal-css' );

?>
<div id="charitable-donation-form-modal-<?php echo esc_attr( $charitable_campaign->ID ); ?>" style="display: none;" class="<?php echo esc_attr( $charitable_modal_class ); ?>">
	<a class="modal-close"></a>
	<?php $charitable_campaign->get_donation_form()->render(); ?>
</div>
