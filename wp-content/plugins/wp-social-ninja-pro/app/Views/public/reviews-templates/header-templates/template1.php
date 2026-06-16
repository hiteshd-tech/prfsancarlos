<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;

extract($business_info);
extract($template_meta);

// Get pre-calculated data from handler
$header_data = isset($header_data) ? $header_data : [];

$meta_platform = Arr::get($header_data, 'meta_platform', Arr::get($template_meta, 'platform', []));
$total_platforms = Arr::get($header_data, 'total_platforms', count($meta_platform));
$platform_name = Arr::get($header_data, 'platform_name', isset($platform_name) ? $platform_name : '');
$total_business = Arr::get($header_data, 'total_business', isset($total_business) ? $total_business : null);
$rating_text = Arr::get($header_data, 'rating_text', '');
$platform_name_class = Arr::get($header_data, 'platform_name_class', Helper::platformDynamicClassName($business_info));
$wrapperClass = Arr::get($header_data, 'wrapperClass', '');

echo '<div class="wpsr-row">';
echo '<div class="wpsr-business-info ' . $wrapperClass . ' ' . $platform_name_class . '">';
echo '<div class="wpsr-business-info-left">';

// Show platform icons for multiple businesses
if ((!empty($platforms) && is_array($platforms)) && $total_business > 1) {
    // Use handler method to render platform icons with business name
    if (isset($handler)) {
        echo $handler->renderPlatformIcons($platforms, $template_meta, $meta_platform, $display_header_business_logo, $display_header_business_name, $rating_text);
    }
}

// Show single platform icon
if ($total_business <= 1) {
    // Use handler method to render single platform icon with business name
    if (isset($handler)) {
        echo $handler->renderSinglePlatformIcon($platform_name, $template_meta, $display_header_business_logo, $display_header_business_name, $rating_text);
    }
}

// Render rating and count section
if (isset($handler)) {
    echo $handler->renderRatingAndCount($business_info, $template_meta, $isBooking, $templateId, $display_header_rating, $display_header_reviews, $custom_number_of_reviews_text);
}
echo '</div>';
do_action('wpsocialreviews/render_reviews_write_a_review_btn', $template_meta, $templateType, $business_info, $templateId, $translations);
echo '</div>';
echo '</div>';