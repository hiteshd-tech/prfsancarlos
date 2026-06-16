<?php
/**
 * Library functions aid in code reusability and contain the actual business
 * logic of our plugin. They break down the plugin functionality into logical
 * units.
 *
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Loads the shared.php library when no other divimode plugin provides the
 * required functions.
 *
 * @since 3.0.0
 */
function pfd_load_library() {
	if ( ! defined( 'DM_DASH_PATH' ) ) {
		require_once __DIR__ . '/shared.php';
	}

	dm_admin_notice_schedule_cron( DIVI_POPUP_INST, DIVI_POPUP_STORE );
}

/**
 * Loads the plugins textdomain files.
 *
 * @since 3.0.0
 */
function pfd_translate_plugin() {
	// Translate the plugin.
	load_plugin_textdomain(
		'popups-for-divi',
		false,
		dirname( DIVI_POPUP_PLUGIN ) . '/languages/'
	);
}
