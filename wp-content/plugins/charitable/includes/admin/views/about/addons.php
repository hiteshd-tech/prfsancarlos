<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the Addons section of About tab.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/About
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 * @version   1.8.8.6
 */

if ( ! charitable_current_user_can( 'manage_charitable_settings' ) ) {
	return;
}

// Ensure functions are loaded.
if ( ! function_exists( 'charitable_get_am_plugins' ) || ! function_exists( 'charitable_get_plugin_data' ) ) {
	return;
}

$charitable_all_plugins          = get_plugins();
$charitable_am_plugins           = charitable_get_am_plugins();
$charitable_can_install_plugins  = charitable_can_install( 'plugin' );
$charitable_can_activate_plugins = charitable_can_activate( 'plugin' );

?>
<div id="charitable-admin-addons">
	<div class="addons-container">
		<?php
		foreach ( $charitable_am_plugins as $charitable_plugin => $charitable_details ) :

			$charitable_plugin_data              = charitable_get_plugin_data( $charitable_plugin, $charitable_details, $charitable_all_plugins );
			$charitable_plugin_ready_to_activate = $charitable_can_activate_plugins
				&& isset( $charitable_plugin_data['status_class'] )
				&& $charitable_plugin_data['status_class'] === 'status-installed';
			$charitable_plugin_not_activated     = ! isset( $charitable_plugin_data['status_class'] )
				|| $charitable_plugin_data['status_class'] !== 'status-active';

			?>
			<div class="addon-container">
				<div class="addon-item">
					<div class="details charitable-clear">
						<img src="<?php echo esc_url( $charitable_plugin_data['details']['icon'] ); ?>" alt="<?php echo esc_attr( $charitable_plugin_data['details']['name'] ); ?>">
						<h5 class="addon-name">
							<?php echo esc_html( $charitable_plugin_data['details']['name'] ); ?>
						</h5>
						<p class="addon-desc">
							<?php echo wp_kses_post( $charitable_plugin_data['details']['desc'] ); ?>
						</p>
					</div>
					<div class="actions charitable-clear">
						<div class="status">
							<strong>
								<?php
								printf( /* translators: %s - status label. */
									esc_html__( 'Status: %s', 'charitable' ),
									'<span class="status-label ' . esc_attr( $charitable_plugin_data['status_class'] ) . '">' . wp_kses_post( $charitable_plugin_data['status_text'] ) . '</span>'
								);
								?>
							</strong>
						</div>
						<div class="action-button">
							<?php if ( $charitable_can_install_plugins || $charitable_plugin_ready_to_activate || ! $charitable_details['wporg'] ) { ?>
								<button class="<?php echo esc_attr( $charitable_plugin_data['action_class'] ); ?>" data-plugin="<?php echo esc_attr( $charitable_plugin_data['plugin_src'] ); ?>" data-type="plugin">
									<?php echo wp_kses_post( $charitable_plugin_data['action_text'] ); ?>
								</button>
							<?php } elseif ( $charitable_plugin_not_activated ) { ?>
								<a href="<?php echo esc_url( $charitable_details['wporg'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'WordPress.org', 'charitable' ); ?>
									<span aria-hidden="true" class="dashicons dashicons-external"></span>
								</a>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
