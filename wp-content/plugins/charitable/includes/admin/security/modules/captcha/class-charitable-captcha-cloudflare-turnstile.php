<?php
/**
 * The main Charitable CAPTCHA class.
 *
 * @package   Charitable/Classes/Charitable_Captcha_Cloudflare_Turnstile
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.8.9
 * @since     1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Captcha_Cloudflare_Turnstile' ) ) :

	/**
	 * Cloudflare Turnstile module.
	 *
			 * @since 1.8.9
	 */
	class Charitable_Captcha_Cloudflare_Turnstile {

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
				error_log( '[Charitable CAPTCHA Turnstile] Constructor called | Provider: ' . $provider . ' | Site Key: ' . ( ! empty( $site_key ) ? 'SET' : 'EMPTY' ) . ' | Secret Key: ' . ( ! empty( $secret_key ) ? 'SET' : 'EMPTY' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			if ( $this->is_active() ) {
				if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
					error_log( '[Charitable CAPTCHA Turnstile] Module is active, calling setup()' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$this->setup();
			} elseif ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
				error_log( '[Charitable CAPTCHA Turnstile] Module is NOT active, setup() not called' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
			return 'cloudflare-turnstile' === charitable_get_option( 'captcha_provider' )
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
				'cloudflare_turnstile_site_key'   => array(
					'title'    => __( 'Cloudflare Turnstile Site Key', 'charitable' ),
					'type'     => 'text',
					'class'    => 'wide',
					'help'     => __( 'Your Cloudflare Turnstile "Site key" setting. Find this in <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">your Cloudflare dashboard</a>.', 'charitable' ),
					'priority' => 152,
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'cloudflare-turnstile',
						'style'              => 'width: 100%;',
					),
				),
				'cloudflare_turnstile_secret_key' => array(
					'title'    => __( 'Cloudflare Turnstile Secret Key', 'charitable' ),
					'type'     => 'text',
					'class'    => 'wide',
					'help'     => __( 'Your Cloudflare Turnstile "Secret key" setting. Find this in <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">your Cloudflare dashboard</a>.', 'charitable' ),
					'priority' => 156,
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => 'cloudflare-turnstile',
						'style'              => 'width: 100%;',
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
			return charitable_get_option( 'cloudflare_turnstile_site_key' );
		}

		/**
		 * Get the secret key.
		 *
		 * @since  1.8.0
		 *
		 * @return string
		 */
		public function get_secret_key() {
			return charitable_get_option( 'cloudflare_turnstile_secret_key' );
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
			add_action( 'charitable_form_after_fields', array( $this, 'add_turnstile_to_form' ) );

			/* For the donation form, validate token as part of the security check. */
			add_filter( 'charitable_validate_donation_form_submission_security_check', array( $this, 'validate_token_for_donation' ), 10, 2 );

			/**
			 * For the password retrieval, password reset, profile and registration
			 * forms, we check turnstile before the regular form processor occurs.
			 *
			 * If the turnstile check fails, we prevent further processing.
			 */
			add_action( 'charitable_retrieve_password', array( $this, 'check_turnstile_before_form_processing' ), 1 );
			add_action( 'charitable_reset_password', array( $this, 'check_turnstile_before_form_processing' ), 1 );
			add_action( 'charitable_update_profile', array( $this, 'check_turnstile_before_form_processing' ), 1 );
			add_action( 'charitable_save_registration', array( $this, 'check_turnstile_before_form_processing' ), 1 );
		}

		/**
		 * Add Cloudflare Turnstile scripts.
		 *
		 * @since  1.8.0
		 *
		 * @return void
		 */
		public function add_scripts() {
			// Cloudflare Turnstile requires loading from their CDN - this is the official API endpoint.
			wp_enqueue_script(
				'charitable-cloudflare-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js', // phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent
				array(),
				'v0',
				true
			);
		}

		/**
		 * Add Cloudflare Turnstile widget to the form.
		 *
		 * @since  1.8.0
		 *
		 * @param  Charitable_Form $form A form object.
		 * @return void
		 */
		public function add_turnstile_to_form( Charitable_Form $form ) {
			if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
				$form_class = get_class( $form );
				$form_key = $this->get_current_form_from_class( $form );
				$is_enabled = $this->is_enabled_for_form( $form_key );
				error_log( '[Charitable CAPTCHA Turnstile] add_turnstile_to_form() called | Form Class: ' . $form_class . ' | Form Key: ' . ( $form_key ? $form_key : 'NULL' ) . ' | Enabled: ' . ( $is_enabled ? 'YES' : 'NO' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			/* Don't show captcha for logged in users. */
			if ( is_user_logged_in() && 'no' === charitable_get_option( 'captcha_logged_in', 'no' ) ) {
				if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
					error_log( '[Charitable CAPTCHA Turnstile] Skipping - user is logged in and captcha_logged_in is "no"' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				return;
			}

			if ( $this->is_enabled_for_form( $this->get_current_form_from_class( $form ) ) ) {
				if ( charitable_is_debug() && charitable_is_debug( 'security' ) ) {
					error_log( '[Charitable CAPTCHA Turnstile] Adding Turnstile widget to form' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$form_key = $this->get_current_form_from_class( $form );
				$action   = $this->get_action_for_form( $form_key );

				ob_start();
				?>
				<div class="charitable-turnstile cf-turnstile" data-sitekey="<?php echo esc_attr( $this->get_site_key() ); ?>" data-action="charitable-form-<?php echo esc_attr( $action ); ?>"></div>
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
		 * Get action name for form.
		 *
		 * @since  1.8.0
		 *
		 * @param  string $form_key The form key.
		 * @return string
		 */
		public function get_action_for_form( $form_key ) {
			switch ( $form_key ) {
				case 'donation_form':
					return 'donation';
				case 'registration_form':
					return 'registration';
				case 'password_reset_form':
					return 'password_reset';
				case 'password_retrieval_form':
					return 'pwd_retrieval';
				case 'profile_form':
					return 'profile';
				case 'campaign_form':
					return 'campaign';
				default:
					return 'form';
			}
		}

		/**
		 * Returns whether Turnstile is enabled for the given form.
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
		 * Returns an array of forms that Turnstile can be enabled for.
		 *
		 * @since  1.8.0
		 *
		 * @return array
		 */
		public function get_form_settings() {
			/**
			 * Returns an array of forms along with whether Turnstile
			 * is enabled for them. By default, all forms are enabled.
			 *
			 * @since 1.8.9
			 *
			 * @param array $forms All the supported forms in a key=>value array, where the value is either
			 *                     true (Turnstile is enabled) or false (Turnstile is disabled).
			 */
			return apply_filters(
				'charitable_cloudflare_turnstile_forms',
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
		 * Validate the Turnstile token on donation form submission.
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

			return $this->is_captcha_valid( 'donation' );
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
		public function check_turnstile_before_form_processing() {
			/* Don't show captcha for logged in users. */
			if ( is_user_logged_in() && 'no' === charitable_get_option( 'captcha_logged_in', 'no' ) ) {
				return;
			}

			$form_key = $this->get_current_form_from_hook();

			if ( ! $this->is_enabled_for_form( $form_key ) ) {
				return;
			}

			$action = $this->get_action_for_form( $form_key );

			/* Captcha token isn't valid. */
			if ( ! $this->is_captcha_valid( $action ) ) {
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
		 * @param  string $action The form action name.
		 * @return boolean
		 */
		public function is_captcha_valid( $action = 'form' ) {
			// Cloudflare Turnstile automatically creates a hidden input with name 'cf-turnstile-response'.
			if ( ! isset( $_POST['cf-turnstile-response'] ) || empty( $_POST['cf-turnstile-response'] ) ) { // phpcs:ignore
				charitable_get_notices()->add_error( __( 'Missing captcha token.', 'charitable' ) );
				return false;
			}

			$token = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ); // phpcs:ignore

			$response = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
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

			if ( ! isset( $result['success'] ) || ! $result['success'] ) {
				charitable_get_notices()->add_error( __( 'Captcha validation failed.', 'charitable' ) );
				return false;
			}

			// Check if action matches (optional but recommended).
			$expected_action = 'charitable-form-' . $action;
			if ( isset( $result['action'] ) && $result['action'] !== $expected_action ) {
				charitable_get_notices()->add_error( __( 'Captcha action mismatch.', 'charitable' ) );
				return false;
			}

			return true;
		}
	}

endif;

