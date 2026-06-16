<?php
/**
 * Class to add donation amount (oringially from the widget) to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Donate_Amount' ) ) :

	/**
	 * Class to add campaign field donate amount field to a campaign form in the builder.
	 */
	class Charitable_Field_Donate_Amount extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Donate Amount', 'charitable' );
			$this->type  = 'donate-amount';
			$this->icon  = 'fa fa-dollar';
			$this->order = 100;
			$this->group = 'standard';

			$this->align_default = 'center';

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = false;
			$this->edit_label        = esc_html__( 'Edit Donate Amount', 'charitable' );
			$this->edit_type         = 'donate-amount';
			$this->edit_section      = 'standard';
			$this->max_allowed       = 1;

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			// add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
			// add_action( 'charitable_builder_backend_scripts', [ $this, 'builder_js' ]); // admin_enqueue_scripts.
			add_filter( 'charitable_builder_field_button_attributes', [ $this, 'make_unique_button' ], 10, 4 );
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

			$headline    = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : false;
			$campaign_id = isset( $campaign_data['id'] ) && 0 !== intval( $campaign_data['id'] ) ? intval( $campaign_data['id'] ) : false;

			$preview_or_template_css = ( 'preview' === $mode ) ? 'preview' : 'template';
			$input_disabled_attr     = ( 'preview' === $mode ) ? 'disabled="true"' : false;
			$donation_amount_found   = false;

			$currency_helper = charitable_get_currency_helper();

			ob_start();

			if ( 'preview' === $mode ) :
				echo '<div class="placeholder">';
			endif;

			if ( 'preview' === $mode || ( 'template' === $mode && false !== $headline ) ) :
				echo '<h5 class="charitable-field-' . esc_attr( $preview_or_template_css ) . '-headline">' . esc_html( $headline ) . '</h5>';
			endif;

			// if there is no campaign id, then we assume this is a new campaign and assign the default donation amounts.
			if ( false === $campaign_id ) {

				echo '
                <div class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-options">
                <ul class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amounts">
                    <li class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amount suggested-donation-amount">
                        <label><span class="amount">' . esc_html( $currency_helper->get_monetary_amount( '5.00' ) ) . '</span></label>
                    </li>
                    <li class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amount suggested-donation-amount">
						<label><span class="amount">' . esc_html( $currency_helper->get_monetary_amount( '10.00' ) ) . '</span></label>
                    </li>
                    <li class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amount suggested-donation-amount selected">
						<label><span class="amount">' . esc_html( $currency_helper->get_monetary_amount( '15.00' ) ) . '</span></label>
                    </li>
                    <li class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amount suggested-donation-amount">
						<label><span class="amount">' . esc_html( $currency_helper->get_monetary_amount( '20.00' ) ) . '</span></label>
                    </li>
                    <li class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amount custom-donation-amount">
                        <span class="custom-donation-amount-wrapper">
							<label>
								<input type="radio" name="donation_amount" value="custom"><span class="description">' . esc_html__( 'Custom Amount', 'charitable' ) . '</span>
							</label>
							<input type="text" ' . esc_attr( $input_disabled_attr ) . ' class="custom-donation-input" name="custom_donation_amount" placeholder="' . esc_html__( 'Custom Donation Amount', 'charitable' ) . '" value="">
                        </span>
                    </li>
            </ul>
            </div>';

			} else {

				$campaign = charitable_get_campaign( $campaign_id );

				// We want the button to contain a donation amount, but we go with what is in the SESSION first, then the DEFAULT amount.
				$donation_amount_in_session = $campaign->get_donation_amount_in_session();

				if ( ! $campaign ) :
					echo '<p>Cannot recieve donations.</p>';
					echo '</div> <!-- placeholder -->';
					$html = ob_get_clean();
					echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					return;
				endif;

				$suggested_donations = $campaign->get_suggested_donations();

				if ( empty( $suggested_donations ) && ! $campaign->get( 'allow_custom_donations' ) ) :
					echo '<p>No donation options available.</p>';
					echo '</div> <!-- placeholder -->';
					$html = ob_get_clean();
					echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					return;
				endif;

				echo '
                <div class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-options">
                <ul class="charitable-' . esc_attr( $preview_or_template_css ) . '-donation-amounts">';

				if ( ! empty( $suggested_donations ) ) :

					$suggested_donations_default = ! empty( $campaign_data['settings']['donation-options']['suggested_donations_default'] ) ? intval( $campaign_data['settings']['donation-options']['suggested_donations_default'] ) : 0;

					foreach ( $suggested_donations as $suggested_donation_id => $suggested_donation ) :

						$selected_css      = false;
						$checked_attribute = false;

						if ( ( $donation_amount_in_session && $donation_amount_in_session === $suggested_donation['amount'] )
						|| ( ! $donation_amount_in_session && $suggested_donation_id === $suggested_donations_default ) ) {
							$selected_css          = 'selected';
							$checked_attribute     = 'checked="true"';
							$donation_amount_found = true;
						}

						?>
					<li class="charitable-<?php echo esc_attr( $preview_or_template_css ); ?>-donation-amount suggested-donation-amount <?php echo esc_attr( $selected_css ); ?>">
						<label>
							<input type="radio" name="donation_amount" value="<?php echo esc_attr( $suggested_donation['amount'] ); ?>" <?php echo esc_attr( $checked_attribute ); ?>>
							<span class="amount"><?php echo esc_html( $currency_helper->get_monetary_amount( $suggested_donation['amount'] ) ); ?></span> <?php /* <span class="description"><?php echo esc_html( $suggested_donation['description'] ); ?></span> */ ?>
						</label>
					</li>
						<?php
				endforeach;
				endif;
				?>

				<?php

				$custom_donation_amount_class = ! empty( $campaign_data['settings']['donation-options']['allow_custom_donations'] ) ? false : 'charitable-hidden';

				$value = false === $donation_amount_found && $donation_amount_in_session !== 0 ? $currency_helper->get_monetary_amount( $donation_amount_in_session ) : false;

				?>

				<li class="charitable-<?php echo esc_attr( $preview_or_template_css ); ?>-donation-amount custom-donation-amount <?php echo $custom_donation_amount_class; ?>"> <?php // phpcs:ignore ?>
					<span class="custom-donation-amount-wrapper">
					<label>
					<input type="radio" name="donation_amount" value="custom"><span class="description"><?php echo esc_html__( 'Custom amount', 'charitable' ); ?></span>
					</label>
					<input type="text" <?php echo esc_attr( $input_disabled_attr ); ?> class="custom-donation-input" name="custom_donation_amount" placeholder="<?php echo esc_html__( 'Custom Donation Amount', 'charitable' ); ?>" value="<?php echo esc_attr( $value ); ?>">
					</span>
				</li>

				<?php

				echo '
            </ul>
            </div>';

			}

			if ( 'preview' === $mode ) :
				echo '</div> <!-- placeholder -->';
			endif;

			$html = ob_get_clean();

			return $html;
		}

		/**
		 * Donate Amount preview inside the builder.
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

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type     The passed field type.
		 * @param array  $field_data     Any field data.
		 * @param array  $campaign_data  Amount data and settings.
		 */
		public function field_display( $field_type = '', $field_data = false, $campaign_data = false ) {

		$html = $this->field_display_wrapper( $this->render( $field_data, $campaign_data ), $field_data );

		echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Donate Amount display on the form front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id      Number ID.
		 * @param array   $campaign_data Amount data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings       = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['headline'] ) ? $settings['headline'] : '',
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add a headline to this field.', 'charitable' ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => $field_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo $charitable_builder_form_fields->generate_donation_amounts_mini( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$campaign_data,
				esc_html__( 'Suggested Donation Amounts', 'charitable' ),
				array(
					'from'     => 'field',
					'id'       => 'field_' . esc_attr( $this->type ) . '_donation_amounts' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'donation_amounts' ),
					'field_id' => intval( $field_id ),
					'default'  => false,
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => $field_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$campaign_settings = false;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
			if ( ! empty( $_GET['campaign_id'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display/routing parameter
				$campaign_id = intval( wp_unslash( $_GET['campaign_id'] ) );
				if ( $campaign_id > 0 ) {
					$campaign_settings = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
				}
			}

			$default_allow_custom = false !== $campaign_settings && ! isset( $campaign_settings['settings']['donation-options']['allow_custom_donations'] ) ? false : '1';

			echo $charitable_builder_form_fields->generate_toggle( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$default_allow_custom,
				esc_html__( 'Allow Custom Donations', 'charitable' ),
				array(
					'id'              => 'field_' . esc_attr( $this->type ) . '_allow_custom_donations' . '_' . intval( $field_id ), // phpcs:ignore
					'name'            => array( '_fields', intval( $field_id ), 'allow_custom_donations' ),
					'container_class' => 'charitable-campaign-builder-allow-custom-donations',
					'field_id'        => esc_attr( $field_id ),
					'checked_value'   => '1',
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => $field_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		 * Display settings for AJAX requests.
		 *
		 * @since  1.8.0
		 * @version 1.8.9.1
		 *
		 * @return void
		 */
		public function settings_display_ajax() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$field_id    = isset( $_POST['field_id'] ) ? intval( wp_unslash( $_POST['field_id'] ) ) : 0;
			$campaign_id = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0; // todo: should this be added? see a few lines down.
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
				wp_send_json_error( esc_html__( 'Something went wrong while performing this action.', 'charitable' ) );
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$campaign_data = get_post_meta( $campaign_id, 'campaign_settings_v2', true );
			$settings      = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;

			ob_start();

			?>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				isset( $settings['headline'] ) ? $settings['headline'] : '',
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add a headline to this field.', 'charitable' ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		 * @param array $min Min or not.
		 */
		public function builder_js( $min ) {
		}

		/**
		 * Amountat and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id      Field type.
		 * @param mixed $field_submit  Field value that was submitted.
		 * @param array $campaign_data Campaign data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
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
		 * Return attributes that disable the field button if campaign_data shows one already exists in the template.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_attr     All the settings for this field button.
		 * @param array $field Field data.
		 * @param array $campaign_data Campaign data and settings.
		 * @param array $current_fields Current fields according to saved campaign data.
		 */
		public function make_unique_button( $field_attr = false, $field = false, $campaign_data = false, $current_fields = false ) {

			if ( false === $field || false === $campaign_data || false === $current_fields || ! is_array( $campaign_data ) || ! is_array( $current_fields ) || empty( $field['type'] ) ) {
				return $field_attr;
			}

			if ( ! empty( $field['type'] ) && $field['type'] !== $this->type ) {
				return $field_attr;
			}

			// is this already in the template?
			if ( in_array( $this->type, $current_fields, true ) ) {
				$field_attr['class'][] = 'charitable-disabled';
			}

			return $field_attr;
		}

		/**
		 * Possible depreciated function.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field     Field type.
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
		 * @param string $field     Field type.
		 * @param array  $field_atts Field value that was submitted.
		 * @param array  $campaign_data Campaign data and settings.
		 */
		public function section_bottom( $field, $field_atts, $campaign_data ) {
		}
	}

	new Charitable_Field_Donate_Amount();

endif;