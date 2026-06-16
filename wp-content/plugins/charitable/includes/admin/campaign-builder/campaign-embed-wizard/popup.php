<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campaign Embed Wizard.
 * Embed popup HTML template.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 * @version   1.8.8.6
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}
$charitable_pages_exists = ! empty( $args['dropdown_pages'] ) ? 1 : 0;

?>

<div id="charitable-admin-campaign-embed-wizard-container" class="charitable-admin-popup-container">
	<div id="charitable-admin-campaign-embed-wizard" class="charitable-admin-popup" data-pages-exists="<?php echo esc_attr( $charitable_pages_exists ); ?>">
		<div class="charitable-admin-popup-content">
			<h3><?php esc_html_e( 'Embed in a Page', 'charitable' ); ?></h3>
			<div id="charitable-admin-campaign-embed-wizard-content-initial">
				<p class="no-gap"><b><?php esc_html_e( 'We can help embed your campaign with just a few clicks!', 'charitable' ); ?></b></p>

				<?php if ( ! empty( $args['user_can_edit_pages'] ) ) : ?>
					<p><?php esc_html_e( 'Would you like to embed your campaign in an existing page, or create a new one?', 'charitable' ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $args['user_can_edit_pages'] ) ) : ?>
				<div id="charitable-admin-campaign-embed-wizard-content-select-page" style="display: none;">
					<p><?php esc_html_e( 'Select the page you would like to embed your campaign in.', 'charitable' ); ?></p>
				</div>
				<div id="charitable-admin-campaign-embed-wizard-content-create-page" style="display: none;">
					<p><?php esc_html_e( 'What would you like to call the new page?', 'charitable' ); ?></p>
				</div>
				<div id="charitable-admin-campaign-embed-wizard-section-btns" class="charitable-admin-popup-bottom">
					<button type="button" data-action="select-page" class="charitable-admin-popup-btn"><?php esc_html_e( 'Select Existing Page', 'charitable' ); ?></button>
					<button type="button" data-action="create-page" class="charitable-admin-popup-btn"><?php esc_html_e( 'Create New Page', 'charitable' ); ?></button>
				</div>
				<div id="charitable-admin-campaign-embed-wizard-section-go" class="charitable-admin-popup-bottom charitable-admin-popup-flex" style="display: none;">
					<?php echo $args['dropdown_pages']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="text" id="charitable-admin-campaign-embed-wizard-new-page-title" value="" placeholder="<?php esc_attr_e( 'Name Your Page', 'charitable' ); ?>">
					<button type="button" data-action="go" class="charitable-admin-popup-btn"><?php esc_html_e( 'Let’s Go!', 'charitable' ); ?></button>
				</div>
			<?php endif; ?>
			<div id="charitable-admin-campaign-embed-wizard-section-toggles" class="charitable-admin-popup-bottom">
				<p class="secondary">
					<?php
					printf(
						wp_kses( /* translators: %1$s - Video tutorial toggle CSS classes, %2$s - shortcode toggle CSS classes. */
							__( 'You can also <a href="#" class="%1$s">embed your campaign manually</a> or <a href="#" class="%2$s">use a shortcode</a>', 'charitable' ),
							[
								'a' => [
									'href'  => [],
									'class' => [],
								],
							]
						),
						'tutorial-toggle charitable-admin-popup-toggle',
						'shortcode-toggle charitable-admin-popup-toggle'
					);
					?>
				</p>
				<iframe style="display: none;" src="about:blank" frameborder="0" id="charitable-admin-campaign-embed-wizard-tutorial" allowfullscreen width="450" height="256"></iframe>
				<div id="charitable-admin-campaign-embed-wizard-shortcode-wrap" style="display: none;">
					<input type="text" id="charitable-admin-campaign-embed-wizard-shortcode" class="charitable-admin-popup-shortcode" disabled />
					<span id="charitable-admin-campaign-embed-wizard-shortcode-copy" title="<?php esc_attr_e( 'Copy embed code to clipboard', 'charitable' ); ?>">
						<i class="fa fa-files-o" aria-hidden="true"></i>
					</span>
				</div>
			</div>
			<div id="charitable-admin-campaign-embed-wizard-section-goback" class="charitable-admin-popup-bottom" style="display: none;">
				<p class="secondary">
					<a href="#" class="charitable-admin-popup-toggle initialstate-toggle">« <?php esc_html_e( 'Go back', 'charitable' ); ?></a>
				</p>
			</div>
		</div>
		<i class="fa fa-times charitable-admin-popup-close"></i>
	</div>
</div>
