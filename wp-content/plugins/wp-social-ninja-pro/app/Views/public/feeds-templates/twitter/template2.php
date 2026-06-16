<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Feeds\Twitter\Helper as TwitterHelper;
use WPSocialReviews\Framework\Support\Arr;

if (!empty($wpsr_feeds) && is_array($wpsr_feeds)) {
    $layout_type  = isset($template_meta['layout_type']) ? $template_meta['layout_type'] : '';
    ?>
    <?php foreach ($wpsr_feeds as $index => $feed) {
        if ($index >= $sinceId && $index <= $maxId) {
            $retweeted_tweet = isset($feed['retweeted_status']) ? $feed['retweeted_status'] : '';
            $quoted_tweet    = isset($feed['quoted_status']) ? $feed['quoted_status'] : '';
            $tweet_action_target = Arr::get($template_meta, 'advance_settings.tweet_action_target', 'popup');

            if ((isset($template_meta['advance_settings']['show_retweeted_tweet']) && $template_meta['advance_settings']['show_retweeted_tweet'] === 'false' && $retweeted_tweet) || (isset($template_meta['advance_settings']['show_quoted_tweet']) && $template_meta['advance_settings']['show_quoted_tweet'] === 'false' && $quoted_tweet)) {
                continue;
            }
            $desktop_column = Arr::get($template_meta, 'responsive_column_number.desktop');
            $tablet_column = Arr::get($template_meta, 'responsive_column_number.tablet');
            $mobile_column = Arr::get($template_meta, 'responsive_column_number.mobile');
            ?>
            <div role="group" class="<?php echo $layout_type === 'carousel' ? 'swiper-slide' : ''; ?> <?php echo $layout_type === 'carousel' ? '' : 'wpsr-col-' . esc_attr($desktop_column) . ' wpsr-col-sm-' . esc_attr($tablet_column) . ' wpsr-col-xs-' . esc_attr($mobile_column); ?>">
                <div class="wpsr-twitter-tweet">
                    <div class="wpsr-twitter-author-tweet">
                        <?php
                        /**
                         * tweet_retweeted_status hook.
                         *
                         * @hooked wpsr_render_tweet_retweeted_status_html 10
                         * */
                        do_action('wpsocialreviews/tweet_retweeted_status', $feed);
                        ?>
                        <div class="wpsr-tweet-author-info">
                            <?php
                            /**
                             * tweet_author_avatar hook.
                             *
                             * @hooked wpsr_render_tweet_author_avatar_html 10
                             * */
                            do_action('wpsocialreviews/tweet_author_avatar', $feed, $template_meta);
                            ?>
                            <div class="wpsr-tweet-author-links">
                                <?php
                                /**
                                 * tweet_author_info hook.
                                 *
                                 * @hooked wpsr_render_tweet_author_verified_icon_html 10
                                 * @hooked wpsr_render_tweet_author_name_html 5
                                 * */
                                do_action('wpsocialreviews/tweet_author_info', $feed, $template_meta);
                                ?>
                                <div class="wpsr-tweet-author-username-time">
                                    <?php
                                    /**
                                     * tweet_author_info hook.
                                     *
                                     * @hooked wpsr_render_tweet_time_html 10
                                     * @hooked wpsr_render_tweet_author_username_html 10
                                     * */
                                    do_action('wpsocialreviews/tweet_author_username', $feed, $template_meta);
                                    do_action('wpsocialreviews/tweet_time', $feed, $template_meta);
                                    ?>
                                </div>
                            </div>
                            <?php if (isset($template_meta['advance_settings']) && $template_meta['advance_settings']['twitter_logo'] === 'true') { ?>
                                <div class="wpsr-tweet-logo">
                                    <a target="_blank"
                                       href="<?php echo esc_url('https://twitter.com/' . Arr::get($feed, 'user.screen_name', '') . '/status/' . Arr::get($feed, 'id', '')); ?>">
                                        <?php echo TwitterHelper::getSvgIcons('twitter_logo'); ?>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="wpsr-tweet-content">
                            <?php
                            /**
                             * tweet_content hook.
                             *
                             * @hooked wpsr_render_tweet_content_html 10
                             * */
                            do_action('wpsocialreviews/tweet_content', $feed, $template_meta, $templateId, $index);
                            ?>
                        </div>
                        <div class="wpsr-tweet-actions" data-actions="<?php echo esc_attr($tweet_action_target); ?>">
                            <?php
                            /**
                             * tweet_author_info hook.
                             *
                             * @hooked wpsr_render_tweet_action_favorite_count 15
                             * @hooked wpsr_render_tweet_action_retweet_count 10
                             * @hooked wpsr_render_tweet_action_reply 5
                             * */
                            do_action('wpsocialreviews/tweet_actions', $feed, $template_meta);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } //if condition end
    } ?>
    <?php
}

