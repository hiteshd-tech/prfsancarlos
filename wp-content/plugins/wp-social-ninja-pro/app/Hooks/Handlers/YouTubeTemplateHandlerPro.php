<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;


use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Feeds\Youtube\Helper as YoutubeHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviewsPro\App\Traits\LoadView;
use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;

class YouTubeTemplateHandlerPro
{
    use LoadView;

    public function youtubePlaylistApiUrlDetails($playlist_id, $total, $fetch_url)
    {
        if (empty($playlist_id) || !$playlist_id) {
            return array('error_message' => __('Please enter playlist id to fetch videos!! ', 'wp-social-ninja-pro'));
        }

        $feedCacheName     = 'playlist_feed_id_' . $playlist_id . '_num_' . $total;
        $youtubeFeedApiUrl = $fetch_url . 'playlistItems?part=id,snippet&playlistId=' . $playlist_id . '&';

        return array(
            'cache_name' => $feedCacheName,
            'api_url'    => $youtubeFeedApiUrl
        );
    }

    public function youtubeSearchApiUrlDetails($search_term, $total, $fetch_url)
    {
        if (empty($search_term)) {
            return array('error_message' => __('Please enter search term to fetch videos!! ', 'wp-social-ninja-pro'));
        }

        $feedCacheName     = 'search_feed_search_term_' . str_replace(' ', '_', $search_term) . '_num_' . $total;
        $youtubeFeedApiUrl = $fetch_url . 'search?part=id,snippet&q=' . str_replace('_', ' ', $search_term) . '&order=date&';

        return array(
            'cache_name' => $feedCacheName,
            'api_url'    => $youtubeFeedApiUrl
        );
    }

    public function youtubeLiveStreamsApiUrlDetails($channel_handle, $event_type, $total, $fetch_url)
    {
        if (empty($channel_handle)) {
            return array('error_message' => __('Please enter a channel id to fetch videos!! ', 'wp-social-ninja-pro'));
        }

        // If it's a handle, convert and use the channel ID for everything
        if (strpos($channel_handle, 'UC') === false) {
            $channel_id = YoutubeHelper::getChannelIdFromHandle($channel_handle);
            if (empty($channel_id)) {
                return ['error_message' => __('Please enter a valid channel Handle!! ', 'wp-social-ninja-pro')];
            }
            $cache_key_id = $channel_id;
        } else {
            $channel_id = $channel_handle;
            $cache_key_id = $channel_id;
        }
        $feedCacheName = 'live_streams_feed_id_' . $cache_key_id . '_num_' . $total . '_event_type_' . $event_type;

        $youtubeFeedApiUrl = $fetch_url . 'search?part=id,snippet&channelId=' . $channel_id . '&order=date&type=video&eventType=' . $event_type . '&';

        return array(
            'cache_name' => $feedCacheName,
            'api_url'    => $youtubeFeedApiUrl
        );
    }

    public function youtubeSingleVideoStatistics($video_id, $fetch_url)
    {
        return $fetch_url . 'videos?part=contentDetails,statistics&id=' . $video_id . '&';
    }

    public function youtubeSingleVideoCommentsApi($video_id)
    {
        if(empty($video_id)){
            return false;
        }
        return 'https://www.googleapis.com/youtube/v3/commentThreads?textFormat=plainText&part=snippet&videoId=' . $video_id . '&maxResults=10&';
    }

    public function youtubeApiParts($parts, $feed_type)
    {
        if ($feed_type === 'live_streams_feed') {
            return $parts . ',statistics,liveStreamingDetails,contentDetails';
        } else {
            return $parts . ',statistics,contentDetails';
        }
    }

    public function getYoutubeLikesCount($feed)
    {
        return isset($feed['statistics']['likeCount']) ? $feed['statistics']['likeCount'] : 0;
    }

    public function getYoutubeCommentsCount($feed)
    {
        return isset($feed['statistics']['commentCount']) ? $feed['statistics']['commentCount'] : 0;
    }

    public function youtubeFeedsByPopularity($feeds, $popularity_type)
    {
        $multiply = ($popularity_type === 'most_popular') ? -1 : 1;
        usort($feeds, function ($m1, $m2) use ($multiply) {
            $sum1 = $this->getYoutubeLikesCount($m1) + $this->getYoutubeCommentsCount($m1);
            $sum2 = $this->getYoutubeLikesCount($m2) + $this->getYoutubeCommentsCount($m2);

            if($sum1 == $sum2) {
                return 0;
            }

            if($sum1 < $sum2) {
                return -1 * $multiply;
            }
            return 1 * $multiply;
        });

        return $feeds;
    }

    /**
     *
     * Render YouTube Channel Statistics HTML
     *
     * @param $header
     * @param $template_header_meta
     *
     * @since 1.2.5
     *
     **/
    public function renderChannelStatisticsHtml($header = [], $template_header_meta = [], $translations = [])
    {
        ?>
        <div class="wpsr-yt-header-channel-statistics">
            <?php if (Arr::get($template_header_meta, 'display_subscriber_counter') === 'true' && Arr::get($header,
                    'items.0.statistics.subscriberCount')) { ?>
                <div class="wpsr-yt-header-statistic-item">
                    <?php
                        $subscribers_text = Arr::get($translations, 'subscribers') ?: __('Subscribers', 'wp-social-ninja-pro');
                        echo GlobalHelper::shortNumberFormat($header['items'][0]['statistics']['subscriberCount']) .' '. $subscribers_text;
                    ?>
                </div>
            <?php } ?>
            <?php if (Arr::get($template_header_meta, 'display_videos_counter') === 'true' && Arr::get($header,
                    'items.0.statistics.videoCount')) { ?>
                <div class="wpsr-yt-header-statistic-item">
                    <?php
                        $videos_text = Arr::get($translations, 'videos') ?: __('Videos', 'wp-social-ninja-pro');
                        echo GlobalHelper::shortNumberFormat($header['items'][0]['statistics']['videoCount']) .' '. $videos_text;
                    ?>
                </div>
            <?php } ?>
            <?php if (Arr::get($template_header_meta, 'display_views_counter') === 'true' && Arr::get($header,
                    'items.0.statistics.viewCount')) { ?>
                <div class="wpsr-yt-header-statistic-item">
                    <?php
                        $views_text = Arr::get($translations, 'views') ?: __('Views', 'wp-social-ninja-pro');
                        echo GlobalHelper::shortNumberFormat($header['items'][0]['statistics']['viewCount']) .' '. $views_text;
                    ?>
                </div>
            <?php } ?>
        </div>
        <?php
    }


    /**
     *
     * Render YouTube Channel Subscribe Button HTML
     *
     * @param $header
     * @param $settings
     *
     * @since 1.2.5
     *
     **/
    public function renderYoutubeChannelSubscribeBtnHtml($header = [], $settings = [])
    {
        if (Arr::get($settings, 'subscribe_button_text') === '') {
            return;
        }
        $subscribe_button_text = Arr::get($settings, 'subscribe_button_text');
        ?>
        <div class="wpsr-yt-header-subscribe-btn">
            <a href="<?php echo esc_url('https://www.youtube.com/channel/' . Arr::get($header,
                    'items.0.id') . '?sub_confirmation=1'); ?>"
               target="_blank" rel="noopener noreferrer"
               aria-label="<?php echo esc_attr($subscribe_button_text); ?>"
            ><?php echo esc_html($subscribe_button_text); ?></a>
        </div>
        <?php
    }

    public function renderYoutubeFeedDescriptionHtml($feed = [], $template_meta = [])
    {
        if (Arr::get($template_meta, 'video_settings.display_description') === 'false') {
            return;
        }
        $description = substr($feed['snippet']['description'], 0, 100) . '...';
        ?>
        <p class="wpsr-yt-video-description">
            <?php echo wp_kses_post($description); ?>
        </p>
        <?php
    }

    public function renderYoutubeFeedStatisticsHtml($feed = [], $template_meta = [], $feed_info = [], $index = null, $templateId = null)
    {
        $videoId = YoutubeHelper::getVideoId($feed);
        ?>
        <?php
        if (Arr::get($template_meta, 'video_settings.display_channel_name') === 'true') {
            ?>
            <a href="<?php echo esc_url('https://www.youtube.com/channel/' . $feed['snippet']['channelId']); ?>"
               target="_blank" rel="noopener noreferrer" class="wpsr-yt-channel-title">
                <?php echo esc_html($feed['snippet']['channelTitle']); ?>
            </a>
        <?php } ?>

        <div class="wpsr-yt-video-statistics">
            <?php if (Arr::get($template_meta, 'video_settings.display_views_counter') === 'true' && Arr::get($feed,
                    'statistics.viewCount')) { ?>
                <div class="wpsr-yt-video-statistic-item">
                    <?php echo GlobalHelper::shortNumberFormat($feed['statistics']['viewCount']) . __(' Views',
                            'wp-social-ninja-pro'); ?>
                </div>
            <?php } ?>
            <?php if (Arr::get($template_meta,
                    'video_settings.display_date') === 'true' && $feed_info['feed_type'] !== 'live_streams_feed') { ?>
                <div class="wpsr-yt-video-statistic-item">
                    <?php
                    $publishedAt = date_format(date_create($feed['snippet']['publishedAt']), 'D M j H:i:s O Y');
                    // translators: %s is the time difference (e.g., "2 hours", "3 days")
                    echo sprintf(__('%s ago', 'wp-social-ninja-pro'), human_time_diff(strtotime($publishedAt)));
                    ?>
                </div>
            <?php } ?>
            <?php if ($feed_info['feed_type'] === 'live_streams_feed' && $feed_info['event_type'] !== 'live') { ?>
                <div class="wpsr-yt-video-statistic-item">
                    <?php if ($feed_info['event_type'] === 'completed') { ?>
                        <span>
                            <?php
                            if (isset($feed['snippet']['publishedAt'])) {
                                $publishedAt = date_format(date_create($feed['snippet']['publishedAt']), 'D M j H:i:s O Y');
                                // translators: %s is the time difference (e.g., "2 hours", "3 days")
                                $human_time_diff = sprintf(__('%s ago', 'wp-social-ninja-pro'), human_time_diff(strtotime($publishedAt)));

                                echo __('Streamed ', 'wp-social-ninja-pro') . $human_time_diff;
                            } ?>
                            </span>
                    <?php } ?>
                    <?php if ($feed_info['event_type'] === 'upcoming' && Arr::get($feed, 'liveStreamingDetails.scheduledStartTime')) { ?>
                        <span><?php echo get_date_from_gmt($feed['liveStreamingDetails']['scheduledStartTime'], 'm/d/Y, g:i A'); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if (Arr::get($template_meta, 'video_settings.display_likes_counter') === 'true' && Arr::get($feed,
                    'statistics.likeCount')) { ?>
                <div class="wpsr-yt-video-statistic-item">
                    <?php echo GlobalHelper::shortNumberFormat($feed['statistics']['likeCount']) . __(' Likes',
                            'wp-social-ninja-pro'); ?>
                </div>
            <?php } ?>
            <?php if ((Arr::get($template_meta, 'video_settings.display_comments_counter') === 'true' && Arr::get($feed,
                    'statistics.commentCount'))) { ?>
                <div class="wpsr-yt-video-statistic-item">
                    <?php echo GlobalHelper::shortNumberFormat($feed['statistics']['commentCount']) . __(' Comments',
                            'wp-social-ninja-pro'); ?>
                </div>
            <?php } ?>
            <?php if ($feed_info['feed_type'] === 'live_streams_feed' && $feed_info['event_type'] === 'live') { ?>
                <div class="wpsr-yt-video-statistic-item">
                    <a class="wpsr-yt-video-playmode wpsr-yt-live-now-btn"
                       data-videoid="<?php echo esc_attr($videoId); ?>" data-index="<?php echo esc_attr($index); ?>"
                       data-playmode="<?php echo isset($template_meta['video_settings']['play_mode']) ? esc_attr($template_meta['video_settings']['play_mode']) : 'inline'; ?>"
                       data-template-id="<?php echo esc_attr($templateId); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo __('LIVE NOW', 'wp-social-ninja-pro'); ?>
                    </a>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    public function popupContentHtml($feed = [], $template_meta = [], $header = [])
    {
        $display_title = Arr::get($template_meta, 'popup_settings.display_title');
        $display_views_counter = Arr::get($template_meta, 'popup_settings.display_views_counter');
        $display_date = Arr::get($template_meta, 'popup_settings.display_date');

        $display_likes_counter = Arr::get($template_meta, 'popup_settings.display_likes_counter');
        $display_dislikes_counter = Arr::get($template_meta, 'popup_settings.display_dislikes_counter');

        $display_channel_logo = Arr::get($template_meta, 'popup_settings.display_channel_logo');
        $display_channel_name = Arr::get($template_meta, 'popup_settings.display_channel_name');
        $display_subscribers_counter = Arr::get($template_meta, 'popup_settings.display_subscribers_counter');
        $display_description = Arr::get($template_meta, 'popup_settings.display_description');
        $display_subscribe_button = Arr::get($template_meta, 'popup_settings.display_subscribe_button');
        $display_comments = Arr::get($template_meta, 'popup_settings.display_comments');

        if($display_title === 'false'
            && $display_views_counter === 'false'
            && $display_date === 'false'
            && $display_likes_counter === 'false'
            && $display_dislikes_counter === 'false'
            && $display_channel_logo === 'false'
            && $display_channel_name === 'false'
            && $display_subscribers_counter === 'false'
            && $display_description === 'false'
            && $display_subscribe_button === 'false'
            && $display_comments === 'false'
        ){
            return;
        }
        ?>
        <div class="wpsr-yt-popup-box-content">

        <?php if ($display_title === 'true') { ?>
        <h1 class="wpsr-yt-popup-video-title"><?php echo esc_html(Arr::get($feed, 'snippet.title', '')); ?></h1>
    <?php } ?>
        <?php if($display_views_counter === 'true' || $display_date === 'true' || $display_likes_counter === 'true' || $display_dislikes_counter === 'true'){ ?>
        <div class="wpsr-yt-popup-video-info">

            <?php if($display_views_counter === 'true' || $display_date === 'true'){ ?>
                <div class="wpsr-yt-popup-video-info-left">
                    <?php if ($display_views_counter === 'true' && Arr::get($feed, 'statistics')) { ?>
                        <span class="wpsr-yt-popup-video-views">
                        <?php echo GlobalHelper::shortNumberFormat(Arr::get($feed, 'statistics.viewCount', null)) . __(' Views',
                                'wp-social-ninja-pro'); ?>
                    </span>
                    <?php } ?>
                    <?php if ($display_date === 'true') { ?>
                        <span class="wpsr-yt-popup-video-date"> <?php echo date_format(date_create($feed['snippet']['publishedAt']),
                                'M j, Y'); ?></span>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if($display_likes_counter === 'true' || $display_dislikes_counter === 'true'){ ?>
                <div class="wpsr-yt-popup-video-info-right">
                    <?php if ($display_likes_counter === 'true' && Arr::get($feed,
                            'statistics.likeCount') && Arr::get($feed, 'statistics.likeCount') >= 0) { ?>
                        <span class="wpsr-yt-popup-video-likes">
                        <svg viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"
                             class="wpsr-yt-like-icon">
                            <g class="wpsr-yt-like-icon">
                            <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L23 10z"
                                  class="wpsr-yt-like-icon"></path>
                            </g>
                        </svg>
                        <?php echo GlobalHelper::shortNumberFormat(Arr::get($feed, 'statistics.likeCount')); ?>
                    </span>
                    <?php } ?>
                    <?php if ($display_dislikes_counter === 'true' && Arr::get($feed,
                            'statistics.dislikeCount') && Arr::get($feed, 'statistics.dislikeCount') >= 0) { ?>
                        <span class="wpsr-yt-popup-video-dislikes">
                         <svg viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" focusable="false"
                              class="wpsr-yt-dislike-icon">
                            <g class="wpsr-yt-dislike-icon">
                            <path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v1.91l.01.01L1 14c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"
                                  class="wpsr-yt-dislike-icon"></path>
                            </g>
                        </svg>
                    <?php echo GlobalHelper::shortNumberFormat(Arr::get($feed, 'statistics.dislikeCount')); ?>
                    </span>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

        <?php
        if ( (!empty($header) && is_array($header)) && ($display_channel_logo === 'true' || $display_channel_name === 'true' || $display_subscribers_counter === 'true' || $display_description === 'true') || $display_subscribe_button === 'true') {

            $wpsr_channel_id = Arr::get($header, 'items.0.id', '');
            $wpsr_channel_title = Arr::get($header, 'items.0.snippet.title', '');
            $wpsr_channel_avatar_url = Arr::get($header, 'avatar', '');
            $wpsr_subscriber_count = Arr::get($header, 'items.0.statistics.subscriberCount', null);
        ?>
        <div class="wpsr-yt-popup-video-meta">
            <?php if ($display_channel_logo === 'true') { ?>
                <div class="wpsr-yt-popup-video-meta-channel-logo">
                    <a target="_blank" href="<?php echo esc_url('https://www.youtube.com/channel/' . $wpsr_channel_id) ?>">
                        <img src="<?php echo esc_url($wpsr_channel_avatar_url); ?>"
                             alt="<?php echo esc_attr($wpsr_channel_title); ?>">
                    </a>
                </div>
            <?php } ?>
            <?php if($display_channel_name === 'true' || $display_subscribers_counter === 'true' || $display_description === 'true'){ ?>
                <div class="wpsr-yt-popup-video-meta-info">
                    <?php if ($display_channel_name === 'true') { ?>
                        <a class="wpsr-yt-popup-video-meta-channel-name" target="_blank" rel="noopener noreferrer"
                           href="<?php echo esc_url('https://www.youtube.com/channel/' . $wpsr_channel_id) ?>"
                           title="<?php echo esc_attr($wpsr_channel_title); ?>">
                            <?php echo esc_html($wpsr_channel_title); ?>
                        </a>
                    <?php } ?>
                    <?php if ($display_subscribers_counter === 'true') { ?>
                        <span class="wpsr-yt-popup-video-meta-channel-subscriber-count">
                        <?php echo GlobalHelper::shortNumberFormat($wpsr_subscriber_count) . __(' Subscribers',
                                'wp-social-ninja-pro'); ?>
                    </span>
                    <?php } ?>
                    <?php if ($display_description === 'true') { ?>
                        <p class="wpsr-yt-popup-video-meta-description wpsr_show_less_content"><?php echo make_clickable(YoutubeHelper::formatContent(Arr::get($feed, 'snippet.description'))); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if ($display_subscribe_button === 'true') { ?>
                <div class="wpsr-yt-popup-video-meta-btn">
                    <a href="<?php echo esc_url('https://www.youtube.com/channel/' . $wpsr_channel_id) . '?sub_confirmation=1'; ?>"
                       target="_blank" rel="noopener"
                       aria-label="<?php echo __('Subscribe', 'wp-social-ninja-pro'); ?>"
                    >
                        <?php echo __('Subscribe', 'wp-social-ninja-pro'); ?>
                    </a>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

        <?php if ( Arr::get($feed, 'comment.items') && ($display_comments === 'true')) { ?>
        <div class="wpsr-yt-popup-video-comments">
            <?php foreach ($feed['comment']['items'] as $index => $comment) { ?>
                <div class="wpsr-yt-popup-video-comment">
                    <div class="wpsr-yt-popup-video-comment-profile-pic">
                        <a href="<?php echo esc_url($comment['snippet']['topLevelComment']['snippet']['authorChannelUrl']); ?>"
                           target="_blank" rel="noopener">
                            <img src="<?php echo esc_url($comment['snippet']['topLevelComment']['snippet']['authorProfileImageUrl']); ?>"
                                 alt="<?php echo esc_attr($comment['snippet']['topLevelComment']['snippet']['authorDisplayName']); ?>">
                        </a>
                    </div>
                    <div class="wpsr-yt-popup-video-comment-info">
                        <div class="wpsr-yt-popup-video-comment-info-header">
                            <a href="<?php echo esc_url($comment['snippet']['topLevelComment']['snippet']['authorChannelUrl']); ?>"
                               target="_blank"
                               rel="noopener"
                               class="wpsr-yt-popup-video-comment-info-header-username"><?php echo esc_html($comment['snippet']['topLevelComment']['snippet']['authorDisplayName']); ?></a>
                            <span class="wpsr-yt-popup-video-comment-info-header-time">
                                <?php
                                $publishedAt = date_format(date_create($comment['snippet']['topLevelComment']['snippet']['publishedAt']), 'D M j H:i:s O Y');
                                // translators: %s is the time difference (e.g., "2 hours", "3 days")
                                echo sprintf(__('%s ago', 'wp-social-ninja-pro'), human_time_diff(strtotime($publishedAt)));
                                ?>
                            </span>
                        </div>
                        <div class="wpsr-yt-popup-video-comment-text">
                            <p class="wpsr-yt-popup-video-comment-text-inner wpsr_show_less_content"><?php echo make_clickable(YoutubeHelper::formatContent($comment['snippet']['topLevelComment']['snippet']['textOriginal'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        </div>
    <?php }
    }

    /**
     *
     * Render YouTube Channel Description HTML
     *
     * @param $header
     * @param $template_header_meta
     *
     * @since 1.2.5
     *
     **/
    public function renderChannelDescriptionHtml($header = array(), $template_header_meta = [])
    {
        if (Arr::get($template_header_meta, 'display_description') === 'false') {
            return;
        }
        ?>
        <div class="wpsr-yt-header-channel-description">
            <p><?php echo esc_html($header['items'][0]['snippet']['description']); ?></p>
        </div>
        <?php
    }

    public function renderYoutubePrevNextPagination($templateId, $paginate, $total, $playMode)
    {
        echo '<ul class="wpsr-yt-prev-next wpsr-prev-next-default wpsr-prev-next-' . esc_attr($playMode) . '"
                    id="wpsr-yt-prev-next-' . esc_attr($templateId) . '"
                    data-template-id="' . esc_attr($templateId) . '"
                    data-paginate="' . esc_attr($paginate) . '"
                    data-pagenum="0"
                    data-total="' . esc_attr($total) . '">
                    <li><a href="#" rel="noopener" class="wpsr-pagi-prev wpsr-link-disable">' . __('Prev', 'wp-social-ninja-pro') . '</a></li>
                    <li><a href="#" rel="noopener" class="wpsr-pagi-next">' . __('Next', 'wp-social-ninja-pro') . '</a></li>
                </ul>';
    }
}