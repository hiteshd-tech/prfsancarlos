<?php
/**
 * Charitable Checklist.
 *
 * @package   Charitable/Classes/Charitable_Checklist
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.2
 * @version   1.8.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Checklist' ) ) :

	/**
	 * Charitable_Checklist
	 *
	 * @final
	 * @since 1.8.2
	 */
	class Charitable_Checklist {

		/**
		 * The name of the option value.
		 *
		 * @var array
		 */
		private $checklist_option_name = 'charitable_onboarding_checklist';

		/**
		 * The name of the option value.
		 *
		 * @var array
		 */
		const PRO_PLUGIN = 'charitable-pro/charitable.php';

		/**
		 * The steps in the checklist.
		 *
		 * @var array
		 */
		public $steps = array(
			'connect-gateway',
			'general-settings',
			'email-settings',
			'first-campaign',
			'first-donation',
			'next-level',
		);

		/**
		 * The step urls in the checklist.
		 *
		 * @var array
		 */
		public $steps_url = array(
			'connect-gateway'  => 'admin.php?page=charitable-settings&tab=gateways&checklist=connect-gateway',
			'general-settings' => 'admin.php?page=charitable-settings&checklist=general-settings',
			'email-settings'   => 'admin.php?page=charitable-settings&tab=emails&checklist=email-settings',
			'first-campaign'   => 'admin.php?page=charitable-campaign-builder&view=template',
			'first-donation'   => 'admin.php?edit.php?post_type=donation',
			'next-level'       => 'admin.php?page=charitable-addons',
		);

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Checklist|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.2
		 */
		public function __construct() {
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.2
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.8.2
		 */
		public function enqueue_styles_and_scripts() {

			if ( $this->maybe_load_checklist_assets() ) {

				$min           = charitable_get_min_suffix(); // 1.8.3
				$version       = charitable()->get_version();
				$assets_dir    = charitable()->get_path( 'assets', false );
				$style_version = charitable_get_style_version();

				wp_enqueue_style(
					'charitable-onboarding-checklist',
					$assets_dir . 'css/onboarding/checklist' . $min . '.css',
					array(),
					$version
				);

				// Enqueue about-us.css for navigation styling
				wp_enqueue_style(
					'charitable-admin-about-us',
					$assets_dir . 'css/admin/about-us.css',
					array(),
					$version
				);

				/* tour */

				wp_enqueue_script(
					'charitable-float-ui-core',
					charitable()->get_path( 'directory', false ) . 'assets/js/libraries/floating-ui-core.min.js',
					array( 'jquery' ),
					charitable()->get_version()
				);

				wp_enqueue_script(
					'charitable-float-ui-dom',
					charitable()->get_path( 'directory', false ) . 'assets/js/libraries/floating-ui-dom.min.js',
					array( 'charitable-float-ui-core' ),
					charitable()->get_version()
				);

				wp_enqueue_script(
					'charitable-shepherd',
					charitable()->get_path( 'directory', false ) . 'assets/js/libraries/shepherd.js',
					array( 'jquery', 'charitable-float-ui-core', 'charitable-float-ui-dom' ),
					charitable()->get_version()
				);

				wp_enqueue_style(
					'charitable-shepherd',
					charitable()->get_path( 'directory', false ) . "assets/css/libraries/shepherd{$min}.css",
					array(),
					$style_version
				);

				wp_enqueue_script(
					'charitable-admin-checklist',
					charitable()->get_path( 'directory', false ) . 'assets/js/admin/charitable-admin-checklist.js',
					array( 'jquery', 'charitable-shepherd', 'charitable-float-ui-core', 'charitable-float-ui-dom' ), // 'charitable-admin-utils',
					charitable()->get_version()
				);

				wp_localize_script(
					'charitable-admin-checklist',
					'charitable_admin_checklist_onboarding',
					[
						'nonce'   => wp_create_nonce( 'charitable_onboarding_ajax_nonce' ),
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'option'  => array(
							'window_closed' => $this->get_checklist_option( 'window_closed' ),
							'tour'          => array(
								'status'          => $this->get_checklist_option( 'status' ),
								'steps_completed' => $this->get_checklist_option( 'steps' ),
							),
						),
						'strings' => $this->get_localized_strings(),
					]
				);

			}
		}

		/**
		 * Add the checklist-completed class to the admin body if it's completed, etc.
		 *
		 * @since 1.8.2
		 *
		 * @param  string $classes Body classes.
		 * @return string
		 */
		public function add_body_class( $classes = '' ) {

			if ( $this->is_checklist_completed() ) {
				$classes .= ' charitable-checklist-completed ';
			} elseif ( $this->is_checklist_skipped() ) {
				$classes .= ' charitable-checklist-skipped ';
			} elseif ( $this->is_checklist_started() ) {
				$stats    = $this->get_steps_stats();
				$classes .= ' charitable-checklist-started  charitable-checklist-steps-completed-' . intval( $stats['completed'] ) . ' charitable-checklist-steps-total-' . intval( $stats['total'] . ' ' );
			}

			// add a class if we are on the actual checklist page.
			if ( isset( $_GET['page'] ) && 'charitable-setup-checklist' === $_GET['page'] ) { // phpcs:ignore
				$classes .= ' charitable-checklist-page ';
			}

			return $classes;
		}

		/**
		 * Get percentage completed.
		 *
		 * @since 1.8.2
		 *
		 * @return int
		 */
		public function get_percentage_completed() {

			if ( $this->is_checklist_completed() ) {
				return 100;
			}

			$stats = $this->get_steps_stats();

			if ( ! is_array( $stats ) || empty( $stats ) || ! isset( $stats['total'] ) || ! isset( $stats['completed'] ) ) {
				return 0;
			}

			if ( 0 === intval( $stats['total'] ) ) {
				return 0;
			}

			return intval( ( intval( $stats['completed'] ) / intval( $stats['total'] ) ) * 100 );
		}

		/**
		 * Get the checklist menu HTML.
		 *
		 * @since 1.8.2
		 *
		 * @return string
		 */
		public function get_checklist_bar_html() {

			$percentage = $this->get_percentage_completed();
			$percentage = min( 100, max( 0, $percentage ) );

			$html = '<span class="charitable-checklist-progress-bar-container" style="display: block;height: 4px;margin: 5px 0;background-color: #000;"><span style="background-color: #5AA152; width: ' . $percentage . '%; height: 4px; display: block;"></span></span>';

			return $html;
		}

		/**
		 * Add checklist to WordPress admin menu.
		 *
		 * @since 1.8.2
		 * @version 1.8.8.1
		 *
		 * @param array $submenu The submenu array.
		 *
		 * @return array
		 */
		public function add_checklist_to_menu( $submenu = array() ) {

			if ( ( defined( 'CHARITABLE_ONBOARDING_NO_CHECKLIST' ) && CHARITABLE_ONBOARDING_NO_CHECKLIST ) ) {
				return $submenu;
			}

			// Always show checklist menu item - removed time-based restrictions
			$checklist_menu_item = array(
				'page_title' => __( 'Checklist', 'charitable' ),
				'menu_title' => __( 'Checklist', 'charitable' ) . $this->get_checklist_bar_html(),
				'menu_slug'  => 'charitable-setup-checklist',
				'function'   => array( $this, 'render_setup_checklist_page' ),
				'capability' => 'manage_charitable_settings',
			);

			// Add checklist menu item to the beginnnning of the submenu.
			array_unshift( $submenu, $checklist_menu_item );

			return $submenu;
		}

		/**
		 * Display the Charitable Growth Tools page.
		 *
		 * @since  1.8.1.15
		 *
		 * @return void
		 */
		public function render_setup_checklist_page() {
			charitable_admin_view( 'checklist/checklist' );
		}

		/**
		 * Save the checklist option via AJAX.
		 *
		 * @since 1.8.2
		 *
		 * @return void
		 */
		public function save_checklist_option_ajax() {

			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'charitable_onboarding_ajax_nonce' ) ) { // phpcs:ignore
				wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			}

			if ( ! isset( $_POST['option_data'] ) ) {
				wp_send_json_error( array( 'message' => 'Missing option or value' ) );
			}

			if ( isset( $_POST['option_data']['status'] ) && in_array( sanitize_text_field( $_POST['option_data']['status'] ), array( 'completed', 'skipped', 'start', 'init' ) ) ) {
				$status = sanitize_text_field( wp_unslash( $_POST['option_data']['status'] ) );
				$this->update_checklist_status( $status );
				wp_send_json_success( array( 'message' => esc_html__( 'Charitable checklist status saved', 'charitable' ) ) );
			} else {
				// This isn't updating the status of the checklist, just passing a value.
				$window_closed = isset( $_POST['option_data']['window_closed'] ) ? sanitize_text_field( $_POST['option_data']['window_closed'] ) : ''; // phpcs:ignore
				if ( $window_closed !== '' ) {
					$options                  = $this->get_checklist_data();
					$options['window_closed'] = $window_closed;
					update_option( $this->checklist_option_name, $options );
					wp_send_json_success( array( 'message' => esc_html__( 'Charitable checklist information saved', 'charitable' ) ) );
				}
			}

			if ( isset( $_POST['option_data']['stepStatus'] ) && 'completed' === sanitize_text_field( $_POST['option_data']['stepStatus'] ) ) { // phpcs:ignore
				if ( isset( $_POST['option_data']['step'] ) ) {
					$step = sanitize_text_field( wp_unslash( $_POST['option_data']['step'] ) );
					$this->mark_step_completed( $step );
					wp_send_json_success( array( 'message' => esc_html__( 'Charitable checklist information saved', 'charitable' ) ) );
				} else {
					wp_send_json_error( array( 'message' => esc_html__( 'No checklist step was updated.', 'charitable' ) ) );
				}

			}

			wp_send_json_success( array( 'message' => esc_html__( 'No checklist information was updated.', 'charitable' ) ) );
		}

		/**
		 * Get the CSS classes for the steps.
		 *
		 * @since 1.8.1.5
		 *
		 * @return array
		 */
		public function get_steps_css() {

			if ( empty( $this->steps ) ) {
				return array();
			}

			$checklist_classes = array();

			foreach ( $this->steps as $step ) {
				$checklist_classes[ $step ] = $this->is_step_completed( $step ) ? 'charitable-checklist-completed charitable-checklist-checked' : 'charitable-checklist-unchecked';
			}

			return $checklist_classes;
		}

		/**
		 * Get the checklist urls.
		 *
		 * @since 1.8.2
		 *
		 * @return array
		 */
		public function get_steps_urls() {
			return $this->steps_url;
		}

		/**
		 * Check if the checklist is completed.
		 *
		 * @since 1.8.2
		 *
		 * @return bool
		 */
		public function is_checklist_completed() {

			$options = $this->get_checklist_data();

			if ( $options['status'] === 'completed' ) {
				return true;
			}

			// Go through all the steps, check if the step is completed, and if they are all completed return true.
			foreach ( $this->steps as $step ) {
				// ignore first-donation.
				if ( 'first-donation' === $step ) {
					continue;
				}
				if ( ! $this->is_step_completed( $step ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Check if the checklist is completed.
		 *
		 * @since 1.8.1.5
		 *
		 * @return bool
		 */
		public function is_checklist_skipped() {

			$options = $this->get_checklist_data();

			if ( $options['status'] === 'skipped' ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if the checklist should be considered completed based on actual conditions.
		 * This method determines if the checklist should be auto-completed based on:
		 * - Having campaigns created
		 * - Time since activation (14+ days)
		 * - Other completion criteria
		 *
		 * @since 1.8.2
		 * @version 1.8.8.1
		 *
		 * @return bool
		 */
		public function should_checklist_be_completed() {

			$total_campaigns = wp_count_posts( 'campaign' );
			$count_campaigns = ! empty( $total_campaigns->publish ) ? $total_campaigns->publish : 0;

			// If there are campaigns created, check activation time
			if ( intval( $count_campaigns ) > 0 ) {
				$activation_date = get_option( 'wpcharitable_activated_datetime', false );

				// If wpcharitable_activated_datetime is false, try the backup option
				if ( false === $activation_date ) {
					$activation_date = get_option( 'charitable_activated', false );
					if ( is_array( $activation_date ) ) {
						foreach ( $activation_date as $date ) {
							if ( is_numeric( $date ) ) {
								$activation_date = $date;
								break;
							}
						}
					}
				}

				// If we have an activation date and it's been more than 14 days, auto-complete
				if ( false !== $activation_date && is_numeric( $activation_date ) ) {
					$difference = time() - $activation_date;
					if ( $difference > ( 14 * 24 * 60 * 60 ) ) {
						return true;
					}
				}
			}

			// Also check if all required steps are actually completed
			$required_steps = array( 'connect-gateway', 'general-settings', 'email-settings', 'first-campaign' );
			$all_steps_completed = true;

			foreach ( $required_steps as $step ) {
				if ( ! $this->is_step_completed( $step, false ) ) {
					$all_steps_completed = false;
					break;
				}
			}

			return $all_steps_completed;
		}

		/**
		 * Check if the checklist is started.
		 *
		 * @since 1.8.2
		 *
		 * @return bool
		 */
		public function is_checklist_started() {

			$options = $this->get_checklist_data();

			if ( $options['status'] === 'start' ) {
				return true;
			}

			// check if any of the steps have been completed.
			foreach ( $this->steps as $step ) {
				if ( $this->is_step_completed( $step ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get the stats for the steps.
		 *
		 * @since 1.8.2
		 *
		 * @return array
		 */
		public function get_steps_stats() {

			$stats = array(
				'total'     => count( $this->steps ) - 1,
				'completed' => 0,
			);

			foreach ( $this->steps as $step ) {
				if ( $this->is_step_completed( $step ) && 'first-donation' !== $step ) {
					++$stats['completed'];
				}
			}

			return $stats;
		}

		/**
		 * After saving general settings after a checklist prompt, return to the checklist.
		 *
		 * @since 1.8.2
		 *
		 * @param array $values Current values.
		 * @param array $new_values New values.
		 *
		 * @return array
		 */
		public function maybe_redirect_to_checklist_after_event( $values, $new_values ) { // phpcs:ignore

			if ( ! function_exists( 'charitable_is_settings_view' ) || ! charitable_is_settings_view() ) {
				return $values;
			}

			if ( empty( $_POST['_wp_http_referer'] ) ) { // phpcs:ignore
				return $values;
			}

			// if checklist=general-settings is in the _wp_http_referer, then redirect to the checklist page.
			if ( strpos( $_POST['_wp_http_referer'], 'checklist=general-settings' ) !== false ) { // phpcs:ignore
				$redirect_url = admin_url( 'admin.php?page=charitable-setup-checklist&step=continue' );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			// if checklist=general-settings is in the _wp_http_referer, then redirect to the checklist page.
			if ( strpos( $_POST['_wp_http_referer'], 'checklist=email-settings' ) !== false ) { // phpcs:ignore
				$redirect_url = admin_url( 'admin.php?page=charitable-setup-checklist&step=continue' );
				wp_safe_redirect( $redirect_url );
				exit;
			}

			return $values;
		}

		/**
		 * After saving general settings after a checklist prompt, maybe return to the checklist.
		 *
		 * @since 1.8.2
		 *
		 * @return void
		 */
		public function maybe_redirect_to_next_step() {

			// if the page is the checklist page and the next step is simple 'continue' then redirect to the checklist page with an updated step query string.
			if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'charitable-setup-checklist' || ! isset( $_GET['step'] ) || $_GET['step'] !== 'continue' ) { // phpcs:ignore
				return;
			}

			// we need to find the next step that has not been completed.
			$next_checklist_url = $this->get_next_checklist_url();

			if ( empty( $next_checklist_url ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=charitable-setup-checklist' ) );
				exit;
			}

			wp_safe_redirect( $next_checklist_url );
			exit;
		}

		/**
		 * Confirm general settings was done, and check it off.
		 *
		 * @since 1.8.2
		 *
		 * @param array $values Current values.
		 * @param array $new_values New values.
		 *
		 * @return array
		 */
		public function confirm_general_settings( $values, $new_values ) { // phpcs:ignore

			// Not the right setting page.
			if ( ! isset( $_POST['_wp_http_referer'] ) || ! isset( $_POST['option_page'] ) || 'charitable_settings' !== $_POST['option_page'] || empty( $_POST['charitable_settings'] ) ) { // phpcs:ignore
				return $values;
			}

			if ( strpos( $_POST['_wp_http_referer'], 'checklist=general-settings' ) === false ) { // phpcs:ignore
				return $values;
			}

			$this->mark_step_completed( 'general-settings' );

			return $values;
		}

		/**
		 * Confirm email settings form was submitted, and check it off.
		 *
		 * @since 1.8.2
		 *
		 * @param array $values Current values.
		 * @param array $new_values New values.
		 *
		 * @return array
		 */
		public function confirm_email_settings( $values, $new_values ) { // phpcs:ignore

			// Not the right setting page.
			if ( ! isset( $_POST['_wp_http_referer'] ) || ! isset( $_POST['option_page'] ) || 'charitable_settings' !== $_POST['option_page'] || empty( $_POST['charitable_settings'] ) ) { // phpcs:ignore
				return $values;
			}

			if ( strpos( $_POST['_wp_http_referer'], 'checklist=email-settings' ) === false ) { // phpcs:ignore
				return $values;
			}

			$this->mark_step_completed( 'email-settings' );

			return $values;
		}

		/**
		 * If the user enables or disables an email while on the settings page then the step is checked off (even if the user doesn't save the settings form).
		 *
		 * @since 1.8.2
		 *
		 * @return bool
		 */
		public function confirm_email_changes() {

			// Not the right setting page.
			if ( ! isset( $_GET['page'] ) || ! isset( $_GET['tab'] ) || 'charitable-settings' !== $_GET['page'] || 'emails' !== $_GET['tab'] ) { //	phpcs:ignore
				return false;
			}

			// There needs to be an action taking place.
			if ( ! isset( $_GET['charitable_action'] ) ) { // phpcs:ignore
				return false;
			}

			if ( ! $this->is_checklist_started() || $this->is_checklist_completed() || $this->is_checklist_skipped() ) {
				return false;
			}

			$completed = $this->mark_step_completed( 'email-settings' );

			return $completed;
		}

		/**
		 * Save the checklist option.
		 *
		 * @since 1.8.2
		 *
		 * @param string $step The option to save. Could be 'general-settings', 'email-settings', 'connect-gateway', etc.
		 *
		 * @return bool
		 */
		public function mark_step_completed( $step = '' ) {

			if ( '' === $step ) {
				return false;
			}

			$options = $this->get_checklist_data();
			if ( ! in_array( $step, $options['steps'] ) ) {
				$options['steps'][] = $step;
			}
			update_option( $this->checklist_option_name, $options );

			return true;
		}

		/**
		 * Mark checklist completed (regardless of steps).
		 *
		 * @since 1.8.2
		 */
		public function maybe_complete_checklist() {

			// if we are on the checklist page, it's a must.
			if ( ! isset( $_GET['page'] ) || 'charitable-setup-checklist' !== $_GET['page'] ) { // phpcs:ignore
				return;
			}

			if ( ! isset( $_GET['completed'] ) || 'true' !== $_GET['completed'] ) { // phpcs:ignore
				return;
			}

			// go through all the steps and mark them completed.
			foreach ( $this->steps as $step ) {
				$this->mark_step_completed( $step );
			}

			$this->update_checklist_status( 'completed' );

			// redirect to the checklist page.
			wp_safe_redirect( admin_url( 'admin.php?page=charitable-setup-checklist' ) );
			exit;
		}

		/**
		 * Manually mark checklist as completed.
		 * This method can be called to force completion of the checklist.
		 *
		 * @since 1.8.2
		 * @version 1.8.8.1
		 *
		 * @return bool True if successful, false otherwise.
		 */
		public function force_complete_checklist() {

			// Mark all steps as completed
			foreach ( $this->steps as $step ) {
				$this->mark_step_completed( $step );
			}

			// Update status to completed
			$result = $this->update_checklist_status( 'completed' );

			// Clear any existing dashboard notices
			$dashboard_notices = get_option( 'charitable_dashboard_notifications', array() );
			if ( isset( $dashboard_notices['checklist_status'] ) ) {
				unset( $dashboard_notices['checklist_status'] );
				update_option( 'charitable_dashboard_notifications', $dashboard_notices );
			}

			return $result;
		}


		/**
		 * Save the checklist option.
		 *
		 * @since 1.8.2
		 *
		 * @param string $status The status to save. Could be 'completed', 'skipped', 'start', 'init'.
		 *
		 * @return bool
		 */
		public function update_checklist_status( $status = '' ) {

			if ( '' === $status ) {
				return false;
			}

			$options           = $this->get_checklist_data();
			$options['status'] = $status;
			update_option( $this->checklist_option_name, $options );

			return true;
		}

		/**
		 * Get the start checklist URL.
		 *
		 * @since 1.8.2
		 *
		 * @return string
		 */
		public function get_start_checklist_url() {

			return apply_filters( 'charitable_start_checklist_url', admin_url( 'admin.php?page=charitable-setup-checklist' ) );
		}

		/**
		 * Get the next checklist URL.
		 *
		 * @since 1.8.2
		 *
		 * @return string
		 */
		public function get_next_checklist_url() {

			// Determine what is the first step that has not been completed.
			$options = $this->get_checklist_data();
			$steps   = ! empty( $options['steps'] ) ? $options['steps'] : array();
			$steps   = ! empty( $steps ) ? array_values( array_intersect( $this->steps, $steps ) ) : array();

			foreach ( $this->steps as $step ) {
				if ( $step === 'first-donation' ) {
					continue;
				}
				if ( ! in_array( $step, $steps, true ) ) {
					// ...find that step in $steps_url and return that URL.
					if ( isset( $this->steps_url[ $step ] ) ) {
						// there's an exemption for connect-gateway, check if it's completed and if so skip.
						if ( 'connect-gateway' === $step && $this->is_step_completed( 'connect-gateway' ) ) {
							continue;
						}
						return apply_filters( 'charitable_start_checklist_url', admin_url( 'admin.php?page=charitable-setup-checklist#' . $step ) );
					}
				}
			}
			// when in doubt, return the start checklist URL.
			return apply_filters( 'charitable_start_checklist_url', admin_url( 'admin.php?page=charitable-setup-checklist' ) );
		}

		/**
		 * Translate strings.
		 *
		 * @since 1.8.2
		 *
		 * @return array
		 */
		public function get_localized_strings() {

			$strings = array(
				'version'                       => '1.8.2',
				'ok'                            => esc_html__( 'OK', 'charitable' ),
				'next'                          => esc_html__( 'Next', 'charitable' ),
				'start_tour'                    => esc_html__( 'Start Tour', 'charitable' ),
				'watch_video'                   => esc_html__( 'Watch Video', 'charitable' ),
				'choose_a_template'             => esc_html__( 'Choose a Template', 'charitable' ),
				'lets_get_started'              => esc_html__( 'Get Started', 'charitable' ),
				'general_settings_step_0_title' => esc_html__( 'Settings Page', 'charitable' ),
				'general_settings_step_0_text'  => '<p style="margin-bottom: 0;">' . esc_html__( 'This is where general settings for your Charitable plugin live. You can change your currency, country, and the behavior of your donation form on this page.', 'charitable' ) . '</p>',
				'general_settings_step_1_text'  => '<p>' . esc_html__( 'Confirm your settings and save changes to complete this item on the checklist.', 'charitable' ) . '</p>',
				'email_settings_step_0_title'   => esc_html__( 'Email Settings', 'charitable' ),
				'email_settings_step_0_text'    => '<p>' . esc_html__( 'Enable and configure email notifications for donors, campaign creators, and site admins (like you).', 'charitable' ) . '</p>',
				'email_settings_step_1_text'    => '<h2>' . esc_html__( 'Donor Donation Receipt', 'charitable' ) . '</h2><p>' . esc_html__( 'This email sends donors a receipt after they make a donation.', 'charitable' ) . '</p>',
				'email_settings_step_2_text'    => '<h2>' . esc_html__( 'Admin New Donation Notification', 'charitable' ) . '</h2><p>' . esc_html__( 'This email sends you (the admin) a notification when a new donation has been received.', 'charitable' ) . '</p><p>' . esc_html__( 'You can ', 'charitable' ) . '<a href="https://www.wpcharitable.com/documentation/start-here/#Emails" target="_blank">' . esc_html__( 'read more about email settings', 'charitable' ) . '</a> ' . esc_html__( 'in our docs', 'charitable' ) . '.</p>',
				'email_settings_step_3_text'    => '<p>' . esc_html__( 'Confirm your email settings and save changes to complete this item on the checklist.', 'charitable' ) . '</p>',

				'gateway_settings_step_0_text'  => '<h2>' . esc_html__( 'Gateway Settings', 'charitable' ) . '</h2><p>' . esc_html__( 'Connect to a gateway to start getting donations as soon possible. Want to test before setting up a gateway or taking alternate methods of payment? Enable "Offline Mode".', 'charitable' ) . '</p>',
				'gateway_settings_step_1_text'  => '<p>' . esc_html__( 'We recommend Stripe if it\'s available in your country', 'charitable' ) . '</p>',
				'gateway_settings_step_2_text'  => '<p>' . esc_html__( 'Test mode allows Charitable to connect to Stripe and other payment gateways in their test modes. Make sure to check this off when you are ready to accept live donations.', 'charitable' ) . '</p>',
				'gateway_settings_step_3_text'  => '<p>' . esc_html__( 'Confirm your gateway settings and save changes to complete this item on the checklist.', 'charitable' ) . '</p>',

			);

			$strings = apply_filters( 'charitable_checklist_strings', $strings );

			return $strings;
		}

		/**
		 * Get tour parameter(s).
		 *
		 * @since 1.8.2
		 *
		 * @param string $option Option key.
		 * @return mixed
		 */
		public function get_checklist_option( $option = false ) {

			$schema  = $this->checklist_option_schema();
			$options = $this->get_checklist_data();
			$steps   = ! empty( $options['steps'] ) ? $options['steps'] : array();

			if ( 'status' === $option && defined( 'CHARITABLE_ONBOARDING_FORCE_CHECKLIST' ) && CHARITABLE_ONBOARDING_FORCE_CHECKLIST ) {
				return 'init';
			}

			// Step in and override here... if 'status' is the option being requested and there is already at least one campaign created then return 'skipped'.
			if ( 'status' === $option && ( ! empty( $steps ) || ! isset( $options['status'] ) || 'init' === $options['status'] ) ) {

				if ( ( defined( 'CHARITABLE_ONBOARDING_NO_CHECKLIST' ) && CHARITABLE_ONBOARDING_NO_CHECKLIST ) ) {
					return 'skipped';
				}
			}

			if ( ! $options || ! is_array( $options ) ) {
				return $schema[ $option ] ?? '';
			}

			return $options[ $option ] ?? $schema[ $option ] ?? '';
		}

		/**
		 * Check if a step is completed.
		 *
		 * This is how we tell when a step is completed on the checklist.
		 *
		 * "general-settings"       - if the user has saved general settings at least once.
		 * "email-settings"         - if the user has saved email settings at least once.
		 * "connect-payment"        - if any payment gateway is connected.
		 * "first-campaign"         - if a campaign has been created.
		 * "first-donation"         - if a donation has been created.
		 * "opt-in"                 - if the user has opted in to the newsletter/sharing data.
		 * "fundraising-next-level" - when pro is active or ???
		 *
		 * @since 1.8.2
		 *
		 * @param string $step The step to check.
		 * @param bool   $update_option If true, update the checklist data option if we "double check" the step and it is completed.
		 *
		 * @return bool
		 */
		public function is_step_completed( $step = '', $update_option = true ) {

			if ( empty( $step ) ) {
				return false;
			}

			$checklist_data = $this->get_checklist_data();
			// we store this as an option, so load the option and check the array key.

			if ( ! isset( $checklist_data['steps'] ) ) {
				return false;
			} else {
				$completed_steps = (array) $checklist_data['steps'];
			}

			if ( in_array( $step, $completed_steps ) ) {
				return true;
			}

			// if the step is not in the array, sometimes we can "double" check depending on the step.
			switch ( $step ) {
				case 'first-campaign':
					// if there is at least one campaign, this is completed.
					$campaigns_count = Charitable_Campaigns::query(
						array(
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					)->found_posts;
					if ( $campaigns_count > 0 ) {
						if ( $update_option ) {
							$this->mark_step_completed( 'first-campaign' );
						}
						return true;
					}
					break;

				case 'first-donation':
					// if the step is false... then if there is at least one donation, this is completed.
					$total_donations_array = (array) wp_count_posts( 'donation' ); // the function caches this, so we shouldn't have to.
					$total_donations       = ! empty( $total_donations_array ) ? array_sum( $total_donations_array ) : 0;
					if ( $total_donations > 0 ) {
						if ( $update_option ) {
							$this->mark_step_completed( 'first-donation' );
						}
						return true;
					}
					break;

				case 'connect-gateway':
					$stripe_gateway   = new Charitable_Gateway_Stripe_AM();
					$stripe_connected = $stripe_gateway->maybe_stripe_connected();
					$gateway_mode     = ( charitable_get_option( 'test_mode' ) ) ? 'test' : 'live';

					if ( $stripe_connected || ! empty( $_POST['charitable_settings']['gateways'] ) ) { // phpcs:ignore
						if ( $update_option ) {
							$this->mark_step_completed( 'connect-gateway' );
						}
						return true;
					}
					return false;

					break;

				default:
					break;
			}

			return false;
		}

		/**
		 * Check if the first campaign step is completed.
		 *
		 * @since 1.8.2
		 *
		 * @return bool
		 */
		public function check_step_first_campaign() {
			if ( $this->is_step_completed( 'first-campaign' ) ) {
				return true;
			}

			// if there is at least one campaign, this is completed.
			$count_campaigns = wp_count_posts( 'campaign' );
			if ( $count_campaigns > 0 ) {
				$this->mark_step_completed( 'first-campaign' );
				return true;
			}

			return false;
		}

		/**
		 * The schema for the checklist option.
		 *
		 * @since 1.8.2
		 *
		 * @return array
		 */
		public function checklist_option_schema() {

			return array(
				'status'        => 'init',
				'steps'         => array(),
				'window_closed' => '',
			);
		}

		/**
		 * Retrieve the checklist data (or create it if it doesn't exist).
		 *
		 * @since 1.8.2
		 *
		 * @return array
		 */
		public function get_checklist_data() {

			$checklist_data = (array) get_option( $this->checklist_option_name, array() );

			if ( empty( $checklist_data ) || false === $checklist_data ) {
				$checklist_data = $this->checklist_option_schema();
				update_option( $this->checklist_option_name, $checklist_data );
			}

			return $checklist_data;
		}

		/**
		 * Adds (probably) the checklist HTML to the footer.
		 *
		 * @since 1.8.2
		 *
		 * @return bool
		 */
		public function maybe_add_checklist_widget_html() {

			if ( $this->maybe_load_checklist_assets() ) {

				// If the checklist was skipped, do not add the widget.
				if ( $this->is_checklist_skipped() ) {
					return false;
				}

				include charitable()->get_path( 'includes' ) . 'admin/templates/checklist-widget.php';

			}
		}

		/**
		 * Confirm if we can load the checklist assets.
		 *
		 * @since 1.8.2
		 * @version 1.8.8.1
		 *
		 * @return bool
		 */
		public function maybe_load_checklist_assets() {

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			// if we are on the checklist page, always allow access
			if ( isset( $_GET['page'] ) && 'charitable-setup-checklist' === $_GET['page'] ) { // phpcs:ignore`
				return true;
			}

			// if we aren't on a Charitable "screen" then don't load the checklist assets.
			if ( is_null( $screen ) || ! in_array( $screen->id, charitable_get_charitable_screens() ) ) {
				return false;
			}

			// if the constant is set to force the checklist, then load the assets.
			if ( defined( 'CHARITABLE_ONBOARDING_FORCE_CHECKLIST' ) && CHARITABLE_ONBOARDING_FORCE_CHECKLIST ) {
				return true;
			}

			// if the checklist is skipped, don't bother.
			if ( $this->is_checklist_skipped() || $this->is_checklist_completed() ) {
				return false;
			}

			// Always allow checklist assets to load on Charitable screens - removed time restrictions
			if ( ! is_null( $screen ) && in_array( $screen->id, charitable_get_charitable_screens() ) ) {
				return true;
			}

			// false will be the fall back.
			return apply_filters( 'charitable_load_checklist_assets', false, $screen );
		}

		/**
		 * Get dashboard notices.
		 *
		 * @since   1.8.1.6
		 * @version 1.8.1.9 // Updated doc URL.
		 * @version 1.8.2
		 * @version 1.8.8.1
		 */
		public function get_dashboard_notices() {

			$dashboard_notices = get_option( 'charitable_dashboard_notifications', array() );

			// Check if checklist should be considered completed based on actual conditions
			$should_be_completed = $this->should_checklist_be_completed();

			// If checklist should be completed but isn't marked as such, mark it as completed
			if ( $should_be_completed && ! $this->is_checklist_completed() && ! $this->is_checklist_skipped() ) {
				$this->update_checklist_status( 'completed' );
			}

			// Has user completed the checklist?
			if ( $this->is_checklist_completed() || $this->is_checklist_skipped() ) {
				// remove the checklist notice if it exists, and then bail.
				if ( isset( $dashboard_notices['checklist_status'] ) ) {
					unset( $dashboard_notices['checklist_status'] );
					update_option( 'charitable_dashboard_notifications', $dashboard_notices );
				}
				return $dashboard_notices;
			}

			// if the 'checklist_status' already exists in the dashboard notices, don't add it again.
			if ( isset( $dashboard_notices['checklist_status'] ) ) {
				return $dashboard_notices;
			}

			// Only show notice if checklist is actually incomplete and user has started it
			if ( ! $this->is_checklist_started() ) {
				return $dashboard_notices;
			}

			$dashboard_notices['checklist_status'] = array(
				'type'       => 'notice',
				'dismiss'    => false,
				'title'      => esc_html__( 'Notice', 'charitable' ),
				'custom_css' => 'charitable-notification-type-notice',
				'message'    => sprintf(
					'<p>%1$s <a href="%2$s">%3$s</a></p>',
					__( 'Charitable Checklist is not completed.', 'charitable' ),
					admin_url( 'admin.php?page=charitable-setup-checklist' ),
					__( 'Finish the Checklist.', 'charitable' )
				)
			);

			update_option( 'charitable_dashboard_notifications', $dashboard_notices );

			return $dashboard_notices;
		}

		/**
		 * Determine if we load checklist (or assets) if there is at least one campaign and X days past activation.
		 *
		 * @since 1.8.2
		 *
		 * @return string
		 */
		public function maybe_load_checklist_within_activation() {

			$total_campaigns = wp_count_posts( 'campaign' );
			$count_campaigns = ! empty( $total_campaigns->publish ) ? $total_campaigns->publish : 0;
			// if there is a campaign created (but the checklist hasn't been skipped or completed) AND the activation has been past 7 days, then do not load the checklist assets.
			if ( intval( $count_campaigns ) > 0 && ( ! $this->is_checklist_skipped() && ! $this->is_checklist_completed() ) ) {
				$activation_date = get_option( 'wpcharitable_activated_datetime', false );
				// If wpcharitable_activated_datetime is false, then the backup would be to load the charitable_activated option as an array and get the first value in the array that is a timestamp.
				if ( false === $activation_date ) {
					$activation_date = get_option( 'charitable_activated', false );
					if ( is_array( $activation_date ) ) {
						foreach ( $activation_date as $date ) {
							if ( is_numeric( $date ) ) {
								$activation_date = $date;
								break;
							}
						}
					}
				}
				if ( false !== $activation_date && is_numeric( $activation_date ) ) {
					// Calculate the difference in seconds.
					$difference = time() - $activation_date;
					// Determine if more than 14 days have passed (14 days * 24 hours * 60 minutes * 60 seconds).
					if ( $difference > ( 14 * 24 * 60 * 60 ) ) {

						$cache_last_cleared = get_option( 'charitable_cache_cleared', false );
						if ( false !== $cache_last_cleared && ( time() - $cache_last_cleared ) > 3600 ) {
							return false;
						}
					}
				}
			}

			return true;
		}

		/**
		 * This will incercept the request and redirect to the dashboard page.
		 *
		 * @since 1.8.3
		 */
		public function maybe_redirect_from_checklist_page() {

			if ( ! empty( $_GET['page'] ) && 'charitable-checklist' === $_GET['page'] ) { // phpcs:ignore
				// is the checklist active?
				if ( ! $this->maybe_load_checklist_assets() ) {
					// if the checklist is not active, then redirect to the dashboard page.
					wp_safe_redirect( admin_url( 'admin.php?page=charitable-dashboard' ) );
					exit;
				}
			}
		}

		/**
		 * Activate the pro plugin after onboarding.
		 * This includes needed flushing of cache so Pro can be updated instantly if need be.
		 *
		 * @since   1.8.5
		 * @version 1.8.5.1
		 */
		public function maybe_activate_pro_after_onboarding() {

			// Are we about to visit'charitable-setup-checklist'?
			if ( ! isset( $_GET['page'] ) || 'charitable-setup-checklist' !== $_GET['page'] ) { // phpcs:ignore
				return;
			}

			// Is the option for installing pro after onboarding set?
			$activate_pro = get_option( 'charitable_activate_pro', false );

			if ( ! $activate_pro ) {
				return;
			}

			// CRITICAL SAFETY CHECK: Verify Pro plugin actually exists before proceeding
			$pro_plugin_path = WP_PLUGIN_DIR . '/' . self::PRO_PLUGIN;
			if ( ! file_exists( $pro_plugin_path ) ) {
				// Pro plugin doesn't exist - don't deactivate Lite version
				// Clear the option to prevent future attempts
				delete_option( 'charitable_activate_pro' );
			// Log this for debugging
			error_log( 'Charitable: Pro plugin not found at ' . $pro_plugin_path . ' - preventing Lite deactivation' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return;
			}

			// Confirm the pro plugin is installed (and not activated).
			if ( ! is_plugin_inactive( self::PRO_PLUGIN ) ) {
				return;
			}

			// Deactivate the lite version first.
			$plugin = plugin_basename( charitable()->get_path( 'plugin-directory' ) . '/charitable/charitable.php' );

			deactivate_plugins( $plugin );

			// phpcs:ignore Charitable.Comments.PHPDocHooks.RequiredHookDocumentation, Charitable.PHP.ValidateHooks.InvalidHookName
			do_action( 'charitable_plugin_deactivated', $plugin );

			// Activate the plugin silently.
			$activated = activate_plugin( self::PRO_PLUGIN, '', false, true );

			if ( ! is_wp_error( $activated ) ) {

				// Add the pro_connect activation date to the activated array.
				$activated = (array) get_option( 'charitable_activated', array() );

				if ( empty( $activated['pro_connect'] ) ) {
					$activated['pro_connect'] = time();
					update_option( 'charitable_activated', $activated );
				}
			}

			// remove the option.
			delete_option( 'charitable_activate_pro' );

			$empty_transient = new \stdClass();
			set_site_transient( 'update_plugins', $empty_transient ); // Depreciated item.
			delete_site_option( 'wpc_plugin_versions' );
			update_option( 'charitable_connect_completed', true );

			// redirect again to the checklist page.
			wp_safe_redirect( admin_url( 'admin.php?page=charitable-setup-checklist' ) );
			exit;

		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.2
		 *
		 * @return Charitable_Checklist
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
