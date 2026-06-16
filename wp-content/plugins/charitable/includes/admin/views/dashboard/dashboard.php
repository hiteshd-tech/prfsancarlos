<?php
/**
 * Display the Charitable dashboard new page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Dashboard New
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 * @version   1.8.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The header is automatically included by Charitable_Post_Types::admin_header()
// when the page is registered in the applicable_post_types array

// Get the dashboard instance
$charitable_dashboard = Charitable_Dashboard::get_instance();
?>
<div id="charitable-dashboard-v2" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php do_action( 'charitable_maybe_show_notification' ); ?>

	<?php
		/**
		 * Do or render something right before the dashboard new area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_before_admin_dashboard_v2' );
	?>

	<?php $charitable_dashboard->render_dashboard_header(); ?>

	<?php $charitable_dashboard->render_dashboard_notifications(); ?>

	<div class="charitable-dashboard-v2-content">
		<div class="charitable-dashboard-v2-layout">
			<!-- Left Column -->
			<div class="charitable-dashboard-v2-left-column">
				<?php $charitable_dashboard->render_stats_section(); ?>

				<?php $charitable_dashboard->render_tabs_section(); ?>

				<?php $charitable_dashboard->render_upgrade_section(); ?>

				<?php 
				// Show Quick Access in left column for Pro users (when upgrade section is hidden)
				if ( charitable_is_pro() ) {
					$charitable_dashboard->render_quick_access_section();
				}
				?>
			</div>

			<!-- Right Column -->
			<div class="charitable-dashboard-v2-right-column">
				<?php $charitable_dashboard->render_enhance_campaign_section(); ?>

				<?php $charitable_dashboard->render_latest_updates_section(); ?>

				<?php 
				// Show Quick Access in right column for non-Pro users
				if ( ! charitable_is_pro() ) {
					$charitable_dashboard->render_quick_access_section();
				}
				?>
			</div>
		</div>
	</div>

	<?php
		/**
		 * Do or render something right after the dashboard new area.
		 *
		 * @since 1.8.1
		 */
		do_action( 'charitable_after_admin_dashboard_v2' );
	?>

	<?php $charitable_dashboard->render_dashboard_scripts(); ?>
</div>