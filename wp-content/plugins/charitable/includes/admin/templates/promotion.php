<?php
/**
 * Footer promotion template.
 *
 * @since 1.8.7.2
 * @version 1.8.8.6
 * @package Charitable/Admin/Templates
 *
 * @var string $title
 * @var array  $links
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_links_count = count( $links );

?>

<div class="charitable-footer-promotion">
	<p>
	<?php
	echo wp_kses(
		$title,
		array(
			'span' => array(
				'style' => array(),
				'color' => array(),
			),
		)
	);
	?>
	</p>
	<ul class="charitable-footer-promotion-links">
		<?php foreach ( $links as $charitable_key => $charitable_item ) : ?>
			<li>
				<?php
				$charitable_attributes = array(
					'href'   => esc_url( $charitable_item['url'] ),
					'target' => isset( $charitable_item['target'] ) ? $charitable_item['target'] : false,
					'rel'    => isset( $charitable_item['target'] ) ? 'noopener noreferrer' : false,
				);

				printf(
					'<a %1s>%2$s</a>%3$s',
					charitable_html_attributes( '', array(), array(), $charitable_attributes, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $charitable_item['text'] ),
					$charitable_links_count === $charitable_key + 1 ? '' : '<span>/</span>'
				);
				?>
			</li>
		<?php endforeach; ?>
	</ul>
	<ul class="charitable-footer-promotion-social">
		<li>
			<a href="https://www.facebook.com/wpcharitable/" target="_blank" rel="noopener noreferrer">
				<svg width="16" height="16" aria-hidden="true">
					<path fill="#A7AAAD" d="M16 8.05A8.02 8.02 0 0 0 8 0C3.58 0 0 3.6 0 8.05A8 8 0 0 0 6.74 16v-5.61H4.71V8.05h2.03V6.3c0-2.02 1.2-3.15 3-3.15.9 0 1.8.16 1.8.16v1.98h-1c-1 0-1.31.62-1.31 1.27v1.49h2.22l-.35 2.34H9.23V16A8.02 8.02 0 0 0 16 8.05Z"/>
				</svg>
				<span class="screen-reader-text"><?php echo esc_html( 'Facebook' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://www.instagram.com/wpcharitable/" target="_blank" rel="noopener noreferrer">
				<svg width="16" height="16" aria-hidden="true">
					<path fill="#A7AAAD" d="M8.016 4.39c-2 0-3.594 1.626-3.594 3.594 0 2 1.594 3.594 3.594 3.594a3.594 3.594 0 0 0 3.593-3.594c0-1.968-1.625-3.593-3.593-3.593Zm0 5.938a2.34 2.34 0 0 1-2.344-2.344c0-1.28 1.031-2.312 2.344-2.312a2.307 2.307 0 0 1 2.312 2.312c0 1.313-1.031 2.344-2.312 2.344Zm4.562-6.062a.84.84 0 0 0-.844-.844.84.84 0 0 0-.843.844.84.84 0 0 0 .843.843.84.84 0 0 0 .844-.843Zm2.375.843c-.062-1.125-.312-2.125-1.125-2.937-.812-.813-1.812-1.063-2.937-1.125-1.157-.063-4.625-.063-5.782 0-1.125.062-2.093.312-2.937 1.125-.813.812-1.063 1.812-1.125 2.937-.063 1.157-.063 4.625 0 5.782.062 1.125.312 2.093 1.125 2.937.844.813 1.812 1.063 2.937 1.125 1.157.063 4.625.063 5.782 0 1.125-.062 2.125-.312 2.937-1.125.813-.844 1.063-1.812 1.125-2.937.063-1.157.063-4.625 0-5.782Zm-1.5 7c-.219.625-.719 1.094-1.312 1.344-.938.375-3.125.281-4.125.281-1.032 0-3.22.094-4.125-.28a2.37 2.37 0 0 1-1.344-1.345c-.375-.906-.281-3.093-.281-4.125 0-1-.094-3.187.28-4.125a2.41 2.41 0 0 1 1.345-1.312c.906-.375 3.093-.281 4.125-.281 1 0 3.187-.094 4.125.28.593.22 1.062.72 1.312 1.313.375.938.281 3.125.281 4.125 0 1.032.094 3.22-.28 4.125Z"/>
				</svg>
				<span class="screen-reader-text"><?php echo esc_html( 'Instagram' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://www.linkedin.com/company/wpcharitable/" target="_blank" rel="noopener noreferrer">
				<svg width="16" height="16" aria-hidden="true">
					<path fill="#A7AAAD" d="M14 1H1.97C1.44 1 1 1.47 1 2.03V14c0 .56.44 1 .97 1H14a1 1 0 0 0 1-1V2.03C15 1.47 14.53 1 14 1ZM5.22 13H3.16V6.34h2.06V13ZM4.19 5.4a1.2 1.2 0 0 1-1.22-1.18C2.97 3.56 3.5 3 4.19 3c.65 0 1.18.56 1.18 1.22 0 .66-.53 1.19-1.18 1.19ZM13 13h-2.1V9.75C10.9 9 10.9 8 9.85 8c-1.1 0-1.25.84-1.25 1.72V13H6.53V6.34H8.5v.91h.03a2.2 2.2 0 0 1 1.97-1.1c2.1 0 2.5 1.41 2.5 3.2V13Z"/>
				</svg>
				<span class="screen-reader-text"><?php echo esc_html( 'LinkedIn' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://x.com/wpcharitable" target="_blank" rel="noopener noreferrer">
				<svg width="16" height="16" aria-hidden="true" viewBox="0 0 512 512">
					<path fill="#A7AAAD" d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/>
				</svg>
				<span class="screen-reader-text"><?php echo esc_html( 'X' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://www.youtube.com/@wpCharitable" target="_blank" rel="noopener noreferrer">
				<svg width="17" height="16" aria-hidden="true">
					<path fill="#A7AAAD" d="M16.63 3.9a2.12 2.12 0 0 0-1.5-1.52C13.8 2 8.53 2 8.53 2s-5.32 0-6.66.38c-.71.18-1.3.78-1.49 1.53C0 5.2 0 8.03 0 8.03s0 2.78.37 4.13c.19.75.78 1.3 1.5 1.5C3.2 14 8.51 14 8.51 14s5.28 0 6.62-.34c.71-.2 1.3-.75 1.49-1.5.37-1.35.37-4.13.37-4.13s0-2.81-.37-4.12Zm-9.85 6.66V5.5l4.4 2.53-4.4 2.53Z"/>
				</svg>
				<span class="screen-reader-text"><?php echo esc_html( 'YouTube' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://www.wpcharitable.com/tiktok" target="_blank" rel="noopener noreferrer">
			<svg width="14" height="14" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 449.45 515.38"><path fill-rule="nonzero" fill="#A7AAAD" d="M382.31 103.3c-27.76-18.1-47.79-47.07-54.04-80.82-1.35-7.29-2.1-14.8-2.1-22.48h-88.6l-.15 355.09c-1.48 39.77-34.21 71.68-74.33 71.68-12.47 0-24.21-3.11-34.55-8.56-23.71-12.47-39.94-37.32-39.94-65.91 0-41.07 33.42-74.49 74.48-74.49 7.67 0 15.02 1.27 21.97 3.44V190.8c-7.2-.99-14.51-1.59-21.97-1.59C73.16 189.21 0 262.36 0 352.3c0 55.17 27.56 104 69.63 133.52 26.48 18.61 58.71 29.56 93.46 29.56 89.93 0 163.08-73.16 163.08-163.08V172.23c34.75 24.94 77.33 39.64 123.28 39.64v-88.61c-24.75 0-47.8-7.35-67.14-19.96z"/></svg>
				<span class="screen-reader-text"><?php echo esc_html( 'TikTok' ); ?></span>
			</a>
		</li>
	</ul>
</div>
