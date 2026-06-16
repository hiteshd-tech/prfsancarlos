<?php
/**
 * Charitable Core Functions.
 *
 * General core functions.
 *
 * @package   Charitable/Functions/Core
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.37
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This returns the original Charitable object.
 *
 * Use this whenever you want to get an instance of the class. There is no
 * reason to instantiate a new object, though you can do so if you're stubborn :)
 *
 * @since  1.0.0
 *
 * @return Charitable
 */
function charitable() {
	return Charitable::get_instance();
}

/**
 * This returns the value for a particular Charitable setting.
 *
 * @since  1.0.0
 *
 * @param  mixed $key          Accepts an array of strings or a single string.
 * @param  mixed $default      The value to return if key is not set.
 * @param  array $settings     Optional. Used when $key is an array.
 * @param  mixed $original_key Optional. Original array of keys.
 * @return mixed
 */
function charitable_get_option( $key, $default = false, $settings = array(), $original_key = array() ) {
	if ( empty( $settings ) ) {
		$settings = get_option( 'charitable_settings' );
	}

	if ( ! is_array( $key ) ) {
		$key = array( $key );
	}

	$current_key = current( $key );

	if ( empty( $original_key ) ) {
		$original_key = $key;
	}

	/* Key does not exist */
	if ( ! isset( $settings[ $current_key ] ) ) {
		return $default;
	}

	array_shift( $key );

	if ( ! empty( $key ) ) {
		return charitable_get_option( $key, $default, $settings[ $current_key ], $original_key );
	}

	/**
	 * Filter the option value.
	 *
	 * @since 1.6.37
	 *
	 * @param mixed $value   The option value.
	 * @param mixed $key     The key, or list of keys.
	 * @param mixed $default The default value.
	 */
	return apply_filters( 'charitable_option_' . $current_key, $settings[ $current_key ], $original_key, $default );
}

/**
 * Returns a helper class.
 *
 * @since  1.0.0
 *
 * @param  string $class_key The class to get an object for.
 * @return mixed|false
 */
function charitable_get_helper( $class_key ) {
	return charitable()->registry()->get( $class_key );
}

/**
 * Returns the Charitable_Notices class instance.
 *
 * @since  1.0.0
 *
 * @return Charitable_Notices
 */
function charitable_get_notices() {
	return charitable()->registry()->get( 'notices' );
}

/**
 * Returns the Charitable_Donation_Processor class instance.
 *
 * @since  1.0.0
 *
 * @return Charitable_Donation_Processor
 */
function charitable_get_donation_processor() {
	$registry = charitable()->registry();

	if ( ! $registry->has( 'donation_processor' ) ) {
		$registry->register_object( Charitable_Donation_Processor::get_instance() );
	}

	return $registry->get( 'donation_processor' );
}

/**
 * Return Charitable_Locations helper class.
 *
 * @since  1.0.0
 *
 * @return Charitable_Locations
 */
function charitable_get_location_helper() {
	return charitable()->registry()->get( 'locations' );
}

/**
 * Returns the current user's session object.
 *
 * @since  1.0.0
 *
 * @return Charitable_Session
 */
function charitable_get_session() {
	return charitable()->registry()->get( 'session' );
}

/**
 * Returns the current request helper object.
 *
 * @since  1.0.0
 *
 * @return Charitable_Request
 */
function charitable_get_request() {
	$registry = charitable()->registry();

	if ( ! $registry->has( 'request' ) ) {
		$registry->register_object( Charitable_Request::get_instance() );
	}

	return $registry->get( 'request' );
}

/**
 * Returns the Charitable_User_Dashboard object.
 *
 * @since  1.0.0
 *
 * @return Charitable_User_Dashboard
 */
function charitable_get_user_dashboard() {
	return charitable()->registry()->get( 'user_dashboard' );
}

/**
 * Return the database table helper object.
 *
 * @since  1.0.0
 *
 * @param  string $table The table key.
 * @return mixed|null A child class of Charitable_DB if table exists. null otherwise.
 */
function charitable_get_table( $table ) {
	$charitable = function_exists( 'charitable' ) ? charitable() : null;

	if ( empty( $charitable ) || ! is_object( $charitable ) || ! method_exists( $charitable, 'get_db_table' ) ) {
		return null;
	}

	return $charitable->get_db_table( $table );
}

/**
 * Returns the current donation form.
 *
 * @since  1.0.0
 *
 * @return Charitable_Donation_Form_Interface|false
 */
function charitable_get_current_donation_form() {
	$campaign = charitable_get_current_campaign();
	return false === $campaign ? false : $campaign->get_donation_form();
}

/**
 * Returns the provided array as a HTML element attribute.
 *
 * @since  1.0.0
 *
 * @param  array $args Arguments to be added.
 * @return string
 */
function charitable_get_action_args( $args ) {
	return sprintf( "data-charitable-args='%s'", wp_json_encode( $args ) );
}

/**
 * Returns the Charitable_Deprecated class, loading the file if required.
 *
 * @since  1.4.0
 *
 * @return Charitable_Deprecated
 */
function charitable_get_deprecated() {
	$registry = charitable()->registry();

	if ( ! $registry->has( 'deprecated' ) ) {
		$registry->register_object( Charitable_Deprecated::get_instance() );
	}

	return $registry->get( 'deprecated' );
}

/**
 * Returns if the check (license check or otherwise) determines if the install is "pro".
 *
 * @since  1.7.0
 *
 * @return boolean
 */
function charitable_is_pro() {

	if ( charitable_get_helper( 'licenses' )->is_pro() ) {
		return true;
	}

	return false;
}

/**
 * Returns if Charitable is currently using built-in Stripe Connect
 *
 * @since  1.7.0
 *
 * @return boolean
 */
function charitable_using_stripe_connect() {

	// the option gets written when the stripe connect in the core plugin (starting in v1.7.0) is connected in gateway settings in the admin.
	// the option is removed when, after the stripe connect is connected, the user clicks on the "disconnect" link is clicked in the settings.

	$charitable_stripe_connect = get_option( 'charitable_using_stripe_connect' );

	if ( $charitable_stripe_connect ) {
		return true;
	}

	return false;
}

/**
 * Returns if Charitable is currently using built-in Square "Connect". Similar to charitable_using_stripe_connect().
 * But the difference is that it can check for a specific mode.
 *
 * @since  1.8.7
 *
 * @param  string $mode_to_check The mode to check for. Should be 'test' or 'live'. If it's sandbox, rename it to 'test'.
 * @return boolean
 */
function charitable_using_square_connect( $mode_to_check = '' ) {
	if ( '' === $mode_to_check ) {
		return null;
	}

	if ( charitable_is_debug( 'square' ) ) {
		// phpcs:disable
		error_log( 'USING AND BEING FORCED charitable_using_square_connect: ' . $mode_to_check );
		// phpcs:enable
	}

	return true;
}

/**
 * A top level fundtion to get Access Token for Square.
 *
 * @since 1.8.7
 *
 * @param string $mode The mode to get the access token for.
 * @return string
 */
function charitable_square_get_access_token( $mode = '' ) {
	// If there is no $mode being "forced", we get the mode from the settings.
	if ( empty( $mode ) ) {
		$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
	}
	if ( 1 == $mode ) { // phpcs:ignore
		$mode = 'test';
	}
	// Now that we have a mode, let's see if we are using Square connect or legacy settings.
	if ( charitable_using_square_connect( $mode ) ) {
		$square_settings = charitable_get_option( 'gateways_square' );
		$access_token    = ! empty( $square_settings[ $mode ]['access_token'] ) ? $square_settings[ $mode ]['access_token'] : '';
		// This is an encrypted token.
		$access_token = charitable_crypto_decrypt( $access_token );
	} else {
		$access_token = charitable_get_option( array( 'gateways_square', $mode, 'access_token' ) );
	}
	return esc_html( $access_token );
}

/**
 * Get the refresh token for Square.
 *
 * @since 1.8.7
 *
 * @param string $mode The mode to get the refresh token for.
 * @return string
 */
function charitable_square_get_refresh_token( $mode = '' ) {
	// If there is no $mode being "forced", we get the mode from the settings.
	if ( empty( $mode ) ) {
		$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
	}
	if ( 1 == $mode ) { // phpcs:ignore
		$mode = 'test';
	}
	// Now that we have a mode, let's see if we are using Square connect or legacy settings.
	if ( charitable_using_square_connect( $mode ) ) {
		$square_settings = charitable_get_option( 'gateways_square' );
		$access_token    = ! empty( $square_settings[ $mode ]['refresh_token'] ) ? $square_settings[ $mode ]['refresh_token'] : '';
		// This is an encrypted token.
		$access_token = charitable_crypto_decrypt( $access_token );
	} else {
		$access_token = charitable_get_option( array( 'gateways_square', $mode, 'refresh_token' ) );
	}
	return esc_html( $access_token );
}

/**
 * Check if Square is connected.
 *
 * @since 1.8.7
 *
 * @param string $mode The mode to check for.
 */
function charitable_square_is_connected( $mode = '' ) {
	if ( empty( $mode ) ) {
		$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
	}

	return charitable_square_get_access_token( $mode ) ? true : false;
}

/**
 * A top level function to get Application ID for Square.
 *
 * @since 1.8.7
 *
 * @return mixed
 */
function charitable_square_get_application_id( $mode = 'test' ) {

	if ( empty( $mode ) ) {
		$mode = charitable_get_option( 'test_mode' ) ? 'test' : 'live';
	}

	if ( 'sandbox' === $mode ) {
		$mode = 'test';
	}

	// Are we overridng with legacy settings?
	if ( charitable_square_legacy_mode() ) {
		$application_id = charitable_get_option( 'gateways_square', $mode . '_application_id' );
		return $application_id['application_id'];
	}

	// Get from settings or use default.
	$settings       = charitable_get_option( 'gateways_square' );
	$application_id = ! empty( $settings[ $mode ]['application_id'] ) ? esc_html( $settings[ $mode ]['application_id'] ) : '';

	if ( empty( $application_id ) ) {
		// Default to sandbox application ID.
		$application_id = 'sandbox-sq0idb-xxxxxxxxxxxxxxxxxxxxxxxx';
	}

	return $application_id;
}

/**
 * Returns if should load the core stripe functionality.
 *
 * @since  1.7.0
 *
 * @return boolean
 */
function charitable_load_core_stripe() {

	if ( false !== ( defined( 'USE_NEW_STRIPE' ) && USE_NEW_STRIPE ) ) { // phpcs:ignore
		return true;
	}

	// check for stripe addon.

	if ( in_array( 'charitable-stripe/charitable-stripe.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { // phpcs:ignore
		return false;
	}

	if ( class_exists( 'Charitable_Stripe' ) ) {
		return false;
	}

	return true;
}

/**
 * Returns if charitable debug should be on for an admin screen, even if the constant isn't defined/false.
 *
 * @since  1.7.0.2
 *
 * @return boolean
 */
function charitable_is_admin_debug() {

	if ( isset( $_GET['charitable_debug'] ) && 'true' == $_GET['charitable_debug'] ) { // phpcs:ignore
		return true;
	}

	return false;
}

/**
 * Returns if charitable is in break-cache mode, which is a mode that attempts to break cache for certain styles and scripts.
 *
 * @since  1.8.4.2
 *
 * @return boolean
 */
function charitable_is_break_cache() {
	return ( charitable_is_debug() || ( defined( 'CHARITABLE_BREAK_CACHE_STYLES' ) && CHARITABLE_BREAK_CACHE_STYLES ) ) ? true : false;
}

/**
 * Returns if charitable is in debug mode, mostly by checking the constant.
 * Supports 'vendor', 'settings', 'stripe', and 'square' modes and whatever is passed in.
 *
 * @since   1.8.0
 * @version 1.8.6.1 Revisited to allow for more granular debug modes.
 * @version 1.8.7.1 Revisited so if modes are passed in and the constant is not defined, it will return false.
 *
 * @param string $mode Optional. 'vendor' to check for vendor debug mode.
 *
 * @return boolean
 */
function charitable_is_debug( $mode = '' ) {

	if ( ! empty( $mode ) ) {
		$constant = 'CHARITABLE_DEBUG_' . strtoupper( $mode );
		if ( defined( $constant ) ) {
			return constant( $constant ) ? true : false;
		}
		return false;
	}

	return ( defined( 'CHARITABLE_DEBUG' ) && CHARITABLE_DEBUG ) ? true : false; // phpcs:ignore
}


/**
 * Returns if charitable is in script debug mode, mostly by checking the constant.
 *
 * @since  1.8.0
 *
 * @return boolean
 */
function charitable_is_script_debug() {

	return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? true : false; // phpcs:ignore
}

/**
 * Checks for the existence of the legacy dashboard constant.
 *
 * @since  1.8.1
 *
 * @return boolean
 */
function charitable_use_legacy_dashboard() {

	return ( defined( 'CHARITABLE_LEGACY_DASHBOARD' ) && CHARITABLE_LEGACY_DASHBOARD ) ? true : false;
}


/**
 * Format, sanitize, and return/echo HTML element ID, classes, attributes,
 * and data attributes.
 *
 * @since 1.3.7
 *
 * @param string $id    HTML id attribute value.
 * @param array  $class A list of classnames for the class attribute.
 * @param array  $datas Data attributes.
 * @param array  $atts  Any additional HTML attributes and their values.
 * @param bool   $echo  Whether to echo the output or just return it. Defaults to return.
 *
 * @return string|void
 */
function charitable_html_attributes( $id = '', $class = array(), $datas = array(), $atts = array(), $echo = false ) {

	$id    = trim( $id );
	$parts = array();

	if ( ! empty( $id ) ) {
		$id = sanitize_html_class( $id );

		if ( ! empty( $id ) ) {
			$parts[] = 'id="' . $id . '"';
		}
	}

	if ( ! empty( $class ) ) {
		$class = charitable_sanitize_classes( $class, true );

		if ( ! empty( $class ) ) {
			$parts[] = 'class="' . $class . '"';
		}
	}

	if ( ! empty( $datas ) ) {
		foreach ( $datas as $data => $val ) {
			$parts[] = 'data-' . sanitize_html_class( $data ) . '="' . esc_attr( $val ) . '"';
		}
	}

	if ( ! empty( $atts ) ) {
		foreach ( $atts as $att => $val ) {
			if ( '0' === (string) $val || ! empty( $val ) ) {
				if ( $att[0] === '[' ) {
					// Handle special case for bound attributes in AMP.
					$escaped_att = '[' . sanitize_html_class( trim( $att, '[]' ) ) . ']';
				} else {
					$escaped_att = sanitize_html_class( $att );
				}
				$parts[] = $escaped_att . '="' . esc_attr( $val ) . '"';
			}
		}
	}

	$output = implode( ' ', $parts );

	if ( $echo ) {
		echo trim( $output ); // phpcs:ignore
	} else {
		return trim( $output );
	}
}


/**
 * Sanitize string of CSS classes.
 *
 * @since 1.8.0
 *
 * @param array|string $classes CSS classes.
 * @param bool         $convert True will convert strings to array and vice versa.
 *
 * @return string|array
 */
function charitable_sanitize_classes( $classes, $convert = false ) {

	$array = is_array( $classes );
	$css   = array();

	if ( ! empty( $classes ) ) {
		if ( ! $array ) {
			$classes = explode( ' ', trim( $classes ) );
		}
		foreach ( array_unique( $classes ) as $class ) {
			if ( ! empty( $class ) ) {
				$css[] = sanitize_html_class( $class );
			}
		}
	}

	if ( $array ) {
		return $convert ? implode( ' ', $css ) : $css;
	}

	return $convert ? $css : implode( ' ', $css );
}

/**
 * Add UTM tags to a link that allows detecting traffic sources for our or partners' websites.
 *
 * @since 1.7.5
 *
 * @param string $link    Link to which you need to add UTM tags.
 * @param string $medium  The page or location description. Check your current page and try to find
 *                        and use an already existing medium for links otherwise, use a page name.
 * @param string $content The feature's name, the button's content, the link's text, or something
 *                        else that describes the element that contains the link.
 * @param string $term    Additional information for the content that makes the link more unique.
 *
 * @return string
 */
function charitable_utm_link( $link, $medium, $content = '', $term = '' ) {

	return add_query_arg(
		array_filter(
			array(
				'utm_campaign' => charitable_is_pro() ? 'plugin' : 'liteplugin',
				'utm_source'   => strpos( $link, 'https://wpcharitable.com' ) === 0 ? 'WordPress' : 'charitableplugin',
				'utm_medium'   => rawurlencode( $medium ),
				'utm_content'  => rawurlencode( $content ),
				'utm_term'     => rawurlencode( $term ),
			)
		),
		$link
	);
}

/**
 * Get an upgrade link.
 *
 * @since 1.8.0
 *
 * @param string $medium  The page or location description.
 * @param string $content Content.
 *
 * @return string
 */
function charitable_admin_upgrade_link( $medium, $content = 'Upgrade+to+Pro' ) {

	return charitable_utm_link( 'https://wpcharitable.com/lite-vs-pro/', $medium, $content, false );
}

/**
 * Get an upgrade modal text.
 *
 * @since 1.8.0
 *
 * @param string $help_id Referrer code to pass to the help link.
 *
 * @return string
 */
function charitable_help_link( $help_id = false ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( false === $help_id ) {
		$screen = get_current_screen();
		if ( $screen && ! empty( $screen->base ) ) {

			switch ( esc_attr( $screen->base ) ) {
				case 'edit':
					$help_id = 'general';
					break;

				default:
					$help_id = 'general';
					break;
			}
		}
	}

	return 'https://www.wpcharitable.com/documentation/?utm_campaign=liteplugin&utm_source=WordPress&utm_medium=help&utm_content=help-' . $help_id;
}

/**
 * Get an upgrade modal text.
 *
 * @since 1.8.0
 *
 * @param string $type Either "pro" or "elite". Default is "pro".
 *
 * @return string
 */
function charitable_get_upgrade_modal_text( $type = 'pro' ) {

	switch ( $type ) {
		case 'basic':
			$level = 'Charitable Basic';
			break;
		case 'plus':
			$level = 'Charitable Basic';
			break;
		case 'agency':
			$level = 'Charitable Basic';
			break;
		case 'pro':
		default:
			$level = 'Charitable Pro';
	}

	if ( charitable_is_pro() ) {
		return '<p>' .
			sprintf(
				wp_kses( /* translators: %s - WPCharitable.com contact page URL. */
					__( 'Thank you for considering upgrading. If you have any questions, please <a href="%s" target="_blank" rel="noopener noreferrer">let us know</a>.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				esc_url(
					charitable_utm_link(
						'https://wpcharitable.com/contact/',
						'Upgrade Follow Up Modal',
						'Contact Support'
					)
				)
			) .
			'</p>' .
			'<p>' .
			wp_kses(
				__( 'After upgrading, your license key will remain the same.<br>You may need to do a quick refresh to unlock your new addons. In your WordPress admin, go to <strong>Charitable &raquo; Settings</strong>. If you don\'t see your updated plan, click <em>refresh</em>.', 'charitable' ),
				array(
					'strong' => array(),
					'br'     => array(),
					'em'     => array(),
				)
			) .
			'</p>' .
			'<p>' .
			sprintf(
				wp_kses( /* translators: %s - WPCharitable.com upgrade license docs page URL. */
					__( 'Check out <a href="%s" target="_blank" rel="noopener noreferrer">our documentation</a> for step-by-step instructions.', 'charitable' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				'https://wpcharitable.com/docs/upgrade-charitable-license/'
			) .
			'</p>';
	}

	return '<p>' .
		sprintf(
			wp_kses( /* translators: %s - WPCharitable.com contact page URL. */
				__( 'If you have any questions or issues just <a href="%s" target="_blank" rel="noopener noreferrer">let us know</a>.', 'charitable' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			esc_url(
				charitable_utm_link(
					'https://wpcharitable.com/contact/',
					'Upgrade Intention Alert',
					'Upgrade Intention Alert'
				)
			)
		) .
		'</p>' .
		'<p>' .
		sprintf(
			wp_kses( /* translators: %s - license level, Charitable Pro or Charitable Elite. */
				__( 'After purchasing a license, just <strong>enter your license key on the Charitable Settings page</strong>. This will let your site automatically upgrade to %s! (Don\'t worry, all your campaigns, donations and settings will be preserved.)', 'charitable' ),
				array(
					'strong' => array(),
					'br'     => array(),
				)
			),
			$level
		) .
		'</p>' .
		'<p>' .
		sprintf(
			wp_kses( /* translators: %s - WPCharitable.com upgrade from Lite to paid docs page URL. */
				__( 'Check out <a href="%s" target="_blank" rel="noopener noreferrer">our documentation</a> for step-by-step instructions.', 'charitable' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			esc_url(
				charitable_utm_link(
					'https://wpcharitable.com/lite-vs-pro/',
					'Upgrade Intention Alert',
					'Upgrade Documentation'
				)
			)
		) .
		'</p>';
}

/**
 * Perform json_decode and unslash.
 *
 * IMPORTANT: This function decodes the result of charitable_encode() properly only if
 * wp_insert_post() or wp_update_post() were used after the data is encoded.
 * Both wp_insert_post() and wp_update_post() remove excessive slashes added by charitable_encode().
 *
 * Using charitable_decode() on charitable_encode() result directly
 * (without using wp_insert_post() or wp_update_post() first) always returns null or false.
 *
 * @since 1.0.0
 *
 * @param string $data Data to decode.
 *
 * @return array|false|null
 */
function charitable_decode( $data ) {

	if ( ! $data || empty( $data ) ) {
		return false;
	}

	return wp_unslash( json_decode( $data, true ) );
}

/**
 * Perform json_encode and wp_slash.
 *
 * IMPORTANT: This function adds excessive slashes to prevent data damage
 * by wp_insert_post() or wp_update_post() that use wp_unslash() on all the incoming data.
 *
 * Decoding the result of this function by charitable_decode() directly
 * (without using wp_insert_post() or wp_update_post() first) always returns null or false.
 *
 * @since 1.3.1.3
 *
 * @param mixed $data Data to encode.
 *
 * @return string|false
 */
function charitable_encode( $data = false ) {

	if ( empty( $data ) ) {
		return false;
	}

	return wp_slash( wp_json_encode( $data ) );
}

/**
 * Decode json-encoded string if it is in json format.
 *
 * @since 1.7.5
 *
 * @param string $the_string      A string.
 * @param bool   $associative Decode to the associative array if true. Decode to object if false.
 *
 * @return array|string
 */
function charitable_json_decode( $the_string, $associative = false ) {

	$the_string = html_entity_decode( $the_string );

	if ( function_exists( 'charitable_is_json' ) && ! charitable_is_json( $the_string ) ) {
		return $the_string;
	}

	return json_decode( $the_string, $associative );
}

/**
 * Check permissions for currently logged in user, taken from Charitable.
 * Both short (e.g. 'view_own_forms') or long (e.g. 'charitable_view_own_forms') capability name can be used.
 * Only Charitable capabilities get processed.
 *
 * @since 1.7.0.3
 *
 * @param array|string $caps Capability name(s).
 * @param int          $id   ID of the specific object to check against if capability is a "meta" cap. "Meta"
 *                           capabilities, e.g. 'edit_post', 'edit_user', etc., are capabilities used by
 *                           map_meta_cap() to map to other "primitive" capabilities, e.g. 'edit_posts',
 *                           edit_others_posts', etc. Accessed via func_get_args() and passed to
 *                           WP_User::has_cap(), then map_meta_cap().
 *
 * @return bool
 */
function charitable_current_user_can( $caps = array(), $id = 0 ) {

	$user_can = current_user_can( $caps, $id );

	return apply_filters( 'charitable_current_user_can', $user_can, $caps, $id );
}

/**
 * Get a suffix for assets, if SCRIPT_DEBUG or CHARITABLE_DEBUG are 'true' then it's blank, otherwise it's minimial (`.min`)'.
 *
 * @since 1.8.0
 *
 * @return string
 */
function charitable_get_min_suffix() {

	return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( charitable_is_debug() ) || ( ! charitable_is_script_minification_enabled() ) ? '' : '.min';
}

/**
 * Get a version for style unqueues. If SCRIPT_DEBUG or CHARITABLE_DEBUG are 'true' then force a flush cache with time() otherwise it's the Chartiable version.
 *
 * @since 1.8.0
 *
 * @return string
 */
function charitable_get_style_version() {
	return ( charitable_is_break_cache() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( charitable_is_debug() ) ) ? time() : charitable()->get_version();
}

/**
 * User can override the minification setting via advanced settings.
 *
 * @since 1.8.1.9
 *
 * @return string
 */
function charitable_is_script_minification_enabled() {

	return 'scripts-enabled' === charitable_get_option( 'script_minification', 'scripts-enabled' );
}

/**
 * Returns an array of screen IDs where the Charitable scripts should be loaded.
 * Used to be get_charitable_screens() in class-charitable-admin.php.
 *
 * @uses   charitable_admin_screens
 *
 * @since  1.0.0
 *
 * @return array
 */
function charitable_get_charitable_screens() {
	/**
	 * Filter admin screens where Charitable styles & scripts should be loaded.
	 *
	 * @since 1.8.0
	 * @version 1.8.1 Added `charitable_page_charitable-dashboard` and 'charitable_page_charitable-reports' to the list of screens.
	 * @version 1.8.1.6 Added 'charitable_page_charitable-tools' and 'charitable_page_charitable-growth-tool' to the list of screens.
	 * @version 1.8.1.15 Added to core functions, added 'charitable_page_charitable-setup-checklist' to the list of screens.
	 * @version 1.8.5 Added 'charitable_page_charitable-donors' to the list of screens.
	 *
	 * @param string[] $screens List of screen ids.
	 */
		return apply_filters(
		'charitable_admin_screens',
		array(
			'campaign',
			'donation',
			'charitable_page_charitable-reports',
			'charitable_page_charitable-dashboard',
			'charitable_page_charitable-settings',
			'charitable_page_charitable-tools',
			'charitable_page_charitable-growth-tools',
			'edit-campaign',
			'edit-donation',
			'toplevel_page_charitable',
			'charitable_page_charitable-addons',
			'charitable_page_charitable-setup-checklist',
			'charitable_page_charitable-donors',
			'charitable_page_charitable-about',
			'charitable_page_charitable-seo',
			'charitable_page_charitable-smtp',
			'charitable_page_charitable-privacy-compliance',
			'charitable_page_charitable-backups',
			'charitable_page_charitable-automation',
		)
	);
}

/**
 * Determines if it's ok to show plugin notifications when the dashboard page is visited.
 * Shouldn't do it every time, so we limit it.
 *
 * Returns true to show the notifications, false to not auto show them.
 *
 * @since  1.8.3
 *
 * @return bool
 */
function charitable_get_autoshow_plugin_notifications() {

	if ( false === ( $autoshow_plugin = get_transient( 'charitable_autoshow_plugin_notifications' ) ) ) {
		// It wasn't there, so regenerate the data and save the transient.
		set_transient( 'charitable_autoshow_plugin_notifications', true, 60 * 60 ); // one hour.
		return true;
	} else {
		return false;
	}
}

/**
 * Check if Square legacy mode is enabled.
 *
 * @since 1.8.7
 *
 * @return boolean
 */
function charitable_square_legacy_mode() {

	$settings = charitable_get_option( 'gateways_square' );

	// Debug: Log the settings and plugin status.
	if ( charitable_is_debug( 'square' ) ) {
		// phpcs:disable
		error_log( '[Square Legacy Mode] Settings: ' . print_r( $settings, true ) );
		error_log( '[Square Legacy Mode] Plugin active: ' . ( is_plugin_active( 'charitable-square/charitable-square.php' ) ? 'true' : 'false' ) );
		error_log( '[Square Legacy Mode] square_legacy_settings: ' . ( isset( $settings['square_legacy_settings'] ) ? $settings['square_legacy_settings'] : 'not set' ) );
		// phpcs:enable
	}

	// Check if the legacy Square plugin is installed and activated.
	if ( ! is_plugin_active( 'charitable-square/charitable-square.php' ) ) {
		if ( charitable_is_debug( 'square' ) ) {
			// phpcs:disable
			error_log( '[Square Legacy Mode] Returning false - plugin not active' );
			// phpcs:enable
		}
		return false;
	}

	// Check if Square Legacy gateway is active.
	$active_gateways      = charitable_get_helper( 'gateways' )->get_active_gateways();
	$square_legacy_active = isset( $active_gateways['square'] );

	if ( charitable_is_debug( 'square' ) ) {
		// phpcs:disable
		error_log( '[Square Legacy Mode] Square Legacy gateway active: ' . ( $square_legacy_active ? 'true' : 'false' ) );
		// phpcs:enable
	}

	// Return true if either the settings option is set OR the gateway is active.
	if ( ( isset( $settings['square_legacy_settings'] ) && $settings['square_legacy_settings'] ) || $square_legacy_active ) {
		if ( charitable_is_debug( 'square' ) ) {
			// phpcs:disable
			error_log( '[Square Legacy Mode] Returning true - settings or gateway active' );
			// phpcs:enable
		}
		return true;
	}

	if ( charitable_is_debug( 'square' ) ) {
		// phpcs:disable
		error_log( '[Square Legacy Mode] Returning false' );
		// phpcs:enable
	}
	return false;
}

/**
 * Check if charitable-square plugin is active.
 *
 * @since 1.8.7
 *
 * @return boolean
 */
function charitable_is_square_addon_active() {
	return is_plugin_active( 'charitable-square/charitable-square.php' );
}
