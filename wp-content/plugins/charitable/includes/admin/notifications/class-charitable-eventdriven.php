<?php
/**
 * Events for notification class.
 *
 * @package   Charitable/Classes/Charitable_Admin_Form
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EventDriven.
 *
 * @since 1.8.3
 */
class Charitable_EventDriven {

	/**
	 * The single instance of this class.
	 *
	 * @var Charitable_Notifications|null
	 */
	private static $instance = null;

	/**
	 * Charitable version when the Event Driven feature has been introduced.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	const FEATURE_INTRODUCED = '1.8.3';

	/**
	 * Expected date format for notifications.
	 *
	 * @since 1.8.3
	 *
	 * @var string
	 */
	const DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Common UTM parameters.
	 *
	 * @since 1.8.3
	 *
	 * @var array
	 */
	const UTM_PARAMS = [
		'utm_source' => 'WordPress',
		'utm_medium' => 'Event Notification',
	];

	/**
	 * Common targets for date logic.
	 *
	 * Available items:
	 *  - upgraded (upgraded to a latest version)
	 *  - activated
	 *  - campaigns_first_created
	 *  - X.X.X.X (upgraded to a specific version)
	 *  - pro (activated/installed)
	 *  - lite (activated/installed)
	 *
	 * @since 1.8.3
	 *
	 * @var array
	 */
	const DATE_LOGIC = [ 'upgraded', 'activated', 'campaigns_first_created' ];

	/**
	 * Timestamps.
	 *
	 * @since 1.8.3
	 *
	 * @var array
	 */
	private $timestamps = [];

	/**
	 * Create object instance.
	 *
	 * @since 1.8.3
	 */
	public function __construct() {
	}

	/**
	 * Initialize class.
	 *
	 * @since 1.8.3
	 */
	public function init() {

		if ( ! $this->allow_load() ) {
			return;
		}
	}

	/**
	 * Indicate if this is allowed to load.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	private function allow_load() {

		return charitable()->registry()->get( 'notifications' )->has_access() || wp_doing_cron();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.3
	 */
	private function hooks() {
	}

	/**
	 * Add Event Driven notifications before saving them in database.
	 *
	 * @since 1.8.3
	 *
	 * @param array $data Notification data.
	 *
	 * @return array
	 */
	public function update_events( $data ) {

		$updated = [];

		/**
		 * Allow developers to turn on debug mode: store all notifications and then show all of them.
		 *
		 * @since 1.8.3
		 *
		 * @param bool $is_debug True if it's a debug mode. Default: false.
		 */
		$is_debug = (bool) apply_filters( 'charitable_admin_notifications_event_driven_update_events_debug', false );

		$charitable_notifications = charitable()->registry()->get( 'notifications' );

		foreach ( $this->get_notifications() as $slug => $notification ) {

			$is_processed      = ! empty( $data['events'][ $slug ]['start'] );
			$is_conditional_ok = ! ( isset( $notification['condition'] ) && $notification['condition'] === false );

			// If it's a debug mode OR valid notification has been already processed - skip running logic checks and save it.
			if (
				$is_debug ||
				( $is_processed && $is_conditional_ok && $charitable_notifications->is_valid( $data['events'][ $slug ] ) )
			) {
				unset( $notification['date_logic'], $notification['offset'], $notification['condition'] );

				// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$notification['start'] = $is_debug ? date( self::DATE_FORMAT ) : $data['events'][ $slug ]['start'];
				$updated[ $slug ]      = $notification;

				continue;
			}

			// Ignore if a condition is not passed conditional checks.
			if ( ! $is_conditional_ok ) {
				continue;
			}

			$timestamp = $this->get_timestamp_by_date_logic(
				$this->prepare_date_logic( $notification )
			);

			if ( empty( $timestamp ) ) {
				continue;
			}

			// Probably, notification should be visible after some time.
			$offset = empty( $notification['offset'] ) ? 0 : absint( $notification['offset'] );

			// Set a start date when notification will be shown.
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$notification['start'] = date( self::DATE_FORMAT, $timestamp + $offset );

			// Ignore if notification data is not valid.
			if ( ! $charitable_notifications->is_valid( $notification ) ) {
				continue;
			}

			// Remove unnecessary values, mark notification as active, and save it.
			unset( $notification['date_logic'], $notification['offset'], $notification['condition'] );
			$updated[ $slug ] = $notification;
		}

		$data['events'] = $updated;

		return $data;
	}

	/**
	 * Prepare and retrieve date logic.
	 *
	 * @since 1.8.3
	 *
	 * @param array $notification Notification data.
	 *
	 * @return array
	 */
	private function prepare_date_logic( $notification ) {

		$date_logic = empty( $notification['date_logic'] ) || ! is_array( $notification['date_logic'] ) ? self::DATE_LOGIC : $notification['date_logic'];

		return array_filter( array_filter( $date_logic, 'is_string' ) );
	}

	/**
	 * Retrieve a notification timestamp based on date logic.
	 *
	 * @since 1.8.3
	 *
	 * @param array $args Date logic.
	 *
	 * @return int
	 */
	private function get_timestamp_by_date_logic( $args ) {

		foreach ( $args as $target ) {

			if ( ! empty( $this->timestamps[ $target ] ) ) {
				return $this->timestamps[ $target ];
			}

			$timestamp = call_user_func(
				$this->get_timestamp_callback( $target ),
				$target
			);

			if ( ! empty( $timestamp ) ) {
				$this->timestamps[ $target ] = $timestamp;

				return $timestamp;
			}
		}

		return 0;
	}

	/**
	 * Retrieve a callback that determines needed timestamp.
	 *
	 * @since 1.8.3
	 *
	 * @param string $target Date logic target.
	 *
	 * @return callable
	 */
	private function get_timestamp_callback( $target ) {

		$raw_target = $target;

		// As $target should be a part of name for callback method,
		// this regular expression allow lowercase characters, numbers, and underscore.
		$target = strtolower( preg_replace( '/[^a-z0-9_]/', '', $target ) );

		// Basic callback.
		$callback = [ $this, 'get_timestamp_' . $target ];

		// Determine if a special version number is passed.
		// Uses the regular expression to check a SemVer string.
		// @link https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string.
		if ( preg_match( '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:\.([1-9\d*]))?(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/', $raw_target ) ) {
			$callback = [ $this, 'get_timestamp_upgraded' ];
		}

		// If callback is callable, return it. Otherwise, return fallback.
		return is_callable( $callback ) ? $callback : '__return_zero';
	}

	/**
	 * Retrieve a timestamp when Charitable was upgraded.
	 *
	 * @since 1.8.3
	 *
	 * @param string $version Charitable version.
	 *
	 * @return int|false Unix timestamp. False on failure.
	 */
	private function get_timestamp_upgraded( $version = false ) {

		if ( false === $version || $version === 'upgraded' ) {
			$version = charitable()->get_version();
		}

		$timestamp = charitable_get_updated_timestamp( $version );

		if ( $timestamp === false ) {
			return false;
		}

		// Return a current timestamp if no luck to return a migration's timestamp.
		return $timestamp <= 0 ? time() : $timestamp;
	}

	/**
	 * Retrieve a timestamp when Charitable was first installed/activated.
	 *
	 * @since 1.8.3
	 *
	 * @return int|false Unix timestamp. False on failure.
	 */
	private function get_timestamp_activated() {

		return charitable_get_activated_timestamp();
	}

	/**
	 * Retrieve a timestamp when Lite was first installed.
	 *
	 * @since 1.8.3
	 *
	 * @return int|false Unix timestamp. False on failure.
	 */
	private function get_timestamp_lite() {

		$activated = (array) get_option( 'charitable_activated', [] );

		return ! empty( $activated['lite'] ) ? absint( $activated['lite'] ) : false;
	}

	/**
	 * Retrieve a timestamp when Pro was first installed.
	 *
	 * @since 1.8.3
	 *
	 * @return int|false Unix timestamp. False on failure.
	 */
	private function get_timestamp_pro() {

		$activated = (array) get_option( 'charitable_activated', [] );

		return ! empty( $activated['pro'] ) ? absint( $activated['pro'] ) : false;
	}

	/**
	 * Retrieve a timestamp when a first form was created.
	 *
	 * @since 1.8.3
	 *
	 * @return int|false Unix timestamp. False on failure.
	 */
	private function get_timestamp_campaigns_first_created() {

		$timestamp = get_option( 'charitable_campaigns_first_created' );

		return ! empty( $timestamp ) ? absint( $timestamp ) : false;
	}

	/**
	 * Retrieve a number of entries.
	 *
	 * @since 1.8.3
	 *
	 * @return int
	 */
	private function get_entry_count() {

		static $count;

		if ( is_int( $count ) ) {
			return $count;
		}

		global $wpdb;

		$count              = 0;
		$entry_handler      = charitable()->get( 'entry' );
		$entry_meta_handler = charitable()->get( 'entry_meta' );

		if ( ! $entry_handler || ! $entry_meta_handler ) {
			return $count;
		}

		$query = "SELECT COUNT({$entry_handler->primary_key})
				FROM {$entry_handler->table_name}
				WHERE {$entry_handler->primary_key}
				NOT IN (
					SELECT entry_id
					FROM {$entry_meta_handler->table_name}
					WHERE type = 'backup_id'
				);";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $wpdb->get_var( $query ); // phpcs:ignore

		return $count;
	}

	/**
	 * Retrieve campaigns.
	 *
	 * @since 1.8.3
	 *
	 * @param int $posts_per_page Number of form to return.
	 *
	 * @return array
	 */
	private function get_campaigns( $posts_per_page ) { // phpcs:ignore

		return ! empty( $campaigns ) ? (array) $campaigns : [];
	}

	/**
	 * Determine if the user has at least 1 form.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	private function has_form() {

		return ! empty( $this->get_campaigns( 1 ) );
	}

	/**
	 * Determine if it is a new user, usually by checking if the plugin has been updated or first installed.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	private function is_new_user() {

		$activated = (array) get_option( 'charitable_activated', array() );

		if ( empty( $activated ) || ! is_array( $activated ) ) {
			delete_transient( 'charitable_autoshow_plugin_notifications' );
			return true;
		}

		// Find for the oldest unix timestamp in the values of $activated and assign it to a variable.
		$activated_time = max( $activated );

		// If the timestamp eariler than 30 days from current time, then the user is new.
		$is_new_user = ( time() - $activated_time ) < 30 * DAY_IN_SECONDS;

		if ( ! $is_new_user ) {
			return false;
		}

		$versions_upgraded_from = get_option( 'charitable_version_upgraded_from', [] );

		if ( empty( $versions_upgraded_from ) ) {
			return true;
		}

		// Get the first value in the $versions_upgraded_from array.
		$version = reset( $versions_upgraded_from );

		// If the version equals the current version, then it is a new user.
		$is_new_user = version_compare( $version, charitable()->get_version(), '=' );

		if ( $is_new_user ) {
			delete_transient( 'charitable_autoshow_plugin_notifications' );
			return true;
		}

		return false;
	}

	/**
	 * Determine if it's an English site.
	 *
	 * @since 1.8.3
	 *
	 * @return bool
	 */
	private function is_english_site() {

		static $result;

		if ( is_bool( $result ) ) {
			return $result;
		}

		$locales = array_unique(
			array_map(
				[ $this, 'language_to_iso' ],
				[ get_locale(), get_user_locale() ]
			)
		);
		$result  = count( $locales ) === 1 && $locales[0] === 'en';

		return $result;
	}

	/**
	 * Convert language to ISO.
	 *
	 * @since 1.8.3
	 *
	 * @param string $lang Language value.
	 *
	 * @return string
	 */
	private function language_to_iso( $lang ) {

		return $lang === '' ? $lang : explode( '_', $lang )[0];
	}

	/**
	 * Retrieve a modified URL query string.
	 *
	 * @since 1.8.3
	 *
	 * @param array  $args An associative array of query variables.
	 * @param string $url  A URL to act upon.
	 *
	 * @return string
	 */
	private function add_query_arg( $args, $url ) {

		return add_query_arg(
			array_merge( $this->get_utm_params(), array_map( 'rawurlencode', $args ) ),
			$url
		);
	}

	/**
	 * Retrieve UTM parameters for Event Driven notifications links.
	 *
	 * @since 1.8.3
	 *
	 * @return array
	 */
	private function get_utm_params() {

		static $utm_params;

		if ( ! $utm_params ) {
			$utm_params = [
				'utm_source'   => self::UTM_PARAMS['utm_source'],
				'utm_medium'   => rawurlencode( self::UTM_PARAMS['utm_medium'] ),
				'utm_campaign' => charitable_is_pro() ? 'plugin' : 'liteplugin',
			];
		}

		return $utm_params;
	}

	/**
	 * Retrieve Event Driven notifications.
	 *
	 * @since 1.8.3
	 *
	 * @return array
	 */
	private function get_notifications() {

		return [
			'welcome-message' => [
				'id'        => 'welcome-message',
				'title'     => esc_html__( 'Welcome to Charitable!', 'charitable' ),
				'content'   => esc_html__( 'We’re grateful that you chose Charitable! Now that you’ve installed the plugin, you’re less than 5 minutes away from publishing your first campaign. To make it easy, we’ve got a checklist to get you started!', 'charitable' ),
				'btns'      => [
					'main' => [
						'url'  => admin_url( 'admin.php?page=charitable-setup-checklist' ),
						'text' => esc_html__( 'Start Checklist', 'charitable' ),
					],
					'alt'  => [
						'url'  => $this->add_query_arg(
							[ 'utm_content' => 'Welcome Read the Guide' ],
							'https://wpcharitable.com/documentation/creating-your-first-campaign/'
						),
						'text' => esc_html__( 'Read the Guide', 'charitable' ),
					],
				],
				'type'      => [
					'lite',
					'basic',
					'plus',
					'pro',
					'agency',
					'elite',
					'ultimate',
					'lifetime',
				],
				// Immediately after activation (new users only, not upgrades).
				'condition' => $this->is_new_user(),
			],
		];
	}

	/**
	 * Returns and/or create the single instance of this class.
	 *
	 * @since  1.8.3
	 *
	 * @return Charitable_EventDriven
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
