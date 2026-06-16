<?php

namespace WPSocialReviewsPro\App\Services;

use WPSocialReviews\App\Services\Platforms\PlatformManager;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Models\Review;
use WPSocialReviews\App\Models\Post;
use League\Csv\Writer;
use League\Csv\Reader;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\PermissionManager;
use WPSocialReviews\App\Services\DataProtector;
use WPSocialReviews\App\Services\Platforms\Chats\SocialChat;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviewsPro\App\Services\Helper as ProHelper;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\JudgeMeService;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooCommerce;

class ImportExportHandler
{
    // Constants for better maintainability
    private const BATCH_SIZE = 3000;
    private const HTTP_STATUS_UNPROCESSABLE_ENTITY = 423;
    private const HTTP_STATUS_FORBIDDEN = 403;
    private const HTTP_STATUS_OK = 200;
    

    private const VALID_MIME_TYPES = [
        'text/csv',
        'application/csv', 
        'application/json',
        'application/octet-stream'
    ];
    



    protected $protector;
    private $platformManager;
    private $judgeMeService;

    public function __construct()
    {
        $this->protector = new DataProtector();
        $this->platformManager = new PlatformManager();
        $this->judgeMeService = new JudgeMeService();

        // Register the WordPress action for processing Judge.me images
        add_action('wpsr_process_judgeme_images', array($this->judgeMeService, 'processReviewImages'));
        add_action('wpsr_process_single_judgeme_image', array($this->judgeMeService, 'processSingleReviewImages'));
    }

    private function getTableHeaders()
    {
        $commonHeaders = ['platform_name', 'review_title', 'reviewer_name', 'reviewer_url', 'reviewer_img', 'reviewer_text', 'review_time', 'rating', 'created_at', 'updated_at'];
        $testimonialSpecificHeaders = ['category', 'author_company', 'author_position', 'author_website_logo', 'author_website_url'];
        $judgeMeHeaders = ['title', 'body', 'rating', 'review_date', 'source', 'curated', 'reviewer_name', 'reviewer_email', 'product_id', 'product_handle', 'reply', 'reply_date', 'picture_urls', 'ip_address', 'location'];

        return [
            'custom' => array_merge(['source_id'], $commonHeaders),
            'custom_sources' => array_merge(['source_id'], $commonHeaders),
            'testimonial' => array_merge($commonHeaders, $testimonialSpecificHeaders),
            'judge-me' => $judgeMeHeaders
        ];
    }

    public function includeAutoloadFile()
    {
        require_once WPSOCIALREVIEWS_PRO_DIR . 'app/Services/Libs/CSV/autoload.php';
    }

    private function isValidCsvFile($fileType)
    {
        return in_array($fileType, self::VALID_MIME_TYPES);
    }

    private function sanitizeData(&$data)
    {
        foreach ($data as $datumKey => $datum) {
            if (is_array($datum) && !empty($datum)) {
                foreach ($datum as $key => $value) {
                    if (is_scalar($value)) { // Check if the value is scalar (string, int, float, etc.)
                        $data[$datumKey][$key] = ProHelper::sanitizeForCSV($value);
                    }
                }
            }
        }
    }

    private function writeCsv($data, $header, $fileName)
    {
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $writer->setDelimiter(",");
        $writer->setNewline("\r\n");
        $writer->insertOne($header);
        $writer->insertAll($data);
        $writer->output(sanitize_file_name($fileName));
        die();
    }

    private function sendJSONResponse($data, $platformName)
    {
        $fileName = 'wpsn-' . $platformName . '-template-export-' . date('Y-m-d-H-i-s') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo json_encode($data);
        die();
    }

    private function generateFileName($prefix)
    {
        return $prefix . '-' . date('Y-m-d-H-i-s') . '.csv';
    }

    public function updatePlatformOptions($platform, $data)
    {
        update_option('wpsr_' . $platform . '_verification_configs', $data['verification_configs']);
    }

    private function fetchCustomData($headers, $platformName = 'custom', $sourceId = null)
    {
        $query = Review::select($headers)
            ->where('platform_name', $platformName);

        // Filter by sourceId if provided
        if ($sourceId) {
            $query->where('source_id', $sourceId);
        }

        return $query->get()->toArray();
    }

    private function isValidPlatformType($type)
    {
        // Get valid platforms from the system
        $validPlatforms = Helper::validPlatforms();
        $validPlatformKeys = array_keys($validPlatforms);

        return in_array($type, $validPlatformKeys);
    }

    private function fetchTestimonialData($headers)
    {
        $queryColumns = array_merge($headers, ['category', 'fields']);
        $testimonials = Review::select($queryColumns)
            ->where('platform_name', 'testimonial')
            ->get()
            ->toArray();

        return array_map([$this, 'mapTestimonialData'], $testimonials);
    }

    private function mapTestimonialData($testimonial)
    {
        $fields = Arr::get($testimonial, 'fields');
        return [
            'platform_name' => Arr::get($testimonial, 'platform_name', ''),
            'review_title' => Arr::get($testimonial, 'review_title', ''),
            'reviewer_name' => Arr::get($testimonial, 'reviewer_name', ''),
            'reviewer_url' => Arr::get($testimonial, 'reviewer_url', ''),
            'reviewer_img' => Arr::get($testimonial, 'reviewer_img', ''),
            'reviewer_text' => Arr::get($testimonial, 'reviewer_text', ''),
            'review_time' => Arr::get($testimonial, 'review_time'),
            'rating' => Arr::get($testimonial, 'rating'),
            'created_at' => Arr::get($testimonial, 'created_at', null),
            'updated_at' => Arr::get($testimonial, 'updated_at', null),
            'category' => Arr::get($testimonial, 'category', ''),
            'author_company' => Arr::get($fields, 'author_company', ''),
            'author_position' => Arr::get($fields, 'author_position', ''),
            'author_website_logo' => Arr::get($fields, 'author_website_logo', ''),
            'author_website_url' => Arr::get($fields, 'author_website_url', ''),
        ];
    }

    private function addTestimonialColumns($itemTemp)
    {
        $extraColumns = [
            'author_company' => $itemTemp['author_company'] ?? '',
            'author_position' => $itemTemp['author_position'] ?? '',
            'author_website_logo' => $itemTemp['author_website_logo'] ?? '',
            'author_website_url' => $itemTemp['author_website_url'] ?? '',
        ];

        $itemTemp['fields'] = json_encode($extraColumns);
        unset($itemTemp['author_company'], $itemTemp['author_position'], $itemTemp['author_website_logo'], $itemTemp['author_website_url']);

        return $itemTemp;
    }

    private function mapItemToHeader($item, $csvHeader, $headerCount)
    {
        if ($headerCount == count($item)) {
            return array_combine($csvHeader, $item);
        }

        return array_combine(
            $csvHeader,
            array_merge(
                array_intersect_key($item, array_fill_keys(array_values($csvHeader), null)),
                array_fill_keys(array_diff(array_values($csvHeader), array_keys($item)), null)
            )
        );
    }

    public function exportData()
    {
        if (!PermissionManager::currentUserCan('wpsn_full_access')) {
            return false;
        }

        $this->includeAutoloadFile();

        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $templateID = isset($_GET['templateID']) ? sanitize_text_field($_GET['templateID']) : null;
        $platformName = isset($_GET['platformName']) ? sanitize_text_field($_GET['platformName']) : '';
        $sourceId = isset($_GET['sourceId']) ? intval($_GET['sourceId']) : null;
        $tableHeaders = $this->getTableHeaders();
        

        $type = ($type == 'notifications') ? 'template' : $type;

        // Determine which headers to use
        $headersToUse = null;

        switch ($type) {
            case 'custom':
                $data = $this->fetchCustomData($tableHeaders['custom'], 'custom', $sourceId);
                $fileName = $this->generateFileName('wpsn-export-reviews');
                $headersToUse = $tableHeaders['custom'];
                break;
            case 'testimonial':
                $data = $this->fetchTestimonialData($tableHeaders['custom']);
                $fileName = $this->generateFileName('wpsn-export-testimonials');
                $headersToUse = $tableHeaders['testimonial'];
                break;
            case 'template':
                $this->handleTemplateExport($templateID, $platformName);
                return;
            case 'chat-widget':
                $this->handleChatWidgetTemplateExport($templateID);
                return;
            default:
                // Handle dynamic platform types (fluent_forms, woocommerce, etc.)
                if ($this->isValidPlatformType($type)) {
                    $data = $this->fetchCustomData($tableHeaders['custom_sources'], $type, $sourceId);
                    $fileName = $this->generateFileName('wpsn-export-' . $type . '-reviews');
                    $headersToUse = $tableHeaders['custom_sources']; // Use custom headers for all dynamic platforms
                } else {
                    return false;
                }
                break;
        }

        if (empty($data)) {
            wp_redirect(admin_url('admin.php?data=empty&page=wpsocialninja.php') . '#/tools/export');
            exit;
        }

        $this->sanitizeData($data);
        $this->writeCsv($data, $headersToUse, $fileName);
    }

    private function handleChatWidgetTemplateExport($templateID)
    {
        if (!$templateID) {
            return false;
        }

        $metaData = (new SocialChat())->processMetadata($templateID);
        $postData = $this->formatPostData($templateID);

        $data = [
            'post_meta' => $metaData,
            'post_data' => $postData
        ];

        $this->sanitizeData($data);

        $this->sendJSONResponse($data, 'chat-widget');
    }

    private function handleTemplateExport($templateID, $platformNames)
    {
        if (!$templateID) {
            return false;
        }
        $post = new Post();
        $metaData = $post->getConfig($templateID);
        $styleData = $post->getConfig($templateID, '_wpsr_template_styles_config');

        $platforms = is_array($platformNames) ? $platformNames : explode(',', $platformNames);
        $exportData = [];

        foreach ($platforms as $platformName) {
            $platformName = trim($platformName);

            $connectedConfigs = $this->platformManager->getConnectedSourcesConfigs($platformName);
            $selectedAccounts = $this->platformManager->getSelectedFeedAccounts($platformName, $metaData);
            $filteredVerificationConfigs = $this->platformManager->getFeedVerificationConfigsBySourceId($platformName, $connectedConfigs, $selectedAccounts);

            $data = [
                'platform_name' => $platformName,
                'connected_sources_config' => $filteredVerificationConfigs,
                'verification_configs' => $this->getVerificationConfigsIfNeeded($platformName),
                'platform_global_settings' => get_option('wpsr_' . $platformName . '_global_settings', []),
                'post_meta' => $metaData,
                'post_data' => $this->formatPostData($templateID),
                'post_style_meta' => $styleData,
            ];

            if (!in_array($platformName, $this->platformManager->feedPlatforms())) {
                $data['business_info'] = get_option('wpsr_reviews_' . $platformName . '_business_info');
            }

            if ($platformName === 'google') {
                $data['location_lists'] = get_option('wpsr_reviews_google_locations_list');
            }

            $this->sanitizeData($data);
            $exportData[] = $data;
        }

        $this->sendJSONResponse($exportData, $platformNames);
    }

    public function importData()
    {
        if (!PermissionManager::currentUserCan('wpsn_full_access')) {
            return false;
        }

        $this->includeAutoloadFile();
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'custom';
        $file = $_FILES['file'] ?? null;

        // Get target source ID for import
        $targetSourceId = isset($_POST['source_id']) ? intval($_POST['source_id']) : null;

        if (!$this->isValidCsvFile($file['type'])) {
            wp_send_json_error(
                ['message' => __('Please upload a valid CSV or JSON file.', 'wp-social-ninja-pro')],
                self::HTTP_STATUS_UNPROCESSABLE_ENTITY
            );
        }

        $data = $this->handleFileUpload($file, $type);
        if ($type === 'template') {
            $this->processTemplateImport($data);
        } elseif ($type === 'chat-widget') {
            $this->processChatWidgetTemplateImport($data);
        } elseif ($type === 'notifications') {
            $this->processNotificatioImport($data);
        } elseif ($type === 'judge-me') {
            $this->processJudgeMeImportWithService($data);
        } else {
            $this->processCSVImport($data, $type, $targetSourceId);
        }
    }

    private function handleFileUpload($file, $type)
    {
        $tmpName = sanitize_text_field($file['tmp_name']);
        $fileContents = file_get_contents($tmpName);

        if ($type === 'template' || $type === 'chat-widget' || $type === 'notifications') {
            $jsonData = json_decode($fileContents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => __('Invalid JSON format.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
            }
            return $jsonData;
        }

        return Reader::createFromString($fileContents)->fetchAll();
    }

    private function processChatWidgetTemplateImport($data)
    {
        if (empty($data)) {
            wp_send_json_error(['message' => __('File is empty or invalid.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        $postDetails = [];
        $metaData = [];
        if (method_exists(GlobalHelper::class, 'safeUnserialize')) {
            $postDetails = GlobalHelper::safeUnserialize($data['post_data']);
            $metaData = GlobalHelper::safeUnserialize($data['post_meta']);
        }

        $post = new Post();
        $postId = $post->createPost([
            'post_title' => $postDetails['post_title'],
            'post_content' => $postDetails['post_content'],
            'post_type' => $postDetails['post_type'],
            'post_status' => $postDetails['post_status'],
        ]);

        if (!$postId || is_wp_error($postId)) {
            wp_send_json_error(['message' => __('Failed to create post.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }


        if (!add_post_meta($postId, '_wpsr_template_config', $metaData)) {
            wp_send_json_error(['message' => __('Failed to add post meta.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        wp_send_json_success([
            'message' => __('Successfully uploaded data.', 'wp-social-ninja-pro'),
        ], self::HTTP_STATUS_OK);
    }

    private function processNotificatioImport($data)
    {
        if (empty($data)) {
            wp_send_json_error(['message' => __('File is empty or invalid.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        $platformDataList = isset($data[0]) ? $data : [$data];

        $mergedPostMeta = [];
        $mergedPlatforms = [];
        $postData = null;

        foreach ($platformDataList as $platformData) {
            $platform = Arr::get($platformData, 'platform_name');
            $postData = Arr::get($platformData, 'post_data');
            $platform = sanitize_text_field($platform ?? '');
            if (!$platform) {
                wp_send_json_error(['message' => __('Platform name is missing.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
            }

            if ($platform == 'fluent_forms') {
                $this->verifyFluentFormExists();
            } elseif ($platform != 'fluent_forms' && $platform != 'custom' && $platform != 'testimonial') {
                $this->validatePlatformData($platform, $platformData);
            }

            $postMeta = Arr::get($platformData, 'post_meta');
            if (isset($postMeta)) {
                $mergedPostMeta = array_merge($mergedPostMeta, $postMeta);
            }

            $mergedPlatforms[] = $platform;

            if (!empty($platformData['platform_global_settings'])) {
                $platformGlobalSettings = Arr::get($platformData, 'platform_global_settings');
                update_option('wpsr_' . $platform . '_global_settings', $platformGlobalSettings);
            }

            if (in_array($platform, $this->platformManager->feedPlatforms())) {
                $verificationConfigs = Arr::get($platformData, 'verification_configs');
                if (!empty($verificationConfigs)) {
                    $this->updatePlatformVerificationConfigs($platform, $verificationConfigs);
                }
            } else {
                $this->updateReviewsPlatformVerificationConfigs($platform, $platformData);
            }

        }

        $this->createPostForImport($mergedPlatforms, 'notifications', $platformData['post_meta'], $postData);

        wp_send_json_success([
            'message' => __('Successfully uploaded data.', 'wp-social-ninja-pro')
        ], self::HTTP_STATUS_OK);
    }

    private function processTemplateImport($data)
    {
        if (empty($data)) {
            wp_send_json_error(['message' => __('File is empty or invalid.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        $mergedPlatforms = [];
        $postData = null;
        $platformDataList = isset($data[0]) ? $data : [$data];
        foreach ($platformDataList as $platformData) {
            $platform = sanitize_text_field($platformData['platform_name'] ?? '');

            $postData = Arr::get($platformData, 'post_data');
            $mergedPlatforms[] = $platform;
            if (!$platform) {
                wp_send_json_error(['message' => __('Platform name is missing.', 'wp-social-ninja-pro')], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
            }

            if ($platform == 'fluent_forms') {
                $this->verifyFluentFormExists();
            } elseif ($platform != 'fluent_forms' && $platform != 'custom' && $platform != 'testimonial') {
                $this->validatePlatformData($platform, $platformData);
            }

            update_option('wpsr_' . $platform . '_global_settings', $platformData['platform_global_settings']);
            if (in_array($platform, $this->platformManager->feedPlatforms())) {
                $this->updatePlatformVerificationConfigs($platform, $platformData);
            } else {
                $this->updateReviewsPlatformVerificationConfigs($platform, $platformData);
            }
        }

        $this->createPostForImport($mergedPlatforms, 'wp_social_reviews', $platformData['post_meta'], $postData);

        wp_send_json_success([
            'message' => __('Successfully uploaded data.', 'wp-social-ninja-pro')
        ], self::HTTP_STATUS_OK);
    }

    private function validatePlatformData($platform, $platformData)
    {
        $errorMessages = [
            'youtube' => __('Verification config is missing, please connect the platform before exporting feeds.', 'wp-social-ninja-pro'),
            'tiktok' => __('Connected sources config is missing, please connect the platform before exporting feeds.', 'wp-social-ninja-pro'),
            'tiktok_not_installed' => __('TikTok is not installed. Please install and connect TikTok to proceed.', 'wp-social-ninja-pro'),
            'default' => __('Verification configs are missing, please connect the platform before exporting feeds.', 'wp-social-ninja-pro'),
        ];

        if ($platform === 'tiktok') {
            if (!GlobalHelper::isCustomFeedForTiktokInstalled()) { //  `isTikTokInstalled()` checks if TikTok is installed
                wp_send_json_error(['message' => $errorMessages['tiktok_not_installed']], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
            }
            if (empty($platformData['connected_sources_config'])) {
                wp_send_json_error(['message' => $errorMessages['tiktok']], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
            }
        }

        // YouTube-specific validation
        if (empty($platformData['verification_configs']) && $platform === 'youtube') {
            wp_send_json_error(['message' => $errorMessages['youtube']], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        // Default validation for other platforms
        if (
            (empty($platformData['connected_sources_config']) || empty($platformData['verification_configs']))
            && !in_array($platform, ['tiktok', 'youtube'])
        ) {
            wp_send_json_error(['message' => $errorMessages['default']], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

    }

    private function getVerificationConfigsIfNeeded($platformName)
    {
        $platformsRequiringVerification = ['instagram', 'facebook_feed', 'youtube', 'twitter', 'google', 'airbnb', 'yelp', 'tripadvisor', 'amazon', 'aliexpress', 'booking.com', 'facebook', 'trustpilot'];
        if (in_array($platformName, $platformsRequiringVerification)) {
            if ($platformName === 'google') {
                return get_option('wpsr_reviews_google_verification_configs');
            }

            $platformsWithSettings = ['airbnb', 'yelp', 'tripadvisor', 'amazon', 'aliexpress', 'booking.com', 'facebook', 'trustpilot'];

            if (in_array($platformName, $platformsWithSettings, true)) {
                return get_option("wpsr_reviews_{$platformName}_settings");
            }
            return $this->platformManager->getFeedVerificationConfigs($platformName);
        }
        return [];
    }

    private function updateReviewsPlatformVerificationConfigs($platform, $platformData)
    {
        if ($platform === 'google') {
            update_option('wpsr_reviews_google_verification_configs', $platformData['verification_configs']);
            update_option('wpsr_reviews_' . $platform . '_locations_list', $platformData['location_lists']);
            $apiSettings = $platformData['connected_sources_config'] ?? [];
            update_option('wpsr_reviews_google_settings', $apiSettings, false);
        } else if (isset($platformData['verification_configs']) && $platform === 'facebook') {
            $this->processVerificationConfigs(
                $platformData['verification_configs'],
                $this->getVerificationType($platform),
                $platform,
                $platformData
            );
            update_option('wpsr_reviews_' . $platform . '_settings', $platformData['verification_configs']);
        } else {
            update_option('wpsr_reviews_' . $platform . '_settings', $platformData['verification_configs']);
        }
        update_option('wpsr_reviews_' . $platform . '_business_info', $platformData['business_info']);
    }
    private function updatePlatformVerificationConfigs($platform, $data)
    {
        if (isset($data['connected_sources_config']) || isset($data['verification_configs'])) {
            $configType = isset($data['connected_sources_config']) &&
                in_array($platform, ['facebook_feed', 'instagram', 'tiktok'])
                ? 'connected_sources_config'
                : 'verification_configs';

            $this->processVerificationConfigs($data[$configType], $this->getVerificationType($platform), $platform);
        }

        switch ($platform) {
            case 'facebook_feed':
                $this->updatePlatformOptions($platform, $data);
                update_option('wpsr_facebook_feed_connected_sources_config', ['sources' => $data['connected_sources_config']]);
                break;
            case 'instagram':
                $verification_configs['verification_configs'] = [
                    'connected_accounts' => $data['connected_sources_config']
                ];
                $this->updatePlatformOptions($platform, $verification_configs);
                break;
            case 'youtube':
                $this->updatePlatformOptions($platform, $data);
                break;
            case 'twitter':
                $this->updatePlatformOptions($platform, $data);
                break;
            case 'tiktok':
                update_option('wpsr_tiktok_connected_sources_config', ['sources' => $data['connected_sources_config']]);
                break;
        }
    }

    private function getVerificationType($platform)
    {
        $verificationTypes = [
            'facebook_feed' => 'EA',
            'instagram' => 'IG',
            'tiktok' => 'act',
            'youtube' => 'YT',
            'facebook' => 'EA'
        ];

        return $verificationTypes[$platform] ?? '';
    }

    public function processVerificationConfigs(&$verificationConfigs, $tokenPrefix, $platform, &$platformData = null)
    {
        foreach ($verificationConfigs as &$verificationConfig) {
            if (isset($verificationConfig['access_token']) || isset($verificationConfig['api_key'])) {
                $access_token = $verificationConfig['access_token'] ?? $verificationConfig['api_key'];
                $parsedToken = $this->protector->decrypt($access_token) ?: $access_token;

                if ($parsedToken && !str_contains($parsedToken ?? '', $tokenPrefix)) {
                    if ($platform == 'facebook') {
                        $placeId = $verificationConfig['place_id'];
                        if (!isset($platformData['business_info'][$placeId])) {
                            $platformData['business_info'][$placeId] = [];
                        }
                        $platformData['business_info'][$placeId]['encryption_error'] = true;
                    } else {
                        $verificationConfig['encryption_error'] = true;
                    }
                }
            }
        }
    }

    private function processCSVImport($data, $type, $targetSourceId = null)
    {
        $tableHeaders = $this->getTableHeaders();

        // Sanitize CSV header
        $csvHeader = array_shift($data);
        $csvHeader = array_map('esc_attr', $csvHeader);

        // Check if user is trying to import custom_sources CSV with wrong import type
        $this->validateImportTypeMatch($csvHeader, $type);

        // Check if platform_name already exists in CSV header
        $hasPlatformName = in_array('platform_name', $csvHeader);

        if (!$hasPlatformName) {
            // Only add platform_name if it doesn't exist
            array_splice($csvHeader, 1, 0, "platform_name");
        }

        // Validate headers against allowed columns for the type
        $validationHeaders = $tableHeaders[$type] ?? $tableHeaders['custom'];
        foreach ($csvHeader as $column) {
            if (!in_array($column, $validationHeaders)) {
                wp_send_json_error([
                    'message' => __('Unknown column: ' . $column . '. Invalid characters in column name!', 'wp-social-ninja-pro')
                ], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
            }
        }

        // Format the data according to the sanitized header
        $formattedData = array_map(function ($el) use ($type, $hasPlatformName) {
            if (!$hasPlatformName) {
                // Only insert platform_name if it wasn't in the original CSV
                // For custom_sources, use 'custom' as the platform name
                $platformName = ($type === 'custom_sources') ? 'custom' : $type;
                array_splice($el, 1, 0, $platformName);
            }
            return $el;
        }, $data);

        $dataRecords = $this->prepareDataRecords($formattedData, $csvHeader, $type);

        // Check for source ID conflicts when importing custom_sources
        if ($type === 'custom_sources') {
            $this->validateSourceIdConflicts($dataRecords, $targetSourceId);
        }

        // If target source ID is provided, assign all reviews to that source
        if ($targetSourceId) {
            $this->assignToTargetSource($dataRecords, $targetSourceId);
        }

        foreach (array_chunk($dataRecords, self::BATCH_SIZE) as $chunk) {
            $this->batchInsert($chunk, $type);
        }
    }

    /**
     * Process Judge.me CSV import using dedicated service
     * 
     * @param array $data CSV data from Judge.me export
     */
    private function processJudgeMeImportWithService($data)
    {
        // Use the dedicated JudgeMe service for processing
        $result = $this->judgeMeService->processImport($data);

        if (!$result['success']) {
            $this->sendErrorResponse($result['message'], $result['error_code']);
        }

        // Process the mapped data in chunks
        foreach (array_chunk($result['data'], self::BATCH_SIZE) as $chunk) {
            $this->batchInsert($chunk, 'woocommerce');
        }

        // Build success message
        $statistics = $result['statistics'];
        $message = sprintf(
            __('Successfully imported %d Judge.me reviews.', 'wp-social-ninja-pro'),
            $statistics['imported']
        );

        if ($statistics['skipped_duplicates'] > 0) {
            $message .= sprintf(
                __(' %d duplicate reviews were skipped.', 'wp-social-ninja-pro'),
                $statistics['skipped_duplicates']
            );
        }

        if ($statistics['skipped_invalid_products'] > 0) {
            $message .= sprintf(
                __(' %d reviews with invalid product IDs were skipped.', 'wp-social-ninja-pro'),
                $statistics['skipped_invalid_products']
            );
        }

        if (isset($statistics['skipped_without_product_id']) && $statistics['skipped_without_product_id'] > 0) {
            $message .= sprintf(
                __(' %d reviews without product IDs were skipped.', 'wp-social-ninja-pro'),
                $statistics['skipped_without_product_id']
            );
        }

        if (isset($statistics['reviews_without_product_id']) && $statistics['reviews_without_product_id'] > 0) {
            $message .= sprintf(
                __(' %d reviews without product IDs were imported as general reviews.', 'wp-social-ninja-pro'),
                $statistics['reviews_without_product_id']
            );
        }

        if (isset($statistics['reviews_with_empty_fields']) && $statistics['reviews_with_empty_fields'] > 0) {
            $message .= sprintf(
                __(' %d reviews had empty fields that were filled with default values.', 'wp-social-ninja-pro'),
                $statistics['reviews_with_empty_fields']
            );
        }

        if (isset($statistics['skipped_malformed_rows']) && $statistics['skipped_malformed_rows'] > 0) {
            $message .= sprintf(
                __(' %d malformed rows were skipped.', 'wp-social-ninja-pro'),
                $statistics['skipped_malformed_rows']
            );
        }

        $this->sendSuccessResponse([
            'message' => $message,
            'imported' => $statistics['imported'],
            'skipped_duplicates' => $statistics['skipped_duplicates'],
            'skipped_invalid_products' => $statistics['skipped_invalid_products'],
            'skipped_without_product_id' => $statistics['skipped_without_product_id'] ?? 0,
            'reviews_with_empty_fields' => $statistics['reviews_with_empty_fields'] ?? 0,
            'skipped_malformed_rows' => $statistics['skipped_malformed_rows'] ?? 0,
            'reviews_without_product_id' => $statistics['reviews_without_product_id'] ?? 0,
            'invalid_product_ids' => $statistics['invalid_product_ids'],
            'total' => $statistics['total']
        ]);
    }



    private function prepareDataRecords($reader, $csvHeader, $type)
    {
        $dataRecords = [];
        $headerCount = count($csvHeader);

        foreach ($reader as $item) {
            $itemTemp = $this->mapItemToHeader($item, $csvHeader, $headerCount);
            $itemTemp['review_time'] = date('Y-m-d H:i:s', strtotime($itemTemp['review_time']));
            $itemTemp['created_at'] = date('Y-m-d H:i:s');
            $itemTemp['updated_at'] = date('Y-m-d H:i:s');

            // Add the extra testimonial columns to the fields column
            if ($type == 'testimonial') {
                $itemTemp = $this->addTestimonialColumns($itemTemp);
            }

            $dataRecords[] = $itemTemp;
        }

        return $dataRecords;
    }

    public function batchInsert($rows, $type)
    {
        global $wpdb;
        // Extract column list from first row of data
        $columns = array_keys($rows[0]);

        $table = $wpdb->prefix . 'wpsr_reviews';

        asort($columns);
        $columnList = '`' . implode('`, `', $columns) . '`';
        $productIds = array_column($rows, 'source_id');
        $uniqueProductIds = array_unique($productIds);

        // Start building SQL, initialise data and placeholder arrays
        $sql = "INSERT INTO `$table` ($columnList) VALUES\n";
        $placeholders = array();
        $data = array();

        // Build placeholders for each row, and add values to data array
        foreach ($rows as $row) {
            ksort($row);
            $rowPlaceholders = array();
            foreach ($row as $key => $value) {
                $data[] = json_decode(json_encode(sanitize_text_field($value), JSON_UNESCAPED_UNICODE), true);

                if ($key === 'source_id') {
                    $rowPlaceholders[] = '%s';
                } else {
                    $rowPlaceholders[] = is_numeric($value) ? '%d' : '%s';
                }
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        // Stitch all rows together
        $sql .= implode(",\n", $placeholders);
        // Run the query.  Returns number of affected rows.

        $res = $wpdb->query($wpdb->prepare($sql, $data));
        if (!$res) {
            wp_send_json_error([
                'message' => __('Data is not uploaded!!', 'wp-social-ninja-pro')
            ], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        // Schedule image processing for Judge.me reviews
        if ($type === 'woocommerce' && $res > 0) {
            // Instead of trying to calculate ID ranges, we'll schedule processing
            // and let the service find the reviews that need image processing
            $this->judgeMeService->scheduleImageProcessing();
        }

        if ($type === 'woocommerce') {
            // Filter out empty product IDs before processing
            $validProductIds = array_filter($uniqueProductIds, function ($productId) {
                return !empty($productId) && $productId !== '' && $productId !== null;
            });

            foreach ($validProductIds as $productId) {
                (new WooCommerce())->connectProductToWPSR($productId, true);
            }

            // Only store valid product IDs in options
            if (!empty($validProductIds)) {
                $existingProductIds = get_option('_wpsn_ids', []);
                if (!is_array($existingProductIds)) {
                    $existingProductIds = [];
                }
                $allProductIds = array_unique(array_merge($existingProductIds, $validProductIds));
                update_option('_wpsn_ids', $allProductIds, false);
            }
        } else {
            // Update business info option for non-feed platforms
            $businessInfo = Review::getInternalBusinessInfo($type);
            update_option('wpsr_reviews_' . $type . '_business_info', $businessInfo);
        }

        // Only send success response if this is not called from Judge.me import
        if ($type !== 'woocommerce') {
            wp_send_json_success([
                'message' => __('Successfully uploaded data.', 'wp-social-ninja-pro')
            ], self::HTTP_STATUS_OK);
        }
    }

    /**
     * Create a post for the imported data
     *
     * @param array $platforms
     * @param string $type (template, notification, chat-widget)
     * @param array $postMeta
     * @return int|false
     */
    private function createPostForImport($platforms, $type, $postMeta, $postInformation = null)
    {
        $post = new Post();
        $implodedPlatforms = implode(', ', $platforms);

        $postData = [
            'post_title' => $postInformation['post_title'],
            'post_content' => $postInformation['post_content'],
            'post_type' => $postInformation['post_type'],
        ];

        $postId = $post->createPost($postData);

        if ($postId && !empty($postMeta)) {
            $post->updatePostMeta($postId, $postMeta, $implodedPlatforms);

            switch ($type) {
                case 'template':
                case 'notifications':
                    update_post_meta($postId, '_wpsr_template_styles_config', json_encode($postMeta['post_style_meta'] ?? []));
                    break;
                case 'chat-widget':
                    update_post_meta($postId, '_wpsr_template_config', $postMeta);
                    break;
            }
        }

        return $postId;
    }

    private function formatPostData($postId)
    {
        $post = get_post($postId);
        return [
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
        ];
    }

    /**
     * Send standardized error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    private function sendErrorResponse($message, $statusCode = self::HTTP_STATUS_UNPROCESSABLE_ENTITY)
    {
        wp_send_json_error(['message' => $message], $statusCode);
    }

    /**
     * Send standardized success response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    private function sendSuccessResponse($data, $statusCode = self::HTTP_STATUS_OK)
    {
        wp_send_json_success($data, $statusCode);
    }

    private function verifyFluentFormExists()
    {
        $hasFluentForm = defined('FLUENTFORM_VERSION');
        if (!$hasFluentForm) {
            $this->sendErrorResponse(__('Fluent Forms is not installed. Please install Fluent Forms to proceed.', 'wp-social-ninja-pro'));
        }
    }

    /**
     * Assign all imported reviews to target source ID
     */
    private function assignToTargetSource(&$dataRecords, $targetSourceId)
    {
        foreach ($dataRecords as &$record) {
            $record['source_id'] = $targetSourceId;
        }
    }

    /**
     * Validate that import type matches CSV content
     */
    private function validateImportTypeMatch($csvHeader, $selectedType)
    {
        // Check if CSV appears to be custom_sources format but user selected different type
        $customSourcesIndicators = ['source_id', 'platform_name', 'reviewer_name', 'rating'];
        $hasCustomSourcesIndicators = 0;

        foreach ($customSourcesIndicators as $indicator) {
            if (in_array($indicator, $csvHeader)) {
                $hasCustomSourcesIndicators++;
            }
        }

        // If CSV has 3 or more custom_sources indicators but user didn't select custom_sources
        if ($hasCustomSourcesIndicators >= 3 && $selectedType !== 'custom_sources') {
            wp_send_json_error([
                'message' => __('Import failed: Your CSV file appears to contain custom sources data, but you selected a different import type. Please select "Custom Sources Reviews" as the import type.', 'wp-social-ninja-pro')
            ], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }

        // Check if user selected custom_sources but CSV doesn't have the required headers
        if ($selectedType === 'custom_sources' && $hasCustomSourcesIndicators < 2) {
            wp_send_json_error([
                'message' => __('Import failed: You selected "Custom Sources Reviews" but your CSV file does not appear to contain custom sources data. Please check your file format or select the correct import type.', 'wp-social-ninja-pro')
            ], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Validate source ID conflicts for custom_sources import
     */
    private function validateSourceIdConflicts($dataRecords, $targetSourceId)
    {
        // If target source ID is provided, check if it matches any CSV source IDs
        $csvSourceIds = array_unique(array_column($dataRecords, 'source_id'));
        $csvSourceIds = array_filter($csvSourceIds, function($id) {
            return !empty($id) && $id !== null;
        });

        if (!in_array($targetSourceId, $csvSourceIds)) {
            $errorMessage = sprintf(
                __('Import failed: The selected target source ID (%s) does not match any source ID in your CSV file. This could cause a conflict. Please select the correct target source.', 'wp-social-ninja-pro'),
                $targetSourceId
            );

            wp_send_json_error([
                'message' => $errorMessage,
            ], self::HTTP_STATUS_UNPROCESSABLE_ENTITY);
        }
    }
}