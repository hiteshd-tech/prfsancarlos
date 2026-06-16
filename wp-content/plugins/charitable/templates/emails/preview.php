<?php
/**
 * Email Preview
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Emails
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['email_id'] ) ) { // phpcs:ignore
	return;
}

$charitable_email        = charitable_get_helper( 'emails' )->get_email( esc_html( $_GET['email_id'] ) ); // phpcs:ignore
$charitable_email_object = new $charitable_email();

echo $charitable_email_object->preview(); // phpcs:ignore