<?php
/**
 * Library functions aid in code reusability and contain the actual business
 * logic of our plugin. They break down the plugin functionality into logical
 * units.
 *
 * This module integrates functions from the shared divimode library. That
 * library is part of all premium plugins but the free plugin only requires
 * a small subset of those features.
 *
 * @package PopupsForDivi
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// This library is only needed, if no premium plugin is active.
if ( defined( 'DM_DASH_PATH' ) ) {
	return;
}

/**
 * Returns the constant value, if it's defined. Otherwise returns null.
 *
 * @see   function in shared/includes/helpers.php
 *
 * @since 0.4.0
 *
 * @param string $name Name of a maybe defined constant.
 *
 * @return mixed Either the constant value, or null.
 */
function dm_get_const( $name ) {
	if ( defined( $name ) ) {
		return constant( $name );
	}

	return null;
}

/**
 * Collect anonymous details about the current system for output in error logs.
 *
 * @see    function in shared/includes/metabox/functions.php
 *
 * @filter divimode_debug_infos
 * @since  0.4.0
 *
 * @param array $infos Debug details.
 *
 * @return array Array containing debug details.
 */
function dm_metabox_generate_debug_infos( array $infos ) {
	global $wp_version;

	/*
	For security reasons, we only generate debug information for
	logged-in users.
	*/

	if ( is_user_logged_in() ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$curr_theme     = wp_get_theme();
		$builder_plugin = 'divi-builder/divi-builder.php';

		if ( $curr_theme->stylesheet !== $curr_theme->template ) {
			$curr_theme           = wp_get_theme( $curr_theme->template );
			$infos['child_theme'] = true;
		} else {
			$infos['child_theme'] = false;
		}
		$infos['theme']     = $curr_theme->stylesheet;
		$infos['theme_ver'] = $curr_theme->version;

		if (
			file_exists( WP_PLUGIN_DIR . '/' . $builder_plugin )
			&& (
				is_plugin_active( $builder_plugin )
				|| is_plugin_active_for_network( $builder_plugin )
			)
		) {
			$builder_plugin_path  = wp_normalize_path( WP_PLUGIN_DIR . '/' . $builder_plugin );
			$divi_plugin          = get_plugin_data( $builder_plugin_path );
			$infos['use_builder'] = true;
			$infos['builder_ver'] = $divi_plugin['Version'];
		} else {
			$infos['use_builder'] = false;
			$infos['builder_ver'] = '-';
		}

		$infos['plugin_pfd'] = sprintf(
			'Popups for Divi, v%s',
			DIVI_POPUP_VERSION
		);

		$infos['wp_ver']  = $wp_version;
		$infos['php_ver'] = PHP_VERSION;
	}

	return $infos;
}

/**
 * Returns the specific plugin configuration.
 *
 * @see   function in shared/includes/options/functions.php
 *
 * @since 0.4.0
 * @since 3.0.2 -- Simplified function of shared library.
 *
 * @param string $inst          The plugin ID/key.
 * @param string $key           The configuration key.
 * @param string $default_value Optional. Return value, when $key is not set.
 * @param string $container     Optional. Name of the container to load. Default
 *                              is "data".
 *
 * @return mixed The plugin configuration value.
 */
function dm_option_get( $inst, $key, $default_value = false, $container = 'data' ) {
	$value = $default_value;

	$option_name = "dm_{$inst}_$container";
	$options     = get_option( $option_name, [] );

	if ( is_array( $options ) && isset( $options[ $key ] ) ) {
		$value = $options[ $key ];
	}

	return $value;
}

/**
 * Saves the specified plugin configuration.
 *
 * @see   function in shared/includes/options/functions.php
 *
 * @since 0.4.0
 * @since 3.0.2 -- Simplified function of shared library.
 *
 * @param string $inst      The plugin ID/key.
 * @param string $key       The configuration key.
 * @param mixed  $value     Optional. The plugin configuration value.
 * @param string $container Optional. Name of the container to update.
 *                          Only used, when $key is a string value.
 *                          Default is "data".
 *
 * @return void
 */
function dm_option_set( $inst, $key, $value, $container = 'data' ) {
	$option_name = "dm_{$inst}_$container";
	$options     = get_option( $option_name, [] );

	if ( ! is_array( $options ) ) {
		$options = [];
	}

	$options[ $key ] = $value;
	update_option( $option_name, $options, true );
}

/**
 * Returns allowed HTML tags and attributes to escape admin notification strings
 * via `wp_kses()`.
 *
 * @see   function in shared/includes/utils/functions.php
 *
 * @since 3.0.2
 * @return array Allowed HTML definition for wp_kses().
 */
function dm_kses_notice_html() {
	return [
		'div'    => [
			'class' => [],
		],
		'p'      => [],
		'strong' => [],
		'em'     => [],
		'i'      => [
			'class' => [],
		],
		'a'      => [
			'href'                => [],
			'title'               => [],
			'class'               => [],
			'target'              => [],
			'aria-label'          => [],
			'data-beacon-article' => [], // Allow Helpscout integration.
		],
		'img'    => [
			'alt'   => [],
			'title' => [],
		],
		'br'     => [],
	];
}

/**
 * Creates a daily cron schedule to check for new plugin notifications.
 *
 * @see   function in shared/includes/admin/functions.php
 *
 * @since 3.0.2
 *
 * @param string $inst  The plugin instance key.
 * @param string $store ID of the sellers store.
 */
function dm_admin_notice_schedule_cron( $inst, $store ) {
	$args = [ $inst, $store ];

	if ( ! wp_next_scheduled( 'dm_admin_cron_notice_check', $args ) ) {
		wp_schedule_event( time(), 'daily', 'dm_admin_cron_notice_check', $args );
	}
}

/**
 * Creates a daily cron schedule to check for new plugin notifications.
 *
 * @see   function in shared/includes/admin/functions.php
 *
 * @since 3.0.2
 *
 *
 * @param string $inst  The plugin instance key.
 * @param string $store ID of the sellers store.
 */
function dm_admin_notice_clear_cron( $inst, $store ) {
	$args = [ $inst, $store ];
	wp_clear_scheduled_hook( 'dm_admin_cron_notice_check', $args );
}

/**
 * Fetches available messages from the divimode REST API.
 *
 * This function is called via a cron tab once per day.
 *
 * @see    function in shared/includes/admin/functions.php
 *
 * @action dm_admin_cron_notice_check -- cron event.
 * @since  3.0.2
 *
 * @param string $inst  The plugin instance key.
 * @param string $store The store-key from where the plugin was downloaded.
 */
function dm_admin_notice_fetch( $inst = '', $store = '' ) {
	if ( ! $inst || ! $store ) {
		return;
	}

	// Determine, how long this plugin is installed.
	$active_since = dm_option_get( $inst, 'active_since' );
	if ( ! is_numeric( $active_since ) || $active_since < 100 ) {
		$active_since = time();
		dm_option_set( $inst, 'active_since', $active_since );
	}
	$days_active = floor( ( time() - $active_since ) / DAY_IN_SECONDS );

	// Fetch relevant notifications for the current plugin.
	$response = wp_remote_get(
		"https://divimode.com/wp-json/divimode/v1/notifications/$inst-$store"
	);

	if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		$queue = (array) dm_option_get( 'core', 'queue', [], 'notices' );
		$items = (array) json_decode( $response['body'], true );

		foreach ( $items as $notice ) {
			// Skip items without an ID or without content.
			if ( ! is_array( $notice ) || empty( $notice['ID'] ) || empty( $notice['html'] ) ) {
				continue;
			}

			$notice_id = (int) $notice['ID'];
			unset( $queue[ $notice_id ] );

			// Populate missing fields.
			$notice = array_merge(
				[
					'delay'     => 0,
					'type'      => 'info',
					'condition' => 'always',
				],
				$notice
			);

			// Check the active-days condition.
			if ( ! empty( $notice['delay'] ) && (int) $notice['delay'] > $days_active ) {
				continue;
			}

			/**
			 * Filter custom notification conditions.
			 *
			 * By default, every notification with "condition === always" is
			 * enqueued, and all other notifications are skipped.
			 *
			 * @since 3.0.2
			 *
			 * @param bool  $skip   Whether to skip the current notification.
			 * @param array $notice Notification details.
			 */
			$skip = apply_filters(
				'divimode_skip_notice',
				'always' !== $notice['condition'],
				$notice
			);

			if ( true === $skip ) {
				continue;
			}

			// Enqueue the notice for display in wp-admin.
			$queue[ $notice_id ] = [
				'inst' => $inst,
				'type' => $notice['type'],
				'html' => $notice['html'],
			];
		}

		dm_option_set( 'core', 'queue', array_filter( $queue ), 'notices' );
	}
}

/**
 * Ajax handler that marks a given notification as "dismissed".
 *
 * Every divimode notification has a user-scope. That means, every admin user
 * sees the notification until dismissing it - in that case, a flag is added
 * to the user-meta table to dismiss the notification for the current user.
 *
 * @see   function in shared/includes/admin/functions.php
 *
 * @since 3.0.2
 */
function dm_admin_notice_ajax_dismiss() {
	if ( empty( $_POST['id'] ) ) {
		return;
	}
	$notice_id = (int) $_POST['id'];
	check_ajax_referer( 'dismiss-notice-' . $notice_id );

	$user = wp_get_current_user();

	if ( ! $notice_id || ! $user || ! $user->ID ) {
		return;
	}

	// Permanently dismiss the notification for the current user.
	$dismissed = (array) $user->get( '_dm_dismissed' );

	$dismissed[ $notice_id ] = time();
	update_user_meta( $user->ID, '_dm_dismissed', $dismissed );

	wp_send_json_success();
}

/**
 * Outputs the first enqueued divimode notifications.
 *
 * Divimode notifications are displayed on every admin page for all admin-users.
 *
 * We respect the "DISABLE_NAG_NOTICES" flag to globally remove admin notices
 * for all users.
 *
 * @see   function in shared/includes/admin/functions.php
 *
 * @since 3.0.2
 */
function dm_admin_notice_show() {
	if ( dm_get_const( 'DISABLE_NAG_NOTICES' ) ) {
		return;
	}

	$queue   = (array) dm_option_get( 'core', 'queue', [], 'notices' );
	$user    = wp_get_current_user();
	$next_id = 0;

	if ( ! $queue || ! $user || ! $user->ID || ! $user->has_cap( 'manage_options' ) ) {
		return;
	}

	// Find the first message that was not dismissed by the current user.
	$dismissed = $user->get( '_dm_dismissed' );
	foreach ( $queue as $notice_id => $notice ) {
		$active_since = dm_option_get( $notice['inst'], 'active_since' );

		if ( empty( $dismissed[ $notice_id ] ) || $dismissed[ $notice_id ] < $active_since ) {
			$next_id = $notice_id;
			break;
		}
	}

	// Bail, if the current user dismissed all messages.
	if ( ! $next_id ) {
		return;
	}

	$notice    = $queue[ $next_id ];
	$ajax_args = [
		'action'      => 'dm_notice_dismiss',
		'id'          => $next_id,
		'_ajax_nonce' => wp_create_nonce( "dismiss-notice-$next_id" ),
	];

	// HTML with notification contents.
	printf(
		'<div id="divimode-notice-%1$s" class="notice notice-%2$s is-dismissible">%3$s
		<button type="button" class="notice-dismiss"><span class="screen-reader-text">%4$s</span></button>
		</div>',
		(int) $next_id,
		esc_attr( $notice['type'] ),
		wp_kses( $notice['html'], dm_kses_notice_html() ),
		esc_html__( 'Dismiss this notice.' ) // No text-domain is intentional.
	);

	// JS logic to hide the notification.
	printf(
		'<script>
		jQuery(function($){
			var box = $("#divimode-notice-%1$s");
			
			box.find(".notice-dismiss").on("click", function(){
				// Ajax request to remove the box.
				$.post("%2$s", %3$s)
				// Hide the notification after the ajax request finished.
				.always(function() {
					box.fadeOut(function() { box.remove(); });
				});
			});
		});
		</script>',
		(int) $next_id,
		esc_url_raw( admin_url( 'admin-ajax.php' ) ),
		wp_json_encode( $ajax_args )
	);
}


// -----------------------------------------------------------------------------


/**
 * Hook up shared library functions.
 *
 * @since 3.0.0
 */
function dm_shared_hooks() {
	// Inject default debug details into JS output where needed.
	add_filter( 'divimode_debug_infos', 'dm_metabox_generate_debug_infos' );

	// Display pending divimode notifications.
	add_action( 'admin_notices', 'dm_admin_notice_show' );

	// Ajax handler to dismiss divimode notifications.
	add_action( 'wp_ajax_dm_notice_dismiss', 'dm_admin_notice_ajax_dismiss' );

	// Cron handler.
	add_action( 'dm_admin_cron_notice_check', 'dm_admin_notice_fetch', 10, 2 );
}

dm_shared_hooks();
