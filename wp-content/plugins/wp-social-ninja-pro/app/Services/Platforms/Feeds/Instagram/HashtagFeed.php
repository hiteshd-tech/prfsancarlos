<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Feeds\Instagram;

use WPSocialReviews\App\Services\DataProtector;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\InstagramFeed;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\Common;
use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\SingleFeed;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

class HashtagFeed
{
    protected $cacheHandler;
    private $perPage = 30;
    protected $dataProtector;
    protected $errorManager;

    public function __construct()
    {
        $this->cacheHandler = new CacheHandler('instagram');
        $this->dataProtector = new DataProtector();
        if(class_exists('\WPSocialReviews\App\Services\Platforms\PlatformErrorManager')){
            $this->errorManager = new \WPSocialReviews\App\Services\Platforms\PlatformErrorManager('instagram');
        }
    }

    public function validHashTags($hashtags)
    {
        $hashtags = str_replace('#', '', trim($hashtags));
        $hashtags = str_replace(' ', '', $hashtags);

        return $hashtags;
    }

    /**
     * Merge multiple hashtag response
     *
     * @param $accountIds
     * @param $hashtags
     * @param $hashtagType
     *
     * @return array
     * @since 1.3.0
     *
     */
    public function getMultipleHashtagResponse($accountIds, $hashtags, $hashtagType)
    {
        if(!class_exists('\WPSocialReviews\App\Services\Platforms\PlatformErrorManager')){
            return [
                'error_message' => __('Please upgrade the wp social ninja base plugin to use this hashtag feeds.', 'wp-social-ninja-pro'),
                'feeds' => []
            ];
        }

        $hashtags = $this->validHashTags($hashtags);
        $hashtags = array_map('trim', explode(",", $hashtags));
        $error_message = '';

        $connectedAccounts      = (new Common())->findConnectedAccounts();
        $businessAccountCounter = 0;
        foreach ($accountIds as $index => $accountId) {
            $accountDetails = Arr::get($connectedAccounts, $accountId, '');
            $userName = Arr::get($connectedAccounts, $accountId.'.username');

            if ($accountDetails) {
                //if already it executes a business account response 
                if ($businessAccountCounter) {
                    break;
                }
                //if the selected account is from  personal account
                if ($accountDetails['api_type'] === 'personal') {
                    continue;
                } //else normally executes the business account response
                else {
                    if ($accountDetails['api_type'] === 'business') {
                        ++$businessAccountCounter;
                        $response   = array();
                        $hashtagIds = array();

                        $has_error = Arr::get($accountDetails, 'status') === 'error';
                        $has_error_code = Arr::get($accountDetails, 'error_code');

                        $errors = $this->errorManager->getErrors('instagram');
                        $has_error_hashtags = [];
                        if(isset($errors['hashtag'])){
                            $hashtag_errors = Arr::get($errors, 'hashtag', []);
                            if(!empty($hashtag_errors)){
                                $has_error_hashtags = array_map(function ($hashtag){
                                    return Arr::get($hashtag, 'hashtag', '');
                                }, $hashtag_errors);
                            }
                        }

                        foreach ($hashtags as $index => $hashtag) {
                            if(in_array($hashtag, $has_error_hashtags)){
                                $error_message = __('The Hashtag '.$hashtag.' requested by the user either cannot be seen due to missing permissions, is invalid or doesn\'t exist.', 'wp-social-ninja-pro');
                            } else {
                                if ($has_error_code && $has_error) {
                                    $hashtagCache = "hashtag_{$hashtag}";
                                    $error_message = Arr::get($accountDetails, 'error_message');
                                    $hashtagIds[] = $this->cacheHandler->getFeedCache($hashtagCache);
                                }
    
                                $response = $this->getHashtagId($hashtag, $accountDetails);
                                if(is_wp_error($response)){
                                    $error_message = $response->get_error_message();
                                    $this->errorManager->addError('hashtag', $response, $accountDetails);
                                } else {
                                    if ((new Common())->instagramError($response)) {
                                        $response['error']['hashtag'] = $hashtag;
                                        $this->errorManager->addError('hashtag', $response, $accountDetails);
                                        $error_message = Arr::get($response, 'error.error_user_title');
                                    } else {
                                        $this->errorManager->removeErrors('hashtag');
                                        $hashtagIds[] = $response;
                                    }
                                }
                            }
                        }

                        $response = array();
                        foreach ($hashtagIds as $index => $hashtagId) {
                            if (!empty($hashtagId)) {
                                if($has_error_code && $has_error) {
                                    $feedCacheName = "hashtag_feed_id_{$accountId}_hashtag_id_{$hashtagId}_type_{$hashtagType}";
                                    $error_message = Arr::get($accountDetails, 'error_message');
                                    $response[$index] = $this->cacheHandler->getFeedCache($feedCacheName);
                                    continue;
                                }
                                $response[$index] = $this->getHashtagFeed($hashtagId, $hashtagType,
                                    $accountDetails);

                                if(Arr::get($response, $index.'.'.$userName.'.error.message')){
                                    $error_message = Arr::get($response[$index], $userName.'.error.message');
                                }
                            }
                        }

                        //merge all hashtag feeds
                        if (count($response)) {
                            $allFeeds = array();
                            foreach ($response as $index => $feeds) {
                                if(is_array($feeds)) {
                                    $allFeeds = array_merge($allFeeds, $feeds);
                                }
                            }
                            return [
                                'error_message' => $error_message,
                                'feeds' => $allFeeds
                            ];
                        }

                        return [
                            'error_message' => $error_message,
                            'feeds' => $response
                        ];
                    }
                }
            }
        }

        if (!$businessAccountCounter) {
            $message = __('You need a business account to get hashtag feed!!', 'wp-social-ninja-pro');
            return array('error_message' => $message);
        }
    }

    /**
     * Build hashtag feed api URL
     *
     * @param $hashtagId
     * @param $hashtagType
     * @param $accountDetails
     *
     * @return string
     * @since 1.3.0
     *
     */
    public function getFeedApiUrl($hashtagId, $hashtagType, $accountDetails)
    {
        $access_token = $this->dataProtector->decrypt($accountDetails['access_token']) ? $this->dataProtector->decrypt($accountDetails['access_token']) : $accountDetails['access_token'];

        $fields = [
            'id',
            'caption',
            'like_count',
            'comments_count',
            'media_type',
            'media_product_type',
            'media_url',
            'permalink',
            'timestamp'
        ];

        $childrenFields = [
            'id',
            'media_url',
            'media_type',
            'permalink',
        ];
        $list           = implode(',', $childrenFields);
        $fields[]       = "children{{$list}}";
        $query          = [
            'fields'       => implode(',', $fields),
            'user_id'      => $accountDetails['user_id'],
            'access_token' => $access_token,
            'limit'        => apply_filters('wpsocialreviews/instagram_hashtag_feeds_api_limit', $this->perPage),
        ];

        return "https://graph.facebook.com/v21.0/{$hashtagId}/{$hashtagType}?" . http_build_query($query);
    }

    public function getNextPageUrlResponse($nextUrl, $response)
    {
        $posts   = $response;
        $limit   = apply_filters('wpsocialreviews/instagram_hashtag_feeds_limit', 100);
        $perPage = $this->perPage;
        $pages = (int)($limit/$perPage);

        $x = 1;
        while($x < $pages){
            $x++;
            $nextUrlResponse = (new Common())->makeRequest($nextUrl);
            $newData = Arr::get($nextUrlResponse, 'data', []);
            $oldData = Arr::get($response, 'data', []);
            $posts['data'] = array_merge($oldData, $newData);
        }

        return $posts;
    }
    /**
     * Get hashtag feed by hashtag id from cache or request
     *
     * @param $hashtagId
     * @param $hashtagType
     * @param $accountDetails
     *
     * @return mixed
     * @since 1.3.0
     *
     */
    public function getHashtagFeed($hashtagId, $hashtagType, $accountDetails)
    {
        $accountId     = $accountDetails['user_id'];
        $feedCacheName = "hashtag_feed_id_{$accountId}_hashtag_id_{$hashtagId}_type_{$hashtagType}";

        //if exists in cache then return it
	    $response = $this->cacheHandler->getFeedCache($feedCacheName);
        if (!$response) {
            $response = $this->makeRequestToGetNewData($feedCacheName, $hashtagId, $hashtagType, $accountDetails);
        }

        return $response;
    }

    public function makeRequestToGetNewData($feedCacheName, $hashtagId, $hashtagType, $accountDetails)
    {
        $userName = $accountDetails['username'];

        //if not in cache then make a request
        $api_url  = $this->getFeedApiUrl($hashtagId, $hashtagType, $accountDetails);
        $response = (new Common())->makeRequest($api_url);
        if(isset($response['paging'])){
            $nextUrl = Arr::get($response, 'paging.next');
            if($nextUrl){
                $response = $this->getNextPageUrlResponse($nextUrl, $response);
            }
        }

        if ((new Common())->instagramError($response)) {
            return array($userName => $response);
        }

        if (!(new Common())->instagramError($response)) {
            $response = Arr::get($response, 'data', []);
            if (!empty($response)) {
                foreach ($response as $key => $feed) {
                    $response[$key]['username'] = $userName;
                }
                
                // Then batch process all feeds that need media URL fixing
//                if (class_exists('\WPSocialReviews\App\Services\Platforms\Feeds\Instagram\SingleFeed')) {
//                    $response = (new \WPSocialReviews\App\Services\Platforms\Feeds\Instagram\SingleFeed())->batchFixMediaUrls($response);
//                }

                $this->cacheHandler->createCache($feedCacheName, $response);
            }
        } else {
            $response = $this->cacheHandler->getFeedCache($feedCacheName);
        }

        return $response;
    }

    /**
     * Build hashtag id api URL
     *
     * @param $hashtag
     * @param $account
     *
     * @return string
     * @since 1.3.0
     *
     */
    public function getHashtagIdApi($hashtag, $account)
    {
        $access_token = $this->dataProtector->decrypt($account['access_token']) ? $this->dataProtector->decrypt($account['access_token']) : $account['access_token'];

        $q = [
            'q'            => $hashtag,
            'user_id'      => $account['user_id'],
            'access_token' => $access_token,
            'limit'        => 1,
        ];

        return 'https://graph.facebook.com/ig_hashtag_search?' . http_build_query($q);
    }

    /**
     * Get hashtag node id of a specific tag
     *
     * @param $hashtag
     * @param $account
     *
     * @return string/null
     * @since 1.3.0
     *
     */
    public function getHashtagId($hashtag, $account)
    {
        $hashtagCache = "hashtag_{$hashtag}";
	    $hashtagId = $this->cacheHandler->getFeedCache($hashtagCache);
        if (!$hashtagId) {
            $api_url  = $this->getHashtagIdApi($hashtag, $account);
            $response = (new Common())->makeRequest($api_url);

            if ((new Common())->instagramError($response)) {
                return $response;
            }

            if (!(new Common())->instagramError($response)) {
                $data = Arr::get($response, 'data', []);
                if (count($data) === 0) {
                    return null;
                }
                $hashtag   = $data[0];
                $hashtagId = Arr::get($hashtag, 'id', null);
                if ($hashtagId) {
                    $this->cacheHandler->createCache($hashtagCache, $hashtagId);
                }
            } else {
                $hashtagId = $this->cacheHandler->getFeedCache($hashtagCache);
            }
        }

        return $hashtagId;
    }
}
