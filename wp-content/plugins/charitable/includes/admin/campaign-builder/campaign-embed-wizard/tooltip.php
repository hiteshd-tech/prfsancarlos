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
 * @version   1.8.1
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="charitable-admin-campaign-embed-wizard-tooltip">
	<div id="charitable-admin-campaign-embed-wizard-tooltip-content">
		<?php if ( charitable_is_gutenberg_active() ) : // Gutenberg content. ?>
			<h3><?php esc_html_e( 'Add a Campaign', 'charitable' ); ?></h3>
			<p>
				<?php
				printf(
					wp_kses( /* translators: %s - link to the Charitable documentation page. */
						__( 'Click the plus button, search for Campaign Builder, click the block to<br>embed it. <a href="%s" target="_blank" rel="noopener noreferrer">Learn More</a>.', 'charitable' ),
						[
							'a'  => [
								'href'   => [],
								'rel'    => [],
								'target' => [],
							],
							'br' => [],
						]
					),
					'https://www.wpcharitable.com/documentation/'
				);
				?>
			</p>
			<i class="charitable-admin-campaign-embed-wizard-tooltips-red-arrow"></i>
		<button type="button" class="charitable-admin-campaign-embed-wizard-done-btn"><?php esc_html_e( 'Done', 'charitable' ); ?></button>
		<?php endif; ?>
	</div>
</div>
