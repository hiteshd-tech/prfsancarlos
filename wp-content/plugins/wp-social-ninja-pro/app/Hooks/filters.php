<?php

/**
 * All registered filter's handlers should be in app\Hooks\Handlers,
 * addFilter is similar to add_filter and addCustomFlter is just a
 * wrapper over add_filter which will add a prefix to the hook name
 * using the plugin slug to make it unique in all wordpress plugins,
 * ex: $app->addCustomFilter('foo', ['FooHandler', 'handleFoo']) is
 * equivalent to add_filter('slug-foo', ['FooHandler', 'handleFoo']).
 */

/**
 * $app
 * @var $app WPSocialReviewsPro\App\Application
 */
// Common Hooks
$app->addFilter('wpsocialreviews/feeds_by_random', 'WPSocialReviewsPro\App\Hooks\Handlers\CustomFilterHandlerPro@feedsByRandom');
$app->addFilter('wpsocialreviews/include_or_exclude_feed', 'WPSocialReviewsPro\App\Hooks\Handlers\CustomFilterHandlerPro@includeOrExcludeFeed', 10, 3);
$app->addFilter('wpsocialreviews/hide_feed', 'WPSocialReviewsPro\App\Hooks\Handlers\CustomFilterHandlerPro@hideFeed', 10, 2);


// Reviews Backend hooks
$app->addFilter('wpsocialreviews/airbnb_reviews_limit_end_point', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@airbnbReviewsLimitEndPoint');
$app->addFilter('wpsocialreviews/admin_app_vars', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@adminAppVars');
$app->addFilter('wpsocialreviews/available_valid_reviews_platforms', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@pushPlatforms');

//Reviews FrontEnd Hooks
$app->addFilter('wpsocialreviews/add_reviews_template', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@addReviewsTemplate', 10, 4);
$app->addFilter('wpsocialreviews/add_reviews_badge_template', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@addReviewsBadgeTemplate', 10, 4);
$app->addFilter('wpsocialreviews/add_reviews_notification_template', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@addReviewsNotificationTemplate', 10, 3);
$app->addFilter('wpsocialreviews/author_position', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@renderAuthorPosition', 10, 2);
$app->addFilter('wpsocialreviews/author_website_logo', 'WPSocialReviewsPro\App\Hooks\Handlers\ReviewsTemplateHandlerPro@renderAuthorWebsiteLogo', 10, 2);

// Twitter feed hooks
$app->addFilter('wpsocialreviews/set_twitter_transient_name', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@setTwitterTransientName', 10, 4);
$app->addFilter('wpsocialreviews/set_twitter_api_base_url', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@setTwitterApiBaseUrl', 10, 2);
$app->addFilter('wpsocialreviews/twitter_set_get_field', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@twitterSetGetFieldEndPoint', 10, 3);
$app->addFilter('wpsocialreviews/twitter_feed_response', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@twitterFeedResponse');
$app->addFilter('wpsocialreviews/twitter_feed_header_api_response', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@twitterFeedHeaderApiResponse', 10, 4);
$app->addFilter('wpsocialreviews/twitter_feeds_by_popularity', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@twitterFeedsByPopularity', 10, 2);

//Twitter FrontEnd Hooks
$app->addFilter('wpsocialreviews/add_twitter_template', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@addTwitterTemplate');
$app->addFilter('wpsocialreviews/render_twitter_template_header', 'WPSocialReviewsPro\App\Hooks\Handlers\TwitterTemplateHandlerPro@renderTwitterTemplateHeader', 10, 3);



// YouTube feed hooks
$app->addFilter('wpsocialreviews/youtube_playlist_api_url_details', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubePlaylistApiUrlDetails', 10, 3);
$app->addFilter('wpsocialreviews/youtube_search_api_url_details', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubeSearchApiUrlDetails', 10, 3);
$app->addFilter('wpsocialreviews/youtube_live_streams_api_url_details', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubeLiveStreamsApiUrlDetails', 10, 4);
$app->addFilter('wpsocialreviews/youtube_single_video_statistics', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubeSingleVideoStatistics', 10, 2);
$app->addFilter('wpsocialreviews/youtube_single_video_comments_api', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubeSingleVideoCommentsApi');
$app->addFilter('wpsocialreviews/youtube_api_parts', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubeApiParts', 10, 2);
$app->addFilter('wpsocialreviews/youtube_feeds_by_popularity', 'WPSocialReviewsPro\App\Hooks\Handlers\YouTubeTemplateHandlerPro@youtubeFeedsByPopularity', 10, 2);

//instagram feed filters hooks
$app->addFilter('wpsocialreviews/fetch_instagram_comments', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@fetchInstagramComments', 10, 2);
$app->addFilter('wpsocialreviews/instagram_feeds_limit', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@instagramFeedsLimit');
$app->addFilter('wpsocialreviews/instagram_feeds_by_popularity', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@feedsByPopularity', 10, 2);
$app->addFilter('wpsr_feed_items_by_page_instagram', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@getPaginatedInstaFeedHtml', 10, 3);
$app->addFilter('wpsocialreviews/instagram_trim_caption_words', 'WPSocialReviewsPro\App\Hooks\Handlers\InstagramTemplateHandlerPro@trimCaptionWords', 10, 2);


// facebook feed hooks
$app->addFilter('wpsocialreviews/facebook_timeline_feed_api_fields', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookTimelineFeedApiFields');
$app->addFilter('wpsocialreviews/facebook_video_feed_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookVideoFeedApiDetails', 10, 4);
$app->addFilter('wpsocialreviews/facebook_video_playlist_feed_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookVideoPlaylistFeedApiDetails', 10, 4);
$app->addFilter('wpsocialreviews/facebook_photo_feed_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookPhotoFeedApiDetails', 10, 4);
$app->addFilter('wpsocialreviews/facebook_event_feed_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookEventFeedApiDetails', 10, 4);
$app->addFilter('wpsocialreviews/facebook_albums_feed_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookAlbumsFeedApiDetails', 10, 4);
$app->addFilter('wpsocialreviews/facebook_single_album_feed_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookSingleAlbumFeedApiDetails', 10, 4);
$app->addFilter('wpsocialreviews/facebook_feed_extend_api_endpoints', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookFeedExtendApiEndpoints', 10, 2);
$app->addFilter('wpsocialreviews/facebook_feed_get_post_attachments', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookFeedFetchPostMoreAttachments', 10, 3);
$app->addFilter('wpsocialreviews/facebook_feeds_by_popularity', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@facebookFeedsByPopularity', 10, 2);
$app->addFilter('wpsocialreviews/facebook_feed_album_paginated_feed_html', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@getAlbumPaginatedFeedHtml', 10, 4);
$app->addFilter('wpsocialreviews/add_facebook_feed_template', 'WPSocialReviewsPro\App\Hooks\Handlers\FacebookTemplateHandlerPro@addTemplate');

// tiktok feed hooks
if (defined('CUSTOM_FEED_FOR_TIKTOK_VERSION')) {
    $app->addFilter('wpsocialreviews/tiktok_feeds_by_popularity', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@tiktokFeedsByPopularity', 10, 2);
    $app->addFilter('custom_feed_for_tiktok/tiktok_video_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@tiktokVideoApiDetails');
    $app->addFilter('custom_feed_for_tiktok/add_tiktok_feed_template', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@addTemplate');

    $app->addFilter('custom_feed_for_tiktok/tiktok_specific_video_api_details', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@tiktokSpecificVideoApiDetails');
    $app->addFilter('custom_feed_for_tiktok/tiktok_specific_video_ids', 'WPSocialReviewsPro\App\Hooks\Handlers\TiktokTemplateHandlerPro@tiktokSpecificVideoIds');
}
// chat widgets hooks
$app->addFilter('wpsocialreviews/display_user_online_status', 'WPSocialReviewsPro\App\Hooks\Handlers\ChatHandler@updateDisplayUserOnlineStatus', 10, 2);



