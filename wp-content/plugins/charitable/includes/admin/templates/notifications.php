<?php
/**
 * Admin Notifications template.
 *
 * @since 1.8.3
 * @version 1.8.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_active_count        = intval( $args['notifications']['active_count'] );
$charitable_dismissed_count     = intval( $args['notifications']['dismissed_count'] );
$charitable_notifications_title = $charitable_active_count > 1 ? esc_html__( 'New Notifications', 'charitable' ) : esc_html__( 'New Notifications', 'charitable' );

?>

<div class="charitable-plugin-notifications" id="charitable-plugin-notifications">
	<div class="notification-menu">
		<div class="notification-header">
			<span class="new-notifications notifications-visible">(<span id="new-notifications-count"><strong><?php echo intval( $charitable_active_count ); ?></strong></span>) <?php echo esc_html( $charitable_notifications_title ); ?></span>
			<span class="old-notifications">(<span id="dismissed-notifications-count"><strong><?php echo intval( $charitable_dismissed_count ); ?></strong></span>) <?php esc_attr_e( 'Dismissed Notifications', 'charitable' ); ?></span>
			<div class="dismissed-notifications">
				<!---->
				<a href="#" data-status="dismissed"><?php esc_attr_e( 'Dismissed Notifications', 'charitable' ); ?></a>
				<!---->
			</div>
			<div>
				<svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-close">
					<path d="M11.8211 1.3415L10.6451 0.166504L5.98305 4.82484L1.32097 0.166504L0.14502 1.3415L4.80711 5.99984L0.14502 10.6582L1.32097 11.8332L5.98305 7.17484L10.6451 11.8332L11.8211 10.6582L7.159 5.99984L11.8211 1.3415Z" fill="currentColor"></path>
				</svg>
			</div>
		</div>
		<div class="charitable-notification-cards notification-cards notification-cards-active notification-cards-visible">
			<?php echo $args['notifications']['active_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<!---->
		</div>
		<div class="charitable-notification-cards notification-cards notification-cards-dismissed">
			<?php if ( empty( $args['notifications']['dismissed_html'] ) ) : ?>
				<div class="notification-card">
					<div class="notification-card-content">
						<span class="notification-no-dismissed-title"><?php esc_attr_e( 'No dismissed notifications.', 'charitable' ); ?></span>
					</div>
				</div>
			<?php else : ?>
				<?php echo $args['notifications']['dismissed_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<div class="notification-footer">
			<?php

				// Show the dismiss all button if there are active notifications.
			if ( $charitable_active_count > 0 ) :

				?>
			<div class="dismiss-all"><a href="#" class="dismiss"><?php esc_attr_e( 'Dismiss All', 'charitable' ); ?></a></div>
			<?php endif; ?>
		</div>
	</div>
	<div class="charitable-notifications-overlay"></div>
</div>
