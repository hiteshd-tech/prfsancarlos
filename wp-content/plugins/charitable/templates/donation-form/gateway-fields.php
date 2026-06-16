<?php
/**
 * The template used to display the gateway fields.
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.8.3.1 Cleanup.
 * @version 1.8.3.7 Added support for cc_fields_format.
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$charitable_form             = $view_args['form'];
$charitable_field            = $view_args['field'];
$charitable_classes          = $view_args['classes'];
$charitable_gateways         = $charitable_field['gateways'];
$charitable_default          = isset( $charitable_field['default'] ) && isset( $charitable_gateways[ $charitable_field['default'] ] ) ? $charitable_field['default'] : key( $charitable_gateways );
$charitable_cc_fields_format = '';

if ( charitable()->load_core_stripe() && class_exists( 'Charitable_Gateway_Stripe_AM' ) ) {
	$charitable_settings         = get_option( 'charitable_settings' );
	$charitable_cc_fields_format = ( ! empty( $charitable_settings['gateways_stripe']['cc_fields_format'] ) && '' !== $charitable_settings['gateways_stripe']['cc_fields_format'] ) ? $charitable_settings['gateways_stripe']['cc_fields_format'] : '';
}

?>
<fieldset id="charitable-gateway-fields" class="charitable-fieldset">
<?php do_action( 'charitable_gateway_fields_front', $charitable_form, $charitable_field, $charitable_gateways ); ?>
	<?php
	if ( isset( $charitable_field['legend'] ) ) :
		?>

		<div class="charitable-form-header"><?php echo esc_html( $charitable_field['legend'] ); ?></div>

		<?php
	endif;

	do_action( 'charitable_gateway_fields_after_legend', $charitable_form, $charitable_field, $charitable_gateways );

	if ( count( $charitable_gateways ) > 1 ) :
		?>
		<fieldset class="charitable-fieldset-field-wrapper">
			<div class="charitable-fieldset-field-header" id="charitable-gateway-selector-header"><?php esc_html_e( 'Choose Your Payment Method', 'charitable' ); ?></div>
			<ul id="charitable-gateway-selector" class="charitable-radio-list charitable-form-field">
				<?php foreach ( $charitable_gateways as $charitable_gateway_id => $charitable_details ) : ?>
					<li><input type="radio"
							id="gateway-<?php echo esc_attr( $charitable_gateway_id ); ?>"
							name="gateway"
							value="<?php echo esc_attr( $charitable_gateway_id ); ?>"
							aria-describedby="charitable-gateway-selector-header"
							<?php checked( $charitable_default, $charitable_gateway_id ); ?> />
						<label for="gateway-<?php echo esc_attr( $charitable_gateway_id ); ?>"><?php echo esc_html( $charitable_details['label'] ); ?></label>
					</li>
				<?php endforeach ?>
			</ul>
		</fieldset>
		<?php
	endif;

	foreach ( $charitable_gateways as $charitable_gateway_id => $charitable_details ) :

		if ( ! isset( $charitable_details['fields'] ) || empty( $charitable_details['fields'] ) ) :
			continue;
		endif;

		$charitable_gateway_fields_classes  = 'charitable-gateway-fields charitable-form-fields cf';
		$charitable_gateway_fields_classes .= ( 'stripe' === $charitable_gateway_id && ! empty( $charitable_cc_fields_format ) ) ? ' charitable-cc-fields-' . esc_attr( $charitable_cc_fields_format ) : 'standard';

		?>
		<div id="charitable-gateway-fields-<?php echo esc_html( $charitable_gateway_id ); ?>" class="<?php echo esc_attr( $charitable_gateway_fields_classes ); ?>" data-gateway="<?php echo esc_html( $charitable_gateway_id ); ?>">
			<?php $charitable_form->view()->render_fields( $charitable_details['fields'] ); ?>
		</div><!-- #charitable-gateway-fields-<?php echo esc_html( $charitable_gateway_id ); ?> -->
	<?php endforeach ?>

	<?php do_action( 'charitable_gateway_fields_end', $charitable_form, $charitable_field, $charitable_gateways ); ?>

</fieldset><!-- .charitable-fieldset -->
