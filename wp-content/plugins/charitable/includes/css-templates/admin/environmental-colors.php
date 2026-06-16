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

$primary   = isset( $_GET['p'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['p'] ) : '#F9F6EE';
$secondary = isset( $_GET['s'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['s'] ) : '#24231E';
$tertiary  = isset( $_GET['t'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['t'] ) : '#61AA4F';
$button    = isset( $_GET['b'] ) ? '#' . preg_replace("/[^A-Za-z0-9 ]/", '', $_GET['b'] ) : '#61AA4F';

$slug    = 'environmental'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.
$wrapper = '.charitable-preview.charitable-builder-template-' . $slug . ' #charitable-design-wrap .charitable-campaign-preview'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variable used in CSS selectors.

require_once ('../../admin/campaign-builder/templates/functions-campaign-templates.php');

?>

.charitable-preview.charitable-builder-template-<?php echo $slug; ?> { /* everything wraps in this */

  font-family: -apple-system, BlinkMacSystemFont, sans-serif;

}

/* this narrows things down a little to the preview area header/tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> {
  /* field items in preview area */
}

/* wide spread changes in header vs tabs */

<?php echo charitable_esc_attr_php( $wrapper ); ?> header {
    color: #606060;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> header h6 {
    color: <?php echo $secondary; ?>
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h1,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h2,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h3,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h4,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h5,
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content h6 {
    color: <?php echo $secondary; ?>;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content > * {
    color: black;
}

<?php echo charitable_esc_attr_php( $wrapper ); ?> header h5 {

}

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .placeholder {

}

/* aligns */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-left > div {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-center > div {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-preview-align-right > div {

}

/* column specifics */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="0"] {
    background-color: transparent;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="0"] .charitable-field-photo {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .column[data-column-id="1"] {
    background-color: <?php echo $primary; ?>;
}

/* headlines in general */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  h5.charitable-field-preview-headline {
    color: <?php echo $secondary; ?>;
}

/* field: campaign title */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-title h1 {

    color: <?php echo $secondary; ?>;

}

/* field: campaign description */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description h5.charitable-field-preview-headline {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-campaign-description .charitable-campaign-builder-placeholder-preview-text {

}


/* field: text */

<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-text .charitable-campaign-builder-placeholder-preview-text {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-text h5.charitable-field-preview-headline {
    color: <?php echo $tertiary; ?>;
}


/* field: button */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-donate-button .charitable-field-preview-donate-button span.placeholder.button {
  background-color: <?php echo $button; ?> !important;
  border-color: <?php echo $button; ?> !important;
  color: <?php echo charitable_get_constracting_text_color($button); ?>;
}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image {


}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-photo .primary-image img {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .charitable-field.charitable-field-photo .primary-image img {

}


<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-photo .charitable-preview-align-center .primary-image-container {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-photo .charitable-preview-align-left .primary-image-container {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?>  .charitable-field-photo .charitable-preview-align-right .primary-image-container {

}

/* field: photo */

<?php echo charitable_esc_attr_php( $wrapper ); ?> header .primary-image-container {

}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .tab-content .primary-image-container {

}

/* field: progress bar */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-percent-raised {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar-info-row div.campaign-goal {
    color: <?php echo $primary; ?>;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar {
  background-color: <?php echo $primary; ?>;
}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field.charitable-field-progress-bar .progress-bar span {
  background-color: <?php echo $primary; ?>;
}

/* field: social linking */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking {

}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-field-preview-social-linking-headline-container {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-field-row {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking h5.charitable-field-preview-headline {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-linking .charitable-placeholder {

}

/* social icons */

<?php
// Include shared social icons CSS with dark icon style.
$icon_style = 'dark';
include dirname( __FILE__ ) . '/partials/social-icons.php';
?>

/* field: social sharing */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing {

}

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-preview-social-sharing-headline-container {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-field-row {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing h5.charitable-field-preview-headline {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-social-sharing .charitable-placeholder {

}

/* field: campaign summary */

<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div {

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary div span {
    color: <?php echo $secondary; ?>;

}
<?php echo charitable_esc_attr_php( $wrapper ); ?> .charitable-field-preview-campaign-summary .campaign-summary-item {

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

// @codingStandardsIgnoreEnd