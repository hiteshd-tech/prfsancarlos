<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\Framework\Support\Arr;
$likesCount = Arr::get($feed, 'statistics.like_count', '');
$commentsCount = Arr::get($feed, 'statistics.comment_count', '');
$viewsCount = Arr::get($feed, 'statistics.view_count', '');
$userID = Arr::get($feed, 'user.name');
$videoID = Arr::get($feed, 'id');
$videoLink = 'https://www.tiktok.com/@' . $userID . '/video/' . $videoID;
$displayLikesCount = Arr::get($template_meta, 'post_settings.display_likes_count');
$displayCommentsCount = Arr::get($template_meta, 'post_settings.display_comments_count');
$displayViewsCount = Arr::get($template_meta, 'post_settings.display_views_count');

$addRemoveSpacingClass = ($displayLikesCount === 'false')
    && ($displayCommentsCount === 'false')
    && ($displayViewsCount === 'false');

?>
<div class="wpsr-tiktok-feed-statistics <?php echo esc_attr($addRemoveSpacingClass ? 'wpsr-remove-white-space' : ''); ?> ">
    <div class="wpsr-tiktok-feed-reactions">
        <?php if( Arr::get($template_meta, 'post_settings.display_likes_count') === 'true'){ ?>
            <a class="wpsr-tiktok-feed-reaction-wrapeer" href="<?php echo esc_url($videoLink); ?>" target="_blank" rel="noopener noreferrer nofollow">
                <span class="wpsr-tiktok-feed-reactions-icon-like wpsr-tiktok-feed-reactions-icon"></span>
                <span class="wpsr-tiktok-feed-reaction-count">
                    <?php echo esc_html(GlobalHelper::shortNumberFormat($likesCount)); ?>
                </span>
            </a>
        <?php } ?>
        <?php if( Arr::get($template_meta, 'post_settings.display_comments_count') === 'true'){ ?>
            <a class="wpsr-tiktok-feed-reaction-wrapeer" href="<?php echo esc_url($videoLink); ?>" target="_blank" rel="noopener noreferrer nofollow">
                <span class="wpsr-tiktok-feed-reactions-icon-comment wpsr-tiktok-feed-reactions-icon"></span>
                <span class="wpsr-tiktok-feed-reaction-count">
                    <?php echo esc_html(GlobalHelper::shortNumberFormat($commentsCount)); ?>
                </span>
            </a>
        <?php } ?>
        <?php if( Arr::get($template_meta, 'post_settings.display_views_count') === 'true'){ ?>
            <a class="wpsr-tiktok-feed-reaction-wrapeer" href="<?php echo esc_url($videoLink); ?>" target="_blank" rel="noopener noreferrer nofollow">
                <span class="wpsr-tiktok-feed-reactions-icon-play wpsr-tiktok-feed-reactions-icon"></span>
                <span class="wpsr-tiktok-feed-reaction-count">
                    <?php echo esc_html(GlobalHelper::shortNumberFormat($viewsCount)); ?>
                </span>
            </a>
        <?php } ?>
    </div>
</div>