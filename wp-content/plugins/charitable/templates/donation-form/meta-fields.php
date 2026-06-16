<?php
/**
 * The template used to display the user fields.
 *
 * @author 	WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.5.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form   = $view_args['form'];
$charitable_field  = $view_args['field'];
$charitable_fields = isset( $charitable_field['fields'] ) ? $charitable_field['fields'] : array();

if ( empty( $charitable_fields ) ) {
	return;
}

?>
<div id="charitable-meta-fields">
	<?php if ( isset( $charitable_field['legend'] ) ) : ?>
		<div class="charitable-form-header"><?php echo esc_html( $charitable_field['legend'] ); ?></div>
	<?php endif; ?>
	<?php $charitable_form->view()->render_fields( $charitable_fields ); ?>
</div><!-- #charitable-meta-fields -->
