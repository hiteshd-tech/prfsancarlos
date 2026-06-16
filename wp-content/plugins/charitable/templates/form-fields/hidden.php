<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display text form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form  = $view_args['form'];
$charitable_field = $view_args['field'];
$charitable_value = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';
?>
<input type="hidden" name="<?php echo esc_attr( $charitable_field['key'] ); ?>" value="<?php echo esc_attr( $charitable_value ); ?>" <?php echo charitable_get_arbitrary_attributes( $charitable_field ); // phpcs:ignore ?>/>
