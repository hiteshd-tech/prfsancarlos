<?php
/**
 * Get, update and deactivate webhooks.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Webhook_API
 * @author    Eric Daams
 * @copyright Copyright (c) 2021-2022, WPCharitable
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.3.0
 * @version   1.3.0
 */

// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_var_export

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Stripe_Webhook_API' ) ) :

	/**
	 * Charitable_Stripe_Webhook_API
	 *
	 * @since 1.3.0
	 */
	class Charitable_Stripe_Webhook_API {

		/**
		 * Whether we're in test mode.
		 *
		 * @since 1.3.0
		 *
		 * @var   boolean
		 */
		public $test_mode;

		/**
		 * Secret key.
		 *
		 * @since 1.3.0
		 *
		 * @var   string
		 */
		private $secret_key;

		/**
		 * The webhook setting key, based on whether we're in test mode
		 * and whether this is for the Connect webhook.
		 *
		 * @since 1.3.0
		 *
		 * @var   string
		 */
		private $setting_key;

		/**
		 * Whether this is for the Connect webhook.
		 *
		 * @since 1.3.0
		 *
		 * @var   boolean
		 */
		private $connect_application;

		/**
		 * Gateway helper.
		 *
		 * @since 1.3.0
		 *
		 * @var   Charitable_Gateway_Stripe_AM
		 */
		private $gateway;

		/**
		 * Create class object.
		 *
		 * @since 1.3.0
		 *
		 * @param boolean|null $test_mode           Whether to use test mode or not. If left as
		 *                                          null, the site test mode setting will be used.
		 * @param boolean|null $secret_key          The api login id to use. If left as null, the
		 *                                          stored setting will be used.
		 * @param boolean      $connect_application Whether we want the Connect webhook.
		 */
		public function __construct( $test_mode = null, $secret_key = null, $connect_application = false ) {
			$this->test_mode           = is_null( $test_mode ) ? charitable_get_option( 'test_mode', false ) : $test_mode;
			$this->connect_application = $connect_application;
			$this->secret_key          = is_null( $secret_key ) ? $this->parse_secret_key() : $secret_key;
			$this->setting_key         = $this->parse_setting_key();
			$this->gateway             = new Charitable_Gateway_Stripe_AM();
		}

		/**
		 * Returns class properties.
		 *
		 * @since  1.3.0
		 *
		 * @param  string $prop The property to return.
		 * @return mixed
		 */
		public function __get( $prop ) {
			return isset( $this->$prop ) ? $this->$prop : null;
		}

		/**
		 * Return the set of webhook event types we need to subscribe to.
		 *
		 * @since  1.3.0
		 *
		 * @return array
		 */
		public function get_webhook_events() {
			/**
			 * Filter the events that the webhook will be notified about.
			 *
			 * @since 1.3.0
			 *
			 * @param array $events The events that the webhook will be notified about.
			 */
			return apply_filters(
				'charitable_stripe_webhook_events',
				[
					'charge.refunded',
					'invoice.created',
					'invoice.payment_failed',
					'invoice.payment_succeeded',
					'invoice.payment_action_required',
					'customer.subscription.updated',
					'customer.subscription.deleted',
					'payment_intent.payment_failed',
					'payment_intent.succeeded',
					'checkout.session.completed',
				],
				$this->connect_application
			);
		}

		/**
		 * Return the webhook listener endpoint.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		public function get_webhook_listener() {
			return charitable_get_ipn_url( Charitable_Gateway_Stripe_AM::ID );
		}

		/**
		 * Add a new webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return string The webhook id.
		 */
		public function add_webhook( $idempotency_key = '' ) {
			/**
			 * First check whether the webhook has already been added.
			 */
			$webhook = $this->get_webhook();

			if ( charitable_is_debug() ) {
				error_log( 'add_webook' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( print_r( $webhook, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
			}

			if ( false === $webhook ) {
				try {

					if ( charitable_is_debug() ) {
						error_log( 'add_webook false' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( print_r( $this->secret_key, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
					}

					$this->gateway->setup_api( $this->secret_key );

					if ( charitable_is_debug() ) {
						error_log( 'add_webook after setup_api' );
					}

					$request_opts = ! empty( $idempotency_key ) ? [ 'idempotency_key' => $idempotency_key ] : [];

					$webhook = \Stripe\WebhookEndpoint::create(
						[
							'url'            => $this->get_webhook_listener(),
							'enabled_events' => $this->get_webhook_events(),
							'api_version'    => Charitable_Gateway_Stripe_AM::STRIPE_API_VERSION,
							'connect'        => $this->connect_application,
						],
						$request_opts
					);

					if ( charitable_is_debug() ) {
						error_log( 'add_webook after WebhookEndpoint' );
						error_log( print_r( $webhook, true ) );
					}
					// Override the signing secret, for testing purposes (perhaps with the Stripe API CLI).
					if ( defined( 'CHARITABLE_WEBHOOK_SIGNING_SECRET' ) && CHARITABLE_WEBHOOK_SIGNING_SECRET ) {
						$webhook->secret = CHARITABLE_WEBHOOK_SIGNING_SECRET;
					}
					if ( charitable_is_debug() ) {
						error_log( 'add_webook after WebhookEndpoint UPDATED FOR CLI' );
						error_log( print_r( $webhook, true ) );
					}

					// Store the webhook signing secret for signature verification.
					if ( ! empty( $webhook->secret ) ) {
						$this->save_webhook_signing_secret( $webhook->secret );
					}

				} catch ( Exception $e ) {
					if ( charitable_is_debug() ) {
						error_log(
							sprintf(
								// translators: %s is the error message.
								__( 'Error creating Stripe webhook: %s', 'charitable' ),
								$e->getMessage()
							)
						);
					}

					if ( charitable_is_debug() ) {
						error_log( 'add_webhook ERROR' );
						error_log( print_r( $e->getMessage(), true ) );
					}

					return 'invalid_request';
				}
			}

			if ( charitable_is_debug() ) {
				error_log( 'add_webook returning $webhook->id' );
				error_log( print_r( $webhook->id, true ) );
			}

			return $webhook->id;
		}

		/**
		 * Returns the WebhookEndpoint object, or false if one doesn't exist yet.
		 *
		 * @since  1.3.0
		 *
		 * @return false|\Stripe\WebhookEndpoint
		 */
		public function get_webhook() {
			$webhook_id = charitable_get_option( [ 'gateways_stripe', $this->setting_key ], '' );

			if ( ! $webhook_id ) {
				if ( charitable_is_debug() ) {
					error_log( 'get_webhook ! $webhook_id' );
				}
				return $this->has_webhook();
			}

			try {
				if ( charitable_is_debug() ) {
					error_log( 'get_webhook try setup_api using secret key ' . $this->secret_key );
				}
				$this->gateway->setup_api( $this->secret_key );

				return \Stripe\WebhookEndpoint::retrieve( $webhook_id );
			} catch ( Exception $e ) {
				if ( charitable_is_debug() ) {
					error_log( 'get_webhook try setup_api catch' );
				}
				return false;
			}
		}

		/**
		 * Checks whether a matching webhook already exists within Stripe.
		 *
		 * @since  1.3.0
		 *
		 * @return false|\Stripe\WebhookEndpoint If a webhook exists, returns it.
		 *                                       Otherwise, returns false.
		 */
		public function has_webhook() {
			try {
				$this->gateway->setup_api( $this->secret_key );

				$endpoints = \Stripe\WebhookEndpoint::all( [ 'limit' => 100 ] );

				$endpoint_urls = $this->get_possible_endpoint_urls();

				if ( charitable_is_debug() ) {
					error_log( 'has_webhook endpoint_urls' );
					error_log( print_r( $endpoint_urls, true ) );
					error_log( print_r( $endpoints, true ) );
				}

				foreach ( $endpoints->data as $webhook ) {
					if ( ! in_array( $webhook->url, $endpoint_urls ) ) {
						continue;
					}

					// Skip disabled webhooks — a disabled endpoint cannot receive events and
					// should not prevent creation of a new, active one.
					if ( isset( $webhook->status ) && 'enabled' !== $webhook->status ) {
						continue;
					}

					/**
					 * If we're looking for a Connect application webhook, check that the application
					 * property is not null. Otherwise, make sure it is null.
					 */
					if ( $this->connect_application ? is_null( $webhook->application ) : ! is_null( $webhook->application ) ) {
						continue;
					}

					return $webhook;
				}
			} catch ( Exception $e ) {
				if ( charitable_is_debug() ) {
					error_log( var_export( $e, true ) );
				}

				return false;
			}

			return false;
		}

		/**
		 * Checks if a webhook needs an update.
		 *
		 * @since  1.3.0
		 *
		 * @param  \Stripe\WebhookEndpoint $webhook The webhook endpoint object.
		 * @return boolean
		 */
		public function webhook_needs_update( $webhook ) {
			/* The webhook is not enabled, so it needs an update. */
			if ( 'enabled' != $webhook->status ) {
				return true;
			}

			/* The webhook is not sending some events we need, so we need to update it. */
			if ( ! in_array( '*', $webhook->enabled_events ) && ! empty( array_diff( $this->get_webhook_events(), $webhook->enabled_events ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Update the webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function update_webhook() {
			$webhook = $this->get_webhook();

			if ( ! $webhook ) {
				return false;
			}

			try {
				$this->gateway->setup_api( $this->secret_key );

				$webhook                 = \Stripe\WebhookEndpoint::retrieve( $webhook->id );
				$webhook->disabled       = false;
				$webhook->enabled_events = $this->get_webhook_events();
				$webhook->save();

				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Deactivate the webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return boolean
		 */
		public function deactivate_webhook() {
			$webhook = $this->get_webhook();

			if ( ! $webhook ) {
				return false;
			}

			try {
				$this->gateway->setup_api( $this->secret_key );

				$webhook           = \Stripe\WebhookEndpoint::retrieve( $webhook->id );
				$webhook->disabled = true;
				$webhook->save();
			} catch ( Exception $e ) {
				return false;
			}
		}

		/**
		 * Returns the setting key to use based on whether it's test mode and whether it's for the Connect webhook.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		private function parse_setting_key() {
			if ( $this->test_mode ) {
				return $this->connect_application ? 'test_connect_webhook_id' : 'test_webhook_id';
			}

			return $this->connect_application ? 'live_connect_webhook_id' : 'live_webhook_id';
		}

		/**
		 * Returns the option key for the webhook signing secret.
		 *
		 * @since  1.8.9.8
		 *
		 * @return string
		 */
		private function get_signing_secret_key() {
			if ( $this->test_mode ) {
				return $this->connect_application ? 'test_connect_webhook_signing_secret' : 'test_webhook_signing_secret';
			}

			return $this->connect_application ? 'live_connect_webhook_signing_secret' : 'live_webhook_signing_secret';
		}

		/**
		 * Fetch the signing secret from an existing Stripe webhook and store it locally.
		 *
		 * Note: Stripe only returns the full signing secret at webhook creation time.
		 * For existing webhooks, we must re-create the webhook to get a new secret.
		 *
		 * @since  1.8.9.8
		 *
		 * @return bool True if the signing secret was stored, false otherwise.
		 */
		/**
		 * Delete all Stripe webhook endpoints for this site's listener URL.
		 *
		 * Clears any duplicate endpoints that may have accumulated from repeated
		 * disconnect/reconnect cycles or multiple clicks of the signature verification
		 * button. Also clears the stored webhook ID so add_webhook() starts fresh.
		 *
		 * @since  1.8.10.4
		 *
		 * @return void
		 */
		private function delete_all_webhooks_by_url() {
			try {
				$this->gateway->setup_api( $this->secret_key );

				$endpoints     = \Stripe\WebhookEndpoint::all( [ 'limit' => 100 ] );
				$endpoint_urls = $this->get_possible_endpoint_urls();

				foreach ( $endpoints->data as $webhook ) {
					if ( ! in_array( $webhook->url, $endpoint_urls ) ) {
						continue;
					}

					// Only delete webhooks of our type (direct vs. Connect application).
					if ( $this->connect_application ? is_null( $webhook->application ) : ! is_null( $webhook->application ) ) {
						continue;
					}

					try {
						$webhook->delete();

						if ( charitable_is_debug() ) {
							error_log( 'Charitable Stripe: deleted webhook endpoint ' . $webhook->id . ' during refresh.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					} catch ( \Exception $e ) {
						if ( charitable_is_debug() ) {
							error_log( 'Charitable Stripe: could not delete webhook ' . $webhook->id . ': ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}
			} catch ( \Exception $e ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable Stripe: delete_all_webhooks_by_url failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}

			// Clear the stored webhook ID and signing secret so that add_webhook()
			// creates a fresh endpoint and has_signing_secret() accurately reflects
			// whether a new secret was returned (vs. an idempotency-cached response
			// that omits the secret for an already-deleted endpoint).
			$settings = get_option( 'charitable_settings', array() );
			$changed  = false;

			if ( isset( $settings['gateways_stripe'][ $this->setting_key ] ) ) {
				unset( $settings['gateways_stripe'][ $this->setting_key ] );
				$changed = true;
			}

			$signing_secret_key = $this->get_signing_secret_key();
			if ( isset( $settings['gateways_stripe'][ $signing_secret_key ] ) ) {
				unset( $settings['gateways_stripe'][ $signing_secret_key ] );
				$changed = true;
			}

			if ( $changed ) {
				update_option( 'charitable_settings', $settings );
			}
		}

		public function refresh_webhook_signing_secret() {
			try {
				$this->gateway->setup_api( $this->secret_key );

				// Delete ALL webhook endpoints for our listener URL, not just the stored one.
				// Multiple endpoints accumulate when disconnect/reconnect or this button is clicked
				// repeatedly. Each has a different signing secret; keeping duplicates causes
				// SignatureVerificationException 500s because Charitable can only store one secret.
				$this->delete_all_webhooks_by_url();

				// Generate a time-windowed idempotency key (30-second window) so that
				// concurrent or rapidly-retried requests — e.g. a double-click or a
				// network glitch where Stripe received the request but the response
				// never made it back — map to a single endpoint creation at Stripe.
				// After 30 seconds the window rolls over, so intentional re-runs always
				// get a fresh key and create a new endpoint normally.
				$idempotency_key = 'charitable_wh_create_' . md5(
					home_url() . '|'
					. ( $this->test_mode ? 'test' : 'live' ) . '|'
					. ( $this->connect_application ? 'connect' : 'direct' ) . '|'
					. (string) floor( time() / 30 )
				);

				$new_webhook_id = $this->add_webhook( $idempotency_key );

				if ( 'invalid_request' === $new_webhook_id || empty( $new_webhook_id ) ) {
					return false;
				}

				// Guard against a Stripe idempotency cache hit: when the same key is
				// reused within the 30-second window, Stripe returns the cached creation
				// response but omits ->secret (only returned on true first creation).
				// delete_all_webhooks_by_url() already cleared the old secret, so if
				// has_signing_secret() is still false here it means no new secret was
				// stored — the webhook ID would be stale/deleted. Return false so the
				// caller sees a failure rather than persisting a broken state.
				if ( ! $this->has_signing_secret() ) {
					return false;
				}

				// add_webhook() already stores the signing secret and returns the new ID.
				// Update the stored webhook ID.
				$settings = get_option( 'charitable_settings', array() );

				if ( ! isset( $settings['gateways_stripe'] ) ) {
					$settings['gateways_stripe'] = array();
				}

				$settings['gateways_stripe'][ $this->setting_key ] = $new_webhook_id;
				update_option( 'charitable_settings', $settings );

				return true;

			} catch ( \Exception $e ) {
				if ( charitable_is_debug() ) {
					error_log( 'Charitable: Failed to refresh webhook signing secret: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				return false;
			}
		}

		/**
		 * Check if an API key is configured for this webhook's mode.
		 *
		 * Uses an internal check to avoid __get() returning null for private
		 * properties in some PHP 8.x multisite contexts.
		 *
		 * @since  1.8.10.1
		 *
		 * @return bool
		 */
		public function has_api_key() {
			return ! empty( $this->secret_key );
		}

		/**
		 * Check if a webhook signing secret is stored for this webhook.
		 *
		 * @since  1.8.9.8
		 *
		 * @return bool
		 */
		public function has_signing_secret() {
			$secret = charitable_get_option( array( 'gateways_stripe', $this->get_signing_secret_key() ) );
			return ! empty( $secret );
		}

		/**
		 * Save the webhook signing secret to Charitable options.
		 *
		 * @since  1.8.9.8
		 *
		 * @param  string $secret The webhook signing secret from Stripe.
		 * @return void
		 */
		public function save_webhook_signing_secret( $secret ) {
			$settings = get_option( 'charitable_settings', array() );

			if ( ! isset( $settings['gateways_stripe'] ) ) {
				$settings['gateways_stripe'] = array();
			}

			$settings['gateways_stripe'][ $this->get_signing_secret_key() ] = $secret;

			update_option( 'charitable_settings', $settings );
		}

		/**
		 * Return the secret key to use based on whether it's test mode.
		 *
		 * @since  1.3.0
		 *
		 * @return string
		 */
		private function parse_secret_key() {
			$setting = $this->test_mode ? 'test_secret_key' : 'live_secret_key';

			return charitable_get_option( [ 'gateways_stripe', $setting ] );
		}

		/**
		 * Return all possible webhook URLs.
		 *
		 * @since  1.3.0
		 *
		 * @return string[]
		 */
		private function get_possible_endpoint_urls() {
			$home_url = home_url();

			return [
				sprintf( '%s/charitable-listener/%s', untrailingslashit( $home_url ), Charitable_Gateway_Stripe_AM::ID ),
				esc_url_raw( add_query_arg( [ 'charitable-listener' => Charitable_Gateway_Stripe_AM::ID ], trailingslashit( $home_url ) ) ),
				esc_url_raw( add_query_arg( [ 'charitable-listener' => Charitable_Gateway_Stripe_AM::ID ], untrailingslashit( $home_url ) ) ),
			];
		}
	}

endif;
