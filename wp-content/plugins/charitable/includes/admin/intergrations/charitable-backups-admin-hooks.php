<?php
/**
 * Backups Admin Hooks
 *
 * @package   Charitable/Classes/Charitable_Backups_Admin_Hooks
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 * @version   1.8.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backups Admin Hooks
 *
 * @since 1.8.8
 */
class Charitable_Backups_Admin_Hooks {

	/**
	 * Set up the class.
	 *
	 * @since 1.8.8
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the class.
	 *
	 * @since 1.8.8
	 */
	private function init() {
		/**
		 * Load the Backups integration class.
		 *
		 * @see Charitable_Intergrations_Backups::register_settings()
		 */
		add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_Backups::get_instance(), 'enqueue_scripts' ) );
	}
}

new Charitable_Backups_Admin_Hooks();
