<?php
/**
 * Class to assist with checking charitable extension versions.
 *
 * @package     Charitable/Classes/Charitable_Licenses
 * @version     1.8.2
 * @author      David Bisset
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use CharitablePluginUpdater\CharitablePluginUpdater;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Checker' ) ) :

	/**
	 * Charitable_Updater
	 */
	class Charitable_Checker {
		private $api_url  = ''; // phpcs:ignore
		private $api_data = array(); // phpcs:ignore
		private $name     = ''; // phpcs:ignore
		private $slug     = ''; // phpcs:ignore
		private $version  = ''; // phpcs:ignore

		/**
		 * Class constructor.
		 *
		 * @uses  plugin_basename()
		 * @uses  hook()
		 *
		 * @param string $_api_url     The URL pointing to the custom API endpoint.
		 * @param string $_plugin_file Path to the plugin file.
		 * @param array  $_api_data    Optional data to send with API calls.
		 */
		public function __construct( $_api_url, $_plugin_file, $_api_data = array() ) {

			// check if CharitablePluginUpdater class exists.
			if ( ! class_exists( 'CharitablePluginUpdater' ) ) {
				return;
			}

			$updater = new CharitablePluginUpdater( $_api_url, $_plugin_file, $_api_data );
		}
	}

endif;
