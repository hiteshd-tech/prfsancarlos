<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display select form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
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
$charitable_value       = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';

if ( is_array( $charitable_value ) ) {
	$charitable_value = current( $charitable_value );
}

if ( count( $charitable_options ) ) :

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
	<select name="<?php echo esc_attr( $charitable_field['key'] ); ?>" id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element">
		<?php
		foreach ( $charitable_options as $charitable_val => $charitable_label ) :

			if ( is_array( $charitable_label ) ) :
				?>
				<optgroup label="<?php echo esc_attr( $charitable_val ); ?>">
				<?php foreach ( $charitable_label as $charitable_val => $charitable_label ) : ?>
					<option value="<?php echo esc_attr( $charitable_val ); ?>" <?php selected( $charitable_val, $charitable_value ); ?>><?php echo esc_html( $charitable_label ); ?></option>
				<?php endforeach; ?>
				</optgroup>
			<?php else : ?>
				<option value="<?php echo esc_attr( $charitable_val ); ?>" <?php selected( $charitable_val, $charitable_value ); ?>><?php echo esc_html( $charitable_label ); ?></option>
				<?php

			endif;
		endforeach;

		?>
	</select>
	<?php if ( isset( $charitable_field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $charitable_field['help']; // phpcs:ignore ?></p>
	<?php endif ?>
</div>
	<?php

endif;
