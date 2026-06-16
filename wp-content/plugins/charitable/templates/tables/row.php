<?php
/**
 * Displays a table header row.
 *
 * Override this template by copying it to yourtheme/charitable/tables/header-row.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Tables
 * @since   1.5.0
 * @version 1.5.0
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! array_key_exists( 'data', $view_args ) || ! array_key_exists( 'columns', $view_args ) ) {
    return;
}

$charitable_data    = $view_args['data'];
$charitable_columns = $view_args['columns'];

?>
<tr>
    <?php foreach ( $charitable_columns as $charitable_key => $charitable_header ) : ?>
    <td class="charitable-table-cell-<?php echo esc_attr( $charitable_key ) ?>"><?php echo array_key_exists( $charitable_key, $charitable_data ) ? wp_kses_post( $charitable_data[ $charitable_key ] ) : '' ?></td>
    <?php endforeach ?>
</tr>
