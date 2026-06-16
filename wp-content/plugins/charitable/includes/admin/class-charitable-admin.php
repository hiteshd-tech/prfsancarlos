<?php
/**
 * Class that sets up the Charitable Admin functionality.
 *
 * @package   Charitable/Classes/Charitable_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin' ) ) :

	/**
	 * Charitable_Admin
	 *
	 * @final
	 * @since 1.0.0
	 */
	final class Charitable_Admin {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Admin|null
		 */
		private static $instance = null;

		/**
		 * Donation actions class.
		 *
		 * @var Charitable_Donation_Admin_Actions
		 */
		private $donation_actions;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since  1.0.0
		 * @version 1.8.3 added third party plugin area.
		 */
		protected function __construct() {
			$this->load_dependencies();

			$this->donation_actions = new Charitable_Donation_Admin_Actions();

			// Initialize About page.
			new Charitable_About();

			do_action( 'charitable_admin_loaded' );

			add_action( 'wp_ajax_charitable_lite_settings_upgrade', array( $this, 'dismiss_lite_cta' ) );
			add_action( 'wp_ajax_charitable_lite_reports_upgrade', array( $this, 'dismiss_lite_reports_cta' ) );

			// Addon management AJAX handlers.
			add_action( 'wp_ajax_charitable_install_addon', array( $this, 'install_addon' ) );
			add_action( 'wp_ajax_charitable_activate_addon', array( $this, 'activate_addon' ) );
			add_action( 'wp_ajax_charitable_deactivate_addon', array( $this, 'deactivate_addon' ) );

			// Dedicated About/Addons handlers to avoid conflicts with legacy hooks.
			add_action( 'wp_ajax_charitable_addons_install_wporg', array( $this, 'install_addon' ) );
			add_action( 'wp_ajax_charitable_addons_activate', array( $this, 'activate_addon' ) );
			add_action( 'wp_ajax_charitable_addons_deactivate', array( $this, 'deactivate_addon' ) );

			add_filter( 'post_row_actions', array( $this, 'campaign_action_row' ), 10, 2 );
			add_filter( 'get_edit_post_link', array( $this, 'campaign_link' ), 99, 3 );
			add_filter( 'preview_post_link', array( $this, 'campaign_preview_link' ), 10, 2 );

			add_filter( 'admin_init', array( $this, 'update_dashboard_url' ), 10 );

			/* third party plugins */
			add_filter( 'elementor/settings/controls/checkbox_list_cpt/post_type_objects', array( $this, 'elementor_post_types' ), 10, 1 );
		}

		/**
		 * Redirect from the old dashboard to the new, unless legacy campaigns are being used and defined.
		 *
		 * @since   1.8.1
		 * @version 1.8.4
		 *
		 * @return void
		 */
		public function update_dashboard_url() {
			global $pagenow;

			if ( function_exists( 'charitable_use_legacy_dashboard' ) && charitable_use_legacy_dashboard() ) {
				return;
			}

			if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === 'charitable' ) { // phpcs:ignore

				// Check and see if this has to do with onboarding.

				if ( isset( $_GET['wpchar_lite'] ) && isset( $_GET['setup'] ) ) {  // phpcs:ignore

					switch ( sanitize_text_field( wp_unslash( $_GET['setup'] ) ) ) {  // phpcs:ignore

						case 'welcome':
							if ( empty( $_GET['wpchar_lite'] ) || 'lite' !== $_GET['wpchar_lite'] ) { // phpcs:ignore
								wp_safe_redirect( admin_url( 'admin.php?page=charitable-dashboard' ) );
								exit;
							}

							break;

						case 'return':
							// if the user is returning from a redirect from a login or back link detect that and redirect them to the welcome/continue page.
							// Only good way so far would be to detect POST data.
							if ( empty( $_POST ) && is_admin() ) { // phpcs:ignore
								wp_safe_redirect( admin_url( 'admin.php?page=charitable&wpchar_lite=lite&setup=welcome&resume=true&lostconnection=1' ) );
								exit;
							}

							$plugins     = isset( $_POST['plugins'] )     ? base64_decode( sanitize_text_field( wp_unslash( $_POST['plugins'] ) ) ) : ''; // phpcs:ignore
							$features    = isset( $_POST['features'] )    ? base64_decode( sanitize_text_field( wp_unslash( $_POST['features'] ) ) ) : ''; // phpcs:ignore
							$meta        = isset( $_POST['meta'] )        ? base64_decode( sanitize_text_field( wp_unslash( $_POST['meta'] ) ) ) : ''; // phpcs:ignore
							$pm	   	     = isset( $_POST['pm'] )          ? base64_decode( sanitize_text_field( wp_unslash( $_POST['pm'] ) ) ) : ''; // phpcs:ignore
							$license_key = isset( $_POST['license_key'] ) ? base64_decode( sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) ) : ''; // phpcs:ignore

							// process meta.
							if ( ! empty( $meta ) ) {
								$meta = str_replace( '\"', '"', $meta );
								if ( ! empty( $meta ) ) {
									$meta = json_decode( $meta, true );
								}
							}
							// process payment methods.
							if ( ! empty( $plugins ) ) {
								$plugins = json_decode( $plugins );
								$plugins = str_replace( array( '"', '[', ']' ), '', $plugins );
								if ( ! empty( $plugins ) ) {
									$plugins = explode( ',', $plugins );
								}
							}

							// process plugins.
							if ( ! empty( $pm ) ) {
								$pm = json_decode( $pm );
								if ( $pm ) {
									$pm = str_replace( array( '"', '[', ']' ), '', $pm );
									if ( ! empty( $plugins ) ) {
										$pm = explode( ',', $pm );
									}
								}
							}

							// process features.
							if ( ! empty( $features ) ) {
								$features = json_decode( $features );
								$features = str_replace( array( '"', '[', ']' ), '', $features );
								if ( ! empty( $features ) ) {
									$features = explode( ',', $features );
								}
							}

							// clean license (remove quotes).
							$license_key = str_replace( '"', '', $license_key );

							// process campaign.
							$campaign_template    = isset( $_POST['template'] ) && ! empty( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : ''; // phpcs:ignore
							$campaign_title       = isset( $_POST['campaign_title'] ) && ! empty( $_POST['campaign_title'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_title'] ) ) : ''; // phpcs:ignore
							$campaign_description = isset( $_POST['campaign_description'] ) && ! empty( $_POST['campaign_description'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_description'] ) ) : ''; // phpcs:ignore
							$campaign_goal        = isset( $_POST['campaign_goal'] ) && ! empty( $_POST['campaign_goal'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_goal'] ) ) : ''; // phpcs:ignore
							$campaign_end_date    = isset( $_POST['campaign_end_date'] ) && ! empty( $_POST['campaign_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_end_date'] ) ) : ''; // phpcs:ignore
							$campaign             = array(
								'template'    => $campaign_template,
								'title'       => $campaign_title,
								'description' => $campaign_description,
								'goal'        => $campaign_goal,
								'end_date'    => $campaign_end_date,
							);

							// store server side setups.
							$serverside = new Charitable_Setup();

							$serverside->store_meta( $meta );
							$serverside->store_plugins( $plugins );
							$serverside->store_features( $features );
							$serverside->store_payment_methods( $pm );
							$serverside->store_campaign( $campaign );
							if ( $license_key ) {
								$serverside->store_license_key( $license_key );
							}

							// If this is a select few addons that we need to disable their auto-activation, do so.
							if ( ! empty( $plugins ) ) {
								foreach ( $plugins as $plugin ) {
									if ( $plugin === 'all-in-one-seo-pack' ) {
										update_option( 'aioseo_activation_redirect', true );
									} elseif ( $plugin === 'wp-mail-smtp' ) {
										update_option( 'wp_mail_smtp_activation_prevent_redirect', true );
									}
								}
							}

							// add a transient to indicate that the server side onboarding has moved to the plugin.
							set_transient( 'charitable_ss_onboarding', 1, 0 );

							delete_option( 'charitable_started_onboarding' );

							wp_safe_redirect( admin_url( 'admin.php?page=charitable-setup&setup=1' ) );
							exit;

						case 'cancelled':
							// Either user is going "back" to the checklist page or (if the checklist is disabled) the welcome page.
							// https://mydomain.com/wp-admin/admin.php?page=charitable&wpchar_lite=lite&setup=cancelled.
							$checklist_class     = Charitable_Checklist::get_instance();
							$checklist_possible  = $checklist_class->is_checklist_skipped() || $checklist_class->is_checklist_completed();
							$welcome_go_back_url = $checklist_possible ? admin_url( 'admin.php?page=charitable-dashboard' ) : admin_url( 'admin.php?page=charitable-setup-checklist' );

							// Clear the transient.
							delete_transient( 'charitable_activation_redirect' );

							// Clear the option.
							delete_option( 'charitable_started_onboarding' );

							wp_safe_redirect( $welcome_go_back_url );
							exit;

						default:
							wp_safe_redirect( admin_url( 'admin.php?page=charitable-dashboard' ) );
							exit;

					}
				} else {

					wp_safe_redirect( admin_url( 'admin.php?page=charitable-dashboard' ) );
					exit;

				}
			}
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Include admin-only files.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function load_dependencies() {
			$admin_dir = charitable()->get_path( 'includes' ) . 'admin/';

			require_once $admin_dir . 'charitable-core-admin-functions.php';
			require_once $admin_dir . 'campaigns/charitable-admin-campaign-hooks.php';
			require_once $admin_dir . 'dashboard/class-charitable-dashboard-ajax.php';
			require_once $admin_dir . 'charitable-dashboard-hooks.php';
			require_once $admin_dir . 'dashboard-widgets/charitable-dashboard-widgets-hooks.php';
			require_once $admin_dir . 'donations/charitable-admin-donation-hooks.php';
			require_once $admin_dir . 'settings/charitable-settings-admin-hooks.php';
			// Security hooks are now loaded in charitable.php to ensure CAPTCHA works on frontend.
			// require_once $admin_dir . 'security/charitable-security-hooks.php';
			require_once $admin_dir . 'activities/charitable-admin-activity-hooks.php';
			require_once $admin_dir . 'reports/charitable-core-reports-functions.php';
			require_once $admin_dir . 'reports/charitable-admin-reports-hooks.php';
			require_once $admin_dir . 'plugins/class-charitable-admin-plugins-third-party.php';
			require_once $admin_dir . 'tools/charitable-tools-admin-hooks.php';
			require_once $admin_dir . 'growth-tools/charitable-growth-tools-admin-hooks.php';
			require_once $admin_dir . 'onboarding/charitable-onboarding-admin-hooks.php';
			require_once $admin_dir . 'tracking/charitable-tracking-admin-hooks.php';
			require_once $admin_dir . 'smtp/charitable-smtp-admin-hooks.php';
			require_once $admin_dir . 'class-charitable-about.php';
			require_once $admin_dir . 'charitable-admin-addons-functions.php';
			require_once $admin_dir . 'privacy-compliance/charitable-privacy-compliance-admin-hooks.php';
			require_once $admin_dir . 'intergrations/charitable-privacy-compliance-admin-hooks.php';
			require_once $admin_dir . 'intergrations/charitable-backups-admin-hooks.php';
			require_once $admin_dir . 'intergrations/charitable-seo-admin-hooks.php';
			require_once $admin_dir . 'intergrations/charitable-automation-admin-hooks.php';
		}

		/**
		 * Get Charitable_Donation_Admin_Actions class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_Admin_Actions
		 */
		public function get_donation_actions() {
			return $this->donation_actions;
		}

		/**
		 * Do an admin action.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean|WP_Error WP_Error in case of error. Mixed results if the action was performed.
		 */
		public function maybe_do_admin_action() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! array_key_exists( 'charitable_admin_action', $_GET ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check for required keys only
			if ( count( array_diff( array( 'action_type', '_nonce', 'object_id' ), array_keys( $_GET ) ) ) ) {
				return new WP_Error( __( 'Action could not be executed.', 'charitable' ) );
			}

			if ( ! isset( $_GET['_nonce'] ) || ! wp_verify_nonce( $_GET['_nonce'], 'donation_action' ) ) { // phpcs:ignore.
				return new WP_Error( __( 'Action could not be executed. Nonce check failed.', 'charitable' ) );
			}

			if ( ! isset( $_GET['action_type'] ) || 'donation' !== $_GET['action_type'] ) {
				return new WP_Error( __( 'Action from an unknown action type executed.', 'charitable' ) );
			}

			return $this->donation_actions->do_action( esc_html( $_GET['charitable_admin_action'] ), esc_html( $_GET['object_id'] ) ); // phpcs:ignore.
		}

		/**
		 * Loads admin-only scripts and stylesheets.
		 *
		 * @since  1.0.0
		 * @version 1.8.8.6
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {

			$min     = charitable_get_min_suffix(); // 1.8.3
			$version = charitable_is_break_cache() ? charitable()->get_version() . '.' . time() : charitable()->get_version();

			$assets_dir = charitable()->get_path( 'assets', false );

			$localized_vars = array(
				'suggested_amount_placeholder'             => __( 'Amount', 'charitable' ),
				'suggested_amount_description_placeholder' => __( 'Optional Description', 'charitable' ),
				'ajax_url'                                 => admin_url( 'admin-ajax.php' ),
				'nonce'                                    => wp_create_nonce( 'charitable_admin_nonce' ),
				'heads_up'                                 => __( 'Heads Up!', 'charitable' ),
				'square_legacy_modal_message'              => __( 'Square (Legacy) is currently active. Activating the Square (Core) payment method will deactivate the Square (Legacy) payment and you will need to reconnect your Square account.<br><br>Click <strong><span style="color: #ff6b35;">Ok</span></strong> to deactivate Square (Legacy) and activate Square (Core) payment. Click <strong><span style="color: #000000;">Cancel</span></strong> to keep Square (Legacy) payment active.</strong><br><br><a href="https://wpcharitable.com/documentation/square-legacy" target="_blank">Read our documentation</a>', 'charitable' ),
				'square_core_modal_message'                => __( 'Square (Core) is currently active. Activating the Square (Legacy) payment method will deactivate the Square (Core) payment and you will need to reconnect your Square account.<br><br>Click <strong><span style="color: #ff6b35;">Ok</span></strong> to deactivate Square (Core) and activate Square (Legacy) payment. Click <strong><span style="color: #000000;">Cancel</span></strong> to keep Square (Core) payment active.<br><br><a href="https://wpcharitable.com/documentation/square-legacy" target="_blank">Read our documentation</a>', 'charitable' ),
				'oops'                                     => __( 'Oops!', 'charitable' ),
				'square_legacy_switch_error'               => __( 'There was an error switching the payment gateways. Please try again.', 'charitable' ),
				'ok'                                       => __( 'OK', 'charitable' ),
				'cancel'                                   => __( 'Cancel', 'charitable' ),
				'square_legacy_active'                     => charitable_get_helper( 'gateways' )->is_active_gateway( 'square' ),
				'square_core_active'                       => charitable_get_helper( 'gateways' )->is_active_gateway( 'square_core' ),
			);

			/* Menu styles are loaded everywhere in the WordPress dashboard. */
			wp_register_style(
				'charitable-admin-menu',
				$assets_dir . 'css/admin/charitable-admin-menu' . $min . '.css',
				array(),
				$version
			);

			wp_enqueue_style( 'charitable-admin-menu' );

			wp_register_script(
				'charitable-admin-pages-all',
				$assets_dir . 'js/admin/charitable-admin-pages' . $min . '.js',
				array( 'jquery' ),
				$version,
				false
			);

			wp_enqueue_script( 'charitable-admin-pages-all' );

			/* Admin page styles are registered but only enqueued when necessary. */
			wp_register_style(
				'charitable-admin-pages',
				$assets_dir . 'css/admin/charitable-admin-pages' . $min . '.css',
				array(),
				$version
			);

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			/* This check covers the category and tags pages, only load the main admin css (upgrade banner, etc.) */
			if ( ! is_null( $screen ) && ( in_array( $screen->id, array( 'edit-campaign_category', 'edit-campaign_tag' ), true ) || ( in_array( $screen->base, array( 'edit-tags', 'edit-categories' ), true ) ) ) ) {

				wp_register_style(
					'charitable-admin',
					$assets_dir . 'css/admin/charitable-admin-legacy' . $min . '.css',
					array(),
					$version
				);

				wp_enqueue_style( 'charitable-admin' );

				wp_register_script(
					'charitable-admin-notice',
					$assets_dir . 'js/admin/charitable-admin-notice' . $min . '.js',
					array( 'jquery' ),
					$version,
					false
				);

			}

			// v 1.8.0.
			wp_register_style(
				'charitable-admin-2.0',
				$assets_dir . 'css/admin/charitable-admin' . $min . '.css',
				array(),
				$version
			);

			wp_enqueue_style( 'charitable-admin-2.0' );

			wp_register_style(
				'charitable-admin',
				$assets_dir . 'css/admin/charitable-admin-legacy' . $min . '.css',
				array(),
				$version
			);

			wp_enqueue_style( 'charitable-admin' );

			if ( ! is_null( $screen ) && in_array( $screen->id, charitable_get_charitable_screens() ) ) {

				$dependencies = array( 'jquery-ui-datepicker', 'jquery-ui-tabs', 'jquery-ui-sortable' );

				if ( 'donation' === $screen->id ) {
					wp_register_script(
						'accounting',
						$assets_dir . 'js/libraries/accounting' . $min . '.js',
						array( 'jquery' ),
						$version,
						true
					);

					$dependencies[] = 'accounting';
					$localized_vars = array_merge(
						$localized_vars,
						array(
							'currency_format_num_decimals' => esc_attr( charitable_get_currency_helper()->get_decimals() ),
							'currency_format_decimal_sep'  => esc_attr( charitable_get_currency_helper()->get_decimal_separator() ),
							'currency_format_thousand_sep' => esc_attr( charitable_get_currency_helper()->get_thousands_separator() ),
							'currency_format'              => esc_attr( charitable_get_currency_helper()->get_accounting_js_format() ),
						)
					);
				}

				wp_register_script(
					'charitable-admin',
					$assets_dir . 'js/admin/charitable-admin' . $min . '.js',
					$dependencies,
					$version,
					false
				);

				wp_enqueue_script( 'charitable-admin' );

				// v 1.8.0.

				wp_enqueue_style(
					'jquery-confirm',
					charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
					null,
					'3.3.4'
				);

				wp_enqueue_style(
					'charitable-font-awesome',
					charitable()->get_path( 'directory', false ) . 'assets/lib/font-awesome/font-awesome.min.css',
					null,
					'4.7.0'
				);

				wp_enqueue_script(
					'jquery-confirm',
					charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
					array( 'jquery' ),
					'3.3.4',
					false
				);

				wp_enqueue_style(
					'lity',
					charitable()->get_path( 'directory', false ) . 'assets/lib/lity/lity.min.css',
					null,
					'3.0.0'
				);

				// Add custom CSS for lity image sizing
				wp_add_inline_style( 'lity', '
					.lity-image img {
						max-width: 90vw !important;
						max-height: 90vh !important;
						width: auto !important;
						height: auto !important;
						margin: 0 auto !important;
					}
					.lity-image .lity-container {
						max-width: 90vw !important;
						max-height: 90vh !important;
					}
				' );

				wp_enqueue_script(
					'lity',
					charitable()->get_path( 'directory', false ) . 'assets/lib/lity/lity.min.js',
					array( 'jquery' ),
					'3.0.0',
					false
				);

				wp_register_script(
					'charitable-admin-2.0',
					$assets_dir . 'js/admin/charitable-admin-2.0' . $min . '.js',
					$dependencies,
					$version,
					false
				);

				wp_enqueue_script( 'charitable-admin-2.0' );

				/**
				 * Filter the admin Javascript vars.
				 *
				 * @since 1.0.0
				 *
				 * @param array $localized_vars The vars.
				 */
				$localized_vars = (array) apply_filters( 'charitable_localized_javascript_vars', $localized_vars );

				wp_localize_script( 'charitable-admin', 'CHARITABLE', $localized_vars );

				// Localize script for charitable-admin-2.0.js
				wp_localize_script( 'charitable-admin-2.0', 'charitable_admin', array(
					'autoshow_plugin_notifications' => charitable_get_autoshow_plugin_notifications(),
				) );

				/* color picker for admin settings */
				wp_enqueue_script( 'wp-color-picker', false, false, false, true ); // phpcs:ignore.
				wp_enqueue_style( 'wp-color-picker' );

			} // end if

			wp_enqueue_script(
				'charitable-admin-utils',
				charitable()->get_path( 'directory', false ) . "assets/js/admin/charitable-admin-utils{$min}.js",
				array( 'jquery' ),
				charitable()->get_version(),
				false
			);

			// Register these scripts only for reports and dashboard pages.
			if ( ! is_null( $screen ) && ( $screen->id === 'charitable_page_charitable-reports' || $screen->id === 'charitable_page_charitable-dashboard' ) ) {

				// Specific styles for the "overview" and main reporting tabs.
				if ( empty( $_GET['tab'] ) || ( ! empty( $_GET['tab'] && charitable_reports_allow_tab_load_scripts( strtolower( $_GET['tab'] ) ) ) ) ) { // phpcs:ignore

					wp_register_script(
						'charitable-apex-charts',
						charitable()->get_path( 'assets', false ) . 'js/libraries/apexcharts.min.js',
						array( 'jquery' ),
						$version,
						true
					);

					/**
					 * Use WordPress core's Moment.js library.
					 *
					 * WordPress core includes Moment.js and exposes it via wp_enqueue_script('moment').
					 * This library is required as a dependency for daterangepicker.min.js, which powers
					 * the date range picker functionality in the reporting interface.
					 *
					 * @see https://github.com/moment/moment
					 * @since 1.0.0
					 * @version 1.8.8.6
					 */
					wp_enqueue_script( 'moment' );

					wp_register_script(
						'charitable-report-date-range-picker',
						charitable()->get_path( 'assets', false ) . 'js/libraries/daterangepicker.min.js',
						array( 'jquery', 'moment' ),
						$version,
						true
					);

					wp_register_script(
						'charitable-reporting',
						$assets_dir . 'js/admin/charitable-admin-reporting' . $min . '.js',
						array( 'jquery', 'charitable-apex-charts', 'charitable-report-date-range-picker' ),
						$version,
						true
					);

					wp_enqueue_style(
						'charitable-report-date-range-picker',
						charitable()->get_path( 'assets', false ) . 'css/libraries/daterangepicker.css',
						null,
						'4.7.0'
					);

					wp_enqueue_script( 'charitable-apex-charts' );
					wp_enqueue_script( 'charitable-report-date-range-picker' );
					wp_enqueue_script( 'charitable-reporting' );

				} else if ( ! empty( $_GET['tab'] && 'analytics' === $_GET['tab'] ) ) { // phpcs:ignore

					// this loads a specific script for the analytics tab.
					do_action( 'charitable_admin_enqueue_analytics_scripts' );

				}
			}

			// Register v2 dashboard specific assets
			if ( ! is_null( $screen ) && $screen->id === 'charitable_page_charitable-dashboard' && ! charitable_use_legacy_dashboard() ) {

				// Register ApexCharts for the v2 dashboard chart
				wp_register_script(
					'charitable-apex-charts',
					charitable()->get_path( 'assets', false ) . 'js/libraries/apexcharts.min.js',
					array( 'jquery' ),
					$version,
					true
				);

				// Enqueue dashboard specific CSS
				wp_enqueue_style(
					'charitable-admin-dashboard',
					charitable()->get_path( 'assets', false ) . 'css/admin/charitable-admin-dashboard.css',
					array(),
					charitable()->get_version()
				);

				// Enqueue dashboard specific JavaScript with ApexCharts dependency
				wp_enqueue_script(
					'charitable-admin-dashboard',
					$assets_dir . 'js/admin/charitable-admin-dashboard.js',
					array( 'jquery', 'charitable-apex-charts' ),
					charitable()->get_version(),
					true
				);

				// Enqueue ApexCharts
				wp_enqueue_script( 'charitable-apex-charts' );
			}

			// Register these scripts only for checklist, onboarding, and similar pages.
			if ( ! is_null( $screen ) && ( $screen->id === 'charitable_page_charitable-setup-checklist' ) ) {

				// v 1.8.1.10.

				wp_register_script(
					'charitable-onboarding',
					$assets_dir . 'js/admin/charitable-admin-onboarding' . $min . '.js',
					array( 'jquery' ),
					$version,
					true
				);

				wp_enqueue_script( 'charitable-onboarding' );

			}

			wp_register_script(
				'charitable-admin-plugins',
				$assets_dir . 'js/plugins/charitable-admin-plugins' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			wp_enqueue_script( 'charitable-admin-plugins' );

			wp_register_script(
				'charitable-admin-notice',
				$assets_dir . 'js/admin/charitable-admin-notice' . $min . '.js',
				array( 'jquery' ),
				$version,
				false
			);

			wp_register_script(
				'charitable-admin-media',
				$assets_dir . 'js/admin/charitable-admin-media' . $min . '.js',
				array( 'jquery' ),
				$version,
				false
			);

			wp_register_script(
				'lean-modal',
				$assets_dir . 'js/libraries/leanModal' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			wp_register_style(
				'lean-modal-css',
				$assets_dir . 'css/modal' . $min . '.css',
				array(),
				$version
			);

			wp_register_script(
				'charitable-admin-tables',
				$assets_dir . 'js/admin/charitable-admin-tables' . $min . '.js',
				array( 'jquery', 'lean-modal' ),
				$version,
				true
			);

			wp_register_script(
				'select2',
				$assets_dir . 'js/libraries/select2' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			wp_register_style(
				'select2-css',
				$assets_dir . 'css/libraries/select2' . $min . '.css',
				array(),
				$version
			);

			do_action( 'after_charitable_admin_enqueue_scripts', $min, $version, $assets_dir ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook name is already in use by other code (charitable-admin-hooks.php). Changing it would break existing functionality.
		}

		/**
		 * Set admin body classes.
		 *
		 * @since   1.5.0
		 * @version 1.8.1.6
		 * @version 1.8.1.16 Added pro/lite check.
		 * @version 1.8.3
		 * @version 1.8.5 Added dashboard and reports classes.
		 *
		 * @param  string $classes Existing list of classes.
		 * @return string
		 */
		public function set_body_class( $classes ) {
			$screen = get_current_screen();

			if ( 'donation' === $screen->post_type && ( 'add' === $screen->action || isset( $_GET['show_form'] ) ) ) { // phpcs:ignore.
				$classes .= ' charitable-admin-donation-form';
			}

			if ( isset( $_GET['page'] ) && ( 'charitable-tools' === $_GET['page'] || 'charitable-settings' === $_GET['page'] ) ) { // phpcs:ignore.
				$classes .= ' charitable-admin-settings';
			}

			if ( isset( $_GET['page'] ) && 'charitable-settings' === $_GET['page'] && isset( $_GET['tab'] ) && '' !== $_GET['tab'] ) { // phpcs:ignore.
				$classes .= ' charitable-admin-settings-' . esc_attr( $_GET['tab'] ); // phpcs:ignore.
			}

			if ( isset( $_GET['page'] ) && 'charitable-settings' === $_GET['page'] && isset( $_GET['tab'] ) && 'gateways' === $_GET['tab'] && empty( $_GET['group'] ) ) { // phpcs:ignore.
				$classes .= ' charitable-admin-settings-gateways-main'; // phpcs:ignore.
			}

			if ( isset( $_GET['page'] ) && 'charitable-reports' === $_GET['page'] ) { // phpcs:ignore.
				$classes .= ' charitable-admin-reports';
			}

			if ( isset( $_GET['page'] ) && 'charitable-dashboard' === $_GET['page'] && ! isset( $_GET['tab'] ) ) { // phpcs:ignore.
				$classes .= ' charitable-admin-reports-dashboard';
			}

			if ( isset( $_GET['page'] ) && 'charitable-reports' === $_GET['page'] && isset( $_GET['tab'] ) && '' !== $_GET['tab'] ) { // phpcs:ignore.
				$classes .= ' charitable-admin-reports charitable-admin-reports-' . esc_attr( $_GET['tab'] ); // phpcs:ignore.
			}

			if ( function_exists( 'charitable_use_legacy_dashboard' ) && charitable_use_legacy_dashboard() ) {
				$classes .= ' charitable_legacy_dashboard';
			}

			if ( charitable_show_promotion_footer() ) {
				$classes .= ' charitable-admin-promotion-footer';
			}

			$classes .= function_exists( 'charitable_is_pro' ) && charitable_is_pro() ? ' charitable-pro' : ' charitable';

			return $classes;
		}

		/**
		 * Add notices to the dashboard.
		 *
		 * @since  1.4.0
		 * @version 1.8.3 Added check for PHP Version deprecation.
		 *
		 * @return void
		 */
		public function add_notices() {
			if ( charitable_is_debug( 'square' ) ) {
				error_log( 'Charitable_Admin::add_notices() - Starting to render admin notices' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			// Render notices.
			charitable_get_admin_notices()->render();

			if ( charitable_is_debug( 'square' ) ) {
				error_log( 'Charitable_Admin::add_notices() - Current admin notices: ' . print_r( charitable_get_admin_notices()->get_notices(), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			// Check for PHP version deprecation.
			if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
				$this->php_notice_deprecated();
			}

			// Add third party notices.
			$this->add_third_party_notices();

			// Add version update notices.
			$this->add_version_update_notices();
		}

		/**
		 * Add notices for potential third party warnings.
		 *
		 * @since 1.7.0.8
		 */
		public function add_third_party_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Allow this feature to be disabled in case unforeseen problems come up.
			if ( false === apply_filters( 'charitable_donations_export_args', true ) ) {
				return;
			}

			$notices = array(
				/* translators: %s: link */
				'code-snippets/code-snippets.php' => sprintf(
					// Translators: %s: link.
					__( "You appear to be using a code snippet plugin. Please <a href='%s' target='_blank'>review best practices</a> when using snippets to avoid problems when upgrading or deactivating Charitable.", 'charitable' ),
					'https://wpcharitable.com/code-snippet-best-practices/'
				),
			);

			// Allow this list to be altered by a third party or charitable addon.
			$notices = apply_filters( 'charitable_admin_third_party_notices', $notices );

			$helper = charitable_get_admin_notices();

			foreach ( $notices as $notice => $message ) {
				if ( false === charitable_get_third_party_warning_option( 'code-snippets/code-snippets.php' ) || 'dismissed' === charitable_get_third_party_warning_option( 'code-snippets/code-snippets.php' ) ) {
					continue;
				}

				$helper->add_third_party_warning( $message, $notice );
			}
		}

		/**
		 * Add version update notices to the dashboard.
		 *
		 * @since  1.4.6 (deprecated in 1.8.0)
		 *
		 * @return void
		 */
		public function add_version_update_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$notices = array(
				'release-150' => sprintf(
					/* translators: %s: link */
					__( "Charitable 1.5 is packed with new features and improvements. <a href='%s' target='_blank'>Find out what's new</a>.", 'charitable' ),
					'https://wpcharitable.com/charitable-1-5-release-notes/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Admin+Notice&utm_content=Version+One+Five+Whats+New'
				),
				'release-160' => sprintf(
					/* translators: %s: link */
					__( 'Charitable 1.6 introduces important new user privacy features and other improvements. <a href="%s" target="_blank">Find out what\'s new</a>.', 'charitable' ),
					'https://www.wpcharitable.com/charitable-1-6-user-privacy-gdpr-better-refunds-and-a-new-shortcode/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Admin+Notice&utm_content=Version+One+Six+Whats+New'
				),
			);

			$helper = charitable_get_admin_notices();

			foreach ( $notices as $notice => $message ) {
				if ( ! get_transient( 'charitable_' . $notice . '_notice' ) ) {
					continue;
				}

				$helper->add_version_update( $message, $notice );
			}
		}

		/**
		 * Add php decpreation notices.
		 *
		 * @since  1.8.3
		 *
		 * @return void
		 */
		public function php_notice_deprecated() {
			$medium = charitable_is_pro() ? 'proplugin' : 'liteplugin';
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses(
						sprintf(
							// Translators: 1 - Opening HTML bold tag, 2 - Closing HTML bold tag, 3 - Opening HTML link tag, 4 - Closing HTML link tag.
							__( 'Your site is running an %1$soutdated version%2$s of PHP that is no longer supported and may cause issues with %3$s. Please contact your web hosting provider to update your PHP version or switch to a %4$srecommended WordPress hosting company%5$s.', 'charitable' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
							'<strong>',
							'</strong>',
							'<strong>Charitable</strong>',
							'<a href="https://www.wpbeginner.com/wordpress-hosting/" target="_blank" rel="noopener noreferrer">',
							'</a>'
						),
						array(
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
							'strong' => array(),
						)
					);
					?>
					<br><br>
					<?php
					echo wp_kses(
						sprintf(
							// phpcs:ignore Generic.Files.LineLength.MaxExceeded
							// Translators: 1 - Opening HTML bold tag, 2 - Closing HTML bold tag, 3 - The PHP version, 4 - The current year, 5 - The short plugin name ("Charitable"), 6 - Opening HTML link tag, 7 - Closing HTML link tag.
							__( '%1$sNote:%2$s Support for PHP %3$s will be discontinued in %4$s. After this, if no further action is taken, %5$s functionality will be disabled. %6$sRead more for additional information.%7$s', 'charitable' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
							'<strong>',
							'</strong>',
							PHP_VERSION,
							gmdate( 'Y' ),
							'Charitable',
							'<a href="https://wpcharitable.com/documentation/supported-php-versions-for-charitable/?utm_source=WordPress&utm_medium=' . $medium . '&utm_campaign=outdated-php-notice" target="_blank" rel="noopener noreferrer">', // phpcs:ignore Generic.Files.LineLength.MaxExceeded
							'</a>'
						),
						array(
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
							'strong' => array(),
						)
					);
					?>
				</p>
			</div>

			<?php
			// In case this is on plugin activation.
			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore HM.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] );
			}
		}

		/**
		 * Dismiss a notice.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function dismiss_notice() {
			if ( ! isset( $_POST['notice'] ) ) { // phpcs:ignore.
				wp_send_json_error();
			}

			$notice = sanitize_text_field( wp_unslash( $_POST['notice'] ) ); // phpcs:ignore.

			// Handle Square connection error notice.
			if ( 'square_connection_error' === $notice ) {
				delete_option( 'charitable_square_connection_error_notice' );
				wp_send_json_success();
				return;
			}

			$ret = ( 'noted' === charitable_get_third_party_warning_option( 'code-snippets/code-snippets.php' ) ) ? charitable_set_third_party_warning_option( 'code-snippets/code-snippets.php', 'dismissed' ) : delete_transient( 'charitable_' . $notice . '_notice', true );

			do_action( 'charitable_dismiss_notice', $_POST ); // phpcs:ignore.

			if ( ! $ret ) {
				wp_send_json_error( $ret );
			}

			wp_send_json_success();
		}

		/**
		 * Dismiss a banner.
		 *
		 * @since  1.7.0
		 *
		 * @return void
		 */
		public function dismiss_banner() {
			if ( ! isset( $_POST['banner_id'] ) ) { // phpcs:ignore.
				wp_send_json_error();
			}

			$ret = set_transient( 'charitable_' . esc_attr( $_POST['banner_id'] ) . '_banner', 1, WEEK_IN_SECONDS ); // phpcs:ignore.

			if ( ! $ret ) {
				wp_send_json_error( $ret );
			}

			wp_send_json_success();
		}

		/**
		 * Dismiss a banner.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function dismiss_list_banner() {
			if ( ! isset( $_POST['banner_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_send_json_error();
			}

			$ret = set_transient( 'charitable_' . esc_attr( $_POST['banner_id'] ) . '_list_banner', 1, WEEK_IN_SECONDS ); // phpcs:ignore.

			if ( ! $ret ) {
				wp_send_json_error( $ret );
			}

			wp_send_json_success();
		}

		/**
		 * Dismiss a five star rating.
		 *
		 * @since  1.7.0
		 *
		 * @return void
		 */
		public function dismiss_five_star_rating() {
			if ( ! isset( $_POST['banner_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_send_json_error();
			}

			$check = get_transient( 'charitable_' . esc_attr( $_POST['banner_id'] ) . '_banner' ); // phpcs:ignore.

			if ( ! $check ) {
				$ret = set_transient( 'charitable_' . esc_attr( $_POST['banner_id'] ) . '_banner', true ); // phpcs:ignore.
				if ( ! $ret ) {
					wp_send_json_error( $ret );
				}
			}

			wp_send_json_success();
		}

		/**
		 * Dismiss dashboard notices.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function dismiss_dashboard_notices() {
			if ( ! isset( $_POST['notice_ids'] ) ) { // phpcs:ignore.
				wp_send_json_error();
			}

			$ret = delete_transient( 'charitable_dashboard_notices' );

			// take a comma delimited $_POST value and turn it into an array that is properly filtered only for alphanumeric values.
			$notices = array_filter( array_map( 'sanitize_text_field', explode( ',', $_POST['notice_ids'] ) ) ); // phpcs:ignore

			if ( ! empty( $notices ) && in_array( 'donation-security', $notices, true ) ) {
				delete_transient( 'charitable_donation_security_checks' );
			}

			if ( ! $ret ) {
				wp_send_json_error( $ret );
			}

			wp_send_json_success();
		}

		/**
		 * Dismiss guide tool notices.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function dismiss_growth_tools_notices() {

			// check the nonce via ajax.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable_dismiss_growth_tools' ) ) { // phpcs:ignore
				wp_send_json_error();
			}

			$notice_type = isset( $_POST['notice_type'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_type'] ) ) : ''; // phpcs:ignore

			if ( ! $notice_type ) {
				wp_send_json_error();
			}

			$charitable_growth_tool_notices = get_option( 'charitable_growth_tool_notices' );

			if ( ! is_array( $charitable_growth_tool_notices ) ) {
				$charitable_growth_tool_notices = array();
			}

			$charitable_growth_tool_notices['dismiss'][ $notice_type ] = time();

			$ret = update_option( 'charitable_growth_tool_notices', $charitable_growth_tool_notices );

			if ( ! $ret ) {
				wp_send_json_error( $ret );
			}

			wp_send_json_success();
		}

		/**
		 * Dismiss a five star rating.
		 *
		 * @since  1.7.0
		 *
		 * @return void
		 */
		public function dismiss_lite_cta() {
			if ( ! isset( $_POST['charitable_action'] ) ) { // phpcs:ignore.
				wp_send_json_error();
			}

			$updated = update_option( 'charitable_lite_settings_upgrade', true );

			if ( $updated ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * Dismiss a five star rating.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function dismiss_lite_reports_cta() {
			if ( ! isset( $_POST['charitable_action'] ) ) { // phpcs:ignore.
				wp_send_json_error();
			}

			$updated = update_option( 'charitable_lite_reports_upgrade', true );

			if ( $updated ) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		/**
		 * Adds one or more classes to the body tag in the dashboard.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $classes Current body classes.
		 * @return string Altered body classes.
		 */
		public function add_admin_body_class( $classes ) {
			$screen = get_current_screen();

			if ( in_array( $screen->post_type, array( Charitable::DONATION_POST_TYPE, Charitable::CAMPAIGN_POST_TYPE ) ) ) {
				$classes .= ' post-type-charitable';
			}

			return $classes;
		}

		/**
		 * Add custom links to the plugin actions.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $links Plugin action links.
		 * @return string[]
		 */
		public function add_plugin_action_links( $links ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=charitable-settings' ) . '">' . __( 'Settings', 'charitable' ) . '</a>';
			return $links;
		}

		/**
		 * Add Extensions link to the plugin row meta.
		 *
		 * @since  1.2.0
		 *
		 * @param  string[] $links Plugin action links.
		 * @param  string   $file  The plugin file.
		 * @return string[] $links
		 */
		public function add_plugin_row_meta( $links, $file ) {
			if ( plugin_basename( charitable()->get_path() ) != $file ) {
				return $links;
			}

			// Use an updated UTM.
			$extensions_link = charitable_ga_url(
				'https://wpcharitable.com/extensions/',
				urlencode( 'Admin Plugin Page' ),
				urlencode( 'Extensions' )
			);

			$links[] = '<a href="' . $extensions_link . '" target="_blank" rel="noopener">' . __( 'Extensions', 'charitable' ) . '</a>';

			return $links;
		}

		/**
		 * Remove the jQuery UI styles added by Ninja Forms.
		 *
		 * @since  1.2.0
		 *
		 * @param  string $context Media buttons context.
		 * @return string
		 */
		public function remove_jquery_ui_styles_nf( $context ) {
			wp_dequeue_style( 'jquery-smoothness' );
			return $context;
		}

		/**
		 * Export donations.
		 *
		 * @since  1.3.0
		 *
		 * @return false|void Returns false if the export failed. Exits otherwise.
		 */
		public function export_donations() {

			if ( ! wp_verify_nonce( $_GET['_charitable_export_nonce'], 'charitable_export_donations' ) ) { // phpcs:ignore
				return false;
			}

			/**
			 * Filter the donation export arguments.
			 *
			 * @since 1.3.0
			 *
			 * @param array $args Export arguments.
			 */
			$export_args = apply_filters(
				'charitable_donations_export_args',
				array(
					'start_date'  => isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '',
					'end_date'    => isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '',
					'status'      => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
					'campaign_id' => isset( $_GET['campaign_id'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_id'] ) ) : '',
					'report_type' => isset( $_GET['report_type'] ) ? sanitize_text_field( wp_unslash( $_GET['report_type'] ) ) : '',
				)
			);

			/**
			 * Filter the export class name.
			 *
			 * @since 1.3.0
			 *
			 * @param string $report_type The type of report.
			 * @param array  $args        Export arguments.
			 */
			$export_class = apply_filters( 'charitable_donations_export_class', 'Charitable_Export_Donations', esc_attr( $_GET['report_type'] ), $export_args ); // phpcs:ignore

			new $export_class( $export_args );

			die();
		}

		/**
		 * Export campaigns.
		 *
		 * @since  1.6.0
		 *
		 * @return false|void Returns false if the export failed. Exits otherwise.
		 */
		public function export_campaigns() {

			if ( ! isset( $_GET['_charitable_export_nonce'] ) || ! wp_verify_nonce( $_GET['_charitable_export_nonce'], 'charitable_export_campaigns' ) ) { // phpcs:ignore
				return false;
			}

			/**
			 * Filter the campaign export arguments.
			 *
			 * @since 1.6.0
			 *
			 * @param array $args Export arguments.
			 */
			$export_args = apply_filters(
				'charitable_campaigns_export_args',
				array(
					'start_date_to' => isset( $_GET['start_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date_to'] ) ) : '',
					'end_date_from' => isset( $_GET['end_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date_from'] ) ) : '',
					'end_date_to'   => isset( $_GET['end_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date_to'] ) ) : '',
					'status'        => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
					'report_type'   => isset( $_GET['report_type'] ) ? sanitize_text_field( wp_unslash( $_GET['report_type'] ) ) : '',
				)
			);

			/**
			 * Filter the export class name.
			 *
			 * @since 1.6.0
			 *
			 * @param string $report_type The type of report.
			 * @param array  $args        Export arguments.
			 */
			$export_class = apply_filters( 'charitable_campaigns_export_class', 'Charitable_Export_Campaigns', esc_attr( $_GET['report_type'] ), $export_args ); // phpcs:ignore

			new $export_class( $export_args );

			die();
		}

		/**
		 * Returns an array of screen IDs where the Charitable scripts should be loaded.
		 * (Deprecated in 1.8.1.15, use charitable_get_charitable_Screens() in core functions instead).
		 *
		 * @uses   charitable_admin_screens
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_charitable_screens() {
			/**
			 * Filter admin screens where Charitable styles & scripts should be loaded.
			 *
			 * @since 1.8.0
			 * @since 1.8.1 Added `charitable_page_charitable-dashboard` and 'charitable_page_charitable-reports' to the list of screens.
			 * @since 1.8.1.6 Added 'charitable_page_charitable-tools' and 'charitable_page_charitable-growth-tool' to the list of screens.
			 * @since 1.8.5 Added 'charitable_page_charitable-donors' to the list of screens.
			 *
			 * @param string[] $screens List of screen ids.
			 */
			return apply_filters(
				'charitable_admin_screens',
				array(
					'campaign',
					'donation',
					'charitable_page_charitable-reports',
					'charitable_page_charitable-dashboard',
					'charitable_page_charitable-settings',
					'charitable_page_charitable-tools',
					'charitable_page_charitable-growth-tools',
					'edit-campaign',
					'edit-donation',
					'dashboard',
					'toplevel_page_charitable',
					'charitable_page_charitable-addons',
					'charitable_page_charitable-donors',
				)
			);
		}

		/**
		 * Updates the "action row" of campaigns to add the ability to edit with builder.
		 *
		 * @since  1.8.0
		 *
		 * @param array $actions Actions.
		 * @param array $post   Post data.
		 *
		 * @return array
		 */
		public function campaign_action_row( $actions, $post ) {

			$actions_first = array();

			$suffix = ( charitable_is_debug() || charitable_is_script_debug() ) ? '#charitabledebug' : false;

			// Check for the post type.

			if ( is_object( $post ) && isset( $post->post_type ) && 'campaign' === $post->post_type && ( ! isset( $_GET['post_status'] ) || ( isset( $_GET['post_status'] ) && 'trash' !== strtolower( $_GET['post_status'] ) ) ) ) { // phpcs:ignore

				$post_id = intval( $post->ID );

				// Determine if this is a "legacy" campaign.
				$is_legacy_campaign = charitable_is_campaign_legacy( $post_id );

				if ( isset( $actions['edit'] ) && 0 !== $post_id ) {

					$actions_show_id     = array( 'id' => esc_html__( 'ID: ', 'charitable' ) . $post_id );
					$actions_edit_legacy = array( 'edit' => $actions['edit'] );

					unset( $actions['edit'] );

				}

				if ( defined( 'CHARITABLE_BUILDER_SHOW_LEGACY_EDIT_LINKS' ) && CHARITABLE_BUILDER_SHOW_LEGACY_EDIT_LINKS === true ) {

					// Add "edit" link that goes to the builder. The campaign can still be edited w/ legacy via direct link (example https://website.test/wp-admin/post.php?post=246&action=edit).
					$actions_edit_legacy = array( 'Edit' => '<a href="' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '">' . esc_html__( 'Legacy Edit', 'charitable' ) . '</a>' );

					$actions = ( false === $is_legacy_campaign )
					? $actions_show_id + $actions_edit_legacy + array( 'charitable_builder' => '<a href="' . admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . $post_id ) . $suffix . '">' . esc_html__( 'Edit With Builder', 'charitable' ) . '</a>' ) + $actions
					: $actions_show_id + $actions_edit_legacy + $actions;

				} else {

					$actions = ( false === $is_legacy_campaign )
					? $actions_show_id + array( 'charitable_builder' => '<a href="' . admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . $post_id ) . $suffix . '">' . esc_html__( 'Edit', 'charitable' ) . '</a>' ) + $actions
					: $actions_show_id + $actions_edit_legacy + $actions;

				}
			}

			return $actions;
		}

		/**
		 * Updates the "post link" of campaigns to add the ability to edit with builder.
		 *
		 * @since  1.8.0
		 * @since  1.8.1 added check for get_current_screen().
		 *
		 * @param string  $link The current link URL.
		 * @param integer $post_id Post ID.
		 * @param array   $context Context.
		 *
		 * @return array
		 */
		public function campaign_link( $link, $post_id, $context ) { // phpcs:ignore

			if ( ! function_exists( 'get_current_screen' ) ) {
				return $link;
			}

			$screen  = get_current_screen();
			$post_id = intval( $post_id );

			if ( 0 === $post_id || ! isset( $screen->id ) || $screen->id !== 'edit-campaign' ) {
				return $link;
			}

			// Determine if this is a "legacy" campaign.
			$is_legacy_campaign = charitable_is_campaign_legacy( $post_id );

			$suffix = ( charitable_is_debug() || charitable_is_script_debug() ) ? '#charitabledebug' : false;

			$new_link = ( false === $is_legacy_campaign )
						? admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . $post_id ) . $suffix
						: esc_url( $link );

			return $new_link;
		}

		/**
		 * Filters the URL used for a campaign preview.
		 *
		 * @since 1.8.0
		 *
		 * @param string  $preview_link URL used for the post preview.
		 * @param WP_Post $post         Post object.
		 */
		public function campaign_preview_link( $preview_link, $post ) {

			if ( ! empty( $post ) && 'campaign' === $post->post_type ) {

				$preview_link = add_query_arg(
					array(
						'charitable_campaign_preview' => $post->ID,
					),
					$preview_link
				);

			}

			return $preview_link;
		}

		/**
		 * Adjustments needed for Elementor.
		 * Last tested with Elementor Lite 3.24.7.
		 *
		 * @since  1.8.3
		 *
		 * @param array $post_types_objects The post types.
		 *
		 * @return void
		 */
		public function elementor_post_types( $post_types_objects = array() ) {

			// If Elementor is not installed, ignore.
			if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
				return;
			}

			// Check for the Charitable Elementor integration override.
			if ( defined( 'CHARITABLE_ELEMENTOR_ALLOW_POST_TYPE' ) && CHARITABLE_ELEMENTOR_ALLOW_POST_TYPE ) {
				return;
			}

			$post_types_to_remove = apply_filters( 'charitable_elementor_post_types_to_remove', array( 'campaign' ) );

			if ( empty( $post_types_to_remove ) || ! is_array( $post_types_to_remove ) ) {
				return $post_types_objects;
			}

			// Remove all the post types from the object array.
			foreach ( $post_types_to_remove as $post_type ) {
				if ( isset( $post_types_objects[ $post_type ] ) ) {
					unset( $post_types_objects[ $post_type ] );
				}
			}

			return $post_types_objects;
		}

		/**
		 * Install addon via AJAX.
		 *
		 * @since 1.8.7.6
		 */
		public function install_addon() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// Check nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable_admin_addons' ) ) {
				wp_send_json_error( 'Invalid nonce' );
			}

			// Check permissions.
			if ( ! charitable_can_install( 'plugin' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}

			$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
			$type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $plugin ) ) {
				wp_send_json_error( 'Plugin not specified' );
			}

			// Ensure filesystem access (request credentials if needed) similar to legacy flow.
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$method = '';
			$url    = esc_url( admin_url( 'admin.php?page=charitable-about' ) );

			ob_start();
			$creds = request_filesystem_credentials( $url, $method, false, false, null );
			if ( false === $creds ) {
				$form = ob_get_clean();
				wp_send_json_success( array( 'form' => $form ) );
			}

			if ( ! WP_Filesystem( $creds ) ) {
				ob_start();
				request_filesystem_credentials( $url, $method, true, false, null );
				$form = ob_get_clean();
				wp_send_json_success( array( 'form' => $form ) );
			}

			// Download and install the plugin.
			$result = charitable_install_wporg_plugin( $plugin );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			// Get plugin basename.
			$plugin_basename = charitable_get_plugin_basename_from_slug( $plugin );

			wp_send_json_success( array(
				'msg'         => 'Plugin installed successfully',
				'basename'    => $plugin_basename,
				'is_activated' => false,
			) );
		}

		/**
		 * Activate addon via AJAX.
		 *
		 * @since 1.8.7.6
		 */
		public function activate_addon() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// Check nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable_admin_addons' ) ) {
				wp_send_json_error( 'Invalid nonce' );
			}

			// Check permissions.
			if ( ! charitable_can_activate( 'plugin' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}

			$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $plugin ) ) {
				wp_send_json_error( 'Plugin not specified' );
			}

			// Normalize plugin basename if a folder only or slug was passed.
			if ( false === strpos( $plugin, '/' ) ) {
				$plugin = charitable_get_plugin_basename_from_slug( $plugin );
			}

			// Activate the plugin.
			$result = charitable_activate_wporg_plugin( $plugin );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( 'Plugin activated successfully' );
		}

		/**
		 * Deactivate addon via AJAX.
		 *
		 * @since 1.8.7.6
		 */
		public function deactivate_addon() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// Check nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable_admin_addons' ) ) {
				wp_send_json_error( 'Invalid nonce' );
			}

			// Check permissions.
			if ( ! charitable_can_activate( 'plugin' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}

			$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $plugin ) ) {
				wp_send_json_error( 'Plugin not specified' );
			}

			// Deactivate the plugin.
			$result = charitable_deactivate_wporg_plugin( $plugin );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			wp_send_json_success( 'Plugin deactivated successfully' );
		}
	}

endif;
