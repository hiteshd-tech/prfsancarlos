<?php

namespace WPSocialReviewsPro\App\Services\AI;

use Exception;
use WPSocialReviews\Framework\Database\Orm\Collection;
use WPSocialReviewsPro\App\Services\AI\Providers\BaseProvider;
use WPSocialReviewsPro\App\Services\AI\Providers\DeepSeekProvider;
use WPSocialReviewsPro\App\Services\AI\Providers\OpenAIProvider;
use WPSocialReviewsPro\App\Services\AI\Providers\OpenRouterProvider;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\DataProtector;
use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;
use WPSocialReviews\App\Models\Review;

if (!defined('ABSPATH')) {
    exit;
}

class AIReviewSummarizerHelper
{

    public static function getTemplateIdFromTransient($transient)
    {
        return str_replace(AIReviewSummarizer::$reviewCacheTransientPrefix, '', $transient);
    }

    public static function summarizeAndCreateCache(AIReviewSummarizer $aiSummarizer, CacheHandler $cacheHandler, $filteredReviews, $formattedMeta, $transientName, $aiProvider){
        try {
            $data = [
                'reviews' => $filteredReviews->toArray(),
            ];
            
            $aiSummary = $aiSummarizer->summarizeReviews($data);
    
            $cacheData = [
                'formattedMeta' => static::getAppliedFilters($formattedMeta),
                'aiSummary' => $aiSummary,
            ];
    
            $cacheData = apply_filters('wpsocialreviews/ai_summary_cache_data', $cacheData);
    
            // cache expiration time
            $oneWeekTimeStamp =  apply_filters('wpsocialreviews/ai_summary_cache_expiration_time', WEEK_IN_SECONDS);
    
            $cacheHandler->createCacheWithExpirationTime($transientName, wp_json_encode($cacheData), $oneWeekTimeStamp);
            
            return static::formatAiSummary($aiProvider,$aiSummary, $filteredReviews);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getCurrentAIProvider($platform, $apiKey){

        switch ($platform) {
            case 'OpenAI':
                $aiProvider = new OpenAIProvider($apiKey);
                break;
            case 'Deepseek':
                $aiProvider = new DeepSeekProvider($apiKey);
                break;
            case 'openrouter':
            default:
                $aiProvider = new OpenRouterProvider($apiKey);
                break;
        }

        return $aiProvider;
    }

    public static function getCurrentAISummaryProviderCredentials(){
        $protector = new DataProtector();
        $settings = get_option('wpsr_global_settings', []);

        $platform = Arr::get($settings, 'global_settings.advance_settings.ai_platform', '');
        $apiKey = Arr::get($settings, 'global_settings.advance_settings.ai_api_key', '');

        $apiKey = $protector->decrypt($apiKey);
        $model = Arr::get($settings, 'global_settings.advance_settings.selected_model', '');

        return [
            'platform' => $platform,
            'apiKey' => $apiKey,
            'model' => $model,
        ];
    }
    public static function formatAiSummary( BaseProvider $aiProvider, $aiSummary, $filteredReviews){

        if (empty($aiSummary)) {
            return [];
        }

        $settings = get_option('wpsr_global_settings', []);
        $translations = Arr::get($settings, 'global_settings.translations', []);
        if(is_array($aiSummary) && Arr::has($aiSummary, 'choices')){
            $choices = Arr::get($aiSummary, 'choices');
        } else{
            $choices = $aiSummary->choices;
        }

        if ($choices) {
            $summary = json_decode($choices, true);
            
            $values =  [
                'category' => 'ai_summary',
                'created_at' => date('Y-m-d H:i:s'),
                'fields' => null,
                'id' => count($filteredReviews) + 1,
                'platform_name' => "ai",
                'rating' => 5,
                'recommendation_type' => null,
                'review_approved' => 1,
                'review_id' => rand(),
                'review_time' => date('Y-m-d H:i:s'),
                'review_title' => "AI Summary",
                'reviewer_img' => WPSOCIALREVIEWS_URL . 'assets/images/ai.png',
                'reviewer_name' => Arr::get($translations, 'ai_generated_summary') ?: __( 'AI-Generated Summary', 'wp-social-ninja-pro' ),
                'reviewer_text' => Arr::get($summary, 'summary_text'),
                'reviewer_url' => WPSOCIALREVIEWS_URL . 'assets/images/ai.png',
                'source_id' => "13447649560902657555",
                'reviewer_id' => null,
            ];

            $aiSummaryAsReviewObject = new Review($values);

            $aiSummaryAsReviewObject->summary_text = Arr::get($summary, 'summary_text');
            $aiSummaryAsReviewObject->summary_list = Arr::get($summary, 'summary_list');

            return $aiSummaryAsReviewObject;
        }

        return [];
    }

    public static function validateAISummaryCache($cache, $formattedMeta){
        $cache = json_decode($cache, true);
        $cacheFormattedMeta = Arr::get($cache, 'formattedMeta', null);
        $formattedMeta = static::getAppliedFilters($formattedMeta);
        if($cacheFormattedMeta === $formattedMeta){
            return true;
        }

        return false;
    }

    private static function getAppliedFilters($formattedMeta){
        $includeIds = Arr::get($formattedMeta, 'selectedIncList', []);
        $excludeIds = Arr::get($formattedMeta, 'selectedExcList', []);

        $starFilterVal = Arr::get($formattedMeta, 'starFilterVal', -1);
        $filterByTitle = Arr::get($formattedMeta, 'filterByTitle', 'all');
        $order         = Arr::get($formattedMeta, 'order', 'desc');
        $hideEmptyReviews = Arr::get($formattedMeta, 'hide_empty_reviews', false);
        $selectedBusinesses     = Arr::get($formattedMeta, 'selectedBusinesses', array());

		$categories = Arr::get($formattedMeta, 'selectedCategories', array());
        $totalReviews = Arr::get($formattedMeta, 'totalReviewsNumber');

        return [
            'includeIds' => $includeIds,
            'excludeIds' => $excludeIds,
            'starFilterVal' => $starFilterVal,
            'filterByTitle' => $filterByTitle,
            'order' => $order,
            'hideEmptyReviews' => $hideEmptyReviews,
            'selectedBusinesses' => $selectedBusinesses,
            'categories' => $categories,
            'totalReviews' => $totalReviews,
        ];
    }

    public Static function formatReviewsForAIProvider($reviews){
        $reviewParameterForAIProvider = is_array($reviews) ? array_map(function($review) {
            return [
                'text'          => wp_strip_all_tags($review['content'] ?? ''),
                'rating'        => $review['rating'] ?? '',
                'review_time'   => $review['review_time'] ?? '',
                'platform_name' => $review['platform_name'] ?? '',
            ];
        }, $reviews) : [];

        return apply_filters('wpsocialreviews/ai_summarizer_reviews_parameter', $reviewParameterForAIProvider);
    }

    public static function responseFormat(){

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'summary',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'summary_text' => [
                            'type' => 'string',
                            'description' => 'A concise paragraph summarizing the main pointscan contain more then 12 words and less then 50 words.',
                        ],
                        'summary_list' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                            'description' => 'A list of 3 bullet points highlighting key aspects. Ensure the summary captures both positive and negative points while maintaining neutrality. Each point should not contain more then 12 words.',
                        ],
                    ],
                    'required' => ['summary_text', 'summary_list'],
                    'additionalProperties' => false,
                ],
            ],
        ];

    }

    public static function isAIModelAndApikeySet()
    {
        // Get settings directly from the database to avoid circular dependency
        $settings = get_option('wpsr_global_settings', []);
        
        // Check if all required settings are configured
        $platform = Arr::get($settings, 'global_settings.advance_settings.ai_platform', '');
        $apiKey = Arr::get($settings, 'global_settings.advance_settings.ai_api_key', '');
        $selectedModel = Arr::get($settings, 'global_settings.advance_settings.selected_model', '');

        // All three are required for AI to work properly
        return !empty($platform) && !empty($apiKey) && !empty($selectedModel);
    }

    public static function addAiSummaryToReviewArray(Collection $reviews, $templateId, $formattedMeta, $isFirstRound = false, $forceRegenerateSummary = false){
        if(!is_numeric($templateId)){
            throw new Exception(__('Invalid template ID', 'wp-social-ninja-pro'), 1);
        }

        try {
            $aiSummary = static::getAIsummary($reviews, $templateId, $formattedMeta, $isFirstRound, $forceRegenerateSummary);

            if(!empty($aiSummary)){
                try {
                    $reviews = $reviews->prepend($aiSummary);
                    return [
                        'reviews' => $reviews,
                        'summary_added' => true
                    ];
                } catch (\Throwable $th) {
                    throw new \Exception($th->getMessage(), 1);
                }
            }
            return [
                'reviews' => $reviews,
                'summary_added' => false
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getAIsummary($filteredReviews, $templateId, $formattedMeta, $isFirstRound = false, $forceRegenerateSummary = false){
        $credentials = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::getCurrentAISummaryProviderCredentials();

        $platform = Arr::get($credentials, 'platform', '');
        $apiKey = Arr::get($credentials, 'apiKey', '');
        $model = Arr::get($credentials, 'model', '');

        $summaryTransientCachePrefix = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizer::$reviewCacheTransientPrefix;
        $aiSummary = [];

        if (empty($apiKey) || empty($model)) {
            throw new Exception(__('Please provide valid API Key and Model', 'wp-social-ninja-pro'), 1);
        }
        try {
            $aiProvider = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::getCurrentAIProvider($platform, $apiKey);

            $aiSummarizer = new \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizer(
                $aiProvider,
                $model,
            );

            $transientName = $summaryTransientCachePrefix . $templateId;

            $cacheHandler = new CacheHandler($aiProvider->platform);

            $expired = $cacheHandler->getExpiredCacheByName($transientName);
            $cachedAiSummary = $cacheHandler->getFeedCache($transientName);
            $cacheIsValid = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::validateAISummaryCache($cachedAiSummary, $formattedMeta);

            if ($isFirstRound && count($expired) === 0 && !$cacheIsValid) {
                // is excuted only when the first round of reviews are fetched
                // there is no expired cache or unexpired cache
                return [];
            }

            if ($forceRegenerateSummary || (!$isFirstRound && (count($expired) > 0 || !$cacheIsValid))) {
                // Clear the cache if expired or invalid
                $cacheHandler->clearCacheByName($transientName);
                // Summarize and create cache
                try {
                    return \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::summarizeAndCreateCache(
                        $aiSummarizer,
                        $cacheHandler,
                        $filteredReviews,
                        $formattedMeta,
                        $transientName,
                        $aiProvider
                    );
                } catch (Exception $e) {
                    throw $e;
                }

            } elseif ($isFirstRound && count($expired) > 0) {
                // Do not return anything if it's the first round and there are expired caches
                return [];
            } else {
                // Use the valid cached summary
                $cachedAiSummary = json_decode($cachedAiSummary, true);
                $aiSummary = Arr::get($cachedAiSummary, 'aiSummary', []);
                return \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::formatAiSummary($aiProvider, $aiSummary, $filteredReviews);
            }

        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function shouldIncludeAISummary($filteredReviews, $templateId, $formattedMeta, $isFirstRound = false, $forceRegenerateSummary = false){
        $AISummaryEnabled= Arr::get($formattedMeta, 'ai_summary.enabled', false);

        $aiSummaryError = '';
        $summaryAdded = false;
        $infos = [];
        $needsImmediateUpdate = $isFirstRound && $AISummaryEnabled === 'true';
        $proAvailable = defined('WPSOCIALREVIEWS_PRO');

        // Check if AI is globally enabled and properly configured
        $isAIConfigured = static::isAIModelAndApikeySet();
        
        // Also check if AI summarizer is globally enabled
        $settings = get_option('wpsr_global_settings', []);
        $isGloballyEnabled = Arr::get($settings, 'global_settings.advance_settings.ai_review_summarizer_enabled', 'false') === 'true';

        if ($proAvailable && $filteredReviews instanceof Collection && $AISummaryEnabled !== 'false' && $isAIConfigured && $isGloballyEnabled) {
            try {
                $aiSummaryData = static::addAiSummaryToReviewArray(
                    $filteredReviews,
                    $templateId,
                    $formattedMeta,
                    $isFirstRound,
                    $forceRegenerateSummary
                );

                $filteredReviews = Arr::get($aiSummaryData, 'reviews');
                $summaryAdded = Arr::get($aiSummaryData, 'summary_added', false);

                $needsImmediateUpdate = $needsImmediateUpdate && !$summaryAdded;

                if($needsImmediateUpdate){
                    $infos[] = [
                        'title' => __("AI Summary Generation in progress.", 'wp-social-ninja-pro'),
                        'description' => __("Please wait while we generate AI summary for your reviews.", 'wp-social-ninja-pro'),
                    ];
                }
            } catch (\Throwable $th) {
                $aiSummaryError = $th->getMessage();
            }

            return [
                'filtered_reviews' => $filteredReviews,
                'infos' => $infos,
                'aiSummaryError' => $aiSummaryError,
                'summaryAdded' => $summaryAdded,
                'needsImmediateUpdate' => $needsImmediateUpdate,
            ];
        }

        return [
            'filtered_reviews' => $filteredReviews,
            'infos' => [],
            'aiSummaryError' => null,
            'summaryAdded' => false,
            'needsImmediateUpdate' => false,
        ];
    }

}