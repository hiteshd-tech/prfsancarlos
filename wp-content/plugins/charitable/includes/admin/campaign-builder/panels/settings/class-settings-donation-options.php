<?php
/**
 * The class that defines a subpanel for the settings area for the campaign builder.
 *
 * @package   Charitable/Admin/Charitable_Campaign_Meta_Boxes
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Settings_Donation_Options' ) ) :

	/**
	 * General subpanel for Settings Panel for campaign builder.
	 *
	 * @since 1.8.0
	 * @version 1.8.9.1
	 */
	class Charitable_Builder_Panel_Settings_Donation_Options {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $slug = 'donation-options';

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

			$this->primary_label = esc_html__( 'Donation Options', 'charitable' );

			add_action( 'charitable_campaign_builder_settings_sidebar', array( $this, 'sidebar_tab' ), 10 );
			add_action( 'charitable_campaign_builder_settings_panels', array( $this, 'panel_content' ), 20 );

			add_filter( 'charitable_campaign_builder_localized_conditionals', array( $this, 'field_conditionals' ), 10, 1 );
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
		 * Add conditionals for fields in the donation options panel.
		 *
		 * @since 1.8.0
		 *
		 * @param array $conditionals The existing conditionals.
		 * @return array
		 */
		public function field_conditionals( $conditionals ) {

			$conditionals['#charitable-panel-field-settings-charitable-campaign-allow-recurring-donations'] = array( // toggle.
				'checked'   => array(
					'show' => array(
						'.charitable-panel-fields-group-recurring-donations .charitable-panel-field:not(#charitable-panel-field-settings-charitable-campaign-allow-recurring-donations-wrap)',
					),
				),
				'unchecked' => array(
					'hide' => array(
						'.charitable-panel-fields-group-recurring-donations .charitable-panel-field:not(#charitable-panel-field-settings-charitable-campaign-allow-recurring-donations-wrap)',
					),
				),
			);
			$conditionals['#settings-donation-options-recurring-donations-mode--advanced']                  = array( // radio
				'checked'   => array(
					'if'   => array(
						'#charitable-panel-field-settings-charitable-campaign-allow-recurring-donations' => 'checked',
					),
					'show' => array(
						'#charitable-panel-field-settings-campaign-recurring-donation-amounts-wrap, #charitable-panel-field-settings-charitable-campaign-allow-custom-recurring-donations-wrap, #charitable-panel-field-settings-campaign-recurring-default-tab-wrap',
					),
				),
				'unchecked' => array(
					'hide' => array(
						'#charitable-panel-field-settings-campaign-recurring-donation-amounts-wrap, #charitable-panel-field-settings-charitable-campaign-allow-custom-recurring-donations-wrap,
                                    #charitable-panel-field-settings-campaign-recurring-default-tab-wrap',
					),
				),
			);

			return $conditionals;
		}

		/**
		 * Generate panel content.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 */
		public function panel_content( $campaign_data = false ) { // phpcs:ignore

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();
			$settings                       = new Charitable_Builder_Panel_Settings();

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;
			$style  = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'display: block;' : false;

			ob_start();

			?>

			<div class="charitable-panel-content-section charitable-panel-content-section-<?php echo esc_attr( $this->slug ); ?> <?php echo esc_attr( $active ); ?>" style="<?php echo esc_html( $style ); ?>">

				<div class="charitable-panel-content-section-title"><?php echo esc_html( $this->primary_label ); ?></div>

				<?php

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$suggested_donations_default = $settings->campaign_data_settings( 'suggested_donations_default', 'donation-options' );

				echo $charitable_builder_form_fields->generate_donation_amounts( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$settings->campaign_data_settings( 'donation_amounts', 'donation-options' ),
					esc_html__( 'Suggested Donation Amounts', 'charitable' ),
					array(
						'id'      => 'campaign_donation_amounts',
						'name'    => array( 'settings', esc_attr( $this->slug ), 'donation_amounts' ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'default' => $suggested_donations_default,
					)
				);

				$currency_symbol_raw = function_exists( 'charitable_get_currency_helper' ) ? charitable_get_currency_helper()->get_currency_symbol() : 'images/campaign-builder/settings/goal_dollar.png';
				$currency_symbol = wp_kses_post( $currency_symbol_raw );

				echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$settings->campaign_data_settings( 'minimum_donation_amount', 'donation-options' ),
					esc_html__( 'Minimum Donation Amount', 'charitable' ),
					array(
						'id'              => 'campaign_minimum_donation_amount',
						'name'            => array( 'settings', esc_attr( $this->slug ), 'minimum_donation_amount' ),
						'placeholder'     => '',
						// 'add_commas'  => true,
						'description'     => esc_html__( 'Leave empty to allow no restrictions on how small the donation can be.', 'charitable' ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'icon'        => $currency_symbol,
						'container_class' => 'charitable-format-money',
					)
				);

					$default_allow_custom = ! empty( $_GET['campaign_id'] ) ? $settings->campaign_data_settings( 'allow_custom_donations', 'donation-options' ) : '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				echo $charitable_builder_form_fields->generate_toggle( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$default_allow_custom,
					esc_html__( 'Allow Custom Donations', 'charitable' ),
					array(
						'id'              => 'campaign_allow_custom_donations',
						'checked_value'   => '1',
						'name'            => array( 'settings', esc_attr( $this->slug ), 'allow_custom_donations' ),
						'container_class' => 'charitable-campaign-builder-allow-custom-donations',
					)
				);

					do_action( 'charitable_campaign_builder_settings_donation_options_after', $this->active, $this->slug );

				?>

			</div>

			<?php

			$html = ob_get_clean();

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	new Charitable_Builder_Panel_Settings_Donation_Options();

endif;
