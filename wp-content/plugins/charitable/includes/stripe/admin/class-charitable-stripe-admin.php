<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Stripe/Classes/Charitable_Stripe_Admin
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

if ( ! class_exists( 'Charitable_Stripe_Admin' ) ) :

	/**
	 * Charitable_Stripe_Admin
	 *
	 * @since 1.1.0
	 */
	class Charitable_Stripe_Admin {

		/**
		 * Single instance of this class.
		 *
		 * @since 1.1.0
		 *
		 * @var   Charitable_Stripe_Admin
		 */
		private static $instance = null;

		/**
		 * Create class object. Private constructor.
		 *
		 * @since 1.1.0
		 */
		public function __construct() {

			/**
			 * Add a direct link to the Extensions settings page from the plugin row.
			 */
			if ( class_exists( 'Charitable' ) ) {
				add_filter( 'plugin_action_links_' . plugin_basename( charitable()->get_path() ), array( $this, 'add_plugin_action_links' ) );
			}

			/**
			 * Add settings to the Privacy tab.
			 */
			add_filter( 'charitable_settings_tab_fields_privacy', array( $this, 'add_stripe_privacy_settings' ) );

			/**
			 * When saving Stripe settings, check for webhook if secret key has changed (when you aren't using Stripe Connect AM)
			 */
			add_filter( 'charitable_save_settings', array( $this, 'save_stripe_settings' ), 10, 3 );

			/**
			 * When connecting Stripe Connect, check for webhook if secret key has changed.
			 */
			add_action( 'wpcharitable_stripe_account_connected', array( $this, 'update_webhook_upon_connection' ), 10, 1 );

			/**
			 * Run webhook signing secret migration on admin_init.
			 */
			add_action( 'admin_init', array( $this, 'maybe_migrate_webhook_signing_secrets' ) );

			/**
			 * Add webhook security status to Stripe gateway settings.
			 */
			add_filter( 'charitable_settings_fields_gateways_gateway_stripe', array( $this, 'add_webhook_security_settings' ), 20 );

			/**
			 * Display admin notice if webhook signature verification failures are detected.
			 */
			add_action( 'admin_notices', array( $this, 'maybe_show_webhook_failure_notice' ) );

			/**
			 * AJAX handler for refreshing webhook signing secret.
			 */
			add_action( 'wp_ajax_charitable_refresh_webhook_signing_secret', array( $this, 'ajax_refresh_webhook_signing_secret' ) );

			/**
			 * Add "Sync Pending Donations" UI to Stripe gateway settings.
			 */
			add_filter( 'charitable_settings_fields_gateways_gateway_stripe', array( $this, 'add_sync_pending_donations_settings' ), 25 );

			/**
			 * AJAX handler for syncing pending Stripe donations.
			 */
			add_action( 'wp_ajax_charitable_sync_pending_stripe_donations', array( $this, 'ajax_sync_pending_stripe_donations' ) );
		}

		/**
		 * Create and return the class object.
		 *
		 * @since  1.1.0
		 *
		 * @return Charitable_Stripe_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Stripe_Admin();
			}

			return self::$instance;
		}

		/**
		 * Add links to activate
		 *
		 * @since  1.1.0
		 *
		 * @param  string[] $links Plugin action links.
		 * @return string[]
		 */
		public function add_plugin_action_links( $links ) {
			if ( Charitable_Gateways::get_instance()->is_active_gateway( 'stripe' ) ) {
				// $links[] = '<a href="' . admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe&default_gateway=true' ) . '">' . __( 'Settings', 'charitable-stripe' ) . '</a>';
			} else {
				$activate_url = esc_url(
					add_query_arg(
						array(
							'charitable_action' => 'enable_gateway',
							'gateway_id'        => 'stripe',
							'_nonce'            => wp_create_nonce( 'gateway' ),
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);

				$links[] = '<a href="' . $activate_url . '">' . __( 'Activate Stripe Gateway', 'charitable' ) . '</a>';
			}

			return $links;
		}

		/**
		 * Add extra settings to the Privacy tab.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $settings The privacy settings.
		 * @return array
		 */
		public function add_stripe_privacy_settings( $settings ) {
			if ( array_key_exists( 'data_retention_fields', $settings ) ) {
				$settings['data_retention_fields']['options']['stripe'] = __( 'Stripe Data', 'charitable' );
			}

			return $settings;
		}

		/**
		 * When Stripe settings are saved, maybe run background processes to set hidden settings.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $values     The submitted values.
		 * @param  array $new_values The new settings.
		 * @param  array $old_values The previous settings.
		 * @return array
		 */
		public function save_stripe_settings( $values, $new_values, $old_values ) {

			if ( charitable_is_debug() ) {
				error_log( 'save_stripe_settings'); // phpcs:ignore
				error_log( print_r( $values, true ) ); // phpcs:ignore
				error_log( print_r( $new_values, true ) ); // phpcs:ignore
				error_log( print_r( $old_values, true ) ); // phpcs:ignore
			}

			/* Bail early if this is not the Stripe settings page. */
			if ( ! array_key_exists( 'gateways_stripe', $values ) ) {
				return $values;
			}

			/* Bail early if Stripe is not an active gateway */
			if ( isset( $values['active_gateways'] ) && ! array_key_exists( 'gateways_stripe', $values['active_gateways'] ) ) {
				return $values;
			}

			/* Add webhooks unless we're on localhost. */
			if ( charitable_is_debug() ) {
				error_log( 'save_stripe_settings midpoint'); // phpcs:ignore
			}

			// a reminder on the charitable_using_stripe_connect check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			if ( function_exists( 'charitable_stripe_should_setup_webhooks' ) && charitable_stripe_should_setup_webhooks() && ! charitable_using_stripe_connect() ) {
				if ( defined( 'CHARITABLE_FORCE_WEBHOOKS_WITHOUT_STRIPE_CONNECT' ) && CHARITABLE_FORCE_WEBHOOKS_WITHOUT_STRIPE_CONNECT ) {
					if ( charitable_is_debug() ) {
						error_log( 'charitable_stripe_should_setup_webhooks exists'); // phpcs:ignore
						error_log( print_r( $values, true ) ); // phpcs:ignore
					}
					$values = $this->setup_webhooks( $values, $new_values, $old_values );
					if ( charitable_is_debug() ) {
						error_log( print_r( $values, true ) ); // phpcs:ignore
						error_log( print_r( $new_values, true ) ); // phpcs:ignore
						error_log( print_r( $old_values, true ) ); // phpcs:ignore
					}
				}
			}

			return $values;
		}

		/**
		 * When Stripe settings are saved, maybe run background processes to set hidden settings.
		 *
		 * @since  1.3.0
		 *
		 * @param  array $account_data The account data.
		 * @return void
		 */
		public function update_webhook_upon_connection( $account_data ) {

			if ( charitable_is_debug() ) {
				// phpcs:disable
				error_log( 'update_webhook_upon_connection 0' );
				error_log( print_r( charitable_stripe_should_setup_webhooks(), true ) );
				error_log( print_r( charitable_using_stripe_connect(), true ) );
				// phpcs:enable
			}

			// a reminder on the charitable_using_stripe_connect check:
			// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
			// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.
			// The wpcharitable_stripe_account_connected action itself guarantees Stripe Connect
			// was just established — charitable_using_stripe_connect() is intentionally omitted
			// here because its option may not yet be written when this action fires.
			if ( function_exists( 'charitable_stripe_should_setup_webhooks' ) && charitable_stripe_should_setup_webhooks() ) {

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'update_webhook_upon_connection: refreshing webhooks for fresh signing secrets' );
					error_log( print_r( $account_data, true ) );
					// phpcs:enable
				}

				// On every Stripe Connect (or reconnect), force fresh webhook creation to guarantee
				// a correct signing secret is stored. refresh_webhook_signing_secret() deletes any
				// existing webhook on Stripe, creates a new one, and saves both the webhook ID and
				// signing secret directly to the DB — avoiding any stale-snapshot overwrite risk.
				// This supersedes the old setup_webhooks() approach for this specific code path.
				$stripe_settings = charitable_get_option( 'gateways_stripe', array() );
				$mode_pairs      = array(
					true  => 'test_secret_key',
					false => 'live_secret_key',
				);

				foreach ( $mode_pairs as $test_mode => $key ) {
					if ( ! empty( $stripe_settings[ $key ] ) ) {
						$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, $stripe_settings[ $key ] );
						$webhook_api->refresh_webhook_signing_secret();
					}
				}

			}
		}

		/**
		 * Set up webhooks after settings are saved.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $values     The submitted values.
		 * @param  array $new_values The new settings.
		 * @param  array $old_values The previous settings.
		 * @return array
		 */
		private function setup_webhooks( $values, $new_values, $old_values ) {
			/* Check whether the stripe_update_hidden_settings upgrade has been completed. */
			$upgrade_log  = get_option( 'charitable_stripe_upgrade_log' );
			$upgrade_done = is_array( $upgrade_log ) && array_key_exists( 'stripe_update_hidden_settings', $upgrade_log );

			$old_settings = $old_values['gateways_stripe'];
			$new_settings = $values['gateways_stripe'];

			$setting_pairs = array(
				'test_secret_key' => true,
				'live_secret_key' => false,
			);

			foreach ( $setting_pairs as $setting_key => $test_mode ) {

				$old = isset( $old_settings[ $setting_key ] ) ? trim( $old_settings[ $setting_key ] ) : false;
				$new = trim( $new_settings[ $setting_key ] );

				/* The secret key is unchanged and the upgrade is done, so no need to do anything. */
				if ( $old == $new && $upgrade_done ) {
					if ( charitable_is_debug() ) {
						// phpcs:disable
						error_log( 'key unchanged' );
						error_log( 'old:' );
						error_log( print_r( $old, true ) );
						error_log( 'new:' );
						error_log( print_r( $new, true ) );
						// phpcs:enable
					}
				}

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'old:' );
					error_log( print_r( $old, true ) );
					error_log( 'new:' );
					error_log( print_r( $new, true ) );
					// phpcs:enable
				}

				/* If the secret key has changed, deactivate the previously stored webhook. */
				if ( $old != $new ) {
					$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, $old );
					$webhook_api->deactivate_webhook();
				}

				/* If the new secret key is blank, set webhook_id to false. */
				if ( '' == $new && isset( $webhook_api->setting_key ) ) {
					$values['gateways_stripe'][ $webhook_api->setting_key ] = false;
					continue;
				}

				/* Finally, if we're still here, add a webhook using the new secret key. */
				$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, $new );

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'webhook_api here' );
					error_log( print_r( $webhook_api, true ) );
					// phpcs:enable
				}

				/* First, check if we have a webhook. */
				$webhook = $webhook_api->get_webhook();

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'webhook here' );
					error_log( print_r( $webhook, true ) );
					// phpcs:enable
				}

				/* We don't have a webhook, so create one. */
				if ( ! $webhook ) {
					// phpcs:disable
					error_log( 'add webhook We do not have a webhook, so create one.' );
					$webhook_id = $webhook_api->add_webhook();
					error_log( 'add webhook' );
					// phpcs:enable

					/* add_webhook() saves the signing secret directly to the DB. Sync it back
					 * into $values now so a subsequent update_option() call with the stale
					 * snapshot does not overwrite the newly-saved secret. */
					$signing_secret_key = $test_mode ? 'test_webhook_signing_secret' : 'live_webhook_signing_secret';
					$fresh_settings     = get_option( 'charitable_settings', array() );
					if ( ! empty( $fresh_settings['gateways_stripe'][ $signing_secret_key ] ) ) {
						$values['gateways_stripe'][ $signing_secret_key ] = $fresh_settings['gateways_stripe'][ $signing_secret_key ];
					}
				} else {
					/* We have a webhook, but it needs to be updated. */
					if ( $webhook_api->webhook_needs_update( $webhook ) ) {
						$webhook_api->update_webhook();
					}
					$webhook_id = $webhook->id;
					if ( charitable_is_debug() ) {
						// phpcs:disable
						error_log( print_r( $webhook_id, true ) );
						// phpcs:enable
					}
				}

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'final testing data' );
					error_log( print_r( $webhook_api->setting_key, true ) );
					error_log( print_r( $webhook_id, true ) );
					// phpcs:enable
				}

				$values['gateways_stripe'][ $webhook_api->setting_key ] = $webhook_id;

				if ( charitable_is_debug() ) {
					// phpcs:disable
					error_log( 'values gateways_stripe updated' );
					error_log( print_r( $values, true ) );
					// phpcs:enable
				}
			}

			/* Mark the upgrade as done. */
			if ( ! $upgrade_done ) {
				if ( ! is_array( $upgrade_log ) ) {
					$upgrade_log = array();
				}

				$upgrade_log['stripe_update_hidden_settings'] = array(
					'time'    => time(),
					'version' => charitable_stripe()->get_version(),
				);

				update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
			}

			return $values;
		}

		/**
		 * Migration: Fetch and store webhook signing secrets for existing webhooks.
		 *
		 * Runs once on upgrade to 1.8.9.8. Re-creates existing webhooks to obtain
		 * and store the signing secret for cryptographic signature verification.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		public function maybe_migrate_webhook_signing_secrets() {
			// Never run during AJAX requests — this migration makes live Stripe API calls
			// and must only run during a real admin page load.
			if ( wp_doing_ajax() ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Stripe webhook classes are only loaded when Stripe is an active gateway.
			if ( ! class_exists( 'Charitable_Stripe_Webhook_API' ) ) {
				return;
			}

			// Charitable_Gateways must be available before calling is_active_gateway().
			if ( ! class_exists( 'Charitable_Gateways' ) ) {
				return;
			}

			$upgrade_log  = get_option( 'charitable_stripe_upgrade_log', array() );
			$upgrade_done = is_array( $upgrade_log ) && array_key_exists( 'stripe_webhook_signing_secrets', $upgrade_log );

			if ( $upgrade_done ) {
				// Retry if migration previously failed.
				$needs_retry = (bool) get_transient( 'charitable_stripe_signing_secret_migration_failed' );

				if ( $needs_retry ) {
					// Throttle retries — don't hammer the Stripe API on every page load.
					if ( get_transient( 'charitable_stripe_migration_retry_cooldown' ) ) {
						return;
					}
				}

				if ( ! $needs_retry ) {
					// Also retry if the current mode has API keys but no signing secret for the
					// direct webhook. Connect webhook failures are non-blocking.
					$test_mode = charitable_get_option( 'test_mode', false );
					$check     = new Charitable_Stripe_Webhook_API( $test_mode, null, false );
					if ( $check->has_api_key() && ! $check->has_signing_secret() ) {
						$needs_retry = true;
					}
				}

				if ( ! $needs_retry ) {
					return;
				}

				unset( $upgrade_log['stripe_webhook_signing_secrets'] );
				update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
			}

			// Check if Stripe gateway is active.
			if ( ! Charitable_Gateways::get_instance()->is_active_gateway( 'stripe' ) ) {
				// Mark as done — no Stripe gateway active, nothing to migrate.
				$this->mark_signing_secret_migration_done( $upgrade_log );
				return;
			}

			// Attempt to refresh signing secrets for all webhook configurations.
			$configurations = array(
				array( 'test_mode' => true, 'connect' => false ),
				array( 'test_mode' => true, 'connect' => true ),
				array( 'test_mode' => false, 'connect' => false ),
				array( 'test_mode' => false, 'connect' => true ),
			);

			$any_refreshed      = false;
			$any_failed         = false;
			$any_connect_failed = false;

			foreach ( $configurations as $config ) {
				$webhook_api = new Charitable_Stripe_Webhook_API( $config['test_mode'], null, $config['connect'] );

				// Skip if we already have a signing secret for this configuration.
				if ( $webhook_api->has_signing_secret() ) {
					continue;
				}

				// Skip if no API keys are configured for this mode — nothing to connect to.
				if ( ! $webhook_api->has_api_key() ) {
					continue;
				}

				$result = $webhook_api->refresh_webhook_signing_secret();

				if ( $result ) {
					$any_refreshed = true;
				} elseif ( $config['connect'] ) {
					// Connect webhook failures are non-blocking — not all sites use Stripe Connect
					// as a platform. Don't count these as migration failures.
					$any_connect_failed = true;
				} else {
					$any_failed = true;
				}
			}

			// Only set failure transient for direct webhook failures, not connect webhook failures.
			if ( $any_failed ) {
				// Set a cooldown so failed migrations don't retry on every page load.
				set_transient( 'charitable_stripe_migration_retry_cooldown', true, HOUR_IN_SECONDS );
				// Store a flag so the admin warning can reference it.
				set_transient( 'charitable_stripe_signing_secret_migration_failed', true, DAY_IN_SECONDS * 30 );
			} else {
				delete_transient( 'charitable_stripe_migration_retry_cooldown' );
				// No direct failures — clear any stale failure transient so the notice goes away.
				delete_transient( 'charitable_stripe_signing_secret_migration_failed' );
			}

			$this->mark_signing_secret_migration_done( $upgrade_log );
		}

		/**
		 * Mark the signing secret migration as completed.
		 *
		 * @since  1.8.9.8
		 *
		 * @param  array $upgrade_log The current upgrade log.
		 * @return void
		 */
		private function mark_signing_secret_migration_done( $upgrade_log ) {
			if ( ! is_array( $upgrade_log ) ) {
				$upgrade_log = array();
			}

			$upgrade_log['stripe_webhook_signing_secrets'] = array(
				'time'    => time(),
				'version' => charitable()->get_version(),
			);

			update_option( 'charitable_stripe_upgrade_log', $upgrade_log );
		}

		/**
		 * Add webhook security status fields to the Stripe settings page.
		 *
		 * Shows an inline warning if no signing secret is stored, and a button
		 * to manually refresh the signing secret.
		 *
		 * @since  1.8.9.8
		 *
		 * @param  array $settings The current Stripe settings fields.
		 * @return array
		 */
		public function add_webhook_security_settings( $settings ) {
			$test_mode = charitable_get_option( 'test_mode', false );
			$has_secret = false;

			// Check if a signing secret exists for the current mode.
			$secret_keys = $test_mode
				? array( 'test_connect_webhook_signing_secret', 'test_webhook_signing_secret' )
				: array( 'live_connect_webhook_signing_secret', 'live_webhook_signing_secret' );

			foreach ( $secret_keys as $key ) {
				if ( ! empty( charitable_get_option( array( 'gateways_stripe', $key ) ) ) ) {
					$has_secret = true;
					break;
				}
			}

			// Only show if Stripe is connected.
			$gateway = new Charitable_Gateway_Stripe_AM();
			if ( ! $gateway->maybe_stripe_connected() ) {
				return $settings;
			}

			$nonce    = wp_create_nonce( 'charitable_refresh_webhook_signing_secret' );
			$ajax_url = admin_url( 'admin-ajax.php' );

			if ( ! $has_secret ) {
				$settings['webhook_security_warning'] = array(
					'type'        => 'content',
					'title'       => __( 'Webhook Security', 'charitable' ),
					'priority'    => 50,
					'content'     => '<div class="charitable-inline-notice warning">'
						. '<p><strong>' . esc_html__( 'Webhook signature verification is not configured.', 'charitable' ) . '</strong></p>'
						. '<p>' . esc_html__( 'Your Stripe webhooks are currently verified using a fallback method. For stronger security, click the button below to enable cryptographic signature verification.', 'charitable' ) . '</p>'
						. '<p><button type="button" class="button" id="charitable-refresh-webhook-secret" data-nonce="' . esc_attr( $nonce ) . '" data-ajax-url="' . esc_url( $ajax_url ) . '">'
						. esc_html__( 'Enable Webhook Signature Verification', 'charitable' )
						. '</button> <span class="spinner" style="float:none;"></span></p>'
						. '<p class="charitable-webhook-secret-result" style="display:none;"></p>'
						. '</div>',
				);
			} else {
				$settings['webhook_security_status'] = array(
					'type'     => 'content',
					'title'    => __( 'Webhook Security', 'charitable' ),
					'priority' => 50,
					'content'  => '<div class="charitable-inline-notice info">'
						. '<p>' . esc_html__( 'Webhook signature verification is active. Incoming Stripe webhooks are cryptographically verified.', 'charitable' ) . '</p>'
						. '<p><button type="button" class="button button-link" id="charitable-refresh-webhook-secret" data-nonce="' . esc_attr( $nonce ) . '" data-ajax-url="' . esc_url( $ajax_url ) . '">'
						. esc_html__( 'Refresh Signing Secret', 'charitable' )
						. '</button> <span class="spinner" style="float:none;"></span></p>'
						. '<p class="charitable-webhook-secret-result" style="display:none;"></p>'
						. '</div>',
				);
			}

			// Add inline JS for the refresh button.
			$settings['webhook_security_script'] = array(
				'type'     => 'content',
				'title'    => '',
				'priority' => 51,
				'content'  => '<script>
					jQuery(function($) {
						$("#charitable-refresh-webhook-secret").on("click", function(e) {
							e.preventDefault();
							var $btn = $(this),
								$spinner = $btn.next(".spinner"),
								$result = $btn.closest("div").find(".charitable-webhook-secret-result");

							$btn.prop("disabled", true);
							$spinner.addClass("is-active");
							$result.hide();

							$.post($btn.data("ajax-url"), {
								action: "charitable_refresh_webhook_signing_secret",
								_wpnonce: $btn.data("nonce")
							}, function(response) {
								$spinner.removeClass("is-active");
								$btn.prop("disabled", false);
								$result.show();
								if (response.success) {
									$result.empty().append($("<span>").text(response.data.message).css("color", "green"));
									if (response.data.reload) {
										setTimeout(function() { location.reload(); }, 1500);
									}
								} else {
									$result.empty().append($("<span>").text(response.data.message).css("color", "red"));
								}
							}).fail(function() {
								$spinner.removeClass("is-active");
								$btn.prop("disabled", false);
								$result.show().empty().append($("<span>").text("' . esc_js( __( 'Request failed. Please try again.', 'charitable' ) ) . '").css("color", "red"));
							});
						});
					});
				</script>',
			);

			return $settings;
		}

		/**
		 * Display admin notice when repeated webhook signature verification failures are detected.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		public function maybe_show_webhook_failure_notice() {
			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				return;
			}

			$failure_count = (int) get_transient( 'charitable_stripe_webhook_verification_failures' );

			if ( $failure_count >= 5 ) {
				$settings_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' );
				?>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( 'Charitable: Stripe Webhook Notice', 'charitable' ); ?></strong>
					</p>
					<p>
						<?php
						printf(
							/* translators: %1$d: number of failures, %2$s: opening Stripe settings link tag, %3$s: closing link tag, %4$s: opening documentation link tag, %5$s: closing link tag. */
							esc_html__( '%1$d webhook events failed signature verification in the last 24 hours. This often happens after reconnecting Stripe or refreshing your account. If the issue persists, verify your signing secret in your %2$sStripe settings%3$s. %4$sLearn more%5$s.', 'charitable' ),
							$failure_count,
							'<a href="' . esc_url( $settings_url ) . '">',
							'</a>',
							'<a href="https://www.wpcharitable.com/documentation/stripe-webhook-setup-and-troubleshooting-in-charitable/" target="_blank">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}

			// Also show migration failure notice.
			if ( get_transient( 'charitable_stripe_signing_secret_migration_failed' ) ) {
				// Before showing the notice, verify that a signing secret is genuinely missing.
				// Use the same check as add_webhook_security_settings() so the notice is always
				// consistent with the "active" status widget on the settings page: if ANY secret
				// (connect or direct) is stored, the UI shows "active" and the failure notice
				// must not appear simultaneously.
				$show_notice = true;
				$test_mode   = charitable_get_option( 'test_mode', false );

				$secret_keys = $test_mode
					? array( 'test_connect_webhook_signing_secret', 'test_webhook_signing_secret' )
					: array( 'live_connect_webhook_signing_secret', 'live_webhook_signing_secret' );

				foreach ( $secret_keys as $key ) {
					if ( ! empty( charitable_get_option( array( 'gateways_stripe', $key ) ) ) ) {
						delete_transient( 'charitable_stripe_signing_secret_migration_failed' );
						$show_notice = false;
						break;
					}
				}

				if ( $show_notice ) {
					$settings_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' );
					?>
					<div class="notice notice-warning is-dismissible">
						<p>
							<strong><?php esc_html_e( 'Charitable: Stripe Webhook Security Setup', 'charitable' ); ?></strong>
						</p>
						<p>
							<?php
							printf(
								/* translators: %1$s: opening link tag, %2$s: closing link tag. */
								esc_html__( 'Charitable was unable to automatically configure webhook signature verification for Stripe. Please visit your %1$sStripe settings%2$s and click "Enable Webhook Signature Verification" to secure your webhook endpoint.', 'charitable' ),
								'<a href="' . esc_url( $settings_url ) . '">',
								'</a>'
							);
							?>
						</p>
					</div>
					<?php
				}
			}
		}

		/**
		 * AJAX handler for refreshing the webhook signing secret.
		 *
		 * @since  1.8.9.8
		 *
		 * @return void
		 */
		public function ajax_refresh_webhook_signing_secret() {
			check_ajax_referer( 'charitable_refresh_webhook_signing_secret' );

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
			}

			$test_mode = charitable_get_option( 'test_mode', false );

			// Webhooks are always registered as direct (non-connect-application) webhooks
			// by setup_webhooks(). Never pass connect_application=true here, regardless of
			// whether the site authenticated via Stripe Connect.
			$webhook_api = new Charitable_Stripe_Webhook_API( $test_mode, null, false );
			$result = $webhook_api->refresh_webhook_signing_secret();

			if ( $result ) {
				// Clear the migration failure transient if it exists.
				delete_transient( 'charitable_stripe_signing_secret_migration_failed' );

				wp_send_json_success(
					array(
						'message' => __( 'Webhook signature verification has been enabled successfully. The page will reload.', 'charitable' ),
						'reload'  => true,
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'Unable to configure webhook signature verification. Please ensure your Stripe API keys are valid and try again, or disconnect and reconnect your Stripe account.', 'charitable' ),
					)
				);
			}
		}

		/**
		 * Add "Sync Pending Donations" UI block to the Stripe gateway settings page.
		 *
		 * Renders a dry-run/live button that checks Stripe for the status of pending
		 * donations made in the last 30 days and marks them completed when Stripe
		 * shows payment succeeded. Processes donations in batches of 5 to avoid
		 * proxy timeouts on sites with many pending donations.
		 *
		 * @since  1.8.10.2
		 * @version 1.8.10.3
		 *
		 * @param  array $settings The current Stripe settings fields.
		 * @return array
		 */
		public function add_sync_pending_donations_settings( $settings ) {
			// Only show if Stripe is connected.
			$gateway = new Charitable_Gateway_Stripe_AM();
			if ( ! $gateway->maybe_stripe_connected() ) {
				return $settings;
			}

			$nonce    = wp_create_nonce( 'charitable_sync_pending_stripe_donations' );
			$ajax_url = admin_url( 'admin-ajax.php' );

			$settings['sync_pending_donations'] = array(
				'type'     => 'content',
				'title'    => __( 'Sync Pending Donations', 'charitable' ),
				'priority' => 55,
				'content'  => '<div class="charitable-inline-notice info">'
					. '<p>' . esc_html__( 'Use this tool if donations are stuck in Pending due to a webhook issue. It checks Stripe for the last 30 days and marks any confirmed payments as completed.', 'charitable' ) . '</p>'
					. '<p><label><input type="checkbox" id="charitable-sync-dry-run" checked> '
					. esc_html__( 'Dry Run (preview only, no changes made)', 'charitable' ) . '</label></p>'
					. '<p><label><input type="checkbox" id="charitable-sync-send-emails"> '
					. esc_html__( 'Send receipt emails for completed donations', 'charitable' ) . '</label></p>'
					. '<p><button type="button" class="button" id="charitable-sync-pending-donations" data-nonce="' . esc_attr( $nonce ) . '" data-ajax-url="' . esc_url( $ajax_url ) . '">'
					. esc_html__( 'Sync Pending Donations', 'charitable' )
					. '</button> <span class="charitable-sync-spinner spinner" style="float:none;"></span></p>'
					. '<p class="charitable-sync-result" style="display:none;"></p>'
					. '</div>',
			);

			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			$settings['sync_pending_donations_script'] = array(
				'type'     => 'content',
				'title'    => '',
				'priority' => 56,
				'content'  => '<script>
jQuery(function($) {
    $("#charitable-sync-pending-donations").on("click", function(e) {
        e.preventDefault();
        var $btn        = $(this),
            $spinner    = $btn.siblings(".charitable-sync-spinner"),
            $result     = $btn.closest("div").find(".charitable-sync-result"),
            dry_run     = $("#charitable-sync-dry-run").is(":checked") ? 1 : 0,
            send_emails = $("#charitable-sync-send-emails").is(":checked") ? 1 : 0,
            ajaxUrl     = $btn.data("ajax-url"),
            nonce       = $btn.data("nonce");

        $btn.prop("disabled", true);
        $spinner.addClass("is-active");
        $result.hide();

        function runBatch(ids_to_process, remaining_ids, total_found, acc) {
            var data = {
                action:         "charitable_sync_pending_stripe_donations",
                _wpnonce:       nonce,
                dry_run:        dry_run,
                send_emails:    send_emails,
                ids_to_process: ids_to_process,
                remaining_ids:  remaining_ids,
                total_found:    total_found
            };
            if (dry_run) {
                data.acc_would_update = acc.would_update;
                data.acc_total_amount = acc.total_amount;
                data.acc_earliest_ts  = acc.earliest_ts;
                data.acc_latest_ts    = acc.latest_ts;
            } else {
                data.acc_updated = acc.updated;
                data.acc_skipped = acc.skipped;
                data.acc_errors  = acc.errors;
            }
            $.post(ajaxUrl, data, function(response) {
                if (!response.success) {
                    $spinner.removeClass("is-active");
                    $btn.prop("disabled", false);
                    $result.show().html("<span style=\"color:red;\">" + response.data.message + "</span>");
                    return;
                }
                var d = response.data;
                if (d.has_more) {
                    var next_acc;
                    if (dry_run) {
                        var bE = d.batch_earliest_ts || 0, bL = d.batch_latest_ts || 0;
                        next_acc = {
                            would_update: acc.would_update + (d.batch_would_update || 0),
                            total_amount: acc.total_amount + (d.batch_total_amount || 0),
                            earliest_ts:  (acc.earliest_ts === 0) ? bE : (bE === 0 ? acc.earliest_ts : Math.min(acc.earliest_ts, bE)),
                            latest_ts:    Math.max(acc.latest_ts, bL)
                        };
                    } else {
                        next_acc = {
                            updated: acc.updated + (d.updated || 0),
                            skipped: acc.skipped + (d.skipped || 0),
                            errors:  acc.errors  + (d.errors  || 0)
                        };
                    }
                    $result.show().html("<span style=\"color:#666;\">" + d.message + "</span>");
                    runBatch(d.next_ids, d.next_remaining, d.total_found, next_acc);
                } else {
                    $spinner.removeClass("is-active");
                    $btn.prop("disabled", false);
                    $result.show().html("<span style=\"color:green;\">" + d.message + "</span>");
                }
            }).fail(function() {
                $spinner.removeClass("is-active");
                $btn.prop("disabled", false);
                $result.show().html("<span style=\"color:red;\">Request failed. Please try again.</span>");
            });
        }

        var initialAcc = dry_run
            ? { would_update: 0, total_amount: 0, earliest_ts: 0, latest_ts: 0 }
            : { updated: 0, skipped: 0, errors: 0 };
        runBatch([], [], 0, initialAcc);
    });
});
</script>',
			);
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

			return $settings;
		}

		/**
		 * AJAX handler: check recent pending Stripe donations against the Stripe API
		 * and mark as completed any where the PaymentIntent status is "succeeded".
		 *
		 * Supports dry-run mode (preview with no DB writes), suppressing receipt
		 * emails, and batched processing (5 donations per request) to avoid proxy
		 * timeouts on sites with many pending donations.
		 *
		 * @since   1.8.10.2
		 * @version 1.8.10.3
		 *
		 * @return void
		 */
		public function ajax_sync_pending_stripe_donations() {
			check_ajax_referer( 'charitable_sync_pending_stripe_donations' );

			if ( charitable_is_debug() ) {
				error_log( '[charitable_sync_pending_stripe_donations] AJAX handler fired.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			if ( ! current_user_can( 'manage_charitable_settings' ) ) {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Permission check failed.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'charitable' ) ) );
				return;
			}

			$dry_run     = ! empty( $_POST['dry_run'] );     // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$send_emails = ! empty( $_POST['send_emails'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// Set up the Stripe API with the current mode's secret key.
			$gateway = new Charitable_Gateway_Stripe_AM();
			if ( ! $gateway->setup_api() ) {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] setup_api() failed — no valid API key.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				wp_send_json_error( array( 'message' => __( 'Unable to connect to Stripe. Please check that your API keys are configured correctly.', 'charitable' ) ) );
				return;
			}

			// Cap each individual Stripe API call at 10 seconds so a slow or unresponsive
			// Stripe endpoint cannot cause the entire AJAX batch request to time out.
			// The SDK default is 80 seconds — far too long for a synchronous admin request.
			\Stripe\HttpClient\CurlClient::instance()->setTimeout( 10 );

			/**
			 * Filter the number of days back to look for pending Stripe donations.
			 *
			 * @since 1.8.10.2
			 *
			 * @param int $days Number of days. Default 30.
			 */
			$days = (int) apply_filters( 'charitable_sync_pending_stripe_donations_days', 30 );
			$days = max( 1, $days );

			// Batch size: number of Stripe API calls per AJAX request.
			$batch_size = 5;

			/**
			 * Filter the maximum number of pending Stripe donations to check per sync run.
			 *
			 * @since 1.8.10.2
			 *
			 * @param int $limit Maximum number. Default 50.
			 */
			$sync_limit = (int) apply_filters( 'charitable_sync_pending_stripe_donations_limit', 50 );
			$sync_limit = max( 1, $sync_limit );

			// -------------------------------------------------------------------------
			// Determine the IDs to process this batch.
			// On the first call ids_to_process is empty — query the DB.
			// On subsequent calls JS sends us the exact IDs to process.
			// -------------------------------------------------------------------------
			if ( ! empty( $_POST['ids_to_process'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$ids_to_process = array_map( 'absint', (array) $_POST['ids_to_process'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$remaining_ids  = ! empty( $_POST['remaining_ids'] ) ? array_map( 'absint', (array) $_POST['remaining_ids'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$total_found    = max( count( $ids_to_process ) + count( $remaining_ids ), absint( $_POST['total_found'] ?? 0 ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Stripe API initialized. Mode: ' . ( $dry_run ? 'DRY RUN' : 'LIVE' ) . '. Querying pending donations (last ' . $days . ' days).' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}

				$query = new WP_Query(
					array(
						'post_type'      => Charitable::DONATION_POST_TYPE,
						'post_status'    => 'charitable-pending',
						'posts_per_page' => $sync_limit,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						'date_query'     => array(
							array(
								'after'     => $days . ' days ago',
								'inclusive' => true,
							),
						),
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'     => '_stripe_payment_intent',
								'value'   => '',
								'compare' => '!=',
							),
						),
					)
				);

				$all_ids = $query->posts;

				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Query found ' . count( $all_ids ) . ' pending donation(s): ' . implode( ', ', $all_ids ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}

				if ( empty( $all_ids ) ) {
					wp_send_json_success( array(
						'message'  => sprintf(
							/* translators: %d: number of days */
							__( 'No pending Stripe donations found in the last %d days.', 'charitable' ),
							$days
						),
						'has_more' => false,
					) );
					return;
				}

				$ids_to_process = array_slice( $all_ids, 0, $batch_size );
				$remaining_ids  = array_slice( $all_ids, $batch_size );
				$total_found    = count( $all_ids );
			}

			$has_more       = ! empty( $remaining_ids );
			$next_ids       = array_slice( $remaining_ids, 0, $batch_size );
			$next_remaining = array_slice( $remaining_ids, $batch_size );

			// Number of donations we will have processed after this batch completes.
			$processed_so_far = $total_found - count( $remaining_ids );

			// -------------------------------------------------------------------------
			// Accumulated stats from previous batches (sent back by JS each call).
			// -------------------------------------------------------------------------
			if ( $dry_run ) {
				$acc_would_update = absint( $_POST['acc_would_update'] ?? 0 );               // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$acc_total_amount = max( 0.0, (float) ( $_POST['acc_total_amount'] ?? 0 ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$acc_earliest_ts  = absint( $_POST['acc_earliest_ts'] ?? 0 );               // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$acc_latest_ts    = absint( $_POST['acc_latest_ts'] ?? 0 );                 // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				$acc_updated = absint( $_POST['acc_updated'] ?? 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$acc_skipped = absint( $_POST['acc_skipped'] ?? 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$acc_errors  = absint( $_POST['acc_errors']  ?? 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}

			// =========================================================================
			// DRY RUN — check Stripe, accumulate stats, no DB writes, no log entries.
			// =========================================================================
			if ( $dry_run ) {
				$batch_would_update = 0;
				$batch_total_amount = 0.0;
				$batch_earliest_ts  = 0;
				$batch_latest_ts    = 0;

				foreach ( $ids_to_process as $donation_id ) {
					$intent_id = get_post_meta( $donation_id, '_stripe_payment_intent', true );
					if ( empty( $intent_id ) ) {
						continue;
					}

					$options    = array();
					$account_id = get_post_meta( $donation_id, '_stripe_account_id', true );
					if ( ! empty( $account_id ) ) {
						$options['stripe_account'] = $account_id;
					}

					try {
						$intent = \Stripe\PaymentIntent::retrieve( $intent_id, $options );

						if ( charitable_is_debug() ) {
							error_log( '[charitable_sync_pending_stripe_donations] Dry run — Donation #' . $donation_id . ' PaymentIntent status: ' . $intent->status ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}

						if ( 'succeeded' === $intent->status ) {
							$batch_would_update++;
							$donation = charitable_get_donation( $donation_id );
							if ( $donation ) {
								$batch_total_amount += (float) $donation->get_total_donation_amount();
							}
							$timestamp = strtotime( (string) get_post_field( 'post_date', $donation_id ) );
							if ( false !== $timestamp ) {
								if ( 0 === $batch_earliest_ts || $timestamp < $batch_earliest_ts ) {
									$batch_earliest_ts = $timestamp;
								}
								if ( $timestamp > $batch_latest_ts ) {
									$batch_latest_ts = $timestamp;
								}
							}
						}
					} catch ( \Exception $e ) {
						if ( charitable_is_debug() ) {
							error_log( '[charitable_sync_pending_stripe_donations] Dry run — Donation #' . $donation_id . ' Stripe API error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				}

				$total_would_update = $acc_would_update + $batch_would_update;
				$total_amount       = $acc_total_amount + $batch_total_amount;
				$earliest_ts        = ( 0 === $acc_earliest_ts ) ? $batch_earliest_ts : ( ( 0 === $batch_earliest_ts ) ? $acc_earliest_ts : min( $acc_earliest_ts, $batch_earliest_ts ) );
				$latest_ts          = max( $acc_latest_ts, $batch_latest_ts );

				if ( $has_more ) {
					$message = sprintf(
						/* translators: 1: checked count, 2: total count */
						__( 'Dry Run: Checking donations... (%1$d of %2$d checked)', 'charitable' ),
						$processed_so_far,
						$total_found
					);
				} elseif ( 0 === $total_would_update ) {
					$message = __( 'Dry Run: No pending donations were found with a confirmed payment in Stripe. No changes were made.', 'charitable' );
				} else {
					$message = sprintf(
						/* translators: 1: count, 2: formatted total, 3: earliest date, 4: latest date */
						__( 'Dry Run: Found %1$d donation(s) that would be updated, totaling %2$s. Date range: %3$s – %4$s. No changes were made. Uncheck "Dry Run" and click again to process.', 'charitable' ),
						$total_would_update,
						charitable_format_money( $total_amount ),
						date_i18n( 'M j', $earliest_ts ),
						date_i18n( 'M j', $latest_ts )
					);
				}

				wp_send_json_success( array(
					'message'            => $message,
					'has_more'           => $has_more,
					'next_ids'           => $next_ids,
					'next_remaining'     => $next_remaining,
					'total_found'        => $total_found,
					'batch_would_update' => $batch_would_update,
					'batch_total_amount' => $batch_total_amount,
					'batch_earliest_ts'  => $batch_earliest_ts,
					'batch_latest_ts'    => $batch_latest_ts,
				) );
				return;
			}

			// =========================================================================
			// LIVE RUN — update statuses and write donation log entries.
			// =========================================================================
			if ( ! $send_emails ) {
				remove_action( 'charitable-completed_donation', array( 'Charitable_Email_Donation_Receipt', 'send_with_donation_id' ) );
				remove_action( 'charitable-completed_donation', array( 'Charitable_Email_New_Donation', 'send_with_donation_id' ) );
			}

			$updated = 0;
			$skipped = 0;
			$errors  = 0;

			foreach ( $ids_to_process as $donation_id ) {
				$intent_id = get_post_meta( $donation_id, '_stripe_payment_intent', true );

				if ( empty( $intent_id ) ) {
					if ( charitable_is_debug() ) {
						error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — no PaymentIntent meta, skipping.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
					$donation = charitable_get_donation( $donation_id );
					if ( $donation ) {
						$donation->log()->add( __( 'Stripe Sync: No PaymentIntent ID found — skipped.', 'charitable' ) );
					}
					$skipped++;
					continue;
				}

				$options    = array();
				$account_id = get_post_meta( $donation_id, '_stripe_account_id', true );
				if ( ! empty( $account_id ) ) {
					$options['stripe_account'] = $account_id;
				}

				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — retrieving PaymentIntent ' . $intent_id . ( ! empty( $account_id ) ? ' (Connect account: ' . $account_id . ')' : '' ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}

				try {
					$intent = \Stripe\PaymentIntent::retrieve( $intent_id, $options );

					if ( charitable_is_debug() ) {
						error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — PaymentIntent status: ' . $intent->status . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}

					if ( 'succeeded' === $intent->status ) {
						$donation = charitable_get_donation( $donation_id );
						if ( ! $donation ) {
							$errors++;
							continue;
						}
						// Guard against double-processing if a webhook completed this donation
						// between the first-call query and this batch.
						if ( 'charitable-completed' === $donation->get_status() ) {
							$skipped++;
							if ( charitable_is_debug() ) {
								error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — already completed (webhook beat us to it), skipping.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
							}
							continue;
						}
						$donation->update_status( 'charitable-completed' );
						$donation->log()->add(
							sprintf(
								/* translators: %s: Stripe PaymentIntent ID. */
								__( 'Stripe Sync: PaymentIntent %s confirmed (succeeded). Status updated to Paid.', 'charitable' ),
								$intent_id
							)
						);
						$updated++;
						if ( charitable_is_debug() ) {
							error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — marked completed.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					} else {
						$donation = charitable_get_donation( $donation_id );
						if ( $donation ) {
							$donation->log()->add(
								sprintf(
									/* translators: 1: Stripe PaymentIntent ID, 2: Stripe status string. */
									__( "Stripe Sync: PaymentIntent %1\$s status '%2\$s' — payment not confirmed. Status remains Pending.", 'charitable' ),
									$intent_id,
									$intent->status
								)
							);
						}
						$skipped++;
						if ( charitable_is_debug() ) {
							error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — skipped (Stripe status: ' . $intent->status . ').' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						}
					}
				} catch ( \Exception $e ) {
					$donation = charitable_get_donation( $donation_id );
					if ( $donation ) {
						$donation->log()->add(
							sprintf(
								/* translators: 1: Stripe PaymentIntent ID, 2: error message. */
								__( 'Stripe Sync: Error retrieving PaymentIntent %1$s — %2$s', 'charitable' ),
								$intent_id,
								$e->getMessage()
							)
						);
					}
					$errors++;
					if ( charitable_is_debug() ) {
						error_log( '[charitable_sync_pending_stripe_donations] Donation #' . $donation_id . ' — Stripe API error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
				}
			}

			if ( ! $send_emails ) {
				add_action( 'charitable-completed_donation', array( 'Charitable_Email_Donation_Receipt', 'send_with_donation_id' ) );
				add_action( 'charitable-completed_donation', array( 'Charitable_Email_New_Donation', 'send_with_donation_id' ) );
			}

			$total_updated = $acc_updated + $updated;
			$total_skipped = $acc_skipped + $skipped;
			$total_errors  = $acc_errors  + $errors;

			if ( $has_more ) {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Batch done. Updated: ' . $updated . ', Skipped: ' . $skipped . ', Errors: ' . $errors . '. Running totals — Updated: ' . $total_updated . ', Skipped: ' . $total_skipped . ', Errors: ' . $total_errors . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$message = sprintf(
					/* translators: 1: processed count, 2: total count */
					__( 'Syncing... %1$d of %2$d donations processed.', 'charitable' ),
					$processed_so_far,
					$total_found
				);
			} else {
				if ( charitable_is_debug() ) {
					error_log( '[charitable_sync_pending_stripe_donations] Done. Updated: ' . $total_updated . ', Skipped: ' . $total_skipped . ', Errors: ' . $total_errors . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$message = sprintf(
					/* translators: 1: updated count, 2: skipped count, 3: error count */
					__( '%1$d donation(s) marked completed. %2$d still pending in Stripe. %3$d error(s).', 'charitable' ),
					$total_updated,
					$total_skipped,
					$total_errors
				);
			}

			wp_send_json_success( array(
				'message'        => $message,
				'has_more'       => $has_more,
				'next_ids'       => $next_ids,
				'next_remaining' => $next_remaining,
				'total_found'    => $total_found,
				'updated'        => $updated,
				'skipped'        => $skipped,
				'errors'         => $errors,
			) );
		}
	}

endif;
