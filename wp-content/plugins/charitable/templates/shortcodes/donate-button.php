<?php
/**
 * The template used to display a simple donation button/link.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/donate-button.php
 *
 * @author  David Bisset
 * @package Charitable/Templates/Shortcodes
 * @since   1.8.2
 * @version 1.8.8.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$charitable_target_for_link   = $view_args['new_tab'] ? ' target="_blank"' : '';
$charitable_target_for_button = $view_args['new_tab'] ? ' onclick="window.open(\'' . esc_url( $view_args['url'] ) . '\')" ' : ' onclick="location.href=\'' . esc_url( $view_args['url'] ) . '\'" ';

// if the type is a button, use a button element, otherwise use a link.
if ( 'link' === $view_args['type'] ) :

	// filter esc_attr but prevent quotes from being encoded.
	$charitable_target_for_link   = str_replace( '&quot;', '"', esc_attr( $charitable_target_for_link ) );
	$charitable_target_for_button = str_replace( '&quot;', '"', esc_attr( $charitable_target_for_button ) );
	?>

	<a <?php echo $charitable_target_for_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> href="<?php echo esc_url( $view_args['url'] ); ?>" class="<?php echo esc_attr( $view_args['css'] ); ?>"><?php echo esc_html( $view_args['label'] ); ?></a>

<?php else : ?>

	<input <?php echo $charitable_target_for_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> id="charitable-donate-button-<?php echo intval( $view_args['campaign'] ); ?>" class="<?php echo esc_attr( $view_args['css'] ); ?>" type="button"  value="<?php echo esc_html( $view_args['label'] ); ?>" />

<?php endif; ?>
