<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the campaign benefactors form.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 * @version   1.8.8.6
 */

$charitable_benefactor = $view_args['benefactor'];

if ( $charitable_benefactor->is_active() ) {
	$charitable_summary = $charitable_benefactor;
} elseif ( $charitable_benefactor->is_expired() ) {
	$charitable_summary = sprintf( '<span>%s</span>%s', __( 'Expired', 'charitable' ), $charitable_benefactor );
} else {
	$charitable_summary = sprintf( '<span>%s</span>%s', __( 'Inactive', 'charitable' ), $charitable_benefactor );
}

?>
<div class="charitable-benefactor-summary">
	<span class="summary"><?php echo wp_kses_post( $charitable_summary ); ?></span>
	<span class="alignright">
		<a href="#" data-charitable-toggle="campaign_benefactor_<?php echo esc_attr( $charitable_benefactor->campaign_benefactor_id ); ?>" data-charitable-toggle-text="<?php esc_attr_e( 'Close', 'charitable' ); ?>"><?php esc_html_e( 'Edit', 'charitable' ); ?></a>&nbsp;&nbsp;&nbsp;
		<a href="#" data-campaign-benefactor-delete="<?php echo esc_attr( $charitable_benefactor->campaign_benefactor_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'charitable-deactivate-benefactor' ) ); ?>"><?php esc_html_e( 'Delete', 'charitable' ); ?></a>
	</span>
</div>
