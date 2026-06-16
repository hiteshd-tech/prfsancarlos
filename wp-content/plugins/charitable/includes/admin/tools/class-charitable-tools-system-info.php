<?php
/**
 * Charitable Tools - System Info.
 *
 * @package   Charitable/Classes/Charitable_Tools_System_Info
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools_System_Info' ) ) :

	/**
	 * Charitable_Tools
	 *
	 * @final
	 * @since 1.8.1.6
	 */
	class Charitable_Tools_System_Info {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.8.1.6
		 *
		 * @var    Charitable_Tools_System_Info|null
		 */
		private static $instance = null;

		/**
		 * Load the class.
		 *
		 * @since 1.8.1.6
		 */
		public function load() {
			// Setup completed - hooks are registered via charitable-tools-admin-hooks.php
		}

		/**
		 * Hooks.
		 *
		 * @since 1.8.1.6
		 */
		private function hooks() {
			// AJAX actions are now registered in charitable-tools-admin-hooks.php
		}

		/**
		 * Enqueue assets.
		 *
		 * @since   1.8.1.6
		 */
		public function enqueue_scripts() {

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( ( ! empty( $_GET['page'] ) && 'charitable-tools' === $_GET['page'] ) || ( ! is_null( $screen ) && 'charitable_page_charitable-tools' === $screen->base ) || ( ! empty( $_GET['tab'] ) && 'system-info' === $_GET['tab'] ) ||
			 ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'charitable-tools' ) !== false ) ) { // phpcs:ignore

				wp_enqueue_style(
					'charitable-system-info',
					$assets_dir . 'css/tools/system-info' . $min . '.css',
					array(),
					$version
				);

				wp_enqueue_script(
					'charitable-system-info',
					$assets_dir . "js/tools/system-info{$min}.js",
					[ 'jquery' ],
					$version,
					true
				);

				// Localize script for AJAX
				wp_localize_script(
					'charitable-system-info',
					'charitable_email_diagnostics',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'charitable_email_diagnostics' ),
						'test_email_nonce' => wp_create_nonce( 'charitable_send_test_email' ),
						'running_text' => __( 'Running Diagnostics...', 'charitable' ),
						'error_text' => __( 'Diagnostics failed. Please try again.', 'charitable' ),
						'sending_text' => __( 'Sending...', 'charitable' )
					)
				);

				// Localize script for admin functions (error logs, SSL, etc.)
				// Use unique variable 'charitable_admin_tools' so it is not overwritten by
				// charitable-admin-2.0.js which also localizes 'charitable_admin' (no nonce).
				// Nonce: wp_create_nonce( 'charitable-admin-tools' ), verified by check_ajax_referer( 'charitable-admin-tools', 'nonce', false ).
				wp_localize_script(
					'charitable-system-info',
					'charitable_admin_tools',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'charitable-admin-tools' ),
						'testing'  => __( 'Testing...', 'charitable' ),
					)
				);

			}
		}

		/**
		 * Get view label.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		public function get_label() {

			return esc_html__( 'System Info', 'charitable' );
		}

		/**
		 * Checking user capability to view.
		 *
		 * @since 1.8.1.6
		 *
		 * @return bool
		 */
		public function check_capability() {

			return charitable_current_user_can();
		}

		/**
		 * System view content.
		 *
		 * @since 1.8.1.6
		 */
		public function display() {
			?>

			<div class="charitable-system-info-row">
				<h4 id="system-information"><?php esc_html_e( 'System Information', 'charitable' ); ?></h4>
				<textarea id="charitable-system-information" class="info-area" readonly><?php echo esc_textarea( $this->get_system_info() ); ?></textarea>
				<button type="button" id="charitable-system-information-copy" class="button button-primary">
					<?php esc_html_e( 'Copy System Information', 'charitable' ); ?>
				</button>
			</div>

			<div class="charitable-system-info-row">
				<h4 id="email-diagnostics"><?php esc_html_e( 'Email System Diagnostics', 'charitable' ); ?></h4>
				<p class="desc"><?php esc_html_e( 'Run comprehensive email system diagnostics to troubleshoot any potential issues.', 'charitable' ); ?></p>
				<button type="button" id="charitable-email-diagnostics" class="button button-primary">
					<?php esc_html_e( 'Run Detailed Email Diagnostics', 'charitable' ); ?>
				</button>
				<button type="button" id="charitable-send-test-email" class="button button-secondary" style="margin-left: 10px;">
					<?php esc_html_e( 'Send Test Email', 'charitable' ); ?>
				</button>
				<div id="charitable-email-diagnostics-results" style="display:none;">
					<textarea class="info-area" readonly placeholder="<?php esc_attr_e( 'Diagnostic results will appear here...', 'charitable' ); ?>"></textarea>
					<button type="button" id="charitable-email-diagnostics-copy" class="button button-secondary" style="display:none;">
						<?php esc_html_e( 'Copy Diagnostic Results', 'charitable' ); ?>
					</button>
				</div>
				<div id="charitable-test-email-message" style="display:none; margin-top: 10px;">
					<div class="charitable-notice" style="padding: 10px; border-left: 4px solid #0073aa; background: #f9f9f9;">
						<p id="charitable-test-email-message-text"></p>
					</div>
				</div>
			</div>

			<div class="charitable-system-info-row compact charitable-settings-row-debug-log-scanner">
				<h4 id="debug-log-scanner"><?php esc_html_e( 'Debug Log Scanner', 'charitable' ); ?></h4>
				<p class="desc"><?php esc_html_e( 'Scans WordPress debug logs to help identify issues for troubleshooting.', 'charitable' ); ?></p>
				<?php
				$scanner = Charitable_Tools_Debug_Log_Scanner::get_instance();
				$ui_state = $scanner->get_ui_state();

				// Show status message based on UI state
				switch ( $ui_state['status'] ) {
					case 'disabled_no_file':
						echo '<div class="charitable-notice" style="padding: 10px; border-left: 4px solid #ffba00; background: #fff8e1; margin: 10px 0;">';
						echo '<p><strong>' . esc_html__( 'WordPress Debug Logging Not Enabled', 'charitable' ) . '</strong></p>';
						echo '<p>' . esc_html__( 'To use the debug log scanner you need to enable WordPress debug logging. Add these lines to your wp-config.php file:', 'charitable' ) . '</p>';
						echo '<p><code style="background: #f0f0f0; padding: 2px 5px; font-family: monospace;">define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);</code></p>';
						echo '<p>' . sprintf(
							__( 'For more information see our <a href="%s" target="_blank">guide to debugging WordPress</a>', 'charitable' ),
							'https://wpcharitable.com/documentation/debugging-wordpress'
						) . '</p>';
						echo '</div>';
						break;

					case 'no_errors_yet':
						echo '<div class="charitable-notice" style="padding: 10px; border-left: 4px solid #0073aa; background: #e7f3ff; margin: 10px 0;">';
						echo '<p><strong>' . esc_html__( 'No Error Log Found', 'charitable' ) . '</strong></p>';
						echo '<p>' . esc_html( $ui_state['message'] ) . '</p>';
						echo '</div>';
						break;

					case 'disabled_but_file_exists':
						echo '<div class="charitable-notice" style="padding: 10px; border-left: 4px solid #ffba00; background: #fff8e1; margin: 10px 0;">';
						echo '<p><strong>' . esc_html__( 'WordPress Debug Logging Disabled', 'charitable' ) . '</strong></p>';
						echo '<p>' . esc_html( $ui_state['message'] ) . '</p>';
						echo '<p>' . sprintf(
							__( 'To enable debug logging see our <a href="%s" target="_blank">guide to debugging WordPress</a>', 'charitable' ),
							'https://wpcharitable.com/documentation/debugging-wordpress'
						) . '</p>';
						echo '</div>';
						break;
				}
				?>

				<?php if ( $ui_state['show_button'] ) : ?>
					<button type="button" id="charitable-debug-log-scan" class="button button-primary" <?php echo $ui_state['button_enabled'] ? '' : 'disabled'; ?>>
						<?php esc_html_e( 'Scan Debug Log', 'charitable' ); ?>
					</button>
					<p style="margin-top: 5px; color: #666; font-size: 13px;">
						<?php
						if ( $ui_state['status'] === 'disabled_but_file_exists' ) {
							esc_html_e( 'Scanning historical log data. Scans the last 24 hours by default.', 'charitable' );
						} else {
							esc_html_e( 'Scans the last 24 hours by default.', 'charitable' );
						}
						?>
					</p>
				<?php endif; ?>
				<div id="charitable-debug-log-results" style="display:none; margin-top: 15px;">
					<div class="charitable-debug-format-tabs" style="margin-bottom: 10px;">
						<button type="button" class="button button-secondary charitable-format-tab active" data-format="summary">
							<?php esc_html_e( 'Summary', 'charitable' ); ?>
						</button>
						<button type="button" class="button button-secondary charitable-format-tab" data-format="technical">
							<?php esc_html_e( 'Technical Details', 'charitable' ); ?>
						</button>
						<button type="button" class="button button-secondary charitable-format-tab" data-format="forum">
							<?php esc_html_e( 'Forum-Ready', 'charitable' ); ?>
						</button>
					</div>
					<textarea id="charitable-debug-summary" class="info-area charitable-format-content" readonly placeholder="<?php esc_attr_e( 'Debug log scan summary will appear here...', 'charitable' ); ?>"></textarea>
					<textarea id="charitable-debug-technical" class="info-area charitable-format-content" readonly placeholder="<?php esc_attr_e( 'Technical details will appear here...', 'charitable' ); ?>" style="display:none;"></textarea>
					<textarea id="charitable-debug-forum" class="info-area charitable-format-content" readonly placeholder="<?php esc_attr_e( 'Forum-ready format will appear here...', 'charitable' ); ?>" style="display:none;"></textarea>
					<div style="margin-top: 10px;">
						<button type="button" id="charitable-debug-copy" class="button button-secondary">
							<?php esc_html_e( 'Copy Current View', 'charitable' ); ?>
						</button>
						<span id="charitable-debug-copy-message" style="margin-left: 10px; color: #46b450; display: none;">
							<?php esc_html_e( 'Copied to clipboard!', 'charitable' ); ?>
						</span>
					</div>
				</div>
				<div id="charitable-debug-log-message" style="display:none; margin-top: 10px;">
					<div class="charitable-notice" style="padding: 10px; border-left: 4px solid #0073aa; background: #f9f9f9;">
					<p id="charitable-debug-log-message-text"></p>
					</div>
				</div>
			</div>

			<div class="charitable-system-info-row compact charitable-settings-row-error-logs">
				<h4 id="donation-error-logs"><?php esc_html_e( 'Donation Form Error Logs', 'charitable' ); ?></h4>
				<p class="desc"><?php esc_html_e( 'Recent donation form errors and troubleshooting information.', 'charitable' ); ?></p>
				<?php
				$recent_errors = charitable_get_recent_form_errors( 10 );
				if ( ! empty( $recent_errors ) ) {
					$error_count = count( charitable_get_recent_form_errors( 50 ) );
					echo '<p><strong>' . sprintf( esc_html( _n( '%d error found in recent logs', '%d errors found in recent logs', $error_count, 'charitable' ) ), $error_count ) . '</strong></p>';
					?>
					<p>
						<button type="button" id="charitable-toggle-error-logs" class="button button-secondary">
							<?php esc_html_e( 'Show Error Logs', 'charitable' ); ?>
						</button>
					</p>
					<div id="charitable-error-logs-container" style="display: none; margin-top: 15px;">
						<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
							<?php foreach ( array_slice( $recent_errors, 0, 10 ) as $error ) : ?>
								<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
									<strong><?php echo esc_html( $error->date_recorded ); ?></strong>
									<span style="color: #d63638;"><?php echo esc_html( ucwords( str_replace( '_', ' ', $error->primary_action ) ) ); ?></span>
									- <?php echo esc_html( ucwords( str_replace( '_', ' ', $error->secondary_action ) ) ); ?>
									<?php if ( $error->campaign_id ) : ?>
										<br><small>Campaign: <?php echo esc_html( get_the_title( $error->campaign_id ) ); ?> (#<?php echo intval( $error->campaign_id ); ?>)</small>
									<?php endif; ?>
									<?php if ( $error->amount ) : ?>
										<small>| Amount: <?php echo esc_html( charitable_format_money( $error->amount ) ); ?></small>
									<?php endif; ?>
									<?php if ( ! empty( $error->meta_data['error_message'] ) ) : ?>
										<br><small style="color: #826200;">→ <?php echo esc_html( $error->meta_data['error_message'] ); ?></small>
									<?php endif; ?>
									<?php if ( ! empty( $error->meta_data['email_class'] ) ) : ?>
										<small style="color: #888;">[<?php echo esc_html( $error->meta_data['email_class'] ); ?>]</small>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<p style="margin-top: 10px;">
							<button type="button" id="charitable-clear-error-logs" class="button button-secondary">
								<?php esc_html_e( 'Clear Error Logs', 'charitable' ); ?>
							</button>
							<button type="button" id="charitable-export-error-logs" class="button button-secondary" style="margin-left: 10px;">
								<?php esc_html_e( 'Export Error Logs', 'charitable' ); ?>
							</button>
						</p>
					</div>
					<?php
				} else {
					echo '<p style="color: #46b450;"><strong>' . esc_html__( 'No recent donation form errors found.', 'charitable' ) . '</strong></p>';
				}
				?>
				<div id="charitable-error-log-message" style="display:none; margin-top: 10px;">
					<div class="charitable-notice" style="padding: 10px; border-left: 4px solid #0073aa; background: #f9f9f9;">
						<p id="charitable-error-log-message-text"></p>
					</div>
				</div>
			</div>

			<div class="charitable-system-info-row last charitable-settings-row-test-ssl">
				<h4 id="ssl-verify"><?php esc_html_e( 'Test SSL Connections', 'charitable' ); ?></h4>
				<p class="desc"><?php esc_html_e( 'Click the button below to verify your web server can perform SSL connections successfully.', 'charitable' ); ?></p>
				<button type="button" id="charitable-ssl-verify" class="button button-primary">
					<?php esc_html_e( 'Test Connection', 'charitable' ); ?>
				</button>
			</div>

			<?php
		}

		/**
		 * Get system information.
		 *
		 * Based on a function from Easy Digital Downloads by Pippin Williamson.
		 *
		 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/admin/tools.php#L470
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		public function get_system_info() {

			$data = '### Begin System Info ###' . "\n\n";

			$data .= $this->charitable_info();
			$data .= $this->site_info();
			$data .= $this->wp_info();
			$data .= $this->uploads_info();
			$data .= $this->plugins_info();
			$data .= $this->server_info();
			$data .= $this->charitable_privacy_settings_info();
			$data .= $this->email_diagnostics();
			$data .= $this->donation_error_logs();
			$data .= $this->debug_log_scanner();

			$data .= "\n" . '### End System Info ###';

			return $data;
		}

		/**
		 * Get Charitable info.
		 *
		 * @since  1.8.1.6
		 * @version 1.8.3 added version upgraded from.
		 * @version 1.8.4.5
		 *
		 * @return string
		 */
		private function charitable_info() {

			$data = '-- Charitable Info' . "\n\n";

			$activated = get_option( 'wpcharitable_activated_datetime', false );

			$slug_dismissed   = get_user_meta( get_current_user_id(), 'charitable-pointer-slug-dismissed', true );
			$dismissed_addons = get_user_meta( get_current_user_id(), 'charitable_dismissed_addons', true );

			$wpc_plugin_version = (array) get_site_option( 'wpc_plugin_version' );

			$wpc_plugin_failure = (array) get_option( 'wpc_edd_sl_failed_plugin_versions' );

			$versions_upgraded_from = get_option( 'charitable_version_upgraded_from', [] );
			if ( ! empty( $versions_upgraded_from ) ) {
				$versions_upgraded_from = array_unique( $versions_upgraded_from );
			}

			if ( ! empty( $versions_upgraded_from ) ) {
				$data .= 'Versions Upgraded From:  ' . implode( ',', $versions_upgraded_from ) . "\n";
			}
			if ( ! empty( $slug_dismissed ) ) {
				$data .= 'Pointer Slugs Dismissed:  ' . implode( ',', $slug_dismissed ) . "\n";
			}
			if ( ! empty( $dismissed_addons ) ) {
				$data .= 'G Removals:           ' . implode( ',', $dismissed_addons ) . "\n";
			}
			if ( ! empty( array_filter( $wpc_plugin_version ) ) ) {
				$data .= 'Plugin Version DEBUG 1:  ' . implode( ',', $wpc_plugin_version ) . "\n";
			}
			if ( ! empty( array_filter( $wpc_plugin_failure ) ) ) {
				$data .= 'Plugin Version DEBUG 2:  ' . implode( ',', $wpc_plugin_failure ) . "\n";
			}
			if ( $activated ) {
				$data .= 'Activated:                ' . $this->get_formatted_datetime( $activated ) . "\n";
			}
			return $data;
		}

		/**
		 * Get Charitable privacy settings (for copyable system information).
		 *
		 * @since  1.8.1.6
		 *
		 * @return string
		 */
		private function charitable_privacy_settings_info() {

			if ( ! function_exists( 'charitable_get_option' ) ) {
				return '';
			}

			$retention_labels = array(
				0         => __( 'None', 'charitable' ),
				1         => __( 'One year', 'charitable' ),
				2         => __( 'Two years', 'charitable' ),
				3         => __( 'Three years', 'charitable' ),
				4         => __( 'Four years', 'charitable' ),
				5         => __( 'Five years', 'charitable' ),
				6         => __( 'Six years', 'charitable' ),
				7         => __( 'Seven years', 'charitable' ),
				8         => __( 'Eight years', 'charitable' ),
				9         => __( 'Nine years', 'charitable' ),
				10        => __( 'Ten years', 'charitable' ),
				'endless' => __( 'Forever', 'charitable' ),
			);

			$retention_period = charitable_get_option( 'minimum_data_retention_period', 2 );
			$retention_label  = isset( $retention_labels[ $retention_period ] ) ? $retention_labels[ $retention_period ] : (string) $retention_period;

			$data  = "\n" . '-- Charitable Privacy Settings' . "\n\n";
			$data .= 'Minimum Data Retention:   ' . $retention_label . "\n";

			$retention_fields = charitable_get_option( 'data_retention_fields', array() );
			if ( is_array( $retention_fields ) && ! empty( $retention_fields ) ) {
				$keys  = array_keys( $retention_fields );
				$list  = array_values( $keys ) === range( 0, count( $keys ) - 1 );
				$names = $list ? array_values( $retention_fields ) : $keys;
				$data .= 'Retained Data Fields:     ' . implode( ', ', $names ) . "\n";
			} else {
				$data .= 'Retained Data Fields:     (none or all)' . "\n";
			}

			$data .= 'Contact Consent Field:     ' . ( charitable_get_option( 'contact_consent', 0 ) ? 'Enabled' : 'Disabled' ) . "\n";
			$data .= 'Consent Required (Forms):  ' . ( charitable_get_option( 'contact_consent_required', false ) ? 'Yes' : 'No' ) . "\n";

			$contact_label_default = __( 'Yes, I am happy for you to contact me via email or phone.', 'charitable' );
			$contact_label_value   = charitable_get_option( 'contact_consent_label', $contact_label_default );
			$data .= 'Contact Consent Label:     ' . $this->privacy_text_summary( $contact_label_value, $contact_label_default ) . "\n";

			$data .= 'Privacy Policy Field:      ' . ( charitable_get_option( 'privacy_policy_enabled', 0 ) ? 'Enabled' : 'Disabled' ) . "\n";

			$privacy_page_id = charitable_get_option( 'privacy_policy_page', 0 );
			if ( ! empty( $privacy_page_id ) && get_post_status( $privacy_page_id ) ) {
				$data .= 'Privacy Policy Page:       ' . get_the_title( $privacy_page_id ) . ' (#' . (int) $privacy_page_id . ')' . "\n";
			} else {
				$data .= 'Privacy Policy Page:       Not set' . "\n";
			}

			$privacy_policy_default = __( 'Your personal data will be used to process your donation, support your experience throughout this website, and for other purposes described in our [privacy_policy].', 'charitable' );
			$privacy_policy_value   = charitable_get_option( 'privacy_policy', $privacy_policy_default );
			$data .= 'Privacy Policy Text:       ' . $this->privacy_text_summary( $privacy_policy_value, $privacy_policy_default ) . "\n";

			$data .= 'Terms and Conditions Field: ' . ( charitable_get_option( 'terms_and_conditions_enabled', 0 ) ? 'Enabled' : 'Disabled' ) . "\n";

			$terms_page_id = charitable_get_option( 'terms_conditions_page', 0 );
			if ( ! empty( $terms_page_id ) && get_post_status( $terms_page_id ) ) {
				$data .= 'Terms and Conditions Page:  ' . get_the_title( $terms_page_id ) . ' (#' . (int) $terms_page_id . ')' . "\n";
			} else {
				$data .= 'Terms and Conditions Page:  Not set' . "\n";
			}

			$terms_default = __( 'I have read and agree to the website [terms].', 'charitable' );
			$terms_value   = charitable_get_option( 'terms_conditions', $terms_default );
			$data .= 'Terms and Conditions Text:  ' . $this->privacy_text_summary( $terms_value, $terms_default ) . "\n";

			return $data;
		}

		/**
		 * Short summary for a privacy text option: "Default" or "Custom (X characters)".
		 *
		 * @since  1.8.1.6
		 *
		 * @param  string $value   Current option value.
		 * @param  string $default Default text to compare against.
		 * @return string
		 */
		private function privacy_text_summary( $value, $default ) {

			$value   = is_string( $value ) ? trim( $value ) : '';
			$default = is_string( $default ) ? trim( $default ) : '';

			if ( $value === $default || $value === '' ) {
				return 'Default';
			}

			return 'Custom (' . strlen( $value ) . ' characters)';
		}

		/**
		 * Get Site info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function site_info() {

			$data  = "\n" . '-- Site Info' . "\n\n";
			$data .= 'Site URL:                 ' . site_url() . "\n";
			$data .= 'Home URL:                 ' . home_url() . "\n";
			$data .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

			return $data;
		}

		/**
		 * Get WordPress Configuration info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function wp_info() {

			global $wpdb;

			$theme_data = wp_get_theme();
			$theme      = $theme_data->name . ' ' . $theme_data->version;

			$data  = "\n" . '-- WordPress Configuration' . "\n\n";
			$data .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
			$data .= 'Language:                 ' . get_locale() . "\n";
			$data .= 'User Language:            ' . get_user_locale() . "\n";
			$data .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
			$data .= 'Active Theme:             ' . $theme . "\n";
			$data .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

			// Only show page specs if front page is set to 'page'.
			if ( get_option( 'show_on_front' ) === 'page' ) {
				$front_page_id = get_option( 'page_on_front' );
				$blog_page_id  = get_option( 'page_for_posts' );

				$data .= 'Page On Front:            ' . ( $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
				$data .= 'Page For Posts:           ' . ( $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
			}
			$data .= 'ABSPATH:                  ' . ABSPATH . "\n";
            $data .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n"; //phpcs:ignore
			$data .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'WP_DEBUG_LOG:             ' . ( defined( 'WP_DEBUG_LOG' ) ? ( WP_DEBUG_LOG ? ( is_string( WP_DEBUG_LOG ) ? 'Enabled (Custom: ' . WP_DEBUG_LOG . ')' : 'Enabled' ) : 'Disabled' ) : 'Not set' ) . "\n";
			$data .= 'WP_DEBUG_DISPLAY:         ' . ( defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'SCRIPT_DEBUG:             ' . ( defined( 'SCRIPT_DEBUG' ) ? SCRIPT_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'CHARITABLE_DEBUG:         ' . ( defined( 'CHARITABLE_DEBUG' ) ? CHARITABLE_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'CHARITABLE_DEBUG_SECURITY: ' . ( defined( 'CHARITABLE_DEBUG_SECURITY' ) ? CHARITABLE_DEBUG_SECURITY ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
			$data .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";
			$data .= 'Revisions:                ' . ( WP_POST_REVISIONS ? WP_POST_REVISIONS > 1 ? 'Limited to ' . WP_POST_REVISIONS : 'Enabled' : 'Disabled' ) . "\n";

			return $data;
		}

		/**
		 * Get Uploads/Constants info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function uploads_info() {

			// @todo Charitable configuration/specific details.
			$data  = "\n" . '-- WordPress Uploads/Constants' . "\n\n";
			$data .= 'WP_CONTENT_DIR:           ' . ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR ? WP_CONTENT_DIR : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'WP_CONTENT_URL:           ' . ( defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL ? WP_CONTENT_URL : 'Disabled' : 'Not set' ) . "\n";
			$data .= 'UPLOADS:                  ' . ( defined( 'UPLOADS' ) ? UPLOADS ? UPLOADS : 'Disabled' : 'Not set' ) . "\n";

			$uploads_dir = wp_upload_dir();

			$data .= 'wp_uploads_dir() path:    ' . $uploads_dir['path'] . "\n";
			$data .= 'wp_uploads_dir() url:     ' . $uploads_dir['url'] . "\n";
			$data .= 'wp_uploads_dir() basedir: ' . $uploads_dir['basedir'] . "\n";
			$data .= 'wp_uploads_dir() baseurl: ' . $uploads_dir['baseurl'] . "\n";

			return $data;
		}

		/**
		 * Get Plugins info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function plugins_info() {

			// Get plugins that have an update.
			$data  = $this->mu_plugins();
			$data .= $this->installed_plugins();
			$data .= $this->multisite_plugins();

			return $data;
		}

		/**
		 * Get MU Plugins info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function mu_plugins() {

			$data = '';

			// Must-use plugins.
			// NOTE: MU plugins can't show updates!
			$muplugins = get_mu_plugins();

			if ( ! empty( $muplugins ) && count( $muplugins ) > 0 ) {
				$data = "\n" . '-- Must-Use Plugins' . "\n\n";

				foreach ( $muplugins as $plugin => $plugin_data ) {
					$data .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
				}
			}

			return $data;
		}

		/**
		 * Get Installed Plugins info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function installed_plugins() {

			$updates = function_exists( 'get_plugin_updates' ) ? get_plugin_updates() : array();

			// WordPress active plugins.
			$data = "\n" . '-- WordPress Active Plugins' . "\n\n";

			$plugins        = get_plugins();
			$active_plugins = get_option( 'active_plugins', [] );

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
					continue;
				}
				$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$data  .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			// WordPress inactive plugins.
			$data .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( in_array( $plugin_path, $active_plugins, true ) ) {
					continue;
				}
				$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$data  .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			return $data;
		}

		/**
		 * Get Multisite Plugins info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function multisite_plugins() {

			$data = '';

			if ( ! is_multisite() ) {
				return $data;
			}

			$updates = function_exists( 'get_plugin_updates' ) ? get_plugin_updates() : array();

			// WordPress Multisite active plugins.
			$data = "\n" . '-- Network Active Plugins' . "\n\n";

			$plugins        = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', [] );

			foreach ( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}
				$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$plugin = get_plugin_data( $plugin_path );
				$data  .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			return $data;
		}

		/**
		 * Get Server info.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		private function server_info() {

			global $wpdb;

			// Server configuration (really just versions).
			$data  = "\n" . '-- Webserver Configuration' . "\n\n";
			$data .= 'PHP Version:              ' . PHP_VERSION . "\n";
			$data .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
			$data .= 'Webserver Info:           ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '' ) . "\n";

			// PHP configs... now we're getting to the important stuff.
			$data .= "\n" . '-- PHP Configuration' . "\n\n";
			$data .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
			$data .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
			$data .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
			$data .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
			$data .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
			$data .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
			$data .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

			// PHP extensions and such.
			$data .= "\n" . '-- PHP Extensions' . "\n\n";
			$data .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
			$data .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
			$data .= 'SOAP Client:              ' . ( class_exists( 'SoapClient', false ) ? 'Installed' : 'Not Installed' ) . "\n";
			$data .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

			// Session stuff.
			$data .= "\n" . '-- Session Configuration' . "\n\n";
			$data .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

			// The rest of this is only relevant if session is enabled.
			if ( isset( $_SESSION ) ) {
				$data .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
				$data .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
				$data .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
				$data .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
				$data .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
			}

			return $data;
		}

		/**
		 * Get formatted datetime.
		 *
		 * @since 1.8.5
		 *
		 * @param int|string $date Date.
		 *
		 * @return string
		 */
		private function get_formatted_datetime( $date ) {

			return sprintf(
				'%1$s at %2$s (GMT)',
				gmdate( 'M j, Y', $date ),
				gmdate( 'g:ia', $date )
			);
		}

		/**
		 * Get email diagnostics summary for system info.
		 *
		 * @since 1.8.9.2
		 *
		 * @return string
		 */
		private function email_diagnostics() {
			try {
				$diagnostics = Charitable_Tools_Email_Diagnostics::get_instance();
				return $diagnostics->get_email_diagnostics_summary();
			} catch ( Exception $e ) {
				return "\n-- Email System Diagnostics\n\nError: Unable to run email diagnostics - " . $e->getMessage() . "\n";
			}
		}

		/**
		 * AJAX handler for detailed email diagnostics.
		 *
		 * @since 1.8.9.2
		 *
		 * @return void
		 */
		public function ajax_email_diagnostics() {
			// Check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'charitable_email_diagnostics' ) ) {
				wp_die( __( 'Security check failed', 'charitable' ) );
			}

			// Check capability
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'Insufficient permissions', 'charitable' ) );
			}

			try {
				$diagnostics = Charitable_Tools_Email_Diagnostics::get_instance();
				$results = $diagnostics->get_detailed_email_diagnostics();

				// Format for display
				$output = $this->format_detailed_diagnostics( $results );

				wp_send_json_success( array(
					'diagnostics' => $output,
					'raw_data' => $results
				) );

			} catch ( Exception $e ) {
				wp_send_json_error( array(
					'message' => 'Diagnostics failed: ' . $e->getMessage(),
					'error_details' => array(
						'file' => $e->getFile(),
						'line' => $e->getLine()
					)
				) );
			}
		}

		/**
		 * Format detailed diagnostics for display.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $results Raw diagnostic results.
		 * @return string Formatted diagnostic output.
		 */
		private function format_detailed_diagnostics( $results ) {
			if ( isset( $results['error'] ) ) {
				return "### DIAGNOSTIC ERROR ###\n\n" . $results['error']['message'] . "\n";
			}

			$output = "### DETAILED EMAIL DIAGNOSTICS ###\n\n";
			$output .= sprintf( "Generated: %s\n", $results['timestamp'] ?? 'Unknown' );
			$output .= sprintf( "Execution Time: %sms\n\n", $results['execution_time'] ?? '0' );

			// Health Score Section
			if ( isset( $results['health_score'] ) ) {
				$health = $results['health_score'];
				$output .= "=== OVERALL HEALTH SCORE ===\n";
				$output .= sprintf( "Score: %d/%d (%d%%) - %s\n",
					$health['total_score'], $health['max_score'], $health['percentage'], $health['grade'] );
				$output .= sprintf( "Assessment: %s\n\n", $health['interpretation'] );

				// Category breakdown
				$output .= "Category Scores:\n";
				foreach ( $health['category_scores'] as $category => $score ) {
					$output .= sprintf( "  %-20s: %2d/%-2d (%3d%%)\n",
						ucwords( str_replace( '_', ' ', $category ) ),
						$score['score'], $score['max_score'], $score['percentage'] );
				}
				$output .= "\n";
			}

			// Individual Test Results
			$test_sections = array(
				'email_registration' => 'EMAIL REGISTRATION TEST',
				'email_access' => 'EMAIL ACCESS TEST',
				'preview_functionality' => 'PREVIEW FUNCTIONALITY TEST',
				'hook_timing' => 'HOOK TIMING TEST',
				'environment_config' => 'ENVIRONMENT CONFIGURATION',
				'performance' => 'PERFORMANCE METRICS'
			);

			foreach ( $test_sections as $key => $title ) {
				if ( ! isset( $results[ $key ] ) ) {
					continue;
				}

				$test = $results[ $key ];
				$output .= "=== {$title} ===\n";
				$output .= sprintf( "Status: %s (Score: %d/%d)\n",
					strtoupper( $test['status'] ), $test['score'], $test['max_score'] );

				// Test-specific details
				switch ( $key ) {
					case 'email_registration':
						$this->format_registration_details( $test, $output );
						break;
					case 'email_access':
						$this->format_access_details( $test, $output );
						break;
					case 'preview_functionality':
						$this->format_preview_details( $test, $output );
						break;
					case 'hook_timing':
						$this->format_timing_details( $test, $output );
						break;
					case 'environment_config':
						$this->format_environment_details( $test, $output );
						break;
					case 'performance':
						$this->format_performance_details( $test, $output );
						break;
				}

				$output .= "\n";
			}

			$output .= "### END DETAILED DIAGNOSTICS ###";
			return $output;
		}

		/**
		 * Format registration test details.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $test Test results.
		 * @param string &$output Output string to append to.
		 */
		private function format_registration_details( $test, &$output ) {
			$details = $test['details'];

			$output .= sprintf( "Emails Registered: %d\n", count( $test['registered_emails'] ?? array() ) );
			$output .= sprintf( "Core Emails Present: %d/%d\n",
				$details['core_emails_present'] ?? 0, $details['core_emails_total'] ?? 3 );
			$output .= sprintf( "Registration Time: %sms\n",
				$test['timing']['duration_ms'] ?? '0' );

			if ( isset( $details['helper_accessible'] ) && ! $details['helper_accessible'] ) {
				$output .= "⚠ Helper Access: FAILED\n";
				if ( isset( $details['helper_error'] ) ) {
					$output .= "  Error: " . $details['helper_error'] . "\n";
				}
			}

			if ( ! empty( $test['registered_emails'] ) ) {
				$output .= "Registered Email Classes:\n";
				foreach ( $test['registered_emails'] as $id => $class ) {
					$output .= "  {$id}: {$class}\n";
				}
			}
		}

		/**
		 * Format access test details.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $test Test results.
		 * @param string &$output Output string to append to.
		 */
		private function format_access_details( $test, &$output ) {
			$details = $test['details'];

			$output .= sprintf( "Successful Access: %d/%d\n",
				$details['successful_accesses'] ?? 0, $details['total_emails_tested'] ?? 3 );
			$output .= sprintf( "Average Access Time: %.1fms\n", $test['average_time'] ?? 0 );

			if ( isset( $test['email_tests'] ) ) {
				$output .= "Individual Email Tests:\n";
				foreach ( $test['email_tests'] as $email_id => $email_test ) {
					$status = $email_test['accessible'] ? '✓' : '✗';
					$output .= sprintf( "  %s %-25s: %s (%.1fms)\n",
						$status, $email_id,
						$email_test['accessible'] ? 'OK' : 'FAILED',
						$email_test['timing_ms'] );

					if ( ! $email_test['accessible'] && isset( $email_test['access_error'] ) ) {
						$output .= "    Error: " . $email_test['access_error'] . "\n";
					}
				}
			}
		}

		/**
		 * Format preview test details.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $test Test results.
		 * @param string &$output Output string to append to.
		 */
		private function format_preview_details( $test, &$output ) {
			$output .= sprintf( "Functional Previews: %d/%d\n",
				$test['functional_count'] ?? 0, $test['details']['total_tested'] ?? 3 );

			if ( isset( $test['preview_tests'] ) ) {
				$output .= "Preview URL Tests:\n";
				foreach ( $test['preview_tests'] as $email_id => $preview_test ) {
					$status = $preview_test['preview_works'] ? '✓' : '✗';
					$output .= sprintf( "  %s %-25s: %s",
						$status, $email_id,
						$preview_test['preview_works'] ? 'Working' : 'Failed' );

					if ( isset( $preview_test['status_code'] ) ) {
						$output .= sprintf( " (HTTP %d)", $preview_test['status_code'] );
					}
					$output .= "\n";

					if ( ! $preview_test['preview_works'] && isset( $preview_test['endpoint_error'] ) ) {
						$output .= "    Error: " . $preview_test['endpoint_error'] . "\n";
					}
				}

				// If all failures are the local SSL certificate error, add a short explanation.
				$ssl_error_phrase = 'unable to get local issuer certificate';
				$has_ssl_error = false;
				$any_failure = false;
				foreach ( $test['preview_tests'] as $preview_test ) {
					if ( ! $preview_test['preview_works'] ) {
						$any_failure = true;
						$err = isset( $preview_test['endpoint_error'] ) ? $preview_test['endpoint_error'] : '';
						if ( strpos( $err, $ssl_error_phrase ) !== false || strpos( $err, 'cURL error 60' ) !== false ) {
							$has_ssl_error = true;
							break;
						}
					}
				}
				if ( $any_failure && $has_ssl_error ) {
					$output .= "\n  Note: This is an SSL verification error. The server's PHP/cURL cannot verify your site's HTTPS certificate (common with local/dev or self-signed certs). It is not a Charitable bug; preview links may still work in the browser.\n";
				}
			}
		}

		/**
		 * Format timing test details.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $test Test results.
		 * @param string &$output Output string to append to.
		 */
		private function format_timing_details( $test, &$output ) {
			$details = $test['details'];

			$output .= sprintf( "Init Completed: %s\n", $details['init_completed'] ? 'Yes' : 'No' );
			$output .= sprintf( "Admin Init Completed: %s\n", $details['admin_init_completed'] ? 'Yes' : 'No' );
			$output .= sprintf( "Current Action: %s\n", $details['current_action'] ?? 'none' );

			if ( isset( $details['charitable_plugin_position'] ) ) {
				$output .= sprintf( "Plugin Load Position: %d/%d\n",
					$details['charitable_plugin_position'] + 1, $details['total_active_plugins'] );
			}

			if ( ! empty( $details['timing_issues'] ) ) {
				$output .= "Timing Issues:\n";
				foreach ( $details['timing_issues'] as $issue ) {
					$output .= "  ⚠ {$issue}\n";
				}
			}
		}

		/**
		 * Format environment test details.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $test Test results.
		 * @param string &$output Output string to append to.
		 */
		private function format_environment_details( $test, &$output ) {
			$env = $test['environment_info'];

			$output .= sprintf( "PHP Version: %s\n", $env['php_version'] );
			$output .= sprintf( "WordPress Version: %s\n", $env['wordpress_version'] );
			$output .= sprintf( "Charitable Version: %s\n", $env['charitable_version'] );
			$output .= sprintf( "Memory Limit: %s\n", $env['memory_limit'] );
			$output .= sprintf( "Debug Mode: %s\n", $env['debug_enabled'] ? 'Enabled' : 'Disabled' );

			if ( ! empty( $test['caching_plugins'] ) ) {
				$output .= "Caching Plugins: " . implode( ', ', $test['caching_plugins'] ) . "\n";
			}

			$output .= sprintf( "Object Cache: %s\n",
				$test['details']['object_cache_enabled'] ? 'Enabled' : 'Disabled' );
			$output .= sprintf( "OPcache: %s\n",
				$test['details']['opcache_enabled'] ? 'Enabled' : 'Disabled' );
			$output .= sprintf( "SMTP Configured: %s\n",
				$test['details']['smtp_configured'] ? 'Yes' : 'No' );
		}

		/**
		 * Format performance test details.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $test Test results.
		 * @param string &$output Output string to append to.
		 */
		private function format_performance_details( $test, &$output ) {
			$metrics = $test['metrics'];

			$output .= sprintf( "Helper Access Time: %.2fms\n", $metrics['helper_access_time_ms'] );
			$output .= sprintf( "Registration Time: %.2fms\n", $metrics['registration_time_ms'] );
			$output .= sprintf( "Total Test Time: %.2fms\n", $metrics['total_time_ms'] );
			$output .= sprintf( "Memory Used: %.1fKB\n", $metrics['memory_used_kb'] );
			$output .= sprintf( "Peak Memory: %.1fMB\n", $metrics['peak_memory_mb'] );

			if ( isset( $test['details']['slow_helper'] ) ) {
				$output .= "⚠ " . $test['details']['slow_helper'] . "\n";
			}
			if ( isset( $test['details']['slow_registration'] ) ) {
				$output .= "⚠ " . $test['details']['slow_registration'] . "\n";
			}
			if ( isset( $test['details']['high_memory'] ) ) {
				$output .= "⚠ " . $test['details']['high_memory'] . "\n";
			}
		}

		/**
		 * AJAX handler for sending test email.
		 *
		 * @since 1.8.9.2
		 *
		 * @return void
		 */
		public function ajax_send_test_email() {
			// Check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'charitable_send_test_email' ) ) {
				wp_die( __( 'Security check failed', 'charitable' ) );
			}

			// Check capability
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'Insufficient permissions', 'charitable' ) );
			}

			try {
				// Load the test email class if not already loaded
				if ( ! class_exists( 'Charitable_Email_Test' ) ) {
					require_once charitable()->get_path( 'includes' ) . 'emails/class-charitable-email-test.php';
				}

				// Send test email
				$result = Charitable_Email_Test::send_test_email();

				if ( $result['success'] ) {
					wp_send_json_success( array(
						'message' => $result['message'],
						'success' => true,
					) );
				} else {
					wp_send_json_error( array(
						'message' => $result['message'],
						'success' => false,
					) );
				}

			} catch ( Exception $e ) {
				wp_send_json_error( array(
					'message' => sprintf(
						__( 'Test email failed: %s', 'charitable' ),
						$e->getMessage()
					),
					'success' => false,
					'error_details' => array(
						'file' => $e->getFile(),
						'line' => $e->getLine(),
					),
				) );
			}
		}

		/**
		 * Get donation form error logs.
		 *
		 * @since  1.8.9.2
		 *
		 * @return string
		 */
		private function donation_error_logs() {
			$data = "\n\n" . '-- Donation Form Error Logs' . "\n\n";

			// Check if error logging is enabled
			if ( ! apply_filters( 'charitable_form_error_logging_enabled', true ) ) {
				$data .= 'Error logging is disabled.' . "\n";
				return $data;
			}

			// Get recent errors (last 20)
			$recent_errors = charitable_get_recent_form_errors( 20 );

			if ( empty( $recent_errors ) ) {
				$data .= 'No donation form errors found in the last 7 days.' . "\n";
				return $data;
			}

			$data .= 'Recent Errors (last 20):' . "\n";
			$data .= str_repeat( '-', 40 ) . "\n";

			foreach ( $recent_errors as $error ) {
				$data .= sprintf(
					'[%s] %s - %s',
					$error->date_recorded,
					$error->primary_action,
					$error->secondary_action
				);

				// Add context if available
				if ( $error->campaign_id ) {
					$campaign_title = get_the_title( $error->campaign_id );
					$data .= sprintf( ' | Campaign: %s (#%d)', $campaign_title, $error->campaign_id );
				}

				if ( $error->amount ) {
					$data .= sprintf( ' | Amount: %s', charitable_format_money( $error->amount ) );
				}

				if ( ! empty( $error->meta_data ) && isset( $error->meta_data['gateway'] ) ) {
					$data .= sprintf( ' | Gateway: %s', $error->meta_data['gateway'] );
				}

				// Show error message detail (SMTP errors, exception messages, etc.)
				if ( ! empty( $error->meta_data ) && isset( $error->meta_data['error_message'] ) ) {
					$data .= "\n  → " . $error->meta_data['error_message'];
				}

				// Show email class for email failures
				if ( ! empty( $error->meta_data ) && isset( $error->meta_data['email_class'] ) ) {
					$data .= ' [' . $error->meta_data['email_class'] . ']';
				}

				$data .= "\n";
			}

			// Get error statistics
			$error_stats = charitable_get_form_error_stats( 7 );

			if ( ! empty( $error_stats ) ) {
				$data .= "\n" . 'Error Summary (last 7 days):' . "\n";
				$data .= str_repeat( '-', 40 ) . "\n";

				foreach ( $error_stats as $stat ) {
					$data .= sprintf(
						'%s - %s: %d occurrences (last: %s)' . "\n",
						$stat->primary_action,
						$stat->secondary_action,
						$stat->error_count,
						$stat->last_occurrence
					);
				}
			}

			return $data;
		}

		/**
		 * Get debug log scanner summary for system info.
		 *
		 * @since 1.8.9.2
		 *
		 * @return string
		 */
		private function debug_log_scanner() {
			try {
				$scanner = Charitable_Tools_Debug_Log_Scanner::get_instance();
				return $scanner->get_debug_log_scanner_summary();
			} catch ( Exception $e ) {
				return "\n-- Debug Log Scanner\n\nError: Unable to run debug log scanner - " . $e->getMessage() . "\n";
			}
		}

		/**
		 * Clear error logs via AJAX.
		 *
		 * @since  1.8.9.2
		 *
		 * @return void
		 */
		public function ajax_clear_error_logs() {
			// Check nonce first (distinct message for debugging).
			if ( ! check_ajax_referer( 'charitable-admin-tools', 'nonce', false ) ) {
				wp_send_json_error( array(
					'message' => __( 'Security check failed: invalid or expired nonce. Refresh the page and try again.', 'charitable' ),
					'success' => false,
				) );
				return;
			}
			// Check capability.
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array(
					'message' => __( 'You do not have the manage_charitable_settings capability.', 'charitable' ),
					'success' => false,
				) );
				return;
			}

			try {
				// Clear error logs (delete all)
				$deleted_count = charitable_cleanup_old_form_errors( 0 ); // 0 days = delete all

				wp_send_json_success( array(
					'message' => sprintf(
						/* translators: %d: number of deleted error logs */
						_n(
							'%d error log cleared successfully.',
							'%d error logs cleared successfully.',
							$deleted_count,
							'charitable'
						),
						$deleted_count
					),
					'success' => true,
					'deleted_count' => $deleted_count,
				) );

			} catch ( Exception $e ) {
				wp_send_json_error( array(
					'message' => sprintf(
						__( 'Error clearing logs: %s', 'charitable' ),
						$e->getMessage()
					),
					'success' => false,
				) );
			}
		}

		/**
		 * Export error logs via AJAX.
		 *
		 * @since  1.8.9.2
		 *
		 * @return void
		 */
		public function ajax_export_error_logs() {
			// Check nonce first (distinct message for debugging).
			if ( ! check_ajax_referer( 'charitable-admin-tools', 'nonce', false ) ) {
				wp_send_json_error( array(
					'message' => __( 'Security check failed: invalid or expired nonce. Refresh the page and try again.', 'charitable' ),
					'success' => false,
				) );
				return;
			}
			// Check capability.
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array(
					'message' => __( 'You do not have the manage_charitable_settings capability.', 'charitable' ),
					'success' => false,
				) );
				return;
			}

			try {
				// Get all error logs
				$errors = charitable_get_recent_form_errors( 1000 ); // Get up to 1000 errors

				$csv_data = array();
				$csv_data[] = array( 'Date', 'Error Type', 'Error Details', 'Campaign ID', 'Campaign Title', 'Amount', 'Gateway', 'User ID' );

				foreach ( $errors as $error ) {
					$campaign_title = $error->campaign_id ? get_the_title( $error->campaign_id ) : '';
					$gateway = ! empty( $error->meta_data ) && isset( $error->meta_data['gateway'] ) ? $error->meta_data['gateway'] : '';

					$csv_data[] = array(
						$error->date_recorded,
						$error->primary_action,
						$error->secondary_action,
						$error->campaign_id ?: '',
						$campaign_title,
						$error->amount ? charitable_format_money( $error->amount ) : '',
						$gateway,
						$error->created_by ?: '',
					);
				}

				// Create CSV content
				$csv_content = '';
				foreach ( $csv_data as $row ) {
					$csv_content .= '"' . implode( '","', $row ) . '"' . "\n";
				}

				// Prepare download data
				$filename = 'charitable-error-logs-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

				wp_send_json_success( array(
					'message' => __( 'Error logs exported successfully.', 'charitable' ),
					'success' => true,
					'filename' => $filename,
					'content' => base64_encode( $csv_content ),
					'mimetype' => 'text/csv',
				) );

			} catch ( Exception $e ) {
				wp_send_json_error( array(
					'message' => sprintf(
						__( 'Error exporting logs: %s', 'charitable' ),
						$e->getMessage()
					),
					'success' => false,
				) );
			}
		}

		/**
		 * AJAX handler for debug log scanner.
		 *
		 * @since 1.8.9.2
		 *
		 * @return void
		 */
		public function ajax_debug_log_scan() {
			// Check nonce
			if ( ! wp_verify_nonce( $_POST['nonce'], 'charitable-admin-tools' ) ) {
				wp_die( __( 'Security check failed', 'charitable' ) );
			}

			// Check capability
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_die( __( 'Insufficient permissions', 'charitable' ) );
			}

			try {
				$scanner = Charitable_Tools_Debug_Log_Scanner::get_instance();
				$results = $scanner->get_detailed_debug_log_scan();

				// Format for display
				$output = $this->format_debug_log_scan( $results );

				wp_send_json_success( array(
					'scan_results' => $output,
					'raw_data' => $results
				) );

			} catch ( Exception $e ) {
				// Enhanced error debugging
				$debug_info = array();

				// Get debug logging status
				$debug_info['wp_debug'] = defined( 'WP_DEBUG' ) ? ( WP_DEBUG ? 'enabled' : 'disabled' ) : 'not defined';
				$debug_info['wp_debug_log'] = defined( 'WP_DEBUG_LOG' ) ? ( WP_DEBUG_LOG ? 'enabled' : 'disabled' ) : 'not defined';

				// Get log file path
				try {
					$reflection = new ReflectionClass( $scanner );
					$method = $reflection->getMethod( 'get_debug_log_path' );
					$method->setAccessible( true );
					$log_file = $method->invoke( $scanner );
					$debug_info['log_file_path'] = $log_file;
					$debug_info['log_file_exists'] = $log_file ? file_exists( $log_file ) : false;
					if ( $log_file && file_exists( $log_file ) ) {
						$debug_info['log_file_size'] = round( filesize( $log_file ) / 1024, 2 ) . ' KB';
						$debug_info['log_file_readable'] = is_readable( $log_file );
					}
				} catch ( Exception $path_error ) {
					$debug_info['log_path_error'] = $path_error->getMessage();
				}

				// Get memory info
				$debug_info['php_memory_limit'] = ini_get( 'memory_limit' );
				$debug_info['current_memory_usage'] = round( memory_get_usage( true ) / 1024 / 1024, 2 ) . ' MB';

				wp_send_json_error( array(
					'message' => 'Debug log scan failed: ' . $e->getMessage(),
					'detailed_message' => $this->format_debug_error_message( $e, $debug_info ),
					'error_details' => array(
						'file' => $e->getFile(),
						'line' => $e->getLine(),
						'debug_info' => $debug_info
					)
				) );
			}
		}

		/**
		 * Format debug log scan results for display.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $results Raw scan results.
		 * @return array
		 */
		private function format_debug_log_scan( $results ) {
			if ( isset( $results['error'] ) ) {
				return array(
					'summary' => "### DEBUG LOG SCAN ERROR ###\n\n" . $results['error']['message'] . "\n",
					'technical' => $results['error']['message'],
					'forum_ready' => "Debug log scan encountered an error. Please check if WP_DEBUG and WP_DEBUG_LOG are properly configured."
				);
			}

			if ( ! isset( $results['pre_flight'] ) || ! $results['pre_flight']['passed'] ) {
				$messages = isset( $results['pre_flight']['messages'] ) ? implode( "\n", $results['pre_flight']['messages'] ) : 'Unknown pre-flight error';
				return array(
					'summary' => "### DEBUG LOG SCAN UNAVAILABLE ###\n\n" . $messages . "\n",
					'technical' => $messages,
					'forum_ready' => "Debug log scanning is not available. " . $messages
				);
			}

			$scanner = Charitable_Tools_Debug_Log_Scanner::get_instance();
			$sanitized_errors = $scanner->sanitize_errors_for_sharing( $results['errors'] );

			// Generate summary format
			$summary = "### DEBUG LOG SCAN RESULTS ###\n\n";
			$summary .= sprintf( "Generated: %s\n", $results['timestamp'] ?? 'Unknown' );
			$summary .= sprintf( "Execution Time: %sms\n", $results['execution_time'] ?? '0' );
			$summary .= sprintf( "Scan Timeframe: %d hours\n\n", $results['scan_timeframe'] ?? 24 );

			$summary .= "=== SUMMARY ===\n";
			$summary .= sprintf( "Total Errors Found: %d\n", $results['statistics']['total_errors'] );

			if ( ! empty( $results['statistics']['by_category'] ) ) {
				$summary .= "\nErrors by Category:\n";
				foreach ( $results['statistics']['by_category'] as $category => $count ) {
					$summary .= sprintf( "  %s: %d\n", ucfirst( str_replace( '_', ' ', $category ) ), $count );
				}
			}

			if ( ! empty( $results['statistics']['by_priority'] ) ) {
				$summary .= "\nErrors by Priority:\n";
				foreach ( $results['statistics']['by_priority'] as $priority => $count ) {
					$summary .= sprintf( "  %s: %d\n", $priority, $count );
				}
			}

			if ( ! empty( $results['recommendations'] ) ) {
				$summary .= "\nRecommendations:\n";
				foreach ( $results['recommendations'] as $recommendation ) {
					$summary .= "  " . $recommendation . "\n";
				}
			}

			// Generate technical details
			$technical = $summary . "\n=== DETAILED ERROR LOG ===\n\n";
			if ( ! empty( $sanitized_errors ) ) {
				foreach ( $sanitized_errors as $error ) {
					$technical .= sprintf( "[%s] %s - %s\n", $error['datetime'], $error['level'], $error['category'] );
					$technical .= sprintf( "Priority: %d (%s)\n", $error['priority'], $error['description'] );
					$technical .= sprintf( "Message: %s\n", $error['message'] );
					if ( ! empty( $error['file'] ) ) {
						$technical .= sprintf( "File: %s", $error['file'] );
						if ( ! empty( $error['line'] ) ) {
							$technical .= sprintf( " (line %s)", $error['line'] );
						}
						$technical .= "\n";
					}
					$technical .= "\n" . str_repeat( '-', 50 ) . "\n\n";
				}
			} else {
				$technical .= "No errors found in the specified timeframe.\n";
			}

			// Generate forum-ready format
			$forum_ready = "### Charitable Debug Log Scan Results ###\n\n";
			$forum_ready .= sprintf( "**WordPress Version:** %s\n", get_bloginfo( 'version' ) );
			$forum_ready .= sprintf( "**PHP Version:** %s\n", PHP_VERSION );
			$forum_ready .= sprintf( "**Charitable Version:** %s\n\n", charitable()->get_version() );

			$forum_ready .= sprintf( "**Scan Summary:** %d errors found in last %d hours\n\n",
				$results['statistics']['total_errors'],
				$results['scan_timeframe'] ?? 24
			);

			if ( ! empty( $results['statistics']['by_category'] ) ) {
				$forum_ready .= "**Error Categories:**\n";
				foreach ( $results['statistics']['by_category'] as $category => $count ) {
					$forum_ready .= sprintf( "- %s: %d\n", ucfirst( str_replace( '_', ' ', $category ) ), $count );
				}
				$forum_ready .= "\n";
			}

			if ( ! empty( $sanitized_errors ) ) {
				$forum_ready .= "**Recent Errors (Sanitized):**\n\n";
				$error_count = 0;
				foreach ( $sanitized_errors as $error ) {
					if ( $error_count >= 5 ) { // Limit to top 5 errors for forum sharing
						$forum_ready .= sprintf( "... and %d more errors (see technical details)\n\n", count( $sanitized_errors ) - 5 );
						break;
					}
					$forum_ready .= sprintf( "**Error %d:** [%s] %s\n", $error_count + 1, $error['datetime'], $error['level'] );
					$forum_ready .= sprintf( "Category: %s (Priority: %d)\n", $error['category'], $error['priority'] );
					$forum_ready .= sprintf( "Message: %s\n", $error['message'] );
					if ( ! empty( $error['file'] ) ) {
						$forum_ready .= sprintf( "File: %s", $error['file'] );
						if ( ! empty( $error['line'] ) ) {
							$forum_ready .= sprintf( " (line %s)", $error['line'] );
						}
						$forum_ready .= "\n";
					}
					$forum_ready .= "\n";
					$error_count++;
				}
			}

			if ( ! empty( $results['recommendations'] ) ) {
				$forum_ready .= "**Recommendations:**\n";
				foreach ( $results['recommendations'] as $recommendation ) {
					$forum_ready .= "- " . $recommendation . "\n";
				}
			}

			return array(
				'summary' => $summary,
				'technical' => $technical,
				'forum_ready' => $forum_ready
			);
		}

		/**
		 * Format detailed debug error message.
		 *
		 * @since 1.8.9.2
		 *
		 * @param Exception $exception The exception that occurred.
		 * @param array     $debug_info Debug information array.
		 * @return string
		 */
		private function format_debug_error_message( $exception, $debug_info ) {
			$message = "Debug Log Scanner Error Details:\n\n";

			$message .= "Error: " . $exception->getMessage() . "\n";
			$message .= "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n\n";

			$message .= "WordPress Debug Configuration:\n";
			$message .= "- WP_DEBUG: " . $debug_info['wp_debug'] . "\n";
			$message .= "- WP_DEBUG_LOG: " . $debug_info['wp_debug_log'] . "\n\n";

			if ( isset( $debug_info['log_file_path'] ) ) {
				$message .= "Log File Information:\n";
				$message .= "- Path: " . $debug_info['log_file_path'] . "\n";
				$message .= "- Exists: " . ( $debug_info['log_file_exists'] ? 'Yes' : 'No' ) . "\n";
				if ( isset( $debug_info['log_file_size'] ) ) {
					$message .= "- Size: " . $debug_info['log_file_size'] . "\n";
					$message .= "- Readable: " . ( $debug_info['log_file_readable'] ? 'Yes' : 'No' ) . "\n";
				}
				$message .= "\n";
			}

			$message .= "System Information:\n";
			$message .= "- PHP Memory Limit: " . $debug_info['php_memory_limit'] . "\n";
			$message .= "- Current Memory Usage: " . $debug_info['current_memory_usage'] . "\n\n";

			// Provide helpful suggestions
			$message .= "Troubleshooting Suggestions:\n";

			if ( $debug_info['wp_debug'] !== 'enabled' || $debug_info['wp_debug_log'] !== 'enabled' ) {
				$message .= "1. Enable debug logging in wp-config.php:\n";
				$message .= "   define('WP_DEBUG', true);\n";
				$message .= "   define('WP_DEBUG_LOG', true);\n\n";
			}

			if ( isset( $debug_info['log_file_exists'] ) && ! $debug_info['log_file_exists'] ) {
				$message .= "2. No debug.log file found - this is normal if no errors have occurred.\n";
				$message .= "   Try creating a test error or reproduce an issue first.\n\n";
			}

			if ( isset( $debug_info['log_file_readable'] ) && ! $debug_info['log_file_readable'] ) {
				$message .= "3. Debug log file is not readable - check file permissions.\n\n";
			}

			$message .= "4. If the issue persists, copy this detailed error message when reporting the issue.";

			return $message;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.8.1.6
		 *
		 * @return  Charitable_Tools_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
