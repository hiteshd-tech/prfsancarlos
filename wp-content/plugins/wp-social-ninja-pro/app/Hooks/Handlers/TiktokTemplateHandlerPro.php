<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;

use WPSocialReviews\App\Services\GlobalSettings;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviewsPro\App\Traits\LoadView;

class TiktokTemplateHandlerPro
{
    use LoadView;

    /**
     *
     * Render Tiktok follow button HTML
     *
     * @param $user
     * @param $settings
     *
     * @since 1.3.0
     *
     **/
    public function renderTiktokFollowButtonHtml($feed_settings = [], $header=[] )
    {
        $profile_deep_link = Arr::get($header, 'profile_deep_link', '#');
        $follow_button_text = Arr::get($feed_settings, 'follow_button_settings.follow_button_text', '');

        echo '<div class="wpsr-tiktok-feed-follow-button-group">
            <div class="wpsr-tiktok-feed-btn">
                <a href="' . esc_url($profile_deep_link) . '" target="_blank" rel="noopener" aria-label="'.esc_attr($follow_button_text).'">
                    ' . $follow_button_text . '
                </a>
            </div>
        </div>';
    }

    public function getCommentsCount($feed)
    {
        return Arr::get($feed, 'comment_count', 0);
    }

    public function getCount($feed, $countType)
    {
        $countKey = ($countType === 'view') ? 'view_count' : 'like_count';
        return Arr::get($feed, $countKey, 0);
    }

    public function tiktokFeedsByPopularity($feeds, $popularity_type)
    {
        $countType = ($popularity_type === 'most_viewed') ? 'view' : 'like';

        usort($feeds, function ($m1, $m2) use ($countType) {
            return $this->compareCounts($m1, $m2, $countType);
        });

        return $feeds;
    }

    private function compareCounts($m1, $m2, $countType)
    {
        $sum1 = $this->getCount($m1['statistics'], $countType);
        $sum2 = $this->getCount($m2['statistics'], $countType);

        if ($sum1 == $sum2) {
            return 0;
        }

        if ($sum1 > $sum2) {
            return -1;
        }
        return 1;
    }

    public function renderTiktokHeaderStatistics($header_settings, $header, $translations)
    {
        $likes_count = Arr::get($header, 'likes_count');
        $likes_text = Arr::get($translations, 'likes') ?: __('Likes', 'wp-social-ninja-pro');
        $follower_count = Arr::get($header, 'follower_count');
        $follower_text = Arr::get($translations, 'followers') ?: __('Followers', 'wp-social-ninja-pro');
        $following_count = Arr::get($header, 'following_count');
        $following_text = Arr::get($translations, 'following') ?: __('Following', 'wp-social-ninja-pro');

        echo '<div class="wpsr-tiktok-feed-user-statistics">';
        if ($header_settings['display_likes_counter'] === 'true') {
            echo '<span><strong>' . GlobalHelper::shortNumberFormat($likes_count) . '</strong>'.esc_html($likes_text).'</span>';
        }
        if ($header_settings['display_followers_counter'] === 'true') {
            echo '<span><strong>' . GlobalHelper::shortNumberFormat($follower_count) . '</strong>'.esc_html($follower_text).'</span>';
        }
        if ($header_settings['display_following_counter'] === 'true') {
            echo '<span><strong>' . GlobalHelper::shortNumberFormat($following_count) . '</strong>'.esc_html($following_text).'</span>';
        }
        echo '</div>';
    }

    public function renderTiktokFeedStatistics ($template_meta, $feed)
    {
        $displayLikesCount = Arr::get($template_meta, 'post_settings.display_likes_count');
        $displayCommentsCount = Arr::get($template_meta, 'post_settings.display_comments_count');
        $displayViewsCount = Arr::get($template_meta, 'post_settings.display_views_count');
        $displayStatistics = false;

        if ($displayLikesCount === 'true' || $displayCommentsCount === 'true' || $displayViewsCount === 'true') {
            $displayStatistics = true;
        }
        if (!$displayStatistics) {
            return;
        }

        $html = $this->loadView('feeds-templates/tiktok/elements/statistics', array(
            'template_meta' => $template_meta,
            'feed' => $feed
        ));

        echo $html; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
    }

    public function renderFeedDate($template_meta = [], $feed = [])
    {
        if (Arr::get($template_meta,'post_settings.display_date') === 'false'){
            return;
        }
        $html = $this->loadView('feeds-templates/tiktok/elements/date', array(
            'feed'  => $feed
        ));
        echo $html; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
    }

    public function tiktokSpecificVideoIds($apiSettings)
    {
        
        $video_ids = Arr::get($apiSettings, 'single_video_feed_ids', []);
        
        if (empty($video_ids)) {
            return [];
        }

        $video_ids = explode(',', $video_ids);
        $video_ids = array_map('trim', $video_ids);

        update_option('wpsr_tiktok_specific_video_ids', $video_ids);

        return $video_ids;
    }

    public function renderTiktokFeedBioDescription($header_settings, $header)
    {
        if (Arr::get($header_settings,'display_description') === 'false'){
            return;
        }

        $bio_description = Arr::get($header, 'bio_description');
        echo '<div class="wpsr-tiktok-feed-user-info-description">
                <p>' . esc_html($bio_description) . '</p>
              </div>';
    }

    public function tiktokVideoApiDetails($fields)
    {
        $statistics = ',video_description,like_count,comment_count,share_count,view_count';
        return $fields . $statistics;
    }

    public function addTemplate($data = [])
    {
        return $this->loadView('feeds-templates/tiktok/template2', $data);
    }
    public function tiktokSpecificVideoApiDetails($fields = '')
    {
        return 'video/query/?fields=id,title,duration,cover_image_url,embed_link,video_description,create_time,like_count,comment_count,share_count,view_count';
    }

}