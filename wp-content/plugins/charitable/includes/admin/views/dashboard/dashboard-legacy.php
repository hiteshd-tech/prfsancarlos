<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main reports page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.1
 * @version   1.8.8.6
 */

require_once ABSPATH . 'wp-admin/includes/translation-install.php';

$charitable_gateways        = Charitable_Gateways::get_instance()->get_active_gateways_names();
$charitable_campaigns       = wp_count_posts( 'campaign' );
$charitable_campaigns_count = $charitable_campaigns->publish + $charitable_campaigns->draft + $charitable_campaigns->future + $charitable_campaigns->pending + $charitable_campaigns->private;
$charitable_emails          = charitable_get_helper( 'emails' )->get_enabled_emails_names();
$charitable_install         = isset( $_GET['install'] ) && $_GET['install']; // phpcs:ignore
$charitable_languages       = function_exists( 'wp_get_available_translations' ) ? wp_get_available_translations() : array();
$charitable_locale          = get_locale(); // phpcs:ignore
$charitable_language        = isset( $charitable_languages[ $charitable_locale ]['native_name'] ) ? $charitable_languages[ $charitable_locale ]['native_name'] : $charitable_locale;
$charitable_currency        = charitable_get_default_currency();
$charitable_currencies      = charitable_get_currency_helper()->get_all_currencies();

$charitable_dashboard = Charitable_Dashboard_Legacy::get_instance();
$charitable_start_date           = $charitable_dashboard->get_start_date();
$charitable_end_date             = $charitable_dashboard->get_end_date();
$charitable_days                 = $charitable_dashboard->get_days();

$charitable_notices = $charitable_dashboard->get_notices();

$charitable_show_gt_chart_notice          = apply_filters( 'charitable_show_growth_tools_dashboard_notice', $charitable_dashboard->maybe_show_dashboard_growth_tool_chart_notice() );
$charitable_show_gt_chart_notice_headline = '';

if ( $charitable_show_gt_chart_notice ) {

	$charitable_total_donations_array         = (array) wp_count_posts( 'donation' ); // the function caches this, so we shouldn't have to.
	$charitable_total_donations               = array_sum( $charitable_total_donations_array );
	$charitable_show_gt_chart_notice_headline = ( false !== $charitable_total_donations_array && is_array( $charitable_total_donations_array ) && $charitable_total_donations > 0 ) ? esc_html__( 'No donations recently? Let Charitable help!', 'charitable' ) . ' 🚀' : esc_html__( 'Excited to make your first donation? Let Charitable help!', 'charitable' ) . ' 🚀';

	if ( $charitable_total_donations_array['publish'] > 0 ) {
		$charitable_show_gt_chart_notice = false;
	}
	$charitable_suggestion = Charitable_Guide_Tools::get_instance()->get_suggestion( 'dashboard', $charitable_total_donations );
	if ( false === $charitable_suggestion || ! is_array( $charitable_suggestion ) || empty( $charitable_suggestion ) ) {
		$charitable_show_gt_chart_notice = false;
	}
}

$charitable_is_cached = $charitable_dashboard->maybe_cache_dashboard();
$charitable_cached    = $charitable_dashboard->is_dashboard_data_cached();

$charitable_html = $charitable_dashboard->generate_dashboard_report_html();

ob_start();
?>
<div id="charitable-reports" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<?php
		/**
		 * Do or render something right before the dashboard area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_before_admin_dashboard' );
	?>

		<div class="tablenav top">
			<div class="alignleft actions">
				<h1><?php echo esc_html__( 'Dashboard', 'charitable' ); ?> <?php
				if ( $charitable_cached && $charitable_is_cached ) :
					echo '<span class="badge">Last cached ' . esc_html( $charitable_cached ) . '</span>';
				endif;
				?>
				</h1>
			</div>
			<div class="alignright">

				<form action="" method="post" target="_blank" class="charitable-report-print" id="charitable-dashboard-print">
					<input name="charitable_report_action" type="hidden" value="charitable_report_print_dashboard" />
					<input name="start_date" type="hidden" value="<?php echo esc_attr( $charitable_start_date ); ?>" />
					<input name="end_date" type="hidden" value="<?php echo esc_attr( $charitable_end_date ); ?>" />
					<input name="days" type="hidden" value="<?php echo esc_attr( $charitable_days ); ?>" />
					<?php wp_nonce_field( 'charitable_export_report', 'charitable_export_report_nonce' ); ?>
					<button value="Print" type="submit" class="button with-icon charitable-report-print-button" title="<?php echo esc_html__( 'Print Summary', 'charitable' ); ?>" class="button with-icon charitable-report-ui" data-nonce="<?php echo wp_create_nonce( 'charitable_export_report' ); // phpcs:ignore ?>"><label><?php echo esc_html__( 'Print', 'charitable' ); ?></label><img width="15" height="15" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/print.svg' ); ?>" alt=""></button>
				</form>

				<?php echo $charitable_dashboard->get_filter_dropdown(); // phpcs:ignore ?>

				<input type="hidden" value="<?php echo esc_html( $charitable_start_date ); ?>" name="charitable-dashboard-report-start-date" id="charitable-dashboard-report-start-date">
				<input type="hidden" value="<?php echo esc_html( $charitable_end_date ); ?>" name="charitable-dashboard-report-end-date" id="charitable-dashboard-report-end-date">

			</div>
			<br class="clear">
		</div>

		<?php
		/**
		 * Do or render something after the dashboard title bar but before the reporting.
		 *
		 * @since 1.8.2
		 */
		do_action( 'charitable_before_admin_dashboard_reports' );
		?>

		<div id="charitable-dashboard-report-container">

			<div class="charitable-dashboard-report">

				<!--- dashboard welcome start -->

				<?php
				/**
				 * Do or render something after the dashboard title bar but before the reporting.
				 *
				 * @since 1.8.2
				 */
				do_action( 'charitable_admin_dashboard_notifications' );
				?>

				<div class="charitable-dashboard-title-cards">

					<?php
						/**
						 * Do or render something above dashboard items (like a notice).
						 *
						 * @since 1.8.1.15
						 */
						do_action( 'charitable_after_getting_started_dashboard' );
					?>

				</div>

				<!--- dashboard welcome end -->

				<div class="charitable-headline-reports">

					<div id="charitable-dashboard-report-cards">
						<?php echo $charitable_html['charitable_cards']; // phpcs:ignore ?>
					</div>

					<div class="charitable-container charitable-report-ui charitable-headline-graph-container
					<?php
					if ( $charitable_show_gt_chart_notice ) :
						?>
						charitable-with-growth-tools<?php endif; ?>">

						<?php if ( $charitable_show_gt_chart_notice ) : ?>

							<div id="charitable-growth-tools-notice" class="charitable-growth-tools-notice charitable-growth-tools-dashboard charitable-hidden" data-notice-type="dashboard" data-nonce="<?php echo wp_create_nonce( 'charitable_dismiss_growth_tools' ); // phpcs:ignore ?>">

								<div class="charitable-growth-tools-notice-interior">

									<?php

										$charitable_notice_html = Charitable_Guide_Tools::get_instance()->get_dashboard_notice_html( $charitable_suggestion, $charitable_show_gt_chart_notice_headline );
										echo $charitable_notice_html; // phpcs:ignore

									?>

								</div>

							</div>

						<?php endif; ?>

						<div id="charitable-headline-graph" class="charitable-headline-graph"></div>

					</div>

				</div>

				<div class="charitable-section charitable-section-flexible">

					<div id="charitable-dashboard-report-sections">
						<?php echo $charitable_html['charitable_reports']; // phpcs:ignore ?>
					</div>

				</div>

			</div>

		</div>

	<?php
		/**
		 * Do or render something right after the dashboard area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_after_admin_dashboard' );
	?>
</div>
<?php
echo ob_get_clean(); // phpcs:ignore
