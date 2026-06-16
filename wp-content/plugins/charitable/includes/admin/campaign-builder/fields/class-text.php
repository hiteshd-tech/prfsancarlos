<?php
/**
 * Class to add campaign description field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Text' ) ) :

	/**
	 * Class to add campaign organizer text field to a campaign form in the builder.
	 *
	 * @version 1.8.9.1
	 */
	class Charitable_Field_Text extends Charitable_Builder_Field {

		/**
		 * Character Limit to show in preview.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $preview_character_limit = 500;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define field type information.
			$this->name  = esc_html__( 'Text', 'charitable' );
			$this->type  = 'text';
			$this->icon  = 'fa-list-ul';
			$this->order = 100;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Text', 'charitable' );
			$this->edit_type         = 'text'; // was settings.
			$this->edit_section      = 'standard';
			$this->html_field        = 'content';

			// Misc.
			$this->tooltip                 = esc_html__( 'Placeholder tool tip', 'charitable' );
			$this->preview_character_limit = 2500;

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Text options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Text settings.
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

			$content  = ! empty( $field_data['content'] ) ? ( $field_data['content'] ) : ''; // todo: santitize
			$headline = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : esc_html__( 'Headline', 'charitable' );

			$field_preview_html = '<div class="charitable-field-text" data-field-type="' . $this->type . '">
									<div class="placeholder"><h5 class="charitable-field-' . $mode . '-headline">' . $headline . '</h5>
										<div class="charitable-campaign-builder-placeholder-' . $mode . '-text charitable-prevent-select">';

			if ( '' !== trim( $content ) ) {

				$field_preview_html .= wpautop(
					wp_kses(
						strlen( $content ) > $this->preview_character_limit ? substr( $content, 0, $this->preview_character_limit ) . '...' : $content,
						[
							'a'      => [
								'href'   => [],
								'class'  => [],
								'target' => [],
							],
							'p'      => [
								'class' => [],
							],
							'span'   => [
								'class' => [],
							],
							'div'    => [
								'class' => [],
							],
							'strong' => [
								'class' => [],
							],
							'em'     => [
								'class' => [],
							],
							'b'      => [
								'class' => [],
							],
							'i'      => [
								'class' => [],
							],
							'h1'     => [
								'class' => [],
							],
							'h2'     => [
								'class' => [],
							],
							'h3'     => [
								'class' => [],
							],
							'h4'     => [
								'class' => [],
							],
							'h5'     => [
								'class' => [],
							],
							'h6'     => [
								'class' => [],
							],
						]
					)
				) . '</div>';

			} else {

				$field_preview_html .= '
			<div><p>Add your text here.</p></div>
		</div>';

			}

			$field_preview_html .= '
									</div>
								</div>';

			$html = $field_preview_html;

			return $html;
		}


		/**
		 * Text preview inside the builder.
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
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type     The passed field type.
		 * @param array  $field_data     Any field data.
		 * @param array  $campaign_data  Amount data and settings.
		 */
		public function field_display( $field_type = '', $field_data = false, $campaign_data = false ) {

			$html = $this->field_display_wrapper( $this->render( $field_data, $campaign_data ), $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_id       Field id.
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
				isset( $settings['headline'] ) ? $settings['headline'] : esc_html__( 'Headline', 'charitable' ),
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['content'] ) ? $settings['content'] : esc_html__( 'Add your text here', 'charitable' ),
				esc_html__( 'Text', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_html' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'content' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html( $this->tooltip ),
					'class'    => 'campaign-builder-htmleditor',
					'rows'     => 30,
					'html'     => true,
					'code'     => false,
					'special'  => 'text',
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => intval( $field_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
					'min_actual' => 10,
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
		 * @param int   $field_id     Text ID.
		 * @param mixed $field_submit Text value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $form_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Text ID.
		 * @param mixed $field_submit Text value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $form_data ) {
		}
	}

	new Charitable_Field_Text();

endif;