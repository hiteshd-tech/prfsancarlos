<?php
/**
 * Template management panel.
 *
 * @package   Charitable
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.8.0
 * @version   1.8.9.1
 * @version   1.8.9.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Builder_Panel_Template' ) ) :

	/**
	 * Charitable campaign builder template panel.
	 *
	 * @since 1.8.0
	 */
	class Charitable_Builder_Panel_Template extends Charitable_Builder_Panel {

		/**
		 * Form data and settings.
		 *
		 * @since 1.8.0
		 *
		 * @var array
		 */
		public $campaign_data;

		/**
		 * All systems go.
		 *
		 * @since 1.8.0
		 */
		public function init() {

			// Define panel information.
			$this->name    = esc_html__( 'Template', 'charitable' );
			$this->slug    = 'template';
			$this->icon    = 'panel_template.svg';
			$this->order   = 10;
			$this->sidebar = false;
		}

		/**
		 * Enqueue assets for the template panel.
		 *
		 * @since 1.8.0
		 */
		public function enqueues() {
		}

		/**
		 * Output the panel primary content, which is a UI of campaign templates to select.
		 *
		 * @since 1.8.0
		 * @version 1.8.9.1
		 * @version 1.8.9.7
		 */
		public function panel_content() {

			$builder_template = new Charitable_Campaign_Builder_Templates();

			// attempt to prefill name and email fields.
			$current_user = wp_get_current_user();
			$name         = ( $current_user ) ? charitable_get_creator_data() : false;
			$email        = ! empty( $current_user->user_email ) ? $current_user->user_email : false;

			?>

			<div class="charitable-preview-wrap">

				<!-- start feedback form -->

				<div class="charitable-feedback-form-container">

					<div id="charitable-feedback-form" class="charitable-form charitable-feedback-form">

						<div class="charitable-feedback-form-header">
							<div class="charitable-feedback-form-title"><?php echo esc_html__( 'Give Feedback', 'charitable' ); ?></div>
							<div class="charitable-templates-close-icon">×</div>
						</div>

						<div class="charitable-feedback-form-interior">

						<input type="hidden" class="charitable-feedback-form-type" value="templates" />

							<div class="charitable-form-row charitable-feedback-form-row">
								<label><?php echo esc_html__( 'Name', 'charitable' ); ?>  <span class="charitable-feedback-form-required">*</span></label>
								<input name="charitable-feedback-form-name" type="text" class="charitable-feedback-form-name" value="<?php echo esc_html( $name ); ?>" />
							</div>
							<div class="charitable-form-row charitable-feedback-form-row">
								<label><?php echo esc_html__( 'Email', 'charitable' ); ?> <span class="charitable-feedback-form-required">*</span></label>
								<input name="charitable-feedback-form-email" type="email" class="charitable-feedback-form-email" value="<?php echo esc_html( $email ); ?>" />
							</div>
							<div class="charitable-form-row charitable-feedback-form-row">
								<label><?php echo esc_html__( 'Let us know which template you think missing here and can be a good addition?', 'charitable' ); ?><span class="charitable-feedback-form-required">*</span></label>
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


				<div class="charitable-template-preview">

					<div class="charitable-setup-title">
						<?php echo esc_html__( 'Select A Template', 'charitable' ); ?> <span class="charitable-setup-title-after"></span>
					</div>

					<p class="charitable-setup-desc secondary-text">
						<?php

						// determine if the user already has a template selected for a campaign, or is starting fresh.
						$template_label = isset( $this->campaign_data['template_label'] ) ? esc_attr( $this->campaign_data['template_label'] ) : false;

						if ( $template_label ) :

							echo wp_kses_post(
								sprintf(
									/* translators: 1: Template ID */
									__( 'You are currently using the <strong class="template-name">"%1$s"</strong> template. Changing a template now might result in losing fields and data. Proceed carefully.', 'charitable' ),
									esc_html( $template_label )
								)
							);

						else :

							echo wp_kses_post(
								sprintf(
									/* translators: 1: Site URL */
									__( 'To speed up the process you can select from one of our pre-made templates or start from scratch <a href="%1$s" class="charitable-trigger-blank">with our simple column templates</a>. Have a suggestion for a new template? <a href="#" class="send-feedback">We would love to hear it</a>!', 'charitable' ),
									esc_url( admin_url() )
								)
							);

						endif;

						echo $builder_template->output_templates_panel(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						?>
					</p>

				</div>

			</div>

			<?php
		}
	}

endif;

new Charitable_Builder_Panel_Template();
