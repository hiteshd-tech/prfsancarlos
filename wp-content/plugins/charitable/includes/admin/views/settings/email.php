<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display email field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( empty( $charitable_value ) ) :
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

$charitable_escaped_key  = implode( '_', $view_args['key'] );
$charitable_escaped_name = $view_args['name'];

?>
<input type="email"
	id="charitable_settings_<?php echo esc_attr( $charitable_escaped_key ); ?>"
	name="charitable_settings[<?php echo esc_attr( $charitable_escaped_name ); ?>]"
	value="<?php echo esc_attr( $charitable_value ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
/>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
<?php endif; ?>
