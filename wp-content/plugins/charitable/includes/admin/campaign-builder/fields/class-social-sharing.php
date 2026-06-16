<?php
/**
 * Class to add a social sharing area to a campaign form in the builder.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Field_Social_Sharing' ) ) :

	/**
	 * Class to add campaign organizer social sharing field to a campaign form in the builder.
	 */
	class Charitable_Field_Social_Sharing extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Social Sharing', 'charitable' );
			$this->type  = 'social-sharing';
			$this->icon  = 'fa-share-alt';
			$this->order = 100;

			$this->align_default = 'left';

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Social Sharing', 'charitable' );
			$this->edit_type         = 'social-sharing';
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Social Sharing options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Social Sharing settings.
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

			// $social_networks = $this->get_social_networks( $campaign_data );

			$headline = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : esc_html__( 'Share:', 'charitable' );

			$html = '<div class="charitable-field-' . $mode . '-social-sharing">
					<div class="charitable-field-' . $mode . '-social-sharing-headline-container placeholder">';

			if ( false !== $headline ) {
				$html .= '<h5 class="charitable-field-' . $mode . '-headline">' . $headline . '</h5>';
			}

			$html .= '</div>
						<div class="charitable-field-row">';

			$social_networks_settings = (array) isset( $campaign_data['fields'][ intval( $field_id ) ]['social_networks'] ) && ! empty( $campaign_data['fields'][ intval( $field_id ) ]['social_networks'] ) ? $campaign_data['fields'][ intval( $field_id ) ]['social_networks'] : false;

			$defaults = array(
				'twitter'   => 1,
				'facebook'  => 1,
				'linkedin'  => 1,
				'pinterest' => 1,
				'mastodon'  => 0,
				'threads'   => 0,
				'bluesky'   => 1,
			);

			// Load the class templates file.
			$file = charitable()->get_path( 'includes' ) . 'admin/campaign-builder/templates/class-templates.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			$template_id = ! empty( $campaign_data['template_id'] ) ? esc_attr( $campaign_data['template_id'] ) : false;
			$suffix      = ! empty( $template_data['meta']['suffixes'][ $this->type ] ) ? esc_attr( $template_data['meta']['suffixes'][ $this->type ] ) : false;

			// $social_networks_settings = wp_parse_args( $social_networks_settings, $defaults );
			if ( ! is_array( $social_networks_settings ) || empty( $social_networks_settings ) ) {
				$social_networks_settings = $defaults;
			} else {
				foreach ( $defaults as $key => $value ) {
					if ( ! isset( $social_networks_settings[ $key ] ) ) {
						$social_networks_settings[ $key ] = 0;
					}
				}
			}
			$social_networks_template = ( $mode === 'template' ) ? $this->get_social_networks( $campaign_data, $mode, $suffix ) : false;

			$open_new_tab = ! empty( $field_data['open_new_tab'] ) ? esc_html( $field_data['open_new_tab'] ) : 1;

			if ( ! empty( $social_networks_settings ) ) {

				foreach ( $social_networks_settings as $network => $selected ) {

					if ( 1 === intval( $selected ) ) {

						if ( $mode === 'template' ) {

							$label = $network;

							$icon_url  = ( ! empty( $social_networks_template[ $label ]['icon_url'] ) ) ? $social_networks_template[ $label ]['icon_url'] : charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/mastodon.svg'; // todo: replace with a genric icon of some sort.
							$link      = isset( $field_data[ $label . '_url' ] ) ? esc_url( $field_data[ $label . '_url' ] ) : '#';
							$share_url = false;
							$href      = false;

							// Prepare the custom share URL.
							if ( 'mastodon' !== $network && ! empty( $social_networks_template[ $label ]['share_url'] ) ) {
								$share_url = str_replace( '{$text}', rawurlencode( wp_strip_all_tags( $campaign_data['title'] ) ), $social_networks_template[ $label ]['share_url'] );
								$share_url = str_replace( '{$url}', rawurlencode( get_permalink( $campaign_data['id'] ) ), $share_url );
								$share_url = apply_filters( 'charitable_campaign_social_sharing_link_' . $network, $share_url, $social_networks_template[ $label ], $campaign_data );
								$href      = '<a href="' . esc_url( $share_url ) . '" target="_blank">';
							} elseif ( 'mastodon' === $network ) {
								$href = '<a href="#" target="mastodon" class="charitable-mastodon-share">';
							}

							if ( $link && $href ) {

								$html .= '<div class="charitable-social-field-column charitable-social-sharing-' . $mode . '-' . esc_attr( $network ) . '">
										<div class="charitable-campaign-social-link" data-social-network="' . $label . '">
											' . $href . '
												<div><img title="' . esc_html__( 'Share On', 'charitable' ) . ' ' . $social_networks_template[ $label ]['public_label'] . '"  src="' . $icon_url . '" alt="' . $social_networks_template[ $label ]['public_label'] . '" /></div>
												<p>' . $social_networks_template[ $label ]['public_label'] . '</p>
											</a>
										</div>
									</div>';

							}
						} else {

							$html .= '<div class="charitable-social-field-column charitable-social-sharing-' . $mode . '-' . esc_attr( $network ) . '">
									<span class="charitable-placeholder"></span>
								</div>';

						}
					} elseif ( $mode === 'preview' ) {
						$html .= '<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-' . esc_attr( $network ) . '">
								<span class="charitable-placeholder"></span>
							</div>';
					}
				}
			} else {

				$html .= '<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-twitter">
					<span class="charitable-placeholder"></span>
				</div>
				<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-facebook">
					<span class="charitable-placeholder"></span>
				</div>
				<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-linkedin">
					<span class="charitable-placeholder"></span>
				</div>
				<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-pinterest">
					<span class="charitable-placeholder"></span>
				</div>
				<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-mastodon">
					<span class="charitable-placeholder"></span>
				</div>
				<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-threads">
					<span class="charitable-placeholder"></span>
				</div>
				<div class="charitable-social-field-column charitable-hidden charitable-social-sharing-' . $mode . '-bluesky">
					<span class="charitable-placeholder"></span>
				</div>';

			}

			$html .= '</div></div>';

			return $html;
		}

		/**
		 * Social Sharing preview inside the builder.
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
			$html .= $this->field_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'preview', $theme ), $field_data );

			echo $html; // phpcs:ignore
		}

		/**
		 * The display on the campaign front-end.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field_type      The passed field type.
		 * @param array  $field_data      Any field data.
		 * @param array  $campaign_data   Amount data and settings.
		 * @param bool   $is_preview_page If the page is a preview page.
		 * @param int    $field_id        The field ID.
		 * @param array  $template_data   Template data.
		 */
		public function field_display( $field_type = '', $field_data = false, $campaign_data = false, $is_preview_page = false, $field_id = false, $template_data = false ) {

			$html = $this->field_display_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'template', $template_data ), $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . esc_attr( $this->type ) . '_field_display', $html, $campaign_data ); // phpcs:ignore
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field_id       Social Sharing settings.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings        = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;
			$social_networks = $this->get_social_networks( $campaign_data );

			if ( empty( $social_networks ) ) {
				return;
			}

			add_action( 'charitable_builder_' . esc_attr( $this->type ) . '_settings_display_start', [ $this, 'settings_section_top' ], 10, 2 );
			add_action( 'charitable_builder_' . esc_attr( $this->type ) . '_settings_display_end', [ $this, 'settings_section_bottom' ], 10, 2 );

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<div class="charitable-panel-field charitable-panel-field-section" data-field-id="<?php echo intval( $field_id ); ?>">

				<?php do_action( 'charitable_builder_' . esc_attr( $this->type ) . '_settings_display_start', $field_id, $campaign_data ); ?>

			</div>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
				isset( $settings['headline'] ) ? $settings['headline'] : esc_html__( 'Share:', 'charitable' ),
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			// Go through all the social networks.
			echo $charitable_builder_form_fields->generate_checkboxes( // phpcs:ignore
				isset( $settings['social_networks'] ) ? $settings['social_networks'] : false,
				esc_html__( 'Choose your social network(s):', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_social_networks' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'social_networks' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html( $this->tooltip ),
					'class'    => 'charitable-social-network-checkboxes',
					'options'  => array(
						'Twitter / X' => 'twitter',
						'Facebook'    => 'facebook',
						'LinkedIn'    => 'linkedin',
						'Pinterest'   => 'pinterest',
						'Mastodon'    => 'mastodon',
						'Threads'     => 'threads',
						'Bluesky'    => 'bluesky',
					),
					'defaults' => array(
						'twitter',
						'facebook',
						'linkedin',
						'pinterest',
						'mastodon',
						'threads',
						'bluesky',
					),
				)
			);

			echo $charitable_builder_form_fields->generate_toggle( // phpcs:ignore
				isset( $settings['open_new_tab'] ) ? $settings['open_new_tab'] : 1,
				esc_html__( 'Open Links In New Tab', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_open_new_tab' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'open_new_tab' ),
					'field_id' => intval( $field_id ),
				)
			);

			echo $charitable_builder_form_fields->generate_divider( false, false, array( 'field_id' => $field_id ) ); // phpcs:ignore

			echo $charitable_builder_form_fields->generate_number_slider( // phpcs:ignore
				isset( $settings['width_percentage'] ) ? $settings['width_percentage'] : 100,
				esc_html__( 'Width', 'charitable' ),
				array(
					'id'         => 'field_' . esc_attr( $this->type ) . '_width_percentage' . '_' . intval( $field_id ), // phpcs:ignore
					'name'       => array( '_fields', intval( $field_id ), 'width_percentage' ),
					'field_type' => esc_attr( $this->type ),
					'field_id'   => intval( $field_id ),
					'symbol'     => '%',
					'min'        => 0,
					'min_actual' => '50',
					'css_class'  => 'charitable-indicator-on-hover',
					'tooltip'    => esc_html__( 'Adjust the width of the field within the column.', 'charitable' ),
				)
			);

			echo $charitable_builder_form_fields->generate_align( // phpcs:ignore
				isset( $settings['align'] ) ? $settings['align'] : esc_attr( $this->align_default ),
				esc_html__( 'Align', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_align' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'align' ),
					'field_id' => intval( $field_id ),
				)
			);

			/* CSS CLASS */

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
				isset( $settings['css_class'] ) ? $settings['css_class'] : false,
				esc_html__( 'CSS Class', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_css_class' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'css_class' ),
					'field_id' => intval( $field_id ),
					'tooltip'  => esc_html__( 'Add CSS classes (seperated by a space) for this field to customize it\'s appearance in your theme.', 'charitable' ),
				)
			);

			?>

			<div class="charitable-panel-field charitable-panel-field-section" data-field-id="<?php echo intval( $field_id ); ?>">

				<?php do_action( 'charitable_builder_' . esc_attr( $this->type ) . '_settings_display_end', $field_id, $campaign_data ); ?>

			</div>

			<?php

			$html = ob_get_clean();

			remove_action( 'charitable_builder_' . esc_attr( $this->type ) . '_settings_display_start', [ $this, 'settings_section_top' ], 10 );
			remove_action( 'charitable_builder_' . esc_attr( $this->type ) . '_settings_display_end', [ $this, 'settings_section_bottom' ], 10 );

			return $html;
		}

		/**
		 * Display content above the content settings in the panel in the admin via hook.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id Field ID.
		 * @param array   $campaign_data Data on campaign.
		 */
		public function settings_section_top( $field_id = false, $campaign_data = false ) {

			$message = '<p>' . esc_html__( 'Don\'t see a social network here that you use and would like added?', 'charitable' ) . ' <a target="_blank" class="charitable-new-tab-link" data-type="' . esc_attr( $this->edit_type ) . '" data-section="' . $this->edit_section . '" href="https://wpcharitable.com/contact">' . esc_html__( 'Let us know!', 'charitable' ) . '</a></p>';

			echo $message; // phpcs:ignore.
		}

		/**
		 * Display content above the content settings in the panel in the admin via hook.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $field_id Field ID.
		 * @param array   $campaign_data Data on campaign.
		 */
		public function settings_section_bottom( $field_id = false, $campaign_data = false ) {
		}

		/**
		 * Enqueue frontend limit option js.
		 *
		 * @since 1.8.0
		 *
		 * @param array $forms Forms on the current page.
		 */
		public function frontend_js( $forms ) {
		}

		/**
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Social Sharing ID.
		 * @param mixed $field_submit Social Sharing value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Social Sharing ID.
		 * @param mixed $field_submit Social Sharing value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Return main list of social networks.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $campaign_data    Form data and settings.
		 * @param string $mode    Preview or template.
		 * @param string $suffix Any suffix.
		 */
		public function get_social_networks( $campaign_data = false, $mode = 'preview', $suffix = '' ) {

			return (array) apply_filters(
				'charitable_builder_social_sharing_networks',
				array(
					'twitter'   => array(
						'label'        => esc_html__( 'Twitter / X', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'twitter_url',
						'public_label' => esc_html__( 'Twitter / X', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/twitter' . $suffix . '.svg',
						'share_url'    => 'https://x.com/intent/post?text={$text}&url={$url}',
					),
					'facebook'  => array(
						'label'        => esc_html__( 'Facebook', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'facebook_url',
						'public_label' => esc_html__( 'Facebook', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/facebook' . $suffix . '.svg',
						'share_url'    => 'https://www.facebook.com/sharer/sharer.php?u={$url}',
					),
					'linkedin'  => array(
						'label'        => esc_html__( 'LinkedIn', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'linkedin_url',
						'public_label' => esc_html__( 'LinkedIn', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/linkedin' . $suffix . '.svg',
						'share_url'    => 'https://www.linkedin.com/sharing/share-offsite/?url={$url}',
					),
					'pinterest' => array(
						'label'        => esc_html__( 'Pinterest', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'pinterest_url',
						'public_label' => esc_html__( 'Pinterest', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/pinterest' . $suffix . '.svg',
						'share_url'    => 'https://www.pinterest.com/pin/create/button/?url={$url}&description={$text}',
					),
					'mastodon'  => array(
						'label'        => esc_html__( 'Mastodon', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'mastodon_url',
						'public_label' => esc_html__( 'Mastodon', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/mastodon' . $suffix . '.svg',
						'share_url'    => '',
						'on_click'     => 'charitableMastodonShare',
						'data'         => array(
							'src' => false,
						),
					),
					'threads'   => array(
						'label'        => esc_html__( 'Threads', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'threads_url',
						'public_label' => esc_html__( 'Threads', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/threads' . $suffix . '.svg',
						'share_url'    => 'https://www.threads.net/intent/post?text={$text}%0D%0A%0D%0A{$url}',
					),
					'bluesky'   => array(
						'label'        => esc_html__( 'Bluesky', 'charitable' ),
						'css_class'    => '',
						'field_id'     => 'bluesky_url',
						'public_label' => esc_html__( 'Bluesky', 'charitable' ),
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/bluesky' . $suffix . '.svg',
						'share_url'    => 'https://bsky.app/intent/compose?text={$text}%0D%0A%0D%0A{$url}',
					),

				),
				$campaign_data
			);
		}
	}

	new Charitable_Field_Social_Sharing();

endif;