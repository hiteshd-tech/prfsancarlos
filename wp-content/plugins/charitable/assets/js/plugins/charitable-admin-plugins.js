/* global wpCookies */
// eslint-disable-line no-unused-vars

var CharitableAdminPlugins = window.CharitableAdminPlugins || (function (document, window, $) {

    var s = {},
        $reports,
        $blankSlate,
        $targetArea,
        $guideTools,
        $toolsArea,
        $smtpArea,
        elements = {};

    var app = {

        settings: {
            clickWatch: false
        },

        init: function () {

            s = this.settings;

            // Document ready.
            $(app.ready);

        },

        ready: function () { // check to see if javascript has been defined.

            $reports               = $('#charitable-reports');
            $blankSlate            = $('.charitable-blank-slate');
            $guideTools            = $('#charitable-growth-tools');
            $toolsArea             = $('#charitable-tools');
            $smtpArea              = $('#charitable-smtp');
            $privacyComplianceArea = $('#charitable-privacy-compliance');

            if ( $reports.length === 0 && $blankSlate.length === 0 && $guideTools.length === 0 && $toolsArea.length === 0 && $smtpArea.length === 0 && $privacyComplianceArea.length === 0 ) {
                return;
            }

            if ( $reports.length > 0 ) {
                $targetArea = $reports;
            } else if ( $guideTools.length > 0 ) {
                $targetArea = $guideTools;
            } else if ( $toolsArea.length > 0 ) {
                $targetArea = $toolsArea;
            } else if ( $smtpArea.length > 0 ) {
                $targetArea = $smtpArea;
            } else if ( $privacyComplianceArea.length > 0 ) {
                $targetArea = $privacyComplianceArea;
            } else {
                $targetArea = $blankSlate;
            }

            // UI elements.
            elements.$button_install = $('a.charitable-button-install');

            // Data.
            s.datePickerStartDate = '';
            s.datePickerEndDate = '';

            // Bind all actions.
            app.bindUIActions();

        },

        bindUIActions: function () {

            /* button install */

            $targetArea.on('click', 'a.charitable-button-install, a.charitable-install-plugin', function (e) {
                e.preventDefault();

                var $button             = $( this ),
                    plugin_slug         = $button.data('charitable-third-party-plugin'),
                    current_text        = $button.html(),
                    install_text        = charitable_admin.install,
                    activate_text       = charitable_admin.activate,
                    activate_final_text = current_text.replace( install_text, activate_text ),
                    loading_text        = charitable_admin.loading,
                    error               = charitable_admin.something_went_wrong,
                    data                = {
                        action: 'charitable_install_plugin',
                        nonce:  charitable_admin.nonce,
                        slug:   plugin_slug
                    };

                app.disableUI();
                app.reportUILoadingOn();

                // Display "Loadding...".
                $button.html( loading_text );

                $.post( charitable_admin.ajax_url, data, function (response) {

                    if ( response.success ) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                        // Replace the word "Install" with "Activated".
                        $button.html( activate_final_text );
                        if ( response.data.basename ) {
                            $button.attr('data-basename', response.data.basename );
                        }
                        $button.removeClass('charitable-button-install').addClass('charitable-button-activate');

                        app.enableUI();
                        app.reportUILoadingOff();

                    } else {

                        $button.html( error );
                        app.enableUI();
                        app.reportUILoadingOff();
                    }

                });


            });

            /* button activate */

            $targetArea.on('click', 'a.charitable-button-activate, a.charitable-activate-plugin', function (e) {
                e.preventDefault();

                var $button             = $( this ),
                    basename            = $button.data('basename'),
                    current_text        = $button.html(),
                    activate_text       = charitable_admin.activate,
                    setup_text          = charitable_admin.setup,
                    settings_text       = charitable_admin.settings,
                    setup_final_text    = current_text.replace( activate_text, setup_text ),
                    settings_final_text = current_text.replace( activate_text, settings_text ),
                    loading_text        = charitable_admin.loading,
                    error               = charitable_admin.something_went_wrong,
                    data                = {
                        action:   'charitable_activate_plugin',
                        nonce:    charitable_admin.nonce,
                        basename: basename
                    };

                app.disableUI();
                app.reportUILoadingOn();

                // Display "Loadding...".
                $button.html( loading_text );

                $.post( charitable_admin.ajax_url, data, function (response) {

                    if ( response.success ) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                        // Replace the word "Install" with "Activated".
                        $button.removeClass('charitable-button-activate').addClass('charitable-button-setup');
                        if ( response.data.setup ) {
                            // change href of link.
                            $button.attr('href', response.data.setup );

                            // add target blank.
                            $button.attr('target', '_blank' );

                            $button.html( setup_final_text );
                        } else if ( response.data.settings ) {
                            $button.attr('href', response.data.settings );

                            $button.html( settings_final_text );
                        } else {
                            $button.attr('href', '#' );
                        }

                        app.enableUI();
                        app.reportUILoadingOff();

                    } else {

                        $button.html( error );
                        app.enableUI();
                        app.reportUILoadingOff();
                    }

                });

            });

            /* button setup */

            $targetArea.on('click', 'a.charitable-button-setup, a.charitable-setup-plugin', function (e) {
                e.preventDefault();

                var $button             = $( this ),
                    current_link        = $button.attr('href');

                app.disableUI();
                app.reportUILoadingOn();

                // If there's a link/href then open a new tab otherwise do nothing.
                if ( current_link !== '#' && current_link !== '' && current_link !== undefined && current_link !== null && current_link.length > 0 ) {
                    window.open( current_link, '_blank' );
                }

                app.enableUI();
                app.reportUILoadingOff();

            });

            $targetArea.on('click', 'a.suggestion-dismiss', function (e) {
                e.preventDefault();

                var $button             = $( this ),
                    plugin_slug         = $button.data('plugin-slug'),
                    plugin_type         = $button.data('plugin-type'),
                    data                = {
                        action: 'charitable_dismiss_suggestion',
                        nonce:  charitable_admin.nonce,
                        slug:   plugin_slug,
                        type:   plugin_type,
                    };

                $.post( charitable_admin.ajax_url, data, function (response) {

                    if ( response.success ) { // check and see if HTML donation_breakdown is returned and if so, update the breakdown table.

                       $button.closest('.charitable-plugin-suggestion').fadeOut();

                    } else {

                        $button.hide();
                    }

                });


            });

			$targetArea.on( 'click', '.charitable-screenshot a.hover', function( e ) {
				e.preventDefault();
                $('.charitable-screenshot-modal').addClass('active');
			});

			// Close button on feedback form.
			$targetArea.on( 'click', '.charitable-large-screenshot-form div.charitable-screenshot-close-icon', function( e ) {
				e.preventDefault();

				$('.charitable-screenshot-modal').removeClass('active');


			});

        },

        /* UI and Misc */


        disableUI: function () {

            if ( $reports.length > 0 ) {

                $targetArea.find('a, button, input, select').attr('disabled', 'disabled').addClass('charitable-disabled');

            }

            if ( $guideTools.length > 0 ) {

                $targetArea.find('#charitable-growth-tools a, #charitable-growth-tools button').attr('disabled', 'disabled').addClass('charitable-disabled');

            }

        },

        enableUI: function () {

            if ( $reports.length > 0 ) {

                $targetArea.find('a, button, input, select').removeAttr('disabled').removeClass('charitable-disabled');

            }

            if ( $guideTools.length > 0 ) {

                $targetArea.find('#charitable-growth-tools a, #charitable-growth-tools button').removeAttr('disabled').removeClass('charitable-disabled');

            }

        },

        reportUILoadingOn: function () {

            $targetArea.find('a.charitable-button').addClass('charitable-ui-loading');

        },

        reportUILoadingOff: function () {

            $targetArea.find('a.charitable-button').removeClass('charitable-ui-loading');

        },

        /* utils */

        decodeHtml: function (html) {
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }


    };

    // Provide access to public functions/properties.
    return app;

}(document, window, jQuery)); // eslint-disable-line no-undef

CharitableAdminPlugins.init();
