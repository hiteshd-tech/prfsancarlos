<?php

namespace WPSocialReviewsPro\App\Http\Controllers\CustomSources;

use WPSocialReviews\App\Http\Controllers\Controller;
use WPSocialReviewsPro\App\Services\CustomSources\CustomSourceService;
use WPSocialReviewsPro\App\Services\CustomSources\ValidationService;
use WPSocialReviewsPro\App\Services\CustomSources\FluentFormsService;
use WPSocialReviews\Framework\Request\Request;
use WPSocialReviews\Framework\Support\Arr;

class CustomSourcesController extends Controller
{
    private CustomSourceService $customSourceService;
    private ValidationService $validationService;
    private FluentFormsService $fluentFormsService;

    public function __construct()
    {
        $this->customSourceService = new CustomSourceService();
        $this->validationService = new ValidationService();
        $this->fluentFormsService = new FluentFormsService();
    }

    /**
     * Get all custom sources
     */
    public function index(Request $request)
    {
        try {
            $params = [
                'search' => $request->get('search', ''),
                'filter' => $request->get('filter', ''),
                'per_page' => $request->get('per_page', 10),
                'page' => $request->get('page', 1)
            ];

            $result = $this->customSourceService->getSources($params);

            return [
                'message' => 'success',
                'items' => $result['items'],
                'all_valid_platforms' => $result['all_valid_platforms'],
                'total_items' => $result['total_items'],
            ];
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            // Sanitize and validate input data
            $data = $this->validationService->sanitize([
                'name' => $request->get('name'),
                'label' => $request->get('label'),
                'type' => $request->get('type', 'custom'),
                'form_id' => $request->get('form_id'),
                'is_manual_form' => $request->get('is_manual_form', false)
            ]);

            // Validate required fields
            $errors = $this->validationService->validateSourceCreation($data);
            if (!empty($errors)) {
                return $this->validationError($errors);
            }

            // Create the source using the service
            $result = $this->customSourceService->createSource($data);

            return [
                'message' => __('Custom source created successfully', 'wp-social-reviews'),
                'source_id' => $result['source_id']
            ];

        } catch (\Exception $e) {
            return $this->handleException($e, 423);
        }
    }

    public function delete(Request $request)
    {
        try {
            $ids = $request->get('ids');
            
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            
            if (empty($ids)) {
                return $this->handleException(new \Exception(__('No valid source IDs provided', 'wp-social-reviews')), 400);
            }

            $deletedCount = 0;
            $errors = [];
            
            foreach ($ids as $id) {
                try {
                    $this->customSourceService->deleteSource($id);
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = sprintf(__('Failed to delete source ID %d: %s', 'wp-social-reviews'), $id, $e->getMessage());
                }
            }
            
            if ($deletedCount > 0) {
                $message = $deletedCount === count($ids) 
                    ? sprintf(__('%d source(s) successfully deleted', 'wp-social-reviews'), $deletedCount)
                    : sprintf(__('%d of %d source(s) successfully deleted', 'wp-social-reviews'), $deletedCount, count($ids));
                
                $response = [
                    'message' => $message,
                    'deleted_count' => $deletedCount,
                    'total_count' => count($ids)
                ];
                
                if (!empty($errors)) {
                    $response['errors'] = $errors;
                }
                
                return $response;
            } else {
                return $this->handleException(new \Exception(__('No sources were deleted', 'wp-social-reviews')), 400);
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getSettings($sourceId)
    {
        try {
            return $this->customSourceService->getSourceSettings((int)$sourceId);
        } catch (\Exception $e) {
            return $this->handleException($e, 404);
        }
    }

    public function saveSettings(Request $request)
    {
        try {
            $sourceId = (int)$request->get('id');
            $settings = wp_unslash($request->get('settings', []));
            $settings = $this->validationService->sanitize($settings);

            // Validate settings
            $errors = $this->validationService->validateSettings($settings);
            if (!empty($errors)) {
                return $this->validationError($errors);
            }

            $this->customSourceService->saveSourceSettings($sourceId, $settings);

            return [
                'message' => __('Settings saved successfully', 'wp-social-reviews')
            ];
        } catch (\Exception $e) {
            return $this->handleException($e, 404);
        }
    }

    /**
     * Get reviews for a custom source
     */
    public function getReviews($sourceId)
    {
        try {
            return $this->customSourceService->getSourceReviews((int)$sourceId);
        } catch (\Exception $e) {
            return $this->handleException($e, 404);
        }
    }

    /**
     * Get available Fluent Forms templates
     */
    public function getTemplates()
    {
        try {
            return $this->fluentFormsService->getTemplates();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle exceptions and return appropriate error response
     */
    private function handleException(\Exception $e, int $defaultCode = 500): array
    {
        $message = $e->getMessage();
        $code = method_exists($e, 'getCode') && $e->getCode() > 0 ? $e->getCode() : $defaultCode;

        return wp_send_json_error(['message' => $message], $code);
    }

    /**
     * Handle validation errors
     */
    private function validationError(array $errors): array
    {
        return wp_send_json_error([
            'message' => __('Validation failed', 'wp-social-reviews'),
            'errors' => $errors
        ], 422);
    }
}