<?php
/**
 * Displays the logged in message.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/logged-in.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Account
 * @since   1.0.0
 * @version 1.5.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_message = isset( $view_args['logged_in_message'] )
	? $view_args['logged_in_message']
	: __( 'You are already logged in!', 'charitable' );

echo wp_kses_post( wpautop( $charitable_message ) );

?>
<a href="<?php echo esc_url( wp_logout_url( charitable_get_current_url() ) ) ?>"><?php esc_html_e( 'Logout.', 'charitable' ) ?></a>
