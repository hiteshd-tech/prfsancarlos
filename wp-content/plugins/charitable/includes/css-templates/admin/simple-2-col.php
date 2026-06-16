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

$slug              = 'simple-2-col'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper           = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper_no_fields = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap.no-fields-mode .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

	font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
	/* field items in preview area */
	font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field {
	display: flex;
}

/* wide spread changes in header vs tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> > header {
	margin-bottom: 40px;
	font-size: 40px;
	line-height: 70px;
	font-weight: 600;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> h1:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> h2:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> h3:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> h4:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> h5:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> h6:empty {
	display: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h6 {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h6 {

}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content > * {
	color: black;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> header h5 {
	font-size: 18px;
	line-height: 21px;
	font-weight: 500;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .placeholder {
	padding-left: 0;
	padding-right: 0;
	padding-top: 0;
	padding-bottom: 0;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> > .charitable-preview-header .charitable-field-wrap.charitable-field-target{
	min-height: 150px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> > .charitable-preview-row .charitable-field-wrap.charitable-field-target {
	min-height: 600px;
}

/* aligns */

<?php
/*
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-left > div {
	margin-left: 0;
	margin-right: auto;
	display: table;
	width: 100%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center > div {
	margin-left: auto;
	margin-right: auto;
	display: table;
	width: 100%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right > div {
	margin-left: auto;
	margin-right: 0;
	display: table;
	width: 100%;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-left div.charitable-field-campaign-title,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center div.charitable-field-campaign-title,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right div.charitable-field-campaign-title {
	width: auto;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-left .donation-wall h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-left .donation-wall ol {
	text-align: left;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center .donation-wall h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center .donation-wall ol {
	text-align: center;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right .donation-wall h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right .donation-wall ol {
	text-align: right;
} */
?>

/* column specifics */

<?php
/*
echo $wrapper; ?> .column[data-column-id="0"] {
	flex: 1;
	border: 0;
	padding-left: 0;
	padding-top: 0;
	padding-bottom: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="0"] .charitable-field-photo {
	padding: 0;
	margin: 0;
	border: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="1"] {
	flex: 1;
	border: 0;
} */
?>

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-panel-fields .charitable-field-wrap {
	padding-bottom: 0;
}

/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  h5.charitable-field-preview-headline {

}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-title h1 {
	margin: 15px auto;
	font-size: 72px;
	line-height: 84px;
	font-weight: 600;
}

/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-campaign-description h5.charitable-field-preview-headline {
	font-size: 27px;
	line-height: 29px;
	font-weight: 500;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	margin: 0;
	color: #000000;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-campaign-builder-no-description-preview div {
	margin: 0;
	float: none;
}


/* field: text */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #202020;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-text h5.charitable-field-preview-headline {

}

/* field: html */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-html .placeholder {
	padding: 0;
	margin: 0;
}

/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder {
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
	text-transform: none;
	border-radius: 0px;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	min-height: 50px;
	height: 50px;
	font-size: 16px;
	line-height: 50px;
}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image {
	border: transparent;
	border-radius: 0px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image img {
	max-width: 100%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .charitable-field.charitable-field-photo .primary-image img {
	border-radius: 0px;
}


<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-photo .charitable-preview-align-center .primary-image-container {
	text-align: center;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-photo .charitable-preview-align-left .primary-image-container {
	text-align: left;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-photo .charitable-preview-align-right .primary-image-container {
	text-align: right;
}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> header .primary-image-container {
	margin: 0;
	padding: 0;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .primary-image-container {
	margin: 0;
	padding: 0;
}

/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {

	font-weight: 400;
	font-size: 18px;
	line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
	font-weight: 400;
	font-size: 18px;
	line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 5px;
	margin-top: 15px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {

	height: 8px !important;
	border-radius: 5px;
	text-align: right;
	opacity: 1.0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
	display: inline-block;
	border-radius: 25px;
	width: 25px;
	height: 25px;
	margin-right: -15px;
	margin-top: -10px;
}

/* field: social linking */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking {
	display: table;
	width: auto !important;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-field-preview-social-linking-headline-container {
	display: block;
	float: left;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {
	font-size: 14px;
	line-height: 26px;
	font-weight: 300;
	margin: 0 20px 0 0;
	padding: 0;
	color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-placeholder {
	padding: 10px;
}

/* field: social sharing */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing {
	display: table;
	width: auto !important;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {
	display: block;
	float: left;
	padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
	font-size: 14px;
	line-height: 26px;
	font-weight: 300;
	margin: 0 20px 0 0;
	padding: 0;
	color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {
	padding: 10px;
}

/* field: campaign summary */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div {
	color: <?php echo $primary; ?>;
	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div span {
	color: <?php echo $secondary; ?>;
	font-weight: 600;
	font-size: 32px;
	line-height: 38px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
	color: <?php echo $primary; ?>;
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
}

/* field: donate amount */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount label,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
	border: 1px solid !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
	border: 10px solid;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {

}

/* field: donate form */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donation-form .charitable-form-placeholder {
	width: 100%;
	margin-left: auto;
	margin-right: auto;
	background-color: #f6f6f6;
	height: auto;
	max-height: auto;
	min-height: 200px;
	padding-top: 0;
	padding-bottom: 0;
	display: inline-block;
}

/* field: shortcode */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .shortcode-preview {
	padding-left: 10px;
	padding-right: 10px;
}

/* tabs: container */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-tab-container {
	background-color: transparent;
	margin-top: 0px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-tab-container .tab-content img {
	max-width: 100%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-tab-container .tab-content > ul {
	margin-left: 0;
	margin-right: 0;
}

/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
	border: 1px solid transparent;
	background-color: transparent;
	width: auto;
	margin-left: 0;
	margin-right: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
	border-top: 0;
	border-right: 0;
	border-bottom: 0;
	border-left: 0;
	background-color: transparent;
	margin: 0 10px 0 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
	color: black;
	display: block;
	text-transform: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active {
	background-color: <?php echo $primary; ?>;
	color: white;
	text-decoration: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li:hover {
	background-color: <?php echo $primary; ?>;
	color: white;
	text-decoration: none;
	filter: brightness(90%);
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active a,
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li:hover a {
	color: white;
}


/* tabs: style */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-boxed li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-rounded li a {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li {
	background-color: transparent;
	border-bottom: 1px solid;
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li a {
	border-top: 0;
	border-right: 0;
	border-left: 0;
}

/* tabs: sized */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-small li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-small li a {
	font-weight: 500;
						font-size: 14px;
						line-height: 16px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-medium li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-medium li a {
						font-weight: 500;
						font-size: 19px;
						line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-large li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-large li a {
	font-weight: 500;
	font-size: 26px;
						line-height: 28px;
}

// @codingStandardsIgnoreEnd