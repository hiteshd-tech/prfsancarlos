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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#7A8347';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#000000';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#5F5F5F';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#7A8347';

$slug = 'school-trip'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

font-family: SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area div.charitable-preview-row/tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
/* field items in preview area */
}

/* wide spread changes in div.charitable-preview-row vs tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field {
  display: flex;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row {
  background-color: transparent;
  padding: 50px;
  color: white;
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
  color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h1:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h2:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h3:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h4:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5:empty,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h6:empty {
  display: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h6 {
  color: black;
  font-size: 32px;
  line-height: 38px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content {
  color: <?php echo $tertiary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content > * {
  color: #92908F;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row > * {
  color: <?php echo $tertiary; ?>;
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
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center > div,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center .charitable-header {
  margin-left: auto;
  margin-right: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right > div {
  margin-left: auto;
  margin-right: 0;
}


/* column specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-column:nth-child(even) {
  flex: 2;
  border: 0;
  padding-top: 0px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-column:nth-child(odd) {
  border: 0;
  flex: 1 1 26%;
  padding-top: 0px;
  padding-bottom: 0px;
}

/* section specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-column:nth-child(even) .charitable-field-section {
  background-color: <?php echo $primary; ?>;
  padding: 20px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-column:nth-child(odd) .charitable-field-section {
  background-color: transparent;

}

/* header specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-header .charitable-preview-align-center  {
  text-align: center;
}

/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5.charitable-field-preview-headline {
  font-weight: 400;
  font-size: 32px;
  line-height: 34px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row .charitable-field-column:nth-child(even) h5.charitable-field-preview-headline {
  color: white;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5.charitable-field-preview-headline {
  color: black !important;
  font-weight: 500 !important;
  text-transform: inherit;
  font-size: 32px !important;
  line-height: 38px !important;
  margin-top: 20px;
  margin-bottom: 20px;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-header h5.charitable-field-preview-headline {
  color: <?php echo $primary; ?> !important;
  font-weight: 500 !important;
  text-transform: inherit;
  font-size: 42px !important;
  line-height: 48px !important;
  margin-top: 20px;
  margin-bottom: 20px;
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
  color: <?php echo $tertiary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
  font-size: 15px;
  line-height: 28px;
  font-weight: 400;
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
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo img {
  width: 100%;
  border: 0;
  border-radius: 15px;
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
  color: white;
  font-weight: 100;
  font-size: 21px;
  line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
  border: 0;
  padding: 0;
  background-color: #E0E0E0;
  border-radius: 20px;
  margin-top: 15px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: white;
  height: 13px !important;
  border-radius: 20px;
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
  font-size: 14px !important;
  line-height: 16px !important;
  color: <?php echo $secondary; ?>;
  font-weight: 300 !important;
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
  font-size: 14px !important;
  line-height: 16px !important;
  color: <?php echo $secondary; ?>;
  font-weight: 300 !important;
  margin: 0 15px 0 0;
  padding: 5px 5px 5px 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {
  padding: 10px;
}

/* field: social links and sharing */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-field-row .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-field-row .charitable-placeholder {
    padding: 10px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-twitter .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-twitter .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/twitter-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-facebook .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-facebook .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/facebook-white.svg');
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-linkedin .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-linkedin .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/linkedin-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-instagram .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-instagram .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/instagram-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-pinterest .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-pinterest .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/pinterest-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-tiktok .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-tiktok .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/tiktok-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-links .charitable-social-linking-preview-mastodon .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-social-sharing .charitable-social-sharing-preview-mastodon .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/mastodon-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-youtube .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-youtube .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/youtube-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-threads .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-threads .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/threads-white.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-bluesky .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-bluesky .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/bluesky-white.svg');
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
  border: 1px solid <?php echo $primary; ?>;
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
  background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active a {
  color: white;
}

/* missing addons? */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-charitable-videos {
    margin-top: 20px;
    margin-bottom: 20px;
    min-height: 500px;
    background-color: rgba(0,0,0,.7);
    display: table;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-charitable-videos .charitable-missing-addon-content {
    background-color: transparent;
    vertical-align: middle;
    display: table-cell;
    height: 100;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-charitable-videos .charitable-missing-addon-content h2 {
    color: white;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-charitable-videos .charitable-missing-addon-bg {
    background-size: cover;
}

// @codingStandardsIgnoreEnd