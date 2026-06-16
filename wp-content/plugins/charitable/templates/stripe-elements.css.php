<?php
/**
 * Renders the custom styles for Stripe Elements added by Charitable.
 *
 * Override this template by copying it to yourtheme/charitable/charitable-stripe/stripe-elements.css.php
 *
 * @author  WP Charitable LLC
 * @package Charitable Stripe/Templates/CSS
 * @since   1.4.0
 * @version 1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style id="charitable-stripe-elements-styles">
	.StripeElement {
		border: 1px solid #ccc;
		padding: 1em;
	}
	#charitable_stripe_card_errors {
		color: #eb1c26;
		font-size: .8em;
		margin: .5em 0 0 0;
	}
</style>
