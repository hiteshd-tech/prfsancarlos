<?php
/**
 * The class responsible for helping us understand the response to a Square payment request.
 *
 * @package   \Charitable\Pro\Square\Gateway\Payment\Response
 * @author    Studio 164a
 * @copyright Copyright (c) 2022, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Response object after making payment request.
 *
 * @since 1.0.0
 */
class Charitable_Square_Payment_Response {

	/**
	 * The response data.
	 *
	 * @since 1.0.0
	 *
	 * @var   object
	 */
	private $response;

	/**
	 * Set up the response object.
	 *
	 * @since 1.0.0
	 *
	 * @param object $response The response data.
	 */
	public function __construct( $response ) {
		$this->response = $response;
	}

	/**
	 * Return the gateway transaction id.
	 *
	 * @since  1.8.7
	 *
	 * @return string
	 */
	public function get_gateway_transaction_id() {
		$id = null;

		if ( isset( $this->response->checkout->order ) ) {
			$id = $this->response->checkout->order->id;
		} elseif ( isset( $this->response->payment ) ) {
			$id = $this->response->payment->order_id;
		}

		return $id;
	}

	/**
	 * Return the gateway payment id
	 *
	 * @since  1.8.7
	 *
	 * @return string|false
	 */
	public function get_gateway_payment_id() {
		$id = null;

		if ( isset( $this->response->checkout->payment ) ) {
			$id = $this->response->checkout->payment->id;
		} elseif ( isset( $this->response->payment ) ) {
			$id = $this->response->payment->id;
		}

		return $id;
	}

	/**
	 * Return the gateway transaction url
	 *
	 * @since  1.0.0
	 *
	 * @return string|false
	 */
	public function get_gateway_transaction_url() {
		if ( ! $this->get_gateway_transaction_id() ) {
			return '';
		}
		$domain          = $this->is_sandbox() ? 'https://squareupsandbox.com/' : 'https://squareup.com/';
		$transaction_url = $domain . 'dashboard/sales/transactions/' . $this->get_gateway_transaction_id();
		return $transaction_url;
	}

	/**
	 * Return the gateway subscription id
	 *
	 * @since  1.8.7
	 *
	 * @return string|false
	 */
	public function get_gateway_subscription_id() {
		$id = null;

		if ( isset( $this->response->subscription->id ) ) {
			$id = $this->response->subscription->id;
		}

		return $id;
	}

	/**
	 * Return the gateway plan variation id
	 *
	 * @since  1.8.7
	 *
	 * @return string|false
	 */
	public function get_gateway_plan_variation_id() {
		$id = null;

		if ( isset( $this->response->subscription->plan_variation_id ) ) {
			$id = $this->response->subscription->plan_variation_id;
		}

		return $id;
	}

	/**
	 * Checks if this is a sandbox transaction or not
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	private function is_sandbox() {
		$checkout_page_url = isset( $this->response->checkout->checkout_page_url ) ? $this->response->checkout->checkout_page_url : '';
		return str_contains( $checkout_page_url, 'sandbox' );
	}

	/**
	 * Returns whether the payment requires some further action.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function payment_requires_action() {
		return false;
	}

	/**
	 * Get any data that is needed to perform the required action.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_required_action_data() {
		return array();
	}

	/**
	 * Whether the payment requires a redirect to a payment page.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function payment_requires_redirect() {
		return true;
	}

	/**
	 * The URL to redirect the donor to, or null if not required.
	 *
	 * @since  1.0.0
	 *
	 * @param int $donation_id The donation ID.
	 * @return string|null
	 */
	public function get_redirect( $donation_id = false ) {
		// If it's recurring we need to add in the recurring meta as well.
		if ( charitable_is_debug() ) {
			// phpcs:disable
			error_log( 'charitable_square_payment_response - get_redirect' );
			error_log( 'the donation id is ' );
			error_log( print_r( $donation_id, true ) ); // David
			error_log( 'the response is ' );
			error_log( print_r( $this->response, true ) ); // David
			// phpcs:enable
		}
		if ( $donation_id ) {
			$checkout_page_url = home_url( 'donation-receipt/' . intval( $donation_id ) . '/' );
		} else {
			$checkout_page_url = isset( $this->response->checkout->checkout_page_url ) ? $this->response->checkout->checkout_page_url : home_url();
		}
		return $checkout_page_url;
	}

	/**
	 * Returns whether the payment failed.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function payment_failed() {
		if ( isset( $this->response->status ) ) {
			return 'failed' === $this->response->status;
		}
	}

	/**
	 * Returns whether the payment was completed.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function payment_completed() {
		if ( isset( $this->response->status ) ) {
			return 'paid' === $this->response->status;
		}
	}

	/**
	 * Returns whether the payment was cancelled.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	public function payment_cancelled() {
		if ( isset( $this->response->status ) ) {
			return in_array(
				$this->response->status,
				array( 'canceled', 'expired' ),
				true
			);
		}
	}

	/**
	 * Returns any log messages to be added for the payment.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_logs() {
		return array();
	}

	/**
	 * Returns any meta data to be recorded for the payment, beyond
	 * the gateway transaction id.
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_meta() {
		return array();
	}
}
