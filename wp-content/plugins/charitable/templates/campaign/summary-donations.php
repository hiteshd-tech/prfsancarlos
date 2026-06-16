<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the campaign's donation summary.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/summary-donations.php
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
$charitable_donation_summary_content = $charitable_campaign->get_donation_summary();

if ( '' !== trim( $charitable_donation_summary_content ) ) :

	?>
<div class="campaign-figures campaign-summary-item">
	<?php echo $charitable_campaign->get_donation_summary(); // phpcs:ignore ?>
</div>

<?php endif; ?>