<?php
/**
 * Admin Notifications template.
 *
 * @since 1.8.3
 * @version 1.8.8.6
 *
 * @package Charitable/Admin/Templates
 * @var array $notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_active_count           = intval( $args['notifications']['active_count'] );
$charitable_active_count_string    = esc_html( $args['notifications']['active_count'] );
$charitable_remaining_active_count = $charitable_active_count > 4 ? $charitable_active_count - 3 : 0;
$charitable_dismissed_count        = intval( $args['notifications']['dismissed_count'] );
$charitable_no_items_css           = ( $charitable_active_count > 0 ) ? 'charitable-hidden' : '';
$charitable_yes_items_css          = ( $charitable_active_count === 0 ) ? 'charitable-hidden' : '';
$charitable_notifications_title    = $charitable_active_count > 1 ? esc_html__( 'New Notifications', 'charitable' ) : esc_html__( 'New Notification', 'charitable' );

?>


<div class="charitable-container charitable-report-card charitable-dashboard-notifications">
	<div class="header">
		<?php if ( $charitable_active_count ) : ?>
			<h4>(<span id="new-notifications-count-dashboard"><?php echo esc_html( $charitable_active_count_string ); ?></span>) <?php echo esc_html( $charitable_notifications_title ); ?></h4>
		<?php else : ?>
			<h4><?php echo esc_html__( 'Notifications', 'charitable' ); ?></h4>
		<?php endif; ?>

		<a href="#" class="charitable-toggle"><i class="fa fa-angle-down charitable-angle-down"></i></a>
	</div>
	<div class="charitable-toggle-container charitable-report-ui">
		<div class="no-items <?php echo esc_attr( $charitable_no_items_css ); ?>">
			<p><strong><?php echo esc_html__( 'There are currently no active notifications.', 'charitable' ); ?></strong></p>
			<p class="link charitable-view-notifications"><a href="#"><?php echo esc_html__( 'View Notifications', 'charitable' ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a></p>
		</div>
		<div class="the-list <?php echo esc_attr( $charitable_yes_items_css ); ?>">
			<?php echo $args['notifications']['active_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<div class="more">
		<?php if ( $charitable_remaining_active_count ) : ?>
			<a href="#"><?php wp_sprintf( 'You have %d more notifications', $charitable_remaining_active_count ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a>
		<?php elseif ( $charitable_active_count > 0 || $charitable_dismissed_count > 0 ) : ?>
			<a href="#"><?php esc_html_e( 'View Notifications', 'charitable' ); ?><img src="<?php echo charitable()->get_path( 'assets', false ) . 'images/icons/east.svg'; // phpcs:ignore ?>" /></a>
		<?php endif; ?>
	</div>

</div>