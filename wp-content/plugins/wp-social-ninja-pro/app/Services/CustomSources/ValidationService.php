<?php

namespace WPSocialReviewsPro\App\Services\CustomSources;

use WPSocialReviews\Framework\Support\Arr;

class ValidationService
{
    /**
     * Sanitize input fields based on rules
     */
    public function sanitize(array $fields)
    {
        $sanitizeRules = [
            'logo' => 'sanitize_text_field',
            'source_name' => 'sanitize_text_field',
            'source_url' => 'sanitize_url',
            'privacy_policy_url' => 'sanitize_url',
            'source_label' => 'sanitize_text_field',
            'name' => 'sanitize_text_field',
            'label' => 'sanitize_text_field',
            'type' => 'sanitize_text_field',
        ];
        $form_id = Arr::get($fields, 'form_id');
        $sanitizeRules['form_id'] = is_numeric($form_id) ? 'intval' : 'sanitize_text_field';


        $sanitized = [];
        if ($fields && is_array($fields)) {
            foreach ($fields as $dataKey => $dataItem) {
                $sanitizeFunc = Arr::get($sanitizeRules, $dataKey, 'sanitize_text_field');
                $sanitized[$dataKey] = $sanitizeFunc($dataItem);
            }
        }

        return $sanitized;
    }

    /**
     * Validate source creation data
     */
    public function validateSourceCreation(array $data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = __('Source name is required', 'wp-social-reviews');
        }

        if (empty($data['label'])) {
            $errors['label'] = __('Source label is required', 'wp-social-reviews');
        }

        // Validate form_id if provided
        if (!empty($data['form_id']) && !is_numeric($data['form_id']) && $data['is_manual_form'] === true) {
            $errors['form_id'] = __('Form ID must be a valid number', 'wp-social-reviews');
        }

        return $errors;
    }

    /**
     * Validate settings data
     */
    public function validateSettings(array $settings)
    {
        $errors = [];

        if (empty($settings['source_name'])) {
            $errors['source_name'] = __('Source name is required', 'wp-social-reviews');
        }

        // Validate URLs if provided
        if (!empty($settings['source_url']) && !filter_var($settings['source_url'], FILTER_VALIDATE_URL)) {
            $errors['source_url'] = __('Source URL must be a valid URL', 'wp-social-reviews');
        }

        if (!empty($settings['privacy_policy_url']) && !filter_var($settings['privacy_policy_url'], FILTER_VALIDATE_URL)) {
            $errors['privacy_policy_url'] = __('Privacy policy URL must be a valid URL', 'wp-social-reviews');
        }

        return $errors;
    }
}