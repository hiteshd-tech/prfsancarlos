<?php
/**
 * Charitable Licenses Settings UI.
 *
 * @package     Charitable/Classes/Charitable_Licenses_Settings
 * @version     1.8.1.12
 * @author      David Bisset
 * @copyright   Copyright (c) 2023, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Licenses_Settings' ) ) :

	/**
	 * Charitable_Licenses_Settings
	 *
	 * @final
	 * @since   1.0.0
	 */
	final class Charitable_Licenses_Settings {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Licenses_Settings|null
		 */
		private static $instance = null;

		/**
		 * Create object instance.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.2.0
		 *
		 * @return  Charitable_Licenses_Settings
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Optionally add the licenses tab.
		 *
		 * @since   1.4.7
		 *
		 * @param   string[] $tabs Settings tabs.
		 * @return  string[]
		 */
		public function maybe_add_licenses_tab( $tabs ) {

			$products = charitable_get_helper( 'licenses' )->get_products();

			if ( empty( $products ) ) {
				return $tabs;
			}

			$show_licenses_tab = apply_filters( 'charitable_show_old_license_tab', false );

			if ( $show_licenses_tab ) :

				$tabs = charitable_add_settings_tab(
					$tabs,
					'licenses',
					__( 'Licenses', 'charitable' ),
					array(
						'index' => 4,
					)
				);

			endif;

			return $tabs;
		}

		/**
		 * Add the licenses tab settings fields.
		 *
		 * @since   1.0.0
		 *
		 * @return  array
		 */
		public function add_licenses_fields() {
			if ( ! charitable_is_settings_view( 'licenses' ) ) {
				return array();
			}

			$fields = array(
				'section'  => array(
					'title'    => '',
					'type'     => 'hidden',
					'priority' => 10000,
					'value'    => 'licenses',
					'save'     => false,
				),
				'licenses' => array(
					'title'    => false,
					'callback' => array( $this, 'render_licenses_table' ),
					'priority' => 4,
				),
			);

			foreach ( charitable_get_helper( 'licenses' )->get_products() as $key => $product ) {
				$fields[ $key ] = array(
					'type'     => 'text',
					'render'   => false,
					'priority' => 6,
				);
			}

			return $fields;
		}

		/**
		 * Add the licenses group.
		 *
		 * @since   1.0.0
		 *
		 * @param   string[] $groups Settings groups.
		 * @return  string[]
		 */
		public function add_licenses_group( $groups ) {
			$groups['licenses'] = array();
			return $groups;
		}

		/**
		 * Render the licenses table.
		 *
		 * @since   1.0.0
		 *
		 * @return  void
		 */
		public function render_licenses_table() {
			charitable_admin_view( 'settings/licenses' );
		}

		/**
		 * Check if a license is expiring in x seconds.
		 *
		 * @since   1.8.1.12
		 *
		 * @param   int $seconds The number of seconds to check for.
		 * @return  bool
		 */
		public function is_license_expiring( $seconds = 1209600 ) {

			if ( $this->is_license_expired() ) {
				return false;
			}

			// If the 'CHARITABLE_FORCE_EXPIRING_LICENSE' is defined, then return true.
			if ( defined( 'CHARITABLE_FORCE_EXPIRING_LICENSE' ) && CHARITABLE_FORCE_EXPIRING_LICENSE ) {
				return true;
			}

			$expire_date = $this->get_license_expire_date( 'timestamp' );

			if ( false === $expire_date ) {
				return false;
			}

			// expire_date must be a timestamp, if not then return false.
			if ( ! is_numeric( $expire_date ) ) {
				return false;
			}

			$current_date = time();
			// If the license is expiring in x seconds, return true.
			if ( $expire_date - $current_date < $seconds ) {
				return true;
			}
			return false;
		}

		/**
		 * Check if a license has expired,
		 *
		 * @since   1.8.1.12
		 *
		 * @return  bool
		 */
		public function is_license_expired() {

			// If the 'CHARITABLE_FORCE_EXPIRED_LICENSE' is defined, then return true. For troubleshooting.
			if ( defined( 'CHARITABLE_FORCE_EXPIRED_LICENSE' ) && CHARITABLE_FORCE_EXPIRED_LICENSE ) {
				return true;
			}

			$expire_date = $this->get_license_expire_date( 'timestamp' );

			if ( false === $expire_date ) {
				return false;
			}

			// expire_date must be a timestamp, if not then return false.
			if ( ! is_numeric( $expire_date ) ) {
				return false;
			}

			$current_date = time();
			// If the license is expiring in x seconds, return true.
			if ( $expire_date - $current_date < 0 ) {
				return true;
			}
			return false;
		}

		/**
		 * If a license has expired, do a license request (to make sure we have the most updated informatino, especially when it comes to license renewals).
		 *
		 * @since 1.8.3.2
		 *
		 * @return void
		 */
		public function maybe_check_if_license_expired() {

			// We need to be on the settings general page, otherwise bail.
			if ( ! charitable_is_settings_view( 'general' ) ) {
				return;
			}

			// Determine if the license is expired.
			if ( ! $this->is_license_expired() ) {
				return;
			}

			// Determine when (if ever) we last checked.

			$settings         = get_option( 'charitable_settings' );
			$license_settings = $settings['licenses'];

			if ( false === $license_settings || false === $license_settings['charitable-v2'] || ! isset( $license_settings['checked'] ) || time() > $license_settings['checked'] ) {
				/*
				* Check and see if this check recently happened.
				* If it has within the last day (and the license is still expired) then try again for a while.
				*/

				if ( ! empty( $license_settings['charitable-v2']['license'] ) ) :

					$license_data = charitable_get_helper( 'licenses' )->verify_license( 'charitable', esc_html( $license_settings['charitable-v2']['license'] ), true, false );

					// if the license "keys" don't match, abort.
					if ( empty( $license_data ) || ! isset( $license_data['license'] ) || $license_data['license'] !== $license_settings['charitable-v2']['license'] ) { // phpcs:ignore
						// do nothing.
					} else {
						// Check the expiration_date in the license data - if it's not the same as the current expiration date, update it.
						if ( isset( $license_data['expiration_date'] ) && $license_data['expiration_date'] !== $license_settings['charitable-v2']['expiration_date'] ) {
							$license_settings['charitable-v2']['expiration_date'] = $license_data['expiration_date'];
						}
					}

				endif;

				// We've done the check, so update the updated time for one day in the future.
				$license_settings['checked'] = strtotime( '+1 day' );
				$settings['licenses']        = $license_settings;

				global $wpdb;
				// manually update the 'charitable_settings' option.
				$wpdb->update( // phpcs:ignore
					$wpdb->options,
					array(
						'option_value' => maybe_serialize( $settings ),
					),
					array(
						'option_name' => 'charitable_settings',
					)
				);

				// redirect to the settings page.
				wp_safe_redirect( admin_url( 'admin.php?page=charitable-settings' ) );
				exit;
			}
		}

		/**
		 * If a license has expired, do a license request (to make sure we have the most updated informatino, especially when it comes to license renewals).
		 *
		 * @since 1.8.3.2
		 *
		 * @return void
		 */
		public function maybe_check_if_license_expiring() {

			// We need to be on the settings general page, otherwise bail.
			if ( ! charitable_is_settings_view( 'general' ) ) {
				return;
			}

			// Determine if the license is expired.
			if ( ! $this->is_license_expiring() ) {
				return;
			}

			// Determine when (if ever) we last checked.

			$settings         = get_option( 'charitable_settings' );
			$license_settings = $settings['licenses'];

			if ( false === $license_settings || false === $license_settings['charitable-v2'] || ! isset( $license_settings['checked'] ) || time() > $license_settings['checked'] ) {
				/*
				* Check and see if this check recently happened.
				* If it has within the last day (and the license is still expired) then try again for a while.
				*/

				if ( ! empty( $license_settings['charitable-v2']['license'] ) ) :

					$license_data = charitable_get_helper( 'licenses' )->verify_license( 'charitable', esc_html( $license_settings['charitable-v2']['license'] ), true, false );

					// if the license "keys" don't match, abort.
					if ( empty( $license_data ) || ! isset( $license_data['license'] ) || $license_data['license'] !== $license_settings['charitable-v2']['license'] ) { // phpcs:ignore
						// do nothing.
					} else {
						// Check the expiration_date in the license data - if it's not the same as the current expiration date, update it.
						if ( isset( $license_data['expiration_date'] ) && $license_data['expiration_date'] !== $license_settings['charitable-v2']['expiration_date'] ) {
							$license_settings['charitable-v2']['expiration_date'] = $license_data['expiration_date'];
						}
					}

				endif;

				// We've done the check, so update the updated time for one day in the future.
				$license_settings['checked'] = strtotime( '+1 day' );
				$settings['licenses']        = $license_settings;

				global $wpdb;
				// manually update the 'charitable_settings' option.
				$wpdb->update( // phpcs:ignore
					$wpdb->options,
					array(
						'option_value' => maybe_serialize( $settings ),
					),
					array(
						'option_name' => 'charitable_settings',
					)
				);

				// redirect to the settings page.
				wp_safe_redirect( admin_url( 'admin.php?page=charitable-settings' ) );
				exit;
			}
		}

		/**
		 * Get the expire data of a current license (checks for "is_pro" happen before this function).
		 *
		 * @since   1.8.1.12
		 * @version 1.8.1.14 added lifetime license check.
		 *
		 * @param mixed $timestamp Whether to return a timestamp or a formatted date.
		 *
		 * @return  mixed[]
		 */
		public function get_license_expire_date( $timestamp = false ) {

			$settings = get_option( 'charitable_settings' );

			if ( false === $settings ) {
				return false;
			}

			$license_key     = ! empty( $settings['licenses']['charitable-v2']['license'] ) ? esc_html( $settings['licenses']['charitable-v2']['license'] ) : false;
			$license_expires = ! empty( $settings['licenses']['charitable-v2']['expiration_date'] ) ? esc_html( $settings['licenses']['charitable-v2']['expiration_date'] ) : false;

			if ( strtolower( $license_expires ) === 'lifetime' ) {
				// make the license expire date a long time from now.
				if ( $timestamp ) {
					return strtotime( '+100 years' );
				} else {
					return date_i18n( get_option( 'date_format' ), strtotime( '+100 years' ) );
				}
			}

			if ( false === $license_key || false === $license_expires ) {
				return false;
			}

			if ( $timestamp ) {
				return strtotime( $license_expires );
			}

			return date_i18n( get_option( 'date_format' ), strtotime( $license_expires ) );
		}

		/**
		 * Add an extra button to the Licenses tab to re-check licenses.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $button The button HTML.
		 * @return string
		 */
		public function add_license_recheck_button( $button ) {
			$licenses = array_filter( charitable_get_helper( 'licenses' )->get_licenses(), 'is_array' );

			if ( empty( $licenses ) ) {
				return $button;
			}

			$slug = Charitable_Addons_Directory::get_current_plan_slug();

			if ( $slug === false || ( is_string( $slug ) && strtolower( $slug ) === 'lite' ) ) {
				// there is no valid updated license so allow this button to output.

				$html = '<input style="margin-left:8px;" type="submit" class="button button-secondary" name="recheck" value="' . esc_attr__( 'Save & Re-check All Licenses', 'charitable' ) . '" /></p>';

				return str_replace(
					'</p>',
					$html,
					$button
				);

			}

			return $button;
		}

		/**
		 * Checks for updated license and invalidates status field if not set.
		 *
		 * @since   1.0.0
		 * @version 1.7.0.4
		 * @version 1.8.6.2 - clear transient _charitable_plugin_versions
		 * @version 1.8.9.1
		 *
		 * @param   mixed[] $values The parsed values combining old values & new values.
		 * @param   mixed[] $new_values The newly submitted values.
		 * @return  mixed[]
		 */
		public function save_license( $values, $new_values ) {

			/* If we didn't just submit licenses, stop here. */
			if ( ! isset( $new_values['licenses_legacy'] ) ) {
				return $values;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$re_check = array_key_exists( 'recheck', $_POST );
			$licenses = $new_values['licenses_legacy'];

			// Delete transients (related to plugin versions).
			delete_transient( '_charitable_plugin_versions' );

			// Remember that legacy licenses are passed into values differently in this hook. $values[licenses_legacy].
			foreach ( $licenses as $product_key => $license ) {
				$license = trim( $license );

				if ( empty( $license ) ) {
					$values['licenses_legacy'][ $product_key ] = '';
					continue;
				}

				$license_data = charitable_get_helper( 'licenses' )->verify_license( $product_key, $license, $re_check, true ); // the true added to make sure we let the server know we are attempting to vertify a legacy license.

				if ( empty( $license_data ) ) {
					continue;
				}

				$values['licenses'][ $product_key ] = $license_data; // this is ok because this follows previous versions of where the licenses would be going.
			}

			return $values;
		}

		/**
		 * Outputs the "new" license HTML for the general settings tab.
		 *
		 * @since   1.7.0.4
		 * @version 1.8.3 added charitable_is_pro() check.
		 * @version 1.8.5 added pro_connect flag.
		 *
		 * @param   string $has_valid_license Allows us to control HTML based on valid license already in the system.
		 * @return  string
		 */
		public function generate_license_check_html( $has_valid_license = 'false' ) {

			$slug              = (string) Charitable_Addons_Directory::get_current_plan_slug();
			$is_legacy         = Charitable_Addons_Directory::is_current_plan_legacy();
			$pro_connect       = $this->is_pro_connect();
			$readonly          = false;
			$show_license_form = true;

			// PRE-VALIDATION: Check for existing valid licenses before showing upgrade prompt
			$licenses_helper = charitable_get_helper( 'licenses' );
			$has_stored_valid_license = false;

			// Check if there's already a valid license stored (prevents duplicate prompts)
			if ( $licenses_helper ) {
				// Check for v3 license (newer format)
				if ( method_exists( $licenses_helper, 'is_v3_license_valid' ) && $licenses_helper->is_v3_license_valid() ) {
					$has_stored_valid_license = true;
				}
				// Check for any valid license in the system
				elseif ( method_exists( $licenses_helper, 'has_valid_license' ) && $licenses_helper->has_valid_license( 'charitable' ) ) {
					$has_stored_valid_license = true;
				}
			}

			if ( $slug === false || strtolower( $slug ) === 'lite' ) {
				$has_valid_license = $has_stored_valid_license; // Use stored license status instead of always false
			} elseif ( charitable_is_pro() ) {
				$has_valid_license = true;
			}

			$output = '<div id="charitable-license-message" class="license-message license-valid-' . $has_valid_license . '">';
			if ( $has_valid_license && ! $is_legacy ) {
				$output  .= $this->get_licensed_message();
				$readonly = 'readonly value="xxxxxxxxxxxxxxxxxxxx"';
			} else {
				// Getting the current plan slug might be only applicate for newer licenses, so we need to account for "legacy" licenses to see if the install is licensed or not.
				if ( charitable_is_pro() && $is_legacy ) {
					$output           .= $this->get_legacy_licensed_message();
					$show_license_form = false;
				} else {
					// 1.8.5
					if ( charitable_is_pro() ) {
						$output           .= $this->get_licensed_message();
						$readonly          = 'readonly value="xxxxxxxxxxxxxxxxxxxx"';
						$has_valid_license = true;
					} else {
						$output .= $this->get_unlicensed_message();
					}
				}
			}
			if ( $show_license_form ) :
				$output .= '<p>';
				$output .= '<input type="password" autocomplete="off" name="license-key" id="charitable-settings-upgrade-license-key" ' . $readonly . ' placeholder="' . esc_attr__( 'Paste license key here', 'charitable' ) . '" value="" />';
				if ( ! $has_valid_license ) {
					$output .= '<button data-action="verify" type="button" class="charitable-btn charitable-btn-md charitable-btn-green charitable-btn-activate" id="charitable-settings-connect-btn" data-pro-connect="' . $pro_connect . '">' . esc_html__( 'Verify Key', 'charitable' ) . '</button>';
				} elseif ( $has_valid_license ) {
					$output .= '<button data-action="deactivate" type="button" class="charitable-btn charitable-btn-md charitable-btn-orange charitable-btn-deactivate" id="charitable-settings-connect-btn">' . esc_html__( 'Deactivate Key', 'charitable' ) . '</button>';
				}
				$output .= '</p>';
			endif;

			// Add help text to the license field.
			$output .= '<div class="charitable-help">' . esc_html__( 'Your license key provides access to updates and addons.', 'charitable' ) . '</div>';

			$output . '</div>';

			return $output;
		}

		/**
		 * Checks if the pro_connect flag is set. This decides if entering a license key will trigger Charitable Connect, an improved migration path to Charitable Pro.
		 *
		 * @since   1.8.5
		 * @version 1.8.5.3
		 *
		 * @return boolean
		 */
		public function is_pro_connect() {

			if ( defined( 'CHARITABLE_FORCE_PRO_CONNECT' ) && CHARITABLE_FORCE_PRO_CONNECT ) {
				return true;
			}

			if ( defined( 'CHARITABLE_MANUAL_LICENSE_ACTIVATION' ) && CHARITABLE_MANUAL_LICENSE_ACTIVATION ) {
				return false;
			}

			// If the pro plugin is already installed (but not activated), then we don't need to do anything.
			if ( file_exists( WP_PLUGIN_DIR . '/charitable-pro/charitable.php' ) ) {
				return false;
			}

			$charitable_activated = (array) get_option( 'charitable_activated', array() );

			// If pro connect has been done before, it can be done again.
			if ( ! empty( $charitable_activated['pro_connect'] ) ) {
				return true;
			}

			// Allows special cases to be handled by other plugins.
			return apply_filters( 'charitable_allow_pro_connect', true );
		}

		/**
		 * Outputs a message for unlicnensed users for the general settings tab.
		 *
		 * @since   1.7.0.4
		 *
		 * @param   boolean $valid Valid license.
		 * @param   array   $license_data Available license information.
		 * @param   boolean $error Error flag.
		 * @return  string
		 */
		public function get_unlicensed_message( $valid = false, $license_data = false, $error = true ) {

			$output  = '<p>' . esc_html__( 'You\'re using ', 'charitable' );
			$output .= '<strong>Charitable Lite</strong>';
			$output .= esc_html__( ' - no license needed. Enjoy!', 'charitable' ) . ' 🙂</p>';
			$output .=
				'<p>' .
				sprintf(
					wp_kses(
						/* translators: %s - charitable.com upgrade URL. */
						__( 'To unlock more features consider <strong><a href="%s" target="_blank" rel="noopener noreferrer" class="charitable-upgrade-modal">upgrading to PRO</a></strong>.', 'charitable' ),
						array(
							'a'      => array(
								'href'   => array(),
								'class'  => array(),
								'target' => array(),
								'rel'    => array(),
							),
							'strong' => array(),
						)
					),
					esc_url( charitable_pro_upgrade_url( 'settings-upgrade' ) )
				) .
				'</p>';
			$output .=
				'<p class="discount-note">' .
					wp_kses(
						__( 'As a valued Charitable Lite user, you receive up to <strong>$300 off</strong>, automatically applied at checkout!', 'charitable' ),
						array(
							'strong' => array(),
							'br'     => array(),
						)
					) .
				'</p>';

			if ( $valid && false === $error ) {
				$output .= '<p>' . esc_html__( 'Already registered? You might have an expired or invalid license. Reach out to us for support.', 'charitable' ) . '</p>';
			} elseif ( ! $valid && isset( $license_data['license_limit'] ) && false !== $license_data['license_limit'] ) {
				$output .= '<p>' . esc_html__( 'There was an error attempting to validate your license key. Check and see if you have exceeded your license activations.', 'charitable' ) . '</p>';
			} elseif ( ! $valid && isset( $license_data['comm_success'] ) && false !== $license_data['comm_success'] ) {
				$output .= '<p>' . esc_html__( 'There was an error attempting to contact the license server. Please try again later.', 'charitable' ) . '</p>';
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			} elseif ( isset( $_GET['valid'] ) && 'invalid' === sanitize_text_field( wp_unslash( $_GET['valid'] ) ) && isset( $_GET['comm_success'] ) && 0 === intval( wp_unslash( $_GET['comm_success'] ) ) ) {
				$output .= '<p style="color:red;">' . esc_html__( 'There was an error attempting to validate your license key. Please try again later.', 'charitable' ) . '</p>';
			} elseif ( isset( $_GET['valid'] ) && 'invalid' === sanitize_text_field( wp_unslash( $_GET['valid'] ) ) && isset( $_GET['license_limit'] ) && false !== sanitize_text_field( wp_unslash( $_GET['license_limit'] ) ) ) {
				$output .= '<p style="color:red;">' . esc_html__( 'There was an error attempting to validate your license key. Check and see if you have exceeded your license activations.', 'charitable' ) . '</p>';
			} elseif ( isset( $_GET['valid'] ) && 'invalid' === sanitize_text_field( wp_unslash( $_GET['valid'] ) ) ) {
				$output .= '<p style="color:red;" data-invalid="Unknown">' . esc_html__( 'There was a problem attempting to validate your license key. Please try again later.', 'charitable' ) . '</p>';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			} else {
				$output .= '<hr><p>' . esc_html__( 'Already purchased? Simply enter your license key below to enable Charitable PRO!', 'charitable' ) . '</p>';
			}

			return $output;
		}

		/**
		 * Outputs a message for licnensed users for the general settings tab.
		 *
		 * @since   1.7.0.4
		 * @version 1.8.1.15 added license expiring message, account for no license expire date (lifetime licenses).
		 *
		 * @param   boolean $force_valid Force valid license (not used).
		 * @return  string
		 */
		public function get_licensed_message( $force_valid = true ) { // phpcs:ignore

			$settings        = get_option( 'charitable_settings' );
			$price_id        = intval( $settings['licenses']['charitable-v2']['plan_id'] );
			$license_expires = ! empty( $settings['licenses']['charitable-v2']['expiration_date'] ) ? esc_html( $settings['licenses']['charitable-v2']['expiration_date'] ) : false;
			$valid           = $settings['licenses']['charitable-v2']['valid'];
			$output          = '';

			if ( $valid ) {

				$plan_name = $this->get_license_label_from_plan_id( $price_id );

				$output  = '<p>' . esc_html__( 'You\'re using ', 'charitable' );
				$output .= '<strong>Charitable ' . $plan_name . '</strong>';
				if ( $license_expires ) :
					if ( 'lifetime' === $license_expires ) :
						$output .= esc_html__( '. You have a lifetime license', 'charitable' );
					else :
						$output .= esc_html__( '. Your license expires on ', 'charitable' );
						$output .= gmdate( 'M d, Y', strtotime( $license_expires ) );
					endif;
				endif;
				$output .= esc_html__( '. Enjoy!', 'charitable' ) . ' 🙂</p>';

				if ( $this->is_license_expiring() ) {
					$output .= '<p style="color:red;">' . sprintf(
						wp_kses(
							/* translators: %s - charitable.com upgrade URL. */
							__( 'Your license may be expiring soon. Please renew to continue receiving updates and support. <a target="_blank" href="%s">Learn more</a>.', 'charitable' ),
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
						'https://wpcharitable.com/documentation/expired-expiring-license'
					) . '</p>';
				} elseif ( $this->is_license_expired() ) {

					$output .= '<p style="color:red;">' . sprintf(
						wp_kses(
							/* translators: %s - charitable.com upgrade URL. */
							__( 'It appears your license may have expired. Please renew to continue receiving updates and support. <a target="_blank" href="%s">Learn more</a>.', 'charitable' ),
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
						'https://wpcharitable.com/documentation/expired-expiring-license'
					) . '</p>';
				}

				if ( defined( 'CHARITABLE_DEBUG_LICENSE' ) && CHARITABLE_DEBUG_LICENSE ) {
					$output      .= '<fieldset style="border: 1px; margin: 10px 0; padding: 10px; border-width: 10px; background-color: #eae2d3;"><legend style="border: 1px; background: white; padding: 10px;">Charitable Debug License Data</legend>';
					$license_data = '';
					foreach ( $settings['licenses'] as $key => $value ) {
						if ( is_array( $settings['licenses'][ $key ] ) ) {
							foreach ( $settings['licenses'][ $key ] as $k => $v ) {
								$license_data .= '<p>' . $k . ' : ' . $v . '</p>';
							}
						}
					}

					$output .= '<p><strong>' . esc_html__( 'License data:', 'charitable' ) . '</strong> ' . $license_data . '</p>';
					if ( $settings ) {
						$output .= '<p><strong>' . esc_html__( 'Settings data:', 'charitable' ) . '</strong> ' . print_r( $settings, true ) . '</p>'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					}
					if ( $plan_name ) {
						$output .= '<p><strong>' . esc_html__( 'Plan name:', 'charitable' ) . '</strong> ' . print_r( $plan_name, true ) . '</p>'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					}
					$output .= '</fieldset>';
					error_log( 'get_licensed_message - valid' ); // phpcs:ignore
					error_log( print_r( $settings, true ) ); // phpcs:ignore
					error_log( print_r( $plan_name, true ) ); // phpcs:ignore
				}
			} else {
				$plan_name = esc_html__( 'Lite', 'charitable' );
				if ( intval( $price_id ) > 0 ) {
					$valid = esc_html__( 'Your license is not valid or has expired.', 'charitable' );
				} else {
					$valid = false;
				}
				$output .= $this->get_unlicensed_message( $valid );
				if ( defined( 'CHARITABLE_DEBUG_LICENSE' ) && CHARITABLE_DEBUG_LICENSE ) {
					error_log( 'get_licensed_message - not valid' ); // phpcs:ignore
					error_log( print_r( $valid, true ) ); // phpcs:ignore
				}
			}

			return $output;
		}

		/**
		 * Outputs a message for deactivated licenses for the general settings tab.
		 *
		 * @since   1.8.5
		 * @return  string
		 */
		public function get_deactivated_message() {

			return sprintf(
				'<p class="charitable-license-message-details">'
				. '<strong>' . __( 'Your license key has been removed.', 'charitable' ) . '</strong>'
				. '</p>'
			);
		}

		/**
		 * Get license label from plan id.
		 *
		 * @since   1.8.0
		 *
		 * @param   boolean $plan_id Plan ID.
		 * @return  string
		 */
		public function get_license_label_from_plan_id( $plan_id = false ) {

			return charitable_get_license_label_from_plan_id( $plan_id );
		}

		/**
		 * Outputs a message for unlicnensed users for the general settings tab.
		 *
		 * @since   1.7.0.4
		 * @version 1.8.3.2 remove upsell message.
		 * @return  string
		 */
		public function get_legacy_licensed_message() {

			$output =
				'<p>' .
				sprintf(
					wp_kses(
						/* translators: %s - charitable.com upgrade URL. */
						__( 'You\'re using <strong>Charitable</strong> with one or more <a href="%s">activated legacy licenses</a>. Enjoy! 🙂', 'charitable' ),
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
					esc_url( admin_url( 'admin.php?page=charitable-settings&tab=advanced' ) )
				) .
				'</p>';

			return $output;
		}

		/**
		 * Activates the pro plugin after a license is activated.
		 *
		 * @since   1.8.5.3
		 * @return  void
		 */
		public function activate_pro_plugin_after_license_activation() {

			// Check query params.
			if ( ! isset( $_GET['valid'] ) || 'valid' !== $_GET['valid'] ) { // phpcs:ignore
				return;
			}

			if ( ! isset( $_GET['pro'] ) || 'activate' !== $_GET['pro'] ) { // phpcs:ignore
				return;
			}
			// Confirm pro plugin is installed.
			if ( ! file_exists( WP_PLUGIN_DIR . '/charitable-pro/charitable.php' ) ) {
				return;
			}

			// Activate the pro plugin.
			activate_plugin( 'charitable-pro/charitable.php' );

			// Redirect to the settings page.
			wp_safe_redirect( admin_url( 'admin.php?page=charitable-settings&tab=general&valid=valid' ) );
			exit;
		}
	}

endif;
