<?php
/**
 * Hooks up filters and actions of this module.
 *
 * Builder module: Integration into Divis Visual Builder. Because the VB is
 * available in the front-end (FB) and in the wp-admin area (BFB), this module
 * is neither admin nor front-end specific.
 *
 * @free    include file
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set up Divi-specific hooks to inject our "Popup" tab in sections.
add_action( 'et_builder_framework_loaded', 'pfd_builder_add_hooks' );

// Pre-processes the Divi section settings before they are actually saved.
add_action( 'wp_ajax_et_fb_ajax_save', 'pfd_builder_et_fb_ajax_save', 0 );

