<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Feeds\Instagram;

use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\Common;
use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;

if (!defined('ABSPATH')) {
    exit;
}

class TaggedFeed
{
    protected $cacheHandler;

    public function __construct()
    {
        $this->cacheHandler = new CacheHandler('instagram');
    }

    /**
     * Get Tagged Feed
     *
     * @param array $accountIds
     *
     * @return array
     * @since 1.3.0
     */
    public function getMultipleTaggedResponse($accountIds)
    {
        $response          = array();
        $isBusinessAccount = false;
        $connectedAccounts = (new Common())->findConnectedAccounts();
        foreach ($accountIds as $index => $accountId) {
            $accountDetails = isset($connectedAccounts[$accountId]) ? $connectedAccounts[$accountId] : '';

            $resultWithComments = array();

            //if the selected account is from  personal account
            if (!empty($accountDetails) && $accountDetails['api_type'] === 'personal') {
                continue;
            } else {
                if (!empty($accountDetails) && $accountDetails['api_type'] === 'business') {
                    $isBusinessAccount = true;

                    $feedCacheName = 'tagged_feed_' . $accountId;
                    $taggedApi     = "https://graph.facebook.com/" . $accountDetails['user_id'] . "/tags?fields=id,username,timestamp,caption,like_count,comments_count,media_type,media_url,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}&limit=200&access_token=" . $accountDetails['access_token'];

	                $resultWithComments = $this->cacheHandler->getFeedCache($feedCacheName);
                    if (!$resultWithComments) {
                        $resultWithoutComments = (new Common())->expandWithoutComments($taggedApi);
                        if (!(new Common())->instagramError($resultWithoutComments)) {
                            $resultWithComments = (new Common())->expandWithComments($accountDetails,
                                $resultWithoutComments);
                            $this->cacheHandler->createCache($feedCacheName, $resultWithComments);
                        } else {
                            $resultWithComments = $this->cacheHandler->getFeedCache($feedCacheName, true);
                        }
                    }
                }
            }

            $response[] = $resultWithComments ? $resultWithComments : array();
        }

        if (!$isBusinessAccount) {
            $message = __('You need business account to get tagged feed!!', 'wp-social-ninja-pro');

            return array('error_message' => $message);
        }

        $accountFeed = array();

        foreach ($response as $index => $feeds) {
            $accountFeed = array_merge($accountFeed, $feeds);
        }

        return $accountFeed;
    }
}
