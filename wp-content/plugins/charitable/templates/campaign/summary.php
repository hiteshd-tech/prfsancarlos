<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays the campaign summary.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/summary.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

$charitable_campaign = $view_args['campaign'];

/**
 * Do something before the campaign summary is rendered.
 *
 * @since 1.0.0
 *
 * @param Charitable_Campaign $campaign The Campaign instance.
 */
do_action( 'charitable_campaign_summary_before', $charitable_campaign );

?>
<div class="campaign-summary">
	<?php
	/**
	 * Do something while the campaign summary is rendered.
	 *
	 * By default, the following callbacks are run on the
	 * `charitable_campaign_summary` hook:
	 *
	 * - charitable_template_campaign_percentage_raised (priority: 4)
	 * - charitable_template_campaign_donation_summary (priority: 6)
	 * - charitable_template_campaign_donor_count (priority: 8)
	 * - charitable_template_campaign_time_left (priority: 10)
	 * - charitable_template_donate_button (priority: 12)
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Campaign $campaign The Campaign instance.
	 */
	do_action( 'charitable_campaign_summary', $charitable_campaign );
	?>
</div>
<?php
/**
 * Do something after the campaign summary is rendered.
 *
 * @since 1.0.0
 *
 * @param Charitable_Campaign $campaign The Campaign instance.
 */
do_action( 'charitable_campaign_summary_after', $charitable_campaign );
