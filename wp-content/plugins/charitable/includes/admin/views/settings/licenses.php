<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the table of products requiring licenses.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_helper   = charitable_get_helper( 'licenses' );
$charitable_products = $charitable_helper->get_products();

if ( empty( $charitable_products ) ) :
	return;
endif;

$charitable_slug      = Charitable_Addons_Directory::get_current_plan_slug();
$charitable_is_legacy = Charitable_Addons_Directory::is_current_plan_legacy();

if ( false !== $charitable_slug && strtolower( $charitable_slug ) !== 'lite' && ! $charitable_is_legacy ) {

	// there is a valid legacy license present.
	$charitable_new_tab_notification =
	'<p>' .
	sprintf(
		wp_kses(
		/* translators: %s - charitable.com upgrade URL. */
			__( 'You already have a non-legacy license activated on this install, which you can deactivate <a href="%s">in the "General" tab</a>.', 'charitable' ),
			array(
				'a'      => array(
					'href'   => array(),
					'class'  => array(),
					'target' => array(),
					'rel'    => array(),
				),
				'br'     => array(),
				'strong' => array(),
			)
		),
		esc_url( admin_url( 'admin.php?page=charitable-settings&tab=general' ) )
	) .
	'</p>';

	?>

<div class="charitable-settings-notice license-notice" style="margin-bottom: 20px;">
<p><?php esc_html_e( 'This area is reserved for older (legacy) license keys.', 'charitable' ); ?></p>
<p><?php echo $charitable_new_tab_notification; // phpcs:ignore ?></p>
</div>

	<?php

} else {

	$charitable_new_tab_notification =
	'<p>' .
	sprintf(
		wp_kses(
		/* translators: %s - charitable.com upgrade URL. */
			__( 'If you have purchased your license key for <strong>Basic</strong>, <strong>Plus</strong>, <strong>Pro</strong>, or <strong>Agency / Elite</strong> recently, please enter your charitable license key <a href="%s">in the "General" tab</a>.', 'charitable' ),
			array(
				'a'      => array(
					'href'   => array(),
					'class'  => array(),
					'target' => array(),
					'rel'    => array(),
				),
				'br'     => array(),
				'strong' => array(),
			)
		),
		esc_url( admin_url( 'admin.php?page=charitable-settings&tab=general' ) )
	) .
	'</p>';

	?>
<div class="charitable-settings-notice license-notice" style="margin-bottom: 20px;">
	<p><?php esc_html_e( 'This area is reserved for older (legacy) license keys.', 'charitable' ); ?></p>
	<p><?php echo $charitable_new_tab_notification; // phpcs:ignore ?></p>
	<p><?php esc_html_e( 'By adding your license keys, you agree for your website to send requests to wpcharitable.com to check license details and provide automatic plugin updates. Your license(s) can be disconnected at any time.', 'charitable' ); ?></p>
</div>
	<?php

	$_charitable_legacy_license_info = get_transient( '_charitable_legacy_license_info' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $_charitable_legacy_license_info is a transient key used by WordPress transients API.

	foreach ( $charitable_products as $charitable_key => $charitable_product ) :

		$charitable_license = $charitable_helper->get_license_details( $charitable_key );

		// set a default invalid message.
		$charitable_invalid_message = __( 'This is an invalid license.', 'charitable' );

		if ( is_array( $charitable_license ) ) {
			if ( isset( $charitable_license['expiration_date'] ) && false !== $charitable_license['expiration_date'] && isset( $charitable_license['valid'] ) && false !== $charitable_license['valid'] ) {
				$charitable_is_active   = $charitable_license['valid'];
				$charitable_license_key = $charitable_license['license'];
			} else {
				$charitable_is_active   = false;
				$charitable_license_key = false;
				// this is different because we try to avoid just a BAD license vs. an invalid-but-could-be-expired license.
				$charitable_referer = wp_get_referer();
				if ( admin_url( 'admin.php?page=charitable-settings&tab=advanced' ) === $charitable_referer ) {
					if ( false !== $_charitable_legacy_license_info && is_array( $_charitable_legacy_license_info ) && array_key_exists( $charitable_key, $_charitable_legacy_license_info ) && '' !== trim( $_charitable_legacy_license_info[ $charitable_key ] ) ) {
						$charitable_invalid_message = __( 'The license was not valid.', 'charitable' );
					} else {
						$charitable_invalid_message = false;
					}
				} else {
					$charitable_invalid_message = false;
				}
			}
		} else {
			$charitable_is_active   = false;
			$charitable_license_key = $charitable_license;
		}

		?>
	<div class="charitable-settings-object charitable-licensed-product">
		<h4><?php echo esc_html( $charitable_product['name'] ); ?></h4>
		<input type="text" name="charitable_settings[legacy_licenses][<?php echo esc_attr( $charitable_key ); ?>]" id="charitable_settings_licenses_<?php echo esc_attr( $charitable_key ); ?>" class="charitable-settings-field" placeholder="<?php esc_attr_e( 'Add your license key', 'charitable' ); ?>" value="<?php echo esc_attr( $charitable_license_key ); ?>" />
		<?php if ( $charitable_license ) : ?>
			<div class="license-meta">
				<?php if ( $charitable_is_active ) : ?>
					<a href="<?php echo esc_url( $charitable_helper->get_license_deactivation_url( $charitable_key ) ); ?>" class="button-secondary license-deactivation"><?php esc_html_e( 'Deactivate License', 'charitable' ); ?></a>
					<?php if ( 'lifetime' === $charitable_license['expiration_date'] ) : ?>
						<span class="license-expiration-date"><?php esc_html_e( 'Lifetime license', 'charitable' ); ?></span>
					<?php else : ?>
						<span class="license-expiration-date"><?php printf( '%s %s.', esc_html__( 'Expiring in', 'charitable' ), human_time_diff( strtotime( $charitable_license['expiration_date'] ), time() ) ); // phpcs:ignore ?></span>
					<?php endif ?>
				<?php elseif ( is_array( $charitable_license ) ) : ?>
					<span class="license-invalid"><?php echo $charitable_invalid_message; // phpcs:ignore ?></span>
				<?php else : ?>
					<span class="license-invalid"><?php esc_html_e( 'We could not validate this license.', 'charitable' ); ?></span>
				<?php endif ?>
			</div>
		<?php endif ?>
	</div>

		<?php
endforeach;



	delete_transient( '_charitable_legacy_license_info' );

}
