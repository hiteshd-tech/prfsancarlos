<?php
/**
 * Class that models a simple test email for diagnostics.
 *
 * @package   Charitable/Classes/Charitable_Email_Test
 * @author    Claude Code
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9.2
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Test' ) ) :

	/**
	 * Test Email for Diagnostics
	 *
	 * @since   1.8.9.2
	 */
	class Charitable_Email_Test extends Charitable_Email {

		/** Email ID */
		const ID = 'test_email';

		/**
		 * Target email address for the test.
		 *
		 * @since 1.8.9.2
		 *
		 * @var   string
		 */
		protected $test_recipient;

		/**
		 * Array of supported object types (none needed for test).
		 *
		 * @since 1.8.9.2
		 *
		 * @var   string[]
		 */
		protected $object_types = array();

		/**
		 * Instantiate the email class, defining its key values.
		 *
		 * @since 1.8.9.2
		 *
		 * @param array $objects an array containing test parameters.
		 */
		public function __construct( $objects = array() ) {
			parent::__construct( $objects );

			$this->name = apply_filters( 'charitable_email_test_name', __( 'Test Email', 'charitable' ) );
			$this->test_recipient = isset( $objects['recipient'] ) ? $objects['recipient'] : get_option( 'admin_email' );
		}

		/**
		 * Returns the current email's ID.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  string
		 */
		public static function get_email_id() {
			return self::ID;
		}

		/**
		 * Static method to send a test email to the admin email address.
		 *
		 * @since   1.8.9.2
		 *
		 * @param   string $recipient_email Optional. Email address to send test to. Defaults to admin email.
		 * @return  array Array with 'success' boolean and 'message' string.
		 */
		public static function send_test_email( $recipient_email = '' ) {
			if ( empty( $recipient_email ) ) {
				$recipient_email = get_option( 'admin_email' );
			}

			if ( ! is_email( $recipient_email ) ) {
				return array(
					'success' => false,
					'message' => sprintf( __( 'Invalid email address: %s', 'charitable' ), $recipient_email ),
				);
			}

			$email = new self(
				array(
					'recipient' => $recipient_email,
				)
			);

			$sent = $email->send();

			if ( $sent ) {
				return array(
					'success' => true,
					'message' => sprintf( __( 'Test email sent successfully to %s using Charitable\'s email system.', 'charitable' ), $recipient_email ),
				);
			} else {
				return array(
					'success' => false,
					'message' => sprintf( __( 'Failed to send test email to %s. This indicates an issue with Charitable\'s email system or your server\'s email configuration.', 'charitable' ), $recipient_email ),
				);
			}
		}

		/**
		 * Return the recipient for the email.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  string
		 */
		public function get_recipient() {
			return apply_filters( 'charitable_email_test_recipient', $this->test_recipient, $this );
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  string
		 */
		protected function get_default_subject() {
			$subject = sprintf( __( 'Test Email from %s - Charitable Plugin', 'charitable' ), get_bloginfo( 'name' ) );
			return apply_filters( 'charitable_email_test_default_subject', $subject, $this );
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  string
		 */
		protected function get_default_headline() {
			return apply_filters( 'charitable_email_test_default_headline', __( 'Charitable Email System Test', 'charitable' ), $this );
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  string
		 */
		protected function get_default_body() {
			$charitable_logo_html = $this->get_charitable_logo_html();

			ob_start();
			?>
<div style="text-align: center; margin: 20px 0;">
	<?php echo $charitable_logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>

<p><?php esc_html_e( 'Hello!', 'charitable' ); ?></p>

<p><?php esc_html_e( 'This is a test email sent from the Charitable plugin to verify that the email system is working correctly.', 'charitable' ); ?></p>

<p><strong><?php esc_html_e( 'Test Details:', 'charitable' ); ?></strong></p>
<ul>
	<li><?php esc_html_e( 'Website:', 'charitable' ); ?> [charitable_email show=site_name] ([charitable_email show=site_url])</li>
	<li><?php esc_html_e( 'Sent via:', 'charitable' ); ?> <?php esc_html_e( 'Charitable Email System', 'charitable' ); ?></li>
	<li><?php esc_html_e( 'Date/Time:', 'charitable' ); ?> <?php echo esc_html( current_time( 'mysql' ) ); ?></li>
	<li><?php esc_html_e( 'Charitable Version:', 'charitable' ); ?> <?php echo esc_html( charitable()->get_version() ); ?></li>
</ul>

<p><?php esc_html_e( 'If you received this email, it means Charitable\'s email system is functioning properly on your website.', 'charitable' ); ?></p>

<p><em><?php esc_html_e( 'This test email was sent from the Charitable Tools page in your WordPress admin.', 'charitable' ); ?></em></p>
			<?php
			$body = ob_get_clean();

			return apply_filters( 'charitable_email_test_default_body', $body, $this );
		}

		/**
		 * Get Charitable logo HTML for email.
		 *
		 * @since   1.8.9.2
		 *
		 * @return  string
		 */
		private function get_charitable_logo_html() {
			$logo_url = charitable()->get_path( 'directory', false ) . 'assets/images/charitable-logo.png';

			// Check if logo file exists, fallback to text
			if ( file_exists( charitable()->get_path( 'directory' ) . 'assets/images/charitable-logo.png' ) ) {
				return sprintf(
					'<img src="%s" alt="Charitable" style="max-width: 200px; height: auto;" />',
					esc_url( $logo_url )
				);
			} else {
				return '<div style="font-size: 24px; font-weight: bold; color: #0073aa;">Charitable</div>';
			}
		}

		/**
		 * Checks whether an email can be resent.
		 *
		 * @since  1.8.9.2
		 *
		 * @param  int   $object_id An object ID (not used for test emails).
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function can_be_resent( $object_id, $args = array() ) {
			return true; // Test emails can always be "resent"
		}
	}

endif;