<?php
/**
 * Admin notice: 5 star review request
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 * @version   1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_feedback_url = 'https://wordpress.org/support/plugin/charitable/reviews/#new-post';

?><div class="charitable-admin-notice-five-star-rating" data-step="1" data-id="five-star-review">
	<p style="margin: 0;">
		<?php esc_html_e( 'Are you enjoying Charitable?', 'charitable' ); ?>
	</p>

	<p style="display: flex; align-items: center; margin: 0; font-size: 14px; line-height: 14px;">
		<button type="button" class="button button-link" data-navigate="3">
			<?php esc_html_e( 'Yes', 'charitable' ); ?>
		</button>
		&nbsp;&bull;&nbsp;
		<button type="button" class="button button-link" data-navigate="2" style="margin-left: 0px;">
			<?php esc_html_e( 'Not really', 'charitable' ); ?>
		</button>
	</p>
</div>

<div class="charitable-admin-notice-five-star-rating" data-step="2" style="display: none;" data-id="five-star-review">
	<p>
		<?php
		esc_html_e(
			'We\'re sorry to hear you aren\'t enjoying WP Charitable. We would love a chance to improve. Could you take a minute and let us know what we can do better?',
			'charitable'
		);
		?>
	</p>

	<p style="display: flex; align-items: center;">
		<a href="<?php echo esc_url( $charitable_feedback_url ); ?>" class="button button-link charitable-notice-dismiss" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Give feedback', 'charitable' ); ?>
		</a>

		<button type="button" class="button button-link charitable-notice-dismiss" style="margin-left: 10px;">
			<?php esc_html_e( 'No thanks', 'charitable' ); ?>
		</button>
	</p>
</div>

<div class="charitable-admin-notice-five-star-rating" data-step="3" style="display: none;" data-id="five-star-review">
	<p>
		<?php
		esc_html_e(
			'That\'s awesome! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our team motivation?',
			'charitable'
		);
		?>
	</p>

	<p>
		<strong>
			<?php
			echo wp_kses(
				__(
					'~ WP Charitable Team',
					'charitable'
				),
				array(
					'br' => array(),
				)
			);
			?>
		</strong>
	</p>

	<p style="display: flex; align-items: center;">
		<a href="https://wordpress.org/support/plugin/charitable/reviews/#new-post" class="button button-link charitable-notice-dismiss" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Ok, you deserve it', 'charitable' ); ?>
		</a>

		<button type="button" class="button button-link charitable-notice-dismiss" style="margin-left: 10px;">
			<?php esc_html_e( 'I already did', 'charitable' ); ?>
		</button>
	</p>
</div>