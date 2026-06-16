<?php
/**
 * Add recurring donations support.
 *
 * @package   Charitable Stripe/Functions/Recurring Compat
 * @author    David Bisset
 * @copyright Copyright (c) 2021-2022, David Bisset
 * @license   http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since     1.3.0
 * @version   1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'charitable_recurring_get_donation_periods_i18n' ) ) :

	/**
	 * Return an i18n'ified associative array of all possible subscription periods.
	 *
	 * @since  1.3.0
	 *
	 * @param  int    $number Optional. An interval in the range 1-6.
	 * @param  string $period Optional. month|quarter|semiannual|year. If empty, all subscription periods are returned.
	 * @return mixed string|array
	 */
	function charitable_recurring_get_donation_periods_i18n( $number = 1, $period = '' ) {
		$translated_periods = apply_filters(
			'charitable_recurring_periods',
			[
				// translators: %s: number of periods.
				'month'      => sprintf( _n( '%s month', '%s months', $number, 'charitable' ), $number ),
				// translators: %s: number of periods.
				'quarter'    => sprintf( _n( '%s quarter', '%s quarters', $number, 'charitable' ), $number ),
				// translators: %s: number of periods.
				'semiannual' => sprintf( _n( '%s semiannual', '%s semiannuals', $number, 'charitable' ), $number ),
				// translators: %s: number of periods.
				'year'       => sprintf( _n( '%s year', '%s years', $number, 'charitable' ), $number ),
			]
		);

		if ( ! empty( $period ) ) {
			$value = isset( $translated_periods[ $period ] ) ? $translated_periods[ $period ] : '';
		} else {
			$value = $translated_periods;
		}

		return $value;
	}

endif;
