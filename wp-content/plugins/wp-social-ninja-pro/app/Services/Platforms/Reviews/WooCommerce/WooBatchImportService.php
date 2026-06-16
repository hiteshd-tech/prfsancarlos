<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Models\Review;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Batch Import Service
 * Handles the batch import process for WooCommerce reviews using Action Scheduler
 */
class WooBatchImportService
{
    // Constants
    private const DEFAULT_BATCH_SIZE = 100;
    private const DEFAULT_OFFSET_TIME = 0;
    private const RETRY_OFFSET_TIME = 10;
    private const MAX_RETRIES = 3;
    private const SETTINGS_OPTION_KEY = 'wpsr_reviews_woocommerce_settings';
    private const ACTION_HOOK = 'wpsr_import_woo_reviews_batch';
    private const ACTION_GROUP = 'wpsr_woo_import';

    /**
     * Initialize the service
     */
    public function init()
    {
        add_action(self::ACTION_HOOK, array($this, 'processBatch'));
    }

    /**
     * Check if Action Scheduler is available
     */
    private function isActionSchedulerAvailable()
    {
        return function_exists('as_schedule_single_action') && class_exists('ActionScheduler');
    }

    /**
     * Get service instance (singleton)
     */
    private static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Start import with better fallback handling
     */
    public function startImport()
    {
        try {

            // Check if already running
            if ($this->isImportAlreadyRunning()) {
                return false;
            }

            // Clear any existing scheduled actions
            $this->clearScheduledActions();

            // Get total reviews
            $totalReviews = $this->getTotalReviewsCount();

            if ($totalReviews === 0) {
                return false;
            }

            // Initialize progress
            $this->initializeProgress($totalReviews);

            // Try Action Scheduler first, fallback to immediate processing
            if ($this->isActionSchedulerAvailable()) {
                $this->scheduleFirstBatch();

                // Wait a moment and check if it started processing
                sleep(2);
                $progress = $this->getProgress();
                $offset = (int) Arr::get($progress, 'offset', 0);

                // If no progress after 2 seconds, process immediately
                if ($offset === 0) {
                    $this->processImmediately();
                }
            } else {
                $this->processImmediately();
            }

            return true;

        } catch (\Exception $e) {
            $this->handleError('Failed to start import', $e);
            return false;
        }
    }

    /**
     * Check if import is already running
     */
    private function isImportAlreadyRunning()
    {
        $progress = $this->getProgress();

        // Check progress status
        if ($this->isImportActive($progress)) {
            // Also check if there are pending actions
            if ($this->isActionSchedulerAvailable()) {
                $pending_actions = as_get_scheduled_actions([
                    'hook' => self::ACTION_HOOK,
                    'group' => self::ACTION_GROUP,
                    'status' => 'pending',
                    'per_page' => 1
                ]);

                return !empty($pending_actions);
            }
            return true;
        }

        return false;
    }

    /**
     * Process multiple batches in single execution
     */
    public function processBatch()
    {
        try {
            $config = $this->getBatchConfig();
            $progress = $this->getProgress();

            $offset = (int) Arr::get($progress, 'offset', 0);
            $total = (int) Arr::get($progress, 'total', 0);

            // Safety limit to prevent runaway loops
            $maxBatches = apply_filters('wpsocialreviews/max_batches_per_run', 50);
            $batchCount = 0;

            while ($offset < $total && $batchCount < $maxBatches) {
                $result = $this->processReviewsBatch($config, $progress);

                if ($result['is_empty']) {
                    // No more reviews to process
                    $this->completeImport();
                    return;
                }

                // Update progress
                $this->updateProgress($result, $progress);

                // IMPORTANT: Refresh progress and offset after update
                $progress = $this->getProgress();
                $offset = (int) Arr::get($progress, 'offset', 0);
                $total = (int) Arr::get($progress, 'total', 0); // Refresh in case total changed

                $batchCount++;

                // Optional: Add small delay if needed (usually not required)
                // usleep(100000); // 0.1s
            }

            // If we broke due to batch limit, schedule next batch
            if ($offset < $total) {
                $this->scheduleNextBatch();
            } else {
                $this->completeImport();
            }

        } catch (\Exception $e) {
            $this->handleBatchError($e);
        }
    }

    /**
     * Get batch configuration with performance optimizations
     */
    private function getBatchConfig()
    {
        return [
            'batch_size' => apply_filters('wpsocialreviews/woo_import_batch_size', self::DEFAULT_BATCH_SIZE), // Very large batch size
            'offset_time' => apply_filters('wpsocialreviews/woo_import_batch_offset_time', self::DEFAULT_OFFSET_TIME),
            'retry_offset_time' => apply_filters('wpsocialreviews/woo_import_retry_offset_time', self::RETRY_OFFSET_TIME),
            'max_retries' => apply_filters('wpsocialreviews/woo_import_max_retries', self::MAX_RETRIES),
            'use_bulk_insert' => apply_filters('wpsocialreviews/woo_import_use_bulk_insert', true)
        ];
    }

    /**
     * Process reviews batch with bulk operations
     */
    private function processReviewsBatch($config, $progress)
    {
        $offset = (int) Arr::get($progress, 'offset', 0);
        $batchSize = $config['batch_size'];

        $reviews = WooCommerceHelper::getWooReviewsBatch($offset, $batchSize);
        if (empty($reviews)) {
            return [
                'processed_count' => 0,
                'success_count' => 0,
                'product_ids' => [],
                'is_empty' => true
            ];
        }

        // Bulk check for existing reviews (single query instead of multiple)
        $reviewIds = array_column($reviews, 'comment_ID');
        $existingReviews = $this->getExistingReviewIds($reviewIds);
        $existingReviewsSet = array_flip($existingReviews); // Use isset() for faster lookup

        $bulkInsertData = [];
        $productIds = [];
        $successCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($reviews as $review) {
            try {
                $productId = $review->comment_post_ID;

                // Fast lookup using isset instead of in_array
                if (isset($existingReviewsSet[$review->comment_ID])) {
                    $skippedCount++;
                    continue;
                }

                // Prepare data for bulk insert instead of individual imports
                $reviewData = $this->prepareReviewData($review);
                if ($reviewData) {
                    $bulkInsertData[] = $reviewData;
                    $successCount++;
                    if (!in_array($productId, $productIds)) {
                        $productIds[] = $productId;
                    }
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        // Bulk insert all reviews at once
        if (!empty($bulkInsertData)) {
            $this->bulkInsertReviews($bulkInsertData);
        }

        return [
            'processed_count' => count($reviews),
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'failed_count' => $failedCount,
            'product_ids' => $productIds,
            'is_empty' => false
        ];
    }

    private function updateBusinessInfoToIncludeAllProducts()
    {
        // Get all product IDs
        $productIds = $this->getAllProductIds();

        if (empty($productIds)) {
            return;
        }

        update_option('_wpsn_ids', $productIds, false);

        // Update business information for each product
        foreach ($productIds as $productId) {
            (new WooCommerce())->verifyCredential($productId);
        }
    }

    private function getAllProductIds()
    {
        $product_ids = get_posts(array(
            'post_type' => 'product',
            'numberposts' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));

        return $product_ids;
    }

    /**
     * Prepare review data for bulk insert
     */
    private function prepareReviewData($review)
    {
        try {
            // Use WooCommerceHelper to format the data but don't insert yet
            return WooCommerceHelper::formatReviewForInsert($review);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Bulk insert reviews using Review model
     */
    private function bulkInsertReviews($reviewsData)
    {
        if (empty($reviewsData)) {
            return;
        }

        try {
            // Process in chunks to avoid memory issues
            $chunks = array_chunk($reviewsData, 500);
            foreach ($chunks as $chunk) {
                Review::insert($chunk);
            }

        } catch (\Exception $e) {
            // Fallback to individual inserts if bulk fails
            $this->fallbackIndividualInserts($reviewsData);
        }
    }

    /**
     * Fallback to individual inserts if bulk insert fails
     */
    private function fallbackIndividualInserts($reviewsData)
    {
        foreach ($reviewsData as $reviewData) {
            try {
                Review::create($reviewData);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Update import progress
     */
    private function updateProgress($result, $currentProgress)
    {
        $settings = $this->getSettings();
        $offset = (int) Arr::get($currentProgress, 'offset', 0);

        // Store product IDs
        if (!empty($result['product_ids'])) {
            $this->storeProductIds($result['product_ids']);
        }

        // Update progress
        $newOffset = $offset + $result['processed_count'];
        $settings['reviews_import']['offset'] = $newOffset;
        $settings['reviews_import']['updated_at'] = time();
        $settings['reviews_import']['last_batch_size'] = $result['processed_count'];
        $settings['reviews_import']['last_success_count'] = $result['success_count'];
        $settings['reviews_import']['last_skipped_count'] = Arr::get($result, 'skipped_count', 0);
        $settings['reviews_import']['last_failed_count'] = Arr::get($result, 'failed_count', 0);

        // Reset retries on successful batch (including skipped reviews)
        if (!$result['is_empty'] && ($result['success_count'] > 0 || $result['skipped_count'] > 0)) {
            $settings['reviews_import']['retries'] = 0;
        }

        $this->saveSettings($settings);
    }


    /**
     * Complete import process
     */
    private function completeImport()
    {
        $settings = $this->getSettings();
        $settings['reviews_import']['completed'] = true;
        $settings['reviews_import']['completed_at'] = time();
        $settings['reviews_import']['updated_at'] = time();
        $this->saveSettings($settings);

        $this->updateBusinessInfoToIncludeAllProducts();

        // Clear any remaining scheduled actions to prevent further execution
        $this->clearScheduledActions();
    }

    /**
     * Initialize progress tracking
     */
    private function initializeProgress($totalReviews)
    {
        $settings = $this->getSettings();
        $settings['reviews_import'] = [
            'total' => (int) $totalReviews,
            'offset' => 0,
            'completed' => false,
            'failed' => false,
            'started_at' => time(),
            'updated_at' => time(),
            'retries' => 0,
            'last_batch_size' => 0,
            'last_success_count' => 0,
            'last_failed_count' => 0
        ];
        $settings['product_ids'] = [];
        $this->saveSettings($settings);
    }

    /**
     * Schedule next batch
     */
    private function scheduleNextBatch($customOffset = null)
    {
        if (!$this->isActionSchedulerAvailable()) {
            $this->markAsFailed('Action Scheduler not available');
            return;
        }

        // Use minimal delay - just 1 second
        $offset = $customOffset ?? self::DEFAULT_OFFSET_TIME;
        $scheduledTime = time() + $offset;

        $action_id = as_schedule_single_action(
            $scheduledTime,
            self::ACTION_HOOK,
            [],
            self::ACTION_GROUP
        );

        if (!$action_id) {
            $this->markAsFailed('Failed to schedule next batch with Action Scheduler');
        }
    }

    /**
     * Schedule first batch
     */
    private function scheduleFirstBatch()
    {
        if (!$this->isActionSchedulerAvailable()) {
            $this->markAsFailed('Action Scheduler not available');
            return;
        }

        // Schedule immediately with no delay
        $action_id = as_schedule_single_action(
            time(),
            self::ACTION_HOOK,
            [],
            self::ACTION_GROUP
        );

        if (!$action_id) {
            $this->markAsFailed('Failed to schedule first batch with Action Scheduler');
        }
    }

    /**
     * Clear scheduled actions
     */
    private function clearScheduledActions()
    {
        // Clear Action Scheduler actions only
        if ($this->isActionSchedulerAvailable()) {
            as_unschedule_all_actions(self::ACTION_HOOK, [], self::ACTION_GROUP);
        }
    }

    /**
     * Handle batch errors
     */
    private function handleBatchError(\Exception $e)
    {
        $settings = $this->getSettings();
        $retries = (int) Arr::get($settings, 'reviews_import.retries', 0);
        $newRetries = $retries + 1;

        if ($newRetries <= self::MAX_RETRIES) {
            $settings['reviews_import']['retries'] = $newRetries;
            $settings['reviews_import']['last_error'] = $e->getMessage();
            $settings['reviews_import']['last_error_at'] = time();
            $this->saveSettings($settings);

            $this->scheduleNextBatch(self::RETRY_OFFSET_TIME);
        } else {
            $this->markAsFailed('Maximum retries exceeded after batch error: ' . $e->getMessage());
        }
    }

    /**
     * Mark import as failed
     */
    private function markAsFailed($reason)
    {
        $settings = $this->getSettings();
        $settings['reviews_import']['failed'] = true;
        $settings['reviews_import']['failed_at'] = time();
        $settings['reviews_import']['failure_reason'] = $reason;
        $settings['reviews_import']['updated_at'] = time();
        $this->saveSettings($settings);
    }

    /**
     * Handle general errors
     */
    private function handleError($message, \Exception $e)
    {
        $settings = $this->getSettings();
        $settings['reviews_import']['failed'] = true;
        $settings['reviews_import']['failure_reason'] = $message . ': ' . $e->getMessage();
        $settings['reviews_import']['failed_at'] = time();
        $this->saveSettings($settings);
    }

    /**
     * Get import progress
     */
    public function getProgress()
    {
        $settings = $this->getSettings();
        return Arr::get($settings, 'reviews_import', []);
    }

    /**
     * Get detailed progress information
     */
    public function getDetailedProgress()
    {
        try {
            $progress = $this->getProgress();

            // Ensure progress is an array
            if (!is_array($progress)) {
                $progress = [];
            }

            $total = (int) Arr::get($progress, 'total', 0);
            $processed = (int) Arr::get($progress, 'offset', 0);
            $completed = (bool) Arr::get($progress, 'completed', false);
            $failed = (bool) Arr::get($progress, 'failed', false);
            $started_at = (int) Arr::get($progress, 'started_at', null);
            $updated_at = (int) Arr::get($progress, 'updated_at', null);

            // Cap processed at total
            $processed = min($processed, $total);

            $percentage = $total > 0 ? round(($processed / $total) * 100, 2) : 0;

            // Calculate speed and ETA
            $speed = 0;
            $eta = null;

            if ($started_at && $processed > 0 && !$completed && !$failed) {
                $elapsed = time() - $started_at;
                $speed = $elapsed > 0 ? round($processed / $elapsed, 2) : 0;
                $remaining = $total - $processed;
                if ($speed > 0) {
                    $eta = round($remaining / $speed);
                }
            }

            // Get Action Scheduler status
            $scheduler_status = $this->getActionSchedulerStatus();

            return [
                'total' => $total,
                'processed' => $processed,
                'completed' => $completed,
                'failed' => $failed,
                'percentage' => $percentage,
                'remaining' => max(0, $total - $processed),
                'speed' => $speed,
                'eta' => $eta,
                'is_active' => !$completed && !$failed && $processed < $total,
                'updated_at' => $updated_at ? wp_date('d F Y, h:i:s A', $updated_at) : null,
                'retries' => (int) Arr::get($progress, 'retries', 0),
                'failure_reason' => Arr::get($progress, 'failure_reason', ''),
                'last_batch_size' => (int) Arr::get($progress, 'last_batch_size', 0),
                'last_success_count' => (int) Arr::get($progress, 'last_success_count', 0),
                'last_skipped_count' => (int) Arr::get($progress, 'last_skipped_count', 0),
                'scheduler_status' => $scheduler_status
            ];
        } catch (\Exception $e) {
            // Return safe defaults if there's an error
            return [
                'total' => 0,
                'processed' => 0,
                'completed' => false,
                'failed' => true,
                'percentage' => 0,
                'remaining' => 0,
                'speed' => 0,
                'eta' => null,
                'is_active' => false,
                'updated_at' => null,
                'retries' => 0,
                'failure_reason' => 'Error retrieving progress: ' . $e->getMessage(),
                'last_batch_size' => 0,
                'last_success_count' => 0,
                'last_skipped_count' => 0,
                'scheduler_status' => ['available' => false, 'pending_actions' => 0, 'running_actions' => 0, 'last_action' => null]
            ];
        }
    }

    /**
     * Store product IDs
     */
    private function storeProductIds($productIds)
    {
        $settings = $this->getSettings();

        if (!isset($settings['product_ids'])) {
            $settings['product_ids'] = [];
        }

        $settings['product_ids'] = array_unique(array_merge($settings['product_ids'], $productIds));
        $this->saveSettings($settings);
    }

    /**
     * Get total reviews count
     */
    private function getTotalReviewsCount()
    {
        return WooCommerceHelper::getWooReviewsCount();
    }

    /**
     * Get settings
     */
    private function getSettings()
    {
        return get_option(self::SETTINGS_OPTION_KEY, []);
    }

    /**
     * Save settings
     */
    private function saveSettings($settings)
    {
        update_option(self::SETTINGS_OPTION_KEY, $settings, false);
    }

    /**
     * Check if import is currently active
     */
    private function isImportActive($progress)
    {
        $completed = (bool) Arr::get($progress, 'completed', false);
        $failed = (bool) Arr::get($progress, 'failed', false);
        $offset = (int) Arr::get($progress, 'offset', 0);
        $total = (int) Arr::get($progress, 'total', 0);

        return !$completed && !$failed && $offset < $total;
    }

    /**
     * Static method to trigger import
     */
    public static function trigger()
    {
        $service = self::getInstance();
        return $service->startImport();
    }

    /**
     * Static method to get progress
     */
    public static function getImportProgress()
    {
        return self::getInstance()->getDetailedProgress();
    }

    /**
     * Static method to reset import
     */
    public static function resetImport()
    {
        $service = self::getInstance();

        // Clear scheduled actions
        $service->clearScheduledActions();

        // Reset progress
        $settings = $service->getSettings();
        $settings['reviews_import'] = [
            'total' => 0,
            'offset' => 0,
            'completed' => false,
            'failed' => false,
            'reset_at' => time()
        ];
        $service->saveSettings($settings);

        return true;
    }

    /**
     * Get Action Scheduler status with error handling
     */
    private function getActionSchedulerStatus()
    {
        try {
            if (!$this->isActionSchedulerAvailable()) {
                return [
                    'available' => false,
                    'pending_actions' => 0,
                    'running_actions' => 0,
                    'last_action' => null
                ];
            }

            $pending_actions = as_get_scheduled_actions([
                'hook' => self::ACTION_HOOK,
                'group' => self::ACTION_GROUP,
                'status' => 'pending',
                'per_page' => -1
            ]);

            $running_actions = as_get_scheduled_actions([
                'hook' => self::ACTION_HOOK,
                'group' => self::ACTION_GROUP,
                'status' => 'in-progress',
                'per_page' => -1
            ]);

            $completed_actions = as_get_scheduled_actions([
                'hook' => self::ACTION_HOOK,
                'group' => self::ACTION_GROUP,
                'status' => 'complete',
                'per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            // Fix the "Undefined offset: 0" error
            $last_action = null;
            if (!empty($completed_actions) && is_array($completed_actions) && count($completed_actions) > 0) {
                $first_action = reset($completed_actions);
                if ($first_action && method_exists($first_action, 'get_date')) {
                    $last_action = $first_action->get_date();
                }
            }

            return [
                'available' => true,
                'pending_actions' => is_array($pending_actions) ? count($pending_actions) : 0,
                'running_actions' => is_array($running_actions) ? count($running_actions) : 0,
                'last_action' => $last_action
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'pending_actions' => 0,
                'running_actions' => 0,
                'last_action' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimized existing review check using Review model
     */
    private function getExistingReviewIds($reviewIds)
    {
        if (empty($reviewIds)) {
            return [];
        }

        try {
            // Use Review model with chunking for better performance
            $chunks = array_chunk($reviewIds, 1000);
            $existingIds = [];

            foreach ($chunks as $chunk) {
                $chunkIds = Review::where('platform_name', 'woocommerce')
                    ->whereIn('review_id', $chunk)
                    ->pluck('review_id')
                    ->toArray();
                $existingIds = array_merge($existingIds, $chunkIds);
            }

            return $existingIds;

        } catch (\Exception $e) {
            return []; // Return empty array to allow processing
        }
    }

    /**
     * Process immediately without Action Scheduler
     */
    private function processImmediately()
    {
        $config = $this->getBatchConfig();
        $progress = $this->getProgress();
        $batchCount = 0;
        $maxBatches = 50; // Prevent infinite loops

        // Set time limit for large imports
        if (function_exists('set_time_limit')) {
            set_time_limit(300); // 5 minutes max
        }

        while ($batchCount < $maxBatches) {
            $batchCount++;

            $result = $this->processReviewsBatch($config, $progress);

            if ($result['is_empty']) {
                $this->completeImport();
                break;
            }

            $this->updateProgress($result, $progress);
            $progress = $this->getProgress();

            // Check if we've processed all reviews
            $offset = (int) Arr::get($progress, 'offset', 0);
            $total = (int) Arr::get($progress, 'total', 0);

            if ($offset >= $total) {
                $this->completeImport();
                break;
            }
        }

        // Safety check - if we hit max batches without completing
        if ($batchCount >= $maxBatches) {
            $this->markAsFailed('Maximum batch limit reached during immediate processing');
        }
    }
}
