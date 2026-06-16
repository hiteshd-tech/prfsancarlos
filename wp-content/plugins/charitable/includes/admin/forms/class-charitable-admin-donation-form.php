<?php
/**
 * Admin donation form model class.
 *
 * @package   Charitable/Classes/Charitable_Admin_Donation_Form
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.55
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Donation_Form' ) ) :

	/**
	 * Charitable_Admin_Donation_Form
	 *
	 * @since 1.5.0
	 */
	class Charitable_Admin_Donation_Form extends Charitable_Admin_Form {

		/**
		 * Current Charitable_Donation object, or false if it's a new donation.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Donation|false
		 */
		protected $donation;

		/**
		 * Form action.
		 *
		 * @since 1.5.0
		 *
		 * @var   string
		 */
		protected $form_action;

		/**
		 * Merged fields.
		 *
		 * @since 1.5.11
		 *
		 * @var   array
		 */
		protected $merged_fields;

		/**
		 * Create a donation form object.
		 *
		 * @since 1.5.0
		 *
		 * @param Charitable_Donation|false $donation For existing donations, the `Charitable_Donation` instance.
		 *                                            False for new donations.
		 */
		public function __construct( $donation ) {
			$this->id          = uniqid();
			$this->donation    = $donation;
			$this->form_action = $this->has_donation() ? 'update_donation' : 'add_donation';
		}

		/**
		 * Return the current Charitable_Donation instance, or false if this is a new donation.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation|false
		 */
		public function get_donation() {
			return $this->donation;
		}

		/**
		 * Whether there is a current active donation we are editing.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function has_donation() {
			return $this->donation && ! in_array( $this->donation->get_status(), array( 'auto-draft', 'draft' ) );
		}

		/**
		 * Return the donation form fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array[]
		 */
		public function get_fields() {
			$fields = array(
				'donation_fields' => array(
					'type'     => 'fieldset',
					'fields'   => $this->get_donation_fields(),
					'priority' => 21,
					'tabindex' => 1,
				),
				'donor_header'    => array(
					'type'     => 'heading',
					'level'    => 'h3',
					'title'    => __( 'Donor', 'charitable' ),
					'priority' => 40,
				),
				'user_fields'     => array(
					'type'     => 'fieldset',
					'fields'   => $this->get_user_fields(),
					'priority' => 50,
					'tabindex' => 100,
				),
				'meta_fields'     => array(
					'type'     => 'fieldset',
					'fields'   => $this->get_meta_fields(),
					'priority' => 60,
					'tabindex' => 200,
				),
			);

			if ( $this->has_donation() ) {
				if ( 'manual' !== $this->get_donation()->get_gateway() ) {
					$fields['meta_fields']['fields']['date']['type']  = 'hidden';
					$fields['meta_fields']['fields']['date']['value'] = Charitable_Data_Processor::sanitize_datepicker( $fields['meta_fields']['fields']['date']['value'] );
				}

				$fields['meta_fields']['fields']['time'] = array(
					'type'     => 'hidden',
					'priority' => 2,
					'value'    => date( 'H:i:s', strtotime( $this->get_donation()->post_date_gmt ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				);
			} else {
				$fields['donor_id'] = array(
					'type'          => 'select',
					'options'       => $this->get_all_donors(),
					'priority'      => 41,
					'value'         => '',
					'description'   => __( 'Select an existing donor or choose "Add a New Donor" to create a new donor.', 'charitable' ),
					'wrapper_class' => array( 'select2' ),
					'attrs'         => array(
						'data-nonce' => wp_create_nonce( 'donor-select' ),
					),
				);
			}

			/* User can only create a donation for themselves. */
			if ( ! current_user_can( 'edit_others_donations' ) ) {
				$user = charitable_get_user( get_current_user_id() );

				if ( array_key_exists( 'donor_id', $fields ) ) {
					$fields['donor_id']['type']  = 'hidden';
					$fields['donor_id']['value'] = $user->get_donor_id();
				}

				foreach ( $fields['user_fields']['fields'] as $key => $details ) {
					$fields['user_fields']['fields'][ $key ]['value'] = $user->$key;

					if ( $key === 'email' ) {
						$fields['user_fields']['fields'][ $key ]['field_attrs'] = array(
							'attrs' => array( 'disabled' => 'disabled' ),
						);
					}
				}
			}

			/**
			 * Filter the admin donation form fields.
			 *
			 * Note that the recommended way to add fields to the form is
			 * with the Donation Fields API. This filter provides the ability
			 * to re-organize the sections within the form and change fields
			 * in the form that do not come from the Donation Fields API
			 * (headers, campaign/amount field, resend receipt).
			 *
			 * @since 1.5.0
			 *
			 * @param array                          $fields Array of fields.
			 * @param Charitable_Admin_Donation_Form $form   This instance of `Charitable_Admin_Donation_Form`.
			 */
			$fields = apply_filters( 'charitable_admin_donation_form_fields', $fields, $this );

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Get donation fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_donation_fields() {
			if ( ! $this->donation ) {
				$value = array();
			} else {
				$value = (array) $this->donation->get_campaign_donations();
			}

			return array(
				'campaign_donations' => array(
					'type'  => 'campaign-donations',
					'value' => $value,
				),
			);
		}

		/**
		 * Return all the fields in a particular section.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $section The section we're fetching fields for.
		 * @return array
		 */
		public function get_section_fields( $section ) {
			$fields = charitable()->donation_fields()->get_admin_form_fields( $section );
			$keys   = array_keys( $fields );
			$fields = array_combine(
				$keys,
				array_map( array( $this, 'maybe_set_field_value' ), wp_list_pluck( $fields, 'admin_form' ), $keys )
			);

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Return the fields for the user section.
		 *
		 * @since  1.6.28
		 *
		 * @return array
		 */
		public function get_user_fields() {
			$fields            = $this->get_section_fields( 'user' );
			$loading_icon      = charitable()->get_path( 'assets', false ) . '/images/charitable-loading.gif';
			$fields['overlay'] = array(
				'type'     => 'content',
				'priority' => 1,
				'content'  => '<div class="charitable-overlay" style="display: none;"><img src="' . $loading_icon . '" width=60 height=60 alt="' . __( 'Loading icon', 'charitable' ) . '" /></div>',
			);

			uasort( $fields, 'charitable_priority_sort' );

			return $fields;
		}

		/**
		 * Return the fields for the meta section.
		 *
		 * @since 1.6.28
		 * @since 1.8.1.8 Added the `charitable_admin_donation_form_meta_fields` filter.
		 *
		 * @return array
		 */
		public function get_meta_fields() {
			$fields             = $this->get_section_fields( 'meta' );
			$fields['log_note'] = array(
				'label'    => __( 'Donation Note', 'charitable' ),
				'type'     => 'textarea',
				'priority' => 12,
				'required' => false,
			);

			if ( $this->should_add_donation_receipt_checkbox() ) {
				$fields['send_donation_receipt'] = array(
					'type'     => 'checkbox',
					'label'    => __( 'Send an email receipt to the donor.', 'charitable' ),
					'value'    => 1,
					'default'  => 1,
					'priority' => 16,
				);
			}

			uasort( $fields, 'charitable_priority_sort' );

			return apply_filters( 'charitable_admin_donation_form_meta_fields', $fields, $this );
		}

		/**
		 * Get the value submitted for a particular field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field   The field.
		 * @param  mixed  $default The default value to return if the value was not submitted.
		 * @return mixed
		 */
		public function get_submitted_value( $field, $default = false ) {
			$data = $this->get_submitted_values();
			return isset( $data[ $field ] ) ? $data[ $field ] : $default;
		}

		/**
		 * Filter a campaign donation array, making sure both a campaign
		 * and amount are provided.
		 *
		 * @since  1.5.0
		 *
		 * @param  object $campaign_donation A particular campaign donation.
		 * @return boolean
		 */
		public function filter_campaign_donation( $campaign_donation ) {
			$campaign_donation = (array) $campaign_donation;

			return array_key_exists( 'campaign_id', $campaign_donation )
				&& array_key_exists( 'amount', $campaign_donation )
				&& ! empty( $campaign_donation['campaign_id'] )
				&& ! empty( $campaign_donation['amount'] );
		}

		/**
		 * Validate the form submission.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function validate_submission() {
			/* If we have already validated the submission, return the value. */
			if ( isset( $this->validated ) ) {
				return $this->valid;
			}

			$this->valid = $this->check_required_fields( $this->get_merged_fields() );

			/**
			 * If required fields are missing, get the error message from the
			 * notices and add them to the admin notices.
			 */
			if ( ! $this->valid ) {
				charitable_get_admin_notices()->fill_notices_from_frontend();
			}

			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array data, sanitized in filter_campaign_donation
			$campaign_donations = array_key_exists( 'campaign_donations', $_POST ) ? wp_unslash( $_POST['campaign_donations'] ) : array();
			$campaign_donations = array_filter( $campaign_donations, array( $this, 'filter_campaign_donation' ) );
			$_POST['campaign_donations'] = $campaign_donations;

			if ( empty( $campaign_donations ) ) {
			// phpcs:enable WordPress.Security.NonceVerification.Missing
				charitable_get_admin_notices()->add_error( __( 'You must provide both a campaign and amount.', 'charitable' ) );

				$this->valid = false;
			}

			if ( $this->donation_needs_email() ) {
				charitable_get_admin_notices()->add_error( __( 'Please choose an existing donor or provide an email address for a new donor.', 'charitable' ) );

				$this->valid = false;
			}

			/**
			 * Filter whether the admin donation form passes validation.
			 *
			 * @since 1.5.0
			 *
			 * @param boolean                        $valid Whether the form submission is valid.
			 * @param Charitable_Admin_Donation_Form $form  This instance of `Charitable_Admin_Donation_Form`.
			 */
			$this->valid     = apply_filters( 'charitable_validate_admin_donation_form_submission', $this->valid, $this );
			$this->validated = true;

			return $this->valid;
		}

		/**
		 * Return the donation values.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_donation_values() {
			$values = array(
				'ID'       => $this->get_submitted_value( 'ID' ),
				'donor_id' => abs( (int) $this->get_submitted_value( 'donor_id' ) ),
				'status'   => $this->get_submitted_value( 'status' ),
				'log_note' => $this->get_submitted_value( 'log_note' ),
				'user_id'  => 0,
			);

			if ( 'add_donation' == $this->get_submitted_value( 'charitable_action' ) ) {
				$values['donation_gateway'] = __( 'Manual', 'charitable' );
			}

			$values = $this->sanitize_submitted_campaign_donation( $values );
			$values = $this->sanitize_submitted_date( $values );
			$values = $this->sanitize_submitted_log_note( $values );
			$values = $this->sanitize_submitted_donor( $values );

			/* Ensure that the user has not created a donation for someone else. */
			if ( ! current_user_can( 'edit_others_donations' ) ) {
				$user = charitable_get_user( get_current_user_id() );

				$values['donor_id'] = $user->get_donor_id();

				if ( array_key_exists( 'user_id', $values ) ) {
					$values['user_id'] = $user->ID;
				}

				if ( array_key_exists( 'user', $values ) && array_key_exists( 'email', $values['user'] ) ) {
					$values['user']['email'] = $user->email;
				}
			}

			foreach ( $this->get_merged_fields() as $key => $field ) {
				if ( $this->should_field_be_added( $field, $key, $values ) ) {
					$values[ $field['data_type'] ][ $key ] = $this->get_field_value_from_submission( $field, $key );
				}
			}

			/**
			 * Filter the submitted values.
			 *
			 * @since 1.5.0
			 *
			 * @param array                          $values The submitted values.
			 * @param Charitable_Admin_Donation_Form $form   This instance of `Charitable_Admin_Donation_Form`.
			 */
			return apply_filters( 'charitable_admin_donation_form_submission_values', $values, $this );
		}

		/**
		 * Return the value for a particular field from the form submission, or return the default.
		 *
		 * @since  1.5.7
		 *
		 * @param  array  $field The field definition.
		 * @param  string $key   The key of the field.
		 * @return mixed
		 */
		protected function get_field_value_from_submission( $field, $key ) {
			$default = 'checkbox' == $field['type'] ? false : '';

			return $this->get_submitted_value( $key, $default );
		}

		/**
		 * Checks whether a field should be added to the values to be saved.
		 *
		 * @since  1.5.7
		 *
		 * @param  array  $field  The field definition.
		 * @param  string $key    The key of the field.
		 * @param  array  $values The sanitized values so far.
		 * @return boolean
		 */
		protected function should_field_be_added( $field, $key, $values ) {
			if ( ! $this->should_data_type_be_added( $field ) || ! array_key_exists( 'type', $field ) ) {
				return false;
			}

			if ( isset( $values[ $field['data_type'] ][ $key ] ) ) {
				return false;
			}

			return 'user' == $field['data_type'] ? $this->should_user_field_be_added( $values ) : true;
		}

		/**
		 * Whether the passed data type should be added to the values to be saved.
		 *
		 * @since  1.5.7
		 *
		 * @param  array $field The field definition.
		 * @return boolean
		 */
		protected function should_data_type_be_added( $field ) {
			return array_key_exists( 'data_type', $field ) && 'core' != $field['data_type'];
		}

		/**
		 * Returns whether users fields should be added.
		 *
		 * This will return true if no donor_id is set, or if this is an edit
		 * of an existing donation.
		 *
		 * @since  1.5.7
		 *
		 * @param  array $values The sanitized values so far.
		 * @return boolean
		 */
		protected function should_user_field_be_added( $values ) {
			return ! $values['donor_id'] || $values['ID'];
		}

		/**
		 * Return donor values.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $values The submitted values.
		 * @return array
		 */
		protected function sanitize_submitted_donor( $values ) {
			/* Shortcircuit for new donations. */
			if ( ! $values['donor_id'] ) {
				if ( $values['ID'] && $this->has_donation() ) {
					$values['user'] = $this->get_donor_data_to_preserve();
				}

				return $values;
			}

			$donor = new Charitable_Donor( $values['donor_id'] );

			/* Populate the user_id' arg, then prepare the 'user' array. */
			$values['user_id'] = $donor->get_user()->ID;
			$values['user']    = array(
				'email'      => $this->get_submitted_value( 'email' ),
				'first_name' => $this->get_submitted_value( 'first_name' ),
				'last_name'  => $this->get_submitted_value( 'last_name' ),
				'address'    => $this->get_submitted_value( 'address' ),
				'address_2'  => $this->get_submitted_value( 'address_2' ),
				'postcode'   => $this->get_submitted_value( 'postcode' ),
				'state'      => $this->get_submitted_value( 'state' ),
				'country'    => $this->get_submitted_value( 'country' ),
				'phone'      => $this->get_submitted_value( 'phone' ),
			);

			return $values;
		}

		/**
		 * Returns any of the current donor data that should be preserved
		 * after saving. This is any data that exists that doesn't have a
		 * field in the admin donation form.
		 *
		 * @since  1.5.11
		 *
		 * @return array
		 */
		protected function get_donor_data_to_preserve() {
			$data = $this->donation->get_donor_data();

			foreach ( $this->get_merged_fields() as $key => $field ) {
				if ( ! array_key_exists( 'data_type', $field ) || 'user' != $field['data_type'] ) {
					continue;
				}

				if ( ! array_key_exists( $key, $data ) ) {
					continue;
				}

				unset( $data[ $key ] );

				/* There is no data left to preserve, so return an empty array. */
				if ( empty( $data ) ) {
					return $data;
				}
			}

			return $data;
		}

		/**
		 * Sanitize the log note, or add one if none was included.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $values The submitted values.
		 * @return array
		 */
		protected function sanitize_submitted_log_note( $values ) {
			if ( ! $values['log_note'] ) {
				$values['log_note'] = sprintf(
					/* translators: %1$s: User profile URL, %2$s: User display name */
					__( 'Donation updated manually by <a href="%1$s">%2$s</a>.', 'charitable' ),
					admin_url( 'user-edit.php?user_id=' . wp_get_current_user()->ID ),
					wp_get_current_user()->display_name
				);
			} else {
				$values['log_note'] .= sprintf(
					' - <a href="%s">%s</a>',
					admin_url( 'user-edit.php?user_id=' . wp_get_current_user()->ID ),
					wp_get_current_user()->display_name
				);
			}

			return $values;
		}

		/**
		 * Sanitize the campaign donation submitted.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $values The submitted values.
		 * @return array
		 */
		protected function sanitize_submitted_campaign_donation( $values ) {
			$campaigns = array();

			foreach ( $this->get_submitted_value( 'campaign_donations' ) as $key => $campaign_donation ) {
				$campaign_donation['amount'] = charitable_get_currency_helper()->sanitize_monetary_amount( $campaign_donation['amount'] );
				$campaigns[ $key ]           = $campaign_donation;
			}

			$values['campaigns'] = $campaigns;

			if ( $this->has_donation() ) {
				$old_campaign_ids = wp_list_pluck( $this->donation->get_campaign_donations(), 'campaign_id' );

				foreach ( $campaigns as $campaign_donation ) {
					if ( ! in_array( $campaign_donation['campaign_id'], $old_campaign_ids ) ) {
						Charitable_Campaign::flush_donations_cache( $campaign_donation['campaign_id'] );
					}
				}
			}

			return $values;
		}

		/**
		 * Sanitize the date.
		 *
		 * @since   1.5.0
		 * @version 1.8.8.5
		 *
		 * @param  array $values The submitted values.
		 * @return array
		 */
		protected function sanitize_submitted_date( $values ) {
			$donation = charitable_get_donation( $this->get_submitted_value( 'ID' ) );
			$is_new   = false === $donation || 'Auto Draft' === $donation->post_title;
			$date     = $this->get_submitted_value( 'date' );
			$time     = $this->get_submitted_value( 'time', '00:00:00' );

			/* The date has already been sanitized in Charitable_Date_Processor::sanitize_datepicker */
			$values['date_gmt'] = $date . ' ' . $time;

			/* If the date matches today's date and it's a new donation, save the time too. */
			if ( date( 'Y-m-d 00:00:00' ) == $values['date_gmt'] && $is_new ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$values['date_gmt'] = date( 'Y-m-d H:i:s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}

			/* If the donation date has been changed, preserve the original time from the donation log */
			if ( $values['date_gmt'] !== $donation->post_date_gmt && ! $is_new ) {
				// Get the original donation time from the donation log
				$donation_log = get_post_meta( $donation->ID, '_donation_log', true );
				$original_time = '00:00:00'; // Default fallback

				if ( is_array( $donation_log ) && ! empty( $donation_log ) ) {
					// Get the first log entry which contains the original creation time
					$first_log_entry = $donation_log[0];
					if ( isset( $first_log_entry['time'] ) ) {
						// Convert the original timestamp to GMT time format
						$original_time = gmdate( 'H:i:s', $first_log_entry['time'] );
					}
				}

				$values['date_gmt'] = $date . ' ' . $original_time;
			}

			return $values;
		}

		/**
		 * Get a key=>value array of all existing donors.
		 *
		 * @since   1.5.0
		 * @version 1.8.8
		 *
		 * @return array
		 */
		protected function get_all_donors() {
			$donors = new Charitable_Donor_Query(
				array(
					'number'         => -1,
					'orderby'        => 'name',
					'order'          => 'ASC',
					'output'         => 'raw',
					'status'         => false, // Return any.
					'include_erased' => false,
				)
			);

			$donor_list = array();

			foreach ( $donors as $donor ) {
				$name = trim( sprintf( '%s %s', $donor->first_name, $donor->last_name ) );

				if ( charitable_is_valid_email_address( $donor->email ) ) {
					$name .= ' - ' . $donor->email;
				}

				$donor_list[ $donor->donor_id ] = $name;
			}

			// Apply a filter here to allow for the donor list to be modified.
			$donor_list = apply_filters( 'charitable_admin_donation_form_donor_list', $donor_list );

			$list = array(
				'new'      => __( 'Add a New Donor', 'charitable' ),
				'existing' => array(
					'label'   => __( 'Existing Donors', 'charitable' ),
					'options' => $donor_list,
				),
			);

			return $list;
		}

		/**
		 * Set a field's initial value.
		 *
		 * @since  1.5.0
		 * @version 1.8.9.1
		 *
		 * @param  array  $field Field definition.
		 * @param  string $key   The key of the field.
		 * @return array
		 */
		protected function maybe_set_field_value( $field, $key ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( array_key_exists( $key, $_POST ) ) {
				$field['value'] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				// phpcs:enable WordPress.Security.NonceVerification.Missing
				return $field;
			}

			/* Checkboxes don't need a value set. */
			if ( 'checkbox' != $field['type'] ) {
				$field['value'] = array_key_exists( 'default', $field ) ? $field['default'] : '';
			}

			if ( ! $this->has_donation() ) {
				return $field;
			}

			if ( isset( $field['value_callback'] ) ) {
				$value = call_user_func( $field['value_callback'], $this->get_donation(), $key );
			} else {
				$value = $this->donation->get( $key );
			}

			if ( isset( $field['timestamp_callback'] ) ) {
				$field['timestamp'] = call_user_func( $field['timestamp_callback'], $this->get_donation(), $key );
			}

			if ( ! $value ) {
				return $field;
			}

			if ( 'checkbox' == $field['type'] ) {
				$field['checked'] = $value;
			} else {
				$field['value'] = $value;
			}

			return $field;
		}

		/**
		 * Returns whether a checkbox should be included for sending the donation receipt.
		 *
		 * @since  1.5.9
		 *
		 * @return boolean
		 */
		protected function should_add_donation_receipt_checkbox() {
			return ! $this->has_donation() && charitable_get_helper( 'emails' )->is_enabled_email( 'donation_receipt' );
		}

		/**
		 * Return whether the donation needs an email address.
		 *
		 * @since  1.6.0
		 *
		 * @return boolean
		 */
		public function donation_needs_email() {
			if ( charitable_permit_donor_without_email() ) {
				return false;
			}

			if ( strlen( $this->get_submitted_value( 'email', '' ) ) ) {
				return false;
			}

			$donor_id = $this->get_submitted_value( 'donor_id' );

			return 'new' == $donor_id || '' == $donor_id;
		}
	}

endif;
