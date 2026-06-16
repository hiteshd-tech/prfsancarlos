<?php
/**
 * The template used to display the user fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.8.9.1
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form    = $view_args['form'];
$charitable_field   = $view_args['field'];
$charitable_fields  = isset( $charitable_field['fields'] ) ? $charitable_field['fields'] : array();
$charitable_classes = array();

if ( $charitable_form->should_hide_user_fields() ) {
	$charitable_classes[] = 'charitable-hidden';
}

if ( count( $charitable_form->get_meta_fields() ) ) {
	$charitable_classes[] = 'bordered';
}

$charitable_class = empty( $charitable_classes ) ? '' : 'class="' . implode( ' ', $charitable_classes ) . '"';

if ( empty( $charitable_fields ) ) {
	return;
}

if ( isset( $charitable_field['legend'] ) ) :
?>
	<div class="charitable-form-header"><?php echo wp_kses_post( $charitable_field['legend'] ); ?></div>
<?php
endif;

/**
 * Add something before the donor fields.
 *
 * @since 1.0.0
 *
 * @param Charitable_Donation_Form $form The donation form instance.
 */
do_action( 'charitable_donation_form_donor_fields_before', $charitable_form );

?>
	<div id="charitable-user-fields" <?php echo esc_attr( $charitable_class ); ?>>
		<?php $charitable_form->view()->render_fields( $charitable_fields ); ?>
	</div><!-- #charitable-user-fields -->
<?php

/**
 * Add something after the donor fields.
 *
 * @since 1.0.0
 *
 * @param Charitable_Donation_Form $form The donation form instance.
 */
do_action( 'charitable_donation_form_donor_fields_after', $charitable_form );
