<?php
/**
 * The template for displaying campaign content within loops.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop/campaign.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = charitable_get_current_campaign();

?>
<li id="campaign-<?php echo esc_attr( get_the_ID() ); ?>" <?php post_class(); ?>>
<?php
	/**
	 * Hook: Before campaign content loop.
	 *
	 * @hook charitable_campaign_content_loop_before
	 */
	do_action( 'charitable_campaign_content_loop_before', $charitable_campaign, $view_args );

?>
	<a href="<?php the_permalink(); ?>">
		<?php
			/**
			 * Hook: Before campaign content loop title.
			 *
			 * @hook charitable_campaign_content_loop_before_title
			 */
			do_action( 'charitable_campaign_content_loop_before_title', $charitable_campaign, $view_args );
		?>

		<h3><?php the_title(); ?></h3>

		<?php
			/**
			 * Hook: After campaign content loop title.
			 *
			 * @hook charitable_campaign_content_loop_after_title
			 */
			do_action( 'charitable_campaign_content_loop_after_title', $charitable_campaign, $view_args );
		?>
	</a>
	<?php

	/**
	 * Hook: After campaign content loop.
	 *
	 * @hook charitable_campaign_content_loop_after
	 */
	do_action( 'charitable_campaign_content_loop_after', $charitable_campaign, $view_args );
	?>
</li>
