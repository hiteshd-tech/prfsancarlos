<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews\WooCommerce;

use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle WooCommerce Single Product Template
 * @since 3.11.0
 */
class WooProductTemplate
{
    /**
     * Cache for effective settings to avoid repeated calls
     * @var array
     */
    private $effectiveSettingsCache = [];

    public $wooCommerceInstance;

    public function __construct()
    {
        $this->wooCommerceInstance = new WooCommerce();
    }

    public function init(): void
    {
        global $post;
        $post_id = $post->ID ?? null;

        $this->setupProductListHooks();

        if (!$post_id || !WooCommerceHelper::isProductConnectedToWPSN($this->wooCommerceInstance)) {
            return;
        }

        $effectiveSettings = $this->getEffectiveSettings($post_id);

        $this->setupSingleProductHooks($effectiveSettings);
    }

    /**
     * Setup hooks for single product page based on settings
     */
    private function setupSingleProductHooks(array $effectiveSettings): void
    {
        $hide_reviews_count = $effectiveSettings['hide_reviews_count'];
        $hide_reviews_title = $effectiveSettings['hide_reviews_title'];
        $reviews_widget_placement = $effectiveSettings['reviews_widgets_placement'];
        $reviews_form = $effectiveSettings['reviews_form'];
        $selected_template = $effectiveSettings['selected_template'];

        if ($hide_reviews_count === 'yes') {
            add_filter('woocommerce_product_tabs', [$this, 'maybeWooProductTabs']);
        }

        if (!$selected_template) {
            return;
        }

        // Handle widget placement
        if ($reviews_widget_placement === 'display_outside_tabs') {
            add_filter('woocommerce_product_tabs', [$this, 'removeReviewsTab']);
            add_action('woocommerce_after_single_product_summary', [$this, 'displayReviewsOutsideTabs'], 14);
        } else if ($reviews_widget_placement === 'display_inside_reviews_tab' && $selected_template) {
            add_filter('woocommerce_product_tabs', [$this, 'modifyReviewsTabCallback']);
        }

        add_action('comment_form_before', [$this, 'hideNoReviewsText']);

        if ($reviews_form !== 'woocommerce') {
            // Add WooCommerce review link redirect data to JavaScript
            add_action('wp_footer', [$this, 'addWooReviewLinkData']);
        }

        add_filter('woocommerce_product_review_comment_form_args', [$this, 'modifyProductReviewCommentFormArgs']);
        add_filter('woocommerce_product_review_list_args', [$this, 'modifyProductReviewListArgs']);

        if ($hide_reviews_title === 'yes' || $effectiveSettings['isGlobalTemplateGettingPrecedence']) {
            add_filter('woocommerce_reviews_title', [$this, 'removeReviewsTitle'], 10, 3);
        }

        $this->registerWooCommerceHooks();
    }

    /**
     * Get cached effective settings for a product
     */
    private function getEffectiveSettings(int $product_id): array
    {
        if (!isset($this->effectiveSettingsCache[$product_id])) {
            $this->effectiveSettingsCache[$product_id] = WooCommerceHelper::getEffectiveSettings($product_id);
        }
        return $this->effectiveSettingsCache[$product_id];
    }

    public function modifyProductReviewListArgs(array $args): array
    {
        $args['callback'] = '__return_false';
        $args['per_page'] = 0;

        return $args;
    }

    public function modifyProductReviewCommentFormArgs(array $comment_form): array
    {
        global $product;
        $product_id = $product ? $product->get_ID() : null;
        $businessInfo = $this->getBusinessInfoByID($product_id);

        $comment_form['title_reply'] = Arr::get($businessInfo, 'total_rating')
            ? esc_html__('Add a review', 'wp-social-ninja-pro')
            : sprintf(esc_html__('Be the first to review &ldquo;%s&rdquo;', 'wp-social-ninja-pro'), get_the_title());

        return $comment_form;
    }

    public function setupProductListHooks(): void
    {
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'overrideWooCommerceProductListRating'], 4);
    }

    public function removeReviewsTitle(string $reviews_title, int $count, $product): string
    {
        return '';
    }

    public function modifyReviewsTabCallback(array $tabs): array
    {
        if (isset($tabs['reviews'])) {
            $tabs['reviews']['callback'] = [$this, 'displayReviewsInProductTab'];
        }
        return $tabs;
    }

    public function hideNoReviewsText(): void
    {
        echo '<style>.woocommerce-noreviews{display: none;}</style>';
    }

    public function maybeWooProductTabs($tabs = [])
    {
        // Reviews tab - shows comments.
        if (comments_open()) {
            $tabs['reviews'] = array(
                /* translators: %s: reviews count */
                'title' => __('Reviews', 'wp-social-ninja-pro'),
                'priority' => 30,
                'callback' => 'comments_template',
            );
        }

        return $tabs;
    }

    public function displayReviewsInProductTab($args)
    {
        global $product;
        $product_id = $product ? $product->get_ID() : null;
        $effectiveSettings = $this->getEffectiveSettings($product_id);
        $selected_template = $effectiveSettings['selected_template'];
        $shouldDisplaySelectedTemplate = isset($selected_template) && $selected_template !== "0";
        $platform = $effectiveSettings['platform'];

        echo '<div id="wpsr-reviews-section">';
        if ($effectiveSettings['isGlobalTemplateGettingPrecedence'] || $shouldDisplaySelectedTemplate) {
            echo do_shortcode('[wp_social_ninja id="' . $selected_template . '" platform="' . $platform . '"]');
            if ($effectiveSettings['reviews_form'] === 'woocommerce') {
                comments_template();
            }
        }
        echo '</div>';
        return $args;
    }

    public function removeReviewsTab(array $tabs): array
    {
        unset($tabs['reviews']);
        return $tabs;
    }

    public function displayReviewsOutsideTabs(): void
    {
        global $product;
        $product_id = $product ? $product->get_ID() : null;
        $effectiveSettings = $this->getEffectiveSettings($product_id);
        $selected_template = $effectiveSettings['selected_template'];
        $reviews_form = $effectiveSettings['reviews_form'];

        if ($selected_template) {
            echo '<div id="wpsr-reviews-section" class="wpsr-reviews-outside-tabs">';
            echo do_shortcode('[wp_social_ninja id="' . $selected_template . '" platform="reviews"]');

            // Display WooCommerce reviews form
            if (comments_open() && $reviews_form === 'woocommerce') {
                comments_template();
            }

            echo '</div>';
        } else {
            comments_template();
        }
    }

    /**
     * Register WooCommerce integration hooks
     */
    public function registerWooCommerceHooks(): void
    {
        if (!defined('WC_VERSION')) {
            return;
        }

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
        add_action('woocommerce_single_product_summary', [$this, 'woocommerceTemplateSingleRating'], 9);
        add_filter('woocommerce_product_tabs', [$this, 'modifyWooCommerceReviewsTabTitle'], 98);
    }

    /**
     * Get product rating data from business info or fallback to WooCommerce defaults
     *
     * @param \WC_Product $product
     * @return array Contains 'average_rating', 'rating_count', and 'total_rating'
     */
    private function getProductRatingData($product): array
    {
        $product_id = $product ? $product->get_ID() : null;
        $businessInfo = $this->getBusinessInfoByID($product_id);

        $average_rating = Arr::get($businessInfo, 'average_rating', $product->get_average_rating());
        $rating_count = Arr::get($businessInfo, 'total_rating', $product->get_rating_count());
        $total_rating = $businessInfo ? $rating_count : $product->get_review_count();

        return [
            'average_rating' => (float) $average_rating,
            'rating_count' => (int) $rating_count,
            'total_rating' => (int) $total_rating,
        ];
    }

    /**
     * Get business info for a specific product
     */
    private function getBusinessInfoByID(int $product_id): array
    {
        $businessInfo = WooCommerceHelper::getAllBusinessInfo($this->wooCommerceInstance);
        return Arr::get($businessInfo, $product_id, []);
    }

    public function woocommerceTemplateSingleRating(): void
    {
        global $product;

        if (!$product || !wc_review_ratings_enabled()) {
            return;
        }

        $ratingData = $this->getProductRatingData($product);

        if ($ratingData['rating_count'] > 0) {
            $this->displayRatingHtml($ratingData, true);
        }
    }

    public function overrideWooCommerceProductListRating(): void
    {
        global $product;

        if (!$product || !wc_review_ratings_enabled()) {
            return;
        }
        $product_id = $product ? $product->get_ID() : null;
        $effectiveSettings = $this->getEffectiveSettings($product_id);
        $useSocialNinjaPrimary = Arr::get($effectiveSettings, 'use_social_ninja_primary', 'no');
        if ($useSocialNinjaPrimary !== 'yes') {
            return;
        }

        if (WooCommerceHelper::isProductConnectedToWPSN($this->wooCommerceInstance)) {
            $ratingData = $this->getProductRatingData($product);
            if ($ratingData['rating_count'] > 0) {
                $this->displayRatingHtml($ratingData, false, $effectiveSettings);
            }
        } else {
            // Default to WooCommerce rating display
            $average_rating = $product->get_average_rating();
            $rating_count = $product->get_rating_count();

            if ($rating_count > 0) {
                echo '<div class="woocommerce-product-rating">';
                echo wc_get_rating_html($average_rating, $rating_count);
                if ($effectiveSettings['hide_product_list_rating_count'] !== 'yes') {
                    echo '<span class="rating-count">' . sprintf(_n('%d review', '%d reviews', $rating_count, 'wp-social-ninja-pro'), $rating_count) . '</span>';
                }
                echo '</div>';
            }
        }
    }

    /**
     * Display rating HTML (shared logic)
     */
    private function displayRatingHtml(array $ratingData, bool $includeReviewLink = false, $effectiveSettings = []): void
    {
        $hideProductListRatingCount = $effectiveSettings['hide_product_list_rating_count'] ?? 'no';

        echo '<div class="wpsr-single-product-rating woocommerce-product-rating">';
        echo wc_get_rating_html($ratingData['average_rating'], $ratingData['rating_count']);

        if ($hideProductListRatingCount !== 'yes' && !$includeReviewLink) {
            echo '<span class="rating-count">' . sprintf(_n('%s review', '%s reviews', $ratingData['total_rating'], 'wp-social-ninja-pro'), number_format_i18n($ratingData['total_rating'])) . '</span>';
        }

        if ($includeReviewLink && comments_open()) {
            echo '<a href="#wpsr-reviews-section" class="woocommerce-review-link" rel="nofollow">';
            echo '(' . sprintf(
                _n('%s customer review', '%s customer reviews', $ratingData['total_rating'], 'wp-social-ninja-pro'),
                '<span class="count">' . esc_html(number_format_i18n($ratingData['total_rating'])) . '</span>'
            ) . ')';
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Modify reviews tab title to show custom review count
     *
     * @param array $tabs
     * @return array
     */
    public function modifyWooCommerceReviewsTabTitle(array $tabs): array
    {
        if (!defined('WC_VERSION') || !isset($tabs['reviews'])) {
            return $tabs;
        }

        global $product;
        if (!$product) {
            return $tabs;
        }

        $productId = $product ? $product->get_ID() : null;
        $businessInfo = $this->getBusinessInfoByID($productId);

        if (isset($businessInfo['total_rating'])) {
            $reviewCount = (int) $businessInfo['total_rating'];
            $tabs['reviews']['title'] = $reviewCount > 0
                ? sprintf(_n('Review (%s)', 'Reviews (%s)', $reviewCount, 'wp-social-ninja-pro'), number_format_i18n($reviewCount))
                : __('Reviews', 'wp-social-ninja-pro');
        }

        return $tabs;
    }

    /**
     * Add WooCommerce review link redirect data to JavaScript
     */
    public function addWooReviewLinkData(): void
    {
        global $post;

        if (!is_product() || !$post) {
            return;
        }

        $effectiveSettings = $this->getEffectiveSettings($post->ID);
        $reviews_form = $effectiveSettings['reviews_form'];
        $reviews_widget_placement = $effectiveSettings['reviews_widgets_placement'];
        ?>
        <script>
            window.wpsrWooSettings = {
                reviewsForm: '<?php echo esc_js($reviews_form); ?>',
                reviewsWidgetPlacement: '<?php echo esc_js($reviews_widget_placement); ?>'
            };
        </script>
        <?php
    }
}