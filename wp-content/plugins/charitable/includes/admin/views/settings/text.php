<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display text field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( empty( $charitable_value ) ) :
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

?>
<input type="text"
	id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
	name="<?php printf( 'charitable_settings[%s]', $view_args['name'] ); // phpcs:ignore ?>"
	value="<?php echo esc_attr( $charitable_value ); ?>"
	class="<?php echo esc_attr( $view_args['classes'] ); ?>"
	<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?> />
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>
	<?php
endif;
