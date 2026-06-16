<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the Getting Started tab content.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/About
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 */

ob_start();
?>

<div class="charitable-admin-about-section">
	<div class="charitable-admin-columns">
		<div class="charitable-admin-column">
			<h2><?php esc_html_e( 'Quick Start Guide', 'charitable' ); ?></h2>
			<p><?php esc_html_e( 'Coming soon...', 'charitable' ); ?></p>
		</div>
		<div class="charitable-admin-column">
			<h2><?php esc_html_e( 'Tutorials & Resources', 'charitable' ); ?></h2>
			<p><?php esc_html_e( 'Coming soon...', 'charitable' ); ?></p>
		</div>
	</div>
</div>

<div id="wpfooter">
	<?php charitable_admin_view( 'admin-footer-promotion' ); ?>
</div>

<?php
echo ob_get_clean(); // phpcs:ignore
