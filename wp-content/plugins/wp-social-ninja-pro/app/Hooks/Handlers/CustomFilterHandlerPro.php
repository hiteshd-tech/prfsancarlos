<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;

use WPSocialReviews\App\Services\Helper as GlobalHelper;

class CustomFilterHandlerPro
{

    public function feedsByRandom($feeds)
    {
        if (!is_array($feeds) || count($feeds) < 2) {
            return $feeds;
        }

        $keys = array_keys($feeds);
        shuffle($keys);

        $randomFeeds = [];
        foreach ($keys as $key) {
            $randomFeeds[$key] = $feeds[$key];
        }

        return $randomFeeds;
    }

    public function includeOrExcludeFeed($hasIncludeWord, $includesWords, $post_caption)
    {
        $post_caption = trim($post_caption, " ");
        if (empty($post_caption)) {
            return false;
        }

        // Normalize caption for robust matching using shared method from Helper class
        if(method_exists(GlobalHelper::class, 'normalizeText')){
            $normalizedCaption = GlobalHelper::normalizeText($post_caption);
        } else {
            // Fallback normalization if method doesn't exist
            $normalizedCaption = mb_strtolower(trim($post_caption), 'UTF-8');
        }

        $hasIncludeWord = false;
        foreach ($includesWords as $includeWord) {
            if ($includeWord === null || $includeWord === '') {
                continue;
            }

            if (method_exists(GlobalHelper::class, 'normalizeText')){
                $normalizedWord = GlobalHelper::normalizeText($includeWord);
            } else {
                // Fallback normalization if method doesn't exist
                $normalizedWord = mb_strtolower(trim($includeWord), 'UTF-8');
            }
            
            if ($normalizedWord === '') {
                continue;
            }

            // Case-insensitive multibyte safe contains
            // Safe mb_stripos with fallback to stripos
            $position = function_exists('mb_stripos')
                ? mb_stripos($normalizedCaption, $normalizedWord, 0, 'UTF-8')
                : stripos($normalizedCaption, $normalizedWord, 0);

            if ($position !== false) {
                $hasIncludeWord = true;
                break;
            }
        }

        return $hasIncludeWord;
    }

    public function hideFeed($hidePostIds, $feedId)
    {
        $hasHidePost = false;
        foreach ($hidePostIds as $id) {
            if (!empty($id) && !empty($feedId)) {
                if ($id === $feedId) {
                    $hasHidePost = true;
                    break;
                } elseif (strpos($feedId, $id) !== false) {
                    $hasHidePost = true;
                    break;
                }
            }
        }

        return $hasHidePost;
    }

    public function updateDisplayUserOnlineStatus($settings)
    {
        $days = array(
            __('Saturday', 'wp-social-ninja-pro'),
            __('Sunday', 'wp-social-ninja-pro'),
            __('Monday', 'wp-social-ninja-pro'),
            __('Tuesday', 'wp-social-ninja-pro'),
            __('Wednesday', 'wp-social-ninja-pro'),
            __('Thursday', 'wp-social-ninja-pro'),
            __('Friday', 'wp-social-ninja-pro')
        );

        //day params
        $dataParams                    = array();
        $dataParams['dayTimeSchedule'] = isset($settings['day_time_schedule']) ? $settings['day_time_schedule'] : 'false';
        $dataParams['dayLists']        = isset($settings['day_list']) ? $settings['day_list'] : $days;

        //time params
        $dataParams['timeSchedule'] = isset($settings['time_schedule']) ? $settings['time_schedule'] : 'false';
        $dataParams['startTime']    = isset($settings['start_time']) ? $settings['start_time'] : '';
        $dataParams['endTime']      = isset($settings['end_time']) ? $settings['end_time'] : '';

        return $dataParams;
    }

    public function loadTemplateAssets($templateId)
    {
        if(!in_array($templateId, \WPSocialReviews\App\Services\Helper::$loadedTemplates)){
            (new \WPSocialReviewsPro\App\Services\TemplateCssHandler())->renderTemplateCss($templateId);
            GlobalHelper::$loadedTemplates[] = $templateId;
        }
    }

    public function loadTemplateAssetsInWpHead()
    {
        global $post;
        $post_id = isset($post) && isset($post->ID) ? $post->ID : null;

        if (!is_a($post, 'WP_Post')) {
            return;
        }

        $has_wpsn_ids = get_post_meta($post_id, '_wpsn_ids', true);
        if ($has_wpsn_ids) {
            if ($ids = GlobalHelper::getShortCodeIds($post->post_content)) {
                foreach ($ids as $id) {
                    do_action('wpsocialreviews/load_template_assets', $id);
                }
            }

        }
    }
}