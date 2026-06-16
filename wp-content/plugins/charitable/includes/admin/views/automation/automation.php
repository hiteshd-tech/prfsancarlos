<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main automation page wrapper.
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

<div id="charitable-automation" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="charitable-intergrations-container intergration-automation">

		<img class="charitable-automation-logo" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/integrations/automator.png'; ?>" alt="<?php echo esc_html__( 'Integrate Automation Tools Into Charitable For Streamlined Fundraising', 'charitable' ); ?>">

		<h1><?php echo esc_html__( 'Automate Your Fundraising Campaigns', 'charitable' ); ?></h1>

		<h2><?php echo esc_html__( 'Put your campaigns on autopilot with Uncanny Automator. Automatically run actions in hundreds of supported plugins and apps when visitors make donations.', 'charitable' ); ?></h2>

		<div class="bullets-thumbnail">

			<div>
				<div class="charitable-screenshot">
					<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/automation-screenshot-thumbnail.png' ); ?>" alt="<?php echo esc_html__( 'Integrate Automation Tools Into Charitable For Streamlined Fundraising', 'charitable' ); ?>">
					<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/automation-screenshot-full.png' ); ?>" class="hover" data-lity=""></a>
				</div>
			</div>
			<div>
				<div class="vertical-wrapper">
					<ul>
						<li><?php echo esc_html__( 'Automatically push donors and donations to Google Sheets, Airtable, Trello and more.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Automatically publish campaign updates on social media.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Automatically schedule and send follow-up messages on email, WhatsApp, Telegram, SMS and more.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Build automations in minutes with a powerful, no-code recipe builder.', 'charitable' ); ?></li>
					</ul>
				</div>
			</div>

		</div>

		<div class="charitable-intergration-steps">

			<?php

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party(); // phpcs:ignore

			// determine if the Automation plugin is installed and activated.
			$charitable_is_automation_installed = $charitable_plugins_third_party->is_plugin_installed( 'automation' );
			$charitable_is_automation_active    = $charitable_plugins_third_party->is_plugin_activated( 'automation' );

			if ( ! $charitable_is_automation_installed ) {

				$charitable_install_button_html = $charitable_plugins_third_party->get_install_button_html( 'uncanny-automator', 'Install Uncanny Automator' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1"  data-status="install">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Install and Activate Uncanny Automator', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Install the Uncanny Automator plugin by clicking this button', 'charitable' ); ?></p>
					<?php echo $charitable_install_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

				<?php
			} elseif ( ! $charitable_is_automation_active ) {

				$charitable_basename = $charitable_plugins_third_party->get_basename_from_slug( 'uncanny-automator' );

				if ( $charitable_basename ) :

					$charitable_activate_button_html = $charitable_plugins_third_party->get_activation_button_html( 'uncanny-automator', 'Activate Uncanny Automator' );

					?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="activate">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Activate Uncanny Automator', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Activate Automation by clicking this button:', 'charitable' ); ?></p>
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

				$charitable_setup_url = $charitable_plugins_third_party->get_setup_screen_for_plugin( 'uncanny-automator' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="setup">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Setup Uncanny Automator', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Setup Automation plugin by clicking this button:', 'charitable' ); ?></p>
					<a href="<?php echo esc_url( $charitable_setup_url ); ?>" target="_blank" class="charitable-button button-link charitable-button-setup"><?php echo esc_html__( 'Set Up Uncanny Automator', 'charitable' ); ?></a>
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
					<h3><?php echo esc_html__( 'Upgrade to Uncanny Automator Pro', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Unlock thousands of additional triggers, tokens and actions by upgrading to Uncanny Automator Pro. Special introductory offer: Save at least 25%!', 'charitable' ); ?></p>
					<a href="https://automatorplugin.com/pricing" target="_blank" class="charitable-button button-link"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
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
