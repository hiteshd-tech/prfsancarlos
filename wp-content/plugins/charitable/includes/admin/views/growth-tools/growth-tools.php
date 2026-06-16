<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the main tools page wrapper.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Tools
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.1.6
 * @version   1.8.1.6
 * @version   1.8.8.6
 */

$charitable_active_tab      = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : '';  // phpcs:ignore

$charitable_sections      = [
	'featured'   => esc_html__( 'Featured', 'charitable' ),
	'traffic'    => esc_html__( 'Traffic', 'charitable' ),
	'engagement' => esc_html__( 'Engagement', 'charitable' ),
	'revenue'    => esc_html__( 'Revenue', 'charitable' ),
	'guides'     => esc_html__( 'Guides & Resources', 'charitable' ),
];
$charitable_section_tools = array();

$charitable_growth_tools = Charitable_Guide_Tools::get_instance();

$charitable_tools       = $charitable_growth_tools->get_growth_tools();
$charitable_show_button = true;

if ( is_array( $charitable_tools ) && ! empty( $charitable_tools ) ) :

	foreach ( $charitable_tools as $charitable_slug => $charitable_tool_info ) :

		if ( isset( $charitable_tool_info['gt_section'] ) ) {
			$charitable_section_tools[ $charitable_tool_info['gt_section'] ][ $charitable_slug ] = $charitable_tool_info;
		}

endforeach;

endif;

ob_start();
?>

<div id="charitable-growth-tools" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $charitable_sections as $charitable_section_slug => $charitable_section_title ) :

			if ( ! isset( $charitable_section_tools[ $charitable_section_slug ] ) || empty( $charitable_section_tools[ $charitable_section_slug ] ) ) {
				continue;
			}

			?>
		<a href="#<?php echo esc_attr( sanitize_title( $charitable_section_slug ) ); ?>" class="nav-tab <?php echo $charitable_active_tab === $charitable_section_slug ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $charitable_section_title ); ?></a>
		<?php endforeach; ?>

	</h2>

	<main id="charitable-growth-tools" class="charitable-growth-tools">

		<div class="charitable-growth-container">

			<?php

			if ( is_array( $charitable_sections ) && ! empty( $charitable_sections ) ) :

				foreach ( $charitable_sections as $charitable_section_slug => $charitable_section_title ) :

					if ( ! isset( $charitable_section_tools[ $charitable_section_slug ] ) || empty( $charitable_section_tools[ $charitable_section_slug ] ) ) {
						continue;
					}

					?>

					<section id="charitable-section-<?php echo esc_attr( $charitable_section_slug ); ?>" class="charitable-growth-block">
						<a id="wpchr-<?php echo esc_attr( $charitable_section_slug ); ?>"></a>
						<h2 class="charitable-growth-block-title"><?php echo esc_html( $charitable_section_title ); ?></h2>
						<div class="charitable-growth-item">

						<?php

						foreach ( $charitable_section_tools[ $charitable_section_slug ] as $charitable_tool_slug => $charitable_tool_info ) :

							if ( empty( $charitable_tool_info ) ) {
								continue;
							}

							$charitable_content_class = '';

							if ( isset( $charitable_tool_info['coming_soon'] ) && true === $charitable_tool_info['coming_soon'] ) {
								$charitable_content_class = 'charitable-growth-coming-soon';
								$charitable_show_button   = false;
							}

							?>

								<div class="charitable-growth-content <?php echo esc_attr( $charitable_content_class ); ?>" id="charitable-growth-content-<?php echo esc_attr( $charitable_tool_info['id'] ); ?>">

									<a id="wpchr-<?php echo esc_attr( $charitable_tool_info['id'] ); ?>"></a>

									<div class="charitable-growth-content-icon_container">
										<div class="charitable-growth-content-icon icon-<?php echo esc_attr( $charitable_tool_info['id'] ); ?>"></div>
									</div>

									<div class="charitable-growth-content-desc_container">
										<h3 class="charitable-growth-desc-title"><?php echo esc_html( $charitable_tool_info['title'] ); ?></h3>
										<?php if ( ! empty( $charitable_tool_info['excerpt'] ) ) : ?>
										<p class="charitable-growth-desc-excerpt">
											<?php
											echo wp_kses(
												$charitable_tool_info['excerpt'],
												[
													'a'    => [
														'href'   => [],
														'target' => [],
														'rel'    => [],
													],
													'span' => [
														'class' => [],
													],
												]
											);
											?>
										</p>
										<?php endif; ?>
										<?php if ( ! empty( $charitable_tool_info['why'] ) ) : ?>
												<p class="charitable-growth-desc-why"><strong><?php echo esc_html__( 'TIP:', 'charitable' ); ?></strong> <?php echo $charitable_tool_info['why']; // phpcs:ignore ?></p>
										<?php endif; ?>
									</div>
									<div class="charitable-growth-content-button_container">
										<div>
											<?php if ( $charitable_show_button ) : ?>
												<?php

													$charitable_plugins_third_party = new Charitable_Admin_Plugins_Third_Party();
													$charitable_plugin_button_html             = $charitable_plugins_third_party->get_plugin_button_html( $charitable_tool_slug, false, '' );
                                                    echo $charitable_plugin_button_html; // phpcs:ignore

												?>
											<?php endif; ?>
										</div>
									</div>
								</div>

								<?php

							endforeach;

						?>

						</div>
					</section>

					<?php
				endforeach;

			endif;

			?>

		</div>

	</main>



</div>
<?php
echo ob_get_clean(); // phpcs:ignore
