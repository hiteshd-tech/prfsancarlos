/* global charitable_admin, jconfirm, wpCookies, Choices, List */

;( function( $ ) {

	'use strict';

	// Global settings access.
	var s;

	// Admin object.
	var CharitableDirAdmin = {

		// Settings.
		settings: {
			iconActivate: '', // '<i class="fa fa-toggle-on fa-flip-horizontal" aria-hidden="true"></i>',
			iconDeactivate: '', // '<i class="fa fa-toggle-on" aria-hidden="true"></i>',
			iconInstall: '', // '<i class="fa fa-cloud-download" aria-hidden="true"></i>',
			iconSpinner: 'Standby...', // '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>',
			mediaFrame: false,
		},

		/**
		 * Start the engine.
		 *
		 * @since 1.3.9
		 */
		init: function() {

			// Settings shortcut.
			s = this.settings;

			// Document ready.
			$( CharitableDirAdmin.ready );

			// Entries Single (Details).
			// CharitableDirAdmin.initEntriesSingle();

			// // Entries List.
			// CharitableDirAdmin.initEntriesList();

			// // Welcome activation.
			// CharitableDirAdmin.initWelcome();

			// Addons List.
			$( document ).on( 'CharitableDirReady', CharitableDirAdmin.initAddons );

			// Settings.
			// CharitableDirAdmin.initSettings();

			// // Tools.
			// CharitableDirAdmin.initTools();

			// // Upgrades (Tools view).
			// CharitableDirAdmin.initUpgrades();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.3.9
		 */
		ready: function() {

			// Action available for each binding.
			$( document ).trigger( 'CharitableDirReady' );

			// if there is a value already in the input text box search, then simulate a click to filter the addon list.
			if ( $( '#charitable-admin-addons-search' ).val() ) {
				$( '#charitable-admin-addons-search' ).trigger( 'keyup' );
			}


		},

		//--------------------------------------------------------------------//
		// Addons List.
		//--------------------------------------------------------------------//

		/**
		 * Element bindings for Addons List page.
		 *
		 * @since 1.3.9
		 */
		initAddons: function() {

			// Only run on the addons page.
			if ( ! $( '#charitable-admin-addons' ).length ) {
				return;
			}

			// Addons searching.
			if ( $( '#charitable-admin-addons-list' ).length ) {
				var addonSearch = new List(
					'charitable-admin-addons-list',
					{
						valueNames: [ 'addon-link' ],
					}
				);

				$( '#charitable-admin-addons-search' ).on(
					'keyup search',
					function() {
						CharitableDirAdmin.updateAddonSearchResult( this, addonSearch );
					}
				);
			}

			// Toggle an addon state.
			$( document ).on( 'click', '#charitable-admin-addons .addon-item button', function( event ) {

				event.preventDefault();

				if ( $( this ).hasClass( 'disabled' ) ) {
					return false;
				}

				CharitableDirAdmin.addonToggle( $( this ) );
			} );
		},

		/**
		 * Handle addons search field operations.
		 *
		 * @since 1.7.4
		 *
		 * @param {object} searchField The search field html element.
		 * @param {object} addonSearch Addons list (uses List.js).
		 */
		updateAddonSearchResult: function( searchField, addonSearch ) {

			var searchTerm = $( searchField ).val(),
				$heading   = $( '#addons-heading' );

			// if ( searchTerm ) {
			// 	$heading.text( charitable_admin.addon_search );
			// } else {
			// 	$heading.text( $heading.data( 'text' ) );
			// }

			/*
			 * Replace dot and comma with space
			 * it is workaround for a bug in listjs library.
			 *
			 * Note: remove when the issue below is fixed:
			 * @see https://github.com/javve/list.js/issues/699
			 */
			searchTerm = searchTerm.replace( /[.,]/g, ' ' );

			addonSearch.search( searchTerm );
		},

		/**
		 * Change plugin/addon state.
		 *
		 * @since 1.6.3
		 *
		 * @param {string}   plugin     Plugin slug or URL for download.
		 * @param {string}   state      State status activate|deactivate|install.
		 * @param {string}   pluginType Plugin type addon or plugin.
		 * @param {Function} callback   Callback for get result from AJAX.
		 */
		setAddonState: function( plugin, state, pluginType, callback ) {

			var actions = {
					'activate': 'charitable_activate_addon',
					'install': 'charitable_install_addon',
					'deactivate': 'charitable_deactivate_addon',
				},
				action = actions[ state ];

			if ( ! action ) {
				return;
			}

			var data = {
				action: action,
				nonce: charitable_admin.nonce,
				plugin: plugin,
				type: pluginType,
			};

			$.post( charitable_admin.ajax_url, data, function( res ) {

				callback( res );
			} ).fail( function( xhr ) {

				console.log( xhr.responseText );
			} );
		},

		/**
		 * Toggle addon state.
		 *
		 * @since 1.3.9
		 */
		addonToggle: function( $btn ) {

			var $addon = $btn.closest( '.addon-item' ),
				plugin = $btn.attr( 'data-plugin' ),
				pluginType = $btn.attr( 'data-type' ),
				state,
				cssClass,
				stateText,
				buttonText,
				errorText,
				successText;

			if ( $btn.hasClass( 'status-go-to-url' ) ) {

				// Open url in new tab.
				window.open( $btn.attr( 'data-plugin' ), '_blank' );
				return;
			}

			$btn.prop( 'disabled', true ).addClass( 'loading' );
			$btn.html( s.iconSpinner );

			if ( $btn.hasClass( 'status-active' ) ) {

				// Deactivate.
				state = 'deactivate';
				cssClass = 'status-installed';
				if ( pluginType === 'plugin' ) {
					cssClass += ' button button-secondary';
				}
				stateText = charitable_admin.addon_inactive;
				buttonText = charitable_admin.addon_activate;
				errorText  = charitable_admin.addon_deactivate;
				if ( pluginType === 'addon' ) {
					buttonText = s.iconActivate + buttonText;
					errorText  = s.iconDeactivate + errorText;
				}

			} else if ( $btn.hasClass( 'status-installed' ) ) {

				// Activate.
				state = 'activate';
				cssClass = 'status-active';
				if ( pluginType === 'plugin' ) {
					cssClass += ' button button-secondary disabled';
				}
				stateText = charitable_admin.addon_active;
				buttonText = charitable_admin.addon_deactivate;
				if ( pluginType === 'addon' ) {
					buttonText = s.iconDeactivate + buttonText;
					errorText  = s.iconActivate + charitable_admin.addon_activate;
				} else if ( pluginType === 'plugin' ) {
					buttonText = charitable_admin.addon_activated;
					errorText  = charitable_admin.addon_activate;
				}

			} else if ( $btn.hasClass( 'status-missing' ) ) {

				// Install & Activate.
				state = 'install';
				cssClass = 'status-active';
				if ( pluginType === 'plugin' ) {
					cssClass += ' button disabled';
				}
				stateText = charitable_admin.addon_active;
				buttonText = charitable_admin.addon_activated;
				errorText  = s.iconInstall;
				if ( pluginType === 'addon' ) {
					buttonText = s.iconActivate + charitable_admin.addon_deactivate;
					errorText += charitable_admin.addon_install;
				}

			} else {
				return;
			}

			// eslint-disable-next-line complexity
			CharitableDirAdmin.setAddonState( plugin, state, pluginType, function( res ) {

				if ( res.success ) {
					if ( 'install' === state ) {
						$btn.attr( 'data-plugin', res.data.basename );
						successText = res.data.msg;
						if ( ! res.data.is_activated ) {
							stateText  = charitable_admin.addon_inactive;
							buttonText = 'addon' === pluginType ? charitable_admin.addon_activate : s.iconActivate + charitable_admin.addon_activate;
							cssClass   = 'addon' === pluginType ? 'status-installed button button-secondary' : 'status-installed';
						}
					} else {
						successText = res.data;
					}
					$addon.find( '.actions' ).append( '<div class="msg success">' + successText + '</div>' );
					$addon.find( 'span.status-label' )
						.removeClass( 'status-active status-installed status-missing' )
						.addClass( cssClass )
						.removeClass( 'button button-primary button-secondary disabled' )
						.text( stateText );
					$btn
						.removeClass( 'status-active status-installed status-missing' )
						.removeClass( 'button button-primary button-secondary disabled' )
						.addClass( cssClass ).html( buttonText );

					// Check if this is Charitable Pro activation and redirect if needed.
					if ( 'activate' === state && plugin === 'charitable-pro/charitable.php' && $btn.attr( 'data-redirect' ) ) {
						setTimeout( function() {
							window.location.href = $btn.attr( 'data-redirect' );
						}, 1500 );
					}
				} else {
					if ( 'object' === typeof res.data ) {
						if ( pluginType === 'addon' ) {
							$addon.find( '.actions' ).append( '<div class="msg error"><p>' + charitable_admin.addon_error + '</p></div>' );
						} else {
							$addon.find( '.actions' ).append( '<div class="msg error"><p>' + charitable_admin.plugin_error + '</p></div>' );
						}
					} else {

						if ( 'string' === typeof res ) {
							var err_data = JSON.parse( res );

							if ( 'string' === typeof err_data.error ) {
								$addon.find( '.actions' ).append( '<div class="msg error"><p>' + err_data.error + '</p></div>' );
							} else {
								$addon.find( '.actions' ).append( '<div class="msg error"><p>There has been an error.</p></div>' );
							}
						}
					}
					if ( 'install' === state && 'addon' === pluginType ) {
						$btn.addClass( 'status-go-to-url' ).removeClass( 'status-missing' );
					}
					$btn.html( errorText );
				}

				$btn.prop( 'disabled', false ).removeClass( 'loading' );

				if ( ! $addon.find( '.actions' ).find( '.msg.error' ).length ) {
					setTimeout( function() {

						$( '.addon-item .msg' ).remove();
					}, 3000 );
				}
			} );
		},

		/**
		 * Get query string in a URL.
		 *
		 * @since 1.3.9
		 */
		getQueryString: function( name ) {

			var match = new RegExp( '[?&]' + name + '=([^&]*)' ).exec( window.location.search );
			return match && decodeURIComponent( match[1].replace( /\+/g, ' ' ) );
		},

		/**
		 * Debug output helper.
		 *
		 * @since 1.4.4
		 * @param msg
		 */
		debug: function( msg ) {

			if ( CharitableDirAdmin.isDebug() ) {
				if ( typeof msg === 'object' || msg.constructor === Array ) {
					console.log( 'Charitable Debug:' );
					console.log( msg );
				} else {
					console.log( 'Charitable Debug: ' + msg );
				}
			}
		},

		/**
		 * Is debug mode.
		 *
		 * @since 1.4.4
		 */
		isDebug: function() {

			return ( window.location.hash && '#charitabledebug' === window.location.hash );
		},
	};

	CharitableDirAdmin.init();

	window.CharitableDirAdmin = CharitableDirAdmin;

} )( jQuery );
