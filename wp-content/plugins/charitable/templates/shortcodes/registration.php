<?php
/**
 * The template used to display the registration form.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Account
 * @since   1.0.0
 * @version 1.6.29
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_form = $view_args['form'];

/**
 * Do something before rendering the user registration form.
 *
 * @param Charitable_Form $form      The form object.
 * @param array           $view_args All args passed to template.
 */
do_action( 'charitable_user_registration_before', $charitable_form, $view_args );

?>
<form method="post" id="charitable-registration-form" class="charitable-form">
	<?php
	/**
	 * Do something before rendering the form fields.
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Form $form      The form object.
	 * @param array           $view_args All args passed to template.
	 */
	do_action( 'charitable_form_before_fields', $charitable_form, $view_args );

	?>
	<div class="charitable-form-fields cf">
		<?php $charitable_form->view()->render(); ?>
	</div><!-- .charitable-form-fields -->
	<?php

	/**
	 * Do something after rendering the form fields.
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Form $form      The form object.
	 * @param array           $view_args All args passed to template.
	 */
	do_action( 'charitable_form_after_fields', $charitable_form, $view_args );

	?>
	<div class="charitable-form-field charitable-submit-field">
		<button class="<?php echo esc_attr( charitable_get_button_class( 'registration' ) ); ?>" type="submit" name="register"><?php esc_attr_e( 'Register', 'charitable' ); ?></button>
	</div>
</form><!-- #charitable-registration-form -->
<?php

/**
 * Do something after rendering the user registration form.
 *
 * @param Charitable_Form $form      The form object.
 * @param array           $view_args All args passed to template.
 */
do_action( 'charitable_user_registration_after', $charitable_form, $view_args );
