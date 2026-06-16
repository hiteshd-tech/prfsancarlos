<?php
/**
 * The template used to display the donation amount inputs.
 *
 * Override this template by copying it to yourtheme/charitable/donation-form/donation-amount-list.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Donation Form
 * @since   1.5.0
 * @version 1.6.49
 * @version 1.8.6 - Added filter to allow customizing the suggested donation amount text.
 * @version 1.8.8.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! array_key_exists( 'form_id', $view_args ) || ! array_key_exists( 'campaign', $view_args ) ) {
	return;
}

/* @var Charitable_Campaign */
$charitable_campaign      = $view_args['campaign'];
$charitable_form_id       = $view_args['form_id'];
$charitable_suggested     = $charitable_campaign->get_suggested_donations();
$charitable_has_suggested = count( $charitable_suggested ) > 0 ? 'has-suggested-amounts' : '';
$charitable_custom        = $charitable_campaign->get( 'allow_custom_donations' );
$charitable_default       = ( $charitable_campaign->get_suggested_donations_default() ) ? $charitable_campaign->get_suggested_donations_default() : false;
$charitable_amount        = $charitable_campaign->get_donation_amount_in_session();
$charitable_active_period = 'once' == $charitable_campaign->get_initial_donation_period() || in_array( $charitable_campaign->get( 'recurring_donation_mode' ), array( 'variable', 'simple' ) );
$charitable_checked_once  = false;

if ( 0 === $charitable_amount ) {
	$charitable_amount = $charitable_campaign->get_default_donation_amount();
}

if ( empty( $charitable_suggested ) && ! $charitable_custom ) {
	return;
}

if ( count( $charitable_suggested ) ) :

	$charitable_amount_is_suggestion = false;
	?>
	<ul class="donation-amounts <?php echo esc_attr( $charitable_has_suggested ); ?> donation-amounts-count-<?php echo count( $charitable_suggested ); ?>">
		<?php
		foreach ( $charitable_suggested as $charitable_suggestion ) :
			$charitable_checked  = $charitable_active_period ? checked( $charitable_suggestion['amount'], $charitable_amount, false ) : '';
			$charitable_field_id = esc_attr(
				sprintf(
					'form-%s-field-%s',
					$charitable_form_id,
					$charitable_suggestion['amount']
				)
			);

			if ( strlen( $charitable_checked ) ) :
				$charitable_amount_is_suggestion = true;
			endif;

			if ( $charitable_amount === 0 && ( false === $charitable_checked_once && trim( htmlentities( $charitable_default ) ) === trim( html_entity_decode( charitable_format_money( $charitable_suggestion['amount'], false, true ) ) ) || trim( htmlentities( $charitable_default ) ) === trim( html_entity_decode( $charitable_suggestion['amount'] ) ) ) ) :
				$charitable_checked      = 'checked = "true"';
				$charitable_checked_once = true;
			endif;

			?>
			<li class="donation-amount suggested-donation-amount <?php echo strlen( $charitable_checked ) ? 'selected' : ''; ?>">
				<label for="<?php echo esc_attr( $charitable_field_id ); ?>">
					<input
						id="<?php echo esc_attr( $charitable_field_id ); ?>"
						type="radio"
						name="donation_amount"
						value="<?php echo esc_attr( charitable_get_currency_helper()->sanitize_database_amount( $charitable_suggestion['amount'] ) ); ?>" <?php echo $charitable_checked; // phpcs:ignore ?>
					/>
					<?php
						printf(
							'<span class="amount">%s</span> <span class="description">%s</span>',
							apply_filters( 'charitable_donation_amount_form_suggested_amount_text', charitable_format_money( $charitable_suggestion['amount'], false, true ), $charitable_suggestion ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							isset( $charitable_suggestion['description'] ) ? wp_kses_post( $charitable_suggestion['description'] ) : ''
						);
					?>
				</label>
			</li>
			<?php
		endforeach;
		?>
	</ul>
	<ul class="donation-amounts donation-suggested-amount">
		<?php
		if ( $charitable_custom ) :
			$charitable_amount = apply_filters( 'charitable_donation_amount_form_custom_amount', $charitable_amount );
			$charitable_has_custom_donation_amount = $charitable_active_period && ( ! $charitable_amount_is_suggestion && $charitable_amount );
			?>
			<li class="donation-amount custom-donation-amount">
				<span class="custom-donation-amount-wrapper">
					<label for="form-<?php echo esc_attr( $charitable_form_id ); ?>-field-custom-amount">
						<input
							id="form-<?php echo esc_attr( $charitable_form_id ); ?>-field-custom-amount"
							type="radio"
							name="donation_amount"
							value="custom" <?php checked( $charitable_has_custom_donation_amount ); ?>
						/><span class="description"><?php echo apply_filters( 'charitable_donation_amount_form_custom_amount_text', __( 'Custom amount', 'charitable' ) ); // phpcs:ignore ?></span>
					</label>
					<input
						type="text"
						class="custom-donation-input"
						name="custom_donation_amount"
						placeholder="<?php esc_attr_e( 'Enter donation amount', 'charitable' ); ?>"
						value="<?php echo $charitable_has_custom_donation_amount ? charitable_get_currency_helper()->get_sanitized_and_localized_amount( $charitable_amount, false, true ) : ''; // phpcs:ignore ?>"
					/>
				</span>
			</li>
		<?php endif ?>
	</ul><!-- .donation-amounts -->
<?php elseif ( $charitable_custom ) :
	$charitable_amount = apply_filters( 'charitable_donation_amount_form_custom_amount', $charitable_amount ); ?>
	<div id="custom-donation-amount-field" class="charitable-form-field charitable-custom-donation-field-alone">
		<input
			type="text"
			class="custom-donation-input"
			name="custom_donation_amount"
			placeholder="<?php esc_attr_e( 'Enter donation amount', 'charitable' ); ?>"
			value="<?php echo $charitable_amount ? esc_attr( charitable_get_currency_helper()->get_sanitized_and_localized_amount( $charitable_amount, false, true ) ) : ''; ?>"
		/>
	</div><!-- #custom-donation-amount-field -->
<?php endif ?>
