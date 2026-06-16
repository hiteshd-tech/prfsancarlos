<?php
/**
 * The main Charitable CAPTCHA class.
 *
 * @package   Charitable/Classes/Charitable_Captcha_Google_ReCAPTCHA_V3
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.8.9
 * @since     1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Captcha_Google_ReCAPTCHA_V3' ) ) :

	/**
	 * Google reCAPTCHA v3 module.
	 *
			 * @since 1.8.9
	 */
	class Charitable_Captcha_Google_ReCAPTCHA_V3 {

		/**
		 * Class constructor.
		 *
		 * @since 1.8.9
		 */
		public function __construct() {
			if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
				$provider = charitable_get_option( 'captcha_provider', 'not_set' );
				$site_key = $this->get_site_key();
				$secret_key = $this->get_secret_key();
				error_log( '[Charitable CAPTCHA v3] Constructor called | Provider: ' . $provider . ' | Site Key: ' . ( ! empty( $site_key ) ? 'SET' : 'EMPTY' ) . ' | Secret Key: ' . ( ! empty( $secret_key ) ? 'SET' : 'EMPTY' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			if ( $this->is_active() ) {
				if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
					error_log( '[Charitable CAPTCHA v3] Module is active, calling setup()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$this->setup();
			} elseif ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
				error_log( '[Charitable CAPTCHA v3] Module is NOT active, setup() not called' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}

		/**
		 * Return whether the module is active.
		 *
		 * @since  1.8.0
		 *
		 * @return boolean
		 */
		public function is_active() {
			return 'google-recaptcha-v3' === charitable_get_option( 'captcha_provider' )
				&& ! empty( $this->get_site_key() )
				&& ! empty( $this->get_secret_key() );
		}

		/**
		 * Get the module settings.
		 *
		 * @since  1.8.0
		 *
		 * @return array
		 */
		public function get_settings() {
			return array(
				'recaptcha_v3_site_key'   => array(
					'title'    => __( 'Google reCAPTCHA v3 Site Key', 'charitable' ),
					'type'     => 'text',
					'class'    => 'wide',
					'help'     => __( 'Your reCAPTCHA v3 "Site key" setting. Find this at <a href="https://www.google.com/recaptcha/admin" target="_blank">www.google.com/recaptcha/admin</a>. Make sure to create a reCAPTCHA v3 site, not v2.', 'charitable' ),
					'priority' => 125,
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'google-recaptcha-v3',
						'style'              => 'width: 100%;',
					),
				),
				'recaptcha_v3_secret_key' => array(
					'title'    => __( 'Google reCAPTCHA v3 Secret Key', 'charitable' ),
					'type'     => 'text',
					'class'    => 'wide',
					'help'     => __( 'Your reCAPTCHA v3 "Secret key" setting. Find this at <a href="https://www.google.com/recaptcha/admin" target="_blank">www.google.com/recaptcha/admin</a>.', 'charitable' ),
					'priority' => 129,
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'google-recaptcha-v3',
						'style'              => 'width: 100%;',
					),
				),
				'recaptcha_v3_score_threshold' => array(
					'title'    => __( 'Score Threshold', 'charitable' ),
					'type'     => 'select',
					'options'  => array(
						'normal'     => __( 'Normal (0.50)', 'charitable' ),
						'aggressive' => __( 'Aggressive (0.80)', 'charitable' ),
					),
					'help'     => __( 'Determines how lenient judgement should be on suspected bot usage. Normal allows more submissions, Aggressive is stricter.', 'charitable' ),
					'priority' => 133,
					'default'  => 'normal',
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'google-recaptcha-v3',
					),
				),
			);
		}

		/**
		 * Get the site key.
		 *
		 * @since  1.8.0
		 *
		 * @return string
		 */
		public function get_site_key() {
			return charitable_get_option( 'recaptcha_v3_site_key' );
		}

		/**
		 * Get the secret key.
		 *
		 * @since  1.8.0
		 *
		 * @return string
		 */
		public function get_secret_key() {
			return charitable_get_option( 'recaptcha_v3_secret_key' );
		}

		/**
		 * Get the score threshold.
		 *
		 * @since  1.8.0
		 *
		 * @return float
		 */
		public function get_score_threshold() {
			$threshold = charitable_get_option( 'recaptcha_v3_score_threshold', 'normal' );

			switch ( $threshold ) {
				case 'aggressive':
					$minimum_score = 0.80;
					break;
				case 'normal':
				default:
					$minimum_score = 0.50;
					break;
			}

			/**
			 * Filter the minimum score allowed for a reCAPTCHA v3 response to allow form submission.
			 *
			 * @since 1.8.9
			 *
			 * @param float $minimum_score Minimum score (0.0 to 1.0).
			 */
			return apply_filters( 'charitable_recaptcha_v3_minimum_score', $minimum_score );
		}

		/**
		 * Set up module hooks.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function setup() {
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 15 );

			/**
			 * For the password retrieval, password reset, profile and registration
			 * forms, we check recaptcha before the regular form processor occurs.
			 *
			 * If the recaptcha check fails, we prevent further processing.
			 */
			add_action( 'charitable_retrieve_password', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_reset_password', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_update_profile', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_save_registration', array( $this, 'check_recaptcha_before_form_processing' ), 1 );

			/**
			 * For the donation form, validate recaptcha as part of the security check.
			 */
			add_filter( 'charitable_validate_donation_form_submission_security_check', array( $this, 'validate_recaptcha' ), 10, 2 );
		}

		/**
		 * Add reCAPTCHA v3 script.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function add_scripts() {
			$site_key = $this->get_site_key();

			if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
				error_log( '[Charitable CAPTCHA v3] add_scripts() called | Site Key: ' . ( ! empty( $site_key ) ? 'SET' : 'EMPTY' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			if ( empty( $site_key ) ) {
				if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
					error_log( '[Charitable CAPTCHA v3] Site key is empty, not enqueuing scripts' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				return;
			}

			// Enqueue reCAPTCHA v3 script with site key.
			$url = add_query_arg(
				array(
					'render' => $site_key,
				),
				'https://www.google.com/recaptcha/api.js'
			);

			wp_enqueue_script(
				'charitable-recaptcha-v3',
				esc_url( $url ),
				array(),
				'v3',
				true
			);

			// Enqueue our custom handler.
			wp_register_script(
				'charitable-recaptcha-v3-handler',
				charitable()->get_path( 'assets', false ) . 'js/security/charitable-recaptcha-v3.js',
				array( 'jquery', 'charitable-recaptcha-v3' ),
				charitable()->get_version(),
				true
			);

			wp_enqueue_script( 'charitable-recaptcha-v3-handler' );

			wp_localize_script(
				'charitable-recaptcha-v3-handler',
				'CHARITABLE_RECAPTCHA_V3',
				array(
					'site_key'      => $site_key,
					'action'        => 'charitable_donation',
					'error_message' => __( 'Your form submission failed because the captcha failed to be validated.', 'charitable' ),
				)
			);
		}

		/**
		 * Returns an array of forms that reCAPTCHA v3 can be enabled for.
		 *
		 * @since  1.8.0
		 *
		 * @return array
		 */
		public function get_form_settings() {
			/**
			 * Returns an array of forms along with whether reCAPTCHA v3
			 * is enabled for them. By default, all forms are enabled.
			 *
			 * @since 1.8.9
			 *
			 * @param array $forms All the supported forms in a key=>value array, where the value is either
			 *                     true (reCAPTCHA v3 is enabled) or false (reCAPTCHA v3 is disabled).
			 */
			return apply_filters(
				'charitable_recaptcha_v3_forms',
				array(
					'donation_form'           => true,
					'donation_amount_form'    => false,
					'registration_form'       => true,
					'password_reset_form'     => true,
					'password_retrieval_form' => true,
					'profile_form'            => false,
					'campaign_form'           => true,
				)
			);
		}

		/**
		 * Return the current form key based on the class name.
		 *
		 * @since  1.8.0
		 *
		 * @param  Charitable_Form $form A form object.
		 * @return string|null Form key if it's a supported form. Null otherwise.
		 */
		public function get_current_form_from_class( Charitable_Form $form ) {
			switch ( get_class( $form ) ) {
				case 'Charitable_Registration_Form':
					$form_key = 'registration_form';
					break;

				case 'Charitable_Profile_Form':
					$form_key = 'profile_form';
					break;

				case 'Charitable_Forgot_Password_Form':
					$form_key = 'password_retrieval_form';
					break;

				case 'Charitable_Reset_Password_Form':
					$form_key = 'password_reset_form';
					break;

				case 'Charitable_Donation_Form':
					$form_key = 'donation_form';
					break;

				case 'Charitable_Donation_Amount_Form':
					$form_key = 'donation_amount_form';
					break;

				case 'Charitable_Ambassadors_Campaign_Form':
					$form_key = 'campaign_form';
					break;

				default:
					$form_key = null;
			}

			return $form_key;
		}

		/**
		 * Return the form key based on the hook.
		 *
		 * @since  1.8.0
		 *
		 * @return string|null
		 */
		public function get_current_form_from_hook() {
			switch ( current_filter() ) {
				case 'charitable_save_registration':
					$form_key = 'registration_form';
					break;

				case 'charitable_update_profile':
					$form_key = 'profile_form';
					break;

				case 'charitable_retrieve_password':
					$form_key = 'password_retrieval_form';
					break;

				case 'charitable_reset_password':
					$form_key = 'password_reset_form';
					break;

				case 'charitable_save_campaign':
					$form_key = 'campaign_form';
					break;

				default:
					$form_key = null;
			}
			return $form_key;
		}

		/**
		 * Returns whether reCAPTCHA v3 is enabled for the given form.
		 *
		 * @since  1.8.0
		 *
		 * @param  string|null $form_key The key of the form, or NULL if it's not a supported one.
		 * @return boolean
		 */
		public function is_enabled_for_form( $form_key ) {
			if ( is_null( $form_key ) ) {
				return false;
			}

			$forms = $this->get_form_settings();

			return isset( $forms[ $form_key ] ) && $forms[ $form_key ];
		}

		/**
		 * Check reCAPTCHA v3 token validity before processing a form submission.
		 *
		 * If the reCAPTCHA v3 check fails, form processing is blocked.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function check_recaptcha_before_form_processing() {
			/* Don't show captcha for logged in users. */
			if ( is_user_logged_in() && 'no' === charitable_get_option( 'captcha_logged_in', 'no' ) ) {
				return;
			}

			$form_key = $this->get_current_form_from_hook();

			if ( $this->is_enabled_for_form( $form_key ) && ! $this->is_captcha_valid() ) {
				switch ( $form_key ) {
					case 'registration_form':
						remove_action( 'charitable_save_registration', array( 'Charitable_Registration_Form', 'save_registration' ) );
						break;

					case 'profile_form':
						remove_action( 'charitable_update_profile', array( 'Charitable_Profile_Form', 'update_profile' ) );
						break;

					case 'password_retrieval_form':
						remove_action( 'charitable_retrieve_password', array( 'Charitable_Forgot_Password_Form', 'retrieve_password' ) );
						break;

					case 'password_reset_form':
						remove_action( 'charitable_reset_password', array( 'Charitable_Reset_Password_Form', 'reset_password' ) );
						break;

					case 'campaign_form':
						remove_action( 'charitable_save_campaign', array( 'Charitable_Ambassadors_Campaign_Form', 'save_campaign' ) );
						break;
				}
			}
		}

		/**
		 * Validate the reCAPTCHA v3 token.
		 *
		 * @since  1.8.0
		 *
		 * @param  boolean                  $ret  The result to be returned. True or False.
		 * @param  Charitable_Donation_Form $form The donation form object.
		 * @return boolean
		 */
		public function validate_recaptcha( $ret, Charitable_Donation_Form $form ) {
			if ( ! $ret ) {
				return $ret;
			}

			/* Don't show captcha for logged in users. */
			if ( is_user_logged_in() && 'no' === charitable_get_option( 'captcha_logged_in', 'no' ) ) {
				return $ret;
			}

			if ( ! $this->is_enabled_for_form( $this->get_current_form_from_class( $form ) ) ) {
				return $ret;
			}

			return $this->is_captcha_valid();
		}

		/**
		 * Returns whether the reCAPTCHA v3 token is valid.
		 *
		 * @since  1.8.0
		 *
		 * @return boolean
		 */
		public function is_captcha_valid() {
			if ( ! isset( $_POST['charitable_recaptcha_v3_token'] ) || empty( $_POST['charitable_recaptcha_v3_token'] ) ) { // phpcs:ignore
				charitable_get_notices()->add_error( __( 'Missing captcha token.', 'charitable' ) );
				return false;
			}

			$token = sanitize_text_field( wp_unslash( $_POST['charitable_recaptcha_v3_token'] ) ); // phpcs:ignore

			$response = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'body' => array(
						'secret'   => $this->get_secret_key(),
						'response' => $token,
						'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '', // phpcs:ignore
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				charitable_get_notices()->add_error( __( 'Failed to verify captcha.', 'charitable' ) );
				return false;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ), true );

			// Check if request was successful.
			if ( ! isset( $result['success'] ) || ! $result['success'] ) {
				charitable_get_notices()->add_error( __( 'Captcha validation failed.', 'charitable' ) );
				return false;
			}

			// Check if score is available (v3 specific).
			if ( ! isset( $result['score'] ) ) {
				charitable_get_notices()->add_error( __( 'Captcha score not available.', 'charitable' ) );
				return false;
			}

			// Check if action matches (optional but recommended).
			if ( isset( $result['action'] ) && 'charitable_donation' !== $result['action'] ) {
				charitable_get_notices()->add_error( __( 'Captcha action mismatch.', 'charitable' ) );
				return false;
			}

			// Check score against threshold.
			$score        = floatval( $result['score'] );
			$threshold    = $this->get_score_threshold();
			$score_passed = $score >= $threshold;

			if ( ! $score_passed ) {
				charitable_get_notices()->add_error(
					sprintf(
						/* translators: %1$s: score, %2$s: threshold */
						__( 'Captcha score (%1$s) is below the required threshold (%2$s).', 'charitable' ),
						number_format( $score, 2 ),
						number_format( $threshold, 2 )
					)
				);
			}

			return $score_passed;
		}
	}

endif;

