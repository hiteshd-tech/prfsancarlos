<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display radio field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.6.7
 * @version   1.6.41
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$charitable_is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];
$charitable_field_attrs = array_key_exists( 'field_attrs', $view_args ) ? $view_args['field_attrs'] : array();

?>
<div id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>>
	<fieldset class="charitable-radio-fieldset">
		<?php if ( isset( $view_args['label'] ) || isset( $view_args['description'] ) ) : ?>
			<legend>
				<?php
				echo esc_html( $view_args['label'] );
				if ( $charitable_is_required ) :
					?>
					<abbr class="required" title="required">*</abbr>
					<?php
				endif;
				?>
			</legend>
			<?php if ( isset( $view_args['description'] ) ) : ?>
				<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
			<?php endif ?>
		<?php endif ?>
		<ul class="charitable-radio-list">
		<?php foreach ( $view_args['options'] as $charitable_key => $charitable_option ) : ?>
			<li>
				<input type="radio"
					id="<?php echo esc_attr( $view_args['key'] . '-' . $charitable_key ); ?>"
					name="<?php echo esc_attr( $view_args['key'] ); ?>"
					value="<?php echo esc_attr( $charitable_key ); ?>"
					aria-describedby="charitable_field_<?php echo esc_attr( $view_args['key'] ); ?>_label"
					<?php echo charitable_get_arbitrary_attributes( $charitable_field_attrs ); // phpcs:ignore ?>
					<?php checked( $view_args['value'], $charitable_key ); ?>
				/>
				<label for="<?php echo esc_attr( $view_args['key'] . '-' . $charitable_key ); ?>"><?php echo wp_kses_post( $charitable_option ); ?></label>
			</li>
		<?php endforeach ?>
		</ul>
	</fieldset>
</div><!-- #<?php echo $view_args['wrapper_id']; // phpcs:ignore ?> -->
