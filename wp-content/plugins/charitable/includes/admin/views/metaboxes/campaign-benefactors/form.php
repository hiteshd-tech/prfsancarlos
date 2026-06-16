<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the campaign benefactors form.
 *
 * @author    David Bisset
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.35
 * @version   1.8.8.6
 */

$charitable_i18n       = charitable()->registry()->get( 'i18n' );
$charitable_php_format = $charitable_i18n->get_datepicker_format( 'F d, Y' );
$charitable_js_format  = $charitable_i18n->get_js_datepicker_format( 'MM d, yy' );

$charitable_benefactor           = isset( $view_args['benefactor'] ) ? $view_args['benefactor'] : null;
$charitable_extension            = isset( $view_args['extension'] ) ? $view_args['extension'] : '';
$charitable_is_active_benefactor = is_null( $charitable_benefactor ) || $charitable_benefactor->is_active();

if ( is_null( $charitable_benefactor ) ) {
	$charitable_default_args = array(
		'index'                           => '_0',
		'contribution_amount'             => '',
		'contribution_amount_is_per_item' => 0,
		'date_created'                    => date_i18n( $charitable_php_format ),
		'date_deactivated'                => 0,
	);

	$charitable_args = array_merge( $charitable_default_args, $view_args );
} else {
	$charitable_args = array(
		'index'                           => $charitable_benefactor->campaign_benefactor_id,
		'contribution_amount'             => $charitable_benefactor->get_contribution_amount(),
		'contribution_amount_is_per_item' => $charitable_benefactor->contribution_amount_is_per_item,
		'date_created'                    => date_i18n( $charitable_php_format, strtotime( $charitable_benefactor->date_created ) ),
		'date_deactivated'                => '0000-00-00 00:00:00' == $charitable_benefactor->date_deactivated ? '' : date_i18n( $charitable_php_format, strtotime( $charitable_benefactor->date_deactivated ) ), // phpcs:ignore
	);
}

$charitable_id_base   = 'campaign_benefactor_' . $charitable_args['index'];
$charitable_name_base = '_campaign_benefactor[' . $charitable_args['index'] . ']';

?>
<div id="<?php echo esc_attr( $charitable_id_base ); ?>" class="charitable-metabox-wrap charitable-benefactor-wrap" style="display: none;">
	<?php if ( is_null( $charitable_benefactor ) ) : ?>
		<a class="charitable-benefactor-form-cancel" href="#"><?php esc_html_e( 'Cancel', 'charitable' ); ?></a>
	<?php endif ?>
	<p><strong><?php esc_html_e( 'Contribution Amount', 'charitable' ); ?></strong></p>
	<fieldset class="charitable-benefactor-contribution-amount">
		<input type="text" id="<?php echo esc_attr( $charitable_id_base ); ?>_contribution_amount" class="contribution-amount" name="<?php echo esc_attr( $charitable_name_base ); ?>[contribution_amount]" value="<?php echo esc_attr( $charitable_args['contribution_amount'] ); ?>" placeholder="<?php esc_html_e( 'Enter amount. e.g. 10%, $2', 'charitable' ); ?>" />
		<select id="<?php echo esc_attr( $charitable_id_base ); ?>_contribution_amount_is_per_item" class="contribution-type" name="<?php echo esc_attr( $charitable_name_base ); ?>[contribution_amount_is_per_item]">
			<option value="1" <?php selected( 1, $charitable_args['contribution_amount_is_per_item'] ); ?>><?php esc_html_e( 'Apply to every matching item', 'charitable' ); ?></option>
			<option value="0" <?php selected( 0, $charitable_args['contribution_amount_is_per_item'] ); ?>><?php esc_html_e( 'Apply only once per purchase', 'charitable' ); ?></option>
		</select>
	</fieldset>
	<?php
		do_action( 'charitable_campaign_benefactor_form_extension_fields', $charitable_benefactor, $charitable_extension, $charitable_args['index'] );
	?>
	<div class="charitable-benefactor-date-wrap cf">
		<label for="<?php echo esc_attr( $charitable_id_base ); ?>_date_created"><?php esc_html_e( 'Starting From:', 'charitable' ); ?>
			<input type="text"
				id="<?php echo esc_attr( $charitable_id_base ); ?>_date_created"
				name="<?php echo esc_attr( $charitable_name_base ); ?>[date_created]"
				tabindex="3"
				class="charitable-datepicker"
				autocomplete="off"
				data-date="<?php echo esc_attr( $charitable_args['date_created'] ); ?>"
				data-format="<?php echo esc_attr( $charitable_js_format ); ?>"
			/>
		</label>
		<label for="<?php echo esc_attr( $charitable_id_base ); ?>_date_deactivated"><?php esc_html_e( 'Ending:', 'charitable' ); ?>
			<input type="text"
				id="<?php echo esc_attr( $charitable_id_base ); ?>_date_deactivated"
				name="<?php echo esc_attr( $charitable_name_base ); ?>[date_deactivated]"
				placeholder="&#8734;"
				tabindex="3"
				class="charitable-datepicker"
				autocomplete="off"
				data-date="<?php echo esc_attr( $charitable_args['date_deactivated'] ); ?>"
				data-format="<?php echo esc_attr( $charitable_js_format ); ?>"
			/>
		</label>
	</div>
</div>
