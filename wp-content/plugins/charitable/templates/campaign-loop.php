<?php
/**
 * Displays the campaign loop.
 *
 * Override this template by copying it to yourtheme/charitable/campaign-loop.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.5.7
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaigns = $view_args['campaigns'];

if ( ! $charitable_campaigns->have_posts() ) :
	return;
endif;

$charitable_loop_class = charitable_campaign_loop_class( $view_args );
$charitable_args       = charitable_campaign_loop_args( $view_args );

/**
 * Add something before the campaign loop.
 *
 * @since   1.5.0
 *
 * @param   WP_Query $campaigns The campaigns.
 * @param   array    $args      Loop args.
 */
do_action( 'charitable_campaign_loop_before', $charitable_campaigns, $charitable_args );

?>
<ol class="<?php echo esc_attr( $charitable_loop_class ); ?>">

<?php
while ( $charitable_campaigns->have_posts() ) :

	$charitable_campaigns->the_post();

	charitable_template( 'campaign-loop/campaign.php', $charitable_args );

endwhile;

wp_reset_postdata();
?>
</ol>
<?php

/**
 * Add something after the campaign loop.
 *
 * @since   1.5.0
 *
 * @param   WP_Query $campaigns The campaigns.
 * @param   array    $args      Loop args.
 */
do_action( 'charitable_campaign_loop_after', $charitable_campaigns, $charitable_args );
