<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;


use WPSocialReviews\App\Hooks\Handlers\ShortcodeHandler;
use WPSocialReviews\App\Services\GlobalSettings;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\Common;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\Helper as InstagramHelper;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\InstagramFeed;
use WPSocialReviews\Framework\Foundation\App;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviewsPro\App\Traits\LoadView;
use WPSocialReviews\App\Services\DataProtector;

class InstagramTemplateHandlerPro
{
    use LoadView;

    public function fetchInstagramComments($response, $accountDetails)
    {
        if (isset($accountDetails['api_type']) && $accountDetails['api_type'] === 'business') {
            $mediaIds = array_map(function ($value) {
                if ($value['comments_count'] > 0) {
                    return $value['id'];
                }
            }, $response);
            $mediaIds = array_filter($mediaIds);
            $mediaIds = array_slice($mediaIds, 0, 50);

            $fields = [
                'id',
                'username',
                'text',
                'timestamp',
                'like_count',
            ];

            $dataProtector = new DataProtector();
            $decrypt_access_token = $dataProtector->decrypt($accountDetails['access_token']) ? $dataProtector->decrypt($accountDetails['access_token']) : $accountDetails['access_token'];
            $q = [
                'ids'          => implode(',', $mediaIds),
                'fields'       => implode(',', $fields),
                'access_token' => $decrypt_access_token,
                'limit'        => 10,
            ];

            $apiUrl = "https://graph.facebook.com/comments?" . http_build_query($q);

            if (filter_var($apiUrl, FILTER_VALIDATE_URL)) {
                $comments = (new Common())->makeRequest($apiUrl);
                foreach ($response as $idx => $media) {
                    $mediaId = $media['id'];
                    if (!isset($comments[$mediaId])) {
                        continue;
                    }

                    // Sanitize comments
                    $sanitizedComments = [];
                    if (isset($comments[$mediaId]['data']) && is_array($comments[$mediaId]['data'])) {
                        foreach ($comments[$mediaId]['data'] as $comment) {
                            // Sanitize comment text
                            if (isset($comment['text'])) {
                                $comment['text'] = GlobalHelper::sanitizeForStorage($comment['text']);
                            }
                            $sanitizedComments[] = $comment;
                        }
                    }

                    $response[$idx]['comments'] = $sanitizedComments;
                }
            }
        }

        return $response;
    }

    public function instagramFeedsLimit()
    {
        return 100;
    }

    public function getLikesCount($feed)
    {
        return isset($feed['like_count']) ? $feed['like_count'] : 0;
    }

    public function getCommentsCount($feed)
    {
        return isset($feed['comments_count']) ? $feed['comments_count'] : 0;
    }

    public function feedsByPopularity($feeds, $popularity_type)
    {
        $multiply = ($popularity_type === 'most_popular') ? -1 : 1;
        usort($feeds, function ($m1, $m2) use ($multiply) {
            $sum1 = $this->getLikesCount($m1) + $this->getCommentsCount($m1);
            $sum2 = $this->getLikesCount($m2) + $this->getCommentsCount($m2);


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
     * Retrieve instagram load more data
     *
     * @since 1.2.5
     *
     **/
    public function getPaginatedInstaFeedHtml($content, $templateId, $page)
    {
        $app                 = App::getInstance();
        $shortcodeHandler = new ShortcodeHandler();

        $template_meta = $shortcodeHandler->templateMeta($templateId, 'instagram');
        $feed          = (new InstagramFeed())->getTemplateMeta($template_meta);
        $settings      = $shortcodeHandler->formatFeedSettings($feed);

        $templateMapping = [
            'template1' => 'public.feeds-templates.instagram.template1',
            'template2' => 'public.feeds-templates.instagram.template2',
        ];
        $template        = Arr::get($settings['feed_settings'], 'template', '');
        $file            = $templateMapping[$template];

        $pagination_settings = $shortcodeHandler->formatPaginationSettings($feed);
        $sinceId             = (($page - 1) * $pagination_settings['paginate']);
        $maxId               = ($sinceId + $pagination_settings['paginate']) - 1;

        $global_settings = get_option('wpsr_instagram_global_settings');
        $advanceSettings = (new GlobalSettings())->getGlobalSettings('advance_settings');

        $image_settings = [
            'optimized_images' => Arr::get($global_settings, 'global_settings.optimized_images', 'false'),
            'has_gdpr' => Arr::get($advanceSettings, 'has_gdpr', "false")
        ];

        return (string) $app->view->make($file, array(
            'templateId'    => $templateId,
            'feeds'         => $settings['feeds'],
            'template_meta' => $settings['feed_settings'],
            'image_settings'=> $image_settings,
            'sinceId'       => $sinceId,
            'maxId'         => $maxId,
        ));
    }

    /**
     *
     * Render Instagram Feed Statistics HTML
     *
     * @param $feed
     * @param $template_meta
     *
     * @since 1.3.0
     *
     **/
    public function renderInstagramPostStatisticsHtml($feed = [], $template_meta = [])
    {
        if (isset($feed['like_count']) || isset($feed['comments_count'])) {
            ?>

            <div class="wpsr-ig-post-statistics">
                <?php if (isset($feed['like_count']) && isset($template_meta['post_settings']['display_likes_counter']) && $template_meta['post_settings']['display_likes_counter'] === 'true') { ?>
                    <div class="wpsr-ig-post-single-statistic wpsr-ig-post-like-count">
                        <svg aria-label="Like" viewBox="0 0 48 48">
                            <path d="M34.6 6.1c5.7 0 10.4 5.2 10.4 11.5 0 6.8-5.9 11-11.5 16S25 41.3 24 41.9c-1.1-.7-4.7-4-9.5-8.3-5.7-5-11.5-9.2-11.5-16C3 11.3 7.7 6.1 13.4 6.1c4.2 0 6.5 2 8.1 4.3 1.9 2.6 2.2 3.9 2.5 3.9.3 0 .6-1.3 2.5-3.9 1.6-2.3 3.9-4.3 8.1-4.3m0-3c-4.5 0-7.9 1.8-10.6 5.6-2.7-3.7-6.1-5.5-10.6-5.5C6 3.1 0 9.6 0 17.6c0 7.3 5.4 12 10.6 16.5.6.5 1.3 1.1 1.9 1.7l2.3 2c4.4 3.9 6.6 5.9 7.6 6.5.5.3 1.1.5 1.6.5.6 0 1.1-.2 1.6-.5 1-.6 2.8-2.2 7.8-6.8l2-1.8c.7-.6 1.3-1.2 2-1.7C42.7 29.6 48 25 48 17.6c0-8-6-14.5-13.4-14.5z"></path>
                        </svg>
                        <span><?php echo esc_html(GlobalHelper::numberWithCommas($feed['like_count'])  ); ?></span>
                    </div>
                <?php } ?>

                <?php if (isset($feed['comments_count']) && isset($template_meta['post_settings']['display_comments_counter']) && $template_meta['post_settings']['display_comments_counter'] === 'true') { ?>
                    <div class="wpsr-ig-post-single-statistic wpsr-ig-post-comment-comment">
                        <svg aria-label="Comment" viewBox="0 0 48 48">
                            <path clip-rule="evenodd"
                                  d="M47.5 46.1l-2.8-11c1.8-3.3 2.8-7.1 2.8-11.1C47.5 11 37 .5 24 .5S.5 11 .5 24 11 47.5 24 47.5c4 0 7.8-1 11.1-2.8l11 2.8c.8.2 1.6-.6 1.4-1.4zm-3-22.1c0 4-1 7-2.6 10-.2.4-.3.9-.2 1.4l2.1 8.4-8.3-2.1c-.5-.1-1-.1-1.4.2-1.8 1-5.2 2.6-10 2.6-11.4 0-20.6-9.2-20.6-20.5S12.7 3.5 24 3.5 44.5 12.7 44.5 24z"
                                  fill-rule="evenodd"></path>
                        </svg>
                        <span><?php echo esc_html(GlobalHelper::numberWithCommas($feed['comments_count'])); ?></span>
                    </div>
                <?php } ?>
            </div>

            <?php
        }
    }

    public function renderInstagramShoppableButtonHtml($feed = array(), $template_meta = array())
    {
        $target = Arr::get($feed, 'shoppable_options.url_settings.open_in_new_tab') ? '_blank' : '';
        $url = Arr::get($feed, 'shoppable_options.url_settings.url');
        $buttonText = Arr::get($feed, 'shoppable_options.url_settings.text', '');
        ?>
        <a class="wpsr-shoppable-button" type="button" href="<?php echo esc_url($url)?>" target="<?php echo esc_attr($target); ?>">
            <?php echo  esc_html($buttonText); ?>

            <?php if(Arr::get($template_meta, 'template') === 'template2') {?>
                <i class="icon-angle-right"></i>
            <?php } ?>
        </a>
        <?php
    }

    /**
     *
     * Render Instagram follow button HTML
     *
     * @param $user
     * @param $settings
     *
     * @since 1.3.0
     *
     **/
    public function renderInstagramFollowButtonHtml($settings = [])
    {
        $account = InstagramHelper::getUserAccountInfo($settings);
        if (isset($settings['follow_button_settings']['display_follow_button']) && isset($account['username']) && $settings['follow_button_settings']['display_follow_button'] === 'true') {
            $follow_button_text = Arr::get($settings, 'follow_button_settings.follow_button_text');
            ?>
            <div class="wpsr-ig-follow-btn">
                <a href="<?php echo esc_url('https://www.instagram.com/' . $account['username']); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr($follow_button_text); ?>">
                    <?php echo esc_html($follow_button_text); ?>
                </a>
            </div>
            <?php
        }
    }

    /**
     *
     * Render Instagram Header Statistics HTML
     *
     * @param $user
     * @param $settings
     *
     * @since 1.3.0
     *
     **/
    public function renderInstagramHeaderStatisticsHtml($user = [], $settings = [], $translations = [])
    {
        $media_count     = Arr::get($user, 'media_count', null);
        $followers_count = Arr::get($user, 'followers_count', 0);
        if ($media_count || $followers_count) {
            ?>
            <div class="wpsr-ig-header-statistics">
                <?php if ($media_count && Arr::get($settings, 'display_posts_counter') === 'true') { ?>
                    <div class="wpsr-ig-header-statistic-item">
                        <strong><?php echo esc_html(GlobalHelper::shortNumberFormat($user['media_count'])); ?> </strong>
                        <?php echo esc_html(Arr::get($translations, 'posts') ?: __( 'Posts', 'wp-social-ninja-pro' )); ?>
                    </div>
                <?php } ?>
                <?php if ($followers_count && Arr::get($settings, 'display_followers_counter') === 'true') { ?>
                    <div class="wpsr-ig-header-statistic-item">
                        <strong><?php echo esc_html(GlobalHelper::shortNumberFormat($user['followers_count'])); ?></strong>
                        <?php echo esc_html(Arr::get($translations, 'followers') ?: __('Followers', 'wp-social-ninja-pro')); ?>
                    </div>
                <?php } ?>
            </div>
        <?php }
    }

    public function renderInstagramShoppableIcon()
    {
        ?>
        <div class="wpsr-ig-shoppble-icon-top">
            <img src="<?php echo esc_url(WPSOCIALREVIEWS_URL.'assets/images/svg/shoppable-icon.svg'); ?>" alt="shopping bag">
        </div>
        <?php
    }

    /**
     *
     * Trim instagram caption
     *
     * @param $caption
     * @param $trim_words_count
     *
     * @since 1.3.0
     *
     **/
    public function trimCaptionWords($caption, $trim_words_count)
    {
        $word_count = 0;
        $output = '';
        $in_tag = false;
        $words = preg_split('/(\s+)/u', $caption, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($words as $part) {
            if (preg_match('/^<[^>]+>$/', $part)) {
                $output .= $part;
            } elseif (trim($part) === '') {
                $output .= $part;
            } else {
                $word_count++;
                $output .= $part;
                if ($word_count >= $trim_words_count) {
                    break;
                }
            }
        }

        if ($word_count >= $trim_words_count) {
            $output = rtrim($output) . '...';
        }
        return $output;
    }

}