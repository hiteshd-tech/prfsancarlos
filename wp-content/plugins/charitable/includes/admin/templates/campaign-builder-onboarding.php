<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Challenge HTML template specific to Campaign Builder.
 *
 * @since 1.8.1.12
 * @package Charitable
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="charitable-onboarding-tooltips">
	<div id="tooltip-content1">
		<h3><?php esc_html_e( 'Name Your Campaign', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'Give your campaign a name so you can easily identify it.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step1-done charitable-onboarding-done-btn"><?php esc_html_e( 'Done', 'charitable' ); ?></button>
	</div>

	<div id="tooltip-content2">
		<h3><?php esc_html_e( 'Select a Template', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'Build your campaign from scratch or use one of our pre-made templates.', 'charitable' ); ?></p>
	</div>

	<div id="tooltip-content3">
		<p><?php esc_html_e( 'You can drag additional fields to add to your page. ', 'charitable' ); ?></p>
		<p><strong><?php esc_html_e( 'Recommended:', 'charitable' ); ?></strong> <?php esc_html_e( 'Usually found on all campaign pages.', 'charitable' ); ?></p>
		<p><strong><?php esc_html_e( 'Standard:', 'charitable' ); ?></strong> <?php esc_html_e( 'Common fields you can use if you need them.', 'charitable' ); ?></p>
		<p><strong><?php esc_html_e( 'Pro:', 'charitable' ); ?></strong> <?php esc_html_e( 'Advanced fields or fields offered by extensions.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step3-done charitable-onboarding-done-btn"><?php esc_html_e( 'Next', 'charitable' ); ?></button>
	</div>

	<div id="tooltip-content4">
		<h3><?php esc_html_e( 'Save Your Campaign', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'Save your campaign progress at any time.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step4-done charitable-onboarding-done-btn"><?php esc_html_e( 'Next', 'charitable' ); ?></button>
	</div>

	<div id="tooltip-content5">
		<h3><?php esc_html_e( 'Publish Your Campaign', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'When you\'re ready, launch your campaign and start raising funds.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step5-done charitable-onboarding-done-btn"><?php esc_html_e( 'Next', 'charitable' ); ?></button>
	</div>

	<div id="tooltip-content6">
		<h3><?php esc_html_e( 'Preview and View', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'See how your campaign will look before saving. You can also check out your campaign once it\'s live.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step6-done charitable-onboarding-done-btn"><?php esc_html_e( 'Next', 'charitable' ); ?></button>
	</div>

	<div id="tooltip-content7">
		<h3><?php esc_html_e( 'Embed', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'Add a campaign to a new or existing page with our embed wizard, or use the shortcode provided.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step7-done charitable-onboarding-done-btn"><?php esc_html_e( 'Next', 'charitable' ); ?></button>
	</div>

	<div id="tooltip-content8">
		<h3><?php esc_html_e( 'Settings', 'charitable' ); ?></h3>
		<p><?php esc_html_e( 'Customize campaign details and preferences. Add donation goals, end dates, suggested amounts, and more.', 'charitable' ); ?></p>
		<button type="button" class="charitable-onboarding-step8-done charitable-onboarding-done-btn"><?php esc_html_e( 'Next', 'charitable' ); ?></button>
	</div>

</div>


<div class="charitable-onboarding-popup-container">
	<div id="charitable-onboarding-welcome-builder-popup" class="charitable-onboarding-popup charitable-onboarding-popup-plain">
		<div class="charitable-onboarding-popup-content">
			<h3><?php esc_html_e( 'Welcome to the Campaign Builder!', 'charitable' ); ?></h3>
			<p><?php esc_html_e( 'This is where you build, manage, and add features to your campaigns. The following steps will walk you through essential areas.', 'charitable' ); ?></p>
			<button type="button" class="charitable-onboarding-popup-btn"><?php esc_html_e( 'Let’s Go!', 'charitable' ); ?></button>
		</div>
	</div>
	<div id="charitable-onboarding-goodbye-builder-popup" class="charitable-onboarding-popup charitable-onboarding-popup-plain">
		<div class="charitable-onboarding-popup-content">
			<h3><?php esc_html_e( 'We hope you enjoyed the tour!', 'charitable' ); ?></h3>
			<p>
				<?php
				// translators: %1$s is the link to the getting started guide, %2$s is the link to the documentation, %3$s is the link to the support page.
				printf(
					wp_kses(
						// translators: %1$s is the link to the getting started guide, %2$s is the link to the documentation, %3$s is the link to the support page.
						__( 'Remember that you can view our <a href="%1$s" target="_blank">getting started guide</a>, read our <a href="%2$s" target="_blank">documentation</a>, or <a href="%3$s" target="_blank">reach out to us</a> for support if you have any questions.', 'charitable' ),
						array(
							'a' => array(
								'href' => array(),
								'target' => array(),
							),
						)
					),
					esc_url( 'https://wpcharitable.com/getting-started' ),
					esc_url( 'https://wpcharitable.com/documentation' ),
					esc_url( 'https://wpcharitable.com/support' )
				);
				?>
			</p>
			<button type="button" class="charitable-onboarding-popup-btn"><?php esc_html_e( 'Get Started!', 'charitable' ); ?></button>
		</div>
	</div>
</div>
