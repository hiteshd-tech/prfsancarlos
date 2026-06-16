<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the table of emails.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.2 Added checklist querystring value to action_url.
 * @version   1.8.7 Removed cf class from the email settings table.
 * @version   1.8.8.6
 */

$charitable_helper = charitable_get_helper( 'emails' );
$charitable_emails = $charitable_helper->get_available_emails();

if ( count( $charitable_emails ) ) :

	foreach ( $charitable_emails as $charitable_email ) :

		$charitable_email      = new $charitable_email();
		$charitable_is_enabled = $charitable_helper->is_enabled_email( $charitable_email->get_email_id() );
		$charitable_action_url = esc_url(
			add_query_arg(
				array(
					'charitable_action' => $charitable_is_enabled ? 'disable_email' : 'enable_email',
					'email_id'          => $charitable_email->get_email_id(),
					'_nonce'            => wp_create_nonce( 'email' ),
				),
				admin_url( 'admin.php?page=charitable-settings&tab=emails' )
			)
		);
		// if the querystring value of checklist exists then add it to the action_url.
		if ( isset( $_GET['checklist'] ) ) { // phpcs:ignore
			$charitable_action_url .= '&checklist=' . esc_attr( $_GET['checklist'] ); // phpcs:ignore
		}

		?>
		<div class="charitable-settings-object charitable-email">
			<h4><?php echo esc_html( $charitable_email->get_name() ); ?></h4>
			<span class="actions">
				<?php
				if ( $charitable_is_enabled ) :
					$charitable_settings_url = esc_url(
						add_query_arg(
							array(
								'group' => 'emails_' . $charitable_email->get_email_id(),
							),
							admin_url( 'admin.php?page=charitable-settings&tab=emails' )
						)
					);
					?>
					<a href="<?php echo esc_url( $charitable_settings_url ); ?>" class="button button-primary"><?php esc_html_e( 'Email Settings', 'charitable' ); ?></a>
				<?php endif ?>
				<?php if ( ! $charitable_email->is_required() ) : ?>
					<?php if ( $charitable_is_enabled ) : ?>
						<a href="<?php echo esc_url( $charitable_action_url ); ?>" class="button"><?php esc_html_e( 'Disable Email', 'charitable' ); ?></a>
					<?php else : ?>
						<a href="<?php echo esc_url( $charitable_action_url ); ?>" class="button"><?php esc_html_e( 'Enable Email', 'charitable' ); ?></a>
					<?php endif ?>
				<?php endif ?>
			</span>
		</div>
	<?php endforeach ?>
	<?php
else :
	esc_html_e( 'There are no emails available in your system.', 'charitable' );
endif;
