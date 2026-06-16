<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display select fieldsets.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/fieldset.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.5.0
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form    = $view_args['form'];
$charitable_field   = $view_args['field'];
$charitable_classes = $view_args['classes'];
$charitable_fields  = isset( $charitable_field['fields'] ) ? $charitable_field['fields'] : array();

if ( ! count( $charitable_fields ) ) :
	return;
endif;

?>
<fieldset class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php
	if ( isset( $charitable_field['legend'] ) ) :
		?>
		<div class="charitable-form-header"><?php echo esc_html( $charitable_field['legend'] ); ?></div>
		<?php
	endif;

	$charitable_form->view()->render_fields( $charitable_fields );
	?>
</fieldset>
