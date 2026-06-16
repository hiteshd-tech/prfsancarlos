<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the campaign donations table for adding/editing donations to campaigns.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.5.0
 * @version   1.6.54
 * @version   1.8.8.6
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$charitable_campaigns = get_posts(
	array(
		'post_type'      => 'campaign',
		'posts_per_page' => -1,
		'post_status'    => array( 'draft', 'pending', 'private', 'publish' ),
		'fields'         => 'ids',
		'perm'           => 'readable',
		'orderby'        => 'title',
		'order'          => 'ASC',
	)
);

$charitable_campaign_donations = $view_args['value'];

if ( empty( $charitable_campaign_donations ) ) {
	$charitable_campaign_donations = array(
		(object) array(
			'campaign_id' => '',
			'amount'      => '',
		),
	);
}

$charitable_currency_helper = Charitable_Currency::get_instance();

?>
<div id="charitable-campaign-donations-metabox-wrap" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>">
	<table id="charitable-campaign-donations" class="widefat">
		<thead>
			<tr class="table-header">
				<th><label id="<?php echo esc_attr( $view_args['id'] ); ?>-campaign-label"><?php esc_html_e( 'Campaign', 'charitable' ); ?></label></th>
				<th><label id="<?php echo esc_attr( $view_args['id'] ); ?>-amount-label"><?php esc_html_e( 'Amount', 'charitable' ); ?></label></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $charitable_campaign_donations as $charitable_i => $charitable_campaign_donation ) : ?>
			<tr>
				<td>
					<select name="<?php echo esc_attr( sprintf( '%s[%d][campaign_id]', $view_args['key'], $charitable_i ) ); ?>" labelledby="<?php echo esc_attr( $view_args['id'] ); ?>-campaign-label" tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>">
						<option value=""><?php esc_html_e( 'Select a campaign', 'charitable' ); ?></option>
						<?php foreach ( $charitable_campaigns as $charitable_campaign_id ) : ?>
							<option value="<?php echo esc_attr( $charitable_campaign_id ); ?>" <?php selected( $charitable_campaign_id, $charitable_campaign_donation->campaign_id ); ?>><?php echo esc_html( get_the_title( $charitable_campaign_id ) ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
				<td>
					<input type="text"
						class="currency-input"
						name="<?php echo esc_attr( sprintf( '%s[%d][amount]', $view_args['key'], $charitable_i ) ); ?>"
						labelledby="<?php echo esc_attr( $view_args['id'] ); ?>-amount-label"
						tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
						value="<?php echo empty( $charitable_campaign_donation->amount ) ? '' : esc_attr( $charitable_currency_helper->get_sanitized_and_localized_amount( $charitable_campaign_donation->amount ) ); ?>"
					/>
					<?php if ( isset( $charitable_campaign_donation->campaign_donation_id ) ) : ?>
						<input type="hidden"
							name="<?php echo esc_attr( sprintf( '%s[%d][campaign_donation_id]', $view_args['key'], $charitable_i ) ); ?>"
							value="<?php echo esc_attr( $charitable_campaign_donation->campaign_donation_id ); ?>"
						/>
					<?php endif ?>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
