<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display file form fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.6.13
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

if ( array_key_exists( 'uploader', $view_args['field'] ) && ! $view_args['field']['uploader'] ) {
	return charitable_template( 'form-fields/file.php', $view_args );
}

$charitable_form          = $view_args['form'];
$charitable_field         = $view_args['field'];
$charitable_classes       = $view_args['classes'];
$charitable_is_required   = isset( $charitable_field['required'] ) ? $charitable_field['required'] : false;
$charitable_placeholder   = isset( $charitable_field['placeholder'] ) ? esc_attr( $charitable_field['placeholder'] ) : '';
$charitable_size          = isset( $charitable_field['size'] ) ? $charitable_field['size'] : 'thumbnail';
$charitable_max_uploads   = isset( $charitable_field['max_uploads'] ) ? $charitable_field['max_uploads'] : 1;
$charitable_max_file_size = isset( $charitable_field['max_file_size'] ) ? $charitable_field['max_file_size'] : wp_max_upload_size();
$charitable_pictures      = isset( $charitable_field['value'] ) ? $charitable_field['value'] : array();

if ( wp_is_mobile() ) {
	$charitable_classes .= ' mobile';
}

if ( ! is_array( $charitable_pictures ) ) {
	$charitable_pictures = array( $charitable_pictures );
}

foreach ( $charitable_pictures as $charitable_i => $charitable_picture ) {
	if ( false === strpos( $charitable_picture, 'img' ) && ! wp_attachment_is_image( $charitable_picture ) ) {
		unset( $charitable_pictures[ $charitable_i ] );
	}
}

$charitable_has_max_uploads = count( $charitable_pictures ) >= $charitable_max_uploads;
$charitable_params          = array(
	'runtimes'            => 'html5,silverlight,flash,html4',
	'file_data_name'      => 'async-upload',
	'container'           => $charitable_field['key'] . '-dragdrop',
	'browse_button'       => $charitable_field['key'] . '-browse-button',
	'drop_element'        => $charitable_field['key'] . '-dragdrop-dropzone',
	'multiple_queues'     => true,
	'url'                 => admin_url( 'admin-ajax.php' ),
	'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
	'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
	'multipart'           => true,
	'urlstream_upload'    => true,
	'filters'             => array(
		array(
			'title'      => _x( 'Allowed Image Files', 'image upload', 'charitable' ),
			'extensions' => 'jpg,jpeg,gif,png',
		),
	),
	'multipart_params'    => array(
		'field_id'    => $charitable_field['key'],
		'action'      => 'charitable_plupload_image_upload',
		'_ajax_nonce' => wp_create_nonce( "charitable-upload-images-{$charitable_field[ 'key' ]}" ),
		'post_id'     => isset( $charitable_field['parent_id'] ) && strlen( $charitable_field['parent_id'] ) ? $charitable_field['parent_id'] : '0',
		'size'        => $charitable_size,
		'max_uploads' => $charitable_max_uploads,
	),
);

wp_enqueue_script( 'charitable-plup-fields' );
wp_enqueue_style( 'charitable-plup-styles' );

?>
<div id="charitable_field_<?php echo esc_attr( $charitable_field['key'] ); ?>" class="<?php echo esc_attr( $charitable_classes ); ?>">
	<?php if ( isset( $charitable_field['label'] ) ) : ?>
		<label>
			<?php echo wp_kses_post( $charitable_field['label'] ); ?>
			<?php if ( $charitable_is_required ) : ?>
				<abbr class="required" title="<?php esc_html_e( 'Required', 'charitable' ); ?>">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<?php if ( isset( $charitable_field['help'] ) ) : ?>
		<p class="charitable-field-help"><?php echo $charitable_field['help']; // phpcs:ignore  ?></p>
	<?php endif ?>
	<div id="<?php echo esc_attr( $charitable_field['key'] ); ?>-dragdrop"
		class="charitable-drag-drop"
		data-max-size="<?php echo esc_attr( $charitable_max_file_size ); ?>"
		data-images="<?php echo esc_attr( $charitable_field['key'] ); ?>-dragdrop-images"
		data-params="<?php echo esc_attr( wp_json_encode( $charitable_params ) ); ?>">
		<div id="<?php echo esc_attr( $charitable_field['key'] ); ?>-dragdrop-dropzone" class="charitable-drag-drop-dropzone" <?php echo $charitable_has_max_uploads ? 'style="display:none;"' : ''; ?>>
			<p class="charitable-drag-drop-info"><?php echo esc_html( 1 === $charitable_max_uploads ? _x( 'Drop image here', 'image upload', 'charitable' ) : _x( 'Drop images here', 'image upload plural', 'charitable' ) ); ?></p>
			<p><?php echo esc_html_x( 'or', 'image upload', 'charitable' ); ?></p>
			<p class="charitable-drag-drop-buttons">
				<button id="<?php echo esc_attr( $charitable_field['key'] ); ?>-browse-button" class="button" type="button"><?php echo esc_html_x( 'Select Files', 'image upload', 'charitable' ); ?></button>
			</p>
		</div>
		<div class="charitable-drag-drop-image-loader" style="display: none;">
			<p class="loader-title"><?php esc_html_e( 'Uploading...', 'charitable' ); ?></p>
			<ul class="images"></ul>
		</div>
		<ul id="<?php echo esc_attr( $charitable_field['key'] ); ?>-dragdrop-images" class="charitable-drag-drop-images charitable-drag-drop-images-<?php echo esc_attr( $charitable_max_uploads ); ?>">
			<?php
			foreach ( $charitable_pictures as $charitable_image ) :
				charitable_template(
					'form-fields/picture-preview.php',
					array(
						'image' => $charitable_image,
						'field' => $charitable_field,
					)
				);
			endforeach;
			?>
		</ul>
	</div>
</div>
