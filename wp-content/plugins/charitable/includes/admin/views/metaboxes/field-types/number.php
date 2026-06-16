<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display number field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.6.36
 * @version   1.6.36
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$charitable_is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];
$charitable_field_attrs = array_key_exists( 'field_attrs', $view_args ) ? $view_args['field_attrs'] : array();

?>
<div id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>>
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
	<input type="number"
		id="<?php echo esc_attr( $view_args['id'] ); ?>"
		name="<?php echo esc_attr( $view_args['key'] ); ?>"
		value="<?php echo esc_attr( $view_args['value'] ); ?>"
		tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
		<?php echo $charitable_is_required ? 'required' : ''; ?>
		<?php echo charitable_get_arbitrary_attributes( $charitable_field_attrs ); // phpcs:ignore ?>
	/>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div>
