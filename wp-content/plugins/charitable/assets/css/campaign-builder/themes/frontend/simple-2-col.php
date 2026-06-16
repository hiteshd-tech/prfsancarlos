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

require_once '../../../../../includes/admin/campaign-builder/templates/functions-campaign-templates.php';

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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['p'] ) : '#000000';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['s'] ) : '#2B66D1';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['t'] ) : '#F99E36';
$accent    = '#F99E36';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace( '/[^A-Za-z0-9 ]/', '', $_GET['b'] ) : '#5AA152';

$mobile_width = isset( $_GET['mw'] ) ? intval( $_GET['mw'] ) : 800;

$slug            = 'simple-2-col';
$wrapper         = '.charitable-campaign-wrap.template-' . $slug;
$preview_wrapper = '.charitable-campaign-wrap.is-charitable-preview.template-' . $slug;

/* what should change from admin vs. frontend */

// .charitable-field        ----------> .charitable-campaign-field
// .charitable-preview-*    ----------> .charitable-campaign-*

?>

/* this narrows things down a little to the preview area header/tabs */

<?php echo $wrapper; ?> {
	font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

/* aligns */


/* column specifics */


/* headlines in general */

<?php echo $wrapper; ?> h5.charitable-field-template-headline,
<?php echo $wrapper; ?> .charitable-campaign-title,
<?php echo $wrapper; ?> .charitable-field-template-headline {
	color: <?php echo $primary; ?>;
}

/* field: campaign title */


/* field: campaign description */


/* field: campaign text */


/* field: html */


/* field: button */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button button.charitable-button,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-button a.charitable-button {
		background-color: <?php echo $button; ?> !important;
		border-color: <?php echo $button; ?> !important;
	color: <?php echo charitable_get_constracting_text_color( $button ); ?>;
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


/* field: photo */


/* field: progress bar */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {
	color: <?php echo $primary; ?>;

}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar-info-row div.campaign-goal {

}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress {

}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar {
	background-color: <?php echo $secondary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-progress-bar .progress-bar span {

}

/* field: social linking */


<?php
/*
echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-twitter .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-twitter .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/twitter-dark.svg');
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-facebook .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-facebook .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/facebook-dark.svg');
}

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-linkedin .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-linkedin .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/linkedin-dark.svg');
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-instagram .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-instagram .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/instagram-dark.svg');
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-pinterest .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-pinterest .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/pinterest-dark.svg');
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-tiktok charitable-.placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-tiktok .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/tiktok-dark.svg');
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-links .charitable-social-linking-preview-mastodon .charitable-placeholder,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-field-social-sharing .charitable-social-sharing-preview-mastodon .charitable-placeholder {
	background-image: url('../../../../../assets/images/campaign-builder/fields/social-links/mastodon-dark.svg');
} */
?>

/* field: social sharing */


/* field: campaign summary */

<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-campaign-summary  div.campaign-summary-item {
	color: <?php echo $primary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-campaign-summary  div.campaign-summary-item span {
	color: <?php echo $secondary; ?>;
}

/* field: donate amount */

<?php echo $wrapper; ?>  .charitable-campaign-field.charitable-campaign-field-donate-amount .charitable-template-donation-amount.selected {
	border-color: <?php echo $tertiary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-amount label,
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-amount input.custom-donation-input[type="text"] {
	color: <?php echo $primary; ?>;
	border-color: <?php echo $primary; ?> !important;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected {
	border-color: <?php echo $tertiary; ?>;
}
<?php echo $wrapper; ?> .charitable-campaign-field.charitable-campaign-field-donate-amount ul li.suggested-donation-amount.selected span.amount {
	color: <?php echo $primary; ?>;
}

/* field: donate form */


/* field: shortcode */


/* tabs: container */


/* tabs: tab nav */




/* tabs: style */

<?php echo $wrapper; ?> article nav.tab-style-boxed li {

}
<?php echo $wrapper; ?> article nav.tab-style-boxed li a {

}
<?php echo $wrapper; ?> article nav.tab-style-rounded li {

}
<?php echo $wrapper; ?> article nav.tab-style-rounded li a {

}
<?php echo $wrapper; ?> article nav.tab-style-minimum li {

}
<?php echo $wrapper; ?> article nav.tab-style-minimum li a {

}

/* tabs: sized */

<?php echo $wrapper; ?> article nav.tab-size-small li {

}
<?php echo $wrapper; ?> article nav.tab-size-small li a {

}
<?php echo $wrapper; ?> article nav.tab-size-medium li {

}
<?php echo $wrapper; ?> article nav.tab-size-medium li a {

}
<?php echo $wrapper; ?> article nav.tab-size-large li {

}
<?php echo $wrapper; ?> article nav.tab-size-large li a {

}

/* field: donor wall */


/* field: organizer */

<?php echo $wrapper; // phpcs:ignore ?>  .charitable-campaign-container {
	container-type: inline-size;
  container-name: campaign-<?php echo $slug; // phpcs:ignore ?>-area;
}

@container campaign-<?php echo $slug; // phpcs:ignore ?>-area (max-width: 700px) {
	// ... container query styles ...
}

.charitable-preview.charitable-builder-template-<?php echo $slug; // phpcs:ignore ?> {
	// ... preview styles ...
}
// @codingStandardsIgnoreEnd