<?php
/**
 * Charitable Install class.
 *
 * The responsibility of this class is to manage the events that need to happen
 * when the plugin is activated.
 *
 * @package   Charitable/Class/Charitable Install
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.1 added activity tables.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Install' ) ) :

	/**
	 * Charitable_Install
	 *
	 * @since  1.0.0
	 */
	class Charitable_Install {

		/**
		 * Includes directory path.
		 *
		 * @since 1.6.42
		 *
		 * @var   string
		 */
		private $includes_path;

		/**
		 * Install the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param string $includes_path Path to the includes directory.
		 */
		public function __construct( $includes_path ) {
			$this->includes_path = $includes_path;

			$this->setup_roles();
			$this->create_tables();
			$this->setup_upgrade_log();
			$this->setup_initial_settings();

			set_transient( 'charitable_install', 1, 0 );
		}

		/**
		 * Finish the plugin installation.
		 *
		 * @since  1.3.4
		 *
		 * @return void
		 */
		public static function finish_installing() {
			Charitable_Cron::schedule_events();

			add_action( 'init', 'flush_rewrite_rules' );
		}

		/**
		 * Create wp roles and assign capabilities
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		protected function setup_roles() {
			require_once( $this->includes_path . '/users/class-charitable-roles.php' ); //phpcs:ignore
			$roles = new Charitable_Roles();
			$roles->add_roles();
			$roles->add_caps();
		}

		/**
		 * Create database tables.
		 *
		 * @since  1.0.0
		 * @version  1.8.1
		 *
		 * @return void
		 */
		protected function create_tables() {
			require_once( $this->includes_path . 'abstracts/abstract-class-charitable-db.php' ); //phpcs:ignore

			$tables = array(
				$this->includes_path . 'data/class-charitable-donors-db.php'              => 'Charitable_Donors_DB',
				$this->includes_path . 'data/class-charitable-donormeta-db.php'           => 'Charitable_Donormeta_DB',
				$this->includes_path . 'data/class-charitable-campaign-donations-db.php'  => 'Charitable_Campaign_Donations_DB',
				$this->includes_path . 'data/class-charitable-donation-activities-db.php' => 'Charitable_Donation_Activities_DB',
				$this->includes_path . 'data/class-charitable-campaign-activities-db.php' => 'Charitable_Campaign_Activities_DB',
			);

			foreach ( $tables as $file => $class ) {
				require_once( $file ); //phpcs:ignore
				$table = new $class();
				$table->create_table();
			}
		}

		/**
		 * Set up the upgrade log.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		protected function setup_upgrade_log() {
			require_once( $this->includes_path . '/admin/upgrades/class-charitable-upgrade.php' ); //phpcs:ignore
			Charitable_Upgrade::get_instance()->populate_upgrade_log_on_install();
		}

		/**
		 * Set up initial settings for install.
		 *
		 * @since   1.8.3.1
		 * @version 1.8.3.7 - Added general default settings.
		 *
		 * @return void
		 */
		protected function setup_initial_settings() {

			$activated = (array) get_option( 'charitable_activated', array() );

			// If no activation date is set, assume it's a new install.
			if ( false === $activated || empty( $activated ) ) {

				// Default general settings.
				$this->set_initial_charitable_option( 'donation_form_display', 'modal' );
				$this->set_initial_charitable_option( 'country', 'US' );
				$this->set_initial_charitable_option( 'currency', 'USD' );
				$this->set_initial_charitable_option( 'currency_format', 'left' );
				$this->set_initial_charitable_option( 'decimal_separator', '.' );
				$this->set_initial_charitable_option( 'thousands_separator', ',' );
				$this->set_initial_charitable_option( 'decimal_count', '2' );

				// Default gateway settings.
				$this->set_initial_charitable_option( 'test_mode', 1 );
				$this->set_initial_charitable_option(
					'active_gateways',
					array(
						0        => 'stripe',
						'stripe' => 'Charitable_Gateway_Stripe_AM',
					)
				);
				$this->set_initial_charitable_option( 'default_gateway', 'stripe' );

				// Default advanced settings.
				$this->set_initial_charitable_option( 'disable_campaign_legacy_mode', 1 );
			}

			// Clean up Square gateway settings if charitable-square plugin is not active.
			$this->cleanup_square_gateway_settings();
		}

		/**
		 * Clean up Square gateway settings if charitable-square plugin is not active.
		 *
		 * @since 1.8.7
		 *
		 * @return void
		 */
		private function cleanup_square_gateway_settings() {
			// Check if charitable-square plugin is active.
			if ( ! $this->is_square_addon_active() ) {
				$settings = get_option( 'charitable_settings', array() );

				// If 'square' gateway exists in active_gateways, remove it.
				if ( isset( $settings['active_gateways']['square'] ) ) {
					unset( $settings['active_gateways']['square'] );

					// If 'square' was the default gateway, set a new default.
					if ( isset( $settings['default_gateway'] ) && 'square' === $settings['default_gateway'] ) {
						$settings['default_gateway'] = count( $settings['active_gateways'] ) ? key( $settings['active_gateways'] ) : '';
					}

					update_option( 'charitable_settings', $settings );
				}
			}
		}

		/**
		 * Check if charitable-square plugin is active.
		 *
		 * @since 1.8.7
		 *
		 * @return boolean
		 */
		private function is_square_addon_active() {
			return is_plugin_active( 'charitable-square/charitable-square.php' );
		}

		/**
		 * Set an initial Charitable option.
		 *
		 * @since 1.8.3.1
		 *
		 * @param string $setting The setting key.
		 * @param mixed  $value   The setting value.
		 * @return void
		 */
		private function set_initial_charitable_option( $setting = '', $value = false ) {
			$settings = get_option( 'charitable_settings', [] );

			// if the setting somehow already exists, don't overwrite it.
			if ( '' === $setting || isset( $settings[ $setting ] ) ) {
				return;
			}

			$settings[ $setting ] = $value;

			update_option( 'charitable_settings', $settings );
		}
	}

endif;
