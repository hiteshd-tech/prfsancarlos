<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the suggested donation amounts field inside the donation options metabox for the Campaign post type.
 *
 * @author    WP Charitable LLC
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @since     1.0.0
 * @version   1.6.0
 * @version   1.8.8.6
 */

global $post;

/**
 * Filter the fields to be included in the suggested amounts array.
 *
 * @since 1.0.0
 *
 * @param array $fields A set of fields including a column header and placeholder value.
 */
$charitable_fields = apply_filters(
	'charitable_campaign_donation_suggested_amounts_fields',
	array(
		'default_amount' => array(
			'column_header' => __( 'Default<br/>(<a href="javascript:void(0);">Clear</a>)', 'charitable' ),
			'type'          => 'radio',
		),
		'amount'         => array(
			'column_header' => __( 'Amount', 'charitable' ),
			'placeholder'   => __( 'Amount', 'charitable' ),
		),
		'description'    => array(
			'column_header' => __( 'Description (optional)', 'charitable' ),
			'placeholder'   => __( 'Optional Description', 'charitable' ),
		),
	)
);

$title               = isset( $view_args['label'] ) ? $view_args['label'] : ''; // phpcs:ignore
$charitable_tooltip             = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> ' . $view_args['tooltip'] . '</span>' : '';
$charitable_description         = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . $view_args['description'] . '</span>' : '';
$charitable_suggested_donations = charitable_get_campaign( $post->ID )->get_suggested_donations();
$charitable_suggested_default   = charitable_get_campaign( $post->ID )->get_suggested_donations_default();

$charitable_counter                   = 1;
$charitable_suggested_default_counter = 0;
foreach ( $charitable_suggested_donations as $charitable_suggested_donation ) {
	if ( isset( $charitable_suggested_donation['amount'] ) ) {
		if ( $charitable_suggested_default === ( trim( html_entity_decode( charitable_format_money( $charitable_suggested_donation['amount'], false, true ) ) ) ) || $charitable_suggested_default === ( trim( html_entity_decode( $charitable_suggested_donation['amount'], false, true ) ) ) ) {
			$charitable_suggested_default_counter = $charitable_counter;
		} else {
			++$charitable_counter;
		}
	}
}

/* Add a default empty row to the end. We will use this as our clone model. */
$charitable_default = array_fill_keys( array_keys( $charitable_fields ), '' );

array_push( $charitable_suggested_donations, $charitable_default );

?>
<div id="charitable-campaign-suggested-donations-metabox-wrap" class="charitable-metabox-wrap">
	<table id="charitable-campaign-suggested-donations" class="widefat charitable-campaign-suggested-donations">
		<thead>
			<tr class="table-header">
				<th colspan="<?php echo count( $charitable_fields ) + 2; ?>"><label for="campaign_suggested_donations"><?php echo esc_html( $title ); ?></label></th>
			</tr>
			<tr>
				<?php $charitable_i = 1; ?>
				<?php foreach ( $charitable_fields as $charitable_key => $charitable_field ) : ?>
					<th <?php echo $charitable_i === 1 ? 'colspan="2"' : ''; ?> class="<?php echo esc_attr( $charitable_key ); ?>-col"><?php echo wp_kses_post( $charitable_field['column_header'] ); ?></th>
					<?php ++$charitable_i; ?>
				<?php endforeach ?>
				<th class="remove-col"></th>
			</tr>
		</thead>
		<tbody>
			<tr class="no-suggested-amounts <?php echo ( count( $charitable_suggested_donations ) > 1 ) ? 'hidden' : ''; ?>">
				<td colspan="<?php echo count( $charitable_fields ) + 2; ?>"><?php esc_html_e( 'No suggested amounts have been created yet.', 'charitable' ); ?></td>
			</tr>
		<?php

		$charitable_counter = 1;

		foreach ( $charitable_suggested_donations as $charitable_i => $charitable_donation ) :

			?>
			<tr data-index="<?php echo esc_attr( $charitable_i ); ?>" class="<?php echo ( $charitable_donation === end( $charitable_suggested_donations ) ) ? 'to-copy hidden' : 'default'; ?>">
				<td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle"></span></td>
				<?php



				foreach ( $charitable_fields as $charitable_key => $charitable_field ) :

					if ( isset( $charitable_field['type'] ) && 'radio' === $charitable_field['type'] ) {

						$charitable_checked = false;

						if ( $charitable_suggested_default_counter > 0 && false !== $charitable_suggested_default && ( $charitable_counter ) === intval( $charitable_suggested_default_counter ) ) {
							$charitable_checked = true;
						}

						?>

					<td class="<?php echo esc_attr( $charitable_key ); ?>-col"><input
						type="radio"
						class="campaign_suggested_donations"
						<?php if ( $charitable_checked ) : ?>
						checked="checked"
						<?php endif; ?>
						name="_campaign_suggested_donations_default[]"
						value="<?php echo esc_attr( $charitable_i ); ?>" />
					</td>

						<?php

					} else {

						if ( is_array( $charitable_donation ) && isset( $charitable_donation[ $charitable_key ] ) ) {
							$charitable_value = $charitable_donation[ $charitable_key ];
						} elseif ( 'amount' == $charitable_key ) { // phpcs:ignore
							$charitable_value = $charitable_donation;
						} else {
							$charitable_value = '';
						}

						if ( 'amount' == $charitable_key && strlen( $charitable_value ) ) { // phpcs:ignore
							$charitable_value = charitable_format_money( $charitable_value, false, true );
						}
						?>
					<td class="<?php echo esc_attr( $charitable_key ); ?>-col"><input
						type="text"
						class="campaign_suggested_donations"
						name="_campaign_suggested_donations[<?php echo esc_attr( $charitable_i ); ?>][<?php echo esc_attr( $charitable_key ); ?>]"
						value="<?php echo esc_attr( $charitable_value ); ?>"
						placeholder="<?php echo esc_attr( $charitable_field['placeholder'] ); ?>" />
					</td>
						<?php

					}

				endforeach
				?>
				<td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>
			</tr>
			<?php

			++$charitable_counter;
			endforeach
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="<?php echo count( $charitable_fields ) + 2; ?>"><a class="button" href="#" data-charitable-add-row="suggested-amount"><?php esc_html_e( '+ Add a Suggested Amount', 'charitable' ); ?></a></td>
			</tr>
		</tfoot>
	</table>
</div>
