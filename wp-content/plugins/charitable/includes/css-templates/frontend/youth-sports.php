<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Club Organization campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

require_once '../../admin/campaign-builder/templates/functions-campaign-templates.php';

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

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#3418d2'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#005eff'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#00a1ff'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#ec5f25'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug    = 'youth-sports';
$charitable_wrapper = '.charitable-campaign-wrap.template-' . $charitable_slug;

require_once '../../admin/campaign-builder/templates/functions-campaign-templates.php';

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> {
	font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> * {
	font-family: inherit !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row {
	background-color: <?php echo $primary; // phpcs:ignore ?>;
	padding: 30px;
	color: white;
}


<?php echo $wrapp; // phpcs:ignore ?>  div.charitable-campaign-row .section[data-section-type="fields"] .charitable-campaign-field {
	margin-top: 10px;
	margin-bottom: 10px;
}

/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	flex: 1;
	border: 0;
	padding-top: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) {
	border: 0;
	flex: 1;
	padding-top: 0;
	padding-bottom: 0;
}

/* section specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
	color: white;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) * {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) .charitable-field-section {
	background-color: transparent;

}

/* headlines */

<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row h5.charitable-field-template-headline {
	font-weight: 500;
	font-size: 14px;
	line-height: 18px;
	text-transform: uppercase;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-preview-row h5.charitable-field-template-headline {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .tab-content h5.charitable-field-template-headline {
	color: black !important;
	font-weight: 700 !important;
	text-transform: inherit;
	font-size: 42px !important;
	line-height: 48px !important;
	margin-top: 20px;
	margin-bottom: 20px;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-header h5.charitable-field-template-headline {
	color: <?php echo $primary; // phpcs:ignore ?> !important;
	font-weight: 500 !important;
	text-transform: inherit;
	font-size: 42px !important;
	line-height: 48px !important;
	margin-top: 20px;
	margin-bottom: 20px;
}

/* field: campaign title */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field_campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; // phpcs:ignore ?>;
	font-weight: 600;
	font-size: 42px;
	line-height: 60px;
	text-transform: uppercase;
	word-wrap: anywhere;
}

/* field: campaign description */

<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: <?php echo $tertiary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 15px;
	line-height: 28px;
	font-weight: 400;
}

/* field: button */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.donate-button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> button.charitable-button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> a.charitable-button {
	background-color: <?php echo $button; // phpcs:ignore ?> !important;
	border-color: <?php echo $button; // phpcs:ignore ?> !important;
	color: <?php echo esc_attr( charitable_get_constracting_text_color( $button ) ); ?>;
	text-transform: none;
	border-radius: 0px;
	text-transform: uppercase;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	height: 50px;
	font-size: 16px;
	line-height: 16px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> a.charitable-button.donate-button {
	font-size: 16px;
	line-height: 36px;
		display: inline-block;
		text-align: center;
		text-decoration: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.donate-button {
	border-radius: 0px;
	text-transform: uppercase;
	border-radius: 0px;
	margin-top: 0;
	margin-bottom: 0;
	font-weight: 400;
	min-height: 50px;
	height: 50px;
	font-size: 16px;
	line-height: 15px;
	background-color: <?php echo $button; // phpcs:ignore ?> !important;
	border-color: <?php echo $button; // phpcs:ignore ?> !important;
	display: flex; /* Changed from block to flex */
	align-items: center; /* Vertically centers the text */
	justify-content: center; /* Optionally centers the text horizontally too */
	text-align: center !important;
	text-decoration: none !important;
	transition: filter 0.3s; /* Smooth transition */
}

/* field: progress bar */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	font-size: 28px;
	line-height: 18px;
	font-weight: 500;
	text-transform: uppercase;
	padding-left: 0;
	text-align: left;
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row {
	font-size: 14px;
	line-height: 21px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	font-weight: 500;
	font-size: 14px;
	line-height: 18px;
	text-transform: uppercase;
	text-align: right;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	border-radius: 0;
	margin-top: 25px;
	height: 10px;
	overflow: hidden;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	height: 18px;
	border-radius: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
	display: none;
}

/* field: social linking */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking {
	display: table;
	padding: 0;
	margin-top: 40px;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-template-social-linking-headline-container  {
	float: left;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking-headline-container h5 {
	margin-right: 10px !important;
	font-weight: 400 !important;
	font-size: 18px !important;
	line-height: 24px !important;
	color: white !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row p {
	display: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking h5.charitable-field-template-headline {
	color: <?php echo $secondary; // phpcs:ignore ?>;
	margin: 0 30px 10px 0 !important;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-placeholder {
	padding: 0 10px 0 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column .charitable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}


/* field: social sharing */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing {
	display: table;
	padding: 0;
	margin-top: 40px;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container   {
	float: left;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing-headline-container  h5 {
	margin-right: 10px !important;
	font-weight: 400 !important;
	font-size: 18px !important;
	line-height: 24px !important;
	color: white !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row {
	display: block;
	float: none;
	width: auto;
	margin: 0 0 0 0;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row p {
	display: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
	color: <?php echo $secondary; // phpcs:ignore ?>;
	margin: 0 20px 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-placeholder {
	padding: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column .charitable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}

/* field: social sharing AND linking */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-row .charitable-campaign-social-link,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row .charitable-campaign-social-link {
	border: 1px solid <?php echo $tertiary; // phpcs:ignore ?>;
	border-radius: 40px;
	padding: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
	border: 1px solid rgba(0, 0, 0, 0.20);
	border-radius: 10px;
	display: table;
	width: 100%;
	padding: 0px;
}

/* field: social sharing AND linking */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-row .charitable-campaign-social-link,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row .charitable-campaign-social-link {
	border: 0;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
	border: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking img,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing img {
	height: 20px !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field.charitable-field-social-links .charitable-field-row .charitable-placeholder,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field.charitable-field-social-sharing .charitable-field-row .charitable-placeholder {
	padding: 10px;
}

/* field: campaign summary */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div {

	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div span {
	color: white;
	font-weight: 100;
	font-size: 32px;
	line-height: 38px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div.campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	text-transform: capitalize;
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
	width: 100%;
}

/* field: donate amount */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-template-donation-amounts {
	display: flex;
	flex-wrap: wrap;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-template-donation-amount {
	margin-right: 10px; /* Adjust spacing between items */
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .custom-donation-amount {
	flex-basis: 100%;
	margin-top: 10px; /* Adjust space above if needed */
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .custom-donation-amount input.custom-donation-input[type="text"] {
	border-color: white !important;
	color: white !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .custom-donation-amount.selected {
	border: none !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .custom-donation-amount.selected input.custom-donation-input[type="text"] {
	border: 2px solid <?php echo $button; // phpcs:ignore ?> !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field.charitable-campaign-field-donate-amount .charitable-template-donation-options ul.charitable-template-donation-amounts .charitable-template-donation-amount.selected {
	border: 2px solid <?php echo $button; // phpcs:ignore ?> !important;
}


/* field: shortcode */

<?php echo $charitable_wrapper; // phpcs:ignore ?>  .shortcode-campaign {
padding-left: 10px;
padding-right: 10px;
}

/* tabs: container */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {

}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content img {
max-width: 100%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
margin-left: 0;
margin-right: 0;
}

/* tabs: container */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-preview-tab-container {
	background-color: white;
}

/* tabs: tab nav */
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
padding: 0 50px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav {
	width: auto;
	margin-left: 0;
	margin-right: 0;
	margin-bottom: 40px;
	margin-top: 40px;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav li {
	border-top: 0;
	border-right: 0;
	border-bottom: 0;
	border-left: 0;
	margin: 0 25px 0 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav li a {
	display: block;
	font-weight: 400;
	font-size: 14px;
	line-height: 15px;
	text-transform: uppercase;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav li.active a {
	font-weight: 600;
}

/* tabs: style */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li {
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover {

	border-bottom: 2px solid;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover a {
	color: white;
	opacity: 1 !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li a {
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active:hover a {
	color: black;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active {
	border-bottom: 2px solid <?php echo $button; // phpcs:ignore ?> !important;
}

/* tabs: sized */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li {
	font-size:10px;
	padding:0
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-small li a {
	font-size:16px;
	padding:18px
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li {
	font-size:14px;
	padding:0
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-medium li a {
	font-size:21px;
	padding:23px
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li {
	font-size:21px;
	padding:0
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-size-large li a {
	font-size:30px;
	padding:32px
}


<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container {
	container-type: inline-size;
  container-name: campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area;
}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 700px) {
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container #charitable-template-row-0 .charitable-campaign-primary-image {
		margin-left: 0;
		margin-right: 0;
		width: 100%;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container #charitable-template-row-0 .charitable-campaign-primary-image img {
		width: 100%;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .charitable-template-donation-amounts {
		flex-wrap: wrap;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .charitable-template-donation-amounts li {
		flex: 0 0 calc(50% - 10px); /* 50% width minus half of the gap */
		/* Alternative without gap: flex: 0 0 50%; */

		/* Optional: Add some styling */
		min-height: 100px;
		box-sizing: border-box;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container input.custom-donation-input {
		min-height: 40px;
		width: calc(100% - 5px) !important;
		text-indent: 10px;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav {
		margin-top: 20px;
		margin-bottom: 0px;
	}
	.charitable-campaign-wrap .charitable-campaign-column,
		.charitable-campaign-wrap .charitable-campaign-column:nth-child(even),
		.charitable-campaign-wrap .charitable-campaign-column:nth-child(odd) {
		flex: 0 0 100% !important;
		padding-top: 0;
		padding-bottom: 0;
		padding-left: 0;
		padding-right: 0;
		}
}

.charitable-preview.charitable-builder-template-<?php echo $charitable_slug; // phpcs:ignore ?> {
	// ... preview styles ...
}