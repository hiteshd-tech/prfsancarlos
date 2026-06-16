<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main donors page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.5
 */

?>
<div class="wrap">

	<div class="charitable-education-page">

		<div class="charitable-education-page-heading">
			<h4><?php esc_html_e( 'Donors', 'charitable' ); ?></h4>
			<p>
				<?php esc_html_e( 'Manage your donors efficiently and effectively with our powerful donor management system. With this feature, you can view a donor\'s donations, send custom one-to-one emails, add addresses and emails, and more.', 'charitable' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'By having all your donor information in one place, you\'ll be able to build stronger relationships with your supporters, increase donor retention, and streamline your fundraising process.', 'charitable' ); ?>
			</p>
		</div>

		<div class="charitable-education-page-media">
			<div class="charitable-education-page-images">
				<figure>
					<div class="charitable-education-page-images-image">
						<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donors/education-1.png" alt="<?php esc_html_e( 'Donor Management', 'charitable' ); ?>">
						<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donors/education-1.png" class="hover" data-lity="" data-lity-desc="<?php esc_html_e( 'Donor Management', 'charitable' ); ?>"></a>
					</div>
					<figcaption><?php esc_html_e( 'Donor Management', 'charitable' ); ?></figcaption>
				</figure>
				<figure>
					<div class="charitable-education-page-images-image">
						<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donors/education-2.png" alt="<?php esc_html_e( 'Address Autocomplete Field', 'charitable' ); ?>">
						<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donors/education-2.png" class="hover" data-lity="" data-lity-desc="<?php esc_html_e( 'Address Autocomplete Field', 'charitable' ); ?>"></a>
					</div>
					<figcaption><?php esc_html_e( 'View Donation History', 'charitable' ); ?></figcaption>
				</figure>
				<figure>
					<div class="charitable-education-page-images-image">
						<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donors/education-3.png" alt="<?php esc_html_e( 'Smart Address Field', 'charitable' ); ?>">
						<a href="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/education/donors/education-3.png" class="hover" data-lity="" data-lity-desc="<?php esc_html_e( 'Donor Management', 'charitable' ); ?>"></a>
					</div>
					<figcaption><?php esc_html_e( 'Send Custom One-to-One Emails', 'charitable' ); ?></figcaption>
				</figure>
			</div>
		</div>

		<div class="charitable-education-page-caps">
			<p><?php esc_html_e( 'Powerful features for managing your donors...', 'charitable' ); ?></p>
			<ul>
				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'View Donor\'s Donations', 'charitable' ); ?>
				</li>
				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'Send Custom One-to-One Emails', 'charitable' ); ?>
				</li>
				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'Add Addresses And Emails', 'charitable' ); ?>
				</li>
				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'Download Donation Receipts', 'charitable' ); ?></li>

				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'Quick Access To Annual Receipts', 'charitable' ); ?>
				</li>
				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'Search Donors', 'charitable' ); ?>
				</li>
				<li><i class="fa fa-solid fa-check"></i>
					<?php esc_html_e( 'Export Donors', 'charitable' ); ?>
				</li>
			</ul>
		</div>

		<?php if ( ! charitable_is_pro() ) : ?>
			<div class="charitable-education-page-button">
				<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-upgrade/', 'donors', 'Upgrade to CharitablePro' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Upgrade to Charitable Pro', 'charitable' ); ?></a>
			</div>
		<?php else : ?>
			<div class="charitable-education-page-button">
				<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/account/', 'donors', 'Download Charitable Pro' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Download Charitable Pro', 'charitable' ); ?></a>
			</div>

			<div class="charitable-education-page-disabled-download">
				<p>
				<?php
				printf(
					/* translators: %s: Documentation URL */
					esc_html__( 'This feature is only available in Charitable Pro. Please %s on how to download Charitable Pro.', 'charitable' ),
					'<a href="https://wpcharitable.com/docs/download-charitable-pro/" target="_blank">' . esc_html__( 'see our documentation', 'charitable' ) . '</a>'
				);
				?>
				</p>
			</div>

		<?php endif; ?>

	</div>

</div>
