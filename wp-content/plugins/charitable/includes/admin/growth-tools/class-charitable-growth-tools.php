<?php
/**
 * Charitable Growth Tools.
 *
 * @package   Charitable/Classes/Charitable_Guide_Tools
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

if ( ! class_exists( 'Charitable_Guide_Tools' ) ) :

	/**
	 * Charitable_Guide_Tools
	 *
	 * @final
	 * @since 1.8.1.6
	 */
	class Charitable_Guide_Tools {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Guide_Tools|null
		 */
		private static $instance = null;


		/**
		 * The installed plugins.
		 *
		 * @var array
		 */
		public $installed_plugins = array();

		/**
		 * The growth tools.
		 *
		 * @var array
		 */
		public $growth_tools = array();

		/**
		 * Create object instance.
		 *
		 * @since 1.8.1.6
		 */
		public function __construct() {
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1.6
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * Get the tools, grabbing the information actually from the Charitable_Admin_Plugins_Third_Party library.
		 *
		 * @since 1.8.1.6
		 *
		 * @return array
		 */
		public function get_growth_tools() {

			if ( empty( $this->growth_tools ) ) {
				$growth_tools       = array();
				$growth_tools       = Charitable_Admin_Plugins_Third_Party::get_instance()->get_plugins( false );
				$this->growth_tools = $growth_tools;
			}

			return apply_filters( 'charitable_growth_tools', $this->growth_tools );
		}

		/**
		 * Get the installed plugins.
		 *
		 * @since 1.8.1.6
		 *
		 * @return array
		 */
		public function get_installed_plugins() {

			if ( empty( $this->installed_plugins ) ) {
				$installed_plugins       = array();
				$installed_plugins       = get_plugins();
				$this->installed_plugins = $installed_plugins;
			}

			return $this->installed_plugins;
		}

		/**
		 * Enqueue assets.
		 *
		 * @since   1.8.1.6
		 */
		public function enqueue_scripts() {

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( ( ! empty( $_GET['page'] ) && 'charitable-growth-tools' === $_GET['page'] ) ) { // phpcs:ignore

				wp_enqueue_style(
					'charitable-growth-tools',
					$assets_dir . 'css/growth-tools/growth-tools' . $min . '.css',
					array(),
					$version
				);

			}
		}

		/**
		 * Get a single guide tool suggestion. This is what is called usually by another outside function.
		 *
		 * @param string $type The type of suggestion to get.
		 * @param bool   $has_donations Whether the site has donations or not.
		 *
		 * @since 1.8.1.6
		 *
		 * @return array
		 */
		public function get_suggestion( $type = false, $has_donations = true ) {

			$suggestions = $this->generate_suggestions( $type );

			// get unique keys from $suggestions and pick a random one.
			$plugin_categories = array_keys( $suggestions );
			$plugin_category   = $plugin_categories[ array_rand( $plugin_categories ) ];

			// get a random array element in the $suggestions array with the key of $plugin_category.
			$plugin_suggestions = (array) $suggestions[ $plugin_category ];
			if ( count( $plugin_suggestions ) > 1 ) {
				$plugin_suggestion = $plugin_suggestions[ array_rand( $plugin_suggestions ) ];
			} else {
				$plugin_suggestion = $plugin_suggestions[0];
			}

			// get headline - if there has never been a donation, then offer a different text.
			if ( $has_donations ) {
				$plugin_suggestion['headline'] = esc_html__( 'Ready to grow your donations? Let Charitable help!', 'charitable' ) . ' 🚀';
			} else {
				$plugin_suggestion['headline'] = esc_html__( 'Excited to make your first donation? Let Charitable help!', 'charitable' ) . ' 🚀';
			}

			return $plugin_suggestion;
		}


		/**
		 * Generate a list of possible guide tool suggestions, seperated by category.
		 *
		 * @since 1.8.1.6.
		 *
		 * @param string $type The type of suggestion to generate.
		 *
		 * @return array
		 */
		public function generate_suggestions( $type = false ) {

			$suggestions  = array();
			$growth_tools = $this->get_growth_tools();

			if ( ! $type ) {
				return false;
			}

			// limit tool section to those assigned to a guide tools section.
			$growth_tools = array_filter(
				$growth_tools,
				function ( $tool ) {
					return isset( $tool['gt_section'] );
				}
			);

			// get the list of installed plugins.
			$installed_plugins = $this->get_installed_plugins();

			// determine what to suggest based on what plugin they might not have installed.

			// form plugins.
			if ( ! $this->does_have_form_plugins( $installed_plugins ) ) {
				$suggestions['form'] = $this->get_plugin_suggestion( 'form' );
			}
			if ( ! $this->does_have_seo_plugins( $installed_plugins ) ) {
				$suggestions['seo'] = $this->get_plugin_suggestion( 'seo' );
			}
			if ( ! $this->does_have_email_plugins( $installed_plugins ) ) {
				$suggestions['email'] = $this->get_plugin_suggestion( 'email' );
			}
			if ( ! $this->does_have_page_builder_plugins( $installed_plugins ) ) {
				$suggestions['email'] = $this->get_plugin_suggestion( 'page-builder' );
			}

			$suggestions['marketing'] = $this->get_plugin_suggestion( 'marketing' );

			return $suggestions;
		}

		/**
		 * Get a suggestion for a particular plugin.
		 *
		 * @since 1.8.1.6
		 *
		 * @param string $suggestion_type The type of suggestion to get.
		 *
		 * @return array
		 */
		public function get_plugin_suggestion( $suggestion_type = 'form' ) {
			$suggestions = array();

			switch ( $suggestion_type ) {
				case 'form':
					$recommendations = array( 'wpforms', 'formidable' );
					$plugin_list     = array( 'tablepress', 'custom-post-type-ui', 'query-monitor', 'wpdatatables' );
					$random_number   = wp_rand( 1, 100 );

					$has_plugin_list = false;
					foreach ( $plugin_list as $plugin ) {
						if ( isset( $this->installed_plugins[ $plugin ] ) ) {
							$has_plugin_list = true;
							break;
						}
					}
					if ( $has_plugin_list ) {
						// get random number.
						$slug = $random_number > 75 ? 'formidable' : 'wpforms-lite';
					} else {
						$slug = $random_number > 50 ? 'formidable' : 'wpforms-lite';
					}

					$suggestions[] = $this->get_suggestion_from_growth_tools( $slug );
					break;

				case 'seo':
					$suggestions[] = $this->get_suggestion_from_growth_tools( 'aioseo' );
					break;
				case 'email':
					$suggestions[] = $this->get_suggestion_from_growth_tools( 'wp-mail-smtp' );
					break;
				case 'marketing':
					$suggestions[] = $this->get_suggestion_from_growth_tools( 'rafflepress' );
					$suggestions[] = $this->get_suggestion_from_growth_tools( 'pushengage' );
					$suggestions[] = $this->get_suggestion_from_growth_tools( 'optinmonster' );
					break;
				case 'page-builder':
					$suggestions[] = $this->get_suggestion_from_growth_tools( 'coming-soon' );
					break;
			}

			return $suggestions;
		}

		/**
		 * Get a suggestion title, content, etc. from the guide tools.
		 *
		 * @param string $slug The slug of the guide tool to get.
		 *
		 * @since 1.8.1.6
		 *
		 * @return array
		 */
		public function get_suggestion_from_growth_tools( $slug = false ) {
			if ( ! $slug ) {
				return false;
			}

			if ( empty( $this->growth_tools[ $slug ]['title'] ) || empty( $this->growth_tools[ $slug ]['excerpt'] ) || empty( $this->growth_tools[ $slug ]['why'] ) ) {
				return false;
			}

			$tool = array(
				'name'        => isset( $this->growth_tools[ $slug ]['title'] ) ? esc_html( $this->growth_tools[ $slug ]['title'] ) : false,
				'description' => isset( $this->growth_tools[ $slug ]['excerpt'] ) ? esc_html( $this->growth_tools[ $slug ]['excerpt'] ) : false,
				'tip'         => isset( $this->growth_tools[ $slug ]['why'] ) ? esc_html( $this->growth_tools[ $slug ]['why'] ) : false,
				'id'          => $slug,
				'content'     => $this->generate_content( $slug ),
			);

			return $tool;
		}

		/**
		 * Generate the content for a particular guide tool.
		 *
		 * @param string $slug The slug of the guide tool to generate content for.
		 *
		 * @since 1.8.1.6
		 *
		 * @return string
		 */
		public function generate_content( $slug ) {

			$growth_tools = $this->get_growth_tools();

			// translators: Placeholder for the missing plugin name.
			$content = '<p>' . sprintf( __( '%1$s We suggest %2$s.', 'charitable' ), ! empty( $this->growth_tools[ $slug ]['what'] ) ? 'We noticed you may not have ' . esc_html( $this->growth_tools[ $slug ]['what'] ) . '.' : false, '<a target="_blank" href="' . admin_url( 'admin.php?page=charitable-growth-tools#' . $this->growth_tools[ $slug ]['id'] ) . '" class="tool-suggestion">' . $this->growth_tools[ $slug ]['title'] . '</a>' ) . '</p>';

			return $content;
		}

		/**
		 * Check if the site has a form plugin installed.
		 *
		 * @since 1.8.1.6
		 *
		 * @param array $installed_plugins The list of installed plugins.
		 *
		 * @return boolean
		 */
		public function does_have_form_plugins( $installed_plugins = array() ) {

			if ( empty( $installed_plugins ) ) {
				$installed_plugins = get_plugins();
			}

			if ( empty( $installed_plugins ) ) {
				return false;
			}

			$form_plugins = array(
				'contact-form-7' => 'Contact Form 7',
				'ninja-forms'    => 'Ninja Forms',
				'gravity-forms'  => 'Gravity Forms',
			);

			foreach ( $form_plugins as $slug => $name ) {
				if ( isset( $installed_plugins[ $slug ] ) ) {
					return true;
				}
			}

			return false;
		}


		/**
		 * Check if the site has a known SEO plugin installed.
		 *
		 * @since 1.8.1.6
		 *
		 * @param array $installed_plugins The list of installed plugins.
		 *
		 * @return boolean
		 */
		public function does_have_seo_plugins( $installed_plugins = array() ) {

			if ( empty( $installed_plugins ) ) {
				$installed_plugins = get_plugins();
			}

			if ( empty( $installed_plugins ) ) {
				return false;
			}

			$seo_plugins = array(
				'yoast' => 'Yoast SEO',
			);

			foreach ( $seo_plugins as $slug => $name ) {
				if ( isset( $installed_plugins[ $slug ] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if the site has a known SEO plugin installed.
		 *
		 * @since 1.8.1.6
		 *
		 * @param array $installed_plugins The list of installed plugins.
		 *
		 * @return boolean
		 */
		public function does_have_email_plugins( $installed_plugins = array() ) {

			if ( empty( $installed_plugins ) ) {
				$installed_plugins = get_plugins();
			}

			if ( empty( $installed_plugins ) ) {
				return false;
			}

			$email_plugins = array(
				'wp-mail-smtp' => 'WP Mail SMTP',
				'smtp-mailer'  => 'SMTP Mailer',
			);

			foreach ( $email_plugins as $slug => $name ) {
				if ( isset( $installed_plugins[ $slug ] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if the site has a known page builder plugin installed.
		 *
		 * @since 1.8.1.6
		 *
		 * @param array $installed_plugins The list of installed plugins.
		 *
		 * @return boolean
		 */
		public function does_have_page_builder_plugins( $installed_plugins = array() ) {

			if ( empty( $installed_plugins ) ) {
				$installed_plugins = get_plugins();
			}

			if ( empty( $installed_plugins ) ) {
				return false;
			}

			$plugin_list = array( '', '', '' );

			$email_plugins = array(
				'elementor'                   => 'Elementor',
				'beaver-builder-lite-version' => 'Beaver Builder',
				'wpbakery'                    => 'WPBakery',
			);

			foreach ( $email_plugins as $slug => $name ) {
				if ( isset( $installed_plugins[ $slug ] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get the HTML for the dashboard notice.
		 *
		 * @since 1.8.1.6
		 *
		 * @param array  $suggestion                    The suggestion to display.
		 * @param string $show_gt_chart_notice_headline The headline to display.
		 *
		 * @return string
		 */
		public function get_dashboard_notice_html( $suggestion = array(), $show_gt_chart_notice_headline = '' ) {

			if ( empty( $suggestion ) ) {
				return false;
			}

			ob_start();

			?>

				<?php if ( ! empty( $show_gt_chart_notice_headline ) ) : ?>
				<h2 class=""><?php echo $show_gt_chart_notice_headline; // phpcs:ignore ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $suggestion['content'] ) ) : ?>
					<?php echo $suggestion['content']; // phpcs:ignore ?>
				<?php endif; ?>

				<?php if ( ! empty( $suggestion['tip'] ) ) : ?>
					<p class="charitable-notice-why"><strong><?php echo esc_html__( 'Tip:', 'charitable' ); ?></strong><br/><?php echo esc_html( $suggestion['tip'] ); ?></p>
				<?php endif; ?>

				<p class="more-recommendations"><?php echo esc_html__( 'More recommendations:', 'charitable' ); ?> <a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-growth-tools' ) ); ?>"><?php echo esc_html__( 'Growth Tools', 'charitable' ); ?></a> - <a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-addons' ) ); ?>"><?php echo esc_html__( 'Charitable Addons', 'charitable' ); ?></a></p>
				<p class="charitable-dashboard-notice-another-suggestion"><a href="#"><strong><?php echo esc_html__( 'Get another suggestion.', 'charitable' ); ?></a></p>

				<a href="#" class="charitable-remove-growth-tools" title="<?php echo esc_html__( 'Close this notice.', 'charitable' ); ?>"></a>

			<?php

			$notice_html = ob_get_clean();

			return $notice_html;
		}

		/**
		 * Get the HTML for the dashboard notice via AJAX.
		 *
		 * @since 1.8.1.6
		 *
		 * @return void
		 */
		public function ajax_get_dashboard_notice_html() {

			// check for ajax.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'charitable-admin' ) ) { // phpcs:ignore
				wp_send_json_error( esc_html__( 'No suggestions are available at this time.', 'charitable' ) );
			}

			$suggestion = ! empty( $_POST['suggestion'] ) ? esc_html( $_POST['suggestion'] ) : false; // phpcs:ignore
			$headline   = ! empty( $_POST['headline'] ) ? esc_html( $_POST['headline'] ) : false; // phpcs:ignore
			$html       = false;

			if ( empty( $suggestion ) || empty( $headline ) ) {

				$total_donations_array = (array) wp_count_posts( 'donation' ); // the function caches this, so we shouldn't have to.
				$total_donations       = ! empty( $total_donations_array ) ? array_sum( $total_donations_array ) : 0;
				$headline              = ( false !== $total_donations_array && is_array( $total_donations_array ) && $total_donations > 0 ) ? esc_html__( 'No donations recently? Let Charitable help!', 'charitable' ) . ' 🚀' : esc_html__( 'Excited to make your first donation? Let Charitable help!', 'charitable' ) . ' 🚀';
				$suggestion            = self::get_instance()->get_suggestion( 'dashboard', $total_donations );

				$html = $this->get_dashboard_notice_html( $suggestion, $headline );

			} else {

				$html = $this->get_dashboard_notice_html( $suggestion, $headline );

			}

			if ( $html ) {
				wp_send_json_success( $html );
			} else {
				wp_send_json_error( esc_html__( 'Error generating dashboard notice.', 'charitable' ) );
			}
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1.6
		 *
		 * @return Charitable_Guide_Tools
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
