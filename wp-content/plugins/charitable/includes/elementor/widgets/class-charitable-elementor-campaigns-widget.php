<?php
/**
 * Charitable Campaign Widget
 *
 * @package Elementor_Example_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Charitable_Campaign_Widget
 */
class Charitable_Elementor_Campaigns_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'charitable_campaigns';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Campaigns', 'charitable' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'wpcharitable' );
	}

	/**
	 * Check if we're in Elementor editor mode.
	 *
	 * @return bool Whether we're in editor mode.
	 */
	private function is_elementor_editor() {
		return \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode();
	}

	/**
	 * Register widget styles.
	 */
	public function register_styles() {
		wp_register_style(
			'charitable-elementor-editor',
			charitable()->get_path( 'assets', false ) . 'css/charitable-elementor-editor.css',
			array(),
			charitable()->get_version()
		);
	}

	/**
	 * Enqueue editor-specific styles.
	 */
	public function enqueue_editor_styles() {
		if ( $this->is_elementor_editor() ) {
			wp_enqueue_style( 'charitable-elementor-editor' );
		}
	}

	/**
	 * Widget constructor.
	 *
	 * @param array $data Widget data.
	 * @param array $args Widget arguments.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		// Register and enqueue styles
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__( 'Campaign Display Settings', 'charitable' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Number of campaigns
		$this->add_control(
			'number',
			array(
				'label'       => esc_html__( 'Number of Campaigns', 'charitable' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => esc_html__( 'Set to -1 to show all campaigns. Default is the number of blog posts configured in WordPress settings.', 'charitable' ),
				'default'     => '',
				'placeholder' => '',
			)
		);

		// Order By
		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'charitable' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'post_date' => esc_html__( 'Date', 'charitable' ),
					'popular'   => esc_html__( 'Popularity', 'charitable' ),
					'ending'    => esc_html__( 'Ending Soon', 'charitable' ),
					'title'     => esc_html__( 'Title', 'charitable' ),
					'rand'      => esc_html__( 'Random', 'charitable' ),
				),
				'default' => 'post_date',
			)
		);

		// Order
		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Sort Order', 'charitable' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'DESC' => esc_html__( 'Descending', 'charitable' ),
					'ASC'  => esc_html__( 'Ascending', 'charitable' ),
				),
				'default' => 'DESC',
			)
		);

		// Categories
		$this->add_control(
			'category',
			array(
				'label'       => esc_html__( 'Categories', 'charitable' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'Filter by category slug. For multiple categories, separate with commas.', 'charitable' ),
				'placeholder' => 'category-1,category-2',
			)
		);

		// Creator
		$this->add_control(
			'creator',
			array(
				'label'       => esc_html__( 'Creator', 'charitable' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => esc_html__( 'Only show campaigns created by a specific user ID.', 'charitable' ),
				'placeholder' => esc_html__( 'User ID', 'charitable' ),
			)
		);

		// Exclude
		$this->add_control(
			'exclude',
			array(
				'label'       => esc_html__( 'Exclude Campaigns', 'charitable' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => esc_html__( 'Campaign IDs to exclude. Separate multiple IDs with commas.', 'charitable' ),
				'placeholder' => '12,34,56',
			)
		);

		// Include Inactive
		$this->add_control(
			'include_inactive',
			array(
				'label'        => esc_html__( 'Include Inactive Campaigns', 'charitable' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'charitable' ),
				'label_off'    => esc_html__( 'No', 'charitable' ),
				'return_value' => 'true',
				'default'      => '',
			)
		);

		// Columns
		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'charitable' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'1' => esc_html__( '1 Column', 'charitable' ),
					'2' => esc_html__( '2 Columns', 'charitable' ),
					'3' => esc_html__( '3 Columns', 'charitable' ),
					'4' => esc_html__( '4 Columns', 'charitable' ),
				),
				'default' => '2',
			)
		);

		// Button
		// $this->add_control(
		// 'button',
		// array(
		// 'label'   => esc_html__('Button Type', 'charitable'),
		// 'type'    => \Elementor\Controls_Manager::SELECT,
		// 'options' => array(
		// 'donate'  => esc_html__('Donate Button', 'charitable'),
		// 'details' => esc_html__('Read More Link', 'charitable'),
		// '0'       => esc_html__('None', 'charitable'),
		// ),
		// 'default' => 'donate',
		// )
		// );

		// // Specific IDs
		// $this->add_control(
		// 'id',
		// array(
		// 'label'       => esc_html__('Specific Campaign IDs', 'charitable'),
		// 'type'        => \Elementor\Controls_Manager::TEXT,
		// 'description' => esc_html__('Show only specific campaigns by ID. Separate multiple IDs with commas.', 'charitable'),
		// 'placeholder' => '123,456,789',
		// )
		// );

		// Responsive
		// $this->add_control(
		// 'responsive',
		// array(
		// 'label'        => esc_html__('Responsive Layout', 'charitable'),
		// 'type'         => \Elementor\Controls_Manager::SELECT,
		// 'options'      => array(
		// ''       => esc_html__('Default (768px)', 'charitable'),
		// '0'      => esc_html__('Disabled', 'charitable'),
		// 'custom' => esc_html__('Custom Breakpoint', 'charitable'),
		// ),
		// 'default'      => '',
		// )
		// );

		// // Custom responsive breakpoint (shown conditionally)
		// $this->add_control(
		// 'responsive_custom',
		// array(
		// 'label'       => esc_html__('Custom Breakpoint', 'charitable'),
		// 'type'        => \Elementor\Controls_Manager::TEXT,
		// 'description' => esc_html__('Enter a value with px or em (e.g. 480px)', 'charitable'),
		// 'placeholder' => '480px',
		// 'condition'   => array(
		// 'responsive' => 'custom',
		// ),
		// )
		// );

		// Masonry
		// $this->add_control(
		// 'masonry',
		// array(
		// 'label'        => esc_html__('Masonry Layout', 'charitable'),
		// 'type'         => \Elementor\Controls_Manager::SWITCHER,
		// 'label_on'     => esc_html__('Yes', 'charitable'),
		// 'label_off'    => esc_html__('No', 'charitable'),
		// 'return_value' => '1',
		// 'default'      => '',
		// )
		// );

		$this->end_controls_section();

		// Style Section.
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Style', 'charitable' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => esc_html__( 'Padding', 'charitable' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .charitable-campaigns-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'border',
				'selector' => '{{WRAPPER}} .charitable-campaigns-wrapper',
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => esc_html__( 'Background Color', 'charitable' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .charitable-campaigns-wrapper' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Determine if we're in Elementor editor
		$is_editor = $this->is_elementor_editor();

		// Start wrapper with appropriate classes
		$wrapper_classes = array( 'charitable-elementor-editor-wrapper' );
		if ( $is_editor ) {
			$wrapper_classes[] = 'in-elementor-editor';
		}

		$output = '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';

		// Build shortcode attributes
		$shortcode_atts = array();

		// Number of campaigns
		if ( ! empty( $settings['number'] ) ) {
			$shortcode_atts[] = 'number="' . absint( $settings['number'] ) . '"';
		}

		// Order By
		if ( ! empty( $settings['orderby'] ) ) {
			$shortcode_atts[] = 'orderby="' . esc_attr( $settings['orderby'] ) . '"';
		}

		// Order
		if ( ! empty( $settings['order'] ) ) {
			$shortcode_atts[] = 'order="' . esc_attr( $settings['order'] ) . '"';
		}

		// Categories
		if ( ! empty( $settings['category'] ) ) {
			$shortcode_atts[] = 'category="' . esc_attr( $settings['category'] ) . '"';
		}

		// Creator
		if ( ! empty( $settings['creator'] ) ) {
			$shortcode_atts[] = 'creator="' . absint( $settings['creator'] ) . '"';
		}

		// Exclude
		if ( ! empty( $settings['exclude'] ) ) {
			$shortcode_atts[] = 'exclude="' . esc_attr( $settings['exclude'] ) . '"';
		}

		// Include Inactive
		if ( ! empty( $settings['include_inactive'] ) && 'true' === $settings['include_inactive'] ) {
			$shortcode_atts[] = 'include_inactive="1"';
		}

		// Columns
		if ( ! empty( $settings['columns'] ) ) {
			$shortcode_atts[] = 'columns="' . absint( $settings['columns'] ) . '"';
		}

		// Build the complete shortcode
		$shortcode = '[campaigns ' . implode( ' ', $shortcode_atts ) . ']';

		// Process the shortcode
		$output .= do_shortcode( $shortcode );

		$output .= '</div>';

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
