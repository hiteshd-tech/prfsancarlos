<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Models\Review;

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerceHelper
{

    /**
     * Get effective settings for a product (product-specific or global fallback)
     *
     * @param int $product_id
     * @return array
     */
    public static function getEffectiveSettings($product_id, $settings_key = 'wpsr-settings-woo')
    {
        $settings = get_post_meta($product_id, $settings_key, true);
        $globalSettingsRaw = get_option('wpsr_woocommerce_global_settings', []);

        // Extract global settings from nested structure
        $globalSettings = Arr::get($globalSettingsRaw, 'global_settings', [
            'use_social_ninja_primary' => 'no',
            'hide_reviews_count' => 'no',
            'hide_reviews_title' => 'yes',
            'hide_product_list_rating_count' => 'yes',
            'reviews_widgets_placement' => 'display_inside_reviews_tab',
            'reviews_form' => 'woocommerce',
            'selected_template' => '0',
        ]);

        // Check if we should use global settings
        $useGlobal = Arr::get($globalSettings, 'use_social_ninja_primary', 'no') === 'yes' && (empty($settings) || Arr::get($settings, 'selected_template', '0') === '0');

        if ($useGlobal) {
            // Return global settings
            return [
                'use_social_ninja_primary' =>  Arr::get($globalSettings, 'use_social_ninja_primary', 'no'),
                'hide_reviews_count' => Arr::get($globalSettings, 'hide_reviews_count'),
                'hide_product_list_rating_count' => Arr::get($globalSettings, 'hide_product_list_rating_count'),
                'hide_reviews_title' => Arr::get($globalSettings, 'hide_reviews_title'),
                'reviews_widgets_placement' => Arr::get($globalSettings, 'reviews_widgets_placement', 'display_inside_reviews_tab'),
                'reviews_form' => Arr::get($globalSettings, 'reviews_form', 'woocommerce'),
                'platform' => 'reviews',
                'selected_template' => Arr::get($globalSettings, 'selected_template'),
                'isGlobalTemplateGettingPrecedence' => true,
            ];
        } else {
            // Return product-specific settings with global fallbacks
            return [
                'use_social_ninja_primary' => 'no',
                'hide_reviews_count' => Arr::get($settings, 'hide_reviews_count', 'no'),
                'hide_product_list_rating_count' => Arr::get($globalSettings, 'hide_product_list_rating_count'),
                'hide_reviews_title' => Arr::get($settings, 'hide_reviews_title', 'no'),
                'reviews_widgets_placement' => Arr::get($settings, 'reviews_widgets_placement', 'display_inside_reviews_tab'),
                'reviews_form' => Arr::get($settings, 'reviews_form', 'woocommerce'),
                'platform' => 'reviews',
                'selected_template' => Arr::get($settings, 'selected_template'),
                'isGlobalTemplateGettingPrecedence' => false,
            ];
        }
    }

    /**
     * Check if current context is a WooCommerce product
     *
     * @return array Context information with is_product flag and product_id
     */
    public static function isWooCommerceProductContext()
    {
        global $post;

        $is_product = defined('WC_PLUGIN_FILE') && function_exists('is_product') && is_product();

        $product_id = ($is_product && isset($post->ID)) ? $post->ID : 0;

        return [
            'is_product' => $is_product,
            'product_id' => $product_id
        ];
    }

    /**
     * Get relevant reviews for WooCommerce products
     *
     * @param array $template_meta Template metadata
     * @return array|null Modified template meta or null if not applicable
     */
    public static function getRelevantReviewsForProduct($template_meta)
    {
        $wooContext = static::isWooCommerceProductContext();

        if ($wooContext['is_product']) {
            $product_id = $wooContext['product_id'];
            $effectiveSettings = static::getEffectiveSettings($product_id);
            if ($effectiveSettings['isGlobalTemplateGettingPrecedence']) {
                $template_meta['platform'] = Arr::get($template_meta, 'platform', ['woocommerce']);
                $template_meta['selectedBusinesses'][] = $product_id;
                return $template_meta;
            }
        }

        return null;
    }

    /**
     * Get relevant business info for WooCommerce products
     *
     * @return array|null Business info or null if not applicable
     */
    public static function getRelevantBusinessInfoForProduct()
    {
        $wooContext = static::isWooCommerceProductContext();

        if ($wooContext['is_product']) {
            $product_id = $wooContext['product_id'];
            $effectiveSettings = static::getEffectiveSettings($product_id);

            if ($effectiveSettings['isGlobalTemplateGettingPrecedence']) {
                return [
                    'product_id' => $product_id,
                    'use_product_info' => true
                ];
            }
        }

        return null;
    }

    /**
     * Build a single review array from a WP_Comment object
     */
    public static function buildReviewFromComment($comment, $product_id)
    {
        if (!$comment || $comment->comment_type !== 'review') {
            return null;
        }
        $comment_id = (int) $comment->comment_ID;
        $comment_rating = (int) get_comment_meta($comment_id, 'rating', true);
        return [
            'review_id' => $comment->comment_ID,
            'reviewer_name' => $comment->comment_author,
            'review_date' => strtotime($comment->comment_date),
            'review_text' => $comment->comment_content,
            'review_rating' => $comment_rating,
            'reviewer_email' => $comment->comment_author_email,
            'fields' => [
                'product_name' => get_the_title($product_id),
                'product_thumbnail' => wp_get_attachment_image_src(get_post_thumbnail_id($product_id), [100, 100], false)
            ]
        ];
    }

    /**
     * Fetch product information and its approved reviews (lightweight array form)
     */
    public static function fetchProductData($productId)
    {
        $product = wc_get_product($productId);
        if (!$product) {
            return [];
        }
        $data = [];
        $data['product_name'] = $product->get_name();
        $data['average_rating'] = (float) $product->get_average_rating();
        $data['total_rating'] = (int) $product->get_rating_count();

        $reviews = [];
        foreach (get_comments(array( 'post_id' => $productId ) ) as $comment) {
            $review = static::buildReviewFromComment($comment, $productId);
            if ($review) {
                $reviews[] = $review;
            }
        }
        if ($reviews) {
            $data['reviews'] = $reviews;
            $data['total_rating'] = count($reviews); // maintain previous override behavior
        }
        return $data;
    }


    /**
     * During the judge.me import process, product data should be retrieved from the SR reviews table
     * instead of WooCommerce comments. This ensures that the average rating and total rating
     * are calculated correctly from the SR reviews data. Otherwise, the connection will not
     * display star ratings on the frontend.
     */
    
    public static function fetchProductDataFromWPSR($productId){

        $product = wc_get_product($productId);
        if (!$product) {
            return [];
        }

        $reviewsData = Review::where('platform_name', 'woocommerce')
            ->where('source_id', $productId)
            ->get();

        $data = [];
        $data['product_name'] = $product->get_name();
        $data['average_rating'] = array_sum($reviewsData->pluck('rating')->toArray()) / max(1, $reviewsData->count());
        $data['total_rating'] = $reviewsData->count();


        $reviews = [];

        foreach ($reviewsData as $reviewRecord) {
            $review = [
                'review_id' => $reviewRecord->review_id,
                'reviewer_name' => $reviewRecord->reviewer_name,
                'review_date' => strtotime($reviewRecord->review_time),
                'review_text' => $reviewRecord->reviewer_text,
                'review_rating' => $reviewRecord->rating,
                'reviewer_email' => $reviewRecord->reviewer_id,
                'fields' => [
                    'product_name' => get_the_title($productId),
                    'product_thumbnail' => wp_get_attachment_image_src(get_post_thumbnail_id($productId), [100, 100], false)
                ]
            ];
            $reviews[] = $review;
        }

        if ($reviews) {
            $data['reviews'] = $reviews;
            $data['total_rating'] = count($reviews); // maintain previous override behavior
        }
        return $data;
    }

    /**
     * Count total approved WooCommerce product reviews
     */
    /**
     * Count total approved WooCommerce product reviews
     */
    public static function getWooReviewsCount()
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(c.comment_ID) 
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            WHERE c.comment_type = %s 
            AND c.comment_approved = %s
            AND p.post_type = %s",
            'review',
            '1',
            'product'
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Fetch batch of approved WooCommerce product reviews
     */
    /**
     * Fetch batch of approved WooCommerce product reviews
     */
    public static function getWooReviewsBatch($offset, $limit)
    {
        global $wpdb;

        // Validate and sanitize inputs
        $offset = max(0, (int) $offset);
        $limit = max(1, min(1000, (int) $limit)); // Cap at 1000 for performance

        $query = $wpdb->prepare(
            "SELECT c.comment_ID, c.comment_post_ID, c.comment_author, c.comment_author_email, 
                    c.comment_date, c.comment_content, c.comment_approved, 
                    cm.meta_value as rating 
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s
            INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID AND p.post_type = %s
            WHERE c.comment_type = %s 
            AND c.comment_approved = %s
            ORDER BY c.comment_ID ASC
            LIMIT %d OFFSET %d",
            'rating',
            'product',
            'review',
            '1',
            $limit,
            $offset
        );

        return $wpdb->get_results($query);
    }

    public static function isProductConnectedToWPSN($platform)
    {
        global $post;
        if (!$post || !isset($post->ID)) {
            return false;
        }

        $businessInfo = static::getAllBusinessInfo($platform);
        
        if (!is_array($businessInfo)) {
            return false;
        }
        
        return array_key_exists($post->ID, $businessInfo);
    }

    /**
     * Get cached business info for all products
     */
    public static function getAllBusinessInfo($platform)
    {
        if (!$platform || !method_exists($platform, 'getBusinessInfo')) {
            return [];
        }
        
        $businessInfo = $platform->getBusinessInfo();
        
        return is_array($businessInfo) ? $businessInfo : [];
    }

    /**
     * Format review data for bulk insert
     */
    public static function formatReviewForInsert($review)
    {
        $reviewData = [
            'platform_name' => 'woocommerce',
            'source_id' => $review->comment_post_ID,
            'reviewer_name' => $review->comment_author,
            'reviewer_id' => $review->user_id ?: $review->comment_author_email,
            'reviewer_url' => $review->comment_author_url,
            'reviewer_img' => get_avatar_url($review->comment_author_email),
            'reviewer_text' => $review->comment_content,
            'rating' => get_comment_meta($review->comment_ID, 'rating', true) ?: 5,
            'review_time' => $review->comment_date,
            'review_approved' => $review->comment_approved,
            'updated_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'review_id' => $review->comment_ID
        ];

        return $reviewData;
    }
}