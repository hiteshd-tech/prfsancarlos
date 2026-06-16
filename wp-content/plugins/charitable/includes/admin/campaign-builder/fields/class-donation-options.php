<?php
/**
 * Class to add donation options to a campaign form in the builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Field_Donation_Options' ) ) :

	/**
	 * Class to add campaign donation options field to a campaign form in the builder.
	 */
	class Charitable_Field_Donation_Options extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define field type information.
			$this->name  = esc_html__( 'Donation Options', 'charitable' );
			$this->type  = 'donation-options';
			$this->icon  = 'fa-sliders';
			$this->order = 30;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Donation Options', 'charitable' );
			$this->edit_type         = 'donation-options'; // was settings
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Donation Options options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Donation Options settings.
		 */
		public function field_options( $field ) {
			/*
			 * Basic field options.
			 */

			// Options open markup.
		}

		/**
		 * Donation Options preview inside the builder.
		 *
		 * @since   1.8.0
		 * @version 1.8.8.5
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			// Define data.
			$placeholder   = ! empty( $field_data['placeholder'] ) ? $field_data['placeholder'] : '';
			$default_value = ! empty( $field_data['default_value'] ) ? $field_data['default_value'] : '';

			echo '<h4>' . esc_html( $this->name ) . '</h4>';

			echo '<div class="charitable-field-preview-donation-options donation-options" data-field-type="' . esc_attr( $this->type ) . '"><div class="row">
            <div class="column">
                <span class="placeholder"></span>
                <span class="placeholder"></span>
            </div>
            <div class="column">
                <span class="placeholder"></span>
                <span class="placeholder"></span>
            </div>
            <div class="column">
                <span class="placeholder"></span>
                <span class="placeholder"></span>
            </div>
        </div></div>';
		}

		/**
		 * Donation Options display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field      Donation Options settings.
		 * @param array $deprecated Deprecated.
		 * @param array $form_data  Form data and settings.
		 */
		public function field_display( $field, $deprecated = false, $form_data = false ) {

			echo '[Real Donation Options Here]';
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field          Social Sharing settings.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['css_class'] ) ? $settings['css_class'] : false,
				esc_html__( 'CSS Class', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_css_class' . '_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'css_class' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add CSS classes (seperated by a space) for this field to customize it\'s appearance in your theme.', 'charitable' ),
				)
			);

			?>

			<div class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>">
				<p><button class="go-to-settings-button charitable-button" data-settings-section="donation-options">Go To Settings for Donation Options</button></p>
			</div>

			<?php

			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 *
		 * @param array $forms Forms on the current page.
		 */
		public function frontend_js( $forms ) {
		}

		/**
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Donation Options ID.
		 * @param mixed $field_submit Donation Options value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $form_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Donation Options ID.
		 * @param mixed $field_submit Donation Options value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $form_data ) {
		}
	}

	new Charitable_Field_Donation_Options();

endif;