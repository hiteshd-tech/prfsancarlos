<?php
/**
 * Charitable Elementor Integration
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Charitable Elementor Integration
 *
 * @since 1.8.6
 */
class Charitable_Elementor {

    /**
     * Single instance of this class.
     *
     * @since 1.8.6
     *
     * @var   Charitable_Elementor
     */
    private static $instance = null;

    /**
     * Create class instance.
     *
     * @since 1.8.6
     */
    private function __construct() {

        // if the CHARITABLE_DISABLE_ELEMENTOR_INTEGRATION constant is defined, don't load the elementor integration
        if ( defined( 'CHARITABLE_DISABLE_ELEMENTOR_INTEGRATION' ) && CHARITABLE_DISABLE_ELEMENTOR_INTEGRATION ) {
            return;
        }

        $this->load_dependencies();
        $this->setup_hooks();
    }

    /**
     * Initialize the class.
     *
     * @since 1.8.6
     */
    public static function init() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        self::get_instance();
    }

    /**
     * Include necessary files.
     *
     * @since  1.8.6
     *
     * @return void
     */
    private function load_dependencies() {
        require_once charitable()->get_path( 'includes' ) . 'elementor/widgets/class-charitable-elementor-campaign-widget.php';
        require_once charitable()->get_path( 'includes' ) . 'elementor/widgets/class-charitable-elementor-button-widget.php';
        require_once charitable()->get_path( 'includes' ) . 'elementor/widgets/class-charitable-elementor-donation-form-widget.php';
        require_once charitable()->get_path( 'includes' ) . 'elementor/widgets/class-charitable-elementor-campaigns-widget.php';
    }

    /**
     * Set up hook and filter callback functions.
     *
     * @since  1.8.6
     *
     * @return void
     */
    private function setup_hooks() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_charitable_category' ) );
        add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
        add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
    }

    /**
     * Register Charitable widgets.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     * @since 1.8.6
     */
    public function register_widgets( $widgets_manager ) {
        $widgets_manager->register( new \Charitable_Elementor_Campaign_Widget() );
        $widgets_manager->register( new \Charitable_Elementor_Button_Widget() );
        $widgets_manager->register( new \Charitable_Elementor_Donation_Form_Widget() );
        $widgets_manager->register( new \Charitable_Elementor_Campaigns_Widget() );
    }

    /**
     * Add Charitable category to Elementor.
     *
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
     * @since 1.8.6
     */
    public function add_elementor_charitable_category( $elements_manager ) {
        $elements_manager->add_category(
            'wpcharitable',
            [
                'title' => esc_html__( 'Charitable', 'charitable' ),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Register scripts.
     *
     * @since 1.8.6
     */
    public function register_scripts() {
        wp_enqueue_script(
            'charitable-elementor-preview',
            charitable()->get_path( 'assets', false ) . 'js/elementor/charitable-elementor-preview.js',
            array( 'jquery' ),
            charitable()->get_version(),
            true
        );

        wp_localize_script(
            'charitable-elementor-preview',
            'charitableElementor',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'charitable_elementor_preview' ),
                'debug'   => true,
            )
        );
    }

    /**
     * Register styles.
     *
     * @since 1.8.6
     */
    public function register_styles() {
        wp_enqueue_style(
            'charitable-elementor-editor',
            charitable()->get_path( 'assets', false ) . '/css/elementor/charitable-elementor-editor.css',
            array(),
            charitable()->get_version()
        );
    }

    /**
     * Returns the original instance of this class.
     *
     * @since  1.8.6
     *
     * @return Charitable_Elementor
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}