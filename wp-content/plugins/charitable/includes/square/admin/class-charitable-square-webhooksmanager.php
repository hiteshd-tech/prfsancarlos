<?php
/**
 * The class responsible for adding & saving extra settings in the Charitable admin.
 *
 * @package   Charitable Square/Classes/Charitable_Square_WebhooksManager
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Square_WebhooksManager' ) ) :

	/**
	 * Webhooks Manager.
	 *
	 * @since 1.8.7
	 */
	class Charitable_Square_WebhooksManager {

		/**
		 * Square client.
		 *
		 * @since 1.8.7
		 *
		 * @var SquareClient
		 */
		private $client;

		/**
		 * Create webhook endpoint.
		 * Retrieve the existing one when the endpoint already exists.
		 *
		 * @since 1.8.7
		 */
		public function connect() {

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'testing for reval - Charitable_Square_WebhooksManager::connect' );
				// phpcs:enable
			}

			// Security and permissions check.
			if (
				! check_ajax_referer( 'charitable-admin', 'nonce', false ) ||
				! current_user_can( 'manage_options' )
			) {
				wp_send_json_error( array( 'message ' => esc_html__( 'You are not allowed to perform this action', 'charitable' ) ) );
			}

			$personal_access_token = ! empty( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

			if ( empty( $personal_access_token ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Personal access token is required.', 'charitable' ) ) );
			}

			$webhook = $this->create( $personal_access_token );

			// Register webhook health check.
			( new Charitable_Square_WebhooksHealthCheck() )->maybe_schedule_check();

			// Store endpoint ID and secret.
			if ( ! empty( $webhook ) ) {
				$this->save_settings( $webhook );

				wp_send_json_success( array( 'message' => esc_html__( 'Webhook created successfully!', 'charitable' ) ) );
			}

			wp_send_json_error( array( 'message' => esc_html__( 'Failed to create webhook.', 'charitable' ) ) );
		}

		/**
		 * Create or update a webhook endpoint.
		 *
		 * @since 1.8.7
		 *
		 * @param string $personal_access_token Personal access token.
		 *
		 * @return array Endpoint ID and secret.
		 */
		private function create( string $personal_access_token ): array {

			$this->client = new \Square\SquareClient(
				token: $personal_access_token,
				options: array(
					'baseUrl' => charitable_get_option( 'test_mode' ) ? \Square\Environments::Sandbox->value : \Square\Environments::Production->value,
				)
			);

			// Check if the webhook already exists.
			$existing_webhook = $this->webhook_exists();

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'testing for reval - Charitable_Square_WebhooksManager::create' );
				error_log( 'creating webhook - existing webhook: ' . print_r( $existing_webhook, true ) );
				// phpcs:enable
			}

			// Prepare a webhook subscription object.
			$webhook_subscription = new \Square\Types\WebhookSubscription();
			$webhook_subscription->setName( sprintf( 'WPCharitable endpoint (%1$s mode)', charitable_get_option( 'test_mode' ) ? 'test' : 'live' ) );
			$webhook_subscription->setNotificationUrl( Charitable_Square_Helpers::get_webhook_url() );
			// $webhook_subscription->setEventTypes( WebhookRoute::get_webhooks_events_list() );

			// Updated to include all events.
			$webhook_subscription->setEventTypes(
				array(
					'payment.created',
					'payment.updated',
					'refund.updated',
					'subscription.created',
					'subscription.updated',
					'invoice.payment_made',
					'oauth.authorization.revoked',
				)
			);

			if ( $existing_webhook ) {
				try {
					// Create an update request and set the subscription payload.
					$request = new \Square\Webhooks\Subscriptions\Requests\UpdateWebhookSubscriptionRequest(
						array(
							'subscriptionId' => $existing_webhook['id'],
							'subscription'   => $webhook_subscription,
						)
					);

					// Update the existing webhook subscription.
					$response = $this->client->webhooks->subscriptions->update( $request );

					// Check for errors instead of using isSuccess().
					if ( empty( $response->getErrors() ) ) {
						// Get subscription directly from the response.
						$subscription = $response->getSubscription();

						// Check if subscription is not null before accessing methods.
						if ( $subscription ) {
							return array(
								'id'            => $subscription->getId(),
								'signature_key' => $existing_webhook['signature_key'] ?? '', // getSignatureKey() isn't available in the update response, fall back to the existing webhook's signature key.
							);
						}
					}
					// If the update fails or subscription is null, return the existing webhook details.
					return $existing_webhook;
				} catch ( Exception $e ) {
					// Log the exception or handle it appropriately.
					if ( charitable_is_debug( 'webhook' ) ) {
						// phpcs:disable
						error_log( 'Error updating Square webhook: ' . $e->getMessage() );
						// phpcs:enable
					}
					return $existing_webhook;
				}
			}

			// Create a new webhook subscription if none exists.
			$request = new \Square\Webhooks\Subscriptions\Requests\CreateWebhookSubscriptionRequest(
				array(
					'subscription'   => $webhook_subscription,
					'idempotencyKey' => uniqid(),
				)
			);

			try {
				// Create the webhook subscription.
				$response = $this->client->webhooks->subscriptions->create( $request );

				// Check for errors instead of using isSuccess().
				if ( empty( $response->getErrors() ) ) {
					// Get subscription directly from the response.
					$subscription = $response->getSubscription();

					// Check if subscription is not null before accessing methods.
					if ( $subscription ) {
						return array(
							'id'            => $subscription->getId(),
							'signature_key' => $subscription->getSignatureKey(),
						);
					}
				}

				// Log error if webhook creation failed or subscription is null.
				if ( charitable_is_debug( 'webhook' ) ) {
					// phpcs:disable
					error_log( 'Failed to create Square webhook or subscription data is missing. Errors: ' . print_r( $response->getErrors(), true ) );
					// phpcs:enable
				}
				return array();
			} catch ( Exception $e ) {
				// Log the exception or handle it appropriately.
				if ( charitable_is_debug( 'webhook' ) ) {
					// phpcs:disable
					error_log( 'Error creating Square webhook: ' . $e->getMessage() );
					// phpcs:enable
				}
				return array();
			}
		}

		/**
		 * Check if webhook already exists.
		 *
		 * @since 1.8.7
		 *
		 * @return array
		 */
		private function webhook_exists(): array {

			try {
				$pager = $this->client->webhooks->subscriptions->list();

				// Get all subscriptions from the pager.
				$subscriptions = array();
				foreach ( $pager as $subscription ) {
					$subscriptions[] = $subscription;
				}

				/*
				(
					[0] => Square\Types\WebhookSubscription Object
						(
							[__additionalProperties:Square\Core\Json\JsonSerializableType:private] => Array
								(
								)

							[id:Square\Types\WebhookSubscription:private] => wbhk_341dd1cb2984412e806931f760732883
							[name:Square\Types\WebhookSubscription:private] => testing-gamma-long-url
							[enabled:Square\Types\WebhookSubscription:private] => 1
							[eventTypes:Square\Types\WebhookSubscription:private] => Array
								(
									[0] => customer.created
									[1] => customer.deleted
									[2] => customer.updated
									[3] => invoice.canceled
									[4] => invoice.created
									[5] => invoice.deleted
									[6] => invoice.payment_made
									[7] => invoice.published
									[8] => invoice.refunded
									[9] => invoice.scheduled_charge_failed
									[10] => invoice.updated
									[11] => order.created
									[12] => order.updated
									[13] => payment.created
									[14] => payment.updated
									[15] => refund.created
									[16] => refund.updated
									[17] => subscription.created
									[18] => subscription.updated
								)

							[notificationUrl:Square\Types\WebhookSubscription:private] => https://a9c6-2600-1700-d30-876f-c8b3-3e76-41fe-961d.ngrok-free.app?charitable-listener=square
							[apiVersion:Square\Types\WebhookSubscription:private] => 2025-03-19
							[signatureKey:Square\Types\WebhookSubscription:private] =>
							[createdAt:Square\Types\WebhookSubscription:private] => 2025-04-14 15:49:23 +0000 UTC
							[updatedAt:Square\Types\WebhookSubscription:private] => 2025-04-16 15:52:08 +0000 UTC
						)

				)
				*/

				if ( empty( $subscriptions ) ) {
					return array();
				}

				$endpoint_urls = $this->get_possible_endpoint_urls();

				foreach ( $subscriptions as $subscription ) {
					if ( ! in_array( $subscription->getNotificationUrl(), $endpoint_urls, true ) ) {
						continue;
					}

					// Found existing webhook matching one of the possible URLs.
					return array(
						'id'            => $subscription->getId(),
						'signature_key' => $subscription->getSignatureKey(),
					);
				}
			} catch ( Exception $e ) {
				// Log the exception or handle it appropriately.
				if ( charitable_is_debug() ) {
					error_log( 'Error listing Square webhooks: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				return array();
			}

			return array();
		}

		/**
		 * Remove a webhook.
		 *
		 * @since 1.8.7
		 *
		 * @param string $personal_access_token Personal access token.
		 * @param string $webhook_id Webhook ID.
		 *
		 * @return bool True if webhook was successfully deleted, false otherwise.
		 */
		public function remove_webhook( string $personal_access_token, string $webhook_id ) {
			try {
				$this->client = new \Square\SquareClient(
					token: $personal_access_token,
					options: array(
						'baseUrl' => charitable_get_option( 'test_mode' ) ? \Square\Environments::Sandbox->value : \Square\Environments::Production->value,
					)
				);

				// Create delete request with the webhook subscription ID.
				$request = new \Square\Webhooks\Subscriptions\Requests\DeleteSubscriptionsRequest(
					array(
						'subscriptionId' => $webhook_id,
					)
				);

				// Send the delete request.
				$response = $this->client->webhooks->subscriptions->delete( $request );

				// Check if there were any errors.
				if ( empty( $response->getErrors() ) ) {
					return true;
				}

				// Log errors if any occurred.
				if ( charitable_is_debug( 'square' ) ) {
					error_log( 'Error deleting Square webhook: ' . print_r( $response->getErrors(), true ) ); // phpcs:ignore
				}
				return false;

			} catch ( Exception $e ) {
				// Log any exceptions that might occur.
				if ( charitable_is_debug( 'square' ) ) {
					error_log( 'Exception deleting Square webhook: ' . $e->getMessage() ); // phpcs:ignore
				}
				return false;
			}
		}

		/**
		 * Save webhook settings.
		 *
		 * @since 1.8.7
		 *
		 * @param array $webhook Webhook endpoint.
		 */
		private function save_settings( array $webhook ) {

			$mode     = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
			$settings = (array) get_option( 'charitable_settings', array() );

			// Save webhooks endpoint ID and secret.
			$settings['gateways_square'][ $mode ]['webhooks-id']     = sanitize_text_field( $webhook['id'] );
			$settings['gateways_square'][ $mode ]['webhooks-secret'] = sanitize_text_field( $webhook['signature_key'] );
			// Ensure webhooks are enabled.
			$settings['gateways_square']['square_enable_webhooks'] = 1;

			( new Charitable_Square_WebhooksHealthCheck() )->save_status( Charitable_Square_WebhooksHealthCheck::ENDPOINT_OPTION, Charitable_Square_WebhooksHealthCheck::STATUS_OK );

			update_option( 'charitable_settings', $settings );
		}

		/**
		 * Return all possible webhook URLs.
		 *
		 * @since  1.8.7
		 *
		 * @return string[]
		 */
		private function get_possible_endpoint_urls() {
			$home_url = home_url();

			if ( charitable_is_debug( 'webhook' ) ) {
				// phpcs:disable
				error_log( 'get_possible_endpoint_urls in the webhooks manager' );
				// phpcs:enable
			}

			return apply_filters(
				'charitable_square_webhook_urls',
				array(
					sprintf( '%s/charitable-listener/%s', untrailingslashit( $home_url ), Charitable_Gateway_Square::ID ),
					esc_url_raw( add_query_arg( array( 'charitable-listener' => Charitable_Gateway_Square::ID ), trailingslashit( $home_url ) ) ),
					esc_url_raw( add_query_arg( array( 'charitable-listener' => Charitable_Gateway_Square::ID ), untrailingslashit( $home_url ) ) ),
				)
			);
		}
	}

endif;
