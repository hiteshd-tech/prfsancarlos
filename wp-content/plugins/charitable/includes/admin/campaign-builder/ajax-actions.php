<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax actions related to the campaign builder used in by admin.
 *
 * @package   Charitable
 * @since     1.8.0
 * @version   1.8.9.1
 */

/**
 * Get and return template data via ajax.
 *
 * @since 1.8.0
 */
function charitable_get_campaign_builder_template_data() {

	if ( empty( $_POST['id'] ) ) { // phpcs:ignore
		wp_send_json_error( esc_html__( 'Something went wrong while performing this action.', 'charitable' ) );
	}

	$builder_template = new Charitable_Campaign_Builder_Templates();
	$template_id      = ! empty( $_POST['id'] ) ? esc_attr( $_POST['id'] ) : false; // phpcs:ignore

	if ( ! $template_id ) {
		wp_send_json_error( esc_html__( 'Something went wrong while performing this action.', 'charitable' ) );
	}

	// get the title if passed so we can create a temp $campaign_data array to custom populare a template down the road.
	$campaign_title = ! empty( $_POST['title'] ) ? esc_html( $_POST['title'] ) : false; // phpcs:ignore
	$campaign_data  = array();
	if ( $campaign_title ) {
		$campaign_data['title'] = $campaign_title;
	}

	$no_cache = apply_filters( 'charitable_campaign_builder_ajax_template_no_cache', true );

	$template_data = $builder_template->get_template_data( $template_id, $campaign_data, $no_cache );

	if ( $template_data ) {

		wp_send_json_success( $template_data );

	} else {

		wp_send_json_error( esc_html__( 'Something went wrong while attempting to get template information.', 'charitable' ) );
	}
}
add_action( 'wp_ajax_charitable_get_campaign_builder_template_data', 'charitable_get_campaign_builder_template_data' );

/**
 * Get and return template preview via ajax.
 *
 * @since 1.8.0
 */
function charitable_get_campaign_builder_template_preview() {

	if ( empty( $_POST['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_send_json_error( esc_html__( 'Something went wrong while performing this action.', 'charitable' ) );
	}

	$template_id = intval( $_POST['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( 0 === $template_id ) {
		wp_send_json_error( esc_html__( 'Something went wrong with getting the template while performing this action.', 'charitable' ) );
	}

	$builder_template = new Charitable_Campaign_Builder_Templates();
	$template_preview = $builder_template->get_template_preview( $template_id );

	if ( $template_preview ) {

		$response = array( 'html' => $template_preview );

		wp_send_json_success( $response );

	} else {

		wp_send_json_error( esc_html__( 'Something went wrong while attempting to get the template information.', 'charitable' ) );

	}
}
add_action( 'wp_ajax_charitable_get_campaign_builder_template_preview', 'charitable_get_campaign_builder_template_preview' );


/**
 * Save a campaign via ajax.
 *
 * @since 1.8.0
 */
function charitable_save_campaign() {

	// Run a security check.
	if ( ! check_ajax_referer( 'charitable-builder', 'nonce', false ) ) {
		wp_send_json_error( esc_html__( 'Your session expired. Please reload the builder.', 'charitable' ) );
	}

	// Check for permissions.
	if ( ! charitable_current_user_can( 'edit_campaigns' ) ) {
		wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'charitable' ) );
	}

	// Check for form data.
	if ( empty( $_POST['data'] ) ) {
		wp_send_json_error( esc_html__( 'Something went wrong while performing this action.', 'charitable' ) );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data, decoded and sanitized in loop
	// $campaign_post = json_decode( wp_unslash( $_POST['data'] ), ARRAY_A );
	$campaign_post = array_column( json_decode( wp_unslash( $_POST['data'] ), ARRAY_A ), null, 'name' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data, decoded and sanitized in loop
	$is_preview    = isset( $_POST['preview'] ) && 'true' === $_POST['preview'] ? true : false;
	$data          = [
		'fields' => [],
	];

	$campaign_settings_v2 = array();

	// Note: We have to first get the layout from the template because the incoming data is only the fields the user has placed into the form.
	// If we don't do this, if the user doesn't put anything into a particular column or row, that won't be included in the POST and we'll lose that data.

	$campaign_settings_v2 = charitable_template_layout_to_campaign_layout( $campaign_settings_v2, $campaign_post );

	// this tracks where tabs might be in the layout.
	$campaign_tabs_location = array();

	if ( $campaign_post ) {

		foreach ( $campaign_post as $post_field_name => $post_field_data ) {

			// This process has to check for various formats of fields that appear in the form on the screen hence the multiple if statements.

			if ( strpos( $post_field_name, '__' ) !== false && strpos( $post_field_name, 'xxx' ) === false ) { // this covers fields that are like EXAMPLE__EXAMPLE__EXAMPLE.

				$array_level = explode( '__', $post_field_name );
				if ( count( $array_level ) === 4 ) {
					$campaign_settings_v2[ $array_level[0] ][ $array_level[1] ][ $array_level[2] ][ $array_level[3] ] = $post_field_data['value'];
				} elseif ( count( $array_level ) === 3 ) {
					$campaign_settings_v2[ $array_level[0] ][ $array_level[1] ][ $array_level[2] ] = $post_field_data['value'];
				} elseif ( count( $array_level ) === 2 ) {
					$campaign_settings_v2[ $array_level[0] ][ $array_level[1] ] = $post_field_data['value'];
				} elseif ( count( $array_level ) === 2 ) {
					$campaign_settings_v2[ $array_level[0] ] = $post_field_data['value'];
				}

			} elseif ( strpos( $post_field_name, '[donation_amounts]' ) !== false && strpos( $post_field_name, '[campaign_suggested_donations_default]' ) !== false ) {

				$campaign_settings_v2['settings']['donation-options']['suggested_donations_default'] = $post_field_data['value'];

			} elseif ( strpos( $post_field_name, 'donation_amounts' ) !== false && strpos( $post_field_name, '_fields' ) !== false ) {

				continue;

			} elseif ( strpos( $post_field_name, '[donation_amounts]' ) !== false && strpos( $post_field_name, '[campaign_suggested_donations_default]' ) === false ) {

				$post_field_name = str_replace( 'settings]', '', $post_field_name );
				$post_field_name = str_replace( '[donation-options]', '', $post_field_name );
				$post_field_name = str_replace( '[donation_amounts]', '', $post_field_name );

				$output     = charitable_convert_campaign_string_to_array( $post_field_name, $post_field_data['value'] );
				$_tmp_array = $output[ array_key_first( $output ) ];

				$first_key  = esc_attr( array_key_first( $output ) );
				$second_key = esc_attr( array_key_first( $_tmp_array ) );

				// this would avoid field 0 which should be a hidden field for the purpose of adding dynamic rows via Javascript.
				if ( 0 === intval( $first_key ) ) {
					continue;
				}

				$value = ( 'amount' === esc_attr( $second_key ) ) ? charitable_sanitize_amount( $post_field_data['value'] ) : wp_strip_all_tags( $post_field_data['value'] );

				// if ( intval( $first_key ) !== 0 ) { // this would avoid field 0 which should be a hidden field for the purpose of adding dynamic rows via Javascript.
					$campaign_settings_v2['settings']['donation-options']['donation_amounts'][ $first_key ][ $second_key ] = $value;
				// }

			} elseif ( strpos( $post_field_name, '[recurring_donation_amounts]' ) !== false && strpos( $post_field_name, '[campaign_suggested_recurring_donations_default]' ) !== false ) {

				$campaign_settings_v2['settings']['donation-options']['suggested_recurring_donations_default'] = $post_field_data['value'];

			} elseif ( strpos( $post_field_name, '[recurring_donation_amounts]' ) !== false && strpos( $post_field_name, '[campaign_suggested_recurring_donations_default]' ) === false ) {

				$post_field_name = str_replace( 'settings]', '', $post_field_name );
				$post_field_name = str_replace( '[donation-options]', '', $post_field_name );
				$post_field_name = str_replace( '[recurring_donation_amounts]', '', $post_field_name );

				$output     = charitable_convert_campaign_string_to_array( $post_field_name, $post_field_data['value'] );
				$_tmp_array = $output[ array_key_first( $output ) ];

				$first_key  = esc_attr( array_key_first( $output ) );
				$second_key = esc_attr( array_key_first( $_tmp_array ) );

				// this would avoid field 0 which should be a hidden field for the purpose of adding dynamic rows via Javascript.
				if ( 0 === intval( $first_key ) ) {
					continue;
				}

				$value = ( 'amount' === esc_attr( $second_key ) ) ? charitable_sanitize_amount( $post_field_data['value'] ) : wp_strip_all_tags( $post_field_data['value'] );

				// if ( intval( $first_key ) !== 0 ) { // this would avoid field 0 which should be a hidden field for the purpose of adding dynamic rows via Javascript.
					$campaign_settings_v2['settings']['donation-options']['recurring_donation_amounts'][ $first_key ][ $second_key ] = $value;
				// }

			} elseif ( strpos( $post_field_name, '[recurring_donation_periods_to_display]' ) !== false ) {

				$post_field_name = str_replace( 'settings[', '', $post_field_name );
				$post_field_name = str_replace( 'donation-options]', '', $post_field_name );
				$post_field_name = str_replace( '[recurring_donation_periods_to_display]', '', $post_field_name );

				$output = charitable_convert_campaign_string_to_array_new( $post_field_name, $post_field_data['value'] );

				$first_key  = esc_attr( array_key_first( $output ) );
				$value = wp_strip_all_tags( $post_field_data['value'] );

				$campaign_settings_v2['settings']['donation-options']['recurring_donation_periods_to_display'][ $first_key ] = $value;

			} elseif ( strpos( $post_field_name, '[row]' ) !== false && strpos( $post_field_name, '[column]' ) !== false && strpos( $post_field_name, '[row-type-tabs]' ) === false ) {

				$post_field_name = str_replace( 'layout[row]', '', $post_field_name );
				$post_field_name = str_replace( '[column]', '', $post_field_name );
				$post_field_name = str_replace( '[section]', '', $post_field_name );
				$post_field_name = str_replace( '[fields]', '', $post_field_name );

				$output = charitable_convert_campaign_string_to_array_new( $post_field_name, $post_field_data['value'], 'photo' );

				$row_type     = array_key_first( $output );
				$row_id       = array_key_first( $output[ $row_type ] );
				$row_css      = array_key_first( $output[ $row_type ][ $row_id ] );
				$column_id    = array_key_first( $output[ $row_type ][ $row_id ][ $row_css ] );
				$section_type = array_key_first( $output[ $row_type ][ $row_id ][ $row_css ][ $column_id ] );
				$section_id   = array_key_first( $output[ $row_type ][ $row_id ][ $row_css ][ $column_id ][ $section_type ] );

				$row_type     = str_replace( 'row-type-', '', $row_type );
				$section_type = str_replace( 'section-type-', '', $section_type );

				if ( 'row' === $row_type || 'header' === $row_type ) {

					$campaign_settings_v2['layout']['rows'][ $row_id ]['type'] = ( false !== $row_type ) ? esc_attr( $row_type ) : 'row';

					if ( 'tabs' === $section_type ) {

						$tab_id       = array_key_first( $output[ 'row-type-' . $row_type ][ $row_id ][ $row_css ][ $column_id ][ 'section-type-' . $section_type ][ $section_id ]['tabs'] );
						$field_id_key = array_key_first( $output[ 'row-type-' . $row_type ][ $row_id ][ $row_css ][ $column_id ][ 'section-type-' . $section_type ][ $section_id ]['tabs'][ $tab_id ] );

						$campaign_settings_v2['layout']['rows'][ $row_id ]['type']      = $row_type;
						$campaign_settings_v2['layout']['rows'][ $row_id ]['css_class'] = esc_attr( $row_css );
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['type']                        = 'tabs';
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['tabs'][ $tab_id ]['title']    = $campaign_post[ 'tabs__' . $tab_id . '__title' ]['value'];
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['tabs'][ $tab_id ]['type']     = 'fields';
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['tabs'][ $tab_id ]['slug']     = '';
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['tabs'][ $tab_id ]['fields'][] = $field_id_key;

						$campaign_tabs_location = array(
							'row_id'     => $row_id,
							'column_id'  => $column_id,
							'section_id' => $section_id,
						);

					} elseif ( 'fields' === $section_type ) {

						$field_id_key = array_key_first( $output[ 'row-type-' . $row_type ][ $row_id ][ $row_css ][ $column_id ][ 'section-type-' . $section_type ][ $section_id ] );

						$campaign_settings_v2['layout']['rows'][ $row_id ]['type']      = $row_type;
						$campaign_settings_v2['layout']['rows'][ $row_id ]['css_class'] = esc_attr( $row_css );
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['type']     = 'fields';
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['fields'][] = $field_id_key;

					}

					$campaign_settings_v2['layout']['rows'][ $row_id ]['fields'][ $field_id_key ] = $post_field_data['value'];

				}

			} elseif ( strpos( $post_field_name, '[row]' ) !== false && strpos( $post_field_name, '[blocks]' ) !== false && strpos( $post_field_name, '[row-type-tabs]' ) !== false ) {

				$post_field_name = str_replace( 'layout[row][row-type-tabs][999]', '', $post_field_name );
				$post_field_name = str_replace( '[blocks]', '', $post_field_name );

				$output = charitable_convert_campaign_string_to_array( $post_field_name, $post_field_data['value'] );

				$_tmp_array = $output[ array_key_first( $output ) ];

				$tab_key   = array_key_first( $output );
				$field_key = array_key_first( $_tmp_array );

				$campaign_settings_v2['layout']['rows'][999]['type']                 = 'tabs';
				$campaign_settings_v2['layout']['rows'][999]['fields'][ $field_key ] = $post_field_data['value'];
				$campaign_settings_v2['layout']['rows'][999]['tabs'][ $tab_key ][]   = $field_key;

			} elseif ( strpos( $post_field_name, '_fields' ) !== false ) {

				$post_field_name = str_replace( '_fields', '', $post_field_name );

				$output = charitable_convert_campaign_string_to_array( $post_field_name, $post_field_data['value'] );

				$_tmp_array   = $output[ array_key_first( $output ) ];
				$_tmp_array_2 = is_array( $_tmp_array ) ? $_tmp_array[ array_key_first( $_tmp_array ) ] : false;

				$first_key  = array_key_first( $output );
				$second_key = array_key_first( $_tmp_array );
				$third_key  = $_tmp_array_2 !== false && is_array( $_tmp_array_2 ) && '' !== array_key_first( $_tmp_array_2 ) ? array_key_first( $_tmp_array_2 ) : false;

				// Santitize here.
				// todo: this needs to be cleaned up.
				if ( in_array( $second_key, array( 'css_class' ), true ) ) {
					// Remove all characters from this string that would not make it a valid css value or attribute.
					$post_field_data['value'] = str_replace( array( '"', "'", '`' ), '', $post_field_data['value'] );
					$post_field_data['value'] = preg_replace( '/[^a-zA-Z0-9_,\/$:\-\.!\s;]/', '', $post_field_data['value'] );
					$post_field_data['value'] = esc_attr( $post_field_data['value'] );
				}

				if ( null !== $third_key && false !== $third_key && '' !== trim( $third_key ) ) {
					$campaign_settings_v2['fields'][ $first_key ][ $second_key ][ $third_key ] = $post_field_data['value'];
				} else {
					$campaign_settings_v2['fields'][ $first_key ][ $second_key ] = $post_field_data['value'];
				}

			} elseif ( strpos( $post_field_name, '][' ) !== false ) { // this covers EXAMPLE[EXAMPLE][EXAMPLE].

				$output  = array();
				$pointer = &$output;
				$input   = $post_field_name;
				$counter = ( substr_count( $input, '[' ) ) - 1;
				while ( ( $index = strpos( $input, '[' ) ) !== false ) {
					if ( $index != 0 ) {
						$key             = substr( $input, 0, $index );
						$pointer[ $key ] = array();
						$pointer         = &$pointer[ $key ];
						$input           = substr( $input, $index );
						continue;
					}
					$end_index = strpos( $input, ']' );
					$array_key = substr( $input, $index + 1, $end_index - 1 );
					if ( $counter <= 1 ) {
						$pointer[ $array_key ] = array();
					} elseif ( is_array( $pointer ) ) {
							$pointer[ $array_key ] = isset( $post_field_data['value'] ) ? $post_field_data['value'] : false;
					}
					if ( is_array( $pointer ) ) {
						$pointer = &$pointer[ $array_key ];
					}
					$input = substr( $input, $end_index + 1 );
					++$counter;
				}

				$campaign_settings_v2 = array_merge_recursive( $campaign_settings_v2, $output );

			} elseif ( strpos( $post_field_name, 'xxx' ) === false ) {

				$campaign_settings_v2[ $post_field_name ] = $post_field_data['value'];

			}

		}
	}

	if ( empty( $campaign_tabs_location ) ) {
		// either there is NO tabs in this template, or there's at least one tab but has no fields in it. Let's got hunting for tabs.
		foreach ( $campaign_settings_v2['layout']['rows'] as $row_id => $row ) {
			foreach ( $row['columns'] as $column_id => $column ) {
				foreach ( $column['sections'] as $section_id => $section ) {
					if ( 'tabs' === $section['type'] ) {
						$campaign_tabs_location = array(
							'row_id'     => $row_id,
							'column_id'  => $column_id,
							'section_id' => $section_id,
						);
						break;
					}
				}
			}
		}
	}

	// We still check the tabs location because the above might not have found any tabs (likely because on purpose the template doesn't have any).
	if ( ! empty( $campaign_tabs_location ) ) {
		// get the list of current tabs in the layout from what we remembered/recorded from the location when we when through things above.
		$campaign_tabs = $campaign_settings_v2['layout']['rows'][ $campaign_tabs_location['row_id'] ]['columns'][ $campaign_tabs_location['column_id'] ]['sections'][ $campaign_tabs_location['section_id'] ]['tabs'];
		if ( ! empty( $campaign_tabs ) && ! empty( $campaign_settings_v2['tabs'] ) ) {
			foreach ( $campaign_settings_v2['tabs'] as $settings_campaign_tab_id => $settings_campaign_tab ) {
				// if there is no tab in the tabs field in a section, but there's a tab registered in the "settings" tab area, then add it to the layout (likely an empty tab?).
				if ( ! isset( $campaign_tabs[ $settings_campaign_tab_id ] ) || empty( $campaign_tabs[ $settings_campaign_tab_id ] ) ) {
					// tab section - ensure even empty tabs are included.
					$campaign_settings_v2['layout']['rows'][ $campaign_tabs_location['row_id'] ]['columns'][ $campaign_tabs_location['column_id'] ]['sections'][ $campaign_tabs_location['section_id'] ]['tabs'][ $settings_campaign_tab_id ] = array(
						'title'  => $settings_campaign_tab['title'],
						'type'   => 'fields',
						'slug'   => '',
						'fields' => array(),
					);
				}
			}
		}
	}

	// Remove any tabs in the layout tab section that aren't in the main tabs directory.
	if ( ! empty( $campaign_tabs_location ) ) {
		$campaign_tabs = $campaign_settings_v2['layout']['rows'][ $campaign_tabs_location['row_id'] ]['columns'][ $campaign_tabs_location['column_id'] ]['sections'][ $campaign_tabs_location['section_id'] ]['tabs'];
		foreach ( $campaign_tabs as $section_tab_id => $campaign_tab ) {
			// if there is a tab in the tabs field in a section, but there's no tab registered in the "settings" tab area, then remove it from the layout.
			if ( ! isset( $campaign_settings_v2['tabs'][ $section_tab_id ] ) || empty( $campaign_settings_v2['tabs'][ $section_tab_id ] ) ) {
				unset( $campaign_tabs[ $section_tab_id ] );
			}
		}
		$campaign_settings_v2['layout']['rows'][ $campaign_tabs_location['row_id'] ]['columns'][ $campaign_tabs_location['column_id'] ]['sections'][ $campaign_tabs_location['section_id'] ]['tabs'] = $campaign_tabs;
	}

	// If there are tabs in the section/layout but the tab directory is empty, we need to clear out the section/layout tabs.
	if ( ! empty( $campaign_tabs_location ) && empty( $campaign_settings_v2['tabs'] ) ) {
		$campaign_settings_v2['layout']['rows'][ $campaign_tabs_location['row_id'] ]['columns'][ $campaign_tabs_location['column_id'] ]['sections'][ $campaign_tabs_location['section_id'] ]['tabs'] = array();
	}

	// tags.
	$custom_tax_terms = array();
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data, decoded and sanitized in loop
	if ( ! empty( $_POST['data'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data, decoded and sanitized in loop
		$_tmp_data = json_decode( wp_unslash( $_POST['data'] ) );
		foreach ( $_tmp_data as $index => $object ) {
			if ( $object->name === 'settings[general][tags]' ) {
				$custom_tax_terms[] = intval( $object->value );
			}
		}
		$campaign_settings_v2['settings']['general']['tags'] = $custom_tax_terms;
	}

	// categories.
	$custom_cat_terms = array();
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data, decoded and sanitized in loop
	if ( ! empty( $_POST['data'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data, decoded and sanitized in loop
		$_tmp_data = json_decode( wp_unslash( $_POST['data'] ) );
		foreach ( $_tmp_data as $index => $object ) {
			if ( strpos( $object->name, 'categories-' ) !== false ) {
				$custom_cat_terms[] = intval( $object->value );
			}
		}
	}

	// Starting cleaning up.

	// Remove any donation option that doesn't have an amount.
	if ( isset( $campaign_settings_v2['settings']['donation-options']['donation_amounts'] ) ) {
		foreach ( $campaign_settings_v2['settings']['donation-options']['donation_amounts'] as $key => $info ) {
			if ( isset( $info['amount'] ) && 0 === intval( $info['amount'] ) ) {
				unset( $campaign_settings_v2['settings']['donation-options']['donation_amounts'][ $key ] );
			}
		}
	}

	// Remove any fields that are associated with feedback forms.
	foreach ( $campaign_settings_v2 as $key => $info ) {
		if ( strpos( $key, '-feedback-form' ) !== false ) {
			unset( $campaign_settings_v2[ $key ] );
		}
	}

	// convert date formats.
	if ( isset( $campaign_settings_v2['settings']['general']['end_date'] ) ) {
		$campaign_settings_v2['settings']['general']['end_date'] = Charitable_Campaign::sanitize_campaign_end_date( $campaign_settings_v2['settings']['general']['end_date'] );
	}

	// Sanitize some misc fields.
	$campaign_settings_v2['settings']['general']['goal'] = ! empty( $campaign_settings_v2['settings']['general']['goal'] ) ? Charitable_Currency::get_instance()->sanitize_monetary_amount( (string) $campaign_settings_v2['settings']['general']['goal'] ) : false;
	$campaign_settings_v2['settings']['general']['goal'] = '0' == $campaign_settings_v2['settings']['general']['goal'] ? '' : $campaign_settings_v2['settings']['general']['goal'];

	// if the money values are negative, make it positive.
	if ( ! empty( $campaign_settings_v2['settings']['general']['goal'] ) && '-' === substr( $campaign_settings_v2['settings']['general']['goal'], 0, 1 ) ) {
		$campaign_settings_v2['settings']['general']['goal'] = substr( $campaign_settings_v2['settings']['general']['goal'], 1 );
	}
	if ( ! empty( $campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'] ) && '-' === substr( $campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'], 0, 1 ) ) {
		$campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'] = substr( $campaign_settings_v2['settings']['donation-options']['minimum_donation_amount'], 1 );
	}

	$campaign_settings_v2['field_id'] = charitable_find_highest_field_key( $campaign_settings_v2 );

	// Update OLD v1 campaign data.
	if ( intval( $campaign_post['id']['value'] ) === 0 ) {
		// new campaign.
		$campaign_id = charitable_create_campaign(
			array(
				'title'       => isset( $campaign_post['title']['value'] ) ? $campaign_post['title']['value'] : false,
				'description' => isset( $campaign_post['setings_desc']['value'] ) ? $campaign_post['setings_desc']['value'] : false,
			)
		);
	} else {
		// existing campaign.
		$campaign_id = charitable_create_campaign(
			array(
				'ID'          => isset( $campaign_post['id']['value'] ) ? $campaign_post['id']['value'] : false,
				'title'       => isset( $campaign_post['title']['value'] ) ? $campaign_post['title']['value'] : false,
				'description' => isset( $campaign_post['setings_desc'] ) ? $campaign_post['setings_desc']['value'] : false,
			)
		);
	}

	// Update campaign data for v1.
	$processor              = new Charitable_Campaign_Processor();
	$processor->campaign_id = $campaign_id;

	// Base meta values.
	$base_meta_keys = array(
		'goal'                        => 0,
		'end_date'                    => 0,
		'suggested_donations'         => array(),
		'suggested_donations_default' => 0,
		'allow_custom_donations'      => 0,
		'minimum_donation_amount'     => 0,
		'donate_button_text'          => '',
	); // taken from class-charitable-campaign-processor.php.
	foreach ( $base_meta_keys as $base_meta_key => $base_meta_value ) {
		$value = charitable_search_settings( $campaign_settings_v2['settings'], $base_meta_key );
		if ( $value ) {
			$processor->save_meta_field( $value, $base_meta_key );
		} else {
			$processor->save_meta_field( $base_meta_value, $base_meta_key );
		}
	}

	if ( ! empty( $campaign_settings_v2['settings']['donation-options']['donation_amounts'] ) ) {
		update_post_meta( $campaign_id, '_campaign_suggested_donations', ( $campaign_settings_v2['settings']['donation-options']['donation_amounts'] ) );
	} else {
		update_post_meta( $campaign_id, '_campaign_suggested_donations', ( array() ) );
	}

	if ( ! empty( $campaign_settings_v2['settings']['donation-options']['suggested_donations_default'] ) ) {
		$suggest_donation_row_id = intval( $campaign_settings_v2['settings']['donation-options']['suggested_donations_default'] );
		$array_to_serialize      = array( html_entity_decode( charitable_format_money( $campaign_settings_v2['settings']['donation-options']['donation_amounts'][ $suggest_donation_row_id ]['amount'] ) ) );
		update_post_meta( $campaign_id, '_campaign_suggested_donations_default', $array_to_serialize );
	} else {
		update_post_meta( $campaign_id, '_campaign_suggested_donations_default', ( serialize( array() ) ) );
	}

	/* descriptions */
	if ( ! empty( $campaign_settings_v2['settings']['general']['description'] ) ) {
		update_post_meta( $campaign_id, '_campaign_description', trim( $campaign_settings_v2['settings']['general']['description'] ) ); // todo: santitize this.
	} else {
		update_post_meta( $campaign_id, '_campaign_description', false );
	}
	if ( ! empty( $campaign_settings_v2['settings']['general']['extended_description'] ) ) {
		$campaign_post_update = array(
			'ID'           => $campaign_id,
			'post_content' => $campaign_settings_v2['settings']['general']['extended_description'],
		);
	} elseif ( ! empty( $campaign_settings_v2['settings']['general']['description'] ) ) {
		$campaign_post_update = array(
			'ID'           => $campaign_id,
			'post_content' => $campaign_settings_v2['settings']['general']['description'],
		);
	} else {
		$campaign_post_update = array(
			'ID'           => $campaign_id,
			'post_content' => '',
		);
	}

	/* update campaign author which should be the campaign_creator_id, if it exists (otherwise leave it alone) */
	if ( ! empty( $campaign_settings_v2['settings']['general']['campaign_creator_id'] ) ) {
		$campaign_post_update['post_author'] = intval( $campaign_settings_v2['settings']['general']['campaign_creator_id'] );
	}

	/* tags */
	wp_set_post_terms( $campaign_id, $custom_tax_terms, 'campaign_tag' );

	/* categories */
	wp_set_post_terms( $campaign_id, $custom_cat_terms, 'campaign_category' );

	// Update the post status of the campaign.
	$post_status = false;
	// In the next statement, false means simply nothing was passed and if that happens we don't change anything.
	$desired_post_status = isset( $_POST['status'] ) ? esc_attr( $_POST['status'] ) : false; // phpcs:ignore

	if ( $desired_post_status ) {

		switch ( $desired_post_status ) {
			case 'draft':
			case 'pending':
			case 'publish':
			case 'private':
			case 'future':
				$post_status = $desired_post_status;
				break;

			case 'switch-draft':
				$post_status = 'draft';
				break;

			case 'switch-review':
				$post_status = 'pending';
				break;

			default:
				break;
		}

		if ( $post_status ) {
			$campaign_post_update['post_status'] = apply_filters( 'charitable_campaign_builder_post_status_on_save', $post_status, $campaign_id );
		}

		$campaign_settings_v2['post_status']       = $post_status;
		$campaign_settings_v2['post_status_label'] = ucfirst( $post_status );

	}

	// IF this isn't a preview request, update the post into the database.
	if ( ! $is_preview ) :
		wp_update_post( $campaign_post_update );
	endif;

	/* donation summary */
	$summary_hide_keys = array(
		'campaign_hide_amount_donated',
		'campaign_hide_number_of_donors',
		'campaign_hide_percent_raised',
		'campaign_hide_time_remaining',
	);

	if ( ! empty( $campaign_settings_v2['settings']['campaign-summary'] ) ) {
		foreach ( $summary_hide_keys as $meta_key ) {
			if ( ! empty( $campaign_settings_v2['settings']['campaign-summary'][ $meta_key ] ) ) {
				update_post_meta( $campaign_id, '_' . $meta_key, array( str_replace( 'campaign_', '', $meta_key ) ) );
			} else {
				update_post_meta( $campaign_id, '_' . $meta_key, '' );
			}
		}
	} else {
		foreach ( $summary_hide_keys as $meta_key ) {
			update_post_meta( $campaign_id, '_' . $meta_key, '' );
		}
	}

	if ( ! empty( $campaign_settings_v2['settings']['campaign-summary']['donation_button_text'] ) ) {
		update_post_meta( $campaign_id, '_campaign_donate_button_text', trim( wp_strip_all_tags( $campaign_settings_v2['settings']['campaign-summary']['donation_button_text'] ) ) );
	} else {
		update_post_meta( $campaign_id, '_campaign_donate_button_text', false );
	}

	if ( $campaign_id && 0 === intval( $campaign_settings_v2['id'] ) ) {
		$campaign_settings_v2['id'] = intval( $campaign_id );
	}

	// Adjust campaign creator ID if applicable.
	if ( isset( $campaign_settings_v2['settings']['campaign-creator']['campaign_creator_id'] ) ) {
		$creator_id = intval( $campaign_settings_v2['settings']['campaign-creator']['campaign_creator_id'] );
		if ( 0 !== $creator_id ) { // user id must exist in the posted data.
			$user = get_userdata( $creator_id ); // user must exist.
			if ( $user !== false ) {
				$arg = array(
					'ID'          => $campaign_id,
					'post_author' => $creator_id,
				);
				wp_update_post( $arg );
			}
		}

	}

	if ( isset( $campaign_settings_v2['tabs'] ) && is_array( $campaign_settings_v2['tabs'] ) ) :
		$campaign_settings_v2['tab_order'] = array_keys( $campaign_settings_v2['tabs'] );
	endif;

	if ( isset( $campaign_settings_v2['layout']['_header']['fields'] ) && is_array( $campaign_settings_v2['layout']['_header']['fields'] ) ) :
		$campaign_settings_v2['layout']['_header']['order'] = array_keys( $campaign_settings_v2['layout']['_header']['fields'] );
	endif;
	if ( isset( $campaign_settings_v2['layout']['_tabs'] ) && is_array( $campaign_settings_v2['layout']['_tabs'] ) ) :
		foreach ( $campaign_settings_v2['layout']['_tabs'] as $tab_id => $tab_info ) :
			$campaign_settings_v2['layout']['_tabs'][ $tab_id ]['order'] = array_keys( $campaign_settings_v2['layout']['_tabs'][ $tab_id ]['fields'] );
		endforeach;
	endif;


	// todo: remove this at some point - this was likely just because of testing.
	unset( $campaign_settings_v2['settings]'] );

	// Allow addons to update, sync, or override settings before they are saved.
	$campaign_settings_v2 = apply_filters( 'charitable_campaign_builder_save_campaign_settings', $campaign_settings_v2, $campaign_id );

	// Save the settings as post metadata.
	if ( $is_preview ) {
		// If this is a preview request, save the settings as a transient ONLY.
		set_transient( 'charitable_campaign_preview_' . $campaign_id, $campaign_settings_v2, DAY_IN_SECONDS );
	} else {
		// IF this isn't a preview request, update the post into the database and update the transient (the preview at this point should match the real thing).
		set_transient( 'charitable_campaign_preview_' . $campaign_id, $campaign_settings_v2, DAY_IN_SECONDS );
		update_post_meta( $campaign_id, 'campaign_settings_v2', $campaign_settings_v2 );
	}

	/**
	 * Fires after updating campaign data.
	 *
	 * @since 1.8.0
	 *
	 * @param int   $campaign_id campaign ID.
	 * @param array $data    campaign data.
	 */
	do_action( 'charitable_builder_save_campaign', $campaign_id, $campaign_settings_v2, $campaign_post, $is_preview );

	if ( ! $campaign_id ) {
		wp_send_json_error( esc_html__( 'Something went wrong while saving the campaign.', 'charitable' ) );
	}

	// Response data, start with the campaign settings.
	$response_data                = $campaign_settings_v2;
	$response_data['redirect']    = admin_url( 'admin.php?page=charitable-overview' );
	$response_data['campaign_id'] = $campaign_id;
	$response_data['preview_url'] = charitable_get_campaign_preview_url( $campaign_id, true ); // get_preview_post_link( $campaign_id );
	$response_data['permalink']   = get_permalink( $campaign_id );

	/**
	 * Allows filtering ajax response data after form was saved.
	 *
	 * @since 1.8.0
	 *
	 * @param array $response_data The data to be sent in the response.
	 * @param int   $campaign_id   Campaign ID.
	 * @param array $data          Campaign data.
	 */
	$response_data = apply_filters(
		'charitable_builder_save_campaign_response_data',
		$response_data,
		$campaign_id,
		$data
	);

	wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_charitable_save_campaign', 'charitable_save_campaign' );

/**
 * Converts the template layout to campaign layout.
 *
 * @param array $campaign_settings_v2 The campaign settings.
 * @param array $campaign_post         The campaign post data.
 *
 * @return array The updated campaign settings.
 */
function charitable_template_layout_to_campaign_layout( $campaign_settings_v2, $campaign_post ) {

	$builder_template = new Charitable_Campaign_Builder_Templates();
	$template_id      = ! empty( $campaign_post['template_id']['value'] ) ? esc_attr( $campaign_post['template_id']['value'] ) : false;
	$template_data    = ( false !== $template_id ) ? $builder_template->get_template_data( $template_id ) : array();

	$campaign_settings_v2['layout']['rows'] = array();

	$row_id     = 0;
	$column_id  = 0;
	$section_id = 0;

	foreach ( $template_data['layout'] as $row_id => $row ) :

		$campaign_settings_v2['layout']['rows'][ $row_id ]['type']      = ! empty( $row['type'] ) ? $row['type'] : 'row';
		$campaign_settings_v2['layout']['rows'][ $row_id ]['css_class'] = ! empty( $row['css_class'] ) ? $row['css_class'] : false;

		foreach ( $row['columns'] as $column_key => $column ) :

			foreach ( $column as $section_key => $section ) :

				$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'] = array();

				$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['type'] = esc_attr( $section['type'] );

				switch ( $section['type'] ) {
					case 'fields':
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['fields'] = array(); // [0] => 1 /// (array) $section['fields'];

						break;
					case 'tabs':
						$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ]['tabs'] = array(); // [0] => 1 /// (array) $section['fields'];

						if ( ! empty( $section['tabs'] ) ) {
							foreach ( $section['tabs'] as $tab_id => $tab_info ) :
								$campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ][ $section['type'] ][ $tab_id ] = array(
									'title'  => ! empty( $campaign_post[ 'tabs__' . $tab_id . '__title' ]['value'] ) ? sanitize_text_field( $campaign_post[ 'tabs__' . $tab_id . '__title' ]['value'] ) : $tab_info['title'],
									'type'   => $tab_info['type'],
									'slug'   => sanitize_title( $tab_info['slug'] ),
									'fields' => array(),
								);
							endforeach;
						}

						break;
					default:
						// $campaign_settings_v2['layout']['rows'][ $row_id ]['columns'][ $column_id ]['sections'][ $section_id ][ $section['type'] ] = $section[ $section['type'] ];
						break;
				}

				++$column_id;
				++$section_id;

			endforeach;

				// $column_id++;

		endforeach;

		$campaign_settings_v2['layout']['rows'][ $row_id ]['fields'] = array();

		++$row_id;

	endforeach;

	return $campaign_settings_v2;
}

/**
 * Searches for a key in a multidimensional array.
 *
 * @param array $array The array to search.
 * @param mixed $key The key to search for.
 * @return mixed|null The value of the key if found, null otherwise.
 */
function charitable_search_settings( $array, $key ) {
	// If the key exists in the current level of the array, return its value.
	if ( array_key_exists( $key, $array ) ) {
		return $array[ $key ];
	}

	// Iterate over each element in the array.
	foreach ( $array as $value ) {
		// If the element is an array, recursively search it for the key.
		if ( is_array( $value ) ) {
			$result = charitable_search_settings( $value, $key );

			// If the key is found in the nested array, return its value.
			if ( $result !== null ) {
				return $result;
			}
		}
	}

	// The key was not found in the array.
	return null;
}


/**
 * Find the highest field ID field so we can keep the max number accurate.
 *
 * @since 1.8.0
 *
 * @param array $array Settings data.
 */
function charitable_find_highest_field_key( $array ) {
	$highest_key = null;

	// Iterate through each element in the array.
	foreach ( $array as $key => $value ) {
		// Check if the current element is an array.
		if ( is_array( $value ) ) {
			// Recursively call the function to search through nested arrays.
			$field_key = charitable_find_highest_field_key( $value );

			// Check if the key is numeric and greater than the current highest key.
			if ( is_numeric( $field_key ) && ( $highest_key === null || $field_key > $highest_key ) ) {
				$highest_key = $field_key;
			}
		} else {
			// Check if the key is numeric and greater than the current highest key.
			if ( is_numeric( $key ) && ( $highest_key === null || $key > $highest_key ) ) {
				$highest_key = $key;
			}
		}
	}

	return $highest_key;
}

/**
 * A very particular util array for the builder.
 *
 * @since 1.8.0
 *
 * @param string $string The string from the settings data.
 * @param string $value  Value.
 * @param string $end_key End key.
 */
function charitable_convert_campaign_string_to_array( $string, $value, $end_key = 'amount' ) {

	$keys    = explode( '][', trim( $string, '[]' ) ); // split the string into an array of keys.
	$array   = [];
	$current = &$array;
	foreach ( $keys as $key ) {
		$current[ $key ] = [];
		if ( $key === $end_key ) {
			$current[ $key ] = $value; // $value;
		}
		$current = &$current[ $key ];
	}

	return $array;
}

/**
 * Convert a campaign string to an array.
 *
 * @param string $input The input string to convert.
 * @param mixed  $value The default value to use for each key.
 * @param string $end_key The key to use for the final value.
 * @return array The resulting array.
 */
function charitable_convert_campaign_string_to_array_new( $input, $value, $end_key = 'amount' ) { // phpcs:ignore

	$output  = array();
	$pointer = &$output;

	while ( ( $index = strpos( $input, '[' ) ) !== false ) {
		if ( $index != 0 ) {
			$key             = substr( $input, 0, $index );
			$pointer[ $key ] = array();
			$pointer         = &$pointer[ $key ];
			$input           = substr( $input, $index );
			continue;
		}
		$end_index             = strpos( $input, ']' );
		$array_key             = substr( $input, $index + 1, $end_index - 1 );
		$pointer[ $array_key ] = array();
		$pointer               = &$pointer[ $array_key ];
		$input                 = substr( $input, $end_index + 1 );
	}

	return( $output );
}

/**
 * Create a new form.
 *
 * @since 1.8.0
 */
function charitable_new_campaign() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

	check_ajax_referer( 'charitable-builder', 'nonce' );

	if ( empty( $_POST['title'] ) ) {
		wp_send_json_error(
			[
				'error_type' => 'missing_campaign_title',
				'message'    => esc_html__( 'No form name provided.', 'charitable' ),
			]
		);
	}

	$campaign_title    = sanitize_text_field( wp_unslash( $_POST['title'] ) );
	$campaign_template = empty( $_POST['template'] ) ? 'blank' : sanitize_text_field( wp_unslash( $_POST['template'] ) );

	if ( ! charitable()->get( 'builder_templates' )->is_valid_template( $campaign_template ) ) {
		wp_send_json_error(
			[
				'error_type' => 'invalid_template',
				'message'    => esc_html__( 'The template you selected is currently not available, but you can try again later. If you continue to have trouble, please reach out to support.', 'charitable' ),
			]
		);
	}

	// phpcs:ignore WordPress.WP.DeprecatedFunctions.get_page_by_titleFound -- Legacy support, will be replaced with WP_Query in future version
	$title_exists = get_page_by_title( $campaign_title, 'OBJECT', 'charitable' );
	$campaign_id  = charitable()->get( 'form' )->add(
		$campaign_title,
		[],
		[
			'template' => $campaign_template,
		]
	);

	if ( $title_exists !== null ) {

		// Skip creating a revision for this action.
		remove_action( 'post_updated', 'wp_save_post_revision' );

		wp_update_post(
			[
				'ID'         => $campaign_id,
				'post_title' => $campaign_title . ' (ID #' . $campaign_id . ')',
			]
		);

		// Restore the initial revisions state.
		add_action( 'post_updated', 'wp_save_post_revision', 10, 1 );
	}

	if ( ! $campaign_id ) {
		wp_send_json_error(
			[
				'error_type' => 'cant_create_campaign',
				'message'    => esc_html__( 'Error creating form.', 'charitable' ),
			]
		);
	}

	if ( charitable_current_user_can( 'edit_campaign_single', $campaign_id ) ) {
		wp_send_json_success(
			[
				'id'       => $campaign_id,
				'redirect' => add_query_arg(
					[
						'view'        => 'fields',
						'campaign_id' => $campaign_id,
						'newform'     => '1',
					],
					admin_url( 'admin.php?page=charitable-builder' )
				),
			]
		);
	}

	if ( charitable_current_user_can( 'view_campaigns' ) ) {
		wp_send_json_success( [ 'redirect' => admin_url( 'admin.php?page=charitable-overview' ) ] );
	}

	wp_send_json_success( [ 'redirect' => admin_url() ] );
}

add_action( 'wp_ajax_charitable_new_campaign', 'charitable_new_campaign' );

/**
 * Update form template.
 *
 * @since 1.0.0
 */
function charitable_update_campaign_template() {

	// Run a security check.
	check_ajax_referer( 'charitable-builder', 'nonce' );

	// Check for form name.
	if ( empty( $_POST['campaign_id'] ) ) {
		wp_send_json_error(
			[
				'error_type' => 'invalid_campaign_id',
				'message'    => esc_html__( 'No form ID provided.', 'charitable' ),
			]
		);
	}

	$campaign_id       = absint( $_POST['campaign_id'] );
	$campaign_template = empty( $_POST['template'] ) ? 'blank' : sanitize_text_field( wp_unslash( $_POST['template'] ) );

	if ( ! charitable()->get( 'builder_templates' )->is_valid_template( $campaign_template ) ) {
		wp_send_json_error(
			[
				'error_type' => 'invalid_template',
				'message'    => esc_html__( 'The template you selected is currently not available, but you can try again later. If you continue to have trouble, please reach out to support.', 'charitable' ),
			]
		);
	}

	$data = charitable()->get( 'form' )->get(
		$campaign_id,
		[
			'content_only' => true,
		]
	);

	if ( ! empty( $_POST['title'] ) ) {
		$data['settings']['campaign_title'] = sanitize_text_field( wp_unslash( $_POST['title'] ) );
	}

	$updated = (bool) charitable()->get( 'form' )->update(
		$campaign_id,
		$data,
		[
			'template' => $campaign_template,
		]
	);

	if ( $updated ) {
		wp_send_json_success(
			[
				'id'       => $campaign_id,
				'redirect' => add_query_arg(
					[
						'view'        => 'fields',
						'campaign_id' => $campaign_id,
					],
					admin_url( 'admin.php?page=charitable-builder' )
				),
			]
		);
	}

	wp_send_json_error(
		[
			'error_type' => 'cant_update',
			'message'    => esc_html__( 'Error updating form template.', 'charitable' ),
		]
	);
}

add_action( 'wp_ajax_charitable_update_campaign_template', 'charitable_update_campaign_template' );

/**
 * Campaign Builder update next field ID.
 *
 * @since 1.8.0
 */
function charitable_builder_increase_next_field_id() {

	// Run a security check.
	check_ajax_referer( 'charitable-builder', 'nonce' );

	// Check for permissions.
	if ( ! charitable_current_user_can( 'edit_campaigns' ) ) {
		wp_send_json_error();
	}

	// Check for required items.
	if ( empty( $_POST['campaign_id'] ) ) {
		wp_send_json_error();
	}

	$args = [];

	// In the case of duplicating the Layout field that contains a bunch of fields,
	// we need to set the next `field_id` to the desired value which is passed via POST argument.
	if ( ! empty( $_POST['field_id'] ) ) {
		$args['field_id'] = absint( $_POST['field_id'] );
	}

	charitable()->get( 'form' )->next_field_id( absint( $_POST['campaign_id'] ), $args );

	wp_send_json_success();
}

add_action( 'wp_ajax_charitable_builder_increase_next_field_id', 'charitable_builder_increase_next_field_id' );

/**
 * Campaign Builder Dynamic Choices option toggle.
 *
 * This can be triggered with select/radio/checkbox fields.
 *
 * @since 1.8.0
 */
function charitable_builder_dynamic_choices() {

	// Run a security check.
	check_ajax_referer( 'charitable-builder', 'nonce' );

	// Check for permissions.
	if ( ! charitable_current_user_can( 'edit_campaigns' ) ) {
		wp_send_json_error();
	}

	// Check for valid/required items.
	if ( ! isset( $_POST['field_id'] ) || empty( $_POST['type'] ) || ! in_array( $_POST['type'], [ 'post_type', 'taxonomy' ], true ) ) {
		wp_send_json_error();
	}

	$type = sanitize_key( $_POST['type'] );
	$id   = absint( $_POST['field_id'] );

	// Fetch the option row HTML to be returned to the builder.
	$field      = new Charitable_Field_Select( false );
	$field_args = [
		'id'              => $id,
		'dynamic_choices' => $type,
	];
	$option_row = $field->field_option( 'dynamic_choices_source', $field_args, [], false );

	wp_send_json_success(
		[
			'markup' => $option_row,
		]
	);
}

add_action( 'wp_ajax_charitable_builder_dynamic_choices', 'charitable_builder_dynamic_choices' );

/**
 * Content preview via ajax.
 *
 * @since 1.8.0
 */
function charitable_builder_tab_content_preview() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	$output = charitable_builder_tab_content_preview_by_type( $type );

	wp_send_json_success(
		[
			'output' => $output,
		]
	);
}

add_action( 'wp_ajax_charitable_tab_content_preview', 'charitable_builder_tab_content_preview' );

/**
 * Content preview via ajax.
 *
 * @since 1.8.0
 */
function charitable_builder_field_content_preview() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST['field_type'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below

		$type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', sanitize_key( wp_unslash( $_POST['field_type'] ) ) ) ) ) );

		if ( class_exists( $type ) ) :

			$class         = new $type();
			$field_id      = isset( $_POST['field_id'] ) ? intval( wp_unslash( $_POST['field_id'] ) ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$campaign_data = isset( $_POST['campaign_id'] ) ? get_post_meta( intval( wp_unslash( $_POST['campaign_id'] ) ), 'campaign_settings_v2', true ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array data, sanitized in field_preview method
			$field_data    = $_POST;

			ob_start();

			echo $class->field_preview( $field_data, $campaign_data, $field_id, false, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$output = ob_get_clean();

			wp_send_json_success(
				[
					'output' => $output,
				]
			);

		endif;
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	wp_send_json_error();
}

add_action( 'wp_ajax_charitable_builder_field_content_preview', 'charitable_builder_field_content_preview' );

/**
 * Perform test connection to verify that the current web host can successfully
 * make outbound SSL connections.
 *
 * @since 1.8.0
 */
function charitable_verify_ssl_ajax() {

	// Run a security check.
	check_ajax_referer( 'charitable-admin', 'nonce' );

	// Check for permissions.
	if ( ! charitable_current_user_can() ) {
		wp_send_json_error(
			[
				'msg' => esc_html__( 'You do not have permission to perform this operation.', 'charitable' ),
			]
		);
	}

	$response = wp_remote_post( 'https://wpcharitable.com/connection-test.php' );

	if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
		wp_send_json_success(
			[
				'msg' => esc_html__( 'Success! Your server can make SSL connections.', 'charitable' ),
			]
		);
	}

			wp_send_json_error(
				[
					'msg'   => esc_html__( 'There was an error and the connection failed. Please contact your web host with the technical details below.', 'charitable' ),
					'debug' => '<pre>' . print_r( map_deep( $response, 'wp_strip_all_tags' ), true ) . '</pre>', // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				]
			);
}
// add_action( 'wp_ajax_charitable_verify_ssl', 'charitable_verify_ssl_ajax' ); // Disabled - conflicts with handler in charitable-core-admin-functions.php


/**
 * Update form template.
 *
 * @since 1.8.0
 */
function charitable_builder_ajax_update_campaign_creator() {

	// Run a security check.
	check_ajax_referer( 'charitable-builder', 'nonce' );

	$campaign_id    = ! empty( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : false;
	$new_creator_id = ! empty( $_POST['creator_id'] ) ? absint( $_POST['creator_id'] ) : false;

	if ( false === $new_creator_id ) {
		wp_send_json_error(
			[
				'error_type' => 'invalid_campaign_id',
				'message'    => esc_html__( 'No creator ID provided.', 'charitable' ),
			]
		);
	}

	// check to see if the user exists.
	$user = get_userdata( $new_creator_id );
	if ( $user === false ) {
		wp_send_json_error(
			[
				'error_type' => 'invalid_campaign_id',
				'message'    => esc_html__( 'Invalid creator ID provided.', 'charitable' ),
			]
		);
	}

	// todo: check creds?

	if ( $user ) {

		wp_send_json_success(
			[
				'id'                  => $campaign_id,
				'new_creator_id'      => $new_creator_id,
				'avatar_url'          => esc_url( get_avatar_url( $new_creator_id ) ),
				'creator_name'        => $user->display_name . ' (USER ID ' . $new_creator_id . ')',
				'public_profile_link' => get_author_posts_url( $new_creator_id ),
				'edit_profile_link'   => admin_url( 'user-edit.php?user_id=' . $new_creator_id ),
			]
		);
	}

	wp_send_json_error(
		[
			'error_type' => 'cant_update',
			'message'    => esc_html__( 'Error updating campaign creator.', 'charitable' ),
		]
	);
}
add_action( 'wp_ajax_charitable_update_campaign_creator', 'charitable_builder_ajax_update_campaign_creator' );

/**
 * Update form template.
 *
 * @since 1.8.0
 */
function charitable_builder_ajax_update_campaign_status_link() {

	// Run a security check.
	check_ajax_referer( 'charitable-builder', 'nonce' );

	// Check for campaign ID.
	if ( empty( $_POST['campaign_id'] ) || 0 === intval( $_POST['campaign_id'] ) ) {
		wp_send_json_error(
			[
				'error_type' => 'invalid_campaign_id',
				'message'    => esc_html__( 'No campaign ID provided.', 'charitable' ),
			]
		);
	}

	$campaign_id = absint( $_POST['campaign_id'] );

	// get status.
	$campaign = $campaign_id ? charitable_get_campaign( $campaign_id ) : false;
	$status   = $campaign ? $campaign->get_status() : false;
	if ( 'finished' === $status && $campaign->has_goal() ) {
		$status = $campaign->has_achieved_goal() ? 'successful' : 'unsuccessful';
	}

	// campaign status.
	$html = '<span class="charitable-view-campaign-external-link"><a href="' . get_permalink( $campaign_id ) . '" target="_blank" title="' . esc_html__( 'View Campaign Page', 'charitable' ) . '"><!--<i class="fa fa-eye"></i>--></a></span>';

	wp_send_json_success(
		[
			'id'   => $campaign_id,
			'html' => $html,
		]
	);
}
add_action( 'wp_ajax_charitable_update_campaign_status_link', 'charitable_builder_ajax_update_campaign_status_link' );

/**
 * Receive and process feedback form content.
 *
 * @since 1.8.0
 */
function charitable_campaign_builder_send_feedback_ajax() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	/* Data to send in our API request */
	$feedback_params = array(
		'feedback_action' => 'template_feedback',
		'type'            => ! empty( $_POST['data']['type'] ) ? sanitize_text_field( wp_unslash( $_POST['data']['type'] ) ) : false, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		'name'            => ! empty( $_POST['data']['name'] ) ? sanitize_text_field( wp_unslash( $_POST['data']['name'] ) ) : false, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		'email'           => ! empty( $_POST['data']['email'] ) ? sanitize_email( wp_unslash( $_POST['data']['email'] ) ) : false, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		'feedback'        => ! empty( $_POST['data']['feedback'] ) ? sanitize_text_field( wp_unslash( $_POST['data']['feedback'] ) ) : false, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	);
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	/* Call the custom API */
	$response = wp_remote_post(
		'https://wpcharitable.com',
		array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $feedback_params,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error(
		);
	} else {
		wp_send_json_success(
			[
				'sent' => true,
			]
		);
	}
}
add_action( 'wp_ajax_charitable_campaign_builder_send_feedback_ajax', 'charitable_campaign_builder_send_feedback_ajax' );

