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

if ( ! class_exists( 'Charitable_Field_Campaign_Description' ) ) :

	/**
	 * Class to add campaign description field to a campaign form in the builder.
	 *
	 * @version 1.8.9.1
	 */
	class Charitable_Field_Campaign_Description extends Charitable_Builder_Field {

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
			$this->name  = esc_html__( 'Campaign Description', 'charitable' );
			$this->type  = 'campaign-description';
			$this->icon  = 'fa-paragraph';
			$this->order = 10;

			$this->align_default = 'center';

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = false;
			$this->edit_label        = esc_html__( 'Edit Campaign Description', 'charitable' );
			$this->edit_type         = 'campaign-description'; // was settings.
			$this->edit_section      = 'recommended';
			$this->max_allowed       = 1;

			// Misc.
			$this->tooltip                 = esc_html__( 'Placeholder tool tip', 'charitable' );
			$this->preview_character_limit = 99550;

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Campaign Description options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Campaign Description settings.
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

			$campaign_description = ! empty( $campaign_data['settings']['general']['description'] ) ? ( $campaign_data['settings']['general']['description'] ) : ''; // todo: santitize.
			$campaign_description = ( $campaign_description === '' && ! empty( $field_data['content'] ) ) ? ( $field_data['content'] ) : $campaign_description; // todo: santitize.
			$headline             = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : false;

			$field_preview_html = '<div class="charitable-field-title-description title-description" data-field-type="' . $this->type . '">
									<div class="placeholder charitable-placeholder"><h5 class="charitable-field-' . $mode . '-headline">' . $headline . '</h5>
										<div class="charitable-campaign-builder-placeholder-' . $mode . '-text charitable-campaign-builder-no-description-' . $mode . ' charitable-prevent-select">';

			if ( '' !== $campaign_description ) {

				// Adjust the character limit - no character limit for the campaign description on the frontend. Limit for admin preview only.
				$preview_character_limit = $mode === 'template' ? $this->preview_character_limit : strlen( $campaign_description );

				$field_preview_html .= wpautop(
					wp_kses(
						strlen( $campaign_description ) > $preview_character_limit ? substr( $campaign_description, 0, $this->preview_character_limit ) . '...' : $campaign_description,
						[
							'strong' => [],
							'em'     => [],
							'h1'     => [],
							'h2'     => [],
							'h3'     => [],
							'h4'     => [],
							'h5'     => [],
							'h6'     => [],
							'p'      => [],
							'span'   => [],
						]
					)
				) . '</div>';

			} else {

				$field_preview_html .= '
			<div>
				<p><em><strong>This is an example of a campaign description.</strong> It should summarize thoughtfully what your campaign is about and what your goal(s) are. If this is the only campaign of your site you can link to a more detailed About page that introduces into more detail. The description here might say something like this:</em></p>
				<p><br/></p>
					<p>Hi there! This campaign is to raise money for equipment, uniforms, and various other supplies for our local school sport team, The Tigers.</p>
					<p><br/></p>
					<p>...or something like this:</p>
					<p><br/></p>
					<p>The Tigers basebase team was founded in 1971 and has been providing fun after school activities for young children in the Dalls, Texas USA public schools ever since. The campaign is run by Mr. and Mrs. Smith and employs about 5 people including coaches and staff.</p>
					<p><br/></p>
					<p>Since this is a new campaign, you as a Charitable admin should update this content by clicking on this block in the preview area in the campaign builder. 😄</p>
					<p><br/></p>
			</div>
		</div>';

			}

			$field_preview_html .= '
									</div>
								</div>';

			$html = $field_preview_html;

			return $html;
		}


		/**
		 * Campaign Description preview inside the builder.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			$html  = $this->field_title( $this->name );
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'preview' ), $field_data );

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
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
		 * @version 1.8.9.1
		 *
		 * @param array $field_id Field ID.
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
				isset( $settings['headline'] ) ? $settings['headline'] : false,
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			$campaign_description = ! empty( $campaign_data['settings']['general']['description'] ) ? ( $campaign_data['settings']['general']['description'] ) : 'Add your description here.'; // todo: santitize.

			echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$campaign_description,
				esc_html__( 'Campaign Description', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_html' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'content' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html( $this->tooltip ),
					'class'    => 'campaign-builder-htmleditor',
					'rows'     => 30,
					'html'     => true,
					'code'     => false,
					'special'  => 'campaign_description',
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
					'css_class'  => 'charitable-indicator-on-hover',
					'min'        => 0,
					'min_actual' => 20,
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
					'id'       => 'field_' . esc_attr( $this->type ) . '_css_class' . '_' . intval( $field_id ), // phpcs:ignore
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
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Campaign Description ID.
		 * @param mixed $field_submit Campaign Description value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $form_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Campaign Description ID.
		 * @param mixed $field_submit Campaign Description value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $form_data ) {
		}
	}



	new Charitable_Field_Campaign_Description();



endif;
