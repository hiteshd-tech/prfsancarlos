<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper functions to work with licenses, permissions and capabilities.
 *
 * @package Charitable
 * @since 1.8.0
 * @phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found
 */

/**
 * Search for posts editable by user.
 *
 * @since 1.8.0
 * @version 1.8.8.6
 *
 * @param string $search_term Optional search term. Default ''.
 * @param array  $args        Args {
 *                            Optional. An array of arguments.
 *
 * @type string   $post_type   Post type to search for.
 * @type string[] $post_status Post status to search for.
 * @type int      $count       Number of results to return. Default 20.
 * }
 *
 * @return array
 * @noinspection PhpTernaryExpressionCanBeReducedToShortVersionInspection
 * @noinspection ElvisOperatorCanBeUsedInspection
 */
function charitable_campaign_search_posts( $search_term = '', $args = [] ) {

	global $wpdb;

	$default_args = [
		'post_type'   => 'page',
		'post_status' => [ 'publish' ],
		'count'       => 20,
	];
	$args         = wp_parse_args( $args, $default_args );

	// @todo: add trash access capabilities to MySQL.
	// See edit_post/edit_page case in map_meta_cap().
	$args['post_status'] = array_diff( $args['post_status'], [ 'trash' ] );

	$user      = wp_get_current_user();
	$user_id   = $user ? $user->ID : 0;
	$post_type = get_post_type_object( $args['post_type'] );

	if ( ! $user_id || ! $post_type || $args['count'] <= 0 ) {
		return [];
	}

	$last_changed = wp_cache_get_last_changed( 'posts' );
	$key          = __FUNCTION__ . ":$search_term:$last_changed";
	$cache_posts  = wp_cache_get( $key, '', false, $found );

	if ( $found ) {
		return $cache_posts;
	}

	$post_title_where = $search_term ? $wpdb->prepare(
		'post_title LIKE %s AND',
		'%' . $wpdb->esc_like( $search_term ) . '%'
	) :
		'';

	$post_statuses_array        = array_intersect( array_keys( get_post_statuses() ), $args['post_status'] );
	$post_statuses              = charitable_wpdb_prepare_in( $post_statuses_array );
	$policy_id                  = (int) get_option( 'wp_page_for_privacy_policy' );
	$can_delete_published_posts = (int) $user->has_cap( $post_type->cap->delete_published_posts );
	$can_delete_posts           = (int) $user->has_cap( $post_type->cap->delete_posts );
	$can_delete_others_posts    = (int) $user->has_cap( $post_type->cap->delete_others_posts );
	$can_delete_private_posts   = (int) $user->has_cap( $post_type->cap->delete_private_posts );
	$can_edit_policy            = (int) $user->has_cap( map_meta_cap( 'manage_privacy_options', $user_id )[0] );

	// For the case when user is post author.
	// Integers are safe as they're cast to int above.
	$capability_author_where = "post_author = $user_id AND
		( ( post_status IN ( 'publish', 'future' ) AND $can_delete_published_posts ) OR
		( ( post_status NOT IN ( 'publish', 'future', 'trash' ) ) AND $can_delete_posts )
		)";

	// For the case when accessing someone other's post.
	$capability_other_where = "post_author != $user_id AND
		$can_delete_others_posts AND
		( ( post_status IN ( 'publish', 'future' ) AND $can_delete_published_posts ) OR
		( ( post_status IN ( 'private' ) ) AND $can_delete_private_posts )
		)";

	// For privacy policy page.
	$capability_policy_where = "ID = $policy_id AND $can_edit_policy";

	$capability_where = '( ' .
		'(' . $capability_author_where . ') OR ' .
		'(' . $capability_other_where . ') OR ' .
		'(' . $capability_policy_where . ')' .
		' )';

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
	// $post_title_where is already prepared by $wpdb->prepare() on line 59.
	// $post_statuses is already prepared by charitable_wpdb_prepare_in().
	// $capability_where uses integers that are cast to int, so they're safe.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$posts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_title, post_author
					FROM $wpdb->posts
					WHERE $post_title_where
					post_type = %s AND
					post_status IN ( $post_statuses ) AND
					$capability_where
					ORDER BY post_title LIMIT %d",
			$args['post_type'],
			absint( $args['count'] )
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

	$posts = $posts ? $posts : [];
	$posts = array_map(
		static function ( $post ) {
			$post->post_title = charitable_get_post_title( $post );

			unset( $post->post_author );

			return $post;
		},
		$posts
	);

	wp_cache_set( $key, $posts );

	return $posts;
}

/**
 * Search pages by search term and return an array containing
 * `value` and `label` which is the post ID and post title respectively.
 *
 * @since 1.7.9
 *
 * @param string $search_term The search term.
 * @param array  $args        Optional. An array of arguments.
 *
 * @return array
 */
function charitable_search_pages_for_dropdown( $search_term, $args = [] ) {

	$search_results = charitable_campaign_search_posts( $search_term, $args );
	$result_pages   = [];

	// Prepare for ChoicesJS render.
	foreach ( $search_results as $search_result ) {
		$result_pages[] = [
			'value' => absint( $search_result->ID ),
			'label' => esc_html( $search_result->post_title ),
		];
	}

	return $result_pages;
}
