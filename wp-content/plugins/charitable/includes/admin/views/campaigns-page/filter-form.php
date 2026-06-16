<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the Filters modal.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Campaigns Page
 * @since   1.6.36
 * @version 1.8.9.1
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

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$charitable_start_date_from = isset( $_GET['start_date_from'] ) ? charitable_sanitize_date_filter_format( wp_unslash( $_GET['start_date_from'] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by charitable_sanitize_date_filter_format
$charitable_start_date_to   = isset( $_GET['start_date_to'] ) ? charitable_sanitize_date_filter_format( wp_unslash( $_GET['start_date_to'] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by charitable_sanitize_date_filter_format
$charitable_end_date_from   = isset( $_GET['end_date_from'] ) ? charitable_sanitize_date_filter_format( wp_unslash( $_GET['end_date_from'] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by charitable_sanitize_date_filter_format
$charitable_end_date_to     = isset( $_GET['end_date_to'] ) ? charitable_sanitize_date_filter_format( wp_unslash( $_GET['end_date_to'] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by charitable_sanitize_date_filter_format
$charitable_status          = isset( $_GET['status'] ) ? esc_html( wp_unslash( $_GET['status'] ) ) : 'all'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by esc_html
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$charitable_statuses = array(
	'all'     => __( 'All', 'charitable' ),
	'pending' => __( 'Pending', 'charitable' ),
	'draft'   => __( 'Draft', 'charitable' ),
	'active'  => __( 'Active', 'charitable' ),
	'finish'  => __( 'Finished', 'charitable' ),
	'publish' => __( 'Published', 'charitable' ),
);
?>
<div id="charitable-campaigns-filter-modal" style="display: none" class="charitable-campaigns-modal <?php echo esc_attr( $charitable_modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php esc_html_e( 'Filter Campaigns', 'charitable' ); ?></h3>
	<form class="charitable-campaigns-modal-form charitable-modal-form" method="get" action="">
		<input type="hidden" name="post_type" class="post_type_page" value="campaign">
		<?php wp_nonce_field( 'charitable_filter_campaigns', 'charitable_nonce', false ); ?>
		<fieldset>
			<legend><?php esc_html_e( 'Filter by Start Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-filter-start_date_from" name="start_date_from" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_start_date_from ); ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-filter-start_date_to" name="start_date_to" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_start_date_to ); ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e( 'Filter by End Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-filter-end_date_from" name="end_date_from" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_end_date_from ); ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-filter-end_date_to" name="end_date_to" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_end_date_to ); ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-campaigns-filter-status"><?php esc_html_e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-campaigns-filter-status" name="post_status">
			<?php foreach ( $charitable_statuses as $charitable_key => $charitable_label ) : ?>
				<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_status, $charitable_key ); ?>><?php echo esc_html( $charitable_label ); ?></option>
			<?php endforeach ?>
		</select>
		<?php
		/**
		 * Add additional filters to the form.
		 *
		 * @since 1.6.36
		 */
		do_action( 'charitable_filter_campaigns_form' );
		?>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply', 'charitable' ); ?></button>
	</form>
</div>
