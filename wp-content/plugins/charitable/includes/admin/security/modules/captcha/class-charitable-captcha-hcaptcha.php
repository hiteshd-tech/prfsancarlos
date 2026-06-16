<?php
/**
 * The main Charitable CAPTCHA class.
 *
 * @package   Charitable/Classes/Charitable_Captcha_HCaptcha
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.8.9
 * @since     1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Captcha_HCaptcha' ) ) :

	/**
	 * hCaptcha module.
	 *
			 * @since 1.8.9
	 */
	class Charitable_Captcha_HCaptcha {

		/**
		 * Class constructor.
		 *
		 * @since 1.8.9
		 */
		public function __construct() {
			if ( $this->is_active() ) {
				$this->setup();
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
			return 'hcaptcha' === charitable_get_option( 'captcha_provider' )
				&& ! empty( $this->get_sitekey() )
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
				'hcaptcha_sitekey'    => array(
					'title'    => __( 'hCaptcha Site Key', 'charitable' ),
					'type'     => 'text',
					'class'    => 'wide',
					'help'     => __( 'Your hCaptcha "Sitekey" setting. Find this in <a href="https://dashboard.hcaptcha.com/sites" target="_blank">your hCaptcha dashboard</a>.', 'charitable' ),
					'priority' => 144,
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'hcaptcha',
						'style'              => 'width: 100%;',
					),
				),
				'hcaptcha_secret_key' => array(
					'title'    => __( 'hCaptcha Secret Key', 'charitable' ),
					'type'     => 'password',
					'class'    => 'wide',
					'help'     => __( 'Your hCaptcha Secret Key setting can be found in the Settings page in your hCaptcha dashboard.', 'charitable' ),
					'priority' => 148,
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'hcaptcha',
						'style'              => 'width: 100%;',
					),
				),
			);
		}

		/**
		 * Get the sitekey.
		 *
		 * @since  1.8.0
		 *
		 * @return string
		 */
		public function get_sitekey() {
			return charitable_get_option( 'hcaptcha_sitekey' );
		}

		/**
		 * Get the secret key.
		 *
		 * @since  1.8.0
		 *
		 * @return string
		 */
		public function get_secret_key() {
			return charitable_get_option( 'hcaptcha_secret_key' );
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
			add_action( 'charitable_form_after_fields', array( $this, 'add_hcaptcha_to_form' ) );

			/* For the donation form, validate token as part of the security check. */
			add_filter( 'charitable_validate_donation_form_submission_security_check', array( $this, 'validate_token_for_donation' ), 10, 2 );

			/**
			 * For the password retrieval, password reset, profile and registration
			 * forms, we check hcaptcha before the regular form processor occurs.
			 *
			 * If the hcaptcha check fails, we prevent further processing.
			 */
			add_action( 'charitable_retrieve_password', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_reset_password', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_update_profile', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
			add_action( 'charitable_save_registration', array( $this, 'check_recaptcha_before_form_processing' ), 1 );
		}

		/**
		 * Add hCaptcha scripts.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function add_scripts() {
			wp_register_script( 'charitable-hcaptcha', charitable()->get_path( 'assets', false ) . 'js/security/charitable-hcaptcha.js', array( 'jquery' ), charitable()->get_version(), true );
			wp_enqueue_script( 'charitable-hcaptcha' );
			wp_localize_script(
				'charitable-hcaptcha',
				'CHARITABLE_HCAPTCHA',
				array(
					'sitekey'       => $this->get_sitekey(),
					'error_message' => __( 'Your form submission failed because the captcha failed to be validated.', 'charitable' ),
				)
			);
		}

		/**
		 * Add hCaptcha widget to the form.
		 *
		 * @since  1.8.0
		 *
		 * @param  Charitable_Form $form A form object.
		 * @return void
		 */
		public function add_hcaptcha_to_form( Charitable_Form $form ) {
			/* Don't show captcha for logged in users. */
			if ( is_user_logged_in() && 'no' === charitable_get_option( 'captcha_logged_in', 'no' ) ) {
				return;
			}

			if ( $this->is_enabled_for_form( $this->get_current_form_from_class( $form ) ) ) {
				ob_start();
				?>
				<div class="charitable-hcaptcha" id="hcaptcha-<?php echo esc_attr( $form->get_form_identifier() ); ?>" data-sitekey="<?php echo esc_attr( $this->get_sitekey() ); ?>"></div>
				<input name="hcaptcha_token" type="hidden" value="" />
				<script src="https://hcaptcha.com/1/api.js?onload=charitable_hcaptcha_onload&render=explicit" async defer></script> <?php // phpcs:ignore ?>
				<?php
				echo ob_get_clean(); // phpcs:ignore
			}
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
		 * Returns whether hCaptcha is enabled for the given form.
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
		 * Returns an array of forms that hCaptcha can be enabled for.
		 *
		 * @since  1.8.0
		 *
		 * @return array
		 */
		public function get_form_settings() {
			/**
			 * Returns an array of forms along with whether hCaptcha
			 * is enabled for them. By default, all forms are enabled.
			 *
			 * @since 1.8.9
			 *
			 * @param array $forms All the supported forms in a key=>value array, where the value is either
			 *                     true (hCaptcha is enabled) or false (hCaptcha is disabled).
			 */
			return apply_filters(
				'charitable_hcaptcha_forms',
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
		 * Validate the hCaptcha token on donation form submission.
		 *
		 * @since  1.8.0
		 *
		 * @param  boolean                  $ret  The result to be returned. True or False.
		 * @param  Charitable_Donation_Form $form The donation form object.
		 * @return boolean
		 */
		public function validate_token_for_donation( $ret, Charitable_Donation_Form $form ) {
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
		 * Check token validity before processing a form submission.
		 *
		 * If the check fails, form processing is blocked.
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

			if ( ! $this->is_enabled_for_form( $form_key ) ) {
				return;
			}

			/* Captcha token isn't valid. */
			if ( ! $this->is_captcha_valid() ) {
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
		 * Returns whether the captcha is valid.
		 *
		 * @since  1.8.0
		 *
		 * @return boolean
		 */
		public function is_captcha_valid() {
			if ( ! isset( $_POST['hcaptcha_token'] ) || empty( $_POST['hcaptcha_token'] ) ) { // phpcs:ignore
				charitable_get_notices()->add_error( __( 'Missing captcha token.', 'charitable' ) );
				return false;
			}

			$token = sanitize_text_field( wp_unslash( $_POST['hcaptcha_token'] ) ); // phpcs:ignore

			$response = wp_remote_post(
				'https://hcaptcha.com/siteverify',
				array(
					'body' => array(
						'secret'   => $this->get_secret_key(),
						'response' => $token,
						'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '', // phpcs:ignore
						'sitekey'  => $this->get_sitekey(),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				charitable_get_notices()->add_error( __( 'Failed to verify captcha.', 'charitable' ) );
				return false;
			}

			$result = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! isset( $result['success'] ) || ! $result['success'] ) {
				charitable_get_notices()->add_error( __( 'Captcha validation failed.', 'charitable' ) );
				return false;
			}

			return true;
		}
	}

endif;

