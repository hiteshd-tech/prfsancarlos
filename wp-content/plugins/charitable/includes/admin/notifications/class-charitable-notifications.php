<?php
/**
 * Charitable Admin Plugin Notifications.
 *
 * @package   Charitable/Classes/Charitable_Admin_Form
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.3
 * @version   1.8.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notifications.
 *
 * @since 1.8.3
 * @version 1.8.3
 */
class Charitable_Notifications {

	/**
	 * The single instance of this class.
	 *
	 * @var Charitable_Notifications|null
	 */
	private static $instance = null;

	/**
	 * Source of notifications content.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	const SOURCE_URL = 'https://plugin.wpcharitable.com/wp-content/charitable-notifications.json';

	/**
	 * Array of license types, that are considered being Elite level.
	 *
	 * @since 1.8.3
	 *
	 * @var array
	 */
	const LICENSES_ELITE = [ 'agency', 'pro', 'elite' ];

	/**
	 * Option value.
	 *
	 * @since 1.8.3
	 *
	 * @var bool|array
	 */
	public $option = false;

	/**
	 * Current license type.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	private $license_type;

	/**
	 * Returns and/or create the single instance of this class.
	 *
	 * @since  1.2.0
	 *
	 * @return Charitable_User_Dashboard
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.8.3
	 */
	public function __construct() {

		if ( ! defined( 'CHARITABLE_DISABLE_NOTIFICATIONS' ) || ! CHARITABLE_DISABLE_NOTIFICATIONS ) {
			$this->init();
		}
	}

	/**
	 * Initialize class.
	 *
	 * @since 1.8.3
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.3
	 */
	public function hooks() {
		add_action( 'charitable_maybe_show_plugin_notifications', [ $this, 'output' ], 99 ); // where the notifications comes out in the admin.
		add_action( 'charitable_admin_notifications_update', [ $this, 'update' ] );
		add_action( 'deactivate_plugin', [ $this, 'delete' ], 10, 2 );
	}

	/**
	 * Check if user has access and is enabled.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	public function has_access() {

		$access = charitable_current_user_can( 'administrator' ) && ! charitable_get_option( 'hide-announcements' );

		/**
		 * Allow modifying state if a user has access.
		 *
		 * @since 1.7.0.3
		 *
		 * @param bool $access True if user has access.
		 */
		return (bool) apply_filters( 'charitable_admin_notifications_has_access', $access );
	}

	/**
	 * Add notifications to the WordPress admin menu for Charitable.
	 *
	 * @since 1.8.3
	 *
	 * @param array $submenu Array of menu items.
	 * @return bool
	 */
	public function add_notifications_to_menu( $submenu = array() ) {

		if ( ( defined( 'CHARITABLE_NOTIFICATIONS_FORCE_OFF' ) && CHARITABLE_NOTIFICATIONS_FORCE_OFF ) ) {
			return $submenu;
		}

		$notifications_menu_item = array(
			'page_title' => __( 'Notifications', 'charitable' ),
			'menu_title' => __( 'Notifications', 'charitable' ),
			'menu_slug'  => 'charitable-notifications',
			'function'   => array( $this, 'render_plugin_notifications_page' ),
			'capability' => 'manage_charitable_settings',
		);

		// if there are new notifications add the indicator to the menu title.
		if ( $this->get_new_notifications_count() ) {
			$notifications_menu_item['menu_title'] .= '<div class="charitable-menu-notification-indicator"></div>';
		}

		// If the checklist exists in the submenu, add this item after it.
		$checklist_key = array_search( 'charitable-setup-checklist', array_column( $submenu, 'menu_slug' ), true );
		if ( false !== $checklist_key ) {
			$submenu = array_merge(
				array_slice( $submenu, 0, $checklist_key + 1 ),
				array( $notifications_menu_item ),
				array_slice( $submenu, $checklist_key + 1 )
			);
		} else {
			array_unshift( $submenu, $notifications_menu_item );
		}

		return $submenu;
	}

	/**
	 * Technically this should render the plugin notifications page.
	 * However, notifications aren't rendering with this method. There should be a redirect, but the existance of this function prevents fatal errors.
	 *
	 * @since 1.8.3
	 */
	public function render_plugin_notifications_page() {
		$this->output();
	}

	/**
	 * Get option value.
	 *
	 * @since 1.8.3
	 *
	 * @param bool $cache Reference property cache if available.
	 *
	 * @return array
	 */
	public function get_option( $cache = true ) {

		if ( $this->option && $cache ) {
			return $this->option;
		}

		$option = (array) get_option( 'charitable_notifications', [] );
		// $option = [];

		$this->option = [
			'update'    => ! empty( $option['update'] ) ? (int) $option['update'] : 0,
			'feed'      => ! empty( $option['feed'] ) ? (array) $option['feed'] : [],
			'events'    => ! empty( $option['events'] ) ? (array) $option['events'] : [],
			'dismissed' => ! empty( $option['dismissed'] ) ? (array) $option['dismissed'] : [],
		];

		return $this->option;
	}

	/**
	 * Fetch notifications from feed.
	 *
	 * @since 1.8.3
	 *
	 * @return array
	 */
	public function fetch_feed() {

		$args = [
			'timeout'    => 10,
			'sslverify'  => false,
			'user-agent' => charitable_get_default_user_agent(),
		];

		$response = wp_remote_get(
			self::SOURCE_URL,
			$args
		);

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return [];
		}

		return $this->verify( json_decode( $body, true ) );
	}

	/**
	 * Verify notification data before it is saved.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notifications Array of notifications items to verify.
	 *
	 * @return array
	 */
	public function verify( $notifications ) {

		$data = [];

		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return $data;
		}

		foreach ( $notifications as $notification ) {

			// Ignore if one of the conditional checks is true:
			//
			// 1. notification message is empty.
			// 2. license type does not match.
			// 3. notification is expired.
			// 4. notification has already been dismissed.
			// 5. notification existed before installing Charitable.
			// (Prevents bombarding the user with notifications after activation).
			if (
				empty( $notification['content'] ) ||
				! $this->is_license_type_match( $notification ) ||
				$this->is_expired( $notification ) ||
				$this->is_dismissed( $notification ) ||
				$this->is_existed( $notification )
			) {
				continue;
			}

			$data[] = $notification;
		}

		return $data;
	}

	/**
	 * Verify saved notification data for active notifications.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notifications Array of notifications items to verify.
	 *
	 * @return array
	 */
	public function verify_active( $notifications ) {

		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return [];
		}

		$current_timestamp = time();

		// Remove notifications that are not active.
		foreach ( $notifications as $key => $notification ) {
			if (
				( ! empty( $notification['start'] ) && $current_timestamp < strtotime( $notification['start'] ) ) ||
				( ! empty( $notification['end'] ) && $current_timestamp > strtotime( $notification['end'] ) )
			) {
				unset( $notifications[ $key ] );
			}
		}

		return $notifications;
	}

	/**
	 * Get notification data.
	 *
	 * @since 1.8.3
	 *
	 * @return array
	 */
	public function get() {

		if ( defined( 'CHARITABLE_DISABLE_NOTIFICATIONS' ) && CHARITABLE_DISABLE_NOTIFICATIONS ) {
			return [];
		}

		if ( ! $this->has_access() ) {
			return [];
		}

		$option = $this->get_option();

		// Update notifications using async task.
		if ( empty( $option['update'] ) || time() > $option['update'] + DAY_IN_SECONDS ) {
			$option = $this->update();
		} elseif ( empty( $option['feed'] ) && empty( $option['events'] ) ) {
			// regardless of the update time, if there are no notifications, update attempt.
			$option = $this->update();
		}

		$feed   = ! empty( $option['feed'] ) ? $this->verify_active( $option['feed'] ) : [];
		$events = ! empty( $option['events'] ) ? $this->verify_active( $option['events'] ) : [];

		return array_merge( $feed, $events );
	}

	/**
	 * Get notification count in the feed, regardless if they are dismissed or not.
	 *
	 * @since 1.8.3
	 *
	 * @return int
	 */
	public function get_count() {
		return count( $this->get() );
	}

	/**
	 * Get new notifications by comparing those in the feed vs dismissed.
	 *
	 * @since 1.8.3
	 *
	 * @return int
	 */
	public function get_new_notifications_count() {

		$option            = $this->get_option();
		$dimissed_ids      = ! empty( $option['dismissed'] ) ? $option['dismissed'] : [];
		$notification_feed = $this->get();

		// if $option is empty or if the feed is empty, there are no new notifications.
		if ( empty( $option ) || empty( $notification_feed ) ) {
			return 0;
		}

		$count = 0;

		foreach ( $notification_feed as $notification ) {
			// check the id of the notification against the dismissed ids.
			if ( ! in_array( (string) $notification['id'], $dimissed_ids, true ) ) {
				++$count;
			}
		}

		return (int) $count;
	}

	/**
	 * Add a new Event Driven notification.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 */
	public function add( $notification ) {

		if ( ! $this->is_valid( $notification ) ) {
			return;
		}

		$option = $this->get_option();

		// Notification ID already exists.
		if ( ! empty( $option['events'][ $notification['id'] ] ) ) {
			return;
		}

		update_option(
			'charitable_notifications',
			[
				'update'    => $option['update'],
				'feed'      => $option['feed'],
				'events'    => array_merge( $notification, $option['events'] ),
				'dismissed' => $option['dismissed'],
			]
		);
	}

	/**
	 * Determine if notification data is valid.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return bool
	 */
	public function is_valid( $notification ) {

		if ( empty( $notification['id'] ) ) {
			return false;
		}

		return ! empty( $this->verify( [ $notification ] ) );
	}

	/**
	 * Determine if notification has already been dismissed.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return bool
	 */
	private function is_dismissed( $notification ) {

		$option = $this->get_option();

		// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		return ! empty( $option['dismissed'] ) && in_array( $notification['id'], $option['dismissed'] );
	}

	/**
	 * Determine if license type is match.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return bool
	 */
	private function is_license_type_match( $notification ) {

		// A specific license type is not required.
		if ( empty( $notification['type'] ) ) {
			return true;
		}

		return in_array( $this->get_license_type(), (array) $notification['type'], true );
	}

	/**
	 * Determine if notification is expired.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return bool
	 */
	private function is_expired( $notification ) {
		// Check if the notification 'end' parameter is set and not empty.
		if ( empty( $notification['end'] ) ) {
			return false;
		}

		// Get the WordPress timezone.
		$timezone = wp_timezone();

		// Convert the notification end date to a DateTime object in the WordPress timezone.
		$end_time = new DateTime( $notification['end'], $timezone );

		// Get the current server time as a DateTime object in the WordPress timezone.
		$current_time = new DateTime( 'now', $timezone );

		// Compare the current time with the notification end time.
		return $current_time > $end_time;
	}

	/**
	 * Determine if notification existed before installing Charitable.
	 * If there is an "allow_on_activation" key, it will be used to determine to display even if the notification existed before.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return bool
	 */
	private function is_existed( $notification ) {

		$activated = charitable_get_activated_timestamp();

		if ( ! isset( $notification['allow_on_activation'] ) ) {
			return ! empty( $activated ) &&
			! empty( $notification['start'] ) &&
			$activated > strtotime( $notification['start'] );
		} else {
			return ! empty( $activated ) &&
			! empty( $notification['start'] ) &&
			false === $notification['allow_on_activation'];
		}
	}

	/**
	 * Update notification data from feed.
	 *
	 * @since 1.8.3
	 */
	public function update() {

		$option = $this->get_option();

		$data = [
			'update'    => time(),
			'feed'      => $this->fetch_feed(),
			'events'    => $option['events'],
			'dismissed' => $option['dismissed'],
		];

		// phpcs:disable Charitable.PHP.ValidateHooks.InvalidHookName
		/**
		 * Allow changing notification data before it will be updated in database.
		 *
		 * @since 1.8.3
		 *
		 * @param array $data New notification data.
		 */
		$data = (array) apply_filters( 'charitable_admin_notifications_update_data', $data );
		// phpcs:enable Charitable.PHP.ValidateHooks.InvalidHookName

		update_option( 'charitable_notifications', $data );

		return $data;
	}

	/**
	 * Remove notification data from database before a plugin is deactivated.
	 *
	 * @since 1.8.3
	 *
	 * @param string $plugin               Path to the plugin file relative to the plugins directory.
	 * @param bool   $network_deactivating Whether the plugin is deactivated for all sites in the network
	 *                                     or just the current site. Multisite only. Default false.
	 */
	public function delete( $plugin, $network_deactivating ) { // phpcs:ignore

		$charitable_plugins = [
			'charitable/charitable.php',
		];

		if ( ! in_array( $plugin, $charitable_plugins, true ) ) {
			return;
		}

		delete_option( 'charitable_notifications' );
	}

	/**
	 * Output notices.
	 *
	 * @since 1.8.3
	 */
	public function output_notices() {
		return $this->output( 'notices' );
	}

	/**
	 * Output notifications on Form Overview admin area.
	 *
	 * @since 1.8.3
	 *
	 * @param string $location Location where the notifications are output.
	 * @param int    $limit_active Limit of active notifications to show.
	 */
	public function output( $location = false, $limit_active = 4 ) {

		if ( defined( 'CHARITABLE_DISABLE_NOTIFICATIONS' ) && CHARITABLE_DISABLE_NOTIFICATIONS ) {
			return;
		}

		global $post;

		if ( 'notices' === $location && ! $this->show_notifications() ) {
			return;
		}

		$notifications = $this->get();

		$active_notifications_html     = '';
		$dismissed_notifications_html  = '';
		$active_notifications_count    = 0;
		$dismissed_notifications_count = 0;
		$current_class                 = ' current';
		$content_allowed_tags          = [
			'em'     => [],
			'strong' => [],
			'span'   => [
				'style' => [],
			],
			'a'      => [
				'href'   => [],
				'target' => [],
				'rel'    => [],
			],
		];
		$template_location             = $location === 'dashboard' ? '/admin/templates/notifications-dashboard' : '/admin/templates/notifications';
		$template_location             = apply_filters( 'charitable_admin_notifications_template_location', $template_location, $location );

		if ( empty( $notifications ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo charitable_render(
				$template_location,
				[
					'notifications' => [
						'active_count'    => $active_notifications_count,
						'dismissed_count' => $dismissed_notifications_count,
						'active_html'     => $active_notifications_html,
						'dismissed_html'  => $dismissed_notifications_html,
					],
				],
				true
			);
			return;
		}

		// Get active notifications.
		foreach ( $notifications as $notification ) {

			// Prepare required arguments.
			$notification = wp_parse_args(
				$notification,
				[
					'id'      => 0,
					'title'   => '',
					'content' => '',
					'video'   => '',
				]
			);

			$title   = $this->get_component_data( $notification['title'] );
			$content = $this->get_component_data( $notification['content'] );

			if ( ! $title && ! $content ) {
				continue;
			}

			// If the notification ID is not a part of the dismissed notifications, add it to the list.
			if ( ! $this->is_dismissed( $notification ) ) {

				if ( 'dashboard' === $location ) {

					// Notification HTML, slightyl different for dashboard.
					$active_notifications_html .= sprintf(
						'<div aria-expanded="true" class="charitable-notification charitable-notification-%5$s %8$s" data-notification-id="%6$s">
							<div>
								%7$s
								<div class="body">
									<div class="title dashboard-title">
										<div>%1$s</div>
										<div class="date">%3$s</div>
									</div>
									<div class="notification-content">%2$s</div>
									<div class="actions">
										%4$s
										<!----><a href="#" class="dismiss">Dismiss</a>
									</div>
								</div>
							</div>
						</div>',
						esc_html( $title ),
						wp_kses( $content, $content_allowed_tags ),
						! empty( $notification['start'] ) ? $this->human_readable_date_from_mysql( $notification['start'] ) : '',
						$this->get_notification_buttons_html( $notification ),
						esc_attr( $notification['id'] ),
						esc_attr( $notification['id'] ),
						$this->get_notification_icon_html( $notification ),
						esc_attr( $current_class )
						// $this->get_video_badge_html( $this->get_component_data( $notification['video'] ) )
					);

				} else {

					// Notification HTML.
					$active_notifications_html .= sprintf(
						'<div aria-expanded="true" class="charitable-notification charitable-notification-%5$s %8$s" data-notification-id="%6$s">
							<div>
								%7$s
								<div class="body">
									<div class="title">
										<div class="date">%3$s</div>
										<div>%1$s</div>
									</div>
									<div class="notification-content">%2$s</div>
									<div class="actions">
										%4$s
										<!----><a href="#" class="dismiss">Dismiss</a>
									</div>
								</div>
							</div>
						</div>',
						esc_html( $title ),
						wp_kses( $content, $content_allowed_tags ),
						! empty( $notification['start'] ) ? $this->human_readable_date_from_mysql( $notification['start'] ) : '',
						$this->get_notification_buttons_html( $notification ),
						esc_attr( $notification['id'] ),
						esc_attr( $notification['id'] ),
						$this->get_notification_icon_html( $notification ),
						esc_attr( $current_class )
						// $this->get_video_badge_html( $this->get_component_data( $notification['video'] ) )
					);

				}

				++$active_notifications_count;

			} else {

				// Dismissed notification.
				$dismissed_notifications_html .= sprintf(
					'<div aria-expanded="true" class="charitable-notification charitable-notification-%5$s %8$s" data-notification-id="%6$s">
						<div>
							%7$s
							<div class="body">
								<div class="title">
									<div class="date">%3$s</div>
									<div>%1$s</div>
								</div>
								<div class="notification-content">%2$s</div>
								<div class="actions">
									%4$s
								</div>
							</div>
						</div>
					</div>',
					esc_html( $title ),
					wp_kses( $content, $content_allowed_tags ),
					! empty( $notification['start'] ) ? $this->human_readable_date_from_mysql( $notification['start'] ) : '',
					$this->get_notification_buttons_html( $notification ),
					esc_attr( $notification['id'] ),
					esc_attr( $notification['id'] ),
					$this->get_notification_icon_html( $notification ),
					esc_attr( $current_class )
					// $this->get_video_badge_html( $this->get_component_data( $notification['video'] ) )
				);

				++$dismissed_notifications_count;
			}

			// Only first notification is current.
			$current_class = '';

			if ( 'dashboard' === $location && $active_notifications_count >= $limit_active ) {
				break;
			}
		}

		// For dashboard, we need to show the actual count being displayed (limited), not the total count
		$display_count = $active_notifications_count;
		if ( 'dashboard' === $location ) {
			// Get the total count for comparison
			$total_count = $this->get_new_notifications_count();

			// If we have more than the limit, show the limit with a "+"
			if ( $total_count > $limit_active ) {
				$display_count = $limit_active . '+';
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo charitable_render(
			$template_location,
			[
				'notifications' => [
					'active_count'    => $display_count,
					'dismissed_count' => $dismissed_notifications_count,
					'active_html'     => $active_notifications_html,
					'dismissed_html'  => $dismissed_notifications_html,
				],
			],
			true
		);
	}

	/**
	 * Get notificaiton icon html based on if the type is a success, warning, info.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return string
	 */
	private function get_notification_icon_html( $notification ) {

		$icon = '';

		$notification_type = ! empty( $notification['notification_type'] ) ? sanitize_text_field( $notification['notification_type'] ) : '';

		switch ( $notification_type ) {
			case 'warning':
				$html = '<div class="icon">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-circle-exclamation warning">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M11.99 2.00012C6.47 2.00012 2 6.48012 2 12.0001C2 17.5201 6.47 22.0001 11.99 22.0001C17.52 22.0001 22 17.5201 22 12.0001C22 6.48012 17.52 2.00012 11.99 2.00012ZM13 13.0001V7.00012H11V13.0001H13ZM13 17.0001V15.0001H11V17.0001H13ZM4 12.0001C4 16.4201 7.58 20.0001 12 20.0001C16.42 20.0001 20 16.4201 20 12.0001C20 7.58012 16.42 4.00012 12 4.00012C7.58 4.00012 4 7.58012 4 12.0001Z" fill="currentColor"></path>
					</svg>
				</div>';
				break;
			case 'success':
				$html = '<div class="icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-circle-check success""><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM10 14.17L16.59 7.58L18 9L10 17L6 13L7.41 11.59L10 14.17Z" fill="currentColor"></path></svg></div>';
				break;
			default:
				$html = '<div class="icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="charitable-circle-check success""><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM10 14.17L16.59 7.58L18 9L10 17L6 13L7.41 11.59L10 14.17Z" fill="currentColor"></path></svg></div>';
				break;
		}

		return $html;
	}

	/**
	 * Get human readable date from MySQL timestamp.
	 *
	 * @since 1.8.3
	 *
	 * @param string $mysql_timestamp MySQL timestamp.
	 *
	 * @return bool
	 */
	private function human_readable_date_from_mysql( $mysql_timestamp = false ) {

		// Convert the MySQL timestamp to a Unix timestamp.
		$timestamp = strtotime( $mysql_timestamp );

		// Get the current time as a Unix timestamp.
		$current_time = current_time( 'timestamp' ); // phpcs:ignore

		// Calculate the difference in seconds.
		$time_diff = $current_time - $timestamp;

		// 30 days in seconds
		$thirty_days = 30 * 24 * 60 * 60;

		if ( $time_diff < $thirty_days ) {
			// If the timestamp is less than 30 days ago, return a 'time ago' format.
			return human_time_diff( $timestamp, $current_time ) . ' ago';
		} else {
			// If the timestamp is 30 days or more in the past, use 'F j, Y' format.
			return date( 'F j, Y', $timestamp );  // phpcs:ignore
		}
	}

	/**
	 * Retrieve notification's buttons HTML.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return string
	 */
	private function get_notification_buttons_html( $notification ) {

		$html = '';

		if ( empty( $notification['btns'] ) || ! is_array( $notification['btns'] ) ) {
			return $html;
		}

		foreach ( $notification['btns'] as $btn_type => $btn ) {

			$btn = $this->get_component_data( $btn );

			if ( ! $btn ) {
				continue;
			}

			$url    = $this->prepare_btn_url( $btn );
			$target = ! empty( $btn['target'] ) ? $btn['target'] : '_blank';
			$target = ! empty( $url ) && strpos( $url, home_url() ) === 0 ? '_self' : $target;

			$html .= sprintf(
				'<a href="%1$s" class="button button-%2$s"%3$s>%4$s</a>',
				esc_url( $url ),
				$btn_type === 'main' ? 'primary' : 'secondary',
				$target === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '',
				! empty( $btn['text'] ) ? esc_html( $btn['text'] ) : ''
			);
		}

		return ! empty( $html ) ? sprintf( '<div class="charitable-notifications-buttons">%s</div>', $html ) : '';
	}

	/**
	 * Retrieve notification's component data by a license type.
	 *
	 * @since 1.8.3
	 *
	 * @param mixed $data Component data.
	 *
	 * @return false|mixed
	 */
	private function get_component_data( $data ) {

		if ( empty( $data['license'] ) ) {
			return $data;
		}

		$license_type = $this->get_license_type();

		if ( in_array( $license_type, self::LICENSES_ELITE, true ) ) {
			$license_type = 'elite';
		}

		return ! empty( $data['license'][ $license_type ] ) ? $data['license'][ $license_type ] : false;
	}

	/**
	 * Retrieve the current installation license type (always lowercase).
	 *
	 * @since 1.8.3
	 *
	 * @return string
	 */
	private function get_license_type() {

		if ( $this->license_type ) {
			return $this->license_type;
		}

		if ( charitable_is_pro() ) {
			return 'pro';
		} else {
			return 'lite';
		}
	}

	/**
	 * Dismiss notification via AJAX.
	 *
	 * @since 1.8.3
	 */
	public function dismiss() {

		// Check for required param, security and access.
		if (
			empty( $_POST['notification_id'] ) ||
			! check_ajax_referer( 'charitable-admin', 'nonce', false ) ||
			! $this->has_access()
		) {
			wp_send_json_error();
		}

		$id     = sanitize_key( $_POST['notification_id'] );
		$type   = is_numeric( $id ) ? 'feed' : 'events';
		$option = $this->get_option();

		$option['dismissed'][] = $id;
		$option['dismissed']   = array_unique( $option['dismissed'] );

		update_option( 'charitable_notifications', $option );

		wp_send_json_success();
	}

	/**
	 * Dismiss multiple notifications via AJAX.
	 *
	 * @since 1.8.3
	 */
	public function dismiss_multiple() {

		// Check for required param, security and access.
		if (
			empty( $_POST['notification_ids'] ) ||
			! check_ajax_referer( 'charitable-admin', 'nonce', false ) ||
			! $this->has_access()
		) {
			wp_send_json_error();
		}

		if ( ! is_array( $_POST['notification_ids'] ) ) {
			wp_send_json_error();
		}

		$option           = $this->get_option();
		$notification_ids = array_map( 'sanitize_key', $_POST['notification_ids'] );
		foreach ( $notification_ids as $id ) {
			$type                  = is_numeric( $id ) ? 'feed' : 'events';
			$option['dismissed'][] = $id;
			$option['dismissed']   = array_unique( $option['dismissed'] );

		}

		update_option( 'charitable_notifications', $option );

		wp_send_json_success();
	}

	/**
	 * Prepare button URL.
	 *
	 * @since 1.8.3
	 *
	 * @param array $btn Button data.
	 *
	 * @return string
	 */
	private function prepare_btn_url( $btn ) {

		if ( empty( $btn['url'] ) ) {
			return '';
		}

		$replace_tags = [
			'{admin_url}' => admin_url(),
		];

		return str_replace( array_keys( $replace_tags ), array_values( $replace_tags ), $btn['url'] );
	}

	/**
	 * Get the notification's video badge HTML.
	 *
	 * @since 1.8.3
	 *
	 * @param string $video_url Valid video URL.
	 *
	 * @return string
	 */
	private function get_video_badge_html( $video_url ) {

		$video_url = wp_http_validate_url( $video_url );

		if ( empty( $video_url ) ) {
			return '';
		}

		$data_attr_lity = wp_is_mobile() ? '' : 'data-lity';

		return sprintf(
			'<a class="charitable-notifications-badge" href="%1$s" %2$s>%3$s</a>',
			esc_url( $video_url ),
			esc_attr( $data_attr_lity ),
			esc_html__( 'Watch Video', 'charitable' )
		);
	}

	/**
	 * Determine to show notifications on this page or not.
	 *
	 * @since 1.7.0.3
	 *
	 * @return boolean
	 */
	public function show_notifications() {
		if ( isset( $_GET['taxonomy'] ) ) { // phpcs:ignore
			return false;
		}
		if ( isset( $_GET['post_type'] ) && sanitize_text_field( $_GET['post_type'] ) === 'campaign' ) { // phpcs:ignore
			return true;
		}
		if ( isset( $_GET['post_type'] ) && sanitize_text_field( $_GET['post_type'] ) === 'donation' ) { // phpcs:ignore
			return true;
		}
		return false;
	}

	/**
	 * This will incercept the request and redirect to the dashboard page.
	 *
	 * @since 1.8.3
	 * @version 1.8.3.1
	 */
	public function maybe_redirect_from_notifications() {

		if ( ! empty( $_GET['page'] ) && 'charitable-notifications' === $_GET['page'] ) { // phpcs:ignore
			delete_transient( 'charitable_autoshow_plugin_notifications' );
			wp_safe_redirect( admin_url( 'admin.php?page=charitable-dashboard' ) );
			exit;
		}
	}
}
