<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper as ReviewsHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Libs\SimpleDom\Helper;
use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviewsPro\App\Services\Helper as ProHelper;
use WPSocialReviews\App\Services\Includes\CountryNames;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Booking Reviews
 * @since 1.0.0
 */
class Booking extends BaseReview
{
    private $remoteBaseUrl = 'https://www.booking.com/reviewlist.html';
    private $placeId = null;

    public function __construct()
    {
        parent::__construct(
            'booking.com',
            'wpsr_reviews_booking.com_settings',
            'wpsr_booking.com_reviews_update'
        );
        if(class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')){
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }
    }

    public function handleCredentialSave($credentials)
    {
        $downloadUrl = $credentials['url_value'];
        $language = Arr::get($credentials, 'language');
        $credData = [
            'language' => $language,
            'url' => $downloadUrl,
        ];

        try {
            $businessInfo = $this->verifyCredential($credData);
            $message = ReviewsHelper::getNotificationMessage($businessInfo, $this->placeId);

            if (Arr::get($businessInfo, 'total_fetched_reviews') && Arr::get($businessInfo, 'total_fetched_reviews') > 0) {
                // save caches when auto sync is on
                $apiSettings = get_option('wpsr_booking.com_global_settings');
                if(Arr::get($apiSettings, 'global_settings.auto_syncing') === 'true'){
                    $this->saveCache();
                }
            }

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

    public function verifyCredential($credData)
    {
        $language = Arr::get($credData, 'language');
        $downloadUrl = Arr::get($credData, 'url');

        if (empty($downloadUrl)) {
	        throw new \Exception(
		        __('URL field should not be empty!', 'wp-social-ninja-pro')
	        );
        }

        if (filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
            ini_set('memory_limit', '600M');

            $downloadUrl = strtok($downloadUrl, '?');
            $pageUrl = $downloadUrl;
            $link_array = explode('/', $downloadUrl);

            $page_name = end($link_array);
            $this->placeId = explode('.', $page_name)[0].'-'.$this->platform;

            $country_code = $link_array[count($link_array) - 2];

            $businessInfo = '';
            $reviews = array();
            for ($i = 1; $i <= 4; $i++) {
                $extra = '?r_lang='.$language.'&customer_type=total&page=' . $i;
                $downloadUrl = 'https://www.booking.com/reviews/' . $country_code . '/hotel/' . $page_name . $extra;

                // added header to support Italian, Germany or others languages reviews
                $headers = [
                    'user-agent' => ProHelper::getUserAgent(),
//                    'authority' => 'www.booking.com',
//                    'pragma' => 'no-cache',
//                    'cache-control' => 'no-cache',
//                    'dnt' => '1',
//                    'upgrade-insecure-requests' => '1',
//                    'content-type' => 'text/html; charset=utf-8',
//                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
//                    'accept-language' => 'en-GB,en-US;q=0.9,en;q=0.8',
//                    'sec-fetch-site' => 'none',
//                    'sec-fetch-mode' => 'navigate',
//                    'sec-fetch-dest' => 'document',
                ];

                $headers = apply_filters('wpsocialreviews/booking_header_request_cookie_set', $headers);
                
                $args = [
                    'headers' => $headers
//                    'sslverify' => false,
//                    'timeout' => 30,
//                    'redirection' => 5,
//                    'httpversion' => '1.1',
                ];

                $fileUrlContents = wp_remote_retrieve_body(wp_remote_get($downloadUrl, $args));

                if(empty($fileUrlContents)){
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_URL, $downloadUrl);
                    $fileUrlContents = curl_exec($ch);
                }

                if (empty($fileUrlContents)) {
	                throw new \Exception(
		                __('Can\'t fetch reviews due to slow network, please try again', 'wp-social-ninja-pro')
	                );
                }

                $html = Helper::str_get_html($fileUrlContents);

                if ($i === 1) {
                    $businessInfo = $this->getDetailsInfo($html, $pageUrl);
                }

                $reviewContents = false;
                if ($html->find('ul.review_list', 0)) {
                    $reviewContents = $html->find('ul.review_list', 0)->find('li.review_item');
                    foreach ($reviewContents as $review) {
                        $reviews[] = [
                            'source_id'     => $this->placeId,
                            'reviewer_name' => $this->getReviewerName($review),
                            'review_text'   => $this->getReviewText($review),
                            'review_title'  => $this->getReviewTitle($review),
                            'review_rating' => $this->getReviewRating($review),
                            'reviewer_image'=> '',
                            'review_date'   => $this->getReviewDate($review),
                            'reviewer_url'  => $pageUrl
                        ];
                    }
                } else {
                    break;
                }

                sleep(rand(0, 2));
                if (!empty($html)) {
                    $html->clear();
                    unset($html);
                }
            }

            if (!empty($reviews)) {
                if(!empty($this->placeId) && !empty(Arr::get($businessInfo, 'name'))) {
                    $this->saveApiSettings([
                        'api_key' => '0c799c2f-9af0-475c-922a-4fb60cadb9d2',
                        'place_id' => $this->placeId,
                        'url_value' => $pageUrl,
                        'language' => $language
                    ]);

                    $this->syncRemoteReviews($reviews);

                    $businessInfo = $this->saveBusinessInfo($businessInfo);

                    $totalFetchedReviews = count($reviews);
                    if ($totalFetchedReviews > 0) {
                        update_option('wpsr_reviews_booking.com_business_info', $businessInfo, 'no');
                    }

                    $businessInfo['total_fetched_reviews'] = $totalFetchedReviews;

                    return $businessInfo;
                }
            } else {
                
	            throw new \Exception(
		            __('No reviews Found!', 'wp-social-ninja-pro')
	            );
            }
        } else {
	        throw new \Exception(
		        __('Please enter a valid url!', 'wp-social-ninja-pro')
	        );
        }
    }

    public function pushValidPlatform($platforms)
    {
        $settings    = $this->getApiSettings();
        if (!isset($settings['data']) && sizeof($settings) > 0) {
            $platforms['booking.com'] = __('Booking.com', 'wp-social-ninja-pro');
        }
        return $platforms;
    }

    public function getDetailsInfo($html, $pageUrl)
    {
        $productDetails = array();

        $productDetails['name'] = $this->getBusinessName($html);
        $productDetails['average_rating'] = $this->getAverageRating($html);
        $productDetails['review_url'] = $pageUrl;
        $productDetails['total_rating'] = $this->getTotalRating($html);
        $productDetails['address'] = $this->getBusinessAdress($html);

        return $productDetails;
    }

    public function formatData($review, $index)
    {
        $source_id = Arr::get($review, 'source_id');
        $reviewer_name = Arr::get($review, 'reviewer_name');

        $review_title = Arr::get($review, 'review_title', '');
        $review_text = Arr::get($review, 'review_text', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $review_title = GlobalHelper::sanitizeForStorage($review_title);
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }

        return [
            'platform_name' => $this->platform,
            'source_id'     => $source_id,
            'review_id'     => $source_id.'-'.$reviewer_name,
            'reviewer_name' => $reviewer_name,
            'review_title'  => $review_title,
            'reviewer_url'  => Arr::get($review, 'reviewer_url'),
            'reviewer_img'  => Arr::get($review, 'reviewer_image'),
            'reviewer_text' => $review_text,
            'rating'        => (int)Arr::get($review, 'review_rating'),
            'review_time'   => Arr::get($review, 'review_date'),
            'review_approved' => 1,
            'updated_at'    => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s')
        ];
    }

    public function saveBusinessInfo($data = array())
    {
        $businessInfo  = [];
        $infos         = $this->getBusinessInfo();
        $infos = empty($infos) ? [] : $infos;
        if ($data && is_array($data) && !empty($this->placeId) && !empty(Arr::get($data, 'name', ''))) {
            $placeId                        = $this->placeId;
            $businessInfo['place_id']       = $placeId;
            $businessInfo['name']           = Arr::get($data, 'name');
            $businessInfo['url']            = Arr::get($data, 'review_url');
            $businessInfo['address']        = Arr::get($data, 'address');
            $businessInfo['average_rating'] = Arr::get($data, 'average_rating');
            $businessInfo['total_rating']   = Arr::get($data, 'total_rating');
            $businessInfo['phone']          = '';
            $businessInfo['platform_name']  = $this->platform;
            $businessInfo['status']         = true;
            $infos[$placeId]                = $businessInfo;
        }
        return $infos;
    }

    public function getBusinessInfo()
    {
        return get_option('wpsr_reviews_booking.com_business_info');
    }

    public function saveApiSettings($settings)
    {
        $apiKey       = $settings['api_key'];
        $placeId      = $settings['place_id'];
        $businessUrl  = $settings['url_value'];
        $language     = Arr::get($settings, 'language', 'en');
        $apiSettings  = $this->getApiSettings();

        if(isset($apiSettings['data']) && !$apiSettings['data']) {
            $apiSettings = [];
        }

        if($apiKey && $placeId && $businessUrl){
            $apiSettings[$placeId]['api_key'] = $apiKey;
            $apiSettings[$placeId]['place_id'] = $placeId;
            $apiSettings[$placeId]['url_value'] = $businessUrl;
            $apiSettings[$placeId]['language'] = $language;
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
        $languages      = (new CountryNames())->get();
        return [
            'languages'       => $languages
        ];
    }

    //get business info
    public function getBusinessName($html)
    {
        $businessName = '';
        if ($html->find('a.standalone_header_hotel_link')) {
            $businessName = strip_tags($html->find('a.standalone_header_hotel_link', 0));
        }

        $businessName = ReviewsHelper::removeSpecialChars($businessName);
        return $businessName;
    }

    public function getTotalRating($html)
    {
        $totalRating = '';
        if ($html->find('title')) {
            $totalRating = strip_tags($html->find('title', 0));
            $totalRating = trim($totalRating);
            $totalRating = (int)filter_var($totalRating, FILTER_SANITIZE_NUMBER_INT);
        }

        if (!$totalRating && $html->find('p.review_list_score_count')) {
            $totalRating = strip_tags($html->find('p.review_list_score_count', 0));
            $totalRating = trim($totalRating);
            $totalRating = (int)filter_var($totalRating, FILTER_SANITIZE_NUMBER_INT);
        }

        return $totalRating;
    }

    public function getAverageRating($html)
    {
        $avgRating = '';
        if ($html->find('span.review-score-badge')) {
            $avgRating = strip_tags($html->find('span.review-score-badge', 0));
            if(!empty($avgRating)) {
                $avgRating = str_replace(',', '.', $avgRating);
            }
            $avgRating = trim($avgRating);
        }

        return $avgRating;
    }

    public function getBusinessAdress($html)
    {
        $address = '';
        if ($html->find('p.hotel_address')) {
            $address = strip_tags($html->find('p.hotel_address', 0));
        }

        return $address;
    }

    //get review info
    public function getReviewerName($review)
    {
        //find reviewer name
        $reviewerName = '';
        if ($review->find('div.review_item_reviewer', 0)) {
            $reviewerName = strip_tags($review->find('div.review_item_reviewer',
                0)->find('p.reviewer_name', 0));
        }

        if(!empty($reviewerName)) {
            $reviewerName = trim($reviewerName, " ");
        }

        return $reviewerName;
    }

    public function getReviewText($review)
    {
        $reviewText = '';
        if ($review->find('div.review_item_review_content', 0)) {
            if ($review->find('div.review_item_review_content', 0)->find('p.review_pos', 0)) {
                if ($review->find('div.review_item_review_content', 0)->find('p.review_pos', 0)->find('span[itemprop=reviewBody]', 0)) {
                    $reviewText = strip_tags($review->find('div.review_item_review_content', 0)->find('p.review_pos', 0)->find('span[itemprop=reviewBody]', 0));
                    $reviewText = trim($reviewText);
                }
            }
        }

        if(empty($reviewText)) {
            if ($review->find('div.review_item_review_content', 0)) {
                if ($review->find('div.review_item_review_content', 0)->find('p.review_neg ', 0)) {
                    if ($review->find('div.review_item_review_content', 0)->find('p.review_neg', 0)->find('span[itemprop=reviewBody]', 0)) {
                        $reviewText = strip_tags($review->find('div.review_item_review_content', 0)->find('p.review_neg ', 0)->find('span[itemprop=reviewBody]', 0));
                        $reviewText = trim($reviewText);
                    }
                }
            }
        }

        return $reviewText;
    }

    public function getValidCharIndex($chars) {
        $cnt = 0;
        foreach($chars as $char) {
            if(($char >= 'a' && $char <= 'z') || ($char >= 'A' && $char <= 'Z')) {
                break;
            }
            $cnt++;
        }

        return $cnt;
    }
    public function getReviewTitle($review)
    {
        $reviewTitle = '';
        if($review->find('div.review_item_header_content')) {
            $reviewTitle = $review->find('div.review_item_header_content', 0)->plaintext;
        }

        if(!empty($reviewTitle)) {
            $reviewTitle    = html_entity_decode($reviewTitle, ENT_QUOTES, "UTF-8");

            //remove unnecessary chars from the beginning of the string
            $chars          = str_split($reviewTitle);
            $validIndex     = $this->getValidCharIndex($chars);
            $reviewTitle    = substr($reviewTitle, $validIndex);

            //remove unnecessary chars from the ending of the string
            $chars          = array_reverse($chars);
            $validIndex     = $this->getValidCharIndex($chars);
            $reviewTitle    = substr($reviewTitle, 0, -$validIndex);
        }

        if(!empty($reviewTitle)) {
            $reviewTitle = trim($reviewTitle, " ");
        }

        if (!is_string($reviewTitle)) {
            $reviewTitle = '';
        }
        return $reviewTitle;
    }

    public function getReviewRating($review)
    {
        $reviewRating = '';
        if ($review->find('span.review-score-badge', 0)) {
            $reviewRating = strip_tags($review->find('span.review-score-badge', 0));
            $reviewRating = trim($reviewRating);
        }

        return $reviewRating;
    }

    public function getReviewDate($review)
    {
        $reviewDate = '';
        if ($review->find('p.review_item_date', 0)) {
            $reviewDateText = strip_tags($review->find('p.review_item_date', 0));
            $reviewDateText = trim($reviewDateText);

            //dynamic date extraction from various language
            $filteredNumbers = array_filter(preg_split("/\D+/", $reviewDateText));
            $firstOccurence = reset($filteredNumbers);

            $offset = strpos($reviewDateText, $firstOccurence);
            $reviewDate = substr($reviewDateText, $offset);

            while(strlen($reviewDate) && !is_numeric($reviewDate[strlen($reviewDate)-1])) {
                $reviewDate = substr_replace($reviewDate, "", -1);
            }
            
            if($reviewDate) {
                $reviewDate = trim($reviewDate, ' ');
            }

            $dateSubmitted = ProHelper::unixTimeStamp($reviewDate);
            $reviewDate = date("Y-m-d H:i:s", $dateSubmitted);
        }

        return $reviewDate;
    }

    public function clearVerificationConfigs($userId)
    {

    }
}
