<?php
/**
 * A class to resolve compatibility issues with Translatepress.
 *
 * @package   Charitable/Classes/Charitable_Translatepress_Compat
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.48
 * @version   1.6.48
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Translatepress_Compat' ) ) :

	/**
	 * Charitable_Translatepress_Compat
	 *
	 * @since 1.6.48
	 */
	class Charitable_Translatepress_Compat {

		/**
		 * Create class object.
		 *
		 * @since 1.6.48
		 */
		public function __construct() {
			/* Load Donation & Campaign field translations late */
			add_action( 'wp_head', array( $this, 'load_late_translations' ), 200 );

			/**
			 * Do something with this class instance.
			 *
			 * @since 1.6.48
			 *
			 * @param Charitable_Translatepress_Compat $helper Translatepress compatibility class instance.
			 */
			do_action( 'charitable_translatepress_compat', $this );
		}

		/**
		 * TranslatePress shows translateable gettext strings by collecting any gettext calls made
		 * after the wp_head hook (priority 100), so here we deliberately run through the list of
		 * campaign & donation fields and re-run them through a call to __(), which forces them to
		 * be picked up by TranslatePress.
		 *
		 * @since  1.6.48
		 *
		 * @return void
		 */
		public function load_late_translations() {
			$field_apis = array(
				array(
					'fields' => charitable()->donation_fields(),
					'forms'  => array( 'donation_form', 'admin_form' ),
				),
				array(
					'fields' => charitable()->campaign_fields(),
					'forms'  => array( 'campaign_form', 'admin_form' ),
				),
			);

			$translateable_form_fields = array( 'label', 'placeholder' );

			foreach ( $field_apis as $api ) {
				$fields = $api['fields'];

				foreach ( $fields->get_fields() as $field ) {

					/* Update the field label. */
					$field->label = __( $field->label ); // phpcs:ignore

					foreach ( $api['forms'] as $form ) {
						$form_settings = $field->$form;

						if ( ! is_array( $form_settings ) ) {
							continue;
						}

						/* Translate form label and placeholder. */
						foreach ( $translateable_form_fields as $form_field ) {
							if ( array_key_exists( $form_field, $form_settings ) ) {
								$field->set( $form, $form_field, __( $form_settings[ $form_field ] ) ); // phpcs:ignore
							}
						}

						/* Translate options */
						if ( array_key_exists( 'options', $form_settings ) && is_array( $form_settings['options'] ) ) {
							$options = $form_settings['options'];

							foreach ( $options as $key => $value ) {
								$options[ $key ] = __( $value ); // phpcs:ignore
							}

							if ( in_array( $field->field, array( 'country', 'state' ) ) ) {
								asort( $options );
							}

							$field->set( $form, 'options', $options );
						}
					}
				}
			}

			if ( function_exists( 'charitable_get_recipient_types' ) ) {
				foreach ( charitable_get_recipient_types() as $option => $recipient_type ) {
					// Translators: %s is the label of the recipient type.
					$recipient_type['label']       = sprintf( __( '%s', 'charitable' ), $recipient_type['label'] ); // phpcs:ignore
					// Translators: %s is the label of the description.
					$recipient_type['description'] = sprintf( __( '%s', 'charitable' ), $recipient_type['description'] ); // phpcs:ignore

					charitable_register_recipient_type( $option, $recipient_type );
				}
			}
		}
	}

endif;
