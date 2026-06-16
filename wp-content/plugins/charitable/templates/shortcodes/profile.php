<?php
/**
 * The template used to display the profile form.
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

$charitable_form  = $view_args['form'];
$charitable_donor = charitable_get_user( wp_get_current_user() );

/**
 * Do something before rendering the user profile form.
 *
 * @param array $view_args All args passed to template.
 */
do_action( 'charitable_user_profile_before', $view_args );

?>
<form method="post" id="charitable-profile-form" class="charitable-form" enctype="multipart/form-data">
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
		<?php $charitable_form->view()->render() ?>
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
		<button class="<?php echo esc_attr( charitable_get_button_class( 'profile' ) ); ?>" type="submit" name="update-profile"><?php echo esc_html( apply_filters( 'charitable_profile_form_submit_button_name', __( 'Update', 'charitable' ) ) ); ?></button>
	</div>
</form><!-- #charitable-profile-form -->
<?php
/**
 * Do something after rendering the user profile form.
 *
 * @param array $view_args All args passed to template.
 */
do_action( 'charitable_user_profile_after', $view_args );
