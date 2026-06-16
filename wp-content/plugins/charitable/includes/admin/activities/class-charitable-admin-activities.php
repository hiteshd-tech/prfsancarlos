<?php
/**
 * This class is responsible for setting up and general maintenance of acitivity logs.
 *
 * @package   Charitable/Classes/Charitable_Admin_Activities
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Activities' ) ) :

	/**
	 * Charitable_Admin_Activities
	 *
	 * @since 1.8.1
	 */
	final class Charitable_Admin_Activities {

		/**
		 * The name of our database table
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $table_name;

		/**
		 * The activities.
		 *
		 * @since 1.0.0
		 *
		 * @var   array
		 */
		public $activities;

		/**
		 * The database tables that store activity data.
		 *
		 * @since 1.8.1
		 *
		 * @var   array
		 */
		public $activity_db_tables = array( 'donation', 'campaign' );

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Admin_Activities|null
		 */
		private static $instance = null;


		/**
		 * Create class object.
		 *
		 * @since  1.8.1
		 */
		public function __construct() {
		}

		/**
		 * Create the table.
		 *
		 * @since 1.0.0
		 *
		 * @global WPDB $wpdb
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
				donor_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				email varchar(100) NOT NULL,
				first_name varchar(255) default '',
				last_name varchar(255) default '',
				date_joined datetime NOT NULL default '0000-00-00 00:00:00',
				data_erased datetime default '0000-00-00 00:00:00',
				contact_consent tinyint(1) unsigned default NULL,
				PRIMARY KEY  (donor_id),
				KEY user_id (user_id),
				KEY email (email),
				KEY data_erased (data_erased),
				KEY contact_consent (contact_consent)
				) $charset_collate;";

			// $this->_create_table( $sql );
		}

		/**
		 * Determine if we load the activity data (depending on if it's the lite version or not, and what page we are on).
		 *
		 * @since 1.8.1
		 *
		 * @return bool
		 */
		public function maybe_use_activity_data_sample() {
			// If lite version, give sample data to populate restricted area.
			if ( ! charitable_is_pro() && ( ! isset( $_GET['page'] ) || ( 'charitable-reports' === $_GET['page'] && isset( $_GET['tab'] ) && 'activity' === $_GET['tab'] ) ) ) { // phpcs:ignore
				return true;
			}

			return false;
		}

		/**
		 * Get activities.
		 *
		 * @since 1.8.1
		 *
		 * @param array|string $args {
		 *
		 *     An array of arguments.
		 *     @type array  $activity_filter_types The types of activity information you want. Accepts '' (all), 'donations-made', 'donations-made-paid', 'refunds', 'campaigns-created', 'user-comments'. Default: array().
		 *     @type array  $activity_action_types The action types of activities in the custom databae tables (primary and/or secondary). Empty array returns all. Default: array().
		 *     @type int    $campaign_id           Optional. Campaign ID that narrows activity to that campaign. Default: 0.
		 *     @type int    $category_id           Optional. For campaigns, this is the term ID for the custom Charitable category taxonomy. Default: 0.
		 *     @type string $start_date            The start date for the activity. Accepts 'Y/m/d'. Default: 7 days ago.
		 *     @type string $end_date              The end date for the activity. Accepts 'Y/m/d'. Default: today.
		 *     @type int    $limit                 Optional. The number of activities to return. Default: 0.
		 *     @type int    $offset                Optional. The number of activities to offset the query. Default: 0.
		 *     @type string $sort                  Optional. The column to sort by. Default: 'date_recorded'.
		 *
		 * }
		 * @return int|bool The ID of the group on success. False on error.
		 */
		public function get_activity( $args = array() ) {

			global $wpdb;

			// If lite version, give sample data to populate restricted area.
			if ( $this->maybe_use_activity_data_sample() ) {
				$this->activities = $this->get_activity_data_sample( 'donation' );
				return $this->activities;
			}

			// Gather the arguments passed to the function.
			$args = wp_parse_args(
				$args,
				array(
					'activity_filter_types' => array(),
					'activity_action_types' => array(),
					'campaign_id'           => 0,
					'category_id'           => 0,
					'start_date'            => gmdate( 'Y/m/d', strtotime( '-7 days' ) ),
					'end_date'              => gmdate( 'Y/m/d' ),
					'limit'                 => 0,
					'offset'                => 0,
					'sort_by'               => 'date_to_sort',
					'order'                 => 'DESC',
				)
			);

			// Santitize or check incoming args.
			$args['campaign_id'] = ( $args['campaign_id'] < 0 ) ? 0 : intval( $args['campaign_id'] ); // make sure -1 doesn't get passed as campaign_id.

			// convert state and end date to mysql format.
			$args['start_date'] = gmdate( 'Y-m-d', strtotime( $args['start_date'] ) ) . ' 00:00:00';
			$args['end_date']   = gmdate( 'Y-m-d', strtotime( $args['end_date'] ) ) . ' 23:59:59';
			$limit_sql          = $args['limit'] ? 'LIMIT ' . $args['limit'] : '';

			// Define the custom database table names and shortcuts.
			$activity_db_tables = $this->activity_db_tables;
			$table_names        = array();
			$table_shortcuts    = array();
			foreach ( $activity_db_tables as $activity_db_table ) {
				$table_shortcuts[ $activity_db_table ] = $activity_db_table . 's_a';
				$table_names[ $activity_db_table ]     = $wpdb->prefix . 'charitable_' . $activity_db_table . '_activities ' . $table_shortcuts[ $activity_db_table ];
			}

			// 1. build the WHERE statement for mysql based on the passed in values.
			$where_sql   = array(); // init the where.
			$where_sql[] = ( $args['campaign_id'] > 0 ) ? $table_shortcuts['donation'] . '.campaign_id = ' . $args['campaign_id'] : false;
			$where_sql[] = "a.primary_action != ''";

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			$db_types   = array();
			$db_actions = array(
				'donation' => array(),
				'campaign' => array(),
			);

			// Extract individual variables from args array.
			$activity_filter_types = $args['activity_filter_types'] ?? array();
			$activity_action_types = $args['activity_action_types'] ?? array();
			$campaign_id           = $args['campaign_id'] ?? 0;
			$category_id           = $args['category_id'] ?? 0;
			$start_date            = $args['start_date'] ?? gmdate( 'Y/m/d', strtotime( '-7 days' ) );
			$end_date              = $args['end_date'] ?? gmdate( 'Y/m/d' );
			$limit                 = $args['limit'] ?? 0;
			$offset                = $args['offset'] ?? 0;
			$sort_by               = $args['sort_by'] ?? 'date_to_sort';
			$order                 = $args['order'] ?? 'DESC';

			if ( ! empty( $activity_filter_types ) ) :

				foreach ( $activity_filter_types as $activity_filter_type ) :

					switch ( sanitize_title( $activity_filter_type ) ) {
						case 'donations-made':
							$db_types               = $db_types + array( 'donation' );
							$db_actions['donation'] = $db_actions['donation']
														+ array(
															'primary_action'   => 'charitable-completed',
															'secondary_action' => false,
														)
														+ array(
															'primary_action'   => 'charitable-pending',
															'secondary_action' => false,
														)
														+ array(
															'primary_action'   => 'charitable-failed',
															'secondary_action' => false,
														)
														+ array(
															'primary_action'   => 'charitable-refunded',
															'secondary_action' => false,
														);
							break;

						case 'donations-made-paid':
							$db_types               = $db_types + array( 'donation' );
							$db_actions['donation'] = $db_actions['donation']
														+ array(
															'primary_action'   => 'charitable-completed',
															'secondary_action' => 'add_donation',
														);
							break;

						case 'refunds':
							$db_types               = $db_types + array( 'donation' );
							$db_actions['donation'] = $db_actions['donation']
														+ array(
															'primary_action'   => 'charitable-refunded',
															'secondary_action' => false,
														);
							break;

						case 'campaigns-created':
							$db_types                 = $db_types + array( 'campaign' );
							$db_actions['campaign'][] = array(
								'primary_action'   => 'update',
								'secondary_action' => 'publish',
							);
							break;

						default:
							break;
					}

				endforeach;

			endif;

			$activities_data = $this->get_activity_db_data( $db_types, $db_actions, $args );

			// Merge results arrays (donation, campaign, etc.).
			$this->activities = $this->merge_activities( $activities_data );

			// Sort the results based on 'sort' and 'order' args in $args.
			$this->activities = $this->sort_activities( $this->activities, $args );

			// Filter for plugins to add/remove activities, sort.
			$this->activities = apply_filters( 'charitable_reports_activity_activities', $this->activities, $args );

			return $this->activities;
		}

		/**
		 * Generate sql for getting campaign activity.
		 *
		 * @since 1.8.1
		 *
		 * @param array $args The arguments for the sql.
		 * @param array $db_actions The actions used to assemble the sql.
		 *
		 * @return string
		 */
		public function get_activity_campaign_sql( $args, $db_actions = array() ) {

			global $wpdb;

			// Define the custom database table names and shortcuts.
			$activity_db_tables = $this->activity_db_tables;
			$table_names        = array();
			$table_shortcuts    = array();
			foreach ( $activity_db_tables as $activity_db_table ) {
				$table_shortcuts[ $activity_db_table ] = $activity_db_table . 's_a';
				$table_names[ $activity_db_table ]     = $wpdb->prefix . 'charitable_' . $activity_db_table . '_activities ' . $table_shortcuts[ $activity_db_table ];
			}

			// 1. build the WHERE statement for mysql based on the passed in values.
			$where_sql   = array( '1=1' ); // init the where.
			$where_sql[] = ( $args['campaign_id'] > 0 ) ? $table_shortcuts['campaign'] . '.campaign_id = ' . $args['campaign_id'] : false;
			$where_sql[] = "{$table_shortcuts['campaign']}.primary_action != ''";

			if ( ! empty( $db_actions ) ) {
				foreach ( $db_actions as $actions ) {
					$where_sql[] = "{$table_shortcuts['campaign']}.primary_action = '" . $actions['primary_action'] . "' AND {$table_shortcuts['campaign']}.secondary_action = '" . $actions['secondary_action'] . "'";
				}
			}

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			// 2. build LEFT JOIN
			$left_join               = array();
			$left_join['campaign'][] = $wpdb->prefix . 'posts p ON p.ID = ' . $table_shortcuts['campaign'] . '.campaign_id';
			$left_join['campaign']   = array_filter( $left_join['campaign'] ); // remove all empty values from the array.
			$left_join_campaign_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join['campaign'] );

			$start_date = $args['start_date'] ?? '';
			$end_date   = $args['end_date'] ?? '';

			$sql = "SELECT {$table_shortcuts['campaign']}.activity_id,
				{$table_shortcuts['campaign']}.campaign_id,
				null AS donor_id,
				{$table_shortcuts['campaign']}.type,
				{$table_shortcuts['campaign']}.primary_action,
				{$table_shortcuts['campaign']}.secondary_action,
				null AS amount,
				{$table_shortcuts['campaign']}.created_by,
				{$table_shortcuts['campaign']}.date_recorded,
				{$table_shortcuts['campaign']}.date_recorded AS date_to_sort,
				p.post_date AS campaign_date
				FROM {$table_names['campaign']} {$left_join_campaign_args} WHERE $where_args AND p.post_date >= %s AND p.post_date <= %s";

			return array(
				'sql'        => $sql,
				'start_date' => $start_date,
				'end_date'   => $end_date,
			);
		}

		/**
		 * Generate sql for getting donation activity.
		 *
		 * @since 1.8.1
		 *
		 * @param array $args The arguments for the sql.
		 *
		 * @return string
		 */
		public function get_activity_donation_sql( $args = array() ) {

			global $wpdb;

			if ( empty( $args ) ) {
				return array(
					'sql'        => false,
					'start_date' => false,
					'end_date'   => false,
				);
			}

			// Define the custom database table names and shortcuts.
			$activity_db_tables = $this->activity_db_tables;
			$table_names        = array();
			$table_shortcuts    = array();
			foreach ( $activity_db_tables as $activity_db_table ) {
				$table_shortcuts[ $activity_db_table ] = $activity_db_table . 's_a';
				$table_names[ $activity_db_table ]     = $wpdb->prefix . 'charitable_' . $activity_db_table . '_activities ' . $table_shortcuts[ $activity_db_table ];
			}

			// 1. build the WHERE statement for mysql based on the passed in values.
			$where_sql   = array( '1=1' ); // init the where.
			$where_sql[] = ( $args['campaign_id'] > 0 ) ? $table_shortcuts['donation'] . '.campaign_id = ' . $args['campaign_id'] : false;
			$where_sql[] = "{$table_shortcuts['donation']}.primary_action != ''";
			if ( ! empty( $args['activity_filter_types'] ) ) :
				foreach ( $args['activity_filter_types'] as $activity_filter_type ) :
					if ( 'refunds' === $activity_filter_type ) {
						$where_sql[] = "{$table_shortcuts['donation']}.primary_action = 'charitable-refunded'";
					}
					if ( 'donations-made-paid' === $activity_filter_type ) {
						$where_sql[] = "{$table_shortcuts['donation']}.primary_action = 'charitable-completed'";
					}
				endforeach;
			endif;

			// remove all empty values from the array.
			$where_sql  = array_filter( $where_sql );
			$where_args = implode( ' AND ', $where_sql );

			// 2. build LEFT JOIN
			$left_join               = array();
			$left_join['donation'][] = $wpdb->prefix . 'posts p ON p.ID = ' . $table_shortcuts['donation'] . '.donation_id';
			$left_join['donation']   = array_filter( $left_join['donation'] ); // remove all empty values from the array.
			$left_join_donation_args = 'LEFT JOIN ' . implode( ' LEFT JOIN ', $left_join['donation'] );

			$start_date = $args['start_date'] ?? '';
			$end_date   = $args['end_date'] ?? '';

				$sql = "SELECT {$table_shortcuts['donation']}.activity_id,
								{$table_shortcuts['donation']}.campaign_id,
								{$table_shortcuts['donation']}.donation_id,
								{$table_shortcuts['donation']}.donor_id,
								{$table_shortcuts['donation']}.type,
								{$table_shortcuts['donation']}.primary_action,
								{$table_shortcuts['donation']}.secondary_action,
								{$table_shortcuts['donation']}.amount,
								{$table_shortcuts['donation']}.created_by,
								{$table_shortcuts['donation']}.date_recorded,
								{$table_shortcuts['donation']}.date_recorded AS date_to_sort,
								p.post_date AS donation_date
								FROM {$table_names['donation']} {$left_join_donation_args} WHERE $where_args AND {$table_shortcuts['donation']}.date_recorded >= %s AND {$table_shortcuts['donation']}.date_recorded <= %s";

			return array(
				'sql'        => $sql,
				'start_date' => $start_date,
				'end_date'   => $end_date,
			);
		}

		/**
		 * Get "raw" data for the activity report based on the types, actions, and args passed in.
		 *
		 * @since 1.8.1
		 *
		 * @param array $db_types The types of activity information you want. Accepts '' (all), 'donations-made', 'donations-made-paid', 'refunds', 'campaigns-created', 'user-comments'. Default: array().
		 * @param array $db_actions The actions used to assemble the sql.
		 * @param array $args The arguments for the sql.
		 *
		 * @return array
		 */
		public function get_activity_db_data( $db_types = array(), $db_actions = array(), $args = array() ) {

			global $wpdb;

			$campaign_results = array();
			$donation_results = array();

			$activity_db_tables = (array) $this->activity_db_tables;
			$sql_results        = array();

			if ( empty( $db_types ) ) {

				$campaign_sql     = (array) $this->get_activity_campaign_sql( $args );
				$campaign_results = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						$campaign_sql['sql'], // phpcs:ignore
						$campaign_sql['start_date'],
						$campaign_sql['end_date']
					)
				);

				$donation_sql     = (array) $this->get_activity_donation_sql( $args );
				$donation_results = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						$donation_sql['sql'], // phpcs:ignore
						$donation_sql['start_date'],
						$donation_sql['end_date']
					)
				);

				$sql_results = array(
					'campaign_activities' => $campaign_results,
					'donation_activities' => $donation_results,
				);

			} else {

				foreach ( $db_types as $db_type ) {

					switch ( $db_type ) {
						case 'campaign':
							$campaign_sql     = $this->get_activity_campaign_sql( $args, $db_actions['campaign'] );
							$campaign_results = $wpdb->get_results( // phpcs:ignore
								$wpdb->prepare(
									$campaign_sql['sql'], // phpcs:ignore
									$campaign_sql['start_date'],
									$campaign_sql['end_date']
								)
							);
							break;
						case 'donation':
							$donation_sql     = $this->get_activity_donation_sql( $args, $db_actions['donation'] );
							$donation_results = $wpdb->get_results( // phpcs:ignore
								$wpdb->prepare(
									$donation_sql['sql'], // phpcs:ignore
									$donation_sql['start_date'],
									$donation_sql['end_date']
								)
							);
							break;
						default:
							// code...
							break;
					}

				}

				$sql_results = array(
					'campaign_activities' => $campaign_results,
					'donation_activities' => $donation_results,
				);

			}

			return $sql_results;
		}

		/**
		 * Merge activities.
		 *
		 * @since 1.8.1
		 *
		 * @param array $activities The activities to merge. This is an array of arrays. Each array is a different type of activity.
		 *
		 * @return array
		 */
		public function merge_activities( $activities = array() ) {

			// merge the two associative arrays keeping the keys.
			$activities = array_merge_recursive( $activities['campaign_activities'], $activities['donation_activities'] );

			return $activities;
		}

		/**
		 * Sort activities.
		 * Mostly a placeholder until more sorting functions are added.
		 *
		 * @since 1.8.1
		 *
		 * @param array $activities The activities to sort.
		 * @param array $args The arguments which contain potentially a 'sort_by' and 'order'.
		 *
		 * @return array
		 */
		public function sort_activities( $activities = array(), $args = array() ) {

			if ( empty( $activities ) ) {
				return $activities;
			}

			$activities = $this->sort_activities_by_datetime( $activities, $args );

			return $this->sort_activities_by_datetime( $activities, $args );
		}

		/**
		 * Sort activities by mysql date time stamp (or date in general).
		 *
		 * @since 1.8.1
		 *
		 * @param array $activities The activities to sort.
		 * @param array $args The arguments which contain potentially a 'sort_by' and 'order'.
		 *
		 * @return array
		 */
		public function sort_activities_by_datetime( $activities = array(), $args = array() ) {

			if ( empty( $activities ) ) {
				return $activities;
			}

			$sort_by = ! empty( $args['sort_by'] ) ? $args['sort_by'] : 'date_to_sort';
			$order   = ! empty( $args['order'] ) ? $args['order'] : 'DESC';

			usort(
				$activities,
				function ( $a, $b ) use ( $sort_by, $order ) {
					if ( $order === 'ASC' ) {
						return strtotime( $a->$sort_by ) - strtotime( $b->$sort_by );
					} else {
						return strtotime( $b->$sort_by ) - strtotime( $a->$sort_by );
					}
				}
			);

			return $activities;
		}

		/**
		 * Get the activity report data.
		 *
		 * @since 1.8.1
		 *
		 * @param array $activities The activities to get the report data for.
		 *
		 * @return array
		 */
		public function get_activity_report_data( $activities = array() ) {

			$report_activities = array();

			// if $activities isn't an array, convert it to an array.
			$activities = (array) $activities;

			if ( ! is_array( $activities ) || empty( $activities ) ) {
				return;
			}

			foreach ( $activities as $index => $activity ) {

				if ( empty( $activity->sample ) && ( empty( $activity->activity_id ) || 0 === $activity->activity_id || empty( $activity->primary_action ) ) ) {
					continue;
				}

				// Make sure certain fields are set.
				$activity->secondary_action = ! empty( $activity->secondary_action ) ? $activity->secondary_action : false;

				if ( $activity->type === 'donation' && $activity->primary_action === 'charitable-completed' && ! empty( $activity->donation_date ) ) {
					// paid, but need to record the date of donation not the time it was added in the stream (especially for manual donations).
					$donation_recorded = ( $activity->donation_date );
				} else {
					$donation_recorded = ( $activity->date_recorded );
				}

				$donor        = false;
				$wp_user_name = false;

				if ( ! empty( $activity->donor_id ) ) {
					$donor = new Charitable_Donor( intval( $activity->donor_id ) );
				}
				if ( ! empty( $activity->created_by ) ) {
					$wp_user = new WP_User( intval( $activity->created_by ) );
					if ( $wp_user ) {
						$wp_user_name = $wp_user->user_login;
					}
				}

				// For campaigns, the item_id is the campaign id, not the donation id (or anything else).
				$campaign_id = ( $activity->type === 'campaign' || $activity->type === 'donation' ) && ! empty( $activity->campaign_id ) ? $activity->campaign_id : false;

				$report_activities[ $index ]                   = new stdClass();
				$report_activities[ $index ]->type             = $activity->type;
				$report_activities[ $index ]->amount           = $activity->amount;
				$report_activities[ $index ]->date_recorded    = $donation_recorded;
				$report_activities[ $index ]->date_to_sort     = ! empty( $activity->date_to_sort ) ? esc_html( $activity->date_to_sort ) : false;
				$report_activities[ $index ]->campaign_id      = ! empty( $campaign_id ) ? intval( $campaign_id ) : false;
				$report_activities[ $index ]->campaign_title   = ! empty( $campaign_id ) ? get_the_title( $campaign_id ) : false;
				$report_activities[ $index ]->created_by       = ! empty( $activity->created_by ) ? intval( $activity->created_by ) : false;
				$report_activities[ $index ]->created_by_name  = ! empty( $wp_user_name ) ? $wp_user_name : false;
				$report_activities[ $index ]->donor_id         = ! empty( $activity->donor_id ) ? intval( $activity->donor_id ) : false;
				$report_activities[ $index ]->donor_name       = ! empty( $donor ) ? $donor->get_name() : false;
				$report_activities[ $index ]->status           = $this->get_status_slug( $activity->type, $activity->primary_action, $activity->secondary_action );
				$report_activities[ $index ]->status_label     = $this->get_status_label( $activity->type, $activity->primary_action, $activity->secondary_action );
				$report_activities[ $index ]->action_label     = $this->get_action_label( $activity->type, $activity->primary_action, $activity->secondary_action );
				$report_activities[ $index ]->icon             = $this->get_icon( $activity->type, $activity->primary_action, $activity->secondary_action );
				$report_activities[ $index ]->item_id          = ! empty( $activity->donation_id ) ? intval( $activity->donation_id ) : false;
				$report_activities[ $index ]->item_id_prefix   = '#';
				$report_activities[ $index ]->primary_action   = ! empty( $activity->primary_action ) ? esc_attr( $activity->primary_action ) : false;
				$report_activities[ $index ]->secondary_action = ! empty( $activity->secondary_action ) ? esc_attr( $activity->secondary_action ) : false;

			}

			return $report_activities;
		}

		/**
		 * Get the status "slug" from a string.
		 *
		 * @since 1.8.1
		 *
		 * @param string $activity_type The type of activity. Accepts 'donation', 'campaign', etc.
		 * @param string $primary_action The primary action. Accepts 'charitable-completed', 'charitable-refunded', etc.
		 * @param string $secondary_action The secondary action. Accepts 'add_donation', 'make_donation', etc.
		 *
		 * @return string
		 */
		public function get_status_slug( $activity_type = 'donation', $primary_action = false, $secondary_action = false ) {

			// if the primary action is charitable-completed, charitable-refunded, etc. then the slug is the status.
			$primary_action_slug = strpos( $primary_action, 'charitable-', 0 ) !== false ? str_replace( 'charitable-', '', strtolower( $primary_action ) ) : $primary_action;

			$status_slug = esc_attr( $primary_action_slug );

			switch ( $activity_type ) {
				case 'donation':
					switch ( $status_slug ) {
						case 'completed':
							$status_slug = 'paid';
							break;

						default:
							$status_slug = esc_attr( $primary_action_slug );
							break;
					}
					break;
				case 'campaign':
					switch ( $status_slug ) {
						case 'update':
							$status_slug = 'published';
							break;

						default:
							$status_slug = false;
							break;
					}
					break;
				default:
					$status_slug = esc_attr( $primary_action_slug );
					break;
			}

			return apply_filters( 'charitable_activities_status_slug', $status_slug, $activity_type, $primary_action, $secondary_action );
		}

		/**
		 * Get the status label from a status string.
		 *
		 * @since 1.8.1
		 *
		 * @param string $activity_type The type of activity. Accepts 'donation', 'campaign', etc.
		 * @param string $primary_action The primary action. Accepts 'charitable-completed', 'charitable-refunded', etc.
		 * @param string $secondary_action The secondary action. Accepts 'add_donation', 'make_donation', etc.
		 *
		 * @return string
		 */
		public function get_status_label( $activity_type = 'donation', $primary_action = false, $secondary_action = false ) {

			// if the primary action is charitable-completed, charitable-refunded, etc. then the slug is the status.
			$primary_action_slug = strpos( $primary_action, 'charitable-', 0 ) !== false ? str_replace( 'charitable-', '', strtolower( $primary_action ) ) : $primary_action;

			switch ( $activity_type ) {
				case 'donation':
					switch ( $primary_action_slug ) {
						case 'completed':
							$status_label = 'Paid';
							break;

						default:
							$status_label = ucwords( $primary_action_slug );
							break;
					}
					break;
				case 'campaign':
					switch ( $primary_action_slug ) {
						case 'update':
							$status_label = 'Published';
							break;

						default:
							$status_label = false;
							break;
					}
					break;
				default:
					$status_label = ucwords( 'Unknown' );
					break;
			}

			return apply_filters( 'charitable_activities_status_label', $status_label, $activity_type, $primary_action, $secondary_action );
		}

		/**
		 * Get the action label from a status string.
		 *
		 * @since 1.8.1
		 *
		 * @param string $activity_type The type of activity. Accepts 'donation', 'campaign', etc.
		 * @param string $primary_action The primary action. Accepts 'charitable-completed', 'charitable-refunded', etc.
		 * @param string $secondary_action The secondary action. Accepts 'add_donation', 'make_donation', etc.
		 *
		 * @return string
		 */
		public function get_action_label( $activity_type = 'donation', $primary_action = false, $secondary_action = false ) {

			// if the primary action is charitable-completed, charitable-refunded, etc. then the slug is the status.
			$primary_action_slug = strpos( $primary_action, 'charitable-', 0 ) !== false ? str_replace( 'charitable-', '', strtolower( $primary_action ) ) : $primary_action;

			$action_label = '';

			switch ( $activity_type ) {
				case 'donation':
					if ( in_array( $secondary_action, array( 'add_donation', 'make_donation' ), true ) ) {
						$action_label = 'Donation %id Made';
					} elseif ( in_array( $secondary_action, array( 'update_donation', 'change_status' ), true ) && 'refunded' === $primary_action_slug ) {
						$action_label = 'Refund %id';
					} elseif ( in_array( $secondary_action, array( 'update_donation', 'change_status' ), true ) ) {
						$action_label = 'Donation %id Updated';
					}
					break;
				case 'campaign':
					if ( in_array( $primary_action, array( 'update' ), true ) && in_array( $secondary_action, array( 'publish' ), true ) ) {
						$action_label = 'Campaign %id';
					}
					break;
				default:
					$action_label = ucwords( 'Unknown' );
					break;
			}

			return apply_filters( 'charitable_activities_action_label', $action_label, $activity_type, $primary_action, $secondary_action );
		}


		/**
		 * Get the icon from a status string.
		 *
		 * @since 1.8.1
		 *
		 * @param string $activity_type The type of activity. Accepts 'donation', 'campaign', etc.
		 * @param string $primary_action The primary action. Accepts 'charitable-completed', 'charitable-refunded', etc.
		 * @param string $secondary_action The secondary action. Accepts 'add_donation', 'make_donation', etc.
		 *
		 * @return string
		 */
		public function get_icon( $activity_type = 'donation', $primary_action = false, $secondary_action = false ) {

			// Set the default.
			$icon = $activity_type;

			$status_slug = $this->get_status_slug( $activity_type, $primary_action, $secondary_action );

			switch ( $activity_type ) {
				case 'donation':
					if ( in_array( $secondary_action, array( 'add_donation', 'make_donation' ), true ) ) { // phpcs:ignore

					} elseif ( in_array( $secondary_action, array( 'update_donation', 'change_status' ), true ) && 'refunded' === $status_slug ) {
						$icon = 'refund';
					} elseif ( in_array( $secondary_action, array( 'update_donation', 'change_status' ), true ) ) { // phpcs:ignore

					}
					break;
				default:
					break;
			}

			return apply_filters( 'charitable_activities_action_icon', $icon, $activity_type, $primary_action, $secondary_action );
		}


		/**
		 * Insert a new row
		 *
		 * @since  1.8.1
		 * @since  1.8.1,3 Added check.
		 *
		 * @param  array  $data       The data to insert.
		 * @param  string $table_name The name of the table to insert into.
		 * @param  string $type       The type of activity.
		 *
		 * @return int
		 */
		public function insert( $data, $table_name = '', $type = 'donation' ) {
			global $wpdb;

			if ( empty( $data ) || '' === $table_name ) {
				return 0;
			}

			/* Set default values */
			$data = wp_parse_args( $data, $this->get_column_defaults() );

			/**
			 * Filter the activity data.
			 *
			 * @since 1.8.1
			 *
			 * @param array $data      Core data.
			 * @param Charitable_Admin_Activities $this This object.
			 */
			$data = apply_filters( 'charitable_activity_admin_insert_data', $data, $this );

			do_action( 'charitable_activity_pre_insert_' . $type, $data );

			/* Initialise column format array */
			$column_formats = $this->get_columns();

			/* Force fields to lower case */
			$data = array_change_key_case( $data );

			/* White list columns */
			$data = array_intersect_key( $data, $column_formats );

			/* Reorder $column_formats to match the order of columns given in $data */
			$data_keys      = array_keys( $data );
			$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

			$inserted = $wpdb->insert( $table_name, $data, $column_formats ); // phpcs:ignore

			/* If the insert failed, return 0 */
			if ( false === $inserted ) {
				return 0;
			}

			do_action( 'charitable_activity_post_insert_' . $type, $wpdb->insert_id, $data );

			return $wpdb->insert_id;
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_columns() {
			$columns = array(
				'activity_id'      => '%d',
				'donation_id'      => '%d',
				'campaign_id'      => '%d',
				'donor_id'         => '%d',
				'type'             => '%s',
				'primary_action'   => '%s',
				'secondary_action' => '%s',
				'amount'           => '%s',
				'created_by'       => '%d',
				'date_recorded'    => '%s',
			);

			return $columns;
		}

		/**
		 * Default column values.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_column_defaults() {
			$defaults = array(
				'created_by'    => 0,
				'date_recorded' => gmdate( 'Y-m-d H:i:s' ),
			);

			return $defaults;
		}

		/**
		 * Return sample data for the example tables.
		 *
		 * @since  1.8.1
		 *
		 * @param  string $report_type The type of report.
		 * @param  string $data_type   The type of data to return. Either 'object' or 'array'.
		 * @param  int    $number_of_records The number of records to return.
		 *
		 * @return array
		 */
		public function get_activity_data_sample( $report_type = 'donation', $data_type = 'object', $number_of_records = 10 ) {

			if ( false === $report_type ) {
				$report_type = 'donation';
			}

			if ( $data_type === 'object' ) {
				$data = new stdClass();
			} else {
				$data = array();
			}

			for ( $x = 0; $x < $number_of_records; $x++ ) {

				$random_price = wp_rand( 50, 1000 ) / 10;
				$random_date  = gmdate( 'Y-m-d H:i:s', wp_rand( strtotime( '-37 days' ), strtotime( 'now' ) ) );

				if ( $data_type === 'object' ) {

					$data->{$x} = new stdClass();

					$data->{$x}->activity_id    = 0;
					$data->{$x}->donation_id    = 0;
					$data->{$x}->campaign_id    = 0;
					$data->{$x}->donor_id       = 0;
					$data->{$x}->type           = 'donation';
					$data->{$x}->primary_action = 'charitable-completed';
					$data->{$x}->amount         = $random_price;
					$data->{$x}->created_by     = 1;
					$data->{$x}->date_recorded  = $random_date;
					$data->{$x}->donation_date  = $random_date;
					$data->{$x}->date_to_sort   = $random_date;
					$data->{$x}->sample         = true;

				} else {

					$data[] = array(

						'activity_id'    => 0,
						'donation_id'    => 0,
						'campaign_id'    => 0,
						'donor_id'       => 0,
						'type'           => 'donation',
						'primary_action' => 'charitable-completed',
						'amount'         => $random_price,
						'created_by'     => 1,
						'date_recorded'  => $random_date,
						'donation_date'  => $random_date,
						'date_to_sort'   => $random_date,
						'sample'         => true,

					);
				}
			}

			// sort the sample data.
			if ( $data_type === 'object' && ! empty( $data ) ) {

				// convert object having objects to array of objects.
				$_data = (array) $data;

				function cmp( $a, $b ) { // phpcs:ignore
					return strcmp( $a->date_to_sort, $b->date_to_sort );
				}

				usort( $_data, 'cmp' );

				// convert array of objects back to object.
				$data = (object) $_data;

			}

			$data = apply_filters(
				'charitable_report_activity_sample_data',
				$data,
				$report_type,
				$data_type,
				$number_of_records
			);

			return $data;
		}

		/**
		 * Log a donation form error.
		 *
		 * @since  1.8.9.2
		 *
		 * @param  string $error_type The type of error (validation_failure, ajax_failure, security_failure, email_failure).
		 * @param  string $error_details Specific error details or code.
		 * @param  array  $context Additional context data for the error.
		 *
		 * @return int|false The activity ID on success, false on failure.
		 */
		public function log_form_error( $error_type, $error_details, $context = array() ) {
			global $wpdb;

			// Check if error logging is enabled
			if ( ! apply_filters( 'charitable_form_error_logging_enabled', true ) ) {
				return false;
			}

			// Prepare the data for insertion
			$data = array(
				'donation_id'      => isset( $context['donation_id'] ) ? intval( $context['donation_id'] ) : null,
				'campaign_id'      => isset( $context['campaign_id'] ) ? intval( $context['campaign_id'] ) : null,
				'donor_id'         => isset( $context['donor_id'] ) ? intval( $context['donor_id'] ) : null,
				'type'             => 'form_error',
				'primary_action'   => sanitize_text_field( $error_type ),
				'secondary_action' => sanitize_text_field( $error_details ),
				'amount'           => isset( $context['amount'] ) ? floatval( $context['amount'] ) : null,
				'created_by'       => get_current_user_id(),
				'date_recorded'    => gmdate( 'Y-m-d H:i:s' ),
			);

			// Get table name
			$table_name = $wpdb->prefix . 'charitable_donation_activities';

			// Insert the error log
			$activity_id = $this->insert( $data, $table_name, 'form_error' );

			// Store additional context data as meta if needed
			if ( $activity_id && ! empty( $context ) ) {
				$meta_data = $this->prepare_error_meta_data( $context );
				if ( ! empty( $meta_data ) ) {
					// Store meta data using WordPress options temporarily
					// In a full implementation, you might want a dedicated meta table
					update_option( 'charitable_form_error_meta_' . $activity_id, $meta_data, false );
				}
			}

			/**
			 * Action fired after a form error is logged.
			 *
			 * @since 1.8.9.2
			 *
			 * @param int    $activity_id The activity ID.
			 * @param string $error_type  The error type.
			 * @param string $error_details The error details.
			 * @param array  $context     The error context.
			 */
			do_action( 'charitable_form_error_logged', $activity_id, $error_type, $error_details, $context );

			return $activity_id;
		}

		/**
		 * Prepare error meta data for storage.
		 *
		 * @since  1.8.9.2
		 * @since  1.8.9.8 Handle array recipients to prevent fatal TypeError in sanitize_email().
		 *
		 * @param  array $context The error context.
		 *
		 * @return array The prepared meta data.
		 */
		private function prepare_error_meta_data( $context ) {
			$meta_data = array();

			// Store essential data only for privacy
			if ( isset( $context['url'] ) ) {
				$meta_data['url'] = esc_url_raw( $context['url'] );
			}

			if ( isset( $context['user_agent'] ) ) {
				$meta_data['user_agent'] = sanitize_text_field( $context['user_agent'] );
			}

			if ( isset( $context['ip_address'] ) ) {
				$meta_data['ip_address'] = sanitize_text_field( $context['ip_address'] );
			}

			if ( isset( $context['gateway'] ) ) {
				$meta_data['gateway'] = sanitize_text_field( $context['gateway'] );
			}

			// Store error message for email and other failures — critical for debugging.
			if ( isset( $context['error_message'] ) ) {
				$meta_data['error_message'] = sanitize_text_field( substr( $context['error_message'], 0, 500 ) );
			}

			// Store email class for email failures.
			if ( isset( $context['email_class'] ) ) {
				$meta_data['email_class'] = sanitize_text_field( $context['email_class'] );
			}

			// Store recipient for email failures (helps debug delivery issues).
			if ( isset( $context['recipient'] ) ) {
				$recipient = $context['recipient'];
				if ( is_array( $recipient ) ) {
					$meta_data['recipient'] = implode( ', ', array_map( 'sanitize_email', $recipient ) );
				} else {
					$meta_data['recipient'] = sanitize_email( $recipient );
				}
			}

			// Apply privacy filter to allow customization
			$meta_data = apply_filters( 'charitable_form_error_log_data', $meta_data, $context );

			return $meta_data;
		}

		/**
		 * Get recent form errors for display.
		 *
		 * @since  1.8.9.2
		 *
		 * @param  int $limit The number of errors to retrieve.
		 *
		 * @return array Array of error records.
		 */
		public function get_recent_form_errors( $limit = 50 ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'charitable_donation_activities';

			$sql = $wpdb->prepare(
				"SELECT * FROM {$table_name}
				WHERE type = 'form_error'
				ORDER BY date_recorded DESC
				LIMIT %d",
				$limit
			);

			$errors = $wpdb->get_results( $sql ); // phpcs:ignore

			// Enhance errors with meta data
			foreach ( $errors as &$error ) {
				$meta_data = get_option( 'charitable_form_error_meta_' . $error->activity_id, array() );
				$error->meta_data = $meta_data;
			}

			return $errors;
		}

		/**
		 * Get form error statistics.
		 *
		 * @since  1.8.9.2
		 *
		 * @param  int $days Number of days to look back.
		 *
		 * @return array Statistics array.
		 */
		public function get_form_error_stats( $days = 7 ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'charitable_donation_activities';
			$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			$sql = $wpdb->prepare(
				"SELECT
					primary_action,
					secondary_action,
					COUNT(*) as error_count,
					MAX(date_recorded) as last_occurrence
				FROM {$table_name}
				WHERE type = 'form_error'
				AND date_recorded >= %s
				GROUP BY primary_action, secondary_action
				ORDER BY error_count DESC",
				$date_threshold
			);

			return $wpdb->get_results( $sql ); // phpcs:ignore
		}

		/**
		 * Clean up old form error logs.
		 *
		 * @since  1.8.9.2
		 *
		 * @param  int $days Number of days to retain.
		 *
		 * @return int Number of records deleted.
		 */
		public function cleanup_old_form_errors( $days = 7 ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'charitable_donation_activities';
			$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

			// Get IDs of records to delete for meta cleanup
			$sql = $wpdb->prepare(
				"SELECT activity_id FROM {$table_name}
				WHERE type = 'form_error'
				AND date_recorded < %s",
				$date_threshold
			);

			$old_error_ids = $wpdb->get_col( $sql ); // phpcs:ignore

			// Delete meta data
			foreach ( $old_error_ids as $activity_id ) {
				delete_option( 'charitable_form_error_meta_' . $activity_id );
			}

			// Delete the activity records
			$sql = $wpdb->prepare(
				"DELETE FROM {$table_name}
				WHERE type = 'form_error'
				AND date_recorded < %s",
				$date_threshold
			);

			return $wpdb->query( $sql ); // phpcs:ignore
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Admin_Activities
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
