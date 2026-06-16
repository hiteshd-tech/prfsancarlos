<?php
/**
 * Sets up the campaign list table in the admin.
 *
 * @package   Charitable/Classes/Charitable_Campaign_List_Table
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.8.1.5, 1.8.9.1, 1.8.10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_List_Table' ) ) :

	/**
	 * Charitable_Campaign_List_Table class.
	 *
	 * @since 1.5.0
	 */
	final class Charitable_Campaign_List_Table {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Campaign_List_Table|null
		 */
		private static $instance = null;

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $list_table_type = 'campaign';

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Campaign_List_Table
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Updates body class for admin.
		 *
		 * @since  1.8.0
		 *
		 * @param  string $classes The current body classes.
		 *
		 * @return string
		 */
		public function admin_body_class( $classes ) {

			global $pagenow;

			if ( in_array( $pagenow, array( 'edit.php' ), true ) && ! empty( $_GET['post_type'] ) && $_GET['post_type'] === 'campaign' ) { // phpcs:ignore.
				$count_posts = ( wp_count_posts( 'campaign' )->draft + wp_count_posts( 'campaign' )->publish + wp_count_posts( 'campaign' )->future + wp_count_posts( 'campaign' )->pending + wp_count_posts( 'campaign' )->private );
				$classes    .= ' charitable-campaigns charitable-campaigns-' . intval( $count_posts ) . '-published'; // this should be at start: charitable-campaigns charitable-campaigns-0-published.
			}

			if ( in_array( $pagenow, array( 'edit.php' ), true ) && ! empty( $_GET['post_status'] ) && $_GET['post_status'] === 'trash' ) { // phpcs:ignore.
				$count_posts = ( wp_count_posts( 'campaign' )->trash );
				$classes    .= ' charitable-trash charitable-campaigns-' . intval( $count_posts ) . '-trash';
			}

			return $classes;
		}

		/**
		 * Customize campaigns columns.
		 *
		 * @see    get_column_headers
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function dashboard_columns() {
			/**
			 * Filter the columns shown in the campaigns list table.
			 *
			 * @since 1.5.0
			 *
			 * @param array $columns The list of columns.
			 */
			return apply_filters(
				'charitable_campaign_dashboard_column_names',
				array(
					'cb'           => '<input type="checkbox"/>',
					'title'        => __( 'Title', 'charitable' ),
					'creator'      => __( 'Creator', 'charitable' ),
					'donated'      => __( 'Donations', 'charitable' ),
					'status'       => __( 'Status', 'charitable' ),
					'end_date'     => __( 'End Date', 'charitable' ),
					'date_created' => __( 'Date Created', 'charitable' ),
					'actions'      => __( 'Actions', 'charitable' ),
				)
			);
		}

		/**
		 * Add information to the dashboard campaign table listing.
		 *
		 * @see    WP_Posts_List_Table::single_row()
		 *
		 * @since  1.5.0
		 *
		 * @param  string $column_name The name of the column to display.
		 * @param  int    $post_id     The current post ID.
		 * @return void
		 */
		public function dashboard_column_item( $column_name, $post_id ) {
			$campaign = charitable_get_campaign( $post_id );
			$display  = '';

			switch ( $column_name ) {
				case 'ID':
					$display = $post_id;
					break;

				case 'creator':
					$creator_id   = $campaign->get_campaign_creator();
					$creator_name = $campaign->get_campaign_creator_name();

					if ( intval( $creator_id ) > 0 ) {
						$display .= '<a href="' . get_edit_user_link( $creator_id ) . '" >';
						$display .= '<img class="charitable-campaign-creator-avatar" alt="' . $creator_name . '" title="' . $creator_name . '" src="' . esc_url( get_avatar_url( $creator_id ) ) . '" />';
					}
					$display .= '<span class="charitable-campaign-creator-name">' . $creator_name . '</span>';
					if ( intval( $creator_id ) > 0 ) {
						$display .= '</a>';
					}
					break;

				case 'donated':
					$display = charitable_format_money( $campaign->get_donated_amount() );
					$percent = absint( $campaign->get_percent_donated() );
					if ( $percent > 100 ) {
						$percent = 100;
					}
					if ( ! $campaign->is_endless() ) {
						$display .= '<div class="meter">
										<span style="width: ' . $percent . '%"></span>
									</div>';
					}
					break;

				case 'date_created':
					$date_format = get_option( 'date_format' );
					$date_format = ( '' === trim( $date_format ) ) ? 'd/m/Y' : $date_format;
					$display     = date( $date_format, strtotime( $campaign->post_date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					break;

				case 'end_date':
					$display = $campaign->is_endless() ? '&#8734;' : $campaign->get_end_date();
					break;

				case 'actions':
					// determine if we share the preview or the actual live URL.
					$status = get_post_status( $post_id );

					if ( 'trash' === $status ) {
						break;
					}

					$link    = ( 'publish' === $status || 'private' === $status ) ? get_permalink( $post_id ) : charitable_get_campaign_preview_url( $post_id, true, $status );
					$display = '<a class="charitable-campaign-action-button" title="' . esc_html__( 'Preview', 'charitable' ) . '" href="' . esc_url( $link ) . '" target="_blank"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/eye.svg" width="14" height="14" alt="' . esc_html__( 'Preview', 'charitable' ) . '" /></a><a class="charitable-campaign-action-button" title="' . esc_html__( 'Trash', 'charitable' ) . '" href="' . get_delete_post_link( $post_id ) . '"><img src="' . charitable()->get_path( 'assets', false ) . '/images/icons/trash.svg" width="14" height="14" alt="' . esc_html__( 'Trash', 'charitable' ) . '" /></a>';

					// <button><i class="fa fa-envelope"></i></button>

					break;

				case 'status':
					$status = $campaign->get_status();

					if ( 'finished' === $status && $campaign->has_goal() ) {
						$status = $campaign->has_achieved_goal() ? 'successful' : 'unsuccessful';
					}

					$display = '<mark class="' . esc_attr( $status ) . '">' . $status . '</mark>';
					break;

				default:
					$display = '';
			}

			/**
			 * Filter the output of the cell.
			 *
			 * @since 1.5.0
			 *
			 * @param string              $display     The content that will be displayed.
			 * @param string              $column_name The name of the column.
			 * @param int                 $post_id     The ID of the campaign being shown.
			 * @param Charitable_Campaign $campaign    The Charitable_Campaign object.
			 */
			echo apply_filters( 'charitable_campaign_column_display', $display, $column_name, $post_id, $campaign ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Modify bulk messages
		 *
		 * @since  1.5.0
		 *
		 * @param  array $bulk_messages Messages to show after bulk actions.
		 * @param  array $bulk_counts   Array showing how many items were affected by the action.
		 * @return array
		 */
		public function bulk_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ Charitable::CAMPAIGN_POST_TYPE ] = array(
				/* translators: %s: number of items */
				'updated'   => _n( '%d campaign updated.', '%d campaigns updated.', $bulk_counts['updated'], 'charitable' ),
				'locked'    => ( 1 == $bulk_counts['locked'] )
								? __( '1 campaign not updated, somebody is editing it.', 'charitable' )
								/* translators: %s: number of items */
								: _n( '%s campaign not updated, somebody is editing it.', '%s campaigns not updated, somebody is editing them.', $bulk_counts['locked'], 'charitable' ),
				/* translators: %s: number of items */
				'deleted'   => _n( '%s campaign permanently deleted.', '%s campaigns permanently deleted.', $bulk_counts['deleted'], 'charitable' ),
				/* translators: %s: number of items */
				'trashed'   => _n( '%s campaign moved to the Trash.', '%s campaigns moved to the Trash.', $bulk_counts['trashed'], 'charitable' ),
				/* translators: %s: number of items */
				'untrashed' => _n( '%s campaign restored from the Trash.', '%s campaigns restored from the Trash.', $bulk_counts['untrashed'], 'charitable' ),
			);

			return $bulk_messages;
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.6.0
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_export( $which ) {
			if ( ! current_user_can( 'export_charitable_reports' ) ) {
				return;
			}

			if ( 'top' === $which && $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/export' );
			}
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.6.0
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_legacy_link( $which ) {
			if ( ! current_user_can( 'publish_posts' ) ) {
				return;
			}

			if ( 'top' === $which && $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/legacy-add' );
			}
		}


		/**
		 * Add filters above the campaigns table.
		 *
		 * @since  1.6.36
		 *
		 * @global string $typenow The current post type.
		 *
		 * @return void
		 */
		public function add_filters() {
			global $typenow;

			/* Show custom filters to filter orders by donor. */
			if ( in_array( $typenow, array( Charitable::CAMPAIGN_POST_TYPE ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTypeDeclaration
				charitable_admin_view( 'campaigns-page/filters' );
			}
		}

		/**
		 * Add modal template to footer.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function modal_forms() {
			if ( $this->is_campaigns_page() ) {
				charitable_admin_view( 'campaigns-page/filter-form' );
				charitable_admin_view( 'campaigns-page/export-form' );
			}
		}

		/**
		 * Admin scripts and styles.
		 *
		 * Set up the scripts & styles used for the modal.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $hook The current page hook/slug.
		 * @return void
		 */
		public function load_scripts( $hook ) {
			if ( 'edit.php' != $hook ) {
				return;
			}

			if ( $this->is_campaigns_page() ) {
				wp_enqueue_style( 'lean-modal-css' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'lean-modal' );
				wp_enqueue_script( 'charitable-admin-tables' );
			}
		}

		/**
		 * Add custom filters to the query that returns the campaigns to be displayed.
		 *
		 * @since  1.6.36
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  array $vars The array of args to pass to WP_Query.
		 * @return array
		 */
		public function filter_request_query( $vars ) {
			if ( ! $this->is_campaigns_page() ) {
				return $vars;
			}

			/* No Status: fix WP's crappy handling of "all" post status. */
			if ( ! isset( $_GET['post_status'] ) || empty( $_GET['post_status'] ) || 'all' === $_GET['post_status'] ) {
				$vars['post_status'] = array_keys( get_post_statuses() );
				$vars['perm']        = 'readable';
			} else {
				switch ( $_GET['post_status'] ) {
					case 'active':
						$vars['post_status'] = 'publish';
						$vars['meta_query']  = array(
							'relation' => 'OR',
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
								'compare' => '>',
								'type'    => 'datetime',
							),
							array(
								'key'     => '_campaign_end_date',
								'value'   => 0,
								'compare' => '=',
							),
						);
						break;

					case 'finish':
						$vars['post_status'] = 'publish';
						$vars['meta_query']  = array(
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
								'compare' => '<=',
								'type'    => 'datetime',
							),
						);
						break;

					default:
						$vars['post_status'] = esc_attr( $_GET['post_status'] ); // phpcs:ignore
						$vars['perm']        = 'readable';
				}
			}

			if ( ! empty( $_GET['charitable_nonce'] ) &&
				wp_verify_nonce( esc_html( $_GET['charitable_nonce'] ), 'charitable_filter_campaigns' ) ) :  // phpcs:ignore

				/* Set up start date query */
				if ( isset( $_GET['start_date_from'] ) && ! empty( $_GET['start_date_from'] ) ) {
					$start_date_from             = $this->get_parsed_date( esc_html( $_GET['start_date_from'] ) );  // phpcs:ignore
					$vars['date_query']['after'] = array(
						'year'  => $start_date_from['year'],
						'month' => $start_date_from['month'],
						'day'   => $start_date_from['day'],
					);
				}

				if ( isset( $_GET['start_date_to'] ) && ! empty( $_GET['start_date_to'] ) ) {
					$start_date_to                = $this->get_parsed_date( esc_html( $_GET['start_date_to'] ) );  // phpcs:ignore
					$vars['date_query']['before'] = array(
						'year'  => $start_date_to['year'],
						'month' => $start_date_to['month'],
						'day'   => $start_date_to['day'],
					);
				}

				/* Set up end date query */
				if ( isset( $_GET['end_date_from'] ) && ! empty( $_GET['end_date_from'] ) ) {
					$end_date_from        = $this->get_parsed_date( esc_html( $_GET['end_date_from'] ) ); // phpcs:ignore
					$vars['meta_query'][] = array(
						'key'     => '_campaign_end_date',
						'value'   => sprintf( '%d-%d-%d 00:00:00', $end_date_from['year'], $end_date_from['month'], $end_date_from['day'] ),
						'compare' => '>=',
						'type'    => 'datetime',
					);
				}

				if ( isset( $_GET['end_date_to'] ) && ! empty( $_GET['end_date_to'] ) ) {
					$end_date_to          = $this->get_parsed_date( esc_html( $_GET['end_date_to'] ) );  // phpcs:ignore
					$vars['meta_query'][] = array(
						'key'     => '_campaign_end_date',
						'value'   => sprintf( '%d-%d-%d 00:00:00', $end_date_to['year'], $end_date_to['month'], $end_date_to['day'] ),
						'compare' => '<=',
						'type'    => 'datetime',
					);
				}

				if ( isset( $vars['date_query'] ) ) {
					$vars['date_query']['inclusive'] = true;
				}

				/* Filter by campaign. */
				if ( isset( $_GET['campaign_id'] ) && false !== stripos( esc_html( $_GET['charitable_nonce'] ), 'all' ) ) {  // phpcs:ignore
					$vars['post__in'] = charitable_get_table( 'campaign_donations' )->get_donation_ids_for_campaign( intval( $_GET['campaign_id'] ) );
				}

			endif;

			/* Restrict by author if user can only edit their own. */
			if ( ! current_user_can( 'edit_others_campaigns' ) ) {
				$vars['author'] = get_current_user_id();
			}

			/**
			 * Filter the campaign list query vars.
			 *
			 * @since 1.6.36
			 *
			 * @param array $vars The request query vars.
			 */
			return apply_filters( 'charitable_filter_campaigns_list_request_vars', $vars );
		}

		/**
		 * Given a date, returns an array containing the date, month and year.
		 * Updated in 1.7.0.8 to reflect a change in date format, but points to a new date format function in case someone else is using the old one.
		 *
		 * @since  1.6.36
		 *
		 * @param  string $date A date as a string that can be parsed by strtotime.
		 * @return string[]
		 */
		protected function get_parsed_date( $date ) {
			$time = charitable_sanitize_date_alt_format( $date );

			$args = array(
				'year'  => date( 'Y', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'month' => date( 'm', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'day'   => date( 'd', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			);

			return $args;
		}

		/**
		 * Checks whether this is the campaigns page.
		 *
		 * @since  1.6.0
		 *
		 * @global string $typenow The current post type.
		 * @global string $pagenow The current admin page.
		 *
		 * @return boolean
		 */
		private function is_campaigns_page() {
			global $typenow, $pagenow;

			return 'edit.php' === $pagenow && in_array( $typenow, array( Charitable::CAMPAIGN_POST_TYPE ), true );
		}

	/**
	 * Show blank slate.
	 *
	 * @since   1.8.1.5
	 * @version 1.8.8.6, 1.8.9.1, 1.8.10
	 *
	 * @param string $which String which tablenav is being shown.
	 *
	 * @return void
	 */
	public function maybe_render_blank_state( $which = '' ) {
		global $post_type, $typenow;

		// Static flag to prevent double rendering
		static $blank_state_rendered = false;

		// Use $typenow as fallback if $post_type is not set
		$current_post_type = $post_type ? $post_type : $typenow;

		// Only proceed if we're on the correct post type
		if ( $current_post_type !== $this->list_table_type ) {
			return;
		}

		// Check post count
		$counts = (array) wp_count_posts( $current_post_type );
		unset( $counts['auto-draft'] );
		$count = array_sum( $counts );

		// Skip if there are posts or blank slate is disabled
		if ( ! defined( 'CHARITABLE_FORCE_BLANK_SLATE' ) || ! CHARITABLE_FORCE_BLANK_SLATE ) {
			if ( 0 < $count || ( defined( 'CHARITABLE_DISABLE_BLANK_SLATE' ) && CHARITABLE_DISABLE_BLANK_SLATE ) ) {
				return;
			}
		}

		// Prefer bottom, but render in top if bottom doesn't fire (when there are 0 posts)
		if ( 'bottom' === $which && ! $blank_state_rendered ) {
			$blank_state_rendered = true;
			$this->enqueue_blank_slate_assets();
			$this->render_blank_state();
			echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub, .wrap .search-box, .wrap .tablenav-pages  { display: none; } #posts-filter .tablenav.bottom { height: auto; } body.post-type-campaign .page-title-action { display: none; } </style>';
		} elseif ( 'top' === $which && ! $blank_state_rendered ) {
			// Fallback: if bottom never fires (WordPress doesn't render it with 0 posts), render in top
			$blank_state_rendered = true;
			$this->enqueue_blank_slate_assets();
			$this->render_blank_state();
			echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top .actions, .wrap .subsubsub, .wrap .search-box, .wrap .tablenav-pages  { display: none; } #posts-filter .tablenav.top { height: auto; } body.post-type-campaign .page-title-action { display: none; } </style>';
		}
	}

		/**
		 * Renders advice in the event that no campaigns exist yet.
		 *
		 * @since   1.8.1.5
		 * @version 1.8.10
		 *
		 * @return void
		 */
		public function render_blank_state() {
			?>
			<div class="charitable-blank-slate">
				<!-- Page Header -->
				<div class="page-header">
					<h1 class="page-title"><?php esc_html_e( 'Campaigns', 'charitable' ); ?></h1>
					<?php if ( charitable_disable_legacy_campaigns() ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Create Campaign', 'charitable' ); ?></a>
					<?php else : ?>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Create Legacy Campaign', 'charitable' ); ?></a>
					<?php endif; ?>
				</div>

				<!-- Primary Welcome Section -->
				<div class="welcome-card">
					<h2 class="welcome-title"><?php esc_html_e( 'Ready to Start Fundraising?', 'charitable' ); ?></h2>
					<p class="welcome-description">
						<?php esc_html_e( 'Create your first campaign to start accepting donations and building your supporter community.', 'charitable' ); ?>
					</p>
					<?php if ( charitable_disable_legacy_campaigns() ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>" class="btn-primary btn-large"><?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?></a>
					<?php else : ?>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>" class="btn-primary btn-large"><?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?></a>
					<?php endif; ?>
				</div>

				<!-- Secondary Actions -->
				<div class="secondary-actions">
					<div class="action-card">
						<h3><?php esc_html_e( 'Already Have Campaigns Elsewhere?', 'charitable' ); ?></h3>
						<?php
						$givewp_installed = $this->is_givewp_installed();
						$givewp_count     = $givewp_installed ? $this->get_givewp_campaign_count() : 0;

						if ( $givewp_installed && $givewp_count > 0 ) :
						?>
						<p class="givewp-detection-text">
							<?php
							printf(
								wp_kses(
									/* translators: 1: bolded "GiveWP", 2: number of campaigns. */
									__( 'We detected %1$s with %2$s campaigns installed on your site. Use our import tool to import them into Charitable.', 'charitable' ),
									[
										'strong' => [],
										'span'   => [ 'class' => [] ],
									]
								),
								'<strong>GiveWP</strong>',
								'<span class="givewp-count">' . absint( $givewp_count ) . '</span>'
							);
							?>
						</p>
						<a href="https://www.wpcharitable.com/documentation/import-export-tool-givewp-to-charitable/" class="btn-secondary" target="_blank"><?php esc_html_e( 'Import GiveWP Campaigns', 'charitable' ); ?></a>
						<?php elseif ( $givewp_installed && 0 === $givewp_count ) : ?>
						<p class="givewp-detection-text">
							<?php
							printf(
								wp_kses(
									/* translators: %s: bolded "GiveWP". */
									__( 'Did you know you can import %s campaigns, donations, and donors right into Charitable?', 'charitable' ),
									[ 'strong' => [] ]
								),
								'<strong>GiveWP</strong>'
							);
							?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=import&sub_tab=givewp' ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Import Tools', 'charitable' ); ?></a>
						<?php else : ?>
						<p><?php esc_html_e( 'Import your existing campaigns from your old fundraising platform to get started quickly with Charitable.', 'charitable' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=import&sub_tab=charitable' ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Import Campaigns', 'charitable' ); ?></a>
						<?php endif; ?>
					</div>

					<div class="blank-slate-feature-card">
						<div class="blank-slate-feature-title-icon-row">
							<div class="blank-slate-feature-icon">
								<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/group.svg' ); ?>" alt="<?php esc_attr_e( 'Peer-to-Peer', 'charitable' ); ?>" width="32" height="32" />
							</div>
							<h3 class="blank-slate-feature-title"><?php esc_html_e( 'Enable Peer-to-Peer', 'charitable' ); ?> <span class="blank-slate-pro-badge"><?php esc_html_e( 'PRO', 'charitable' ); ?></span></h3>
						</div>
						<div class="blank-slate-feature-content">
							<p class="blank-slate-feature-description"><?php esc_html_e( 'Charitable Ambassadors transforms your website into a peer-to-peer fundraising or crowdfunding platform.', 'charitable' ); ?></p>
							<div class="blank-slate-button-row">
							<?php
							$ambassadors_state = $this->get_ambassadors_addon_state();

							if ( 'upgrade' === $ambassadors_state['button_action'] ) {
								$upgrade_url = isset( $ambassadors_state['upgrade_url'] ) ? $ambassadors_state['upgrade_url'] : '#';
								?>
								<a href="<?php echo esc_url( $upgrade_url ); ?>" class="charitable-dashboard-v2-enhance-grid-button <?php echo esc_attr( $ambassadors_state['button_class'] ); ?>" data-action="upgrade" target="_blank"><?php echo esc_html( $ambassadors_state['button_text'] ); ?></a>
								<?php
							} else {
								$button_attributes = [
									'data-action="' . esc_attr( $ambassadors_state['button_action'] ) . '"',
									'data-slug="charitable-ambassadors"',
									'data-type="charitable_addon"',
								];

								if ( $ambassadors_state['button_disabled'] ) {
									$button_attributes[] = 'disabled';
								}
								?>
								<button class="charitable-dashboard-v2-enhance-grid-button <?php echo esc_attr( $ambassadors_state['button_class'] ); ?>" <?php echo implode( ' ', $button_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $ambassadors_state['button_text'] ); ?></button>
								<?php
							}
							?>
							<a href="https://www.wpcharitable.com/features/peer-to-peer-fundraising/" class="charitable-dashboard-v2-enhance-grid-button charitable-dashboard-v2-learn-more-button" target="_blank"><?php esc_html_e( 'Learn More', 'charitable' ); ?></a>
							</div>
						</div>
					</div>
				</div>

				<!-- Help Resources -->
				<div class="help-resources">
					<h3><?php esc_html_e( 'Additional Resources', 'charitable' ); ?></h3>
					<ul class="help-links">
						<li><a href="https://www.wpcharitable.com/documentation/start-here/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=campaign-page&utm_content=Start%20Here%20Guide" target="_blank"><?php esc_html_e( 'Get Started With Charitable', 'charitable' ); ?></a></li>
						<li><a href="https://www.wpcharitable.com/documentation/creating-your-first-campaign/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=campaign-page&utm_content=Campaign%20Guide" target="_blank"><?php esc_html_e( 'Creating Your First Campaign', 'charitable' ); ?></a></li>
						<?php if ( $givewp_installed ) : ?>
							<li><a href="https://www.wpcharitable.com/documentation/import-export-tool-givewp-to-charitable/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=campaign-page&utm_content=Import%20Guide" target="_blank"><?php esc_html_e( 'Import Campaigns from GiveWP', 'charitable' ); ?></a></li>
						<?php else : ?>
							<li><a href="https://www.wpcharitable.com/documentation/installing-extensions/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=campaign-page&utm_content=Extension%20Guide" target="_blank"><?php esc_html_e( 'How to Install and Activate Charitable Extensions', 'charitable' ); ?></a></li>
						<?php endif; ?>
						<?php if ( charitable_disable_legacy_campaigns() ) : ?>
							<li><a class="legacy-campaign-link" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=campaign' ) ); ?>"><?php esc_html_e( 'Create a Legacy Campaign', 'charitable' ); ?></a></li>
						<?php else : ?>
							<li><a class="modern-campaign-link" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder&view=template' ) ); ?>"><?php esc_html_e( 'Create A Modern Campaign', 'charitable' ); ?></a></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
			<?php
		}

		/**
		 * Enqueue dashboard assets for AJAX button functionality on blank slate.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		private function enqueue_blank_slate_assets() {
			$version = charitable()->get_version();
			$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script(
				'charitable-admin-dashboard',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-admin-dashboard' . $suffix . '.js',
				[ 'jquery' ],
				$version,
				true
			);

			wp_localize_script(
				'charitable-admin-dashboard',
				'charitable_admin',
				[
					'nonce'   => wp_create_nonce( 'charitable-admin' ),
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				]
			);
		}

		/**
		 * Check if GiveWP is installed and active.
		 *
		 * @since 1.8.10
		 *
		 * @return bool
		 */
		private function is_givewp_installed() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			return is_plugin_active( 'give/give.php' );
		}

		/**
		 * Count GiveWP forms (campaigns).
		 *
		 * @since 1.8.10
		 *
		 * @return int
		 */
		private function get_givewp_campaign_count() {
			global $wpdb;

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'draft', 'private')",
					'give_forms'
				)
			);
		}

		/**
		 * Get the Ambassadors addon button state based on install/license status.
		 *
		 * @since 1.8.10
		 *
		 * @return array
		 */
		private function get_ambassadors_addon_state() {
			$slug = 'charitable-ambassadors';

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_file       = $slug . '/' . $slug . '.php';
			$is_installed      = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
			$is_active         = $is_installed && is_plugin_active( $plugin_file );
			$current_plan      = Charitable_Addons_Directory::get_current_plan_slug();
			$required_plans    = [ 'pro', 'agency' ];
			$has_required_plan = in_array( $current_plan, $required_plans, true );

			if ( $is_installed && $is_active ) {
				return [
					'button_text'     => __( 'Installed', 'charitable' ),
					'button_class'    => 'charitable-dashboard-v2-installed-button',
					'button_action'   => 'installed',
					'button_disabled' => true,
				];
			}

			if ( $is_installed && ! $is_active ) {
				return [
					'button_text'     => __( 'Activate', 'charitable' ),
					'button_class'    => 'charitable-dashboard-v2-activate-button',
					'button_action'   => 'activate_addon',
					'button_disabled' => false,
				];
			}

			if ( ! $has_required_plan ) {
				return [
					'button_text'     => __( 'Upgrade To Pro', 'charitable' ),
					'button_class'    => 'charitable-dashboard-v2-upgrade-button',
					'button_action'   => 'upgrade',
					'button_disabled' => false,
					'upgrade_url'     => 'https://wpcharitable.com/lite-upgrade/?discount=LITEUPGRADE&utm_source=WordPress&utm_campaign=liteplugin&utm_medium=blank-slate&utm_content=Ambassadors',
				];
			}

			return [
				'button_text'     => __( 'Install & Activate', 'charitable' ),
				'button_class'    => 'charitable-dashboard-v2-install-button',
				'button_action'   => 'install_addon',
				'button_disabled' => false,
			];
		}

		/**
		 * Renders the blank slate recommendations.
		 *
		 * @since      1.8.1.5
		 * @deprecated 1.8.10 No longer used. Replaced by card-based layout in render_blank_state().
		 *
		 * @return string
		 */
		public function render_blank_slate_recommendations() {

			$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();
			$user_recommended_list          = $charitable_plugins_third_party->get_recommendations( 'campaign', 3 );

			if ( empty( $user_recommended_list ) ) {
				return false;
			}

			ob_start();

			if ( ! empty( $user_recommended_list ) ) {

				foreach ( $user_recommended_list as $slug => $item ) {
					echo $this->render_blank_slate_recommendation( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			} else {

				return;

			}

			$output = ob_get_clean();

			return $output;
		}

		/**
		 * Renders a blank slate recommendation.
		 *
		 * @since 1.8.1.5
		 *
		 * @param string $slug The slug of the recommendation.
		 *
		 * @return string
		 */
		public function render_blank_slate_recommendation( $slug = '' ) {

			// check and see if the slug refers to a Charitable addon first.
			$recommended_addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $recommended_addons ) {
				$recommended_addons = charitable_get_addons_data_from_server();
			}

			$recommended_addon = array();
			$addon_slug        = $slug;

			if ( $recommended_addons ) {

				foreach ( (array) $recommended_addons as $i => $addon ) {

					if ( ! empty( $addon['slug'] ) && (string) $addon_slug === (string) $addon['slug'] ) {
						$recommended_addon = $addon;
						break;
					}
				}
			}

			if ( ! empty( $recommended_addon ) ) {

				// this is a Chartiable addon.
				$title       = str_replace( 'Charitable', '', $recommended_addon['name'] );
				$sections    = ! empty( $recommended_addon['sections'] ) ? unserialize( $recommended_addon['sections'] ) : false;
				$description = is_array( $sections ) && ! empty( $sections['description'] ) ? $sections['description'] : '';

				ob_start();

				?>

				<div class="charitable-intergration-step charitable-addon charitable-plugin-suggestion" data-status="<?php echo esc_attr( $slug ); ?>">
					<div class="instructions">
						<header class="charitable-intergration-step-header">
							<div class="sub-header">
								<h3><?php echo esc_html( $title ); ?></h3>
								<span class="badge"><a href="#"><?php esc_html_e( 'Pro', 'charitable' ); ?></a></span>
							</div>
						</header>
						<p class="description"><?php echo wp_strip_all_tags( $description ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-addons&search=' . esc_attr( str_replace( '-', ' ', $slug ) ) ) ); ?>" class="charitable-button button-link charitable-addons"><?php esc_html_e( 'View Addons', 'charitable' ); ?></a>
					</div>
					<div class="step">
						<div class="vertical-wrapper">
							<div class="step-image"><a class="suggestion-dismiss" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>" data-plugin-slug="<?php echo esc_attr( $slug ); ?>" data-plugin-type="addon" href="#"><i class="" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>"></i></a></div>
						</div>
					</div>
				</div>


				<?php

				$output = ob_get_clean();

			} else {

				// this is a third party plugin.

				$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();
				$plugin_data                    = $charitable_plugins_third_party->get_plugin( esc_attr( $slug ) );

				if ( ! $plugin_data ) {
					return '';
				}

				ob_start();

				?>

				<div class="charitable-intergration-step charitable-third-party charitable-plugin-suggestion" data-status="<?php echo esc_attr( $slug ); ?>">
					<div class="instructions">

						<header class="charitable-intergration-step-header">
							<div class="sub-header">
								<h3><?php echo esc_html( $plugin_data['title'] ); ?></h3>
								<span class="badge"><a href="#"><?php esc_html_e( 'Partner', 'charitable' ); ?></a></span>
							</div>
						</header>

						<p class="description"><?php echo esc_html( $plugin_data['excerpt'] ); ?></p>
						<?php
							$plugin_button_html = $charitable_plugins_third_party->get_plugin_button_html( $slug, false, '' );
							echo $plugin_button_html; // phpcs:ignore
						?>
					</div>
					<div class="step">
						<div class="vertical-wrapper">

							<div class="step-image"><a class="suggestion-dismiss" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>" data-plugin-slug="<?php echo esc_attr( $slug ); ?>" data-plugin-type="partner" href="#"><i class="" title="<?php esc_html_e( 'Dismiss this suggestion', 'charitable' ); ?>"></i></a></div>

						</div>
					</div>
				</div>


				<?php

				$output = ob_get_clean();

			}

			return $output;
		}
	}

endif;
