<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for School Trip campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#F58A07'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#1D3444'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#5B5B5B'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#F58A07'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug            = 'school-trip';
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

/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	flex: 2;
	border: 0;
	padding-top: 50px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) {
	border: 0;
	flex: 1 1 26%;
	padding-top: 15px;
	padding-bottom: 15px;
}

/* section specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
	color: white;
	padding: 35px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) * {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) .charitable-field-section {
	background-color: transparent;

}

/* headlines */

<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row h5.charitable-field-template-headline {
	font-weight: 400;
	font-size: 32px;
	line-height: 34px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> div.charitable-campaign-row .charitable-campaign-column:nth-child(even) h5.charitable-field-template-headline {
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .tab-content h5.charitable-field-template-headline {
	color: black !important;
	font-weight: 500 !important;
	text-transform: inherit;
	font-size: 32px !important;
	line-height: 38px !important;
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
  color: <?php echo $secondary; // phpcs:ignore ?>;
}

/* field: button */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> button.charitable-button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> a.charitable-button {
	background-color: <?php echo $button; // phpcs:ignore ?> !important;
	border-color: <?php echo $button; // phpcs:ignore ?> !important;
  color: <?php echo charitable_get_constracting_text_color($button); // phpcs:ignore ?>;
	text-transform: uppercase;
	border-radius: 0px;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	min-height: 50px;
	height: 50px;
	font-size: 16px;
	line-height: 15px;
}

/* field: photo */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-photo .primary-image {
	border: transparent;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-photo .charitable-campaign-primary-image {
	width: 100%;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-photo img {
	width: 100%;
	border: 0;
	border-radius: 15px;
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
	background-color: #E0E0E0;
	border-radius: 20px;
	margin-top: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: white;
	height: 13px !important;
	border-radius: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {

}

/* field: social linking */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking {
	display: table;
	padding: 0;
	margin: 0;
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
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row.charitable-field-row-social-linking {
	width: auto;
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
	padding: 6px 0 0 0px;
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
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	text-transform: capitalize;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_percent_raised {
	width: 34%;
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_amount_donated {
	width: 43%;
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_number_of_donors {
	width: 23%;
	color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item.campaign_hide_time_remaining {
	width: 100%;
	color: white;
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

/* field: donate form */

<?php echo $charitable_wrapper; // phpcs:ignore ?> form.charitable-donation-form .donation-amount.selected {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
  border-color: <?php echo $primary; // phpcs:ignore ?>;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> form.charitable-donation-form .charitable-form-field.charitable-radio-list li {
	display: inline-block;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> form.charitable-donation-form .charitable-notice {
	padding: 0;
}

/* tabs: container */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article {
	padding-top: 0px;
	padding-bottom: 0px;
	color: #000;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul > li {
	display: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content li {
	display: block;
}

/* tabs: nav */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
	width: auto;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav > ul,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
	margin-left: 30px !important;
	margin-right: 30px !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
  border: 1px solid <?php echo $primary; // phpcs:ignore ?>;
	background-color: transparent;
	margin: 0 15px 0 0;
	padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {
	color: black;
	display: block;
	font-weight: 500 !important;
	font-size: 14px !important;
	line-height: 15px !important;
	text-transform: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
	color: white !important;
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

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li:hover a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-minimum li.active a {

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

/* field: donation form */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-donation-form.charitable-template-standard #charitable-gateway-fields #charitable_stripe_card_field {
	padding-top: 15px !important;
	padding-bottom: 15px !important;
}


<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container {
	container-type: inline-size;
  container-name: campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area;
}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 700px) {
	<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container .charitable-tabs {
		margin-top: 0px;
	margin-left: 0px !important;
	margin-right: 0px !important;
	}
  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article .tab-content > ul {
	margin-left: 0px !important;
	margin-right: 0px !important;
	}
  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	padding-top: 0;
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
}