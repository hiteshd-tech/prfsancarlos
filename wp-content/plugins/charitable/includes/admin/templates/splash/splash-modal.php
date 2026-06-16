<?php
/**
 * Charitable Admin Splash modal template.
 *
 * @package Charitable/Admin/Templates
 * @since 1.8.6
 * @version 1.8.8.6
 *
 * @var array $header Header data.
 * @var array $footer Footer data.
 * @var array $blocks Blocks data.
 * @var array $license License type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/html" id="tmpl-charitable-splash-modal-content">
	<div id="charitable-splash-modal">
		<?php
		echo wp_kses_post(
			charitable_render(
				'admin/templates/splash/splash-header',
				[
					'header' => $data['header'],
				],
				true
			)
		);
		?>
		<main>
			<?php
			if ( ! empty( $data['sections'] ) ) {

				foreach ( $data['sections'] as $charitable_section ) {
					echo wp_kses_post(
						charitable_render(
							'admin/templates/splash/splash-section',
							[
								'section' => $charitable_section,
							],
							true
						)
					);
				}
			}
			?>
		</main>
		<?php
		$charitable_license = isset( $license ) ? $license : 'lite'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $license may be passed in from external context.
		echo wp_kses_post(
			charitable_render(
				'admin/templates/splash/splash-footer',
				[
					'footer' => $data['footer'],
				],
				true
			)
		);
		?>
	</div>
</script>
