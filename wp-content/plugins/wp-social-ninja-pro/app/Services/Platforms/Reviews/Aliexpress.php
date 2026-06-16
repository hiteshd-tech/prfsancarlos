<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Libs\SimpleDom\Helper;
use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper as ReviewsHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Aliexpress Reviews
 * @since 1.0.0
 */
class Aliexpress extends BaseReview
{
    private $remoteBaseUrl = 'https://aliexpress.com/';
    private $placeId = null;

    public function __construct()
    {
        parent::__construct(
            'aliexpress',
            'wpsr_reviews_aliexpress_settings',
            'wpsr_aliexpress_reviews_update'
        );
        if(class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')){
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }
    }

    public function handleCredentialSave($credentials)
    {
        $placeId = Arr::get($credentials, 'source_id');
        $businessName = Arr::get($credentials, 'product_name');
        $credData = [
            'place_id' => $placeId,
            'businessName' => $businessName,
        ];

        try {
            $businessInfo = $this->verifyCredential($credData);
            $message = ReviewsHelper::getNotificationMessage($businessInfo, $this->placeId);
            if (Arr::get($businessInfo, 'total_fetched_reviews') && Arr::get($businessInfo, 'total_fetched_reviews') > 0) {
                unset($businessInfo['total_fetched_reviews']);

                // save caches when auto sync is on
                $apiSettings = get_option('wpsr_aliexpress_global_settings');
                if(Arr::get($apiSettings, 'global_settings.auto_syncing') === 'true'){
                    $this->saveCache();
                }
            }

            wp_send_json_success([
                'message'       => $message,
                'business_info' => $businessInfo
            ], 200);
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 423);
        }
    }

    public function verifyCredential($credData)
    {
        $placeId = Arr::get($credData, 'place_id');
        $businessName = Arr::get($credData, 'businessName');

        if (empty($placeId) || empty($businessName)) {
            throw new \Exception(__('Field should not be empty!', 'wp-social-ninja-pro'));
        }

        ini_set('memory_limit', '600M');

        $businessUrl = 'https://feedback.aliexpress.com/pc/searchEvaluation.do?productId=' . $placeId;
        $this->remoteBaseUrl = 'https://www.aliexpress.com/item/'. $placeId .'.html';
        $this->placeId     = $placeId;

        $request = wp_remote_get($businessUrl);
        if (is_wp_error($request)) {
            $message = $request->get_error_message();
            return new \WP_Error(423, $message);
        }

        $body = json_decode(wp_remote_retrieve_body($request), true);

        if (!empty($body['error'])) {
            $error = 'Unknown Error';
            if (isset($body['error_description'])) {
                $error = $body['error_description'];
            } elseif (!empty($body['error']['message'])) {
                $error = $body['error']['message'];
            }

            return new \WP_Error(423, $error);
        }

        $reviews = Arr::get($body, 'data.evaViewList', []);
        $businessDetails = [
            'business_name'  => $businessName,
            'total_reviews'  => Arr::get($body, 'data.productEvaluationStatistic.totalNum'),
            'average_rating' => Arr::get($body, 'data.productEvaluationStatistic.evarageStar'),
            'review_url'     => $this->remoteBaseUrl
        ];

        if (!empty($reviews)) {
            $this->saveApiSettings([
                'api_key' => '703669e4-4907-4b21-90b5-f1b59354baf2',
                'place_id' => $this->placeId,
                'url_value' => $this->remoteBaseUrl
            ]);

            $this->syncRemoteReviews($reviews);
            $businessInfo = $this->saveBusinessInfo($businessDetails);

            $totalFetchedReviews = count($reviews);
            if ($totalFetchedReviews > 0) {
                update_option('wpsr_reviews_aliexpress_business_info', $businessInfo, 'no');
            }

            $businessInfo['total_fetched_reviews'] = $totalFetchedReviews;
            
            return $businessInfo;
        } else {
            throw new \Exception(__('AliExpress reviews not found!', 'wp-social-ninja-pro'));
        }
    }

    public function pushValidPlatform($platforms)
    {
        $settings    = $this->getApiSettings();
        if (!isset($settings['data']) && sizeof($settings) > 0) {
            $platforms['aliexpress'] = __('AliExpress', 'wp-social-ninja-pro');
        }
        return $platforms;
    }

    public function formatData($review, $index)
    {
        $reviewDate = Arr::get($review, 'evalDate');
        $reviewer_text = Arr::get($review, 'buyerFeedback', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
           $reviewer_text = GlobalHelper::sanitizeForStorage($reviewer_text);
        }

        return [
            'platform_name' => $this->platform,
            'source_id'     => $this->placeId,
            'review_id'     => Arr::get($review, 'evaluationIdStr'),
            'reviewer_name' => Arr::get($review, 'buyerName'),
            'review_title'  => '',
            'reviewer_url'  => $this->remoteBaseUrl,
            'reviewer_img'  => Arr::get($review, 'buyerHeadPortrait'),
            'reviewer_text' => $reviewer_text,
            'rating'        => ((int)Arr::get($review, 'buyerEval') / 20),
            'review_time'   => date('Y-m-d H:i:s', strtotime($reviewDate)),
            'review_approved' => 1,
            'updated_at'    => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s')
        ];
    }

    public function saveBusinessInfo($data = array())
    {
        $businessInfo = [];
        $infos        = $this->getBusinessInfo();
        $infos = empty($infos) ? [] : $infos;
        if ($data && is_array($data) && !empty($this->placeId) && !empty(Arr::get($data, 'business_name', ''))) {
            $placeId                          = $this->placeId;
            $businessInfo['place_id']         = $placeId;
            $businessInfo['name']             = Arr::get($data, 'business_name');
            $businessInfo['url']              = Arr::get($data, 'review_url');
            $businessInfo['address']          = '';
            $businessInfo['average_rating']   = Arr::get($data, 'average_rating');
            $businessInfo['total_rating']     = Arr::get($data, 'total_reviews');
            $businessInfo['phone']            = '';
            $businessInfo['platform_name']    = $this->platform;
            $businessInfo['status']           = true;
            $infos[$placeId]                  =  $businessInfo;
        }
        return $infos;
    }

    public function getBusinessInfo()
    {
        return get_option('wpsr_reviews_aliexpress_business_info');
    }

    public function saveApiSettings($settings)
    {
        $apiKey       = $settings['api_key'];
        $placeId      = $settings['place_id'];
        $businessUrl  = $settings['url_value'];
        $apiSettings  = $this->getApiSettings();

        if(isset($apiSettings['data']) && !$apiSettings['data']) {
            $apiSettings = [];
        }

        if($apiKey && $placeId && $businessUrl){
            $apiSettings[$placeId]['api_key'] = $apiKey;
            $apiSettings[$placeId]['place_id'] = $placeId;
            $apiSettings[$placeId]['url_value'] = $businessUrl;
        }
        return update_option($this->optionKey, $apiSettings, 'no');
    }

    public function getApiSettings()
    {
        $settings = get_option($this->optionKey);
        if (!$settings) {
            $settings = [
                'api_key'   => '',
                'place_id'  => '',
                'url_value' => '',
                'data'      => false
            ];
        }
        return $settings;
    }

    public function getAdditionalInfo()
    {
        return [];
    }

    public function clearVerificationConfigs($userId)
    {
        
    }
}