<?php

namespace WPSocialReviewsPro\App\Services\AI\Providers;

class DeepSeekProvider extends BaseProvider {

    public string $platform = 'deepseek';
    public function __construct($apiKey) {
        parent::__construct($apiKey, 'https://api.deepseek.com/');
    }

    public function sendRequest($endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Content-Type' => 'application/json',
        ];

        $response = wp_remote_post($url, [
            'body' => wp_json_encode($data),
            'headers' => $headers
        ]);

        return $this->handleResponse(json_decode($response, true));
    }

    public static function getResponseArray($choices){

        if(is_array($choices) 
            && count($choices) > 0 
            && Arr::has($choices[0], 'message.content')
        ) {
            return $choices[0]['message']['content'];
        }

        throw new Exception('Invalid response data');
        
    }
    
}