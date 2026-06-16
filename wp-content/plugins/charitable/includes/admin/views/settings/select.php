<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display select field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

// Determine if this is a tools field or settings field.
$charitable_is_tools_field = charitable_is_tools_view();

if ( $charitable_is_tools_field ) {
	$charitable_tools_options = get_option( 'charitable_tools', array() );
	$charitable_value = '';
	if ( isset( $view_args['key'] ) && is_array( $view_args['key'] ) ) {
		$charitable_value_path = $charitable_tools_options;
		foreach ( $view_args['key'] as $charitable_key_part ) {
			if ( isset( $charitable_value_path[ $charitable_key_part ] ) ) {
				$charitable_value_path = $charitable_value_path[ $charitable_key_part ];
			} else {
				$charitable_value_path = false;
				break;
			}
		}
		$charitable_value = $charitable_value_path;
	}
	if ( false === $charitable_value ) {
		$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
	}
	$charitable_field_id = sprintf( 'charitable_tools_%s', implode( '_', $view_args['key'] ) );
	$charitable_field_name = sprintf( 'charitable_tools[%s]', $view_args['name'] );
} else {
	$charitable_value = charitable_get_option( $view_args['key'] );
	if ( false === $charitable_value ) {
		$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
	}
	$charitable_field_id = sprintf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) );
	$charitable_field_name = sprintf( 'charitable_settings[%s]', $view_args['name'] );
}
?>
<select id="<?php echo esc_attr( $charitable_field_id ); ?>"
	name="<?php echo esc_attr( $charitable_field_name ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>
	>
	<?php
	foreach ( $view_args['options'] as $charitable_key => $charitable_option ) :
		if ( is_array( $charitable_option ) ) :
			$charitable_label = isset( $charitable_option['label'] ) ? $charitable_option['label'] : '';
			?>
			<optgroup label="<?php echo wp_kses_post( $charitable_label ); ?>">
			<?php foreach ( $charitable_option['options'] as $charitable_k => $charitable_opt ) : ?>
				<option value="<?php echo esc_attr( $charitable_k ); ?>" <?php selected( $charitable_k, $charitable_value ); ?>><?php echo esc_html( $charitable_opt ); ?></option>
			<?php endforeach ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_key, $charitable_value ); ?>><?php echo $charitable_option; // phpcs:ignore ?></option>
			<?php
		endif;
	endforeach
	?>
</select>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>
	<?php
endif;
