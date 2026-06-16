<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a benefactors addon metabox. Used by any plugin that utilizes the Benefactors Addon.
 *
 * @since       1.0.0
 * @version     1.8.9.1
 * @version     1.8.8.6
 * @author      David Bisset
 * @package     Charitable/Admin Views/Metaboxes
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 */

global $post;

if ( ! isset( $view_args['extension'] ) ) {
	charitable_get_deprecated()->doing_it_wrong(
		'charitable_campaign_meta_boxes',
		'Campaign benefactors metabox requires an extension argument.',
		'1.0.0'
	);
	return;
}

$charitable_extension   = $view_args['extension'];
$charitable_benefactors = charitable_get_table( 'benefactors' )->get_campaign_benefactors_by_extension( $post->ID, $charitable_extension );
$charitable_ended       = charitable_get_campaign( $post->ID )->has_ended();

?>
<div class="charitable-metabox charitable-metabox-wrap">
	<?php
	if ( empty( $charitable_benefactors ) ) :
		if ( $charitable_ended ) :
		?>
			<p><?php esc_html_e( 'You did not add any contribution rules.', 'charitable' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'You have not added any contribution rules yet.', 'charitable' ); ?></p>
		<?php
		endif;
	else :
		foreach ( $charitable_benefactors as $charitable_benefactor ) :
			$charitable_benefactor_object = Charitable_Benefactor::get_object( $charitable_benefactor, $charitable_extension );

			if ( $charitable_benefactor_object->is_active() ) {
				$charitable_active_class = 'charitable-benefactor-active';
			} elseif ( $charitable_benefactor_object->is_expired() ) {
				$charitable_active_class = 'charitable-benefactor-expired';
			} else {
				$charitable_active_class = 'charitable-benefactor-inactive';
			}
			?>
			<div class="charitable-metabox-block charitable-benefactor <?php echo esc_attr( $charitable_active_class ); ?>">
				<?php do_action( 'charitable_campaign_benefactor_meta_box', $charitable_benefactor_object, $charitable_extension ); ?>
			</div>
			<?php

		endforeach;
	endif;

	charitable_admin_view( 'metaboxes/campaign-benefactors/form', array(
		'benefactor' => null,
		'extension'  => $charitable_extension,
	) );

	if ( ! $charitable_ended ) :
	?>
			<p><a href="#" class="button" data-charitable-toggle="campaign_benefactor__0"><?php esc_html_e( '+ Add New Contribution Rule', 'charitable' ); ?></a></p>
	<?php
	endif;
	?>
</div>
