<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the export button in the campaign filters box.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Campaigns Page
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

?>
<div class="alignleft actions charitable-export-actions charitable-campaign-export-actions">
	<a href="#charitable-campaigns-export-modal" title="<?php esc_html_e( 'Export', 'charitable' ); ?>" class="campaign-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal="charitable-campaigns-export-modal"><img src="<?php echo esc_url( charitable()->get_path( 'directory', false ) ) . 'assets/images/icons/export.svg'; ?>" alt="<?php esc_html_e( 'Export', 'charitable' ); ?>"  /><label><?php esc_html_e( 'Export', 'charitable' ); ?></label></a>
</div>
