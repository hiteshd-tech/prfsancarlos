<?php
/**
 * Displays Charitable promotional banners on non-Charitable admin screens.
 *
 * @package   Charitable/Classes/Charitable_External_Banner
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_External_Banner' ) ) :

	/**
	 * Charitable_External_Banner
	 *
	 * Renders Charitable banners on third-party plugin admin screens
	 * when conditions are met (e.g., GiveWP is active).
	 *
	 * @since 1.8.10
	 */
	class Charitable_External_Banner {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_External_Banner|null
		 */
		private static $instance = null;

		/**
		 * Option key for storing dismissed banner IDs.
		 *
		 * @var string
		 */
		const DISMISSED_OPTION = 'charitable_external_banners_dismissed';

		/**
		 * Create class object.
		 *
		 * @since 1.8.10
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'maybe_render_banner' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
			add_action( 'wp_ajax_charitable_dismiss_external_banner', array( $this, 'ajax_dismiss_banner' ) );
			add_action( 'charitable_after_clear_expired_options', array( $this, 'reset_dismissed_banners' ) );
		}

		/**
		 * Get the banner configurations for external screens.
		 *
		 * Each banner config includes:
		 * - 'id'        (string) Unique banner identifier.
		 * - 'screens'   (array)  WordPress screen IDs where the banner should appear.
		 * - 'condition'  (callable) Optional. Must return true for the banner to display.
		 * - 'messages'  (array)  Screen-specific messages. Keys are screen IDs, 'default' is fallback.
		 * - 'button_url' (string) The CTA link URL.
		 * - 'button_text' (string) The CTA link text.
		 *
		 * @since 1.8.10
		 *
		 * @return array
		 */
		private function get_banner_configs() {
			$configs = array();

			// GiveWP banner — show on GiveWP admin screens when GiveWP is active.
			if ( $this->is_givewp_active() ) {
				$counts = $this->get_givewp_counts();

				$configs[] = array(
					'id'          => 'givewp_import',
					'screens'     => array(
						'give_forms_page_give-campaigns',
						'give_forms_page_give-payment-history',
						'give_forms_page_give-donors',
						'give_forms_page_give-reports',
						'give_forms_page_give-tools',
						'give_forms_page_give-settings',
					),
					'messages'    => $this->get_givewp_messages( $counts ),
					'button_url'  => admin_url( 'admin.php?page=charitable-tools&tab=import' ),
					'button_text' => __( 'Import Now', 'charitable' ),
				);
			}

			/**
			 * Filter the external banner configurations.
			 *
			 * @since 1.8.10
			 *
			 * @param array $configs Banner configurations.
			 */
			return apply_filters( 'charitable_external_banner_configs', $configs );
		}

		/**
		 * Maybe render a banner on the current screen.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function maybe_render_banner() {
			$screen = get_current_screen();

			if ( is_null( $screen ) ) {
				return;
			}

			$dismissed = get_option( self::DISMISSED_OPTION, array() );
			$configs   = $this->get_banner_configs();

			foreach ( $configs as $config ) {
				// Skip if not on a target screen.
				if ( ! in_array( $screen->id, $config['screens'], true ) ) {
					continue;
				}

				// Skip if dismissed.
				if ( isset( $dismissed[ $config['id'] ] ) ) {
					continue;
				}

				// Get the screen-specific or default message.
				$message = isset( $config['messages'][ $screen->id ] )
					? $config['messages'][ $screen->id ]
					: $config['messages']['default'];

				$this->render_banner( $config, $message );

				// Only render one banner per page.
				break;
			}
		}

		/**
		 * Render a single external banner.
		 *
		 * @since 1.8.10
		 *
		 * @param array  $config  The banner configuration.
		 * @param string $message The message to display.
		 *
		 * @return void
		 */
		private function render_banner( $config, $message ) {
			$nonce = wp_create_nonce( 'charitable_external_banner_dismiss' );

			printf(
				'<div class="charitable-external-banner" data-banner-id="%s" data-nonce="%s" style="display:none;">
					<div class="charitable-external-banner-content">
						<span class="charitable-external-banner-icon">
							<img src="%s" alt="%s" width="20" height="20" />
						</span>
						<span class="charitable-external-banner-message">%s</span>
						<a href="%s" class="charitable-external-banner-cta">%s &rarr;</a>
					</div>
					<button type="button" class="charitable-external-banner-dismiss" aria-label="%s">&times;</button>
				</div>',
				esc_attr( $config['id'] ),
				esc_attr( $nonce ),
				esc_url( charitable()->get_path( 'assets', false ) . 'images/charitable-logo.svg' ),
				esc_attr__( 'Charitable', 'charitable' ),
				wp_kses_post( $message ),
				esc_url( $config['button_url'] ),
				esc_html( $config['button_text'] ),
				esc_attr__( 'Dismiss this notice', 'charitable' )
			);
		}

		/**
		 * Enqueue banner assets on target screens.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function maybe_enqueue_assets() {
			$screen = get_current_screen();

			if ( is_null( $screen ) ) {
				return;
			}

			$dismissed = get_option( self::DISMISSED_OPTION, array() );
			$configs   = $this->get_banner_configs();

			foreach ( $configs as $config ) {
				if ( in_array( $screen->id, $config['screens'], true ) && ! isset( $dismissed[ $config['id'] ] ) ) {
					$this->enqueue_assets();
					return;
				}
			}
		}

		/**
		 * Enqueue the banner JS and inline CSS.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		private function enqueue_assets() {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script(
				'charitable-external-banner',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-external-banner' . $suffix . '.js',
				array( 'jquery' ),
				charitable()->get_version(),
				true
			);

			wp_localize_script(
				'charitable-external-banner',
				'charitable_external_banner',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
			);

			// Inline CSS so we don't need a separate CSS file for this.
			wp_add_inline_style( 'wp-admin', $this->get_inline_css() );
		}

		/**
		 * Get the inline CSS for the banner.
		 *
		 * @since 1.8.10
		 *
		 * @return string
		 */
		private function get_inline_css() {
			return '
				.charitable-external-banner {
					background: #E89940;
					color: #fff;
					padding: 11px 40px 11px 20px;
					font-size: 13px;
					line-height: 1.5;
					position: relative;
					margin-left: -20px;
					margin-right: 0;
				}
				.charitable-external-banner-content {
					display: flex;
					align-items: center;
					justify-content: center;
					gap: 8px;
					flex-wrap: wrap;
				}
				.charitable-external-banner-icon {
					display: inline-flex;
					align-items: center;
					flex-shrink: 0;
				}
				.charitable-external-banner-icon img {
					display: block;
				}
				.charitable-external-banner-message {
					color: #fff;
				}
				.charitable-external-banner-message strong {
					font-weight: 700;
				}
				.charitable-external-banner-cta {
					color: #fff !important;
					font-weight: 700;
					text-decoration: underline;
					white-space: nowrap;
				}
				.charitable-external-banner-cta:hover {
					color: #fff !important;
					opacity: 0.85;
				}
				.charitable-external-banner-dismiss {
					position: absolute;
					top: 50%;
					right: 10px;
					transform: translateY(-50%);
					background: rgba(0, 0, 0, 0.15);
					border: none;
					border-radius: 50%;
					color: #fff;
					font-size: 18px;
					font-weight: 700;
					cursor: pointer;
					width: 24px;
					height: 24px;
					padding: 0;
					line-height: 22px;
					text-align: center;
					opacity: 1;
				}
				.charitable-external-banner-dismiss:hover {
					background: rgba(0, 0, 0, 0.3);
				}
				@media screen and (max-width: 782px) {
					.charitable-external-banner {
						padding: 10px 36px 10px 12px;
					}
					.charitable-external-banner-content {
						justify-content: flex-start;
					}
				}
			';
		}

		/**
		 * AJAX handler for dismissing an external banner.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function ajax_dismiss_banner() {
			check_ajax_referer( 'charitable_external_banner_dismiss', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Insufficient permissions.' );
			}

			$banner_id = isset( $_POST['banner_id'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_id'] ) ) : '';

			if ( empty( $banner_id ) ) {
				wp_send_json_error( 'Missing banner ID.' );
			}

			$dismissed = get_option( self::DISMISSED_OPTION, array() );
			$dismissed[ $banner_id ] = time();
			update_option( self::DISMISSED_OPTION, $dismissed );

			wp_send_json_success();
		}

		/**
		 * Reset dismissed banners when Charitable cache is cleared.
		 *
		 * Hooked to 'charitable_after_clear_expired_options'.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function reset_dismissed_banners() {
			delete_option( self::DISMISSED_OPTION );
		}

		/**
		 * Check if GiveWP is active.
		 *
		 * @since 1.8.10
		 *
		 * @return bool
		 */
		private function is_givewp_active() {
			return defined( 'GIVE_VERSION' ) || class_exists( 'Give' );
		}

		/**
		 * Get GiveWP data counts for dynamic messaging.
		 *
		 * @since 1.8.10
		 *
		 * @return array {
		 *     @type int $campaigns Number of GiveWP campaigns.
		 *     @type int $forms     Number of GiveWP donation forms.
		 *     @type int $donations Number of GiveWP donations.
		 *     @type int $donors    Number of GiveWP donors.
		 * }
		 */
		private function get_givewp_counts() {
			$cached = get_transient( 'charitable_givewp_counts' );

			if ( false !== $cached ) {
				return $cached;
			}

			global $wpdb;

			$counts = array(
				'campaigns' => 0,
				'forms'     => 0,
				'donations' => 0,
				'donors'    => 0,
			);

			// Count forms (give_forms post type).
			$counts['forms'] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'draft', 'private')", 'give_forms' )
			);

			// Count campaigns (give_campaigns custom table, if it exists).
			$campaigns_table = $wpdb->prefix . 'give_campaigns';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $campaigns_table ) ) === $campaigns_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$counts['campaigns'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$campaigns_table} WHERE %d = %d", 1, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Use the higher of campaigns or forms for "campaigns" messaging.
			$counts['campaigns'] = max( $counts['campaigns'], $counts['forms'] );

			// Count donations (give_payment post type).
			$counts['donations'] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status != %s", 'give_payment', 'trash' )
			);

			// Count donors (give_donors custom table).
			$donors_table = $wpdb->prefix . 'give_donors';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $donors_table ) ) === $donors_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$counts['donors'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$donors_table} WHERE %d = %d", 1, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			set_transient( 'charitable_givewp_counts', $counts, HOUR_IN_SECONDS );

			return $counts;
		}

		/**
		 * Get screen-specific messages for GiveWP pages.
		 *
		 * @since 1.8.10
		 *
		 * @param array $counts GiveWP data counts.
		 *
		 * @return array Keyed by screen ID, with 'default' fallback.
		 */
		private function get_givewp_messages( $counts ) {
			$messages = array();

			$generic = __( 'You have Charitable installed! Import your GiveWP campaigns, donations, and donors into Charitable.', 'charitable' );

			// Campaigns page.
			if ( $counts['campaigns'] > 0 ) {
				$messages['give_forms_page_give-campaigns'] = sprintf(
					/* translators: %s: number of campaigns */
					__( 'You have <strong>%s campaigns</strong> in GiveWP. Import them into Charitable in just a few clicks.', 'charitable' ),
					number_format_i18n( $counts['campaigns'] )
				);
			}

			// Donations page.
			if ( $counts['donations'] > 0 ) {
				$messages['give_forms_page_give-payment-history'] = sprintf(
					/* translators: %s: number of donations */
					__( 'You have <strong>%s donations</strong> in GiveWP. Import them into Charitable in just a few clicks.', 'charitable' ),
					number_format_i18n( $counts['donations'] )
				);
			}

			// Donors page.
			if ( $counts['donors'] > 0 ) {
				$messages['give_forms_page_give-donors'] = sprintf(
					/* translators: %s: number of donors */
					__( 'You have <strong>%s donors</strong> in GiveWP. Bring them into Charitable and keep your donor relationships intact.', 'charitable' ),
					number_format_i18n( $counts['donors'] )
				);
			}

			// Reports page — always a custom message.
			$messages['give_forms_page_give-reports'] = __( 'Get better reporting with Charitable. Import your GiveWP data for unified analytics.', 'charitable' );

			// Settings and Tools — always a custom message.
			$messages['give_forms_page_give-settings'] = __( 'Already using Charitable? Import your GiveWP data &mdash; campaigns, donations, and donors.', 'charitable' );
			$messages['give_forms_page_give-tools']    = $messages['give_forms_page_give-settings'];

			// Default fallback for any screen without a specific message.
			$messages['default'] = $generic;

			return $messages;
		}

		/**
		 * Create and return the class object.
		 *
		 * @since 1.8.10
		 *
		 * @return Charitable_External_Banner
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
