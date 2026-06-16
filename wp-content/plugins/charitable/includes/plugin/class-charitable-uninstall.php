<?php
/**
 * Charitable Uninstall class.
 *
 * The responsibility of this class is to manage the events that need to happen
 * when the plugin is deactivated.
 *
 * @package   Charitable/Charitable_Uninstall
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.42
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Uninstall' ) ) :

	/**
	 * Charitable_Uninstall
	 *
	 * @since 1.0.0
	 */
	class Charitable_Uninstall {

		/**
		 * Uninstall the plugin.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( charitable()->is_deactivation() && charitable_get_option( 'delete_data_on_uninstall' ) ) {

				$this->remove_caps();
				$this->remove_post_data();
				$this->remove_tables();
				$this->remove_settings();

				do_action( 'charitable_uninstall' );
			}
		}

		/**
		 * Remove plugin-specific roles.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function remove_caps() {
			$roles = new Charitable_Roles();
			$roles->remove_caps();
		}

		/**
		 * Remove post objects created by Charitable.
		 *
		 * @since  1.0.0
		 *
		 * @global WPDB $wpdb The WordPress database object.
		 * @return void
		 */
		private function remove_post_data() {
			global $wpdb;

			$posts = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type IN ( 'donation', 'campaign' );" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		/**
		 * Remove the custom tables added by Charitable.
		 *
		 * @since  1.0.0
		 * @version 1.8.1
		 *
		 * @global WPDB $wpdb The WordPress database object.
		 * @return void
		 */
		private function remove_tables() {
			global $wpdb;

			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_campaign_donations' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_donors' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_donormeta' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_benefactors' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_donation_activities' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'charitable_campaign_activities' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

			delete_option( $wpdb->prefix . 'charitable_campaign_donations_db_version' );
			delete_option( $wpdb->prefix . 'charitable_donors_db_version' );
			delete_option( $wpdb->prefix . 'charitable_donormeta_db_version' );
			delete_option( $wpdb->prefix . 'charitable_benefactors_db_version' );
			delete_option( $wpdb->prefix . 'charitable_donation_activities_db_version' );
			delete_option( $wpdb->prefix . 'charitable_campaign_activities_db_version' );
		}

		/**
		 * Remove any other options added by Charitable.
		 *
		 * @since  1.6.42
		 *
		 * @return void
		 */
		private function remove_settings() {
			delete_option( 'charitable_settings' );
			delete_option( 'charitable_version' );
			delete_option( 'charitable_upgrade_log' );
			delete_option( 'charitable_skipped_donations_with_empty_donor_id' );

			delete_transient( 'charitable_notices' );
			delete_transient( 'charitable_user_dashboard_objects' );
			delete_transient( 'charitable_custom_styles' );

			/* Stop Charitable from re-adding the notices transient. */
			if ( function_exists( 'charitable_get_admin_notices' ) ) {
				remove_action( 'shutdown', array( charitable_get_admin_notices(), 'shutdown' ) );
			}
		}
	}

endif;
