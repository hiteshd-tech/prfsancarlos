<?php
/**
 * Contains the class that is used to register and retrieve notices in the admin like errors, warnings, success messages, etc.
 *
 * @package   Charitable/Classes/Charitable_Admin_Notices
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.6
 * @version   1.6.24
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Notices' ) ) :

	/**
	 * Charitable_Admin_Notices
	 *
	 * @since 1.4.6
	 */
	class Charitable_Admin_Notices extends Charitable_Notices {

		/**
		 * Whether the script has been enqueued.
		 *
		 * @since 1.4.6
		 *
		 * @var   boolean
		 */
		private $script_enqueued;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.4.6
		 *
		 * @return Charitable_Admin_Notices
		 */
		public static function get_instance() {
			return charitable()->registry()->get( 'admin_notices' );
		}

		/**
		 * Create class object. A private constructor, so this is used in a singleton context.
		 *
		 * @since 1.4.6
		 * @version 1.5.4 Access changed to public.
		 * @version 1.8.0 Added the 'render_campaign_builder_notice' notice.
		 * @version 1.8.1 Added 'render_dashboard_reporting_notice' and 'dismiss_dashboard_reporting_notice'. Removed 'render_campaign_builder_notice' notice.
		 * @version 1.8.1.12 Added 'render_license_expiring_banner'.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->load_notices();

			add_action( 'admin_notices', array( $this, 'render_upgrade_banner' ) );
			add_action( 'admin_notices', array( $this, 'render_license_expiring_banner' ) );
			add_action( 'admin_notices', array( $this, 'render_license_expired_banner' ) );
			add_action( 'admin_notices', array( $this, 'render_five_star_rating' ) );
			add_action( 'admin_notices', array( $this, 'render_square_connection_error' ) );
			add_action( 'charitable_dismiss_notice', array( $this, 'dismiss_five_star_notice' ), 10, 1 );
			add_action( 'charitable_dismiss_notice', array( $this, 'dismiss_campaign_builder_notice' ), 10, 1 );
			add_action( 'charitable_dismiss_notice', array( $this, 'dismiss_dashboard_reporting_notice' ), 10, 1 );
			add_action( 'charitable_dismiss_notice', array( $this, 'dismiss_square_connection_error_notice' ), 10, 1 );
			add_filter( 'charitable_localized_javascript_vars', array( $this, 'render_campaign_upgrade_banner_html' ), 10, 1 );
		}

		/**
		 * Adds a notice message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message The message to display.
		 * @param  string  $type    The type of notice. Accepts 'error', 'warning', 'success', 'info', 'version'.
		 * @param  string  $key     Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Whether the notice can be dismissed. Defaults to false.
		 * @return void
		 */
		public function add_notice( $message, $type, $key = false, $dismissible = false ) {
			if ( false === $key ) {
				$this->notices[ $type ][] = array(
					'message'     => $message,
					'dismissible' => $dismissible,
				);
			} else {
				$this->notices[ $type ][ $key ] = array(
					'message'     => $message,
					'dismissible' => $dismissible,
				);
			}
		}

		/**
		 * Adds an error message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message The message to display.
		 * @param  string  $key     Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_error( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'error', $key, $dismissible );
		}

		/**
		 * Adds a warning message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message The message to display.
		 * @param  string  $key     Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_warning( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'warning', $key, $dismissible );
		}

		/**
		 * Adds a success message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message The message to display.
		 * @param  string  $key     Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_success( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'success', $key, $dismissible );
		}

		/**
		 * Adds an info message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message The message to display.
		 * @param  string  $key     Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_info( $message, $key = false, $dismissible = false ) {
			$this->add_notice( $message, 'info', $key, $dismissible );
		}

		/**
		 * Adds a version update message.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $message     The message to display.
		 * @param  string  $key         Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_version_update( $message, $key = false, $dismissible = true ) {
			$this->add_notice( $message, 'version', $key, $dismissible );
		}

		/**
		 * Adds a third party warning message.
		 *
		 * @since  1.7.0.8
		 *
		 * @param  string  $message     The message to display.
		 * @param  string  $key         Optional. If not set, next numeric key is used.
		 * @param  boolean $dismissible Optional. Set to true by default.
		 * @return void
		 */
		public function add_third_party_warning( $message, $key = false, $dismissible = true ) {
			$this->add_notice( $message, 'warning', $key, $dismissible );
		}

		/**
		 * Render notices.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function render() {
			foreach ( charitable_get_admin_notices()->get_notices() as $type => $notices ) {
				foreach ( $notices as $key => $notice ) {
					if ( ! empty( $notice['message'] ) ) {
						$this->render_notice( $notice['message'], $type, $notice['dismissible'], $key );
					}
				}
			}
		}

		/**
		 * Render a notice.
		 *
		 * @since  1.4.6
		 *
		 * @param  string  $notice The notice message.
		 * @param  string  $type  The type of notice. Accepts 'error', 'warning', 'success', 'info', 'version'.
		 * @param  boolean $dismissible Optional. Whether the notice can be dismissed. Defaults to false.
		 * @param  string  $notice_key Optional. If set, the notice will be dismissed when the user dismisses the notice.
		 * @param  boolean $paragraph_tags Optional. Whether to wrap the notice in paragraph tags. Defaults to true.
		 * @return void
		 */
		public function render_notice( $notice, $type, $dismissible = false, $notice_key = '', $paragraph_tags = true ) {
			if ( ! isset( $this->script_enqueued ) ) {
				if ( ! wp_script_is( 'charitable-admin-notice' ) ) {
					wp_enqueue_script( 'charitable-admin-notice' );
				}

				$this->script_enqueued = true;
			}

			$class = 'notice charitable-notice';

			switch ( $type ) {
				case 'error':
					$class .= ' notice-error';
					break;

				case 'warning':
					$class .= ' notice-warning';
					break;

				case 'success':
					$class .= ' updated';
					break;

				case 'info':
					$class .= ' notice-info';
					break;

				case 'five-star-review':
					$class .= ' notice-info notice-five-star-review';
					break;

				case 'version':
					$class .= ' charitable-upgrade-notice';
					break;
			}

			if ( $dismissible ) {
				$class .= ' is-dismissible';
			}

			$body_text = ( $paragraph_tags ) ? '<p>%s</p>' : '%s';

			printf(
				'<div class="%s" %s>' . wp_kses_post( $body_text ) . '</div>',
				esc_attr( $class ),
				strlen( $notice_key ) ? 'data-notice="' . esc_attr( $notice_key ) . '"' : '',
				wp_kses_post( $notice )
			);

			if ( strlen( $notice_key ) ) {
				unset( $this->notices[ $type ][ $notice_key ] );
			}
		}

		/**
		 * Render a lite to pro banner
		 *
		 * @since 1.7.0
		 */
		public function render_upgrade_banner() {
			if ( charitable_is_pro() ) {
				return;
			}
			$screen = get_current_screen();
			if ( ! is_null( $screen ) && ( in_array( $screen->id, charitable_get_charitable_screens() ) || ( isset( $screen->taxonomy ) && 'campaign_category' === $screen->taxonomy ) || ( isset( $screen->taxonomy ) && 'campaign_tag' === $screen->taxonomy ) ) ) {
				$banner = get_transient( 'charitable_charitablelitetopro_banner' );
				if ( ! $banner ) {
					$utm_link = charitable_pro_upgrade_url( 'Upgrade From Lite Top Banner Link', 'To unlock more features consider upgrading to Pro.' );
					$this->render_banner(
						'You\'re using Charitable Lite! To unlock more features consider <a href="' . $utm_link . '" target="_blank" rel="noopener noreferrer">upgrading to Pro</a>.
				',
						'top-of-page',
						true
					);
				}
			}
		}

		/**
		 * Render a license soon-to-be-expired banner.
		 *
		 * @since 1.8.1.12
		 */
		public function render_license_expiring_banner() {

			if ( ! charitable_is_pro() || ! class_exists( 'Charitable_Licenses_Settings' ) || ! class_exists( 'Charitable_Addons_Directory' ) ) {
				// This should only show if the user has a license already entered.
				return;
			}

			$is_legacy = Charitable_Addons_Directory::is_current_plan_legacy();
			if ( $is_legacy ) {
				return;
			}

			// Is the license expiring within 14 days?
			$is_license_expiring = Charitable_Licenses_Settings::get_instance()->is_license_expiring( 1209600 );

			if ( $is_license_expiring ) {
				$screen = get_current_screen();
				if ( ! is_null( $screen ) && ( in_array( $screen->id, charitable_get_charitable_screens(), true ) || ( isset( $screen->taxonomy ) && 'campaign_category' === $screen->taxonomy ) || ( isset( $screen->taxonomy ) && 'campaign_tag' === $screen->taxonomy ) ) ) {
					$banner = get_transient( 'charitable_expiringlicense_banner' );
					if ( ! $banner ) {
						$this->render_banner(
							'Your Charitable license may be expiring soon. <a href="' . admin_url( 'admin.php?page=charitable-settings&tab=general&warning=license-expire' ) . '" target="_blank" rel="noopener noreferrer">View license settings</a> to ensure support and updates are not interrupted.
					',
							'top-of-page',
							true,
							'expiringlicense',
							'expiringlicense',
							false,
							'charitable-license-expiring-banner'
						);
					}
				}
			}
		}

		/**
		 * Render a license soon-to-be-expired banner.
		 *
		 * @since 1.8.1.12
		 */
		public function render_license_expired_banner() {
			if ( ! charitable_is_pro() || ! class_exists( 'Charitable_Licenses_Settings' ) || ! class_exists( 'Charitable_Addons_Directory' ) ) {
				// This should only show if the user has a license already entered.
				return;
			}

			$is_legacy = Charitable_Addons_Directory::is_current_plan_legacy();
			if ( $is_legacy ) {
				return;
			}

			// Is the license expiring within 14 days?
			$is_license_expired = Charitable_Licenses_Settings::get_instance()->is_license_expired();

			if ( $is_license_expired ) {
				$screen = get_current_screen();
				if ( ! is_null( $screen ) && ( in_array( $screen->id, charitable_get_charitable_screens(), true ) || ( isset( $screen->taxonomy ) && 'campaign_category' === $screen->taxonomy ) || ( isset( $screen->taxonomy ) && 'campaign_tag' === $screen->taxonomy ) ) ) {
					$banner = get_transient( 'charitable_expiredlicense_banner' );
					if ( ! $banner ) {
						$this->render_banner(
							'Your Charitable license may have expired. <a href="' . admin_url( 'admin.php?page=charitable-settings&tab=general&warning=license-expire' ) . '" target="_blank" rel="noopener noreferrer">View license settings</a> to ensure support and updates are not interrupted.
					',
							'top-of-page',
							true,
							'expiredlicense',
							'expiredlicense',
							false,
							'charitable-license-expiring-banner'
						);
					}
				}
			}
		}

		/**
		 * Render a lite to pro banner in campaign lists, add it to the charitable admin vars so it can be injected via JS.
		 *
		 * @param array $strings The localized strings.
		 *
		 * @since 1.8.0
		 */
		public function render_campaign_upgrade_banner_html( $strings = false ) {
			if ( charitable_is_pro() ) {
				return $strings;
			}
			$screen = get_current_screen();
			if ( ! is_null( $screen ) && isset( $screen->base ) && 'edit' === $screen->base && isset( $screen->id ) && 'edit-campaign' === $screen->id ) {
				$banner = get_transient( 'charitable_charitable_ltp_lb_list_banner' );
				if ( ! $banner ) {
					$nonce             = wp_create_nonce( 'charitable_dismiss_list_banner' );
					$banner_text       = '<p><strong>Unlock More Donations with Peer-to-Peer Fundraising!</strong><br/>Harness the power of supporter networks and friends to reach more people and raise more money for your cause.</p>';
					$strings['banner'] = sprintf(
						'<div data-id="charitable_ltp_lb" data-nonce="%1$s" class="charitable-campaign-list-banner">' .
						'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' .
						'<div class="charitable-campaign-list-banner-icon-text">' .
						'<div class="charitable-campaign-list-banner-icon"><img src="%2$s" /></div>' .
						'<div class="charitable-campaign-list-banner-text">%3$s</div>' .
						'</div>' .
						'<div class="charitable-campaign-list-banner-button"><a class="button-link" href="#">Learn More</a></div>' .
						'</div>',
						$nonce,
						charitable()->get_path( 'assets', false ) . 'images/icons/ambassador.png',
						$banner_text
					);
				} else {
					$strings['banner'] = '';
				}
			}

			return $strings;
		}

		/**
		 * Render a prompt to ask for a five star rating.
		 *
		 * @since 1.7.0
		 */
		public function render_five_star_rating() {

			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();

			// determine if we are on the current screen.
			if ( ! is_null( $screen ) && ( in_array( $screen->id, charitable_get_charitable_screens() ) || ( isset( $screen->taxonomy ) && 'campaign_category' === $screen->taxonomy ) || ( isset( $screen->taxonomy ) && 'campaign_tag' === $screen->taxonomy ) ) ) {

				$slug = 'five-star-review';

				// determine when to display this message. for now, there should be some sensible boundaries before showing the notification: a minimum of 14 days of use, created one donation form and received at least one donation.
				$activated_datetime = ( false !== get_option( 'wpcharitable_activated_datetime' ) ) ? get_option( 'wpcharitable_activated_datetime' ) : false;
				$days               = 0;
				if ( $activated_datetime ) {
					$diff = current_time( 'timestamp' ) - $activated_datetime; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
					$days = abs( round( $diff / 86400 ) );
				}

				$count_campaigns = wp_count_posts( 'campaign' );
				$total_campaigns = isset( $count_campaigns->publish ) ? $count_campaigns->publish : 0;
				$count_donations = wp_count_posts( 'donation' );
				$total_donations = isset( $count_donations->{'charitable-completed'} ) ? $count_donations->{'charitable-completed'} : 0;

				if ( $days >= apply_filters( 'charitable_days_since_activated', 14 ) && $total_campaigns >= 1 && $total_donations >= 1 ) {
					// check transient.
					$star_review = get_transient( 'charitable_' . $slug . '_banner' );

					// render five star rating banner/notice.
					if ( ! $star_review ) {
						$message = charitable_admin_view( 'notices/admin-notice-five-star-review', array(), true );
						$key     = 'five-star-review';
						$this->render_notice( $message, 'five-star-review', true, $key, false );
					}
				}
			}
		}

		/**
		 * Render a notice when the Square connection fails.
		 *
		 * @since 1.8.7
		 */
		public function render_square_connection_error() {

			$admin_notice = get_option( 'charitable_square_connection_error_notice' );

			if ( false === $admin_notice ) {
				return;
			}

			$message = charitable_admin_view( 'notices/admin-notice-square-connection-error', array( 'message' => $admin_notice['message'], 'status_code' => $admin_notice['status_code'] ), true ); // phpcs:ignore
			$key     = 'square-connection-error';
			$this->render_notice( $message, 'error', true, $key, false );
		}

		/**
		 * Render a banner to promote the new campaign builder in version 1.8.0.
		 * This was removed in 1.8.1 in favor of the updated notice render_dashboard_reporting_notice.
		 *
		 * @since 1.8.0
		 */
		public function render_campaign_builder_notice() {

			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();

			// determine if we are on the current screen.
			if ( ! is_null( $screen ) && ( in_array( $screen->id, charitable_get_charitable_screens() ) || ( isset( $screen->taxonomy ) && 'campaign_category' === $screen->taxonomy ) || ( isset( $screen->taxonomy ) && 'campaign_tag' === $screen->taxonomy ) ) ) {

				$slug = 'campaign-builder';

				// check transient.
				$builder_notice = get_transient( 'charitable_' . $slug . '_banner' );

				// render banner/notice.
				if ( ! $builder_notice ) {
					$message = charitable_admin_view( 'notices/admin-notice-campaign-builder', array(), true );
					$key     = 'campaign-builder';
					$this->render_notice( $message, 'campaign-builder', true, $key, false );
				}
			}
		}

		/**
		 * Render a banner to promote
		 *
		 * @since 1.8.1
		 * @version 1.8.1.10
		 * @version 1.8.2 deprecated.
		 */
		public function render_dashboard_reporting_notice() {

			if ( ! function_exists( 'get_current_screen' ) ) {
				return;
			}

			$screen = get_current_screen();

			// determine if we are on the current screen.
			if ( ! is_null( $screen ) && ( in_array( $screen->id, charitable_get_charitable_screens() ) || ( isset( $screen->taxonomy ) && 'campaign_category' === $screen->taxonomy ) || ( isset( $screen->taxonomy ) && 'campaign_tag' === $screen->taxonomy ) ) ) {

				$slug = 'dashboard-reporting';

				// determine when to display this message. for now, there should be some sensible boundaries before showing the notification: a minimum of 10 days of use.
				$activated_datetime = ( false !== get_option( 'wpcharitable_activated_datetime' ) ) ? get_option( 'wpcharitable_activated_datetime' ) : false;
				$days               = 0;
				if ( $activated_datetime ) {
					$diff = current_time( 'timestamp' ) - $activated_datetime; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
					$days = abs( round( $diff / 86400 ) );
				}

				if ( $days >= apply_filters( 'charitable_days_since_activated', 10, $slug ) ) {
					// check transient.
					$builder_notice = get_transient( 'charitable_' . $slug . '_banner' );

					// render banner/notice.
					if ( ! $builder_notice ) {
						$message = charitable_admin_view( 'notices/admin-notice-dashboard-reporting', array(), true );
						$key     = 'dashboard-reporting';
						$this->render_notice( $message, 'dashboard-reporting', true, $key, false );
					}
				}
			}
		}

		/**
		 * Dismiss review request.
		 *
		 * @since 1.8.0
		 * @param array $postdata $_POST data.
		 */
		public function dismiss_five_star_notice( $postdata ) {

			if ( empty( $postdata['notice'] ) || 'five-star-review' !== $postdata['notice'] ) {
				return;
			}

			$slug = 'five-star-review';

			set_transient( 'charitable_' . $slug . '_banner', 1, 0 );
		}

		/**
		 * Dismiss the new campaign builder notice. Deprecated in 1.8.1.
		 *
		 * @since 1.8.0
		 * @param array $postdata $_POST data.
		 */
		public function dismiss_campaign_builder_notice( $postdata ) {

			if ( empty( $postdata['notice'] ) || 'campaign-builder' !== $postdata['notice'] ) {
				return;
			}

			$slug = 'campaign-builder';

			set_transient( 'charitable_' . $slug . '_banner', 1, 0 );
		}

		/**
		 * Dismiss the new campaign builder notice.
		 *
		 * @since 1.8.1
		 * @param array $postdata $_POST data.
		 */
		public function dismiss_dashboard_reporting_notice( $postdata ) {

			if ( empty( $postdata['notice'] ) || 'dashboard-reporting' !== $postdata['notice'] ) {
				return;
			}

			$slug = 'dashboard-reporting';

			set_transient( 'charitable_' . $slug . '_banner', 1, 0 );
		}

		/**
		 * Dismiss the Square connection error notice.
		 *
		 * @since 1.8.7
		 * @param array $postdata $_POST data.
		 */
		public function dismiss_square_connection_error_notice( $postdata ) {
			if ( empty( $postdata['notice'] ) || 'square-connection-error' !== $postdata['notice'] ) {
				return;
			}

			delete_option( 'charitable_square_connection_error_notice' );
			wp_send_json_success();
			exit;
		}

		/**
		 * Render a banner.
		 *
		 * @since 1.7.0
		 * @version 1.8.1.12 Added css_class.
		 *
		 * @param string  $message The message to display.
		 * @param string  $type    The type of notice. Accepts 'top-of-page'.
		 * @param boolean $dismissible Optional. Whether the notice can be dismissed. Defaults to false.
		 * @param mixed   $data_nonce Optional. The nonce to use for the notice.
		 * @param mixed   $data_id Optional. The id to use for the notice.
		 * @param string  $data_lifespan Optional. The lifespan of the notice.
		 * @param string  $additional_css_class Optional. The CSS class to use for the notice.
		 *
		 * @return void
		 */
		public function render_banner( $message, $type = 'top-of-page', $dismissible = false, $data_nonce = 'charitablelitetopro', $data_id = 'charitablelitetopro', $data_lifespan = false, $additional_css_class = '' ) {
			if ( ! isset( $this->script_enqueued ) ) {
				if ( ! wp_script_is( 'charitable-admin-notice' ) ) {
					wp_enqueue_script( 'charitable-admin-notice' );
				}

				$this->script_enqueued = true;
			}

			$css_class = trim( 'charitable-banner ' . $additional_css_class );

			switch ( $type ) {
				case 'top-of-page':
					$css_class .= ' charitable-admin-banner-top-of-page';
					break;
			}

			if ( $dismissible ) { // phpcs:ignore
				// $class .= ' is-dismissible';
			}

			printf(
				'<div class="%s" %s %s %s>%s <button type="button" class="button-link charitable-banner-dismiss">x</button></div>',
				esc_attr( $css_class ),
				strlen( $data_nonce ) ? 'data-notice="' . esc_attr( $data_nonce ) . '"' : '',
				strlen( $data_id ) ? 'data-id="' . esc_attr( $data_id ) . '"' : '',
				strlen( $data_lifespan ) ? 'data-lifespan="' . esc_attr( $data_lifespan ) . '"' : '',
				wp_kses_post( $message )
			);
		}


		/**
		 * When PHP finishes executing, stash any notices that haven't been rendered yet.
		 *
		 * @since  1.4.13
		 *
		 * @return void
		 */
		public function shutdown() {
			if ( charitable_is_debug( 'square' ) ) {
				// phpcs:disable
				error_log( 'Saving admin notices to transient' );
				error_log( 'Notices to save: ' . print_r( $this->notices, true ) );
				// phpcs:enable
			}

			set_transient( 'charitable_notices', $this->notices );
		}

		/**
		 * Load the notices array.
		 *
		 * If there are any stuffed in a transient, pull those out. Otherwise, reset a clear array.
		 *
		 * @since  1.4.13
		 *
		 * @return void
		 */
		public function load_notices() {
			$this->notices = get_transient( 'charitable_notices' );

			if ( ! is_array( $this->notices ) ) {
				$this->clear();
			}
		}

		/**
		 * Fill admin notices from the front-end notices array.
		 *
		 * @since  1.6.24
		 *
		 * @return void
		 */
		public function fill_notices_from_frontend() {
			$notices = charitable_get_notices();

			foreach ( $notices->get_notices() as $type => $type_notices ) {
				foreach ( $type_notices as $notice ) {
					$this->add_notice( $notice, $type );
				}
			}

			$notices->clear();
		}

		/**
		 * Clear out all existing notices.
		 *
		 * @since  1.4.6
		 *
		 * @return void
		 */
		public function clear() {
			$clear = array(
				'error'   => array(),
				'warning' => array(),
				'success' => array(),
				'info'    => array(),
				'version' => array(),
			);

			$this->notices = $clear;
		}

		/**
		 * Returns an array of screen IDs where the Charitable notices should be displayed.
		 * (Deprecated in 1.8.1.15)
		 *
		 * @uses   charitable_admin_screens
		 *
		 * @since  1.7.0
		 * @since  1.8.1 Added the Charitable reports page.
		 * @since  1.8.1.6 Added the Charitable tools and guide tools page.
		 * @since  1.8.5 Added the Charitable donors page.
		 * @return array
		 */
		public function get_charitable_screens() {
			/**
			 * Filter admin screens where Charitable styles & scripts should be loaded.
			 *
			 * @since 1.7.0
			 *
			 * @param string[] $screens List of screen ids.
			 */
			return apply_filters(
				'charitable_admin_notice_screens',
				array(
					'campaign',
					'donation',
					'charitable_page_charitable-settings',
					'charitable_page_charitable-tools',
					'charitable_page_charitable-growth-tools',
					'charitable_page_charitable-reports',
					'charitable_page_charitable-dashboard',
					'edit-campaign',
					'edit-donation',
					'toplevel_page_charitable',
					'charitable_page_charitable-addons',
					'charitable_page_charitable-donors',
				)
			);
		}
	}

endif;
