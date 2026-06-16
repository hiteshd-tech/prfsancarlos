<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display text field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.5.0
 * @version   1.5.0
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

?>
<input type="hidden"
	id="<?php echo esc_attr( $view_args['id'] ); ?>"
	name="<?php echo esc_attr( $view_args['key'] ); ?>"
	value="<?php echo esc_attr( $view_args['value'] ); ?>"
/>
