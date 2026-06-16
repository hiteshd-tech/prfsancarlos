<?php

namespace WPSocialReviewsPro\App\Services\CustomSources;

use WPSocialReviews\App\Models\Post;
use WPSocialReviews\App\Models\Review;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;

class CustomSourceService
{
    private const POST_TYPE = 'wpsr_custom_source';
    private const VALID_PLATFORMS_OPTION = 'wpsr_available_valid_platforms';

    private Post $postModel;
    private FluentFormsService $fluentFormsService;

    public function __construct()
    {
        $this->postModel = new Post();
        $this->fluentFormsService = new FluentFormsService();
    }

    /**
     * Get paginated custom sources with metadata
     */
    public function getSources(array $params)
    {
        $sources = $this->postModel->getPosts(
            static::POST_TYPE,
            $params['search'] ?? '',
            $params['filter'] ?? '',
            $params['per_page'] ?? 10,
            $params['page'] ?? 1
        )->toArray();

        $validPlatforms = [];

        if (isset($sources['data'])) {
            foreach ($sources['data'] as $index => $source) {
                $sourceId = Arr::get($source, 'ID');
                if ($sourceId) {
                    $enrichedSource = $this->enrichSourceData($source, $sourceId);
                    $sources['data'][$index] = $enrichedSource;
                    $validPlatforms[$enrichedSource['name']] = Arr::get($source, 'post_title', '');
                }
            }
        }

        return [
            'items' => $sources,
            'all_valid_platforms' => $validPlatforms,
            'total_items' => Arr::get($sources, 'total', 0),
        ];
    }

    /**
     * Create a new custom source
     */
    public function createSource(array $data)
    {
        $this->validateSourceData($data);

        // Handle Fluent Forms integration if needed
        $formData = $this->handleFluentFormsIntegration($data);

        // Create the post
        $sourceId = $this->postModel->createPost([
            'post_title' => $data['label'],
            'post_content' => $data['name'],
            'post_type' => static::POST_TYPE,
            'post_status' => 'publish'
        ]);

        $sources = get_post($sourceId)->to_array();
        $post_content = Arr::get($sources, 'post_content', '');
        $post_name = Arr::get($sources, 'post_name', $data['name']);
        $data['name'] = $post_content === 'fluent_forms' ? 'fluent_forms' : $post_name;

        // Set metadata
        $this->setSourceMetadata($sourceId, $data, $formData);

        // Update valid platforms
        $this->updateValidPlatforms($data['name'], $data['label']);

        // Update business info if form_id exists
        if (!empty($formData['form_id'])) {
            $this->updateBusinessInfo(
                $data['name'],
                $formData['form_id'],
                $data['label']
            );
        }

        return [
            'source_id' => $sourceId,
            'form_data' => $formData
        ];
    }

    /**
     * Delete a custom source and related data
     */
    public function deleteSource(int $sourceId)
    {
        $meta = $this->postModel->getConfig($sourceId);
        $sourceSettings = Arr::get($meta, 'source_settings', []);
        $sourceName = Arr::get($sourceSettings, 'name', '');
        $formId = Arr::get($sourceSettings, 'form_id', null);
        $actualSourceId = $formId ?: $sourceId;

        // Remove from valid platforms
        $this->removeFromValidPlatforms($sourceName);

        // Delete related reviews
        Review::where('platform_name', $sourceName)
            ->where('source_id', $actualSourceId)
            ->delete();

        // Delete the post
        $this->postModel->deletePost($sourceId);
    }

    /**
     * Get source settings and business info
     */
    public function getSourceSettings(int $sourceId)
    {
        $source = get_post($sourceId);

        if (!$source || $source->post_type !== static::POST_TYPE) {
            throw new \Exception(__('Custom source not found', 'wp-social-reviews'));
        }

        $meta = $this->postModel->getConfig($sourceId);
        $sourceSettings = Arr::get($meta, 'source_settings', []);
        $businessName = Arr::get($sourceSettings, 'name', '');
        $businessInfo = Helper::getConnectedBusinessesForAPlatform($businessName);

        return [
            'source' => $source,
            'settings' => $meta,
            'business_info' => $businessInfo
        ];
    }

    /**
     * Save source settings
     */
    public function saveSourceSettings(int $sourceId, array $settings)
    {
        $source = get_post($sourceId);

        if (!$source || $source->post_type !== static::POST_TYPE) {
            throw new \Exception(__('Custom source not found', 'wp-social-reviews'));
        }

        $meta = $this->postModel->getConfig($sourceId);
        $sourceSettings = Arr::get($meta, 'source_settings', []);
        $businessName = Arr::get($sourceSettings, 'name', '');

        // Handle Fluent Forms special case
        $actualSourceId = $sourceId;
        if ($businessName === 'fluent_forms') {
            $formId = Arr::get($sourceSettings, 'form_id', null);
            $actualSourceId = $formId ?: $sourceId;
        }

        $this->saveSettings($businessName, $actualSourceId, $settings);
    }

    /**
     * Get reviews for a source
     */
    public function getSourceReviews(int $sourceId)
    {
        $source = get_post($sourceId);

        if (!$source || $source->post_type !== static::POST_TYPE) {
            throw new \Exception(__('Custom source not found', 'wp-social-reviews'));
        }

        $sourceName = $source->post_name;

        if ($sourceName) {
            return $this->fetchReviews($sourceId, $sourceName);
        }

        return ['reviews' => [], 'count' => 0];
    }

    /**
     * Enrich source data with metadata and business info
     */
    private function enrichSourceData(array $source, int $sourceId)
    {
        $meta = $this->postModel->getConfig($sourceId);
        $sourceSettings = Arr::get($meta, 'source_settings', []);
        $type = Arr::get($sourceSettings, 'type', '');

        $sourceName = Arr::get($source, 'post_name', '');
        $sourceName = $type === 'fluent_forms' ? 'fluent_forms' : $sourceName;
        $sourceLabel = Arr::get($source, 'post_title', '');


        $businessInfos = get_option('wpsr_reviews_' . $sourceName . '_business_info', []);

        $formData = $this->extractFormData($sourceSettings, $sourceName);
        $reviews = $this->getReviewsCount($formData['form_id'] ?: $sourceId, $sourceName);

        $displayId = $formData['form_id'] ?: $sourceId;

        return [
            'id' => $sourceId,
            'form_id' => $formData['form_id'],
            'ff_form_edit_url' => $formData['form_edit_url'],
            'ff_integration_url' => $formData['integration_url'],
            'reviews_count' => Arr::get($reviews, 'count', 0),
            'name' => $sourceName,
            'type' => Arr::get($sourceSettings, 'type', 'custom'),
            'logo' => Arr::get($businessInfos, $displayId . '.logo', ''),
            'url' => Arr::get($sourceSettings, 'url', ''),
            'status' => Arr::get($sourceSettings, 'status', 'active'),
            'title' => Arr::get($businessInfos, $displayId . '.platform_label', $sourceLabel),
            'created_at' => Arr::get($source, 'post_date', null),
            'updated_at' => Arr::get($source, 'post_modified', null),
        ];
    }

    /**
     * Extract form-related data for Fluent Forms integration
     */
    private function extractFormData(array $sourceSettings, string $sourceName)
    {
        $formId = null;
        $integrationId = null;
        $formEditUrl = '';
        $integrationUrl = '';

        if ($sourceName === 'fluent_forms') {
            $formId = Arr::get($sourceSettings, 'form_id', null);
            $integrationId = Arr::get($sourceSettings, 'integration_id', null);
            $formEditUrl = admin_url('admin.php?page=fluent_forms&route=editor&form_id=' . $formId);

            if ($integrationId) {
                $integrationUrl = admin_url('admin.php?page=fluent_forms&form_id=' . $formId . '&route=settings&sub_route=form_settings#/all-integrations/' . $integrationId . '/wp_social_ninja');
            }
        }

        return [
            'form_id' => $formId,
            'integration_id' => $integrationId,
            'form_edit_url' => $formEditUrl,
            'integration_url' => $integrationUrl,
        ];
    }

    /**
     * Get reviews count for a source
     */
    private function getReviewsCount(int $sourceId, string $sourceName)
    {
        return $this->fetchReviews($sourceId, $sourceName);
    }

    /**
     * Fetch reviews from database
     */
    private function fetchReviews(int $sourceId, string $sourceName)
    {
        $reviews = Review::where('platform_name', $sourceName)
            ->where('source_id', $sourceId)
            ->orderBy('id', 'DESC')
            ->get()
            ->toArray();

        return [
            'reviews' => $reviews,
            'count' => count($reviews),
        ];
    }

    /**
     * Validate source creation data
     */
    private function validateSourceData(array $data)
    {
        if (empty($data['name']) || empty($data['label'])) {
            throw new \Exception(__('Source name and label are required', 'wp-social-reviews'));
        }
    }

    /**
     * Handle Fluent Forms integration logic
     */
    private function handleFluentFormsIntegration(array $data)
    {
        $formData = ['form_id' => null, 'integration_id' => null];

        if (!empty($data['form_id'])) {
            if ($data['is_manual_form'] ?? false) {
                $formData = $this->fluentFormsService->validateExistingForm($data['form_id']);
            } else {
                $formData = $this->fluentFormsService->createForm($data['form_id']);
            }
        }

        return $formData;
    }

    /**
     * Set source metadata
     */
    private function setSourceMetadata(int $sourceId, array $data, array $formData)
    {
        $defaultMeta = [
            'source_settings' => [
                'name' => $data['name'],
                'label' => $data['label'],
                'form_id' => $formData['form_id'],
                'integration_id' => $formData['integration_id'],
                'type' => $data['type'] ?? 'custom',
                'url' => '',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->postModel->updatePostMeta($sourceId, $defaultMeta, $data['name']);
    }

    /**
     * Update valid platforms option
     */
    private function updateValidPlatforms(string $sourceName, string $sourceLabel)
    {
        $validPlatforms = get_option(static::VALID_PLATFORMS_OPTION, []);

        if (!is_array($validPlatforms)) {
            $validPlatforms = [];
        }

        $validPlatforms[$sourceName] = $sourceLabel;
        update_option(static::VALID_PLATFORMS_OPTION, $validPlatforms);
    }

    /**
     * Remove source from valid platforms
     */
    private function removeFromValidPlatforms(string $sourceName)
    {
        $validPlatforms = get_option(static::VALID_PLATFORMS_OPTION, []);

        if (!empty($validPlatforms) && array_key_exists($sourceName, $validPlatforms)) {
            unset($validPlatforms[$sourceName]);
            update_option(static::VALID_PLATFORMS_OPTION, $validPlatforms);
        }
    }

    /**
     * Update business info for a source
     */
    public function updateBusinessInfo(string $sourceName, int $sourceId, string $sourceLabel)
    {
        $dataSource = ['source_id' => $sourceId];
        $businessInfo = Review::getInternalBusinessInfo($sourceName, $dataSource);
        $existingInfo = Arr::get($businessInfo, $sourceId, []);

        $updatedInfo = [
            'place_id' => $sourceId,
            'platform_name' => $sourceName,
            'logo' => '',
            'platform_label' => $sourceLabel,
            'privacy_policy_url' => '',
        ];

        $businessInfo[$sourceId] = array_merge($existingInfo, $updatedInfo);
        $this->saveBusinessInfo($sourceName, $businessInfo);
    }

    /**
     * Save settings for a business
     */
    public function saveSettings(string $businessName, int $sourceId, array $settings)
    {
        $dataSource = ['source_id' => $sourceId];
        $businessInfo = Review::getInternalBusinessInfo($businessName, $dataSource);
        $existingInfo = Arr::get($businessInfo, $sourceId, []);

        // Update URL from settings
        $existingInfo['url'] = Arr::get($settings, 'source_url', '');

        $updatedInfo = [
            'place_id' => $sourceId,
            'platform_name' => $businessName,
            'logo' => Arr::get($settings, 'logo', ''),
            'platform_label' => Arr::get($settings, 'source_label', ''),
            'privacy_policy_url' => Arr::get($settings, 'privacy_policy_url', ''),
        ];

        $businessInfo[$sourceId] = array_merge($existingInfo, $updatedInfo);
        $this->saveBusinessInfo($businessName, $businessInfo);
    }

    /**
     * Get business info for a platform
     */
    public function getBusinessInfo(string $platformName)
    {
        return get_option('wpsr_reviews_' . $platformName . '_business_info', []);
    }

    /**
     * Save business info to WordPress options
     */
    private function saveBusinessInfo(string $sourceName, array $businessInfo)
    {
        update_option('wpsr_reviews_' . $sourceName . '_business_info', $businessInfo);
    }
}