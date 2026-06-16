<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display the login fields.
 *
 * @author 	WP Charitable LLC
 * @since   1.0.0
 * @version 1.8.9.1
 * @version 1.8.8.6
 */

$charitable_form 			= charitable_get_current_donation_form();
$charitable_account_fields = $charitable_form->get_user_account_fields();
$charitable_user 			= wp_get_current_user();

if ( 0 !== $charitable_user->ID ) {
	return;
}

if ( empty( $charitable_account_fields ) ) {
	return;
}
?>
<div class="charitable-login-details">
	<h4 class="charitable-form-header"><?php esc_html_e( 'Login Details', 'charitable' ); ?></h4>
	<p class="charitable-description"><?php esc_html_e( 'When you make your donation, a new donor account will be created for you.', 'charitable' ); ?></p>
	<?php
	/**
	 * @hook 	charitable_donation_form_before_login_fields
	 */
	do_action( 'charitable_donation_form_before_login_fields' );

	foreach ( $charitable_account_fields as $charitable_key => $charitable_field ) :

		do_action( 'charitable_donation_form_user_field', $charitable_field, $charitable_key, $charitable_form );

	endforeach;

	/**
	 * @hook 	charitable_donation_form_after_login_fields
	 */
	do_action( 'charitable_donation_form_after_login_fields' );
	?>
</div>
