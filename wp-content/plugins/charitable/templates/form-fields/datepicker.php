<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display datepicker form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.6.40
 * @version 1.8.6.1 Added description output.
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

global $wp_locale;

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = esc_attr( $view_args['classes'] );
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_value       = isset( $charitable_field['value'] ) ? esc_attr( $charitable_field['value'] ) : '';
$charitable_min_date    = isset( $charitable_field['min_date'] ) ? esc_attr( $charitable_field['min_date'] ) : '';
$charitable_max_date    = isset( $charitable_field['max_date'] ) ? esc_attr( $charitable_field['max_date'] ) : '';
$charitable_year_range  = array_key_exists( 'year_range', $charitable_field ) ? $charitable_field['year_range'] : 'c-100:c';
$charitable_date_format = array_key_exists( 'date_format', $charitable_field ) ? $charitable_field['date_format'] : charitable()->registry()->get( 'i18n' )->get_js_datepicker_format( 'MM d, yy' );
$charitable_json_args   = array(
	'changeMonth'     => true,
	'changeYear'      => true,
	'dateFormat'      => $charitable_date_format,
	'yearRange'       => $charitable_year_range,
	'monthNames'      => array_values( $wp_locale->month ),
	'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
);

if ( array_key_exists( 'min_date', $charitable_field ) ) {
	$charitable_json_args['minDate'] = '+' . $charitable_field['min_date'];
}

if ( array_key_exists( 'max_date', $charitable_field ) ) {
	$charitable_json_args['maxDate'] = '+' . $charitable_field['max_date'];
}

/* Enqueue the datepicker */
if ( ! wp_script_is( 'jquery-ui-datepicker' ) ) {
	wp_enqueue_script( 'jquery-ui-datepicker' );
}

$charitable_datepicker_json_args = wp_json_encode( $charitable_json_args );

wp_add_inline_script(
	'jquery-ui-datepicker',
	"jQuery(document).ready( function(){ jQuery( '.datepicker' ).datepicker( {$charitable_datepicker_json_args} ); });"
);

wp_enqueue_style( 'charitable-datepicker' );

?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element">
			<?php echo wp_kses_post( $charitable_field['label'] ); ?>
			<?php if ( $charitable_is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<input
		type="text"
		class="datepicker"
		name="<?php echo esc_attr( $charitable_field['key'] ); ?>"
		value="<?php echo esc_attr( $charitable_value ); ?>"
		id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element"
		<?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore ?>
	/>
	<?php

	// If there is a description, add it after the input.
	if ( isset( $charitable_field['description'] ) && ! empty( $charitable_field['description'] ) ) {
		echo '<p class="charitable-field-description">' . wp_kses_post( $charitable_field['description'] ) . '</p>';
	}

	?>
</div>