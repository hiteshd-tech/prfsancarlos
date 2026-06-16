<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the table of payment gateways.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.38 - Add Stripe Warning
 * @version   1.8.2 - Add Stripe Warning
 * @version   1.8.7 - Add Square Legacy gateway and sanitize gateway logos.
 * @version   1.8.8.6
 */

$charitable_helper   = charitable_get_helper( 'gateways' );
$charitable_gateways = $charitable_helper->get_available_gateways();
$charitable_default  = $charitable_helper->get_default_gateway();
$charitable_upgrades = $charitable_helper->get_recommended_gateways();

// Add a warning message here if Stripe is an enabled gateway, but no keys are found.

if ( class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {

	if ( ( ! defined( 'CHARITABLE_DISABLE_STRIPE_KEY_CHECK' ) || ! CHARITABLE_DISABLE_STRIPE_KEY_CHECK ) && is_array( $charitable_gateways ) && ! empty( $charitable_gateways ) && in_array( 'Charitable_Gateway_Stripe_AM', $charitable_gateways, true ) && $charitable_helper->is_active_gateway( 'Charitable_Gateway_Stripe_AM' ) ) { // phpcs:ignore
		$charitable_stripe_gateway = new Charitable_Gateway_Stripe_AM();
		$charitable_stripe_keys    = $charitable_stripe_gateway->get_keys();

		if ( ! isset( $charitable_stripe_keys['public_key'] ) || empty( $charitable_stripe_keys['public_key'] ) ) {
			// Display the warning message HTML.
			/* translators: %s: URL to the Stripe settings page */
			echo wp_kses(
				sprintf(
					'<div class="charitable-settings-notice charitable-stripe-key-notice"> %s</div>',
					sprintf(
						/* translators: %s: URL to the Stripe settings page */
						esc_html__( '<strong>Note:</strong> Stripe is enabled but it does not appear to be connected or API keys are missing. <a href="%s">Confirm Stripe settings to keep using this gateway</a>.', 'charitable' ),
						esc_url( admin_url( 'admin.php?page=charitable-settings&tab=gateways&group=gateways_stripe' ) )
					)
				),
				array(
					'div'    => array( 'class' => array() ),
					'strong' => array(),
					'a'      => array( 'href' => array() ),
				)
			);
		}
	}
}

// We need to make sure the 'square_core' gateway always follows the 'stripe' gateway.
$charitable_gateway_keys = array_keys( $charitable_gateways );
$charitable_stripe_index = array_search( 'stripe', $charitable_gateway_keys, true );
$charitable_square_index = array_search( 'square_core', $charitable_gateway_keys, true );

if ( false !== $charitable_stripe_index && false !== $charitable_square_index && $charitable_square_index !== $charitable_stripe_index + 1 ) {
	// Remove square_core from its current position.
	$charitable_square_gateway = $charitable_gateways['square_core'];
	unset( $charitable_gateways['square_core'] );

	// Rebuild the array with square_core after stripe.
	$charitable_reordered_gateways = array();
	$charitable_gateway_keys       = array_keys( $charitable_gateways );

	foreach ( $charitable_gateway_keys as $charitable_key ) {
		$charitable_reordered_gateways[ $charitable_key ] = $charitable_gateways[ $charitable_key ];

		// Insert square_core after stripe.
		if ( 'stripe' === $charitable_key ) {
			$charitable_reordered_gateways['square_core'] = $charitable_square_gateway;
		}
	}

	$charitable_gateways = $charitable_reordered_gateways;
}

foreach ( $charitable_gateways as $charitable_gateway ) :

	$charitable_gateway = class_exists( $charitable_gateway ) ? new $charitable_gateway() : null;
	if ( ! $charitable_gateway ) {
		continue;
	}

	$charitable_is_active = $charitable_helper->is_active_gateway( $charitable_gateway->get_gateway_id() );

	if ( $charitable_is_active ) {
		$charitable_action_url  = esc_url(
			add_query_arg(
				array(
					'charitable_action' => 'disable_gateway',
					'gateway_id'        => $charitable_gateway->get_gateway_id(),
					'_nonce'            => wp_create_nonce( 'gateway' ),
				),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
			)
		);
		$charitable_action_text = __( 'Disable Gateway', 'charitable' );
	} else {
		$charitable_action_url  = esc_url(
			add_query_arg(
				array(
					'charitable_action' => 'enable_gateway',
					'gateway_id'        => $charitable_gateway->get_gateway_id(),
					'_nonce'            => wp_create_nonce( 'gateway' ),
				),
				admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
			)
		);
		$charitable_action_text = __( 'Enable Gateway', 'charitable' );
	}

	$charitable_action_url = esc_url(
		add_query_arg(
			array(
				'charitable_action' => $charitable_is_active ? 'disable_gateway' : 'enable_gateway',
				'gateway_id'        => $charitable_gateway->get_gateway_id(),
				'_nonce'            => wp_create_nonce( 'gateway' ),
			),
			admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
		)
	);

	$charitable_make_default_url = esc_url(
		add_query_arg(
			array(
				'charitable_action' => 'make_default_gateway',
				'gateway_id'        => $charitable_gateway->get_gateway_id(),
				'_nonce'            => wp_create_nonce( 'gateway' ),
			),
			admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
		)
	);

	$charitable_gateway_name = null !== $charitable_gateway->get_name() ? $charitable_gateway->get_name() : '';

	?>
	<div class="charitable-settings-object charitable-gateway <?php echo esc_attr( strtolower( $charitable_gateway_name ) ); ?>">
		<span class="gateway-logo-name">
			<?php
				// if method get_logo() exists, use it.
			if ( method_exists( $charitable_gateway, 'get_logo' ) ) {
				$charitable_logo = $charitable_gateway->get_logo();
				if ( is_string( $charitable_logo ) && ( strpos( $charitable_logo, '<img' ) !== false || strpos( $charitable_logo, '<svg' ) !== false ) ) {
					echo '<span class="gateway-logo">' . wp_kses(
						$charitable_logo,
						array(
							'img'  => array(
								'src'    => array(),
								'alt'    => array(),
								'class'  => array(),
								'width'  => array(),
								'height' => array(),
							),
							'svg'  => array(
								'xmlns'   => array(),
								'viewBox' => array(),
								'width'   => array(),
								'height'  => array(),
								'class'   => array(),
							),
							'path' => array(
								'd'    => array(),
								'fill' => array(),
							),
						)
					) . '</span>';
				} else {
					echo '<span class="gateway-logo">' . esc_html( $charitable_logo ) . '</span>';
				}
			} else {
				$charitable_name = esc_html( $charitable_gateway->get_name() );
				if ( 'square' === strtolower( $charitable_name ) ) {
					$charitable_name .= ' ' . __( '(Legacy)', 'charitable' );
				}
				echo '<h4>' . esc_html( $charitable_name ) . '</h4>';
			}
			?>

			<?php if ( $charitable_gateway->get_recommended() ) : ?>
				<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i><?php esc_html_e( 'Recommended', 'charitable' ); ?></span>
			<?php endif ?>

			<?php if ( (string) $charitable_gateway->get_gateway_id() === (string) $charitable_default ) : ?>

				<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-orange charitable-badge-rounded"><i class="fa fa-check" aria-hidden="true"></i><?php esc_html_e( 'Default Gateway', 'charitable' ); ?></span>

			<?php elseif ( $charitable_is_active ) : ?>

				<a href="<?php echo esc_url( $charitable_make_default_url ); ?>" class="make-default-gateway"><i class="fa fa-check" aria-hidden="true"></i><?php esc_html_e( 'Make default', 'charitable' ); ?></a>

			<?php endif ?>
		</span>
		<span class="actions">
			<?php
			if ( $charitable_is_active ) :
				$charitable_settings_url = esc_url(
					add_query_arg(
						array(
							'group' => 'gateways_' . $charitable_gateway->get_gateway_id(),
						),
						admin_url( 'admin.php?page=charitable-settings&tab=gateways' )
					)
				);
				?>

				<a href="<?php echo esc_url( $charitable_settings_url ); ?>" class="button button-primary"><?php esc_html_e( 'Gateway Settings', 'charitable' ); ?></a>
			<?php endif ?>
			<a href="<?php echo esc_url( $charitable_action_url ); ?>" class="button"><?php echo esc_html( $charitable_action_text ); ?></a>
		</span>
	</div>

	<?php
	// Display Square gateway row after Stripe if the class isn't loaded.
	if ( 'stripe' === $charitable_gateway->get_gateway_id() && ! class_exists( 'Charitable_Gateway_Square' ) ) :
		$charitable_php_version_check = version_compare( PHP_VERSION, '8.1.0', '>=' );

		if ( ! $charitable_php_version_check ) {
			$charitable_error_message = sprintf(
				/* translators: %s: URL to documentation */
				__( 'Requires PHP 8.1.0 or higher. <a href="%s" target="_blank">See our documentation</a>.', 'charitable' ),
				'https://www.wpcharitable.com/documentation/php-version-compatibility-square/'
			);
		} else {
			$charitable_error_message = __( 'Square gateway could not be loaded.', 'charitable' );
		}
		?>
		<div class="charitable-settings-object charitable-gateway square">
			<span class="gateway-logo-name">
				<span class="gateway-logo"><?php echo '<svg width="88" height="22" viewBox="0 0 88 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.6761 1.54849e-07H18.3239C19.299 -0.000282679 20.2344 0.386894 20.924 1.07633C21.6136 1.76576 22.0011 2.70095 22.0011 3.6761V18.3228C22.0011 20.3537 20.3547 22 18.3239 22H3.6761C1.64567 21.9994 0 20.3533 0 18.3228V3.6761C0 1.64584 1.64584 1.54849e-07 3.6761 1.54849e-07ZM16.8434 18.0005C17.4842 18.0005 18.0037 17.481 18.0037 16.8402L18.0005 5.16083C18.0005 4.52004 17.481 4.00058 16.8402 4.00058H5.1619C4.854 4.00058 4.55872 4.12297 4.3411 4.34079C4.12348 4.55861 4.00137 4.854 4.00165 5.1619V16.8402C4.00165 17.481 4.52111 18.0005 5.1619 18.0005H16.8434Z" fill="#3E4348"/><path d="M8.66615 13.9828C8.30039 13.9799 8.00543 13.6826 8.00544 13.3168V8.65442C8.00459 8.47722 8.07438 8.30698 8.19939 8.18138C8.32439 8.05578 8.49429 7.98517 8.67149 7.98517H13.3403C13.5174 7.98545 13.6871 8.05615 13.8121 8.18169C13.937 8.30724 14.0069 8.47731 14.0063 8.65442V13.3157C14.0069 13.4928 13.937 13.6629 13.8121 13.7884C13.6871 13.914 13.5174 13.9847 13.3403 13.985L8.66615 13.9828Z" fill="#3E4348"/><path d="M33.1959 10.0196C32.5149 9.83388 31.8702 9.65883 31.3696 9.43575C30.4431 9.0216 30.0119 8.44734 30.0119 7.62972C30.0119 6.08414 31.5062 5.3882 32.9942 5.3882C34.4084 5.3882 35.6434 5.97313 36.4728 7.03412L36.5293 7.1067L37.7248 6.17167L37.6672 6.09908C36.5646 4.69653 34.9315 3.92801 33.0667 3.92801C31.8254 3.92801 30.6875 4.26317 29.8646 4.87265C28.9381 5.55044 28.4492 6.53351 28.4492 7.70657C28.4492 10.4338 31.0173 11.0987 33.0817 11.6335C35.1706 12.1843 36.4504 12.6027 36.4504 14.1952C36.4504 15.7632 35.1823 16.7762 33.2204 16.7762C32.2502 16.7762 30.4538 16.519 29.3245 14.7941L29.2722 14.7129L28.0148 15.6234L28.0639 15.6971C29.1313 17.3131 30.9736 18.2407 33.1244 18.2407C36.0458 18.2407 38.0098 16.5915 38.0098 14.1387C38.0098 11.3314 35.3392 10.6045 33.1959 10.0196Z" fill="#3E4348"/><path fill-rule="evenodd" clip-rule="evenodd" d="M47.5395 9.45282V7.97662H48.9452V21.9979H47.5395V16.52C46.7368 17.6205 45.5328 18.2225 44.1174 18.2225C41.4447 18.2225 39.5767 16.0824 39.5767 12.9923C39.5767 9.90219 41.4489 7.745 44.1174 7.745C45.5232 7.745 46.7272 8.35021 47.5395 9.45282ZM41.0583 12.9752C41.0583 15.8358 42.6967 16.8552 44.2305 16.8552L44.2337 16.8562C46.2415 16.8562 47.5395 15.3192 47.5395 12.9752C47.5395 10.6312 46.2394 9.11552 44.2305 9.11552C41.8919 9.11552 41.0583 11.1094 41.0583 12.9752Z" fill="#3E4348"/><path d="M58.239 7.97662V13.511C58.239 15.4484 56.9122 16.8552 55.0848 16.8552C53.567 16.8552 52.8284 15.9543 52.8284 14.1024V7.97662H51.4226V14.3895C51.4226 16.7911 52.728 18.2246 54.914 18.2246C56.276 18.2246 57.4459 17.6472 58.24 16.5915V17.9962H59.6458V7.97662H58.239Z" fill="#3E4348"/><path fill-rule="evenodd" clip-rule="evenodd" d="M62.293 9.02907C63.3294 8.21465 64.7362 7.7482 66.1505 7.7482C68.3846 7.7482 69.7177 8.85935 69.7135 10.723V17.9984H68.3067V16.8872C67.5968 17.7763 66.57 18.2268 65.2486 18.2268C63.0956 18.2268 61.7571 17.0494 61.7571 15.1559C61.7571 12.6934 64.0776 12.307 65.0661 12.1426C65.2272 12.116 65.3937 12.0904 65.5601 12.0647L65.5603 12.0647L65.5663 12.0638C66.9168 11.8559 68.3109 11.6414 68.3109 10.4957C68.3109 9.19878 66.6276 9.09845 66.1121 9.09845C65.2016 9.09845 63.9154 9.3685 63.0412 10.1263L62.9612 10.1957L62.2268 9.08137L62.293 9.02907ZM63.2248 15.0769C63.2248 16.6823 64.7362 16.8562 65.3863 16.8562H65.3873C66.8006 16.8562 68.3131 16.1027 68.3099 13.985V12.5354C67.6242 12.9685 66.6483 13.1377 65.7778 13.2886L65.7631 13.2912L65.3265 13.3691C63.9325 13.6274 63.2248 13.9604 63.2248 15.0769Z" fill="#3E4348"/><path d="M77.8577 8.16554C77.5236 7.92752 76.9974 7.78555 76.4487 7.78555C75.3213 7.80037 74.2664 8.34386 73.5998 9.25322V7.97235H72.1941V17.9909H73.5998V12.6326C73.5998 10.2566 74.9352 9.19237 76.2576 9.19237C76.6447 9.1872 77.0279 9.26852 77.3795 9.4304L77.4745 9.48057L77.9196 8.20611L77.8577 8.16554Z" fill="#3E4348"/><path fill-rule="evenodd" clip-rule="evenodd" d="M78.2697 13.0136C78.2697 9.91394 80.2027 7.7482 82.9662 7.7482C85.6282 7.7482 87.4887 9.67057 87.4834 12.4276C87.4826 12.6673 87.4694 12.9067 87.4439 13.1449L87.4353 13.2271H79.7501C79.7853 15.4334 81.1132 16.8562 83.1508 16.8562C84.3186 16.8562 85.3304 16.3813 85.9997 15.5177L86.0605 15.4388L87.0788 16.346L87.0223 16.4143C86.3455 17.2394 85.1116 18.2236 83.0729 18.2236C80.2016 18.2236 78.2697 16.1304 78.2697 13.0136ZM82.9277 9.09738C81.2103 9.09738 79.9924 10.2277 79.8024 11.9879H85.9976C85.8759 10.5725 85.0113 9.09738 82.9277 9.09738Z" fill="#3E4348"/></svg>'; ?></span>

				<span class="charitable-badge charitable-badge-sm charitable-badge-inline charitable-badge-green charitable-badge-rounded"><i class="fa fa-star" aria-hidden="true"></i><?php esc_html_e( 'Recommended', 'charitable' ); ?></span>
			</span>
			<span class="actions">
				<span class="gateway-error">
					<?php
					echo wp_kses(
						$charitable_error_message,
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
					?>
				</span>
			</span>
		</div>
		<?php
	endif;
endforeach
?>

<?php
if ( ! empty( $charitable_upgrades ) ) :
	if ( 1 === count( $charitable_upgrades ) ) {
		$charitable_currencies = charitable_get_currency_helper()->get_all_currencies();
		$charitable_gateway    = key( $charitable_upgrades );
		$charitable_message    = sprintf(
			/* translators: %1$s: currency; %2$s: hyperlink %3$s: payment gateway name */
			__( '<strong>Tip</strong>: Accept donations in %1$s with <a href="%2$s" target="_blank">%3$s</a>.', 'charitable' ),
			$charitable_currencies[ charitable_get_currency() ],
			'https://www.wpcharitable.com/extensions/charitable-' . $charitable_gateway . '/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Upgrade+Notice&utm_content=Accept+Donations',
			current( $charitable_upgrades )
		);
	} else {
		$charitable_message = sprintf(
			/* translators: %1$s: hyperlink; %2$s: single extension name; %3$s: comma-separated list of extension names */
			__( '<strong>Need more options?</strong> <a href="%1$s" target="_blank">Click here to browse our payment gateway extensions</a>, including %3$s and %2$s.', 'charitable' ),
			'https://www.wpcharitable.com/extensions/category/payment-gateways/?utm_source=WordPress&utm_campaign=WP+Charitable&utm_medium=Admin+Notice&utm_content=Need+More+Options+Browse+Gateway+Extensions',
			array_pop( $charitable_upgrades ),
			implode( ', ', $charitable_upgrades )
		);
	}
	?>
	<p class="charitable-gateway-prompt charitable-settings-notice"><?php echo esc_html( $charitable_message ); ?></p>
<?php endif ?>
