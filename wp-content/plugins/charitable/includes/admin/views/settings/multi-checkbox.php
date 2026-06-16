<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display a series of checkboxes.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'], array() );

if ( empty( $charitable_value ) ) :
	$charitable_value = isset( $view_args['default'] ) ? (array) $view_args['default'] : array();
endif;

if ( ! is_array( $charitable_value ) ) {
	$charitable_value = (array) $charitable_value;
}

$charitable_setting_attributes = '';

if ( array_key_exists( 'attrs', $view_args ) ) {
	$charitable_setting_attributes_keys = array( 'data-trigger-key', 'data-trigger-change-type', 'data-trigger-value' );

	foreach ( $charitable_setting_attributes_keys as $charitable_key ) {
		if ( ! array_key_exists( $charitable_key, $view_args['attrs'] ) ) {
			continue;
		}

		$charitable_setting_attributes .= sprintf( ' %s="%s"', $charitable_key, esc_attr( $view_args['attrs'][ $charitable_key ] ) );

		unset( $view_args['attrs'][ $charitable_key ] );
	}
}

?>
<ul class="charitable-checkbox-list <?php echo esc_attr( $view_args['classes'] ); ?>" <?php echo wp_kses_post( $charitable_setting_attributes ); ?>>
	<?php foreach ( $view_args['options'] as $charitable_option => $charitable_label ) : ?>
		<li><input type="checkbox"
				id="<?php printf( 'charitable_settings_%s_%s', esc_attr( implode( '_', $view_args['key'] ) ), esc_attr( $charitable_option ) ); ?>"
				name="<?php printf( 'charitable_settings[%s][]', esc_attr( $view_args['name'] ) ); ?>"
				value="<?php echo esc_attr( $charitable_option ); ?>"
				<?php checked( in_array( $charitable_option, $charitable_value, true ) ); ?>
				<?php echo esc_attr( charitable_get_arbitrary_attributes( $view_args ) ); ?>
			/>
			<?php echo ( $charitable_label ); // phpcs:ignore ?>
		</li>
	<?php endforeach ?>
</ul>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
	<?php
endif;
