<?php
/**
 * Class to add donation wall to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Donation_Wall' ) ) :

	/**
	 * Class to add campaign donation wall to a campaign form in the builder.
	 */
	class Charitable_Field_Donation_Wall extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic inwallation.
			$this->name  = esc_html__( 'Donation Wall', 'charitable' );
			$this->type  = 'donation-wall';
			$this->icon  = 'fa fa-user';
			$this->order = 100;
			$this->group = 'pro';

			$this->align_default = 'center';

			// Edit/Duplication inwallation.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Donation Wall', 'charitable' );
			$this->edit_type         = 'donation-wall';
			$this->edit_section      = 'pro';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
			add_action( 'charitable_builder_backend_scripts', [ $this, 'builder_js' ] ); // admin_enqueue_scripts.

			// Defaults.
			add_filter( 'charitable_field_new_default', [ $this, 'new_field_defaults' ] );
		}

		/**
		 * Field options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Field settings.
		 */
		public function field_options( $field ) {
			/*
			 * Basic field options.
			 */

			// Options open markup.
		}

		/**
		 * Set default values for a new field.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field The field settings.
		 * @return array
		 */
		public function new_field_defaults( $field ) {

			if ( isset( $field['type'] ) && 'donation-wall' !== $field['type'] ) {
				return $field;
			}

			$field['show_name']   = 'show_name';
			$field['show_amount'] = 'show_amount';
			$field['show_avatar'] = 'show_avatar';

			return $field;
		}

		/**
		 * Render the field.
		 *
		 * @since 1.8.0
		 *
		 * @param array   $field_data     Any field data.
		 * @param array   $campaign_data  Amount data and settings.
		 * @param integer $field_id       The field ID.
		 * @param string  $mode           Where the field is being displayed ("preview" or "template").
		 * @param array   $template_data  Tempalate data.
		 */
		public function render( $field_data = false, $campaign_data = false, $field_id = false, $mode = 'template', $template_data = false ) {

			$html = '';

			if ( 'preview' === $mode ) {

				if ( false === $field_data || ! is_array( $field_data ) ) {
					$field_data = array();
				}

				$field_data['show_name']     = ( empty( $field_data['show_name'] ) ) ? 0 : 1;
				$field_data['show_location'] = ( empty( $field_data['show_location'] ) ) ? 0 : 1;
				$field_data['show_amount']   = ( empty( $field_data['show_amount'] ) ) ? 0 : 1;
				$field_data['show_avatar']   = ( empty( $field_data['show_avatar'] ) ) ? 0 : 1;

				$field_data['show_name']     = ( isset( $field_data['show_hide']['show_name'] ) && 'show_name' === $field_data['show_hide']['show_name'] ) ? 1 : $field_data['show_name'];
				$field_data['show_location'] = ( isset( $field_data['show_hide']['show_location'] ) && 'show_location' === $field_data['show_hide']['show_location'] ) ? 1 : $field_data['show_location'];
				$field_data['show_amount']   = ( isset( $field_data['show_hide']['show_amount'] ) && 'show_amount' === $field_data['show_hide']['show_amount'] ) ? 1 : $field_data['show_amount'];
				$field_data['show_avatar']   = ( isset( $field_data['show_hide']['show_avatar'] ) && 'show_avatar' === $field_data['show_hide']['show_avatar'] ) ? 1 : $field_data['show_avatar'];

				$defaults = array(
					'number'            => 10,
					'orderby'           => 'date',
					'order'             => 'DESC',
					'campaign_id'       => ! empty( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : 0,
					'campaign'          => ! empty( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : 0,
					'distinct_donors'   => 0,
					'orientation'       => 'horizontal',
					'show_name'         => 1,
					'show_location'     => 0,
					'show_amount'       => 1,
					'show_avatar'       => 1,

					'hide_if_no_donors' => 0,
					'builder_preview'   => 1,
				);

				$params = apply_filters( 'charitable_campaign_builder_donation_wall_args', array_replace_recursive( $defaults, $field_data ), $field_data, $campaign_data );

				$shortcode_args = '';

				foreach ( $defaults as $name => $value ) {

					if ( is_integer( $value ) ) {
						$shortcode_args .= $name . '=' . $params[ $name ] . ' ';
					} else {
						$shortcode_args .= $name . '="' . $params[ $name ] . '" ';
					}
				}

				$show_hide_args = array( 'show_name', 'show_location', 'show_amount', 'show_avatar' );

				foreach ( $show_hide_args as $show_hide_arg ) {

					if ( ! empty( $field_data[ $show_hide_arg ] ) && 0 === intval( $field_data[ $show_hide_arg ] ) ) {
						$shortcode_args .= ' ' . $show_hide_arg . '=0';
					} elseif ( ! empty( $field_data[ $show_hide_arg ] ) && 1 === intval( $field_data[ $show_hide_arg ] ) ) {
						$shortcode_args .= ' ' . $show_hide_arg . '=1';
					} elseif ( empty( $field_data[ $show_hide_arg ] ) ) {
						$shortcode_args .= ' ' . $show_hide_arg . '=0';
					}
				}

				$html = '<div class="charitable-field-preview-html donation-wall charitable-prevent-select" data-field-type="' . $this->type . '">
						<span class="charitable-field-donation-wall-placeholder charitable-placeholder">';

				$html .= ! empty( $field_data['headline'] ) ? '<h5 class="charitable-field-preview-headline">' . esc_html( $field_data['headline'] ) . '</h5>' : '';

				$html .= do_shortcode( '[charitable_donors ' . $shortcode_args . ' ]' );

				$html .= '	</span>
				  	</div>';

				return $html;

			}

			return $html;
		}


		/**
		 * Donation Wall preview inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $field_data Field data and settings.
		 * @param array  $campaign_data Campaign data and settings.
		 * @param array  $field_id Field ID.
		 * @param string $theme Template data.
		 */
		public function field_preview( $field_data = false, $campaign_data = false, $field_id = false, $theme = '' ) {

			$html  = $this->field_title( $this->name );
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'preview' ), $field_data );

			echo $html; // phpcs:ignore
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field          The passed field type.
		 * @param array  $field_data     Any field data.
		 * @param array  $campaign_data  Wall data and settings.
		 */
		public function field_display( $field, $field_data = false, $campaign_data = false ) {

			$campaign_id = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : false;
			$css_class   = ! empty( $field_data['css_class'] ) ? ' class="' . esc_html( $field_data['css_class'] ) . '" ' : '';
			$headline    = ! empty( $field_data['headline'] ) ? '<h5 class="charitable-field-template-headline">' . esc_html( $field_data['headline'] ) . '</h5>' : '';

			if ( 0 === intval( $campaign_id ) ) {
				return;
			}

			ob_start();

			?>
			<?php echo $headline; // phpcs:ignore
			?>
				<div class="charitable-campaign-field charitable-campaign-field_<?php echo esc_attr( $this->type ); ?>">
					<div <?php echo esc_attr( $css_class ); ?>>

					<?php

						$defaults = array(
							'number'            => 10,
							'orderby'           => 'date',
							'order'             => 'DESC',
							'campaign_id'       => $campaign_id,
							'campaign'          => $campaign_id,
							'distinct_donors'   => 0,
							'orientation'       => 'horizontal',
							'hide_if_no_donors' => 0,
						);

						$params = apply_filters( 'charitable_campaign_builder_donation_wall_args', array_replace_recursive( $defaults, $field_data ), $field_data, $campaign_data );

						$shortcode_args = '';

						foreach ( $defaults as $name => $value ) {

							if ( is_integer( $value ) ) {
								$shortcode_args .= $name . '=' . $params[ $name ] . ' ';
							} else {
								$shortcode_args .= $name . '="' . $params[ $name ] . '" ';
							}
						}

						$show_hide_args = array( 'show_name', 'show_location', 'show_amount', 'show_avatar' );

						foreach ( $show_hide_args as $show_hide_arg ) {

							if ( ! isset( $field_data['show_hide'][ $show_hide_arg ] ) || empty( $field_data['show_hide'][ $show_hide_arg ] ) ) {
								$shortcode_args .= ' ' . $show_hide_arg . '=0';
							} elseif ( ! empty( $field_data['show_hide'][ $show_hide_arg ] ) ) {
								$shortcode_args .= ' ' . $show_hide_arg . '=1';
							} elseif ( empty( $field_data['show_hide'][ $show_hide_arg ] ) ) {
								$shortcode_args .= ' ' . $show_hide_arg . '=0';
							}
						}

						echo do_shortcode( '[charitable_donors ' . $shortcode_args . ' ]' );

						?>
					</div>
				</div>

			<?php

			$final_html = ob_get_clean();

			$html = $this->field_display_wrapper( $final_html, $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore
		}

		/**
		 * Donation Wall display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id      Number ID.
		 * @param array   $campaign_data Wall data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
				isset( $settings['headline'] ) ? $settings['headline'] : false,
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			echo $charitable_builder_form_fields->generate_radio_options( // phpcs:ignore
				isset( $settings['orientation'] ) ? $settings['orientation'] : false,
				esc_html__( 'Orientation', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_orientation' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'orientation' ),
					'field_id'        => esc_attr( $field_id ),
					'tooltip'         => esc_html( $this->tooltip ),
					'options'         => array(
						'Vertical'   => 'vertical',
						'Horizontal' => 'horizontal',
					),
					'option_default'  => 'horizontal',
					'container_class' => 'charitable-campaign-builder-donor-wall',
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
				isset( $settings['number'] ) ? $settings['number'] : false,
				esc_html__( 'Number of Donors To Show', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_number' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'number' ),
					'field_id'        => esc_attr( $field_id ),
					'default'         => 10,
					'type'            => 'number',
					'container_class' => 'charitable-campaign-builder-donor-wall',
				)
			);

			$orderby_options = array(
				'date' => 'Date',
				'name' => 'Name',
			);

			echo $charitable_builder_form_fields->generate_dropdown( // phpcs:ignore
				isset( $settings['orderby'] ) ? $settings['orderby'] : 1,
				esc_html__( 'Order By', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_orderby',
					'name'            => array( '_fields', intval( $field_id ), 'orderby' ),
					'container_class' => 'charitable-campaign-builder-donor-wall',
					'options'         => $orderby_options, // phpcs:ignore
					'default'         => 'date',
					'field_id'        => esc_attr( $field_id ),

				)
			);

			$order_options = array(
				'DESC' => esc_html__( 'Descending', 'charitable' ),
				'ASC'  => esc_html__( 'Ascending', 'charitable' ),
			);

			echo $charitable_builder_form_fields->generate_dropdown( // phpcs:ignore
				isset( $settings['order'] ) ? $settings['order'] : 1,
				esc_html__( 'Order', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_order',
					'name'            => array( '_fields', intval( $field_id ), 'order' ),
					'container_class' => 'charitable-campaign-builder-donor-wall',
					'options'         => $order_options, // phpcs:ignore
					'default'         => 'DESC',
					'field_id'        => esc_attr( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_toggle( // phpcs:ignore
				isset( $settings['hide_if_no_donors'] ) ? $settings['hide_if_no_donors'] : false,
				esc_html__( 'Hide If No Donors', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_hide_if_no_donors' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'hide_if_no_donors' ),
					'container_class' => 'charitable-campaign-builder-donor-wall',
					'field_id'        => esc_attr( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_toggles( // phpcs:ignore
				isset( $settings['show_hide'] ) ? $settings['show_hide'] : false,
				esc_html__( 'Donor Information', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_show_hide' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'show_hide' ),
					'field_id'        => esc_attr( $field_id ),
					'tooltip'         => esc_html( $this->tooltip ),
					'default'         => array( 'show_name', 'show_amount', 'show_avatar' ),
					'container_class' => 'charitable-campaign-builder-donor-wall',
					'options'         => array(
						'Show Name'     => 'show_name',
						'Show Location' => 'show_location',
						'Show Amount'   => 'show_amount',
						'Show Avatar'   => 'show_avatar',
					)
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => intval( $field_id ) ) ); // phpcs:ignore

			echo $charitable_builder_form_fields->generate_number_slider( // phpcs:ignore
				isset( $settings['width_percentage'] ) ? $settings['width_percentage'] : 100,
				esc_html__( 'Width', 'charitable' ),
				array(
					'id'         => 'field_' . esc_attr( $this->type ) . '_width_percentage' . '_' . intval( $field_id ), // phpcs:ignore
					'name'       => array( '_fields', intval( $field_id ), 'width_percentage' ),
					'field_type' => esc_attr( $this->type ),
					'css_class'  => 'charitable-indicator-on-hover',
					'field_id'   => intval( $field_id ),
					'symbol'     => '%',
					'min'        => 0,
					'min_actual' => 25,
				)
			);

			echo $charitable_builder_form_fields->generate_align( // phpcs:ignore
				isset( $settings['align'] ) ? $settings['align'] : esc_attr( $this->align_default ),
				esc_html__( 'align', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_align' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'align' ),
					'field_id' => intval( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
				isset( $settings['css_class'] ) ? $settings['css_class'] : false,
				esc_html__( 'CSS Class', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_css_class' . '_' . intval( $field_id ),
					'name'     => array( '_fields', intval( $field_id ), 'css_class' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add CSS classes (seperated by a space) for this field to customize it\'s appearance in your theme.', 'charitable' ),
				)
			);

			?>

			<?php

			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Generate field vix ajax.
		 *
		 * @since 1.8.0
		 */
		public function settings_display_ajax() {

			$field_id    = isset( $_POST['field_id'] ) ? intval( $_POST['field_id'] ) : 0; // phpcs:ignore
			$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0; // phpcs:ignore.

			$charitable_builder_wall_fields = new Charitable_Builder_Form_Fields();

			$campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings      = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

			<?php

			echo $charitable_builder_wall_fields->generate_text( // phpcs:ignore
				$settings['css_class'],
				esc_html__( 'CSS Class', 'charitable' ),
				array(
					'id'      => 'field_' . esc_attr( $this->type ) . '_css_class',
					'name'    => array( 'fields', esc_attr( $this->type ), 'css_class' ),
					'tooltip' => esc_html( $this->tooltip ),
				)
			);

			?>

			<?php

			$html = ob_get_clean();

			wp_send_json_success( [ 'html' => $html ] );

			exit;
		}

		/**
		 * Enqueue frontend js.
		 *
		 * @since 1.8.0
		 */
		public function frontend_js() {
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 *
		 * @param array $walls Walls on the current page.
		 */
		public function builder_js( $min ) {
		}

		/**
		 * Wallat and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id      Field type.
		 * @param mixed $field_submit  Field value that was submitted.
		 * @param array $campaign_data Campaign data and settings.
		 */
		public function wallat( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on wall submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Field ID.
		 * @param mixed $field_submit Field value that was submitted.
		 * @param array $campaign_data Campaign data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Possible depreciated function.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field      Field ID.
		 * @param array  $field_atts Field value that was submitted.
		 * @param array  $campaign_data Campaign data and settings.
		 */
		public function section_top( $field, $field_atts, $campaign_data ) {
		}

		/**
		 * Possible depreciated function.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field      Field type.
		 * @param array  $field_atts Field value that was submitted.
		 * @param array  $campaign_data Campaign data and settings.
		 */
		public function section_bottom( $field, $field_atts, $campaign_data ) {
		}
	}

	new Charitable_Field_Donation_Wall();

endif;