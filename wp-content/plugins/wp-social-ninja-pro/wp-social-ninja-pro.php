<?php
/*
Plugin Name:  WP Social Ninja Pro
Plugin URI:   https://wpsocialninja.com/
Description:  Display your social feeds, reviews and chat widgets automatically and easily on your website with the all-in-one social media plugin.
Version:      4.0.0
Author:       WPManageNinja LLC
Author URI:   https://wpsocialninja.com/
License:      GPLv2 or later
Text Domain:  wp-social-ninja-pro
Domain Path:  /language
*/

if (defined('WPSOCIALREVIEWS_PRO_MAIN_FILE')) {
    return;
}

define('WPSOCIALREVIEWS_PRO_MAIN_FILE', __FILE__);

require_once('wp-social-ninja-pro-boot.php');

add_action('wp_social_reviews_loaded_v2', function ($app) {
    (new \WPSocialReviewsPro\App\Application($app));
    do_action('wp_social_ninja_pro_loaded', $app);
});

add_action('init', function () {
    load_plugin_textdomain('wp-social-ninja-pro', false, basename(dirname(__FILE__)) . '/language');
});