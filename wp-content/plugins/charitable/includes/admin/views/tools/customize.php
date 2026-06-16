<?php
/**
 * Customize tools page.
 *
 * @since 1.8.2
 * @version 1.8.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = Charitable_Campaigns::query(
	array(
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'meta_query'     => array( // phpcs:ignore
			'relation' => 'OR',
			array(
				'key'     => '_campaign_end_date',
				'value'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore
				'compare' => '>=',
				'type'    => 'datetime',
			),
			array(
				'key'     => '_campaign_end_date',
				'value'   => 0,
				'compare' => '=',
			),
		),
	)
);

if ( $charitable_campaign->found_posts ) {
	$charitable_url = charitable_get_permalink(
		'campaign_donation',
		array(
			'campaign_id' => current( $charitable_campaign->posts ),
		)
	);
}

if ( ! isset( $charitable_url ) || false === $charitable_url ) {
	$charitable_url = home_url();
}

$charitable_customize_link = rawurlencode( $charitable_url );
?>
<div class="charitable-customizer">
	<div class="charitable-customizer-section">
		<h2><?php esc_html_e( 'Customize Charitable (Legacy)', 'charitable' ); ?></h2>
		<?php echo '<img width="119" src="' . esc_url( charitable()->get_path( 'assets', false ) . 'images/misc/customizer.png' ) . '" alt="" />'; ?>
		<p>
			<?php
			printf(
				'<p>%s <a target="_blank" href="%s">%s</a> %s</p>',
				esc_html__( 'Previously Charitable had an option in the menu that linked to the ', 'charitable' ),
				esc_url( 'https://www.wpcharitable.com/documentation/customizer-settings/' ),
				esc_html__( 'WordPress Customizer', 'charitable' ),
				esc_html__( 'for a few select settings. That menu link has been moved here.', 'charitable' ),
			);
			?>
		</p>
		<p><?php esc_html_e( 'This link will be depreciated in an upcoming Charitable release.', 'charitable' ); ?></p>
		<p><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=charitable&url=' . $charitable_customize_link ) ); ?>" class="button button-primary"><?php esc_html_e( 'Open WordPress Customizer', 'charitable' ); ?></a></p>
	</div>
</div>
