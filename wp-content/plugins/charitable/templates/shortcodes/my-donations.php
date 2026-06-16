<?php
/**
 * Displays a table of the user's donations, with links to the donation receipts.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/my-donations.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/Account
 * @since   1.4.0
 * @version 1.6.19
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_user      = array_key_exists( 'user', $view_args ) ? $view_args['user'] : charitable_get_user( get_current_user_id() );
$charitable_donations = $view_args['donations'];

/**
 * Do something before rendering the donations.
 *
 * @param  object[] $donations An array of donations as a simple object.
 * @param  array    $view_args All args passed to template.
 */
do_action( 'charitable_my_donations_before', $charitable_donations, $view_args );

if ( empty( $charitable_donations ) ) : ?>

	<p><?php esc_html_e( 'You have not made any donations yet.', 'charitable' ); ?></p>

<?php else : ?>

	<table class="charitable-my-donations charitable-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Date', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the donation date header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_date', $charitable_donations, $view_args );
				?>
				<th scope="col"><?php esc_html_e( 'Campaign', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the campaign header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_campaigns', $charitable_donations, $view_args );
				?>
				<th scope="col"><?php esc_html_e( 'Amount', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the amount header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_amount', $charitable_donations, $view_args );
				?>
				<th scope="col"><?php esc_html_e( 'Status', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the status header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.4
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 */
					do_action( 'charitable_my_donations_table_header_after_status', $charitable_donations );
				?>
				<th scope="col"><?php esc_html_e( 'Receipt', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the receipt header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_receipt', $charitable_donations, $view_args );
				?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $charitable_donations as $charitable_donation ) : ?>
			<tr>
				<td><?php echo esc_html( mysql2date( 'F j, Y', get_post_field( 'post_date', $charitable_donation->ID ) ) ); ?></td>
				<?php
					/**
					 * Add a cell after the donation date. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_date', $charitable_donation );
				?>
				<td><?php echo esc_html( $charitable_donation->campaigns ); ?></td>
				<?php
					/**
					 * Add a cell after the list of campaigns. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_campaigns', $charitable_donation );
				?>
				<td class="amount" data-title="<?php esc_attr_e( 'Amount', 'charitable' ); ?>">
					<?php
						/**
						 * Filter the total donation amount.
						 *
						 * @since 1.6.19
						 *
						 * @param string $amount   The total donation amount.
						 * @param object $donation The donation as a simple object.
						 */
						echo esc_html( apply_filters( 'charitable_my_donation_total_amount', charitable_format_money( $charitable_donation->amount ), $charitable_donation ) );
					?>
				</td>
				<?php
					/**
					 * Add a cell after the donation amount. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_amount', $charitable_donation );
				?>
				<td><?php echo esc_html( charitable_get_donation( $charitable_donation->ID )->get_status_label() ); ?></td>
				<?php
					/**
					 * Add a cell after the donation status. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.4
					 *
					 * @param object $donation The donation as a simple object.
					 */
					do_action( 'charitable_my_donations_table_after_status', $charitable_donation );
				?>
				<td><a href="<?php echo esc_url( charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $charitable_donation->ID ) ) ); ?>"><?php esc_html_e( 'View Receipt', 'charitable' ); ?></a></td>
				<?php
					/**
					 * Add a cell after the link to the receipt. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_receipt', $charitable_donation, $view_args );
				?>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php
endif;

/**
 * Do something after rendering the donations.
 *
 * @param  object[] $donations An array of donations as a simple object.
 * @param  array    $view_args All args passed to template.
 */
do_action( 'charitable_my_donations_after', $charitable_donations, $view_args );
