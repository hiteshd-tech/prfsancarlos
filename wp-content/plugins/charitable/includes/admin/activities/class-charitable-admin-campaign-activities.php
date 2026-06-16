<?php
/**
 * This class is responsible for setting up and general maintenance of acitivity logs.
 *
 * @package   Charitable/Classes/Charitable_Admin_Campaign_Activities
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



if ( ! class_exists( 'Charitable_Admin_Campaign_Activities' ) ) :

	/**
	 * Charitable_Admin_Campaign_Activities
	 *
	 * @since 1.8.1
	 */
	final class Charitable_Admin_Campaign_Activities {

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
		 * @var     Charitable_Admin_Campaign_Activities|null
		 */
		private static $instance = null;


		/**
		 * Create class object.
		 *
		 * @since  1.8.1
		 */
		private function __construct() {

			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_campaign_activities';
		}

		/**
		 * Add a campaign activity after a post status transition.
		 *
		 * @since  1.8.1
		 * @param  string $new_status The new status. Example: "publish".
		 * @param  string $old_status The old status. Example: "draft".
		 * @param  object $post       The post object.
		 */
		public function add_campaign_activity_transition_post_status( $new_status = false, $old_status = false, $post = null ) {

			// For doing stuff when moving in and out (!) of published status only.
			if ( empty( $post->ID ) || $old_status === $new_status || $old_status !== 'publish' && $new_status !== 'publish' ) {
				return;
			}

			// For now, limit to just when a campaign is pubhlished.
			if ( 'publish' !== $new_status ) {
				return;
			}

			$campaign_id = $post->ID;
			$action      = 'update';
			$data        = (array) $post;

			$this->add_campaign_activity( $campaign_id, $data, $action, $new_status );
		}

		/**
		 * Add a campaign activity.
		 *
		 * @since  1.8.1
		 * @param  int    $campaign_id The campaign ID.
		 * @param  array  $data The campaign data.
		 * @param  string $primary_action The primary action to write to the db table.
		 * @param  string $secondary_action The secondary action to write to the db table.
		 */
		public function add_campaign_activity( $campaign_id = false, $data = array(), $primary_action = '', $secondary_action = '' ) { // phpcs:ignore

			if ( false === $campaign_id || empty( $data ) ) {
				return;
			}

			if ( ! class_exists( 'Charitable_Campaign_Activities_DB' ) ) {
				return;
			}

			$charitable_campaign_activities_db = Charitable_Campaign_Activities_DB::get_instance();

			// check to see if db tables exist.
			if ( ! $charitable_campaign_activities_db->table_exists() ) {
				return;
			}

			$log_data = array();

			// Filter the primary action.
			$primary_action = esc_attr( $primary_action );

			// If we aren't passed a secondary action, attempt to add one from a $_POST value.
			$secondary_action = '' === $secondary_action && ! empty( $_POST['charitable_action'] ) ? $this->get_activity_slug_from_action( esc_attr( $_POST['charitable_action'] ) ) : $secondary_action; // phpcs:ignore
			$secondary_action = false === $secondary_action && ! empty( $_POST['action'] ) ? $this->get_activity_slug_from_action( esc_attr( $_POST['action'] ) ) : $secondary_action; // phpcs:ignore

			$log_data[] = array(
				'campaign_id'      => intval( $campaign_id ),
				'type'             => 'campaign',
				'primary_action'   => $primary_action,
				'secondary_action' => $secondary_action,
				'created_by'       => get_current_user_id(),
				'date_recorded'    => current_time( 'mysql' ),
			);

			$activity = new Charitable_Admin_Activities();

			foreach ( $log_data as $log ) {
				$activity_id = $activity->insert( $log, $this->table_name );
			}
		}

		/**
		 * Add a campaign status activity via Charitable_Admin_Actions do_action().
		 * Empty/placeholder function that is already deprecated in favor of add_campaign_activity_transition_post_status().
		 *
		 * @since  1.8.1
		 *
		 * @param  bool   $success     Whether the previous passed in action was successful.
		 * @param  int    $campaign_id The campaign ID.
		 * @param  array  $args        The campaign data or args. This can be empty.
		 * @param  string $action      The action. Example: "change_status_to_charitable-pending".
		 * @return void
		 */
		public function add_campaign_status_activity( $success = false, $campaign_id = false, $args = array(), $action = '' ) {

			if ( false === $campaign_id || '' === $action ) {
				return;
			}

			if ( strpos( $action, 'change_status_to_charitable-' ) !== 0 ) {
				return;
			}
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
				case 'add_campaign':
					$action_slug = 'add_campaign';
					break;
				case 'add_campaign':
					$action_slug = 'update_campaign';
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
		 * @return Charitable_Admin_Campaign_Activities
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
