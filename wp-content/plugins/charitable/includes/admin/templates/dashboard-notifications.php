<?php
/**
 * Admin Dashboard template.
 *
 * @since 1.8.2
 * @version 1.8.8.6
 *
 * @var array $notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $notifications ) || ! is_array( $notifications ) ) {
	return;
}

$charitable_notifications_count = 1;
$charitable_notifications_total = count( $notifications );


?>

<div class="charitable-container charitable-dashboard-notifications">

	<div class="charitable-dashboard-notification-bar">

			<?php if ( (int) $charitable_notifications_total > 1 ) : ?>
				<div class="charitable-dashboard-notification-navigation">
					<a class="prev">
						<span class="screen-reader-text"><?php esc_attr_e( 'Previous message', 'charitable' ); ?></span>
						<span aria-hidden="true">&lsaquo;</span>
					</a>
					<a class="next">
						<span class="screen-reader-text"><?php esc_attr_e( 'Next message', 'charitable' ); ?></span>
						<span aria-hidden="true">&rsaquo;</span>
					</a>
				</div>
			<?php else : ?>
				<div class="charitable-dashboard-notification-navigation"></div>
			<?php endif; ?>

			<a href="#" class="charitable-remove-dashboard-notification"></a>

		</div>

	<?php

	foreach ( $notifications as $charitable_notification_slug => $charitable_notification ) :

		$charitable_css_class     = ! empty( $charitable_notification['custom_css'] ) ? $charitable_notification['custom_css'] : '';
		$charitable_css_class    .= $charitable_notifications_count === 1 ? '' : ' charitable-hidden';
		$charitable_message_title = ! empty( $charitable_notification['title'] ) ? sanitize_text_field( $charitable_notification['title'] ) : esc_html__( 'Important', 'charitable' );
		$charitable_message       = ! empty( $charitable_notification['message'] ) ? $charitable_notification['message'] : '';
			$charitable_message       = wp_kses(
			$charitable_message,
			array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'strong' => array(),
				'p'      => array(),
				'ol'     => array(),
				'ul'     => array(),
				'li'     => array(),
				'br'     => array(),
				'h1'     => array(),
				'h2'     => array(),
				'h3'     => array(),
				'h4'     => array(),
				'h5'     => array(),
			)
		);

		?>

			<div class="charitable-dashboard-notification <?php echo esc_attr( $charitable_css_class ); ?>" data-notification-number="<?php echo (int) $charitable_notifications_count; ?>" data-notification-id="<?php echo esc_attr( $charitable_notification_slug ); ?>" data-notification-type="<?php echo esc_attr( $charitable_notification['type'] ); ?>">

				<div class="charitable-dashboard-notification-message">
				<h4 class="charitable-dashboard-notification-headline"><?php echo esc_html( $charitable_message_title ); ?></h4>
					<?php echo $charitable_message; // phpcs:ignore ?>
				</div>

			</div>

		<?php

		++$charitable_notifications_count;

	endforeach;
	?>

</div>
