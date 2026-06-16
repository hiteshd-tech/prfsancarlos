<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Campaign Page Settings metabox.
 *
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @since     1.2.0
 * @version   1.7.0.9
 * @version   1.8.8.6
 */

global $post, $wpdb;

$charitable_description = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . $view_args['description'] . '</span>' : '';

$charitable_campaign_donate_button_text = wp_strip_all_tags( get_post_meta( $post->ID, '_campaign_donate_button_text', true ) );
$charitable_campaign_donate_button_text = trim( $charitable_campaign_donate_button_text ) === '' ? false : $charitable_campaign_donate_button_text;

?>
<div id="charitable-campaign-page-settings-metabox-wrap" class="charitable-metaboxx-wrap">
	<h4 style="margin-top: 0;"><?php echo esc_html__( 'Hide Information', 'charitable' ); ?></h4>
	<div class="charitable-metabox" style="width: 100%">
		<?php

		$charitable_fields = array( 'Amount Donated', 'Number of Donors', 'Percent Raised', 'Time Remaining' );
		foreach ( $charitable_fields as $charitable_field ) :

			$charitable_santitized_field = strtolower( str_replace( ' ', '_', $charitable_field ) );
			$charitable_meta_field       = ( get_post_meta( $post->ID, '_campaign_hide_' . $charitable_santitized_field, true ) );

			?>
		<div style="display: inline-block; width: 24%;">
		<ul style="padding: 0 10px;">
				<?php
				$charitable_checked = ( false !== $charitable_meta_field && is_countable( $charitable_meta_field ) && count( $charitable_meta_field ) > 0 && in_array( 'hide_' . $charitable_santitized_field, $charitable_meta_field, true ) ) ? 'checked="checked"' : false;
				?>
				<li><input type="checkbox" <?php echo esc_attr( $charitable_checked ); ?> name="_campaign_hide_<?php echo esc_attr( $charitable_santitized_field ); ?>[]" value="hide_<?php echo esc_attr( $charitable_santitized_field ); ?>" id="_campaign_hide_<?php echo esc_attr( $charitable_santitized_field ); ?>_<?php echo esc_attr( $charitable_santitized_field ); ?>" /><label for="_campaign_hide_<?php echo esc_attr( $charitable_santitized_field ); ?>_<?php echo esc_attr( $charitable_santitized_field ); ?>"><?php echo esc_html( $charitable_field ); ?></label></li>
		</ul>
		</div>
		<?php endforeach; ?>

	</div>
</div>

<div id="charitable-campaign-min-donation-metabox-wrap" class="charitable-metabox-wrap">
	<h4><?php echo esc_html__( 'Donation Button Text:', 'charitable' ); ?></h4>
	<label class="screen-reader-text" for="campaign_minimum_donation_amount"><?php echo esc_html__( 'Donate', 'charitable' ); ?></label>
	<input type="text" id="campaign_donate_button_text" name="_campaign_donate_button_text"  placeholder="<?php echo esc_html__( 'Donate', 'charitable' ); ?>" value="<?php echo esc_html( $charitable_campaign_donate_button_text ); ?>" />
	<?php echo $charitable_description; // phpcs:ignore ?>
</div>

