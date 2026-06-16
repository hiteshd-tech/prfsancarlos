<?php

/**
 * All registered action's handlers should be in app\Hooks\Handlers,
 * addAction is similar to add_action and addCustomAction is just a
 * wrapper over add_action which will add a prefix to the hook name
 * using the plugin slug to make it unique in all wordpress plugins,
 * ex: $app->addCustomAction('foo', ['FooHandler', 'handleFoo']) is
 * equivalent to add_action('slug-foo', ['FooHandler', 'handleFoo']).
 */

/**
 * @var $app WPSocialReviewsPro\App\Application
 */

// Init the platform on plugin load
(new \WPSocialReviewsPro\App\Hooks\Handlers\PlatformHandlerPro())->register();
(new \WPSocialReviewsPro\App\Services\License())->init();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking for admin page context, not processing form data
if (isset($_GET['page']) && $_GET['page'] == 'wpsocialninja.php' && is_admin()) {
    $app->addAction('admin_enqueue_scripts', 'WPSocialReviewsPro\App\Hooks\Handlers\ScriptsHandler@loadSwiperScripts');
}

$app->addAction('wp_social_review_loading_layout_masonry', function ($templateId) {
    wp_enqueue_script('imagesloaded');
    wp_enqueue_script('jquery-masonry');
    do_action('wp_social_ninja_add_layout_script');
});

$app->addAction('wp_social_review_loading_layout_carousel', function ($templateId) {
    (new \WPSocialReviewsPro\App\Hooks\Handlers\ScriptsHandler())->loadSwiperScripts();
    do_action('wp_social_ninja_add_layout_script');
});


$app->addAction('wpsocialreviews/render_reviews_template_business_info', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@renderReviewsTemplateBusinessInfo', 10, 5);
$app->addAction('wpsocialreviews/render_reviews_write_a_review_btn', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@renderReviewsWriteaReviewBtn', 10, 5);
$app->addAction('wpsocialreviews/review_images', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@renderReviewImages', 10, 2);

$app->addAction('wp_ajax_wpsr_export_data', 'WPSocialReviewsPro\App\Services\ImportExportHandler@exportData');
$app->addAction('wp_ajax_wpsr_import_data', 'WPSocialReviewsPro\App\Services\ImportExportHandler@importData');


$app->addAction('wpsocialreviews/tweeter_profile_banner', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterProfileBannerHtml', 10, 2);
$app->addAction('wpsocialreviews/tweeter_user_profile_picture', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserProfilePictureHtml', 10, 2);
$app->addAction('wpsocialreviews/tweeter_user_profile_follow_btn', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserProfileFollowBtnHtml', 10, 2);
$app->addAction('wpsocialreviews/tweeter_user_profile_info', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserProfileInfoNameHtml', 5, 2);
$app->addAction('wpsocialreviews/tweeter_user_profile_info', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserProfileInfoUsernameHtml', 10, 2);
$app->addAction('wpsocialreviews/tweeter_user_profile_description', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserProfileDescriptionHtml', 10, 2);
$app->addAction('wpsocialreviews/tweeter_user_address', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserAddressHtml', 10, 2);
$app->addAction('wpsocialreviews/tweeter_user_profile_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTweeterUserProfileStatisticsHtml', 10, 3);
$app->addAction('wp_ajax_nopriv_wpsr_twitter_cards', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@generateTwitterCards');
$app->addAction('wp_ajax_wpsr_twitter_cards', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@generateTwitterCards');


$app->addAction('wpsocialreviews/youtube_channel_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@renderChannelStatisticsHtml', 10, 3);
$app->addAction('wpsocialreviews/youtube_channel_subscribe_btn', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@renderYoutubeChannelSubscribeBtnHtml', 10, 2);
$app->addAction('wpsocialreviews/youtube_feed_description', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@renderYoutubeFeedDescriptionHtml', 10, 2);
$app->addAction('wpsocialreviews/youtube_feed_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@renderYoutubeFeedStatisticsHtml', 10, 5);
$app->addAction('wpsocialreviews/youtube_popup_content', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@popupContentHtml', 10, 3);
$app->addAction('wpsocialreviews/youtube_channel_description', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@renderChannelDescriptionHtml', 10, 2);
$app->addAction('wpsocialreviews/render_youtube_prev_next_pagination', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@renderYoutubePrevNextPagination', 10, 4);


$app->addAction('wpsocialreviews/instagram_post_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@renderInstagramPostStatisticsHtml', 10, 2);
$app->addAction('wpsocialreviews/instagram_shoppable_button', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@renderInstagramShoppableButtonHtml', 10, 2);
$app->addAction('wpsocialreviews/instagram_follow_button', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@renderInstagramFollowButtonHtml');
$app->addAction('wpsocialreviews/instagram_header_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@renderInstagramHeaderStatisticsHtml', 10, 3);
$app->addAction('wpsocialreviews/instagram_shoppable_icon', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@renderInstagramShoppableIcon');



$app->addAction('wpsocialreviews/facebook_feed_like_button', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedLikeButtonHtml', 10, 2);
$app->addAction('wpsocialreviews/facebook_feed_share_button', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedShareButtonHtml', 10, 2);
$app->addAction('wpsocialreviews/facebook_feed_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedStatistics', 10, 3);
$app->addAction('wpsocialreviews/facebook_feed_videos', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedVideos', 10, 2);
$app->addAction('wpsocialreviews/facebook_feed_events', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedEvents', 10, 4);
$app->addAction('wpsocialreviews/facebook_feed_summary_card_image', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedSummaryCardImage', 10, 3);
$app->addAction('wpsocialreviews/facebook_feed_image', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedImage', 10, 2);
$app->addAction('wpsocialreviews/facebook_feed_album', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedAlbum', 10, 4);
$app->addAction('wpsocialreviews/facebook_feed_single_album_feed', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedSingleAlbum', 10, 5);
$app->addAction('wpsocialreviews/facebook_feed_photo_feed_image', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedPhotoFeedImage', 10, 4);
$app->addAction('wpsocialreviews/facebook_feed_album_feed_info', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@renderFacebookFeedInfo', 10, 2);
$app->addAction('wpsocialreviews/facebook_feed_handle_album_photo', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@handleAlbumPhoto');

$app->addAction('custom_feed_for_tiktok/tiktok_follow_button', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@renderTiktokFollowButtonHtml', 10, 2);
$app->addAction('custom_feed_for_tiktok/tiktok_feed_bio_description', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@renderTiktokFeedBioDescription', 10, 2);
$app->addAction('custom_feed_for_tiktok/tiktok_header_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@renderTiktokHeaderStatistics', 10, 3);
$app->addAction('custom_feed_for_tiktok/tiktok_feed_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@renderTiktokFeedStatistics', 10, 2);
$app->addAction('custom_feed_for_tiktok/tiktok_feed_date', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@renderFeedDate', 10, 2);


$app->addAction('wpsocialreviews/render_chat_css', 'WPSocialReviewsPro\App\Hooks\Handlers\ChatHandler@renderChatCss');


$app->addAction('wpsocialreviews/load_template_assets', 'WPSocialReviewsPro\App\Hooks\Handlers\CustomFilterHandlerPro@loadTemplateAssets');
$app->addAction('wp', 'WPSocialReviewsPro\App\Hooks\Handlers\CustomFilterHandlerPro@loadTemplateAssetsInWpHead');

$app->addAction('wpsocialreviews/render_ai_summary_total_reviews', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@addTotalReviewsToAISummaryCard', 10, 3);
$app->addAction('wpsocialreviews/custom_review_created', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@handleUpdateBusinessInfo');
$app->addAction('wpsocialreviews/custom_review_updated', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@handleUpdateBusinessInfo');
$app->addAction('wpsocialreviews/custom_review_deleted', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@handleUpdateBusinessInfo');


add_action('init', function () {
    (new \WPSocialReviewsPro\App\Services\Integrations\IntegrationInit())->init();
});

add_action('template_redirect', function () {
    if (defined('WC_PLUGIN_FILE')) {
        (new \WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooProductTemplate())->init();
    }
});

WPSocialReviewsPro\App\Services\QRCodeHandler::registerHooks();
function woocommerce_comments($comment, $args, $depth)
{
    global $product;
    $product_id = $product ? $product->get_ID() : null;

    $woocommercePlatformInstance = new \WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooCommerce();
    $settings = \WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooCommerceHelper::getEffectiveSettings($product_id);
    $isProductConnectedToWPSN = \WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooCommerceHelper::isProductConnectedToWPSN($woocommercePlatformInstance);

    $selected_template = \WPSocialReviews\Framework\Support\Arr::get($settings, 'selected_template');

    if (!$isProductConnectedToWPSN || !$selected_template) {
        // only load default template if product is not connected to WPSN 
        // or 
        // no template is selected either globally or product-specificly

        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['comment'] = $comment;
        wc_get_template(
            'single-product/review.php',
            array(
                'comment' => $comment,
                'args' => $args,
                'depth' => $depth,
            )
        );
    }
}
