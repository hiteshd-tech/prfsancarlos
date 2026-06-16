<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation form meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 * @version   1.8.8.6
 */

$charitable_form   = $view_args['form'];
$charitable_fields = $charitable_form->get_fields();

unset( $charitable_fields['meta_fields'] );

?>
<div class="donation-banner-wrapper">
	<div class="donation-banner">
		<h3 class="donation-number"><?php printf( '%s #%d', esc_html__( 'Donation', 'charitable' ), esc_html( $charitable_form->get_donation()->get_number() ) ); ?></h3>
	</div>
</div>
<div class="charitable-form-fields primary">
	<?php
	$charitable_form->view()->render_hidden_fields();
	$charitable_form->view()->render_fields( $charitable_fields );
	?>
</div><!-- .charitable-form-fields -->
