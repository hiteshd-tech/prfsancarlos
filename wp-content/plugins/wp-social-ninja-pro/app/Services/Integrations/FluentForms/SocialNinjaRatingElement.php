<?php
namespace WPSocialReviewsPro\App\Services\Integrations\FluentForms;

if (!defined('ABSPATH')) {
    exit;
}

use FluentForm\App\Services\FormBuilder\BaseFieldManager;

class SocialNinjaRatingElement extends BaseFieldManager
{
    /**
     * Wrapper class for repeat element
     * @var string
     */
    protected $wrapperClass = 'wpsr_rating_elem';

    public function __construct()
    {

        parent::__construct(
            'wpsr_rating_elem',
            'Social Ninja Ratings',
            ['Rating', 'social ninja'],
            'advanced'
        );

        add_filter('fluentform/fields_requiring_advanced_script', function ($advancedFields) {
            $advancedFields[] = 'wpsr_rating_elem';
            return $advancedFields;
        });
    }

    public function getComponent()
    {
        return [
            'index'          => 10,
            'element'        => 'wpsr_rating_elem',
            'attributes'     => array(
                'class'     => '',
                'name'      => 'wpsr_rating_elem',
                'value'     => 0,
            ),
            'settings'       => array(
                'label'              => $this->title,
                'show_text'          => 'no',
                'help_message'       => '',
                'label_placement'    => '',
                'admin_field_label'  => $this->title,
                'container_class'    => '',
                'conditional_logics' => [],
                'validation_rules'   => array(
                    'required' => [
                        'value'   => false,
                        'message' => __('Rating is required', 'wp-social-ninja-pro'),
                        'global'  => true,
                    ],
                ),
            ),
            'options' => [
                '1' => __('Nice', 'wp-social-ninja-pro'),
                '2' => __('Good', 'wp-social-ninja-pro'),
                '3' => __('Very Good', 'wp-social-ninja-pro'),
                '4' => __('Awesome', 'wp-social-ninja-pro'),
                '5' => __('Amazing', 'wp-social-ninja-pro'),
            ],
            'editor_options' => array(
                'title'      => $this->title,
                'icon_class' => 'ff-edit-rating',
                'template'   => 'ratings'
            ),
        ];
    }

    public function getGeneralEditorElements()
    {
        return [
            'label',
            'label_placement',
            'admin_field_label',
            'options',
            'show_text',
            'validation_rules',
        ];
    }

    public function getAdvancedEditorElements()
    {
        return [
            'help_message',
            'name',
            'conditional_logics',
        ];
    }

    /**
     * Compile and echo the html element
     * @param array $data [element data]
     * @param object $form [Form Object]
     * @return void
     */
    public function render($data, $form)
    {
        return (new \FluentForm\App\Services\FormBuilder\Components\Rating())->compile($data, $form);
    }
}