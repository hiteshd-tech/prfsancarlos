/* global charitableWpcodeVars, List, charitable_admin */

/**
 * @param charitableWpcodeVars.installing_text
 */

/**
 * WPCode integration script.
 *
 * @since 1.8.1.6
 */
const CharitableWPCode = window.CharitableWPCode || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.1.6
	 */
	const app = {

		/**
		 * Blue spinner HTML.
		 *
		 * @since 1.8.1.6
		 *
		 * @type {Object}
		 */
		spinnerBlue: '<i class="charitable-loading-spinner charitable-loading-blue charitable-loading-inline"></i>',

		/**
		 * White spinner HTML.
		 *
		 * @since 1.8.1.6
		 *
		 * @type {Object}
		 */
		spinnerWhite: '<i class="charitable-loading-spinner charitable-loading-white charitable-loading-inline"></i>',

		/**
		 * List.js object.
		 *
		 * @since 1.8.1.6
		 *
		 * @type {Object}
		 */
		snippetSearch: null,

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1.6
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.1.6
		 */
		ready() {

			if ( $( '#charitable-wpcode-snippets-list' ).length ) {

				var wpcodeSearch = new List(
					'charitable-wpcode-snippets-list',
					{
						valueNames: [ 'charitable-wpcode-snippet-title' ],
						listClass: 'charitable-wpcode-snippets-list',
					}
				);

				$( '#charitable-wpcode-snippet-search' ).on(
					'keyup search',
					function() {
						app.searchSnippet( this, wpcodeSearch );
					}
				);
			}
			app.events();
		},

		/**
		 * Events.
		 *
		 * @since 1.8.1.6
		 */
		events() {

			$( '.charitable-wpcode-snippet-button' ).on( 'click', app.installSnippet );

			$( '.charitable-wpcode-popup-button' ).on( 'click', 	app.installPlugin );

		},

		/**
		 * Install snippet.
		 *
		 * @since 1.8.1.6
		 */
		installSnippet() {
			const $button = $( this );

			if ( $button.data( 'action' ) === 'edit' ) {
				return;
			}

			const originalWidth = $button.width();
			const $badge = $button.prev( '.charitable-wpcode-snippet-badge' );

			$badge.addClass( 'charitable-wpcode-installing-in-progress' ).text( charitableWpcodeVars.installing_text );
			$button.width( originalWidth ).html( app.spinnerBlue );
		},

		/**
		 * Search snippet.
		 *
		 * @param {Object} searchField The search field html element.
		 * @param {object} wpcodeSearch Addons list (uses List.js).
		 * @since 1.8.1.6
		 */
		searchSnippet( searchField, wpcodeSearch ) {

			var searchTerm = $( searchField ).val();

			/*
			 * Replace dot and comma with space
			 * it is workaround for a bug in listjs library.
			 *
			 * Note: remove when the issue below is fixed:
			 * @see https://github.com/javve/list.js/issues/699
			 */
			searchTerm = searchTerm.replace( /[.,]/g, ' ' );

			// const searchTerm = $( searchField ).val();
			const searchResults = wpcodeSearch.search( searchTerm );
			const $noResultsMessage = $( '#charitable-wpcode-no-results' );

			if ( searchResults.length === 0 ) {
				$noResultsMessage.show();
			} else {
				$noResultsMessage.hide();
			}


		},

		/**
		 * Install or activate WPCode plugin by button click.
		 *
		 * @since 1.8.1.6
		 */
		installPlugin() {
			const $btn = $( this );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			const action = $btn.attr( 'data-action' ),
				plugin = $btn.attr( 'data-plugin' ),
				// eslint-disable-next-line camelcase
				args = JSON.stringify( { overwrite_package: true } ),
				ajaxAction = action === 'activate' ? 'charitable_activate_addon' : 'charitable_install_addon';

			// Fix original button width, add spinner and disable it.
			$btn.width( $btn.width() ).html( app.spinnerWhite ).addClass( 'disabled' );

			const data = {
				action: ajaxAction,
				nonce: charitable_admin.nonce,
				plugin,
				args,
				type: 'plugin'
			};

			$.post( charitable_admin.ajax_url, data )
				.done( function() {
					location.reload();
				} );
		},
	};

	return app;
}( document, window, jQuery ) );

// Initialize.
CharitableWPCode.init();
