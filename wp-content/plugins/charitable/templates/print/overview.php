<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format this page for PDF or print.
 *
 * Override this template by copying it to yourtheme/charitable/charitable-pdf-receipts/pdf.php
 *
 * @package Charitable-PDF-Receipts
 * @author  WPCharitable
 * @since   1.8.1
 * @version 1.8.8.6
 */

$charitable_action = $view_args['action'] === 'print' ? 'print' : 'download';

$charitable_admin_2_0_css = $view_args['charitable_admin_2_0_css'];

$charitable_donations                     = $view_args['donations'];
$charitable_donation_total                = $view_args['donation_total'];
$charitable_donation_average              = $view_args['donation_average'];
$charitable_total_count_donors            = $view_args['total_count_donors'];
$charitable_total_amount_refunds          = $view_args['total_amount_refunds'];
$charitable_total_count_refunds           = $view_args['total_count_refunds'];
$charitable_donation_breakdown_html       = $view_args['donation_breakdown_html'];
$charitable_activity_list                 = $view_args['activity_list'];
$charitable_top_donors                    = $view_args['top_donors'];
$charitable_top_campaigns                 = $view_args['top_campaigns'];
$charitable_payment_methods_list          = $view_args['payment_methods_list'];
$charitable_headline_chart_options        = $view_args['headline_chart_options'];
$charitable_payment_methods_chart_options = $view_args['payment_methods_chart_options'];

$charitable_campaign_id = $view_args['campaign_id'];
$charitable_campaign    = $view_args['campaign'];

$charitable_category_id        = $view_args['category_id'];
$charitable_category_term_name = $view_args['category_term_name'];

$charitable_chart_js = $view_args['charitable_chart_js'];

// convert php array values into a javascript array.
$charitable_headline_chart_options_donation_axis = wp_json_encode( $charitable_headline_chart_options['donation_axis'] );
$charitable_headline_chart_options_date_axis     = wp_json_encode( $charitable_headline_chart_options['date_axis'] );

$charitable_payment_methods_chart_options_payment_percentages = wp_json_encode( $charitable_payment_methods_chart_options['payment_percentages'] );
$charitable_payment_methods_chart_options_payment_labels      = wp_json_encode( $charitable_payment_methods_chart_options['payment_labels'] );

$charitable_currency_symbol = $view_args['currency_symbol'];
$charitable_currency_symbol = ( false !== $charitable_currency_symbol ) ? html_entity_decode( $charitable_currency_symbol ) : '$';

$charitable_start_date = ! empty( $view_args['start_date'] ) ? $view_args['start_date'] : false;
$charitable_end_date   = ! empty( $view_args['end_date'] ) ? $view_args['end_date'] : false;

?>
<!DOCTYPE html>
<html>
	<head>
	<title><?php echo esc_html__( 'Overview Report', 'charitable' ); ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
			<?php
			echo file_get_contents( $charitable_admin_2_0_css ); // phpcs:ignore

			/**
			 * Add any custom styles to the PDF.
			 *
			 * @since 1.8.1
			 *
			 * @param array $view_args The view arguments.
			 */
			do_action( 'charitable_pdf_receipts_pdf_styles', $view_args );
			?>
		</style>
	</head>

	<body id="charitable-pdf" style="margin: 0p; padding: 0; width: 100%; border: 0;">

		<div class="charitable-overview-report"
		<?php
		if ( $charitable_action === 'download' ) :
			?>
			style="width: 100%; background-color: white; margin: 0; padding: 0;"<?php endif; ?>>

			<div class="charitable-headline-reports"
			<?php
			if ( $charitable_action === 'download' ) :
				?>
				style="margin: 0 auto; padding: 0;"<?php endif; ?>>

				<div class="charitable-print-header">
					<h1><?php echo esc_html__( 'Overview Report', 'charitable' ); ?></h1>
					<?php if ( $charitable_start_date && $charitable_end_date ) : ?>
						<p><?php echo esc_html( $charitable_start_date ); ?> - <?php echo esc_html( $charitable_end_date ); ?></p>
					<?php endif; ?>
					<?php if ( isset( $charitable_campaign->post_title ) && $charitable_campaign->post_title ) : ?>
						<p><?php echo esc_html__( 'Campaign', 'charitable' ); ?>: <?php echo esc_html( $charitable_campaign->post_title ); ?></p>
					<?php endif; ?>
					<?php if ( $charitable_category_term_name ) : ?>
						<p><?php echo esc_html__( 'Campaign Category:', 'charitable' ); ?>: <?php echo esc_html( $charitable_category_term_name ); ?></p>
					<?php endif; ?>
				</div>


				<div class="charitable-cards">
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $charitable_action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-donation-total-amount"><?php echo $charitable_donation_total; // phpcs:ignore ?></span></strong>
						<p><span id="charitable-top-donation-total-count"><?php echo count( $charitable_donations ); ?></span> <?php echo esc_html__( 'Total Donations (Net)', 'charitable' ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $charitable_action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-donation-average"><?php echo $charitable_donation_average; // phpcs:ignore ?></span></strong>
						<p><?php echo esc_html__( 'Average Donation', 'charitable' ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $charitable_action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-donor-count"><?php echo intval( $charitable_total_count_donors ); // phpcs:ignore ?></span></strong>
						<p><?php echo esc_html__( 'Donors', 'charitable' ); ?></p>
					</div>
					<div class="charitable-container charitable-report-ui charitable-card"
					<?php
					if ( $charitable_action === 'download' ) :
						?>
						style="width: 24.5%; display:inline-block;"<?php endif; ?>>
						<strong><span id="charitable-top-refund-total-amount"><?php echo charitable_format_money( $charitable_total_amount_refunds ); // phpcs:ignore ?></span></strong>
						<p><span id="charitable-top-refund-count"><?php echo intval( $charitable_total_count_refunds ); ?></span> <?php echo esc_html__( 'Refunds', 'charitable' ); ?></p>
					</div>
				</div>

				<?php if ( $charitable_action === 'print' ) : ?>
					<div class="charitable-container charitable-report-ui charitable-headline-graph-container">
						<div id="charitable-headline-graph" class="charitable-headline-graph">
						</div>
					</div>
				<?php endif; ?>

			</div>

		</div>

		<div class="
		<?php
		if ( $charitable_action === 'print' ) :
			?>
			tablenav charitable-section<?php endif; ?>"
			<?php
			if ( $charitable_action === 'download' ) :
				?>
			style="width: 100%; background-color: white; margin: 0px 0 0 0;"<?php endif; ?>>

			<div class="alignleft actions">
				<h4><?php echo esc_html__( 'Donations Breakdown', 'charitable' ); ?></h4>
			</div>

			<br class="clear">

			<table class="wp-list-table widefat fixed striped table-view-list donations-breakdown charitable-report-ui">
				<thead>
					<tr>
						<th scope="col" id="date" class="manage-column column-date"><span><?php echo esc_html__( 'Date', 'charitable' ); ?></span></a></th>
						<th scope="col" id="donations" class="manage-column column-donations"><?php echo esc_html__( 'Donations', 'charitable' ); ?></th>
						<th scope="col" id="no-of-donors" class="manage-column column-donors"><?php echo esc_html__( 'No. of Donors', 'charitable' ); ?></th>
						<th scope="col" id="refunds" class="manage-column column-refunds"><?php echo esc_html__( 'Refunds', 'charitable' ); ?></th>
						<th scope="col" id="net" class="manage-column column-net"><?php echo esc_html__( 'Net', 'charitable' ); ?></th>
					</tr>
				</thead>
				<tbody id="donations-breakdown-list">
					<?php echo $charitable_donation_breakdown_html; // phpcs:ignore ?>
				</tbody>
			</table>

			<br class="clear">

		</div>

		<div class="
		<?php
		if ( $charitable_action === 'print' ) :
			?>
			charitable-section-grid charitable-section-grid-column-flexible<?php endif; ?>">

			<div class="charitable-container charitable-report-card charitable-activity-report" data-section-name="activity">
				<div class="header">
					<h4><?php echo esc_html__( 'Activity', 'charitable' ); ?></h4>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
					<div class="the-list">
						<?php echo $charitable_activity_list; // phpcs:ignore ?>
					</div>
				</div>
			</div>

			<div class="charitable-container charitable-report-card charitable-top-donors-report">
				<div class="header">
					<h4><?php echo esc_html__( 'Top Donors', 'charitable' ); ?></h4>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
					<?php echo $charitable_top_donors; // phpcs:ignore ?>
				</div>
			</div>

			</div>

			<div class="charitable-section-grid one-third charitable-section-grid-column-flexible">

			<div class="charitable-container charitable-report-card charitable-top-campaigns-report">
				<div class="header">
					<h4><?php echo esc_html__( 'Top Campaigns', 'charitable' ); ?></h4>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
				<?php if ( ! empty( $charitable_top_campaigns ) ) : ?>
					<div class="the-list">
						<ul id="charitable-top-campaigns-list">
							<?php echo $charitable_top_campaigns; // phpcs:ignore ?>
						</ul>
					</div>
				<?php else : ?>
				<?php endif; ?>
				</div>
			</div>

			<div class="charitable-container charitable-report-card charitable-payment-methods-report">
				<div class="header">
					<h4><?php echo esc_html__( 'Payment Methods', 'charitable' ); ?></h4>
				</div>
				<div class="charitable-toggle-container charitable-report-ui">
					<div class="the-graph">
						<div id="charitable-payment-methods-graph" class="charitable-payment-methods-graph">
						</div>
						<div class="the-legend">
							<ul id="charitable-payment-methods-list">
								<?php echo $charitable_payment_methods_list; // phpcs:ignore ?>
							</ul>
						</div> <!-- the legend -->
					</div>
				</div>
			</div>

		</div>

		<?php if ( $charitable_action === 'print' ) : ?>

			<script src="<?php echo esc_url( $charitable_chart_js ); // phpcs:ignore ?>" id="charitable-apex-charts-js"></script>

			<script id="charitable-report-data-js">
					var charitable_reporting = {
						'currency_symbol' : "<?php echo $charitable_currency_symbol; // phpcs:ignore ?>",
						"headline_chart_options":
						{"donation_axis":<?php echo $charitable_headline_chart_options_donation_axis; // phpcs:ignore ?>,
							"date_axis":<?php echo $charitable_headline_chart_options_date_axis; // phpcs:ignore ?>
						},
						"payment_methods_chart_options":{
							"payment_percentages":<?php echo $charitable_payment_methods_chart_options_payment_percentages; // phpcs:ignore ?>,
							"payment_labels":<?php echo $charitable_payment_methods_chart_options_payment_labels; // phpcs:ignore ?>
						}
					};
			</script>

			<script type="text/javascript" id="charitable-report-headline-chart-js">
				var charitable_headline_chart = new ApexCharts( document.querySelector("#charitable-headline-graph"), {
					chart: {
						animations: {
							enabled: false
						},
						background: '#fff',
						foreColor: "#757781",
						type: 'area',
						width: '900px',
						stacked: true,
						toolbar: {
							show: false
						},
						zoom: {
							enabled: false
						}
					},
					colors: ["#5AA15226"],
					grid: {
						borderColor: "#C9D4CA",
						clipMarkers: false,
						yaxis: {
							lines: {
								show: true
							}
						}
					},
					dataLabels: {
						enabled: false
					},
					series: [{
						name: '<?php echo esc_html__( 'Donations', 'charitable' ); ?>',
						data: charitable_reporting.headline_chart_options.donation_axis
					}],
					stroke: {
						width: 3,
						colors: ["#5AA152"]
					},
					fill: {
						type: "solid"
					},
					markers: {
						size: 5,
						colors: ["#FFFFFF"],
						strokeColor: "#5AA152",
						strokeWidth: 4
					},
					legend: {
						show: false
					},
					xaxis: {
						categories: charitable_reporting.headline_chart_options.date_axis,
					},
					yaxis: {
						labels: {
							formatter: function (val) {
								return charitable_decodeHtml(charitable_reporting.currency_symbol) + val.toFixed(2)
							}
						}
					}
				});

				/* utils */

				/**
				 * Util function that decodes HTML entities.
				 *
				 * @since 1.8.1
				 *
				 */
				function charitable_decodeHtml( html ) {
					var txt = document.createElement("textarea");
					txt.innerHTML = html;
					return txt.value;
				}

				charitable_headline_chart.render();

				var charitable_payment_chart = new ApexCharts( document.querySelector("#charitable-payment-methods-graph"), {
					series: charitable_reporting.payment_methods_chart_options.payment_percentages,
					labels: charitable_reporting.payment_methods_chart_options.payment_labels,
					colors: [
						'#d21561', '#9e36f9', '#F99E36', '#2B66D1', '#5AA152'
					],
					chart: {
						type: 'donut',
						width: '75%',
						toolbar: {
							autoSelected: "pan",
							show: false
						}
					},
					dataLabels: {
						enabled: false
					},
					yaxis: {
						labels: {
							formatter: (value) => {
								return value + '%'
							}
						}
					},
					plotOptions: {
						pie: {
							donut: {
								size: '80%'
							}
						}
					},
					total: {
						show: true,
						showAlways: true,
						label: 'Total',
						fontSize: '22px',
						fontFamily: 'Helvetica, Arial, sans-serif',
						fontWeight: 600,
						color: '#373d3f',
						formatter: function (w) {
							return w.globals.seriesTotals.reduce((a, b) => {
								return a + b
							}, 0)
						}
					},
					legend: {
						show: false,
						showForSingleSeries: true,
						showForNullSeries: true,
						showForZeroSeries: true,
						position: 'bottom',
						horizontalAlign: 'center',
						floating: false,
						fontSize: '14px',
						fontFamily: 'Helvetica, Arial',
						fontWeight: 400,
						inverseOrder: true,
						customLegendItems: [],
						offsetX: 0,
						offsetY: 0,
						labels: {
							useSeriesColors: false
						},
						markers: {
							width: 12,
							height: 12,
							strokeWidth: 0,
							strokeColor: '#fff',
							radius: 12,
							offsetX: 0,
							offsetY: 0,
							customHTML: function () {
								return '<span class="custom-marker">test</span>'
							}
						},
						itemMargin: {
							horizontal: 5,
							vertical: 0
						}
					}
				});

				charitable_payment_chart.render();

				window.onload = function() { window.print(); }

			</script>

		<?php endif; ?>

	</body>

</html>
