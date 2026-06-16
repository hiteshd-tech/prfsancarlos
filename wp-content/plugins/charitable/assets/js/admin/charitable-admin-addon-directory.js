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

			// Addons List.
			$( document ).on( 'CharitableDirReady', CharitableDirAdmin.initAddons );
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

			// Addon page collapsible sections.
			function setup_addon_sections() {
				function setCookie(name, value, days) {
					var expires = "";
					if (days) {
						var date = new Date();
						date.setTime(date.getTime() + (days*24*60*60*1000));
						expires = "; expires=" + date.toUTCString();
					}
					document.cookie = name + "=" + (value || "")  + expires + "; path=/";
				}

				function getCookie(name) {
					var nameEQ = name + "=";
					var ca = document.cookie.split(';');
					for(var i=0;i < ca.length;i++) {
						var c = ca[i];
						while (c.charAt(0)==' ') c = c.substring(1,c.length);
						if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
					}
					return null;
				}

				var $collapsible_headers = $('.charitable-addons-section-header:not(.charitable-addons-section-header-featured)');
				var open_sections = getCookie('charitable_open_addon_sections');
				open_sections = open_sections ? JSON.parse(open_sections) : [];

				// Make sure 'featured' is not in the cookie for some legacy reason.
				var featured_index = open_sections.indexOf('featured');
				if (featured_index > -1) {
					open_sections.splice(featured_index, 1);
					setCookie('charitable_open_addon_sections', JSON.stringify(open_sections), 7);
				}

				$collapsible_headers.each(function() {
					var section_id = $(this).data('section');
					if (open_sections.includes(section_id)) {
						$(this).addClass('open');
						$('#charitable-addons-' + section_id).show();
					}
				});

				$collapsible_headers.on('click', function() {
					var section_id = $(this).data('section');
					var content = $('#charitable-addons-' + section_id);
					var open_sections = getCookie('charitable_open_addon_sections');
					open_sections = open_sections ? JSON.parse(open_sections) : [];

					$(this).toggleClass('open');
					content.slideToggle();

					if ($(this).hasClass('open')) {
						if (!open_sections.includes(section_id)) {
							open_sections.push(section_id);
						}
					} else {
						var index = open_sections.indexOf(section_id);
						if (index > -1) {
							open_sections.splice(index, 1);
						}
					}
					setCookie('charitable_open_addon_sections', JSON.stringify(open_sections), 7);
				});
			}

			setup_addon_sections();

			// Addons searching.
			if ( $( '#charitable-addons' ).length ) {
				$( '#charitable-admin-addons-search' ).on(
					'keyup search input',
					function() {
						CharitableDirAdmin.updateAddonSearchResult( this );
					}
				);

				// Store search term in session storage for persistence
				$( '#charitable-admin-addons-search' ).on(
					'input',
					function() {
						var searchTerm = $( this ).val();
						if ( searchTerm ) {
							sessionStorage.setItem( 'charitable_addons_search', searchTerm );
						} else {
							sessionStorage.removeItem( 'charitable_addons_search' );
						}
					}
				);

				// Restore search term on page load
				var savedSearch = sessionStorage.getItem( 'charitable_addons_search' );
				if ( savedSearch ) {
					$( '#charitable-admin-addons-search' ).val( savedSearch ).trigger( 'keyup' );
				}
			}

			// Toggle an addon state.
			$( document ).on( 'click', '#charitable-admin-addons .charitable-addons-list-item  button', function( event ) {

				event.preventDefault();

				if ( $( this ).hasClass( 'disabledd6' ) ) {
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
		updateAddonSearchResult: function( searchField ) {

			var searchTerm = $( searchField ).val();

			/*
			 * Replace dot and comma with space
			 * it is workaround for a bug in listjs library.
			 *
			 * Note: remove when the issue below is fixed:
			 * @see https://github.com/javve/list.js/issues/699
			 */
			searchTerm = searchTerm.replace( /[.,]/g, ' ' );

			// Custom search implementation for the current HTML structure
			CharitableDirAdmin.performCustomSearch( searchTerm );

			// Add highlighting to search results
			CharitableDirAdmin.highlightSearchTerms( searchTerm );
		},

		/**
		 * Perform custom search across all addon items.
		 *
		 * @since 1.7.4
		 *
		 * @param {string} searchTerm The search term to filter by.
		 */
		performCustomSearch: function( searchTerm ) {

			var $allAddonItems = $( '#charitable-addons .charitable-addons-list-item' );
			var searchWords = searchTerm.toLowerCase().split( ' ' ).filter( function( word ) {
				return word.length > 0;
			} );

			if ( searchWords.length === 0 ) {
				// Show all items when search is empty
				$allAddonItems.show();
				$( '.charitable-addons-section' ).show();
				return;
			}

			var hasVisibleItems = false;

			$allAddonItems.each( function() {
				var $item = $( this );
				var $title = $item.find( '.addon-link' );
				var $description = $item.find( '.addon-description' );

				var titleText = $title.text().toLowerCase();
				var descriptionText = $description.text().toLowerCase();

				var matches = false;

				// Check if any search word matches title or description
				searchWords.forEach( function( word ) {
					if ( titleText.indexOf( word ) !== -1 || descriptionText.indexOf( word ) !== -1 ) {
						matches = true;
					}
				} );

				if ( matches ) {
					$item.show();
					hasVisibleItems = true;
				} else {
					$item.hide();
				}
			} );

			// Show/hide sections based on whether they have visible items
			$( '.charitable-addons-section' ).each( function() {
				var $section = $( this );
				var $visibleItems = $section.find( '.charitable-addons-list-item:visible' );

				if ( $visibleItems.length > 0 ) {
					$section.show();
					// Ensure the section content is visible
					$section.find( '.charitable-addons-section-content' ).show();
					$section.find( '.charitable-addons-section-header' ).addClass( 'open' );
				} else {
					$section.hide();
				}
			} );
		},

		/**
		 * Highlight search terms in the results.
		 *
		 * @since 1.7.4
		 *
		 * @param {string} searchTerm The search term to highlight.
		 */
		highlightSearchTerms: function( searchTerm ) {

			if ( ! searchTerm ) {
				// Remove all highlighting when search is cleared
				$( '.addon-link, .addon-description' ).each( function() {
					var $element = $( this );
					$element.html( $element.text() );
				} );
				return;
			}

			// Split search term into words for highlighting
			var searchWords = searchTerm.toLowerCase().split( ' ' ).filter( function( word ) {
				return word.length > 0;
			} );

			if ( searchWords.length === 0 ) {
				return;
			}

			// Only highlight visible items
			$( '.charitable-addons-list-item:visible .addon-link, .charitable-addons-list-item:visible .addon-description' ).each( function() {
				var $element = $( this );
				var originalText = $element.text();
				var highlightedText = originalText;

				// Highlight each search word
				searchWords.forEach( function( word ) {
					if ( word.length > 0 ) {
						var regex = new RegExp( '(' + word.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + ')', 'gi' );
						highlightedText = highlightedText.replace( regex, '<mark>$1</mark>' );
					}
				} );

				// Update the element with highlighted text
				if ( highlightedText !== originalText ) {
					$element.html( highlightedText );
				}
			} );
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

			// Check if button is disabled or has invalid plugin data
			if ( $btn.prop( 'disabled' ) || $btn.attr( 'data-plugin' ) === 'invalid' ) {
				return;
			}

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
				if ( pluginType === 'plugin' || pluginType === 'addon' ) {
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
				if ( pluginType === 'plugin' || pluginType === 'addon' ) {
					cssClass += ' button';
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
				if ( pluginType === 'plugin' || pluginType === 'addon' ) {
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
						} else {
							// Addon was installed and activated, so it should be status-active without disabled
							cssClass = 'status-active button';
							stateText = charitable_admin.addon_active;
							buttonText = charitable_admin.addon_deactivate;
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
