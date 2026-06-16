<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display notices.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.3.0
 * @version 1.8.8.6
 */

if ( ! isset( $view_args['notices'] ) ) {
	return;
}

$charitable_notices = array_filter( $view_args['notices'] );

if ( empty( $charitable_notices ) ) {
	return;
}

foreach ( $charitable_notices as $type => $messages ) : // phpcs:ignore
	if ( 'error' == $type ) : // phpcs:ignore
		$type = 'errors'; // phpcs:ignore
	endif;
	?>
	<div class="charitable-notice charitable-form-<?php echo esc_attr( $type ); ?>">
		<ul class="charitable-notice-<?php echo esc_attr( $type ); ?> <?php echo esc_attr( $type ); ?>">
			<?php foreach ( $messages as $charitable_message ) : ?>
				<li><?php echo $charitable_message; // phpcs:ignore ?></li>
			<?php endforeach ?>
		</ul><!-- charitable-notice-<?php esc_attr( $type ); ?> -->
	</div><!-- .charitable-notices -->
<?php endforeach ?>
