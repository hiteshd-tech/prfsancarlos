<?php
/**
 * What's New modal section.
 *
 * @since   1.8.8
 * @version 1.8.9.1
 *
 * @var string $title Section title.
 * @var string $content Section content.
 * @var array $img Section image.
 * @var string $new Is new feature.
 * @var array $buttons Section buttons.
 * @var string $layout Section layout.
 * @package Charitable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// These are local template variables, not global variables. They are scoped to this template file.
$classes = [
	'charitable-splash-section',
	'charitable-splash-section-' . $section['layout'],
	'charitable-splash-section-' . $section['class'],
];
$classes_output = charitable_sanitize_classes( $classes, true );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>

<section class="<?php echo esc_attr( $classes_output ); ?>">
	<div class="charitable-splash-section-content">
		<?php
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		// Local template variables scoped to this conditional block.
		if ( ! empty( $section['new'] ) ) {
			$version_text = ! empty( $section['version'] ) ? ' ' . $section['version'] : '';
			printf(
				'<span class="charitable-splash-badge">%s</span>',
				esc_html(
					// translators: %s is the version number
					sprintf( __( 'New Feature%s', 'charitable' ), $version_text )
				)
			);
		} elseif ( ! empty( $section['new-addon'] ) ) {
			$version_text = ! empty( $section['version'] ) ? ' ' . $section['version'] : '';
			printf(
				'<span class="charitable-splash-badge">%s</span>',
				esc_html(
					// translators: %s is the version number
					sprintf( __( 'New Addon%s', 'charitable' ), $version_text )
				)
			);
		} elseif ( ! empty( $section['new-for-pro'] ) ) {
			$version_text = ! empty( $section['version'] ) ? ' ' . $section['version'] : '';
			printf(
				'<span class="charitable-splash-badge">%s</span>',
				esc_html(
					// translators: %s is the version number
					sprintf( __( 'New for Pro%s', 'charitable' ), $version_text )
				)
			);
		}
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		?>
		<h3><?php echo esc_html( $section['title'] ); ?></h3>
		<p><?php echo wp_kses_post( $section['content'] ); ?></p>

		<?php if ( ! empty( $section['buttons'] ) ) : ?>
			<div class="charitable-splash-section-buttons">
				<?php
				// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				// Local template variables scoped to this foreach loop.
				foreach ( $section['buttons'] as $button_type => $button ) {
					$button_class = $button_type === 'main' ? 'charitable-btn-orange' : 'charitable-btn-bordered';

					printf(
						'<a href="%1$s" class="charitable-btn %3$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( $button['url'] ),
						esc_html( $button['text'] ),
						esc_attr( $button_class )
					);
				}
				// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $section['img'] ) ) : ?>
		<?php
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local template variable scoped to this conditional block.
		$shadow_class = charitable_sanitize_classes( $section['img']['shadow'] ?? 'none' );
		?>
		<div class="charitable-splash-section-image charitable-image-shadow-<?php echo esc_attr( $shadow_class ); ?>">
			<img src="<?php echo esc_url( $section['img']['url'] ); ?>" alt="">
		</div>
	<?php endif; ?>
</section>
