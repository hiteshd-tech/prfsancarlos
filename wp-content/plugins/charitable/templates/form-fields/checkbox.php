<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display checkbox form fields.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/checkbox.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.2.0
 * @version 1.8.6.1 Added description.
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = $view_args['classes'];
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_value       = isset( $charitable_field['value'] ) ? esc_attr( $charitable_field['value'] ) : '1';

if ( isset( $charitable_field['checked'] ) ) {
	$charitable_checked = $charitable_field['checked'];
} else {
	$charitable_checked = isset( $charitable_field['default'] ) ? $charitable_field['default'] : 0;
}
?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<input
		type="checkbox"
		name="<?php echo esc_attr( $charitable_field['key'] ); ?>"
		value="<?php echo esc_attr( $charitable_value ); ?>"
		id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element"
		<?php checked( $charitable_checked ); ?>
		<?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	/>
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element">
		<?php echo wp_kses_post( $charitable_field['label'] ); ?>
			<?php if ( $charitable_is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php if ( isset( $charitable_field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo wp_kses_post( $charitable_field['help'] ); ?></p>
	<?php endif ?>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $charitable_field['description'] ) && ! empty( $charitable_field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $charitable_field['description'] ) . '</p>';
	}

	?>
</div>