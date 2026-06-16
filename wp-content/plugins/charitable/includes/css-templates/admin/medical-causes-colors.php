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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#192E45';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#215DB7';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#48A9F5';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#48A9F5';

$slug    = 'medical-causes'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

require_once '../../admin/campaign-builder/templates/functions-campaign-templates.php';
?>

/* this narrows things down a little to the preview area div.charitable-preview-row/tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
/* field items in preview area */
}

/* wide spread changes in div.charitable-preview-row vs tabs */


<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row {
	background-color: <?php echo $secondary; ?>;

	color: white;
}


<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h6 {
	color: white;
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

}

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .placeholder {
	padding: 0;
}

/* aligns */


/* column specifics */

/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5.charitable-field-preview-headline {
	color: white;

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5.charitable-field-preview-headline {
	color: black !important;

}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-title h1 {

	color: <?php echo $secondary; ?>;


/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {

	color: #D8DAD7;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .tab-content .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {

	color: black;

}

/* field: text */



/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
background-color: <?php echo $button; ?> !important;
border-color: <?php echo $button; ?> !important;
color: <?php echo charitable_get_constracting_text_color( $button ); ?>;
}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image {
	border: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo img {
	border: 5px solid <?php echo $primary; ?>;
}

/* field: photo */


/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #FFFFFF;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: white;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {

	background-color: #E0E0E0;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
	background-color: <?php echo $tertiary; ?>;

}


/* field: social linking */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {

	color: <?php echo $secondary; ?>;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-placeholder {
	padding: 0px;
}
/* field: social sharing */


<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {
	font-size: 14px;
	line-height: 16px;
	color: <?php echo $secondary; ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {
	padding: 0px;
}

/* social icons */

<?php
// Include shared social icons CSS with dark icon style.
$icon_style = 'dark';
include dirname( __FILE__ ) . '/partials/social-icons.php';
?>

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
	color: white;
	font-weight: 100;
	font-size: 32px;
	line-height: 38px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	text-transform: capitalize;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
	width: 100%;
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
	background-color: white;
}

/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
	width: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
	border: 1px solid <?php echo $secondary; ?>;
	background-color: transparent;
	margin: 0 15px 0 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
	color: black;
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

/* missing addons? */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-video,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-charitable-videos {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-video .charitable-missing-addon-content,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-charitable-videos .charitable-missing-addon-content {
	background-color: transparent;
}
// @codingStandardsIgnoreEnd