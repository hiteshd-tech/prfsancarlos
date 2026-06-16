<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the donation form fields options.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = $view_args['classes'];
$charitable_options     = isset( $charitable_field['options'] ) ? $charitable_field['options'] : array();
$charitable_value       = isset( $charitable_field['value'] ) ? (array) $charitable_field['value'] : array();
$charitable_placeholder = isset( $charitable_field['placeholder'] ) ? $charitable_field['placeholder'] : '';

if ( empty( $charitable_options ) ) {
	return;
}
?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>">
			<?php echo wp_kses_post( $charitable_field['label'] ); ?>
		</label>
	<?php endif ?>
	<ul class="options">
	<?php foreach ( $charitable_options as $charitable_val => $charitable_label ) : ?>
		<li>
			<input type="checkbox" name="<?php echo esc_attr( $charitable_field['key'] ); ?>[]" value="<?php echo esc_attr( $charitable_val ); ?>" <?php checked( in_array( $charitable_val, $charitable_value ) ); ?> />
			<?php echo wp_kses_post( $charitable_label ); ?>
		</li>
	<?php endforeach ?>
	</ul>
</div>
