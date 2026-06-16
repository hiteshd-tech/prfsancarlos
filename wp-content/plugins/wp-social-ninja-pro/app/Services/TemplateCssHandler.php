<?php

namespace WPSocialReviewsPro\App\Services;

use WPSocialReviews\Framework\Support\Arr;

class TemplateCssHandler
{
    public function getTemplateConfig($templateId)
    {
        $encoded_meta = get_post_meta($templateId, '_wpsr_template_config', true);
        return json_decode($encoded_meta, true);
    }

    public function getCss($templateId)
    {
        return get_post_meta($templateId, '_wpsr_template_css', true);
    }

    public function saveCss($settings = [], $postId = null)
    {
        $styles_config = Arr::get($settings, 'styles_config');

        $styles = Arr::get($settings, 'styles');
        $responsive_styles = Arr::get($settings, 'responsive_styles');

        $css = [];
        if($styles){
            $css['styles'] = implode(' ', $styles);
        }

        if($responsive_styles){
            $css['responsive_styles'] = implode(' ', $responsive_styles);
        }

        if($styles_config){
            update_post_meta($postId, '_wpsr_template_styles_config', json_encode($styles_config));
            if($css){
                update_post_meta($postId, '_wpsr_template_css', $css);
            }
        }
    }

    public function renderTemplateCss($templateId = null)
    {
        $cssMeta = $this->getCss($templateId);
        if($cssMeta){
            $action = false;

            if(!did_action('wp_head')) {
                $action = 'wp_head';
            } else if(!did_action('wp_footer')) {
                $action = 'wp_footer';
            }

            if ($action && !did_action($action)) {
                add_action($action, function () use ($cssMeta) {
                    $this->addStyleTag($cssMeta);
                }, 99);
            } else {
                $this->addStyleTag($cssMeta);
                return true;
            }
        }
    }

    protected function addStyleTag($cssMeta)
    {
        $styles = Arr::get($cssMeta, 'styles');
        $responsive_styles = Arr::get($cssMeta, 'responsive_styles');
        ?>
        <style id="wp-social-reviews" type="text/css">
            <?php
                if($styles){
                  echo $this->escCss($styles);
                }
                if ($responsive_styles) {
                    echo $this->escCss($responsive_styles);
                }
            ?>
        </style>
        <?php
    }

    protected function escCss($css)
    {
        return preg_match('#</?\w+#', $css) ? '' : $css;
    }

}