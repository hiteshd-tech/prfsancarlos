<?php
/**
 * The template used to display the donation amount inputs.
 *
 * Override this template by copying it to yourtheme/charitable/donation-form/donation-amount.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.5.0
 * @version 1.8.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) ) {
	return;
}

/* @var Charitable_Donation_Form */
$charitable_form     = $view_args['form'];
$charitable_form_id  = $charitable_form->get_form_identifier();
$charitable_campaign = $charitable_form->get_campaign();

if ( is_null( $charitable_campaign ) ) {
	return;
}

$charitable_suggested       = $charitable_campaign->get_suggested_donations();
$charitable_currency_helper = charitable_get_currency_helper();

if ( empty( $charitable_suggested ) && ! $charitable_campaign->get( 'allow_custom_donations' ) ) {
	return;
}

/**
 * Do something before the donation options fields.
 *
 * @since 1.0.0
 *
 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
 */
do_action( 'charitable_donation_form_before_donation_amount', $charitable_form );

?>
<div class="charitable-donation-options">
	<?php
	/**
	 * Do something before the donation amounts are listed.
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
	 */
	do_action( 'charitable_donation_form_before_donation_amounts', $charitable_form );

	charitable_template_from_session(
		'donation-form/donation-amount-list.php',
		array(
			'campaign' => $charitable_campaign,
			'form_id'  => $charitable_form_id,
		),
		'donation_form_amount_field',
		array(
			'campaign_id' => $charitable_campaign->ID,
			'form_id'     => $charitable_form_id,
		)
	);

	/**
	 * Do something after the donation amounts are listed.
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
	 */
	do_action( 'charitable_donation_form_after_donation_amounts', $charitable_form );
	?>
</div><!-- .charitable-donation-options -->
<?php

/**
 * Do something after the donation options fields.
 *
 * @since 1.0.0
 *
 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
 */
do_action( 'charitable_donation_form_after_donation_amount', $charitable_form );
