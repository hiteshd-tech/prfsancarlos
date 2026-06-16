<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Basic theme for the Campaign Builder.
 *
 * @package Charitable/Campaign Builder/Themes
 * @author  WP Charitable
 * @since   1.0.0
 * @version 1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#3418d2'; // phpcs:ignore
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#005eff'; // phpcs:ignore
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#00a1ff'; // phpcs:ignore
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#ec5f25'; // phpcs:ignore

$wrapper = '.charitable-preview #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore
