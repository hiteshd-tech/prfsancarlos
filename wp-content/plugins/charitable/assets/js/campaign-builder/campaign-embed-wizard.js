/* global campaign_admin_campaign_embed_wizard, CharitableCampaignBuilder, ajaxurl, charitable_builder, wpchar, Charitable */

/**
 * Campaign Embed Wizard function.
 *
 * @since 1.8.0
 */

'use strict';

var CharitableCampaignEmbedWizard = window.CharitableCampaignEmbedWizard || ( function( document, window, $ ) {

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

            //wpchar.debug( 'wizard ready' , 'embed wizard' );

			app.initVars();
			app.events();
		},

		/**
		 * Window load.
		 *
		 * @since 1.8.0
		 */
		load: function() {

            //wpchar.debug( 'embed wizard load' , 'embed wizard' );
            //wpchar.debug( vars , 'embed wizard' );

			// Initialize wizard state in the form builder only.
			if ( vars.isBuilder ) {
				app.initialStateToggle();
			}

			app.initSelectPagesChoicesJS();
		},

		/**
		 * Init variables.
		 *
		 * @since 1.8.0
		 */
		initVars: function() {

            //wpchar.debug('wizard initVars', 'embed wizard' );

			// Caching some DOM elements for further use.
			el = {
				$wizardContainer:   $( '#charitable-admin-campaign-embed-wizard-container' ),
				$wizard:            $( '#charitable-admin-campaign-embed-wizard' ),
				$contentInitial:    $( '#charitable-admin-campaign-embed-wizard-content-initial' ),
				$contentSelectPage: $( '#charitable-admin-campaign-embed-wizard-content-select-page' ),
				$contentCreatePage: $( '#charitable-admin-campaign-embed-wizard-content-create-page' ),
				$sectionBtns:       $( '#charitable-admin-campaign-embed-wizard-section-btns' ),
				$sectionGo:         $( '#charitable-admin-campaign-embed-wizard-section-go' ),
				$newPageTitle:      $( '#charitable-admin-campaign-embed-wizard-new-page-title' ),
				$selectPage:        $( '#campaign-setting-campaign-embed-wizard-choicesjs-select-pages' ),
				$videoTutorial:     $( '#charitable-admin-campaign-embed-wizard-tutorial' ),
				$sectionToggles:    $( '#charitable-admin-campaign-embed-wizard-section-toggles' ),
				$sectionGoBack:     $( '#charitable-admin-campaign-embed-wizard-section-goback' ),
				$shortcode:         $( '#charitable-admin-campaign-embed-wizard-shortcode-wrap' ),
				$shortcodeInput:    $( '#charitable-admin-campaign-embed-wizard-shortcode' ),
				$shortcodeCopy:     $( '#charitable-admin-campaign-embed-wizard-shortcode-copy' ),
				$editorToolTipContainer:       $( '#charitable-admin-campaign-embed-wizard-tooltip-content' ),
				$editorToolTipContainerButton: $( '.charitable-admin-campaign-embed-wizard-done-btn' ),
			};

			el.$selectPageContainer = el.$selectPage.parents( 'span.choicesjs-select-wrap' );

			// Detect the form builder screen and store the flag.
			vars.isBuilder = typeof CharitableCampaignBuilder !== 'undefined';


			// Are the pages exists?
			vars.pagesExists = el.$wizard.data( 'pages-exists' ) === 1;
		},

		/**
		 * Init ChoicesJS for "Select Pages" field in embed.
		 *
		 * @since 1.7.9
		 */
		initSelectPagesChoicesJS: function() {

			if ( el.$selectPage.length <= 0 ) {
				return;
			}

			const useAjax = el.$selectPage.data( 'use_ajax' ) === 1;

			Charitable.Admin.Builder.CharitableChoicesJS.setup(
				el.$selectPage[0],
				{},
				{
					action: 'campaign_admin_campaign_embed_wizard_search_pages_choicesjs',
					nonce: useAjax ? campaign_admin_campaign_embed_wizard.nonce : null,
				}
			);
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.8.0
		 */
		events: function() {

            //wpchar.debug('CharitableCampaignEmbedWizard events', 'embed wizard' );
            //wpchar.debug( el.$wizard , 'embed wizard' );

			// Skip wizard events in the page editor.
			if ( el.$editorToolTipContainer.length ) {
				el.$editorToolTipContainer
					.on( 'click', '.charitable-admin-campaign-embed-wizard-done-btn', app.closeTooltip );
				return;
			}

			// Skip wizard events in the page editor.
			if ( ! el.$wizard.length ) {
				return;
			}

			el.$wizard
				.on( 'click', 'button', app.popupButtonsClick )
				.on( 'click', '.tutorial-toggle', app.tutorialToggle )
				.on( 'click', '.shortcode-toggle', app.shortcodeToggle )
				.on( 'click', '.initialstate-toggle', app.initialStateToggle )
				.on( 'click', '.charitable-admin-popup-close', app.closePopup )
				.on( 'click', '#charitable-admin-campaign-embed-wizard-shortcode-copy', app.copyShortcodeToClipboard )
				.on( 'click', '#charitable-admin-campaign-embed-wizard-tooltip-content .charitable-admin-campaign-embed-wizard-done-btn', app.closeTooltip );
		},

		/**
		 * Close tooltip.
		 *
		 * @since 1.8.1
		 */
		closeTooltip: function() {

			var thisTooltip = $( this ).closest( '.charitable-admin-campaign-embed-wizard-tooltip' ),
				data = {
					action  : 'campaign_admin_campaign_embed_wizard_clear_meta',
					_wpnonce: campaign_admin_campaign_embed_wizard.nonce,
				};

			$.post( ajaxurl, data, function( response ) {
				if ( response.success ) {
					// thisTooltip.remove();
				}
			} );

			thisTooltip.remove();

		},

		/**
		 * Popup buttons events handler.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		popupButtonsClick: function( e ) {

			//wpchar.debug('popupButtonsClick', 'embed wizard' );

			var $btn = $( e.target );

			if ( ! $btn.length ) {
				return;
			}

			var	$div = $btn.closest( 'div' ),
				action = $btn.data( 'action' ) || '',
				labelText = $btn.text();

			el.$contentInitial.hide();

			//wpchar.debug('popupButtonsClick with action ' + action , 'embed wizard' );

			switch ( action ) {

				// Select existing page.
				case 'select-page':
					el.$newPageTitle.hide();
					el.$contentSelectPage.show();
					break;

				// Create a new page.
				case 'create-page':
					el.$selectPageContainer.hide();
					el.$contentCreatePage.show();
					break;

				// Let's Go!
				case 'go':
					if ( el.$selectPageContainer.is( ':visible' ) && el.$selectPage.val() === '' ) {
						return;
					}
					//wpchar.debug('popupButtonsClick disabled button' , 'embed wizard' );
					//wpchar.debug('popupButtonsClick disabled button text: ' + labelText , 'embed wizard' );
					$btn.prop( 'disabled', true );
					$btn.text( charitable_builder.loading + '...' );
					app.saveFormAndRedirect( $btn, labelText );

					return;
			}

			$div.hide();
			$div.next().fadeIn();
			el.$sectionToggles.hide();
			el.$sectionGoBack.fadeIn();

			// Set focus to the field that is currently displayed.
			$.each( [ el.$selectPage, el.$newPageTitle ], function() {
				if ( this.is( ':visible' ) ) {
					this.trigger( 'focus' );
				}
			} );

		},

		/**
		 * Provide a link but later provide a video tutorial inside popup.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		tutorialToggle: function( e ) {

			e.preventDefault();

			// open a new window and goto a url.
			window.open( 'https://www.wpcharitable.com/documentation/creating-a-campaign-page/', '_blank' );

			// close the embed popup.
			app.closePopup();
		},

		/**
		 * Toggle shortcode input field.
		 *
		 * @since 1.8.0
		 *
		 * @param {object} e Event object.
		 */
		shortcodeToggle: function( e ) {

			e.preventDefault();

			el.$videoTutorial.hide();
			el.$shortcodeInput.val( '[campaign id="' + vars.formId + '"]' );
			el.$shortcode.toggle();
		},

		/**
		 * Copies the shortcode embed code to the clipboard.
		 *
		 * @since 1.8.0
		 */
		copyShortcodeToClipboard: function() {

			// Remove disabled attribute, select the text, and re-add disabled attribute.
			$( '#charitable-admin-campaign-embed-wizard-shortcode' )
				.prop( 'disabled', false )
				.select()
				.prop( 'disabled', true );

			// Copy it.
			document.execCommand( 'copy' );

			var $icon = el.$shortcodeCopy.find( 'i' );

			// Add visual feedback to copy command.
			$icon.removeClass( 'fa-files-o' ).addClass( 'fa-check' );

			// Reset visual confirmation back to default state after 2.5 sec.
			window.setTimeout( function() {
				$icon.removeClass( 'fa-check' ).addClass( 'fa-files-o' );
			}, 2500 );
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
			el.$contentSelectPage.hide();
			el.$contentCreatePage.hide();
			el.$selectPageContainer.show();
			el.$newPageTitle.show();
			el.$sectionBtns.show();
			el.$sectionGo.hide();
		},

		/**
		 * Save the form and redirect to form embed page.
		 *
		 * @since 1.8.0
		 */
		saveFormAndRedirect: function( $button = false, labelText = false ) {

			//wpchar.debug('saveFormAndRedirect' , 'embed wizard' );

			// Just redirect if no need to save the form.
			if ( ! vars.isBuilder || CharitableCampaignBuilder.formIsSaved() ) {
				app.embedPageRedirect( $button, labelText );
				return;
			}

			$.confirm( {
				title: false,
				content: charitable_builder.exit_confirm,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				closeIcon: true,
				buttons: {
					confirm: {
						text: charitable_builder.save_embed,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action: function() {
							CharitableCampaignBuilder.formSave().done( app.embedPageRedirect );
						},
					},
					cancel: {
						text: charitable_builder.embed,
						action: function() {
							CharitableCampaignBuilder.setCloseConfirmation( false );
							app.embedPageRedirect();
						},
					},
				},
				onClose: function() {
					el.$sectionGo.find( 'button' ).prop( 'disabled', false );
				},
			} );
		},

		/**
		 * Prepare data for requesting redirect URL.
		 *
		 * @since 1.8.0
		 *
		 * @returns {object} AJAX data object.
		 */
		embedPageRedirectAjaxData: function() {

			var data = {
				action  : 'campaign_admin_campaign_embed_wizard_embed_page_url',
				_wpnonce: campaign_admin_campaign_embed_wizard.nonce,
				formId: vars.formId,
			};

			if ( el.$selectPageContainer.is( ':visible' ) ) {
				data.pageId = el.$selectPage.val();
			}

			if ( el.$newPageTitle.is( ':visible' ) ) {
				data.pageTitle = el.$newPageTitle.val();
			}

			return data;
		},

		/**
		 * Redirect to form embed page.
		 *
		 * @since 1.8.0
		 */
		embedPageRedirect: function( $button = false, labelText = false ) {

			//wpchar.debug('embedPageRedirect' , 'embed wizard' );
			//wpchar.debug('embedPageRedirect labelText:' + labelText , 'embed wizard' );

			var data = app.embedPageRedirectAjaxData();

			// Exit if no one page is selected.
			if ( typeof data.pageId !== 'undefined' && data.pageId === '' ) {
				return;
			}

			$.post( ajaxurl, data, function( response ) {
				//wpchar.debug('embedPageRedirect response ' + response , 'embed wizard' );
				if ( response.success ) {
					// open a new tab and goto a url.
					window.open( response.data, '_blank' );
					//wpchar.debug('embedPageRedirect ' + $button , 'embed wizard' );
					//wpchar.debug('embedPageRedirect ' + labelText , 'embed wizard' );
					if ( $button ) {
						//wpchar.debug('embedPageRedirect disabling false: ' + $button , 'embed wizard' );
						$button.prop( 'disabled', false );
					}
					if ( labelText !== false && $button ) {
						$button.html( labelText );
					}
					// close the embed popup.
					app.closePopup();
				}
			} );
		},

		/**
		 * Display wizard popup.
		 *
		 * @since 1.8.0
		 *
		 * @param {numeric} openFormId Form ID to embed. Used only if called outside the form builder.
		 */
		openPopup: function( openFormId ) {

			openFormId = openFormId || 0;

            //wpchar.debug('openPopup opening with ' + openFormId , 'embed wizard' );

			vars.formId = vars.isBuilder ? $( '#charitable-builder-form' ).data( 'id' ) : openFormId;

			// Re-init sections.
			// if ( el.$selectPage.length === 0 ) {
			// 	el.$sectionBtns.hide();
			// 	el.$sectionGo.show();
			// } else {
				el.$sectionBtns.show();
				el.$sectionGo.hide();
			// }
			el.$newPageTitle.show();
			el.$selectPageContainer.show();

			el.$wizardContainer.fadeIn();
		},

		/**
		 * Close wizard popup.
		 *
		 * @since 1.8.0
		 */
		closePopup: function() {

            //wpchar.debug('closePopup', 'embed wizard' );
            //wpchar.debug( el.$wizardContainer , 'embed wizard' );

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

			var $dot = $( '<span class="charitable-admin-campaign-embed-wizard-dot">&nbsp;</span>' ),
				isGutengerg = app.isGutenberg(),
				anchor = isGutengerg ? '.block-editor .edit-post-header' : '#wp-content-editor-tools .charitable-insert-form-button';

			var tooltipsterArgs = {
				content          : $( '#charitable-admin-campaign-embed-wizard-tooltip-content' ),
				trigger          : 'custom',
				interactive      : true,
				animationDuration: 0,
				delay            : 0,
				theme            : [ 'tooltipster-default', 'charitable-admin-campaign-embed-wizard' ],
				side             : isGutengerg ? 'bottom' : 'right',
				distance         : 3,
				functionReady    : function( instance, helper ) { // eslint-disable-line no-unused-vars

					instance._$tooltip.on( 'click', 'button', function() {

						instance.close();
						$( '.charitable-admin-campaign-embed-wizard-dot' ).remove();
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
CharitableCampaignEmbedWizard.init();
