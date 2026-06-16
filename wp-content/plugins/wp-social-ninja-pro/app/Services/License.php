<?php

namespace WPSocialReviewsPro\App\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ActionHooks Class
 * @since 1.1.4
 */
class License
{
    public function init()
    {

        $licenseManager = (new \WPSocialReviewsPro\App\Services\Libs\PluginManager\FluentLicensing())->register([
            'version'      => WPSOCIALREVIEWS_PRO_VERSION, // Current version of your plugin
            'item_id'      => 7560868, // Product ID from FluentCart
            'settings_key' => '__wpsr_license',
            'plugin_title' => 'WP Social Ninja Pro',
            'basename'     => 'wp-social-ninja-pro/wp-social-ninja-pro.php', // Plugin basename (e.g., 'your-plugin/your-plugin.php')
            'api_url'      => 'https://fluentapi.wpmanageninja.com/', // The API URL for license verification. Normally your store URL
            'store_url'    => 'https://wpmanageninja.com/', // Your store URL
            'purchase_url' => 'https://wpsocialninja.com/', // Purchase URL
            'activate_url' => admin_url('admin.php?page=wpsocialninja.php#/settings/license-management'),
            'show_check_update' => true
        ]);

        add_filter('wpsr_get_license', function ($response, $request) use ($licenseManager) {
            $data = $licenseManager->getStatus(true);

            $status = $data['status'];

            if ($status == 'expired') {
                $data['renew_url'] = $licenseManager->getRenewUrl();
            }

            $data['purchase_url'] = $licenseManager->getConfig('purchase_url');

            unset($data['license_key']);
            return $data;
        }, 10, 2);

        add_filter('wpsr_activate_license', function ($response, $request) use ($licenseManager) {
            $licenseKey = $request->get('license_key');
            $licenseResponse = $licenseManager->activate($licenseKey);

            if(is_wp_error($licenseResponse)) {
                return $licenseResponse;
            }

            return [
                'license_data' => $licenseResponse,
                'message' => __('Your license key has been successfully updated', 'wp-social-ninja-pro')
            ];
        }, 10, 2);

        add_filter('wpsr_deactivate_license', function ($response, $request) use ($licenseManager) {
            $remoteResponse = $licenseManager->deactivate();
            if(is_wp_error($remoteResponse)) {
                return $remoteResponse;
            }

            unset($remoteResponse['license_key']);

            return [
                'license_data' => $remoteResponse,
                'message' => __('Your license key has been successfully deactivated', 'fluentcampaign-pro')
            ];
        }, 10, 2);

        add_action('admin_init', function () use ($licenseManager) {
            $licenseMessage = $licenseManager->getLicenseMessages();
            if ($licenseMessage) {
                add_action('admin_notices', function () use ($licenseMessage) {
                    $class = 'notice notice-error fc_message';
                    $message = $licenseMessage['message'];
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
                });
            }
        });
    }
}
