<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the campaign's donor summary.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/summary-donors.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.8.1.9
 * @version 1.8.8.6
 */

$charitable_campaign = $view_args['campaign'];

if ( ! class_exists( 'Charitable_Campaign' ) || ! $charitable_campaign instanceof Charitable_Campaign ) {
	return;
}

?>
<div class="campaign-donors campaign-summary-item">
	<?php
	printf(
		/* translators: %s: number of donors */
		esc_html_x( '%s Donors', 'number of donors', 'charitable' ),
		'<span class="donors-count">' . esc_html( $charitable_campaign->get_donor_count() ) . '</span>'
	);
	?>
</div>
