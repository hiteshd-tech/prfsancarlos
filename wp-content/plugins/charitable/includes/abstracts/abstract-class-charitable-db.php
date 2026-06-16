<?php
/**
 * Charitable DB base class.
 *
 * Credit: This is based on Easy Digital Downloads' EDD_DB class. Major props to Pippin Williamson.
 * https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/class-edd-db.php
 *
 * @package   Charitable/Classes/Charitable_DB
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.9.1
 */

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_DB' ) ) :

	/**
	 * Charitable_DB
	 *
	 * @abstract
	 * @since  1.0.0
	 */
	abstract class Charitable_DB {

		/**
		 * The name of our database table
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $table_name;

		/**
		 * The version of our database table
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $version;

		/**
		 * The name of the primary column
		 *
		 * @since 1.0.0
		 *
		 * @var   string
		 */
		public $primary_key;

		/**
		 * Get things started
		 *
		 * @since 1.0.0
		 */
		public function __construct() {}

		/**
		 * Whitelist of columns
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		abstract public function get_columns();

		/**
		 * Default column values
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		abstract public function get_column_defaults();

		/**
		 * Return the format for the given column.
		 *
		 * @since  1.0.0
		 * @version 1.8.9.1
		 *
		 * @param  string $column The column to get the format for.
		 * @return %s, %d or %f
		 */
		public function get_column_format( $column ) {
			$columns = $this->get_columns();
			$format  = isset( $columns[ $column ] ) ? $columns[ $column ] : false;

		// If the column isn't found, throw an exception.
		if ( false === $format ) {
			throw new Exception( sprintf( 'Invalid column passed %s', esc_html( $column ) ) );
		}

		// If the column format isn't valid, throw an exception.
		if ( ! in_array( $format, array( '%s', '%d', '%f' ) ) ) {
			throw new Exception( sprintf( 'Invalid column format for column %s. Format returned %s', esc_html( $column ), esc_html( $format ) ) );
		}

			return $format;
		}

		/**
		 * Validate that a column name exists in the table schema.
		 *
		 * @since  1.8.8.6
		 *
		 * @param  string $column The column name to validate.
		 * @return string The validated column name.
		 * @throws Exception If column is invalid.
		 */
		protected function validate_column_name( $column ) {
			$columns = $this->get_columns();
			if ( ! isset( $columns[ $column ] ) ) {
				throw new Exception( sprintf( 'Invalid column passed %s', esc_html( $column ) ) );
			}
			return $column;
		}

		/**
		 * Retrieve a row by the primary key
		 *
		 * @since  1.0.0
		 *
		 * @return object
		 */
		public function get( $row_id ) {
			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $this->table_name and $this->primary_key are class properties set at initialization and validated.
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $this->table_name WHERE $this->primary_key = %d LIMIT 1;",
					$row_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		/**
		 * Retrieve a row by a specific column / value
		 *
		 * @since  1.0.0
		 * @return  object
		 */
		public function get_by( $column, $row_id ) {
			global $wpdb;

			// Validate column name before use.
			$column = $this->validate_column_name( $column );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $column is validated above via validate_column_name(), $this->table_name is a class property set at initialization.
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $this->table_name WHERE $column = {$this->get_column_format($column)} LIMIT 1;",
					$row_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Retrieve a specific column's value by the primary key.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_column( $column, $row_id ) {
			global $wpdb;

			// Validate column name before use.
			$column = $this->validate_column_name( $column );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $column is validated above via validate_column_name(), $this->table_name and $this->primary_key are class properties set at initialization.
			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT $column FROM $this->table_name WHERE $this->primary_key = %d LIMIT 1;",
					$row_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Retrieve a specific column's value by the the specified column / value
		 *
		 * @since  1.0.0
		 *
		 * @param  string $column       The column with the value we are retrieving.
		 * @param  string $column_where The column that we will be querying by.
		 * @param  mixed  $column_value The value we are searching for in the column set by $column_where.
		 * @return mixed
		 */
		public function get_column_by( $column, $column_where, $column_value ) {
			global $wpdb;

			// Validate column names before use.
			$column       = $this->validate_column_name( $column );
			$column_where = $this->validate_column_name( $column_where );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $column and $column_where are validated above via validate_column_name(), $this->table_name is a class property set at initialization.
			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT $column FROM $this->table_name WHERE $column_where = {$this->get_column_format($column_where)} LIMIT 1;",
					$column_value
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Retrieve all values in a specific column by the specified column / value.
		 *
		 * @since  1.5.11
		 *
		 * @param  string $column       The column with the value we are retrieving.
		 * @param  string $column_where The column that we will be querying by.
		 * @param  mixed  $column_value The value we are searching for in the column set by $column_where.
		 * @return array
		 */
		public function get_column_all_by( $column, $column_where, $column_value ) {
			global $wpdb;

			// Validate column names before use.
			$column       = $this->validate_column_name( $column );
			$column_where = $this->validate_column_name( $column_where );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $column and $column_where are validated above via validate_column_name(), $this->table_name is a class property set at initialization.
			return $wpdb->get_col(
				$wpdb->prepare(
					"SELECT $column FROM $this->table_name WHERE $column_where = {$this->get_column_format($column_where)};",
					$column_value
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Count all rows.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function count_all() {
			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// Direct database queries are expected in this database abstraction class.
			// $this->table_name is a class property set at initialization.
			return $wpdb->get_var(
				"SELECT COUNT( * ) FROM $this->table_name;"
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Count all rows that certain criteria.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function count_by( $column, $column_value ) {
			global $wpdb;

			// Validate column name before use.
			$column = $this->validate_column_name( $column );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $column is validated above via validate_column_name(), $this->table_name is a class property set at initialization.
			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT( * ) FROM $this->table_name WHERE $column = {$this->get_column_format($column)};",
					$column_value
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Insert a new row
		 *
		 * @since   1.0.0
		 * @version 1.8.8.6
		 *
		 * @return int
		 */
		public function insert( $data, $type = '' ) {
			global $wpdb;

			/* Set default values */
			$data = wp_parse_args( $data, $this->get_column_defaults() );

			do_action( 'charitable_pre_insert_' . $type, $data );

			/* Initialise column format array */
			$column_formats = $this->get_columns();

			/* Force fields to lower case */
			$data = array_change_key_case( $data );

			/* White list columns */
			$data = array_intersect_key( $data, $column_formats );

			/* Reorder $column_formats to match the order of columns given in $data */
			$data_keys      = array_keys( $data );
			$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// Direct database queries are expected in this database abstraction class.
			// $this->table_name is a class property set at initialization, and $data/$column_formats are sanitized above.
			$inserted = $wpdb->insert( $this->table_name, $data, $column_formats );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			/* If the insert failed, return 0 */
			if ( false === $inserted ) {
				return 0;
			}

			do_action( 'charitable_post_insert_' . $type, $wpdb->insert_id, $data );

			return $wpdb->insert_id;
		}

		/**
		 * Update a row.
		 *
		 * @since  1.0.0
		 * @version 1.8.8.6
		 *
		 * @global WPDB $wpdb
		 * @param  int    $row_id The record to update.
		 * @param  array  $data   Data to update.
		 * @param  string $where  Column used in where argument.
		 * @return boolean
		 */
		public function update( $row_id, $data = array(), $where = '' ) {
			global $wpdb;

			// Row ID must be positive integer.
			$row_id = absint( $row_id );

			if ( empty( $row_id ) ) {
				return false;
			}

			if ( empty( $where ) ) {
				$where = $this->primary_key;
			}

			/* Initialise column format array. */
			$column_formats = $this->get_columns();

			/* Force fields to lower case */
			$data = array_change_key_case( $data );

			/* White list columns. */
			$data = array_intersect_key( $data, $column_formats );

			/* Reorder $column_formats to match the order of columns given in $data. */
			$data_keys      = array_keys( $data );
			$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			// Direct database queries are expected in this database abstraction class.
			// $this->table_name is a class property set at initialization, $where is validated (defaults to primary_key or validated column),
			// $row_id is sanitized via absint(), and $data/$column_formats are sanitized above.
			$updated = $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( false === $updated ) {
				return false;
			}

			return true;
		}

		/**
		 * Delete a row identified by the primary key
		 *
		 * @since  1.0.0
		 *
		 * @param  int $row_id
		 * @return boolean
		 */
		public function delete( $row_id = 0 ) {
			// Row ID must be positive integer
			$row_id = absint( $row_id );

			if ( empty( $row_id ) ) {
				return false;
			}

			return $this->delete_by( $this->primary_key, $row_id );
		}

		/**
		 * Delete a row identified by a specific column.
		 *
		 * @since  1.2.0
		 *
		 * @global WPDB $wpdb
		 * @param  string $column The column to search for the row ID.
		 * @param  mixed  $row_id The row ID to be removed.
		 * @return boolean
		 */
		public function delete_by( $column, $row_id = 0 ) {
			global $wpdb;

			// Validate column name before use.
			$column = $this->validate_column_name( $column );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
			// Direct database queries are expected in this database abstraction class.
			// $column is validated above via validate_column_name(), $this->table_name is a class property set at initialization.
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $column = {$this->get_column_format($column)}", $row_id ) );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter

			return false !== $result;
		}

		/**
		 * Create the table.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $sql The SQL statement to execute.
		 * @return void
		 */
		protected function _create_table( $sql ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			dbDelta( $sql );

			update_option( $this->table_name . '_db_version', $this->version );
		}
	}

endif;
