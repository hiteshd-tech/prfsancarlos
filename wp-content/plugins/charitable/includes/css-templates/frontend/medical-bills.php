<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display custom CSS for Medical Bills campaign template.
 *
 * @package   Charitable
 * @author    WP Charitable LLC
 * @copyright Copyright (c) 2024, WP Charitable LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 * @version   1.8.8.6
 */

header( 'Content-type: text/css; charset: UTF-8' );

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#5C8AF3'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#21458F'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#F5F0EE'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#5C8AF3'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$charitable_slug            = 'medical-bills';
$charitable_wrapper         = '.charitable-campaign-wrap.template-' . $charitable_slug;
$charitable_preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $charitable_slug;

require_once '../../admin/campaign-builder/templates/functions-campaign-templates.php';

// phpcs:disable
?>

:root {
	--charitable_campaign_theme_primary: <?php echo $primary; // phpcs:ignore ?>;
	--charitable_campaign_theme_secondary: <?php echo $secondary; // phpcs:ignore ?>;
	--charitable_campaign_theme_tertiary: <?php echo $tertiary; // phpcs:ignore ?>;
	--charitable_campaign_theme_button: <?php echo $button; // phpcs:ignore ?>;
}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row {
	color: #76838B;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> article {
	color: #76838B;
	margin-top: 0;
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
	font-weight: 600;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> #charitable-template-row-1 {
	/* background-color: <?php echo $tertiary; // phpcs:ignore ?>; */
}

/* column specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) {
	flex: 1;
	padding: 0 25px 0 25px;
	max-width: calc(100% - 50px);
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) {
	flex: 1;
	padding: 0;
}

/* section specifics */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
	background-color: white;
	padding: 25px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(odd) .charitable-field-section {

}

/* header */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row-type-header h1,
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-row-type-header .charitable-campaign-field_campaign-title h1 {
	font-size: 40px !important;
	line-height: 70px !important;
	font-weight: 600  !important;
	color: <?php echo $secondary; // phpcs:ignore ?> !important;
	word-wrap: anywhere;
}

/* field: campaign title */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-campaign-title h1 {
	margin: 5px 0 5px 0;
	color: <?php echo $secondary; // phpcs:ignore ?> !important;
	font-size: 68px !important;
	line-height: 72px !important;
	font-weight: 500 !important;
	word-wrap: anywhere;
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
	height: 8px !important;
	border-radius: 5px;
	text-align: right;
	opacity: 1.0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
}

/* field: campaign summary */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary {

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div {
	color: #92918E;

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary div span {
	color: <?php echo $secondary; // phpcs:ignore ?> !important;

}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-summary .campaign-summary-item {

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

/* tabs: tab nav */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav {
	background-color: #F7F7F7;
	margin: 0;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .section[data-section-type="tabs"] article nav.charitable-campaign-nav li {
	border-top: 0;
	border-right: 0
	border-bottom: 0;
	border-left: 0;
	margin: 0 5px 0 0;
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
}

/* tabs: style */

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li {
	background-color: transparent;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-boxed li a {
	color: <?php echo $secondary; // phpcs:ignore ?>;
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
	background-color: transparent;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-container .section[data-section-type="tabs"] article nav.charitable-tab-style-rounded li a {
	color: <?php echo $button; // phpcs:ignore ?>;
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

/* field: social linking */
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-links .charitable-placeholder {
	padding: 0px;
}
<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row.charitable-field-row-social-linking {
	width: auto;
}

<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row.charitable-field-row-social-sharing {
	width: auto;
}


<?php echo $charitable_wrapper; // phpcs:ignore ?>  .charitable-campaign-container {
	container-type: inline-size;
  container-name: campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area;
}
@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 700px) {
	<?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-column:nth-child(even) .charitable-field-section {
		padding: 0;
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

@container campaign-<?php echo $charitable_slug; // phpcs:ignore ?>-area (max-width: 1000px) {

	  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row.charitable-field-row-social-linking {
		width: 100%;
		}
	  <?php echo $charitable_wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-social-sharing .charitable-field-row {
		width: 100%;
		}


}

// phpcs:enable