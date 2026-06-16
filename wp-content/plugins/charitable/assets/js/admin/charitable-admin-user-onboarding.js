/* global wpchar, wpCookies */
// eslint-disable-line no-unused-vars

var CharitableSetup = window.CharitableSetup || (function (document, window, $) {

    var s = {},
        elements = {};

    var app = {

        settings: {
            onboarded: false
        },

		/**
		 * Start the engine.
		 *
		 * @since 1.8.1
		 */
        init: function () {

            s = this.settings;

            // Document ready.
            $(app.ready);

        },

		/**
		 * Document ready.
		 *
		 * @since 1.8.4
		 *
		 */
        ready: function () { // check to see if javascript has been defined.

            // check to see if charitable_setup has been defined.
            if ( typeof charitable_setup === 'undefined' ) {
                return;
            }

            wpchar.debug('charitable_setup');
            wpchar.debug(charitable_setup);

			s.version             = '1.8.4';
            s.steps               = [ 'start', 'meta', 'plugins_installed', 'plugins_activated', 'activate_license', 'features', 'campaign', 'payment_methods', 'almost_complete', 'complete' ];
            s.setup_step          = charitable_setup.setup_step || 'meta';
            s.plugins             = charitable_setup.plugins || [];
            s.plugins_meta        = charitable_setup.plugins_meta || [];
            s.features            = charitable_setup.features || [];
            s.features_meta       = charitable_setup.features_meta || [];
            s.payment_methods     = charitable_setup.payment_methods || [];
            s.stripe_connect_url  = charitable_setup.stripe_connect_url || '';
            s.stripe_skip_html    = charitable_setup.stripe_skip_html || '';
            s.stripe_returned     = charitable_setup.stripe_returned || false;
            s.campaign_skip       = charitable_setup.campaign_skip || false;
            s.checklist_completed = charitable_setup.checklist_completed || false;
            s.key                 = charitable_setup.key || '';

            s.pro_test     = false; // true if we did the test already.
            s.is_pro       = false; // true if we're running the pro version.
            s.test_mode    = charitable_setup.test_mode || false;

            s.feature_issue = false; // true if one or more of the feature addons were not installed or activated.
            s.license_issue = false; // true if the license was not activated.

			// Action buttons.
			elements.$progressBar          = $( '.charitable-user-onboarding-progress-bar' );
			elements.$progressBarFill      = $( '.charitable-user-onboarding-progress-bar-fill' );
            elements.$textHeadline         = $( '.chartiable-user-onboarding-content h1' );
            elements.$textSubHeadline      = $( '.chartiable-user-onboarding-content p.charitable-subheading' );

            // Bind all actions.
            app.bindUIActions();

            // Setup Triggers.
            app.setupTriggers();

            app.processStart();

            // Check if we are returning from Stripe.
            if ( s.stripe_returned || s.checklist_completed ) {

                $(document).trigger( 'charitable_setup_step_complete' );

            } else {

                setTimeout( function() {
                    app.processMeta();
                }, 1500 );
                // Process step.

            }

        },

		/**
		 * Element bindings.
		 *
		 * @since 1.8.4
		 */
        bindUIActions: function () {

        },

        setupTriggers: function() {

            $(document).on( 'charitable_setup_step_start', app.processStart );

            $(document).on( 'charitable_setup_step_meta', app.processMeta );

            $(document).on( 'charitable_setup_step_plugins_install', app.processPluginsInstall );

            $(document).on( 'charitable_setup_step_plugins_activate', app.processPluginsActivate );

            $(document).on( 'charitable_setup_step_activate_license', app.processActivateLicense );

            $(document).on( 'charitable_setup_step_features', app.processFeatures );

            $(document).on( 'charitable_setup_step_campaign', app.processCampaign );

            $(document).on( 'charitable_setup_step_pm', app.processPM );

            $(document).on( 'charitable_setup_step_almost_complete', app.stepAlmostComplete );

            $(document).on( 'charitable_setup_step_complete', app.stepComplete );

        },

        processStart: function() {

            wpchar.debug( 'processStart' );
            s.setup_step = 'start';

            app.updateHeadline( s.setup_step );
            // app.updateSubHeadline( s.setup_step );

            // update the progress bar.
            app.updateProgressBar( s.setup_step );

        },

        processMeta: function( step = false ) {

            // Step 1.
            // Update email and general settings (should be quick).

			var data = {
				action : 'charitable_setup_process_meta',
				step   : s.setup_step,
				nonce  : charitable_setup.setup_nonce
			};

			$.post( charitable_setup.ajax_url, data, function( response ) {

				if ( response.success ) {

                    wpchar.debug( response );

                    // check to see if there's a processed step.
                    if ( response.data.processed_step ) {

                        // update the setup step.
                        s.setup_step = response.data.next_step;

                        // update the progress bar.
                        app.updateProgressBar( s.setup_step );

                        // update the text.
                        app.updateHeadline( s.setup_step );
                        app.updateSubHeadline( s.setup_step );

                        // trigger a custom event.
                        $(document).trigger( 'charitable_setup_step_plugins_install' );

                    }

                } else {

                    wpchar.debug('processMeta error');
                    wpchar.debug( response );

                }

            });

        },

        processPluginsInstall: function( step = false ) {

            wpchar.debug( 'processPluginsInstall' );

            wpchar.debug( s.plugins );
            wpchar.debug( s.plugins_meta );

            s.setup_step = 'plugins_installed';

            // if s,plugins is empty or s.plugins.install is empty, then we're done.
            if ( ! s.plugins || ! s.plugins.install || s.plugins.install.length === 0 ) {
                wpchar.debug('installed done');
                // trigger a custom event.
                // update the progress bar.
                app.updateProgressBar( s.setup_step );
                $(document).trigger( 'charitable_setup_step_plugins_activate' );
                return;
            }

            // Remove duplicates from s.plugins.install.
            s.plugins.install = s.plugins.install.filter( function( item, index, inputArray ) {
                return inputArray.indexOf(item) == index;
            });

            // Get the next plugin from the list.
            var plugin_slug = s.plugins.install.shift();

            wpchar.debug( plugin_slug );

            var plugin_name = ( 'undefined' !== typeof s.plugins_meta[ plugin_slug ] ) ? s.plugins_meta[ plugin_slug ].name : '';

            if ( '' === plugin_name ) {
                this.return;
            }

            var data = {
                action: 'charitable_install_plugin',
                nonce:  charitable_admin.nonce,
                slug:   plugin_slug
			};

            // Update the "subheadine" text.
            elements.$textSubHeadline.text( 'Installing ' + plugin_name + '...' );

            $.post( charitable_admin.ajax_url, data, function (response) {

                if ( response.success ) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                    wpchar.debug( 'success')
                    wpchar.debug( response );

                    app.updateHeadline( s.setup_step );
                    elements.$textSubHeadline.text( 'Installed ' + plugin_name + '.' );

                    // update the progress bar.
                    app.updateProgressBar( s.setup_step, plugin_slug, s.plugins.install );

                    // ad the slug to the plugins activate list.
                    s.plugins.activate.push( plugin_slug );

                    // is the installed plugins list empty? if so, trigger activate plugins.
                    if ( s.plugins.install.length === 0 ) {
                        // update the setup step.
                        s.setup_step = response.data.next_step;
                        $(document).trigger( 'charitable_setup_step_plugins_activate' );
                    } else {
                        app.processPluginsInstall();
                    }

                } else {

                    wpchar.debug( 'error' );
                    wpchar.debug( response );

                    // don't stop things, keep going.

                    // update the progress bar.
                    app.updateProgressBar( s.setup_step, plugin_slug, s.plugins.install );

                    // is the installed plugins list empty? if so, trigger activate plugins.
                    if ( s.plugins.install.length === 0 ) {
                        // update the setup step.
                        s.setup_step = ( 'undefined' === typeof response.data || 'undefined' === typeof response.data.next_step ) ? 'plugins_activated' : response.data.next_step;
                        $(document).trigger( 'charitable_setup_step_plugins_activate' );
                    } else {
                        app.processPluginsInstall();
                    }

                }

            });

        },

        processPluginsActivate: function( step = false ) {

            wpchar.debug( 'processPluginsActivate' );

            // wpchar.debug( plugin_slug );
            wpchar.debug( s.plugins );
            wpchar.debug( s.plugins_meta );

            s.setup_step = 'plugins_activated';

            // if s,plugins is empty or s.plugins.install is empty, then we're done.
            if ( ! s.plugins || ! s.plugins.activate || s.plugins.activate.length === 0 ) {
                wpchar.debug('activated done');
                // update the progress bar.
                app.updateProgressBar( s.setup_step );

                // do we go on to activate the license or the feature step?
                if ( s.key ) {
                    s.setup_step = 'activate_license';
                    $(document).trigger( 'charitable_setup_step_activate_license' );
                } else {
                    s.setup_step = 'features';
                    $(document).trigger( 'charitable_setup_step_features' );
                }
                return;
            }

            // Remove duplicates from s.plugins.activate.
            s.plugins.activate = s.plugins.activate.filter( function( item, index, inputArray ) {
                return inputArray.indexOf(item) == index;
            });

            // Get the next plugin from the list.
            var plugin_slug = s.plugins.activate.shift();

            wpchar.debug( plugin_slug );

            wpchar.debug('s.plugins_meta');
            wpchar.debug( s.plugins_meta );

            wpchar.debug('s.plugins_meta[ plugin_slug ]');
            wpchar.debug( s.plugins_meta[ plugin_slug ] );

            wpchar.debug('s.plugins_meta[ plugin_slug ].name');
            wpchar.debug( s.plugins_meta[ plugin_slug ].name );

            if ( 'undefined' !== typeof s.plugins_meta[ plugin_slug ] && 'undefined' !== typeof s.plugins_meta[ plugin_slug ].name ) {

                var plugin_name = s.plugins_meta[ plugin_slug ].name;

                var data = {
                    action: 'charitable_activate_plugin',
                    nonce:  charitable_admin.nonce,
                    slug:   plugin_slug,
                    basename: s.plugins_meta[ plugin_slug ].basename || ''
                };

                // Update the "subheadine" text.
                elements.$textSubHeadline.text( 'Activating ' + plugin_name + '...' );

                $.post( charitable_admin.ajax_url, data, function (response) {

                    if ( response.success ) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                        wpchar.debug( 'success')
                        wpchar.debug( response );

                        app.updateHeadline( s.setup_step );
                        elements.$textSubHeadline.text( 'Activated ' + plugin_name + '.' );

                        // update the progress bar.
                        app.updateProgressBar( s.setup_step, plugin_slug, s.plugins.activate );

                        // is the installed plugins list empty? if so, trigger activate plugins.
                        if ( s.plugins.activate.length === 0 ) {

                            wpchar.debug('activated phase done');
                            wpchar.debug( s.key );

                            // do we go on to activate the license or the feature step?
                            if ( s.key ) {
                                s.setup_step = 'activate_license';
                                $(document).trigger( 'charitable_setup_step_activate_license' );
                            } else {
                                s.setup_step = 'features';
                                $(document).trigger( 'charitable_setup_step_features' );
                            }
                            return;

                        } else {
                            app.processPluginsActivate();
                        }

                    } else {

                        wpchar.debug( 'error' );
                        wpchar.debug( response );
                    }

                });

            } else {

                if ( s.plugins.activate.length === 0 ) {
                    // update the setup step.
                    wpchar.debug('activated phase alt done');
                    wpchar.debug( s.key );

                    // do we go on to activate the license or the feature step?
                    if ( s.key ) {
                        s.setup_step = 'activate_license';
                        $(document).trigger( 'charitable_setup_step_activate_license' );
                    } else {
                        s.setup_step = 'features';
                        $(document).trigger( 'charitable_setup_step_features' );
                    }
                } else {
                    app.processPluginsActivate();
                }

            }

        },

        processActivateLicense: function( step = false ) {

            wpchar.debug( 'processActivateLicense' );
            wpchar.debug( s.key );

            if ( ! s.key ) {
                s.setup_step = 'features';
                $(document).trigger( 'charitable_setup_step_features' );
                return;
            }

            s.setup_step = 'activate_license';

            // use the ajax charitable_license_check.
			var data = {
                'action'	        : 'charitable_license_check',
                'license' 	        : s.key,
                'nonce'			    : charitable_setup.key_nonce,
                'charitable_action' : 'verify',
                'download_pro'      : true
            };

            // update the text.
            app.updateHeadline( s.setup_step );
            app.updateSubHeadline( s.setup_step );

			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'json',
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {
                    wpchar.debug ('success in response');
                    wpchar.debug( response );
					if ( response.success && response.data.valid ) {
                        wpchar.debug ('success in response: license valid');
                        // update the progress bar.
                        app.updateProgressBar( s.setup_step );
                        s.setup_step = 'features';
                        $(document).trigger( 'charitable_setup_step_features' );
					} else {
                        wpchar.debug ('success in response: license not valid');
                        wpchar.debug( response );
                        s.license_issue = true;
                        // update the progress bar.
                        app.updateProgressBar( s.setup_step );
                        s.setup_step = 'features';
                        $(document).trigger( 'charitable_setup_step_features' );
                    }
				}
            }).fail(function (data) {
                if ( window.console && window.wpchar.debug ) {
                    wpchar.debug( 'license activation error' );
                    wpchar.debug( data );
                }
                s.license_issue = true;
                // show much go on.
                // update the progress bar.
                app.updateProgressBar( s.setup_step );
                s.setup_step = 'features';
                $(document).trigger( 'charitable_setup_step_features' );
            });

        },

        processFeatures: function( step = false ) {

            wpchar.debug( 'function : processFeatures' );
            wpchar.debug( s.features );

            s.setup_step = 'features';

            // if s,plugins is empty or s.plugins.install is empty, then we're done.
            if ( ! s.features || ! s.features.length === 0 ) {
                wpchar.debug('features done');
                // update the progress bar.
                app.updateProgressBar( s.setup_step );
                $(document).trigger( 'charitable_setup_step_campaign' );
                return;
            }

            if ( s.pro_test && ! s.is_pro ) {
                wpchar.debug('features not possible');
                // update the progress bar.
                app.updateProgressBar( s.setup_step );
                $(document).trigger( 'charitable_setup_step_campaign' );
                return;
            }

            // Remove duplicates from s.plugins.activate.
            s.features = s.features.filter( function( item, index, inputArray ) {
                return inputArray.indexOf(item) == index;
            });

            // Get the next feature from the list.
            var feature_slug = s.features.shift();

            if ( 'undefined' === typeof s.features_meta[ feature_slug ] ) {
                wpchar.debug('s.features_meta[ feature_slug ] not found');
                app.updateProgressBar( s.setup_step );
                $(document).trigger( 'charitable_setup_step_campaign' );
                return;
            }

            wpchar.debug( feature_slug );
            wpchar.debug( s.features_meta );

            var feature_name = s.features_meta[ feature_slug ].name;

            var data = {
                action: 'charitable_activate_feature',
                nonce:  charitable_admin.nonce,
                slug:   feature_slug,
			};

            if ( ! s.pro_test ) {
                // Update the "subheadine" text.
                elements.$textSubHeadline.text( 'Checking license and account information...' );
            } else {
                // Update the "subheadine" text.
                elements.$textSubHeadline.text( 'Installing ' + feature_name + '...' );
            }

            $.post( charitable_admin.ajax_url, data, function (response) {

                if ( response.success ) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                    wpchar.debug( 'process features: success')
                    wpchar.debug( response );

                    app.updateHeadline( s.setup_step );
                    elements.$textSubHeadline.text( 'Activated ' + feature_name + '.' );
                    wpchar.debug( 'Activated ' + feature_name + '.' );

                    // update the progress bar.
                    app.updateProgressBar( s.setup_step );

                    $('#charitable-user-onboarding-notice-featured-not-required').removeClass('charitable-hidden');

                    // is the installed plugins list empty? if so, trigger activate plugins.
                    if ( s.features.length === 0 ) {

                        setTimeout( function() {
                            $('#charitable-user-onboarding-notice-featured-not-required').addClass('charitable-hidden');
                            $('p.charitable-subheading').removeClass('charitable-hidden');
                            $(document).trigger( 'charitable_setup_step_campaign' );
                        }, 2500 );


                    } else {

                        setTimeout( function() {
                            app.processFeatures();
                        }, 2500 );
                    }

                } else {

                    wpchar.debug( 'process features: error' );
                    wpchar.debug( response );
                    wpchar.debug( s.setup_step );

                    s.feature_issue = true;

                    if ( s.features.length === 0 ) {

                        $('p.charitable-subheading').addClass('charitable-hidden');
                        $('#charitable-user-onboarding-notice-featured-required').removeClass('charitable-hidden');

                        app.updateProgressBar( s.setup_step );
                        app.updateHeadline( s.setup_step );
                        app.updateSubHeadline( s.setup_step );

                        // wait a couple of seconds and then trigger the next step.
                        setTimeout( function() {
                            $('#charitable-user-onboarding-notice-featured-required').addClass('charitable-hidden');
                            $('p.charitable-subheading').removeClass('charitable-hidden');
                            $(document).trigger( 'charitable_setup_step_campaign' );
                        }, 2500 );


                    } else {

                        setTimeout( function() {
                            app.processFeatures();
                        }, 2500 );

                    }

                    // $('p.charitable-subheading').addClass('charitable-hidden');
                    // $('#charitable-user-onboarding-notice-featured-required').removeClass('charitable-hidden');

                    // app.updateProgressBar( s.setup_step );
                    // app.updateHeadline( s.setup_step );
                    // app.updateSubHeadline( s.setup_step );

                    // // wait a couple of seconds and then trigger the next step.
                    // setTimeout( function() {
                    //     $('#charitable-user-onboarding-notice-featured-required').addClass('charitable-hidden');
                    //     $('p.charitable-subheading').removeClass('charitable-hidden');
                    //     // $(document).trigger( 'charitable_setup_step_campaign' );
                    //     $(document).trigger( 'charitable_setup_step_campaign' );
                    // }, 2500 );
                }

            });

        },

        processCampaign: function( step = false ) {

            wpchar.debug( 'processCampaign' );

            // we need to determine if the user has created a campaign or skipped that option.
            if ( s.campaign_skip ) {
                s.setup_step = 'campaign';
                // update the progress bar.
                app.updateProgressBar( s.setup_step );
                $(document).trigger( 'charitable_setup_step_pm' );
                return;
            }

			var data = {
				action : 'charitable_setup_process_campaign',
				step   : s.setup_step,
				nonce  : charitable_setup.setup_nonce
			};

			$.post( charitable_setup.ajax_url, data, function( response ) {

				if ( response.success ) {

                    if ( ( 'undefined' !== ( typeof response.data.status ) && response.data.status === 'campaign-created' ) ) {

                        s.setup_step = 'campaign';
                        app.updateHeadline( s.setup_step );
                        app.updateSubHeadline( s.setup_step );

                        wpchar.debug( response );

                        // update the progress bar.
                        app.updateProgressBar( s.setup_step );

                        setTimeout( function() {
                            $(document).trigger( 'charitable_setup_step_pm' );
                        }, 2500 );

                    } else {
                        // likely the user skipped building the campaign.
                        $(document).trigger( 'charitable_setup_step_pm' );
                    }

                } else {

                    wpchar.debug('create campaign error');

                    wpchar.debug( response );

                    // show must go on.
                    // $(document).trigger( 'charitable_setup_step_pm' );

                }

            });

        },

        processPM: function( step = false ) {

            wpchar.debug( 'processPM' );

            // wpchar.debug( plugin_slug );
            s.setup_step = 'payment_methods';
            app.updateHeadline( s.setup_step );
            app.updateSubHeadline( s.setup_step );

            // update the progress bar.
            app.updateProgressBar( s.setup_step );

            $(document).trigger( 'charitable_setup_step_almost_complete' );
        },

        stepAlmostComplete: function( step = false ) {

            wpchar.debug( 'almostComplete' );

            s.setup_step = 'almost_complete';
            app.updateHeadline( s.setup_step );
            app.updateSubHeadline( s.setup_step );

            // at this point the gateways have been enabled, but if the user selected Stripe
            // we want the last "step" to be the Stripe Connect to keep the flow.

            var stripe_index = s.payment_methods.indexOf( 'stripe' );
            // check also for the connect url (if there's no connect url then something is wrong or it's already connected).
            if ( stripe_index !== -1 && s.stripe_connect_url !== '' ) {
                app.updateSubHeadline( 'almost_complete_stripe' );
                // adjust the warning message at the bottom they can actually skip this if they must.
                $('.charitable-go-back').html( s.stripe_skip_html );
                // show the Stripe connect field in the setup template.
                $('#charitable-user-onboarding-stripe-connect').removeClass('charitable-hidden');
            } else {
                // update the progress bar.
                app.updateProgressBar( s.setup_step );
                $(document).trigger( 'charitable_setup_step_complete' );
            }

        },

        stepComplete: function( step = false ) {

            wpchar.debug( 'Complete' );

            s.setup_step = 'complete';

			var data = {
				action : 'charitable_setup_process_complete',
				step   : s.setup_step,
				nonce  : charitable_setup.setup_nonce
			};

			$.post( charitable_setup.ajax_url, data, function( response ) {

				if ( response.success ) {

                    app.updateHeadline( s.setup_step );
                    app.updateSubHeadline( s.setup_step );

                    // update the progress bar.
                    app.updateProgressBar( s.setup_step );

                    app.fireConfetti();

                    if ( s.license_issue === true ) {
                        // remove the hidden class from the license issue notice.
                        $('#charitable-user-onboarding-notice-license-failed').removeClass('charitable-hidden');
                        $("#charitable-user-onboarding-notice-license-not-required").addClass('charitable-hidden');
                    } else if ( s.feature_issue === true ) {
                        // remove the hidden class from the feature issue notice.
                        $('#charitable-user-onboarding-notice-featured-failed').removeClass('charitable-hidden');
                        $("#charitable-user-onboarding-notice-featured-not-required").addClass('charitable-hidden');
                    }

                    $('#charitable-user-onboarding-complete-buttons').removeClass('charitable-hidden');

                    if ( s.checklist_completed ) {
                        $('#charitable-user-onboarding-complete-buttons .charitable-view-checklist').addClass('charitable-hidden');
                    }

                } else {

                    // show must go on.

                    app.updateHeadline( s.setup_step );
                    app.updateSubHeadline( s.setup_step );

                    // update the progress bar.
                    app.updateProgressBar( s.setup_step );

                    app.fireConfetti();

                    $('#charitable-user-onboarding-complete-buttons').removeClass('charitable-hidden');

                }

            });

        },

        fireConfetti: function() {

            if ( typeof confetti === 'function') {
                confetti({
                    spread: 150,
                    particleCount: 250,
                    zIndex: 9999999999,
                    origin: { y: 0.55 },
                });
            }

        },

        updateHeadline: function( step = '' ) {

            var headline = charitable_setup.headlines[ step ];

            elements.$textHeadline.text( headline );

        },

        updateSubHeadline: function( step = '' ) {

            var subHeadline = charitable_setup.text[ step ];

            elements.$textSubHeadline.text( subHeadline );

        },

        /**
         * Update the progress bar.
         *
         * @since 1.8.1
         */
        updateProgressBar: function( step = '', plugin_slug = '', plugin_slug_list = [] ) {

            wpchar.debug( 'updateProgressBar' );
            wpchar.debug( step );

            var $progressBar = elements.$progressBar,
                $progressBarFill = elements.$progressBarFill,
                position = s.steps.indexOf( step ) + 1;
                total_size = s.steps.length,
                percent = Math.round( ( position / total_size ) * 100 );

            // if we have a plugin_slug, then determine percentage wise where that slug is in the plugin_slug_list and override precent to add.
            if ( plugin_slug && plugin_slug_list.length > 0 ) {
                perecent = percent + 3;
            }

            if ( step === 'start' ) {
                percent = 0;
            }

            wpchar.debug( 'percent' );
            wpchar.debug( percent );

            $progressBarFill.css( 'width', percent + '%' );

        }

    };

    return app;

}(document, window, jQuery)); // eslint-disable-line no-undef

CharitableSetup.init();
