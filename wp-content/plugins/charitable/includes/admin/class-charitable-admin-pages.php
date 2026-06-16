<?php
/**
 * This class is responsible for adding the Charitable admin pages.
 *
 * @package   Charitable/Classes/Charitable_Admin_Pages
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.1.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Pages' ) ) :

	/**
	 * Charitable_Admin_Pages
	 *
	 * @since 1.0.0
	 */
	final class Charitable_Admin_Pages {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Admin_Pages|null
		 */
		private static $instance = null;

		/**
		 * The page to use when registering sections and fields.
		 *
		 * @var     string
		 */
		private $admin_menu_parent_page;

		/**
		 * The capability required to view the admin menu.
		 *
		 * @var     string
		 */
		private $admin_menu_capability;

		/**
		 * Create class object.
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			/**
			 * The default capability required to view Charitable pages.
			 *
			 * @since 1.0.0
			 *
			 * @param string $cap The capability required.
			 */
			$this->admin_menu_capability  = apply_filters( 'charitable_admin_menu_capability', 'view_charitable_sensitive_data' );
			$this->admin_menu_parent_page = 'charitable';

			// Hook into plugin installation to set timestamps for rotating menu
			add_action( 'upgrader_process_complete', array( $this, 'handle_plugin_installation' ), 10, 2 );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Admin_Pages
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * This forces the charitable menu to be open for category and tag pages.
		 *
		 * @since  1.7.0
		 *
		 * @param  string $parent_file The parent file.
		 *
		 * @return string
		 */
		public function menu_highlight( $parent_file ) {
			global $current_screen;

			$taxonomy = isset( $current_screen->taxonomy ) ? $current_screen->taxonomy : false;
			if ( false !== $taxonomy && ( 'campaign_category' === $taxonomy || 'campaign_tag' === $taxonomy ) ) {
				$parent_file = 'charitable';
			}

			$post_type = isset( $current_screen->post_type ) ? $current_screen->post_type : false;
			if ( false !== $post_type && ( 'campaign' === $post_type || 'donation' === $post_type ) ) {
				$parent_file = 'charitable';
			}

			return $parent_file;
		}

		/**
		 * Add Settings menu item under the Campaign menu tab.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function add_menu() {
			add_menu_page(
				'Charitable',
				'Charitable',
				'edit_campaigns', // phpcs:ignore
				$this->admin_menu_parent_page,
				array( $this, 'render_welcome_page' )
			);

			foreach ( $this->get_submenu_pages() as $page ) {
				if ( ! isset( $page['page_title'] )
					|| ! isset( $page['menu_title'] )
					|| ! isset( $page['menu_slug'] ) ) {
					continue;
				}

				$page_title = $page['page_title'];
				$menu_title = $page['menu_title'];
				$capability = isset( $page['capability'] ) ? $page['capability'] : $this->admin_menu_capability;
				$menu_slug  = $page['menu_slug'];
				$function   = isset( $page['function'] ) ? $page['function'] : '';

				add_submenu_page(
					$this->admin_menu_parent_page,
					$page_title,
					$menu_title,
					$capability,
					$menu_slug,
					$function
				);
			}
		}

		/**
		 * Returns an array with all the submenu pages.
		 *
		 * @since   1.0.0
		 * @since   1.8.8
		 *
		 * @return array
		 */
		private function get_submenu_pages() {
			$campaign_post_type = get_post_type_object( 'campaign' );
			$donation_post_type = get_post_type_object( 'donation' );

			/**
			 * Filter the list of submenu pages that come
			 * under the Charitable menu tab.
			 *
			 * @since   1.0.0
			 * @version 1.8.1 Added dashboard and reports.
			 * @version 1.8.1.6 Added tools.
			 * @version 1.8.1.8 Added SMTP.
			 * @version 1.8.2 Remove categories, tags, and customize.
			 * @version 1.8.5.1 Added donors.
			 *
			 * @param array $pages Every page is an array with at least a page_title,
			 *                     menu_title and menu_slug set.
			 */
			// Build dashboard pages array based on legacy dashboard setting
			$dashboard_pages = array();

			if ( charitable_use_legacy_dashboard() ) {
				// Show legacy dashboard
				$dashboard_pages[] = array(
					'page_title' => __( 'Dashboard', 'charitable' ),
					'menu_title' => __( 'Dashboard', 'charitable' ),
					'menu_slug'  => 'charitable-dashboard',
					'function'   => array( $this, 'render_dashboard_new_page' ),
					'capability' => 'manage_charitable_settings',
				);
			} else {
				// Show new dashboard (formerly beta)
				$dashboard_pages[] = array(
					'page_title' => __( 'Dashboard', 'charitable' ),
					'menu_title' => __( 'Dashboard', 'charitable' ),
					'menu_slug'  => 'charitable-dashboard',
					'function'   => array( $this, 'render_dashboard_page' ),
					'capability' => 'manage_charitable_settings',
				);
			}

			$pages = apply_filters(
				'charitable_submenu_pages',
				array_merge(
					$dashboard_pages,
					array(
						array(
							'page_title' => $campaign_post_type->labels->menu_name,
							'menu_title' => $campaign_post_type->labels->menu_name,
							'menu_slug'  => 'edit.php?post_type=campaign',
							'capability' => 'edit_campaigns',
						),
						array(
							'page_title' => $campaign_post_type->labels->add_new,
							'menu_title' => $campaign_post_type->labels->add_new,
							'menu_slug'  => 'post-new.php?post_type=campaign',
							'capability' => 'edit_campaigns',
						),
						array(
							'page_title' => $donation_post_type->labels->menu_name,
							'menu_title' => $donation_post_type->labels->menu_name,
							'menu_slug'  => 'edit.php?post_type=donation',
							'capability' => 'edit_donations',
						),
						array(
							'page_title' => __( 'Charitable Donors', 'charitable' ),
							'menu_title' => __( 'Donors', 'charitable' ) . ' <span class="charitable-menu-new-indicator">&nbsp;' . esc_html__( 'NEW', 'charitable' ) . '!</span>',
							'menu_slug'  => 'charitable-donors',
							'function'   => array( $this, 'render_donors_page' ),
							'capability' => 'manage_charitable_settings',
						),
						array(
							'page_title' => __( 'Charitable Reports', 'charitable' ),
							'menu_title' => __( 'Reports', 'charitable' ),
							'menu_slug'  => 'charitable-reports',
							'function'   => array( $this, 'render_reports_page' ),
							'capability' => 'manage_charitable_settings',
						),
						array(
							'page_title' => __( 'Charitable Tools', 'charitable' ),
							'menu_title' => __( 'Tools', 'charitable' ),
							'menu_slug'  => 'charitable-tools',
							'function'   => array( $this, 'render_tools_page' ),
							'capability' => 'manage_charitable_settings',
						),
						array(
							'page_title' => __( 'Charitable Settings', 'charitable' ),
							'menu_title' => __( 'Settings', 'charitable' ),
							'menu_slug'  => 'charitable-settings',
							'function'   => array( $this, 'render_settings_page' ),
							'capability' => 'manage_charitable_settings',
						),
						array(
							'page_title' => __( 'Charitable Addons', 'charitable' ),
							'menu_title' => '<span style="color:#f18500">' . __( 'Addons', 'charitable' ) . '</span>',
							'menu_slug'  => 'charitable-addons',
							'function'   => array( $this, 'render_addons_directory_page' ),
							'capability' => 'manage_charitable_settings',
						),
						array(
							'page_title' => __( 'SMTP', 'charitable' ),
							'menu_title' => __( 'SMTP', 'charitable' ),
							'menu_slug'  => 'charitable-smtp',
							'function'   => array( $this, 'render_smtp_page' ),
							'capability' => 'manage_charitable_settings',
						),
					)
				)
			);

			// Rotating submenu: Privacy Compliance / SMTP.
			$rotation = $this->get_rotating_marketing_submenu();

			if ( $rotation ) {
				$pages = array_merge( $pages, array(
					array(
						'page_title' => $rotation['page_title'],
						'menu_title' => $rotation['menu_title'],
						'menu_slug'  => $rotation['menu_slug'],
						'function'   => array( $this, 'admin_page' ),
						'capability' => 'manage_charitable_settings',
					)
				) );
			}

			$pages = array_merge( $pages, array(
				array(
					'page_title' => __( 'About Charitable', 'charitable' ),
					'menu_title' => __( 'About Us', 'charitable' ),
					'menu_slug'  => 'charitable-about',
					'function'   => array( $this, 'render_about_page' ),
					'capability' => 'view_charitable_sensitive_data',
				)
			) );

			return $pages;
		}

		/**
		 * Determine which marketing submenu item to show (rotation).
		 *
		 * Current behavior:
		 * - Show "Privacy Compliance" until the WPConsent plugin has been activated for 7 or more days.
		 * - Once 7+ days have passed since WPConsent first activation, show "SMTP".
		 * - This can be extended to more items later and to use per-item activation timestamps.
		 *
		 * @since 1.8.8
		 *
		 * @return array|null { menu_title, page_title, menu_slug } or null to show none.
		 */
		private function get_rotating_marketing_submenu(): ?array {

			$items = $this->get_marketing_rotation_items();
			if ( empty( $items ) ) {
				return null;
			}

			$now = time();

			// Find the first item that should still be displayed.
			foreach ( $items as $item ) {
				$label        = $item['label'] ?? '';
				$menu_slug    = $item['menu_slug'] ?? '';
				$option_name  = $item['option_name'] ?? '';
				$option_key   = $item['option_key'] ?? '';
				$plugin_file  = $item['plugin_file'] ?? '';
				$period_days  = (int) ( $item['period_days'] ?? 7 );

				if ( empty( $label ) || empty( $menu_slug ) ) {
					continue; // Skip misconfigured entries.
				}

				// If a reference plugin is provided and it's not active, keep showing this item.
				if ( ! empty( $plugin_file ) ) {
					if ( ! function_exists( 'is_plugin_active' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					if ( function_exists( 'is_plugin_active' ) && ! is_plugin_active( $plugin_file ) ) {
						return [
							'menu_title' => $label,
							'page_title' => $label,
							'menu_slug'  => $menu_slug,
						];
					}
				}

				$ts = 0;
				if ( ! empty( $option_name ) ) {
					$opt = get_option( $option_name );
					if ( is_array( $opt ) ) {
						$ts = isset( $opt[ $option_key ] ) && is_numeric( $opt[ $option_key ] ) ? (int) $opt[ $option_key ] : 0;
					} elseif ( is_numeric( $opt ) ) {
						$ts = (int) $opt;
					}
				}

				$threshold = max( 0, $period_days ) * DAY_IN_SECONDS;
				$within    = $ts === 0 || ( $now - $ts ) < $threshold;

				if ( $within ) {
					return [
						'menu_title' => $label,
						'page_title' => $label,
						'menu_slug'  => $menu_slug,
					];
				}
			}

			// If all items are considered "complete", return the first one (cycle back).
			$first = $items[0];
			return [
				'menu_title' => $first['label'],
				'page_title' => $first['label'],
				'menu_slug'  => $first['menu_slug'],
			];
		}

		/**
		 * Editable list of rotating marketing submenu items.
		 *
		 * @since 1.8.8
		 *
		 * @return array
		 */
		private function get_marketing_rotation_items(): array {
			return [
				[
					'label'       => esc_html__( 'Privacy Compliance', 'charitable' ),
					'menu_slug'   => 'charitable-privacy-compliance',
					'option_name' => 'wpconsent_activated',
					'option_key'  => 'wpconsent',
					'plugin_file' => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
					'period_days' => 7,
				],
				[
					'label'       => esc_html__( 'Backups', 'charitable' ),
					'menu_slug'   => 'charitable-backups',
					'option_name' => 'duplicator_activated',
					'option_key'  => 'duplicator',
					'plugin_file' => 'duplicator/duplicator.php',
					'period_days' => 7,
				],
				[
					'label'       => esc_html__( 'SEO', 'charitable' ),
					'menu_slug'   => 'charitable-seo',
					'option_name' => 'all-in-one-seo-pack_activated',
					'option_key'  => 'all-in-one-seo-pack',
					'plugin_file' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
					'period_days' => 7,
				],
				[
					'label'       => esc_html__( 'Automation', 'charitable' ),
					'menu_slug'   => 'charitable-automation',
					'option_name' => 'uncanny-automator_activated',
					'option_key'  => 'uncanny_automator',
					'plugin_file' => 'uncanny-automator/uncanny-automator.php',
					'period_days' => 7,
				],
			];
		}

		/**
		 * Set up the redirect to the welcome page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function setup_welcome_redirect() {
			add_action( 'admin_init', array( self::get_instance(), 'redirect_to_welcome' ) );
		}

		/**
		 * Redirect to the welcome page.
		 *
		 * @since  1.3.0
		 *
		 * @return void
		 */
		public function redirect_to_welcome() {
			wp_safe_redirect( admin_url( 'admin.php?page=charitable&install=true' ) );
			exit;
		}

		/**
		 * Display the Charitable donors page.
		 *
		 * @since  1.8.5
		 *
		 * @return void
		 */
		public function render_donors_page() {
			charitable_admin_view( 'donors/donors' );
		}

		/**
		 * Display the Charitable reports page.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function render_reports_page() {
			charitable_admin_view( 'reports/reports' );
		}

		/**
		 * Display the Charitable tools page.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function render_tools_page() {
			charitable_admin_view( 'tools/tools' );
		}

		/**
		 * Display the Charitable SMTP page.
		 *
		 * @since  1.8.1.8
		 *
		 * @return void
		 */
		public function render_smtp_page() {
			charitable_admin_view( 'smtp/smtp' );
		}

		/**
		 * Display the Charitable About page.
		 *
		 * @since  1.8.7.6
		 *
		 * @return void
		 */
		public function render_about_page() {
			charitable_admin_view( 'about/about' );
		}

		/**
		 * Display the Charitable Growth Tools page.
		 *
		 * @since  1.8.1.6
		 *
		 * @return void
		 */
		public function render_growth_tools_page() {
			charitable_admin_view( 'growth-tools/growth-tools' );
		}

		/**
		 * Display the Charitable Privacy Compliance page.
		 *
		 * @since  1.8.8
		 *
		 * @return void
		 */
		public function render_privacy_compliance_page() {
			charitable_admin_view( 'privacy-compliance/privacy-compliance' );
		}

		/**
		 * Display the Charitable Backups page.
		 *
		 * @since  1.8.8
		 *
		 * @return void
		 */
		public function render_backups_page() {
			charitable_admin_view( 'backups/backups' );
		}

		/**
		 * Display the Charitable SEO page.
		 *
		 * @since  1.8.8
		 *
		 * @return void
		 */
		public function render_seo_page() {
			charitable_admin_view( 'seo/seo' );
		}

		/**
		 * Display the Charitable Automation page.
		 *
		 * @since  1.8.8
		 *
		 * @return void
		 */
		public function render_automation_page() {
			charitable_admin_view( 'automation/automation' );
		}

		/**
		 * Dynamic admin page renderer for rotating marketing submenu items.
		 *
		 * @since  1.8.8
		 *
		 * @return void
		 */
		public function admin_page() {
			$rotation = $this->get_rotating_marketing_submenu();
			if ( ! $rotation ) {
				return;
			}

			$menu_slug = $rotation['menu_slug'];

			switch ( $menu_slug ) {
				case 'charitable-privacy-compliance':
					$this->render_privacy_compliance_page();
					break;
				case 'charitable-backups':
					$this->render_backups_page();
					break;
				case 'charitable-seo':
					$this->render_seo_page();
					break;
				case 'charitable-automation':
					$this->render_automation_page();
					break;
				default:
					// Fallback to privacy compliance if unknown
					$this->render_privacy_compliance_page();
					break;
			}
		}

		/**
		 * Display the Charitable dashboard page.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function render_dashboard_page() {
			$this->enqueue_dashboard_spinner_script();
			charitable_admin_view( 'dashboard/dashboard' );
		}

		/**
		 * Display the Charitable dashboard new page.
		 *
		 * @since  1.8.1
		 *
		 * @return void
		 */
		public function render_dashboard_new_page() {
			$this->enqueue_dashboard_spinner_script();
			charitable_admin_view( 'dashboard/dashboard-legacy' );
		}

		/**
		 * Enqueue dashboard spinner script
		 *
		 * @since  1.8.7.5
		 *
		 * @return void
		 */
		private function enqueue_dashboard_spinner_script() {
			// Add inline script to hide spinner when dashboard content loads
			wp_add_inline_script( 'charitable-admin-dashboard', '
				(function() {
					// Hide spinners immediately when dashboard content is detected
					function hideDashboardSpinners() {
						document.body.classList.add("charitable-dashboard-loaded");
						var dashboardV2 = document.getElementById("charitable-dashboard-v2");
						if (dashboardV2) {
							dashboardV2.classList.add("charitable-dashboard-loaded");
						}
					}

					// Hide spinners on DOM ready
					if (document.readyState === "loading") {
						document.addEventListener("DOMContentLoaded", hideDashboardSpinners);
					} else {
						hideDashboardSpinners();
					}

					// Also hide spinners when dashboard content is detected
					var checkForContent = function() {
						var hasContent = document.querySelector("#charitable-dashboard-v2 .charitable-dashboard-v2-content") ||
										document.querySelector("#charitable-dashboard-report-container") ||
										document.querySelector(".charitable-dashboard-report");

						if (hasContent) {
							hideDashboardSpinners();
						} else {
							setTimeout(checkForContent, 100);
						}
					};

					// Start checking for content after a short delay
					setTimeout(checkForContent, 200);
				})();
			' );
		}

		/**
		 * Display the Charitable settings page.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function render_settings_page() {
			charitable_admin_view( 'settings/settings' );
		}

		/**
		 * Display the Charitable addons page.
		 *
		 * @since  1.7.0.4
		 *
		 * @return void
		 */
		public function render_addons_directory_page() {
			charitable_admin_view( 'addons-directory/addons-directory' );
		}

		/**
		 * Display the Charitable donations page.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 *
		 * @deprecated 1.4.0
		 */
		public function render_donations_page() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				__( 'Donations page now rendered by WordPress default manage_edit-donation_columns', 'charitable' )
			);

			charitable_admin_view( 'donations-page/page' );
		}

		/**
		 * Display the Charitable welcome page.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function render_welcome_page() {
			charitable_admin_view( 'welcome-page/page' );
		}

		/**
		 * Return a preview URL for the customizer.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		private function get_customizer_campaign_preview_url() {
			$campaign = Charitable_Campaigns::query(
				array(
					'posts_per_page' => 1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'     => '_campaign_end_date',
							'value'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							'compare' => '>=',
							'type'    => 'datetime',
						),
						array(
							'key'     => '_campaign_end_date',
							'value'   => 0,
							'compare' => '=',
						),
					),
				)
			);

			if ( $campaign->found_posts ) {
				$url = charitable_get_permalink(
					'campaign_donation',
					array(
						'campaign_id' => current( $campaign->posts ),
					)
				);
			}

			if ( ! isset( $url ) || false === $url ) {
				$url = home_url();
			}

			return urlencode( $url );
		}

		/**
		 * Handle plugin installation to set timestamps for rotating menu items.
		 *
		 * @since 1.8.8
		 *
		 * @param WP_Upgrader $upgrader The upgrader instance.
		 * @param array       $hook_extra Extra arguments passed to hooked filters.
		 * @return void
		 */
		public function handle_plugin_installation( $upgrader, $hook_extra ) {
			// Only process plugin installations, not updates
			if ( ! isset( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) {
				return;
			}

			// Only process installations, not updates
			if ( ! isset( $hook_extra['action'] ) || 'install' !== $hook_extra['action'] ) {
				return;
			}

			$items = $this->get_marketing_rotation_items();
			$current_time = time();

			foreach ( $items as $item ) {
				$option_name = $item['option_name'] ?? '';
				$option_key = $item['option_key'] ?? '';
				$plugin_file = $item['plugin_file'] ?? '';

				if ( empty( $option_name ) || empty( $plugin_file ) ) {
					continue;
				}

				// Check if the installed plugin matches our rotating menu items
				$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
				if ( file_exists( $plugin_path ) ) {
					// Set timestamp for this plugin installation
					$existing_option = get_option( $option_name );

					if ( is_array( $existing_option ) ) {
						$existing_option[ $option_key ] = $current_time;
						update_option( $option_name, $existing_option );
					} else {
						update_option( $option_name, array( $option_key => $current_time ) );
					}
				}
			}
		}

		/**
		 * Handle plugin activation to set timestamps for rotating menu items.
		 * (Kept for reference but not used in current implementation)
		 *
		 * @since 1.8.8
		 *
		 * @param string $plugin The plugin file that was activated.
		 * @return void
		 */
		public function handle_plugin_activation( $plugin ) {
			// This method is kept for reference but not used in current implementation
			// The rotating menu now tracks installations instead of activations
		}
	}

endif;
