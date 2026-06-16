<?php
/**
 * Donation amount form model class.
 *
 * @package   Charitable/Classes/Charitable_Donation_Amount_Form
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Amount_Form' ) ) :

	/**
	 * Charitable_Donation_Amount_Form
	 *
	 * @since  1.0.0
	 */
	class Charitable_Donation_Amount_Form extends Charitable_Donation_Form implements Charitable_Donation_Form_Interface {

		/**
		 * The current campaign.
		 *
		 * @since 1.0.0
		 *
		 * @var     Charitable_Campaign
		 */
		protected $campaign;

		/**
		 * Form fields.
		 *
		 * @since 1.0.0
		 *
		 * @var     array
		 */
		protected $form_fields;

		/**
		 * Nonce action.
		 *
		 * @since 1.0.0
		 *
		 * @var     string
		 */
		protected $nonce_action = 'charitable_donation_amount';

		/**
		 * Nonce name.
		 *
		 * @since 1.0.0
		 *
		 * @var     string
		 */
		protected $nonce_name = '_charitable_donation_amount_nonce';

		/**
		 * Action to be executed upon form submission.
		 *
		 * @since 1.0.0
		 *
		 * @var     string
		 */
		protected $form_action = 'make_donation_streamlined';

		/**
		 * Return the donation form fields.
		 *
		 * @since  1.0.0
		 *
		 * @return array[]
		 */
		public function get_fields() {
			return $this->get_donation_fields();
		}

		/**
		 * Validate the form submission.
		 *
		 * @since  1.4.4
		 *
		 * @return boolean
		 */
		public function validate_submission() {
			/* If we have already validated the submission, return the value. */
			if ( $this->validated ) {
				return $this->valid;
			}

			$this->validated = true;

			$this->valid = $this->validate_security_check()
				&& $this->check_required_fields( $this->get_merged_fields() )
				&& $this->validate_amount();

			$this->valid = apply_filters( 'charitable_validate_donation_amount_form_submission', $this->valid, $this );

			return $this->valid;
		}

		/**
		 * Return the donation values.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_donation_values() {
			$submitted = $this->get_submitted_values();

			$values = array(
				'campaign_id' => $submitted['campaign_id'],
				'amount'      => self::get_donation_amount( $submitted ),
			);

			/**
			 * Filter the donation widget form submission amount.
			 *
			 * @since 1.0.0
			 *
			 * @param array                           $values    The donation values, including a campaign_id and amount.
			 * @param array                           $submitted All the submitted values.
			 * @param Charitable_Donation_Amount_Form $form      The form object.
			 */
			return apply_filters( 'charitable_donation_amount_form_submission_values', $values, $submitted, $this );
		}

		/**
		 * Redirect to payment form after submission.
		 *
		 * @since   1.0.0
		 * @version 1.8.8.6
		 *
		 * @param  int $campaign_id The campaign we are donating to.
		 * @param  int $amount      The donation amount.
		 * @return void
		 */
		public function redirect_after_submission( $campaign_id, $amount ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			$redirect_url = charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign_id ) );

			if ( 'same_page' == charitable_get_option( 'donation_form_display', 'separate_page' ) ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				$redirect_url .= '#charitable-donation-form';
			}

			$redirect_url = apply_filters( 'charitable_donation_amount_form_redirect', $redirect_url, $campaign_id, $amount );

			// Check if URL is external and add to allowed hosts if needed.
			$parsed_url = wp_parse_url( $redirect_url );
			if ( ! empty( $parsed_url['host'] ) ) {
				$redirect_host = $parsed_url['host'];
				$site_host     = wp_parse_url( home_url(), PHP_URL_HOST );

				// If redirecting to external host, add it to allowed redirect hosts.
				if ( $redirect_host !== $site_host ) {
					add_filter(
						'allowed_redirect_hosts',
						function( $hosts ) use ( $redirect_host ) {
							if ( ! in_array( $redirect_host, $hosts, true ) ) {
								$hosts[] = $redirect_host;
							}
							return $hosts;
						}
					);
				}
			}

			wp_safe_redirect( esc_url_raw( $redirect_url ) );

			exit();
		}

		/**
		 * Render the donation form.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function render() {
			/* Load the script if it hasn't been loaded yet. */
			if ( ! wp_script_is( 'charitable-script', 'enqueued' ) ) {

				if ( ! class_exists( 'Charitable_Public' ) ) {
					require_once( charitable()->get_path( 'public' ) . 'class-charitable-public.php' );
				}

				Charitable_Public::get_instance()->enqueue_donation_form_scripts();
			}

			charitable_template(
				'donation-form/form-donation.php',
				array(
					'campaign'      => $this->get_campaign(),
					'form_template' => $this->get_form_template(),
					'form'          => $this,
					'form_id'       => 'charitable-donation-amount-form',
				)
			);
		}
	}

endif;
