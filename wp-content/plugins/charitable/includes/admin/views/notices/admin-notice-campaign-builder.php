<?php
/**
 * Admin notice: Campaign Builder.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 * @version   1.8.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_information_url = 'https://wpcharitable.com/campaign-builder/';

?><div class="charitable-admin-notice-campaign-builder" data-id="campaign-builder">
	<p>
		<?php

			printf(
				'%s <a target="_blank" href="%s" aria-label="%s">%s</a>. %s',
				esc_html__( 'We are pleased to announce the launch of our new Campaign Builder in Charitable v1.8.0. Create impactful campaigns effortlessly using professional templates enhanced with features like photos, progress bars, buttons and your content.  You can', 'charitable' ),
				$charitable_information_url, // phpcs:ignore
				esc_html__( 'Campaign Builder', 'charitable' ),
				esc_html__( 'learn more about the campaign builder here', 'charitable' ),
				esc_html__( 'Try the new builder with your next new campaign!', 'charitable' )
			);

			?>

	</p>

</div>