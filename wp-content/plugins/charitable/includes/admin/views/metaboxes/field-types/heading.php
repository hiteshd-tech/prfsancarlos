<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display heading in metabox.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.2.0
 * @version   1.5.0
 * @version   1.8.8.6
 */

$charitable_level = array_key_exists( 'level', $view_args ) ? $view_args['level'] : 'h4';
?>
<<?php echo wp_kses_post( $charitable_level ); ?> class="charitable-metabox-header" <?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>><?php echo esc_html( $view_args['title'] ); ?></<?php echo wp_kses_post( $charitable_level ); ?>>