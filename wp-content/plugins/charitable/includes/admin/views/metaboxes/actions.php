<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Donations Page
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.0
 * @version   1.8.8.6
 */

global $post;

$charitable_helper  = array_key_exists( 'actions', $view_args ) ? $view_args['actions'] : charitable_get_donation_actions();
$charitable_actions = $charitable_helper->get_available_actions( $post->ID );
$charitable_groups  = $charitable_helper->get_available_groups( $post->ID );
$type    = esc_attr( $charitable_helper->get_type() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( empty( $charitable_actions ) ) {
	return;
}
?>
<div id="charitable-<?php echo esc_attr( $type ); ?>-actions-metabox-wrapper" class="charitable-metabox charitable-actions-form-wrapper">

	<div id="charitable-<?php echo esc_attr( $type ); ?>-actions-form" class="charitable-actions-form">
		<?php
		/**
		 * Do something at the top of the actions form.
		 *
		 * @since 1.6.0
		 *
		 * @param int $post_id The current post ID.
		 */
		do_action( 'charitable_' . $type . '_actions_start', $post->ID );
		?>
		<select id="charitable_<?php echo esc_attr( $type ); ?>_actions" name="charitable_<?php echo esc_attr( $type ); ?>_action" class="charitable-action-select">
			<option value=""><?php esc_html_e( 'Select an action', 'charitable' ); ?></option>
			<?php
			foreach ( $charitable_groups as $charitable_label => $charitable_group_actions ) :

				if ( ! empty( $charitable_label ) && 'default' != $charitable_label ) : // phpcs:ignore
					?>
					<optgroup label="<?php echo esc_attr( $charitable_label ); ?>">
					<?php
				endif;
				foreach ( $charitable_group_actions as $action ) : // phpcs:ignore
					if ( array_key_exists( $action, $charitable_actions ) ) :
						?>
						<option value="<?php echo esc_attr( $action ); ?>" data-button-text="<?php echo esc_attr( $charitable_actions[ $action ]['button_text'] ); ?>"><?php echo esc_html( $charitable_actions[ $action ]['label'] ); ?></option>
						<?php
					endif;
				endforeach;
				if ( ! empty( $charitable_label ) ) :
					?>
					</optgroup>
					<?php
				endif;
			endforeach;
			?>
		</select>
		<?php
		foreach ( $charitable_groups as $charitable_group_actions ) :
			foreach ( $charitable_group_actions as $action ) : // phpcs:ignore
				$charitable_helper->add_action_fields( $post->ID, $action );
			endforeach;
		endforeach;
		/**
		 * Do something at the end of the actions form.
		 *
		 * @since 1.6.0
		 *
		 * @param int $post_id The current post ID.
		 */
		do_action( 'charitable_' . $type . '_actions_end', $post->ID );
		?>
	</div><!-- #charitable-<?php echo esc_html( $type ); ?>-actions-form -->
	<div id="charitable-<?php echo esc_html( $type ); ?>-actions-submit" class="charitable-actions-submit">
		<button type="submit" class="button-primary" title="<?php esc_attr_e( 'Submit', 'charitable' ); ?>"><?php esc_html_e( 'Submit', 'charitable' ); ?></button>
		<div class="clear"></div>
	</div><!-- #charitble-<?php echo esc_html( $type ); ?>-actions-submit -->
</div><!-- #charitable-<?php echo esc_html( $type ); ?>-actions-metabox-wrapper -->
