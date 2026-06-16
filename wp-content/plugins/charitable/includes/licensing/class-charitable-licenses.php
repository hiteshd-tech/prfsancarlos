<?php
/**
 * Class to assist with the setup of extension licenses.
 *
 * @package     Charitable/Classes/Charitable_Licenses
 * @version     1.8.2
 * @author      David Bisset
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use CharitableLicenses\CharitableLicenses;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Licenses' ) ) :

	/**
	 * Charitable_Licenses
	 *
	 * @since   1.0.0
	 */
	class Charitable_Licenses {

		/* @var string */
		const UPDATE_URL = 'https://wpcharitable.com';

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Licenses|null
		 */
		private static $instance = null;

		/**
		 * All the registered products requiring licensing.
		 *
		 * @var array
		 */
		private $products;

		/**
		 * All the stored licenses.
		 *
		 * @var array
		 */
		private $licenses;

		/**
		 * The key for the failed request cache.
		 *
		 * @var string
		 */
		private $failed_request_cache_key = 'wpc_edd_sl_failed_plugin_versions';

		/**
		 * Cached update data.
		 *
		 * @var array
		 */
		private $update_data;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Licenses
		 */
		public static function get_instance() {
			return self::$instance ??= new self();
		}

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {

			$registry = charitable()->registry();

			if ( ! $registry->has( 'charitable_licenses' ) ) {
				$registry->register_object( CharitableLicenses::get_instance() );
			}
		}

		/**
		 * Checks licenses (new and legacy) to see if any are valid.
		 *
		 * @since  1.7.0
		 *
		 * @return boolean
		 */
		public function is_pro() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: is_pro()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			// On multisite subsites, the vendor's is_pro() only checks the network main site's license.
			// Also check the current subsite's license so subsite admins with valid licenses are recognized.
			if ( is_multisite() && ! is_main_site() ) {
				$settings = get_option( 'charitable_settings' );
				if ( $settings && isset( $settings['licenses']['charitable-v2']['valid'] ) && 1 === intval( $settings['licenses']['charitable-v2']['valid'] ) ) {
					return true;
				}
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->is_pro();
		}

		/**
		 * Checks the "v3" license (starting Nov 2022) to see if it's valid.
		 * If valid, normally doesn't require other license/pro checks.
		 *
		 * @since  1.7.0.4
		 *
		 * @return boolean
		 */
		public function is_v3_license_valid() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: is_v3_license_valid()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->is_v3_license_valid();
		}

		/**
		 * Checks for any Charitable extensions with updates.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $_transient_data The plugin updates data.
		 * @return array
		 */
		public function check_for_updates( $_transient_data ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: check_for_updates()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->check_for_updates( $_transient_data );
		}


		/**
		 * Updates information on the "View version x.x details" page with custom data.
		 *
		 * @uses   api_request()
		 *
		 * @since  1.4.20
		 *
		 * @param  mixed  $_data   Default set of data.
		 * @param  string $_action The current action.
		 * @param  object $_args   Request args.
		 * @return object $_data
		 */
		public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: plugins_api_filter()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->plugins_api_filter( $_data, $_action, $_args );
		}

		/**
		 * Return whether a particular plugin is missing version info.
		 *
		 * @since  1.4.20
		 *
		 * @param  array        $product      Product details array.
		 * @param  false|object $update_cache Optional argument to pass update cache.
		 * @return boolean
		 */
		public function is_missing_version_info( $product, $update_cache = false ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: is_missing_version_info()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->is_missing_version_info( $product, $update_cache );
		}

		/**
		 * Return the version update info for a particular plugin.
		 *
		 * @since  1.4.20
		 *
		 * @param  string       $slug         The plugin slug.
		 * @param  false|object $update_cache Optional argument to pass update cache.
		 * @return array|false Array if an update is available. False otherwise.
		 */
		public function get_version_info( $slug, $update_cache = false ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_version_info()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_version_info( $slug, $update_cache );
		}

		/**
		 * Checks whether the given plugin can be updated.
		 *
		 * @since  1.6.14
		 *
		 * @param  array $version_info Version information.
		 * @return true|array If it can be updated, returns true. Otherwise
		 *                    returns an array with a reason_code and description.
		 */
		protected function able_to_update( $version_info ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: able_to_update()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->able_to_update( $version_info );
		}

		/**
		 * Return the package string for an expired license.
		 *
		 * @since  1.4.20
		 *
		 * @param  string $plugin Plugin basename.
		 * @return string
		 */
		public function get_expired_license_package( $plugin ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_expired_license_package()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_expired_license_package( $plugin );
		}


		/**
		 * Return the package string for a plugin missing requirements.
		 *
		 * @since  1.6.14
		 *
		 * @param  string $plugin Plugin basename.
		 * @return string
		 */
		public function get_missing_requirements_package( $plugin ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_missing_requirements_package()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_missing_requirements_package( $plugin );
		}

		/**
		 * Register a product that requires licensing.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $item_name The title of the product.
		 * @param  string $author    The author of the product.
		 * @param  string $version   The current product version we have installed.
		 * @param  string $file      The path to the plugin file.
		 * @param  string $url       The base URL where the plugin is licensed. Defaults to Charitable_Licenses::UPDATE_URL.
		 * @return void
		 */
		public function register_licensed_product( $item_name, $author, $version, $file, $url = false ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: register_licensed_product()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			$registry->get( 'CharitableLicenses\CharitableLicenses' )->register_licensed_product( $item_name, $author, $version, $file, $url );
		}

		/**
		 * Return the list of products requiring licensing.
		 *
		 * @since  1.0.0
		 *
		 * @return array[]
		 */
		public function get_products() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_products()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_products();
		}

		/**
		 * Return a specific product's details.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $item The item for which we are getting product details.
		 * @return string[]
		 */
		public function get_product_license_details( $item ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_product_license_details()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_product_license_details( $item );
		}

		/**
		 * Returns whether the given product has a valid license.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $item The item to check.
		 * @return boolean
		 */
		public function has_valid_license( $item ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: has_valid_license()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->has_valid_license( $item );
		}

		/**
		 * Returns the license details for the given product.
		 *
		 * @since   1.0.0
		 *
		 * @param  string $item The item to get the license for.
		 * @return mixed[]
		 */
		public function get_license( $item ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_license()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_license( $item );
		}

		/**
		 * Returns the active license details for the given product.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $item The item to get active licensing details for.
		 * @return mixed[]
		 */
		public function get_license_details( $item ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_license_details()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_license_details( $item );
		}

		/**
		 * Return the list of licenses.
		 *
		 * Note: The licenses are not necessarily valid. If a user enters an invalid
		 * license, the license will be stored but it will be flagged as invalid.
		 *
		 * @since  1.0.0
		 *
		 * @return array[]
		 */
		public function get_licenses() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_licenses()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_licenses();
		}

		/**
		 * Verify a license.
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $item    The item to verify.
		 * @param  string  $license The license key for the item.
		 * @param  boolean $force   Whether to force the verification check.
		 * @param  boolean $legacy  Whether to use the legacy API (?).
		 * @return mixed[]
		 */
		public function verify_license( $item, $license, $force = false, $legacy = false ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: verify_license()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->verify_license( $item, $license, $force, $legacy );
		}

		/**
		 * Return the URL to deactivate a specific license.
		 *
		 * @since   1.0.0
		 *
		 * @param  string $item The item to deactivate.
		 * @return string
		 */
		public function get_license_deactivation_url( $item ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_license_deactivation_url()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_license_deactivation_url( $item );
		}

		/**
		 * Deactivate a license.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function deactivate_license() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: deactivate_license()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			$registry->get( 'CharitableLicenses\CharitableLicenses' )->deactivate_license();
		}

		/**
		 * Flush the version update cache.
		 *
		 * @since 1.4.20
		 * @since 1.8.1.1 - Remove site option. Other items in this function are depreciated.
		 *
		 * @return void
		 */
		protected function flush_update_cache() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: flush_update_cache()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			$registry->get( 'CharitableLicenses\CharitableLicenses' )->flush_update_cache();
		}

		/**
		 * Return a key for the item, based on the item name.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $item_name Name of the item.
		 * @return string
		 */
		protected function get_item_key( $item_name ) {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_item_key()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_item_key( $item_name );
		}

		/**
		 * Return the latest versions of Charitable plugins.
		 *
		 * @since 1.4.0
		 * @since 1.8.1.1 - Removed cache_get method and site a site option instead.
		 * @since 1.8.1.3 - Added request_recently_failed check and log_failed_request call.
		 *
		 * @return array
		 */
		protected function get_versions() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: get_versions()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->get_versions();
		}

		/**
		 * Logs a failed HTTP request for this API URL.
		 *
		 * We set a timestamp for 1 hour from now. This prevents future API requests from being
		 * made to this domain for 1 hour. Once the timestamp is in the past, API requests
		 * will be allowed again. This way if the site is down for some reason we don't bombard
		 * it with failed API requests.
		 *
		 * @see EDD_SL_Plugin_Updater::request_recently_failed
		 *
		 * @since 1.8.1.3
		 */
		private function log_failed_request() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: log_failed_request()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			$registry->get( 'CharitableLicenses\CharitableLicenses' )->log_failed_request();
		}

		/**
		 * Determines if a request has recently failed.
		 *
		 * @since 1.8.1.3
		 *
		 * @return bool
		 */
		private function request_recently_failed() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: request_recently_failed()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			return $registry->get( 'CharitableLicenses\CharitableLicenses' )->request_recently_failed();
		}

		/**
		 * AJAX handler for license checks.
		 *
		 * @since  1.0.0
		 * @since  1.8.1.8 - Added nonce and permissions check.
		 *
		 * @return void
		 */
		public function ajax_license_check() {

			if ( charitable_is_debug( 'vendor' ) ) {
				error_log( 'CHARITABLE: NEW VENDOR CALL THROWN: ajax_license_check()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$registry = charitable()->registry();
			$registry->get( 'CharitableLicenses\CharitableLicenses' )->ajax_license_check();
		}
	}

endif;
