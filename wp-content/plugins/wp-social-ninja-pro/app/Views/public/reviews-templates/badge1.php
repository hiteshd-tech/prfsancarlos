<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;

$platform_name  = Arr::get($business_info, 'platform_name', '');
$total_platforms = Arr::get($business_info, 'total_platforms', 0);
$total_rating = Arr::get($business_info, 'total_rating', 0);
$display_mode = Arr::get($badge_settings, 'display_mode', '');
$open_in_new_window = Arr::get($badge_settings, 'open_in_new_window', 'true');
$form_shortcode_id = Arr::get($badge_settings, 'form_shortcode_id');

$classes = [
    $display_mode !== 'none' ? 'wpsr-reviews-badge-btn' : 'wpsr-reviews-badge-html',
    'wpsr-'.$badge_settings['badge_position']
];

$attrs = [
    'data-badge_id' =>  $display_mode === 'popup' || $display_mode === 'form_shortcode_id' ? $templateId : '',
    'data-display_mode' =>  $display_mode,
    'id'            =>  $display_mode === 'popup' || $display_mode === 'form_shortcode_id' ? 'wpsr-reviews-badge-'.$templateId : '',
    'class'         =>  implode( ' ', $classes),
];

if($display_mode !== 'none') {
    $attrs['href']      = $display_mode === 'popup' || $display_mode === 'form_shortcode_id' ? '#' : Arr::get($badge_settings, 'url');
    $target = $open_in_new_window === 'true' ? '_blank' : '_self';
    $attrs['target']    = $display_mode === 'popup' ? '' : $target;
};

$attr = '';
foreach ($attrs as $key => $value) {
    if($value) {
        $attr .= $key .'="'.$value.'" ';
    }
}

$platform_name_class = Helper::platformDynamicClassName($business_info);

$custom_num_of_reviews_text     = Arr::get($badge_settings, 'custom_num_of_reviews_text');
if (!empty($custom_num_of_reviews_text)) {
    if ($total_rating >= 1 && strpos($custom_num_of_reviews_text, '{reviews_count}') !== false) {
        $custom_num_of_reviews_text = str_replace('{reviews_count}', number_format($total_rating, 0), $custom_num_of_reviews_text);
    } else {
        $custom_num_of_reviews_text = str_replace('{reviews_count}', '', $custom_num_of_reviews_text);
    }
}

$image_size = $badge_settings['template'] === 'badge2' ? 'small' : '';
$icon = Helper::platformIcon($platform_name, $image_size);

$tagName =  $display_mode !== 'none' ? 'a' : 'div';
$startTag = "<".$tagName . " ". $attr.">";
$endTag = "</".$tagName.">";

$wrapperClasses = [
    'wpsr-reviews-badge-'.$templateId,
    $platform_name_class,
    'wpsr-reviews-'.$badge_settings['template'],
    $display_mode !== 'none' ? 'wpsr-enable-cursor' : '',
    $total_rating < 1 ? 'wpsr-no-reviews' : '',
];

$wrapperClasses = implode( ' ', $wrapperClasses);
?>

<div class="wpsr-reviews-badge-wrapper <?php echo $wrapperClasses; ?>">

    <?php if(defined('FLUENTFORM') && $display_mode === 'form_shortcode_id' && !empty($form_shortcode_id)){ ?>
    <div class="wpsr-reviews-form-popup-overlay">
        <div class="wpsr-reviews-form-popup-box-wraper">
            <div class="wpsr-reviews-form-popup-box-wraper-inner">
                <a class="wpsr-popup-close" href="#">
                    <svg viewBox="0 0 16 16" style="fill: rgb(255, 255, 255);">
                        <path d="M3.426 2.024l.094.083L8 6.586l4.48-4.479a1 1 0 011.497 1.32l-.083.095L9.414 8l4.48 4.478a1 1 0 01-1.32 1.498l-.094-.083L8 9.413l-4.48 4.48a1 1 0 01-1.497-1.32l.083-.095L6.585 8 2.106 3.522a1 1 0 011.32-1.498z">
                        </path>
                    </svg>
                </a>

                <?php echo do_shortcode('[fluentform id="'.esc_attr(Arr::get($badge_settings, 'form_shortcode_id')).'"]'); ?>
            </div>
        </div>
    </div>
    <?php } ?>
<!--conditional tag(div/a)-->
<?php echo $startTag; ?>
    <div class="wpsr-reviews-badge-wrapper-inner">
        <!--logos-->
        <div class="wpsr-business-info-logo">
            <!--single platform-->
            <?php if($badge_settings['display_platform_icon'] === 'true' && $total_platforms === 1) { ?>
                <img src="<?php echo esc_url($icon); ?>" alt="<?php echo $platform_name; ?>"/>
            <?php } ?>

            <!--multiple platforms-->
            <?php if($badge_settings['display_platform_icon'] === 'true' && $total_platforms > 1 && !empty(Arr::get($business_info, 'platforms', []))) { ?>
                    <div class="wpsr-business-info-paltforms">
                    <?php
                    $count = [];
                    foreach ($business_info['platforms'] as $index => $platform) {
                        $customLogo = Arr::get($platform, 'logo', '');
                        $platformName = Arr::get($platform, 'platform_name');
                        if(empty(Arr::get($platform, 'url')) || (isset($count[$platformName]) && $count[$platformName])) continue;
                        $count[$platformName] = 1;
                        $icon_small = Helper::platformIcon($platform['platform_name'], 'small');
                    ?>
                        <img src="<?php echo $customLogo ? esc_url($customLogo) : esc_url($icon_small); ?>" alt="<?php echo esc_attr($platform['platform_name']); ?>">
                    <?php } ?>
                    </div>
            <?php } ?>

            <?php if(!empty($badge_settings['custom_title']) && $badge_settings['template'] === 'badge1') { ?>
                <span class="wpsr-reviews-badge-title"> <?php echo $badge_settings['custom_title']; ?> </span>
            <?php } ?>
        </div>

        <div class="wpsr-rating-and-count">
            <?php if(!empty($badge_settings['custom_title']) && $badge_settings['template'] === 'badge2') { ?>
                <span class="wpsr-reviews-badge-title"> <?php echo $badge_settings['custom_title']; ?> </span>
            <?php } ?>

            <!-- show empty stars if no reviews -->
            <?php if($total_rating < 1){  ?>
            <div class="wpsr-total-rating">
                  <span class="wpsr-rating">
                    <?php echo Helper::generateRatingIcon(0, $templateId); ?>
                  </span>
            </div>
            <?php } ?>

            <?php if (Arr::get($business_info, 'average_rating')) { ?>
                <div class="wpsr-total-rating">
                    <?php echo number_format($business_info['average_rating'], 1) ; ?>
                    <span class="wpsr-rating">
                        <?php echo Helper::generateRatingIcon(number_format($business_info['average_rating'], 1), $templateId); ?>
                    </span>
                </div>
            <?php } ?>

            <div class="wpsr-total-reviews">
                <?php echo $custom_num_of_reviews_text; ?>
            </div>
        </div>
    </div>
<!--conditional tag(div/a)-->
<?php echo $endTag ?>
</div>