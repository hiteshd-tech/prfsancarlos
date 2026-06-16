<?php
/**
 * Charitable Email Fields interface.
 *
 * This defines a strict interface that email fields classes must implement.
 *
 * @version   1.5.0
 * @package   Charitable/Interfaces/Charitable_Email_Fields_Interface
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Charitable_Email_Fields_Interface' ) ) :

	/**
	 * Charitable_Email_Fields_Interface interface.
	 *
	 * @since 1.5.0
	 */
	interface Charitable_Email_Fields_Interface {

		/**
		 * Return email fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_fields();
	}

endif;
