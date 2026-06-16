<?php
/**
 * Charitable Campaign Functions.
 *
 * Campaign related functions.
 *
 * @package   Charitable/Functions/Campaign
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.54
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the given campaign.
 *
 * @since  1.0.0
 *
 * @param  int $campaign_id The campaign ID.
 * @return Charitable_Campaign
 */
function charitable_get_campaign( $campaign_id ) {
	return new Charitable_Campaign( $campaign_id );
}

/**
 * Create a campaign.
 *
 * @since  1.5.9
 *
 * @param  array $args Values for the campaign.
 * @return int
 */
function charitable_create_campaign( array $args ) {
	$processor = new Charitable_Campaign_Processor( $args );
	return $processor->save();
}

/**
 * Returns the current campaign.
 *
 * @since  1.0.0
 *
 * @return Charitable_Campaign|false Campaign object if we're viewing a campaign
 *                                   within a loop. False otherwise.
 */
function charitable_get_current_campaign() {
	return charitable_get_request()->get_current_campaign();
}

/**
 * Returns the current campaign ID.
 *
 * @since  1.0.0
 *
 * @return int
 */
function charitable_get_current_campaign_id() {
	return charitable_get_request()->get_current_campaign_id();
}

/**
 * Returns the current campaign template 'slug'.
 *
 * @since  1.8.0
 *
 * @return string
 */
function charitable_get_current_campaign_template() {
	return charitable_get_request()->get_current_campaign_template();
}

/**
 * Returns the current campaign template 'slug'.
 *
 * @since  1.8.0
 *
 * @return string
 */
function charitable_get_current_campaign_template_id() {
	return charitable_get_request()->get_current_campaign_template_id();
}

/**
 * Returns the campaign data (v2).
 *
 * @since  1.8.0
 *
 * @return string
 */
function charitable_get_current_campaign_data() {
	return charitable_get_request()->get_current_campaign_builder_data();
}

/**
 * Returns whether the current user is the creator of the given campaign.
 *
 * @since  1.0.0
 *
 * @param  int $campaign_id The campaign ID.
 * @return boolean
 */
function charitable_is_current_campaign_creator( $campaign_id = null ) {
	if ( is_null( $campaign_id ) ) {
		$campaign_id = charitable_get_current_campaign_id();
	}

	return get_post_field( 'post_author', $campaign_id ) == get_current_user_id();
}

/**
 * Returns whether the given campaign can receive donations.
 *
 * @since  1.5.14
 *
 * @param  int $campaign_id The campaign ID.
 * @return boolean
 */
function charitable_campaign_can_receive_donations( $campaign_id ) {
	if ( Charitable::CAMPAIGN_POST_TYPE !== get_post_type( $campaign_id ) ) {
		return false;
	}

	$campaign = charitable_get_campaign( $campaign_id );

	return $campaign && $campaign->can_receive_donations();
}

/**
 * Given a campaign ID and a key, return the post field.
 *
 * @since  1.6.0
 *
 * @param  Charitable_Campaign $campaign The campaign object.
 * @param  string              $key      The meta key.
 * @return mixed
 */
function charitable_get_campaign_post_field( Charitable_Campaign $campaign, $key ) {
	if ( ! isset( $campaign->ID ) || 0 === intval( $campaign->ID ) ) {
		return false;
	}

	return get_post_field( $key, $campaign->ID );
}

/**
 * Get the campaign date as a timestamp.
 *
 * @since  1.6.54
 *
 * @param  Charitable_Campaign $campaign The campaign instance.
 * @return int
 */
function charitable_get_campaign_date_as_timestamp( Charitable_Campaign $campaign ) {
	return strtotime( $campaign->post_date );
}

/**
 * Get a particular user field for a campaign's creator.
 *
 * @since  1.6.5
 *
 * @param  Charitable_Campaign $campaign The campaign object.
 * @param  string              $key      The meta key.
 * @return string
 */
function charitable_get_campaign_creator_field( Charitable_Campaign $campaign, $key ) {
	$creator = charitable_get_user( $campaign->get_campaign_creator() );
	$key     = str_replace( 'campaign_creator_', '', $key );

	return $creator->get( $key );
}

/**
 * Get a particular taxonomy field for a campaign.
 *
 * @since  1.6.19
 *
 * @param  Charitable_Campaign $campaign The campaign object.
 * @param  string              $key      The meta key.
 * @return string
 */
function charitable_get_campaign_taxonomy_terms_list( Charitable_Campaign $campaign, $key ) {
	$taxonomies = array(
		'categories' => 'campaign_category',
		'tags'       => 'campaign_tag',
	);

	$taxonomy = array_key_exists( $key, $taxonomies ) ? $taxonomies[ $key ] : $key;
	$terms    = wp_get_object_terms( $campaign->ID, $taxonomy, array( 'fields' => 'names' ) );

	if ( is_wp_error( $terms ) ) {
		return '';
	}

	return implode( ', ', $terms );
}

/**
 * Get the featured image for a particular campaign.
 *
 * @since  1.6.25
 *
 * @param  Charitable_Campaign $campaign The campaign object.
 * @return string|int
 */
function charitable_get_campaign_featured_image( Charitable_Campaign $campaign ) {
	return get_post_thumbnail_id( $campaign->ID );
}

/**
 * Return the default templaate (usually for front-end previews or campaign pages/shortcodes) for campaign builder campaign areas.
 *
 * @since  1.8.0
 *
 * @return string
 */
function charitable_campaign_builder_default_template() {

	return apply_filters( 'charitable_campaign_builder_default_template', 'basic' );
}

/**
 * Return name of user - trying first/last name and falling back on "display name".
 *
 * @since  1.8.0
 *
 * @param  int $user_id ID of user.
 * @return string
 */
function charitable_get_creator_data( $user_id = null ) {

	$user_info = $user_id ? new WP_User( $user_id ) : wp_get_current_user();

	if ( false === $user_info || is_wp_error( $user_info ) ) {
		return false;
	}

	if ( $user_info->first_name ) {
		if ( $user_info->last_name ) {
			return $user_info->first_name . ' ' . $user_info->last_name;
		}
		return $user_info->first_name;
	}

	return $user_info->display_name;
}

/**
 * Return the admin URL for editing a campaign.
 *
 * @since  1.8.1
 *
 * @param  int $campaign_id ID of donation, assuming it still exists.
 * @return string
 */
function charitable_get_admin_campaign_edit_url( $campaign_id = false ) {

	if ( false === $campaign_id || intval( $campaign_id ) === 0 ) {
		return false;
	}

	// Determine if this is a "legacy" campaign.
	$is_legacy_campaign = charitable_is_campaign_legacy( $campaign_id );

	// Determines if we add anything due to debug mode(s).
	$suffix = ( charitable_is_debug() || charitable_is_script_debug() ) ? '#charitabledebug' : false;

	$admin_campaign_edit_url = ( ! $is_legacy_campaign ) ? admin_url( 'admin.php?page=charitable-campaign-builder&campaign_id=' . $campaign_id ) . $suffix : admin_url( 'post.php?post=' . $campaign_id . '&action=edit' );

	return $admin_campaign_edit_url;
}

/**
 * Return the admin URL for editing a campaign.
 *
 * @since  1.8.1
 *
 * @param  int $donation_id ID of donation, assuming it still exists.
 * @return string
 */
function charitable_get_admin_donation_edit_url( $donation_id = false ) {

	if ( false === $donation_id || intval( $donation_id ) === 0 ) {
		return false;
	}

	$admin_donation_edit_url = admin_url( 'post.php?post=' . $donation_id ) . '&action=edit';

	return $admin_donation_edit_url;
}

/**
 * Find the photo in the (v2) campaign settings.
 *
 * @since 1.8.2
 *
 * @param  array $campaign_settings The campaign settings.
 *
 * @return mixed
 */
function charitable_find_photo_in_campaign_settings( $campaign_settings ) {

	$layout = isset( $campaign_settings['layout'] ) ? $campaign_settings['layout'] : false;

	if ( ! $layout ) {
		return false;
	}

	$found_row_id   = -1;
	$found_field_id = -1;

	foreach ( $layout['rows'] as $row_id => $layout_row ) {
		$found_row_id = $row_id;
		if ( ! empty( $layout_row['fields'] ) ) {
			foreach ( $layout_row['fields'] as $field_id => $layout_field_type ) {
				if ( $layout_field_type === 'photo' ) {
					$found_field_id = $field_id;
					break;
				}
			}
		}
	}

	if ( $found_field_id < 0 || $found_row_id < 0 ) {
		return false;
	}

	$found_field_id = apply_filters( 'charitable_campaign_photo_field_id', $found_field_id, $campaign_settings );

	// get file url.
	$file_url = ! empty( $campaign_settings['fields'][ $found_field_id ] ) ? ( $campaign_settings['fields'][ $found_field_id ] ) : false;
	$media_id = chartitable_get_image_attached_id_from_url( $file_url['file'] );

	return array(
		'file'     => $file_url['file'],
		'media_id' => $media_id,
	);
}

/**
 * Charitable's function to get the attachment ID from the image URL.
 * To be used sparingly, and likely only in the context of the campaign builder. Cache the results to be planned.
 *
 * @since 1.8.2
 *
 * @param  string $image_url The image URL.
 *
 * @return mixed
 */
function chartitable_get_image_attached_id_from_url( $image_url = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- This is a legacy function that cannot be renamed.
	global $wpdb;
	if ( ! $image_url ) {
		return false;
	}
	$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) ); // phpcs:ignore
	if ( ! empty( $attachment[0] ) ) {
		return $attachment[0];
	}
	return false;
}

/**
 * Find the description in the (v2) campaign settings.
 *
 * @since  1.8.2
 *
 * @param  array $campaign_settings The campaign settings.
 * @param  int   $description_limit The description limit.
 *
 * @return string
 */
function charitable_find_description_in_campaign_settings( $campaign_settings = false, $description_limit = 0 ) {

	if ( ! $campaign_settings ) {
		return false;
	}

	$layout = isset( $campaign_settings['layout'] ) ? $campaign_settings['layout'] : false;

	if ( ! $layout ) {
		return false;
	}

	$description    = '';
	$found_row_id   = -1;
	$found_field_id = -1;

	foreach ( $layout['rows'] as $row_id => $layout_row ) {
		$found_row_id = (int) $row_id;
		if ( ! empty( $layout_row['fields'] ) ) {
			foreach ( $layout_row['fields'] as $field_id => $layout_field_type ) {
				if ( $layout_field_type === 'campaign-description' ) {
					$found_field_id = (int) $field_id;
					break;
				}
			}
		}
	}

	$found_field_id = apply_filters( 'charitable_campaign_description_field_id', $found_field_id, $campaign_settings );

	if ( $found_field_id >= 0 && $found_row_id >= 0 ) {
		$description = ! empty( $campaign_settings['fields'][ $found_field_id ] ) && ! empty( $campaign_settings['fields'][ $found_field_id ]['content'] ) ? ( $campaign_settings['fields'][ $found_field_id ]['content'] ) : false;
	}

	// limit the description if the value is greater than 0.
	// add the "..." if the description is longer than the limit.
	if ( false !== $description && '' !== trim( $description ) && $description_limit > 0 ) {
		$description = wp_trim_words( $description, $description_limit, '...' );
	}

	if ( $description ) {
		return $description;
	}

	$found_row_id   = -1;
	$found_field_id = -1;

	foreach ( $layout['rows'] as $row_id => $layout_row ) {
		$found_row_id = $row_id;
		if ( ! empty( $layout_row['fields'] ) ) {
			foreach ( $layout_row['fields'] as $field_id => $layout_field_type ) {
				if ( $layout_field_type === 'text' ) {
					$found_field_id = (int) $field_id;
					break;
				}
			}
		}
	}

	if ( $found_field_id < 0 || $found_row_id < 0 ) {
		return false;
	}

	$description = ! empty( $campaign_settings['fields'][ $found_field_id ] ) ? ( $campaign_settings['fields'][ $found_field_id ]['content'] ) : false;

	// limit the description if the value is greater than 0.
	// add the "..." if the description is longer than the limit.
	if ( false !== $description && '' !== trim( $description ) && $description_limit > 0 ) {
		$description = wp_trim_words( $description, $description_limit, '...' );
	}

	return apply_filters( 'charitable_campaign_settings_description', $description, $campaign_settings );
}
