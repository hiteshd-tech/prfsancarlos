<?php
if (! defined('ABSPATH')) exit;

use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;

// Get WooCommerce context data passed from the handler
$is_woocommerce_context = isset($woo_context['is_woocommerce_context']) ? $woo_context['is_woocommerce_context'] : false;
$woo_settings = isset($woo_context['woo_settings']) ? $woo_context['woo_settings'] : [];
$reviews_form = isset($woo_context['reviews_form']) ? $woo_context['reviews_form'] : 'social_ninja';
$wrapper_classes = isset($woo_context['wrapper_classes']) ? $woo_context['wrapper_classes'] : 'wpsr-business-info-wrapper';

// Handle war_btn_source for WooCommerce reviews form if needed
if ($is_woocommerce_context && $reviews_form === 'woocommerce') {
    // Set war_btn_source for WooCommerce reviews form
    // because if the SN is set to fluentforms it will print the FF popoup which is unnecessary
    // and 'write a review' button works using the JS click event.
    $war_btn_source = Arr::set($template_meta, 'war_btn_source', 'custom_url');
}

// Determine which header template to use
$header_template = isset($template_meta['header_template']) ? $template_meta['header_template'] : 'template1';

// Ensure the header template value is valid
$valid_templates = ['template1', 'template2'];
if (!in_array(strtolower($header_template), $valid_templates)) {
    $header_template = 'template1'; // Default fallback
} else {
    $header_template = strtolower($header_template);
}

// Include the appropriate header template
$template_file = __DIR__ . '/header-templates/' . $header_template . '.php';

// Add wrapper around template inclusion
echo '<div class="' . $wrapper_classes . '">';

if (file_exists($template_file)) {
    include $template_file;
} else {
    // Fallback to template1 if the requested template doesn't exist
    include __DIR__ . '/header-templates/template1.php';
}

echo '</div>';