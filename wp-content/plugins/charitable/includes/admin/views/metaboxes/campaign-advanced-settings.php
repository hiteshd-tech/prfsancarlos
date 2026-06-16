<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loops over the meta boxes inside the advanced settings area of the Campaign post type.
 *
 * @author 		WP Charitable LLC
 * @package     Charitable/Admin Views/Metaboxes
 * @copyright 	Copyright (c) 2023, WP Charitable LLC
 * @since 		1.0.0
 * @version     1.8.8.6
 */

global $post;

if ( ! isset( $view_args['meta_boxes'] ) || empty( $view_args['meta_boxes'] ) ) {
	return;
}
?>
<div id="charitable-campaign-advanced-metabox" class="charitable-metabox">
	<ul class="charitable-tabs">
		<?php foreach ( $view_args['meta_boxes'] as $charitable_meta_box ) : ?>
			<li><a href="<?php printf( '#%s', esc_attr( $charitable_meta_box['id'] ) ); ?>"><?php echo esc_html( $charitable_meta_box['title'] ); ?></a></li>
		<?php endforeach ?>
	</ul>
	<?php foreach ( $view_args['meta_boxes'] as $charitable_meta_box ) : ?>
		<div id="<?php echo esc_attr( $charitable_meta_box['id'] ); ?>" class="postbox <?php echo esc_attr( postbox_classes( $charitable_meta_box['id'], 'campaign' ) ); ?>">
			<div class="inside">
				<?php call_user_func( $charitable_meta_box['callback'], $post, $charitable_meta_box ); ?>
			</div>
		</div>
	<?php
	endforeach;
	?>
</div>