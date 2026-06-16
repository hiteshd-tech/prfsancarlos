<?php
/**
 * Charitable Template Functions.
 *
 * Functions used with template hooks.
 *
 * @package   Charitable/Functions/Templates
 * @author    David Bisset
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.9.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// //////////////////////////////
// HEAD OUTPUT
// //////////////////////////////
if ( ! function_exists( 'charitable_template_custom_styles' ) ) :

	/**
	 * Add custom styles to the <head> section.
	 *
	 * This is used on the wp_head action.
	 *
	 * @since  1.2.0
	 * @version 1.8.9.1
	 *
	 * @return void
	 */
	function charitable_template_custom_styles() {

		if ( ! apply_filters( 'charitable_add_custom_styles', true ) ) {
			return;
		}

		if ( defined( 'CHARITABLE_NO_CUSTOM_TEMPLATE_STYLES' ) && CHARITABLE_NO_CUSTOM_TEMPLATE_STYLES ) {
			return;
		}

		$styles = get_transient( 'charitable_custom_styles' );

		if ( false === $styles ) {

			ob_start();

			charitable_template( 'custom-styles.css.php' );

			$styles = ob_get_clean();

			$styles = charitable_compress_css( $styles );

			set_transient( 'charitable_custom_styles', $styles, 0 );
		}

		echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

endif;

if ( ! function_exists( 'charitable_hide_admin_bar' ) ) :

	/**
	 * Hides the admin bar from header.
	 *
	 * This is designed to be used when viewing the campaign widget.
	 *
	 * @since  1.3.0
	 *
	 * @return void
	 */
	function charitable_hide_admin_bar() {
		?>
<style type="text/css" media="screen">
html { margin-top: 0 !important; }
* html body { margin-top: 0 !important; }
</style>
		<?php
	}

endif;

// //////////////////////////////
// BODY CLASSES
// //////////////////////////////
if ( ! function_exists( 'charitable_add_body_classes' ) ) :

	/**
	 * Adds custom body classes to certain templates.
	 *
	 * @since  1.3.0
	 *
	 * @param  string[] $classes Body classes.
	 * @return string[]
	 */
	function charitable_add_body_classes( $classes ) {
		if ( charitable_is_page( 'donation_receipt_page' ) ) {
			$classes[] = 'campaign-donation-receipt';
		}

		if ( charitable_is_page( 'donation_processing_page' ) ) {
			$classes[] = 'campaign-donation-processing';
		}

		if ( charitable_is_page( 'campaign_donation_page' ) ) {
			$classes[] = 'campaign-donation-page';
		}

		if ( charitable_is_page( 'campaign_widget_page' ) ) {
			$classes[] = 'campaign-widget';
		}

		if ( charitable_is_page( 'email_preview' ) ) {
			$classes[] = 'email-preview';
		}

		return $classes;
	}

endif;

// //////////////////////////////
// SINGLE CAMPAIGN CONTENT
// //////////////////////////////
if ( ! function_exists( 'charitable_template_campaign_description' ) ) :

	/**
	 * Display the campaign description before the summary and rest of content.
	 *
	 * @since   1.0.0
	 * @version 1.8.1 Add check for CHARITABLE_NO_LEGACY_CHECK_CAMPAIGN_DESCRIPTION constant.
	 * @version 1.8.2 Added charitable_find_description_in_campaign_settings method
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @param  array               $view_args The view arguments.
	 * @return void
	 */
	function charitable_template_campaign_description( $campaign, $view_args = array() ) {
		if ( charitable_is_campaign_legacy( $campaign ) || ( defined( 'CHARITABLE_NO_LEGACY_CHECK_CAMPAIGN_DESCRIPTION' ) && CHARITABLE_NO_LEGACY_CHECK_CAMPAIGN_DESCRIPTION ) ) {
			charitable_template(
				'campaign/description.php',
				array(
					'campaign'  => $campaign,
					'view_args' => $view_args,
				)
			);
		} elseif ( $campaign->ID && ! empty( $view_args['shortcode'] ) ) {
				$campaign_settings = get_post_meta( $campaign->ID, 'campaign_settings_v2', true );
			if ( is_array( $campaign_settings ) && ! empty( $campaign_settings ) ) {
				$description_limit = isset( $view_args['description_limit'] ) ? intval( $view_args['description_limit'] ) : 0;
				$description       = charitable_find_description_in_campaign_settings( $campaign_settings, $description_limit );
				charitable_template(
					'campaign/description.php',
					array(
						'campaign'    => $campaign,
						'description' => $description,
						'view_args'   => $view_args,
					)
				);
			}
		}
	}


endif;

if ( ! function_exists( 'charitable_template_campaign_finished_notice' ) ) :

	/**
	 * Display the campaign finished notice.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_finished_notice( $campaign ) {
		if ( ! $campaign->has_ended() ) {
			return;
		}

		charitable_template( 'campaign/finished-notice.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_percentage_raised' ) ) :

	/**
	 * Display the percentage that the campaign has raised in summary block.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return boolean     True if the template was displayed. False otherwise.
	 */
	function charitable_template_campaign_percentage_raised( $campaign ) {
		// Check if $campaign is a valid object.
		if ( ! is_a( $campaign, 'Charitable_Campaign' ) ) {
			return false;
		}

		if ( ! $campaign->has_goal() ) {
			return false;
		}

		// Added in 1.7.0.9 - add filter for custom logic and now controllable by new tab for campaign settings.
		if ( false === apply_filters( 'charitable_display_template_campaign_percentage_raised', true, $campaign ) ) {
			return false;
		}

		charitable_template( 'campaign/summary-percentage-raised.php', array( 'campaign' => $campaign ) );

		return true;
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_donation_summary' ) ) :

	/**
	 * Display campaign goal in summary block.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return true
	 */
	function charitable_template_campaign_donation_summary( $campaign ) {
		charitable_template( 'campaign/summary-donations.php', array( 'campaign' => $campaign ) );
		return true;
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_donor_count' ) ) :

	/**
	 * Display number of campaign donors in summary block.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return true
	 */
	function charitable_template_campaign_donor_count( $campaign ) {

		// Added in 1.7.0.9 - add filter for custom logic and now controllable by new tab for campaign settings.
		if ( false === apply_filters( 'charitable_display_template_campaign_donor_count', true, $campaign ) ) {
			return false;
		}

		charitable_template( 'campaign/summary-donors.php', array( 'campaign' => $campaign ) );
		return true;
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_time_left' ) ) :

	/**
	 * Display the amount of time left in the campaign in the summary block.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return boolean     True if the template was displayed. False otherwise.
	 */
	function charitable_template_campaign_time_left( $campaign ) {
		if ( ! is_a( $campaign, 'Charitable_Campaign' ) ) {
			return false;
		}

		if ( $campaign->is_endless() ) {
			return false;
		}

		// Added in 1.7.0.9 - add filter for custom logic and now controllable by new tab for campaign settings.
		if ( false === apply_filters( 'charitable_display_template_campaign_time_remaining', true, $campaign ) ) {
			return false;
		}

		charitable_template( 'campaign/summary-time-left.php', array( 'campaign' => $campaign ) );
		return true;
	}

endif;

if ( ! function_exists( 'charitable_template_donate_button' ) ) :

	/**
	 * Display donate button or link in the campaign summary.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return boolean     True if the template was displayed. False otherwise.
	 */
	function charitable_template_donate_button( $campaign ) {
		if ( ! is_a( $campaign, 'Charitable_Campaign' ) ) {
			return false;
		}

		if ( ! $campaign->can_receive_donations() ) {
			return false;
		}

		$campaign->donate_button_template();

		return true;
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_summary' ) ) :

	/**
	 * Display campaign summary before rest of campaign content.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_summary( $campaign ) {
		if ( charitable_is_campaign_legacy( $campaign ) ) {
			charitable_template( 'campaign/summary.php', array( 'campaign' => $campaign ) );
		}
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_progress_bar' ) ) :

	/**
	 * Output the campaign progress bar.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_progress_bar( $campaign ) {
		charitable_template( 'campaign/progress-bar.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_donate_button' ) ) :

	/**
	 * Output the campaign donate button.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_donate_button( $campaign ) {
		if ( charitable_is_campaign_legacy( $campaign ) ) {
			charitable_template( 'campaign/donate-button.php', array( 'campaign' => $campaign ) );
		}
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_donate_link' ) ) :

	/**
	 * Output the campaign donate link.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_donate_link( $campaign ) {
		charitable_template( 'campaign/donate-link.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_status_tag' ) ) :

	/**
	 * Output the campaign status tag.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_status_tag( $campaign ) {
		charitable_template( 'campaign/status-tag.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_donation_form_in_page' ) ) :

	/**
	 * Add the donation form straight into the campaign page.
	 *
	 * @since  1.0.0
	 * @since  1.5.0 Function now returns true/false depending
	 *               on whether the template is rendered.
	 * @since  1.8.1 Added check to see if charitable_get_current_donation_form() returns false, preventing fatal error
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return boolean
	 */
	function charitable_template_campaign_donation_form_in_page( $campaign ) {
		if ( ! is_a( $campaign, 'Charitable_Campaign' ) ) {
			return false;
		}
		if ( $campaign->can_receive_donations() && 'same_page' == charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
			$donation_id = get_query_var( 'donation_id', false );

			/* If a donation ID is included, make sure it belongs to the current user. */
			if ( $donation_id && ! charitable_user_can_access_donation( $donation_id ) ) {
				return false;
			}

			if ( false !== charitable_get_current_donation_form() ) {
				charitable_get_current_donation_form()->render();
			}

			return true;
		}

		return false;
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_modal_donation_window' ) ) :

	/**
	 * Adds the modal donation window to a campaign page.
	 *
	 * @since  1.0.0
	 * @since  1.5.0 Function now returns true/false depending
	 *               on whether the template is rendered.
	 * @version 1.8.2 Added additional pattern to search for campaign id in the content.
	 *
	 * @global WP_Query $wp_query
	 * @return boolean
	 */
	function charitable_template_campaign_modal_donation_window() {
		global $wp_query, $post;

		// We need to find the campaign so we can use charitable_get_current_campaign().
		if ( Charitable::CAMPAIGN_POST_TYPE === get_post_type() ) {

			if ( isset( $wp_query->query_vars['donate'] ) ) {
				return false;
			}

			$campaign_ids = array( charitable_get_current_campaign_id() );

		} elseif ( 'page' === get_post_type() && ! empty( $post->post_content ) ) {

			// Find all instances of {"campaignID":"126"} in the content and extract all the different campaign ID and put them into a PHP array.
			$pattern = '/{"campaignID":"(\d+)"}/';
			preg_match_all( $pattern, $post->post_content, $matches );
			$campaign_ids = array_unique( $matches[1] );
			// if empty, then find all instances of "campaign id=126" in the content and extract all the different campaign ID and put them into a PHP array.
			if ( empty( $campaign_ids ) ) {
				$pattern = '/campaign id="(\d+)"/';
				preg_match_all( $pattern, $post->post_content, $matches );
				$campaign_ids = array_unique( $matches[1] );
			}

		} else {

			return false;

		}

		if ( ! $campaign_ids ) {
			return false;
		}

		foreach ( $campaign_ids as $campaign_id ) {

			$campaign = charitable_get_campaign( $campaign_id );

			if ( $campaign->can_receive_donations() && 'modal' === charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
				charitable_template( 'campaign/donate-modal-window.php', array( 'campaign' => $campaign ) );
			}

		}

		return true;
	}

endif;

if ( ! function_exists( 'charitable_template_elementor_campaign_modal_donation_window' ) ) :

	/**
	 * Adds the modal donation window for Elementor widgets.
	 *
	 * @since  1.8.7.1
	 *
	 * @global WP_Post $post
	 * @return boolean
	 */
	function charitable_template_elementor_campaign_modal_donation_window() {
		global $post;

		// Only run if Elementor is active.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}

		// Only run on pages/posts.
		if ( ! $post || ! in_array( $post->post_type, array( 'page', 'post' ) ) ) {
			return false;
		}

		// Get Elementor data.
		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
		if ( empty( $elementor_data ) ) {
			return false;
		}

		// Decode JSON data.
		$data = json_decode( $elementor_data, true );
		if ( ! is_array( $data ) ) {
			return false;
		}

		$campaign_ids = array();

		// Recursively search for Charitable widgets.
		$campaign_ids = charitable_extract_campaign_ids_from_elementor_data( $data );

		if ( empty( $campaign_ids ) ) {
			return false;
		}

		// Remove duplicates.
		$campaign_ids = array_unique( $campaign_ids );

		foreach ( $campaign_ids as $campaign_id ) {

			$campaign = charitable_get_campaign( $campaign_id );

			if ( $campaign->can_receive_donations() && 'modal' === charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
				charitable_template( 'campaign/donate-modal-window.php', array( 'campaign' => $campaign ) );
			}

		}

		return true;
	}

endif;

if ( ! function_exists( 'charitable_extract_campaign_ids_from_elementor_data' ) ) :

	/**
	 * Recursively extract campaign IDs from Elementor data.
	 *
	 * @since  1.8.7.1
	 *
	 * @param  array $data Elementor data array.
	 * @return array
	 */
	function charitable_extract_campaign_ids_from_elementor_data( $data ) {
		$campaign_ids = array();

		foreach ( $data as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			// Check if this is a Charitable widget.
			if ( isset( $element['widgetType'] ) ) {
				$widget_type = $element['widgetType'];

				// Check for Charitable campaign widgets.
				if ( in_array( $widget_type, array( 'charitable_campaign', 'charitable_button', 'charitable_donation_form' ) ) ) {
					if ( isset( $element['settings']['campaign_id'] ) && ! empty( $element['settings']['campaign_id'] ) ) {
						$campaign_ids[] = absint( $element['settings']['campaign_id'] );
					}
				}
			}

			// Recursively check child elements.
			if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$child_campaign_ids = charitable_extract_campaign_ids_from_elementor_data( $element['elements'] );
				$campaign_ids = array_merge( $campaign_ids, $child_campaign_ids );
			}
		}

		return $campaign_ids;
	}

endif;

// //////////////////////////////
// CAMPAIGN LOOP
// //////////////////////////////
if ( ! function_exists( 'charitable_template_campaign_loop' ) ) :

	/**
	 * Display the campaign loop.
	 *
	 * This is used instead of the_content filter.
	 *
	 * @since  1.0.0
	 *
	 * @param  WP_Query $campaigns Query with campaigns.
	 * @param  int      $columns   Number of columns to use for loop.
	 * @return void
	 */
	function charitable_template_campaign_loop( $campaigns = false, $columns = 1 ) {
		if ( ! $campaigns ) {
			global $wp_query;
			$campaigns = $wp_query;
		}

		charitable_template(
			'campaign-loop.php',
			array(
				'campaigns' => $campaigns,
				'columns'   => $columns,
			)
		);
	}

endif;

if ( ! function_exists( 'charitable_template_responsive_styles' ) ) :

	/**
	 * Add responsive styles for the campaign loop.
	 *
	 * @since  1.4.0
	 * @version 1.8.9.1
	 *
	 * @param  WP_Query $campaigns The campaigns that will be displayed.
	 * @param  array    $args      The view arguments.
	 * @return void
	 */
	function charitable_template_responsive_styles( $campaigns, $args ) {
		if ( ! isset( $args['responsive'] ) || ! $args['responsive'] || is_admin() ) {
			return;
		}

		// Check if we're in Elementor editor or preview mode
		$is_elementor = false;
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$is_elementor = \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode();
		}

		// If we're in Elementor, don't output inline styles
		if ( $is_elementor ) {
			return;
		}

		$breakpoint = '768px';

		if ( preg_match( '/[px|em]/', $args['responsive'] ) ) {
			$breakpoint = $args['responsive'];
		}
		?>
<style type="text/css" media="screen">
@media only screen and (max-width: <?php echo esc_attr( $breakpoint ); ?>) {
	.campaign-loop.campaign-grid.masonry { -moz-column-count: 1; -webkit-column-count: 1; column-count: 1; }
	.campaign-loop.campaign-grid .campaign,.campaign-loop.campaign-grid .campaign.hentry { width: 100% !important; }
}
</style>
		<?php
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_loop_thumbnail' ) ) :

	/**
	 * Output the campaign thumbnail on campaigns displayed within the loop.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_loop_thumbnail( $campaign ) {
		charitable_template( 'campaign-loop/thumbnail.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_loop_donation_stats' ) ) :

	/**
	 * Output the campaign donation status on campaigns displayed within the loop.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return void
	 */
	function charitable_template_campaign_loop_donation_stats( $campaign ) {
		charitable_template( 'campaign-loop/donation-stats.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_loop_donate_link' ) ) :

	/**
	 * Output the campaign donation status on campaigns displayed within the loop.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @param  mixed[]             $args     Optional arguments.
	 * @return void
	 */
	function charitable_template_campaign_loop_donate_link( $campaign, $args = array() ) {
		if ( isset( $args['button'] ) && 'donate' != $args['button'] ) {
			return;
		}

		$campaign->donate_button_loop_template();
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_loop_more_link' ) ) :

	/**
	 * Output the read more link on campaigns displayed within the loop.
	 *
	 * @since  1.2.3
	 *
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @param  mixed[]             $args     Optional arguments.
	 * @return void
	 */
	function charitable_template_campaign_loop_more_link( $campaign, $args = array() ) {
		if ( ! isset( $args['button'] ) || 'details' !== $args['button'] ) {
			return;
		}

		charitable_template( 'campaign-loop/more-link.php', array( 'campaign' => $campaign ) );
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_loop_add_modal' ) ) :

	/**
	 * Checks if the modal option is enabled and hooks the modal template up to wp_footer if it is.
	 *
	 * @since  1.2.3
	 *
	 * @return void
	 */
	function charitable_template_campaign_loop_add_modal() {
		if ( 'modal' == charitable_get_option( 'donation_form_display', 'separate_page' ) ) {
			add_action( 'wp_footer', 'charitable_template_campaign_loop_modal_donation_window' );
		}
	}

endif;

if ( ! function_exists( 'charitable_template_campaign_loop_modal_donation_window' ) ) :

	/**
	 * Adds the modal donation window to the campaign loop.
	 *
	 * @since  1.2.3
	 *
	 * @return void
	 */
	function charitable_template_campaign_loop_modal_donation_window() {
		charitable_template( 'campaign-loop/donate-modal-window.php' );
	}

endif;

/* DONATION FORM */


if ( ! function_exists( 'charitable_template_donation_form' ) ) :

	/**
	 * Display a campaign's donation form.
	 *
	 * @since  1.6.55
	 * @since  1.8.3.5 Added get_form_template().
	 *
	 * @param  int   $campaign_id The campaign ID.
	 * @param  array $args        Args to pass to the view.
	 * @return false|void
	 */
	function charitable_template_donation_form( $campaign_id, $args = array() ) {
		if ( Charitable::CAMPAIGN_POST_TYPE !== get_post_type( $campaign_id ) ) {
			return false;
		}

		if ( ! array_key_exists( 'campaign_id', $args ) ) {
			$args['campaign_id'] = $campaign_id;
		}

		if ( ! charitable_campaign_can_receive_donations( $args['campaign_id'] ) ) {
			return false;
		}

		$donation_id = get_query_var( 'donation_id', false );

		/* If a donation ID is included, make sure it belongs to the current user. */
		if ( $donation_id && ! charitable_user_can_access_donation( $donation_id ) ) {
			return false;
		}

		if ( ! wp_script_is( 'charitable-script', 'enqueued' ) ) {
			Charitable_Public::get_instance()->enqueue_donation_form_scripts();
		}

		$form = charitable_get_campaign( $campaign_id )->get_donation_form();

		/**
		 * Do something before rendering the donation form.
		 *
		 * @since 1.0.0
		 *
		 * @param Charitable_Donation_Form $form The donation form instance.
		 */
		do_action( 'charitable_donation_form_before', $form );

		$args['form']          = $form;
		$args['form_template'] = $form->get_form_template();
		$args['campaign']      = $form->get_campaign();

		charitable_template( 'donation-form/form-donation.php', $args );

		/**
		 * Do something after rendering the donation form.
		 *
		 * @since 1.0.0
		 *
		 * @param Charitable_Donation_Form $form The donation form instance.
		 */
		do_action( 'charitable_donation_form_after', $form );
	}

endif;

// //////////////////////////////
// DONATION RECEIPT
// //////////////////////////////
if ( ! function_exists( 'charitable_template_donation_receipt_content' ) ) :

	/**
	 * Display the donation form. This is used with the_content filter.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $content Page content.
	 * @return string
	 */
	function charitable_template_donation_receipt_content( $content ) {

		if ( ! in_the_loop() || ! charitable_is_page( 'donation_receipt_page' ) ) {
			return $content;
		}

		/* If we are NOT using the automatic option, this is a static page with the shortcode, so don't filter again. */
		if ( 'auto' != charitable_get_option( 'donation_receipt_page', 'auto' ) ) {
			return $content;
		}

		return charitable_template_donation_receipt_output( $content );
	}

endif;

if ( ! function_exists( 'charitable_template_donation_receipt_output' ) ) :

	/**
	 * Render the donation receipt. This can be used by the [donation_receipt] shortcode or through `the_content` filter.
	 *
	 * @since  1.0.0
	 * @since  1.5.0 Added $donation argument.
	 *
	 * @param  string                   $content  Page content.
	 * @param  Charitable_Donation|null $donation Optional. Useful when the donation is not the current donation.
	 * @return string
	 */
	function charitable_template_donation_receipt_output( $content, $donation = null ) {
		if ( is_null( $donation ) ) {
			$donation = charitable_get_current_donation();
		}

		if ( ! $donation || 'simple' != $donation->get_donation_type() ) {
			return $content;
		}

		ob_start();

		if ( ! $donation->is_from_current_user() ) {
			charitable_template_from_session(
				'donation-receipt/not-authorized.php',
				array( 'content' => $content ),
				'donation_receipt',
				array( 'donation_id' => $donation->ID )
			);

			return ob_get_clean();
		}

		do_action( 'charitable_donation_receipt_page', $donation );

		charitable_template(
			'content-donation-receipt.php',
			array(
				'content'  => $content,
				'donation' => $donation,
			)
		);

		$content = ob_get_clean();

		return $content;
	}

endif;

if ( ! function_exists( 'charitable_template_donation_receipt_summary' ) ) :

	/**
	 * Display the donation receipt summary.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Donation $donation The Donation object.
	 * @return void
	 */
	function charitable_template_donation_receipt_summary( Charitable_Donation $donation ) {
		charitable_template( 'donation-receipt/summary.php', array( 'donation' => $donation ) );
	}

endif;

if ( ! function_exists( 'charitable_template_donation_receipt_offline_payment_instructions' ) ) :

	/**
	 * Display the offline payment instructions, if applicable.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Donation $donation The Donation object.
	 * @return void
	 */
	function charitable_template_donation_receipt_offline_payment_instructions( Charitable_Donation $donation ) {
		if ( 'offline' != $donation->get_gateway() ) {
			return;
		}

		charitable_template( 'donation-receipt/offline-payment-instructions.php', array( 'donation' => $donation ) );
	}

endif;

if ( ! function_exists( 'charitable_template_donation_receipt_details' ) ) :

	/**
	 * Display the donation details.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Donation $donation The Donation object.
	 * @return void
	 */
	function charitable_template_donation_receipt_details( Charitable_Donation $donation ) {
		charitable_template( 'donation-receipt/details.php', array( 'donation' => $donation ) );
	}


endif;

// //////////////////////////////
// DONATION FORM
// //////////////////////////////
if ( ! function_exists( 'charitable_template_donation_form_content' ) ) :

	/**
	 * Display the donation form. This is used with the_content filter.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $content Page content.
	 * @return string
	 */
	function charitable_template_donation_form_content( $content ) {

		if ( ! charitable_is_main_loop() || ! charitable_is_page( 'campaign_donation_page' ) ) {
			return $content;
		}

		if ( 'separate_page' != charitable_get_option( 'donation_form_display', 'separate_page' )
			&& false === get_query_var( 'donate', false ) ) {
			return $content;
		}

		ob_start();

		charitable_template( 'content-donation-form.php' );

		return ob_get_clean();
	}

endif;

if ( ! function_exists( 'charitable_template_donation_form_login' ) ) :

	/**
	 * Display a prompt to login at the start of the user fields block.
	 *
	 * @since   1.0.0
	 * @version 1.8.2
	 *
	 * @param  Charitable_Donation_Form_Interface $form The donation form object.
	 * @return void
	 */
	function charitable_template_donation_form_login( Charitable_Donation_Form_Interface $form ) {
		$user = $form->get_user();

		if ( $user ) {
			return;
		}

		$allow_login_form = apply_filters( 'charitable_donation_form_login_allow', true );

		if ( ! $allow_login_form ) {
			return;
		}

		if ( '0' !== charitable_get_option( 'donation_form_show_login_message', '1' ) ) {
			charitable_template( 'donation-form/donor-fields/login-form.php' );
		}
	}

endif;

if ( ! function_exists( 'charitable_template_donation_form_donor_details' ) ) :

	/**
	 * Display the donor's saved details if the user is logged in.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Donation_Form_Interface $form The donation form object.
	 * @return void
	 */
	function charitable_template_donation_form_donor_details( Charitable_Donation_Form_Interface $form ) {
		/* Verify that the user is logged in and has all required fields filled out */
		if ( ! $form->should_hide_user_fields() ) {
			return;
		}

		charitable_template( 'donation-form/donor-fields/donor-details.php', array( 'user' => $form->get_user() ) );
	}

endif;

if ( ! function_exists( 'charitable_template_donation_form_donor_fields_hidden_wrapper_start' ) ) :

	/**
	 * If the user is logged in, adds a wrapper around the donor fields that hide them.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Donation_Form_Interface $form The donation form object.
	 * @return void
	 */
	function charitable_template_donation_form_donor_fields_hidden_wrapper_start( Charitable_Donation_Form_Interface $form ) {
		/* Verify that the user is logged in and has all required fields filled out */
		if ( ! $form->should_hide_user_fields() ) {
			return;
		}

		charitable_template( 'donation-form/donor-fields/hidden-fields-wrapper-start.php' );
	}

endif;

if ( ! function_exists( 'charitable_template_donation_form_donor_fields_hidden_wrapper_end' ) ) :

	/**
	 * Closes the hidden donor fields wrapper div if the user is logged in.
	 *
	 * @since  1.0.0
	 *
	 * @param  Charitable_Donation_Form_Interface $form The donation form object.
	 * @return void
	 */
	function charitable_template_donation_form_donor_fields_hidden_wrapper_end( Charitable_Donation_Form_Interface $form ) {
		/* Verify that the user is logged in and has all required fields filled out */
		if ( ! $form->should_hide_user_fields() ) {
			return;
		}

		charitable_template( 'donation-form/donor-fields/hidden-fields-wrapper-end.php' );
	}

endif;

if ( ! function_exists( 'charitable_template_donation_form_current_amount_text' ) ) :

	/**
	 * Display the amount currently selected to donate.
	 *
	 * @since  1.5.0
	 *
	 * @param  int|float $amount      The current donation amount.
	 * @param  string    $form_id     The current form ID.
	 * @param  string    $campaign_id The current campaign ID.
	 * @return string
	 */
	function charitable_template_donation_form_current_amount_text( $amount, $form_id, $campaign_id ) {
		if ( ! $amount ) {
			return '';
		}

		/**
		 * Format the donation amount.
		 *
		 * @since 1.4.14
		 * @since 1.5.0 Third parameter has been changed from an instance of
		 *              `Charitable_Donation_Form` to a campaign ID.
		 * @since 1.8.3.6 Added <div> wrapper.
		 *
		 * @param string    $amount_formatted The formatted amount.
		 * @param int|float $amount           The raw amount.
		 * @param int       $campaign_id      Campaign ID.
		 */
		$amount_formatted = apply_filters( 'charitable_session_donation_amount_formatted', charitable_format_money( $amount, false, true ), $amount, $campaign_id );

		$content = '<div class="charitable-your-donation-amount"><p>';
		/* translators: %s: donation amount */
		$content .= sprintf( __( 'Your Donation Amount: %s.', 'charitable' ), '<strong>' . $amount_formatted . '</strong>' );
		$content .= '&nbsp;<a href="#" class="change-donation" data-charitable-toggle="charitable-donation-options-' . esc_attr( $form_id ) . '">' . __( 'Change', 'charitable' ) . '</a>';
		$content .= '</div></p>';

		return $content;
	}

endif;



// //////////////////////////////
// DONATION PROCESSING PAGE
// //////////////////////////////
if ( ! function_exists( 'charitable_template_donation_processing_content' ) ) :

	/**
	 * Render the content of the donation processing page.
	 *
	 * @since  1.2.0
	 *
	 * @param  string $content Default content to be rendered.
	 * @return string
	 */
	function charitable_template_donation_processing_content( $content ) {
		if ( ! charitable_is_page( 'donation_processing_page' ) ) {
			return $content;
		}

		$donation = charitable_get_current_donation();

		if ( ! $donation ) {
			return $content;
		}

		$content = apply_filters( 'charitable_processing_donation_' . $donation->get_gateway(), $content, $donation );

		return $content;
	}

endif;

// //////////////////////////////
// ACCOUNT PAGES
// //////////////////////////////
if ( ! function_exists( 'charitable_template_forgot_password_content' ) ) :

	/**
	 * Render the content of the forgot password page.
	 *
	 * @since  1.4.0
	 *
	 * @param  string $content Default content to be rendered.
	 * @return string
	 */
	function charitable_template_forgot_password_content( $content = '' ) {
		if ( ! charitable_is_page( 'forgot_password_page' ) ) {
			return $content;
		}

		ob_start();

		if ( isset( $_GET['email_sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			charitable_template( 'account/forgot-password-sent.php' );

		} else {

			charitable_template(
				'account/forgot-password.php',
				array(
					'form' => new Charitable_Forgot_Password_Form(),
				)
			);

		}

		$content = ob_get_clean();

		return $content;
	}

endif;

if ( ! function_exists( 'charitable_template_reset_password_content' ) ) :

	/**
	 * Render the content of the reset password page.
	 *
	 * @since  1.4.0
	 *
	 * @param  string $content Default content to be rendered.
	 * @return string
	 */
	function charitable_template_reset_password_content( $content = '' ) {
		if ( ! charitable_is_page( 'reset_password_page' ) ) {
			return $content;
		}

		ob_start();

		charitable_template(
			'account/reset-password.php',
			array(
				'form' => new Charitable_Reset_Password_Form(),
			)
		);

		$content = ob_get_clean();

		return $content;
	}

endif;

if ( ! function_exists( 'charitable_template_form_login_link' ) ) :

	/**
	 * Display a link to the login form.
	 *
	 * @since  1.4.2
	 * @version 1.8.9.1
	 *
	 * @param  Charitable_Registration_Form|null $form Instance of `Charitable_Registration_Form`, or
	 *                                                 null in previous versions of Charitable.
	 * @return void
	 */
	function charitable_template_form_login_link( $form = null ) {
		/**
		 * For backwards compatibility, since previously the
		 * Form object was not passed to the hook.
		 */
		if ( is_null( $form ) ) {
			return;
		}

		if ( ! $form->get_login_link() ) {
			return;
		}

		printf( '<p>%s</p>', wp_kses_post( $form->get_login_link() ) );
	}

endif;

if ( ! function_exists( 'charitable_maybe_hide_campaign_percentage_raised' ) ) :

	/**
	 * Determine if a campaign setting says to hide this. Return true to show, false to hide.
	 *
	 * @since  1.7.0.8
	 *
	 * @param  boolean             $current_value Current value of boolean passed in.
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @return boolean
	 */
	function charitable_maybe_hide_campaign_percentage_raised( $current_value = true, $campaign = false ) {

		if ( false === $campaign || 0 === $campaign->get_campaign_id() ) {
			return $current_value;
		}

		$campaign_hide_percent_raised = get_post_meta( $campaign->get_campaign_id(), '_campaign_hide_percent_raised', true );

		if ( false === $campaign_hide_percent_raised || empty( $campaign_hide_percent_raised ) ) {
			return $current_value;
		}

		if ( in_array( 'hide_percent_raised', $campaign_hide_percent_raised ) ) {
			return false; // hide.
		}

		return $current_value;
	}

endif;


if ( ! function_exists( 'charitable_maybe_hide_campaign_donor_count' ) ) :

	/**
	 * Determine if a campaign setting says to hide this. Return true to show, false to hide.
	 *
	 * @since  1.7.0.8
	 *
	 * @param boolean             $current_value Current value of boolean passed in.
	 * @param Charitable_Campaign $campaign The campaign object.
	 *
	 * @return boolean
	 */
	function charitable_maybe_hide_campaign_donor_count( $current_value = true, $campaign = false ) {

		if ( false === $campaign || 0 === $campaign->get_campaign_id() ) {
			return $current_value;
		}

		$campaign_hide_donor_count = get_post_meta( $campaign->get_campaign_id(), '_campaign_hide_number_of_donors', true );

		if ( false === $campaign_hide_donor_count || empty( $campaign_hide_donor_count ) ) {
			return $current_value;
		}

		if ( in_array( 'hide_number_of_donors', $campaign_hide_donor_count ) ) {
			return false; // hide.
		}

		return $current_value;
	}

endif;


if ( ! function_exists( 'charitable_maybe_hide_campaign_amount_donated' ) ) :

	/**
	 * Determine if a campaign setting says to hide this. Return true to show, false to hide.
	 *
	 * @since  1.7.0.8
	 *
	 * @param  boolean             $current_value Current value of boolean passed in.
	 * @param  Charitable_Campaign $campaign The campaign object.
	 * @param  int                 $amount The amount donated.
	 * @param  int                 $goal The campaign goal.
	 * @return boolean
	 */
	function charitable_maybe_hide_campaign_amount_donated( $current_value, $campaign, $amount, $goal ) { // phpcs:ignore

		if ( false === $campaign || 0 === $campaign->get_campaign_id() ) {
			return $current_value;
		}

		$campaign_hide_amount_donated = get_post_meta( $campaign->get_campaign_id(), '_campaign_hide_amount_donated', true );

		if ( false === $campaign_hide_amount_donated || empty( $campaign_hide_amount_donated ) ) {
			return $current_value;
		}

		if ( in_array( 'hide_amount_donated', $campaign_hide_amount_donated ) ) {
			return false; // hide.
		}

		return $current_value;
	}

endif;


if ( ! function_exists( 'charitable_maybe_hide_campaign_time_remaining' ) ) :

	/**
	 * Determine if a campaign setting says to hide this. Return true to show, false to hide.
	 *
	 * @since  1.7.0.8
	 *
	 * @param  boolean $current_value Current value of boolean passed in.
	 * @return boolean
	 */
	function charitable_maybe_hide_campaign_time_remaining( $current_value = true, $campaign = false ) {

		if ( false === $campaign || 0 === $campaign->get_campaign_id() ) {
			return $current_value;
		}

		$campaign_hide_time_remaining = get_post_meta( $campaign->get_campaign_id(), '_campaign_hide_time_remaining', true );

		if ( false === $campaign_hide_time_remaining || empty( $campaign_hide_time_remaining ) ) {
			return $current_value;
		}

		if ( in_array( 'hide_time_remaining', $campaign_hide_time_remaining ) ) {
			return false; // hide.
		}

		return $current_value;
	}

endif;



// //////////////////////////////
// NOTICES
// //////////////////////////////
if ( ! function_exists( 'charitable_template_notices' ) ) :

	/**
	 * Render any notices.
	 *
	 * @since  1.4.0
	 *
	 * @param  array $notices Optional. Notices to be rendered.
	 * @return void
	 */
	function charitable_template_notices( $notices = array() ) {
		if ( empty( $notices ) ) {
			$notices = charitable_get_notices()->get_notices();
		}

		charitable_template_from_session(
			'form-fields/notices.php',
			array(
				'notices' => $notices,
			),
			'notices'
		);
	}

endif;

if ( ! function_exists( 'charitable_is_campaign_legacy' ) ) :

	/**
	 * Determine if a campaign is using the legacy settings.
	 *
	 * @since  1.8.0
	 *
	 * @param  mixed $campaign The campaign to check. Can be a campaign ID, a campaign object, or an array with an 'id' key.
	 * @return bool            True if the campaign is using the legacy settings, false otherwise.
	 */
	function charitable_is_campaign_legacy( $campaign = false ) {

		if ( ! $campaign ) {
			return true;
		}

		if ( is_array( $campaign ) && ! empty( $campaign['id'] ) ) {
			$campaign_id = intval( $campaign['id'] );
		} elseif ( is_int( $campaign ) ) { // a campaign ID.
			$campaign_id = intval( $campaign );
		} elseif ( is_a( $campaign, 'Charitable_Campaign' ) ) {
			$campaign_id = $campaign->__get( 'ID' );
		} else {
			return true;
		}

		$campaign_v2_settings = get_post_meta( $campaign_id, 'campaign_settings_v2', true );

		return ! empty( $campaign_v2_settings ) ? false : true;
	}

endif;

/**
 * Filters the allowed HTML tags for campaign builder fields.
 *
 * @since 1.8.0
 *
 * @param string|bool $text The text to filter.
 *
 * @return string|bool The filtered text.
 */
function charitable_campaign_builder_filter_allowed_html( $text = false ) {

	$allowed_html_tags = apply_filters(
		'charitable_campaign_builder_html_allowed_tags',
		array(
			'a'      => [
				'href'   => [],
				'class'  => [],
				'target' => [],
			],
			'p'      => [
				'class' => [],
			],
			'span'   => [
				'class' => [],
			],
			'div'    => [
				'class' => [],
			],
			'strong' => [
				'class' => [],
			],
			'em'     => [
				'class' => [],
			],
			'b'      => [
				'class' => [],
			],
			'i'      => [
				'class' => [],
			],
			'h1'     => [
				'class' => [],
			],
			'h2'     => [
				'class' => [],
			],
			'h3'     => [
				'class' => [],
			],
			'h4'     => [
				'class' => [],
			],
			'h5'     => [
				'class' => [],
			],
			'h6'     => [
				'class' => [],
			],
		)
	);

	return wp_kses( $text, $allowed_html_tags );
}

/**
 * Returns the preview content for a given builder tab type.
 *
 * @since  1.8.0
 *
 * @param string $type The type of builder tab.
 *
 * @return string The preview content.
 */
function charitable_builder_tab_content_preview_by_type( $type = 'html' ) {

	switch ( $type ) {

		case 'discussion':
			$output = '
			<div class="placeholder-row">
				<div class="placeholder-column avatar big">
					<span class="placeholder avatar big"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar big right">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar big">
					<span class="placeholder avatar big"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar big right">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			';
			break;

		case 'updates':
			$output = '
			<div class="placeholder-row">
				<div class="placeholder-column avatar">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			<div class="placeholder-row">
				<div class="placeholder-column avatar">
					<span class="placeholder avatar"></span>
				</div>
				<div class="placeholder-column">
					<span class="placeholder"></span>
					<span class="placeholder"></span>
					<span class="placeholder"></span>
				</div>
			</div>
			';
			break;

		default:
			$output = '';
			// $output = '

			// <div>
			// <span class="placeholder big"></span>
			// </div>
			// ';
			break;
	}

	return $output;
}
