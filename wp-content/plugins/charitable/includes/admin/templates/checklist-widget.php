<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checklist main modal "widget" window template.
 *
 * @since 1.8.1.15
 *
 * @package Charitable/Admin/Templates
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// These are local template variables, not global variables. They are scoped to this template file.
$checklist_class              = Charitable_Checklist::get_instance();
$checklist_state              = $checklist_class->get_checklist_option( 'status' );
$checklist_classes            = $checklist_class->get_steps_css();
$check_list_completed         = $checklist_class->is_checklist_completed() ? 'charitable-checklist-completed' : '';
$check_list_close_button_text = $checklist_class->is_checklist_completed() ? esc_attr__( 'Close checklist', 'charitable' ) : esc_attr__( 'Skip checklist', 'charitable' );

$checklist_stats = $checklist_class->get_steps_stats();
if ( ! is_array( $checklist_stats ) || empty( $checklist_stats ) ) {
	$checklist_stats = [
		'completed' => 0,
		'total'     => 5,
	];
}

$base_class = 'init' === $checklist_state || 'start' === $checklist_state ? 'charitable-checklist-start' : '';
$base_class = $checklist_class->is_checklist_completed() ? 'charitable-checklist-completed' : $base_class;

$start_checklist_url = $checklist_class->get_start_checklist_url();
$next_checklist_url  = $checklist_class->get_next_checklist_url();

$closed_css = $checklist_class->get_checklist_option( 'window_closed' ) ? 'closed' : '';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

?>
<div class="charitable-checklist <?php echo esc_attr( $base_class ); ?>">

	<div class="charitable-checklist-list-block <?php echo esc_attr( $closed_css ); ?>">
		<i class="list-block-button checklist-toggle" title="<?php esc_attr_e( 'Toggle list', 'charitable' ); ?>"></i>
		<i class="list-block-button checklist-skip" title="<?php echo esc_html( $check_list_close_button_text ); ?>"
			data-cancel-title="<?php esc_attr_e( 'Cancel checklist', 'charitable' ); ?>"></i>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %1$d - number of minutes, %2$s - singular or plural form of 'minute'. */
					__( 'Complete the <b>Charitable Checklist</b> and get up and running within %1$s.', 'charitable' ),
					__( 'minutes', 'charitable' )
				),
				[ 'b' => [] ]
			);
			?>
		</p>
		<ul class="charitable-checklist-list">
			<li class="charitable-checklist-step3-item <?php echo esc_attr( $checklist_classes['connect-gateway'] ); ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#connect-gateway' ) ); ?>"><span></span><?php esc_html_e( 'Connect Your Gateway', 'charitable' ); ?></a></li>
			<li class="charitable-checklist-step1-item <?php echo esc_attr( $checklist_classes['general-settings'] ); ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#general-settings' ) ); ?>"><span></span><?php esc_html_e( 'Confirm General Settings', 'charitable' ); ?></a></li>
			<li class="charitable-checklist-step2-item <?php echo esc_attr( $checklist_classes['email-settings'] ); ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#email-settings' ) ); ?>"><span></span><?php esc_html_e( 'Check Email Settings', 'charitable' ); ?></a></li>
			<li class="charitable-checklist-step4-item <?php echo esc_attr( $checklist_classes['first-campaign'] ); ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#first-campaign' ) ); ?>"><span></span><?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?></a></li>
			<?php /* <li class="charitable-checklist-step5-item <?php echo esc_attr( $checklist_classes['first-donation'] ); ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#first-donation' ) ); ?>"><span></span><?php esc_html_e( 'View Your Donations', 'charitable' ); ?></a> */ ?></li>
			<li class="charitable-checklist-step6-item <?php echo esc_attr( $checklist_classes['next-level'] ); ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#next-level' ) ); ?>"><span></span><?php esc_html_e( 'Level Up Your Fundraising', 'charitable' ); ?></a></li>
		</ul>
	</div>

	<div class="charitable-checklist-bar" style="display:none">
		<div></div>
	</div>

	<div class="charitable-checklist-block-timer">
		<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . '/images/charitable-logo.svg' ); ?>" alt="<?php esc_attr_e( 'Charitable', 'charitable' ); ?>">
		<div>
			<h3><?php esc_html_e( 'Charitable Checklist', 'charitable' ); ?></h3>

			<?php

			if ( $checklist_class->is_checklist_completed() ) {

				?>
			<p>
				<?php

				echo esc_html__( 'Congrats! You did it!', 'charitable' );

				?>
			</p>
			<?php } else { ?>
			<p>
				<?php

				echo wp_kses(
					sprintf(
						/* translators: %1$d - number of minutes, %2$s - singular or plural form of 'minute'. */
						__( '%1$s of %2$s completed', 'charitable' ),
						'<span id="charitable-checklist-steps-completed">' . absint( $checklist_stats['completed'] ) . '</span>',
						'<span id="charitable-checklist-steps-total">' . absint( $checklist_stats['total'] ) . '</span>'
					),
					[ 'b' => [] ]
				);

				?>
			</p>
			<?php } ?>
		</div>
	</div>

	<div class="charitable-checklist-block-under-status">
		<?php if ( ! $checklist_class->is_checklist_completed() ) : ?>

			<?php if ( $checklist_class->is_checklist_started() ) : ?>
				<a href="<?php echo esc_url( $next_checklist_url ); ?>" class="charitable-btn charitable-btn-orange">
					<?php esc_html_e( 'Continue', 'charitable' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $start_checklist_url ); ?>" class="charitable-btn charitable-btn-orange">
					<?php esc_html_e( 'Start Checklist', 'charitable' ); ?>
				</a>
			<?php endif; ?>
		<?php else : ?>
			<p>
				<?php
				printf(
					/* translators: %1$s Opening strong tag, do not translate. %2$s Closing strong tag, do not translate. %3$s Opening anchor tag, do not translate. %4$s Closing anchor tag, do not translate. */
					__( 'Please rate %1$sCharitable%2$s %3$s★★★★★%4$s on %3$sWordPress.org%4$s!', 'charitable' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<strong>',
					'</strong>',
					'<a href="https://wordpress.org/support/plugin/charitable/reviews/#new-post" rel="noopener noreferrer" target="_blank">',
					'</a>'
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</div>
