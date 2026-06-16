<?php
/**
 * Defines all plugin specific constants.
 *
 * @package Popups_For_Divi
 */

/**
 * Internal reference to this plugin. This is used in many places, for example
 * to prefix the options-name of plugin settings.
 *
 * @var string
 */
const DIVI_POPUP_INST = 'pfd';

/**
 * Basename of the WordPress plugin. I.e., "plugin-dir/plugin-file.php".
 *
 * @var string
 */
define( 'DIVI_POPUP_PLUGIN', plugin_basename( DIVI_POPUP_PLUGIN_FILE ) );

/**
 * Absolute path to the plugin folder, with trailing slash.
 *
 * @var string
 */
define( 'DIVI_POPUP_PATH', plugin_dir_path( DIVI_POPUP_PLUGIN_FILE ) );

/**
 * Absolute URL to the plugin folder, with trailing slash.
 *
 * @var string
 */
define( 'DIVI_POPUP_URL', plugin_dir_url( DIVI_POPUP_PLUGIN_FILE ) );


	/**
	 * Store-key from where the plugin was downloaded.
	 *
	 * @since 3.0.2
	 * @var string
	 */
	define( 'DIVI_POPUP_STORE', 'dm' );

