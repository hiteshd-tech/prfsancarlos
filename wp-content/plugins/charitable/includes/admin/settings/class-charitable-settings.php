<?php
/**
 * Charitable Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Settings' ) ) :

	/**
	 * Charitable_Settings
	 *
	 * @final
	 * @since 1.0.0
	 */
	final class Charitable_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var  Charitable_Settings|null
		 */
		private static $instance = null;

		/**
		 * Dynamic groups.
		 *
		 * @since 1.5.7
		 *
		 * @var   array
		 */
		private $dynamic_groups;

		/**
		 * List of static pages, used in some settings.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		private $pages;

		/**
		 * Create object instance.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			do_action( 'charitable_admin_settings_start', $this );

			add_action( 'charitable_after_admin_settings', array( $this, 'settings_cta' ) );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Return the array of tabs used on the settings page.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_sections() {
			/**
			 * Filter the settings tabs.
			 *
			 * @since   1.0.0
			 * @version 1.8.1.6 removed 'tools' tab
			 * @version 1.8.5.1 added marketing and security tabs depending on addon status.
			 *
			 * @param string[] $tabs List of tabs in key=>label format.
			 */
			$tabs = array(
				'general'  => __( 'General', 'charitable' ),
				'gateways' => __( 'Payment Gateways', 'charitable' ),
				'emails'   => __( 'Emails', 'charitable' ),
				'donors'   => __( 'Donors', 'charitable' ),
				'privacy'  => __( 'Privacy', 'charitable' ),
				'advanced' => __( 'Advanced', 'charitable' ),
			);

			// Only show marketing tab if newsletter connect addon is not active.
			if ( ! class_exists( 'Charitable_Newsletter_Connect' ) ) {
				$tabs = charitable_add_settings_tab(
					$tabs,
					'marketing',
					__( 'Marketing', 'charitable' ),
					array(
						'index' => 5,
					)
				);
			}

			// Always show security tab.
			// When spam blocker is active, it will handle the settings.
			// When spam blocker is inactive, core security features will handle the settings.
				$tabs = charitable_add_settings_tab(
					$tabs,
					'security',
					__( 'Security', 'charitable' ),
					array(
						'index' => 6,
					)
				);

			// Ensure the advanced tab is always last by moving it to the end of the array.
			if ( isset( $tabs['advanced'] ) ) {
				$advanced_tab = $tabs['advanced'];
				unset( $tabs['advanced'] );
				$tabs['advanced'] = $advanced_tab;
			}

			return apply_filters( 'charitable_settings_tabs', $tabs );
		}

		/**
		 * Optionally add the extensions tab.
		 *
		 * @since   1.3.0
		 * @version 1.8.5.1 Adjust index so it comes before the advanced tab.
		 *
		 * @param  string[] $tabs The existing set of tabs.
		 * @return string[]
		 */
		public function maybe_add_extensions_tab( $tabs ) {
			$actual_tab = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'general'; // phpcs:ignore

			/* Set the tab to 'extensions' */
			$_GET['tab'] = 'extensions';

			/**
			 * Filter the settings in the extensions tab.
			 *
			 * @since 1.3.0
			 *
			 * @param array $fields Array of fields. Empty by default.
			 */
			$settings = apply_filters( 'charitable_settings_tab_fields_extensions', array() );

			/* Set the tab back to whatever it actually is */
			$_GET['tab'] = $actual_tab;

			if ( ! empty( $settings ) ) {
				$tabs = charitable_add_settings_tab(
					$tabs,
					'extensions',
					__( 'Extensions', 'charitable' ),
					array(
						'index' => 7,
					)
				);
			}

			// Ensure the advanced tab is always last by moving it to the end of the array.
			if ( isset( $tabs['advanced'] ) ) {
				$advanced_tab = $tabs['advanced'];
				unset( $tabs['advanced'] );
				$tabs['advanced'] = $advanced_tab;
			}

			return $tabs;
		}

		/**
		 * Add the hidden "extensions" section field.
		 *
		 * @since  1.6.7
		 *
		 * @param  array $fields All the settings fields.
		 * @return array
		 */
		public function add_hidden_extensions_setting_field( $fields ) {
			if ( ! array_key_exists( 'extensions', $fields ) ) {
				return $fields;
			}

			$fields['extensions']['section'] = array(
				'title'    => '',
				'type'     => 'hidden',
				'priority' => 10000,
				'value'    => 'extensions',
				'save'     => false,
			);

			return $fields;
		}

		/**
		 * Register setting.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function register_settings() {
			if ( ! charitable_is_settings_view() ) {
				return;
			}

			register_setting( 'charitable_settings', 'charitable_settings', array( $this, 'sanitize_settings' ) );

			$fields = $this->get_fields();

			if ( empty( $fields ) ) {
				return;
			}

			$sections = array_merge( $this->get_sections(), $this->get_dynamic_groups() );

			/* Register each section */
			foreach ( $sections as $section_key => $section ) {
				$section_id = 'charitable_settings_' . $section_key;

				add_settings_section(
					$section_id,
					__return_null(),
					'__return_false',
					$section_id
				);

				if ( ! isset( $fields[ $section_key ] ) || empty( $fields[ $section_key ] ) ) {
					continue;
				}

				/* Sort by priority */
				$section_fields = $fields[ $section_key ];
				uasort( $section_fields, 'charitable_priority_sort' );

				/* Add the individual fields within the section */
				foreach ( $section_fields as $key => $field ) {
					$this->register_field( $field, array( $section_key, $key ) );
				}
			}
		}

		/**
		 * Sanitize submitted settings before saving to the database
		 * This includes: "workaround" for Stripe keys with 1.7.0.0, additional settings options after 1.7.0.6+
		 *
		 * @since  1.0.0
		 *
		 * @param  array $values The submitted values.
		 * @return string
		 */
		public function sanitize_settings( $values ) {

			$old_values = get_option( 'charitable_settings', array() );
			$new_values = array();

			if ( ! is_array( $old_values ) ) {
				$old_values = array();
			}

			if ( ! is_array( $values ) ) {
				$values = array();
			}

			/* Loop through all fields, merging the submitted values into the master array */
			foreach ( $values as $section => $submitted ) {
				$new_values = array_merge( $new_values, $this->get_section_submitted_values( $section, $submitted ) );
			}

			$settings_keys = array_keys( $new_values );

			if ( charitable_is_debug() ) {
				error_log( 'santiize_settings' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'values:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( 'old_values:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $old_values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( 'new_values:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $new_values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			// determine if Charitable gateway "test_mode" is being changed, and if so add a notice to the user.
			if ( array_key_exists( 'test_mode', $new_values ) && array_key_exists( 'test_mode', $old_values ) && $old_values['test_mode'] !== $new_values['test_mode'] ) {
				$old_settings_keys = array_keys( $old_values );
				$dismissible       = true;

				// Check if Square has connection issues for the new mode
				$square_has_issues = $this->check_square_connection_for_mode_change( $new_values['test_mode'] );

				// Determine notice type and message
				$notice_type = $square_has_issues ? 'error' : 'warning';
				$gateway_list = array();

				if ( in_array( 'gateways_stripe', $old_settings_keys ) ) {
					$gateway_list[] = 'Stripe';
				}

				if ( $square_has_issues ) {
					$gateway_list[] = 'Square';
				}

				if ( ! empty( $gateway_list ) ) {
					$gateway_text = implode( ' and ', $gateway_list );
					$message = sprintf(
						/* translators: %s: comma-separated list of gateway names */
						__( 'Some active payment gateways <strong>(including %s)</strong> might have reset their connections due to an update in the test mode. Please check your active payment gateways to ensure they are still connected.', 'charitable' ),
						$gateway_text
					);
				} else {
					$message = __( 'Some active payment gateways might have reset their connections due to an update in the test mode. Please check your active payment gateways to ensure they are still connected.', 'charitable' );
				}

				// Add additional Square-specific message if Square has connection issues
				if ( $square_has_issues ) {
					$mode_label = $new_values['test_mode'] ? __( 'test', 'charitable' ) : __( 'live', 'charitable' );
					$message .= '<br><br><strong>' . sprintf(
						/* translators: %s: mode (test or live) */
						__( 'Square payment gateway is enabled but not connected in %s mode. Please connect your Square account to continue processing payments.', 'charitable' ),
						$mode_label
					) . '</strong>';
				}

				charitable_get_admin_notices()->add_notice( $message, $notice_type, false, $dismissible );
			}

			if ( in_array( 'gateways_stripe', $settings_keys ) && charitable()->is_stripe_connect_addon() && false === charitable_using_stripe_connect() ) {
				// non-existant array keys in the array for API keys might be a result of the "manual update key" feature which is based on ajax and might not load keys into form if the user doesn't click on the link in the settings to view/edit them.
				if ( ! array_key_exists( 'live_secret_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['live_secret_key'] ) && ( false !== $old_values['gateways_stripe']['live_secret_key'] ) ) {
					$values['gateways_stripe']['live_secret_key'] = $old_values['gateways_stripe']['live_secret_key'];
				}
				if ( ! array_key_exists( 'live_public_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['live_public_key'] ) && ( false !== $old_values['gateways_stripe']['live_public_key'] ) ) {
					$values['gateways_stripe']['live_public_key'] = $old_values['gateways_stripe']['live_secret_key'];
				}
				if ( ! array_key_exists( 'test_secret_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['test_secret_key'] ) && ( false !== $old_values['gateways_stripe']['test_secret_key'] ) ) {
					$values['gateways_stripe']['test_secret_key'] = $old_values['gateways_stripe']['test_secret_key'];
				}
				if ( ! array_key_exists( 'test_public_key', $values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['test_public_key'] ) && ( false !== $old_values['gateways_stripe']['test_public_key'] ) ) {
					$values['gateways_stripe']['test_public_key'] = $old_values['gateways_stripe']['test_public_key'];
				}
				if ( charitable_is_debug() ) {
					error_log( 'update:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( print_r( $values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
				}
			}

			// determine if stripe is being returned as new values - if so, then perform the API key save workaround.
			if ( in_array( 'gateways_stripe', $settings_keys ) && charitable()->is_stripe_connect_addon() && false === charitable_using_stripe_connect() ) {
				if ( charitable_is_debug() ) {
					error_log( 'there is a stripe connect addon but we are not using stripe connect' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				// the stripe connect addon is installed AND the AM Stripe Connect isn't being used... so it's possible the keys could be manual and the user could be updating them... therefore let's just make sure the new values match the values incoming.
				$new_values['gateways_stripe']['live_secret_key'] = ( isset( $values['gateways_stripe']['live_secret_key'] ) && ( false !== $values['gateways_stripe']['live_secret_key'] ) ) ? $values['gateways_stripe']['live_secret_key'] : null;
				$new_values['gateways_stripe']['live_public_key'] = ( isset( $values['gateways_stripe']['live_public_key'] ) && ( false !== $values['gateways_stripe']['live_public_key'] ) ) ? $values['gateways_stripe']['live_public_key'] : null;
				$new_values['gateways_stripe']['test_secret_key'] = ( isset( $values['gateways_stripe']['test_secret_key'] ) && ( false !== $values['gateways_stripe']['test_secret_key'] ) ) ? $values['gateways_stripe']['test_secret_key'] : null;
				$new_values['gateways_stripe']['test_public_key'] = ( isset( $values['gateways_stripe']['test_public_key'] ) && ( false !== $values['gateways_stripe']['test_public_key'] ) ) ? $values['gateways_stripe']['test_public_key'] : null;
			} elseif ( in_array( 'gateways_stripe', $settings_keys ) ) {
				// otherwise we preserve the keys.
				if ( charitable_is_debug() ) {
					error_log( 'charitable debug, final with debug on:' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					$new_values['gateways_stripe']['live_secret_key'] = ( isset( $values['gateways_stripe']['live_secret_key'] ) && ( false !== $values['gateways_stripe']['live_secret_key'] ) ) ? $values['gateways_stripe']['live_secret_key'] : null;
					$new_values['gateways_stripe']['live_public_key'] = ( isset( $values['gateways_stripe']['live_public_key'] ) && ( false !== $values['gateways_stripe']['live_public_key'] ) ) ? $values['gateways_stripe']['live_public_key'] : null;
					$new_values['gateways_stripe']['test_secret_key'] = ( isset( $values['gateways_stripe']['test_secret_key'] ) && ( false !== $values['gateways_stripe']['test_secret_key'] ) ) ? $values['gateways_stripe']['test_secret_key'] : null;
					$new_values['gateways_stripe']['test_public_key'] = ( isset( $values['gateways_stripe']['test_public_key'] ) && ( false !== $values['gateways_stripe']['test_public_key'] ) ) ? $values['gateways_stripe']['test_public_key'] : null;
				} else {
					$new_values['gateways_stripe']['live_secret_key'] = ( isset( $old_values['gateways_stripe']['live_secret_key'] ) && ( false !== $old_values['gateways_stripe']['live_secret_key'] ) ) ? $old_values['gateways_stripe']['live_secret_key'] : null;
					$new_values['gateways_stripe']['live_public_key'] = ( isset( $old_values['gateways_stripe']['live_public_key'] ) && ( false !== $old_values['gateways_stripe']['live_public_key'] ) ) ? $old_values['gateways_stripe']['live_public_key'] : null;
					$new_values['gateways_stripe']['test_secret_key'] = ( isset( $old_values['gateways_stripe']['test_secret_key'] ) && ( false !== $old_values['gateways_stripe']['test_secret_key'] ) ) ? $old_values['gateways_stripe']['test_secret_key'] : null;
					$new_values['gateways_stripe']['test_public_key'] = ( isset( $old_values['gateways_stripe']['test_public_key'] ) && ( false !== $old_values['gateways_stripe']['test_public_key'] ) ) ? $old_values['gateways_stripe']['test_public_key'] : null;
				}
			}

			// preserve any webhook keys, regardless if stripe connect addon is active or not.
			if ( in_array( 'gateways_stripe', $settings_keys ) ) {
				if ( ! empty( $old_values['gateways_stripe'] ) && array_key_exists( 'test_webhook_id', $old_values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['test_webhook_id'] ) ) {
					$new_values['gateways_stripe']['test_webhook_id'] = $old_values['gateways_stripe']['test_webhook_id'];
				}
				if ( ! empty( $old_values['gateways_stripe'] ) && array_key_exists( 'live_webhook_id', $old_values['gateways_stripe'] ) && isset( $old_values['gateways_stripe']['live_webhook_id'] ) ) {
					$new_values['gateways_stripe']['live_webhook_id'] = $old_values['gateways_stripe']['live_webhook_id'];
				}
			}

			// account for incoming legacy licenses (1.7.0.4+)
			// all we are doing is adding the licenses into the values so that the save_license hook can retreieve them and validate, etc.
			if ( ! empty( $values['legacy_licenses'] ) && isset( $values['advanced']['section_hidden_licenses'] ) && 'licenses' === $values['advanced']['section_hidden_licenses'] ) {
				if ( ! isset( $new_values['licenses']['legacy'] ) ) {
					$new_values['licenses_legacy'] = (array) $values['legacy_licenses'];
				}
			}

			// ensure any hex color text files are valid hex fields.
			if ( isset( $new_values['donation_form_default_highlight_colour'] ) ) {
				$color_to_test = esc_html( $new_values['donation_form_default_highlight_colour'] );
				$color_valid   = false;
				// check for a hex color string
				if ( preg_match( '/^#[a-f0-9]{6}$/i', $color_to_test ) ) {
					// hex color is valid
					$color_valid = true;
				}
				// Check for a hex color string without hash.
				if ( preg_match( '/^[a-f0-9]{6}$/i', $color_to_test ) ) {
					// hex color is valid
					$color_valid = true;
					$new_values['donation_form_default_highlight_colour'] = '#' . $color_to_test;
				}
				if ( false === $color_valid ) {
					// not valid, make sure it is blank.
					$new_values['donation_form_default_highlight_colour'] = false;
				}
				// clear the transient that stores the color styles.
				delete_transient( 'charitable_custom_styles' );
			}

			$values = wp_parse_args( $new_values, $old_values );

			// Sanitize decimal_count to prevent XSS attacks.
			if ( isset( $values['decimal_count'] ) ) {
				$values['decimal_count'] = absint( $values['decimal_count'] );
				// Ensure decimal_count is between 0 and 10.
				$values['decimal_count'] = min( max( $values['decimal_count'], 0 ), 10 );
			}

			if ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) {
				error_log( 'santiize_settings values updated' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $values, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			/**
			 * Filter sanitized settings.
			 *
			 * @since 1.0.0
			 *
			 * @param array $values     All values, merged.
			 * @param array $new_values Newly submitted values.
			 * @param array $old_values Old settings.
			 */

			$values = apply_filters( 'charitable_save_settings', $values, $new_values, $old_values );

			// save a temp transient for legacy licenses (1.7.0.8+)
			// purpose is to provide better error reporting (transient should be deleted after settings page (advanced tab) is loaded).
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['charitable_settings']['legacy_licenses'] ) ) {
				$referer = wp_get_referer();
				if ( false !== strpos( $referer, 'admin.php?page=charitable-settings&tab=advanced' ) ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array data, sanitized later
					$legacy_licenses = wp_unslash( $_POST['charitable_settings']['legacy_licenses'] );
					set_transient( '_charitable_legacy_license_info', $legacy_licenses, MINUTE_IN_SECONDS );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$this->add_update_message( __( 'Settings saved', 'charitable' ), 'success' );

			return $values;
		}

		/**
		 * Checkbox settings should always be either 1 or 0.
		 *
		 * @since  1.0.0
		 *
		 * @param  mixed $value Submitted value for field.
		 * @param  array $field Field definition.
		 * @return int
		 */
		public function sanitize_checkbox_value( $value, $field ) {
			if ( isset( $field['type'] ) && 'checkbox' == $field['type'] ) {
				$value = intval( $value && 'on' == $value );
			}

			return $value;
		}

		/**
		 * Render field. This is the default callback used for all fields, unless an alternative callback has been specified.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Field definition.
		 * @return void
		 */
		public function render_field( $args ) {
			$field_type = isset( $args['type'] ) ? $args['type'] : 'text';

			charitable_admin_view( 'settings/' . $field_type, $args );
		}

		/**
		 * Returns an array of all pages in the id=>title format.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_pages() {
			if ( ! isset( $this->pages ) ) {
				$this->pages = charitable_get_pages_options();
			}

			return $this->pages;
		}

		/**
		 * Add an update message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message     The message text.
		 * @param  string  $type        The type of message. Options: 'error', 'success', 'warning', 'info'.
		 * @param  boolean $dismissible Whether the message can be dismissed.
		 * @return void
		 */
		public function add_update_message( $message, $type = 'error', $dismissible = true ) {
			if ( ! in_array( $type, array( 'error', 'success', 'warning', 'info' ) ) ) {
				$type = 'error';
			}

			charitable_get_admin_notices()->add_notice( $message, $type, false, $dismissible );
		}

		/**
		 * Recursively add settings fields, given an array.
		 *
		 * @since   1.0.0
		 * @version 1.8.7
		 *
		 * @param  array $field The setting field.
		 * @param  array $keys  Array containing the section key and field key.
		 * @return void
		 */
		private function register_field( $field, $keys ) {
			$section_id = 'charitable_settings_' . $keys[0];

			if ( isset( $field['render'] ) && ! $field['render'] ) {
				return;
			}

			/* Drop the first key, which is the section identifier */
			$field['name'] = implode( '][', $keys );

			if ( ! $this->is_dynamic_group( $keys[0] ) ) {
				array_shift( $keys );
			}

			$field['key']     = $keys;
			$field['classes'] = $this->get_field_classes( $field ); // deprecated?
			$field['class']   = $this->get_field_classes( $field );
			$callback         = isset( $field['callback'] ) ? $field['callback'] : array( $this, 'render_field' );
			$label            = $this->get_field_label( $field, end( $keys ) );

			add_settings_field(
				sprintf( 'charitable_settings_%s', implode( '_', $keys ) ),
				$label,
				$callback,
				$section_id,
				$section_id,
				$field
			);
		}

		/**
		 * Return the label for the given field.
		 *
		 * @since  1.0.0
		 *
		 * @param  array  $field The field definition.
		 * @param  string $key   The field key.
		 * @return string
		 */
		private function get_field_label( $field, $key ) { // phpcs:ignore
			$label = '';

			if ( isset( $field['label_for'] ) ) {
				$label = $field['label_for'];
			}

			if ( isset( $field['title'] ) ) {
				$label = $field['title'];
			}

			return $label;
		}

		/**
		 * Return a space separated string of classes for the given field.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		private function get_field_classes( $field ) {
			$classes = array( 'charitable-settings-field' );

			if ( isset( $field['class'] ) ) {
				$classes[] = $field['class'];
			}

			/**
			 * Filter the list of classes to apply to settings fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $classes The list of classes.
			 * @param array $field   The field definition.
			 */
			$classes = apply_filters( 'charitable_settings_field_classes', $classes, $field );

			return implode( ' ', $classes );
		}

		/**
		 * Return an array with all the fields & sections to be displayed.
		 *
		 * @uses   charitable_settings_fields
		 * @see    Charitable_Settings::register_setting()
		 * @since  1.0.0
		 *
		 * @return array
		 */
		private function get_fields() {
			/**
			 * Use the charitable_settings_tab_fields to include the fields for new tabs.
			 * DO NOT use it to add individual fields. That should be done with the
			 * filters within each of the methods.
			 */
			$fields = array();

			foreach ( $this->get_sections() as $section_key => $section ) {
				/**
				 * Filter the array of fields to display in a particular tab.
				 *
				 * @since 1.0.0
				 *
				 * @param array $fields Array of fields.
				 */
				$fields[ $section_key ] = apply_filters( 'charitable_settings_tab_fields_' . $section_key, array() );
			}

			/**
			 * Filter the array of settings fields.
			 *
			 * @since 1.0.0
			 *
			 * @param array $fields Array of fields.
			 */
			return apply_filters( 'charitable_settings_tab_fields', $fields );
		}

		/**
		 * Get the submitted value for a particular setting.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key       The key of the setting being saved.
		 * @param  array  $field     The setting field.
		 * @param  array  $submitted The submitted values.
		 * @param  string $section   The section being saved.
		 * @return mixed|null        Returns null if the value was not submitted or is not applicable.
		 */
		private function get_setting_submitted_value( $key, $field, $submitted, $section ) {
			$value = null;

			if ( isset( $field['save'] ) && ! $field['save'] ) {
				return $value;
			}

			$field_type = isset( $field['type'] ) ? $field['type'] : '';

			switch ( $field_type ) {

				case 'checkbox':
					$value = intval( array_key_exists( $key, $submitted ) && ( 'on' === $submitted[ $key ] || 1 === (int) $submitted[ $key ] ) );
					break;

				case 'multi-checkbox':
					$value = isset( $submitted[ $key ] ) ? $submitted[ $key ] : array();
					break;

				case '':
				case 'heading':
					return $value;

				default:
					if ( ! array_key_exists( $key, $submitted ) ) {
						return $value;
					}

					$value = $submitted[ $key ];

			}//end switch

			/**
			 * General way to sanitize values. If you only need to sanitize a
			 * specific setting, used the filter below instead.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed  $value     The current setting value.
			 * @param array  $field     The field configuration.
			 * @param array  $submitted All submitted data.
			 * @param string $key       The setting key.
			 * @param string $section   The section being saved.
			 */
			$value = apply_filters( 'charitable_sanitize_value', $value, $field, $submitted, $key, $section );

			/**
			 * Sanitize the setting value.
			 *
			 * The filter hook is formatted like this: charitable_sanitize_value_{$section}_{$key}.
			 *
			 * @since 1.5.0
			 *
			 * @param mixed $value     The current setting value.
			 * @param array $field     The field configuration.
			 * @param array $submitted All submitted data.
			 */
			return apply_filters( 'charitable_sanitize_value_' . $section . '_' . $key, $value, $field, $submitted );
		}

		/**
		 * Return the submitted values for the given section.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $section   The section being edited.
		 * @param  array  $submitted The submitted values.
		 * @return array
		 */
		private function get_section_submitted_values( $section, $submitted ) {
			$values      = array();
			$form_fields = $this->get_fields();

			if ( ! isset( $form_fields[ $section ] ) ) {
				return $values;
			}

			foreach ( $form_fields[ $section ] as $key => $field ) {
				$value = $this->get_setting_submitted_value( $key, $field, $submitted, $section );

				if ( is_null( $value ) ) {
					continue;
				}

				if ( $this->is_dynamic_group( $section ) ) {
					$values[ $section ][ $key ] = $value;
					continue;
				}

				$values[ $key ] = $value;
			}

			return $values;
		}

		/**
		 * Return list of dynamic groups.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		private function get_dynamic_groups() {
			if ( ! isset( $this->dynamic_groups ) ) {
				/**
				 * Filter the list of dynamic groups.
				 *
				 * @since 1.0.0
				 *
				 * @param array $groups The dynamic groups.
				 */
				$this->dynamic_groups = apply_filters( 'charitable_dynamic_groups', array() );
			}

			return $this->dynamic_groups;
		}

		/**
		 * Returns whether the given key indicates the start of a new section of the settings.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $composite_key The unique key for this group.
		 * @return boolean
		 */
		private function is_dynamic_group( $composite_key ) {
			return array_key_exists( $composite_key, $this->get_dynamic_groups() );
		}

		/* DEPRECATED FUNCTIONS */

		/**
		 * Get the update messages.
		 *
		 * @deprecated 1.7.0
		 *
		 * @since 1.4.13 Deprecated.
		 */
		public function get_update_messages() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.13',
				'Charitable_Admin_Notices::get_notices()'
			);

			return charitable_get_admin_notices()->get_notices();
		}

		/**
		 * Display upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since   1.7.0.4
		 * @version 1.8.8
		 *
		 * @param string $view Current view inside the plugin settings page.
		 * @param string $css_class CSS class for the cta.
		 * @param bool   $show_close_button Whether to show the close button.
		 */
		public function settings_cta( $view = false, $css_class = 'reports-lite-cta', $show_close_button = false ) { // phpcs:ignore

			$no_show_cta = array(
				'donors',
				'security',
				'marketing',
			);

			if ( in_array( $view, $no_show_cta ) ) {
				return;
			}

			if ( charitable_is_pro() ) {
				// no need to display this cta since they have a valid license.
				return;
			}

			if ( get_option( 'charitable_lite_settings_upgrade', false ) || apply_filters( 'charitable_lite_settings_upgrade', false ) ) {
				return;
			}
			?>
			<?php if ( $show_close_button ) : ?>
				<div class="charitable-cta-dismiss-container">
					<button type="button" class="button-link charitable-banner-dismiss dismiss">x</button>
				</div>
			<?php endif; ?>
			<?php charitable_render_global_upgrade_cta( $css_class ); ?>
			<script type="text/javascript">
				jQuery( function ( $ ) {
					$( document ).on( 'click', '.settings-lite-cta .dismiss', function ( event ) {
						event.preventDefault();
						$.post( ajaxurl, {
							action: 'charitable_lite_settings_upgrade',
							charitable_action: 'remove_lite_cta'
						} );
						$( '.settings-lite-cta' ).remove();
					} );
				} );
			</script>
			<?php
		}

		/**
		 * Show the settings CTA.
		 *
		 * @since   1.8.5.1
		 * @version 1.8.9
		 *
		 * @return void
		 */
		public function show_settings_cta() {

			$tab_values_to_check = array(
				'marketing',
				'donors',
				'security',
			);

			$tab_value = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : ''; // phpcs:ignore

			if ( in_array( $tab_value, $tab_values_to_check ) ) { // phpcs:ignore
				ob_start();
				?>

				<div class="charitable-education-page">

					<?php
					if ( 'marketing' === $tab_value ) {
						$this->get_settings_cta_education_content_marketing_settings();
					} elseif ( 'donors' === $tab_value ) {
						$this->get_settings_cta_education_content_donor_settings();
					} elseif ( 'security' === $tab_value && ! charitable_is_pro() ) {
						$this->get_settings_cta_education_content_advanced_security_settings();
					}
					?>
					<?php if ( charitable_is_pro() ) : ?>
						<div class="charitable-education-page-disabled-download">
							<p>
							<?php
							printf(
								/* translators: %s: Documentation URL */
								esc_html__( 'Please add your license and upgrade to Charitable Pro for more advanced security features. Please %s on how to download Charitable Pro.', 'charitable' ),
								'<a href="https://wpcharitable.com/docs/download-charitable-pro/" target="_blank">' . esc_html__( 'see our documentation', 'charitable' ) . '</a>'
							);
							?>
							</p>
						</div>
					<?php else : ?>
					<div class="charitable-education-page-button">
						<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-upgrade/', $tab_value, __( 'Upgrade to Charitable Pro', 'charitable' ) ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Upgrade to Charitable Pro', 'charitable' ); ?></a>
					</div>
					<?php endif; ?>
				</div>
				<?php
				echo ob_get_clean(); // phpcs:ignore
			}
		}

		/**
		 * Get the settings CTA education content for the donor referrals sub tab.
		 *
		 * @since 1.8.5.1
		 *
		 * @return void
		 */
		public function get_settings_cta_education_content_donor_settings() {
			?>

			<div class="charitable-education-page-heading">
				<h4><?php esc_html_e( 'New Donor Features', 'charitable' ); ?></h4>
				<p><?php esc_html_e( 'Give donors the power to manage their contributions to your organization with a new donor dashboard. Encourage donors to spread the word about your organization by offering rewards for successful referrals. And give donors the ability to post comments to campaigns when they donate.', 'charitable' ); ?></p>
				<p><?php esc_html_e( 'All of this is possible with the Charitable Pro plugin.', 'charitable' ); ?></p>
			</div>

			<div class="charitable-education-page-media">
				<div class="charitable-education-page-images">
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donor-dashboard/education-1.png" alt="<?php esc_attr_e( 'Donor Dashboard', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donor-dashboard/education-1.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Donor Dashboard', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Donor Dashboard', 'charitable' ); ?></figcaption>
					</figure>
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donor-referrals/education-2.png" alt="<?php esc_attr_e( 'Donor Referrals', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donor-referrals/education-2.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Donor Referrals', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Donor Referrals', 'charitable' ); ?></figcaption>
					</figure>
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donor-comments/education-3.png" alt="<?php esc_attr_e( 'Donor Comments', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donor-comments/education-3.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Donor Comments', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Donor Comments', 'charitable' ); ?></figcaption>
					</figure>
				</div>
			</div>

			<div class="charitable-education-page-caps">
				<p><?php esc_html_e( 'Give donors the power to manage their contributions to your organization.', 'charitable' ); ?></p>
				<ul>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Increased Donor Engagement', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Customizable Dashboard', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Donation History', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'View Campaigns', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'View Comments', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Download Donation Receipts', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Tracking Personalized Notifications', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Donor Profile Management', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'See Each Donor\'s Referrals', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Track Referral Success Rates', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Customizable Referral Rewards', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Easy Promotion of Campaigns', 'charitable' ); ?></li>

				</ul>
			</div>
			<?php
		}

		/**
		 * Get the settings CTA education content for the donor referrals sub tab.
		 *
		 * @since 1.8.5.1
		 *
		 * @return void
		 */
		public function get_settings_cta_education_content_marketing_settings() {
			?>

			<div class="charitable-education-page-heading">
				<h4><?php esc_html_e( 'Marketing', 'charitable' ); ?></h4>
				<p><?php esc_html_e( 'Turn one-time donors into ongoing supporters with your newsletter list. This feature allows you to connect to your email marketing account and automatically add donors to your email newsletter list. You can integrate your campaigns seamlessly with MailChimp, Campaign Monitor, MailerLite, ActiveCampaign, Mailster and MailPoet, allowing you to enable one or more providers.', 'charitable' ); ?></p>
			</div>

			<div class="charitable-education-page-media">
				<div class="charitable-education-page-images">
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/marketing/education-1.png" alt="<?php esc_attr_e( 'Marketing', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/marketing/education-1.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Marketing', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Choose a default list', 'charitable' ); ?></figcaption>
					</figure>
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/marketing/education-2.png" alt="<?php esc_attr_e( 'Marketing', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/marketing/education-2.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Marketing', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Sync lists', 'charitable' ); ?></figcaption>
					</figure>
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/marketing/education-3.png" alt="<?php esc_attr_e( 'Marketing', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/marketing/education-3.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Marketing', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Donation form options', 'charitable' ); ?></figcaption>
					</figure>
				</div>
			</div>

			<div class="charitable-education-page-caps">
				<p><?php esc_html_e( 'Turn one-time donors into ongoing supporters.', 'charitable' ); ?></p>
				<ul>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Easy Integration', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Built-in contact consent options', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Automatically add donors to your email list', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Organize donors with tags', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Send email series to keep donors engaged', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Enable multiple email services', 'charitable' ); ?></li>
				</ul>
			</div>
			<?php
		}

		/**
		 * Get the settings CTA education content for the donor referrals sub tab.
		 *
		 * @since 1.8.5.1
		 *
		 * @return void
		 */
		public function get_settings_cta_education_content_security_settings() {
			?>

			<div class="charitable-education-page-heading">
				<h4><?php esc_html_e( 'Security', 'charitable' ); ?></h4>
				<p><?php esc_html_e( 'It is important to protect against potential misuse while maintaining donor trust. CAPTCHA integration adds an essential layer of protection against automated attacks and spam donations. Every transaction is meticulously logged with IP address documentation and timestamp verification, providing a clear audit trail and helping prevent fraudulent activities.', 'charitable' ); ?></p>
			</div>

			<div class="charitable-education-page-media">
				<div class="charitable-education-page-images">
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/security/education-1.png" alt="<?php esc_attr_e( 'Security', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/security/education-1.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Security', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Captchas', 'charitable' ); ?></figcaption>
					</figure>
					<figure>
						<div class="charitable-education-page-images-image">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/security/education-3.png" alt="<?php esc_attr_e( 'Security', 'charitable' ); ?>">
							<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/security/education-3.png" class="hover" data-lity="" data-lity-desc="<?php esc_attr_e( 'Security', 'charitable' ); ?>"></a>
						</div>
						<figcaption><?php esc_html_e( 'Form options', 'charitable' ); ?></figcaption>
					</figure>
				</div>
			</div>

			<div class="charitable-education-page-caps">
				<p><?php esc_html_e( 'Protect against potential misuse while maintaining donor trust.', 'charitable' ); ?></p>
				<ul>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Easy Activation', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Record IP Addresses', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Block Bad Bots', 'charitable' ); ?></li>
				</ul>
			</div>
			<?php
		}

		/**
		 * Get the settings CTA education content for advanced security features.
		 *
		 * @since 1.8.9
		 *
		 * @return void
		 */
		public function get_settings_cta_education_content_advanced_security_settings() {
			?>

			<div class="charitable-education-page-heading">
				<h4><?php esc_html_e( 'Advanced Security Features', 'charitable' ); ?></h4>
				<p><?php esc_html_e( 'Charitable Pro offers a fortified layer of safety designed for high-volume campaigns. Upgrade to unlock sophisticated spam filtering and enhanced payment protection, ensuring the highest level of trust and security for your donors.', 'charitable' ); ?></p>
			</div>
			<div class="charitable-education-page-caps">
				<p><?php esc_html_e( 'Protect against potential misuse while maintaining donor trust.', 'charitable' ); ?></p>
				<ul>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'General Rate Limiting', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Stripe Rate Limiting', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'IP Blacklist/Whitelist', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Email Pattern Blocking', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Minimum Donation Features', 'charitable' ); ?></li>
					<li><i class="fa fa-solid fa-check"></i> <?php esc_html_e( 'Math Field Protection', 'charitable' ); ?></li>
				</ul>
			</div>
			<?php
		}

		/**
		 * Dismiss upgrade notice at the bottom on the plugin settings pages.
		 *
		 * @since 1.7.0.4
		 *
		 * @return void
		 */
		public function settings_cta_dismiss() {

			if ( ! charitable_current_user_can() ) {
				wp_send_json_error();
			}

			update_option( 'charitable_lite_settings_upgrade', time() );

			wp_send_json_success();
		}

		/**
		 * Check Square connection for the new mode.
		 *
		 * @since 1.8.5
		 *
		 * @param string $new_mode The new test mode.
		 * @return boolean True if Square has connection issues, false otherwise.
		 */
		private function check_square_connection_for_mode_change( $new_mode ) {
			// Check if Square gateway is active.
			$active_gateways = charitable_get_helper( 'gateways' )->get_active_gateways();

			if ( ! is_array( $active_gateways ) || ! in_array( 'Charitable_Gateway_Square', $active_gateways ) ) {
				return false;
			}

			// Determine the mode string.
			$mode = $new_mode ? 'test' : 'live';

			// Check if there's a Square connection for this mode.
			$connection = Charitable_Square_Connection::get( $mode );

			// Check if connection exists and is properly configured.
			if ( ! $connection || ! $connection->is_configured() || ! $connection->is_valid() ) {

				// Set a warning flag that will be checked on the payment page.
				update_option( 'charitable_square_mode_connection_warning', array(
					'mode' => $mode,
					'timestamp' => time(),
				) );

				return true; // Square has connection issues.
			} else {
				// Clear the warning if connection exists.
				delete_option( 'charitable_square_mode_connection_warning' );
				return false; // Square has no connection issues.
			}
		}
	}

endif;
