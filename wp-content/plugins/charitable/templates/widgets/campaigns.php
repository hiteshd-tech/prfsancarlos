<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display a list of campaigns.
 *
 * Override this template by copying it to yourtheme/charitable/widgets/campaigns.php
 *
 * @package Charitable/Templates/Widgets
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.4.18
 * @version 1.8.8.6
 */

$charitable_campaigns      = $view_args['campaigns'];
$charitable_show_thumbnail = isset( $view_args['show_thumbnail'] ) ? $view_args['show_thumbnail'] : true;
$charitable_thumbnail_size = apply_filters( 'charitable_campaign_widget_thumbnail_size', 'medium' );

if ( ! $charitable_campaigns->have_posts() ) :
	return;
endif;

echo $view_args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! empty( $view_args['title'] ) ) :

	echo $view_args['before_title'] . $view_args['title'] . $view_args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

endif;
?>
<ol class="campaigns">
<?php
while ( $charitable_campaigns->have_posts() ) :

	$charitable_campaigns->the_post();

	$charitable_campaign = new Charitable_Campaign( get_the_ID() );
	?>
	<li class="campaign">
		<?php
		if ( $charitable_show_thumbnail && has_post_thumbnail() ) :

			the_post_thumbnail( $charitable_thumbnail_size );

		endif;
		?>
		<h6 class="campaign-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h6>
		<?php if ( ! $charitable_campaign->is_endless() ) : ?>
			<div class="campaign-time-left"><?php echo wp_kses_post( $charitable_campaign->get_time_left() ); ?></div>
		<?php endif ?>
	</li>
	<?php
endwhile;
?>
</ol>
<?php

echo $view_args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

wp_reset_postdata();
