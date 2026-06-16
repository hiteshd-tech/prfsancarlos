<?php
/**
 * SEO Admin Hooks
 *
 * @package   Charitable/Classes/Charitable_SEO_Admin_Hooks
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
 * SEO Admin Hooks
 *
 * @since 1.8.8
 */
class Charitable_SEO_Admin_Hooks {

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
		 * Load the SEO integration class.
		 *
		 * @see Charitable_Intergrations_SEO::register_settings()
		 */
		add_action( 'admin_enqueue_scripts', array( Charitable_Intergrations_SEO::get_instance(), 'enqueue_scripts' ) );
	}
}

new Charitable_SEO_Admin_Hooks();
