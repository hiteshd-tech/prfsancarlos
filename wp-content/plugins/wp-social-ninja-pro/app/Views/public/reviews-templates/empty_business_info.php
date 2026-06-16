<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;

// Get WooCommerce context data passed from the handler
$is_woocommerce_context = isset($woo_context['is_woocommerce_context']) ? $woo_context['is_woocommerce_context'] : false;
$wrapper_classes = isset($woo_context['wrapper_classes']) ? $woo_context['wrapper_classes'] : 'wpsr-business-info-wrapper';

$templateType = Arr::get($template_meta, 'templateType', '');
// set empty platform to break condition in write-a-review-btn.php
$business_info['platforms'] = ['platform_name' => ''];

echo '<div class="wpsr-empty-business-info '.$wrapper_classes.'">';
echo '<div class="wpsr-business-info">';

echo '<div class="wpsr-business-info-left">';
echo '<div class="wpsr-rating-and-count"><span class="wpsr-rating">';
echo Helper::generateRatingIcon(0, $templateId);
echo '</span></div>';
echo '<div class="wpsr-no-reviews-message">'.__('Be the first to write a review.', 'wp-social-ninja-pro').'</div>';
echo '</div>';
do_action('wpsocialreviews/render_reviews_write_a_review_btn', $template_meta, $templateType, $business_info, $templateId, $translations);
echo '</div>';
echo '</div>';