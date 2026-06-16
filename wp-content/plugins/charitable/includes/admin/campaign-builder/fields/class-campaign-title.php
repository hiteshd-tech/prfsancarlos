<?php
/**
 * Class to add campaign title field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Campaign_Title' ) ) :

	/**
	 * Class to add campaign title field to a campaign form in the builder.
	 *
	 * @version 1.8.9.1
	 */
	class Charitable_Field_Campaign_Title extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Campaign Title', 'charitable' );
			$this->type  = 'campaign-title';
			$this->icon  = 'fa-text-width';
			$this->order = 10;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Campaign Title', 'charitable' );
			$this->edit_type         = 'campaign-title';
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip                 = esc_html__( 'Placeholder tool tip', 'charitable' );
			$this->preview_character_limit = 150;

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Campaign Title options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Campaign Title settings.
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

			$campaign_title = ! empty( $campaign_data['title'] ) ? esc_html( stripslashes( $campaign_data['title'] ) ) : esc_html__( 'Campaign', 'charitable' );

			$html = '<div class="charitable-field-campaign-title title-description" data-field-type="' . $this->type . '">
										<div class="charitable-campaign-builder-placeholder-preview-text charitable-prevent-select">';

			if ( $campaign_title ) {

				$html .= wpautop(
					'<h1 class="charitable-campaign-title">' . wp_kses(
						strlen( $campaign_title ) > $this->preview_character_limit ? substr( $campaign_title, 0, $this->preview_character_limit ) . '...' : $campaign_title,
						[
							'strong' => [],
							'em'     => [],
						]
					) . '</h1>'
				);

			} else {

				$html .= '<div class="row">
										<div class="column">
											<span class="placeholder"></span>
										</div>
									</div>';

			}

			$html .= '		</div>
								</div>';

			return $html;
		}

		/**
		 * Campaign Title preview inside the builder.
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
		 * @param array $field          Campaign Title settings.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			add_action( 'charitable_builder_' . $this->type . '_settings_display_start', [ $this, 'settings_section_top' ], 10, 2 );
			add_action( 'charitable_builder_' . $this->type . '_settings_display_end', [ $this, 'settings_section_bottom' ], 10, 2 );

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<div class="charitable-panel-field charitable-panel-field-section" data-field-id="<?php echo intval( $field_id ); ?>">

				<?php echo do_action( 'charitable_builder_' . $this->type . '_settings_display_start', $field_id, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			</div>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['title'] ) ? $settings['title'] : false,
				esc_html__( 'Campaign Title', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_title' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'title' ),
					'field_id' => intval( $field_id ),
					'default'  => ! empty( $campaign_data['title'] ) ? esc_html( $campaign_data['title'] ) : false,
					'class'    => 'charitable-campaign-builder-title',
					'special'  => 'campaign_title',
				)
			);

			echo $charitable_builder_form_fields->generate_align( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['align'] ) ? $settings['align'] : esc_attr( $this->align_default ),
				esc_html__( 'Align', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_align' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'align' ),
					'field_id' => intval( $field_id ),
					'default'  => 'left',
				)
			);

			/* CSS CLASS */

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

			<div class="charitable-panel-field charitable-panel-field-section" data-field-id="<?php echo intval( $field_id ); ?>">

				<?php do_action( 'charitable_builder_' . $this->type . '_settings_display_end', $field_id, $campaign_data ); ?>

			</div>

			<?php

			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Display content above the content settings in the panel in the admin via hook.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id Field ID.
		 * @param array   $campaign_data Data on campaign.
		 */
		public function settings_section_top( $field_id = false, $campaign_data = false ) {
		}

		/**
		 * Display content above the content settings in the panel in the admin via hook.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id Field ID.
		 * @param array   $campaign_data Data on campaign.
		 */
		public function settings_section_bottom( $field_id = false, $campaign_data = false ) {
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
		 * @param int   $field_id     Campaign Title ID.
		 * @param mixed $field_submit Campaign Title value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Campaign Title ID.
		 * @param mixed $field_submit Campaign Title value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}
	}


	new Charitable_Field_Campaign_Title();


endif;