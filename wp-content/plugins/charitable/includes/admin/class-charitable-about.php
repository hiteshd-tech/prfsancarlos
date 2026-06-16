<?php
/**
 * Charitable About admin page class.
 *
 * @package   Charitable/Classes/Charitable_About
 * @author    David Bisset
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.7.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_About' ) ) :

	/**
	 * Charitable_About
	 *
	 * @since 1.8.7.6
	 */
	class Charitable_About {

		/**
		 * Admin menu page slug.
		 *
		 * @since 1.8.7.6
		 *
		 * @var string
		 */
		const SLUG = 'charitable-about';

		/**
		 * Default view for a page.
		 *
		 * @since 1.8.7.6
		 *
		 * @var string
		 */
		const DEFAULT_TAB = 'about';

		/**
		 * The current active tab.
		 *
		 * @since 1.8.7.6
		 *
		 * @var string
		 */
		public $view;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.7.6
		 */
		public function __construct() {
			$this->hooks();
		}

			/**
	 * Register hooks.
	 *
	 * @since 1.8.7.6
	 */
	private function hooks() {
		// Maybe load about page.
		add_action( 'admin_init', [ $this, 'init' ] );
		// Enqueue assets.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

		/**
		 * Determining if the user is viewing our page, if so, party on.
		 *
		 * @since 1.8.7.6
		 */
		public function init() {
			// Check what page we are on.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			// Only load if we are actually on the about page.
			if ( $page !== self::SLUG ) {
				return;
			}

			// Determine the current active tab.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->view = ! empty( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : self::DEFAULT_TAB;

			// If the user tries to load an invalid view - redirect to About Us.
			$valid_views = [ 'about', 'lite-vs-pro' ];
			if ( ! in_array( $this->view, $valid_views, true ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=charitable-about&view=about' ) );
				exit;
			}

			add_action( 'charitable_admin_page', [ $this, 'output' ] );

			// Hook for addons.
			do_action( 'charitable_admin_about_init' );
		}

		/**
		 * Output the basic page structure.
		 *
		 * @since 1.8.7.6
		 */
		public function output() {
			charitable_admin_view( 'about/about' );
		}

		/**
		 * Enqueue assets for the About page.
		 *
		 * @since 1.8.7.6
		 */
		public function enqueue_assets() {
			// Check if we're on the about page
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			if ( $page !== self::SLUG ) {
				return;
			}

			// Enqueue CSS
			wp_enqueue_style(
				'charitable-admin-about',
				charitable()->get_path( 'assets', false ) . 'css/admin/about-us.css',
				[],
				charitable()->get_version()
			);

			// Enqueue JavaScript
			wp_enqueue_script(
				'charitable-admin-about',
				charitable()->get_path( 'assets', false ) . 'js/admin/about-us.js',
				[ 'jquery' ],
				charitable()->get_version(),
				true
			);

			// Enqueue addons JavaScript
			wp_enqueue_script(
				'charitable-admin-addons',
				charitable()->get_path( 'assets', false ) . 'js/admin/charitable-admin-addons.js',
				[ 'jquery' ],
				charitable()->get_version(),
				true
			);

			// Localize addons script
			wp_localize_script(
				'charitable-admin-addons',
				'charitable_admin_addons',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'charitable_admin_addons' ),
				)
			);
		}

		/**
		 * Get the current view.
		 *
		 * @since 1.8.7.6
		 *
		 * @return string
		 */
		public function get_view() {
			return $this->view;
		}

		/**
		 * Get license data for a specific feature and license level.
		 *
		 * @since 1.8.7.6
		 *
		 * @param string $feature The feature slug.
		 * @param string $license The license level.
		 * @return array|false
		 */
		public function get_license_data( $feature, $license ) {
			$data = $this->get_licenses_data();

			if ( ! isset( $data[ $feature ][ $license ] ) ) {
				return false;
			}

			return $data[ $feature ][ $license ];
		}

		/**
		 * Get all licenses data.
		 *
		 * @since 1.8.7.6
		 *
		 * @return array
		 */
		private function get_licenses_data() {
		return array(
			'campaigns'    => array(
				'lite'  => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Unlimited Campaigns', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Unlimited Campaigns', 'charitable' ) . '</strong>',
					),
				),
			),
			'payment_gateways' => array(
				'lite'  => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Stripe, Square, PayPal Standard and Offline Donations', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Authorize.Net, Windcave, Mollie, Braintree, PayFast, GoCardless, Payrexx, and more', 'charitable' ) . '</strong>',
					),
				),
			),
			'recurring_donations' => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Accept weekly, monthly, quarterly, semiannual, or annual donations', 'charitable' ) . '</strong>',
					),
				),
			),
			'suggested_amounts' => array(
				'lite'  => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Advanced customization & recurring options', 'charitable' ) . '</strong>',
					),
				),
			),
			'fundraising_goals' => array(
				'lite'  => array(
					'status' => 'partial',
					'text'   => array(
						'<strong>' . esc_html__( 'Basic goal display', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Advanced goal tracking with flexible styles & progress bars', 'charitable' ) . '</strong>',
					),
				),
			),
			'peer_to_peer' => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Let supporters create their own fundraising pages & track their progress', 'charitable' ) . '</strong>',
					),
				),
			),
			'ambassadors'  => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Recruit ambassadors & teams to raise funds on your behalf', 'charitable' ) . '</strong>',
					),
				),
			),
			'donation_data' => array(
				'lite'  => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Full donation record management', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Full donation and donor records', 'charitable' ) . '</strong>',
					),
				),
			),
			'donor_comments' => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Allow donors to leave public or private comments', 'charitable' ) . '</strong>',
					),
				),
			),
			'donor_dashboard' => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Donors manage their giving, receipts, and payment methods', 'charitable' ) . '</strong>',
					),
				),
			),
			'pdf_receipts' => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Automatically generate & send professional receipts', 'charitable' ) . '</strong>',
					),
				),
			),
			'fee_relief'   => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Let donors cover processing fees', 'charitable' ) . '</strong>',
					),
				),
			),
			'marketing'    => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'Not Available', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Mailchimp, MailerLite, Campaign Monitor, and more', 'charitable' ) . '</strong>',
					),
				),
			),
			'reporting'    => array(
				'lite'  => array(
					'status' => 'partial',
					'text'   => array(
						'<strong>' . esc_html__( 'Basic reports', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Advancing reports (including LYBUNT, SYBUNT, and detailed donor reports) & insights', 'charitable' ) . '</strong>',
					),
				),
			),
			'extensions'   => array(
				'lite'  => array(
					'status' => 'none',
					'text'   => array(
						'<strong>' . esc_html__( 'None', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'All Pro Extensions included', 'charitable' ) . '</strong>',
					),
				),
			),
			'support'      => array(
				'lite'  => array(
					'status' => 'partial',
					'text'   => array(
						'<strong>' . esc_html__( 'Community Support Only', 'charitable' ) . '</strong>',
					),
				),
				'pro'   => array(
					'status' => 'full',
					'text'   => array(
						'<strong>' . esc_html__( 'Priority Email Support', 'charitable' ) . '</strong>',
					),
				),
			),
		);

			return $data;
		}
	}

endif;
