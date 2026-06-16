<?php
/**
 * Displays the notice to say that the user cannot access the donation receipt.
 *
 * Override this template by copying it to yourtheme/charitable/donation-receipt/not-authorized.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Receipt
 * @since   1.1.2
 * @version 1.6.27
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_content = $view_args['content'];

if ( is_user_logged_in() ) : ?>
	<div class="charitable-notice">
		<?php esc_html_e( 'You do not have access to this donation receipt.', 'charitable' ); ?>
	</div>
<?php else : ?>
	<div class="charitable-notice">
		<?php
			esc_html_e( 'You must be logged in to access your donation receipt.', 'charitable' );

			/* Display any other notices. */
			charitable_template( 'form-fields/notices.php', array( 'notices' => charitable_get_notices()->get_notices() ) );

			/**
			 * Unhook the default template notices function from showing, as it would
			 * result in another <noscript> element being created inside of this one.
			 *
			 * @see https://github.com/Charitable/Charitable/issues/715
			 */
			remove_action( 'charitable_login_form_before', 'charitable_template_notices', 10, 0 );
		?>
	</div>
	<?php
		echo Charitable_Login_Shortcode::display( array( 'redirect' => esc_url( charitable_get_current_url() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/* Turn the login form notices hook back on. */
		add_action( 'charitable_login_form_before', 'charitable_template_notices', 10, 0 );
endif;
