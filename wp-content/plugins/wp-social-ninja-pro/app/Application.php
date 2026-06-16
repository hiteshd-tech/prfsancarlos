<?php

namespace WPSocialReviewsPro\App;

class Application
{
    public function __construct($app)
    {
        $this->boot($app);
    }

    public function boot($app)
    {
        $router = $app->router;

        require_once WPSOCIALREVIEWS_PRO_DIR . 'app/Hooks/actions.php';
        require_once WPSOCIALREVIEWS_PRO_DIR . 'app/Hooks/filters.php';
        require_once WPSOCIALREVIEWS_PRO_DIR . 'app/Http/Routes/api.php';
    }
}