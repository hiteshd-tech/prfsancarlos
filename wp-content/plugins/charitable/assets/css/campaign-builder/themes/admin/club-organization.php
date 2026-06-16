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

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#3E4735';
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#B49A5F';
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#F4F0EE';
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#B49A5F';
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'club-organization'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper         = '.charitable-campaign-wrap.template-' . $slug; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

?>

:root {
	--charitable_campaign_theme_primary: <?php echo charitable_sanitize_hex_color( $primary ); ?>;
	--charitable_campaign_theme_secondary: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	--charitable_campaign_theme_tertiary: <?php echo charitable_sanitize_hex_color( $tertiary ); ?>;
	--charitable_campaign_theme_button: <?php echo charitable_sanitize_hex_color( $button ); ?>;
}

/* this narrows things down a little to the preview area header/tabs */
<?php echo charitable_esc_attr_php( $wrapper ); ?> {
	font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-row {
	background-color: <?php echo charitable_sanitize_hex_color( $primary ); ?>;
	color: #606060;
	padding: 25px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-row > * {
	color: #D8DAD7;
}

/* aligns */


/* column specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-column:nth-child(even) {
	flex: 2;
	padding-top: 50px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-column:nth-child(odd) {
	flex: 1;
	padding-top: 15px;
	padding-bottom: 15px;
	padding-left: 15px;
	padding-right: 15px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-column.charitable-campaign-column-2 {
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-column:nth-child(even) .charitable-campaign-field {
	margin-top: 0;
	margin-bottom: 0;
}



/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?> h5.charitable-field-template-headline,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-title,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-headline {
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?> !important;
}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-campaign-field_campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?> !important;
	font-size: 24px !important;
	line-height: 28px !important;
	font-weight: 100 !important;
	word-wrap: anywhere;
}

/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text {
	padding: 0;
	color: inherit !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text,
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
	color: inherit !important;
}


/* field: campaign text */


/* field: html */


/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-donate-button button.button,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-donate-button a.donate-button {
	background-color: <?php echo charitable_sanitize_hex_color( $button ); ?> !important;
	border-color: <?php echo charitable_sanitize_hex_color( $button ); ?> !important;
	text-transform: uppercase;
	border-radius: 10px;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	min-height: 50px;
	height: 50px;
	font-size: 16px;
	line-height: 16px;
	display: flex; /* Changed from block to flex */
	align-items: center; /* Vertically centers the text */
	justify-content: center; /* Optionally centers the text horizontally too */
	padding: 0;
	text-align: center !important;
	text-decoration: none !important;
	color: white;
	transition: filter 0.3s; /* Smooth transition */
}


/* field: photo */


/* field: photo */


/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #FFFFFF;
	font-size: 21px;
	line-height: 21px;
	font-weight: 100;
	padding-left: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	font-weight: 100;
	font-size: 21px;
	line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 0px;
	margin-top: 15px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	height: 13px !important;
	border-radius: 0px;
	text-align: right;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
	display: none;
}

/* field: social linking */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-social-links {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking {
	display: table;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-field-template-social-linking-headline-container  {
	float: none;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking-headline-container h5 {
	margin-right: 10px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-field-row p {
	display: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking h5.charitable-field-template-headline {
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-placeholder {
	padding: 10px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column .charitable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-linking .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}


/* field: social sharing */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-social-sharing {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing {
	display: table;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container   {
	float: none;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing-headline-container h5.charitable-field-template-headline {
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px !important;
	line-height: 16px !important;
	font-weight: 700 !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-field-row {
	display: block;
	float: none;
	width: auto;
	margin: 0 0 0 0;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-field-row p {
	display: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-placeholder {
	padding: 10px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column .charitable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}

/* field: social sharing AND linking */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-row .charitable-campaign-social-link,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row .charitable-campaign-social-link {
	border: 1px solid <?php echo charitable_sanitize_hex_color( $tertiary ); ?>;
	border-radius: 40px;
	padding: 10px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
	border: 1px solid rgba(0, 0, 0, 0.20);
	border-radius: 10px;
	display: table;
	width: 100%;
	padding: 15px;
}


/* field: campaign summary */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div {
	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div span {
	font-weight: 100 !important;
	font-size: 32px !important;
	line-height: 38px !important;
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?> !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	color: #D8DAD7;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item.campaign_hide_time_remaining {
	width: 100%;
}

/* field: donate amount */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-campaign-field.charitable-campaign-field-donate-amount .charitable-template-donation-amount.selected {
	border-color: <?php echo charitable_sanitize_hex_color( $tertiary ); ?> !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-donate-amount label,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo charitable_sanitize_hex_color( $primary ); ?>;
	border-color: <?php echo charitable_sanitize_hex_color( $primary ); ?> !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
	border-color: <?php echo charitable_sanitize_hex_color( $tertiary ); ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-field.charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo charitable_sanitize_hex_color( $primary ); ?>;
}

/* field: donate form */


/* field: shortcode */


/* tabs: container */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article {
	background-color: <?php echo charitable_sanitize_hex_color( $tertiary ); ?>;
	padding: 25px;
}

/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article {
	padding: 30px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
	border: 1px solid <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	width: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
	border-top: 0;
	border-right: 1px solid <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	border-bottom: 0;
	border-left: 0;
	margin: 0;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {

	display: block;
	font-weight: 500 !important;
	font-size: 17px !important;
	line-height: 17px !important;
	text-transform: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {
	background-color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
	border: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
	color: black;
}

/* tabs: style */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
	background-color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
	color: white;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
	background-color: <?php echo charitable_sanitize_hex_color( $secondary ); ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
	color: white;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li a {

}

/* tabs: sized */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li a {

}

/* field: donor wall */


/* field: organizer */

// @codingStandardsIgnoreEnd