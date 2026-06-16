<?php
/**
 * The class that defines a subpanel for the settings area for the campaign builder.
 *
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version.  1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Settings_Campaign_Summary' ) ) :

	/**
	 * General subpanel for Settings Panel for campaign builder.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Settings_Campaign_Summary {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $slug = 'campaign-summary';

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

			$this->primary_label = esc_html__( 'Campaign Summary', 'charitable' );

			add_action( 'charitable_campaign_builder_settings_sidebar', array( $this, 'sidebar_tab' ) );
			add_action( 'charitable_campaign_builder_settings_panels', array( $this, 'panel_content' ) );
		}

		/**
		 * Generate sidebar html.
		 *
		 * @since 1.8.0
		 */
		public function sidebar_tab() {

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;

			echo '<a href="#" class="charitable-panel-sidebar-section charitable-panel-sidebar-section-' . esc_attr( $this->slug ) . ' ' . esc_attr( $active ) . '" data-section="' . esc_attr( $this->slug ) . '">' . esc_html( $this->primary_label ) . ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>';
		}

		/**
		 * Generate panel content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();
			$settings                       = new Charitable_Builder_Panel_Settings();

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;
			$style  = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'display: block;' : false;

			ob_start();

			?>

			<div class="charitable-panel-content-section charitable-panel-content-section-<?php echo esc_attr( $this->slug ); ?> <?php echo esc_attr( $active ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<div class="charitable-panel-content-section-title"><?php echo esc_html( $this->primary_label ); ?></div>

				<?php

			echo $charitable_builder_form_fields->generate_checkboxes( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$settings->campaign_data_settings( '', 'campaign-summary' ),
					esc_html__( 'Hide Information', 'charitable' ),
					array(
						'id'      => 'charitable-campaign-suggested-donations',
						'name'    => array( 'settings', esc_attr( $this->slug ) ),
						'options' => array(
							'Amount Donated'   => 'campaign_hide_amount_donated',
							'Number of Donors' => 'campaign_hide_number_of_donors',
							'Percent Raised'   => 'campaign_hide_percent_raised',
							'Time Remaining'   => 'campaign_hide_time_remaining',
						),
					)
				);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$settings->campaign_data_settings( 'donation_button_text', 'campaign-summary' ),
					esc_html__( 'Donation Button Text', 'charitable' ),
					array(
						'id'          => 'campaign_donation_button_text',
						'name'        => array( 'settings', esc_attr( $this->slug ), 'donation_button_text' ),
						'placeholder' => 'Donate',
					)
				);

				?>

			</div>

			<?php

			$html = ob_get_clean();

			echo wp_kses_post( $html );
		}
	}

	new Charitable_Builder_Panel_Settings_Campaign_Summary();

endif;