<?php
/**
 * Displays the campaign content created by the campaign builder, starting in 1.8.0.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/builder/content.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Campaign
 * @since   1.8.0
 * @version 1.8.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Data related to the template (and therefore the defining layout) to be used.

$charitable_campaign = $view_args['campaign']; // Charitable_Campaign Instance of `Charitable_Campaign`.

if ( ! empty( $_GET['charitable_campaign_preview'] ) ) { //phpcs:ignore
	// get the transient that is storing the temp settings information, as this is what we will use to display the preview.
	$charitable_campaign_data = get_transient( 'charitable_campaign_preview_' . intval( $_GET['charitable_campaign_preview'] ) ); //phpcs:ignore
} else {
	$charitable_campaign_data = empty( $view_args['campaign_data'] ) && ! empty( $view_args['id'] ) ? get_post_meta( intval( $view_args['id'] ), 'campaign_settings_v2', true ) : $view_args['campaign_data'];
}

$charitable_template_data   = isset( $view_args['template'] ) && is_array( $view_args['template'] ) ? $view_args['template'] : array();
$charitable_template_id     = isset( $charitable_campaign_data['template_id'] ) && ! empty( $charitable_campaign_data['template_id'] ) ? sanitize_key( $charitable_campaign_data['template_id'] ) : charitable_campaign_builder_default_template();
$charitable_template_layout = isset( $charitable_template_data['layout'] ) ? $charitable_template_data['layout'] : array();

if ( is_admin() ) {

	// Check if Elementor is installed and activated.
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	// load the Campaign Builder Preview class.
	require_once charitable()->get_path( 'includes', true ) . 'admin/campaign-builder/class-campaign-builder-preview.php';

	$charitable_assets_dir = charitable()->get_path( 'assets', false );
	$charitable_min        = charitable_get_min_suffix();

	if ( charitable_is_debug( 'elementor' ) ) {
		echo 'Charitable Elementor template id is ' . esc_html( $charitable_template_id );
	}

	// Directly link and load the CSS files.
	$charitable_version        = charitable_is_break_cache() ? charitable()->get_version() . '.' . time() : charitable()->get_version();
	$charitable_css_theme_file = $charitable_assets_dir . 'css/campaign-builder/themes/frontend/' . $charitable_template_id . '.php';

	echo '<link rel="stylesheet" id="charitable-campaign-theme-' . esc_attr( $charitable_template_id ) . '-css" href="' . esc_url( $charitable_css_theme_file ) . '" media="all" />'; //phpcs:ignore
	echo '<link rel="stylesheet" id="charitable-campaign-theme-base-css" href="' . esc_url( $charitable_assets_dir . 'css/campaign-builder/themes/frontend/base' . $charitable_min . '.css' ) . '" media="all" />'; //phpcs:ignore

	// Add inline CSS to prevent clicks on '.charitable-campaign-wrapper'.
	echo '<style type="text/css">.charitable-campaign-wrapper { pointer-events: none; }</style>';
}

/* preview page check */
$charitable_content_preview = new Campaign_Builder_Preview();
$charitable_is_preview_page = $charitable_content_preview->is_preview_page();

/* tabs */
$charitable_enabled_tabs = isset( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) ? false : true;


/* The Setup */

/* Campaign Related */

$charitable_campaign = $view_args['campaign']; // Charitable_Campaign Instance of `Charitable_Campaign`.

if ( ! empty( $_GET['charitable_campaign_preview'] ) ) { //phpcs:ignore
	// get the transient that is storing the temp settings information, as this is what we will use to display the preview.
	$charitable_campaign_data = get_transient( 'charitable_campaign_preview_' . intval( $_GET['charitable_campaign_preview'] ) ); //phpcs:ignore
} else {
	$charitable_campaign_data = empty( $view_args['campaign_data'] ) && ! empty( $view_args['id'] ) ? get_post_meta( intval( $view_args['id'] ), 'campaign_settings_v2', true ) : $view_args['campaign_data'];
}

/* Template Related */

$charitable_template_id     = isset( $charitable_campaign_data['template_id'] ) && ! empty( $charitable_campaign_data['template_id'] ) ? sanitize_key( $charitable_campaign_data['template_id'] ) : charitable_campaign_builder_default_template();
$charitable_template_layout = isset( $charitable_template_data['layout'] ) ? $charitable_template_data['layout'] : array();

$charitable_template_parent_id = ( isset( $charitable_template_data['meta']['parent_theme'] ) && ! empty( $charitable_template_data['meta']['parent_theme'] ) ) ? esc_attr( $charitable_template_data['meta']['parent_theme'] ) : false;
$charitable_template_wrap_css  = false !== $charitable_template_parent_id ? 'template-' . $charitable_template_parent_id : '';
$charitable_template_wrap_css .= false !== $charitable_template_id ? ' template-' . $charitable_template_id : '';
$charitable_template_wrap_css .= ! empty( $view_args['campaign_data']['settings']['general']['form_css_class'] ) ? ' ' . esc_attr( $view_args['campaign_data']['settings']['general']['form_css_class'] ) : false;
$charitable_template_wrap_css .= 'draft' === get_post_status( $charitable_campaign_data['id'] ) ? ' is-charitable-preview' : false; // this to give a css class only when the campaign is previewed.

/* Layout Related */

$charitable_row_counter = 0;

$charitable_rows = (array) isset( $charitable_campaign_data['layout'] ) && ! empty( $charitable_campaign_data['layout']['rows'] ) ? $charitable_campaign_data['layout']['rows'] : array();

/* css: wrap and containers */

$charitable_css_classes                    = array();
$charitable_css_classes['container-wrap']  = 'charitable-campaign-wrap ' . trim( $charitable_template_wrap_css );
$charitable_css_classes['container-wrap'] .= ! empty( $view_args['id'] ) ? ' charitable-campaign-wrap-id-' . intval( $view_args['id'] ) : '';
$charitable_css_classes                    = apply_filters( 'charitable_builder_campaign_content_css_classes', $charitable_css_classes, $charitable_template_data, $charitable_campaign_data );
$charitable_css_classes_output             = implode( ' ', $charitable_css_classes );

// Get the post status - if this is a draft, we will display a notice to the admin or author, and not show this to the public.
$charitable_post_status = get_post_status( $charitable_campaign_data['id'] );
$charitable_post_author = get_post_field( 'post_author', $charitable_campaign_data['id'] );

// Only display the message if the viewer if the view isn't viewing a preview from the campaign builder (maybe they are viewing this via shortcode on the frontend, etc.).
if ( empty( $_GET['charitable_campaign_preview'] ) && ( false === $charitable_post_status || 'draft' === $charitable_post_status ) ) : //phpcs:ignore

	// if the user is the author of the post OR if they have permissions to view drafts, show the notice.
	if ( $charitable_post_author === get_current_user_id() || current_user_can( 'edit_posts' ) ) {
		?>
		<div class="charitable-notice charitable-notice-info">
			<p style="margin: 0;"><?php esc_html_e( 'This campaign is currently in draft mode. Only you can see it, and some functionality (like donation forms, donation buttons, etc.) might be disabled.', 'charitable' ); ?></p>
		</div>
		<?php
	} else {
		// show a generic message to the public.
		?>
		<div class="charitable-notice charitable-notice-info">
			<p style="margin: 0;"><?php esc_html_e( 'This campaign is currently in draft mode.', 'charitable' ); ?></p>
		</div>
		<?php
	}

endif;

// if the campaign/post is published OR if the user is the author of the post OR if they have permissions to edit posts, show the (slightly disabled) campaign.
if ( 'publish' === $charitable_post_status || ( ( false === $charitable_post_status || 'draft' === $charitable_post_status ) && ( $charitable_post_author === get_current_user_id() || current_user_can( 'edit_posts' ) ) ) ) :

	/**
	 * Add something before the campaign builder content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_builder_campaign_content_before', $charitable_campaign_data );

	/**
	 * Add something before the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_campaign_content_before', $charitable_campaign );

	?>

	<div class="<?php echo esc_attr( $charitable_css_classes_output ); ?>">

		<div class="charitable-campaign-container">

		<?php

		foreach ( $charitable_rows as $charitable_row_id => $charitable_row ) :

			$charitable_row_type = ! empty( $charitable_row['type'] ) ? esc_attr( $charitable_row['type'] ) : false;

			$charitable_row_css        = array(
				'charitable-campaign-row',
				'charitable-campaign-row-type-' . $charitable_row_type,
			);
			$charitable_additional_css = ! empty( $charitable_row['css_class'] ) ? esc_attr( $charitable_row['css_class'] ) : '';
			if ( '' !== $charitable_additional_css ) {
				$charitable_row_css[] = $charitable_additional_css;
			}

			$charitable_row_css_classes = apply_filters(
				'charitable_campaign_row_css',
				$charitable_row_css,
				$charitable_row,
				$charitable_template_id,
				$charitable_campaign_data
			);

			if ( 'row' === $charitable_row_type || 'header' === $charitable_row_type ) :
				?>

					<?php echo '<!-- row START -->'; ?>

					<div id="charitable-template-row-<?php echo intval( $charitable_row_id ); ?>" data-row-id="<?php echo intval( $charitable_row_id ); ?>" data-row-type="<?php echo esc_attr( $charitable_row_type ); ?>" class="<?php echo implode( ' ', array_map( 'esc_attr', $charitable_row_css_classes ) ); ?>">

					<?php

					if ( ! empty( $charitable_row['columns'] ) ) :

						$charitable_column_counter = 0;

						foreach ( $charitable_row['columns'] as $charitable_column_id => $charitable_column ) :

								$charitable_column_css_classes = apply_filters(
									'charitable_campaign_column_css',
									array(
										'charitable-campaign-column',
										'charitable-campaign-column-' . $charitable_column_id,
									),
									$charitable_column,
									$charitable_template_id,
									$charitable_campaign_data
								);


							echo '<!-- column START -->';

							echo '<div data-column-id="' . intval( $charitable_column_id ) . '" class="' . implode( ' ', array_map( 'esc_attr', $charitable_column_css_classes ) ) . '">';

							$charitable_section_counter = 0;

							foreach ( $charitable_column['sections'] as $charitable_section ) :

								echo '<!-- section START -->';

								echo '<div data-section-id="' . intval( $charitable_section_counter ) . '" data-section-type="' . esc_attr( $charitable_section['type'] ) . '" class="section charitable-field-section">';

								$charitable_section_type = ! empty( $charitable_section['type'] ) ? esc_attr( $charitable_section['type'] ) : 'fields';

								if ( 'tabs' === $charitable_section_type ) {

									// Did the user disable tabs entirely?
									$charitable_enable_tabs = ( ! empty( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && $charitable_campaign_data['layout']['advanced']['enable_tabs'] === 'disabled' ) ? false : true;

									if ( false !== $charitable_enable_tabs ) {


										// If there is campaign data, make a list of fields already in use which might determine if we show any tabs or not.
										$charitable_fields_in_tabs = array();

										if ( ! empty( $charitable_section['tabs'] ) ) {
											foreach ( $charitable_section['tabs'] as $charitable_section_tab ) {
												if ( ! empty( $charitable_section_tab['fields'] ) ) {
													$charitable_fields_in_tabs = array_merge( $charitable_fields_in_tabs, $charitable_section_tab['fields'] );
												}
											}
										}

										// If there are no fields in any tabs, don't show the tabs.
										if ( empty( $charitable_fields_in_tabs ) ) {
											continue;
										}

										$charitable_tab_tabs  = (array) isset( $charitable_section['tabs'] ) && ! empty( $charitable_section['tabs'] ) ? $charitable_section['tabs'] : array();
										$charitable_tab_order = isset( $charitable_campaign_data['tab_order'] ) && ! empty( $charitable_campaign_data['tab_order'] ) ? $charitable_campaign_data['tab_order'] : array();
										$charitable_tab_style = isset( $charitable_campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_style'] ) ? $charitable_campaign_data['layout']['advanced']['tab_style'] : 'medium';
										$charitable_tab_size  = isset( $charitable_campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_size'] ) ? $charitable_campaign_data['layout']['advanced']['tab_size'] : 'medium';
										$charitable_css_class = isset( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : false;

										// sort a multidimensional array matching the same order of keys as another multidimensional array.
										if ( ! empty( $charitable_tab_order ) ) {
											$charitable_temp_tab_tabs = array();
											foreach ( $charitable_tab_order as $charitable_order_id => $charitable_tab_id ) {
												if ( isset( $charitable_tab_tabs[ $charitable_tab_id ] ) ) {
													$charitable_temp_tab_tabs[ $charitable_tab_id ] = $charitable_tab_tabs[ $charitable_tab_id ];
												}
											}
											$charitable_tab_tabs = $charitable_temp_tab_tabs;
										}

										?>
									<article>
										<?php
										// Check if all tabs should be hidden (1.8.8.2)
										$charitable_all_tabs_hidden = true;
										$charitable_visible_tabs_count = 0;
										foreach ( $charitable_tab_tabs as $charitable_tab_id => $charitable_tab_fields ) {
											$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];
											if ( empty( $charitable_tab_info['visible_nav'] ) || $charitable_tab_info['visible_nav'] !== 'invisible' ) {
												$charitable_all_tabs_hidden = false;
												$charitable_visible_tabs_count++;
											}
										}

										// Only show navigation if there are multiple visible tabs (hide for single tab)
										if ( ! $charitable_all_tabs_hidden && $charitable_visible_tabs_count > 1 ) :
										?>
										<nav class="charitable-campaign-nav charitable-tab-style-<?php echo esc_attr( $charitable_tab_style ); ?> charitable-tab-size-<?php echo esc_attr( $charitable_tab_size ); ?>">
											<ul>
											<?php if ( ! empty( $charitable_tab_tabs ) ) : ?>

													<?php

														$charitable_counter = 1;

													foreach ( $charitable_tab_tabs as $charitable_tab_id => $charitable_tab_fields ) :

														$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

														$charitable_tab_type  = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
														$charitable_tab_title = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
														$charitable_tab_desc  = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
														$charitable_css_class = 'tab_type_' . $charitable_tab_type . ' ';
														$charitable_css_class = ( $charitable_counter === 1 ) ? 'active' : false;


														?><li id="tab_<?php echo intval( $charitable_tab_id ); ?>_title" data-tab-id="<?php echo intval( $charitable_tab_id ); ?>" data-tab-type="<?php echo esc_attr( $charitable_tab_type ); ?>" class="tab_title <?php echo esc_attr( $charitable_css_class ); ?>"><a href="#"><?php echo esc_html( $charitable_tab_title ); ?></a></li><?php //phpcs:ignore

															++$charitable_counter;

															endforeach;

													?>

												<?php endif; ?>

											</ul>
										</nav>
										<?php endif; // end if not all tabs hidden ?>
										<div class="tab-content">

											<ul class="charitable-tabs">

											<?php if ( ! empty( $charitable_tab_tabs ) ) : ?>

												<?php

														$charitable_counter = 1;

												foreach ( $charitable_tab_tabs as $charitable_tab_id => $charitable_tab_fields ) :

													$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

													$charitable_tab_type   = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
													$charitable_tab_title  = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
													$charitable_tab_desc   = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
													$charitable_css_class  = 'tab_type_' . $charitable_tab_type . ' ';
													$charitable_css_class .= ( $charitable_counter === 1 ) ? 'active' : false;


													?>
													<li id="tab_<?php echo intval( $charitable_tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $charitable_css_class ); ?>" data-tab-type="<?php echo esc_attR( $charitable_tab_type ); ?>" data-tab-id="<?php echo esc_attr( $charitable_tab_id ); ?>">

															<div class="charitable-tab-wrap">

													<?php

														$charitable_tab_tabs = (array) isset( $charitable_section['tabs'] ) && ! empty( $charitable_section['tabs'][ $charitable_tab_id ] ) ? $charitable_section['tabs'][ $charitable_tab_id ] : array();

													if ( ! empty( $charitable_tab_tabs['fields'] ) ) :

															$charitable_tab_fields_types = isset( $charitable_row['fields'] ) ? $charitable_row['fields'] : array();

														foreach ( $charitable_tab_tabs['fields'] as $charitable_tab_field_id => $charitable_tab_field_type_id ) :

															$charitable_tab_field_data = ! empty( $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] : false;
															$charitable_tab_field_type = ! empty( $charitable_row['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_row['fields'][ $charitable_tab_field_type_id ] : false;

															$charitable_field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $charitable_tab_field_type ) ) ) );

															if ( class_exists( $charitable_field_class ) ) :

																	$charitable_class = new $charitable_field_class();
																	$charitable_class->field_display( $charitable_tab_field_type, $charitable_tab_field_data, $charitable_campaign_data, $charitable_is_preview_page, $charitable_tab_field_id );

																endif;


																endforeach;

														endif;

													?>
															</div>

														</li>

													<?php

													++$charitable_counter;

														endforeach;
												?>

												<?php endif; ?>
											</ul>
										</div>
									</article>

									<?php } // end if tabs ?>

									<?php


								} elseif ( 'fields' === $charitable_section_type ) {

									$charitable_field_types_data = $charitable_row['fields'];

									foreach ( $charitable_section['fields'] as $charitable_key => $charitable_field_id ) :

											$charitable_field_data  = ! empty( $charitable_campaign_data['fields'][ $charitable_field_id ] ) ? $charitable_campaign_data['fields'][ $charitable_field_id ] : false;
											$charitable_field_type  = false !== $charitable_field_data && isset( $charitable_field_types_data[ $charitable_field_id ] ) ? sanitize_key( $charitable_field_types_data[ $charitable_field_id ] ) : false;
											$charitable_field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $charitable_field_type ) ) ) );

										if ( class_exists( $charitable_field_class ) ) :

											$charitable_class = new $charitable_field_class();
											$charitable_class->field_display( $charitable_field_type, $charitable_field_data, $charitable_campaign_data, $charitable_is_preview_page, $charitable_field_id, $charitable_template_data );

											endif;

										endforeach;

								}

								++$charitable_section_counter;

								echo '</div>';

								echo '<!-- section END -->';

										endforeach;

										++$charitable_column_counter;

							?>

								</div>

							<?php

								echo '<!-- column END -->';

						endforeach;

					endif;

					?>

					</div>

					<?php echo '<!-- row END -->'; ?>

				<?php

			elseif ( $charitable_enabled_tabs && 'tabs' === $charitable_row_type ) :

				// Did the user disable tabs entirely?
				$charitable_enable_tabs = ( ! empty( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && $charitable_campaign_data['layout']['advanced']['enable_tabs'] === 'disabled' ) ? false : true;

				if ( false === $charitable_enable_tabs ) {
					continue;
				}

				$charitable_row_tabs  = isset( $charitable_row['tabs'] ) ? $charitable_row['tabs'] : false;
				$charitable_tab_style = isset( $charitable_campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_style'] ) ? $charitable_campaign_data['layout']['advanced']['tab_style'] : 'medium';
				$charitable_tab_size  = isset( $charitable_campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $charitable_campaign_data['layout']['advanced']['tab_size'] ) ? $charitable_campaign_data['layout']['advanced']['tab_style'] : 'medium';
				$charitable_css_class = isset( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) && 'disabled' === trim( $charitable_campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : false;

				?>

				<article>
					<nav class="charitable-campaign-nav charitable-tab-style-<?php echo esc_attr( $charitable_tab_style ); ?> charitable-tab-size-<?php echo esc_attr( $charitable_tab_size ); ?>">
						<ul>
							<?php if ( $charitable_row_tabs ) : ?>
								<?php

									$charitable_counter = 1;

								foreach ( $charitable_row_tabs as $charitable_tab_id => $charitable_tab_fields ) :

									$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

									$charitable_tab_type      = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
									$charitable_tab_title     = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
									$charitable_tab_desc      = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
										$charitable_css_class = 'tab_type_' . $charitable_tab_type . ' ';
										$charitable_css_class = ( $charitable_counter === 1 ) ? 'active' : false;


									?>
										<li id="tab_<?php echo intval( $charitable_tab_id ); ?>_title" data-tab-id="<?php echo intval( $charitable_tab_id ); ?>" data-tab-type="<?php echo esc_attr( $charitable_tab_type ); ?>" class="tab_title <?php echo esc_attr( $charitable_css_class ); ?>"><a href="#"><?php echo esc_html( $charitable_tab_title ); ?></a></li>
										<?php

										++$charitable_counter;

										endforeach;
								?>

								<?php endif; ?>
						</ul>
					</nav>
					<div class="tab-content">
							<ul>
							<?php

							if ( $charitable_row_tabs ) :

								$charitable_counter = 1;

								foreach ( $charitable_row_tabs as $charitable_tab_id => $charitable_tab_fields ) :

									$charitable_tab_info = $charitable_campaign_data['tabs'][ $charitable_tab_id ];

									$charitable_tab_type   = isset( $charitable_tab_info['type'] ) && ! empty( $charitable_tab_info['type'] ) ? ( $charitable_tab_info['type'] ) : false;
									$charitable_tab_title  = isset( $charitable_tab_info['title'] ) && ! empty( $charitable_tab_info['title'] ) ? ( $charitable_tab_info['title'] ) : false;
									$charitable_tab_desc   = isset( $charitable_tab_info['desc'] ) && ! empty( $charitable_tab_info['desc'] ) ? ( $charitable_tab_info['desc'] ) : false;
									$charitable_css_class  = 'tab_type_' . $charitable_tab_type . ' ';
									$charitable_css_class .= ( $charitable_counter === 1 ) ? 'active' : false;


									?>
										<li id="tab_<?php echo intval( $charitable_tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $charitable_css_class ); ?>" data-tab-type="<?php echo esc_attr( $charitable_tab_type ); ?>" data-tab-id="<?php echo intval( $charitable_tab_id ); ?>">

											<div class="charitable-tab-wrap">

									<?php

										$charitable_tab_field_info = isset( $charitable_row['fields'] ) ? $charitable_row['fields'] : false;

									if ( false !== $charitable_tab_field_info ) :

											$charitable_tab_fields_types = isset( $charitable_row['fields'] ) ? $charitable_row['fields'] : array();

										foreach ( $charitable_tab_fields as $charitable_tab_field_id => $charitable_tab_field_type_id ) :

											$charitable_tab_field_data = ! empty( $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_campaign_data['fields'][ $charitable_tab_field_type_id ] : false;
											$charitable_tab_field_type = ! empty( $charitable_row['fields'][ $charitable_tab_field_type_id ] ) ? $charitable_row['fields'][ $charitable_tab_field_type_id ] : false;

											$charitable_field_class = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $charitable_tab_field_type ) ) ) );

											if ( class_exists( $charitable_field_class ) ) :

													$charitable_class = new $charitable_field_class();
													$charitable_class->field_display( $charitable_tab_field_type, $charitable_tab_field_data, $charitable_campaign_data, $charitable_is_preview_page, $charitable_tab_field_id );

												endif;

											endforeach;

										endif;

									?>
											</div>

										</li>

									<?php

									++$charitable_counter;

									endforeach;
								?>

								<?php endif; ?>
							</ul>
						</div>
				</article>

					<?php

				endif;

			endforeach;

		?>

			</div>
		</div>

	<?php

	/**
	 * Add something after the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_builder_campaign_content_after', $charitable_campaign_data );

	/**
	 * Add something before the campaign content.
	 *
	 * @since 1.8.0
	 *
	 * @param $campaign Charitable_Campaign Instance of `Charitable_Campaign`.
	 */
	do_action( 'charitable_campaign_content_after', $charitable_campaign );

endif;
