<?php
/**
 * Payment class management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.1.4
 * @version   1.8.7.1 - Added Square to the list of gateways that are built in.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Payment' ) ) :

	/**
	 * Design management panel.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Payment extends Charitable_Builder_Panel {

		/**
		 * Form data and payment.
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
			$this->name    = esc_html__( 'Payment', 'charitable' );
			$this->slug    = 'payment';
			$this->icon    = 'panel_payment.svg';
			$this->order   = 50;
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
		}

		/**
		 * Load panels.
		 *
		 * @since   1.8.0
		 * @version 1.8.7.1 - Square below Stripe.
		 */
		public function load_submenu_panels() {

			$this->submenu_panels = apply_filters(
				'charitable_builder_panels_payment',
				array(
					'stripe',
					'square',
					'paypal',
					'braintree',
					'mollie',
					'gocardless',
					'authorize-net',
					'payfast',
					'payrexx',
					'paystack',
					'payumoney',
					'windcave',
					'request',
				)
			);

			foreach ( $this->submenu_panels as $panel ) {
				$panel = sanitize_file_name( $panel );
				$file  = apply_filters( 'charitable_builder_panels_payment_classfile', charitable()->get_path( 'includes' ) . "admin/campaign-builder/panels/payment/class-payment-{$panel}.php", $panel );

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

			do_action( 'charitable_campaign_builder_payment_sidebar', $this->campaign_data );
		}

		/**
		 * Process payment for campaigns, mostly via the builder.
		 *
		 * @since 1.8.0
		 *
		 * @param string $field Field.
		 * @param string $section Payment section.
		 * @param string $top_level Level.
		 * @param string $meta_key Alt legacy location to try to pull payment.
		 */
		public function campaign_data_payment( $field = 'title', $section = 'general', $top_level = 'payment', $meta_key = false ) {
		}

		/**
		 * Process education payment text.
		 *
		 * @since   1.8.0
		 * @version 1.8.7.1 - Added Square to the list of gateways that are built in.
		 *
		 * @param string $label Reader friendly output of object.
		 * @param string $slug Object slug.
		 * @param string $type Type of request.
		 */
		public function education_payment_text( $label = false, $slug = false, $type = false ) {

			$learn_more_url = charitable_ga_url(
				'https://wpcharitable.com/lite-vs-pro/', // base url.
				rawurlencode( esc_html( $label ) . ' Payment Page' ), // utm-medium.
				rawurlencode( 'Learn More' ) // utm-content.
			);

			$big_icon = apply_filters( 'charitable_campaign_builder_payment_icon_url', charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/payment/' . esc_attr( $slug ) . '_big.png', $slug );
			?>

			<?php

			if ( $big_icon ) :
				echo '<img class="charitable-builder-sidebar-icon" src="' . $big_icon . '" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endif;

			?>

			<section class="header-content">

				<?php if ( $slug === 'stripe' || $slug === 'paypal' || $slug === 'square' ) : ?>

				<h2>
					<?php
						printf(
							// translators: %s is the name of the addon.
							esc_html__( 'Charitable has %s built in.', 'charitable' ),
							'<span>' . esc_html( $label ) . '</span>'
						);
					?>
				</h2>

				<p><?php echo esc_html( $label ); ?> <?php echo esc_html__( 'allows you to easily reach more supporters and increase donations.', 'charitable' ); ?></p>

				<?php elseif ( 'request' === $type ) : ?>

					<h2><?php echo esc_html__( 'Don\'t see an integration with ', 'charitable' ); ?><strong><?php echo esc_html__( 'Charitable', 'charitable' ); ?></strong> <?php echo esc_html__( 'and your favorite payment method?', 'charitable' ); ?></h2>
					<h2><?php echo esc_html__( 'Let us know!', 'charitable' ); ?></h2>

				<?php else : ?>

				<h2><strong><?php echo esc_html__( 'Charitable Pro', 'charitable' ); ?></strong> <?php echo esc_html__( 'allows you to integrate seamlessly with ', 'charitable' ); ?><span><?php echo esc_html( $label ); ?></span>.</h2>

				<p><?php echo esc_html( $label ); ?> <?php echo esc_html__( 'allows you to easily reach more supporters and increase donations with payment platforms including PayPal, Venmo, Apple Pay and Google Pay.', 'charitable' ); ?></p>

				<div class="education-buttons education-buttons-top">
						<a class="button-link" target="_blank" href="<?php echo esc_url( $learn_more_url ); ?>"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a> <button type="button" class="btn btn-confirm update-to-pro-link"><?php echo esc_html__( 'Upgrade to PRO', 'charitable' ); ?></button>
				</div>

				<?php endif; ?>

			</section>

			<?php if ( $slug === 'stripe' || $slug === 'paypal' || $slug === 'square' ) : ?>

						<?php
				elseif ( 'request' === $type ) :

					// attempt to prefill name and email fields.
					$current_user = wp_get_current_user();
					$name         = ( $current_user ) ? charitable_get_creator_data() : false;
					$email        = ! empty( $current_user->user_email ) ? $current_user->user_email : false;

					?>

			<!-- start feedback form -->

			<div class="charitable-feedback-form-container-payment">

				<div id="charitable-marketing-form" class="charitable-form charitable-feedback-form">

					<div class="charitable-feedback-form-interior">

						<input type="hidden" class="charitable-feedback-form-type" value="payment" />

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

			<div id="charitable-marketing-form-confirmation" class="charitable-form charitable-marketing-form charitable-form-confirmation charitable-hidden">
				<h2><?php echo esc_html__( 'Thank you!', 'charitable' ); ?></h2>
				<p><?php echo esc_html__( 'Your feedback has been sent to our team. Stay tuned to our updates on', 'charitable' ); ?> <a href="https://wpcharitable.com" target="_blank">wpcharitable.com</a>.</p>
			</div>

					<?php

					if ( ! charitable_is_pro() ) :
						?>
			<section class="main-content">

				<p><?php echo esc_html__( 'Upgrading to ', 'charitable' ); ?><strong><?php echo esc_html__( 'Pro', 'charitable' ); ?></strong> <?php echo esc_html__( 'gives you the following capabilities...', 'charitable' ); ?></p>

				<ul>
					<li><?php echo esc_html__( 'Integrate with a wide variety of gateways including', 'charitable' ); ?> <strong><?php echo esc_html__( 'Payfast', 'charitable' ); ?></strong>, <strong><?php echo esc_html__( 'Mollie', 'charitable' ); ?></strong>, <strong><?php echo esc_html__( 'Authorize.Net', 'charitable' ); ?></strong> <?php echo esc_html__( 'and more.', 'charitable' ); ?></li>
					<li>
							<?php
							// translators: %s is the name of the addon.
							echo esc_html__( 'With the <strong>Gift Aid</strong> addon, a tax incentive for UK charities, you can boost your donations by 25 percent.', 'charitable' );

							?>
					</li>
					<li><?php echo esc_html__( 'Allow your donors to donate anonmously.', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Give your donors the ability to share a message when they donate.', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Help your donors with their record keeping by providing downloadable annual receipts.', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Make life easy for your donors by providing them with a PDF receipt for their donation.', 'charitable' ); ?></li>
				</ul>

			</section>

					<?php endif; ?>

			<?php else : ?>

			<div class="education-images">

			<img class="charitable-payment-image charitable-payment-image-1 charitable-payment-image-<?php echo esc_attr( $slug ); ?>" src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/payment/education/' . esc_attr( $slug ) . '.gif' ); ?>" alt="<?php echo esc_html( $label ); ?>" />

			</div>

			<section class="main-content">

				<p><?php echo esc_html__( 'Upgrading to ', 'charitable' ); ?><strong><?php echo esc_html__( 'Pro', 'charitable' ); ?></strong> <?php echo esc_html__( 'gives you the following capabilities...', 'charitable' ); ?></p>

				<ul>
					<li><?php echo esc_html__( 'Integrate with a wide variety of gateways including', 'charitable' ); ?> <strong><?php echo esc_html__( 'Payfast', 'charitable' ); ?></strong>, <strong><?php echo esc_html__( 'Mollie', 'charitable' ); ?></strong>, <strong><?php echo esc_html__( 'Authorize.Net', 'charitable' ); ?></strong> <?php echo esc_html__( 'and more.', 'charitable' ); ?></li>
					<li>
					<?php
					// translators: %s is the name of the addon.
					echo esc_html__( 'With the <strong>Gift Aid</strong> addon, a tax incentive for UK charities, you can boost your donations by 25 percent.', 'charitable' );

					?>
					</li>
					<li><?php echo esc_html__( 'Allow your donors to donate anonmously.', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Give your donors the ability to share a message when they donate.', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Help your donors with their record keeping by providing downloadable annual receipts.', 'charitable' ); ?></li>
					<li><?php echo esc_html__( 'Make life easy for your donors by providing them with a PDF receipt for their donation.', 'charitable' ); ?></li>
				</ul>

			</section>

			<?php endif; ?>

			<div class="education-buttons">

				<?php if ( $slug === 'stripe' || $slug === 'paypal' || $slug === 'square' ) { ?>

					<?php

					$helper = charitable_get_helper( 'gateways' );

					if ( $slug === 'stripe' ) {

						$gateway   = new Charitable_Gateway_Stripe_AM();
						$is_active = $helper->is_active_gateway( $gateway->get_gateway_id() );

						if ( $is_active ) {

							$action_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' );

							echo '<a class="button-link" href="' . esc_url( $action_url ) . '" target="_blank">' . esc_html__( 'Go To Payment Gateway Settings', 'charitable' ) . '</a>';

						} else {

							$action_url = esc_url(
								add_query_arg(
									array(
										'charitable_action' => 'enable_gateway',
										'gateway_id' => $gateway->get_gateway_id(),
										'_nonce'     => wp_create_nonce( 'gateway' ),
									),
									admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
								)
							);

							echo '<a class="button-link" href="' . esc_url( $action_url ) . '" target="_blank">' . esc_html__( 'Enable Stripe', 'charitable' ) . '</a>';
						}
					}

					if ( $slug === 'square' && version_compare( PHP_VERSION, '8.1.0', '>=' ) && class_exists( 'Charitable_Gateway_Square' ) ) {

						$gateway   = new Charitable_Gateway_Square();
						$is_active = $helper->is_active_gateway( $gateway->get_gateway_id() );

						if ( $is_active ) {

							$action_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_square_core' );

							echo '<a class="button-link" href="' . esc_url( $action_url ) . '" target="_blank">' . esc_html__( 'Go To Payment Gateway Settings', 'charitable' ) . '</a>';

						} else {

							$action_url = esc_url(
								add_query_arg(
									array(
										'charitable_action' => 'enable_gateway',
										'gateway_id' => $gateway->get_gateway_id(),
										'_nonce'     => wp_create_nonce( 'gateway' ),
									),
									admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
								)
							);

							echo '<a class="button-link" href="' . esc_url( $action_url ) . '" target="_blank">' . esc_html__( 'Enable Square', 'charitable' ) . '</a>';
						}
					}

					if ( $slug === 'paypal' ) {

						$gateway   = new Charitable_Gateway_Paypal();
						$is_active = $helper->is_active_gateway( $gateway->get_gateway_id() );

						if ( $is_active ) {

							$action_url = admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_paypal' );

							echo '<a class="button-link" href="' . esc_url( $action_url ) . '" target="_blank">' . esc_html__( 'Go To Payment Gateway Settings', 'charitable' ) . '</a>';

						} else {

							$action_url = esc_url(
								add_query_arg(
									array(
										'charitable_action' => 'enable_gateway',
										'gateway_id' => $gateway->get_gateway_id(),
										'_nonce'     => wp_create_nonce( 'gateway' ),
									),
									admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
								)
							);

							echo '<a class="button-link" href="' . esc_url( $action_url ) . '" target="_blank">' . esc_html__( 'Enable Paypal', 'charitable' ) . '</a>';
						}
					}

					?>

				<?php } elseif ( ! charitable_is_pro() ) { ?>

					<h2><?php echo esc_html__( 'Get the most out of your campaigns with ', 'charitable' ); ?><strong><?php echo esc_html__( 'Pro', 'charitable' ); ?></strong>.</h2>

					<a class="button-link" href="<?php echo esc_url( $learn_more_url ); ?>"><?php echo esc_html__( 'Learn More', 'charitable' ); ?></a> <button type="button" class="btn btn-confirm"><?php echo esc_html__( 'Upgrade to PRO', 'charitable' ); ?></button>

				<?php } ?>

			</div>

			<?php
		}

		/**
		 * Process addon not activated text.
		 *
		 * @since 1.8.1.4
		 *
		 * @param string $label Reader friendly output of object.
		 * @param string $slug Object slug.
		 * @param string $needed_addon_url Needed addon URL, used to check if the plugin information is stored in the db.
		 *
		 * @return void
		 */
		public function plugin_not_activated_text( $label = false, $slug = false, $needed_addon_url = '' ) {

			// Get the first part of the main slug before any "-" or "_" to pass to the search param for the addon page.
			$search_slug = explode( '-', $slug );
			$search_slug = ! empty( $search_slug ) ? $search_slug[0] : $slug;

			$addons_url        = esc_url(
				add_query_arg(
					array(
						'search' => $search_slug,
					),
					admin_url( 'admin.php?page=charitable-addons' )
				)
			);
			$addon_description = esc_html( $label ) . ' ' . esc_html__( 'allows you to easily reach more supporters and increase donations.', 'charitable' ); // default description.

			// attempt to get the "slug" so we can eventually see if we can get the plugin description from the stored plugin versions in the databse.
			$plugin_version_slug        = explode( '/', $slug );
			$plugin_version_slug_string = ! empty( $plugin_version_slug ) ? 'charitable-' . $plugin_version_slug[0] : '';

			// start the output.
			echo '<img class="charitable-builder-sidebar-icon" src="' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/payment/' . esc_attr( $slug ) . '_big.png" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			?>

			<section class="header-content">

				<h2><strong><?php echo esc_html__( 'Charitable Pro', 'charitable' ); ?></strong> <?php echo esc_html__( 'allows you to integrate seamlessly with ', 'charitable' ); ?><span><?php echo esc_html( $label ); ?></span>.</h2>

				<?php

				// attempt to get the addon description from addon itself, if that information is stored in the database. If not, go with a default description.
				$versions = get_site_option( 'wpc_plugin_versions' );

				if ( false !== $versions && ! empty( $versions['data'] ) ) :
					foreach ( $versions['data'] as $version ) :
						if ( empty( $version['slug'] ) || $version['slug'] !== $plugin_version_slug_string || empty( $version['sections'] ) ) {
							continue;
						}
						$section           = rawurlencode( $version['sections'] );
						$addon_description = ! empty( $section ) ? wp_strip_all_tags( $section['description'] ) : $addon_description;
					endforeach;
				endif;

				?>

				<p><?php echo esc_html( $addon_description ); ?></p>

				<div class="education-buttons education-buttons-top">
						<a class="button-link" target="_blank" href="<?php echo esc_url( $addons_url ); ?>"><?php echo esc_html__( 'Install This Addon', 'charitable' ); ?></a></button>
				</div>

			</section>

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

			echo '<img class="charitable-builder-sidebar-icon" src="' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/payment/' . esc_attr( $marketing_item_slug ) . '_big.png" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			?>

			<section class="header-content">

					<h2>
					<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'You have the %s addon activated.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>' );
					?>
					</h2>

					<p>
						<?php
						// translators: %s is the name of the addon.
						printf( esc_html__( 'The %1$s addon allows you to integrate seamlessly with %2$s as a payment gateway.', 'charitable' ), '<strong>' . esc_html( $addon_name ) . '</strong>', '<strong>' . esc_html( $marketing_item_label ) . '</strong>' );
						?>
					</p>


			</section>


			<div class="education-buttons education-buttons-bottom">

				<?php

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

			echo '<img class="charitable-builder-sidebar-icon" src="' . charitable()->get_path( 'assets', false ) . 'images/campaign-builder/settings/payment/' . esc_attr( $marketing_item_slug ) . '_big.png" wdith="178" height="178" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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

			// Setup defaults for a few variables.
			$settings_url = false;
			$gateway_id   = '';
			$settings_url = '';
			$enable_url   = '';

			// Get the gateway ID, which in this case is the first part of the addon_url before the '/' and remove the 'charitable-' prefix.
			if ( ! empty( $addon_url ) ) {
				$gateway_id = explode( '/', $addon_url );
				$gateway_id = ! empty( $gateway_id ) ? str_replace( 'charitable-', '', $gateway_id[0] ) : '';
				$gateway_id = str_replace( '-', '_', $gateway_id );

				$settings_url = esc_url(
					add_query_arg(
						array(
							'group' => 'gateways_' . $gateway_id,
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);
			}

			if ( ! empty( $gateway_id ) ) {
				$enable_url = esc_url(
					add_query_arg(
						array(
							'charitable_action' => 'enable_gateway',
							'gateway_id'        => $gateway_id,
							'_nonce'            => wp_create_nonce( 'gateway' ),
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);
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

			$addon_url_slug = explode( '/', $addon_url );
			$addon_url_slug = $addon_url_slug[0]; // example: charitable-newsletter-connect.

			$addon_information = array();

			// Extract the correct addon data.
			if ( ! empty( $charitable_addons ) ) {
				foreach ( $charitable_addons as $addon ) {
					if ( ! empty( $addon['slug'] ) && (string) $addon_url_slug === (string) $addon['slug'] ) {
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

			<div class="education-buttons education-buttons-bottom">

				<div class="action-button">
					<?php echo '<a class="button-link ' . esc_attr( $action_css_class ) . '" data-enable-url="' . esc_url( $enable_url ) . '" data-settings-url="' . esc_url( $settings_url ) . '" data-plugin-url="' . esc_url( $addon_information_install ) . '" data-name="' . esc_html( $addon_information_name ) . '" data-plugin-slug="' . esc_html( $addon_url ) . '" data-field-icon="">' . esc_html( $button_label ) . '</a>'; ?>
				</div>

			</div>

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
		public function get_gateway_button_info( $provider_slug = '', $button_url = '', $button_label = '' ) {

			$button_info = array(
				'button_url'   => $button_url,
				'button_label' => $button_label,
			);

			$helper   = charitable_get_helper( 'gateways' );
			$gateways = $helper->get_available_gateways();

			foreach ( $gateways as $gateway_slug => $gateway ) :

				if ( $gateway_slug !== $provider_slug ) {
					continue;
				}

				$gateway   = new $gateway();
				$is_active = $helper->is_active_gateway( $gateway->get_gateway_id() );

				if ( $is_active ) {
					$action_url  = esc_url(
						add_query_arg(
							array(
								'group' => 'gateways_' . $gateway->get_gateway_id(),
							),
							admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
						)
					);
					$action_text = __( 'Go To Settings', 'charitable' );
				} else {
					$action_url  = esc_url(
						add_query_arg(
							array(
								'charitable_action' => 'enable_gateway',
								'gateway_id'        => $gateway->get_gateway_id(),
								'_nonce'            => wp_create_nonce( 'gateway' ),
							),
							admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
						)
					);
					$action_text = __( 'Enable Gateway', 'charitable' );
				}

				$button_info = array(
					'button_url'   => $action_url,
					'button_label' => $action_text,
				);

			endforeach;

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

			do_action( 'charitable_campaign_builder_payment_panels', $this->campaign_data );
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

new Charitable_Builder_Panel_Payment();
