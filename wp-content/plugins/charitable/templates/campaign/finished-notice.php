<?php
/**
 * The template for displaying a notice at the top of the campaign
 * summary to announce how long ago it finished.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/finished-notice.php
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
$charitable_notice   = $charitable_campaign->get_finished_notice();

if ( empty( $charitable_notice ) ) :
	return;
endif;

?>
<div class="campaign-finished">
	<?php echo wp_kses_post( $charitable_notice ); ?>
</div>
