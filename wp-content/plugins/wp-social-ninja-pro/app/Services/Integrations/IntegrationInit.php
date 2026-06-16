<?php
namespace WPSocialReviewsPro\App\Services\Integrations;

use WPSocialReviewsPro\App\Services\Integrations\FluentForms\SocialNinjaRatingElement;

if (!defined('ABSPATH')) {
    exit;
}

class IntegrationInit
{

    public function init()
    {
        $this->registerIntegrations();
    }

    public function registerIntegrations()
    {
        if(defined('FLUENTFORM')){
            new SocialNinjaRatingElement();
        }
    }
}