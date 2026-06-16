<?php

namespace WPSocialReviewsPro\App\Traits;

Trait LoadView
{
    public function loadView($fileName, $data)
    {
        // normalize the filename
        $fileName = str_replace(array('../', './'), '', $fileName);
        $basePath = WPSOCIALREVIEWS_PRO_DIR . 'app/Views/public/';


        $filePath = $basePath . $fileName . '.php';

        extract($data);
        ob_start();
        include $filePath;

        return ob_get_clean();
    }
}