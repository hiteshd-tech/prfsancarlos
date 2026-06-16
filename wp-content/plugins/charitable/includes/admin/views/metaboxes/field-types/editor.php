<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editor field.
 *
 * @package   Charitable
 * @copyright Copyright (c) 2022, David Bisset
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.60
 * @version   1.6.60
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$charitable_textarea_name      = 'content';
$charitable_textarea_rows      = apply_filters( 'charitable_extended_description_rows', 40 );
$charitable_textarea_tab_index = isset( $view_args['tab_index'] ) ? $view_args['tab_index'] : 0;

$charitable_is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];
$charitable_field_attrs = array_key_exists( 'field_attrs', $view_args ) ? $view_args['field_attrs'] : array();
?>
<div id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>>
	<?php if ( isset( $view_args['label'] ) ) : ?>
		<label for="<?php echo esc_attr( $view_args['id'] ); ?>">
			<?php
			echo esc_html( $view_args['label'] );
			if ( $charitable_is_required ) :
				?>
				<abbr class="required" title="required">*</abbr>
				<?php
			endif;
			?>
		</label>
		<?php
	endif;
	wp_editor(
		$view_args['value'],
		$view_args['id'],
		array(
			'textarea_name' => esc_attr( $view_args['key'] ),
			'textarea_rows' => $charitable_textarea_rows,
			'tabindex'      => $charitable_textarea_tab_index,
		)
	);
	?>
</div>
