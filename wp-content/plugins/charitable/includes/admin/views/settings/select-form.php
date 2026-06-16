<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display select field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( false === $charitable_value ) {
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
}

$charitable_form_action_url    = isset( $view_args['form_action_url'] ) ? $view_args['form_action_url'] : '';
$charitable_form_action_method = isset( $view_args['form_action_method'] ) ? $view_args['form_action_method'] : 'POST';
$charitable_button_label       = isset( $view_args['button_label'] ) ? $view_args['button_label'] : 'Submit';
$charitable_nonce_action_name  = isset( $view_args['nonce_action_name'] ) ? $view_args['nonce_action_name'] : 'wpcharitable-action';
$charitable_nonce_field_name   = isset( $view_args['nonce_field_name'] ) ? $view_args['nonce_field_name'] : false;
$charitable_error_message      = false;

// determine if the fields should be shown, based on what we are trying to do with them.
if ( isset( $view_args['nonce_action_name'] ) && 'export_campaign' === $view_args['nonce_action_name'] && is_array( $view_args['options'] ) && 0 === count( $view_args['options'] ) ) {
	$charitable_error_message = '<strong>' . __( 'You have no campaigns to export.', 'charitable' ) . '</strong>';
} elseif ( isset( $view_args['nonce_action_name'] ) && 'export_donations_from_campaign' === $view_args['nonce_action_name'] && is_array( $view_args['options'] ) && 0 === count( $view_args['options'] ) ) {
	$charitable_error_message = '<strong>' . __( 'You have no donations to export.', 'charitable' ) . '</strong>';
}
?>
<form action="<?php echo esc_url( $charitable_form_action_url ); ?>" method="<?php echo esc_attr( $charitable_form_action_method ); ?>">

	<?php wp_nonce_field( $charitable_nonce_action_name, $charitable_nonce_field_name ); ?>

	<?php if ( ! $charitable_error_message ) { ?>
		<select id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
			name="<?php printf( 'charitable_settings[%s]', $view_args['name'] ); // phpcs:ignore ?>"
			class="<?php echo esc_attr( $view_args['classes'] ); ?>"
			<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>
			>
			<?php
			foreach ( $view_args['options'] as $charitable_key => $charitable_option ) :
				if ( is_array( $charitable_option ) ) :
					$charitable_label = isset( $charitable_option['label'] ) ? $charitable_option['label'] : '';
					?>
					<optgroup label="<?php echo esc_html( $charitable_label ); ?>">
					<?php foreach ( $charitable_option['options'] as $charitable_k => $charitable_opt ) : ?>
						<option value="<?php echo esc_attr( $charitable_k ); ?>" <?php selected( $charitable_k, $charitable_value ); ?>><?php echo esc_html( $charitable_opt ); ?></option>
					<?php endforeach ?>
					</optgroup>
				<?php else : ?>
					<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_key, $charitable_value ); ?>><?php echo $charitable_option; // phpcs:ignore ?></option>
					<?php
				endif;
			endforeach
			?>
		</select>
		<input class="button button-primary" type="submit" value="<?php echo esc_html( $charitable_button_label ); ?>">
	<?php } else { ?>
        <p><?php echo $charitable_error_message; // phpcs:ignore ?></p>
	<?php } ?>
</form>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>
	<?php
endif;
