<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\GlobalSettings;

if(count($reviews)) {
 $notification_position = Arr::get($templateMeta, 'notification_settings.notification_position');
 $display_mode = Arr::get($templateMeta, 'notification_settings.display_mode');
 $notificationDelay = Arr::get($templateMeta, 'notification_settings.notification_delay', 5000);
 $initialDelay = Arr::get($templateMeta, 'notification_settings.initial_delay', 5000);
 $delayFor     = Arr::get($templateMeta, 'notification_settings.delay_for', 5000);
 $displayDate = Arr::get($templateMeta, 'notification_settings.display_date', 'true');
 $translations =  GlobalSettings::getTranslations();
 $platform_name = Arr::get($reviews, '0.platform_name', '');
 $display_tp_brand = $templateMeta['display_tp_brand'] === 'true';
 $display_platform_icon = $templateMeta['isPlatformIcon'] === 'true';
 $url = Arr::get($templateMeta, 'notification_settings.url', '');
 if(empty($url) || $display_mode === 'none' || $display_mode === 'popup') {
     $url = '#';
 }
//  error_log(print_r($reviews[0], true));
$product_name  = Arr::get($reviews, '0.fields.product_name');
$product_thumbnail  = Arr::get($reviews, '0.fields.product_thumbnail');
$product_thumbnail_url  = Arr::get($product_thumbnail, '0', '');

$source_id = Arr::get($reviews, '0.source_id', null);
$customPlatformData = ['logo' => '', 'name' => ''];
if (method_exists(Helper::class, 'getCustomPlatformLogo')) {
    $customPlatformData = Helper::getCustomPlatformLogo($platform_name, $source_id);
}
$customLogo = Arr::get($customPlatformData, 'logo', '');
$customName = Arr::get($customPlatformData, 'name', '');
?>
<a id="wpsr-notification-card-<?php echo esc_attr($templateId); ?>"
     class="wpsr-reviews-notification-<?php echo esc_attr($templateId);?> wpsr-reviews-notification-card-wrapper <?php echo 'wpsr-'.$notification_position;?> cursor-<?php echo $display_mode;?>"
     href="<?php echo esc_url($url); ?>"
     target="_blank"
     data-notification_id="<?php echo esc_attr($templateId); ?>"
     data-total="<?php echo count($reviews); ?>"
     data-index="0"
     data-display_mode="<?php echo esc_attr($display_mode);?>"
     data-notification_delay="<?php echo esc_attr($notificationDelay);?>"
     data-display_date="<?php echo esc_attr($displayDate);?>"
     data-assets_url="<?php echo esc_url(WPSOCIALREVIEWS_URL . 'assets'); ?>"
     data-assets_url_pro="<?php echo esc_url(WPSOCIALREVIEWS_PRO_URL . 'assets/images/'); ?>"
     data-initial_delay="<?php echo esc_attr($initialDelay); ?>"
     data-delay_for="<?php echo esc_attr($delayFor); ?>"
     data-display_tp_brand="<?php echo $display_tp_brand; ?>"
     data-display_platform_icon="<?php echo $display_platform_icon; ?>"
     data-custom_platform_logo="<?php echo esc_url($customLogo); ?>"
>
    <div class="wpsr-reviews-notification-card">
        <?php if($templateMeta['reviewer_image'] === 'true') { ?>
            <div class="wpsr-notification-image-wrapper">
                <div class="wpsr-reviewer-image">
                    <img src="<?php echo (!empty($reviews[0]['media_url'])) ? $reviews[0]['media_url'] : WPSOCIALREVIEWS_URL.'assets/images/template/review-template/placeholder-image.png'; ?>" alt="<?php echo !empty($reviews[0]['reviewer_name']) ?  $reviews[0]['reviewer_name'] : '';?>" width="50" height="50" loading="lazy">
                </div>
            </div>
        <?php } ?>
        <div class="wpsr-notification-content-wrapper">
            <div class="wpsr-review-header">
                <span class="reviewer-name"><?php echo !empty($reviews[0]['reviewer_name']) ? $reviews[0]['reviewer_name'] : ''; ?></span>
                <?php
                    $custom_notification_text = $templateMeta['notification_settings']['custom_notification_text'];
                    if($custom_notification_text){
                ?>
                 <p><?php echo str_replace('{review_rating}', "<span class='review-rating'>{$reviews[0]['rating']}</span>",  $custom_notification_text); ?></p>
                <?php } ?>
            </div>
            <div class="wpsr-notification-body">
                <div class="wpsr-rating-wrapper">
                    <div class="wpsr-rating">
                        <?php echo Helper::generateRatingIcon($reviews[0]['rating']); ?>
                    </div>
                </div>
                <span class="<?php echo Helper::hideLogo($templateMeta, Arr::get($reviews, '0.platform_name')) ? 'wpsr-hide-logo' : 'wpsr-show-logo' ?>"><?php echo Arr::get($translations, 'on') ?: __('on', 'wp-social-ninja-pro'); ?></span>
                <div class="wpsr-review-platform">
                    <?php if(!empty($customLogo)): ?>
                        <img class="<?php echo Helper::hideLogo($templateMeta, Arr::get($reviews, '0.platform_name')) ? 'wpsr-hide-logo' : 'wpsr-show-logo' ?>"
                             src="<?php echo esc_url($customLogo); ?>"
                             alt="<?php echo esc_attr(Arr::get($reviews, '0.platform_name')); ?>"
                             width="20" height="20">
                    <?php else: ?>
                        <img class="<?php echo Helper::hideLogo($templateMeta, Arr::get($reviews, '0.platform_name')) ? 'wpsr-hide-logo' : 'wpsr-show-logo' ?>"
                             src=""
                             alt="<?php echo esc_attr(Arr::get($reviews, '0.platform_name')); ?>"
                             width="20" height="20">
                    <?php endif; ?>
                    <span class="wpsr-review-source-title"><?php echo $product_name; ?></span>
                </div>
            </div>

            <?php if($displayDate === 'true') {?>
                <div class="wpsr-notification-footer">
                    <span class="review-time">
                        <?php
                        if(!empty($reviews[0]['review_time'])) {
                            $review_time = strtotime($reviews[0]['review_time']);
                            // translators: %s is the time difference (e.g., "2 hours", "3 days")
                            echo sprintf(__('%s ago', 'wp-social-ninja-pro'), human_time_diff($review_time));
                        }
                        ?>
                    </span>&nbsp;
                </div>
            <?php } ?>
        </div>
    </div>

    <?php if( $templateMeta['notification_settings']['display_close_button'] === 'true' ) {?>
        <span class="wpsr-close">
          <svg viewBox="0 0 16 16" style="fill: rgb(255, 255, 255);">
            <path d="M3.426 2.024l.094.083L8 6.586l4.48-4.479a1 1 0 011.497 1.32l-.083.095L9.414 8l4.48 4.478a1 1 0 01-1.32 1.498l-.094-.083L8 9.413l-4.48 4.48a1 1 0 01-1.497-1.32l.083-.095L6.585 8 2.106 3.522a1 1 0 011.32-1.498z"></path>
          </svg>
        </span>
    <?php } ?>
</a>
<?php }