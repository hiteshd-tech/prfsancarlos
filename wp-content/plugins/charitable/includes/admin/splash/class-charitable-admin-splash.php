<?php
/**
 * Charitable Admin Splash.
 *
 * @package   Charitable/Classes/Charitable_Admin_Splash
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Splash' ) ) :

	/**
	 * Charitable_Advanced_Settings
	 *
	 * @final
	 * @since   1.8.6
	 */
	final class Charitable_Admin_Splash {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Admin_Splash|null
		 */
		private static $instance = null;

		/**
		 * Default plugin version.
		 *
		 * @since 1.8.6
		 *
		 * @var string
		 */
		private $default_plugin_version = '1.8.6'; // The last version before the "What's New?" feature.

		/**
		 * Previous plugin version.
		 *
		 * @since 1.8.6
		 *
		 * @var string
		 */
		private $previous_plugin_version;

		/**
		 * Latest splash version.
		 *
		 * @since 1.8.6
		 *
		 * @var string
		 */
		private $latest_splash_version;

		/**
		 * Splash data.
		 *
		 * @since 1.8.6
		 *
		 * @var array
		 */
		private $splash_data = array();

		/**
		 * Whether it is a new Charitable installation.
		 *
		 * @since 1.8.6
		 *
		 * @var bool
		 */
		private $is_new_install;

		/**
		 * Whether the splash link is added.
		 *
		 * @since 1.8.6
		 *
		 * @var bool
		 */
		private $splash_link_added = false;

		/**
		 * Create object instance.
		 *
		 * @since   1.8.6
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.8.6
		 *
		 * @return  Charitable_Admin_Splash
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Initialize splash data.
		 *
		 * @since 1.8.6
		 */
		public function initialize_splash_data() {

			if ( ! $this->is_allow_splash() ) {
				return;
			}

			if ( empty( $this->splash_data ) ) {
				$this->splash_data = $this->get_default_data();

				// Add splash data to a transient.
				set_transient( 'charitable_splash_data', $this->splash_data, 60 * 60 * 24 );

				$version = $this->get_major_version( charitable()->get_version() );

				$this->update_splash_data_version( $version );
			}
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.8.6
		 */
		public function admin_enqueue_scripts() {

			$min = charitable_get_min_suffix();

			if ( ! wp_style_is( 'jquery-confirm', 'enqueued' ) ) {
				wp_enqueue_style(
					'jquery-confirm',
					charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
					null,
					'3.3.4'
				);
			}

			if ( ! wp_script_is( 'jquery-confirm', 'enqueued' ) ) {
				wp_enqueue_script(
					'jquery-confirm',
					charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
					array( 'jquery' ),
					'3.3.4',
					false
				);
			}

			wp_register_script(
				'charitable-admin-splash',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-admin-splash' . $min . '.js',
				array( 'jquery', 'wp-util' ),
				charitable()->get_version(),
				true
			);

			wp_register_style(
				'charitable-admin-splash',
				charitable()->get_path( 'assets', false ) . 'css/admin/charitable-admin-splash' . $min . '.css',
				array(),
				charitable()->get_version()
			);

			wp_localize_script(
				'charitable-admin-splash',
				'charitable_admin_splash_data',
				array(
					'nonce'            => wp_create_nonce( 'charitable_admin_splash_nonce' ),
					'triggerForceOpen' => $this->should_open_splash(),
				)
			);
		}

		/**
		 * Render splash modal.
		 *
		 * @since 1.8.6
		 *
		 * @param array $data Splash modal data.
		 */
		public function render_modal( array $data = array() ) { // phpcs:ignore

			wp_enqueue_script( 'jquery-confirm' );
			wp_enqueue_style( 'jquery-confirm' );

			wp_enqueue_script( 'charitable-admin-splash' );
			wp_enqueue_style( 'charitable-admin-splash' );

			if ( $this->should_open_splash() ) {
				$this->update_splash_version();
			}

			// Get splash data from a transient.
			$this->splash_data = get_transient( 'charitable_splash_data' );

			if ( empty( $this->splash_data ) ) {
				$this->splash_data = $this->get_default_data();
			}

			$this->splash_data['sections'] = $this->retrieve_sections_for_user( $this->splash_data['sections'] ?? array() );

			$template_location = '/admin/templates/splash/splash-modal';
			$template_location = apply_filters( 'charitable_admin_splash_modal_template_location', $template_location );
			echo charitable_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$template_location,
				array(
					'data' => $this->splash_data,
				),
				true
			);
		}

		/**
		 * Retrieve sections for user.
		 *
		 * @since 1.8.6
		 *
		 * @param array $sections Sections.
		 * @return array Sections.
		 */
		public function retrieve_sections_for_user( array $sections = array() ): array {

			$sections = array(
				array(
					'new'     => true,
					'version' => '1.8.9',
					'layout'  => 'fifty-fifty',
					'class'   => 'no-order',
					'title'   => __( 'Security Enhancements', 'charitable' ),
					'content' => __( 'Charitable Lite now supports Google reCAPTCHA, hCaptcha, and Cloudflare Turnstile for improved security.', 'charitable' ),
					'img'     => array(
						'url'    => charitable()->get_path( 'assets', false ) . 'images/splash/1-8-9-security.png',
						'shadow' => 'none',
					),
					'buttons' => array(
						'main'      => array(
							'text' => __( 'Get Started', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/get-started/1-8-9-security/', 'splash-modal', 'Square Widgets Main' ),
						),
						'secondary' => array(
							'text' => __( 'Learn More', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/learn-more/1-8-9-security/', 'splash-modal', 'Square Widgets Secondary' ),
						),
					),
				),
				array(
					'new'     => true,
					'version' => '1.8.8',
					'layout'  => 'fifty-fifty',
					'class'   => 'no-order',
					'title'   => __( 'New Dashboard!', 'charitable' ),
					'content' => __( 'Charitable now has a new dashboard design with top campaigns, latest donations, top donors, and comments. New "30 Day" period added.', 'charitable' ),
					'img'     => array(
						'url'    => charitable()->get_path( 'assets', false ) . 'images/splash/1-8-8-dashboard.png',
						'shadow' => 'none',
					),
					'buttons' => array(
						'main'      => array(
							'text' => __( 'Get Started', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/get-started/square/', 'splash-modal', 'Square Widgets Main' ),
						),
						'secondary' => array(
							'text' => __( 'Learn More', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/learn-more/square/', 'splash-modal', 'Square Widgets Secondary' ),
						),
					),
				),
				array(
					'new-for-pro' => true,
					'layout'    => 'one-third-two-thirds-flipped',
					'class'     => 'no-order',
					'title'     => __( 'Advanced Elementor Widgets', 'charitable' ),
					'content'   => __( 'When you create pages with the Elementor page builder, you\'ll now find four ready-made Charitable widgets (campaigns, donation button, donation form, campaigns).', 'charitable' ),
					'img'       => array(
						'url'    => charitable()->get_path( 'assets', false ) . 'images/splash/1-8-8-elementor.png',
						'shadow' => 'none',
					),
					'buttons'   => array(
						'main'      => array(
							'text' => __( 'Get Started', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/documentation/how-to-use-charitable-widgets-in-elementor/', 'splash-modal', 'Elementor Widgets Main' ),
						),
						'secondary' => array(
							'text' => __( 'Learn More', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/introducing-charitable-1-8-6-elementor-widgets-reply-to-and-new-splash-screen/', 'splash-modal', 'Elementor Widgets Secondary' ),
						),
					),
				),
				array(
					'new-addon' => true,
					'layout'    => 'one-third-two-thirds',
					'class'     => 'no-order',
					'title'     => __( 'DonorTrust', 'charitable' ),
					'content'   => __( 'Showcase real-time, verified donations to your website visitors and encourage more people to donate to your cause.', 'charitable' ),
					'img'       => array(
						'url'    => charitable()->get_path( 'assets', false ) . 'images/splash/1-8-8-donortrust.gif',
						'shadow' => 'none',
					),
					'buttons'   => array(
						'main'      => array(
							'text' => __( 'Get Started', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/documentation/charitable-donortrust/', 'splash-modal', 'DonorTrust Main' ),
						),
						'secondary' => array(
							'text' => __( 'Learn More', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/introducing-donortrust/', 'splash-modal', 'DonorTrust Secondary' ),
						),
					),
				),
				array(
					'new-for-pro'     => true,
					'layout'  => 'one-third-two-thirds-flipped',
					'class'   => 'no-order',
					'title'   => __( 'More Stripe Options!', 'charitable' ),
					'content' => __( 'Charitable now supports ACH Direct Debit, SEPA Direct Debit, Cash App, and BECS Direct Debit for Stripe users.', 'charitable' ),
					'img'     => array(
						'url'    => charitable()->get_path( 'assets', false ) . 'images/splash/1-8-8-stripe.png',
						'shadow' => 'none',
					),
					'buttons' => array(
						'main'      => array(
							'text' => __( 'Get Started', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/introducing-charitable-1-8-8/', 'splash-modal', 'Square Widgets Main' ),
						),
						'secondary' => array(
							'text' => __( 'Learn More', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/introducing-charitable-1-8-8/', 'splash-modal', 'Square Widgets Secondary' ),
						),
					),
				),
				array(
					'new-addon' => true,
					'layout'    => 'one-third-two-thirds',
					'class'     => 'no-order',
					'title'     => __( 'Google Analytics', 'charitable' ),
					'content'   => __( 'The new Google Analytics addon means you can track your campaign performance and see how your donors are engaging with your campaign.', 'charitable' ),
					'img'       => array(
						'url'    => charitable()->get_path( 'assets', false ) . 'images/splash/1-8-7-ga.png',
						'shadow' => 'none',
					),
					'buttons'   => array(
						'main'      => array(
							'text' => __( 'Get Started', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/get-started/google-analytics/', 'splash-modal', 'GA Main' ),
						),
						'secondary' => array(
							'text' => __( 'Learn More', 'charitable' ),
							'url'  => charitable_utm_link( 'https://www.wpcharitable.com/learn-more/google-analytics/', 'splash-modal', 'GA Secondary' ),
						),
					),
				),
			);

			return $sections;
		}

		/**
		 * Check if splash data is empty.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if empty, false otherwise.
		 */
		public function is_splash_empty(): bool {

			if ( empty( $this->splash_data ) ) {
				return true;
			}

			return empty( $this->retrieve_sections_for_user( $this->splash_data['sections'] ?? array() ) );
		}

		/**
		 * Output splash modal.
		 *
		 * @since 1.8.6
		 */
		public function admin_footer() {
			if ( ! $this->is_allow_splash() ) {
				return;
			}

			$this->render_modal();
		}

		/**
		 * Get splash data version.
		 *
		 * @since 1.8.6
		 *
		 * @return string Splash data version.
		 */
		private function get_splash_data_version(): string {

			return get_option( 'charitable_splash_data_version', charitable()->get_version() );
		}

		/**
		 * Update splash data version.
		 *
		 * @since 1.8.6
		 *
		 * @param string $version Splash data version.
		 */
		private function update_splash_data_version( string $version ) {

			update_option( 'charitable_splash_data_version', $version );
		}

		/**
		 * Get the latest splash version.
		 *
		 * @since 1.8.6
		 *
		 * @return string Splash version.
		 */
		private function get_latest_splash_version(): string {

			if ( $this->latest_splash_version ) {
				return $this->latest_splash_version;
			}

			$this->latest_splash_version = get_option( 'charitable_splash_version', '1.8.6' );

			// Create option if it doesn't exist.
			if ( empty( $this->latest_splash_version ) ) {
				$this->latest_splash_version = $this->default_plugin_version;

				update_option( 'charitable_splash_version', $this->latest_splash_version );
			}

			return $this->latest_splash_version;
		}

		/**
		 * Update option with the latest splash version.
		 *
		 * @since 1.8.6
		 */
		private function update_splash_version() {

			update_option( 'charitable_splash_version', $this->get_major_version( charitable()->get_version() ) );
		}

		/**
		 * Get a major version.
		 *
		 * @since 1.8.6
		 *
		 * @param string $version Version.
		 *
		 * @return string Major version.
		 */
		private function get_major_version( $version ): string {

			// Allow only digits and dots.
			$clean_version = preg_replace( '/[^0-9.]/', '.', $version );

			// Get version parts.
			$version_parts = explode( '.', $clean_version );

			// If a version has more than 3 parts - use only first 3. Get block data only for major versions.
			if ( count( $version_parts ) > 3 ) {
				$version = implode( '.', array_slice( $version_parts, 0, 3 ) );
			}

			return $version;
		}

		/**
		 * Get user license type.
		 *
		 * @since 1.8.6
		 *
		 * @return string
		 */
		private function get_user_license(): string {

			/**
			 * License type used for splash screen.
			 *
			 * @since 1.8.6
			 *
			 * @param string $license License type.
			 */
			return (string) apply_filters( 'charitable_admin_splash_splashtrait_get_user_license', 'lite' );
		}

		/**
		 * Get default splash modal data.
		 *
		 * @since 1.8.6
		 *
		 * @return array Splash modal data.
		 */
		private function get_default_data(): array {

			$default_data = array(
				'license' => $this->get_user_license(),
				'buttons' => array(
					'get_started' => __( 'Get Started', 'charitable' ),
					'learn_more'  => __( 'Learn More', 'charitable' ),
				),
				'header'  => array(
					'image'       => charitable()->get_path( 'assets', false ) . 'images/charitable-logo.svg',
					'title'       => __( 'What\'s New in Charitable', 'charitable' ),
					'description' => __( 'Since you\'ve been gone, we\'ve added some great new features to help grow your campaigns and generate more donations. Here are some highlights...', 'charitable' ),
				),

			);

			// If the chartiable_pro is active, that means they are licensed but not using Charitable Pro plugin.
			if ( ! charitable_is_pro() ) :
				$default_data['footer'] = array(
					'title'       => __( 'Add Your License To Activate Charitable Pro Plugin Now!', 'charitable' ),
					'description' => __( 'Charitable Pro is a powerful upgrade that allows you to manage donors along with built-in features like videos, donor comments, PDF receipts, a dashboard for donors, and more.', 'charitable' ),
					'upgrade'     => array(
						'text' => __( 'Learn More', 'charitable' ),
						'url'  => charitable_utm_link( 'https://www.wpcharitable.com/introducing-charitable-pro/', 'splash-modal', 'learn-more' ),
					),
				);
			else :
				$default_data['footer'] = array(
					'title'       => __( 'Thank you for using Charitable Pro!', 'charitable' ),
					'description' => __( 'We hope you love the new features and updates we\'ve made to Charitable Pro. Learn more about the latest updates and improvements.', 'charitable' ),
					'upgrade'     => array(
						'text' => __( 'Learn More', 'charitable' ),
						'url'  => charitable_utm_link( 'https://www.wpcharitable.com/blog/', 'splash-modal', 'learn-more' ),
					),
				);
			endif;

			return $default_data;
		}

		/**
		 * Determine if the current update is a minor update.
		 *
		 * Checks the charitable_upgrade_log option for the latest upgrade entry
		 * and compares its version with the current version to determine if this is
		 * a minor update (same major version) or major update (different major version).
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if it's a minor update, false otherwise.
		 */
		private function is_minor_update(): bool {
			$upgrade_log = get_option( 'charitable_upgrade_log', array() );

			// If no upgrade log exists, this is not a minor update.
			if ( empty( $upgrade_log ) ) {
				return false;
			}

			// Get the latest upgrade entry.
			$latest_entry = end( $upgrade_log );

			// If no version in the latest entry, this is not a minor update.
			if ( ! isset( $latest_entry['version'] ) ) {
				return false;
			}

			$latest_version  = $latest_entry['version'];
			$current_version = charitable()->get_version();

			// Get major versions (e.g., 1.8.5 from 1.8.5.1).
			$latest_major  = $this->get_major_version( $latest_version );
			$current_major = $this->get_major_version( $current_version );

			// If major versions match, this is a minor update.
			return $latest_major === $current_major;
		}

		/**
		 * Check if splash modal is allowed.
		 * Only allow on Charitable dashboard or settings screens; never on onboarding/setup welcome.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if allowed, false otherwise.
		 */
		public function is_allow_splash(): bool {

			// Never show on onboarding/setup welcome flow (splash would sit on top of onboarding UI).
			if ( $this->is_onboarding_setup_page() ) {
				return false;
			}

			// Only show on Charitable pages OR dashboard.
			return charitable_is_admin_screen() || $this->is_dashboard();
		}

		/**
		 * Check if current screen is the onboarding/setup welcome flow.
		 * Splash must not show here so it doesn't overlay the onboarding UI.
		 *
		 * @since 1.8.9.6
		 *
		 * @return bool True if on onboarding setup page, false otherwise.
		 */
		private function is_onboarding_setup_page(): bool {

			if ( ! is_admin() || empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return false;
			}

			$page = sanitize_text_field( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Block splash on the setup wizard, checklist, and any charitable-setup* pages.
			if ( strpos( $page, 'charitable-setup' ) !== false ) {
				return true;
			}

			// Block splash on the welcome/onboarding flow (page=charitable&wpchar_lite=lite&setup=...).
			if ( 'charitable' !== $page ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET['wpchar_lite'] ) || 'lite' !== $_GET['wpchar_lite'] ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET['setup'] ) ) {
				return false;
			}

			$setup = sanitize_key( $_GET['setup'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			return in_array( $setup, array( 'welcome', 'cancelled', 'return' ), true );
		}

		/**
		 * Check if splash modal should be opened.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if splash should open, false otherwise.
		 */
		private function should_open_splash(): bool {

			// Skip if announcements are hidden, or it is the dashboard page.
			if ( $this->is_dashboard() || $this->hide_splash_modal() ) {
				return false;
			}

			// If we are forcing the preview, then we should open the splash.
			if ( $this->is_force_open() ) {
				return true;
			}

			if ( ! $this->is_allow_splash() ) {
				return false;
			}

			// Allow if a splash version different from the current plugin major version, and it's not a new installation.
			if ( charitable_is_debug( 'splash' ) ) {
                // phpcs:disable
				error_log( 'Charitable Admin Splash: Latest splash version: ' . $this->get_latest_splash_version() );
				error_log( 'Charitable Admin Splash: Current major version: ' . $this->get_major_version( charitable()->get_version() ) );
				error_log( 'Charitable Admin Splash: Is new install: ' . ( $this->is_new_install() ? 'true' : 'false' ) );
				error_log( 'Charitable Admin Splash: Is force open: ' . ( $this->is_force_open() ? 'true' : 'false' ) );
                // phpcs:enable
			}

			$should_open_splash = $this->get_latest_splash_version() !== $this->get_major_version( charitable()->get_version() ) &&
				( ! $this->is_new_install() || $this->is_force_open() );

			if ( ! $should_open_splash ) {
				return false;
			}

			// Skip if user on the builder page and the Challenge can be started.
			if ( $this->is_builder_page() ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the current page is the builder page.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if it is the builder page, false otherwise.
		 */
		private function is_builder_page(): bool {
			return ! empty( $_GET['page'] ) && 'charitable-campaign-builder' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Check if the current page is the dashboard.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if it is the dashboard, false otherwise.
		 */
		private function is_dashboard(): bool {

			global $pagenow;

			if ( ! empty( $_GET['page'] ) && 'charitable-dashboard' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			return false;
		}

		/**
		 * Check if splash modal should be forced open.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if it should be forced open, false otherwise.
		 */
		private function is_force_open(): bool {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_key( $_GET['charitable_action'] ?? '' ) === 'preview-splash-screen';
		}

		/**
		 * Check if the plugin is newly installed.
		 *
		 * Checks the charitable_upgrade_log option for the initial installation entry
		 * and compares its version with the current version to determine if this is
		 * a new installation.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if new install, false otherwise.
		 */
		private function is_new_install(): bool {
			$upgrade_log = get_option( 'charitable_upgrade_log', array() );

			// If no upgrade log exists, this is a new install.
			if ( empty( $upgrade_log ) ) {
				return true;
			}

			// Find the initial installation entry.
			$install_entry = null;
			foreach ( $upgrade_log as $entry ) {
				if ( isset( $entry['message'] ) && 'Charitable was installed.' === $entry['message'] ) {
					$install_entry = $entry;
					break;
				}
			}

			// If no installation entry found, this is a new install.
			if ( ! $install_entry || ! isset( $install_entry['version'] ) ) {
				return true;
			}

			$installed_version = $install_entry['version'];
			$current_version   = charitable()->get_version();

			// Get major versions (e.g., 1.8.5 from 1.8.5.1).
			$installed_major = $this->get_major_version( $installed_version );
			$current_major   = $this->get_major_version( $current_version );

			// If major versions match and current version is not greater, this is a new install.
			return $installed_major === $current_major && version_compare( $current_version, $installed_version, '<=' );
		}

		/**
		 * Check if splash modal should be hidden.
		 *
		 * @since 1.8.6
		 *
		 * @return bool True if hidden, false otherwise.
		 */
		private function hide_splash_modal(): bool {

			/**
			 * Force to hide splash modal.
			 *
			 * @since 1.8.6
			 *
			 * @param bool $hide_splash_modal True to hide, false otherwise.
			 */
			return (bool) apply_filters( 'charitable_admin_splash_screen_hide_splash_modal', charitable_get_option( 'hide_announcements' ) );
		}
	}

endif;
