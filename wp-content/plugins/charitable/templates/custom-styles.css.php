<?php
/**
 * Renders the custom styles added by Charitable.
 *
 * Override this template by copying it to yourtheme/charitable/custom-styles.css.php
 *
 * @author  WP Charitable LLC
 * @package Charitable/Templates/CSS
 * @since   1.0.0
 * @version 1.7.0.8
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Introduced in 1.7.0.7, as we migrate away from WordPress customizer.
$charitable_highlight_color_donation_form = esc_html( charitable_get_option( 'donation_form_default_highlight_colour', false ) );

$charitable_highlight_colour       = charitable_get_option( 'highlight_colour', apply_filters( 'charitable_default_highlight_colour', '#f89d35' ) );
$charitable_highlight_colour_error = ( false === $charitable_highlight_color_donation_form ) ? charitable_get_option( 'highlight_colour', apply_filters( 'charitable_default_highlight_colour', '#f89d35' ) ) : $charitable_highlight_color_donation_form;

?>
<style id="charitable-highlight-colour-styles">
.campaign-raised .amount,
.campaign-figures .amount,
.donors-count,
.time-left,
.charitable-form-field a:not(.button),
.charitable-form-fields .charitable-fieldset a:not(.button),
.charitable-notice,
.charitable-notice .errors a {
	color: <?php echo esc_html( $charitable_highlight_colour_error ); ?>;

}
#charitable-donation-form .charitable-notice {
	border-color: <?php echo esc_html( $charitable_highlight_colour_error ); ?>;
}

.campaign-progress-bar .bar,
.donate-button,
.charitable-donation-form .donation-amount.selected,
.charitable-donation-amount-form .donation-amount.selected { background-color: <?php echo esc_html( $charitable_highlight_colour ); ?>; }

.charitable-donation-form .donation-amount.selected,
.charitable-donation-amount-form .donation-amount.selected,
.charitable-notice,
.charitable-drag-drop-images li:hover a.remove-image,
.supports-drag-drop .charitable-drag-drop-dropzone.drag-over { border-color: <?php echo esc_html( $charitable_highlight_colour ); ?>; }

<?php do_action( 'charitable_custom_styles', $charitable_highlight_colour ); ?>
</style>
