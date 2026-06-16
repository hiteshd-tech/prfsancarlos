<?php
/**
 * The main Charitable CAPTCHA class.
 *
 * @package   Charitable/Classes/Charitable_Captcha
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.8.9
 * @since     1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Captcha' ) ) :

	/**
	 * CAPTCHA module.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Captcha {

		/**
		 * Return whether the module is active.
		 *
		 * @since  1.8.9
		 *
		 * @return boolean
		 */
		public function is_active() {
			return 'disabled' !== charitable_get_option( 'captcha_provider' );
		}

		/**
		 * Get the module settings.
		 *
		 * @since  1.8.9
		 *
		 * @return array
		 */
		public function get_settings() {
			return array(
				'security_section_header' => array(
					'title'    => __( 'Security', 'charitable' ),
					'type'     => 'heading',
					'priority' => 1,
				),
				'captcha_section_header'  => array(
					'title'    => __( 'Captcha', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'section-heading',
					'priority' => 100,
				),
				'captcha_provider'        => array(
					'title'    => __( 'Captcha Provider', 'charitable' ),
					'type'     => 'select',
					'options'  => array(
						'disabled'            => __( 'Disabled', 'charitable' ),
						'google-recaptcha'     => __( 'Google reCAPTCHA (Invisible V2)', 'charitable' ),
						'google-recaptcha-v3' => __( 'Google reCAPTCHA v3 (Invisible)', 'charitable' ),
						'hcaptcha'             => __( 'hCaptcha', 'charitable' ),
						'cloudflare-turnstile' => __( 'Cloudflare Turnstile', 'charitable' ),
					),
					'priority' => 104,
					'default'  => 'disabled',
				),
				'captcha_logged_in'       => array(
					'title'    => __( 'Require captcha for logged-in users', 'charitable' ),
					'type'     => 'radio',
					'options'  => array(
						'yes' => __( 'Yes', 'charitable' ),
						'no'  => __( 'No', 'charitable' ),
					),
					'priority' => 108,
					'default'  => 'no',
					'attrs'    => array(
						'data-trigger-key'   => '#charitable_settings_captcha_provider',
						'data-trigger-value' => '!disabled',
					),
				),
			);
		}
	}

endif;

