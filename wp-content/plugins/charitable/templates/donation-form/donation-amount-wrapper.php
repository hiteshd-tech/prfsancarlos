<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display the donation amount wrapper.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.5.0
 * @version 1.8.4 // updated filtering for legend filter that was added in 1.8.3.6.
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form    = $view_args['form'];
$charitable_field   = $view_args['field'];
$charitable_classes = $view_args['classes'];
$charitable_fields  = isset( $charitable_field['fields'] ) ? $charitable_field['fields'] : array();
$charitable_legend  = apply_filters( 'charitable_donation_amount_legend', wp_kses_post( $charitable_field['legend'] ), $charitable_form );

if ( ! count( $charitable_fields ) ) :
	return;
endif;

?>
<fieldset class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php
	if ( isset( $charitable_field['legend'] ) ) :
		?>
		<?php do_action( 'charitable_before_donation_amount_wrapper_header' ); ?>
		<div class="charitable-form-header"><?php echo $charitable_legend; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php do_action( 'charitable_after_donation_amount_wrapper_header' ); ?>
		<?php
	endif;

	echo $charitable_form->maybe_show_current_donation_amount(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	?>
	<div id="charitable-donation-options-<?php echo esc_attr( $charitable_form->get_form_identifier() ); ?>">
		<?php $charitable_form->view()->render_fields( $charitable_fields ); ?>
	</div><!-- charitable-donation-options-<?php echo esc_attr( $charitable_form->get_form_identifier() ); ?> -->
</fieldset>
