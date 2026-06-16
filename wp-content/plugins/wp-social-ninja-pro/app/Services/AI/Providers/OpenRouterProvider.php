<?php

namespace WPSocialReviewsPro\App\Services\AI\Providers;

use WPSocialReviews\Framework\Support\Arr;
use Exception;

class OpenRouterProvider extends BaseProvider {

    public string $platform = 'openrouter';
    public function __construct($apiKey) {
        parent::__construct($apiKey, 'https://openrouter.ai/api/v1/');
    }

    public static function getResponseArray($choices){

        if(is_array($choices) 
            && count($choices) > 0 
            && Arr::has($choices, '0.text')
        ) {
            return Arr::get($choices, '0.text');
        }

        throw new \Exception('Invalid response data');
        
    }

    public function handleResponse($response) {
        // Check if the response is an error.
        if (Arr::has($response, 'error')) {
            $error = Arr::get($response, 'error');
            $code = Arr::get($error, 'code');
            $message = Arr::get($error, 'message');
            throw new \Exception("Error Code: $code, Message: $message");
        }

        // Map the usage data if it exists.
        $usageArray = Arr::get($response, 'usage');
        $usage = [
            'prompt_tokens' => Arr::get($usageArray, 'prompt_tokens'),
            'completion_tokens' => Arr::get($usageArray, 'completion_tokens'),
            'total_tokens' => Arr::get($usageArray, 'total_tokens')
        ];

        // Map the response data to ResponseData and return it.
        return [
            'id' => Arr::get($response, 'id'),
            'provider' => Arr::get($response, 'provider'),
            'model' => Arr::get($response, 'model'),
            'object' => Arr::get($response, 'object'),
            'created' => Arr::get($response, 'created'),
            'choices' => $this->getResponseArray(Arr::get($response, 'choices')),
            'usage' => $usage,
            'citations' => Arr::get($response, 'citations')
        ];
    }
}