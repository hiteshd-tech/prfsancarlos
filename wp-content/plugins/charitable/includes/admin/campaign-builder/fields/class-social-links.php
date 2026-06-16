<?php
/**
 * Class to add a social links area to a campaign form in the builder.
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

if ( ! class_exists( 'Charitable_Field_Social_Links' ) ) :

	/**
	 * Class to add campaign social links field to a campaign form in the builder.
	 */
	class Charitable_Field_Social_Links extends Charitable_Builder_Field {

		/**
		 * Primary class constructor.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Basic information.
			$this->name  = esc_html__( 'Social Links', 'charitable' );
			$this->type  = 'social-links';
			$this->icon  = 'fa-link';
			$this->order = 100;

			$this->align_default = 'left';

			// Edit/Duplication information.
			$this->can_be_edited     = true;
			$this->can_be_deleted    = true;
			$this->can_be_duplicated = true;
			$this->edit_label        = esc_html__( 'Edit Social Links', 'charitable' );
			$this->edit_type         = 'social-links';
			$this->edit_section      = 'standard';

			// Misc.
			$this->tooltip = '';

			// Define additional field properties.
			add_action( 'charitable_frontend_js', [ $this, 'frontend_js' ] );
		}

		/**
		 * Social Links options panel inside the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param array $field Social Links settings.
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

			$social_networks_settings = $field_id !== false && ! empty( $campaign_data['fields'] ) && ! empty( $campaign_data['fields'][ intval( $field_id ) ] ) ? $campaign_data['fields'][ intval( $field_id ) ] : false;
			$social_networks_settings = empty( $social_networks_settings ) && ! empty( $field_data ) ? $field_data : $social_networks_settings;
			$headline                 = ! empty( $field_data['headline'] ) ? esc_html( $field_data['headline'] ) : false;
			$headline                 = false === $headline ? esc_html__( 'Learn More:', 'charitable' ) : $headline;
			$open_new_tab             = ! empty( $field_data['open_new_tab'] ) ? esc_html( $field_data['open_new_tab'] ) : false;
			$new_tab                  = ( 1 === intval( $open_new_tab ) ) ? 'target="_blank"' : false;

			$html = '<div class="charitable-field-' . $mode . '-social-linking">';

			if ( trim( $headline ) !== '' ) {
				$html .= '<div class="charitable-field-' . $mode . '-social-linking-headline-container charitable-placeholder"><h5 class="charitable-field-' . $mode . '-headline">' . trim( $headline ) . '</h5></div>';
			}

			$html .= '<div class="charitable-field-row charitable-field-row-social-linking">';

			$social_networks_settings = $this->custom_sort_social_network_preview( $social_networks_settings );
			$suffix                   = ! empty( $template_data['meta']['suffixes'][ $this->type ] ) ? esc_attr( $template_data['meta']['suffixes'][ $this->type ] ) : false;
			$social_networks_template = ( $mode === 'template' ) ? $this->get_social_networks( $campaign_data, $mode, $suffix ) : false;

			foreach ( $social_networks_settings as $label => $value ) {

				$label = str_replace( '_url', '', esc_attr( $label ) );
				if ( ! array_intersect( array( 'twitter', 'facebook', 'linkedin', 'instagram', 'tiktok', 'pinterest', 'mastodon', 'youtube', 'threads', 'bluesky' ), array( $label ) ) ) {
					continue;
				}

				if ( '' !== trim( $value ) ) {

					if ( $mode === 'template' ) {

						$icon_url = ( ! empty( $social_networks_template[ $label ]['icon_url'] ) ) ? $social_networks_template[ $label ]['icon_url'] : charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/mastodon.svg'; // todo: replace with a genric icon of some sort.
						$link     = isset( $field_data[ $label . '_url' ] ) ? esc_url( $field_data[ $label . '_url' ] ) : false;

						if ( $link ) {

							$html .= '<div class="charitable-social-field-column charitable-social-linking-' . $mode . '-' . $label . '">
									<div class="charitable-campaign-social-link" data-social-network="' . $label . '">
										<a href="' . esc_url( $link ) . '" ' . $new_tab . '>
											<div><img title="' . $social_networks_template[ $label ]['public_label'] . '"  src="' . $icon_url . '" alt="' . $social_networks_template[ $label ]['public_label'] . '" /></div>
											<p>' . $social_networks_template[ $label ]['public_label'] . '</p>
										</a>
									</div>
								</div>';

						}
					} else {
						$html .= '<div class="charitable-social-field-column charitable-social-linking-' . $mode . '-' . $label . '">
								<span class="charitable-placeholder"></span>
							</div>';
					}
				} elseif ( $mode === 'preview' ) {
					$html .= '<div class="charitable-social-field-column charitable-hidden charitable-social-linking-' . $mode . '-' . $label . '">
							<span class="charitable-placeholder"></span>
						</div>';
				}
			}

			if ( 'template' === $mode && empty( array_filter( $social_networks_settings ) ) ) { // phpcs:ignore

			} else {

				$hide_class = ! empty( array_filter( $social_networks_settings ) ) ? 'charitable-hidden' : false;

				$html .= '<div class="charitable-social-linking-no-links ' . $hide_class . '">
							<p><em>' . esc_html__( 'Add a url to a social network in the field settings on the left to see icons start to appear here.', 'charitable' ) . '</em></p>
						</div>';
			}

			$html .= '</div>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Social Links preview inside the builder.
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
		 * @param string $field_type     The passed field type.
		 * @param array  $field_data     Any field data.
		 * @param array  $campaign_data  Amount data and settings.
		 * @param bool   $is_preview_page If the page is a preview page.
		 * @param int    $field_id       The field ID.
		 * @param array  $template_data  Template data.
		 */
		public function field_display( $field_type = '', $field_data = false, $campaign_data = false, $is_preview_page = false, $field_id = false, $template_data = false ) {

			$html = $this->field_display_wrapper( $this->render( $field_data, $campaign_data, $field_id, 'template', $template_data ), $field_data );

			echo apply_filters( 'charitable_campaign_builder_' . $this->type . '_field_display', $html, $campaign_data ); // phpcs:ignore
		}

		/**
		 * The display on the form settings backend when the user clicks on the field/block.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id       Field ID.
		 * @param array $campaign_data  Campaign data and settings.
		 */
		public function settings_display( $field_id = false, $campaign_data = false ) {

			if ( ! class_exists( 'Charitable_Builder_Form_Fields' ) ) {
				return;
			}

			$charitable_builder_form_fields = new Charitable_Builder_Form_Fields();

			$settings        = isset( $campaign_data['fields'][ $field_id ] ) ? $campaign_data['fields'][ $field_id ] : false;
			$social_html     = array();
			$social_networks = $this->get_social_networks( $campaign_data );

			if ( empty( $social_networks ) ) {
				return;
			}

			add_action( 'charitable_builder_' . $this->type . '_settings_display_start', [ $this, 'settings_section_top' ], 10, 2 );
			add_action( 'charitable_builder_' . $this->type . '_settings_display_end', [ $this, 'settings_section_bottom' ], 10, 2 );

			ob_start();

			?>

		<h4 class="charitable-panel-field" data-field-id="<?php echo intval( $field_id ); ?>"><?php echo esc_html( $this->name ); ?> (ID: <?php echo intval( $field_id ); ?>)</h4>

			<div class="charitable-panel-field charitable-panel-field-section" data-field-id="<?php echo intval( $field_id ); ?>">

				<?php do_action( 'charitable_builder_' . $this->type . '_settings_display_start', $field_id, $campaign_data ); ?>

			</div>

			<?php

			echo $charitable_builder_form_fields->generate_text( // phpcs:ignore
				isset( $settings['headline'] ) ? $settings['headline'] : esc_html__( 'Learn More:', 'charitable' ),
				esc_html__( 'Headline', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_headline' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'headline' ),
					'field_id' => intval( $field_id ),
					'class'    => 'charitable-campaign-builder-headline',
				)
			);

			// Go through all the social networks.

			foreach ( $social_networks as $social_id => $data ) :

				$social_html[] = $charitable_builder_form_fields->generate_text(
					isset( $settings[ $data['field_id'] ] ) ? $settings[ $data['field_id'] ] : false,
					$data['label'],
					array(
						'id'          => 'field_' . esc_attr( $this->type ) . '_' . $data['field_id'] . '_' . intval( $field_id ), // phpcs:ignore
						'name'        => array( '_fields', intval( $field_id ), $data['field_id'] ),
						'field_id'    => $field_id,
						'type'        => 'url',
						'class'       => 'charitable-campaign-builder-social-links-text-field',
						'placeholder' => 'https://',
					)
				);

				endforeach;

			$social_html = apply_filters( 'charitable_campaign_builder_social_links_html', $social_html );

			if ( empty( $social_html ) || ! is_array( $social_html ) ) {
				return;
			}

			foreach ( $social_html as $html ) {
				echo $html; // phpcs:ignore
			}

			echo $charitable_builder_form_fields->generate_toggle( // phpcs:ignore
				isset( $settings['open_new_tab'] ) ? $settings['open_new_tab'] : false,
				esc_html__( 'Open Links In New Tab', 'charitable' ),
				array(
					'id'       => 'field_' . esc_attr( $this->type ) . '_open_new_tab' . '_' . intval( $field_id ), // phpcs:ignore
					'name'     => array( '_fields', intval( $field_id ), 'open_new_tab' ),
					'field_id' => intval( $field_id ),
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
					'field_id'   => intval( $field_id ),
					'symbol'     => '%',
					'min'        => 0,
					'min_actual' => 50,
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
					'default'  => 'left',
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

				<?php do_action( 'charitable_builder_' . $this->type . '_settings_display_end', $field_id, $campaign_data ); ?>

			</div>

			<?php

			remove_action( 'charitable_builder_' . $this->type . '_settings_display_start', [ $this, 'settings_section_top' ], 10 );
			remove_action( 'charitable_builder_' . $this->type . '_settings_display_end', [ $this, 'settings_section_bottom' ], 10 );

			$html = ob_get_clean();

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

			echo $message; // phpcs:ignore
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
		 * @param int   $field_id     Social Links ID.
		 * @param mixed $field_submit Social Links value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function format( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Custom sort the array on the preview side.
		 *
		 * @since 1.8.0
		 *
		 * @param array $social_networks_settings Social networks.
		 */
		public function custom_sort_social_network_preview( $social_networks_settings ) {

			$updated_settings = array(
				'twitter_url'   => isset( $social_networks_settings['twitter_url'] ) ? esc_url( $social_networks_settings['twitter_url'] ) : '',
				'facebook_url'  => isset( $social_networks_settings['facebook_url'] ) ? esc_url( $social_networks_settings['facebook_url'] ) : '',
				'linkedin_url'  => isset( $social_networks_settings['linkedin_url'] ) ? esc_url( $social_networks_settings['linkedin_url'] ) : '',
				'instagram_url' => isset( $social_networks_settings['instagram_url'] ) ? esc_url( $social_networks_settings['instagram_url'] ) : '',
				'tiktok_url'    => isset( $social_networks_settings['tiktok_url'] ) ? esc_url( $social_networks_settings['tiktok_url'] ) : '',
				'pinterest_url' => isset( $social_networks_settings['pinterest_url'] ) ? esc_url( $social_networks_settings['pinterest_url'] ) : '',
				'mastodon_url'  => isset( $social_networks_settings['mastodon_url'] ) ? esc_url( $social_networks_settings['mastodon_url'] ) : '',
				'youtube_url'   => isset( $social_networks_settings['youtube_url'] ) ? esc_url( $social_networks_settings['youtube_url'] ) : '',
				'threads_url'   => isset( $social_networks_settings['threads_url'] ) ? esc_url( $social_networks_settings['threads_url'] ) : '',
				'bluesky_url'   => isset( $social_networks_settings['bluesky_url'] ) ? esc_url( $social_networks_settings['bluesky_url'] ) : '',
			);

			return apply_filters( 'charitable_campaign_social_links_networks_preview', $updated_settings, $social_networks_settings );
		}

		/**
		 * Format and sanitize field.
		 *
		 * @since 1.8.0
		 *
		 * @param string $social_network Text slug of social network.
		 */
		public function format_share_link( $social_network = false ) {

			if ( false === $social_network ) {
				return;
			}

			global $post;

			$url = ( get_permalink( $post->ID ) );

			switch ( $social_network ) {
				case 'twitter':
					$link = 'http://twitter.com/share?text=&url=' . esc_url( $url ) . ''; // &hashtags=hashtag1,hashtag2,hashtag3
					break;
				case 'facebook':
					$link = 'https://www.facebook.com/sharer/sharer.php?u=' . esc_url( $url );
					break;
				case 'linkedin':
					$link = 'https://www.linkedin.com/links/share-offsite/?url=' . esc_url( $url );
					break;
				case 'instagram':
					$link = 'https://www.instagram.com/?url=' . esc_url( $url );
					break;
				case 'tiktok':
					$link = 'https://www.tiktok.com/?url=' . esc_url( $url );
					break;
				case 'pinterest':
					$link = 'https://pinterest.com/pin/create/button/?url=' . esc_url( $url );
					break;
				case 'bluesky':
					$link = 'https://bsky.app/intent/compose?text=' . esc_url( $url );
					break;
				case 'mastodon':
					$link = esc_url( $url );
					break;
				default:
					$link = false;
					break;
			}

			return apply_filters( 'charitable_campaign_social_links_network_link', $link, $social_network );
		}

		/**
		 * Validate field on form submit.
		 *
		 * @since 1.8.0
		 *
		 * @param int   $field_id     Social Links ID.
		 * @param mixed $field_submit Social Links value that was submitted.
		 * @param array $campaign_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $campaign_data ) {
		}

		/**
		 * Return main list of social networks.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $campaign_data   Form data and settings.
		 * @param string $mode           Where the field is being displayed ("preview" or "template").
		 * @param string $suffix         Suffix for the icon.
		 */
		public function get_social_networks( $campaign_data = false, $mode = 'preview', $suffix = '' ) {

			return (array) apply_filters(
				'charitable_builder_social_links_networks',
				array(
					'twitter'   => array(
						'label'        => 'Twitter / X URL',
						'field_id'     => 'twitter_url',
						'public_label' => 'Twitter / X',
						'icon'         => 'fa-brands fa-twitter',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/twitter' . $suffix . '.svg',
					),
					'facebook'  => array(
						'label'        => 'Facebook URL',
						'field_id'     => 'facebook_url',
						'public_label' => 'Facebook',
						'icon'         => 'fa-brands fa-facebook',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/facebook' . $suffix . '.svg',
					),
					'linkedin'  => array(
						'label'        => 'LinkedIn URL',
						'field_id'     => 'linkedin_url',
						'public_label' => 'LinkedIn',
						'icon'         => 'fa-brands fa-linkedin',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/linkedin' . $suffix . '.svg',
					),
					'instagram' => array(
						'label'        => 'Instagram URL',
						'field_id'     => 'instagram_url',
						'public_label' => 'Instagram',
						'icon'         => 'fa-brands fa-instagram',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/instagram' . $suffix . '.svg',
					),
					'tiktok'    => array(
						'label'        => 'TikTok URL',
						'field_id'     => 'tiktok_url',
						'public_label' => 'TikTok',
						'icon'         => 'fa-brands fa-tiktok',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/tiktok' . $suffix . '.svg',
					),
					'pinterest' => array(
						'label'        => 'Pinterest URL',
						'field_id'     => 'pinterest_url',
						'public_label' => 'Pinterest',
						'icon'         => 'fa-brands fa-pinterest',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/pinterest' . $suffix . '.svg',
					),
					'youtube'   => array(
						'label'        => 'YouTube URL',
						'field_id'     => 'youtube_url',
						'public_label' => 'YouTube',
						'icon'         => 'fa-brands fa-youtube',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/youtube' . $suffix . '.svg',
					),
					'threads'   => array(
						'label'        => 'Threads URL',
						'field_id'     => 'threads_url',
						'public_label' => 'Threads',
						'icon'         => 'fa-brands fa-threads',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/threads' . $suffix . '.svg',
					),
					'bluesky'   => array(
						'label'        => 'Bluesky URL',
						'field_id'     => 'bluesky_url',
						'public_label' => 'Bluesky',
						'icon'         => 'fa-brands fa-bluesky',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/bluesky' . $suffix . '.svg',
					),
					'mastodon'  => array(
						'label'        => 'Mastodon URL',
						'field_id'     => 'mastodon_url',
						'public_label' => 'Mastodon',
						'icon'         => 'fa-brands fa-mastodon',
						'icon_url'     => charitable()->get_path( 'directory', false ) . 'assets/images/campaign-builder/fields/social-sharing/mastodon' . $suffix . '.svg',
					),
				),
				$campaign_data
			);
		}
	}

	new Charitable_Field_Social_Links();

endif;