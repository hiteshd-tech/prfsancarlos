<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviews\App\Services\Libs\SimpleDom\Helper;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper as ReviewsHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviewsPro\App\Services\Helper as ProHelper;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Amazon Reviews
 * @since 1.0.0
 */
class Amazon extends BaseReview
{
    private $remoteBaseUrl = 'https://amazon.com';
    private $placeId = null;

    public function __construct()
    {
        parent::__construct(
            'amazon',
            'wpsr_reviews_amazon_settings',
            'wpsr_amazon_reviews_update'
        );
        if(class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')){
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }
    }

    public function handleCredentialSave($settings = array())
    {
        $downloadUrl = $settings['url_value'];
        $credData = [
            'url' => $downloadUrl,
        ];
        try {
            $businessInfo = $this->verifyCredential($credData);
            $message = ReviewsHelper::getNotificationMessage($businessInfo, $this->placeId);

            if (Arr::get($businessInfo, 'total_fetched_reviews') && Arr::get($businessInfo, 'total_fetched_reviews') > 0) {
                // save caches when auto sync is on
                $apiSettings = get_option('wpsr_amazon_global_settings');
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
        $downloadUrl = Arr::get($credData, 'url');
        if (empty($downloadUrl)) {
            throw new \Exception(
                __('URL field should not be empty!', 'wp-social-ninja-pro')
            );
        }

        if (filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
            ini_set('memory_limit', '600M');
            $downloadUrl = strtok($downloadUrl, '?');

            $reg     = '#(?:http[s]?://(?:www\.){0,1}amazon(.*?)(?:/.*){0,1}(?:/dp/|/gp/product/|/product-reviews/))(.*?)(?:/.*|$)#';

            $matches = array();
            preg_match($reg, $downloadUrl, $matches);
            $parse = wp_parse_url($downloadUrl);

            $productDetails = $this->getProductDetails($downloadUrl);

            $baseUrl = '';
            if(!empty($matches)) {
                $fetchUrl = $parse['scheme'] . '://' . $parse['host'] . '/product-reviews/' . $matches[2];
                $baseUrl = $fetchUrl . '/ref=cm_cr_getr_d_paging_btm_prev_1?ie=UTF8&reviewerType=all_reviews&pageNumber=';
                $this->placeId = $matches[2];
            }

            $startUrls = [];
            # Creating list of urls to be scraped by appending page number at the end of base url
            for ($i = 1; $i <= 10; $i++) {
                array_push($startUrls, $baseUrl . $i);
            }

            $reviews = array();
            foreach ($startUrls as $index => $urlValue) {
                // $fileUrlContents = wp_remote_retrieve_body(wp_remote_get($urlValue, array('User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0')));

                $headers = [
                    'authority' => 'www.amazon.com',
                    'pragma' => 'no-cache',
                    'cache-control' => 'no-cache',
                    'dnt' => '1',
                    'upgrade-insecure-requests' => '1',
                    'user-agent' => 'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36',
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'sec-fetch-site' => 'none',
                    'sec-fetch-mode' => 'navigate',
                    'sec-fetch-dest' => 'document',
                    'accept-language' => 'en-GB,en-US;q=0.9,en;q=0.8',
                ];

                $args = [
                    'headers' => $headers
                ];

                $fileUrlContents = wp_remote_retrieve_body(wp_remote_get($urlValue, $args));

                if (empty($fileUrlContents)) {
                    throw new \Exception(
                        __('Can\'t fetch reviews due to slow network, please try again', 'wp-social-ninja-pro')
                    );
                }
                $html = Helper::str_get_html($fileUrlContents);

                if($index == 0 && empty(Arr::get($productDetails, 'average_rating')) && empty(Arr::get($productDetails, 'total_rating'))) {
                    if(!empty($html)) {
                        $productDetails['product_name']     = $this->getProductName($html);
                        $productDetails['average_rating']   = $this->getAverageRating($html);
                        $productDetails['review_url']       = $downloadUrl;
                        $productDetails['total_rating']     = intval($this->getTotalRating($html));
                    }
                }

                //find reviews
                $reviewContents = false;
                if ($html->find('div.reviews-content', 0)) {
                    $reviewContents = $html->find('div.reviews-content', 0)->find('div.review');
                    foreach ($reviewContents as $review) {
                        $reviewRating = substr($this->getReviewRating($review), 0, 3);
                        $reviewRating = str_replace(',', '.', $reviewRating);

                        $reviews[] = [
                            'source_id'      => $this->placeId,
                            'reviewer_name'  => $this->getReviewerName($review),
                            'review_title'   => $this->getReviewTitle($review),
                            'review_text'    => $this->getReviewText($review),
                            'review_rating'  => $reviewRating,
                            'reviewer_image' => $this->getReviewerImage($review),
                            'review_date'    => $this->getReviewDate($review),
                            'reviewer_url'   => $downloadUrl
                        ];
                    }
                }
                sleep(rand(0, 2));
                if (!empty($html)) {
                    $html->clear();
                    unset($html);
                }
            }

            if (!empty($reviews)) {
                if(!empty($this->placeId) && !empty(Arr::get($productDetails, 'product_name'))) {
                    $this->saveApiSettings([
                        'api_key' => '479711fa-64ba-47ce-b63b-9c2ba8d663f9',
                        'place_id' => $this->placeId,
                        'url_value' => $downloadUrl
                    ]);

                    $this->syncRemoteReviews($reviews);
                    $businessInfo = $this->saveBusinessInfo($productDetails);

                    $totalFetchedReviews = count($reviews);
                    if ($totalFetchedReviews > 0) {
                        update_option('wpsr_reviews_amazon_business_info', $businessInfo, 'no');
                    }

                    $businessInfo['total_fetched_reviews'] = $totalFetchedReviews;

                    return $businessInfo;
                }
            } else {
                
                throw new \Exception(
                    __('Please try again after few minutes!', 'wp-social-ninja-pro')
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
            $platforms['amazon'] = __('Amazon', 'wp-social-ninja-pro');
        }
        return $platforms;
    }

    public function getProductDetails($downloadUrl)
    {
        $headers = [
            'authority' => 'www.amazon.com',
            'pragma' => 'no-cache',
            'cache-control' => 'no-cache',
            'dnt' => '1',
            'upgrade-insecure-requests' => '1',
            'user-agent' => 'Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36',
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'sec-fetch-site' => 'none',
            'sec-fetch-mode' => 'navigate',
            'sec-fetch-dest' => 'document',
            'accept-language' => 'en-GB,en-US;q=0.9,en;q=0.8',
        ];

        $args = [
            'headers' => $headers
        ];

        $fileUrlContents = wp_remote_retrieve_body(wp_remote_get($downloadUrl, $args));

        if (empty($fileUrlContents)) {
            throw new \Exception(
                __('Can\'t fetch reviews due to slow network, please try again', 'wp-social-ninja-pro')
            );
        }
        //fix for lazy load base64 ""
        $fileUrlContents = str_replace('=="', '', $fileUrlContents);
        $html            = Helper::str_get_html($fileUrlContents);
        $productDetails  = array();
        if(!empty($html)) {
            $productDetails['product_name'] = $this->getProductName($html);
            $productDetails['average_rating'] = $this->getAverageRating($html);
            $productDetails['review_url'] = $downloadUrl;
            $productDetails['total_rating'] = intval($this->getTotalRating($html));
        }

        return $productDetails;
    }

    public function formatData($review, $index)
    {
        $review_title = Arr::get($review, 'review_title', '');
        $review_text = Arr::get($review, 'review_text', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $review_title = GlobalHelper::sanitizeForStorage($review_title);
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }

        return [
            'platform_name' => $this->platform,
            'source_id'     => $this->placeId,
            'review_id'     => Arr::get($review, 'review_id'),
            'reviewer_name' => Arr::get($review, 'reviewer_name'),
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
        if ($data && is_array($data)) {
            $placeId                          = $this->placeId;
            $businessInfo['place_id']         = $placeId;
            $businessInfo['name']             = Arr::get($data, 'product_name');
            $businessInfo['url']              = Arr::get($data, 'review_url');
            $businessInfo['address']          = '';
            $businessInfo['average_rating']   = Arr::get($data, 'average_rating');
            $businessInfo['total_rating']     = Arr::get($data, 'total_rating');
            $businessInfo['phone']            = '';
            $businessInfo['platform_name']    = $this->platform;
            $businessInfo['status']           = true;
            $infos[$placeId]                  =  $businessInfo;
        }
        return $infos;
    }

    public function getBusinessInfo()
    {
        return get_option('wpsr_reviews_amazon_business_info');
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

    //get business info
    public function getProductName($html)
    {
        $productName = '';
        if ($html->find('div.product-title', 0)) {
            if( $html->find('div.product-title', 0)->find('a.a-link-normal', 0)) {
                $productName = $html->find('div.product-title', 0)->find('a.a-link-normal', 0)->plaintext;
            }
        }

        if ($productName === '') {
            $productName = trim(strip_tags($html->find('span[id=productTitle]', 0)));
        }

        $productName = ReviewsHelper::removeSpecialChars($productName);
        return $productName;
    }

    public function getTotalRating($html)
    {
        $totalRating = null;
        if ($html->find('div.averageStarRatingNumerical', 0)) {
            $totalRating = $html->find('div.averageStarRatingNumerical', 0)->find('span', 0)->plaintext;
            if ($totalRating) {
                $totalRatingStringArray = explode(' ', trim($totalRating));
                $totalRating            = str_replace(',', '', $totalRatingStringArray[0]);
            }
        }
        return $totalRating;
    }

    public function getAverageRating($html)
    {
        //avg rating
        $avgRating = '';
        if($html->find('div.AverageCustomerReviews', 0)) {
            if ($html->find('div.AverageCustomerReviews', 0)->find('i.averageStarRating', 0)) {
                if($html->find('div.AverageCustomerReviews', 0)->find('i.averageStarRating', 0)->find('span', 0)) {
                    $avgRating = $html->find('div.AverageCustomerReviews', 0)->find('i.averageStarRating', 0)->find('span', 0)->plaintext;
                }
            }
            if ($html->find('div.AverageCustomerReviews', 0)->find('div.a-col-right', 0)) {
                if($html->find('div.AverageCustomerReviews', 0)->find('div.a-col-right', 0)->find('span', 0)) {
                    $avgRating = $html->find('div.AverageCustomerReviews', 0)->find('div.a-col-right', 0)->find('span[data-hook=rating-out-of-text]', 0)->plaintext;
                }
            }
        }
        $avgRating = $avgRating ? substr($avgRating, 0, 3) : 0;

        if($avgRating) {
            $avgRating = str_replace(',', '.', $avgRating);
        }
        return $avgRating;
    }

    //get review info
    public function getReviewerName($review)
    {
        //find reviewer name
        $reviewerName = '';
        if ($review->find('span.a-profile-name', 0)) {
            $reviewerName = $review->find('span.a-profile-name', 0)->plaintext;
        }
        return $reviewerName;
    }

    public function getReviewerUrl($review)
    {
        //find reviewer url
        $reviewerUrl = '';
        if ($review->find('a.a-profile', 0)) {
            $reviewerUrl = 'https://www.amazon.com' . $review->find('a.a-profile', 0)->href;
        }
        return $reviewerUrl;
    }

    public function getReviewText($review)
    {
        //find review text
        $reviewText = '';
        if ($review->find('span.review-text', 0)) {
            if($review->find('span.review-text', 0)->find('span', 0)) {
                $reviewText = $review->find('span.review-text', 0)->find('span', 0)->plaintext;
            }
        }
        return $reviewText;
    }

    public function getReviewTitle($review)
    {
        $reviewTitle = '';
        if($review->find('span.review-title')) {
            $reviewTitle = $review->find('span.review-title', 0)->plaintext;
        }

        if(empty($reviewTitle) && $review->find('a.review-title')) {
            $reviewTitle = $review->find('a.review-title', 0)->plaintext;
        }

        if(!empty($reviewTitle)) {
            $reviewTitle = trim($reviewTitle, " ");
        }

        $pattern = '/\d\.\d out of 5 stars/';
        $reviewTitle = preg_replace($pattern, '', $reviewTitle);

        return $reviewTitle;
    }

    public function getReviewRating($review)
    {
        //find review rating
        $reviewRating = '';
        if ($review->find('i.review-rating', 0)) {
            if($review->find('i.review-rating', 0)->find('span', 0)) {
                $reviewRating = $review->find('i.review-rating', 0)->find('span', 0)->plaintext;
            }
        }

        return $reviewRating;
    }

    public function getReviewId($review)
    {
        $reviewId = '';
    
        // Check if the 'id' attribute exists in the review div
        if (isset($review->id)) {
            $reviewId = $review->id;
        }
        
        return $reviewId;
    }

    public function getReviewerImage($review)
    {
        //find reviewer image
        $reviewerImage = '';
        if ($review->find('div.a-profile-avatar', 0)) {
            $reviewerImage = $review->find('div.a-profile-avatar', 0)->find('img', 0)->{'data-src'};
        }
        return $reviewerImage;
    }

    public function getReviewDate($review)
    {
        //find review date
        $reviewDate = '';
        if ($review->find('span.review-date', 0)) {
            if($review->find('span[data-hook=review-date]', 0)) {
                $reviewDate = $review->find('span[data-hook=review-date]', 0)->plaintext;
            }
        }

        $dateSubmitted = '';

        $reviewDate = explode(' ', rtrim($reviewDate));
        if(count($reviewDate) >= 3 ) {
            $rSize = count($reviewDate);
            $dateSubmitted .= $reviewDate[$rSize-3]. ' ';
            $dateSubmitted .= $reviewDate[$rSize-2]. ' ';
            $dateSubmitted .= $reviewDate[$rSize-1];
        }

        if(!empty($dateSubmitted)) {
            $dateSubmitted = ProHelper::unixTimeStamp($dateSubmitted);
            if(empty($dateSubmitted)) {
                return '';
            }
            $dateSubmitted = date("Y-m-d H:i:s", $dateSubmitted);
        }

        return $dateSubmitted;
    }

    public function clearVerificationConfigs($userId)
    {
        
    }

    protected function getUserAgent()
    {
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
        $userAgentArray[] = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36 OPR/49.0.2725.47";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/16.16299";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36 OPR/49.0.2725.64";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/62.0.3202.94 Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:56.0) Gecko/20100101 Firefox/56.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;  Trident/5.0)";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/63.0.3239.84 Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0;  Trident/5.0)";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0";
        $userAgentArray[] = "Mozilla/5.0 (iPad; CPU OS 11_1_2 like Mac OS X) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0 Mobile/15B202 Safari/604.1";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Safari/604.1.38";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; CrOS x86_64 9901.77.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.97 Safari/537.36";

        $userAgentKey = array_rand($userAgentArray);
        return $userAgentArray[$userAgentKey];
    }
}