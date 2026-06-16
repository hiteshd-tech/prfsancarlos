<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\App\Models\Review;
use WPSocialReviews\App\Services\Widgets\Helper;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle WooCommerce Admin Settings
 * @since 3.11.0
 */
class WooProductAdmin
{
    private $postMetaName = 'wpsr-settings-woo';
    private $batchImportService;

    public function init()
    {
        /*
         * Admin Product Edit Page Actions
         */
        add_action('woocommerce_product_write_panel_tabs', array($this, 'addPanelTitle'));
        add_action('woocommerce_product_data_panels', array($this, 'addPanelSettings'));
        add_action('save_post_product', array($this, 'saveMetaData'));

        add_action('wp_insert_comment', array($this, 'insertProductReview'), 10, 2);
        add_action('transition_comment_status', array($this, 'approveCommentCallback'), 10, 3);

        /*
         * Initialize Batch Import Service
         */
        $this->batchImportService = new WooBatchImportService();
        $this->batchImportService->init();
    }

    public function addPanelTitle()
    {
        $logo = defined('WPSOCIALREVIEWS_URL') ? WPSOCIALREVIEWS_URL . 'assets/images/icon/wp-social-icon.png' : '';

        if (!is_admin()) {
            return;
        }
        ?>
        <li class="wpsr-wc-settings-tab hide_if_grouped">
            <a href="#wpsr_wc_tab">
                <img src="<?php echo esc_url($logo); ?>" alt="WP Social Ninja">
                <span><?php _e('WP Social Ninja', 'wp-social-ninja-pro'); ?></span>
            </a>
        </li>
        <style>
            .wpsr-wc-settings-tab a:before {
                content: none;
                display: none;
            }

            .wpsr-wc-settings-tab a {
                display: flex !important;
                align-items: center;
            }

            .wpsr-wc-settings-tab img {
                width: 15px;
            }
        </style>
        <?php
    }

    public function addPanelSettings()
    {
        if (!is_admin()) {
            return '';
        }

        global $post;
        $post_id = isset($post) && isset($post->ID) ? $post->ID : null;

        $defaults = [
            'selected_template' => null,
            'hide_reviews_count' => '',
            'hide_reviews_title' => 'yes',
            'reviews_widgets_placement' => 'display_inside_reviews_tab',
            'reviews_form' => 'woocommerce', // Default to social ninja form
        ];

        $platforms = ['twitter', 'youtube', 'instagram', 'tiktok'];
        $templates = Helper::getTemplates($platforms);
        $settings = wp_parse_args(get_post_meta($post_id, $this->postMetaName, true), $defaults);

        // Add an nonce field so we can check for it later.
        wp_nonce_field('wpsr_meta_box_woo', 'wpsr_meta_box_woo_nonce');
        ?>
        <div id="wpsr_wc_tab" class="panel woocommerce_options_panel wpsr-meta">
            <div class="wpsr_wc_tab_section_title">
                <h3 style="margin: 10px 0;">
                    <?php _e('WP Social Ninja Integration', 'wp-social-ninja-pro'); ?>
                </h3>
                <span><?php esc_html_e('You can choose the template that will be included in the product reviews section.', 'wp-social-ninja-pro'); ?></span>
            </div>

            <div class="options_group">
                <p class="form-field" style="padding: 5px 20px 5px 212px!important;">
                    <label style="margin: 0 0 0 -200px; width: 200px;"
                        for="_low_stock_amount"><?php esc_html_e('Choose a Review Template', 'wp-social-ninja-pro'); ?></label>
                    <select placeholder="<?php esc_attr_e('Select Template', 'wp-social-ninja-pro'); ?>" style="width:100%;"
                        class="select short" name="<?php echo esc_attr($this->postMetaName); ?>[selected_template]"
                        id="wpsr_templates">
                        <?php foreach ($templates as $id => $template): ?>
                            <option value="<?php echo esc_attr($id); ?>" <?php if ((int) $settings['selected_template'] === $id) {
                                   echo 'selected';
                               } ?>><?php echo esc_html($template); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="form-field" style="padding: 5px 20px 5px 212px!important;">
                    <label style="margin: 0 0 0 -200px; width: 200px;" for="_manage_reviews_count">Hide the Review
                        Total?</label>
                    <input type="checkbox" class="checkbox"
                        name="<?php echo esc_attr($this->postMetaName); ?>[hide_reviews_count]" id="_manage_reviews_count"
                        value="yes" <?php checked($settings['hide_reviews_count'], 'yes'); ?>>
                </p>
                <p class="form-field" style="padding: 5px 20px 5px 212px!important;">
                    <label style="margin: 0 0 0 -200px; width: 200px;" for="_manage_reviews_title">Hide Review Headline?</label>
                    <input type="checkbox" class="checkbox"
                        name="<?php echo esc_attr($this->postMetaName); ?>[hide_reviews_title]" id="_manage_reviews_title"
                        value="yes" <?php checked($settings['hide_reviews_title'], 'yes'); ?>>
                </p>
                <p class="form-field" style="padding: 5px 20px 5px 212px!important;">
                    <label style="margin: 0 0 0 -200px; width: 200px;"
                        for="_reviews_widgets_placement"><?php esc_html_e('Choose Where to Show Template', 'wp-social-ninja-pro'); ?></label>
                    <select name="<?php echo esc_attr($this->postMetaName); ?>[reviews_widgets_placement]"
                        id="_reviews_widgets_placement" class="select short" style="width:100%;">
                        <option value="display_inside_reviews_tab" <?php selected($settings['reviews_widgets_placement'], 'display_inside_reviews_tab'); ?>>
                            <?php esc_html_e('Display Inside Reviews Tab', 'wp-social-ninja-pro'); ?>
                        </option>
                        <option value="display_outside_tabs" <?php selected($settings['reviews_widgets_placement'], 'display_outside_tabs'); ?>>
                            <?php esc_html_e('Display Outside Tabs', 'wp-social-ninja-pro'); ?>
                        </option>
                    </select>
                </p>
                <p class="form-field" style="padding: 5px 20px 5px 212px!important;">
                    <label style="margin: 0 0 0 -200px; width: 200px;"
                        for="_reviews_form"><?php esc_html_e('Select Your Review Form Style', 'wp-social-ninja-pro'); ?></label>
                    <select name="<?php echo esc_attr($this->postMetaName); ?>[reviews_form]" id="_reviews_form"
                        class="select short" style="width:100%;">
                        <option value="woocommerce" <?php selected($settings['reviews_form'], 'woocommerce'); ?>>
                            <?php esc_html_e('Default WooCommerce form', 'wp-social-ninja-pro'); ?>
                        </option>
                        <option value="social_ninja" <?php selected($settings['reviews_form'], 'social_ninja'); ?>>
                            <?php esc_html_e('Inherit from social ninja', 'wp-social-ninja-pro'); ?>
                        </option>
                    </select>
                </p>
            </div>
        </div>
        <style>
            .wpsr-meta {
                padding: 0 20px;
            }
        </style>
        <?php
    }

    public function saveMetaData($post_id)
    {
        if (
            !isset($_POST['wpsr_meta_box_woo_nonce']) ||
            !wp_verify_nonce($_POST['wpsr_meta_box_woo_nonce'], 'wpsr_meta_box_woo') ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        ) {
            return;
        }

        if ($_POST['post_type'] != 'product') {
            return;
        }

        $data = Arr::get($_POST, $this->postMetaName, []);
        update_post_meta($post_id, $this->postMetaName, $data);
    }

    protected function haveSelectedTemplate($comment)
    {
        if (!$comment->comment_post_ID) {
            return;
        }

        $post_id = $comment->comment_post_ID;
        $settings = WooCommerceHelper::getEffectiveSettings($post_id);
        $selected_template = Arr::get($settings, 'selected_template');

        return $selected_template;
    }

    public function insertProductReview($comment_id, $comment)
    {
        if ($this->haveSelectedTemplate($comment)) {
            $post_id = $comment->comment_post_ID;
            \WC_Comments::add_comment_rating($comment_id);
            $review = WooCommerceHelper::buildReviewFromComment($comment, $post_id);
            $reviewerEmail = Arr::get($review, 'reviewer_email');

            $global_settings          =  get_option('wpsr_fluent_forms_global_settings');
            $manually_review_approved = Arr::get($global_settings, 'global_settings.manually_review_approved', 'false');
            $review_approved          = $manually_review_approved === 'true' ? 0 : 1;

            $review['source_id'] = $post_id;
            $review['platform_name'] = 'woocommerce';
            $review['reviewer_text'] = Arr::get($review, 'review_text', '');
            $review['review_title'] = '';
            $review['reviewer_img'] = $reviewerEmail ? get_avatar_url($reviewerEmail) : '';
            $review['rating'] = (int) Arr::get($review, 'review_rating');
            $review['review_time'] = date('Y-m-d H:i:', Arr::get($review, 'review_date'));
            $review['fields'] = json_encode(Arr::get($review, 'fields'));
            $review['review_approved'] = $review_approved;
            $review['created_at'] = date('Y-m-d H:i:s');
            $review['updated_at'] = date('Y-m-d H:i:s');
            $createdReview = Review::create($review);

            do_action('wpsocialreviews/custom_review_created', $createdReview);
        }
    }

    public function approveCommentCallback($new_status, $old_status, $comment)
    {
        if ($this->haveSelectedTemplate($comment)) {
            $comment_id = $comment->comment_ID;
            if ($new_status == 'approved') {
                $this->insertProductReview($comment_id, $comment);
            }

            if ($new_status != 'approved') {
                Review::trashReview('woocommerce', 'review_id', $comment_id);
                $this->insertProductReview($comment_id, $comment);
            }
        }
    }

    /**
     * Public method to trigger import (can be called externally)
     */
    public static function triggerWooImport()
    {
        WooBatchImportService::trigger();
    }

    /**
     * Get import progress information
     */
    public static function getImportProgress()
    {
        return WooBatchImportService::getImportProgress();
    }

    /**
     * Reset import completely
     */
    public static function resetImport()
    {
        return WooBatchImportService::resetImport();
    }
}
