<?php
/**
 * Charitable Security Hooks.
 *
 * Action/filter hooks used for Charitable Security Settings API.
 *
 * @package   Charitable/Functions/Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9
 * @version   1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the security settings class.
// Use a direct path since charitable() may not be available yet during load_dependencies().
$charitable_security_settings_path = dirname( __FILE__ ) . '/class-charitable-security-settings.php';
if ( file_exists( $charitable_security_settings_path ) ) {
	require_once $charitable_security_settings_path;
}

// Always instantiate the security settings class (needed for frontend CAPTCHA functionality).
// Defer instantiation until after plugins_loaded to ensure charitable() is available.
add_action( 'plugins_loaded', function() {
	if ( function_exists( 'charitable' ) ) {
		Charitable_Security_Settings::get_instance();
	}
}, 5 );

// Only register admin settings hooks in admin area.
if ( is_admin() ) {
	/**
	 * Add the security settings group.
	 *
	 * @see Charitable_Security_Settings::add_security_settings_group()
	 */
	add_filter( 'charitable_security_admin_settings_groups', function( $groups ) {
		if ( function_exists( 'charitable' ) ) {
			return Charitable_Security_Settings::get_instance()->add_security_settings_group( $groups );
		}
		return $groups;
	} );

	/**
	 * Add the security settings.
	 *
	 * @see Charitable_Security_Settings::add_settings()
	 */
	add_filter( 'charitable_settings_tab_fields_security', function( $settings ) {
		if ( function_exists( 'charitable' ) ) {
			return Charitable_Security_Settings::get_instance()->add_settings( $settings );
		}
		return $settings;
	} );

	/**
	 * Hook into settings save to reset security notice dismissal if needed.
	 *
	 * @see Charitable_Security_Settings::maybe_reset_security_notice_dismissal()
	 */
	add_filter( 'charitable_save_settings', function( $values, $new_values ) {
		if ( function_exists( 'charitable' ) ) {
			return Charitable_Security_Settings::get_instance()->maybe_reset_security_notice_dismissal( $values, $new_values );
		}
		return $values;
	}, 10, 2 );
}

