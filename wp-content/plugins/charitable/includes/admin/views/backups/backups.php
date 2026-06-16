<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main backups page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 * @version   1.8.8
 * @version   1.8.8.6
 */

ob_start();
?>

<div id="charitable-backups" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="charitable-intergrations-container intergration-backups">

		<img class="charitable-backups-logo" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/integrations/backups.png'; ?>" alt="<?php echo esc_html__( 'Integrate Backup Tools Into Charitable For Secure Data Protection', 'charitable' ); ?>">

		<h1><?php echo esc_html__( 'Making Website Backups Easy for WordPress', 'charitable' ); ?></h1>

		<h2><?php echo esc_html__( 'Backup tools ensure your fundraising campaigns and donor data are always safe and recoverable. Built with the same reliability as Charitable.', 'charitable' ); ?></h2>

		<div class="bullets-thumbnail">

			<div>
				<div class="charitable-screenshot">
					<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/backups-screenshot-thumbnail.png' ); ?>" alt="<?php echo esc_html__( 'Integrate Backup Tools Into Charitable For Enhanced Data Protection', 'charitable' ); ?>">
					<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/backups-screenshot-full.png' ); ?>" class="hover" data-lity=""></a>
				</div>
			</div>
			<div>
				<div class="vertical-wrapper">
					<ul>
						<li><?php echo esc_html__( 'Automated daily backups of your entire website.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Used by 2+ million websites.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Free features: Local backups, scheduled backups, one-click restore.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Pro features: Cloud storage, incremental backups, real-time monitoring, automated testing.', 'charitable' ); ?></li>
					</ul>
				</div>
			</div>

		</div>

		<div class="charitable-intergration-steps">

			<?php

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party(); // phpcs:ignore

			// determine if the Backups plugin is installed and activated.
			$charitable_is_backups_installed = $charitable_plugins_third_party->is_plugin_installed( 'duplicator' );
			$charitable_is_backups_active    = $charitable_plugins_third_party->is_plugin_activated( 'duplicator' );

			if ( ! $charitable_is_backups_installed ) {

				$charitable_install_button_html = $charitable_plugins_third_party->get_install_button_html( 'duplicator', 'Install Duplicator' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1"  data-status="install">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Install and Activate Duplicator', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Install the Duplicator plugin by clicking this button', 'charitable' ); ?></p>
					<?php echo $charitable_install_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

				<?php
			} elseif ( ! $charitable_is_backups_active ) {

				$charitable_basename = $charitable_plugins_third_party->get_basename_from_slug( 'backups' );

				if ( $charitable_basename ) :

					$charitable_activate_button_html = $charitable_plugins_third_party->get_activation_button_html( 'duplicator', 'Activate Duplicator' );

					?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="activate">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Activate Duplicator', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Activate Duplicator by clicking this button:', 'charitable' ); ?></p>
					<?php echo $charitable_activate_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

					<?php

			endif;

			} else {

				$charitable_setup_url = $charitable_plugins_third_party->get_setup_screen_for_plugin( 'duplicator' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="setup">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Setup Duplicator', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Setup Duplicator plugin by clicking this button:', 'charitable' ); ?></p>
					<a href="<?php echo esc_url( $charitable_setup_url ); ?>" target="_blank" class="charitable-button button-link charitable-button-setup"><?php echo esc_html__( 'Set Up Duplicator', 'charitable' ); ?></a>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

			<?php } ?>

			<?php if ( ! charitable_is_installed_mi_pro() ) : ?>

			<div class="charitable-intergration-step charitable-intergration-step-1">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Upgrade to Duplicator Pro', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Unlock addition features by upgrading to Duplicator Pro. Special offer: Get $50 OFF your plan!', 'charitable' ); ?></p>
					<a href="https://www.duplicator.com/lite-upgrade/?utm_source=liteplugin&utm_medium=settings-panel&utm_campaign=ecommerce-tab&utm_content=8.22.0" target="_blank" class="charitable-button button-link"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-2.png' ); ?>" alt="<?php echo esc_html__( 'Step 2', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

			<?php endif; ?>

		</div> <!-- charitable intergration steps -->

	</div> <!-- charitable integrations container -->


</div>

<?php
echo ob_get_clean(); // phpcs:ignore
