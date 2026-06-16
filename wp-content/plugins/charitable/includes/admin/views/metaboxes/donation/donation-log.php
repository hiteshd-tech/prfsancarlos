<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.35
 * @version   1.8.8.6
 */

global $post;

$charitable_logs             = charitable_get_donation( $post->ID )->log()->get_log();
$charitable_date_time_format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
?>
<div id="charitable-donation-log-metabox" class="charitable-metabox">
	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date &amp; Time', 'charitable' ); ?></th>
				<th><?php esc_html_e( 'Log', 'charitable' ); ?></th>
			</th>
		</thead>
		<?php foreach ( $charitable_logs as $charitable_log ) : ?>
		<tr>
			<td><?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $charitable_log['time'] ), $charitable_date_time_format ) ); ?></td>
			<td><?php echo ( $charitable_log['message'] ); // phpcs:ignore ?></td>
		</tr>
		<?php endforeach ?>
	</table>
</div>
