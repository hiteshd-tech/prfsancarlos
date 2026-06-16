<?php
/**
 * GiveWP Migration Tool for Charitable Lite.
 *
 * Imports campaigns and donations directly from GiveWP database tables.
 *
 * @package   Charitable/Classes/Charitable_GiveWP_Importer
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.10
 * @version   1.8.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_GiveWP_Importer' ) ) :

	/**
	 * Charitable_GiveWP_Importer
	 *
	 * Handles direct database migration from GiveWP to Charitable.
	 * Lite version supports campaigns and donations only.
	 *
	 * @since 1.8.10
	 */
	class Charitable_GiveWP_Importer {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_GiveWP_Importer|null
		 */
		private static $instance = null;

		/**
		 * Batch size for processing items.
		 *
		 * @var int
		 */
		private $batch_size = 25;

		/**
		 * Create object instance.
		 *
		 * @since 1.8.10
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since 1.8.10
		 *
		 * @return Charitable_GiveWP_Importer
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Enqueue scripts for the GiveWP migration tool.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			if ( ! charitable_is_tools_view( 'import' ) || ! charitable_is_tools_view( 'import__givewp' ) ) {
				return;
			}

			wp_enqueue_script(
				'charitable-givewp-migration',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-givewp-migration.js',
				array( 'jquery' ),
				charitable()->get_version(),
				true
			);

			wp_localize_script(
				'charitable-givewp-migration',
				'charitable_givewp_migration',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'charitable_givewp_migration' ),
					'i18n'     => array(
						'starting'       => __( 'Starting migration...', 'charitable' ),
						'importing'      => __( 'Importing %1$s... (%2$d/%3$d)', 'charitable' ),
						'complete'       => __( 'Migration complete!', 'charitable' ),
						'dry_complete'   => __( 'Dry run complete! No data was written to the database.', 'charitable' ),
						'error'          => __( 'An error occurred during migration.', 'charitable' ),
						'confirm'        => __( 'Are you sure you want to start the migration? This will import GiveWP data into Charitable.', 'charitable' ),
						'confirm_dry'    => __( 'Start a dry run? This will simulate the migration without writing any data.', 'charitable' ),
						'campaigns'      => __( 'campaigns', 'charitable' ),
						'donations'      => __( 'donations', 'charitable' ),
					),
				)
			);
		}

		/**
		 * Check if GiveWP is active.
		 *
		 * @since 1.8.10
		 *
		 * @return bool
		 */
		public function is_givewp_active() {
			return defined( 'GIVE_VERSION' );
		}

		/**
		 * Get counts of GiveWP items available for import.
		 *
		 * @since 1.8.10
		 *
		 * @return array
		 */
		public function get_counts() {
			global $wpdb;

			$counts = array(
				'campaigns' => 0,
				'donations' => 0,
			);

			if ( ! $this->is_givewp_active() ) {
				return $counts;
			}

			// Count GiveWP forms (campaigns).
			$counts['campaigns'] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'draft', 'private')", 'give_forms' )
			);

			// Count GiveWP payments (donations).
			$counts['donations'] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status != %s", 'give_payment', 'trash' )
			);

			return $counts;
		}

		/**
		 * Initialize an import session.
		 *
		 * @since 1.8.10
		 *
		 * @param array $options Import options.
		 *
		 * @return string|false Import ID or false on failure.
		 */
		public function init_import( $options = array() ) {
			$import_id = wp_generate_uuid4();

			$defaults = array(
				'campaigns' => true,
				'donations' => true,
				'dry_run'   => false,
			);

			$options = wp_parse_args( $options, $defaults );

			// Build phases based on options.
			$phases = array();
			if ( $options['campaigns'] ) {
				$phases[] = 'campaigns';
			}
			if ( $options['donations'] ) {
				$phases[] = 'donations';
			}

			if ( empty( $phases ) ) {
				return false;
			}

			$progress = array(
				'import_id'    => $import_id,
				'options'      => $options,
				'phases'       => $phases,
				'current_phase' => 0,
				'offset'       => 0,
				'status'       => 'running',
				'campaign_map' => array(), // Maps GiveWP form IDs to Charitable campaign IDs.
				'results'      => array(
					'campaigns' => array(
						'imported' => 0,
						'skipped'  => 0,
						'errors'   => 0,
					),
					'donations' => array(
						'imported' => 0,
						'skipped'  => 0,
						'errors'   => 0,
					),
				),
			);

			set_transient( 'charitable_givewp_import_' . $import_id, $progress, HOUR_IN_SECONDS );

			return $import_id;
		}

		/**
		 * Run the next batch of the import.
		 *
		 * @since 1.8.10
		 *
		 * @param string $import_id The import session ID.
		 *
		 * @return array|false Progress data or false on failure.
		 */
		public function run_batch( $import_id ) {
			$progress = get_transient( 'charitable_givewp_import_' . $import_id );

			if ( ! $progress || 'running' !== $progress['status'] ) {
				return false;
			}

			$current_phase_index = $progress['current_phase'];

			if ( $current_phase_index >= count( $progress['phases'] ) ) {
				$progress['status'] = 'complete';
				set_transient( 'charitable_givewp_import_' . $import_id, $progress, HOUR_IN_SECONDS );
				return $progress;
			}

			$phase  = $progress['phases'][ $current_phase_index ];
			$offset = $progress['offset'];

			$dry_run = ! empty( $progress['options']['dry_run'] );

			switch ( $phase ) {
				case 'campaigns':
					$processed = $this->import_campaigns_batch( $progress, $offset, $dry_run );
					break;

				case 'donations':
					$processed = $this->import_donations_batch( $progress, $offset, $dry_run );
					break;

				default:
					$processed = 0;
					break;
			}

			if ( $processed < $this->batch_size ) {
				// Phase complete, move to next.
				$progress['current_phase'] = $current_phase_index + 1;
				$progress['offset']        = 0;
			} else {
				$progress['offset'] = $offset + $processed;
			}

			// Check if all phases are done.
			if ( $progress['current_phase'] >= count( $progress['phases'] ) ) {
				$progress['status'] = 'complete';
			}

			set_transient( 'charitable_givewp_import_' . $import_id, $progress, HOUR_IN_SECONDS );

			return $progress;
		}

		/**
		 * Import a batch of campaigns from GiveWP forms.
		 *
		 * @since 1.8.10
		 *
		 * @param array &$progress The progress data (passed by reference).
		 * @param int    $offset   The offset for the query.
		 * @param bool   $dry_run  Whether this is a dry run.
		 *
		 * @return int Number of items processed in this batch.
		 */
		private function import_campaigns_batch( &$progress, $offset, $dry_run = false ) {
			global $wpdb;

			$forms = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->posts}
					WHERE post_type = 'give_forms'
					AND post_status IN ('publish', 'draft', 'private')
					ORDER BY ID ASC
					LIMIT %d OFFSET %d",
					$this->batch_size,
					$offset
				)
			);

			if ( empty( $forms ) ) {
				return 0;
			}

			foreach ( $forms as $form ) {
				// Check for duplicate (already imported).
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta}
						WHERE meta_key = '_givewp_form_id' AND meta_value = %s
						LIMIT 1",
						$form->ID
					)
				);

				if ( $existing ) {
					++$progress['results']['campaigns']['skipped'];
					// Still map it for donation imports.
					$progress['campaign_map'][ $form->ID ] = $existing;
					continue;
				}

				if ( $dry_run ) {
					++$progress['results']['campaigns']['imported'];
					continue;
				}

				// Get the GiveWP form goal.
				$goal = get_post_meta( $form->ID, '_give_set_goal', true );

				$campaign_data = array(
					'post_title'   => $form->post_title,
					'post_content' => $form->post_content,
					'post_status'  => 'draft',
					'post_type'    => 'campaign',
					'post_author'  => $form->post_author,
					'post_date'    => $form->post_date,
					'post_date_gmt' => $form->post_date_gmt,
				);

				$campaign_id = wp_insert_post( $campaign_data );

				if ( is_wp_error( $campaign_id ) ) {
					++$progress['results']['campaigns']['errors'];
					continue;
				}

				// Set goal if available.
				if ( ! empty( $goal ) && is_numeric( $goal ) ) {
					update_post_meta( $campaign_id, '_campaign_goal', sanitize_text_field( $goal ) );
				}

				// Store reference to original GiveWP form.
				update_post_meta( $campaign_id, '_givewp_form_id', $form->ID );

				// Map for donation import.
				$progress['campaign_map'][ $form->ID ] = $campaign_id;

				++$progress['results']['campaigns']['imported'];
			}

			return count( $forms );
		}

		/**
		 * Import a batch of donations from GiveWP payments.
		 *
		 * @since 1.8.10
		 *
		 * @param array &$progress The progress data (passed by reference).
		 * @param int    $offset   The offset for the query.
		 * @param bool   $dry_run  Whether this is a dry run.
		 *
		 * @return int Number of items processed in this batch.
		 */
		private function import_donations_batch( &$progress, $offset, $dry_run = false ) {
			global $wpdb;

			$payments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->posts}
					WHERE post_type = 'give_payment'
					AND post_status != 'trash'
					ORDER BY ID ASC
					LIMIT %d OFFSET %d",
					$this->batch_size,
					$offset
				)
			);

			if ( empty( $payments ) ) {
				return 0;
			}

			// Block emails during bulk import.
			if ( ! $dry_run ) {
				$this->start_blocking_emails();
			}

			foreach ( $payments as $payment ) {
				// Check for duplicate.
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta}
						WHERE meta_key = '_givewp_donation_id' AND meta_value = %s
						LIMIT 1",
						$payment->ID
					)
				);

				if ( $existing ) {
					++$progress['results']['donations']['skipped'];
					continue;
				}

				if ( $dry_run ) {
					++$progress['results']['donations']['imported'];
					continue;
				}

				// Get GiveWP donation meta.
				$amount     = get_post_meta( $payment->ID, '_give_payment_total', true );
				$form_id    = get_post_meta( $payment->ID, '_give_payment_form_id', true );
				$email      = get_post_meta( $payment->ID, '_give_payment_donor_email', true );
				$first_name = get_post_meta( $payment->ID, '_give_donor_billing_first_name', true );
				$last_name  = get_post_meta( $payment->ID, '_give_donor_billing_last_name', true );
				$gateway    = get_post_meta( $payment->ID, '_give_payment_gateway', true );

				if ( empty( $email ) || empty( $amount ) ) {
					++$progress['results']['donations']['errors'];
					continue;
				}

				// Resolve campaign ID.
				$campaign_id = 0;
				if ( ! empty( $form_id ) && isset( $progress['campaign_map'][ $form_id ] ) ) {
					$campaign_id = $progress['campaign_map'][ $form_id ];
				}

				// Map GiveWP status to Charitable status.
				$status = $this->map_payment_status( $payment->post_status );

				$donation_args = array(
					'user'      => array(
						'email'      => sanitize_email( $email ),
						'first_name' => sanitize_text_field( $first_name ),
						'last_name'  => sanitize_text_field( $last_name ),
					),
					'campaigns' => array(
						array(
							'campaign_id' => $campaign_id,
							'amount'      => floatval( $amount ),
						),
					),
					'gateway'   => sanitize_text_field( $gateway ),
					'status'    => $status,
					'date'      => $payment->post_date,
					'log_note'  => __( 'Imported from GiveWP via Migration Tool.', 'charitable' ),
				);

				$donation_id = charitable_create_donation( $donation_args );

				if ( $donation_id ) {
					update_post_meta( $donation_id, '_givewp_donation_id', $payment->ID );

					// Copy address meta if available.
					$address_fields = array(
						'_give_donor_billing_address1' => 'address',
						'_give_donor_billing_address2' => 'address_2',
						'_give_donor_billing_city'     => 'city',
						'_give_donor_billing_state'    => 'state',
						'_give_donor_billing_zip'      => 'postcode',
						'_give_donor_billing_country'  => 'country',
					);

					foreach ( $address_fields as $give_key => $charitable_key ) {
						$value = get_post_meta( $payment->ID, $give_key, true );
						if ( ! empty( $value ) ) {
							update_post_meta( $donation_id, 'donor_' . $charitable_key, sanitize_text_field( $value ) );
						}
					}

					++$progress['results']['donations']['imported'];
				} else {
					++$progress['results']['donations']['errors'];
				}
			}

			if ( ! $dry_run ) {
				$this->stop_blocking_emails();
			}

			return count( $payments );
		}

		/**
		 * Map a GiveWP payment post_status to a Charitable donation status.
		 *
		 * @since 1.8.10
		 *
		 * @param string $givewp_status The GiveWP payment post_status.
		 *
		 * @return string The Charitable donation status.
		 */
		private function map_payment_status( $givewp_status ) {
			$map = array(
				'give_subscription' => 'charitable-completed',
				'publish'           => 'charitable-completed',
				'give_pending'      => 'charitable-pending',
				'pending'           => 'charitable-pending',
				'give_processing'   => 'charitable-pending',
				'give_complete'     => 'charitable-completed',
				'give_cancelled'    => 'charitable-cancelled',
				'give_abandoned'    => 'charitable-cancelled',
				'give_failed'       => 'charitable-failed',
				'give_revoked'      => 'charitable-cancelled',
				'give_preapproval'  => 'charitable-pending',
				'refunded'          => 'charitable-refunded',
				'give_refunded'     => 'charitable-refunded',
			);

			return isset( $map[ $givewp_status ] ) ? $map[ $givewp_status ] : 'charitable-pending';
		}

		/**
		 * Block Charitable email notifications during bulk import.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		private function start_blocking_emails() {
			add_filter( 'charitable_send_email', '__return_false', 999 );
		}

		/**
		 * Unblock Charitable email notifications after bulk import.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		private function stop_blocking_emails() {
			remove_filter( 'charitable_send_email', '__return_false', 999 );
		}

		/**
		 * Get the import summary from a completed import.
		 *
		 * @since 1.8.10
		 *
		 * @param string $import_id The import session ID.
		 *
		 * @return array|false The results array or false.
		 */
		public function get_import_summary( $import_id ) {
			$progress = get_transient( 'charitable_givewp_import_' . $import_id );

			if ( ! $progress ) {
				return false;
			}

			return $progress['results'];
		}

		/**
		 * Handle the AJAX request to start a migration.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function ajax_import_start() {
			check_ajax_referer( 'charitable_givewp_migration', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
			}

			if ( ! $this->is_givewp_active() ) {
				wp_send_json_error( array( 'message' => __( 'GiveWP is not active.', 'charitable' ) ) );
			}

			$options = array(
				'campaigns' => ! empty( $_POST['campaigns'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'donations' => ! empty( $_POST['donations'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'dry_run'   => ! empty( $_POST['dry_run'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);

			$import_id = $this->init_import( $options );

			if ( ! $import_id ) {
				wp_send_json_error( array( 'message' => __( 'No import types selected.', 'charitable' ) ) );
			}

			$counts = $this->get_counts();

			wp_send_json_success(
				array(
					'import_id' => $import_id,
					'counts'    => $counts,
					'options'   => $options,
				)
			);
		}

		/**
		 * Handle the AJAX request to process a migration batch.
		 *
		 * @since 1.8.10
		 *
		 * @return void
		 */
		public function ajax_import_batch() {
			check_ajax_referer( 'charitable_givewp_migration', 'nonce' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
			}

			$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( empty( $import_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid import session.', 'charitable' ) ) );
			}

			$progress = $this->run_batch( $import_id );

			if ( false === $progress ) {
				wp_send_json_error( array( 'message' => __( 'Import session not found or already completed.', 'charitable' ) ) );
			}

			$current_phase = '';
			if ( isset( $progress['phases'][ $progress['current_phase'] ] ) ) {
				$current_phase = $progress['phases'][ $progress['current_phase'] ];
			}

			wp_send_json_success(
				array(
					'status'        => $progress['status'],
					'current_phase' => $current_phase,
					'results'       => $progress['results'],
					'dry_run'       => ! empty( $progress['options']['dry_run'] ),
				)
			);
		}
	}

endif;
