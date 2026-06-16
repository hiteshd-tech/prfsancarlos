<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display multi select field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.6.5
 * @version   1.8.9.1
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$charitable_value       = is_array( $view_args['value'] ) ? $view_args['value'] : array( $view_args['value'] );
$charitable_is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];
$charitable_field_attrs = array_key_exists( 'field_attrs', $view_args ) ? $view_args['field_attrs'] : array();

?>
<div id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( isset( $view_args['label'] ) ) : ?>
		<label for="<?php echo esc_attr( $view_args['id'] ); ?>">
			<?php
			echo esc_html( $view_args['label'] );
			if ( $charitable_is_required ) :
				?>
				<abbr class="required" title="required">*</abbr>
				<?php
			endif;
			?>
		</label>
	<?php endif ?>
	<select
		id="<?php echo esc_attr( $view_args['id'] ); ?>"
		name="<?php echo esc_attr( $view_args['key'] ); ?>"
		tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
		multiple="true"
		<?php echo charitable_get_arbitrary_attributes( $charitable_field_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	>
	<?php
	foreach ( $view_args['options'] as $charitable_key => $charitable_option ) :
		if ( is_array( $charitable_option ) ) :
			$charitable_label = isset( $charitable_option['label'] ) ? $charitable_option['label'] : '';
			?>
			<optgroup label="<?php echo esc_attr( $charitable_label ); ?>">
			<?php foreach ( $charitable_option['options'] as $charitable_k => $charitable_opt ) : ?>
				<option value="<?php echo esc_attr( $charitable_k ); ?>" <?php selected( in_array( $charitable_k, $charitable_value ) ); ?>><?php echo esc_html( $charitable_opt ); ?></option>
			<?php endforeach ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( in_array( $charitable_key, $charitable_value ) ); ?>><?php echo esc_html( $charitable_option ); ?></option>
			<?php
		endif;
	endforeach;
	?>
	</select>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo esc_html( $view_args['wrapper_id'] ); ?> -->
