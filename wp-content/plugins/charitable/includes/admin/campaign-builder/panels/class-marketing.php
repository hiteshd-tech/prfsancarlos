<?php
/**
 * Marketing class management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Marketing' ) ) :

	/**
	 * Design management panel.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Marketing extends Charitable_Builder_Panel {

		/**
		 * Form data and marketing.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * Panels for the submenu campaign builder.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $submenu_panels;

		/**
		 * All systems go.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define panel information.
			$this->name    = esc_html__( 'Marketing', 'charitable' );
			$this->slug    = 'marketing';
			$this->icon    = 'panel_marketing.svg';
			$this->order   = 40;
			$this->sidebar = true;

			// This should never be called unless we are on the campaign builder page.
			if ( campaign_is_campaign_builder_admin_page() ) {
				$this->load_submenu_panels();
			}
		}

		/**
		 * Enqueue assets for the Design panel.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {
			// Add custom CSS for the upgrade button
			$this->add_custom_styles();
		}

		/**
		 * Add custom CSS styles for the upgrade button.
		 *
		 * @since 1.8.0
		 */
		private function add_custom_styles() {
			?>
			<style>
				.charitable-upgrade-to-pro-button {
					background-color: #df7739 !important;
					color: #ffffff !important;
					border: 2px solid #df7739 !important;
					padding: 10px 20px !important;
					border-radius: 5px !important;
					text-decoration: none !important;
					display: inline-block !important;
					font-weight: 600 !important;
					transition: all 0.3s ease !important;
				}

				.charitable-upgrade-to-pro-button:hover {
					background-color: #c66a32 !important;
					border-color: #c66a32 !important;
					color: #ffffff !important;
					transform: translateY(-2px) !important;
					box-shadow: 0 4px 8px rgba(223, 119, 57, 0.3) !important;
				}
			</style>
			<?php
		}

		/**
		 * Load panels.
		 *
		 * @since 1.8.0
		 */
		public function load_submenu_panels() {

			$this->submenu_panels = apply_filters(
				'charitable_builder_panels_marketing_',
				array(
					'mailchimp',
					'active-campaign',
					'campaign-monitor',
					'mailerlite',
					'mailpoet',
					'mailster',
					'constant-contact',
					'zapier',
					'hubspot',
					'aweber',
					'convert-kit',
					'integromat',
					'zoho-flow',
					'request',
				)
			);

			foreach ( $this->submenu_panels as $panel ) {
				$panel = sanitize_file_name( $panel );
				$file  = charitable()->get_path( 'includes' ) . "admin/campaign-builder/panels/marketing/class-marketing-{$panel}.php";

				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
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

			do_action( 'charitable_campaign_builder_marketing_sidebar', $this->campaign_data );
		}

		/**
		 * Process marketing for campaigns, mostly via the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field Field.
		 * @param string $section Marketing section.
		 * @param string $top_level Level.
		 * @param string $meta_key Alt legacy location to try to pull marketing.
		 */
		public function campaign_data_marketing( $field = 'title', $section = 'general', $top_level = 'marketing', $meta_key = false ) {
		}

		/**
		 * Process education marketing text.
		 *
		 * @since 1.8.0
		 *
		 * @param string $label Reader friendly output of object.
		 * @param string $slug Object slug.
		 * @param string $type Type of request.
		 */
		public function education_marketing_text( $label = false, $slug = false, $type = false ) {

			$learn_more_url = charitable_ga_url(
				'https://wpcharitable.com/lite-vs-pro/', // base url.
				rawurlencode( esc_html( $label ) . ' Marketing Page' ), // utm-medium.
				rawurlencode( 'Learn More' ) // utm-content.
			);

			$action_css_class  = '';
			$button_label      = '';
			$addon_url         = '';
			$settings_url      = '';
			$addon_information = array(
				'install' => '',
				'name'    => '',
			);

			?>

			<?php

			echo '<img class="charitable-builder-sidebar-icon" src="' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/marketing/' . esc_attr( $slug ) . '_big.png" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			?>

			<section class="header-content">

				<?php

				if ( 'charitable-automation-connect' === $type ) :

					?>

					<?php echo esc_html__( 'Turn one-time donors into ongoing supporters with', 'charitable' ); ?> <span><?php echo esc_html( $label ); ?></span>.</h2>

					<p><strong><?php echo esc_html__( 'Automation Connect', 'charitable' ); ?></strong> <?php echo esc_html__( 'addon allows you to connect', 'charitable' ); ?> <strong><?php echo esc_html__( 'Charitable', 'charitable' ); ?></strong> <?php echo esc_html__( 'with thousands of other apps including', 'charitable' ); ?> <strong><?php echo esc_html( $label ); ?></strong> <?php echo esc_html__( 'by using 3rd party automation platforms like Zapier, Integromat or Zoho Flow.', 'charitable' ); ?></p>

			<?php elseif ( 'request' === $type ) : ?>

				<h2><?php echo esc_html__( 'Don\'t see an integration with', 'charitable' ); ?> <strong><?php echo esc_html__( 'Charitable', 'charitable' ); ?></strong> <?php echo esc_html__( 'and your favorite product or service?', 'charitable' ); ?></h2>
				<h2><?php echo esc_html__( 'Let us know!', 'charitable' ); ?></h2>

			<?php else : ?>

				<h2><?php echo esc_html__( 'Turn one-time donors into ongoing supporters with ', 'charitable' ); ?> <span><?php echo esc_html( $label ); ?></span>.</h2>

				<p><?php echo esc_html( $label ); ?><?php echo esc_html__( '\'s affordable pricing and excellent reputation have made it the go-to hosted email marketing provider for more millions of people.', 'charitable' ); ?> <?php echo esc_html__( 'Charitable Pro', 'charitable' ); ?> <?php echo esc_html__( 'allows you to integrate seamlessly with ', 'charitable' ); ?><span><?php echo esc_html( $label ); ?></span>.</p>


			</section>

			<?php endif; ?>


				<?php

				if ( 'charitable-automation-connect' === $type ) :

					?>

				<div class="education-buttons education-buttons-top">
					<a class="button-link" target="_blank" href="<?php echo esc_url( $learn_more_url ); ?>"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a> <button type="button" class="btn btn-confirm update-to-pro-link"><?php echo esc_html__( 'Upgrade to PRO', 'charitable' ); ?></button>
				</div>

				<div class="education-images type-custom type-<?php echo esc_attr( $type ); ?>">

					<img class="charitable-marketing-image charitable-marketing-image-1 charitable-marketing-image-<?php echo esc_attr( $slug ); ?>" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/marketing/education/' . esc_attr( $slug ) . '-1.png' ); ?>" alt="<?php echo esc_html( $label ); ?>" />

				</div>

				<section class="main-content">

					<p><?php echo esc_html__( 'Upgrading to <strong>Pro</strong> gives you great capabilities to get more donations...', 'charitable' ); ?></p>

					<ul>
						<li><?php echo esc_html__( 'Give your donors an attractive end of financial year receipt.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Fine-tune the processing fees by setting a fixed fee per donation.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Allow users to raise donations and gain incredible support for their cause.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Boost your fundraising with recurring donations.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Set a location for your campaigns, and display them on a Google Map.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Developer-friendly automation addon that will help you customize it to your needs.', 'charitable' ); ?></li>
					</ul>

				</section>

					<?php
			elseif ( 'request' === $type ) :

				// attempt to prefill name and email fields.
				$current_user = wp_get_current_user();
				$name         = ( $current_user ) ? charitable_get_creator_data() : false;
				$email        = ! empty( $current_user->user_email ) ? $current_user->user_email : false;

				?>

				<!-- start feedback form -->

				<div class="charitable-feedback-form-container-marketing">

					<div id="charitable-marketing-form" class="charitable-form charitable-feedback-form">

						<div class="charitable-feedback-form-interior">

							<input type="hidden" class="charitable-feedback-form-type" value="marketing" />

							<div class="charitable-form-row charitable-feedback-form-row">
								<label><?php echo esc_html__( 'Name', 'charitable' ); ?>  <span class="charitable-feedback-form-required">*</span></label>
								<input name="charitable-feedback-form-name" type="text" class="charitable-feedback-form-name" value="<?php echo esc_html( $name ); ?>" />
							</div>
							<div class="charitable-form-row charitable-feedback-form-row">
								<label><?php echo esc_html__( 'Email', 'charitable' ); ?> <span class="charitable-feedback-form-required">*</span></label>
								<input name="charitable-feedback-form-email" type="email" class="charitable-feedback-form-email" value="<?php echo esc_html( $email ); ?>" />
							</div>
							<div class="charitable-form-row charitable-feedback-form-row">
								<label><?php echo esc_html__( 'What intergration(s) would you like to see?', 'charitable' ); ?><span class="charitable-feedback-form-required">*</span></label>
								<textarea name="charitable-feedback-form-feedback" class="charitable-feedback-form-feedback"></textarea>
							</div>
							<div class="charitable-form-row charitable-feedback-form-row">
								<p class="charitable-feedback-form-required">* = <?php echo esc_html__( 'Required', 'charitable' ); ?></p>
							</div>
							<div class="charitable-form-row charitable-feedback-form-row">
								<a class="button-link"><?php echo esc_html__( 'Send Request', 'charitable' ); ?></a>
							</div>
							<i class="charitable-loading-spinner charitable-loading-black charitable-loading-inline charitable-hidden"></i>

						</div>

						<div class="charitable-feedback-form-interior-confirmation">

							<!-- confirmation -->
							<div id="charitable-feedback-form-confirmation" class="charitable-form charitable-feedback-form charitable-form-confirmation charitable-hidden">
								<h2><?php echo esc_html__( 'Thank You!', 'charitable' ); ?></h2>
								<p><?php echo esc_html__( 'Your feedback has been sent to our team. Stay tuned to our updates on', 'charitable' ); ?> <a href="https://wpcharitable.com" target="_blank">wpcharitable.com</a>.</p>
							</div>

						</div>

					</div>

				</div>

				<!-- end feedback form -->

				<section class="main-content">

					<?php if ( ! charitable_is_pro() ) { ?>

					<p><?php echo esc_html__( 'Upgrading to', 'charitable' ); ?> <strong><?php echo esc_html__( 'Pro', 'charitable' ); ?></strong> <?php echo esc_html__( 'gives you the following capabilities...', 'charitable' ); ?></p>

					<ul>
						<li><?php echo esc_html__( 'Set a default list, opt-in mode and label that is used for all campaigns.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Have donors subscribed to multiple lists when they donate.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Allow donors automatically added to your mailing list when they give their consent.', 'charitable' ); ?></li>
						<li><?php echo esc_html__( 'Create your own custom / merge fields and map these fields to your donation forms.', 'charitable' ); ?></li>
					</ul>

					<h2><?php echo esc_html__( 'Get the most out of your campaigns with ', 'charitable' ); ?><strong><?php echo esc_html__( 'Pro', 'charitable' ); ?></strong>.</h2>

						<p style="margin-top: 20px;"><a class="button-link" href="<?php echo esc_url( $learn_more_url ); ?>"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a></p>

					<?php } ?>

				</section>

				<?php

			endif;
			?>

			<?php if ( $button_label ) : ?>

			<div class="education-buttons education-buttons-bottom">
				<div class="action-button">
					<?php echo '<a class="button-link ' . esc_attr( $action_css_class ) . '" data-settings-url="' . esc_url( $settings_url ) . '" data-plugin-url="' . esc_attr( $addon_information['install'] ) . '" data-name="' . esc_attr( $addon_information['name'] ) . '" data-plugin-slug="' . esc_url( $addon_url ) . '" data-field-icon="">' . esc_html( $button_label ) . '</a>'; ?>
				</div>
			</div>

			<?php endif; ?>



			<?php
		}

		/**
		 * Process education marketing text.
		 *
		 * @since 1.8.1.4
		 *
		 * @param string $marketing_item_label Marketing item label. Example: Mailchimp.
		 * @param string $marketing_item_slug Marketing item slug. Exmaple: campaign-monitor.
		 * @param string $addon_name Addon name.
		 * @param string $button_label Button label.
		 * @param string $button_url Button URL.
		 * @param string $addon_url Addon URL.
		 * @param bool   $generic_button Whether to use a generic button label. Default is false.
		 */
		public function plugin_activated_text( $marketing_item_label = false, $marketing_item_slug = false, $addon_name = '', $button_label = 'View Settings', $button_url = '', $addon_url = '', $generic_button = false ) {

			echo '<img class="charitable-builder-sidebar-icon" src="' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/marketing/' . esc_attr( $marketing_item_slug ) . '_big.png" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			?>

			<section class="header-content">

					<h2>
					<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'You have the %s addon activated.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>' );
					?>
					</h2>

					<?php

					if ( strpos( $addon_url, 'charitable-automation-connect' ) !== false ) :

						$direct_automation_services = array( 'zapier', 'integromat', 'zoho-flow', 'automateio' );

						if ( in_array( $marketing_item_slug, $direct_automation_services, true ) ) :

							?>

						<p>
							<?php
							// translators: %1$s is the name of the addon, %2$s is the marketing item label, %3$s is the link to the documentation.
							printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s. %3$s for assistance or click the button below to access your webhook settings.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>', '<a href="https://www.wpcharitable.com/documentation/charitable-automation-connect/" target="_blank">View our documentation</a>' );
							?>
						</p>

						<?php else : ?>
						<p>
							<?php
							// translators: %1$s is the name of the addon, %2$s is the marketing item label, %3$s is the service name, %4$s is the link to the documentation.
							printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s via services such as %3$s. %4$s for assistance or click the button below to access your webhook settings.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>', '<strong>Zapier</strong>', '<a href="https://www.wpcharitable.com/documentation/charitable-automation-connect/" target="_blank">View our documentation</a>' );
							?>
						</p>

						<?php endif; ?>

					<?php else : ?>

					<p>
						<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>' );
						?>
					</p>

					<?php endif; ?>


			</section>


			<div class="education-buttons education-buttons-bottom">

				<?php

					$button_label = $generic_button ? $button_label : $button_label . ' ' . $marketing_item_label;

					echo '<a class="button-link" href="' . esc_url( $button_url ) . '" target="_blank">' . esc_html( $button_label ) . '</a>';

				?>

			</div>

			<?php
		}

		/**
		 * Process installed text related to plugin installed/installing.
		 *
		 * @since 1.8.1.12
		 *
		 * @param string $marketing_item_label Marketing item label. Example: Mailchimp.
		 * @param string $marketing_item_slug Marketing item slug. Exmaple: campaign-monitor.
		 * @param string $addon_name Addon name.
		 * @param string $button_label Button label.
		 * @param string $button_url Button URL.
		 * @param string $addon_url Addon URL.
		 * @param bool   $generic_button Whether to use a generic button label. Default is false.
		 */
		public function plugin_installed_text( $marketing_item_label = false, $marketing_item_slug = false, $addon_name = '', $button_label = 'View Settings', $button_url = '', $addon_url = '', $generic_button = false ) {

			$learn_more_url = charitable_ga_url(
				'https://wpcharitable.com/lite-vs-pro/', // base url.
				rawurlencode( esc_html( $marketing_item_label ) . ' Marketing Page' ), // utm-medium.
				rawurlencode( 'Learn More' ) // utm-content.
			);

			echo '<img class="charitable-builder-sidebar-icon" src="' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/marketing/' . esc_attr( $marketing_item_slug ) . '_big.png" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// Determine if the plugin in question is installed and/or activated.
			$status       = $this->check_plugin_status( $addon_url );
			$button_label = '';
			switch ( $status ) {
				case 'installed':
					$button_label     = esc_html__( 'Activate', 'charitable' );
					$action_css_class = 'charitable-not-activated-button';
					break;
				case 'activated':
					$button_label     = esc_html__( 'View Settings', 'charitable' );
					$action_css_class = 'charitable-view-settings-button';
					break;
				default:
					// doesn't exist.
					$button_label     = esc_html__( 'Install And Activate', 'charitable' );
					$action_css_class = 'charitable-not-installed-button';
					break;
			}

			// Determine the settings_url.
			$settings_url = false;
			if ( strpos( $addon_url, 'charitable-automation-connect' ) !== false ) {
				$settings_url = admin_url( 'admin.php?page=charitable-settings&tab=advanced' );
			} elseif ( strpos( $addon_url, 'charitable-newsletter' ) !== false ) {
				$settings_url = admin_url( 'admin.php?page=charitable-settings&tab=extensions' );
			}

			// get license stuff once.
			$charitable_addons = get_transient( '_charitable_addons' ); // @codingStandardsIgnoreLine - testing.

			// Get addons data from transient or perform API query if no transient.
			if ( false === $charitable_addons ) {
				$charitable_addons = charitable_get_addons_data_from_server();
			}

			if ( empty( $addon_url ) ) {
				return;
			}

			$addon_url_slug    = explode( '/', $addon_url );
			$addon_url_slug    = $addon_url_slug[0]; // example: charitable-newsletter-connect.
			$addon_information = array();

			if ( ! empty( $charitable_addons ) ) {
				// Extract the charitable-newsletter-connect data.
				foreach ( $charitable_addons as $addon ) {
					if ( (string) $addon_url_slug === (string) $addon['slug'] ) {
						$addon_information = $addon;
						break;
					}
				}
			}

			$addon_information_install = ! empty( $addon_information['install'] ) ? $addon_information['install'] : '';
			$addon_information_name    = ! empty( $addon_information['name'] ) ? $addon_information['name'] : '';

			?>

			<section class="header-content">

					<h2 class="charitable-header-content-installed
					<?php
					if ( 'installed' !== $status ) :
						?>
						charitable-hidden<?php endif; ?>">
						<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'You have the %s addon installed, but not activated.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>' );
						?>
					</h2>
					<h2 class="charitable-header-content-activated
					<?php
					if ( 'activated' !== $status ) :
						?>
						charitable-hidden<?php endif; ?>">
						<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'You have the %s addon installed and activated.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>' );
						?>
					</h2>
					<h2 class="charitable-header-content-nonexist
					<?php
					if ( 'nonexist' !== $status ) :
						?>
						charitable-hidden<?php endif; ?>">
						<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'You need to install the %s addon.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>' );
						?>
					</h2>

					<?php

					if ( strpos( $addon_url, 'charitable-automation-connect' ) !== false ) :

						$direct_automation_services = array( 'zapier', 'integromat', 'zoho-flow', 'automateio' );

						if ( in_array( $marketing_item_slug, $direct_automation_services, true ) ) :

							?>

						<p>
							<?php
							// translators: %1$s is the name of the addon, %2$s is the marketing item label, %3$s is the link to the documentation.
							printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s. %3$s for assistance.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>', '<a href="https://www.wpcharitable.com/documentation/charitable-automation-connect/" target="_blank">View our documentation</a>' );
							?>
						</p>

						<?php else : ?>
						<p>
							<?php
							// translators: %1$s is the name of the addon, %2$s is the marketing item label, %3$s is the service name, %4$s is the link to the documentation.
							printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s via services such as %3$s. %4$s for assistance.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>', '<strong>Zapier</strong>', '<a href="https://www.wpcharitable.com/documentation/charitable-automation-connect/" target="_blank">View our documentation</a>' );
							?>
						</p>

						<?php endif; ?>

					<?php else : ?>

					<p>
						<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>' );
						?>
					</p>

					<?php endif; ?>


			</section>

			<?php if ( ! charitable_is_pro() ) : ?>

			<div class="education-buttons education-buttons- bottom">

				<div class="action-button">
					<?php
					// Check if this is MailChimp specifically and show upgrade button instead of install button
					if ( strpos( $addon_url, 'charitable-newsletter-connect' ) !== false && $marketing_item_slug === 'mailchimp' ) {
						echo '<a class="button-link charitable-upgrade-to-pro-button" target="_blank" href="https://wpcharitable.com/lite-vs-pro/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Upgrade+From+Lite+Top+Banner+Link&utm_content=To+unlock+more+features+consider+upgrading+to+Pro">' . esc_html__( 'Upgrade to PRO', 'charitable' ) . '</a>';
					} else {
						echo '<a class="button-link ' . esc_attr( $action_css_class ) . '" data-settings-url="' . esc_url( $settings_url ) . '" data-plugin-url="' . esc_attr( $addon_information_install ) . '" data-name="' . esc_attr( $addon_information_name ) . '" data-plugin-slug="' . esc_attr( $addon_url ) . '" data-field-icon="">' . esc_html( $button_label ) . '</a>';
					}
					?>
				</div>

			</div>

			<?php else : ?>

				<div class="education-buttons education-buttons-top">
					<a class="btn button-link update-to-pro-link" target="_blank" href="<?php echo esc_url( $learn_more_url ); ?>"><?php echo esc_html__( 'Upgrade to PRO', 'charitable' ); ?></a>
				</div>

			<?php endif; ?>

			<?php
		}

		/**
		 * Get button label and URL if the newsletter adoon is active.
		 *
		 * @since 1.8.1.4
		 *
		 * @param string $provider_slug Provider slug.
		 * @param string $button_url Default Button URL.
		 * @param string $button_label Default Button label.
		 *
		 * @return array
		 */
		public function get_newsletter_button_info( $provider_slug = '', $button_url = '', $button_label = '' ) {

			$button_info = array(
				'button_url'   => $button_url,
				'button_label' => $button_label,
			);

			if ( class_exists( 'Charitable_Newsletter_Connect_Providers' ) ) :

				$helper    = Charitable_Newsletter_Connect_Providers::get_instance(); // phpcs:ignore
				$providers = $helper->get_available_providers();
				$found     = false;

				foreach ( $providers as $provider ) :

					if ( $provider::ID !== $provider_slug ) {
						continue;
					}

					$provider  = new $provider();
					$is_active = $provider->is_active();

					if ( $provider->is_a_wordpress_provider() && ! $is_active ) :
						if ( $provider->is_installed() ) :

							$found       = true;
							$action_slug = 'activate';
							$button_url  = esc_url(
								add_query_arg(
									[
										'charitable_action' => 'activate_wp_provider',
										'plugin' => $provider->get_plugin_key(),
										'_nonce' => wp_create_nonce( 'wp-provider' ),
									],
									admin_url( 'admin.php?page=charitable-settings&tab=extensions' )
								)
							);

						else :
							$found       = true;
							$action_slug = 'install';
							$button_url  = esc_url( $provider->get_install_link() );

						endif;
					elseif ( count( $provider->provider_settings() ) ) :

						$found           = true;
							$action_slug = 'configure';
							$button_url  = esc_url(
								add_query_arg(
									[
										'group' => 'providers_' . $provider::ID,
									],
									admin_url( 'admin.php?page=charitable-settings&tab=extensions' )
								)
							);

					endif;

				endforeach;

				if ( $found ) :
					$button_info = array(
						'button_url'   => $button_url,
						'button_label' => ucwords( $action_slug ),
					);
				endif;

			endif;

			return $button_info;
		}

		/**
		 * Output the Field panel primary content.
		 *
		 * @since 1.8.0
		 */
		public function panel_content() {

			// This should never be called unless we are on the campaign builder page.
			if ( ! campaign_is_campaign_builder_admin_page() ) {
				return;
			}

			do_action( 'charitable_campaign_builder_marketing_panels', $this->campaign_data );
		}

		/**
		 * Builder field buttons.
		 *
		 * @since 1.8.0
		 */
		public function fields() {
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
		 * Check the installation and activation status of a plugin.
		 *
		 * @since 1.8.1.12
		 *
		 * @param string $plugin_slug The plugin slug, e.g., 'my-plugin/my-plugin.php'.
		 *
		 * @return string Status message indicating plugin state.
		 */
		public function check_plugin_status( $plugin_slug = '' ) {

			if ( '' === $plugin_slug ) {
				return 'nonexist';
			}

			// Check if the plugin is installed.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins      = get_plugins();
			$plugin_installed = array_key_exists( $plugin_slug, $all_plugins );

			if ( $plugin_installed && is_plugin_active( $plugin_slug ) ) {
				return 'activated';
			} elseif ( $plugin_installed ) {
				return 'installed';
			} else {
				return 'nonexist';
			}
		}
	}

endif;

new Charitable_Builder_Panel_Marketing();
