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
 * @version   1.7.0.8
 * @version   1.8.7
 * @version   1.8.8.6
 */

global $post;

$charitable_meta = charitable_get_donation( $post->ID )->get_donation_meta();

?>
<div id="charitable-donation-details-metabox" class="charitable-metabox">
	<dl>
	<?php do_action( 'charitable_before_admin_donation_details_list', $post ); ?>
	<?php foreach ( $charitable_meta as $charitable_key => $charitable_details ) : ?>
		<dt><?php echo esc_html( $charitable_details['label'] ); ?></dt>
		<dd>
		<?php
		if ( 'gateway_transaction_id' === $charitable_key && ! empty( $charitable_details['value'] ) ) {
			$charitable_receipt_url = get_post_meta( $post->ID, '_donation_receipt_url', true );
			if ( ! empty( $charitable_receipt_url ) ) {
				echo '<a target="_blank" href="' . esc_url( $charitable_receipt_url ) . '">' . esc_html( $charitable_details['value'] ) . '</a>';
			} else {
				echo esc_html( $charitable_details['value'] );
			}
		} else {
			echo wp_kses_post( $charitable_details['value'] );
		}
		?>
		</dd>
	<?php endforeach ?>
	<?php do_action( 'charitable_after_admin_donation_details_list', $post ); ?>
	</dl>
</div>
