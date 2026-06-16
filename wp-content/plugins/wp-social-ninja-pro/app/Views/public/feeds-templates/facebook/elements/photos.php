<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\Framework\Support\Arr;

$feed_type = Arr::get($template_meta, 'source_settings.feed_type');
$display_mode = Arr::get($template_meta, 'post_settings.display_mode');
$permalink_url = $display_mode !== 'none' && $feed_type === 'album_feed' ? Arr::get($feed, 'from.link') : Arr::get($feed, 'permalink_url');
$desktop_column = Arr::get($template_meta, 'responsive_column_number.desktop');
$tablet_column = Arr::get($template_meta, 'responsive_column_number.tablet');
$mobile_column = Arr::get($template_meta, 'responsive_column_number.mobile');

$imageResolution = Arr::get($template_meta, 'post_settings.resolution');
$display_mode = Arr::get($template_meta, 'post_settings.display_mode');
$display_optimized_image = Arr::get($image_settings, 'optimized_images', 'false');
$has_gdpr = Arr::get($image_settings, 'has_gdpr', 'false');


$classes = 'wpsr-mb-30 wpsr-col-' . esc_attr($desktop_column) . ' wpsr-col-sm-' . esc_attr($tablet_column) . ' wpsr-col-xs-' . esc_attr($mobile_column);

$attrs = [
    'class'  => 'class="wpsr-feed-link"',
    'target' => $display_mode !== 'none' ? 'target="_blank"' : '',
    'rel'    => 'rel="nofollow"',
    'href'   =>  $display_mode !== 'none' ? 'href="'.esc_url($permalink_url).'"' : '',
];

if($pagination_type === 'none'){
    $sinceId = 0;
    $maxId = count($photos);
}

?>
    <?php foreach($photos as $index => $photo) {
        $page_id = Arr::get($feed, 'page_id', null);
        $post_id = ($feed_type == 'album_feed') ? Arr::get($feed, 'photos.data.0.id', '') : Arr::get($feed, 'id', '');
        if ($index >= $sinceId && $index <= $maxId) {
            ?>

            <div class="<?php echo esc_attr($classes); ?>">
                <div class="wpsr-fb-feed-item">
                    <?php 
                    do_action('wpsocialreviews/facebook_feed_template_content_wrapper_before', $template_meta, $feed, $templateId, $index);
                    ?>
                        <div class="wpsr-fb-feed-image">
                            <?php if(!empty($photo)) {
                                /**
                                 * facebook_feed_photo_feed_image hook.
                                 *
                                 * @hooked render_facebook_feed_photo_feed_image 10
                                 * */
                                do_action('wpsocialreviews/facebook_feed_photo_feed_image', $photo, $template_meta, $attrs, $image_settings);
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
    }
    ?>
