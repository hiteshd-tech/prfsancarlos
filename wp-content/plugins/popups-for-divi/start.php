<?php
/**
 * Load all application modules.
 *
 * @package Popups_For_Divi
 */

defined( 'ABSPATH' ) || die();

add_action( 'plugins_loaded', 'pfd_init_plugin' );

/**
 * Initialize the free plugin after all plugins were loaded.
 * We want to check, if the premium plugin is active, before loading the
 * free plugin.
 */
function pfd_init_plugin() {
	if ( defined( 'DIVI_AREAS_INST' ) ) {
		return;
	}

	require_once __DIR__ . '/constants.php';

	/**
	 * Instead of using an autoloader that dynamically loads our classes, we have decided
	 * to include all dependencies during initialization.
	 *
	 * We have the following reasons for this:
	 *
	 * 1. It makes the plugin structure more transparent: We can see all used files here.
	 * 2. The number of files is so small that auto-loading does not save a lot of
	 *    resources.
	 * 3. In a production build we want to make sure that files are always loaded in the
	 *    same order, at the same time.
	 * 4. Every file is treated equal: No different treatment for classes vs function
	 *    files.
	 *
	 * @since 2.0.0
	 */

	// Core files.
	require_once DIVI_POPUP_PATH . 'includes/functions.php';
	require_once DIVI_POPUP_PATH . 'includes/helpers.php';
	require_once DIVI_POPUP_PATH . 'includes/hooks.php';

	require_once DIVI_POPUP_PATH . 'includes/admin/functions.php';
	require_once DIVI_POPUP_PATH . 'includes/admin/hooks.php';
	require_once DIVI_POPUP_PATH . 'includes/assets/functions.php';
	require_once DIVI_POPUP_PATH . 'includes/assets/hooks.php';
	require_once DIVI_POPUP_PATH . 'includes/builder/functions.php';
	require_once DIVI_POPUP_PATH . 'includes/builder/hooks.php';
	
	require_once DIVI_POPUP_PATH . 'includes/builder-5/functions.php';
	require_once DIVI_POPUP_PATH . 'includes/builder-5/hooks.php';

	// Integrations and compatibility.
	require_once DIVI_POPUP_PATH . 'includes/integrations/ie.php';
	require_once DIVI_POPUP_PATH . 'includes/integrations/sg-optimizer.php';
	require_once DIVI_POPUP_PATH . 'includes/integrations/wp-rocket.php';
	require_once DIVI_POPUP_PATH . 'includes/integrations/wpdatatables.php';
}
