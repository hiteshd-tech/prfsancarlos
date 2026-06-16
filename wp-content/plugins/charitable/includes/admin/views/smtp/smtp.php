<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main smtp page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.8
 * @version   1.8.1.8
 * @version   1.8.8.6
 */

ob_start();
?>

<div id="charitable-smtp" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="charitable-intergrations-container intergration-smtp">

		<img class="charitable-smtp-logo" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/integrations/smtp.png'; ?>" alt="<?php echo esc_html__( 'Integrate WP Mail SMTP Into Charitable For Improved Email Reliability', 'charitable' ); ?>">

		<h1><?php echo esc_html__( 'Making Email Deliverability Easy for WordPress', 'charitable' ); ?></h1>

		<h2><?php echo esc_html__( 'WP Mail SMTP fixes deliverability problems with your WordPress emails and form notifications. It\'s built by the same folks behind Charitable.', 'charitable' ); ?></h2>

		<div class="bullets-thumbnail">

			<div>
				<div class="charitable-screenshot">
					<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/smtp-screenshot-thumnbail.png' ); ?>" alt="<?php echo esc_html__( 'Integrate WP Mail SMTP Into Charitable For Improved Email Reliability', 'charitable' ); ?>">
					<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/smtp-screenshot-full.png' ); ?>" class="hover" data-lity=""></a>
				</div>
			</div>
			<div>
				<div class="vertical-wrapper">
					<ul>
						<li><?php echo esc_html__( 'Improves email deliverability in WordPress.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Used by 2+ million websites.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Free mailers: SendLayer, SMTP.com, Brevo, Google Workspace / Gmail, Mailgun, Postmark, SendGrid.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Pro mailers: Amazon SES, Microsoft 365 / Outlook.com, Zoho Mail.', 'charitable' ); ?></li>
					</ul>
				</div>
			</div>

		</div>

		<div class="charitable-intergration-steps">

			<?php

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party(); // phpcs:ignore

			// determine if the SMTP plugin is installed and activated.
			$charitable_is_smtp_installed = $charitable_plugins_third_party->is_plugin_installed( 'wp-mail-smtp' );
			$charitable_is_smtp_active    = $charitable_plugins_third_party->is_plugin_activated( 'wp-mail-smtp' );

			if ( ! $charitable_is_smtp_installed ) {

				$charitable_install_button_html = $charitable_plugins_third_party->get_install_button_html( 'wp-mail-smtp', 'Install WP Mail SMTP' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1"  data-status="install">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Install and Activate WP Mail SMTP', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Install the WP Mail SMTP plugin by clicking this button', 'charitable' ); ?></p>
					<?php echo $charitable_install_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

				<?php
			} elseif ( ! $charitable_is_smtp_active ) {

				$charitable_basename = $charitable_plugins_third_party->get_basename_from_slug( 'wp-mail-smtp' );

				if ( $charitable_basename ) :

					$charitable_activate_button_html = $charitable_plugins_third_party->get_activation_button_html( 'wp-mail-smtp', 'Activate WP Mail SMTP' );

					?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="activate">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Activate WP Mail SMTP', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Activate WP Mail SMTP by clicking this button:', 'charitable' ); ?></p>
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

				$charitable_setup_url = $charitable_plugins_third_party->get_setup_screen_for_plugin( 'wp-mail-smtp' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="setup">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Setup WP Mail SMTP', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Setup WP Mail SMTP plugin by clicking this button:', 'charitable' ); ?></p>
					<a href="<?php echo esc_url( $charitable_setup_url ); ?>" target="_blank" class="charitable-button button-link charitable-button-setup"><?php echo esc_html__( 'Set Up WP Mail SMTP', 'charitable' ); ?></a>
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
					<h3><?php echo esc_html__( 'Upgrade to WP Mail SMTP Pro', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Unlock addition features by upgrading to WP Mail SMTP Pro. Special offer: Get $50 OFF your plan!', 'charitable' ); ?></p>
					<a href="https://www.wpmailsmtp.com/lite-upgrade/?utm_source=liteplugin&utm_medium=settings-panel&utm_campaign=ecommerce-tab&utm_content=8.22.0" target="_blank" class="charitable-button button-link"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
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
