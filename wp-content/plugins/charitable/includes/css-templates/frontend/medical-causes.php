<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Medical Causes campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#192E45'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#215DB7'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#48A9F5'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#48A9F5'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug            = 'medical-causes';
$charitable_wrapper         = '.charitable-campaign-wrap.template-' . $charitable_slug;
$charitable_preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $charitable_slug;

require_once ('../../admin/campaign-builder/templates/functions-campaign-templates.php');
?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

/* this narrows things down a little to the preview area header/tabs */


/* headings, headlines */


<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row h5.charitable-field-template-headline {
	color: white;
	font-weight: 400 !important;
	font-size: 64px !important;
	line-height: 64px !important;
	margin-top: 0px;
	margin-bottom: 20px;
  margin-left: -1px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row .tab-content h5.charitable-field-template-headline {
	color: black !important;
	font-weight: 500 !important;
	text-transform: inherit;
	font-size: 32px !important;
	line-height: 38px !important;
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> #charitable-template-row-0 h1.charitable-campaign-title {
  color: white;
}


/* row specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row {
	background-color: <?php echo $secondary; // phpcs:ignore ?>;
	padding: 50px;
	color: white;
	padding: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row > * {
	/* color: <?php echo $secondary; // phpcs:ignore ?>; */
	font-size: 14px;
	line-height: 24px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row.no-padding,
<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row.no-padding .charitable-campaign-column {
	padding: 0 !important;
}

/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	flex: 2;
	border: 0;
	/* padding-top: 50px; */
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) {
	border: 0;
	flex: 1 1 26%;
	padding-top: 15px;
	padding-bottom: 15px;
}

/* section specifics */

/* header */

/* field: campaign title */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-campaign-title h1 {
  margin: 5px 0 5px 0;
  font-size: 55px !important;
  line-height: 58px !important;
  font-weight: 100 !important;
  word-wrap: anywhere;
}

/* field: campaign description */
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
}

/* field: button */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.button {
	background-color: <?php echo $button; // phpcs:ignore ?> !important;
	border-color: <?php echo $button; // phpcs:ignore ?> !important;
  color: <?php echo charitable_get_constracting_text_color($button); // phpcs:ignore ?>;
  text-transform: uppercase;
  border-radius: 0px;
  margin-top: 0;
  margin-bottom: 0;
  font-weight: 400;
  min-height: 50px;
  height: 50px;
  font-size: 16px;
  line-height: 15px;
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


/* field: photo */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-photo img {
  width: 100%;
  border: 5px solid <?php echo $primary; // phpcs:ignore ?>;
}

/* field: text */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="fields"] .charitable-campaign-field-text {
  color: white;
}

/* field: summary */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary {
  padding-left: 0;
  padding-right: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div {
  font-weight: 400 !important;
  font-size: 14px !important;
  line-height: 16px !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div span {
  font-weight: 100 !important;
  font-size: 32px !important;
  line-height: 38px !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item {
  border: 0;
  margin-top: 5px;
  margin-bottom: 5px;
  text-transform: capitalize;
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

<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="fields"] .charitable-field-template-campaign-summary div span,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="fields"] .charitable-field-template-campaign-summary .campaign-summary-item {
  color: white;
}



/* field: progress bar */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
  color: #FFFFFF;
  font-size: 21px;
  line-height: 21px;
  font-weight: 100;
  padding-left: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
  color: white;
  font-weight: 100;
  font-size: 21px;
  line-height: 21px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
  border: 0;
  padding: 0;
  background-color: #E0E0E0;
  border-radius: 20px;
  margin-top: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
  background-color: <?php echo $tertiary; // phpcs:ignore ?>;
  height: 13px !important;
  border-radius: 20px;
  text-align: right;
  opacity: 1.0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
  display: none;
}

/* field: donate amount */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-donate-amount label,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-donate-amount input.custom-donation-input[type="text"] {
  color: <?php echo $secondary; // phpcs:ignore ?>;
  border: 1px solid <?php echo $secondary; // phpcs:ignore ?> !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
  color: <?php echo $tertiary; // phpcs:ignore ?>;
}

/* field: social linking */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-linking {
  display: table;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-linking .charitable-field-preview-social-linking-headline-container {
  display: block;
  float: left;
  padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-linking .charitable-field-row {
  display: block;
  float: left;
  width: auto;
  margin: 0 0 0 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-linking h5.charitable-field-template-headline {
  font-size: 14px;
  line-height: 16px;
  color: <?php echo $secondary; // phpcs:ignore ?>;
  font-weight: 300;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-linking .charitable-placeholder {
  padding: 10px;
}

/* field: social sharing */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-sharing {
  display: table;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {
  display: block;
  float: left;
  padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-sharing .charitable-field-row {
  display: block;
  float: left;
  width: auto;
  margin: 0 0 0 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-sharing h5.charitable-field-template-headline {
  font-size: 14px;
  line-height: 16px;
  color: <?php echo $secondary; // phpcs:ignore ?>;
  font-weight: 300;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-preview-social-sharing .charitable-placeholder {
  padding: 10px;
}

/* tabs: container */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
  background-color: white;
  padding-top: 20px;
  padding-bottom: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul li {
    padding-top: 20px;
    padding-bottom: 25px;
}

/* tabs: tab nav */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
  width: auto;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul {
    margin-top: 30px !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
    margin-left: 30px !important;
    margin-right: 30px !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
  border: 1px solid <?php echo $secondary; // phpcs:ignore ?>;
  background-color: transparent;
  margin: 0 15px 0 0;
  padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
  display: block;
  font-weight: 500 !important;
  font-size: 14px !important;
  line-height: 15px !important;
  text-transform: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {
  background-color: <?php echo $secondary; // phpcs:ignore ?>;
}


/* tabs: style */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: black;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
	background-color: <?php echo $secondary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
	background-color: <?php echo $secondary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
	color: white;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {
	background-color: transparent;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {
	color: black;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {
	background-color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {
	background-color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active a {
	color: white;
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
	<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:first-of-type {
		padding: 0 !important;
		max-width: 100%;
	}
  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row {
    padding: 10px 30px;
  }
  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
    padding-top: 0;
    padding-bottom: 0;
  }
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .section[data-section-type="tabs"] article nav {
		margin-top: 0px;
		margin-bottom: 0px;
	}
  <?php echo $charitable_wrapper; // phpcs:ignore ?>  .section[data-section-type="tabs"] article .tab-content > ul li {
    margin-top: 0;
    padding-top: 0 !important;
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