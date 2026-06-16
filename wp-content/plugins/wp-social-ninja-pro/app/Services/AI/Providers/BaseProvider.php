<?php

namespace WPSocialReviewsPro\App\Services\AI\Providers;

use WPSocialReviews\Framework\Support\Arr;
use Exception;
if (!defined('ABSPATH')) {
    exit;
}

abstract class BaseProvider
{
    protected $apiKey;
    protected $baseUrl;

    public function getApiKey() {
        return $this->apiKey;
    }

    public function getBaseUrl() {
        return $this->baseUrl;
    }

    public function __construct($apiKey, $baseUrl) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function sendRequest($endpoint, $data) {


        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Content-Type' => 'application/json',
        ];

        $timeOut = apply_filters('wpsocialreviews/openai_timeout', 60);

        $response = wp_remote_post($url, [
            'body' => wp_json_encode($data),
            'headers' => $headers,
            'timeout' => $timeOut
        ]);

        $code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            return new Exception($message, $code);
        }

        if(in_array($code, static::errorCodes())){
            return $this->handleError($code);
        }

        $body = $this->jsonDecode($response);
        return $this->handleResponse($body);
    }

    abstract public static function getResponseArray($choices);
    public function handleResponse($response) {
        // Check if the response is an error.
        if (Arr::has($response, 'error')) {
            return $this->handleError($response);
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

    protected static function errorCodes() {
        return [
            400,
            401,
            402,
            403,
            408,
            429,
            500,
            502,
            503
        ];
    }

    /**
     * @throws \Exception
     */
    protected function handleError($code) {
        error_log($code);
        switch ($code) {
            case 400:
                throw new \Exception("400: Bad Request (invalid or missing params, CORS).");
            case 401:
                throw new \Exception("401: Invalid credentials (OAuth session expired, disabled/invalid API key).");
            case 402:
                throw new \Exception("402: Your account or API key has insufficient credits. Add more credits and retry the request.");
            case 403:
                throw new \Exception("403: Your chosen model requires moderation and your input was flagged.");
            case 408:
                throw new \Exception("408: Your request timed out.");
            case 429:
                throw new \Exception("429: You are being rate limited.");
            case 500:
                throw new \Exception("500: The server encountered an error.");
            case 502:
                throw new \Exception("502: Your chosen model is down or we received an invalid response from it.");
            case 503:
                throw new \Exception("503: There is no available model provider that meets your routing requirements.");
            default:
                throw new \Exception("Error Code: $code");
        }
    }

    public function jsonDecode($response = null)
    {
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}

