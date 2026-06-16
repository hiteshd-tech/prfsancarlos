<?php
/**
 * The class that defines a subpanel for the settings area for the campaign builder.
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

if ( ! class_exists( 'Charitable_Builder_Panel_Settings_General' ) ) :

	/**
	 * General subpanel for Settings Panel for campaign builder.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Settings_General {

		/**
		 * Slug.
		 *
		 * @since 1.8.0
		 *
		 * @var string
		 */
		private $slug = 'general';

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
		private $active = true;

		/**
		 * Get things going. Add action hooks for the sidebar menu and the panel itself.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			$this->primary_label = esc_html__( 'General Settings', 'charitable' );

			add_action( 'charitable_campaign_builder_settings_sidebar', array( $this, 'sidebar_tab' ), 1 );
			add_action( 'charitable_campaign_builder_settings_panels', array( $this, 'panel_content' ), 10 );
		}

		/**
		 * Generate sidebar html.
		 *
		 * @since 1.8.0
		 */
		public function sidebar_tab() {

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;

			echo '<a href="#" class="charitable-panel-sidebar-section charitable-panel-sidebar-section-' . esc_attr( $this->slug ) . ' ' . esc_attR( $active ) . '" data-section="' . esc_attr( $this->slug ) . '">' . esc_html( $this->primary_label ) . ' <i class="fa fa-angle-right charitable-toggle-arrow"></i></a>';
		}

		/**
		 * Displays the content of the general settings panel.
		 *
		 * @since 1.8.0
		 *
		 * @param array|bool $campaign_data Optional. An array of campaign data. Default is false.
		 */
		public function panel_content( $campaign_data = false ) { // phpcs:ignore

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();
			$settings                       = new Charitable_Builder_Panel_Settings();

			$active = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'active' : false;
			$style  = ( true === apply_filters( 'charitable_campaign_builder_settings_sidebar_active', $this->active, $this->slug ) ) ? 'display: block;' : false;

			$campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : false; // phpcs:ignore
			$campaign    = charitable_get_campaign( $campaign_id );
			$status      = ( false !== $campaign_id ) ? $campaign->get_status() : 'not-saved';
			$post_status = ( false !== $campaign_id ) ? get_post_status( $campaign_id ) : 'not-saved';

			if ( 'finished' === $status && $campaign->has_goal() ) {
				$status = $campaign->has_achieved_goal() ? 'successful' : 'unsuccessful';
			}

			switch ( $status ) {
				case 'active':
					$message = '<p>Your campaign is currently active and accepting donations.</p>';
					break;

				case 'unsuccessful':
					$message = '<p><i class="fa fa-exclamation-circle"></i> <strong>What This Means:</strong> Your campaign\'s end date has past and did not meet it\'s campaign goal. You can extend the campaign end date or create a new campaign when you are ready to try again.</p>';
					break;

				case 'successful':
					$message = '<p><i class="fa fa-exclamation-circle"></i> <strong>What This Means:</strong> Your campaign\'s end date has past and met it\'s campaign goal.</p>';
					break;

				case 'not-saved':
				default:
					$message = '<p><i class="fa fa-exclamation-circle"></i> <strong>What This Means:</strong> Your new campaign is unsaved. Click "Save" button in the top bar to save your campaign.</p>';
					break;
			}

			switch ( $post_status ) {
				case 'publish':
					$post_status = 'public';
					break;
				default:
					$post_status = $post_status;
					break;

			}

			$post_status = 'public';

			ob_start();

			?>

			<div class="charitable-panel-content-section charitable-panel-content-section-<?php echo esc_attr( $this->slug ); ?> <?php echo esc_attr( $active ); ?>" style="<?php echo esc_attr( $style ); ?>">

				<div class="charitable-panel-content-section-title"><?php echo esc_html( $this->primary_label ); ?></div>

				<?php

				do_action( 'charitable_campaign_builder_before_settings_general', $settings, $this->slug );

				echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore
					$settings->campaign_data_settings( 'description', 'general' ),
					esc_html__( 'Campaign Description', 'charitable' ),
					array(
						'id'     => 'campaign_description',
						'name'   => array( 'settings', esc_attr( $this->slug ), 'description' ),
						'class'  => 'campaign-builder-htmleditor',
						'rows'   => 10,
						'html'   => true,
						'hidden' => true,
					)
				);

				echo $charitable_builder_form_fields->generate_textbox( // phpcs:ignore
					$settings->campaign_data_settings( 'extended_description', 'general' ),
					esc_html__( 'Campaign Extended Description', 'charitable' ),
					array(
						'id'     => 'campaign_extended_description',
						'name'   => array( 'settings', esc_attr( $this->slug ), 'extended_description' ),
						'class'  => 'campaign-builder-htmleditor',
						'rows'   => 30,
						'html'   => true,
						'hidden' => true,
					)
				);

				echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
					$settings->campaign_data_settings( 'goal', 'general' ),
					esc_html__( 'Goal', 'charitable' ),
					array(
						'id'          => 'campaign_goal',
						'name'        => array( 'settings', esc_attr( $this->slug ), 'goal' ),
						'placeholder' => '∞',
						'add_commas'  => true,
						'tooltip'     => esc_html__( 'Set a monetary goal that you want to reach for this campaign. Leave blank for no goal.', 'charitable' ),
						'icon'        => function_exists( 'charitable_get_currency_helper' ) ? charitable_get_currency_helper()->get_currency_symbol() : 'images/campaign-builder/settings/goal_dollar.png', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					)
				);

				echo $charitable_builder_form_fields->generate_date( // phpcs:ignore
					$settings->campaign_data_settings( 'end_date', 'general' ),
					esc_html__( 'Ending Date', 'charitable' ),
					array(
						'id'          => 'campaign_end_date',
						'name'        => array( 'settings', esc_attr( $this->slug ), 'end_date' ),
						'placeholder' => '∞',
						'class'       => 'campaign-builder-datepicker',
						'label_below' => '',
						'tooltip'     => esc_html__( 'Set an ending date for your campaign, or leave blank to let the campaign run forever', 'charitable' ),
						'icon'        => 'images/campaign-builder/settings/calendar_month.png',
					)
				);

				$tags_to_load = array();

				$taxonomies = get_terms(
					array(
						'taxonomy'   => 'campaign_tag',
						'hide_empty' => false,
					)
				);

				if ( ! empty( $taxonomies ) ) :
					foreach ( $taxonomies as $category ) {
						$tags_to_load[ $category->term_id ] = esc_html( $category->name );
					}
				endif;

				echo $charitable_builder_form_fields->generate_tag_selector( // phpcs:ignore
					$settings->campaign_data_settings( 'tags', 'general' ),
					esc_html__( 'Tags', 'charitable' ),
					array(
						'id'      => 'campaign_tags',
						'name'    => array( 'settings', esc_attr( $this->slug ), 'tags' ),
						'options' => $tags_to_load, // phpcs:ignore
						// translators: %s: URL to edit tags.
						'tooltip' => wp_kses(
							sprintf(
								/* translators: %s is the URL to the Charitable settings campaign tags page. */
								__( '<a href="%s" target="_blank">Visit the Tags page in Charitable\'s tool menu</a> to create, edit, and remove tags.', 'charitable' ),
								esc_url( admin_url( 'edit-tags.php?taxonomy=campaign_tag&post_type=campaign' ) )
							),
							[
								'a' => [
									'href'   => [],
									'target' => [],
								],
							]
						),
						'default' => false,
					)
				);

				echo $charitable_builder_form_fields->generate_categories( // phpcs:ignore
					$settings->campaign_data_settings( 'categories', 'general' ),
					'Categories',
					array(
						'id'      => 'campaign_categories',
						'name'    => array( 'settings', esc_attr( $this->slug ), 'categories' ),
						'class'   => 'campaign-tag-categories',
						'tooltip' => wp_kses(
							sprintf(
								/* translators: %s is the URL to the Charitable settings campaign categories page. */
								__( '<a href="%s" target="_blank">Visit the Categories page in Charitable\'s tool menu</a> to create, edit, and remove categories.', 'charitable' ),
								esc_url( admin_url( 'edit-tags.php?taxonomy=campaign_category&post_type=campaign' ) )
							),
							[
								'a' => [
									'href'   => [],
									'target' => [],
								],
							]
						),
					)
				);

				$users    = apply_filters( 'charitable_campaign_builder_settings_general_users', charitable_get_users_as_campaign_creators( 'permissions', get_current_user_id() ) );
				$campaign = isset( $campaign_id ) && 0 !== $campaign_id ? get_post( $campaign_id ) : false;

				if ( ! empty( $users ) ) {

					foreach ( $users as $user ) {
						$users_to_pass[ $user->data->ID ] = array(
							'avatar' => esc_url( get_avatar_url( $user->data->ID ) ),
							'text'   => ( '' . charitable_get_creator_data( $user->data->ID ) ),
							'meta'   => '&nbsp;( ID: ' . $user->data->ID . ' )&nbsp;Joined: ' . date( 'M d, Y ', strtotime( $user->data->user_registered ) ) . ' ', // phpcs:ignore
						);
					}

					$campaign_creator_id = ( $campaign_id && ! empty( $campaign->post_author ) ) ? intval( $campaign->post_author ) : get_current_user_id();

					echo $charitable_builder_form_fields->generate_user_dropdown( // phpcs:ignore
						$settings->campaign_data_settings( 'campaign_creator_id', 'campaign-creator' ),
						esc_html__( 'Campaign Creator', 'charitable' ),
						array(
							'id'             => 'campaign_campaign_creator_id',
							'name'           => array( 'settings', esc_attr( $this->slug ), 'campaign_creator_id' ),
							'class'          => 'campaign-builder-campaign-creator-id',
							'options'        => $users_to_pass, // phpcs:ignore
							'html'           => true,
							'default'        => get_current_user_id(), // phpcs:ignore
							'selected_value' => intval( $campaign_creator_id ),
							'tooltip'        => wp_kses(
								sprintf(
									/* translators: %s is the URL to the WordPress users page. */
									__( 'Campaign creators can be any registered <a href="%s" target="_blank">WordPress user</a> with create or edit campaign permissions.', 'charitable' ),
									esc_url( admin_url( 'users.php' ) )
								),
								[
									'a' => [
										'href'   => [],
										'target' => [],
									],
								]
							),
						)
					);

				}

					do_action( 'charitable_campaign_builder_after_settings_general', $settings, $this->slug );

				?>

				<div class="charitable-panel-fields-group" data-group="settings_advanced"> <?php /* class used to have unfoldable */ ?>
					<div class="charitable-panel-fields-group-border-top"></div>
					<div class="charitable-panel-fields-group-title"><?php echo esc_html__( 'Advanced', 'charitable' ); ?> <?php /* <i class="fa fa-chevron-circle-right"></i> */ ?>
					</div>
					<div class="charitable-panel-fields-group-inner" <?php /* style="display: none;" */ ?>>

						<?php

							echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
								$settings->campaign_data_settings( 'form_css_class', 'general' ),
								esc_html__( 'Form CSS Class', 'charitable' ),
								array(
									'id'      => 'campaign_builder_css_class',
									'name'    => array( 'settings', esc_attr( $this->slug ), 'form_css_class' ),
									'tooltip' => esc_html__( 'Assign a CSS class to the campaign container.', 'charitable' ),
								)
							);

						?>

					</div>
				</div>

			</div>

			<?php

			$html = ob_get_clean();

			echo $html; // phpcs:ignore
		}
	}

	new Charitable_Builder_Panel_Settings_General();

endif;