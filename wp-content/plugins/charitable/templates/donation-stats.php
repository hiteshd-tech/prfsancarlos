<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the site donation stats.
 *
 * Override this template by copying it to yourtheme/charitable/donation-stats.php
 *
 * @package Charitable/Templates/Widgets
 * @author  WP Charitable LLC
 * @since   1.5.0
 * @version 1.6.57
 * @version 1.8.8.6
 */

/**
 * Do something before displaying the donation stats.
 *
 * @since 1.6.57
 *
 * @param array $view_args The view arguments.
 */
do_action( 'charitable_donation_stats_before', $view_args );

$charitable_campaigns_count = Charitable_Campaigns::query(
	array(
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
)->found_posts;
$charitable_campaigns_text  = 1 === $charitable_campaigns_count ? __( 'Campaign', 'charitable' ) : __( 'Campaigns', 'charitable' );

/**
 * Filter the donation stats to show.
 *
 * @since 1.5.0
 *
 * @param array $donation_stats The default stats to show.
 * @param array $view_args      All arguments passed to the view.
 */
$charitable_donation_stats = apply_filters(
	'charitable_donation_stats',
	array(
		'campaign_count' => array(
			'amount'      => $charitable_campaigns_count,
			'description' => $charitable_campaigns_text,
		),
		'donated'        => array(
			'amount'      => charitable_format_money( charitable_get_table( 'campaign_donations' )->get_total() ),
			'description' => __( 'Donated', 'charitable' ),
		),
		'donor_count'    => array(
			'amount'      => charitable_get_table( 'donors' )->count_donors_with_donations(),
			'description' => __( 'Donors', 'charitable' ),
		),
	),
	$view_args
);

?>
<ul class="donation-stats">
<?php
foreach ( $charitable_donation_stats as $charitable_stat ) :
	printf( '<li><span class="figure">%s</span> %s</li>', esc_html( $charitable_stat['amount'] ), esc_html( $charitable_stat['description'] ) );
endforeach;
?>
</ul><!-- .donation-stats -->
<?php

/**
 * Do something after displaying the donation stats.
 *
 * @since 1.6.57
 *
 * @param array $view_args The view arguments.
 */
do_action( 'charitable_donation_stats_after', $view_args );