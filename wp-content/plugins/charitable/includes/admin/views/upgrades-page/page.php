<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the upgrades page.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Upgrades
 * @since   1.0.0
 * @version 1.8.1
 * @version 1.8.8.6
 */

$page   = $view_args['page']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$action = $page->get_action(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$charitable_step   = $page->get_step();
$charitable_total  = $page->get_total();
$charitable_number = $page->get_number();
$charitable_nonce  = $page->get_nonce();
$charitable_steps  = $page->get_steps( $charitable_total, $charitable_number );
$charitable_args   = array(
	'charitable-upgrade' => $action,
	'page'               => 'charitable-upgrades',
	'step'               => $charitable_step,
	'total'              => $charitable_total,
	'steps'              => $charitable_steps,
	'nonce'              => $charitable_nonce,
);

$charitable_timeout_url  = 'index.php?charitable_action=' . $action;
$charitable_timeout_url .= '&step=' . $charitable_step;

if ( $charitable_total ) {
	$charitable_timeout_url .= '&total=' . $charitable_total;
}

if ( $charitable_nonce ) {
	$charitable_timeout_url .= '&nonce=' . $charitable_nonce;
}


update_option( 'charitable_doing_upgrade', $charitable_args );

if ( $charitable_step > $charitable_steps ) {
	// Prevent a weird case where the estimate was off. Usually only a couple.
	$charitable_steps = $charitable_step;
}
?>
<div class="wrap">
	<h2><?php esc_html_e( 'Charitable - Upgrades', 'charitable' ); ?></h2>

	<div id="charitable-upgrade-status">
		<p><?php esc_html_e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'charitable' ); ?></p>
		<?php if ( ! empty( $charitable_total ) ) : ?>
			<?php // Translators: %1$s is the current step, %2$s is the total number of steps. ?>
			<p><strong><?php printf( esc_html__( 'Step %1$d of approximately %2$d running', 'charitable' ), esc_html( $charitable_step ), esc_html( $charitable_steps ) ); ?></strong></p>
		<?php endif; ?>
	</div>
	<script type="text/javascript">
		setTimeout(function() { document.location.href = "<?php echo esc_url_raw( $charitable_timeout_url ); ?>"; }, 250);
	</script>
</div>
