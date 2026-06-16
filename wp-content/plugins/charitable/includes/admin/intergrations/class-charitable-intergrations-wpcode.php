<?php
/**
 * Charitable Intergration For WPCode.
 *
 * @package   Charitable/Classes/Class_Intergrations_WPCode
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 * @version   1.8.1.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Intergrations_WPCode' ) ) :

	/**
	 * Charitable_Tools
	 *
	 * @final
	 * @since 1.8.1.6
	 */
	class Charitable_Intergrations_WPCode {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.8.1.6
		 *
		 * @var    Charitable_Tools_Settings|null
		 */
		private static $instance = null;

		/**
		 * WPCode lite download URL.
		 *
		 * @since 1.8.1.6
		 *
		 * @var string
		 */
		public $lite_download_url = 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip';

		/**
		 * Lite plugin slug.
		 *
		 * @since 1.8.1.6
		 *
		 * @var string
		 */
		public $lite_plugin_slug = 'insert-headers-and-footers/ihaf.php';

		/**
		 * WPCode lite download URL.
		 *
		 * @since 1.8.1.6
		 *
		 * @var string
		 */
		public $pro_plugin_slug = 'wpcode-premium/wpcode.php';

		/**
		 * Load the class.
		 *
		 * @since 1.8.1.6
		 */
		public function load() {

			$this->hooks();
		}

		/**
		 * Hooks.
		 *
		 * @since 1.8.1.6
		 */
		private function hooks() {
		}

		/**
		 * Load the WPCode snippets for our desired username or return an empty array if not available.
		 *
		 * @since 1.8.1.6
		 *
		 * @param  int|bool $limit         The number of snippets to load.
		 * @param  bool     $show_installed Whether to show installed snippets.
		 *
		 * @return array The snippets.
		 */
		public function load_charitable_snippets( $limit = false, $show_installed = true ): array { // phpcs:ignore

			$snippets = $this->get_placeholder_snippets();

			if ( function_exists( 'wpcode_get_library_snippets_by_username' ) ) {
				$snippets = wpcode_get_library_snippets_by_username( 'wpcharitable' );
			}

			// Sort by installed.
			uasort(
				$snippets,
				function ( $a, $b ) {
					return ( $b['installed'] <=> $a['installed'] );
				}
			);

			// If there's a limit, slice the array.
			if ( $limit ) {
				$snippets = array_slice( $snippets, 0, $limit );
			}

			return $snippets;
		}

		/**
		 * Checks if the plugin is installed, either the lite or premium version.
		 *
		 * @since 1.8.1.6
		 *
		 * @return bool True if the plugin is installed.
		 */
		public function is_plugin_installed(): bool {

			return $this->is_pro_installed() || $this->is_lite_installed();
		}

		/**
		 * Is the pro plugin installed.
		 *
		 * @since 1.8.1.6
		 *
		 * @return bool True if the pro plugin is installed.
		 */
		public function is_pro_installed(): bool {

			return array_key_exists( $this->pro_plugin_slug, get_plugins() );
		}

		/**
		 * Is the lite plugin installed.
		 *
		 * @since 1.8.1.6
		 *
		 * @return bool True if the lite plugin is installed.
		 */
		public function is_lite_installed(): bool {

			return array_key_exists( $this->lite_plugin_slug, get_plugins() );
		}

		/**
		 * Basic check if the plugin is active by looking for the main function.
		 *
		 * @since 1.8.1.6
		 *
		 * @return bool True if the plugin is active.
		 */
		public function is_plugin_active(): bool {

			return function_exists( 'wpcode' );
		}

		/**
		 * Get plugin version.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		public function plugin_version(): string {

			if ( $this->is_pro_installed() ) {
				return get_plugins()[ $this->pro_plugin_slug ]['Version'];
			}

			if ( $this->is_lite_installed() ) {
				return get_plugins()[ $this->lite_plugin_slug ]['Version'];
			}

			return '';
		}

		/**
		 * Get placeholder snippets if the WPCode snippets are not available.
		 *
		 * @since 1.8.1.6
		 *
		 * @return array The placeholder snippets.
		 */
		private function get_placeholder_snippets(): array {

			$snippet_titles = [
				'Redirect To Referer After Login',
				'Add Custom Field to Donation Form',
				'Remove Donation Form Fields',
				'Set Custom Redirection After Registration',
				'Change Custom Amount Field Label',
				'Collect National ID Number',
				'Add Field Placeholders',
				'Remove Campaign Description',
				'Change Country Field To Hidden',
				'Add Donation Form Shortcode',
				'Add Checkbox To Donation Form',
				'Make Donor Address Required',
				'Use Page Template For Campaigns',
				'Add Accept Terms Field',
				'Remove Section',
				'Set Custom Donation Receipt Page',
			];

			$placeholder_snippets = [];

			foreach ( $snippet_titles as $snippet_title ) {

				// Add placeholder install link so we show a button.
				$placeholder_snippets[] = [
					'title'     => $snippet_title,
					'install'   => 'https://library.wpcode.com/',
					'installed' => false,
					'note'      => 'Placeholder code snippet short description text.',
				];
			}

			return $placeholder_snippets;
		}

		/**
		 * Enqueue assets.
		 *
		 * @since   1.8.1.6
		 */
		public function enqueue_scripts() {

			if ( ! class_exists( 'Charitable' ) ) {
				return;
			}

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( ( ! empty( $_GET['page'] ) && 'charitable-dashboard' === $_GET['page'] ) || ( ! is_null( $screen ) && 'charitable_page_charitable-tools' === $screen->base ) || ( ! empty( $_GET['tab'] ) && 'snippets' === $_GET['tab'] ) ) { // phpcs:ignore

				wp_enqueue_script(
					'listjs',
					$assets_dir . 'js/libraries/list.min.js',
					[ 'jquery' ],
					$version,
					false
				);

				wp_enqueue_script(
					'charitable-wpcode',
					$assets_dir . "js/integrations/wpcode/wpcode{$min}.js",
					[ 'jquery', 'listjs' ],
					$version,
					true
				);

				wp_localize_script(
					'charitable-wpcode',
					'charitableWpcodeVars',
					[
						'installing_text' => __( 'Installing', 'charitable' ),
					]
				);

				wp_register_style(
					'charitable-integrations-wpcode',
					$assets_dir . 'css/integrations/wpcode' . $min . '.css',
					array(),
					$version
				);

				wp_enqueue_style( 'charitable-integrations-wpcode' );

			}
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.8.1.6
		 *
		 * @return  Charitable_Tools_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
