<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the legacy add button in the campaign filters box.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Campaigns Page
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 * @version   1.8.3.5
 * @version   1.8.8.6
 */

if ( ! empty( $_GET['post_status'] ) && 'trash' === $_GET['post_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
	return;
}

// check the advanced setting for disabled legacy campaigns.
$charitable_disable_legacy_campaign = charitable_get_option( 'disable_campaign_legacy_mode', false ) ? true : false;
$charitable_disable_legacy_campaign = apply_filters( 'charitable_disable_legacy_campaign', $charitable_disable_legacy_campaign );

?>
<div class="alignleft actions charitable-legacy-actions charitable-campaign-legacy-actions">

	<?php if ( ! charitable_disable_legacy_campaigns() ) : ?>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" title="<?php esc_attr_e( 'Create A New Modern Campaign', 'charitable' ); ?>" class="campaign-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal><img src="<?php echo esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/icons/add.svg' ); ?>" alt="<?php esc_attr_e( 'Create A New Modern Campaign', 'charitable' ); ?>"  /><label><?php esc_html_e( 'Add Modern Campaign', 'charitable' ); ?></label></a>

	<?php else : ?>

		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" title="<?php esc_attr_e( 'Create A New Legacy Campaign', 'charitable' ); ?>" class="campaign-export-with-icon trigger-modal hide-if-no-js" data-trigger-modal><img src="<?php echo esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/icons/add.svg' ); ?>" alt="<?php esc_attr_e( 'Create A New Legacy Campaign', 'charitable' ); ?>"  /><label><?php esc_html_e( 'Add Legacy', 'charitable' ); ?></label></a>

	<?php endif; ?>


</div>
