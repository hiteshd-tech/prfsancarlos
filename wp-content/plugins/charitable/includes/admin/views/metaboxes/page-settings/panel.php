<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a single panel's content in the Campaign Settings meta box.
 *
 * @author    WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.6.0
 * @version   1.6.0
 * @version   1.8.8.6
 */


if ( ! array_key_exists( 'fields', $view_args ) || empty( $view_args['fields'] ) ) {
	return;
}

$charitable_form = new Charitable_Admin_Form();
$charitable_form->set_fields( $view_args['fields'] );
$charitable_form->view()->render_fields();
