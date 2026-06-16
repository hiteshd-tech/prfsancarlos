<?php
/**
 * Class to add html field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_HTML' ) ) :

	/**
	 * Class to add campaign html field to a campaign form in the builder.
	 *
	 * @version 1.8.9.1
	 */
	class Charitable_Field_HTML extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'HTML', 'charitable' );
			$this->type  = 'html';
			$this->icon  = 'fa-code';
			$this->order = 30;

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit HTML', 'charitable' );
			$this->edit_type         = 'html';
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
			add_action( 'charitable_builder_backend_scripts', [ $this, 'builder_js' ], 999 ); // admin_enqueue_scripts.
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

			// Define data.
			$width_percentage = isset( $field['width_percentage'] ) ? absint( $field['width_percentage'] ) : 95;
			if ( $width_percentage > 95 ) {
				$width_percentage = 95;
			}
			if ( $width_percentage < 10 ) {
				$width_percentage = 10;
			}

			$content = ! empty( $field_data['content'] ) ? $field_data['content'] : false;

			$html = '<div class="charitable-field-preview-html" data-field-type="' . $this->type . '">
                	<span class="placeholder">
						<div class="charitable-preview-notice">
							<h6><i class="fa fa-code"></i>&nbsp;HTML / Code Block</h6>
							<p>Contents of this field are not displayed in the campaign builder preview.</p>
						</div>
					</span>
              	</div>';

			return $html;
		}

		/**
		 * HTML preview inside the builder.
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
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id ), $field_data );

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 *
		 * @param string $field          The passed field type.
		 * @param array  $field_data     Any field data.
		 * @param array  $campaign_data  Form data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			$campaign = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? charitable_get_campaign( $campaign_data['id'] ) : false;

			if ( ! $campaign ) {
				return;
			}

			$html_content = ! empty( $field_data['content'] ) ? $this->format( $field, $field_data['content'], $campaign_data ) : false;
			$css_class    = ! empty( $field_data['css_class'] ) ? ' class="' . esc_html( $field_data['css_class'] ) . '" ' : '';

			ob_start();

			?>

		<div class="charitable-campaign-field charitable-campaign-field_<?php echo esc_attr( $this->type ); ?>">
			<div <?php echo esc_attr( $css_class ); ?>>
				<?php echo $html_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

			<?php

			$html = ob_get_clean();

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * HTML display on the form front-end.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 *
		 * @param integer $field_id      Number ID.
		 * @param array   $campaign_data Form data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<?php

			echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['content'] ) ? $settings['content'] : false,
				esc_html__( 'HTML', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_html' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'content' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html( $this->tooltip ),
					'class'    => 'campaign-builder-codeeditor',
					'rows'     => 30,
					'html'     => false,
					'code'     => true,
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
		 * @param boolean $min Do we minify the file? Default is false.
		 */
		public function builder_js( $min = false ) {

			/* CodeMirror editor */
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
			if ( $settings ) {
				wp_localize_script( 'jquery', 'charitable_campaign_builder_code_editor_settings', $settings );
			}

			/* Load Custom JS */

			wp_enqueue_script(
				'charitable-field-' . $this->type,
				charitable()->get_path( 'directory', false ) . 'assets/js/campaign-builder/fields/' . $this->type . "{$min}.js",
				[
					'charitable-builder',
				],
				charitable()->get_version()
			);

			/* Load Custom CSS */

			wp_enqueue_style(
				'charitable_campaign_builder_field_' . $this->type,
				charitable()->get_path( 'directory', false ) . 'assets/css/campaign-builder/fields/' . $this->type . "{$min}.css",
				null,
				charitable()->get_version()
			);

			wp_enqueue_style(
				'wp-codemirror',
				'/wp-includes/js/codemirror/codemirror.min.css',
				null,
				charitable()->get_version()
			);
		}

		/**
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 * @version 1.8.3 added img, svg, ul, ol, li.
		 *
		 * @param int   $field_id      Field type.
		 * @param mixed $field_submit  Field value that was submitted.
		 * @param array $campaign_data Campaign data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {

			$allowed_html_tags = apply_filters(
				'charitable_campaign_builder_html_allowed_tags',
				array(
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
					'img'    => [
						'src'     => [],
						'target'  => [],
						'class'   => [],
						'alt'     => [],
						'width'   => [],
						'height'  => [],
						'style'   => [],
						'loading' => [],
					],
					'svg'    => [
						'class'   => [],
						'width'   => [],
						'height'  => [],
						'viewBox' => [],
						'fill'    => [],
						'xmlns'   => [],
					],
					'ul'     => [
						'class' => [],
						'style' => [],
					],
					'ol'     => [
						'class' => [],
						'style' => [],
					],
					'li'     => [
						'class' => [],
						'style' => [],
					],
				),
				$campaign_data
			);

			return wp_kses( $field_submit, $allowed_html_tags );
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

	new Charitable_Field_HTML();

endif;
