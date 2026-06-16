<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Save the Museum campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#2B2B2B'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#F5F5F5'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#FFFFFF'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#2B2B2B'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug            = 'save-the-museum';
$charitable_wrapper         = '.charitable-campaign-wrap.template-' . $charitable_slug;
$charitable_preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $charitable_slug;

require_once '../../admin/campaign-builder/templates/functions-campaign-templates.php';
?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> {
	font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row {
	background-color: white;
	color: #5B5B5B;
	padding: 0 35px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
	background-color: white;
	color: #5B5B5B;
}

/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	border: 0;
	flex: 1;
	padding: 50px 0 0 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) {
	flex: 1.5;
	border: 0;
}

/* headlines */

<?php echo $charitable_wrapper; // phpcs:ignore ?> h5.charitable-campaign-field-headline {
	color: <?php echo $secondary; // phpcs:ignore ?>;
	font-weight: 500;
	text-transform: uppercase;
	font-size: 21px;
	line-height: 23px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .tab-content h5.charitable-field-template-headline {
	color: black;
	font-weight: 500;
	text-transform: inherit;
	font-size: 32px;
	line-height: 38px;
	margin-bottom: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row h5 {
	font-size: 42px;
	line-height: 50px;
	font-weight: 500;
	letter-spacing: inherit;
}

/* field: campaign title */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-title h1.charitable-campaign-title {
	margin: 5px 0 5px 0;
  color: <?php echo $secondary; // phpcs:ignore ?> !important;
	font-size: 68px !important;
	line-height: 72px !important;
	font-weight: 500 !important;
	font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

/* field: campaign description */

<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text,
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text p {
	font-size: 18px;
	line-height: 27px;
	font-weight: 300;
	color: #5B5B5B;
}

/* field: button */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button button.button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.donate-button {
	border-radius: 0px;
	background-color: transparent !important;
	border: 1px solid black !important;
  color: <?php echo charitable_get_constracting_text_color($button); // phpcs:ignore ?> !important;
	display: flex; /* Changed from block to flex */
	align-items: center; /* Vertically centers the text */
	justify-content: center; /* Optionally centers the text horizontally too */
	text-align: center !important;
	text-decoration: none !important;
	transition: filter 0.3s; /* Smooth transition */
	width: 100%;
	font-weight: 400;
	min-height: 50px;
	font-size: 16px;
	line-height: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button button.button:hover,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.donate-button:hover {
  background-color: <?php echo $button; // phpcs:ignore ?> !important;
}

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
	margin-top: 10px;
	margin-bottom: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking {
	display: flex;
	flex-wrap: wrap;
	flex-direction: row;
	justify-content: start;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking .charitable-field-row.charitable-field-row-social-linking {
	width: auto !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-template-social-linking-headline-container  {
	float: left;
	display: table-cell;
	vertical-align: middle;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking-headline-container h5 {
	margin-right: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row {
	display: flex;
	flex-wrap: wrap;
	flex-direction: row;
	align-items: center;
	justify-content: start;
	gap: 10px;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row p {
	display: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking h5.charitable-field-template-headline {
	font-size: 14px;
	line-height: 16px;
  color: <?php echo $secondary; // phpcs:ignore ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-placeholder {
	padding: 0px;
	display: flex;
	align-items: center;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-placeholder .charitable-field-template-headline {
	margin-top: 6px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-social-field-column {
	float: none !important;
	margin: 0 !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-field-column .charitable-campaign-social-link {
	min-height: 10px !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}


/* field: social sharing */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing {
	margin-top: 10px;
	margin-bottom: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing {
	display: flex;
	flex-wrap: wrap;
	flex-direction: row;
	justify-content: start;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-template-social-sharing-headline-container   {
	float: left;
	display: table-cell;
	vertical-align: middle;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing-headline-container  h5 {
	margin-right: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row {
	display: flex;
	flex-wrap: wrap;
	flex-direction: row;
	align-items: center;
	justify-content: start;
	gap: 10px;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row p {
	display: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
	font-size: 14px;
	line-height: 16px;
  color: <?php echo $secondary; // phpcs:ignore ?>;
	font-weight: 300;
	margin: 0 15px 0 0;
	padding: 5px 5px 5px 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-placeholder {
	padding: 0px;
	display: flex;
	align-items: center;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-placeholder .charitable-field-template-headline {
	margin-top: 6px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column {
	float: none !important;
	margin: 0 !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-field-column .charitable-campaign-social-link {
	min-height: 10px !important;
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
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item span {
    color: <?php echo $primary; // phpcs:ignore ?>;
	font-weight: 500;
	font-size: 32px;
	line-height: 38px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
    color: <?php echo $secondary; // phpcs:ignore ?>;
	text-align: left;
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

/* field: donate amount */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount label,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
  color: <?php echo $secondary; // phpcs:ignore ?>;
  border: 1px solid <?php echo $secondary; // phpcs:ignore ?> !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
  color: <?php echo $tertiary; // phpcs:ignore ?>;
}

/* tabs: container */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-tab-container {
  background-color: <?php echo $tertiary; // phpcs:ignore ?>;
}

/* tabs: tab nav */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
	padding: 30px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
    border: 1px solid <?php echo $primary; // phpcs:ignore ?>;
    background-color: <?php echo $primary; // phpcs:ignore ?>;
	width: auto;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
	border-top: 0;
    border-right: 1px solid <?php echo $primary; // phpcs:ignore ?>;
	border-bottom: 0;
	border-left: 0;
	background-color: transparent;
	margin: 0;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
	color: white;
	display: block;
	font-weight: 500 !important;
	font-size: 14px !important;
	line-height: 15px !important;
	text-transform: none !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li:hover {
    background-color: <?php echo $primary; // phpcs:ignore ?>;
	text-decoration: none;
	filter: brightness(90%);
	border: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
	color: white;
}


/* tabs: style */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {

}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active a {

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


<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container {
	container-type: inline-size;
  container-name: campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area;
}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 700px) {
  <?php echo $charitable_wrapper; // phpcs:ignore ?>  #charitable-template-row-0 {
	display: flex
;    flex-direction: column;
	flex-flow: column-reverse;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	padding: 0 0 0 0;
	}
  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
	padding-bottom: 0;
	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav {
		margin-top: 0px;
		margin-bottom: 0px;
	}
  <?php echo $charitable_wrapper; // phpcs:ignore ?>  .section[data-section-type="tabs"] article .tab-content > ul li {

	}
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .charitable-tabs {
		margin-top: 0px;
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