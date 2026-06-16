<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the About Us tab content.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/About
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.8
 * @version   1.8.8
 */

ob_start();
?>

<div class="charitable-admin-about-section charitable-admin-columns">

	<div class="charitable-admin-column-60">
		<h3>
			<?php esc_html_e( 'Hello and welcome to Charitable, the most powerful and flexible WordPress donation and fundraising plugin. At Charitable, we believe raising funds online should be simple, affordable, and accessible to everyone—whether you’re a small nonprofit, a local community group, or a global charity.', 'charitable' ); ?>
		</h3>
		<p>
					<?php esc_html_e( 'When we looked around, we noticed most WordPress donation plugins were limited, clunky, or full of expensive add-ons. So we set out with a clear mission: build a WordPress fundraising plugin that’s easy to use, packed with features, and gives you complete freedom—without costly transaction fees.', 'charitable' ); ?>
					<?php esc_html_e( 'Our goal is to empower you to raise more money online, keep more of what you raise, and run campaigns the way you want.', 'charitable' ); ?>
					<?php esc_html_e( 'Charitable is proudly built by a passionate team that’s been part of the WordPress community for years. Thousands of nonprofits around the world already trust Charitable to power their fundraising.', 'charitable' ); ?>
					<?php esc_html_e( 'We’re here to help you make a bigger impact—one donation at a time.', 'charitable' ); ?>
		<p>
			<?php
			printf(
				wp_kses( /* translators: %1$s - WP Charitable URL, %2$s - WP Charitable URL. */
					__( 'Charitable is brought to you by the team at <a href="%1$s" target="_blank" rel="noopener noreferrer">WP Charitable</a>, dedicated to creating the best WordPress donation and fundraising tools for nonprofits, charities, and organizations worldwide.', 'charitable' ),
					[
						'a' => [
							'href'   => [],
							'rel'    => [],
							'target' => [],
						],
					]
				),
				'https://wpcharitable.com',
				'https://wpcharitable.com'
			);
			?>
		</p>
		<p>
			<?php esc_html_e( 'We know a thing or two about building awesome fundraising tools that help organizations make a difference.', 'charitable' ); ?>
		</p>
	</div>

	<div class="charitable-admin-column-40 charitable-admin-column-last">
		<figure>
			<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/about/team.jpg' ); ?>" alt="<?php esc_attr_e( 'The Charitable Team photo', 'charitable' ); ?>">
			<figcaption>
				<?php esc_html_e( 'The Charitable Team', 'charitable' ); ?><br>
			</figcaption>
		</figure>
	</div>

</div>

<?php
// Add the addons section
charitable_admin_view( 'about/addons' );
?>

<?php
echo ob_get_clean(); // phpcs:ignore
