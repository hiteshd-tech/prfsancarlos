/* global charitable_builder, jconfirm, charitable_panel_switch, Choices, Charitable, CharitableCampaignEmbedWizard, wpCookies, tinyMCE, CharitableUtils, List */ // eslint-disable-line no-unused-vars

var CharitableAdminUI = window.CharitableAdminUI || ( function( document, window, $ ) {

	var s = {};

	var elements = {};

    var app = {

		settings: {
			clickWatch: false
		},

		init: function() {

			// charitable_panel_switch = true;
			s = this.settings;

			// Document ready.
			$( app.ready );

		},

		ready: function() {

            // Move navigation elements on certain pages.
            app.moveNavigationElements();

			elements.$addNewCampaignButton = $( 'body.post-type-campaign .page-title-action' );

			// Bind all actions.
			app.bindUIActions();

            var urlParams = new URLSearchParams(window.location.search);

            if ( urlParams.has('create') && 'campaign' === urlParams.get('create') ) {
                app.newCampaignPopup();
                urlParams.delete('create');
                window.history.pushState({}, '', '/wp-admin/edit.php?' + urlParams.toString() );
            }

            // upon loading the page or a change of a hash - view the hash in the url and if that anchor exists, scroll to it.
            $(document).ready(function() {
                app.scrollToAnchor();
            }
            );
            $(window).on('hashchange', function() {
                app.scrollToAnchor();
            });

            // if the body tag has the css class charitable_page_charitable-dashboard and the notification container has a notification in it, show the notifications.
            if ( $('body').hasClass('charitable_page_charitable-dashboard') && $('#charitable-plugin-notifications').find('.notification-cards-active .charitable-notification').length > 0 ) {
                // check if charitable_admin.autoshow_notifications is not undefined and is true.
                if ( typeof charitable_admin.autoshow_plugin_notifications !== 'undefined' && charitable_admin.autoshow_plugin_notifications ) { // eslint-disable-line no-undef
                    $('body').addClass('charitable-show-notifications');
                    $("#charitable-plugin-notifications").addClass("in");
                    $('body').find('.charitable-notifications-overlay').fadeIn();
                }
            }

        },

        /**
         * Move navigation elements on certain pages.
         *
         * @return {void}
         *
         * @since 1.8.2
        */
        moveNavigationElements: function() {

            // if the body class has the class 'post-type-charitable' and 'edit-tags-php' then move the navigation elements "#charitable-tools-nav" right after form.search-form.
            if ( $('body.post-type-charitable.edit-tags-php').length > 0 ) {
                $('#charitable-tools-nav').insertBefore('h1.wp-heading-inline');
            }

        },

        /**
         * Bind all UI actions.
         *
         * @return {void}
         *
        */
        bindUIActions: function() {

            // Deprecated.
            // $('body.post-type-campaign').on( 'click', '.page-title-action', function( e ) {
            //     e.preventDefault();
            //     app.newCampaignPopup();
            // } );

            $('body.post-type-campaign').on( 'click', '.charitable-campaign-list-banner a.button-link', function( e ) {
                e.preventDefault();
                app.campaignListBannerPopup();
            } );

            $('body.post-type-campaign').on( 'click', '.jconfirm-closeIcon', function( e ) { // eslint-disable-line no-unused-vars
                s.clickWatch = false;
            } );
            if ( s.clickWatch === false ) {
                $('body.post-type-campaign').on( 'click', 'input.campaign_name', function( e ) {
                    e.preventDefault();
                    $(this).select();
                    s.clickWatch = true;
                } );
            }

            // Blank slate create new campaign button.
            if ( $('.charitable-blank-slate-create-campaign').length > 0 ) {

                $('body.post-type-campaign').on( 'click', '.charitable-blank-slate-create-campaign', function( e ) {
                    e.preventDefault();
                    app.newCampaignPopup();
                } );

            }

            // Welcome activation page.
            app.initWelcome();

            // Upgrade Modal.
            app.initUpgradeModal();

            // Notifications (Dashboard)
            app.initNotifications();

            // Notifications (AM)
            app.initAMNotifications();

            // Square Legacy Modal.
            app.initSquareLegacyModal();

        },

        /**
         * Initialize the AM notifications.
         *
         * @return {void}
         *
         * @since 1.8.3
         *
         */
        initAMNotifications: function() {

            if ( $('.charitable-notification-inbox').length > 0 ) {
                // on click add body class charitable-notifications-open.
                $('body').on( 'click', '.charitable-notification-inbox, .charitable-close, #toplevel_page_charitable li.notifications, .charitable-report-card.charitable-dashboard-notifications .more a, p.charitable-view-notifications a', function( e ) {
                    e.preventDefault();
                    $('body').toggleClass('charitable-show-notifications');
                    $("#charitable-plugin-notifications").toggleClass("in");
                    $('body').find('.charitable-notifications-overlay').fadeToggle();
                });
            }

            if ( $('.charitable-notifications-overlay').length > 0 ) {
                // click on overlay, the notifications will close.
                $('body').on( 'click', '.charitable-notifications-overlay', function( e ) {
                    e.preventDefault();
                    $('body').removeClass('charitable-show-notifications');
                    $("#charitable-plugin-notifications").removeClass("in");
                    $('body').find('.charitable-notifications-overlay').fadeOut();
                });
                // dismiss a single notification.
                $('.charitable-notification').on( 'click', 'a.dismiss', function( e ) {
                    e.preventDefault();
                    var $this = $(this),
                        notification_id = $this.closest('.charitable-notification').data('notification-id');

                    // ajax call to diable the notification.
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl, // eslint-disable-line no-undef
                        data: {
                            action: 'charitable_notification_dismiss',
                            notification_id: notification_id,
                            nonce: charitable_admin.nonce, // eslint-disable-line no-undef
                        },
                        success: function( response ) {
                            if ( response.success ) {
                                // Remove the element that has the notification id.
                                // Because the dismiss button might be in multiple locations, for each element that has the data-notification-id attribute and find the 'charitable-notification' element in it's children.
                                // $('body').find('.charitable-notification[data-notification-id="' + notification_id + '"]').fadeOut(400, function() { app.moveAMNotificationToDismissed( $(this) ) });
                                // do the same thing but remove the fadeOut but reference the app.moveAMNotificationToDismissed function.
                                var the_counts = app.moveAMNotificationToDismissed( $('body').find('#charitable-plugin-notifications .charitable-notification[data-notification-id="' + notification_id + '"]') ),
                                    active_notification_count = the_counts.active_notification_count,
                                    dismissed_notification_count = the_counts.dismissed_notification_count;

                                if ( active_notification_count === 0 ) {
                                    // remove the notifications which should be none but still.
                                    $('.charitable-notification-cards.notification-cards-active .charitable-notification').remove();
                                    // ..same for the dashboard notifications, if it exists.
                                    if ( $('#charitable-dashboard-report-sections').length > 0 ) {
                                        $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notification').remove();
                                    }

                                    // For the dashboard notifications, if there are no more notifications, show the no-items message.
                                    $('.charitable-dashboard-notifications').find('.no-items').removeClass('charitable-hidden');
                                    $('.charitable-dashboard-notifications').find('.the-list').addClass('charitable-hidden');

                                    // change the text at the top of the notifications.
                                    $('#charitable-plugin-notifications .new-notifications').html('No new notifications');
                                    // change the text of the dashboard notifications, if it exists.
                                    $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications .header h4').html('Notifications');

                                    // Set up the "no active notifications" message on dashboard.
                                    $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications').find('.charitable-report-ui').html('<div class="no-items"><p><strong>There is currently no active notifications.</strong></p><p class="link charitable-view-notifications"><a href="#">View Notification<img src="/wp-content/plugins/charitable/assets/images/icons/east.svg"></a></p></div>');

                                    // remove the red dot from notifications icon and the WP admin menu.
                                    $('.charitable-notification-inbox').find('.number').remove();
                                    $('#toplevel_page_charitable').find('li.notifications .charitable-menu-notification-indicator').remove();

                                    // remove dismiss all link.
                                    $('#charitable-plugin-notifications').find('.dismiss-all').remove();
                                } else {
                                    $('#charitable-plugin-notifications').find('#new-notifications-count').html( active_notification_count );
                                    
                                    // Update the header badge count
                                    $('.charitable-notification-inbox').find('.number').html( active_notification_count );

                                    // Get the number of active notifications for the dashboard.
                                    var active_notification_count_dashboard = $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications .charitable-notification').length;

                                    if ( active_notification_count > active_notification_count_dashboard ) {
                                        if ( active_notification_count === 1 ) {
                                            $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count + '+' );
                                            // Insert HTML into .charitable-report-ui of this id.
                                            $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications').find('.charitable-report-ui').html('<div class="no-items"><p><strong>There is currently one active notification.</strong></p><p class="link charitable-view-notifications"><a href="#">View Notification<img src="/wp-content/plugins/charitable/assets/images/icons/east.svg"></a></p></div>');
                                        } else {
                                            $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count_dashboard + '+' );
                                        }
                                    } else {
                                        $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count_dashboard );
                                    }
                                }

                                var active_notification_count_dashboard = $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications .charitable-notification').length;

                                $('#charitable-plugin-notifications').find('#new-notifications-count').html( active_notification_count );

                                if ( active_notification_count > active_notification_count_dashboard ) {
                                    if ( active_notification_count === 1 ) {
                                        $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count + '+' );
                                        // Insert HTML into .charitable-report-ui of this id.
                                        $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications').find('.charitable-report-ui').html('<div class="no-items"><p><strong>There is currently one active notification.</strong></p><p class="link charitable-view-notifications"><a href="#">View Notification<img src="/wp-content/plugins/charitable/assets/images/icons/east.svg"></a></p></div>');
                                    } else {
                                        $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count_dashboard + '+' );
                                    }
                                } else {
                                    $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count_dashboard );
                                }

                                // if active is only one, use the singular form... otherwise use the plural form.
                                if ( active_notification_count === 1 ) {
                                    app.replaceNotifications(".new-notifications.notifications-visible");
                                    // check to see if the h4 element exists in the dashboard notifications and replace the text.
                                    if ( $('#charitable-dashboard-report-sections').length > 0 ) {
                                        app.replaceNotifications(".charitable-dashboard-notifications .header h4");
                                    }
                                }

                                // Replace the number inside the count div.
                                $('#charitable-plugin-notifications').find('#dismissed-notifications-count').text( dismissed_notification_count );

                                if ( dismissed_notification_count === 1 ) {
                                    app.replaceNotifications('#charitable-plugin-notifications .old-notifications strong');
                                } else {
                                    app.replaceNotification('#charitable-plugin-notifications .old-notifications strong');
                                }
                            }
                        }
                    });
                });
                // dismiss all notifications.
                $('.charitable-plugin-notifications .notification-menu').on( 'click', '.dismiss-all a.dismiss', function( e ) {
                    e.preventDefault();
                    var $this = $(this),
                        notification_ids = [];

                    // get all the notification ids.
                    $('.charitable-notification').each(function() {
                        notification_ids.push( $(this).data('notification-id') );
                    });

                    // ajax call to diable the notification.
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl, // eslint-disable-line no-undef
                        data: {
                            action: 'charitable_notification_dismiss_multiple',
                            notification_ids: notification_ids,
                            nonce: charitable_admin.nonce, // eslint-disable-line no-undef
                        },
                        success: function( response ) {
                            if ( response.success ) {
                                // remove all elements.
                                $this.remove();

                                var active_notification_count = 0,
                                    dismissed_notification_count = 0;

                                // foreach notification in #charitable-plugin-notifications .charitable-notification, move it to the dismissed section.
                                $('#charitable-plugin-notifications .charitable-notification').each(function() {
                                    var the_counts = app.moveAMNotificationToDismissed( $(this) );
                                    active_notification_count = the_counts.active_notification_count,
                                    dismissed_notification_count = the_counts.dismissed_notification_count;
                                });

                                // remove the notifications which should be none but still.
                                $('.charitable-notification-cards.notification-cards-active .charitable-notification').remove();
                                // ..same for the dashboard notifications, if it exists.
                                if ( $('#charitable-dashboard-report-sections').length > 0 ) {
                                    $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notification').remove();
                                }

                                // For the dashboard notifications, if there are no more notifications, show the no-items message.
                                $('.charitable-dashboard-notifications').find('.no-items').removeClass('charitable-hidden');
                                $('.charitable-dashboard-notifications').find('.the-list').addClass('charitable-hidden');

                                // change the text at the top of the notifications.
                                $('#charitable-plugin-notifications .new-notifications').html('No new notifications');
                                // change the text of the dashboard notifications, if it exists.
                                $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications .header h4').html('Notifications');

                                // remove the red dot from notifications icon and the WP admin menu.
                                $('.charitable-notification-inbox').find('.number').remove();
                                $('#toplevel_page_charitable').find('li.notifications .charitable-menu-notification-indicator').remove();

                                $('#charitable-plugin-notifications').find('#new-notifications-count').html( active_notification_count );
                                $('#charitable-dashboard-report-sections').find('#new-notifications-count-dashboard').html( active_notification_count );

                                $('#charitable-plugin-notifications').find('#dismissed-notifications-count').html( dismissed_notification_count );

                                $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications').find('.charitable-report-ui').html('<div class="no-items"><p><strong>There is currently no active notifications.</strong></p><p class="link charitable-view-notifications"><a href="#">View Notification<img src="/wp-content/plugins/charitable/assets/images/icons/east.svg"></a></p></div>');

                            }
                        }
                    });
                });
                // switch between active and dismissed notifications.
                $('.charitable-plugin-notifications .notification-menu').on( 'click', '.dismissed-notifications a', function( e ) {
                    e.preventDefault();
                    var $this = $(this),
                        $container = $this.closest('.notification-menu'),
                        $dismissed = $container.find('.notification-cards-dismissed'),
                        $active = $container.find('.notification-cards-active'),
                        $new_notifications_title = $container.find('.new-notifications'),
                        $old_notifications_title = $container.find('.old-notifications'),
                        $dismiss_all_element = $container.find('.dismiss-all a.dismiss'),
                        active_notification_count = $('#charitable-plugin-notifications .notification-cards-active .charitable-notification').length,
                        dismissed_notification_count = $('#charitable-plugin-notifications .notification-cards-dismissed .charitable-notification').length;

                    if ( $this.data('status') !== 'active' ) {
                        // if active is only one, use the singular form... otherwise use the plural form.
                        if ( active_notification_count === 1 ) {
                            $this.html('Active Notification');
                        } else {
                            $this.html('Active Notifications');
                        }
                        $this.data('status', 'active');
                    } else {
                        // if dismissed is only one, use the singular form... otherwise use the plural form.
                        if ( dismissed_notification_count === 1 ) {
                            $this.html('Dismissed Notification');
                        } else {
                            $this.html('Dismissed Notification');
                        }
                        $this.data('status', 'dismissed');
                    }

                    $active.toggleClass('notification-cards-visible');
                    $dismissed.toggleClass('notification-cards-visible');
                    $new_notifications_title.toggleClass('notifications-visible');
                    $old_notifications_title.toggleClass('notifications-visible');
                    $dismiss_all_element.toggleClass('charitable-hidden');
                });
            }
        },

        /**
         * Replace the text "Notifications" with "Notification".
         * This is used in the AM notifications.
         *
         * @param {string} selector The selector of the element to replace the text.
         *
         * @return {void}
         *
         * @since 1.8.3
        */
        replaceNotifications: function( selector ) {
            $(selector).each(function() {
                // Get the current text
                var currentText = $(this).text();
                // Replace "Notifications" with "Notification"
                var newText = currentText.replace(/Notifications/g, "Notification");
                // Update the element's text
                $(this).text(newText);
            });
        },

        replaceNotification: function( selector ) {
            $(selector).each(function() {
                // Get the current text
                var currentText = $(this).text();
                // Replace "Notifications" with "Notification"
                var newText = currentText.replace(/Notification/g, "Notifications");
                // Update the element's text
                $(this).text(newText);
            });
        },

        /**
         * Move the notification to the dismissed section.
         *
         * @param {object} $notification The jQuery object of the notification.
         *
         * @since 1.8.3
         *
         * @return {void}
         */
        moveAMNotificationToDismissed: function( $notification ) {

            // remove the "no dismissed notifications" title.
            if ( $('.notification-no-dismissed-title').length > 0 ) {
                $('.notification-no-dismissed-title').closest('.notification-card').remove();
            }

            // Remove the dismiss button in the notification that is being dismissed.
            $notification.find('.dismiss').remove();

            // mmove $notification to the dismissed section - it should be the last child inside the parent of charitable-notification-cards.notification-cards-dismissed.
            $notification.appendTo('#charitable-plugin-notifications .notification-cards-dismissed').show();

            // if we are on the dashboard make sure this notification is also removed from the dashboard notifications pane.
            if ( $('#charitable-dashboard-report-sections').length > 0 ) {
                $('#charitable-dashboard-report-sections').find('.charitable-dashboard-notifications .charitable-notification[data-notification-id="' + $notification.data('notification-id') + '"]').remove();
            }

            // return accurate counts of the notifications in the sidebar.
            var active_notification_count = $('#charitable-plugin-notifications .notification-cards-active .charitable-notification').length,
            dismissed_notification_count = $('#charitable-plugin-notifications .notification-cards-dismissed .charitable-notification').length;

            return { active_notification_count: active_notification_count, dismissed_notification_count: dismissed_notification_count };

        },

        /**
         * Initialize the notifications.
         *
         * @return {void}
         *
         * @since 1.8.2
         *
         */
        initNotifications: function() {

            // when a prev or next button is clicked inside the notification navigation.
            $('body .charitable-dashboard-notification-navigation').on( 'click', 'a', function( e ) {
                e.preventDefault();

                var $this = $(this),
                    $container = $this.closest('.charitable-dashboard-notifications'),
                    $current = $container.find('.charitable-dashboard-notification:not(.charitable-hidden)'),
                    notification_number = $current.data('notification-number'),
                    notification_count = $container.find('.charitable-dashboard-notification').length,
                    new_number = notification_number;

                if ( $this.hasClass('next') ) {
                    new_number++;
                    if ( new_number > notification_count ) {
                        new_number = 1;
                    }
                } else if ( $this.hasClass('prev') ) {
                    new_number--;
                    if ( new_number < 1 ) {
                        new_number = notification_count;
                    }
                }

                // Fade out current, then fade in next.
                $current.fadeOut( 200, function() {
                    $current.addClass('charitable-hidden').removeAttr('style');
                    $container.find('.charitable-dashboard-notification[data-notification-number="' + new_number + '"]').hide().removeClass('charitable-hidden').fadeIn( 200 );
                });

                // Update the counter text.
                $container.find('.charitable-dashboard-notification-counter').text( new_number + ' of ' + notification_count );
            }
            );

            // when the close button is clicked, remove the notificaiton from the HTML and do an ajax call removing the notficaition from the database.
            $('body .charitable-dashboard-notifications').on( 'click', '.charitable-remove-dashboard-notification', function( e ) {
                e.preventDefault();

                var $this = $(this),
                    $container = $this.closest('.charitable-dashboard-notifications'),
                    notification_id = $container.find('.charitable-dashboard-notification:not(.charitable-hidden)').data('notification-id');

                // ajax call to diable the notification.
                $.ajax({
                    type: 'POST',
                    url: ajaxurl, // eslint-disable-line no-undef
                    data: {
                        action: 'charitable_disable_dashboard_notification',
                        notification_id: notification_id,
                        nonce: charitable_admin.nonce // eslint-disable-line no-undef
                    },
                    success: function( response ) {
                        if ( response.success ) {
                            var $dismissed = $container.find('.charitable-dashboard-notification[data-notification-id="' + notification_id + '"]');

                            $dismissed.fadeOut( 200, function() {
                                $dismissed.remove();
                                // count the number of notifications that are left.
                                var notification_count = $container.find('.charitable-dashboard-notification').length;
                                // if there are no more notifications, remove the entire container.
                                if ( notification_count === 0 ) {
                                    $container.fadeOut( 200, function() { $container.remove(); });
                                } else {
                                    // Show the first remaining notification with fade.
                                    $container.find('.charitable-dashboard-notification').first().removeClass('charitable-hidden').hide().fadeIn( 200 );

                                    if ( notification_count <= 1 ) {
                                        // Hide navigation when only one notification remains.
                                        $container.find('.charitable-dashboard-notification-navigation').html('');
                                    } else {
                                        // Update the counter text.
                                        var current_number = $container.find('.charitable-dashboard-notification:not(.charitable-hidden)').first().data('notification-number');
                                        $container.find('.charitable-dashboard-notification-counter').text( current_number + ' of ' + notification_count );
                                    }
                                }
                            });
                        }
                    }
                });

            });

        },

        /**
         * Scroll to anchor.
         * If the url has a hash, scroll to the anchor.
         *
         * @return {void}
         *
         * @since 1.8.2
        */
        scrollToAnchor: function() {
            // get the hash from the url.
            var hash = window.location.hash;
            hash = hash.substring(1);

            if ( hash ) {

                // santitize the hash.
                hash = hash.replace(/[^a-zA-Z0-9-_]/g, '');

                var $target = $( 'a#wpchr-' + hash ),
                    $container = false;

                if ( $target.length ) {
                    $container = $target.length ? $target.closest('.charitable-growth-content') : false;
                }

                // remove all css classes 'charitable-selected' from all containers.
                $('.charitable-growth-content').removeClass('charitable-selected');

                if ( $target.length ) {
                    // scroll to the target.
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100
                    }, 1000);
                    // after 2 seconds, slowly fade the background color to white.
                    $container.addClass('charitable-selected');
                }
            }
        },

        /**
         * Create a new campaign popup (deprecated).
         *
        */
        newCampaignPopup: function() {

            var admin_url = typeof charitable_admin !== "undefined" && typeof charitable_admin.admin_url !== "undefined" ? charitable_admin.admin_url : '/wp-admin/', // eslint-disable-line no-undef
                box_width = $(window).width() * .50;

            if ( box_width > 770 ) {
                box_width = 770;
            }

            $.confirm( {
                title: 'Create Campaign',
                content: '' +
                '<form id="create-campaign-form" method="POST" action="' + admin_url + 'admin.php?page=charitable-campaign-builder&view=template" class="formName">' +
                '<div class="form-group">' +
                '<label>Name:</label>' +
                '<input type="text" placeholder="Campaign Name" value="My New Campaign" name="campaign_name" class="name campaign_name form-control" required />' +
                '</div>' +
                '</form>',
                closeIcon: true,
                boxWidth: box_width + 'px',
                useBootstrap: false,
                type: 'create-campaign',
                animation: 'none',
                buttons: {
                    formSubmit: {
                        text: 'Create Campaign',
                        btnClass: 'btn-green',
                        action: function () {
                            var campaign_name = this.$content.find('.campaign_name').val().trim();
                            if ( ! campaign_name ){
                                $.alert('Please provide a valid campaign name.');
                                return false;
                            } else {
                                $('.jconfirm-buttons button.btn').html('Creating...');
                                $('#create-campaign-form').submit();
                                return false;
                            }
                        }
                    }
                },
                onContentReady: function () {

                }
            } );

        },

        /**
         * Create a new campaign popup.
         * This is the new campaign popup that is used in the campaign list page.
         *
         * @since 1.8.2
         *
         * @return {void}
        */
        campaignListBannerPopup: function() {

            var plugin_asset_dir = typeof charitable_admin.plugin_asset_dir !== "undefined" ? charitable_admin.plugin_asset_dir : '/wp-content/plugins/charitable/assets'; // eslint-disable-line no-undef

            $.confirm( {
                title: false,
                content: '' +
                '<div class="charitable-lite-pro-popup">' +
                    '<div class="charitable-lite-pro-popup-left" >' +
                        '<h1>The Ambassadors Extension is only available for Charitable Pro users.</h1>' +
                        '<h2>Harness the power of supporter networks and friends to reach more people and raise more money for your cause.</h2>' +
                        '<ul>' +
						'<li><p>Create a crowdfunding platform (similar to GoFundMe)</p></li>' +
                        '<li><p>Simplified fundraiser creation and management</p></li>' +
                        '<li><p>Let supporters fundraise together through our Teams feature</p></li>' +
                        '<li><p>Integrate with email marketing to follow up with campaign creators</p></li>' +
                        '<li><p>Give people a place to fundraise for their own cause</p></li>' +
                        '</ul>' +
                        '<a href="https://wpcharitable.com/lite-vs-pro/?utm_source=WordPress&utm_medium=Ambassadors+Campaign+Modal+Unlock&utm_campaign=WP+Charitable" target="_blank" class="charitable-lite-pro-popup-button">Unlock Peer-to-Peer Fundraising</a>' +
                        '<a href="https://wpcharitable.com/lite-vs-pro/?utm_source=WordPress&utm_medium=Ambassadors+Campaign+Modal+More&utm_campaign=WP+Charitable" target="_blank" class="charitable-lite-pro-popup-link">Or learn more about the Ambassadors extension &rarr;</a>' +
                    '</div>' +
                    '<div class="charitable-lite-pro-popup-right" >' +
                    '<img src="' + plugin_asset_dir + 'images/lite-to-pro/ambassador.png" alt="Charitable Ambassador Extension" >' +
                    '</img>' +
                '</div>',
                closeIcon: true,
                alignMiddle: true,
                boxWidth: '986px',
                useBootstrap: false,
                animation: 'none',
                buttons: false,
                type: 'lite-pro-ad',
                onContentReady: function () {

                }
            } );

        },

		/**
		 * Welcome activation page.
		 *
		 */
		initWelcome: function() {

			// Open modal and play video.
			$( document ).on( 'click', '#charitable-welcome .play-video', function( event ) {
				event.preventDefault();

				const video = '<div class="video-container"><iframe width="1280" height="720" src="https://www.youtube-nocookie.com/embed/834h3huzzk8?rel=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe></div>';

                if ( typeof jconfirm !== 'undefined' ) {

                    // jquery-confirm defaults.
                    jconfirm.defaults = {
                        closeIcon: true,
                        backgroundDismiss: false,
                        escapeKey: true,
                        animationBounce: 1,
                        useBootstrap: false,
                        theme: 'modern',
                        animateFromElement: false
                    };

                    $.dialog( {
                        title: false,
                        content: video,
                        closeIcon: true,
                        boxWidth: '1300'
                    } );

                }

			} );
		},

        /**
         * Initialize the upgrade modal.
         *
         * @since 1.8.1.15
         *
         * @return {void}
         *
        */
        initUpgradeModal: function() {

            // Upgrade information modal for upgrade links.
            $( document ).on( 'click', '.charitable-upgrade-modal', function() {

                $.alert( {
                    title        : charitable_admin.thanks_for_interest, // eslint-disable-line no-undef
                    content      : charitable_admin.upgrade_modal, // eslint-disable-line no-undef
                    icon         : 'fa fa-info-circle',
                    type         : 'blue',
                    boxWidth     : '550px',
                    useBootstrap : false,
                    theme        : 'modern,charitable-install-form',
                    closeIcon    : false,
                    draggable    : false,
                    buttons: {
                        confirm: {
                            text: charitable_admin.ok, // eslint-disable-line no-undef
                            btnClass: 'btn-confirm',
                            keys: [ 'enter' ],
                        }, // eslint-disable-line
                    },
                } );
            } );

        },

        /**
         * Initialize the Square legacy modal.
         *
         * @since 1.8.7
         *
         * @return {void}
         *
        */
        initSquareLegacyModal: function() {

            // Check if we're on the gateway settings page and Square Core enable button is clicked.
            $( document ).on( 'click', 'a[href*="charitable_action=enable_gateway"][href*="gateway_id=square_core"]', function( e ) {

                // Check if Square Legacy is active and Square Core is not active.
                if ( typeof CHARITABLE !== 'undefined' && CHARITABLE.square_legacy_active && !CHARITABLE.square_core_active ) {
                    e.preventDefault();

                    $.alert( {
                        title: CHARITABLE.heads_up,
                        content: CHARITABLE.square_legacy_modal_message,
                        icon: 'fa fa-exclamation-circle',
                        type: 'orange',
                        buttons: {
                            confirm: {
                                text: CHARITABLE.ok,
                                btnClass: 'btn-confirm',
                                keys: [ 'enter' ],
                                action: function() {
                                    // Perform AJAX call to switch gateways.
                                    $.post( CHARITABLE.ajax_url, {
                                        action: 'charitable_switch_square_gateways',
                                        nonce: CHARITABLE.nonce
                                    }, function( response ) {
                                        if ( response.success ) {
                                            window.location.href = response.data.redirect_url;
                                        } else {
                                            // Show error message if AJAX fails.
                                            $.alert( {
                                                title: CHARITABLE.oops,
                                                content: CHARITABLE.square_legacy_switch_error,
                                                icon: 'fa fa-exclamation-circle',
                                                type: 'red',
                                                buttons: {
                                                    confirm: {
                                                        text: CHARITABLE.ok,
                                                        btnClass: 'btn-confirm',
                                                        keys: [ 'enter' ]
                                                    }
                                                }
                                            } );
                                        }
                                    } ).fail( function() {
                                        // Show error message if AJAX request fails.
                                        $.alert( {
                                            title: CHARITABLE.oops,
                                            content: CHARITABLE.square_legacy_switch_error,
                                            icon: 'fa fa-exclamation-circle',
                                            type: 'red',
                                            buttons: {
                                                confirm: {
                                                    text: CHARITABLE.ok,
                                                    btnClass: 'btn-confirm',
                                                    keys: [ 'enter' ]
                                                }
                                            }
                                        } );
                                    } );
                                }
                            },
                            cancel: {
                                text: CHARITABLE.cancel,
                                keys: [ 'esc' ]
                            }
                        }
                    } );
                }
            } );

            // Check if we're on the gateway settings page and Square Legacy enable button is clicked.
            $( document ).on( 'click', 'a[href*="charitable_action=enable_gateway"][href*="gateway_id=square"]', function( e ) {

                // Check if Square Core is active and Square Legacy is not active.
                if ( typeof CHARITABLE !== 'undefined' && CHARITABLE.square_core_active && !CHARITABLE.square_legacy_active ) {
                    e.preventDefault();

                    $.alert( {
                        title: CHARITABLE.heads_up,
                        content: CHARITABLE.square_core_modal_message,
                        icon: 'fa fa-exclamation-circle',
                        type: 'orange',
                        buttons: {
                            confirm: {
                                text: CHARITABLE.ok,
                                btnClass: 'btn-confirm',
                                keys: [ 'enter' ],
                                action: function() {
                                    // Perform AJAX call to switch gateways.
                                    $.post( CHARITABLE.ajax_url, {
                                        action: 'charitable_switch_square_core_to_legacy',
                                        nonce: CHARITABLE.nonce
                                    }, function( response ) {
                                        if ( response.success ) {
                                            window.location.href = response.data.redirect_url;
                                        } else {
                                            // Show error message if AJAX fails.
                                            $.alert( {
                                                title: CHARITABLE.oops,
                                                content: CHARITABLE.square_legacy_switch_error,
                                                icon: 'fa fa-exclamation-circle',
                                                type: 'red',
                                                buttons: {
                                                    confirm: {
                                                        text: CHARITABLE.ok,
                                                        btnClass: 'btn-confirm',
                                                        keys: [ 'enter' ]
                                                    }
                                                }
                                            } );
                                        }
                                    } ).fail( function() {
                                        // Show error message if AJAX request fails.
                                        $.alert( {
                                            title: CHARITABLE.oops,
                                            content: CHARITABLE.square_legacy_switch_error,
                                            icon: 'fa fa-exclamation-circle',
                                            type: 'red',
                                            buttons: {
                                                confirm: {
                                                    text: CHARITABLE.ok,
                                                    btnClass: 'btn-confirm',
                                                    keys: [ 'enter' ]
                                                }
                                            }
                                        } );
                                    } );
                                }
                            },
                            cancel: {
                                text: CHARITABLE.cancel,
                                keys: [ 'esc' ]
                            }
                        }
                    } );
                }
            } );

        },

    };

    // Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ); // eslint-disable-line no-undef

CharitableAdminUI.init();
