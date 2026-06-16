<?php
/**
 * Responsible for documenting notices, errors, etc. for a donation.
 *
 * @package   Charitable/Classes/Charitable_Donation_Notice
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



if ( ! class_exists( 'Charitable_Donation_Notices' ) ) :

	/**
	 * Charitable_Donation_Notice
	 *
	 * @since 1.8.1.6
	 */
	class Charitable_Donation_Notices {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Donation_Notices|null
		 */
		private static $instance = null;

		/**
		 * The donation ID.
		 *
		 * @since 1.8.1.6
		 *
		 * @var   int
		 */
		private $donation_id;

		/**
		 * Create class object.
		 *
		 * @since 1.8.1.6
		 */
		public function __construct() {
		}

		/**
		 * Add security check error total.
		 *
		 * @since  1.8.1.6
		 *
		 * @param boolean                  $ret Whether to return the value true/false, passed in and not modified.
		 * @param Charitable_Donation_Form $form The donation form.
		 */
		public function update_security_check_error_total( $ret = true, $form = false ) {

			// if the passed value is true (from validate_security_check() ), then the check passed and we can exit.
			if ( true === $ret ) {
				return $ret;
			}

			$donation_security_checks = get_transient( 'charitable_donation_security_checks' );

			if ( ! is_array( $donation_security_checks ) ) {
				$donation_security_checks = array();
			}

			$campaign    = $form->get_campaign(); // Charitable_Campaign object.
			$campaign_id = ! empty( $campaign->get_campaign_id() ) ? $campaign->get_campaign_id() : 0;

			if ( 0 === $campaign_id ) {
				return $ret;
			}

			if ( isset( $donation_security_checks[ $campaign_id ] ) ) {
				++$donation_security_checks[ $campaign_id ];
			} else {
				$donation_security_checks[ $campaign_id ] = 1;
			}

			$time_span = apply_filters( 'charitable_donation_security_checks_log_length', 7 * DAY_IN_SECONDS );

			// if the transients doesn't exist, create one that lasts 7 days.
			set_transient( 'charitable_donation_security_checks', $donation_security_checks, $time_span );

			return $ret;
		}

		/**
		 * Get dashboard notices.
		 *
		 * @since 1.8.1.6
		 * @version 1.8.1.9 // Updated doc URL.
		 * @version 1.8.2 // added dashboard notification related code.
		 */
		public function get_dashboard_notices() {

			// Until there's a global dashboard notice system, we'll just tap into dashboard notifications.

			$dashboard_notices        = get_option( 'charitable_dashboard_notifications', array() );
			$donation_security_checks = get_transient( 'charitable_donation_security_checks' );

			// if the 'donation_security_checks' already exists in the dashboard notices, don't add it again.
			if ( isset( $dashboard_notices['donation_security_checks'] ) ) {
				return $dashboard_notices;
			}

			if ( is_array( $donation_security_checks ) && ! empty( $donation_security_checks ) && count( $donation_security_checks ) > 0 ) {
				if ( empty( $dashboard_notices ) || ! is_array( $dashboard_notices ) ) {
					$dashboard_notices = array();
				}
				$dashboard_notices['donation_security_checks'] = array(
					'type'       => 'warning',
					'dismiss'    => false,
					'title'      => esc_html__( 'Important', 'charitable' ), // phpcs:ignore
					'custom_css' => 'charitable-notification-type-important',
					'message'    => sprintf( '<p><strong>%s</strong></p>', esc_html__( 'Charitable has noticed some recent donation attempts via a donation form have failed due to invalid security checks.', 'charitable' ) ) .
								sprintf( '<ul class="charitable-notice-recommendations"><li>%s</li>', esc_html__( 'If you have any caching plugins installed, try deactivating to test or add any relevant URLs to any "bypass" list in the cache plugin\'s settings.', 'charitable' ) ) .
								sprintf( '<li>%s <strong>%s</strong>.</li>', esc_html__( 'Depending on your theme and/or plugins you can manually clear cache on pages to test the donation form. Example: ', 'charitable' ), esc_html__( 'test.com/campaign/donate?cache=123', 'charitable' ) ) .
								sprintf( '<li><a href="%s">%s</a> %s <a href="%s">%s</a> %s</li>', 'https://www.wpcharitable.com/documentation/charitable-has-noticed-some-recent-donation-attempts-via-the-donation-form-have-failed-due-to-invalid-security-checks/', esc_html__( 'Please read our documentation.', 'charitable' ), esc_html__( 'Also feel free to ', 'charitable' ), 'https://www.wpcharitable.com/support/', esc_html__( 'contact our support team', 'charitable' ), esc_html__( 'if you need further assistance.', 'charitable' ) ) .
								sprintf( '</ul>' ),
				);
			}

			update_option( 'charitable_dashboard_notifications', $dashboard_notices );

			return $dashboard_notices;
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.8.1.6
		 *
		 * @return Charitable_Upgrade
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
