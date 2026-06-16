<?php
/**
 * The template used to display the default form.
 *
 * Override this template by copying it to yourtheme/charitable/donation-form/form-donation.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.6.57
 * @version 1.8.3.5 Added $form_class to allow for charitable-minimal beta.
 * @version 1.8.4.2 Added charitable_donation_after_form_submit_button action.
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_form       = $view_args['form'];
$charitable_user       = wp_get_current_user();
$charitable_use_ajax   = 'make_donation' == $charitable_form->get_form_action() && (int) Charitable_Gateways::get_instance()->gateways_support_ajax();
$charitable_form_id    = isset( $view_args['form_id'] ) ? $view_args['form_id'] : charitable_get_donation_form_id();
$charitable_form_class = ! empty( $view_args['form_template'] ) ? apply_filters( 'charitable_donation_form_class', 'charitable-form charitable-donation-form charitable-template-' . esc_attr( $view_args['form_template'] ), $charitable_form ) : apply_filters( 'charitable_donation_form_class', 'charitable-form charitable-donation-form charitable-template-standard', $charitable_form ); // allows for charitable-minimal.

if ( ! $charitable_form ) {
	return;
}

?>
<form method="post" id="<?php echo esc_attr( $charitable_form_id ); ?>" class="<?php echo esc_attr( $charitable_form_class ); ?>" data-use-ajax="<?php echo esc_attr( $charitable_use_ajax ); ?>">
	<?php
	/**
	 * Do something before rendering the form fields.
	 *
	 * @since 1.0.0
	 * @since 1.6.0 Added $view_args parameter.
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
	 * @since 1.6.0 Added $view_args parameter.
	 *
	 * @param Charitable_Form $form      The form object.
	 * @param array           $view_args All args passed to template.
	 */
	do_action( 'charitable_form_after_fields', $charitable_form, $view_args );

	/**
	 * Add filter to determine if the submit button should appear for the donation form.
	 * 99.9% it should BUT there are cases - like spam busting - where we want to remove the ability for the donate form to be submitted.
	 *
	 * @since 1.7.0.9
	 */
	$charitable_show_donation_button = apply_filters( 'charitable_show_donation_form_button', true, $charitable_form, $view_args );

	if ( $charitable_show_donation_button ) :
		?>
	<div class="charitable-form-field charitable-submit-field">
		<button class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>" type="submit" name="donate"><?php esc_html_e( 'Donate', 'charitable' ); ?></button>
		<?php do_action( 'charitable_donation_after_form_submit_button', $charitable_form, $view_args ); ?>
		<div class="charitable-form-processing" style="display: none;">
			<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/charitable-loading.gif" width="60" height="60" alt="<?php esc_attr_e( 'Loading&hellip;', 'charitable' ); ?>" />
		</div>
	</div>
	<?php endif; ?>
</form><!-- #<?php echo esc_html( $charitable_form_id ); ?>-->
