<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The template used to display error messages.
 *
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.5.0
 * @package Charitable/Templates/Form Fields
 */

if ( ! array_key_exists( 'errors', $view_args ) || empty( $view_args['errors'] ) ) {
	return;
}

?>
<div class="charitable-form-errors charitable-notice">
	<ul class="errors">
		<?php foreach ( $view_args['errors'] as $error ) : // phpcs:ignore ?>
			<li><?php echo $error; // phpcs:ignore ?></li>
		<?php endforeach ?>
	</ul>
</div>
