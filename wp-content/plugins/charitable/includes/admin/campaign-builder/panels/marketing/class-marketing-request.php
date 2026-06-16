<?php
/**
 * The class that defines a subpanel for the marketing area for the campaign builder.
 *
 * @package   Charitable/Admin/Charitable_Campaign_Meta_Boxes
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version.  1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Marketing_Request' ) ) :

	/**
	 * Constant Contact subpanel for Marketing Panel for campaign builder.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Marketing_Request {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $slug = 'request';

		/**
		 * The label/headline at the top of the panel.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $primary_label = '';

		/**
		 * Determines if the tab is initially active on a fresh new page load.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $active = false;

		/**
		 * Get things going. Add action hooks for the sidebar menu and the panel itself.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			$this->primary_label = esc_html__( 'Don’t See Yours?', 'charitable' );

			add_action( 'charitable_campaign_builder_marketing_sidebar', array( $this, 'sidebar_tab' ) );
			add_action( 'charitable_campaign_builder_marketing_panels', array( $this, 'panel_content' ) );
		}

		/**
		 * Generate sidebar html.
		 *
		 * @since 1.8.0
		 */
		public function sidebar_tab() {

			$active = ( true === apply_filters( 'charitable_campaign_builder_marketing_sidebar_active', $this->active, esc_attr( $this->slug ) ) ) ? 'active' : false;

			echo '<a href="#" class="charitable-panel-sidebar-section charitable-panel-sidebar-section-' . esc_attr( $this->slug ) . ' ' . esc_attr( $active ) . '" data-section="' . esc_attr( $this->slug ) . '">'
				. '<img class="charitable-builder-sidebar-icon" src="' . esc_url( charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/marketing/' . esc_attr( $this->slug ) . '.png' ) . '" />'
				. esc_html( $this->primary_label )
				. ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>';
		}

		/**
		 * Generate panel content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;
			$style  = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'display: block;' : false;

			$panel = new Charitable_Builder_Panel_Marketing();

			ob_start();

			?>

			<div class="charitable-panel-content-section charitable-panel-content-section-parent-marketing charitable-panel-content-section-<?php echo esc_attr( $this->slug ); ?> <?php echo esc_attr( $active ); ?>" style="<?php echo esc_attr( $style ); ?>">

				<div class="charitable-panel-content-section-title"></div>

				<div class="charitable-panel-content-section-interior">

				<?php

					do_action( 'charitable_campaign_builder_before_marketing_' . $this->slug );

				?>

				<?php $panel->education_marketing_text( $this->primary_label, $this->slug, 'request' ); ?>

				<?php

					do_action( 'charitable_campaign_builder_after_marketing_' . $this->slug );

				?>

				</div>

			</div>

			<?php

			$html = ob_get_clean();

			echo $html; // phpcs:ignore
		}
	}

	new Charitable_Builder_Panel_Marketing_Request();

endif;