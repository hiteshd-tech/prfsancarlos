/* global campaign_admin_campaign_congrats_wizard, CharitableCampaignBuilder, ajaxurl, charitable_builder, wpchar, Charitable */

/**
 * Campaign Congrats Wizard function.
 *
 * @since 1.8.0
 */

'use strict';

var CharitableCampaignCongratsWizard = window.CharitableCampaignCongratsWizard || ( function( document, window, $ ) {

	/**
	 * Elements.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	var el = {};

	/**
	 * Runtime variables.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	var vars = {
		formId:                  0,
		isBuilder:               false,
		lastEmbedSearchPageTerm: '',
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.0
		 */
		init: function() {

			$( app.ready );
			$( window ).on( 'load', function() {

				// in case of jQuery 3.+ we need to wait for an `ready` event first.
				if ( typeof $.ready.then === 'function' ) {
					$.ready.then( app.load );
				} else {
					app.load();
				}
			} );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.0
		 */
		ready: function() {

            wpchar.debug( 'wizard ready' , 'congrats wizard' );

			app.initVars();
			app.events();
		},

		/**
		 * Window load.
		 *
		 * @since 1.8.0
		 */
		load: function() {

            wpchar.debug( 'congrats wizard load' , 'congrats wizard' );
            wpchar.debug( vars , 'congrats wizard' );

			// Initialize wizard state in the form builder only.
			if ( vars.isBuilder ) {
				app.initialStateToggle();
			}

		},

		/**
		 * Init variables.
		 *
		 * @since 1.8.0
		 */
		initVars: function() {

            wpchar.debug('wizard initVars', 'congrats wizard' );

			// Caching some DOM elements for further use.
			el = {
				$wizardContainer:   $( '#charitable-admin-campaign-congrats-wizard-container' ),
				$wizard:            $( '#charitable-admin-campaign-congrats-wizard' ),
				$contentInitial:    $( '#charitable-admin-campaign-congrats-wizard-content-initial' ),
			};

			// Detect the form builder screen and store the flag.
			vars.isBuilder = typeof CharitableCampaignBuilder !== 'undefined';

			// Are the pages exists?
			vars.pagesExists = el.$wizard.data( 'pages-exists' ) === 1;
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.8.0
		 */
		events: function() {

            wpchar.debug('CharitableCampaignCongratsWizard events', 'congrats wizard' );
            wpchar.debug( el.$wizard , 'congrats wizard' );

			// Skip wizard events in the page editor.
			if ( ! el.$wizard.length ) {
				return;
			}

			el.$wizard
				.on( 'click', '.charitable-admin-launch-embed-wizard', app.embedPageRedirect )
                .on( 'click', '.charitable-admin-popup-btn-1', app.gotoLink1 )
                .on( 'click', '.charitable-admin-popup-btn-2', app.gotoLink2 )
                .on( 'click', '.charitable-admin-popup-btn-3', app.gotoLink3 )
				.on( 'click', '.charitable-admin-popup-close', app.closePopup );
		},


        openInNewTab: function( url ) {
            var win = window.open(url, '_blank');
            win.focus();
        },

        gotoLink1: function( e ) {
			e.preventDefault();
			e.stopPropagation();
            // open a url in a new tab.
            app.openInNewTab( 'https://www.wpcharitable.com/extensions/charitable-newsletter-connect/');

        },


        gotoLink2: function( e ) {
			e.preventDefault();
			e.stopPropagation();
            // open a url in a new tab.
            app.openInNewTab( 'https://wpcharitable.com/extensions/charitable-ambassadors/');

        },

        gotoLink3: function( e ) {
			e.preventDefault();
			e.stopPropagation();
            // open a url in a new tab.
            app.openInNewTab( 'https://www.wpcharitable.com/extensions/charitable-automation-connect/');

        },

		/**
		 * Launch embed wizard.
		 *
		 * @since 1.8.0
		 */
		embedPageRedirect: function() {

			app.closePopup();

            CharitableCampaignEmbedWizard.openPopup();

		},

		/**
		 * Toggle initial state.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		initialStateToggle: function( e ) {

			if ( e ) {
				e.preventDefault();
			}

			el.$contentInitial.show();

		},

		/**
		 * Display wizard popup.
		 *
		 * @since 1.8.0
		 *
		 * @param {numeric} openFormId Form ID to embed. Used only if called outside the form builder.
		 */
		openPopup: function( openFormId, campaignURL ) {

			openFormId = openFormId || 0;

            wpchar.debug('openPopup opening with ' + openFormId , 'congrats wizard' );

			// update the link in the form with the campaign URL.
			$( 'a.charitable-admin-congrats-popup-btn' ).attr( 'href', campaignURL );

			vars.formId = vars.isBuilder ? $( '#charitable-builder-form' ).data( 'id' ) : openFormId;

            if ( typeof confetti === 'function') {
                confetti({
                    particleCount: 150,
                    zIndex: 9999999999,
                });
            }

			el.$wizardContainer.fadeIn();
		},

		/**
		 * Close wizard popup.
		 *
		 * @since 1.8.0
		 */
		closePopup: function() {

            wpchar.debug('closePopup', 'congrats wizard' );
            wpchar.debug( el.$wizardContainer , 'congrats wizard' );

			el.$wizardContainer.fadeOut();
			app.initialStateToggle();

			$( document ).trigger( 'charitableWizardPopupClose' );
		},

		/**
		 * Init embed page tooltip.
		 *
		 * @since 1.8.0
		 */
		initTooltip: function() {

			if ( typeof $.fn.tooltipster === 'undefined' ) {
				return;
			}

			var $dot = $( '<span class="charitable-admin-campaign-congrats-wizard-dot">&nbsp;</span>' ),
				isGutengerg = app.isGutenberg(),
				anchor = isGutengerg ? '.block-editor .edit-post-header' : '#wp-content-editor-tools .charitable-insert-form-button';

			var tooltipsterArgs = {
				content          : $( '#charitable-admin-campaign-congrats-wizard-tooltip-content' ),
				trigger          : 'custom',
				interactive      : true,
				animationDuration: 0,
				delay            : 0,
				theme            : [ 'tooltipster-default', 'charitable-admin-campaign-congrats-wizard' ],
				side             : isGutengerg ? 'bottom' : 'right',
				distance         : 3,
				functionReady    : function( instance, helper ) { // eslint-disable-line no-unused-vars

					instance._$tooltip.on( 'click', 'button', function() {

						instance.close();
						$( '.charitable-admin-campaign-congrats-wizard-dot' ).remove();
					} );

					instance.reposition();
				},
			};

			$dot.insertAfter( anchor ).tooltipster( tooltipsterArgs ).tooltipster( 'open' );
		},

		/**
		 * Check if we're in Gutenberg editor.
		 *
		 * @since 1.8.0
		 *
		 * @returns {boolean} Is Gutenberg or not.
		 */
		isGutenberg: function() {

			return typeof wp !== 'undefined' && Object.prototype.hasOwnProperty.call( wp, 'blocks' ); // eslint-disable-line no-undef
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line no-undef

// Initialize.
CharitableCampaignCongratsWizard.init();
