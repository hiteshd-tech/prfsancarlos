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
<fieldset id="charitable-donor-fields" class="charitable-fieldset">
	<?php if ( isset( $charitable_field['legend'] ) ) : ?>
		<div class="charitable-form-header"><?php echo wp_kses_post( $charitable_field['legend'] ); ?></div>
	<?php endif; ?>
	<div class="charitable-form-fields cf">
		<?php $charitable_form->view()->render_fields( $charitable_fields ); ?>
	</div><!-- .charitable-form-fields -->
</fieldset><!-- #charitable-donor-fields -->
