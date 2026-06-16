<?php
/**
 * The class that defines a subpanel for the payment area for the campaign builder.
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

if ( ! class_exists( 'Charitable_Builder_Panel_Payment_Payrexx' ) ) :

	/**
	 * Payrexx subpanel for Marketing Panel for campaign builder.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Payment_Payrexx {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $slug = 'payrexx';

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
		 * Determines if the tab is available for the lite version. If not, the CTA popup will be displayed when clicked in the submenu.
		 *
		 * @since 1.8.1.12
		 *
		 * @var string
		 */
		private $not_available_for_lite = true;

		/**
		 * The URL to the plugin/addon in the local install.
		 *
		 * @since 1.8.1.4
		 *
		 * @var string
		 */
		private $needed_addon_url = 'charitable-payrexx/charitable-payrexx.php';

		/**
		 * The name of the plugin/addon.
		 *
		 * @since 1.8.1.4
		 *
		 * @var string
		 */
		private $needed_addon_name = 'Charitable Payrexx';

		/**
		 * The slug for the provider.
		 *
		 * @since 1.8.1.4
		 *
		 * @var string
		 */
		private $provider_slug = 'payrexx';

		/**
		 * Get things going. Add action hooks for the sidebar menu and the panel itself.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			$this->primary_label = esc_html__( 'Payrexx', 'charitable' );

			add_action( 'charitable_campaign_builder_payment_sidebar', array( $this, 'sidebar_tab' ) );
			add_action( 'charitable_campaign_builder_payment_panels', array( $this, 'panel_content' ) );
		}

		/**
		 * Generate sidebar html.
		 *
		 * @since 1.8.0
		 * @version 1.8.1.12 Added logic to show popup message if user is on lite version.
		 */
		public function sidebar_tab() {

			$not_available = ! charitable_is_pro() && $this->not_available_for_lite ? 'charitable-not-available' : '';
			$css_class     = ( '' === $not_available && true === apply_filters( 'charitable_campaign_builder_marketing_sidebar_active', $this->active, esc_attr( $this->slug ) ) ) ? 'active' : $not_available;
			$data_name     = esc_html__( 'ability to use', 'charitable' ) . ' ' . $this->primary_label;

			echo '<a href="#" class="charitable-panel-sidebar-section charitable-panel-sidebar-section-' . esc_attr( $this->slug ) . ' ' . esc_attr( $css_class ) . '" data-name="' . esc_html( $data_name ) . '" data-section="' . esc_attr( $this->slug ) . '">'
				. '<img class="charitable-builder-sidebar-icon" src="' . esc_url( charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/payment/' . esc_attr( $this->slug ) . '.png' ) . '" />'
				. esc_html( $this->primary_label )
				. ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>';
		}

		/**
		 * Generate panel content.
		 *
		 * @since 1.8.0
		 * @since 1.8.1.4 - Added logic to check if the plugin/addon is activated and display the appropriate content.
		 *
		 * @return void
		 */
		public function panel_content() {

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, esc_attr( $this->slug ) ) ) ? 'active' : false;
			$style  = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, esc_attr( $this->slug ) ) ) ? 'display: block;' : false;

			$panel = new Charitable_Builder_Panel_Payment();

			$button_url   = admin_url( 'admin.php?page=charitable-settings&tab=gateways' );
			$button_label = esc_html__( 'View Gateway Settings', 'charitable' );

			// Set the default URL and label for the button.
			$button_info = $panel->get_gateway_button_info( $this->provider_slug, $button_url, $button_label );

			$button_url   = $button_info['button_url'];
			$button_label = $button_info['button_label'];

			$slug = esc_attr( $this->slug );

			$status = $panel->check_plugin_status( $this->needed_addon_url );

			ob_start();

			?>

			<div class="charitable-panel-content-section charitable-panel-content-section-parent-marketing charitable-panel-content-section-<?php echo esc_attr( $this->slug ); ?> <?php echo esc_attr( $active ); ?>" style="<?php echo esc_attr( $style ); ?>">

				<div class="charitable-panel-content-section-title"><?php echo esc_html( $this->primary_label ); ?> <?php echo esc_html__( 'Settings', 'charitable' ); ?></div>

				<div class="charitable-panel-content-section-interior">

				<?php

					do_action( 'charitable_campaign_builder_before_payment_' . $slug );

				?>

				<?php

				// determine if the user is (1) using pro and (2) has the needed addon activated.
				if ( charitable_is_pro() && 'activated' === $status ) {

					// plugin is activated so proceed with displaying non-marketing, plugin content.
					$panel->plugin_activated_text( $this->primary_label, esc_attr( $this->slug ), $this->needed_addon_name, $button_label, $button_url, $this->needed_addon_url );

				} elseif ( charitable_is_pro() && 'installed' === $status ) {

					// plugin is installed but not activated so proceed with displaying...
					$panel->plugin_installed_text( $this->primary_label, esc_attr( $this->slug ), $this->needed_addon_name, $button_label, $button_url, $this->needed_addon_url );

				} else {

					// somehow if the user gets here, present the educational content.
					$panel->plugin_installed_text( $this->primary_label, esc_attr( $this->slug ), $this->needed_addon_name, $button_label, $button_url, $this->needed_addon_url );

				}

				?>

				<?php

					do_action( 'charitable_campaign_builder_after_payment_' . $slug );

				?>

				</div>

			</div>

			<?php

			$html = ob_get_clean();

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	new Charitable_Builder_Panel_Payment_Payrexx();

endif;