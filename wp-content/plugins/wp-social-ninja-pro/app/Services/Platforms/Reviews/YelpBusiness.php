<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Yelp Reviews Place Id and Api Key
 * @since 1.0.0
 */
class YelpBusiness extends BaseReview
{
    private $remoteBaseUrl = 'https://api.yelp.com/v3/businesses/';
    private $sourceId = null;

    public function __construct()
    {
        parent::__construct(
            'yelp',
            'wpsr_reviews_yelp_settings',
            'wpsr_yelp_reviews_update'
        );
        if(class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')){
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }
    }

    public function handleCredentialSave($settings = array())
    {
        $apiKey  = $settings['api_key'];
        $placeId = $settings['source_id'];
        try {
            $businessInfo = $this->verifyCredential($apiKey, $placeId);

            // save caches when auto sync is on
            $apiSettings = get_option('wpsr_yelp_global_settings');
            if(Arr::get($apiSettings, 'global_settings.auto_syncing') === 'true'){
                $this->saveCache();
            }
            wp_send_json_success([
                'message'       => __('Yelp Business Reviews Successfully Saved', 'wp-social-ninja-pro'),
                'business_info' => $businessInfo
            ], 200);

        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 423);
        }
    }

    public function pushValidPlatform($platforms)
    {
        $settings    = $this->getApiSettings();
        if (!isset($settings['data']) && sizeof($settings) > 0) {
            $platforms['yelp'] = __('Yelp', 'wp-social-ninja-pro');
        }
        return $platforms;
    }

    public function verifyCredential($apiKey, $placeId)
    {
        $data = $this->fetchRemoteReviews($apiKey, $placeId);

        if (empty(Arr::get($data, 'reviews'))) {
	        throw new \Exception(
		        __('We can\'t fetch any reviews from this business name, Please check your place id is correct or not.', 'wp-social-ninja-pro')
	        );
        }

        $businessInfo = $this->getRemoteBusinessInfo($apiKey, $placeId);

        if(count($data['reviews']) > 0) {
            $this->saveApiSettings([
                'api_key' => $apiKey,
                'place_id' => $placeId
            ]);

            $this->syncRemoteReviews($data['reviews'], $placeId);

            update_option('wpsr_reviews_yelp_business_info', $businessInfo, 'no');
        }

        return $businessInfo;
    }

    public function fetchRemoteReviews($apiKey, $placeId)
    {
        $fetchReviewsUrl = add_query_arg([
            'locale' => 'en_US',
//            'sort_by' => 'date_desc'
        ], $this->remoteBaseUrl . $placeId . '/reviews');
        $args            = array(
            'user-agent' => '',
            'headers'    => array(
                'authorization' => 'Bearer ' . $apiKey,
            ),
        );

        $response        = wp_remote_get($fetchReviewsUrl, $args);

        if(is_wp_error($response)) {
	        throw new \Exception(
		        $response->get_error_message()
	        );
        }

        if ('OK' !== wp_remote_retrieve_response_message($response) || 200 !== wp_remote_retrieve_response_code($response)) {
	        throw new \Exception(
                Arr::get($response, 'response.message')
	        );
        }

        $responseBody = wp_remote_retrieve_body($response);
        $result       = json_decode($responseBody, true);

        return $result;
    }

    public function formatData($review, $index)
    {
        $review_text = Arr::get($review, 'text', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }
        return [
            'platform_name' => $this->platform,
            'source_id'     => $this->sourceId,
            'review_id'     => Arr::get($review, 'id', ''),
            'reviewer_name' => Arr::get($review, 'user.name'),
            'review_title'  => $this->platform . '_' . ($index + 1),
            'reviewer_url'  => Arr::get($review, 'user.profile_url'),
            'reviewer_img'  => Arr::get($review, 'user.image_url'),
            'reviewer_text' => $review_text,
            'rating'        => intval(Arr::get($review, 'rating')),
            'review_time'   => Arr::get($review, 'time_created'),
            'review_approved' => 1,
            'updated_at'    => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s')
        ];
    }

    public function getRemoteBusinessInfo($apiKey, $placeId)
    {
        $args            = array(
            'user-agent' => '',
            'headers'    => array(
                'authorization' => 'Bearer ' . $apiKey,
            ),
        );

        $fetchInfoUrl    = $this->remoteBaseUrl . $placeId;
        $businessInfoResponse = wp_remote_get($fetchInfoUrl, $args);

        if(is_wp_error($businessInfoResponse)) {
            throw new \Exception(
                $businessInfoResponse->get_error_message()
            );
        }

        if ('OK' !== wp_remote_retrieve_response_message($businessInfoResponse) || 200 !== wp_remote_retrieve_response_code($businessInfoResponse)) {
            throw new \Exception(
                Arr::get($businessInfoResponse, 'response.message')
            );
        }
        $businessInfoBody      = wp_remote_retrieve_body($businessInfoResponse);
        $businessInfoResult    = json_decode($businessInfoBody, true);

        $this->sourceId = $placeId;

        $formattedBusinessInfo = $this->saveBusinessInfo($businessInfoResult);
        return $formattedBusinessInfo;
    }

    public function saveBusinessInfo($data = array())
    {
        $businessInfo = [];
        $infos         = $this->getBusinessInfo();
        $infos = empty($infos) ? [] : $infos;
        if ($data && is_array($data)) {
            $placeId                          = $this->sourceId;
            $businessInfo['place_id']         = $placeId;
            $businessInfo['name']             = Arr::get($data, 'name');
            $businessInfo['url']              = Arr::get($data, 'url');
            $businessInfo['address']          = Arr::get(isset($data['location']) ? $data['location'] : '', 'city');
            $businessInfo['average_rating']   = Arr::get($data, 'rating');
            $businessInfo['total_rating']     = Arr::get($data, 'review_count');
            $businessInfo['phone']            = Arr::get($data, 'phone');
            $businessInfo['platform_name']    = $this->platform;
            $businessInfo['status']           = true;
            $infos[$placeId]                  = $businessInfo;
        }
        return $infos;
    }

    public function getBusinessInfo()
    {
        return get_option('wpsr_reviews_yelp_business_info');
    }

    public function saveApiSettings($settings)
    {
        $apiKey       = $settings['api_key'];
        $placeId      = $settings['place_id'];
        $apiSettings  = $this->getApiSettings();

        if(isset($apiSettings['data']) && !$apiSettings['data']) {
            $apiSettings = [];
        }

        if($apiKey && $placeId){
            $apiSettings[$placeId]['api_key'] = $apiKey;
            $apiSettings[$placeId]['place_id'] = $placeId;
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

    public function manuallySyncReviews($credentials)
    {
        $settings = get_option($this->optionKey);

        if (!empty($settings) && is_array($settings)) {
            $placeId  = Arr::get($credentials, 'place_id', '');
            $apiKey   = Arr::get($settings, $placeId.'.api_key', '');
            if($apiKey && $placeId){
                try {
                    $this->verifyCredential($apiKey, $placeId);
                    wp_send_json_success([
                        'message'  => __('Reviews synced successfully!', 'wp-social-ninja-pro')
                    ]);
                } catch (\Exception $exception){
                    wp_send_json_error([
                        'message'    => $exception->getMessage()
                    ], 423);
                }
            }
        }
    }

    public function doCronEvent()
    {
	    $expiredCaches = $this->cacheHandler->getExpiredCaches();

        $settings     = get_option($this->optionKey);
        if (!empty($settings) && is_array($settings)) {
            foreach ($settings as $setting) {
                $placeId = Arr::get($setting, 'place_id');
	            if (in_array($placeId, $expiredCaches)) {
	                $apiKey  = Arr::get($setting, 'api_key', '');
                    if($apiKey && $placeId){
                        try {
                            $this->verifyCredential($apiKey, $placeId);
                        } catch (\Exception $exception){
                            error_log($exception->getMessage());
                        }
                    }

		            $this->cacheHandler->createCache(
			            'wpsr_reviews_' . $this->platform . '_business_info_' . $setting['place_id'],
			            $setting['place_id']
		            );
	            }
            }
        }
    }

    public function clearVerificationConfigs($userId)
    {
        
    }
}