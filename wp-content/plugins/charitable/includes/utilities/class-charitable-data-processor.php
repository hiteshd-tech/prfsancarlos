<?php
/**
 * Responsible for accepting a set of raw data, such as form submission data,
 * and sanitizing and normalizing that data, depending on its data type and
 * the type of data (text, checkbox, etc.).
 *
 * @package   Charitable/Classes/Charitable_Data_Processor
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.9
 * @version   1.6.55
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Charitable_Data_Processor' ) ) :

	/**
	 * Data processor class.
	 *
	 * @since 1.5.9
	 */
	class Charitable_Data_Processor {
		/**
		 * The raw input data.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $data;

		/**
		 * The map of fields.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $fields;

		/**
		 * An array of valid options for specific fields.
		 *
		 * @since 1.6.51
		 *
		 * @var   array
		 */
		protected $options;

		/**
		 * This is the output data. It has the same structure
		 * as the map of fields, but has the sanitized &
		 * normalized values from the input data.
		 *
		 * @since 1.5.9
		 *
		 * @var   array
		 */
		protected $output;

		/**
		 * Whether the data is invalid.
		 *
		 * @since 1.5.9
		 *
		 * @var   boolean
		 */
		protected $invalid;

		/**
		 * Instantiate the formatter with a dataset and array of fields.
		 *
		 * @since 1.5.9
		 *
		 * @param array $data   The raw input data.
		 * @param array $fields The map of fields.
		 */
		public function __construct( $data, $fields, $options = array() ) {
			$this->data    = $data;
			$this->fields  = $fields;
			$this->options = $options;
			$this->output  = array();
			$this->invalid = false;

			$this->process_data( $this->fields );
		}

		/**
		 * Returns whether the data is valid.
		 *
		 * @since  1.5.9
		 *
		 * @return boolean
		 */
		public function is_valid() {
			return false == $this->invalid;
		}

		/**
		 * Return a single field's output data.
		 *
		 * @since  1.5.9
		 *
		 * @param  string       $key       The field key.
		 * @param  string|false $data_type Optional. The data type.
		 * @return mixed|null
		 */
		public function get( $key, $data_type = false ) {
			if ( $data_type ) {
				return $this->get_from_data_type( $key, $data_type );
			}

			return isset( $this->output[ $key ] ) ? $this->output[ $key ] : null;
		}

		/**
		 * Return a single field's output data, from a data type.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key       The field key.
		 * @param  string $data_type The data type.
		 * @return mixed|null
		 */
		public function get_from_data_type( $key, $data_type ) {
			return isset( $this->output[ $data_type ][ $key ] ) ? $this->output[ $data_type ][ $key ] : null;
		}

		/**
		 * Returns the full output array.
		 *
		 * @since  1.5.9
		 *
		 * @return array
		 */
		public function output() {
			return $this->output;
		}

		/**
		 * Process an array of fields.
		 *
		 * @since  1.5.9
		 *
		 * @param  array        $fields    Map of fields.
		 * @param  false|string $data_type Optional. Data type.
		 * @return void
		 */
		protected function process_data( $fields, $data_type = false ) {
			$data = array();

			foreach ( $fields as $key => $type ) {
				if ( is_array( $type ) ) {
					$this->process_data( $type, $key );
					continue;
				}

				$data[ $key ] = $this->process_field( $key, $type, $data_type );
			}

			if ( ! empty( $data ) ) {
				$this->set_output( $data, $data_type );
			}
		}

		/**
		 * Add a set of data to the output array.
		 *
		 * @since  1.5.9
		 *
		 * @param  array        $data      The processed data.
		 * @param  false|string $data_type Optional. Data type.
		 * @return void
		 */
		protected function set_output( $data, $data_type = false ) {
			if ( $data_type ) {
				$this->output[ $data_type ] = $data;
			} else {
				$this->output = $data;
			}
		}

		/**
		 * Apply the correct function to a field, based on the type of function
		 * and the key, type and data type of the field.
		 *
		 * @since  1.5.9
		 *
		 * @param  array $functions Stack of functions, in order of priority, with $args
		 *                          passed as the value of the function.
		 * @param  mixed $default   The default value to return if none of the functions exist.
		 * @return mixed|null
		 */
		protected function apply_function_to_field( $functions, $default = '' ) {
			foreach ( $functions as $function => $args ) {
				if ( method_exists( $this, $function ) ) {
					return call_user_func_array( array( $this, $function ), $args );
				}
			}

			return $default;
		}

		/**
		 * Process a single field, returning the set value or null.
		 *
		 * @since  1.5.9
		 *
		 * @param  string       $key       The field key.
		 * @param  string       $type      The type of field.
		 * @param  string|false $data_type Optional. The data type.
		 * @return mixed|null The set value of the field, or NULL if the
		 *                    field was not contained in the data.
		 */
		protected function process_field( $key, $type, $data_type = false ) {
			$sanitized_key       = str_replace( '-', '_', $key );
			$sanitized_type      = str_replace( '-', '_', $type );
			$sanitized_data_type = str_replace( '-', '_', $data_type );
			$field_options       = isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;

			/* Retrieve the value. */
			$value = $this->apply_function_to_field(
				array(
					'process_' . $sanitized_key       => array( $type, $data_type ),
					'process_' . $sanitized_data_type => array( $key, $type ),
					'process_' . $sanitized_type      => array( $key, $data_type ),
					'process_generic_field'           => array( $key ),
				)
			);

			/* Return the value after it is sanitized. */
			return $this->apply_function_to_field(
				array(
					'sanitize_' . $sanitized_key       => array( $value, $type, $data_type, $field_options ),
					'sanitize_' . $sanitized_data_type => array( $value, $key, $type, $field_options ),
					'sanitize_' . $sanitized_type      => array( $value, $key, $data_type, $field_options ),
					'sanitize_generic_field'           => array( $value, $key, $field_options ),
				),
				$value
			);
		}

		/**
		 * Process a checkbox field.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The field key.
		 * @return int|string Returns 0 if the checkbox was not checked, or the
		 *                    value of the checkbox if checked.
		 */
		protected function process_checkbox( $key ) {
			return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : 0;
		}

		/**
		 * Process a picture field.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The key of the picture field.
		 * @return int|false
		 */
		protected function process_picture( $key ) {
			$value = array_key_exists( $key, $this->data ) ? $this->data[ $key ] : '';

			/**
			 * If Javascript is enabled, we do not expect to have a $_FILES array with the
			 * picture, as the upload was already handled client-side.
			 */
			if ( ! $this->picture_file_exists( $key ) ) {
				return $value;
			}

			$value = $this->upload_attachment( $key );

			if ( is_wp_error( $value ) ) {
				charitable_get_notices()->add_errors_from_wp_error( $value );
				$value         = '';
				$this->invalid = true;
			}

			return $value;
		}

		/**
		 * Process a generic field. i.e. One that hasn't been processed
		 * by any of the other processors.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The field key.
		 * @return mixed|null The set value of the field, or NULL if the
		 *                    field was not contained in the data.
		 */
		public function process_generic_field( $key ) {
			return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
		}

		/**
		 * Sanitize a number.
		 *
		 * @since  1.5.9
		 *
		 * @param  string|int $value The number to be sanitized.
		 * @return int
		 */
		public static function sanitize_number( $value ) {
			return intval( $value );
		}

		/**
		 * Sanitize a value received from a datepicker.
		 *
		 * When decline_months is on, the JS datepicker sends yy-mm-dd (e.g. 2026-2-1).
		 * We normalize to Y-m-d (e.g. 2026-02-01) so MySQL and PHP never interpret
		 * the string as d-m-y (e.g. January 2 instead of February 1).
		 *
		 * @since   1.5.9
		 * @version 1.8.9.2
		 *
		 * @param  string $value The datepicker value.
		 * @return string|int If a date was chosen, returns the date in YYYY-MM-DD format.
		 *                    Otherwise, returns 0.
		 */
		public static function sanitize_datepicker( $value ) {
			if ( empty( $value ) ) {
				return 0;
			}

			if ( ! charitable()->registry()->get( 'i18n' )->decline_months() ) {
				$value = charitable_sanitize_date( $value, 'Y-m-d' );
				if ( false === $value ) {
					$value = 0;
				}
			} else {
				// Normalize to canonical Y-m-d so MySQL/PHP never interpret as d-m-y.
				$value = trim( $value );
				$dt    = DateTime::createFromFormat( 'Y-m-d', $value );
				if ( ! $dt ) {
					$dt = DateTime::createFromFormat( 'Y-n-j', $value );
				}
				if ( $dt ) {
					$value = $dt->format( 'Y-m-d' );
				} else {
					$value = 0;
				}
			}

			return $value;
		}

		/**
		 * Sanitize editor fields.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed $value The submitted value.
		 * @return string
		 */
		public static function sanitize_editor( $value ) {
			return wp_kses_post( $value );
		}

		/**
		 * Sanitize textarea.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed $value The submitted value.
		 * @return string
		 */
		public static function sanitize_textarea( $value ) {
			return sanitize_textarea_field( $value );
		}

		/**
		 * Sanitize submitted email.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed $value The submitted value.
		 * @return string
		 */
		public static function sanitize_email( $value ) {
			return sanitize_email( $value );
		}

		/**
		 * Escape a submitted url.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed $value The submitted value.
		 * @return string
		 */
		public static function sanitize_url( $value ) {
			return esc_url_raw( $value );
		}

		/**
		 * Fallback sanitization, using sanitize_text_field.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed $value The submitted value.
		 * @return string
		 */
		public static function sanitize_text( $value ) {
			return sanitize_text_field( $value );
		}

		/**
		 * Sanitize multi-checkbox fields.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed      $value     The submitted value.
		 * @param  string     $key       The field key.
		 * @param  string     $data_type The type of data.
		 * @param  null|array $options   The options for the field.
		 * @return string|array
		 */
		public static function sanitize_multi_checkbox( $value, $key, $data_type, $options = null ) {
			/* Multi-checkbox should return an empty string or an array. */
			if ( ! is_array( $value ) ) {
				return '';
			}

			if ( is_null( $options ) ) {
				return $value;
			}

			/* Check against a specific set of options that are valid for this field. */
			foreach ( $value as $i => $option ) {
				/* If the selected option is not valid, remove it. */
				if ( ! in_array( $option, $options ) ) {
					unset( $value[ $i ] );
				}
			}

			return $value;
		}

		/**
		 * Fallback sanitization.
		 *
		 * @since  1.6.51
		 *
		 * @param  mixed      $value   The submitted value.
		 * @param  string     $key     The field key.
		 * @param  null|array $options The options for the field.
		 * @return string
		 */
		public static function sanitize_generic_field( $value, $key, $options = null ) {
			/* Check against a specific set of options that are valid for this field. */
			if ( is_array( $options ) ) {
				$value = in_array( $value, $options ) ? $value : '';
			}

			return $value;
		}

		/**
		 * Returns true if a file was found for the picture in the $_FILES array.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $key The picture file key.
		 * @return boolean
		 */
		protected function picture_file_exists( $key ) {
			return isset( $_FILES ) && isset( $_FILES[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Uploads a file and attaches it to the given post.
		 *
		 * @since  1.5.9
		 *
		 * @param  string $file_key  Key of the file input.
		 * @param  int    $post_id   Post ID.
		 * @return int|WP_Error ID of the attachment or a WP_Error object on failure.
		 */
		public function upload_attachment( $file_key, $post_id = 0 ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			$overrides = $this->get_file_overrides( $file_key, $overrides );

			return media_handle_upload( $file_key, $post_id, array(), $overrides );
		}

		/**
		 * Return overrides array for use with upload_attachment() methods.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $file_key  Reference to a single element of `$_FILES`. Call the
		 *                           function once for each uploaded file.
		 * @param  array  $overrides Optional. An associative array of names=>values to
		 *                           override default variables. Default false.
		 * @return array
		 */
		protected function get_file_overrides( $file_key, $overrides = array() ) {
			$allowed_mimes = array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'bmp'          => 'image/bmp',
				'tif|tiff'     => 'image/tiff',
				'ico'          => 'image/x-icon',
			);

			$defaults = array(
				'test_form' => false,
				'mimes'     => apply_filters( 'charitable_file_' . $file_key . '_allowed_mimes', $allowed_mimes ),
			);

			$overrides = wp_parse_args( $overrides, $defaults );

			return $overrides;
		}
	}

endif;
