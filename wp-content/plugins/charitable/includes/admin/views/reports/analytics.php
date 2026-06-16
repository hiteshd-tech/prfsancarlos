<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the MonsterInsights integration step for the Analytics report.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @package Charitable/Admin Views/Reports
 * @version 1.8.8.6
 */

?>
<div class="charitable-analytics-container intergration-monsterinsights">

	<img class="charitable-monsterinsights-logo" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/logos.png' ); ?>" alt="<?php echo esc_html__( 'Integrate Monster Insights Into Charitable For Analytics', 'charitable' ); ?>">

	<h1><?php echo esc_html__( 'Increase Donations with MonsterInsights', 'charitable' ); ?></h1>

	<h2><?php echo esc_html__( 'MonsterInsights connects Google Analytics to your website so that you see what campaigns are working best with no coding needed.', 'charitable' ); ?></h2>

	<div class="bullets-thumbnail">

		<div>
			<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/monsterinsights.jpg' ); ?>" alt="<?php echo esc_html__( 'Integrate MonsterInsights Into Charitable For Analytics', 'charitable' ); ?>">
		</div>
		<div>
			<div class="vertical-wrapper">
				<ul>
					<li><?php echo esc_html__( 'See what types of website traffic are delivering results and donations', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Easily track donation form performance to maximize results', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'View all key traffic and donation stats and reporting inside WordPress Admin area', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Automatic one-click setup with WP Charitable', 'charitable' ); ?></li>
				</ul>
			</div>
		</div>

	</div>

	<div class="charitable-intergration-steps">

		<?php

		$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();

		// determine if the MonsterInsights plugin is installed and activated.
		$charitable_is_monsterinsights_installed = $charitable_plugins_third_party->is_plugin_installed( 'monsterinsights' );
		$charitable_is_monsterinsights_active    = $charitable_plugins_third_party->is_plugin_activated( 'monsterinsights' );

		if ( ! $charitable_is_monsterinsights_installed ) {

			$charitable_install_button_html = $charitable_plugins_third_party->get_install_button_html( 'monsterinsights', esc_html__( 'Install MonsterInsights', 'charitable' ) );

			?>

		<div class="charitable-intergration-step charitable-intergration-step-1"  data-status="install">
			<div class="instructions">
				<h3><?php echo esc_html__( 'Install and Activate MonsterInsights', 'charitable' ); ?></h3>
				<p><?php echo esc_html__( 'Install the MonsterInsights plugin by clicking this button', 'charitable' ); ?></p>
				<?php echo $charitable_install_button_html; // phpcs:ignore ?>
			</div>
			<div class="step">
				<div class="vertical-wrapper">
					<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
				</div>
			</div>
		</div>

			<?php
		} elseif ( ! $charitable_is_monsterinsights_active ) {

			$charitable_basename = $charitable_plugins_third_party->get_basename_from_slug( 'monsterinsights' );

			if ( $charitable_basename ) :

				$charitable_activate_button_html = $charitable_plugins_third_party->get_activation_button_html( 'monsterinsights', esc_html__( 'Activate MonsterInsights', 'charitable' ) );

				?>

		<div class="charitable-intergration-step charitable-intergration-step-1" data-status="activate">
			<div class="instructions">
				<h3><?php echo esc_html__( 'Activate MonsterInsights', 'charitable' ); ?></h3>
				<p><?php echo esc_html__( 'Activate MonsterInsights plugin by clicking this button:', 'charitable' ); ?></p>
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

			$charitable_setup_url = $charitable_plugins_third_party->get_setup_screen_for_plugin( 'monsterinsights' );

			?>

		<div class="charitable-intergration-step charitable-intergration-step-1" data-status="setup">
			<div class="instructions">
				<h3><?php echo esc_html__( 'Setup MonsterInsights', 'charitable' ); ?></h3>
				<p><?php echo esc_html__( 'Setup MonsterInsights plugin by clicking this button:', 'charitable' ); ?></p>
				<a href="<?php echo esc_url( $charitable_setup_url ); ?>" target="_blank" class="charitable-button button-link charitable-button-setup"><?php echo esc_html__( 'Set Up MonsterInsights', 'charitable' ); ?></a>
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
				<h3><?php echo esc_html__( 'Upgrade to MonsterInsights Pro', 'charitable' ); ?></h3>
				<p><?php echo esc_html__( 'Unlock all tracking, reports, and one-click Charitable integration by upgrading to MonsterInsights Pro. Special offer: Save Up To 50%!', 'charitable' ); ?></p>
				<a href="https://www.monsterinsights.com/lite/?utm_source=liteplugin&utm_medium=settings-panel&utm_campaign=ecommerce-tab&utm_content=8.22.0" target="_blank" class="charitable-button button-link"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
			</div>
			<div class="step">
				<div class="vertical-wrapper">
					<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-2.png' ); ?>" alt="<?php echo esc_html__( 'Step 2', 'charitable' ); ?>" /></div>
				</div>
			</div>
		</div>

		<?php endif; ?>

	</div> <!-- charitable intergration steps -->

</div> <!-- charitable analytics container -->
