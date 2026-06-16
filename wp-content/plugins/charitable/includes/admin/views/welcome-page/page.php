<?php
/**
 * Display the Welcome page.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Admin View/Welcome Page
 * @since   1.0.0
 * @version 1.8.0
 * @version 1.8.4 Revised for server-side onboarding.
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// assemble a url to the setup wizard using WordPress function.
$charitable_onboarding_url = charitable_get_onboarding_url();

// Enqueue the styles for the onboarding process.
wp_enqueue_style( 'charitable-admin-user-onboarding' );

// Has this user already completed the onboarding process?
$charitable_onboarding_completed = get_option( 'charitable_ss_complete', false ) ? true : false;
$charitable_onboarding_welcome   = ! empty( $_GET['f'] ) ? intval( $_GET['f'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$charitable_welcome_headline  = $charitable_onboarding_completed ? __( 'Welcome back to the Charitable Setup Wizard!', 'charitable' ) : __( 'Welcome to the Charitable Setup Wizard!', 'charitable' );
$charitable_introduction_text = $charitable_onboarding_completed ? __( 'You have already completed the setup wizard. If you need to make any changes, you can restart this process or adjust your Charitable settings.', 'charitable' ) : __( 'Your first fundraising begins with a creating a campaign. Ready to get started? Your first campaign is just 5 minutes away!', 'charitable' );
$charitable_button_label      = $charitable_onboarding_completed ? __( 'Restart Setup Wizard', 'charitable' ) : __( 'Let\'s Get Started', 'charitable' );


// Either they are going "back" to the checklist page or (if the checlist is disabled) the welcome page.
$charitable_checklist_class      = Charitable_Checklist::get_instance();
$charitable_checklist_possible   = $charitable_checklist_class->maybe_load_checklist_assets();
$charitable_welcome_go_back_url  = ! $charitable_checklist_possible ? admin_url( 'admin.php?page=charitable-dashboard&charitable_onboarding=cancel' ) : admin_url( 'admin.php?page=charitable-setup-checklist&charitable_onboarding=cancel' );
$charitable_welcome_go_back_text = ! $charitable_checklist_possible ? __( 'Go back to the dashboard', 'charitable' ) : __( 'Cancel this wizard and go back to the checklist', 'charitable' );

// Resume the onboarding process if it has been started, and override text.
$charitable_resume_onboarding = get_option( 'charitable_started_onboarding', false );
if ( $charitable_resume_onboarding && ! $charitable_onboarding_welcome ) {
	$charitable_welcome_headline    = __( 'Welcome back to the Charitable Setup Wizard!', 'charitable' );
	$charitable_introduction_text   = __( 'You have already started the setup wizard. Would you like to continue where you left off?', 'charitable' );
	$charitable_button_label        = __( 'Resume Setup Wizard', 'charitable' );
	$charitable_onboarding_url      = 'https://app.wpcharitable.com/setup-wizard-charitable_lite&resume=' . charitable_get_site_token();
	$charitable_welcome_go_back_url = admin_url( 'admin.php?page=charitable-setup-checklist&charitable_onboarding=cancel' );
}
// Override even more things if the user is returning from a login after the onboarding process by checking for reauth=1 in the query string.
if ( ! empty( $_GET['lostconnection'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$charitable_welcome_headline    = __( 'Welcome back!', 'charitable' );
	$charitable_introduction_text   = __( 'You seem to have been logged out of your WordPress admin during the onboarding process. In order for the onboarding to install plugins and update settings please go back the last step and submit while you are still logged into WordPress.', 'charitable' );
	$charitable_button_label        = __( 'Go Back', 'charitable' );
	$charitable_onboarding_url      = charitable_get_onboarding_url();
	$charitable_onboarding_url      = 'https://app.wpcharitable.com/setup-wizard-charitable_lite&resume=' . charitable_get_site_token();
	$charitable_welcome_go_back_url = admin_url( 'admin.php?page=charitable-setup-checklist&charitable_onboarding=cancel' );
}

?>
<div class="charitable-user-onboarding-wrap">
	<div class="charitable-user-onboarding-logo">
		<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) . 'images/onboarding/welcome/charitable-header-logo.png' ); ?>" alt="Charitable">
	</div>
	<div class="chartiable-user-onboarding-content">
		<h1><?php echo esc_html( $charitable_welcome_headline ); ?></h1>
		<p><?php echo esc_html( $charitable_introduction_text ); ?></p>
		<a href="<?php echo esc_url( $charitable_onboarding_url ); ?>" class="charitable-button-link"><?php echo esc_html( $charitable_button_label ); ?> →</a>
	</div>
	<div class="charitable-go-back"><a href="<?php echo esc_url( $charitable_welcome_go_back_url ); ?>">← <?php echo esc_html( $charitable_welcome_go_back_text ); ?></a></div>
	<div class="charitable-onbarding-notice">
		<p><?php echo esc_html__( 'Note: You will be transfered to an WPCharitable.com to complete the setup wizard.', 'charitable' ); ?></p>
	</div>
</div>
