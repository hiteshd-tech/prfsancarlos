<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\Platforms\Reviews\BaseReview;
use WPSocialReviews\App\Services\Libs\SimpleDom\Helper;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper as ReviewsHelper;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Trustpilot Reviews
 * @since 1.0.0
 */
class Trustpilot extends BaseReview
{
    private $remoteBaseUrl = 'https://trustpilot.com';
    private $placeId = null;

    public function __construct()
    {
        parent::__construct(
            'trustpilot',
            'wpsr_reviews_trustpilot_settings',
            'wpsr_trustpilot_reviews_update'
        );

        if(class_exists('\WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler')){
            (new \WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler($this->platform))->registerHooks();
        }

        add_filter('wpsocialreviews/platforms', function ($platforms) {
            if (!in_array('', $platforms)) {
                $platforms[] = 'trustpilot';
            }
            return $platforms;
        });

        add_filter('wpsocialreviews/admin_app_vars', function ($vars) {
            $vars['brand_icons']['trustpilot'] = WPSOCIALREVIEWS_PRO_URL . 'assets/images/icon-trustpilot-small.png';
            $vars['tp_icon'] = WPSOCIALREVIEWS_PRO_URL . 'assets/images/icon-trustpilot-small.png';
            $vars['tp_slug'] = 'trustpilot';
            $vars['tp_title'] = 'Trustpilot';
            return $vars;
        });

        add_filter('wpsocialreviews/reviews_slugs', function ($items) {
            $items[] = 'trustpilot';
            return $items;
        });

        add_filter('wpsocialreviews/reviews_platforms', function ($platforms) {
            return array_merge(['trustpilot'], $platforms);
        });

        add_filter('wpsocialreviews/reviewer_name_based_providers', function ($items) {
            $items[] = 'trustpilot';
            return $items;
        });

        add_filter('wpsocialreviews/consumer_displayName_value_providers', function ($items) {
            $items[] = 'trustpilot';
            return $items;
        });

        add_filter('wpsocialreviews/elementor_keywords', function ($items) {
            $items[] = 'trustpilot';
            return $items;
        });

        add_filter('wpsocialreviews/platforms_info', function ($platforms) {
            $platforms[] = [
                'id'                 => 11,
                'platform'           => 'trustpilot',
                'platform_title'     => __('Trustpilot', 'wp-social-ninja-pro'),
                'image'              => WPSOCIALREVIEWS_PRO_URL . 'assets/images/icon-trustpilot-small.png',
                'apiKey'             => '',
                'sourceId'           => '',
                'message'            => '',
                'reviewsinfo'        => [],
                'sourceText'         => 'Place',
                'apiUrl'             => '#',
                'sourceUrl'          => '#',
                'exampleURL'         => 'https://www.trustpilot.com/review/craftcuts.com',
                'docs'               => 'https://wpsocialninja.com/docs/trustpilot-configuration-social-reviews-wp-social-ninja/',
                'privacy'            => 'https://wpsocialninja.com/privacy-policy/',
                'termsAndConditions' => 'https://wpsocialninja.com/terms-conditions/'
            ];

            return $platforms;
        });

        add_filter('wpsocialreviews/settings_review_platforms', function ($platforms) {
            $platforms[] = [
                'route' => 'tp-settings',
                'title' => 'TrustPilot Settings',
                'permission' => ['wpsn_reviews_platforms_settings'],
                'icon'  => WPSOCIALREVIEWS_PRO_URL . 'assets/images/icon-trustpilot-small.png',
            ];

            return $platforms;
        });
        
        add_filter('wpsocialreviews/trustpilot_reviews_platform_icon', function ($url) {
            $icon_name = 'icon-trustpilot.png';
            if(strpos($url, 'small') !== false ) { //found small icon
                $icon_name = 'icon-trustpilot-small.png';
            }
            return WPSOCIALREVIEWS_PRO_URL . 'assets/images/'.$icon_name;
        });

        add_filter('wpsocialreviews/platform_icons', function ($icons) {
            $icons['trustpilot'] = WPSOCIALREVIEWS_PRO_URL . 'assets/images/icon-trustpilot-small.png';
            return $icons;
        });
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
                $apiSettings = get_option('wpsr_trustpilot_global_settings');
                if (Arr::get($apiSettings, 'global_settings.auto_syncing') === 'true') {
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
            $reviews = array();
            $businessUnit = array();

            //fetch reviews from multiple pages
            $downloadUrl = strtok($downloadUrl, '?');
            $this->remoteBaseUrl = $downloadUrl;

            $url_array = explode('/', $downloadUrl);
            $businessName = end($url_array);
            $businessName = strtok($businessName, '?');
//            $businessName   = preg_replace('#^(http(s)?://)?w{3}\.#', '$1', $businessName);
            $this->placeId = $businessName;

            ini_set('memory_limit', '600M');
            $currUrl = $downloadUrl . '?languages=all';
            $isFoundNextUrl = true;
            for ($x = 1; $x <= 5 && $isFoundNextUrl; $x++) {
                $fileUrlContents = wp_remote_retrieve_body(wp_remote_get($currUrl));

                if (empty($fileUrlContents)) {
                    
                    throw new \Exception(
                        __('Can\'t fetch reviews due to slow network, please try again', 'wp-social-ninja-pro')
                    );
                }

                $html = Helper::str_get_html($fileUrlContents);
                $scripts = $html->find('script[id=__NEXT_DATA__]', 0);

                $scripts = $scripts->innertext;

                $data = json_decode($scripts, true);

                if (isset($data['props']['pageProps']['businessUnit']) && $x === 1) {
                    $businessUnit = $data['props']['pageProps']['businessUnit'];
                }

                if (isset($data['props']['pageProps']['reviews'])) {
                    $reviewsData = $data['props']['pageProps']['reviews'];
                    
                    foreach ($reviewsData as $review) {
                        $reviews[] = $review;
                    }
                }

                //find next button
                $isFoundNextUrl = false;
                if (!empty($businessUnit)) {
                    $totalReviews = Arr::get($businessUnit, 'numberOfReviews');
                    if ($totalReviews > ($x * 20)) {
                        $currUrl = $this->remoteBaseUrl . '?page=' . ($x + 1);
                        $isFoundNextUrl = true;
                    }
                }
            }

            if (!empty($reviews)) {
                if(!empty($this->placeId) && !empty(Arr::get($businessUnit, 'displayName'))) {
                    // api key and place id is dummy
                    $this->saveApiSettings([
                        'api_key' => '479711fa-64ba-47ce-b63b-9c2ba8d663f9',
                        'place_id' => $this->placeId,
                        'url_value' => $downloadUrl
                    ]);
                    $this->syncRemoteReviews($reviews);
                    $businessInfo = $this->saveBusinessInfo($businessUnit);

                    $totalFetchedReviews = count($reviews);
                    if ($totalFetchedReviews > 0) {
                        update_option('wpsr_reviews_trustpilot_business_info', $businessInfo, 'no');
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
        $settings = $this->getApiSettings();
        if (!isset($settings['data']) && sizeof($settings) > 0) {
            $platforms['trustpilot'] = __('Trustpilot', 'wp-social-ninja-pro');
        }
        return $platforms;
    }

    public function formatData($review, $index)
    {
        $strToTime = strtotime($review['dates']['publishedDate']);
        $review_title = Arr::get($review, 'title', '');
        $review_text = Arr::get($review, 'text', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $review_title = GlobalHelper::sanitizeForStorage($review_title);
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }

        return [
            'platform_name'   => $this->platform,
            'source_id'       => $this->placeId,
            'review_id'       => Arr::get($review, 'id'),
            'reviewer_name'   => Arr::get($review, 'consumer.displayName'),
            'review_title'    => $review_title,
            'reviewer_url'    => 'https://www.trustpilot.com/review/'.$this->placeId,
            'reviewer_img'    => Arr::get($review, 'consumer.imageUrl', ''),
            'reviewer_text'   => $review_text,
            'rating'          => Arr::get($review, 'rating'),
            'review_time'     => date('Y-m-d H:i:s', $strToTime),
            'review_approved' => 1,
            'updated_at'      => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s')
        ];
    }

    public function saveBusinessInfo($data = array())
    {
        $businessInfo = [];
        $infos = $this->getBusinessInfo();
        $infos = empty($infos) ? [] : $infos;
        if ($data && is_array($data) && !empty($this->placeId) && !empty(Arr::get($data, 'displayName', ''))) {
            $placeId = $this->placeId;
            $businessInfo['place_id'] = $placeId;
            $businessInfo['name'] = Arr::get($data, 'displayName');
            $businessInfo['url'] = str_replace('review', 'evaluate', $this->remoteBaseUrl);
            $businessInfo['address'] = '';
            $businessInfo['average_rating'] = Arr::get($data, 'trustScore');
            $businessInfo['total_rating'] = Arr::get($data, 'numberOfReviews');
            $businessInfo['phone'] = '';
            $businessInfo['platform_name'] = $this->platform;
            $businessInfo['status'] = true;
            $infos[$placeId] = $businessInfo;
        }
        return $infos;
    }

    public function getBusinessInfo()
    {
        return get_option('wpsr_reviews_trustpilot_business_info');
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

        if ($apiKey && $placeId && $businessUrl) {
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
