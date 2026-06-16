<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display date field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9
 * @version   1.8.9
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
				$charitable_value_path = '';
				break;
			}
		}
		$charitable_value = $charitable_value_path;
	}
	if ( empty( $charitable_value ) ) {
		$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
	}
	$charitable_field_id = sprintf( 'charitable_tools_%s', implode( '_', $view_args['key'] ) );
	$charitable_field_name = sprintf( 'charitable_tools[%s]', $view_args['name'] );
} else {
	$charitable_value = charitable_get_option( $view_args['key'] );
	if ( empty( $charitable_value ) ) {
		$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
	}
	$charitable_field_id = sprintf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) );
	$charitable_field_name = sprintf( 'charitable_settings[%s]', $view_args['name'] );
}

?>
<input type="date"
	id="<?php echo esc_attr( $charitable_field_id ); ?>"
	name="<?php echo esc_attr( $charitable_field_name ); ?>"
	value="<?php echo esc_attr( $charitable_value ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?> />
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>
	<?php
endif;

