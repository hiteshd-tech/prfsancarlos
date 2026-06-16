<?php
/**
 * Charitable Onboarding.
 *
 * @package   Charitable/Classes/Charitable_Onboarding
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.12
 * @version   1.8.1.12
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Onboarding' ) ) :

	/**
	 * Charitable_Onboarding
	 *
	 * @final
	 * @since 1.8.1.12
	 */
	class Charitable_Onboarding {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Onboarding|null
		 */
		private static $instance = null;


		/**
		 * The installed plugins.
		 *
		 * @var array
		 */
		private $checklist_option_name = 'charitable_setup_checklist_completed_steps';


		/**
		 * Create object instance.
		 *
		 * @since 1.8.1.12
		 */
		public function __construct() {
		}

		/**
		 * Run things upon init.
		 *
		 * @since 1.8.1.12
		 *
		 * @return void
		 */
		public function init() {
		}

		/**
		 * Enqueue assets.
		 *
		 * @since   1.8.1.12
		 */
		public function enqueue_scripts() {

			$min        = charitable_get_min_suffix();
			$version    = charitable()->get_version();
			$assets_dir = charitable()->get_path( 'assets', false );

			/* The following styles are only loaded on Charitable screens. */
			$screen = get_current_screen();

			if ( ( ! empty( $_GET['page'] ) && 'charitable-setup-checklist' === $_GET['page'] ) ) { // phpcs:ignore

				wp_enqueue_style(
					'charitable-setup-checklist',
					$assets_dir . 'css/setup-checklist/setup-checklist' . $min . '.css',
					array(),
					$version
				);

			}
		}

		/**
		 * Check if a step is completed.
		 *
		 * This is how we tell when a step is completed on the checklist.
		 *
		 * "plugin-config"          - if the user has saved general settings at least once.
		 * "opt-in"                 - if the user has opted in to the newsletter/sharing data.
		 * "connect-payment"        - if any payment gateway is connected.
		 * "first-campaign"         - if a campaign has been created.
		 * "fundraising-next-level" - when pro is active or ???
		 *
		 * @since 1.8.1.12
		 *
		 * @param string $step The step to check.
		 *
		 * @return bool
		 */
		public function is_step_completed( $step = '' ) {

			if ( empty( $step ) ) {
				return false;
			}

			// we store this as an option, so load the option and check the array key.
			$completed_steps = get_option( $this->checklist_option_name, array() );
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
						return true;
					}
					break;

				default:
					// code...
					break;
			}

			return false;
		}

		/**
		 * Check if the first campaign step is completed.
		 *
		 * @since 1.8.1.12
		 *
		 * @return bool
		 */
		public function check_step_first_campaign() {
			if ( $this->is_step_completed( 'first-campaign' ) ) {
				return true;
			}

			// if there is at least one campaign, this is completed.
			$campaigns = charitable_get_table( 'campaign' )->count();
			if ( $campaigns > 0 ) {
				$this->mark_step_completed( 'first-campaign' );
				return true;
			}

			return false;
		}

		/**
		 * Check if the first donation step is completed.
		 *
		 * @since 1.8.2
		 *
		 * @return bool
		 */
		public function check_step_first_donation() {
			if ( $this->is_step_completed( 'first-donation' ) ) {
				return true;
			}
			if ( ! isset( $_GET['post_type'] ) || 'donation' !== $_GET['post_type'] ) { // phpcs:ignore
				return false;
			}

			$this->mark_step_completed( 'first-campaign' );

			return true;
		}

		/**
		 * Mark a step as completed.
		 *
		 * @since 1.8.1.12
		 *
		 * @param string $step The step to mark as completed.
		 *
		 * @return bool
		 */
		public function mark_step_completed( $step = '' ) {
			if ( empty( $step ) ) {
				return false;
			}

			// we store this as an option, so load the option and check the array key.
			$completed_steps = get_option( $this->checklist_option_name, array() );
			if ( ! in_array( $step, $completed_steps ) ) {
				$completed_steps[] = $step;
				update_option( $this->checklist_option_name, $completed_steps );
			}

			return true;
		}



		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1.12
		 *
		 * @return Charitable_Onboarding
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
