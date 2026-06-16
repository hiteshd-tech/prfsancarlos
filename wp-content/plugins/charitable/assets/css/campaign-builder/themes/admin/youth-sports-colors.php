<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS.
 *
 * @package Charitable
 * @author  WP Charitable LLC
 * @since   1.8.0
 * @version 1.8.3.7
 */

header( 'Content-type: text/css; charset: UTF-8' );

if ( ! function_exists( 'charitable_sanitize_hex_color' ) ) {
	/**
	 * Sanitize a hex color.
	 *
	 * @param string $color The color to sanitize.
	 * @return string|null The sanitized color or null if the color is invalid.
	 */
	function charitable_sanitize_hex_color( $color ) {
		// Ensure the value is a string.
		$color = trim( $color );

		// Check if it's a valid 6-character hex color including the hash.
		if ( preg_match( '/^#[a-fA-F0-9]{6}$/', $color ) ) {
			return $color;
		}

		// Optionally return a default color or handle errors.
		return null; // Or return default color.
	}
}

if ( ! function_exists( 'charitable_esc_attr_php' ) ) {
	/**
	 * Escapes a string for use in PHP.
	 *
	 * @param string $text The text to escape.
	 * @return string The escaped text.
	 */
	function charitable_esc_attr_php( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

// @codingStandardsIgnoreStart

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#3418d2';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#005eff';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#00a1ff';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#ec5f25';

$slug    = 'youth-sports'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

require_once '../../../../../includes/admin/campaign-builder/templates/functions-campaign-templates.php';
?>

/* row specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-row {
	background-color: <?php echo $primary; ?>;
	color: white;
}

/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
	background-color: rgba(255,255,255,0.5);
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
	background-color: #fff;
}

/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
	background-color: <?php echo $button; ?>;
	border-color: <?php echo $button; ?>;
	color: <?php echo charitable_get_constracting_text_color( $button ); ?>;
}

/* field: donate amount */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount label,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected label {
	border-color: <?php echo $button; ?> !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {

}

/* field: campaign summary */


<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div span {
	color: white;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
	color: white;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .charitable-field-preview-campaign-summary div span {
	color: black;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .charitable-field-preview-campaign-summary .campaign-summary-item {
	color: black;
}


/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
	border: 1px solid transparent;
	background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
	color: #DDD;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active {
	background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li:hover {
	background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active a {
	color: black;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li:hover a {
	color: <?php echo $tertiary; ?>;
}

/* tabs: style */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li.active {
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li:hover {
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li.active a {
	color: white;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li:hover a {
	color: white;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li.active {
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li:hover {
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li.active a {
	color: white;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li:hover a {
	color: white;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li {
	background-color: transparent;
	border-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li a {
	color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li.active {
	border-color: <?php echo $button; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li:hover {
	border-color: <?php echo $button; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li.active a {
	color: black;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li:hover a {
	color: <?php echo $tertiary; ?>;
}


/* missing addons? */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing {
	background-color: rgba(0,0,0,.7);
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing .charitable-missing-addon-content {
	background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing .charitable-missing-addon-content,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing .charitable-missing-addon-content h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing .charitable-missing-addon-content p {
	color: white !important;
}

// @codingStandardsIgnoreEnd