<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the "advanced" reports page.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8,1
 * @version   1.8.1
 * @version   1.8.8.6
 */

$charitable_report_args = charitable_reports_get_advanced_report_args();

$charitable_reports = Charitable_Reports::get_instance();

if ( charitable_is_pro() ) :

	$charitable_reports->init_with_array( $charitable_report_args['report_type'] );

	$charitable_advanced_report_data = $charitable_reports->get_advanced_data_by_report_type( $charitable_report_args );

else :

	$charitable_advanced_report_data = $charitable_reports->get_data_sample( 'advanced' );

endif;

$charitable_reports->maybe_load_scripts();
$charitable_reports->maybe_add_reports_cta();

$charitable_start_date_compare_from = $charitable_report_args['start_date_compare_from'];
$charitable_end_date_compare_from   = $charitable_report_args['end_date_compare_from'];
$charitable_start_date_compare_to   = $charitable_report_args['start_date_compare_to'];
$charitable_end_date_compare_to     = $charitable_report_args['end_date_compare_to'];

?>

<div class="tablenav top with-margin">
	<div class="alignleft actions">
		<?php if ( charitable_is_pro() ) : ?>
		<label for="report-campaign-filter" class="screen-reader-text"><?php echo esc_html__( 'Select Report', 'charitable' ); ?></label>
			<div class="charitable-datepicker-container"><label for="charitable-reports-start_date"><?php echo esc_html__( 'Select Report', 'charitable' ); ?>:</label>
			<?php echo $charitable_reports->get_advanced_report_type_dropdown( $charitable_report_args['report_type'] ); // phpcs:ignore ?>
		</div>
		<?php else : ?>
		<label for="report-campaign-filter" class="screen-reader-text"><?php echo esc_html__( 'Available Reports', 'charitable' ); ?></label>
			<div class="charitable-datepicker-container"><label for="charitable-reports-start_date"><?php echo esc_html__( 'Available Reports', 'charitable' ); ?>:</label>
			<?php echo $charitable_reports->get_advanced_report_type_dropdown( $charitable_report_args['report_type'] ); // phpcs:ignore ?>
		</div>
		<?php endif; ?>
	</div>
	<div class="alignright">

		<div id="charitable-advanced-date-pickers">
			<?php if ( charitable_is_pro() ) : ?>

				<div class="charitable-advanced-date-picker" data-report-type="lybunt">
					<label><?php echo esc_html__( 'Compare', 'charitable' ); ?> <input type="text" id="charitable-reports-topnav-datepicker-comparefrom-lybunt" class="charitable-reports-datepicker charitable-datepicker-ranged" data-start-date="<?php echo esc_html( $charitable_start_date_compare_from ); ?>" data-end-date="<?php echo esc_html( $charitable_end_date_compare_from ); ?>" value="<?php echo esc_html( $charitable_start_date_compare_from ); ?> - <?php echo esc_html( $charitable_end_date_compare_from ); ?>" />
					<?php echo esc_html__( 'to', 'charitable' ); ?> <input type="text" id="charitable-reports-topnav-datepicker-compareto-lybunt" class="charitable-reports-datepicker charitable-datepicker-ranged" data-start-date="<?php echo esc_html( $charitable_start_date_compare_to ); ?>" data-end-date="<?php echo esc_html( $charitable_end_date_compare_to ); ?>" value="<?php echo esc_html( $charitable_start_date_compare_to ); ?> - <?php echo esc_html( $charitable_end_date_compare_to ); ?>" /></label>
				</div>

				<?php do_action( 'charitable_report_advanced_date_pickers' ); ?>

			<?php endif; ?>
		</div>

		<div id="charitable-advanced-filter-buttons">
			<?php if ( charitable_is_pro() ) : ?>

				<div class="charitable-datepicker-container charitable-advanced-filter-button" data-report-type="lybunt">
					<a href="#" class="button button-primary" id="charitable-reports-filter-button" data-filter-type="advanced"><?php echo esc_html__( 'Filter', 'charitable' ); ?></a>
				</div>

				<?php do_action( 'charitable_report_advanced_filter' ); ?>
			<?php endif; ?>
		</div>

	</div>
	<br class="clear">
</div>

<div class="charitable-advanced-report">

	<div class="charitable-container charitable-title-card">

		<div class="charitable-title-card-content">

			<?php echo $charitable_reports->generate_title_card_html( $charitable_report_args['report_type'] );  // phpcs:ignore  ?>

		</div>

	</div>

	<div id="charitable-report-advanced-container">

		<?php echo $charitable_reports->generate_advanced_breakdown_report_html( $charitable_report_args['report_type'], $charitable_advanced_report_data, $charitable_report_args );  // phpcs:ignore  ?>

	</div>

	<br class="clear">

</div>

<br class="clear">