<?php

/**
 * Charitable Campaign Widget
 *
 * @package Elementor_Example_Plugin
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Charitable_Campaign_Widget
 */
class Charitable_Elementor_Campaign_Widget extends \Elementor\Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'charitable_campaign';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return esc_html__('Campaign', 'charitable');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'eicon-site-identity';
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return array( 'wpcharitable' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls()
    {
        // Content Section.
        $this->start_controls_section(
            'content_section',
            array(
                'label' => esc_html__('Content', 'charitable'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'title',
            array(
                'label'   => esc_html__('Title', 'charitable'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Campaign', 'charitable'),
            )
        );

        // Get campaigns for dropdown.
        $campaigns = get_posts(
            array(
                'post_type'   => 'campaign',
                'numberposts' => -1,
                'orderby'     => 'title',
                'order'       => 'ASC',
            )
        );

        $campaign_options = array();
        foreach ($campaigns as $campaign) {
            $campaign_options[ $campaign->ID ] = $campaign->post_title;
        }

        $this->add_control(
            'campaign_id',
            array(
                'label'   => esc_html__('Select Campaign', 'charitable'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $campaign_options,
                'label_block' => true,
                'dynamic' => ['active' => true],
            )
        );


        $this->end_controls_section();

        // Style Section.
        $this->start_controls_section(
            'style_section',
            array(
                'label' => esc_html__('Style', 'charitable'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'padding',
            array(
                'label'      => esc_html__('Padding', 'charitable'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .charitable-campaign-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'border',
                'selector' => '{{WRAPPER}} .charitable-campaign-wrapper',
            )
        );

        $this->add_control(
            'background_color',
            array(
                'label'     => esc_html__('Background Color', 'charitable'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .charitable-campaign-wrapper' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $output = '<div class="charitable-campaign-wrapper">';
        if (! empty($settings['title'])) {
            $output .= '<h2 class="charitable-campaign-title">' . esc_html($settings['title']) . '</h2>';
        }

        if (! empty($settings['campaign_id'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $shortcode = sprintf('[campaign id="%d"]', absint($settings['campaign_id']));
            } else {
                $shortcode = sprintf('[campaign id="%d"]', absint($settings['campaign_id']));
            }

            $output .= do_shortcode($shortcode);
        }
        $output .= '</div>';

        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }


}
