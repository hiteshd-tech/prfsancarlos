<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loops over the meta boxes inside the advanced settings area of the Campaign post type.
 *
 * @author    WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.6.0
 * @version   1.6.0
 * @version   1.8.8.6
 */

global $post;

$charitable_helper = charitable()->registry()->get( 'campaign_meta_boxes' );
$charitable_panels = $charitable_helper->get_campaign_settings_panels();

?>
<div id="charitable-campaign-advanced-metabox" class="charitable-metabox charitable-campaign-settings">
	<ul class="charitable-tabs">
		<?php foreach ( $charitable_panels as $id => $charitable_panel ) : ?>
			<li><a href="<?php echo esc_attr( sprintf( '#%s', $id ) ); ?>"><?php echo wp_kses_post( $charitable_panel['title'] ); ?></a></li>
		<?php endforeach ?>
	</ul>
	<?php foreach ( $charitable_panels as $id => $charitable_panel ) : ?>
		<div id="<?php echo esc_attr( $id ); ?>" class="charitable-campaign-settings-panel">
			<?php
			if ( ! array_key_exists( 'view', $charitable_panel ) ) :
				$charitable_panel['view'] = 'metaboxes/campaign-settings/panel';
			endif;

			$charitable_helper->get_meta_box_helper()->metabox_display( $post, array( 'args' => $charitable_panel ) );
			?>
		</div><!-- #<?php echo esc_attr( $id ); ?> -->
	<?php endforeach ?>
</div><!-- #charitable-campaign-advanced-metabox -->
