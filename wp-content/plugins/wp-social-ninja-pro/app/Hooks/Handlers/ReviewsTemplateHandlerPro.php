<?php

namespace WPSocialReviewsPro\App\Hooks\Handlers;

use WPSocialReviews\App\Models\Review;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce\WooCommerceHelper;
use WPSocialReviewsPro\App\Traits\LoadView;

class ReviewsTemplateHandlerPro
{
    use LoadView;

    /**
     * Detect if we're in WooCommerce context and get settings
     * 
     * @return array Contains is_woocommerce_context, woo_settings, reviews_form, and wrapper_classes
     */
    private function getWooCommerceContext()
    {
        global $post;
        $is_woocommerce_context = WooCommerceHelper::isWooCommerceProductContext();

        $woo_settings = [];
        $reviews_form = 'social_ninja';
        $wrapper_classes = 'wpsr-business-info-wrapper';

        if (Arr::get($is_woocommerce_context, 'is_product') && isset($post->ID)) {
            $woo_settings = WooCommerceHelper::getEffectiveSettings($post->ID);
            $reviews_form = Arr::get($woo_settings, 'reviews_form', 'woocommerce');
            $wrapper_classes .= ' wpsr-woocommerce-context';
            if ($reviews_form === 'woocommerce') {
                $wrapper_classes .= ' wpsr-woocommerce-reviews-form';
            }
        }

        return [
            'is_woocommerce_context' => $is_woocommerce_context,
            'woo_settings' => $woo_settings,
            'reviews_form' => $reviews_form,
            'wrapper_classes' => $wrapper_classes
        ];
    }

    public function airbnbReviewsLimitEndPoint()
    {
        return 100;
    }

    public function adminAppVars($vars)
    {
        $vars['assets_url_pro'] = WPSOCIALREVIEWS_PRO_URL . 'assets/images/';
        return $vars;
    }

    public function pushPlatforms($platforms)
    {
        $platforms['testimonial'] = __('Testimonial', 'wp-social-ninja-pro');
        return $platforms;
    }

    public function addReviewsTemplate($template, $reviews, $template_meta, $business_info = [])
    {
        $templateMapping = [
            'grid6' => 'reviews-templates/template6',
            'grid7' => 'reviews-templates/template7',
            'grid8' => 'reviews-templates/template8',
            'grid9' => 'reviews-templates/template9',
            'grid10' => 'reviews-templates/template10',
            'testimonial1' => 'testimonial-templates/testimonial1',
            'testimonial2' => 'testimonial-templates/testimonial2',
        ];

        if (!isset($templateMapping[$template])) {
            return __('No templates found!! Please save template and try again', 'wp-social-ninja-pro');
        }

        return $this->loadView($templateMapping[$template], array(
            'reviews' => $reviews,
            'template_meta' => $template_meta,
            'business_info' => $business_info
        ));
    }

    public function renderReviewsTemplateBusinessInfo($reviews = [], $business_info = [], $template_meta = [], $templateId = null, $translations = [])
    {
        $platforms = Arr::get($business_info, 'platforms');
        $total_rating = Arr::get($business_info, 'total_rating', 0);
        // Get WooCommerce context data
        $woo_context = $this->getWooCommerceContext();

        if($total_rating < 1 ){
            echo $this->loadView('reviews-templates/empty_business_info', array(
                'business_info' => $business_info,
                'template_meta' => $template_meta,
                'templateId' => $templateId,
                'translations' => $translations,
                'woo_context' => $woo_context,
            ));
            return;
        }

        if ((isset($template_meta['show_header']) && $template_meta['show_header'] === 'true') && !empty($business_info) && defined('WPSOCIALREVIEWS_PRO') && $platforms && $total_rating >= 1) {
            $platformNames = array_column($business_info['platforms'], 'platform_name');
            $isBooking = false;
            if (in_array('booking.com', $platformNames)) {
                if (count(array_unique($platformNames)) === 1 && end($platformNames) === 'booking.com') {
                    $isBooking = true;
                }
            }

            // Prepare header template data
            $header_data = $this->prepareHeaderTemplateData($business_info, $template_meta, $reviews);

            // Calculate rating breakdown if needed
            $rating_breakdown = [];
            if ($header_data['shouldShowRatingBreakdown']) {
                $rating_breakdown = $this->calculateRatingBreakdown($reviews, $header_data['isBookingComOnly'], $templateId, $template_meta, $business_info);
            }

            // Add custom business info if custom platform exists
            // $business_info = Helper::addCustomBusinessInfo($business_info, $template_meta);

            if(count($rating_breakdown) === 0){
                return [];
            }

            echo $this->loadView('reviews-templates/business_info', array(
                'reviews' => $reviews,
                'business_info' => $business_info,
                'template_meta' => $template_meta,
                'isBooking' => $isBooking,
                'templateId' => $templateId,
                'translations' => $translations,
                'woo_context' => $woo_context,
                'header_data' => $header_data,
                'rating_breakdown' => $rating_breakdown,
                'handler' => $this
            ));
        }
    }

    public function renderReviewsWriteaReviewBtn($template_meta = [], $templateType = '', $business_info = [], $templateId = null, $translations = [])
    {
        if (!Arr::get($template_meta, 'display_header_write_review', true)) {
            return;
        }
        $html = $this->loadView('reviews-templates/write-a-review-btn', array(
            'templateId' => $templateId,
            'template_meta' => $template_meta,
            'templateType' => $templateType,
            'business_info' => $business_info,
            'translations' => $translations
        ));
        echo $html;
    }

    public function renderReviewImages($images = '', $reviewId = null)
    {
        if (empty($images)) {
            return;
        }
        $images = is_array($images) ? $images : explode(',', $images);

        echo '<div class="wpsr-review-images">';
        foreach ($images as $index => $image) {
            $dataAttributes = '';
            if ($reviewId) {
                $dataAttributes .= ' data-review-id="' . esc_attr($reviewId) . '"';
                $dataAttributes .= ' data-image-index="' . esc_attr($index) . '"';
            }
            echo '<img class="wpsr-review-image wpsr-review-photos-playmode" src="' . esc_url($image) . '" alt="Review Image"' . $dataAttributes . '/>';
        }
        echo '</div>';
    }

    public function addReviewsBadgeTemplate($templateId = null, $templateType = '', $business_info = [], $badge_settings = [])
    {
        return $this->loadView('reviews-templates/badge1', array(
            'templateId' => $templateId,
            'templateType' => $templateType,
            'business_info' => $business_info,
            'badge_settings' => $badge_settings
        ));
    }

    public function addReviewsNotificationTemplate($templateId, $templateMeta, $reviews)
    {
        return $this->loadView('reviews-templates/notification', array(
            'templateId' => $templateId,
            'templateMeta' => $templateMeta,
            'reviews' => $reviews
        ));
    }

    public function renderAuthorPosition($template_meta, $reviews)
    {
        if (Arr::get($template_meta, 'author_position') !== 'true' && Arr::get($template_meta, 'author_company_name') !== 'true') {
            return;
        }
        if (Arr::get($reviews, 'fields')) {
            $author_position = Arr::get($reviews, 'fields.author_position', '');
            $author_company = Arr::get($reviews, 'fields.author_company', '');
            ?>
            <span class="wpsr-reviewer-position">
                <?php
                if (Arr::get($template_meta, 'author_position') === 'true' && $author_position) {
                    echo esc_html($author_position);
                }
                if (Arr::get($template_meta, 'author_company_name') === 'true' && $author_company) {
                    echo esc_html('@' . $author_company);
                }
                ?>
            </span>
            <?php
        }
    }

    public function renderAuthorWebsiteLogo($template_meta, $reviews)
    {
        if (Arr::get($template_meta, 'website_logo') !== 'true') {
            return;
        }
        if (Arr::get($reviews, 'fields')) {
            $author_website_url = Arr::get($reviews, 'fields.author_website_url', '');
            $author_website_logo = Arr::get($reviews, 'fields.author_website_logo', '');
            $author_company = Arr::get($reviews, 'fields.author_company', '');
            if (!$author_website_logo) {
                return false;
            }
            ?>
            <div class="wpsr-author-website-logo-wrapper">
                <a class="wpsr-author-website-logo-url" href="<?php echo esc_url($author_website_url); ?>" target="_blank">
                    <img class="wpsr-author-website-logo" src="<?php echo esc_url($author_website_logo); ?>"
                        alt="<?php echo esc_attr($author_company); ?>">
                </a>
            </div>
            <?php
        }
    }

    /**
     * Render Total Reviews HTML on AI Summary Card
     *
     * @param $total_rating
     * @param $custom_number_of_reviews_text
     * @param $review
     *
     * @since 3.1
     */
    public function addTotalReviewsToAISummaryCard($total_rating, $custom_number_of_reviews_text, $review)
    {
        if ($review->category === 'ai_summary') {
            echo '<div class="wpsr-total-reviews-for-ai-summary-card">' .
                str_replace(
                    '{total_reviews}',
                    '<span>' . esc_html(number_format($total_rating, 0)) . '</span>',
                    esc_html($custom_number_of_reviews_text)
                )
                . '</div>';
        }
    }

    /**
     * Prepare data for header template rendering
     * 
     * @param array $business_info
     * @param array $template_meta
     * @param array $reviews
     * @return array
     */
    public function prepareHeaderTemplateData($business_info, $template_meta, $reviews = [])
    {
        $meta_platform = Arr::get($template_meta, 'platform', []);
        $total_platforms = !empty($meta_platform) && is_array($meta_platform) ? count($meta_platform) : 0;

        $platform_name = Arr::get($business_info, 'platform_name', '');
        $total_business = Arr::get($business_info, 'total_business');

        // Determine the header title
        $custom_title_text = Arr::get($template_meta, 'custom_title_text', '');
        if (empty($custom_title_text)) {
            $rating_text = $total_platforms > 1 ? __('Overall Rating', 'wp-social-ninja-pro') : __(' Rating', 'wp-social-ninja-pro');
        } else {
            $rating_text = strlen($custom_title_text) ? $custom_title_text : '';
        }

        // Wrapper classes
        $platform_name_class = Helper::platformDynamicClassName($business_info);

        // Template-specific wrapper classes
        $template_type = Arr::get($template_meta, 'templateType', '');
        $wrapperClass = '';
        if ($template_type === 'badge' || $template_type === 'notification') {
            $wrapperClass = 'wpsr-display-block';
            // Add template2 specific class if needed
            if (isset($template_meta['header_template']) && $template_meta['header_template'] === 'template2') {
                $wrapperClass .= ' wpsr-template-2-block';
            }
        }

        // Rating breakdown conditions
        // Get total and average rating using safe array access
        $total_rating = Arr::get($business_info, 'total_rating');
        $average_rating = Arr::get($business_info, 'average_rating');

        // Determine if rating breakdown should be shown:
        // - total_rating and average_rating must be non-empty
        // - reviews array must not be empty
        $shouldShowRatingBreakdown = !empty($total_rating) &&
            !empty($average_rating) &&
            !empty($reviews) &&
            count($reviews) > 0;

        // Check if this is booking.com only
        $isBookingComOnly = ($total_platforms === 1 && $platform_name === 'booking.com');

        return [
            'meta_platform' => $meta_platform,
            'total_platforms' => $total_platforms,
            'platform_name' => $platform_name,
            'total_business' => $total_business,
            'rating_text' => $rating_text,
            'platform_name_class' => $platform_name_class,
            'wrapperClass' => $wrapperClass,
            'shouldShowRatingBreakdown' => $shouldShowRatingBreakdown,
            'isBookingComOnly' => $isBookingComOnly
        ];
    }

    /**
     * Calculate star rating breakdown for reviews
     * 
     * @param array $reviews
     * @param bool $isBookingComOnly
     * @param int $templateId
     * @param array $template_meta
     * @param array $business_info Optional business info that might contain pre-calculated breakdown
     * @return array
     */
    public function calculateRatingBreakdown($reviews, $isBookingComOnly = false, $templateId = null, $template_meta = [], $business_info = [])
    {
        // First check if business_info already has rating breakdown data
        if (isset($business_info['rating_breakdown']) && !empty($business_info['rating_breakdown'])) {
            return $business_info['rating_breakdown'];
        }

        // Fallback to the old method using filtered reviews if business_info doesn't have the data
        $reviews = Review::collectReviewsAndBusinessInfo($template_meta, $templateId);
        $filtered_reviews = Arr::get($reviews, 'filtered_reviews', $reviews);

        if (empty($filtered_reviews)) {
            return [];
        }

        if ($isBookingComOnly && method_exists(Helper::class, 'calculateBookingRatingBreakdown')) {
            return Helper::calculateBookingRatingBreakdown($reviews);
        } elseif (method_exists(Helper::class, 'calculateStarRatingBreakdownFromReviews')) {
            return Helper::calculateStarRatingBreakdownFromReviews($filtered_reviews);
        } else {
            return [];
        }
    }

    /**
     * Check if platform icon should be displayed
     * 
     * @param string $platform_name
     * @param array $template_meta
     * @param bool $display_header_business_logo
     * @return bool
     */
    private function shouldDisplayPlatformIcon($platform_name, $template_meta, $display_header_business_logo)
    {
        return !empty($platform_name) &&
            $display_header_business_logo &&
            (!Helper::is_tp($platform_name) ||
                (Arr::get($template_meta, 'display_tp_brand') === 'true' && Helper::is_tp($platform_name)));
    }

    /**
     * Get platform icon with appropriate size
     * 
     * @param string $platform_name
     * @param bool $is_multiple_platforms
     * @return string
     */
    private function getPlatformIcon($platform_name, $is_multiple_platforms = false)
    {
        $image_size = $is_multiple_platforms ? 'small' : '';
        return Helper::platformIcon($platform_name, $image_size);
    }

    /**
     * Generate HTML for platform icons (unified method)
     * 
     * @param array $config Configuration array
     * @return string
     */
    private function generatePlatformIconsHtml($config)
    {
        $platforms = $config['platforms'];
        $template_meta = $config['template_meta'];
        $is_multiple_platforms = $config['is_multiple_platforms'];
        $display_logo = $config['display_logo'];
        $include_business_name = $config['include_business_name'] ?? false;
        $rating_text = $config['rating_text'] ?? '';
        $wrapper_class = $config['wrapper_class'] ?? 'wpsr-business-info-paltforms';

        if (empty($platforms) || !is_array($platforms)) {
            return '';
        }

        $html = '<div class="' . esc_attr($wrapper_class) . '">';
        $processed_platforms = [];

        foreach ($platforms as $platform) {
            $platform_name = Arr::get($platform, 'platform_name');
            $customLogo = Arr::get($platform, 'logo');
            $validPlatforms = get_option('wpsr_available_valid_platforms', []);

            // Skip if already processed
            if (isset($processed_platforms[$platform_name]) && !in_array($platform_name, array_keys($validPlatforms))) {
                continue;
            }
            $processed_platforms[$platform_name] = true;

            // Check if platform should be displayed
            if (!empty(Arr::get($platform, 'url')) && $this->shouldDisplayPlatformIcon($platform_name, $template_meta, $display_logo)) {

                $icon_url = $this->getPlatformIcon($platform_name, $is_multiple_platforms);

                if(!empty($customLogo)){
                    $html .= '<img src="' . esc_url($customLogo) . '" alt="' . esc_attr($platform_name) . '">';
                }
                if (!empty($icon_url) && empty($customLogo)) {
                    $html .= '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($platform_name) . '">';
                }
            }
        }

        // Add business name if required (for template1)
        if ($include_business_name && !empty($rating_text)) {
            $html .= '<span>' . esc_html($rating_text) . '</span>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generate HTML for single platform icon (unified method)
     * 
     * @param array $config Configuration array
     * @return string
     */
    private function generateSinglePlatformIconHtml($config)
    {
        $platform_name = $config['platform_name'];
        $template_meta = $config['template_meta'];
        $display_logo = $config['display_logo'];
        $include_business_name = $config['include_business_name'] ?? false;
        $rating_text = $config['rating_text'] ?? '';
        $wrapper_class = $config['wrapper_class'] ?? 'wpsr-business-info-logo';

        $html = '<div class="' . esc_attr($wrapper_class) . '">';

        if ($this->shouldDisplayPlatformIcon($platform_name, $template_meta, $display_logo)) {
            $customPlatformData = [
                'logo' => '',
                'name' => ''
            ];
            if (method_exists(Helper::class, 'getCustomPlatformLogo')) {
                $customPlatformData = Helper::getCustomPlatformLogo($platform_name);
            }

            if (!empty($customPlatformData['logo'])) {
                $html .= '<img src="' . esc_url($customPlatformData['logo']) . '" alt="' . esc_attr($customPlatformData['name']) . '">';
            } else {
                $large_icon = $this->getPlatformIcon($platform_name, false);
                if (!empty($large_icon)) {
                    $html .= '<img src="' . esc_url($large_icon) . '" alt="' . esc_attr($platform_name) . '"/>';
                }
            }
        }

        // Add business name if required (for template1)
        if ($include_business_name && !empty($rating_text)) {
            $html .= '<span>' . esc_html($rating_text) . '</span>';
        }

        $html .= '</div>';
        return $html;
    }

    // ====== EXISTING PUBLIC METHODS (MAINTAINED FOR BACKWARD COMPATIBILITY) ======

    /**
     * Render platform icons for multiple businesses
     * 
     * @param array $platforms
     * @param array $template_meta
     * @param array $meta_platform
     * @param bool $display_header_business_logo
     * @param bool $display_header_business_name (optional)
     * @param string $rating_text (optional)
     * @return string
     */
    public function renderPlatformIcons($platforms, $template_meta, $meta_platform, $display_header_business_logo, $display_header_business_name = false, $rating_text = '')
    {
        return $this->generatePlatformIconsHtml([
            'platforms' => $platforms,
            'template_meta' => $template_meta,
            'is_multiple_platforms' => sizeof($meta_platform) > 1,
            'display_logo' => $display_header_business_logo,
            'include_business_name' => $display_header_business_name,
            'rating_text' => $rating_text
        ]);
    }

    /**
     * Render single platform icon
     * 
     * @param string $platform_name
     * @param array $template_meta
     * @param bool $display_header_business_logo
     * @param bool $display_header_business_name (optional)
     * @param string $rating_text (optional)
     * @return string
     */
    public function renderSinglePlatformIcon($platform_name, $template_meta, $display_header_business_logo, $display_header_business_name = false, $rating_text = '')
    {
        return $this->generateSinglePlatformIconHtml([
            'platform_name' => $platform_name,
            'template_meta' => $template_meta,
            'display_logo' => $display_header_business_logo,
            'include_business_name' => $display_header_business_name,
            'rating_text' => $rating_text
        ]);
    }



    /**
     * Render rating and count section
     * 
     * @param array $business_info
     * @param array $template_meta
     * @param bool $isBooking
     * @param string $templateId
     * @param bool $display_header_rating
     * @param bool $display_header_reviews
     * @param string $custom_number_of_reviews_text
     * @return string
     */
    public function renderRatingAndCount($business_info, $template_meta, $isBooking, $templateId, $display_header_rating, $display_header_reviews, $custom_number_of_reviews_text)
    {
        $average_rating = Arr::get($business_info, 'average_rating');
        $total_rating = Arr::get($business_info, 'total_rating');

        $html = '<div class="wpsr-rating-and-count">';

        if (isset($average_rating) && !empty($average_rating) && $display_header_rating === true) {
            $html .= '<span class="wpsr-total-rating">' . esc_html(number_format($average_rating, 1)) . '</span>';
            if (!$isBooking) {
                $html .= '<span class="wpsr-rating">' . Helper::generateRatingIcon(number_format($average_rating, 1), $templateId) . '</span>';
            }
        }

        if (isset($total_rating) && !empty($total_rating) && $display_header_reviews === true && strlen($custom_number_of_reviews_text)) {
            $html .= '<div class="wpsr-total-reviews">' .
                str_replace(
                    '{total_reviews}',
                    '<span>' . esc_html(number_format($total_rating, 0)) . '</span>',
                    esc_html($custom_number_of_reviews_text)
                )
                . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Handle update of business info when a review is updated/approved.
     * Moved from WooProductTemplate to allow admin-side handling.
     *
     * @param object $review
     */
    public function handleUpdateBusinessInfo($review)
    {
        $platformName = isset($review->platform_name) ? $review->platform_name : '';
        $customValidPlatforms = get_option('wpsr_available_valid_platforms', []);
        // Only process for specific platforms
        $allowedPlatforms = ['woocommerce', 'custom', 'fluent_forms'];
        if(!empty($customValidPlatforms)){
            $allowedPlatforms = array_merge($allowedPlatforms, $customValidPlatforms);
        }
        if (!in_array($platformName, $allowedPlatforms)) {
            return;
        }

        // Update the business info with new total_reviews count
        $this->updateBusinessInfo($review);
    }


    /**
     * Update business info for the review's platform
     *
     * @param object $review
     */
    private function updateBusinessInfo($review)
    {
        $platformName = Arr::get($review, 'platform_name', 'custom');
        $sourceId = Arr::get($review, 'source_id', null);
        if (!$sourceId) {
            return;
        }

        // Get business name
        $name = defined('WC_VERSION') && $platformName === 'woocommerce' ? get_the_title($sourceId) : '';
        $dataSource = [
            'source_id'   => $sourceId,
            'handle' => $name
        ];

        $businessInfo = Review::getInternalBusinessInfo($platformName, $dataSource);
        update_option('wpsr_reviews_'.$platformName.'_business_info', $businessInfo);
    }
}