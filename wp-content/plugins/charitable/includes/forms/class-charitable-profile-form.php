<?php
/**
 * Class that manages the display and processing of the profile form.
 *
 * @package   Charitable/Classes/Charitable_Profile_Form
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.51
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Profile_Form' ) ) :

	/**
	 * Charitable_Profile_Form
	 *
	 * @since 1.0.0
	 */
	class Charitable_Profile_Form extends Charitable_Form {

		/**
		 * Shortcode parameters.
		 *
		 * @var array
		 */
		protected $shortcode_args;

		/**
		 * @var string
		 */
		protected $nonce_action = 'charitable_user_profile';

		/**
		 * @var string
		 */
		protected $nonce_name = '_charitable_user_profile_nonce';

		/**
		 * Action to be executed upon form submission.
		 *
		 * @var string
		 */
		protected $form_action = 'update_profile';

		/**
		 * The current user.
		 *
		 * @var Charitable_User
		 */
		protected $user;

		/**
		 * Create class object.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args User-defined shortcode attributes.
		 */
		public function __construct( $args = array() ) {
			$this->id             = uniqid();
			$this->shortcode_args = $args;

			/* For backwards-compatibility */
			add_action( 'charitable_form_field', array( $this, 'render_field' ), 10, 6 );

			add_filter( 'charitable_user_fields', array( $this, 'add_avatar_field' ), 10, 2 );
			add_filter( 'charitable_profile_update_values', array( $this, 'save_avatar' ), 10, 3 );
		}

		/**
		 * Return the current user's Charitable_User object.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_User
		 */
		public function get_user() {
			if ( ! isset( $this->user ) ) {
				$this->user = new Charitable_User( wp_get_current_user() );
			}

			return $this->user;
		}

		/**
		 * Returns the value of a particular key.
		 *
		 * @since  1.0.0
		 * @version 1.8.9.1
		 *
		 * @param  string $key     The key of the data we are searching for.
		 * @param  string $default Optional. The value that will be used if none is set.
		 * @return mixed
		 */
		public function get_user_value( $key, $default = '' ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ $key ] ) ) {
				return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$value = $this->get_user_prop_or_default( $key, $default );

			/**
			 * Deprecated filter. Use the filter below instead.
			 *
			 * @deprecated 1.8.0
			 *
			 * @since 1.0.0
			 * @since 1.5.7 Deprecated.
			 *
			 * @param string          $value The value.
			 * @param string          $key   The key of the value.
			 * @param Charitable_User $user  Instance of `Charitable_User`.
			 */
			$value = apply_filters( 'charitable_campaign_submission_user_value', $value, $key, $this->get_user() );

			/**
			 * Filter the set value for a particular key.
			 *
			 * @since 1.5.7
			 *
			 * @param string          $value The value.
			 * @param string          $key   The key of the value.
			 * @param Charitable_User $user  Instance of `Charitable_User`.
			 */
			return apply_filters( 'charitable_profile_value', $value, $key, $this->get_user() );
		}

		/**
		 * Return the core user fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_user_fields() {
			$user_fields = apply_filters(
				'charitable_user_fields',
				array(
					'username'     => array(
						'type'     => 'paragraph',
						'priority' => '',
						'content'  => sprintf(
							/* translators: %s: username */
							__( 'Username: %s.', 'charitable' ),
							$this->get_user()->user_login
						),
					),
					'first_name'   => array(
						'label'    => __( 'First name', 'charitable' ),
						'type'     => 'text',
						'priority' => 4,
						'required' => true,
						'value'    => $this->get_user_value( 'first_name' ),
					),
					'last_name'    => array(
						'label'    => __( 'Last name', 'charitable' ),
						'type'     => 'text',
						'priority' => 6,
						'required' => true,
						'value'    => $this->get_user_value( 'last_name' ),
					),
					'user_email'   => array(
						'label'    => __( 'Email', 'charitable' ),
						'type'     => 'email',
						'required' => true,
						'priority' => 8,
						'value'    => $this->get_user_value( 'user_email' ),
					),
					'organisation' => array(
						'label'    => __( 'Organization', 'charitable' ),
						'type'     => 'text',
						'priority' => 10,
						'required' => false,
						'value'    => $this->get_user_value( 'organisation' ),
					),
					'description'  => array(
						'label'    => __( 'Bio', 'charitable' ),
						'type'     => 'textarea',
						'required' => false,
						'priority' => 12,
						'value'    => $this->get_user_value( 'description' ),
					),
				),
				$this
			);

			uasort( $user_fields, 'charitable_priority_sort' );

			return $user_fields;
		}

		/**
		 * Return the user's address fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_address_fields() {
			/**
			 * Filter the list of user address fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array                   $fields The fields in the form.
			 * @param Charitable_Profile_Form $form   The profile form.
			 */
			$address_fields = apply_filters(
				'charitable_user_address_fields',
				array(
					'address'   => array(
						'label'    => __( 'Address', 'charitable' ),
						'type'     => 'text',
						'priority' => 22,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_address' ),
					),
					'address_2' => array(
						'label'    => __( 'Address 2', 'charitable' ),
						'type'     => 'text',
						'priority' => 24,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_address_2' ),
					),
					'city'      => array(
						'label'    => __( 'City', 'charitable' ),
						'type'     => 'text',
						'priority' => 26,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_city' ),
					),
					'state'     => array(
						'label'    => __( 'State', 'charitable' ),
						'type'     => 'text',
						'priority' => 28,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_state' ),
					),
					'postcode'  => array(
						'label'    => __( 'Postcode / ZIP code', 'charitable' ),
						'type'     => 'text',
						'priority' => 30,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_postcode' ),
					),
					'country'   => array(
						'label'    => __( 'Country', 'charitable' ),
						'type'     => 'select',
						'options'  => charitable_get_location_helper()->get_countries(),
						'priority' => 32,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_country', charitable_get_option( 'country' ) ),
					),
					'phone'     => array(
						'label'    => __( 'Phone', 'charitable' ),
						'type'     => 'text',
						'priority' => 34,
						'required' => false,
						'value'    => $this->get_user_value( 'donor_phone' ),
					),
				),
				$this
			);

			uasort( $address_fields, 'charitable_priority_sort' );

			return $address_fields;
		}

		/**
		 * Return the social fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_social_fields() {
			$social_fields = apply_filters(
				'charitable_user_social_fields',
				array(
					'user_url' => array(
						'label'    => __( 'Your Website', 'charitable' ),
						'type'     => 'url',
						'priority' => 42,
						'required' => false,
						'value'    => $this->get_user_value( 'user_url' ),
					),
					'twitter'  => array(
						'label'    => __( 'Twitter / X', 'charitable' ),
						'type'     => 'text',
						'priority' => 44,
						'required' => false,
						'value'    => $this->get_user_value( 'twitter' ),
					),
					'facebook' => array(
						'label'    => __( 'Facebook', 'charitable' ),
						'type'     => 'text',
						'priority' => 46,
						'required' => false,
						'value'    => $this->get_user_value( 'facebook' ),
					),
				),
				$this
			);

			uasort( $social_fields, 'charitable_priority_sort' );

			return $social_fields;
		}

		/**
		 * Maybe add communication preferences fields.
		 *
		 * @since  1.6.2
		 *
		 * @param  array $fields The existing profile form fields.
		 * @return array
		 */
		public function maybe_get_communication_preferences_fields( $fields ) {
			if ( ! charitable_is_contact_consent_activated() ) {
				return $fields;
			}

			$donor = $this->get_user()->get_donor();

			if ( array_key_exists( 'contact_consent', $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$consent = sanitize_text_field( wp_unslash( $_POST['contact_consent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} elseif ( is_null( $donor ) ) {
				$consent = false;
			} else {
				$consent = $donor->contact_consent;
			}

			/**
			 * Filter the communication preferences fields.
			 *
			 * @since 1.6.2
			 *
			 * @param array                   $communication_fields List of fields.
			 * @param Charitable_Profile_Form $form                 Instance of `Charitable_Profile_Form`.
			 */
			$communication_fields = apply_filters(
				'charitable_user_communication_preferences_fields',
				array(
					'contact_consent' => array(
						'type'     => 'checkbox',
						'label'    => charitable_get_option( 'contact_consent_label', __( 'Yes, I am happy for you to contact me via email or phone.', 'charitable' ) ),
						'priority' => 8,
						'required' => false,
						'checked'  => $consent,
					),
				),
				$this
			);

			if ( empty( $communication_fields ) ) {
				return $fields;
			}

			uasort( $fields, 'charitable_priority_sort' );

			return array_merge(
				$fields,
				array(
					'communication_fields' => array(
						'legend'   => __( 'Communication Preferences', 'charitable' ),
						'type'     => 'fieldset',
						'fields'   => $communication_fields,
						'priority' => 60,
					),
				)
			);
		}

		/**
		 * Profile fields to be displayed.
		 *
		 * @since  1.0.0
		 *
		 * @return array[]
		 */
		public function get_fields() {
			$fields = apply_filters(
				'charitable_user_profile_fields',
				array(
					'user_fields'     => array(
						'legend'   => __( 'Your Details', 'charitable' ),
						'type'     => 'fieldset',
						'fields'   => $this->get_user_fields(),
						'priority' => 0,
					),
					'password_fields' => array(
						'legend'   => __( 'Change Your Password', 'charitable' ),
						'type'     => 'fieldset',
						'fields'   => $this->get_password_fields(),
						'priority' => 10,
					),
					'address_fields'  => array(
						'legend'   => __( 'Your Address', 'charitable' ),
						'type'     => 'fieldset',
						'fields'   => $this->get_address_fields(),
						'priority' => 20,
					),
					'social_fields'   => array(
						'legend'   => __( 'Your Social Profiles', 'charitable' ),
						'type'     => 'fieldset',
						'fields'   => $this->get_social_fields(),
						'priority' => 40,
					),
				),
				$this
			);

			$fields = $this->maybe_get_communication_preferences_fields( $fields );

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Retrieve hidden fields.
		 *
		 * @since  1.6.2
		 *
		 * @return array
		 */
		public function get_hidden_fields() {
			return array_merge(
				parent::get_hidden_fields(),
				array(
					'current_email' => $this->get_user()->get_email(),
				)
			);
		}

		/**
		 * The fields displayed on the password form.
		 *
		 * @since  1.4.0
		 *
		 * @return array[]
		 */
		public function get_password_fields() {
			$password_fields = apply_filters(
				'charitable_user_profile_password_fields',
				array(
					'current_pass'     => array(
						'priority' => 2,
						'type'     => 'password',
						'label'    => __( 'Current Password (leave blank to leave unchanged)', 'charitable' ),
						'value'    => '',
						'required' => false,
					),
					'user_pass'        => array(
						'priority' => 4,
						'type'     => 'password',
						'label'    => __( 'New Password (leave blank to leave unchanged)', 'charitable' ),
						'required' => false,
					),
					'user_pass_repeat' => array(
						'priority' => 6,
						'type'     => 'password',
						'label'    => __( 'New Password (again)', 'charitable' ),
						'required' => false,
					),
				),
				$this
			);

			uasort( $password_fields, 'charitable_priority_sort' );

			return $password_fields;
		}

		/**
		 * Update profile after form submission.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public static function update_profile() {
			$form = new Charitable_Profile_Form();

			if ( ! $form->validate_nonce() || ! $form->validate_honeypot() ) {
				charitable_get_notices()->add_error( __( 'Unfortunately, we were unable to verify your form submission. Please reload the page and try again.', 'charitable' ) );
				return;
			}

			$user = $form->get_user();

			/* Verify that the user is logged in. */
			if ( 0 == $user->ID ) {
				return;
			}

			$fields = $form->get_merged_fields();

			/**
			 * Filter the profile values.
			 */
			$submitted = apply_filters(
				'charitable_profile_update_values',
				$form->get_submitted_values(),
				$fields,
				$form
			);

			/* Remove the current_pass and user_pass_repeat fields, if set. */
			unset(
				$submitted['current_pass'],
				$submitted['user_pass_repeat']
			);

			$valid = $form->check_required_fields( $fields );

			if ( $valid && $form->is_changing_password() ) {
				$valid = $form->validate_password_change();
			}

			if ( $valid && $form->is_changing_email() ) {
				$valid = $form->validate_email_change();
			}

			if ( $valid ) {
				$user->update_profile( $submitted, array_keys( $fields ) );

				charitable_get_notices()->add_success( __( 'Your profile has been updated.', 'charitable' ) );

				do_action( 'charitable_profile_updated', $submitted, $fields, $form );
			}
		}

		/**
		 * Check whether the password is being changed.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function is_changing_password() {
			if ( ! isset( $_POST['user_pass'] ) || empty( $_POST['user_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			if ( ! isset( $_POST['current_pass'] ) || empty( $_POST['current_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			return true;
		}

		/**
		 * Changes a password if the current password is correct and the repeat matches the new password.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function validate_password_change() {
			/* The current password must be correct. */
			if ( false == wp_check_password( $_POST['current_pass'], $this->get_user()->user_pass ) ) { // phpcs:ignore
				charitable_get_notices()->add_error( __( 'Current password is incorrect.', 'charitable' ) );
				return false;
			}

			/* The new password must match the repeat (if set). */
			if ( isset( $_POST['user_pass_repeat'] ) && $_POST['user_pass_repeat'] != $_POST['user_pass'] ) { // phpcs:ignore
				charitable_get_notices()->add_error( __( 'New passwords did not match.', 'charitable' ) );
				return false;
			}

			return true;
		}

		/**
		 * Check whether the email address is being changed.
		 *
		 * @since  1.6.2
		 *
		 * @return boolean
		 */
		public function is_changing_email() {
			if ( ! isset( $_POST['user_email'] ) || empty( $_POST['user_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			if ( ! isset( $_POST['current_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $_POST['user_email'] !== $this->get_user()->get_email(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			return $_POST['user_email'] !== $_POST['current_email']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Validate the email change.
		 *
		 * @since  1.6.2
		 * @version 1.8.9.1
		 *
		 * @return boolean
		 */
		public function validate_email_change() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( empty( $user_email ) ) {
				return false;
			}

			if ( 0 != charitable_get_donor_id_by_email( $user_email ) ) {
				charitable_get_notices()->add_error(
					__( 'This email address is already in use.', 'charitable' )
				);
				return false;
			}

			return true;
		}

		/**
		 * Return a user's set value for a particular value, or return the default value.
		 *
		 * @since  1.5.7
		 *
		 * @param  string $key     The property we're getting the value for.
		 * @param  string $default The fallback value.
		 * @return string
		 */
		protected function get_user_prop_or_default( $key, $default ) {
			$user = $this->get_user();

			return $user && $user->has_prop( $key ) ? $user->get( $key ) : $default;
		}

		/**
		 * Add the charitable_user_profile_after_fields hook but fire off a deprecated notice.
		 *
		 * @deprecated 1.4.0
		 * @since  1.4.0
		 *
		 * @return void
		 */
		public static function add_deprecated_charitable_user_profile_after_fields_hook( $form ) {
			if ( ! has_action( 'charitable_user_profile_after_fields' ) ) {
				return;
			}

			charitable_get_deprecated()->doing_it_wrong(
				__METHOD__,
				__( 'charitable_user_profile_after_fields hook has been removed. Use charitable_form_after_fields instead.', 'charitable' ),
				'1.4.0'
			);

			if ( 'Charitable_Profile_Form' == get_class( $form ) ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				do_action( 'charitable_user_profile_after_fields', $form );
			}
		}

		/**
		 * Add avatar section to user profile form.
		 *
		 * @param   array[]                 $fields The fields in the form.
		 * @param   Charitable_Profile_Form $form  The profile form object.
		 * @return  array[]
		 * @access  public
		 * @since   1.0.0
		 */
		public function add_avatar_field( $fields, $form ) {
			$user        = $form->get_user();
			$user_avatar = new Charitable_User_Avatar();
			$avatar      = $user_avatar->get_user_avatar( false, $user );

			if ( ! $avatar && charitable_user_has_gravatar( $user->user_email ) ) {
				$avatar = get_avatar( $user->ID, 100 );
			}

			$fields['avatar'] = apply_filters(
				'charitable_user_avatar_field_args',
				array(
					'label'     => __( 'Your Profile Photo', 'charitable' ),
					'type'      => 'picture',
					'uploader'  => true,
					'size'      => 100,
					'value'     => $avatar,
					'priority'  => 14,
					'fullwidth' => true,
				)
			);

			return $fields;
		}

		/**
		 * Upload avatar and add file fields to the submitted fields.
		 *
		 * @param   array                   $submitted The values submitted by the user.
		 * @param   array[]                 $fields    The fields in the form.
		 * @param   Charitable_Profile_Form $form      The profile form object.
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function save_avatar( $submitted, $fields, $form ) {
			if ( isset( $_FILES ) && isset( $_FILES['avatar'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				$attachment_id = $form->upload_post_attachment( 'avatar', 0 );

				if ( ! is_wp_error( $attachment_id ) ) {

					$submitted['avatar'] = $attachment_id;

					/* Delete the previously upload avatar. */
					$old_avatar = get_user_meta( $form->get_user()->ID, 'avatar', true );

					if ( ! empty( $old_avatar ) ) {

						wp_delete_attachment( $old_avatar );

					}

					update_user_meta( $form->get_user()->ID, 'avatar', $attachment_id );

				} else {
					/**
					* Handle image upload error.
					*
					* @todo
					*/
				}//end if
			} elseif ( ! array_key_exists( 'avatar', $submitted ) ) {

				/* The picture has been removed. */
				$submitted['avatar'] = '';

			}//end if

			return $submitted;
		}
	}

endif;
