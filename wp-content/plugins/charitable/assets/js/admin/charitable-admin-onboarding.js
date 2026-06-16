/* global wpchar, wpCookies */
// eslint-disable-line no-unused-vars

var CharitableOnboarding = window.CharitableOnboarding || (function (document, window, $) {

    var s = {},
        $checklist,
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
		 * @since 1.8.1.12
		 *
		 */
        ready: function () { // check to see if javascript has been defined.

            $checklist = $('#charitable-setup-checklist');

            // Bind all actions.
            app.bindUIActions();

        },

		/**
		 * Element bindings.
		 *
		 * @since 1.8.1
		 */
        bindUIActions: function () {

            $checklist.on('click', '.charitable-toggle', function (e) {
                e.preventDefault();
                // remove the focus from the button.
                $(this).blur();
                app.fieldSectionToggle($(this), 'click');
            });

            $checklist.on('click', '.charitable-toggle-optin-allow', function (e) {
                e.preventDefault();
                // remove the focus from the button.
                $(this).blur();
                var $this = $(this),
                    $toggleGroup = $('.charitable-checklist-allow'),
                    $icon = $this.find('i');
                $icon.toggleClass('charitable-angle-right');
                $toggleGroup.stop().slideToggle('', function () {
                    $toggleGroup.toggleClass('charitable-closed');
                    if ($toggleGroup.hasClass('charitable-closed')) {
                        $toggleGroup.removeClass('charitable-open');
                    }
                });
            });

            $checklist.on('click', '#wpchar-no-stripe', function (e) {
                // if this checkbox is checked, then add a css class to charitable-connect-stripe.
                if ($(this).is(':checked')) {
                    $('.charitable-connect-stripe').addClass('wpchar-disabled');
                } else {
                    $('.charitable-connect-stripe').removeClass('wpchar-disabled');
                }
            });

        },

        /**
         * Toggle field group visibility in the field sidebar.
         *
         * @since 1.8.1.12
         *
         * @param {mixed}  el     DOM element or jQuery object.
         * @param {string} action Action.
         */
        fieldSectionToggle: function (el, action) {

            var $this = $(el),
                $nearestContainer = $this.closest('section.charitable-step'),
                $toggleGroup = $nearestContainer.find('.charitable-toggle-container'),
                sectionName = $nearestContainer.data('section-name'),
                $icon = $this.find('i'),
                cookieName = 'charitable_checklist_section_' + sectionName;

            if (action === 'click') {

                $icon.toggleClass('charitable-angle-right');

                $toggleGroup.stop().slideToggle('', function () {
                    $nearestContainer.toggleClass('charitable-closed');
                    if ($nearestContainer.hasClass('charitable-closed')) {
                        $nearestContainer.removeClass('charitable-open');
                        wpCookies.remove(cookieName);
                    } else {
                        wpCookies.set(cookieName, 'true', 2592000); // 1 month
                    }
                });

                return;
            }

        },

    };

    return app;

}(document, window, jQuery)); // eslint-disable-line no-undef

CharitableOnboarding.init();
