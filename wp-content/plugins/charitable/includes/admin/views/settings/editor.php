<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display WP Editor in a settings field.
 *
 * @author    David Bisset
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2023, WP Charitable LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.8.8.6
 */

$charitable_value = charitable_get_option( $view_args['key'] );

if ( empty( $charitable_value ) ) :
	$charitable_value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

$charitable_editor_args         = isset( $view_args['editor'] ) ? $view_args['editor'] : array();
$charitable_default_editor_args = array(
	'textarea_name' => sprintf( 'charitable_settings[%s]', $view_args['name'] ),
);
$charitable_editor_args         = wp_parse_args( $charitable_editor_args, $charitable_default_editor_args );
?>
<div <?php echo esc_attr( charitable_get_arbitrary_attributes( $view_args ) ); ?>>
	<?php
	wp_editor( $charitable_value, sprintf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ), $charitable_editor_args );

	if ( isset( $view_args['help'] ) ) :
		?>
		<div class="charitable-help"><?php echo wp_kses_post( $view_args['help'] ); ?></div>
		<?php
	endif;
	?>
</div>
