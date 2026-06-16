<?php
/**
 * Charitable Elementor Hooks.
 *
 * Action/filter hooks used for Charitable Elementor.
 *
 * @package   Charitable/Functions/Elementor
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the Elementor integration.
 *
 * @see Charitable_Elementor::init()
 */
add_action( 'init', array( 'Charitable_Elementor', 'init' ) );
