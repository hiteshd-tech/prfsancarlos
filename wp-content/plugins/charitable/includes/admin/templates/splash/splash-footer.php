<?php
/**
 * What's New modal footer.
 *
 * @since 1.8.7
 *
 * @var string $title Footer title.
 * @var string $description Footer content.
 * @var array $upgrade Upgrade link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<footer>
	<div class="charitable-splash-footer-content">
		<h2><?php echo esc_html( $footer['title'] ); ?></h2>
		<p><?php echo esc_html( $footer['description'] ); ?></p>
	</div>
	<a href="<?php echo esc_url( $footer['upgrade']['url'] ); ?>" class="charitable-btn charitable-btn-green" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $footer['upgrade']['text'] ); ?></a>
</footer>
