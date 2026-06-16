<?php
/**
 * The class that defines a subpanel for the settings area for the campaign builder.
 *
 * @package   Charitable/Admin/Charitable_Campaign_Meta_Boxes
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version.  1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Settings_Campaign_Creator' ) ) :

	/**
	 * General subpanel for Settings Panel for campaign builder.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Settings_Campaign_Creator {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $slug = 'campaign-creator';

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

			$this->primary_label = esc_html__( 'Campaign Creator', 'charitable' );

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

			echo '<a href="#" class="charitable-panel-sidebar-section charitable-panel-sidebar-section-' . $this->slug . ' ' . $active . '" data-section="' . $this->slug . '">' . $this->primary_label . ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Generate panel content.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 */
		public function panel_content( $campaign_data = false, $campaign_id = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();
			$settings                       = new Charitable_Builder_Panel_Settings();

			$campaign_id = ( ( false === $campaign_id && empty( $campaign_data['id'] ) ) && isset( $_GET['campaign_id'] ) ) ? intval( $_GET['campaign_id'] ) : intval( $campaign_id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$campaign_id = ( 0 === $campaign_id && ! empty( $campaign_data['id'] ) ) ? intval( $campaign_data['id'] ) : $campaign_id;

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;
			$style  = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'display: block;' : false;

			ob_start();

			?>

			<div class="charitable-panel-content-section charitable-panel-content-section-<?php echo esc_attr( $this->slug ); ?> <?php echo esc_attr( $active ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<div class="charitable-panel-content-section-title"><?php echo esc_html( $this->primary_label ); ?></div>

				<?php

					// get all users.
					$users    = get_users();
					$campaign = isset( $campaign_id ) && 0 !== $campaign_id ? get_post( $campaign_id ) : false;

				if ( ! empty( $users ) ) {

					$users_to_pass = array();

					foreach ( $users as $user ) {
						$users_to_pass[ $user->data->ID ] = $user->data->user_login;
					}

					$campaign_creator_id = ( false === $campaign ) ? get_current_user_id() : $campaign->post_author;

					echo $charitable_builder_form_fields->generate_campaign_creator_info( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$settings->campaign_data_settings( 'campaign_creator_id', 'campaign-creator' ),
						'',
						array(
							'id'         => 'campaign_campaign_creator_info',
							'creator_id' => intval( $campaign_creator_id ),
						)
					);

					echo $charitable_builder_form_fields->generate_dropdown( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$settings->campaign_data_settings( 'campaign_creator_id', 'campaign-creator' ),
						esc_html__( 'Change the campaign creator', 'charitable' ),
						array(
							'id'             => 'campaign_campaign_creator_id',
							'name'           => array( 'settings', esc_attr( $this->slug ), 'campaign_creator_id' ),
							'class'          => 'campaign-builder-campaign-creator-id',
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'options'        => $users_to_pass,
							'html'           => true,
							'selected_value' => intval( $campaign_creator_id ),
						)
					);

				}

				?>
			</div>

			<?php

			$html = ob_get_clean();

			echo wp_kses_post( $html );
		}
	}

	new Charitable_Builder_Panel_Settings_Campaign_Creator();

endif;
