<?php

namespace WPSocialReviewsPro\app\Services\AI;

use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper;
if (!defined('ABSPATH')) {
    exit;
}


class AIReviewSummarizerCron
{
    public function registerHooks(){
        add_action('wpsocialreviews/regenerate_ai_summary_cache', array($this, 'summarizeReviews'));
    }

    public function summarizeReviews(){
        $credentials = AIReviewSummarizerHelper::getCurrentAISummaryProviderCredentials();

        $platform = Arr::get($credentials, 'platform', '');
        $apiKey = Arr::get($credentials, 'apiKey', '');
        $model = Arr::get($credentials, 'model', '');

        if(empty($platform) || empty($apiKey) || empty($model)){
            return false;
        }
        $provider = AIReviewSummarizerHelper::getCurrentAIProvider($platform, $apiKey);
        $cacheHandler = new CacheHandler($provider->platform);

        $expiredCaches = $cacheHandler->getExpiredCaches();
        if(!$expiredCaches){
            // no expired cache found
            // if there are no cache generated yet, return false
            return false;
        }

        // expired cache found

        foreach($expiredCaches as $key => $cache){

            try {
                $templateId = AIReviewSummarizerHelper::getTemplateIdFromTransient($key);
            
                $formattedMeta = Helper::getTemplateMetaByTemplateId($templateId);
                $reviews = Helper::getReviewsDataByTemplateId($templateId, $formattedMeta);
    
                $filteredReviews = Arr::get($reviews, 'filtered_reviews');
                $airSummaryData = AIReviewSummarizerHelper::getAiSummary($filteredReviews, $templateId, $formattedMeta);

            } catch (\Throwable $th) {

            }
        }
    }
}