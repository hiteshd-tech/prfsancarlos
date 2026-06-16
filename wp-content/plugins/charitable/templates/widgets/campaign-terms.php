<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Display a list of campaign categories or tags.
 *
 * Override this template by copying it to yourtheme/charitable/widgets/campaign-terms.php
 *
 * @package Charitable/Templates/Widgets
 * @author  WP Charitable LLC
 * @since   1.0.0
 * @version 1.0.0
 */

$taxonomy   = isset($view_args['taxonomy']) ? $view_args['taxonomy'] : 'campaign_category'; // phpcs:ignore
$show_count = isset($view_args['show_count']) && $view_args['show_count']; // phpcs:ignore
$hide_empty = isset($view_args['hide_empty']) && $view_args['hide_empty']; // phpcs:ignore

echo $view_args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! empty( $view_args['title'] ) ) :

	echo $view_args['before_title'] . esc_html( $view_args['title'] ) . $view_args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

endif;
?>
<ul class="charitable-terms-widget">
	<?php
	wp_list_categories(
		array(
		'title_li'   => '',
		'taxonomy'   => $taxonomy,
		'show_count' => $show_count,
		'hide_empty' => $hide_empty,
		)
	);
	?>
</ul><!-- .charitable-terms-widget -->
<?php

echo $view_args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
