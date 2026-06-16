<?php
/**
 * Charitable Addons Directory.
 *
 * @package   Charitable/Classes/Charitable_Addons_Directory
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Addons_Directory' ) ) :

	/**
	 * Charitable_Addons_Directory
	 *
	 * @final
	 * @since 1.0.0
	 */
	final class Charitable_Addons_Directory {

		/**
		 * The update URL.
		 *
		 * @var string
		 */
		const UPDATE_URL = 'https://wpcharitable.com';

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Addons_Directory|null
		 */
		private static $instance = null;

		/**
		 * All the stored licenses.
		 *
		 * @var array
		 */
		private $licenses;

		/**
		 * The key for the failed request cache.
		 *
		 * @var string
		 */
		private $failed_request_cache_key = 'wpc_edd_sl_failed_plugin_versions';

		/**
		 * Determine if the plugin/addon installations are allowed.
		 *
		 * @since 1.6.7
		 *
		 * @var bool
		 */
		private $can_install;

		/**
		 * Create object instance.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Init.
		 *
		 * @since 1.6.7
		 */
		public function init() {

			$this->can_install = charitable_can_install( 'addon' );

			do_action( 'charitable_addons_directory_settings_start', $this );

			add_action( 'admin_enqueue_scripts', array( $this, 'charitable_admin_scripts' ) );

			// Add callbacks for content.
			add_action( 'charitable_addons_directory_section', array( $this, 'addons_directory_content' ) );
		}

		/**
		 * Add JS text scripts and load admin scripts.
		 *
		 * @since   1.8.0
		 * @version 1.8.1.15 - Added upgrade_modal.
		 */
		public function charitable_admin_scripts() {

			$min     = charitable_get_min_suffix();
			$version = charitable()->get_version();

			$assets_dir = charitable()->get_path( 'assets', false );

			$strings = apply_filters(
				'charitable_admin_strings',
				array(
					'nonce'                           => wp_create_nonce( 'charitable-admin' ),
					'addon_activate'                  => esc_html__( 'Activate', 'charitable' ),
					'addon_activated'                 => esc_html__( 'Activated', 'charitable' ),
					'addon_active'                    => esc_html__( 'Active', 'charitable' ),
					'addon_deactivate'                => esc_html__( 'Deactivate', 'charitable' ),
					'addon_inactive'                  => esc_html__( 'Inactive', 'charitable' ),
					'addon_install'                   => esc_html__( 'Install Addon', 'charitable' ),
					'addon_error'                     => sprintf(
						wp_kses( /* translators: %1$s - An addon download URL, %2$s - Link to manual installation guide. */
							__( 'Could not install the addon. Please <a href="%1$s" target="_blank" rel="noopener noreferrer">download it from wpcharitable.com</a> and <a href="%2$s" target="_blank" rel="noopener noreferrer">install it manually</a>.', 'charitable' ),
							[
								'a' => [
									'href'   => true,
									'target' => true,
									'rel'    => true,
								],
							]
						),
						'https://wpcharitable.com/account/licenses/',
						'https://wpcharitable.com/docs/how-to-manually-install-addons-in-charitable/'
					),
					'plugin_error'                    => esc_html__( 'Could not install the plugin automatically. Please download and install it manually.', 'charitable' ),
					'addon_search'                    => esc_html__( 'Searching Addons', 'charitable' ),
					'ajax_url'                        => admin_url( 'admin-ajax.php' ),
					'admin_url'                       => admin_url(),
					'ok'                              => esc_html__( 'OK', 'charitable' ),
					'cancel'                          => esc_html__( 'Cancel', 'charitable' ),
					'close'                           => esc_html__( 'Close', 'charitable' ),
					'plugin_install_activate_btn'     => esc_html__( 'Install and Activate', 'charitable' ),
					'plugin_install_activate_confirm' => esc_html__( 'needs to be installed and activated to import its forms. Would you like us to install and activate it for you?', 'charitable' ),
					'plugin_activate_btn'             => esc_html__( 'Activate', 'charitable' ),
					'plugin_activate_confirm'         => esc_html__( 'needs to be activated to import its forms. Would you like us to activate it for you?', 'charitable' ),
					'connecting'                      => esc_html__( 'Connecting...', 'charitable' ),
					'save_refresh'                    => esc_html__( 'Save and Refresh', 'charitable' ),
					'server_error'                    => esc_html__( 'Unfortunately there was a server connection error.', 'charitable' ),
					'testing'                         => esc_html__( 'Testing', 'charitable' ),
					'upgrade_completed'               => esc_html__( 'Upgrade was successfully completed!', 'charitable' ),
					'edit_license'                    => esc_html__( 'To edit the License Key, please first click the Deactivate Key button. Please note that deactivating this key will remove access to updates, addons, and support.', 'charitable' ),
					'something_went_wrong'            => esc_html__( 'Something went wrong', 'charitable' ),
					'success'                         => esc_html__( 'Success', 'charitable' ),
					'loading'                         => esc_html__( 'Loading...', 'charitable' ),
					'use_simple_contact_form'         => esc_html__( 'Use Simple Contact Form Template', 'charitable' ),
					'error_select_template'           => esc_html__( 'Something went wrong while applying the template.', 'charitable' ),
					'plugin_asset_dir'                => charitable()->get_path( 'assets', false ),
					'install'                         => esc_html__( 'Install', 'charitable' ),
					'activate'                        => esc_html__( 'Activate', 'charitable' ),
					'setup'                           => esc_html__( 'Setup', 'charitable' ),
					'settings'                        => esc_html__( 'Settings', 'charitable' ),
					'thanks_for_interest'             => esc_html__( 'Thanks for your interest in Charitable Pro!', 'charitable' ),
					'upgrade_modal'                   => charitable_get_upgrade_modal_text(),
					'autoshow_plugin_notifications'   => charitable_get_autoshow_plugin_notifications(),
					'enable_express_checkout_message' => __( 'To enable this payment method, enable Express Checkout', 'charitable' ),
					'activate_now'                    => esc_html__( 'Activate Now', 'charitable' ),
					'activate_later'                  => esc_html__( 'Activate Later', 'charitable' ),
					'download_pro'                    => esc_html__( 'Download Pro', 'charitable' ),
					'downloading'                     => esc_html__( 'Downloading...', 'charitable' ),
					'downloading_pro_message'         => esc_html__( 'Please wait while we download Charitable Pro.', 'charitable' ),
					'activating'                      => esc_html__( 'Activating...', 'charitable' ),
					'activating_pro_message'          => esc_html__( 'Please wait while we activate Charitable Pro.', 'charitable' ),
					'download_failed'                 => esc_html__( 'Download failed. Please try again.', 'charitable' ),
					'activation_failed'               => esc_html__( 'Activation failed. You can activate it manually from the Plugins page.', 'charitable' ),
					'visit_addons_page'               => esc_html__( 'Visit Addons Page', 'charitable' ),
					'ready_to_use'                    => esc_html__( 'Ready to Use', 'charitable' ),
					'pro_ready_to_activate'           => esc_html__( 'Charitable Pro has been downloaded and is ready to activate when you\'re ready. You can activate it from the Plugins page.', 'charitable' ),
					'verifying'                       => esc_html__( 'Verifying...', 'charitable' ),
					'deactivating'                    => esc_html__( 'Deactivating...', 'charitable' ),
					'manual_install'                  => esc_html__( 'Manual Install', 'charitable' ),
					'license_validated'               => esc_html__( 'License Validated', 'charitable' ),
				)
			);

			wp_enqueue_script(
				'listjs',
				$assets_dir . 'js/libraries/list.min.js',
				[ 'jquery' ],
				$version,
				false
			);

			wp_register_style(
				'charitable-admin-addons-directory',
				$assets_dir . 'css/admin/charitable-admin-addons-directory' . $min . '.css',
				array(),
				$version
			);
			wp_enqueue_style( 'charitable-admin-addons-directory' );

			wp_register_script(
				'charitable-admin-addon-directory',
				$assets_dir . 'js/admin/charitable-admin-addon-directory' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			wp_enqueue_script( 'charitable-admin-addon-directory' );

			wp_localize_script(
				'charitable-admin-addon-directory',
				'charitable_admin',
				$strings
			);
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Addons_Directory
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Attemps to confirm if the current license is a legacy one, even if we have a plan_id / slug (which normally came from newer license)
		 *
		 * @since  1.7.0.4
		 *
		 * @return Charitable_Addons_Directory
		 */
		public static function is_current_plan_legacy() {

			$settings = get_option( 'charitable_settings' );

			// always check the v2 settings first.
			$plan_id = isset( $settings['licenses']['charitable-v2']['plan_id'] ) ? intval( $settings['licenses']['charitable-v2']['plan_id'] ) : false;
			$valid   = isset( $settings['licenses']['charitable-v2']['valid'] ) ? trim( esc_html( $settings['licenses']['charitable-v2']['valid'] ) ) : false;

			if ( false === $valid || '' === $valid ) {
				// try looking at the "legacy" license for a plan_id.
				if ( isset( $settings['licenses']['charitable'] ) ) {
					$plan_id = isset( $settings['licenses']['charitable']['plan_id'] ) ? intval( $settings['licenses']['charitable']['plan_id'] ) : false;
					$valid   = isset( $settings['licenses']['charitable']['valid'] ) ? trim( esc_html( $settings['licenses']['charitable']['valid'] ) ) : false;

					if ( $plan_id ) {
						return true;
					}
				}

				// try looking for an install that just installed an addon license only, which would be another example of a "legacy" license.
				if ( ! empty( $settings['licenses'] ) ) {
					$addon_licenses = $settings['licenses'];
					if ( isset( $addon_licenses['charitable'] ) ) {
						unset( $addon_licenses['charitable'] );
					}
					if ( isset( $addon_licenses['charitable_pro'] ) ) {
						unset( $addon_licenses['charitable_pro'] );
					}
					foreach ( $addon_licenses as $addon_license ) {
						if ( isset( $addon_license['valid'] ) && 1 === intval( $addon_license['valid'] ) ) {
							return true;
						}
					}
				}
			}

			return false;
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Addons_Directory
		 */
		public static function get_current_plan_slug() {

			$settings = get_option( 'charitable_settings' );

			// always check the v2 settings first.
			$plan_id = isset( $settings['licenses']['charitable-v2']['plan_id'] ) ? intval( $settings['licenses']['charitable-v2']['plan_id'] ) : false;
			$valid   = isset( $settings['licenses']['charitable-v2']['valid'] ) ? trim( esc_html( $settings['licenses']['charitable-v2']['valid'] ) ) : false;

			if ( false === $valid || '' === $valid ) {
				// try looking at the "legacy" license for a plan_id.
				if ( isset( $settings['licenses']['charitable'] ) ) {
					$plan_id = isset( $settings['licenses']['charitable']['plan_id'] ) ? intval( $settings['licenses']['charitable']['plan_id'] ) : false;
					$valid   = isset( $settings['licenses']['charitable']['valid'] ) ? trim( esc_html( $settings['licenses']['charitable']['valid'] ) ) : false;
				}
			}

			if ( $valid ) {
				switch ( $plan_id ) {
					case 1:
						$plan_slug = 'basic';
						break;
					case 2:
						$plan_slug = 'plus';
						break;
					case 3:
						$plan_slug = 'pro';
						break;
					case 4:
						$plan_slug = 'agency';
						break;
					default:
						$plan_slug = 'lite';
						break;
				}
			} else {
				$plan_slug = 'lite';
			}

			return $plan_slug;
		}

		/**
		 * Callback for displaying the UI for Addons.
		 *
		 * @since 1.7.0
		 * @version 1.8.8.6
		 */
		public function addons_directory_content() {

			// Get Addons.
			$temp_addons = $this->get_addons();

			// If error(s) occured during license key verification, display them and exit now.
			if ( empty( $temp_addons ) ) { ?>

					<h1 class="page-title"><?php esc_html_e( 'Charitable Addons', 'charitable' ); ?></h1>

					<div class="error below-h2">
						<p>
							<?php esc_html_e( 'There are some communication or license issues. Please make sure your server is able to communicate with wpcharitable.com. Contact Charitable Support for additional assistance.', 'charitable' ); ?>
						</p>
					</div>
				<?php

				if ( charitable_is_debug() ) { // @codingStandardsIgnoreLine
					error_log( 'addons_directory_content - communication issue' ); // @codingStandardsIgnoreLine
				}

				return;
			}

			$recommended_addons = array_map( array( $this, 'prepare_addon_data' ), ! empty( $temp_addons['recommended'] ) ? $temp_addons['recommended'] : array() );
			$unlicensed_addons  = array_map( array( $this, 'prepare_addon_data' ), ! empty( $temp_addons['unlicensed'] ) ? $temp_addons['unlicensed'] : array() );
			$licensed_addons    = array_map( array( $this, 'prepare_addon_data' ), ! empty( $temp_addons['licensed'] ) ? $temp_addons['licensed'] : array() );

			$installed_plugins = get_plugins();

			// Categorize addons.
			$featured_addons    = array();
			$installed_addons   = array();
			$available_addons   = array();
			$premium_addons     = array();
			$all_addons_display = array();

			// Combine all addons into a single array to avoid duplicates.
			$all_api_addons = array_merge( $recommended_addons, $licensed_addons, $unlicensed_addons );
			$all_addons     = array();
			foreach ( $all_api_addons as $addon ) {
				if ( isset( $addon['slug'] ) ) {
					$all_addons[ $addon['slug'] ] = $addon;
				}
			}

			foreach ( $all_addons as $addon ) {
				// Apply slug replacements for plugin detection (same logic as in prepare_addon_data).
				$replacements = array(
					'charitable-recurring-donations' => 'charitable-recurring',
					'charitable-anonymous-donations' => 'charitable-anonymous',
					'charitable-easy-digital-downloads-connect' => 'charitable-edd',
				);

				$slug_for_detection = $addon['slug'];
				if ( array_key_exists( $addon['slug'], $replacements ) ) {
					$slug_for_detection = $replacements[ $addon['slug'] ];
				}

				$plugin_basename     = $this->get_plugin_basename_from_slug( $slug_for_detection );
				$is_installed        = array_key_exists( $plugin_basename, $installed_plugins );
				$is_featured         = ! empty( $addon['featured'] ) && in_array( 'recommended', $addon['featured'], true );
				$is_available        = ! empty( $addon['license'] ) && in_array( $this->get_current_plan_slug(), $addon['license'], true );
				$addon['is_updated'] = $this->is_addon_updated( $addon );
				$addon['is_new']     = $this->is_addon_new( $addon );
				$addon['is_active']  = is_plugin_active( $plugin_basename );

				// Check if addon is included in pro.
				$addons_included_in_pro      = array(
					'charitable-videos',
					'charitable-pdf-receipts',
					'charitable-stripe',
					'charitable-geolocation',
					'charitable-donor-comments',
				);
				$is_included_in_pro          = in_array( $addon['slug'], $addons_included_in_pro, true );
				$addon['is_included_in_pro'] = $is_included_in_pro;

				// Prioritize installed addons over featured status.
				// Don't add addons included in pro to the installed category.
				if ( $is_installed && ! $is_included_in_pro ) {
					$installed_addons[] = $addon;
				} elseif ( $is_featured ) {
					$featured_addons[] = $addon;
				} elseif ( $is_available && ! $is_included_in_pro ) {
					$available_addons[] = $addon;
				} elseif ( ! $is_included_in_pro ) {
					$premium_addons[] = $addon;
				}
			}

			$addon_categories = array(
				'featured'  => array(
					'label'  => __( 'Featured', 'charitable' ),
					'addons' => $featured_addons,
				),
				'installed' => array(
					'label'  => __( 'Installed', 'charitable' ),
					'addons' => $installed_addons,
				),
				'available' => array(
					'label'  => __( 'Available In Your Plan', 'charitable' ),
					'addons' => $available_addons,
				),
				'premium'   => array(
					'label'  => __( 'Premium Addons', 'charitable' ),
					'addons' => $premium_addons,
				),
			);

			// If no Addon(s) were returned, our API call returned an error.
			// Show an error message with a button to reload the page, which will trigger another API call.
			if ( ! $all_addons ) {
				?>
				<form id="charitable-addons-refresh-addons-form" method="post">
					<p>
						<?php esc_html_e( 'There was an issue retrieving the addons for this site. Please click on the button below the refresh the addons data.', 'charitable' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>" class="button button-primary"><?php esc_html_e( 'Refresh Addons', 'charitable' ); ?></a> <?php // @codingStandardsIgnoreLine ?>
					</p>
				</form>
				<?php
				return;
			}

			// If here, we have Addons to display, so let's output them now.
			// Get installed plugins and upgrade URL.
			$upgrade_url   = $this->charitable_get_upgrade_link();
			$type              = ( isset( $_GET['plan'] ) ) ? esc_attr( $_GET['plan'] ) : $this->get_current_plan_slug(); // phpcs:ignore
			$preset_search     = ( isset( $_GET['search'] ) ) ? trim( esc_attr( $_GET['search'] ) ) : ''; // phpcs:ignore
			?>

			<div class="charitable-addons-header">
				<h1 class="charitable-addons-header-title">
					Charitable Addons
					<a href="<?php echo esc_url( add_query_arg( 'charitable_refresh_addons', '1' ) ); ?>" class="charitable-addons-link">
						<?php esc_html_e( 'Refresh Addons', 'charitable' ); ?>
					</a>
				</h1>
				<div class="charitable-addons-header-search">
					<input type="search" placeholder="<?php esc_html_e( 'Search Addons', 'charitable' ); ?>" id="charitable-admin-addons-search" value="<?php echo esc_html( $preset_search ); ?>" />
				</div>
			</div>
			<?php

			// phpcs:disable
			if ( defined( 'CHARITABLE_DEBUG_LICENSE' ) && CHARITABLE_DEBUG_LICENSE ) {
				echo '<p>Plan type is: ' . $type . '</p>';
				error_log( 'addons_directory_content' );
				error_log( print_r( $type, true ) );
				error_log( print_r( $installed_plugins, true ) );
				error_log( print_r( $upgrade_url, true ) );
			}
			// phpcs:enable

			/*
			* Remove addons that are not needed for pro.
			*
			* @since 1.8.5
			*/
			$addons_to_remove = array(
				'charitable-pro-plugin',
				'charitable-pro',
			);

			// Recursively remove specified addons.
			foreach ( $addon_categories as $slug => &$category ) {
				foreach ( $category['addons'] as $k => &$v ) {
					if ( is_array( $v ) && isset( $v['slug'] ) && in_array( $v['slug'], $addons_to_remove ) ) {
						unset( $category['addons'][ $k ] );
					}
				}
				// Reindex array after removal.
				$category['addons'] = array_values( $category['addons'] );
			}
			unset( $category ); // Break the reference.
			unset( $v ); // Break the reference.
			?>

			<div id="charitable-addons">
				<?php foreach ( $addon_categories as $slug => $category ) : ?>
					<?php if ( ! empty( $category['addons'] ) ) : ?>
						<div class="charitable-addons-section">
							<?php
							$is_featured_section = ( 'featured' === $slug );
							$header_classes      = array( 'charitable-addons-section-header' );
							if ( $is_featured_section ) {
								$header_classes[] = 'charitable-addons-section-header-featured';
							}
							?>
							<div class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>" data-section="<?php echo esc_attr( $slug ); ?>">
								<div class="charitable-addons-section-title">
									<?php echo esc_html( $category['label'] ); ?>
									<?php
									if ( 'available' === $slug ) {
										$plan_slug = $this->get_current_plan_slug();
										if ( ! empty( $plan_slug ) && 'lite' !== $plan_slug ) {
											echo ' <span class="charitable-badge charitable-badge-lg charitable-badge-inline charitable-badge-titanium charitable-badge-rounded">' . esc_html( $plan_slug ) . '</span>';
										}
									}
									?>
								</div>
								<?php if ( ! $is_featured_section ) : ?>
									<div class="charitable-addons-section-toggle">
										<span class="dashicons dashicons-arrow-right-alt2"></span>
									</div>
								<?php endif; ?>
							</div>
						<div id="charitable-addons-<?php echo esc_attr( $slug ); ?>" class="charitable-addons-list charitable-addons-section-content"<?php echo ( $is_featured_section ) ? '' : ' style="' . esc_attr( 'display: none;' ) . '"'; ?>>
								<?php
								foreach ( (array) $category['addons'] as $i => $addon ) {
									$this->get_addon_card( $addon, $i, true, $installed_plugins );
								}
								?>
							</div>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<?php
		}

		/**
		 * Retrieves addons from the stored transient or remote server.
		 *
		 * @since 1.7.0
		 *
		 * @return bool | array false | Array of licensed and unlicensed Addons.
		 */
		public function get_addons() {

			// Get license key and type.
			$key    = false; // charitable_get_license_key();.
			$type   = $this->get_current_plan_slug();
			$addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $addons ) {
				$addons = $this->get_addons_data_from_server( $key );
			}

			// If no Addons exist, return false.
			if ( ! $addons ) {
				return array();
			}

			// Iterate through Addons, to build two arrays:
			// - Addons the user is licensed to use,
			// - Addons the user isn't licensed to use.
			$results = array(
				'recommended' => array(),
				'licensed'    => array(),
				'unlicensed'  => array(),
			);

			foreach ( (array) $addons as $i => $addon ) {

				if ( ! isset( $addon['slug'] ) || $addon['slug'] === '' || strtolower( $addon['slug'] ) === 'auto draft' ) {
					continue;
				}

				if ( ! is_array( $addon ) ) {
					$addon = array();
				}

				$addon['path'] = ( '' === $addon['path'] ) ? sprintf( '%1$s/%1$s.php', $addon['slug'] ) : $addon['path'];

				$type = ( isset( $_GET['license'] ) ) ? $_GET['license'] : $this->get_current_plan_slug(); // phpcs:ignore

				if ( isset( $addon['featured'] ) && in_array( 'recommended', $addon['featured'] ) ) { // phpcs:ignore
					$results['recommended'][] = (array) $addon;
					continue;
				} else { // phpcs:ignore
					// Determine if the addon belongs licensed or unlicensed based on the confirmed plan.
					if ( isset( $addon['license'] ) && in_array( $type, $addon['license'] ) ) { // phpcs:ignore
						$results['licensed'][] = (array) $addon;
						continue;
					} else {
						$results['unlicensed'][] = (array) $addon;
						continue;
					}
				}
			}

			// Return Addons, split by licensed and unlicensed.
			return $results;
		}


		/**
		 * Stores an upgrade link used for the addons page when a person needs a pro license.
		 *
		 * @since 1.7.0.4
		 *
		 * @return string The url
		 */
		public function charitable_get_upgrade_link() {
			return 'https://wpcharitable.com/lite-upgrade/?discount=LITEUPGRADE&utm_source=WordPress&utm_campaign=liteplugin&utm_medium=addons&utm_content=ActiveCampaign%20Addon';
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
		 * Outputs the addon "box" on the addons page.
		 *
		 * @since   1.7.0
		 * @version 1.8.1.12 - Added EDD to replacements string check.
		 *
		 * @param object $addon Addon data from the API / transient call.
		 * @param int    $counter Index of this Addon in the collection.
		 * @param bool   $is_licensed Whether the Addon is licensed for use.
		 * @param array  $installed_plugins Installed WordPress Plugins.
		 */
		public function get_addon_card( $addon, $counter = 0, $is_licensed = false, $installed_plugins = false ) {

			if ( ! isset( $addon['slug'] ) ) {
				return;
			}

			// Setup some vars.
			$plugin_basename = $this->get_plugin_basename_from_slug( $addon['slug'] );
			$replacements    = array(
				'charitable-recurring-donations' => 'charitable-recurring',
				'charitable-anonymous-donations' => 'charitable-anonymous',
				'charitable-easy-digital-downloads-connect' => 'charitable-edd',
			);

			if ( array_key_exists( $addon['slug'], $replacements ) ) {
				$plugin_basename = $this->get_plugin_basename_from_slug( $replacements[ $addon['slug'] ] );
			}

			if ( ! $installed_plugins ) {
				$installed_plugins = get_plugins();
			}

			// If the Addon doesn't supply an upgrade_url key, it's because the user hasn't provided a license.
			// get_upgrade_link() will return the Lite or Pro link as necessary for us.
			if ( ! isset( $addon['upgrade_url'] ) ) {
				$addon['upgrade_url'] = $this->charitable_get_upgrade_link();
			}

			// Add marketing tracking.
			$addon['upgrade_url'] = add_query_arg(
				array(
					'utm_source'   => 'proplugin',
					'utm_medium'   => 'addonspage',
					'utm_campaign' => str_replace( '-', '', $addon['slug'] ) . 'addon',
				),
				$addon['upgrade_url']
			);

			switch ( $this->get_current_plan_slug() ) {
				case 'basic':
					$the_plans = array( 'basic' );
					break;
				case 'plus':
					$the_plans = array( 'basic', 'plus' );
					break;
				case 'pro':
					$the_plans = array( 'basic', 'plus', 'pro' );
					break;
				case 'agency':
					$the_plans = array( 'basic', 'plus', 'pro', 'agency', 'elite' );
					break;

				default:
					$the_plans = array();
					break;
			}

			if ( ! isset( $installed_plugins[ $plugin_basename ] ) ) {

				$addon['status'] = 'missing';
				$addon['action'] = 'license';

				foreach ( $addon['license'] as $license_to_check ) {
					if ( in_array( $license_to_check, $the_plans ) ) { // phpcs:ignore
						$addon['status'] = 'missing';
						$addon['action'] = 'install';
					}
				}
			} else { // phpcs:ignore
				// Plugin is installed.
				if ( $addon['is_active'] ) {
					$addon['status'] = 'active';
					$addon['action'] = 'deactivate';
				} else {
					$addon['status'] = 'installed';
					$addon['action'] = 'activate';
				}
			}

			// Check if addon is included in pro.
			$addons_included_in_pro = array(
				'charitable-videos',
				'charitable-pdf-receipts',
				'charitable-stripe',
				'charitable-geolocation',
				'charitable-donor-comments',
			);
			$is_included_in_pro = in_array( $addon['slug'], $addons_included_in_pro, true );

			// Check if addon is installed.
			$is_installed = array_key_exists( $plugin_basename, $installed_plugins );

			// Override status for addons included in pro.
			if ( $is_included_in_pro ) {
				$addon['status'] = 'included_in_pro';
				$addon['action'] = 'none';
			}

			// Override status for installed addons to show "Installed" text instead of button.
			if ( $is_installed && ! $is_included_in_pro ) {
				$addon['status'] = 'installed_text';
				$addon['action'] = 'none';
			}

			$button = $this->get_addon_button_html( $addon );

			$status_label = $this->get_addon_status_label( $addon['status'] );

			// get the icon/graphic.
			$icon = isset( $addon['icon'] ) ? esc_url( $addon['icon'] ) : 'placeholder';

			$icon_file_name = str_replace( 'charitable-', '', $addon['slug'] );
			$icon_file_name = str_replace( '-banner', '', $icon_file_name );

			// Locate icon, if it exists.
			if ( ! file_exists( charitable()->get_path( 'assets', true ) . 'images/addons/addon-icon-' . $icon_file_name . '.png' ) ) {
				$icon = charitable()->get_path( 'assets', false ) . 'images/addons/addon-icon-stripe.png';
			} else {
				$icon = charitable()->get_path( 'assets', false ) . 'images/addons/addon-icon-' . $icon_file_name . '.png';
			}

			// get the plugin description.
			$sections       = unserialize( $addon['sections'] ); // phpcs:ignore
			$description    = wp_strip_all_tags( ( $sections['description'] ) );
			$is_recommended = ( isset( $addon['featured'] ) && is_array( $addon['featured'] ) && in_array( 'recommended', $addon['featured'] ) ) ? true : false; // phpcs:ignore

			// css.
			$css = array();
			if ( $is_recommended ) {
				$css[] = 'has-badge';
				$css[] = 'recommended';
			}

			// Output the card.

			$addon_name       = str_replace( 'Charitable ', '', $addon['name'] );
			$landing_page_url = ! empty( $addon['homepage'] ) ? $addon['homepage'] : false;
			$landing_page_url = false === $landing_page_url ? $addon['upgrade_url'] : $landing_page_url;
			?>


			<div class="charitable-addons-list-item <?php echo esc_attr( implode( ' ', $css ) ); ?>">
				<div class="charitable-addons-list-item-header">
					<img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_html( $addon['name'] ); ?> logo">

					<div class="charitable-addons-list-item-header-meta">
						<div class="charitable-addons-list-item-header-meta-title">
							<a href="<?php echo esc_url( $landing_page_url ); ?>" title="Learn more" target="_blank" rel="noopener noreferrer" class="addon-link"><?php echo esc_html( $addon_name ); ?></a>
							<?php if ( $is_recommended ) : ?>
								<?php /* <span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i>Recommended</span> */ ?>
								<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-orange charitable-badge-rounded">Featured</span>
							<?php elseif ( $addon['is_updated'] ) : ?>
								<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-blue charitable-badge-rounded">Updated</span>
							<?php endif; ?>
						</div>

						<div class="charitable-addons-list-item-header-meta-excerpt addon-description">
						<?php echo wpautop( $description ); // phpcs:ignore ?>	</div>
					</div>
				</div>

				<div class="charitable-addons-list-item-footer charitable-addons-list-item-footer-installed" data-plugin="<?php echo esc_attr( $addon['path'] ); ?>" data-type="addon">
					<div>
						<?php if ( ! empty( $addon['license'] ) ) : ?>
						<span class="charitable-badge charitable-badge-lg charitable-badge-inline charitable-badge-titanium charitable-badge-rounded"><?php echo esc_html( end( $addon['license'] ) ); ?></span>
						<?php endif; ?>
					</div>

					<div class="charitable-addons-list-item-footer-actions">
						<?php echo wp_kses_post( $button ); ?>
					</div>
				</div>
			</div>

			<?php
		}

		/**
		 * Get addon button HTML.
		 *
		 * @since 1.6.7
		 *
		 * @param array $addon Prepared addon data.
		 *
		 * @return string
		 */
		private function get_addon_button_html( $addon ) {

			// Check if addon is included in pro and return appropriate text.
			if ( ! empty( $addon['is_included_in_pro'] ) ) {
				return '<span class="charitable-addon-included-in-pro">' . esc_html__( 'Included In Pro', 'charitable' ) . '</span>';
			}

			// Check if addon is installed and return appropriate text.
			if ( 'installed_text' === $addon['status'] ) {
				return '<span class="charitable-addon-installed">' . esc_html__( 'Installed', 'charitable' ) . '</span>';
			}

			if ( $addon['action'] === 'upgrade' || $addon['action'] === 'license' || ! $addon['plugin_allow'] ) {

				if ( charitable_is_pro() ) {

					// go ahead and send those who have a license key added to the checkout page, but down the road we will send them to the pricing page and then customize the prices on the pricing page to reflect the upgrade amounts per plan.

					$license_data_to_send = $this->get_license_key_for_upgrade();

					if ( false !== $license_data_to_send && isset( $license_data_to_send['license'] ) ) {

						$upid = 0; // the upgrade plan id.

						if ( in_array( 'agency', $addon['license'] ) ) { // phpcs:ignore
							// if the desired addon is in the agency category license, we need to pass along the upgrade plan id of 4.
							$upid = 4;
						}

						if ( in_array( 'pro', $addon['license'] ) ) { // phpcs:ignore
							// if the desired addon is in the pro category license, we need to pass along the upgrade plan id of 3.
							$upid = 3;
						}

						if ( in_array( 'plus', $addon['license'] ) ) { // phpcs:ignore
							// if the desired addon is in the plus category license, we need to pass along the upgrade plan id of 2.
							$upid = 2;
						}

						// we have a valid license that we could get an upgrade path, etc. from.
						$upgrade_url = charitable_ga_url(
							// pid = plan_id.
							self::UPDATE_URL . '/?license_upgrade=true&license_key=' . $license_data_to_send['license'] . '&upid=' . $upid . '&pid=' . $license_data_to_send['plan_id'] . '&addon=' . esc_html( $addon['name'] ),
							urlencode( 'Plugin Addon Page' ), // phpcs:ignore
							urlencode( $addon['name'] . ' Upgrade' ) // phpcs:ignore
						);

					} else {

						// they may be on 'pro' mode but they don't have a license we can send, so fall back to sending them to the pricing page.
						$upgrade_url = charitable_ga_url(
							'https://www.wpcharitable.com/pricing/',
							urlencode( 'Plugin Addon Page' ), // phpcs:ignore
							urlencode( $addon['name'] . ' Upgrade' ) // phpcs:ignore
						);

					}
				} else {
					// they are on lite, with no license so send them to the pricing page.
					$upgrade_url = charitable_ga_url(
						'https://www.wpcharitable.com/pricing/',
						urlencode( 'Plugin Addon Page' ), // phpcs:ignore
						urlencode( $addon['name'] . ' Upgrade' ) // phpcs:ignore
					);
				}

				return sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer" class="button button-secondary">%2$s</a>',
					$upgrade_url,
					esc_html__( 'Upgrade Now', 'charitable' )
				);
			}

			$html = '';

			// Override to account for odd setup prior to new ownership.
			$addon['path'] = str_replace( 'charitable-recurring-donations', 'charitable-recurring', $addon['path'] );

			// Apply filter, mostly for testing and troubleshooting.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook name maintained for backward compatibility with existing filters.
			$addon = apply_filters( 'get_addon_button_html_addon', $addon );

			switch ( $addon['status'] ) {
				case 'active':
					$html  = '<button class="button button-secondary status-' . esc_attr( $addon['status'] ) . '" data-plugin="' . esc_attr( $addon['path'] ) . '" data-type="addon">';
					$html .= esc_html__( 'Deactivate', 'charitable' );
					$html .= '</button>';
					break;

				case 'installed':
					$html  = '<button class="button button-secondary status-' . esc_attr( $addon['status'] ) . '" data-plugin="' . esc_attr( $addon['path'] ) . '" data-type="addon">';
					$html .= esc_html__( 'Activate', 'charitable' );
					$html .= '</button>';
					break;

				case 'missing':
					if ( ! $this->can_install ) {
						break;
					}

					// Testing backup links for download.
					$download_url = ! empty( $addon['download_link'] ) ? esc_url( $addon['download_link'] ) : false;
					$download_url = ( false === $download_url ) && ! empty( $addon['install'] ) ? esc_url( $addon['install'] ) : $download_url;

					// Validate the download URL - if it's invalid, disable the button
					$is_valid_url = $download_url && filter_var( $download_url, FILTER_VALIDATE_URL ) && ! strpos( $download_url, 'invalid_price_id_plan__' );

					if ( $is_valid_url ) {
						$html  = '<button class="button button-secondary status-' . esc_attr( $addon['status'] ) . '" data-plugin="' . esc_url( $download_url ) . '" data-type="addon">';
						$html .= esc_html__( 'Install Addon', 'charitable' );
						$html .= '</button>';
					} else {
						// Invalid URL - disable button and add warning class
						$html  = '<button class="button button-secondary status-' . esc_attr( $addon['status'] ) . ' disabled" data-plugin="invalid" data-type="addon" disabled>';
						$html .= esc_html__( 'Install Addon', 'charitable' );
						$html .= '</button>';
					}
					break;
			}

			return $html;
		}


		/**
		 * Check if any addons have invalid download URLs and display admin notice.
		 *
		 * @since 1.8.8
		 * @version 1.8.9.1
		 */
		public function check_for_invalid_urls() {
			$addons           = $this->get_addons();
			$has_invalid_urls = false;

			foreach ( $addons as $addon ) {
				if ( 'missing' === $addon['status'] ) {
					$download_url = ! empty( $addon['download_link'] ) ? $addon['download_link'] : false;
					$download_url = ( false === $download_url ) && ! empty( $addon['install'] ) ? $addon['install'] : $download_url;

					if ( ! $download_url || ! filter_var( $download_url, FILTER_VALIDATE_URL ) || strpos( $download_url, 'invalid_price_id_plan__' ) !== false ) {
						$has_invalid_urls = true;
						break;
					}
				}
			}

			if ( $has_invalid_urls ) {
				$settings_url = admin_url( 'admin.php?page=charitable-settings' );
				$notice       = sprintf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
					esc_html__( 'License Data Warning:', 'charitable' ),
					esc_html__( 'Some addon download URLs appear to be invalid. This may indicate that your license data is not fully synchronized with the license server. You may need to reconnect your license by removing it and adding it back in the', 'charitable' ),
					esc_url( $settings_url ),
					esc_html__( 'Charitable Settings page', 'charitable' )
				);

				echo wp_kses_post( $notice );
			}
		}

		/**
		 * Get addon status label.
		 *
		 * @since 1.6.7
		 *
		 * @param string $status Addon status.
		 *
		 * @return string
		 */
		private function get_addon_status_label( $status ) {

			$label = [
				'active'    => esc_html__( 'Active', 'charitable' ),
				'installed' => esc_html__( 'Inactive', 'charitable' ),
				'missing'   => esc_html__( 'Not Installed', 'charitable' ),
			];

			return isset( $label[ $status ] ) ? $label[ $status ] : '';
		}

		/**
		 * Check if addon is new.
		 * This examines 'last_updated' from the addon data and compares it to the current date.
		 *
		 * @since 1.8.8
		 *
		 * @param array $addon Addon data.
		 *
		 * @return bool
		 */
		public function is_addon_updated( $addon ) {

			if ( empty( $addon['last_updated'] ) ) {
				return false;
			}

			$addon_date   = strtotime( $addon['last_updated'] );
			$current_date = strtotime( date( 'Y-m-d' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

			// If the last_updated is within 90 days, return true.
			return $addon_date > $current_date - 90 * DAY_IN_SECONDS;
		}

		/**
		 * Check if addon is new.
		 *
		 * @since 1.8.8
		 *
		 * @param array $addon Addon data.
		 *
		 * @return bool
		 */
		public function is_addon_new( $addon ) {

			return false;
		}

		/**
		 * Prepare addon data.
		 *
		 * @since 1.6.6
		 *
		 * @param array $addon Addon data.
		 *
		 * @return array Extended addon data.
		 */
		protected function prepare_addon_data( $addon ) {

			if ( empty( $addon ) ) {
				return [];
			}

			$addon['title'] = ! empty( $addon['title'] ) ? $addon['title'] : '';
			$addon['slug']  = ! empty( $addon['slug'] ) ? $addon['slug'] : '';

			/* translators: %s - addon name. */
			$addon['modal_name']    = sprintf( esc_html__( '%s addon', 'charitable' ), $addon['name'] );
			$addon['clear_slug']    = str_replace( 'charitable-', '', $addon['slug'] );
			$addon['utm_content']   = ucwords( str_replace( '-', ' ', $addon['clear_slug'] ) );
			$addon['license']       = empty( $addon['license'] ) ? [] : (array) $addon['license'];
			$addon['license_level'] = $this->get_license_level( $addon );
			$addon['icon']          = ! empty( $addon['icon'] ) ? $addon['icon'] : '';
			$addon['path']          = sprintf( '%1$s/%1$s.php', $addon['slug'] );
			$addon['video']         = ! empty( $addon['video'] ) ? $addon['video'] : '';
			$addon['plugin_allow']  = $this->has_access( $addon );
			$addon['status']        = 'missing';
			$addon['action']        = 'upgrade';
			$addon['page_url']      = empty( $addon['url'] ) ? '' : $addon['url'];
			$addon['doc_url']       = empty( $addon['doc'] ) ? '' : $addon['doc'];
			$addon['url']           = '';

			static $nonce   = '';
			$nonce          = empty( $nonce ) ? wp_create_nonce( 'charitable-admin' ) : $nonce;
			$addon['nonce'] = $nonce;

			return $addon;
		}


		/**
		 * Get available addon data by slug.
		 *
		 * @since 1.6.6
		 *
		 * @param string $slug Addon slug, can be both "charitable-drip" and "drip".
		 *
		 * @return array Single addon data. Empty array if addon is not found.
		 */
		public function get_addon( $slug ) {

			$slug = 'charitable-' . str_replace( 'charitable-', '', sanitize_key( $slug ) );

			$addon = ! empty( $this->available_addons[ $slug ] ) ? $this->available_addons[ $slug ] : [];

			// In case if addon is "not available" let's try to get and prepare addon data from all addons.
			if ( empty( $addon ) ) {
				$addon = ! empty( $this->addons[ $slug ] ) ? $this->prepare_addon_data( $this->addons[ $slug ] ) : [];
			}

			return $addon;
		}

		/**
		 * Get license level of the addon.
		 *
		 * @since 1.6.6
		 *
		 * @param array|string $addon Addon data array OR addon slug.
		 *
		 * @return string License level: pro | elite.
		 */
		private function get_license_level( $addon ) {

			if ( empty( $addon ) ) {
				return '';
			}

			$addon            = is_string( $addon ) ? $this->get_addon( $addon ) : $addon;
			$addon['license'] = empty( $addon['license'] ) ? [] : (array) $addon['license'];

			// TODO: convert to a class constant when we will drop PHP 5.5.
			$levels  = [ 'basic', 'plus', 'pro', 'elite', 'agency', 'ultimate' ];
			$license = '';

			foreach ( $levels as $level ) {
				if ( in_array( $level, $addon['license'], true ) ) {
					$license = $level;

					break;
				}
			}

			if ( empty( $license ) ) {
				return '';
			}

			return in_array( $license, [ 'basic', 'plus', 'pro' ], true ) ? 'pro' : 'elite';
		}

		/**
		 * This attempts to send the BEST license key to Charitable whenever a suggested upgrade is possible.
		 * Note: We look for "v2" licenses in settings first, then attempt to locate a charitable basic/plus/pro/agency (new or oldschool) license.
		 * We don't return individual addons in this function because there's no good way to suggest an upgrade EDD license path.
		 *
		 * @since 1.7.0.4
		 *
		 * @return array
		 */
		private function get_license_key_for_upgrade() {

			$settings = get_option( 'charitable_settings' );
			if ( isset( $settings['licenses']['charitable-v2']['license'] ) && ! empty( $settings['licenses']['charitable-v2']['license'] ) ) {

				// attempt to grab the "v2" license.
				$license = esc_html( $settings['licenses']['charitable-v2']['license'] );
				$plan_id = isset( $settings['licenses']['charitable-v2']['plan_id'] ) && ! empty( $settings['licenses']['charitable-v2']['plan_id'] ) ? intval( $settings['licenses']['charitable-v2']['plan_id'] ) : false;
				return array(
					'license' => $license,
					'plan_id' => $plan_id,
				);

			} elseif ( isset( $settings['licenses']['charitable'] ) && ! empty( $settings['licenses']['charitable'] ) ) {

				$license = esc_html( $settings['licenses']['charitable'] );
				return array(
					'license' => $license,
					'plan_id' => false,
				); // no plan id when this was added into settings.

			} elseif ( isset( $settings['licenses']['charitable_pro'] ) && ! empty( $settings['licenses']['charitable_pro'] ) ) {

				// unlikely but technically possible.
				$license = esc_html( $settings['licenses']['charitable_pro'] );
				return array(
					'license' => $license,
					'plan_id' => false,
				); // no plan id when this was added into settings.

			}

			return false;
		}

		/**
		 * Determine if user's license level has access.
		 *
		 * @since 1.6.6
		 *
		 * @param array|string $addon Addon data array OR addon slug.
		 *
		 * @return bool
		 */
		protected function has_access( $addon ) { // phpcs:ignore

			return true;
		}


		/**
		 * Return the latest versions of Charitable plugins.
		 *
		 * @since  1.7.0.4
		 *
		 * @param array $licenses Array of license keys.
		 *
		 * @return array
		 */
		public function get_addons_data_from_server( $licenses = false ) {

			$versions = get_transient( '_charitable_plugin_versions' );

			if ( false === $versions ) {

				if ( false === $licenses ) {

					$licenses = array();

					foreach ( $this->get_licenses() as $license ) {
						if ( isset( $license['license'] ) ) {
							$licenses[] = $license['license'];
						}
					}
				}

				$response = wp_remote_post(
					self::UPDATE_URL . '/edd-api/versions-v3/',
					array(
						'sslverify' => false,
						'timeout'   => 15,
						'body'      => array(
							'licenses' => $licenses,
							'url'      => home_url(),
						),
					)
				);

				$response_body = wp_remote_retrieve_body( $response );
				$response_code = wp_remote_retrieve_response_code( $response );

				// Bail out early if there are any errors.
				if ( (int) $response_code !== 200 || is_wp_error( $response_body ) ) {
					return false;
				}

				$versions = json_decode( $response_body, true );

				set_transient( '_charitable_plugin_versions', $versions, DAY_IN_SECONDS );

			} // end if

			return $versions;
		}

		/**
		 * Return the latest versions of Charitable plugins.
		 *
		 * @since 1.4.0
		 * @since 1.8.1.1 - Removed cache_get method and site a site option instead.
		 * @since 1.8.1.3 - Added request_recently_failed check and log_failed_request call.
		 *
		 * @return array
		 */
		protected function get_versions() {

			$versions = get_site_option( 'wpc_plugin_versions' );

			if ( false === $versions || ! isset( $versions['expires'] ) || time() > $versions['expires'] || ! isset( $versions['data'] ) ) {
				/*
				* Check and see if this request recently failed.
				* Most likely because the site is down or didn't return a 200 response.
				* Regardless this means we're allowed to try again for a while.
				*/
				if ( $this->request_recently_failed() ) {
					if ( charitable_is_debug() ) {
						error_log( 'WPCharitable Debug Error: get_versions (licenses) API call aborted because it recently failed' ); // @codingStandardsIgnoreLine
					}
					return false;
				}

				$licenses = array();

				foreach ( $this->get_licenses() as $license ) {
					if ( isset( $license['license'] ) ) {
						$licenses[] = $license['license'];
					}
				}

				$response = wp_remote_post(
					self::UPDATE_URL . '/edd-api/versions-v2/',
					array(
						'sslverify' => false,
						'timeout'   => 15,
						'body'      => array(
							'licenses' => $licenses,
							'url'      => home_url(),
						),
					)
				);

				// check the response code... if it's bad, then return what we have if anything.
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					// Log this failure so we don't keep trying.
					$this->log_failed_request();

					if ( charitable_is_debug() ) {
						error_log( 'WPCharitable Debug Error: get_versions (licenses) API call failed' ); // @codingStandardsIgnoreLine
						error_log( print_r( $response, true ) ); // @codingStandardsIgnoreLine
					}

					return ( false !== $versions && isset( $versions['data'] ) ) ? $versions['data'] : array();
				}

				$versions = wp_remote_retrieve_body( $response );

				$versions = json_decode( $versions, true );

				update_site_option(
					'wpc_plugin_versions',
					array(
						'expires' => strtotime( '+1 hour', time() ),
						'data'    => $versions,
					)
				);

				if ( charitable_is_debug() ) {
					error_log( 'WPCharitable Debug Notice: get_versions (licenses) API call in addons directory successful and added to site option.' ); // @codingStandardsIgnoreLine
					error_log( // @codingStandardsIgnoreLine
						print_r( // @codingStandardsIgnoreLine
							array(
								'expires' => strtotime( '+1 hour', time() ),
								'data'    => $versions,
							),
							true
						)
					);
				}
			} else {
				$versions = $versions['data'];
			}

			return $versions;
		}


		/**
		 * Logs a failed HTTP request for this API URL.
		 *
		 * We set a timestamp for 1 hour from now. This prevents future API requests from being
		 * made to this domain for 1 hour. Once the timestamp is in the past, API requests
		 * will be allowed again. This way if the site is down for some reason we don't bombard
		 * it with failed API requests.
		 *
		 * @see EDD_SL_Plugin_Updater::request_recently_failed
		 *
		 * @since 1.8.1.3
		 */
		private function log_failed_request() {
			update_option( $this->failed_request_cache_key, strtotime( '+1 hour' ) );
		}

		/**
		 * Determines if a request has recently failed.
		 *
		 * @since 1.8.1.3
		 *
		 * @return bool
		 */
		private function request_recently_failed() {
			$failed_request_details = get_option( $this->failed_request_cache_key );

			// Request has never failed.
			if ( empty( $failed_request_details ) || ! is_numeric( $failed_request_details ) ) {
				return false;
			}

			/*
			 * Request previously failed, but the timeout has expired.
			 * This means we're allowed to try again.
			 */
			if ( time() > $failed_request_details ) {
				delete_option( $this->failed_request_cache_key );

				return false;
			}

			return true;
		}

		/**
		 * Return the list of licenses.
		 *
		 * Note: The licenses are not necessarily valid. If a user enters an invalid
		 * license, the license will be stored but it will be flagged as invalid.
		 *
		 * @since  1.0.0
		 *
		 * @return array[]
		 */
		public function get_licenses() {
			if ( ! isset( $this->licenses ) ) {
				$this->licenses = charitable_get_option( 'licenses', array() );
			}

			return $this->licenses;
		}
	}

endif;
