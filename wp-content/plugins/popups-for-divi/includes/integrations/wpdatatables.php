<?php
/**
 * Integration modules provide compatibility with other plugins, or extend the
 * core features of Divi Areas Pro.
 *
 * Integrates with: WPDataTables
 * Scope: Layout compatibility
 *
 * @free include file
 * @package PopupsForDivi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initialize the integration.
 *
 * @since 3.0.0
 */
function pfd_integration_wpdatatables_setup() {
	if ( ! defined( 'WDT_ROOT_PATH' ) ) {
		return;
	}

	add_action( 'wp_footer', 'divi_popups_helper_wpdatatables_styles', 999 );
}

/**
 * Output inline CSS that is used for wpDataTables compatibility.
 *
 * @since 2.3.0
 * @return void
 */
function divi_popups_helper_wpdatatables_styles() {
	if ( ! wp_script_is( 'wdt-common', 'done' ) ) {
		return;
	}

	?>
	<!-- Divi Areas compatibility with wpDataTables -->
	<style>
		.da-popup-visible .dt-button-collection,
		.da-hover-visible .dt-button-collection,
		.da-flyin-visible .dt-button-collection {
			z-index: 990000003;
		}

		.da-popup-visible .wpdt-c .modal,
		.da-hover-visible .wpdt-c .modal,
		.da-flyin-visible .wpdt-c .modal {
			z-index: 990000002;
		}

		.da-popup-visible .modal-backdrop,
		.da-hover-visible .modal-backdrop,
		.da-flyin-visible .modal-backdrop {
			z-index: 990000001;
		}

		.da-popup-visible .media-modal,
		.da-hover-visible .media-modal,
		.da-flyin-visible .media-modal {
			z-index: 990001000;
		}

		.da-popup-visible .media-modal-backdrop,
		.da-hover-visible .media-modal-backdrop,
		.da-flyin-visible .media-modal-backdrop {
			z-index: 990000990;
		}
	</style>
	<?php
}

// Integrate with WPDataTables.
add_action( 'init', 'pfd_integration_wpdatatables_setup' );
