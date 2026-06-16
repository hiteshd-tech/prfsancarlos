<?php
/**
 * Charitable Connect callback endpoint.
 *
 * Called by upgrade.wpcharitable.com to activate or install Pro. This file
 * loads WordPress and defines CHARITABLE_CONNECT_CALLBACK so the main plugin
 * can run the actual callback on init at priority -9999, before any redirect
 * (e.g. to login) runs. That way the upgrade server's request (no cookies)
 * still gets JSON instead of the login page.
 *
 * @package Charitable
 * @since 1.8.9.6
 */

if ( ! defined( 'CHARITABLE_CONNECT_CALLBACK' ) ) {
	define( 'CHARITABLE_CONNECT_CALLBACK', true );
}

$wp_root = dirname( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) );
if ( ! file_exists( $wp_root . '/wp-load.php' ) ) {
	header( 'Content-Type: application/json' );
	echo json_encode( array( 'success' => false, 'data' => array( 'message' => 'WordPress not found.' ) ) );
	exit;
}
require_once $wp_root . '/wp-load.php';

// If Charitable ran the callback on init -9999, execution already exited. Otherwise fallback.
if ( ! function_exists( 'charitable' ) || ! class_exists( 'Charitable_Admin_Connect' ) ) {
	header( 'Content-Type: application/json' );
	wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'Charitable not loaded.' ) ) );
}
Charitable_Admin_Connect::get_instance()->process();
