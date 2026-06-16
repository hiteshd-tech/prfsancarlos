<?php
/**
 * Base case for fields.
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

if ( ! class_exists( 'Charitable_Builder_Field' ) ) :

	/**
	 * Base field template.
	 *
	 * @since 1.8.0
	 */
	abstract class Charitable_Builder_Field {

		/**
		 * Full name of the field type, eg "Paragraph Text".
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Type of the field, eg "textarea".
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $type;

		/**
		 * Defines if the field is able to be edited by the user.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $can_be_edited = false;

		/**
		 * Defines if the field is able to be deleted by the user.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $can_be_deleted = true;

		/**
		 * Defines if the field is able to be duplicated by the user.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $can_be_duplicated = true;

		/**
		 * Defines the label when the user hovers over the edit button in the preview.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $edit_label = '';

		/**
		 * Defines the type.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $edit_type = '';

		/**
		 * Field group the field belongs to.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $edit_section = 'standard';

		/**
		 * Field group the field belongs to.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $group = 'standard';

		/**
		 * Name of field that requires a quill WYSIWYG JS applied to it.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $html_field = '';

		/**
		 * Defines the field edit for editting.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $edit_field_id = '';

		/**
		 * Defines any default tooltip text.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $tooltip = '';

		/**
		 * Defines default alignment (left/center/right) for the field element.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $align_default = 'left';

		/**
		 * Defines a charavter limit in the preview window, intended for textareas (like campaign description).
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $preview_character_limit = 550;

		/**
		 * Font Awesome Icon used for the editor button, eg "fa-list".
		 *
		 * @since 1.8.0
		 *
		 * @var mixed
		 */
		public $icon = false;

		/**
		 * Priority order the field button should show inside the "Add Fields" tab.
		 *
		 * @since 1.8.0
		 *
		 * @var int
		 */
		public $order = 1;

		/**
		 * Placeholder to hold default value(s) for some field types.
		 *
		 * @since 1.8.0
		 *
		 * @var mixed
		 */
		public $defaults;

		/**
		 * Current campaign ID in the admin builder.
		 *
		 * @since 1.8.0
		 *
		 * @var int|bool
		 */
		public $form_id;

		/**
		 * Current field ID.
		 *
		 * @since 1.8.0
		 *
		 * @var int
		 */
		public $field_id;

		/**
		 * Current form data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $form_data;

		/**
		 * Current field data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $field_data;

		/**
		 * Total number of fields of this type that can exist in a single campaign template.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $max_allowed = false;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 *
		 * @param bool $init Pass false to allow to shortcut the whole initialization, if needed.
		 */
		public function __construct( $init = true ) {

			if ( ! $init ) {
				return;
			}

			// The form ID is to be accessed in the builder.
			$this->form_id = isset( $_GET['campaign_id'] ) ? (int) $_GET['campaign_id'] : false; // phpcs:ignore WordPress.Security.NonceVerification

			// Bootstrap.
			$this->init();

			// Add fields tab.
			add_filter( 'charitable_builder_design_buttons', [ $this, 'field_button' ], 15 );

			// Field options tab.
			add_action( "charitable_builder_fields_options_{$this->type}", [ $this, 'field_options' ], 10 );

			// Preview fields.
			add_action( "charitable_builder_fields_previews_{$this->type}", [ $this, 'field_preview' ], 10, 3 );

			// AJAX Add new field.
			add_action( "wp_ajax_charitable_new_field_{$this->type}", [ $this, 'field_new' ] );

			// Display field input elements on front-end.
			add_action( "charitable_display_field_{$this->type}", [ $this, 'field_display' ], 10, 3 );

			// Display field on back-end.
			add_filter( "charitable_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_true', 9 );

			// Validation on submit.
			add_action( "charitable_process_validate_{$this->type}", [ $this, 'validate' ], 10, 3 );

			// Format.
			add_action( "charitable_process_format_{$this->type}", [ $this, 'format' ], 10, 3 );

			// Prefill.
			add_filter( 'charitable_field_properties', [ $this, 'field_prefill_value_property' ], 10, 3 );

			// Change the choice's value while saving entries.
			add_filter( 'charitable_process_before_form_data', [ $this, 'field_fill_empty_choices' ] );

			// Change field name for ajax error.
			add_filter( 'charitable_process_ajax_error_field_name', [ $this, 'ajax_error_field_name' ], 10, 4 );

			// Add HTML line breaks before all newlines in Entry Preview.
			add_filter( "charitable_pro_fields_entry_preview_get_field_value_{$this->type}_field_after", 'nl2br', 100 );
		}

		/**
		 * All systems go. Used by subclasses. Required.
		 *
		 * @since 1.8.0
		 */
		abstract public function init();

		/**
		 * Create a new field in the admin AJAX editor.
		 *
		 * @since 1.8.0
		 */
		public function field_new() {

			// Run a security check.
			if ( ! check_ajax_referer( 'charitable-builder', 'nonce', false ) ) {
				wp_send_json_error( esc_html__( 'Your session expired. Please reload the builder.', 'charitable' ) );
			}

			// phpcs:disable WordPress.Security.NonceVerification.Missing
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce handles validation
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'charitable-builder' ) ) {
				wp_send_json_error( esc_html__( 'A security or cache check has failed. Please reload the builder.', 'charitable' ) );
			}

			// Check for permissions.
			if ( ! charitable_current_user_can( 'edit_campaigns' ) ) {
				wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'charitable' ) );
			}

			// Check for field type to add.
			if ( empty( $_POST['type'] ) ) {
				wp_send_json_error( esc_html__( 'No field type found', 'charitable' ) );
			}

			// Grab field data.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_text_field
			$campaign_title = ! empty( $_POST['campaign_title'] ) && '' !== trim( wp_unslash( $_POST['campaign_title'] ) ) ? sanitize_text_field( wp_unslash( $_POST['campaign_title'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$field_args     = ! empty( $_POST['defaults'] ) && is_array( $_POST['defaults'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['defaults'] ) ) : [];
			$field_type     = sanitize_key( $_POST['type'] );
			$field_id       = 0 === absint( $_POST['field_id'] ) ? 1 : absint( $_POST['field_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			$field          = [
				'id'          => $field_id,
				'type'        => $field_type,
				'label'       => $this->name,
				'description' => '',
			];
			$field          = wp_parse_args( $field_args, $field );
			$field          = apply_filters( 'charitable_field_new_default', $field );
			$field_required = apply_filters( 'charitable_field_new_required', '', $field );
			$field_class    = apply_filters( 'charitable_field_new_class', '', $field );
			// $field_max         = 0 === absint( $_POST['field_max'] ) ? 1 : absint( $_POST['field_max'] );
			// $field_helper_hide = ! empty( $_COOKIE['charitable_field_helper_hide'] );

			$campaign_data = array();

			// Field types that default to required.
			if ( ! empty( $field_required ) ) {
				$field_required    = 'required';
				$field['required'] = '1';
			}

			$campaign_data = get_post_meta( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0, 'campaign_settings_v2', true );

			// If $campaign_data is false made it an array.
			if ( ! is_array( $campaign_data ) ) {
				$campaign_data = array();
			}

			// If there isn't any data, we might still have the campaign title at least from the form (probably not saved).
			if ( empty( $campaign_data['title'] ) && '' !== $campaign_title ) {
				$campaign_data['title'] = $campaign_title;
			}

			// Build Preview.
			ob_start();
			$this->field_preview( $field, $campaign_data, $field_id );
			$prev    = ob_get_clean();
			$preview = sprintf(
				'<div class="charitable-field charitable-field-%1$s %2$s %3$s %4$s %5$s %6$s" id="charitable-field-%7$d" data-field-id="%7$d" data-field-type="%8$s" data-field-max="%9$d">',
				esc_attr( $field_type ),
				esc_attr( $field_required ),
				esc_attr( $field_class ),
				$this->can_be_edited ? 'charitable-can-edit' : 'charitable-no-edit',
				$this->can_be_duplicated ? 'charitable-can-duplicate' : 'charitable-no-duplicate',
				$this->can_be_deleted ? 'charitable-can-delete' : 'charitable-no-delete',
				absint( $field['id'] ),
				esc_attr( $field_type ),
				absint( $this->max_allowed )
			);

			if ( apply_filters( 'charitable_field_new_display_edit_button', true, $field ) ) {
				if ( $this->can_be_edited ) :
					$edit_label = ! empty( $this->edit_label ) ? esc_attr( $this->edit_label ) : esc_attr__( 'Edit Field', 'charitable' );
					$preview   .= sprintf( '<a href="#" class="charitable-field-edit" data-type="' . $this->edit_type . '" data-section="' . $this->edit_section . '" title="%s"><i class="fa fa-pencil"></i></a>', $edit_label );
				endif;
			}

			if ( apply_filters( 'charitable_field_new_display_duplicate_button', true, $field ) ) {
				if ( $this->can_be_duplicated ) :
					$preview .= sprintf( '<a href="#" class="charitable-field-duplicate" title="%s"><i class="fa fa-files-o" aria-hidden="true"></i></a>', esc_attr__( 'Duplicate Field', 'charitable' ) );
				endif;
			}

			if ( apply_filters( 'charitable_field_new_display_delete_button', true, $field ) ) {
				if ( $this->can_be_deleted ) :
					$preview .= sprintf( '<a href="#" class="charitable-field-delete" title="%s"><i class="fa fa-trash-o"></i></a>', esc_attr__( 'Delete Field', 'charitable' ) );
				endif;
			}

			$preview .= $prev;
			$preview .= '</div>';

			// Build Options.
			$class   = apply_filters( 'charitable_builder_field_option_class', '', $field );
			$options = sprintf(
				'<div class="charitable-field-option charitable-field-option-%1$s %2$s" id="charitable-field-option-%3$d" data-field-id="%3$d">',
				sanitize_html_class( $field['type'] ),
				charitable_sanitize_classes( $class ),
				absint( $field['id'] )
			);

			$options .= sprintf(
				'<input type="hidden" name="fields[%1$d][id]" value="%1$d" class="charitable-field-option-hidden-id">',
				absint( $field['id'] )
			);
			$options .= sprintf(
				'<input type="hidden" name="fields[%d][type]" value="%s" class="charitable-field-option-hidden-type">',
				absint( $field['id'] ),
				esc_attr( $field['type'] )
			);

			ob_start();
			$this->field_options( $field );
			$options .= ob_get_clean();
			$options .= '</div>';

			$edit_field_html = $this->settings_display( $field_id, $campaign_data );

			// Prepare to return compiled results.
			wp_send_json_success(
				[
					'form_id'         => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
					'field_id'        => absint( $field_id ),
					'field'           => $field,
					'preview'         => $preview,
					'options'         => $options,
					'max'             => $this->max_allowed,
					'edit_field_html' => $edit_field_html,
					'wysiwyg'         => $this->html_field,
				]
			);
		}

		/**
		 * Create the button for the 'Add Fields' tab, inside the form editor.
		 *
		 * @since 1.8.0
		 *
		 * @param array $fields List of form fields with their data.
		 *
		 * @return array
		 */
		public function field_button( $fields ) {

			// Don't add if it is already added.
			if ( isset( $fields[ $this->edit_section ]['fields'][ $this->type ] ) ) {
				return $fields;
			}

			// Add field information to fields array.
			$fields[ $this->edit_section ]['fields'][ $this->type ] = [
				'order' => $this->order,
				'name'  => $this->name,
				'type'  => $this->type,
				'icon'  => $this->icon,
			];

			// Wipe hands clean.
			return $fields;
		}

		/**
		 * Create the field options panel. Used by subclasses.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Field data and settings.
		 */
		abstract public function field_options( $field );

		/**
		 * Create the field preview. Used by subclasses.
		 *
		 * @since 1.8.0
		 *
		 * @param array   $field Field data and settings.
		 * @param array   $campaign_data Campaign data and settings.
		 * @param integer $field_id Field ID.
		 */
		abstract public function field_preview( $field, $campaign_data, $field_id );

		/**
		 * Display the field input elements on the frontend.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field      Field data and settings.
		 * @param array $field_atts Field attributes.
		 * @param array $form_data  Form data and settings.
		 */
		abstract public function field_display( $field, $field_atts, $form_data );

		/**
		 * Create the settings display panel. Used by subclasses.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field campaign_data Campaign data and settings.
		 */
		abstract public function settings_display( $field, $campaign_data );

		/**
		 * Display field input errors if present.
		 *
		 * @since 1.8.0
		 *
		 * @param string $key   Input key.
		 * @param array  $field Field data and settings.
		 */
		public function field_display_error( $key, $field ) {

			// Need an error.
			if ( empty( $field['properties']['error']['value'][ $key ] ) ) {
				return;
			}

			printf(
				'<label class="charitable-error" for="%s">%s</label>',
				esc_attr( $field['properties']['inputs'][ $key ]['id'] ),
				esc_html( $field['properties']['error']['value'][ $key ] )
			);
		}

		/**
		 * Wrap field HTML title - a universal title function!
		 *
		 * @since 1.8.0
		 *
		 * @param string $title Field title.
		 */
		public function field_title( $title = false ) {

			return '<h6 class="charitable-preview-field-title"><span>' . $title . '</span></h6>';
		}

		/**
		 * Wrap field HTML with a container that allows a (mostly) universal way of aligning and controlling visuals of the field in the preview area.
		 *
		 * @since 1.8.0
		 *
		 * @param string $html   Field HTML.
		 * @param array  $field_data Field data and settings.
		 */
		public function field_wrapper( $html = '', $field_data = false ) {

			$align            = isset( $field_data['align'] ) ? esc_attr( $field_data['align'] ) : $this->align_default;
			$columns          = isset( $field_data['columns'] ) ? intval( $field_data['columns'] ) : 1;
			$width_percentage = isset( $field_data['width_percentage'] ) ? absint( $field_data['width_percentage'] ) : 100;

			$css_classes = array(
				'charitable-preview-field-container',
				'charitable-preview-align-' . $align,
				'charitable-preview-columns-' . $columns,
			);

			$html = '<div class="' . implode( ' ', $css_classes ) . '"  style="width: ' . $width_percentage . '%"><span class="charitable-preview-field-indicator charitable-hidden"></span>' . $html . '</div>';

			return $html;
		}

		/**
		 * Wrap field HTML with a container that allows a (mostly) universal way of aligning and controlling visuals of the field in the preview area.
		 *
		 * @since 1.8.0
		 *
		 * @param string $html   Field HTML.
		 * @param array  $field_data Field data and settings.
		 */
		public function field_display_wrapper( $html, $field_data = false ) {

			$align            = isset( $field_data['align'] ) ? esc_attr( $field_data['align'] ) : $this->align_default;
			$columns          = isset( $field_data['columns'] ) ? intval( $field_data['columns'] ) : 1;
			$width_percentage = isset( $field_data['width_percentage'] ) ? absint( $field_data['width_percentage'] ) : 100;
			$custom_css_class = isset( $field_data['css_class'] ) && '' !== trim( $field_data['css_class'] ) ? esc_attr( $field_data['css_class'] ) : false;

			$css_classes = array(
				'charitable-campaign-field-container',
				'charitable-campaign-align-' . $align,
				'charitable-campaign-columns-' . $columns,
			);

			if ( $custom_css_class ) {
				$css_classes[] = $custom_css_class;
			}

			$html = '<div class="charitable-campaign-field charitable-campaign-field-' . $this->type . ' ' . implode( ' ', $css_classes ) . '"><div class="charitable-campaign-field-inner" style="width: ' . $width_percentage . '%">' . $html . '</div></div>';

			return $html;
		}

		/**
		 * Displays a preview area because the field can't be totally functional in a preview state (say on a preview campaign page)
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type    Field type.
		 * @param array  $field_data    Any field data.
		 * @param array  $campaign_data Form data and settings.
		 */
		public function show_preview( $field_type, $field_data = false, $campaign_data = false ) {

			$field_label = ucwords( str_replace( '-', ' ', $field_type ) );

			$html = '<div class="charitable-campaign-builder-field-no-preview">';
			/* translators: %s is the field label */
			$html .= '<p>' . sprintf( esc_html__( 'The %s field cannot be rendered in preview mode.', 'charitable' ), $field_label ) . '</p></div>';

			return apply_filters( 'charitable_campaign_builder_preview_html', $html, $field_type, $field_data, $campaign_data );
		}

		/**
		 * Keep a number within a certain range.
		 *
		 * @param integer $n The number.
		 * @param integer $min Min.
		 * @param integer $max Max.
		 * @return integer
		 */
		public function keep_in_range( $n, $min = 0, $max = 100 ) {
			return max( min( $max, $n ), $min );
		}

		/**
		 * Get field name for ajax error message.
		 *
		 * @since 1.8.0
		 *
		 * @param string $name  Field name for error triggered.
		 * @param array  $field Field settings.
		 * @param array  $props List of properties.
		 * @param string $error Error message.
		 *
		 * @return string
		 */
		public function ajax_error_field_name( $name, $field, $props, $error ) { // phpcs:ignore

			if ( $name ) {
				return $name;
			}
			$input = isset( $props['inputs']['primary'] ) ? $props['inputs']['primary'] : end( $props['inputs'] );

			return (string) isset( $input['attr']['name'] ) ? $input['attr']['name'] : '';
		}
	}

endif;
