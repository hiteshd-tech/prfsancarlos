<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display a preview of an uploaded photo.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.4.0
 * @version 1.6.43
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['image'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_image              = $view_args['image'];
$charitable_field              = $view_args['field'];
$charitable_size               = isset( $charitable_field['size'] ) ? $charitable_field['size'] : 'thumbnail';
$charitable_multiple           = isset( $charitable_field['max_uploads'] ) && $charitable_field['max_uploads'] > 1 ? '[]' : '';
$charitable_is_src             = strpos( $charitable_image, 'img' ) !== false;
$charitable_remove_button_text = isset( $charitable_field['remove_button_text'] ) ? $charitable_field['remove_button_text'] : __( 'Remove', 'charitable' );
$charitable_remove_button_show = isset( $charitable_field['remove_button_show'] ) && $charitable_field['remove_button_show'];

if ( is_numeric( $charitable_size ) ) {
	$charitable_size = array( $charitable_size, $charitable_size );
}

?>
<li <?php echo ! $charitable_is_src ? 'data-attachment-id="' . esc_attr( $charitable_image ) . '"' : ''; ?>>
	<a href="#" class="remove-image button"
	<?php
	if ( $charitable_remove_button_show ) {
		echo 'style="display:block;"';}
	?>
	><?php echo esc_html( $charitable_remove_button_text ); ?></a>
	<?php
	if ( $charitable_is_src ) :
		echo $charitable_image; // phpcs:ignore
	else :
		?>
		<input type="hidden"
			name="<?php echo esc_attr( $charitable_field['key'] . $charitable_multiple ); ?>"
			id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_element"
			value="<?php echo esc_attr( $charitable_image ); ?>"
		/>
		<?php echo wp_get_attachment_image( $charitable_image, $charitable_size ); ?>
	<?php endif ?>
</li>
