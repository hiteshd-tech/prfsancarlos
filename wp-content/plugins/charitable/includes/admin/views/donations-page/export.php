<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the export button in the donation filters box.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Donations Page
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<div class="alignleft actions charitable-export-actions charitable-donation-export-actions">
	<a href="#charitable-donations-export-modal" title="<?php esc_attr_e( 'Export', 'charitable' ); ?>" class="donation-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal="charitable-donations-export-modal"><img src="<?php echo esc_url( charitable()->get_path( 'directory', false ) ) . 'assets/images/icons/export.svg'; ?>" alt="<?php esc_attr_e( 'Export', 'charitable' ); ?>"  /><label><?php esc_html_e( 'Export', 'charitable' ); ?></label></a>
</div>
