<?php
/**
 * Class that sets up the emails.
 *
 * @version   1.5.0
 * @package   Charitable/Classes/Charitable_Emails
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Emails' ) ) :

	/**
	 * Charitable_Emails
	 *
	 * @since 1.0.0
	 */
	class Charitable_Emails {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Emails|null
		 */
		private static $instance = null;

		/**
		 * All available emails.
		 *
		 * @var string[]
		 */
		private $emails;

		/**
		 * Registration flag to prevent multiple calls.
		 *
		 * @since 1.8.9.2
		 * @var bool
		 */
		private $emails_registered = false;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the charitable_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			// Initialize state
			$this->emails_registered = false;
			$this->emails = null;

			/* 3rd party hook for overriding anything we've done so far. */
			do_action( 'charitable_emails_start', $this );

			add_action( 'init', array( $this, 'register_email_hooks' ) );
		}

		/**
		 * Register email hooks for donation status changes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function register_email_hooks() {
			foreach ( charitable_get_approval_statuses() as $approval_status ) {
				add_action( $approval_status . '_' . Charitable::DONATION_POST_TYPE, array( 'Charitable_Email_Donation_Receipt', 'send_with_donation_id' ) );
				add_action( $approval_status . '_' . Charitable::DONATION_POST_TYPE, array( 'Charitable_Email_New_Donation', 'send_with_donation_id' ) );
			}
		}

		/**
		 * Ensure emails are registered before accessing them.
		 * Implements lazy loading to handle timing race conditions.
		 *
		 * @since 1.8.9.2
		 * @return bool True if emails are available, false on failure.
		 */
		private function ensure_emails_registered() {
			// Quick check - if emails exist, we're done
			if ( is_array( $this->emails ) && ! empty( $this->emails ) ) {
				return true;
			}

			// Multiple detection methods for maximum compatibility
			if ( did_action( 'init' ) || doing_action( 'init' ) || wp_doing_ajax() || is_admin() ) {
				return $this->safe_register_emails();
			}

			// Fallback registration with error handling
			return $this->safe_register_emails();
		}

		/**
		 * Safely register emails with error handling and guard.
		 *
		 * @since 1.8.9.2
		 * @return bool True if registration successful, false otherwise.
		 */
		private function safe_register_emails() {
			try {
				$this->register_emails();
				return is_array( $this->emails ) && ! empty( $this->emails );
			} catch ( Exception $e ) {
				error_log( 'Charitable: Email registration failed - ' . $e->getMessage() );
				return false;
			}
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Emails
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Register Charitable emails.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function register_emails() {
			// Guard against multiple registrations
			if ( $this->emails_registered && is_array( $this->emails ) ) {
				return $this->emails;
			}

			$this->emails_registered = true;

			$this->emails = apply_filters(
				'charitable_emails',
				array(
					'donation_receipt'              => 'Charitable_Email_Donation_Receipt',
					'offline_donation_receipt'      => 'Charitable_Email_Offline_Donation_Receipt',
					'new_donation'                  => 'Charitable_Email_New_Donation',
					'campaign_end'                  => 'Charitable_Email_Campaign_End',
					'offline_donation_notification' => 'Charitable_Email_Offline_Donation_Notification',
					'password_reset'                => 'Charitable_Email_Password_Reset',
					'email_verification'            => 'Charitable_Email_Email_Verification',
				)
			);

			return $this->emails;
		}

		/**
		 * Register admin actions for default emails.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function register_admin_actions() {
			$donation_actions = charitable_get_donation_actions();

			/**
			 * Filter the list of resendable donation emails.
			 *
			 * @since 1.5.0
			 *
			 * @param string[] $emails The list of emails and their label.
			 */
			$emails = apply_filters(
				'charitable_resendable_donation_emails',
				array(
					'donation_receipt',
					'new_donation',
					'offline_donation_receipt',
					'offline_donation_notification',
				)
			);

			foreach ( $emails as $email ) {
				$class  = $this->get_email( $email );
				$object = new $class();

				$donation_actions->register(
					'resend_' . $email,
					array(
						'label'           => $object->get_name(),
						'callback'        => array( $this, 'resend_email' ),
						'button_text'     => __( 'Resend Email', 'charitable' ),
						'active_callback' => array( $class, 'can_be_resent' ),
						'success_message' => 11,
						'failed_message'  => 12,
					),
					__( 'Resend Donation Emails', 'charitable' )
				);
			}
		}

		/**
		 * Resend an email.
		 *
		 * This is the callback for all of the resend email actions.
		 *
		 * @since  1.5.0
		 *
		 * @param  boolean $success   Whether the action has been successfully completed.
		 * @param  int     $object_id An object ID.
		 * @param  array   $args      Mixed set of arguments.
		 * @param  string  $action    The action we are executing.
		 * @return boolean
		 */
		public function resend_email( $success, $object_id, $args, $action ) {
			$email  = str_replace( 'resend_', '', $action );
			$class  = $this->get_email( $email );
			$object = new $class();

			return $object->resend( $object_id, $args );
		}

		/**
		 * Receives a request to enable or disable an email and validates it before passing it off.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function handle_email_settings_request() {
			if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'email' ) ) { // phpcs:ignore
				wp_die( __( 'Cheatin\' eh?!', 'charitable' ) ); // phpcs:ignore
			}

			$email = isset( $_REQUEST['email_id'] ) ? $_REQUEST['email_id'] : false; // phpcs:ignore

			/* Gateway must be set */
			if ( false === $email ) {
				wp_die( __( 'Missing email.', 'charitable' ) ); // phpcs:ignore
			}

			/* Ensure emails are registered. */
			if ( ! $this->ensure_emails_registered() ) {
				wp_die( __( 'Email system not available.', 'charitable' ) );
			}

			/* Validate email. */
			if ( ! isset( $this->emails[ $email ] ) ) {
				wp_die( __( 'Invalid email.', 'charitable' ) ); // phpcs:ignore
			}

			/* All good, so disable or enable the email */
			if ( 'charitable_disable_email' == current_filter() ) {
				$this->disable_email( $email );
			} else {
				$this->enable_email( $email );
			}
		}

		/**
		 * Returns all available emails.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_available_emails() {
			if ( ! $this->ensure_emails_registered() ) {
				return null; // Changed from array() for better error detection
			}
			return $this->emails;
		}

		/**
		 * Returns the currently enabled emails.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_enabled_emails() {
			$enabled = charitable_get_option( 'enabled_emails', array() );

			/* The Password Reset & Email Verification emails are always enabled. */
			$enabled['password_reset']     = 'Charitable_Email_Password_Reset';
			$enabled['email_verification'] = 'Charitable_Email_Email_Verification';

			return $enabled;
		}

		/**
		 * Returns a list of the names of currently enabled emails.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		public function get_enabled_emails_names() {
			$emails = array();

			foreach ( $this->get_enabled_emails() as $class ) {
				if ( ! class_exists( $class ) ) {
					continue;
				}

				$email                            = new $class();
				$emails[ $email->get_email_id() ] = $email->get_name();
			}

			return $emails;
		}

		/**
		 * Return the email class name for a given email.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $email The email to return.
		 * @return string|false A string representing the email class name if the
		 *                      email is registered; false if it is not registered.
		 */
		public function get_email( $email ) {
			if ( ! $this->ensure_emails_registered() ) {
				return false;
			}
			return isset( $this->emails[ $email ] ) ? $this->emails[ $email ] : false;
		}

		/**
		 * Returns whether the passed email is enabled.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $email_id The email to check for.
		 * @return boolean
		 */
		public function is_enabled_email( $email_id ) {
			return array_key_exists( $email_id, $this->get_enabled_emails() );
		}

		/**
		 * Enable an email.
		 *
		 * @since  1.0.0
		 * @version 1.8.2 - Added array check.
		 *
		 * @param  string $email The email to be enabled.
		 * @return void
		 */
		protected function enable_email( $email ) {
			$settings = get_option( 'charitable_settings', array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
			$enabled_emails             = isset( $settings['enabled_emails'] ) ? $settings['enabled_emails'] : array();
			$enabled_emails[ $email ]   = $this->emails[ $email ];
			$settings['enabled_emails'] = $enabled_emails;

			update_option( 'charitable_settings', $settings );

			Charitable_Settings::get_instance()->add_update_message( __( 'Email enabled', 'charitable' ), 'success' );

			do_action( 'charitable_email_enable', $email );
		}

		/**
		 * Disable an email.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $email The email to be disabled.
		 * @return void
		 */
		protected function disable_email( $email ) {
			$settings = get_option( 'charitable_settings' );

			if ( ! isset( $settings['enabled_emails'][ $email ] ) ) {
				return;
			}

			unset( $settings['enabled_emails'][ $email ] );

			update_option( 'charitable_settings', $settings );

			Charitable_Settings::get_instance()->add_update_message( __( 'Email disabled', 'charitable' ), 'success' );

			do_action( 'charitable_email_disable', $email );
		}
	}

endif;
