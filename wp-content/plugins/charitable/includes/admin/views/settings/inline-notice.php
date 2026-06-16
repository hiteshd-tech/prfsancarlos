<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display notice in settings area.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.2.0
 * @version   1.8.8.6
 */

$charitable_notice_type = isset( $view_args['notice_type'] ) ? $view_args['notice_type'] : 'error';

?>
<div class="charitable-notice charitable-inline-notice charitable-notice-<?php echo esc_attr( $charitable_notice_type ); ?>" <?php echo esc_attr( charitable_get_arbitrary_attributes( $view_args ) ); ?>>
	<p><?php echo $view_args['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
</div>
