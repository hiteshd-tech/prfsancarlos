<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;


class ScriptsHandler
{

    public function loadSwiperScripts()
    {
        if (!wp_script_is('bricks-swiper', 'enqueued')) {
            wp_enqueue_script('swiper', WPSOCIALREVIEWS_PRO_URL . 'assets/libs/swiper/swiper-bundle.min.js', array('jquery'),
                WPSOCIALREVIEWS_PRO_VERSION, true);
        }

        wp_enqueue_style(
            'swiper',
            WPSOCIALREVIEWS_PRO_URL . 'assets/libs/swiper/swiper-bundle.min.css',
            array(),
            WPSOCIALREVIEWS_PRO_VERSION
        );
    }
}