<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;

use WPSocialReviewsPro\App\Services\Platforms\Reviews\Aliexpress;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\Amazon;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\Booking;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\FacebookBusiness;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\Tripadvisor;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\Trustpilot;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\YelpBusiness;
use WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerCron;

class PlatformHandlerPro
{
    public function register()
    {

        (new Aliexpress())->registerHooks();
        (new Amazon())->registerHooks();
        (new Booking())->registerHooks();
        (new FacebookBusiness())->registerHooks();
        (new Tripadvisor())->registerHooks();
        (new Trustpilot())->registerHooks();
        (new YelpBusiness())->registerHooks();
        (new AIReviewSummarizerCron())->registerHooks();

        if (defined('FLUENTFORM')) {
            new \WPSocialReviewsPro\App\Services\Platforms\Reviews\Fluentform(wpFluentForm());
        }

        if (defined('WC_VERSION')) {
            add_action('woocommerce_after_register_post_type', [(new \WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooCommerce()), 'registerHooks']);
            (new \WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooProductAdmin())->init();
        }
    }
}