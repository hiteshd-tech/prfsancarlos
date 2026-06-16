<?php
/**
 * Charitable Tools Settings UI.
 *
 * @package   Charitable/Classes/Charitable_Tools_Settings
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.7.0.7
 * @version   1.8.1.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools_Settings' ) ) :

	/**
	 * Charitable_Tools_Settings
	 *
	 * @since 1.8.1.6
	 */
	final class Charitable_Tools_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @since  1.8.1.6
		 *
		 * @var    Charitable_Tools_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.8.1.6
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.8.1.6
		 *
		 * @return  Charitable_Tools_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the tools tab settings fields.
		 *
		 * @since      1.8.1.6
		 * @deprecated 1.8.10 Use add_tools_import_charitable_fields() instead.
		 *
		 * @return  array<string,array>
		 */
		public function add_tools_import_fields() {
			return $this->add_tools_import_charitable_fields();
		}

		/**
		 * Add the Charitable sub-tab import fields (Campaign Import + Campaign Donations).
		 *
		 * @since 1.8.10
		 *
		 * @return array<string,array>
		 */
		public function add_tools_import_charitable_fields() {

			if ( ! charitable_is_tools_view( 'import' ) || ! charitable_is_tools_view( 'import__charitable' ) ) {
				return array();
			}

			return array(
				'section'              => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 1000,
					'value'    => 'import',
				),
				'charitable_heading'   => array(
					'title'    => __( 'Charitable', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'charitable-tools-heading-pro',
					'help'     => __( 'Import campaigns and donations from Charitable JSON export files.', 'charitable' ),
					'priority' => 15,
				),
				'import_campaign'  => array(
					'label_for'         => __( 'Campaign Import', 'charitable' ),
					'type'              => 'file',
					'wrapper_class'     => 'test',
					'nonce_action_name' => 'import_campaign',
					'nonce_field_name'  => 'charitable_nonce',
					'name'              => 'campaign',
					'action'            => 'tools-campaign',
					'button_label'      => esc_html__( 'Import Campaign', 'charitable' ),
					'help'              => sprintf(
						/* translators: %1$s: HTML strong tag. %2$s: HTML closing strong tag. %3$s: HTML break tag. */
						esc_html__( 'Imports a campaign JSON file into this website. It will not overwrite any previous campaigns, and it will set to "inactive" with a new campaign ID.%3$s %1$sCampaign tools do not include donations or campaign creators%2$s.', 'charitable' ),
						'<strong>',
						'</strong>',
						'<br />'
					),
					'priority'          => 25,
					'default'           => 2,
				),
				'import_donations' => array(
					'label_for'         => __( 'Campaign Donations', 'charitable' ),
					'type'              => 'file',
					'wrapper_class'     => 'test',
					'nonce_action_name' => 'import_donations',
					'nonce_field_name'  => 'charitable_nonce',
					'name'              => 'donations',
					'action'            => 'tools-donation',
					'button_label'      => esc_html__( 'Import Donations', 'charitable' ),
					'help'              => sprintf(
						/* translators: %1$s: HTML strong tag. %2$s: HTML closing strong tag. %3$s: HTML break tag. */
						esc_html__( 'Imports a donation JSON file into the selected campaign. It will not overwrite any previous donations or prevent duplicates.%3$s %1$sDonation tools do not include users - if a donor\'s email address is not found, the donation will not be assigned to a user%2$s.', 'charitable' ),
						'<strong>',
						'</strong>',
						'<br />'
					),
					'priority'          => 25,
					'default'           => 2,
					'options'           => $this->get_campaign_list( true ),
					'select_name'       => 'tools_campaign',
				),
			);
		}

		/**
		 * Add the GiveWP sub-tab import fields (CSV Donations Import + Migration Tool).
		 *
		 * @since 1.8.10
		 *
		 * @return array<string,array>
		 */
		public function add_tools_import_givewp_fields() {

			if ( ! charitable_is_tools_view( 'import' ) || ! charitable_is_tools_view( 'import__givewp' ) ) {
				return array();
			}

			return array(
				'section'                     => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 1000,
					'value'    => 'import',
				),
				'givewp_heading'              => array(
					'title'    => __( 'GiveWP', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'charitable-tools-heading-pro',
					'help'     => __( 'Manually import donations from GiveWP.', 'charitable' ),
					'priority' => 15,
				),
				'import_donations_givewp_csv' => array(
					'label_for'         => __( 'GiveWP CSV Donations Import', 'charitable' ),
					'type'              => 'file',
					'wrapper_class'     => 'test',
					'nonce_action_name' => 'import_donations_givewp',
					'nonce_field_name'  => 'charitable_nonce',
					'name'              => 'donations_givewp',
					'action'            => 'tools-givewp-donations',
					'button_label'      => esc_html__( 'Import GiveWP Donations', 'charitable' ),
					'help'              => esc_html__( 'Import donations from a GiveWP CSV export file. Donors will be created or matched by email address during the import process.', 'charitable' ),
					'accept'            => '.csv',
					'priority'          => 25,
					'default'           => 2,
				),
				'givewp_migration_heading'    => array(
					'title'    => __( 'GiveWP Migration Tool', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'charitable-tools-heading-pro',
					'help'     => __( 'Import data directly from the GiveWP database.', 'charitable' ),
					'priority' => 45,
				),
				'givewp_migration_tool'       => array(
					'title'     => __( 'GiveWP Migration', 'charitable' ) . ' <span class="badge beta">' . __( 'BETA', 'charitable' ) . '</span>',
					'label_for' => 'charitable-givewp-migrate-start',
					'type'      => 'content',
					'render'    => true,
					'callback'  => array( $this, 'render_givewp_migration_tool' ),
					'priority'  => 50,
					'class'     => 'charitable-givewp-migration-row',
				),
			);
		}

		/**
		 * Render the GiveWP Pro upsell notice.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function render_givewp_pro_notice() {
			echo '<div class="charitable-import-pro-notice">';
			echo '<p>';
			printf(
				/* translators: %1$s: opening anchor tag. %2$s: closing anchor tag. */
				esc_html__( '%1$sUpgrade to Charitable Pro%2$s for full GiveWP migration capabilities including recurring donations, standalone donor imports, rollback support, and import history.', 'charitable' ),
				'<a href="' . esc_url( 'https://wpcharitable.com/lite-upgrade/' ) . '" target="_blank"><strong>',
				'</strong></a>'
			);
			echo '</p>';
			echo '</div>';
		}

		/**
		 * Render the GiveWP Migration Tool UI.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function render_givewp_migration_tool() {
			$importer  = Charitable_GiveWP_Importer::get_instance();
			$is_active = $importer->is_givewp_active();

			echo '<div class="charitable-givewp-migration-wrap">';

			if ( ! $is_active ) {
				echo '<div class="migration-info-box">';
				echo '<p>' . esc_html__( 'GiveWP is not active on this site. Please install and activate GiveWP to use the migration tool.', 'charitable' ) . '</p>';
				echo '</div>';
				echo '</div>';
				return;
			}

			$counts = $importer->get_counts();

			// Pro upsell notice.
			$this->render_givewp_pro_notice();

			// Before You Begin box.
			echo '<div class="migration-info-box charitable-before-import">';
			echo '<p class="info-box-heading"><strong>' . esc_html__( 'Before You Begin:', 'charitable' ) . '</strong></p>';
			echo '<ul>';
			echo '<li>' . esc_html__( 'Back up your database before importing.', 'charitable' ) . '</li>';
			echo '<li>' . esc_html__( 'Imported campaigns will be set to draft status for your review.', 'charitable' ) . '</li>';
			echo '<li>' . esc_html__( 'Duplicate items (previously imported) will be automatically skipped.', 'charitable' ) . '</li>';
			echo '<li>' . esc_html__( 'Keep GiveWP activated until you verify the import completed correctly.', 'charitable' ) . '</li>';
			echo '</ul>';
			echo '</div>';

			// GiveWP Data Found box.
			echo '<div class="migration-info-box">';
			echo '<p class="info-box-heading"><strong>' . esc_html__( 'GiveWP Data Found:', 'charitable' ) . '</strong></p>';
			echo '<table class="migration-data-found">';
			echo '<tr><td class="data-count">' . intval( $counts['campaigns'] ) . '</td><td>' . esc_html__( 'Campaigns (Forms)', 'charitable' ) . '</td></tr>';
			echo '<tr><td class="data-count">' . intval( $counts['donations'] ) . '</td><td>' . esc_html__( 'Donations', 'charitable' ) . '</td></tr>';
			echo '</table>';
			echo '</div>';

			// Select What to Import.
			echo '<div class="migration-select-section">';
			echo '<p class="select-heading"><strong>' . esc_html__( 'Select What to Import:', 'charitable' ) . '</strong></p>';
			echo '<div class="migration-options">';
			echo '<label><input type="checkbox" name="givewp_import_campaigns" value="1" checked="checked" /> ';
			/* translators: %d: number of GiveWP forms found. */
			echo sprintf( esc_html__( 'Campaigns (%d GiveWP forms)', 'charitable' ), intval( $counts['campaigns'] ) );
			echo '</label>';
			echo '<label><input type="checkbox" name="givewp_import_donations" value="1" checked="checked" /> ';
			/* translators: %d: number of GiveWP donations found. */
			echo sprintf( esc_html__( 'Donations (%d)', 'charitable' ), intval( $counts['donations'] ) );
			echo '</label>';
			echo '</div>';

			echo '<div class="migration-extra-options">';
			echo '<label><input type="checkbox" name="givewp_dry_run" value="1" /> ';
			echo esc_html__( 'Dry Run (Preview Only)', 'charitable' );
			echo '<span class="option-description"> ' . esc_html__( 'Preview what will be imported without making changes', 'charitable' ) . '</span>';
			echo '</label>';
			echo '</div>';
			echo '</div>';

			// Start button.
			echo '<p class="migration-button-wrap">';
			echo '<button type="button" id="charitable-givewp-migrate-start" class="charitable-migration-btn">';
			echo '<span class="dashicons dashicons-migrate"></span> ';
			echo esc_html__( 'Start GiveWP Import', 'charitable' );
			echo '</button>';
			echo '</p>';

			// Progress.
			echo '<div class="migration-progress">';
			echo '<div class="progress-bar-outer"><div class="progress-bar-inner"></div></div>';
			echo '<div class="progress-status"></div>';
			echo '</div>';

			// Results.
			echo '<div class="migration-results">';
			echo '<h4>' . esc_html__( 'Migration Results', 'charitable' ) . '</h4>';
			echo '<table>';
			echo '<thead><tr><th>' . esc_html__( 'Type', 'charitable' ) . '</th><th>' . esc_html__( 'Imported', 'charitable' ) . '</th><th>' . esc_html__( 'Skipped', 'charitable' ) . '</th><th>' . esc_html__( 'Errors', 'charitable' ) . '</th></tr></thead>';
			echo '<tbody></tbody>';
			echo '</table>';
			echo '</div>';

			// Dry-run results.
			echo '<div class="migration-dry-run-results" style="display:none;">';
			echo '<h4>' . esc_html__( 'Dry Run Results', 'charitable' ) . '</h4>';
			echo '<table>';
			echo '<thead><tr><th>' . esc_html__( 'Type', 'charitable' ) . '</th><th>' . esc_html__( 'Would Import', 'charitable' ) . '</th><th>' . esc_html__( 'Would Skip', 'charitable' ) . '</th><th>' . esc_html__( 'Potential Issues', 'charitable' ) . '</th></tr></thead>';
			echo '<tbody></tbody>';
			echo '</table>';
			echo '</div>';

			// Error.
			echo '<div class="migration-error"></div>';

			echo '</div>';

			wp_nonce_field( 'charitable_givewp_migration', 'charitable_givewp_migration_nonce' );
		}

		/**
		 * Add the GiveButter sub-tab import fields (CSV Donations Import only).
		 *
		 * @since 1.8.10
		 *
		 * @return array<string,array>
		 */
		public function add_tools_import_givebutter_fields() {

			if ( ! charitable_is_tools_view( 'import' ) || ! charitable_is_tools_view( 'import__givebutter' ) ) {
				return array();
			}

			return array(
				'section'                         => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 1000,
					'value'    => 'import',
				),
				'givebutter_heading'              => array(
					'title'    => __( 'GiveButter', 'charitable' ),
					'type'     => 'heading',
					'class'    => 'charitable-tools-heading-pro',
					'help'     => __( 'Manually import donations from GiveButter.', 'charitable' ),
					'priority' => 15,
				),
				'import_donations_givebutter_csv' => array(
					'label_for'         => __( 'GiveButter CSV Donations Import', 'charitable' ),
					'type'              => 'file',
					'wrapper_class'     => 'test',
					'nonce_action_name' => 'import_donations_givebutter',
					'nonce_field_name'  => 'charitable_nonce',
					'name'              => 'donations_givebutter',
					'action'            => 'tools-givebutter-donations',
					'button_label'      => esc_html__( 'Import GiveButter Donations', 'charitable' ),
					'help'              => esc_html__( 'Import donations from a GiveButter CSV export file. Donors will be created or matched by email address during the import process.', 'charitable' ),
					'accept'            => '.csv',
					'priority'          => 25,
					'default'           => 2,
				),
			);
		}

		/**
		 * Add the tools tab settings fields.
		 *
		 * @since   1.8.1.6
		 *
		 * @return  array<string,array>
		 */
		public function add_tools_export_fields() {

			if ( ! charitable_is_tools_view( 'export' ) ) {
				return array();
			}

			return array(
				'section'          => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 2000,
					'value'    => 'export',
				),
				'export_campaign'  => array(
					'label_for'         => __( 'Campaign Export', 'charitable' ),
					'type'              => 'select-form',
					'wrapper_class'     => 'test',
					'nonce_action_name' => 'export_campaign',
					'nonce_field_name'  => 'charitable_nonce',
					'name'              => 'campaign',
					'action'            => 'export-campaign',
					'button_label'      => __( 'Export Campaign', 'charitable' ),
					'help'              => sprintf(
						/* translators: %1$s: HTML strong tag. %2$s: HTML closing strong tag. %1$s: HTML break tag. */
						__( 'Campaign exports files can be used to create a backup of your campaigns or to import campaigns into another site with Charitable installed.%3$s %1$sCampaign exports do not include donations or campaign creators%2$s.', 'charitable' ),
						'<strong>',
						'</strong>',
						'<br />'
					),
					'priority'          => 225,
					'default'           => 2,
					'options'           => $this->get_campaigns_to_export(),
				),
				'export_donations' => array(
					'label_for'         => __( 'Donations Export', 'charitable' ),
					'type'              => 'select-form',
					'wrapper_class'     => 'test',
					'nonce_action_name' => 'export_donations_from_campaign',
					'nonce_field_name'  => 'charitable_nonce',
					'name'              => 'donations',
					'action'            => 'export-donations-from-campaign',
					'button_label'      => __( 'Export Donations', 'charitable' ),
					'help'              => sprintf(
						/* translators: %1$s: HTML strong tag. %2$s: HTML closing strong tag. %1$s: HTML break tag. */
						__( 'Donation exports files can be used to create a backup of your donations or to import donations if you are migrating campaigns to another site with Charitable installed. %3$s All donation types (paid, refunded, pending) will be exported. %1$sRecurring donations are not included%2$s.', 'charitable' ),
						'<strong>',
						'</strong>',
						'<br />'
					),
					'priority'          => 227,
					'default'           => 2,
					'options'           => $this->get_campaigns_to_export( true ),
				),
			);
		}

		/**
		 * Placeholder function.
		 *
		 * @since 1.8.1.6
		 */
		public function add_tools_snippets_fields() {

			return array();
		}

		/**
		 * Placeholder function.
		 *
		 * @since 1.8.2
		 */
		public function add_tools_customize_fields() {

			return array();
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.8.1.6
		 *
		 * @return string[]
		 */
		protected function get_user_donation_field_options() {
			$fields = charitable()->donation_fields()->get_data_type_fields( 'user' );

			return array_combine(
				array_keys( $fields ),
				wp_list_pluck( $fields, 'label' )
			);
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.7.0.7
		 *
		 * @param  boolean $include_donations Whether to include the number of donations in the campaign name.
		 *
		 * @return string[]
		 */
		protected function get_campaign_list( $include_donations = false ) {

			$campaigns = array();

			$args = array(
				'posts_per_page' => -1,
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'fields'         => array( 'post_title', 'ID' ),
			);

			$results = Charitable_Campaigns::query( $args );

			if ( empty( $results->posts ) ) {
				return array();
			}

			foreach ( $results->posts as $index => $post ) :
				$campaigns[ $post->ID ] = $post->post_title;
				if ( $include_donations ) {
					$donations              = charitable_get_table( 'campaign_donations' )->get_donations_on_campaign( $post->ID );
					$donation_count         = count( $donations );
					$plural                 = ( ( $donation_count > 1 ) || 0 === $donation_count ) ? 's' : '';
					$campaigns[ $post->ID ] = $post->post_title . ' (' . count( $donations ) . ' donation' . $plural . ') ';
				}
			endforeach;

			return( $campaigns );
		}

		/**
		 * Return the list of user donation field options.
		 *
		 * @since  1.7.0.7
		 *
		 * @param  boolean $include_donations Whether to include the number of donations in the campaign name.
		 *
		 * @return string[]
		 */
		protected function get_campaigns_to_export( $include_donations = false ) {

			$campaigns = array();

			$args = array(
				'posts_per_page' => -1,
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'fields'         => array( 'post_title', 'ID' ),
			);

			$results = Charitable_Campaigns::query( $args );

			if ( empty( $results->posts ) ) {
				return array();
			}

			foreach ( $results->posts as $index => $post ) :
				$campaigns[ $post->ID ] = $post->post_title;
				if ( $include_donations ) {
					$donations              = charitable_get_table( 'campaign_donations' )->get_donations_on_campaign( $post->ID );
					$donation_count         = count( $donations );
					$plural                 = ( ( $donation_count > 1 ) || 0 === $donation_count ) ? 's' : '';
					$campaigns[ $post->ID ] = $post->post_title . ' (' . count( $donations ) . ' donation' . $plural . ') ';
				}
			endforeach;

			return( $campaigns );
		}
	}

endif;
