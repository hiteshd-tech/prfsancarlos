<?php
/**
 * Social media icons CSS partial - shared across all campaign templates.
 *
 * This file generates CSS for social media icon background images using
 * proper absolute URLs instead of broken relative URLs.
 *
 * @package   Charitable/CSS-Templates/Admin/Partials
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.9.2
 * @version   1.8.9.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure required variables are available.
if ( ! isset( $wrapper ) || ! isset( $icon_style ) ) {
	return;
}

// Define available social media icons.
$social_icons = array(
	'twitter',
	'facebook',
	'linkedin',
	'instagram',
	'pinterest',
	'tiktok',
	'mastodon',
	'youtube',
	'threads',
	'bluesky',
);

// Determine icon suffix based on style.
$icon_suffix = ( $icon_style === 'white' ) ? '-white' : '-dark';

/**
 * Filter the social media icons to include in templates.
 *
 * @since   1.8.9.2
 * @version 1.8.9.4
 *
 * @param array  $social_icons List of social media icon names.
 * @param string $icon_style   Icon style ('dark' or 'white').
 */
$social_icons = apply_filters( 'charitable_campaign_builder_social_icons', $social_icons, $icon_style );

// Output CSS for each social media icon.
foreach ( $social_icons as $icon ) {
	$icon_path = "images/campaign-builder/fields/social-links/{$icon}{$icon_suffix}.svg";
	$icon_url  = charitable_get_campaign_builder_asset_url( $icon_path );

	// Skip if asset URL couldn't be generated.
	if ( empty( $icon_url ) ) {
		continue;
	}

	// Output CSS for both social linking and social sharing variants.
	?>
/* <?php echo esc_html( ucfirst( $icon ) ); ?> Icon */
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-<?php echo esc_attr( $icon ); ?> .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-<?php echo esc_attr( $icon ); ?> .charitable-placeholder {
	background-image: url('<?php echo esc_url( $icon_url ); ?>');
}
	<?php
}

/**
 * Allow templates to add custom social icon CSS.
 *
 * @since   1.8.9.2
 * @version 1.8.9.4
 *
 * @param string $wrapper    CSS wrapper selector.
 * @param string $icon_style Icon style ('dark' or 'white').
 */
do_action( 'charitable_campaign_builder_social_icons_css', $wrapper, $icon_style );
?>