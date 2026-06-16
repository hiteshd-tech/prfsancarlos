<?php
/**
 * Charitable Campaign Activities DB class.
 *
 * @package     Charitable/Classes/Charitable_Campaign_Activities_DB
 * @version     1.8.1
 * @author      David Bisset
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Activities_DB' ) ) :

	/**
	 * Charitable_Campaign_Activities_DB
	 *
	 * @since   1.8.1
	 */
	class Charitable_Campaign_Activities_DB extends Charitable_DB {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Campaign_Activities_DB|null
		 */
		private static $instance = null;

		/**
		 * The version of our database table
		 *
		 * @since   1.8.1
		 * @var     string
		 */
		public $version = '1.8.1';

		/**
		 * The name of the primary column
		 *
		 * @since   1.8.1
		 * @var     string
		 */
		public $primary_key = 'activity_id';

		/**
		 * The name of the table.
		 *
		 * @since   1.8.1
		 * @var     string
		 */
		public $table_name = 'charitable_campaign_activities';

		/**
		 * Set up the database table name.
		 *
		 * @since   1.8.1
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_campaign_activities';
		}

		/**
		 * Create the table.
		 *
		 * @global  $wpdb
		 * @since   1.8.1
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                `activity_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `campaign_id` int(11) unsigned DEFAULT NULL,
                `type` varchar(25) DEFAULT NULL,
                `primary_action` varchar(25) DEFAULT NULL,
                `secondary_action` varchar(25) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `date_recorded` datetime DEFAULT NULL,
                PRIMARY KEY (`activity_id`)
              ) $charset_collate;";

			$this->_create_table( $sql );
		}


		/**
		 * Checks if the table exists.
		 *
		 * @global  $wpdb
		 * @since   1.8.1
		 */
		public function table_exists() {
			global $wpdb;

			return $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name; // phpcs:ignore
		}

		/**
		 * Remove the table.
		 *
		 * @global  $wpdb
		 * @since   1.8.1
		 *
		 * @return void
		 */
		public function remove_table() {
			global $wpdb;

			$sql = "DROP TABLE IF EXISTS {$this->table_name};";

			$wpdb->query( $sql ); // phpcs:ignore
		}

		/**
		 * Wipes all data from the table.
		 *
		 * @global  $wpdb
		 * @since   1.8.1
		 *
		 * @return void
		 */
		public function clear_table() {
			global $wpdb;

			$sql = "TRUNCATE TABLE {$this->table_name};";

			$wpdb->query( $sql ); // phpcs:ignore
		}

		/**
		 * Whitelist of columns.
		 *
		 * @since   1.8.1
		 *
		 * @return  array
		 */
		public function get_columns() {
			return array(
				'activity_id'      => '%d',
				'campaign_id'      => '%d',
				'type'             => '%s',
				'primary_action'   => '%s',
				'secondary_action' => '%s',
				'created_by'       => '%d',
				'date_recorded'    => '%s',
			);
		}

		/**
		 * Default column values.
		 *
		 * @since   1.8.1
		 *
		 * @return  array
		 */
		public function get_column_defaults() {
			return array(
				'date_recorded' => gmdate( 'Y-m-d H:i:s' ),
			);
		}

		/**
		 * Add a new object. Currently a placeholder function for future use.
		 *
		 * @since   1.8.1
		 *
		 * @param   array  $data Data to insert.
		 * @param   string $type Optional. Type of data to insert. Default is empty string.
		 */
		public function insert( $data, $type = '' ) {
		}

		/**
		 * Update a object. Currently a placeholder function for future use.
		 *
		 * @since   1.8.1
		 *
		 * @param   int   $row_id The row ID.
		 * @param   array $data Optional.
		 * @param   array $where Optional.
		 */
		public function update( $row_id, $data = array(), $where = '' ) {
		}

		/**
		 * Delete a row identified by the primary key. Currently a placeholder function for future use.
		 *
		 * @since   1.8.1
		 *
		 * @param   int $row_id The row ID.
		 */
		public function delete( $row_id = 0 ) {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.1
		 *
		 * @return Charitable_Campaign_Activities_DB
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

endif;
