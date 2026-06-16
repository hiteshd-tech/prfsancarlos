<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\App\Models\Review;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\PermissionManager;
use DateTime;

/**
 * JudgeMe Service Class
 * 
 * Handles all JudgeMe-specific functionality including import, validation,
 * and data processing for JudgeMe reviews integration with WooCommerce.
 */
class JudgeMeService
{

    static $defaultSourceId = 'store-reviews';
    // Constants for JudgeMe functionality
    private const REQUIRED_COLUMNS = [
        'rating',
        'review_date'
    ];

    private const VALID_DATE_FORMATS = [
        'Y-m-d H:i:s T', // 2025-08-06 05:11:14 UTC
        'Y-m-d H:i:s',   // 2025-08-06 05:11:14
        'Y-m-d H:i',     // 2025-08-06 05:11
        'Y-m-d'          // 2025-08-06
    ];

    /**
     * Process Judge.me CSV import
     * 
     * Maps Judge.me export format to internal database structure
     * Required columns: rating, review_date
     * Optional columns: title, body, reviewer_name, source, curated, reviewer_email, product_id, product_handle, reply, reply_date, picture_urls, ip_address, location
     * 
     * @param array $data CSV data from Judge.me export
     * @return array Processing result with statistics
     */
    public function processImport($data)
    {
        if (empty($data)) {
            return [
                'success' => false,
                'message' => __('File is empty or invalid.', 'wp-social-ninja-pro'),
                'error_code' => 423
            ];
        }

        // Check WooCommerce installation
        if (!$this->isWooCommerceInstalled()) {
            return [
                'success' => false,
                'message' => __('WooCommerce is not installed or activated. Judge.me reviews import requires WooCommerce to be installed.', 'wp-social-ninja-pro'),
                'error_code' => 423
            ];
        }

        // Get and validate header row
        $csvHeader = array_shift($data);
        $csvHeader = array_map('esc_attr', $csvHeader);

        // Validate required columns
        $validationResult = $this->validateRequiredColumns($csvHeader);
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'message' => $validationResult['message'],
                'error_code' => 423
            ];
        }

        // Get import setting for reviews without product IDs
        $importSetting = $this->getImportSettingForReviewsWithoutProductId();

        // Process data with validation
        $processingResult = $this->processData($data, $csvHeader, $importSetting);

        if (empty($processingResult['mapped_data'])) {
            return $this->buildEmptyDataResponse($processingResult);
        }

        return [
            'success' => true,
            'data' => $processingResult['mapped_data'],
            'statistics' => [
                'imported' => count($processingResult['mapped_data']),
                'skipped_duplicates' => $processingResult['skipped_duplicates'],
                'skipped_invalid_products' => $processingResult['skipped_invalid_products'],
                'skipped_without_product_id' => $processingResult['skipped_without_product_id'] ?? 0,
                'reviews_with_empty_fields' => $processingResult['reviews_with_empty_fields'] ?? 0,
                'skipped_malformed_rows' => $processingResult['skipped_malformed_rows'] ?? 0,
                'reviews_without_product_id' => $processingResult['reviews_without_product_id'] ?? 0,
                'invalid_product_ids' => array_unique($processingResult['invalid_product_ids']),
                'total' => count($data)
            ]
        ];
    }

    /**
     * Get import setting for handling reviews without product IDs
     * 
     * @return string 'import' or 'skip'
     */
    private function getImportSettingForReviewsWithoutProductId()
    {
        /**
         * Filter to control how reviews without product IDs are handled during import
         * 
         * @param string $action Default action for reviews without product IDs
         *                      'import' - Import reviews without product IDs as general reviews
         *                      'skip' - Skip reviews that don't have product IDs
         * 
         * @since 1.0.0
         */
        return apply_filters('wpsr_import_reviews_without_product_id', 'import');
    }

    /**
     * Validate required JudgeMe columns
     * 
     * @param array $csvHeader CSV header columns
     * @return array Validation result
     */
    private function validateRequiredColumns($csvHeader)
    {
        $missingColumns = array_diff(self::REQUIRED_COLUMNS, $csvHeader);

        if (!empty($missingColumns)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    __('Missing required columns: %s. Please ensure your CSV file contains rating and review_date columns.', 'wp-social-ninja-pro'),
                    implode(', ', $missingColumns)
                )
            ];
        }

        return ['valid' => true];
    }

    /**
     * Process Judge.me data with validation and mapping
     * 
     * @param array $data CSV data rows
     * @param array $csvHeader CSV header columns
     * @param string $importSetting Import setting for reviews without product IDs ('import' or 'skip')
     * @return array Processing result with mapped data and statistics
     */
    private function processData($data, $csvHeader, $importSetting = 'import')
    {
        $mappedData = [];
        $skippedDuplicates = 0;
        $skippedInvalidProducts = 0;
        $skippedWithoutProductId = 0;
        $reviewsWithoutProductId = 0;
        $reviewsWithEmptyFields = 0;
        $skippedMalformedRows = 0;
        $invalidProductIds = [];
        $rowNumber = 1;

        foreach ($data as $row) {
            $rowNumber++;

            // Check if row has correct number of columns
            if (count($row) !== count($csvHeader)) {
                $skippedMalformedRows++;
                continue;
            }

            $rowData = array_combine($csvHeader, $row);

            // Check if array_combine failed (shouldn't happen after count check, but safety first)
            if ($rowData === false) {
                $skippedMalformedRows++;
                continue;
            }

            // Check for duplicates before processing
            if ($this->isReviewExists($rowData)) {
                $skippedDuplicates++;
                continue;
            }

            // Handle product ID validation
            $productId = $rowData['product_id'] ?? '';

            if (empty($productId)) {
                // Handle reviews without product ID based on import setting
                if ($importSetting === 'skip') {
                    $skippedWithoutProductId++;
                    continue;
                } else {
                    // Import reviews without product ID but mark them accordingly
                    $reviewsWithoutProductId++;
                }
            } elseif (!$this->isProductExists($productId)) {
                // Only skip if product ID is provided but invalid
                $skippedInvalidProducts++;
                $invalidProductIds[] = $productId;
                continue;
            }

            $mappedRow = $this->mapToInternalFormat($row, $csvHeader, $rowNumber);
            if ($mappedRow) {
                // Track if this review had empty fields that we filled with defaults
                $originalReviewerName = trim($rowData['reviewer_name'] ?? '');
                $originalBody = trim($rowData['body'] ?? '');
                $originalTitle = trim($rowData['title'] ?? '');

                if (empty($originalReviewerName) || (empty($originalBody) && empty($originalTitle))) {
                    $reviewsWithEmptyFields++;
                }

                $mappedData[] = $mappedRow;
            }
        }

        return [
            'mapped_data' => $mappedData,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_invalid_products' => $skippedInvalidProducts,
            'skipped_without_product_id' => $skippedWithoutProductId,
            'reviews_with_empty_fields' => $reviewsWithEmptyFields,
            'skipped_malformed_rows' => $skippedMalformedRows,
            'reviews_without_product_id' => $reviewsWithoutProductId,
            'invalid_product_ids' => $invalidProductIds
        ];
    }

    /**
     * Build response for empty data scenarios
     * 
     * @param array $processingResult Processing result
     * @return array Error response
     */
    private function buildEmptyDataResponse($processingResult)
    {
        $skippedDuplicates = $processingResult['skipped_duplicates'];
        $skippedInvalidProducts = $processingResult['skipped_invalid_products'];
        $skippedWithoutProductId = $processingResult['skipped_without_product_id'] ?? 0;
        $reviewsWithoutProductId = $processingResult['reviews_without_product_id'] ?? 0;

        $messageParts = [];

        if ($skippedDuplicates > 0) {
            $messageParts[] = sprintf(
                __('%d duplicate reviews', 'wp-social-ninja-pro'),
                $skippedDuplicates
            );
        }

        if ($skippedInvalidProducts > 0) {
            $messageParts[] = sprintf(
                __('%d reviews with invalid product IDs', 'wp-social-ninja-pro'),
                $skippedInvalidProducts
            );
        }

        if ($skippedWithoutProductId > 0) {
            $messageParts[] = sprintf(
                __('%d reviews without product IDs', 'wp-social-ninja-pro'),
                $skippedWithoutProductId
            );
        }

        if (!empty($messageParts)) {
            $message = sprintf(
                __('All reviews in the file were skipped. %s were skipped.', 'wp-social-ninja-pro'),
                implode(', ', $messageParts)
            );
        } else {
            $message = __('No valid data found in the file.', 'wp-social-ninja-pro');
        }

        return [
            'success' => false,
            'message' => $message,
            'error_code' => 423
        ];
    }

    /**
     * Check if WooCommerce is installed and activated
     * 
     * @return bool True if WooCommerce is installed and activated
     */
    private function isWooCommerceInstalled()
    {
        if (defined('WC_PLUGIN_FILE')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a WooCommerce product exists by product ID
     * 
     * @param string|int $productId The product ID to check
     * @return bool True if product exists, false otherwise
     */
    private function isProductExists($productId)
    {
        if (empty($productId)) {
            return false;
        }

        // Ensure WooCommerce is active
        if (!$this->isWooCommerceInstalled()) {
            return false;
        }

        // Check if the product exists using WooCommerce functions
        $product = wc_get_product($productId);

        if ($product && $product->get_id()) {
            return true;
        }

        // Also check by post type in case wc_get_product fails
        $post = get_post($productId);
        if ($post && $post->post_type === 'product' && $post->post_status === 'publish') {
            return true;
        }

        return false;
    }

    /**
     * Check if a Judge.me review already exists to prevent duplicates
     * 
     * Uses multiple strategies to detect duplicates:
     * 1. Check by unique review ID (most reliable)
     * 2. Check by combination of product_id, reviewer_name, and review text
     * 3. Check by combination of product_id, reviewer_email, and review date
     * 
     * @param array $rowData Judge.me row data
     * @return bool True if review exists, false otherwise
     */
    private function isReviewExists($rowData)
    {
        $productId = $rowData['product_id'] ?? '';
        $reviewerName = $rowData['reviewer_name'] ?? '';
        $reviewerEmail = $rowData['reviewer_email'] ?? '';
        $reviewDate = $this->formatDate($rowData['review_date'] ?? '');
        $reviewText = $rowData['body'] ?? '';

        // Generate unique review ID for strategy 1
        $uniqueReviewId = $this->generateReviewId($rowData);

        // Build a single query with OR conditions for all strategies
        $query = Review::where('platform_name', 'woocommerce');

        $hasConditions = false;

        // Start with an empty where clause that we'll add OR conditions to
        $query->where(function ($q) use ($uniqueReviewId, $productId, $reviewerName, $reviewText, $reviewerEmail, $reviewDate, &$hasConditions) {

            // Strategy 1: Check by unique review ID (most reliable)
            $q->where('review_id', $uniqueReviewId);
            $hasConditions = true;

            // Strategy 2: Check by product_id, reviewer_name, and review text combination
            if (!empty($productId) && !empty($reviewerName) && !empty($reviewText)) {
                $q->orWhere(function ($subQuery) use ($productId, $reviewerName, $reviewText) {
                    $subQuery->where('source_id', $productId)
                        ->where('reviewer_name', $reviewerName)
                        ->where('reviewer_text', $reviewText);
                });
            }

            // Strategy 3: Check by product_id, reviewer_email, and review date (if email is available)
            if (!empty($productId) && !empty($reviewerEmail) && !empty($reviewDate)) {
                $dateStart = date('Y-m-d H:i:s', strtotime($reviewDate . ' -12 hours'));
                $dateEnd = date('Y-m-d H:i:s', strtotime($reviewDate . ' +12 hours'));

                $q->orWhere(function ($subQuery) use ($productId, $reviewerEmail, $dateStart, $dateEnd) {
                    $subQuery->where('source_id', $productId)
                        ->where('review_time', '>=', $dateStart)
                        ->where('review_time', '<=', $dateEnd)
                        ->where('fields', 'LIKE', '%' . $reviewerEmail . '%');
                });
            }

            // Strategy 4: Check by product_id, reviewer_name, and similar review date (within 1 hour)
            if (!empty($productId) && !empty($reviewerName) && !empty($reviewDate)) {
                $dateStart = date('Y-m-d H:i:s', strtotime($reviewDate . ' -30 minutes'));
                $dateEnd = date('Y-m-d H:i:s', strtotime($reviewDate . ' +30 minutes'));

                $q->orWhere(function ($subQuery) use ($productId, $reviewerName, $dateStart, $dateEnd) {
                    $subQuery->where('source_id', $productId)
                        ->where('reviewer_name', $reviewerName)
                        ->where('review_time', '>=', $dateStart)
                        ->where('review_time', '<=', $dateEnd);
                });
            }
        });

        // If we have any conditions, execute the query
        if ($hasConditions) {
            $existingReview = $query->first();
            return $existingReview !== null;
        }

        return false;
    }

    /**
     * Map Judge.me CSV row to internal database format
     * 
     * @param array $row CSV row data
     * @param array $csvHeader CSV header columns
     * @param int $rowNumber Row number for error reporting
     * @return array|null Mapped data or null if validation fails
     */
    private function mapToInternalFormat($row, $csvHeader, $rowNumber = 0)
    {
        // Create associative array from row data
        $rowData = array_combine($csvHeader, $row);

        // Get field values (allow empty values)
        $reviewerName = trim($rowData['reviewer_name'] ?? '');
        $reviewBody = trim($rowData['body'] ?? '');
        $reviewTitle = trim($rowData['title'] ?? '');

        // Provide default values for empty fields
        if (empty($reviewerName)) {
            $reviewerName = __('Anonymous', 'wp-social-ninja-pro');
        }

        if (empty($reviewBody) && empty($reviewTitle)) {
            $reviewBody = __('No review content provided', 'wp-social-ninja-pro');
        }

        $rating = Arr::get($rowData, 'rating', 0);

        // Handle product ID - use default source ID if product_id is empty
        $productId = !empty($rowData['product_id']) ? $rowData['product_id'] : static::$defaultSourceId;
        $category = $rowData['product_handle'] ?? '';

        // If no product ID but we have product handle, use that as category
        if (empty($productId) && !empty($category)) {
            $category = 'Unlinked Product: ' . $category;
        } elseif (empty($productId) && empty($category)) {
            $category = 'General Review';
        }

        // Map Judge.me fields to internal format
        $mappedData = [
            'platform_name' => 'woocommerce',
            'source_id' => $productId,
            'review_id' => $this->generateReviewId($rowData),
            'category' => $category,
            'review_title' => $reviewTitle,
            'reviewer_name' => $reviewerName,
            'reviewer_url' => '', // Judge.me doesn't provide reviewer URL
            'reviewer_img' => '', // Judge.me doesn't provide reviewer image
            'reviewer_text' => $reviewBody,
            'review_time' => $this->formatDate($rowData['review_date'] ?? ''),
            'rating' => $rating,
            'review_approved' => 1,
            'recommendation_type' => '',
            'fields' => $this->mapFields($rowData),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $mappedData;
    }

    /**
     * Generate a unique review ID for Judge.me reviews
     * 
     * Creates a consistent unique identifier based on multiple data points
     * to ensure the same review always generates the same ID.
     * Guarantees the ID will never exceed 255 characters (varchar limit).
     * 
     * @param array $rowData Judge.me row data
     * @return string Unique review ID (max 255 characters)
     */
    private function generateReviewId($rowData)
    {
        $productId = $rowData['product_id'] ?? '';
        $reviewerName = $rowData['reviewer_name'] ?? '';
        $reviewerEmail = $rowData['reviewer_email'] ?? '';
        $reviewDate = $rowData['review_date'] ?? '';
        $reviewText = substr($rowData['body'] ?? '', 0, 100); // First 100 chars for uniqueness
        $reviewTitle = substr($rowData['title'] ?? '', 0, 50); // Include title for uniqueness

        // Normalize the data for consistent hashing
        $productId = trim($productId);
        $reviewerName = trim(strtolower($reviewerName));
        $reviewerEmail = trim(strtolower($reviewerEmail));

        // Use formatted date for consistency
        $formattedDate = $this->formatDate($reviewDate);

        // Create a unique string combining multiple identifiers
        // Priority: product_id + reviewer_email + date (most reliable)
        // Fallback: product_id + reviewer_name + date + partial_text + title
        if (!empty($reviewerEmail)) {
            $uniqueString = $productId . '_' . $reviewerEmail . '_' . $formattedDate;
        } else {
            // Include title and review text for better uniqueness when reviewer info is missing
            $uniqueString = $productId . '_' . $reviewerName . '_' . $formattedDate . '_' . md5($reviewText . $reviewTitle);
        }

        // Generate MD5 hash to ensure consistent length (32 chars) + prefix
        $hashedId = md5($uniqueString);
        $reviewId = 'judge_me_' . $hashedId;

        return $reviewId;
    }

    /**
     * Map Judge.me specific fields to JSON fields column
     * 
     * @param array $rowData Judge.me row data
     * @return string JSON encoded fields
     */
    private function mapFields($rowData)
    {
        $fields = [
            'reviewer_email' => $rowData['reviewer_email'] ?? '',
            'source' => $rowData['source'] ?? 'judge-me',
            'curated' => $rowData['curated'] ?? '',
            'product_id' => !empty($rowData['product_id']) ? $rowData['product_id'] : static::$defaultSourceId,
            'product_handle' => $rowData['product_handle'] ?? '',
            'reply' => $rowData['reply'] ?? '',
            'reply_date' => $rowData['reply_date'] ?? '',
            'picture_urls' => $rowData['picture_urls'] ?? '',
            'ip_address' => $rowData['ip_address'] ?? '',
            'location' => $rowData['location'] ?? '',
            'imported_from' => 'judge-me'
        ];

        return json_encode($fields);
    }

    public function scheduleImageProcessing()
    {
        // Schedule using Action Scheduler if available, fallback to wp_schedule_single_event
        if (function_exists('as_schedule_single_action')) {
            // Check if batch image processing is already scheduled
            $scheduled_actions = as_get_scheduled_actions([
                'hook' => 'wpsr_process_judgeme_images',
                'status' => 'pending'
            ]);

            if (empty($scheduled_actions)) {
                as_schedule_single_action(time(), 'wpsr_process_judgeme_images', array(true), 'wpsr_review_image_import', true);
            }
        } else {
            // Check if the event is already scheduled in WordPress cron
            $scheduled_time = wp_next_scheduled('wpsr_process_judgeme_images', array(true));
            if (!$scheduled_time) {
                wp_schedule_single_event(time() + 30, 'wpsr_process_judgeme_images', array(true));
            }
        }
    }

    public function processReviewImages($processImage)
    {

        // If we received a batch indicator, find all Judge.me reviews that need image processing        
        $reviewIds = $this->findReviewsNeedingImageProcessing();

        if (empty($reviewIds)) {
            return;
        }

        // Schedule separate events for each review to avoid execution time limits
        $delay = 5; // Start with 5 second delay
        foreach ($reviewIds as $reviewId) {
            $this->scheduleSingleReviewImageProcessing($reviewId, $delay);
            $delay += 2; // Add 2 seconds between each review processing to spread the load
        }
    }

    /**
     * Schedule image processing for a single review
     * 
     * @param int $reviewId Review ID to process
     * @param int $delay Delay in seconds before processing
     */
    private function scheduleSingleReviewImageProcessing($reviewId, $delay = 5)
    {
        if (empty($reviewId)) {
            return;
        }

        // Schedule using Action Scheduler if available, fallback to wp_schedule_single_event
        if (function_exists('as_schedule_single_action')) {
            // Check if this specific review image processing is already scheduled
            $scheduled_actions = as_get_scheduled_actions([
                'hook' => 'wpsr_process_single_judgeme_image',
                'args' => array($reviewId),
                'status' => 'pending'
            ]);

            if (empty($scheduled_actions)) {
                as_schedule_single_action(time() + $delay, 'wpsr_process_single_judgeme_image', array($reviewId), 'wpsr_review_image_import', false);
            }
        } else {
            // Check if the event is already scheduled in WordPress cron
            $scheduled_time = wp_next_scheduled('wpsr_process_single_judgeme_image', array($reviewId));
            if (!$scheduled_time) {
                wp_schedule_single_event(time() + $delay, 'wpsr_process_single_judgeme_image', array($reviewId));
            }
        }
    }

    /**
     * Process images for a single review (callback for scheduled event)
     * 
     * @param int $reviewId Review ID to process
     */
    public function processSingleReviewImages($reviewId)
    {
        if (empty($reviewId)) {
            return;
        }

        ini_set('max_execution_time', 300); // Extend execution time for image processing

        // Check if this review has already been processed to prevent duplicate processing
        $review = Review::find($reviewId);
        if (!$review) {
            return;
        }

        $fields = $review->fields;
        if (!empty($fields['picture_urls_processed'])) {
            return;
        }

        $this->downloadAndAttachImages($reviewId);
    }

    /**
     * Find Judge.me reviews that need image processing
     * 
     * @return array Array of review IDs that need image processing
     */
    private function findReviewsNeedingImageProcessing()
    {
        // Find all WooCommerce reviews with Judge.me data that have picture_urls but haven't been processed
        $reviews = Review::where('platform_name', 'woocommerce')
            ->where('fields', 'LIKE', '%judge-me%')
            ->where('fields', 'LIKE', '%picture_urls%')
            ->where('fields', 'NOT LIKE', '%picture_urls_processed%')
            ->get(['id', 'fields']);

        $reviewIds = [];
        foreach ($reviews as $review) {
            $fields = $review->fields;
            if (!empty($fields['picture_urls']) && empty($fields['picture_urls_processed'])) {
                $reviewIds[] = $review->id;
            }
        }

        return $reviewIds;
    }

    /**
     * Download and attach images for a specific review
     * 
     * @param int $reviewId Review ID
     */
    private function downloadAndAttachImages($reviewId)
    {
        $review = Review::find($reviewId);
        if (!$review) {
            return;
        }

        $fields = $review->fields;

        if (empty($fields) || empty($fields['picture_urls'])) {
            return;
        }

        $pictureUrls = $fields['picture_urls'];

        // Handle comma-separated URLs or single URL
        $imageUrls = is_string($pictureUrls) ? explode(',', $pictureUrls) : (array) $pictureUrls;
        $imageUrls = array_filter(array_map('trim', $imageUrls));

        if (empty($imageUrls)) {
            return;
        }

        $downloadedImages = [];
        $failedDownloads = [];

        foreach ($imageUrls as $imageUrl) {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $failedDownloads[] = $imageUrl;
                continue;
            }

            $attachmentId = $this->downloadImage($imageUrl);
            if ($attachmentId) {
                $downloadedImages[] = wp_get_attachment_url($attachmentId);
            } else {
                $failedDownloads[] = $imageUrl;
            }
        }

        // Always mark as processed to avoid infinite retry loops, but log the results
        $fields['picture_urls_processed'] = true;

        if (!empty($downloadedImages)) {
            // Update the fields with downloaded image URLs
            $fields['review_images'] = $downloadedImages;
        }

        if (!empty($failedDownloads)) {
            $fields['failed_image_count'] = count($failedDownloads);
        }

        // Update the review with new fields
        Review::where('id', $reviewId)->update([
            'fields' => json_encode($fields)
        ]);
    }

    /**
     * Download an image from URL and create WordPress attachment
     * 
     * @param string $imageUrl URL of the image to download
     * @return int|false Attachment ID on success, false on failure
     */
    private function downloadImage($imageUrl)
    {
        if (!function_exists('media_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        // Check if allow_url_fopen is enabled
        if (!ini_get('allow_url_fopen')) {
            return false;
        }

        // Check uploads directory permissions
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['path'])) {
            return false;
        }

        // Download the image
        $tmp = download_url($imageUrl);

        if (is_wp_error($tmp)) {
            return false;
        }

        // Check if temporary file was actually created and has content
        if (!file_exists($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            return false;
        }

        // Validate file extension from URL
        $file_name = basename(parse_url($imageUrl, PHP_URL_PATH));
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (empty($file_ext) || !in_array($file_ext, $allowed_extensions)) {
            // Try to get file type from downloaded file
            $file_type = wp_check_filetype($tmp);
            if (!$file_type['ext'] || !in_array($file_type['ext'], $allowed_extensions)) {
                @unlink($tmp);
                return false;
            }
            $file_name = 'judge-me-image-' . time() . '.' . $file_type['ext'];
        }

        $file_array = array(
            'name' => $file_name,
            'tmp_name' => $tmp
        );

        // Do the validation and storage stuff
        $id = media_handle_sideload($file_array, 0);

        // If error storing permanently, unlink
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        // Get the final file path for verification
        $attachment_path = get_attached_file($id);
        return $id;
    }

    /**
     * Get business info for WooCommerce Judge.me reviews
     *
     * @param array $rows Review rows
     * @param string $type Info type to retrieve
     * @return array Business info
     */
    public function getWooCommerceBusinessInfo($rows, $type)
    {
        $dataFields = json_decode(Arr::get($rows, '0.fields', '{}'), true);
        $source_id = Arr::get($dataFields, 'product_id');
        $imported_from = Arr::get($dataFields, 'imported_from', '');
        $product_handle = Arr::get($dataFields, 'product_handle', '');

        if (!empty($source_id) && defined('WC_VERSION')) {
            $product_handle = get_the_title($source_id);
        }

        $dataSource = [
            'handle' => $product_handle,
            'source_id' => $source_id,
            'is_imported' => true,
        ];

        // Handle reviews without product IDs
        if ($imported_from === 'judge-me') {
            if (!empty($source_id)) {
                return Review::getInternalBusinessInfo($type, $dataSource);
            } else {
                // Return default business info for reviews without product IDs
                return [
                    'name' => $product_handle ?: __('General Reviews', 'wp-social-ninja-pro'),
                    'handle' => $product_handle ?: 'general-reviews',
                    'source_id' => '',
                    'is_imported' => true,
                ];
            }
        }

        return [];
    }

    /**
     * Format Judge.me date string to MySQL datetime format
     * 
     * Handles various date formats from Judge.me exports:
     * - Y-m-d H:i:s T (2025-08-06 05:11:14 UTC)
     * - Y-m-d H:i:s (2025-08-06 05:11:14)
     * - Y-m-d H:i (2025-08-06 05:11)
     * - Y-m-d (2025-08-06)
     * 
     * @param string $dateString Date string from Judge.me
     * @return string Formatted date string for MySQL
     */
    private function formatDate($dateString)
    {
        if (empty($dateString)) {
            return date('Y-m-d H:i:s');
        }

        // Handle different date formats from Judge.me
        foreach (self::VALID_DATE_FORMATS as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // If no format matches, try strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return date('Y-m-d H:i:s');
    }
}
