<?php
/**
 * Plugin Name: Charitable
 * Plugin URI: https://www.wpcharitable.com
 * Description: The best WordPress donation plugin. Fundraising with recurring donations, and powerful features to help you raise more money online.
 * Version: 1.8.10.4
 * Author: Charitable Donations & Fundraising Team
 * Author URI: https://wpcharitable.com
 * Requires at least: 5.0
 * Stable tag: 1.8.10.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Text Domain: charitable
 * Domain Path: /i18n/languages/
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2026, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable' ) ) :

	/**
	 * Main Charitable class.
	 */
	class Charitable {

		/** The product name. */
		const NAME = 'Charitable';

		/** The plugin author name. */
		const AUTHOR = 'WP Charitable';

		/* Plugin version. */
		const VERSION = '1.8.10.4';

		/* Version of database schema. */
		const DB_VERSION = '20180522';

		/* Campaign post type. */
		const CAMPAIGN_POST_TYPE = 'campaign';

		/* Donation post type. */
		const DONATION_POST_TYPE = 'donation';

		/**
		 * Single instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable
		 */
		private static $instance = null;

		/**
		 * The absolute path to this plugin's directory.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $directory_path;

		/**
		 * The URL of this plugin's directory.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $directory_url;

		/**
		 * Directory path for the includes folder of the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		private $includes_path;

		/**
		 * Store of registered objects.
		 *
		 * @since 1.0.0
		 * @since 1.5.0 Changed to Charitable_Registry object. Previously it was an array.
		 *
		 * @var   Charitable_Registry
		 */
		private $registry;

		/**
		 * Classmap.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		private $classmap;

		/**
		 * Plugin.
		 *
		 * @since 1.8.0
		 *
		 * @var   array
		 */
		private $plugin;

		/**
		 * Create class instance.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->directory_path = plugin_dir_path( __FILE__ );
			$this->directory_url  = plugin_dir_url( __FILE__ );
			$this->includes_path  = $this->directory_path . 'includes/';

			define( 'CHARITABLE_DIRECTORY_PATH', plugin_dir_path( __FILE__ ) );
			define( 'CHARITABLE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			define( 'CHARITABLE_MARKETING_URL', 'https://wpcharitable.com/' );

			define( 'CHARITABLE_BUILDER_SHOW_LEGACY_EDIT_LINKS', true ); // 1.8.0 specific.

			spl_autoload_register( array( $this, 'autoloader' ) );

			$this->load_dependencies();

			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			add_filter( 'plugin_action_links_' . CHARITABLE_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ), 99, 2 );
			add_action( 'plugins_loaded', array( $this, 'start' ), 3 );

			add_action( 'plugins_loaded', array( $this, 'check_for_version_conflicts' ), 2 );

			// Run upgrade-server callback before any redirect (e.g. login) can run. Request from connect-callback.php.
			if ( defined( 'CHARITABLE_CONNECT_CALLBACK' ) && CHARITABLE_CONNECT_CALLBACK ) {
				add_action( 'init', array( $this, 'run_connect_callback' ), -9999 );
			}

			// Initialize error handler early.
			add_action(
				'plugins_loaded',
				function () {
					require_once $this->get_path( 'includes' ) . 'class-charitable-error-handler.php';
					$error_handler = new Charitable_Error_Handler();
					$error_handler->init();
				},
				1
			);
		}

		/**
		 * Returns the original instance of this class.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable
		 */
		public static function get_instance() {
			return self::$instance;
		}

		/**
		 * Run the startup sequence.
		 *
		 * This is only ever executed once.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function start() {
			/* If we've already started (i.e. run this function once before), do not pass go. */
			if ( $this->started() ) {
				return;
			}

			/* Set static instance. */
			self::$instance = $this;

			/* Set up the registry and instantiate objects we need straight away. */
			$this->registry();

			$this->setup_licensing();

			$this->maybe_start_ajax();

			$this->attach_hooks_and_filters();

			$this->maybe_start_admin();

			$this->maybe_start_public();

			Charitable_Addons::load( $this );
		}

		/**
		 * Include necessary files.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function load_dependencies() {
			$includes_path = $this->get_path( 'includes' );

			/* Load files with hooks & functions. Classes are autoloaded. */
			require_once $includes_path . 'charitable-core-functions.php';
			require_once $includes_path . 'api/charitable-api-functions.php';
			require_once $includes_path . 'campaigns/charitable-campaign-functions.php';
			require_once $includes_path . 'campaigns/charitable-campaign-hooks.php';
			require_once $includes_path . 'compat/charitable-compat-functions.php';
			require_once $includes_path . 'currency/charitable-currency-functions.php';
			require_once $includes_path . 'deprecated/charitable-deprecated-functions.php';
			require_once $includes_path . 'donations/charitable-donation-hooks.php';
			require_once $includes_path . 'donations/charitable-donation-functions.php';
			require_once $includes_path . 'emails/charitable-email-hooks.php';
			require_once $includes_path . 'endpoints/charitable-endpoints-functions.php';
			require_once $includes_path . 'privacy/charitable-privacy-functions.php';
			require_once $includes_path . 'public/charitable-template-helpers.php';
			require_once $includes_path . 'shortcodes/charitable-shortcodes-hooks.php';
			require_once $includes_path . 'upgrades/charitable-upgrade-hooks.php';
			require_once $includes_path . 'users/charitable-user-functions.php';
			require_once $includes_path . 'user-management/charitable-user-management-hooks.php';
			require_once $includes_path . 'utilities/charitable-utility-functions.php';
			require_once $includes_path . 'elementor/charitable-elementor-hooks.php';

			// Load security hooks on both admin and frontend (CAPTCHA needs to work on frontend).
			require_once $this->get_path( 'includes', true ) . 'admin/security/charitable-security-hooks.php';

			/* Load vendor/autoload.php - our modified platform_check.php and autoload_real.php will handle conditional loading */
			require_once $this->get_path( 'directory' ) . 'vendor/autoload.php';

			/* Square Compatibility Wrapper - always load this regardless of PHP version */
			require_once $includes_path . 'square/class-charitable-square-compatibility.php';

			/* Initialize Square conditionally - will check for compatibility internally */
			Charitable_Square_Compatibility::init();

			if ( $this->load_core_stripe() ) :

				/* Interfaces */
				require_once $includes_path . 'stripe/interfaces/interface-charitable-stripe-gateway-processor.php';

				/* Abstract classes */
				require_once $includes_path . 'stripe/abstracts/class-charitable-stripe-gateway-processor.php';

				/* Classes */
				require_once $includes_path . 'stripe/donations/class-charitable-stripe-donation-log.php';
				require_once $includes_path . 'stripe/donations/class-charitable-stripe-recurring-donation-log.php';
				require_once $includes_path . 'stripe/i18n/class-charitable-stripe-i18n.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-connected-customer.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-customer.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-gateway-processor-payment-intents.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-payment-intent.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-plan.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-product.php';

				require_once $includes_path . 'stripe/connect/charitable-stripe-connect-functions.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-gateway-processor-checkout.php';

				/* Functions & Hooks */
				require_once $includes_path . 'stripe/charitable-stripe-core-functions.php';
				require_once $includes_path . 'stripe/donations/charitable-stripe-donation-functions.php';
				require_once $includes_path . 'stripe/gateway/charitable-stripe-gateway-hooks.php';

				/* webhooks */
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-webhook-api.php';
				require_once $includes_path . 'stripe/gateway/class-charitable-stripe-webhook-processor.php';

			endif;
		}

		/**
		 * Load the template functions after theme is loaded.
		 *
		 * This gives themes time to override the functions.
		 *
		 * @since  1.6.10
		 *
		 * @return void
		 */
		public function load_template_files() {
			$includes_path = $this->get_path( 'includes' );

			require_once $includes_path . 'public/charitable-template-functions.php';
			require_once $includes_path . 'public/charitable-template-hooks.php';
		}

		/**
		 * Dynamically loads the class attempting to be instantiated elsewhere in the
		 * plugin by looking at the $class_name parameter being passed as an argument.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $class_name The fully-qualified name of the class to load.
		 * @return boolean
		 */
		public function autoloader( $class_name ) {
			/* If the specified $class_name already exists, bail. */
			if ( class_exists( $class_name ) ) {
				return false;
			}

			/* If the specified $class_name does not include our namespace, duck out. */
			if ( false === strpos( $class_name, 'Charitable_' ) ) {
				return false;
			}

			/* Autogenerated class map. */
			if ( ! isset( $this->classmap ) ) {
				$this->classmap = include 'includes/autoloader/charitable-class-map.php';

				$this->classmap['Charitable_Gateway_Stripe_AM'] = 'gateways/class-charitable-gateway-stripe-am.php';
				$this->classmap['Charitable_Gateway_Stripe']    = 'gateways/class-charitable-gateway-stripe-am.php';
			}

			$file_path = isset( $this->classmap[ $class_name ] ) ? $this->get_path( 'includes' ) . $this->classmap[ $class_name ] : false;

			if ( false !== $file_path && file_exists( $file_path ) && is_file( $file_path ) ) {
				require_once $file_path;
				return true;
			}
			return false;
		}

		/**
		 * Returns a registered class object.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Registry
		 */
		public function registry() {
			if ( ! isset( $this->registry ) ) {
				$this->registry = new Charitable_Registry();
				$this->registry->register_object( Charitable_Emails::get_instance() );
				$this->registry->register_object( Charitable_Request::get_instance() );
				$this->registry->register_object( Charitable_Gateways::get_instance() );
				$this->registry->register_object( Charitable_i18n::get_instance() );
				$this->registry->register_object( Charitable_Post_Types::get_instance() );
				$this->registry->register_object( Charitable_Cron::get_instance() );
				$this->registry->register_object( Charitable_Widgets::get_instance() );
				$this->registry->register_object( Charitable_Licenses::get_instance() );
				$this->registry->register_object( Charitable_User_Dashboard::get_instance() );
				$this->registry->register_object( Charitable_Locations::get_instance() );
				$this->registry->register_object( Charitable_Currency::get_instance() );
				$this->registry->register_object( Charitable_Notifications::get_instance() );

				$this->registry->register_object( new Charitable_Privacy() );
				$this->registry->register_object( new Charitable_Debugging() );
				$this->registry->register_object( new Charitable_Locale() );
			}

			return $this->registry;
		}

		/**
		 * Set up hook and filter callback functions.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function attach_hooks_and_filters() {
			add_action( 'wpmu_new_blog', array( $this, 'maybe_activate_charitable_on_new_site' ) );
			add_action( 'plugins_loaded', array( $this, 'charitable_install' ), 100 );
			add_action( 'plugins_loaded', array( $this, 'charitable_start' ), 100 );
			add_action( 'plugins_loaded', array( $this, 'endpoints' ), 100 );
			add_action( 'init', array( $this, 'donation_fields' ), 100 );
			add_action( 'init', array( $this, 'campaign_fields' ), 100 );
			add_action( 'plugins_loaded', array( $this, 'register_donormeta_table' ) );
			add_action( 'plugins_loaded', 'charitable_load_compat_functions' );
			add_action( 'setup_theme', array( 'Charitable_Customizer', 'start' ) );
			add_action( 'after_setup_theme', array( $this, 'load_template_files' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_start_qunit' ), 100 );
			add_action( 'rest_api_init', 'charitable_register_api_routes' );

			/**
			 * We do this on priority 20 so that any functionality that is loaded on init (such
			 * as addons) has a chance to run before the event.
			 */
			add_action( 'init', array( $this, 'do_charitable_actions' ), 20 );

			if ( $this->load_core_stripe() ) :

				/**
				 * Set up scripts & stylesheets & enqueue them when appropriate.
				 */
				add_action( 'charitable_form_after_fields', array( $this, 'maybe_setup_stripe_scripts_in_donation_form' ) );
				add_action( 'charitable_campaign_loop_after', array( $this, 'maybe_setup_stripe_scripts_in_campaign_loop' ) );

			endif;
		}


		/**
		 * Load Stripe JS, as well as our handling scripts.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function enqueue_stripe_scripts() {
			if ( ! Charitable_Gateways::get_instance()->is_active_gateway( Charitable_Gateway_Stripe_AM::get_gateway_id() ) ) {
				return false;
			}

			wp_enqueue_script( 'charitable-stripe' );

			return true;
		}

		/**
		 * Load Stripe JS or Stripe Checkout, as well as our handling script.
		 *
		 * @uses   Charitable_Stripe::enqueue_scripts()
		 *
		 * @since  1.4.0
		 *
		 * @param  Charitable_Donation_Form $form The current form object.
		 * @return boolean
		 */
		public function maybe_setup_stripe_scripts_in_donation_form( $form ) {
			if ( ! is_a( $form, 'Charitable_Donation_Form' ) ) {
				return false;
			}

			if ( 'make_donation' !== $form->get_form_action() ) {
				return false;
			}

			return $this->enqueue_stripe_scripts();
		}

		/**
		 * Enqueue the Stripe JS/Checkout scripts after a campaign loop if modal donations are in use.
		 *
		 * @uses Charitable_Stripe::enqueue_scripts()
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function maybe_setup_stripe_scripts_in_campaign_loop() {
			if ( 'modal' !== charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
				return false;
			}

			return $this->enqueue_stripe_scripts();
		}

		/**
		 * Checks whether we're in the admin area and if so, loads the admin-only functionality.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Admin|false
		 */
		private function maybe_start_admin() {
			if ( ! is_admin() ) {
				return false;
			}

			// load blocks/Gutenberg related.
			require_once $this->get_path( 'admin' ) . 'class-charitable-admin-blocks.php';

			require_once $this->get_path( 'admin' ) . 'class-charitable-admin.php';
			require_once $this->get_path( 'admin' ) . 'charitable-admin-hooks.php';

			// load campaign builder.
			require_once $this->get_path( 'admin' ) . 'campaign-builder/util.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/functions.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/ajax-actions.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/functions-legacy.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/debug.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/access.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/class-builder-fields.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/class-builder-form-fields.php';

			// connect for upgrade.wpcharitable.com.
			require_once $this->get_path( 'admin' ) . 'class-charitable-admin-connect.php';

			// misc.
			require_once $this->get_path( 'admin' ) . 'class-charitable-admin-pointers.php';
			require_once $this->get_path( 'admin' ) . 'plugins/charitable-admin-plugin-hooks.php';
			require_once $this->get_path( 'admin' ) . 'class-charitable-admin-getting-started.php';

			$admin = Charitable_Admin::get_instance();
			$this->registry->register_object( $admin );

			// Stripe.
			require_once $this->get_path( 'includes' ) . 'stripe/admin/class-charitable-stripe-admin.php';
			new Charitable_Stripe_Admin();

			return $admin;
		}

		/**
		 * Run the upgrade-server connect callback on init before any redirect (e.g. to login) can run.
		 * Only registered when CHARITABLE_CONNECT_CALLBACK is defined (request came from connect-callback.php).
		 *
		 * @since 1.8.9.6
		 */
		public function run_connect_callback() {
			ini_set( 'display_errors', '0' );
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$connect_file = $this->get_path( 'admin' ) . 'class-charitable-admin-connect.php';
			if ( file_exists( $connect_file ) ) {
				require_once $connect_file;
			}
			if ( ! class_exists( 'Charitable_Admin_Connect' ) ) {
				header( 'Content-Type: application/json' );
				wp_send_json( array( 'success' => false, 'data' => array( 'message' => 'Charitable not loaded.' ) ) );
			}
			Charitable_Admin_Connect::get_instance()->process();
			exit;
		}

		/**
		 * Checks whether we're on the public-facing side and if so, loads the public-facing functionality.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Public|false
		 */
		private function maybe_start_public() {
			if ( is_admin() && ! $this->is_ajax() ) {
				return false;
			}

			require_once $this->get_path( 'public' ) . 'class-charitable-public.php';

			// Campaign Builder related (v1.8.0).
			require_once $this->get_path( 'admin' ) . 'campaign-builder/class-campaign-builder-preview.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/class-builder-fields.php';
			require_once $this->get_path( 'admin' ) . 'campaign-builder/class-builder-form-fields.php';

			// load blocks/Gutenberg related.
			require_once $this->get_path( 'admin' ) . 'class-charitable-admin-blocks.php';

			$public = Charitable_Public::get_instance();

			$this->registry->register_object( $public );

			return $public;
		}

		/**
		 * Load the QUnit tests if ?qunit is appended to the request.
		 *
		 * @since  1.4.17
		 *
		 * @return boolean
		 */
		public function maybe_start_qunit() {
			/* Skip out early if ?qunit isn't included in the request. */
			if ( ! array_key_exists( 'qunit', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return false;
			}

		/* The unit tests have to exist. */
		if ( ! file_exists( $this->get_path( 'directory' ) . 'tests/qunit/tests.js' ) ) {
			return false;
		}

		/**
		 * Register QUnit testing library.
		 *
		 * Note: These files must be bundled locally for WordPress.org compliance.
		 * Download from:
		 * - JS: https://code.jquery.com/qunit/qunit-2.3.3.js
		 * - CSS: https://code.jquery.com/qunit/qunit-2.3.3.css
		 * Save to:
		 * - assets/js/libraries/qunit-2.3.3.js
		 * - assets/css/libraries/qunit-2.3.3.css
		 *
		 * @since 1.0.0
		 * @version 1.8.9.1
		 */
		wp_register_script(
			'qunit',
			$this->get_path( 'directory', false ) . 'assets/js/libraries/qunit-2.3.3.js',
			array(),
			'2.3.3',
			true
		);

		/* Version: '20170615-15:44' */
		wp_register_script( 'qunit-tests', $this->get_path( 'directory', false ) . 'tests/qunit/tests.js', array( 'jquery', 'qunit' ), time(), true );
		wp_enqueue_script( 'qunit-tests' );

		wp_register_style(
			'qunit',
			$this->get_path( 'directory', false ) . 'assets/css/libraries/qunit-2.3.3.css',
			array(),
			'2.3.3',
			'all'
		);
		wp_enqueue_style( 'qunit' );
		}

		/**
		 * Checks whether the current request is an AJAX request.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		private function is_ajax() {
			return false !== ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		}

		/**
		 * Checks whether we're executing an AJAX hook and if so, loads some AJAX functionality.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		private function maybe_start_ajax() {
			if ( ! $this->is_ajax() ) {
				return;
			}

			require_once $this->get_path( 'includes' ) . 'ajax/charitable-ajax-functions.php';
			require_once $this->get_path( 'includes' ) . 'ajax/charitable-ajax-hooks.php';
		}

		/**
		 * This method is fired after all plugins are loaded and simply fires the charitable_start hook.
		 *
		 * Extensions can use the charitable_start event to load their own functionality.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function charitable_start() {
			do_action( 'charitable_start', $this );
		}

		/**
		 * Fires off an action right after Charitable is installed, allowing other
		 * plugins/themes to do something at this point.
		 *
		 * @since   1.0.1
		 * @version 1.8.1.12 - Added transient for activation redirect.
		 * @version 1.8.3 - Added call to add version and delete notifications.
		 *
		 * @return void
		 */
		public function charitable_install() {
			$install = get_transient( 'charitable_install' );

			if ( ! $install ) {
				return;
			}

			require_once $this->get_path( 'includes' ) . 'plugin/class-charitable-install.php';

			Charitable_Install::finish_installing();

			do_action( 'charitable_install' );

			delete_transient( 'charitable_install' );

			// Store the date when the initial activation was performed.
			$type      = function_exists( 'charitable_is_pro' ) && charitable_is_pro() ? 'pro' : 'lite';
			$activated = (array) get_option( 'charitable_activated', array() );

			// If no activation date is set, assume it's a new install and set the activation redirect transient.
			if ( false === $activated || empty( $activated ) ) {
				// Add transient to trigger redirect to the Welcome / Getting Started screen.
				set_transient( 'charitable_activation_redirect', true, 30 );
			}

			$this->add_version_to_upgraded_from();

			if ( empty( $activated[ $type ] ) ) {
				$activated[ $type ] = time();
				update_option( 'charitable_activated', $activated );
			}

			// Clear notifications.
			delete_option( 'charitable_notifications' );
		}

		/**
		 * Returns whether we are currently in the start phase of the plugin.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_start() {
			return current_filter() == 'charitable_start'; // phpcs:ignore
		}

		/**
		 * Returns whether the plugin has already started.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function started() {
			return did_action( 'charitable_start' ) || current_filter() == 'charitable_start'; // phpcs:ignore
		}

		/**
		 * Returns whether the plugin is being activated.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_activation() {
			return current_filter() == 'activate_charitable/charitable.php'; // phpcs:ignore
		}

		/**
		 * Returns whether the plugin is being deactivated.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_deactivation() {
			return current_filter() == 'deactivate_charitable/charitable.php'; // phpcs:ignore
		}

		/**
		 * Returns plugin paths.
		 *
		 * @since  1.0.0
		 *
		 * @param  string  $type          If empty, returns the path to the plugin.
		 * @param  boolean $absolute_path If true, returns the file system path. If false, returns it as a URL.
		 * @return string
		 */
		public function get_path( $type = '', $absolute_path = true ) {
			$base = $absolute_path ? $this->directory_path : $this->directory_url;

			switch ( $type ) {
				case 'includes':
					$path = $base . 'includes/';
					break;

				case 'admin':
					$path = $base . 'includes/admin/';
					break;

				case 'public':
					$path = $base . 'includes/public/';
					break;

				case 'assets':
					$path = $base . 'assets/';
					break;

				case 'templates':
					$path = $base . 'templates/';
					break;

				case 'plugin-directory':
					$path = WP_PLUGIN_DIR;
					break;

				case 'directory':
					$path = $base;
					break;

				default:
					$path = __FILE__;

			}//end switch

			return $path;
		}

		/**
		 * Returns the plugin's version number.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_version() {
			$version = self::VERSION;

			if ( false !== strpos( $version, '-' ) ) {
				$parts   = explode( '-', $version );
				$version = $parts[0];
			}

			return $version;
		}

		/**
		 * Adds previous versions to the upgraded_from option.
		 *
		 * @since  1.8.3
		 */
		public function add_version_to_upgraded_from() {

			$upgraded_from = get_option( 'charitable_version_upgraded_from', array() );

			if ( ! is_array( $upgraded_from ) ) {
				$upgraded_from = array();
			}

			$upgraded_from[] = $this->get_version();

			update_option( 'charitable_version_upgraded_from', $upgraded_from, false );
		}

		/**
		 * Get the campaign fields registry
		 *
		 * If the registry has not been set up yet, this will also
		 * perform the task of setting it up initially.
		 *
		 * This method is called on the plugins_loaded hook.
		 *
		 * @since  1.6.0
		 *
		 * @return Charitable_Campaign_Field_Registry
		 */
		public function campaign_fields() {
			if ( ! $this->registry->has( 'campaign_field_registry' ) ) {
				$campaign_fields = new Charitable_Campaign_Field_Registry();

				/* Register it immediately to avoid endless recursion. */
				$this->registry->register_object( $campaign_fields );

				$fields = include $this->get_path( 'includes' ) . 'fields/default-fields/campaign-fields.php';

				foreach ( $fields as $key => $args ) {
					$campaign_fields->register_field( new Charitable_Campaign_Field( $key, $args ) );
				}
			}

			return $this->registry->get( 'campaign_field_registry' );
		}

		/**
		 * Get the donation fields registry
		 *
		 * If the registry has not been set up yet, this will also
		 * perform the task of setting it up initially.
		 *
		 * This method is called on the plugins_loaded hook.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_Field_Registry
		 */
		public function donation_fields() {
			if ( ! $this->registry->has( 'donation_field_registry' ) ) {
				$donation_fields = new Charitable_Donation_Field_Registry();

				/* Register it immediately to avoid endless recursion. */
				$this->registry->register_object( $donation_fields );

				$fields = include $this->get_path( 'includes' ) . 'fields/default-fields/donation-fields.php';

				foreach ( $fields as $key => $args ) {
					$donation_fields->register_field( new Charitable_Donation_Field( $key, $args ) );
				}
			}

			return $this->registry->get( 'donation_field_registry' );
		}

		/**
		 * Return the Endpoints API object.
		 *
		 * If the Endpoints API has not been set up yet, this will also
		 * perform the task of setting it up initially.
		 *
		 * This method is called on the plugins_loaded hook.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Endpoints
		 */
		public function endpoints() {
			if ( ! $this->registry->has( 'endpoints' ) ) {
				/**
				 * The order in which we register endpoints is important, because
				 * it determines the order in which the endpoints are checked to
				 * find whether they are the current page.
				 *
				 * Any endpoint that builds on another endpoint should be registered
				 * BEFORE the endpoint it builds on. In other words, move from
				 * most specific to least specific.
				 */
				$endpoints = new Charitable_Endpoints();
				$endpoints->register( new Charitable_Campaign_Donation_Endpoint() );
				$endpoints->register( new Charitable_Campaign_Widget_Endpoint() );
				$endpoints->register( new Charitable_Campaign_Endpoint() );
				$endpoints->register( new Charitable_Donation_Cancellation_Endpoint() );
				$endpoints->register( new Charitable_Donation_Processing_Endpoint() );
				$endpoints->register( new Charitable_Donation_Receipt_Endpoint() );
				$endpoints->register( new Charitable_Email_Preview_Endpoint() );
				$endpoints->register( new Charitable_Email_Verification_Endpoint() );
				$endpoints->register( new Charitable_Forgot_Password_Endpoint() );
				$endpoints->register( new Charitable_Reset_Password_Endpoint() );
				$endpoints->register( new Charitable_Registration_Endpoint() );
				$endpoints->register( new Charitable_Login_Endpoint() );
				$endpoints->register( new Charitable_Profile_Endpoint() );
				$endpoints->register( new Charitable_Webhook_Listener_Endpoint() );

				$this->registry->register_object( $endpoints );
			}

			return $this->registry->get( 'endpoints' );
		}

		/**
		 * Returns a registered object.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $class The type of class to be retrieved.
		 * @return object
		 */
		public function get_registered_object( $class ) { // phpcs:ignore
			return $this->registry->get( $class );
		}

		/**
		 * Registers our donormeta table as a meta data table.
		 *
		 * @since  1.6.0
		 *
		 * @global WPDB $wpdb
		 * @return void
		 */
		public function register_donormeta_table() {
			global $wpdb;

			$wpdb->donormeta = $wpdb->prefix . 'charitable_donormeta';
		}

		/**
		 * Returns the model for one of Charitable's database tables.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $table The database table to retrieve.
		 * @return Charitable_DB
		 */
		public function get_db_table( $table ) {
			$tables = $this->get_tables();

			if ( ! isset( $tables[ $table ] ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					sprintf( 'Invalid table %s passed', $table ),
					'1.0.0'
				);
				return null;
			}

			return $this->registry->get( $tables[ $table ] );
		}

		/**
		 * Return the filtered list of registered tables.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		private function get_tables() {
			/**
			 * Filter the array of available Charitable table classes.
			 *
			 * @since 1.0.0
			 * @version 1.8.1
			 *
			 * @param array $tables List of tables as a key=>value array.
			 */
			return apply_filters(
				'charitable_db_tables',
				array(
					'campaign_donations'  => 'Charitable_Campaign_Donations_DB',
					'donors'              => 'Charitable_Donors_DB',
					'donormeta'           => 'Charitable_Donormeta_DB',
					'donation_activities' => 'Charitable_Donation_Activities_DB',
					'campaign_activities' => 'Charitable_Campaign_Activities_DB',
				)
			);
		}

		/**
		 * Maybe activate Charitable when a new site is added in a multisite network.
		 *
		 * @since  1.4.6
		 *
		 * @param  int $blog_id The blog to activate Charitable on.
		 * @return void
		 */
		public function maybe_activate_charitable_on_new_site( $blog_id ) {
			if ( is_plugin_active_for_network( basename( $this->directory_path ) . '/charitable.php' ) ) {
				switch_to_blog( $blog_id );
				$this->activate( false );
				restore_current_blog();
			}
		}

		/**
		 * Runs on plugin activation.
		 *
		 * @see    register_activation_hook
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $network_wide Whether to enable the plugin for all sites in the network
		 *                               or just the current site. Multisite only. Default is false.
		 * @return void
		 */
		public function activate( $network_wide = false ) {
			require_once $this->get_path( 'includes' ) . 'plugin/class-charitable-install.php';

			if ( is_multisite() && $network_wide ) {
				global $wpdb;

				foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) { // phpcs:ignore
					switch_to_blog( $blog_id );
					new Charitable_Install( $this->includes_path );
					restore_current_blog();
				}
			} else {
				new Charitable_Install( $this->includes_path );
			}

			// create or update the install date for future reference.
			update_option( 'wpcharitable_activated_datetime', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			// Set initial timestamps for rotating menu items if they're already installed
			$this->set_initial_rotating_menu_timestamps();
		}

		/**
		 * Runs on plugin deactivation.
		 *
		 * @see    register_deactivation_hook
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function deactivate() {
			require_once $this->get_path( 'includes' ) . 'plugin/class-charitable-uninstall.php';
			new Charitable_Uninstall();
		}

		/**
		 * If a charitable_action event is triggered, delegate the event using do_action.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function do_charitable_actions() {
			if ( isset( $_REQUEST['charitable_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$action = esc_attr( $_REQUEST['charitable_action'] ); // phpcs:ignore

				/**
				 * Handle Charitable action.
				 *
				 * @since 1.0.0
				 */
				do_action( 'charitable_' . $action );
			}
		}

		/**
		 * Throw error on object clone.
		 *
		 * This class is specifically designed to be instantiated once. You can retrieve the instance using charitable()
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function __clone() {
			charitable_get_deprecated()->doing_it_wrong(
				__FUNCTION__,
				__( 'Cloning this object is forbidden.', 'charitable' ),
				'1.0.0'
			);
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function __wakeup() {
			charitable_get_deprecated()->doing_it_wrong(
				__FUNCTION__,
				__( 'Unserializing instances of this class is forbidden.', 'charitable' ),
				'1.0.0'
			);
		}

		/**
		 * DEPRECATED METHODS
		 */

		/**
		 * Load plugin compatibility files on plugins_loaded hook.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.4.18
		 * @since  1.5.0 Deprecated.
		 *
		 * @return void
		 */
		public function load_plugin_compat_files() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0.',
				'charitable_load_compat_functions'
			);

			charitable_load_compat_functions();
		}

		/**
		 * Stores an object in the plugin's registry.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  mixed $object Object to be registered.
		 * @return void
		 */
		public function register_object( $object ) { // phpcs:ignore
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'charitable()->registry()->register_object()'
			);

			$this->registry->register_object( $object );
		}

		/**
		 * Returns the public class.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @return Charitable_Public
		 */
		public function get_public() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'charitable_get_helper'
			);

			return $this->registry->get( 'Charitable_Public' );
		}

		/**
		 * Returns the admin class.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @return Charitable_Admin
		 */
		public function get_admin() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'charitable_get_helper'
			);

			return $this->registry->get( 'Charitable_Admin' );
		}

		/**
		 * Return the current request object.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @return Charitable_Request
		 */
		public function get_request() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.5.0',
				'charitable_get_helper'
			);

			return $this->registry->get( 'Charitable_Request' );
		}

		/**
		 * Returns the instance of Charitable_Currency.
		 *
		 * @deprecated 1.7.0
		 *
		 * @since 1.4.0 Deprecated.
		 */
		public function get_currency_helper() {
			charitable_get_deprecated()->deprecated_function( __METHOD__, '1.4.0', 'charitable_get_currency_helper' );
			return charitable_get_currency_helper();
		}

		/**
		 * Returns the instance of Charitable_Locations.
		 *
		 * @deprecated 1.6.0
		 *
		 * @since 1.2.0 Deprecated.
		 */
		public function get_location_helper() {
			charitable_get_deprecated()->deprecated_function( __METHOD__, '1.2.0', 'charitable_get_location_helper' );
			return charitable_get_location_helper();
		}

		/**
		 * Determine if we use Stripe core files or use an addon if that's installed and active. Checks for USE_NEW_STRIPE first.
		 *
		 * @since  1.7.0
		 *
		 * @return boolean
		 */
		public function load_core_stripe() {

			if ( false !== ( defined( 'USE_NEW_STRIPE' ) && USE_NEW_STRIPE ) ) {
				return true;
			}

			// check and see if the core Stripe AM gateway is an active gateway, and if it is not, don't load the core stripe JS, etc.
			if ( class_exists( 'Charitable_Gateways' ) && class_exists( 'Charitable_Gateway_Stripe_AM' ) && ! Charitable_Gateways::get_instance()->is_active_gateway( Charitable_Gateway_Stripe_AM::get_gateway_id() ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Determine Stripe Connect is active.
		 *
		 * @since  1.7.0
		 *
		 * @return boolean
		 */
		public function is_stripe_connect_addon() {

			if ( in_array( 'charitable-stripe/charitable-stripe.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { // phpcs:ignore
				return true;
			}

			return false;
		}

		/**
		 * Action links for the plugins page.
		 *
		 * @since  1.7.0
		 *
		 * @param  array   $actions    An array of existing actions.
		 * @param  string  $plugin_file The plugin file we are modifying.
		 * @param  boolean $action_links An array of action links to add.
		 * @return array              An array of action links.
		 */
		public function plugin_action_links( $actions, $plugin_file, $action_links = false ) {

			if ( ! charitable_is_pro() ) :

				// if this isn't pro, add the upgrade link which is visible on the plugins page until the plugin name.

				$action_links = array(
					'proupgrade' => array(
						// Translators: This is an action link users can click to purchase a license for All in One SEO Pro.
						'label' => __( 'Upgrade to Pro', 'charitable' ),
						'url'   => 'https://wpcharitable.com/lite-vs-pro/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Action+Link+Plugins+Page&utm_content=Upgrade+to+Pro',
					),
				);

			endif;

			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			return $this->parse_action_links( $actions, $plugin_file, $action_links, 'before' );
		}

		/**
		 * Parse the action links.
		 *
		 * @since 1.7.0
		 *
		 * @param array  $actions   An array of existing actions.
		 * @param string $plugin_file The plugin file we are modifying.
		 * @param array  $action_links An array of action links to add.
		 * @param string $position  The position to add the action links.
		 * @return array
		 */
		protected function parse_action_links( $actions, $plugin_file, $action_links = array(), $position = 'after' ) {
			if ( empty( $this->plugin ) ) {
				$this->plugin = CHARITABLE_PLUGIN_BASENAME;
			}

			if ( $this->plugin === $plugin_file && ! empty( $action_links ) ) {
				foreach ( $action_links as $key => $value ) {
					$link = array(
						$key => '<a href="' . $value['url'] . '">' . $value['label'] . '</a>',
					);

					$actions = 'after' === $position ? array_merge( $actions, $link ) : array_merge( $link, $actions );
				}
			}

			return $actions;
		}

		/**
		 * Set up licensing for pro.
		 *
		 * @since  1.7.0
		 *
		 * @return void
		 */
		public function setup_licensing() {
			charitable_get_helper( 'licenses' )->register_licensed_product(
				self::NAME,
				self::AUTHOR,
				self::VERSION,
				__FILE__
			);
		}

		/**
		 * Deativates and/or warnings about any Charitable verison conflicts.
		 *
		 * @since  1.7.0
		 */
		public function check_for_version_conflicts() {

			// Check for Charitable Stripe < 1.5.0.
			if ( in_array( 'charitable-stripe/charitable-stripe.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { // phpcs:ignore
				if ( class_exists( 'Charitable_Stripe' ) && version_compare( Charitable_Stripe::VERSION, '1.5.0', '<' ) ) { // phpcs:ignore
					// manually deactivate plugin.
					$found   = false;
					$current = get_option( 'active_plugins', array() );
					$key     = array_search( 'charitable-stripe/charitable-stripe.php', $current, true );
					if ( false !== $key ) {
						$found = true;

						unset( $current[ $key ] );
						update_option( 'active_plugins', $current );

						// translators: %s is the version number.
						$message = sprintf( __( '<p><strong style="color:red;">NOTICE:</strong> <strong>Charitable</strong> detected an out-of-date and incompatible version of <strong>Charitable Stripe</strong> addon. To avoid issues with Stripe donations, <strong>Charitable Stripe</strong> %s was deactivated. Please update <strong>Charitable Stripe</strong> to version 1.5.0 or higher.</p><p>You can get the latest version of <strong>Charitable Stripe</strong> through your <a href="https://www.wpcharitable.com/account/" target="_blank">Charitable account at wpcharitable.com</a>. All active licenses are accessible in the "My Downloads" tab.</p>', 'charitable' ), Charitable_Stripe::VERSION );

						wp_die( $message . ' <a href="' . admin_url( 'plugins.php' ) . '">Click here to return to the plugins screen.</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					add_option( 'charitable_version_warning', $message );
				} else {
					delete_option( 'charitable_version_warning' );
				}
			}
		}

		/**
		 * Set initial timestamps for rotating menu items if they're already installed.
		 * This ensures the 7-day countdown starts immediately for already-installed plugins.
		 *
		 * @since 1.8.8
		 *
		 * @return void
		 */
		private function set_initial_rotating_menu_timestamps() {
			// Get the rotating menu items configuration
			$admin_pages = Charitable_Admin_Pages::get_instance();

			try {
				$reflection = new ReflectionClass( $admin_pages );
				$method = $reflection->getMethod( 'get_marketing_rotation_items' );
				$method->setAccessible( true );
				$items = $method->invoke( $admin_pages );
			} catch ( ReflectionException $e ) {
				// Fallback if reflection fails in future PHP versions
				$items = array();
			}

			$current_time = time();

			foreach ( $items as $item ) {
				$option_name = $item['option_name'] ?? '';
				$option_key = $item['option_key'] ?? '';
				$plugin_file = $item['plugin_file'] ?? '';

				if ( empty( $option_name ) || empty( $plugin_file ) ) {
					continue;
				}

				// Check if the plugin is installed (file exists in plugins directory)
				$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
				if ( file_exists( $plugin_path ) ) {
					// Plugin is installed, check if we already have a timestamp
					$existing_option = get_option( $option_name );
					$has_timestamp = false;

					if ( is_array( $existing_option ) ) {
						$has_timestamp = isset( $existing_option[ $option_key ] ) && is_numeric( $existing_option[ $option_key ] );
					} elseif ( is_numeric( $existing_option ) ) {
						$has_timestamp = true;
					}

					// Only set timestamp if one doesn't already exist
					if ( ! $has_timestamp ) {
						if ( is_array( $existing_option ) ) {
							$existing_option[ $option_key ] = $current_time;
							update_option( $option_name, $existing_option );
						} else {
							update_option( $option_name, array( $option_key => $current_time ) );
						}
					}
				}
			}
		}
	}

	$charitable = new Charitable();

endif;
