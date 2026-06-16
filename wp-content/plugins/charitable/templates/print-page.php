<?php
/**
 * A template for printing pages.
 *
 * Override this template by copying it to yourtheme/charitable/print-page.php
 *
 * @package Charitable/Templates/Print-Page
 * @author  WP Charitable LLC
 * @since   1.6.57
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_assets_folder = plugins_url( 'assets', __DIR__ );
$charitable_version       = charitable()->get_version(); // phpcs:ignore

?>

<html>
	<head>
		<link rel='stylesheet' href='<?php echo esc_url( $charitable_assets_folder ); ?>/css/charitable.css?ver=<?php echo esc_attr( $charitable_version ); // phpcs:ignore ?>' media='all' />
		<link rel='stylesheet' href='<?php echo esc_url( $charitable_assets_folder ); ?>/css/charitable-print.css?ver=<?php echo esc_attr( $charitable_version ); // phpcs:ignore ?>' media='all' />
		<?php
			/**
			 * Add in extra stylesheets if needed
			 *
			 * @since 1.7.0
			 */
			do_action( 'charitable_print_styles' );
		?>
	</head>
	<body id="charitable-print-stage">
		<nav>
			<a onclick="print();">
				<svg width="24" height="24" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M17.5714 18H20.4C20.7314 18 21 17.7314 21 17.4V11C21 8.79086 19.2091 7 17 7H7C4.79086 7 3 8.79086 3 11V17.4C3 17.7314 3.26863 18 3.6 18H6.42857" stroke="currentColor" stroke-width="1.5"></path>
					<path d="M8 7V3.6C8 3.26863 8.26863 3 8.6 3H15.4C15.7314 3 16 3.26863 16 3.6V7" stroke="currentColor" stroke-width="1.5"></path>
					<path d="M6.09782 20.3151L6.42855 18L6.92639 14.5151C6.96862 14.2196 7.22177 14 7.52036 14H16.4796C16.7782 14 17.0313 14.2196 17.0736 14.5151L17.5714 18L17.9021 20.3151C17.9538 20.6766 17.6733 21 17.3082 21H6.69179C6.32666 21 6.04618 20.6766 6.09782 20.3151Z" stroke="currentColor" stroke-width="1.5"></path>
					<path d="M17 10.01L17.01 9.99889" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
				</svg>
				<?php esc_html_e( 'Print', 'charitable' ); ?>
			</a>
		</nav>
		<article>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<?php the_content(); ?>
		<?php endwhile; // end of the loop. ?>
		</article>
	</body>
</html>
