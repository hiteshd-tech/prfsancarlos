<?php
/**
 * Class to add donation button field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Donate_Button' ) ) :

	/**
	 * Class to add campaign donate button field to a campaign form in the builder.
	 *
	 * @version 1.8.9.1
	 */
	class Charitable_Field_Donate_Button extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Donate Button', 'charitable' );
			$this->type  = 'donate-button';
			$this->icon  = 'fa-link';
			$this->order = 60;

			$this->align_default = 'center';

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Donate Button', 'charitable' );
			$this->edit_type         = 'donate-button';
			$this->edit_section      = 'recommended';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Social Sharing options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Donate Button settings.
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

			$button_label = ! empty( $field_data['button_label'] ) ? esc_html( $field_data['button_label'] ) : esc_html__( 'Donate', 'charitable' );

			$html = '<div class="charitable-field-preview-donate-button"><div class="charitable-field-donate-button-row">
			<div class="charitable-field-donate-button-column">
				<span class="charitable-placeholder placeholder button charitable-prevent-select">' . $button_label . '</span>
			</div>
		</div></div>';

			return $html;
		}

		/**
		 * Social Sharing preview inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			if ( empty( $field_data['align'] ) ) {
				$field_data['align'] = $this->align_default;
			}

			$html  = $this->field_title( $this->name );
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id ), $field_data );

			echo wp_kses_post( $html );
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param array   $field          Donate Button settings.
		 * @param array   $field_data     Any field data.
		 * @param array   $campaign_data  Form data and settings.
		 * @param boolean $is_preview_page Is this being generated on a preview page.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false, $is_preview_page = false ) {

			$campaign = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? charitable_get_campaign( $campaign_data['id'] ) : false;

			if ( ! $campaign ) :
				return;
			endif;

			$button_label          = ! empty( $field_data['button_label'] ) ? esc_html( $field_data['button_label'] ) : 'Donate';
			$css_class             = ! empty( $field_data['css_class'] ) ? ' class="' . esc_html( $field_data['css_class'] ) . '" ' : '';
			$donation_form_display = charitable_get_option( 'donation_form_display', 'separate_page' ); // could also be same_page or modal.

			// We want the button to contain a donation amount, but we go with what is in the SESSION first, then the DEFAULT amount.
			$donation_amount = $campaign->get_donation_amount_in_session();

			ob_start();

			?>

			<div <?php echo esc_attr( $css_class ); ?>>

			<?php

			if ( ! $campaign->can_receive_donations() && ! $is_preview_page ) {

				$charitable_campaign_button_closed_message = apply_filters( 'charitable_campaign_button_closed_message', esc_html__( 'This campaign is closed or goal has been reached.', 'charitable' ), $campaign );

				?>

			<button type="submit" name="charitable_submit" disabled class="<?php echo esc_attr( charitable_get_button_class( 'donate', true ) ); ?>">
				<?php echo esc_html( $charitable_campaign_button_closed_message ); ?>
			</button>

			<?php } elseif ( 'modal' === $donation_form_display ) { ?>

				<div class="campaign-donation">
					<a data-trigger-modal="charitable-donation-form-modal-<?php echo intval( $campaign->ID ); ?>"
						data-campaign-id="<?php echo intval( $campaign->ID ); ?>"
						class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
						href="<?php echo esc_url( charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign->ID ) ) ); ?>"
					<?php // translators: %s is the campaign title. ?>
					aria-label="<?php echo esc_attr( sprintf( _x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ), get_the_title( $campaign->ID ) ) ); ?>">
					<?php echo esc_html( wp_strip_all_tags( $button_label ) ); ?>
				</a>
				</div>


				<?php
			} elseif ( 'same_page' === $donation_form_display ) {

				?>

				<a class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"
					href="#charitable-donation-form"
					<?php // translators: %s is the campaign title. ?>
					aria-label="<?php echo esc_attr( sprintf( _x( 'Make a donation to %s', 'make a donation to campaign', 'charitable' ), get_the_title( $campaign->ID ) ) ); ?>"
				>
					<?php echo esc_html( wp_strip_all_tags( $button_label ) ); ?>
				</a>


			<?php } else { ?>

				<form class="campaign-donation" method="post">
					<?php wp_nonce_field( 'charitable-donate', 'charitable-donate-now' ); ?>
					<input type="hidden" name="charitable_action" value="start_donation" />
					<input type="hidden" name="campaign_id" value="<?php echo esc_html( $campaign->ID ); ?>" />
					<input type="hidden" name="charitable_donation_amount" value="<?php $donation_amount; ?>" />
					<input type="hidden" name="charitable_builder" value="true" />
					<button type="submit" name="charitable_submit" class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"><?php echo esc_html( wp_strip_all_tags( $button_label ) ); ?></button>
				</form>

			<?php } ?>

			</div>



			<?php

			$html = ob_get_clean();

			$html = $this->field_display_wrapper( $html, $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_id       Field ID.
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
				isset( $settings['button_label'] ) ? $settings['button_label'] : esc_html__( 'Donate', 'charitable' ),
				esc_html__( 'Button Label', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_button_label' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'button_label' ),
					'class'    => 'charitable-campaign-builder-donate-button-button-label',
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
					'css_class'  => 'charitable-indicator-on-hover',
					'field_id'   => intval( $field_id ),
					'symbol'     => '%',
					'min'        => 0,
					'min_actual' => 20,
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
		 * @param int   $field_id     Donate Button ID.
		 * @param mixed $field_submit Donate Button value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Donate Button ID.
		 * @param mixed $field_submit Donate Button value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}
	}

	new Charitable_Field_Donate_Button();

endif;