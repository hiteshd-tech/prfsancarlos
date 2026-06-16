<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Disaster Relief campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#9F190E'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#202020'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#FFFFFF'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#9F190E'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug            = 'disaster-relief';
$charitable_wrapper         = '.charitable-campaign-wrap.template-' . $charitable_slug;
$charitable_preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $charitable_slug;

require_once ('../../../../../includes/admin/campaign-builder/templates/functions-campaign-templates.php');
?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row {
	background-color: <?php echo $tertiary; // phpcs:ignore ?>;
	color: #606060;
}



/* row specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row {
	padding: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row > * {
	color: <?php echo $secondary; // phpcs:ignore ?>;
	font-size: 14px;
	line-height: 24px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row h5 {
	color: <?php echo $primary; // phpcs:ignore ?>;
	margin: 0 0 10px 0;
	padding: 0;
	font-size: 16px;
	line-height: 16px;
	font-weight: 700;
	word-wrap: break-word;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> #charitable-template-row-1 {
	background-color: <?php echo $tertiary; // phpcs:ignore ?>;
}

/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> #charitable-template-row-0 .charitable-campaign-column:nth-child(even) {
	border: 1px solid #ECECEC;
	padding: 25px;
	max-width: calc(100% - 50px);
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> #charitable-template-row-0 .charitable-campaign-column:nth-child(odd) {
	flex: 0 0 66%;
	padding: 25px;
	max-width: calc(100% - 50px);
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> #charitable-template-row-0 .charitable-campaign-column:nth-child(even) .charitable-campaign-field {
	margin-top: 5px;
	margin-bottom: 5px;
}

/* field: campaign description */

<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
	padding: 0;
	color: #D8DAD7;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
	font-size: 24px;
	line-height: 38px;
	font-weight: 300;
}


/* field: button */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button button.button,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-button a.donate-button {
	border-radius: 35px;
	background-color: <?php echo $button; // phpcs:ignore ?> !important;
	border-color: <?php echo $button; // phpcs:ignore ?> !important;
	color: <?php echo charitable_get_constracting_text_color($button); // phpcs:ignore ?>;
	display: flex; /* Changed from block to flex */
	align-items: center; /* Vertically centers the text */
	justify-content: center; /* Optionally centers the text horizontally too */
	text-align: center !important;
	text-decoration: none !important;
	transition: filter 0.3s; /* Smooth transition */
}



/* field: progress bar */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: #202020;
	font-weight: 500;
	font-size: 18px;
	line-height: 21px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	color: <?php echo $primary; // phpcs:ignore ?>;
	font-weight: 600;
	font-size: 24px;
	line-height: 28px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 5px;
	margin-top: 15px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: <?php echo $primary; // phpcs:ignore ?>;

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
	display: inline-block;
	background-color: <?php echo $primary; // phpcs:ignore ?>;
	border-radius: 25px;
	width: 25px;
	height: 25px;
	margin-right: -15px;
	margin-top: -10px;
}


/* field: campaign summary */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary {
	margin-bottom: 10px !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div span {
	color: <?php echo $secondary; // phpcs:ignore ?> !important;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div.campaign-summary-item {
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
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

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links {
	margin-top: 20px;
	margin-bottom: 20px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking {
	display: flex;
	flex-wrap: wrap;
	flex-direction: row;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-row-social-linking {
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

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-placeholder {
 padding: 0px 0px 10px 0;
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
 float: left;
 display: table-cell;
 vertical-align: middle;
 padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing-headline-container h5 {
 margin-right: 10px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row {
 display: block;
 float: left;
 width: auto;
 margin: 0 0 0 0;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row p {
 display: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {

 color: <?php echo $secondary; // phpcs:ignore ?>;

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
 border: 0;
 padding: 0px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing {
 border: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking img,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing img {
 height: 30px !important;
 width: auto !important;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-field-template-social-linking-headline-container.charitable-placeholder,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-template-social-sharing-headline-container.charitable-placeholder {
    padding-left: 0;
    padding-top: 2px;
    padding-bottom: 0;
    padding-right: 0;
}

/* tabs: tab container */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-tab-wrap {
 font-size: 14px;
 line-height: 24px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article {
 padding: 10px;
}

/* tabs: tab nav */


<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
 border-top: 0;
 border-right: 1px solid <?php echo $secondary; // phpcs:ignore ?>;
 border-bottom: 0;
 border-left: 0;
 margin: 0;
 padding: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li a {

 display: block;
 font-weight: 500 !important;
 font-size: 17px !important;
 line-height: 17px !important;
 text-transform: none;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active {

 border: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li.active a {
 color: white;
}

/* tabs: style */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
 background-color: transparent;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
 color: <?php echo $button; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover {
 background-color: <?php echo $button; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li:hover a {
 color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active {
 background-color: <?php echo $button; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li.active a {
 color: white;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover {
 background-color: <?php echo $button; // phpcs:ignore ?>;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li:hover a {
 color: white;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li.active {
 background-color: <?php echo $button; // phpcs:ignore ?>;
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

<?php echo $charitable_wrapper; // phpcs:ignore ?> {
  container-type: inline-size;
  container-name: campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area;
}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 700px) {

}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 400px) {



}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 1000px) {

	  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row.charitable-field-row-social-linking {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
	  }
	  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
	  }
	  <?php echo $charitable_wrapper; // phpcs:ignore ?>  .section[data-section-type="tabs"] article {
		padding: 13px !important;
	  }


}

.charitable-preview.charitable-builder-template-<?php echo $charitable_slug; // phpcs:ignore ?> {
}