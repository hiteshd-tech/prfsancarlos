<?php
/**
 * Class to add info bar field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Info_Bar' ) ) :

	/**
	 * Class to add campaign info bar field to a campaign form in the builder.
	 */
	class Charitable_Field_Info_Bar extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Info Bar', 'charitable' );
			$this->type  = 'info-bar';
			$this->icon  = 'fa-list-ul';
			$this->order = 150;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = 'Edit Info Bar';
			$this->edit_type         = 'info-bar';
			$this->edit_section      = 'standard';

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
		 * @param array $field ProgressBar settings.
		 */
		public function field_options( $field ) {
			/*
			 * Basic field options.
			 */

			// Options open markup.
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

			echo '<h4>' . esc_html( $this->name ) . '</h4>';

			echo '<div class="charitable-field-preview-info-bar"><div class="row">
            <div class="column">
                <span class="placeholder"></span>
            </div>
            <div class="column">
                <span class="placeholder"></span>
            </div>
        </div></div>';
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field      Campaign Title settings.
		 * @param array $field_data Field Data.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			$campaign = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? charitable_get_campaign( $campaign_data['id'] ) : false;

			if ( ! $campaign ) {
				return;
			}

			$show_donors         = ! empty( $field_data['show_donors'] ) && 'show' === ( $field_data['show_donors'] ) ? true : false;
			$show_donation_total = ! empty( $field_data['show_donation_total'] ) && 'show' === ( $field_data['show_donation_total'] ) ? true : false;
			$css_class           = ! empty( $field_data['css_class'] ) ? ' class="' . esc_html( $field_data['css_class'] ) . '" ' : '';

			ob_start();

			?>

		<div class="charitable-campaign-info-bar">
				<div <?php echo esc_attr( $css_class ); ?>>
				<div class="charitable-campaign-info-bar-row">

					<?php if ( $show_donation_total ) : ?>
					<div class="charitable-campaign-info-column">
						<?php echo wp_kses_post( $campaign->get_donation_summary() ); ?>
					</div>
					<?php endif; ?>

					<?php if ( $show_donors ) : ?>
					<div class="charitable-campaign-info-column">
						<?php
						printf(
							/* translators: %s: number of donors */
							esc_html_x( '%s Donors', 'number of donors', 'charitable' ),
							'<span class="donors-count">' . esc_html( $campaign->get_donor_count() ) . '</span>'
						);
						?>
					</div>
					<?php endif; ?>

				</div>
			</div>
		</div>

			<?php

			$html = ob_get_clean();

			echo wp_kses_post( apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ) );
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

			if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<div class="charitable-panel-field charitable-panel-field-section" data-field-id="<?php echo intval( $field_id ); ?>">

				<?php do_action( 'charitable_builder_' . $this->type . '_settings_display_start', $field_id, $campaign_data ); ?>

			</div>

			<?php

			echo $charitable_builder_form_fields->generate_checkbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['show_donors'] ) ? $settings['show_donors'] : false,
				esc_html__( 'Show Donors', 'charitable' ),
				array(
					'id'            => 'field_' . esc_attr( $this->type ) . '_show_donors' . '_' . intval( $field_id ), // phpcs:ignore
					'name'          => array( '_fields', intval( $field_id ), 'show_donors' ),
					'checked_value' => 'show',
					'field_id'      => $field_id, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'tooltip'       => $this->tooltip, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				)
			);

			echo $charitable_builder_form_fields->generate_checkbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['show_donation_total'] ) ? $settings['show_donation_total'] : false,
				esc_html__( 'Show Donation Total', 'charitable' ),
				array(
					'id'            => 'field_' . esc_attr( $this->type ) . '_show_donation_total' . '_' . intval( $field_id ), // phpcs:ignore
					'name'          => array( '_fields', intval( $field_id ), 'show_donation_total' ),
					'checked_value' => 'show',
					'field_id'      => $field_id, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'value'         => 'show',
					'tooltip'       => $this->tooltip, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		 * @param int   $field_id     ProgressBar ID.
		 * @param mixed $field_submit ProgressBar value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     ProgressBar ID.
		 * @param mixed $field_submit ProgressBar value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}
	}

	new Charitable_Field_Info_Bar();

endif;