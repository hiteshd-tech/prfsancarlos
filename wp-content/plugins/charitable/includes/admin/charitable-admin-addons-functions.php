<?php
/**
 * Charitable Admin Addons helper functions.
 *
 * @package   Charitable/Admin Functions
 * @author    David Bisset
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 * @version   1.8.7.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: charitable_can_install() and charitable_can_activate() are already defined in charitable-core-admin-functions.php

/**
 * Get the list of AM plugins that we propose to install.
 *
 * @since 1.8.7.6
 *
 * @return array
 */
function charitable_get_am_plugins() {
	$images_url = charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/';

	return array(
		'optinmonster/optin-monster-wp-api.php'        => array(
			'icon'  => $images_url . 'om.png',
			'name'  => esc_html__( 'OptinMonster', 'charitable' ),
			'desc'  => esc_html__( 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/optinmonster/',
			'url'   => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
		),
		'google-analytics-for-wordpress/googleanalytics.php' => array(
			'icon'  => $images_url . 'mi.png',
			'name'  => esc_html__( 'MonsterInsights', 'charitable' ),
			'desc'  => esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/google-analytics-for-wordpress/',
			'url'   => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
			'pro'   => array(
				'plug' => 'google-analytics-premium/googleanalytics-premium.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-mi.png',
				'name' => esc_html__( 'MonsterInsights Pro', 'charitable' ),
				'desc' => esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'charitable' ),
				'url'  => 'https://www.monsterinsights.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'wp-mail-smtp/wp_mail_smtp.php'                => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-smtp.png',
			'name'  => esc_html__( 'WP Mail SMTP', 'charitable' ),
			'desc'  => esc_html__( "Improve your WordPress email deliverability and make sure that your website emails reach user's inbox with the #1 SMTP plugin for WordPress. Over 3 million websites use it to fix WordPress email issues.", 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/wp-mail-smtp/',
			'url'   => 'https://downloads.wordpress.org/plugin/wp-mail-smtp.zip',
			'pro'   => array(
				'plug' => 'wp-mail-smtp-pro/wp_mail_smtp.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-smtp.png',
				'name' => esc_html__( 'WP Mail SMTP Pro', 'charitable' ),
				'desc' => esc_html__( "Improve your WordPress email deliverability and make sure that your website emails reach user's inbox with the #1 SMTP plugin for WordPress. Over 3 million websites use it to fix WordPress email issues.", 'charitable' ),
				'url'  => 'https://wpmailsmtp.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'all-in-one-seo-pack/all_in_one_seo_pack.php'  => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-aioseo.png',
			'name'  => esc_html__( 'AIOSEO', 'charitable' ),
			'desc'  => esc_html__( "The original WordPress SEO plugin and toolkit that improves your website's search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.", 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/all-in-one-seo-pack/',
			'url'   => 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip',
			'pro'   => array(
				'plug' => 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-aioseo.png',
				'name' => esc_html__( 'AIOSEO Pro', 'charitable' ),
				'desc' => esc_html__( "The original WordPress SEO plugin and toolkit that improves your website's search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.", 'charitable' ),
				'url'  => 'https://aioseo.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'coming-soon/coming-soon.php'                  => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-seedprod.png',
			'name'  => esc_html__( 'SeedProd', 'charitable' ),
			'desc'  => esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/coming-soon/',
			'url'   => 'https://downloads.wordpress.org/plugin/coming-soon.zip',
			'pro'   => array(
				'plug' => 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-seedprod.png',
				'name' => esc_html__( 'SeedProd Pro', 'charitable' ),
				'desc' => esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'charitable' ),
				'url'  => 'https://www.seedprod.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'rafflepress/rafflepress.php'                  => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-rp.png',
			'name'  => esc_html__( 'RafflePress', 'charitable' ),
			'desc'  => esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/rafflepress/',
			'url'   => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
			'pro'   => array(
				'plug' => 'rafflepress-pro/rafflepress-pro.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-rp.png',
				'name' => esc_html__( 'RafflePress Pro', 'charitable' ),
				'desc' => esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'charitable' ),
				'url'  => 'https://rafflepress.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'pushengage/main.php'                          => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-pushengage.png',
			'name'  => esc_html__( 'PushEngage', 'charitable' ),
			'desc'  => esc_html__( 'Connect with your visitors after they leave your website with the leading web push notification software. Over 10,000+ businesses worldwide use PushEngage to send 15 billion notifications each month.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/pushengage/',
			'url'   => 'https://downloads.wordpress.org/plugin/pushengage.zip',
		),
		'instagram-feed/instagram-feed.php'            => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-instagram.png',
			'name'  => esc_html__( 'Smash Balloon Instagram Feeds', 'charitable' ),
			'desc'  => esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/instagram-feed/',
			'url'   => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
			'pro'   => array(
				'plug' => 'instagram-feed-pro/instagram-feed.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-instagram.png',
				'name' => esc_html__( 'Smash Balloon Instagram Feeds Pro', 'charitable' ),
				'desc' => esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'charitable' ),
				'url'  => 'https://smashballoon.com/instagram-feed/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'custom-facebook-feed/custom-facebook-feed.php' => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-fb.png',
			'name'  => esc_html__( 'Smash Balloon Facebook Feeds', 'charitable' ),
			'desc'  => esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/custom-facebook-feed/',
			'url'   => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
			'pro'   => array(
				'plug' => 'custom-facebook-feed-pro/custom-facebook-feed.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-fb.png',
				'name' => esc_html__( 'Smash Balloon Facebook Feeds Pro', 'charitable' ),
				'desc' => esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'charitable' ),
				'url'  => 'https://smashballoon.com/custom-facebook-feed/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'feeds-for-youtube/youtube-feed.php'           => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-youtube.png',
			'name'  => esc_html__( 'Smash Balloon YouTube Feeds', 'charitable' ),
			'desc'  => esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/feeds-for-youtube/',
			'url'   => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
			'pro'   => array(
				'plug' => 'youtube-feed-pro/youtube-feed.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-youtube.png',
				'name' => esc_html__( 'Smash Balloon YouTube Feeds Pro', 'charitable' ),
				'desc' => esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'charitable' ),
				'url'  => 'https://smashballoon.com/youtube-feed/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'custom-twitter-feeds/custom-twitter-feed.php' => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-twitter.png',
			'name'  => esc_html__( 'Smash Balloon Twitter Feeds', 'charitable' ),
			'desc'  => esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/custom-twitter-feeds/',
			'url'   => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
			'pro'   => array(
				'plug' => 'custom-twitter-feeds-pro/custom-twitter-feed.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sb-twitter.png',
				'name' => esc_html__( 'Smash Balloon Twitter Feeds Pro', 'charitable' ),
				'desc' => esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'charitable' ),
				'url'  => 'https://smashballoon.com/custom-twitter-feeds/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'trustpulse-api/trustpulse.php'                => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-trustpulse.png',
			'name'  => esc_html__( 'TrustPulse', 'charitable' ),
			'desc'  => esc_html__( 'Boost your sales and conversions by up to 15% with real-time social proof notifications. TrustPulse helps you show live user activity and purchases to help convince other users to purchase.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/trustpulse-api/',
			'url'   => 'https://downloads.wordpress.org/plugin/trustpulse-api.zip',
		),
		'searchwp/index.php'                           => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-searchwp.png',
			'name'  => esc_html__( 'SearchWP', 'charitable' ),
			'desc'  => esc_html__( 'The most advanced WordPress search plugin. Customize your WordPress search algorithm, reorder search results, track search metrics, and everything you need to leverage search to grow your business.', 'charitable' ),
			'wporg' => false,
			'url'   => 'https://searchwp.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
			'act'   => 'go-to-url',
		),
		'affiliate-wp/affiliate-wp.php'                => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-affwp.png',
			'name'  => esc_html__( 'AffiliateWP', 'charitable' ),
			'desc'  => esc_html__( 'The #1 affiliate management plugin for WordPress. Easily create an affiliate program for your eCommerce store or membership site within minutes and start growing your sales with the power of referral marketing.', 'charitable' ),
			'wporg' => false,
			'url'   => 'https://affiliatewp.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
			'act'   => 'go-to-url',
		),
		'stripe/stripe-checkout.php'                   => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-wp-simple-pay.png',
			'name'  => esc_html__( 'WP Simple Pay', 'charitable' ),
			'desc'  => esc_html__( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/stripe/',
			'url'   => 'https://downloads.wordpress.org/plugin/stripe.zip',
			'pro'   => array(
				'plug' => 'wp-simple-pay-pro-3/simple-pay.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-wp-simple-pay.png',
				'name' => esc_html__( 'WP Simple Pay Pro', 'charitable' ),
				'desc' => esc_html__( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'charitable' ),
				'url'  => 'https://wpsimplepay.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'easy-digital-downloads/easy-digital-downloads.php' => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-edd.png',
			'name'  => esc_html__( 'Easy Digital Downloads', 'charitable' ),
			'desc'  => esc_html__( 'The best WordPress eCommerce plugin for selling digital downloads. Start selling eBooks, software, music, digital art, and more within minutes. Accept payments, manage subscriptions, advanced access control, and more.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/easy-digital-downloads/',
			'url'   => 'https://downloads.wordpress.org/plugin/easy-digital-downloads.zip',
		),
		'sugar-calendar-lite/sugar-calendar-lite.php'  => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sugarcalendar.png',
			'name'  => esc_html__( 'Sugar Calendar', 'charitable' ),
			'desc'  => esc_html__( 'A simple & powerful event calendar plugin for WordPress that comes with all the event management features including payments, scheduling, timezones, ticketing, recurring events, and more.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/sugar-calendar-lite/',
			'url'   => 'https://downloads.wordpress.org/plugin/sugar-calendar-lite.zip',
			'pro'   => array(
				'plug' => 'sugar-calendar/sugar-calendar.php',
				'icon' => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-sugarcalendar.png',
				'name' => esc_html__( 'Sugar Calendar Pro', 'charitable' ),
				'desc' => esc_html__( 'A simple & powerful event calendar plugin for WordPress that comes with all the event management features including payments, scheduling, timezones, ticketing, recurring events, and more.', 'charitable' ),
				'url'  => 'https://sugarcalendar.com/?utm_source=charitableplugin&utm_medium=link&utm_campaign=About%20Charitable',
				'act'  => 'go-to-url',
			),
		),
		'insert-headers-and-footers/ihaf.php'          => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-wpcode.png',
			'name'  => esc_html__( 'WPCode', 'charitable' ),
			'desc'  => esc_html__( 'Future proof your WordPress customizations with the most popular code snippet management plugin for WordPress. Trusted by over 1,500,000+ websites for easily adding code to WordPress right from the admin area.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/insert-headers-and-footers/',
			'url'   => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
		),
		'duplicator/duplicator.php'                    => array(
			'icon'  => charitable()->get_path( 'assets', false ) . 'images/plugins/third-party/plugin-duplicator.png',
			'name'  => esc_html__( 'Duplicator', 'charitable' ),
			'desc'  => esc_html__( 'Leading WordPress backup & site migration plugin. Over 1,500,000+ smart website owners use Duplicator to make reliable and secure WordPress backups to protect their websites. It also makes website migration really easy.', 'charitable' ),
			'wporg' => 'https://wordpress.org/plugins/duplicator/',
			'url'   => 'https://downloads.wordpress.org/plugin/duplicator.zip',
		),
	);
}

/**
 * Get AM plugin data to display in the Addons section of About tab.
 *
 * @since 1.8.7.6
 *
 * @param string $plugin      Plugin slug.
 * @param array  $details     Plugin details.
 * @param array  $all_plugins List of all plugins.
 *
 * @return array
 */
function charitable_get_plugin_data( $plugin, $details, $all_plugins ) {
	$have_pro = ( ! empty( $details['pro'] ) && ! empty( $details['pro']['plug'] ) );
	$show_pro = false;

	$plugin_data = array();

	if ( $have_pro ) {
		// Only promote Pro if the Pro plugin is installed (exists in the plugins list).
		if ( array_key_exists( $details['pro']['plug'], $all_plugins ) ) {
			$show_pro = true;
		}
		if ( $show_pro ) {
			$plugin  = $details['pro']['plug'];
			$details = $details['pro'];
		}
	}

	if ( array_key_exists( $plugin, $all_plugins ) ) {
		if ( is_plugin_active( $plugin ) ) {
			// Status text/status.
			$plugin_data['status_class'] = 'status-active';
			$plugin_data['status_text']  = esc_html__( 'Active', 'charitable' );
			// Button text/status.
			$plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-secondary disabled';
			$plugin_data['action_text']  = esc_html__( 'Activated', 'charitable' );
			$plugin_data['plugin_src']   = esc_attr( $plugin );
		} else {
			// Status text/status.
			$plugin_data['status_class'] = 'status-installed';
			$plugin_data['status_text']  = esc_html__( 'Inactive', 'charitable' );
			// Button text/status.
			$plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-secondary';
			$plugin_data['action_text']  = esc_html__( 'Activate', 'charitable' );
			$plugin_data['plugin_src']   = esc_attr( $plugin );
		}
	} else {
		// Doesn't exist, install.
		// Status text/status.
		$plugin_data['status_class'] = 'status-missing';

		if ( isset( $details['act'] ) && 'go-to-url' === $details['act'] ) {
			$plugin_data['status_class'] = 'status-go-to-url';
		}
		$plugin_data['status_text'] = esc_html__( 'Not Installed', 'charitable' );
		// Button text/status.
		$plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-primary';
		$plugin_data['action_text']  = esc_html__( 'Install Plugin', 'charitable' );
		$plugin_data['plugin_src']   = esc_url( $details['url'] );
	}

	$plugin_data['details'] = $details;

	return $plugin_data;
}

/**
 * Install a WordPress.org plugin.
 *
 * @since 1.8.7.6
 * @version 1.8.9.1
 *
 * @param string $plugin Plugin slug or URL.
 * @return WP_Error|true
 */
function charitable_install_wporg_plugin( $plugin ) {
	// Include required files.
	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'unzip_file' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	// For plugins_api.
	if ( ! function_exists( 'plugins_api' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	}

	// Download the plugin.
	$download_link = $plugin;

	if ( ! filter_var( $plugin, FILTER_VALIDATE_URL ) ) {
		// Normalize slug if a basename like "slug/plugin.php" was passed.
		$slug = $plugin;
		if ( strpos( $slug, '/' ) !== false ) {
			$parts = explode( '/', $slug );
			$slug  = reset( $parts );
		}
		$slug = str_replace( '.php', '', $slug );

		// Get download URL from WordPress.org.
		$api = plugins_api( 'plugin_information', array(
			'slug'   => $slug,
			'fields' => array( 'download_link' => true ),
		) );

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		$download_link = $api->download_link;
	}

	// Download the plugin.
	$download = download_url( $download_link );
	if ( is_wp_error( $download ) ) {
		return $download;
	}

	// Unzip the file.
	$unzip = unzip_file( $download, WP_PLUGIN_DIR );
	if ( is_wp_error( $unzip ) ) {
		unlink( $download ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		return $unzip;
	}

	// Clean up the zip file.
	unlink( $download ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

	return true;
}

/**
 * Activate a WordPress.org plugin.
 *
 * @since 1.8.7.6
 *
 * @param string $plugin Plugin basename.
 * @return WP_Error|true
 */
function charitable_activate_wporg_plugin( $plugin ) {
	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$result = activate_plugin( $plugin );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return true;
}

/**
 * Deactivate a WordPress.org plugin.
 *
 * @since 1.8.7.6
 *
 * @param string $plugin Plugin basename.
 * @return WP_Error|true
 */
function charitable_deactivate_wporg_plugin( $plugin ) {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$result = deactivate_plugins( $plugin );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return true;
}

/**
 * Get plugin basename from slug.
 *
 * @since 1.8.7.6
 * @version 1.8.9.1
 *
 * @param string $plugin Plugin slug.
 * @return string
 */
function charitable_get_plugin_basename_from_slug( $plugin ) {
	$all_plugins = get_plugins();

	// If a full URL was provided (e.g. downloads.wordpress.org/plugin/slug.zip), derive slug from URL.
	if ( filter_var( $plugin, FILTER_VALIDATE_URL ) ) {
		$path = wp_parse_url( $plugin, PHP_URL_PATH );
		$base = $path ? basename( $path ) : '';
		$slug = $base ? preg_replace( '/\.zip$/', '', $base ) : '';
		if ( $slug ) {
			$plugin = $slug;
		}
	}

	// If a "folder/file.php" was provided, reduce to folder (slug).
	if ( strpos( $plugin, '/' ) !== false ) {
		$plugin = explode( '/', $plugin )[0];
	}

	foreach ( $all_plugins as $basename => $plugin_data ) {
		if ( strpos( $basename, $plugin ) === 0 ) {
			return $basename;
		}
	}

	return $plugin;
}