<?php
/**
 * Sets up translations for Charitable Stripe.
 *
 * @package     Charitable Stripe/Classes/i18n
 * @version     1.0.0
 * @author      David Bisset
 * @copyright   Copyright (c) 2018, WP Charitable LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Charitable_Stripe_i18n' ) ) :

	/**
	 * Charitable_Stripe_i18n
	 *
	 * @since       1.0.0
	 */
	class Charitable_Stripe_i18n extends Charitable_i18n {

		/**
		 * The single instance of this class.
		 *
		 * @var     Charitable_Stripe_i18n|null
		 * @access  private
		 * @static
		 */
		private static $instance = null;

		/**
		 * Text domain for the plugin.
		 *
		 * @var     string
		 * @access 	protected
		 */
		protected $textdomain = 'charitable-stripe';

		/**
		 * Set up the class.
		 *
		 * @access  private
		 * @since   1.0.0
		 */
		private function __construct() {
			$this->languages_directory = apply_filters( 'charitable_stripe_languages_directory', 'charitable-stripe/languages' );
			$this->locale = apply_filters( 'plugin_locale', get_locale(), $this->textdomain ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'plugin_locale' is a WordPress core hook for plugin localization.
			$this->mofile = sprintf( '%1$s-%2$s.mo', $this->textdomain, $this->locale );

			$this->load_textdomain();
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @return  Charitable_Stripe_i18n
		 * @access  public
		 * @since   1.1.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Charitable_Stripe_i18n();
			}

			return self::$instance;
		}
	}

endif;
