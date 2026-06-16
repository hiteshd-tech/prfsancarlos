<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Admin
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.1.0
 * @version   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Square\Legacy\Models\Location;
use Square\Legacy\Models\LocationStatus;
use Square\Legacy\Models\LocationCapability;

if ( ! class_exists( 'Charitable_Square_Connect' ) ) :

	/**
	 * Charitable_Square_Admin
	 *
	 * @since 1.1.0
	 */
	class Charitable_Square_Connect {

		/**
		 * Charitable website URL.
		 *
		 * @since 1.8.7
		 */
		private const CHARITABLE_SQUARE_CONNECT_URL = 'https://connect.wpcharitable.com';

		/**
		 * Webhooks manager.
		 *
		 * @since 1.8.7
		 *
		 * @var WebhooksManager
		 */
		private $webhooks_manager;

		/**
		 * Single instance of this class.
		 *
		 * @since 1.1.0
		 *
		 * @var   Charitable_Square_Connect
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.1.0
		 */
		public function __construct() {
		}

		/**
		 * Initialize the class.
		 *
		 * @since 1.8.7
		 */
		public function init() {

			$this->webhooks_manager = new Charitable_Square_WebhooksManager();

			$this->hooks();

			return $this;
		}

		/**
		 * Hooks.
		 *
		 * @since 1.8.7
		 */
		public function hooks() {
			add_action( 'charitable_square_refresh_connection', array( $this, 'refresh_connection_schedule' ) );
			add_action( 'wp_ajax_charitable_square_refresh_connection', array( $this, 'refresh_connection_manual' ) );
		}

		/**
		 * Handle actions.
		 *
		 * @since 1.8.7
		 */
		public function handle_actions() {

			if ( ! charitable_current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
				return;
			}

			$this->validate_scopes();

			if (
			isset( $_GET['_wpnonce'] ) &&
			wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'charitable_square_disconnect' )
			) {
				$this->handle_disconnect();

				return;
			}

			$this->schedule_refresh();

			if (
			! empty( $_GET['state'] ) &&
			isset( $_GET['square_connect'] ) &&
			$_GET['square_connect'] === 'complete'
			) {
				$this->handle_connected();
			}
		}

		/**
		 * Validate connection scopes.
		 *
		 * @since 1.8.7
		 */
		private function validate_scopes() {

			if ( Charitable_Square_Helpers::is_license_ok() ) {
				return;
			}

			$connection = Charitable_Square_Connection::get();

			if ( ! $connection || ! $connection->is_configured() ) {
				return;
			}

			// Bail early if currency is not supported for applying a fee.
			if ( ! Charitable_Square_Helpers::is_application_fee_supported() ) {
				return;
			}

			if ( $connection->get_scopes_updated() ) {
				return;
			}

			// Revoke tokens if the license is not valid and scopes are missing.
			$connection->revoke_tokens();
		}

		/**
		 * Handle a successful connection.
		 *
		 * @since 1.8.7
		 */
		private function handle_connected() {

			$state = sanitize_text_field( wp_unslash( $_GET['state'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated

			if ( empty( $state ) ) {
				return;
			}

			$connection_raw = $this->fetch_new_connection( $state );
			$connection     = $this->maybe_save_connection( $connection_raw );

			if ( ! $connection ) {
				return;
			}

			$mode = $connection->get_mode();

			$this->prepare_locations( $mode );

			$redirect_url = add_query_arg(
				array(
					'tab'   => 'gateways',
					'page'  => 'charitable-settings',
					'group' => 'gateways_square_core',
				),
				admin_url( 'admin.php' )
			);

			if ( ! $connection->is_usable() ) {
				$redirect_url .= '&redirect=true#charitable-setting-row-square-heading';
			}

			// delete the option that stores the error admin notice.
			delete_option( 'charitable_square_connection_error_notice' );

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Handle disconnection.
		 *
		 * @since 1.8.7
		 * @version 1.8.9.1
		 *
		 * @param bool $redirect Whether to redirect after disconnection.
		 */
		private function handle_disconnect( $redirect = true ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['live_mode'] ) ) {
				$live_mode = absint( wp_unslash( $_GET['live_mode'] ) );
			} else {
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
				$live_mode = charitable_get_option( 'test_mode' ) ? 0 : 1;
			}
			$mode       = $live_mode ? 'live' : 'test';
			$connection = Charitable_Square_Connection::get( $mode );

			// Track the overall success of the disconnection process.
			$disconnection_success     = true;
			$square_revocation_success = true;

			// Delete the Square connection error notice.
			delete_option( 'charitable_square_connection_error_notice' );

			// Attempt to revoke the token before deleting local data.
			if ( $connection && $connection->is_configured() ) {
				$result = $this->fetch_revoke_connection(
					$connection->get_merchant_id(),
					$connection->get_access_token(),
					$mode
				);

				if ( is_wp_error( $result ) ) {
					$square_revocation_success = false;
					$disconnection_success     = false;

					// Add admin notice for Square API revocation failure.
					Charitable_Settings::get_instance()->add_update_message(
						sprintf(
							/* translators: %s: Error message from Square API */
							__( 'Square Error: Unable to revoke access token. %s', 'charitable' ),
							$result->get_error_message()
						),
						'error'
					);
				}
			}

			// Attempt to delete local connection data.
			if ( $connection ) {
				$delete_result = $connection->delete();
				if ( ! $delete_result ) {
					$disconnection_success = false;

					// Add admin notice for local deletion failure.
					Charitable_Settings::get_instance()->add_update_message(
						__( 'Error: Unable to remove Square connection from your site. Please try again.', 'charitable' ),
						'error'
					);
				}
			}

			// Are in live mode (production)?
			if ( ! charitable_get_option( 'test_mode' ) ) {
				$this->unschedule_refresh();
			}

			// Webhooks are now manually configured in Square dashboard - no need to reset.
			// Previously this was: Charitable_Square_Helpers::reset_webhook_configuration( true );.

			// Not needed because this as stored in the "connection" that was wiped above.
			$api = new Charitable_Square_API( $connection );
			$api->set_location_id( '', $mode );
			$api->set_location_currency( '', $mode );
			$api->detete_transients( $mode );

			// Add appropriate success/partial success notice.
			if ( $disconnection_success ) {
				if ( ! $square_revocation_success ) {
					// Partial success - local disconnection worked but Square revocation failed.
					Charitable_Settings::get_instance()->add_update_message(
						__( 'Square has been disconnected from your site, but there was an issue revoking access on Square\'s end. You may need to manually revoke access in your Square dashboard.', 'charitable' ),
						'warning'
					);
				} else {
					// Complete success.
					Charitable_Settings::get_instance()->add_update_message(
						__( 'Square has been successfully disconnected from your site.', 'charitable' ),
						'success'
					);
				}
			}

			if ( $redirect ) {
				wp_safe_redirect( charitable_get_square_settings_page_url() );
				exit;
			}
		}

		/**
		 * Handle refresh connection triggered by AS task.
		 *
		 * @since 1.8.7
		 */
		public function refresh_connection_schedule() {

			// Don't run refresh tokens for Sandbox connection.
			if ( Charitable_Square_Helpers::is_sandbox_mode() ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'refresh - is sandbox mode' );
					// phpcs:enable
				}
				return;
			}

			$connection = Charitable_Square_Connection::get();

			// Check connection and cancel AS task if connection is not exists, broken OR already invalid.
			if ( ! $connection || ! $connection->is_configured() || ! $connection->is_valid() ) {
				$this->unschedule_refresh();
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'refresh - connection is not valid' );
					// phpcs:enable
				}
				return;
			}

			// If connection is not expired, we'll just fetch active locations.
			if ( ! $connection->is_expired() ) {
				$this->prepare_locations( $connection->get_mode() );
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'refresh - connection is not expired' );
					// phpcs:enable
				}
				return;
			}

			// If connection is expired, try to refresh tokens.
			$connection = $this->try_refresh_connection( $connection );

			if ( is_wp_error( $connection ) ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'refresh - connection is wp error' );
					error_log( print_r( $connection, true ) ); // phpcs:ignore
					// phpcs:enable
				}
				return;
			}

			// If connection is invalid, we'll cancel AS task.
			if ( $connection && ! $connection->is_valid() ) {
				$this->unschedule_refresh();
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'refresh - connection is invalid' );
					// phpcs:enable
				}
				return;
			}

			// Make sure and check connection tokens through fetching active locations.
			$this->prepare_locations( $connection->get_mode() );
		}

		/**
		 * Handle refresh connection triggered manually.
		 *
		 * @since 1.8.7
		 */
		public function refresh_connection_manual() {

			// Security and permissions check.
			if (
				! check_ajax_referer( 'charitable-admin', 'nonce', false ) ||
				! current_user_can( 'manage_options' )
			) {
				wp_send_json_error( esc_html__( 'You are not allowed to perform this action', 'charitable' ) );
			}

			$error_general = esc_html__( 'Something went wrong while performing a refresh tokens request', 'charitable' );

			// Required data check.
			if ( empty( $_POST['mode'] ) ) {
				wp_send_json_error( $error_general );
			}

			$mode       = sanitize_key( $_POST['mode'] );
			$connection = Charitable_Square_Connection::get( $mode );

			// Connection check.
			if ( ! $connection || ! $connection->is_configured() ) {
				wp_send_json_error( $error_general );
			}

			// Try to refresh connection.
			$connection = $this->try_refresh_connection( $connection );

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'refresh_connection_manual - connection' );
				error_log( print_r( $connection, true ) ); // phpcs:ignore
				// phpcs:enable
			}

			if ( is_wp_error( $connection ) ) {
				$error_specific = $connection->get_error_message();
				$error_message  = empty( $error_specific ) ? $error_general : $error_general . ': ' . $error_specific;

				wp_send_json_error( $error_message );
			}

			$this->prepare_locations( $mode, $connection );

			wp_send_json_success();
		}

		/**
		 * Try to refresh connection.
		 *
		 * @since 1.8.7
		 *
		 * @param Connection $connection Connection object.
		 *
		 * @return Connection|WP_Error
		 */
		private function try_refresh_connection( $connection ) {

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'try_refresh_connection - trying to refresh connection' );
				error_log( 'try_refresh_connection - refresh token start' );
				error_log( $connection->get_refresh_token() );
				error_log( 'try_refresh_connection - refresh token end' );
				error_log( 'try_refresh_connection - mode start' );
				error_log( $connection->get_mode() );
				error_log( 'try_refresh_connection - mode end' );
				// phpcs:enable
			}

			$response = $this->fetch_refresh_connection( $connection->get_refresh_token(), $connection->get_mode() );

			if ( is_wp_error( $response ) ) {

				error_log( 'try_refresh_connection - wp error: ' . print_r( $response, true ) ); // phpcs:ignore

				if ( $response->get_error_code() === 'refresh_connection_fail' && $connection->is_valid() ) {
					$connection
						->set_status( Charitable_Square_Connection::STATUS_INVALID )
						->save(); // not it.
				}

				return $response;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'refresh - is not wp error' );
				error_log( print_r( $response, true ) );
				// phpcs:enable
			}

			$refreshed_connection = $this->maybe_save_connection( $response, true );

			return $refreshed_connection ?? new WP_Error();
		}

		/**
		 * Schedule the connection refresh using WP Cron.
		 *
		 * @since 1.8.7
		 */
		private function schedule_refresh() {

			// Schedule WP Cron only if a Production connection exists.
			if ( ! Charitable_Square_Connection::get( 'live' ) ) {
				return;
			}

			// Check if the event is already scheduled.
			if ( ! wp_next_scheduled( 'charitable_square_refresh_connection' ) ) {
				// Schedule the event to run daily.
				wp_schedule_event( time(), 'daily', 'charitable_square_refresh_connection' );
			}
		}

		/**
		 * Unschedule the connection refresh WP Cron job.
		 *
		 * @since 1.8.7
		 */
		public function unschedule_refresh() {
			$timestamp = wp_next_scheduled( 'charitable_square_refresh_connection' );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'charitable_square_refresh_connection' );
			}
		}

		/**
		 * Check connection raw data and save it if everything is OK.
		 *
		 * @since 1.8.7
		 * @version 1.8.9.1
		 *
		 * @param array $raw    Connection raw data.
		 * @param bool  $silent Optional. Whether to prevent showing admin notices. Default false.
		 *
		 * @return Connection|null
		 */
		private function maybe_save_connection( array $raw, bool $silent = false ) {

			$connection = new Charitable_Square_Connection( $raw, false );

			// Bail if a connection doesn't have required data.
			if ( ! $connection->is_configured() ) {
				if ( charitable_is_debug() ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log(
						'Square error',
						'We could not connect to Square. No tokens were given.',
						array(
							'type' => array( 'payment', 'error' ),
						)
					);
				}
				if ( ! $silent ) {
					Charitable_Settings::get_instance()->add_update_message( __( 'Square Error: We could not connect to Square. No tokens were given.', 'charitable' ), 'error' );
				}

				return null;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'maybe_save_connection - connection' );
				error_log( print_r( $connection, true ) ); // phpcs:ignore
				// phpcs:enable
			}

			// Prepare connection for save.
			$connection
				->set_renew_at()
				->set_scopes_updated()
				->encrypt_tokens();

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'maybe_save_connection - connection after encrypt_tokens' );
				error_log( print_r( $connection, true ) ); // phpcs:ignore
				// phpcs:enable
			}

			// Bail if a connection is not ready for save.
			if ( ! $connection->is_saveable() ) {
				if ( charitable_is_debug() ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log(
						'Square error',
						'We could not save an account connection safely. Please, try again later.',
						array(
							'type' => array( 'payment', 'error' ),
						)
					);
				}
				if ( ! $silent ) {
					Charitable_Settings::get_instance()->add_update_message( __( 'Square Error: We could not save an account connection safely. Please, try again later.', 'charitable' ), 'error' );
				}

				return null;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'maybe_save_connection - connection before save' );
			}

			$connection->save(); // not it.

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'maybe_save_connection - connection after save' );
				error_log( print_r( $connection, true ) ); // phpcs:ignore
				// phpcs:enable
			}

			return $connection;
		}

		/**
		 * Retrieve active business locations with processing capability.
		 *
		 * @since 1.8.7
		 *
		 * @param array $locations Locations.
		 *
		 * @return array
		 */
		private function active_locations_filter( array $locations ): array {

			$active_locations = array();

			if ( empty( $locations ) ) {
				return $active_locations;
			}

			foreach ( $locations as $location ) {

				if (
				// ! $location instanceof Location ||
				$location->getStatus() !== LocationStatus::ACTIVE ||
				! is_array( $location->getCapabilities() ) ||
				! in_array( LocationCapability::CREDIT_CARD_PROCESSING, $location->getCapabilities(), true )
				) {
					continue;
				}

				$location_id = $location->getId();

				$active_locations[ $location_id ] = array(
					'id'       => $location_id,
					'name'     => $location->getName(),
					'currency' => $location->getCurrency(),
				);
			}

			return $active_locations;
		}

		/**
		 * Set/update location things: ID and currency.
		 *
		 * @since 1.8.7
		 *
		 * @param array                        $locations Active locations.
		 * @param string                       $mode      Square mode.
		 * @param Charitable_Square_Connection $connection Square connection.
		 */
		private function set_location( array $locations, string $mode, $connection = null ) {

			if ( $connection === null ) {
				$connection = Charitable_Square_Connection::get( $mode );
			}

			$api                = new Charitable_Square_API( $connection );
			$stored_location_id = $api->get_location_id( $mode );

			// Location ID was not set previously or saved ID is not available now.
			if ( empty( $stored_location_id ) || ! isset( $locations[ $stored_location_id ] ) ) {
				$stored_location_id = charitable_array_key_first( $locations );

				// Set a new location ID.
				$api->set_location_id( $stored_location_id, $mode );
			}

			// Set location currency for connection.
			// In this case, we can make sure that location currency is matched with Charitable currency.
			if ( $connection !== null ) {
				$connection->set_currency( $locations[ $stored_location_id ]['currency'] )->save(); // not it.
				update_option( 'charitable_square_location_currency_' . $mode, $locations[ $stored_location_id ]['currency'] );
			}
		}

		/**
		 * Get cached business locations or fetch it from Square.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return array
		 */
		public function get_connected_locations( string $mode ): array {

			$locations = get_transient( 'charitable_square_active_locations_' . $mode );

			if ( empty( $locations ) ) {
				$locations = $this->prepare_locations( $mode );
			}

			return $locations;
		}

		/**
		 * Reset location ID and currency if no locations are received.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 */
		private function reset_location( string $mode ) {

			$connection = Charitable_Square_Connection::get( $mode );

			if ( ! $connection || ! $connection->is_valid() ) {
				return;
			}

			$api = new Charitable_Square_API( $connection );
			$api->set_location_id( '', $mode );

			$connection->set_currency( '' )->save();
		}

		/**
		 * Prepare Square business locations.
		 *
		 * @since 1.8.7
		 *
		 * @param string                       $mode Square mode.
		 * @param Charitable_Square_Connection $connection Square connection.
		 *
		 * @return array
		 */
		public function prepare_locations( string $mode, $connection = null ): array {

			$locations = $this->fetch_locations( $mode );

			if ( $locations === null ) {

				$this->reset_location( $mode );

				delete_transient( 'charitable_square_active_locations_' . $mode );

				return array();
			}

			$locations = $this->active_locations_filter( $locations );

			if ( empty( $locations ) ) {
				$this->reset_location( $mode );

				set_transient( 'charitable_square_active_locations_' . $mode, array(), DAY_IN_SECONDS );

				return array();
			}

			$this->set_location( $locations, $mode, $connection );

			set_transient( 'charitable_square_active_locations_' . $mode, $locations, DAY_IN_SECONDS );

			return $locations;
		}

		/**
		 * Fetch Square business locations.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return array|null
		 */
		private function fetch_locations( string $mode ) {

			$connection = Charitable_Square_Connection::get( $mode );

			if ( ! $connection || ! $connection->is_valid() ) {
				if ( charitable_is_debug( 'square' ) ) {
					// phpcs:disable
					error_log( 'fetch_locations - connection is not valid' );
					error_log( print_r( $connection, true ) ); // phpcs:ignore
					// phpcs:enable
				}
				return null;
			}

			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'fetching locations with this connection' );
				error_log( print_r( $connection, true ) ); // phpcs:ignore
				// phpcs:enable
			}

			$api = new Charitable_Square_API( $connection );

			$locations = $api->get_locations();

			if ( ! $locations ) {
				$connection->set_status( Charitable_Square_Connection::STATUS_INVALID );
				$connection->save();
				return null;
			}

			return is_array( $locations ) ? $locations : array( $locations );
		}

		/**
		 * Fetch new connection credentials.
		 *
		 * @since 1.8.7
		 *
		 * @param string $state Unique ID to safely fetch connection data.
		 *
		 * @return array
		 */
		private function fetch_new_connection( string $state ): array {

			$connection = array();
			$response   = wp_remote_post(
				$this->get_server_url() . '/oauth/square-connect',
				array(
					'body'    => array(
						'action' => 'credentials',
						'state'  => $state,
					),
					'timeout' => 30,
				)
			);

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body       = json_decode( wp_remote_retrieve_body( $response ), true );
				$connection = is_array( $body ) ? $body : array();
			}

			return $connection;
		}

		/**
		 * Fetch refresh connection credentials.
		 *
		 * @since 1.8.7
		 *
		 * @param string $token The refresh token.
		 * @param string $mode  Square mode.
		 *
		 * @return array|WP_Error
		 */
		private function fetch_refresh_connection( string $token, string $mode ) {

			$response = wp_remote_post(
				$this->get_server_url() . '/oauth/square-connect',
				array(
					'body'    => array(
						'action'    => 'refresh',
						'live_mode' => absint( $mode === 'live' ),
						'token'     => $token,
					),
					'timeout' => 30,
				)
			);

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return new WP_Error();
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) ) {
				return new WP_Error();
			}

			if ( ! empty( $body['success'] ) ) {
				return $body;
			}

			$error_message = empty( $body['message'] ) ? '' : wp_kses_post( $body['message'] );

			return new WP_Error( 'refresh_connection_fail', $error_message );
		}

		/**
		 * Fetch revoke connection request.
		 *
		 * @since 1.8.7
		 *
		 * @param string $merchant_id The Square merchant ID.
		 * @param string $access_token The access token to revoke.
		 * @param string $mode Square mode (live/test).
		 *
		 * @return array|WP_Error
		 */
		private function fetch_revoke_connection( string $merchant_id, string $access_token, string $mode ) {
			$response = wp_remote_post(
				$this->get_server_url() . '/oauth/square-connect',
				array(
					'body'    => array(
						'action'       => 'revoke',
						'live_mode'    => absint( $mode === 'live' ),
						'merchant_id'  => $merchant_id,
						'access_token' => $access_token,
					),
					'timeout' => 30,
				)
			);

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return new WP_Error( 'revoke_connection_fail', __( 'Failed to revoke Square connection.', 'charitable' ) );
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) ) {
				return new WP_Error( 'revoke_connection_fail', __( 'Invalid response from server.', 'charitable' ) );
			}

			if ( ! empty( $body['success'] ) ) {
				return $body;
			}

			$error_message = empty( $body['message'] ) ? '' : wp_kses_post( $body['message'] );

			return new WP_Error( 'revoke_connection_fail', $error_message );
		}

		/**
		 * Retrieve the connect URL.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 * @param string $redirect_url The URL to redirect to after connecting.
		 *
		 * @return string
		 */
		public function get_connect_url( string $mode, string $redirect_url = '' ) {

			if ( empty( $redirect_url ) ) {

				$redirect_url = add_query_arg(
					array(
						'tab'   => 'gateways',
						'page'  => 'charitable-settings',
						'group' => 'gateways_square_core',
					),
					admin_url( 'admin.php' )
				);

			}

			return add_query_arg(
				array(
					'action'    => 'init',
					'live_mode' => absint( $mode === 'live' ),
					'state'     => uniqid( '', true ),
					'site_url'  => rawurlencode( $redirect_url ),
					'scopes'    => implode( ' ', $this->get_scopes() ),
				),
				$this->get_server_url() . '/oauth/square-connect'
			);
		}


		/**
		 * Retrieve the disconnect URL.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode Square mode.
		 *
		 * @return string
		 */
		public function get_disconnect_url( string $mode ): string {

			$action = 'charitable_square_disconnect';
			$url    = add_query_arg(
				array(
					'action'    => $action,
					'live_mode' => absint( $mode === 'live' ),
				),
				charitable_get_square_settings_page_url()
			);

			return wp_nonce_url( $url, $action );
		}

		/**
		 * Retrieve a connect server URL.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_server_url(): string {

			if ( defined( 'CHARITABLE_SQUARE_LOCAL_CONNECT_SERVER' ) && CHARITABLE_SQUARE_LOCAL_CONNECT_SERVER ) {
				return home_url();
			}

			return self::CHARITABLE_SQUARE_CONNECT_URL;
		}

		/**
		 * Retrieve the connection scopes (permissions).
		 *
		 * @since 1.8.7
		 *
		 * @return array
		 */
		public function get_scopes(): array {

			/**
			 * Filter the connection scopes.
			 *
			 * @since 1.8.7
			 *
			 * @param array $scopes The connection scopes.
			 */
			return (array) apply_filters(
				'charitable_square_admin_connect_get_scopes',
				array(
					'MERCHANT_PROFILE_READ',
					'PAYMENTS_READ',
					'PAYMENTS_WRITE',
					'ORDERS_READ',
					'ORDERS_WRITE',
					'PAYMENTS_WRITE_ADDITIONAL_RECIPIENTS',
				)
			);
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.1.0
		 *
		 * @return Charitable_Square_Connect
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Square_Connect();
			}

			return self::$instance;
		}
	}

endif;
