<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Helper;

if (!empty($feeds) && is_array($feeds)) {
    $feed_type = Arr::get($template_meta, 'source_settings.feed_type');
    $column = isset($template_meta['column_number']) ? $template_meta['column_number'] : 4;
    $columnClass = 'wpsr-col-' . $column;
    $layout_type = isset($template_meta['layout_type']) && defined('WPSOCIALREVIEWS_PRO') ? $template_meta['layout_type'] : 'grid';
    $animation_img_class = $layout_type === 'carousel' ? 'wpsr-animated-background' : '';

    // Check if the feed type is user_feed and the pro version is not defined
    if ($feed_type !== 'user_feed' && !defined('WPSOCIALREVIEWS_PRO')) {
        echo '<p>' . __('You need to upgrade to pro to use this feature.', 'custom-tiktok-feed') . '</p>';
        return;
    }

    // Check if post_settings exist in template_meta, if not, return
    if (!Arr::get($template_meta, 'post_settings')) {
        return;
    }

    $displayPlatformIcon = Arr::get($template_meta, 'post_settings.display_platform_icon');
    $displayDescription = Arr::get($template_meta, 'post_settings.display_description');
    $displayAuthorPhoto = Arr::get($template_meta, 'post_settings.display_author_photo');
    $displayAuthorName = Arr::get($template_meta, 'post_settings.display_author_name');
    $displayDate = Arr::get($template_meta, 'post_settings.display_date');
    $display_mode = Arr::get($template_meta, 'post_settings.display_mode');
    $addRemoveSpacingClass = ($displayPlatformIcon === 'false')
        && ($displayDescription === 'false')
        && ($displayAuthorPhoto === 'false')
        && ($displayAuthorName === 'false')
        && ($displayDate === 'false');

    foreach ($feeds as $index => $feed) {
        if ($index >= $sinceId && $index <= $maxId) {
            if ($layout_type !== 'carousel') {
                do_action('custom_feed_for_tiktok/tiktok_feed_template_item_wrapper_before', $template_meta);
            }
            $userName = Arr::get($feed, 'user.name');
            $videoID = Arr::get($feed, 'id');
            $videoLink = 'https://www.tiktok.com/@' . $userName . '/video/' . $videoID;
            $media_url = Arr::get($feed, 'media_url', '');
            $imageOptimization = Arr::get($image_settings, 'optimized_images');
            $gdprEnabled = Arr::get($image_settings, 'has_gdpr');
            $imageResolution = Arr::get($template_meta, 'post_settings.resolution');
            $imgClass = !empty($media_url) && !str_contains($media_url ?? '', 'placeholder') ? 'wpsr-tt-post-img wpsr-show' : 'wpsr-tt-post-img wpsr-hide';
$animationImgClass = str_contains($media_url ?? '', 'placeholder') && $media_url ? 'wpsr-animated-background' : '';
            $previewImage = Arr::get($feed, 'media.preview_image_url', '');
            $description = Arr::get($feed, 'text', '');
            $imageResolution = Arr::get($template_meta, 'post_settings.resolution');

            $attrs = [
                'class'  => 'class="wpsr-tiktok-feed-video-preview wpsr-tiktok-feed-video-playmode wpsr-feed-link"',
                'target' => $display_mode !== 'none' ? 'target="_blank"' : '',
                'rel'    => 'rel="nofollow"',
                'href'   =>  $display_mode !== 'none' ? 'href="'.esc_url($videoLink).'"' : '',
            ];
            ?>
        <div tabindex="0" role="group" class="wpsr-tiktok-feed-item wpsr-tt-post <?php echo ($layout_type === 'carousel' && defined('WPSOCIALREVIEWS_PRO')) ? 'swiper-slide' : ''; ?>"
             data-post_id="<?php echo esc_attr($videoID); ?>"
             data-user_name="<?php echo esc_attr($userName); ?>"
             data-image_size="<?php echo esc_attr($imageResolution); ?>"
        >

                <div class="wpsr-tiktok-feed-inner">
                    <div class="wpsr-tiktok-feed-content-preview">
                        <div class="wpsr-tiktok-feed-image">
                            <?php if ($display_mode === 'tiktok'): ?>
                            <a <?php Helper::printInternalString(implode(' ', $attrs)); ?>>
                                <?php else: ?>
                                <div class="wpsr-tiktok-feed-video-preview wpsr-tiktok-feed-video-playmode wpsr-feed-link  <?php echo esc_attr($animationImgClass); ?>"
                                     data-feed_type="<?php echo esc_attr($feed_type); ?>"
                                     data-index="<?php echo esc_attr($index); ?>"
                                     data-playmode="<?php echo esc_attr($template_meta['post_settings']['display_mode']); ?>"
                                     data-template-id="<?php echo esc_attr($templateId); ?>"
                                     data-optimized_images="<?php echo esc_attr($imageOptimization); ?>"
                                     data-has_gdpr="<?php echo esc_attr($gdprEnabled); ?>"
                                     data-image_size="<?php echo esc_attr($imageResolution); ?>"
                                >
                                    <?php endif; ?>
                                    <img class="<?php echo esc_attr($imgClass); ?>" src="<?php echo esc_url($imageOptimization === 'true' ? $media_url : $previewImage); ?>" alt="<?php echo esc_attr($description); ?>"/>
                                    <?php if ($template_meta['post_settings']['display_play_icon'] === 'true'): ?>
                                        <div class="wpsr-tiktok-feed-content-box">
                                            <div class="wpsr-tiktok-feed-video-play">
                                                <div class="wpsr-tiktok-feed-video-play-icon"></div>
                                            </div>
                                        </div>
                                        <?php if($layout_type === 'carousel'){ ?>
                                            <div class="<?php echo esc_attr($animation_img_class); ?>"></div>
                                        <?php } ?>

                                    <?php endif; ?>
                                    <?php if ($display_mode === 'tiktok'): ?>
                            </a>
                            <?php else: ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="wpsr-tiktok-feed-content-wrapper">
                    <?php
                    /**
                     * tiktok_feed_description hook.
                     *
                     * @hooked TiktokTemplateHandler::renderFeedDescription 10
                     * */
                    do_action('custom_feed_for_tiktok/tiktok_feed_description', $feed, $template_meta);
                    ?>
                    <div class="wpsr-tiktok-feed-content-top-wrapper <?php echo esc_attr($addRemoveSpacingClass ? 'wpsr-tiktok-feed-author-avatar-wrapper-remove-spacing' : ''); ?>">
                        <?php
                        /**
                         * tiktok_feed_author hook.
                         *
                         * @hooked TiktokTemplateHandler::renderFeedAuthor 10
                         * */
                        do_action('custom_feed_for_tiktok/tiktok_feed_author', $feed, $template_meta);

                        if ($displayPlatformIcon === 'true') { ?>
                            <a href="<?php echo esc_url($videoLink); ?>" target="_blank" rel="noopener noreferrer nofollow" class="wpsr-tiktok-icon-temp-2"></a>
                        <?php } ?>
                    </div>

                    <?php
                    /**
                     * tiktok_feed_statistics hook.
                     *
                     * @hooked render_tiktok_feed_statistics 10
                     * */
                    do_action('custom_feed_for_tiktok/tiktok_feed_statistics', $template_meta, $feed);
                    ?>
                </div>

                </div>
        </div>

        <?php if ($layout_type !== 'carousel') { ?>
            </div>
        <?php }
        }
    }
}
?>