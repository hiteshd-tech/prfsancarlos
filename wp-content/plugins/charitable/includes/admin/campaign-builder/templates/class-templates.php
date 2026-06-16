<?php
/**
 * Main class for template management for campaign builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Builder_Templates' ) ) :

	/**
	 * Sets up the WordPress customizer.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Campaign_Builder_Templates {

		/**
		 * One is the loneliest number that you'll ever do.
		 *
		 * @since 1.8.0
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Field ID.
		 *
		 * @since 1.8.0
		 *
		 * @var integer
		 */
		public $field_id;

		/**
		 * Prepared templates list.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		private $prepared_templates = array();

		/**
		 * Template categories data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		private $categories;

		/**
		 * Template addon labels data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		private $addon_labels;

		/**
		 * Is addon templates available?
		 *
		 * @since 1.8.0
		 *
		 * @var bool
		 */
		private $is_addon_templates_available = false;

		/**
		 * Is custom templates available?
		 *
		 * @since 1.8.0
		 *
		 * @var bool
		 */
		private $is_custom_templates_available = false;

		/**
		 * Storing template data (for now).
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		private $templates_data = array();

		/**
		 * Main Instance.
		 *
		 * @since 1.8.0
		 *
		 * @return Charitable_Builder
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Charitable_Campaign_Builder_Templates ) ) {

				self::$instance = new Charitable_Campaign_Builder();

				add_action( 'admin_init', array( self::$instance, 'init' ), 10 );
			}

			return self::$instance;
		}

		/**
		 * Create object instance.
		 *
		 * @since 1.8.0
		 */
		public function __construct() {

			if ( ! is_admin() ) {
				return;
			}

			$this->field_id = 0;

			$this->templates_data = $this->get_source_template_data();

			$this->init();
		}

		/**
		 * Init things.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			$this->hooks();
		}

		/**
		 * Hook things.
		 *
		 * @since 1.8.0
		 */
		public function hooks() {

			add_action( 'admin_print_scripts', array( $this, 'upgrade_banner_template' ) );
		}

		/**
		 * Returns the starting HTML element for a given template type.
		 *
		 * @since 1.8.0
		 *
		 * @param string $type The type of template element to retrieve.
		 * @param int    $counter The counter for the template element.
		 * @param string $additional_css Additional CSS classes to add to the template element.
		 * @param array  $campaign_data An array of campaign data to pass to the template element.
		 * @return string The starting HTML element for the given template type.
		 */
		public function get_template_element_start( $type = 'row', $counter = 1, $additional_css = '', $campaign_data = array() ) {

			ob_start();

			switch ( $type ) {
				case 'header':
					?>
					<!-- charitable header start -->
					<header id="charitable-preview-header-<?php echo intval( $counter ); ?>" class="charitable-preview-header <?php echo esc_attr( $additional_css ); ?>">
						<div class="row" data-row-id="<?php echo intval( $counter ); ?>" data-row-type="<?php echo esc_attr( $type ); ?>" data-row-css="<?php echo esc_attr( $additional_css ); ?>">
					<?php
					break;

				case 'tabs':
					$enable_tabs_css = isset( $campaign_data['layout']['advanced']['enable_tabs'] ) && '' !== trim( $campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : '';
					?>
					<!-- charitable tabs start -->
					<article id="charitable-preview-tab-container" class="charitable-preview-tab-container <?php echo $enable_tabs_css; ?>"> <?php // phpcs:ignore ?>
						<a href="#" class="charitable-hover-button charitable-field-edit" title="<?php echo esc_html__( 'Edit Area', 'charitable' ); ?>"><i class="fa fa-pencil"></i></a>
					<?php
					break;

				default:
					// likely a row.
					?>
					<!-- charitable row/default start -->
					<div id="charitable-preview-row-<?php echo intval( $counter ); ?>" class="charitable-preview-row <?php echo esc_attr( $additional_css ); ?>">
						<div class="row" data-row-id="<?php echo intval( $counter ); ?>" data-row-type="<?php echo esc_attr( $type ); ?>" data-row-css="<?php echo esc_attr( $additional_css ); ?>">
					<?php
					break;
			}

			return ob_get_clean();
		}

		/**
		 * Create closing HTML tags based on the type.
		 *
		 * @since 1.8.0
		 * @param string $type The type of template element to retrieve.
		 * @param int    $counter The counter for the template element.
		 *
		 * @return string The closing HTML tags for the given template type.
		 */
		public function get_template_element_end( $type = 'row', $counter = 1 ) { // phpcs:ignore

			ob_start();

			switch ( $type ) {
				case 'header':
					?>
						</div>
					</header>
					<!-- charitable header end -->
					<?php
					break;

				case 'tabs':
					?>
					</article>
					<!-- charitable tabs end -->
					<?php
					break;

				default:
					// likely a row.
					?>
						</div>
					</div>
					<!-- charitable row/default end -->
					<?php
					break;
			}

			return ob_get_clean();
		}

		/**
		 * Create fields for preview area based on an array, usually for a column of the layout..
		 *
		 * @since 1.8.0
		 *
		 * @param array $fields         Fields.
		 * @param array $theme          Theme data.
		 * @param array $campaign_data  Campaign data.
		 * @param int   $last_field_id  Last field ID.
		 *
		 * @return string
		 */
		public function render_fields( $fields, $theme, $campaign_data, $last_field_id = 0 ) {

			if ( empty( $fields ) ) {
				return;
			}

			$field_id = $last_field_id;

			foreach ( $fields as $key => $field_type_data ) :

				$field_type = is_array( $field_type_data ) && ! empty( $field_type_data['type'] ) ? esc_html( $field_type_data['type'] ) : esc_html( $key );

				$type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

				if ( class_exists( $type ) ) :

					$class = new $type();

					$field_settings = is_array( $field_type_data ) ? $field_type_data : array();

					// define a default photo for the photo type block if it's passed from the theme JSON data.
					if ( $field_type == 'photo' && ! empty( $field_settings['default'] ) && ! empty( $theme['meta']['slug'] ) ) {

						$campaign_template_slug = esc_attr( $theme['meta']['slug'] );

						// if the "default" parameter for the photo is passed then we attempt to pull that photo.
						$default_filename = ! empty( $field_type_data['default'] ) ? esc_html( $field_type_data['default'] ) : 'photo.jpg';
						$theme_thumbnail  = ( false !== $default_filename ) && $campaign_template_slug ? charitable()->get_path( 'assets', true ) . 'images/campaign-builder/templates/' . $campaign_template_slug . '/' . $default_filename : false;

						// see if the thumbnail or default photo exists.
						if ( false !== $theme_thumbnail && file_exists( $theme_thumbnail ) ) {
							$field_settings['default'] = charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/' . $campaign_template_slug . '/' . $default_filename;
						}
					}

					$field_settings['id'] = $field_id;

					$charitable_field_css_classes = array(
						'charitable-field',
						'charitable-field-' . $field_type,
						$class->can_be_edited ? 'charitable-can-edit' : 'charitable-no-edit',
						$class->can_be_duplicated ? 'charitable-can-duplicate' : 'charitable-no-duplicate',
						$class->can_be_deleted ? 'charitable-can-delete' : 'charitable-no-delete',
					);

					echo '<div class="' . esc_attr( implode( ' ', $charitable_field_css_classes ) ) . '" id="charitable-field-' . intval( $field_id ) . '" data-field-id="' . intval( $field_id ) . '" data-field-type="' . esc_attr( $field_type ) . '" data-field-max="' . intval( $class->max_allowed ) . '" style="">'; // phpcs:ignore
					if ( $class->can_be_edited ) :
						echo '<a href="#" class="charitable-field-edit" data-type="' . esc_attr( $class->edit_type ) . '" data-section="' . esc_attr( $class->edit_section ) . '" data-edit-field-id="' . esc_attr( $class->edit_field_id ) . '" title="' . esc_attr( $class->edit_label ) . '"><i class="fa fa-pencil"></i></a>';
					endif;
					if ( $class->can_be_duplicated ) :
						echo '<a href="#" class="charitable-field-duplicate" title="Duplicate Field"><i class="fa fa-files-o" aria-hidden="true"></i></a>';
					endif;
					if ( $class->can_be_deleted ) :
						echo '<a href="#" class="charitable-field-delete" title="Delete Field"><i class="fa fa-trash-o"></i></a>';
					endif;

					echo $class->field_preview( $field_settings, $campaign_data, $field_id, $theme ); // phpcs:ignore
					echo '</div>';

				else :

					$this->render_missing_addon_field_preview( $field_type, $field_id, $field_type_data, $campaign_data );

				endif;

				++$field_id;

			endforeach;

			return $field_id;
		}

		/**
		 * Sorts an array by the order of another array.
		 *
		 * @param array $tabs      The array to be sorted.
		 * @param array $tab_order The array that defines the order.
		 *
		 * @return array The sorted array.
		 */
		public function sort_array_by_array( $tabs, $tab_order ) {

			$ordered = array();

			foreach ( $tab_order as $key ) {
				if ( array_key_exists( $key, $tabs ) ) {
					$ordered[ $key ] = $tabs[ $key ];
					unset( $tabs[ $key ] );
				}
			}

			return $ordered + $tabs;
		}

		/**
		 * Create tabs based on the template data.
		 *
		 * @since 1.8.0
		 *
		 * @param string $tabs  Tab info.
		 * @param string $theme The current theme.
		 * @param array  $campaign_data Campaign data..
		 *
		 * @return string
		 */
		public function get_template_tab_nav( $tabs = false, $theme = false, $campaign_data = false ) {

			if ( ! is_admin() ) {
				return;
			}

			if ( false === $tabs || false === $theme ) {
				return;
			}

			ob_start();

			$counter   = 0;
			$tab_id    = 0;
			$advanced  = ( ! empty( $campaign_data['layout']['advanced'] ) ) ? $campaign_data['layout']['advanced'] : $theme['advanced'];
			$tab_style = isset( $advanced['tab_style'] ) && '' !== trim( $advanced['tab_style'] ) ? $advanced['tab_style'] : 'boxed';
			$tab_size  = isset( $advanced['tab_size'] ) && '' !== trim( $advanced['tab_size'] ) ? $advanced['tab_size'] : 'medium';
			$tab_order = ! empty( $campaign_data['tab_order'] ) ? $campaign_data['tab_order'] : false;

			// sort the multi-dimenstional array of $tabs based on the values of $tab_order array.
			if ( is_array( $tabs ) && ! empty( $tabs ) && ! empty( $tab_order ) ) {
				$tabs = $this->sort_array_by_array( $tabs, $tab_order );
			}

			?>

			<nav class="charitable-campaign-preview-nav tab-style-<?php echo esc_attr( $tab_style ); ?> tab-size-<?php echo esc_attr( $tab_size ); ?>">

				<ul class="charitable-campaign-preview-nav-list">

				<?php if ( is_array( $tabs ) && ! empty( $tabs ) ) : ?>
					<?php

						$counter = 1;

						/*
							The initial structure of this array looks something like:

							'title' => 'My Campaign',
							'type' => 'html',
							'slug' => 'default',
							'fields' => array (
								'campaign-description',
							)

						*/

					foreach ( $tabs as $tab_id => $tab_info ) :

						$title         = ! empty( $tab_info['title'] ) ? esc_html( $tab_info['title'] ) : false;
						$title         = false === $title && ! empty( $campaign_data['tabs'][ $tab_id ]['title'] ) ? esc_html( $campaign_data['tabs'][ $tab_id ]['title'] ) : $title;
						$type          = ( isset( $tab_info['type'] ) && '' !== trim( $tab_info['type'] ) ) ? $tab_info['type'] : 'html';
						$class         = 'tab_type_' . $type . ' ';
						$class         = ( $counter === 1 ) ? 'active' : false;
						$show_hide_css = ! empty( $campaign_data['tabs'][ $tab_id ]['visible_nav'] ) && $campaign_data['tabs'][ $tab_id ]['visible_nav'] === 'invisible' ? 'charitable-tab-hide' : false;

						?>
						<li id="tab_<?php echo esc_attr( $tab_id ); ?>_title" data-tab-id="<?php echo esc_attr( $tab_id ); ?>" data-tab-type="<?php echo esc_attr( $type ); ?>" class="tab_title <?php echo esc_attr( $class ); ?> <?php echo esc_attr( $show_hide_css ); ?>"><a href="#"><?php echo esc_html( $title ); ?></a></li>
						<?php

						++$counter;

						endforeach;

					endif;
				?>

				</ul>

			</nav>

			<?php

			return ob_get_clean();
		}

		/**
		 * Create tab content based on the template data.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $tabs          Tab info.
		 * @param string $theme         The current theme.
		 * @param array  $campaign_data Campaign data.
		 * @param array  $row_fields    Row fields.
		 * @param int    $last_field_id Last field ID.
		 *
		 * @return string
		 */
		public function get_template_tab_content( $tabs = false, $theme = false, $campaign_data = array(), $row_fields = false, $last_field_id = 0 ) {

			if ( ! is_admin() ) {
				return;
			}

			if ( false === $tabs || false === $theme || ! is_array( $tabs ) ) {
				return;
			}

			ob_start();

			$counter      = 0;
			$tab_id       = 0;
			$field_id     = $last_field_id;
			$no_tab_class = ( empty( $tabs ) || false === $tabs ) ? 'empty-tabs' : false;

			?>

			<div class="tab-content <?php echo esc_attr( $no_tab_class ); ?>">
					<ul>
					<?php if ( $tabs ) : ?>
							<?php

								$counter = 1;

							foreach ( $tabs as $tab_id => $tab_info ) :

								$type   = ( isset( $tab_info['type'] ) && '' !== trim( $tab_info['type'] ) ) ? $tab_info['type'] : 'html';
								$class  = 'tab_type_' . $type . ' ';
								$class .= ( $counter === 1 ) ? 'active' : false;
								$fields = ( ! empty( $tab_info['fields'] ) ) ? $tab_info['fields'] : false;
								$class .= ( false === $fields ) ? ' empty-tab' : false;

								?>

								<li id="tab_<?php echo intval( $tab_id ); ?>_content" class="tab_content_item <?php echo esc_attr( $class ); ?>" data-tab-type="<?php echo esc_attr( $type ); ?>" data-tab-id="<?php echo esc_attr( $tab_id ); ?>">

									<div class="charitable-tab-wrap ui-sortable">

								<?php echo $this->tab_empty_notice(); // phpcs:ignore ?>

								<?php echo charitable_builder_tab_content_preview_by_type( $type ); // phpcs:ignore ?>

								<?php

								if ( ! empty( $fields ) ) :

									foreach ( $fields as $key => $field_info_or_id ) :

										// field_info could be just field_id OR an array of information from template JSON (type, default, headline, etc.).

										if ( is_array( $field_info_or_id ) ) {
											$field_info      = $field_info_or_id;
											$field_type      = ! empty( $field_info['type'] ) ? esc_attr( $field_info['type'] ) : false;
											$field_type_data = $field_info_or_id;
										} else {
											$field_id        = intval( $field_info_or_id );
											$field_type      = is_array( $row_fields ) && ! empty( $row_fields[ $field_id ] ) ? esc_attr( $row_fields[ $field_id ] ) : false;
											$field_type_data = ! empty( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;
										}

										if ( false !== $field_type ) :

											$class_type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

											if ( class_exists( $class_type ) ) :

												$class          = new $class_type();
												$field_settings = is_array( $field_type_data ) ? $field_type_data : array();

												// define a default photo for the photo type block if it's passed from the theme JSON data.
												if ( $field_type === 'photo' && ! empty( $field_settings['default'] ) && ! empty( $theme['meta']['slug'] ) ) {

													$campaign_template_slug = esc_attr( $theme['meta']['slug'] );

													// if the "default" parameter for the photo is passed then we attempt to pull that photo.
													$default_filename = ! empty( $field_type_data['default'] ) ? esc_html( $field_type_data['default'] ) : 'photo.jpg';
													$theme_thumbnail  = ( false !== $default_filename ) && $campaign_template_slug ? charitable()->get_path( 'assets', true ) . 'images/campaign-builder/templates/' . $campaign_template_slug . '/' . $default_filename : false;

													// see if the thumbnail or default photo exists.
													if ( false !== $theme_thumbnail && file_exists( $theme_thumbnail ) ) {
														$field_settings['default'] = charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/' . $campaign_template_slug . '/' . $default_filename;
													}
												}
												$field_settings['id'] = $field_id;

												$charitable_field_css_classes = array(
													'charitable-field',
													'charitable-field-' . $field_type,
													$class->can_be_edited ? 'charitable-can-edit' : 'charitable-no-edit',
													$class->can_be_duplicated ? 'charitable-can-duplicate' : 'charitable-no-duplicate',
													$class->can_be_deleted ? 'charitable-can-delete' : 'charitable-no-delete',
												);

												echo '<div class="' . implode( ' ', $charitable_field_css_classes ) . '" id="charitable-field-' . intval( $field_id ) . '" data-field-id="' . intval( $field_id ) . '" data-field-type="' . esc_attr( $field_type ) . '" data-field-max="' . esc_attr( $class->max_allowed ) . '" style="">'; // phpcs:ignore

												if ( $class->can_be_edited ) :
													echo '<a href="#" class="charitable-field-edit" data-type="' . esc_attr( $class->edit_type ) . '" data-section="' . esc_attr( $class->edit_section ) . '" data-edit-field-id="' . esc_attr( $class->edit_field_id ) . '" title="' . esc_html( $class->edit_label ) . '"><i class="fa fa-pencil"></i></a>';
													endif;
												if ( $class->can_be_duplicated ) :
													echo '<a href="#" class="charitable-field-duplicate" title="Duplicate Field"><i class="fa fa-files-o" aria-hidden="true"></i></a>';
													endif;
												if ( $class->can_be_deleted ) :
													echo '<a href="#" class="charitable-field-delete" title="Delete Field"><i class="fa fa-trash-o"></i></a>';
													endif;
												echo $class->field_preview( $field_settings, $campaign_data, $field_id, 'preview', $theme ); // phpcs:ignore
												echo '</div>';

												else :

													$this->render_missing_addon_field_preview( $field_type, $field_id, $field_type_data, $campaign_data );

											endif; // class exists.

										endif; // if type isn't false.

										++$field_id;

										endforeach;

										endif;

								?>
									</div>

								</li>

								<?php

								++$counter;

								endforeach;
							?>
						<?php else : ?>
							<li id="tab_<?php echo esc_attr( $tab_id ); ?>_content" class="tab_content_item active" data-tab-id="0" data-tab-type="html"></li>
						<?php endif; ?>
					</ul>
					<?php if ( $no_tab_class ) : ?>
						<p class="no-tab-notice"><?php echo $this->no_tab_empty_notice(); // phpcs:ignore ?></p>
					<?php endif; ?>
				</div>

			<?php

			echo ob_get_clean(); // phpcs:ignore

			return absint( $field_id - 1 );
		}

		/**
		 * Display missing addon message.
		 *
		 * @since 1.8.0
		 *
		 * @param string  $field_type      Tab info.
		 * @param integer $field_id        The current theme.
		 * @param array   $field_type_data The field type data.
		 * @param array   $campaign_data   The campaign data.
		 *
		 * @return void
		 */
		public function render_missing_addon_field_preview( $field_type = false, $field_id = false, $field_type_data = false, $campaign_data = false ) {

			$field_label = ( false !== $field_type ) ? (string) $this->get_addon_field_label( $field_type ) : false;
			$field_label = ( false === $field_label || '' === trim( $field_label ) ) ? 'unknown' : $field_label;

			$addon_label = ( false !== $field_type ) ? (string) $this->get_addon_label( $field_type ) : false;
			$addon_label = ( false === $addon_label || '' === trim( $addon_label ) ) ? 'unknown' : $addon_label;

			$template_id = ! empty( $campaign_data['template_id'] ) ? esc_attr( $campaign_data['template_id'] ) : false;
			$template_id = $template_id === false & ! empty( $_POST['id'] ) ? esc_attr( $_POST['id'] ) : $template_id; // phpcs:ignore

			// Get a background image, preferrabbly supplied.
			$background_image_url = $this->get_addon_placeholder_image( $field_type );
			$css_style            = false !== $background_image_url ? 'background-image: url(' . $background_image_url . ');' : false;
			$search_term          = $this->get_addon_search_term( $field_type );

			$field_id = intval( $field_id );

			echo '<div class="charitable-field charitable-field-missing charitable-field-' . esc_attr( $field_type ) . '" id="charitable-field-' . intval( $field_id ) . '" data-field-id="' . intval( $field_id ) . '" data-field-type="' . esc_attr( $field_type ) . '">';

			echo '<a href="#" class="charitable-field-delete" title="Delete Field"><i class="fa fa-trash-o"></i></a>';

			echo '<div class="charitable-missing-addon-bg" style="' . esc_attr( $css_style ) . '"></div>';

			echo '<div class="charitable-missing-addon-content">';

			if ( charitable_is_pro() ) {

				$display_html = $this->get_addon_install_or_activate_buttons( $field_type );

				if ( ! empty( $display_html['title'] ) ) {
					echo $display_html['title']; // phpcs:ignore
				} else {
					echo '<h2>We\'re sorry, the <strong>' . esc_html( $field_label ) . '</strong> does not seem to be available.</h2>';
				}

				if ( ! empty( $display_html['description'] ) ) {
					echo $display_html['description']; // phpcs:ignore
				} else {
					echo '<p>Please make sure that you have the latest version of the <strong>' . esc_html( $addon_label ) . '</strong> addon installed and activated.</p>';
				}

				echo '<div class="education-buttons">';

				if ( ! empty( $display_html['buttons'] ) ) {
					echo $display_html['buttons']; // phpcs:ignore
				} else {
					echo '<a class="button-link" target="_blank" href="' . esc_url( admin_url( 'admin.php?page=charitable-addons&search=' . $search_term ) ) . '">' . esc_html__( 'View Addons', 'charitable' ) . '</a>';
				}

				echo '</div>';

			} else {

				echo '<h2>We\'re sorry, the <strong>' . esc_html( $field_label ) . '</strong> is not available on your plan.</h2> <p>Please upgrade to the PRO plan to unlock all these awesome features.</p>';

				echo '<div class="education-buttons">
                <a class="button-link" target="_blank" href="https://wpcharitable.com/lite-vs-pro/">' . esc_html__( 'Learn More', 'charitable' ) . '</a> <button type="button" class="btn btn-confirm update-to-pro-link">' . esc_html__( 'Upgrade to PRO', 'charitable' ) . '</button>
                </div>';

			}

			echo '<a href="#" class="charitable-field-delete" title="Delete Field"><i class="fa fa-trash-o"></i></a>';
			echo '</div>';
			echo '</div>';
		}

		/**
		 * Display missing addon message and button(s).
		 *
		 * @since 1.8.0.4
		 *
		 * @param string $field_type The field type.
		 *
		 * @return array|bool
		 */
		public function get_addon_install_or_activate_buttons( $field_type = false ) {

			if ( false === $field_type ) {
				return false;
			}

			// get the list of installed plugins and charitable addon information.
			$installed_plugins = get_plugins();
			$addon_information = $this->get_addon_information( $field_type );

			// grab the list of active plugins only once, don't need it in the loop.
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'active_plugins' is a WordPress core hook.

			if ( false === $addon_information ) {
				return false;
			}

			$slug = ! empty( $addon_information['slug'] ) ? esc_attr( $addon_information['slug'] ) : false;

			if ( false === $slug ) {
				return false;
			}

			// Determine the plugin basename.
			$plugin_basename = $this->get_plugin_basename_from_slug( $addon_information['slug'] );

			if ( isset( $installed_plugins[ $plugin_basename ] ) && ! is_plugin_active( $plugin_basename ) ) {

				// if the plugin exists (installed, visible in the plugin list in WP admin) but not activated.

				return array(
					'title'       => '<h2>We\'re sorry, the <strong>' . esc_html( $addon_information['name'] ) . '</strong> addon does not seem to be available.</h2>',
					'description' => '<p>It appears you have <strong>' . esc_html( $addon_information['name'] ) . '</strong> installed but it needs to be activated.</p>',
					'buttons'     => '<a class="button-link charitable-not-activated" data-plugin-url="' . esc_attr( $addon_information['slug'] ) . '/' . esc_attr( $addon_information['slug'] ) . '.php" data-name="' . $addon_information['name'] . '" data-slug="' . $addon_information['slug'] . '" data-field-icon="" href="#">' . esc_html__( 'Activate ', 'charitable' ) . esc_html( $addon_information['name'] ) . '</a>',
				);
			} elseif ( is_array( $active_plugins ) && ! in_array( 'charitable-' . $slug . '/charitable-' . $slug . '.php', $active_plugins, true ) ) {

				// if pro is active AND the plugin is NOT activated, then that's another CSS class to encourage an install.

				return array(
					'title'       => '<h2>We\'re sorry, the <strong>' . esc_html( $addon_information['name'] ) . '</strong> addon does not seem to be available.</h2>',
					'description' => '<p>It appears you need <strong>' . esc_html( $addon_information['name'] ) . '</strong> to be installed and activated.</p>',
					'buttons'     => '<a class="button-link charitable-not-installed" data-plugin-url="' . $addon_information['install'] . '" data-name="' . $addon_information['name'] . '" data-slug="" data-field-icon="" href="#">' . esc_html__( 'Install &amp; Activate ', 'charitable' ) . esc_html( $addon_information['name'] ) . '</a>',
					'install_url' => ! empty( $addon_information['install'] ) ? esc_url( $addon_information['install'] ) : false,
				);
			}

			return false;
		}


		/**
		 * Get information about the addon used to display the missing addon message.
		 *
		 * @since 1.8.0.4
		 *
		 * @param string $field_type The field type.
		 *
		 * @return array|bool
		 */
		public function get_addon_information( $field_type = false ) {

			if ( false === $field_type ) {
				return false;
			}

			// Attempt to self-correct. If the $field_type doesn't start with 'charitable-', add it.
			if ( false === strpos( strtolower( $field_type ), 'charitable-' ) ) {
				$field_type = 'charitable-' . $field_type;
			}

			// grab the list of active plugins only once, don't need it in the loop.
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'active_plugins' is a WordPress core hook.

			// get license stuff once.
			$charitable_addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $charitable_addons ) {
				$charitable_addons = charitable_get_addons_data_from_server();
			}

			if ( ! is_array( $charitable_addons ) || empty( $charitable_addons ) ) {
				return;
			}

			foreach ( $charitable_addons as $charitable_addon ) {
				if ( ! empty( $charitable_addon['slug'] ) &&
						( strtolower( $field_type ) === strtolower( $charitable_addon['slug'] )
						|| strtolower( $field_type ) . 's' === strtolower( $charitable_addon['slug'] ) // covers in case the field type is 'video' when the slug is actually 'charitable-videos' (pural).
						)
					) {
					return $charitable_addon;
				}
				// This is to cover the case where the field type for example is 'charitable-ambassadors-team' but the slug is 'charitable-ambassadors'.
				if ( substr_count( $field_type, '-' ) > 1 ) {
					$modified_field_type = substr( $field_type, 0, strrpos( $field_type, '-' ) );
					if ( ! empty( $charitable_addon['slug'] ) && strtolower( $modified_field_type ) === strtolower( $charitable_addon['slug'] ) ) {
						return $charitable_addon;
					}
				}
			}

			return false;
		}

		/**
		 * Retrieve the plugin basename from the plugin slug.
		 *
		 * @since 1.7.0
		 *
		 * @param string $slug The plugin slug.
		 * @return string The plugin basename if found, else the plugin slug.
		 */
		public function get_plugin_basename_from_slug( $slug ) {

			$keys = array_keys( get_plugins() );

			foreach ( $keys as $key ) {
				if ( preg_match( '|^' . $slug . '|', $key ) ) {
					return $key;
				}
			}

			return $slug;
		}

		/**
		 * Get addoon field label.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type The field type.
		 *
		 * @return array
		 */
		public function get_addon_field_label( $field_type = false ) {

			$field_type_labels = $this->get_addon_field_labels();

			if ( array_key_exists( $field_type, $field_type_labels ) ) {
				return esc_html( $field_type_labels[ $field_type ]['field_label'] );
			}

			// Try a variation.
			if ( array_key_exists( 'charitable-' . $field_type, $field_type_labels ) ) {
				return esc_html( $field_type_labels[ 'charitable-' . $field_type ]['field_label'] );
			}

			// Try a variation.
			if ( array_key_exists( 'charitable-' . $field_type . 's', $field_type_labels ) ) {
				return esc_html( $field_type_labels[ 'charitable-' . $field_type . 's' ]['field_label'] );
			}

			return false;
		}

		/**
		 * Get addoon label.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type The field type.
		 *
		 * @return array
		 */
		public function get_addon_label( $field_type = false ) {

			$field_type_labels = $this->get_addon_field_labels();

			if ( array_key_exists( $field_type, $field_type_labels ) ) {
				return esc_html( $field_type_labels[ $field_type ]['addon_label'] );
			}

			// Try a variation.
			if ( array_key_exists( 'charitable-' . $field_type, $field_type_labels ) ) {
				return esc_html( $field_type_labels[ 'charitable-' . $field_type ]['addon_label'] );
			}

			// Try a variation.
			if ( array_key_exists( 'charitable-' . $field_type . 's', $field_type_labels ) ) {
				return esc_html( $field_type_labels[ 'charitable-' . $field_type . 's' ]['addon_label'] );
			}

			return false;
		}

		/**
		 * Get the search term that will show the correct addon if the user clicks on "View addons" in the preview
		 *
		 * @since 1.8.0
		 *
		 * @param string $addon_slug The addon slug.
		 *
		 * @return string
		 */
		public function get_addon_search_term( $addon_slug = false ) {

			if ( false === $addon_slug ) {
				return false;
			}

			$addon_labels = $this->get_addon_field_labels();

			if ( ! empty( $addon_labels[ $addon_slug ] ) ) {
				return strtolower( $addon_labels[ $addon_slug ]['admin_search'] );
			}

			// not found? take a guess.
			$search_term = str_replace( 'charitable-', '', $addon_slug );

			return strtolower( $search_term );
		}

		/**
		 * Get the placeholder image that appears in preview when the user doesn't have the addon activated.
		 *
		 * @since 1.8.0
		 *
		 * @param string $addon_slug The addon slug.
		 *
		 * @return string
		 */
		public function get_addon_placeholder_image( $addon_slug = false ) {

			if ( false === $addon_slug ) {
				return false;
			}

			$addon_labels = $this->get_addon_field_labels();

			if ( ! empty( $addon_labels[ $addon_slug ] ) ) {
				return strtolower( $addon_labels[ $addon_slug ]['placeholder_img'] );
			}

			return false;
		}

		/**
		 * Get addon field labels.
		 *
		 * @since 1.8.0
		 *
		 * @return array
		 */
		public function get_addon_field_labels() {

			$this->addon_labels = apply_filters(
				'charitable_campaign_builder_addon_labels',
				array(
					'ambassadors-team' => array(
						'field_label'     => 'Team Field',
						'addon_label'     => 'Ambassadors Addon',
						'url'             => false,
						'plan'            => 'pro',
						'admin_search'    => 'ambassador',
						'placeholder_img' => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/placeholders/field-team.jpg',
					),
					'video'            => array(
						'field_label'     => 'Video Field',
						'addon_label'     => 'Video Addon',
						'url'             => false,
						'plan'            => 'basic',
						'admin_search'    => 'video',
						'placeholder_img' => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/placeholders/field-video.jpg',
					),
					'updates-main'     => array(
						'field_label'     => 'Updates Field',
						'addon_label'     => 'Simple Updates Addon',
						'url'             => false,
						'plan'            => 'pro',
						'admin_search'    => 'updates',
						'placeholder_img' => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/placeholders/field-updates.jpg',
					),
					'comments-main'    => array(
						'field_label'     => 'Comments Field',
						'addon_label'     => 'Comments Addon',
						'url'             => false,
						'plan'            => 'pro',
						'admin_search'    => 'comments',
						'placeholder_img' => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/placeholders/field-comments.jpg',
					),
				)
			);

			return $this->addon_labels;
		}

		/**
		 * Get the preview of a campaign builder template.
		 *
		 * @since 1.8.0
		 *
		 * @param string|bool $template_id      The ID of the template to preview.
		 * @param string|bool $theme            The theme to use for the preview.
		 * @param array|bool  $campaign_data    The data to use for the campaign.
		 * @param bool        $exclude_wrappers Whether to exclude the template wrappers or not.
		 *
		 * @return string The HTML preview of the template.
		 */
		public function get_template_preview( $template_id = false, $theme = false, $campaign_data = false, $exclude_wrappers = false ) {

			if ( false === $template_id ) {
				return false;
			}

			ob_start();

			$this->field_id  = 0;
			$column_counter  = 0;
			$element_counter = 0;
			$section_counter = 0;
			$field_id        = 0;
			$last_field_id   = 0;

			$layout = isset( $theme['layout'] ) ? $theme['layout'] : false;

			// if this is a "default" theme or there's no layout (odd) then display the "no fields preview".
			$no_preview_css_class  = ( empty( $layout ) || ! is_array( $layout ) || ( ! empty( $theme['meta']['template_type'] ) && is_array( $theme['meta']['template_type'] ) && in_array( 'default', $theme['meta']['template_type'], true ) ) ) ? false : 'charitable-hidden';
			$design_wrap_css_class = ( false === $no_preview_css_class ) ? ' no-fields-mode ' : false;

			if ( false !== $exclude_wrappers ) {
				?>

			<div>
				<div>

			<?php } else { ?>

			<div class="charitable-design-wrap<?php echo esc_attr( $design_wrap_css_class ); ?>" id="charitable-design-wrap">

				<?php /* loading screen, sometimes needed for color previewing */ ?>

				<div class="charitable-campaign-preview-loader"><i class="charitable-loading-spinner charitable-loading-black charitable-loading-inline"></i></div>

				<div class="charitable-campaign-preview charitable-campaign-preview-container">

			<?php } ?>

				<?php

				if ( ! empty( $layout ) && is_array( $layout ) ) {

					foreach ( $layout as $row ) {

						$additional_css = ! empty( $row['css_class'] ) ? esc_attr( $row['css_class'] ) : '';
						$row_fields     = ! empty( $row['fields'] ) ? $row['fields'] : false;

						echo $this->get_template_element_start( $row['type'], $element_counter, $additional_css ); // phpcs:ignore

						$tabs_rendered = false;

						foreach ( $row['columns'] as $column ) {

							echo '<!-- column START -->';

							echo '<div data-column-id="' . intval( $column_counter ) . '" class="column charitable-field-column">';

							foreach ( $column as $section ) {

								echo '<!-- section START -->';

								ob_start();

								switch ( $section['type'] ) {
									case 'fields':
										if ( $tabs_rendered ) {
											++$last_field_id;
										}
										$last_field_id = (int) $this->render_fields( $section['fields'], $theme, $campaign_data, $last_field_id );
										break;
									case 'header':
										$last_field_id = (int) $this->render_fields( $section['fields'], $theme, $campaign_data, $last_field_id );
										break;
									case 'tabs':
										echo $this->get_template_element_start( 'tabs', null, null, $campaign_data ); // phpcs:ignore
										$campaign_tabs = ! empty( $campaign_data['tabs'] ) ? $campaign_data['tabs'] : array();
										echo $this->get_template_tab_nav( $section['tabs'], $theme, $campaign_tabs ); // phpcs:ignore
										$last_field_id = (int) $this->get_template_tab_content( $section['tabs'], $theme, $campaign_data, $row_fields, $last_field_id );
										echo $this->get_template_element_end( 'tabs' ); // phpcs:ignore
										$tabs_rendered = true;
										break;
									default:
										do_action( 'charitable_campaign_builder_preview_section_' . $section['type'], $section, $row, $theme, $campaign_data );
										break;
								}

								$field_html = ob_get_clean();

								$section_css_class  = ( '' === $field_html && ( empty( $row['css_class'] ) || strpos( $row['css_class'], 'no-field-target' ) === false ) ) ? 'charitable-field-target' : '';
								$section_css_class .= ( empty( $row['css_class'] ) || strpos( $row['css_class'], 'no-field-wrap' ) === false ) ? ' charitable-field-wrap' : '';

								echo '<div data-section-id="' . intval( $section_counter ) . '" data-section-type="' . esc_attr( $section['type'] ) . '" class="section charitable-field-section ' . esc_attr( $section_css_class ) . '">';

								echo '<div class="charitable-drag-new-block-here"><p>Drag New Block Here.</p></div>';

								echo $field_html; // phpcs:ignore

								++$section_counter;

								echo '</div>';

								echo '<!-- section END -->';
							}

							++$column_counter;

							echo '</div>';

							echo '<!-- column END -->';
						}

						echo $this->get_template_element_end( $row['type'], $element_counter ); // phpcs:ignore

						++$element_counter;
					}
				}

				return ob_get_clean();
		}

		/**
		 * Returns the default start of a column.
		 *
		 * @return string The HTML markup for the start of a column.
		 */
		public function get_default_column_start() {

			ob_start();

			?>

			<div data-column-id="0" class="column charitable-field-column">

			<?php

			return ob_get_clean();
		}

		/**
		 * Returns the default end of a column.
		 *
		 * @return string The HTML markup for the end of a column.
		 */
		public function get_default_column_end() {

			ob_start();

			?>

			</div>

			<?php

			return ob_get_clean();
		}

		/**
		 * Output the 'fields' or 'blocks' for the template.
		 *
		 * @since 1.8.0
		 *
		 * @param string $template_id   Template slug.
		 * @param string $theme         The current theme.
		 * @param array  $campaign_data The campaign data.
		 *
		 * @return string
		 */
		public function get_template_field_options( $template_id = false, $theme = false, $campaign_data = false ) {

			ob_start();

			$field_id = 0;
			$layout   = (array) $theme['layout'];

			// If there is no description for this campaign, then this (assumingly) must be a new campaign and we should check and see if there's a description that comes with the template.
			if ( false !== $template_id && ! isset( $campaign_data['settings']['general']['description'] ) ) {

				if ( ! empty( $this->templates_data['templates'][ $template_id ]['settings']['general']['description'] ) ) {
					$campaign_data['settings']['general']['description'] = $this->templates_data['templates'][ $template_id ]['settings']['general']['description'];
				}
			}

			foreach ( $layout as $row ) {
				foreach ( $row['columns'] as $column ) {
					foreach ( $column as $section_key => $section ) {

						switch ( $section['type'] ) {
							case 'fields':
								foreach ( $section['fields'] as $key => $field_type_data ) :

									$field_type = is_array( $field_type_data ) && ! empty( $field_type_data['type'] ) ? esc_attr( $field_type_data['type'] ) : esc_attr( $key );

									$type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

									if ( is_array( $field_type_data ) ) {
										$campaign_data['fields'][ $field_id ] = $field_type_data;
									}

									if ( class_exists( $type ) ) :
										$class = new $type();
										if ( method_exists( $class, 'settings_display' ) ) {
											echo $class->settings_display( $field_id, $campaign_data ); // phpcs:ignore --- second param was $this->campaign_data, but that isn't available.
										}
									endif;
									++$field_id;
								endforeach;
								break;
							case 'tabs':
								foreach ( $section['tabs'] as $tab_index => $tab ) :
									foreach ( $tab['fields'] as $key => $field_type_data ) :

										$field_type = is_array( $field_type_data ) && ! empty( $field_type_data['type'] ) ? esc_attr( $field_type_data['type'] ) : esc_attr( $key );

										// header.
										$type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

										if ( is_array( $field_type_data ) ) {
											$campaign_data['fields'][ $field_id ] = $field_type_data;
										}

										if ( class_exists( $type ) ) :
											$class = new $type();
											if ( method_exists( $class, 'settings_display' ) ) {
												echo $class->settings_display( $field_id, $campaign_data ); // phpcs:ignore --- second param was $this->campaign_data, but that isn't available.
											}
										endif;

										++$field_id;

									endforeach;
								endforeach;
								break;
							default:
								break;
						}
					}
				}
			}

			return ob_get_clean();
		}

		/**
		 * Output tab options for the left sidebar.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $template_id  Tab info.
		 * @param string  $theme        The current theme.
		 * @param array   $campaign_data Campaign data.
		 *
		 * @return string
		 */
		public function get_template_tab_options( $template_id = false, $theme = false, $campaign_data = false ) { // phpcs:ignore

			ob_start();

			$max_tab_title_length = apply_filters( 'charitable_builder_design_tab_title_length', 20 );

			$layout = (array) $theme['layout'];
			$tabs   = array();

			// Currently only one tab area per template, so we need to locate that tab area (if it exists) in the layout.
			foreach ( $layout as $row ) {
				foreach ( $row['columns'] as $column ) {
					foreach ( $column as $section ) {
						if ( $section['type'] === 'tabs' && ! empty( $section['tabs'] ) ) {
							$tabs = $section['tabs'];
							continue;
						}
					}
				}
			}

			$number_of_tabs        = count( $tabs );
			$hide_tab_nav_disabled = $number_of_tabs > 1 ? 'disabled="disabled"' : false;

			?>

				<?php $enable_tabs = 'enabled'; ?>

				<div data-field-id="" id="charitable-group-row-design-layout-options-tabs-enable" class="charitable-panel-field charitable-panel-field-toggle " data-ajax-label="enable_tabs">
					<span class="charitable-toggle-control">
						<input type="checkbox" id="charitable-panel-field-settings-charitable-campaign-enable-tabs" name="layout__advanced__enable_tabs" data-advanced-field-id="enable_tabs" value="disabled" <?php checked( $enable_tabs, 'disabled' ); ?> />
						<label class="charitable-toggle-control-icon" for="charitable-panel-field-settings-charitable-campaign-enable-tabs"></label>
						<label for="charitable-panel-field-settings-charitable-campaign-enable-tabs">Hide tab section. </label>
						<?php echo charitable_get_tooltip_html( esc_html__( 'Remove tabs from your public campaign page but not delete any items in the preview.', 'charitable' ), 'tooltipstered' ); // phpcs:ignore ?>
					</span>
				</div>

				<hr />

				<!-- group -->
				<div class="charitable-group charitable-layout-options-tab-group charitable-closed charitable-new-tab hidden" data-group_id="2">
					<div class="charitable-general-layout-heading" data-group="general-layout-tab">
						<a href="#" class="charitable-draggable"><i class="fa fa-bars"></i></a>
						<span>[ <?php echo esc_html__( 'New Tab', 'charitable' ); ?> ]</span>
						<a href="#" class="charitable-toggleable-group"><i class="fa fa-angle-down charitable-angle-down"></i></a>
						<a href="#" class="charitable-tab-group-delete" title="Delete Tab"><i class="fa fa-trash-o" aria-hidden="true"></i></a>

					</div>
					<!-- rows -->
					<div class="charitable-group-rows">
						<!-- row -->
						<div class="charitable-group-row charitable-tab-title-row" id="tabs_xxx_row_title" data-field-id="">
							<label for=""><?php echo esc_html__( 'Title', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( false, 'tooltipstered' ); // phpcs:ignore ?>
							<input type="text" class="" id="tabs_xxx_title" name="tabs__xxx__title" value="" placeholder="<?php echo esc_html__( 'Tab Title', 'charitable' ); ?> " maxlength="<?php echo intval( $max_tab_title_length ); ?>">
						</div>
						<div class="charitable-group-row charitable-tab-title-row" id="tabs_xxx_row_visible_nav" data-field-id="">
							<span class="charitable-toggle-control">
								<input type="checkbox" id="tabs_xxx_visible_nav" class="charitable-settings-tab-visible-nav" name="tabs__xxx__visible_nav" value="invisible" <?php echo esc_attr( $hide_tab_nav_disabled ); ?> />
								<label class="charitable-toggle-control-icon" for="tabs_xxx_visible_nav"></label>
								<label for="tabs_xxx_visible_nav"><?php echo esc_html__( 'Hide tab navigation.', 'charitable' ); ?> </label>
								<?php echo charitable_get_tooltip_html( esc_html__( 'Hide the tab in the navigation bar (only if you have one tab in your design).', 'charitable' ), 'tooltipstered' ); // phpcs:ignore ?>
							</span>
						</div>
					</div>
					<!-- end rows -->
				</div> <!-- end group -->
				<?php

				if ( $tabs ) {

					$counter = 0;

					foreach ( $tabs as $tab_id => $tab_info ) :

						$group_id       = ( strtolower( $tab_id ) === 'campaign' ) ? 0 : $tab_id;
						$group_name_var = ( $tab_id === 'campaign' ) ? $group_id : $group_id;
						$group_title    = isset( $tab_info['title'] ) && '' !== trim( $tab_info['title'] ) ? $tab_info['title'] : '[ New Tab ]';
						$group_header   = ( $tab_id === 'campaign' ) ? 'Campaign' : $group_title;
						$group_desc     = isset( $tab_info['desc'] ) && '' !== trim( $tab_info['desc'] ) ? $tab_info['desc'] : false;
						$group_classes  = isset( $tab_info['title'] ) && '' !== trim( $tab_info['title'] ) ? false : 'charitable-new-tab';
						$data_group     = ( $tab_id === 'campaign' ) ? 'general-layout-tab' : 'general-layout-tab'; // general-layout-campaign.
						$active         = $counter === 0 ? 'active' : false;
						$closed         = $counter === 0 ? 'charitable-open' : 'charitable-closed';
						$arrow_icon     = ! $active ? 'fa fa-angle-down charitable-angle-right' : 'fa fa-angle-down charitable-angle-down';
						$type           = ( isset( $tab_info['type'] ) && '' !== trim( $tab_info['type'] ) ) ? $tab_info['type'] : 'html';
						$row_class      = 'html' !== $type ? 'hidden' : false;

						$visible_nav_checked = ! empty( $tab_info['visible_nav'] ) && 'invisible' === $tab_info['visible_nav'] ? 'checked="checked"' : '';
						$visible_nav_checked = ( false !== $hide_tab_nav_disabled ) ? '' : $visible_nav_checked;
						$disabled_css        = ( false !== $hide_tab_nav_disabled ) ? 'charitable-disabled' : false;

						?>

							<!-- group -->
							<div class="charitable-group charitable-layout-options-tab-group <?php echo esc_attr( $group_classes ); ?> <?php echo esc_attr( $closed ); ?> <?php echo esc_attr( $active ); ?>" data-group_id="<?php echo esc_attr( $group_id ); ?>">
								<div class="charitable-general-layout-heading" data-group="<?php echo esc_attr( $data_group ); ?>">
									<a href="#" class="charitable-draggable"><i class="fa fa-bars"></i></a>
									<span><?php echo esc_html( $group_header ); ?></span>
									<a href="#" class="charitable-toggleable-group"><i class="<?php echo esc_attr( $arrow_icon ); ?>"></i></a>
									<a href="#" class="charitable-tab-group-delete" title="<?php esc_attr_e( 'Delete Tab', 'charitable' ); ?>"><i class="fa fa-trash-o" aria-hidden="true"></i></a>

								</div>
								<!-- rows -->
								<div class="charitable-group-rows">
									<div class="charitable-group-row charitable-tab-title-row" id="row_tabs__<?php echo esc_attr( $group_name_var ); ?>__title" data-tab-id="<?php echo esc_attr( $group_name_var ); ?>">
										<label for=""><?php echo esc_html__( 'Title', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( false, 'tooltipstered' ); // phpcs:ignore ?>
										<input type="text" class="" id="" name="tabs__<?php echo esc_attr( $group_name_var ); ?>__title" value="<?php echo esc_attr( $group_title ); ?>" placeholder="" maxlength="<?php echo intval( $max_tab_title_length ); ?>">
									</div>
									<div class="charitable-group-row charitable-tab-title-row" id="row_tabs__<?php echo esc_attr( $group_name_var ); ?>__visible_nav" data-tab-id="<?php echo esc_attr( $group_name_var ); ?>">
										<span class="charitable-toggle-control">
											<input type="checkbox" id="charitable-panel-field-settings-charitable-campaign-<?php echo esc_attr( $group_name_var ); ?>__visible_nav" class="charitable-settings-tab-visible-nav" name="tabs__<?php echo esc_attr( $group_name_var ); ?>__visible_nav" value="invisible" <?php echo esc_attr( $visible_nav_checked ); ?> <?php echo esc_attr( $visible_nav_checked ); ?> />
											<label class="charitable-toggle-control-icon <?php echo esc_attr( $disabled_css ); ?>" for="charitable-panel-field-settings-charitable-campaign-<?php echo esc_attr( $group_name_var ); ?>__visible_nav"></label>
											<label class="<?php echo esc_attr( $disabled_css ); ?>" for="charitable-panel-field-settings-charitable-campaign-<?php echo esc_attr( $group_name_var ); ?>__visible_nav">Hide tab navigation. </label>
										<?php echo charitable_get_tooltip_html( esc_html__( 'Hide the tab in the navigation bar (only if you have one tab in your design).', 'charitable' ), 'tooltipstered' ); // phpcs:ignore ?>
										</span>
									</div>
								</div>
								<!-- end rows -->
							</div> <!-- end group -->

							<?php ++$counter; ?>

						<?php
						endforeach;

				} else {
					?>

						<!-- group -->
						<div class="charitable-group charitable-layout-options-tab-group charitable-layout-options-general-group active" data-group_id="0"> <!-- open or closed -->
							<a href="#" class="charitable-general-layout-heading charitable-toggleable-group" data-group="general-layout-campaign">
								<span><?php echo esc_html__( 'Campaign', 'charitable' ); ?></span>
								<i class="fa fa-angle-down"></i>
							</a>
							<!-- rows -->
							<div class="charitable-group-rows">
								<input type="hidden" name="tabs__campaign__type" value="html" />
								<!-- row -->
								<div class="charitable-group-row" id="" data-field-id="">
									<label for="">Title <?php echo charitable_get_tooltip_html( false, 'tooltipstered' ); // phpcs:ignore ?>
									<input type="text" class="" id="charitable-field-option-4-name" name="tabs__campaign__title" value="" placeholder="">
								</div>
								<!-- row -->
								<div class="charitable-group-row" id="" data-field-id="">
									<label for="">Description <?php echo charitable_get_tooltip_html( false, 'tooltipstered' ); // phpcs:ignore ?>
									<textarea class="" id="" name="tabs__campaign__desc" rows="3"></textarea>
								</div>
							</div>
							<!-- end rows -->
						</div> <!-- end group -->
					<?php } ?>

				<!-- button? -->
				<button class="charitable-tab-groups-add charitable-btn charitable-btn-sm"><?php echo esc_html__( '+ Add New Tab', 'charitable' ); ?></button>
				<!-- end button -->

				<?php
				return ob_get_clean();
		}

		/**
		 * Retreive data of a particular theme.
		 *
		 * @since 1.8.0
		 *
		 * @param string $template_id   Template slug.
		 * @param array  $campaign_data Campaign data.
		 * @param bool   $no_cache      Whether to bypass the cache or not. True to bypass the cache. False to use the cache. Default is false.
		 *
		 * @return array
		 */
		public function get_template_data( $template_id = false, $campaign_data = array(), $no_cache = false ) {

			if ( false === $template_id ) {
				$template_id = charitable_campaign_builder_default_template();
			}

			$templates_data = $this->get_templates_data( $template_id, $campaign_data, $no_cache );
			$template_data  = ! empty( $templates_data['templates'][ $template_id ] ) ? (array) $templates_data['templates'][ $template_id ] : false;

			return $template_data;
		}

		/**
		 * Retreive data of all available campaign builder templates from the database (or generate).
		 *
		 * @since 1.8.0
		 * @since 1.8.1.3 Added $no_cache parameter.
		 *
		 * @param string $template_id   Template slug.
		 * @param array  $campaign_data Campaign data.
		 * @param bool   $no_cache      Whether to bypass the cache or not. True to bypass the cache. False to use the cache. Default is false.
		 *
		 * @return array
		 */
		public function get_templates_data( $template_id = false, $campaign_data = array(), $no_cache = false ) {

			$templates_data = $no_cache || ( charitable_is_debug() ) || ( defined( 'CHARITABLE_BUILDER_NO_CACHE_TEMPLATE' ) && CHARITABLE_BUILDER_NO_CACHE_TEMPLATE ) ? false : get_option( 'charitable_campaign_builder_templates' );

			if ( empty( $templates_data ) ) {

				// if we cannot locate, we will create the basic choices and add them into the WordPress option, while returning the data.
				$templates_data = $this->get_source_template_data();

				foreach ( $templates_data['templates'] as $key => $values ) :

					if ( is_admin() ) :
						$templates_data['templates'][ $key ]['preview']                      = $this->get_template_preview( $key, $templates_data['templates'][ $key ], $campaign_data );
						$templates_data['templates'][ $key ]['field_options']                = $this->get_template_field_options( $key, $templates_data['templates'][ $key ], $campaign_data );
						$templates_data['templates'][ $key ]['tab_options']                  = $this->get_template_tab_options( $key, $templates_data['templates'][ $key ], $campaign_data );
						$templates_data['templates'][ $key ]['advanced']['show_field_names'] = charitable_show_field_names_by_default( $key ) ? 'show' : 'hide';
						$templates_data['templates'][ $key ]['advanced']['preview_mode']     = charitable_show_preview_mode_by_default( $key ) ? 'normal' : 'minimum';
					endif;
				endforeach;

				if ( ( charitable_is_debug() ) || ( defined( 'CHARITABLE_BUILDER_NO_CACHE_TEMPLATE' ) && CHARITABLE_BUILDER_NO_CACHE_TEMPLATE ) ) {
					delete_option( 'charitable_campaign_builder_templates', $templates_data );
				} else {
					// update the WordPress option.
					update_option( 'charitable_campaign_builder_templates', $templates_data );
				}
			}

			return apply_filters( 'charitable_campaign_builder_template_data', $templates_data );
		}


		/**
		 * Get the meta field data for a given template.
		 *
		 * @param string   $field_name The name of the meta field.
		 * @param int|bool $template_id The ID of the template to get the meta field data for.
		 * @return mixed The value of the meta field.
		 */
		public function get_template_data_meta_field( $field_name = '', $template_id = false ) {

			if ( '' === trim( $field_name ) ) {
				return false;
			}

			if ( false === $template_id ) {
				$template_id = charitable_campaign_builder_default_template();
			}

			$template_data = $this->get_template_data( $template_id );

			return ! empty( $template_data['meta'][ $field_name ] ) ? $template_data['meta'][ $field_name ] : false;
		}

		/**
		 * Out the HTML that does the display of items and sidebar search/categories of the builder.
		 *
		 * @since 1.8.0
		 * @version 1.8.1.12 Broke off HTML into function to generate sections (blank vs templated).
		 *
		 * @return array
		 */
		public function output_templates_panel() {

			ob_start();

			$current_template_id = false;
			$template_items      = array(
				'single_column' => 0,
				'template'      => 0,
			);

			if ( isset( $_GET['campaign_id'] ) && 0 !== intval( $_GET['campaign_id'] ) ) { // phpcs:ignore

				$template_data       = charitable_get_template_data_from_campaign( abs( $_GET['campaign_id'] ) ); // phpcs:ignore
				$current_template_id = isset( $template_data['template_id'] ) ? $template_data['template_id'] : false;

			}

			$this->prepare_templates_data();

			?>

			<div id="charitable-template-container" class="charitable-template-options">

				<div class="charitable-setup-templates-sidebar">

					<div class="charitable-setup-templates-search-wrap">
						<i class="fa fa-search"></i>
						<i class="fa fa-close"></i>
						<label>
							<input type="text" id="charitable-setup-template-search" value="" placeholder="Search Templates">
						</label>
					</div>

					<ul class="charitable-setup-templates-categories">
						<?php $this->template_categories(); ?>
					</ul>

					<div class="charitable-setup-templates-feedback">
						<h6><?php echo esc_html__( "Don't See What You're Looking For?", 'charitable' ); ?></h6>
						<p>
						<?php
						printf(
							/* translators: 1: Template ID */
							'<a href="#" class="send-feedback">%1$s</a> %2$s',
							esc_html__( 'Let us know', 'charitable' ),
							esc_html__( 'what templates to add in the future.', 'charitable' ) // no comma here to avoid fatal on old PHP versions (for now).
						);
						?>
						</p>

					</div>

				</div>

				<div id="charitable-template-list" class="charitable-template-list"> <!-- start template list -->

					<div class="charitable-template-list-container">

					<?php

					if ( ! empty( $this->prepared_templates ) ) :

						foreach ( $this->prepared_templates as $campaign_template_slug => $campaign_template_data ) :

							if ( is_array( $campaign_template_data['meta']['template_type'] ) && array_intersect( array( 'blank' ), $campaign_template_data['meta']['template_type'] ) ) {

								++$template_items['single_column'];

							} else {

								++$template_items['template'];

							}

						endforeach;

						if ( $template_items['single_column'] > 0 ) :

							echo '<div class="charitable-template-list-section charitable-template-list-section-blank">
									<h4>' . esc_html__( 'Start from scratch with a blank template...', 'charitable' ) . '</h4>
								  </div>';

							foreach ( $this->prepared_templates as $campaign_template_slug => $campaign_template_data ) :

								$this->output_template_item( $campaign_template_slug, $campaign_template_data, array( 'blank' ), array(), $current_template_id );

							endforeach;

						endif;

						if ( $template_items['template'] > 0 ) :

							echo '<div class="charitable-template-list-section charitable-template-list-section-prebuilt">
									<h4>' . esc_html__( '...or select from a prebuilt template and be up and running in minutes!', 'charitable' ) . '</h4>
								  </div>';

							foreach ( $this->prepared_templates as $campaign_template_slug => $campaign_template_data ) :

								$this->output_template_item( $campaign_template_slug, $campaign_template_data, false, array( 'blank' ), $current_template_id );

							endforeach;

						endif;

						?>

						<?php else : ?>

						<div class="charitable-error">

							<p><?php echo esc_html__( 'Unable to locate templates for campaigns. Contact support.', 'charitable' ); ?></p>

						</div>

						<?php endif; ?>

					</div>

				<div class="charitable-templates-no-results" style="display: none;">
					<p><?php echo esc_html__( 'Sorry, we didn\'t find any templates that match your criteria.', 'charitable' ); ?></p>
				</div>

			</div> <!-- end template list -->

		</div>

			<?php

			return ob_get_clean();
		}

		/**
		 * HTML output for a single template item in the list.
		 *
		 * @since 1.8.1.12
		 *
		 * @param string $campaign_template_slug  The template slug.
		 * @param array  $campaign_template_data  The template data.
		 * @param array  $filter_include          The categories to include.
		 * @param array  $filter_exclude          The categories to exclude.
		 * @param bool   $current_template_id     The current template ID.
		 */
		private function output_template_item( $campaign_template_slug = '', $campaign_template_data = array(), $filter_include = array(), $filter_exclude = array(), $current_template_id = false ) {

			$create_update_term  = isset( $_GET['campaign_id'] ) && 0 !== intval( $_GET['campaign_id'] ) ? esc_html__( 'Restart Campaign', 'charitable' ) : esc_html__( 'Create Campaign', 'charitable' ); // phpcs:ignore

			if ( '' === trim( $campaign_template_slug ) || empty( $campaign_template_data ) ) {
				return false;
			}

			// If we are filtering for template categories and this template is not in the list ('template_type'), then return false.
			if ( ! empty( $filter_include ) && is_array( $campaign_template_data['meta']['template_type'] ) && ! empty( $campaign_template_data['meta']['template_type'] ) ) {
				// If none of the elements in the $filter_include array are in the template_type, return.
				if ( ! array_intersect( $filter_include, $campaign_template_data['meta']['template_type'] ) ) {
					return false;
				}
			}

			// If we are filtering for items to exclude, then we will return false if any of the elements in the $filter_exclude array are in the template_type.
			if ( ! empty( $filter_exclude ) && is_array( $campaign_template_data['meta']['template_type'] ) && ! empty( $campaign_template_data['meta']['template_type'] ) ) {
				// If any of the elements in the $filter_exclude array are in the template_type, return.
				if ( array_intersect( $filter_exclude, $campaign_template_data['meta']['template_type'] ) ) {
					return false;
				}
			}

			$primary      = isset( $campaign_template_data['meta']['colors']['primary'] ) && ! empty( $campaign_template_data['meta']['colors']['primary'] ) ? esc_attr( $campaign_template_data['meta']['colors']['primary'] ) : false;
			$secondary    = isset( $campaign_template_data['meta']['colors']['secondary'] ) && ! empty( $campaign_template_data['meta']['colors']['secondary'] ) ? esc_attr( $campaign_template_data['meta']['colors']['secondary'] ) : false;
			$tertiary     = isset( $campaign_template_data['meta']['colors']['tertiary'] ) && ! empty( $campaign_template_data['meta']['colors']['tertiary'] ) ? esc_attr( $campaign_template_data['meta']['colors']['tertiary'] ) : false;
			$button_color = isset( $campaign_template_data['meta']['colors']['button_bg'] ) && ! empty( $campaign_template_data['meta']['colors']['button_bg'] ) ? esc_attr( $campaign_template_data['meta']['colors']['button_bg'] ) : false;

			$template_categories = ! empty( $campaign_template_data['meta']['categories'] ) ? implode( ', ', $campaign_template_data['meta']['categories'] ) : false; // todo: santitize.
			$template_tags       = ! empty( $campaign_template_data['meta']['search_tags'] ) ? implode( ', ', $campaign_template_data['meta']['search_tags'] ) : false; // todo: santitize.
			$suffixes            = ! empty( $campaign_template_data['meta']['suffixes'] ) ? serialize( $campaign_template_data['meta']['suffixes'] ) : false; // todo: santitize.

			$show_only_for_lite = ! empty( $campaign_template_data['dependencies']['is_lite'] ) ? true : false;

			if ( charitable_is_pro() && $show_only_for_lite ) {
				return;
			}

			if ( isset( $campaign_template_data['thumbnail_url'] ) && false !== $campaign_template_data['thumbnail_url'] ) {

				$template_thumbnail_html = '<div class="charitable-template-thumbnail charitable-template-thumbnail-' . $campaign_template_slug . '" style="background-image: url(' . $campaign_template_data['thumbnail_url'] . ')"></div>';

			} elseif ( file_exists( charitable()->get_path( 'assets', true ) . 'images/campaign-builder/templates/' . $campaign_template_slug . '/thumbnail.png' ) ) {

					$template_thumbnail_html = '<div class="charitable-template-thumbnail charitable-template-thumbnail-' . $campaign_template_slug . '" style="background-image: url(' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/' . $campaign_template_slug . '/thumbnail.png)"></div>';

			} else {

				$template_thumbnail_html = '<div class="charitable-template-thumbnail charitable-template-thumbnail-' . $campaign_template_slug . '" style="background-image: url(' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/feedback.png)"></div>';

			}

			$create_update_css    = isset( $_GET['campaign_id'] ) && 0 !== intval( $_GET['campaign_id'] ) ? 'update-campaign' : 'create-campaign'; // phpcs:ignore
			$template_code        = esc_attr( $campaign_template_slug );
			$template_preview_url = charitable()->get_path( 'assets', false ) . 'images/campaign-builder/templates/' . $template_code . '/preview.png';
			$template_description = esc_html( wp_strip_all_tags( $campaign_template_data['meta']['description'] ) );
			$template_types       = is_array( $campaign_template_data['meta']['template_type'] ) && ! empty( $campaign_template_data['meta']['template_type'] ) ? implode( ' ', $campaign_template_data['meta']['template_type'] ) : false;
			$template_types       = ( false === $template_types && false !== $campaign_template_data['meta']['template_type'] ) ? esc_attr( $campaign_template_data['meta']['template_type'] ) : $template_types;
			?>

			<div class="charitable-template-list-container-item charitable-template-<?php echo esc_attr( $campaign_template_slug ); ?> <?php echo esc_attr( $template_types ); ?>">

				<div class="charitable-template
				<?php
				if ( $current_template_id && $campaign_template_slug === $current_template_id ) :
					?>
					active<?php endif; ?>" data-template-code="<?php echo esc_attr( $template_code ); ?>" data-template-preview-url="<?php echo esc_url( $template_preview_url ); ?>" data-template-description="<?php echo esc_attr( $template_description ); ?>" data-template-label="<?php echo esc_html( $campaign_template_data['meta']['label'] ); ?>" data-template-type="<?php echo esc_attr( $template_types ); ?>" data-template-categories="<?php echo esc_attr( $template_categories ); ?>" data-template-tags="<?php echo esc_attr( $template_tags ); ?>" data-template-primary="<?php echo esc_attr( $primary ); ?>" data-template-secondary="<?php echo esc_attr( $secondary ); ?>" data-template-tertiary="<?php echo esc_attr( $tertiary ); ?>" data-template-button="<?php echo esc_attr( $button_color ); ?>" data-template-suffixes="<?php echo htmlspecialchars( $suffixes ); // phpcs:ignore ?>">

					<div class="charitable-banner-container
					<?php
					if ( ! $current_template_id || $campaign_template_slug !== $current_template_id ) :
						?>
						charitable-hidden<?php endif; ?>">
						<div class="charitable-banner"><?php echo esc_html__( 'Active', 'charitable' ); ?></div>
					</div>

					<div class="template-thumbnail placeholder">
					<?php if ( 'lite-to-pro' !== $campaign_template_slug ) { ?>
							<div class="template-buttons
							<?php
							if ( $current_template_id && $campaign_template_slug === $current_template_id ) :
								?>
								charitable-hidden<?php endif; ?>">
								<a href="#" class="button <?php echo esc_attr( $create_update_css ); ?>"><?php echo esc_html( $create_update_term ); ?></a>
								<?php if ( ! isset( $campaign_template_data['meta']['preview'] ) || false !== $campaign_template_data['meta']['preview'] ) : ?>
									<a href="#" class="button preview-campaign"><?php echo esc_html__( 'Preview', 'charitable' ); ?></a>
								<?php endif; ?>
							</div>
						<?php } else { ?>
							<div class="template-buttons">
								<a href="#" class="button send-feedback"><?php echo esc_html__( 'Send Feedback', 'charitable' ); ?></a>
							</div>
						<?php } ?>

					<?php echo $template_thumbnail_html; // phpcs:ignore ?>

					</div>
				<?php printf( '<h4>%s</h4>', $campaign_template_data['meta']['label'] ); // phpcs:ignore ?>
				<?php printf( '<p>%s</p>', $campaign_template_data['meta']['description'] ); // phpcs:ignore ?>

				</div>

			</div>

			<?php
		}

		/**
		 * Prepare templates data for output.
		 *
		 * @since 1.8.0
		 * @version 1.8.1.12
		 */
		private function prepare_templates_data() {

			$templates        = $this->get_templates_data();
			$single_templates = array();

			if ( empty( $templates ) || empty( $templates['templates'] ) ) {
				return;
			}

			ksort( $templates['templates'] );

			// move any "simple" template to the front.
			foreach ( $templates['templates'] as $template_name => $template_info ) :
				if ( ! empty( $template_info['meta']['template_type'] ) && is_array( $template_info['meta']['template_type'] ) && in_array( 'simple', $template_info['meta']['template_type'] ) ) {
					$single_templates['templates'][ $template_name ] = $template_info;
					unset( $templates['templates'][ $template_name ] );
				}
			endforeach;

			ksort( $single_templates['templates'] );

			$templates['templates'] = array_merge( $single_templates['templates'], $templates['templates'] );

			// Loop through each available template.
			foreach ( $templates['templates'] as $id => $template ) {

				$this->prepared_templates[ $id ] = $this->prepare_template_render_arguments( $template );

			}
		}

		/**
		 * Prepare arguments for rendering template item.
		 *
		 * @since 1.8.0
		 *
		 * @param array $template Template data.
		 *
		 * @return array Arguments.
		 */
		private function prepare_template_render_arguments( $template ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

			return $template;
		}

		/**
		 * Generate and display categories menu.
		 *
		 * @since 1.8.0
		 */
		public function template_categories() {

			$templates_count = $this->get_count_in_categories();

			$generic_categories = array(
				'all' => esc_html__( 'All Templates', 'charitable' ),
			);

			if ( isset( $templates_count['all'], $templates_count['available'] ) && $templates_count['all'] !== $templates_count['available'] ) {
				$generic_categories['available'] = esc_html__( 'Available Templates', 'charitable' );
			}

			$generic_categories['favorites']    = esc_html__( 'Favorite Templates', 'charitable' );
			$generic_categories['new']          = esc_html__( 'New Templates', 'charitable' );
			$generic_categories['single']       = esc_html__( 'Single', 'charitable' );
			$generic_categories['peer-to-peer'] = esc_html__( 'Peer-to-Peer', 'charitable' );

			$generic_categories = apply_filters( 'charitable_campaign_builder_template_generic_categories', $generic_categories );

			$this->output_categories( $generic_categories, $templates_count );

			printf( '<li class="divider"></li>' );

			$common_categories = array();

			if ( $this->is_custom_templates_available ) {
				$common_categories['custom'] = esc_html__( 'Custom Templates', 'charitable' );
			}

			if ( $this->is_addon_templates_available ) {
				$common_categories['addons'] = esc_html__( 'Addon Templates', 'charitable' );
			}

			$categories = array_merge(
				$common_categories,
				$this->get_categories()
			);

			$this->output_categories( $categories, $templates_count );
		}

		/**
		 * Output categories list.
		 *
		 * @since 1.8.0
		 *
		 * @param array $categories      Categories list.
		 * @param array $templates_count Templates count by categories.
		 *
		 * @return void
		 */
		private function output_categories( $categories, $templates_count ) {

			foreach ( $categories as $slug => $name ) {

				$class = '';

				if ( $slug === 'all' ) {
					$class = ' class="active"';
				} elseif ( empty( $templates_count[ $slug ] ) ) {
					$class = ' class="charitable-hidden"';
				}

				$count = isset( $templates_count[ $slug ] ) ? $templates_count[ $slug ] : '0';

				printf(
					'<li data-category="%1$s"%2$s>%3$s<span>%4$s</span></li>',
					esc_attr( $slug ),
					$class, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $name ),
					esc_html( $count )
				);
			}
		}

		/**
		 * Get categories data.
		 *
		 * @since 1.8.0
		 *
		 * @return array
		 */
		public function get_categories() {

			$this->categories = apply_filters(
				'charitable_campaign_builder_template_categories',
				array(
					'animal-pets'        => esc_html__( 'Animal / Pets', 'charitable' ),
					'club-organizations' => esc_html__( 'Club / Organizations', 'charitable' ),
					'environmental'      => esc_html__( 'Environmental', 'charitable' ),
					'events'             => esc_html__( 'Events', 'charitable' ),
					'family'             => esc_html__( 'Family', 'charitable' ),
					'legal'              => esc_html__( 'Legal', 'charitable' ),
					'medical'            => esc_html__( 'Medical', 'charitable' ),
					'political'          => esc_html__( 'Political', 'charitable' ),
					'special-occasions'  => esc_html__( 'Special Occasions', 'charitable' ),
					'sports'             => esc_html__( 'Sports', 'charitable' ),
					'youth'              => esc_html__( 'Youth', 'charitable' ),
				)
			);

			return $this->categories;
		}

		/**
		 * Get categories templates count.
		 *
		 * @since 1.8.0
		 *
		 * @return array
		 */
		private function get_count_in_categories() {

			$all_categories            = array();
			$available_templates_count = 0;
			$favorites_templates_count = 0;

			foreach ( $this->prepared_templates as $template_id => $template_data ) {

				$template_meta = $template_data['meta'];
				$categories    = $template_meta['categories'];

				if ( $template_meta['has_access'] ) {
					++$available_templates_count;
				}

				if ( $template_meta['favorite'] ) {
					++$favorites_templates_count;
				}

				if ( is_array( $categories ) ) {
					// loop through $categories and add to $all_categories.
					foreach ( $categories as $category ) {
						$all_categories[] = $category;
					}
					continue;
				}

				$all_categories[] = $categories;
			}

			$categories_count              = array_count_values( $all_categories );
			$categories_count['all']       = count( $this->prepared_templates );
			$categories_count['available'] = $available_templates_count;
			$categories_count['favorites'] = $favorites_templates_count;

			return $categories_count;
		}

		/**
		 * Retrieves the source template data for the campaign builder, which hopefully ends up cached.
		 *
		 * @access private
		 * @return array The source template data.
		 */
		public function get_source_template_data() {

			return array(
				'version'    => '1.0.0',
				'date_added' => time(),
				'templates'  => array(
					'simple-1-col'        => array(
						'meta'     => array(
							'label'         => 'Simple 1 Column',
							'slug'          => 'simple-1-column',
							'description'   => 'A simple one column campaign template to build from.',
							'template_type' => array( 'single', 'simple', 'blank' ),
							'search_tags'   => array(
								'default',
								'simple',
								'blank',
								'clean',
							),
							'categories'    => array(
								'default',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'preview'       => false, // show a "preview" button, default would be true.
							'colors'        => array(
								'primary'   => '#000000',
								'secondary' => '#2B66D1',
								'tertiary'  => '#F99E36',
								'accent'    => false,
								'button_bg' => '#5AA152',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p><strong>This is a description of your campaign.</strong></p><p>Write one or two short paragraphs about what you want to accomplish and perhaps brief of origin of this campaign, past campaigns, and feature key people and events that will enspire people to give to your cause.</p>',
							),
						),
					),
					'simple-2-col'        => array(
						'meta'     => array(
							'label'         => 'Simple 2 Column',
							'slug'          => 'simple-2-column',
							'description'   => 'A simple two column campaign template to build from.',
							'template_type' => array( 'single', 'simple', 'blank' ),
							'search_tags'   => array(
								'default',
								'simple',
								'blank',
								'clean',
							),
							'categories'    => array(
								'default',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'preview'       => false, // show a "preview" button, default would be true.
							'colors'        => array(
								'primary'   => '#000000',
								'secondary' => '#2B66D1',
								'tertiary'  => '#F99E36',
								'accent'    => false,
								'button_bg' => '#5AA152',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p><strong>This is a description of your campaign.</strong></p><p>Write one or two short paragraphs about what you want to accomplish and perhaps brief of origin of this campaign, past campaigns, and feature key people and events that will enspire people to give to your cause.</p>',
							),
						),
					),
					'simple-2-col-header' => array(
						'meta'     => array(
							'label'         => 'Simple 2 Column w/ header',
							'slug'          => 'simple-2-column-header',
							'description'   => 'A simple two column layout with a header.',
							'template_type' => array( 'single', 'simple', 'blank' ),
							'search_tags'   => array(
								'default',
								'simple',
								'blank',
								'clean',
							),
							'categories'    => array(
								'default',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'preview'       => false, // show a "preview" button, default would be true.
							'colors'        => array(
								'primary'   => '#000000',
								'secondary' => '#2B66D1',
								'tertiary'  => '#F99E36',
								'accent'    => false,
								'button_bg' => '#5AA152',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
						),
						'layout'   => array(
							array( // row.
								'type'      => 'header',
								'css_class' => 'charitable-header',
								'columns'   => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(),
										),
									),
								),
							),
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p><strong>This is a description of your campaign.</strong></p><p>Write one or two short paragraphs about what you want to accomplish and perhaps brief of origin of this campaign, past campaigns, and feature key people and events that will enspire people to give to your cause.</p>',
							),
						),
					),
					'youth-sports'        => array(
						'meta'     => array(
							'label'         => 'Youth Sports',
							'slug'          => 'youth-sports',
							'description'   => 'Accept donations for a local school\'s sports or athletics team.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'youth',
								'sports',
								'blue',
							),
							'categories'    => array(
								'sports',
								'youth',
								'special-occasions',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#102963',
								'secondary' => '#0D0D0D',
								'tertiary'  => '#989898',
								'accent'    => false,
								'button_bg' => '#ec5f25',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'photo.jpg',
												),
												array(
													'type' => 'campaign-summary',
												),
											),
										),
									),
									array(
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-title',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => true,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => ' ',
													'label_goal' => 'Goal USD: ',
													'meta_position' => 'top',
												),
												array(
													'type' => 'donate-amount',
													'headline' => 'Select An Amount',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'campaign-description',
															'headline' => 'Make every minute on the field count ⚾',
															'content' => '<p>We are announcing a campaign to bolster our local youth sports team, comprised of enthusiastic youngsters aged 8-12. Your support will enable us to update their uniforms, provide essential supplies, ensure a safe playing environment, and offer security measures on the field. Additionally, your contributions will go towards accommodations for parents and teachers, fostering a supportive community atmosphere where these young athletes can thrive. Join us in nurturing the next generation of athletes, empowering them with the resources and encouragement they need to excel both on and off the field.</p><p>Together, let\'s create a positive and inspiring environment for our youth, fostering a love for sports and teamwork that will last a lifetime.😄</p>',
														),
														array(
															'type' => 'ambassadors-team',
															'headline' => 'Top Team Members',
															'columns' => 6,
															'upgrade-img-bg' => 'field-team.jpg',
														),
													),
												),
												array(
													'title' => 'Updates',
													'type' => '',
													'slug' => 'updates',
													'fields' => array(
														'updates-main' => array(
															'headline' => 'Top Team Members',
															'columns' => 6,
														),
													),
												),
												array(
													'title' => 'Comments',
													'type' => '',
													'slug' => 'comments',
													'fields' => array(
														'comments-main' => array(
															'headline' => 'Top Team Members',
															'columns' => 6,
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'minimum',
							'tab_size'  => 'small',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>We are announcing a campaign to bolster our local youth sports team, comprised of enthusiastic youngsters aged 8-12. Your support will enable us to update their uniforms, provide essential supplies, ensure a safe playing environment, and offer security measures on the field. Additionally, your contributions will go towards accommodations for parents and teachers, fostering a supportive community atmosphere where these young athletes can thrive. Join us in nurturing the next generation of athletes, empowering them with the resources and encouragement they need to excel both on and off the field.</p><p>Together, let\'s create a positive and inspiring environment for our youth, fostering a love for sports and teamwork that will last a lifetime.😄</p>',
							),
						),
					),
					'school-trip'         => array(
						'meta'     => array(
							'label'         => 'School Trip',
							'slug'          => 'school-trip',
							'description'   => 'Raise funds for a school trip or fundraiser.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'youth',
								'sports',
								'green',
								'brown',
							),
							'categories'    => array(
								'sports',
								'youth',
								'events',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#7A8347',
								'secondary' => '#000000',
								'tertiary'  => '#5F5F5F',
								'accent'    => false,
								'button_bg' => '#7A8347',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(
								'social-links'   => '-white',
								'social-sharing' => '-white',
							),
						),
						'layout'   => array(
							array( // row.
								'type'      => 'header',
								'css_class' => 'charitable-header',
								'columns'   => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'text',
													'headline' => 'Help the youth learn with an adventure',
													'content' => 'Join us in giving our students the opportunity to explore, learn, and grow on this incredible journey!',
													'align' => 'center',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-description',
													'headline' => 'Raise fund for our school trip!',
													'content' => '<p>We are announcing a campaign to make the dream of a once-in-a-lifetime field trip come true for our graduating 5th graders: an unforgettable adventure in California! Your support will enable us to purchase necessary supplies, ensure safe travel, and provide accommodations for both parents and teachers accompanying the students. With your contribution, we can create lasting memories and valuable learning experiences, fostering a sense of camaraderie and curiosity that will stay with these young minds for a lifetime.</p>',
												),
												array(
													'type' => 'photo',
													'default' => 'trip-1.jpg',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'trip-2.jpg',
												),
												array(
													'type' => 'campaign-summary',
												),
												array(
													'type' => 'text',
													'headline' => 'Together, we can inspire these young minds and equip them with the tools they need to make a positive impact on the world.',
													'content' => 'Your generosity will not only provide the means for this adventure but will also contribute to the overall educational enrichment of our students. This trip isn\'t just a mere excursion; it\'s a chance for our 5th graders to expand their horizons, learn about different cultures, and engage with historical and natural wonders that will enhance their academic understanding. ',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => true,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => 'Funded: ',
													'label_goal' => 'Goal: ',
													'meta_position' => 'top',
												),
												array(
													'type' => 'social-links',
													'headline' => 'Follow on',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Donate Now',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'donation-form',
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliqui.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliqui.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>',
							),
						),
					),
					'animal-sanctuary'    => array(
						'meta'     => array(
							'label'         => 'Animal Sanctuary',
							'slug'          => 'animal-sanctuary',
							'description'   => 'Get funds for your mission to rescue or rehabilitate.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'animal-pets',
								'environmental',
								'blue',
							),
							'categories'    => array(
								'animal-pets',
								'environmental',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#805F93',
								'secondary' => '#1D1C1C',
								'tertiary'  => '#808080',
								'accent'    => false,
								'button_bg' => '#805F93',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(
								'social-sharing' => '-dark',
								'social-links'   => '-dark',
							),
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'photo-1.jpg',
												),
												array(
													'type' => 'campaign-description',
													'headline' => 'Donate today to support our mission to rescue, rehabilitate, and rehome',
													'content' => '<p>I\'m thrilled to launch our new campaign aimed at supporting an incredible cause: an animal sanctuary dedicated to rescuing abandoned and lost animals and finding them loving homes. With your help, we aim to raise funds to maintain this sanctuary, providing a safe haven for these adorable pets and ensuring they receive the care they deserve. Your contributions will not only help us sustain the facility but also enable us to actively seek new, caring families for these animals, giving them a chance at a brighter and happier future. Together, we can make a real difference in the lives of these innocent creatures.</p>',
												),
												array(
													'type' => 'campaign-summary',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'organizer',
													'role_or_title' => 'Organizer',
													'content' => '',
												),
												array(
													'type' => 'social-sharing',
													'headline' => 'Share Now',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
												),
												array(
													'type' => 'social-links',
													'headline' => 'Follow Now',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
													'instagram_url' => 'https://instagram.com/',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'text',
															'headline' => 'About Us',
															'content' => 'We are an animal santuary established in 2007.',
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>I\'m thrilled to launch our new campaign aimed at supporting an incredible cause: an animal sanctuary dedicated to rescuing abandoned and lost animals and finding them loving homes. With your help, we aim to raise funds to maintain this sanctuary, providing a safe haven for these adorable pets and ensuring they receive the care they deserve. Your contributions will not only help us sustain the facility but also enable us to actively seek new, caring families for these animals, giving them a chance at a brighter and happier future. Together, we can make a real difference in the lives of these innocent creatures.</p>',
							),
						),
					),
					'save-the-museum'     => array(
						'meta'     => array(
							'label'         => 'Save The Museum',
							'slug'          => 'save-the-museum',
							'description'   => 'Save a historical building, residence, or important landmark.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'legal',
								'special-occasions',
								'blue',
							),
							'categories'    => array(
								'legal',
								'club-organizations',
								'special-occasions',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#F58A07',
								'secondary' => '#1D3444',
								'tertiary'  => '#5B5B5B',
								'accent'    => false,
								'button_bg' => '#FFFFFF',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(
								'social-sharing' => '-dark',
								'social-links'   => '-dark',
							),
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'photo-1.jpg',
												),
												array(
													'type' => 'campaign-description',
													'headline' => 'About This Project',
													'content' => '<p>In 1918 the top floor of the Lyric Theatre was converted into a hospital to care for the sick and dying during the Spanish Flu Epidemic. A nurse, Mrs. Lapp, was forced to care for all of families upstairs. Today nearly 100 years later the top floor of the Lyric remains unused and it’s sole occupant is the spirit of Nurse Lapp. We are trying to save this abandoned building with the help of you all.</p>',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-title',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => false,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => 'Funded ',
													'label_goal' => 'Goal USD: ',
													'meta_position' => 'top',
												),
												array(
													'type' => 'campaign-summary',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
												),
												array(
													'type' => 'social-links',
													'headline' => 'Follow on',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'text',
															'headline' => 'Save the Lyrique Building',
															'content' => '<p>Mrs. Lapp\'s spirit is said to linger in the hushed corners of the Lyric Theatre, her presence felt by those who dare to venture to the top floor. Visitors claim to have heard soft whispers and felt a comforting presence, as if she\'s still watching over the space she once tended to with such dedication. The abandoned building stands as a testament to the resilience of the human spirit during times of crisis, and it has become a symbol of hope and survival. Efforts to preserve the Lyric Theatre are fueled by the desire to honor Mrs. Lapp\'s legacy and the countless lives she touched.</p><p>The community has rallied together, sharing stories of her bravery and compassion, inspiring a new generation to join the cause. With each donation and volunteer effort, the echoes of the past grow stronger, uniting people across time in a shared mission to save this historical landmark and the memory of Nurse Lapp, reminding us all of the power of compassion and community in the face of adversity.</p>',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-2.jpg',
														),
													),
												),
												array(
													'title' => 'Updates',
													'type' => '',
													'slug' => 'updates',
													'fields' => array(
														'updates-main' => array(
															'headline' => 'Top Team Members',
															'columns' => 6,
														),
													),
												),
												array(
													'title' => 'Comments',
													'type' => '',
													'slug' => 'comments',
													'fields' => array(
														'comments-main' => array(
															'headline' => 'Top Team Members',
															'columns' => 6,
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>In 1918 the top floor of the Lyric Theatre was converted into a hospital to care for the sick and dying during the Spanish Flu Epidemic. A nurse, Mrs. Lapp, was forced to care for all of families upstairs. Today nearly 100 years later the top floor of the Lyric remains unused and it’s sole occupant is the spirit of Nurse Lapp. We are trying to save this abandoned building with the help of you all.</p>',
							),
						),
					),
					'environmental'       => array(
						'meta'     => array(
							'label'         => 'Environmental',
							'slug'          => 'environmental',
							'description'   => 'Preserve nature and the earth for future generations.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'environmental',
								'animal-pets',
								'blue',
							),
							'categories'    => array(
								'environmental',
								'animal-pets',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#F9F6EE',
								'secondary' => '#24231E',
								'tertiary'  => '#61AA4F',
								'accent'    => false,
								'button_bg' => '#61AA4F',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(
								'social-sharing' => '-dark',
								'social-links'   => '-dark',
							),
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'photo-1.jpg',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'text',
													'headline' => 'Save Earth',
													'content' => 'Together, let\'s reclaim the serenity of our shorelines and ensure a cleaner, greener future for generations to come.',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'campaign-description',
													'headline' => 'Help Preserving The Earth For Future Generations',
													'content' => '<p>We are announcing a campaign dedicated to restoring the natural beauty of our local beach, which has sadly fallen victim to years of neglect and pollution. Our non-profit is on a mission to raise funds for cleaning supplies, vehicles, and essential costs to orchestrate a massive cleanup effort. By contributing, you\'re not just supporting a cleaner beach but also promoting environmental health and community pride. </p>',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'campaign-summary',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'social-links',
													'headline' => ' ',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
													'align' => 'left',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'text',
															'headline' => 'Keep the scene green by taking the lead',
															'content' => 'Your support will make a significant impact, enabling us to organize community clean-up events where volunteers, equipped with the necessary resources, can work together to remove garbage and restore the beach to its natural state. Additionally, your contributions will empower us to implement educational programs aimed at raising awareness about the importance of environmental conservation. We believe that through collective action, we can not only clean up our beloved beach but also inspire a lasting change in our community\'s attitudes towards environmental responsibility. Every donation, no matter how big or small, brings us one step closer to a cleaner, healthier environment for everyone. Join us in this vital endeavor, and let\'s create a positive ripple effect that benefits both our local ecosystem and the people who call this community home.',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-2.jpg',
															'align' => 'center',
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>We are announcing a campaign dedicated to restoring the natural beauty of our local beach, which has sadly fallen victim to years of neglect and pollution. Our non-profit is on a mission to raise funds for cleaning supplies, vehicles, and essential costs to orchestrate a massive cleanup effort. By contributing, you\'re not just supporting a cleaner beach but also promoting environmental health and community pride.</p>',
							),
						),
					),
					'disaster-relief'     => array(
						'meta'     => array(
							'label'         => 'Disaster Relief',
							'slug'          => 'disaster-relief',
							'description'   => 'Help those effected by natural disasters.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'environmental',
								'medical',
								'family',
								'blue',
							),
							'categories'    => array(
								'environmental',
								'medical',
								'family',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#9F190E',
								'secondary' => '#202020',
								'tertiary'  => '#FFFFFF',
								'accent'    => false,
								'button_bg' => '#9F190E',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(),
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'photo-1.jpg',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => true,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => ' ',
													'label_goal' => 'Goal USD: ',
													'meta_position' => 'top',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'text',
													'headline' => 'Donate Today',
													'content' => 'This project will only be funded if at least $270,000 is raised by December 22, 2025.',
												),
												array(
													'type' => 'campaign-summary',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
													'width_percentage' => 100,
												),
												array(
													'type' => 'social-sharing',
													'headline' => 'Share',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
													'align' => 'left',
												),
												array(
													'type' => 'social-links',
													'headline' => 'Follow on',
													'facebook_url' => 'https://facebook.com/',
													'twitter_url' => 'https://twitter.com/',
													'align' => 'left',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'campaign-description',
															'headline' => 'About This Campaign',
															'content' => '<p>We are announcing a new campaign aimed at providing crucial support to the victims of a recent devastating natural disaster. Together, we are rallying behind our fellow community members, raising funds through this non-profit initiative to help them rebuild homes, restore essential services, and reclaim their lives. Join us in making a difference, as every contribution brings us one step closer to bringing hope and stability back to those in need..</p>',
															'align' => 'left',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-2.jpg',
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>We are announcing a new campaign aimed at providing crucial support to the victims of a recent devastating natural disaster. Together, we are rallying behind our fellow community members, raising funds through this non-profit initiative to help them rebuild homes, restore essential services, and reclaim their lives. Join us in making a difference, as every contribution brings us one step closer to bringing hope and stability back to those in need.</p>',
							),
						),
					),
					'club-organization'   => array(
						'meta'     => array(
							'label'         => 'Club / Organization',
							'slug'          => 'club-organization',
							'description'   => 'Fund programs or help increase funds for a worthy organization.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'club-organizations',
								'school',
								'blue',
							),
							'categories'    => array(
								'club-organizations',
								'school',
								'sports',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#474735',
								'secondary' => '#B1A17C',
								'tertiary'  => '#F4F0EE',
								'accent'    => false,
								'button_bg' => '#B49A5F',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(),
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'photo',
													'default' => 'photo-1.jpg',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-description',
													'headline' => 'About This Club',
													'content' => '<p>We are announcing a campaign to support our cherished local country club, a haven for our community, especially our elderly residents. Our non-profit is passionately rallying for funds to ensure the maintenance of this vital space and to sustain the heartwarming annual events that bring together generations. By contributing, you\'re preserving a beloved institution that unites the young and the old, fostering a sense of belonging and community spirit. Join us in safeguarding this haven for all ages!</p>',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => true,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => ' ',
													'label_goal' => 'Goal USD: ',
													'meta_position' => 'top',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'campaign-summary',
													'width_percentage' => 100,
													'align' => 'left',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
													'width_percentage' => 40,
													'align' => 'center',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type'     => 'text',
															'headline' => 'With 100% of donations going towards grants and programs',
															'content'  => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliqui.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-2.jpg',
														),
													),
												),
												array(
													'title' => 'Updates',
													'type' => '',
													'slug' => 'updates',
													'fields' => array(
														array(
															'type' => 'updates-main',
															'headline' => 'Top Team Members',
															'columns' => 6,
														),
													),
												),
												array(
													'title' => 'Comments',
													'type' => '',
													'slug' => 'comments',
													'fields' => array(
														array(
															'type' => 'comments-main',
															'headline' => 'Top Team Members',
															'columns' => 6,
														),
													),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>We are announcing a campaign to support our cherished local country club, a haven for our community, especially our elderly residents. Our non-profit is passionately rallying for funds to ensure the maintenance of this vital space and to sustain the heartwarming annual events that bring together generations. By contributing, you\'re preserving a beloved institution that unites the young and the old, fostering a sense of belonging and community spirit. Join us in safeguarding this haven for all ages!</p>',
							),
						),
					),
					'medical-causes'      => array(
						'meta'     => array(
							'label'         => 'Medical Causes',
							'slug'          => 'medical-causes',
							'description'   => 'Have a goal for more research, better treatment or a cure.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'medical',
								'family',
								'blue',
							),
							'categories'    => array(
								'medical',
								'family',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#192E45',
								'secondary' => '#215DB7',
								'tertiary'  => '#48A9F5',
								'accent'    => false,
								'button_bg' => '#48A9F5',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
							'suffixes'      => array(),
						),
						'layout'   => array(
							array( // row.
								'type'    => 'row',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'video',
													'headline' => '',
													'upgrade-img-bg' => 'field-charitable-videos.jpg',
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-title',
												),
												array(
													'type' => 'text',
													'headline' => 'Cancer Research Program',
													'content' => 'By donating to this cause, you can improve the lives of a variety of different people in your community and feel good about doing it.',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => true,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => ' ',
													'label_goal' => 'Goal USD: ',
													'meta_position' => 'top',
												),
												array(
													'type' => 'campaign-summary',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
													'align' => 'left',
													'width_percentage' => 50,
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'overview',
													'fields' => array(
														array(
															'type' => 'campaign-description',
															'headline' => 'About Our Organization',
															'content' => '<p>We are announcing a campaign dedicated to supporting our local hospital\'s crucial cancer research initiatives, focusing on children under 18, women, and minority communities. Our non-profit is passionately rallying for funds to maintain this specialized facility and sustain popular fundraisers accessible to our community. By contributing, you\'re directly investing in cutting-edge research, ensuring that young lives are given a fighting chance, and addressing healthcare disparities. Join us in this noble cause, as together, we strive to create a healthier future for our youth, women, and minority members.</p>',
															'width_percentage' => 100,
															'align' => 'left',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-1.jpg',
														),
														array(
															'type' => 'text',
															'headline' => 'Thinking differently is a proven strategy for changing the world',
															'content' => '<p>Your support will not only fund vital research but also provide essential resources for specialized treatments, counseling, and support services tailored to the unique needs of young patients, women, and minorities battling cancer. By contributing to this campaign, you\'re helping us bridge the gaps in healthcare access, ensuring that everyone, regardless of age, gender, or ethnicity, receives the best possible care and support during their challenging journey.</p><p>Together, we can make a significant difference in the lives of those affected by cancer, offering them hope, strength, and the promise of a brighter, healthier tomorrow. Join us in our fight against cancer, and let\'s create a community where every individual receives the care and compassion they deserve.</p>',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-2.jpg',
														),
													),
												),
												array(
													'title' => 'Comments',
													'type' => '',
													'slug' => 'comments',
													'fields' => array(),
												),
												array(
													'title' => 'Reviews',
													'type' => '',
													'slug' => 'reviews',
													'fields' => array(),
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>We are announcing a campaign dedicated to supporting our local hospital\'s crucial cancer research initiatives, focusing on children under 18, women, and minority communities. Our non-profit is passionately rallying for funds to maintain this specialized facility and sustain popular fundraisers accessible to our community. By contributing, you\'re directly investing in cutting-edge research, ensuring that young lives are given a fighting chance, and addressing healthcare disparities. Join us in this noble cause, as together, we strive to create a healthier future for our youth, women, and minority members.</p>',
							),
						),
					),
					'medical-bills'       => array(
						'meta'     => array(
							'label'         => 'Medical Bills',
							'slug'          => 'medical-bills',
							'description'   => 'Raise funds to devote towards medical treatment of a friend or family member.',
							'template_type' => array( 'single', 'prebuilt' ),
							'search_tags'   => array(
								'medical',
								'family',
								'blue',
							),
							'categories'    => array(
								'medical',
								'family',
							),
							'created'       => '2023-10-05', // Y-m-d.
							'version'       => '1.0',
							'author'        => 'David Bisset',
							'dependencies'  => false, // not used yet.
							'has_access'    => true, // not used yet, but false if the user can't select it.
							'favorite'      => false,
							'colors'        => array(
								'primary'   => '#5C8AF3',
								'secondary' => '#21458F',
								'tertiary'  => '#F7F7F7',
								'accent'    => false,
								'button_bg' => '#5C8AF3',
							),
							'parent_theme'  => false, // not used yet.
							'thumbnail_url' => false, // overrides default location to look for thumbnails in template page.
						),
						'layout'   => array(
							array( // row.
								'type'    => 'header',
								'columns' => array(
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-title',
													'align' => 'center',
												),
											),
										),
									),
								),
							),
							array( // row.
								'type'      => 'row',
								'css_class' => '', // 'css_class' => 'no-padding no-field-target no-field-wrap',
								'columns'   => array(
									array( // column.
										array( // section.
											'type' => 'tabs',
											'tabs' => array(
												array(
													'title' => 'Overview',
													'type' => '',
													'slug' => 'default',
													'fields' => array(
														array(
															'type' => 'campaign-description',
															'headline' => '',
															'content' => '<p>On June 12, 2023 Brook’s wife Maleena was admitted to hospital while 20 weeks pregnant, where she was diagnosed with Stage 4 B-cell Lymphoma. At just 22 years old, she has been confronted with a life-altering diagnosis and is now facing a grueling fight. After experiencing inflammation and pain in her legs in March, Brooke spoke with her GP and sought treatment from chiropractors and physios.</p>',
															'width_percentage' => 100,
															'align' => 'left',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-2.jpg',
														),
														array(
															'type' => 'text',
															'headline' => ' ',
															'content' => 'Devastated by Maleena\'s diagnosis, Brooke found himself navigating a whirlwind of emotions and responsibilities. He became Maleena\'s unwavering pillar of support, attending every doctor\'s appointment and chemotherapy session with her, his heart aching at the sight of his young wife enduring such pain. The couple, once planning nursery decorations and baby names, now found themselves discussing treatment options and potential outcomes. Despite the overwhelming fear and uncertainty, they clung to each other, drawing strength from their love and the life growing inside Maleena. Friends and family rallied around them, offering words of encouragement and practical help, reminding them that they were not alone in this battle. In the face of adversity, Brooke and Maleena vowed to fight together, holding onto hope and cherishing every moment they had together, knowing that their love would be the guiding light in the darkest of times.',
														),
														array(
															'type' => 'photo',
															'default' => 'photo-1.jpg',
														),
													),
												),
												array(
													'title' => 'Updates',
													'type' => '',
													'slug' => 'updates',
													'fields' => array(
														array(
															'type' => 'text',
															'headline' => ' ',
															'content' => 'This is on second tab.',
														),
													),
												),
												array(
													'title' => 'Comments',
													'type' => '',
													'slug' => 'comments',
													'fields' => array(
														array(
															'type' => 'text',
															'headline' => ' ',
															'content' => 'This is on third tab.',
														),
													),
												),
											),
										),
									),
									array( // column.
										array( // section.
											'type'   => 'fields',
											'fields' => array(
												array(
													'type' => 'campaign-summary',
												),
												array(
													'type' => 'progress-bar',
													'show_hide' => array(
														'show_donated' => true,
														'show_goal' => true,
													),
													'css_class' => 'sample-css-class',
													'label_donate' => ' ',
													'label_goal' => 'Goal USD: ',
													'meta_position' => 'top',
												),
												array(
													'type' => 'text',
													'headline' => ' ',
													'content' => 'This project will only be funded if at least $270,000 is raised by December 22, 2025.',
												),
												array(
													'type' => 'donate-button',
													'button_label' => 'Donate Now',
													'width_percentage' => 75,
													'align' => 'center',
												),
												array(
													'type' => 'organizer',
													'role_or_title' => 'Organizer',
													'content' => '',
												),
											),
										),
									),
								),
							),
						),
						'advanced' => array(
							'tab_style' => 'boxed',
							'tab_size'  => 'medium',
						),
						'settings' => array(
							'general' => array(
								'description' => '<p>On June 12, 2023 Brook’s wife Maleena was admitted to hospital while 20 weeks pregnant, where she was diagnosed with Stage 4 B-cell Lymphoma. At just 22 years old, she has been confronted with a life-altering diagnosis and is now facing a grueling fight. After experiencing inflammation and pain in her legs in March, Brooke spoke with her GP and sought treatment from chiropractors and physios.</p>',
							),
						),
					),

				),
			);
		}

		/**
		 * Template for upgrade banner.
		 *
		 * @since 1.8.0
		 */
		public function upgrade_banner_template() {

			if ( charitable_is_pro() ) {
				return;
			}

			$medium = esc_html__( 'Campaign Builder Templates', 'charitable' );

			?>
			<script type="text/html" id="tmpl-charitable-templates-upgrade-banner">
				<div class="charitable-template-upgrade-banner">
					<div class="charitable-template-content">
						<h3>
							<?php
							printf( esc_html__( 'Super Charge Your Campaigns And Donations With Pro Addons!', 'charitable' ) );
							?>
						</h3>

						<p>
							<?php
							// translators: %s is the amount saved.
							printf( esc_html__( 'With Charitable addons you can enable crowdfunding, provide additional payment methods, add videos, offer recurring donations, and more. As thanks to you for being a lite user you can unlock access and save up to $%s, automatically applied at checkout! ', 'charitable' ), '<strong>300.00</strong>' );
							?>
						</p>
					</div>
					<div class="charitable-template-upgrade-button">
						<a href="<?php echo esc_url( charitable_pro_upgrade_url( $medium ) ); ?>" class="charitable-btn charitable-btn-orange charitable-btn-md" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to PRO', 'charitable' ); ?></a>
					</div>
				</div>
			</script>
			<?php
		}

		/**
		 * Tab Empty Notice.
		 *
		 * @since 1.8.0
		 */
		public function tab_empty_notice() {

			return sprintf(
				/* translators: 1: Template ID */
				'<p class="empty-tab-notice">' . __( 'This tab is empty. Drag a block from the left into this area or ', 'charitable' ) . '<br/><strong><a href="%1$s" class="charitable-configure-tab-settings">' . __( 'configure tab settings', 'charitable' ) . '</a></strong>.</p>',
				'#'
			);
		}

		/**
		 * No Empty Notice.
		 *
		 * @since 1.8.0
		 */
		public function no_tab_empty_notice() {

			return sprintf(
			/* translators: 1: Template ID */
				__( 'There are no tabs yet for this template. You can ', 'charitable' ) . '<br/><strong><a href="%1$s" class="charitable-configure-tab-settings">' . __( 'configure tab settings', 'charitable' ) . '</a> to add a new tab</strong>.',
				'#'
			);
		}
	}

endif;

new Charitable_Campaign_Builder_Templates();
