<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display the WP Editor in a form.
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

$charitable_form        = $view_args['form'];
$charitable_field       = $view_args['field'];
$charitable_classes     = $view_args['classes'];
$charitable_is_required = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_value       = isset( $charitable_field['value'] ) ? $charitable_field['value'] : '';
$charitable_editor_args = isset( $charitable_field['editor'] ) ? wpautop( $charitable_field['editor'] ) : array();

/**
 * Change the editor settings.
 *
 * @see   https://developer.wordpress.org/reference/classes/_wp_editors/parse_settings/
 *
 * @since 1.5.0
 *
 * @param array $settings The default settings.
 */
$charitable_default_editor_args = array(
	'media_buttons' => true,
	'teeny'         => true,
	'quicktags'     => false,
	'tinymce'       => array(
		'theme_advanced_path'     => false,
		'theme_advanced_buttons1' => 'bold,italic,bullist,numlist,blockquote,justifyleft,justifycenter,justifyright,link,unlink',
	),
);

$charitable_editor_args = wp_parse_args( $charitable_editor_args, $charitable_default_editor_args );
?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label for="<?php echo esc_attr( $charitable_field['key'] ); ?>">
			<?php echo wp_kses_post( $charitable_field['label'] ); ?>
			<?php if ( $charitable_is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php
		wp_editor( $charitable_value, $charitable_field['key'], $charitable_editor_args );
	?>
</div>
