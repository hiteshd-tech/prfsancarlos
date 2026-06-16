<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the Charitable admin header.
 *
 * @package Charitable
 * @since   1.8.0
 * @version 1.8.8.6
 */

// if there are new notifications add the indicator to the menu title.
$charitable_notification_count = class_exists( 'Charitable_Notifications' ) ? Charitable_Notifications::get_instance()->get_new_notifications_count() : 0;
$charitable_display_text = $charitable_notification_count > 0 ? ( $charitable_notification_count > 9 ? '9+' : $charitable_notification_count ) : '';
$charitable_indictor_html = $charitable_notification_count > 0 ? '<span class="round number">' . $charitable_display_text . '</span>' : '';
?>

<?php do_action( 'charitable_admin_before_header' ); ?>

<div id="charitable-admin-header" class="charitable-admin-header charitable-campaign-header">
	<div class="charitable-admin-header-interior">

		<h1 class="charitable-logo" id="charitable-logo">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable' ) ); ?>"><img src="<?php echo esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/charitable-header-logo.png' ); ?>" alt="<?php esc_html_e( 'Charitable', 'charitable' ); ?>" width="200" height="38" /></a>
		</h1>
		<div class="charitable-header-logos">
			<ul>
				<li><a href="#" title="<?php echo esc_html__( 'Notifications', 'charitable' ); ?>" class="charitable-header-logo charitable-notification-inbox"><?php echo ( wp_kses( $charitable_indictor_html, array( 'span' => array( 'class' => array() ) ) ) ); ?>
				<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-notifications-icon"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.8333 2.5H4.16667C3.25 2.5 2.5 3.25 2.5 4.16667V15.8333C2.5 16.75 3.24167 17.5 4.16667 17.5H15.8333C16.75 17.5 17.5 16.75 17.5 15.8333V4.16667C17.5 3.25 16.75 2.5 15.8333 2.5ZM15.8333 15.8333H4.16667V13.3333H7.13333C7.70833 14.325 8.775 15 10.0083 15C11.2417 15 12.3 14.325 12.8833 13.3333H15.8333V15.8333ZM11.675 11.6667H15.8333V4.16667H4.16667V11.6667H8.34167C8.34167 12.5833 9.09167 13.3333 10.0083 13.3333C10.925 13.3333 11.675 12.5833 11.675 11.6667Z" fill="currentColor"></path></svg></a></li><li><a href="<?php echo esc_url( charitable_help_link() ); ?>" target="_blank" title="<?php esc_html_e( 'Help', 'charitable' ); ?>" class="charitable-header-logo charitable-notification-help"><img src="<?php echo esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/icons/help.png' ); ?>" alt="<?php esc_html_e( 'Help', 'charitable' ); ?>" width="25" height="25" /></a></li>
			</ul>
		</div>

	<?php do_action( 'charitable_admin_in_header' ); ?>

	</div>
</div>

<?php do_action( 'charitable_admin_after_header' ); ?>

<?php do_action( 'charitable_maybe_show_plugin_notifications' ); ?>