<?php

namespace WPSocialReviewsPro\App\Http\Controllers;

use WPSocialReviews\App\Http\Controllers\Controller;
use WPSocialReviews\App\Services\PermissionManager;
use WPSocialReviews\Framework\Request\Request;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooProductAdmin;
use WPSocialReviews\Framework\Support\Arr;

class SettingsController extends Controller
{

    /**
     * Required permissions for WooCommerce operations
     */
    private const REQUIRED_PERMISSIONS = [
        'wpsn_full_access',
        'wpsn_manage_platforms',
        'wpsn_manage_reviews',
        'wpsn_feeds_platforms_settings'
    ];

    /**
     * Check if WooCommerce is active and user has required permissions
     * 
     * @return array|null Returns error response array if validation fails, null if passes
     */
    private function validateWooCommerceAccess()
    {
        // Check if WooCommerce is active
        if (!defined('WC_VERSION')) {
            return $this->sendError([
                'message' => __('WooCommerce plugin is not active. Please activate WooCommerce plugin to sync reviews.', 'wp-social-ninja-pro')
            ], 400);
        }

        // Check if WooCommerce has at least one product
        $product_count = wc_get_products(['limit' => 1, 'return' => 'ids']);
        if (empty($product_count)) {
            return $this->sendError([
                'message' => __('No WooCommerce products found. Please add at least one product to sync reviews.', 'wp-social-ninja-pro')
            ], 400);
        }

        // Check permissions
        if (!$this->hasRequiredPermissions()) {
            return $this->sendError([
                'message' => __('You do not have permission to perform this action.', 'wp-social-ninja-pro')
            ], 403);
        }

        return null;
    }

    /**
     * Check if current user has all required permissions
     * 
     * @return bool
     */
    private function hasRequiredPermissions()
    {
        foreach (self::REQUIRED_PERMISSIONS as $permission) {
            if (!PermissionManager::currentUserCan($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Trigger WooCommerce reviews batch import
     */
    public function triggerWooImport(Request $request)
    {
        // Validate access
        $validationError = $this->validateWooCommerceAccess();
        if ($validationError !== null) {
            return $validationError;
        }

        // Trigger the import
        try {
            WooProductAdmin::triggerWooImport();

            return [
                'success' => true,
                'message' => __('Review sync in progress…', 'wp-social-ninja-pro')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => __('Failed to trigger import: ', 'wp-social-ninja-pro') . $e->getMessage()
            ], 500);
        }
    }

    public function restartWooImport(Request $request)
    {
        // Validate access
        $validationError = $this->validateWooCommerceAccess();
        if ($validationError !== null) {
            return $validationError;
        }

        // Restart the import
        try {
            WooProductAdmin::resetImport();
            WooProductAdmin::triggerWooImport();
            return [
                'success' => true,
                'message' => __('Review sync restarted successfully.', 'wp-social-ninja-pro')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => __('Failed to restart import: ', 'wp-social-ninja-pro') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WooCommerce import progress
     */
    public function getWooImportProgress(Request $request)
    {
        // Validate access
        $validationError = $this->validateWooCommerceAccess();
        if ($validationError !== null) {
            return $validationError;
        }

        try {
            $progressData = WooProductAdmin::getImportProgress();
            $completed = Arr::get($progressData, 'completed', false);
            $message = $completed
                ? __('Import completed successfully.', 'wp-social-ninja-pro')
                : __('Import progress retrieved successfully.', 'wp-social-ninja-pro');

            return [
                'success' => true,
                'message' => $message,
                'data' => $progressData
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => __('Failed to get import progress: ', 'wp-social-ninja-pro') . $e->getMessage()
            ], 500);
        }
    }
}