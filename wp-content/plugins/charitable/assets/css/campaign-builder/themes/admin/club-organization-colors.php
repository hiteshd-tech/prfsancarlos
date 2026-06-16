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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#3E4735';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#B49A5F';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#F4F0EE';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#B49A5F';

$slug    = 'club-organization'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

require_once '../../../../../includes/admin/campaign-builder/templates/functions-campaign-templates.php';
?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

	font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area div.charitable-preview-row/tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
	/* field items in preview area */
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field {
	display: flex;
}
/* wide spread changes in div.charitable-preview-row vs tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row {
	background-color: <?php echo $primary; ?>;
	color: #606060;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row.no-padding,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row.no-padding div.column {
	padding: 0 !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h6 {
	color: <?php echo $secondary; ?>
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h6 {
	color: black;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content {
	color: <?php echo $tertiary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content > * {
	color: #92908F;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row > * {
	color: #D8DAD7;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5 {
	font-size: 24px;
	line-height: 28px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .placeholder {
	padding: 0;
}

/* aligns */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-left > div {
	margin-left: 0;
	margin-right: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center > div {
	margin-left: auto;
	margin-right: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right > div {
	margin-left: auto;
	margin-right: 0;
}

/* column specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="1"] {
	padding: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="0"] {

}


/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5.charitable-field-preview-headline {
	color: <?php echo $secondary; ?>;
	font-weight: 500;
	text-transform: uppercase;
	font-size: 21px;
	line-height: 23px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5.charitable-field-preview-headline {
	color: black;
	font-weight: 500;
	text-transform: inherit;
	font-size: 21px;
	line-height: 23px;
}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; ?>;
	font-size: 55px;
	line-height: 58px;
	font-weight: 100;
}

/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
}


/* field: text */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-text h5.charitable-field-preview-headline {

}


/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
	background-color: <?php echo $button; ?> !important;
	border-color: <?php echo $button; ?> !important;
	color: <?php echo charitable_get_constracting_text_color( $button ); ?>;
	text-transform: uppercase;
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

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row .primary-image-container {
	margin: 0;
	padding: 0;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .primary-image-container {
	margin: 0;
	padding: 0;
}

/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #FFFFFF;
	font-size: 21px;
	line-height: 21px;
	font-weight: 100;
	padding-left: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo $secondary; ?>;
	font-weight: 100;
	font-size: 21px;
	line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 0px;
	margin-top: 15px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
	background-color: <?php echo $secondary; ?>;
	height: 13px !important;
	border-radius: 0px;
	text-align: right;
	opacity: 1.0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
	display: none;
}

/* field: social linking */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking {
	display: table;
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
	line-height: 16px;
	color: <?php echo $secondary; ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-placeholder {
	padding: 10px;
}

/* field: social sharing */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing {
	display: table;
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
	line-height: 16px;
	color: <?php echo $secondary; ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
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

	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div span {
	color: <?php echo $secondary; ?>;
	font-weight: 100;
	font-size: 32px;
	line-height: 38px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
}


/* field: donate amount */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount label,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo $secondary; ?>;
	border: 1px solid <?php echo $secondary; ?> !important;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
	background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo $tertiary; ?>;
}

/* tabs: container */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-tab-container {
	background-color: <?php echo $tertiary; ?>;
}

/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
	border: 1px solid <?php echo $secondary; ?>;
	width: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
	border-top: 0;
	border-right: 1px solid <?php echo $secondary; ?>;
	border-bottom: 0;
	border-left: 0;
	background-color: transparent;
	margin: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
	color: <?php echo $secondary; ?>;
	display: block;
	font-weight: 500;
	font-size: 14px;
	line-height: 15px;
	text-transform: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active {
	background-color: <?php echo $secondary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active a {
	color: white;
}
// @codingStandardsIgnoreEnd