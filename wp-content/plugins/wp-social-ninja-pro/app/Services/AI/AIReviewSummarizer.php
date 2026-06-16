<?php

namespace WPSocialReviewsPro\App\Services\AI;

use WPSocialReviewsPro\App\Services\AI\Providers\BaseProvider;
use WPSocialReviewsPro\App\Services\AI\Providers\DeepSeekProvider;
use WPSocialReviewsPro\App\Services\AI\Providers\OpenAIProvider;
use WPSocialReviewsPro\App\Services\AI\Providers\OpenRouterProvider;

if (!defined('ABSPATH')) {
    exit;
}

class AIReviewSummarizer
{
    public BaseProvider $provider;
    public string $model;
    public string $basePromt  = 'Summarize the following reviews into key takeaways that highlight the most positive and impactful aspects of the customer experience. Ensure each takeaway is concise, engaging, and relevant to potential customers. Do not include ratings, missing details, or general analysis—only specific insights from the reviews. You should not include any personal information or any information that can be used to identify the reviewer. Dont make more then 3 points and each point should not contain more the 12 words. But the summary_text can contain more then 12 words. The summary should be in English.';
    public int $maxTokens;

    public static string $reviewCacheTransientPrefix = 'wpsr_reviews_ai_summary_';

    public function __construct(BaseProvider $provider, string $model, int $maxTokens = 2048)
    {
        $this->provider = $provider;
        $this->model = $model;
        $this->maxTokens = $maxTokens;
    }

    public function summarizeReviews($data)
    {
        if($this->provider instanceof OpenAIProvider) {
            $chatData = $this->openAIParameters($data);
        } else if($this->provider instanceof DeepSeekProvider) {
            $chatData = $this->openAIParameters($data);
        } else if($this->provider instanceof OpenRouterProvider) {
            $chatData = $this->openRouterParameters($data);
        } else {
            throw new \Exception('Invalid provider');
        }

        try{
            $response = $this->provider->sendRequest('chat/completions', $chatData);    
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function openAIParameters($data = [])
    {
        $reviews = $data['reviews'];

        $basePrompt = apply_filters('wpsocialreviews/openai_summarizer_prompt', $this->basePromt);
        
        $reviewParameterForAIProvider = AIReviewSummarizerHelper::formatReviewsForAIProvider($reviews);

        $messages = apply_filters('wpsocialreviews/openai_prompt', [
            [
                'role' => 'developer',
                'content' => wp_json_encode($reviewParameterForAIProvider)
            ],
            [
                'role' => 'developer',
                'content' => $basePrompt
            ]
        ]);

        $chatData = [
            'messages' => $messages,
            'model' => $this->model,
            'stop' => ['</s>'],
            'response_format' => AIReviewSummarizerHelper::responseFormat(),
        ];

        return $chatData;
    }

    public function openRouterParameters($data)
    {   
        $reviews = $data['reviews'];
        $reviewParameterForAIProvider = AIReviewSummarizerHelper::formatReviewsForAIProvider($reviews);

        $basePrompt = apply_filters('wpsocialreviews/openrouter_summarizer_prompt', $this->basePromt);

        $prompt = 'Knowledgebase: ' . wp_json_encode($reviewParameterForAIProvider). '. '. $basePrompt;

        $chatData = [
            'prompt' => $prompt,
            'model' => $this->model,
            'response_format' => AIReviewSummarizerHelper::responseFormat(),
            'stop' => ['</s>'],
            'max_tokens' => $this->maxTokens,
        ];

        return $chatData;
    }

    public function deepseekParameters()
    {
        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens
        ];
    }
}