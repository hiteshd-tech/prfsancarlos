<?php
/**
 * This class is responsible for setting up and general maintenance of acitivity logs.
 *
 * @package   Charitable/Classes/Charitable_Admin_Donation_Activities
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Donation_Activities' ) ) :

	/**
	 * Charitable_Admin_Donation_Activities
	 *
	 * @since 1.8.1
	 */
	final class Charitable_Admin_Donation_Activities {

		/**
		 * The name of our database table
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $table_name;

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Admin_Donation_Activities|null
		 */
		private static $instance = null;


		/**
		 * Create class object.
		 *
		 * @since  1.8.1
		 */
		private function __construct() {

			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_donation_activities';
		}

		/**
		 * Add a donation activity.
		 *
		 * @since  1.8.1
		 *
		 * @param  int    $donation_id The donation ID.
		 * @param  object $processor   Charitable_Donation_Processor Object.
		 * @param  array  $data        The donation data.
		 * @return void
		 */
		public function add_donation_activity( $donation_id = false, $processor = false, $data = array() ) { // phpcs:ignore

			if ( false === $donation_id ) {
				return;
			}

			if ( ! class_exists( 'Charitable_Donation_Activities_DB' ) ) {
				return;
			}

			$charitable_donation_activities_db = Charitable_Donation_Activities_DB::get_instance();

			// check to see if db tables exist.
			if ( ! $charitable_donation_activities_db->table_exists() ) {
				return;
			}

			$log_data = array();

			$donor_id         = false !== $processor && method_exists( $processor, 'get_donor_id' ) ? $processor->get_donor_id() : false;
			$primary_action   = false !== $processor && method_exists( $processor, 'get_donation_data_value' ) && $processor->get_donation_data_value( 'status' ) ? $processor->get_donation_data_value( 'status' ) : get_post_status( $donation_id );
			$secondary_action = ! empty( $_POST['charitable_action'] ) ? $this->get_activity_slug_from_action( sanitize_text_field( $_POST['charitable_action'] ) ) : false; // phpcs:ignore
			$secondary_action = false === $secondary_action && ! empty( $_POST['action'] ) ? $this->get_activity_slug_from_action( sanitize_text_field( $_POST['action'] ) ) : $secondary_action; // phpcs:ignore

			foreach ( $processor->get_donation_data_value( 'campaigns' ) as $campaign_donation ) {

				$log_data[] = array(
					'donation_id'      => intval( $donation_id ),
					'campaign_id'      => ! empty( $campaign_donation['campaign_id'] ) ? $campaign_donation['campaign_id'] : 0,
					'donor_id'         => $donor_id,
					'type'             => 'donation',
					'primary_action'   => $primary_action,
					'secondary_action' => $secondary_action,
					'amount'           => ! empty( $campaign_donation['amount'] ) ? $campaign_donation['amount'] : false,
					'created_by'       => get_current_user_id(),
					'date_recorded'    => current_time( 'mysql' ),
				);

			}

			$activity = new Charitable_Admin_Activities();

			foreach ( $log_data as $log ) {
				$activity_id = $activity->insert( $log, $this->table_name );
			}
		}

		/**
		 * Add a donation status activity via Charitable_Admin_Actions do_action().
		 *
		 * @since  1.8.1
		 *
		 * @param  int    $success     The success of the action (the publish change), which is being passed from a hook. Not used in this function.
		 * @param  int    $donation_id The donation ID.
		 * @param  array  $args        The donation data or args. This can be empty.
		 * @param  string $action      The action. Example: "change_status_to_charitable-pending".
		 *
		 * @return int
		 */
		public function add_donation_status_activity( $success = false, $donation_id = false, $args = array(), $action = '' ) {

			if ( false === $donation_id || '' === $action ) {
				return;
			}

			if ( strpos( $action, 'change_status_to_charitable-' ) !== 0 ) {
				return;
			}

			$amount       = charitable_get_table( 'campaign_donations' )->get_donation_amount( $donation_id );
			$donor_id     = charitable_get_donation( $donation_id )->get_donor_id();
			$campaign_ids = charitable_get_table( 'campaign_donations' )->get_campaigns_for_donation( $donation_id );
			$campaign_id  = ! empty( $campaign_ids[0] ) ? $campaign_ids[0] : false;

			$primary_action   = str_replace( 'change_status_to_', '', $action );
			$secondary_action = 'change_status';

			$log_data = array(
				'donation_id'      => intval( $donation_id ),
				'campaign_id'      => $campaign_id,
				'donor_id'         => $donor_id,
				'type'             => 'donation',
				'primary_action'   => $primary_action,
				'secondary_action' => $secondary_action,
				'amount'           => $amount,
				'created_by'       => get_current_user_id(),
				'date_recorded'    => current_time( 'mysql' ),
			);

			$activity    = new Charitable_Admin_Activities();
			$activity_id = $activity->insert( $log_data, $this->table_name );

			return $activity_id;
		}

		/**
		 * Get a slug for the activity type. Often this is the same as the action but not always, and is filterable for addons.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $action The action/slug.
		 *
		 * @return string
		 */
		public function get_activity_slug_from_action( $action = false ) {

			if ( false === $action ) {
				return false;
			}

			switch ( $action ) {
				case 'add_donation':
					$action_slug = 'add_donation';
					break;
				case 'update_donation':
					$action_slug = 'update_donation';
					break;
				default:
					$action_slug = sanitize_text_field( $action );
					break;
			}

			return apply_filters( 'charitable_reports_action_slug', $action_slug );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Admin_Donation_Activities
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
