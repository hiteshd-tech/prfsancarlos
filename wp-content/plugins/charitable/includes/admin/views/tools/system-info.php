<?php
/**
 * WPCode integration code snippets page.
 *
 * @since 1.8.1.6
 *
 * @var array  $snippets        WPCode snippets list.
 * @var bool   $action_required Indicate that user should install or activate WPCode.
 * @var string $action          Popup button action.
 * @var string $plugin          WPCode Lite download URL | WPCode Lite plugin slug.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

charitable_get_system_info()->display();

?>
