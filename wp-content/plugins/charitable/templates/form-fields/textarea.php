<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display textarea fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.6.1 Added description output.
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = $view_args['classes'];
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_value       = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';

if ( ! isset( $charitable_field['attrs']['rows'] ) ) {
	$charitable_field['attrs']['rows'] = 4;
}

?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element">
			<?php echo wp_kses_post( $charitable_field['label'] ); ?>
			<?php if ( $charitable_is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<textarea name="<?php echo esc_attr( $charitable_field['key'] ); ?>" id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element" <?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore ?>><?php echo esc_textarea( stripslashes( $charitable_value ) ); ?></textarea>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $charitable_field['description'] ) && ! empty( $charitable_field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $charitable_field['description'] ) . '</p>';
	}

	?>
</div>
