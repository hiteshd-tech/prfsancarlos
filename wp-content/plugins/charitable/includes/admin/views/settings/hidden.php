<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a hidden field in settings area.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

?>
<input type="hidden"
	id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
	name="<?php printf( 'charitable_settings[%s]', $view_args['name'] ); // phpcs:ignore ?>"
	value="<?php echo esc_attr( $view_args['value'] ); ?>"
/>
