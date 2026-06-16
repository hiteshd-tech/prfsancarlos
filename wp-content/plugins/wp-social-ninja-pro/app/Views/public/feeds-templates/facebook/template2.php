<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;
$single_album_id = Arr::get($template_meta, 'source_settings.single_album_id');
if (!empty($feeds) && is_array($feeds)) {
    $column      = isset($template_meta['column_number']) ? $template_meta['column_number'] : 4;
    $columnClass = 'wpsr-col-' . $column;
    $layout_type = isset($template_meta['layout_type']) && defined('WPSOCIALREVIEWS_PRO') ? $template_meta['layout_type'] : 'timeline';

    if ($feed_type !== 'timeline_feed' && !defined('WPSOCIALREVIEWS_PRO')) {
        echo '<p>' . __('You need to upgrade to pro to use this feature.', 'wp-social-ninja-pro') . '</p>';
        return;
    }

    if(!Arr::get($template_meta, 'post_settings')) {
        return;
    }

    if($feed_type === 'album_feed'){
        $feeds = array_values(array_filter($feeds, function ($feed) {
            return isset($feed['photos']);
        }));
    }

    foreach($feeds as $index => $feed) {
        $page_id = Arr::get($feed, 'page_id', null);
        if ($index >= ($feed_type === 'album_feed' ? 0 : $sinceId) && $index <= ($feed_type === 'album_feed' ? count($feeds) : $maxId)) {
            if ($layout_type !== 'carousel') {
                /**
                 * facebook_feed_template_item_wrapper_before hook.
                 *
                 * @hooked FacebookFeedTemplateHandler::renderTemplateItemWrapper 10 - (outputs opening divs for the template item)
                 **/
                do_action('wpsocialreviews/facebook_feed_template_item_wrapper_before', $template_meta);

            }
            $post_id = ($feed_type == 'album_feed') ? Arr::get($feed, 'photos.data.0.id', '') : Arr::get($feed, 'id', '');
            ?>

            <div role="group" class="wpsr-fb-feed-item wpsr-fb-post <?php echo ($layout_type === 'carousel' && defined('WPSOCIALREVIEWS_PRO')) ? 'swiper-slide' : ''; echo ($feed_type === 'album_feed') ? ' wpsr-album-cover-photo-wrapper' : ''; ?>" data-post_id="<?php echo esc_attr($post_id); ?>" 
            data-user_name="<?php echo esc_attr($page_id); ?>"
            data-image_size="<?php echo esc_attr($imageResolution); ?>">
                <?php if($feed_type === 'timeline_feed'){ ?>
                    <div class="wpsr-fb-feed-inner">
                        <?php

                        /**
                         * facebook_feed_template_content_wrapper_before hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::setWrapperContentAttributes 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_template_content_wrapper_before', $template_meta, $feed, $templateId, $index);
                            /**
                             * facebook_feed_media hook.
                             *
                             * @hooked FacebookFeedTemplateHandler::renderFeedMedia 10
                             * */
                            do_action('wpsocialreviews/facebook_feed_media', $feed, $template_meta);                    
                        ?>
                        </div> <!-- End of the wrapper div for the Facebook timeline feed media content -->
                        <?php

                        /**
                         * facebook_feed_author hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::renderFeedAuthor 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_author', $feed, $template_meta);

                        /**
                         * facebook_feed_description hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::renderFeedDescription 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_description', $feed, $template_meta);

                        /**
                         * facebook_feed_summary_card hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::renderFeedSummaryCard 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_summary_card', $feed, $template_meta);

                        /**
                         * facebook_feed_statistics hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::renderFeedStatistics 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_statistics', $feed, $template_meta, $translations);
                        ?>
                    </div>
                <?php }

                if($feed_type === 'photo_feed') { 
                    /**
                     * facebook_feed_template_content_wrapper_before hook.
                     *
                     * @hooked FacebookFeedTemplateHandler::setWrapperContentAttributes 10
                     * */
                    do_action('wpsocialreviews/facebook_feed_template_content_wrapper_before', $template_meta, $feed, $templateId, $index);
                        /**
                         * facebook_feed_media hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::renderFeedMedia 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_media', $feed, $template_meta);
                    ?>
                    </div> <!-- End of the wrapper div for the Facebook photo feed media content -->
                    <?php
                }

                if($feed_type === 'video_feed' || $feed_type === 'video_playlist_feed') { 
                    /**
                     * facebook_feed_template_content_wrapper_before hook.
                     *
                     * @hooked FacebookFeedTemplateHandler::setWrapperContentAttributes 10
                     * */
                    do_action('wpsocialreviews/facebook_feed_template_content_wrapper_before', $template_meta, $feed, $templateId, $index);
                        /**
                         * facebook_feed_videos hook.
                         *
                         * @hooked render_facebook_feed_videos 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_videos', $feed, $template_meta);
                    ?>
                    </div> <!-- End of the wrapper div for the Facebook feed videos content -->
                    <?php
                }

                if($feed_type === 'event_feed') { ?>
                    <div class="wpsr-fb-feed-inner">
                        <?php
                        /**
                         * facebook_feed_template_content_wrapper_before hook.
                         *
                         * @hooked FacebookFeedTemplateHandler::setWrapperContentAttributes 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_template_content_wrapper_before', $template_meta, $feed, $templateId, $index);

                        /**
                         * facebook_feed_event hook.
                         *
                         * @hooked render_facebook_feed_events 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_events', $feed, $template_meta, $translations);
                        ?>
                    </div>
                    </div> <!-- End of the wrapper div for the Facebook feed events content -->
                <?php }


                if($feed_type === 'single_album_feed' && $single_album_id) { ?>

                    <div class="wpsr-fb-feed-inner wpsr-single-album-feed">
                        <?php
                        /**
                         * facebook_feed_videos hook.
                         *
                         * @hooked render_facebook_feed_videos 10
                         * */
                        do_action('wpsocialreviews/facebook_feed_single_album_feed', $feed, $template_meta, $templateId, $pagination_settings, $index);?>
                    </div>
                    
                    <?php
                }

                if($feed_type === 'album_feed') {
                    /**
                     * facebook_feed_media hook.
                     *
                     * @hooked FacebookFeedTemplateHandler::renderFeedMedia 10
                     * */
                    do_action('wpsocialreviews/facebook_feed_album', $feed, $template_meta, $templateId , $pagination_settings);
                }
                ?>

            </div>
            <?php if ($layout_type !== 'carousel') { ?>
                </div>
            <?php } ?>
            <?php
        }
    }
}