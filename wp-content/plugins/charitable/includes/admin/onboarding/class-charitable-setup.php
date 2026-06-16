<?php
/**
 * Charitable Setup.
 *
 * @package   Charitable/Classes/Charitable_Setup
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.4
 * @version   1.8.4
 * @version   1.8.9.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Setup' ) ) :

	/**
	 * Charitable_Setup
	 *
	 * @final
	 * @since 1.8.4
	 */
	class Charitable_Setup {

		/**
		 * The steps.
		 *
		 * @var array
		 */
		private $steps = array( 'meta', 'plugins_installed', 'plugins_activated', 'payment_methods', 'features', 'campaign' );

		/**
		 * The plugin meta.
		 *
		 * @var array
		 */
		private $plugin_meta = array(
			'wp-mail-smtp'                   => array(
				'name'     => 'WP Mail SMTP',
				'basename' => 'wp-mail-smtp/wp_mail_smtp.php',
			),
			'optinmonster'                   => array(
				'name'     => 'OptinMonster',
				'basename' => 'optinmonster/optin-monster-wp-api.php',
			),
			'wpforms'                        => array(
				'name'     => 'WPForms',
				'basename' => 'wpforms/wpforms.php',
			),
			'google-analytics-for-wordpress' => array(
				'name'     => 'MonsterInsights',
				'basename' => 'google-analytics-for-wordpress/googleanalytics.php',
			),
			'all-in-one-seo-pack'            => array(
				'name'     => 'All in One SEO',
				'basename' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
			),
			'duplicator'                     => array(
				'name'     => 'Duplicator',
				'basename' => 'duplicator/duplicator.php',
			),
		);

		/**
		 * The feature meta.
		 *
		 * @since 1.8.4
		 *
		 * @var array
		 */
		private $feature_meta = array(
			'ambassadors' => array(
				'name' => 'Ambassadors',
			),
			'recurring'  => array(
				'name' => 'Recurring Donations',
			),
			'fee-relief' => array(
				'name' => 'Fee Relief',
			),
		);

		/**
		 * The private app_server_url property.
		 *
		 * @since 1.8.4
		 *
		 * @var string
		 */
		private $app_server_url = 'https://app.wpcharitable.com/';

		/**
		 * The private $stripe_redirect_url property.
		 *
		 * @var string
		 */
		private $stripe_redirect_url = '';

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Setup|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.4
		 */
		public function __construct() {

			add_action( 'plugins_loaded', [ $this, 'hooks' ] );

			$this->stripe_redirect_url = admin_url( 'admin.php?page=charitable-setup&setup=stripe-connect' );
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.4
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * The hooks.
		 *
		 * @since  1.8.4
		 */
		public function hooks() {

			// If user is in admin ajax or doing cron, return.
			if ( wp_doing_ajax() || wp_doing_cron() ) {
				return;
			}

			// If user cannot manage_options, return.
			if ( ! charitable_current_user_can( 'administrator' ) ) {
				return;
			}

			// Third party plugins.
			add_filter( 'duplicator_disable_onboarding_redirect', '__return_true' );

			// Resume onboarding.
			add_action( 'init', [ $this, 'resume_onboarding' ] );
			add_action( 'init', [ $this, 'cancel_onboarding' ] );

			// Init.
			add_action( 'admin_init', [ $this, 'redirect' ], 9999 );
		}

		/**
		 * Cancels the onboarding process.
		 *
		 * @since  1.8.4
		 *
		 * @return void
		 */
		public function cancel_onboarding() {

			if ( ! isset( $_GET['charitable_onboarding'] ) || 'cancel' !== $_GET['charitable_onboarding'] ) { // phpcs:ignore
				return;
			}

			// Clear the transient.
			delete_transient( 'charitable_activation_redirect' );

			// Clear the option.
			delete_option( 'charitable_started_onboarding' );

			// If the user was going to the checklist, send them there.
			$checklist_class = Charitable_Checklist::get_instance();
			$redirect_url    = ! $checklist_class->maybe_load_checklist_assets() ? admin_url( 'admin.php?page=charitable-dashboard' ) : admin_url( 'admin.php?page=charitable-setup-checklist' );

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * If the option 'charitable_start_onboarding' is set, then we will start the onboarding process, assuming the user lost connection, WP site logged out by the time app.wpcharitable.com came back, etc.
		 *
		 * @since  1.8.4
		 * @since  1.8.9.2 Added WordPress context awareness and smart page detection.
		 */
		public function resume_onboarding() {

			// Don't redirect during WordPress maintenance operations.
			if ( defined( 'DOING_AUTOSAVE' ) || wp_is_json_request() ||
			     defined( 'IFRAME_REQUEST' ) || ( function_exists( 'wp_doing_heartbeat' ) && wp_doing_heartbeat() ) ) {
				return;
			}

			// Check if we should consider redirection.
			if ( false === (bool) get_option( 'charitable_started_onboarding', false ) ) {
				return;
			}

			// Allow developers to disable resume onboarding via filter.
			if ( apply_filters( 'charitable_disable_activation_redirect', false ) ) {
				return;
			}

			// Don't redirect if user is on a non-Charitable admin page.
			if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'charitable' ) === false ) {
				return;
			}

			// Don't redirect from specific WordPress admin pages without page parameter.
			$current_page = basename( $_SERVER['PHP_SELF'] ?? '' );
			$wp_admin_pages = array( 'edit.php', 'post-new.php', 'upload.php', 'users.php', 'edit-tags.php', 'edit-comments.php', 'themes.php', 'plugins.php', 'tools.php', 'options-general.php' );
			if ( in_array( $current_page, $wp_admin_pages ) ) {
				return;
			}

			// Don't redirect if user is accessing post type or taxonomy pages.
			if ( isset( $_GET['post_type'] ) || isset( $_GET['taxonomy'] ) ) {
				return;
			}

			// Make sure we aren't already on the setup welcome page.
			if ( isset( $_GET['wpchar_lite'] ) && 'lite' === $_GET['wpchar_lite'] ) { // phpcs:ignore
				return;
			}

			// Don't redirect if user is trying to cancel onboarding - let cancel_onboarding() method handle it.
			if ( isset( $_GET['charitable_onboarding'] ) && 'cancel' === $_GET['charitable_onboarding'] ) { // phpcs:ignore
				return;
			}

			// Build the URL for going back to the onboarding process.
			$onboarding_url = 'https://app.wpcharitable.com/setup-wizard-charitable_lite&resume=' . charitable_get_site_token();

			wp_safe_redirect( admin_url( 'admin.php?page=charitable&wpchar_lite=lite&setup=welcome&resume=true' ) );
			exit;
		}

		/**
		 * Onboarding welcome screen redirect.
		 *
		 * This function checks if a new install or update has just occurred. If so,
		 * then we redirect the user to the appropriate page.
		 *
		 * @since 1.8.1.12
		 * @version 1.8.3 tweak what is an initial install.
		 * @version 1.8.4 add charitable_started_onboarding option.
		 * @version 1.8.9.2 Added WordPress filter and context awareness.
		 */
		public function redirect() {

			// Don't redirect during WordPress maintenance operations.
			if ( defined( 'DOING_AUTOSAVE' ) || wp_is_json_request() ||
			     defined( 'IFRAME_REQUEST' ) || ( function_exists( 'wp_doing_heartbeat' ) && wp_doing_heartbeat() ) ) {
				return;
			}

			// Check if we should consider redirection.
			if ( ! get_transient( 'charitable_activation_redirect' ) ) {
				return;
			}

			// If we are redirecting, clear the transient so it only happens once.
			delete_transient( 'charitable_activation_redirect' );

			// Check option to disable welcome redirect.
			if ( get_option( 'charitable_activation_redirect', false ) ) {
				return;
			}

			// Allow developers to disable activation redirect via filter.
			if ( apply_filters( 'charitable_disable_activation_redirect', false ) ) {
				return;
			}

			// Only do this for single site installs.
			if ( isset( $_GET['activate-multi'] ) || is_network_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			// Check if this is an update or first install.
			$upgrade = get_option( 'charitable_version_upgraded_from', [] );

			if ( ! $upgrade || 1 === count( $upgrade ) ) {
				// Initial install.
				update_option( 'charitable_started_onboarding', 1 );
				wp_safe_redirect( admin_url( 'admin.php?page=charitable&wpchar_lite=lite&setup=welcome&f=1' ) );
				exit;
			}
		}

		/**
		 * Enqueue assets.
		 *
		 * @since   1.8.4
		 */
		public function enqueue_scripts() {

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( $this->is_setup_page() ) {

				wp_enqueue_script(
					'campaign-admin-congrats-wizard-confetti',
					charitable()->get_path( 'directory', false ) . 'assets/js/libraries/confetti.min.js',
					[ 'jquery' ],
					$version,
					true
				);

			}

			if ( $this->is_setup_page() || $this->is_welcome_page() ) {

				wp_register_style(
					'charitable-admin-user-onboarding',
					$assets_dir . 'css/admin/charitable-admin-user-onboarding' . $min . '.css',
					array(),
					$version
				);

				wp_enqueue_style( 'charitable-admin-user-onboarding' );

				if ( $this->is_setup_page() ) :

					wp_register_script(
						'charitable-admin-user-onboarding',
						$assets_dir . 'js/admin/charitable-admin-user-onboarding' . $min . '.js',
						array( 'jquery', 'campaign-admin-congrats-wizard-confetti' ),
						$version,
						true
					);

					wp_enqueue_script( 'charitable-admin-user-onboarding' );

					wp_localize_script(
						'charitable-admin-user-onboarding',
						'charitable_setup',
						$this->get_localized_strings()
					);

				endif;

			}
		}

		/**
		 * Is this the setup page?
		 *
		 * @since 1.8.4
		 *
		 * @return bool
		 */
		public function is_setup_page() {
			return is_admin() && isset( $_GET['page'] ) && 'charitable-setup' === $_GET['page']; // phpcs:ignore
		}

		/**
		 * Is this the welcome page?
		 *
		 * @since 1.8.4
		 *
		 * @return bool
		 */
		public function is_welcome_page() {
			return is_admin() && isset( $_GET['page'] ) && 'charitable' === $_GET['page'] && isset( $_GET['wpchar_lite'] ) && 'lite' === $_GET['wpchar_lite'] && isset( $_GET['setup'] ) && ( 'cancelled' === $_GET['setup'] || 'welcome' === $_GET['setup'] || 'return' === $_GET['setup'] ); // phpcs:ignore
		}

		/**
		 * Maybe process the next step.
		 *
		 * @since 1.8.4
		 *
		 * @return void
		 */
		public function maybe_process_step() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-setup' ) ) { // phpcs:ignore
				wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			}

			$current_step = (string) $this->get_current_setup_step();

			if ( 'meta' === $current_step ) {
				$meta           = get_option( 'charitable_setup_meta', array() );
				$processed_meta = $this->process_meta( $meta );

				// we are also going to process the payment options (enable them, leaving a possible Stripe connect for later near the end).
				$payment_methods = $this->retrieve_payment_methods();
				$processed_pm    = $this->process_payment_methods( $payment_methods );
			}

			// return json success.
			wp_send_json_success(
				array(
					'status'         => 'success',
					'processed_step' => $current_step,
					'next_step'      => $this->get_next_step(),
				)
			);
			exit;
		}

		/**
		 * Get the next setup step by looking at the private $steps property and returning the next value.
		 *
		 * @param string $current_step The current step.
		 *
		 * @since  1.8.4
		 *
		 * @return string
		 */
		public function get_next_step( $current_step = '' ) {

			if ( ! $current_step ) {
				$current_step = $this->get_current_setup_step();
			}

			$steps = $this->steps;

			$next_step = '';

			foreach ( $steps as $key => $step ) {
				if ( $step === $current_step ) {
					$next_step = $steps[ $key + 1 ];
					break;
				}
			}

			return $next_step;
		}

		/**
		 * Get the current setup step.
		 * Possible values: meta, plugins, payment_methods, features.
		 *
		 * @since  1.8.4
		 *
		 * @return int
		 */
		public function get_current_setup_step() {

			return 'meta';
		}

		/**
		 * Register the page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function register_page() {
			add_dashboard_page(
				__( 'Setup Charitable', 'charitable' ),
				__( 'Setup Charitable', 'charitable' ),
				'manage_charitable_settings',
				'charitable-setup',
				array( $this, 'render_page' )
			);
		}

		/**
		 * Remove the page from the dashboard menu.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function remove_page_from_menu() {
			remove_submenu_page( 'index.php', 'charitable-setup' );
		}

		/**
		 * Render the page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function render_page() {

			$stripe_connect_url = '';
			$stripe_connected   = false;

			$stripe_returned = isset( $_GET['setup'] ) && 'stripe-connect' === $_GET['setup'] ? true : false; // phpcs:ignore

			if ( ! $stripe_returned ) {

				$payment_methods = $this->retrieve_payment_methods();

				if ( in_array( 'stripe', $payment_methods, true ) && class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {
					$stripe_gateway     = new Charitable_Gateway_Stripe_AM();
					$stripe_connect_url = ! $stripe_gateway->maybe_stripe_connected() ? $stripe_gateway->get_stripe_connect_url( $this->stripe_redirect_url ) : '';
					$stripe_connected   = $stripe_gateway->maybe_stripe_connected();
				}
			}

			// The "continue" or "return" URL should the checklist by default, but on the chance the checklist is completed, we'll send them to the dashboard.
			$checklist_class    = Charitable_Checklist::get_instance();
			$checklist_possible = $checklist_class->is_checklist_skipped() || $checklist_class->is_checklist_completed();
			$completed_url      = $checklist_possible ? admin_url( 'admin.php?page=charitable-dashboard' ) : admin_url( 'admin.php?page=charitable-setup-checklist' );

			// If the checklist is not an option, check and see if they created a campaign. If so, send them to that draft campaign.
			$campaign_admin_url = false !== get_option( 'charitable_setup_campaign_created', false )
			? admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . intval( get_option( 'charitable_setup_campaign_created', 0 ) ) . '&view=design' )
			: false;

			charitable_admin_view(
				'onboarding/setup',
				array(
					'page'               => $this,
					'stripe_connect_url' => $stripe_connect_url,
					'stripe_connected'   => $stripe_connected,
					'stripe_returned'    => $stripe_returned,
					'completed_url'      => $completed_url,
					'campaign_admin_url' => $campaign_admin_url,
				)
			);
		}

		/**
		 * Store the meta.
		 *
		 * @param array $meta The meta.
		 *
		 * @since  1.8.4
		 */
		public function store_meta( $meta = array() ) {

			update_option( 'charitable_setup_meta', $meta, false );
		}

		/**
		 * Store plugin data.
		 *
		 * @param array $plugins The plugins.
		 *
		 * @since  1.8.4
		 */
		public function store_plugins( $plugins = array() ) {

			update_option( 'charitable_setup_plugins', $plugins, false );
		}

		/**
		 * Retrieve the plugin data that was stored.
		 *
		 * @since  1.8.4
		 *
		 * @return array
		 */
		public function retrieve_plugins() {

			$plugins = get_option( 'charitable_setup_plugins', array() );

			if ( ! is_array( $plugins ) ) {
				return array();
			}

			$plugins = array_map( 'sanitize_text_field', $plugins );

			if ( empty( $plugins ) ) {
				return array();
			}

			return $plugins;
		}

		/**
		 * Return plugins that need to be installed potentially.
		 *
		 * @since  1.8.4
		 *
		 * @return array
		 */
		public function plugins_to_install() {

			$plugins = $this->retrieve_plugins();

			if ( empty( $plugins ) ) {
				return array();
			}

			$plugins = array_map(
				function ( $plugin ) {
					// is this plugin already installed in the WordPress site?
					if ( ! $this->is_plugin_installed( $plugin ) ) {
						return $plugin;
					}
				},
				$plugins
			);

			// remove any null values and remove keys.
			$plugins = array_values( array_filter( $plugins ) );

			return $plugins;
		}

		/**
		 * Return plugins that need to be activated potentially.
		 *
		 * @since  1.8.4
		 *
		 * @return array
		 */
		public function plugins_to_activate() {

			$plugins = $this->retrieve_plugins();

			if ( empty( $plugins ) ) {
				return array();
			}

			// check if the plugin is installed and activated. If not, add it to the list of plugins to activate.
			$plugins = array_map(
				function ( $plugin ) {
					// is this plugin already installed in the WordPress site?
					if ( $this->is_plugin_installed( $plugin ) ) {
						// is this plugin already activated?
						if ( ! is_plugin_active( $plugin ) ) {
							return $plugin;
						}
					}
				},
				$plugins
			);

			// remove any null values and reindex the array.
			$plugins = array_values( array_filter( $plugins ) );

			return $plugins;
		}

		/**
		 * Returns stored "features".
		 *
		 * @since  1.8.4
		 *
		 * @return array
		 */
		public function features_to_install() {

			$features = get_option( 'charitable_setup_features', array() );

			if ( ! is_array( $features ) ) {
				return array();
			}

			$features = array_map( 'sanitize_text_field', $features );

			if ( empty( $features ) ) {
				return array();
			}

			return $features;
		}

		/**
		 * Activate "features" via ajax call.
		 *
		 * @since  1.8.4
		 *
		 * @return void
		 */
		public function ajax_activate_feature() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-admin' ) ) { // phpcs:ignore
				wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
			}

			$plugin_basename = isset( $_POST['slug'] ) ? 'charitable-' . sanitize_text_field( $_POST['slug'] ) : ''; // phpcs:ignore

			if ( ! $plugin_basename ) {
				wp_send_json_error( array( 'message' => 'Invalid feature.' ) );
			}

			// override a few slug/names here.
			if ( $plugin_basename === 'charitable-recurring' ) {
				$plugin_basename = 'charitable-recurring-donations';
			}

			$is_pro = charitable_is_pro();

			if ( ! $is_pro ) {
				wp_send_json_error(
					array(
						'message' => 'Invalid access.',
						'is_pro'  => $is_pro,
						'error'   => 'test',
					)
				);
			}

			$plugin_slug = $plugin_basename . '/' . $plugin_basename . '.php';

			// is this plugin already installed?
			if ( is_plugin_active( $plugin_basename ) ) {
				wp_send_json_success(
					array(
						'message' => 'Already installed and activated',
						'error'   => 'feature-plugin-success',
					)
				);
				exit;
			}

			// is this plugin installed and just needs to be activated?
			if ( $this->is_plugin_installed( $plugin_slug ) ) {
				$activate = activate_plugin( $plugin_slug );
				if ( is_wp_error( $activate ) ) {
					wp_send_json_error(
						array(
							'message' => $activate->get_error_message(),
							'error'   => 'feature-plugin-error',
						)
					);
					exit;
				} else {
					wp_send_json_success(
						array(
							'message' => "Plugin '{$plugin_slug}' activated successfully.",
							'error'   => 'feature-plugin-success',
						)
					);
					exit;
				}
			}

			// ok, so it's not installed at all? Let's install it - and activate it.

			// check if the feature is already installed and if not install it.
			$addons       = charitable_get_addons_data_from_server();
			$download_url = '';

			if ( $addons ) {

				foreach ( (array) $addons as $i => $addon ) {

					if ( ! isset( $addon['slug'] ) || $addon['slug'] === '' || strtolower( $addon['slug'] ) === 'auto draft' ) {
						continue;
					}

					if ( $addon['slug'] === $plugin_basename ) {
						$addon_slug   = $addon['slug'];
						$download_url = ! empty( $addon['install'] ) ? esc_url( $addon['install'] ) : false;
						break;
					}

				}

			}

			if ( ! $download_url ) {
				wp_send_json_error(
					array(
						'message' => 'Feature not found.',
						'error'   => 'feature-not-found',
					)
				);
			}

			// we need to convery &#038; to & in the download_url.
			$download_url = str_replace( '&#038;', '&', $download_url );

			// Prepare variables.
			$method = '';
			$url    = add_query_arg(
				array(
					'page' => 'charitable-settings',
				),
				admin_url( 'admin.php' )
			);
			$url    = esc_url( $url );

			// Start output bufferring to catch the filesystem form if credentials are needed.
			ob_start();
			$creds = request_filesystem_credentials( $url, $method, false, false, null );
			if ( false === $creds ) {
				$form = ob_get_clean();
				echo wp_json_encode( array( 'form' => $form ) );
				die;
			}

			// If we are not authenticated, make it happen now.
			if ( ! WP_Filesystem( $creds ) ) {
				ob_start();
				request_filesystem_credentials( $url, $method, true, false, null );
				$form = ob_get_clean();
				echo wp_json_encode( array( 'form' => $form ) );
				die;
			}

			// Do not allow WordPress to search/download translations, as this will break JS output.
			remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

			// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once plugin_dir_path( CHARITABLE_DIRECTORY_PATH ) . 'charitable/includes/utilities/Skin.php';

			// Create the plugin upgrader with our custom skin.
			$skin      = new Charitable_Skin();
			$installer = new Plugin_Upgrader( $skin );
			$installer->install( $download_url );

			// Flush the cache and return the newly installed plugin basename.
			wp_cache_flush();

			if ( $installer->plugin_info() ) {
				$plugin_basename = $installer->plugin_info();
				$plugin          = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';
				// attempt to activate the installed addon, save the user a step.
				$activate = activate_plugins( $plugin_basename );
				if ( is_plugin_active( $plugin_basename ) ) {
					wp_send_json_success(
						array(
							'message' => "Plugin '{$plugin_slug}' activated successfully.",
							'error'   => 'feature-plugin-success',
						)
					);
				} else {
					wp_send_json_error(
						array(
							'message' => "Failed to activate plugin '{$plugin_slug}'.",
							'error'   => 'feature-plugin-error',
						)
					);
				}
				exit;
			} else {

				wp_send_json_error(
					array(
						'message' => "Failed issue with installing plugin '{$plugin_slug}'.",
						'error'   => 'feature-plugin-error',
					)
				);

			}
		}

		/**
		 * Get license key.
		 *
		 * @param string $license_key The license key.
		 *
		 * @since  1.8.4
		 */
		public function get_license_key( $license_key = '' ) { // phpcs:ignore

			return get_option( 'charitable_setup_license_key', '' );
		}

		/**
		 * Store license key.
		 *
		 * @param string $license_key The license key.
		 *
		 * @since  1.8.4
		 */
		public function store_license_key( $license_key = '' ) {

			update_option( 'charitable_setup_license_key', $license_key, '' );
		}

		/**
		 * Store payment methods.
		 *
		 * @param array $payment_methods The payment methods.
		 *
		 * @since  1.8.4
		 */
		public function store_payment_methods( $payment_methods = array() ) {

			update_option( 'charitable_setup_payment_methods', $payment_methods, false );
		}

		/**
		 * Store features.
		 *
		 * @param array $features The features.
		 *
		 * @since  1.8.4
		 */
		public function store_features( $features = array() ) {

			update_option( 'charitable_setup_features', $features, false );
		}

		/**
		 * Store campaign info for setup.
		 *
		 * @param array $campaign The campaign.

		 * @since  1.8.4
		 */
		public function store_campaign( $campaign = array() ) {

			update_option( 'charitable_setup_campaign', $campaign, false );
		}

		/**
		 * Determines if we can skip building a new campaign.
		 *
		 * @since  1.8.4
		 *
		 * @return bool
		 */
		public function maybe_skip_campaign_setup() {

			// Check for permissions.
			if ( ! charitable_current_user_can( 'edit_campaigns' ) ) {
				return false;
			}

			$campaign_data = $this->retrieve_campaign();

			if ( ! $campaign_data ) {
				return true;
			}

			return false;
		}

		/**
		 * Processing opt-in tracking requests.
		 *
		 * @since  1.8.4
		 *
		 * @return void
		 */
		public function ajax_process_tracking() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-admin' ) ) { // phpcs:ignore
				wp_send_json_error(
					array(
						'message' => 'Invalid nonce.',
						'error'   => 'invalid-nonce',
					)
				);
			}

			// Check for permissions.
			if ( ! charitable_current_user_can( 'administrator' ) ) {
				wp_send_json_error(
					array(
						'message' => 'You are not allowed to perform this action.',
						'error'   => 'permission-denied',
					)
				);
			}

			// checking for the action.
			if ( ! isset( $_POST['action'] ) ) {
				wp_send_json_error(
					array(
						'message' => 'Invalid tracking action.',
						'error'   => 'invalid-tracking-action',
					)
				);
			}

			if ( ! isset( $_POST['tracking_action'] ) || ! in_array( $_POST['tracking_action'], array( 'opt-in', 'opt-out' ), true ) ) {
				wp_send_json_error(
					array(
						'message' => 'Invalid tracking action.',
						'error'   => 'invalid-tracking-action',
					)
				);
			}

			// setup variables.
			$opt_in_tracking = empty( $_POST['tracking_action'] ) || $_POST['tracking_action'] === 'opt-out' ? '0' : '1';
			$email           = update_option( 'charitable_email_signup', false ) ? get_option( 'charitable_email_signup' ) : get_option( 'admin_email' );

			// assemble a url to the setup wizard using WordPress function.
			$tracking_update_url = add_query_arg(
				array(
					'email'        => is_email( $email ) ? $email : false,
					'opt_in_track' => $opt_in_tracking,
					'site_token'   => charitable_get_site_token(),
					'return'       => rawurlencode(
						base64_encode( get_admin_url( null, 'admin.php') ) // phpcs:ignore
					),
				),
				$this->app_server_url . 'optin-charitable_tracking'
			);

			// Send the POST request.
			$response = wp_remote_get(
				$tracking_update_url,
				[
					'timeout'   => 45,
					'sslverify' => false,
				]
			);

			// Get the http status code.
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $response_code ) {
				wp_send_json_error(
					array(
						'message' => 'Error updating tracking.',
						'error'   => 'tracking-error',
					)
				);
				exit;
			}

			if ( is_wp_error( $response ) ) {
				// Handle the error.
				$error_message = $response->get_error_message();
				wp_send_json_error(
					array(
						'message' => 'Error updating tracking: ' . $error_message,
						'error'   => 'tracking-error',
					)
				);
				exit;
			}

			$response_args   = json_decode( $response['body'] );
			$return_url      = isset( $response_args->return_url ) ? $response_args->return_url : false;
			$site_token      = isset( $response_args->site_token ) ? $response_args->site_token : false;
			$opt_in_tracking = isset( $response_args->opt_in_tracking ) ? intval( $response_args->opt_in_tracking ) : false;

			// the site token should match the site token we have.
			if ( $site_token !== charitable_get_site_token() ) {
				wp_send_json_error(
					array(
						'message' => 'Error updating tracking: Invalid site token.',
						'error'   => 'tracking-error',
					)
				);
				exit;
			}

			// flip the $opt_in_tracking variable... if it's opt-in, we want to opt-out and vice versa.
			$opt_in_tracking_setting = ( 0 === intval( $opt_in_tracking ) ) ? false : true;

			charitable_update_usage_tracking_setting( $opt_in_tracking_setting );

			// redirect to the return url.
			wp_send_json_success(
				array(
					'text'            => $opt_in_tracking === 0 ? 'Allow And Continue' : 'Opt Out',
					'return'          => $return_url,
					'tracking_action' => $opt_in_tracking === 0 ? '' : 'joined',
					'opt_in_tracking' => $opt_in_tracking === 0 ? false : true,
				)
			);

			exit;
		}

		/**
		 * Process campaign.
		 *
		 * @since  1.8.4
		 *
		 * @return void
		 */
		public function ajax_process_campaign() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-setup' ) ) { // phpcs:ignore
				wp_send_json_error(
					array(
						'message' => 'Invalid nonce.',
						'error'   => 'invalid-nonce',
					)
				);
			}

			// Check for permissions.
			if ( ! charitable_current_user_can( 'edit_campaigns' ) ) {
				wp_send_json_error(
					array(
						'message' => 'You are not allowed to perform this action.',
						'error'   => 'permission-denied',
					)
				);
			}

			$campaign_data = $this->retrieve_campaign();

			if ( ! $campaign_data ) {
				wp_send_json_error(
					array(
						'message'            => 'No campaign.',
						'error'              => 'invalid-campaign',
						'processed_campaign' => 'false',
					)
				);
			}

			// Remove any previous one, if it exists.
			delete_option( 'charitable_setup_campaign_created' );

			$campaign_template    = isset( $campaign_data['template'] ) ? sanitize_text_field( $campaign_data['template'] ) : ''; // example: animal-sanctuary.
			$campaign_title       = isset( $campaign_data['title'] ) ? sanitize_text_field( $campaign_data['title'] ) : '';
			$campaign_description = isset( $campaign_data['description'] ) ? sanitize_text_field( $campaign_data['description'] ) : '';
			$campaign_goal        = isset( $campaign_data['goal'] ) ? sanitize_text_field( $campaign_data['goal'] ) : '0';

			// if $campaign_end_date is a validated date, then we can convert it to a proper date format "2025-01-25 23:59:59". If it's not a valid date, then we can set it to 0.
			$campaign_end_date = isset( $campaign_data['end_date'] ) ? sanitize_text_field( $campaign_data['end_date'] ) : 0;
			$campaign_end_date = strtotime( $campaign_end_date ) ? gmdate( 'Y-m-d H:i:s', strtotime( $campaign_end_date ) ) : 0;

			// Generate the campaign in the datbase so we can get the database ID.
			$campaign_id = charitable_create_campaign(
				array(
					'title'    => $campaign_title,
					'content'  => $campaign_description,
					'creator'  => get_current_user_id(),
					'end_date' => $campaign_end_date,
					'goal'     => $campaign_goal,
					'status'   => 'draft',
				)
			);

			// Process the campaign, which has it's roots back to legacy campaigns.
			$processor              = new Charitable_Campaign_Processor();
			$processor->campaign_id = $campaign_id;

			// taken from class-charitable-campaign-processor.php.
			$campaign_meta_keys = array(
				'goal'     => $campaign_goal,
				'end_date' => $campaign_end_date,
			);
			foreach ( $campaign_meta_keys as $meta_key => $meta_value ) {
				$processor->save_meta_field( $meta_value, $meta_key );
			}

			$campaign_data = array(
				'title' => $campaign_title,
			);

			// get the template settings.
			$builder_template  = new Charitable_Campaign_Builder_Templates();
			$template_settings = $builder_template->get_template_data( $campaign_template, $campaign_data );

			// Update (overwrite) the campaign settings with the new values.
			$campaign_settings_v2 = [];

			$campaign_settings_v2['layout'] = $this->transform_layout_structure( $template_settings['layout'] ); // Updated.

			$campaign_settings_v2['id']                   = $campaign_id;
			$campaign_settings_v2['template_id']          = $template_settings['meta']['slug'];
			$campaign_settings_v2['template_label']       = $template_settings['meta']['label'];
			$campaign_settings_v2['color_base_primary']   = $template_settings['meta']['colors']['primary'];
			$campaign_settings_v2['color_base_secondary'] = $template_settings['meta']['colors']['secondary'];
			$campaign_settings_v2['color_base_tertiary']  = $template_settings['meta']['colors']['tertiary'];
			$campaign_settings_v2['color_base_button']    = $template_settings['meta']['colors']['button_bg'];

			$campaign_settings_v2['layout']['advanced']['theme_color_primary']   = $template_settings['meta']['colors']['primary'];
			$campaign_settings_v2['layout']['advanced']['theme_color_secondary'] = $template_settings['meta']['colors']['secondary'];
			$campaign_settings_v2['layout']['advanced']['theme_color_tertiary']  = $template_settings['meta']['colors']['tertiary'];
			$campaign_settings_v2['layout']['advanced']['theme_color_button']    = $template_settings['meta']['colors']['button_bg'];

			$campaign_settings_v2['layout']['advanced']['tab_style']        = $template_settings['advanced']['tab_style'];
			$campaign_settings_v2['layout']['advanced']['tab_size']         = $template_settings['advanced']['tab_size'];
			$campaign_settings_v2['layout']['advanced']['show_field_names'] = 'hide'; // $template_settings['advanced']['show_field_names'];
			$campaign_settings_v2['layout']['advanced']['preview_mode']     = $template_settings['advanced']['preview_mode'];

			$campaign_settings_v2['post_status']       = 'draft';
			$campaign_settings_v2['post_status_label'] = 'Draft';
			$campaign_settings_v2['title']             = $campaign_title;

			$campaign_settings_v2['fields']   = $this->campaign_map_fields( $template_settings );
			$campaign_settings_v2['tabs']     = $this->get_tabs_from_template_for_campaign_settings( $template_settings );
			$campaign_settings_v2['field_id'] = count( $campaign_settings_v2['fields'] ); // ?????

			$campaign_settings_v2['settings']                                    = [];
			$campaign_settings_v2['settings']['general']['description']          = $campaign_description;
			$campaign_settings_v2['settings']['general']['extended_description'] = '';
			$campaign_settings_v2['settings']['general']['form_css_class']       = '';
			$campaign_settings_v2['settings']['general']['goal']                 = '0' === $campaign_goal ? '' : Charitable_Currency::get_instance()->sanitize_monetary_amount( (string) ( $campaign_goal ) );
			$campaign_settings_v2['settings']['general']['end_date']             = Charitable_Campaign::sanitize_campaign_end_date( $campaign_end_date );
			$campaign_settings_v2['settings']['general']['campaign_creator_id']  = get_current_user_id();

			$campaign_settings_v2['settings']['donation-options'] = $this->get_donation_options();

			$campaign_settings_v2['campaign_id'] = $campaign_id;

			$campaign_settings_v2['tab_order'] = array(
				0 => 0,
				1 => 1,
				2 => 2,
			);

			// Allow addons to update, sync, or override settings before they are saved.
			$campaign_settings_v2 = apply_filters( 'charitable_campaign_builder_save_campaign_settings', $campaign_settings_v2, $campaign_id );

			// Save the campaign settings.
			$updated = update_post_meta( $campaign_id, 'campaign_settings_v2', $campaign_settings_v2 );

			if ( ! $updated ) {

				wp_send_json_error(
					array(
						'message' => 'Error saving campaign settings.',
						'error'   => 'error-saving-campaign-settings',
					)
				);
			} else {

				// Create a (temp) option that marks the creation of the campaign during the setup process so we can direct the user back to the campaign.

				update_option( 'charitable_setup_campaign_created', $campaign_id, false );

				if ( class_exists( 'Charitable_Checklist' ) ) {
					$checklist_class = Charitable_Checklist::get_instance();
					$checklist_class->mark_step_completed( 'first-campaign' );
				}

				wp_send_json_success(
					array(
						'message' => 'Campaign created.',
						'status'  => 'campaign-created',
					)
				);
			}

			exit;
		}

		/**
		 * Convert layout structure to the new format.
		 *
		 * @since  1.8.4
		 * @param array $original_layout The original layout.
		 *
		 * @return array
		 */
		public function transform_layout_structure( $original_layout ) {
			$new_layout = [
				'rows' => [],
			];

			$field_counter      = 0;
			$field_type_counter = 0;
			$section_counter    = 0;
			$columns_counter    = 0;
			$alt_index_starter  = 0;

			foreach ( $original_layout as $row_index => $row ) {
				if ( ! is_numeric( $row_index ) ) {
					continue;
				}

				$new_row = [
					'type'      => $row['type'],
					'css_class' => $row['css_class'] ?? 'no-css',
					'columns'   => [],
					'fields'    => [],
				];

				$alt_number = 0;

				// Process columns.
				foreach ( $row['columns'] as $column_index => $column ) {
					$new_column = [
						'sections' => [],
					];

					foreach ( $column as $section ) {
						if ( $section['type'] === 'fields' ) {
							$new_section = [
								'type'   => 'fields',
								'fields' => [],
							];

							$field_indices = [];
							foreach ( $section['fields'] as $field ) {
								// Add to row's fields array.
								$new_row['fields'][ $field_type_counter ] = $field['type'];
								// Store the index for this field.
								$field_indices[] = $field_counter;
								++$field_counter;
								++$field_type_counter;
							}

							$alt_index_starter = count( $new_row['fields'] );

							$new_section['fields']                      = $field_indices;
							$new_column['sections'][ $section_counter ] = $new_section;

							++$section_counter;

						} elseif ( $section['type'] === 'tabs' ) {
							$new_section = [
								'type' => 'tabs',
								'tabs' => [],
							];

							foreach ( $section['tabs'] as $tab ) {
								$new_tab = [
									'title'  => $tab['title'],
									'type'   => 'fields',
									'slug'   => $tab['slug'] ?? '',
									'fields' => [],
								];

								$field_indices = [];
								if ( ! empty( $tab['fields'] ) ) {
									foreach ( $tab['fields'] as $field ) {
										// Add to row's fields array.
										$new_row['fields'][ $field_type_counter ] = $field['type'];
										// Store the index for this field.
										$field_indices[] = $field_counter;
										++$field_counter;
										++$field_type_counter;
									}
								}

								$new_tab['fields']     = $field_indices;
								$new_section['tabs'][] = $new_tab;
							}

							$new_column['sections'][ $section_counter ] = $new_section;

							++$section_counter;
						}
					}

					$new_row['columns'][ $columns_counter ] = $new_column;
					++$columns_counter;
				}

				$new_layout['rows'][] = $new_row;
			}

			return $new_layout;
		}

		/**
		 * Get the tabs from the template for the campaign settings.
		 *
		 * @param array $template_settings The template settings.
		 *
		 * @since  1.8.4
		 * @version 1.8.9.7
		 *
		 * @return array
		 */
		public function get_tabs_from_template_for_campaign_settings( $template_settings ) {

			$tabs = [];

			foreach ( $template_settings['layout'] as $row ) {
				foreach ( $row as $column ) {
					if ( ! is_array( $column ) ) {
						continue;
					}
					foreach ( $column as $section ) {
						if ( ! is_array( $section ) ) {
							continue;
						}
						foreach ( $section as $mini_sections ) {
							if ( ! is_array( $mini_sections ) || ! isset( $mini_sections['tabs'] ) ) {
								continue;
							}
							foreach ( $mini_sections['tabs'] as $found_tabs ) {
									$tabs[] = $found_tabs;
							}
						}
					}
				}
			}

			$returned_tabs = [];
			foreach ( $tabs as $index => $tab ) {
				$returned_tabs[] = array(
					'title' => isset( $tab['title'] ) ? $tab['title'] : '',
				);
			}

			return $returned_tabs;
		}

		/**
		 * Map the "fields" option in the root of the array from the template to the campaign settings.
		 *
		 * @since  1.8.4
		 * @param array $input The input (campaign array).
		 *
		 * @return array
		 */
		public function campaign_map_fields( $input ) {
			$output = array( 'fields' => array() );

			// Check if 'layout' exists and is an array.
			if ( isset( $input['layout'] ) && is_array( $input['layout'] ) ) {
				foreach ( $input['layout'] as $layout_item ) {
					// Check if 'columns' exists and is an array.
					if ( isset( $layout_item['columns'] ) && is_array( $layout_item['columns'] ) ) {
						foreach ( $layout_item['columns'] as $column ) {
							if ( is_array( $column ) ) {
								foreach ( $column as $column_item ) {
									// Check if 'fields' exists and is an array.
									if ( isset( $column_item['fields'] ) && is_array( $column_item['fields'] ) ) {
										foreach ( $column_item['fields'] as $field ) {
											// Process each field.
											$mapped_field       = $this->process_field( $field );
											$output['fields'][] = $mapped_field;
										}
									} elseif ( isset( $column_item['tabs'] ) && is_array( $column_item['tabs'] ) ) { // Check if 'tabs' exists (e.g., in the second layout item).
										foreach ( $column_item['tabs'] as $tab ) {
											if ( isset( $tab['fields'] ) && is_array( $tab['fields'] ) ) {
												foreach ( $tab['fields'] as $field ) {
													$mapped_field       = $this->process_field( $field );
													$output['fields'][] = $mapped_field;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}

			return $output['fields'];
		}

		/**
		 * Process the field from the campaign_map_fields function.
		 *
		 * @since  1.8.4
		 * @param array $field The field.
		 *
		 * @return array
		 */
		public function process_field( $field ) {
			$mapped_field = array();

			// Get the field type.
			$type = isset( $field['type'] ) ? $field['type'] : '';

			// Map fields based on the type.
			switch ( $type ) {
				case 'photo':
					$mapped_field['file']             = isset( $field['file'] ) ? $field['file'] : '';
					$mapped_field['alt_text']         = isset( $field['alt_text'] ) ? $field['alt_text'] : '';
					$mapped_field['default']          = isset( $field['default'] ) ? $field['default'] : '';
					$mapped_field['width_percentage'] = isset( $field['width_percentage'] ) ? $field['width_percentage'] : 100;
					$mapped_field['align']            = isset( $field['align'] ) ? $field['align'] : 'center';
					$mapped_field['css_class']        = isset( $field['css_class'] ) ? $field['css_class'] : '';
					break;

				case 'campaign-description':
				case 'text':
					$mapped_field['headline']         = isset( $field['headline'] ) ? $field['headline'] : '';
					$mapped_field['content']          = isset( $field['content'] ) ? $field['content'] : '';
					$mapped_field['width_percentage'] = isset( $field['width_percentage'] ) ? $field['width_percentage'] : 100;
					$mapped_field['align']            = isset( $field['align'] ) ? $field['align'] : 'left';
					$mapped_field['css_class']        = isset( $field['css_class'] ) ? $field['css_class'] : '';
					break;

				case 'progress-bar':
				case 'campaign-summary':
					$mapped_field['headline']  = isset( $field['headline'] ) ? $field['headline'] : '';
					$mapped_field['show_hide'] = array();
					if ( isset( $field['show_hide'] ) ) {
						foreach ( $field['show_hide'] as $key => $value ) {
							if ( $value ) {
								$mapped_field['show_hide'][ $key ] = $key;
							}
						}
					}
					$mapped_field['meta_position']    = isset( $field['meta_position'] ) ? $field['meta_position'] : '';
					$mapped_field['label_donate']     = isset( $field['label_donate'] ) ? $field['label_donate'] : '';
					$mapped_field['label_goal']       = isset( $field['label_goal'] ) ? $field['label_goal'] : '';
					$mapped_field['width_percentage'] = isset( $field['width_percentage'] ) ? $field['width_percentage'] : 100;
					$mapped_field['align']            = isset( $field['align'] ) ? $field['align'] : 'left';
					$mapped_field['css_class']        = isset( $field['css_class'] ) ? $field['css_class'] : '';
					break;

				case 'donate-button':
					$mapped_field['button_label']     = isset( $field['button_label'] ) ? $field['button_label'] : '';
					$mapped_field['width_percentage'] = isset( $field['width_percentage'] ) ? $field['width_percentage'] : 100;
					$mapped_field['align']            = isset( $field['align'] ) ? $field['align'] : 'left';
					$mapped_field['css_class']        = isset( $field['css_class'] ) ? $field['css_class'] : '';
					break;

				default:
					// If the type is unrecognized, you can decide how to handle it.
					$mapped_field = $field;
					break;
			}

			return $mapped_field;
		}

		/**
		 * Setup default donation options ready for the campaign.
		 *
		 * @since  1.8.4
		 *
		 * @return array
		 */
		public function get_donation_options() {

			return [

				'donation_amounts'            => [
					1 => [
						'amount'      => 5,
						'description' => 'This is a small donation.',
					],
					2 => [
						'amount'      => 10,
						'description' => 'This is a slightly larger donation.',
					],
					3 => [
						'amount'      => 15,
						'description' => 'This is a larger (and default) donation.',
					],
					4 => [
						'amount'      => 20,
						'description' => 'This is a large donation.',
					],
				],
				'suggested_donations_default' => 3,
				'minimum_donation_amount'     => null,
				'allow_custom_donations'      => 1,

			];
		}


		/**
		 * Clean up and mark the setup as complete.
		 *
		 * @since   1.8.4
		 * @version 1.8.5 - Added charitable_activate_pro option.
		 *
		 * @return void
		 */
		public function ajax_process_complete() {

			// security check.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'charitable-setup' ) ) { // phpcs:ignore
				wp_send_json_error(
					array(
						'message' => 'Invalid nonce.',
						'error'   => 'invalid-nonce',
					)
				);
			}

			// CRITICAL SAFETY CHECK: Only set Pro activation if Pro plugin actually exists
			$pro_plugin_path     = WP_PLUGIN_DIR . '/charitable-pro/charitable-pro.php';
			$should_activate_pro = file_exists( $pro_plugin_path );

			// remove the transient, set the option.
			delete_transient( 'charitable_ss_onboarding' );
			update_option( 'charitable_ss_complete', true );

			// Only set Pro activation if Pro is actually available
			if ( $should_activate_pro ) {
				update_option( 'charitable_activate_pro', true );
			} else {
			// Log this for debugging
			error_log( 'Charitable: Pro plugin not found during setup completion - skipping Pro activation' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			wp_send_json_success(
				array(
					'message' => 'Setup completed.',
					'status'  => 'setup-completed',
				)
			);
		}

		/**
		 * Retrieve campaign.
		 *
		 * @since 1.8.4
		 *
		 * @return mixed
		 */
		public function retrieve_campaign() {

			$campaign = get_option( 'charitable_setup_campaign', array() );

			if ( ! is_array( $campaign ) ) {
				return array();
			}

			$campaign = array_map( 'sanitize_text_field', $campaign );
			$campaign = array_map( 'trim', $campaign );

			if ( empty( $campaign ) ) {
				return array();
			}

			// check and see if we have the vital fields (a campaign name and a template).
			if ( ! key_exists( 'title', $campaign ) || ! key_exists( 'template', $campaign ) ) {
				return false;
			}
			if ( empty( $campaign['title'] ) || empty( $campaign['template'] ) ) {
				return false;
			}

			return $campaign;
		}

		/**
		 * Retrieve payment methods.
		 *
		 * @since 1.8.4
		 *
		 * @return mixed
		 */
		public function retrieve_payment_methods() {

			$payment_methods = get_option( 'charitable_setup_payment_methods', array() );

			if ( ! is_array( $payment_methods ) ) {
				return array();
			}

			$payment_methods = array_map( 'sanitize_text_field', $payment_methods );

			if ( empty( $payment_methods ) ) {
				return array();
			}

			return $payment_methods;
		}

		/**
		 * Helper function to determine if a plugin is installed.
		 *
		 * @since 1.8.4
		 * @param string $plugin_slug The plugin slug.
		 *
		 * @return mixed
		 */
		public function is_plugin_installed( $plugin_slug ) {

			// Get the full path to the plugins directory.
			$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;

			// Check if the directory exists for the given plugin slug.
			return is_dir( $plugin_dir );
		}

		/**
		 * Process meta.
		 *
		 * @since   1.8.4
		 * @version 1.8.4.2 - Upon successful tracking opt-in, send initial usage information.
		 *
		 * @param array $meta The meta to process.
		 * @return bool
		 */
		public function process_meta( $meta = array() ) {

			if ( ! $meta ) {
				return false;
			}

			$meta = array_map( 'sanitize_text_field', $meta );

			// Define vars to determine if we can/should check off checklist items depending on what is updated from onboarding.
			$checklist_general_settings_completed = false;
			$checklist_email_settings_completed   = false;

			// step 2, country, currency, decimal count.

			if ( key_exists( 'country', $meta ) ) {
				$checklist_general_settings_completed = true;
				$this->set_charitable_option( 'country', $meta['country'] );
			}

			if ( key_exists( 'currency', $meta ) ) {
				$checklist_general_settings_completed = true;
				$this->set_charitable_option( 'currency', $meta['currency'] );
			}

			if ( key_exists( 'decimal_count', $meta ) ) {
				$checklist_general_settings_completed = true;
				$this->set_charitable_option( 'decimal_count', $meta['decimal'] );
			}

			// step 2, email settings.
			if ( key_exists( 'email_donor_notification', $meta ) ) {
				$checklist_email_settings_completed = true;
				$this->set_charitable_email_option( 'email_donor_notification' );
			}
			if ( key_exists( 'email_admin_notification', $meta ) ) {
				$checklist_email_settings_completed = true;
				$this->set_charitable_email_option( 'email_admin_notification' );
			}

			// If the general settings are completed, we can check off the checklist item.
			if ( $checklist_general_settings_completed ) {
				$checklist_class = Charitable_Checklist::get_instance();
				$checklist_class->mark_step_completed( 'general-settings' );
			}
			if ( $checklist_email_settings_completed ) {
				$checklist_class = Charitable_Checklist::get_instance();
				$checklist_class->mark_step_completed( 'email-settings' );
			}
			// misc.
			if ( key_exists( 'tracking', $meta ) ) {
				if ( 'yes' === $meta['tracking'] ) {
					if ( charitable_is_debug() ) {
						error_log( 'tracking is enabled' ); // phpcs:ignore
					}
					charitable_update_usage_tracking_setting( true );
					// Send initial usage information just once (after that scheduled), now that we have permission.
					if ( class_exists( 'Charitable_Tracking' ) ) {
						Charitable_Tracking::get_instance()->send_checkins( false, true );
					}
				}
			}
			if ( key_exists( 'email_signup', $meta ) ) {
				// if the value is a valid santitized email address...
				if ( is_email( $meta['email_signup'] ) ) {
					if ( charitable_is_debug() ) {
						error_log( 'email signup is valid' ); // phpcs:ignore
					}
					update_option( 'charitable_email_signup', $meta['email_signup'] );
				}
			}

			return true;
		}

		/**
		 * Process plugins.
		 *
		 * @since 1.8.4
		 *
		 * @param array $plugins The plugins to process.
		 * @return void
		 */
		public function process_plugins( $plugins = array() ) {

			if ( ! $plugins ) {
				return;
			}

			$plugins = array_map( 'sanitize_text_field', $plugins );

			return $plugins;
		}

		/**
		 * Process meta.
		 *
		 * @since 1.8.4
		 *
		 * @param array $payment_methods The methods to process.
		 * @return bool
		 */
		public function process_payment_methods( $payment_methods = array() ) {

			if ( ! $payment_methods || ! class_exists( 'Charitable_Gateways' ) ) {
				return false;
			}

			$ret = Charitable_Gateways::get_instance()->check_setup_payment_methods();

			return $ret;
		}

		/**
		 * Update a Charitable option.
		 *
		 * @since 1.8.4
		 *
		 * @param string $setting The setting key.
		 * @param mixed  $value   The setting value.
		 * @return void
		 */
		private function set_charitable_option( $setting = '', $value = false ) {
			$settings = get_option( 'charitable_settings', [] );

			$settings[ $setting ] = $value;

			if ( defined( 'CHARITABLE_DEBUG_SETUP' ) ) {
				error_log( 'Charitable Setup: Updated ' . $setting . ' to ' . $value ); // phpcs:ignore
			}

			update_option( 'charitable_settings', $settings );
		}

		/**
		 * Process email setting.
		 *
		 * @since 1.8.4
		 *
		 * @param string $email_setting The email setting to process.
		 * @return void
		 */
		private function set_charitable_email_option( $email_setting = '' ) {

			$settings       = get_option( 'charitable_settings', [] );
			$enabled_emails = ! empty( $settings['enabled_emails'] ) ? $settings['enabled_emails'] : [];

			switch ( $email_setting ) {
				case 'email_donor_notification':
					if ( ! key_exists( 'donation_receipt', $enabled_emails ) ) {
						$enabled_emails['donation_receipt'] = 'Charitable_Email_Donation_Receipt';
						if ( defined( 'CHARITABLE_DEBUG_SETUP' ) ) {
							error_log( 'Charitable Setup: Added donation_receipt to enabled_emails' ); // phpcs:ignore
						}
					}
					break;
				case 'email_admin_notification':
					if ( ! key_exists( 'new_donation', $enabled_emails ) ) {
						$enabled_emails['new_donation'] = 'Charitable_Email_New_Donation';
						if ( defined( 'CHARITABLE_DEBUG_SETUP' ) ) {
							error_log( 'Charitable Setup: Added new_donation to enabled_emails' ); // phpcs:ignore
						}
					}
					break;
			}

			$settings['enabled_emails'] = $enabled_emails;

			update_option( 'charitable_settings', $settings );
		}

		/**
		 * Get localized strings.
		 *
		 * @since  1.8.4
		 *
		 * @return array
		 */
		private function get_localized_strings() {

			// $currency_helper = charitable_get_currency_helper();

			$stripe_connect_url = '';

			$payment_methods = $this->retrieve_payment_methods();

			$stripe_returned = isset( $_GET['setup'] ) && 'stripe-connect' === $_GET['setup'] ? true : false; // phpcs:ignore

			if ( in_array( 'stripe', $payment_methods, true ) && class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {
				$stripe_gateway     = new Charitable_Gateway_Stripe_AM();
				$redirect_url       = $this->stripe_redirect_url;
				$stripe_connect_url = ! $stripe_gateway->maybe_stripe_connected() ? $stripe_gateway->get_stripe_connect_url( $redirect_url ) : '';
			}

			$strings = array(
				'version'                   => '1.8.4',
				'setup_step'                => $this->get_current_setup_step(),
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'headlines'                 => array(
					'start'             => esc_html__( 'Starting...', 'charitable' ),
					'meta'              => esc_html__( 'Setting Up General Settings...', 'charitable' ),
					'plugins'           => esc_html__( 'Updating Plugins...', 'charitable' ),
					'plugins_installed' => esc_html__( 'Installing Plugins...', 'charitable' ),
					'plugins_activated' => esc_html__( 'Activating Plugins...', 'charitable' ),
					'activate_license'  => esc_html__( 'Setting Up Features...', 'charitable' ),
					'features'          => esc_html__( 'Setting Up Features...', 'charitable' ),
					'campaign'          => esc_html__( 'Setting Up Your First Campaign...', 'charitable' ),
					'payment_methods'   => esc_html__( 'Setting Up Payment Methods...', 'charitable' ),
					'almost_complete'   => esc_html__( 'Almost done!', 'charitable' ),
					'complete'          => esc_html__( 'Congratulations!', 'charitable' ),
				),
				'text'                      => array(
					'start'                  => '',
					'meta'                   => esc_html__( 'Updating country, currency, and related settings for you.', 'charitable' ),
					'plugins'                => esc_html__( 'Checking plugins...', 'charitable' ),
					'plugins_installed'      => '',
					'plugins_activated'      => '',
					'activate_license'       => esc_html__( 'Checking license and site information...', 'charitable' ),
					'features'               => esc_html__( 'Checking license and site information...', 'charitable' ),
					'campaign'               => esc_html__( 'Creating draft...', 'charitable' ),
					'payment_methods'        => esc_html__( 'Checking payment methods...', 'charitable' ),
					'almost_complete'        => esc_html__( 'Finishing things...', 'charitable' ),
					'almost_complete_stripe' => esc_html__( 'Stripe offers a seamless and secure payment experience for both you and your donors.', 'charitable' ),
					'complete'               => esc_html__( 'Your Charitable install has been setup and is ready for you.', 'charitable' ),
				),
				'nonce'                     => wp_create_nonce( 'charitable-admin' ),
				'setup_nonce'               => wp_create_nonce( 'charitable-setup' ),
				'plugins'                   => array(
					'install'  => $this->plugins_to_install(),
					'activate' => $this->plugins_to_activate(),
				),
				'plugins_meta'              => $this->plugin_meta,
				'features'                  => $this->features_to_install(),
				'key'                       => $this->get_license_key(),
				'key_nonce'                 => wp_create_nonce( 'charitable_settings-options' ),
				'features_meta'             => $this->feature_meta,
				/* translators: %1$s: List of requested features */
				'features_message'          => esc_html__( 'In order to install and activate some of the features you requested %1$s you need to activate your PRO license after setup is complete.', 'charitable' ),
				'features_message_activate' => esc_html__( 'Activating license and installing addons...', 'charitable' ),
				'payment_methods'           => $payment_methods,
				'stripe_connect_url'        => $stripe_connect_url,
				'campaign_skip'             => $this->maybe_skip_campaign_setup(),
				'checklist_url'             => admin_url( 'admin.php?page=charitable-setup-checklist' ),
				'stripe_skip_html'          => '<a href="' . admin_url( 'admin.php?page=charitable-setup-checklist' ) . '">' . esc_html__( 'Skip setting up Stripe', 'charitable' ) . '</a>.',
				'stripe_returned'           => $stripe_returned,
				'checklist_completed'       => get_option( 'charitable_ss_complete', false ) ? true : false,
				'test_mode'           => ! empty( $_GET['test_mode'] ), // phpcs:ignore
			);

			$strings = apply_filters( 'charitable_setup_strings', $strings );

			return (array) $strings;
		}

		/**
		 * Get the requested feature string for the install message about license activation.
		 *
		 * @since  1.8.4
		 *
		 * @return string
		 */
		public function get_requested_feature_string() {

			$features = $this->features_to_install();

			if ( empty( $features ) ) {
				return '';
			}

			// create an array of names from feature_meta, with strong tags.
			$features = array_map(
				function ( $feature ) {
					return '<strong>' . $this->feature_meta[ $feature ]['name'] . '</strong>';
				},
				$features
			);

			// make string with a comma and an 'and' before the last feature.
			$features = implode( ', ', $features );

			return $features;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.4
		 *
		 * @return Charitable_Setup
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
