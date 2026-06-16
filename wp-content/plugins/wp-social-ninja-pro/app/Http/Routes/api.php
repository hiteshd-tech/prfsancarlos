<?php

/**
 * @var $router WPFluent\Http\Router
 */

use WPSocialReviewsPro\App\Http\Controllers\ManagersController;
use WPSocialReviewsPro\App\Http\Controllers\SettingsController;
use WPSocialReviewsPro\App\Http\Controllers\CustomSources\CustomSourcesController;

$router->prefix('pro')->withPolicy('SettingsPolicy')->group(function ($router) {
    
    $router->group(['prefix' => 'settings'], function ($router) {
        $router->get('/managers', [ManagersController::class, 'getManagers']);
        $router->post('/managers', [ManagersController::class, 'addManagers']);
        $router->put('/managers', [ManagersController::class, 'updateManagers']);
        $router->delete('/managers/{id}', [ManagersController::class, 'removeManagers'])->int('id');


        $router->get('/get-reviews/qr-code', 'SettingsController@getReviewCollectionQrCodes');
        $router->post('/get-reviews/qr-code', 'SettingsController@createReviewCollectionQrCode');
        $router->put('/get-reviews/qr-code/{id}', 'SettingsController@updateReviewCollectionQrCode')->int('id');
        $router->delete('/get-reviews/qr-code/{id}', 'SettingsController@deleteReviewCollectionQrCode');
        $router->get('/get-reviews/get-review-collection-platforms', 'SettingsController@getReviewCollectionPlatforms');

        // WooCommerce Batch Import
        $router->post('/woocommerce/import-reviews', [SettingsController::class, 'triggerWooImport']);
        $router->post('/woocommerce/restart-import-reviews', [SettingsController::class, 'restartWooImport']);
        $router->get('/woocommerce/import-progress', [SettingsController::class, 'getWooImportProgress']);

        // $router->get('/managers', 'WPSocialReviewsPro\App\Http\Controllers\ManagersController@getManagers');
//        $router->post('/managers', 'WPSocialReviewsPro\App\Http\Controllers\ManagersController@addManagers');
//        $router->put('/managers', 'WPSocialReviewsPro\App\Http\Controllers\ManagersController@updateManagers');
//        $router->delete('/managers/{id}', 'WPSocialReviewsPro\App\Http\Controllers\ManagersController@removeManagers')->int('id');
    });

});

$router->prefix('pro/custom-sources')->withPolicy('CustomSourcesPolicy')->group(function ($router) {
    $router->get('/', [CustomSourcesController::class, 'index']);
    $router->post('/', [CustomSourcesController::class, 'store']);
    $router->delete('/', [CustomSourcesController::class, 'delete']);

    $router->get('/{id}', [CustomSourcesController::class, 'getSettings'])->int('id');
    $router->post('/{id}/settings', [CustomSourcesController::class, 'saveSettings'])->int('id');

    $router->get('forms/templates', [CustomSourcesController::class, 'getTemplates']);
});

