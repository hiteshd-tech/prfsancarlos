<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper as ReviewsHelper;
use WPSocialReviews\App\Services\Widgets\Helper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Models\Review;

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce extends BaseReview
{
    private $productId = null;
    public function __construct()
    {
        parent::__construct(
            'woocommerce',
            'wpsr_reviews_woocommerce_settings',
            'wpsr_woocommerce_reviews_update'
        );
        if (class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')) {
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }
    }

    public function pushValidPlatform($platforms)
    {
        // Check if WooCommerce is installed and active
        if (!class_exists('WooCommerce')) {
            return $platforms;
        }

        $settings = $this->getApiSettings();

        // Check if WooCommerce API settings exist or if WooCommerce is available
        $hasApiSettings = (!isset($settings['data']) && sizeof($settings) > 0);
        $hasWooCommerce = $this->hasWooCommerceReviews();

        // Add WooCommerce platform if WooCommerce is available
        if ($hasApiSettings || $hasWooCommerce) {
            $platforms['woocommerce'] = __('WooCommerce', 'wp-social-ninja-pro');
        }

        return $platforms;
    }

    public function getAdvanceSettings()
    {
        $settings = get_option('wpsr_' . $this->platform . '_global_settings', []);
        if (!$settings) {
            $settings = array(
                'global_settings' => array(
                    'auto_syncing' => 'false',
                    'expiration' => 86400,
                )
            );
        }

        $platforms = ['twitter', 'youtube', 'instagram', 'tiktok', 'facebook_feed'];
        $allTemplates = Helper::getTemplates($platforms);

        wp_send_json_success([
            'settings' => $settings,
            'templates' => $allTemplates
        ], 200);
    }

    /**
     * Check if WooCommerce reviews exist in the database
     * 
     * @return bool True if WooCommerce reviews exist
     */
    public function hasWooCommerceReviews()
    {
        return Review::where('platform_name', 'woocommerce')->count() > 0;
    }

    public function connectProductToWPSR($productId, $fromImport = false)
    {
        $this->productId = $productId;

        try {
            $productData = WooCommerceHelper::fetchProductDataFromWPSR($productId);

            // Remove the temporary total_fetched_reviews field from business info
            if (isset($productData['total_fetched_reviews'])) {
                unset($productData['total_fetched_reviews']);
            }

            $business_info = $this->saveBusinessInfo($productData);
            update_option('wpsr_reviews_' . $this->platform . '_business_info', $business_info, false);

            return $business_info;

        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

    }

    public function handleCredentialSave($settings = array())
    {
        $sourceId = Arr::get($settings, 'source_id', '');

        if (empty($sourceId)) {
            wp_send_json_error([
                'message' => __('Please provide a valid product id!!', 'wp-social-ninja-pro')
            ], 400);
            return;
        }

        try {
            $businessInfo = $this->verifyCredential($sourceId);
            $message = ReviewsHelper::getNotificationMessage($businessInfo, $this->productId);

            // Remove the temporary total_fetched_reviews field from business info
            if (isset($businessInfo['total_fetched_reviews'])) {
                unset($businessInfo['total_fetched_reviews']);
            }

            $updated = update_option('wpsr_reviews_' . $this->platform . '_business_info', $businessInfo, false);

            wp_send_json_success([
                'message' => $message,
                'business_info' => $businessInfo
            ], 200);
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 423);
        }
    }

    /**
     * @throws \Exception
     */
    public function verifyCredential($sourceId)
    {
        if (empty($sourceId)) {
            throw new \Exception(__('Please provide a valid product id!!', 'wp-social-ninja-pro'));
        }

        $this->productId = $sourceId;
        $products_data = WooCommerceHelper::fetchProductData($sourceId);

        $products_reviews = Arr::get($products_data, 'reviews', []);
        $total_reviews = !empty($products_reviews) && is_array($products_reviews) ? count($products_reviews) : 0;

        $products_data['total_rating'] = $total_reviews;
        $business_info = $this->saveBusinessInfo($products_data);

        if ($products_reviews && $total_reviews > 0) {
            $this->syncRemoteReviews($products_reviews);
        }

        $this->saveApiSettings([
            'api_key' => '93af975a-f01f-4de8-9507-432c74255d38',
            'place_id' => $sourceId,
            'url_value' => '',
        ]);

        $reviewObject = new \stdClass();
        $reviewObject->platform_name = $this->platform;
        $reviewObject->source_id = $sourceId;
        do_action('wpsocialreviews/custom_review_updated', $reviewObject);

        $business_info['total_fetched_reviews'] = $total_reviews;
        return $business_info;
    }

    public function formatData($review, $index)
    {
        $dateTime = Arr::get($review, 'review_date');
        $reviewerEmail = Arr::get($review, 'reviewer_email');
        $reviewDate = date('Y-m-d H:i:', $dateTime);

        return [
            'platform_name' => $this->platform,
            'source_id' => $this->productId,
            'review_id' => Arr::get($review, 'review_id'),
            'reviewer_name' => Arr::get($review, 'reviewer_name'),
            'review_title' => $this->platform . '_' . ($index + 1),
            'reviewer_url' => '',
            'reviewer_img' => $reviewerEmail ? get_avatar_url($reviewerEmail) : '',
            'reviewer_text' => Arr::get($review, 'review_text', ''),
            'rating' => (int) Arr::get($review, 'review_rating'),
            'review_time' => $reviewDate,
            'fields' => json_encode(Arr::get($review, 'fields')),
            'review_approved' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    public function getAllProducts()
    {
        $args = array(
            'post_type' => 'product',
            'numberposts' => -1,
            'post_status' => 'publish'
        );

        return get_posts($args);
    }

    public function getAdditionalInfo()
    {
        return $this->getAllProducts();
    }

    public function saveBusinessInfo($reviewData)
    {
        $businessInfo = [];
        $infos = $this->getBusinessInfo();

        $businessInfo['place_id'] = $this->productId;
        $businessInfo['name'] = Arr::get($reviewData, 'product_name', '');
        $businessInfo['url'] = get_the_permalink($this->productId) . '#reviews';
        $businessInfo['address'] = '';
        $businessInfo['average_rating'] = Arr::get($reviewData, 'average_rating');
        $businessInfo['total_rating'] = Arr::get($reviewData, 'total_rating');
        $businessInfo['phone'] = '';
        $businessInfo['platform_name'] = $this->platform;
        $infos[$this->productId] = $businessInfo;

        return $infos;
    }

    public function getBusinessInfo($data = array())
    {
        return get_option('wpsr_reviews_' . $this->platform . '_business_info', []);
    }

    public function saveApiSettings($settings)
    {
        $apiKey = $settings['api_key'];
        $placeId = $settings['place_id'];
        $businessUrl = $settings['url_value'];

        $apiSettings = $this->getApiSettings();

        if (isset($apiSettings['data']) && !$apiSettings['data']) {
            $apiSettings = [];
        }

        if ($apiKey && $placeId) {
            $apiSettings[$placeId]['api_key'] = $apiKey;
            $apiSettings[$placeId]['place_id'] = $placeId;
            $apiSettings[$placeId]['url_value'] = $businessUrl;
        }
        return update_option($this->optionKey, $apiSettings, false);
    }

    public function getApiSettings()
    {
        $settings = get_option($this->optionKey);
        if (!$settings) {
            $settings = [
                'api_key' => '',
                'place_id' => '',
                'url_value' => '',
                'data' => false,
            ];
        }

        return $settings;
    }

    public function manuallySyncReviews($credentials)
    {
        $settings = get_option($this->optionKey);

        if (!empty($settings) && is_array($settings)) {
            $sourceId = Arr::get($credentials, 'place_id', '');
            if ($sourceId) {
                try {
                    $this->verifyCredential($sourceId);
                } catch (\Exception $exception) {
                    error_log($exception->getMessage());
                }

                wp_send_json_success([
                    'message' => __('Reviews synced successfully!', 'wp-social-ninja-pro')
                ]);
            }
        }
    }

    public function doCronEvent()
    {
        $expiredCaches = $this->cacheHandler->getExpiredCaches();
        if (!$expiredCaches) {
            return false;
        }

        $settings = get_option($this->optionKey);
        if (!empty($settings) && is_array($settings)) {
            foreach ($settings as $setting) {
                $sourceId = Arr::get($setting, 'place_id', '');
                if (in_array($sourceId, $expiredCaches)) {
                    if ($sourceId) {
                        try {
                            $this->verifyCredential($sourceId);
                        } catch (\Exception $exception) {
                            error_log($exception->getMessage());
                        }
                    }

                    $this->cacheHandler->createCache('wpsr_reviews_' . $this->platform . '_business_info_' . $sourceId, $sourceId);
                }
            }
        }
    }

    public function clearVerificationConfigs($userId)
    {

    }
}