<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display number field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( false === $charitable_value ) {
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
}

$charitable_min = isset( $view_args['min'] ) ? 'min="' . $view_args['min'] . '"' : '';
$charitable_max = isset( $view_args['max'] ) ? 'max="' . $view_args['max'] . '"' : '';
?>
<input type="number"
	id="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>"
	name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
	value="<?php echo esc_attr( $charitable_value ); ?>"
	<?php echo esc_attr( $charitable_min ); ?>
	<?php echo esc_attr( $charitable_max ); ?>
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	/>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
	<?php
endif;
