<?php
/**
 * Sets up the donations list table in the admin.
 *
 * @package   Charitable/Classes/Charitable_Donation_List_Table
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.8.1.5, 1.8.9.1, 1.8.10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_List_Table' ) ) :

	/**
	 * Charitable_Donation_List_Table class.
	 *
	 * @final
	 * @since 1.5.0
	 */
	final class Charitable_Donation_List_Table {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Donation_List_Table|null
		 */
		private static $instance = null;

		/**
		 * Post type.
		 *
		 * @var string
		 */
		protected $list_table_type = 'donation';

		/**
		 * Status counts.
		 *
		 * @var array
		 */
		private $status_counts;

		/**
		 * Create object instance.
		 *
		 * @since 1.5.0
		 */
		public function __construct() {
			do_action( 'charitable_admin_donation_post_type_start', $this );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Donation_List_Table
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Customize donations columns.
		 *
		 * @see     get_column_headers
		 *
		 * @since   1.5.0
		 *
		 * @return  array
		 */
		public function dashboard_columns() {
			/**
			 * Filter the columns shown in the donations list table.
			 *
			 * @since 1.0.0
			 *
			 * @param array $columns The list of columns.
			 */
			return apply_filters(
				'charitable_donation_dashboard_column_names',
				array(
					'cb'            => '<input type="checkbox"/>',
					'id'            => __( 'Donation', 'charitable' ),
					'amount'        => __( 'Amount Donated', 'charitable' ),
					'campaigns'     => __( 'Campaign(s)', 'charitable' ),
					'donation_date' => __( 'Date', 'charitable' ),
					'post_status'   => __( 'Status', 'charitable' ),
				)
			);
		}

		/**
		 * Add information to the dashboard donations table listing.
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
			$donation = charitable_get_donation( $post_id );

			switch ( $column_name ) {

				case 'id':
					$title = esc_attr__( 'View Donation Details', 'charitable' );
					$name  = $donation->get_donor()->get_name();
					$email = $donation->get_donor()->get_email();

					if ( $name ) {
						$text = sprintf(
							/* translators: %1$d is the donation ID, %2$s is the donor name */
							_x( '#%1$d by %2$s', 'number symbol', 'charitable' ),
							$post_id,
							$name
						);
						if ( $email ) {
							$text .= '<br/>' . sprintf(
								/* translators: %s is the donor email */
								_x( '%1$s', 'email', 'charitable' ), // phpcs:ignore
								$email
							);
						}
					} else {
						$text = sprintf(
							/* translators: %d is the donation ID */
							_x( '#%d', 'number symbol', 'charitable' ),
							$post_id
						);
					}

					$url = esc_url(
						add_query_arg(
							array(
								'post'   => $post_id,
								'action' => 'edit',
							),
							admin_url( 'post.php' )
						)
					);

					$display = sprintf( '<a href="%s" aria-label="%s">%s</a>', $url, $title, $text );
					break;

				case 'post_status':
					$display = sprintf(
						'<mark class="status %s">%s</mark>',
						esc_attr( $donation->get_status() ),
						strtolower( $donation->get_status_label() )
					);
					break;

				case 'amount':
					$display = charitable_format_money( $donation->get_total_donation_amount(), false, false, $donation->get_currency() );
					// translators: %s is the gateway label.
					$display .= '<span class="meta">' . sprintf( _x( 'via %s', 'gateway label in donations', 'charitable' ), $donation->get_gateway_label() ) . '</span>';
					break;

				case 'campaigns':
					$campaigns = array();

					foreach ( $donation->get_campaign_donations() as $cd ) {
						$campaigns[] = sprintf(
							'<a href="edit.php?post_type=%s&campaign_id=%s">%s</a>',
							Charitable::DONATION_POST_TYPE,
							$cd->campaign_id,
							$cd->campaign_name
						);
					}

					$display = implode( ', ', $campaigns );
					break;

				case 'donation_date':
					$display = $donation->get_date();
					if ( ! defined( 'CHARITABLE_DONATIONS_LIST_SHOW_DONATION_TIME' ) || ( defined( 'CHARITABLE_DONATIONS_LIST_SHOW_DONATION_TIME' ) && CHARITABLE_DONATIONS_LIST_SHOW_DONATION_TIME ) ) {
						$display .= ' <span>' . $donation->get_time() . '</span>';
					}
					break;

				default:
					$display = '';
					break;

			}

			/**
			 * Filter the output of the cell.
			 *
			 * @since 1.0.0
			 *
			 * @param string              $display     The content that will be displayed.
			 * @param string              $column_name The name of the column.
			 * @param int                 $post_id     The ID of the donation being shown.
			 * @param Charitable_Donation $donation    The Charitable_Donation object.
			 */
			echo apply_filters( 'charitable_donation_column_display', $display, $column_name, $post_id, $donation ); // phpcs:ignore
		}

		/**
		 * Make columns sortable.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $columns List of columns that are sortable by default.
		 * @return array
		 */
		public function sortable_columns( $columns ) {
			$sortable_columns = array(
				'id'            => 'ID',
				'amount'        => 'amount',
				'donation_date' => 'date',
			);

			return wp_parse_args( $sortable_columns, $columns );
		}

		/**
		 * Set list table primary column for donations.
		 *
		 * Support for WordPress 4.3.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $default  Default primary column.
		 * @param  string $screen_id The current screen ID.
		 * @return string
		 */
		public function primary_column( $default, $screen_id ) { // phpcs:ignore
			if ( 'edit-donation' === $screen_id ) {
				return 'id';
			}

			return $default;
		}

		/**
		 * Set row actions for donations.
		 *
		 * @since  1.5.0
		 *
		 * @param  array   $actions List of row actions.
		 * @param  WP_Post $post   The current post object.
		 * @return array
		 */
		public function row_actions( $actions, $post ) {
			if ( Charitable::DONATION_POST_TYPE !== $post->post_type ) {
				return $actions;
			}

			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'post'      => $post->ID,
							'action'    => 'edit',
							'show_form' => true,
						),
						admin_url( 'post.php' )
					)
				),
				esc_attr__( 'Edit Donation', 'charitable' ),
				__( 'Edit', 'charitable' )
			);

			$actions = array_merge(
				array(
					'view' => sprintf(
						'<a href="%s" aria-label="%s">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'post'   => $post->ID,
									'action' => 'edit',
								),
								admin_url( 'post.php' )
							)
						),
						esc_attr__( 'View Details', 'charitable' ),
						__( 'View', 'charitable' )
					),
				),
				$actions
			);

			return $actions;
		}

		/**
		 * Customize the output of the status views.
		 *
		 * @since  1.5.0
		 *
		 * @param  string[] $views The default list of status views.
		 * @return string[]
		 */
		public function set_status_views( $views ) {
			$counts  = $this->get_status_counts();
			$current = array_key_exists( 'post_status', $_GET ) ? esc_html( $_GET['post_status'] ) : ''; // phpcs:ignore

			foreach ( charitable_get_valid_donation_statuses() as $key => $label ) {
				$views[ $key ] = sprintf(
					'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
					esc_url(
						add_query_arg(
							array(
								'post_status' => $key,
								'paged'       => false,
							)
						)
					),
					$current === $key ? ' class="current"' : '',
					$label,
					array_key_exists( $key, $counts ) ? $counts[ $key ] : '0'
				);
			}

			$views['all'] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( remove_query_arg( array( 'post_status', 'paged' ) ) ),
				'all' === $current || '' === $current ? ' class="current"' : '',
				__( 'All', 'charitable' ),
				array_sum( $counts )
			);

			unset( $views['mine'] );

			return $views;
		}

		/**
		 * Add Custom bulk actions
		 *
		 * @param   array $actions The list of bulk actions.
		 * @return  array
		 * @since   1.5.0
		 */
		public function custom_bulk_actions( $actions ) {
			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			return array_merge( $actions, $this->get_bulk_actions() );
		}

		/**
		 * Process bulk actions
		 *
		 * @param   int    $redirect_to The URL to redirect to.
		 * @param   string $action  The action being performed.
		 * @param   int[]  $post_ids    The list of post IDs.
		 * @return  string
		 * @since   1.5.0
		 */
		public function bulk_action_handler( $redirect_to, $action, $post_ids ) {
			/* Bail out if this is not a status-changing action. */
			if ( strpos( $action, 'set-' ) === false ) {
				$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'locked', 'ids' ), wp_get_referer() );
				wp_safe_redirect( esc_url_raw( $sendback ) );

				exit();
			}

			$donation_statuses = charitable_get_valid_donation_statuses();

			$new_status    = str_replace( 'set-', '', $action ); // get the status name from action.
			$report_action = 'bulk_' . Charitable::DONATION_POST_TYPE . '_status_update';

			/**
			 * Sanity check: bail out if this is actually not a status, or is
			 * not a registered status.
			 */
			if ( ! isset( $donation_statuses[ $new_status ] ) ) {
				return $redirect_to;
			}

			foreach ( $post_ids as $post_id ) {
				$donation = charitable_get_donation( $post_id );
				$donation->update_status( $new_status );

				do_action( 'charitable_donations_table_do_bulk_action', $post_id, $new_status );
			}

			$redirect_to = add_query_arg( $report_action, count( $post_ids ), $redirect_to );

			return $redirect_to;
		}


		/**
		 * Remove edit from the bulk actions.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $actions List of bulk actions.
		 * @return array
		 */
		public function remove_bulk_actions( $actions ) {
			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			return $actions;
		}

		/**
		 * Retrieve the bulk actions.
		 *
		 * @since  1.5.0
		 *
		 * @return array $actions Array of the bulk actions
		 */
		public function get_bulk_actions() {
			$actions = array();

			foreach ( charitable_get_valid_donation_statuses() as $status_key => $label ) {
				/* translators: %s is the status label */
				$actions[ 'set-' . $status_key ] = sprintf( _x( 'Set to %s', 'set donation status to x', 'charitable' ), $label );
			}

			/**
			 * Filter the list of bulk actions for donations.
			 *
			 * @since 1.4.0
			 *
			 * @param array $actions The list of bulk actions.
			 */
			return apply_filters( 'charitable_donations_table_bulk_actions', $actions );
		}

		/**
		 * Add extra bulk action options to mark orders as complete or processing.
		 *
		 * Using Javascript until WordPress core fixes: https://core.trac.wordpress.org/ticket/16031
		 *
		 * @since  1.5.0
		 *
		 * @global string $post_type
		 *
		 * @return void
		 */
		public function bulk_admin_footer() {
			global $post_type;

			if ( Charitable::DONATION_POST_TYPE === $post_type ) {

				$js  = '<script type="text/javascript">';
				$js .= '(function($) {';
				foreach ( $this->get_bulk_actions() as $status_key => $label ) {
					$js .= sprintf( "jQuery('<option>').val('%s').text('%s').appendTo( [ '#bulk-action-selector-top', '#bulk-action-selector-bottom' ] );", $status_key, $label );
				}
				$js .= '})(jQuery);';
				$js .= '</script>';

				echo $js; // phpcs:ignore
			}
		}

		/**
		 * Process the new bulk actions for changing order status.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function process_bulk_action() {
			/* We only want to deal with donations. In case any other CPTs have an 'active' action. */
			if ( ! isset( $_REQUEST['post_type'] ) || Charitable::DONATION_POST_TYPE !== $_REQUEST['post_type'] || ! isset( $_REQUEST['post'] ) ) {
				return;
			}

			check_admin_referer( 'bulk-posts' );

			/* Get the action. */
			$action = '';

			if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) );
			}

			$post_ids    = array_map( 'absint', (array) $_REQUEST['post'] );
			$redirect_to = add_query_arg(
				array(
					'post_type' => Charitable::DONATION_POST_TYPE,
				),
				admin_url( 'edit.php' )
			);
			$redirect_to = $this->bulk_action_handler( $redirect_to, $action, $post_ids );

			wp_safe_redirect( esc_url_raw( $redirect_to ) );

			exit();
		}

		/**
		 * Show confirmation message that order status changed for number of orders.
		 *
		 * @since  1.5.0
		 *
		 * @global string $post_type
		 * @global string $pagenow
		 *
		 * @return void
		 */
		public function bulk_admin_notices() {
			global $post_type, $pagenow;

			/* Bail out if not on shop order list page. */
			if ( 'edit.php' !== $pagenow || Charitable::DONATION_POST_TYPE !== $post_type ) {
				return;
			}

			/* Check if any status changes happened. */
			$report_action = 'bulk_' . Charitable::DONATION_POST_TYPE . '_status_update';

			if ( ! empty( $_REQUEST[ $report_action ] ) ) { // phpcs:ignore
				$number  = absint( $_REQUEST[ $report_action ] ); // phpcs:ignore
				// translators: %s is the number of orders.
				$message = sprintf( _n( '%s donation status changed.', '%s donation statuses changed.', $number, 'charitable' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
			}
		}

		/**
		 * Change messages when a post type is updated.
		 *
		 * @since  1.5.0
		 * @version 1.8.1.15
		 *
		 * @global WP_Post $post
		 * @global int     $post_ID
		 * @param  array $messages The default list of messages.
		 * @return array
		 */
		public function post_messages( $messages ) {
			global $post, $post_ID;

			$messages[ Charitable::DONATION_POST_TYPE ] = array(
				0  => '', // Unused. Messages start at index 1.
				// translators: %s: the URL.
				1  => sprintf( __( 'Donation updated. <a href="%s">View Donation</a>', 'charitable' ), esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.', 'charitable' ),
				3  => __( 'Custom field deleted.', 'charitable' ),
				4  => __( 'Donation updated.', 'charitable' ),
				// translators: %s: post title.
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Donation restored to revision from %s', 'charitable' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// translators: %s: the URL.
				6  => sprintf( __( 'Donation published. <a href="%s">View Donation</a>', 'charitable' ), esc_url( get_permalink( $post_ID ) ) ),
				7  => __( 'Donation saved.', 'charitable' ),
				8  => sprintf(
					// translators: %s: the URL.
					__( 'Donation submitted. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				9  => sprintf(
					// translators: %1$s is the date, %2$s is the preview link.
					__( 'Donation scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Donation</a>', 'charitable' ),
					date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ),
					esc_url( get_permalink( $post_ID ) )
				),
				10 => sprintf(
					// translators: %s is the preview link.
					__( 'Donation draft updated. <a target="_blank" href="%s">Preview Donation</a>', 'charitable' ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				11 => __( 'Donation updated and email sent.', 'charitable' ),
				12 => __( 'Email could not be sent.', 'charitable' ),
			);

			return $messages;
		}

		/**
		 * Modify bulk messages
		 *
		 * @since  1.5.0
		 *
		 * @param  array $bulk_messages Bulk messages.
		 * @param  array $bulk_counts Bulk counts.
		 * @return array
		 */
		public function bulk_messages( $bulk_messages, $bulk_counts ) {
			$bulk_messages[ Charitable::DONATION_POST_TYPE ] = array(
				// translators: %d: number of items.
				'updated'   => _n( '%d donation updated.', '%d donations updated.', $bulk_counts['updated'], 'charitable' ),
				'locked'    => ( 1 == $bulk_counts['locked'] ) ? __( '1 donation not updated, somebody is editing it.', 'charitable' ) :
									// translators: %d: number of items.
									_n( '%s donation not updated, somebody is editing it.', '%s donations not updated, somebody is editing them.', $bulk_counts['locked'], 'charitable' ),
				// translators: %s: number of items.
				'deleted'   => _n( '%s donation permanently deleted.', '%s donations permanently deleted.', $bulk_counts['deleted'], 'charitable' ),
				// translators: %s: number of items.
				'trashed'   => _n( '%s donation moved to the Trash.', '%s donations moved to the Trash.', $bulk_counts['trashed'], 'charitable' ),
				// translators: %s: number of items.
				'untrashed' => _n( '%s donation restored from the Trash.', '%s donations restored from the Trash.', $bulk_counts['untrashed'], 'charitable' ),
			);

			return $bulk_messages;
		}

		/**
		 * Disable the month's dropdown (will replace with custom range search).
		 *
		 * @since  1.5.0
		 *
		 * @param  mixed  $disable           Whether to disable the dropdown.
		 * @param  string $post_type         The current post type.
		 * @return boolean
		 */
		public function disable_months_dropdown( $disable, $post_type ) {
			if ( Charitable::DONATION_POST_TYPE == $post_type ) {
				$disable = true;
			}

			return $disable;
		}

		/**
		 * Add date-based filters above the donations table.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @return void
		 */
		public function add_filters() {
			global $typenow;

			/* Show custom filters to filter orders by donor. */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/filters' );
			}
		}

		/**
		 * Add extra buttons after filters
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  string $which The context where this is being called.
		 * @return void
		 */
		public function add_export( $which ) {
			global $typenow;

			if ( ! current_user_can( 'export_charitable_reports' ) ) {
				return;
			}

			/* Add the export button. */
			if ( 'top' == $which && in_array( $typenow, array( Charitable::DONATION_POST_TYPE ) ) ) {
				charitable_admin_view( 'donations-page/export' );
			}
		}

		/**
		 * Add modal template to footer.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 * @global string $pagenow The current page.
		 *
		 * @return void
		 */
		public function modal_forms() {
			global $typenow, $pagenow;

			/* Add the modal form. */
			if ( 'edit.php' === $pagenow && in_array( $typenow, array( Charitable::DONATION_POST_TYPE ), true ) ) {
				charitable_admin_view( 'donations-page/export-form' );
				charitable_admin_view( 'donations-page/filter-form' );
			}
		}

		/**
		 * Admin scripts and styles.
		 *
		 * Set up the scripts & styles used for the modal.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 *
		 * @param  string $hook The current page hook.
		 * @return void
		 */
		public function load_scripts( $hook ) {
			if ( 'edit.php' !== $hook ) {
				return;
			}

			global $typenow;

			/* Enqueue the scripts for donation page */
			if ( in_array( $typenow, array( Charitable::DONATION_POST_TYPE ), true ) ) {
				wp_enqueue_style( 'lean-modal-css' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'lean-modal' );
				wp_enqueue_script( 'charitable-admin-tables' );
			}
		}

		/**
		 * Add custom filters to the query that returns the donations to be displayed.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 * @global string $pagenow The current admin page.
		 *
		 * @param  array $vars The array of args to pass to WP_Query.
		 * @return array
		 */
		public function filter_request_query( $vars ) {
			global $typenow, $pagenow;

			if ( 'edit.php' !== $pagenow || Charitable::DONATION_POST_TYPE !== $typenow ) {
				return $vars;
			}

			/* No Status: fix WP's crappy handling of "all" post status. */
			if ( ! isset( $vars['post_status'] ) || empty( $vars['post_status'] ) ) {
				$vars['post_status'] = array_keys( charitable_get_valid_donation_statuses() );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_GET['charitable_nonce'] ) && wp_verify_nonce( wp_unslash( $_GET['charitable_nonce'] ), 'charitable_filter_campaigns' ) ) :

				/* Set up date query */
				if ( isset( $_GET['start_date'] ) && ! empty( $_GET['start_date'] ) ) {
					$start_date                  = $this->get_parsed_date( sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) );
					$vars['date_query']['after'] = array(
						'year'  => $start_date['year'],
						'month' => $start_date['month'],
						'day'   => $start_date['day'],
					);
				}

				if ( isset( $_GET['end_date'] ) && ! empty( $_GET['end_date'] ) ) {
					$end_date                     = $this->get_parsed_date( sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) );
					$vars['date_query']['before'] = array(
						'year'  => $end_date['year'],
						'month' => $end_date['month'],
						'day'   => $end_date['day'],
					);
				}

				if ( isset( $vars['date_query'] ) ) {
					$vars['date_query']['inclusive'] = true;
				}

			endif;

			/* Filter by campaign. */
			if ( isset( $_GET['campaign_id'] ) && 'all' !== $_GET['campaign_id'] ) {
				$vars['post__in'] = charitable_get_table( 'campaign_donations' )->get_donation_ids_for_campaign( (int) sanitize_text_field( wp_unslash( $_GET['campaign_id'] ) ) );
			}

			/* If the user cannot view/edit others donations, filter by author. */
			if ( ! current_user_can( 'edit_others_donations' ) ) {
				$vars['author'] = get_current_user_id();
			}

			return $vars;
		}

		/**
		 * Column sorting handler.
		 *
		 * @since  1.5.0
		 *
		 * @global string $typenow The current post type.
		 * @global WPDB $wpdb The WPDB object.
		 * @param  array $clauses Array of SQL query clauses.
		 * @return array
		 */
		public function sort_donations( $clauses ) {
			global $typenow, $wpdb;

			if ( Charitable::DONATION_POST_TYPE != $typenow ) {
				return $clauses;
			}

			if ( ! isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $clauses;
			}

			/* Sorting */
			$order = isset( $_GET['order'] ) && strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) == 'ASC' ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			switch ( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'amount':
					$clauses['join']    = "JOIN {$wpdb->prefix}charitable_campaign_donations cd ON cd.donation_id = $wpdb->posts.ID ";
					$clauses['orderby'] = 'cd.amount ' . $order;
					break;
			}

			return $clauses;
		}

		/**
		 * Return the status counts, taking into account any current filters.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		protected function get_status_counts() {
			if ( ! isset( $this->status_counts ) ) {

				$args = array();

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['s'] ) ) {
				$s_value = sanitize_text_field( wp_unslash( $_GET['s'] ) );
				if ( strlen( $s_value ) ) {
					$args['s'] = $s_value;
				}
			}

			if ( isset( $_GET['start_date'] ) ) {
				$start_date_value = sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
				if ( strlen( $start_date_value ) ) {
					$args['start_date'] = $this->get_parsed_date( $start_date_value );
				}
			}

			if ( isset( $_GET['end_date'] ) ) {
				$end_date_value = sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
				if ( strlen( $end_date_value ) ) {
					$args['end_date'] = $this->get_parsed_date( $end_date_value );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

				/* If the user cannot view/edit others donations, filter by author. */
				if ( ! current_user_can( 'edit_others_donations' ) ) {
					$args['author'] = get_current_user_id();
				}

				$status_counts = Charitable_Donations::count_by_status( $args );

				foreach ( charitable_get_valid_donation_statuses() as $key => $label ) {
					$this->status_counts[ $key ] = array_key_exists( $key, $status_counts )
						? $status_counts[ $key ]->num_donations
						: 0;
				}
			}

			return $this->status_counts;
		}

		/**
		 * Given a date, returns an array containing the date, month and year.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $date A date as a string that can be parsed by strtotime.
		 * @return string[]
		 */
		protected function get_parsed_date( $date ) {
			$time = charitable_sanitize_date_alt_format( $date );

			return array(
				'year'  => date( 'Y', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'month' => date( 'm', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'day'   => date( 'd', $time ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			);
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
			echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub, .wrap .search-box, .wrap .tablenav-pages  { display: none; } #posts-filter .tablenav.bottom { height: auto; } body.post-type-donation .page-title-action { display: none; } </style>';
		} elseif ( 'top' === $which && ! $blank_state_rendered ) {
			// Fallback: if bottom never fires (WordPress doesn't render it with 0 posts), render in top
			$blank_state_rendered = true;
			$this->enqueue_blank_slate_assets();
			$this->render_blank_state();
			echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top .actions, .wrap .subsubsub, .wrap .search-box, .wrap .tablenav-pages  { display: none; } #posts-filter .tablenav.top { height: auto; } body.post-type-donation .page-title-action { display: none; } </style>';
		}
	}

		/**
		 * Renders advice in the event that no donations exist yet.
		 *
		 * @since   1.8.1.5
		 * @version 1.8.10
		 *
		 * @return void
		 */
		public function render_blank_state(): void {
		?>
		<div class="charitable-blank-slate">
			<!-- Page Header -->
			<div class="page-header">
				<h1 class="page-title"><?php esc_html_e( 'Donations', 'charitable' ); ?></h1>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=donation' ) ); ?>" class="btn-primary"><?php esc_html_e( '+ Add Manual Donation', 'charitable' ); ?></a>
			</div>

			<!-- Primary Welcome Section -->
			<div class="welcome-card">
				<h2 class="welcome-title"><?php esc_html_e( 'Welcome to Charitable!', 'charitable' ); ?></h2>
				<p class="welcome-description">
					<?php esc_html_e( 'Start accepting online donations by setting up payment processing for your campaigns.', 'charitable' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways' ) ); ?>" class="btn-primary btn-large"><?php esc_html_e( 'Set Up Payment Processing', 'charitable' ); ?></a>
			</div>

			<!-- Secondary Actions -->
			<div class="secondary-actions">
				<div class="action-card">
					<h3><?php esc_html_e( 'Already Have Donations Elsewhere?', 'charitable' ); ?></h3>
					<?php
					$givewp_installed      = $this->is_givewp_installed();
					$givewp_donation_count = $givewp_installed ? $this->get_givewp_donation_count() : 0;

					if ( $givewp_installed && $givewp_donation_count > 0 ) :
					?>
					<p class="givewp-detection-text">
						<?php
						printf(
							wp_kses(
								/* translators: 1: bolded "GiveWP", 2: number of donations. */
								__( 'We detected %1$s with %2$s donations installed on your site. Use our import tool to import them into Charitable.', 'charitable' ),
								[
									'strong' => [],
									'span'   => [ 'class' => [] ],
								]
							),
							'<strong>GiveWP</strong>',
							'<span class="givewp-count">' . absint( $givewp_donation_count ) . '</span>'
						);
						?>
					</p>
					<a href="https://www.wpcharitable.com/documentation/import-export-tool-givewp-to-charitable/" class="btn-secondary" target="_blank"><?php esc_html_e( 'Import GiveWP Donations', 'charitable' ); ?></a>
					<?php elseif ( $givewp_installed && 0 === $givewp_donation_count ) : ?>
					<p class="givewp-detection-text">
						<?php
						printf(
							wp_kses(
								/* translators: %s: bolded "GiveWP". */
								__( 'Did you know you can import %s donations, campaigns, and donors right into Charitable?', 'charitable' ),
								[ 'strong' => [] ]
							),
							'<strong>GiveWP</strong>'
						);
						?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=import&sub_tab=givewp' ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Import Tools', 'charitable' ); ?></a>
					<?php else : ?>
					<p><?php esc_html_e( 'Import your existing donations from your old fundraising platform to get started quickly with Charitable.', 'charitable' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-tools&tab=import&sub_tab=charitable' ) ); ?>" class="btn-secondary"><?php esc_html_e( 'Import Donations', 'charitable' ); ?></a>
					<?php endif; ?>
				</div>

				<div class="blank-slate-feature-card">
					<div class="blank-slate-feature-title-icon-row">
						<div class="blank-slate-feature-icon">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/icons/recurring.svg' ); ?>" alt="<?php esc_attr_e( 'Recurring Donations', 'charitable' ); ?>" width="32" height="32" />
						</div>
						<h3 class="blank-slate-feature-title"><?php esc_html_e( 'Recurring Donations', 'charitable' ); ?> <span class="blank-slate-pro-badge"><?php esc_html_e( 'PRO', 'charitable' ); ?></span></h3>
					</div>
					<div class="blank-slate-feature-content">
						<p class="blank-slate-feature-description"><?php esc_html_e( 'Increase donor lifetime value by allowing supporters to set up recurring monthly donations.', 'charitable' ); ?></p>
						<div class="blank-slate-button-row">
						<?php
						$recurring_state = $this->get_recurring_addon_state();

						if ( 'upgrade' === $recurring_state['button_action'] ) {
							$upgrade_url = isset( $recurring_state['upgrade_url'] ) ? $recurring_state['upgrade_url'] : '#';
							?>
							<a href="<?php echo esc_url( $upgrade_url ); ?>" class="charitable-dashboard-v2-enhance-grid-button <?php echo esc_attr( $recurring_state['button_class'] ); ?>" data-action="upgrade" target="_blank"><?php echo esc_html( $recurring_state['button_text'] ); ?></a>
							<?php
						} else {
							$button_attributes = [
								'data-action="' . esc_attr( $recurring_state['button_action'] ) . '"',
								'data-slug="charitable-recurring-donations"',
								'data-type="charitable_addon"',
							];

							if ( $recurring_state['button_disabled'] ) {
								$button_attributes[] = 'disabled';
							}
							?>
							<button class="charitable-dashboard-v2-enhance-grid-button <?php echo esc_attr( $recurring_state['button_class'] ); ?>" <?php echo implode( ' ', $button_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $recurring_state['button_text'] ); ?></button>
							<?php
						}
						?>
						<a href="https://www.wpcharitable.com/extensions/charitable-recurring-donations/" class="charitable-dashboard-v2-enhance-grid-button charitable-dashboard-v2-learn-more-button" target="_blank"><?php esc_html_e( 'Learn More', 'charitable' ); ?></a>
						</div>
					</div>
				</div>
			</div>

			<!-- Help Resources -->
			<div class="help-resources">
				<h3><?php esc_html_e( 'Additional Resources', 'charitable' ); ?></h3>
				<ul class="help-links">
					<li><a href="https://www.wpcharitable.com/documentation/adding-payment-gateways/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=donation-page&utm_content=Payment%20Guide" target="_blank"><?php esc_html_e( 'Setting Up Payment Gateways', 'charitable' ); ?></a></li>
					<li><a href="https://www.wpcharitable.com/documentation/import-export-tool-givewp-to-charitable/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=donation-page&utm_content=Import%20Guide" target="_blank"><?php esc_html_e( 'Import Donations from GiveWP', 'charitable' ); ?></a></li>
					<li><a href="https://www.wpcharitable.com/features-main/reports/?utm_campaign=liteplugin&utm_source=charitableplugin&utm_medium=donation-page&utm_content=Reports%20Guide" target="_blank"><?php esc_html_e( 'Donation Reports & Analytics', 'charitable' ); ?></a></li>
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
		 * Count GiveWP donations (payments) in live mode.
		 *
		 * @since 1.8.10
		 *
		 * @return int
		 */
		private function get_givewp_donation_count() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'give_donationmeta';

			// Check if the GiveWP donation meta table exists.
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
				return 0;
			}

			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->prefix}give_donationmeta dm ON p.ID = dm.donation_id
					WHERE p.post_type = 'give_payment'
					AND p.post_status IN ('publish', 'pending', 'failed', 'cancelled', 'refunded', 'revoked', 'abandoned', 'give_subscription')
					AND dm.meta_key = '_give_payment_mode'
					AND dm.meta_value = %s",
					'live'
				)
			);
		}

		/**
		 * Get the Recurring Donations addon button state based on install/license status.
		 *
		 * @since 1.8.10
		 *
		 * @return array
		 */
		private function get_recurring_addon_state() {
			$slug = 'charitable-recurring';

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_file       = $slug . '/' . $slug . '.php';
			$is_installed      = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
			$is_active         = $is_installed && is_plugin_active( $plugin_file );
			$has_required_plan = $this->has_required_plan_for_recurring();

			if ( ! $has_required_plan ) {
				return [
					'button_text'     => __( 'Upgrade To Pro', 'charitable' ),
					'button_class'    => 'charitable-dashboard-v2-upgrade-button',
					'button_action'   => 'upgrade',
					'button_disabled' => false,
					'upgrade_url'     => 'https://wpcharitable.com/lite-upgrade/?discount=LITEUPGRADE&utm_source=WordPress&utm_campaign=liteplugin&utm_medium=blank-slate&utm_content=RecurringDonations',
				];
			}

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

			return [
				'button_text'     => __( 'Install & Activate', 'charitable' ),
				'button_class'    => 'charitable-dashboard-v2-install-button',
				'button_action'   => 'install_addon',
				'button_disabled' => false,
			];
		}

		/**
		 * Check if the user's plan includes Recurring Donations.
		 *
		 * @since 1.8.10
		 *
		 * @return bool
		 */
		private function has_required_plan_for_recurring() {
			$current_plan  = Charitable_Addons_Directory::get_current_plan_slug();
			$allowed_plans = [ 'plus', 'pro', 'agency' ];

			return in_array( $current_plan, $allowed_plans, true );
		}
	}

endif;
