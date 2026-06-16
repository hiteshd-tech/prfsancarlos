<?php
/**
 * Charitable Email Hooks.
 *
 * Action/filter hooks used for Charitable emails.
 *
 * @package   Charitable/Functions/Emails
 * @version   1.5.0
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Charitable emails.
 *
 * @see Charitable_Emails::register_emails()
 */
add_action( 'init', array( Charitable_Emails::get_instance(), 'register_emails' ) );

/**
 * Register admin actions for Charitable emails.
 *
 * @see Charitable_Emails::register_email_admin_actions()
 */
add_action( 'admin_init', array( Charitable_Emails::get_instance(), 'register_admin_actions' ) );

/**
 * Send the Donation Receipt and Donation Notification emails.
 *
 * Both of these emails are sent immediately a donation has been completed.
 *
 * @see Charitable_Email_Donation_Receipt::send_with_donation_id()
 * @see Charitable_Email_New_Donation::send_with_donation_id()
 * @see Charitable_Email_Offline_Donation_Receipt::send_with_donation_id()
 * @see Charitable_Email_Offline_Donation_Notification::send_with_donation_id()
 */

add_action( 'charitable_after_save_donation', array( 'Charitable_Email_Donation_Receipt', 'send_with_donation_id' ) );
add_action( 'charitable_after_save_donation', array( 'Charitable_Email_New_Donation', 'send_with_donation_id' ) );
add_action( 'charitable_after_save_donation', array( 'Charitable_Email_Offline_Donation_Receipt', 'send_with_donation_id' ) );
add_action( 'charitable_after_save_donation', array( 'Charitable_Email_Offline_Donation_Notification', 'send_with_donation_id' ) );

/**
 * Send the Campaign Ended email.
 *
 * This email can be sent to any recipients, within 24 hours after a campaign has reached its end date.
 *
 * @see Charitable_Email_Campaign_End::send_with_campaign_id()
 */
add_action( 'charitable_campaign_end', array( 'Charitable_Email_Campaign_End', 'send_with_campaign_id' ) );

/**
 * Enable & disable emails.
 *
 * @see Charitable_Emails::handle_email_settings_request()
 */
add_action( 'charitable_enable_email', array( Charitable_Emails::get_instance(), 'handle_email_settings_request' ) );
add_action( 'charitable_disable_email', array( Charitable_Emails::get_instance(), 'handle_email_settings_request' ) );

/**
 * Process deferred emails scheduled during AJAX to avoid Oxygen output buffer conflicts.
 *
 * When Oxygen + Braintree + Recurring donations are active, email sending is deferred
 * via wp_cron to prevent email HTML from contaminating the AJAX JSON response.
 *
 * @since 1.8.9.6
 *
 * @param array $email_data Contains 'class', 'args', and 'timestamp'.
 */
add_action( 'charitable_send_deferred_email', function( $email_data ) {
	if ( empty( $email_data['class'] ) || ! class_exists( $email_data['class'] ) ) {
		return;
	}

	$args  = isset( $email_data['args'] ) ? $email_data['args'] : array();
	$class = $email_data['class'];

	// Reconstruct donation object from ID.
	$donation = null;
	if ( ! empty( $args['donation_id'] ) ) {
		$donation = charitable_get_donation( $args['donation_id'] );
	}

	if ( ! $donation ) {
		return;
	}

	// Reconstruct campaign donations.
	$campaign_donations = $donation->get_campaign_donations();

	// Clean output buffers to ensure no contamination.
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	// Instantiate and send the email.
	$email_object = new $class( array(
		'donation' => $donation,
		'campaign' => ! empty( $campaign_donations ) ? charitable_get_campaign( current( $campaign_donations )->campaign_id ) : null,
	) );

	if ( method_exists( $email_object, 'send' ) ) {
		$email_object->send();
	}
} );
