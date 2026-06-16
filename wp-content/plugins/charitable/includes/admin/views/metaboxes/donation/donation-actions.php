<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @deprecated 1.8.0
 *
 * @author WP Charitable LLC
 * @since  1.5.0
 * @since  1.5.9 Deprecated. views/metaboxes/actions.php is used instead.
 * @version 1.8.8.6
 */

if ( ! array_key_exists( 'actions', $view_args ) ) {
	$view_args['actions'] = charitable_get_donation_actions(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $view_args is a template variable passed from the calling function.
}

charitable_admin_view( 'metaboxes/actions', $view_args );
