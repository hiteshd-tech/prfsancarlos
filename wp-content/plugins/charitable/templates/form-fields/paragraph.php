<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display the suggested amounts field.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.0.0
 * @package Charitable/Templates/Form Fields
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form    = $view_args['form'];
$charitable_field   = $view_args['field'];
$charitable_classes = $view_args['classes'];

if ( ! isset( $charitable_field['content'] ) ) {
	return;
}
?>
<p class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php echo $charitable_field['content']; // phpcs:ignore ?>
</p>
