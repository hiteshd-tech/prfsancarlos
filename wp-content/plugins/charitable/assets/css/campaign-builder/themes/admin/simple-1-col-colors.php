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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#000000';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#2B66D1';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#F99E36';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#5AA152';

$slug    = 'simple-1-col'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

require_once '../../../../../includes/admin/campaign-builder/templates/functions-campaign-templates.php';

?>

/* field: headlines */

<?php echo charitable_esc_attr_php( $wrapper ); ?> h5.charitable-field-preview-headline,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-title,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-headline {
	color: <?php echo $primary; ?>;
}

/* field: donation amount */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount label,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo $primary; ?>;
	border-color: <?php echo $primary; ?> !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
	border-color: <?php echo $tertiary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo $primary; ?>;
}

/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: <?php echo $primary; ?>;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
	background-color: <?php echo $secondary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar span {

}

/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
	background-color: <?php echo $button; ?>;
	border-color: <?php echo $button; ?>;
	color: <?php echo charitable_get_constracting_text_color( $button ); ?>;
}




/* field: campaign summary */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div {
	color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div span {
	color: <?php echo $secondary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
	color: <?php echo $primary; ?>;
}


/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
	border: 1px solid transparent;
	background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
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
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li a {
	color: white;
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
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li a {
	color: white;
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
	border-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li a {
	color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li.active {
	border-bottom: 1px solid <?php echo $tertiary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li:hover {
	border-bottom: 1px solid <?php echo $tertiary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li.active a {
	color: black;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li:hover a {
	color: <?php echo $tertiary; ?>;
}

// @codingStandardsIgnoreEnd