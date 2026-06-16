<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Animal Sanctuary campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#805F93'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#1D1C1C'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#808080'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#805F93'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug            = 'animal-sanctuary';
$charitable_wrapper         = '.charitable-campaign-wrap.template-' . $charitable_slug;
$charitable_preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $charitable_slug;

require_once '../../../../../includes/admin/campaign-builder/templates/functions-campaign-templates.php';

?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $charitable_wrapper; // phpcs:ignore ?> {
	font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

/* aligns */


/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	flex: 1;
	flex-direction: column;
	display: flex;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) {
	flex: 1 1 40%;
	flex-direction: column;
	display: flex;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) .charitable-campaign-field {
	margin-top: 0;
	margin-bottom: 0;
}


/* headlines in general */

<?php echo $charitable_wrapper; // phpcs:ignore ?> h5.charitable-field-template-headline,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-title,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-headline {
	color: <?php echo $primary; // phpcs:ignore ?>;
}

/* field: campaign title */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field_campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; // phpcs:ignore ?> !important;
	font-size: 68px !important;
	line-height: 72px !important;
	font-weight: 500 !important;
	word-wrap: anywhere;
}


/* field: campaign description */


/* field: campaign text */


/* field: html */


/* field: button */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button button.button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.button {
	background-color: <?php echo $button; // phpcs:ignore ?> !important;
	border-color: <?php echo $button; // phpcs:ignore ?> !important;
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
	color: <?php echo charitable_get_constracting_text_color($button); // phpcs:ignore ?>;
	transition: filter 0.3s; /* Smooth transition */
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button button.button:hover,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.button:hover {
	filter: brightness(0.8);
}

/* field: photo */


/* field: photo */


/* field: progress bar */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: <?php echo $secondary; // phpcs:ignore ?>;
	font-size: 21px;
	line-height: 21px;
	font-weight: 100;
	padding-left: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo $primary; // phpcs:ignore ?>;
	font-weight: 100;
	font-size: 21px;
	line-height: 21px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 0px;
	margin-top: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: <?php echo $primary; // phpcs:ignore ?>;
	height: 13px !important;
	border-radius: 0px;
	text-align: right;
	opacity: 1.0;
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
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-template-social-linking-headline-container  {
	float: none;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking-headline-container h5 {
	margin-right: 10px;
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
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-placeholder {
	padding: 0px;
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
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container   {
	float: none;
	display: table;
	vertical-align: middle;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing-headline-container h5.charitable-field-template-headline {
	color: <?php echo $secondary; // phpcs:ignore ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px !important;
	line-height: 16px !important;
	font-weight: 700 !important;
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
	margin: 0 0 10px 0;
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
	width: calc(100% - 30px);
	padding: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column .charitable-campaign-social-link img {
	max-width: 100% !important;
}

/* field: campaign summary */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div {
	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div span {
	color: black;
	font-weight: 700;
	font-size: 32px;
	line-height: 38px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	color: black;
	text-align: left;
	font-size: 14px;
	line-height: 17px;
	text-transform: capitalize;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
	width: 100%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-campaign-summary  div.campaign-summary-item {
	color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-campaign-summary  div.campaign-summary-item span {
	color: <?php echo $secondary; // phpcs:ignore ?>;
}

/* field: donate amount */

<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field.charitable-campaign-field-donate-amount .charitable-template-donation-amount.selected {
	border-color: <?php echo $tertiary; // phpcs:ignore ?> !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-amount label,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo $primary; // phpcs:ignore ?>;
	border-color: <?php echo $primary; // phpcs:ignore ?> !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
	border-color: <?php echo $tertiary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo $primary; // phpcs:ignore ?>;
}

/* field: donate form */


/* field: shortcode */


/* tabs: container */


/* tabs: tab nav */


/* tabs: style */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li a {

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

/* field: donor wall */


/* field: organizer */

.charitable-preview.charitable-builder-template-<?php echo $charitable_slug; // phpcs:ignore ?> {

}
