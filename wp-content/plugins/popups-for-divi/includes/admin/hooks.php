<?php
/**
 * Hooks up filters and actions of this module.
 *
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Add an "How To" link to the plugin actions.
add_filter( 'plugin_action_links', 'pfd_admin_plugin_add_settings_link', 10, 2 );

// Replace "View details" link and add a "Get Pro" link below the plugin description.
add_filter( 'plugin_row_meta', 'pfd_admin_plugin_row_meta', 10, 4 );

// Only on the wp-admin Dashboard: Display the Onboarding notice
add_action( 'load-index.php', 'pfd_admin_init_onboarding' );

// Ajax handler: Permanently close the onboarding notice.
add_action( 'wp_ajax_pfd_hide_onboarding', 'pfd_admin_ajax_hide_onboarding' );

// Ajax handler: Sign up to the onboarding email course.
add_action( 'wp_ajax_pfd_start_course', 'pfd_admin_ajax_start_course' );
