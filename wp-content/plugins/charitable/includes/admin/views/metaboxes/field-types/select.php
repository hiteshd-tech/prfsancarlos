<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display select field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.4.6
 * @version   1.8.9.1
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

if ( false !== strpos( $view_args['wrapper_class'], 'select2' ) ) {
	wp_enqueue_script( 'select2' );
	wp_enqueue_style( 'select2-css' );
}

$charitable_is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];

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
		<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	>
	<?php
	foreach ( $view_args['options'] as $charitable_key => $charitable_option ) :
		if ( is_array( $charitable_option ) ) :
			$charitable_label = isset( $charitable_option['label'] ) ? $charitable_option['label'] : '';
			?>
			<optgroup label="<?php echo esc_attr( $charitable_label ); ?>">
			<?php foreach ( $charitable_option['options'] as $charitable_k => $charitable_opt ) : ?>
				<option value="<?php echo esc_attr( $charitable_k ); ?>" <?php selected( $charitable_k, $view_args['value'] ); ?>><?php echo esc_html( $charitable_opt ); ?></option>
			<?php endforeach ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_key, $view_args['value'] ); ?>><?php echo esc_html( $charitable_option ); ?></option>
			<?php
		endif;
	endforeach;
	?>
	</select>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo esc_html( $view_args['wrapper_id'] ); ?> -->
