<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the Filters modal.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Donations Page
 * @since   1.0.0
 * @version 1.6.39
 * @version 1.8.8.6
 */

/**
 * Filter the class to use for the modal window.
 *
 * @since 1.0.0
 *
 * @param string $class The modal window class.
 */
$charitable_modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );

$charitable_campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : ''; // phpcs:ignore
$charitable_campaigns   = get_posts(
	array(
		'post_type'   => Charitable::CAMPAIGN_POST_TYPE,
		'nopaging'    => true,
		'post_status' => array( 'draft', 'pending', 'private', 'publish' ),
		'perm'        => 'readable',
	)
);

$charitable_start_date  = isset( $_GET['start_date'] ) ? charitable_sanitize_date_filter_format( $_GET['start_date'] ) : null; // phpcs:ignore
$charitable_end_date    = isset( $_GET['end_date'] ) ? charitable_sanitize_date_filter_format( $_GET['end_date'] ) : null; // phpcs:ignore
$charitable_post_status = isset( $_GET['post_status'] ) ? esc_html( $_GET['post_status'] ) : 'all'; // phpcs:ignore

?>
<div id="charitable-donations-filter-modal" style="display: none" class="charitable-donations-modal <?php echo esc_attr( $charitable_modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php esc_html_e( 'Filter Donations', 'charitable' ); ?></h3>
	<form class="charitable-donations-modal-form charitable-modal-form" method="get" action="">
		<input type="hidden" name="post_type" class="post_type_page" value="donation">
		<?php wp_nonce_field( 'charitable_filter_campaigns', 'charitable_nonce', false ); ?>
		<fieldset>
			<legend><?php esc_html_e( 'Filter by Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-filter-start_date" name="start_date" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_start_date ); ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-filter-end_date" name="end_date" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_end_date ); ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-donations-filter-status"><?php esc_html_e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-donations-filter-status" name="post_status">
			<option value="all" <?php selected( $charitable_post_status, 'all' ); ?>><?php esc_html_e( 'All', 'charitable' ); ?></option>
			<?php foreach ( charitable_get_valid_donation_statuses() as $charitable_key => $status ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
				<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_post_status, $charitable_key ); ?>><?php echo esc_html( $status ); ?></option>
			<?php endforeach ?>
		</select>
		<label for="charitable-donations-filter-campaign"><?php esc_html_e( 'Filter by Campaign', 'charitable' ); ?></label>
		<select id="charitable-donations-filter-campaign" name="campaign_id">
			<option value="all"><?php esc_html_e( 'All Campaigns', 'charitable' ); ?></option>
			<?php foreach ( $charitable_campaigns as $charitable_campaign ) : ?>
				<option value="<?php echo esc_attr( $charitable_campaign->ID ); ?>" <?php selected( $charitable_campaign_id, $charitable_campaign->ID ); ?>><?php echo esc_html( get_the_title( $charitable_campaign->ID ) ); ?></option>
			<?php endforeach ?>
		</select>
		<?php
		/**
		 * Add additional fields to the end of the donations filter form.
		 *
		 * @since 1.4.0
		 */
		do_action( 'charitable_filter_donations_form' );
		?>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'charitable' ); ?></button>
	</form>
</div>
