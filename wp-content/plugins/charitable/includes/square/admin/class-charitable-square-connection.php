<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Square/Classes/Charitable_Square_Connection
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_Connection' ) ) :

	/**
	 * Charitable_Square_Connection
	 *
	 * @since 1.8.7
	 */
	class Charitable_Square_Connection {

		/**
		 * Valid connection status.
		 *
		 * @since 1.8.7
		 */
		const STATUS_VALID = 'valid';

		/**
		 * Invalid connection status.
		 *
		 * @since 1.8.7
		 */
		const STATUS_INVALID = 'invalid';

		/**
		 * Determine if a connection for production mode.
		 *
		 * @since 1.8.7
		 *
		 * @var bool
		 */
		private $live_mode;

		/**
		 * Access token.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		private $access_token;

		/**
		 * Refresh token.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		private $refresh_token;

		/**
		 * Square-issued ID of an application.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		private $client_id;

		/**
		 * Square-issued ID of the merchant.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		private $merchant_id;

		/**
		 * Currency associated with a merchant account.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		private $currency;

		/**
		 * Connection status.
		 *
		 * @since 1.8.7
		 *
		 * @var string
		 */
		private $status;

		/**
		 * Date when tokens should be renewed.
		 *
		 * @since 1.8.7
		 *
		 * @var int
		 */
		private $renew_at;

		/**
		 * Determine if connection tokens are encrypted.
		 *
		 * @since 1.8.7
		 *
		 * @var bool
		 */
		private $encrypted;

		/**
		 * Determine if scopes were updated.
		 *
		 * @since 1.8.7
		 *
		 * @var int
		 */
		private $scopes_updated = 0;

		/**
		 * Connection constructor.
		 *
		 * @since 1.8.7
		 *
		 * @param array $data      Connection data.
		 * @param bool  $encrypted Optional. Default true. Use false when connection tokens were not encrypted.
		 */
		public function __construct( array $data, bool $encrypted = true ) {

			$data   = (array) $data;
			$status = self::STATUS_VALID;

			if ( ! empty( $data['access_token'] ) ) {
				$this->access_token = $data['access_token'];
			}

			if ( ! empty( $data['refresh_token'] ) ) {
				$this->refresh_token = $data['refresh_token'];
			}

			if ( ! empty( $data['client_id'] ) ) {
				$this->client_id = $data['client_id'];
			}

			if ( ! empty( $data['merchant_id'] ) ) {
				$this->merchant_id = $data['merchant_id'];
			}

			if ( ! empty( $data['scopes_updated'] ) ) {
				$this->scopes_updated = $data['scopes_updated'];
			}

			// We must have an access token, client_id, merchant_id and refresh token to be valid.
			if ( empty( $data['access_token'] ) || empty( $data['client_id'] ) || empty( $data['merchant_id'] ) || empty( $data['refresh_token'] ) ) {
				$status = self::STATUS_INVALID;
			}

			$this->set_status( empty( $data['status'] ) ? $status : $data['status'] );

			$this->currency  = empty( $data['currency'] ) ? '' : strtoupper( (string) $data['currency'] );
			$this->renew_at  = empty( $data['renew_at'] ) ? time() : (int) $data['renew_at'];
			$this->live_mode = ! empty( $data['live_mode'] );
			$this->encrypted = (bool) $encrypted;
		}

		/**
		 * Retrieve a connection instance if it exists.
		 *
		 * @since 1.8.7
		 *
		 * @param string $mode      Square mode.
		 * @param bool   $encrypted Optional. Default true. Use false when connection tokens were not encrypted.
		 *
		 * @return Connection|null
		 */
		public static function get( string $mode = '', bool $encrypted = true ) {

			$settings = get_option( 'charitable_settings' );

			if ( '' === $mode ) {
				$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			}

			if ( empty( $settings['gateways_square'][ $mode ] ) ) {
				return null;
			}

			return new self( (array) $settings['gateways_square'][ $mode ], $encrypted );
		}

		/**
		 * Save connection data into DB.
		 *
		 * @since 1.8.7
		 */
		public function save() {
			global $wpdb;

			$settings = get_option( 'charitable_settings' );

			$settings['gateways_square'][ $this->get_mode() ] = $this->get_data();

			$data = array(
				'option_value' => maybe_serialize( $settings ),
			);

			$where = array(
				'option_name' => 'charitable_settings',
			);

			$ret = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->options,
				$data,
				$where,
				array( '%s' ),
				array( '%s' )
			);

			return $ret;
		}

		/**
		 * Delete connection data from DB.
		 *
		 * @since 1.8.7
		 */
		public function delete() {

			$connections = (array) get_option( 'charitable_square_connections', array() );
			unset( $connections[ $this->get_mode() ] );
			empty( $connections ) ? delete_option( 'charitable_square_connections' ) : update_option( 'charitable_square_connections', $connections );

			$settings = get_option( 'charitable_settings' );
			unset( $settings['gateways_square'][ $this->get_mode() ] );
			$ret = update_option( 'charitable_settings', $settings );

			return $ret;
		}

		/**
		 * Revoke tokens from DB.
		 *
		 * @since 1.8.7
		 */
		public function revoke_tokens() {

			$connections = (array) get_option( 'charitable_square_connections', array() );
			$mode        = $this->get_mode();

			$connections[ $mode ]                  = $this->get_data();
			$connections[ $mode ]['access_token']  = '';
			$connections[ $mode ]['refresh_token'] = '';

			update_option( 'charitable_square_connections', $connections );
		}

		/**
		 * Retrieve true if a connection for production mode.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function get_live_mode(): bool {

			return $this->live_mode;
		}

		/**
		 * Retrieve a connection mode.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_mode(): string {

			return $this->live_mode ? 'live' : 'test';
		}

		/**
		 * Retrieve an un-encrypted access token.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_access_token(): string {
			if ( empty( $this->access_token ) ) {
				return '';
			}

			return $this->encrypted ? charitable_crypto_decrypt( $this->access_token ) : $this->access_token;
		}

		/**
		 * Retrieve an un-encrypted refresh token.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_refresh_token(): string {

			return $this->encrypted ? charitable_crypto_decrypt( $this->refresh_token ) : $this->refresh_token;
		}

		/**
		 * Retrieve a client ID.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_client_id(): string {

			if ( empty( $this->client_id ) ) {
				return '';
			}

			return $this->client_id;
		}

		/**
		 * Retrieve an ID of the authorized merchant.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_merchant_id(): string {

			if ( empty( $this->merchant_id ) ) {
				return '';
			}

			return $this->merchant_id;
		}

		/**
		 * Retrieve a currency code of the authorized merchant.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_currency(): string {

			if ( empty( $this->currency ) ) {
				return '';
			}

			return $this->currency;
		}

		/**
		 * Set a currency code.
		 *
		 * @since 1.8.7
		 *
		 * @param string $code Currency code.
		 *
		 * @return Connection
		 */
		public function set_currency( string $code ) {

			$this->currency = strtoupper( $code );

			return $this;
		}

		/**
		 * Retrieve a connection status.
		 *
		 * @since 1.8.7
		 *
		 * @return string
		 */
		public function get_status(): string {

			return $this->status;
		}

		/**
		 * Set a connection status if it valid.
		 *
		 * @since 1.8.7
		 *
		 * @param string $status The connection status.
		 *
		 * @return Connection
		 */
		public function set_status( string $status ) {

			if ( in_array( $status, $this->get_statuses(), true ) ) {
				$this->status = $status;
			}

			return $this;
		}

		/**
		 * Retrieve a renewal timestamp.
		 *
		 * @since 1.8.7
		 *
		 * @return int
		 */
		public function get_renew_at(): int {

			return $this->renew_at;
		}

		/**
		 * Set/update a renewal timestamp.
		 *
		 * @since 1.8.7
		 *
		 * @return Connection
		 */
		public function set_renew_at() {

			// Tokens must automatically renew every 7 days or less.
			$this->renew_at = time() + wp_rand( 5, 8 ) * DAY_IN_SECONDS;

			return $this;
		}

		/**
		 * Retrieve a scopes updated timestamp.
		 *
		 * @since 1.8.7
		 *
		 * @return int
		 */
		public function get_scopes_updated(): int {

			return $this->scopes_updated;
		}

		/**
		 * Set/update a scopes updated timestamp.
		 *
		 * @since 1.8.7
		 *
		 * @return Connection
		 */
		public function set_scopes_updated() {

			$this->scopes_updated = time();

			return $this;
		}

		/**
		 * Encrypt tokens, if it needed.
		 *
		 * @since 1.8.7
		 *
		 * @return Connection
		 */
		public function encrypt_tokens() {

			// Bail if tokens have already encrypted.
			if ( $this->encrypted ) {
				return $this;
			}

			// Bail if tokens are not passed.
			if ( empty( $this->access_token ) || empty( $this->refresh_token ) ) {
				return $this;
			}

			// Prepare encrypted tokens.
			$encrypted_access_token  = charitable_crypto_encrypt( $this->access_token );
			$encrypted_refresh_token = charitable_crypto_encrypt( $this->refresh_token );

			// Bail if encrypted tokens are invalid.
			if ( empty( $encrypted_access_token ) || empty( $encrypted_refresh_token ) ) {
				return $this;
			}

			$this->encrypted     = true;
			$this->access_token  = $encrypted_access_token;
			$this->refresh_token = $encrypted_refresh_token;

			return $this;
		}

		/**
		 * Retrieve available statuses.
		 *
		 * @since 1.8.7
		 *
		 * @return array
		 */
		private function get_statuses(): array {

			return array( self::STATUS_VALID, self::STATUS_INVALID );
		}

		/**
		 * Retrieve a connection in array format, simply like `toArray` method.
		 *
		 * @since 1.8.7
		 *
		 * @return array
		 */
		private function get_data(): array {

			return array(
				'live_mode'      => $this->live_mode,
				'access_token'   => $this->access_token,
				'refresh_token'  => $this->refresh_token,
				'client_id'      => $this->client_id,
				'merchant_id'    => $this->merchant_id,
				'currency'       => $this->currency,
				'status'         => $this->status,
				'renew_at'       => $this->renew_at,
				'scopes_updated' => $this->scopes_updated,
			);
		}

		/**
		 * Determine whether connection tokens is encrypted.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		private function is_encrypted(): bool {

			return $this->encrypted;
		}

		/**
		 * Determine whether a connection is configured fully.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function is_configured(): bool {

			return ! empty( $this->get_access_token() ) && ! empty( $this->get_refresh_token() ) && ! empty( $this->client_id ) && ! empty( $this->merchant_id );
		}

		/**
		 * Determine whether a connection is expired.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function is_expired(): bool {

			return ( $this->renew_at - time() ) < HOUR_IN_SECONDS;
		}

		/**
		 * Determine whether a connection currency is matched with Charitable currency.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function is_currency_matched(): bool {

			if ( empty( $this->currency ) ) {
				$currency       = get_option( 'charitable_square_location_currency_' . $this->get_mode() );
				$this->currency = $currency;
			}

			return $this->currency === strtoupper( charitable_get_currency() );
		}

		/**
		 * Determine whether a connection is valid.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function is_valid(): bool {

			return $this->get_status() === self::STATUS_VALID;
		}

		/**
		 * Determine whether a connection is ready for save.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function is_saveable(): bool {

			return $this->is_configured() && ! $this->is_expired() && $this->is_encrypted();
		}

		/**
		 * Determine whether a connection is ready for use.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function is_usable(): bool {

			return $this->is_configured() && $this->is_valid() && $this->is_currency_matched();
		}
	}

endif;
