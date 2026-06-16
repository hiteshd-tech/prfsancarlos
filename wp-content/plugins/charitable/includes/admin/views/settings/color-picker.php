<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display color picker field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0.8
 * @version   1.7.0.8
 * @version   1.8.8.6
 */

$charitable_value         = charitable_get_option( $view_args['key'] );
$charitable_default_color = apply_filters( 'charitable_default_highlight_colour', '#f89d35' );

if ( empty( $charitable_value ) ) :
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

?>
<input type="text"
	id="<?php printf( 'charitable_settings_%s', esc_attr( implode( '_', $view_args['key'] ) ) ); ?>"
	name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
	value="<?php echo esc_attr( $charitable_value ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	data-default-color="<?php echo esc_attr( $charitable_default_color ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
	<?php
endif;
