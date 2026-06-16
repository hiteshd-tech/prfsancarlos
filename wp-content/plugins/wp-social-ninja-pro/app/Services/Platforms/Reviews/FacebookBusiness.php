<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\DataProtector;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\PlatformData;
use WPSocialReviews\App\Services\Platforms\PlatformErrorManager;
use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Facebook Reviews Page Id and Api Key
 * @since 1.0.0
 */
class FacebookBusiness extends BaseReview
{
    private $remoteBaseUrl = 'https://graph.facebook.com/';
    private $place_id = null;
    private $account_id = null;
    private $api_key = null;
    private $credentialsType = 'oauth';
    protected $protector;
    protected $platfromData;
    protected $errorManager;
    // protected $app = null;

    public function __construct()
    {
        parent::__construct(
            'facebook',
            'wpsr_reviews_facebook_settings',
            'wpsr_facebook_reviews_update'
        );

        if(class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')){
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }

        $this->protector = new DataProtector();

        $this->platfromData = new PlatformData($this->platform);
        $this->errorManager = new PlatformErrorManager($this->platform);
    }

    public function handleCredentialSave($settings = array())
    {
        $this->credentialsType = Arr::get($settings, 'credentialsType');
        $apiKey  = $this->credentialsType !== 'manually_connect' && $this->protector->decrypt($settings['api_key']) ? $this->protector->decrypt($settings['api_key']) : $settings['api_key'];
        $placeId = $settings['source_id'];

        try {
            $businessInfo = $this->verifyCredential($apiKey, $placeId);
            // save caches when auto sync is on
            $apiSettings = get_option('wpsr_facebook_global_settings', []);
            if(Arr::get($apiSettings, 'global_settings.auto_syncing') === 'true'){
                $this->saveCache();
            }

            $connectedAccounts = get_option('wpsr_reviews_facebook_settings', []);
            if(!empty($placeId) && !empty($connectedAccounts)){
                $connectedAccounts[$placeId]['user_id'] = $placeId;
                $connectedAccounts[$placeId]['username'] = $placeId;
                $this->errorManager->removeErrors('connection', $connectedAccounts[$placeId]);
            }

            wp_send_json_success([
                'message'       => __('Facebook Business Reviews Successfully Saved', 'wp-social-ninja-pro'),
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
        $businessInfos    = $this->getBusinessInfo();
        if ($businessInfos && sizeof($businessInfos) > 0) {
            $platforms['facebook'] = __('Facebook', 'wp-social-ninja-pro');
        }
        return $platforms;
    }

    public function verifyCredential($accessToken, $placeId)
    {
        $data = $this->fetchRemoteReviews($accessToken, $placeId);
        if (is_wp_error($data)) {
            throw new \Exception($data->get_error_message());
        }

        $this->place_id = $placeId;
        $this->api_key = $accessToken;

        $reviews = isset($data['ratings']['data']) ? $data['ratings']['data'] : array();
        if (empty($reviews)) {
            throw new \Exception(__('We could not find any reviews from this page.', 'wp-social-ninja-pro'));
        }

        $this->saveApiSettings($accessToken, $placeId);

        if($accessToken && $placeId && $this->credentialsType === 'manually_connect'){
            $this->manuallyConnectSource($data, $accessToken, $placeId);
        }
        
        $this->reviewDelete($reviews,$placeId);
        $this->syncRemoteReviews($reviews);
        $business_info = $this->saveBusinessInfo($data);
        update_option('wpsr_reviews_facebook_business_info', $business_info, 'no');

        return $business_info;
    }

    public function fetchRemoteReviews($accessToken, $pageId)
    {
        $total_reviews = apply_filters('wpsocialreviews/facebook_reviews_limit', 200);
        $api_url       = $this->remoteBaseUrl . $pageId . "?access_token=" . $accessToken . "&fields=name,link,ratings.fields(has_review,reviewer{id,name,picture.width(120).height(120)},created_time,rating,recommendation_type,review_text,open_graph_story{id}).limit(" . $total_reviews . "),overall_star_rating,rating_count";

        $data = $this->makeRequest($api_url);

        if (is_wp_error($data)) {
            $message = $data->get_error_message();
            return new \WP_Error(423, $message);
        }

        if(!empty(Arr::get($data, 'error'))) {
            if(Arr::get($data, 'error.code') && (new PlatformData('facebook'))->isAppPermissionError($data)){
                do_action( 'wpsocialreviews/'.$this->platform.'_app_permission_revoked' );
            }

            $connectedAccounts = get_option('wpsr_reviews_facebook_settings', []);
            if(empty($connectedAccounts)) {
                $connectedAccounts = [];
            }

            $accountDetails = Arr::get($connectedAccounts, $pageId);
            $place_id  = Arr::get($accountDetails, 'place_id');
            if( !empty($place_id) ) {
                $connectedAccounts = $this->addPlatformApiErrors($data, $connectedAccounts, $accountDetails);
                update_option('wpsr_reviews_facebook_settings', $connectedAccounts);
            }
        }

        if(Arr::get($data, 'error')) {
            $message = Arr::get($data, 'error.message');
            return new \WP_Error(423, $message);
        }

        return $data;
    }

    public function formatData($review, $index)
    {
        $reviewer_img  = Arr::get($review, 'reviewer.picture.data.url', '');
        $review_id     = Arr::get($review, 'reviewer.id', '');
        $reviewer_name = Arr::get($review, 'reviewer.name', '');
        $recommendation_type = Arr::get($review, 'recommendation_type', '');

        $review_text = Arr::get($review, 'review_text', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }

        return [
            'platform_name'       => $this->platform,
            'source_id'           => $this->place_id,
            'review_id'           => $review_id,
            'reviewer_name'       => $reviewer_name,
            'review_title'        => '',
            'reviewer_url'        => $review_id ? 'https://facebook.com/' . $review_id : '',
            'reviewer_img'        => $reviewer_img,
            'reviewer_text'       => $review_text,
            'rating'              => $recommendation_type === 'positive' ? 5 : 1,
            'review_time'         => date('Y-m-d H:i:s', strtotime($review['created_time'])),
            'recommendation_type' => $recommendation_type,
            'review_approved'     => 1,
            'updated_at'          => date('Y-m-d H:i:s'),
            'created_at'          => date('Y-m-d H:i:s')
        ];
    }

    public function manuallyConnectSource($data, $accessToken, $placeId)
    {
        $page_list = $this->getAdditionalInfo();
        $is_exist_page_id = false;

        foreach ($page_list as $page){
            if( in_array($placeId, $page) ){
                $is_exist_page_id = true;
                break;
            }
        }

        if(!$is_exist_page_id){
            $manualPageData = [];
            $manualPageData['access_token'] = $this->protector->maybe_encrypt($accessToken);
            $manualPageData['id'] = $placeId;
            $manualPageData['name'] = Arr::get($data, 'name');
            $page_list[$placeId] = $manualPageData;
            update_option('wpsr_reviews_facebook_pages_list', $page_list, 'no');
        }
    }

    public function saveBusinessInfo($data = array())
    {
        $businessInfo = [];
        $infos         = $this->getBusinessInfo();
        $infos = empty($infos) ? [] : $infos;

        $totalRatingValue = 0;
        if (Arr::get($data, 'ratings.data')) {
            foreach ($data['ratings']['data'] as $index => $review) {
                $recommendation_type = Arr::get($review, 'recommendation_type');
                $totalRatingValue    += $recommendation_type === 'positive' ? 5 : 1;
            }
        }

        if ($data && is_array($data)) {
            $api_total_rating_count = Arr::get($data, 'rating_count', 0);
            $api_overall_star_rating = Arr::get($data, 'overall_star_rating', 0);
            $ratings = Arr::get($data, 'ratings.data');
            $total_rating   = $ratings ? count($ratings) : null;
            $average_rating = $total_rating > 0 && $totalRatingValue > 0 ? $totalRatingValue / $total_rating : 0;

            $business_id    = Arr::get($data, 'id');

            $businessInfo['place_id']        = $business_id;
            $businessInfo['name']            = Arr::get($data, 'name');
            $businessInfo['url']             = Arr::get($data, 'link') . '/reviews';
            $businessInfo['address']         = Arr::get($data, '');
            $businessInfo['average_rating']  = $api_overall_star_rating > 0 ? $api_overall_star_rating : round($average_rating, 2);
            $businessInfo['total_rating']    = ($api_total_rating_count > 0 && $api_total_rating_count >= $total_rating) ? $api_total_rating_count : $total_rating;
            $businessInfo['phone']           = Arr::get($data, '');
            $businessInfo['platform_name']   = $this->platform;
            $businessInfo['status']          = true;
            $infos[$business_id]             =  $businessInfo;
        }
        return $infos;
    }

    public function getBusinessInfo()
    {
        $connectedAccounts = get_option('wpsr_reviews_facebook_settings', []);
        if(empty($connectedAccounts)) {
            $connectedAccounts = [];
        }

        $businesses = get_option('wpsr_reviews_facebook_business_info', []);
        if(!empty($businesses)) {
            foreach($businesses as $business) {
                $page_id = Arr::get($business, 'place_id');
                $account = Arr::get($connectedAccounts, $page_id);

                if(!empty($account)) {
                    $businesses[$page_id]['error_message'] = Arr::get($account, 'error_message');
                    $businesses[$page_id]['status'] = Arr::get($account, 'status', 'success');
                    $businesses[$page_id]['has_app_permission_error'] = Arr::get($account, 'has_app_permission_error', false);
                    $businesses[$page_id]['has_critical_error'] = Arr::get($account, 'has_critical_error', false);
                    $businesses[$page_id]['error_code'] = Arr::get($account, 'error_code');
                    $businesses[$page_id]['encryption_error'] = Arr::get($account, 'encryption_error', 'false');
                }
            }
        }
        return $businesses;
    }

    public function getAdditionalInfo()
    {
        $pages_list = get_option('wpsr_reviews_facebook_pages_list', []);
        $pages_list = $this->formatPageData($pages_list);

        return $pages_list;
    }

    public function saveApiSettings($accessToken, $placeId)
    {
        $apiSettings  = $this->getApiSettings();
        $pageList = $this->getAdditionalInfo();
        $pageDataByPageId = Arr::get($pageList, $placeId.'.id');

        if($accessToken && $placeId){
            $apiSettings[$placeId]['api_key'] = $this->protector->maybe_encrypt($accessToken);
            $apiSettings[$placeId]['place_id'] = $placeId;
            $apiSettings[$placeId]['account_id'] = Arr::get($pageList, $pageDataByPageId.'.account_id');
            $apiSettings[$placeId]['status'] = 'success';
            $apiSettings[$placeId]['error_message'] = '';
            $apiSettings[$placeId]['error_code'] = '';
            $apiSettings[$placeId]['has_app_permission_error'] = false;
            $apiSettings[$placeId]['has_critical_error'] = false;
            $apiSettings[$placeId]['encryption_error'] = false;
        }

        return update_option($this->optionKey, $apiSettings, 'no');
    }

    public function getApiSettings()
    {
        $settings = get_option($this->optionKey, []);
        return $settings;
    }

    public function makeRequest($api_url)
    {
        $args     = array(
            'timeout'   => 60
        );

        $response = wp_remote_get($api_url, $args);
        do_action( 'wpsocialreviews/'.$this->platform.'_api_connect_response', $response );

        if (!is_wp_error($response)) {
            $response = json_decode(wp_remote_retrieve_body($response), true);
        }

        return $response;
    }

    public function formatPageData($accountData = [], $account_id = '')
    {
        $formatAccounts = [];

        $data = Arr::get($accountData, 'data');
        if(!empty($data) && is_array($data)){
            foreach ($data as $account){
                $page_id = Arr::get($account, 'id');
                $access_token = Arr::get($account, 'access_token');
                $formatAccounts[$page_id]['access_token'] = $this->protector->maybe_encrypt($access_token);
                $formatAccounts[$page_id]['account_id'] = $account_id;
                $formatAccounts[$page_id]['id'] = $page_id;
                $formatAccounts[$page_id]['name'] = Arr::get($account, 'name');
            }
            return $formatAccounts;
        }

        return $accountData;
    }

    public function saveConfigs($accessToken = null)
    {
        try {
            $api_url  = $this->remoteBaseUrl . "me?fields=accounts.limit(500),id,name&access_token=" . $accessToken;
            $apiData = $this->makeRequest($api_url);

            if(is_wp_error($apiData)) {
                throw new \Exception($apiData->get_error_message());
            }

            $account_id = Arr::get($apiData, 'id');
            $apiData = Arr::get($apiData, 'accounts');
            $nextPageUrl = Arr::get($apiData, 'paging.next');

            if($nextPageUrl){
                while($nextPageUrl){
                    $apiData = $this->getNextPageAccountsResponse($nextPageUrl, $apiData);
                    $nextPageUrl = Arr::get($apiData, 'paging.next');
                }
            }

            $formattedData = $this->formatPageData($apiData, $account_id);

            update_option('wpsr_reviews_facebook_pages_list', $formattedData, 'no');
            $settings = get_option('wpsr_reviews_facebook_pages_list', []);
            $businessInfo   = get_option('wpsr_reviews_facebook_business_info', []);
            wp_send_json_success(
                [
                    'settings' => $settings,
                    'business_info'   => $businessInfo,
                    'message'  => __('You are Successfully Verified', 'wp-social-ninja-pro')
                ],
                200
            );
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 423);
        }
    }

    public function getNextPageAccountsResponse($nextPageUrl, $accountsData)
    {
        $result = $this->makeRequest($nextPageUrl);
        $newPageData = Arr::get($result, 'data', []);
        $oldPageData = Arr::get($accountsData, 'data', []);
        $result['data'] = array_merge($newPageData, $oldPageData);
        return $result;
    }

    public function manuallySyncReviews($credentials)
    {
        $settings = get_option($this->optionKey, []);
        if (!empty($settings) && is_array($settings)) {
            $placeId  = Arr::get($credentials, 'place_id', '');
            $apiKey   = Arr::get($settings, $placeId.'.api_key', '');
            $apiKey   = $this->protector->decrypt($apiKey) ? $this->protector->decrypt($apiKey) : $apiKey;
            if($apiKey && $placeId){
                try {
                    $this->verifyCredential($apiKey, $placeId);
                    wp_send_json_success([
                        'message'  => __('Reviews synced successfully!', 'wp-social-ninja-pro')
                    ], 200);
                } catch (\Exception $exception){
                    wp_send_json_error([
                        'message'  => $exception->getMessage()
                    ], 423);
                }
            }
        }
    }

    public function doCronEvent()
    {
	    $expiredCaches = $this->cacheHandler->getExpiredCaches();
        $settings = get_option($this->optionKey, []);

        if (!empty($settings) && is_array($settings)) {
            foreach ($settings as $setting){
	            if (in_array($setting['place_id'], $expiredCaches)) {
		            $apiKey  = Arr::get($setting, 'api_key', '');
                    $apiKey   = $this->protector->decrypt($apiKey) ? $this->protector->decrypt($apiKey) : $apiKey;

		            $placeId  = Arr::get($setting, 'place_id', '');
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

    private function hasEncryptionError($accessToken)
    {
        if($accessToken){
            return strpos($accessToken, 'EA') === false && !$this->protector->decrypt($accessToken);
        }
        return false;
    }

    public function addPlatformApiErrors($response, $connectedAccounts, $accountDetails)
    {
        $critical_codes = array(
            803, // ID doesn't exist
            100, // access token or permissions
            190, // app removed
            10, // app permissions or scopes
        );

        $responseErrorCode = Arr::get($response, 'error.code', '');
        $pageId   = Arr::get($accountDetails, 'place_id', null);
        $accessToken   =  Arr::get($accountDetails, 'api_key', '');

        if(!empty($responseErrorCode) && !$this->hasEncryptionError($accessToken)){
            $connectedAccounts[$pageId]['error_message'] = Arr::get($response, 'error.message', '');
            $connectedAccounts[$pageId]['error_code'] = $responseErrorCode;
            $connectedAccounts[$pageId]['has_critical_error'] = in_array( $responseErrorCode, $critical_codes, true );
            $connectedAccounts[$pageId]['has_app_permission_error'] = $this->platfromData->isAppPermissionError($response);
        }
        $connectedAccounts[$pageId]['status'] = 'error';

        if($this->hasEncryptionError($accessToken)){
            $connectedAccounts[$pageId]['encryption_error'] = true;
            $connectedAccounts[$pageId]['has_critical_error'] = false;
            $connectedAccounts[$pageId]['error_code'] = 999;
            $encryptionError = GlobalHelper::getEncryptionErrorData();
            $response['error']['message'] = Arr::get($encryptionError, 'message');
            $response['error']['code'] =  Arr::get($encryptionError, 'code');
        }

        $accountDetails['user_id'] = $pageId;
        $accountDetails['username'] = $pageId;

        $this->errorManager->addError('api', $response, $accountDetails);

        return $connectedAccounts;
    }

    public function clearVerificationConfigs($userId)
    {
        $connectedAccounts = get_option('wpsr_reviews_facebook_pages_list', []);
        if(empty($connectedAccounts)) {
            $connectedAccounts = [];
        }

        $connectedAccounts[$userId]['user_id'] = $userId;
        $connectedAccounts[$userId]['username'] = $userId;
        $this->errorManager->removeErrors('connection', $connectedAccounts[$userId]);
    }
}
