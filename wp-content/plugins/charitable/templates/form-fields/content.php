<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display the suggested amounts field.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/content.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.4.14
 * @version 1.4.14
 */

if ( ! array_key_exists( 'content', $view_args['field'] ) ) {
	return;
}

echo $view_args['field']['content']; // phpcs:ignore
