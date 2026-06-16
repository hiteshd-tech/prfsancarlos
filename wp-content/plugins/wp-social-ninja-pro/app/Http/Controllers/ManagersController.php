<?php

namespace WPSocialReviewsPro\App\Http\Controllers;

use WPSocialReviews\App\Http\Controllers\Controller;
use WPSocialReviews\Framework\Request\Request;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\PermissionManager;


class ManagersController extends Controller
{
    public function getManagers(Request $request)
    {
        $query = new \WP_User_Query( array(
            'meta_key' => '_wpsn_has_role',
            'meta_value' => 1,
            'meta_compare' => '=',
        ) );

        $managers = [];

        foreach ($query->get_results() as $user)
        {
            $managers[] = [
                'id' => $user->ID,
                'email' => $user->user_email,
                'permissions' => PermissionManager::getUserPermissions($user)
            ];
        }

        return [
            'managers' => [
                'data' => $managers,
                'total' => $query->get_total()
            ],
            'permissions' => PermissionManager::getReadablePermissions()
        ];

    }

    public function addManagers(Request $request)
    {
        $manager = $request->get('formData');
        $email = Arr::get($manager, 'email');
        $user = get_user_by('email', $email);

        if(!$user) {
            return $this->sendError([
                'message' => __('Associate user could not be found with this email', 'wp-social-ninja-pro')
            ], 423);
        }

        $permissions = Arr::get($manager, 'permissions', []);


        PermissionManager::attachPermissions($user, $permissions);

        update_user_meta($user->id, '_wpsn_has_role', 1);

        return $this->sendSuccess([
            'message' => __('Manager has been added', 'wp-social-ninja-pro')
        ]);

    }

    public function updateManagers(Request $request)
    {
        $formData = $request->get('formData');
        $email = Arr::get($formData, 'email');

        $user = get_user_by('email', $email);

        if(!$user) {
            return $this->sendError([
                'message' => __('Associate user could not be found with this email', 'wp-social-ninja-pro')
            ], 423);
        }

        $permissions = Arr::get($formData, 'permissions', []);

        PermissionManager::attachPermissions($user, $permissions);

        update_user_meta($user->id, '_wpsn_has_role', 1);

        return $this->sendSuccess([
            'message' => __('Manager has been updated', 'wp-social-ninja-pro')
        ]);
    }

    public function removeManagers(Request $request, $id)
    {
        $user = get_user_by('ID', $id);

        if(!$user) {
            return $this->sendError([
                'message' => __('Associate user could not be found with this email', 'wp-social-ninja-pro')
            ], 423);
        }

        PermissionManager::attachPermissions($user, []);

        update_user_meta($user->id, '_wpsn_has_role', 0);

        return $this->sendSuccess([
            'message' => __('Manager has been deleted', 'wp-social-ninja-pro')
        ]);

    }

}