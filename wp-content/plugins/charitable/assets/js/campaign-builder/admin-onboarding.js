/* global Charitable, CharitableCampaignBuilder, wpchar */
/**
 * Charitable Onboarding function.
 *
 * @since 1.8.1.12
 */
'use strict';

var CharitableOnboarding = window.CharitableOnboarding || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.1.12
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1.12
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
		 * @since 1.8.1.12
		 */
		ready: function() {
		},

		/**
		 * Window load.
		 *
		 * @since 1.8.1.12
		 */
		load: function() {

		},

		/**
		 * Initial setup.
		 *
		 * @since 1.8.1.12
		 */
		setup: function() {

            if ( typeof charitable_admin_builder_onboarding === 'undefined' || typeof charitable_admin_builder_onboarding.option === 'undefined' || typeof charitable_admin_builder_onboarding.option.status === 'undefined' ) { // eslint-disable-line
				return;
			}

			// possible values: 'init', 'started', 'completed', 'skipped'.

			if ( charitable_admin_builder_onboarding.option.status === 'init' ) { // eslint-disable-line
				app.clearLocalStorage();
				app.showWelcomePopup();
			} else if ( charitable_admin_builder_onboarding.option.status === 'started' ) { // eslint-disable-line
				if ( app.loadStep() >= 1 ) {
					app.clearLocalStorage();
					app.stepCompleted( 1 );
					app.eventSelectTemplate();
					app.updateTooltipUI();
				}
			}

		},

		/**
		 * Register JS events.
		 *
		 * @since 1.8.1.12
		 */
		events: function() {

			// Start the Onboarding.
			$( '#charitable-onboarding-welcome-builder-popup' ).on( 'click', 'button', function() {
                app.startOnboarding();
            } );

			// Step 1.
			$( '.charitable-onboarding-step1-done' ).on( 'click', function() {
				app.stepCompleted( 1 );
			} );

			$( '#charitable-builder' )

				// Register select template event when the setup panel is ready.
				.on( 'charitableBuilderSetupReady', function() {
					app.eventSelectTemplate();
				} )

				// Restore tooltips when switching builder panels/sections.
				.on( 'charitablePanelSwitch charitablePanelSectionSwitch', function() {
					app.updateTooltipUI();
				} );

			// Step 3 - Add fields.
			$( '.charitable-onboarding-step3-done' ).on( 'click', function() {
				app.stepCompleted( 3 );
			} );

			// Step 4 - Save Campaign.
			$( '.charitable-onboarding-step4-done' ).on( 'click', function() {
				app.stepCompleted( 4 );
                app.gotoDraftPublishStep();
			} );

			// Step 5 - Draft And Publish..
			$( '.charitable-onboarding-step5-done' ).on( 'click', function() {
                app.undoDraftPublishStep();
                app.stepCompleted( 5 );
			} );

			// Step 6 - Publish and View.
			$( '.charitable-onboarding-step6-done' ).on( 'click', function() {
				app.stepCompleted( 6 );
			} );

			// Step 7 - Embed.
			$( '.charitable-onboarding-step7-done' ).on( 'click', function() {
				app.stepCompleted( 7 );
                app.gotoSettingsStep();
			} );

            $( '.charitable-onboarding-step8-done' ).on( 'click', function() {
				app.stepCompleted( 8 );
                CharitableCampaignBuilder.panelSwitch( 'design' );
                app.showClosingRemarksPopup();
			} );


            if ( app.loadStep() == 4 ) {
                app.gotoDraftPublishStep();
            }

            if ( app.loadStep() == 7 ) {
                app.gotoSettingsStep();
            }

			// Step 4 - Notifications.
			$( document ).on( 'click', '.charitable-onboarding-step16-done', app.showEmbedPopup );

			// Tooltipster ready.
			$.tooltipster.on( 'ready', app.tooltipsterReady );

			// Move to step 3 if onboarding is forced and existing form is opened.
			$( document ).on( 'charitableCampaignFormScreen', function() {
				//if ( $( '.charitable-panel-fields-button' ).hasClass( 'active' ) && app.loadStep() <= 2 ) {
					app.stepCompleted( 1 );
					app.stepCompleted( 2 );
				// }
			} );

            $( '#charitable-onboarding-goodbye-builder-popup .charitable-onboarding-popup-btn' ).on( 'click', function() {
                app.saveOnboardingOption( { status: 'completed' } );
                // close the popup.
                $( '#charitable-onboarding-goodbye-builder-popup' ).fadeOut();
                $( '.charitable-onboarding-popup-container' ).fadeOut();
            });

		},

		/**
		 * Register select template event.
		 *
		 * @since 1.6.8
		 */
		eventSelectTemplate: function() {

			$( '#charitable-panel-setup' )
				// Step 2 - Select the Form template.
				.off( 'click', '.charitable-template-select' ) // Intercept Form Builder's form template selection and apply own logic.
				.on( 'click', '.charitable-template-select', function( e ) {
					app.builderTemplateSelect( this, e );
				} );

		},

		/**
		 * Start the Onboarding.
		 *
		 * @since 1.8.1.12
		 */
		startOnboarding: function() {

			app.saveOnboardingOption( { status: 'started' } );
			// app.initListUI( 'started' );
			$( '.charitable-onboarding-popup-container' ).fadeOut( function() {
				$( '#charitable-onboarding-welcome-builder-popup' ).hide();
			} );
			// app.timer.run( app.timer.initialSecondsLeft );
			app.updateTooltipUI();
		},

		/**
		 * Go to Step.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {number|string} step Last saved step.
		 */
		gotoStep: function( step ) { // eslint-disable-line
			console.warn( 'WARNING! Function "CharitableOnboarding.builder.gotoStep()" has been deprecated.' );
		},

		/**
		 * Save the second step before a template is selected.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {string} el Element selector.
		 * @param {object} e  Event.
		 */
		builderTemplateSelect: function( el, e ) {

			app.resumeOnboardingAndExec( e, function() {

				app.stepCompleted( 2 )
					.done( Charitable.Admin.Builder.Setup.selectTemplate.bind( el, e ) );
			} );
		},

		/**
		 * Tooltipster ready event callback.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {object} e Event object.
		 */
		tooltipsterReady: function( e ) {

			var step = $( e.origin ).data( 'charitable-onboarding-step' );
			var formId = $( '#charitable-builder-form' ).data( 'id' );

			step = parseInt( step, 10 ) || 0;
			formId = parseInt( formId, 10 ) || 0;

			// Save onboarding form ID right after it's created.
			if ( 3 === step && formId > 0 ) {
				app.saveOnboardingOption( { form_id: formId } ); // eslint-disable-line camelcase
			}
		},

		/**
		 * Display 'Welcome to the Form Builder' popup.
		 *
		 * @since 1.8.1.12
		 */
		showWelcomePopup: function() {

			// $( '#charitable-onboarding-welcome-builder-popup' ).show();
			// $( '.charitable-onboarding-popup-container' ).fadeIn();

             // Once the fade is completed, fade in the builder-popup.
            $( '.charitable-onboarding-popup-container' ).fadeIn( function() {
                $( '#charitable-onboarding-welcome-builder-popup' ).fadeIn();
            } );


        },

		/**
		 * Display 'Closing Remarks' popup.
		 *
		 * @since 1.8.1.12
		 */
		showClosingRemarksPopup: function() {

             // Once the fade is completed, fade in the builder-popup.
            $( '.charitable-onboarding-popup-container' ).fadeIn( function() {
                $( '#charitable-onboarding-goodbye-builder-popup' ).fadeIn();
            } );


        },

		/**
		 * Go to Notification step.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {object} e Event object.
		 */
		gotoNotificationStep: function( e ) { // eslint-disable-line

			// app.stepCompleted( 3 ).done( function() {

			// 	CharitableBuilder.panelSwitch( 'settings' );
			// 	CharitableBuilder.panelSectionSwitch( $( '.charitable-panel .charitable-panel-sidebar-section-notifications' ) );
			// } );
		},

		/**
		 * Simulate the dropdown, etc.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {object} e Event object.
		 */
        gotoDraftPublishStep: function( e ) { // eslint-disable-line

            app.stepCompleted( 4 ).done( function() {

                var $dropdown = $( '#charitable-status-button' );

                $dropdown.parent().find('ul#charitable-status-dropdown').removeClass('charitable-hidden');
                $dropdown.addClass( 'active' );
                app.updateTooltipUI();

            } );

        },

        undoDraftPublishStep: function( e ) { // eslint-disable-line

            var $dropdown = $( '#charitable-status-button' );

            $dropdown.parent().find('ul#charitable-status-dropdown').addClass('charitable-hidden');
            $dropdown.removeClass( 'active' );
            app.updateTooltipUI();

        },

        gotoSettingsStep: function( e ) { // eslint-disable-line

            CharitableCampaignBuilder.panelSwitch( 'settings' );
            app.updateTooltipUI();

        },

		/**
		 * Display 'Embed in a Page' popup.
		 *
		 * @since 1.8.1.12
		 */
		showEmbedPopup: function() {

		},

		/**
		 * Enable Embed button when Embed popup is closed.
		 *
		 * @since 1.7.4
		 */
		enableEmbed: function() {

			$( '#charitable-embed' ).removeClass( 'charitable-disabled' );
		},

		/**
		 * Set Onboarding parameter(s) to Onboarding option.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {object} optionData Query using option schema keys.
		 *
		 * @returns {promise} jQuery.post() promise interface.
		 */
		saveOnboardingOption: function( optionData ) {

            if ( typeof charitable_admin_builder_onboarding === 'undefined' ) {
				return;
			}

			var data = {
				action     : 'charitable_onboarding_save_option',
				option_data: optionData,
				_wpnonce   : charitable_admin_builder_onboarding.nonce,
			};

			// Save window closed (collapsed) state as well. Check if the option is set.
			if ( typeof charitable_admin_builder_onboarding.option.window_closed !== 'undefined' ) {
				data.option_data.window_closed = charitable_admin_builder_onboarding.option.window_closed;
			} else {
				data.option_data.window_closed = false;
			}

			$.extend( charitable_admin_builder_onboarding.option, optionData );

			return $.post( charitable_builder.ajax_url, data, function( response ) {
				if ( ! response.success ) {
					console.error( 'Error saving Chartiable Onboarding option.' );
				}
			} );
		},

		/**
		 * Update a step with backend data.
		 *
		 * @since 1.8.1.12
		 */
		refreshStep: function() {

			var savedStep = el.$onboarding.data( 'charitable-onboarding-saved-step' );
			savedStep = parseInt( savedStep, 10 ) || 0;

			// Step saved on a backend has a priority.
			if ( app.loadStep() !== savedStep ) {
				app.saveStep( savedStep );
			}
		},

		/**
		 * Complete onboarding step.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {number|string} step Step to complete.
		 *
		 * @returns {object} jqXHR object from saveStep().
		 */
		stepCompleted: function( step ) {

			// app.updateListUI( step );
			app.updateTooltipUI( step );

			return app.saveStep( step );
		},

		/**
		 * Initialize onboarding tooltips.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {number|string} step   Last saved step.
		 * @param {string}        anchor Element selector to bind tooltip to.
		 * @param {object}        args   Tooltipster arguments.
		 */
		initTooltips: function( step, anchor, args ) {

            if ( typeof $.fn.tooltipster === 'undefined' ) {
				return;
			}

			var $dot = $( '<span class="charitable-onboarding-dot charitable-onboarding-dot-step' + step + '" data-charitable-onboarding-step="' + step + '">&nbsp;</span>' );
			var tooltipsterArgs = {
				content          : $( '#tooltip-content' + step ),
				trigger          : null,
				interactive      : true,
				animationDuration: 0,
				delay            : 0,
				theme            : [ 'tooltipster-default', 'charitable-onboarding-tooltip' ],
				side             : [ 'top' ],
				distance         : 3,
				functionReady    : function( instance, helper ) {

					$( helper.tooltip ).addClass( 'charitable-onboarding-tooltip-step' + step );

					// Custom positioning.
					if ( step === 3 || step === 4 || step === 6 || step === 7 || step === 8 ) {
                        instance.option( 'side', 'bottom' );
                    } else if ( step === 5 ) {
                        instance.option( 'side', 'left' );
					} else if ( step === 1 ) {
						instance.option( 'side', 'bottom' );
					}

					// Reposition is needed to render max-width CSS correctly.
					instance.reposition();
				}
			};

			if ( typeof args === 'object' && args !== null ) {
				$.extend( tooltipsterArgs, args );
			}

			if ( step === 5 ) {
				$dot.insertBefore( anchor ).tooltipster( tooltipsterArgs );
			} else {
				$dot.insertAfter( anchor ).tooltipster( tooltipsterArgs );
			}
		},

		/**
		 * Update tooltips appearance.
		 *
		 * @since 1.8.1.12
		 *
		 * @param {number|string} step Last saved step.
		 */
		updateTooltipUI: function( step ) {

			var nextStep;

			step = step || app.loadStep();
			nextStep = step + 1;

			$( '.charitable-onboarding-dot' ).each( function( i, el ) {

				var $dot = $( el ),
					elStep = $dot.data( 'charitable-onboarding-step' );

				if ( elStep < nextStep ) {
					$dot.addClass( 'charitable-onboarding-dot-completed' );
				}

				if ( elStep > nextStep ) {
					$dot.addClass( 'charitable-onboarding-dot-next' );
				}

				if ( elStep === nextStep ) {
					$dot.removeClass( 'charitable-onboarding-dot-completed charitable-onboarding-dot-next' );
				}

				// Zero timeout is needed to properly detect $el visibility.
				setTimeout( function() {
					if ( $dot.is( ':visible' ) && elStep === nextStep ) {
						$dot.tooltipster( 'open' );
					} else {
						$dot.tooltipster( 'close' );
					}
				}, 0 );
			} );
		},


		/**
		 * Get last saved step.
		 *
		 * @since 1.8.1.12
		 *
		 * @returns {number} Last saved step.
		 */
		loadStep: function() {

			var step = localStorage.getItem( 'charitableOnboardingStep' );
			step = parseInt( step, 10 ) || 0;

			return step;
		},

		/**
		 * Save Onboarding step.
		 *
		 * @param {number|string} step Step to save.
		 *
		 * @returns {object} jqXHR object from saveOnboardingOption().
		 */
		saveStep: function( step ) {

			localStorage.setItem( 'charitableOnboardingStep', step );

			return app.saveOnboardingOption( { step: step } );
		},

		/**
		 * Clear all Onboarding frontend saved data.
		 *
		 * @since 1.8.1.12
		 */
		clearLocalStorage: function() {

			localStorage.removeItem( 'charitableOnboardingStep' );
		},


	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line

// Initialize.
CharitableOnboarding.init();
