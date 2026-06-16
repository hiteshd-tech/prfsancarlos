<?php
/**
 * Charitable Square Gateway Hooks.
 *
 * Action/filter hooks used for handling payments through the Square gateway.
 *
 * @package     Charitable Square/Hooks/Gateway
 * @author      David Bisset
 * @copyright   Copyright (c) 2018, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Only register Square Core hooks if Square Core is active.
 */
function charitable_maybe_register_square_core_hooks() {
	// Check if Square Core gateway class exists.
	if ( ! class_exists( 'Charitable_Gateway_Square' ) ) {
		if ( charitable_is_debug( 'square' ) ) {
			error_log( '[Charitable Square Core] Hooks not registered - Square Core class not found' ); // phpcs:ignore
		}
		return;
	}

	// Check if Square Core is active.
	if ( ! Charitable_Gateway_Square::is_square_core_active() ) {
		if ( charitable_is_debug( 'square' ) ) {
			error_log( '[Charitable Square Core] Hooks not registered - Square Core is not active' ); // phpcs:ignore
		}
		return;
	}

	if ( charitable_is_debug( 'square' ) ) {
		error_log( '[Charitable Square Core] Registering Square Core hooks' ); // phpcs:ignore
	}

	/**
	 * Register our new gateway.
	 *
	 * @see Charitable_Gateway_Stripe_AM::register_gateway()
	 */
	add_filter( 'charitable_payment_gateways', array( 'Charitable_Gateway_Square', 'register_gateway' ) );

	/**
	 * Save more complex settings for the Square gateway.
	 *
	 * @see Charitable_Gateway_Square::save_settings()
	 */
	add_filter( 'charitable_save_settings', array( Charitable_Gateway_Square::get_instance(), 'save_settings' ), 10, 3 );

	/**
	 * Renew the Square token.
	 *
	 * @see Charitable_Gateway_Square::square_renew_token()
	 */
	add_action( 'admin_init', array( Charitable_Gateway_Square::get_instance(), 'square_renew_token' ), 10 );

	add_action( 'admin_init', array( Charitable_Square_Connect::get_instance(), 'handle_actions' ), 1 );

	/**
	 * Enqueue the Square scripts.
	 *
	 * @see Charitable_Gateway_Square::enqueue_square_scripts()
	 */
	add_action( 'wp_enqueue_scripts', array( Charitable_Gateway_Square::get_instance(), 'enqueue_square_scripts' ), 999 );

	/**
	 * When a donation is processed, update the Payment Intent with additional information.
	 */
	add_filter( 'charitable_process_donation_square_core', array( Charitable_Gateway_Square::get_instance(), 'process_donation' ), 10, 3 );

	// Debug: Log that the Square Core hook is registered.
	if ( charitable_is_debug( 'square' ) ) {
		error_log( '[Square Core] Hook registered: charitable_process_donation_square_core' ); // phpcs:ignore
	}

	/**
	* Refund a donation from the dashboard.
	*
	* @see Charitable_Gateway_Stripe_AM::refund_donation_from_dashboard()
	*/
	add_action( 'charitable_process_refund_square_core', array( Charitable_Gateway_Square::get_instance(), 'refund_donation_from_dashboard' ), 10, 3 );

	// Debug: Log that the Square Core refund hook is registered.
	if ( charitable_is_debug( 'square' ) ) {
		error_log( '[Square Core] Refund Hook registered: charitable_process_refund_square_core' ); // phpcs:ignore
	}

	/**
	* Process the Square IPN.
	*
	* @see Charitable_Gateway_Square::process_ipn()
	*/
	add_action( 'charitable_process_ipn_square_core', array( 'Charitable_Square_Webhook_Processor', 'process' ) );

	// Debug: Log that the Square Core IPN hook is registered.
	if ( charitable_is_debug( 'square' ) ) {
		error_log( '[Square Core] IPN Hook registered: charitable_process_ipn_square_core' ); // phpcs:ignore
	}

	/**
	* Initialize the Square payment gateway.
	*
	* @see Charitable_Square_Initialization::initialize_square()
	*/
	add_action( 'wp_ajax_charitable_square_initialize', array( Charitable_Square_Initialization::get_instance(), 'initialize_square' ) );
	add_action( 'wp_ajax_nopriv_charitable_square_initialize', array( Charitable_Square_Initialization::get_instance(), 'initialize_square' ) );

	/**
	 * Cancel a subscription from the dashboard.
	 *
	 * @see Charitable_Gateway_Square::cancel_subscription()
	 */
	add_action( 'charitable_process_cancellation_square_core', array( Charitable_Gateway_Square::get_instance(), 'cancel_subscription' ), 10, 2 );

	/**
	* Validate a donation.
	*/
	add_filter( 'charitable_validate_donation_form_submission_gateway', array( 'Charitable_Gateway_Square', 'validate_donation' ), 10, 3 );

	/**
	 * Add the 14-day cron schedule.
	 */
	add_filter( 'cron_schedules', array( Charitable_Gateways::get_instance(), 'add_cron_schedules' ) );

	/**
	 * Hook up the Square token renewal cron job.
	 */
	add_action( 'charitable_square_token_renewal', array( Charitable_Gateways::get_instance(), 'renew_square_token_cron' ) );
}

// Register hooks when WordPress is ready.
add_action( 'init', 'charitable_maybe_register_square_core_hooks', 5 );
