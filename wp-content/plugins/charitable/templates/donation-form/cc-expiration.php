<?php
/**
 * Displays the credit card expiration select boxes.
 *
 * Override this template by copying it to yourtheme/charitable/cc-expiration.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form         = $view_args['form'];
$charitable_field        = $view_args['field'];
$charitable_classes      = $view_args['classes'];
$charitable_is_required  = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_current_year = date( 'Y' ); // phpcs:ignore

?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<fieldset class="charitable-fieldset-field-wrapper">
		<?php if ( isset( $charitable_field['label'] ) ) : ?>
			<div class="charitable-fieldset-field-header" id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_label">
				<?php echo wp_kses_post( $charitable_field['label'] ); ?>
				<?php if ( $charitable_is_required ) : ?>
					<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
				<?php endif ?>
			</div>
		<?php endif ?>
		<select name="<?php echo esc_attr( $charitable_field['key'] ); ?>[month]" class="month" aria-describedby="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_label">
			<?php
			foreach ( range( 1, 12 ) as $charitable_month ) :
				$charitable_padded_month = sprintf( '%02d', $charitable_month );
				?>
				<option value="<?php echo esc_attr( $charitable_padded_month ); ?>"><?php echo esc_html( $charitable_padded_month ); ?></option>
			<?php endforeach ?>
		</select>
		<select name="<?php echo esc_attr( $charitable_field['key'] ); ?>[year]" class="year" aria-describedby="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>_label">
			<?php
			for ( $charitable_i = 0; $charitable_i < 15; $charitable_i++ ) :
				$charitable_year = $charitable_current_year + $charitable_i; // phpcs:ignore
				?>
				<option value="<?php echo esc_attr( $charitable_year ); ?>"><?php echo esc_html( $charitable_year ); ?></option>
			<?php endfor ?>
		</select>
	</fieldset><!-- .charitable-field-wrapper -->
</div><!-- #charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?> -->
