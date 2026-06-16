<?php
/**
 * General functions made for the campaign builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the list of allowed tags, used in pair with wp_kses() function.
 * This allows getting rid of all potentially harmful HTML tags and attributes.
 *
 * @since 1.8.0
 *
 * @return array Allowed Tags.
 */
function charitable_builder_preview_get_allowed_tags() {

	static $allowed_tags;

	if ( ! empty( $allowed_tags ) ) {
		return $allowed_tags;
	}

	$atts = [ 'align', 'class', 'type', 'id', 'for', 'style', 'src', 'rel', 'href', 'target', 'value', 'width', 'height' ];
	$tags = [ 'label', 'iframe', 'style', 'button', 'strong', 'small', 'table', 'span', 'abbr', 'code', 'pre', 'div', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ol', 'ul', 'li', 'em', 'hr', 'br', 'th', 'tr', 'td', 'p', 'a', 'b', 'i' ];

	$allowed_atts = array_fill_keys( $atts, [] );
	$allowed_tags = array_fill_keys( $tags, $allowed_atts );

	return $allowed_tags;
}

/**
 * Return URL to form preview page.
 *
 * @since 1.8.0
 *
 * @param int    $campaign_id    Form ID.
 * @param bool   $new_window New window flag.
 * @param string $post_status Post status of item.
 *
 * @return string
 */
function charitable_get_campaign_preview_url( $campaign_id = false, $new_window = false, $post_status = false ) {

	if ( intval( $campaign_id ) === 0 ) {
		return false;
	}

	if ( false === $post_status ) {
		$post_status = get_post_status( $campaign_id );
	}

	if ( $post_status === 'publish' || $post_status === 'private' || $post_status === 'draft' || $post_status === 'public' ) {

		$url = add_query_arg(
			[
				'p'                           => absint( $campaign_id ),
				'charitable_campaign_preview' => absint( $campaign_id ),
			],
			home_url()
		);

		if ( $new_window ) {
			$url = add_query_arg(
				[
					'new_window' => 1,
				],
				$url
			);
		}

	} else {

		$url = get_preview_post_link( $campaign_id );

	}

	return $url;
}

/**
 * Determine if we are on the campaign builder admin page
 *
 * @since 1.8.0
 */
function campaign_is_campaign_builder_admin_page() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a legacy function that cannot be renamed.

	if ( isset( $_POST['campaign_id'] ) ) { // @codingStandardsIgnoreLine
		return true;
	}

	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'charitable-campaign-builder' ) { // @codingStandardsIgnoreLine
		return false;
	} else {
		return true;
	}
}

/**
 * Get template data from an existing campaign.
 *
 * @since 1.8.0
 *
 * @param int $campaign_id Form ID.
 */
function charitable_get_template_data_from_campaign( $campaign_id = false ) {

	if ( false === intval( $campaign_id ) ) {
		return false;
	}

	$settings = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

	if ( ! empty( $settings ) ) {
		return $settings;
	}

	if ( ! empty( $settings['template_id'] ) && ! empty( $settings['template_label'] ) ) {

		return array(
			'template_id' => esc_attr( $settings['template_id'] ),
			esc_html( $settings['template_label'] ),
		);

	} else {

		return false;
	}
}

/**
 * Get template data from an existing campaign.
 *
 * @since 1.8.0
 *
 * @param slug $template_id Template ID.
 */
function charitable_show_field_names_by_default( $template_id = false ) { // phpcs:ignore

	if ( charitable_is_debug() || ( defined( 'CHARITABLE_BUILDER_SHOW_FIELD_NAMES' ) && CHARITABLE_BUILDER_SHOW_FIELD_NAMES ) ) {
		return apply_filters( 'charitable_builder_show_field_names_default', true, $template_id );
	}

	return false;
}

/**
 * Check and see if preview mode is shown by default.
 *
 * @since 1.8.0
 *
 * @param slug $template_id Template ID.
 */
function charitable_show_preview_mode_by_default( $template_id = false ) {

	return apply_filters( 'charitable_builder_show_preview_mode_default', false, $template_id );
}

/**
 * Generate a tooltip.
 *
 * @since 1.8.0
 *
 * @param string $tooltip_text The tooltip text.
 * @param string $extra_css Any additional CSS classes.
 */
function charitable_get_tooltip_html( $tooltip_text = false, $extra_css = '' ) {

	if ( false === $tooltip_text ) {
		return false;
	}

	$html = sprintf( '<span class="charitable-help-tooltip-container"><img src="' . charitable()->get_path( 'assets', false ) . 'images/icons/info.svg" alt="" class="charitable-help-tooltip ' . esc_attr( $extra_css ) . '" title="%s"></i></span>', esc_html( $tooltip_text ) );

	return $html;
}

/**
 * Get the list of allowed users who can create and/or edit campaigns.
 *
 * @since 1.8.3.2
 *
 * @param string $by 'permissions' or 'roles'.
 * @param array  $additional_allowed_users_id Additional users to add to the list.
 *
 * @return array Allowed Tags.
 */
function charitable_get_users_as_campaign_creators( $by = 'permissions', $additional_allowed_users_id = [] ) {

	// Check the transient first.
	$allowed_users = get_transient( 'charitable_allowed_campaign_creators_by_' . $by );

	if ( false !== $allowed_users ) {
		return $allowed_users;
	}

	$allowed_users = apply_filters( 'charitable_allowed_campaign_creators', [] );
	if ( ! empty( $allowed_users ) ) {
		return $allowed_users;
	}

	if ( 'roles' === $by ) {

		$allowed_users = get_users( [ 'role__in' => [ 'administrator', 'campaign_manager' ] ] );

	} elseif ( 'permissions' === $by ) {

		$all_users = get_users();

		$users_with_permissions = [];

		if ( ! is_array( $allowed_users ) ) {
			$allowed_users = [];
		}

		foreach ( $all_users as $user ) {

			if ( user_can( $user->ID, 'create_campaigns' ) || user_can( $user->ID, 'edit_campaigns' ) ) { // phpcs:ignore
				$allowed_users[] = $user;
			}

		}
	}

	// If there was any additional users passed in, add them to the list.
	if ( $additional_allowed_users_id ) {
		$users         = get_users( [ 'include' => $additional_allowed_users_id ] );
		$allowed_users = array_merge( $users, $allowed_users );
	}

	// Add this to a transient that expires in one hour.
	set_transient( 'charitable_allowed_campaign_creators_by_' . $by, $allowed_users, HOUR_IN_SECONDS );

	return $allowed_users;
}
