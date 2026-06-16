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

// @codingStandardsIgnoreStart

$primary      = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#F58A07'; // phpcs:ignore
$secondary    = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#1D3444'; // phpcs:ignore
$tertiary     = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#5B5B5B'; // phpcs:ignore
$button       = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#F58A07'; // phpcs:ignore
$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800; // phpcs:ignore

$slug            = 'simple-1-col';
$wrapper         = '.charitable-campaign-wrap.template-' . $slug;
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug;

/* what should change from admin vs. frontend */

// .charitable-field        ----------> .charitable-campaign-field
// .charitable-preview-*    ----------> .charitable-campaign-*

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; // phpcs:ignore ?> { /* everything wraps in this */

font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; // phpcs:ignore ?> {
/* field items in preview area */
font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field {
	/* display: flex; */
	display: table;
}

/* wide spread changes in header vs tabs */

<?php echo $wrapper; // phpcs:ignore ?> > header {
	margin-bottom: 40px;
	font-size: 40px;
	line-height: 70px;
	font-weight: 600;
  color: <?php echo $secondary; // phpcs:ignore ?>;
}
<?php echo $wrapper; // phpcs:ignore ?> h1:empty,
<?php echo $wrapper; // phpcs:ignore ?> h2:empty,
<?php echo $wrapper; // phpcs:ignore ?> h3:empty,
<?php echo $wrapper; // phpcs:ignore ?> h4:empty,
<?php echo $wrapper; // phpcs:ignore ?> h5:empty,
<?php echo $wrapper; // phpcs:ignore ?> h6:empty {
	display: none;
}
<?php echo $wrapper; // phpcs:ignore ?> header h1,
<?php echo $wrapper; // phpcs:ignore ?> header h2,
<?php echo $wrapper; // phpcs:ignore ?> header h3,
<?php echo $wrapper; // phpcs:ignore ?> header h4,
<?php echo $wrapper; // phpcs:ignore ?> header h5,
<?php echo $wrapper; // phpcs:ignore ?> header h6 {

}
<?php echo $wrapper; // phpcs:ignore ?> .tab-content h1,
<?php echo $wrapper; // phpcs:ignore ?> .tab-content h2,
<?php echo $wrapper; // phpcs:ignore ?> .tab-content h3,
<?php echo $wrapper; // phpcs:ignore ?> .tab-content h4,
<?php echo $wrapper; // phpcs:ignore ?> .tab-content h5,
<?php echo $wrapper; // phpcs:ignore ?> .tab-content h6 {

}

<?php echo $wrapper; // phpcs:ignore ?> .tab-content > * {
	color: black;
}

<?php echo $wrapper; // phpcs:ignore ?> header h5 {
	font-size: 18px;
	line-height: 21px;
	font-weight: 500;
}

<?php echo $wrapper; // phpcs:ignore ?>  .placeholder {
	padding-left: 0;
	padding-right: 0;
	padding-top: 0;
	padding-bottom: 0;
}

/* aligns */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-left {
	margin-left: 0;
	margin-right: auto;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center > div {
	margin-left: auto;
	margin-right: auto;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-right {
	margin-left: auto;
	margin-right: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-left div.charitable-field-campaign-title {
	margin-left: 0;
	margin-right: auto;
	text-align: left;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center div.charitable-field-campaign-title {
	margin-left: auto;
	margin-right: auto;
	text-align: center;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-right div.charitable-field-campaign-title {
	margin-left: auto;
	margin-right: 0;
	text-align: right;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-left.charitable-campaign-field-photo img {
	margin-left: 0;
	margin-right: auto;
	display: block;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center.charitable-campaign-field-photo img {
	margin-left: auto;
	margin-right: auto;
	display: block;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-right.charitable-campaign-field-photo img {
	margin-left: auto;
	margin-right: 0;
	display: block;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-left .charitable-field-template-social-sharing,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-left .charitable-field-template-social-linking {
	margin-left: 0;
	margin-right: auto;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center .charitable-field-template-social-sharing,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center .charitable-field-template-social-linking {
	margin-left: auto;
	margin-right: auto;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-right .charitable-field-template-social-sharing,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-right .charitable-field-template-social-linking {
	margin-left: auto;
	margin-right: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-left .donation-wall ol {
	text-align: left;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-center .donation-wall ol {
	text-align: center;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-align-right .donation-wall ol {
	text-align: right;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-placeholder {
	width: 100%;
}


/* column specifics */

<?php echo $wrapper; // phpcs:ignore ?> .column[data-column-id="0"] {
	flex: 1;
	border: 0;
	padding-left: 0;
	padding-top: 0;
	padding-bottom: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .column[data-column-id="0"] .charitable-field-photo {
	padding: 0;
	margin: 0;
	border: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .column[data-column-id="1"] {
	flex: 1;
	border: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-panel-fields .charitable-field-wrap {
	padding-bottom: 0;
}

/* headlines in general */

<?php echo $wrapper; // phpcs:ignore ?>  h5.charitable-field-preview-headline {

}

/* field: campaign title */

<?php echo $wrapper; // phpcs:ignore ?>  .charitable-field-campaign-title h1 {
	margin: 15px auto;
	font-size: 72px;
	line-height: 84px;
	font-weight: 600;
	word-wrap: anywhere;
}

/* field: campaign description */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-description h5.charitable-field-template-headline {
	font-size: 27px;
	line-height: 29px;
	font-weight: 500;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-campaign-description .charitable-campaign-builder-placeholder-template-text {
	padding: 0;
	margin: 0;
	color: #000000;
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-builder-no-description-preview div {
	margin: 0;
	float: none;
}

/* field: campaign text */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-text {
	padding: 0;
	color: #000000;
	font-size: 14px;
	line-height: 24px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-text h5.charitable-field-template-headline {
	font-size: 27px;
	line-height: 29px;
	font-weight: 500;
}

/* field: html */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field.charitable-field-html .placeholder {
	padding: 0;
	margin: 0;
}

/* field: button */
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-button {
	margin-top: 15px;
	margin-bottom: 15px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.charitable-button,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-donate-button a.charitable-button {
    background-color: <?php echo $button; // phpcs:ignore ?>;
    border-color: <?php echo $button; // phpcs:ignore ?>;
    color: <?php echo charitable_get_constracting_text_color($button); // phpcs:ignore ?>;
	text-transform: none;
	border-radius: 0px;
	margin-top: 0;
	margin-bottom: 0;
	width: 100%;
	font-weight: 400;
	font-size: 16px;
	line-height: 25px;
	display: flex; /* Changed from block to flex */
		align-items: center; /* Vertically centers the text */
		justify-content: center; /* Optionally centers the text horizontally too */
	text-align: center !important;
		text-decoration: none !important;
		transition: filter 0.3s; /* Smooth transition */
}

/* field: photo */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-photo .charitable-campaign-primary-image {
	border: transparent;
	border-radius: 0px;
	margin-top: 25px;
	margin-bottom: 25px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-photo .charitable-campaign-primary-image img {
	max-width: 100%;
}
<?php echo $wrapper; // phpcs:ignore ?> .tab-content .charitable-campaign-field-photo .charitable-campaign-primary-image img {
	border-radius: 0px;
}


<?php echo $wrapper; // phpcs:ignore ?>  .charitable-field-photo .charitable-campaign-align-center .primary-image-container {
	text-align: center;
}
<?php echo $wrapper; // phpcs:ignore ?>  .charitable-field-photo .charitable-campaign-align-left .primary-image-container {
	text-align: left;
}
<?php echo $wrapper; // phpcs:ignore ?>  .charitable-field-photo .charitable-campaign-align-right .primary-image-container {
	text-align: right;
}

/* field: photo */

<?php echo $wrapper; // phpcs:ignore ?> header .primary-image-container {
	margin: 0;
	padding: 0;
}

<?php echo $wrapper; // phpcs:ignore ?> .tab-content .primary-image-container {
	margin: 0;
	padding: 0;
}

/* field: progress bar */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
    color: <?php echo $primary; // phpcs:ignore ?>;
	font-weight: 400;
	font-size: 18px;
	line-height: 21px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {
	font-weight: 400;
	font-size: 18px;
	line-height: 21px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {
	border: 0;
	padding: 0;
	background-color: #E0E0E0;
	border-radius: 5px;
	margin-top: 15px;
	margin-bottom: 15px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
    background-color: <?php echo $secondary; // phpcs:ignore ?>;
	height: 8px !important;
	border-radius: 5px;
	text-align: right;
	opacity: 1.0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {
	display: inline-block;
	border-radius: 25px;
	width: 25px;
	height: 25px;
	margin-right: -15px;
	margin-top: -10px;
}

/* field: social linking */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links  {
	display: table;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links .charitable-field-template-social-linking-headline-container {
	display: block;
	float: left;
	padding: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links h5.charitable-field-preview-headline {
	font-size: 14px !important;
	line-height: 26px !important;
	font-weight: 300 !important;
	margin: 0 20px 0 0;
	padding: 0;
  color: <?php echo $primary; // phpcs:ignore ?> !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-links .charitable-placeholder {
	padding: 10px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking {
	display: table;
	margin-top: 10px;
	margin-bottom: 10px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row p {
	display: none;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking h5.charitable-field-template-headline {
	font-size: 14px !important;
	line-height: 26px !important;
	font-weight: 300 !important;
	margin: 0 20px 0 0;
	padding: 0;
  color: <?php echo $primary; // phpcs:ignore ?> !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-social-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-social-field-column .charitable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-campaign-social-link img,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-campaign-social-link a {
	width: 25px;
	height: 25px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-linking .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}

/* social icons */

<?php
// Social media icons for frontend - using dark style
$social_icons = array(
	'twitter', 'facebook', 'linkedin', 'instagram',
	'pinterest', 'tiktok', 'mastodon', 'youtube',
	'threads', 'bluesky'
);

foreach ( $social_icons as $icon ) {
	$icon_url = charitable_get_campaign_builder_asset_url( "images/campaign-builder/fields/social-links/{$icon}-dark.svg" );
	if ( ! empty( $icon_url ) ) {
		?>
		<?php echo $wrapper; // phpcs:ignore ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-<?php echo esc_attr( $icon ); ?> .charitable-placeholder,
		<?php echo $wrapper; // phpcs:ignore ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-<?php echo esc_attr( $icon ); ?> .charitable-placeholder {
			background-image: url('<?php echo esc_url( $icon_url ); ?>');
		}
		<?php
	}
}
?>

/* field: social sharing */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing {
	display: table;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing .charitable-field-template-social-sharing-headline-container {
	display: block;
	float: left;
	padding: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing .charitable-field-row {
	display: block;
	float: left;
	width: auto;
	margin: 0 0 0 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing h5.charitable-field-preview-headline {
	font-size: 14px;
	line-height: 26px;
	font-weight: 300;
	margin: 0 20px 0 0;
	padding: 0;
  color: <?php echo $primary; // phpcs:ignore ?>;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-social-sharing .charitable-placeholder {
	padding: 10px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing {
	display: table;
	margin-top: 10px;
	margin-bottom: 10px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row p {
	display: none;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing h5.charitable-field-template-headline {
	font-size: 14px !important;
	line-height: 26px !important;
	font-weight: 300 !important;
	margin: 0 20px 0 0;
	padding: 0;
  color: <?php echo $primary; // phpcs:ignore ?> !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column {
	float: left;
	margin-right: 20px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-social-field-column .charitable-campaign-social-link {
	margin-top: 5px;
	min-height: 20px !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-campaign-social-link img,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-campaign-social-link a {
	width: 25px;
	height: 25px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-social-sharing .charitable-field-row .charitable-campaign-social-link a:hover {
	opacity: 0.65;
}

/* field: campaign summary */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary {
	padding-left: 0;
	padding-right: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div {
  color: <?php echo $primary; // phpcs:ignore ?>;
	font-weight: 400;
	font-size: 14px;
	line-height: 16px;
	text-align: left;
	text-transform: capitalize;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary div span {
  color: <?php echo $secondary; // phpcs:ignore ?>;
	font-weight: 600;
	font-size: 32px;
	line-height: 38px;
	text-align: left;
	text-transform: capitalize;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-template-campaign-summary .campaign-summary-item {
  color: <?php echo $primary; // phpcs:ignore ?>;
	border: 0;
	margin-top: 5px;
	margin-bottom: 5px;
	text-align: left;
	text-transform: capitalize;
}

/* field: donate amount */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount label,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-ield-donate-amount input.custom-donation-input[type="text"] {
    color: <?php echo $primary; // phpcs:ignore ?>;
	<?php /* border: 1px solid <?php echo $primary; // phpcs:ignore ?> !important; */ ?>
	pointer-events: auto;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
    border: 10px solid <?php echo $tertiary; // phpcs:ignore ?> !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
    color: <?php echo $primary; // phpcs:ignore ?>;
	/* invert the color */
	filter: invert(1);
	/* opacity */
	opacity: 0.5;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-template-donation-options ul.charitable-template-donation-amounts {
	margin: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-template-donation-options ul.charitable-template-donation-amounts .charitable-template-donation-amount,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-template-donation-options ul.charitable-template-donation-amounts .charitable-template-donation-amount.custom-donation-amount input[type="text"] {
    color: <?php echo $primary; // phpcs:ignore ?> !important;
    border-color: <?php echo $primary; // phpcs:ignore ?> !important;
	pointer-events: auto !important;
}
<?php echo $wrapper; // phpcs:ignore ?> .donation-amounts.donation-suggested-amount li input[type=text] {
	font-size: 16px;
}


/* field: donate form */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-field-donation-form .charitable-form-placeholder {
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

<?php echo $wrapper; // phpcs:ignore ?>  .shortcode-preview {
	padding-left: 10px;
	padding-right: 10px;
}

/* tabs: container */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-tab-container {
	background-color: transparent;
	margin-top: 0px;
}

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-tab-container .tab-content img {
	max-width: 100%;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-tab-container .tab-content > ul {
	margin-left: 0;
	margin-right: 0;
}

/* tabs: tab nav */

<?php echo $wrapper; // phpcs:ignore ?> article nav {
	border: 1px solid transparent;
	background-color: transparent;
	width: auto;
	margin-left: 0;
	margin-right: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav li {
	border-top: 0;
	border-right: 0;
	border-bottom: 0;
	border-left: 0;
	background-color: transparent;
	margin: 0 10px 0 0;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav li a {
	color: black;
	display: block;
	text-transform: none;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav li.active {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
	color: white;
	text-decoration: none;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav li:hover {
  background-color: <?php echo $primary; // phpcs:ignore ?>;
	color: white;
	text-decoration: none;
	filter: brightness(90%);
}
<?php echo $wrapper; // phpcs:ignore ?> article nav li.active a,
<?php echo $wrapper; // phpcs:ignore ?> article nav li:hover a {
	color: white;
}

/* tabs: style */

<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-style-boxed li {

}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-style-boxed li a {

}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-style-rounded li {

}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-style-rounded li a {

}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-style-minimum li {
	background-color: transparent;
  border-bottom: 1px solid <?php echo $button; // phpcs:ignore ?>;
	border-top: 0;
	border-right: 0;
	border-left: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-style-minimum li a {
  color: <?php echo $primary; // phpcs:ignore ?>;
	border-top: 0;
	border-right: 0;
	border-left: 0;
}

/* tabs: sized */

<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-size-small li {
	font-size: 10px;
	padding: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-size-small li a {
	font-size: 16px;
	padding: 18px;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-size-medium li {
	font-size: 14px;
	padding: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-size-large li {

}
<?php echo $wrapper; // phpcs:ignore ?> article nav.tab-size-large li a {
	font-weight: 500;
	font-size: inherit;
	line-height: inherit;
}

/* field: donor wall */

<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-donation-wall {
	font-size: 18px;
	line-height: 24px;
	margin-top: 15px;
	margin-bottom: 15px;
}

/* field: organizer */
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer {
	font-size: 15px;
	line-height: 25px;
	font-weight: 400;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer .charitable-organizer-name {
	font-weight: 600;
	font-size: 21px;
	line-height: 31px;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer p,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer h1,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer h2,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer h3,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer h4,
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer h5 {
	margin-top: 0;
	margin-bottom: 0;
}
<?php echo $wrapper; // phpcs:ignore ?> .charitable-campaign-field-organizer h5 {
	margin: 0;
	padding: 0;
	font-size: 15px;
	line-height: 25px;
	font-weight: 400;
}

// @codingStandardsIgnoreEnd