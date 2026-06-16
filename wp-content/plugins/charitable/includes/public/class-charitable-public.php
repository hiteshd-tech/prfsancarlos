<?php
/**
 * Charitable Public class.
 *
 * @package   Charitable/Classes/Charitable_Public
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.60
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Public' ) ) :

	/**
	 * Charitable Public class.
	 *
	 * @since 1.0.0
	 */
	final class Charitable_Public {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.2.0
		 *
		 * @var   Charitable_Public|null
		 */
		private static $instance = null;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Public
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'setup_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_donation_form_scripts' ), 11 );
			add_action( 'charitable_campaign_loop_before', array( $this, 'maybe_enqueue_donation_form_scripts' ) );
			add_filter( 'post_class', array( $this, 'campaign_post_class' ) );

			do_action( 'charitable_public_start', $this );
		}

		/**
		 * Loads public facing scripts and stylesheets.
		 *
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function setup_scripts() {
			$min     = charitable_get_min_suffix();
			$version = charitable()->get_version();

			$assets_dir = charitable()->get_path( 'assets', false );

			/* Main Charitable script. */
			$currency = charitable_get_currency_helper();
			$minimum  = charitable_get_minimum_donation_amount( charitable_get_current_campaign_id() );

			if ( $minimum > 0 ) {
				$amount_msg = sprintf(
					/* Translators: %s: minimum donatino amount */
					__( 'You must donate at least %s.', 'charitable' ),
					$currency->get_monetary_amount( $minimum, false, true )
				);
			} else {
				$amount_msg = sprintf(
					/* Translators: %s: minimum donatino amount */
					__( 'You must donate more than %s.', 'charitable' ),
					$currency->get_monetary_amount( $minimum, false, true )
				);
			}

			$maximum          = charitable_get_maximum_donation_amount( charitable_get_current_campaign_id() );
			$max_exceeded_msg = $maximum
				? sprintf(
					/* Translators: %s: maximum donation amount */
					__( 'The maximum donation amount permitted is %s.', 'charitable' ),
					$currency->get_monetary_amount( $maximum, false, true )
				)
				: '';

			/**
			 * Return whether $0 donations are permitted.
			 *
			 * Note that this filter is also called in Charitable_Donation_Form::validate_amount().
			 *
			 * @since 1.2.0
			 *
			 * @param boolean $permitted Whether $0 donations are permitted.
			 */
			$permit_0_donation = apply_filters( 'charitable_permit_0_donation', false );

			/**
			 * Filter the Javascript vars array.
			 *
			 * @since 1.0.0
			 *
			 * @param array $vars The set of vars.
			 */
			$vars = apply_filters(
				'charitable_javascript_vars',
				array(
					'ajaxurl'                       => admin_url( 'admin-ajax.php' ),
					'loading_gif'                   => $assets_dir . 'images/charitable-loading.gif',
					'country'                       => charitable_get_option( 'country' ),
					'currency'                      => charitable_get_currency(),
					'currency_symbol'               => $currency->get_currency_symbol(),
					'currency_format_num_decimals'  => esc_attr( $currency->get_decimals() ),
					'currency_format_decimal_sep'   => esc_attr( $currency->get_decimal_separator() ),
					'currency_format_thousand_sep'  => esc_attr( $currency->get_thousands_separator() ),
					'currency_format'               => esc_attr( $currency->get_accounting_js_format() ), // For accounting.js.
					'minimum_donation'              => $minimum,
					'maximum_donation'              => $maximum,
					'permit_0_donation'             => $permit_0_donation,
					'error_invalid_amount'          => $amount_msg,
					'error_max_exceeded'            => $max_exceeded_msg,
					'error_required_fields'         => __( 'Please fill out all required fields.', 'charitable' ),
					'error_unknown'                 => __( 'Your donation could not be processed. Please reload the page and try again.', 'charitable' ),
					'error_invalid_cc_number'       => __( 'The credit card passed is not valid.', 'charitable' ),
					'error_invalid_cc_expiry'       => __( 'The credit card expiry date is not valid.', 'charitable' ),
					'version'                       => charitable()->get_version(),
					'test_mode'                     => (int) charitable_get_option( 'test_mode' ),
					'error_notice_donation_display' => esc_attr( charitable_get_option( 'donation_form_notices_display' ) ),
				)
			);

			/* Accounting.js */
			wp_register_script(
				'accounting',
				$assets_dir . 'js/libraries/accounting' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			$deps = array( 'accounting', 'jquery' );

			if ( wp_script_is( 'charitable-sessions', 'enqueued' ) ) {
				array_unshift( $deps, 'charitable-sessions' );
			}

			wp_register_script(
				'charitable-script',
				$assets_dir . 'js/charitable' . $min . '.js',
				$deps,
				$version,
				true
			);

			wp_localize_script(
				'charitable-script',
				'CHARITABLE_VARS',
				$vars
			);

			/* Credit card validation */
			wp_register_script(
				'charitable-credit-card',
				$assets_dir . 'js/charitable-credit-card' . $min . '.js',
				array( 'charitable-script' ),
				$version,
				true
			);

			/* URL sanitizer. */
			wp_register_script(
				'charitable-url-sanitizer',
				$assets_dir . 'js/charitable-url-sanitizer' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			/* General forms scripts. */
			wp_register_script(
				'charitable-forms',
				$assets_dir . 'js/charitable-forms' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);

			/* Main styles */
			wp_register_style(
				'charitable-styles',
				$assets_dir . 'css/charitable' . $min . '.css',
				array(),
				$version
			);

			wp_enqueue_style( 'charitable-styles' );

			// 1.8.4.5
			wp_enqueue_style( 'dashicons' );

			/* Lean Modal is registered but NOT enqueued yet. */
			if ( 'modal' == charitable_get_option( 'donation_form_display', 'separate_page' ) ) {

				wp_register_script(
					'lean-modal',
					$assets_dir . 'js/libraries/leanModal' . $min . '.js',
					array( 'jquery' ),
					$version
				);

				wp_register_style(
					'lean-modal-css',
					$assets_dir . 'css/modal' . $min . '.css',
					array(),
					$version
				);

			}

			wp_register_style(
				'charitable-datepicker',
				$assets_dir . 'css/charitable-datepicker' . $min . '.css',
				array(),
				$version
			);

			/* pupload Fields is also registered but NOT enqueued. */
			$upload_vars = array(
				'remove_image'            => _x( 'Remove', 'remove image button text', 'charitable' ),
				'max_file_uploads_single' => /* translators: %d: number of files */ __( 'You can only upload %d file', 'charitable' ),
				'max_file_uploads_plural' => __( 'You can only upload a maximum of %d files', 'charitable' ),
				'max_file_size'           => /* translators: %1$s: file name; %2$s: max upload size */ __( '%1$s exceeds the max upload size of %2$s', 'charitable' ),
				'upload_problem'          => /* translators: %s: file name */ __( '%s failed to upload. Please try again.', 'charitable' ),
			);

			wp_register_script(
				'charitable-plup-fields',
				$assets_dir . 'js/charitable-plupload-fields' . $min . '.js',
				array( 'jquery-ui-sortable', 'wp-ajax-response', 'plupload-all' ),
				$version,
				true
			);

			wp_localize_script(
				'charitable-plup-fields',
				'CHARITABLE_UPLOAD_VARS',
				$upload_vars
			);

			wp_register_style(
				'charitable-plup-styles',
				$assets_dir . 'css/charitable-plupload-fields' . $min . '.css',
				array(),
				$version
			);

			if ( charitable()->load_core_stripe() && class_exists( 'Charitable_Gateway_Stripe_AM' ) ) :

				$gateway  = new Charitable_Gateway_Stripe_AM();
				$keys     = $gateway->get_keys();
				$settings = get_option( 'charitable_settings' );

				// If there is no public key, skip loading Stripe scripts but continue.
				if ( isset( $keys['public_key'] ) && ! empty( $keys['public_key'] ) ) {

					$cc_fields_format = ( ! empty( $settings['gateways_stripe']['cc_fields_format'] ) && '' !== $settings['gateways_stripe']['cc_fields_format'] ) ? $settings['gateways_stripe']['cc_fields_format'] : '';

					$stripe_vars = array(
						'key'              => $keys['public_key'],
						'currency'         => charitable_get_currency(),
						'site_name'        => get_option( 'blogname' ),
						'zero_decimal'     => Charitable_Gateway_Stripe_AM::is_zero_decimal_currency(),
						'cc_fields_format' => esc_attr( $cc_fields_format ),
					);

					/* Register Stripe JS script. */
					wp_register_script(
						'stripe-v3',
						'https://js.stripe.com/v3/',
						array(),
						$version,
						true
					);

					$dependencies = array( 'charitable-script', 'stripe-v3', 'jquery' );

					if ( charitable_get_option( array( 'gateways_stripe', 'enable_stripe_checkout' ) ) ) {
						$stripe_vars['mode'] = 'checkout';
					} else {
						$stripe_vars['mode'] = 'payment-intents';
					}

					wp_register_script(
						'charitable-stripe',
						$assets_dir . 'js/stripe/charitable-stripe' . $min . '.js',
						$dependencies,
						$version,
						true
					);

					wp_localize_script(
						'charitable-stripe',
						'CHARITABLE_STRIPE_VARS',
						$stripe_vars
					);

					if ( 'payment-intents' == $stripe_vars['mode'] ) {
						/**
						 * Prevent the Stripe script from loading site-wide.
						 *
						 * @since 1.4.3
						 *
						 * @param boolean $load Whether to load the scripts sitewide. Defaults to true.
						 */
						if ( apply_filters( 'charitable_stripe_load_stripe_scripts_sitewide', true ) ) {
							wp_enqueue_script( 'stripe-v3' );
						}
					}
				}

			endif;

			/* General forms scripts. */
			wp_register_script(
				'charitable-frontend-v2',
				$assets_dir . 'js/charitable-frontend' . $min . '.js',
				array( 'jquery' ),
				$version,
				true
			);
			wp_enqueue_script( 'charitable-frontend-v2' );
		}

		/**
		 * Conditionally load the donation form scripts if we're viewing the donation form.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean True if scripts were loaded. False otherwise.
		 */
		public function maybe_enqueue_donation_form_scripts() {
			$load = charitable_is_page( 'campaign_donation_page' );

			if ( ! $load ) {
				$load = 'charitable_campaign_loop_before' == current_action() && 'modal' == charitable_get_option( 'donation_form_display', 'separate_page' );
			}

			if ( $load ) {
				$this->enqueue_donation_form_scripts();
			}

			return $load;
		}

		/**
		 * Enqueues the donation form scripts.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function enqueue_donation_form_scripts() {
			wp_enqueue_script( 'charitable-script' );

			if ( Charitable_Gateways::get_instance()->any_gateway_supports( 'credit-card' ) ) {
				wp_enqueue_script( 'charitable-credit-card' );
			}
		}

		/**
		 * Adds custom post classes when viewing campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  string[] $classes List of classes to be added with post_class().
		 * @return string[]
		 */
		public function campaign_post_class( $classes ) {
			$campaign = charitable_get_current_campaign();

			if ( ! $campaign ) {
				return $classes;
			}

			if ( $campaign->has_goal() ) {
				$classes[] = 'campaign-has-goal';
				$classes[] = $campaign->has_achieved_goal() ? 'campaign-has-achieved-goal' : 'campaign-has-not-achieved-goal';
			} else {
				$classes[] = 'campaign-has-no-goal';
			}

			if ( $campaign->is_endless() ) {
				$classes[] = 'campaign-is-endless';
			} else {
				$classes[] = 'campaign-has-end-date';
				$classes[] = $campaign->has_ended() ? 'campaign-has-ended' : 'campaign-has-not-ended';
			}

			return $classes;
		}

		/**
		 * Disable comments on application pages like the donation page.
		 *
		 * @deprecated 2.2.0
		 *
		 * @since  1.3.0
		 * @since  1.6.36 Deprecated. This is now handled in Charitable_Endpoints.
		 *
		 * @param  boolean $open Whether comments are open.
		 * @return boolean
		 */
		public function disable_comments_on_application_pages( $open ) {
			/* If open is already false, just hit return. */
			if ( ! $open ) {
				return $open;
			}

			if ( charitable_is_page( 'campaign_donation_page', array( 'strict' => true ) )
			|| charitable_is_page( 'campaign_widget_page' )
			|| charitable_is_page( 'donation_receipt_page' )
			|| charitable_is_page( 'donation_processing_page' ) ) {
				$open = false;
			}

			return $open;
		}

		/**
		 * Load the template functions after theme is loaded.
		 *
		 * This gives themes time to override the functions.
		 *
		 * @deprecated 2.0.0
		 *
		 * @since  1.2.3
		 * @since  1.6.10 Deprecated
		 *
		 * @return void
		 */
		public function load_template_files() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.6.10',
				'charitable()->load_template_files()'
			);

			charitable()->load_template_files();
		}
	}

endif;
