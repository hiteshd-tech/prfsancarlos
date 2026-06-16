<?php
/**
 * Load the field types.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 */
class Charitable_Builder_Form_Fields {

	/**
	 * The ID slug for the panel.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	public $id_slug = 'charitable-panel-field-settings';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {

		$this->init();
	}

	/**
	 * Load and init the base field class.
	 *
	 * @since 1.8.0
	 */
	public function init() {
	}

	/**
	 * Hooks.
	 *
	 * @since 1.7.7
	 */
	private function hooks() {
	}

	/**
	 * Load default field types.
	 *
	 * @since 1.0.0
	 */
	public function load() {
	}

	/**
	 * Produce tooltip HTML w/ icon.
	 *
	 * @since 1.8.0
	 *
	 * @param array $tooltip_text Tooltip text.
	 *
	 * @return string
	 */
	public function get_tooltip_html( $tooltip_text = false ) {

		if ( false === $tooltip_text ) {
			return false;
		}

		$html = charitable_get_tooltip_html( $tooltip_text );

		return $html;
	}

	/**
	 * Output a hidden field for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_hidden_field( $value = false, $args = false ) {

		$defaults = array(
			'name'     => '',
			'id'       => '',
			'field_id' => '', // data.
		);

		$params        = array_replace_recursive( $defaults, $args );
		$field_id_attr = ( isset( $params['field_id'] ) && '' !== $params['field_id'] ) ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;

		if ( is_array( $params['name'] ) && isset( $params['name'][2] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		} elseif ( is_array( $params['name'] ) ) {
			$params['name'] = implode( '-', $params['name'] );
		}

		$html  = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-hidden" ' . $field_id_attr . '>';
		$html .= '<input id="' . $this->id_slug . '-' . $params['id'] . '" type="hidden" name="' . $params['name'] . '" value="' . $value . '"/>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Output a generic text div/area for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 * @version 1.8.1.12 - detect $icon is a jpg, png, or gif.
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_text( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'placeholder'     => '',
			'tooltip'         => false,
			'icon'            => false,
			'fa_icon'         => false,
			'default'         => false,
			'add_commas'      => false,
			'type'            => 'text', // could also be "url", etc.
			'field_id'        => '', // data.
		);

		$params = array_replace_recursive( $defaults, $args );

		$params['container_class'] .= ( isset( $params['icon'] ) && false !== $params['icon'] ) ? ' has-icon' : false;
		$icon                       = ( isset( $params['icon'] ) && false !== $params['icon'] ) ? charitable()->get_path( 'assets', false ) . $params['icon'] : false;
		$fa_icon                    = ( isset( $params['fa_icon'] ) && false !== $params['fa_icon'] ) ? '<i class="' . $params['fa_icon'] . ' errspan"></i>' : false;
		$field_id_attr              = ( isset( $params['field_id'] ) && '' !== $params['field_id'] ) ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$description                = ( isset( $params['description'] ) && false !== $params['description'] ) ? '<p class="charitable-campaign-builder-field-textarea-description">' . esc_html( $params['description'] ) . '</p>' : false;
		$tooltip_html               = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;
		$conditional_attr           = ( isset( $params['conditional'] ) && '' !== $params['conditional'] ) ? 'data-conditional-id="' . intval( $params['conditional'] ) . '"' : false;
		$value                      = false === $value && isset( $params['default'] ) && false !== $params['default'] ? esc_html( $params['default'] ) : $value;
		$toggle_value               = false;

		$container_classes         = explode( ' ', $params['container_class'] );
		$container_classes         = array_unique( $container_classes );
		$params['container_class'] = implode( ' ', $container_classes );

		if ( is_array( $value ) && key_exists( $params['name'][2], $value ) ) {
			$value = $value[ $params['name'][2] ];
		} elseif ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) && isset( $params['name'][2] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		} elseif ( is_array( $params['name'] ) ) {
			$params['name'] = implode( '-', $params['name'] );
		}

		// santitize and remove all kinds of quotes from the input field text value.
		$value = str_replace( array( '"', "'", '&#8220;', '&#8221;', '&#8216;', '&#8217;' ), '', $value );
		$value = esc_html( $value );

		if ( true === $params['add_commas'] && '' !== $value ) {
			$value = Charitable_Currency::get_instance()->get_sanitized_and_localized_amount( $value );
		}

		$field_id_attr .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;

		// If the toggle is a css_class, add another css class so it's easier for us to target this.
		if ( 'css_class' === $toggle_value ) {
			$params['class'] .= ' charitable-custom-css-field';
		}

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-text ' . $params['container_class'] . '" ' . $field_id_attr . ' ' . $conditional_attr . '>
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>
                    <div class="charitable-internal">';

		// Is icon a jpg, png, or gif?
		if ( ! empty( $icon ) && false !== $icon ) :
			if ( strpos( $icon, '.jpg' ) || strpos( $icon, '.png' ) || strpos( $icon, '.gif' ) ) {
				$html .= '<img class="charitable-currency-symbol-image" src="' . $icon . '" alt="' . $label . '" />';
			} elseif ( false !== $icon ) {
				$html .= '<span class="charitable-currency-symbol">' . $params['icon'] . '</span>';
			}
		endif;

		$html .= '
                    <input type="' . $params['type'] . '" id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" value="' . $value . '" class="' . $params['class'] . '" ' . $field_id_attr . ' placeholder="' . $params['placeholder'] . '" />
                    ' . $description . '
                    </div>
                </div>';

		return $html;
	}

	/**
	 * Output a toggle switch for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_toggle( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'field_id'        => '',
			'title'           => '',
			'visibility'      => false,
			'checked_value'   => '1',
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$checked                   = ( $value !== false && $value === $params['checked_value'] ) ? 'checked="checked"' : false;
		$field_id_attr             = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$field_id_attr            .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;
		$params['container_class'] = $this->maybe_add_visibility_classes( $params['container_class'], $params['visibility'] );

		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes     = ! empty( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes     = array_unique( $css_container_classes );
		$params['container_class'] = implode( ' ', $css_container_classes );

		$html = isset( $params['title'] ) && '' !== $params['title'] ? '<h5 class="charitable-campaign-builder-setting-subheading">' . esc_html( $params['title'] ) . '</h5>' : false;

		$html .= '<div data-field-id="' . $params['field_id'] . '" id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-toggle ' . $params['container_class'] . '" ' . $field_id_attr . '>
                    <span class="charitable-toggle-control">
                    <input type="checkbox" id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" value="' . $params['checked_value'] . '" class="' . $params['class'] . '" ' . $checked . ' />
                    <label class="charitable-toggle-control-icon" for="' . $this->id_slug . '-' . $params['id'] . '"></label>
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . '</label>
                    </span>
                </div>';

		return $html;
	}

	/**
	 * Output a toggle switch for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 * @since 1.8.4.5 - bug fix for saving when $values is present.
	 *
	 * @param mixed  $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_toggles( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => 'charitable-checkbox-for-toggle',
			'container_class' => '',
			'field_id'        => '',
			'type'            => 'toggle',
			'default'         => array(),
			'checked_value'   => 'true',
			'use_defaults'    => true,
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$field_id_attr = '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;

		$css_container_classes     = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes     = array_unique( $css_container_classes );
		$params['container_class'] = implode( ' ', $css_container_classes );

		$html = '<div data-field-id="' . $params['field_id'] . '" id="' . $this->id_slug . '-' . $params['id'] . '-wrap" ' . $field_id_attr . ' class="charitable-panel-field charitable-panel-field-toggle ' . $params['container_class'] . '">
        <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . '</label>';

		foreach ( $params['options'] as $text => $box_id ) {

			// If there is any value, we need to check if the box_id is in the value.

			if ( ! empty( $value ) && is_array( $value ) ) {
				if ( key_exists( $box_id, $value ) ) {
					$checked = 'checked="checked"';
				} else {
					$checked = false;
				}
			} elseif ( is_array( $params['default'] ) && in_array( $box_id, $params['default'] ) ) {
				$checked = 'checked="checked"';
			} else {
				$checked = false;
			}

			$toggle_name = $params['name'] . '[' . $box_id . ']';

			$html .= '<span class="charitable-toggle-control" ' . $field_id_attr . ' data-ajax-label="' . $box_id . '" data-field-type="' . $params['type'] . '">
                <input type="checkbox" id="' . $this->id_slug . '-' . $box_id . '-' . $params['id'] . '" name="' . $toggle_name . '" value="' . $box_id . '" class="' . $params['class'] . '" ' . $checked . ' ' . $field_id_attr . ' />
                <label class="charitable-toggle-control-icon" for="' . $this->id_slug . '-' . $box_id . '-' . $params['id'] . '"></label>
                <label for="' . $this->id_slug . '-' . $box_id . '-' . $params['id'] . '">' . $text . '</label>
            </span>';
		}

		$html .= '
                </div>';

		return $html;
	}

	/**
	 * Output a generic checkbox for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_checkbox( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'  => '',
			'id'    => '',
			'class' => '',
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$checked = ( $value !== false && $value == $params['checked_value'] ) ? 'checked="checked"' : false;

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-checkbox" data-field-id="' . $params['field_id'] . '">
                    <input type="checkbox" id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" value="' . $params['checked_value'] . '" class="' . $params['class'] . '" ' . $checked . ' />
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . '</label>
                </div>';

		return $html;
	}

	/**
	 * Output a generic set of checkboxes for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 * @version 1.8.2 added tooltips, container_class.
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_checkboxes( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'field_id'        => '',
			'tooltip'         => false,
			'visibility'      => false,
			'defaults'        => array(),
		);

		$params    = array_replace_recursive( $defaults, $args );
		$extra_css = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		if ( ! is_array( $value ) && false !== $value ) {
			$value = array( $value );
		} elseif ( ! is_array( $value ) ) {
			$value = array();
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}



		$css_container_classes      = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes      = array_unique( $css_container_classes );
		$params['container_class']  = implode( ' ', $css_container_classes );
		$params['container_class'] .= $extra_css;

		$field_id_attr = '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$description   = ( isset( $params['description'] ) && false !== $params['description'] ) ? '<p class="charitable-campaign-builder-field-checkbox-description">' . esc_html( $params['description'] ) . '</p>' : false;
		$tooltip_html  = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-checkboxes ' . $params['container_class'] . '" ' . $field_id_attr . '>
                    <label for="' . $this->id_slug . '-' . $params['id'] . '"> ' . $label . ' ' . $tooltip_html . '</label>' . $description;

		foreach ( $params['options'] as $text => $box_id ) {

			$checked = ( empty( $value ) && ! empty( $defaults ) && in_array( $box_id, $params['defaults'] ) )
						|| ( $value !== false && key_exists( $box_id, $value ) && $value[ $box_id ] !== false ) ? 'checked="checked"' : false;

			$html .= '<p ' . $field_id_attr . '><input id="' . $params['name'] . '-' . $box_id . '" type="checkbox" ' . $checked . ' name="' . $params['name'] . '[' . $box_id . ']" value="1" /><label for="' . $params['name'] . '-' . $box_id . '">' . $text . '</label></p>';

		}

		$html .= '
                </div>';

		return $html;
	}


	/**
	 * Output a generic set of radio buttons for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 * @version 1.8.4.5
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_radio_options( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'tooltip'         => false,
			'visibility'      => false,
			'field_id'        => '', // data.
		);

		$params    = array_replace_recursive( $defaults, $args );
		$extra_css = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$field_id_attr  = ( isset( $params['field_id'] ) && '' !== $params['field_id'] ) ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$field_id_attr .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;
		$description    = ( isset( $params['description'] ) && false !== $params['description'] ) ? '<p class="charitable-campaign-builder-field-textarea-description">' . esc_html( $params['description'] ) . '</p>' : false;
		$tooltip_html   = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;

		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes      = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes      = array_unique( $css_container_classes );
		$params['container_class']  = implode( ' ', $css_container_classes );
		$params['container_class'] .= $extra_css;

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-radio-options ' . $params['container_class'] . '" ' . $field_id_attr . '>
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>';

		foreach ( $params['options'] as $text => $box_id ) {
			// For radio buttons, we just need to check if the value matches the box_id.
			$checked = ( $value[0] === $box_id ) ? 'checked="checked"' : '';
			// Only fall back to default if no value is set.
			if ( empty( $value[0] ) && ! empty( $params['option_default'] ) && $box_id === $params['option_default'] ) {
				$checked = 'checked="checked"';
			}

			$input_id = str_replace( '[', '-', $params['name'] );
			$input_id = str_replace( ']', '-', $input_id );
			$input_id = str_replace( '--', '-', $input_id );

			$html .= '<p ' . $field_id_attr . '><input id="' . $input_id . '-' . $box_id . '" type="radio" ' . $checked . ' name="' . $params['name'] . '" value="' . $box_id . '" /><label for="' . $input_id . '-' . $box_id . '">' . $text . '</label></p>';
		}

		$html .= $description . '
                </div>';

		return $html;
	}

	/**
	 * Output a generic dropdown for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_dropdown( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'default'         => false,
			'visibility'      => false,
			'options'         => false,
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$field_id_attr             = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$field_id_attr            .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;
		$selected_value            = isset( $params['selected_value'] ) ? esc_html( $params['selected_value'] ) : $value;
		$default_value             = isset( $params['default'] ) && false !== $params['default'] ? esc_html( $params['default'] ) : false;
		$params['container_class'] = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes     = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes     = array_unique( $css_container_classes );
		$params['container_class'] = implode( ' ', $css_container_classes );

		$tooltip_html = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-dropdown ' . $params['container_class'] . '" ' . $field_id_attr . '>
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>';

		if ( false !== $params['options'] && is_array( $params['options'] ) && count( $params['options'] ) > 0 ) {

			$html .= '<select id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '">';
			foreach ( $params['options'] as $option_value => $text ) {
				$selected = ( $selected_value === '' && $default_value && $option_value === $default_value ) ? 'selected="selected"' : false;
				$selected = ( false === $selected && $selected_value && $option_value === $selected_value ) ? 'selected="selected"' : false;
				$html    .= '<option value="' . $option_value . '" ' . $selected . '>' . $text . '</option>';
			}
			$html .= '</select>';

		} elseif ( ! empty( $params['not_available'] ) ) {

			$html .= '<p>' . esc_html( $params['not_available'] ) . '</p>';

		}

		$html .= '
                </div>';

		return $html;
	}

	/**
	 * Output a generic textbox for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_textbox( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => array(),
			'placeholder'     => '',
			'description'     => '',
			'rows'            => 10,
			'field_id'        => '',
			'visibility'      => false,
			'hidden'          => false,
			'html'            => false,
			'code'            => false,
			'special'         => false,
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		if ( $params['html'] ) {
			// add to class so quill can pick it up.
			$params['class'] .= ' campaign-builder-htmleditor';
		}

		if ( $params['code'] ) {
			// add to class so the js can pick it up.
			$params['class'] .= ' campaign-builder-codeeditor';
		}

		// convert into an array to remove duplicates, then convert back to a string.
		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes       = isset( $params['container_class'] ) && ! empty( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes       = array_unique( $css_container_classes );
		$params['container_classes'] = implode( ' ', $css_container_classes );
		if ( $params['hidden'] ) { // if hidden is true, then this is output is deliberately hidden from the user. This is different from "visibility" which is used for conditional logic.
			$params['container_classes'] .= ' charitable-hidden';
		}

		$field_id_attr = ( isset( $params['field_id'] ) && '' !== $params['field_id'] ) ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$description   = ( isset( $params['description'] ) && '' !== trim( $params['description'] ) ) ? '<p class="charitable-campaign-builder-field-textarea-description">' . esc_html( $params['description'] ) . '</p>' : false;
		$tooltip_html  = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;

		if ( false !== $params['special'] ) {

			if ( 'campaign_description' === $params['special'] && '' === trim( $value ) ) {

				$value = '

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


                ';

			}

		}

		if ( $args['html'] ) {

			$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-textarea ' . $params['container_classes'] . '" ' . $field_id_attr . ' data-special-type="' . $params['special'] . '">
                        <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>' . $description . '
                        <div data-textarea-name="' . $params['name'] . '" id="' . $this->id_slug . '-' . $params['id'] . '" class="' . $params['class'] . '">' . $value . '</div>
                        <input type="hidden" value="' . htmlentities( $value ) . '" name="' . $params['name'] . '" />
                    </div>';

		} elseif ( $args['code'] ) {

			$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field  charitable-panel-field-textarea ' . $params['container_classes'] . '" ' . $field_id_attr . '>
                        <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>' . $description . '
                        <textarea id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" placeholder="" class="' . $params['class'] . '" rows="' . $params['rows'] . '">' . $value . '</textarea>
                    </div>';

		} else {

			$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field  ' . $params['container_classes'] . ' charitable-panel-field-textarea" ' . $field_id_attr . '>
                        <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>' . $description . '
                        <textarea id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" placeholder="" class="' . $params['class'] . '" rows="' . $params['rows'] . '">' . $value . '</textarea>
                    </div>';

		}

		return apply_filters( 'charitable_campaign_builder_form_field_textbox', $html, $value, $label, $args );
	}

	/**
	 * Output a generic date picker for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_date( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'placeholder'     => '',
			'date-format'     => 'F j, Y',
			'data-date'       => '',
			'label-below'     => '',
			'visibility'      => false,
			'icon'            => false,
			'container_class' => '',
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( $value === '0' || $value === 0 ) { // todo: adding this while we figure some things out.
			$value = false;
		}

		if ( false !== $value && '' !== trim( $value ) && ! charitable_is_valid_timestamp( $value ) ) {
			$value = date( $params['date-format'], strtotime( $value ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$params['container_class'] .= ( isset( $params['icon'] ) && false !== $params['icon'] ) ? 'has-icon' : false;

		$container_classes         = explode( ' ', $params['container_class'] );
		$container_classes         = array_unique( $container_classes );
		$params['container_class'] = implode( ' ', $container_classes );
		$tooltip_html              = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;

		$icon = ( isset( $params['icon'] ) && false !== $params['icon'] ) ? charitable()->get_path( 'assets', false ) . $params['icon'] : false;

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-date ' . $params['container_class'] . '">
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>
                    <div class="charitable-internal">
                        <div class="charitable-date-field-icon"><img src="' . $icon . '" class="" /></div>
                    </div>
                    <input type="text" id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" value="' . $value . '" class="' . $params['class'] . '" placeholder="' . $params['placeholder'] . '" />
                    ' . $params['label_below'] . '
                </div>';

		return $html;
	}

	/**
	 * Output a number slider for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_number_slider( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'        => '',
			'id'          => '',
			'css_class'   => '',
			'placeholder' => '',
			'tooltip'     => false,
			'icon'        => false,
			'min'         => 0,
			'max'         => 100,
			'step'        => 10,
			'min_actual'  => '',
			'symbol'      => '%',
			'field_id'    => '', // data.
			'field_type'  => '',
			'visibility'  => false,
		);

		$params            = array_replace_recursive( $defaults, $args );
		$container_classes = ( false !== $params['icon'] ) ? 'has-icon' : false;
		$tooltip_html      = ( false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;

		// css classes.
		$css_classes   = array();
		$css_classes[] = $params['field_type'];
		if ( ! empty( $params['css_class'] ) ) {
			$css_classes[] = esc_attr( $params['css_class'] );
		}
		$css_classes = implode( ' ', $css_classes );

		if ( is_array( $value ) && key_exists( $params['name'][2], $value ) ) {
			$value = $value[ $params['name'][2] ];
		} elseif ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$html  = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-number-slider ' . $container_classes . '" data-field-id="' . $params['field_id'] . '">';
		$html .= '<label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>
                  <div class="charitable-internal">';
		$html .= '<input type="range" readonly
                class="charitable-number-slider ' . $css_classes . '"
                id="' . $this->id_slug . '-' . $params['id'] . '"
                name="' . esc_html( $params['name'] ) . '"
                value="' . $value . '"
                min="' . intval( $params['min'] ) . '"
                max="' . intval( $params['max'] ) . '"
                min-actual="' . intval( $params['min_actual'] ) . '"
                step="' . $params['step'] . '">

                <div
                    id="charitable-number-slider-hint-' . $params['id'] . '"
                    data-hint="' . $value . '"
                    data-symbol="' . $params['symbol'] . '"
                    class="charitable-number-slider-hint">' . $value . '
                ' . $params['symbol'] . '<small>minimum</small>
                </div>';
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Output an upload field for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_uploader( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'         => '',
			'id'           => '',
			'class'        => '',
			'tooltip'      => false,
			'icon'         => false,
			'field_id'     => '', // data.
			'field_type'   => '',
			'size'         => 'regular',
			'description'  => '',
			'button_label' => esc_html__( 'Attach File', 'charitable' ),
			'button_text'  => esc_html__( 'Attach', 'charitable' ),
		);

		$params            = array_replace_recursive( $defaults, $args );
		$container_classes = ( false !== $params['icon'] ) ? 'has-icon' : false;
		$icon              = ( false !== $params['icon'] ) ? '<i class="' . $params['icon'] . ' errspan"></i>' : false;
		$tooltip_html      = ( false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;
		$button_label      = ( false !== $params['button_label'] ) ? esc_html( $params['button_label'] ) : esc_html__( 'Attach File', 'charitable' );
		$placeholder       = ( false !== $params['placeholder'] ) ? esc_html( $params['placeholder'] ) : false;
		$description       = ( false !== $params['description'] ) ? '<p class="charitable-campaign-builder-field-uploader-description">' . esc_html( $params['description'] ) . '</p>' : false;

		$css_classes   = array();
		$css_classes[] = $params['field_type'];
		$css_classes[] = ( isset( $params['size'] ) && ! is_null( $params['size'] ) ) ? $params['size'] : 'regular';
		$css_classes   = implode( ' ', $css_classes );

		if ( is_array( $value ) && key_exists( $params['name'][2], $value ) ) {
			$value = $value[ $params['name'][2] ];
		} elseif ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$html  = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-uploader ' . $container_classes . '" data-field-id="' . $params['field_id'] . '">';
		$html .= '<label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>';
		$html .= ( false !== $description ) ? $description : false;
		$html .= '<div class="charitable-internal">';
		$html .= '<input type="url" class="charitable-uploader ' . $css_classes . '" id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '" placeholder="' . $placeholder . '" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<p><input type="button" data-uploader-title="' . $button_label . '" data-uploader-button-text="' . $button_label . '" class="charitable-campaign-builder-upload-button charitable-campaign-builder-button-secondary" value="' . $button_label . '"/>';
		$html .= '<button class="charitable-campaign-builder-clear-button charitable-campaign-builder-button-secondary">' . __( 'Clear', 'charitable' ) . '</button>';
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Output a generic align (left/center/right) field for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_align( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'              => '',
			'id'                => '',
			'class'             => '',
			'default'           => 'center',
			'align_left_icon'   => 'fa fa-align-left',
			'align_center_icon' => 'fa fa-align-center',
			'align_right_icon'  => 'fa fa-align-right',
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( false === $value && false !== $params['default'] ) {
			$value = $params['default'];
		}

		// Build the align icons, include active state.
		$align_types = array( 'left', 'center', 'right' );

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-align ' . $params['class'] . '" data-field-id="' . $params['field_id'] . '">
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . '</label>';

		foreach ( $align_types as $align_type ) {
			$active = ( $value === $align_type ) ? 'active' : false;
			$html  .= '<span class="' . $active . '"><a title="' . $align_type . '" href="#" data-align-value="' . $align_type . '"><i class="' . $params[ 'align_' . $align_type . '_icon' ] . '"></i></a></span>';
		}

		$html .= '<input type="hidden" value="' . $value . '" name="' . $params['name'] . '" />';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Output a UI that is meant to multiple select for tags. Forked from the generate_dropdown.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_tag_selector( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'default'         => false,
			'visibility'      => false,
			'options'         => false,
			'tooltip'         => false,
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $params['name'] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$field_id_attr             = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$field_id_attr            .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;
		$selected_value            = isset( $params['selected_value'] ) ? esc_html( $params['selected_value'] ) : $value;
		$default_value             = isset( $params['default'] ) && false !== $params['default'] ? esc_html( $params['default'] ) : false;
		$tooltip_html              = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( $params['tooltip'] ) : false;
		$params['container_class'] = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes     = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes     = array_unique( $css_container_classes );
		$params['container_class'] = implode( ' ', $css_container_classes );

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-dropdown ' . $params['container_class'] . '" ' . $field_id_attr . '>
				<label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>';

		if ( empty( $params['options'] ) ) {

			$html .= '<p><em><small>There are currently no Charitable specific tags. <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=campaign_tag&post_type=campaign' ) . '">Click here</a> to start adding some or <a href="https://wpcharitable.com/documentation/charitable-tags-and-categories">read our documentation for more</a>.</small></em></p>';

		}

		if ( false !== $params['options'] && is_array( $params['options'] ) && count( $params['options'] ) > 0 ) {

			if ( empty( $params['options'] ) ) {

				$html .= '<p><em><small>There are currently no Charitable specific tags. <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=campaign_tag&post_type=campaign' ) . '">Click here</a> to start adding some or <a href="https://wpcharitable.com/documentation/charitable-tags-and-categories">read our documentation for more</a>.</small></em></p>';

			} else {

				$html .= '<select id="' . $this->id_slug . '-' . $params['id'] . '" class="campaign-tag-field" name="' . $params['name'] . '" multiple="multiple">';
				foreach ( $params['options'] as $option_value => $text ) {

					$selected = is_array( $value ) && in_array( $option_value, $value, true ) ? 'selected="selected"' : false;

					$html .= '<option value="' . $option_value . '" ' . $selected . '>' . $text . '</option>';
				}
				$html .= '</select>';

			}

		}

		$html .= '
                </div>';

		return $html;
	}

	/**
	 * Output a user/author/creator dropdown for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_user_dropdown( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'default'         => false,
			'visibility'      => false,
			'options'         => false,
			'tooltip'         => false,
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$field_id_attr             = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$field_id_attr            .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;
		$selected_value            = isset( $params['selected_value'] ) ? esc_html( $params['selected_value'] ) : $value;
		$default_value             = isset( $params['default'] ) && false !== $params['default'] ? esc_html( $params['default'] ) : false;
		$tooltip_html              = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( wp_strip_all_tags( $params['tooltip'] ) ) : false;
		$params['container_class'] = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes     = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes     = array_unique( $css_container_classes );
		$params['container_class'] = implode( ' ', $css_container_classes );

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-dropdown ' . $params['container_class'] . '" ' . $field_id_attr . '>
				<label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>';

		if ( false !== $params['options'] && is_array( $params['options'] ) && count( $params['options'] ) > 0 ) {

			$html .= '<select id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '">';
			foreach ( $params['options'] as $option_value => $user_data ) {

				$text   = esc_html( $user_data['text'] );
				$avatar = ! empty( $user_data['avatar'] ) ? esc_url( $user_data['avatar'] ) : false;
				$meta   = ! empty( $user_data['meta'] ) ? ( $user_data['meta'] ) : false;

				$selected = ( isset( $selected_value ) && $option_value !== false && intval( $option_value ) === intval( $selected_value ) ) ? 'selected="selected"' : false;
				$selected = ( false === $selected && false !== $default_value && $option_value === $default_value ) ? 'selected="selected"' : $selected;
				$html    .= '<option data-avatar="' . $avatar . '" data-meta="' . $meta . '" value="' . $option_value . '" ' . $selected . '>' . $text . '</option>';
			}
			$html .= '</select>';

		}

		$html .= '
                </div>';

		return $html;
	}

	/**
	 * Output a user/author/creator dropdown for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $campaign_data Campaign data.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_user_dropdown_mini( $campaign_data = false, $label = false, $args = false ) {

		$defaults = array(
			'name'            => '',
			'id'              => '',
			'class'           => '',
			'container_class' => '',
			'default'         => false,
			'visibility'      => false,
			'options'         => false,
		);

		$params = array_replace_recursive( $defaults, $args );

		// get a default value.
		$value = ! empty( $campaign_data['settings']['general']['campaign_creator_id'] ) ? intval( $campaign_data['settings']['general']['campaign_creator_id'] ) : false;

		if ( is_array( $params['name'] ) ) {
			$toggle_value   = $params['name'][2];
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$field_id_attr  = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$field_id_attr .= isset( $toggle_value ) && '' !== $toggle_value ? ' data-ajax-label="' . $toggle_value . '"' : false;
		$selected_value = isset( $params['selected_value'] ) ? esc_html( $params['selected_value'] ) : $value;
		$default_value  = isset( $params['default'] ) && false !== $params['default'] ? esc_html( $params['default'] ) : false;

		$css_classes     = explode( ' ', $params['class'] );
		$css_classes     = array_unique( $css_classes );
		$params['class'] = implode( ' ', $css_classes );

		$css_container_classes     = isset( $params['container_class'] ) ? explode( ' ', $params['container_class'] ) : array();
		$css_container_classes     = array_unique( $css_container_classes );
		$params['container_class'] = implode( ' ', $css_container_classes );

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-dropdown ' . $params['container_class'] . '" ' . $field_id_attr . '>
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . '</label>';

		if ( false !== $params['options'] && is_array( $params['options'] ) && count( $params['options'] ) > 0 ) {

			$html .= '<select id="' . $this->id_slug . '-' . $params['id'] . '" name="' . $params['name'] . '">';
			foreach ( $params['options'] as $option_value => $user_data ) {

				$text     = esc_html( $user_data['text'] );
				$avatar   = ! empty( $user_data['avatar'] ) ? esc_url( $user_data['avatar'] ) : false;
				$meta     = ! empty( $user_data['meta'] ) ? ( $user_data['meta'] ) : false;
				$selected = ( isset( $selected_value ) && $value !== false && $option_value == $selected_value ) ? 'selected="selected"' : false;
				$selected = ( false === $selected && ( 0 === intval( $selected_value ) || false === $selected_value ) && false !== $default_value && $option_value == $default_value ) ? 'selected="selected"' : $selected;
				$html    .= '<option data-avatar="' . $avatar . '" data-meta="' . $meta . '" value="' . $option_value . '" ' . $selected . '>' . $text . '</option>';
			}
			$html .= '</select>';

		}

		$html .= '
                </div>';

		return $html;
	}

	/**
	 * Output a generic div for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_div( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'  => '',
			'id'    => '',
			'class' => '',
		);

		$params = array_replace_recursive( $defaults, $args );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_array( $params['name'] ) ) {
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-div">
                    <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . '</label>
                    <div id="' . $this->id_slug . '-' . $params['id'] . '" class="' . $params['class'] . '"></div>
                </div>';

		return $html;
	}

	/**
	 * Output a Charitable categories checkbox area for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 * @version 1.8.9.1
	 */
	public function generate_categories( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'    => '',
			'id'      => '',
			'class'   => '',
			'tooltip' => false,
		);

		$params = array_replace_recursive( $defaults, $args );

		$categories_terms = get_terms(
			array(
				'taxonomy'   => 'campaign_category',
				'hide_empty' => 0,
			)
		);

		$tooltip_html = ( isset( $params['tooltip'] ) && false !== $params['tooltip'] ) ? $this->get_tooltip_html( wp_strip_all_tags( $params['tooltip'] ) ) : false;

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-categories">
				 <label for="' . $this->id_slug . '-' . $params['id'] . '">' . $label . ' ' . $tooltip_html . '</label>';

		if ( empty( $categories_terms ) ) {

			$html .= '<p><em><small>There are currently no Charitable campaign categories. <a target="_blank" href="' . admin_url( 'edit-tags.php?taxonomy=campaign_category&post_type=campaign' ) . '">Click here</a> to start adding some or <a href="https://wpcharitable.com/documentation/charitable-tags-and-categories">read our documentation for more</a>.</small></em></p>';

		} else {

			$html .= '<ul class="charitable-category-list">';

			foreach ( $categories_terms as $category_term ) :

				if ( is_array( $params['name'] ) ) {
					// convert array( 'settings', $settings_tab_slug, 'name' ) to settings['tab_slug']['name'].
					$name = implode( '][', $params['name'] ) . ']';
					$name = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
					$name = substr( $name, 0, -1 ) . '-' . $category_term->term_id . ']';
				}

				// Get the value of this option.
				$checked = in_array( $category_term->term_id, $value ) ? 'checked="checked"' : false;

				$html .= '
                <li>
                <input type="checkbox" ' . $checked . ' value="' . $category_term->term_id . '" name="' . $name . '" id="' . $this->id_slug . '-category-' . $category_term->term_id . '" /><label for="' . $this->id_slug . '-category-' . $category_term->term_id . '">' . $category_term->name . '</label>
                </li>';

			endforeach;
			$html .= '</ul>';

		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Output a campaign creator/author for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 * @version 1.8.9.1
	 */
	public function generate_campaign_creator_info( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'       => '',
			'id'         => '',
			'class'      => '',
			'creator_id' => 0,
		);

		$params = array_replace_recursive( $defaults, $args );

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-categories">
        <label>' . $label . '</label>';

		ob_start();

		// make sure this is a number/ID.
		$params['creator_id'] = intval( $params['creator_id'] );

		if ( $params['creator_id'] ) :

			$creator = new Charitable_User( $params['creator_id'] );
			?>
			<div id="campaign-creator" class="charitable-campaign-creator-info-container">
				<div class="creator-avatar charitable-campaign-creator-avatar">
					<?php echo $creator->get_avatar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div><!--.creator-avatar-->
				<div class="charitable-campaign-creator-info">
					<?php if ( false === $creator ) : ?>

					<?php else : ?>

					<h3 class="creator-name"><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $creator->ID ) ); ?>"><?php printf( '%s (%s %d)', esc_html( $creator->display_name ), esc_html__( 'User ID', 'charitable' ), esc_html( $creator->ID ) ); ?></a></h3>
					<p class="joined-on"><?php printf( '%s <span>%s</span>', esc_html_x( 'Joined on', 'joined on date', 'charitable' ), esc_html( date_i18n( 'F Y', strtotime( $creator->user_registered ) ) ) ); ?></p>
					<ul>
						<li><a target="_blank" class="public-profile-link" href="<?php echo esc_url( get_author_posts_url( $creator->ID ) ); ?>"><?php esc_html_e( 'Public Profile', 'charitable' ); ?></a></li><li><a target="_blank" class="edit-profile-link" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $creator->ID ) ); ?>"><?php esc_html_e( 'Edit Profile', 'charitable' ); ?></a></li>
					</ul>

					<?php endif; ?>
				</div><!--.creator-facts-->
			</div><!--#campaign-creator-->
			<?php
		endif;

		$html .= ob_get_clean();

		$html .= '</div>';

		return $html;
	}

	/**
	 * Output a donation amounts area for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_donation_amounts( $value = false, $label = false, $args = false ) {

		$defaults = array(
			'name'                       => '',
			'id'                         => '',
			'class'                      => '',
			'type'                       => '', // for this field, this might be recurring... otherwise blank by default.
			'visibility'                 => false,
			'donation_description_label' => esc_html__( 'donation', 'charitable' ),
			'from'                       => false, // field if it's coming from preview field/block.
			'button_label'               => esc_html__( 'Add a Suggested Amount', 'charitable' ),
			'default'                    => false, // this is the index of the row id if there is a default value.
			'data_add_row'               => 'suggested-amount',
			'data_delete_row'            => 'suggested-amount',
		);

		$params = array_replace_recursive( $defaults, $args );

		$field_id_attr   = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$params['class'] = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-donation-amounts ' . $params['class'] . '" ' . $field_id_attr . '>';

		$donations_table_css = ! empty( $params['type'] ) ? esc_attr( $params['type'] ) . '-' : false;

		$html .= '<table id="' . $params['id'] . '" class="widefat charitable-campaign-suggested-' . $donations_table_css . 'donations-table charitable-campaign-suggested-donations">
		<thead>';

		// Only show this in the mean settings menu, not the settings for the block.
		if ( 'field' !== $params['from'] ) {

			$html .= '<tr class="table-header">
                        <th colspan="5"><label for="campaign_suggested_donations">' . $label . '</label></th>
                      </tr>';

		}

		$html .= '
			<tr>
                <th class="spacer"></th>
                <th class="default_amount-col">Default</th>
                <th class="amount-col">Amount</th>
                <th class="description-col">Description (optional)</th>
                <th class="remove-col"></th>
			</tr>
		</thead>';

		if ( is_array( $params['name'] ) ) {
			// convert array( 'settings', $settings_tab_slug, 'name' ) to settings['tab_slug']['name'].
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		// get a default value.
		$campaign_suggested_donations_default = isset( $params['default'] ) ? intval( $params['default'] ) : false;

		// get the donation description label.
		$donation_description_label = esc_html( $params['donation_description_label'] ) . '.';

		// check and see if this is a new campaign, and if so add some defaults.
		if ( false === $value && ! isset( $_GET['campaign_id'] ) ) { // phpcs:ignore

			$value = array(

				1 => array(
					'amount'      => 5,
					'description' => esc_html__( 'This is a small ', 'charitable' ) . $donation_description_label,
				),
				2 => array(
					'amount'      => 10,
					'description' => esc_html__( 'This is a slightly larger ', 'charitable' ) . $donation_description_label,
				),
				3 => array(
					'amount'      => 15,
					'description' => esc_html__( 'This is a larger (and default) ', 'charitable' ) . $donation_description_label,
				),
				4 => array(
					'amount'      => 20,
					'description' => esc_html__( 'This is a large ', 'charitable' ) . $donation_description_label,
				),
			);

			$campaign_suggested_donations_default = 3;

		}

		$html .= '<tbody class="ui-sortable">';

		if ( false === $value ) { // used to have || $index === 0.

			$html .= '<tr class="no-suggested-amounts ">
                      <td colspan="5" class="no-amounts-yet">No suggested amounts have been created yet.</td>
                      </tr>';

		}

		$default_field_name = ( 'recurring' === $params['type'] ) ? 'campaign_suggested_recurring_donations_default' : 'campaign_suggested_donations_default';

		$html .= '
            <tr data-index="0" class="to-copy hidden">
                        <td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle"></span></td>
                        <td class="default_amount-col"><input type="radio" class="campaign_suggested_donations" name="' . $name . '[' . $default_field_name . '][]" value="0">
                    </td>

                    <td class="amount-col"><input autocomplete="off" type="text" class="campaign_suggested_donations" name="' . $name . '[0][amount]" value="" placeholder="Amount">
                    </td>

                    <td class="description-col"><input type="text" class="campaign_suggested_donations" name="' . $name . '[0][description]" value="" placeholder="Optional Description">
                    </td>

                    <td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row" data-charitable-delete-row="' . $params['data_delete_row'] . '"></span></td>
            </tr>';

		if ( false === $value ) { // used to have || $index === 0.

			// do nothing.

		} elseif ( is_array( $value ) && ! empty( $value ) ) {

			foreach ( $value as $key_id => $donation_info ) {

				$amount      = isset( $donation_info['amount'] ) ? charitable_format_money( $donation_info['amount'] ) : false;
				$description = isset( $donation_info['description'] ) ? esc_html( $donation_info['description'] ) : false;
				$selected    = $key_id == $campaign_suggested_donations_default ? 'checked="checked"' : false;

				$html .= '
                    <tr data-index="' . $key_id . '" class="">
                        <td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle"></span></td>
                            <td class="default_amount-col"><input type="radio" class="campaign_suggested_donations" name="' . $name . '[' . $default_field_name . '][]" value="' . $key_id . '" ' . $selected . '>
                        </td>
                        <td class="amount-col"><input autocomplete="off" type="text" class="campaign_suggested_donations" name="' . $name . '[' . $key_id . '][amount]" value="' . $amount . '" placeholder="Amount">
                        </td>
                        <td class="description-col"><input type="text" class="campaign_suggested_donations" name="' . $name . '[' . $key_id . '][description]" value="' . $description . '" placeholder="Optional Description">
                        </td>
                        <td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>
                    </tr>';
			}

		}

		$html .= '</tbody>
            <tfoot>
                <tr>
                    <td colspan="5"><a class="charitable-button" href="#" data-charitable-add-row="' . $params['data_add_row'] . '">+ ' . $params['button_label'] . '</a> <a class="charitable-clear-defaults" href="javascript:void(0);">Clear Defaults</a></td>
                </tr>
            </tfoot>
        </table>
        </div>';

		return $html;
	}

	/**
	 * Output a donation amounts area for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $campaign_data Campaign data.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_donation_amounts_mini( $campaign_data = false, $label = false, $args = false ) {

		$defaults = array(
			'name'         => '',
			'id'           => '',
			'class'        => '',
			'visibility'   => false,
			'from'         => false, // field if it's coming from preview field/block.
			'button_label' => esc_html__( 'Add a Suggested Amount', 'charitable' ),
			'default'      => false, // this is the index of the row id if there is a default value.
		);

		$params = array_replace_recursive( $defaults, $args );

		$field_id_attr   = isset( $params['field_id'] ) && '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;
		$params['class'] = $this->maybe_add_visibility_classes( $params['class'], $params['visibility'] );

		$html = '<div id="' . $this->id_slug . '-' . $params['id'] . '-wrap" class="charitable-panel-field charitable-panel-field-donation-amounts-clone ' . $params['class'] . '" ' . $field_id_attr . '><label for="' . $this->id_slug . '-' . $params['id'] . '">Suggested Donation Amounts</label>';

		$html .= '<table id="' . $params['id'] . '" class="widefat charitable-campaign-suggested-donations-table charitable-campaign-suggested-donations-mini">
		<thead>';

		// Only show this in the mean settings menu, not the settings for the block.
		if ( 'field' !== $params['from'] ) {

			$html .= '<tr class="table-header">
                        <th colspan="5"><label for="campaign_suggested_donations">' . $label . '</label></th>
                      </tr>';

		}

		$html .= '
			<tr>
                <th class="spacer"></th>
                <th class="default_amount-col">Default</th>
                <th class="amount-col">Amount</th>
                <th class="description-col">Description (optional)</th>
                <th class="remove-col"></th>
			</tr>
		</thead>';

		if ( is_array( $params['name'] ) ) {
			// convert array( 'settings', $settings_tab_slug, 'name' ) to settings['tab_slug']['name'].
			$name           = implode( '][', $params['name'] ) . ']';
			$params['name'] = str_replace( $params['name'][0] . ']', $params['name'][0], $name );
		}

		// get a default value.
		$campaign_suggested_donations_default = ! empty( $campaign_data['settings']['donation-options']['suggested_donations_default'] ) ? intval( $campaign_data['settings']['donation-options']['suggested_donations_default'] ) : false;

		if ( ! empty( $campaign_data['settings']['donation-options']['donation_amounts'] ) ) {
			$value = $campaign_data['settings']['donation-options']['donation_amounts'];
		} else {
			$value = false;
		}

		// check and see if this is a new campaign, and if so add some defaults.
		if ( ( false === $campaign_data || empty( $campaign_data['settings']['donation-options'] ) ) && ! isset( $_GET['campaign_id'] ) ) { // phpcs:ignore

			$value = array(

				1 => array(
					'amount'      => 5,
					'description' => esc_html__( 'This is a small donation.', 'charitable' ),
				),
				2 => array(
					'amount'      => 10,
					'description' => esc_html__( 'This is a slightly larger donation.', 'charitable' ),
				),
				3 => array(
					'amount'      => 15,
					'description' => esc_html__( 'This is a larger (and default) donation.', 'charitable' ),
				),
				4 => array(
					'amount'      => 20,
					'description' => esc_html__( 'This is a large donation.', 'charitable' ),
				),
			);

			$campaign_suggested_donations_default = 3;

		}

		$index = is_array( $value ) ? count( $value ) : 0;

		$html .= '<tbody class="ui-sortable">';

		if ( false === $campaign_data || $index === 0 ) {

			$html .= '<tr class="no-suggested-amounts ">
                      <td colspan="5" class="no-amounts-yet">No suggested amounts have been created yet.</td>
                      </tr>';

		}

		$html .= '
            <tr data-index="0" class="to-copy hidden">
                        <td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle"></span></td>
                        <td class="default_amount-col"><input type="radio" class="campaign_suggested_donations" name="' . $name . '[campaign_suggested_donations_default][]" value="0">
                    </td>

                    <td class="amount-col"><input autocomplete="off" type="text" class="campaign_suggested_donations" name="' . $name . '[0][amount]" value="" placeholder="Amount">
                    </td>

                    <td class="description-col"><input type="text" class="campaign_suggested_donations" name="' . $name . '[0][description]" value="" placeholder="Optional Description">
                    </td>

                    <td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>
            </tr>';

		if ( is_array( $value ) && ! empty( $value ) ) {

			foreach ( $value as $key_id => $donation_info ) {

				$amount      = isset( $donation_info['amount'] ) ? charitable_format_money( $donation_info['amount'] ) : false;
				$description = isset( $donation_info['description'] ) ? esc_html( $donation_info['description'] ) : false;
				$selected    = $key_id === $campaign_suggested_donations_default ? 'checked="checked"' : false;

				$html .= '
                    <tr data-index="' . $key_id . '" class="">
                        <td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle"></span></td>
                            <td class="default_amount-col"><input type="radio" class="campaign_suggested_donations" name="' . $name . '[campaign_suggested_donations_default][]" value="' . $key_id . '" ' . $selected . '>
                        </td>
                        <td class="amount-col"><input autocomplete="off" type="text" class="campaign_suggested_donations" name="' . $name . '[' . $key_id . '][amount]" value="' . $amount . '" placeholder="Amount">
                        </td>
                        <td class="description-col"><input type="text" class="campaign_suggested_donations" name="' . $name . '[' . $key_id . '][description]" value="' . $description . '" placeholder="Optional Description">
                        </td>
                        <td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>
                    </tr>';
			}

		}

		$html .= '</tbody>
            <tfoot>
                <tr>
                    <td colspan="5"><a class="charitable-button" href="#" data-charitable-add-row="suggested-amount">+ ' . $params['button_label'] . '</a></td>
                </tr>
            </tfoot>
        </table>
        </div>';

		return $html;
	}

	/**
	 * Output a generic dividing bar for the form settings in the left sidebar.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_divider( $value = false, $label = false, $args = array() ) {

		$defaults = array(
			'name'       => '',
			'id'         => '',
			'class'      => '',
			'creator_id' => '',
		);

		$params = array_replace_recursive( $defaults, $args );

		$field_id_attr = '' !== $params['field_id'] ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;

		$html = '<hr class="charitable-panel-field charitable-panel-field-divider" ' . $field_id_attr . ' />';

		return $html;
	}

	/**
	 * Output a headline.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value Field value.
	 * @param string $label Feld label.
	 * @param array  $args  Field params.
	 *
	 * @return string
	 */
	public function generate_headline( $text = '', $args = array() ) {

		$defaults = array(
			'name'     => '',
			'id'       => '',
			'class'    => '',
			'field_id' => '',
		);

		$params = array_replace_recursive( $defaults, $args );

		$field_id_attr = '' !== trim( $params['field_id'] ) ? 'data-field-id="' . intval( $params['field_id'] ) . '"' : false;

		$html = '<h4 class="charitable-panel-field charitable-panel-field-headline ' . $params['class'] . '" ' . $field_id_attr . '>' . esc_html( $text ) . '</h4>';

		return $html;
	}

	/**
	 * Add visibility classes to the CSS classes array if the field is conditionally visible.
	 *
	 * @since 1.8.0
	 *
	 * @param array $css_classes The CSS classes array.
	 * @param array $params_visibility The visibility parameters.
	 *
	 * @return array The updated CSS classes array.
	 */
	public function maybe_add_visibility_classes( $css_classes, $params_visibility ) {

		if ( ! empty( $params_visibility ) ) {
			// if there's items in the area, assume it's hidden at start.
			$visible = false;

			// is there any conditions where the field could be visible upon load?
			if ( ! empty( $params_visibility['show'] ) ) {
				$visible_count = 0;

				// all conditions in this array need to be true for visible to be true.
				foreach ( $params_visibility['show'] as $element_value => $required_value ) {
					if ( $element_value === $required_value ) {

						++$visible_count;
					}
				}

				if ( $visible_count === count( $params_visibility['show'] ) ) {
					$visible = true;
				}

			}

			$css_classes .= ( false === $visible ) ? 'charitable-hidden' : '';

		}

		return $css_classes;
	}
}

new Charitable_Builder_Form_Fields();
