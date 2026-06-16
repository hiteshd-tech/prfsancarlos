<?php
/**
 * Displays the campaign progress bar.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/progress-bar.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign Page
 * @since   1.0.0
 * @version 1.0.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_campaign = $view_args['campaign'];

if ( ! $charitable_campaign->has_goal() ) :
	return;
endif;

?>
<div class="campaign-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $charitable_campaign->get_percent_donated_raw(); // phpcs:ignore ?>"><span class="bar" style="width: <?php echo $charitable_campaign->get_percent_donated_raw(); // phpcs:ignore ?>%;"></span></div>
