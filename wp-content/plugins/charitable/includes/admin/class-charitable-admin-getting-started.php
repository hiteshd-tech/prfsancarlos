<?php
/**
 * Charitable Admin Getting Started.
 *
 * @package Charitable/Classes/Charitable_Admin_Getting_Started
 * @since 1.8.1.15
 * @version 1.8.1.15
 * @category Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charitable_Admin_Getting_Started page class.
 *
 * This page is shown when the plugin is first activated.
 *
 * @since 1.8.1.12
 * @version 1.8.1.13
 */
class Charitable_Admin_Getting_Started {

	/**
	 * The single instance of this class.
	 *
	 * @var Charitable_Admin_Getting_Started|null
	 */
	private static $instance = null;

	/**
	 * Returns and/or create the single instance of this class.
	 *
	 * @since  1.8.1.12
	 *
	 * @return Charitable_Admin_Getting_Started
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hidden welcome page slug.
	 *
	 * @since 1.8.1.12
	 */
	const SLUG = 'charitable-getting-started';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.8.1.12
	 */
	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'hooks' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
	}

	/**
	 * Initialize the class.
	 *
	 * @since 1.8.1.12
	 */
	public function init() {
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.8.1.12
	 */
	public function enqueues() {

		$min            = charitable_get_min_suffix();
		$style_version  = charitable_get_style_version();
		$script_version = charitable()->get_version();
		$assets_dir     = charitable()->get_path( 'assets', false );
		$dependencies   = array( 'jquery' );

		wp_enqueue_style(
			'charitable-admin-getting-started',
			charitable()->get_path( 'assets', false ) . "css/admin/charitable-admin-getting-started{$min}.css",
			null,
			$style_version
		);

		wp_enqueue_style(
			'jquery-confirm',
			charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.css',
			null,
			'3.3.4'
		);

		wp_enqueue_style(
			'charitable-font-awesome',
			charitable()->get_path( 'directory', false ) . 'assets/lib/font-awesome/font-awesome.min.css',
			null,
			'4.7.0'
		);

		wp_enqueue_script(
			'jquery-confirm',
			charitable()->get_path( 'directory', false ) . 'assets/lib/jquery.confirm/jquery-confirm.min.js',
			array( 'jquery' ),
			'3.3.4',
			false
		);

		wp_register_script(
			'charitable-admin-2.0',
			$assets_dir . 'js/admin/charitable-admin-2.0.js',
			$dependencies,
			$script_version,
			false
		);

		wp_enqueue_script( 'charitable-admin-2.0' );
	}

	/**
	 * Register all WP hooks.
	 *
	 * @since 1.8.1.12
	 */
	public function hooks() {

		// If user is in admin ajax or doing cron, return.
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// If user cannot manage_options, return.
		if ( ! charitable_current_user_can( 'administrator' ) ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'admin_head', array( $this, 'hide_menu' ) );
	}

	/**
	 * Register the pages to be used for the Getting_Started screen (and tabs).
	 *
	 * These pages will be removed from the Dashboard menu, so they will
	 * not actually show. Sneaky, sneaky.
	 *
	 * @since 1.8.1.12
	 */
	public function register() {

		// Getting started - shows after installation.
		add_dashboard_page(
			esc_html__( 'Welcome to Charitable', 'charitable' ),
			esc_html__( 'Welcome to Charitable', 'charitable' ),
			'manage_charitable_settings', // phpcs:ignore
			self::SLUG,
			array( $this, 'output' )
		);
	}

	/**
	 * Removed the dashboard pages from the admin menu.
	 *
	 * This means the pages are still available to us, but hidden.
	 *
	 * @since 1.8.1.12
	 */
	public function hide_menu() {

		remove_submenu_page( 'index.php', self::SLUG );
	}

	/**
	 * Getting Started screen. Shows after first install.
	 *
	 * @since 1.8.1.12
	 */
	public function output() {

		$class = charitable_is_pro() ? 'pro' : 'lite';

		// get first name of logged in user...
		$first_name = get_user_meta( get_current_user_id(), 'first_name', true );
		if ( ! empty( $first_name ) ) {
			$first_name = esc_html__( 'Hi', 'charitable' ) . ' ' . $first_name . '! ';
		}

		$stripe_connect_url = false;
		$gateway_mode       = false;

		// check if class exists...
		if ( class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {
			$stripe_gateway     = new Charitable_Gateway_Stripe_AM();
			$redirect_url       = admin_url( 'admin.php?page=charitable-getting-started&step=continue' );
			$stripe_connect_url = $stripe_gateway->get_stripe_connect_url( $redirect_url );
			$stripe_connected   = $stripe_gateway->maybe_stripe_connected();
			$gateway_mode       = ( charitable_get_option( 'test_mode' ) ) ? 'test' : 'live';
		}

		?>

		<div id="charitable-welcome" class="<?php echo esc_attr( sanitize_html_class( $class ) ); ?>">

			<div class="container">

				<div class="intro">

					<div class="wpchar-logo"></div>

					<div class="block">
						<h1><?php echo esc_html( $first_name ); ?><?php esc_html_e( 'Welcome to Charitable!', 'charitable' ); ?></h1>
						<h6><?php esc_html_e( 'Thank you for choosing Charitable - the best WordPress donation plugin for successful fundraiser campaigns and easy donation management.', 'charitable' ); ?></h6>
					</div>

					<a href="#" class="play-video" title="<?php esc_attr_e( 'Watch to learn more about Charitable', 'charitable' ); ?>">
						<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); ?>images/onboarding/getting-started/video-poster.jpg" width="100%" alt="<?php esc_attr_e( 'Watch how to create your first campaign', 'charitable' ); ?>" class="video-thumbnail">
					</a>

					<div class="block">

						<h6><?php esc_html_e( 'Charitable makes it easy to build campaigns and accept donations. You can create your first campaign right now or read our guide on how to create your first campaign.', 'charitable' ); ?></h6>

						<div class="charitable-button-wrap charitable-clear">
							<div class="left">
								<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder' ) ); ?>" class="charitable-button-link">
									<?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?>
								</a>
							</div>
							<div class="right">
								<a target="_blank" href="<?php echo esc_url( charitable_utm_link( 'https://www.wpcharitable.com/documentation/creating-your-first-campaign/', 'welcome-page', 'Read The Full Guide' ) ); ?>"
									class="charitable-button-link charitable-button-orange" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Read The Full Guide', 'charitable' ); ?>
								</a>
							</div>
						</div>

					</div>

				</div><!-- /.intro -->

				<?php do_action( 'charitable_welcome_intro_after' ); ?>

				<div class="gateway-connect">
					<div class="block">
						<h1><?php esc_html_e( 'Start Accepting Donations Quickly and Easily!', 'charitable' ); ?></h1>
						<h6><?php esc_html_e( 'Connect Charitable to a payment gateway to receive donations instantly.', 'charitable' ); ?></h6>
						<?php if ( $stripe_connect_url ) : ?>
							<div class="charitable-sub-container charitable-connect-stripe">
								<div>
									<div class="charitable-gateway-icon"><img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); // phpcs:ignore ?>/images/onboarding/stripe-checklist.svg" alt=""></div><div class="charitable-gateway-info-column"><h3><?php esc_html_e( 'Stripe', 'charitable' ); ?> <span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i><?php esc_html_e( 'Recommended', 'charitable' ); ?></span></h3>
										<?php if ( ! $stripe_connected ) : ?>
											<p><?php esc_html_e( 'You can create and connect to your Stripe account in just a few minutes.', 'charitable' ); ?></p>
										<?php else : ?>
											<p><?php printf( '%s %s %s.', esc_html__( 'Good news! You have connected to Stripe in', 'charitable' ), esc_html( $gateway_mode ), esc_html__( ' mode', 'charitable' ) ); ?></p>
										<?php endif; ?>
									</div>
								</div>

								<?php

								if ( ! $stripe_connected ) :

									?>
								<div>
									<a href="<?php echo esc_url( $stripe_connect_url ); ?>"><div class="wpcharitable-stripe-connect"><span><?php esc_html_e( 'Connect With', 'charitable' ); ?></span>&nbsp;&nbsp;<svg width="49" height="20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M48.4718 10.3338c0-3.41791-1.6696-6.11484-4.8607-6.11484-3.2045 0-5.1434 2.69693-5.1434 6.08814 0 4.0187 2.289 6.048 5.5743 6.048 1.6023 0 2.8141-.3604 3.7296-.8678v-2.6702c-.9155.4539-1.9658.7343-3.2987.7343-1.3061 0-2.464-.4539-2.6121-2.0294h6.5841c0-.1735.0269-.8678.0269-1.1882Zm-6.6514-1.26838c0-1.50868.929-2.13618 1.7773-2.13618.8213 0 1.6965.6275 1.6965 2.13618h-3.4738Zm-8.5499-4.84646c-1.3195 0-2.1678.61415-2.639 1.04139l-.1751-.82777h-2.9621V20l3.3661-.7076.0134-3.7784c.4847.3471 1.1984.8411 2.3832.8411 2.4102 0 4.6048-1.9225 4.6048-6.1548-.0134-3.87186-2.235-5.98134-4.5913-5.98134Zm-.8079 9.19894c-.7944 0-1.2656-.2804-1.5888-.6275l-.0134-4.95328c.35-.38719.8348-.65421 1.6022-.65421 1.2253 0 2.0735 1.36182 2.0735 3.11079 0 1.7891-.8347 3.1242-2.0735 3.1242Zm-9.6001-9.98666 3.3796-.72096V0l-3.3796.70761v2.72363Zm0 1.01469h3.3796V16.1282h-3.3796V4.44593Zm-3.6219.98798-.2154-.98798h-2.9083V16.1282h3.3661V8.21095c.7944-1.02804 2.1408-.84112 2.5582-.69426V4.44593c-.4309-.16022-2.0062-.45394-2.8006.98798Zm-6.7322-3.88518-3.2853.69426-.01346 10.69421c0 1.976 1.49456 3.4313 3.48726 3.4313 1.1041 0 1.912-.2003 2.3563-.4406v-2.7103c-.4309.1736-2.5583.7877-2.5583-1.1882V7.28972h2.5583V4.44593h-2.5583l.0135-2.8972ZM3.40649 7.83712c0-.5207.43086-.72096 1.14447-.72096 1.0233 0 2.31588.30707 3.33917.85447V4.83311c-1.11755-.44059-2.22162-.61415-3.33917-.61415C1.81769 4.21896 0 5.63418 0 7.99733c0 3.68487 5.11647 3.09747 5.11647 4.68627 0 .6141-.53858.8144-1.29258.8144-1.11755 0-2.54477-.4539-3.675782-1.0681v3.1776c1.252192.534 2.517842.761 3.675782.761 2.80059 0 4.72599-1.3752 4.72599-3.765-.01346-3.97867-5.14339-3.27106-5.14339-4.76638Z" fill="#fff"/></svg></div></a>
								</div>

								<?php else : ?>

								<div>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' ) ); ?>  " class="charitable-button-link charitable-button"><?php esc_html_e( 'Connected', 'charitable' ); ?></a>
								</div>

								<?php endif; ?>

							</div>
							<?php

							if ( ! $stripe_connected ) :

								?>
							<div class="charitable-gateway-footer">
								<?php // translators: %s is the URL. ?>
								<p>
								<?php
								echo wp_kses(
									sprintf(
									/* translators: %s is the URL */
										__( '<strong>Stripe not available in your country?</strong> Charitable works with payment gateways like PayPal, Authorize.net, Braintree, Payrexx, PayUMoney, GoCardless, and others. <a target="_blank" href="%s">View additional payment options</a> available with PRO extensions.', 'charitable' ),
										esc_url( admin_url( 'admin.php?page=charitable-addons' ) )
									),
									array(
										'strong' => array(),
										'a'      => array(
											'href'   => array(),
											'target' => array(),
										),
									)
								);
								?>
								</p>
							</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="charitable-checklist-section">
					<div class="block">
						<h1><?php esc_html_e( 'Do the Charitable Checklist!', 'charitable' ); ?></h1>
						<h6><?php esc_html_e( 'The fastest way to get to speed with Charitable! Confirm basic settings and create your first campaign with our guided setup wizard.', 'charitable' ); ?></h6>
						<div class="button-wrap">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-setup-checklist#connect-gateway' ) ); ?>" class="charitable-btn charitable-btn-lg charitable-btn-orange">
								<?php esc_html_e( 'Start the Charitable Checklist', 'charitable' ); ?>
							</a>
						</div>
					</div>
				</div>

				<div class="charitable-features">

					<div class="block">

						<h1><?php esc_html_e( 'Charitable Features and Addons', 'charitable' ); ?></h1>
						<h6><?php esc_html_e( 'Charitable is both easy to use and extremely powerful. We have tons of helpful features that will provide you with everything you need.', 'charitable' ); ?></h6>

						<div class="charitable-feature-list">

							<div class="feature-block first">
								<div class="feature-block-icon drag-drop">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0.5 43.67 43.67"><rect width="43.6667" transform="translate(0 0.5)" fill="white"></rect><path d="M8.33331 16.8333L0.999981 9.49992M0.999981 9.49992L0.99998 16.8333M0.999981 9.49992L8.33331 9.49992" stroke="#BA8DE8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M19.3333 16.8333L26.6666 9.49992M26.6666 9.49992L19.3333 9.49992M26.6666 9.49992L26.6666 16.8333" stroke="#BA8DE8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M8.33331 27.8333L0.99998 35.1666M0.99998 35.1666L8.33331 35.1666M0.99998 35.1666L0.99998 27.8333" stroke="#BA8DE8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M19.3333 27.8333L26.6666 35.1666M26.6666 35.1666L26.6666 27.8333M26.6666 35.1666L19.3333 35.1666" stroke="#BA8DE8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Drag &amp; Drop Campaign Builder', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Easily create a beautiful fundraising campaign in just a few minutes without writing any code.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block last">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" width="45"  viewBox="0 0 35 29" fill="none"><path d="M1 27.1667V25.3333C1 21.2832 4.28325 18 8.33333 18H15.6667C19.7168 18 23 21.2832 23 25.3333V27.1667M23 12.5C26.0376 12.5 28.5 10.0376 28.5 7C28.5 3.96243 26.0376 1.5 23 1.5M34 27.1667V25.3333C34 21.2832 30.7168 18 26.6667 18H25.75M17.5 7C17.5 10.0376 15.0376 12.5 12 12.5C8.96243 12.5 6.5 10.0376 6.5 7C6.5 3.96243 8.96243 1.5 12 1.5C15.0376 1.5 17.5 3.96243 17.5 7Z" stroke="#FC9A2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Peer-to-Peer Fundraising', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Let supporters create campaigns to fundraise for your cause individually or in teams.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block first">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0.5 5 35 35"><path d="M3.33333 28L32.6667 28" stroke="#FC9A2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3.33333 17L32.6667 17" stroke="#FC9A2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="18" cy="22.5" r="16.5" stroke="#FC9A2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle><path d="M18 38.6663L17.2858 39.3662C17.4738 39.5581 17.7313 39.6663 18 39.6663C18.2687 39.6663 18.5262 39.5581 18.7142 39.3662L18 38.6663ZM18 6.33301L18.7142 5.63311C18.5262 5.44117 18.2687 5.33301 18 5.33301C17.7313 5.33301 17.4738 5.44117 17.2858 5.63311L18 6.33301ZM23.6 22.4997C23.6 28.5216 21.1927 33.9794 17.2858 37.9664L18.7142 39.3662C22.9727 35.0205 25.6 29.0659 25.6 22.4997H23.6ZM17.2858 7.03291C21.1927 11.0199 23.6 16.4777 23.6 22.4997H25.6C25.6 15.9334 22.9727 9.97882 18.7142 5.63311L17.2858 7.03291ZM12.4 22.4997C12.4 16.4777 14.8073 11.0199 18.7142 7.03291L17.2858 5.63311C13.0273 9.97882 10.4 15.9334 10.4 22.4997H12.4ZM18.7142 37.9664C14.8073 33.9794 12.4 28.5216 12.4 22.4997H10.4C10.4 29.0659 13.0273 35.0205 17.2858 39.3662L18.7142 37.9664Z" fill="#FC9A2A"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Offline Payments', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Offer a secure and convenient way for donors who prefer giving offline via cash, checks, or other methods.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block last">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="6.1 8.02 31.81 28.97"><path d="M36.9032 26.0484V18.6935C36.9032 16.4844 35.1123 14.6935 32.9032 14.6935H17.0322M17.0322 14.6935L22.7096 9.01611M17.0322 14.6935L22.7096 20.371" stroke="#72BFC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7.09681 18.9516L7.09681 26.3065C7.09681 28.5156 8.88767 30.3065 11.0968 30.3065L26.9678 30.3065M26.9678 30.3065L21.2904 35.9839M26.9678 30.3065L21.2904 24.629" stroke="#72BFC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Recurring Donations', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Let your donors automatically contribute any amount at their desired frequency.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block first">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 8.5 35.86 28"><path d="M31.0801 35.4852C26.3874 34.9881 20.9201 35.0785 15.0884 35.4852C14.1317 35.5756 13.1293 35.2593 12.4004 34.6266L1.32924 25.4076C0.828075 25.0009 0.919196 24.1875 1.46592 23.9163C1.46592 23.9163 1.51148 23.9163 1.51148 23.8711C3.51613 22.8769 5.93082 23.2384 7.66211 24.6394L13.4938 29.1133" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M22.7881 29.1583L15.2251 29.7909C14.0405 29.8813 13.0382 29.0227 12.9471 27.8929C12.856 26.7179 13.7216 25.7237 14.8606 25.6334L20.2823 25.1814C21.4213 25.0911 22.5603 24.7295 23.6082 24.1872C26.9341 22.5152 31.6268 24.4132 34.8616 27.1247" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M14.3593 18.2219C14.3593 19.3516 15.4527 20.2554 16.8195 20.2554C18.1863 20.2554 19.2798 19.3516 19.2798 18.2219C19.2798 15.7363 14.4048 15.42 14.4048 12.8893C14.4048 11.7595 15.4983 10.8557 16.8651 10.8557C18.2319 10.8557 19.3253 11.7595 19.3253 12.8893" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M16.8196 20.2559V21.6116" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M16.8196 9.5V10.9009" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Fee Relief', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Allow donors to choose to cover transaction fees so you get the full donation amount.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block last">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 5 27.67 35"><path d="M23.6667 23.333C23.1144 23.333 22.6667 23.7807 22.6667 24.333C22.6667 24.8853 23.1144 25.333 23.6667 25.333V23.333ZM5.66669 16.833C5.66669 12.4147 9.24841 8.83301 13.6667 8.83301V6.83301C8.14384 6.83301 3.66669 11.3102 3.66669 16.833H5.66669ZM5.66669 23.6663V16.833H3.66669V23.6663H5.66669ZM2 27.333C2 26.2284 2.89543 25.333 4 25.333V23.333C1.79086 23.333 0 25.1239 0 27.333H2ZM2 29.6663V27.333H0V29.6663H2ZM4.66669 30.6663H3V32.6663H4.66669V30.6663ZM23 30.6663H4.66669V32.6663H23V30.6663ZM24.6667 30.6663H23V32.6663H24.6667V30.6663ZM25.6667 27.333V29.6663H27.6667V27.333H25.6667ZM23.6667 25.333C24.7712 25.333 25.6667 26.2284 25.6667 27.333H27.6667C27.6667 25.1239 25.8758 23.333 23.6667 23.333V25.333ZM22 16.833V23.6664H24V16.833H22ZM14 8.83301C18.4183 8.83301 22 12.4147 22 16.833H24C24 11.3102 19.5229 6.83301 14 6.83301V8.83301ZM13.6667 8.83301H14V6.83301H13.6667V8.83301ZM23.6667 23.333C23.8508 23.333 24 23.4823 24 23.6664H22C22 24.5868 22.7462 25.333 23.6667 25.333V23.333ZM24.6667 32.6663C26.3235 32.6663 27.6667 31.3232 27.6667 29.6663H25.6667C25.6667 30.2186 25.219 30.6663 24.6667 30.6663V32.6663ZM0 29.6663C0 31.3232 1.34315 32.6663 3 32.6663V30.6663C2.44772 30.6663 2 30.2186 2 29.6663H0ZM3.66669 23.6663C3.66669 23.4822 3.81592 23.333 4 23.333V25.333C4.92049 25.333 5.66669 24.5868 5.66669 23.6663H3.66669Z" fill="#72BFC5"></path><path d="M17.1289 36.941C16.8239 37.5664 16.3472 38.0921 15.7546 38.4567C15.1619 38.8212 14.4778 39.0097 13.7821 39C13.0864 38.9903 12.4078 38.7828 11.8256 38.4018C11.2433 38.0208 10.7816 37.482 10.4941 36.8484" stroke="#72BFC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M13.8333 6V7.83333" stroke="#72BFC5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Notifications', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Build strong relationships through easy, instant communication via emails and third-party tools.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block first">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0.5 10.5 35 25.83"><rect x="1.5" y="11.5" width="33" height="23.8333" rx="2" stroke="#3D914B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></rect><path d="M1.5 21.583H33.5833" stroke="#3D914B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Payments Made Easy', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Easily collect donations via credit cards, PayPal, and more... without hiring a developer.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block last">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0.5 44.38 46"><path d="M37.0384 20.4431C36.5256 30.3584 28.0919 38.0513 18.1196 37.5384C8.20427 37.0256 0.511366 28.5919 1.02423 18.6196C1.53709 8.70427 9.97079 1.01137 19.9431 1.52423C29.8584 2.03709 37.4943 10.4708 37.0384 20.4431Z" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M33.4116 31.2649L42.5176 40.35C43.6702 41.5073 43.6702 43.4748 42.5176 44.6321C41.3649 45.7894 39.4054 45.7894 38.2528 44.6321L29.1469 35.4892" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M23.0836 22.3052L26.716 23.3119C28.026 23.6828 28.9192 24.7954 28.9192 26.0141V27.2328C28.9192 28.0275 28.5619 28.8223 27.8474 29.4051C26.4778 30.5178 23.6195 32.1603 18.8557 32.1603C14.1514 32.1603 11.5313 30.5708 10.2213 29.4581C9.56624 28.8753 9.20895 28.1335 9.20895 27.3387V26.0141C9.20895 24.7954 10.1022 23.6828 11.4122 23.3119L15.0446 22.3052" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M24.539 13.3363C24.539 16.6977 22.1126 20.1152 19.064 20.1152C16.0776 20.1152 13.5889 16.6977 13.5889 13.3363C13.5889 9.97486 16.0153 8.07007 19.064 8.07007C22.0504 8.07007 24.539 10.0309 24.539 13.3363Z" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M13.5889 31.0653V27.7803" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path><path d="M25 31.285V28" stroke="#D27A7A" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"></path></svg>
								</div>
								<h5><?php esc_html_e( 'Insightful Reports', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Gain a deeper understanding of your supporter base and find new ways to increase their contributions.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block first">
								<div class="feature-block-icon">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0.24 5 33.51 35"><path d="M5.40994 11.5742L4.90994 12.4402L4.90994 12.4402L5.40994 11.5742ZM4.04391 11.9402L3.17789 11.4402H3.17789L4.04391 11.9402ZM1.37724 16.559L2.24327 17.059H2.24327L1.37724 16.559ZM1.74327 17.9251L1.24327 18.7911H1.24327L1.74327 17.9251ZM1.74328 27.0742L1.24328 26.2082L1.24328 26.2082L1.74328 27.0742ZM1.37725 28.4402L2.24328 27.9402H2.24328L1.37725 28.4402ZM4.04392 33.059L3.17789 33.559H3.17789L4.04392 33.059ZM5.40994 33.4251L5.90994 34.2911L5.40994 33.4251ZM28.5901 33.4251L28.0901 34.2911L28.5901 33.4251ZM29.9561 33.059L30.8221 33.559L30.8221 33.559L29.9561 33.059ZM32.6227 28.4402L33.4888 28.9402H33.4888L32.6227 28.4402ZM32.2567 27.0742L31.7567 27.9402L32.2567 27.0742ZM32.2567 17.9251L31.7567 17.059L31.7567 17.059L32.2567 17.9251ZM32.6228 16.559L33.4888 16.059L32.6228 16.559ZM29.9561 11.9402L29.0901 12.4402H29.0901L29.9561 11.9402ZM28.5901 11.5742L29.0901 12.4402V12.4402L28.5901 11.5742ZM21.3254 12.383L20.9318 13.3023L21.3254 12.383ZM23.5962 13.6964L22.996 14.4962L23.5962 13.6964ZM28.4282 20.1355L27.9282 19.2694L28.4282 20.1355ZM23.5967 31.3033L24.197 32.1031L23.5967 31.3033ZM24.7613 31.2145L25.2613 30.3485L24.7613 31.2145ZM28.4283 24.8639L27.9283 25.7299L28.4283 24.8639ZM27.9225 23.8126L26.9295 23.6945L27.9225 23.8126ZM12.6747 32.617L12.2811 33.5363L12.6747 32.617ZM9.23874 31.2145L9.73874 32.0805L9.23874 31.2145ZM10.4033 31.3033L11.0036 30.5035L10.4033 31.3033ZM21.3254 32.617L20.9318 31.6977L21.3254 32.617ZM5.57171 24.8639L5.07171 23.9978L5.57171 24.8639ZM6.07758 21.1868L5.08458 21.0687L6.07758 21.1868ZM10.4038 13.6964L9.80355 12.8965L10.4038 13.6964ZM9.23927 13.7851L9.73927 12.9191L9.23927 13.7851ZM12.6747 12.383L12.2811 11.4637L12.6747 12.383ZM14.3333 7V7V5C13.2288 5 12.3333 5.89543 12.3333 7H14.3333ZM14.3333 11.4187V7H12.3333V11.4187H14.3333ZM11.004 14.4962C11.6383 14.0202 12.3306 13.6181 13.0682 13.3023L12.2811 11.4637C11.395 11.8431 10.5641 12.3258 9.80355 12.8965L11.004 14.4962ZM4.90994 12.4402L8.73927 14.6511L9.73927 12.9191L5.90994 10.7082L4.90994 12.4402ZM4.90994 12.4402L4.90994 12.4402L5.90994 10.7082C4.95335 10.1559 3.73017 10.4837 3.17789 11.4402L4.90994 12.4402ZM2.24327 17.059L4.90994 12.4402L3.17789 11.4402L0.511219 16.059L2.24327 17.059ZM2.24327 17.059H2.24327L0.51122 16.059C-0.0410652 17.0156 0.286684 18.2388 1.24327 18.7911L2.24327 17.059ZM6.07179 19.2694L2.24327 17.059L1.24327 18.7911L5.07179 21.0015L6.07179 19.2694ZM7.00001 22.5C7.00001 22.0952 7.02401 21.6964 7.07058 21.305L5.08458 21.0687C5.02871 21.5384 5.00001 22.0161 5.00001 22.5H7.00001ZM7.07051 23.6945C7.02399 23.3032 7.00001 22.9046 7.00001 22.5H5.00001C5.00001 22.9837 5.02868 23.4611 5.0845 23.9306L7.07051 23.6945ZM2.24328 27.9402L6.07171 25.7299L5.07171 23.9978L1.24328 26.2082L2.24328 27.9402ZM2.24328 27.9402L2.24328 27.9402L1.24328 26.2082C0.286694 26.7605 -0.0410563 27.9837 0.511229 28.9402L2.24328 27.9402ZM4.90995 32.559L2.24328 27.9402L0.511229 28.9402L3.17789 33.559L4.90995 32.559ZM4.90994 32.559H4.90995L3.17789 33.559C3.73018 34.5156 4.95336 34.8434 5.90994 34.2911L4.90994 32.559ZM8.73874 30.3485L4.90994 32.559L5.90994 34.2911L9.73874 32.0805L8.73874 30.3485ZM13.0682 31.6977C12.3304 31.3818 11.638 30.9796 11.0036 30.5035L9.80302 32.1031C10.5637 32.674 11.3948 33.1569 12.2811 33.5363L13.0682 31.6977ZM14.3333 38V33.5813H12.3333V38H14.3333ZM14.3333 38H14.3333H12.3333C12.3333 39.1046 13.2288 40 14.3333 40V38ZM19.6667 38H14.3333V40H19.6667V38ZM19.6667 38V40C20.7712 40 21.6667 39.1046 21.6667 38H19.6667ZM19.6667 33.5813V38H21.6667V33.5813H19.6667ZM22.9964 30.5035C22.3621 30.9796 21.6696 31.3818 20.9318 31.6977L21.7189 33.5363C22.6053 33.1569 23.4363 32.674 24.197 32.1031L22.9964 30.5035ZM29.0901 32.559L25.2613 30.3485L24.2613 32.0805L28.0901 34.2911L29.0901 32.559ZM29.0901 32.559L29.0901 32.559L28.0901 34.2911C29.0466 34.8434 30.2698 34.5156 30.8221 33.559L29.0901 32.559ZM31.7567 27.9402L29.0901 32.559L30.8221 33.559L33.4888 28.9402L31.7567 27.9402ZM31.7567 27.9402H31.7567L33.4888 28.9402C34.0411 27.9837 33.7133 26.7605 32.7567 26.2082L31.7567 27.9402ZM27.9283 25.7299L31.7567 27.9402L32.7567 26.2082L28.9283 23.9979L27.9283 25.7299ZM27 22.5C27 22.9046 26.976 23.3032 26.9295 23.6945L28.9155 23.9306C28.9713 23.4612 29 22.9837 29 22.5H27ZM26.9294 21.305C26.976 21.6964 27 22.0952 27 22.5H29C29 22.0161 28.9713 21.5384 28.9154 21.0687L26.9294 21.305ZM31.7567 17.059L27.9282 19.2694L28.9282 21.0015L32.7567 18.7911L31.7567 17.059ZM31.7567 17.059H31.7567L32.7567 18.7911C33.7133 18.2388 34.0411 17.0156 33.4888 16.059L31.7567 17.059ZM29.0901 12.4402L31.7567 17.059L33.4888 16.059L30.8221 11.4402L29.0901 12.4402ZM29.0901 12.4402L29.0901 12.4402L30.8221 11.4402C30.2698 10.4837 29.0467 10.1559 28.0901 10.7082L29.0901 12.4402ZM25.2607 14.6511L29.0901 12.4402L28.0901 10.7082L24.2607 12.9191L25.2607 14.6511ZM20.9318 13.3023C21.6694 13.6181 22.3617 14.0202 22.996 14.4962L24.1965 12.8965C23.4359 12.3258 22.6051 11.8431 21.7189 11.4637L20.9318 13.3023ZM19.6667 7V11.4187H21.6667V7H19.6667ZM19.6667 7H19.6667H21.6667C21.6667 5.89543 20.7712 5 19.6667 5V7ZM14.3333 7H19.6667V5H14.3333V7ZM21.7189 11.4637C21.695 11.4535 21.6794 11.4386 21.6717 11.4274C21.6681 11.4223 21.6669 11.419 21.6667 11.4182C21.6665 11.4178 21.6666 11.4178 21.6666 11.4181C21.6666 11.4183 21.6667 11.4184 21.6667 11.4186C21.6667 11.4187 21.6667 11.4188 21.6667 11.4187H19.6667C19.6667 12.279 20.2089 12.9928 20.9318 13.3023L21.7189 11.4637ZM24.2607 12.9191C24.2607 12.9191 24.2608 12.919 24.2609 12.919C24.261 12.9189 24.2612 12.9189 24.2613 12.9188C24.2616 12.9187 24.2616 12.9187 24.2612 12.9188C24.2604 12.919 24.257 12.9196 24.2508 12.9191C24.2374 12.918 24.2171 12.912 24.1965 12.8965L22.996 14.4962C23.6257 14.9688 24.5156 15.0813 25.2607 14.6511L24.2607 12.9191ZM28.9154 21.0687C28.9124 21.043 28.9174 21.0223 28.9232 21.0101C28.9259 21.0045 28.9281 21.0019 28.9286 21.0012C28.9289 21.0009 28.929 21.0009 28.9287 21.0011C28.9286 21.0012 28.9285 21.0013 28.9284 21.0014C28.9283 21.0015 28.9282 21.0015 28.9282 21.0015L27.9282 19.2694C27.184 19.6991 26.8366 20.5244 26.9294 21.305L28.9154 21.0687ZM24.197 32.1031C24.2176 32.0876 24.238 32.0816 24.2514 32.0805C24.2575 32.08 24.2609 32.0806 24.2617 32.0808C24.2621 32.0809 24.2621 32.0809 24.2618 32.0808C24.2617 32.0807 24.2616 32.0807 24.2614 32.0806C24.2613 32.0806 24.2612 32.0805 24.2613 32.0805L25.2613 30.3485C24.5161 29.9183 23.6262 30.0309 22.9964 30.5035L24.197 32.1031ZM28.9283 23.9979C28.9283 23.9978 28.9283 23.9979 28.9285 23.998C28.9286 23.998 28.9287 23.9981 28.9288 23.9982C28.929 23.9984 28.929 23.9984 28.9287 23.9981C28.9282 23.9975 28.9259 23.9948 28.9233 23.9892C28.9175 23.977 28.9125 23.9563 28.9155 23.9306L26.9295 23.6945C26.8367 24.475 27.1841 25.3003 27.9283 25.7299L28.9283 23.9979ZM12.2811 33.5363C12.3051 33.5465 12.3206 33.5614 12.3283 33.5726C12.3319 33.5777 12.3331 33.581 12.3334 33.5818C12.3335 33.5822 12.3335 33.5822 12.3334 33.5819C12.3334 33.5817 12.3334 33.5816 12.3334 33.5814C12.3333 33.5813 12.3333 33.5812 12.3333 33.5813H14.3333C14.3333 32.721 13.7911 32.0072 13.0682 31.6977L12.2811 33.5363ZM9.73874 32.0805C9.73877 32.0805 9.73871 32.0806 9.73859 32.0806C9.73846 32.0807 9.73833 32.0807 9.7382 32.0808C9.7379 32.0809 9.7379 32.0809 9.7383 32.0808C9.7391 32.0806 9.74248 32.08 9.74864 32.0805C9.76204 32.0816 9.78243 32.0876 9.80302 32.1031L11.0036 30.5035C10.3739 30.0308 9.48394 29.9182 8.73874 30.3485L9.73874 32.0805ZM21.6667 33.5813C21.6667 33.5812 21.6667 33.5813 21.6667 33.5814C21.6667 33.5816 21.6666 33.5817 21.6666 33.5819C21.6666 33.5822 21.6665 33.5822 21.6667 33.5818C21.6669 33.581 21.6681 33.5777 21.6717 33.5726C21.6794 33.5614 21.695 33.5465 21.7189 33.5363L20.9318 31.6977C20.2089 32.0072 19.6667 32.721 19.6667 33.5813H21.6667ZM5.0845 23.9306C5.08755 23.9563 5.08249 23.977 5.07673 23.9892C5.07407 23.9948 5.07184 23.9975 5.07129 23.9981C5.071 23.9984 5.07098 23.9984 5.07122 23.9982C5.07133 23.9981 5.07145 23.998 5.07156 23.9979C5.07167 23.9979 5.07173 23.9978 5.07171 23.9978L6.07171 25.7299C6.81588 25.3003 7.16331 24.475 7.07051 23.6945L5.0845 23.9306ZM5.07179 21.0015C5.07181 21.0015 5.07175 21.0015 5.07164 21.0014C5.07152 21.0013 5.07141 21.0012 5.0713 21.0011C5.07106 21.0009 5.07108 21.0009 5.07137 21.0012C5.07192 21.0019 5.07415 21.0045 5.0768 21.0101C5.08257 21.0223 5.08763 21.043 5.08458 21.0687L7.07058 21.305C7.16343 20.5244 6.81599 19.6991 6.07179 19.2694L5.07179 21.0015ZM9.80355 12.8965C9.78296 12.912 9.76256 12.918 9.74918 12.9191C9.74301 12.9196 9.73963 12.919 9.73883 12.9188C9.73843 12.9187 9.73843 12.9187 9.73873 12.9188C9.73886 12.9189 9.73899 12.9189 9.73912 12.919C9.73924 12.919 9.7393 12.9191 9.73927 12.9191L8.73927 14.6511C9.48443 15.0813 10.3743 14.9688 11.004 14.4962L9.80355 12.8965ZM12.3333 11.4187C12.3333 11.4188 12.3333 11.4187 12.3334 11.4186C12.3334 11.4184 12.3334 11.4183 12.3334 11.4181C12.3335 11.4178 12.3335 11.4178 12.3334 11.4182C12.3331 11.419 12.3319 11.4223 12.3283 11.4274C12.3206 11.4386 12.3051 11.4535 12.2811 11.4637L13.0682 13.3023C13.7911 12.9928 14.3333 12.279 14.3333 11.4187H12.3333Z" fill="#BA8DE8"></path><circle cx="17" cy="22.5" r="5.5" stroke="#BA8DE8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle></svg>
								</div>
								<h5><?php esc_html_e( 'Marketing Integrations', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Automatically send your campaign details to your favorite email marketing tools.', 'charitable' ); ?></p>
							</div>

							<div class="feature-block last">
								<div class="feature-block-icon">
								<svg xmlns="http://www.w3.org/2000/svg" xmlns-xlink="http://www.w3.org/1999/xlink" fill="#008830" version="1.1" id="Capa_1" xml:space="preserve" viewBox="17.11 0 180.05 214.27"><g><path d="M196.926,55.171c-0.11-5.785-0.215-11.25-0.215-16.537c0-4.142-3.357-7.5-7.5-7.5c-32.075,0-56.496-9.218-76.852-29.01 c-2.912-2.832-7.546-2.831-10.457,0c-20.354,19.792-44.771,29.01-76.844,29.01c-4.142,0-7.5,3.358-7.5,7.5 c0,5.288-0.104,10.755-0.215,16.541c-1.028,53.836-2.436,127.567,87.331,158.682c0.796,0.276,1.626,0.414,2.456,0.414 c0.83,0,1.661-0.138,2.456-0.414C199.36,182.741,197.954,109.008,196.926,55.171z M107.131,198.812 c-76.987-27.967-75.823-89.232-74.79-143.351c0.062-3.248,0.122-6.396,0.164-9.482c30.04-1.268,54.062-10.371,74.626-28.285 c20.566,17.914,44.592,27.018,74.634,28.285c0.042,3.085,0.102,6.231,0.164,9.477C182.961,109.577,184.124,170.844,107.131,198.812 z"></path><path d="M132.958,81.082l-36.199,36.197l-15.447-15.447c-2.929-2.928-7.678-2.928-10.606,0c-2.929,2.93-2.929,7.678,0,10.607 l20.75,20.75c1.464,1.464,3.384,2.196,5.303,2.196c1.919,0,3.839-0.732,5.303-2.196l41.501-41.5 c2.93-2.929,2.93-7.678,0.001-10.606C140.636,78.154,135.887,78.153,132.958,81.082z"></path></g></svg>
								</div>
								<h5><?php esc_html_e( 'Safety and Security', 'charitable' ); ?></h5>
								<p><?php esc_html_e( 'Comply with industry security standards by ensuring sensitive payment details are never stored.', 'charitable' ); ?></p>
							</div>

						</div>

						<div class="button-wrap">
							<a href="<?php echo esc_url( charitable_utm_link( 'https://wpcharitable.com/lite-upgrade/', 'welcome-page', 'See All Features' ) ); ?>"
								class="charitable-button-link charitable-button-grey" rel="noopener noreferrer" target="_blank">
								<?php esc_html_e( 'See All Features', 'charitable' ); ?>
							</a>
						</div>

					</div>

				</div><!-- /.features -->

				<div class="charitable-upgrade-cta upgrade">

					<div class="block">

						<div class="left">
							<h2><?php esc_html_e( 'Upgrade to PRO', 'charitable' ); ?></h2>
							<ul>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'No Platform Fees', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Recurring Donations', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Advanced Reporting', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Payment Options', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Fee Relief', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Gift Aid', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Campaign Updates', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Geolocation', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'PDF Receipts', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Annual Receipts', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Videos', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Marketing Integrations', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Peer-to-Peer Fundraising', 'charitable' ); ?></li>
								<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Spam Blocking', 'charitable' ); ?></li>
							</ul>
						</div>

						<div class="right">
							<h2><span><?php esc_html_e( 'PRO', 'charitable' ); ?></span></h2>
							<div class="price">
								<span class="amount">199</span><br>
								<span class="term"><?php esc_html_e( 'per year', 'charitable' ); ?></span>
							</div>
							<a href="<?php echo esc_url( charitable_admin_upgrade_link( 'welcome', 'Upgrade Now CTA Section' ) ); ?>" rel="noopener noreferrer" target="_blank"
								class="charitable-button-link charitable-button-orange charitable-upgrade-modal">
								<?php esc_html_e( 'Upgrade Now', 'charitable' ); ?>
							</a>
						</div>

					</div>

				</div>

				<div class="testimonials upgrade">

					<div class="block">

						<h1><?php esc_html_e( 'Testimonials', 'charitable' ); ?></h1>

						<div class="testimonial-block charitable-clear">
							<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); // phpcs:ignore ?>/images/onboarding/getting-started/stephen-circle2x.jpg">
							<div class="testimonials-stars">
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
							</div>
							<p><?php esc_html_e( '"This plugin is exactly what our small, parent-run organization needed... Easy to set up. Easy to customize... and superior support! Thanks so much for this full-featured software.”', 'charitable' ); ?></p>
							<p class="charitable-cerified-user"><strong><?php esc_html_e( 'Stephen Weinberg', 'charitable' ); ?></strong> <svg xmlns="http://www.w3.org/2000/svg" width="105" height="22" viewBox="0 0 105 22" fill="none"><rect x="0.5" y="0.5" width="104" height="20.3636" rx="10.1818" stroke="#C4D6C6" stroke-linejoin="round"></rect><path d="M12.8364 17.3636L11.6273 15.3273L9.33636 14.8182L9.55909 12.4636L8 10.6818L9.55909 8.9L9.33636 6.54545L11.6273 6.03636L12.8364 4L15 4.92273L17.1636 4L18.3727 6.03636L20.6636 6.54545L20.4409 8.9L22 10.6818L20.4409 12.4636L20.6636 14.8182L18.3727 15.3273L17.1636 17.3636L15 16.4409L12.8364 17.3636ZM14.3318 12.9409L17.9273 9.34545L17.0364 8.42273L14.3318 11.1273L12.9636 9.79091L12.0727 10.6818L14.3318 12.9409Z" fill="#65AD27"></path><path d="M28.6155 13.6819L26.1405 7.17488H27.0855L28.6695 11.3329C28.8135 11.7019 28.9395 12.0709 29.0745 12.5569C29.2275 12.0439 29.3805 11.6119 29.4885 11.3239L31.0635 7.17488H31.9815L29.5335 13.6819H28.6155ZM37.3361 13.6819H33.3671V7.17488H37.3361V7.98488H34.2491V10.0189H37.0301V10.8019H34.2491V12.8629H37.3361V13.6819ZM40.0361 13.6819H39.1541V7.17488H41.6111C43.0061 7.17488 43.8341 7.91288 43.8341 9.12788C43.8341 10.0279 43.3841 10.6759 42.5741 10.9369L43.8971 13.6819H42.9161L41.7011 11.1079H40.0361V13.6819ZM40.0361 7.96688V10.3249H41.6201C42.4391 10.3249 42.9071 9.88388 42.9071 9.13688C42.9071 8.38088 42.4211 7.96688 41.6111 7.96688H40.0361ZM46.4648 7.17488V13.6819H45.5828V7.17488H46.4648ZM51.9602 10.9189H49.4042V13.6819H48.5222V7.17488H52.4462V7.98488H49.4042V10.1179H51.9602V10.9189ZM54.9364 7.17488V13.6819H54.0544V7.17488H54.9364ZM60.9628 13.6819H56.9938V7.17488H60.9628V7.98488H57.8758V10.0189H60.6568V10.8019H57.8758V12.8629H60.9628V13.6819ZM64.9319 13.6819H62.7809V7.17488H64.8959C66.8399 7.17488 68.1539 8.48888 68.1539 10.4329C68.1539 12.3679 66.8579 13.6819 64.9319 13.6819ZM64.8329 7.98488H63.6629V12.8629H64.8689C66.3179 12.8629 67.2359 11.9269 67.2359 10.4329C67.2359 8.92088 66.3179 7.98488 64.8329 7.98488ZM72.7719 11.3689V7.17488H73.6539V11.3149C73.6539 12.3589 74.2299 12.9349 75.2649 12.9349C76.2909 12.9349 76.8579 12.3499 76.8579 11.3149V7.17488H77.7489V11.3689C77.7489 12.8629 76.7949 13.7899 75.2649 13.7899C73.7259 13.7899 72.7719 12.8719 72.7719 11.3689ZM79.3774 8.92988C79.3774 7.81388 80.2774 7.05788 81.6184 7.05788C82.8604 7.05788 83.6614 7.75088 83.7334 8.87588H82.8424C82.7974 8.22788 82.3384 7.84988 81.6094 7.84988C80.7904 7.84988 80.2594 8.26388 80.2594 8.90288C80.2594 9.42488 80.5564 9.73988 81.1684 9.88388L82.2394 10.1359C83.3014 10.3789 83.8414 10.9549 83.8414 11.8729C83.8414 13.0429 82.9324 13.7899 81.5464 13.7899C80.2234 13.7899 79.3414 13.0969 79.2874 11.9809H80.1874C80.2054 12.6019 80.7274 12.9979 81.5464 12.9979C82.4104 12.9979 82.9594 12.5929 82.9594 11.9449C82.9594 11.4319 82.6804 11.1079 82.0594 10.9639L80.9884 10.7209C79.9264 10.4779 79.3774 9.86588 79.3774 8.92988ZM89.4587 13.6819H85.4897V7.17488H89.4587V7.98488H86.3717V10.0189H89.1527V10.8019H86.3717V12.8629H89.4587V13.6819ZM92.1588 13.6819H91.2768V7.17488H93.7338C95.1288 7.17488 95.9568 7.91288 95.9568 9.12788C95.9568 10.0279 95.5068 10.6759 94.6968 10.9369L96.0198 13.6819H95.0388L93.8238 11.1079H92.1588V13.6819ZM92.1588 7.96688V10.3249H93.7428C94.5618 10.3249 95.0298 9.88388 95.0298 9.13688C95.0298 8.38088 94.5438 7.96688 93.7338 7.96688H92.1588Z" fill="#1E1515"></path></svg></p>
						</div>



						<div class="testimonial-block charitable-clear">
						<img src="<?php echo esc_url( charitable()->get_path( 'assets', false ) ); // phpcs:ignore ?>/images/onboarding/getting-started/paul-circle2x.jpg" />
							<div class="testimonials-stars">
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
								<div><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg></div>
							</div>
							<p><?php esc_html_e( '"I would like to thank you both for making such a great tool at such a great cost. It’s literally 24 times cheaper than the platform we were using before I came on board."', 'charitable' ); ?></p>
							<p class="charitable-cerified-user"><strong><?php esc_html_e( 'Paul Lawley-Jones', 'charitable' ); ?></strong> <svg xmlns="http://www.w3.org/2000/svg" width="105" height="22" viewBox="0 0 105 22" fill="none"><rect x="0.5" y="0.5" width="104" height="20.3636" rx="10.1818" stroke="#C4D6C6" stroke-linejoin="round"></rect><path d="M12.8364 17.3636L11.6273 15.3273L9.33636 14.8182L9.55909 12.4636L8 10.6818L9.55909 8.9L9.33636 6.54545L11.6273 6.03636L12.8364 4L15 4.92273L17.1636 4L18.3727 6.03636L20.6636 6.54545L20.4409 8.9L22 10.6818L20.4409 12.4636L20.6636 14.8182L18.3727 15.3273L17.1636 17.3636L15 16.4409L12.8364 17.3636ZM14.3318 12.9409L17.9273 9.34545L17.0364 8.42273L14.3318 11.1273L12.9636 9.79091L12.0727 10.6818L14.3318 12.9409Z" fill="#65AD27"></path><path d="M28.6155 13.6819L26.1405 7.17488H27.0855L28.6695 11.3329C28.8135 11.7019 28.9395 12.0709 29.0745 12.5569C29.2275 12.0439 29.3805 11.6119 29.4885 11.3239L31.0635 7.17488H31.9815L29.5335 13.6819H28.6155ZM37.3361 13.6819H33.3671V7.17488H37.3361V7.98488H34.2491V10.0189H37.0301V10.8019H34.2491V12.8629H37.3361V13.6819ZM40.0361 13.6819H39.1541V7.17488H41.6111C43.0061 7.17488 43.8341 7.91288 43.8341 9.12788C43.8341 10.0279 43.3841 10.6759 42.5741 10.9369L43.8971 13.6819H42.9161L41.7011 11.1079H40.0361V13.6819ZM40.0361 7.96688V10.3249H41.6201C42.4391 10.3249 42.9071 9.88388 42.9071 9.13688C42.9071 8.38088 42.4211 7.96688 41.6111 7.96688H40.0361ZM46.4648 7.17488V13.6819H45.5828V7.17488H46.4648ZM51.9602 10.9189H49.4042V13.6819H48.5222V7.17488H52.4462V7.98488H49.4042V10.1179H51.9602V10.9189ZM54.9364 7.17488V13.6819H54.0544V7.17488H54.9364ZM60.9628 13.6819H56.9938V7.17488H60.9628V7.98488H57.8758V10.0189H60.6568V10.8019H57.8758V12.8629H60.9628V13.6819ZM64.9319 13.6819H62.7809V7.17488H64.8959C66.8399 7.17488 68.1539 8.48888 68.1539 10.4329C68.1539 12.3679 66.8579 13.6819 64.9319 13.6819ZM64.8329 7.98488H63.6629V12.8629H64.8689C66.3179 12.8629 67.2359 11.9269 67.2359 10.4329C67.2359 8.92088 66.3179 7.98488 64.8329 7.98488ZM72.7719 11.3689V7.17488H73.6539V11.3149C73.6539 12.3589 74.2299 12.9349 75.2649 12.9349C76.2909 12.9349 76.8579 12.3499 76.8579 11.3149V7.17488H77.7489V11.3689C77.7489 12.8629 76.7949 13.7899 75.2649 13.7899C73.7259 13.7899 72.7719 12.8719 72.7719 11.3689ZM79.3774 8.92988C79.3774 7.81388 80.2774 7.05788 81.6184 7.05788C82.8604 7.05788 83.6614 7.75088 83.7334 8.87588H82.8424C82.7974 8.22788 82.3384 7.84988 81.6094 7.84988C80.7904 7.84988 80.2594 8.26388 80.2594 8.90288C80.2594 9.42488 80.5564 9.73988 81.1684 9.88388L82.2394 10.1359C83.3014 10.3789 83.8414 10.9549 83.8414 11.8729C83.8414 13.0429 82.9324 13.7899 81.5464 13.7899C80.2234 13.7899 79.3414 13.0969 79.2874 11.9809H80.1874C80.2054 12.6019 80.7274 12.9979 81.5464 12.9979C82.4104 12.9979 82.9594 12.5929 82.9594 11.9449C82.9594 11.4319 82.6804 11.1079 82.0594 10.9639L80.9884 10.7209C79.9264 10.4779 79.3774 9.86588 79.3774 8.92988ZM89.4587 13.6819H85.4897V7.17488H89.4587V7.98488H86.3717V10.0189H89.1527V10.8019H86.3717V12.8629H89.4587V13.6819ZM92.1588 13.6819H91.2768V7.17488H93.7338C95.1288 7.17488 95.9568 7.91288 95.9568 9.12788C95.9568 10.0279 95.5068 10.6759 94.6968 10.9369L96.0198 13.6819H95.0388L93.8238 11.1079H92.1588V13.6819ZM92.1588 7.96688V10.3249H93.7428C94.5618 10.3249 95.0298 9.88388 95.0298 9.13688C95.0298 8.38088 94.5438 7.96688 93.7338 7.96688H92.1588Z" fill="#1E1515"></path></svg></p>
						</div>

					</div>

				</div><!-- /.testimonials -->

				<div class="footer">

					<div class="block">

						<div class="button-wrap">
							<div class="">
								<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=charitable-campaign-builder' ) ); ?>"
									class="charitable-button-link">
									<?php esc_html_e( 'Create Your First Campaign', 'charitable' ); ?>
								</a>
							</div>
							<div class="">
								<a target="_blank" href="<?php echo esc_url( charitable_admin_upgrade_link( 'welcome', 'Upgrade to Charitable Pro' ) ); ?>" target="_blank" rel="noopener noreferrer"
									class="charitable-text-link charitable-upgrade-modal">
									<span class="underline">
										<?php esc_html_e( 'Upgrade to Pro', 'charitable' ); ?></span>
									</span>
								</a>
							</div>
						</div>

					</div>

				</div><!-- /.footer -->

			</div><!-- /.container -->

		</div><!-- /#charitable-welcome -->
		<?php
	}
}
