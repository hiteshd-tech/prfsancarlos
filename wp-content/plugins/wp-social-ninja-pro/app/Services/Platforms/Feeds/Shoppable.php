<?php
namespace WPSocialReviewsPro\App\Services\Platforms\Feeds;

use WPSocialReviews\Framework\Support\Arr;

/**
 * Class Shoppable
 *
 */
if (!defined('ABSPATH')) {
    die('-1');
}

class Shoppable
{
    private $platform = null;

    public function makeShoppableByHashtags($feed)
    {
        $caption = Arr::get($feed, 'caption', '');
        $text_description = strtolower($caption);

        $post_caption = ' ' . str_replace(array('+', '%0A'), ' ',
                urlencode(str_replace(array('#', '@'), array(' HASHTAG', ' MENTION'),
                    $text_description))) . ' ';

        $data = get_option('wpsr_global_shoppable_settings', []);
        $globalShoppableSettings = Arr::get($data, $this->platform, []);

        $matchedSettings = [];
        foreach ($globalShoppableSettings as $shoppableSettings) {
            $hashtagText = Arr::get($shoppableSettings, 'hashtags', '');
            $currentHashtags = explode(',', $hashtagText);
            $foundMatched = apply_filters('wpsocialreviews/include_or_exclude_feed', false, $currentHashtags, $post_caption);
            if($foundMatched) {
                $matchedSettings = $shoppableSettings;
                break;
            }
        }

        return $matchedSettings;
    }

    public function addInFeedsItem($feed, $settings)
    {
        $shoppableFeeds = Arr::get($settings, 'shoppable_settings.shoppable_feeds', []);
        $shoppableSettings = [];

        $hasFound = false;
        $userName = Arr::get($feed, 'username');
        $feedId = Arr::get($feed, 'id');

        if(Arr::get($settings, 'shoppable_settings.enable_shoppable') === 'true' && $userName && $feedId && isset($shoppableFeeds[$userName][$feedId])) {
            $shoppableSettings = $shoppableFeeds[$userName][$feedId];
            $shoppableSettings['from'] = 'template_settings';
            $hasFound = true;
        }

        if(!$hasFound && !empty($this->platform) && Arr::get($settings, 'shoppable_settings.include_shoppable_by_hashtags') === 'true') {
            $shoppableSettings = $this->makeShoppableByHashtags($feed);
            $shoppableSettings['from'] = 'include_shoppable_by_hashtags';
        }

        $urlSettingsText = Arr::get($shoppableSettings, 'url_settings.text', '');
        $urlSettingsUrl  = Arr::get($shoppableSettings, 'url_settings.url', '');

        $is_popup_mode = true;
        if(Arr::get($settings, 'post_settings.display_mode') === 'popup') {
            $is_popup_mode &= !empty($urlSettingsText);
        }

        $data = [
            'show_shoppable'    => $is_popup_mode && !empty($urlSettingsUrl),
            'source_type'       => Arr::get($shoppableSettings, 'source_type', 'custom_url'),
            'from'              => Arr::get($shoppableSettings, 'from', ''),
            'url_settings'      => array(
                'id'                => Arr::get($shoppableSettings, 'url_settings.id'),
                'url'               => Arr::get($shoppableSettings, 'url_settings.url', ''),
                'url_title'         => Arr::get($shoppableSettings, 'url_settings.url_title', ''),
                'open_in_new_tab'   => Arr::get($shoppableSettings, 'url_settings.open_in_new_tab',true),
                'text'              => Arr::get($shoppableSettings, 'url_settings.text', '')
            )
        ];

        $feed['shoppable_options'] = $data;
        return $feed;
    }

    public function makeShoppableFeeds($settings, $platform)
    {
        $shoppable_settings = Arr::get($settings, 'feed_settings.shoppable_settings');
        //if shoppable is not enabled then return default data
        $templateShoppableSettings = Arr::get($shoppable_settings, 'enable_shoppable');
        $globalShoppableSettings   = Arr::get($shoppable_settings, 'include_shoppable_by_hashtags');
        if($templateShoppableSettings === 'false' &&  $globalShoppableSettings === 'false') {
            return $settings;
        }

        $this->platform = $platform;

        $feeds = Arr::get($settings, 'dynamic.items', []);
        $shoppableFeeds = [];
        foreach ($feeds as $feed) {
            $shoppableFeeds[] = $this->addInFeedsItem($feed, $settings['feed_settings']);
        }

        $settings['dynamic']['items'] = $shoppableFeeds;
        return $settings;
    }
}