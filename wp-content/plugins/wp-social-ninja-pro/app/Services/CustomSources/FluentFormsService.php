<?php

namespace WPSocialReviewsPro\App\Services\CustomSources;

use WPSocialReviews\Framework\Support\Arr;

class FluentFormsService
{
    private const INTEGRATION_META_KEY = 'fluentform_wp_social_ninja_reviews';

    /**
     * Validate existing Fluent Form and get integration
     */
    public function validateExistingForm(int $formId)
    {
        $form = wpsrDb()->table('fluentform_forms')->where('id', $formId)->first();

        if (!$form) {
            throw new \Exception('Form with ID ' . $formId . ' not found');
        }

        // Check for existing WP Social Ninja integration
        $integration = wpsrDb()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', static::INTEGRATION_META_KEY)
            ->first();

        return [
            'form_id' => $formId,
            'integration_id' => $integration ? $integration->id : null
        ];
    }

    /**
     * Create new Fluent Form with integration
     */
    public function createForm(string $templateId)
    {
        $template = $this->getTemplate($templateId);

        $formId = $this->createFluentForm($template);
        $integrationId = $this->createIntegration($formId, $template);

        return [
            'form_id' => $formId,
            'integration_id' => $integrationId,
            'label' => $template['label']
        ];
    }

    /**
     * Get available form templates
     */
    public function getTemplates()
    {
        return apply_filters('wpsocialreviews/ff_form_templates', [
            'classic_review_form' => [
                'label' => __('Classic Review Form', 'wp-social-reviews'),
                'image' => '',
                'id' => 'classic_review_form',
                'form_fields' => $this->getClassicFormFields(),
                'form_meta_value' => $this->getClassicFormMetaValue(),
                'custom_css' => '',
            ],
            'modern_review_form' => [
                'label' => __('Modern Review Form', 'wp-social-reviews'),
                'image' => '',
                'id' => 'modern_review_form',
                'form_fields' => $this->getModernFormFields(),
                'form_meta_value' => $this->getModernFormMetaValue(),
                'custom_css' => '',
            ],
        ]);
    }

    /**
     * Get template by ID
     */
    private function getTemplate(string $templateId)
    {
        $templates = $this->getTemplates();

        if (isset($templates[$templateId])) {
            return $templates[$templateId];
        }

        // Return first template as fallback
        $templatesArray = array_values($templates);
        return $templatesArray[0];
    }

    /**
     * Create Fluent Form
     */
    private function createFluentForm(array $template)
    {
        $now = current_time('mysql');

        $formData = [
            'title' => $template['label'],
            'status' => 'published',
            'type' => 'form',
            'created_by' => get_current_user_id(),
            'created_at' => $now,
            'updated_at' => $now,
            'form_fields' => $template['form_fields']
        ];

        $formData = apply_filters('wpsocialreviews/before_form_created', $formData);

        $formId = wpsrDb()->table('fluentform_forms')->insertGetId($formData);

        // Add default form settings
        $this->addDefaultFormSettings($formId);

        return $formId;
    }

    /**
     * Create integration for the form
     */
    private function createIntegration(int $formId, array $template)
    {
        $feedData = [
            'meta_key' => static::INTEGRATION_META_KEY,
            'form_id' => $formId,
            'value' => $template['form_meta_value'],
        ];

        return wpsrDb()->table('fluentform_form_meta')->insertGetId($feedData);
    }

    /**
     * Add default form settings
     */
    private function addDefaultFormSettings(int $formId)
    {
        $defaultSettings = (new \FluentForm\App\Modules\Form\Form(wpFluentForm()))->getFormsDefaultSettings();

        wpsrDb()->table('fluentform_form_meta')->insert([
            'form_id' => $formId,
            'meta_key' => 'formSettings',
            'value' => json_encode($defaultSettings)
        ]);
    }

    /**
     * Get classic form fields JSON
     */
    private function getClassicFormFields()
    {
        return '{"fields":[{"index":10,"element":"wpsr_rating_elem","attributes":{"class":"","name":"wpsr_rating_elem","value":0},"settings":{"label":"Ratings","show_text":"no","help_message":"","label_placement":"","admin_field_label":"Social Ninja Ratings","container_class":"","conditional_logics":[],"validation_rules":{"required":{"value":true,"message":"Rating is required","global":true,"global_message":"This field is required"}}},"options":{"1":"Nice","2":"Good","3":"Very Good","4":"Awesome","5":"Amazing"},"editor_options":{"title":"Social Ninja Ratings","icon_class":"ff-edit-rating","template":"ratings"},"uniqElKey":"el_1759906518811"},{"index":3,"element":"textarea","attributes":{"name":"description","value":"","id":"","class":"","placeholder":"","rows":3,"cols":2,"maxlength":""},"settings":{"container_class":"","label":"Review","admin_field_label":"","label_placement":"","help_message":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true}},"conditional_logics":[]},"editor_options":{"title":"Text Area","icon_class":"ff-edit-textarea","template":"inputTextarea"},"uniqElKey":"el_1759906533186"},{"index":2,"element":"input_text","attributes":{"type":"text","name":"input_text","value":"","class":"","placeholder":"","maxlength":""},"settings":{"container_class":"","label":"First and last name ","label_placement":"","admin_field_label":"","help_message":"","prefix_label":"","suffix_label":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true}},"conditional_logics":[],"is_unique":"no","unique_validation_message":"This value need to be unique."},"editor_options":{"title":"Simple Text","icon_class":"ff-edit-text","template":"inputText"},"uniqElKey":"el_1759906500248"},{"index":1,"element":"input_email","attributes":{"type":"email","name":"email","value":"","id":"","class":"","placeholder":"Email Address"},"settings":{"container_class":"","label":"Email","label_placement":"","help_message":"","admin_field_label":"","prefix_label":"","suffix_label":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true},"email":{"value":true,"message":"This field must contain a valid email","global_message":"This field must contain a valid email","global":true}},"conditional_logics":[],"is_unique":"no","unique_validation_message":"Email address need to be unique."},"editor_options":{"title":"Email","icon_class":"ff-edit-email","template":"inputText"},"uniqElKey":"el_1759906511439"}],"submitButton":{"uniqElKey":"el_1524065200616","element":"button","attributes":{"type":"submit","class":""},"settings":{"align":"left","button_style":"default","container_class":"","help_message":"","background_color":"#1a7efb","button_size":"md","color":"#ffffff","button_ui":{"type":"default","text":"Submit Review","img_url":""},"normal_styles":{"backgroundColor":"#1a7efb","borderColor":"#1a7efb","color":"#ffffff","borderRadius":"","minWidth":""},"hover_styles":{"backgroundColor":"#ffffff","borderColor":"#1a7efb","color":"#1a7efb","borderRadius":"","minWidth":""},"current_state":"normal_styles"},"editor_options":{"title":"Submit Button"}}}';
    }

    /**
     * Get modern form fields JSON
     */
    private function getModernFormFields()
    {
        return '{"fields":[{"index":2,"element":"input_text","attributes":{"type":"text","name":"input_text_1","value":"","class":"","placeholder":"","maxlength":""},"settings":{"container_class":"","label":"Write a short title for your experience","label_placement":"","admin_field_label":"","help_message":"","prefix_label":"","suffix_label":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true}},"conditional_logics":[],"is_unique":"no","unique_validation_message":"This value need to be unique."},"editor_options":{"title":"Simple Text","icon_class":"ff-edit-text","template":"inputText"},"uniqElKey":"el_1761204058499"},{"index":3,"element":"textarea","attributes":{"name":"description","value":"","id":"","class":"","placeholder":"","rows":3,"cols":2,"maxlength":""},"settings":{"container_class":"","label":"Tell us about your experience","admin_field_label":"","label_placement":"","help_message":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true}},"conditional_logics":[]},"editor_options":{"title":"Text Area","icon_class":"ff-edit-textarea","template":"inputTextarea"},"uniqElKey":"el_1761204009693"},{"index":10,"element":"wpsr_rating_elem","attributes":{"class":"","name":"wpsr_rating_elem","value":0},"settings":{"label":"How would you rate your experience?","show_text":"no","help_message":"","label_placement":"","admin_field_label":"Social Ninja Ratings","container_class":"","conditional_logics":[],"validation_rules":{"required":{"value":true,"message":"Rating is required","global":true,"global_message":"This field is required"}}},"options":{"1":"Nice","2":"Good","3":"Very Good","4":"Awesome","5":"Amazing"},"editor_options":{"title":"Social Ninja Ratings","icon_class":"ff-edit-rating","template":"ratings"},"uniqElKey":"el_1759906518811"},{"index":2,"element":"input_text","attributes":{"type":"text","name":"input_text","value":"","class":"","placeholder":"","maxlength":""},"settings":{"container_class":"","label":"First and last name ","label_placement":"","admin_field_label":"","help_message":"","prefix_label":"","suffix_label":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true}},"conditional_logics":[],"is_unique":"no","unique_validation_message":"This value need to be unique."},"editor_options":{"title":"Simple Text","icon_class":"ff-edit-text","template":"inputText"},"uniqElKey":"el_1759906500248"},{"index":1,"element":"input_email","attributes":{"type":"email","name":"email","value":"","id":"","class":"","placeholder":"Email Address"},"settings":{"container_class":"","label":"Email Address","label_placement":"","help_message":"","admin_field_label":"","prefix_label":"","suffix_label":"","validation_rules":{"required":{"value":true,"message":"This field is required","global_message":"This field is required","global":true},"email":{"value":true,"message":"This field must contain a valid email","global_message":"This field must contain a valid email","global":true}},"conditional_logics":[],"is_unique":"no","unique_validation_message":"Email address need to be unique."},"editor_options":{"title":"Email","icon_class":"ff-edit-email","template":"inputText"},"uniqElKey":"el_1759906511439"}],"submitButton":{"uniqElKey":"el_1524065200616","element":"button","attributes":{"type":"submit","class":""},"settings":{"align":"left","button_style":"default","container_class":"","help_message":"","background_color":"#1a7efb","button_size":"md","color":"#ffffff","button_ui":{"type":"default","text":"Submit Review","img_url":""},"normal_styles":{"backgroundColor":"#1a7efb","borderColor":"#1a7efb","color":"#ffffff","borderRadius":"","minWidth":""},"hover_styles":{"backgroundColor":"#ffffff","borderColor":"#1a7efb","color":"#1a7efb","borderRadius":"","minWidth":""},"current_state":"normal_styles"},"editor_options":{"title":"Submit Button"}}}';
    }

    /**
     * Get default meta value for integration
     */
    private function getClassicFormMetaValue()
    {
        return '{"name":"WP Social Ninja Integration Feed","list_id":"fluent_forms","conditionals":{"conditions":[{"field":null,"operator":"=","value":null}],"status":false,"type":"all"},"enabled":true,"ratings":"{inputs.wpsr_rating_elem}","reviewer_name":"{inputs.input_text}","email":"{inputs.email}","comment":"{inputs.description}","source_id":"{inputs.hidden}"}';
    }

    private function getModernFormMetaValue()
    {
       return '{"name":"WP Social Ninja Integration Feed","list_id":"fluent_forms","conditionals":{"conditions":[{"field":null,"operator":"=","value":null}],"status":false,"type":"all"},"enabled":true,"ratings":"{inputs.wpsr_rating_elem}","reviewer_name":"{inputs.input_text}","email":"{inputs.email}","comment":"{inputs.description}","source_id":"{inputs.hidden}","title":"{inputs.input_text_1}"}';
    }
}