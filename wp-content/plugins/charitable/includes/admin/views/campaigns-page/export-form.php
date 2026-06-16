<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the export button in the campaign exports box.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Campaigns Page
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.8.9.1
 * @version   1.8.8.6
 */

/**
 * Filter the class to use for the modal window.
 *
 * @since 1.0.0
 *
 * @param string $class The class name.
 */
$charitable_modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );

// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$charitable_start_date_from = isset( $_GET['start_date_from'] ) ? charitable_sanitize_date_export_format( wp_unslash( $_GET['start_date_from'] ) ) : null;
$charitable_start_date_to   = isset( $_GET['start_date_to'] ) ? charitable_sanitize_date_export_format( wp_unslash( $_GET['start_date_to'] ) ) : null;
$charitable_end_date_from   = isset( $_GET['end_date_from'] ) ? charitable_sanitize_date_export_format( wp_unslash( $_GET['end_date_from'] ) ) : null;
$charitable_end_date_to     = isset( $_GET['end_date_to'] ) ? charitable_sanitize_date_export_format( wp_unslash( $_GET['end_date_to'] ) ) : null;
$charitable_status          = isset( $_GET['post_status'] ) ? esc_html( wp_unslash( $_GET['post_status'] ) ) : 'any';
$charitable_report_type     = isset( $_GET['report_type'] ) ? esc_html( wp_unslash( $_GET['report_type'] ) ) : 'campaigns';
// phpcs:enable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

/**
 * Filter the type of exportable report types.
 *
 * @since 1.6.0
 *
 * @param array $types Types of reports.
 */
$charitable_report_types = apply_filters(
	'charitable_campaign_export_report_types',
	array(
		'campaigns' => __( 'Campaigns', 'charitable' ),
	)
);

$charitable_statuses = array(
	'any'     => __( 'All', 'charitable' ),
	'pending' => __( 'Pending', 'charitable' ),
	'draft'   => __( 'Draft', 'charitable' ),
	'active'  => __( 'Active', 'charitable' ),
	'finish'  => __( 'Finished', 'charitable' ),
	'publish' => __( 'Published', 'charitable' ),
);

?>
<div id="charitable-campaigns-export-modal" style="display: none" class="charitable-campaigns-modal <?php echo esc_attr( $charitable_modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php esc_html_e( 'Export Campaigns', 'charitable' ); ?></h3>
	<form class="charitable-campaigns-modal-form charitable-modal-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<?php wp_nonce_field( 'charitable_export_campaigns', '_charitable_export_nonce' ); ?>
		<input type="hidden" name="charitable_action" value="export_campaigns" />
		<input type="hidden" name="page" value="charitable-campaigns-table" />
		<input type="hidden" name="post_type" class="post_type_page" value="campaign" />
		<fieldset>
			<legend><?php esc_html_e( 'Filter by Start Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-export-start_date_from" name="start_date_from" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_start_date_from ); ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-export-start_date_to" name="start_date_to" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_start_date_to ); ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e( 'Filter by End Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-export-end_date_from" name="end_date_from" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_end_date_from ); ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-export-end_date_to" name="end_date_to" class="charitable-datepicker" autocomplete="off" value="<?php echo esc_attr( $charitable_end_date_to ); ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-campaigns-export-status"><?php esc_html_e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-campaigns-export-status" name="status">
			<?php foreach ( $charitable_statuses as $charitable_key => $charitable_label ) : ?>
				<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_status, $charitable_key ); ?>><?php echo esc_html( $charitable_label ); ?></option>
			<?php endforeach ?>
		</select>
		<?php if ( count( $charitable_report_types ) > 1 ) : ?>
			<label for="charitable-campaign-export-report-type"><?php esc_html_e( 'Type of Report', 'charitable' ); ?></label>
			<select id="charitable-campaign-export-report-type" name="report_type">
			<?php foreach ( $charitable_report_types as $charitable_key => $charitable_report_label ) : ?>
				<option value="<?php echo esc_attr( $charitable_key ); ?>"><?php echo esc_html( $charitable_report_label ); ?></option>
			<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="hidden" name="report_type" value="<?php echo esc_attr( key( $charitable_report_types ) ); ?>" />
			<?php
		endif;

		/**
		 * Add additional exports to the form.
		 *
		 * @since 1.6.36
		 */
		do_action( 'charitable_export_campaigns_form' );
		?>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Export', 'charitable' ); ?></button>
	</form>
</div>
