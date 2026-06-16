<?php
/**
 * Charitable Stripe Gateway Hooks.
 *
 * Action/filter hooks used for handling payments through the Stripe gateway.
 *
 * @package     Charitable Stripe/Hooks/Gateway
 * @author      David Bisset
 * @copyright   Copyright (c) 2018, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.3
 * @version     1.4.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register our new gateway.
 *
 * @see Charitable_Gateway_Stripe_AM::register_gateway()
 */
// add_filter( 'charitable_payment_gateways', [ 'Charitable_Gateway_Stripe_AM', 'register_gateway' ] );

/**
 * Remove the name attribute from credit card fields.
 *
 * @see Charitable_Gateway_Stripe_AM::setup_donation_form_field_output_buffer()
 * @see Charitable_Gateway_Stripe_AM::remove_name_attribute_from_cc_fields()
 */
add_action( 'charitable_form_before_fields', [ 'Charitable_Gateway_Stripe_AM', 'setup_donation_form_field_output_buffer' ] );
add_action( 'charitable_form_after_fields', [ 'Charitable_Gateway_Stripe_AM', 'remove_name_attribute_from_cc_fields' ] );

/**
 * Validate a donation.
 */
add_filter( 'charitable_validate_donation_form_submission_gateway', [ 'Charitable_Gateway_Stripe_AM', 'validate_donation' ], 10, 3 );

/**
 * When a donation is processed, update the Payment Intent with additional information.
 */
add_filter( 'charitable_process_donation_stripe', [ 'Charitable_Gateway_Stripe_AM', 'process_donation' ], 10, 3 );

/**
 * If using Stripe Connect, add hidden acocunt field to the form.
 *
 * @see Charitable_Gateway_Stripe_AM::add_hidden_stripe_account_field()
 */
add_filter( 'charitable_donation_form_hidden_fields', [ 'Charitable_Gateway_Stripe_AM', 'add_hidden_stripe_account_field' ], 10, 2 );

/**
 * Process the Stripe IPN.
 *
 * @see Charitable_Gateway_Stripe_AM::process_ipn()
 */
add_action( 'charitable_process_ipn_stripe', [ 'Charitable_Stripe_Webhook_Processor', 'process' ] );

/**
 * Refund a donation from the dashboard.
 *
 * @see Charitable_Gateway_Stripe_AM::refund_donation_from_dashboard()
 */
add_action( 'charitable_process_refund_stripe', [ 'Charitable_Gateway_Stripe_AM', 'refund_donation_from_dashboard' ] );

/**
 * Returns whether a subscription can be cancelled.
 *
 * @see Charitable_Gateway_Stripe_AM::is_subscription_cancellable()
 */
add_filter( 'charitable_recurring_can_cancel_stripe', [ 'Charitable_Gateway_Stripe_AM', 'is_subscription_cancellable' ], 10, 2 );

/**
 * Cancel a subscription from the dashboard.
 *
 * @see Charitable_Gateway_Stripe_AM::cancel_subscription()
 */
add_action( 'charitable_process_cancellation_stripe', [ 'Charitable_Gateway_Stripe_AM', 'cancel_subscription' ], 10, 2 );

/**
 * Cancel a subscription in Stripe when it's marked as completed by Charitable Recurring.
 *
 * @see Charitable_Gateway_Stripe_AM::process_completed_subscription()
 */
add_action( 'charitable_recurring_donation_completed', [ 'Charitable_Gateway_Stripe_AM', 'process_completed_subscription' ] );