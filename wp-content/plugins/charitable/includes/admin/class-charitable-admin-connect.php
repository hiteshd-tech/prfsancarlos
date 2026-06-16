<?php
/**
 * Charitable Admin Connect.
 *
 * @package Charitable/Classes/Charitable_Admin_Connect
 * @since 1.8.5
 * @version 1.8.9.6
 * @category Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charitable_Admin_Getting_Started page class.
 *
 * This page is shown when the plugin is first activated.
 *
 * @since 1.8.5
 * @version 1.8.9.6
 */
class Charitable_Admin_Connect {

	/**
	 * Charitable Pro plugin basename.
	 *
	 * @since 1.8.5
	 *
	 * @var string
	 */
	const PRO_PLUGIN = 'charitable-pro/charitable.php';

	/**
	 * The single instance of this class.
	 *
	 * @var Charitable_Admin_Connect|null
	 */
	private static $instance = null;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.5
	 */
	public function __construct() {
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.5
	 */
	public function hooks() {
	}

	/**
	 * Settings page enqueues.
	 *
	 * @since 1.8.5
	 * @version 1.8.9.6
	 *
	 * @param string $min Minified suffix.
	 * @param string $version Charitable version.
	 * @param string $assets_dir Assets directory.
	 */
	public function settings_enqueues( $min, $version, $assets_dir ) { // phpcs:ignore

		// Ensure jquery-confirm is available so $.alert() modals (Pro install, activate, etc.) can show.
		if ( ! wp_script_is( 'jquery-confirm', 'registered' ) ) {
			wp_register_script(
				'jquery-confirm',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
				array( 'jquery' ),
				'3.3.4',
				false
			);
			wp_register_style(
				'jquery-confirm',
				charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
				null,
				'3.3.4'
			);
		}
		wp_enqueue_script( 'jquery-confirm' );
		wp_enqueue_style( 'jquery-confirm' );

		wp_enqueue_script(
			'charitable-connect',
			charitable()->get_path( 'assets', false ) . "js/admin/charitable-admin-connect{$min}.js",
			array( 'jquery', 'jquery-confirm' ),
			$version,
			true
		);
	}

	/**
	 * Generate and return Charitable Connect URL.
	 *
	 * @since 1.8.5
	 * @version 1.8.9.6
	 */
	public function generate_url() {

		// Run a security check.
		check_ajax_referer( 'charitable-admin', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to install plugins.', 'charitable' ) ) );
		}

		$current_plugin = plugin_basename( charitable()->get_path() . 'charitable.php' );
		$is_pro         = charitable_is_pro();

		$key = ! empty( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		// Empty license key.
		if ( empty( $key ) ) {
			wp_send_json_error(
				array(
					'show_manual_upgrade' => true,
					'url'                 => 'https://www.wpcharitable.com/documentation/installing-extensions/',
					'message'             => esc_html__( 'Please enter your license key to connect.', 'charitable' ),
				)
			);
		}

		// Whether it is the pro version.
		if ( $is_pro ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Only the Lite version of Charitable can be upgraded.', 'charitable' ) ) );
		}

		// STEP 1: VALIDATE LICENSE FIRST (regardless of Pro plugin existence)
		$license_data = charitable_get_helper( 'licenses' )->verify_license( 'charitable', $key, true, false );

		if ( charitable_is_debug() ) {
			error_log( 'CHARITABLE DEBUG: verify_license returned: ' . print_r( $license_data, true ) ); // phpcs:ignore
		}

		// API returns: $license_data['license'] = the license key string
		//              $license_data['valid']   = 1 for valid, 0 for invalid
		if ( empty( $license_data ) || ! $license_data['valid'] ) {
			if ( charitable_is_debug() ) {
				error_log( 'CHARITABLE DEBUG: License validation FAILED' ); // phpcs:ignore
			}
			wp_send_json_error( array(
				'message' => esc_html__( 'Invalid license key. Please check your license key and try again.', 'charitable' )
			) );
		}

		if ( charitable_is_debug() ) {
			error_log( 'CHARITABLE DEBUG: License validation PASSED' ); // phpcs:ignore
		}

		// STEP 2: STORE VALID LICENSE IMMEDIATELY
		$settings = get_option( 'charitable_settings', array() );
		if ( ! isset( $settings['licenses'] ) ) {
			$settings['licenses'] = array();
		}

		// Use keys returned by verify_license(): expiration_date, plan_id (not expires/price_id).
		$settings['licenses']['charitable-v2'] = array(
			'license'         => $key,
			'expiration_date' => isset( $license_data['expiration_date'] ) ? $license_data['expiration_date'] : false,
			'plan_id'         => isset( $license_data['plan_id'] ) ? $license_data['plan_id'] : false,
			'valid'           => true,
		);

		update_option( 'charitable_settings', $settings );

		// STEP 2.5: CHECK LOCALHOST AFTER LICENSE VALIDATION
		// Now that we have a valid license, handle localhost differently (unless testing: ?charitable_connect_test=1).
		$connect_test = ! empty( $_POST['charitable_connect_test'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( charitable_is_localhost() && ! $connect_test ) {
			wp_send_json_success(
				array(
					'show_manual_upgrade' => true,
					'url'                 => 'https://www.wpcharitable.com/documentation/installing-extensions/',
					'message'             => esc_html__( 'License validated successfully! Since you\'re on localhost, please install Charitable Pro manually using the link below.', 'charitable' ),
					'license_validated'   => true,
				)
			);
		}

		// STEP 2.6: PRO INSTALLED BUT INACTIVE — show popup to activate; do not go to upgrade.wpcharitable.com.
		$pro_plugin_path = WP_PLUGIN_DIR . '/' . self::PRO_PLUGIN;
		$pro_is_active   = file_exists( $pro_plugin_path ) && in_array( self::PRO_PLUGIN, (array) get_option( 'active_plugins', array() ), true );
		if ( file_exists( $pro_plugin_path ) && ! $pro_is_active ) {
			$redirect_url = admin_url( 'admin.php?page=charitable-settings&tab=general' );
			wp_send_json_success(
				array(
					'show_activate_pro_popup' => true,
					'message'                 => esc_html__( 'Your license is valid. Pro is installed but inactive. Activate now?', 'charitable' ),
					'redirect_url'            => $redirect_url,
					'plugin_basename'         => self::PRO_PLUGIN,
				)
			);
		}

		// STEP 3: REDIRECT TO UPGRADE.WPCHARITABLE.COM (Pro not installed — need to download)
		// Build signed URL; upgrade site will validate and call back to process() with the Pro download URL.
		$oth_raw    = wp_generate_password( 64, true, true );
		$oth_signed = hash_hmac( 'sha512', $oth_raw, wp_salt() );
		update_option( 'charitable_connect_token', $oth_raw );
		update_option( 'charitable_connect', $key );

		$redirect_back = admin_url( 'admin.php?page=charitable-settings&tab=general' );
		$endpoint     = $this->get_connect_callback_endpoint();

		// Upgrade site expects redirect to be base64-encoded (see upgrade.wpcharitable.com index.php).
		$upgrade_url = add_query_arg(
			array(
				'key'      => $key,
				'oth'      => $oth_signed,
				'endpoint' => $endpoint,
				'version'  => charitable()->get_version(),
				'siteurl'  => site_url(),
				'homeurl'  => home_url(),
				'redirect' => base64_encode( $redirect_back ),
			),
			'https://upgrade.wpcharitable.com/'
		);

		wp_send_json_success( array( 'upgrade_url' => $upgrade_url ) );
	}

	/**
	 * Process Charitable Connect.
	 *
	 * @since   1.8.5
	 * @version 1.8.9.6
	 */
	public function process() {

		$error = esc_html__( 'There was an error while installing an upgrade. Please download the plugin from charitable.com and install it manually.', 'charitable' );

		if ( charitable_is_debug() ) {
			error_log( 'Charitable Admin Connect process' ); // phpcs:ignore
			error_log( print_r( $_REQUEST, true ) ); // phpcs:ignore
		}

		// Verify params present (oth & download link).
		$post_oth     = ! empty( $_REQUEST['oth'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['oth'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_url     = ! empty( $_REQUEST['file'] ) ? esc_url_raw( wp_unslash( $_REQUEST['file'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_key     = ! empty( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_item_id = ! empty( $_REQUEST['item_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['item_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $post_oth ) || empty( $post_url ) ) {
			if ( charitable_is_debug() ) {
				error_log( 'Charitable Connect process: FAIL Missing oth or file (callback must be called by upgrade server with POST oth + file).' ); // phpcs:ignore
			}
			wp_send_json_error( esc_html__( 'Missing verification token or download URL. This endpoint must be called by the upgrade server.', 'charitable' ) );
		}

		// Verify oth.
		$oth = get_option( 'charitable_connect_token' );

		if ( empty( $oth ) ) {
			if ( charitable_is_debug() ) {
				error_log( 'Charitable Connect process: FAIL No oth found (charitable_connect_token empty in DB).' ); // phpcs:ignore
			}
			wp_send_json_error( esc_html__( 'No oth found.', 'charitable' ) );
		}

		if ( hash_hmac( 'sha512', $oth, wp_salt() ) !== $post_oth ) {
			if ( charitable_is_debug() ) {
				error_log( 'Charitable Connect process: FAIL Invalid oth (HMAC mismatch).' ); // phpcs:ignore
			}
			wp_send_json_error( esc_html__( 'Invalid oth.', 'charitable' ) );
		}

		// Delete so cannot replay.
		delete_option( 'charitable_connect_token' );

		// Set the current screen to avoid undefined notices (only when admin is loaded, e.g. admin-ajax.php; not in connect-callback.php).
		if ( function_exists( 'set_current_screen' ) ) {
			set_current_screen( 'charitable_page_charitable-settings' );
		}

		// Prepare variables.
		$url = esc_url_raw(
			add_query_arg(
				array( 'page' => 'charitable-settings' ),
				admin_url( 'admin.php' )
			)
		);

		// Verify pro not already active. Use plugin file + active_plugins, not charitable_is_pro(),
		// because charitable_is_pro() is true when a valid Pro license is saved (e.g. after Verify),
		// which would skip install even when the Pro plugin is not installed yet.
		$pro_plugin_path = WP_PLUGIN_DIR . '/' . self::PRO_PLUGIN;
		$pro_is_active   = file_exists( $pro_plugin_path ) && in_array( self::PRO_PLUGIN, (array) get_option( 'active_plugins', array() ), true );
		if ( $pro_is_active ) {
			if ( charitable_is_debug() ) {
				error_log( 'Charitable Connect process: SUCCESS path=pro_already_active' ); // phpcs:ignore
			}
			wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'charitable' ) );
		}

		// Pro plugin file exists but not active: try to activate it, then deactivate Lite.
		if ( file_exists( $pro_plugin_path ) ) {
			$active = activate_plugin( self::PRO_PLUGIN, $url, false, true );
			if ( ! is_wp_error( $active ) ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable Connect process: SUCCESS path=pro_already_installed_activated' ); // phpcs:ignore
				}
				$plugin = plugin_basename( charitable()->get_path() . 'charitable.php' );
				deactivate_plugins( $plugin );
				do_action( 'charitable_plugin_deactivated', $plugin );
				wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'charitable' ) );
			}
		}

		// Pro not installed or activation failed: download and install from upgrade server URL.
		$creds = request_filesystem_credentials( $url, '', false, false );

		// Check for file system permissions.
		if ( $creds === false || ! WP_Filesystem( $creds ) ) {
			wp_send_json_error(
				esc_html__( 'There was an error while installing an upgrade. Please check file system permissions and try again. Also, you can download the plugin from charitable.com and install it manually.', 'charitable' )
			);
		}

		/*
		 * We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		 */

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once plugin_dir_path( CHARITABLE_DIRECTORY_PATH ) . 'charitable/includes/utilities/Skin.php';

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( new Charitable_Skin() );

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			wp_send_json_error( esc_html__( 'No install method found.', 'charitable' ) );
		}

		// Check license key.
		$key = get_option( 'charitable_connect', false );

		if ( empty( $key ) ) {
			wp_send_json_error(
				new WP_Error(
					'403',
					esc_html__( 'No key provided.', 'charitable' )
				)
			);
		}

		$installer->install( $post_url ); // phpcs:ignore

		if ( charitable_is_debug() ) {
			error_log( 'Charitable Admin Connect process install' ); // phpcs:ignore
			error_log( $post_url ); // phpcs:ignore
			error_log( print_r( $installer->plugin_info(), true ) ); // phpcs:ignore
		}

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		$plugin_basename = $installer->plugin_info();

		if ( $plugin_basename ) {

			// CRITICAL SAFETY CHECK: Verify the newly installed plugin actually exists before deactivating Lite
			$new_plugin_path = WP_PLUGIN_DIR . '/' . $plugin_basename;
			if ( ! file_exists( $new_plugin_path ) ) {
				wp_send_json_error( esc_html__( 'Newly installed plugin not found. Cannot safely deactivate Lite version.', 'charitable' ) );
			}

			// Deactivate the lite version first.
			$plugin = plugin_basename( charitable()->get_path( 'plugin-directory' ) . '/charitable/charitable.php' );

			deactivate_plugins( $plugin );

			// phpcs:ignore Charitable.Comments.PHPDocHooks.RequiredHookDocumentation, Charitable.PHP.ValidateHooks.InvalidHookName
			do_action( 'charitable_plugin_deactivated', $plugin );

			// Activate the plugin silently.
			$activated = activate_plugin( $plugin_basename, '', false, true );

			if ( ! is_wp_error( $activated ) ) {

				// Add the pro_connect activation date to the activated array.
				$activated = (array) get_option( 'charitable_activated', array() );

				if ( empty( $activated['pro_connect'] ) ) {
					$activated['pro_connect'] = time();
					update_option( 'charitable_activated', $activated );
				}

				if ( charitable_is_debug() ) {
					error_log( 'Charitable Admin Connect process activate' ); // phpcs:ignore
					error_log( 'plugin_basename' ); // phpcs:ignore
					error_log( $plugin_basename ); // phpcs:ignore
					error_log( 'activated' ); // phpcs:ignore
					error_log( print_r( $activated, true ) ); // phpcs:ignore
				}

				if ( ! empty( $post_key ) && ! empty( $post_item_id ) ) {

					$data = array(
						'edd_action' => 'activate_license',
						'license'    => $key,
						'legacy'     => false,
						'item_id'    => $post_item_id,
						'url'        => site_url(),
					);

					if ( charitable_is_debug() ) {
						error_log( 'Charitable Admin Connect process activate license' ); // phpcs:ignore
						error_log( print_r( $data, true ) ); // phpcs:ignore
					}

					$response = wp_remote_post( 'https://wpcharitable.com/edd-api/versions-v2/', array( 'body' => $data ) );

					// Get the body of the response.
					$body = wp_remote_retrieve_body( $response );

					if ( charitable_is_debug() ) {
						error_log( 'Charitable Admin Connect process activate license body' ); // phpcs:ignore
						error_log( $body ); // phpcs:ignore
					}

					$license_data = json_decode( $body );

					if ( charitable_is_debug() ) {
						error_log( 'Charitable Admin Connect process activate license data' ); // phpcs:ignore
						error_log( print_r( $license_data, true ) ); // phpcs:ignore
					}

					// if $license_day is an object, convert it to an array.
					if ( is_object( $license_data ) ) {
						$license_data = (array) $license_data;
					}

					if ( empty( $license_data ) || is_wp_error( $response ) ) {
						if ( charitable_is_debug() ) {
							error_log( 'Charitable Admin Connect process activate license response iniitial failure' ); // phpcs:ignore
							error_log( print_r( $response, true ) ); // phpcs:ignore
							error_log( 'Charitable Admin Connect process activate license response iniitial failure license data' ); // phpcs:ignore
							error_log( print_r( $license_data, true ) ); // phpcs:ignore
						}
					} elseif ( ! empty( $license_data['success'] ) && 1 === intval( $license_data['success'] ) && ! empty( $license_data['license'] ) && 'valid' === $license_data['license'] && ! empty( $license_data['expires'] ) ) {

							// Delete transients (related to plugin versions).
							// delete_transient( '_charitable_plugin_versions' );

							$settings = get_option( 'charitable_settings' );

							$settings['licenses']['charitable-v2'] = array(
								'license'         => $key,
								'expiration_date' => isset( $license_data['expires'] ) ? $license_data['expires'] : false,
								'plan_id'         => isset( $license_data['price_id'] ) ? $license_data['price_id'] : false,
								'valid'           => true,
							);

							update_option( 'charitable_settings', $settings );

							if ( charitable_is_debug() ) {
								error_log( 'Charitable Admin Connect process activate license response good' ); // phpcs:ignore
								error_log( print_r( $response, true ) ); // phpcs:ignore
								error_log( 'Charitable Admin Connect process activate license response good license data' ); // phpcs:ignore
								error_log( print_r( $license_data, true ) ); // phpcs:ignore
							}

							// Create an empty update transient object instead of null.
							$empty_transient = new \stdClass();
							set_site_transient( 'update_plugins', $empty_transient );
							delete_site_option( 'wpc_plugin_versions' );
							update_option( 'charitable_connect_complete', true );
							update_option( 'charitable_connect_completed', true );

					} elseif ( charitable_is_debug() ) {
							error_log( 'Charitable Admin Connect process activate license response failed' ); // phpcs:ignore
							error_log( print_r( $response, true ) ); // phpcs:ignore
							error_log( 'Charitable Admin Connect process activate license response failed license data' ); // phpcs:ignore
							error_log( print_r( $license_data, true ) ); // phpcs:ignore

					}
				}

				if ( charitable_is_debug() ) {
					error_log( 'Charitable Connect process: SUCCESS path=downloaded_and_activated plugin_basename=' . $plugin_basename ); // phpcs:ignore
				}
				wp_send_json_success( esc_html__( 'Plugin installed & activated.', 'charitable' ) );
			} else {
				// Reactivate the lite plugin if pro activation failed.
				activate_plugin( plugin_basename( charitable()->get_path() . 'charitable.php' ), '', false, true );
				wp_send_json_error( esc_html__( 'Pro version installed but needs to be activated on the Plugins page inside your WordPress admin.', 'charitable' ) );
			}
		}

		wp_send_json_error( esc_html__( 'No plugin installed.', 'charitable' ) );
	}

	/**
	 * Get the Charitable Pro download URL from cached API data.
	 *
	 * @since 1.8.9.6
	 * @version 1.8.9.6
	 *
	 * @return string|false The download URL or false if not available.
	 */
	private function get_pro_download_url() {
		// Get cached plugin versions data
		$versions = get_transient( '_charitable_plugin_versions' );

		if ( false === $versions || empty( $versions ) ) {
			// Try to refresh the data if not cached
			$addons_directory = Charitable_Addons_Directory::get_instance();
			$versions = $addons_directory->get_addons_data_from_server();

			if ( false === $versions || empty( $versions ) ) {
				return false;
			}
		}

		// Look for Charitable Pro in the API data. Slug matches EDD product on wpcharitable.com
		// (versions-v3 returns 'charitable-pro-plugin'; plugin dir is charitable-pro/).
		$pro_slugs = array( 'charitable-pro-plugin', 'charitable-pro' );
		if ( is_array( $versions ) ) {
			foreach ( $versions as $plugin ) {
				if ( empty( $plugin['slug'] ) || ! in_array( $plugin['slug'], $pro_slugs, true ) ) {
					continue;
				}
				if ( ! empty( $plugin['download_link'] ) && 'missing_license' !== $plugin['download_link'] ) {
					return $plugin['download_link'];
				}
				if ( ! empty( $plugin['install'] ) && 'missing_license' !== $plugin['install'] ) {
					return $plugin['install'];
				}
				if ( ! empty( $plugin['package'] ) && 'missing_license' !== $plugin['package'] ) {
					return $plugin['package'];
				}
			}
		}

		return false;
	}

	/**
	 * AJAX handler to download Charitable Pro plugin.
	 *
	 * @since 1.8.9.6
	 * @version 1.8.9.6
	 */
	public function download_pro_plugin() {
		// Run a security check.
		check_ajax_referer( 'charitable-admin', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to install plugins.', 'charitable' ) ) );
		}

		$download_url = ! empty( $_POST['download_url'] ) ? esc_url_raw( wp_unslash( $_POST['download_url'] ) ) : '';

		if ( empty( $download_url ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						esc_html__( 'Download URL missing. Please visit the %1$sAddons page%2$s to download manually.', 'charitable' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=charitable-addons' ) ) . '">',
						'</a>'
					),
					'addons_url' => admin_url( 'admin.php?page=charitable-addons' ),
				)
			);
		}

		// Set the current screen to avoid undefined notices.
		set_current_screen( 'charitable_page_charitable-settings' );

		// Check file system permissions.
		$url = admin_url( 'admin.php?page=charitable-settings' );
		$creds = request_filesystem_credentials( $url, '', false, false );

		if ( $creds === false || ! WP_Filesystem( $creds ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						esc_html__( 'Unable to access file system. Please visit the %1$sAddons page%2$s to download manually, or see our %3$sinstallation guide%4$s.', 'charitable' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=charitable-addons' ) ) . '">',
						'</a>',
						'<a href="https://www.wpcharitable.com/documentation/installing-extensions/" target="_blank">',
						'</a>'
					),
					'addons_url' => admin_url( 'admin.php?page=charitable-addons' ),
				)
			);
		}

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

		// Include required files.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once plugin_dir_path( CHARITABLE_DIRECTORY_PATH ) . 'charitable/includes/utilities/Skin.php';

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( new Charitable_Skin() );

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						esc_html__( 'Installation method not available. Please visit the %1$sAddons page%2$s to download manually.', 'charitable' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=charitable-addons' ) ) . '">',
						'</a>'
					),
					'addons_url' => admin_url( 'admin.php?page=charitable-addons' ),
				)
			);
		}

		// Install the plugin.
		$installer->install( $download_url );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		$plugin_basename = $installer->plugin_info();

		if ( $plugin_basename ) {
			// Verify the newly installed plugin actually exists before proceeding
			$new_plugin_path = WP_PLUGIN_DIR . '/' . $plugin_basename;
			if ( ! file_exists( $new_plugin_path ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Plugin download completed but files not found. Please try again.', 'charitable' ) ) );
			}

			wp_send_json_success(
				array(
					'message'         => esc_html__( 'Charitable Pro downloaded successfully!', 'charitable' ),
					'action'          => 'show_activation_confirmation',
					'plugin_basename' => $plugin_basename,
					'activation_title' => esc_html__( 'Download Complete!', 'charitable' ),
					'activation_text'  => esc_html__( 'Charitable Pro downloaded successfully! Activate now?', 'charitable' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => sprintf(
						esc_html__( 'Download failed. Please visit the %1$sAddons page%2$s to try again, or see our %3$sinstallation guide%4$s.', 'charitable' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=charitable-addons' ) ) . '">',
						'</a>',
						'<a href="https://www.wpcharitable.com/documentation/installing-extensions/" target="_blank">',
						'</a>'
					),
					'addons_url' => admin_url( 'admin.php?page=charitable-addons' ),
				)
			);
		}
	}

	/**
	 * AJAX handler to activate Charitable Pro plugin.
	 *
	 * @since 1.8.9.6
	 * @version 1.8.9.6
	 */
	public function activate_pro_plugin() {
		// Run a security check.
		check_ajax_referer( 'charitable-admin', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to activate plugins.', 'charitable' ) ) );
		}

		$plugin_basename = ! empty( $_POST['plugin_basename'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_basename'] ) ) : '';

		if ( empty( $plugin_basename ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Plugin basename missing.', 'charitable' ) ) );
		}

		// CRITICAL SAFETY CHECK: Verify Pro plugin actually exists before attempting activation
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_basename;
		if ( ! file_exists( $plugin_path ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Plugin files not found. Cannot activate.', 'charitable' ) ) );
		}

		// Get current plugin info for deactivation.
		$current_plugin = plugin_basename( charitable()->get_path() . 'charitable.php' );

		// Activate Pro plugin.
		$activated = activate_plugin( $plugin_basename, '', false, true );

		if ( ! is_wp_error( $activated ) ) {
			// TODO: Uncomment to deactivate Lite after activating Pro.
			// deactivate_plugins( $current_plugin );
			// do_action( 'charitable_plugin_deactivated', $current_plugin );

			$redirect_url = admin_url( 'admin.php?page=charitable-settings&tab=general' );
			wp_send_json_success(
				array(
					'message'      => esc_html__( 'Charitable Pro activated successfully!', 'charitable' ),
					'redirect_url' => $redirect_url,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Charitable Pro installed but activation failed. You can activate it manually from the Plugins page.', 'charitable' ),
					'error_details' => $activated->get_error_message(),
				)
			);
		}
	}

	/**
	 * Build redirect data for use after license verify: upgrade_url or show_manual_upgrade (localhost).
	 *
	 * Used so the Verify flow can redirect to upgrade.wpcharitable.com (or show manual message on localhost).
	 *
	 * @since 1.8.9.6
	 *
	 * @param string $license_key License key.
	 * @return array Either ['upgrade_url' => url] or ['show_manual_upgrade' => true, 'url' => ..., 'message' => ...].
	 */
	public function build_upgrade_redirect_data( $license_key ) {
		$license_key = trim( $license_key );
		if ( empty( $license_key ) ) {
			if ( charitable_is_debug() ) {
				error_log( 'CHARITABLE DEBUG: build_upgrade_redirect_data empty key' );
			}
			return array();
		}
		// Pro plugin already active: no redirect (user is on Pro).
		$pro_plugin_path = WP_PLUGIN_DIR . '/' . self::PRO_PLUGIN;
		$pro_is_active   = file_exists( $pro_plugin_path ) && in_array( self::PRO_PLUGIN, (array) get_option( 'active_plugins', array() ), true );
		if ( charitable_is_debug() ) {
			error_log( 'CHARITABLE DEBUG: build_upgrade_redirect_data pro_plugin_path=' . $pro_plugin_path . ' exists=' . ( file_exists( $pro_plugin_path ) ? '1' : '0' ) . ' pro_is_active=' . ( $pro_is_active ? '1' : '0' ) );
		}
		if ( $pro_is_active ) {
			return array();
		}
		// Pro installed but inactive: show popup to activate; do not go to upgrade.wpcharitable.com.
		if ( file_exists( $pro_plugin_path ) ) {
			return array(
				'show_activate_pro_popup' => true,
				'message'                 => esc_html__( 'Your license is valid. Pro is installed but inactive. Activate now?', 'charitable' ),
				'redirect_url'            => admin_url( 'admin.php?page=charitable-settings&tab=general' ),
				'plugin_basename'         => self::PRO_PLUGIN,
			);
		}
		// Localhost: show manual install message instead of redirect.
		if ( function_exists( 'charitable_is_localhost' ) && charitable_is_localhost() ) {
			if ( charitable_is_debug() ) {
				error_log( 'CHARITABLE DEBUG: build_upgrade_redirect_data returning show_manual_upgrade (localhost)' );
			}
			return array(
				'show_manual_upgrade' => true,
				'url'                 => 'https://www.wpcharitable.com/documentation/installing-extensions/',
				'message'             => esc_html__( 'License validated successfully! Since you\'re on localhost, please install Charitable Pro manually using the link below.', 'charitable' ),
			);
		}
		// Set token and connect key so upgrade site callback (process()) can run.
		$oth_raw    = wp_generate_password( 64, true, true );
		$oth_signed = hash_hmac( 'sha512', $oth_raw, wp_salt() );
		update_option( 'charitable_connect_token', $oth_raw );
		update_option( 'charitable_connect', $license_key );
		$redirect_back = admin_url( 'admin.php?page=charitable-settings&tab=general' );
		$endpoint     = $this->get_connect_callback_endpoint();
		$upgrade_url   = add_query_arg(
			array(
				'key'      => $license_key,
				'oth'      => $oth_signed,
				'endpoint' => $endpoint,
				'version'  => charitable()->get_version(),
				'siteurl'  => site_url(),
				'homeurl'  => home_url(),
				'redirect' => base64_encode( $redirect_back ),
			),
			'https://upgrade.wpcharitable.com/'
		);
		if ( charitable_is_debug() ) {
			error_log( 'CHARITABLE DEBUG: build_upgrade_redirect_data returning upgrade_url (length ' . strlen( $upgrade_url ) . ')' );
		}
		return array( 'upgrade_url' => $upgrade_url );
	}

	/**
	 * Filter callback: add upgrade_url (or show_manual_upgrade) to license verify success response.
	 *
	 * @since 1.8.9.6
	 *
	 * @param array  $license_data Data being sent to the frontend.
	 * @param string $license      License key.
	 * @return array Modified license_data.
	 */
	public function add_upgrade_url_to_license_response( $license_data, $license ) {
		if ( charitable_is_debug() ) {
			error_log( 'CHARITABLE DEBUG: add_upgrade_url_to_license_response called' );
		}
		$extra = $this->build_upgrade_redirect_data( $license );
		if ( charitable_is_debug() && ! empty( $extra ) ) {
			error_log( 'CHARITABLE DEBUG: add_upgrade_url_to_license_response adding keys: ' . implode( ', ', array_keys( $extra ) ) );
		}
		return array_merge( $license_data, $extra );
	}

	/**
	 * Get the endpoint URL for the upgrade site callback.
	 *
	 * Uses connect-callback.php in the plugin directory so the callback works when
	 * the site redirects unauthenticated /wp-admin/ requests to the login page.
	 *
	 * @since 1.8.9.6
	 *
	 * @return string URL the upgrade site should POST to.
	 */
	private function get_connect_callback_endpoint() {
		$url = charitable()->get_path( 'directory', false ) . 'vendor/wpcharitable/connect-callback.php';
		return apply_filters( 'charitable_connect_callback_endpoint', $url );
	}

	/**
	 * Returns and/or create the single instance of this class.
	 *
	 * @since  1.8.1.12
	 *
	 * @return Charitable_Admin_Connect
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
