<?php

namespace WPSocialReviewsPro\App\Services\Platforms\Reviews;

use FluentForm\App\Http\Controllers\IntegrationManagerController;
use FluentForm\Framework\Foundation\Application;
use WPSocialReviews\App\Models\Review;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

class Fluentform extends IntegrationManagerController
{
    public function __construct(Application $app)
    {
        parent::__construct(
            $app,
            'WP Social Ninja',
            'wp_social_ninja',
            '_fluentform_wp_social_ninja_settings',
            'fluentform_wp_social_ninja_reviews',
            16
        );

        $this->hasGlobalMenu = false;
        $this->disableGlobalSettings = 'yes';

        $this->logo = defined('WPSOCIALREVIEWS_URL') ? WPSOCIALREVIEWS_URL . 'assets/images/icon/wp_social_ninja.png' : '';

        $this->description = 'WP Social Ninja is the all-in-one WordPress plugin for automatically integrating your social media reviews, news feeds, and chat functionalities.';
        $this->registerAdminHooks();

        add_filter('fluentform/notifying_async_wp_social_ninja', '__return_false');

        add_filter('wpsocialreviews/available_valid_reviews_platforms', array($this, 'pushValidPlatform'));

        add_filter(
            'fluentform/get_integration_values_' .$this->integrationKey,
            [$this, 'resolveIntegrationSettings'],
            10,
            3
        );

        add_filter(
            'fluentform/save_integration_value_' . $this->integrationKey,
            [$this, 'validate'],
            10,
            3
        );
    }

    public function resolveIntegrationSettings($settings, $feed, $formId)
    {
        $serviceName = $this->app->request->get('serviceName', '');
        $serviceId = $this->app->request->get('serviceId', '');

        if ($serviceName) {
            $settings['name'] = $serviceName;
        }

        if ($serviceId) {
            $settings['list_id'] = $serviceId;
        }

        $default_fields = Arr::get($settings, 'default_fields');

        if($default_fields){
            $list_id = Arr::get($settings, 'list_id');
            $settings['list_id'] = $list_id ? $list_id : 'fluent_forms';
            $settings['title'] = Arr::get($settings, 'default_fields.title');
            $settings['reviewer_name'] = Arr::get($settings, 'default_fields.name');
            $settings['email'] = Arr::get($settings, 'default_fields.email');
            $settings['comment'] = Arr::get($settings, 'default_fields.comment');
            $settings['ratings'] = Arr::get($settings, 'default_fields.ratings');
            $settings['image'] = Arr::get($settings, 'default_fields.image');
            $settings['reviewer_url'] = Arr::get($settings, 'default_fields.reviewer_url');
            $settings['review_images'] = Arr::get($settings, 'default_fields.review_images');
            $settings['category'] = Arr::get($settings, 'default_fields.category');
        }
        return $settings;
    }

    public function validate($settings, $integrationId, $formId)
    {
        $error = false;
        $errors = [];

        if(Arr::exists($settings, 'default_fields') && Arr::exists($settings, 'ratings')){
            unset($settings['default_fields']);
        }

        foreach ($this->getFields($settings['list_id']) as $field) {
            if ($field['required'] && empty($settings[$field['key']])) {
                $error = true;
                $errors[$field['key']] = [__($field['label'] . ' is required', 'wp-social-ninja-pro')];
            }
        }

        if ($error) {
            wp_send_json_error([
                'message' => __('Validation Failed', 'wp-social-ninja-pro'),
                'errors'  => $errors
            ], 423);
        }

        return $settings;
    }


    public function getGlobalFields($fields)
    {
        return [
            'logo'             => $this->logo,
            'menu_title'       => __('WP Social Ninja', 'wp-social-ninja-pro'),
            'menu_description' => __('WP Social Ninja is the all-in-one WordPress plugin for automatically integrating your social media reviews, news feeds, and chat functionalities.',
                'wp-social-ninja-pro'),
            'valid_message'    => __('Your WP Social Ninja integration activated', 'wp-social-ninja-pro'),
            'invalid_message'  => __('WP Social Ninja need to approve first ', 'wp-social-ninja-pro'),
            'save_button_text' => __('Approve WP Social Ninja', 'wp-social-ninja-pro'),
            'hide_on_valid'    => true,
            'discard_settings' => [
                'section_description' => 'Your WP Social Ninja integration activated',
                'button_text'         => 'Deactivate',
                'data'                => [
                    'status' => true
                ],
                'show_verify'         => false
            ]
        ];
    }

    public function getGlobalSettings($settings)
    {
        $globalSettings = get_option($this->optionKey);
        if (!$globalSettings) {
            $globalSettings = [];
        }
        $defaults = [
            'status' => ''
        ];

        return wp_parse_args($globalSettings, $defaults);
    }

    public function getMergeFields($list, $listId, $formId)
    {
        return false;
    }

    public function saveGlobalSettings($settings)
    {
        if ($settings['status'] == '' || $settings['status'] == 'false') {
            update_option($this->optionKey, ['status' => true], 'no');
        } else {
            update_option($this->optionKey, ['status' => false], 'no');

            return wp_send_json_success([
                'status'  => false,
                'message' => __('WP Social Ninja Module Deactivated!', 'wp-social-ninja-pro')
            ], 200);
        };

        return wp_send_json_success([
            'status'  => true,
            'message' => __('WP Social Ninja activated!', 'wp-social-ninja-pro')
        ], 200);
    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations[$this->integrationKey] = [
            'title'                 => $this->title . ' Integration',
            'logo'                  => $this->logo,
            'is_active'             => $this->isConfigured(),
            'configure_title'       => 'Configuration required!',
            'global_configure_url'  => admin_url('admin.php?page=fluent_forms_settings#general-wp_social_ninja-settings'),
            'configure_message'     => 'Activate global settings first',
            'configure_button_text' => 'Activate Globally'
        ];

        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        $listId = $this->app->request->get('serviceId');

        return [
            'name'          => '',
            'list_id'       => $listId,
            'conditionals'   => [
                'conditions'    => [],
                'status'        => false,
                'type'          => 'all'
            ],
            'enabled' => true
        ];
    }

    public function getSettingsFields($settings, $formId)
    {

        $fieldSettings = [
            'fields'              => [
                [
                    'key'         => 'name',
                    'label'       => 'Name',
                    'required'    => true,
                    'placeholder' => 'Your Feed Name',
                    'component'   => 'text'
                ],
                [
                    'key'         => 'list_id',
                    'label'       => __('Integration Source', 'wp-social-ninja-pro'),
                    'placeholder' => __('Select', 'wp-social-ninja-pro'),
                    'required'    => false,
                    'component'   => 'refresh',
                    'options'     => $this->getLists()
                ],

            ],
            'button_require_list' => false,
            'integration_title'   => $this->title
        ];

        $listId = $this->app->request->get('serviceId', Arr::get($settings, 'list_id', ''));
        $listId = !isset($listId) ? 'fluent_forms' : $listId;

        if ($listId) {
            $fields = $this->getFields($listId);

            if (empty($fields)) {
                wp_send_json_error([
                    'message' => __("The selected service doesn't have any field settings.", 'wp-social-ninja-pro'),
                ], 423);
            }

            $fields = array_merge($fieldSettings['fields'], $fields);

            $fieldSettings['fields'] = $fields;
        }

        return $fieldSettings;
    }

    protected function getLists()
    {
        return [
            'fluent_forms'      => __('Fluent Forms', 'wp-social-ninja-pro'),
            'custom'            => __('Custom', 'wp-social-ninja-pro'),
            'woocommerce'       => __('WooCommerce', 'wp-social-ninja-pro'),
            'testimonial'       => __('Testimonial', 'wp-social-ninja-pro'),
        ];
    }

    public function getFields($listId)
    {
        $label_prefix = ($listId === 'testimonial') ? 'Author ' : 'Reviewer ';
        $mergedFields = [];
        $defaultFields = [
            [
                'key'     => 'ratings',
                'label'    => esc_html__('Ratings', 'wp-social-ninja-pro'),
                'required' => true,
                'component' => 'value_text'
            ],
            [
                'key'     => 'reviewer_name',
                'label'    => esc_html__($label_prefix.'Name', 'wp-social-ninja-pro'),
                'required' => true,
                'component' => 'value_text'
            ],
            [
                'key'     => 'email',
                'label'    => esc_html__($label_prefix.'Email', 'wp-social-ninja-pro'),
                'required' => false,
                'component' => 'value_text'
            ],
            [
                'key'     => 'comment',
                'label'    => esc_html__('Comment', 'wp-social-ninja-pro'),
                'required' => true,
                'component' => 'value_text'
            ],
            [
                'key'     => 'image',
                'label'    => esc_html__($label_prefix.'Image', 'wp-social-ninja-pro'),
                'required' => false,
                'component' => 'value_text'
            ],
            [
                'key'     => 'reviewer_url',
                'label'    => esc_html__($label_prefix.'URL', 'wp-social-ninja-pro'),
                'required' => false,
                'component' => 'value_text'
            ],
            [
                'key'     => 'review_images',
                'label'    => esc_html__('Review Images', 'wp-social-ninja-pro'),
                'required' => false,
                'component' => 'value_text',
                'tips'     => esc_html__('Upload images for your review. You can select multiple images.', 'wp-social-ninja-pro')
            ],
            [
                'key'     => 'title',
                'label'    => esc_html__('Review Title', 'wp-social-ninja-pro'),
                'required' => false,
                'component' => 'value_text'
            ],
        ];
        switch ($listId) {
            case 'testimonial':
                $mergedFields = array_merge($defaultFields,  [
                    [
                        'key'     => 'author_position',
                        'label'    => esc_html__('Author Position', 'wp-social-ninja-pro'),
                        'required' => false,
                        'component' => 'value_text'
                    ],
                    [
                        'key'     => 'author_company',
                        'label'    => esc_html__('Author Company', 'wp-social-ninja-pro'),
                        'required' => false,
                        'component' => 'value_text'
                    ],
                    [
                        'key'     => 'author_website_logo',
                        'label'    => esc_html__('Author Website Logo', 'wp-social-ninja-pro'),
                        'required' => false,
                        'component' => 'value_text'
                    ],
                    [
                        'key'     => 'author_website_url',
                        'label'    => esc_html__('Author Website URL', 'wp-social-ninja-pro'),
                        'required' => false,
                        'component' => 'value_text'
                    ]
                ]);
                break;
            case 'woocommerce': $mergedFields = array_merge( [
                    [
                        'key'     => 'source_id',
                        'label'    => esc_html__('Product/Post ID (Hidden Field)', 'wp-social-ninja-pro'),
                        'required' => true,
                        'component' => 'value_text'
                    ],
                ],
                $defaultFields);
                break;
            default:
                $mergedFields = $defaultFields;
                break;
        }

        return array_merge($mergedFields, array(
            [
                'key'     => 'category',
                'label'    => esc_html__('Category', 'wp-social-ninja-pro'),
                'required' => false,
                'component' => 'value_text'
            ],
            [
                'require_list' => false,
                'key'          => 'conditionals',
                'label'        => 'Conditional Logics',
                'tips'         => 'Allow WP Social Ninja integration conditionally based on your submission values',
                'component'    => 'conditional_block',
                'required' => false,
            ]
        ));
    }

    public function pushValidPlatform($platforms)
    {
        $settings = get_option('wpsr_fluent_forms_global_settings');
        if (!$settings) {
            $settings = array(
                'global_settings' => array(
                    'manually_review_approved'  => 'false'
                )
            );
            update_option('wpsr_fluent_forms_global_settings', $settings, 'no');
        }

        $platforms['fluent_forms'] = __('Fluent Forms', 'wp-social-ninja-pro');
        return $platforms;
    }

    public function notify($feed, $formData, $entry, $form)
    {
        // get map_fields component default settings
        $default_fields = Arr::get($feed, 'processedValues.default_fields');
        // check map_fields component has default settings or get refresh component values
        $feedData = $default_fields ? $default_fields : Arr::get($feed, 'processedValues');

        $platformName = Arr::get($feed, 'settings.list_id', 'fluent_forms');
        $email = Arr::get($feedData, 'email', '');

        $reviewer_img = '';
        if(Arr::get($feedData, 'image')) {
            $reviewer_img = Arr::get($feedData, 'image');
        }

        if(empty($reviewer_img)) {
            if ($email) {
                $reviewer_img = get_avatar_url($email);
            } else {
                $userId = get_current_user_id();
                if ($userId) {
                    $reviewer_img = esc_url(get_avatar_url($userId));
                }
            }
        }

        $testimonial_fields = [];
        if($platformName === 'testimonial') {
            $testimonial_fields = [
                'is_fluent_forms'       => true,
                'author_position'       => Arr::get($feedData, 'author_position', ''),
                'author_company'        => Arr::get($feedData, 'author_company', ''),
                'author_website_logo'   => Arr::get($feedData, 'author_website_logo', ''),
                'author_website_url'    => Arr::get($feedData, 'author_website_url', ''),
            ];
        }

        $global_settings          =  get_option('wpsr_fluent_forms_global_settings');
        $manually_review_approved = Arr::get($global_settings, 'global_settings.manually_review_approved', 'false');
        $review_approved          = $manually_review_approved === 'true' ? 0 : 1;

        $reviewer_name = Arr::get($feedData, 'reviewer_name', '');
        if( $platformName === 'woocommerce' ) {
            $sourceId = Arr::get($feedData, 'source_id', null);
        } else{
            $sourceId = $form->id;
        }

        $category = Arr::get($feedData, 'category', '');
        $reviewer_name = Arr::get($feedData, 'name', '');
        $review_title = Arr::get($feedData, 'title', '');
        $review_text = Arr::get($feedData, 'comment', '');
        if(method_exists(GlobalHelper::class, 'sanitizeForStorage')) {
            $category = GlobalHelper::sanitizeForStorage($category);
            $reviewer_name = GlobalHelper::sanitizeForStorage($reviewer_name);
            $review_title = GlobalHelper::sanitizeForStorage($review_title);
            $review_text = GlobalHelper::sanitizeForStorage($review_text);
        }

        $insert_data  = [
            'platform_name' => $platformName,
            'source_id'     => intval($sourceId),
            'category'      => $category,
            'reviewer_name' => $reviewer_name,
            'review_title'  => $review_title,
            'reviewer_url'  => Arr::get($feedData, 'reviewer_url', ''),
            'reviewer_img'  => $reviewer_img,
            'reviewer_text' => $review_text,
            'rating'        => intval(Arr::get($feedData, 'ratings')),
            'review_approved' => $review_approved,
            'fields'        => array_merge($testimonial_fields, ['review_images' => Arr::get($feedData, 'review_images')]), // Add review images to fields
            'review_time'   => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
            'created_at'    => current_time('mysql')
        ];

        $response = Review::create($insert_data);
        if ($review_approved == 1) {
            $businessInfoDataFormatted = [
                'source_id' => intval($sourceId),
                'name' => $form->title,
                'platform_name' => $platformName,
            ];
            $businessInfo = $this->saveBusinessInfo($businessInfoDataFormatted);

            update_option('wpsr_reviews_' . $platformName . '_business_info', $businessInfo, false);
        }

        if (is_wp_error($response)) {
            do_action('ff_integration_action_result', $feed, 'failed', $response->get_error_message());
            return false;
        } else {
            do_action('ff_integration_action_result',
                $feed,
                'success',
                'WP Social Ninja data inserted. Review ID: '.$response->id
            );

            return true;
        }
    }

    public function getBusinessInfo($platformName)
    {
        return get_option('wpsr_reviews_'.$platformName.'_business_info');
    }

    public function saveBusinessInfo($data = array())
    {
        $sourceId = Arr::get($data, 'source_id');
        $platformName = Arr::get($data, 'platform_name');

        if (!$sourceId) {
            return [];
        }

        $reviewsQuery = Review::where('platform_name', $platformName)
            ->where('source_id', $sourceId);

        $totalReviews = $reviewsQuery->count();
        $avgRating = $totalReviews > 0 ? $reviewsQuery->avg('rating') : 0;

        if($platformName === 'woocommerce' && defined('WC_VERSION')){
           $name = get_the_title($sourceId);
        } else {
           $name = Arr::get($data, 'name');
        }

        $businessInfo = [
            'place_id' => $sourceId,
            'name' => $name,
            'url' => '',
            'address' => '',
            'average_rating' => $avgRating,
            'total_rating' => $totalReviews,
            'phone' => '',
            'platform_name' => $platformName,
            'status' => true,
        ];

        $existingInfos = $this->getBusinessInfo($platformName) ?: [];
        $existingInfos[$sourceId] = $businessInfo;

        return $existingInfos;
    }

    public function isEnabled()
    {
        return true;
    }

    public function isConfigured()
    {
        return true;
    }

    public function clearVerificationConfigs($userId)
    {
        
    }
}