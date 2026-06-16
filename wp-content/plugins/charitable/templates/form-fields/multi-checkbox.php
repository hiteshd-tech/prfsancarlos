<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display form fields with multiple checkboxes.
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
$charitable_options     = isset( $charitable_field['options'] ) ? $charitable_field['options'] : array();
$charitable_value       = isset( $charitable_field['value'] ) ? (array) $charitable_field['value'] : array();

if ( empty( $charitable_options ) ) {
	return;
}
?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<fieldset class="charitable-fieldset-field-wrapper">
		<?php if ( isset( $charitable_field['label'] ) ) : ?>
			<div class="charitable-fieldset-field-header" id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_label">
				<?php echo wp_kses_post( $charitable_field['label'] ); ?>
				<?php if ( $charitable_is_required ) : ?>
					<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
				<?php endif ?>
			</div>
		<?php endif ?>
		<ul class="charitable-checkbox-list options">
		<?php foreach ( $charitable_options as $charitable_val => $charitable_label ) : ?>
			<li>
				<input type="checkbox"
					id="<?php echo esc_attr( $charitable_field['key'] . '-' . $charitable_val ); ?>"
					name="<?php echo esc_attr( $charitable_field['key'] ); ?>[]"
					value="<?php echo esc_attr( $charitable_val ); ?>"
					aria-describedby="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_label"
					<?php checked( in_array( $charitable_val, $charitable_value ) ); // phpcs:ignore ?>
					<?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore ?> />
				<label for="<?php echo esc_attr( $charitable_field['key'] . '-' . $charitable_val ); ?>"><?php echo esc_html( $charitable_label ); ?></label>
			</li>
		<?php endforeach ?>
		</ul>
		<?php

		// If there is a description, add it after the input.
		if ( isset( $charitable_field['description'] ) && ! empty( $charitable_field['description'] ) ) {
			echo '<p class="charitable-field-description">' . wp_kses_post( $charitable_field['description'] ) . '</p>';
		}

		?>
	</fieldset>
</div>
