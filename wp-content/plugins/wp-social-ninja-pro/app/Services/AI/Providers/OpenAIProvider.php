<?php

namespace WPSocialReviewsPro\App\Services\AI\Providers;

use Exception;
use WPSocialReviews\Framework\Support\Arr;

class OpenAIProvider extends BaseProvider {

    public string $platform = 'openai';
    public function __construct($apiKey) {
        parent::__construct($apiKey, 'https://api.openai.com/v1/');
    }

    public static function getResponseArray($choices){

        if(is_array($choices) 
            && count($choices) > 0 
            && Arr::has($choices, '0.message.content')
        ) {
            return Arr::get($choices, '0.message.content');
        }

        throw new Exception('Invalid response data');
        
    }
}