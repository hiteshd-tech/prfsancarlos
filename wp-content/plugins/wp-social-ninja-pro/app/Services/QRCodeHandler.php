<?php

namespace WPSocialReviewsPro\App\Services;

use WPSocialReviews\App\Services\GlobalSettings;

class QRCodeHandler
{

    public static function registerHooks(){
        add_action('template_redirect', [static::class, 'templateRedirect']);
        static::registerFilters();
    }

    public static function registerFilters(){
        add_filter('query_vars', function ($vars) {
            $vars[] = 'wpsr_qr_code';
            return $vars;
        });
    }

    public static function templateRedirect() {
        $qrCodeId = get_query_var('wpsr_qr_code');
        // Only proceed if $qrCodeId is a non-empty string and a valid array key
        if (is_string($qrCodeId) && $qrCodeId !== '') {
            $qrCodes = (new GlobalSettings())->getGlobalSettings('advance_settings.qr_codes');
            // Ensure $qrCodes is an array and $qrCodeId exists as a key
            if (is_array($qrCodes) && array_key_exists($qrCodeId, $qrCodes)) {
                $qrCode = $qrCodes[$qrCodeId];

                // increment the 'scan_counter' value
                $scanCounter = (int)($qrCode['scan_counter'] ?? 0);
                $scanCounter++;
                $qrCode['scan_counter'] = $scanCounter;
                $qrCodes[$qrCodeId] = $qrCode;

                (new GlobalSettings())->setGlobalSettingsKeyValue('advance_settings.qr_codes', $qrCodes);

                $redirectUrl = $qrCode['url'] ?? null;
                if($redirectUrl === 'custom-url') {
                    $redirectUrl = $qrCode['custom_url'] ?? null;
                }
                if ($redirectUrl) {
                    wp_redirect($redirectUrl);
                    exit;
                } else {
                    wp_die(__('Invalid QR code.', 'wp-social-ninja-pro'));
                }
            } else {
                wp_die(__('Invalid QR code.', 'wp-social-ninja-pro'));
            }
        }
    }
}