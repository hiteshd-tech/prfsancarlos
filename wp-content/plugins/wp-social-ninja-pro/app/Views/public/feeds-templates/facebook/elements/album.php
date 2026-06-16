<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;

$feed_type = Arr::get($template_meta, 'source_settings.feed_type');
$layout_type = Arr::get($template_meta, 'layout_type');
$column_gaps = Arr::get($template_meta, 'column_gaps');
$column_class = $layout_type !== 'carousel' ? 'wpsr-column-gap-'.$column_gaps : '';
$display_mode = Arr::get($template_meta, 'post_settings.display_mode');
$permalink_url = $display_mode !== 'none' && $feed_type === 'album_feed' ? Arr::get($feed, 'from.link') : Arr::get($feed, 'permalink_url');

$attrs = [
    'class'  => 'class="wpsr-feed-link"',
    'target' => $display_mode !== 'none' ? 'target="_blank"' : '',
    'rel'    => 'rel="nofollow"',
    'href'   =>  $display_mode !== 'none' ? 'href="'.esc_url($permalink_url ?? '').'"' : '',
];
?>
<div class="wpsr-album-cover-photo-wrapper-inner" id="<?php echo esc_attr(Arr::get($feed, 'id')); ?>" data-template-id="<?php echo esc_attr($templateId)?>">
    <div class="wpsr-fb-feed-image <?php echo esc_attr($img_class) ?>" >
        <?php if($feed_type === 'album_feed'){
                /**
                 * facebook_feed_photo_feed_image hook.
                 *
                 * @hooked render_facebook_feed_photo_feed_image 10
                 * */
                do_action('wpsocialreviews/facebook_feed_photo_feed_image', $feed, $template_meta, $attrs, $image_settings);
        } ?>
    </div>
    <?php
        /**
         * facebook_feed_album_feed_info hook.
         *
         * @hooked render_facebook_feed_info 10
         * */
        do_action('wpsocialreviews/facebook_feed_album_feed_info', $feed, 'info');
    ?>
</div>

<div class="wpsr-fb-feed-album-wrapper" id="wpsr-album-<?php echo esc_attr(Arr::get($feed, 'id')); ?>">
    <div class="wpsr-fb-feed-bread-crumbs">
        <span class="wpsr-fb-feed-bread-crumbs-album"> <?php echo __('Albums', 'wp-social-ninja-pro'); ?> </span> > <?php echo esc_html(Arr::get($feed, 'name')); ?>
    </div>

    <div class="wpsr-fb-feed-album-header">

        <?php
        /**
         * facebook_feed_album_feed_info hook.
         *
         * @hooked render_facebook_feed_info 10
         * */
        do_action('wpsocialreviews/facebook_feed_album_feed_info', $feed, 'header');
        ?>
        <div class="wpsr-fb-all-feed wpsr-row <?php echo esc_attr($column_class); ?>" id="wpsr-album-feed-<?php echo esc_attr(Arr::get($feed, 'id'))?>">
<!--            photos will rendered inside this div-->
        </div>
        <?php
        if (count($photos) > $paginate && $layout_type !== 'carousel' && $pagination_type === 'load_more') { ?>
            <div class="wpsr-fb-feed-footer wpsr-fb-feed-follow-button-group wpsr-row" >
                <?php do_action('wpsocialreviews/load_more_button', $template_meta, $templateId, $paginate, $layout_type, $total, $feed_type, $feed); ?>
            </div>
        <?php } ?>
    </div>
</div>