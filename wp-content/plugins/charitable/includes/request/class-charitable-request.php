<?php
/**
 * Class used to provide information about the current request.
 *
 * @package   Charitable/Classes/Charitable_Request
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.58
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Request' ) ) :

	/**
	 * Charitable_Request.
	 *
	 * @since 1.0.0
	 * @final
	 */
	final class Charitable_Request {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Request|null
		 */
		private static $instance = null;

		/**
		 * Campaign object.
		 *
		 * @var Charitable_Campaign|false
		 */
		private $campaign;

		/**
		 * Original campaign object.
		 *
		 * @var Charitable_Campaign|false
		 */
		private $original_campaign;

		/**
		 * Original campaign ID.
		 *
		 * @var int
		 */
		private $original_campaign_id;

		/**
		 * Campaign ID.
		 *
		 * @var int
		 */
		private $campaign_id;

		/**
		 * Donation object.
		 *
		 * @var Charitable_Donation
		 */
		private $donation;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the on_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			/* Set the current campaign on the_post hook. */
			add_action( 'the_post', array( $this, 'set_current_campaign' ) );

			/* Add any supported donation parameters to the session. */
			add_action( 'charitable_is_donate_page', array( $this, 'add_donation_params_to_session' ) );

			/* Set the current campaign before a donation form is displayed, and reset to original after. */
			add_action( 'charitable_donation_form_before', array( $this, 'set_current_campaign_before_donation_form' ) );
			add_action( 'charitable_donation_form_after', array( $this, 'reset_current_campaign_after_donation_form' ) );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Request
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * When the_post is set, sets the current campaign to the current post if it is a campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  WP_Post $post The Post object.
		 * @return void
		 */
		public function set_current_campaign( $post ) {
			if ( 'campaign' == $post->post_type ) {
				$this->campaign = new Charitable_Campaign( $post );
			} else {
				unset( $this->campaign, $this->campaign_id );
			}
		}

		/**
		 * Returns the current campaign. If there is no current campaign, return false.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Campaign|false Campaign object if we're viewing a campaign within a loop. False otherwise.
		 */
		public function get_current_campaign() {
			if ( ! isset( $this->campaign ) ) {
				if ( $this->get_current_campaign_id() > 0 ) {
					$this->campaign = new Charitable_Campaign( $this->get_current_campaign_id() );
				} else {
					$this->campaign = false;
				}
			}

			return $this->campaign;
		}

		/**
		 * Returns the current campaign ID. If there is no current campaign, return 0.
		 *
		 * @since  1.0.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return int
		 */
		public function get_current_campaign_id() {
			global $wp_query;

			if ( isset( $this->campaign ) && $this->campaign ) {
				$this->campaign_id = $this->campaign->ID;
			} else {
				$this->campaign_id = 0;

				if ( get_post_type() == Charitable::CAMPAIGN_POST_TYPE ) {
					$this->campaign_id = get_the_ID();
				} elseif ( $wp_query && $wp_query->get( 'donate', false ) ) {
					$session_donation = charitable_get_session()->get( 'donation' );

					if ( false !== $session_donation ) {
						$this->campaign_id = $session_donation->get( 'campaign_id' );
					}
				}
			}//end if

			if ( ! $this->campaign_id ) {
				$this->campaign_id = $this->get_campaign_id_from_submission();
			}

			return $this->campaign_id;
		}

		/**
		 * Returns the current campaign template. If there is no current template (like with legacy 1.x campaigns), return 0.
		 *
		 * @since  1.8.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return string
		 */
		public function get_current_campaign_template() {

			$campaign_settings = get_post_meta( $this->get_current_campaign_id(), 'campaign_settings_v2', true );

			if ( false === $campaign_settings ) { // This is a legacy campaign. Probably.
				return false;
			}

			$campaign_template_id = ! empty( $campaign_settings['template_id'] ) ? esc_attr( $campaign_settings['template_id'] ) : false;

			$templates_data = get_option( 'charitable_campaign_builder_templates' );
			// if false, then the template data was not stored and we will need to get it manually.
			if ( false === $templates_data ) {
				require_once charitable()->get_path( 'includes' ) .  'admin/campaign-builder/templates/class-templates.php';
				$builder_template = new Charitable_Campaign_Builder_Templates();
				$templates_data   = $builder_template->get_templates_data( $campaign_template_id, $campaign_settings );
			}

			$template_data = ! empty( $templates_data['templates'][ $campaign_template_id ] ) ? $templates_data['templates'][ $campaign_template_id ] : false;

			return $template_data;
		}

		/**
		 * Returns the current campaign template. If there is no current template (like with legacy 1.x campaigns), return 0.
		 *
		 * @since  1.8.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return string
		 */
		public function get_current_campaign_template_id() {

			$campaign_settings    = get_post_meta( $this->get_current_campaign_id(), 'campaign_settings_v2', true );
			$campaign_template_id = ! empty( $campaign_settings['template_id'] ) ? esc_attr( $campaign_settings['template_id'] ) : false;

			return $campaign_template_id;
		}

		/**
		 * Returns the current campaign template. If there is no current template (like with legacy 1.x campaigns), return 0.
		 *
		 * @since  1.8.0
		 *
		 * @global WP_Query $wp_query
		 *
		 * @return string
		 */
		public function get_current_campaign_builder_data() {
			global $wp_query;

			$campaign_settings = get_post_meta( $this->get_current_campaign_id(), 'campaign_settings_v2', true );

			return $campaign_settings;
		}



		/**
		 * Returns the campaign ID from a form submission.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_campaign_id_from_submission() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( ! isset( $_POST['campaign_id'] ) ) {
				return 0;
			}

			$campaign_id = absint( sanitize_text_field( wp_unslash( $_POST['campaign_id'] ) ) );
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( Charitable::CAMPAIGN_POST_TYPE !== get_post_type( $campaign_id ) ) {
				return 0;
			}

			return $campaign_id;
		}

		/**
		 * Returns the current donation object. If there is no current donation, return false.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donation|false
		 */
		public function get_current_donation() {
			if ( ! isset( $this->donation ) ) {
				$donation_id    = $this->get_current_donation_id();
				$this->donation = $donation_id ? charitable_get_donation( $donation_id ) : false;
			}

			return $this->donation;
		}

	/**
	 * Returns the current donation ID. If there is no current donation, return 0.
	 *
	 * @since  1.0.0
	 * @version 1.8.9.1
	 *
	 * @return int
	 */
	public function get_current_donation_id() {
		$donation_id = get_query_var( 'donation_id', 0 );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! $donation_id && isset( $_GET['donation_id'] ) ) {
			$donation_id = absint( sanitize_text_field( wp_unslash( $_GET['donation_id'] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $donation_id;
	}

	/**
	 * If set, add supported donation parameters to the session.
	 *
	 * @since  1.6.25
	 * @version 1.8.9.1
	 *
	 * @param  int $campaign_id The campaign receiving the donation.
	 * @return void
	 */
	public function add_donation_params_to_session( $campaign_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( array_key_exists( 'amount', $_REQUEST ) ) {
			$amount = sanitize_text_field( wp_unslash( $_REQUEST['amount'] ) );
			$period = array_key_exists( 'period', $_REQUEST ) ? sanitize_text_field( wp_unslash( $_REQUEST['period'] ) ) : 'once';

			charitable_get_session()->add_donation( $campaign_id, $amount, $period );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

		/**
		 * Temporarily set the current campaign to the one for the donation form.
		 *
		 * @since  1.6.55
		 *
		 * @param  Charitable_Donation_Form $form The donation form object.
		 * @return void
		 */
		public function set_current_campaign_before_donation_form( Charitable_Donation_form $form ) {
			if ( isset( $this->campaign_id ) && $this->campaign_id === $form->get_campaign()->ID ) {
				return;
			}

			if ( isset( $this->campaign ) ) {
				$this->original_campaign = $this->campaign;
			}

			if ( isset( $this->campaign_id ) ) {
				$this->original_campaign_id = $this->campaign_id;
			}

			$this->campaign    = $form->get_campaign();
			$this->campaign_id = $this->campaign->ID;
		}

		/**
		 * Reset the $campaign and $campaign_id properties to what they originally were.
		 *
		 * @since  1.6.55
		 *
		 * @return void
		 */
		public function reset_current_campaign_after_donation_form() {
			$this->campaign    = $this->original_campaign;
			$this->campaign_id = $this->original_campaign ? $this->original_campaign->ID : false;
		}
	}

endif;
