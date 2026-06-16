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

if ( ! class_exists( 'Charitable_Field_Shortcode' ) ) :

	/**
	 * Class to add campaign shortcode field to a campaign form in the builder.
	 */
	class Charitable_Field_Shortcode extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define field type information.
			$this->name  = esc_html__( 'Shortcode', 'charitable' );
			$this->type  = 'shortcode';
			$this->icon  = 'fa-code';
			$this->order = 1000;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Shotcode', 'charitable' );
			$this->edit_type         = 'shortcode'; // was settings.
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip = esc_html__( 'Display any valid WordPress shortcode.', 'charitable' );
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
		 * @since 1.8.0
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 * @version 1.8.9.1
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			$headline  = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : false;
			$shortcode = ! empty( $field_data['shortcode'] ) ? $this->format_for_output( $field_data['shortcode'], $campaign_data ) : false;

			$title_html = $this->field_title( $this->name );

			$html = '<div class="charitable-field-preview-shortcode shortcode" data-field-type="' . $this->type . '">
					<div class="placeholder"><h5 class="charitable-field-preview-headline">' . $headline . '</h5></div>
						<div class="row">
							<div class="column">
								<span class="placeholder shortcode-preview">' . $shortcode . '</span>
							</div>
						</div>
				</div>';

			$final_html = $title_html . $this->field_wrapper( $html, $field_data );

			echo $final_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Donation Options display on the form front-end.
		 *
		 * @since   1.8.0
		 * @version 1.8.8.6
		 *
		 * @param array $field      Donation Options settings.
		 * @param array $deprecated Deprecated.
		 * @param array $form_data  Form data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			$campaign = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? charitable_get_campaign( $campaign_data['id'] ) : false;

			if ( ! $campaign ) {
				return;
			}

		$shortcode = ! empty( $field_data['shortcode'] ) ? $this->format_for_output( $field_data['shortcode'], $campaign_data ) : false;
		$css_class = ! empty( $field_data['css_class'] ) ? ' class="' . esc_attr( $field_data['css_class'] ) . '" ' : '';

		ob_start();

		if ( $shortcode ) :

			?>

		<div class="charitable-campaign-field_<?php echo esc_attr( $this->type ); ?>">
			<div <?php echo $css_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $css_class is already escaped with esc_attr() on line 115. ?>>
				<?php if ( $shortcode ) : ?>
					<?php echo do_shortcode( $shortcode ); ?>
				<?php endif; ?>
			</div>
		</div>

				<?php

			endif;

			$html = ob_get_clean();

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
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
				isset( $settings['headline'] ) ? $settings['headline'] : '',
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add a headline to this field.', 'charitable' ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['shortcode'] ) ? $this->format_for_setting( $settings['shortcode'] ) : false,
				esc_html__( 'Shortcode', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_shortcode' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'shortcode' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html( $this->tooltip ),
					'class'    => 'charitable-campaign-builder-shortcode',
				)
			);

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
		 * Format and sanitize field value for builder, in the setting left pane.
		 *
		 * @since 1.8.0
		 *
		 * @param int $field_value The value.
		 */
		public function format_for_setting( $field_value = false ) {

			return htmlspecialchars( $field_value ); // this will convert quotes, usually used in shortcodes.
		}

		/**
		 * Format and sanitize field, for use in the frontend (preview and campaign page).
		 *
		 * @since 1.8.0
		 *
		 * @param int $field_value The value.
		 */
		public function format_for_output( $field_value = false, $campaign_data = false ) {

			return $field_value;
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

	new Charitable_Field_Shortcode();

endif;