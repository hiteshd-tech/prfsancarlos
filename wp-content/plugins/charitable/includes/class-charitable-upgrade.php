<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This provides backwards compatibility for any extensions that
 * attempt to load the Charitable_Upgrade class from here.
 *
 * @package   Charitable/Functions/Core
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.0
 *
 * @deprecated
 */

if ( class_exists( 'Charitable_Upgrade' ) ) {
	return;
}

require_once charitable()->get_path( 'includes' ) . 'upgrades/class-charitable-upgrade.php';
