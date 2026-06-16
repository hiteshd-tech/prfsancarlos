<?php
/**
 * The template used to display radio form fields.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/radio.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.6.1 Added description output.
 * @version 1.8.6.1 Added SVG support to label.
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = $view_args['classes'];
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_options     = isset( $charitable_field['options'] ) ? $charitable_field['options'] : array();
$charitable_value       = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';

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
		<ul class="charitable-radio-list <?php echo esc_attr( $view_args['classes'] ); ?>">
			<?php foreach ( $charitable_options as $charitable_option => $charitable_label ) : ?>
				<li><input type="radio"
						id="<?php echo esc_attr( $charitable_field['key'] . '-' . $charitable_option ); ?>"
						name="<?php echo esc_attr( $charitable_field['key'] ); ?>"
						value="<?php echo esc_attr( $charitable_option ); ?>"
						aria-describedby="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_label"
						<?php checked( $charitable_value, $charitable_option ); ?>
						<?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore ?> />
					<?php
					$charitable_allowed_html = array_merge(
						wp_kses_allowed_html( 'post' ),
						array(
							'svg' => array(
								'class' => true,
								'aria-hidden' => true,
								'aria-labelledby' => true,
								'role' => true,
								'xmlns' => true,
								'width' => true,
								'height' => true,
								'viewbox' => true
							),
							'path' => array(
								'd' => true,
								'fill' => true
							)
						)
					);
					?>
					<label for="<?php echo esc_attr( $charitable_field['key'] . '-' . $charitable_option ); ?>"><?php echo wp_kses( $charitable_label, $charitable_allowed_html ); ?></label>
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
