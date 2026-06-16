<?php
/**
 * The class that defines a subpanel for the settings area for the campaign builder.
 *
 * @package   Charitable/Admin/Charitable_Campaign_Meta_Boxes
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version.  1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Settings_Addons' ) ) :

	/**
	 * General subpanel for Settings Panel for campaign builder.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Settings_Addons {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $addon_array = array();

		/**
		 * Get things going. Add action hooks for the sidebar menu and the panel itself.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			$this->init();

			add_action( 'charitable_campaign_builder_settings_sidebar', array( $this, 'sidebar_tab' ), 55 );
		}

		/**
		 * Init some things.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			$this->addon_array = apply_filters(
				'charitable_campaign_builder_settings_addons_not_available',
				array(
					'ambassadors'         => array(
						'label'              => esc_html__( 'Ambassadors &amp; Peer to Peer Fundraising', 'charitable' ),
						'addon-slug'         => 'charitable-ambassadors',
						'plan-to-upgrade-to' => 'pro',
					),
					'recurring-donations' => array(
						'label'              => esc_html__( 'Recurring Donations', 'charitable' ),
						'addon-slug'         => 'charitable-recurring',
						'plan-to-upgrade-to' => 'plus',
					),
					'fee-relief'          => array(
						'label'              => esc_html__( 'Fee Relief', 'charitable' ),
						'addon-slug'         => 'charitable-fee-relief',
						'plan-to-upgrade-to' => 'plus',
					),
					'simple-updates'      => array(
						'label'              => esc_html__( 'Simple Updates', 'charitable' ),
						'addon-slug'         => 'charitable-simple-updates',
						'plan-to-upgrade-to' => 'plus',
					),
					'geolocation'         => array(
						'label'              => esc_html__( 'Geolocation', 'charitable' ),
						'addon-slug'         => 'charitable-geolocation',
						'plan-to-upgrade-to' => 'plus',
					),
				)
			);
		}

		/**
		 * Sort the addon array "on the fly" so that items without "file" stay at the bottom.
		 *
		 * @since 1.8.0
		 * @param array $addon_array The array of addons.
		 */
		private function sort_addon_array( $addon_array ) {

			$with_file    = [];
			$without_file = [];

			foreach ( $addon_array as $key => $item ) {
				if ( isset( $item['file'] ) ) {
					$with_file[ $key ] = $item;
				} else {
					$without_file[ $key ] = $item;
				}
			}

			return $with_file + $without_file;
		}

		/**
		 * Retrieve the plugin basename from the plugin slug.
		 *
		 * @since 1.7.0
		 *
		 * @param string $slug The plugin slug.
		 * @return string The plugin basename if found, else the plugin slug.
		 */
		public function get_plugin_basename_from_slug( $slug ) {

			$keys = array_keys( get_plugins() );

			foreach ( $keys as $key ) {
				if ( preg_match( '|^' . $slug . '|', $key ) ) {
					return $key;
				}
			}

			return $slug;
		}

		/**
		 * Generate sidebar html.
		 *
		 * @since 1.8.0
		 * @since 1.8.6.2 - Tweak recurring add-on + not available logic.
		 */
		public function sidebar_tab() {

			if ( ! is_array( $this->addon_array ) || empty( $this->addon_array ) ) :
				return;
			endif;

			// grab the list of active plugins only once, don't need it in the loop.
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'active_plugins' is a WordPress core hook.

			// sort array.
			$addon_array = $this->sort_addon_array( ( $this->addon_array ) );

			if ( empty( $addon_array ) ) {
				return;
			}

			$installed_plugins = get_plugins();

			// get license stuff once.
			$charitable_addons = get_transient( '_charitable_addons' );

			// Get addons data from transient or perform API query if no transient.
			if ( false === $charitable_addons ) {
				$charitable_addons = charitable_get_addons_data_from_server();
			}

			$current_plan_slug = charitable_get_license_slug_from_plan_id();

			foreach ( $addon_array as $slug => $addon_info ) {

				$data_path   = false; // 'charitable-conversational-forms/charitable-conversational-forms.php';
				$slug        = esc_attr( $slug );
				$license     = '';
				$addon_slug  = esc_attr( $addon_info['addon-slug'] );
				$label       = esc_html( $addon_info['label'] );
				$valid_plans = array(); // populate with all the valid plan types for this addon.

				if ( ! empty( $charitable_addons ) ) :
					foreach ( $charitable_addons as $charitable_addon ) {
						if ( ! empty( $charitable_addon['slug'] ) && strpos( $charitable_addon['slug'], $addon_slug ) !== false ) {
							$charitable_addon_info = (array) $charitable_addon;
							break;
						}
					}
					if ( ! empty( $charitable_addon_info['license'] ) && ( in_array( 'agency', $charitable_addon_info['license'], true ) || in_array( 'elite', $charitable_addon_info['license'], true ) ) ) {
						$license = 'pro';
					} elseif ( ! empty( $charitable_addon_info['license'] ) ) {
						$license = reset( $charitable_addon_info['license'] );
					}

				endif;

				switch ( strtolower( $current_plan_slug ) ) {
					case 'pro':
						$valid_plans = array( 'basic', 'plus', 'pro', 'agency', 'elite' );
						break;
					case 'plus':
						$valid_plans = array( 'basic', 'plus' );
						break;
					case 'basic':
						$valid_plans = array( 'basic' );
						break;
					default:
						$valid_plans = array(); // empty.
						break;
				}

				// determine css classes.
				$classes = array(
					'charitable-panel-sidebar-section',
					'charitable-panel-sidebar-section-' . $slug,
				);

				// Determine the plugin basename.
				$plugin_basename = $this->get_plugin_basename_from_slug( $addon_info['addon-slug'] );

				// Setup an attribute for a url to download the addon, in case we need it.
				$install_url = false;

				// If the addon requires an upgrade of the current (valid) plan.
				if ( ! in_array( $license, $valid_plans, true ) ) {
					$classes[] = 'charitable-not-available';
					// Determine if the addon is installed, active, or needs to be installed based on license and other factors.
				} elseif ( ! charitable_is_pro() && in_array( $addon_slug . '/' . $addon_slug . '.php', $active_plugins, true ) ) {
					// if pro isn't active but it's in the plugin directory, that's a special case.
					$classes[] = 'charitable-addon-installed-charitable-lite charitable-addon-' . $slug;
				} elseif ( charitable_is_pro() && in_array( $addon_slug . '/' . $addon_slug . '.php', $active_plugins, true ) ) {
					// if paid version is installed but somehow user has the addon installed, that's a special case.
					$classes[] = 'charitable-addon-installed-charitable-pro charitable-addon-' . $slug;

				} elseif ( ! charitable_is_pro() && ! in_array( $addon_slug . '/' . $addon_slug . '.php', $active_plugins, true ) ) {

					$classes[] = 'charitable-not-available';

				} elseif ( ! empty( $valid_plans ) && ! in_array( $current_plan_slug, $valid_plans, true ) ) { // charitable version is a non-lite paid "pro" version but assign the correct class that either warrants an upgrade, install, or is installed.

					// if pro is active and the plan isn't high enough, add the CSS class.
					$classes[] = 'charitable-need-upgrade';

					// Set the license to the required plan level for upgrade, not the user's current plan.
					if ( ! empty( $addon_info['plan-to-upgrade-to'] ) ) {
						$license = $addon_info['plan-to-upgrade-to'];
					}
				} elseif ( isset( $installed_plugins[ $plugin_basename ] ) && ! is_plugin_active( $plugin_basename ) ) {

					// if the plugin exists (installed, visible in the plugin list in WP admin) but not activated.
					$classes[] = 'charitable-not-activated';

				} elseif ( ! in_array( 'charitable-' . $slug . '/charitable-' . $slug . '.php', $active_plugins, true ) ) {

					// if pro is active AND the plugin is NOT activated, then that's another CSS class to encourage an install.
					$classes[] = 'charitable-not-installed';

					$install_url = ! empty( $charitable_addon_info['download_link'] ) ? esc_url( $charitable_addon_info['download_link'] ) : false;

					// If the install url is not a valid URL, then set it to false.
					if ( ! filter_var( $install_url, FILTER_VALIDATE_URL ) ) {
						$install_url = false;
					}
				} else {

					// where do we load the class, this has to be supplied by the addon.
					$file = isset( $addon_info['file'] ) && ! empty( $addon_info['file'] ) ? esc_html( $addon_info['file'] ) : false;

					if ( $file ) {

						$file = wp_slash( charitable()->get_path( 'plugin-directory' ) ) . $file;

						if ( file_exists( $file ) ) {
							$classes[] = 'charitable-addon-installed charitable-addon-loaded';
							require_once $file;
						} else {
							$classes[] = 'charitable-addon-installed charitable-addon-file-nonexist';
						}

					} else {

						$classes[] = 'charitable-addon-installed charitable-addon-file-missing';

					}
				}

				$classes = implode( ' ', $classes );

				echo '<a href="#" class="' . esc_attr( $classes ) . '" data-plugin-url="' . esc_attr( $addon_slug ) . '/' . esc_attr( $addon_slug ) . '.php' . '" data-name="' . esc_html( $label ) . ' addon" data-slug="' . esc_attr( $slug ) . '" data-section="' . esc_attr( $slug ) . '" data-action="" data-path="' . esc_attr( $data_path ) . '" data-video="" data-install="' . esc_url( $install_url ) . '" data-license="' . esc_attr( $license ) . '" data-utm-content="' . urlencode( $label ) . '"> ' . esc_html( $label ) . ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>';

			}
		}
	}

	new Charitable_Builder_Panel_Settings_Addons();

endif;
