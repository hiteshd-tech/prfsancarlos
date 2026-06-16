<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;

$feed_type = Arr::get($template_meta, 'source_settings.feed_type');
$layout_type = Arr::get($template_meta, 'layout_type');
$column_gaps = Arr::get($template_meta, 'column_gaps');
$column_class = $layout_type !== 'carousel' ? 'wpsr-column-gap-'.$column_gaps : '';
$display_mode = Arr::get($template_meta, 'post_settings.display_mode');
$permalink_url = $display_mode !== 'none' && $feed_type === 'album_feed' ? Arr::get($feed, 'from.link') : Arr::get($feed, 'permalink_url');


$href = $display_mode !== 'none' ? 'href="'.esc_url($permalink_url ?? '').'"' : '';

if($feed_type === 'single_album_feed'){
    $href = 'href="'.Arr::get($feed, 'link').'"';
}

$attrs = [
    'class'  => 'class="wpsr-feed-link"',
    'target' => $display_mode !== 'none' ? 'target="_blank"' : '',
    'rel'    => 'rel="nofollow"',
    'href'   =>  $href,
];
?>
<div class="wpsr-album-cover-photo-wrapper-inner" id="<?php echo esc_attr(Arr::get($feed, 'id')); ?>" data-template-id="<?php echo esc_attr($templateId)?>">
    <?php 
        do_action('wpsocialreviews/facebook_feed_template_content_wrapper_before', $template_meta, $feed, $templateId, $index);
    ?>
        <div class="wpsr-fb-feed-image <?php echo esc_attr($img_class) ?>" >
            <?php if($feed_type === 'single_album_feed'){
                    /**
                     * facebook_feed_photo_feed_image hook.
                     *
                     * @hooked render_facebook_feed_photo_feed_image 10
                     * */
                    do_action('wpsocialreviews/facebook_feed_photo_feed_image', $feed, $template_meta, $attrs, $image_settings);
            } ?>
        </div>
    </div>
</div>

<div class="wpsr-fb-feed-album-wrapper" id="wpsr-album-<?php echo esc_attr(Arr::get($feed, 'id')); ?>">
    <div class="wpsr-fb-feed-album-header">
        <?php
        if (count($photos) > $paginate && $layout_type !== 'carousel' && $pagination_type === 'load_more') { ?>
            <div class="wpsr-fb-feed-footer wpsr-fb-feed-follow-button-group wpsr-row" >
                <?php do_action('wpsocialreviews/load_more_button', $template_meta, $templateId, $paginate, $layout_type, $total, $feed_type, $feed); ?>
            </div>
        <?php } ?>
    </div>
</div>