<?php
/**
 * Charitable Admin Activity Hooks.
 *
 * Action/filter hooks used for setting up activity monitoring in the admin.
 *
 * @package   Charitable/Functions/Activity
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * When a donation is saved to the database, add it to the activity log.
 *
 * @see Charitable_Admin_Donation_Activities::add_donation_activity()
 */
add_action( 'charitable_after_save_donation', array( Charitable_Admin_Donation_Activities::get_instance(), 'add_donation_activity' ), 11, 2 );

/**
 * When a donation status is updated, add it to the activity log.
 *
 * @see Charitable_Admin_Donation_Activities::add_donation_activity()
 */
add_filter( 'charitable_donation_admin_action_change_status_to_charitable-pending', array( Charitable_Admin_Donation_Activities::get_instance(), 'add_donation_status_activity' ), 99, 4 );
add_filter( 'charitable_donation_admin_action_change_status_to_charitable-completed', array( Charitable_Admin_Donation_Activities::get_instance(), 'add_donation_status_activity' ), 99, 4 );
add_filter( 'charitable_donation_admin_action_change_status_to_charitable-cancelled', array( Charitable_Admin_Donation_Activities::get_instance(), 'add_donation_status_activity' ), 99, 4 );
add_filter( 'charitable_donation_admin_action_change_status_to_charitable-refunded', array( Charitable_Admin_Donation_Activities::get_instance(), 'add_donation_status_activity' ), 99, 4 );

/**
 * When a campaign status is updated, add it to the activity log.
 *
 * @see Charitable_Admin_Campaign_Activities::add_campaign_activity()
 */
add_action( 'transition_post_status', array( Charitable_Admin_Campaign_Activities::get_instance(), 'add_campaign_activity_transition_post_status' ), 11, 3 );
