<?php
/**
 * The template used to display the donor's current details.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.6.55
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var Charitable_User
 */
$charitable_user = $view_args['user'];

if ( ! $charitable_user && ! is_customize_preview() ) {
	return;
}

?>
<div class="charitable-donor-details">
	<address class="donor-address"><?php echo wp_kses_post( $charitable_user->get_address() ); ?></address>
	<p class="donor-contact-details">
		<?php
		/* translators: %s: email address */
		printf( esc_html__( 'Email: %s', 'charitable' ), esc_html( $charitable_user->user_email ) );

		if ( ! empty( $charitable_user->get( 'donor_phone' ) ) ) :
			/* translators: %s: phone number */
			echo '<br />' . sprintf( esc_html__( 'Phone number: %s', 'charitable' ), esc_html( $charitable_user->get( 'donor_phone' ) ) );
		endif;
		?>
	</p>
	<p class="charitable-change-user-details">
		<a href="#" data-charitable-toggle="charitable-user-fields"><?php esc_html_e( 'Update your details', 'charitable' ); ?></a>
	</p><!-- .charitable-change-user-details -->
</div><!-- .charitable-donor-details -->
