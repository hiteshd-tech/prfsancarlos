<?php
/**
 * Clean up the DB when plugin is uninstalled.
 * This is a magic file that is only loaded when the plugin is uninstalled.
 *
 * @since   3.0.0
 * @package PopupsForDivi
 */

// Stop if uninstall.php is directly accessed or loaded incorrectly.
defined( 'ABSPATH' ) || die();
defined( 'WP_UNINSTALL_PLUGIN' ) || die();

// Load the full plugin as it contains some helper functions that we need here.
require_once( __DIR__ . '/plugin.php' );
pfd_init_plugin();
pfd_load_library();

// == Clean up user meta ==

delete_metadata(
	'user',            // Clean up the user-meta table.
	false,             // Ignored.
	'_pfd_onboarding', // Meta-key to delete.
	'',                // Ignored.
	true               // Delete all values.
);

delete_metadata(
	'user',            // Clean up the user-meta table.
	false,             // Ignored.
	'_dm_dismissed',   // Meta-key to delete.
	'',                // Ignored.
	true               // Delete all values.
);

// == Clean up post meta ==

// == Clean up options ==

$options = [
	'dm_' . DIVI_POPUP_INST . '_data',
	'dm_core_notices',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// == Clean up cron events ==

dm_admin_notice_clear_cron( DIVI_POPUP_INST, DIVI_POPUP_STORE );
