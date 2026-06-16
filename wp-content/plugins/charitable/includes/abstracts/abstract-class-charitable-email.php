<?php
/**
 * Email model.
 *
 * @package   Charitable/Classes/Charitable_Email
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.60
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email' ) ) :

	/**
	 * Charitable_Email
	 *
	 * @since 1.0.0
	 */
	abstract class Charitable_Email implements Charitable_Email_Interface {

		/** Email ID */
		const ID = '';

		/**
		 * Descriptive name of the email.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $name;

		/**
		 * Array of supported object types (campaigns, donations, donors, etc).
		 *
		 * @since 1.0.0
		 *
		 * @var   string[]
		 */
		protected $object_types = array();

		/**
		 * Whether the email allows you to define the email recipients.
		 *
		 * @since 1.0.0
		 *
		 * @var   boolean
		 */
		protected $has_recipient_field = false;

		/**
		 * Whether the email is required.
		 *
		 * @since 1.4.0
		 *
		 * @var   boolean
		 */
		protected $required = false;

		/**
		 * The Donation object, if relevant.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Donation
		 */
		protected $donation;

		/**
		 * The Campaign object, if relevant.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Campaign
		 */
		protected $campaign;

		/**
		 * Email recipient.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $recipients;

		/**
		 * Email headers.
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		protected $headers;

		/**
		 * Email attachments.
		 *
		 * @since 1.6.59
		 *
		 * @var   string
		 */
		protected $attachments;

		/**
		 * Create a class instance.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed[] $objects Objects for the email.
		 */
		public function __construct( $objects = array() ) {
			$this->donation = isset( $objects['donation'] ) ? $objects['donation'] : null;
			$this->campaign = isset( $objects['campaign'] ) ? $objects['campaign'] : null;
		}

		/**
		 * Return an instance property.
		 *
		 * @since  1.5.0
		 *
		 * @param  key $property The property to return.
		 * @return mixed
		 */
		public function get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			}

			return '';
		}

		/**
		 * Return the email name.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * Return whether the email is required.
		 *
		 * If an email is required, it cannot be disabled/enabled, but it can still be edited.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function is_required() {
			return $this->required;
		}

		/**
		 * Return the types of objects.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		public function get_object_types() {
			return $this->object_types;
		}

		/**
		 * Return the donation object.
		 *
		 * @since  1.3.0
		 *
		 * @return null|Charitable_Donation
		 */
		public function get_donation() {
			return $this->donation;
		}

		/**
		 * Return the campaign object.
		 *
		 * @since  1.3.0
		 *
		 * @return null|Charitable_Campaign
		 */
		public function get_campaign() {
			return $this->campaign;
		}

		/**
		 * Get from name for email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_from_name() {
			return wp_specialchars_decode( charitable_get_option( 'email_from_name', get_option( 'blogname' ) ) );
		}

		/**
		 * Get from address for email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_from_address() {
			return charitable_get_option( 'email_from_email', get_option( 'admin_email' ) );
		}

		/**
		 * Return the email recipients.
		 *
		 * @since  1.0.0
		 * @since  1.8.9.8 Normalize array values to comma-separated string to prevent downstream TypeErrors.
		 *
		 * @return string
		 */
		public function get_recipient() {
			$recipient = $this->get_option( 'recipient', $this->get_default_recipient() );
			return is_array( $recipient ) ? implode( ', ', $recipient ) : $recipient;
		}

		/**
		 * Return the email subject line.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_subject() {
			/**
			 * Allow the email subject to be filtered.
			 *
			 * @since 1.6.60
			 *
			 * @param string            $subject The email subject.
			 * @param \Charitable_Email $email   The email object.
			 */
			return apply_filters(
				'charitable_email_subject',
				$this->get_option( 'subject', $this->get_default_subject() ),
				$this
			);
		}

		/**
		 * Get the email content type
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_content_type() {
			/**
			 * Filter the content type for the email.
			 *
			 * @since 1.0.0
			 *
			 * @param string            $content_type The content type. Defaults to 'text/html'.
			 * @param \Charitable_Email $email        This instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_content_type', 'text/html', $this );
		}

		/**
		 * Get the email headers.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_headers() {
			if ( ! isset( $this->headers ) ) {
				$this->headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
				$this->headers .= "Reply-To: {$this->get_from_address()}\r\n";
				$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
			}

			/**
			 * Filter the email headers.
			 *
			 * @since 1.0.0
			 *
			 * @param string            $headers The default email headers.
			 * @param \Charitable_Email $email   The email object.
			 */
			return apply_filters( 'charitable_email_headers', $this->headers, $this );
		}

		/**
		 * Get the email attachments.
		 *
		 * @since  1.6.59
		 *
		 * @return array
		 */
		public function get_attachments() {
			if ( ! isset( $this->attachments ) ) {
				$this->attachments = array();
			}

			/**
			 * Filter the email attachments.
			 *
			 * @since 1.6.59
			 *
			 * @param array             $attachments The default email attachments.
			 * @param \Charitable_Email $email   The email object.
			 */
			return apply_filters( 'charitable_email_attachments', $this->attachments, $this );
		}

		/**
		 * Checks whether we are currently previewing the email.
		 *
		 * @since  1.3.5
		 *
		 * @return boolean
		 */
		public function is_preview() {
			return isset( $_GET['charitable_action'] ) && 'preview_email' === $_GET['charitable_action']; // phpcs:ignore WordPress.Security.NonceVerification
		}

		/**
		 * Register email settings.
		 *
		 * @since  1.0.0
		 * @since  1.5.0 $settings argument is now deprecated.
		 * @since  1.5.7 $settings argument removed from function definition.
		 *
		 * @return array
		 */
		public function email_settings() {
			if ( func_num_args() ) {
				charitable_get_deprecated()->deprecated_argument(
					__METHOD__,
					'1.5.0',
					__( 'The `$settings` parameter is no longer used.', 'charitable' )
				);
			}

			$email_settings = array(
				'section_email' => array(
					'type'     => 'heading',
					'title'    => $this->get_name(),
					'priority' => 2,
				),
				'subject'       => array(
					'type'     => 'text',
					'title'    => __( 'Email Subject Line', 'charitable' ),
					'help'     => __( 'The email subject line when it is delivered to recipients.', 'charitable' ),
					'priority' => 6,
					'class'    => 'wide',
					'default'  => $this->get_default_subject(),
				),
				'headline'      => array(
					'type'     => 'text',
					'title'    => __( 'Email Headline', 'charitable' ),
					'help'     => __( 'The headline displayed at the top of the email.', 'charitable' ),
					'priority' => 10,
					'class'    => 'wide',
					'default'  => $this->get_default_headline(),
				),
				'body'          => array(
					'type'     => 'editor',
					'title'    => __( 'Email Body', 'charitable' ),
					'help'     => sprintf(
						'%s <div class="charitable-shortcode-options">%s</div>',
						__( 'The content of the email that will be delivered to recipients. HTML is accepted.', 'charitable' ),
						$this->get_shortcode_options()
					),
					'priority' => 14,
					'default'  => $this->get_default_body(),
				),
				'preview'       => array(
					'type'     => 'content',
					'title'    => __( 'Preview', 'charitable' ),
					'content'  => sprintf(
						'<a href="%s" target="_blank" class="button">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'charitable_action' => 'preview_email',
									'email_id'          => $this->get_email_id(),
								),
								home_url()
							)
						),
						__( 'Preview email', 'charitable' )
					),
					'priority' => 18,
					'save'     => false,
				),
			);

			// We need to add 'reply_to_donor' to the email settings if it's not already there but only for the new donation email.
			if ( 'new_donation' === $this->get_email_id() || 'offline_donation_notification' === $this->get_email_id() ) {
				$email_settings['reply_to_donor'] = array(
					'type'     => 'checkbox',
					'title'    => __( 'Reply To Donor', 'charitable' ),
					'help'     => __( 'If active the recipient will be able to thank the donor by replying to the email.', 'charitable' ),
					'priority' => 8,
					'class'    => 'wide',
					'default'  => 1,
				);
			}

			/* Add the recipients field if applicable to this email. */
			$email_settings = $this->add_recipients_field( $email_settings );

			/**
			 * Filter the settings available for this email.
			 *
			 * This filter is primarily useful for adding settings to specific email types.
			 * If you only want to add fields to all email types, use this hook instead:
			 *
			 * charitable_settings_fields_emails_email
			 *
			 * @see   Charitable_Email_Settings::add_individual_email_fields
			 *
			 * @since 1.0.0
			 *
			 * @param array $email_settings Email settings.
			 */
			return apply_filters( 'charitable_settings_fields_emails_email_' . $this->get_email_id(), $email_settings );
		}

		/**
		 * Add recipient field
		 *
		 * @since  1.0.0
		 *
		 * @param  array $settings Existing array of email settings.
		 * @return array
		 */
		public function add_recipients_field( $settings = array() ) {
			if ( ! $this->has_recipient_field ) {
				return $settings;
			}

			$settings['recipient'] = array(
				'type'     => 'text',
				'title'    => __( 'Recipients', 'charitable' ),
				'help'     => __( 'A comma-separated list of email address that will receive this email.', 'charitable' ),
				'priority' => 4,
				'class'    => 'wide',
				'default'  => $this->get_default_recipient(),
			);

			return $settings;
		}

		/**
		 * Sends the email.
		 *
		 * @since  1.0.0
		 * @since  1.8.9.2 Added comprehensive error handling for email processing failures.
		 *
		 * @return boolean
		 */
		public function send() {
			// AJAX DEFERRAL: Prevent email HTML from contaminating AJAX JSON response
			// when Oxygen page builder + Braintree + Recurring donations are active.
			if ( $this->should_defer_ajax_send() ) {
				return $this->defer_ajax_send();
			}

			/**
			 * Do something before sending the email.
			 *
			 * @since 1.0.0
			 *
			 * @param \Charitable_Email $email The email object.
			 */
			do_action( 'charitable_before_send_email', $this );

			try {
				// Safely get each email component with error handling
				$recipient = $this->safe_get_recipient();
				if ( false === $recipient ) {
					return false;
				}

				$subject = $this->safe_get_subject();
				if ( false === $subject ) {
					return false;
				}

				$body = $this->safe_build_email();
				if ( false === $body ) {
					return false;
				}

				$headers = $this->safe_get_headers();
				if ( false === $headers ) {
					return false;
				}

				$attachments = $this->safe_get_attachments();
				if ( false === $attachments ) {
					return false;
				}

				// Capture the actual error from wp_mail() failures.
				// WordPress fires 'wp_mail_failed' action with a WP_Error
				// containing the real SMTP/mailer error message.
				$wp_mail_error = null;
				$capture_error = function( $error ) use ( &$wp_mail_error ) {
					$wp_mail_error = $error;
				};
				add_action( 'wp_mail_failed', $capture_error );

				$sent = wp_mail( $recipient, $subject, $body, $headers, $attachments );

				remove_action( 'wp_mail_failed', $capture_error );

				if ( ! $sent ) {
					$error_detail = 'wp_mail() returned false';
					if ( $wp_mail_error instanceof \WP_Error ) {
						$error_detail .= ' — ' . $wp_mail_error->get_error_code() . ': ' . $wp_mail_error->get_error_message();
						$error_data = $wp_mail_error->get_error_data();
						if ( ! empty( $error_data ) ) {
							$error_detail .= ' | Data: ' . ( is_string( $error_data ) ? $error_data : wp_json_encode( $error_data ) );
						}
					}
					$this->log_email_error( 'wp_mail_failed', $error_detail );
				}

			} catch ( Throwable $e ) {
				$this->log_email_error( 'exception_during_send', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
				$sent = false;
			}

			/**
			 * Do something after sending the email.
			 *
			 * @since 1.0.0
			 *
			 * @param \Charitable_Email $email The email object.
			 */
			do_action( 'charitable_after_send_email', $this, $sent ?? false );

			return $sent ?? false;
		}

		/**
		 * Resend an email.
		 *
		 * @since  1.5.0
		 *
		 * @param  int   $object_id An object ID.
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function resend( $object_id, $args = array() ) {
			charitable_get_deprecated()->doing_it_wrong(
				__METHOD__,
				__( 'A `resend` method has not been defined for this class.', 'charitable' ),
				'1.5.0'
			);

			return false;
		}

		/**
		 * Checks whether an email can be resent.
		 *
		 * @since  1.5.0
		 *
		 * @param  int   $object_id An object ID.
		 * @param  array $args      Mixed set of arguments.
		 * @return boolean
		 */
		public static function can_be_resent( $object_id, $args = array() ) {
			charitable_get_deprecated()->doing_it_wrong(
				__METHOD__,
				__( 'A `can_be_resent` method has not been defined for this class.', 'charitable' ),
				'1.5.0'
			);

			return false;
		}

		/**
		 * Checks whether the email has already been sent.
		 *
		 * @since  1.3.2
		 * @since  1.5.2 Added the $data_type parameter.
		 *
		 * @param  int    $object_id The ID of the object related to this email. May be a campaign ID, a donation ID or a user ID.
		 * @param  string $data_type Optional. The type of meta we are saving. Defaults to 'post'.
		 * @return boolean
		 */
		public function is_sent_already( $object_id, $data_type = 'post' ) {
			$log = get_metadata( $data_type, $object_id, $this->get_log_key(), true );

			if ( is_array( $log ) ) {
				foreach ( $log as $time => $sent ) {
					if ( $sent ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Log that the email was sent.
		 *
		 * @since  1.3.2
		 * @since  1.5.2 Added the $data_type parameter.
		 *
		 * @param  int     $object_id The ID of the object related to this email. May be a campaign ID, a donation ID, or a user ID.
		 * @param  boolean $sent      Whether the email was sent.
		 * @param  string  $data_type Optional. The type of meta we are saving. Defaults to 'post'.
		 * @return void
		 */
		public function log( $object_id, $sent, $data_type = 'post' ) {
			$log = get_metadata( $data_type, $object_id, $this->get_log_key(), true );

			if ( ! $log ) {
				$log = array();
			}

			$log[ time() ] = $sent;

			update_metadata( $data_type, $object_id, $this->get_log_key(), $log );
		}

		/**
		 * Preview the email. This will display a sample email within the browser.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function preview() {
			/**
			 * Do something before building the preview output.
			 *
			 * @since 1.0.0
			 *
			 * @param \Charitable_Email $this Email object.
			 */
			do_action( 'charitable_before_preview_email', $this );

			$output = $this->build_email();

			/**
			 * Do something after building the preview output.
			 *
			 * @since 1.5.0
			 *
			 * @param \Charitable_Email $this Email object.
			 */
			do_action( 'charitable_after_preview_email', $this );

			return $output;
		}

		/**
		 * Returns the body content of the email, formatted as HTML.
		 *
		 * @since  1.0.0
		 * @version 1.8.4.3 add wp_kses_post and html_entity_decode.
		 *
		 * @return string
		 */
		public function get_body() {
			$body = $this->get_option( 'body', $this->get_default_body() );
			$body = do_shortcode( $body );
			$body = wpautop( $body );

			// Because this addition is applying to all emails potentially, let's add a temp global for any troubleshooting.
			if ( ! defined( 'CHARITABLE_EMAILS_DISABLE_HTML' ) || ! CHARITABLE_EMAILS_DISABLE_HTML ) {
				$body = wp_kses_post( $body );
				$body = html_entity_decode( $body );
			}

			/**
			 * Filter the email body before it is sent.
			 *
			 * @since 1.0.0
			 *
			 * @param string            $body  Body content.
			 * @param \Charitable_Email $email Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_body', $body, $this );
		}

		/**
		 * Returns the email headline.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_headline() {
			$headline = $this->get_option( 'headline', $this->get_default_headline() );
			$headline = do_shortcode( $headline );

			/**
			 * Filter the email headline.
			 *
			 * @since 1.0.0
			 *
			 * @param string            $headline Headline.
			 * @param \Charitable_Email $email    Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_headline', $headline, $this );
		}

		/**
		 * Return the value of an option specific to this email.
		 *
		 * @since  1.0.0
		 * @since  1.6.59 Access changed to public.
		 *
		 * @param  string $key     Settings option key.
		 * @param  mixed  $default Default value to return in case setting is not set.
		 * @return mixed
		 */
		public function get_option( $key, $default ) {
			return charitable_get_option( array( 'emails_' . $this->get_email_id(), $key ), $default );
		}

		/**
		 * Return an array of email fields that are specifically available
		 * for this email.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function email_fields() {
			return array();
		}

		/**
		 * Checks whether the email has a valid donation object set.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_valid_donation() {
			if ( is_null( $this->donation ) || ! is_a( $this->donation, 'Charitable_Donation' ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'You cannot send this email without a donation!', 'charitable' ),
					'1.0.0'
				);

				return false;
			}

			return true;
		}
		/**
		 * Checks whether the email has a valid campaign object set.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function has_valid_campaign() {
			if ( is_null( $this->campaign ) || ! is_a( $this->campaign, 'Charitable_Campaign' ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					__( 'You cannot send this email without a campaign!', 'charitable' ),
					'1.0.0'
				);

				return false;
			}

			return true;
		}

		/**
		 * Build the email.
		 *
		 * @since   1.0.0
		 *
		 * @return  string
		 */
		protected function build_email() {
			ob_start();

			charitable_template( 'emails/header.php', array( 'email' => $this ) );

			charitable_template( 'emails/body.php', array( 'email' => $this ) );

			charitable_template( 'emails/footer.php', array( 'email' => $this ) );

			$message = ob_get_clean();

			/**
			 * Filter the email message before it is sent.
			 *
			 * @since 1.0.0
			 *
			 * @param string            $message The full email message output (header, body and footer).
			 * @param \Charitable_Email $email   Instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_message', $message, $this );
		}

		/**
		 * Return the meta key used for the log.
		 *
		 * @since  1.3.2
		 *
		 * @return string
		 */
		protected function get_log_key() {
			return '_email_' . $this->get_email_id() . '_log';
		}

		/**
		 * Return the default recipient for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_recipient() {
			return '';
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_subject() {
			return '';
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_headline() {
			return '';
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		protected function get_default_body() {
			return '';
		}

		/**
		 * Return HTML formatted list of shortcode options that can be used within the body, headline and subject line.
		 *
		 * @since  version
		 * @version 1.8.9.1
		 *
		 * @return string
		 */
		protected function get_shortcode_options() {
			$fields = new Charitable_Email_Fields( $this );
			ob_start();
?>
			<p><?php esc_html_e( 'The following options are available with the <code>[charitable_email]</code> shortcode:', 'charitable' ); ?></p>
			<ul>
			<?php foreach ( $fields->get_fields() as $key => $field ) : ?>
				<li><strong><?php echo wp_kses_post( $field['description'] ); ?></strong>: [charitable_email show=<?php echo esc_html( $key ); ?>]</li>
			<?php endforeach; ?>
			</ul>

<?php
			$html = ob_get_clean();

			/**
			 * Filter the shortcode options block.
			 *
			 * @since 1.0.0
			 *
			 * @param string            $html  The content.
			 * @param \Charitable_Email $email This instance of `Charitable_Email`.
			 */
			return apply_filters( 'charitable_email_shortcode_options_text', $html, $this );
		}

		/**
		 * This function is deprecated as of 1.5.0. Checks whether the passed email is the
		 * same as the current email object.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.3.2
		 * @since  1.5.0 Deprecated. No notice added to allow extensions to be updated first.
		 *
		 * @param  \Charitable_Email $email Email object.
		 * @return boolean
		 */
		protected function is_current_email( Charitable_Email $email ) {
			return $email->get_email_id() == $this->get_email_id();
		}

		/**
		 * Add donation content fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_donation_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Add donation preview fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_preview_donation_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Add campaign content fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_campaign_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Add campaign preview fields.
		 *
		 * @deprecated 1.8.0
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated.
		 *
		 * @param  array            $fields Shortcode fields.
		 * @param  Charitable_Email $email  Instance of `Charitable_Email`.
		 * @return array[]
		 */
		public function add_preview_campaign_content_fields( $fields, Charitable_Email $email ) {
			return $fields;
		}

		/**
		 * Safely get email recipient with error handling.
		 *
		 * @since  1.8.9.2
		 * @since  1.8.9.5 Enhanced with detailed diagnostic context for recipient extraction failures.
		 * @since  1.8.9.8 Normalize array recipients from child class overrides before validation.
		 *
		 * @return string|false Email recipient or false on error.
		 */
		private function safe_get_recipient() {
			try {
				$recipient = $this->get_recipient();

				// Normalize array recipients (e.g. from child class overrides or filters) to comma-separated string.
				if ( is_array( $recipient ) ) {
					$recipient = implode( ', ', array_filter( array_map( 'trim', $recipient ) ) );
				}

				if ( empty( $recipient ) || ! $this->is_valid_recipient( $recipient ) ) {
					// Phase 1: Enhanced diagnostics - minimal overhead, only on errors
					$diagnostic_context = array(
						'recipient_value' => $recipient,
						'recipient_type' => gettype( $recipient ),
						'recipient_length' => is_string( $recipient ) ? strlen( $recipient ) : 0,
					);

					// Add donation context if available (safe checks)
					if ( is_object( $this->donation ) && method_exists( $this->donation, 'get_donor_id' ) ) {
						$donor_id = $this->donation->get_donor_id();
						$diagnostic_context['donation_id'] = method_exists( $this->donation, 'get_donation_id' ) ? $this->donation->get_donation_id() : 'unknown';
						$diagnostic_context['donor_id'] = $donor_id;
						$diagnostic_context['donor_id_type'] = gettype( $donor_id );

						// Check campaign donations count (common failure point)
						if ( method_exists( $this->donation, 'get_campaign_donations' ) ) {
							$campaign_donations = $this->donation->get_campaign_donations();
							$diagnostic_context['campaign_donations_count'] = is_array( $campaign_donations ) ? count( $campaign_donations ) : 'not_array';
						}

						// Check donor object validity if donor_id exists
						if ( ! empty( $donor_id ) && $donor_id !== false && method_exists( $this->donation, 'get_donor' ) ) {
							$donor = $this->donation->get_donor();
							$diagnostic_context['donor_object_type'] = gettype( $donor );
							$diagnostic_context['donor_object_class'] = is_object( $donor ) ? get_class( $donor ) : 'not_object';
						}
					} else {
						$diagnostic_context['donation_object_type'] = gettype( $this->donation );
					}

					$this->log_email_error( 'invalid_recipient',
						'Recipient email is empty or invalid - Enhanced diagnosis: ' . wp_json_encode( $diagnostic_context ) );
					return false;
				}

				return $recipient;

			} catch ( Throwable $e ) {
				$this->log_email_error( 'recipient_error', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
				return false;
			}
		}

		/**
		 * Safely get email subject with error handling.
		 *
		 * @since  1.8.9.2
		 *
		 * @return string|false Email subject or false on error.
		 */
		private function safe_get_subject() {
			try {
				// Capture any errors during shortcode processing
				$original_handler = null;
				if ( function_exists( 'set_error_handler' ) ) {
					$original_handler = set_error_handler( function( $severity, $message, $file, $line ) {
						// Skip deprecation warnings - they are not real errors (PHP 8.2+)
						if ( $severity === E_DEPRECATED || $severity === E_USER_DEPRECATED ) {
							return false;
						}
						// Skip warnings and notices - common in WordPress plugins (WPML, etc.)
						// and should not prevent email delivery.
						if ( $severity === E_WARNING || $severity === E_NOTICE || $severity === E_USER_WARNING || $severity === E_USER_NOTICE || $severity === E_STRICT ) {
							return false;
						}
						// Only convert actual user errors to exceptions
						throw new ErrorException( $message, 0, $severity, $file, $line );
					} );
				}

				$subject = do_shortcode( $this->get_subject() );

				if ( $original_handler !== null ) {
					restore_error_handler();
				}

				return $subject;

			} catch ( Throwable $e ) {
				if ( isset( $original_handler ) && $original_handler !== null ) {
					restore_error_handler();
				}
				$this->log_email_error( 'subject_error', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
				return false;
			}
		}

		/**
		 * Safely build email body with error handling.
		 *
		 * @since  1.8.9.2
		 * @since  1.8.9.5 Enhanced with detailed diagnostic context for email build failures.
		 *
		 * @return string|false Email body or false on error.
		 */
		private function safe_build_email() {
			try {
				// Set up error handler to catch fatal errors during email building
				$original_handler = null;
				if ( function_exists( 'set_error_handler' ) ) {
					$original_handler = set_error_handler( function( $severity, $message, $file, $line ) {
						// Skip deprecation warnings - they are not real errors (PHP 8.2+)
						if ( $severity === E_DEPRECATED || $severity === E_USER_DEPRECATED ) {
							return false;
						}
						// Skip warnings and notices - common in WordPress plugins (WPML, etc.)
						// and should not prevent email delivery.
						if ( $severity === E_WARNING || $severity === E_NOTICE || $severity === E_USER_WARNING || $severity === E_USER_NOTICE || $severity === E_STRICT ) {
							return false;
						}
						// Only convert actual user errors to exceptions
						throw new ErrorException( $message, 0, $severity, $file, $line );
					} );
				}

				$body = $this->build_email();

				if ( $original_handler !== null ) {
					restore_error_handler();
				}

				return $body;

			} catch ( Throwable $e ) {
				if ( isset( $original_handler ) && $original_handler !== null ) {
					restore_error_handler();
				}

				// Phase 1: Enhanced email build diagnostics
				$build_context = array(
					'email_class' => get_class( $this ),
					'email_id' => method_exists( $this, 'get_email_id' ) ? $this->get_email_id() : 'unknown',
					'error_message' => $e->getMessage(),
					'error_file' => basename( $e->getFile() ),
					'error_line' => $e->getLine(),
					'ob_level' => ob_get_level()
				);

				// Add donation context for template building
				if ( is_object( $this->donation ) ) {
					$build_context['has_donation_data'] = true;
					$build_context['donation_id'] = method_exists( $this->donation, 'get_donation_id' ) ? $this->donation->get_donation_id() : 'unknown';
				} else {
					$build_context['has_donation_data'] = false;
				}

				// Check template availability
				$template_dir = CHARITABLE_DIRECTORY_PATH . 'templates/emails/';
				$build_context['templates_exist'] = array(
					'header' => file_exists( $template_dir . 'header.php' ),
					'body' => file_exists( $template_dir . 'body.php' ),
					'footer' => file_exists( $template_dir . 'footer.php' )
				);

				$this->log_email_error( 'email_build_failed',
					'Email template build failed - Enhanced diagnosis: ' . wp_json_encode( $build_context ) );
				return false;
			}
		}

		/**
		 * Safely get email headers with error handling.
		 *
		 * @since  1.8.9.2
		 *
		 * @return string|array|false Email headers or false on error.
		 */
		private function safe_get_headers() {
			try {
				return $this->get_headers();
			} catch ( Throwable $e ) {
				$this->log_email_error( 'headers_error', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
				return false;
			}
		}

		/**
		 * Safely get email attachments with error handling.
		 *
		 * @since  1.8.9.2
		 *
		 * @return array|false Email attachments or false on error.
		 */
		private function safe_get_attachments() {
			try {
				$attachments = $this->get_attachments();
				return is_array( $attachments ) ? $attachments : array();
			} catch ( Throwable $e ) {
				$this->log_email_error( 'attachments_error', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
				return false;
			}
		}

		/**
		 * Log email processing errors using the existing donation form error logging system.
		 *
		 * @since  1.8.9.2
		 *
		 * @param  string $error_details Specific error details or code.
		 * @param  string $error_message The error message with context.
		 * @return int|false The activity ID on success, false on failure.
		 */
		private function log_email_error( $error_details, $error_message ) {
			// Only log if the function exists (defensive coding)
			if ( ! function_exists( 'charitable_log_form_error' ) ) {
				return false;
			}

			// Prepare context with email and donation details
			$context = array(
				'error_message' => $error_message,
				'email_class'   => get_class( $this ),
			);

			// Add donation context if available
			if ( isset( $this->donation ) && is_object( $this->donation ) ) {
				$context['donation_id'] = method_exists( $this->donation, 'get_donation_id' ) ? $this->donation->get_donation_id() : null;
				$context['campaign_id'] = method_exists( $this->donation, 'get_campaign_id' ) ? $this->donation->get_campaign_id() : null;
				$context['donor_id']    = method_exists( $this->donation, 'get_donor_id' ) ? $this->donation->get_donor_id() : null;
				$context['amount']      = method_exists( $this->donation, 'get_total_donation_amount' ) ? $this->donation->get_total_donation_amount() : null;
			}

			// Try to get recipient for context (safely) — normalize to string in case a subclass or filter returns an array.
			try {
				if ( method_exists( $this, 'get_recipient' ) ) {
					$raw_recipient         = $this->get_recipient();
					$context['recipient']  = is_array( $raw_recipient ) ? implode( ', ', $raw_recipient ) : $raw_recipient;
				}
			} catch ( Throwable $e ) {
				// Ignore if we can't get recipient
			}

			// Log using the existing error logging system
			return charitable_log_form_error( 'email_failure', $error_details, $context );
		}

		/**
		 * Check if recipient string is valid (handles single emails, comma-separated lists, and arrays).
		 *
		 * @since  1.8.9.5
		 * @since  1.8.9.8 Handle array recipients explicitly to prevent TypeError on PHP 8+.
		 *
		 * @param  string|array $recipient The recipient string or array to validate.
		 * @return boolean True if valid, false otherwise.
		 */
		private function is_valid_recipient( $recipient ) {
			if ( empty( $recipient ) ) {
				return false;
			}

			// Handle array recipients gracefully.
			if ( is_array( $recipient ) ) {
				foreach ( $recipient as $email ) {
					if ( empty( $email ) || ! is_email( trim( $email ) ) ) {
						return false;
					}
				}
				return true;
			}

			// If it's a single email, use is_email()
			if ( strpos( $recipient, ',' ) === false ) {
				return is_email( trim( $recipient ) );
			}

			// Handle comma-separated emails
			$emails = explode( ',', $recipient );
			foreach ( $emails as $email ) {
				$email = trim( $email );
				if ( empty( $email ) || ! is_email( $email ) ) {
					return false;
				}
			}

			return true;
		}
		/**
		 * Check if the current email should be deferred to avoid AJAX response contamination.
		 *
		 * Only defers when ALL of these conditions are true:
		 * - Running in AJAX context
		 * - Processing a donation form submission
		 * - Using Braintree gateway
		 * - Processing a recurring donation
		 * - Oxygen page builder is active (CT_VERSION defined)
		 *
		 * @since  1.8.9.6
		 *
		 * @return boolean
		 */
		private function should_defer_ajax_send() {
			if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return false;
			}

			if ( ! isset( $_POST['action'] ) || 'make_donation' !== $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			if ( ! isset( $_POST['gateway'] ) || 'braintree' !== $_POST['gateway'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			if ( ! isset( $_POST['recurring_donation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			if ( ! defined( 'CT_VERSION' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Defer email sending via WordPress cron to avoid AJAX contamination.
		 *
		 * @since  1.8.9.6
		 *
		 * @return boolean True if successfully scheduled.
		 */
		private function defer_ajax_send() {
			$email_data = array(
				'class'     => get_class( $this ),
				'args'      => $this->get_email_args_for_deferral(),
				'timestamp' => time(),
			);

			wp_schedule_single_event( time() + 5, 'charitable_send_deferred_email', array( $email_data ) );

			return true;
		}

		/**
		 * Get email constructor args for deferred sending.
		 *
		 * @since  1.8.9.6
		 *
		 * @return array
		 */
		private function get_email_args_for_deferral() {
			$args = array();

			if ( isset( $this->donation ) && is_object( $this->donation ) ) {
				if ( method_exists( $this->donation, 'get_donation_id' ) ) {
					$args['donation_id'] = $this->donation->get_donation_id();
				} elseif ( isset( $this->donation->ID ) ) {
					$args['donation_id'] = $this->donation->ID;
				}
			}

			if ( isset( $this->campaign ) && is_object( $this->campaign ) ) {
				if ( method_exists( $this->campaign, 'get_campaign_id' ) ) {
					$args['campaign_id'] = $this->campaign->get_campaign_id();
				} elseif ( isset( $this->campaign->ID ) ) {
					$args['campaign_id'] = $this->campaign->ID;
				}
			}

			return $args;
		}
	}

endif;
