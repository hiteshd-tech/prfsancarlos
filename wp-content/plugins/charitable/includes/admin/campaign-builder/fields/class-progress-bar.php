<?php
/**
 * Class to add a campaign progress bar to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Progress_Bar' ) ) :

	/**
	 * Class to add campaign progress bar to a campaign form in the builder.
	 */
	class Charitable_Field_Progress_Bar extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define field type information.
			$this->name  = esc_html__( 'Progress Bar', 'charitable' );
			$this->type  = 'progress-bar';
			$this->icon  = 'fa-arrows-h';
			$this->order = 30;

			$this->align_default = 'center';

			// Edit/Duplication information.
			$this->edit_label        = esc_html__( 'Edit Progress Bar', 'charitable' );
			$this->edit_type         = 'progress-bar';
			$this->edit_section      = 'standard';
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_builder_frontend_js', [ $this, 'frontend_js' ] );
			add_action( 'charitable_builder_backend_scripts', [ $this, 'builder_js' ] );

			// Defaults.
			add_filter( 'charitable_field_new_default', [ $this, 'new_field_defaults' ] );
		}

		/**
		 * Set default values for the field.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Field settings.
		 */
		public function new_field_defaults( $field ) {

			if ( isset( $field['type'] ) && 'progress-bar' !== $field['type'] ) {
				return $field;
			}

			$field['show_donated'] = 'show_donated';
			$field['show_goal']    = 'show_goal';

			return $field;
		}


		/**
		 * ProgressBar options panel inside the builder.
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
		 * Render the field.
		 *
		 * @since 1.8.0
		 * @version 1.8.1.12
		 * @version 1.8.4.2 updated infinity display / goal.
		 * @version 1.8.6 added round_donation option.
		 *
		 * @param array   $field_data     Any field data.
		 * @param array   $campaign_data  Amount data and settings.
		 * @param integer $field_id       The field ID.
		 * @param string  $mode           Where the field is being displayed ("preview" or "template").
		 * @param array   $template_data  Tempalate data.
		 */
		public function render( $field_data = false, $campaign_data = false, $field_id = false, $mode = 'template', $template_data = false ) {

			$show_donated_css = false;
			$show_goal_css    = false;
			$headline         = ! empty( $field_data['headline'] ) ? '<h5 class="charitable-field-preview-headline">' . esc_html( $field_data['headline'] ) . '</h5>' : '';

			$goal = isset( $campaign_data['id'] ) ? get_post_meta( $campaign_data['id'], '_campaign_goal', true ) : false;
			if ( ! $goal || 0 === intval( $goal ) ) {
				$goal = '∞';
			} else {
				$goal = Charitable_Currency::get_instance()->get_sanitized_and_localized_amount( $goal );
				$goal = charitable_get_currency_helper()->get_monetary_amount( $goal ); // Add appropriate currency to the goal.
			}
			$goal = ! empty( $field_data['label_goal'] ) ? '<span>' . esc_html( $field_data['label_goal'] ) . '</span> ' . $goal : '<span>' . esc_html__( 'Goal: ', 'charitable' ) . '</span> ' . $goal;
			$goal = apply_filters( 'charitable_campaign_builder_progress_bar_goal', $goal, $field_data, $campaign_data );

			$campaign_id    = ! empty( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : false;
			$campaign       = 0 === intval( $campaign_id ) ? false : charitable_get_campaign( $campaign_id );
			$amount_donated = false !== $campaign ? $campaign->get_donated_amount_formatted() : charitable_format_money( '0' );
			$percent        = false !== $campaign ? $campaign->get_percent_donated() : 0;
			// Round the percent to 0 decimal places if the round_donation is checked. But if there is a percent and it's below 1, round to 1.
			if ( isset( $field_data['round_donation'] ) && $field_data['round_donation'] ) {
				// remove the % sign from the percent.
				$percent = str_replace( '%', '', $percent );
				if ( $percent < 1 ) {
					$percent = 1;
				} else {
					$percent = round( $percent, 0 );
				}
				$percent = $percent . '%';
			}

			$label_donated     = ! empty( $field_data['label_donate'] ) ? '<span>' . esc_html( $field_data['label_donate'] ) . '</span> ' : '<span>' . esc_html__( 'Donated: ', 'charitable' ) . '</span> ';
			$show_donated      = $label_donated . $amount_donated;
			$show_progress_bar = false;

			if ( false !== $campaign ) {
				if ( ! $campaign->has_goal() ) {
					$show_progress_bar = false;
				} else {
					$show_donated      = $label_donated . $percent;
					$show_progress_bar = true;
				}
			}

			// if the "show_goal" is set to "show_goal" then show the goal, otherise do not but hidding it by setting 'charitable-hidden' class.
			$show_donated_css = ! empty( $field_data ) &&
								(
									( is_array( $field_data['show_hide'] ) && ! empty( $field_data['show_hide'] ) && in_array( 'show_donated', $field_data['show_hide'] ) ) || // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
									( is_string( $field_data['show_donated'] ) && 'show_donated' === $field_data['show_donated'] )
								)
								? '' : 'charitable-hidden';
			$show_goal_css    = ! empty( $field_data ) &&

								(
									( is_array( $field_data['show_hide'] ) && ! empty( $field_data['show_hide'] ) && in_array( 'show_goal', $field_data['show_hide'] ) ) || // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
									( is_string( $field_data['show_goal'] ) && 'show_goal' === $field_data['show_goal'] )
								)
								? '' : 'charitable-hidden';

			$progress_bar_css = $show_progress_bar ? '' : ' charitable-campaign-preview-not-available charitable-hidden';

			$width = ( 'preview' === $mode ) ? $this->keep_in_range( absint( $percent ), 5, 100 ) : $this->keep_in_range( absint( $percent ), 0, 100 );

			$preview_text = '';

			if ( isset( $field_data['meta_position'] ) && 'top' === $field_data['meta_position'] ) {

				$preview_text .= '<div class="placeholder charitable-placeholder meta-top">' . $headline;
				$preview_text .= '<div class="progress-bar-info-row">';
				$preview_text .= '<div class="campaign-percent-raised ' . $show_donated_css . '">' . $show_donated . '</div><div class="campaign-goal ' . $show_goal_css . '"> ' . $goal . '</div></div>';
				$preview_text .= '<div class="progress' . $progress_bar_css . '"><div class="progress-bar" style="width: ' . $width . '%"><span></span></div></div>';
				$preview_text .= '</div>';

			} else {

				$preview_text .= '<div class="placeholder charitable-placeholder meta-bottom">' . $headline;
				$preview_text .= '<div class="progress' . $progress_bar_css . '"><div class="progress-bar" style="width: ' . $width . '%"><span></span></div></div>';
				$preview_text .= '<div class="progress-bar-info-row">';
				$preview_text .= '<div class="campaign-percent-raised ' . $show_donated_css . '">' . $show_donated . '</div><div class="campaign-goal ' . $show_goal_css . '"> ' . $goal . '</div></div>';
				$preview_text .= '</div>';

			}

			return $preview_text;
		}

		/**
		 * ProgressBar preview inside the builder.
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

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * ProgressBar display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type Field Type.
		 * @param array  $field_data Deprecated.
		 * @param array  $campaign_data  Form data and settings.
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
		 * @param array $field_id       Social Links settings.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<?php

			echo $charitable_builder_form_fields->generate_headline( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html__( 'Note: Progress bars will be visible only when there is a goal set for the campaign.', 'charitable' ),
				array(
					'field_id' => esc_attr( $field_id ),
					'class'    => 'charitable-campaign-builder-headline-light',
				)
			);

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

			echo $charitable_builder_form_fields->generate_toggles( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['show_hide'] ) ? $settings['show_hide'] : false,
				esc_html__( 'Campaign Information', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_show_hide' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'show_hide' ),
					'field_id'        => esc_attr( $field_id ),
					'tooltip'         => esc_html( $this->tooltip ),
					'container_class' => 'charitable-campaign-builder-progress-bar',
					'options'         => array(
						'Show Donated' => 'show_donated',
						'Show Goal'    => 'show_goal',
					),
					'default'         => array(
						'show_donated',
						'show_goal',
					),
					'use_defaults'    => ( false === $settings ) ? true : false,
				)
			);

			/* 1.8.6.1 */
			echo $charitable_builder_form_fields->generate_checkbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['round_donation'] ) ? $settings['round_donation'] : false,
				esc_html__( 'Round Amounts', 'charitable' ),
				array(
					'id'            => 'field_' . esc_attr( $this->type ) . '_round_donation' . '_' . intval( $field_id ), // phpcs:ignore
					'name'          => array( '_fields', intval( $field_id ), 'round_donation' ),
					'checked_value' => 'show',
					'field_id'      => intval( $field_id ),
					'value'         => '1',
				)
			);

			echo $charitable_builder_form_fields->generate_hidden_field( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['meta_position'] ) ? $settings['meta_position'] : false,
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_meta_position' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'meta_position' ),
					'field_id' => intval( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['label_donate'] ) ? $settings['label_donate'] : esc_html__( 'Donated:', 'charitable' ),
				esc_html__( 'Donate Label:', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_label_donate' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'label_donate' ),
					'field_id' => intval( $field_id ),
					'class'    => 'donate_label',
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['label_goal'] ) ? $settings['label_goal'] : esc_html__( 'Goal:', 'charitable' ),
				esc_html__( 'Goal Label:', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_label_goal' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'label_goal' ),
					'field_id' => intval( $field_id ),
					'class'    => 'donate_goal',
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
					'css_class'  => 'charitable-indicator-on-hover',
					'symbol'     => '%',
					'min'        => 0,
					'min_actual' => 30,
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
		 * Return HTML for display via ajax.
		 *
		 * @since 1.8.0
		 */
		public function settings_display_ajax() {

			$field_id    = isset( $_POST['field_id'] ) ? intval( $_POST['field_id'] ) : false; // phpcs:ignore
			$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : false; // phpcs:ignore

			if ( false === $field_id ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings      = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

			<?php

			echo $charitable_builder_form_fields->generate_text( //	phpcs:ignore
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

			wp_send_json_success( [ 'html' => $html ] );

			exit;
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 *
		 * @param array $min Min string.
		 */
		public function builder_js( $min ) {
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 */
		public function frontend_js() {
		}

		/**
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id      Field type.
		 * @param mixed $field_submit  Field value that was submitted.
		 * @param array $campaign_data Campaign data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Field ID.
		 * @param mixed $field_submit Field value that was submitted.
		 * @param array $campaign_data Campaign data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}
	}

	if ( is_admin() ) {
		new Charitable_Field_Progress_Bar();
	}

endif;
