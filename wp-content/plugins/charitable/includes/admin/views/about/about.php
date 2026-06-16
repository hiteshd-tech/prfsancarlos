<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main about page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/About
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 */

$active_view = isset( $_GET['view'] ) ? esc_html( $_GET['view'] ) : 'about'; // phpcs:ignore

ob_start();
?>

<div id="charitable-about" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<h1 class="charitable-about-hidden-title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	
	<h2 class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-about&view=about' ) ); ?>" class="nav-tab <?php echo $active_view == 'about' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'About Us', 'charitable' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Getting Started', 'charitable' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-about&view=lite-vs-pro' ) ); ?>" class="nav-tab <?php echo $active_view == 'lite-vs-pro' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Lite vs Pro', 'charitable' ); ?></a>
	</h2>

	<?php
	// Load the appropriate tab content
	switch ( $active_view ) {
		case 'about':
			charitable_admin_view( 'about/tabs/about-us' );
			break;
		case 'lite-vs-pro':
			charitable_admin_view( 'about/tabs/lite-vs-pro' );
			break;
		default:
			charitable_admin_view( 'about/tabs/about-us' );
			break;
	}
	?>

</div>

<?php
echo ob_get_clean(); // phpcs:ignore
