<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\Classes\Reviews\BaseReview;
use WPSocialReviews\Classes\ArrayHelper;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Zomato Reviews Place Id and Api Key
 * @since 1.0.0
 */
class Zomato extends BaseReview
{
    private $remoteBaseUrl = 'https://developers.zomato.com/api/v2.1/reviews';
    private $remoteBaseUrlRes = 'https://developers.zomato.com/api/v2.1/restaurant';

    public function __construct()
    {
        parent::__construct(
            'zomato',
            'wpsr_reviews_zomato_settings',
            'wpsr_zomato_reviews_update'
        );
    }

    public function handleCredentialSave()
    {
        $apiKey  = sanitize_text_field($_REQUEST['api_key']);
        $placeId = sanitize_text_field($_REQUEST['source_id']);
        $count   = $_REQUEST['count'] ? sanitize_text_field($_REQUEST['count']) : 10;

        try {
            $businessInfo = $this->verifyCredential($apiKey, $placeId, $count);
            wp_send_json_success([
                'message'       => __('Zomato Business Reviews successfully saved', 'wp-social-ninja-pro'),
                'business_info' => $businessInfo
            ], 200);

        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 423);
        }
    }

    public function doCronEvent()
    {
        $settings     = get_option($this->optionKey);
        $businessInfo = $this->verifyCredential($settings['api_key'], $settings['place_id'], $settings['count']);
    }

    public function pushValidPlatform($platforms)
    {
        $settings = $this->getApiSettings();
        if ($settings['api_key'] && $settings['place_id']) {
            $platforms['zomato'] = __('Zomato Business', 'wp-social-ninja-pro');
        }

        return $platforms;
    }

    public function verifyCredential($apiKey, $placeId, $count)
    {
        $data = $this->fetchRemoteReviews($apiKey, $placeId, $count);

        if (is_wp_error($data)) {
            throw new \Exception($data->get_error_message());
        }

        $oldSettings = $this->getApiSettings();
        if ($oldSettings['place_id'] != $placeId) {
            $this->deletePlatformReviews();
        }

        $this->saveApiSettings([
            'api_key'  => $apiKey,
            'place_id' => $placeId,
            'count'    => $count
        ]);

        $reviews = $data['user_reviews'];
        if (is_array($reviews)) {

            //add cron event schedule here
            $this->syncRemoteReviews($data['user_reviews']);

        }
        //$this->syncZomatoRemoteReviews($data['reviews']);
        //unset($data['user_reviews']);
        // update_option('wpsr_reviews_zomato_business_info', $data, 'no');
        return $this->getBusinessInfo();

    }

    public function fetchRemoteReviews($apiKey, $placeId, $count)
    {


        $args = array(
            'headers' => array(
                'user-key' => $apiKey
            )
        );

        // fetch restaurant reviews
        $reviewsUrl = add_query_arg([
            'res_id' => $placeId,
            'start'  => 1,
            'count'  => $count
        ], $this->remoteBaseUrl);

        $response = wp_remote_get($reviewsUrl, $args);


        // fetch restaurant info
        $restaurant_url = add_query_arg([
            'res_id' => $placeId,
        ], $this->remoteBaseUrlRes);

        $restaurant_details = wp_remote_get($restaurant_url, $args);

        // wp_send_json_success([
        //     'message'       => __('Zomato Business Reviews successfully saved', 'wp-social-ninja-pro'),
        //     'save' => json_decode(wp_remote_retrieve_body($restaurant_details,true),true)
        // ], 200);

        if (($restaurant_details['response']['message'] === "Forbidden") || ($response['response']['message'] === "Forbidden")) {
            return new \WP_Error(423, ArrayHelper::get($data, 'error_message', 'Please enter valid api key'));
        }
        if (!($restaurant_details['response']['message'] === "OK") || !($response['response']['message'] === "OK")) {
            return new \WP_Error(423, ArrayHelper::get($response, 'error_message', 'Please enter your valid place id'));
        } else {
            $data          = json_decode(wp_remote_retrieve_body($restaurant_details, true), true);
            $business_info = $this->getBusinessInfo($data);
            update_option('wpsr_reviews_zomato_business_info', $business_info, 'no');

            return json_decode(wp_remote_retrieve_body($response, true), true);
        }

    }

    public function formatData($review, $index)
    {
        $apiSettings = $this->getApiSettings();

        $review_text = Arr::get($review, 'review.review_text', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }
        return [
            'platform_name' => $this->platform,
            'rating'        => $review['review']['rating'],
            'reviewer_text' => $review_text,
            // 'review_time'   => $review[ 'review' ][ 'timestamp' ],
            'review_title'  => $this->platform . '_' . ($index + 1),
            'reviewer_name' => $review['review']['user']['name'],
            'reviewer_url'  => $review['review']['user']['profile_url'],
            'reviewer_img'  => $review['review']['user']['profile_image'],
            'source_id'     => ArrayHelper::get($apiSettings, 'place_id'),
            'review_time'   => gmdate("Y-m-d H:i:s", $review['review']['timestamp']),
            'updated_at'    => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s')
        ];

        // return [
        //     'platform_name' => $this->platform,
        //     'source_id'     => ArrayHelper::get($apiSettings, 'place_id'),
        //     'reviewer_name' => ArrayHelper::get($review, 'author_name'),
        //     'review_title'  => $this->platform.'_'.($index+1),
        //     'reviewer_url'  => ArrayHelper::get($review, 'author_url'),
        //     'reviewer_img'  => ArrayHelper::get($review, 'profile_photo_url'),
        //     'reviewer_text' => ArrayHelper::get($review, 'text'),
        //     'rating'        => intval(ArrayHelper::get($review, 'rating')),
        //     'review_time'   => gmdate("Y-m-d H:i:s", ArrayHelper::get($review, 'time')),
        //     'updated_at'    => date('Y-m-d H:i:s'),
        //     'created_at'    => date('Y-m-d H:i:s')
        // ];
    }

    public function getBusinessInfo($data = array())
    {
        $info = get_option('wpsr_reviews_zomato_business_info');

        $businessInfo = [
            'name'           => '',
            'url'            => '',
            'address'        => '',
            'average_rating' => '',
            'total_rating'   => '',
            'phone'          => '',
            'platform_name'  => '',
            'status'         => false
        ];

        if ($data && is_array($data)) {
            $businessInfo = [
                'name'           => ArrayHelper::get($data, 'name'),
                'url'            => ArrayHelper::get($data, 'url'),
                'address'        => ArrayHelper::get(isset($data['location']) ? $data['location'] : '', 'address'),
                'average_rating' => ArrayHelper::get(isset($data['user_rating']) ? $data['user_rating'] : '',
                    'aggregate_rating'),
                'total_rating'   => ArrayHelper::get($data, 'all_reviews_count'),
                'phone'          => ArrayHelper::get($data, 'phone_numbers'),
                'platform_name'  => $this->platform,
                'status'         => true
            ];
        }

        if ($info && is_array($info)) {
            $businessInfo = [
                'name'           => ArrayHelper::get($info, 'name'),
                'url'            => ArrayHelper::get($info, 'url'),
                'address'        => ArrayHelper::get($info, 'address'),
                'average_rating' => ArrayHelper::get($info, 'average_rating'),
                'total_rating'   => ArrayHelper::get($info, 'total_rating'),
                'phone'          => ArrayHelper::get($info, 'phone'),
                'platform_name'  => $this->platform,
                'status'         => true
            ];
        }

        return $businessInfo;
    }

    public function saveApiSettings($settings)
    {
        return update_option($this->optionKey, $settings, 'no');
    }

    public function getApiSettings()
    {
        $settings = get_option($this->optionKey);
        if (!$settings || empty($settings['api_key'])) {
            $settings = [
                'api_key'  => '',
                'place_id' => '',
                'count'    => ''
            ];
        }

        return $settings;
    }

    public function getAdditionalInfo()
    {
        return [];
    }
}
