<?php
/**
 * Class to add campaign overview field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Campaign_Overview' ) ) :

	/**
	 * Class to add campaign overview field to a campaign form in the builder.
	 */
	class Charitable_Field_Campaign_Overview extends Charitable_Builder_Field {

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
			$this->name  = esc_html__( 'Campaign Overview', 'charitable' );
			$this->type  = 'campaign-overview';
			$this->icon  = 'fa-paragraph';
			$this->order = 10;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Campaign Overview', 'charitable' );
			$this->edit_type         = 'campaign-overview'; // was settings.
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip                 = esc_html__( 'Placeholder tool tip', 'charitable' );
			$this->preview_character_limit = 550;

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Campaign Overview options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Campaign Overview settings.
		 */
		public function field_options( $field ) {
			/*
			 * Basic field options.
			 */

			// Options open markup.
		}

		/**
		 * Campaign Overview preview inside the builder.
		 *
		 * @since 1.8.0
		 * @since 1.8.1.9 Added santization to the overview.
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			// santitize the overview, which is user supplied text... remove any JS or other bad stuff.
			$campaign_overview = ! empty( $campaign_data['settings']['general']['overview'] ) ? wp_kses_post( $campaign_data['settings']['general']['overview'] ) : '';

			$field_preview_html = '<div class="charitable-field-title-overview title-overview" data-field-type="' . $this->type . '">
									<div class="placeholder">
										<div class="charitable-campaign-builder-placeholder-preview-text charitable-campaign-builder-no-overview-preview charitable-prevent-select" style="overflow: scroll;">';

			if ( $campaign_overview ) {

				$field_preview_html .= wpautop(
					wp_kses(
						strlen( $campaign_overview ) > $this->preview_character_limit ? substr( $campaign_overview, 0, $this->preview_character_limit ) . '...' : $campaign_overview,
						[
							'strong' => [],
							'em'     => [],
						]
					)
				);

			} else {

				$field_preview_html .= '
			<div>
				<p><em><strong>This is an example of a campaign overal.</strong> It can include more text that goes into some details about your campaign - such as the history of the organization, testimonials, and more. An overview can work great as a longer overview under the campaign or inside of the visible tabs on the campaign page. The overview here might say something like this:</em></p>
				    <p><br/></p>
                    <p><img src="' . charitable()->get_path( 'assets', false ) . '/images/campaign-builder/photo-default-image.png" alt="" title="" /></p>
					<p>Hi there! This campaign is to raise money for equipment, uniforms, and various other supplies for our local school sport team, The Tigers. Hi there! This campaign is to raise money for equipment, uniforms, and various other supplies for our local school sport team, The Tigers. Hi there! This campaign is to raise money for equipment, uniforms, and various other supplies for our local school sport team, The Tigers. Hi there! This campaign is to raise money for equipment, uniforms, and various other supplies for our local school sport team, The Tigers.</p>
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

			$html  = $this->field_title( $this->name );
			$html .= $this->field_wrapper( $field_preview_html, $field_data );

			echo $html; // phpcs:ignore
		}

		/**
		 * Campaign Overview display on the form front-end.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 *
		 * @param array $field      Campaign Overview settings.
		 * @param array $field_data Field Data.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			$campaign_overview_raw = ! empty( $campaign_data['settings']['general']['overview'] ) ? ( $campaign_data['settings']['general']['overview'] ) : '';
			$campaign_overview     = wp_kses_post( $campaign_overview_raw );
			$headline              = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : false;
			$css_class             = ! empty( $field_data['css_class'] ) ? ' class="' . esc_html( $field_data['css_class'] ) . '" ' : '';

			ob_start();

			?>

		<div class="charitable-campaign-field charitable-campaign-field_<?php echo esc_attr( $this->type ); ?> <?php echo esc_attr( $css_class ); ?>">

			<?php if ( $headline ) : ?>
				<h4><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h4>
			<?php endif; ?>

			<?php echo $campaign_overview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

			<?php

			$html = ob_get_clean();

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_id       Field id..
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			// $panel_settings = new Charitable_Builder_Panel_Settings();

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

			echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['content'] ) ? $settings['content'] : false,
				esc_html__( 'Campaign Overview', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_html' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'content' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html( $this->tooltip ),
					'class'    => 'campaign-builder-htmleditor',
					'rows'     => 30,
					'html'     => true,
					'code'     => false,
					'special'  => 'campaign_overview',
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => $field_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
		 * @param int   $field_id     Campaign Overview ID.
		 * @param mixed $field_submit Campaign Overview value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $form_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Campaign Overview ID.
		 * @param mixed $field_submit Campaign Overview value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $form_data ) {
		}
	}


	new Charitable_Field_Campaign_Overview();

endif;