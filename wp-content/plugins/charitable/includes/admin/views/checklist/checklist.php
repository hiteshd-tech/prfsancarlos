<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the setup checklist.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 * @version   1.8.2
 * @version   1.8.8.6
 */

// get first name of logged in user...
$charitable_first_name = (string) get_user_meta( get_current_user_id(), 'first_name', true );
// .. and if that doesn't exist, use the username.
if ( ! empty( $charitable_first_name ) ) {
	$charitable_first_name = ', ' . sanitize_text_field( $charitable_first_name );
}

$charitable_checklist_class      = Charitable_Checklist::get_instance();
$charitable_checklist_state      = $charitable_checklist_class->get_checklist_option( 'status' );
$charitable_checklist_classes    = $charitable_checklist_class->get_steps_css();
$charitable_check_list_completed = $charitable_checklist_class->is_checklist_completed() ? 'charitable-checklist-completed' : '';

$charitable_checklist_urls  = $charitable_checklist_class->get_steps_urls();
$charitable_checklist_stats = $charitable_checklist_class->get_steps_stats();

$charitable_onboarding_campaign_id = get_option( 'charitable_setup_campaign_created', 0 );
$charitable_onboarding_completed   = $charitable_onboarding_campaign_id && get_option( 'charitable_ss_complete', false ) ? true : false;

$charitable_email_signup    = get_option( 'charitable_email_signup', false );
$charitable_opt_in_tracking = charitable_get_usage_tracking_setting();

$charitable_checklist_classes['optin'] = $charitable_opt_in_tracking ? 'charitable-checklist-completed charitable-checklist-checked' : '';

$charitable_tab_leave_open_campaign = $charitable_checklist_class->is_step_completed( 'first-campaign' ) ? false : true;
$charitable_tab_leave_open_campaign = $charitable_onboarding_completed ? true : $charitable_tab_leave_open_campaign;


if ( ! is_array( $charitable_checklist_stats ) || empty( $charitable_checklist_stats ) ) {
	$charitable_checklist_stats = [
		'completed' => 0,
		'total'     => 5,
	];
}

// check if class exists...
if ( class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {
	$charitable_stripe_gateway     = new Charitable_Gateway_Stripe_AM();
	$charitable_redirect_url       = admin_url( 'admin.php?page=charitable-setup-checklist&step=continue' );
	$charitable_stripe_connect_url = $charitable_stripe_gateway->get_stripe_connect_url( $charitable_redirect_url );
	$charitable_stripe_connected   = $charitable_stripe_gateway->maybe_stripe_connected();
	$charitable_gateway_mode       = ( charitable_get_option( 'test_mode' ) ) ? 'test' : 'live';
} else {
	$charitable_stripe_connect_url = '';
	$charitable_stripe_connected   = false;
	$charitable_gateway_mode       = '';
}


ob_start();
?>

<div id="charitable-setup-checklist-wrap" class="wrap">

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-about&view=about' ) ); ?>" class="nav-tab"><?php esc_html_e( 'About Us', 'charitable' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-about&view=getting-started' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Getting Started', 'charitable' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-about&view=lite-vs-pro' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Lite vs Pro', 'charitable' ); ?></a>
	</h2>

	<main id="charitable-setup-checklist" class="charitable-setup-checklist">

		<div class="charitable-setup-checklist-container">

			<header class="charitable-main-header">

				<?php if ( $charitable_checklist_class->is_checklist_completed() ) : ?>

					<h1><?php esc_html_e( 'Great job', 'charitable' ); ?><?php echo esc_html( $charitable_first_name ); ?>!</h1>

					<p>
					<?php

						echo wp_kses_post(
							sprintf(
								/* translators: Suggestion on completion of checklist on checklist page. */
								__( '<strong>%1$s</strong> What\'s Next?', 'charitable' ),
								esc_html__( 'You\'ve completed the checklist!', 'charitable' )
							)
						);

					?>
					</p>

					<ul class="charitable-more-links">
						<li><span class="dashicons dashicons-yes"></span>
						<?php


						printf(
							/* translators: Suggestion on completion of checklist on checklist page. */
							__( 'Don\'t forget to visit the <a href="%1$s" target="_blank">%2$s</a> and <a href="%3$s" target="_blank">%4$s</a> to see your fundraising stats and grow your campaigns.', 'charitable' ), // phpcs:ignore
							esc_url( admin_url( 'admin.php?page=charitable-dashboard' ) ),
							esc_html__( 'dashboard', 'charitable' ),
							esc_url( admin_url( 'admin.php?page=charitable-reports' ) ),
							esc_html__( 'view reports', 'charitable' )
						);
						?>
						</li>
						<li><span class="dashicons dashicons-yes"></span>
						<?php
						$charitable_allowed_links = array( 'a' => array( 'href' => array(), 'target' => array() ) );
						$charitable_checklist_msg = sprintf(
							/* translators: Suggestion on completion of checklist on checklist page. */
							__( '%1$s <a href="https://www.wpcharitable.com/documentation/" target="_blank">%2$s</a> %3$s <a href="https://www.wpcharitable.com/support/" target="_blank">%4$s</a>.', 'charitable' ),
							esc_html__( 'Need help? Visit our', 'charitable' ),
							esc_html__( 'documentation', 'charitable' ),
							esc_html__( 'or', 'charitable' ),
							esc_html__( 'contact support', 'charitable' )
						);
						echo wp_kses( $charitable_checklist_msg, $charitable_allowed_links );
						?>
							</li>
						<li><span class="dashicons dashicons-yes"></span>
						<?php
						// translators: Suggestion on completion of checklist on checklist page.
						printf( __( '<a href="%1$s">%2$s</a> to unlock more features, get priority support, and take your campaigns to the next level!', 'charitable' ), esc_url( charitable_admin_upgrade_link( 'welcome', 'Upgrade Now CTA Section' ) ), esc_html__( 'Upgrade to Pro', 'charitable' ) ); // phpcs:ignore
						?>
						</li>
					</ul>

				<?php else : ?>

					<h1><?php esc_html_e( 'Welcome Aboard', 'charitable' ); ?><?php echo esc_html( $charitable_first_name ); ?>!</h1>

					<?php

					// if the user has completed onboarding, show a different message.
					if ( $charitable_onboarding_completed ) :

						?>

					<p>
						<?php
						// translators: Suggestion on completion of checklist on checklist page.
						printf( __( '<strong>%1$s</strong> View remaining steps below to improve your fundraising efforts.', 'charitable' ), esc_html__( 'Thanks for setting up Charitable! ', 'charitable' ) ); // phpcs:ignore
						?>
					</p>
					<?php else : ?>
					<p>
						<?php
						// translators: Suggestion on completion of checklist on checklist page.
						printf( __( '<strong>%1$s</strong> Follow these steps to start fundraising quickly.', 'charitable' ), esc_html__( 'Thanks for installing Charitable! ', 'charitable' ) ); // phpcs:ignore
						?>
					</p>
						<?php

					endif;

				endif;
				?>

			</header>

			<section class="charitable-step charitable-step-connect-payment
			<?php
			if ( $charitable_checklist_class->is_step_completed( 'connect-gateway' ) ) :
				echo 'charitable-closed';
			endif;
			?>
			" data-section-name="connect-payment">
				<header>
					<h2><span class="charitable-checklist-checkbox <?php echo esc_attr( $charitable_checklist_classes['connect-gateway'] ); ?>"></span><?php esc_html_e( 'Connect To Payment Gateway', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down
					<?php
					if ( $charitable_checklist_class->is_step_completed( 'connect-gateway' ) ) :
						echo 'charitable-angle-right';
					endif;
					?>
					"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-one-col-content charitable-column"
				<?php
				if ( $charitable_checklist_class->is_step_completed( 'connect-gateway' ) ) :
					echo 'style="display:none;"';
				endif;
				?>
				>
					<?php if ( $charitable_stripe_connect_url ) : ?>
					<div class="charitable-sub-container charitable-connect-stripe">
						<div>
							<div class="charitable-gateway-icon"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/onboarding/stripe-checklist.svg" alt="" /></div><div class="charitable-gateway-info-column">
								<h3><?php esc_html_e( 'Stripe', 'charitable' ); ?> <span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i><?php esc_html_e( 'Recommended', 'charitable' ); ?></span></h3>
								<?php if ( ! $charitable_stripe_connected ) : ?>
								<p><?php esc_html_e( 'You can create and connect to your Stripe account in just a few minutes.', 'charitable' ); ?></p>
								<?php else : ?>
								<p><?php printf( '%s <strong>%s %s</strong>.', esc_html__( 'Good news! You have connected to Stripe in', 'charitable' ), esc_html( $charitable_gateway_mode ), esc_html__( ' mode', 'charitable' ) ); ?></p>
								<?php endif; ?>
							</div>
						</div>
						<div>
							<?php if ( ! $charitable_stripe_connected ) : ?>
								<a href="<?php echo esc_url( $charitable_stripe_connect_url ); ?>"><div class="wpcharitable-stripe-connect"><span><?php esc_html_e( 'Connect With', 'charitable' ); ?></span>&nbsp;&nbsp;<svg width="49" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.4718 10.3338c0-3.41791-1.6696-6.11484-4.8607-6.11484-3.2045 0-5.1434 2.69693-5.1434 6.08814 0 4.0187 2.289 6.048 5.5743 6.048 1.6023 0 2.8141-.3604 3.7296-.8678v-2.6702c-.9155.4539-1.9658.7343-3.2987.7343-1.3061 0-2.464-.4539-2.6121-2.0294h6.5841c0-.1735.0269-.8678.0269-1.1882Zm-6.6514-1.26838c0-1.50868.929-2.13618 1.7773-2.13618.8213 0 1.6965.6275 1.6965 2.13618h-3.4738Zm-8.5499-4.84646c-1.3195 0-2.1678.61415-2.639 1.04139l-.1751-.82777h-2.9621V20l3.3661-.7076.0134-3.7784c.4847.3471 1.1984.8411 2.3832.8411 2.4102 0 4.6048-1.9225 4.6048-6.1548-.0134-3.87186-2.235-5.98134-4.5913-5.98134Zm-.8079 9.19894c-.7944 0-1.2656-.2804-1.5888-.6275l-.0134-4.95328c.35-.38719.8348-.65421 1.6022-.65421 1.2253 0 2.0735 1.36182 2.0735 3.11079 0 1.7891-.8347 3.1242-2.0735 3.1242Zm-9.6001-9.98666 3.3796-.72096V0l-3.3796.70761v2.72363Zm0 1.01469h3.3796V16.1282h-3.3796V4.44593Zm-3.6219.98798-.2154-.98798h-2.9083V16.1282h3.3661V8.21095c.7944-1.02804 2.1408-.84112 2.5582-.69426V4.44593c-.4309-.16022-2.0062-.45394-2.8006.98798Zm-6.7322-3.88518-3.2853.69426-.01346 10.69421c0 1.976 1.49456 3.4313 3.48726 3.4313 1.1041 0 1.912-.2003 2.3563-.4406v-2.7103c-.4309.1736-2.5583.7877-2.5583-1.1882V7.28972h2.5583V4.44593h-2.5583l.0135-2.8972ZM3.40649 7.83712c0-.5207.43086-.72096 1.14447-.72096 1.0233 0 2.31588.30707 3.33917.85447V4.83311c-1.11755-.44059-2.22162-.61415-3.33917-.61415C1.81769 4.21896 0 5.63418 0 7.99733c0 3.68487 5.11647 3.09747 5.11647 4.68627 0 .6141-.53858.8144-1.29258.8144-1.11755 0-2.54477-.4539-3.675782-1.0681v3.1776c1.252192.534 2.517842.761 3.675782.761 2.80059 0 4.72599-1.3752 4.72599-3.765-.01346-3.97867-5.14339-3.27106-5.14339-4.76638Z" fill="#fff"/></svg></div></a>
							<?php else : ?>
								<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' ) ); ?>" class="charitable-button charitable-button-primary"><?php esc_html_e( 'Connected', 'charitable' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
					<div class="charitable-sub-container charitable-step-one-col-content charitable-column">
						<?php if ( ! $charitable_stripe_connected ) : ?>
						<div class="charitable-sub-container-row">
							<?php // translators: Suggestion on completion of checklist on checklist page. ?>
							<p><?php echo wp_kses_post( sprintf( __( '<strong>Stripe not available in your country?</strong> Charitable works with payment gateways like PayPal, Authorize.net, Braintree, Payrexx, PayUMoney, GoCardless, and others. <a target="_blank" href="%s">View additional payment options</a> available with PRO extensions.', 'charitable' ), esc_url( admin_url( 'admin.php?page=charitable-addons' ) ) ) ); ?></p>
						</div>
						<?php else : ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
			</section>

			<section class="charitable-step charitable-step-opt-in
			<?php
			if ( $charitable_opt_in_tracking ) :
				echo 'charitable-closed';
			endif;
			?>
			" data-section-name="opt-in">
				<header>
					<h2><span class="charitable-checklist-checkbox <?php echo esc_attr( $charitable_checklist_classes['optin'] ); ?>"></span><?php esc_html_e( 'Never Miss An Important Update', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down
					<?php
					if ( $charitable_opt_in_tracking ) :
						echo 'charitable-angle-right';
					endif;
					?>
					"></i></a>
				</header>
				<div class="charitable-toggle-container"
				<?php
				if ( $charitable_opt_in_tracking ) :
					echo 'style="display:none;"';
					endif;
				?>
					>
					<div class="charitable-step-content charitable-step-two-col-content charitable-equal-flex">
						<div class="charitable-bonus-step-container">
							<div>
								<p>
								<span class="chartiable-opt-in-tracking
								<?php
								if ( $charitable_opt_in_tracking ) :
									echo 'charitable-hidden';
endif;
								?>
								">
								<?php

								$charitable_url = preg_replace( '(^https?://)', '', site_url() );

								printf(
									// Translators: %s: domain.
									esc_html__( 'Opt in to get email notifications for security & feature updates, educational content, and occasional offers, and to share some basic WordPress environment info. This will help us make the plugin more compatible with %s to ensure you get donations and run campaigns smoothly.', 'charitable' ),
									'<strong>' . esc_html( $charitable_url ) . '</strong>'
								);


								?>
								</span>
								<span class="chartiable-opt-in-tracking-1
								<?php
								if ( ! $charitable_opt_in_tracking ) :
									echo 'charitable-hidden';
endif;
								?>
								">
								<?php

								printf(
									// Translators: %s: domain.
									esc_html__( 'Being opt in you get email notifications for security & feature updates, educational content, and occasional offers. You also share some basic WordPress environment info that helps us  make the plugin more compatible with %s to ensure you get donations and run campaigns smoothly.', 'charitable' ),
									'<strong>' . esc_html( $charitable_url ) . '</strong>'
								);

								?>
								</span>
								</p>

								<p class="charitable-step-opt-in-allow-title"><a href="#" class="charitable-toggle-optin-allow"><?php esc_html_e( 'This will allow Charitable to:', 'charitable' ); ?> <i class="fa fa-angle-down charitable-angle-down charitable-angle-right"></i></a></p>
								<div class="charitable-checklist-allow">
									<ul>
										<li>
											<div class="charitable-checklist-allow-icon"><span class="dashicons dashicons-admin-users"></span></div>
											<div class="charitable-checklist-allow-text">
												<h6><?php esc_html_e( 'View Basic Info', 'charitable' ); ?></h6>
												<p><?php esc_html_e( 'Your WordPress\'s version, mySQL & PHP versions, basic server information.', 'charitable' ); ?></p>
											</div>
										</li>
										<li>
											<div class="charitable-checklist-allow-icon"><span class="dashicons dashicons-admin-plugins"></span></div>
											<div class="charitable-checklist-allow-text">
												<h6><?php esc_html_e( 'View Charitable Settings Info', 'charitable' ); ?></h6>
												<p><?php esc_html_e( 'Charitable version, license key email and url to troubleshoot authentication.', 'charitable' ); ?></p>
											</div>
										</li>
										<li>
											<div class="charitable-checklist-allow-icon"><span class="dashicons dashicons-art"></span></div>
											<div class="charitable-checklist-allow-text">
												<h6><?php esc_html_e( 'View Plugins &amp; Themes List', 'charitable' ); ?></h6>
												<p><?php esc_html_e( 'Names, slugs, versions, etc. to know what to support and test against.', 'charitable' ); ?></p>
											</div>
										</li>
										<li>
											<?php
											// translators: %s: link to documentation.
											printf( esc_html__( 'Read %s for full details.', 'charitable' ), '<a style="margin-left:4px; margin-right: 4px;" href="' . esc_url( 'https://www.wpcharitable.com/documentation/usage-tracking/' ) . '" target="_blank">' . esc_html__( 'our documentation', 'charitable' ) . '</a>' );
											?>
										</li>
									</ul>
								</div>
							</div>
							<div class="charitable-button-column">
								<?php if ( ! $charitable_opt_in_tracking ) : ?>
									<a data-optin-tracking-status="not-joined" href="#" class="charitable-button charitable-button-primary alt"><?php esc_html_e( 'Allow &amp; Continue', 'charitable' ); ?> <i class="fa fa-arrow-right"></i></a>
								<?php else : ?>
									<a data-optin-tracking-status="joined" href="#" class="charitable-button charitable-button-primary"><?php esc_html_e( 'Opt Out', 'charitable' ); ?> <i class="fa fa-arrow-right"></i></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

			</section>

			<section class="charitable-step charitable-step-plugin-config
			<?php
			if ( $charitable_checklist_class->is_step_completed( 'general-settings' ) ) :
				echo 'charitable-closed';
			endif;
			?>
			" data-section-name="plugin-config">
				<header> <?php /* charitable-checklist-checked if done */ ?>
					<h2><span class="charitable-checklist-checkbox <?php echo esc_attr( $charitable_checklist_classes['general-settings'] ); ?>"></span><?php esc_html_e( 'Confirm General Settings', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down
					<?php
					if ( $charitable_checklist_class->is_step_completed( 'general-settings' ) ) :
						echo 'charitable-angle-right';
					endif;
					?>
					"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-two-col-content charitable-equal-flex"
				<?php
				if ( $charitable_checklist_class->is_step_completed( 'general-settings' ) ) :
					echo 'style="display:none;"';
endif;
				?>
				>
					<div class="charitable-reduced-width">
						<p>
						<?php printf( esc_html__( 'Take a few seconds to confirm your currency, location, and donation form settings.', 'charitable' ) ); ?>
						</p>
					</div>
					<?php

					$charitable_button_css = $charitable_checklist_class->is_step_completed( 'general-settings' ) ? 'charitable-button-primary' : 'charitable-button-primary alt';

					?>
					<a href="<?php echo esc_url( $charitable_checklist_urls['general-settings'] ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'Confirm General Settings', 'charitable' ); ?></a>
				</div>
			</section>

			<section class="charitable-step charitable-step-email-settings
			<?php
			if ( $charitable_checklist_class->is_step_completed( 'email-settings' ) ) :
				echo 'charitable-closed';
			endif;
			?>
			" data-section-name="email-settings">
				<header>
					<h2><span class="charitable-checklist-checkbox <?php echo esc_attr( $charitable_checklist_classes['email-settings'] ); ?>"></span><?php esc_html_e( 'Confirm Email Settings', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down
					<?php
					if ( $charitable_checklist_class->is_step_completed( 'email-settings' ) ) :
						echo 'charitable-angle-right';
					endif;
					?>
					"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-two-col-content"
				<?php
				if ( $charitable_checklist_class->is_step_completed( 'email-settings' ) ) :
					echo 'style="display:none;"';
endif;
				?>
				>
					<div class="charitable-reduced-width">
						<p><?php esc_html_e( 'Quickly set up automated emails to send receipts to donors and admin notifications to you when a donation is made.', 'charitable' ); ?></p>
					</div>
					<?php

					$charitable_button_css = $charitable_checklist_class->is_step_completed( 'email-settings' ) ? 'charitable-button-primary' : 'charitable-button-primary alt';

					?>
					<a href="<?php echo esc_url( $charitable_checklist_urls['email-settings'] ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'Confirm Email Settings', 'charitable' ); ?></a>
				</div>
			</section>

			<section class="charitable-step charitable-step-create-first-campaign
			<?php
			if ( ! $charitable_tab_leave_open_campaign ) :
				echo 'charitable-closed';
			endif;
			?>
			" data-section-name="first-campaign">
				<header>
					<h2><span class="charitable-checklist-checkbox <?php echo esc_attr( $charitable_checklist_classes['first-campaign'] ); ?>"></span><?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down
					<?php
					if ( ! $charitable_tab_leave_open_campaign ) :
						echo 'charitable-angle-right';
					endif;
					?>
					"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-two-col-content"
				<?php
				if ( ! $charitable_tab_leave_open_campaign ) :
					echo 'style="display:none;"';
				endif;
				?>
				>
					<div>
						<?php

						if ( $charitable_onboarding_campaign_id ) :
							?>

							<p><?php esc_html_e( 'You have already created your first campaign! You can edit, update, or publish it with new content.', 'charitable' ); ?></p>

						<?php else : ?>
							<p><?php esc_html_e( 'Build and launch your fundraiser campaign to start collecting donations right away.', 'charitable' ); ?></p>
						<?php endif; ?>
					</div>
					<?php

					$charitable_button_css = $charitable_checklist_class->is_step_completed( 'first-campaign' ) ? 'charitable-button-primary' : 'charitable-button-primary alt';

					?>
						<?php

						if ( ! $charitable_onboarding_campaign_id ) :
							?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'Create Campaign', 'charitable' ); ?></a>
					<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . intval( $charitable_onboarding_campaign_id ) ) ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'Edit Campaign', 'charitable' ); ?></a>
					<?php endif; ?>
				</div>
			</section>

			<?php

			$charitable_button_css = $charitable_checklist_class->is_step_completed( 'next-level' ) ? 'charitable-button-primary' : 'charitable-button-primary alt';

			?>

			<section class="charitable-step charitable-step-fundraising-next-level
			<?php
			if ( $charitable_checklist_class->is_step_completed( 'next-level' ) ) :
				echo 'charitable-closed';
			endif;
			?>
			" data-section-name="fundraising-next-level">
				<header>
					<h2><span class="charitable-checklist-checkbox <?php echo esc_attr( $charitable_checklist_classes['next-level'] ); ?>"></span><?php esc_html_e( 'Take Fundraising To The Next Level', 'charitable' ); ?>
					<?php
					if ( ! $charitable_checklist_class->is_step_completed( 'next-level' ) ) :
						?>
						<small><?php esc_html_e( 'Explore the feature you find most intriguing to complete this step!', 'charitable' ); ?></small><?php endif; ?></h2>
					<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down
					<?php
					if ( $charitable_checklist_class->is_step_completed( 'next-level' ) ) :
						echo 'charitable-angle-right';
					endif;
					?>
					"></i></a>
				</header>
				<div class="charitable-toggle-container charitable-step-content charitable-step-one-col-content charitable-column"
				<?php
				if ( $charitable_checklist_class->is_step_completed( 'next-level' ) ) :
					echo 'style="display:none;"';
endif;
				?>
				>
					<div class="charitable-sub-container">
						<div>
							<div class="charitable-next-level-icon"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/onboarding/checklist-fundraiser-1.svg" alt="" /></div>
								<div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Ambassadors', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Transform your website into a peer-to-peer fundraising platform.', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a target="_blank" href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/extensions/charitable-ambassadors/', 'CheckList', 'More Information' ) ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'More Information', 'charitable' ); ?></a>
						</div>
					</div>
					<div class="charitable-sub-container">
						<div>
							<div class="charitable-next-level-icon"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/onboarding/checklist-fundraiser-2.svg" alt="" /></div>
								<div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Recurring Donations', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Grow your organization\'s revenue with recurring donations.', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a target="_blank" href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/extensions/charitable-recurring-donations/', 'CheckList', 'More Information' ) ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'More Information', 'charitable' ); ?></a>
						</div>
					</div>
					<div class="charitable-sub-container">
						<div>
							<div class="charitable-next-level-icon"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/onboarding/checklist-fundraiser-3.svg" alt="" /></div>
								<div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Fee Relief', 'charitable' ); ?></h3>
								<p><?php esc_html_e( 'Give your donors the option to cover the processing fees on their donations.', 'charitable' ); ?></p>
							</div>
						</div>
						<div>
							<a target="_blank" href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/extensions/charitable-fee-relief/', 'CheckList', 'More Information' ) ); ?>" class="charitable-button <?php echo esc_attr( $charitable_button_css ); ?>"><?php esc_html_e( 'More Information', 'charitable' ); ?></a>
						</div>
					</div>
				</div>
			</section>

			<?php if ( ! $charitable_checklist_class->is_checklist_completed() ) : ?>
			<section class="charitable-step-footer">
				<div class="charitable-step-footer-interior">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist&completed=true' ) ); ?>" class="charitable-text-link">
						<span class="underline"><?php esc_html_e( 'Complete Checklist', 'charitable' ); ?></span>
					</a>
				</div>
			</section>
			<?php endif; ?>

			<?php if ( defined( 'CHARITABLE_SHOW_LAUNCH_WIZARD' ) && CHARITABLE_SHOW_LAUNCH_WIZARD ) { ?>
				<section class="charitable-step-footer">
					<div class="charitable-step-footer-interior">
						<a href="<?php echo esc_url( charitable_get_onboarding_url() ); ?>" class="charitable-text-link">
							<span class="underline"><?php esc_html_e( 'Launch Onboarding Wizard', 'charitable' ); ?></span>
						</a>
					</div>
				</section>
			<?php } ?>

			<?php if ( ! charitable_is_pro() ) : ?>

			<section class="charitable-upgrade-cta upgrade">

				<div class="block">

					<div class="left">
						<h2><?php esc_html_e( 'Upgrade to PRO', 'charitable' ); ?></h2>
						<ul>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'No Platform Fees', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Recurring Donations', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Advanced Reporting', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Payment Options', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Fee Relief', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Gift Aid', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Campaign Updates', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Geolocation', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'PDF Receipts', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Annual Receipts', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Videos', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Marketing Integrations', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Peer-to-Peer Fundraising', 'charitable' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Spam Blocking', 'charitable' ); ?></li>
						</ul>
					</div>

					<div class="right">
						<h2><span><?php esc_html_e( 'PRO', 'charitable' ); ?></span></h2>
						<div class="price">
							<span class="amount"><?php esc_html_e( '199', 'charitable' ); ?></span><br>
							<span class="term"><?php esc_html_e( 'per year', 'charitable' ); ?></span>
						</div>
						<a href="<?php echo esc_url( charitable_admin_upgrade_link( 'welcome', 'Upgrade Now CTA Section' ) ); ?>" rel="noopener noreferrer" target="_blank"
							class="charitable-button-link charitable-button-orange charitable-upgrade-modal">
							<?php esc_html_e( 'Upgrade Now', 'charitable' ); ?>
						</a>
					</div>

				</div>

			</section>

			<?php endif; ?>


		</div>

	</main>



</div>
<?php
echo ob_get_clean(); // phpcs:ignore
