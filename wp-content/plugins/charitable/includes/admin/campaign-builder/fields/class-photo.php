<?php
/**
 * Class to add photo field to a campaign form in the builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Field_Photo' ) ) :

	/**
	 * Charitable_Field_Photo
	 *
	 * @since 1.8.0
	 */
	class Charitable_Field_Photo extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define field type information.
			$this->name  = esc_html__( 'Photo', 'charitable' );
			$this->type  = 'photo';
			$this->icon  = 'fa-image';
			$this->order = 30;

			$this->align_default = 'center';

			// Edit/Duplication information.
			$this->edit_label        = esc_html__( 'Edit Photo', 'charitable' );
			$this->edit_type         = 'photo';
			$this->edit_section      = 'standard';
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;

			// Misc.
			$this->tooltip = esc_html__( 'Select a photo from your media gallery.', 'charitable' );

			// Define additional field properties.
			add_action( 'charitable_builder_frontend_js', array( $this, 'frontend_js' ) );
			add_action( 'charitable_builder_backend_scripts', array( $this, 'builder_js' ) );
		}

		/**
		 * Photo options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Photo settings.
		 */
		public function field_options( $field ) {
			/*
			 * Basic field options.
			 */

			// Options open markup.
		}

		/**
		 * Render the field.
		 *
		 * @since 1.8.0
		 *
		 * @param array   $field_data     Any field data.
		 * @param array   $campaign_data  Amount data and settings.
		 * @param integer $field_id       The field ID.
		 * @param string  $mode           Where the field is being displayed ("preview" or "template").
		 * @param array   $template_data  Tempalate data.
		 */
		public function render( $field_data = false, $campaign_data = false, $field_id = false, $mode = 'template', $template_data = false ) {

			if ( 'preview' === $mode ) {

				// Define data.
				$width_percentage = isset( $field_data['width_percentage'] ) ? absint( $field_data['width_percentage'] ) : 100;
				if ( $width_percentage >= 100 ) {
					$width_percentage = 95;
				}

				// Do we have an uploaded photo we can use as a better placeholder?
				$image_url = ! empty( $field_data['file'] ) ? esc_url( $field_data['file'] ) : false;
				$image_url = false === $image_url && ! empty( $field_data['default'] ) ? esc_url( $field_data['default'] ) : $image_url;

				if ( $image_url ) {
					return '<div class="primary-image-container has-image placeholder"><div class="primary-image"><img class="charitable-campaign-builder-preview-photo" src="' . esc_url( $image_url ) . '" /></div></div>';
				} else {
					return '<div class="primary-image-container placeholder"><div class="primary-image"><img src="' .  esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/photo/temp-icon.svg' ) . '" class="temp-icon" alt="" /></div></div>';
				}

			} else {

				$image_url  = ! empty( $field_data['file'] ) ? esc_url( $field_data['file'] ) : '';
				$image_url  = '' === $image_url && filter_var( $field_data['default'], FILTER_VALIDATE_URL ) ? esc_url( $field_data['default'] ) : $image_url;
				$image_url  = '' === $image_url && ! empty( $field_data['default'] && ! empty( $campaign_data['template_id'] ) ) ? esc_url( charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/templates/' . esc_attr( $campaign_data['template_id'] ) . '/' . $field_data['default'] ) : $image_url;
				$image_url  = '' === $image_url ? apply_filters( 'charitable_campaign_builder_photo_image_placeholder_url', charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/photo-default-image.png' ) : $image_url;
				$alt_text   = ! empty( $field_data['alt_text'] ) ? trim( wp_strip_all_tags( $field_data['alt_text'] ) ): '';
				$css_class  = ! empty( $field_data['css_class'] ) ? ' class="' . esc_attr( $field_data['css_class'] ) . '" ' : '';
				$photo_attr = apply_filters( 'charitable_campaign_builder_photo_image_attributes', false );

				ob_start();

				?>

				<div class="charitable-campaign-primary-image">
					<img <?php echo esc_attr( $css_class ); ?> src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt_text ); ?>" <?php echo esc_attr( $photo_attr ); ?> />
				</div>


				<?php

				$html = ob_get_clean();

				return $html;

			}
		}

		/**
		 * Photo preview inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			$html  = $this->field_title( $this->name );
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'preview' ), $field_data );

			echo wp_kses_post( $html );
		}

		/**
		 * Photo display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type Field Type.
		 * @param array  $field_data Deprecated.
		 * @param array  $campaign_data  Form data and settings.
		 */
		public function field_display( $field_type = '', $field_data = false, $campaign_data = false ) {

			$html = $this->field_display_wrapper( $this->render( $field_data, $campaign_data ), $field_data );

			echo wp_kses_post( apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ) );
		}

		/**
		 * Photo display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_id       Photo settings.
		 * @param array $campaign_data  Form data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			// special check - if "default" is just a filename, readjust the default to a full url.
			if ( ! empty( $settings['default'] ) && ! empty( $campaign_data['template_id'] ) ) {
				$default_basename = basename( $settings['default'] );
				if ( $default_basename === $settings['default'] ) {
					$settings['default'] = charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/' . $campaign_data['template_id'] . '/' . $default_basename;
				}
			}

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<?php

			echo $charitable_builder_form_fields->generate_uploader( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['file'] ) ? $settings['file'] : false,
				esc_html__( 'Photo URL', 'charitable' ),
				array(
					'id'           => 'field_' . esc_attr( $this->type ) . '_file' . '_' . intval( $field_id ), // phpcs:ignore
					'name'         => array( '_fields', intval( $field_id ), 'file' ),
					'field_id'     => esc_attr( $field_id ),
					'button_label' => esc_html__( 'Upload', 'charitable' ),
					'placeholder'  => 'https://',
					'description'  => esc_html__( 'Upload a new image or add an existing image.', 'charitable' ),
					'tooltip'      => esc_attr( $this->tooltip ),
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['alt_text'] ) ? $settings['alt_text'] : false,
				esc_html__( 'ALT Text', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_alt_text' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'alt_text' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Provide text for the ALT tag of this image.', 'charitable' ),
				)
			);

			echo $charitable_builder_form_fields->generate_hidden_field( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['default'] ) ? $settings['default'] : false,
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_default' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'default' ),
					'field_id' => intval( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_number_slider( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['width_percentage'] ) ? $settings['width_percentage'] : 100,
				esc_html__( 'Width', 'charitable' ),
				array(
					'id'         => 'field_' . esc_attr( $this->type ) . '_width_percentage' . '_' . intval( $field_id ), // phpcs:ignore
					'name'       => array( '_fields', intval( $field_id ), 'width_percentage' ),
					'field_type' => esc_attr( $this->type ),
					'field_id'   => intval( $field_id ),
					'symbol'     => '%',
					'min'        => 0,
					'min_actual' => 40,
					'css_class'  => 'charitable-indicator-on-hover',
					'tooltip'    => esc_html__( 'Adjust the width of the field within the column.', 'charitable' ),
				)
			);

			echo $charitable_builder_form_fields->generate_align( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['align'] ) ? $settings['align'] : esc_attr( $this->align_default ),
				esc_html__( 'Align', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_align' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'align' ),
					'field_id' => intval( $field_id ),
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

			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Return HTML for display via ajax.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 */
		public function settings_display_ajax() {

			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$field_id    = isset( $_POST['field_id'] ) ? intval( $_POST['field_id'] ) : false;
			$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : false;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( false === $field_id ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings      = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

			<?php

			echo $charitable_builder_form_fields->generate_number_slider( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['width_percentage'] ) ? $settings['width_percentage'] : 100,
				esc_html__( 'Width', 'charitable' ),
				array(
					'id'         => 'field_' . esc_attr( $this->type ) . '_width_percentage' . '_' . intval( $field_id ), // phpcs:ignore
					'name'       => array( '_fields', intval( $field_id ), 'width_percentage' ),
					'field_type' => esc_attr( $this->type ),
					'field_id'   => intval( $field_id ),
					'symbol'     => '%',
					'min'        => 10,
					'tooltip'    => esc_html__( 'Adjust the width of the field within the column.', 'charitable' ),
				)
			);

			echo $charitable_builder_form_fields->generate_align( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['align'] ) ? $settings['align'] : false,
				esc_html__( 'Align', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_align' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'align' ),
					'field_id' => intval( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$settings['css_class'],
				esc_html__( 'CSS Class', 'charitable' ),
				array(
					'id'      => 'field_' . esc_attr( $this->type ) . '_css_class',
					'name'    => array( 'fields', esc_attr( $this->type ), 'css_class' ),
					'tooltip' => esc_html( $this->tooltip ),
				)
			);

			?>

			<?php

			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );

			exit;
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 *
		 * @param string $min The suffix, if any, to include for the minified version.
		 */
		public function builder_js( $min = '' ) {
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 *
		 * @param string $min The suffix, if any, to include for the minified version.
		 */
		public function frontend_js( $min = '' ) {
		}

		/**
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field          Photo ID.
		 * @param mixed $field_data     Photo value that was submitted.
		 * @param array $campaign_data  Form data and settings.
		 */
		public function format( $field, $field_data = false, $campaign_data = false ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field          Photo ID.
		 * @param mixed $field_data     Photo value that was submitted.
		 * @param array $campaign_data  Form data and settings.
		 */
		public function validate( $field, $field_data = false, $campaign_data = false ) {
		}
	}

	new Charitable_Field_Photo();

endif;