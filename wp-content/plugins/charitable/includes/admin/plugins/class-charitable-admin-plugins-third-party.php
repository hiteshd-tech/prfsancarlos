<?php
/**
 * This class is responsible for interactions (downloading/installing) addons and third-party addons, and recommendations.
 *
 * @package   Charitable/Classes/Charitable_Admin_Plugins_Third_Party
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Plugins_Third_Party' ) ) :

	/**
	 * Charitable_Admin_Analytics
	 *
	 * @since 1.8.1
	 */
	final class Charitable_Admin_Plugins_Third_Party {

		/**
		 * The name of our database table
		 *
		 * @since 1.8.1
		 *
		 * @var   string
		 */
		public $table_name;

		/**
		 * General storage for analytic data
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $analytics;

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Admin_Plugins_Third_Party|null
		 */
		private static $instance = null;


		/**
		 * Create class object.
		 *
		 * @since  1.8.1
		 */
		public function __construct() {

			$this->init();
		}

		/**
		 * Init class.
		 *
		 * @since  1.8.1
		 */
		public function init() {

			add_action( 'wp_ajax_charitable_dismiss_suggestion', array( $this, 'charitable_dismiss_suggestion' ) );
		}

		/**
		 * Dismiss a suggestion via AJAX.
		 *
		 * @since  1.8.1.5
		 *
		 * @return void
		 */
		public function charitable_dismiss_suggestion() {

			check_ajax_referer( 'charitable-admin', 'nonce' );

			$post_data = sanitize_post( $_POST, 'raw' );

			$slug = isset( $post_data['slug'] ) ? sanitize_text_field( wp_unslash( $post_data['slug'] ) ) : false;
			$type = isset( $post_data['type'] ) ? sanitize_text_field( wp_unslash( $post_data['type'] ) ) : false;

			if ( ! $slug ) {
				wp_send_json(
					array(
						'message' => esc_html__( 'Missing plugin name for dismiss.', 'charitable' ),
					)
				);
			}

			if ( ! $type ) {
				wp_send_json(
					array(
						'message' => esc_html__( 'Missing plugin type for dismiss.', 'charitable' ),
					)
				);
			}

			if ( 'addon' === $type ) {

				$user_meta_key = 'charitable_dismissed_addons';

			} elseif ( 'partner' === $type ) {

				$user_meta_key = 'charitable_dismissed_partners';

			} else {
				wp_send_json(
					array(
						'message' => esc_html__( 'Invalid plugin type for dismiss.', 'charitable' ),
					)
				);
			}

			$dismissed = (array) get_user_meta( get_current_user_id(), $user_meta_key, true );

			if ( ! in_array( $slug, $dismissed, true ) ) {
				$dismissed[] = $slug;
			}

			// remove blank values.
			$dismissed = array_filter( $dismissed );

			// update user meta.
			update_user_meta( get_current_user_id(), $user_meta_key, $dismissed );

			wp_send_json_success(
				array(
					'dismissed' => $dismissed,
				),
				200
			);

			wp_die();
		}

		/**
		 * Install plugins which are not Charitable addons.
		 *
		 * @since   1.8.1
		 *
		 * @return void
		 */
		public function install_plugin() {

			check_admin_referer( 'charitable-admin', 'nonce' );

			$post_data = sanitize_post( $_POST, 'raw' );

			// Permission check.
			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json(
					array(
						'error' => esc_html__( 'Plugin install is disabled for you on this site.', 'charitable' ),
					)
				);

				wp_send_json_error( esc_html__( 'Install error', 'charitable' ) );
			}

		$slug = isset( $post_data['slug'] ) ? sanitize_text_field( wp_unslash( $post_data['slug'] ) ) : false;


		if ( ! $slug ) {
			wp_send_json(
				array(
					'message' => esc_html__( 'Missing plugin name for install.', 'charitable' ),
				)
			);
		}

			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

		if ( is_wp_error( $api ) ) {
			wp_send_json(
				array(
					'error' => $api->get_error_message(),
				)
			);
			if ( charitable_is_debug() ) :
				error_log( print_r( $api->get_error_message(), true ) ); // phpcs:ignore
			endif;
			die();
		}

			$download_url = $api->download_link;

			$method = '';
			$url    = add_query_arg(
				array(
					'page' => 'charitable_settings',
				),
				admin_url( 'admin.php' )
			);
			$url    = esc_url( $url );

			ob_start();
			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) { // phpcs:ignore
				$form = ob_get_clean();

				wp_send_json( array( 'form' => $form ) );
			}

			// If we are not authenticated, make it happen now.
			if ( ! WP_Filesystem( $creds ) ) {
				ob_start();
				request_filesystem_credentials( $url, $method, true, false, null );
				$form = ob_get_clean();
				wp_send_json( array( 'form' => $form ) );
				die();
			}

			// Prevent language upgrade in ajax calls.
			remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

			// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once plugin_dir_path( CHARITABLE_DIRECTORY_PATH ) . 'charitable/includes/utilities/Skin.php';

			// Create the plugin upgrader with our custom skin.
			$skin      = new Charitable_Skin();
			$installer = new Plugin_Upgrader( $skin );
			$installer->install( $download_url );

			// Flush the cache and return the newly installed plugin basename.
			wp_cache_flush();

			// send json success with array.
			$basename = $this->get_basename_from_slug( $slug );

			wp_send_json_success(
				array(
					'basename' => $basename,
				),
				200
			);

			wp_die();
		}

		/**
		 * Get plugin basename from slug.
		 *
		 * @param string $slug The plugin slug.
		 */
		public function get_basename_from_slug( $slug = false ) {

			if ( false === $slug ) {
				return;
			}

			$basename = '';
			$plugins  = $this->get_plugins( false );
			if ( charitable_is_debug() ) :
				error_log( print_r( $plugins, true ) ); // phpcs:ignore
			endif;
			if ( ! empty( $plugins ) ) :
				foreach ( $plugins as $nice_name => $plugin ) {
					if ( charitable_is_debug() ) :
						error_log( print_r( $plugin, true ) ); // phpcs:ignore
					endif;
					if ( ! empty( $plugin['slug'] ) && ( $slug === $plugin['slug'] || $slug === $nice_name ) && isset( $plugin['basename'] ) ) {
						$basename = $plugin['basename'];
					}
				}
			endif;

			return $basename;
		}

		/**
		 * Activate plugin.
		 *
		 * @since  1.8.1
		 * @version 1.8.4
		 *
		 * @return void
		 */
		public function activate_plugin() {

			check_ajax_referer( 'charitable-admin', 'nonce' );

			$post_data = sanitize_post( $_POST, 'raw' );

			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json(
					array(
						'error' => esc_html__( 'You are not allowed to install plugins', 'charitable' ),
					)
				);
			}

			$basename = isset( $post_data['basename'] ) ? sanitize_text_field( wp_unslash( $post_data['basename'] ) ) : false;

			if ( ! $basename ) {
				wp_send_json(
					array(
						'message' => esc_html__( 'Missing plugin basename.', 'charitable' ),
					)
				);
			}

			$success = activate_plugin( $basename, '', false );

			// Disable MonsterInsights redirect.
			if ( strstr( $basename, 'google-analytics-for-wordpress' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'MonsterInsights activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( '_monsterinsights_activation_redirect' );
			}
			// Disable Exactmetrics redirect.
			if ( strstr( $basename, 'google-analytics-dashboard-for-wp' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'Exactmetrics activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( '_exactmetrics_activation_redirect' );
			}
			// Disable WP Mail SMTP redirect.
			if ( strstr( $basename, 'wp-mail-smtp' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'WP Mail SMTP activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( 'wp_mail_smtp_activation_redirect' );
			}
			// Disable Rafflepress redirect.
			if ( strstr( $basename, 'rafflepress' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'Rafflepress activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( '_rafflepress_welcome_screen_activation_redirect' );
			}
			// Disable seedprod redirect.
			if ( strstr( $basename, 'seedprod' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'SeedProd activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( '_seedprod_welcome_screen_activation_redirect' );
			}
			// Disable instagram-feed redirect.
			if ( strstr( $basename, 'instagram-feed' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'Instagram Feed activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_option( 'sbi_plugin_do_activation_redirect' );
			}
			// Disable facebook-feed redirect.
			if ( strstr( $basename, 'facebook-feed' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'Facebook Feed activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_option( 'cff_plugin_do_activation_redirect' );
			}
			// Disable trustpulse redirect.
			if ( strstr( $basename, 'trustpulse' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'TrustPulse activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_option( 'trustpulse_api_plugin_do_activation_redirect' );
			}
			// Disable searchwp-live-ajax-search redirect.
			if ( strstr( $basename, 'searchwp-live-ajax-search' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'SearchWP Live Search activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( 'searchwp_live_search_activation_redirect' );
			}
			// Disable duplicator redirect.
			if ( strstr( $basename, 'duplicator' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'Duplicator activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				update_option( 'duplicator_redirect_to_welcome', false );
			}
			// Disable pushengage redirect.
			if ( strstr( $basename, 'pushengage' ) ) {
				if ( charitable_is_debug() ) :
					error_log( print_r( 'PushEngage activated, deleting transient/updating option to prevent redirect.', true ) ); // phpcs:ignore
				endif;
				delete_transient( 'pushengage_activation_redirect' );
			}

			if ( is_wp_error( $success ) ) {
				wp_send_json_error(
					array(
						'error' => $success->get_error_message(),
					)
				);
				wp_die();
			}

			$setup = $this->get_setup_screen_for_plugin( $basename );

			if ( $setup ) :

				wp_send_json_success(
					array(
						'setup' => $setup,
					),
					200
				);
				wp_die();

			endif;

			$settings = $this->get_settings_screen_for_plugin( $basename );

			if ( $settings ) :

				wp_send_json_success(
					array(
						'settings' => $settings,
					),
					200
				);
				wp_die();

			endif;

			wp_die();
		}

		/**
		 * Checks if plugin is installed.
		 *
		 * @param string $plugin The plugin slug.
		 */
		public function is_plugin_installed( $plugin = '' ) {

			if ( empty( $plugin ) ) {
				return false;
			}

			$plugins = $this->get_plugins( false );

			if ( ! empty( $plugins[ $plugin ] ) && ! empty( $plugins[ $plugin ]['installed'] ) ) {
				return (bool) $plugins[ $plugin ]['installed'];
			}

			return false;
		}

		/**
		 * Checks if plugin is activated.
		 *
		 * @param string $plugin The plugin slug.
		 */
		public function is_plugin_activated( $plugin = '' ) {

			if ( empty( $plugin ) ) {
				return false;
			}

			$plugins = $this->get_plugins( false );

			if ( ! empty( $plugins[ $plugin ] ) && ! empty( $plugins[ $plugin ]['active'] ) ) {
				return (bool) $plugins[ $plugin ]['active'];
			}

			return false;
		}

		/**
		 * Get plugin basename.
		 *
		 * @param string $plugin The plugin slug.
		 */
		public function get_basename( $plugin = '' ) {

			if ( empty( $plugin ) ) {
				return false;
			}

			$plugins = $this->get_plugins( false );

			if ( ! empty( $plugins[ $plugin ] ) && ! empty( $plugins[ $plugin ]['active'] ) ) {
				return $plugins[ $plugin ]['basename'];
			}

			return false;
		}

		/**
		 * Get plugin setup url.
		 *
		 * @param string $slug_or_basename The plugin slug.
		 */
		public function get_setup_screen_for_plugin( $slug_or_basename = false ) {

			if ( false === $slug_or_basename ) {
				return;
			}

			$setup   = '';
			$plugins = $this->get_plugins( false );
			if ( ! empty( $plugins ) ) :
				foreach ( $plugins as $plugin_slug => $plugin_info ) {
					if ( ! empty( $plugin_info['setup'] ) && ( $plugin_slug === $slug_or_basename || $plugin_info['basename'] === $slug_or_basename ) ) {
						$setup = $plugin_info['setup'];
					}
				}
			endif;

			return $setup;
		}

		/**
		 * Get plugin settings url.
		 *
		 * @param string $slug_or_basename The plugin slug.
		 */
		public function get_settings_screen_for_plugin( $slug_or_basename = false ) {

			if ( false === $slug_or_basename ) {
				return;
			}

			$setup   = '';
			$plugins = $this->get_plugins( false );
			if ( ! empty( $plugins ) ) :
				foreach ( $plugins as $plugin_slug => $plugin_info ) {
					if ( ! empty( $plugin_info['settings'] ) && ( $plugin_slug === $slug_or_basename || $plugin_info['basename'] === $slug_or_basename ) ) {
						$setup = $plugin_info['settings'];
					}
				}
			endif;

			return $setup;
		}

		/**
		 * Get plugin learn more button.
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $button_text The button text.
		 * @param string $css         The button css.
		 */
		public function get_learn_more_button_html( $plugin_slug = false, $button_text = 'Learn More', $css = '' ) {

			if ( false === $plugin_slug ) {
				return;
			}

			$plugins = $this->get_plugins( false );

			if ( ! isset( $plugins[ $plugin_slug ] ) ) {
				return;
			}

			$plugin = $plugins[ $plugin_slug ];

			if ( ! $plugin || ! isset( $plugin['slug'] ) ) {
				return;
			}

			$button_css = 'charitable-button button-link charitable-button-learn-more';

			if ( $css !== '' ) {
				$button_css .= ' ' . $css;
			}

			if ( false === $button_text ) {
				$button_text = esc_html__( 'Learn More', 'charitable' );
			}

			$url = ! empty( $plugin['gt_learn'] ) ? esc_url( $plugin['gt_learn'] ) : false;

			if ( ! $url ) {
				return;
			}

			$button = '<a href="' . $url . '" target=="_blank" class="' . $button_css . '" data-charitable-third-party-plugin="' . esc_attr( $plugin['slug'] ) . '">' . $button_text . '</a>';

			return $button;
		}

		/**
		 * Get plugin install button.
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $button_text The button text.
		 * @param string $css         The button css.
		 */
		public function get_install_button_html( $plugin_slug = false, $button_text = 'Install', $css = '' ) {

			if ( false === $plugin_slug ) {
				return;
			}

			$plugins = $this->get_plugins( false );

			if ( ! isset( $plugins[ $plugin_slug ] ) ) {
				return;
			}

			$plugin = $plugins[ $plugin_slug ];

			if ( ! $plugin || ! isset( $plugin['slug'] ) ) {
				return;
			}

			$button_css = 'charitable-button button-link charitable-button-install';

			if ( $css !== '' ) {
				$button_css .= ' ' . $css;
			}

			if ( false === $button_text ) {
				$button_text = esc_html__( 'Install', 'charitable' );
			}

			$button = '<a href="#" class="' . $button_css . '" data-charitable-third-party-plugin="' . esc_attr( $plugin['slug'] ) . '">' . $button_text . '</a>';

			return $button;
		}

		/**
		 * Get plugin activation button.
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $button_text The button text.
		 * @param string $css         The button css.
		 */
		public function get_activation_button_html( $plugin_slug = false, $button_text = 'Activate', $css = '' ) {

			if ( false === $plugin_slug ) {
				return;
			}

			$plugins = $this->get_plugins( false );

			if ( ! isset( $plugins[ $plugin_slug ] ) ) {
				return;
			}

			$plugin = $plugins[ $plugin_slug ];

			if ( ! $plugin || ! isset( $plugin['basename'] ) ) {
				return;
			}

			$button_css = 'charitable-button button-link charitable-button-activate';

			if ( $css !== '' ) {
				$button_css .= ' ' . $css;
				$button_css  = str_replace( 'install', 'activate', $button_css );
			}

			if ( false === $button_text ) {
				$button_text = esc_html__( 'Activate', 'charitable' );
			}

			$button = '<a href="#" class="' . $button_css . '" data-basename="' . $plugin['basename'] . '">' . $button_text . '</a>';

			return $button;
		}

		/**
		 * Get plugin setup html link/button.
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $button_text The button text.
		 * @param string $css         The button css.
		 * @param string $setup_link  The setup link.
		 */
		public function get_setup_button_html( $plugin_slug = false, $button_text = 'Activate', $css = '', $setup_link = '' ) {

			if ( false === $plugin_slug ) {
				return;
			}

			$plugins = $this->get_plugins( false );

			if ( ! isset( $plugins[ $plugin_slug ] ) ) {
				return;
			}

			$plugin = $plugins[ $plugin_slug ];

			if ( ! $plugin || ! isset( $plugin['basename'] ) || ! isset( $plugin['setup'] ) ) {
				return;
			}

			$setup_link = isset( $plugin['setup'] ) ? esc_url( $plugin['setup'] ) : esc_url( admin_url() );

			$button_css = 'charitable-button button-link charitable-button-setup';

			if ( $css !== '' ) {
				$button_css .= ' ' . $css;
				$button_css  = str_replace( 'install', 'setup', $button_css );
				$button_css  = str_replace( 'activate', 'setup', $button_css );
			}

			if ( false === $button_text ) {
				$button_text = esc_html__( 'Setup', 'charitable' );
			}

			$button = '<a href="' . esc_url( $setup_link ) . '" class="' . $button_css . '" data-basename="' . $plugin['basename'] . '">' . $button_text . '</a>';

			return $button;
		}

		/**
		 * Get plugin button html.
		 *
		 * @since  1.8.1.5
		 *
		 * @param string $plugin_slug The plugin slug.
		 * @param string $button_text The button text.
		 * @param string $css         The button css.
		 *
		 * @return string
		 */
		public function get_plugin_button_html( $plugin_slug = false, $button_text = false, $css = false ) {

			if ( false === $plugin_slug ) {
				return;
			}

			if ( $this->is_plugin_learn_more( $plugin_slug ) ) {
				return $this->get_learn_more_button_html( $plugin_slug, false, $css );
			}

			$is_installed = $this->is_plugin_installed( $plugin_slug );
			$is_active    = $this->is_plugin_activated( $plugin_slug );

			if ( ! $is_installed ) {
				return $this->get_install_button_html( $plugin_slug, $button_text, $css );
			}

			if ( ! $is_active ) {
				return $this->get_activation_button_html( $plugin_slug, $button_text, $css );
			}

			return $this->get_setup_button_html( $plugin_slug, $button_text, $css );
		}


		/**
		 * Check if plugin has a learn more link.
		 *
		 * @since 1.8.1.6
		 *
		 * @param string $plugin_slug The plugin slug.
		 *
		 * @return boolean
		 */
		public function is_plugin_learn_more( $plugin_slug = false ) {

			if ( false === $plugin_slug ) {
				return;
			}

			$plugins = $this->get_plugins( false );

			if ( ! isset( $plugins[ $plugin_slug ] ) ) {
				return;
			}

			$plugin = $plugins[ $plugin_slug ];

			if ( ! $plugin || ! isset( $plugin['slug'] ) ) {
				return;
			}

			$learn = ! empty( $plugin['gt_learn'] ) ? esc_url( $plugin['gt_learn'] ) : false;

			if ( $learn ) {
				return true;
			}

			return false;
		}


		/**
		 * Get the recommended plugins.
		 *
		 * @since   1.8.1
		 * @version 1.8.8
		 *
		 * @param  boolean $return_json Whether to return the plugins as JSON or an array.
		 *
		 * @return array
		 */
		public function get_plugins( $return_json = true ) {

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$includes_path = charitable()->get_path( 'includes' );
			require_once $includes_path . 'utilities/charitable-utility-functions.php';

			$installed_plugins = get_plugins();

			$plugins = array();

			// Monsterinsights.
			$plugins['monsterinsights'] = array(
				'active'     => function_exists( 'monsterinsights' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/mi.png',
				'title'      => 'MonsterInsights',
				'excerpt'    => __( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'charitable' ),
				'why'        => __( 'Analytics plugins can help you determine how people are finding your campaigns, and which of your public campaigns are getting viewed the most.', 'charitable' ),
				'what'       => __( 'an analytics plugin', 'charitable' ),
				'installed'  => array_key_exists( 'google-analytics-for-wordpress/googleanalytics.php', $installed_plugins ) || array_key_exists( 'google-analytics-premium/googleanalytics-premium.php', $installed_plugins ),
				'basename'   => 'google-analytics-for-wordpress/googleanalytics.php',
				'slug'       => 'google-analytics-for-wordpress',
				'settings'   => admin_url( 'admin.php?page=monsterinsights-settings' ),
				'setup'      => admin_url( 'index.php?page=monsterinsights-onboarding' ),
				'id'         => 'mi',
				'gt_section' => 'traffic',
			);

			// WPForms.
			$plugins['wpforms-lite'] = array(
				'active'     => function_exists( 'wpforms' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/wpforms.png',
				'title'      => 'WPForms',
				'excerpt'    => __( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 1000+ form templates. Trusted by over 6 million websites as the best forms plugin.', 'charitable' ),
				'why'        => __( 'Form plugins can help you get real feedback such as campaign recommendations or ideas for different payment options.', 'charitable' ),
				'what'       => __( 'a form plugin', 'charitable' ),
				'installed'  => array_key_exists( 'wpforms-lite/wpforms.php', $installed_plugins ),
				'basename'   => 'wpforms-lite/wpforms.php',
				'slug'       => 'wpforms-lite',
				'settings'   => admin_url( 'admin.php?page=wpforms-settings' ),
				'setup'      => admin_url( 'admin.php?page=wpforms-settings' ),
				'id'         => 'wpforms',
				'gt_section' => 'engagement',
			);

			// AIOSEO.
			$plugins['aioseo'] = array(
				'active'     => function_exists( 'aioseo' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/all-in-one-seo.png',
				'title'      => 'AIOSEO',
				'excerpt'    => __( 'The original WordPress SEO plugin and toolkit that improves your website’s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.', 'charitable' ),
				'why'        => __( 'Using an SEO plugin helps you make sure your campaigns and causes are findable on the web.', 'charitable' ),
				'what'       => __( 'an SEO plugin', 'charitable' ),
				'installed'  => array_key_exists( 'all-in-one-seo-pack/all_in_one_seo_pack.php', $installed_plugins ),
				'basename'   => ( charitable_is_installed_aioseo_pro() ) ? 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' : 'all-in-one-seo-pack/all_in_one_seo_pack.php',
				'slug'       => 'all-in-one-seo-pack',
				'settings'   => admin_url( 'admin.php?page=aioseo' ),
				'setup'      => admin_url( 'admin.php?page=aioseo' ),
				'id'         => 'aioseo',
				'gt_section' => 'traffic',
			);

			// OptinMonster.
			$plugins['optinmonster'] = array(
				'active'     => class_exists( 'OMAPI' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/om.png',
				'title'      => 'OptinMonster',
				'excerpt'    => __( 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'charitable' ),
				'why'        => __( 'Marketing plugins that offer exit-intent technology, page targeting, and A/B testing can help you keep potential donors on your site.', 'charitable' ),
				'what'       => __( 'particular marketing plugins', 'charitable' ),
				'installed'  => array_key_exists( 'optinmonster/optin-monster-wp-api.php', $installed_plugins ),
				'basename'   => 'optinmonster/optin-monster-wp-api.php',
				'slug'       => 'optinmonster',
				'settings'   => admin_url( 'admin.php?page=optin-monster-dashboard' ),
				'setup'      => admin_url( 'admin.php?page=optin-monster-dashboard' ),
				'id'         => 'optinmonster',
				'gt_section' => 'engagement',
			);

			// RafflePress.
			$plugins['rafflepress'] = array(
				'active'     => defined( 'RAFFLEPRESS_VERSION' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/rafflepress.png',
				'title'      => 'RafflePress',
				'excerpt'    => __( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'charitable' ),
				'why'        => __( 'Offering giveaways and contests can help strengthen your overall cause and particular campaigns.', 'charitable' ),
				'what'       => __( 'particular marketing plugins', 'charitable' ),
				'installed'  => array_key_exists( 'rafflepress/rafflepress.php', $installed_plugins ),
				'basename'   => 'rafflepress/rafflepress.php',
				'slug'       => 'rafflepress',
				'settings'   => admin_url( 'admin.php?page=rafflepress_lite' ),
				'setup'      => admin_url( 'admin.php?page=rafflepress_lite' ),
				'id'         => 'rafflepress',
				'gt_section' => 'traffic',
			);

			// SeedProd.
			$plugins['coming-soon'] = array(
				'active'     => defined( 'SEEDPROD_VERSION' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/seedprod.png',
				'title'      => 'SeedProd',
				'excerpt'    => __( 'Use the best drag-and-drop landing page builder for WordPress to instantly build coming soon pages, sales pages, opt-in pages, webinar pages, maintenance pages, and more. Includes 100+ free templates.', 'charitable' ),
				'why'        => __( 'Build landing pages for your general causes while showcasing testimonials form donors and campaign benefactors.', 'charitable' ),
				'what'       => __( 'a page builder plugin', 'charitable' ),
				'installed'  => array_key_exists( 'coming-soon/coming-soon.php', $installed_plugins ),
				'basename'   => 'coming-soon/coming-soon.php',
				'slug'       => 'coming-soon',
				'settings'   => admin_url( 'admin.php?page=seedprod_lite' ),
				'setup'      => admin_url( 'admin.php?page=seedprod_lite' ),
				'id'         => 'seedprod',
				'gt_section' => 'revenue',
			);

			// WP Mail Smtp.
			$plugins['wp-mail-smtp'] = array(
				'active'     => function_exists( 'wp_mail_smtp' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/smtp.png',
				'title'      => 'WP Mail SMTP',
				'excerpt'    => __( 'Improve your WordPress email deliverability and make sure that your website emails reach user’s inbox with the #1 SMTP plugin for WordPress. Over 3 million websites use it to fix WordPress email issues.', 'charitable' ),
				'why'        => __( 'Sending reliable emails to donors after they have donated is vital, along with making sure admins get email notifications setup in Charitable.', 'charitable' ),
				'what'       => __( 'a SMTP / email plugin', 'charitable' ),
				'installed'  => array_key_exists( 'wp-mail-smtp/wp_mail_smtp.php', $installed_plugins ),
				'basename'   => 'wp-mail-smtp/wp_mail_smtp.php',
				'slug'       => 'wp-mail-smtp',
				'settings'   => admin_url( 'admin.php?page=wp-mail-smtp' ),
				'setup'      => admin_url( 'admin.php?page=wp-mail-smtp' ),
				'id'         => 'wpmailsmtp',
				'gt_section' => 'engagement',
			);

			// EDD.
			$plugins['easy-digital-downloads'] = array(
				'active'     => class_exists( 'Easy_Digital_Downloads' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/edd.png',
				'title'      => 'Easy Digital Downloads',
				'excerpt'    => __( 'The best WordPress eCommerce plugin for selling digital downloads. Start selling eBooks, software, music, digital art, and more within minutes. Accept payments, manage subscriptions, advanced access control, and more.', 'charitable' ),
				'installed'  => array_key_exists( 'easy-digital-downloads/easy-digital-downloads.php', $installed_plugins ),
				'basename'   => 'easy-digital-downloads/easy-digital-downloads.php',
				'slug'       => 'easy-digital-downloads',
				'setup'      => admin_url( 'edit.php?post_type=download&page=edd-settings' ),
				'settings'   => admin_url( 'edit.php?post_type=download&page=edd-settings' ),
				'id'         => 'edd',
				'gt_section' => 'revenue',
			);

			// PrettyLinks.
			$plugins['pretty-link'] = array(
				'active'     => function_exists( 'prli_plugin_info' ),
				'icon'       => false,
				'title'      => 'Pretty Links',
				'excerpt'    => __( 'Automatically monetize your website content with affiliate links added automatically to your content.', 'charitable' ),
				'installed'  => array_key_exists( 'pretty-link/pretty-link.php', $installed_plugins ),
				'basename'   => 'pretty-link/pretty-link.php',
				'slug'       => 'pretty-link',
				'setup'      => admin_url( 'edit.php?post_type=pretty-link' ),
				'settomgs'   => admin_url( 'edit.php?post_type=pretty-link' ),
				'id'         => 'prettylinks',
				'gt_section' => 'revenue',
			);

			// Smash Balloon (Instagram).
			$plugins['smash-balloon-instagram'] = array(
				'active'     => defined( 'SBIVER' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/sb-instagram.png',
				'title'      => 'Smash Balloon Instagram Feeds',
				'excerpt'    => __( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'charitable' ),
				'installed'  => array_key_exists( 'instagram-feed/instagram-feed.php', $installed_plugins ),
				'basename'   => 'instagram-feed/instagram-feed.php',
				'slug'       => 'instagram-feed',
				'setup'      => admin_url( 'admin.php?page=sbi-settings' ),
				'settings'   => admin_url( 'admin.php?page=sbi-settings' ),
				'id'         => 'smashballoon',
				'gt_section' => 'engagement',
			);

			// Smash Balloon (Facebook).
			$plugins['smash-balloon-facebook'] = array(
				'active'     => defined( 'CFFVER' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/sb-facebook.png',
				'title'      => 'Smash Balloon Facebook Feeds',
				'excerpt'    => __( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'charitable' ),
				'installed'  => array_key_exists( 'custom-facebook-feed/custom-facebook-feed.php', $installed_plugins ),
				'basename'   => 'custom-facebook-feed/custom-facebook-feed.php',
				'slug'       => 'custom-facebook-feed',
				'setup'      => admin_url( 'admin.php?page=cff-setup' ),
				'settings'   => admin_url( 'admin.php?page=cff-setup' ),
				'id'         => 'smashballoon',
				'gt_section' => 'engagement',
			);

			// Smash Balloon (YouTube).
			$plugins['smash-balloon-youtube'] = array(
				'active'    => defined( 'SBYVER' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/sb-youtube.png',
				'title'     => 'Smash Balloon YouTube Feeds',
				'excerpt'   => __( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'charitable' ),
				'installed' => array_key_exists( 'feeds-for-youtube/youtube-feed.php', $installed_plugins ),
				'basename'  => 'feeds-for-youtube/youtube-feed.php',
				'slug'      => 'feeds-for-youtube',
				'setup'     => admin_url( 'admin.php?page=sby-feed-builder' ),
				'settings'  => admin_url( 'admin.php?page=sby-feed-builder' ),
			);

			// Smash Balloon (Twitter).
			$plugins['smash-balloon-twitter'] = array(
				'active'    => defined( 'CTF_VERSION' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/sb-twitter.png',
				'title'     => 'Smash Balloon Twitter Feeds',
				'excerpt'   => __( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'charitable' ),
				'installed' => array_key_exists( 'custom-twitter-feeds/custom-twitter-feed.php', $installed_plugins ),
				'basename'  => 'custom-twitter-feeds/custom-twitter-feed.php',
				'slug'      => 'custom-twitter-feeds',
				'setup'     => admin_url( 'admin.php?page=ctf-feed-builder' ),
				'settings'  => admin_url( 'admin.php?page=ctf-feed-builder' ),
			);

			// TrustPulse.
			$plugins['trustpulse'] = array(
				'active'    => defined( 'TRUSTPULSE_PLUGIN_VERSION' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/trustpulse.png',
				'title'     => 'TrustPulse',
				'excerpt'   => __( 'Boost your sales and conversions by up to 15% with real-time social proof notifications. TrustPulse helps you show live user activity and purchases to help convince other users to purchase.', 'charitable' ),
				'installed' => array_key_exists( 'trustpulse-api/trustpulse.php', $installed_plugins ),
				'basename'  => 'trustpulse-api/trustpulse.php',
				'slug'      => 'trustpulse-api',
				'setup'     => admin_url( 'admin.php?page=trustpulse' ),
				'settings'  => admin_url( 'admin.php?page=trustpulse' ),
			);

			// SearchWP.
			$plugins['searchwp'] = array(
				'active'     => defined( 'SEARCHWP_LIVE_SEARCH_VERSION' ),
				'icon'       => false,
				'title'      => 'SearchWP',
				'excerpt'    => __( 'The most advanced WordPress search plugin. Customize your WordPress search algorithm, reorder search results, track search metrics, and everything you need to leverage search to grow your business.', 'charitable' ),
				'why'        => __( 'Unlock better search results for your website. Perfect for any information or eCommerce store to help users find exactly what content and products they’re looking for.', 'charitable' ),
				'what'       => __( 'a search plugin', 'charitable' ),
				'installed'  => false,
				'basename'   => false,
				'slug'       => 'searchwp-live-ajax-search',
				'settings'   => admin_url( 'admin.php?page=searchwp-live-search' ),
				'id'         => 'searchwp',
				'gt_section' => 'revenue',
				'gt_learn'   => 'https://www.wpcharitable.com/refer/searchwp?utm_source=charitableplugin&utm_medium=link&utm_campaign=CharitableGrowthTools&utm_content=' . charitable()->get_version(),

			);

			// AffiliateWP.
			$plugins['affiliatewp'] = array(
				'active'    => class_exists( 'AffiliateWP_Requirements_Check' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/affiliate-wp.png',
				'title'     => 'AffiliateWP',
				'excerpt'   => __( 'The #1 affiliate management plugin for WordPress. Easily create an affiliate program for your eCommerce store or membership site within minutes and start growing your sales with the power of referral marketing.', 'charitable' ),
				'installed' => array_key_exists( 'affiliate-wp/affiliate-wp.php', $installed_plugins ),
				'basename'  => 'affiliate-wp/affiliate-wp.php',
				'slug'      => 'affiliate-wp',
				'setup'     => admin_url(),
				'settings'  => admin_url(),
				'redirect'  => 'https://affiliatewp.com',
			);

			// WP Simple Pay.
			$plugins['wpsimplepay'] = array(
				'active'    => defined( 'SIMPLE_PAY_VERSION' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/wp-simple-pay.png',
				'title'     => 'WP Simple Pay',
				'excerpt'   => __( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'charitable' ),
				'installed' => array_key_exists( 'stripe/stripe-checkout.php', $installed_plugins ),
				'basename'  => 'stripe/stripe-checkout.php',
				'slug'      => 'stripe',
				'setup'     => admin_url( 'edit.php?post_type=simple-pay&page=simpay_settings' ),
				'settings'  => admin_url( 'edit.php?post_type=simple-pay&page=simpay_settings' ),
			);

			// Sugar Calendar.
			$plugins['sugarcalendar'] = array(
				'active'    => class_exists( 'Sugar_Calendar\\Requirements_Check' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/sugar-calendar.png',
				'title'     => 'Sugar Calendar',
				'excerpt'   => __( 'A simple & powerful event calendar plugin for WordPress that comes with all the event management features including payments, scheduling, timezones, ticketing, recurring events, and more.', 'charitable' ),
				'installed' => array_key_exists( 'sugar-calendar-lite/sugar-calendar-lite.php', $installed_plugins ),
				'basename'  => 'sugar-calendar-lite/sugar-calendar-lite.php',
				'slug'      => 'sugar-calendar-lite',
				'settings'  => admin_url( 'admin.php?page=sugar-calendar' ),
			);

			// WPCode.
			$plugins['wpcode'] = array(
				'active'    => function_exists( 'WPCode' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/wpcode.png',
				'title'     => 'WPCode',
				'excerpt'   => __( 'Future proof your WordPress customizations with the most popular code snippet management plugin for WordPress. Trusted by over 1,500,000+ websites for easily adding code to WordPress right from the admin area.', 'charitable' ),
				'installed' => array_key_exists( 'insert-headers-and-footers/ihaf.php', $installed_plugins ),
				'basename'  => 'insert-headers-and-footers/ihaf.php',
				'slug'      => 'insert-headers-and-footers',
				'settings'  => admin_url( 'admin.php?page=wpcode-settings' ),
				'setup'     => admin_url( 'admin.php?page=wpcode-settings' ),
			);

			// Duplicator.
			$plugins['duplicator'] = array(
				'active'    => defined( 'DUPLICATOR_VERSION' ),
				'icon'      => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/duplicator.png',
				'title'     => 'Duplicator',
				'excerpt'   => __( 'Leading WordPress backup & site migration plugin. Over 1,500,000+ smart website owners use Duplicator to make reliable and secure WordPress backups to protect their websites. It also makes website migration really easy.', 'charitable' ),
				'installed' => array_key_exists( 'duplicator/duplicator.php', $installed_plugins ),
				'basename'  => 'duplicator/duplicator.php',
				'slug'      => 'duplicator',
				'settings'  => admin_url( 'admin.php?page=duplicator-settings' ),
			);

			// PushEngage.
			$plugins['pushengage'] = array(
				'active'     => defined( 'PUSHENGAGE_VERSION' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/pushengage.png',
				'title'      => 'PushEngage',
				'excerpt'    => __( 'Connect with your visitors after they leave your website with the leading web push notification software. Over 10,000+ businesses worldwide use PushEngage to send 15 billion notifications each month.', 'charitable' ),
				'why'        => __( 'Use push notifications to notify donors or potential donors that a campaign is about to end, or that a campaign has not reached it\'s goal yet.', 'charitable' ),
				'what'       => __( 'particular marketing plugins', 'charitable' ),
				'installed'  => array_key_exists( 'pushengage/main.php', $installed_plugins ),
				'basename'   => 'pushengage/main.php',
				'slug'       => 'pushengage',
				'setup'      => admin_url( 'admin.php?page=pushengage#/settings/site-details' ),
				'settings'   => admin_url( 'admin.php?page=pushengage#/settings/site-details' ),
				'id'         => 'pushengage',
				'gt_section' => 'traffic',
			);

			// Uncanny Automator.
			$plugins['uncanny-automator'] = array(
				'active'         => function_exists( 'automator_get_recipe_id' ),
				'icon'           => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/uncanny-automator.png',
				'title'          => 'Uncanny Automator',
				'excerpt'        => __( 'Automate everything with the #1 no-code Automation tool for WordPress. Use data from donors, donation submissions, activities and anything else in your campaign.', 'charitable' ),
				'installed'      => array_key_exists( 'uncanny-automator/uncanny-automator.php', $installed_plugins ),
				'basename'       => 'uncanny-automator/uncanny-automator.php',
				'slug'           => 'uncanny-automator',
				'setup_complete' => (bool) get_option( 'automator_reporting', false ),
				'setup'          => admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-setup-wizard' ),
				'settings'       => admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-setup-wizard' ),
				'id'             => 'uncanny-automator',
				'gt_section'     => 'engagement',
			);

			// Envira Gallery.
			$plugins['envira-gallery'] = array(
				'active'     => class_exists( 'Envira_Gallery_Lite' ),
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/envira-gallery.png',
				'title'      => 'Envira Gallery Lite',
				'excerpt'    => __( 'Add a photo gallery to your campaign pages with this fast and powerful gallery plugin.', 'charitable' ),
				'why'        => __( 'Showing photos in an attractive gallery will add more credentiality to your campaigns.', 'charitable' ),
				'installed'  => array_key_exists( 'envira-gallery-lite/envira-gallery-lite.php', $installed_plugins ),
				'basename'   => 'envira-gallery-lite/envira-gallery-lite.php',
				'slug'       => 'envira-gallery-lite',
				'setup'      => admin_url( 'edit.php?post_type=envira&page=envira-gallery-lite-get-started' ),
				'id'         => 'envira',
				'gt_section' => 'engagement',
			);

			// Constant Contact.
			$plugins['constant-contact'] = array(
				'active'     => class_exists( 'ConstantContact' ), // todo: update.
				'icon'       => charitable()->get_path( 'directory', false ) . 'assets/images/plugins/third-party/constant-contact.png',
				'title'      => 'Constant Contact',
				'excerpt'    => __( 'Create amazing email marketing campaigns with drag and drop simplicity. <span>Exclusive Offer: Save 20%.</span>', 'charitable' ),
				'why'        => false,
				'slug'       => 'constantcontact',
				'setup'      => false,
				'id'         => 'constantcontact',
				'gt_section' => 'traffic',
				'gt_learn'   => 'https://www.wpcharitable.com/refer/constant-contact/?utm_source=charitableplugin&utm_medium=link&utm_campaign=CharitableGrowthTools&utm_content=' . charitable()->get_version(),
			);

			// SEMRUSH.
			$plugins['semrush'] = array(
				'active'     => class_exists( 'SEMRUSH' ), // todo: update.
				'icon'       => false,
				'title'      => 'SEMRUSH',
				'excerpt'    => __( 'Perform SEO and content marketing research, track keywords, and much more. <span>Special Offer: First 14 Days Free.</span>', 'charitable' ),
				'why'        => false,
				'slug'       => 'semrush',
				'setup'      => false,
				'id'         => 'semrush',
				'gt_section' => 'traffic',
				'gt_learn'   => 'https://www.wpcharitable.com/refer/semrush?utm_source=charitableplugin&utm_medium=link&utm_campaign=CharitableGrowthTools&utm_content=' . charitable()->get_version(),
			);

			// MemberPress.
			$plugins['memberpress'] = array(
				'active'     => false,
				'icon'       => false,
				'title'      => 'MemberPress',
				'excerpt'    => __( 'Create a membership website.', 'charitable' ),
				'installed'  => false,
				'basename'   => false,
				'why'        => __( 'Creating a community can help boost returning donations. You can offer exclusive content and discussion, especially for donors or campaign creators.', 'charitable' ),
				'what'       => __( 'a membership plugin', 'charitable' ),
				'slug'       => 'memberpress',
				'setup'      => false,
				'id'         => 'memberpress',
				'gt_section' => 'revenue',
				'gt_learn'   => 'https://www.wpcharitable.com/refer/memberpress/?utm_source=charitableplugin&utm_medium=link&utm_campaign=CharitableGrowthTools&utm_content=' . charitable()->get_version(),
			);

			// Easy Affiliate.
			$plugins['easy-affiliate'] = array(
				'active'     => false,
				'icon'       => false,
				'title'      => 'Easy Affiliate',
				'excerpt'    => __( 'Launch, grow, and manage an affiliate program, all right from your WordPress dashboard.', 'charitable' ),
				'installed'  => false,
				'basename'   => false,
				'slug'       => 'easy-affiliate',
				'setup'      => false,
				'id'         => 'easyaffiliate',
				'gt_section' => 'revenue',
				'gt_learn'   => 'https://www.wpcharitable.com/refer/easy-affiliate/?utm_source=charitableplugin&utm_medium=link&utm_campaign=CharitableGrowthTools&utm_content=' . charitable()->get_version(),
			);

			// Formidable Forms.
			$plugins['formidable'] = array(
				'active'     => function_exists( 'load_formidable_forms' ),
				'icon'       => false,
				'title'      => 'Formidable Forms',
				'excerpt'    => __( 'Formidable Forms is the best WordPress forms plugin. Over 300,000 professionals use our WordPress form builder to create contact forms, surveys, calculators, and other WP forms.', 'charitable' ),
				'why'        => __( 'Form plugins can help you get real feedback such as campaign recommendations or ideas for different payment options.', 'charitable' ),
				'what'       => __( 'a form plugin', 'charitable' ),
				'installed'  => array_key_exists( 'formidable/formidable.php', $installed_plugins ),
				'basename'   => 'formidable/formidable.php',
				'slug'       => 'formidable',
				'settings'   => admin_url( 'admin.php?page=formidable-dashboard' ),
				'setup'      => admin_url( 'admin.php?page=formidable-dashboard' ),
				'id'         => 'formidableforms',
				'gt_section' => 'engagement',
			);

			// WPConsent.
			$plugins['wpconsent'] = array(
				'active'    => function_exists( 'wpconsent' ),
				'icon'      => false,
				'title'     => 'WPConsent',
				'excerpt'   => __( 'WPConsent is the best privacy compliance plugin for WordPress. It helps you comply with GDPR, CCPA, and other privacy regulations.', 'charitable' ),
				'why'       => __( 'Privacy compliance plugins can help you comply with GDPR, CCPA, and other privacy regulations.', 'charitable' ),
				'what'      => __( 'a privacy compliance plugin', 'charitable' ),
				'installed' => array_key_exists( 'wpconsent-cookies-banner-privacy-suite/wpconsent.php', $installed_plugins ),
				'basename'  => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
				'slug'      => 'wpconsent-cookies-banner-privacy-suite',
				'settings'  => admin_url( 'admin.php?page=wpconsent-cookies' ),
				'setup'     => admin_url( 'admin.php?page=wpconsent-cookies' ),
				'id'        => 'wpconsent',
				'gt_section' => 'privacy',
				'gt_learn'  => 'https://wordpress.org/plugins/wpconsent-cookies-banner-privacy-suite/',
			);

			$plugins['guide-coming-soon'] = array(
				'title'       => __( 'Dedicated guides and resources will be coming soon.', 'charitable' ),
				'id'          => 'guide',
				'gt_section'  => 'guides',
				'coming_soon' => true,
			);

			if ( $return_json ) {
				wp_send_json( $plugins );
			} else {
				return $plugins;
			}
		}

		/**
		 * Get the recommended plugins.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $recommendation_type The type of recommendation to get.
		 * @param  int    $limit               The number of recommendations to get.
		 *
		 * @return array
		 */
		public function get_recommendations( $recommendation_type = 'campaign', $limit = 3 ) {

			$recommended_addons   = $this->get_recommended_addons( $recommendation_type, $limit );
			$recommended_partners = $this->get_recommended_partners( $recommendation_type, $limit );

			// merge the two arrays.
			$recommended = array_merge( $recommended_addons, $recommended_partners );

			if ( count( $recommended_addons ) + count( $recommended_partners ) > $limit ) {
				$recommended = array_merge( $recommended_addons, $recommended_partners );

				if ( ! empty( $recommended ) ) {
					// randomly remove elements from the array until the total count of the array equals the limit.
					$recommended_count = count( $recommended );
					while ( $recommended_count > $limit ) {
						$random_key = array_rand( $recommended );
						unset( $recommended[ $random_key ] );
						$recommended_count = count( $recommended );
					}
				}
			}

			return $recommended;
		}


		/**
		 * Get the recommended partners / third party items.
		 *
		 * @since  1.8.1.5
		 *
		 * @param  string $recommendation_type The type of recommendation to get.
		 * @param  int    $limit               The number of recommendations to get.
		 *
		 * @return array
		 */
		public function get_recommended_partners( $recommendation_type = 'campaign', $limit = 3 ) {
			/*
			Recommend at least one Charitable addon.

			Rules:
			- Must be on the list of preferred partners.
			- Must not be installed.
			- Must not be dismissed.

			*/

			$partner_list = $this->get_plugins( false );

			$preferred_partners = apply_filters(
				'charitable_recommended_partners_ ' . $recommendation_type, // @codingStandardsIgnoreLine
				array(
					'charitable',
					'optinmonster',
					'wpforms-lite',
				)
			);

			// Remove any installed plugins from the preferred list.
			$installed_plugins = get_plugins();

			foreach ( $installed_plugins as $file => $plugin ) {
				// Get the first part of the string before the /.
				$plugin_slug = explode( '/', $file )[0];

				if ( in_array( $plugin_slug, $preferred_partners, true ) ) {
					unset( $preferred_partners[ array_search( $plugin_slug, $preferred_partners, true ) ] );
				}

				if ( ! empty( $preferred_partners ) ) {
					foreach ( $partner_list as $partner_list_slug => $partner_list_info ) {
						if ( ! empty( $partner_list_info ) && isset( $partner_list_info['slug'] ) && $partner_list_info['slug'] === $plugin_slug ) {
							unset( $preferred_partners[ array_search( $plugin_slug, $preferred_partners, true ) ] );
						}
					}
				}
			}
			// Remove any plugins already dismissed from the logged in user.
			$dismissed_partners = (array) get_user_meta( get_current_user_id(), 'charitable_dismissed_partners', true );
			$dismissed_partners = array_filter( $dismissed_partners );

			if ( ! empty( $dismissed_partners ) ) {
				foreach ( $dismissed_partners as $dismissed_partner ) {
					if ( '' === $dismissed_partner ) {
						continue;
					}
					if ( in_array( $dismissed_partner, $dismissed_partners, true ) ) {
						unset( $preferred_partners[ array_search( $dismissed_partner, $preferred_partners, true ) ] );
					}
				}
			}

			// Limit the number of addons to return.
			$preferred_partners = array_slice( $preferred_partners, 0, $limit );

			// For each remaining item in $preferred_partners, get the plugin details from $partner_list.
			foreach ( $preferred_partners as $key => $partner ) {
				$preferred_partners[ $partner ]         = ! empty( $partner_list[ $partner ] ) ? $partner_list[ $partner ] : array();
				$preferred_partners[ $partner ]['type'] = 'partner';
				unset( $preferred_partners[ $key ] );
			}

			return $preferred_partners;
		}

		/**
		 * Get the recommended addons.
		 *
		 * @since  1.8.1.5
		 *
		 * @param  string $recommendation_type The type of recommendation to get.
		 * @param  int    $limit               The number of recommendations to get.
		 *
		 * @return array
		 */
		public function get_recommended_addons( $recommendation_type = 'campaign', $limit = 3 ) {
			/*
			Recommend at least one Charitable addon.

			Rules:
			- Must be a Charitable addon.
			- Must not be installed.
			- Must not be dismissed.

			*/

			$preferred_addons = apply_filters(
				'charitable_recommended_addons_ ' . $recommendation_type, // @codingStandardsIgnoreLine
				array(
					'charitable-recurring',
					'charitable-ambassadors',
					'charitable-gift-aid',
					'charitable-anonymous-donations',
					'charitable-fee-relief',
				)
			);

			// Remove any installed Charitable addons from the preferred list.
			$installed_plugins = get_plugins();

			foreach ( $installed_plugins as $file => $plugin ) {
				// Get the first part of the string before the /.
				$plugin_slug = explode( '/', $file )[0];

				if ( in_array( $plugin_slug, $preferred_addons, true ) ) {
					unset( $preferred_addons[ array_search( $plugin_slug, $preferred_addons, true ) ] );
				}
			}

			// Remove any addons already dismissed from the logged in user.
			$dismissed_addons = (array) get_user_meta( get_current_user_id(), 'charitable_dismissed_addons', true );
			$dismissed_addons = array_filter( $dismissed_addons );

			if ( ! empty( $dismissed_addons ) ) {
				foreach ( $dismissed_addons as $dismissed_addon ) {
					if ( in_array( $dismissed_addon, $preferred_addons, true ) ) {
						unset( $preferred_addons[ array_search( $dismissed_addon, $preferred_addons, true ) ] );
					}
				}
			}

			// Limit the number of addons to return.
			$preferred_addons_to_compare = array_slice( $preferred_addons, 0, $limit );

			// check and see if the slug refers to a Charitable addon first.
			$addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $addons ) {
				$addons = charitable_get_addons_data_from_server();
			}

			$preferred_addons = array();

			if ( ! empty( $addons ) ) {

				foreach ( $addons as $addon ) {

					if ( ! isset( $addon['slug'] ) || empty( $addon['slug'] ) ) {
						continue;
					}

					if ( in_array( $addon['slug'], $preferred_addons_to_compare, true ) ) {

						$sections = ! empty( $addon['sections'] ) ? unserialize( $addon['sections'] ) : array();

						$preferred_addons[ $addon['slug'] ]['icon']        = charitable()->get_path( 'directory', false ) . 'assets/images/plugins/charitable/' . $addon['slug'] . '.png';
						$preferred_addons[ $addon['slug'] ]['title']       = trim( str_replace( 'Charitable', '', $addon['name'] ) );
						$preferred_addons[ $addon['slug'] ]['sections']    = array();
						$preferred_addons[ $addon['slug'] ]['description'] = isset( $sections['description'] ) ? wp_strip_all_tags( $sections['description'] ) : '';
						$preferred_addons[ $addon['slug'] ]['type']        = 'addon';

					}
				}

			}

			return $preferred_addons;
		}

		/**
		 * Get a single plugin.
		 *
		 * @since  1.8.1.5
		 *
		 * @param  string $slug The plugin slug.
		 *
		 * @return array|null
		 */
		public function get_plugin( $slug ) {
			$plugins = $this->get_plugins( false );

			if ( ! isset( $plugins[ $slug ] ) ) {
				return;
			}

			return $plugins[ $slug ];
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Admin_Plugins_Third_Party
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
