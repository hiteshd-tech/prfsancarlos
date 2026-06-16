<?php
/**
 * Displays the table header.
 *
 * Override this template by copying it to yourtheme/charitable/tables/header.php
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

if ( ! array_key_exists( 'helper', $view_args ) ) {
    return;
}

?>
<tr>
    <?php foreach ( $view_args['helper']->columns as $charitable_key => $charitable_header ) : ?>
    <th scope="col" class="charitable-table-header-<?php echo esc_attr( $charitable_key ) ?>"><?php echo wp_kses_post( $charitable_header ) ?></th>
    <?php endforeach ?>
</tr>
