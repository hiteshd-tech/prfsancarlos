<?php
/**
 * Charitable Tools Misc.
 *
 * @package   Charitable/Classes/Charitable_Tools_Misc
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9
 * @version   1.8.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Tools_Misc' ) ) :

	/**
	 * Charitable_Tools_Misc
	 *
	 * @since 1.8.9
	 */
	class Charitable_Tools_Misc {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Tools_Misc|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.9
		 */
		private function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_init', array( $this, 'handle_redirect_after_save' ) );
		}

		/**
		 * Enqueue scripts for the misc tools tab.
		 *
		 * @since 1.8.9
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			if ( ! charitable_is_tools_view() || ! isset( $_GET['tab'] ) || 'misc' !== $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			wp_enqueue_script(
				'charitable-tools-misc',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-tools-misc.js',
				array( 'jquery' ),
				charitable()->get_version(),
				true
			);
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.8.9
		 *
		 * @return Charitable_Tools_Misc
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add the misc tab fields.
		 *
		 * @since  1.8.9
		 *
		 * @param  array $fields Existing fields.
		 * @return array
		 */
		public function add_misc_fields( $fields = array() ) {
			if ( ! is_array( $fields ) ) {
				$fields = array();
			}
            $fields['section_tools_misc'] = array(
                'title'    => __( 'Misc', 'charitable' ),
                'type'     => 'heading',
                'class'    => 'section-heading',
                'priority' => 5,
                'attrs'    => array(
                    'id' => 'charitable_tools_misc_section_heading',
                ),
            );
			$fields['bulk_remove_donations_section'] = array(
				'title'    => __( 'Bulk Remove Donations', 'charitable' ),
				'type'     => 'heading',
				'class'    => 'section-heading',
				'priority' => 10,
                'attrs'    => array(
                    'id' => 'charitable_tools_misc_bulk_remove_donations_section',
                ),
			);
			$fields['bulk_remove_donations'] = array(
				'label_for' => __( 'Donation Status', 'charitable' ),
				'type'      => 'select',
				'options'   => array(
					''              => __( 'Select status...', 'charitable' ),
					'remove_pending' => __( 'Remove all PENDING donations', 'charitable' ),
					'remove_failed'  => __( 'Remove all FAILED donations', 'charitable' ),
				),
				'help'      => __( 'Warning: This permanently removes all donations from all campaigns, intended for use after a spam or similar attack.', 'charitable' ),
				'priority'  => 20,
				'attrs'     => array(
					'id' => 'charitable_tools_misc_bulk_remove_donations',
				),
			);
			$fields['bulk_remove_donations_date_from'] = array(
				'label_for' => __( 'Date From', 'charitable' ),
				'type'      => 'date',
				'help'      => __( 'Start date for the date range. Leave empty to remove all donations of the selected status. Only used when a donation status is selected.', 'charitable' ),
				'priority'  => 30,
				'attrs'     => array(
					'id' => 'charitable_tools_misc_bulk_remove_donations_date_from',
				),
			);
			$fields['bulk_remove_donations_date_to'] = array(
				'label_for' => __( 'Date To', 'charitable' ),
				'type'      => 'date',
				'help'      => __( 'End date for the date range. Leave empty to remove all donations of the selected status. Only used when a donation status is selected.', 'charitable' ),
				'priority'  => 40,
				'attrs'     => array(
					'id' => 'charitable_tools_misc_bulk_remove_donations_date_to',
				),
			);

			return $fields;
		}

		/**
		 * Handle option update for bulk removal.
		 *
		 * @since   1.8.9
		 *
		 * @param   mixed[] $old_value The old option value.
		 * @param   mixed[] $new_value The new option value.
		 * @return  void
		 */
		public function bulk_remove_donations_on_update( $old_value, $new_value ) {
			if ( ! is_array( $new_value ) ) {
				return;
			}

			// Check if bulk_remove_donations is set in the new values.
			if ( ! isset( $new_value['misc'] ) || ! is_array( $new_value['misc'] ) ) {
				return;
			}

			if ( ! isset( $new_value['misc']['bulk_remove_donations'] ) || '' === trim( $new_value['misc']['bulk_remove_donations'] ) ) {
				return;
			}

			// Process the removal.
			$misc_values = $new_value['misc'];
			$result = $this->bulk_remove_donations( array(), $misc_values, $old_value );

			// Update the option with the processed values to reset the fields.
			if ( is_array( $result ) ) {
				$new_value['misc'] = array_merge( $new_value['misc'], $result );
				// Remove the action to prevent infinite loop.
				remove_action( 'update_option_charitable_tools', array( $this, 'bulk_remove_donations_on_update' ), 10 );
				update_option( 'charitable_tools', $new_value );
				add_action( 'update_option_charitable_tools', array( $this, 'bulk_remove_donations_on_update' ), 10, 2 );
			}
		}

		/**
		 * Removes donations based on status and optional date range.
		 *
		 * @since   1.8.9
		 *
		 * @param   mixed[] $values     All values, merged (when used as filter).
		 * @param   mixed[] $new_values The newly submitted values.
		 * @param   mixed[] $old_values The old values.
		 * @return  mixed[]
		 */
		public function bulk_remove_donations( $values, $new_values, $old_values ) {
			if ( ! is_array( $values ) ) {
				$values = array();
			}

			// The sanitize_settings method flattens non-dynamic groups, so misc data might be at top level.
			// Check both structures: nested under 'misc' or flattened at top level.
			$misc_data = null;
			if ( isset( $new_values['misc'] ) && is_array( $new_values['misc'] ) ) {
				// Data is nested under 'misc' (if misc is a dynamic group).
				$misc_data = $new_values['misc'];
			} elseif ( isset( $new_values['bulk_remove_donations'] ) ) {
				// Data is flattened at top level (misc is not a dynamic group).
				$misc_data = $new_values;
			}

			// If no misc data found, return unchanged.
			if ( is_null( $misc_data ) ) {
				return $values;
			}

			/* If this option isn't in the return values or isn't set, leave. */
			if ( ! isset( $misc_data['bulk_remove_donations'] ) || '' === trim( $misc_data['bulk_remove_donations'] ) ) {
				return $values;
			}

			$status = sanitize_text_field( $misc_data['bulk_remove_donations'] );

			if ( ! in_array( $status, array( 'remove_pending', 'remove_failed' ), true ) ) {
				return $values;
			}

			// Get date range if provided.
			$date_from = isset( $misc_data['bulk_remove_donations_date_from'] ) ? sanitize_text_field( $misc_data['bulk_remove_donations_date_from'] ) : '';
			$date_to   = isset( $misc_data['bulk_remove_donations_date_to'] ) ? sanitize_text_field( $misc_data['bulk_remove_donations_date_to'] ) : '';

			// Determine status value for query.
			$donation_status = 'remove_pending' === $status ? 'charitable-pending' : 'charitable-failed';

			// Build query args.
			$query_args = array(
				'status' => $donation_status,
			);

			// Add date range if provided.
			if ( ! empty( $date_from ) ) {
				// Ensure date includes time for proper comparison (start of day).
				$date_from = gmdate( 'Y-m-d 00:00:00', strtotime( $date_from ) );
				$query_args['start_date'] = $date_from;
			}
			if ( ! empty( $date_to ) ) {
				// Ensure date includes time for proper comparison (end of day).
				$date_to = gmdate( 'Y-m-d 23:59:59', strtotime( $date_to ) );
				$query_args['end_date'] = $date_to;
			}

			// Get donations matching criteria.
			$donations = charitable_get_table( 'campaign_donations' )->get_donations_report( $query_args );

			if ( ! is_array( $donations ) || empty( $donations ) ) {
				$status_label = 'remove_pending' === $status ? __( 'pending', 'charitable' ) : __( 'failed', 'charitable' );
				charitable_get_admin_notices()->add_notice(
					sprintf(
						/* translators: %s: donation status */
						__( 'No %s donations found to remove.', 'charitable' ),
						$status_label
					),
					'error',
					false,
					true
				);
				// Reset the misc fields after processing.
				// Since misc is not a dynamic group, fields are at top level.
				$values['bulk_remove_donations'] = false;
				$values['bulk_remove_donations_date_from'] = false;
				$values['bulk_remove_donations_date_to'] = false;
				return $values;
			}

			$donations_ids = wp_list_pluck( $donations, 'donation_id' );
			$count         = count( $donations_ids );

			foreach ( $donations_ids as $donation_id ) {

				$remove_confirmation = wp_delete_post( $donation_id, true );
				if ( charitable_is_debug() ) {
					error_log( 'Bulk remove donation post: ' . $donation_id . ' - ' . print_r( $remove_confirmation, true ) ); // phpcs:ignore
				}

				$remove_confirmation = charitable_get_table( 'campaign_donations' )->delete( $donation_id );
				if ( charitable_is_debug() ) {
					error_log( 'Bulk remove campaign_donations: ' . $donation_id . ' - ' . $remove_confirmation ); // phpcs:ignore
				}

				$remove_confirmation = charitable_get_table( 'campaign_donations' )->delete_donation_records( $donation_id );
				if ( charitable_is_debug() ) {
					error_log( 'Bulk remove donation records: ' . $donation_id . ' - ' . $remove_confirmation ); // phpcs:ignore
				}

				$remove_confirmation = charitable_get_table( 'donation_activities' )->delete_by( 'donation_id', $donation_id );
				if ( charitable_is_debug() ) {
					error_log( 'Bulk remove activity records: ' . $donation_id . ' - ' . $remove_confirmation ); // phpcs:ignore
				}
			}

			// Allow an addon to hook into this.
			do_action( 'charitable_after_bulk_remove_donations', $status, $count, $date_from, $date_to );

			$status_label = 'remove_pending' === $status ? __( 'pending', 'charitable' ) : __( 'failed', 'charitable' );
			charitable_get_admin_notices()->add_notice(
				sprintf(
					/* translators: %1$d: count, %2$s: status */
					_n(
						'%1$d %2$s donation has been deleted.',
						'%1$d %2$s donations have been deleted.',
						$count,
						'charitable'
					),
					$count,
					$status_label
				),
				'success',
				false,
				true
			);

			// Reset the misc fields after processing.
			// Since misc is not a dynamic group, fields are at top level.
			$values['bulk_remove_donations'] = false;
			$values['bulk_remove_donations_date_from'] = false;
			$values['bulk_remove_donations_date_to'] = false;

			// Allow an addon to hook into this.
			do_action( 'charitable_end_bulk_remove_donations', $values, $new_values );

			return $values;
		}

		/**
		 * Handle redirect after saving tools to ensure we stay on the misc tab.
		 *
		 * @since   1.8.9
		 *
		 * @return  void
		 */
		public function handle_redirect_after_save() {
			// Only process on admin_init when we're on options.php or after redirect.
			if ( ! isset( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				return;
			}

			$is_options_page = false !== strpos( $_SERVER['REQUEST_URI'], 'options.php' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$is_settings_updated = isset( $_GET['settings-updated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! $is_options_page && ! $is_settings_updated ) {
				return;
			}

			// Check if we have misc fields in the POST data (for options.php) or if we just saved.
			if ( $is_options_page ) {
				if ( ! isset( $_POST['charitable_tools'] ) || ! isset( $_POST['charitable_tools']['misc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return;
				}

				// Check if bulk_remove_donations was submitted.
				$bulk_remove_donations = isset( $_POST['charitable_tools']['misc']['bulk_remove_donations'] ) ? sanitize_text_field( wp_unslash( $_POST['charitable_tools']['misc']['bulk_remove_donations'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( '' === trim( $bulk_remove_donations ) ) {
					return;
				}

				// Add filter to modify redirect URL after options are saved.
				add_filter( 'wp_redirect', array( $this, 'filter_redirect_url' ), 10, 2 );
			} elseif ( $is_settings_updated ) {
				// If we're on a settings-updated page but not on misc tab, redirect to misc.
				$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'misc' !== $current_tab && isset( $_GET['page'] ) && 'charitable-tools' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					// Check if we have a transient indicating we just processed misc.
					$misc_processed = get_transient( 'charitable_tools_misc_processed' );
					if ( $misc_processed ) {
						delete_transient( 'charitable_tools_misc_processed' );
						wp_safe_redirect( admin_url( 'admin.php?page=charitable-tools&tab=misc&settings-updated=true' ) );
						exit;
					}
				}
			}
		}

		/**
		 * Filter the redirect URL to ensure we go to the misc tab.
		 *
		 * @since   1.8.9
		 *
		 * @param   string $location The redirect location.
		 * @param   int    $status   The redirect status code.
		 * @return  string
		 */
		public function filter_redirect_url( $location, $status ) {
			// Only filter if we have misc bulk removal data in POST.
			if ( ! isset( $_POST['charitable_tools']['misc']['bulk_remove_donations'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $location;
			}

			// Set transient to indicate misc was processed.
			set_transient( 'charitable_tools_misc_processed', true, 30 );

			// Replace the location with the misc tab URL.
			$location = admin_url( 'admin.php?page=charitable-tools&tab=misc&settings-updated=true' );
			remove_filter( 'wp_redirect', array( $this, 'filter_redirect_url' ), 10 );

			return $location;
		}
	}

endif;

