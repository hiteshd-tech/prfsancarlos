<?php
/**
 * The template used to display a message on the email verification endpoint
 * when the email was not verified.
 *
 * Override this template by copying it to yourtheme/charitable/account/email-not-verified.php
 *
 * @author  David Bisset
 * @package Charitable/Templates/Account
 * @since   1.5.0
 * @version 1.6.57
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$redirect = array_key_exists( 'redirect_url', $_GET ) ? esc_html( $_GET['redirect_url'] ) : false; // phpcs:ignore
$user     = get_user_by( 'login', esc_html( $_GET['login'] ) ); // phpcs:ignore

if ( ! empty( $user ) ) {
	$link = charitable_get_email_verification_link( $user, $redirect, true ); // phpcs:ignore
}

?>
<p><?php esc_html_e( 'We were unable to verify your email address.', 'charitable' ); ?></p>
<?php if ( ! empty( $user ) ) : ?>
	<p><a href="<?php echo esc_url_raw( $link ); ?>"><?php esc_html_e( 'Resend verification email', 'charitable' ); ?></a></p>
<?php endif ?>
