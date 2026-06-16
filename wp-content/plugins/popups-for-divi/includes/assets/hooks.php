<?php
/**
 * Hooks up filters and actions of this module.
 *
 * Assets module: Handles all assets (.js and .css files, inline scripts/styles)
 *
 * @free include file
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Register nonces.
add_action( 'divimode_load_admin_page_settings', 'pfd_asset_enqueue_settings' );

// Enqueue the front-end JS library.
add_action( 'wp_enqueue_scripts', 'pfd_assets_enqueue_js_library' );

