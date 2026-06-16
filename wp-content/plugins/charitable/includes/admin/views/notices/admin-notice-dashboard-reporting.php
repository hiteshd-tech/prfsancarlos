<?php
/**
 * Admin notice: Dashboard And Reporting.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 * @version   1.8.1
 * @version   1.8.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_builder_url   = 'https://wpcharitable.com/campaign-builder/';
$charitable_reporting_url = 'https://wpcharitable.com/dashboard-reporting/';

?><div class="charitable-admin-notice-campaign-builder" data-id="campaign-builder">
	<p>
		<?php

			printf(
				'%s <strong>%s</strong>%s <a target="_blank" href="%s" aria-label="%s">%s</a>. %s',
				esc_html__( 'Charitable v1.8.1 introduces an updated', 'charitable' ),
				esc_html__( 'dashboard and a new reporting tab', 'charitable' ),
				esc_html__( '. View and download information on your donations, activities, donors, and more. You can', 'charitable' ),
				$charitable_reporting_url, // phpcs:ignore
				esc_html__( 'Campaign Builder', 'charitable' ),
				esc_html__( 'learn more about these features here', 'charitable' ),
				esc_html__( 'As we continue to tweak and update these features, we welcome your feedback!', 'charitable' )
			);

			printf(
				' %s <a target="_blank" href="%s" aria-label="%s">%s</a> %s',
				esc_html__( 'Also ', 'charitable' ),
				$charitable_builder_url, // phpcs:ignore
				esc_html__( 'Campaign Builder', 'charitable' ),
				esc_html__( 'learn more about the recently released campaign builder', 'charitable' ),
				esc_html__( 'and use it in your next new campaign!', 'charitable' )
			);

			?>

	</p>

</div>