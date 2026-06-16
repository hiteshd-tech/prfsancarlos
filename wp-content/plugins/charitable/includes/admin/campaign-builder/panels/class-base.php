<?php
/**
 * Base class management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1, 1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel' ) ) :

	/**
	 * Base panel class.
	 *
	 * @since 1.8.0
	 */
	abstract class Charitable_Builder_Panel {

		/**
		 * Full name of the panel.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $slug;

		/**
		 * Font Awesome Icon used for the editor button, eg "fa-list".
		 *
		 * @since 1.8.0
		 *
		 * @var mixed
		 */
		public $icon = false;

		/**
		 * Priority order the field button should show inside the "Add Fields" tab.
		 *
		 * @since 1.8.0
		 *
		 * @var int
		 */
		public $order = 50;

		/**
		 * If panel contains a sidebar element or is full width.
		 *
		 * @since 1.8.0
		 *
		 * @var bool
		 */
		public $sidebar = false;

		/**
		 * If panel is acting as a button instead of showing a panel UI
		 *
		 * @since 1.8.0
		 *
		 * @var bool
		 */
		public $button = false;

		/**
		 * If panel is acting as a button, what's the url.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		public $url = false;

		/**
		 * Contain campaign object if we have one.
		 *
		 * @since 1.8.0
		 *
		 * @var object
		 */
		public $campaign;

		/**
		 * Contain array of the form data (post_content).
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data = array();

		/**
		 * Class instance.
		 *
		 * @since 1.8.0
		 *
		 * @var static
		 */
		private static $instance;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			// Force revision false for now, and this is a potential placeholder for future features.
			$revision = false;

			// Bootstrap.
			$this->init();

			// Load panel specific enqueues.
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueues' ], 15 );

			// Primary panel button.
			add_action( 'charitable_builder_panel_buttons', [ $this, 'button' ], $this->order, 2 );

			// Output.
			add_action( 'charitable_builder_panels', [ $this, 'panel_output' ], $this->order, 2 );

			$this->get_campaign_settings();

			// Save instance.
			self::$instance = $this;
		}

		/**
		 * Get class instance.
		 *
		 * @since 1.8.0
		 *
		 * @return static
		 */
		public static function instance() {

			if ( self::$instance === null || ! self::$instance instanceof static ) {
				self::$instance = new static();
			}

			return self::$instance;
		}

		/**
		 * Get campaign settings and set to the class var.
		 *
		 * @since 1.8.0
		 * @version 1.8.2 add maybe_unserialize
		 */
		public function get_campaign_settings() {

			// was a campaign ID passed (then we are editting an existin campaign form).
			if ( ! empty( $_GET['campaign_id'] ) ) { // phpcs:ignore
				$campaign_id = (int) $_GET['campaign_id']; // phpcs:ignore

				$this->campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

				if ( empty( $this->campaign_data ) || false === $this->campaign_data ) {
					$this->campaign_data = $this->get_default_settings();
				}

				if ( is_string( $this->campaign_data ) ) {
					$this->campaign_data = maybe_unserialize( $this->campaign_data );
				}

				$this->campaign_data['id'] = $this->campaign_data['campaign_id'] = $campaign_id; // this should be fixed at some point.

			} else {

				$this->campaign_data = $this->get_default_settings();

			}
		}

		/**
		 * Get default settings and set to the class var.
		 *
		 * @since 1.8.0
		 */
		public function get_default_settings() {

			if ( ! is_array( $this->campaign_data ) ) {
				$this->campaign_data = array();
			}

			$this->campaign_data['id'] = $this->campaign_data['campaign_id'] = 0;

			$campaign_name = isset( $_POST['campaign_name'] ) && '' !== trim( $_POST['campaign_name'] ) ? esc_html( $_POST['campaign_name'] ) : esc_html__( 'New Campaign', 'charitable' ); // phpcs:ignore

			$this->campaign_data['tabs']['campaign']['title'] = 'Story';
			$this->campaign_data['tabs']['campaign']['desc']  = 'Write Your Campaign\'s Story Here';

			$this->campaign_data['title'] = $campaign_name;
			$this->campaign_data['desc']  = false; // 'test description';

			$this->campaign_data['layout']['advanced']['tab_style'] = 'small';
			$this->campaign_data['layout']['advanced']['tab_size']  = 'medium';

			return $this->campaign_data;
		}

		/**
		 * All systems go. Used by children.
		 *
		 * @since 1.8.0
		 */
		public function init() {
		}

		/**
		 * Enqueue assets for the builder. Used by children.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {
		}

		/**
		 * Primary panel button in the left panel navigation.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 *
		 * @param mixed  $campaign Current campaign object.
		 * @param string $view The current view.
		 */
		public function button( $campaign, $view ) {

			$active          = $view === $this->slug ? 'active' : '';
			$license         = 'pro';
			$upgrade_class   = false;
			$upgrade_params  = false;
			$button_linkable = ( $this->button && $this->url ) ? 'type="button"' : false;
			$data_panel      = $button_linkable ? '' : 'data-panel="' . esc_attr( $this->slug ) . '"';

			?>

			<?php
			if ( $button_linkable ) :
				?>
				<a target="_blank" href="<?php echo esc_url( $this->url ); ?>"><?php endif; ?>

			<button <?php echo esc_attr( $button_linkable ); ?> class="charitable-panel-<?php echo esc_attr( $this->slug ); ?>-button <?php echo esc_attr( $upgrade_class ); ?> <?php echo esc_attr( $active ); ?>" <?php echo $data_panel; ?> <?php echo esc_attr( $upgrade_params ); ?>>
				<?php if ( $this->icon ) : ?>
					<img class="topbar_icon" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/' . $this->icon ); ?>" />
				<?php endif; ?>
				<span><?php echo esc_html( $this->name ); ?></span>
			</button>

			<?php
			if ( $button_linkable ) :
				?>
				</a><?php endif; ?>
			<?php
		}

		/**
		 * Output the contents of the panel.
		 *
		 * @since 1.8.0
		 *
		 * @param object $campaign Current campaign object.
		 * @param string $view Active Campaign Builder view (panel).
		 */
		public function panel_output( $campaign, $view = 'design' ) {

			$wrap          = $this->sidebar ? 'charitable-panel-sidebar-content' : 'charitable-panel-full-content';
			$classes       = [ 'charitable-panel' ];
			$field_slug_id = $this->slug;

			if ( in_array( $this->slug, [ 'design', 'settings', 'fields', 'revisions' ], true ) ) {
				$classes[] = 'charitable-panel-fields';
			}

			if ( in_array( $this->slug, [ 'design', 'revisions' ], true ) ) {
				$classes[]     = 'charitable-panel-design';
				$field_slug_id = 'design';
			}

			if ( in_array( $this->slug, [ 'settings', 'revisions' ], true ) ) {
				$classes[] = 'charitable-panel-settings';
			}

			if ( in_array( $this->slug, [ 'template', 'revisions' ], true ) ) {
				$classes[] = 'charitable-panel-template';
			}

			if ( $view == $this->slug ) {
				$classes[] = 'active';
			}

			printf( '<div class="%s" id="charitable-panel-%s">', charitable_sanitize_classes( $classes, true ), esc_attr( $field_slug_id ) ); // phpcs:ignore

			printf( '<div class="%s">', esc_attr( $wrap ) );

			if ( true === $this->sidebar ) {

				if ( $this->slug === 'design' ) {
					echo '<div class="charitable-panel-sidebar-toggle"><div class="charitable-panel-sidebar-toggle-vertical-line"></div><div class="charitable-panel-sidebar-toggle-icon"><i class="fa fa-angle-left"></i></div></div>';
				}

				echo '<div class="charitable-panel-sidebar">';

				do_action( 'charitable_builder_before_panel_sidebar', $this->campaign, $this->slug );

				$this->panel_sidebar();

				do_action( 'charitable_builder_after_panel_sidebar', $this->campaign, $this->slug );

				echo '</div>';

			}

			echo '<div class="charitable-panel-content-wrap">';

			echo '<div class="charitable-panel-content">';

			do_action( 'charitable_builder_before_panel_content', $this->campaign, $this->slug );

			$this->panel_content();

			do_action( 'charitable_builder_after_panel_content', $this->campaign, $this->slug );

			echo '</div>';

			echo '</div>';

			echo '</div>';

			echo '</div>';
		}

		/**
		 * Output the panel's sidebar if we have one.
		 *
		 * @since 1.8.0
		 */
		public function panel_sidebar() {
		}

		/**
		 * Output panel sidebar sections.
		 *
		 * @since 1.8.0
		 *
		 * @param string $name Sidebar section name.
		 * @param string $slug Sidebar section slug.
		 * @param string $icon Sidebar section icon.
		 */
		public function panel_sidebar_section( $name, $slug, $icon = '' ) {

			$default_classes = [
				'charitable-panel-sidebar-section',
				'charitable-panel-sidebar-section-' . $slug,
			];

			if ( $slug === 'default' ) {
				$default_classes[] = 'default';
			}

			if ( ! empty( $icon ) ) {
				$default_classes[] = 'icon';
			}

			/**
			 * Allow adding custom CSS classes to a sidebar section in the Campaign Builder.
			 *
			 * @since 1.8.0
			 *
			 * @param array  $classes Sidebar section classes.
			 * @param string $name    Sidebar section name.
			 * @param string $slug    Sidebar section slug.
			 * @param string $icon    Sidebar section icon.
			 */
			$classes = (array) apply_filters( 'charitable_builder_panel_sidebar_section_classes', [], $name, $slug, $icon );
			$classes = array_merge( $default_classes, $classes );

			echo '<a href="#" class="' . esc_attr( charitable_sanitize_classes( $classes, true ) ) . '" data-section="' . esc_attr( $slug ) . '">';

			if ( ! empty( $icon ) ) {
				echo '<img src="' . esc_url( $icon ) . '">';
			}

			echo esc_html( $name );

			echo '<i class="fa fa-angle-right charitable-toggle-arrow"></i>';

			echo '</a>';
		}

		/**
		 * Output the panel's primary content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {
		}
	}

endif;
