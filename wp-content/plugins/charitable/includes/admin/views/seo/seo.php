<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main SEO page wrapper.
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

<div id="charitable-seo" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="charitable-intergrations-container intergration-seo">

		<img class="charitable-seo-logo" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ) . 'images/integrations/seo.png'; ?>" alt="<?php echo esc_html__( 'Integrate SEO Tools Into Charitable For Better Campaign Visibility', 'charitable' ); ?>">

		<h1><?php echo esc_html__( 'Rank Higher and Drive More Traffic to Your Site', 'charitable' ); ?></h1>

		<h2><?php echo esc_html__( 'The #1 WordPress SEO plugin trusted by over 3 million users. AIOSEO provides all the tools you need to improve your on-page SEO, track keywords, and grow your online visibility. Built for beginners and pros alike.', 'charitable' ); ?></h2>

		<div class="bullets-thumbnail">

			<div>
				<div class="charitable-screenshot">
					<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/aioseo-screenshot-thumbnail.png' ); ?>" alt="<?php echo esc_html__( 'Integrate SEO Tools Into Charitable For Better Campaign Visibility', 'charitable' ); ?>">
					<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/integrations/aioseo-screenshot-full.png' ); ?>" class="hover" data-lity=""></a>
				</div>
			</div>
			<div>
				<div class="vertical-wrapper">
					<ul>
						<li><?php echo esc_html__( 'Real-time SEO feedback as you write.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'AI-powered title and meta description generation.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Track keyword rankings and site performance.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'SEO tools for local businesses and WooCommerce.', 'charitable' ); ?></li>
					</ul>
				</div>
			</div>

		</div>

		<div class="charitable-intergration-steps">

			<?php

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party(); // phpcs:ignore

			// determine if the SEO plugin is installed and activated.
			$charitable_is_seo_installed = $charitable_plugins_third_party->is_plugin_installed( 'aioseo' );
			$charitable_is_seo_active    = $charitable_plugins_third_party->is_plugin_activated( 'aioseo' );

			if ( ! $charitable_is_seo_installed ) {

				$charitable_install_button_html = $charitable_plugins_third_party->get_install_button_html( 'aioseo', 'Install AIOSEO' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1"  data-status="install">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Install and Activate AIOSEO', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Install AIOSEO by clicking this button', 'charitable' ); ?></p>
					<?php echo $charitable_install_button_html; // phpcs:ignore ?>
				</div>
				<div class="step">
					<div class="vertical-wrapper">
						<div class="step-image"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/reports/analytics/step-1.png' ); ?>" alt="<?php echo esc_html__( 'Step 1', 'charitable' ); ?>" /></div>
					</div>
				</div>
			</div>

				<?php
			} elseif ( ! $charitable_is_seo_active ) {

				$charitable_basename = $charitable_plugins_third_party->get_basename_from_slug( 'aioseo' );

				if ( $charitable_basename ) :

					$charitable_activate_button_html = $charitable_plugins_third_party->get_activation_button_html( 'aioseo', 'Activate AIOSEO' );

					?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="activate">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Activate AIOSEO', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Activate AIOSEO by clicking this button:', 'charitable' ); ?></p>
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

				$charitable_setup_url = $charitable_plugins_third_party->get_setup_screen_for_plugin( 'aioseo' );

				?>

			<div class="charitable-intergration-step charitable-intergration-step-1" data-status="setup">
				<div class="instructions">
					<h3><?php echo esc_html__( 'Setup AIOSEO', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Setup AIOSEO by clicking this button:', 'charitable' ); ?></p>
					<a href="<?php echo esc_url( $charitable_setup_url ); ?>" target="_blank" class="charitable-button button-link charitable-button-setup"><?php echo esc_html__( 'Set Up AIOSEO', 'charitable' ); ?></a>
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
					<h3><?php echo esc_html__( 'Upgrade to AIOSEO Pro', 'charitable' ); ?></h3>
					<p><?php echo esc_html__( 'Don\'t leave traffic on the table. Unlock additional features with AIOSEO Pro that help you outrank the competition and grow your business.', 'charitable' ); ?></p>
					<a href="https://aioseo.com/pricing/" target="_blank" class="charitable-button button-link"><?php echo esc_html__( 'Upgrade To Pro', 'charitable' ); ?></a>
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
