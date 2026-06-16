<?php
/**
 * Charitable Stripe Gateway Processor interface.
 *
 * @package   Charitable Stripe/Interfaces/Charitable_Stripe_Gateway_Processor
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.3.0
 * @version   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Charitable_Stripe_Gateway_Processor_Interface' ) ) :

	/**
	 * Charitable_Stripe_Gateway_Processor interface.
	 *
	 * @since 1.3.0
	 */
	interface Charitable_Stripe_Gateway_Processor_Interface {

		/**
		 * Run the processor.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function run();
	}

endif;
