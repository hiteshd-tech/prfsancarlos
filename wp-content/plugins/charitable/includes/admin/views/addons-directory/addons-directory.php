<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main settings page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.19
 * @version   1.8.8.6
 */

do_action( 'charitable_addons_directory_page_start' );

$charitable_plan_slug = Charitable_Addons_Directory::get_current_plan_slug();

ob_start();
?>
<div id="charitable-settings" class="wrap">
	<h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php do_action( 'charitable_maybe_show_notification' ); ?>
	<div id="charitable-admin-addons" class="wrap charitable-admin-wrap">
		<?php do_action( 'charitable_addons_directory_section' ); ?>
	</div>
</div>
<?php

do_action( 'charitable_addons_directory_page_end' );

echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
