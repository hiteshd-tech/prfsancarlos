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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#F58A07';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#1D3444';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#5B5B5B';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#FFFFFF';

$slug = 'save-the-museum'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

  font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area div.charitable-preview-row/tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
  /* field items in preview area */
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field {
  display: flex;
}

/* wide spread changes in div.charitable-preview-row vs tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row {
    background-color: white;
    color: #606060;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h6 {
    color: <?php echo $secondary; ?>
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
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center > div {
    margin-left: auto;
    margin-right: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right > div {
    margin-left: auto;
    margin-right: 0;
}

/* column specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="1"] {
    flex: 2;
    border: 0;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="0"] {
    border: 0;
    flex: 1;

}

/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?> div.charitable-preview-row h5.charitable-field-preview-headline {
    color: <?php echo $secondary; ?>;
    font-weight: 500;
    text-transform: inherit;
    font-size: 42px;
    line-height: 50px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5.charitable-field-preview-headline {
    color: black;
    font-weight: 500;
    text-transform: inherit;
    font-size: 32px;
    line-height: 38px;
    margin-bottom: 10px;
}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-title h1 {
    margin: 5px 0 5px 0;
    color: <?php echo $secondary; ?>;
    font-size: 68px !important;
    line-height: 72px !important;
    font-weight: 500 !important;
}

/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {
    padding: 0;
    color: #D8DAD7;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text,
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text p {
    font-size: 18px;
    line-height: 27px;
    font-weight: 300;
    color: <?php echo $tertiary; ?>;
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
  border-color: black !important;
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
  color: black;
}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image {
  border: transparent;
  border-radius: 0px;
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
    color: <?php echo $secondary; ?>;
    font-size: 21px;
    line-height: 21px;
    font-weight: 100;
    padding-left: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
    color: <?php echo $primary; ?>;
    font-weight: 100;
    font-size: 21px;
    line-height: 21px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {
    border: 0;
    padding: 0;
    background-color: #E0E0E0;
    border-radius: 0px;
    margin-top: 15px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
    background-color: <?php echo $primary; ?>;
    height: 13px !important;
    border-radius: 0px;
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
    font-weight: 300;
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
    font-size: 14px;
    line-height: 16px;
    color: <?php echo $secondary; ?>;
    font-weight: 300;
    margin: 0 15px 0 0;
    padding: 5px 5px 5px 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {
    padding: 10px;
}

/* social icons */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-twitter .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-twitter .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/twitter-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-facebook .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-facebook .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/facebook-dark.svg');
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-linkedin .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-linkedin .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/linkedin-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-instagram .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-instagram .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/instagram-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-pinterest .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-pinterest .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/pinterest-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-tiktok .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-tiktok .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/tiktok-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-mastodon .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-mastodon .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/mastodon-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-youtube .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-youtube .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/youtube-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-threads .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-threads .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/threads-dark.svg');
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-social-linking-preview-bluesky .charitable-placeholder,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-social-sharing-preview-bluesky .charitable-placeholder {
    background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/bluesky-dark.svg');
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
    color: <?php echo $primary; ?>;
    font-weight: 500;
    font-size: 32px;
    line-height: 38px;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {
    border: 0;
    margin-top: 5px;
    margin-bottom: 5px;
    color: <?php echo $secondary; ?>
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

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-tab-container .tab-content img {
    max-width: 100%;
}

/* tabs: tab nav */

<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav {
    border: 1px solid <?php echo $primary; ?>;
    background-color: <?php echo $primary; ?>;
    width: auto;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li {
    border-top: 0;
    border-right: 1px solid <?php echo $primary; ?>;
    border-bottom: 0;
    border-left: 0;
    background-color: transparent;
    margin: 0;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li a {
    color: white;
    display: block;
    font-weight: 500;
    font-size: 14px;
    line-height: 15px;
    text-transform: none;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active,
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li:hover {
    background-color: <?php echo $primary; ?>;
    text-decoration: none;
    filter: brightness(90%);
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> article nav li.active a {
    color: white;
}
// @codingStandardsIgnoreEnd