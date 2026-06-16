<?php
/**
 * Design class management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Design' ) ) :

	/**
	 * Design class management panel.
	 *
	 * @package   Charitable
	 * @author    David Bisset
	 * @copyright Copyright (c) 2023, WP Charitable LLC
	 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since     1.8.0
	 * @version   1.8.0
	 */
	class Charitable_Builder_Panel_Design extends Charitable_Builder_Panel {

		/**
		 * Form data and settings.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * Template addon labels data.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		private $addon_labels;

		/**
		 * All systems go.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define panel information.
			$this->name    = esc_html__( 'Design', 'charitable' );
			$this->slug    = 'design';
			$this->icon    = 'panel_design.svg';
			$this->order   = 20;
			$this->sidebar = true;

			add_action( 'charitable_builder_fields', [ $this, 'fields' ] );
			add_action( 'charitable_builder_fields_options', [ $this, 'fields_options' ] );
			add_action( 'charitable_builder_preview', [ $this, 'preview' ] );

			// Template for Campaign builder previews.
			add_action( 'charitable_builder_print_footer_scripts', [ $this, 'design_preview_templates' ] );
		}

		/**
		 * Enqueue assets for the Design panel.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {

			$min = charitable_get_min_suffix();

			wp_enqueue_script(
				'charitable-builder-drag-fields',
				charitable()->get_path( 'directory', false ) . "assets/js/campaign-builder/drag-fields{$min}.js",
				[ 'charitable-builder' ],
				charitable()->get_version(),
				true
			);
		}

		/**
		 * Output the Field panel sidebar.
		 *
		 * @since 1.8.0
		 */
		public function panel_sidebar() {

			// This should never be called unless we are on the campaign builder page.
			if ( ! campaign_is_campaign_builder_admin_page() ) {
				return;
			}
			?>
		<ul class="charitable-tabs charitable-clear">

			<li class="charitable-tab" id="add-layout">
				<a href="#" class="active">
					<?php esc_html_e( 'Add Layout', 'charitable' ); ?>
				</a>
			</li>

			<li class="charitable-tab" id="layout-options">
				<a href="#">
					<?php esc_html_e( 'Layout Options', 'charitable' ); ?>
				</a>
			</li>

		</ul>


			<?php

			$display_tour_divs = Charitable_Campaign_Builder::get_instance()->display_tour_divs();

			if ( $display_tour_divs ) :
				?>

				<div id="charitable-tour-block-1" class="charitable-tour-block"></div>
				<div id="charitable-tour-block-2" class="charitable-tour-block"></div>
				<div id="charitable-tour-block-3" class="charitable-tour-block"></div>

			<?php endif; ?>

		<div class="charitable-add-fields charitable-tab-content">
			<?php do_action( 'charitable_builder_fields', $this->campaign_data ); ?>
		</div>

		<div id="charitable-field-options" class="charitable-field-options charitable-tab-content">
			<?php do_action( 'charitable_builder_fields_options', $this->campaign_data ); ?>
		</div>

			<?php
		}

		/**
		 * Output the Field panel primary content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {

			// todo: think if there is campaign data to load if this is a brand new campaign?

			$template_id    = isset( $this->campaign_data['template_id'] ) ? esc_attr( $this->campaign_data['template_id'] ) : charitable_campaign_builder_default_template();
			$campaign_id    = isset( $this->campaign_data['campaign_id'] ) ? esc_attr( $this->campaign_data['campaign_id'] ) : false;
			$campaign_title = isset( $this->campaign_data['title'] ) ? esc_html( $this->campaign_data['title'] ) : esc_html( $this->campaign->post_title );

			$builder_template = new Charitable_Campaign_Builder_Templates();
			$template_data    = $builder_template->get_template_data( $template_id );

			/* get status */
			$campaign = $campaign_id ? charitable_get_campaign( $campaign_id ) : false;
			$status   = $campaign ? $campaign->get_status() : false;
			if ( 'finished' === $status && $campaign->has_goal() ) {
				$status = $campaign->has_achieved_goal() ? 'successful' : 'unsuccessful';
			}
			$campaign_status = $campaign ? '<mark class="charitable-builder-status-' . esc_attr( $status ) . '">' . $status . '</mark>' : false;

			/* establish any classes to add to preview wrap upon load */
			$preview_wrap_classes = array( 'charitable-preview-wrap' );
			if ( ! empty( $this->campaign_data['layout']['advanced']['show_field_names'] ) && 'hide' === $this->campaign_data['layout']['advanced']['show_field_names'] ) {
				$preview_wrap_classes[] = 'charitable-preview-hide-field-names';
			}
			if ( ! empty( $this->campaign_data['layout']['advanced']['preview_mode'] ) && 'minimum' === $this->campaign_data['layout']['advanced']['preview_mode'] ) {
				$preview_wrap_classes[] = 'charitable-preview-minimum-preview';
			}
			$preview_wrap_classes = trim( implode( ' ', $preview_wrap_classes ) );
			?>

		<div class="<?php echo esc_attr( $preview_wrap_classes ); ?>">

			<div class="charitable-preview charitable-builder-template-<?php echo esc_attr( $template_id ); ?>">

				<div class="charitable-preview-top-bar">
					<div class="charitable-preview-top-bar-inner-left">
						<h2 class="charitable-form-name">
							<?php echo esc_html( stripslashes( $campaign_title ) ); ?>
						</h2>
					</div>
					<div class="charitable-preview-top-bar-inner-right">
							<?php if ( 0 !== intval( $campaign_id ) && $campaign_status && 'draft' !== $campaign_status ) : ?>
							<span class="charitable-view-campaign-external-link"><a href="<?php echo esc_url( get_permalink( $campaign_id ) ); ?>" target="_blank" title="View Campaign Page"><?php /* <i class="fa fa-eye"></i> */ ?></a></span>
						<?php endif; ?>
					</div>
				</div>

				<div class="charitable-no-design-holder charitable-hidden"></div>

				<div class="charitable-design-wrap" id="charitable-design-wrap">
						<?php do_action( 'charitable_builder_preview', $this->campaign ); ?>
				</div>

					<?php
					// This action is documented in includes/class-frontend.php.
					do_action( 'charitable_display_submit_after', $this->campaign_data, 'submit' ); // phpcs:ignore Charitable.PHP.ValidateHooks.InvalidHookName

					?>

					<?php charitable_builder_debug_data( $this->campaign_data ); ?>
			</div>

		</div>

			<?php
		}

		/**
		 * Builder field buttons.
		 *
		 * @since 1.8.0
		 * @param array $campaign_data Campaign Data.
		 */
		public function fields( $campaign_data = false ) {

			$fields = [
				'recommended' => [
					'group_name'  => esc_html__( 'Recommended', 'charitable' ),
					'description' => false,
					'fields'      => [],
				],
				'standard'    => [
					'group_name'  => esc_html__( 'Standard', 'charitable' ),
					'description' => false,
					'fields'      => [],
				],
				'pro'         => [
					'group_name'  => esc_html__( 'Pro', 'charitable' ),
					'description' => false,
					'fields'      => [],
				],
			];

			/**
			 * Allows developers to modify content of the the Add Field tab.
			 *
			 * With this filter developers can add their own fields or even fields groups.
			 *
			 * @since 1.8.0
			 *
			 * @param array $fields {
			 *     Design data multidimensional array.
			 *
			 *     @param array $recommended Recommended fields group.
			 *         @param string $group_name Group name.
			 *         @param array  $fields     Design array.
			 *
			 *     @param array $standard Standard fields group.
			 *         @param string $group_name Group name.
			 *         @param array  $fields     Design array.
			 *
			 *     @param array $pro    Pro fields group.
			 *         @param string $group_name Group name.
			 *         @param array  $fields     Design array.
			 * }
			 */

			$fields = apply_filters( 'charitable_builder_design_buttons', $fields );

			// If there is campaign data, make a list of fields already in use which might determine what CSS classes (etC) gets passed to the buttons on the left.
			$fields_in_template = array();
			if ( false !== $campaign_data ) {
				if ( ! empty( $campaign_data['layout']['rows'] ) ) {
					foreach ( $campaign_data['layout']['rows'] as $row ) {
						if ( ! empty( $row['fields'] ) ) {
							$fields_in_template = array_merge( $fields_in_template, $row['fields'] );
						}
					}
				}
			}

			$donation_form_display = charitable_get_option( 'donation_form_display' );

			// Output the buttons.
			foreach ( $fields as $id => $group ) {

				usort( $group['fields'], [ $this, 'field_order' ] );

				echo '<div class="charitable-add-fields-group charitable-add-fields-group-' . esc_attr( $id ) . '">';

				echo '<a href="#" class="charitable-add-fields-heading charitable-add-fields-heading-' . esc_attr( $id ) . '" data-group="' . esc_attr( $id ) . '">';

				echo '<span>' . esc_html( $group['group_name'] ) . '</span>';

				if ( $group['description'] ) :
					echo '<span class="charitable-group-description"><small>' . esc_html( $group['description'] ) . '</small></span>';
				endif;

				echo '<i class="fa fa-angle-down charitable-toggleable-group"></i>';

				echo '</a>';

				echo '<div class="charitable-group-rows"><div class="charitable-add-fields-buttons">';

				foreach ( $group['fields'] as $field ) {

					// If we are loading an already established campaign template, then the 'is the add button disabled' needs/should try to happen in the PHP rendering side.
					$add_field_button_classes = array( 'charitable-add-fields-button' );
					$maybe_disable_add_button = $this->maybe_disable_add_button( $field['type'], $fields_in_template, $campaign_data, $donation_form_display );

					if ( ! empty( $campaign_data ) && false !== $maybe_disable_add_button ) {
						$add_field_button_classes[] = 'charitable-disabled';

						if ( strlen( $maybe_disable_add_button ) > 1 ) {
							$add_field_button_classes[] = 'charitable-disabled' . $maybe_disable_add_button;
						}
					}

					$add_field_button_classes[] = 'charitable-has-field';

					/**
					 * Attributes of the form field button on the Add Design tab in the Campaign Builder.
					 *
					 * @since 1.8.0
					 *
					 * @param array $attributes Field attributes.
					 * @param array $field      Field data.
					 * @param array $form_data  Form data.
					 */
					$atts = apply_filters(
						'charitable_builder_field_button_attributes',
						[
							'id'          => 'charitable-add-fields-' . $field['type'],
							'class'       => $add_field_button_classes,
							'data'        => [
								'field-type' => $field['type'],
								'field-icon' => $field['icon'],
							],
							'font-prefix' => 'fa',
							'atts'        => [],
						],
						$field,
						$this->campaign_data,
						$fields_in_template
					);

					if ( ! empty( $field['class'] ) ) {
						$atts['class'][] = $field['class'];
					}

					echo '<div class="charitable-add-fields-button-wrap">';

					// Deal with recommended checkmarks.
					if ( 'recommended' === $id ) {
						$checked_or_not = ( 'recommended' === $id && in_array( $field['type'], $fields_in_template, true ) ) ? 'checked' : 'unchecked';
						echo '<div class="charitable-check ' . $checked_or_not . '"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					echo '<button ' . charitable_html_attributes( $atts['id'], $atts['class'], $atts['data'], $atts['atts'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $field['icon'] ) {
						echo '<i class="' . esc_attr( $atts['font-prefix'] ) . ' ' . esc_attr( $field['icon'] ) . '"></i> ';
					}
					echo esc_html( $field['name'] );
					echo '</button>';

					echo '</div>';
				}

				echo '</div>
				</div>';

				echo '</div>';
			}
		}

		/**
		 * Determine if we need to disable an add field button in the builder (left).
		 *
		 * @since 1.8.0
		 *
		 * @param string $button_type The type of button to check.
		 * @param bool   $fields_in_template Whether the fields are in the template.
		 * @param bool   $campaign_data The campaign data.
		 * @param bool   $donation_form_display Whether the donation form is displayed.
		 */
		public function maybe_disable_add_button( $button_type = '', $fields_in_template = false, $campaign_data = false, $donation_form_display = false ) {

			if ( '' === trim( $button_type ) || false === $fields_in_template ) {
				return false;
			}

			// If "modal" is set to true and the field is a form or donate amount, immedately reject because these elements shouldn't be available when the donation form is a modal.
			if ( 'modal' === $donation_form_display ) {
				if ( $button_type === 'donation-form' || $button_type === 'donate-amount' ) {
					return '-modal';
				}
			} elseif ( 'same_page' === $donation_form_display ) {
				if ( $button_type === 'donation-form' || $button_type === 'donate-amount' || $button_type === 'donate-button' ) {
					return '-same_page';
				}
			}

			// check the field deny list.
			$deny_list = array(
				'donation-form' => array(
					'donate-button' => 0,
					'donation-form' => 0,
					'donate-amount' => 0,
				),
				'donate-button' => array(
					'donation-form' => 0,
				),
				'donate-amount' => array(
					'donation-form' => 0,
				),
			);

			// for example: donate-amount should be enabled unless donation form is present.
			foreach ( $deny_list as $field_exists => $fields_to_check ) {
				foreach ( $fields_to_check as $field_to_check => $field_amount_limit ) {
					// if the key of the sub array isn't 'donate-amount' skip this.
					if ( $field_to_check !== $button_type ) {
						continue;
					}

					// ok, we found 'donate-amount' but 'donation-form' doesn't exist, so skip this.
					if ( ! in_array( $field_exists, $fields_in_template, true ) ) {
						continue;
					}

					$vals = array_count_values( $fields_in_template );

					// ok if donation form exists determine how many 'donate-amount' are allowed to exist before disabling the add field in the builder.
					if ( isset( $vals[ $field_to_check ] ) && $field_amount_limit <= $vals[ $field_to_check ] ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Editor Field Options.
		 *
		 * @since 1.8.0
		 */
		public function fields_options() {

			// Check to make sure the form actually has fields created already.
			if ( empty( $this->campaign_data['design'] ) ) {
				$this->general_design_options();

				return;
			}

			$fields = $this->campaign_data['design'];

			foreach ( $fields as $field ) {

				$class = apply_filters( 'charitable_builder_field_option_class', '', $field );

				printf( '<div class="charitable-design-option charitable-design-option-%s %s" id="charitable-design-option-%d" data-design-id="%d">', sanitize_html_class( $field['type'] ), charitable_sanitize_classes( $class ), (int) $field['id'], (int) $field['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				printf( '<input type="hidden" name="fields[%d][id]" value="%d" class="charitable-design-option-hidden-id">', (int) $field['id'], (int) $field['id'] );

				printf( '<input type="hidden" name="fields[%d][type]" value="%s" class="charitable-design-option-hidden-type">', (int) $field['id'], esc_attr( $field['type'] ) );

				do_action( "charitable_builder_design_options_{$field['type']}", $field );

				echo '</div>';
			}
		}

		/**
		 * Generate HTML for hidden inputs from given data.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $data Field array data.
		 * @param string $name Input name prefix.
		 */
		private function generate_hidden_inputs( $data = [], $name = '' ) {

			if ( ! is_array( $data ) || empty( $data ) ) {
				return;
			}

			foreach ( $data as $key => $value ) {
				if ( $key === 'id' ) {
					continue;
				}

				$key = ! empty( $data['id'] ) ? sprintf( '[%s][%s]', $data['id'], $key ) : sprintf( '[%s]', $key );

				if ( ! empty( $name ) ) {
					$key = trim( $name ) . $key;
				}

				if ( is_array( $value ) ) {
					$this->generate_hidden_inputs( $value, $key );
				} else {
					printf( "<input type='hidden' name='%s' value='%s' />", esc_attr( $key ), esc_attr( $value ) );
				}
			}
		}

		/**
		 * No fields options markup.
		 *
		 * @since 1.6.0
		 */
		public function no_design_options() {

			printf(
				'<p class="no-design charitable-alert charitable-alert-warning">%s</p>',
				esc_html__( 'You don\'t have any fields yet.', 'charitable' )
			);
		}

		/**
		 * No fields preview placeholder markup.
		 *
		 * @since 1.8.0
		 */
		public function no_design_preview() {
		}

		/**
		 * No fields options markup.
		 *
		 * @since 1.8.0
		 */
		public function general_design_options() {

			// layout options
			// -> general.
			// -> advanced.

			$tabs                 = isset( $this->campaign_data['tabs'] ) ? $this->campaign_data['tabs'] : false;
			$max_tab_title_length = apply_filters( 'charitable_builder_design_tab_title_length', 20 );

			?>

		<!-- sub tab -->
		<div class="charitable-layout-options-tab charitable-layout-options-tab-general active" id="">
			<a href="#" class="charitable-group-toggle charitable-layout-options-group-toggle"><?php echo esc_html__( 'General', 'charitable' ); ?></a>
			<!-- container -->
			<div class="charitable-layout-options-group-inner">

				<div class="charitable-select-field-notice"><?php echo esc_html__( 'Select a field in the preview area to set it\'s settings.', 'charitable' ); ?></div>
				<?php

				if ( ! empty( $this->campaign_data['layout']['rows'] ) ) :

					foreach ( $this->campaign_data['layout']['rows'] as $row_id => $row ) :

						// header.
						if ( isset( $row['fields'] ) ) {

							$row_type = $row['type'];

							if ( 'row' === $row_type || 'header' === $row_type ) {

								foreach ( $row['fields'] as $field_id => $field_type ) {

									$type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

									if ( class_exists( $type ) ) :
										$class = new $type();
										if ( method_exists( $class, 'settings_display' ) ) {
											echo $class->settings_display( $field_id, $this->campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										endif;
								}
							} elseif ( 'tabs' === $row_type ) {

								foreach ( $row['tabs'] as $tab_id => $tab ) {

									foreach ( $tab as $field_id_key => $field_id ) {

										$field_type = $this->campaign_data['layout']['rows'][ $row_id ]['fields'][ $field_id ];

										$type = 'Charitable_Field_' . str_replace( ' ', '_', ( ucwords( str_replace( '-', ' ', $field_type ) ) ) );

										if ( class_exists( $type ) ) :
											$class = new $type();
											if ( method_exists( $class, 'settings_display' ) ) {
												echo $class->settings_display( $field_id, $this->campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											}
											endif;
									}
								}
							}
						}

						endforeach;

					endif;
				?>
			</div>
		</div> <!-- end tab -->

		<!-- sub tab -->
		<div class="charitable-layout-options-tab charitable-layout-options-tab-tabs" id="">
			<a href="#" class="charitable-group-toggle charitable-layout-options-tabs-toggle"><?php echo esc_html__( 'Tabs', 'charitable' ); ?></a>
			<!-- container -->
			<div class="charitable-layout-options-group-inner class-design">

				<?php $enable_tabs = isset( $this->campaign_data['layout']['advanced']['enable_tabs'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['enable_tabs'] ) ? $this->campaign_data['layout']['advanced']['enable_tabs'] : 'enabled'; ?>

				<div data-field-id="" id="charitable-group-row-design-layout-options-tabs-enable" class="charitable-panel-field charitable-panel-field-toggle " data-ajax-label="enable_tabs">
					<span class="charitable-toggle-control">
						<input type="checkbox" id="charitable-panel-field-settings-charitable-campaign-enable-tabs" name="layout__advanced__enable_tabs" data-advanced-field-id="enable_tabs" value="disabled" <?php checked( $enable_tabs, 'disabled' ); ?> />
						<label class="charitable-toggle-control-icon" for="charitable-panel-field-settings-charitable-campaign-enable-tabs"></label>
						<label for="charitable-panel-field-settings-charitable-campaign-enable-tabs"><?php echo esc_html__( 'Hide All Tabs.', 'charitable' ); ?> </label>
						<?php echo charitable_get_tooltip_html( __( 'Turn this on to remove tabs from your campaign page.', 'charitable' ), 'tooltipstered' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
							<label for=""><?php echo esc_html__( 'Title', 'charitable' ); ?>
							<?php echo charitable_get_tooltip_html( __( 'Title appears on the tab in the tab navigation.', 'charitable' ), 'tooltipstered' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<input type="text" class="" id="tabs_xxx_title" name="tabs__xxx__title" value="" placeholder="<?php echo esc_html__( 'Tab Title', 'charitable' ); ?> " maxlength="<?php echo intval( $max_tab_title_length ); ?>">
						</div>
						<div class="charitable-group-row charitable-tab-title-row" id="tabs_xxx_row_visible_nav" data-tab-id="">
							<span class="charitable-toggle-control">
								<input type="checkbox" id="tabs_xxx_visible_nav" class="charitable-settings-tab-visible-nav" name="tabs__xxx__visible_nav" value="invisible" />
								<label class="charitable-toggle-control-icon" for="tabs_xxx_visible_nav"></label>
								<label for="tabs_xxx_visible_nav"><?php echo esc_html__( 'Hide tab navigation.', 'charitable' ); ?></label>
								<?php echo charitable_get_tooltip_html( __( 'Hide the tab in the navigation bar (only if you have one tab in your design).', 'charitable' ), 'tooltipstered' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						</div>
					</div>
					<!-- end rows -->
				</div> <!-- end group -->
					<?php

					// This outputs the tab interface in layout options -> tabs.

					if ( $tabs ) {

						$counter = 0;

						$number_of_tabs        = count( $tabs );
						$hide_tab_nav_disabled = $number_of_tabs > 1 ? 'disabled="disabled"' : false;

						foreach ( $tabs as $tab_id => $tab_info ) :

							$group_id       = ( strtolower( $tab_id ) === 'campaign' ) ? 0 : $tab_id;
							$group_name_var = ( $tab_id === 'campaign' ) ? $group_id : $group_id;
							$group_title    = isset( $tab_info['title'] ) && '' !== trim( $tab_info['title'] ) ? $tab_info['title'] : '[ New Tab ]';
							$group_header   = ( $tab_id === 'campaign' ) ? 'Campaign' : $group_title;
							$group_desc     = isset( $tab_info['desc'] ) && '' !== trim( $tab_info['desc'] ) ? $tab_info['desc'] : false;
							$group_classes  = isset( $tab_info['title'] ) && '' !== trim( $tab_info['title'] ) ? false : 'charitable-new-tab';
							$data_group     = ( $tab_id === 'campaign' ) ? 'general-layout-tab' : 'general-layout-tab';
							$active         = $counter === 0 ? 'active' : false;
							$closed         = $counter === 0 ? 'charitable-open' : 'charitable-closed';
							$arrow_icon     = ! $active ? 'fa fa-angle-down charitable-angle-right' : 'fa fa-angle-down charitable-angle-down';
							$type           = ( isset( $tab_info['type'] ) && '' !== trim( $tab_info['type'] ) ) ? $tab_info['type'] : 'html';
							$row_class      = 'html' !== $type ? 'hidden' : false;

							$visible_nav         = ! empty( $tab_info['visible_nav'] ) && 'invisible' === $tab_info['visible_nav'] ? 'checked="checked"' : '';
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
									<a href="#" class="charitable-tab-group-delete" title="Delete Tab"><i class="fa fa-trash-o" aria-hidden="true"></i></a>

								</div>
								<!-- rows -->
								<div class="charitable-group-rows">
									<!-- row -->
									<div class="charitable-group-row charitable-tab-title-row" id="row_tabs__<?php echo esc_attr( $group_name_var ); ?>__title" data-tab-id="<?php echo esc_attr( $group_name_var ); ?>">
										<label for=""><?php echo esc_html__( 'Title', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( false, 'tooltipstered' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<input type="text" class="" id="" name="tabs__<?php echo esc_attr( $group_name_var ); ?>__title" value="<?php echo esc_attr( $group_title ); ?>" placeholder="" maxlength="<?php echo intval( $max_tab_title_length ); ?>">
									</div>
									<div class="charitable-group-row charitable-tab-title-row" id="row_tabs__<?php echo esc_attr( $group_name_var ); ?>__visible_nav" data-tab-id="<?php echo esc_attr( $group_name_var ); ?>">
										<span class="charitable-toggle-control">
											<input type="checkbox" id="charitable-panel-field-settings-charitable-campaign-<?php echo esc_attr( $group_name_var ); ?>__visible_nav" class="charitable-settings-tab-visible-nav"
											name="tabs__<?php echo esc_attr( $group_name_var ); ?>__visible_nav" value="invisible" <?php echo esc_attr( $visible_nav_checked ); ?> />
											<label class="charitable-toggle-control-icon" for="charitable-panel-field-settings-charitable-campaign-<?php echo esc_attr( $group_name_var ); ?>__visible_nav"></label>
											<label class="<?php echo esc_attr( $disabled_css ); ?>" for="charitable-panel-field-settings-charitable-campaign-<?php echo esc_attr( $group_name_var ); ?>__visible_nav"><?php echo esc_html__( 'Hide tab navigation.', 'charitable' ); ?></label>
											<?php echo charitable_get_tooltip_html( __( 'Hide the tab in the navigation bar (only if you have one tab in your design).', 'charitable' ), 'tooltipstered' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
									</div>
									<!-- row -->
								</div>
								<!-- end rows -->
							</div> <!-- end group -->

							<?php ++$counter; ?>

							<?php
						endforeach;

					}

					?>
				<!-- button -->
				<button class="charitable-tab-groups-add charitable-btn charitable-btn-sm"><?php echo esc_html__( '+ Add New Tab', 'charitable' ); ?></button>
				<!-- end button -->
			</div> <!-- end container -->
		</div> <!-- end tab -->

			<?php

			$tab_style    = isset( $this->campaign_data['layout']['advanced']['tab_style'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['tab_style'] ) ? $this->campaign_data['layout']['advanced']['tab_style'] : 'boxed';
			$tab_size     = isset( $this->campaign_data['layout']['advanced']['tab_size'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['tab_size'] ) ? $this->campaign_data['layout']['advanced']['tab_size'] : 'medium';
			$preview_mode = isset( $this->campaign_data['layout']['advanced']['preview_mode'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['preview_mode'] ) ? $this->campaign_data['layout']['advanced']['preview_mode'] : 'normal';

			$show_field_names_default = charitable_show_field_names_by_default() ? 'show' : 'hide';
			$show_field_names         = isset( $this->campaign_data['layout']['advanced']['show_field_names'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['show_field_names'] ) ? $this->campaign_data['layout']['advanced']['show_field_names'] : $show_field_names_default;

			// todo: filter these.

			$theme_color_primary   = isset( $this->campaign_data['layout']['advanced']['theme_color_primary'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['theme_color_primary'] ) ? $this->campaign_data['layout']['advanced']['theme_color_primary'] : '';
			$theme_color_secondary = isset( $this->campaign_data['layout']['advanced']['theme_color_secondary'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['theme_color_secondary'] ) ? $this->campaign_data['layout']['advanced']['theme_color_secondary'] : '';
			$theme_color_tertiary  = isset( $this->campaign_data['layout']['advanced']['theme_color_tertiary'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['theme_color_tertiary'] ) ? $this->campaign_data['layout']['advanced']['theme_color_tertiary'] : '';
			$theme_color_button    = isset( $this->campaign_data['layout']['advanced']['theme_color_button'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['theme_color_button'] ) ? $this->campaign_data['layout']['advanced']['theme_color_button'] : '';

			// tab enabled/disabled.
			$enable_tabs_css = isset( $this->campaign_data['layout']['advanced']['enable_tabs'] ) && '' !== trim( $this->campaign_data['layout']['advanced']['enable_tabs'] ) ? 'disabled' : '';

			?>

		<!-- sub tab -->
		<div class="charitable-layout-options-tab charitable-layout-options-tab-advanced" id="">
			<a href="#" class="charitable-group-toggle charitable-layout-options-group-toggle"><?php echo esc_html__( 'Advanced', 'charitable' ); ?> </a>
			<!-- container -->
			<div class="charitable-layout-options-group-inner">
				<!-- group -->
				<div class="charitable-group charitable-layout-options-advanced-group">
					<!-- rows -->
					<div class="charitable-group-rows row-first">
						<!-- row -->
						<div class="charitable-group-row" id="charitable-group-row-design-layout-options-advanced-tab-style">
							<label for="charitable-design-layout-options-advanced-tab-style"><?php echo esc_html__( 'Tab Style', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( esc_html__( 'Overall tab size in the template.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
							<select class="charitable-design-layout-options-advanced-tab-style <?php echo esc_attr( $enable_tabs_css ); ?>" id="charitable-design-layout-options-advanced-tab-style" name="layout__advanced__tab_style" data-advanced-field-id="tab_style" <?php echo esc_attr( $enable_tabs_css ); ?>>
							<option value="boxed" <?php selected( $tab_style, 'boxed' ); ?>><?php echo esc_html__( 'Boxed', 'charitable' ); ?></option>
							<option value="rounded" <?php selected( $tab_style, 'rounded' ); ?>><?php echo esc_html__( 'Rounded', 'charitable' ); ?></option>
							<option value="minimum" <?php selected( $tab_style, 'minimum' ); ?>><?php echo esc_html__( 'Minimum', 'charitable' ); ?></option>
							</select>
						</div>
						<!-- row -->
						<div class="charitable-group-row" id="charitable-group-row-design-layout-options-advanced-tab-size">
							<label for="charitable-design-layout-options-advanced-tab-size"><?php echo esc_html__( 'Tab Size', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( esc_html__( 'Overall text size inside the tab.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
							<select class="charitable-design-layout-options-advanced-tab-size <?php echo esc_attr( $enable_tabs_css ); ?>" id="charitable-design-layout-options-advanced-tab-size" name="layout__advanced__tab_size"  data-advanced-field-id="tab_size" <?php echo esc_attr( $enable_tabs_css ); ?>>
							<option value="small" <?php selected( $tab_size, 'small' ); ?>><?php echo esc_html__( 'Small', 'charitable' ); ?></option>
							<option value="medium" <?php selected( $tab_size, 'medium' ); ?>><?php echo esc_html__( 'Medium', 'charitable' ); ?></option>
							<option value="large" <?php selected( $tab_size, 'large' ); ?>><?php echo esc_html__( 'Large', 'charitable' ); ?></option>
							</select>
						</div>
						<!-- row -->
						<div class="charitable-group-row" id="charitable-group-row-design-layout-options-advanced-show-field-names">
							<label for="charitable-design-layout-options-advanced-show-field-names"><?php echo esc_html__( 'Field Names', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( esc_html__( 'Show the text that shows the field types in the preview area..', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
							<select class="charitable-design-layout-options-show-field-names" id="charitable-design-layout-options-show-field-names" name="layout__advanced__show_field_names"  data-advanced-field-id="show_field_names">
								<option value="show" <?php selected( $show_field_names, 'show' ); ?>><?php echo esc_html__( 'Show', 'charitable' ); ?></option>
								<option value="hide" <?php selected( $show_field_names, 'hide' ); ?>><?php echo esc_html__( 'Hide', 'charitable' ); ?></option>
							</select>
						</div>
						<!-- row -->
						<div class="charitable-group-row" id="charitable-group-row-design-layout-options-advanced-preview-mode">
							<label for="charitable-design-layout-options-advanced-preview-mode"><?php echo esc_html__( 'Preview Mode', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( esc_html__( 'Show the text that shows the field types in the preview area..', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
							<select class="charitable-design-layout-options-preview-mode" id="charitable-design-layout-options-preview-mode" name="layout__advanced__preview_mode"  data-advanced-field-id="preview_mode">
								<option value="normal" <?php selected( $preview_mode, 'normal' ); ?>><?php echo esc_html__( 'Normal', 'charitable' ); ?></option>
								<option value="minimum" <?php selected( $preview_mode, 'minimum' ); ?>><?php echo esc_html__( 'Minimum', 'charitable' ); ?></option>
							</select>
						</div>
						<!-- row -->
						<div class="charitable-group-row" id="charitable-group-row-design-layout-options-advanced-theme-colors">

							<label for="charitable-design-layout-options-advanced-theme-colors"><?php echo esc_html__( 'Theme Colors', 'charitable' ); ?> <?php echo charitable_get_tooltip_html( esc_html__( 'Adjust the primary colors of the theme.', 'charitable' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>

							<div class="charitable-group-row">

								<div class="charitable-design-layout-options-advanced-theme-color coloris-layout primary" data-label="<?php echo esc_html__( 'Primary:', 'charitable' ); ?>">
									<label for="layout__advanced__theme_color_primary"><?php echo esc_html__( 'Primary', 'charitable' ); ?></label>
									<input type="text" class="coloris instance2 primary" name="layout__advanced__theme_color_primary" data-label="primary" value="<?php echo esc_attr( $theme_color_primary ); ?>"  />
									<!-- <a class="reset-link" href="#"><?php echo esc_html__( 'Reset', 'charitable' ); ?></a> -->
								</div>
								<div class="charitable-design-layout-options-advanced-theme-color coloris-layout secondary" data-label="<?php echo esc_html__( 'Secondary:', 'charitable' ); ?>">
									<label for="layout__advanced__theme_color_secondary"><?php echo esc_html__( 'Secondary', 'charitable' ); ?></label>
									<input type="text" class="coloris instance2 secondary" name="layout__advanced__theme_color_secondary" data-label="secondary" value="<?php echo esc_attr( $theme_color_secondary ); ?>"  />
									<!-- <a class="reset-link" href="#"><?php echo esc_html__( 'Reset', 'charitable' ); ?></a> -->
								</div>
								<div class="charitable-design-layout-options-advanced-theme-color coloris-layout tertiary" data-label="<?php echo esc_html__( 'Tertiary:', 'charitable' ); ?>">
									<label for="layout__advanced__theme_color_tertiary"><?php echo esc_html__( 'Tertiary', 'charitable' ); ?></label>
									<input type="text" class="coloris instance2 tertiary" name="layout__advanced__theme_color_tertiary" data-label="tertiary" value="<?php echo esc_attr( $theme_color_tertiary ); ?>"  />
									<!-- <a class="reset-link" href="#"><?php echo esc_html__( 'Reset', 'charitable' ); ?></a> -->
								</div>
								<div class="charitable-design-layout-options-advanced-theme-color coloris-layout button-color" data-label="<?php echo esc_html__( 'Button:', 'charitable' ); ?>">
									<label for="layout__advanced__theme_color_button"><?php echo esc_html__( 'Button', 'charitable' ); ?></label>
									<input type="text" class="coloris instance2 button-color" name="layout__advanced__theme_color_button" data-label="button" value="<?php echo esc_attr( $theme_color_button ); ?>"  />
									<!-- <a class="reset-link" href="#"><?php echo esc_html__( 'Reset', 'charitable' ); ?></a> -->
								</div>
							</div>

						</div>
						<!-- row -->
					</div>
					<!-- end rows -->
				</div>
			</div> <!-- end container -->
		</div> <!-- end tab -->

			<?php
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

			$counter = intval( $counter );
			$type    = esc_attr( $type );

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
					<div id="charitable-preview-row-<?php echo $counter; ?>" class="charitable-preview-row <?php echo $additional_css; ?>"> <?php // phpcs:ignore ?>
						<div class="row" data-row-id="<?php echo $counter; ?>" data-row-type="<?php echo esc_attr( $type ); ?>" data-row-css="<?php echo $additional_css; ?>"> <?php // phpcs:ignore ?>
					<?php
					break;
			}

			return ob_get_clean();
		}

		/**
		 * Create closing HTML tags based on the type.
		 *
		 * @since 1.8.0
		 *
		 * @param string $type The type of template element to retrieve.
		 * @param int    $counter The counter for the template element.
		 * @return string The closing HTML element for the given template type.
		 */
		public function get_template_element_end( $type = 'row', $counter = 1 ) {

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
		 * Generate preview of campaign in the admin campaign builder (right side).
		 *
		 * @since 1.8.0
		 */
		public function preview() {

			if ( ! is_admin() ) {
				return;
			}

			$builder_template = new Charitable_Campaign_Builder_Templates();

			ob_start();

			$rows  = (array) isset( $this->campaign_data['layout'] ) && ! empty( $this->campaign_data['layout']['rows'] ) ? $this->campaign_data['layout']['rows'] : array();
			$theme = ! empty( $rows ) && ! empty( $this->campaign_data['template_id'] ) ? $builder_template->get_template_data( $this->campaign_data['template_id'] ) : false;

			$element_counter = 0;
			$column_counter  = 0;
			$section_counter = 0;
			$last_field_id   = 0;

			?>

		<div class="charitable-campaign-preview container">

			<?php

			if ( ! empty( $rows ) ) :

				foreach ( $rows as $row_id => $row ) :

					$additional_css = ! empty( $row['css_class'] ) ? esc_attr( $row['css_class'] ) : '';

					echo $this->get_template_element_start( $row['type'], intval( $row_id ), esc_html( $additional_css ) ); // phpcs:ignore

					foreach ( $row['columns'] as $column_id => $column ) :

						echo '<!-- column START -->';

						echo '<div data-column-id="' . intval( $column_counter ) . '" class="column charitable-field-column">';

						if ( ! empty( $column['sections'] ) ) {

							$section_css_class = 'charitable-field-target-inactive';
							$is_section_empty  = true;

							// do a initial look to see if all sections in this column have no fields, and add the default CSS class accordingly.
							foreach ( $column['sections'] as $section_id => $section ) {
								$is_section_empty = ( $section['type'] === 'tabs' ) || ( $section['type'] === 'fields' && ! empty( $section['fields'] ) ) ? false : $is_section_empty;
							}

							foreach ( $column['sections'] as $section_id => $section ) {

								echo '<!-- section START -->';

								$section_css_class         = $is_section_empty ? 'charitable-field-target' : 'charitable-field-target-inactive';
								$section_css_class         = ( ! empty( $row['css_class'] ) && strpos( $row['css_class'], 'no-field-target' ) !== false ) ? false : $section_css_class;
								$section_css_class_no_wrap = empty( $row['css_class'] ) || strpos( $row['css_class'], 'no-field-wrap' ) === false ? ' charitable-field-wrap' : '';
								$section_css_class_no_wrap = ( $section['type'] === 'tabs' ) ? '' : $section_css_class_no_wrap;

								echo '<div data-section-id="' . intval( $section_counter ) . '" data-section-type="' . esc_attr( $section['type'] ) . '" class="section charitable-field-section ' . esc_attr( $section_css_class ) . ' ' . esc_attr( $section_css_class_no_wrap ) . '">';

								echo '<div class="charitable-drag-new-block-here"><p>Drag New Block Here.</p></div>';

								switch ( $section['type'] ) {
									case 'fields':
										$this->render_fields( $section['fields'], $theme, $this->campaign_data, $row['fields'] );
										break;
									case 'header':
										$this->render_fields( $section['fields'], $theme, $this->campaign_data, $row['fields'] );
										break;
									case 'tabs':
										echo $this->get_template_element_start( 'tabs', null, null, $this->campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo $builder_template->get_template_tab_nav( $section['tabs'], $theme, $this->campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$last_field_id = $builder_template->get_template_tab_content( $section['tabs'], $theme, $this->campaign_data, $row['fields'], $last_field_id );
										echo $this->get_template_element_end( 'tabs' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										break;
									default:
										do_action( 'charitable_campaign_builder_preview_section_' . $section['type'], $section, $row, $theme, $this->campaign_data );
										break;
								}

								++$section_counter;

								echo '</div>';

								echo '<!-- section END -->';

							}
						} else {

							$section_css_class = ( empty( $row['css_class'] ) || strpos( $row['css_class'], 'no-field-target' ) === false ) ? 'charitable-field-target' : false;

							echo '<div data-section-id="' . intval( $section_counter ) . '" data-section-type="fields" class="section charitable-field-section charitable-field-wrap ' . esc_attr( $section_css_class ) . '">';

							echo '<div class="charitable-drag-new-block-here"><p>Drag New Block Here.</p></div>';

							++$section_counter;

							echo '</div>';

							echo '<!-- section END -->';

						}

						++$column_counter;

						echo '</div>';

						echo '<!-- column END -->';

					endforeach;

					echo $this->get_template_element_end( $row['type'], intval( $element_counter ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					++$element_counter;

				endforeach;

				endif;

			?>

		</div>

			<?php

			$preview = ob_get_clean();

			echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Create fields for preview area based on an array, usually for a column of the layout.
		 *
		 * @since 1.8.0
		 *
		 * @param array $fields Fields.
		 * @param array $theme Theme data.
		 * @param array $campaign_data Campaign data.
		 * @param array $row_field_data Row data.
		 *
		 * @return string
		 */
		public function render_fields( $fields, $theme, $campaign_data, $row_field_data ) {

			if ( empty( $fields ) ) {
				return;
			}

			foreach ( $fields as $key => $field_id ) :

				$field_type      = is_array( $row_field_data ) && ! empty( $row_field_data[ $field_id ] ) ? esc_attr( $row_field_data[ $field_id ] ) : esc_attr( $key );
				$field_type_data = $campaign_data['fields'][ $field_id ];

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

					echo '<div class="' . implode( ' ', $charitable_field_css_classes ) . '" id="charitable-field-' . intval( $field_id ) . '" data-field-id="' . intval( $field_id ) . '" data-field-type="' . esc_attr( $field_type ) . '" data-field-max="' . esc_attr( $class->max_allowed ) . '" style="">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( $class->can_be_edited ) :
						echo '<a href="#" class="charitable-field-edit" data-type="' . esc_attr( $class->edit_type ) . '" data-section="' . esc_attr( $class->edit_section ) . '" data-edit-field-id="' . esc_attr( $class->edit_field_id ) . '" title="' . esc_attr( $class->edit_label ) . '"><i class="fa fa-pencil"></i></a>';
					endif;
					if ( $class->can_be_duplicated ) :
						echo '<a href="#" class="charitable-field-duplicate" title="Duplicate Field"><i class="fa fa-files-o" aria-hidden="true"></i></a>';
					endif;
					if ( $class->can_be_deleted ) :
						echo '<a href="#" class="charitable-field-delete" title="Delete Field"><i class="fa fa-trash-o"></i></a>';
					endif;

					echo $class->field_preview( $field_settings, $campaign_data, $field_id, $theme ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</div>';

				else :

					$builder_template = new Charitable_Campaign_Builder_Templates();

					$builder_template->render_missing_addon_field_preview( $field_type, $field_id, $field_type_data, $campaign_data );

				endif;

			endforeach;
		}

		/**
		 * Get addoon field label.
		 *
		 * @since 1.8.0
		 * @param string $field_type Field type.
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
		 * @param string $field_type Field type.
		 *
		 * @return array
		 */
		public function get_addon_label( $field_type = false ) {

			$field_type_labels = $this->get_addon_field_labels();

			if ( array_key_exists( $field_type, $field_type_labels ) ) {
				return esc_html( $field_type_labels[ $field_type ]['addon_label'] );
			}

			return false;
		}

		/**
		 * Get addoon field labels.
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
						'field_label' => 'Team Field',
						'addon_label' => 'Ambassadors Addon',
						'url'         => false,
						'plan'        => 'pro',
					),
					'updates-main'     => array(
						'field_label' => 'Updates Field',
						'addon_label' => 'Simple Updates Addon',
						'url'         => false,
						'plan'        => 'pro',
					),
					'comments-main'    => array(
						'field_label' => 'Comments Field',
						'addon_label' => 'Comments Addon',
						'url'         => false,
						'plan'        => 'pro',
					),
				)
			);

			return $this->addon_labels;
		}

		/**
		 * Sort Add Field buttons by order provided.
		 *
		 * @since 1.8.0
		 *
		 * @param array $a First item.
		 * @param array $b Second item.
		 *
		 * @return array
		 */
		public function field_order( $a, $b ) {

			return $a['order'] - $b['order'];
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
				'<p class="empty-tab-notice">' . __( 'There are no tabs yet for this template. You can ', 'charitable' ) . '<br/><strong><a href="%1$s" class="charitable-configure-tab-settings">' . __( 'configure tab settings', 'charitable' ) . '</a> to add a new tab</strong>.</p>',
				'#'
			);
		}
	}

endif;

new Charitable_Builder_Panel_Design();
