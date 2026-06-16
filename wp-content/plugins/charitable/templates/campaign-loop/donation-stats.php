<?php
/**
 * Displays the campaign donation stats.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="campaign-donation-stats">
<?php echo $view_args['campaign']->get_donation_summary(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
