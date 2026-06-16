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

if ( $charitable_form->has_donation() ) :
	$charitable_button_text = __( 'Update Donation', 'charitable' );
	$charitable_cancel_url  = remove_query_arg( 'show_form' );
else :
	$charitable_button_text = __( 'Save Donation', 'charitable' );
	$charitable_cancel_url  = admin_url( 'edit.php?post_type=donation' );
endif;

?>
<div class="charitable-form-fields secondary">
	<?php $charitable_form->view()->render_field( $charitable_fields['meta_fields'], 'meta_fields' ); ?>
</div>
<div class="charitable-form-field charitable-submit-field">
	<a href="<?php echo esc_url( $charitable_cancel_url ); ?>" class="alignright" title="<?php esc_attr_e( 'Return to donation page', 'charitable' ); ?>" tabindex="401"><?php esc_html_e( 'Cancel', 'charitable' ); ?></a>
	<button class="button button-primary" type="submit" name="donate" tabindex="400"><?php echo wp_kses_post( $charitable_button_text ); ?></button>
</div><!-- .charitable-submit-field -->
