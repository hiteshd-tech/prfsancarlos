<?php
/**
 * Charitable Advanced Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Advanced_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Advanced_Settings' ) ) :

	/**
	 * Charitable_Advanced_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_Advanced_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Advanced_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Advanced_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the advanced tab settings fields.
		 *
		 * @since 1.0.0
		 * @since 1.8.1.4 Added the 'charitable_settings_advanced_fields' filter.
		 *
		 * @return  array<string,array>
		 */
		public function add_advanced_fields() {
			if ( ! charitable_is_settings_view( 'advanced' ) ) {
				return array();
			}

			return apply_filters(
				'charitable_settings_advanced_fields',
				array(
					'section_hidden_advanced'      => array(
						'title'    => '',
						'type'     => 'hidden',
						'priority' => 10000,
						'value'    => 'advanced',
					),
					'section_dangerous'            => array(
						'title'    => __( 'Advanced Settings', 'charitable' ),
						'type'     => 'heading',
						'class'    => 'section-heading',
						'priority' => 100,
					),
					'delete_data_on_uninstall'     => array(
						'label_for' => __( 'Reset Data', 'charitable' ),
						'type'      => 'checkbox',
						'help'      => '<span style="color:red;font-weight:bold;">' . __( 'DELETE ALL DATA', 'charitable' ) . '</span> ' . __( 'when uninstalling the plugin.', 'charitable' ),
						'priority'  => 105,
					),
					'clear_expire_options'         => array(
						'label_for' => __( 'Clear Cache', 'charitable' ),
						'type'      => 'checkbox',
						'help'      => __( 'This removes and refreshes temporarily stored items in the database specific to Charitable.', 'charitable' ),
						'priority'  => 120,
					),
					'clear_activity_database'      => array(
						'label_for' => __( 'Activity Database', 'charitable' ),
						'type'      => 'select',
						'options'   => array(
							''       => __( 'No action', 'charitable' ), // phpcs:ignore
							'clear'  => __( 'Clear database records', 'charitable' ),
							'remove' => __( 'Remove database tables', 'charitable' ),
						),
						'help'      => __( 'This clears or removes the custom database tables related to activities tracked by Charitable.', 'charitable' ),
						'priority'  => 150,
					),
					'disable_campaign_legacy_mode' => array(
						'label_for' => __( 'Disable Legacy Mode', 'charitable' ),
						'type'      => 'checkbox',
						'help'      => /* translators: %1$s: URL to the documentation. */
										sprintf( __( 'Determines ability for admin users to add new legacy campaigns. <a href="%1$s">Learn More</a>.', 'charitable' ), 'https://www.wpcharitable.com/documentation/legacy-campaigns/' ),
						'priority'  => 155,
						'default'   => true,
					),
					'charitable_usage_tracking' => array(
						'label_for' => __( 'Usage Tracking', 'charitable' ),
						'type'      => 'checkbox',
						'help'      => /* translators: %1$s: URL to the documentation. */
										sprintf( __( 'Allows us to better help you as we know which WordPress configurations, themes and plugins we should test. <a href="%1$s">Learn More</a>.', 'charitable' ), 'https://www.wpcharitable.com/documentation/usage-tracking/' ),
						'priority'  => 155,
						'default'   => false,
					),
					'section_hidden_licenses'      => array(
						'title'    => '',
						'type'     => 'hidden',
						'priority' => 10000,
						'value'    => 'licenses',
						'save'     => false,
					),
					'section_licenses'             => array(
						'title'    => __( 'Legacy Licenses', 'charitable' ),
						'type'     => 'heading',
						'class'    => 'section-heading',
						'priority' => 202,
					),
					'licenses'                     => array(
						'title'    => false,
						'callback' => array( $this, 'render_licenses_table' ),
						'priority' => 204,
					),
				)
			);
		}

		/**
		 * Add the licenses group.
		 *
		 * @since   1.0.0
		 *
		 * @param   string[] $groups Settings groups.
		 * @return  string[]
		 */
		public function add_licenses_group( $groups ) {
			$groups['licenses'] = array();
			return $groups;
		}

		/**
		 * Render the licenses table.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function render_licenses_table() {
			charitable_admin_view( 'settings/licenses' );
		}

		/**
		 * Removes select options that might be causing trouble or unwanted notices, mostly items that we done pre-1.7.0. Also known as 'clear cache' or 'clearing cache'.
		 *
		 * @since   1.7.0.7
		 * @version 1.8.1.12 Added expiringlicense and expiredlicense.
		 * @version 1.8.3 Added charitable_notifications.
		 * @version 1.8.3.1 Added charitable_cache_cleared.
		 * @version 1.8.6 Added charitable_splash_version.
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 * @return  mixed[]
		 */
		public function clear_expired_options( $values, $new_values ) {

			/* If this option isn't in the return values or isn't checked off, leave. */
			if ( ! isset( $new_values['clear_expire_options'] ) || 0 === intval( $new_values['clear_expire_options'] ) ) {
				return $values;
			}

			// Remove the options.
			delete_option( 'charitable_doing_upgrade' );
			delete_option( 'charitable_third_party_warnings' );
			delete_option( 'charitable_campaign_builder_templates' ); // v1.8.0.
			delete_option( 'charitable_growth_tool_notices' ); // v1.8.1.6.
			delete_option( 'charitable_lite_settings_upgrade' );
			delete_option( 'charitable_lite_reports_upgrade' );
			delete_option( 'charitable_builder_onboarding' ); // v1.8.1.12.
			delete_option( 'charitable_onboarding_checklist' ); // v1.8.2.
			delete_option( 'charitable_notifications' ); // v1.8.3.
			delete_option( 'charitable_usage_tracking_last_checkin' ); // v1.8.4.
			delete_option( 'charitable_tracking_last_checkin' ); // v1.8.4.
			delete_option( 'charitable_splash_version' ); // v1.8.6.

			// Delete transients (related to notices).
			$notice_slugs = array( 'campaign-builder', 'dashboard-reporting', 'five-star-review', 'expiringlicense', 'expiredlicense' );
			foreach ( $notice_slugs as $slug ) {
				delete_transient( 'charitable_' . $slug . '_banner' );
			}

			// Delete transients (related to dashboard).
			delete_transient( 'wpch_dashboard_data_args' ); // v1.8.1.

			// Delete transients (related to notices).
			delete_transient( 'charitable_dashboard_notices' ); // v1.8.1.6.

			// Delete transients (related to security checks and logs).
			delete_transient( 'charitable_donation_security_checks' ); // v1.8.1.6.

			// Delete transients (related to notifications).
			delete_transient( 'charitable_autoshow_plugin_notifications' ); // v1.8.3.

			// Delete transients (related to plugin versions).
			delete_transient( '_charitable_plugin_versions' ); // v1.8.6.2.

			// Delete all transients whose names start with "charitable-report".
			charitable_delete_transients_from_keys( charitable_search_database_for_transients_by_prefix( 'charitable-report', '-' ) );

			// Delete settings stored in user meta.
			$user_meta_keys = array( 'charitable-pointer-slug-dismissed', 'charitable_dismissed_addons' ); // v1.8.1.5.
			foreach ( $user_meta_keys as $meta_key ) {
				delete_metadata( 'user', 0, $meta_key, '', true );
			}

			// Licenses info, added in v1.8.1.6.
			delete_site_option( 'wpc_plugin_versions' );
			wp_cache_delete( 'plugin_versions', 'charitable' ); // Depreciated item.
			$empty_transient = new \stdClass();
			set_site_transient( 'update_plugins', $empty_transient ); // Depreciated item.

			// Allow an addon to hook into this.
			do_action( 'charitable_after_clear_expired_options' );

			charitable_get_admin_notices()->add_notice( 'Charitable cache has been cleared.', 'success', false, true );

			// Document when the cache was cleared last.
			update_option( 'charitable_cache_cleared', time() );

			$values['clear_expire_options'] = false;

			return $values;
		}

		/**
		 * Removes activity tables - "clears" them but also allows Charitable to turn around and reinstall them.
		 *
		 * @since   1.8.1
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 * @return  mixed[]
		 */
		public function clear_activity_database( $values, $new_values ) {

			/* If this option isn't in the return values or isn't checked off, leave. */
			if ( ! isset( $new_values['clear_activity_database'] ) || '' === trim( $new_values['clear_activity_database'] ) ) {
				return $values;
			}

			switch ( sanitize_text_field( $new_values['clear_activity_database'] ) ) {
				case 'clear':
					// Check and see if method exists.
					if ( method_exists( charitable_get_table( 'donation_activities' ), 'clear_table' ) ) {
						charitable_get_table( 'donation_activities' )->clear_table();
					}
					if ( method_exists( charitable_get_table( 'campaign_activities' ), 'clear_table' ) ) {
						charitable_get_table( 'campaign_activities' )->clear_table();
					}

					// Allow an addon to hook into this.
					do_action( 'charitable_after_clear_activity_database' );

					charitable_get_admin_notices()->add_notice( 'Charitable activity have been cleared.', 'success', false, true );

					$values['clear_activity_database'] = false;

					break;

				case 'remove':
					// Check and see if method exists.
					if ( method_exists( charitable_get_table( 'donation_activities' ), 'remove_table' ) ) {
						charitable_get_table( 'donation_activities' )->remove_table();
					}
					if ( method_exists( charitable_get_table( 'campaign_activities' ), 'remove_table' ) ) {
						charitable_get_table( 'campaign_activities' )->remove_table();
					}

					// Remove the upgrade option.
					$ugprade_log_key = Charitable_Upgrade::get_instance()->get_upgrade_log_key();
					$log             = get_option( $ugprade_log_key );
					if ( is_array( $log ) && array_key_exists( 'create_activity_tables', $log ) ) {
						unset( $log['create_activity_tables'] );
						update_option( $ugprade_log_key, $log );
					}

					// Allow an addon to hook into this.
					do_action( 'charitable_after_remove_activity_database' );

					charitable_get_admin_notices()->add_notice( 'Charitable activity tables have been removed.', 'success', false, true );

					$values['clear_activity_database'] = false;

					break;

				default:
					// do nothing.
					break;
			}

			return $values;
		}

		/**
		 * Enable or disable minification.
		 *
		 * @since   1.8.1.9
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 * @return  mixed[]
		 */
		public function minification_settings( $values, $new_values ) {

			/* If this option isn't in the return values or isn't checked off, leave. */
			if ( ! isset( $new_values['script_minification'] ) || '' === trim( $new_values['script_minification'] ) ) {
				return $values;
			}

			switch ( sanitize_text_field( $new_values['script_minification'] ) ) {
				case 'enable':
					// Allow an addon to hook into this.
					do_action( 'charitable_after_enable_minification' );

					charitable_get_admin_notices()->add_notice( 'Charitable minification has been enabled.', 'success', false, true );

					$values['script_minification'] = 'scripts-enabled';

					break;

				case 'disable':
					// Allow an addon to hook into this.
					do_action( 'charitable_after_disable_minification' );

					charitable_get_admin_notices()->add_notice( 'Charitable minification has been disabled.', 'success', false, true );

					$values['script_minification'] = 'scripts-disabled';

					break;

				default:
					// do nothing.
					break;
			}

			return $values;
		}

		/**
		 * Checks for updated license and invalidates status field if not set.
		 *
		 * @since   1.0.0
		 * @version 1.8.6.2 - clear transient _charitable_plugin_versions
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 * @return  mixed[]
		 */
		public function save_license( $values, $new_values ) {
			/* If we didn't just submit licenses, stop here. */
			if ( ! isset( $new_values['licenses'] ) ) {
				return $values;
			}

			$re_check = array_key_exists( 'recheck', $_POST ); // phpcs:ignore
			$licenses = $new_values['licenses'];

			// Delete transients (related to plugin versions).
			delete_transient( '_charitable_plugin_versions' );

			foreach ( $licenses as $product_key => $license ) {
				$license = trim( $license );

				if ( empty( $license ) ) {
					$values['licenses'][ $product_key ] = '';
					continue;
				}

				$license_data = charitable_get_helper( 'licenses' )->verify_license( $product_key, $license, $re_check );

				if ( empty( $license_data ) ) {
					continue;
				}

				$values['licenses'][ $product_key ] = $license_data;
			}

			return $values;
		}

		/**
		 * Enables/disables usage tracking, used for testing and debugging.
		 *
		 * @since   1.8.4
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 */
		public function update_user_tracking_option( $values, $new_values ) {

			/* If this option isn't in the return values or isn't checked off then the user has opted out, leave. */
			if ( ! isset( $new_values['charitable_usage_tracking'] ) || 0 === intval( $new_values['charitable_usage_tracking'] ) || '' === trim( $new_values['charitable_usage_tracking'] ) ) {
				// remove the option.
				$values['charitable_usage_tracking'] = false;
				delete_option( 'charitable_usage_tracking' );
			} else {
				// add the option.
				$values['charitable_usage_tracking'] = true;
				update_option( 'charitable_usage_tracking', 1 );
				// Send initial usage information just once (after that scheduled), now that we have permission.
				if ( class_exists( 'Charitable_Tracking' ) ) {
					Charitable_Tracking::get_instance()->send_checkins( false, true );
				}
			}

			return $values;
		}
	}

endif;
