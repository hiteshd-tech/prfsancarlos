<?php
/**
 * Class to add organizer field to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Organizer' ) ) :

	/**
	 * Class to add campaign organizer field to a campaign form in the builder.
	 */
	class Charitable_Field_Organizer extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define field type information.
			$this->name  = esc_html__( 'Organizer', 'charitable' );
			$this->type  = 'organizer';
			$this->icon  = 'fa-user';
			$this->order = 30;

			$this->align_default = 'left';

			// Edit/Duplication information.
			$this->edit_label        = esc_html__( 'Edit Organizer', 'charitable' );
			$this->edit_type         = 'organizer';
			$this->edit_section      = 'standard';
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_builder_frontend_js', [ $this, 'frontend_js' ] );
			add_action( 'charitable_builder_backend_scripts', [ $this, 'builder_js' ] );
		}

		/**
		 * Organizer options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Organizer settings.
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
		 * @version 1.1.8.16 added 'charitable_campaign_builder_organizer_image' filter.
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
				$campaign_creator_id = ! empty( $campaign_data['settings']['general']['campaign_creator_id'] ) ? intval( $campaign_data['settings']['general']['campaign_creator_id'] ) : false;
				if ( false === $campaign_creator_id && ! empty( $campaign_data['id'] ) ) {
					$campaign_post       = get_post( intval( $campaign_data['id'] ) );
					$campaign_creator_id = ! empty( $campaign_post->post_author ) ? $campaign_post->post_author : false;
				}
				$campaign_creator_id = ( false === $campaign_creator_id ) ? get_current_user_id() : $campaign_creator_id;

				$image_url         = apply_filters( 'charitable_campaign_builder_organizer_image', esc_url( get_avatar_url( $campaign_creator_id ) ), $campaign_creator_id, $campaign_data );
				$creator_name      = $this->get_creator_data( $campaign_creator_id );
				$role_or_title     = ! empty( $field_data['role_or_title'] ) ? esc_html( $field_data['role_or_title'] ) : 'Organizer';
				$organizer_content = ! empty( $field_data['content'] ) ? $this->format( $field_id, $field_data['content'], $campaign_data ) : '';

				$image_bg_css = ( $image_url ) ? ' style="background-image: url(' . esc_url( $image_url ) . ');"' : false;

				return '<div class="charitable-organizer-container">
                        <div class="charitable-organizer-image-column">
                            <div class="charitable-organizer-image" ' . $image_bg_css . '></div>
                        </div>
                        <div class="charitable-organizer-info">
                            <div class="charitable-organizer-name">' . esc_html( $creator_name ) . '</div>
                            <div class="charitable-organizer-title charitable-main-placeholder"><h5 class="charitable-field-preview-headline">' . $role_or_title . '</h5></div>
                            <div class="charitable-organizer-description">' . ( $organizer_content ) . '</div>
                        </div>
                    </div>';

			} else {

				// Define data.
				$campaign_creator_id = ! empty( $campaign_data['settings']['general']['campaign_creator_id'] ) ? intval( $campaign_data['settings']['general']['campaign_creator_id'] ) : false;
				if ( false === $campaign_creator_id && ! empty( $campaign_data['id'] ) ) {
					$campaign_post       = get_post( intval( $campaign_data['id'] ) );
					$campaign_creator_id = ! empty( $campaign_post->post_author ) ? $campaign_post->post_author : false;
				}
				$campaign_creator_id = ( false === $campaign_creator_id ) ? get_current_user_id() : $campaign_creator_id;

				$image_url         = apply_filters( 'charitable_campaign_builder_organizer_image', esc_url( get_avatar_url( $campaign_creator_id ) ), $campaign_creator_id, $campaign_data );
				$creator_name      = $this->get_creator_data( $campaign_creator_id );
				$role_or_title     = ! empty( $field_data['role_or_title'] ) ? esc_html( $field_data['role_or_title'] ) : 'Organizer';
				$organizer_content = ! empty( $field_data['content'] ) ? trim( $field_data['content'] ) : '';

				$image_bg_css = ( $image_url ) ? ' style="background-image: url(' . esc_url( $image_url ) . ');"' : false;

				return '<div class="charitable-organizer-container">
                        <div class="charitable-organizer-image-column">
                            <div class="charitable-organizer-image" ' . $image_bg_css . '></div>
                        </div>
                        <div class="charitable-organizer-info">
                            <div class="charitable-organizer-name">' . esc_html( $creator_name ) . '</div>
                            <div class="charitable-organizer-title charitable-main-placeholder"><h5 class="charitable-field-template-headline">' . $role_or_title . '</h5></div>
                            <div class="charitable-organizer-description">' . $this->format( $field_id, $organizer_content, $campaign_data ) . '</div>
                        </div>
                    </div>';

			}
		}

		/**
		 * Organizer preview inside the builder.
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
		 * Organizer display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type Field Type.
		 * @param array  $field_data Deprecated.
		 * @param array  $campaign_data  Form data and settings.
		 */
		public function field_display( $field_type = '', $field_data = false, $campaign_data = false ) {

			$html = $this->field_display_wrapper( $this->render( $field_data, $campaign_data ), $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore
		}

		/**
		 * Organizer display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_id       Field id.
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

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['role_or_title'] ) ? $settings['role_or_title'] : esc_html__( 'Organizer', 'charitable' ),
				esc_html__( 'Role or Title', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_role_or_title' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'role_or_title' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add a headline to this field.', 'charitable' ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			$campaign_creator_id = ! empty( $campaign_data['settings']['general']['campaign_creator_id'] ) ? intval( $campaign_data['settings']['general']['campaign_creator_id'] ) : false;

			$users = apply_filters( 'charitable_allowed_campaign_creators', [] );
			if ( empty( $users ) ) {
				$users = get_users();
			}
			foreach ( $users as $user ) {
				$users_to_pass[ $user->data->ID ] = array(
					'avatar' => esc_url( get_avatar_url( $user->data->ID ) ),
					'text'   => ( '' . charitable_get_creator_data( $user->data->ID ) ),
					'meta'   => '&nbsp;( ID: ' . $user->data->ID . ' )&nbsp;Joined: ' . gmdate( 'M d, Y ', strtotime( $user->data->user_registered ) ) . ' ',
				);
			}

			echo $charitable_builder_form_fields->generate_user_dropdown_mini( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$campaign_data,
				esc_html__( 'Campaign Creator', 'charitable' ),
				array(
					'from'            => 'field',
					'id'              => 'field_' . esc_attr( $this->type ) . '_campaign_creator' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'campaign_creator' ),
					'field_id'        => esc_attr( $field_id ),
					'default'         => get_current_user_id(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'options'         => $users_to_pass, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'selected_value'  => intval( $campaign_creator_id ),
					'container_class' => 'campaign-builder-campaign-creator-id-mini',
				)
			);

			echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['content'] ) ? $settings['content'] : false,
				esc_html__( 'Organizer Description', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_html' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'content' ),
					'field_id'        => esc_attr( $field_id ),
					'tooltip'         => esc_html( $this->tooltip ),
					'class'           => 'campaign-builder-htmleditor',
					'rows'            => 30,
					'html'            => true,
					'code'            => false,
					'container_class' => 'campaign-builder-campaign-creator-description',
					'special'         => 'organizer_content',
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
					'id'       => 'field_' . esc_attr( $this->type ) . '_css_class' . '_' . intval( $field_id ), // phpcs:ignore
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

			wp_send_json_success( [ 'html' => $html ] );

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
		 * @param int   $field         Field.
		 * @param mixed $field_data    Field data.
		 * @param array $campaign_data Form data and settings.
		 */
		public function validate( $field, $field_data = false, $campaign_data = false ) {
		}

		/**
		 * Get the creator data.
		 *
		 * @since 1.8.0
		 *
		 * @param int|null $user_id The user ID.
		 *
		 * @return string The creator data.
		 */
		public function get_creator_data( $user_id = null ) {

			$user_info = $user_id ? new WP_User( $user_id ) : wp_get_current_user();

			if ( $user_info->first_name ) {

				if ( $user_info->last_name ) {
					return $user_info->first_name . ' ' . $user_info->last_name;
				}

				return $user_info->first_name;
			}

			return $user_info->display_name;
		}
	}

	new Charitable_Field_Organizer();

endif;