<?php
/**
 * Class to add donation form to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Donation_Form' ) ) :

	/**
	 * Class to add campaign form field to a campaign form in the builder.
	 */
	class Charitable_Field_Donation_Form extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Donation Form', 'charitable' );
			$this->type  = 'donation-form';
			$this->icon  = 'fa-file-image-o';
			$this->order = 50;
			$this->group = 'pro';

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = false;
			$this->edit_label        = esc_html__( 'Edit Donation Form', 'charitable' );
			$this->edit_type         = 'donation-form';
			$this->edit_section      = 'pro';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
			add_action( 'charitable_builder_backend_scripts', [ $this, 'builder_js' ] ); // admin_enqueue_scripts.
		}


		/**
		 * Field options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Field settings.
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

				$html = '
				<!--<span class="charitable-placeholder"></span>-->
				<span class="charitable-form-placeholder">
					<div class="charitable-preview-notice">
						<h6><i class="fa fa-file-image-o"></i>&nbsp;Donation Form</h6>
						<p>Contents of this field are not displayed in the campaign builder preview.</p>
					</div>
				</span>
				';

			}

			return $html;
		}

		/**
		 * Donation Form preview inside the builder.
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

			echo $html; // phpcs:ignore
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field          The passed field type.
		 * @param array  $field_data     Any field data.
		 * @param array  $campaign_data  Form data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			add_filter( 'charitable_campaign_can_receive_donations', [ $this, 'enable_campaign_form' ], 10, 2 );

			$campaign_id = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : false;

			if ( 0 === intval( $campaign_id ) ) {
				return;
			}

			$html = '';

			$defaults = array(
				'campaign_id' => 0,
			);

			$atts = array(
				'campaign_id' => $campaign_id,
			);

			/* Parse incoming $atts into an array and merge it with $defaults. */
			$args = shortcode_atts( $defaults, $atts, 'charitable_donation_form' );

			$headline = ! empty( $field_data['headline'] ) ? '<h5 class="charitable-field-template-headline">' . esc_html( $field_data['headline'] ) . '</h5>' : '';

			ob_start();

			echo $headline; // phpcs:ignore

			charitable_template_donation_form( $args['campaign_id'], $args );

			$html = ob_get_clean();

			remove_filter( 'charitable_campaign_can_receive_donations', [ $this, 'enable_campaign_form' ], 10, 2 );

			$html = $this->field_display_wrapper( $html, $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Stand in function enabling the campaign form.
		 *
		 * @since 1.8.0
		 *
		 * @param boolean $is_able Is able.
		 * @param object  $object The object.
		 */
		public function enable_campaign_form( $is_able, $object = false ) { // phpcs:ignore

			return true;
		}

		/**
		 * Donation Form display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id      Number ID.
		 * @param array   $campaign_data Form data and settings.
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
		 * Display settings for AJAX requests.
		 *
		 * @since  1.8.0
		 * @version 1.8.9.1
		 *
		 * @return void
		 */
		public function settings_display_ajax() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$field_id    = isset( $_POST['field_id'] ) ? intval( wp_unslash( $_POST['field_id'] ) ) : 0;
			$campaign_id = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0; // todo: should this be added? see a few lines down.
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings      = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

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
		 * Enqueue frontend js.
		 *
		 * @since 1.8.0
		 */
		public function frontend_js() {
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

		/**
		 * Possible depreciated function.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field      Field ID.
		 * @param array  $field_atts Field value that was submitted.
		 * @param array  $campaign_data Campaign data and settings.
		 */
		public function section_top( $field, $field_atts, $campaign_data ) {
		}

		/**
		 * Possible depreciated function.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field      Field type.
		 * @param array  $field_atts Field value that was submitted.
		 * @param array  $campaign_data Campaign data and settings.
		 */
		public function section_bottom( $field, $field_atts, $campaign_data ) {
		}
	}

	new Charitable_Field_Donation_Form();

endif;