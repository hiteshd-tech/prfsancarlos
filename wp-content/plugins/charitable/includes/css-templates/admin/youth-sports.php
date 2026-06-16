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
 * @version 1.8.8.6
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
	margin-bottom: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-column .charitable-field:last-of-type {
	margin-bottom: 0;
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

/* row specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-row {
	padding: 35px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row.no-padding,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row.no-padding div.column {
	padding: 0 !important;
}

/* section specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-section {
	padding: 0;
}

/* column specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-row .charitable-field-column {
	padding-left: 15px;
	padding-right: 15px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-panel-fields .charitable-field-wrap {
	padding-bottom: 0;
}

/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  h5.charitable-field-preview-headline {
	font-weight: 500;
	font-size: 14px;
	line-height: 18px;
	text-transform: uppercase;
}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-title h1 {
	text-transform: uppercase;
	font-weight: 600;
	font-size: 64px;
	line-height: 71px;
	margin-top: 0;
	margin-bottom: 0;
}

/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-campaign-description h5.charitable-field-preview-headline {
font-size: 27px;
line-height: 29px;
font-weight: 600;
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
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button .button {
text-transform: none;
border-radius: 0px;
text-transform: uppercase;
margin-top: 0;
margin-bottom: 0;
width: 100%;
font-weight: 400;
height: 50px;
font-size: 16px;
line-height: 50px;
}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image {
	border: 5px solid <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image img {
	max-width: 100%;
	width: 100%;
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


/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	font-size: 28px;
	line-height: 24px;
	font-weight: 500;
	text-transform: uppercase;
	padding-left: 0;
	text-align: left;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
	font-weight: 500;
	font-size: 14px;
	line-height: 18px;
	text-transform: uppercase;
	text-align: right;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
	border: 0;
	padding: 0;
	border-radius: 0;
	margin-top: 25px;
	height: 10px;
	overflow: hidden;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
	height: 18px;
	border-radius: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar span {

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

<?php
/*
echo $wrapper; ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-twitter .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-twitter .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/twitter-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-facebook .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-facebook .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/facebook-dark.svg');
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-linkedin .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-linkedin .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/linkedin-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-instagram .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-instagram .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/instagram-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-pinterest .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-pinterest .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/pinterest-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-tiktok charitable-.placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-tiktok .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/tiktok-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-mastodon .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-mastodon .charitable-placeholder {
background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/mastodon-dark.svg');
} */
?>

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
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected label {
  border-width: 1px;
  border-style: solid;
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

/* tabs: container */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-tab-container {
	background-color: white;
}

/* tabs: tab nav */
<?php echo charitable_esc_attr_php( $wrapper ); ?> article {
  padding: 0 50px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
	width: auto;
  margin-left: 0;
  margin-right: 0;
  margin-bottom: 50px;
  padding: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
	border-top: 0;
  border-right: 0;
	border-bottom: 0;
	border-left: 0;
	margin: 0 25px 0 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
	display: block;
	font-weight: 400;
	font-size: 14px;
	line-height: 15px;
	text-transform: uppercase;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active a {
  font-weight: 600;
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
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li:hover {

  border-bottom: 2px solid;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li:hover a {
	color: black;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li.active {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li a {
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-style-minimum li.active {
	border-bottom: 2px solid;
}

/* tabs: sized */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-small li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-small li a {
	font-size: inherit;
	line-height: inherit;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-medium li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-medium li a {
	font-size: inherit;
	line-height: inherit;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-large li {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav.tab-size-large li a {
	font-size: inherit;
	line-height: inherit;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  article nav.tab-size-small li {
	font-size:10px;
	padding:0
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  article nav.tab-size-small li a {
	font-size:16px;
	padding:18px
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  article nav.tab-size-medium li {
	font-size:14px;
	padding:0
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  article nav.tab-size-medium li a {
	font-size:21px;
	padding:23px
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  article nav.tab-size-large li {
	font-size:21px;
	padding:0
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  article nav.tab-size-large li a {
	font-size:30px;
	padding:32px
}


/* missing addons? */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing {
    background-color: rgba(0,0,0,.7);
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-missing .charitable-missing-addon-content {
    background-color: transparent;
    vertical-align: middle;
    display: table-cell;
	border: 0;
}

// @codingStandardsIgnoreEnd