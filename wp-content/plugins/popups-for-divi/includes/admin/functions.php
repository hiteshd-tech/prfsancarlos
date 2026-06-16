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
 * Display a custom link in the plugins list
 *
 * @since  1.0.2
 *
 * @param array  $links       List of plugin links.
 * @param string $plugin_file Path to the plugin file relative to the plugins
 *                            directory.
 *
 * @return array New list of plugin links.
 */
function pfd_admin_plugin_add_settings_link( $links, $plugin_file ) {
	if ( DIVI_POPUP_PLUGIN !== $plugin_file ) {
		return $links;
	}

	$links[] = sprintf(
		'<a href="%s" target="_blank">%s</a>',
		'https://divimode.com/divi-popup/?utm_source=wpadmin&utm_medium=link&utm_campaign=popups-for-divi',
		__( 'How it works', 'divi-popup' )
	);

	return $links;
}

/**
 * Display additional details in the right column of the "Plugins" page.
 *
 * @param string[] $plugin_meta An array of the plugin's metadata,
 *                              including the version, author,
 *                              author URI, and plugin URI.
 * @param string   $plugin_file Path to the plugin file relative to the plugins
 *                              directory.
 *
 * @return string[]
 * @since 1.6.0
 *
 */
function pfd_admin_plugin_row_meta( $plugin_meta, $plugin_file ) {
	if ( DIVI_POPUP_PLUGIN !== $plugin_file ) {
		return $plugin_meta;
	}

	$plugin_name = 'Popups for Divi';
	$slug = 'popups-for-divi';

	$plugin_meta[] = sprintf(
		'<a href="%s" target="_blank">%s</a>',
		'https://divimode.com/divi-areas-pro/?utm_source=wpadmin&utm_medium=link&utm_campaign=popups-for-divi',
		__( 'Divi Areas <strong>Pro</strong>', 'divi-popup' )
	);
	
	$plugin_meta[2] = sprintf(
		'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
		esc_url(
			str_replace( '&plugin=&', '&plugin=popups-for-divi&', 
			network_admin_url(
				'plugin-install.php?tab=plugin-information&plugin=&section=changelog&TB_iframe=true&width=600&height=550'
			)
			)
		),
		/* translators: %s: Plugin name. */
		esc_attr( sprintf( __( 'More information about %s' ), $plugin_name ) ),
		esc_attr( $plugin_name ),
		__( 'View details' )
	);

	return $plugin_meta;
}

/**
 * Determine, whether to display the onboarding notice.
 *
 * @since 2.0.2
 * @return bool
 */
function pfd_admin_show_onboarding_form() {
	$show_notice = true;
	$discarded   = false;

	if ( defined( 'DISABLE_NAG_NOTICES' ) && DISABLE_NAG_NOTICES ) {
		$show_notice = false;
	}

	if ( ! defined( 'DIVI_POPUP_ONBOARDING_CAP' ) ) {
		// By default, display the onboarding notice to all users who can
		// activate plugins (i.e. administrators).
		define( 'DIVI_POPUP_ONBOARDING_CAP', 'activate_plugins' );
	}

	$user = wp_get_current_user();

	if ( ! $user ) {
		$show_notice = false;
		$discarded   = true;
	} else {
		if ( $user->has_cap( DIVI_POPUP_ONBOARDING_CAP ) ) {
			// Check, if the user discarded the message already.
			$discarded = 'done' === $user->get( '_pfd_onboarding' );
		} else {
			// Never show the notice to users without sufficient permissions.
			$show_notice = false;
		}
	}

	/**
	 * Filter the determined result value to determine if the onboarding notice
	 * should be displayed to the current user.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $show_notice Whether to display the onboarding notice.
	 * @param bool $discarded   True, if the user already discarded the notice.
	 */
	return apply_filters(
		'divi_popups_show_onboarding_form',
		$show_notice && ! $discarded,
		$discarded
	);
}

/**
 * Initialize the onboarding process.
 *
 * @since 1.6.0
 * @return void
 */
function pfd_admin_init_onboarding() {
	if ( pfd_admin_show_onboarding_form() ) {
		add_action( 'admin_notices', 'pfd_admin_onboarding_notice', 1 );
		remove_action( 'admin_notices', 'dm_notice_show' );
	}
}

/**
 * Output the onboarding notice on th wp-admin Dashboard.
 *
 * @since 1.6.0
 * @return void
 */
function pfd_admin_onboarding_notice() {
	$user = wp_get_current_user();

	include __DIR__ . '/templates/onboarding.php';
}

/**
 * Ajax handler: Permanently close the onboarding notice.
 *
 * @since 1.6.0
 * @return void
 */
function pfd_admin_ajax_hide_onboarding() {
	// Make sure that the ajax request comes from the current WP admin site!
	if (
		! is_user_logged_in() // better safe than sorry.
		|| empty( $_POST['_wpnonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'no-onboarding' )
	) {
		wp_send_json_success( 'ERROR' );
	}

	// phpcs:ignore WordPress.VIP.RestrictedFunctions.user_meta_update_user_meta
	update_user_meta(
		get_current_user_id(),
		'_pfd_onboarding',
		'done'
	);

	wp_send_json_success();
}

/**
 * Ajax handler: Subscribe the email address to our onboarding course.
 *
 * Note that this ajax handler only fires for authenticated requests:
 * We handle action `wp_ajax_pfd_start_course`.
 * There is NO handler for `wp_ajax_nopriv_pfd_start_course`!
 *
 * @since 1.6.0
 * @return void
 */
function pfd_admin_ajax_start_course() {
	// Make sure that the ajax request comes from the current WP admin site!
	if (
		! is_user_logged_in() // better safe than sorry.
		|| empty( $_POST['_wpnonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'onboarding' )
	) {
		wp_send_json_success( 'ERROR' );
	}

	$form = wp_unslash( $_POST ); // input var okay.

	$email = sanitize_email( trim( $form['email'] ) );
	$name  = sanitize_text_field( trim( $form['name'] ) );

	// Send the subscription details to our website.
	$resp = wp_remote_post(
		'https://divimode.com/wp-admin/admin-post.php',
		[
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
			],
			'body'    => [
				'action' => 'pfd_start_onboarding',
				'fname'  => $name,
				'email'  => $email,
			],
		]
	);

	if ( is_wp_error( $resp ) ) {
		wp_send_json_success( 'ERROR' );
	}

	$result = wp_remote_retrieve_body( $resp );
	wp_send_json_success( $result );
}
