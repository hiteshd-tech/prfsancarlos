<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display checkbox field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( ! strlen( $charitable_value ) ) {
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : 0;
}

?>

<div class="charitable-admin-ui charitable-admin-ui-field charitable-admin-ui-toggle">

	<div id="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>-wrap" class="charitable-panel-field charitable-panel-field-toggle">
		<span class="charitable-toggle-control">
		<input type="checkbox" id="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>" name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>" value="1" class="<?php echo esc_attr( $view_args['classes'] ); ?>" <?php checked( $charitable_value ); ?> <?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>/>
		<label class="charitable-toggle-control-icon" for="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>"></label>
		<?php if ( isset( $view_args['label'] ) ) : ?>
			<label for="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>"><?php echo esc_html( $view_args['label'] ); ?></label>
		<?php endif; ?>
		</span>
		<?php if ( isset( $view_args['help'] ) ) : ?>
			<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
		<?php endif; ?>
	</div>

</div>