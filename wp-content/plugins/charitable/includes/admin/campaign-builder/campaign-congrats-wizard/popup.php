<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campaign Congrats Wizard.
 * Congrats popup HTML template.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 * @version   1.8.8.6
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}
$charitable_pages_exists = ! empty( $args['dropdown_pages'] ) ? 1 : 0;
$charitable_campaign_id  = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$charitable_campaign_url = get_permalink( $charitable_campaign_id );
$charitable_show_url      = apply_filters( 'charitable_show_campaign_url_in_popup', false );

?>

<div id="charitable-admin-campaign-congrats-wizard-container" class="charitable-admin-popup-container charitable-admin-popup-congrats-wizard">

	<div id="charitable-admin-campaign-congrats-wizard" class="charitable-admin-popup" data-pages-exists="<?php echo esc_attr( $charitable_pages_exists ); ?>">

		<div class="charitable-admin-popup-content">
			<p class="icon">🎉</p>
			<h3>
				<?php esc_html_e( 'Congratulations!', 'charitable' ); ?>
			</h3>
			<h4>
				<?php esc_html_e( 'Your campaign has been published!', 'charitable' ); ?>
			</h4>


			<div class="charitable-admin-popup-section charitable-admin-popup-section-view">
				<a href="<?php echo esc_url( $charitable_campaign_url ); ?>" target="_blank" class="charitable-admin-congrats-popup-btn"><?php esc_html_e( 'View Campaign', 'charitable' ); ?> <img class="popup_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/view_live.svg' ); ?>" /></a>

				<?php if ( $charitable_show_url ) : ?>

				<p><a class="charitable-admin-campaign-link show-url" href="<?php echo esc_url( $charitable_campaign_url ); ?>" target="_blank"><?php echo esc_url( $charitable_campaign_url ); ?></a></p>

				<?php endif; ?>

			</div>

			<div class="charitable-admin-popup-embed">
				<p class="secondary">
					<?php
					printf(
						wp_kses( /* translators: %1$s - css. */
							__( 'Want to embed this campaign? Check our <a href="#" class="%1$s">Embed Wizard</a>.', 'charitable' ),
							[
								'a' => [
									'href'  => [],
									'class' => [],
								],
							]
						),
						'charitable-admin-launch-embed-wizard'
					);
					?>
				</p>

			</div>

			<?php /* if ( ! empty( $args['user_can_edit_pages'] ) ) : ?>

				<div class="charitable-admin-popup-whats-next">

					<div class="charitable-admin-popup-whats-next-title">
						<p><?php esc_html_e( 'What’s Next', 'charitable' ); ?></p>
					</div>

					<div class="charitable-admin-popup-whats-next-item">
						<div class="charitable-admin-popup-whats-next-item-icon">
							<img class="popup_icon" src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/mail.svg'; ?>" />
						</div>
						<div class="charitable-admin-popup-whats-next-item-text">
							<h5><?php esc_html_e( 'Turn one-time donors into ongoing supporters!', 'charitable' ); ?></h5>
							<p><?php esc_html_e( 'With charitable newsletter connect your donors can opt-in to join your newsletter when they make their donation.', 'charitable' ); ?></p>
							<a href="#" class="charitable-admin-popup-btn-1"><?php esc_html_e( 'Learn More', 'charitable' ); ?></a>
						</div>
					</div>

					<div class="charitable-admin-popup-whats-next-item">
						<div class="charitable-admin-popup-whats-next-item-icon">
							<img class="popup_icon" src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/group.svg'; ?>" />
						</div>
						<div class="charitable-admin-popup-whats-next-item-text">
							<h5><?php esc_html_e( 'Recruit more people for your campaign!', 'charitable' ); ?></h5>
							<p><?php esc_html_e( 'With Charitable Ambassadors use peer-to-peer fundraising to grow your organisation’s reach an raise more money.', 'charitable' ); ?></p>
							<a href="#" class="charitable-admin-popup-btn-2"><?php esc_html_e( 'Learn More', 'charitable' ); ?></a>
						</div>
					</div>

					<div class="charitable-admin-popup-whats-next-item">
						<div class="charitable-admin-popup-whats-next-item-icon">
							<img class="popup_icon" src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/update.svg'; ?>" />
						</div>
						<div class="charitable-admin-popup-whats-next-item-text">
							<h5><?php esc_html_e( 'Run automations when you get donations!', 'charitable' ); ?></h5>
							<p><?php esc_html_e( 'With charitable automation connect create workflows with webhooks or automation platforms like Zapier or Zoho Flow.', 'charitable' ); ?></p>
							<a href="#" class="charitable-admin-popup-btn-3"><?php esc_html_e( 'Learn More', 'charitable' ); ?></a>
						</div>
					</div>

				</div>

			<?php endif; */ ?>

			<p class="secondary see-more">
				<?php
				printf(
					wp_kses( /* translators: %1$s - URLs. */
						__( '<a class="charitable-see-more-ways" target="_blank" href="%1$s">View extensions available to <strong>PRO</strong> users</a>.', 'charitable' ),
						[
							'a' => [
								'href'   => [],
								'class'  => [],
								'target' => [],
							],
						]
					),
					'https://wpcharitable.com/extensions'
				);
				?>
			</p>
		</div>
		<div class="charitable-admin-popup-close"><img class="popup_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/close.svg' ); ?>" /></div>
	</div>
</div>
