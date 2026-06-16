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
 * @version   1.8.5.7 - Added show_button argument.
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( false === $charitable_value ) {
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
}

$charitable_form_action_url    = isset( $view_args['form_action_url'] ) ? $view_args['form_action_url'] : '';
$charitable_form_action_method = isset( $view_args['form_action_method'] ) ? $view_args['form_action_method'] : 'POST';
$charitable_show_button        = isset( $view_args['show_button'] ) ? $view_args['show_button'] : true;
$charitable_button_label       = isset( $view_args['button_label'] ) ? $view_args['button_label'] : 'Submit';
$charitable_nonce_action_name  = isset( $view_args['nonce_action_name'] ) ? $view_args['nonce_action_name'] : 'wpcharitable-action';
$charitable_nonce_field_name   = isset( $view_args['nonce_field_name'] ) ? $view_args['nonce_field_name'] : false;
$charitable_select_name        = isset( $view_args['select_name'] ) ? $view_args['select_name'] : $view_args['name'];
$charitable_accept             = isset( $view_args['accept'] ) ? $view_args['accept'] : '.jsn,.json';
$charitable_form_tag           = isset( $view_args['form_tag'] ) ? $view_args['form_tag'] : true;
$charitable_wrapper_class      = isset( $view_args['wrapper_class'] ) ? $view_args['wrapper_class'] : '';
$charitable_thumbnail_preview  = isset( $view_args['thumbnail_preview'] ) ? $view_args['thumbnail_preview'] : false; // This is the ID of the attachment.
$charitable_remove_button      = isset( $view_args['show_remove_button'] ) ? $view_args['show_remove_button'] : false;
$charitable_remove_button_text = isset( $view_args['remove_button_text'] ) ? $view_args['remove_button_text'] : __( 'Remove current logo', 'charitable' );
$charitable_error_message      = false;

// determine if the fields should be shown, based on what we are trying to do with them.
if ( isset( $view_args['nonce_action_name'] ) && 'import_donations' === $view_args['nonce_action_name'] && is_array( $view_args['options'] ) && 0 === count( $view_args['options'] ) ) {
	$charitable_error_message = '<strong>' . __( 'You have no campaigns to import donations into.', 'charitable' ) . '</strong>';
}
?>
<?php if ( $charitable_form_tag ) : ?>
	<form action="<?php echo esc_url( $charitable_form_action_url ); ?>" method="POST" enctype="multipart/form-data" class="form-contains-file charitable-import-campaign-donations-form">

	<?php wp_nonce_field( $charitable_nonce_action_name, $charitable_nonce_field_name ); ?>

	<input type="hidden" name="action" value="<?php echo esc_attr( $view_args['action'] ); ?>">

<?php endif; ?>

<?php if ( isset( $view_args['options'] ) && ! empty( $view_args['subtitle'] ) && ! $charitable_error_message ) : ?>
	<p class="top-label"><?php echo esc_html( $view_args['subtitle'] ); ?></p>
<?php endif; ?>

<?php if ( isset( $view_args['options'] ) ) : ?>
	<div class="charitable-setting-dropdown-container <?php echo esc_attr( $view_args['wrapper_class'] ); ?>">
		<?php if ( ! $charitable_error_message ) { ?>
			<select id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
				name="<?php printf( 'charitable_settings[%s]', esc_attr( $charitable_select_name ) ); ?>"
				class="<?php echo esc_attr( $view_args['classes'] ); ?>"
				<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>
				>
				<?php
				foreach ( $view_args['options'] as $charitable_key => $charitable_option ) :
					if ( is_array( $charitable_option ) ) :
						$charitable_label = isset( $charitable_option['label'] ) ? $charitable_option['label'] : '';
						?>
						<optgroup label="<?php echo esc_attr( $charitable_label ); ?>">
						<?php foreach ( $charitable_option['options'] as $charitable_k => $charitable_opt ) : ?>
							<option value="<?php echo esc_attr( $charitable_k ); ?>" <?php selected( $charitable_k, $charitable_value ); ?>><?php echo esc_html( $charitable_opt ); ?></option>
						<?php endforeach ?>
						</optgroup>
					<?php else : ?>
						<option value="<?php echo esc_attr( $charitable_key ); ?>" <?php selected( $charitable_key, $charitable_value ); ?>><?php echo esc_html( $charitable_option ); ?></option>
						<?php
					endif;
				endforeach
				?>
			</select>
		<?php } else { ?>
			<p><?php echo $charitable_error_message; // phpcs:ignore ?></p>
		<?php } ?>
		<?php endif; ?>
	</div>
	<?php if ( ! $charitable_error_message ) { ?>
	<div class="charitable-setting-file-container">

		<input type="file" id="<?php printf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ); // phpcs:ignore ?>"
			name="<?php printf( 'charitable_settings[%s]', esc_attr( $view_args['name'] ) ); ?>"
			class="<?php echo esc_attr( $view_args['classes'] ); ?>"
			<?php echo charitable_get_arbitrary_attributes( $view_args ); // phpcs:ignore ?>
			accept="<?php echo esc_attr( $charitable_accept ); ?>"
			/>
		<?php if ( $charitable_show_button ) : ?>
			<input class="button button-primary" type="submit" value="<?php echo esc_attr( $charitable_button_label ); ?>">
		<?php endif; ?>

	</div>
	<?php } ?>
	<?php if ( $charitable_thumbnail_preview ) : ?>
		<div class="charitable-setting-file-thumbnail-preview" style="max-height: 100px; display: flex; align-items: center; justify-content: left;">
			<img src="<?php echo esc_url( wp_get_attachment_url( $charitable_thumbnail_preview ) ); ?>" alt="<?php echo esc_attr( $view_args['name'] ); ?>" style="max-height: 100px; width: auto; height: auto; object-fit: contain;">
		</div>
		<?php
		// Add checkbox for removing the logo - if there is no remove button id, don't show the checkbox.
		if ( ! empty( $view_args['remove_button_id'] ) ) {
			$charitable_remove_checkbox_name = 'charitable_settings[' . $view_args['remove_button_id'] . ']';
			$charitable_remove_checkbox_id   = 'charitable_settings_' . $view_args['remove_button_id'];
			?>
			<div class="charitable-setting-remove-logo" style="margin-top: 10px;">
				<label>
					<input type="checkbox"
						name="<?php echo esc_attr( $charitable_remove_checkbox_name ); ?>"
						id="<?php echo esc_attr( $charitable_remove_checkbox_id ); ?>"
						value="1"
						<?php checked( isset( $_POST['charitable_settings'][ $view_args['remove_button_id'] ] ) && $_POST['charitable_settings'][ $view_args['remove_button_id'] ] ); // phpcs:ignore ?>
					>
					<?php echo esc_html( $charitable_remove_button_text ); ?>
				</label>
			</div>
		<?php } ?>
	<?php endif; ?>
<?php if ( $charitable_form_tag ) : ?>
	</form>
<?php endif; ?>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; // phpcs:ignore ?></div>
	<?php
endif;
