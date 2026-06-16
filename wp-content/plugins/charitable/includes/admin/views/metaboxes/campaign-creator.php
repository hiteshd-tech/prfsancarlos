<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Campaign Creator metabox.
 *
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @since     1.2.0
 * @version   1.6.45
 * @version   1.8.8.6
 */

global $post, $wpdb;

/* If no post_author is set yet, WP returns 1, but we need to know if no creator is set. */
$creator_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_author FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post->ID ) ); // phpcs:ignore
$charitable_campaign   = new Charitable_Campaign( $post );
$charitable_authors    = get_users(
	[
		'orderby' => 'display_name',
		'fields'  => [ 'ID', 'display_name' ],
	]
);

?>
<div id="charitable-campaign-creator-metabox-wrap" class="charitable-metabox-wrap">
	<?php
	if ( $creator_id ) :
		$charitable_creator = new Charitable_User( $creator_id );
		?>
		<div id="campaign-creator" class="charitable-media-block">
			<div class="creator-avatar charitable-media-image">
				<?php echo $charitable_creator->get_avatar(); //phpcs:ignore ?>
			</div><!--.creator-avatar-->
			<div class="creator-facts charitable-media-body">
				<h3 class="creator-name"><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . esc_attr( $charitable_creator->ID ) ) ); ?>"><?php printf( '%s (%s %d)', esc_html( $charitable_creator->display_name ), esc_html__( 'User ID', 'charitable' ), esc_html( $charitable_creator->ID ) ); ?></a></h3>
				<p><?php printf( '%s %s', esc_html_x( 'Joined on', 'joined on date', 'charitable' ), esc_html( date_i18n( 'F Y', strtotime( $charitable_creator->user_registered ) ) ) ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( get_author_posts_url( $charitable_creator->ID ) ); ?>"><?php esc_html_e( 'Public Profile', 'charitable' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $charitable_creator->ID ) ); ?>"><?php esc_html_e( 'Edit Profile', 'charitable' ); ?></a></li>
				</ul>
			</div><!--.creator-facts-->
		</div><!--#campaign-creator-->
	<?php endif; ?>
	<div id="charitable-post-author-wrap" class="charitable-metabox charitable-select-wrap">
		<label for="post_author_override"><?php esc_html_e( 'Change the campaign creator', 'charitable' ); ?></label>
		<select name="post_author_override">
			<option value="-" <?php selected( $creator_id, 0 ); ?>><?php esc_html_e( 'Select a user', 'charitable' ); ?></option>
			<?php foreach ( $charitable_authors as $charitable_author ) : ?>
			<option value="<?php echo esc_attr( $charitable_author->ID ); ?>" <?php selected( $creator_id, $charitable_author->ID ); ?>><?php echo esc_html( $charitable_author->display_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div><!--#charitable-campaign-description-metabox-wrap-->
