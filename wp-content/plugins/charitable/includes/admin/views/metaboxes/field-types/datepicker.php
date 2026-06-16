<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display datepicker field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.5.0
 * @version   1.6.52
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$charitable_timestamp  = isset( $view_args['timestamp'] ) ? $view_args['timestamp'] : strtotime( $view_args['value'] );
$charitable_i18n       = charitable()->registry()->get( 'i18n' );
$charitable_php_format = $charitable_i18n->get_datepicker_format( 'F d, Y' );
$charitable_js_format  = $charitable_i18n->get_js_datepicker_format( 'MM d, yy' );

if ( array_key_exists( 'value', $view_args ) ) {
	$charitable_date = 'data-date="' . esc_attr( date_i18n( $charitable_php_format, $charitable_timestamp ) ) . '"';
} elseif ( array_key_exists( 'default', $view_args ) ) {
	$charitable_date = 'data-date="' . esc_attr( $view_args['default'] ) . '"';
} else {
	$charitable_date = '';
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
	<input type="text"
		id="<?php echo esc_attr( $view_args['id'] ); ?>"
		name="<?php echo esc_attr( $view_args['key'] ); ?>"
		class="charitable-datepicker"
		autocomplete="off"
		tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
		data-format="<?php echo $charitable_js_format; // phpcs:ignore ?>"
		<?php echo charitable_get_arbitrary_attributes( $charitable_field_attrs ); // phpcs:ignore ?>
		<?php echo $charitable_date; // phpcs:ignore ?>
	/>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo $view_args['wrapper_id']; // phpcs:ignore ?> -->
