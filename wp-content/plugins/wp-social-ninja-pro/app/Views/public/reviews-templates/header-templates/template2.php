<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;

extract($business_info);
extract($template_meta);

// Get pre-calculated data from handler
$header_data = isset($header_data) ? $header_data : [];

// Use Arr::get to safely retrieve rating breakdown from business_info (fallback to previously set $rating_breakdown or empty array)
$rating_breakdown = Arr::get($business_info, 'rating_breakdown', (isset($rating_breakdown) ? $rating_breakdown : []));

// Check if rating breakdown is already available in business_info
if (isset($business_info['rating_breakdown']) && !empty($business_info['rating_breakdown'])) {
    $rating_breakdown = $business_info['rating_breakdown'];
} else {
    // Fallback to handler calculation if not available in business_info
    $rating_breakdown = isset($rating_breakdown) ? $rating_breakdown : [];
}

$meta_platform = Arr::get($header_data, 'meta_platform', Arr::get($template_meta, 'platform', []));
$total_platforms = Arr::get($header_data, 'total_platforms', count($meta_platform));
$platform_name = Arr::get($header_data, 'platform_name', isset($platform_name) ? $platform_name : '');
$total_business = Arr::get($header_data, 'total_business', isset($total_business) ? $total_business : null);
$rating_text = Arr::get($header_data, 'rating_text', '');
$platform_name_class = Arr::get($header_data, 'platform_name_class', Helper::platformDynamicClassName($business_info));
$wrapperClass = Arr::get($header_data, 'wrapperClass', '');
$shouldShowRatingBreakdown = Arr::get($header_data, 'shouldShowRatingBreakdown', false);
$isBookingComOnly = Arr::get($header_data, 'isBookingComOnly', false);

echo '<div class="wpsr-business-info-wrapper wpsr-template-2-wrapper">';
echo '<div class="wpsr-business-info wpsr-template-2 ' . $wrapperClass . ' ' . $platform_name_class . '">';

// Show title on top
if ($display_header_business_name && !empty($meta_platform) && !empty($average_rating)) {
    echo '<span class="wpsr-business-title wpsr-reviews-header-template-2-title">' . esc_html($rating_text) . '</span>';
}

echo '<div class="wpsr-reviews-header-template-2-three-columns">';

// Column 1: Overall Rating and Review Count
echo '<div class="wpsr-reviews-header-template-2-column-1">';
echo '<div class="wpsr-rating-and-count wpsr-reviews-header-template-2-overall-rating-wrapper">';

// Show platform icons for multiple businesses
if ((!empty($platforms) && is_array($platforms)) && $total_business > 1) {
    // Use handler method to render platform icons
    if (isset($handler)) {
        echo $handler->renderPlatformIcons($platforms, $template_meta, $meta_platform, $display_header_business_logo);
    }
}

// Show single platform icon
if ($total_business <= 1) {
    // Use handler method to render single platform icon
    if (isset($handler)) {
        echo $handler->renderSinglePlatformIcon($platform_name, $template_meta, $display_header_business_logo);
    }
}

// // Show title
// if ($display_header_business_name) {
//     echo '<div class="wpsr-reviews-header-template-2-title">' . esc_html($rating_text) . '</div>';
// }

// Show overall rating
echo '<div class="wpsr-reviews-header-template-2-overall-rating">';
if (isset($average_rating) && !empty($average_rating) && $display_header_rating === true) {
    if (!($isBooking)) {
        echo '<div class="wpsr-rating wpsr-reviews-header-template-2-stars">' . Helper::generateRatingIcon(number_format($average_rating, 1), $templateId) . '</div>';
    }
    echo '<span class="wpsr-total-rating wpsr-reviews-header-template-2-total-rating">' . number_format($average_rating, 1) . ' out of 5</span>';
}
echo '</div>';

// Show review count  
if (isset($total_rating) && !empty($total_rating) && $display_header_reviews === true && strlen($custom_number_of_reviews_text)) {
    echo '<div class="wpsr-total-reviews wpsr-reviews-header-template-2-review-count">' .
        str_replace('{total_reviews}', '<span>' . number_format($total_rating, 0) . '</span>', $custom_number_of_reviews_text)
        . '</div>';
}

echo '</div>';
echo '</div>';

// Column 2: Star Rating Breakdown
if ($shouldShowRatingBreakdown) {
    echo '<div class="wpsr-reviews-header-template-2-column-2">';
} else {
    echo '<div class="wpsr-reviews-header-template-2-column-2" style="display: none;">';
}

// Only show breakdown if we have reviews and rating data
if ($shouldShowRatingBreakdown && !empty($rating_breakdown)) {
    echo '<div class="wpsr-reviews-header-template-2-star-breakdown">';

    foreach ($rating_breakdown as $item) {
        echo '<div class="wpsr-reviews-header-template-2-star-row">';
        echo '<div class="wpsr-reviews-header-template-2-star-label">';

        if (Arr::get($item, 'type') === 'booking_range') {
            // Booking.com point ranges
            echo '<span class="wpsr-reviews-header-template-2-point-range">' . esc_html(Arr::get($item, 'label', '')) . '</span>';
        } else {
            // Traditional star ratings
            echo '<div class="wpsr-reviews-header-template-2-star-icons">';
            echo '<span class="wpsr-rating">' . Helper::generateRatingIcon(Arr::get($item, 'star'), $templateId) . '</span>';
            echo '</div>';
        }

        echo '</div>';
        echo '<div class="wpsr-reviews-header-template-2-progress-bar">';
        echo '<div class="wpsr-reviews-header-template-2-progress-fill" style="width: ' . intval(Arr::get($item, 'percentage', 0)) . '%"></div>';
        echo '</div>';
        echo '<div class="wpsr-reviews-header-template-2-star-count">' . intval(Arr::get($item, 'count', 0)) . '</div>';
        echo '</div>';
    }

    echo '</div>';
}

echo '</div>';

// Column 3: Write Review Button
echo '<div class="wpsr-reviews-header-template-2-column-3">';
do_action('wpsocialreviews/render_reviews_write_a_review_btn', $template_meta, $templateType, $business_info, $templateId, $translations);
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';